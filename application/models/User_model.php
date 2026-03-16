<?php

class User_model extends CI_Model
{
    private function userQuery()
    {
        return $this->db->from('users');
    }

    private function getUserByField($field, $value, $asArray = false)
    {
        $query = $this->userQuery()->where($field, $value)->get();

        return $asArray ? $query->row_array() : $query->row();
    }

    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getUserByUsername($username)
    {
        return $this->get_user_by_username($username);
    }

    public function username_exists($username)
    {
        return (bool) $this->getUserByField('user_name', $username);
    }

    public function email_exists($email)
    {
        return (bool) $this->getUserByField('email', $email);
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

    public function getdata($mail)
    {
        return $this->getUserByField('email', $mail, true);
    }

    public function save_reset_token($email, $token)
    {
        return $this->db->where('email', $email)
            ->update('users', [
                'reset_token'  => $token,
                'token_expiry' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
            ]);
    }

    public function setregidata($arr)
    {
        $arr['role_id'] = 1;
        return $this->db->insert('users', $arr);
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
            'password'     => $this->hashPassword($password),
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
            ->where_in('role_id', [2, 3])
            ->order_by("FIELD(role_id, 3, 2)") // 👈 IT Head first, then Developer
            ->get('users')
            ->result_array();
    }

    public function get_user_staff($user_id)
    {
        return $this->db
            ->where_in('role_id', [2, 3])
            ->where('user_id', $user_id)
            ->get('users')
            ->row_array();
    }

    public function update_user_stuff($user_id, $data)
    {
        return $this->db->where('user_id', $user_id)->update('users', $data);
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

    public function getDepartmentUserIds($department_id, $activeOnly = true)
    {
        $department_id = (int) $department_id;
        if ($department_id <= 0) {
            return [];
        }

        $this->db
            ->select('user_id')
            ->from('users')
            ->where('department_id', $department_id);

        if ($activeOnly) {
            $this->db->where('status', 'Active');
        }

        $rows = $this->db->order_by('name', 'ASC')->get()->result_array();

        return array_values(array_unique(array_map('intval', array_column($rows, 'user_id'))));
    }

    public function getAllActiveUserIds()
    {
        $rows = $this->db
            ->select('user_id')
            ->from('users')
            ->where('status', 'Active')
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        return array_values(array_unique(array_map('intval', array_column($rows, 'user_id'))));
    }

    public function getVisibleUserIdsForScope($currentUserId, $currentDepartmentId, $currentRoleId)
    {
        $currentUserId = (int) $currentUserId;
        $currentDepartmentId = (int) $currentDepartmentId;
        $currentRoleId = (int) $currentRoleId;

        if ($currentRoleId === 2) {
            return $this->getAllActiveUserIds();
        }

        $departmentUserIds = $this->getDepartmentUserIds($currentDepartmentId);
        if ($currentUserId > 0 && !in_array($currentUserId, $departmentUserIds, true)) {
            $departmentUserIds[] = $currentUserId;
        }

        return array_values(array_unique(array_map('intval', $departmentUserIds)));
    }

    public function isDepartmentHead($userId, $departmentId = null)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        $this->db
            ->select('users.user_id')
            ->from('users')
            ->join('departments', 'departments.department_id = users.department_id', 'left')
            ->where('users.user_id', $userId)
            ->group_start()
                ->where('users.role_id', 2)
                ->or_where('departments.department_head_id', $userId)
            ->group_end();

        if ($departmentId !== null) {
            $this->db->where('users.department_id', (int) $departmentId);
        }

        return (bool) $this->db->get()->row_array();
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
        $name = trim((string) $name);
        $email = strtolower(trim((string) $email));
        $department_name = trim((string) $department_name);
        $role_name = strtolower(trim((string) $role_name));

        if ($name === '' || $email === '' || $department_name === '' || $role_name === '') {
            return ['status' => false, 'message' => 'Required columns are missing.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => 'Invalid email format.'];
        }

        $existing = $this->getUserByField('email', $email);
        if ($existing) {
            return ['status' => false, 'message' => 'Email already exists.'];
        }

        $department = $this->db
            ->where('department_name', $department_name)
            ->get('departments')
            ->row();

        if (!$department) {
            return ['status' => false, 'message' => 'Department not found.'];
        }

        $role = $this->db
            ->where('role_name', $role_name)
            ->get('roles')
            ->row();

        if (!$role) {
            return ['status' => false, 'message' => 'Role not found.'];
        }

        $manager = $this->db
            ->where('role_id', 2)
            ->where('department_id', $department->department_id)
            ->where('status', 'Active')
            ->get('users')
            ->row();

        $reports_to = $manager ? $manager->user_id : null;
        $base_username = preg_replace('/[^a-z0-9]+/i', '.', strtolower($name));
        $base_username = trim($base_username, '.');

        if ($base_username === '') {
            $base_username = 'user';
        }

        $username = $base_username;
        $suffix = 1;
        while ($this->username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        $data = [
            'name'          => $name,
            'user_name'     => $username,
            'email'         => $email,
            'role_id'       => $role->role_id,
            'department_id' => $department->department_id,
            'reports_to'    => $reports_to,
            'is_registered' => 0,
            'status'        => 'Inactive',
            'phone'         => '0000000000',
            'company_name'  => 'TRS',
            'created_at'    => date('Y-m-d H:i:s'),
            'created_by'    => $this->session->userdata('user_id')
        ];

        if (!$this->db->insert('users', $data)) {
            return ['status' => false, 'message' => 'Insert failed.'];
        }

        return ['status' => true, 'message' => 'Inserted successfully.'];
    }
}
