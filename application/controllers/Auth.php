<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    private function pullPostLoginRedirect()
    {
        $redirect = trim((string) $this->session->userdata('post_login_redirect'));
        $this->session->unset_userdata('post_login_redirect');

        if ($redirect === '') {
            return '';
        }

        if (preg_match('#^(?:https?:)?//#i', $redirect)) {
            return '';
        }

        return ltrim($redirect, '/');
    }

    private function respondJson(array $payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function loadUserModel()
    {
        $this->load->model('User_model');
    }

    private function sendSystemEmail($to, $subject, $message)
    {
        $this->load->library('email');
        $this->email->clear(true);
        $this->email->from('patelniket972@gmail.com', 'TRS');
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        return (bool) $this->email->send();
    }

    private function redirectWithFlash($key, $message, $route)
    {
        $this->session->set_flashdata($key, $message);
        redirect($route);
    }

    private function setRegistrationOldInput($username, $email)
    {
        $this->session->set_flashdata('old', [
            'user_name' => trim((string) $username),
            'email' => trim((string) $email),
        ]);
    }

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
            $redirect = $this->pullPostLoginRedirect();
            redirect($redirect !== '' ? $redirect : 'dashboard');
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
        $username = $this->input->post('username');
        $this->loadUserModel();

        $user = $this->User_model->getUserByUsername($username);

        $this->respondJson([
            'exists' => $user ? true : false
        ]);
    }

    /* =======================
       CHECK PASSWORD (AJAX)
       ======================= */
    public function checkPassword()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $this->loadUserModel();
        $user = $this->User_model->getUserByUsername($username);

        $this->respondJson(['valid' => (bool) ($user && password_verify($password, $user->password))]);
    }

    /* =======================
       NORMAL LOGIN (NON-AJAX)
       ======================= */
    public function login_check()
    {
        $username = $this->input->post('user_name');
        $password = $this->input->post('password');
        
        $this->loadUserModel();
        $user = $this->User_model->get_user_by_username($username);


        if (!$user) {
            $this->redirectWithFlash('failed', 'Account not Found!!', 'verify');
        }

        if ($user->is_registered != 1 || $user->status != 'Active') {
            $this->redirectWithFlash('failed', 'Account not activated', 'verify');
        }

        if (!password_verify($password, $user->password)) {
            $this->redirectWithFlash('failed', 'Invalid password', 'verify');
        }

        $this->session->set_userdata([
            'is_login'      => true,
            'user_id'       => $user->user_id,
            'username'      => $user->user_name,
            'department_id' => $user->department_id,
            'avatar'      => $user->avatar,
            'role_id'       => $user->role_id,
            'role_name'       => $user->role_name,
            'last_activity' => time()
        ]);

        $redirect = $this->pullPostLoginRedirect();
        redirect($redirect !== '' ? $redirect : 'dashboard');
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
        $this->loadUserModel();

        $this->form_validation->set_rules('user_name', 'Username', 'required|trim');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[3]');
        $this->form_validation->set_rules(
            'confirm_password',
            'Confirm Password',
            'required|matches[password]'
        );

        if ($this->form_validation->run() == FALSE) {
            $this->setRegistrationOldInput(
                $this->input->post('user_name', true),
                $this->input->post('email', true)
            );
            $this->redirectWithFlash('failed', validation_errors(), 'register');
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
            $this->setRegistrationOldInput($username, $email);
            $this->redirectWithFlash('failed', 'Unauthorized or already registered', 'register');
            return;
        }

        $usernameOwner = $this->db
            ->select('user_id')
            ->from('users')
            ->where('user_name', $username)
            ->get()
            ->row();

        if ($usernameOwner && (int) $usernameOwner->user_id !== (int) $user->user_id) {
            $this->setRegistrationOldInput($username, $email);
            $this->redirectWithFlash('failed', 'Username already taken. Please choose another one.', 'register');
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
        if (!$this->db->update('users', $updateData)) {
            $this->setRegistrationOldInput($username, $email);
            $this->redirectWithFlash('failed', 'Unable to activate account right now. Please try again.', 'register');
            return;
        }

        $this->redirectWithFlash('success', 'Account activated successfully', 'verify');
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
        $this->loadUserModel();



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
            $this->session->set_flashdata('success', 'Reset link sent successfully');
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
        $message =
            "Hello,\n\nClick the link to reset your password:\n" . $reset_link . "\n\nThis is valid for 15 minutes."
        ;

        if ($this->sendSystemEmail($email, 'Reset Password', $message)) {
            $this->redirectWithFlash('success', 'Reset link sent successfully', 'verify');
        } else {
            $this->redirectWithFlash('failed', 'Failed to send email', 'verify');
        }
    }

    public function check_email_status_ajax()
    {
        $email = $this->input->post('email');
        $this->loadUserModel();
        $user = $this->User_model->getdata($email);

        if (!$user) {
            $this->respondJson(['status' => 'NotFound']);
            return;
        }
        if ($user['status'] == 'Inactive') {
            $this->respondJson(['status' => 'Inactive']);
            return;
        }

        $this->respondJson(['status' => 'Active']);
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

        $this->loadUserModel();

        $updated = $this->User_model->update_password_by_token($token, $password);

        if ($updated) {
            $this->redirectWithFlash('success', 'Password updated successfully', 'verify');
            exit;
        } else {
            $this->redirectWithFlash('failed', 'Link expired or invalid', 'forget_password');
            exit;
        }
    }

    public function form($token = null)
    {
        if (!$token) {
            $this->session->set_flashdata('failed', 'Invalid/Expire reset Link');
            redirect('verify');
        }

        $this->loadUserModel();

        // token valid hai ya nahi check
        $user = $this->User_model->get_user_by_token($token);

        if (!$user) {
            $this->session->set_flashdata('failed', 'Reset link Expired/Invalid');
            redirect('verify');
        }

        // token view ko bhejna
        $data['token'] = $token;
        $this->load->view('Same_pages/forgetpassword_form', $data);
    }
}
