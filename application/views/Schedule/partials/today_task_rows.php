<?php if (!empty($today_tasks)) { ?>
    <?php foreach ($today_tasks as $task) { ?>
        <tr>
            <td>
                <div class="schedule-name">
                    <strong><?php echo $task->schedule_name; ?></strong>
                    <span class="schedule-description"><?php echo ucfirst($task->frequency); ?> schedule</span>
                </div>
            </td>
            <td>
                <?php echo !empty($task->owner_display_name) ? $task->owner_display_name : $task->effective_user_name; ?>
                <?php if (!empty($task->delegation_note)) { ?>
                    <span class="schedule-inline-note"><?php echo $task->delegation_note; ?></span>
                <?php } ?>
            </td>
            <td><?php echo !empty($task->task_time) ? date('h:i A', strtotime($task->task_time)) : '-'; ?></td>
            <td>
                <span class="schedule-badge <?php echo $task->log_status === 'completed' ? 'schedule-badge--active' : ($task->log_status === 'overdue' ? 'schedule-badge--inactive' : 'schedule-badge--pending'); ?>">
                    <?php echo $task->log_status; ?>
                </span>
            </td>
            <td>
                <?php if ($task->log_status !== 'completed' && (int) $task->effective_user_id === (int) $current_user_id) { ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success js-complete-schedule"
                        data-id="<?php echo $task->id; ?>"
                        data-date="<?php echo date('Y-m-d'); ?>"
                    >
                        Mark Complete
                    </button>
                <?php } else { ?>
                    <span class="schedule-inline-note">No action</span>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
<?php } else { ?>
    <tr>
        <td colspan="5" class="schedule-empty">No due tasks for the selected user today.</td>
    </tr>
<?php } ?>
