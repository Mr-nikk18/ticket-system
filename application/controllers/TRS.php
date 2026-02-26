<?php

class TRS extends My_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        if ($this->session->userdata('is_login') !== true) {
            redirect('Auth/index');
        }
    }

    /* ================= DASHBOARD ================= */

    public function dashboard()
    {
        $this->load->model('TRS_model');

        $role_id = $this->session->userdata('role_id');
        $user_id = $this->session->userdata('user_id');

        $uid = ($role_id == 1) ? $user_id : null;

        $data['open_count']       = $this->TRS_model->count_tickets_by_status(1, $uid);
        $data['in_proess_count'] = $this->TRS_model->count_tickets_by_status(2, $uid);
        $data['resolved_count']   = $this->TRS_model->count_tickets_by_status(3, $uid);
        $data['closed_count']     = $this->TRS_model->count_tickets_by_status(4, $uid);



        if ($role_id == 1) {
            $data['recent_tickets'] = $this->TRS_model->get_user_recent_tickets($user_id, 5);
        } else {
            $data['recent_tickets'] = $this->TRS_model->get_all_recent_tickets(5);
        }

      $this->load->view('Same_pages/Dashboard', $data);
       
    }

    public function confirm_ticket($ticket_id, $answer)
    {
        $this->load->model('TRS_model');
        $this->load->library('session');

        // only USER allowed
        if ($this->session->userdata('role_id') != 1) {
            show_error('Unauthorized');
        }

        $ticket = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket || $ticket['user_id'] != $this->session->userdata('user_id')) {
            show_error('Unauthorized');
        }

      if ($answer === 'yes') {

      $this->TRS_model->update_ticket($ticket_id, [
        'status_id' => $this->TRS_model->get_status_id('closed')
            ]);

            $this->session->set_flashdata('success', 'Ticket closed successfully');

            } else {

            $this->TRS_model->update_ticket($ticket_id, [
                'status_id' => $this->TRS_model->get_status_id('in_progress')
            ]);


            // 🔥 SET ONE-TIME EDIT FLAG
            $reopen = $this->session->userdata('reopen_edit_allowed');
            if (!is_array($reopen)) {
                $reopen = [];
            }

            $reopen[$ticket_id] = true;
            $this->session->set_userdata('reopen_edit_allowed', $reopen);

                    $this->session->set_flashdata('error', 'Issue not solved. You can edit this ticket only once.');
                }

                // 🔥 MUST REDIRECT — OTHERWISE BLANK PAGE
                redirect('TRS/list');
    }



    /* ================= LIST ================= */

 public function list($status = null)
{
    $this->load->model('TRS_model');

    $role_id = $this->session->userdata('role_id');
    $user_id = $this->session->userdata('user_id');

    $allowed_status = [1, 2, 3, 4];

    if ($status !== null && !in_array((int)$status, $allowed_status)) {
        show_error('Invalid status');
    }

    $status = $status !== null ? (int)$status : null;
 
    if ($role_id == 1) {
        $data['val'] = $this->TRS_model->get_user_tickets($user_id, $status);
    } else {
        $data['val'] = $this->TRS_model->get_all_tickets($status);
       
    }

    $data['current_status'] = $status;
    $this->load->view('Users/List', $data);
}


public function list_ajax($status = null)
{
    $this->load->model('TRS_model');

    $role_id = $this->session->userdata('role_id');
    $user_id = $this->session->userdata('user_id');

    $status = $status !== null ? (int)$status : null;

    if ($role_id == 1) {
        $data = $this->TRS_model->get_user_tickets($user_id, $status);
    } else {
        $data = $this->TRS_model->get_all_tickets($status);
    }

    echo json_encode(['data' => $data]);
}




    /* ================= ADD ================= */

    public function see()
    {
        $this->load->view('Users/Add_ticket');
    }

   public function add()
{
    $this->load->model('TRS_model');

    $ticket_id = $this->TRS_model->insert_ticket([
        'user_id' => $this->session->userdata('user_id'),
        'title' => $this->input->post('title'),
        'description' => $this->input->post('description'),
        'status_id' => 1
    ]);

    $tasks = $this->input->post('tasks');

    if(!empty($tasks)){
        $this->TRS_model->add_insert_tasks($ticket_id, $tasks);
    }

      if ($ticket_id) {

            // ✅ flashdata for page reload
            $this->session->set_flashdata('success', 'Ticket added successfully');

            // ✅ ajax response
            //echo json_encode(['status' => true]);
        } else {
            $this->session->set_flashdata('failed', 'Unable to move forward');
            //echo json_encode(['status' => false]);
        }

    redirect('TRS/list');
}


public function add_ajax()
{
    $this->load->model('TRS_model');

    // 1️⃣ Insert Ticket
    $this->db->insert('tickets', [
        'user_id'     => $this->session->userdata('user_id'),
        'title'       => $this->input->post('title', true),
        'description' => $this->input->post('description', true),
        'status_id'   => 1
    ]);

    // 2️⃣ Get Generated ticket_id
    $ticket_id = $this->db->insert_id();

    // 3️⃣ Insert Tasks
    $tasks = $this->input->post('tasks');

    if (!empty($tasks)) {
        foreach ($tasks as $position => $task) {
            if (!empty($task)) {
                $this->db->insert('ticket_tasks', [
                    'ticket_id'   => $ticket_id,
                    'task_title'  => $task,
                    'is_completed'=> 0,
                    'position'    => $position,
                    'created_by'  => $this->session->userdata('user_id')
                ]);
            }
        }
    }

    echo json_encode(['status' => true]);
  if ($tasks) {
            // ✅ flashdata for page reload
            $this->session->set_flashdata('success', 'Ticket added successfully');

            // ✅ ajax response
            echo json_encode(['status' => true]);
        } else {
            $this->session->set_flashdata('failed', 'Unable to move forward');
            echo json_encode(['status' => false]);
        }
    
}



    /* ================= ACCEPT / LEAVE ================= */

    public function accept_ticket($ticket_id)
    {
        if (!in_array($this->session->userdata('role_id'), [2, 3])) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $user_id = $this->session->userdata('user_id');

        // 1️⃣ Update ticket
        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => $user_id,
            'status_id'               => 2
        ]);

        // 2️⃣ Insert history (ACCEPT)
        $this->TRS_model->insert_assignment_history([
            'ticket_id'   => $ticket_id,
            'action_type' => 'accept',
            'assigned_to' => $user_id,
            'assigned_by' => $user_id,
            'remarks'     => 'Ticket accepted by developer',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        redirect('TRS/my_tickets');
    }


    public function leave_ticket($ticket_id)
    {
        if ($this->session->userdata('role_id') == 1) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $dev_id = $this->session->userdata('user_id');  // 🔥 MISSING LINE

        // 1️⃣ Update main ticket
        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => null,
            'status_id' => 1
        ]);

        // 🔥 INSERT HISTORY (ONLY ONCE)
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'    => $ticket_id,
            'action_type' => 'leave',
            'assigned_to' => null,          // ab kisi ke paas nahi
            'assigned_by' => $this->session->userdata('user_id'), // 🔥 IMPORTANT
            'remarks'     => 'Developer left the ticket',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        redirect('TRS/list');
    }



    /* ================= EDIT ================= */


    public function edit($ticket_id)
    {
        $this->load->model('TRS_model');

        $role_id = $this->session->userdata('role_id');
        $ticket  = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket) show_error('Ticket not found');

        // ---- USER RULES ----
        if ($role_id == 1) {

            // 🚫 Never allow edit if CLOSED or RESOLVED
            if (in_array($ticket['status_id'], [4,3])) {
                show_error('Unauthorized');
            }

            // 🚫 Open but already assigned (normal flow)
            if ($ticket['status_id'] == 1 && $ticket['assigned_engineer_id'] != null) {
                show_error('Unauthorized');
            }

            // 🔥 IN_PROGRESS case (this is the important part)
            if ($ticket['status_id'] == 2) {

                $reopen = $this->session->userdata('reopen_edit_allowed');

                // ✅ Allow ONLY if this ticket was reopened and flag exists
                if (is_array($reopen) && isset($reopen[$ticket_id]) && $reopen[$ticket_id] === true) {
                    // allowed ONCE ✅
                } else {
                    // 🚫 Normal in_progress OR already edited once
                    show_error('Unauthorized');
                }
            }
        }

        // IT Head → developer list
        if ($role_id == 3) {
            $data['developers'] = $this->TRS_model->get_all_developers();
        }

        $data['value'] = $ticket;
        $data['assign_mode'] = $this->input->get('assign');

        $this->load->view('Users/Edit', $data);
    }

public function edit_ajax()
{
    $this->load->model('TRS_model');
    $ticket_id = $this->input->post('ticket_id');

    $role_id = $this->session->userdata('role_id');
    $ticket  = $this->TRS_model->get_data_by_id($ticket_id);
    $developers = $this->TRS_model->get_all_developers();
    $tasks = $this->TRS_model->get_tasks_by_ticket($ticket_id); // 🔥 NEW

    if (!$ticket) {
        echo json_encode(['status' => false, 'msg' => 'Ticket not found']);
        exit;
    }

    /* USER RULES (unchanged) */

    if ($role_id == 1) {

        if (in_array($ticket['status_id'], [4,3])) {
            echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        if ($ticket['status_id'] == 1 && $ticket['assigned_engineer_id'] != null) {
            echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        if ($ticket['status_id'] == 2) {
            $reopen = $this->session->userdata('reopen_edit_allowed');

            if (!is_array($reopen) || !isset($reopen[$ticket_id]) || $reopen[$ticket_id] !== true) {
                echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
                exit;
            }
        }
    }

    echo json_encode([
        'status'     => true,
        'data'       => $ticket,
        'developers' => $developers,
        'tasks'      => $tasks  // 🔥 SEND TASKS
    ]);
    exit;
}

public function update_ajax()
{
    $ticket_id = $this->input->post('ticket_id');
    $role_id   = $this->session->userdata('role_id');

    if (!$ticket_id) {
        echo json_encode([
            'status' => false,
            'msg' => 'Invalid ticket'
        ]);
        return;
    }

    $data = [];

    // USER
    if ($role_id == 1) {
        $data['title']       = $this->input->post('title', true);
        $data['description'] = $this->input->post('description', true);
    }

    // DEVELOPER
    if ($role_id == 2) {
        $data['status_id'] = $this->input->post('status_id');
    }

    // ADMIN
    if ($role_id == 3) {
        $data['assigned_engineer_id'] = $this->input->post('assigned_engineer_id');
        $data['status_id']            = $this->input->post('status_id');
    }

    $this->load->model('TRS_model');

    // 🔥 SAFE UPDATE (only if data exists)
    if (!empty($data)) {
        $this->TRS_model->update_ticket($ticket_id, $data);
    }

    // 🔥 TASK UPDATE (only for user)
    if ($role_id == 1) {

        $tasks = $this->input->post('tasks');

        if (!empty($tasks)) {

            // delete old
            $this->db->where('ticket_id', $ticket_id)
                     ->delete('ticket_tasks');

            $position = 1;

            foreach ($tasks as $task) {

                if (trim($task) != '') {

                    $this->db->insert('ticket_tasks', [
                        'ticket_id'   => $ticket_id,
                        'task_title'  => $task,
                        'is_completed'=> 0,
                        'position'    => $position,
                        'created_by'  => $this->session->userdata('user_id')
                    ]);

                    $position++;
                }
            }
        }
    }

    echo json_encode(['status' => true]);
}

    public function update($ticket_id)
    {
        $this->load->model('TRS_model');

        $role_id = $this->session->userdata('role_id');
        $ticket  = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket) show_error('Ticket not found');

        $data = []; // start empty

        /* ================= USER PERMISSION CHECK ================= */
        if ($role_id == 1) {

            // 🚫 Never allow update if CLOSED or RESOLVED
            if (in_array($ticket['status_id'], [4,3])) {
                show_error('Unauthorized');
            }

            // 🚫 If OPEN but already assigned → block
            if ($ticket['status_id'] == 1 && $ticket['assigned_engineer_id'] != null) {
                show_error('Unauthorized');
            }

            // 🔥 If IN_PROGRESS (reopened case) → allow ONLY ONCE using session flag
            if ($ticket['status_id'] == 2) {
                $reopen = $this->session->userdata('reopen_edit_allowed');

                if (!isset($reopen[$ticket_id]) || $reopen[$ticket_id] !== true) {
                    show_error('Unauthorized');
                }
            }

            // ✅ USER can update only title & description
            $data = [
                'title'       => $this->input->post('title'),
                'description' => $this->input->post('description')
            ];
        }

        /* ================= DEVELOPER ================= */ elseif ($role_id == 2) {
            $data = [
                'status' => $this->input->post('status_id')
            ];
        }

        /* ================= IT HEAD ================= */ elseif ($role_id == 3) {   // IT Head / Admin

            $posted_status = $this->input->post('status_id');
            $new_assigned  = $this->input->post('assigned_engineer_id');

            $data = [
                'status' => $posted_status
            ];

            // old assigned engineer
            $old_assigned = $ticket['assigned_engineer_id'];

            $assignmentChanged = false;

            // Assign developer (optional)
            if (!empty($new_assigned)) {

                $data['assigned_engineer_id'] = $new_assigned;

                // status logic
                if (!in_array($posted_status, [3, 4])) {
                    $data['status'] = 2;
                }

                // 🔥 check if assignment really changed
                if ($old_assigned != $new_assigned) {
                    $assignmentChanged = true;
                }
            }

            /* ================= UPDATE ================= */
            if (!empty($data)) {
                $this->TRS_model->update_ticket($ticket_id, $data);
            }

            /* ================= HISTORY LOG (ONLY WHEN ASSIGNMENT CHANGES) ================= */
            if ($assignmentChanged) {

                $this->db->insert('ticket_assignment_history', [
                    'ticket_id'    => $ticket_id,
                    'assigned_to' => $new_assigned,                          // kisko diya
                    'assigned_by' => $this->session->userdata('user_id'),   // admin
                    'remarks'     => 'Ticket assigned by admin',
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
            }
        }


        /* ================= UPDATE ================= */
        if (!empty($data)) {
            $this->TRS_model->update_ticket($ticket_id, $data);
        }

        /* ================= REMOVE ONE-TIME FLAG AFTER USER EDIT ================= */
        if ($role_id == 1 && $ticket['status'] ==2) {
            $reopen = $this->session->userdata('reopen_edit_allowed');

            if (isset($reopen[$ticket_id])) {
                unset($reopen[$ticket_id]);
                $this->session->set_userdata('reopen_edit_allowed', $reopen);
            }
        }

        redirect('TRS/list');
    }


    /* ================= DELETE ================= */

    public function delete($ticket_id)
    {
        $this->load->model('TRS_model');
        $this->TRS_model->delete_ticket($ticket_id);
        redirect('TRS/list');
    }

    /* ================= DEV MY TICKETS ================= */

    public function my_tickets()
    {
        if ($this->session->userdata('role_id') == 1) {
             $this->session->set_flashdata('failed','🚫Unauthorized🚫');
             redirect('Dashboard');
        }

        $this->load->model('TRS_model');

        $data['val'] = $this->TRS_model
            ->get_my_accepted_tickets($this->session->userdata('user_id'));


        $this->load->view('Users/List', $data);
    }

    /* ================= ADD USER FORM ================= */
    public function add_user()
    {
        if ($this->session->userdata('role_id') != 3) {
            $this->session->set_flashdata('failed','🚫Unauthorized🚫');
            redirect('TRS/user_list');
        }

        $this->load->view('Users/Add_user');
    }

    /* ================= SAVE USER ================= */
    public function save_user()
{
        if ($this->session->userdata('role_id') == 1) {
            show_error('Unauthorized access');
        }

        $this->load->model('User_model');

        $password = $this->input->post('password');

        $data = [
            'user_id' => $this->input->post('user_id'),
            'user_name'  => $this->input->post('user_name'),
            'name'       => $this->input->post('name'),
            'email'      => $this->input->post('email'),
            'company_name' => $this->input->post('company_name'),
            'phone'      => $this->input->post('phone'),
            'department' => $this->input->post('department'),
            'role_id'    => $this->input->post('role_id'), // 2 or 3
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'status'     => 'Active'
        ];

        $this->User_model->insert_user($data);

        $this->session->set_flashdata('success', 'User created successfully');
        redirect('TRS/user_list');
}


    public function save_userlist_ajax()
{
    if ($this->session->userdata('role_id') == 1) {
        echo json_encode([
            'status' => false,
            'message' => 'Unauthorized access'
        ]);
        return;
    }

    $this->load->model('User_model');

    $password = $this->input->post('password');

    $data = [
        'user_id'      => $this->input->post('user_id'),
        'user_name'   => $this->input->post('user_name'),
        'name'        => $this->input->post('name'),
        'email'       => $this->input->post('email'),
        'company_name'=> $this->input->post('company_name'),
        'phone'       => $this->input->post('phone'),
        'department'  => $this->input->post('department'),
        'role_id'     => $this->input->post('role_id'),
        'password'    => password_hash($password, PASSWORD_DEFAULT),
        'status'      => 'Active'
    ];

    if ($this->User_model->insert_user($data)) {
        echo json_encode([
            'status' => true,
            'message' => 'User created successfully'
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Failed to create user'
        ]);
    }
}


    /* ================= USER LIST ================= */
    public function user_list()
    {
        if ($this->session->userdata('role_id') != 3) {
              $this->session->set_flashdata('failed','🚫Unauthorized🚫');
              redirect('TRS/list');
        }

        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_all_staff();

        $this->load->view('Users/User_list', $data);
    }

    public function edit_userlist($user_id)
    {
        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_user_staff($user_id);


        $this->load->view('Users/Edit_userlist', $data);
    }

    public function edit_userlist_ajax()
{
    $user_id = $this->input->post('user_id');

    $this->load->model('User_model');
    $user = $this->User_model->get_user_staff($user_id);

    if($user){
        echo json_encode([
            'status' => true,
            'data'   => $user
        ]);
    }else{
        echo json_encode([
            'status' => false,
            'msg' => 'User not found'
        ]);
    }
}


    public function delete_userlist($user_id)
    {
        $this->load->model('User_model');
        $this->User_model->delete_user($user_id);

        $this->session->set_flashdata('success', 'User deleted successfully');
        redirect('TRS/user_list');
    }


    public function update_userlist($user_id)
    {


        $this->load->model('User_model');

        // user id coming from edit form

        if (!$user_id) {
            show_error('Invalid User ID');
        }

        $email = $this->input->post('email');

        // 🔥 DUPLICATE EMAIL CHECK (IGNORE CURRENT USER)
        $check = $this->db
            ->where('email', $email)
            ->where('user_id !=', $user_id)   // 👈 current user ignore
            ->get('users')
            ->row();

        if ($check) {
            $this->session->set_flashdata('failed', 'Email already exists');
            redirect('TRS/Edit_userlist' . $user_id);   // apne edit page ka URL yahan rakho
        }



        $data = [
            'user_name'  => $this->input->post('user_name'),
            'name'       => $this->input->post('name'),
            'email'      => $email,
            'phone'      => $this->input->post('phone'),
            'company_name' => $this->input->post('company_name'),
            'department' => $this->input->post('department'),
            'role_id'    => $this->input->post('role_id'),
            'status'     => 'Active'
        ];



        // password update only if entered
        $password = trim($this->input->post('password'));
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $this->User_model->update_user_stuff($user_id, $data);
        redirect('TRS/User_list');
    }

    public function update_userlist_ajax()
{
    $this->load->model('User_model');

    $user_id = $this->input->post('user_id');

    if(!$user_id){
        echo json_encode(['status'=>false,'msg'=>'Invalid User ID']);
        return;
    }

    $email = $this->input->post('email');

    // Duplicate email check
    $check = $this->db
        ->where('email', $email)
        ->where('user_id !=', $user_id)
        ->get('users')
        ->row();

    if($check){
        echo json_encode([
            'status'=>false,
            'msg'=>'Email already exists'
        ]);
        return;
    }

    $data = [
        'user_name'    => $this->input->post('user_name'),
        'name'         => $this->input->post('name'),
        'email'        => $email,
        'phone'        => $this->input->post('phone'),
        'company_name' => $this->input->post('company_name'),
        'department'   => $this->input->post('department'),
        'role_id'      => $this->input->post('role_id'),
        'status'       => 'Active'
    ];

    // password update only if entered
    $password = trim($this->input->post('password'));
    if($password !== ''){
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if($this->User_model->update_user_stuff($user_id,$data)){
        echo json_encode([
            'status'=>true,
            'msg'=>'User updated successfully'
        ]);
    }else{
        echo json_encode([
            'status'=>false,
            'msg'=>'Update failed'
        ]);
    }
}


    public function ticket()
    {
        $ticket_id = $this->input->post('ticket_id');
        if ($this->session->userdata('role_id') == 1) {
            $this->session->flashdata("failed","🚫Unauthorized🚫");
            redirect('verify');
        }

        $this->load->model('Ticket_model');
        $data['history'] = $this->Ticket_model->TicketHistory($ticket_id);
        $this->load->view('Same_pages/Ticket_history', $data);
    }
    public function reassign_form($ticket_id)
    {
        // only admin and developer
        if ($this->session->userdata('role_id') == 1) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $data['ticket_id'] = $ticket_id;
        $data['developers'] = $this->TRS_model->get_all_developers();

        $this->load->view('Users/reassign_form', $data);
    }
    public function do_reassign()
    {
        // admin (3) OR developer (2)
        if (!in_array($this->session->userdata('role_id'), [2, 3])) {
            show_error('Unauthorized');
        }

        $ticket_id  = $this->input->post('ticket_id');
        $new_dev_id = $this->input->post('developer_id');
        $actor_id   = $this->session->userdata('user_id');
        $role_id    = $this->session->userdata('role_id');

        if (!$ticket_id || !$new_dev_id) {
            show_error('Invalid request');
        }

        // 1️⃣ Update ticket
        $this->db->where('ticket_id', $ticket_id)
            ->update('tickets', [
                'assigned_engineer_id' => $new_dev_id,
                'status_id'               => 2
            ]);

        // 2️⃣ History insert (REASSIGN)
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'   => $ticket_id,
            'action_type' => 'reassign',
            'assigned_to' => $new_dev_id,
            'assigned_by' => $actor_id,
            'remarks'     => ($role_id == 3)
                ? 'Ticket reassigned by IT Head'
                : 'Ticket reassigned by developer',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $this->session->set_flashdata('success', 'Ticket reassigned successfully');
        redirect('TRS/ticket');
    }

public function do_reassign_ajax()
{
    if (!in_array($this->session->userdata('role_id'), [2, 3])) {
        show_error('Unauthorized');
    }

    $ticket_id  = $this->input->post('ticket_id');
    $new_dev_id = $this->input->post('assigned_engineer_id');
    $reason     = trim($this->input->post('reason'));
    $actor_id   = $this->session->userdata('user_id');
    $role_id    = $this->session->userdata('role_id');

    if ($new_dev_id == $actor_id) {
        echo json_encode(['status' => false, 'msg' => 'You cannot reassign ticket to yourself']);
        return;
    }

    if ($reason === '') {
        echo json_encode(['status' => false, 'msg' => 'Reason is required for reassign']);
        return;
    }

    $this->load->model('TRS_model');

    $this->db->trans_start();

    $this->TRS_model->update_ticket($ticket_id, [
        'assigned_engineer_id' => $new_dev_id,
        'status_id' => 2
    ]);

    $systemRemark = ($role_id == 3)
        ? 'Ticket reassigned by IT Head'
        : 'Ticket reassigned by Developer';

    $this->TRS_model->insert_reassignment_history([
        'ticket_id'   => $ticket_id,
        'action_type' => 'reassign',
        'assigned_to' => $new_dev_id,
        'assigned_by' => $actor_id,
        'remarks'     => $systemRemark . "\nReason: " . $reason,
        'created_at'  => date('Y-m-d H:i:s')
    ]);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
        echo json_encode(['status' => false, 'msg' => 'Reassignment failed']);
        return;
    }

    echo json_encode(['status' => true, 'msg' => 'Ticket reassigned successfully']);
}
public function do_leave_ajax()
{
    if (!in_array($this->session->userdata('role_id'), [2, 3])) {
        show_error('Unauthorized');
    }

    $ticket_id = $this->input->post('ticket_id');
    $reason    = trim($this->input->post('reason'));
    $user_id   = $this->session->userdata('user_id');
    $role_id   = $this->session->userdata('role_id');

    if ($reason === '') {
        echo json_encode([
            'status' => false,
            'msg' => 'Reason is required'
        ]);
        return;
    }

    $this->load->model('TRS_model');

    // 🔐 Ensure user is assigned to ticket
    $ticket = $this->TRS_model->get_data_by_id($ticket_id);
    

    if (!$ticket || $ticket['assigned_engineer_id'] != $user_id) {
        echo json_encode([
            'status' => false,
            'msg' => 'You are not assigned to this ticket'
        ]);
        return;
    }

    // 1️⃣ Update ticket
    $this->TRS_model->update_ticket($ticket_id, [
        'assigned_engineer_id' => NULL,
        'status_id' => 1
    ]);

    // 2️⃣ History insert
    $systemRemark = ($role_id == 3)
        ? 'Ticket left by IT Head'
        : 'Ticket left by Developer';

    $this->TRS_model->insert_reassignment_history([
        'ticket_id'   => $ticket_id,
        'action_type' => 'leave',
        'assigned_to' => NULL,
        'assigned_by' => $user_id,
        'remarks'     => $systemRemark . "\nReason: " . $reason,
        'created_at'  => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['status' => true]);
}
  public function board()
{
    $this->load->model('Ticket_model');

    $data['tickets'] = $this->Ticket_model->get_board_tickets();

    $data['statuses'] = $this->db
        ->order_by('display_order','ASC')
        ->get('ticket_statuses')
        ->result();

    $permissions = $this->db
        ->where('role_id', $this->session->userdata('role_id'))
        ->where('allowed', 1)
        ->get('status_permissions')
        ->result();

    $formattedPermissions = [];

    foreach ($permissions as $p) {
        $formattedPermissions[$p->from_status][] = (int)$p->to_status;
    }

    $data['permissions'] = $formattedPermissions;
 
    $this->load->view('Developer/Karban_Board', $data);
}
public function reopen_ticket()
{
    if ($this->session->userdata('role_id') != 1) {
        echo json_encode(['status'=>false]);
        return;
    }

    $ticket_id = $this->input->post('ticket_id');

    $this->db->where('ticket_id', $ticket_id);
    $this->db->update('tickets', [
        'status_id' => 1,
        'closed_at' => NULL
    ]);

    echo json_encode(['status'=>true]);
}
public function confirm_resolution()
{
    $ticket_id = (int)$this->input->post('ticket_id');
    $answer    = $this->input->post('answer');

    if(!$ticket_id){
        echo json_encode(['success'=>false]);
        return;
    }

    $this->load->model('Ticket_model');

    if($answer === 'yes'){

        $this->db->where('ticket_id',$ticket_id);
        $this->db->update('tickets',[
            'status_id' => 4, // Closed
            'closed_at' => date('Y-m-d H:i:s')
        ]);

    } else {

        $this->db->where('ticket_id',$ticket_id);
        $this->db->update('tickets',[
            'status_id' => 1, // Open
            'closed_at' => NULL
        ]);
    }

    echo json_encode(['success'=>true]);
}
public function update_board_position()
{
    header('Content-Type: application/json');

    $order = json_decode($this->input->post('order'), true);
    $status_id = $this->input->post('status_id');

    $current_role_id = $this->session->userdata('role_id');
    $current_user_id = $this->session->userdata('user_id');

    $this->load->model('TRS_model');

    foreach ($order as $item) {

        $ticket = $this->db->where('ticket_id', $item['ticket_id'])
                           ->where('deleted_at IS NULL')
                           ->get('tickets')
                           ->row();

        if (!$ticket) {
            continue;
        }

        $from_status = $ticket->status_id;
        $to_status   = $status_id;

        /*
        =====================================================
        1️⃣ STATUS PERMISSION CHECK (DB Driven)
        =====================================================
        */

        if ($from_status != $to_status) {

            $permission = $this->TRS_model->check_status_permission(
                $current_role_id,
                $from_status,
                $to_status
            );

            if (!$permission) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Status change not allowed'
                ]);
                return;
            }
        }

        /*
        =====================================================
        2️⃣ ROLE OWNERSHIP CHECK (DB Driven)
        =====================================================
        */

        $role = $this->db->where('role_id', $current_role_id)
                         ->get('roles')
                         ->row();

        // Developer restriction (role_name = developer)
        if ($role && $role->role_name == 'developer') {

            if ($ticket->assigned_engineer_id != $current_user_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You can only move your assigned tickets'
                ]);
                return;
            }
        }

        /*
        =====================================================
        3️⃣ TASK COMPLETION CHECK (DB Driven)
        =====================================================
        */

        $status = $this->db->where('status_id', $to_status)
                   ->get('ticket_statuses')
                   ->row();

        if ($status && $status->require_all_tasks_completed == 1) {

            $total_tasks = $this->db
                ->where('ticket_id', $ticket->ticket_id)
                ->count_all_results('ticket_tasks');

            $completed_tasks = $this->db
                ->where('ticket_id', $ticket->ticket_id)
                ->where('is_completed', 1)
                ->count_all_results('ticket_tasks');

            if ($total_tasks == 0 || $total_tasks != $completed_tasks) {

                echo json_encode([
                    'success' => false,
                    'message' => 'Complete all tasks before moving'
                ]);
                return;
            }
        }

        /*
        =====================================================
        4️⃣ UPDATE TICKET
        =====================================================
        */

        $update_data = [
            'board_position' => $item['board_position'],
            'status_id'      => $to_status,
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        // Auto assign when moving to In Progress
        $in_progress = $this->db
            ->where('status_slug', 'in_progress')
            ->get('ticket_statuses')
            ->row();

        if ($in_progress && $to_status == $in_progress->status_id) {
            $update_data['assigned_engineer_id'] = $current_user_id;
        }

        $this->db->where('ticket_id', $ticket->ticket_id);
        $this->db->update('tickets', $update_data);
    }

    echo json_encode(['success' => true]);
}

public function get_ticket_details()
{
    $ticket_id = (int)$this->input->post('ticket_id');

    if (!$ticket_id) {
        echo json_encode(['error' => true]);
        return;
    }

    $this->load->model('Ticket_model');

    $ticket = $this->Ticket_model->get_ticket_by_id($ticket_id);

    // 🔥 Safety: agar ticket nahi mila to error return karo
    if (!$ticket) {
        echo json_encode(['error' => true]);
        return;
    }

    $tasks = $this->Ticket_model->get_tasks_by_ticket($ticket_id);

    echo json_encode([
        'error'  => false,
        'ticket' => $ticket,
        'tasks'  => $tasks ? $tasks : []
    ]);
}

public function update_task_status()
{
    $task_id = (int)$this->input->post('task_id');
    $is_completed = (int)$this->input->post('is_completed');

    if (!$task_id) {
        echo json_encode(['success' => false]);
        return;
    }

    // ✅ Update task status
    $this->db->where('task_id', $task_id);
    $this->db->update('ticket_tasks', [
        'is_completed' => $is_completed
    ]);

    // ✅ Get ticket id of this task
    $task = $this->db
        ->select('ticket_id')
        ->where('task_id', $task_id)
        ->get('ticket_tasks')
        ->row();

    if (!$task) {
        echo json_encode(['success' => false]);
        return;
    }

    // ✅ Check if any incomplete tasks remain
    $pending = $this->db
        ->where('ticket_id', $task->ticket_id)
        ->where('is_completed', 0)
        ->count_all_results('ticket_tasks');

    echo json_encode([
        'success' => true,
        'ticket_id' => $task->ticket_id,
        'can_resolve' => $pending == 0 ? 1 : 0
    ]);
}

public function add_task()
{
    $ticket_id = $this->input->post('ticket_id');
    $task_title = $this->input->post('task_title');

    $this->db->insert('ticket_tasks', [
        'ticket_id' => $ticket_id,
        'task_title' => $task_title,
        'is_completed' => 0,
        'position' => 0,
        'created_by' => $this->session->userdata('user_id')
    ]);

    $task_id = $this->db->insert_id();

    echo json_encode([
        'task' => [
            'task_id' => $task_id,
            'task_title' => $task_title
        ]
    ]);
}

public function update_task_title()
{
    $task_id = $this->input->post('task_id');
    $task_title = $this->input->post('task_title');

    $this->db->where('task_id', $task_id);
    $this->db->update('ticket_tasks', [
        'task_title' => $task_title
    ]);

    echo json_encode(['success' => true]);
}
public function update_task_position()
{
    $order = $this->input->post('order');

    foreach ($order as $item) {
        $this->db->where('task_id', $item['task_id']);
        $this->db->update('ticket_tasks', [
            'position' => $item['position']
        ]);
    }

    echo json_encode(['success' => true]);
}
public function get_notifications()
{
    $user_id = $this->session->userdata('user_id');

    $this->load->model('TRS_model');
    $notifications = $this->TRS_model->get_unread_notifications($user_id);

    echo json_encode([
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
}
public function mark_notification_read()
{
    $id = $this->input->post('id');

    $this->db->where('id', $id)
             ->update('task_messages', ['is_read' => 1]);
}
public function add_task_comment()
{
    $ticket_id = $this->input->post('ticket_id');
    $task_id   = $this->input->post('task_id');
    $message   = $this->input->post('message');
    $sender_id = $this->session->userdata('user_id');

    // 1️⃣ Insert comment (agar alag table me rakhte ho to)
    $this->db->insert('task_comments', [
        'ticket_id' => $ticket_id,
        'task_id'   => $task_id,
        'user_id'   => $sender_id,
        'comment'   => $message,
        'created_at'=> date('Y-m-d H:i:s')
    ]);

    // 2️⃣ Receiver decide karo
    $ticket = $this->db->where('ticket_id', $ticket_id)
                       ->get('tickets')
                       ->row();

    if($ticket->assigned_engineer_id == $sender_id){
        $receiver_id = $ticket->created_by;   // IT Head
    } else {
        $receiver_id = $ticket->assigned_engineer_id;  // Developer
    }

    // 3️⃣ Insert notification in task_messages
    $this->db->insert('task_messages', [
        'ticket_id' => $ticket_id,
        'task_id'   => $task_id,
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'message'   => $message,
        'is_read'   => 0
    ]);

    echo json_encode(['status'=>true]);
}
public function get_all_task_counts()
{
    $tickets = $this->db->select('ticket_id')
                        ->get('tickets')
                        ->result();

    $data = [];

    foreach($tickets as $t){

        $total = $this->db->where('ticket_id', $t->ticket_id)
                          ->count_all_results('ticket_tasks');

        $completed = $this->db->where('ticket_id', $t->ticket_id)
                              ->where('is_completed', 1)
                              ->count_all_results('ticket_tasks');

        $data[] = [
            'ticket_id' => $t->ticket_id,
            'total' => $total,
            'completed' => $completed
        ];
    }

    echo json_encode($data);
}
}
