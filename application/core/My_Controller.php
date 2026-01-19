<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper('url');

        // 🔐 Login required
        if ($this->session->userdata('is_login') !== true) {
            redirect('verify');
        }

        // ⏳ Session timeout (10 min)
        $timeout = 3600;
        $last = $this->session->userdata('last_activity');

        
        if ($last && time() - $last > $timeout) {
            $this->session->sess_destroy();
            redirect('verify');
        }

        // update activity
        $this->session->set_userdata('last_activity', time());
    }
}
