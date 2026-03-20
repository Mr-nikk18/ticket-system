<?php

class Ticket_model extends CI_Model
{
    private function normalizeTableAlias($alias = 't')
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $alias);

        return $alias !== '' ? $alias : 't';
    }

    private function getRecentClosedVisibilitySql($alias = 't')
    {
        $alias = $this->normalizeTableAlias($alias);

        return sprintf(
            '(%1$s.status_id = 4 AND COALESCE(%1$s.closed_at, %1$s.updated_at, %1$s.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY))',
            $alias
        );
    }

    private function applyVisibleTicketStateFilter($alias = 't')
    {
        $alias = $this->normalizeTableAlias($alias);

        $this->db->group_start()
            ->where($alias . '.status_id !=', 4)
            ->or_where($this->getRecentClosedVisibilitySql($alias), null, false)
        ->group_end();
    }

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
                owner.name AS ticket_owner,
                current.name AS now_handled_by,
                by_user.name AS action_by,
                to_user.name AS assigned_to_name
            ')
            ->from('ticket_assignment_history th')
            ->join('tickets t', 't.ticket_id = th.ticket_id')
            ->join('users owner', 'owner.user_id = t.user_id')
            ->join('users current', 'current.user_id = t.assigned_engineer_id', 'left')
            ->join('users by_user', 'by_user.user_id = th.assigned_by', 'left')
            ->join('users to_user', 'to_user.user_id = th.assigned_to', 'left')
            ->join('ticket_statuses ts', 'ts.status_id = t.status_id');

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
                by_user.name AS action_by,
                to_user.name AS assigned_to_name,
                t.title,
                s.status_slug AS recent_status,
                owner.name AS ticket_owner,
                current.name AS now_handled_by
            ')
            ->from('ticket_assignment_history th')
            ->join('users by_user', 'by_user.user_id = th.assigned_by', 'left')
            ->join('users to_user', 'to_user.user_id = th.assigned_to', 'left')
            ->join('tickets t', 't.ticket_id = th.ticket_id')
            ->join('ticket_statuses s', 's.status_id = t.status_id', 'left')
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
                ->where_in('user_id', $visible_user_ids)
                ->or_where('assigned_engineer_id', $user_id)
            ->group_end()
            ->get('tickets')
            ->result();
    }

    public function getDashboardCounts($visible_user_ids)
    {
        $this->db->where_in('user_id', $visible_user_ids);
        $total = $this->db->count_all_results('tickets', false);

        $this->db->where('status_id', 1);
        $open = $this->db->count_all_results('', false);

        $this->db->where('status_id', 4);
        $closed = $this->db->count_all_results();

        return [
            'total' => $total,
            'open' => $open,
            'closed' => $closed,
        ];
    }

    /**
     * Lightweight status counters for dashboard-like widgets.
     *
     * "mine": tickets created by the user OR currently assigned to the user.
     * "all": department-wide view that matches the board's department logic
     *        and the 7-day closed visibility rule.
     */
    public function getTicketStatusOverview($user_id, $role_id, $department_id, $scope = 'all', $year = null)
    {
        $user_id = (int) $user_id;
        $role_id = (int) $role_id;
        $department_id = (int) $department_id;
        $scope = strtolower(trim((string) $scope));
        $year = ($year === null || $year === '') ? null : (int) $year;

        $this->db->select('
            SUM(CASE WHEN t.status_id = 1 THEN 1 ELSE 0 END) AS open_tickets,
            SUM(CASE WHEN t.status_id = 2 THEN 1 ELSE 0 END) AS in_progress_tickets,
            SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) AS resolved_tickets,
            SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS closed_tickets
        ', false);

        $this->db->from('tickets t');
        $this->db->join('users owner', 'owner.user_id = t.user_id', 'left');
        $this->db->join('users eng', 'eng.user_id = t.assigned_engineer_id', 'left');
        $this->db->where('t.deleted_at IS NULL', null, false);
        if (!empty($year)) {
            $this->db->where('YEAR(t.created_at)', $year);
        }

        // Keep in sync with get_board_tickets(): hide closed tickets after 7 days.
        $this->applyVisibleTicketStateFilter('t');

        if ($scope === 'mine') {
            $this->db->group_start()
                ->where('t.user_id', $user_id)
                ->or_where('t.assigned_engineer_id', $user_id)
            ->group_end();
        } else {
            // Match the visible ticket list logic so dashboard counters line up
            // with what the user can actually see in the UI.
            if ($department_id === 2) {
                if ($role_id === 2) {
                    // Team board / all view: managers in dept 2 can see all tickets.
                } elseif ($role_id === 1) {
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
            } else {
                $this->db->where('owner.department_id', $department_id);
            }
        }

        $row = (array) $this->db->get()->row_array();

        $open = (int) ($row['open_tickets'] ?? 0);
        $inProgress = (int) ($row['in_progress_tickets'] ?? 0);
        $resolved = (int) ($row['resolved_tickets'] ?? 0);
        $closed = (int) ($row['closed_tickets'] ?? 0);

        return [
            'open_tickets' => $open,
            'in_progress_tickets' => $inProgress,
            'resolved_tickets' => $resolved,
            'closed_tickets' => $closed,
            'total_tickets' => $open + $inProgress + $resolved + $closed,
            'scope' => $scope === 'mine' ? 'mine' : 'all',
            'year' => $year,
        ];
    }

    public function get_board_tickets()
    {
        $user_id = (int) $this->session->userdata('user_id');
        $role_id = (int) $this->session->userdata('role_id');
        $department_id = (int) $this->session->userdata('department_id');
        $filter = (string) $this->input->get('filter');
        $board = (string) $this->input->get('board');

        $this->db->select('
            t.*,
            ts.status_name,
            ts.status_slug,
            tp.priority_name,
            u.name AS handled_by_name,
            owner.name AS owner_name,
            owner.department_id AS owner_department_id,
            dept.department_name AS owner_department_name,
            (
                SELECT COUNT(*)
                FROM task_messages tm
                WHERE tm.ticket_id = t.ticket_id
                  AND tm.receiver_id = ' . $this->db->escape($user_id) . '
                  AND tm.task_id IS NOT NULL
                  AND tm.is_read = 0
            ) AS unread_comment_count,
            CASE
                WHEN NOT EXISTS (
                    SELECT 1
                    FROM ticket_tasks tt
                    WHERE tt.ticket_id = t.ticket_id
                      AND tt.is_completed = 0
                ) THEN 1
                ELSE 0
            END AS can_resolve
        ');

        $this->db->from('tickets t');
        $this->db->join('ticket_statuses ts', 'ts.status_id = t.status_id');
        $this->db->join('ticket_priorities tp', 'tp.priority_id = t.priority_id', 'left');
        $this->db->join('users u', 'u.user_id = t.assigned_engineer_id', 'left');
        $this->db->join('users owner', 'owner.user_id = t.user_id', 'left');
        $this->db->join('departments dept', 'dept.department_id = owner.department_id', 'left');
        $this->db->where('t.deleted_at IS NULL', null, false);

        if ($department_id === 2) {
            if ($role_id === 2) {
                if ($board === 'team' || $filter === 'all') {
                    // Read-only team board: all tickets.
                } elseif ($filter === 'raised') {
                    $this->db->where('t.user_id', $user_id);
                } else {
                    $this->db->group_start()
                        ->where('t.assigned_engineer_id', $user_id)
                        ->or_group_start()
                            ->where('t.status_id', 1)
                            ->where('t.assigned_engineer_id IS NULL', null, false)
                        ->group_end()
                    ->group_end();
                }
            } elseif ($filter === 'raised') {
                $this->db->where('t.user_id', $user_id);
            } else {
                $this->db->group_start()
                    ->where('t.assigned_engineer_id', $user_id)
                    ->or_group_start()
                        ->where('t.status_id', 1)
                        ->where('t.assigned_engineer_id IS NULL', null, false)
                    ->group_end()
                ->group_end();
            }
        } else {
            if ($role_id === 2) {
                if ($filter === 'raised') {
                    $this->db->where('t.user_id', $user_id);
                } elseif ($filter === 'assigned' || $filter === '') {
                    $this->db->where('owner.department_id', $department_id);
                }
            } elseif ($filter === 'raised') {
                $this->db->where('t.user_id', $user_id);
            } else {
                $this->db->where('owner.department_id', $department_id);
            }
        }

        $this->applyVisibleTicketStateFilter('t');

        $this->db->order_by('ts.display_order', 'ASC');
        $this->db->order_by('t.board_position', 'ASC');
        $this->db->order_by('t.ticket_id', 'DESC');

        $tickets = $this->db->get()->result();

        foreach ($tickets as &$ticket) {
            $ticket->tasks = $this->db
                ->where('ticket_id', $ticket->ticket_id)
                ->order_by('position', 'ASC')
                ->get('ticket_tasks')
                ->result();
        }

        return $tickets;
    }

    public function update_position($ticket_id, $status_id, $position)
    {
        $this->db->where('ticket_id', $ticket_id)
            ->update('tickets', [
                'status_id' => $status_id,
                'board_position' => $position,
            ]);

        $tickets = $this->db->where('status_id', $status_id)
            ->order_by('board_position', 'ASC')
            ->get('tickets')
            ->result();

        $i = 0;
        foreach ($tickets as $ticket) {
            $this->db->where('ticket_id', $ticket->ticket_id)
                ->update('tickets', ['board_position' => $i]);
            $i++;
        }
    }

    public function get_ticket_by_id($ticket_id)
    {
        return $this->db
            ->select('
                t.*,
                ts.status_name,
                ts.status_slug,
                u.name AS handled_by_name,
                owner.name AS owner_name,
                owner.department_id AS owner_department_id
            ')
            ->from('tickets t')
            ->join('ticket_statuses ts', 'ts.status_id = t.status_id', 'left')
            ->join('users u', 'u.user_id = t.assigned_engineer_id', 'left')
            ->join('users owner', 'owner.user_id = t.user_id', 'left')
            ->where('t.ticket_id', $ticket_id)
            ->get()
            ->row();
    }

    public function get_tasks_by_ticket($ticket_id)
    {
        return $this->db
            ->where('ticket_id', $ticket_id)
            ->order_by('position', 'ASC')
            ->get('ticket_tasks')
            ->result();
    }

    /**
     * Ensure the ticket_feedback table exists.
     * This allows storing a single rating + comment per ticket.
     */
    public function ensure_feedback_table_exists()
    {
        $this->load->dbforge();

        if (!$this->db->table_exists('ticket_feedback')) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'ticket_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false
                ],
                'rating' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true
                ],
                'comment' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'created_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false
                ]
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('ticket_id');
            $this->dbforge->create_table('ticket_feedback', true);
        }
    }

    public function get_feedback_by_ticket($ticket_id)
    {
        if (!$this->db->table_exists('ticket_feedback')) {
            return null;
        }

        return $this->db
            ->where('ticket_id', $ticket_id)
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get('ticket_feedback')
            ->row();
    }

    public function save_feedback($ticket_id, $rating, $comment, $created_by)
    {
        $this->ensure_feedback_table_exists();

        $existing = $this->get_feedback_by_ticket($ticket_id);

        $data = [
            'ticket_id' => $ticket_id,
            'rating' => $rating,
            'comment' => $comment,
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            // update existing
            $this->db->where('id', $existing->id);
            return $this->db->update('ticket_feedback', $data);
        }

        return $this->db->insert('ticket_feedback', $data);
    }
}
