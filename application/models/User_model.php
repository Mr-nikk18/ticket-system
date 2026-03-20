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

    public function get_all_roles()
    {
        return $this->db
            ->select('role_id, role_name')
            ->from('roles')
            ->order_by('role_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_all_departments()
    {
        return $this->db
            ->select('department_id, department_name')
            ->from('departments')
            ->order_by('department_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_active_users_for_select()
    {
        return $this->db
            ->select('user_id, name, email, department_id')
            ->from('users')
            ->where('status', 'Active')
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_hierarchy_candidates($excludeUserId = null)
    {
        $this->db
            ->select('
                users.user_id,
                users.name,
                users.email,
                users.department_id,
                users.status,
                roles.role_name,
                departments.department_name
            ')
            ->from('users')
            ->join('roles', 'roles.role_id = users.role_id', 'left')
            ->join('departments', 'departments.department_id = users.department_id', 'left');

        if ($excludeUserId !== null) {
            $this->db->where('users.user_id !=', (int) $excludeUserId);
        }

        return $this->db
            ->order_by('departments.department_name', 'ASC')
            ->order_by('roles.role_name', 'ASC')
            ->order_by('users.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_all_users_with_meta()
    {
        return $this->db
            ->select('
                users.user_id,
                users.user_name,
                users.name,
                users.email,
                users.phone,
                users.company_name,
                users.role_id,
                users.department_id,
                users.reports_to,
                users.is_registered,
                users.status,
                users.created_at,
                roles.role_name,
                departments.department_name,
                manager.name AS reports_to_name,
                manager.email AS reports_to_email
            ')
            ->from('users')
            ->join('roles', 'roles.role_id = users.role_id', 'left')
            ->join('departments', 'departments.department_id = users.department_id', 'left')
            ->join('users manager', 'manager.user_id = users.reports_to', 'left')
            ->order_by('users.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_user_with_meta($user_id)
    {
        return $this->db
            ->select('
                users.*,
                roles.role_name,
                departments.department_name,
                manager.name AS reports_to_name,
                manager.email AS reports_to_email
            ')
            ->from('users')
            ->join('roles', 'roles.role_id = users.role_id', 'left')
            ->join('departments', 'departments.department_id = users.department_id', 'left')
            ->join('users manager', 'manager.user_id = users.reports_to', 'left')
            ->where('users.user_id', (int) $user_id)
            ->get()
            ->row_array();
    }

    public function username_exists_except($username, $user_id = null)
    {
        $username = trim((string) $username);
        if ($username === '') {
            return false;
        }

        $this->db
            ->from('users')
            ->where('user_name', $username);

        if ($user_id !== null) {
            $this->db->where('user_id !=', (int) $user_id);
        }

        return (bool) $this->db->count_all_results();
    }

    public function email_exists_except($email, $user_id = null)
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return false;
        }

        $this->db
            ->from('users')
            ->where('email', $email);

        if ($user_id !== null) {
            $this->db->where('user_id !=', (int) $user_id);
        }

        return (bool) $this->db->count_all_results();
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
