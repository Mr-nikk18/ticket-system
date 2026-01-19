<?php 

class User_model extends CI_Model{

   public function get_user_by_username($username)
    {
        return $this->db
                    ->where('user_name', $username)
                    ->get('users')
                    ->row(); // single row
    }

public function set_registration_detail($arr){
return $this->db->insert('users',$arr);
}

public function getdata($mail){
return $this->db->where('email',$mail)->get('users')->row_array();
}

public function save_reset_token($email, $token)
{
    return $this->db->where('email', $email)
                    ->update('users', [
                        'reset_token'  => $token,
                        'token_expiry' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
                    ]);
}

public function setregidata($arr){
      $arr['role_id'] = 1;
   return $this->db->insert('users',$arr);
}


public function get_user_by_token($token)
{
    return $this->db
        ->where('reset_token', $token)
        ->where('token_expiry >=', date('Y-m-d H:i:s'))
        ->get('users')
        ->row_array();
}

public function update_password_by_token($token, $password)
{
    $this->db->where('reset_token', $token);
    $this->db->update('users', [
        'password'     => password_hash($password, PASSWORD_DEFAULT),
        'reset_token'  => NULL,
        'token_expiry' => NULL
    ]);

    return $this->db->affected_rows(); // 1 = success, 0 = fail
}



}

?>