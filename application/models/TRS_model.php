<?php
class TRS_model extends CI_Model
{


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

    public function insert_assignment_history($data)
{
    return $this->db->insert('ticket_assignment_history', $data);
}
public function insert_reassignment_history($data)
{
    return $this->db->insert('ticket_assignment_history', $data);
}


    /* -------- GET SINGLE -------- */
    public function get_ticket($ticket_id)
    {
        return $this->db->where('ticket_id', $ticket_id)->where('deleted_at IS NULL')
            ->get('tickets')
            ->row_array();
    }

    /* -------- UPDATE -------- */
    public function update_ticket($ticket_id, $data)
    {
        return $this->db->where('ticket_id', $ticket_id)
            ->update('tickets', $data);
    }

    /* -------- DELETE -------- */
    public function delete_ticket($ticket_id)
    {
        $this->db->where('ticket_id', $ticket_id) ->update('tickets', [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /* -------- COUNTS -------- */
    public function count_tickets_by_status($status, $user_id = null)
    {
        $this->db->where('status', $status);
        if ($user_id) $this->db->where('user_id', $user_id);
        return $this->db->count_all_results('tickets');
    }

    /* -------- USER TICKETS -------- */
    public function get_user_tickets($user_id, $status = null)
{
    $this->db
        ->select('tickets.*, d.name AS assigned_engineer_name')
        ->from('tickets')
        ->join('users d', 'd.user_id = tickets.assigned_engineer_id', 'left')
        ->where('tickets.user_id', $user_id)
        ->where('tickets.deleted_at IS NULL');

    if ($status) {
        $this->db->where('tickets.status', $status);
    }

    // 🔥 Status priority for USER
    $this->db->order_by("
        CASE
            WHEN tickets.status = 'in_progress' THEN 1
            WHEN tickets.status = 'resolved'    THEN 2
            WHEN tickets.status = 'open'        THEN 3
            WHEN tickets.status = 'closed'      THEN 4
            ELSE 5
        END
    ", '', false);

    $this->db->order_by('tickets.ticket_id', 'DESC');

    return $this->db->get()->result_array();
}


    /* -------- ALL TICKETS -------- */
   public function get_all_tickets($status = null)
{
    $user_id = $this->session->userdata('user_id');

    $this->db
        ->select('
            tickets.*,
            u.name AS user_full_name,
            u.department AS user_department,
            d.name AS assigned_engineer_name
        ')
        ->from('tickets')
        ->join('users u', 'u.user_id = tickets.user_id', 'left')
        ->join('users d', 'd.user_id = tickets.assigned_engineer_id', 'left')
        ->where('tickets.deleted_at IS NULL');

    if ($status) {
        $this->db->where('tickets.status', $status);
    }

    $this->db->order_by("
        CASE
            -- 🔥 1️⃣ Logged-in user ke tickets pehle
            WHEN tickets.assigned_engineer_id = {$user_id} THEN 1
            ELSE 2
        END
    ", '', false);

    $this->db->order_by("
        CASE
            -- 🔥 2️⃣ Status priority (SAME FOR ALL)
            WHEN tickets.status = 'in_progress' THEN 1
            WHEN tickets.status = 'resolved'    THEN 2
            WHEN tickets.status = 'open'        THEN 3
            WHEN tickets.status = 'closed'      THEN 4
            ELSE 5
        END
    ", '', false);

    $this->db->order_by('tickets.ticket_id', 'DESC');

    return $this->db->get()->result_array();
}


    /* -------- RECENT -------- */
public function get_all_recent_tickets($limit)
{
    $developer_id = $this->session->userdata('user_id');

    return $this->db
        ->select('t.*, u.name AS assigned_engineer_name')
        ->from('tickets t')
        ->join('users u', 'u.user_id = t.assigned_engineer_id', 'left')
        ->where('t.deleted_at IS NULL')
        ->order_by("
            CASE 
                WHEN t.assigned_engineer_id = {$developer_id} 
                     AND t.status IN ('in_process', 'closed') THEN 1
                ELSE 2
            END
        ", '', false)
        ->order_by('t.ticket_id', 'DESC')
        ->limit($limit)
        ->get()
        ->result_array();
}



// MODEL (TRS_model.php)
public function get_user_recent_tickets($user_id, $limit = 5)
{
    return $this->db
        ->select('t.*, u.name AS assigned_engineer_name')
        ->from('tickets t')
        ->join('users u', 'u.user_id = t.assigned_engineer_id', 'left')
        ->where('t.user_id', $user_id)              // ticket owner
        ->where('t.deleted_at IS NULL')
        ->order_by('t.ticket_id', 'DESC')           // latest first
        ->limit($limit)
        ->get()
        ->result_array();
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
        ->join('users u', 'u.user_id = tickets.user_id', 'left')
        ->join('users d', 'd.user_id = tickets.assigned_engineer_id', 'left')
        ->where('tickets.assigned_engineer_id', $developer_id)
        ->where_in('tickets.status', ['resolved', 'in_progress', 'closed'])
        ->order_by("
            CASE
            WHEN tickets.status = 'in_progress' THEN 1
                WHEN tickets.status = 'resolved' THEN 2
                WHEN tickets.status = 'closed' THEN 3
                ELSE 4
            END
        ", '', false)
        ->order_by('tickets.ticket_id', 'DESC')
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

    /* -------- USER LIST -------- */
public function get_status_count($role_id,$user_id,$status)
{
    $sql = "
      SELECT COUNT(*) AS total
      FROM tickets t
      JOIN role_ticket_rules r
        ON r.role_id = ?
       AND r.status = ?
      WHERE t.deleted_at IS NULL
        AND t.status = ?
        AND (
            r.view_type = 'ALL'
            OR (r.view_type = 'ASSIGNED' AND t.assigned_engineer_id = ?)
            OR (r.view_type = 'OWN' AND t.user_id = ?)
        )
    ";

    return $this->db->query($sql,[
        $role_id,
        $status,
        $status,
        $user_id,
        $user_id
    ])->row()->total;
}



public function get_role_view_type($role_id)
{
    return $this->db
        ->select('view_type')
        ->from('role_ticket_rules')
        ->where('role_id',$role_id)
        ->get()
        ->row()
        ->view_type;
}





public function get_recent_tickets($role_id,$user_id)
{
    return $this->db->query("
        SELECT DISTINCT 
            t.*,
            u.user_name AS assigned_engineer_name,

            /* Decide from DB if user can accept */
            CASE
                WHEN r.view_type IN ('ASSIGNED','ALL')
                     AND t.assigned_engineer_id IS NULL
                     AND t.status = 'open'
                     
                THEN 1
                ELSE 0
            END AS can_accept

        FROM tickets t
        LEFT JOIN users u 
            ON u.user_id = t.assigned_engineer_id
        JOIN role_ticket_rules r
            ON r.role_id = ?

        WHERE t.deleted_at IS NULL
          AND (
                r.view_type = 'ALL'
                OR (r.view_type = 'OWN' AND t.user_id = ?)
                OR (r.view_type = 'ASSIGNED' AND t.assigned_engineer_id = ?)
          )

        ORDER BY t.ticket_id DESC
        LIMIT 5
    ", [$role_id,$user_id,$user_id])->result_array();
}



}
