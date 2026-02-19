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
            ts.status_slug AS recent_status,

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
        ->join('users to_user', 'to_user.user_id = th.assigned_to', 'left')

        ->join('ticket_statuses ts', 'ts.status_id = t.status_id');


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
            t.status_id      AS recent_status,
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

 public function getVisibleTickets($user_id, $visible_user_ids)
    {
        return $this->db
            ->group_start()
                ->where_in('created_by', $visible_user_ids)
                ->or_where('assigned_to', $user_id)
            ->group_end()
            ->get('tickets')
            ->result();
    }

    public function getDashboardCounts($visible_user_ids)
    {
        $this->db->where_in('created_by', $visible_user_ids);
        $total = $this->db->count_all_results('tickets', FALSE);

        $this->db->where('status_id', 1);
        $open = $this->db->count_all_results('', FALSE);

        $this->db->where('status_id', 4);
        $closed = $this->db->count_all_results();

        return [
            'total'  => $total,
            'open'   => $open,
            'closed' => $closed
        ];
    }

public function get_board_tickets()
{
    $user_id = $this->session->userdata('user_id');

    $this->db->select('t.*, ts.status_name, ts.status_slug, tp.priority_name');
    $this->db->from('tickets t');
    $this->db->join('ticket_statuses ts', 'ts.status_id = t.status_id');
    $this->db->join('ticket_priorities tp', 'tp.priority_id = t.priority_id', 'left');
    $this->db->where('t.deleted_at IS NULL');

    // 🔥 MAIN FILTER LOGIC
    $this->db->where("
        t.status_id = 1
        OR
        (
            t.status_id IN (2,3,4)
            AND t.assigned_engineer_id = $user_id
        )
    ", NULL, FALSE);

    $this->db->order_by('ts.display_order', 'ASC');
    $this->db->order_by('t.board_position', 'ASC');

    return $this->db->get()->result();
}

public function update_position($ticket_id, $status_id, $position)
{

    // Update moved ticket
    $this->db->where('ticket_id', $ticket_id)
             ->update('tickets', [
                 'status_id' => $status_id,
                 'board_position' => $position
             ]);

    // Reorder all tickets in that column
    $tickets = $this->db->where('status_id', $status_id)
                        ->order_by('board_position','ASC')
                        ->get('tickets')
                        ->result();

    $i = 0;
    foreach($tickets as $t){
        $this->db->where('ticket_id', $t->ticket_id)
                 ->update('tickets', ['board_position' => $i]);
        $i++;
    }
}

}
