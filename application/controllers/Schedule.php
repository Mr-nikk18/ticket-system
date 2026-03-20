<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Schedule extends MY_Controller {
    private function extractDelegationRemarkParts($remarks)
    {
        $remarks = trim((string) $remarks);
        $parts = [
            'task_id' => null,
            'task_title' => '',
            'request_reason' => '',
            'manager_note' => '',
        ];

        if ($remarks === '') {
            return $parts;
        }

        if (strpos($remarks, 'REQUEST_REASON::') === 0 || strpos($remarks, 'TASK_ID::') === 0) {
            $lines = preg_split("/\\r\\n|\\n|\\r/", $remarks);

            foreach ($lines as $line) {
                if (strpos($line, 'TASK_ID::') === 0) {
                    $parts['task_id'] = (int) trim(substr($line, strlen('TASK_ID::')));
                } elseif (strpos($line, 'TASK_TITLE::') === 0) {
                    $parts['task_title'] = trim(substr($line, strlen('TASK_TITLE::')));
                } elseif (strpos($line, 'REQUEST_REASON::') === 0) {
                    $parts['request_reason'] = trim(substr($line, strlen('REQUEST_REASON::')));
                } elseif (strpos($line, 'MANAGER_NOTE::') === 0) {
                    $parts['manager_note'] = trim(substr($line, strlen('MANAGER_NOTE::')));
                }
            }

            return $parts;
        }

        if (strpos($remarks, 'Request reason:') === 0) {
            $parts['request_reason'] = trim(substr($remarks, strlen('Request reason:')));
            return $parts;
        }

        $parts['manager_note'] = $remarks;
        return $parts;
    }

    private function buildDelegationRemarks($request_reason, $manager_note = '', $task_id = null, $task_title = '')
    {
        $lines = [];

        if (!empty($task_id)) {
            $lines[] = 'TASK_ID::' . (int) $task_id;
        }

        if (trim((string) $task_title) !== '') {
            $lines[] = 'TASK_TITLE::' . trim((string) $task_title);
        }

        $lines[] = 'REQUEST_REASON::' . trim((string) $request_reason);

        if (trim((string) $manager_note) !== '') {
            $lines[] = 'MANAGER_NOTE::' . trim((string) $manager_note);
        }

        return implode(PHP_EOL, $lines);
    }

    private function getKrupalApprover()
    {
        static $krupal = null;

        if ($krupal !== null) {
            return $krupal;
        }

        $krupal = $this->Schedule_model->getUserByNameAndScope('Krupal', 2, 2);
        return $krupal;
    }

    private function getKrupalApproverId()
    {
        $krupal = $this->getKrupalApprover();
        return $krupal ? (int) $krupal->user_id : 0;
    }

    private function formatDelegationWindow($delegation)
    {
        $start = !empty($delegation->start_date) ? date('d M Y', strtotime($delegation->start_date)) : '-';
        $end = !empty($delegation->end_date) ? date('d M Y', strtotime($delegation->end_date)) : '-';

        return $start . ' - ' . $end;
    }

    private function getDepartmentApproverUsers($department_id)
    {
        static $cache = [];

        $department_id = (int) $department_id;
        if ($department_id <= 0) {
            return [];
        }

        if (isset($cache[$department_id])) {
            return $cache[$department_id];
        }

        $approvers = [];

        $role_based = $this->db
            ->select('user_id, name, email, role_id, department_id')
            ->from('users')
            ->where('department_id', $department_id)
            ->where('role_id', 2)
            ->where('status', 'Active')
            ->order_by('name', 'ASC')
            ->get()
            ->result();

        foreach ($role_based as $user) {
            $approvers[(int) $user->user_id] = $user;
        }

        $department = $this->db
            ->select('department_head_id')
            ->from('departments')
            ->where('department_id', $department_id)
            ->get()
            ->row();

        $department_head_id = ($department && !empty($department->department_head_id))
            ? (int) $department->department_head_id
            : 0;

        if ($department_head_id > 0 && !isset($approvers[$department_head_id])) {
            $department_head = $this->db
                ->select('user_id, name, email, role_id, department_id')
                ->from('users')
                ->where('user_id', $department_head_id)
                ->where('status', 'Active')
                ->get()
                ->row();

            if ($department_head) {
                $approvers[(int) $department_head->user_id] = $department_head;
            }
        }

        if (empty($approvers)) {
            $krupal = $this->getKrupalApprover();
            if ($krupal) {
                $approvers[(int) $krupal->user_id] = $krupal;
            }
        }

        $cache[$department_id] = array_values($approvers);
        return $cache[$department_id];
    }

    private function canApproveDelegations($department_id = null)
    {
        if ($this->getCurrentUserId() === $this->getKrupalApproverId()) {
            return true;
        }

        if (!$this->isRole(2)) {
            return false;
        }

        if ($department_id === null) {
            return true;
        }

        foreach ($this->getDepartmentApproverUsers((int) $department_id) as $approver) {
            if ((int) $approver->user_id === $this->getCurrentUserId()) {
                return true;
            }
        }

        return false;
    }

    private function decorateDelegationPermissions(array $delegations)
    {
        $today = date('Y-m-d');

        foreach ($delegations as $delegation) {
            $approver_names = array_map(function ($user) {
                return (string) $user->name;
            }, $this->getDepartmentApproverUsers((int) $delegation->original_department_id));

            $delegation->approval_required_by = !empty($approver_names)
                ? implode(', ', $approver_names)
                : 'Department HOD';
            $delegation->can_manage = $this->canApproveDelegations((int) $delegation->original_department_id);
            $delegation->can_manage_before_start = $delegation->can_manage
                && !empty($delegation->start_date)
                && $delegation->start_date > $today;
        }

        return $delegations;
    }

    private function hasScheduleNotificationBeenSent($receiver_id, $notification_type, $message, $date = null)
    {
        $this->db
            ->from('task_messages')
            ->where('receiver_id', (int) $receiver_id)
            ->where('notification_type', (string) $notification_type)
            ->where('message', (string) $message);

        if (!empty($date)) {
            $this->db->where('DATE(created_at) = ' . $this->db->escape($date), null, false);
        }

        return (bool) $this->db->count_all_results();
    }

    private function notifyScheduleUsers(array $users, $subject, $message, $notification_type = 'schedule_delegation', $date = null)
    {
        $count = 0;
        $seen = [];

        foreach ($users as $user) {
            if (empty($user) || empty($user->user_id)) {
                continue;
            }

            $user_id = (int) $user->user_id;
            if ($user_id <= 0 || isset($seen[$user_id])) {
                continue;
            }

            $seen[$user_id] = true;

            if ($this->hasScheduleNotificationBeenSent($user_id, $notification_type, $message, $date)) {
                continue;
            }

            $this->Schedule_model->createNotification([
                'ticket_id' => null,
                'schedule_task_id' => null,
                'task_id' => null,
                'sender_id' => null,
                'receiver_id' => $user_id,
                'message' => $message,
                'notification_type' => $notification_type,
                'is_read' => 0,
            ]);

            $this->sendScheduleEmail(!empty($user->email) ? $user->email : null, $subject, $message);
            $count++;
        }

        return $count;
    }

    private function notifyDelegationDecision($delegation, $approval_status, $manager_note = '', $auto_approved = false)
    {
        if (!$delegation) {
            return 0;
        }

        $approval_status = strtolower(trim((string) $approval_status));
        $window = $this->formatDelegationWindow($delegation);
        $count = 0;

        $original_user = (object) [
            'user_id' => (int) $delegation->original_user_id,
            'name' => (string) $delegation->original_user_name,
            'email' => !empty($delegation->original_user_email) ? (string) $delegation->original_user_email : null,
        ];
        $delegated_user = (object) [
            'user_id' => (int) $delegation->delegated_user_id,
            'name' => (string) $delegation->delegated_user_name,
            'email' => !empty($delegation->delegated_user_email) ? (string) $delegation->delegated_user_email : null,
        ];
        $creator_user = (object) [
            'user_id' => (int) $delegation->created_by,
            'name' => (string) $delegation->created_by_name,
            'email' => !empty($delegation->created_by_email) ? (string) $delegation->created_by_email : null,
        ];

        if ($approval_status === 'approved') {
            $message = 'Leave delegation for ' . $delegation->original_user_name . ' to ' . $delegation->delegated_user_name . ' (' . $window . ') has been approved.';
            if ($auto_approved) {
                $message .= ' It was auto-approved after 06:00 PM because no HOD decision was recorded in time.';
            }

            $subject = $auto_approved
                ? 'Delegation Auto Approved: ' . $delegation->original_user_name
                : 'Delegation Approved: ' . $delegation->original_user_name;

            $count += $this->notifyScheduleUsers(
                [$original_user, $delegated_user, $creator_user],
                $subject,
                $message,
                $auto_approved ? 'schedule_delegation_auto_approved' : 'schedule_delegation_approved'
            );
        } elseif ($approval_status === 'rejected') {
            $message = 'Leave delegation for ' . $delegation->original_user_name . ' to ' . $delegation->delegated_user_name . ' (' . $window . ') has been rejected.';
            if ($manager_note !== '') {
                $message .= ' Reason: ' . $manager_note;
            }

            $count += $this->notifyScheduleUsers(
                [$original_user, $creator_user],
                'Delegation Rejected: ' . $delegation->original_user_name,
                $message,
                'schedule_delegation_rejected'
            );
        }

        return $count;
    }

    private function notifyDelegationStart($delegation, $date)
    {
        if (!$delegation) {
            return 0;
        }

        $window = $this->formatDelegationWindow($delegation);

        $delegated_user = (object) [
            'user_id' => (int) $delegation->delegated_user_id,
            'name' => (string) $delegation->delegated_user_name,
            'email' => !empty($delegation->delegated_user_email) ? (string) $delegation->delegated_user_email : null,
        ];
        $original_user = (object) [
            'user_id' => (int) $delegation->original_user_id,
            'name' => (string) $delegation->original_user_name,
            'email' => !empty($delegation->original_user_email) ? (string) $delegation->original_user_email : null,
        ];
        $creator_user = (object) [
            'user_id' => (int) $delegation->created_by,
            'name' => (string) $delegation->created_by_name,
            'email' => !empty($delegation->created_by_email) ? (string) $delegation->created_by_email : null,
        ];

        $count = 0;

        $delegate_message = 'Leave delegation for ' . $delegation->original_user_name . ' is active from ' . $window . '. All active schedule tasks for this interval are now assigned to you.';
        $count += $this->notifyScheduleUsers(
            [$delegated_user],
            'Delegation Active: ' . $delegation->original_user_name . ' -> ' . $delegation->delegated_user_name,
            $delegate_message,
            'schedule_delegation_start',
            $date
        );

        $owner_message = 'Your leave delegation to ' . $delegation->delegated_user_name . ' is active for ' . $window . '. Active schedule tasks will be handled by ' . $delegation->delegated_user_name . ' during this interval.';
        $count += $this->notifyScheduleUsers(
            [$original_user, $creator_user],
            'Delegation Started: ' . $delegation->original_user_name,
            $owner_message,
            'schedule_delegation_start',
            $date
        );

        return $count;
    }

    private function notifyDelegationApprovalReminder($delegation, array $approvers, $date)
    {
        if (!$delegation || empty($approvers)) {
            return 0;
        }

        $window = $this->formatDelegationWindow($delegation);
        $message = 'Delegation request for ' . $delegation->original_user_name . ' to ' . $delegation->delegated_user_name . ' (' . $window . ') is still pending. Please approve or reject it before 06:00 PM.';

        return $this->notifyScheduleUsers(
            $approvers,
            'Delegation Approval Pending: ' . $delegation->original_user_name,
            $message,
            'schedule_delegation_approval',
            $date
        );
    }

    private function notifyDelegationRequestCreated($delegation)
    {
        if (!$delegation) {
            return 0;
        }

        $window = $this->formatDelegationWindow($delegation);
        $message = 'New delegation request for ' . $delegation->original_user_name . ' to ' . $delegation->delegated_user_name . ' (' . $window . ') is awaiting approval.';

        return $this->notifyScheduleUsers(
            $this->getDepartmentApproverUsers((int) $delegation->original_department_id),
            'New Delegation Request: ' . $delegation->original_user_name,
            $message,
            'schedule_delegation_requested'
        );
    }

    private function processDelegationQueueInternal($run_date = null, $run_time = null)
    {
        $run_date = $run_date ?: date('Y-m-d');
        $run_time = $run_time ?: date('H:i:s');
        $target_start_date = date('Y-m-d', strtotime($run_date . ' +1 day'));

        $summary = [
            'approval_reminders' => 0,
            'auto_approved' => 0,
            'start_notices' => 0,
        ];

        $pending_delegations = $this->Schedule_model->getPendingDelegationsForApprovalWindow($run_date, $target_start_date);
        $approveDelegations = function (array $delegations, $manager_note, $notifyStartImmediately = false) use (&$summary, $run_date) {
            foreach ($delegations as $delegation) {
                if (!$delegation || strtolower((string) $delegation->approval_status) !== 'pending') {
                    continue;
                }

                $remark_parts = $this->extractDelegationRemarkParts($delegation->approval_remarks);
                $approved_at = date('Y-m-d H:i:s');

                $updated = $this->Schedule_model->updateDelegationWorkflow((int) $delegation->id, [
                    'approval_status' => 'approved',
                    'approved_by' => null,
                    'approved_at' => $approved_at,
                    'approval_remarks' => $this->buildDelegationRemarks(
                        $remark_parts['request_reason'],
                        $manager_note,
                        $remark_parts['task_id'],
                        $remark_parts['task_title']
                    ),
                ]);

                if (!$updated) {
                    continue;
                }

                $delegation->approval_status = 'approved';
                $delegation->approved_at = $approved_at;
                $summary['auto_approved']++;

                $this->notifyDelegationDecision($delegation, 'approved', $manager_note, true);

                if ($notifyStartImmediately && !empty($delegation->start_date) && !empty($delegation->end_date)) {
                    if ($delegation->start_date <= $run_date && $delegation->end_date >= $run_date) {
                        $summary['start_notices'] += $this->notifyDelegationStart($delegation, $run_date);
                    }
                }
            }
        };

        $overdue_delegations = array_values(array_filter($pending_delegations, function ($delegation) use ($run_date) {
            return !empty($delegation->start_date) && $delegation->start_date < $run_date;
        }));

        if (!empty($overdue_delegations)) {
            $approveDelegations(
                $overdue_delegations,
                'Auto-approved because the leave window already started without HOD response.',
                true
            );
        }

        $pending_delegations = array_values(array_filter($pending_delegations, function ($delegation) {
            return strtolower((string) $delegation->approval_status) === 'pending';
        }));

        if ($run_time >= '17:00:00' && $run_time < '18:00:00') {
            foreach ($pending_delegations as $delegation) {
                $summary['approval_reminders'] += $this->notifyDelegationApprovalReminder(
                    $delegation,
                    $this->getDepartmentApproverUsers((int) $delegation->original_department_id),
                    $run_date
                );
            }
        }

        if ($run_time >= '18:00:00') {
            $approveDelegations(
                $pending_delegations,
                'Auto-approved after 06:00 PM due to no HOD response.',
                false
            );
        }

        foreach ($this->Schedule_model->getApprovedDelegationsStartingOn($run_date) as $delegation) {
            $summary['start_notices'] += $this->notifyDelegationStart($delegation, $run_date);
        }

        return $summary;
    }

    public function __construct() {
        parent::__construct();
        $this->load->model('Schedule_model');
        $this->load->model('User_model');
    }

    private function syncDelegationsForCurrentRequest()
    {
        $cacheKey = 'schedule_delegation_sync_at';
        $now = time();
        $lastRun = (int) $this->session->userdata($cacheKey);

        if ($lastRun > 0 && ($now - $lastRun) < 180) {
            return [
                'approval_reminders' => 0,
                'auto_approved' => 0,
                'start_notices' => 0,
                'skipped' => true,
            ];
        }

        $this->session->set_userdata($cacheKey, $now);

        return $this->processDelegationQueueInternal(date('Y-m-d'), date('H:i:s'));
    }

    private function getSubordinateIds()
    {
        return array_values(array_unique(array_map('intval', $this->User_model->getAllSubordinates($this->getCurrentUserId()))));
    }

    private function getScopeUserIds()
    {
        return array_values(array_unique(array_merge([$this->getCurrentUserId()], $this->getSubordinateIds())));
    }

    private function getViewScopeUserIds()
    {
        return $this->getVisibleUserIdsByHierarchy();
    }

    private function getScheduleDepartmentFilterId()
    {
        return $this->canViewAcrossDepartments() ? null : $this->getCurrentDepartmentId();
    }

    private function canManageSchedule($schedule)
    {
        if (!$schedule) {
            return false;
        }

        $currentUserId = $this->getCurrentUserId();

        if ($currentUserId > 0 && $currentUserId === $this->getKrupalApproverId()) {
            return true;
        }

        return in_array($currentUserId, [
            (int) $schedule->assigned_user_id,
            (int) $schedule->created_by,
        ], true);
    }

    private function decorateSchedulePermissions(array $schedules)
    {
        foreach ($schedules as $schedule) {
            $schedule->can_manage = $this->canManageSchedule($schedule);
            $schedule->is_read_only = !$schedule->can_manage;
        }

        return $schedules;
    }

    private function resolveAssignedUserId($assign_type, $posted_assigned_user_id, array $subordinate_ids)
    {
        if ($assign_type === 'self') {
            return $this->getCurrentUserId();
        }

        if ($assign_type === 'subordinate' && in_array((int) $posted_assigned_user_id, $subordinate_ids, true)) {
            return (int) $posted_assigned_user_id;
        }

        return null;
    }

    private function getRoleLabel()
    {
        if ($this->isRole(2) && $this->isDepartment(2)) {
            return 'IT Head';
        }

        if ($this->isRole(2)) {
            return 'HOD';
        }

        if ($this->isRole(1) && $this->isDepartment(2)) {
            return 'Developer';
        }

        return 'Department User';
    }

    private function getManagedUser($user_id)
    {
        if ($user_id <= 0) {
            return null;
        }

        $scope_user_ids = $this->getViewScopeUserIds();
        if (!in_array((int) $user_id, $scope_user_ids, true)) {
            return null;
        }

        return $this->db
            ->select('user_id, name, department_id, role_id, email')
            ->from('users')
            ->where('user_id', (int) $user_id)
            ->get()
            ->row();
    }

    private function getDelegationCandidates($department_id, $exclude_user_id)
    {
        if ((int) $department_id <= 0) {
            return [];
        }

        return $this->Schedule_model->getDepartmentPeers((int) $department_id, (int) $exclude_user_id);
    }

    private function getAccessibleDelegation($delegation_id)
    {
        $delegation = $this->Schedule_model->getDelegationById($delegation_id);
        if (!$delegation) {
            return null;
        }

        $scopeUserIds = $this->getViewScopeUserIds();
        $currentUserId = $this->getCurrentUserId();

        if (
            $this->canApproveDelegations() ||
            (int) $delegation->created_by === $currentUserId ||
            (int) $delegation->original_user_id === $currentUserId ||
            (int) $delegation->delegated_user_id === $currentUserId ||
            in_array((int) $delegation->original_user_id, $scopeUserIds, true)
        ) {
            return $delegation;
        }

        return null;
    }

    private function canModifyDelegationRequest($delegation)
    {
        if (!$delegation) {
            return false;
        }

        $today = date('Y-m-d');
        $startsAt = $delegation->start_date;

        return (int) $delegation->created_by === $this->getCurrentUserId()
            && (string) $delegation->approval_status === 'pending'
            && !empty($startsAt)
            && $startsAt > $today;
    }

    private function getAccessibleSchedule($schedule_id)
    {
        $schedule = $this->Schedule_model->getScheduleById($schedule_id);
        if (!$schedule) {
            return null;
        }

        $scopeUserIds = $this->getViewScopeUserIds();
        $currentUserId = $this->getCurrentUserId();

        if (
            (int) $schedule->created_by !== $currentUserId &&
            (int) $schedule->assigned_user_id !== $currentUserId &&
            !in_array((int) $schedule->assigned_user_id, $scopeUserIds, true)
        ) {
            return null;
        }

        return $schedule;
    }

    private function sendScheduleEmail($to, $subject, $message)
    {
        if (empty($to)) {
            return false;
        }

        $this->email->clear(true);
        $this->email->from('patelniket972@gmail.com', 'TRS');
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        return (bool) $this->email->send();
    }

    // Schedule main page
    public function index()
    {
        $this->syncDelegationsForCurrentRequest();

        $user_id = $this->getCurrentUserId();
        $subordinate_ids = $this->getSubordinateIds();
        $scope_user_ids = $this->getScopeUserIds();
        $view_scope_user_ids = $this->getViewScopeUserIds();
        $assignable_users = $this->Schedule_model->getAssignableUsers($subordinate_ids);
        $scope_users = $this->Schedule_model->getScopeUsers($view_scope_user_ids);
        $selected_view_user_key = trim((string) $this->input->get('schedule_user_id'));
        $selected_view_user_id = strtolower($selected_view_user_key) === 'all' ? 0 : (int) $selected_view_user_key;
        $schedule_task_view = $this->input->get('schedule_task_view');
        if (!in_array($schedule_task_view, ['today', 'all'], true)) {
            $schedule_task_view = 'today';
        }

        $schedule_task_priority = trim((string) $this->input->get('schedule_task_priority'));
        $allowedPriorities = ['high', 'medium', 'low', 'all'];
        if (!in_array($schedule_task_priority, $allowedPriorities, true)) {
            $schedule_task_priority = 'all';
        }

        if ($selected_view_user_id !== 0 && !in_array($selected_view_user_id, $view_scope_user_ids, true)) {
            $selected_view_user_id = 0;
        }

        $this->setPageAssets(
            ['assets/dist/css/pages/schedule.css'],
            ['assets/dist/js/pages/schedule.js']
        );

        $delegations = $this->decorateDelegationPermissions(
            $this->Schedule_model->getDelegationsForUsers($view_scope_user_ids)
        );
        $approved_delegations = array_filter($delegations, function ($delegation) {
            return isset($delegation->approval_status) && strtolower($delegation->approval_status) === 'approved';
        });
        $manageable_delegations = array_filter($delegations, function ($delegation) {
            return !empty($delegation->can_manage);
        });

        $today_tasks = $this->Schedule_model->getTodayTasksForScope(
            $user_id,
            $view_scope_user_ids,
            $selected_view_user_id,
            date('Y-m-d'),
            $this->getScheduleDepartmentFilterId(),
            $schedule_task_view,
            $schedule_task_priority
        );

        $this->render('Schedule/schedule_list', [
            'users' => $assignable_users,
            'scope_users' => $scope_users,
            'delegation_scope_users' => $this->Schedule_model->getScopeUsers($this->getOwnDepartmentUserIds()),
            'can_assign_user' => !empty($assignable_users),
            'can_approve_delegations' => !empty($manageable_delegations),
            'current_user_id' => $user_id,
            'role_label' => $this->getRoleLabel(),
            'subordinate_count' => count($assignable_users),
            'selected_view_user_id' => $selected_view_user_id,
            'schedule_task_view' => $schedule_task_view,
            'schedule_task_priority' => $schedule_task_priority,
            'today_tasks' => $today_tasks,
            'delegations' => $delegations,
            'approved_delegation_count' => count($approved_delegations),
            'delegation_candidates' => $this->getDelegationCandidates($this->getCurrentDepartmentId(), $this->getCurrentUserId()),
            'delegation_tasks' => $this->Schedule_model->getActiveScheduleTasksForUser($user_id, $this->getCurrentDepartmentId()),
            'delegation_approval_caption' => 'Department HOD / IT HOD approval',
            'krupal_approver' => $this->getKrupalApprover(),
            'krupal_approver_id' => $this->getKrupalApproverId(),
            'schedules' => $this->decorateSchedulePermissions($this->Schedule_model->getVisibleSchedules($view_scope_user_ids)),
        ]);
    }

    public function ajax_today_task_board()
    {
        $this->syncDelegationsForCurrentRequest();

        $user_id = $this->getCurrentUserId();
        $scope_user_ids = $this->getViewScopeUserIds();
        $selected_view_user_key = trim((string) $this->input->get('schedule_user_id'));
        $selected_view_user_id = strtolower($selected_view_user_key) === 'all' ? 0 : (int) $selected_view_user_key;

        if ($selected_view_user_id !== 0 && !in_array($selected_view_user_id, $scope_user_ids, true)) {
            $selected_view_user_id = 0;
        }

        $task_view = $this->input->get('schedule_task_view');
        if (!in_array($task_view, ['today','all'], true)) {
            $task_view = 'today';
        }
        $task_priority = trim((string) $this->input->get('schedule_task_priority'));
        $allowed_priorities = ['high', 'medium', 'low', 'all'];
        if (!in_array($task_priority, $allowed_priorities, true)) {
            $task_priority = 'all';
        }
        $data['today_tasks'] = $this->Schedule_model->getTodayTasksForScope($user_id, $scope_user_ids, $selected_view_user_id, date('Y-m-d'), $this->getScheduleDepartmentFilterId(), $task_view, $task_priority);
        $data['current_user_id'] = $user_id;
        $data['krupal_approver_id'] = $this->getKrupalApproverId();

        $html = $this->load->view('Schedule/today_task_rows_timer', $data, true);

        $status_counts = ['completed' => 0, 'pending' => 0, 'overdue' => 0];
        foreach ($data['today_tasks'] as $task) {
            $status = strtolower((string) $task->log_status);
            if (!isset($status_counts[$status])) {
                $status_counts[$status] = 0;
            }
            $status_counts[$status]++;
        }

        echo json_encode([
            'status' => 'success',
            'html' => $html,
            'status_counts' => $status_counts,
        ]);
    }

    public function ajax_overdue_task_alert()
    {
        $this->syncDelegationsForCurrentRequest();

        $user_id = $this->getCurrentUserId();
        if ($user_id <= 0) {
            echo json_encode([
                'status' => 'error',
                'has_overdue' => false,
                'message' => 'Unauthorized',
            ]);
            return;
        }

        $tasks = $this->Schedule_model->getTodayTasksForScope(
            $user_id,
            $this->getViewScopeUserIds(),
            0,
            date('Y-m-d'),
            $this->getScheduleDepartmentFilterId(),
            'today',
            'all'
        );

        $overdueTask = null;
        foreach ($tasks as $task) {
            if (
                strtolower((string) ($task->log_status ?? 'pending')) === 'overdue'
                && (int) ($task->effective_user_id ?? 0) === $user_id
            ) {
                $overdueTask = $task;
                break;
            }
        }

        if (!$overdueTask) {
            echo json_encode([
                'status' => 'success',
                'has_overdue' => false,
            ]);
            return;
        }

        $taskTimeLabel = !empty($overdueTask->task_time)
            ? date('h:i A', strtotime($overdueTask->task_time))
            : '-';

        $message = (string) ($overdueTask->schedule_name ?? 'This task') . ' is overdue';
        if ($taskTimeLabel !== '-') {
            $message .= ' (due ' . $taskTimeLabel . ')';
        }
        $message .= '. Please complete this task.';

        echo json_encode([
            'status' => 'success',
            'has_overdue' => true,
            'task_id' => (int) ($overdueTask->id ?? 0),
            'schedule_name' => (string) ($overdueTask->schedule_name ?? ''),
            'task_time' => $taskTimeLabel,
            'message' => $message,
        ]);
    }

    private function getTimerScheduleTask($schedule_task_id, $date)
    {
        $this->syncDelegationsForCurrentRequest();

        $user_id = $this->getCurrentUserId();
        $scope_user_ids = $this->getViewScopeUserIds();

        $tasks = $this->Schedule_model->getTodayTasksForScope(
            $user_id,
            $scope_user_ids,
            0,
            $date,
            $this->getScheduleDepartmentFilterId(),
            'all',
            'all'
        );

        foreach ($tasks as $task) {
            if ((int) $task->id === (int) $schedule_task_id) {
                return $task;
            }
        }

        return null;
    }

    public function ajax_get_user_schedule_tasks()
    {
        $user_id = (int) $this->input->get('user_id');
        $scope_user_ids = $this->getViewScopeUserIds();

        if (!in_array($user_id, $scope_user_ids, true)) {
            echo json_encode(['status' => 'error', 'message' => 'User not in scope']);
            return;
        }

        $tasks = $this->Schedule_model->getActiveScheduleTasksForUser($user_id, $this->getCurrentDepartmentId());
        echo json_encode(['status' => 'success', 'tasks' => $tasks]);
    }

    public function ajax_save_schedule()
   {
        $user_id = $this->getCurrentUserId();
        $subordinate_ids = $this->getSubordinateIds();
        $assign_type = trim((string) $this->input->post('assign_type', true));
        $posted_assigned_user_id = (int) $this->input->post('assigned_user_id');
        $assigned_user_id = $this->resolveAssignedUserId($assign_type, $posted_assigned_user_id, $subordinate_ids);

        if ($assigned_user_id === null) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please select a valid assignment option.'
            ]);
            return;
        }

        $data = [
            'schedule_name' => trim((string) $this->input->post('schedule_name', true)),
            'description' => trim((string) $this->input->post('description', true)),
            'assigned_user_id' => $assigned_user_id,
            'frequency' => trim((string) $this->input->post('frequency', true)),
            'start_date' => $this->input->post('start_date', true) ?: date('Y-m-d'),
            'task_time' => $this->input->post('task_time', true),
            'reminder_time' => $this->input->post('reminder_time', true),
            'priority' => trim((string) $this->input->post('priority', true)),
            'created_by' => $user_id
        ];

        if (empty($data['reminder_time']) && !empty($data['task_time'])) {
            $data['reminder_time'] = date('H:i:s', strtotime($data['task_time'] . ' -30 minutes'));
        }

        if ($data['schedule_name'] === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Schedule name is required.'
            ]);
            return;
        }

        $insert = $this->Schedule_model->createSchedule($data);

        if ($insert) {
            echo json_encode(['status' => 'success']);
            return;
        }

        echo json_encode([
            'status' => 'error',
            'message' => 'Error saving schedule.'
        ]);
    }

    public function ajax_get_schedule()
    {
        $schedule_id = (int) $this->input->post('schedule_id');
        $schedule = $this->getAccessibleSchedule($schedule_id);

        if (!$schedule) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Schedule not found or unauthorized.'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $schedule
        ]);
    }

    public function ajax_update_schedule()
    {
        $schedule_id = (int) $this->input->post('schedule_id');
        $schedule = $this->getAccessibleSchedule($schedule_id);

        if (!$schedule || !$this->canManageSchedule($schedule)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only the assigned user, creator, or Krupal sir can update this schedule.'
            ]);
            return;
        }

        $data = [
            'schedule_name' => trim((string) $this->input->post('schedule_name', true)),
            'description' => trim((string) $this->input->post('description', true)),
            'frequency' => trim((string) $this->input->post('frequency', true)),
            'start_date' => $this->input->post('start_date', true),
            'task_time' => $this->input->post('task_time', true),
            'reminder_time' => $this->input->post('reminder_time', true),
            'priority' => trim((string) $this->input->post('priority', true)),
            'status' => trim((string) $this->input->post('status', true)),
        ];

        if (empty($data['reminder_time']) && !empty($data['task_time'])) {
            $data['reminder_time'] = date('H:i:s', strtotime($data['task_time'] . ' -30 minutes'));
        }

        if ($data['schedule_name'] === '') {
            echo json_encode(['status' => 'error', 'message' => 'Schedule name is required.']);
            return;
        }

        if ($data['start_date'] === '') {
            echo json_encode(['status' => 'error', 'message' => 'Start date is required.']);
            return;
        }

        if (!in_array($data['frequency'], ['daily', 'weekly', 'monthly', 'once'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid frequency selected.']);
            return;
        }

        if (!in_array($data['priority'], ['low', 'medium', 'high'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid priority selected.']);
            return;
        }

        if (!in_array($data['status'], ['active', 'inactive'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status selected.']);
            return;
        }

        $updated = $this->Schedule_model->updateSchedule($schedule_id, $data);

        echo json_encode([
            'status' => $updated ? 'success' : 'error',
            'message' => $updated ? 'Schedule updated successfully.' : 'Unable to update schedule.'
        ]);
    }

    public function ajax_delete_schedule()
    {
        $schedule_id = (int) $this->input->post('schedule_id');
        $schedule = $this->getAccessibleSchedule($schedule_id);

        if (!$schedule || !$this->canManageSchedule($schedule)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only the assigned user, creator, or Krupal sir can delete this schedule.'
            ]);
            return;
        }

        $deleted = $this->Schedule_model->deleteSchedule($schedule_id);

        echo json_encode([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Schedule deleted successfully.' : 'Unable to delete schedule.'
        ]);
    }

    public function ajax_save_delegation()
    {
        $original_user_id = (int) $this->input->post('original_user_id');
        $delegated_user_id = (int) $this->input->post('delegated_user_id');
        $delegation_task_id = (int) $this->input->post('delegation_task_id');
        $start_date = $this->input->post('start_date', true);
        $end_date = $this->input->post('end_date', true);
        $request_reason = trim((string) $this->input->post('request_reason', true));

        $managed_user = $this->getManagedUser($original_user_id);
        if (!$managed_user && $original_user_id !== $this->getCurrentUserId()) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid original user.']);
            return;
        }

        if ($original_user_id === $this->getCurrentUserId()) {
            $managed_user = $this->db
                ->select('user_id, name, department_id, role_id, email')
                ->from('users')
                ->where('user_id', $original_user_id)
                ->get()
                ->row();
        }

        if (!$managed_user || $delegated_user_id <= 0 || $delegated_user_id === $original_user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Please choose a valid delegate user.']);
            return;
        }

        if ($request_reason === '') {
            echo json_encode(['status' => 'error', 'message' => 'Reason is required for delegation request.']);
            return;
        }

        $taskTitle = '';
        if ($delegation_task_id > 0) {
            $task = $this->Schedule_model->getScheduleById($delegation_task_id);
            if (!$task || (int) $task->assigned_user_id !== $original_user_id) {
                echo json_encode(['status' => 'error', 'message' => 'Selected task is invalid or not owned by chosen user.']);
                return;
            }
            $taskTitle = $task->schedule_name;
        }

        if (empty($start_date) || empty($end_date) || $start_date > $end_date) {
            echo json_encode(['status' => 'error', 'message' => 'Please choose valid leave dates.']);
            return;
        }

        if ($start_date < date('Y-m-d')) {
            echo json_encode(['status' => 'error', 'message' => 'Leave delegation cannot start in the past.']);
            return;
        }

        $allowed_delegate_ids = array_map(function ($user) {
            return (int) $user->user_id;
        }, $this->getDelegationCandidates((int) $managed_user->department_id, (int) $original_user_id));

        if (!in_array($delegated_user_id, $allowed_delegate_ids, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Delegate user must belong to the same department.']);
            return;
        }

        $insert = $this->Schedule_model->createDelegation([
            'original_user_id' => $original_user_id,
            'delegated_user_id' => $delegated_user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'created_by' => $this->getCurrentUserId(),
            'approval_status' => 'pending',
            'approval_remarks' => $this->buildDelegationRemarks($request_reason, '', $delegation_task_id, $taskTitle),
        ]);

        if ($insert) {
            $saved_delegation = $this->Schedule_model->getDelegationById((int) $this->db->insert_id());
            if ($saved_delegation) {
                $this->notifyDelegationRequestCreated($saved_delegation);
            }
        }

        echo json_encode([
            'status' => $insert ? 'success' : 'error',
            'message' => $insert ? 'Delegation request submitted for department HOD approval.' : 'Unable to save delegation.'
        ]);
    }

    public function ajax_get_delegation_detail()
    {
        $delegation_id = (int) $this->input->post('delegation_id');
        $delegation = $this->getAccessibleDelegation($delegation_id);

        if (!$delegation) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation request not found or unauthorized.']);
            return;
        }

        $remarkParts = $this->extractDelegationRemarkParts($delegation->approval_remarks);

        $today = date('Y-m-d');
        $effectiveStatus = $delegation->approval_status;

        $canManage = $this->canApproveDelegations((int) $delegation->original_department_id)
            && !empty($delegation->start_date)
            && $delegation->start_date > $today;

        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => (int) $delegation->id,
                'original_user_name' => (string) $delegation->original_user_name,
                'delegated_user_name' => (string) $delegation->delegated_user_name,
                'created_by_name' => (string) $delegation->created_by_name,
                'approved_by_name' => (string) $delegation->approved_by_name,
                'start_date' => (string) $delegation->start_date,
                'end_date' => (string) $delegation->end_date,
                'approval_status' => (string) $delegation->approval_status,
                'effective_status' => $effectiveStatus,
                'request_reason' => (string) $remarkParts['request_reason'],
                'manager_note' => (string) $remarkParts['manager_note'],
                'delegation_task_id' => !empty($remarkParts['task_id']) ? (int) $remarkParts['task_id'] : null,
                'delegation_task_title' => (string) $remarkParts['task_title'],
                'approved_at' => (string) $delegation->approved_at,
                'can_manage' => $canManage,
                'can_edit_request' => $this->canModifyDelegationRequest($delegation),
            ],
        ]);
    }

    public function ajax_update_delegation_status()
    {
        $delegation_id = (int) $this->input->post('delegation_id');
        $approval_status = trim((string) $this->input->post('approval_status', true));
        $approval_remarks = trim((string) $this->input->post('approval_remarks', true));

        if (!in_array($approval_status, ['approved', 'rejected'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid approval action.']);
            return;
        }

        if ($approval_status === 'rejected' && $approval_remarks === '') {
            echo json_encode(['status' => 'error', 'message' => 'Rejection reason is required.']);
            return;
        }

        $delegation = $this->Schedule_model->getDelegationById($delegation_id);
        if (!$delegation) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation request not found.']);
            return;
        }

        if (!$this->canApproveDelegations((int) $delegation->original_department_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Only the respective HOD can approve this delegation request.']);
            return;
        }

        $today = date('Y-m-d');
        if (!empty($delegation->start_date) && $delegation->start_date <= $today) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation cannot be modified after start date.']);
            return;
        }

        if ($delegation->approval_status !== 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Delegation request has already been processed.']);
            return;
        }

        $remarkParts = $this->extractDelegationRemarkParts($delegation->approval_remarks);

        $updated = $this->Schedule_model->updateDelegationApproval(
            $delegation_id,
            $approval_status,
            $this->getCurrentUserId(),
            $this->buildDelegationRemarks($remarkParts['request_reason'], $approval_remarks, $remarkParts['task_id'], $remarkParts['task_title'])
        );

        if ($updated) {
            $delegation->approval_status = $approval_status;
            $delegation->approved_by = $this->getCurrentUserId();
            $delegation->approved_at = date('Y-m-d H:i:s');
            $this->notifyDelegationDecision($delegation, $approval_status, $approval_remarks, false);
        }

        echo json_encode([
            'status' => $updated ? 'success' : 'error',
            'message' => $updated
                ? ($approval_status === 'approved' ? 'Delegation approved successfully.' : 'Delegation rejected successfully.')
                : 'Unable to update delegation request.'
        ]);
    }

    public function ajax_manage_delegation()
    {
        $delegation_id = (int) $this->input->post('delegation_id');
        $target_status = trim((string) $this->input->post('approval_status', true));
        $remarks = trim((string) $this->input->post('approval_remarks', true));

        if (!in_array($target_status, ['approved', 'rejected', 'pending'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid delegation action.']);
            return;
        }

        if ($target_status === 'rejected' && $remarks === '') {
            echo json_encode(['status' => 'error', 'message' => 'Reason is required for rejection.']);
            return;
        }

        $delegation = $this->Schedule_model->getDelegationById($delegation_id);
        if (!$delegation) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation request not found.']);
            return;
        }

        if (!$this->canApproveDelegations((int) $delegation->original_department_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Only the respective HOD can manage this delegation request.']);
            return;
        }

        $today = date('Y-m-d');
        if (!empty($delegation->start_date) && $delegation->start_date <= $today) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation cannot be modified after start date.']);
            return;
        }

        if ($target_status === $delegation->approval_status) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation is already in the selected state.']);
            return;
        }

        $remarkParts = $this->extractDelegationRemarkParts($delegation->approval_remarks);

        $updateData = [
            'approval_status' => $target_status,
            'approval_remarks' => $this->buildDelegationRemarks($remarkParts['request_reason'], $remarks, $remarkParts['task_id'], $remarkParts['task_title']),
        ];

        if ($target_status === 'pending') {
            $updateData['approved_by'] = null;
            $updateData['approved_at'] = null;
        } else {
            $updateData['approved_by'] = $this->getCurrentUserId();
            $updateData['approved_at'] = date('Y-m-d H:i:s');
        }

        $updated = $this->Schedule_model->updateDelegationWorkflow($delegation_id, $updateData);

        if ($updated && in_array($target_status, ['approved', 'rejected'], true)) {
            $delegation->approval_status = $target_status;
            $delegation->approved_by = $target_status === 'pending' ? null : $this->getCurrentUserId();
            $delegation->approved_at = $target_status === 'pending' ? null : ($updateData['approved_at'] ?? date('Y-m-d H:i:s'));
            $this->notifyDelegationDecision($delegation, $target_status, $remarks, false);
        }

        echo json_encode([
            'status' => $updated ? 'success' : 'error',
            'message' => $updated ? 'Delegation updated successfully.' : 'Unable to update delegation request.',
        ]);
    }

    public function ajax_update_delegation_request()
    {
        $delegation_id = (int) $this->input->post('delegation_id');
        $delegation = $this->getAccessibleDelegation($delegation_id);

        if (!$delegation || !$this->canModifyDelegationRequest($delegation)) {
            echo json_encode(['status' => 'error', 'message' => 'Only requester can edit a pending delegation request before start date.']);
            return;
        }

        $delegated_user_id = (int) $this->input->post('delegated_user_id');
        $delegation_task_id = (int) $this->input->post('delegation_task_id');
        $start_date = $this->input->post('start_date', true);
        $end_date = $this->input->post('end_date', true);
        $request_reason = trim((string) $this->input->post('request_reason', true));

        if ($request_reason === '') {
            echo json_encode(['status' => 'error', 'message' => 'Reason is required for delegation request.']);
            return;
        }

        if ($delegation_task_id > 0) {
            $task = $this->Schedule_model->getScheduleById($delegation_task_id);
            if (!$task || (int) $task->assigned_user_id !== (int) $delegation->original_user_id) {
                echo json_encode(['status' => 'error', 'message' => 'Selected task is invalid or not owned by chosen user.']);
                return;
            }
            $taskTitle = $task->schedule_name;
        } else {
            $taskTitle = '';
        }

        if (empty($start_date) || empty($end_date) || $start_date > $end_date) {
            echo json_encode(['status' => 'error', 'message' => 'Please choose valid leave dates.']);
            return;
        }

        if ($start_date < date('Y-m-d')) {
            echo json_encode(['status' => 'error', 'message' => 'Leave delegation cannot start in the past.']);
            return;
        }

        $allowed_delegate_ids = array_map(function ($user) {
            return (int) $user->user_id;
        }, $this->getDelegationCandidates((int) $delegation->original_department_id, (int) $delegation->original_user_id));

        if (!in_array($delegated_user_id, $allowed_delegate_ids, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Delegate user must belong to the same department.']);
            return;
        }

        $updated = $this->Schedule_model->updateDelegationWorkflow($delegation_id, [
            'delegated_user_id' => $delegated_user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'approval_remarks' => $this->buildDelegationRemarks($request_reason, '', $delegation_task_id, $taskTitle),
            'approved_by' => null,
            'approved_at' => null,
        ]);

        echo json_encode([
            'status' => $updated ? 'success' : 'error',
            'message' => $updated ? 'Delegation request updated successfully.' : 'Unable to update delegation request.'
        ]);
    }

    public function ajax_delete_delegation_request()
    {
        $delegation_id = (int) $this->input->post('delegation_id');
        $delegation = $this->getAccessibleDelegation($delegation_id);

        if (!$delegation || !$this->canModifyDelegationRequest($delegation)) {
            echo json_encode(['status' => 'error', 'message' => 'Only requester can delete a pending delegation request before start date.']);
            return;
        }

        $deleted = $this->Schedule_model->deleteDelegation($delegation_id);

        echo json_encode([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Delegation request deleted successfully.' : 'Unable to delete delegation request.'
        ]);
    }

    public function ajax_complete_today_task()
    {
        $this->syncDelegationsForCurrentRequest();

        $schedule_task_id = (int) $this->input->post('schedule_task_id');
        $date = $this->input->post('execution_date', true) ?: date('Y-m-d');

        $scope_user_ids = $this->getViewScopeUserIds();
        $tasks = $this->Schedule_model->getTodayTasksForScope(
            $this->getCurrentUserId(),
            $scope_user_ids,
            0,
            $date,
            $this->getScheduleDepartmentFilterId()
        );

        $target = null;
        foreach ($tasks as $task) {
            if ((int) $task->id === $schedule_task_id) {
                $target = $task;
                break;
            }
        }

        if (!$target) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found for completion.']);
            return;
        }

        if ((int) $target->effective_user_id !== $this->getCurrentUserId()) {
            echo json_encode(['status' => 'error', 'message' => 'Only the assigned user can complete this task.']);
            return;
        }

        $ok = $this->Schedule_model->markTaskComplete(
            $schedule_task_id,
            $date,
            $this->getCurrentUserId(),
            $target->effective_user_id,
            $target->delegated_from_user_id
        );

        if ($ok) {
            $this->Schedule_model->markScheduleNotificationsRead($schedule_task_id, $this->getCurrentUserId());
            $this->load->model('Timer_model');
            $this->Timer_model->stopScheduleTaskTimer($schedule_task_id, $date, $this->getCurrentUserId());
        }

        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Task marked completed.' : 'Unable to update task.'
        ]);
    }

    public function timer_task_status()
    {
        $schedule_task_id = (int) $this->input->get('schedule_task_id');
        $date = $this->input->get('execution_date', true) ?: date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        if ($schedule_task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid task.']);
            return;
        }

        $this->load->model('Timer_model');

        $target = $this->getTimerScheduleTask($schedule_task_id, $date);
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
            return;
        }

        if (strtolower((string) ($target->frequency ?? '')) !== 'once') {
            echo json_encode(['success' => false, 'message' => 'Timer is available only for once schedules.']);
            return;
        }

        $canControl = ((int) $target->effective_user_id === $this->getCurrentUserId());
        $locked = ($target->log_status === 'completed');

        if ($locked) {
            $this->Timer_model->stopScheduleTaskTimer($schedule_task_id, $date, $this->getCurrentUserId());
        }

        $timer = $this->Timer_model->getScheduleTaskTimer($schedule_task_id, $date);
        $isRunning = !empty($timer['is_running']);
        $totalSeconds = $this->Timer_model->getScheduleTaskTimerTotalSeconds($timer);

        echo json_encode([
            'success' => true,
            'schedule_task_id' => $schedule_task_id,
            'total_seconds' => $totalSeconds,
            'is_running' => $isRunning ? 1 : 0,
            'locked' => $locked ? 1 : 0,
            'can_start' => ($canControl && !$locked && !$isRunning),
            'can_pause' => ($canControl && !$locked && $isRunning)
        ]);
    }

    public function timer_task_start()
    {
        $schedule_task_id = (int) $this->input->post('schedule_task_id');
        $date = $this->input->post('execution_date', true) ?: date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        if ($schedule_task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid task.']);
            return;
        }

        $this->load->model('Timer_model');

        $target = $this->getTimerScheduleTask($schedule_task_id, $date);
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
            return;
        }

        if (strtolower((string) ($target->frequency ?? '')) !== 'once') {
            echo json_encode(['success' => false, 'message' => 'Timer is available only for once schedules.']);
            return;
        }

        if ((int) $target->effective_user_id !== $this->getCurrentUserId()) {
            echo json_encode(['success' => false, 'message' => 'Only the assigned user can start this timer.']);
            return;
        }

        if ($target->log_status === 'completed') {
            $this->Timer_model->stopScheduleTaskTimer($schedule_task_id, $date, $this->getCurrentUserId());
            echo json_encode(['success' => false, 'message' => 'Completed tasks cannot be timed.']);
            return;
        }

        $timer = $this->Timer_model->startScheduleTaskTimer($schedule_task_id, $date, $this->getCurrentUserId());
        $totalSeconds = $this->Timer_model->getScheduleTaskTimerTotalSeconds($timer);

        echo json_encode([
            'success' => true,
            'schedule_task_id' => $schedule_task_id,
            'total_seconds' => $totalSeconds,
            'is_running' => 1,
            'locked' => 0,
            'can_start' => 0,
            'can_pause' => 1
        ]);
    }

    public function timer_task_pause()
    {
        $schedule_task_id = (int) $this->input->post('schedule_task_id');
        $date = $this->input->post('execution_date', true) ?: date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        if ($schedule_task_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid task.']);
            return;
        }

        $this->load->model('Timer_model');

        $target = $this->getTimerScheduleTask($schedule_task_id, $date);
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
            return;
        }

        if (strtolower((string) ($target->frequency ?? '')) !== 'once') {
            echo json_encode(['success' => false, 'message' => 'Timer is available only for once schedules.']);
            return;
        }

        if ((int) $target->effective_user_id !== $this->getCurrentUserId()) {
            echo json_encode(['success' => false, 'message' => 'Only the assigned user can pause this timer.']);
            return;
        }

        if ($target->log_status === 'completed') {
            $this->Timer_model->stopScheduleTaskTimer($schedule_task_id, $date, $this->getCurrentUserId());
            echo json_encode(['success' => false, 'message' => 'Completed tasks cannot be timed.']);
            return;
        }

        $timer = $this->Timer_model->pauseScheduleTaskTimer($schedule_task_id, $date, $this->getCurrentUserId());
        if (!$timer) {
            echo json_encode(['success' => false, 'message' => 'Timer not found.']);
            return;
        }

        $totalSeconds = $this->Timer_model->getScheduleTaskTimerTotalSeconds($timer);

        echo json_encode([
            'success' => true,
            'schedule_task_id' => $schedule_task_id,
            'total_seconds' => $totalSeconds,
            'is_running' => 0,
            'locked' => 0,
            'can_start' => 1,
            'can_pause' => 0
        ]);
    }

    public function process_delegation_queue()
    {
        if (!$this->input->is_cli_request() && !$this->isRole(2)) {
            show_error('Unauthorized', 403);
        }

        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $processed = $this->processDelegationQueueInternal($today, $current_time);

        echo json_encode([
            'status' => 'success',
            'processed' => $processed,
            'run_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function process_due_tasks()
    {
        if (!$this->input->is_cli_request() && !$this->isRole(2)) {
            show_error('Unauthorized', 403);
        }

        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $schedules = $this->Schedule_model->getSchedulesForReminderProcessing($today);
        $processed = [
            'reminders' => 0,
            'warnings' => 0,
            'delegations' => $this->processDelegationQueueInternal($today, $current_time),
        ];

        $delegations = $this->Schedule_model->getActiveDelegationsForDate($today, array_map(function ($row) {
            return (int) $row->assigned_user_id;
        }, $schedules));

        foreach ($schedules as $schedule) {
            $effective_user_id = (int) $schedule->assigned_user_id;
            $delegated_from_user_id = null;
            $recipient_email = isset($schedule->assigned_user_email) ? $schedule->assigned_user_email : null;
            $recipient_name = $schedule->assigned_user;

            if (isset($delegations[$schedule->assigned_user_id])) {
                $delegation = $delegations[$schedule->assigned_user_id];
                $effective_user_id = (int) $delegation->delegated_user_id;
                $delegated_from_user_id = (int) $delegation->original_user_id;

                $delegate_user = $this->db->select('name, email')->where('user_id', $effective_user_id)->get('users')->row();
                if ($delegate_user) {
                    $recipient_email = $delegate_user->email;
                    $recipient_name = $delegate_user->name;
                }
            }

            $log = $this->Schedule_model->getOrCreateLog($schedule->id, $today, $effective_user_id, $delegated_from_user_id);

            if ($log->status === 'completed') {
                continue;
            }

            if (!empty($schedule->reminder_time) && $schedule->reminder_time <= $current_time && empty($log->reminder_sent_at)) {
                $message = 'Reminder: "' . $schedule->schedule_name . '" is due at ' . $schedule->task_time . '.';
                if ($delegated_from_user_id) {
                    $message .= ' This task is delegated for today.';
                }

                $this->Schedule_model->createNotification([
                    'ticket_id' => null,
                    'schedule_task_id' => (int) $schedule->id,
                    'task_id' => null,
                    'sender_id' => null,
                    'receiver_id' => $effective_user_id,
                    'message' => $message,
                    'notification_type' => $delegated_from_user_id ? 'schedule_delegation' : 'schedule_reminder',
                    'is_read' => 0,
                ]);

                $this->sendScheduleEmail(
                    $recipient_email,
                    'Schedule Reminder: ' . $schedule->schedule_name,
                    $message . PHP_EOL . 'Assigned to: ' . $recipient_name
                );

                $this->Schedule_model->markReminderSent($log->id);
                $processed['reminders']++;
            }

            $warning_threshold = !empty($schedule->task_time)
                ? date('H:i:s', strtotime($schedule->task_time . ' +10 minutes'))
                : null;

            if (!empty($warning_threshold) && $warning_threshold <= $current_time && empty($log->warning_sent_at) && $log->status !== 'completed') {
                $message = 'Warning: "' . $schedule->schedule_name . '" is still pending after its due time.';

                $this->Schedule_model->createNotification([
                    'ticket_id' => null,
                    'schedule_task_id' => (int) $schedule->id,
                    'task_id' => null,
                    'sender_id' => null,
                    'receiver_id' => $effective_user_id,
                    'message' => $message,
                    'notification_type' => 'schedule_warning',
                    'is_read' => 0,
                ]);

                $this->sendScheduleEmail(
                    $recipient_email,
                    'Schedule Warning: ' . $schedule->schedule_name,
                    $message . PHP_EOL . 'Assigned to: ' . $recipient_name
                );

                $this->Schedule_model->markWarningSent($log->id);
                $processed['warnings']++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'processed' => $processed,
            'run_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
