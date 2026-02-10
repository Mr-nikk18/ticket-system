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
                SUM(t.status = "Resolved") AS resolved_tickets,
                SUM(t.status != "Resolved") AS pending_tickets
            ')
            ->from('users u')
            ->join('tickets t', 't.assigned_engineer_id = u.user_id', 'left')
            ->where('u.department', 'developer')
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
                WHERE status = 'open'
                  AND deleted_at IS NULL
            ) AS open_cnt,

            /* ✅ DEVELOPER WISE COUNTS */
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS process_cnt,
            SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) AS resolved_cnt,
            SUM(CASE WHEN t.status = 'closed' THEN 1 ELSE 0 END) AS closed_cnt
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
    ->where_in('status',['open','closed','resolved','in_progress'])
    ->get('tickets')
    ->result_array();
}

public function getStatusCountsForDeveloper($dev_id)
{
  return $this->db
    ->select("
      (SELECT COUNT(*) FROM tickets 
       WHERE status='open' AND deleted_at IS NULL) as open,
       SUM(status='in_progress') as process,
       SUM(status='resolved') as resolved,
       SUM(status='closed') as closed
    ")
    ->where('assigned_engineer_id',$dev_id)
    ->get('tickets')
    ->row_array();
}






}
