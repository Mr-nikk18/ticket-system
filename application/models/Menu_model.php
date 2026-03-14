<?php
class Menu_model extends CI_Model
{
    public function get_menus_by_role($role_id)
    {
        $department_id = (int) $this->session->userdata('department_id');
        $has_department_column = $this->db->field_exists('department_id', 'role_sidebar_menus');
        $has_rsm_it_only_column = $this->db->field_exists('is_it_only', 'role_sidebar_menus');
        $has_menu_it_only_column = $this->db->field_exists('is_it_only', 'sidebar_menus');

        $this->db
            ->select('sidebar_menus.*')
            ->from('role_sidebar_menus')
            ->join('sidebar_menus', 'sidebar_menus.id = role_sidebar_menus.menu_id')
            ->where('role_sidebar_menus.role_id', (int) $role_id)
            ->where('sidebar_menus.status', 'Active');

        if ($has_department_column) {
            $this->db->where('role_sidebar_menus.department_id', $department_id);
        }

        if ($department_id !== 2) {
            if ($has_rsm_it_only_column) {
                $this->db->where('role_sidebar_menus.is_it_only', 0);
            } elseif ($has_menu_it_only_column) {
                $this->db->where('sidebar_menus.is_it_only', 0);
            }
        }

        return $this->db
            ->distinct()
            ->order_by('sidebar_menus.sort_order', 'ASC')
            ->get()
            ->result_array();
    }
}
