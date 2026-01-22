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
    // check token validity
    $user = $this->db
        ->where('reset_token', $token)
        ->where('token_expiry >=', date('Y-m-d H:i:s'))
        ->get('users')
        ->row();

    if (!$user) {
        return false; // invalid or expired token
    }

    // update password
    $this->db->where('user_id', $user->user_id);
    $this->db->update('users', [
        'password'     => password_hash($password, PASSWORD_DEFAULT),
        'reset_token'  => NULL,
        'token_expiry' => NULL
    ]);

    return true;
}


public function insert_user($data)
    {
        return $this->db->insert('users', $data);
    }

    public function get_all_staff()
    {
        return $this->db
            ->where_in('role_id', [2,3])
            ->order_by("FIELD(role_id, 3, 2)") // 👈 IT Head first, then Developer
            ->get('users')
            ->result_array();
    }

public function get_user_staff($user_id)
{
    return $this->db
        ->where_in('role_id', [2, 3])   
        ->where('user_id',$user_id)
        ->get('users')
        ->row_array();

       
}

public function update_user_stuff($user_id,$data){
 return $this->db->where('user_id',$user_id)->update('users',$data);
 
}

public function delete_user($user_id)
{
    return $this->db
        ->where('user_id', $user_id)
        ->delete('users');
}


}

?>