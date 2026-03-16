<?php
$this->load->view('Layout/Header');
$delegationApprovalCaption = !empty($delegation_approval_caption) ? $delegation_approval_caption : 'Department HOD approval';
?>

<style>
    .schedule-delegation-item {
        cursor: pointer;
    }

    .schedule-delegation-summary {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .schedule-delegation-inline-detail {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #dde6f0;
    }

    .schedule-delegation-item .form-control {
        border-radius: 0.75rem;
    }
</style>

<div class="content-wrapper schedule-page">
    <section class="content-header">
        <div class="schedule-hero">
            <div>
                <h1 class="schedule-hero__title">Schedule Tasks</h1>
                <p class="schedule-hero__meta">Track recurring tasks and assign them cleanly across your reporting hierarchy.</p>
                <p class="schedule-hero__meta">
                    <strong><?php echo $role_label; ?></strong>
                    <?php if ($can_assign_user) { ?>
                        <span> | <?php echo $subordinate_count; ?> subordinate<?php echo $subordinate_count === 1 ? '' : 's'; ?> available</span>
                    <?php } else { ?>
                        <span> | self-assignment only</span>
                    <?php } ?>
                </p>
            </div>
            <div class="schedule-summary">
                <i class="fas fa-calendar-check"></i>
                <span><?php echo count($schedules); ?> Records</span>
            </div>
        </div>
    </section>

    <section class="content">
        <?php
            $statusCounts = ['completed' => 0, 'overdue' => 0, 'pending' => 0];
            foreach ($today_tasks as $task) {
                $statusKey = strtolower((string) $task->log_status);
                if (!isset($statusCounts[$statusKey])) {
                    $statusCounts[$statusKey] = 0;
                }
                $statusCounts[$statusKey]++;
            }
            $section_shell = [
                'id' => 'schedule-workspace',
                'default' => 'workspace',
                'eyebrow' => 'Schedule Workspace',
                'title' => 'Switch schedule operations without leaving the page',
                'description' => 'Move between the live task workspace and the full schedule registry using section navigation.',
                'badge' => count($schedules) . ' active entries',
                'sections' => [
                    ['id' => 'workspace', 'label' => 'Workspace', 'hint' => 'Board, status and delegations'],
                    ['id' => 'registry', 'label' => 'Schedule Registry', 'hint' => 'All recurring schedules'],
                ],
            ];
        ?>
        <div class="trs-section-workspace" data-section-shell="schedule-workspace" data-default-section="workspace">
            <?php $this->load->view('Layout/section_shell_nav', ['section_shell' => $section_shell]); ?>
            <div class="trs-section-panels">
                <section class="trs-section-panel" data-section-panel="workspace" hidden>
                    <div class="trs-section-panel__body">
        <div class="row">
            <div class="col-lg-7">
                <div class="card schedule-card schedule-card--compact">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
                        <div>
                            <h3 class="card-title">Today&apos;s Task Board</h3>
                            <p class="mb-0 text-muted" style="font-size:0.9rem;">Filter by assignee, priority and view.</p>
                        </div>
                        <div class="d-flex align-items-center" style="gap:0.45rem;">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="scheduleBoardRefresh">
                                Refresh
                            </button>
                            <div class="btn-group btn-group-sm" role="group" aria-label="View toggle">
                                <button type="button" class="btn btn-outline-primary js-task-view-toggle <?php echo $schedule_task_view === 'today' ? 'active' : ''; ?>" data-view="today">Today</button>
                                <button type="button" class="btn btn-outline-primary js-task-view-toggle <?php echo $schedule_task_view === 'all' ? 'active' : ''; ?>" data-view="all">All</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-header pt-0 pb-2">
                        <div class="d-flex flex-wrap justify-content-end align-items-center" style="gap:0.45rem;">
                            <select name="schedule_user_id" id="scheduleBoardUserFilter" class="custom-select schedule-filter-select" style="min-width: 140px;">
                                <option value="all" <?php echo (int) $selected_view_user_id === 0 ? 'selected' : ''; ?>>All Visible Users</option>
                                <?php foreach ($scope_users as $scope_user) { ?>
                                    <option value="<?php echo $scope_user->user_id; ?>" <?php echo (int) $selected_view_user_id === (int) $scope_user->user_id ? 'selected' : ''; ?>>
                                        <?php echo $scope_user->name; ?><?php echo !empty($scope_user->department_name) ? ' (' . $scope_user->department_name . ')' : ''; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <select name="schedule_task_priority" id="scheduleTaskPriority" class="custom-select schedule-filter-select" style="min-width: 130px;">
                                <option value="all" <?php echo (!empty($schedule_task_priority) && $schedule_task_priority === 'all') || empty($schedule_task_priority) ? 'selected' : ''; ?>>All Priorities</option>
                                <option value="high" <?php echo (!empty($schedule_task_priority) && $schedule_task_priority === 'high') ? 'selected' : ''; ?>>Urgent</option>
                                <option value="medium" <?php echo (!empty($schedule_task_priority) && $schedule_task_priority === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo (!empty($schedule_task_priority) && $schedule_task_priority === 'low') ? 'selected' : ''; ?>>Low</option>
                            </select>
                            <input type="hidden" name="schedule_task_view" id="scheduleTaskViewInput" value="<?php echo !empty($schedule_task_view) ? htmlspecialchars($schedule_task_view) : 'today'; ?>">
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover schedule-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Priority</th>
                                    <th>Owner</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="todayTaskBoardBody">
                                <?php $this->load->view('Schedule/partials/today_task_rows', ['today_tasks' => $today_tasks, 'current_user_id' => $current_user_id, 'krupal_approver_id' => $krupal_approver_id]); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card schedule-card schedule-card--compact mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title">Live Status Breakdown</h3>
                            <small class="text-muted">Live counts for the visible task board.</small>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column flex-md-row align-items-center">
                        <div style="flex:1; max-width:200px;">
                            <canvas id="todayStatusDonut" width="200" height="200"></canvas>
                        </div>
                        <div style="flex:1; padding-left: 1rem;">
                            <div class="mt-2" id="todayStatusBreakdownBadges">
                                <span class="badge badge-success">Completed: <span data-status-count="completed"><?php echo (int) $statusCounts['completed']; ?></span></span>
                                <span class="badge badge-warning">Pending: <span data-status-count="pending"><?php echo (int) $statusCounts['pending']; ?></span></span>
                                <span class="badge badge-danger">Overdue: <span data-status-count="overdue"><?php echo (int) $statusCounts['overdue']; ?></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card schedule-card schedule-card--compact" style="min-height: 500px;">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="card-title">Leave Delegation Summary</h3>
                            <small class="text-muted">Approved: <?php echo isset($approved_delegation_count) ? (int) $approved_delegation_count : 0; ?> / Total: <?php echo count($delegations); ?></small>
                        </div>
                        <div>
                            <?php if (!empty($can_approve_delegations)) { ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="openDelegationManager">Manage</button>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="card-body schedule-side-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong class="d-block">Leave status</strong>
                                <small class="text-muted">Current approvals</small>
                            </div>
                            <span class="badge badge-success">Approved</span>
                        </div>
                        <div class="schedule-delegation-workspace" id="delegationWorkspace">
                            <div class="schedule-delegation-list" id="delegationRequestList">
                                <?php if (!empty($delegations)) { ?>
                                    <?php foreach ($delegations as $delegation) { ?>
                                        <div
                                            class="schedule-delegation-item js-open-delegation"
                                            data-id="<?php echo (int) $delegation->id; ?>"
                                            data-can-manage="<?php echo !empty($delegation->can_manage) ? '1' : '0'; ?>"
                                        >
                                            <div class="schedule-delegation-summary">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="text-left">
                                                        <strong><?php echo $delegation->original_user_name; ?></strong>
                                                        <span>to <?php echo $delegation->delegated_user_name; ?></span>
                                                    </div>
                                                    
                                                    <?php
                                                    $delegationLabel = ucfirst($delegation->approval_status);
                                                    $delegationBadgeClass = 'schedule-badge--pending';
                                                    if ($delegation->approval_status === 'approved') {
                                                        $delegationLabel = 'Accepted';
                                                        $delegationBadgeClass = 'schedule-badge--active';
                                                    } elseif ($delegation->approval_status === 'rejected') {
                                                        $delegationLabel = 'Rejected';
                                                        $delegationBadgeClass = 'schedule-badge--inactive';
                                                    }
                                                    ?>
                                                    <span class="schedule-badge <?php echo $delegationBadgeClass; ?> js-delegation-badge">
                                                        <?php echo htmlspecialchars($delegationLabel); ?>
                                                    </span>
                                                </div>
                                                <small class="d-block"><?php echo date('d M Y', strtotime($delegation->start_date)); ?> - <?php echo date('d M Y', strtotime($delegation->end_date)); ?></small>
                                                <small class="d-block">Requested by <?php echo !empty($delegation->created_by_name) ? $delegation->created_by_name : 'System'; ?></small>
                                                <small class="d-block">Approval by <?php echo !empty($delegation->approval_required_by) ? htmlspecialchars($delegation->approval_required_by) : htmlspecialchars($delegationApprovalCaption); ?></small>
                                                <?php
                                                $requestReason = '';
                                                $delegationTaskId = 0;
                                                $delegationTaskTitle = '';

                                                if (!empty($delegation->approval_remarks)) {
                                                    if (preg_match('/TASK_ID::(\d+)/', (string) $delegation->approval_remarks, $taskMatches)) {
                                                        $delegationTaskId = (int) $taskMatches[1];
                                                    }
                                                    if (preg_match('/TASK_TITLE::([^\\r\\n]+)/', (string) $delegation->approval_remarks, $titleMatches)) {
                                                        $delegationTaskTitle = trim($titleMatches[1]);
                                                    }
                                                    if (preg_match('/REQUEST_REASON::(.+)/', (string) $delegation->approval_remarks, $matches)) {
                                                        $requestReason = trim($matches[1]);
                                                    } else {
                                                        $requestReason = trim((string) preg_replace('/^Request reason:\s*/', '', (string) $delegation->approval_remarks));
                                                    }
                                                }
                                                ?>
                                                <?php if ($delegationTaskId > 0 && $delegationTaskTitle !== '') { ?>
                                                    <small><strong>Task:</strong> <?php echo htmlspecialchars($delegationTaskTitle); ?></small>
                                                <?php } ?>
                                                <small>
                                                    Reason:
                                                    <?php echo $requestReason !== '' ? htmlspecialchars($requestReason) : '-'; ?>
                                                </small>
                                                <?php $delegationStartsAfterToday = !empty($delegation->start_date) && strtotime($delegation->start_date) > strtotime(date('Y-m-d')); ?>
                                                <?php if ((int) $delegation->created_by === (int) $current_user_id && $delegation->approval_status === 'pending' && $delegationStartsAfterToday) { ?>
                                                    <div class="schedule-manage-actions mt-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary btn-sm js-edit-delegation-request"
                                                            data-toggle="modal"
                                                            data-target="#delegationModal"
                                                            data-id="<?php echo (int) $delegation->id; ?>"
                                                            data-original-user-id="<?php echo (int) $delegation->original_user_id; ?>"
                                                            data-delegated-user-id="<?php echo (int) $delegation->delegated_user_id; ?>"
                                                            data-start-date="<?php echo htmlspecialchars($delegation->start_date); ?>"
                                                            data-end-date="<?php echo htmlspecialchars($delegation->end_date); ?>"
                                                            data-request-reason="<?php echo htmlspecialchars($requestReason); ?>"
                                                            data-task-id="<?php echo (int) $delegationTaskId; ?>"
                                                            data-task-title="<?php echo htmlspecialchars($delegationTaskTitle); ?>"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm js-delete-delegation-request" data-id="<?php echo (int) $delegation->id; ?>">Delete</button>
                                                    </div>
                                                <?php } ?>
                                                <?php if (!empty($delegation->can_manage) && $delegation->approval_status === 'pending' && strtotime($delegation->start_date) > strtotime(date('Y-m-d'))) { ?>
                                                    <div class="schedule-manage-actions mt-2">
                                                        <button type="button" class="btn btn-outline-success btn-sm js-manage-delegation-action" data-status="approved">Accept</button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm js-manage-delegation-action" data-status="rejected">Reject</button>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="schedule-delegation-inline-detail d-none">
                                                <div class="schedule-manage-grid mb-2">
                                                    <div>
                                                        <span class="schedule-manage-key">Leave Window</span>
                                                        <strong class="js-delegation-window"><?php echo date('d M Y', strtotime($delegation->start_date)); ?> - <?php echo date('d M Y', strtotime($delegation->end_date)); ?></strong>
                                                    </div>
                                                    <div>
                                                        <span class="schedule-manage-key">Requested By</span>
                                                        <strong class="js-delegation-requester"><?php echo !empty($delegation->created_by_name) ? $delegation->created_by_name : 'System'; ?></strong>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label>Reason</label>
                                                    <textarea class="form-control js-delegation-request-reason" rows="2" readonly></textarea>
                                                </div>
                                                <?php if (!empty($delegation->can_manage)) { ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm js-manage-delegation-action d-none" data-status="pending">Rollback</button>
                                                        <button type="button" class="btn btn-light btn-sm js-close-delegation-card">✕ Close</button>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" class="btn btn-light btn-sm js-close-delegation-card">✕ Close</button>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="schedule-empty schedule-empty--tight">No delegation request found yet.</div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    </div>
                </section>

                <section class="trs-section-panel" data-section-panel="registry" hidden>
                    <div class="trs-section-panel__body">
        <div class="card schedule-card">
            <div class="card-header d-flex align-items-center">
                <div><h3 class="card-title">All Scheduled Tasks</h3></div>
                <div class="d-flex align-items-center ml-auto" style="gap:0.5rem;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="allScheduleRefresh">
                        Refresh
                    </button>
                    <select id="allScheduleUserFilter" class="custom-select" style="width: 240px;">
                        <option value="all">All Visible Users</option>
                        <?php foreach ($scope_users as $scope_user) { ?>
                            <option value="<?php echo (int) $scope_user->user_id; ?>">
                                <?php echo htmlspecialchars($scope_user->name . (!empty($scope_user->department_name) ? ' (' . $scope_user->department_name . ')' : '')); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">
                        Create Schedule
                    </button>
                </div>
            </div>

            <div class="card-body">
                <table id="allScheduledTasksTable" class="table table-hover schedule-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Schedule Name</th>
                            <th>Assigned User</th>
                            <th>Task Time</th>
                            <th>Reminder Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($schedules)) { ?>
                            <?php foreach ($schedules as $row) { ?>
                                <tr data-assigned-user-id="<?php echo (int) $row->assigned_user_id; ?>">
                                    <td><?php echo $row->id; ?></td>
                                    <td>
                                        <div class="schedule-name">
                                            <strong><?php echo $row->schedule_name; ?></strong>
                                            <span class="schedule-description">
                                                <?php echo !empty($row->description) ? $row->description : 'No description added'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo !empty($row->assigned_user) ? $row->assigned_user : '-'; ?>
                                        <?php if (!empty($row->assigned_department_name)) { ?>
                                            <span class="schedule-inline-note"><?php echo htmlspecialchars($row->assigned_department_name); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <span class="schedule-meta">
                                            <i class="far fa-clock"></i>
                                            <?php echo !empty($row->task_time) ? date('h:i A', strtotime($row->task_time)) : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="schedule-meta">
                                            <i class="far fa-bell"></i>
                                            <?php echo !empty($row->reminder_time) ? date('h:i A', strtotime($row->reminder_time)) : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="schedule-badge <?php echo strtolower($row->status) === 'active' ? 'schedule-badge--active' : 'schedule-badge--inactive'; ?>">
                                            <?php echo $row->status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="schedule-actions">
                                            <?php if (!empty($row->can_manage)) { ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary js-edit-schedule" data-id="<?php echo (int) $row->id; ?>">Edit</button>
                                                <?php if (strtolower((string) $row->status) === 'active') { ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary js-open-schedule-delegation"
                                                        data-task-id="<?php echo (int) $row->id; ?>"
                                                        data-task-title="<?php echo htmlspecialchars($row->schedule_name); ?>"
                                                        data-original-user-id="<?php echo (int) $row->assigned_user_id; ?>"
                                                        data-toggle="modal"
                                                        data-target="#delegationModal"
                                                    >
                                                        Delegate
                                                    </button>
                                                <?php } ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger js-delete-schedule" data-id="<?php echo (int) $row->id; ?>">Delete</button>
                                            <?php } else { ?>
                                                <span class="badge badge-light border">Read only</span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="7" class="schedule-empty">No Schedule Found</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>
</div>

<div class="modal fade schedule-modal" id="scheduleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Create Schedule</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <form id="scheduleForm" class="schedule-form">
                    <div class="form-group">
                        <label>Schedule Name</label>
                        <input type="text" name="schedule_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Assign User</label>
                        <div class="schedule-assignment-box">
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="assignSelf" name="assign_type" value="self" checked>
                                <label for="assignSelf" class="custom-control-label">Self</label>
                            </div>

                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="assignSubordinate" name="assign_type" value="subordinate" <?php echo empty($can_assign_user) ? 'disabled' : ''; ?>>
                                <label for="assignSubordinate" class="custom-control-label">Subordinate</label>
                            </div>

                            <input
                                type="hidden"
                                name="assigned_user_id"
                                id="assigned_user_id"
                                data-current-user-id="<?php echo (int) $current_user_id; ?>"
                                value="<?php echo (int) $current_user_id; ?>"
                            >

                            <div id="subordinateSelectWrap" class="mt-3" style="display:none;">
                                <select id="subordinateSelect" class="custom-select">
                                    <option value="">Select Subordinate</option>
                                    <?php foreach ($users as $user) { ?>
                                        <option value="<?php echo $user->user_id; ?>">
                                            <?php echo $user->name; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <?php if (empty($can_assign_user)) { ?>
                                <small class="schedule-assignment-note d-block">No subordinate user available.</small>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Frequency</label>
                        <select name="frequency" class="custom-select">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="once">Once</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Task Time</label>
                        <input type="time" name="task_time" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Reminder Time</label>
                        <input type="time" name="reminder_time" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="custom-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade schedule-modal" id="delegationModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Request Leave Delegation</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="delegationForm" class="schedule-form">
                    <input type="hidden" name="delegation_id" id="delegation_request_id" value="">
                    <input type="hidden" id="delegation_form_mode" value="create">
                    <div class="form-group">
                        <label>Leave for</label>
                        <select name="original_user_id" id="delegation_original_user_id" class="custom-select" required>
                            <option value="<?php echo $current_user_id; ?>">Self</option>
                            <?php foreach ($delegation_scope_users as $scope_user) { ?>
                                <?php if ((int) $scope_user->user_id !== (int) $current_user_id) { ?>
                                    <option value="<?php echo $scope_user->user_id; ?>">
                                        <?php echo $scope_user->name; ?><?php echo !empty($scope_user->department_name) ? ' (' . $scope_user->department_name . ')' : ''; ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Delegate To</label>
                        <select name="delegated_user_id" id="delegation_delegated_user_id" class="custom-select" required>
                            <option value="">Select User</option>
                            <?php foreach ($delegation_candidates as $candidate) { ?>
                                <?php if ((int) $candidate->user_id !== (int) $current_user_id) { ?>
                                    <option value="<?php echo $candidate->user_id; ?>"><?php echo $candidate->name; ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                        <small class="text-muted d-block mt-2">You can delegate to any active same-department user. Approval request will go to the respective department HOD.</small>
                    </div>

                    <div class="form-group">
                        <label>Task reference (optional)</label>
                        <select name="delegation_task_id" id="delegation_task_id" class="custom-select">
                            <option value="0">No specific task</option>
                            <?php foreach ($delegation_tasks as $task) { ?>
                                <option value="<?php echo (int) $task->id; ?>"><?php echo htmlspecialchars($task->schedule_name); ?></option>
                            <?php } ?>
                        </select>
                        <small class="text-muted d-block mt-2">Optional reference only. Once approved, all active tasks for the selected leave dates move to the delegate user.</small>
                    </div>

                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="request_reason" id="delegation_request_reason" class="form-control" rows="3" required placeholder="Enter leave reason"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Leave Start</label>
                            <input type="date" name="start_date" id="delegation_start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Leave End</label>
                            <input type="date" name="end_date" id="delegation_end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-3">
                        Delegation becomes valid only after <?php echo htmlspecialchars($delegationApprovalCaption); ?>.
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade schedule-modal" id="editScheduleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Schedule</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <form id="editScheduleForm" class="schedule-form">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">

                    <div class="form-group">
                        <label>Schedule Name</label>
                        <input type="text" name="schedule_name" id="edit_schedule_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_schedule_description" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Frequency</label>
                        <select name="frequency" id="edit_schedule_frequency" class="custom-select">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="once">Once</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="edit_schedule_start_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Task Time</label>
                        <input type="time" name="task_time" id="edit_schedule_task_time" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Reminder Time</label>
                        <input type="time" name="reminder_time" id="edit_schedule_reminder_time" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" id="edit_schedule_priority" class="custom-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_schedule_status" class="custom-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var chartEl = document.getElementById('todayStatusDonut');
        if (!chartEl) {
            return;
        }
        var completed = <?php echo (int)$statusCounts['completed']; ?>;
        var pending = <?php echo (int)$statusCounts['pending']; ?>;
        var overdue = <?php echo (int)$statusCounts['overdue']; ?>;
        if (window.Chart) {
            window.todayStatusChart = new Chart(chartEl, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending', 'Overdue'],
                    datasets: [{
                        data: [completed, pending, overdue],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10 } }
                    }
                }
            });
        }
    })();
</script>
<?php
$this->load->view('Layout/Footer');
?>
