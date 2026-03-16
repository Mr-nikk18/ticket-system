(function ($, window) {
    function getDashboardActiveSection() {
        return $('.trs-section-workspace[data-section-shell="dashboard-workspace"]').attr('data-active-section') || 'overview';
    }

    function buildDashboardUrl(overrides, targetSection) {
        var currentUrl = new URL(window.location.href);
        var params = currentUrl.searchParams;
        var nextSection = targetSection || getDashboardActiveSection();
        var scheduleUserValue = $('#dashboardScheduleFilterForm select[name="schedule_user_id"]').val();
        var ticketStatusValue = $('#dashboardRecentFilterForm select[name="dashboard_ticket_status"]').val();

        if (typeof scheduleUserValue !== 'undefined') {
            params.set('schedule_user_id', scheduleUserValue || 'all');
        }

        if (typeof ticketStatusValue !== 'undefined') {
            params.set('dashboard_ticket_status', ticketStatusValue || '0');
        }

        $.each(overrides || {}, function (key, value) {
            params.set(key, value);
        });

        var queryString = params.toString();
        return currentUrl.pathname + (queryString ? '?' + queryString : '') + '#dashboard-workspace=' + encodeURIComponent(nextSection || 'overview');
    }

    function refreshDashboard(url, $button) {
        if (typeof window.refreshMainContent === 'function') {
            window.refreshMainContent(url, $button);
            return;
        }

        window.location.href = url;
    }

    $(function () {
        $(document).on('change', '.js-dashboard-ticket-filter', function (e) {
            e.preventDefault();
            refreshDashboard(buildDashboardUrl({
                dashboard_ticket_status: $(this).val() || '0'
            }, 'activity'));
        });

        $(document).on('change', '.js-dashboard-schedule-filter', function (e) {
            e.preventDefault();
            refreshDashboard(buildDashboardUrl({
                schedule_user_id: $(this).val() || 'all'
            }, 'overview'));
        });

        $(document).on('click', '.js-dashboard-status-link', function (e) {
            e.preventDefault();
            var $link = $(this);
            refreshDashboard(buildDashboardUrl({
                dashboard_ticket_status: $link.data('status') || '0'
            }, $link.data('target-section') || 'activity'), $link);
        });

        $(document).on('click', '#dashboardRecentRefresh, #dashboardScheduleRefresh, #dashboardPageRefresh', function (e) {
            e.preventDefault();
            refreshDashboard(buildDashboardUrl({}, getDashboardActiveSection()), $(this));
        });
    });
})(jQuery, window);
