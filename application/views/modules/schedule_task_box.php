<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title">Today's Scheduled Tasks</h3>
                <?php if (!empty($schedule_scope_users)) { ?>
                    <div class="card-tools">
                        <form method="get">
                            <select name="schedule_user_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <?php foreach ($schedule_scope_users as $scope_user) { ?>
                                    <option value="<?php echo $scope_user->user_id; ?>" <?php echo (int) $selected_schedule_user_id === (int) $scope_user->user_id ? 'selected' : ''; ?>>
                                        <?php echo $scope_user->name; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </form>
                    </div>
                <?php } ?>
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

                    <tbody>

                        <?php if(!empty($today_tasks)) { ?>

                            <?php foreach($today_tasks as $task) { ?>

                                <tr>
                                    <td><?php echo $task->schedule_name; ?></td>
                                    <td><?php echo $task->effective_user_name; ?></td>
                                    <td><?php echo $task->task_time; ?></td>
                                    <td>
                                        <?php if($task->log_status == 'completed'){ ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php } elseif($task->log_status == 'overdue') { ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php } else { ?>
                                            <span class="badge badge-warning">Pending</span>
                                            <?php if ((int) $task->effective_user_id === (int) $this->session->userdata('user_id')) { ?>
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
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="4" class="text-center">
                                    No Scheduled Tasks Today
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
