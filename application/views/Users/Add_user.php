<?php
$this->load->view('Layout/Header');

$old = isset($old_user) && is_array($old_user) ? $old_user : [];
$selectedMode = (string) ($old['account_mode'] ?? 'invite');
$selectedRoleId = (int) ($old['role_id'] ?? 1);
$selectedDepartmentId = (int) ($old['department_id'] ?? 0);
$selectedWorksUnderId = (int) ($old['works_under_user_id'] ?? ($old['reports_to'] ?? 0));
$selectedStatus = (string) ($old['status'] ?? 'Active');
?>

<div class="content-wrapper user-management-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Add User</h1>
                    <p class="text-muted mb-0">Create a pending activation user or an immediately active account from one updated form.</p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= base_url('TRS/user_list') ?>" class="btn btn-outline-primary btn-sm">Manage Users</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content pb-4">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('failed')): ?>
                <div class="alert alert-danger"><?= $this->session->flashdata('failed'); ?></div>
            <?php endif; ?>

            <div class="user-shell-card">
                <div class="user-shell-card__header">
                    <div>
                        <h3>User Account Setup</h3>
                        <p>Invite mode keeps the account inactive until the user completes activation. Active mode creates a ready-to-login account immediately.</p>
                    </div>
                    <div class="user-shell-pill">User Admin</div>
                </div>

                <form method="post" action="<?= base_url('TRS/save_user') ?>" id="managedUserForm">
                    <div class="form-group">
                        <label>Account Mode</label>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" id="accountModeInvite" name="account_mode" value="invite" <?= $selectedMode !== 'active' ? 'checked' : '' ?>>
                            <label for="accountModeInvite" class="custom-control-label">Invite user and let activation happen later</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" id="accountModeActive" name="account_mode" value="active" <?= $selectedMode === 'active' ? 'checked' : '' ?>>
                            <label for="accountModeActive" class="custom-control-label">Create active account now</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="managedUserName">Full Name</label>
                            <input id="managedUserName" type="text" name="name" class="form-control" value="<?= htmlspecialchars((string) ($old['name'] ?? '')) ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="managedUserEmail">Email</label>
                            <input id="managedUserEmail" type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="managedUserPhone">Phone</label>
                            <input id="managedUserPhone" type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($old['phone'] ?? '')) ?>" placeholder="Optional">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="managedUserCompany">Company Name</label>
                            <input id="managedUserCompany" type="text" name="company_name" class="form-control" value="<?= htmlspecialchars((string) ($old['company_name'] ?? 'TRS')) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="managedUserDepartment">Department</label>
                            <select id="managedUserDepartment" name="department_id" class="form-control" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= (int) $department['department_id'] ?>" <?= $selectedDepartmentId === (int) $department['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $department['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="managedUserRole">Role</label>
                            <select id="managedUserRole" name="role_id" class="form-control" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int) $role['role_id'] ?>" <?= $selectedRoleId === (int) $role['role_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $role['role_name']))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="managedWorksUnder">Works Under</label>
                        <select id="managedWorksUnder" name="works_under_user_id" class="form-control" data-selected="<?= (int) $selectedWorksUnderId ?>">
                            <option value="">No direct owner / top level</option>
                        </select>
                        <small class="form-text text-muted">Choose the person this user works under in the hierarchy. If you leave it blank, the system uses the department head when applicable.</small>
                    </div>

                    <div class="form-row" id="managedActiveOnlyFields">
                        <div class="form-group col-md-6">
                            <label for="managedUserUsername">Username</label>
                            <input id="managedUserUsername" type="text" name="user_name" class="form-control" value="<?= htmlspecialchars((string) ($old['user_name'] ?? '')) ?>" placeholder="Required for active accounts">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="managedUserPassword">Password</label>
                            <input id="managedUserPassword" type="password" name="password" class="form-control" placeholder="Required for active accounts">
                        </div>
                    </div>

                    <div class="form-group" id="managedStatusGroup">
                        <label for="managedUserStatus">Status</label>
                        <select id="managedUserStatus" name="status" class="form-control">
                            <option value="Active" <?= $selectedStatus !== 'Inactive' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $selectedStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="asset-note mb-4">
                        Invite mode auto-generates a placeholder username if you leave it blank. The user can later set their final username during activation.
                    </div>

                    <button type="submit" class="btn btn-success">Create User</button>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inviteRadio = document.getElementById('accountModeInvite');
    var activeRadio = document.getElementById('accountModeActive');
    var departmentSelect = document.getElementById('managedUserDepartment');
    var usernameInput = document.getElementById('managedUserUsername');
    var passwordInput = document.getElementById('managedUserPassword');
    var statusField = document.getElementById('managedUserStatus');
    var worksUnderSelect = document.getElementById('managedWorksUnder');
    var hierarchyUsers = <?= json_encode($hierarchy_users ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    function formatRoleName(roleName) {
        return String(roleName || '').replace(/_/g, ' ').replace(/\b\w/g, function (char) {
            return char.toUpperCase();
        });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncWorksUnderOptions() {
        var selectedDepartmentId = parseInt(departmentSelect.value || '0', 10);
        var selectedValue = String(worksUnderSelect.getAttribute('data-selected') || worksUnderSelect.value || '');
        var options = ['<option value="">No direct owner / top level</option>'];

        hierarchyUsers.forEach(function (user) {
            var userDepartmentId = parseInt(user.department_id || '0', 10);
            var includeUser = !selectedDepartmentId || userDepartmentId === selectedDepartmentId || String(user.user_id) === selectedValue;

            if (!includeUser) {
                return;
            }

            var label = user.name + ' - ' + (user.department_name || 'No Department');
            if (user.role_name) {
                label += ' / ' + formatRoleName(user.role_name);
            }

            options.push('<option value="' + user.user_id + '">' + escapeHtml(label) + '</option>');
        });

        worksUnderSelect.innerHTML = options.join('');
        worksUnderSelect.value = selectedValue;
        worksUnderSelect.setAttribute('data-selected', worksUnderSelect.value || '');
    }

    function syncMode() {
        var isActive = activeRadio.checked;

        usernameInput.required = isActive;
        passwordInput.required = isActive;
        statusField.disabled = !isActive;

        if (!isActive) {
            statusField.value = 'Inactive';
        }
    }

    inviteRadio.addEventListener('change', syncMode);
    activeRadio.addEventListener('change', syncMode);
    departmentSelect.addEventListener('change', syncWorksUnderOptions);
    worksUnderSelect.addEventListener('change', function () {
        worksUnderSelect.setAttribute('data-selected', worksUnderSelect.value || '');
    });

    syncWorksUnderOptions();
    syncMode();
});
</script>

<?php $this->load->view('Layout/Footer'); ?>
