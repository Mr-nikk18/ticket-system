<?php
class Menu_model extends CI_Model {

  public function get_menus_by_role($role_id)
{
    return $this->db
        ->select('sidebar_menus.*')
        ->from('role_sidebar_menus')
        ->join('sidebar_menus','sidebar_menus.id = role_sidebar_menus.menu_id')
        ->where('role_sidebar_menus.role_id',$role_id)
        ->where('sidebar_menus.status','Active')
        ->order_by('sidebar_menus.sort_order','ASC')
        ->get()
        ->result_array();
}

}
