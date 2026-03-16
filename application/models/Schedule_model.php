<?php
class Schedule_model extends CI_Model {
    private function applyDueDateCondition($date)
    {
        $this->db->group_start()
            ->where('schedule_tasks.frequency', 'daily')
            ->or_group_start()
                ->where('schedule_tasks.frequency', 'weekly')
                ->where('DAYOFWEEK(schedule_tasks.start_date) = DAYOFWEEK(' . $this->db->escape($date) . ')', null, false)
            ->group_end()
            ->or_group_start()
                ->where('schedule_tasks.frequency', 'monthly')
                ->where('DAY(schedule_tasks.start_date) = DAY(' . $this->db->escape($date) . ')', null, false)
            ->group_end()
            ->or_group_start()
                ->where('schedule_tasks.frequency', 'once')
                ->where('DATE(schedule_tasks.start_date) = ' . $this->db->escape($date), null, false)
            ->group_end()
        ->group_end();
    }

    public function getScopeUsers(array $user_ids)
    {
        if (empty($user_ids)) {
            return [];
        }

        return $this->db
            ->select('users.user_id, users.name, users.role_id, users.department_id, departments.department_name')
            ->from('users')
            ->join('departments', 'departments.department_id = users.department_id', 'left')
            ->where('users.status', 'Active')
            ->where_in('users.user_id', array_unique(array_map('intval', $user_ids)))
            ->order_by('departments.department_name', 'ASC')
            ->order_by('users.name', 'ASC')
            ->get()
            ->result();
    }

    public function getAssignableUsers(array $subordinate_ids)
    {
        return $this->getScopeUsers($subordinate_ids);
    }

    public function getDepartmentPeers($department_id, $exclude_user_id = null)
    {
        $this->db
            ->select('user_id, name, role_id, department_id')
            ->from('users')
            ->where('department_id', (int) $department_id)
            ->where('status', 'Active');

        if ($exclude_user_id !== null) {
            $this->db->where('user_id !=', (int) $exclude_user_id);
        }

        return $this->db
            ->order_by('name', 'ASC')
            ->get()
            ->result();
    }

    public function getUserByNameAndScope($name, $department_id = null, $role_id = null)
    {
        $this->db
            ->select('user_id, name, role_id, department_id, email, status')
            ->from('users')
            ->where('status', 'Active')
            ->like('name', trim((string) $name));

        if ($department_id !== null) {
            $this->db->where('department_id', (int) $department_id);
        }

        if ($role_id !== null) {
            $this->db->where('role_id', (int) $role_id);
        }

        return $this->db
            ->order_by('user_id', 'ASC')
            ->get()
            ->row();
    }

    public function getVisibleSchedules(array $visible_user_ids = [])
    {
        $visible_user_ids = array_unique(array_map('intval', $visible_user_ids));
        if (empty($visible_user_ids)) {
            return [];
        }

        $this->db
            ->select('schedule_tasks.*, users.name as assigned_user, users.department_id as assigned_department_id, departments.department_name as assigned_department_name, creator.name as created_by_name')
            ->from('schedule_tasks')
            ->join('users', 'users.user_id = schedule_tasks.assigned_user_id', 'left')
            ->join('departments', 'departments.department_id = users.department_id', 'left')
            ->join('users as creator', 'creator.user_id = schedule_tasks.created_by', 'left');

        $this->db->group_start()
            ->where_in('schedule_tasks.assigned_user_id', $visible_user_ids)
            ->or_where_in('schedule_tasks.created_by', $visible_user_ids)
            ->group_end();

        return $this->db
            ->order_by('schedule_tasks.id', 'DESC')
            ->get()
            ->result();
    }

    public function getActiveScheduleTasksForUser($user_id, $dept_id = null)
    {
        if ($user_id <= 0) {
            return [];
        }

        $this->db
            ->select('schedule_tasks.*')
            ->from('schedule_tasks')
            ->join('users', 'users.user_id = schedule_tasks.assigned_user_id', 'left')
            ->where('schedule_tasks.assigned_user_id', (int) $user_id)
            ->where('schedule_tasks.status', 'active');

        if (!empty($dept_id)) {
            $this->db->where('users.department_id', (int) $dept_id);
        }

        return $this->db
            ->order_by('schedule_tasks.schedule_name', 'ASC')
            ->get()
            ->result();
    }

    public function getActiveDelegationsForDate($date, array $original_user_ids = [])
    {
        if (empty($original_user_ids)) {
            return [];
        }

        $rows = $this->db
            ->select('task_delegations.*, original.name as original_user_name, delegated.name as delegated_user_name')
            ->from('task_delegations')
            ->join('users as original', 'original.user_id = task_delegations.original_user_id', 'left')
            ->join('users as delegated', 'delegated.user_id = task_delegations.delegated_user_id', 'left')
            ->where_in('task_delegations.original_user_id', array_unique(array_map('intval', $original_user_ids)))
            ->group_start()
                ->where('task_delegations.approval_status', 'approved')
                ->or_group_start()
                    ->where('task_delegations.approval_status', 'pending')
                    ->where('task_delegations.start_date <', $date)
                ->group_end()
            ->group_end()
            ->where('task_delegations.start_date <=', $date)
            ->where('task_delegations.end_date >=', $date)
            ->order_by('task_delegations.id', 'DESC')
            ->get()
            ->result();

        $map = [];
        foreach ($rows as $row) {
            if (!isset($map[$row->original_user_id])) {
                $map[$row->original_user_id] = $row;
            }
        }

        return $map;
    }

    public function getTodayTasksForScope($viewer_user_id, array $scope_user_ids, $selected_user_id, $date, $dept_id = null, $mode = 'today', $priority = 'all')
    {
        $scope_user_ids = array_unique(array_map('intval', $scope_user_ids));
        $selected_user_id = (int) $selected_user_id;
        $filter_by_selected_user = $selected_user_id > 0;
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $allowed_priorities = ['high', 'medium', 'low', 'all'];
        $priority = strtolower(trim((string) $priority));
        if (!in_array($priority, $allowed_priorities, true)) {
            $priority = 'all';
        }

        if (empty($scope_user_ids)) {
            return [];
        }

        if ($filter_by_selected_user && !in_array($selected_user_id, $scope_user_ids, true)) {
            return [];
        }

        $this->db
            ->select('schedule_tasks.*, users.name as assigned_user, users.department_id as assigned_department_id, departments.department_name as assigned_department_name, creator.name as created_by_name')
            ->from('schedule_tasks')
            ->join('users', 'users.user_id = schedule_tasks.assigned_user_id', 'left')
            ->join('departments', 'departments.department_id = users.department_id', 'left')
            ->join('users as creator', 'creator.user_id = schedule_tasks.created_by', 'left')
            ->where('schedule_tasks.status', 'active')
            ->where_in('schedule_tasks.assigned_user_id', $scope_user_ids);

        if (!empty($dept_id)) {
            $this->db->where('users.department_id', (int) $dept_id);
        }

        if ($mode === 'today') {
            $this->applyDueDateCondition($date);
        }

        if ($priority !== 'all') {
            $this->db->where('schedule_tasks.priority', $priority);
        }

        $rows = $this->db
            ->order_by("FIELD(schedule_tasks.priority, 'high', 'medium', 'low')", null, false)
            ->order_by('schedule_tasks.task_time', 'ASC')
            ->order_by('schedule_tasks.id', 'DESC')
            ->get()
            ->result();

        $delegations = $this->getActiveDelegationsForDate($date, array_map(function ($row) {
            return (int) $row->assigned_user_id;
        }, $rows));

        $schedule_ids = [];
        foreach ($rows as $row) {
            $schedule_ids[] = (int) $row->id;
        }

        $logs = $this->getLogsForDate($date, $schedule_ids);
        $filtered = [];

        foreach ($rows as $row) {
            $effective_user_id = (int) $row->assigned_user_id;
            $delegated_from_user_id = null;
            $effective_user_name = $row->assigned_user;
            $delegated_user_name = null;

            if (isset($delegations[$row->assigned_user_id])) {
                $delegation = $delegations[$row->assigned_user_id];
                $effective_user_id = (int) $delegation->delegated_user_id;
                $delegated_from_user_id = (int) $delegation->original_user_id;
                $effective_user_name = $delegation->delegated_user_name;
                $delegated_user_name = $delegation->delegated_user_name;
            }

            if (
                $filter_by_selected_user &&
                $effective_user_id !== $selected_user_id &&
                (int) $row->assigned_user_id !== $selected_user_id
            ) {
                continue;
            }

            $log = isset($logs[$row->id]) ? $logs[$row->id] : null;

            $row->effective_user_id = $effective_user_id;
            $row->effective_user_name = $effective_user_name;
            $row->delegated_from_user_id = $delegated_from_user_id;
            $row->delegated_user_name = $delegated_user_name;
            $row->owner_display_name = $row->assigned_user;
            $row->delegation_note = !empty($delegated_from_user_id)
                ? (!empty($delegated_user_name) ? 'Delegated to ' . $delegated_user_name : 'Delegated')
                : null;
            $row->log_status = $log ? $log->status : 'pending';

            if (
                $row->log_status === 'pending'
                && $date === $today
                && !empty($row->task_time)
                && $row->task_time <= $current_time
            ) {
                $row->log_status = 'overdue';
            }

            $row->completed_time = $log ? $log->completed_time : null;

            $filtered[] = $row;
        }

        return $filtered;
    }

    public function getLogsForDate($date, array $schedule_ids)
    {
        if (empty($schedule_ids)) {
            return [];
        }

        $rows = $this->db
            ->from('schedule_task_logs')
            ->where('execution_date', $date)
            ->where_in('schedule_task_id', array_unique(array_map('intval', $schedule_ids)))
            ->get()
            ->result();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->schedule_task_id] = $row;
        }

        return $map;
    }

    public function getOrCreateLog($schedule_task_id, $date, $assigned_user_id, $delegated_from_user_id = null)
    {
        $log = $this->db
            ->where('schedule_task_id', (int) $schedule_task_id)
            ->where('execution_date', $date)
            ->get('schedule_task_logs')
            ->row();

        if ($log) {
            if ((int) $log->assigned_user_id !== (int) $assigned_user_id || (int) $log->delegated_from_user_id !== (int) $delegated_from_user_id) {
                $this->db
                    ->where('id', $log->id)
                    ->update('schedule_task_logs', [
                        'assigned_user_id' => $assigned_user_id,
                        'delegated_from_user_id' => $delegated_from_user_id,
                    ]);

                $log->assigned_user_id = $assigned_user_id;
                $log->delegated_from_user_id = $delegated_from_user_id;
            }

            return $log;
        }

        $data = [
            'schedule_task_id' => (int) $schedule_task_id,
            'execution_date' => $date,
            'assigned_user_id' => (int) $assigned_user_id,
            'delegated_from_user_id' => $delegated_from_user_id ? (int) $delegated_from_user_id : null,
            'status' => 'pending',
        ];

        $this->db->insert('schedule_task_logs', $data);
        return $this->db->where('id', $this->db->insert_id())->get('schedule_task_logs')->row();
    }

    public function markTaskComplete($schedule_task_id, $date, $completed_by, $assigned_user_id, $delegated_from_user_id = null)
    {
        $log = $this->getOrCreateLog($schedule_task_id, $date, $assigned_user_id, $delegated_from_user_id);

        return $this->db
            ->where('id', $log->id)
            ->update('schedule_task_logs', [
                'status' => 'completed',
                'completed_by' => (int) $completed_by,
                'completed_time' => date('Y-m-d H:i:s'),
            ]);
    }

    public function createDelegation(array $data)
    {
        return $this->db->insert('task_delegations', $data);
    }

    public function hasOverlappingDelegation($original_user_id, $start_date, $end_date, $exclude_id = null)
    {
        $this->db
            ->from('task_delegations')
            ->where('task_delegations.original_user_id', (int) $original_user_id)
            ->where_in('task_delegations.approval_status', ['pending', 'approved'])
            ->where('task_delegations.start_date <=', $end_date)
            ->where('task_delegations.end_date >=', $start_date);

        if ($exclude_id !== null) {
            $this->db->where('task_delegations.id !=', (int) $exclude_id);
        }

        return (bool) $this->db->count_all_results();
    }

    public function getDelegationById($delegation_id)
    {
        return $this->db
            ->select('task_delegations.*, original.department_id as original_department_id, original.name as original_user_name, original.email as original_user_email, delegated.name as delegated_user_name, delegated.email as delegated_user_email, creator.name as created_by_name, creator.email as created_by_email, approver.name as approved_by_name, approver.email as approved_by_email')
            ->from('task_delegations')
            ->join('users as original', 'original.user_id = task_delegations.original_user_id', 'left')
            ->join('users as delegated', 'delegated.user_id = task_delegations.delegated_user_id', 'left')
            ->join('users as creator', 'creator.user_id = task_delegations.created_by', 'left')
            ->join('users as approver', 'approver.user_id = task_delegations.approved_by', 'left')
            ->where('task_delegations.id', (int) $delegation_id)
            ->get()
            ->row();
    }

    public function updateDelegationApproval($delegation_id, $approval_status, $approved_by, $remarks = null)
    {
        return $this->db
            ->where('id', (int) $delegation_id)
            ->update('task_delegations', [
                'approval_status' => $approval_status,
                'approved_by' => (int) $approved_by,
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_remarks' => $remarks !== null && $remarks !== '' ? $remarks : null,
            ]);
    }

    public function updateDelegationWorkflow($delegation_id, array $data)
    {
        return $this->db
            ->where('id', (int) $delegation_id)
            ->update('task_delegations', $data);
    }

    public function deleteDelegation($delegation_id)
    {
        return $this->db
            ->where('id', (int) $delegation_id)
            ->delete('task_delegations');
    }

    public function getDelegationsForUsers(array $user_ids)
    {
        if (empty($user_ids)) {
            return [];
        }

        $user_ids = array_unique(array_map('intval', $user_ids));

        return $this->db
            ->select('task_delegations.*, original.department_id as original_department_id, original.name as original_user_name, original.email as original_user_email, delegated.name as delegated_user_name, delegated.email as delegated_user_email, creator.name as created_by_name, creator.email as created_by_email, approver.name as approved_by_name, approver.email as approved_by_email')
            ->from('task_delegations')
            ->join('users as original', 'original.user_id = task_delegations.original_user_id', 'left')
            ->join('users as delegated', 'delegated.user_id = task_delegations.delegated_user_id', 'left')
            ->join('users as creator', 'creator.user_id = task_delegations.created_by', 'left')
            ->join('users as approver', 'approver.user_id = task_delegations.approved_by', 'left')
            ->group_start()
                ->where_in('task_delegations.original_user_id', $user_ids)
                ->or_where_in('task_delegations.delegated_user_id', $user_ids)
            ->group_end()
            ->order_by('task_delegations.start_date', 'DESC')
            ->get()
            ->result();
    }

    public function getPendingDelegationsForApprovalWindow($run_date, $target_start_date)
    {
        return $this->db
            ->select('task_delegations.*, original.department_id as original_department_id, original.name as original_user_name, original.email as original_user_email, delegated.name as delegated_user_name, delegated.email as delegated_user_email, creator.name as created_by_name, creator.email as created_by_email')
            ->from('task_delegations')
            ->join('users as original', 'original.user_id = task_delegations.original_user_id', 'left')
            ->join('users as delegated', 'delegated.user_id = task_delegations.delegated_user_id', 'left')
            ->join('users as creator', 'creator.user_id = task_delegations.created_by', 'left')
            ->where('task_delegations.approval_status', 'pending')
            ->where('task_delegations.start_date <=', $target_start_date)
            ->where('task_delegations.end_date >=', $run_date)
            ->order_by('task_delegations.start_date', 'ASC')
            ->order_by('task_delegations.id', 'ASC')
            ->get()
            ->result();
    }

    public function getApprovedDelegationsStartingOn($date)
    {
        return $this->db
            ->select('task_delegations.*, original.department_id as original_department_id, original.name as original_user_name, original.email as original_user_email, delegated.name as delegated_user_name, delegated.email as delegated_user_email, creator.name as created_by_name, creator.email as created_by_email')
            ->from('task_delegations')
            ->join('users as original', 'original.user_id = task_delegations.original_user_id', 'left')
            ->join('users as delegated', 'delegated.user_id = task_delegations.delegated_user_id', 'left')
            ->join('users as creator', 'creator.user_id = task_delegations.created_by', 'left')
            ->where('task_delegations.approval_status', 'approved')
            ->where('task_delegations.start_date', $date)
            ->where('task_delegations.end_date >=', $date)
            ->order_by('task_delegations.id', 'ASC')
            ->get()
            ->result();
    }

    public function getSchedulesForReminderProcessing($date)
    {
        $this->db
            ->select('schedule_tasks.*, users.name as assigned_user, users.email as assigned_user_email, creator.name as created_by_name')
            ->from('schedule_tasks')
            ->join('users', 'users.user_id = schedule_tasks.assigned_user_id', 'left')
            ->join('users as creator', 'creator.user_id = schedule_tasks.created_by', 'left')
            ->where('schedule_tasks.status', 'active');

        $this->applyDueDateCondition($date);

        return $this->db
            ->order_by('schedule_tasks.reminder_time', 'ASC')
            ->get()
            ->result();
    }

    public function createNotification(array $data)
    {
        return $this->db->insert('task_messages', $data);
    }

    public function markReminderSent($log_id)
    {
        return $this->db
            ->where('id', (int) $log_id)
            ->update('schedule_task_logs', ['reminder_sent_at' => date('Y-m-d H:i:s')]);
    }

    public function markWarningSent($log_id)
    {
        return $this->db
            ->where('id', (int) $log_id)
            ->update('schedule_task_logs', [
                'warning_sent_at' => date('Y-m-d H:i:s'),
                'status' => 'overdue',
            ]);
    }

    public function markScheduleNotificationsRead($schedule_task_id, $receiver_id)
    {
        return $this->db
            ->where('schedule_task_id', (int) $schedule_task_id)
            ->where('receiver_id', (int) $receiver_id)
            ->where_in('notification_type', ['schedule_reminder', 'schedule_warning', 'schedule_delegation'])
            ->update('task_messages', ['is_read' => 1]);
    }

    public function createSchedule(array $data)
    {
        return $this->db->insert('schedule_tasks', $data);
    }

    public function getScheduleById($schedule_id)
    {
        return $this->db
            ->select('schedule_tasks.*, users.name as assigned_user, creator.name as created_by_name')
            ->from('schedule_tasks')
            ->join('users', 'users.user_id = schedule_tasks.assigned_user_id', 'left')
            ->join('users as creator', 'creator.user_id = schedule_tasks.created_by', 'left')
            ->where('schedule_tasks.id', (int) $schedule_id)
            ->get()
            ->row();
    }

    public function updateSchedule($schedule_id, array $data)
    {
        return $this->db
            ->where('id', (int) $schedule_id)
            ->update('schedule_tasks', $data);
    }

    public function deleteSchedule($schedule_id)
    {
        $schedule_id = (int) $schedule_id;

        $this->db->trans_start();
        $this->db->where('schedule_task_id', $schedule_id)->delete('schedule_task_logs');
        $this->db->where('schedule_task_id', $schedule_id)->delete('task_messages');
        $this->db->where('id', $schedule_id)->delete('schedule_tasks');
        $this->db->trans_complete();

        return $this->db->trans_status() === true;
    }
}
