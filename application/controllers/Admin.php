<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ((int) $this->session->userdata('department_id') !== 2 || (int) $this->session->userdata('role_id') !== 2) {
            $this->session->set_flashdata('failed', 'Unauthorized');
            redirect('Dashboard');
        }
    }

    public function bulk_upload()
    {
        redirect('assets/bulk-upload');
    }

    public function upload_csv()
    {
        redirect('assets/bulk-upload');
    }
}
