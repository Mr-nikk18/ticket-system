<?php 

class Ticket_model extends CI_Model {
public function TicketHistory($ticket_id = null)
{
    $this->db
        ->select('
            th.history_id,
            th.ticket_id,
            th.action_type,
            th.remarks,
            th.created_at,

            t.title,
            t.status AS recent_status,

            owner.name   AS ticket_owner,
            current.name AS now_handled_by,

            by_user.name AS action_by,
            to_user.name AS assigned_to_name
        ')
        ->from('ticket_assignment_history th')

        // ticket details
        ->join('tickets t', 't.ticket_id = th.ticket_id')

        // ticket owner
        ->join('users owner', 'owner.user_id = t.user_id')

        // current handler (from tickets table, NOT history)
        ->join('users current', 'current.user_id = t.assigned_engineer_id', 'left')

        // who performed action
        ->join('users by_user', 'by_user.user_id = th.assigned_by', 'left')

        // to whom ticket was assigned / reassigned
        ->join('users to_user', 'to_user.user_id = th.assigned_to', 'left');

    // 🔥 optional: single ticket history
    if ($ticket_id) {
        $this->db->where('th.ticket_id', $ticket_id);
    }

    return $this->db
        ->order_by('th.created_at', 'DESC')
        ->get()
        ->result_array();
}


public function Ticket_History_Ajax($ticket_id)
{
    return $this->db
        ->select('
            th.*,
            by_user.name  AS action_by,
            to_user.name  AS assigned_to_name,
            t.title,
            t.status      AS recent_status,
            owner.name    AS ticket_owner,
            current.name  AS now_handled_by
        ')
        ->from('ticket_assignment_history th')
        ->join('users by_user', 'by_user.user_id = th.assigned_by', 'left')
        ->join('users to_user', 'to_user.user_id = th.assigned_to', 'left')
        ->join('tickets t', 't.ticket_id = th.ticket_id')
        ->join('users owner', 'owner.user_id = t.user_id')
        ->join('users current', 'current.user_id = t.assigned_engineer_id', 'left')
        ->where('th.ticket_id', $ticket_id)
        ->order_by('th.created_at', 'DESC')
        ->get()
        ->result_array();
}



}
