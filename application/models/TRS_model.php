<?php 

class TRS_model extends CI_Model{
   
public function show($user_id){
return $this->db->where('user_id',$user_id)->get('tickets')->result_array();
}

public function insertdata($arr){
$this->db->insert('tickets',$arr);
}

public function get_data_by_id($ticket_id){
return  $this->db->where('ticket_id',$ticket_id)->get('tickets')->row_array();
}
public function update_data($ticket_id,$arr){
 return $this->db->where('ticket_id',$ticket_id)->update('tickets',$arr);
}
public function insert_ticket_form_data($arr){
    $this->db->insert('tickets',$arr);
}
}

?>