(function ($) {
    function setButtonBusy($button, busyText) {
        if (!$button || !$button.length) {
            return;
        }

        if (!$button.data('original-text')) {
            $button.data('original-text', $.trim($button.text()));
        }

        $button.prop('disabled', true).addClass('is-busy').text(busyText || 'Processing...');
    }

    function resetButtonBusy($button) {
        if (!$button || !$button.length) {
            return;
        }

        $button.prop('disabled', false).removeClass('is-busy').text($button.data('original-text') || 'Submit');
    }

    function formatDelegationDate(value) {
        if (!value) {
            return '-';
        }

        var date = new Date(value + 'T00:00:00');
        if (window.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function formatDelegationDateTime(value) {
        if (!value) {
            return '-';
        }

        var date = new Date(value.replace(' ', 'T'));
        if (window.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function normalizeDelegationStatus(status) {
        if (!status) {
            return 'pending';
        }
        status = status.toLowerCase().trim();
        if (status === 'accepted') {
            status = 'approved';
        }
        if (status === 'approved' || status === 'rejected' || status === 'pending') {
            return status;
        }
        return 'pending';
    }

    function getDelegationBadgeClass(status) {
        status = normalizeDelegationStatus(status);
        if (status === 'approved') {
            return 'schedule-badge--active';
        }

        if (status === 'rejected') {
            return 'schedule-badge--inactive';
        }

        return 'schedule-badge--pending';
    }

    function resetDelegationCard($card) {
        if (!$card || !$card.length) {
            return;
        }
        $card.removeClass('is-active');
        $card.find('.schedule-delegation-inline-detail').addClass('d-none');
    }

    function syncDelegationQuickActions($card, status) {
        if (!$card || !$card.length) {
            return;
        }

        $card.find('.schedule-delegation-summary .js-manage-delegation-action')
            .closest('.schedule-manage-actions')
            .toggle(status === 'pending');
    }

    function renderDelegationDetail($card, data) {
        if (!$card || !$card.length) {
            return;
        }

        $('.js-open-delegation').each(function () {
            resetDelegationCard($(this));
        });

        $card.addClass('is-active');
        var approvalStatus = (data.effective_status || data.approval_status || 'pending').toLowerCase();
        approvalStatus = normalizeDelegationStatus(approvalStatus);
        var badgeText = approvalStatus === 'approved' ? 'Accepted' : approvalStatus.charAt(0).toUpperCase() + approvalStatus.slice(1);

        $card.find('.js-delegation-badge')
            .attr('class', 'schedule-badge js-delegation-badge ' + getDelegationBadgeClass(approvalStatus))
            .text(badgeText);

        syncDelegationQuickActions($card, approvalStatus);

        $card.find('.js-delegation-window').text(formatDelegationDate(data.start_date) + ' - ' + formatDelegationDate(data.end_date));
        $card.find('.js-delegation-requester').text(data.created_by_name || '-');
        $card.find('.js-delegation-request-reason').val(data.request_reason || '-');

        var $detail = $card.find('.schedule-delegation-inline-detail');
        var $rollback = $detail.find('.js-manage-delegation-action[data-status="pending"]');

        $detail.removeClass('d-none');
        $rollback.toggleClass('d-none', !data.can_manage || approvalStatus !== 'approved');

        // if already rejected or approved, hide manage actions on summary so status is authoritative
        if (approvalStatus === 'approved' || approvalStatus === 'rejected') {
            $card.find('.schedule-manage-actions').hide();
        }

    }

    function loadDelegationDetail($card) {
        var delegationId = $card.data('id');
        if (!delegationId) {
            return;
        }

        $.ajax({
            url: base_url + 'Schedule/ajax_get_delegation_detail',
            type: 'POST',
            dataType: 'json',
            data: {
                delegation_id: delegationId
            },
            success: function (res) {
                if (res.status !== 'success' || !res.data) {
                    return;
                }

                renderDelegationDetail($card, res.data);
            }
        });
    }

    function syncScheduleAssignMode() {
        var assignType = $('input[name="assign_type"]:checked').val();
        var currentUserId = $('#assigned_user_id').data('current-user-id');

        if (assignType === 'subordinate') {
            $('#subordinateSelectWrap').slideDown(120);
            $('#subordinateSelect').prop('required', true);
            $('#assigned_user_id').val($('#subordinateSelect').val());
            return;
        }

        $('#subordinateSelectWrap').slideUp(120);
        $('#subordinateSelect').prop('required', false).val('');
        $('#assigned_user_id').val(currentUserId);
    }

    function setDelegationTaskOptions(tasks, selectedTaskId) {
        var $taskSelect = $('#delegation_task_id');
        if (!$taskSelect.length) {
            return;
        }
        var currentValue = selectedTaskId ? parseInt(selectedTaskId, 10) : 0;
        $taskSelect.empty();
        $taskSelect.append('<option value="0">No specific task</option>');
        if (Array.isArray(tasks) && tasks.length > 0) {
            tasks.forEach(function (task) {
                $taskSelect.append('<option value="' + (task.id || task.task_id || 0) + '"' + ((currentValue > 0 && Number(task.id || task.task_id) === currentValue) ? ' selected' : '') + '>' + (task.schedule_name || task.title || 'Task') + '</option>');
            });
        }
    }

    function fetchDelegationTasksForUser(userId, selectedTaskId) {
        if (!userId) {
            return;
        }
        $.ajax({
            url: base_url + 'Schedule/ajax_get_user_schedule_tasks',
            type: 'GET',
            dataType: 'json',
            data: { user_id: userId },
            success: function (res) {
                if (res.status === 'success') {
                    setDelegationTaskOptions(res.tasks || [], selectedTaskId);
                }
            }
        });
    }

    function resetDelegationRequestForm() {
        $('#delegation_request_id').val('');
        $('#delegation_form_mode').val('create');
        $('#delegationForm')[0].reset();
        $('#delegation_start_date').val(new Date().toISOString().slice(0, 10));
        $('#delegation_end_date').val(new Date().toISOString().slice(0, 10));
        $('#delegationModal .modal-title').text('Request Leave Delegation');
        $('#delegationForm button[type="submit"]').text('Submit Request');
        var currentOriginalUser = $('#delegation_original_user_id').val();
        setDelegationTaskOptions([], 0);
        if (currentOriginalUser) {
            fetchDelegationTasksForUser(parseInt(currentOriginalUser, 10), 0);
        }
    }

    function openDelegationEditForm($button) {
        if (!$button || !$button.length) {
            return;
        }

        $('#delegation_request_id').val($button.data('id'));
        $('#delegation_form_mode').val('edit');
        $('#delegation_original_user_id').val($button.data('original-user-id'));
        $('#delegation_delegated_user_id').val($button.data('delegated-user-id'));
        $('#delegation_start_date').val($button.data('start-date'));
        $('#delegation_end_date').val($button.data('end-date'));
        $('#delegation_request_reason').val($button.data('request-reason') || '');
        $('#delegationModal .modal-title').text('Edit Leave Delegation');
        $('#delegationForm button[type="submit"]').text('Update Request');
        fetchDelegationTasksForUser($('#delegation_original_user_id').val(), $button.data('task-id') || 0);
    }

    function openDelegationCreateFromSchedule($button) {
        if (!$button || !$button.length) {
            return;
        }

        resetDelegationRequestForm();

        var originalUserId = parseInt($button.data('original-user-id'), 10) || 0;
        var taskId = parseInt($button.data('task-id'), 10) || 0;

        if (originalUserId > 0) {
            $('#delegation_original_user_id').val(String(originalUserId));
            fetchDelegationTasksForUser(originalUserId, taskId);
        }

        $('#delegationModal .modal-title').text('Delegate Schedule');
        $('#delegationForm button[type="submit"]').text('Submit Request');
    }

    $(document).on('click', '.js-edit-delegation-request', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        openDelegationEditForm($button);
        $('#delegationModal').modal('show');
    });

    function showOverdueTaskAlert() {
        var $row = $('#todayTaskBoardBody tr[data-log-status="overdue"][data-can-complete="1"]').first();

        if (!$row.length) {
            return;
        }

        var scheduleName = $row.data('schedule-name') || 'This task';
        var taskTime = $row.data('task-time') || '';
        var message = scheduleName + ' is overdue';

        if (taskTime && taskTime !== '-') {
            message += ' (due ' + taskTime + ')';
        }

        message += '. Please complete this task.';
        try {
            var alertKey = 'trs-overdue-alert:' + scheduleName + ':' + taskTime;
            var lastShownAt = parseInt(window.sessionStorage.getItem(alertKey) || '0', 10);

            if (lastShownAt > 0 && (Date.now() - lastShownAt) < 600000) {
                return;
            }

            window.sessionStorage.setItem(alertKey, String(Date.now()));
        } catch (error) {
            // Ignore storage limitations and continue showing the alert once.
        }

        alert(message);
    }

    function updateTodayStatusWidgets(statusCounts) {
        statusCounts = statusCounts || {};

        ['completed', 'pending', 'overdue'].forEach(function (statusKey) {
            var value = parseInt(statusCounts[statusKey] || 0, 10);
            $('[data-status-count="' + statusKey + '"]').text(value);
        });

        if (window.todayStatusChart && window.todayStatusChart.data && window.todayStatusChart.data.datasets.length) {
            window.todayStatusChart.data.datasets[0].data = [
                parseInt(statusCounts.completed || 0, 10),
                parseInt(statusCounts.pending || 0, 10),
                parseInt(statusCounts.overdue || 0, 10)
            ];
            window.todayStatusChart.update();
        }
    }

    $(function () {
        function loadTodayTaskBoard() {
            $.ajax({
                url: base_url + 'Schedule/ajax_today_task_board',
                type: 'GET',
                data: {
                    schedule_user_id: $('#scheduleBoardUserFilter').val(),
                    schedule_task_view: $('#scheduleTaskViewInput').val() || 'today',
                    schedule_task_priority: $('#scheduleTaskPriority').length ? $('#scheduleTaskPriority').val() : 'all'
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        $('#todayTaskBoardBody').html(response.html);
                        updateTodayStatusWidgets(response.status_counts || {});
                        showOverdueTaskAlert();
                    }
                }
            });
        }

        $('input[name="assign_type"]').on('change', syncScheduleAssignMode);

        $('#subordinateSelect').on('change', function () {
            $('#assigned_user_id').val($(this).val());
        });

        $('#scheduleBoardUserFilter, #scheduleTaskPriority').on('change', function (e) {
            e.preventDefault();
            loadTodayTaskBoard();
        });

        $('#scheduleBoardRefresh').on('click', function (e) {
            e.preventDefault();
            loadTodayTaskBoard();
        });

        $('#allScheduleUserFilter').on('change', function () {
            var filter = $(this).val();
            $('#allScheduledTasksTable tbody tr').each(function () {
                var assignedUserId = String($(this).data('assigned-user-id') || '');
                if (filter === 'all' || assignedUserId === filter) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('#allScheduleRefresh').on('click', function (e) {
            e.preventDefault();
            window.location.reload();
        });

        $(document).on('click', '.js-task-view-toggle', function () {
            var selected = $(this).data('view');
            $('#scheduleTaskViewInput').val(selected);
            $('.js-task-view-toggle').removeClass('active');
            $(this).addClass('active');
            loadTodayTaskBoard();
        });

        $('#scheduleModal').on('shown.bs.modal', function () {
            syncScheduleAssignMode();
        });

        $('#scheduleForm').on('reset', function () {
            setTimeout(syncScheduleAssignMode, 0);
        });

        $('#scheduleForm').on('submit', function (e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            setButtonBusy($submitBtn, 'Saving...');

            $.ajax({
                url: base_url + 'Schedule/ajax_save_schedule',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    var res = response;

                    if (res.status === 'success') {
                        alert('Schedule Created Successfully');
                        $('#scheduleForm')[0].reset();
                        $('#scheduleModal').modal('hide');
                        location.reload();
                        return;
                    }

                    resetButtonBusy($submitBtn);
                    alert(res.message ? res.message : 'Error Saving Schedule');
                },
                error: function () {
                    resetButtonBusy($submitBtn);
                    alert('Error Saving Schedule');
                }
            });
        });

        $(document).on('click', '.js-edit-schedule', function () {
            var $button = $(this);
            setButtonBusy($button, 'Loading...');

            $.ajax({
                url: base_url + 'Schedule/ajax_get_schedule',
                type: 'POST',
                dataType: 'json',
                data: {
                    schedule_id: $button.data('id')
                },
                success: function (res) {
                    resetButtonBusy($button);

                    if (res.status !== 'success') {
                        alert(res.message ? res.message : 'Unable to load schedule');
                        return;
                    }

                    $('#edit_schedule_id').val(res.data.id || '');
                    $('#edit_schedule_name').val(res.data.schedule_name || '');
                    $('#edit_schedule_description').val(res.data.description || '');
                    $('#edit_schedule_frequency').val(res.data.frequency || 'daily');
                    $('#edit_schedule_start_date').val(res.data.start_date || '');
                    $('#edit_schedule_task_time').val(res.data.task_time || '');
                    $('#edit_schedule_reminder_time').val(res.data.reminder_time || '');
                    $('#edit_schedule_priority').val(res.data.priority || 'medium');
                    $('#edit_schedule_status').val(res.data.status || 'active');
                    $('#editScheduleModal').modal('show');
                },
                error: function () {
                    resetButtonBusy($button);
                    alert('Unable to load schedule');
                }
            });
        });

        $('#editScheduleForm').on('submit', function (e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            setButtonBusy($submitBtn, 'Updating...');

            $.ajax({
                url: base_url + 'Schedule/ajax_update_schedule',
                type: 'POST',
                dataType: 'json',
                data: $(this).serialize(),
                success: function (res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        $('#editScheduleModal').modal('hide');
                        location.reload();
                        return;
                    }

                    resetButtonBusy($submitBtn);
                    alert(res.message ? res.message : 'Unable to update schedule');
                },
                error: function () {
                    resetButtonBusy($submitBtn);
                    alert('Unable to update schedule');
                }
            });
        });

        $(document).on('click', '.js-delete-schedule', function () {
            var $button = $(this);
            if (!window.confirm('Delete this schedule?')) {
                return;
            }

            setButtonBusy($button, 'Deleting...');

            $.ajax({
                url: base_url + 'Schedule/ajax_delete_schedule',
                type: 'POST',
                dataType: 'json',
                data: {
                    schedule_id: $button.data('id')
                },
                success: function (res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                        return;
                    }

                    resetButtonBusy($button);
                    alert(res.message ? res.message : 'Unable to delete schedule');
                },
                error: function () {
                    resetButtonBusy($button);
                    alert('Unable to delete schedule');
                }
            });
        });

        $('#delegationForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var isEdit = $.trim($('#delegation_request_id').val()) !== '' && $('#delegation_form_mode').val() === 'edit';
            setButtonBusy($submitBtn, 'Submitting...');

            $.ajax({
                url: base_url + (isEdit ? 'Schedule/ajax_update_delegation_request' : 'Schedule/ajax_save_delegation'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    var res = response;

                    if (res.status === 'success') {
                        alert(res.message);
                        resetDelegationRequestForm();
                        $('#delegationModal').modal('hide');
                        location.reload();
                        return;
                    }

                    resetButtonBusy($submitBtn);
                    alert(res.message ? res.message : 'Error saving delegation');
                },
                error: function () {
                    resetButtonBusy($submitBtn);
                    alert('Error saving delegation');
                }
            });
        });

        $('#delegationModal').on('show.bs.modal', function (event) {
            var $trigger = $(event.relatedTarget || []);

            if ($trigger.hasClass('js-edit-delegation-request')) {
                openDelegationEditForm($trigger);
                return;
            }

            if ($trigger.hasClass('js-open-schedule-delegation')) {
                openDelegationCreateFromSchedule($trigger);
                return;
            }

            resetDelegationRequestForm();
        });

        $('#delegationModal').on('hidden.bs.modal', function () {
            resetDelegationRequestForm();
        });

        $('#delegation_original_user_id').on('change', function () {
            var userId = parseInt($(this).val(), 10);
            fetchDelegationTasksForUser(userId, 0);
        });

        $(document).on('click', '.js-delete-delegation-request', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }

            if (!window.confirm('Delete this delegation request?')) {
                return;
            }

            var $button = $(this);
            setButtonBusy($button, 'Deleting...');

            $.ajax({
                url: base_url + 'Schedule/ajax_delete_delegation_request',
                type: 'POST',
                dataType: 'json',
                data: {
                    delegation_id: $button.data('id')
                },
                success: function (res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                        return;
                    }

                    resetButtonBusy($button);
                    alert(res.message ? res.message : 'Unable to delete delegation request');
                },
                error: function () {
                    resetButtonBusy($button);
                    alert('Unable to delete delegation request');
                }
            });
        });

        $(document).on('click', '.js-delegation-action', function () {
            var $button = $(this);
            var approvalStatus = $(this).data('status');
            var remarks = window.prompt(
                approvalStatus === 'approved'
                    ? 'Optional approval remarks'
                    : 'Reason for rejection',
                ''
            );

            if (remarks === null) {
                return;
            }

            if (approvalStatus === 'rejected' && $.trim(remarks) === '') {
                alert('Rejection reason is required');
                return;
            }

            setButtonBusy($button, approvalStatus === 'approved' ? 'Approving...' : 'Rejecting...');

            $.ajax({
                url: base_url + 'Schedule/ajax_update_delegation_status',
                type: 'POST',
                data: {
                    delegation_id: $button.data('id'),
                    approval_status: approvalStatus,
                    approval_remarks: remarks
                },
                dataType: 'json',
                success: function (response) {
                    var res = response;

                    if (res.status === 'success') {
                        alert(res.message);
                        loadTodayTaskBoard();
                        return;
                    }

                    resetButtonBusy($button);
                    alert(res.message ? res.message : 'Unable to update delegation');
                },
                error: function () {
                    resetButtonBusy($button);
                    alert('Unable to update delegation');
                }
            });
        });

        $(document).on('click', '.js-open-delegation', function (e) {
            var $button = $(this);
            var $target = $(e.target);

            if ($target.closest('.js-edit-delegation-request, .js-delete-delegation-request, .js-manage-delegation-action, .js-close-delegation-card, .schedule-delegation-inline-detail').length) {
                return;
            }

            loadDelegationDetail($button);
        });

        $(document).on('click', '.schedule-delegation-inline-detail, .schedule-delegation-inline-detail .form-control', function (e) {
            e.stopPropagation();
        });

        $('#openDelegationManager').on('click', function () {
            var $first = $('.js-open-delegation[data-can-manage="1"]').first();
            if (!$first.length) {
                $first = $('.js-open-delegation').first();
            }
            if (!$first.length) {
                return;
            }

            loadDelegationDetail($first);
        });

        $(document).on('click', '.js-close-delegation-card', function (e) {
            e.stopPropagation();
            resetDelegationCard($(this).closest('.js-open-delegation'));
        });

        $(document).on('click', '.js-manage-delegation-action', function (e) {
            e.stopPropagation();
            var $button = $(this);
            var $card = $button.closest('.js-open-delegation');
            var delegationId = $card.data('id');
            var approvalStatus = $button.data('status');
            var remarks = $.trim($card.find('.js-delegation-remarks').val());

            if (!delegationId) {
                return;
            }

            if (approvalStatus === 'rejected' && remarks === '') {
                alert('Reason is required for rejection.');
                return;
            }

            setButtonBusy($button, approvalStatus === 'pending' ? 'Rolling Back...' : 'Saving...');

            $.ajax({
                url: base_url + 'Schedule/ajax_manage_delegation',
                type: 'POST',
                dataType: 'json',
                data: {
                    delegation_id: delegationId,
                    approval_status: approvalStatus,
                    approval_remarks: remarks
                },
                success: function (res) {
                    if (res.status !== 'success') {
                        resetButtonBusy($button);
                        alert(res.message ? res.message : 'Unable to update delegation.');
                        return;
                    }

                    var statusText = approvalStatus.charAt(0).toUpperCase() + approvalStatus.slice(1);
                    $card.find('.js-delegation-badge')
                        .attr('class', 'schedule-badge js-delegation-badge ' + getDelegationBadgeClass(approvalStatus))
                        .text(statusText);

                    if (approvalStatus === 'pending') {
                        resetDelegationCard($card);
                    } else {
                        loadDelegationDetail($card);
                    }

                    alert(res.message);
                    resetButtonBusy($button);
                },
                error: function () {
                    resetButtonBusy($button);
                    alert('Unable to update delegation.');
                }
            });
        });

        $(document).on('click', '.js-complete-schedule', function () {
            var $button = $(this);
            setButtonBusy($button, 'Completing...');

            $.ajax({
                url: base_url + 'Schedule/ajax_complete_today_task',
                type: 'POST',
                data: {
                    schedule_task_id: $button.data('id'),
                    execution_date: $button.data('date')
                },
                dataType: 'json',
                success: function (response) {
                    var res = response;

                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                        return;
                    }

                    resetButtonBusy($button);
                    alert(res.message ? res.message : 'Unable to complete task');
                },
                error: function () {
                    resetButtonBusy($button);
                    alert('Unable to complete task');
                }
            });
        });

        syncScheduleAssignMode();
        showOverdueTaskAlert();
    });
})(jQuery);
