<?php 

class User_model extends CI_Model{

public function username_exists($username)
{
    return $this->db->where('user_name', $username)
                    ->get('users')
                    ->num_rows() > 0;
}

public function email_exists($email)
{
    return $this->db->where('email', $email)
                    ->get('users')
                    ->num_rows() > 0;
}


   public function get_user_by_username($username)
{
    return $this->db
                ->select('users.*, roles.role_name')
                ->from('users')
                ->join('roles', 'roles.role_id = users.role_id', 'left')
                ->where('users.user_name', $username)
                ->get()
                ->row();
}

/*
public function set_registration_detail($arr){
 $this->db->insert('users',$arr);
}
 */

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

// Get direct subordinates
    public function getDirectSubordinates($user_id)
    {
        return $this->db
            ->select('user_id')
            ->where('reports_to', $user_id)
            ->get('users')
            ->result_array();
    }

    // Recursive function to get all subordinates (infinite levels)
    public function getAllSubordinates($user_id, &$subordinates = [])
    {
        $children = $this->getDirectSubordinates($user_id);

        foreach ($children as $child) {
            $subordinates[] = $child['user_id'];
            $this->getAllSubordinates($child['user_id'], $subordinates);
        }

        return $subordinates;
    }
    public function bulk_insert_user($name, $email, $department_name, $role_name)
{
    // Skip if email already exists
    $existing = $this->db->where('email', $email)->get('users')->row();
    if ($existing) return;

    // Get department_id
    $department = $this->db
        ->where('department_name', $department_name)
        ->get('departments')
        ->row();

    if (!$department) return;

    // Get role_id
    $role = $this->db
        ->where('role_name', $role_name)
        ->get('roles')
        ->row();

    if (!$role) return;

    // Find department manager
    $manager = $this->db
        ->where('role_id', $role->role_id == 1 ? 2 : 3) // Example logic
        ->where('department_id', $department->department_id)
        ->get('users')
        ->row();

    $reports_to = $manager ? $manager->user_id : NULL;

    $data = [
        'name'          => $name,
        'email'         => $email,
        'role_id'       => $role->role_id,
        'department_id' => $department->department_id,
        'reports_to'    => $reports_to,
        'is_registered' => 0,
        'status'        => 'Inactive',
        'created_at'    => date('Y-m-d H:i:s'),
        'created_by'    => $this->session->userdata('user_id')
    ];

    $this->db->insert('users', $data);
}

}

?>