<?php

class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Only IT Head allowed
        if ($this->session->userdata('role_id') != 3) {
              $this->session->set_flashdata('failed','🚫Unauthorized🚫');
              redirect('Dashboard');
        }

        $this->load->model('User_model');
    }

    public function bulk_upload()
    {
        $this->load->view('IT_head/bulk_upload');
    }

    public function upload_csv()
    {
        if ($_FILES['csv_file']['name'] == '') {
            $this->session->set_flashdata('error', 'No file selected');
            redirect('Admin/bulk_upload');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        fgetcsv($handle); // Skip header row

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $name       = trim($row[0]);
            $email      = trim($row[1]);
            $department = trim($row[2]);
            $role       = trim($row[3]);

            $this->User_model->bulk_insert_user($name, $email, $department, $role);
        }

        fclose($handle);

        $this->session->set_flashdata('success', 'Users uploaded successfully');
        redirect('Admin/bulk_upload');
    }
}
