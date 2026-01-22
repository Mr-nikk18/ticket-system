<?php 

class Ticket_model extends CI_Model {

public function TicketHistory()
{
    $this->db->select('
        th.ticket_id,
        t.title,
        u.name        AS ticket_owner,
        to_user.user_name AS assigned_to_name,
        by_user.user_name AS assigned_by_name,
        th.remarks,
        th.created_at
    ');

    $this->db->from('ticket_assignment_history th');

    // join tickets table
    $this->db->join('tickets t', 't.ticket_id = th.ticket_id');

    // join users table for ticket owner (who raised ticket)
    $this->db->join('users u', 'u.user_id = t.user_id');

    // join users table for assigned_to (current / new developer)
    $this->db->join('users to_user', 'to_user.user_id = th.assigned_to');

    // join users table for assigned_by (admin who changed)
    $this->db->join('users by_user', 'by_user.user_id = th.assigned_by');

    // latest first
    $this->db->order_by('th.created_at', 'DESC');

    $query = $this->db->get();
    return $query->result_array();
}

}
