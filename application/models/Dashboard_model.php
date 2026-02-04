<?php
class Dashboard_model extends CI_Model {

    public function get_modules_by_role($role_id)
    {
        return $this->db
            ->select('dashboard_modules.view_file')
            ->from('role_modules')
            ->join('dashboard_modules','dashboard_modules.id = role_modules.module_id')
            ->where('role_modules.role_id',$role_id)
            ->where('dashboard_modules.status','Active')
            ->get()
            ->result_array();
    }
}
