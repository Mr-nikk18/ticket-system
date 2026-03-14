<?php

class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ((int) $this->session->userdata('department_id') !== 2 || (int) $this->session->userdata('role_id') !== 2) {
            $this->session->set_flashdata('failed', 'Unauthorized');
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
        if (empty($_FILES['csv_file']['name'])) {
            $this->session->set_flashdata('error', 'No file selected');
            redirect('Admin/bulk_upload');
        }

        $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $this->session->set_flashdata('error', 'Only CSV files are allowed');
            redirect('Admin/bulk_upload');
        }

        if (!is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $this->session->set_flashdata('error', 'Invalid upload request');
            redirect('Admin/bulk_upload');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle === false) {
            $this->session->set_flashdata('error', 'Unable to open uploaded CSV');
            redirect('Admin/bulk_upload');
        }

        $header = fgetcsv($handle);
        if (!$header || count($header) < 4) {
            fclose($handle);
            $this->session->set_flashdata('error', 'CSV header must contain: name,email,department,role');
            redirect('Admin/bulk_upload');
        }

        $success_count = 0;
        $error_rows = [];
        $row_number = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $row_number++;

            if (count(array_filter($row, function ($value) {
                return trim((string) $value) !== '';
            })) === 0) {
                continue;
            }

            if (count($row) < 4) {
                $error_rows[] = 'Row ' . $row_number . ': expected 4 columns.';
                continue;
            }

            $result = $this->User_model->bulk_insert_user(
                $row[0],
                $row[1],
                $row[2],
                $row[3]
            );

            if (!empty($result['status'])) {
                $success_count++;
            } else {
                $error_rows[] = 'Row ' . $row_number . ': ' . $result['message'];
            }
        }

        fclose($handle);

        if ($success_count > 0) {
            $this->session->set_flashdata('success', $success_count . ' user(s) uploaded successfully');
        }

        if (!empty($error_rows)) {
            $this->session->set_flashdata('error', implode('<br>', array_slice($error_rows, 0, 10)));
        }

        redirect('Admin/bulk_upload');
    }
}
