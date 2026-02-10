<?php 

class Sidebar_model extends CI_Model {

  public function get_sidebar_by_role($role_id)
  {
    return $this->db
      ->select('sm.*')
      ->from('sidebar_menus sm')
      ->join('role_sidebar_menus rsm','rsm.menu_id = sm.id')
      ->where('rsm.role_id',$role_id)
      ->where('sm.status','Active')
      ->order_by('sm.sort_order','ASC')
      ->get()
      ->result_array();
  }

}
