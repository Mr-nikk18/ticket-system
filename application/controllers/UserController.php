<?php

class UserController extends MY_Controller{ 

public function create_user()
{
    $currentUserId = $this->session->userdata('user_id');
    $currentRole   = $this->session->userdata('role_id');

    $data = [
        'name'       => $this->input->post('name'),
        'email'      => $this->input->post('email'),
        'password'   => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
        'role_id'    => $this->input->post('role_id'),
        'status'     => 1
    ];

    // If IT Head → top level
    if ($currentRole == 3) { // assuming 3 = it_head
        $data['reports_to'] = NULL;
    } else {
        $data['reports_to'] = $currentUserId;
    }

    $this->db->insert('users', $data);
}

public function upload_avatar()
{
    $user_id = $this->session->userdata('user_id');

    // If predefined selected
    $selected_avatar = $this->input->post('selected_avatar');

    if(!empty($selected_avatar)){

        $this->db->where('user_id', $user_id);
        $this->db->update('users', ['avatar' => $selected_avatar]);

        $this->session->set_userdata('avatar', $selected_avatar);

        redirect($_SERVER['HTTP_REFERER']);
        return;
    }

    // If file uploaded
    if(!empty($_FILES['avatar_file']['name'])){

        $config['upload_path'] = './assets/dist/img/';
        $config['allowed_types'] = 'jpg|jpeg|png';
        $config['max_size'] = 2048;

        $this->load->library('upload', $config);

        if($this->upload->do_upload('avatar_file')){

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

    // session update bhi kar do
    $this->session->set_userdata('avatar', $avatar);

    echo json_encode(['status' => 'success']);
}


}
