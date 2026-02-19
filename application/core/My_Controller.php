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
         $this->session->set_flashdata('failed', 'Please login first');
        redirect('verify');
        }

        // ⏳ Session timeout (30 min)
        $timeout = 1800;
        $last = $this->session->userdata('last_activity');

        if ($last && time() - $last > $timeout) {
            $this->session->sess_destroy();
            redirect('verify');
        }

        // update activity
        $this->session->set_userdata('last_activity', time());

        // ✅ LOAD SIDEBAR MENUS FOR ALL PAGES
        $this->load->model('Menu_model');

        $menus = $this->Menu_model
            ->get_menus_by_role(
                $this->session->userdata('role_id')
            );

        // make available in all views
        $this->load->vars(['menus' => $menus]);
    }
}
