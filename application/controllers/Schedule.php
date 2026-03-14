<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Schedule extends MY_Controller {
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

    public function __construct() {
        parent::__construct();
        $this->load->model('Schedule_model');
        $this->load->model('User_model');
    }

    private function getSubordinateIds()
    {
        return array_values(array_unique(array_map('intval', $this->User_model->getAllSubordinates($this->getCurrentUserId()))));
    }

    private function getScopeUserIds()
    {
        return array_values(array_unique(array_merge([$this->getCurrentUserId()], $this->getSubordinateIds())));
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

        if ($this->isRole(1) && $this->isDepartment(2)) {
            return 'Developer';
        }

        return 'User';
    }

    private function getManagedUser($user_id)
    {
        if ($user_id <= 0) {
            return null;
        }

        $scope_user_ids = $this->getScopeUserIds();
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
        $krupal = $this->getKrupalApprover();

        if (!$krupal || (int) $krupal->user_id === (int) $exclude_user_id) {
            return [];
        }

        if ($department_id !== null && (int) $krupal->department_id !== (int) $department_id) {
            return [];
        }

        return [$krupal];
    }

    private function canApproveDelegations()
    {
        return $this->isRole(2) && $this->isDepartment(2) && $this->getCurrentUserId() === $this->getKrupalApproverId();
    }

    private function getAccessibleDelegation($delegation_id)
    {
        $delegation = $this->Schedule_model->getDelegationById($delegation_id);
        if (!$delegation) {
            return null;
        }

        $scopeUserIds = $this->getScopeUserIds();
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

    private function getAccessibleSchedule($schedule_id)
    {
        $schedule = $this->Schedule_model->getScheduleById($schedule_id);
        if (!$schedule) {
            return null;
        }

        $scopeUserIds = $this->getScopeUserIds();
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
        $user_id = $this->getCurrentUserId();
        $subordinate_ids = $this->getSubordinateIds();
        $scope_user_ids = $this->getScopeUserIds();
        $assignable_users = $this->Schedule_model->getAssignableUsers($subordinate_ids);
        $scope_users = $this->Schedule_model->getScopeUsers($scope_user_ids);
        $selected_view_user_id = (int) $this->input->get('schedule_user_id');

        if ($selected_view_user_id <= 0 || !in_array($selected_view_user_id, $scope_user_ids, true)) {
            $selected_view_user_id = $user_id;
        }

        $this->setPageAssets(
            ['assets/dist/css/pages/schedule.css'],
            ['assets/dist/js/pages/schedule.js']
        );

        $this->render('Schedule/schedule_list', [
            'users' => $assignable_users,
            'scope_users' => $scope_users,
            'can_assign_user' => !empty($assignable_users),
            'can_approve_delegations' => $this->canApproveDelegations(),
            'current_user_id' => $user_id,
            'role_label' => $this->getRoleLabel(),
            'subordinate_count' => count($assignable_users),
            'selected_view_user_id' => $selected_view_user_id,
            'today_tasks' => $this->Schedule_model->getTodayTasksForScope($user_id, $scope_user_ids, $selected_view_user_id, date('Y-m-d')),
            'delegations' => $this->Schedule_model->getDelegationsForUsers($scope_user_ids),
            'delegation_candidates' => $this->getDelegationCandidates($this->getCurrentDepartmentId(), $this->getCurrentUserId()),
            'krupal_approver' => $this->getKrupalApprover(),
            'schedules' => $this->Schedule_model->getVisibleSchedules($user_id, $subordinate_ids),
        ]);
    }

    public function ajax_today_task_board()
    {
        $user_id = $this->getCurrentUserId();
        $scope_user_ids = $this->getScopeUserIds();
        $selected_view_user_id = (int) $this->input->get('schedule_user_id');

        if ($selected_view_user_id <= 0 || !in_array($selected_view_user_id, $scope_user_ids, true)) {
            $selected_view_user_id = $user_id;
        }

        $data['today_tasks'] = $this->Schedule_model->getTodayTasksForScope($user_id, $scope_user_ids, $selected_view_user_id, date('Y-m-d'));
        $data['current_user_id'] = $user_id;

        $html = $this->load->view('Schedule/partials/today_task_rows', $data, true);

        echo json_encode([
            'status' => 'success',
            'html' => $html,
        ]);
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

        if (!$schedule) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Schedule not found or unauthorized.'
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

        if ($data['schedule_name'] === '') {
            echo json_encode(['status' => 'error', 'message' => 'Schedule name is required.']);
            return;
        }

        if ($data['start_date'] === '') {
            echo json_encode(['status' => 'error', 'message' => 'Start date is required.']);
            return;
        }

        if (!in_array($data['frequency'], ['daily', 'weekly', 'monthly'], true)) {
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

        if (!$schedule) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Schedule not found or unauthorized.'
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

        if ($this->Schedule_model->hasOverlappingDelegation($original_user_id, $start_date, $end_date)) {
            echo json_encode(['status' => 'error', 'message' => 'An approved or pending delegation already exists for these leave dates.']);
            return;
        }

        $insert = $this->Schedule_model->createDelegation([
            'original_user_id' => $original_user_id,
            'delegated_user_id' => $delegated_user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'created_by' => $this->getCurrentUserId(),
            'approval_status' => 'pending',
            'approval_remarks' => 'Request reason: ' . $request_reason,
        ]);

        echo json_encode([
            'status' => $insert ? 'success' : 'error',
            'message' => $insert ? 'Delegation request submitted for Krupal sir approval.' : 'Unable to save delegation.'
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
                'approval_remarks' => (string) $delegation->approval_remarks,
                'approved_at' => (string) $delegation->approved_at,
                'can_manage' => $this->canApproveDelegations() && (int) $delegation->original_department_id === 2,
            ],
        ]);
    }

    public function ajax_update_delegation_status()
    {
        if (!$this->canApproveDelegations()) {
            echo json_encode(['status' => 'error', 'message' => 'Only Krupal sir can approve delegation requests.']);
            return;
        }

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

        if ($delegation->approval_status !== 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Delegation request has already been processed.']);
            return;
        }

        if ((int) $delegation->original_department_id !== 2) {
            echo json_encode(['status' => 'error', 'message' => 'Only IT team delegations can be approved here.']);
            return;
        }

        if ($approval_status === 'approved' && $this->Schedule_model->hasOverlappingDelegation($delegation->original_user_id, $delegation->start_date, $delegation->end_date, $delegation->id)) {
            echo json_encode(['status' => 'error', 'message' => 'Another active or pending delegation overlaps these dates.']);
            return;
        }

        $updated = $this->Schedule_model->updateDelegationApproval(
            $delegation_id,
            $approval_status,
            $this->getCurrentUserId(),
            $approval_remarks
        );

        echo json_encode([
            'status' => $updated ? 'success' : 'error',
            'message' => $updated
                ? ($approval_status === 'approved' ? 'Delegation approved successfully.' : 'Delegation rejected successfully.')
                : 'Unable to update delegation request.'
        ]);
    }

    public function ajax_manage_delegation()
    {
        if (!$this->canApproveDelegations()) {
            echo json_encode(['status' => 'error', 'message' => 'Only Krupal sir can manage delegation requests.']);
            return;
        }

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

        if ((int) $delegation->original_department_id !== 2) {
            echo json_encode(['status' => 'error', 'message' => 'Only IT team delegations can be managed here.']);
            return;
        }

        if ($target_status === $delegation->approval_status) {
            echo json_encode(['status' => 'error', 'message' => 'Delegation is already in the selected state.']);
            return;
        }

        if (
            $target_status === 'approved' &&
            $this->Schedule_model->hasOverlappingDelegation($delegation->original_user_id, $delegation->start_date, $delegation->end_date, $delegation->id)
        ) {
            echo json_encode(['status' => 'error', 'message' => 'Another active or pending delegation overlaps these dates.']);
            return;
        }

        $updateData = [
            'approval_status' => $target_status,
            'approval_remarks' => $remarks !== '' ? $remarks : ($delegation->approval_remarks ?: null),
        ];

        if ($target_status === 'pending') {
            $updateData['approved_by'] = null;
            $updateData['approved_at'] = null;
        } else {
            $updateData['approved_by'] = $this->getCurrentUserId();
            $updateData['approved_at'] = date('Y-m-d H:i:s');
        }

        $updated = $this->Schedule_model->updateDelegationWorkflow($delegation_id, $updateData);

        echo json_encode([
            'status' => $updated ? 'success' : 'error',
            'message' => $updated ? 'Delegation updated successfully.' : 'Unable to update delegation request.',
        ]);
    }

    public function ajax_complete_today_task()
    {
        $schedule_task_id = (int) $this->input->post('schedule_task_id');
        $date = $this->input->post('execution_date', true) ?: date('Y-m-d');

        $scope_user_ids = $this->getScopeUserIds();
        $tasks = $this->Schedule_model->getTodayTasksForScope(
            $this->getCurrentUserId(),
            $scope_user_ids,
            $this->getCurrentUserId(),
            $date
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

        $ok = $this->Schedule_model->markTaskComplete(
            $schedule_task_id,
            $date,
            $this->getCurrentUserId(),
            $target->effective_user_id,
            $target->delegated_from_user_id
        );

        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Task marked completed.' : 'Unable to update task.'
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

            if (!empty($schedule->task_time) && $schedule->task_time <= $current_time && empty($log->warning_sent_at) && $log->status !== 'completed') {
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
