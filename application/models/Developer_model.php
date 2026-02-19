<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Developer_model extends CI_Model {

    public function getDeveloperPerformance()
    {
        return $this->db
            ->select('
                u.user_id,
                u.name,
                u.company_name,
                COUNT(t.ticket_id) AS total_tickets,
               SUM(t.status_id = 4) AS resolved_tickets,
                SUM(t.status_id != 4) AS pending_tickets

            ')
            ->from('users u')
            ->join('tickets t', 't.assigned_engineer_id = u.user_id', 'left')
            ->where('u.role_id', 2 )   // ✅ FIXED
            ->group_by('u.user_id')
            ->get()
            ->result_array();
    }

public function getDeveloperWiseStatus()
{
    return $this->db
        ->select("
            u.user_id,
            u.name,

            /* 🔥 GLOBAL OPEN COUNT */
            (
                SELECT COUNT(*)
                FROM tickets
                WHERE status_id = 1
                  AND deleted_at IS NULL
            ) AS open_cnt,

            /* ✅ DEVELOPER WISE COUNTS */
            SUM(CASE WHEN t.status_id = 2 THEN 1 ELSE 0 END) AS process_cnt,
            SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) AS resolved_cnt,
            SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS closed_cnt
        ")
        ->from('users u')
        ->join('tickets t', 't.assigned_engineer_id = u.user_id', 'left')
        ->where('u.role_id', 2)
        ->group_by('u.user_id')
        ->get()
        ->result_array();
}
public function getTicketsByDeveloper($dev_id)
{
    return $this->db
        ->where('assigned_engineer_id', $dev_id)
        ->where('deleted_at IS NULL', null, false)
        ->get('tickets')
        ->result_array();
}
public function getClosedResolvedProcessTickets($dev_id)
{
  return $this->db
    ->where('assigned_engineer_id',$dev_id)
    ->where_in('status_id',[1,4,3,2])
    ->get('tickets')
    ->result_array();
}

public function getStatusCountsForDeveloper($dev_id)
{
  return $this->db
    ->select("
      (SELECT COUNT(*) FROM tickets 
       WHERE status_id=1 AND deleted_at IS NULL) as open,
       SUM(status_id=2) as process,
       SUM(status_id=3) as resolved,
       SUM(status_id=4) as closed
    ")
    ->where('assigned_engineer_id',$dev_id)
    ->get('tickets')
    ->row_array();
}






}
