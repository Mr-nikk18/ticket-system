<?php
$selectedScheduleUserValue = isset($selected_schedule_user_id) && (int) $selected_schedule_user_id > 0 ? (int) $selected_schedule_user_id : 'all';
$selectedDashboardStatus = isset($dashboard_ticket_status) ? (int) $dashboard_ticket_status : 0;
$dashboardScopeValue = in_array((string) ($dashboard_scope ?? 'all'), ['mine', 'all', 'assigned'], true) ? (string) $dashboard_scope : 'all';
$currentDashboardUserId = (int) $this->session->userdata('user_id');
?>

    <div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <h3 class="card-title mb-2 mb-lg-0">Today's Scheduled Tasks</h3>
                <div class="d-flex flex-wrap align-items-center" style="gap:0.5rem;">
                        <?php if (!empty($schedule_scope_users)) { ?>
                        <form id="dashboardScheduleFilterForm" method="get" action="<?= base_url('Dashboard') ?>" class="d-flex flex-wrap align-items-center" style="gap:0.5rem;">
                            <input type="hidden" name="dashboard_ticket_status" value="<?= $selectedDashboardStatus ?>">
                            <input type="hidden" name="dashboard_scope" value="<?= htmlspecialchars($dashboardScopeValue) ?>">
                            <select name="schedule_user_id" class="form-control form-control-sm js-dashboard-schedule-filter">
                                <option value="all" <?= $selectedScheduleUserValue === 'all' ? 'selected' : '' ?>>All Visible Users</option>
                                <?php foreach ($schedule_scope_users as $scope_user) { ?>
                                    <option value="<?php echo $scope_user->user_id; ?>" <?php echo (int) $selected_schedule_user_id === (int) $scope_user->user_id ? 'selected' : ''; ?>>
                                        <?php echo $scope_user->name; ?><?php echo !empty($scope_user->department_name) ? ' (' . $scope_user->department_name . ')' : ''; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </form>
                    <?php } ?>

                    <button type="button" id="dashboardScheduleRefresh" class="btn btn-outline-light btn-sm">
                        Refresh
                    </button>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Task Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody id="todayTaskBoardBody">
                        <?php if (!empty($today_tasks)) { ?>
                            <?php foreach ($today_tasks as $task) { ?>
                                <?php
                                    $logStatus = !empty($task->log_status) ? $task->log_status : 'pending';
                                    $taskTimeLabel = !empty($task->task_time) ? date('h:i A', strtotime($task->task_time)) : '-';
                                    $canControlTimer = ((int) $task->effective_user_id === $currentDashboardUserId) && $logStatus !== 'completed';
                                    $showTimer = strtolower((string) ($task->frequency ?? '')) === 'once';
                                ?>
                                <tr
                                    data-log-status="<?php echo htmlspecialchars($logStatus); ?>"
                                    data-schedule-name="<?php echo htmlspecialchars($task->schedule_name); ?>"
                                    data-task-time="<?php echo htmlspecialchars($taskTimeLabel); ?>"
                                    data-can-complete="<?php echo ((int) $task->effective_user_id === $currentDashboardUserId) ? '1' : '0'; ?>"
                                >
                                    <td><?php echo htmlspecialchars($task->schedule_name); ?></td>
                                    <td><?php echo htmlspecialchars($task->owner_display_name ?: $task->effective_user_name); ?></td>
                                    <td><?php echo !empty($task->task_time) ? htmlspecialchars($taskTimeLabel) : '-'; ?></td>
                                    <td>
                                        <?php if ($logStatus == 'completed') { ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php } elseif ($logStatus == 'overdue') { ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php } else { ?>
                                            <span class="badge badge-warning">Pending</span>
                                            <?php if ((int) $task->effective_user_id === $currentDashboardUserId) { ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-outline-success ml-2 js-complete-schedule"
                                                    data-id="<?php echo $task->id; ?>"
                                                    data-date="<?php echo date('Y-m-d'); ?>"
                                                >
                                                    Complete
                                                </button>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($showTimer) { ?>
                                            <div
                                                class="schedule-timer js-schedule-timer mt-2"
                                                style="display:flex;align-items:center;flex-wrap:wrap;gap:0.4rem;"
                                                data-schedule-task-id="<?php echo (int) $task->id; ?>"
                                                data-execution-date="<?php echo date('Y-m-d'); ?>"
                                                data-can-control="<?php echo $canControlTimer ? '1' : '0'; ?>"
                                            >
                                                <div class="schedule-timer-display js-schedule-timer-display" style="min-width:72px;font-weight:700;font-size:0.78rem;">00:00:00</div>
                                                <div class="btn-group btn-group-sm" role="group" aria-label="Schedule timer controls">
                                                    <button type="button" class="btn btn-outline-primary js-schedule-timer-start">Start</button>
                                                    <button type="button" class="btn btn-outline-secondary js-schedule-timer-pause">Pause</button>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="4" class="text-center">
                                    No scheduled tasks found for the selected filter
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
