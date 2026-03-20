<?php
class TRS_model extends CI_Model
{
        private function getRecentClosedVisibilitySql($alias = 't')
        {
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $alias);
            $alias = $alias !== '' ? $alias : 't';

            return sprintf(
                '(%1$s.status_id = 4 AND COALESCE(%1$s.closed_at, %1$s.updated_at, %1$s.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY))',
                $alias
            );
        }

        private function applyVisibleTicketStateFilter($alias = 't')
        {
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $alias);
            $alias = $alias !== '' ? $alias : 't';

            $this->db->group_start()
                ->where($alias . '.status_id !=', 4)
                ->or_where($this->getRecentClosedVisibilitySql($alias), null, false)
            ->group_end();
        }

        private function get_ticket_rule_scope()
        {
            return ((int) $this->session->userdata('department_id') === 2) ? 1 : 0;
        }

        public function get_it_team_users()
        {
            return $this->db
                ->select('user_id, name, email, role_id')
                ->from('users')
                ->where('department_id', 2)
                ->where('status', 'Active')
                ->order_by('role_id', 'DESC')
                ->order_by('name', 'ASC')
                ->get()
                ->result();
        }

        public function get_ticket_owner($ticket_id)
        {
            return $this->db
                ->select('tickets.ticket_id, tickets.title, tickets.created_at, tickets.assignment_due_at, tickets.assigned_engineer_id, owner.user_id as owner_user_id, owner.name as owner_name, owner.email as owner_email')
                ->from('tickets')
                ->join('users as owner', 'owner.user_id = tickets.user_id', 'left')
                ->where('tickets.ticket_id', (int) $ticket_id)
                ->get()
                ->row();
        }

        public function get_engineer_user($user_id)
        {
            return $this->db
                ->select('user_id, name, email')
                ->from('users')
                ->where('user_id', (int) $user_id)
                ->get()
                ->row();
        }

        public function get_unassigned_open_tickets_for_assignment($now)
        {
            return $this->db
                ->select('tickets.*, owner.name as owner_name, owner.email as owner_email')
                ->from('tickets')
                ->join('users as owner', 'owner.user_id = tickets.user_id', 'left')
                ->where('tickets.status_id', 1)
                ->where('tickets.assigned_engineer_id IS NULL', null, false)
                ->where('tickets.deleted_at IS NULL', null, false)
                ->where('tickets.assignment_due_at <=', $now)
                ->get()
                ->result();
        }

        public function get_assignment_reminder_candidates($now)
        {
            return $this->db
                ->select('tickets.*, owner.name as owner_name, owner.email as owner_email')
                ->from('tickets')
                ->join('users as owner', 'owner.user_id = tickets.user_id', 'left')
                ->where('tickets.status_id', 1)
                ->where('tickets.assigned_engineer_id IS NULL', null, false)
                ->where('tickets.deleted_at IS NULL', null, false)
                ->where('tickets.assignment_reminder_sent_at IS NULL', null, false)
                ->group_start()
                    ->where("TIME(" . $this->db->escape($now) . ") >= '16:00:00'", null, false)
                    ->where("DATE(tickets.created_at) = DATE(" . $this->db->escape($now) . ")", null, false)
                    ->or_where('tickets.assignment_due_at <=', date('Y-m-d H:i:s', strtotime($now . ' +8 hours')))
                ->group_end()
                ->where('tickets.assignment_due_at >', $now)
                ->get()
                ->result();
        }

        public function mark_assignment_reminder_sent($ticket_id)
        {
            return $this->db
                ->where('ticket_id', (int) $ticket_id)
                ->update('tickets', ['assignment_reminder_sent_at' => date('Y-m-d H:i:s')]);
        }

        public function get_auto_assign_candidate()
        {
            return $this->db->query("
                SELECT u.user_id, u.name, u.email, COUNT(t.ticket_id) AS active_ticket_count
                FROM users u
                LEFT JOIN tickets t
                    ON t.assigned_engineer_id = u.user_id
                   AND t.deleted_at IS NULL
                   AND t.status_id IN (1,2,3)
                WHERE u.department_id = 2
                  AND u.role_id = 1
                  AND u.status = 'Active'
                GROUP BY u.user_id, u.name, u.email
                ORDER BY active_ticket_count ASC, u.name ASC
                LIMIT 1
            ")->row();
        }

        public function create_notification(array $data)
        {
            return $this->db->insert('task_messages', $data);
        }

        public function get_status_id($status_name)
        {
            $status = $this->db
                ->select('status_id')
                ->from('ticket_statuses')
                ->where('LOWER(status_name)', strtolower($status_name))
                ->get()
                ->row_array();

            return $status ? $status['status_id'] : 2;
        }

    public function get_data_by_id($ticket_id)
    {
        return $this->db
            ->select('
            tickets.*,
            u.name            AS user_full_name,
            u.department_id      AS departments_department_name,
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
    return $this->db->insert_id();   // 🔥 Important
    }

public function insert_tasks($ticket_id, $tasks)
{
    $position = 1;

    foreach($tasks as $task){

        if(trim($task) != ''){

            $this->db->insert('ticket_tasks', [
                'ticket_id' => $ticket_id,
                'task_title' => $task,
                'is_completed' => 0,
                'position' => $position,
                'created_by' => $this->session->userdata('user_id')
            ]);

            $position++;
        }
    }
}
public function add_insert_tasks($ticket_id, $tasks)
{
    $position = 1;

    foreach($tasks as $task){

        if(trim($task) != ''){

            $this->db->insert('ticket_tasks', [
                'ticket_id'   => $ticket_id,   // 🔥 THIS WAS MISSING
                'task_title'  => $task,
                'is_completed'=> 0,
                'position'    => $position,
                'created_by'  => $this->session->userdata('user_id')
            ]);

            $position++;
        }
    }
}

    public function insert_assignment_history($data)
{
    return $this->db->insert('ticket_assignment_history', $data);
}
public function insert_reassignment_history($data)
{
    return $this->db->insert('ticket_assignment_history', $data);
}
public function get_tasks_by_ticket($ticket_id)
{
    return $this->db
                ->where('ticket_id', $ticket_id)
                ->order_by('position', 'ASC')
                ->get('ticket_tasks')
                ->result_array();
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
    $this->db->where('deleted_at IS NULL', null, false);

    if ((int) $status === 4) {
        $this->db->where('status_id', 4);
        $this->db->where('COALESCE(closed_at, updated_at, created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)', null, false);
    } else {
        $this->db->where('status_id', $status);
    }

    if ($user_id) {
        $this->db->where('user_id', $user_id);
    }

    return $this->db->count_all_results('tickets');
}


    /* -------- USER TICKETS -------- */
public function get_user_tickets($user_id, $status = null)
{
    $this->db
        ->select('
            t.*,
            owner.name AS user_full_name,
            owner.department_id AS owner_department_id,
            engineer.name AS assigned_engineer_name,
            dept.department_name,
            GROUP_CONCAT(tt.task_title SEPARATOR "||") AS tasks
        ')
        ->from('tickets t')

        ->join('users owner', 'owner.user_id = t.user_id', 'left')
        ->join('users engineer', 'engineer.user_id = t.assigned_engineer_id', 'left')
        ->join('departments dept', 'dept.department_id = owner.department_id', 'left')
        ->join('ticket_tasks tt', 'tt.ticket_id = t.ticket_id', 'left')

        ->where('t.user_id', $user_id)
        ->where('t.deleted_at IS NULL')
        ->group_by('t.ticket_id');

    if ($status != null) {
        $this->db->where('t.status_id', $status);
    }

    return $this->db
        ->order_by('t.ticket_id', 'DESC')
        ->get()
        ->result_array();
}


    /* -------- ALL TICKETS -------- */
public function get_all_tickets($status = null)
{
    $this->db
        ->select('
            t.*,
            owner.name AS user_full_name,
            owner.department_id AS owner_department_id,
            engineer.name AS assigned_engineer_name,
            dept.department_name,
            GROUP_CONCAT(tt.task_title SEPARATOR "||") AS tasks
        ')
        ->from('tickets t')

        // Ticket owner
        ->join('users owner', 'owner.user_id = t.user_id', 'left')

        // Assigned engineer
        ->join('users engineer', 'engineer.user_id = t.assigned_engineer_id', 'left')

        // Department (FROM OWNER TABLE)
        ->join('departments dept', 'dept.department_id = owner.department_id', 'left')
        ->join('ticket_tasks tt', 'tt.ticket_id = t.ticket_id', 'left')

        ->where('t.deleted_at IS NULL')
        ->group_by('t.ticket_id');

    if ($status != null) {
        $this->db->where('t.status_id', $status);
    }

    return $this->db
        ->order_by('t.ticket_id', 'DESC')
        ->get()
        ->result_array();
}

public function get_visible_tickets_for_list($role_id, $department_id, $user_id, $status = null, $ticket_scope = 'all_tickets')
{
    $role_id = (int) $role_id;
    $department_id = (int) $department_id;
    $user_id = (int) $user_id;
    $ticket_scope = in_array($ticket_scope, ['all_tickets', 'my_tickets', 'my_accepted'], true)
        ? $ticket_scope
        : 'all_tickets';

    $this->db
        ->select('
            t.*,
            owner.name AS user_full_name,
            owner.department_id AS owner_department_id,
            engineer.name AS assigned_engineer_name,
            dept.department_name,
            GROUP_CONCAT(tt.task_title SEPARATOR "||") AS tasks
        ')
        ->from('tickets t')
        ->join('users owner', 'owner.user_id = t.user_id', 'left')
        ->join('users engineer', 'engineer.user_id = t.assigned_engineer_id', 'left')
        ->join('departments dept', 'dept.department_id = owner.department_id', 'left')
        ->join('ticket_tasks tt', 'tt.ticket_id = t.ticket_id', 'left')
        ->where('t.deleted_at IS NULL', null, false)
        ->group_by('t.ticket_id');

    // Hide closed tickets after 7 days, matching the Kanban board behavior.
    $this->applyVisibleTicketStateFilter('t');

    if ($status !== null) {
        $this->db->where('t.status_id', $status);
    }

    if ($ticket_scope === 'my_tickets') {
        $this->db->where('t.user_id', $user_id);
    } elseif ($ticket_scope === 'my_accepted') {
        $this->db->where('t.assigned_engineer_id', $user_id);

        if ($status === null) {
            $this->db->where_in('t.status_id', [2, 3, 4]);
        }
    }

    if ($role_id === 2 && $department_id === 2) {
        return $this->db
            ->order_by('t.ticket_id', 'DESC')
            ->get()
            ->result_array();
    }

    if ($department_id === 2) {
        if ($role_id === 1) {
            $this->db->group_start()
                ->where('t.user_id', $user_id)
                ->or_where('t.assigned_engineer_id', $user_id)
                ->or_group_start()
                    ->where('t.status_id', 1)
                    ->where('t.assigned_engineer_id IS NULL', null, false)
                ->group_end()
            ->group_end();
        } else {
            $this->db->group_start()
                ->where('t.assigned_engineer_id', $user_id)
                ->or_group_start()
                    ->where('t.status_id', 1)
                    ->where('t.assigned_engineer_id IS NULL', null, false)
                ->group_end()
            ->group_end();
        }

        return $this->db
            ->order_by('t.ticket_id', 'DESC')
            ->get()
            ->result_array();
    }

    if ($role_id === 2) {
        return $this->db
            ->order_by('t.ticket_id', 'DESC')
            ->get()
            ->result_array();
    }

    return $this->db
        ->where('owner.department_id', $department_id)
        ->order_by('t.ticket_id', 'DESC')
        ->get()
        ->result_array();
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
        ->where('t.status_id', 1)
        ->order_by("
            CASE 
                WHEN t.assigned_engineer_id = {$developer_id} 
                     AND t.status_id IN (2,4) THEN 1
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
    $tickets = $this->db
        ->select('
            tickets.*,
            u.name AS user_full_name,
            dept.department_name AS department_name,
            d.name AS assigned_engineer_name,
            COUNT(tt.task_id) AS total_tasks,
            SUM(CASE WHEN tt.is_completed = 1 THEN 1 ELSE 0 END) AS completed_tasks
        ')
        ->from('tickets')
        ->join('users u', 'u.user_id = tickets.user_id', 'left')
        ->join('departments dept', 'dept.department_id = u.department_id', 'left')
        ->join('users d', 'd.user_id = tickets.assigned_engineer_id', 'left')
        ->join('ticket_tasks tt', 'tt.ticket_id = tickets.ticket_id', 'left')
        ->where('tickets.assigned_engineer_id', $developer_id)
        ->where_in('tickets.status_id', [3,2,4])
        ->group_by('tickets.ticket_id')
        ->order_by('tickets.ticket_id', 'DESC')
        ->get()
        ->result_array();

    // 🔥 Now attach tasks
    foreach ($tickets as &$ticket) {

        $ticket['tasks'] = $this->db
            ->where('ticket_id', $ticket['ticket_id'])
            ->get('ticket_tasks')
            ->result_array();
    }

    return $tickets;
}

    public function get_all_developers()
    {
        return $this->db
            ->where('department_id', 2)
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
       AND r.is_it_only = ?
      WHERE t.deleted_at IS NULL
        AND t.status_id = ?
        AND (
            r.view_type = 'ALL'
            OR (r.view_type = 'ASSIGNED' AND t.assigned_engineer_id = ?)
            OR (r.view_type = 'OWN' AND t.user_id = ?)
        )
    ";

    return $this->db->query($sql,[
        $role_id,
        $status,
        $this->get_ticket_rule_scope(),
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
        ->where('is_it_only', $this->get_ticket_rule_scope())
        ->get()
        ->row()
        ->view_type;
}


public function get_recent_tickets($role_id,$user_id,$status_id)
{
    return $this->db->query("
        SELECT DISTINCT 
            t.*,
            u.name AS assigned_engineer_name,

            CASE
                WHEN r.view_type IN ('ASSIGNED','ALL')
                     AND t.assigned_engineer_id IS NULL
                     AND t.status_id = 1
                THEN 1
                ELSE 0
            END AS can_accept

        FROM tickets t

        LEFT JOIN users u 
            ON u.user_id = t.assigned_engineer_id
            
        JOIN role_ticket_rules r
            ON r.role_id = ?
            AND r.status = t.status_id
            AND r.is_it_only = ?

        WHERE t.deleted_at IS NULL
          AND t.status_id = ?   -- 🔥 IMPORTANT
          AND (
                r.view_type = 'ALL'
                OR (r.view_type = 'OWN' AND t.user_id = ?)
                OR (r.view_type = 'ASSIGNED' AND t.assigned_engineer_id = ?)
          )

        ORDER BY t.ticket_id DESC
        LIMIT 5
    ", [$role_id,$this->get_ticket_rule_scope(),$status_id,$user_id,$user_id])->result_array();
}
public function check_status_permission($role_id, $from_status, $to_status)
{
    $department_id = (int) $this->session->userdata('department_id');

    return $this->db
                ->where('role_id', $role_id)
                ->where('from_status', $from_status)
                ->where('to_status', $to_status)
                ->where('allowed', 1)
                ->group_start()
                    ->where('department_id', $department_id)
                    ->or_where('department_id IS NULL', null, false)
                ->group_end()
                ->order_by('department_id IS NULL', 'ASC', false)
                ->get('status_permissions')
                ->row();
}
public function get_tickets_by_status($status_id)
{
    $role_id = $this->session->userdata('role_id');
    $user_id = $this->session->userdata('user_id');
    $department_id=$this->session->userdata('department_id');

    $this->db->from('tickets');
    $this->db->where('status_id', $status_id);

    // 👤 USER
    if($role_id == 1 && $department_id != 2){ 
        $this->db->where('user_id', $user_id);
    }

    // 👨‍💻 DEVELOPER
    elseif($department_id  == 2){

        if($status_id != 1){
            $this->db->where('assigned_engineer_id', $user_id);
        }
    }

    // 🛠 ADMIN / IT HEAD → no filter

    return $this->db->get()->result();
}
public function get_unread_notifications($user_id)
{
    return $this->db
        ->select('task_messages.id,
                  task_messages.ticket_id,
                  task_messages.schedule_task_id,
                  task_messages.task_id,
                  task_messages.notification_type,
                  task_messages.message,
                  task_messages.created_at,
                  tickets.title,
                  schedule_tasks.schedule_name')
        ->join('tickets', 'tickets.ticket_id = task_messages.ticket_id', 'left')
        ->join('schedule_tasks', 'schedule_tasks.id = task_messages.schedule_task_id', 'left')
        ->where('receiver_id', $user_id)
        ->where('is_read', 0)
        ->order_by('task_messages.created_at', 'DESC')
        ->limit(5)
        ->get('task_messages')
        ->result();
}

}
