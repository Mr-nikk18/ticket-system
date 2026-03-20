<?php
$department_id = (int) $this->session->userdata('department_id');
$role_id = (int) $this->session->userdata('role_id');
$dashboardScopeValue = in_array((string) ($dashboard_scope ?? 'all'), ['mine', 'all', 'assigned'], true) ? (string) $dashboard_scope : 'all';
$showDepartmentColumn = ($department_id === 2 || $role_id === 2);
$selectedStatus = isset($dashboard_ticket_status) ? (int) $dashboard_ticket_status : 0;
$viewAllUrl = $selectedStatus > 0 ? base_url('TRS/list/' . $selectedStatus) : base_url('TRS/list');
$scheduleUserValue = isset($selected_schedule_user_id) && (int) $selected_schedule_user_id > 0 ? (int) $selected_schedule_user_id : 'all';
?>

<div class="col-12 mt-3">
    <div class="card">
        <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
            <h3 class="card-title mb-2 mb-lg-0">
                <i class="fas fa-ticket-alt mr-1"></i>
                Recent Tickets
            </h3>

            <div class="d-flex flex-wrap align-items-center ml-lg-auto" style="gap:0.5rem;">
                <form id="dashboardRecentFilterForm" method="get" action="<?= base_url('Dashboard') ?>" class="d-flex flex-wrap align-items-center" style="gap:0.5rem;">
                    <input type="hidden" name="schedule_user_id" value="<?= htmlspecialchars((string) $scheduleUserValue) ?>">
                    <input type="hidden" name="dashboard_scope" value="<?= htmlspecialchars($dashboardScopeValue) ?>">
                    <select name="dashboard_ticket_status" class="custom-select custom-select-sm js-dashboard-ticket-filter" style="min-width: 170px;">
                        <option value="0" <?= $selectedStatus === 0 ? 'selected' : '' ?>>All Statuses</option>
                        <option value="1" <?= $selectedStatus === 1 ? 'selected' : '' ?>>Open</option>
                        <option value="2" <?= $selectedStatus === 2 ? 'selected' : '' ?>>In Process</option>
                        <option value="3" <?= $selectedStatus === 3 ? 'selected' : '' ?>>Resolved</option>
                        <option value="4" <?= $selectedStatus === 4 ? 'selected' : '' ?>>Closed</option>
                    </select>
                </form>

                <button type="button" id="dashboardRecentRefresh" class="btn btn-outline-secondary btn-sm">
                    Refresh
                </button>

                <a href="<?= $viewAllUrl ?>" class="btn btn-primary btn-sm">
                    View All
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped w-100">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Title</th>
                        <?php if ($showDepartmentColumn) { ?>
                            <th>Department</th>
                        <?php } ?>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Handle By</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($recent_tickets)) { ?>
                        <?php foreach ($recent_tickets as $ticket) { ?>
                            <tr>
                                <td><?= $ticket['ticket_id'] ?></td>
                                <td><?= htmlspecialchars($ticket['title']) ?></td>
                                <?php if ($showDepartmentColumn) { ?>
                                    <td><?= htmlspecialchars($ticket['department_name'] ?? '-') ?></td>
                                <?php } ?>
                                <td><?= htmlspecialchars($ticket['description']) ?></td>
                                <td>
                                    <?php if ((int) $ticket['status_id'] === 1) { ?>
                                        <span class="badge badge-success">Open</span>
                                    <?php } elseif ((int) $ticket['status_id'] === 2) { ?>
                                        <span class="badge badge-warning">In Process</span>
                                    <?php } elseif ((int) $ticket['status_id'] === 3) { ?>
                                        <span class="badge badge-info">Resolved</span>
                                    <?php } elseif ((int) $ticket['status_id'] === 4) { ?>
                                        <span class="badge badge-secondary">Closed</span>
                                    <?php } ?>
                                </td>
                                <td><?= !empty($ticket['created_at']) ? date('d-m-Y', strtotime($ticket['created_at'])) : '-' ?></td>
                                <td><?= htmlspecialchars($ticket['assigned_engineer_name'] ?? 'Not Assigned') ?></td>
                                <td>
                                    <?php if ((int) ($ticket['can_accept'] ?? 0) === 1 && $department_id === 2) { ?>
                                        <a href="<?= base_url('TRS/accept_ticket/' . $ticket['ticket_id']) ?>" class="btn btn-sm btn-success">
                                            Accept
                                        </a>
                                    <?php } else { ?>
                                        <span class="badge badge-secondary">No action</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="<?= $showDepartmentColumn ? 8 : 7 ?>" class="text-center">No tickets found</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
