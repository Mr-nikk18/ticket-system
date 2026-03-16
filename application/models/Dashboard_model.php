<?php
class Dashboard_model extends CI_Model {

    public function get_modules_by_role($role_id)
    {
        return $this->db
            ->select('dashboard_modules.view_file')
            ->from('role_modules')
            ->join('dashboard_modules', 'dashboard_modules.id = role_modules.module_id')
            ->where('role_modules.role_id', (int) $role_id)
            ->where('dashboard_modules.status', 'Active')
            ->order_by("CASE WHEN dashboard_modules.view_file = 'modules/total_ticket_box' THEN 0 ELSE dashboard_modules.id END", 'ASC', false)
            ->get()
            ->result_array();
    }
}
