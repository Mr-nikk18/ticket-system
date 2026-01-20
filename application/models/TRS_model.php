<?php
class TRS_model extends CI_Model {


public function get_data_by_id($ticket_id)
{
    return $this->db
        ->select('
            tickets.*,
            u.name            AS user_full_name,
            u.department      AS user_department,
            d.name            AS assigned_engineer_name
        ')
        ->from('tickets')
        ->join('users u', 'u.user_id = tickets.user_id', 'left')
        ->join('users d', 'd.user_id = tickets.assigned_engineer_id', 'left')
        ->where('tickets.ticket_id', $ticket_id)
        ->get()
        ->row_array();   // 👈 IMPORTANT (array, not object)
}



    /* -------- INSERT -------- */
    public function insert_ticket($data)
    {
        $this->db->insert('tickets', $data);
    }

    /* -------- GET SINGLE -------- */
    public function get_ticket($ticket_id)
    {
        return $this->db->where('ticket_id',$ticket_id)
                        ->get('tickets')
                        ->row_array();
    }

    /* -------- UPDATE -------- */
    public function update_ticket($ticket_id, $data)
    {
        return $this->db->where('ticket_id',$ticket_id)
                        ->update('tickets',$data);
    }

    /* -------- DELETE -------- */
    public function delete_ticket($ticket_id)
    {
        $this->db->where('ticket_id',$ticket_id)->delete('tickets');
    }

    /* -------- COUNTS -------- */
    public function count_tickets_by_status($status, $user_id = null)
    {
        $this->db->where('status',$status);
        if ($user_id) $this->db->where('user_id',$user_id);
        return $this->db->count_all_results('tickets');
    }

    /* -------- USER TICKETS -------- */
    public function get_user_tickets($user_id, $status = null)
    {
        $this->db->select('tickets.*, d.name AS assigned_engineer_name')
                 ->from('tickets')
                 ->join('users d','d.user_id = tickets.assigned_engineer_id','left')
                 ->where('tickets.user_id',$user_id);

        if ($status) $this->db->where('tickets.status',$status);

        return $this->db->get()->result_array();
    }

    /* -------- ALL TICKETS -------- */
    public function get_all_tickets($status = null)
    {
        $this->db->select('
            tickets.*,
            u.name AS user_full_name,
            u.department AS user_department,
            d.name AS assigned_engineer_name
        ')
        ->from('tickets')
        ->join('users u','u.user_id = tickets.user_id','left')
        ->join('users d','d.user_id = tickets.assigned_engineer_id','left');

        if ($status) $this->db->where('tickets.status',$status);

        return $this->db->get()->result_array();
    }

    /* -------- RECENT -------- */
    public function get_all_recent_tickets($limit)
    {
        return $this->get_all_tickets(null);
    }

    public function get_user_recent_tickets($user_id, $limit)
    {
        return $this->get_user_tickets($user_id, null);
    }

    /* -------- DEV ACCEPTED -------- */
public function get_my_accepted_tickets($developer_id)
{
    return $this->db
        ->select('
            tickets.*,
            u.name AS user_full_name,
            u.department AS user_department,
            d.name AS assigned_engineer_name
        ')
        ->from('tickets')
        ->join('users u','u.user_id = tickets.user_id','left')
        ->join('users d','d.user_id = tickets.assigned_engineer_id','left')
        ->where('tickets.assigned_engineer_id', $developer_id)
        ->where('tickets.status', 'in_progress')
        ->get()
        ->result_array();
}

    public function get_all_developers()
{
    return $this->db
        ->where('role_id', 2)
        ->where('status', 'Active')
        ->get('users')
        ->result_array();
}

}
