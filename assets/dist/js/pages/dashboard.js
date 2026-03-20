(function ($, window, document) {
    var instanceKey = '__trsDashboardPageInstance';
    var eventNamespace = '.trsDashboard';

    function normalizeScope(scope) {
        var normalized = (scope || '').toString().toLowerCase();
        return ['mine', 'all', 'assigned'].indexOf(normalized) !== -1 ? normalized : 'all';
    }

    function hasDashboardContext(root) {
        return $(root || document).find('#dashboardScopeSelect, .trs-section-workspace[data-section-shell="dashboard-workspace"]').length > 0;
    }

    function getDashboardActiveSection() {
        return $('.trs-section-workspace[data-section-shell="dashboard-workspace"]').attr('data-active-section') || 'overview';
    }

    function getDashboardScope() {
        var $select = $('#dashboardScopeSelect');
        if ($select.length) {
            return normalizeScope($select.val());
        }

        var $mainContent = $('#mainContent');
        var scope = $mainContent.data('dashboard-scope') || $mainContent.attr('data-dashboard-scope') || 'all';
        return normalizeScope(scope);
    }

    function setDashboardScope(scope) {
        var normalized = normalizeScope(scope);
        var $mainContent = $('#mainContent');

        if ($mainContent.length) {
            // Keep both attr + jQuery cache in sync after AJAX content swaps.
            $mainContent.attr('data-dashboard-scope', normalized);
            $mainContent.data('dashboard-scope', normalized);
        }

        $('#dashboardScopeSelect').val(normalized);
        $('#dashboardTicketScopeToggle .js-dashboard-scope-btn').removeClass('active');
        $('#dashboardTicketScopeToggle .js-dashboard-scope-btn[data-scope="' + normalized + '"]').addClass('active');
        $('input[name="dashboard_scope"]').val(normalized);
    }

    function buildDashboardUrl(overrides, targetSection) {
        var currentUrl = new URL(window.location.href);
        var params = currentUrl.searchParams;
        var nextSection = targetSection || getDashboardActiveSection();
        var scheduleUserValue = $('#dashboardScheduleFilterForm select[name="schedule_user_id"]').val();
        var ticketStatusValue = $('#dashboardRecentFilterForm select[name="dashboard_ticket_status"]').val();
        var dashboardScope = getDashboardScope();

        if (typeof scheduleUserValue !== 'undefined') {
            params.set('schedule_user_id', scheduleUserValue || 'all');
        }

        if (typeof ticketStatusValue !== 'undefined') {
            params.set('dashboard_ticket_status', ticketStatusValue || '0');
        }

        params.set('dashboard_scope', dashboardScope);

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

    function navigateDashboard(url) {
        window.location.href = url;
    }

    function bindEvents() {
        $(document).off(eventNamespace);
        $(window).off(eventNamespace);

        if (!hasDashboardContext(document)) {
            return;
        }

        setDashboardScope(getDashboardScope());

        $(document).on('click' + eventNamespace, '#dashboardTicketScopeToggle .js-dashboard-scope-btn', function (e) {
            e.preventDefault();
            var nextScope = normalizeScope($(this).data('scope'));
            setDashboardScope(nextScope);
            refreshDashboard(buildDashboardUrl({ dashboard_scope: nextScope }, getDashboardActiveSection()));
        });

        $(document).on('change' + eventNamespace, '#dashboardScopeSelect', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var nextScope = normalizeScope($(this).val());
            setDashboardScope(nextScope);
            navigateDashboard(buildDashboardUrl({ dashboard_scope: nextScope }, getDashboardActiveSection()));
        });

        $(document).on('change' + eventNamespace, '.js-dashboard-ticket-filter', function (e) {
            e.preventDefault();
            refreshDashboard(buildDashboardUrl({
                dashboard_ticket_status: $(this).val() || '0'
            }, 'activity'));
        });

        $(document).on('change' + eventNamespace, '.js-dashboard-schedule-filter', function (e) {
            e.preventDefault();
            refreshDashboard(buildDashboardUrl({
                schedule_user_id: $(this).val() || 'all'
            }, 'overview'));
        });

        $(document).on('click' + eventNamespace, '.js-dashboard-status-link', function (e) {
            e.preventDefault();
            var $link = $(this);
            refreshDashboard(buildDashboardUrl({
                dashboard_ticket_status: $link.data('status') || '0'
            }, $link.data('target-section') || 'activity'), $link);
        });

        $(document).on('click' + eventNamespace, '#dashboardRecentRefresh, #dashboardScheduleRefresh, #dashboardPageRefresh', function (e) {
            e.preventDefault();
            refreshDashboard(buildDashboardUrl({}, getDashboardActiveSection()), $(this));
        });

        $(window).on('trs:main-content-refreshed' + eventNamespace, function (event, payload) {
            var $mainContent = payload && payload.$mainContent ? payload.$mainContent : $('#mainContent');
            if (!$mainContent.length || !$mainContent.find('#dashboardScopeSelect').length) {
                return;
            }

            setDashboardScope($mainContent.find('#dashboardScopeSelect').val());
        });
    }

    function destroy() {
        $(document).off(eventNamespace);
        $(window).off(eventNamespace);
    }

    if (window[instanceKey] && typeof window[instanceKey].destroy === 'function') {
        window[instanceKey].destroy();
    }

    window[instanceKey] = {
        destroy: destroy,
        init: bindEvents
    };

    $(function () {
        bindEvents();
    });
})(jQuery, window, document);
