<?php if (!empty($today_tasks)) { ?>
    <?php foreach ($today_tasks as $task) { ?>
        <?php
            $logStatus = !empty($task->log_status) ? $task->log_status : 'pending';
            $taskTimeLabel = !empty($task->task_time) ? date('h:i A', strtotime($task->task_time)) : '-';
            $canCompleteTask = $logStatus !== 'completed' && ((int) $task->effective_user_id === (int) $current_user_id);
            $canControlTimer = $canCompleteTask;
            $showTimer = strtolower((string) ($task->frequency ?? '')) === 'once';
        ?>
        <tr
            data-log-status="<?php echo htmlspecialchars($logStatus); ?>"
            data-schedule-name="<?php echo htmlspecialchars($task->schedule_name); ?>"
            data-task-time="<?php echo htmlspecialchars($taskTimeLabel); ?>"
            data-can-complete="<?php echo $canCompleteTask ? '1' : '0'; ?>"
            data-schedule-task-id="<?php echo (int) $task->id; ?>"
            data-execution-date="<?php echo date('Y-m-d'); ?>"
        >
            <td>
                <div class="schedule-name">
                    <strong><?php echo $task->schedule_name; ?></strong>
                    <span class="schedule-description"><?php echo ucfirst($task->frequency); ?> schedule</span>
                </div>
            </td>
            <td>
                <?php $priority = !empty($task->priority) ? strtolower($task->priority) : 'medium';
                $priorityClass = $priority === 'high' ? 'schedule-badge--inactive' : ($priority === 'medium' ? 'schedule-badge--pending' : 'schedule-badge--active'); ?>
                <span class="schedule-badge <?php echo $priorityClass; ?>" style="font-size:0.7rem; text-transform: capitalize;"><?php echo $priority; ?></span>
            </td>
            <td>
                <?php echo !empty($task->owner_display_name) ? $task->owner_display_name : $task->effective_user_name; ?>
                <?php if (!empty($task->delegation_note)) { ?>
                    <span class="schedule-inline-note"><?php echo $task->delegation_note; ?></span>
                <?php } ?>
            </td>
            <td><?php echo !empty($task->task_time) ? date('h:i A', strtotime($task->task_time)) : '-'; ?></td>
            <td>
                <span class="schedule-badge <?php echo $logStatus === 'completed' ? 'schedule-badge--active' : ($logStatus === 'overdue' ? 'schedule-badge--inactive' : 'schedule-badge--pending'); ?>">
                    <?php echo $logStatus; ?>
                </span>
            </td>
            <td>
                <?php if ($showTimer) { ?>
                    <div
                        class="schedule-timer js-schedule-timer mb-2"
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
                <?php if ($canCompleteTask) { ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success js-complete-schedule"
                        data-id="<?php echo $task->id; ?>"
                        data-date="<?php echo date('Y-m-d'); ?>"
                    >
                        Mark Complete
                    </button>
                <?php } else { ?>
                    <span class="schedule-inline-note"><?php echo $logStatus === 'completed' ? 'Completed' : 'Read only'; ?></span>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
<?php } else { ?>
    <tr>
        <td colspan="6" class="schedule-empty">No due tasks for the selected filter.</td>
    </tr>
<?php } ?>
