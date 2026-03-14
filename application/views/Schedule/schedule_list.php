<?php
$this->load->view('Layout/Header');
$krupalApproverName = !empty($krupal_approver) ? $krupal_approver->name : 'Krupal Sir';
?>

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
        <div class="row">
            <div class="col-lg-8">
                <div class="card schedule-card schedule-card--compact">
                    <div class="card-header">
                        <h3 class="card-title">Today&apos;s Task Board</h3>
                        <form method="get" class="schedule-filter-form" id="scheduleBoardFilterForm">
                            <select name="schedule_user_id" id="scheduleBoardUserFilter" class="custom-select schedule-filter-select">
                                <?php foreach ($scope_users as $scope_user) { ?>
                                    <option value="<?php echo $scope_user->user_id; ?>" <?php echo (int) $selected_view_user_id === (int) $scope_user->user_id ? 'selected' : ''; ?>>
                                        <?php echo $scope_user->name; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover schedule-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Owner</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="todayTaskBoardBody">
                                <?php $this->load->view('Schedule/partials/today_task_rows', ['today_tasks' => $today_tasks, 'current_user_id' => $current_user_id]); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card schedule-card schedule-card--compact">
                    <div class="card-header">
                        <h3 class="card-title">Leave Delegation</h3>
                        <div class="schedule-header-actions">
                            <?php if (!empty($can_approve_delegations)) { ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="openDelegationManager">
                                    Manage
                                </button>
                            <?php } ?>
                            <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#delegationModal">
                                Request Delegation
                            </button>
                        </div>
                    </div>
                    <div class="card-body schedule-side-card">
                        <div class="schedule-delegation-workspace" id="delegationWorkspace">
                            <div class="schedule-delegation-list" id="delegationRequestList">
                                <?php if (!empty($delegations)) { ?>
                                    <?php foreach ($delegations as $delegation) { ?>
                                        <button
                                            type="button"
                                            class="schedule-delegation-item js-open-delegation"
                                            data-id="<?php echo (int) $delegation->id; ?>"
                                        >
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="text-left">
                                                    <strong><?php echo $delegation->original_user_name; ?></strong>
                                                    <span>to <?php echo $delegation->delegated_user_name; ?></span>
                                                </div>
                                                <span class="schedule-badge <?php echo $delegation->approval_status === 'approved' ? 'schedule-badge--active' : ($delegation->approval_status === 'rejected' ? 'schedule-badge--inactive' : 'schedule-badge--pending'); ?>">
                                                    <?php echo ucfirst($delegation->approval_status); ?>
                                                </span>
                                            </div>
                                            <small><?php echo date('d M Y', strtotime($delegation->start_date)); ?> - <?php echo date('d M Y', strtotime($delegation->end_date)); ?></small>
                                            <small>Requested by <?php echo !empty($delegation->created_by_name) ? $delegation->created_by_name : 'System'; ?></small>
                                        </button>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="schedule-empty schedule-empty--tight">No delegation request found yet.</div>
                                <?php } ?>
                            </div>
                            <?php if (!empty($can_approve_delegations)) { ?>
                            <div class="schedule-delegation-detail d-none" id="delegationManagePanel">
                                <?php if (!empty($delegations)) { ?>
                                    <div class="schedule-manage-placeholder" id="delegationManagePlaceholder">
                                        <span class="schedule-manage-label">Manage</span>
                                        <h4>Select a request</h4>
                                        <p>Click any leave delegation card to load its full detail and actions here.</p>
                                    </div>
                                    <div class="schedule-manage-content d-none" id="delegationManageContent">
                                        <div class="schedule-manage-header">
                                            <div>
                                                <span class="schedule-manage-label">Manage</span>
                                                <h4 id="delegationDetailTitle">-</h4>
                                            </div>
                                            <span class="schedule-badge schedule-badge--pending" id="delegationDetailStatus">Pending</span>
                                        </div>
                                        <div class="schedule-manage-grid">
                                            <div>
                                                <span class="schedule-manage-key">Leave Window</span>
                                                <strong id="delegationDetailWindow">-</strong>
                                            </div>
                                            <div>
                                                <span class="schedule-manage-key">Requested By</span>
                                                <strong id="delegationDetailRequester">-</strong>
                                            </div>
                                        </div>
                                        <div class="schedule-manage-grid">
                                            <div>
                                                <span class="schedule-manage-key">Approved By</span>
                                                <strong id="delegationDetailApprover">-</strong>
                                            </div>
                                            <div>
                                                <span class="schedule-manage-key">Updated On</span>
                                                <strong id="delegationDetailApprovedAt">-</strong>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="delegationManageRemarks">Remarks</label>
                                            <textarea id="delegationManageRemarks" class="form-control" rows="4" placeholder="Add approval, rejection or rollback note"></textarea>
                                        </div>
                                        <input type="hidden" id="delegationManageId" value="">
                                        <div class="schedule-manage-actions<?php echo empty($can_approve_delegations) ? ' d-none' : ''; ?>" id="delegationManageActions">
                                            <button type="button" class="btn btn-outline-secondary btn-sm js-manage-delegation" data-status="pending">Rollback</button>
                                            <button type="button" class="btn btn-light btn-sm" id="closeDelegationManager">Back</button>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="schedule-empty schedule-empty--tight">Select a delegation request to manage.</div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card schedule-card">
            <div class="card-header">
                <h3 class="card-title">All Scheduled Tasks</h3>
                <button class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">
                    Create Schedule
                </button>
            </div>

            <div class="card-body">
                <table class="table table-hover schedule-table">
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
                                <tr>
                                    <td><?php echo $row->id; ?></td>
                                    <td>
                                        <div class="schedule-name">
                                            <strong><?php echo $row->schedule_name; ?></strong>
                                            <span class="schedule-description">
                                                <?php echo !empty($row->description) ? $row->description : 'No description added'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?php echo !empty($row->assigned_user) ? $row->assigned_user : '-'; ?></td>
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
                                            <button type="button" class="btn btn-sm btn-outline-primary js-edit-schedule" data-id="<?php echo (int) $row->id; ?>">Edit</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger js-delete-schedule" data-id="<?php echo (int) $row->id; ?>">Delete</button>
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
                    <div class="form-group">
                        <label>Original User</label>
                        <select name="original_user_id" class="custom-select" required>
                            <option value="<?php echo $current_user_id; ?>">Self</option>
                            <?php foreach ($scope_users as $scope_user) { ?>
                                <?php if ((int) $scope_user->user_id !== (int) $current_user_id) { ?>
                                    <option value="<?php echo $scope_user->user_id; ?>"><?php echo $scope_user->name; ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Delegate To</label>
                        <select name="delegated_user_id" class="custom-select" required>
                            <option value="">Select User</option>
                            <?php foreach ($delegation_candidates as $candidate) { ?>
                                <?php if ((int) $candidate->user_id !== (int) $current_user_id) { ?>
                                    <option value="<?php echo $candidate->user_id; ?>"><?php echo $candidate->name; ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                        <small class="text-muted d-block mt-2">Delegation is restricted to <?php echo htmlspecialchars($krupalApproverName); ?> only.</small>
                    </div>

                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="request_reason" class="form-control" rows="3" required placeholder="Enter leave reason"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Leave Start</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Leave End</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-3">
                        Delegation becomes valid only after <?php echo htmlspecialchars($krupalApproverName); ?> approval.
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

<?php
$this->load->view('Layout/Footer');
?>
