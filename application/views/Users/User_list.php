<?php
$this->load->view('Layout/Header');

$totalUsers = count($users);
$pendingUsers = 0;
$activeUsers = 0;

foreach ($users as $userRow) {
    if ((int) ($userRow['is_registered'] ?? 0) === 1 && (string) ($userRow['status'] ?? '') === 'Active') {
        $activeUsers++;
    } else {
        $pendingUsers++;
    }
}
?>

<div class="content-wrapper user-management-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Manage Users</h1>
                    <p class="text-muted mb-0">Review account state, department, role, and activation readiness from one page.</p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= base_url('TRS/add_user') ?>" class="btn btn-primary btn-sm">Add User</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content pb-4">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('failed')): ?>
                <div class="alert alert-danger"><?= $this->session->flashdata('failed'); ?></div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
            <?php endif; ?>

            <div class="asset-import-summary mb-4">
                <div class="asset-import-summary__item">
                    <span class="asset-import-summary__label">Total Users</span>
                    <span class="asset-import-summary__value"><?= (int) $totalUsers ?></span>
                </div>
                <div class="asset-import-summary__item">
                    <span class="asset-import-summary__label">Active Accounts</span>
                    <span class="asset-import-summary__value"><?= (int) $activeUsers ?></span>
                </div>
                <div class="asset-import-summary__item">
                    <span class="asset-import-summary__label">Pending / Inactive</span>
                    <span class="asset-import-summary__value"><?= (int) $pendingUsers ?></span>
                </div>
            </div>

            <div class="user-shell-card">
                <div class="user-shell-card__header">
                    <div>
                        <h3>User Directory</h3>
                        <p>The updated view uses real database role names and department names instead of the old hard-coded developer/admin assumptions.</p>
                    </div>
                    <div class="user-shell-pill">User Admin</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Works Under</th>
                                <th>Account State</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $isRegistered = (int) ($user['is_registered'] ?? 0) === 1;
                                $isActive = (string) ($user['status'] ?? '') === 'Active';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) ($user['name'] ?? '')) ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars((string) ($user['phone'] ?? '')) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($user['user_name'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($user['department_name'] ?? 'Not set')) ?></td>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($user['role_name'] ?? 'Not set')))) ?></td>
                                    <td>
                                        <?= htmlspecialchars((string) ($user['reports_to_name'] ?? 'Top Level')) ?>
                                        <?php if (!empty($user['reports_to_email'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars((string) $user['reports_to_email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="user-status-badge <?= $isRegistered ? 'user-status-badge--active' : 'user-status-badge--pending' ?>">
                                            <?= $isRegistered ? 'Registered' : 'Pending Activation' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="user-status-badge <?= $isActive ? 'user-status-badge--active' : 'user-status-badge--inactive' ?>">
                                            <?= htmlspecialchars((string) ($user['status'] ?? 'Inactive')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('TRS/edit_userlist/' . (int) $user['user_id']) ?>" class="btn btn-sm btn-outline-primary mb-1">Edit</a>
                                        <a href="<?= base_url('TRS/delete_userlist/' . (int) $user['user_id']) ?>" class="btn btn-sm btn-outline-danger mb-1" onclick="return confirm('Delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php $this->load->view('Layout/Footer'); ?>
