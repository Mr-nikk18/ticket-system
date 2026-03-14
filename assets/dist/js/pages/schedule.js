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

    function getDelegationBadgeClass(status) {
        if (status === 'approved') {
            return 'schedule-badge--active';
        }

        if (status === 'rejected') {
            return 'schedule-badge--inactive';
        }

        return 'schedule-badge--pending';
    }

    function renderDelegationDetail(data) {
        if (!$('#delegationManagePanel').length) {
            return;
        }
        $('#delegationManagePlaceholder').addClass('d-none');
        $('#delegationManageContent').removeClass('d-none');
        $('#delegationRequestList').addClass('d-none');
        $('#delegationManagePanel').removeClass('d-none');
        $('#delegationManageId').val(data.id || '');
        $('#delegationManageRemarks').val(data.approval_remarks || '');
        $('#delegationDetailStatus')
            .attr('class', 'schedule-badge ' + getDelegationBadgeClass(data.approval_status))
            .text((data.approval_status || 'pending').charAt(0).toUpperCase() + (data.approval_status || 'pending').slice(1));
        $('#delegationDetailWindow').text(formatDelegationDate(data.start_date) + ' - ' + formatDelegationDate(data.end_date));
        $('#delegationDetailRequester').text(data.created_by_name || '-');
        $('#delegationDetailApprover').text(data.approved_by_name || '-');
        $('#delegationDetailApprovedAt').text(formatDelegationDateTime(data.approved_at));
        $('#delegationDetailTitle').text((data.original_user_name || '-') + ' to ' + (data.delegated_user_name || '-'));
        $('#delegationManageActions').toggleClass('d-none', !data.can_manage);
    }

    function loadDelegationDetail(delegationId) {
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

                renderDelegationDetail(res.data);
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

    $(function () {
        function loadTodayTaskBoard() {
            $.ajax({
                url: base_url + 'Schedule/ajax_today_task_board',
                type: 'GET',
                data: {
                    schedule_user_id: $('#scheduleBoardUserFilter').val()
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        $('#todayTaskBoardBody').html(response.html);
                    }
                }
            });
        }

        $('input[name="assign_type"]').on('change', syncScheduleAssignMode);

        $('#subordinateSelect').on('change', function () {
            $('#assigned_user_id').val($(this).val());
        });

        $('#scheduleBoardUserFilter').on('change', function (e) {
            e.preventDefault();
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
            var $submitBtn = $(this).find('button[type="submit"]');
            setButtonBusy($submitBtn, 'Submitting...');

            $.ajax({
                url: base_url + 'Schedule/ajax_save_delegation',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    var res = response;

                    if (res.status === 'success') {
                        alert(res.message);
                        $('#delegationForm')[0].reset();
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
                        location.reload();
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

        $(document).on('click', '.js-open-delegation', function () {
            var $button = $(this);
            $('.js-open-delegation').removeClass('is-active');
            $button.addClass('is-active');
            loadDelegationDetail($button.data('id'));
        });

        $('#openDelegationManager').on('click', function () {
            var $first = $('.js-open-delegation').first();
            if (!$first.length) {
                return;
            }

            $('.js-open-delegation').removeClass('is-active');
            $first.addClass('is-active');
            loadDelegationDetail($first.data('id'));
        });

        $('#closeDelegationManager').on('click', function () {
            $('#delegationManageId').val('');
            $('#delegationManagePanel').addClass('d-none');
            $('#delegationManageContent').addClass('d-none');
            $('#delegationManagePlaceholder').removeClass('d-none');
            $('#delegationRequestList').removeClass('d-none');
            $('.js-open-delegation').removeClass('is-active');
        });

        $(document).on('click', '.js-manage-delegation', function () {
            var $button = $(this);
            var delegationId = $('#delegationManageId').val();
            var approvalStatus = $button.data('status');
            var remarks = $.trim($('#delegationManageRemarks').val());

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

                    loadDelegationDetail(delegationId);

                    var $activeCard = $('.js-open-delegation.is-active');
                    if ($activeCard.length) {
                        $activeCard.find('.schedule-badge')
                            .attr('class', 'schedule-badge ' + getDelegationBadgeClass(approvalStatus))
                            .text(approvalStatus.charAt(0).toUpperCase() + approvalStatus.slice(1));
                    }

                    if (approvalStatus === 'pending') {
                        $('#closeDelegationManager').trigger('click');
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
    });
})(jQuery);
