<?php 

class Dashboard_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // ❌ login nahi → login page
        if ($this->session->userdata('is_login') !== true) {
            redirect('verify');
        }

        // ⏳ Timeout
        $timeout = 3600;
        $last_activity = $this->session->userdata('last_activity');

        if ($last_activity && (time() - $last_activity > $timeout)) {
            $this->session->sess_destroy();
            redirect('verify');
        }

        // ✅ update activity
        $this->session->set_userdata('last_activity', time());
    }

    public function index()
    {
        $this->load->view('Same_pages/Dashboard');
    }
   

}
















?>