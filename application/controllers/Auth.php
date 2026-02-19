<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{

public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // ⚠️ Sirf login page ke liye
        if (
            $this->session->userdata('is_login') === true &&
            $this->router->fetch_method() === 'index'
        ) {
            redirect('dashboard');
        }
    }

   public function index()
    {
        $this->load->view('Same_pages/Login');
    }
    /* =======================
       CHECK USERNAME (AJAX)
       ======================= */
    public function checkUsernameLogin()
    {
        $this->output->set_content_type('application/json');

        $username = $this->input->post('username');
        $this->load->model('User_model');

        $user = $this->User_model->getUserByUsername($username);

        echo json_encode([
            'exists' => $user ? true : false
        ]);
    }

    /* =======================
       CHECK PASSWORD (AJAX)
       ======================= */
    public function checkPassword()
    {
        $this->output->set_content_type('application/json');

        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $this->load->model('User_model');
        $user = $this->User_model->getUserByUsername($username);

        if ($user && password_verify($password, $user->password)) {
            echo json_encode(['valid' => true]);
        } else {
            echo json_encode(['valid' => false]);
        }
    }

    /* =======================
       NORMAL LOGIN (NON-AJAX)
       ======================= */
    public function login_check()
    {
        $username = $this->input->post('user_name');
        $password = $this->input->post('password');

        $this->load->model('User_model');
        $user = $this->User_model->get_user_by_username($username);

       if (!$user || $user->is_registered != 1 || $user->status != 'Active') {
    $this->session->set_flashdata('failed', 'Account not activated');
    redirect('verify');
}


        if (!password_verify($password, $user->password)) {
            $this->session->set_flashdata('failed', 'Invalid password');
            redirect('verify');
        }

        $this->session->set_userdata([
            'is_login'      => true,
            'user_id'       => $user->user_id,
            'username'      => $user->user_name,
            'avatar'      => $user->avatar,
            'role_id'       => $user->role_id,
            'role_name'       => $user->role_name,
            'last_activity' => time()
        ]);

        redirect('dashboard');
    }



    public function registration()
    {
        $this->load->view('Same_pages/Registration');
    }
/*
    public function checkAvailability()
{
    $user_name = $this->input->post('user_name');
    $email     = $this->input->post('email');

    $this->load->model('User_model');

    $response = [
        'user_name' => 'ok',
        'email'     => 'ok'
    ];

    if (!empty($user_name) && $this->User_model->username_exists($user_name)) {
        $response['user_name'] = 'taken';
    }

    if (!empty($email) && $this->User_model->email_exists($email)) {
        $response['email'] = 'taken';
    }

    echo json_encode($response);
}
*/

public function setdataregistration()
{
    $this->load->library('form_validation');
    $this->load->model('User_model');

    $this->form_validation->set_rules('user_name', 'Username', 'required|trim');
    $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
    $this->form_validation->set_rules('password', 'Password', 'required|min_length[3]');
    $this->form_validation->set_rules(
        'confirm_password',
        'Confirm Password',
        'required|matches[password]'
    );

    if ($this->form_validation->run() == FALSE) {
        $this->session->set_flashdata('failed', validation_errors());
        redirect('register');
        return;
    }

    $email = $this->input->post('email', true);
    $username = $this->input->post('user_name', true);

    // 🔥 Check if user exists and not registered
    $user = $this->db->where('email', $email)
                     ->where('is_registered', 0)
                     ->get('users')
                     ->row();

    if (!$user) {
        $this->session->set_flashdata('failed', 'Unauthorized or already registered');
        redirect('register');
        return;
    }

    // 🔥 Activate account
    $updateData = [
        'user_name'     => $username,
        'password'      => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
        'is_registered' => 1,
        'status'        => 'Active'
    ];

    $this->db->where('user_id', $user->user_id);
    $this->db->update('users', $updateData);

    $this->session->set_flashdata('success', 'Account activated successfully');
    redirect('verify');
}






    public function logout()
    {
        $this->session->sess_destroy();
        redirect('verify');

        }

 public function fpass()
    {
        $this->load->view('Same_pages/forget_password');
    }



   public function check_mail()
{
  
    $this->load->library('form_validation');
        $this->load->model('User_model');



    $this->form_validation->set_rules(
        'email',
        'Email',
        'required|valid_email'
    );

        // STEP 1: Validate first
    if ($this->form_validation->run() == FALSE) {
        redirect('verify');
        return;
    }

    $email = $this->input->post('email');
    //check user exists
    $user = $this->User_model->getdata($email);
    
    
   // STEP 2: If user not found (security purpose)
   
    if (!$user) {
        $this->session->set_flashdata('success','Reset link sent successfully');
        redirect('verify');
    
    }
    

               
    //token part
    // generate secure token
    $token = bin2hex(random_bytes(32));

    // save token in DB
    $this->User_model->save_reset_token($email, $token);

    // reset link WITH token
    $reset_link = base_url('reset-password/' . $token);

    // Send Email
    $this->load->library('email');

    $this->email->from('patelniket972@gmail.com', 'TRS');
    $this->email->to($email);
    $this->email->subject('Reset Password');


    $this->email->message(
        "Hello,\n\nClick the link to reset your password:\n".$reset_link."\n\nThis is valid for 15 minutes."
    );

    if ($this->email->send()) {
               $this->session->set_flashdata('success', 'Reset link sent successfully');    

        redirect('verify');
    } else {
        $this->session->set_flashdata('failed', 'Failed to send email');    

       redirect('verify');
    }
}

public function check_email_status_ajax()
{
    $email = $this->input->post('email');
    $this->load->model('User_model');
    $user = $this->User_model->getdata($email);

  if (!$user) {
        echo json_encode(['status' => 'NotFound']);
        return;
    }
    if ($user['status'] == 'Inactive' ) {
        echo json_encode(['status' => 'Inactive']);
        return;
    }

    echo json_encode(['status' => 'Active']);

}


public function Modify_pass()
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $token    = $this->input->post('token');
    $password = $this->input->post('password');
    $confirm_password = $this->input->post('confirm_password');


    // validation
    if (!$token || !$password || !$confirm_password) {
        $this->session->set_flashdata('failed', 'Invalid request');
        redirect('verify');
        exit;
    }

    // password mismatch
    if ($password !== $confirm_password) {
        $this->session->set_flashdata('failed', 'Passwords do not match');
        redirect('verify');
        exit;
    }

    $this->load->model('User_model');

    $updated = $this->User_model->update_password_by_token($token, $password);

    if ($updated) {
        $this->session->set_flashdata('success', 'Password updated successfully');
        redirect('verify');   // login / verify page
        exit;
    } else {
        $this->session->set_flashdata('failed', 'Link expired or invalid');
        redirect('forget_password');
        exit;
    }
}

public function form($token = null)
{
    if (!$token) {
        $this->session->set_flashdata('failed','Invalid/Expire reset Link');
        redirect('verify');
    }

    $this->load->model('User_model');

    // token valid hai ya nahi check
    $user = $this->User_model->get_user_by_token($token);

    if (!$user) {
          $this->session->set_flashdata('failed','Reset link Expired/Invalid');
        redirect('verify');
    }

    // token view ko bhejna
    $data['token'] = $token;
    $this->load->view('Same_pages/forgetpassword_form', $data);
}


}


?>