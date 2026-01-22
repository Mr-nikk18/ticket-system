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

}
