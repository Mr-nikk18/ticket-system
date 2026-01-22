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

        $data['open_count']       = $this->TRS_model->count_tickets_by_status('open', $uid);
        $data['in_process_count'] = $this->TRS_model->count_tickets_by_status('in_progress', $uid);
        $data['resolved_count']   = $this->TRS_model->count_tickets_by_status('resolved', $uid);
        $data['closed_count']     = $this->TRS_model->count_tickets_by_status('closed', $uid);

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

            // ✅ Solved → Closed
            $this->TRS_model->update_ticket($ticket_id, [
                'status' => 'closed'
            ]);

            $this->session->set_flashdata('success', 'Ticket closed successfully');
        } else {

            // ❌ Not solved → Reopen ticket
            $this->TRS_model->update_ticket($ticket_id, [
                'status' => 'in_progress'
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

        $allowed_status = ['open', 'in_progress', 'resolved', 'closed'];
        if ($status && !in_array($status, $allowed_status)) {
            show_error('Invalid status');
        }

        if ($role_id == 1) {
            $data['val'] = $this->TRS_model->get_user_tickets($user_id, $status);
        } else {
            $data['val'] = $this->TRS_model->get_all_tickets($status);
        }

        $data['current_status'] = $status;
        $this->load->view('Users/List', $data);
    }

    /* ================= ADD ================= */

    public function see()
    {
        $this->load->view('Users/Add_ticket');
    }

    public function add()
    {
        $this->load->model('TRS_model');

        $this->TRS_model->insert_ticket([
            'user_id' => $this->session->userdata('user_id'),
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'status' => 'open'
        ]);

        redirect('TRS/list');
    }

    /* ================= ACCEPT / LEAVE ================= */

    public function accept_ticket($ticket_id)
    {
        if (!in_array($this->session->userdata('role_id'), [2, 3])) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => $this->session->userdata('user_id'),
            'status' => 'in_progress'
        ]);

        redirect('TRS/my_tickets');
    }

    public function leave_ticket($ticket_id)
    {
        if ($this->session->userdata('role_id') != 2) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $dev_id = $this->session->userdata('user_id');  // 🔥 MISSING LINE

        // 1️⃣ Update main ticket
        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => null,
            'status' => 'open'
        ]);

        // 🔥 INSERT HISTORY (ONLY ONCE)
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'    => $ticket_id,
            'assigned_to' => null,          // ab kisi ke paas nahi
            'assigned_by' => $dev_id,       // dev ne chhoda
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
            if (in_array($ticket['status'], ['closed', 'resolved'])) {
                show_error('Unauthorized');
            }

            // 🚫 Open but already assigned (normal flow)
            if ($ticket['status'] == 'open' && $ticket['assigned_engineer_id'] != null) {
                show_error('Unauthorized');
            }

            // 🔥 IN_PROGRESS case (this is the important part)
            if ($ticket['status'] == 'in_progress') {

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
            if (in_array($ticket['status'], ['closed', 'resolved'])) {
                show_error('Unauthorized');
            }

            // 🚫 If OPEN but already assigned → block
            if ($ticket['status'] == 'open' && $ticket['assigned_engineer_id'] != null) {
                show_error('Unauthorized');
            }

            // 🔥 If IN_PROGRESS (reopened case) → allow ONLY ONCE using session flag
            if ($ticket['status'] == 'in_progress') {
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
                'status' => $this->input->post('status')
            ];
        }

        /* ================= IT HEAD ================= */ elseif ($role_id == 3) {   // IT Head / Admin

            $posted_status = $this->input->post('status');
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
                if (!in_array($posted_status, ['resolved', 'closed'])) {
                    $data['status'] = 'in_progress';
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
        if ($role_id == 1 && $ticket['status'] == 'in_progress') {
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
        if ($this->session->userdata('role_id') != 2) {
            show_error('Unauthorized');
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
            show_error('Unauthorized access');
        }

        $this->load->view('Users/Add_user');
    }

    /* ================= SAVE USER ================= */
    public function save_user()
    {
        if ($this->session->userdata('role_id') != 3) {
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

    /* ================= USER LIST ================= */
    public function user_list()
    {
        if ($this->session->userdata('role_id') != 3) {
            show_error('Unauthorized access');
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

    public function ticket()
    {
        $this->load->model('Ticket_model');
        $data['history'] = $this->Ticket_model->TicketHistory();
        $this->load->view('Same_pages/Ticket_history', $data);
    }
    public function reassign_form($ticket_id)
    {
        // only admin
        if ($this->session->userdata('role_id') != 3) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $data['ticket_id'] = $ticket_id;
        $data['developers'] = $this->TRS_model->get_all_developers();

        $this->load->view('Users/reassign_form', $data);
    }
    public function do_reassign()
    {
        if ($this->session->userdata('role_id') != 3) {
            show_error('Unauthorized');
        }

        $ticket_id   = $this->input->post('ticket_id');
        $new_dev_id  = $this->input->post('developer_id');
        $admin_id    = $this->session->userdata('user_id');

        // 1️⃣ Update main ticket
        $this->db->where('ticket_id', $ticket_id)
            ->update('tickets', [
                'assigned_engineer_id' => $new_dev_id,
                'status' => 'in_progress'
            ]);

        // 🔥 SIMPLE HISTORY INSERT
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'    => $ticket_id,
            'assigned_to' => $new_dev_id,   // kisko diya
            'assigned_by' => $admin_id,     // kisne diya
            'remarks'     => 'Ticket assigned by admin',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $this->session->set_flashdata('success', 'Ticket reassigned successfully');
        redirect('TRS/ticket');
    }
}
