<?php  

class TRS extends My_Controller {

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
        // ❌ Not solved → back to developer
        $this->TRS_model->update_ticket($ticket_id, [
            'status' => 'in_progress'
        ]);

        $this->session->set_flashdata('error', 'Ticket reopened');
    }

    redirect('TRS/list');
}


    /* ================= LIST ================= */

    public function list($status = null)
    {
        $this->load->model('TRS_model');

        $role_id = $this->session->userdata('role_id');
        $user_id = $this->session->userdata('user_id');

        $allowed_status = ['open','in_progress','resolved','closed'];
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
        if (!in_array($this->session->userdata('role_id'), [2,3])) {
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

        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => null,
            'status' => 'open'
        ]);

        redirect('TRS/list/open');
    }

    /* ================= EDIT ================= */

    public function edit($ticket_id)
    {
        $this->load->model('TRS_model');

        $role_id = $this->session->userdata('role_id');
        $ticket  = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket) show_error('Ticket not found');

        // User cannot edit after assignment
        // User cannot edit after assignment EXCEPT when reopened (in_progress)
if (
    $role_id == 1 &&
    $ticket['assigned_engineer_id'] != null &&
    !in_array($ticket['status'], ['open', 'in_progress'])
) {
    show_error('Unauthorized');
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

    $data = []; // 🔥 start EMPTY

    /* ================= USER ================= */
    if ($role_id == 1) {
        $data = [
            'title'       => $this->input->post('title'),
            'description' => $this->input->post('description')
        ];
    }

    /* ================= DEVELOPER ================= */
    if ($role_id == 2) {
        $data = [
            'status' => $this->input->post('status')
        ];
    }

    /* ================= IT HEAD ================= */
    if ($role_id == 3) {
        $data = [
            'status' => $this->input->post('status')
        ];

        // Assign developer (optional)
        if ($this->input->post('assigned_engineer_id')) {
            $data['assigned_engineer_id'] = $this->input->post('assigned_engineer_id');
            $data['status'] = 'in_progress';
        }
    }

    // ✅ SAFETY: nothing empty should update
    if (!empty($data)) {
        $this->TRS_model->update_ticket($ticket_id, $data);
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
        'user_name'  => $this->input->post('user_name'),
        'name'       => $this->input->post('name'),
        'email'      => $this->input->post('email'),
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



}
