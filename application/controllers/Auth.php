<?php

class Auth extends CI_Controller {

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

    public function login_check()
    {
        $username = $this->input->post('user_name');
        $password = $this->input->post('password');
    
        $this->load->model('User_model');
        $user = $this->User_model->get_user_by_username($username);

        if (!$user) {
            $this->session->set_flashdata("failed","Username not found");
            redirect('verify');
        }

        // ✅ Password check only once
    if (!password_verify($password, $user->password)) {
        $this->session->set_flashdata("failed","Invalid password");
        redirect('verify');
    }

    // ✅ Session set properly
    $this->session->set_userdata([
        'is_login'      => true,
        'user_id'       => $user->user_id,
        'username'      => $user->user_name,
        'role_id'       => $user->role_id,   // 🔥 IMPORTANT
        'last_activity' => time()
    ]);

    // ✅ Role-based redirect
    redirect('dashboard');
}

    public function registration()
    {
        $this->load->view('Same_pages/Registration');
    }
public function setdataregistration()
{
    $password = $this->input->post('password');
    $confirm_password = $this->input->post('confirm_password');

    // ✅ store old form data EXCEPT password
    $old_data = [
        'name'      => $this->input->post('name'),
        'user_name' => $this->input->post('user_name'),
        'email'     => $this->input->post('email'),
        'company_name'=>$this->input->post('company_name'),
        'department'=> $this->input->post('department'),
    ];



    // ❌ password mismatch
    if (empty($password) || empty($confirm_password) || $password !== $confirm_password){

        $this->session->set_flashdata('failed', 'Password and Confirm Password do not match');

        // ⭐ STORE OLD INPUT
        $this->session->set_flashdata('old_input', $old_data);

        redirect('register');
        return;
    }

    $arr = [
        'name'      => $this->input->post('name', true),
        'user_name' => $this->input->post('user_name', true),
        'email'     => $this->input->post('email', true),
        'company_name'=>$this->input->post('company_name',true),
        'department'=> $this->input->post('department', true),
        'password'  => password_hash($password, PASSWORD_DEFAULT),
        'created_at'=> date('Y-m-d H:i:s')
    ];

    $this->load->model('User_model');

    if ($this->User_model->setregidata($arr)) {
        $this->session->set_flashdata('success', 'Registration successful');
        redirect('verify');
    } else {
        $this->session->set_flashdata('failed', 'Registration failed');
        $this->session->set_flashdata('old_input', $old_data);
        redirect('register');
    }
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

    if ($this->form_validation->run() == FALSE) {
       // $this->load->view('Same_pages/forget_password');
       $this->session->set_flashdata('failed', 'Link is Send to Your mail id');    
       redirect('verify');
        
        
       return;
    }
      $email = $this->input->post('email');
      //checck user exists
    $user = $this->User_model->getdata($email);


    if (!$user) {
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
        show_error('Invalid or expired reset link');
        return;
    }

    $this->load->model('User_model');

    // token valid hai ya nahi check
    $user = $this->User_model->get_user_by_token($token);

    if (!$user) {
        show_error('Reset link expired or invalid');
        return;
    }

    // token view ko bhejna
    $data['token'] = $token;
    $this->load->view('Same_pages/forgetpassword_form', $data);
}


}


?>