(function (window, $) {
    var config = window.DeveloperPerformanceConfig || {};
    var instanceKey = '__trsDeveloperPerformanceInstance';
    if (window[instanceKey] && typeof window[instanceKey].destroy === 'function') {
        window[instanceKey].destroy();
    }
    var overviewChart = null;
    var detailStatusChart = null;
    var detailVolumeChart = null;
    var hierarchyLoaded = false;
    var selectedHierarchyUserId = null;
    var selectedHierarchyUserName = '';
    var ticketScope = (config.initialTicketScope || 'all').toString().toLowerCase() === 'mine' ? 'mine' : 'all';
    var gridRequest = null;
    var detailRequest = null;
    var hierarchyRequest = null;
    var gridIntervalId = null;
    var sectionMeta = {
        queue: {
            title: 'Review Queue',
            message: 'Developer card click karo aur full-width detail workspace AJAX se open hoga.'
        },
        detail: {
            title: 'Performance Detail',
            message: 'Selected developer ka full performance breakdown, charts aur ticket tables yahan load honge.'
        },
        hierarchy: {
            title: 'Hierarchy Manager',
            message: 'Reporting structure, node inspection aur reassignment controls yahan manage honge.'
        }
    };

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function abortRequest(request) {
        if (request && request.readyState !== 4) {
            request.abort();
        }
    }

    function applyDeveloperFilters() {
        var search = ($('#developerPerformanceSearch').val() || '').toLowerCase().trim();
        var company = ($('#developerCompanyFilter').val() || '').toLowerCase().trim();
        var department = ($('#developerPerformanceDepartmentFilter').val() || '').toLowerCase().trim();

        $('#developerPerformanceGrid .developer-performance-item').each(function () {
            var $item = $(this);
            var name = ($item.data('name') || '').toString();
            var itemCompany = ($item.data('company') || '').toString();
            var itemDepartment = ($item.data('department') || '').toString();

            var matchesSearch = !search || name.indexOf(search) !== -1 || itemCompany.indexOf(search) !== -1;
            var matchesCompany = !company || itemCompany === company;
            var matchesDepartment = !department || itemDepartment === department;

            $item.toggle(matchesSearch && matchesCompany && matchesDepartment);
        });
    }

    function destroyChart(chartInstance) {
        if (chartInstance && typeof chartInstance.destroy === 'function') {
            chartInstance.destroy();
        }
    }

    function renderOverviewChart(overview) {
        var canvas = document.getElementById('perfOverviewStatusChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        destroyChart(overviewChart);
        overviewChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    data: [
                        parseInt(overview.open_tickets || 0, 10),
                        parseInt(overview.in_progress_tickets || 0, 10),
                        parseInt(overview.resolved_tickets || 0, 10),
                        parseInt(overview.closed_tickets || 0, 10)
                    ],
                    backgroundColor: ['#d97706', '#1d4ed8', '#15803d', '#1f2937'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '68%',
                maintainAspectRatio: false
            }
        });
    }

    function updateOverviewCounters(overview, shouldRenderChart) {
        $('#perfOverviewTotalReports').text(parseInt(overview.total_reports || 0, 10));
        $('#perfOverviewAssignedByYou').text(parseInt(overview.reviewer_assigned || 0, 10));
        $('#perfOverviewAccepted').text(parseInt(overview.accepted_total || 0, 10));
        $('#perfOverviewDelegation').text(parseInt(overview.delegation_absence || 0, 10));
        $('#perfOverviewLeaveMeta').text(parseInt(overview.leave_days_total || 0, 10) + ' leave days / ' + parseInt(overview.present_days_total || 0, 10) + ' present days');
    }

    function updateTicketOverview(ticketOverview, shouldRenderChart) {
        ticketOverview = ticketOverview || {};
        var openCnt = parseInt(ticketOverview.open_tickets || 0, 10);
        var inProgressCnt = parseInt(ticketOverview.in_progress_tickets || 0, 10);
        var resolvedCnt = parseInt(ticketOverview.resolved_tickets || 0, 10);
        var closedCnt = parseInt(ticketOverview.closed_tickets || 0, 10);
        var totalCnt = parseInt(ticketOverview.total_tickets || 0, 10);

        if (!totalCnt) {
            totalCnt = openCnt + inProgressCnt + resolvedCnt + closedCnt;
        }

        $('#perfTicketScopeLabel').text(ticketScope === 'mine' ? 'Mine' : 'All');
        $('#perfTicketTotal').text(totalCnt);

        if (shouldRenderChart) {
            renderOverviewChart({
                open_tickets: openCnt,
                in_progress_tickets: inProgressCnt,
                resolved_tickets: resolvedCnt,
                closed_tickets: closedCnt
            });
        }
    }

    function setTicketScope(nextScope, options) {
        options = options || {};
        nextScope = (nextScope || '').toString().toLowerCase() === 'mine' ? 'mine' : 'all';
        if (nextScope === ticketScope) {
            return;
        }

        ticketScope = nextScope;
        $('#perfTicketScopeSelect').val(ticketScope);
        $('#perfTicketScopeToggle .perf-scope-btn').removeClass('is-active');
        $('#perfTicketScopeToggle .perf-scope-btn[data-scope="' + ticketScope + '"]').addClass('is-active');
        $('#perfTicketScopeLabel').text(ticketScope === 'mine' ? 'Mine' : 'All');

        if (options.refresh !== false) {
            refreshDeveloperGrid();
        }
    }

    function renderRows(items, columns) {
        if (!items || !items.length) {
            return '<tr><td colspan="' + columns + '" class="text-center text-muted py-4">No records found</td></tr>';
        }

        return items.join('');
    }

    function renderDeveloperCards(developers) {
        if (!developers || !developers.length) {
            return '<div class="col-12"><div class="perf-panel text-center text-muted py-5">No developer data available for this period.</div></div>';
        }

        return developers.map(function (dev) {
            var total = parseInt(dev.total_tickets || 0, 10);
            var assigned = parseInt(dev.assigned_tickets || 0, 10);
            var accepted = parseInt(dev.accepted_tickets || 0, 10);
            var resolved = parseInt(dev.resolved_tickets || 0, 10);
            var pending = parseInt(dev.pending_tickets || 0, 10);
            var reviewerAssignedTickets = parseInt(dev.reviewer_assigned_tickets || 0, 10);
            var directReports = parseInt(dev.direct_reports || 0, 10);
            var leaveDays = parseInt(dev.leave_days || 0, 10);
            var presentDays = parseInt(dev.present_days || 0, 10);
            var avgResolutionHours = parseFloat(dev.avg_resolution_hours || 0);
            var investedHours = parseFloat(dev.invest_hours || 0);
            var score = total > 0 ? Math.round((resolved / total) * 100) : 0;
            var assignedPct = total > 0 ? Math.round((assigned / total) * 100) : 0;
            var acceptedPct = total > 0 ? Math.round((accepted / total) * 100) : 0;
            var resolvedPct = total > 0 ? Math.round((resolved / total) * 100) : 0;
            var pendingPct = total > 0 ? Math.round((pending / total) * 100) : 0;
            var initials = (dev.name || '?').trim().charAt(0).toUpperCase();

            return '' +
                '<div class="col-xl-6 developer-performance-item" data-name="' + escapeHtml((dev.name || '').toLowerCase()) + '" data-company="' + escapeHtml(((dev.company_name || 'no company')).toLowerCase()) + '" data-department="' + escapeHtml(((dev.department_name || 'no department')).toLowerCase()) + '">' +
                '<div class="perf-dev-card" data-developer-id="' + parseInt(dev.user_id || 0, 10) + '" data-developer-name="' + escapeHtml(dev.name || 'Unknown') + '">' +
                '<div class="perf-dev-glow"></div>' +
                '<div class="perf-dev-header">' +
                '<div class="perf-dev-avatar">' + escapeHtml(initials) + '</div>' +
                '<div class="perf-dev-meta"><h4>' + escapeHtml(dev.name || 'Unknown') + '</h4><div class="perf-dev-role">' + escapeHtml(dev.company_name || 'TRS') + ' / ' + escapeHtml(dev.department_name || 'No Department') + '</div></div>' +
                '<div class="perf-dev-score">' + score + '%</div>' +
                '</div>' +
                '<div class="perf-chip-row">' +
                '<span class="perf-chip soft">You assigned ' + reviewerAssignedTickets + '</span>' +
                '<span class="perf-chip dark">Accepted ' + accepted + '</span>' +
                '<span class="perf-chip slate">Leave ' + leaveDays + 'd</span>' +
                '<span class="perf-chip green">Present ' + presentDays + 'd</span>' +
                '<span class="perf-chip slate">Avg Res ' + Math.round(avgResolutionHours) + 'h</span>' +
                '<span class="perf-chip green">Invested ' + Math.round(investedHours) + 'h</span>' +
                '<span class="perf-chip amber">Reports ' + directReports + '</span>' +
                '</div>' +
                '<div class="perf-metric-list">' +
                '<div class="perf-metric-row"><span>Assigned</span><div class="perf-metric-bar"><span style="width: ' + assignedPct + '%"></span></div><strong>' + assigned + '</strong></div>' +
                '<div class="perf-metric-row"><span>Accepted</span><div class="perf-metric-bar metric-blue"><span style="width: ' + acceptedPct + '%"></span></div><strong>' + accepted + '</strong></div>' +
                '<div class="perf-metric-row"><span>Resolved</span><div class="perf-metric-bar metric-green"><span style="width: ' + resolvedPct + '%"></span></div><strong>' + resolved + '</strong></div>' +
                '<div class="perf-metric-row"><span>Pending</span><div class="perf-metric-bar metric-amber"><span style="width: ' + pendingPct + '%"></span></div><strong>' + pending + '</strong></div>' +
                '</div>' +
                '<div class="perf-dev-footer">' +
                '<div><span class="perf-mini-label">Current load</span><strong>' + total + ' tickets</strong></div>' +
                '<div><span class="perf-mini-label">Open balance</span><strong>' + pending + '</strong></div>' +
                '<div class="perf-link-label">Click to inspect</div>' +
                '</div>' +
                '</div>' +
                '</div>';
        }).join('');
    }

    function updateSideContext(target, overrides) {
        var base = sectionMeta[target] || sectionMeta.queue;
        var title = (overrides && overrides.title) ? overrides.title : base.title;
        var message = (overrides && overrides.message) ? overrides.message : base.message;

        $('#perfSideContextTitle').text(title);
        $('#perfSideContextMeta').text(message);
    }

    function syncWorkspaceHash(target) {
        if (!target || !window.history || typeof window.history.replaceState !== 'function') {
            return;
        }

        window.history.replaceState(null, document.title, window.location.pathname + window.location.search + '#perf-' + target);
    }

    function getWorkspaceFromHash() {
        var hash = (window.location.hash || '').replace('#', '');
        if (hash.indexOf('perf-') !== 0) {
            return 'queue';
        }

        hash = hash.replace('perf-', '');
        return sectionMeta[hash] ? hash : 'queue';
    }

    function refreshDeveloperGrid(callback) {
        abortRequest(gridRequest);
        gridRequest = $.ajax({
            url: config.dataUrl,
            type: 'GET',
            dataType: 'json',
            data: { year: config.year, ticket_scope: ticketScope }
        }).done(function (response) {
            if (!response || response.status !== true) {
                return;
            }

            updateOverviewCounters(response.overview || config.initialOverview || {}, false);
            updateTicketOverview(response.ticket_overview || config.initialTicketOverview || {}, true);
            $('#developerPerformanceGrid').html(renderDeveloperCards(response.developers || []));
            applyDeveloperFilters();

            if (typeof callback === 'function') {
                callback(response);
            }
        }).always(function () {
            gridRequest = null;
        });
    }

    function renderDeveloperDetail(payload) {
        var data = payload.data || {};
        var dev = data.developer || {};
        var summary = data.summary || {};
        var assigners = data.assignment_breakdown || [];
        var currentTickets = data.current_tickets || [];
        var acceptedTickets = data.accepted_tickets || [];
        var assignedTickets = data.assigned_tickets || [];
        var subordinates = data.subordinates || [];

        $('#perfDetailHeading').text(dev.name || 'Developer Detail');

        var statCards = '' +
            '<div class="perf-detail-grid">' +
            '<div class="perf-detail-stat"><div class="label">Total Tickets</div><div class="value">' + parseInt(summary.total_tickets || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Assigned By You</div><div class="value">' + parseInt(summary.reviewer_assigned_tickets || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Accepted By Self</div><div class="value">' + parseInt(summary.accepted_tickets || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Delegations In</div><div class="value">' + parseInt(summary.incoming_delegations || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Delegations Out</div><div class="value">' + parseInt(summary.outgoing_delegations || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Active Delegations</div><div class="value">' + parseInt(summary.active_delegations || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Leave Days</div><div class="value">' + parseInt(summary.leave_days || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Present Days</div><div class="value">' + parseInt(summary.present_days || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Avg Resol (hrs)</div><div class="value">' + parseFloat(summary.avg_resolution_hours || 0).toFixed(1) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Invested Hours</div><div class="value">' + parseFloat(summary.invest_hours || 0).toFixed(1) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Direct Reports</div><div class="value">' + parseInt(summary.direct_reports || 0, 10) + '</div></div>' +
            '</div>';

        var assignerHtml = assigners.length
            ? assigners.map(function (row) {
                return '<div class="perf-assigner-item"><div><strong>' + escapeHtml(row.assigner_name) + '</strong><small>Assignment source</small></div><span class="perf-assigner-count">' + parseInt(row.assign_count || 0, 10) + '</span></div>';
            }).join('')
            : '<div class="text-muted">No assignment source data found.</div>';

        var subordinateHtml = subordinates.length
            ? subordinates.map(function (row) {
                return '<div class="perf-subordinate-item"><div class="perf-subordinate-meta"><strong>' + escapeHtml(row.name) + '</strong><small>' + escapeHtml(row.role_name || 'User') + ' / ' + escapeHtml(row.company_name || 'TRS') + '</small></div><span class="perf-status-chip">Report</span></div>';
            }).join('')
            : '<div class="text-muted">No direct reports under this member.</div>';

        var currentRows = renderRows(currentTickets.map(function (ticket) {
            return '<tr>' +
                '<td>#' + parseInt(ticket.ticket_id || 0, 10) + '</td>' +
                '<td>' + escapeHtml(ticket.title) + '</td>' +
                '<td>' + escapeHtml(ticket.owner_name || 'N/A') + '</td>' +
                '<td><span class="perf-status-chip">' + escapeHtml(ticket.status_name || 'N/A') + '</span></td>' +
                '<td>' + (parseInt(ticket.can_resolve || 0, 10) === 1 ? 'Ready' : 'Blocked by task') + '</td>' +
                '<td>' + (parseInt(ticket.days_open || 0, 10) >= 0 ? parseInt(ticket.days_open || 0, 10) + ' days' : '0 days') + '</td>' +
                '<td>' + escapeHtml(ticket.created_at || '') + '</td>' +
                '</tr>';
        }), 7);

        var acceptedRows = renderRows(acceptedTickets.map(function (ticket) {
            return '<tr>' +
                '<td>#' + parseInt(ticket.ticket_id || 0, 10) + '</td>' +
                '<td>' + escapeHtml(ticket.title) + '</td>' +
                '<td>' + escapeHtml(ticket.owner_name || 'N/A') + '</td>' +
                '<td><span class="perf-status-chip">' + escapeHtml(ticket.status_name || 'N/A') + '</span></td>' +
                '<td>' + escapeHtml(ticket.accepted_at || '') + '</td>' +
                '</tr>';
        }), 5);

        var assignedRows = renderRows(assignedTickets.map(function (ticket) {
            return '<tr>' +
                '<td>#' + parseInt(ticket.ticket_id || 0, 10) + '</td>' +
                '<td>' + escapeHtml(ticket.title) + '</td>' +
                '<td>' + escapeHtml(ticket.assigner_name || 'System / Auto') + '</td>' +
                '<td>' + escapeHtml(ticket.action_type || '') + '</td>' +
                '<td><span class="perf-status-chip">' + escapeHtml(ticket.status_name || 'N/A') + '</span></td>' +
                '<td>' + escapeHtml(ticket.assigned_at || '') + '</td>' +
                '</tr>';
        }), 6);

        return '' +
            '<div class="mb-3">' +
            '<span class="perf-mini-label">Detailed review for ' + escapeHtml(config.fyLabel || '') + '</span>' +
            '<h4 class="mb-1">' + escapeHtml(dev.name || 'Unknown') + '</h4>' +
            '<div class="text-muted">' + escapeHtml(dev.company_name || 'TRS') + ' / ' + escapeHtml(dev.department_name || 'No Department') + '</div>' +
            '</div>' +
            statCards +
            '<div class="perf-section-block">' +
            '<div class="perf-chart-grid">' +
            '<div class="perf-chart-card"><span class="perf-mini-label">Status mix</span><canvas id="perfDetailStatusChart" height="220"></canvas></div>' +
            '<div class="perf-chart-card"><span class="perf-mini-label">Monthly ticket volume</span><canvas id="perfDetailVolumeChart" height="220"></canvas></div>' +
            '</div>' +
            '</div>' +
            '<div class="perf-section-block">' +
            '<div class="row">' +
            '<div class="col-lg-6 mb-3"><div class="perf-form-title">Assigned By</div><div class="perf-assigner-list">' + assignerHtml + '</div></div>' +
            '<div class="col-lg-6 mb-3"><div class="perf-form-title">Hierarchy Under Member</div><div class="perf-subordinate-list">' + subordinateHtml + '</div></div>' +
            '</div>' +
            '</div>' +
            '<div class="perf-section-block"><div class="perf-form-title">Current Ticket State</div><div class="perf-table-wrap"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ticket</th><th>Title</th><th>Owner</th><th>Status</th><th>Readiness</th><th>Days Open</th><th>Created</th></tr></thead><tbody>' + currentRows + '</tbody></table></div></div></div>' +
            '<div class="perf-section-block"><div class="perf-form-title">Accepted Tickets</div><div class="perf-table-wrap"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ticket</th><th>Title</th><th>Owner</th><th>Status</th><th>Accepted At</th></tr></thead><tbody>' + acceptedRows + '</tbody></table></div></div></div>' +
            '<div class="perf-section-block"><div class="perf-form-title">Department Status Backlog</div><div class="perf-table-wrap"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Department</th><th>Open</th><th>In Progress</th><th>Resolved</th><th>Closed</th></tr></thead><tbody>' +
            (data.department_status && data.department_status.length ? data.department_status.map(function (row) {
                return '<tr><td>' + escapeHtml(row.department_name || 'Unknown') + '</td><td>' + parseInt(row.open_tickets || 0, 10) + '</td><td>' + parseInt(row.in_progress_tickets || 0, 10) + '</td><td>' + parseInt(row.resolved_tickets || 0, 10) + '</td><td>' + parseInt(row.closed_tickets || 0, 10) + '</td></tr>';
            }).join('') : '<tr><td colspan="5" class="text-center text-muted">No department backlog data</td></tr>') +
            '</tbody></table></div></div></div>' +
            '<div class="perf-section-block"><div class="perf-form-title">Assignment History Snapshot</div><div class="perf-table-wrap"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ticket</th><th>Title</th><th>Assigned By</th><th>Action</th><th>Current Status</th><th>Assigned At</th></tr></thead><tbody>' + assignedRows + '</tbody></table></div></div></div>';
    }

    function renderDetailCharts(data) {
        if (typeof Chart === 'undefined') {
            return;
        }

        destroyChart(detailStatusChart);
        destroyChart(detailVolumeChart);

        var statusBreakdown = data.status_breakdown || {};
        var monthlyVolume = data.monthly_volume || [];

        var statusCanvas = document.getElementById('perfDetailStatusChart');
        var volumeCanvas = document.getElementById('perfDetailVolumeChart');

        if (statusCanvas) {
            detailStatusChart = new Chart(statusCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                    datasets: [{
                        data: [
                            parseInt(statusBreakdown.open_tickets || 0, 10),
                            parseInt(statusBreakdown.in_progress_tickets || 0, 10),
                            parseInt(statusBreakdown.resolved_tickets || 0, 10),
                            parseInt(statusBreakdown.closed_tickets || 0, 10)
                        ],
                        backgroundColor: ['#d97706', '#2563eb', '#16a34a', '#1f2937'],
                        borderRadius: 10
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    maintainAspectRatio: false
                }
            });
        }

        if (volumeCanvas) {
            detailVolumeChart = new Chart(volumeCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: monthlyVolume.map(function (row) { return row.label; }),
                    datasets: [{
                        data: monthlyVolume.map(function (row) { return parseInt(row.count || 0, 10); }),
                        borderColor: '#1f3b5c',
                        backgroundColor: 'rgba(31, 59, 92, 0.12)',
                        tension: 0.32,
                        fill: true,
                        pointBackgroundColor: '#d9a441',
                        pointBorderColor: '#d9a441'
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    maintainAspectRatio: false
                }
            });
        }
    }

    function loadDeveloperDetail(developerId) {
        $('#developerPerformanceDetailBody').html('<div class="perf-empty-state compact"><i class="fas fa-circle-notch fa-spin"></i><h4>Loading detail</h4><p>Developer performance, charts and live ticket state fetch ho raha hai.</p></div>');

        abortRequest(detailRequest);
        detailRequest = $.ajax({
            url: config.detailUrl,
            type: 'GET',
            dataType: 'json',
            data: {
                developer_id: developerId,
                year: config.year
            }
        }).done(function (response) {
            if (!response || response.status !== true) {
                $('#developerPerformanceDetailBody').html('<div class="perf-empty-state compact"><i class="fas fa-exclamation-triangle"></i><h4>Detail unavailable</h4><p>Requested developer detail load nahi ho paya.</p></div>');
                return;
            }

            $('#developerPerformanceDetailBody').html(renderDeveloperDetail(response));
            updateSideContext('detail', {
                title: (((response.data || {}).developer || {}).name) || 'Performance Detail',
                message: 'Selected developer ka accepted work, live ticket state, leave metrics aur assignment history yahan visible hai.'
            });
            renderDetailCharts(response.data || {});
        }).fail(function () {
            $('#developerPerformanceDetailBody').html('<div class="perf-empty-state compact"><i class="fas fa-exclamation-triangle"></i><h4>Request failed</h4><p>AJAX response nahi mila.</p></div>');
        }).always(function () {
            detailRequest = null;
        });
    }

    function flattenTree(tree, items, depth) {
        if (!tree || !tree.user_id) {
            return items;
        }

        items.push({
            user_id: parseInt(tree.user_id, 10),
            name: tree.name,
            role_name: tree.role_name,
            depth: depth || 0
        });

        $.each(tree.children || [], function (_, child) {
            flattenTree(child, items, (depth || 0) + 1);
        });

        return items;
    }

    function renderTreeNode(node, isRoot) {
        var children = node.children || [];
        var cardClass = 'perf-tree-card' + (isRoot ? ' is-root' : '') + (selectedHierarchyUserId === parseInt(node.user_id, 10) ? ' is-selected' : '');
        var html = '<li class="perf-tree-node">' +
            '<button type="button" class="' + cardClass + '" data-user-id="' + parseInt(node.user_id, 10) + '">' +
            '<span class="perf-tree-card-head">' +
            '<span>' +
            '<span class="perf-tree-title">' + escapeHtml(node.name) + '</span>' +
            '<span class="perf-tree-meta">' + escapeHtml(node.role_name || 'User') + ' / ' + escapeHtml(node.department_name || 'Department') + '</span>' +
            '</span>' +
            '<span class="perf-tree-actions">' +
            '<span type="button" class="perf-tree-action js-hierarchy-add" data-user-id="' + parseInt(node.user_id, 10) + '" data-user-name="' + escapeHtml(node.name || 'Selected user') + '" title="Add under this member"><i class="fas fa-plus"></i></span>' +
            '</span>' +
            '</span>' +
            '</button>';

        if (children.length) {
            html += '<ul>';
            $.each(children, function (_, child) {
                html += renderTreeNode(child, false);
            });
            html += '</ul>';
        }

        html += '</li>';
        return html;
    }

    function populateHierarchyFormLegacy(tree, eligibleUsers) {
        var hierarchyMembers = flattenTree(tree, [], 0);

        var managerOptions = hierarchyMembers.map(function (item) {
            var indent = new Array((item.depth || 0) + 1).join('› ');
            return '<option value="' + item.user_id + '">' + escapeHtml(indent + item.name) + '</option>';
        }).join('');

        var memberOptions = hierarchyMembers
            .filter(function (item) { return item.depth > 0; })
            .map(function (item) {
                var indent = new Array((item.depth || 0) + 1).join('› ');
                return '<option value="' + item.user_id + '">' + escapeHtml(indent + item.name) + '</option>';
            }).join('');

        var eligibleOptions = eligibleUsers.map(function (item) {
            return '<option value="' + parseInt(item.user_id, 10) + '">' + escapeHtml(item.name + ' (' + (item.role_name || 'User') + ')') + '</option>';
        }).join('');

        $('#hierarchyManagerUser').html('<option value="">Select manager</option>' + managerOptions);
        $('#hierarchyTargetUser').html('<option value="">Select user</option>' + memberOptions + eligibleOptions);
    }

    function prepareHierarchyAddLegacy(managerId, managerName) {
        selectedHierarchyUserId = parseInt(managerId || 0, 10) || null;
        selectedHierarchyUserName = managerName || 'selected member';
        $('#hierarchyManagerUser').val(managerId);
        $('#developerHierarchyFormTitle').text('Add or Move Under ' + selectedHierarchyUserName);
        $('#developerHierarchyFormHelper').text('Direct hierarchy update: user choose karo, manager auto-selected hai. Sirf team member select karke save karo.');
        $('#developerHierarchyMessage').removeClass('success error').text('');
        $('#hierarchyTargetUser').focus();
    }

    function populateHierarchyForm(tree, eligibleUsers) {
        var hierarchyMembers = flattenTree(tree, [], 0);

        var managerOptions = hierarchyMembers.map(function (item) {
            var indent = new Array((item.depth || 0) + 1).join('> ');
            return '<option value="' + item.user_id + '">' + escapeHtml(indent + item.name) + '</option>';
        }).join('');

        var memberOptions = hierarchyMembers
            .filter(function (item) { return item.depth > 0; })
            .map(function (item) {
                var indent = new Array((item.depth || 0) + 1).join('> ');
                return '<option value="' + item.user_id + '">' + escapeHtml(indent + item.name) + '</option>';
            }).join('');

        var eligibleOptions = eligibleUsers.map(function (item) {
            return '<option value="' + parseInt(item.user_id, 10) + '">' + escapeHtml(item.name + ' (' + (item.role_name || 'User') + ')') + '</option>';
        }).join('');

        $('#hierarchyManagerUser').html('<option value="">Select manager</option>' + managerOptions);
        $('#hierarchyTargetUser').html('<option value="">Select user</option>' + memberOptions + eligibleOptions);
    }

    function prepareHierarchyAdd(managerId, managerName) {
        selectedHierarchyUserId = parseInt(managerId || 0, 10) || null;
        selectedHierarchyUserName = managerName || 'selected member';
        $('#hierarchyManagerUser').val(managerId);
        $('#developerHierarchyFormTitle').text('Add or Move Under ' + selectedHierarchyUserName);
        $('#developerHierarchyFormHelper').text('Direct hierarchy update: user choose karo, manager auto-selected hai. Sirf team member select karke save karo.');
        $('#developerHierarchyMessage').removeClass('success error').text('');
        updateSideContext('hierarchy', {
            title: selectedHierarchyUserName,
            message: 'Selected node ke niche hierarchy attach ya move karne ke liye form ready hai.'
        });
        $('#hierarchyTargetUser').focus();
    }

    function renderHierarchyInspector(data) {
        var user = data.user || {};
        var directReports = data.direct_reports || [];

        var directReportHtml = directReports.length
            ? directReports.map(function (row) {
                return '<div class="perf-subordinate-item"><div class="perf-subordinate-meta"><strong>' + escapeHtml(row.name) + '</strong><small>' + escapeHtml(row.role_name || 'User') + '</small></div><span class="perf-status-chip">Direct</span></div>';
            }).join('')
            : '<div class="text-muted">No direct reports under selected node.</div>';

        $('#developerHierarchyInspector').html(
            '<span class="perf-mini-label">Selected node</span>' +
            '<h4 class="mb-1">' + escapeHtml(user.name || 'Unknown') + '</h4>' +
            '<div class="text-muted mb-3">' + escapeHtml(user.role_name || 'User') + ' / ' + escapeHtml(user.department_name || 'Department') + '</div>' +
            '<div class="perf-detail-grid">' +
            '<div class="perf-detail-stat"><div class="label">Tickets This FY</div><div class="value">' + parseInt(data.total_tickets || 0, 10) + '</div></div>' +
            '<div class="perf-detail-stat"><div class="label">Direct Reports</div><div class="value">' + directReports.length + '</div></div>' +
            '</div>' +
            '<div class="perf-section-block">' +
            '<div class="perf-form-title">Members Under This Node</div>' +
            '<div class="perf-subordinate-list">' + directReportHtml + '</div>' +
            '</div>'
        );
    }

    function loadHierarchyMember(userId) {
        if (!userId) {
            return;
        }

        selectedHierarchyUserId = parseInt(userId, 10);

        $.ajax({
            url: config.hierarchyMemberUrl,
            type: 'GET',
            dataType: 'json',
            data: {
                user_id: userId,
                year: config.year
            }
        }).done(function (response) {
            if (!response || response.status !== true) {
                $('#developerHierarchyInspector').html('<div class="perf-empty-state compact"><i class="fas fa-exclamation-triangle"></i><h4>Member unavailable</h4><p>Selected node summary load nahi ho paya.</p></div>');
                return;
            }

            selectedHierarchyUserName = ((response.data || {}).user || {}).name || '';
            renderHierarchyInspector(response.data || {});
            $('#developerHierarchyTree .perf-tree-card').removeClass('is-selected');
            $('#developerHierarchyTree .perf-tree-card[data-user-id="' + selectedHierarchyUserId + '"]').addClass('is-selected');
            prepareHierarchyAdd(selectedHierarchyUserId, selectedHierarchyUserName || 'selected member');
        }).fail(function () {
            $('#developerHierarchyInspector').html('<div class="perf-empty-state compact"><i class="fas fa-exclamation-triangle"></i><h4>Member unavailable</h4><p>Hierarchy node request fail hua.</p></div>');
        });
    }

    function loadHierarchyData(forceReload) {
        if (hierarchyLoaded && !forceReload) {
            return;
        }

        $('#developerHierarchyTree').html('<div class="text-muted py-4 text-center">Hierarchy loading...</div>');

        abortRequest(hierarchyRequest);
        hierarchyRequest = $.ajax({
            url: config.hierarchyUrl,
            type: 'GET',
            dataType: 'json',
            data: {
                year: config.year
            }
        }).done(function (response) {
            if (!response || response.status !== true) {
                $('#developerHierarchyTree').html('<div class="text-danger py-4 text-center">Hierarchy data unavailable.</div>');
                return;
            }

            hierarchyLoaded = true;
            updateOverviewCounters(response.overview || config.initialOverview || {}, true);

            var tree = response.tree || {};
            var eligibleUsers = response.eligible_users || [];

            if (!tree.user_id) {
                $('#developerHierarchyTree').html('<div class="text-muted py-4 text-center">No hierarchy data available.</div>');
                populateHierarchyForm({}, eligibleUsers);
                return;
            }

            $('#developerHierarchyTree').html('<ul class="perf-tree-root">' + renderTreeNode(tree, true) + '</ul>');
            populateHierarchyForm(tree, eligibleUsers);
            loadHierarchyMember(selectedHierarchyUserId || tree.user_id);
        }).fail(function () {
            $('#developerHierarchyTree').html('<div class="text-danger py-4 text-center">Unable to load hierarchy.</div>');
        }).always(function () {
            hierarchyRequest = null;
        });
    }

    function switchWorkspace(target, options) {
        var workspaceTarget = sectionMeta[target] ? target : 'queue';
        options = options || {};

        $('.perf-side-nav-btn').removeClass('is-active');
        $('.perf-side-nav-btn[data-target="' + workspaceTarget + '"]').addClass('is-active');

        $('.perf-main-section').hide().removeClass('is-active');

        if (workspaceTarget === 'hierarchy') {
            $('#perfHierarchySection').show().addClass('is-active');
            loadHierarchyData(false);
        } else if (workspaceTarget === 'detail') {
            $('#perfDetailSection').show().addClass('is-active');
        } else {
            $('#perfQueueSection').show().addClass('is-active');
        }

        updateSideContext(workspaceTarget, options.context || null);

        if (!options.skipHistory) {
            syncWorkspaceHash(workspaceTarget);
        }
    }

    function submitHierarchyUpdate() {
        var payload = $('#developerHierarchyForm').serialize();
        var $message = $('#developerHierarchyMessage');

        $message.removeClass('success error').text('Saving hierarchy...');

        $.ajax({
            url: config.hierarchyUpdateUrl,
            type: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            if (!response || response.status !== true) {
                $message.addClass('error').text((response && response.message) ? response.message : 'Unable to save hierarchy.');
                return;
            }

            $message.addClass('success').text(response.message || 'Hierarchy updated.');
            hierarchyLoaded = false;
            loadHierarchyData(true);
            refreshDeveloperGrid();
        }).fail(function (xhr) {
            var message = 'Unable to save hierarchy.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            $message.addClass('error').text(message);
        });
    }

    function bindEvents() {
        $(document).off('.trsDevPerf');
        $('#perfTicketScopeToggle .perf-scope-btn').off('.trsDevPerf');
        $('#perfTicketScopeSelect').off('.trsDevPerf');
        $('#developerPerformanceSearch, #developerCompanyFilter, #developerPerformanceDepartmentFilter').off('.trsDevPerf');
        $('#developerFilterToggle').off('.trsDevPerf');
        $('#developerHierarchyForm').off('.trsDevPerf');
        $('#refreshHierarchyBtn').off('.trsDevPerf');
        $('#btnDeveloperReportPdf').off('.trsDevPerf');

        updateOverviewCounters(config.initialOverview || {}, false);
        updateTicketOverview(config.initialTicketOverview || {}, true);
        switchWorkspace(getWorkspaceFromHash(), { skipHistory: true });

        $('#developerPerformanceSearch, #developerCompanyFilter, #developerPerformanceDepartmentFilter').on('input.trsDevPerf change.trsDevPerf', applyDeveloperFilters);

        $('#perfTicketScopeToggle .perf-scope-btn').on('click.trsDevPerf', function () {
            setTicketScope($(this).data('scope'), { refresh: true });
        });

        $('#perfTicketScopeSelect').val(ticketScope).on('change.trsDevPerf', function () {
            setTicketScope($(this).val(), { refresh: true });
        });

        $('#developerFilterToggle').on('click.trsDevPerf', function () {
            $('#developerFilterPanel').stop(true, true).slideToggle(160);
        });

        $(document).on('click.trsDevPerf', '.perf-dev-card', function () {
            var developerId = $(this).data('developer-id');
            var developerName = ($(this).data('developer-name') || '').toString();
            if (!developerId) {
                return;
            }

            $('#perfDetailHeading').text(developerName || 'Developer Detail');
            switchWorkspace('detail', {
                context: {
                    title: developerName || 'Performance Detail',
                    message: 'Selected developer ka performance summary, charts aur live tables AJAX se load ho rahe hain.'
                }
            });
            loadDeveloperDetail(developerId);
        });

        $(document).on('click.trsDevPerf', '.perf-side-nav-btn', function () {
            switchWorkspace($(this).data('target'));
        });

        $(document).on('click.trsDevPerf', '#developerHierarchyTree .perf-tree-card', function () {
            loadHierarchyMember($(this).data('user-id'));
        });

        $(document).on('click.trsDevPerf', '#developerHierarchyTree .js-hierarchy-add', function (event) {
            event.preventDefault();
            event.stopPropagation();
            switchWorkspace('hierarchy');
            prepareHierarchyAdd($(this).data('user-id'), $(this).data('user-name'));
        });

        $('#developerHierarchyForm').on('submit.trsDevPerf', function (event) {
            event.preventDefault();
            submitHierarchyUpdate();
        });

        $('#refreshHierarchyBtn').on('click.trsDevPerf', function () {
            hierarchyLoaded = false;
            loadHierarchyData(true);
        });

        $('#btnDeveloperReportPdf').on('click.trsDevPerf', function () {
            var fromDate = $('#reportFromDate').val() || '';
            var toDate = $('#reportToDate').val() || '';
            var year = config.year || new Date().getFullYear();
            var url = config.exportUrl + '?year=' + encodeURIComponent(year) + '&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate);
            window.location.href = url;
        });

        clearInterval(gridIntervalId);
        gridIntervalId = setInterval(function () {
            if (document.hidden || !$('#developerPerformanceGrid').length) {
                return;
            }

            refreshDeveloperGrid();
        }, 120000);
    }

    function destroy() {
        abortRequest(gridRequest);
        abortRequest(detailRequest);
        abortRequest(hierarchyRequest);
        clearInterval(gridIntervalId);
        $(document).off('.trsDevPerf');
        $('#perfTicketScopeToggle .perf-scope-btn').off('.trsDevPerf');
        $('#perfTicketScopeSelect').off('.trsDevPerf');
        $('#developerPerformanceSearch, #developerCompanyFilter, #developerPerformanceDepartmentFilter').off('.trsDevPerf');
        $('#developerFilterToggle').off('.trsDevPerf');
        $('#developerHierarchyForm').off('.trsDevPerf');
        $('#refreshHierarchyBtn').off('.trsDevPerf');
        $('#btnDeveloperReportPdf').off('.trsDevPerf');
    }

    window[instanceKey] = {
        destroy: destroy
    };

    $(function () {
        bindEvents();
    });
})(window, jQuery);
