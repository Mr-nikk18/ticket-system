<?php

class UserController extends MY_Controller
{
    public function create_user()
    {
        $currentUserId = (int) $this->session->userdata('user_id');
        $currentRole = (int) $this->session->userdata('role_id');

        $this->load->library('form_validation');
        $this->load->model('User_model');

        $this->form_validation->set_rules('name', 'Name', 'required|trim|min_length[2]');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        $this->form_validation->set_rules('role_id', 'Role', 'required|integer');
        $this->form_validation->set_rules('department', 'Department', 'required|integer');

        if ($this->form_validation->run() === false) {
            show_error(strip_tags(validation_errors()), 400);
        }

        $name = trim((string) $this->input->post('name', true));
        $email = strtolower(trim((string) $this->input->post('email', true)));

        if ($this->User_model->email_exists($email)) {
            show_error('Email already exists', 400);
        }

        $base_username = preg_replace('/[^a-z0-9]+/i', '.', strtolower($name));
        $base_username = trim($base_username, '.');
        if ($base_username === '') {
            $base_username = 'user';
        }

        $username = $base_username;
        $suffix = 1;
        while ($this->User_model->username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        $data = [
            'user_name' => $username,
            'name' => $name,
            'email' => $email,
            'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
            'role_id' => (int) $this->input->post('role_id'),
            'department_id' => (int) $this->input->post('department'),
            'phone' => '0000000000',
            'company_name' => 'TRS',
            'is_registered' => 1,
            'status' => 'Active',
            'reports_to' => $currentRole === 2 ? null : $currentUserId,
        ];

        $this->db->insert('users', $data);
    }

    public function upload_avatar()
    {
        $user_id = $this->session->userdata('user_id');
        $selected_avatar = $this->input->post('selected_avatar');

        if (!empty($selected_avatar)) {
            $this->db->where('user_id', $user_id);
            $this->db->update('users', ['avatar' => $selected_avatar]);

            $this->session->set_userdata('avatar', $selected_avatar);

            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        if (!empty($_FILES['avatar_file']['name'])) {
            $config['upload_path'] = './assets/dist/img/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['max_size'] = 2048;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('avatar_file')) {
                $data = $this->upload->data();
                $filename = $data['file_name'];

                $this->db->where('user_id', $user_id);
                $this->db->update('users', ['avatar' => $filename]);

                $this->session->set_userdata('avatar', $filename);
            }
        }

        redirect($_SERVER['HTTP_REFERER']);
    }

    public function update_avatar()
    {
        $avatar = $this->input->post('avatar');
        $user_id = $this->session->userdata('user_id');

        $this->db->where('user_id', $user_id);
        $this->db->update('users', ['avatar' => $avatar]);
        $this->session->set_userdata('avatar', $avatar);

        echo json_encode(['status' => 'success']);
    }
}
