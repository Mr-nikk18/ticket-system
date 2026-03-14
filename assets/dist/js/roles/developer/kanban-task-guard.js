(function (window, $) {
    var nativeAlert = window.alert;
    var taskAccess = {
        canManage: true
    };

    function isTaskEndpoint(url) {
        return url.indexOf('update_task_status') !== -1 ||
            url.indexOf('add_task') !== -1 ||
            url.indexOf('update_task_title') !== -1 ||
            url.indexOf('update_task_position') !== -1;
    }

    function computeTaskAccess(ticket) {
        var currentUserId = parseInt((window.AppConfig && window.AppConfig.userId) || 0, 10);
        var currentRoleId = parseInt((window.AppConfig && window.AppConfig.roleId) || 0, 10);
        var currentDepartmentId = parseInt((window.AppConfig && window.AppConfig.departmentId) || 0, 10);
        var ownerId = parseInt(ticket.user_id || 0, 10);
        var assignedEngineerId = parseInt(ticket.assigned_engineer_id || 0, 10);
        var isItHead = currentDepartmentId === 2 && currentRoleId === 2;

        taskAccess.canManage = ownerId !== currentUserId && (isItHead || assignedEngineerId === currentUserId);
    }

    function applyTaskAccess() {
        if (taskAccess.canManage) {
            return;
        }

        $('#ticketDetailContent .task-checkbox').prop('disabled', true);
    }

    function suppressAlerts() {
        window.alert = function () {
            return;
        };
    }

    function blockIfReadonly(event) {
        if (taskAccess.canManage) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }

    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (!settings || !settings.url) {
            return;
        }

        if (settings.url.indexOf('get_ticket_details') !== -1) {
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
            if (response && response.ticket) {
                computeTaskAccess(response.ticket);
                setTimeout(applyTaskAccess, 0);
            }
        }
    });

    $(document).on('click', '.task-checkbox', blockIfReadonly);
    $(document).on('change', '.task-checkbox', blockIfReadonly);

    // Prevent footer callbacks from showing alert for blocked task actions.
    $(document).ajaxError(function (event, xhr, settings) {
        if (!settings || !settings.url || !isTaskEndpoint(settings.url) || taskAccess.canManage) {
            return;
        }

        event.stopImmediatePropagation();
    });

    suppressAlerts();

    $(window).on('unload', function () {
        window.alert = nativeAlert;
    });
})(window, jQuery);
