<?php
$this->load->view('Layout/Header');

$selectedMode = isset($user['account_mode'])
    ? (string) $user['account_mode']
    : ((int) ($user['is_registered'] ?? 0) === 1 ? 'active' : 'invite');
$selectedRoleId = (int) ($user['role_id'] ?? 1);
$selectedDepartmentId = (int) ($user['department_id'] ?? 0);
$selectedWorksUnderId = (int) ($user['works_under_user_id'] ?? ($user['reports_to'] ?? 0));
$selectedStatus = (string) ($user['status'] ?? 'Active');
?>

<div class="content-wrapper user-management-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Edit User</h1>
                    <p class="text-muted mb-0">Update account state, role, department, and login readiness from one refreshed page.</p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= base_url('TRS/user_list') ?>" class="btn btn-outline-primary btn-sm">Back To Manage Users</a>
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
                        <h3><?= htmlspecialchars((string) ($user['name'] ?? 'User')) ?></h3>
                        <p><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></p>
                    </div>
                    <div class="user-shell-pill">Edit Account</div>
                </div>

                <form method="post" action="<?= base_url('TRS/update_userlist/' . (int) $user['user_id']) ?>" id="managedUserEditForm">
                    <div class="form-group">
                        <label>Account Mode</label>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" id="editAccountModeInvite" name="account_mode" value="invite" <?= $selectedMode !== 'active' ? 'checked' : '' ?>>
                            <label for="editAccountModeInvite" class="custom-control-label">Pending activation / invite flow</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" id="editAccountModeActive" name="account_mode" value="active" <?= $selectedMode === 'active' ? 'checked' : '' ?>>
                            <label for="editAccountModeActive" class="custom-control-label">Registered active account</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="editManagedUserName">Full Name</label>
                            <input id="editManagedUserName" type="text" name="name" class="form-control" value="<?= htmlspecialchars((string) ($user['name'] ?? '')) ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editManagedUserEmail">Email</label>
                            <input id="editManagedUserEmail" type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($user['email'] ?? '')) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="editManagedUserPhone">Phone</label>
                            <input id="editManagedUserPhone" type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($user['phone'] ?? '')) ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editManagedUserCompany">Company Name</label>
                            <input id="editManagedUserCompany" type="text" name="company_name" class="form-control" value="<?= htmlspecialchars((string) ($user['company_name'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="editManagedUserDepartment">Department</label>
                            <select id="editManagedUserDepartment" name="department_id" class="form-control" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= (int) $department['department_id'] ?>" <?= $selectedDepartmentId === (int) $department['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $department['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editManagedUserRole">Role</label>
                            <select id="editManagedUserRole" name="role_id" class="form-control" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int) $role['role_id'] ?>" <?= $selectedRoleId === (int) $role['role_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $role['role_name']))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editManagedWorksUnder">Works Under</label>
                        <select id="editManagedWorksUnder" name="works_under_user_id" class="form-control" data-selected="<?= (int) $selectedWorksUnderId ?>">
                            <option value="">No direct owner / top level</option>
                        </select>
                        <small class="form-text text-muted">Choose the direct hierarchy owner for this account. Leave blank if this user should stay at the top level.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="editManagedUserUsername">Username</label>
                            <input id="editManagedUserUsername" type="text" name="user_name" class="form-control" value="<?= htmlspecialchars((string) ($user['user_name'] ?? '')) ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editManagedUserPassword">New Password</label>
                            <input id="editManagedUserPassword" type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editManagedUserStatus">Status</label>
                        <select id="editManagedUserStatus" name="status" class="form-control">
                            <option value="Active" <?= $selectedStatus !== 'Inactive' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $selectedStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="asset-note mb-4">
                        Setting the mode back to invite marks the user as pending activation again and keeps the account inactive until they complete the activation flow.
                    </div>

                    <button type="submit" class="btn btn-success">Update User</button>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inviteRadio = document.getElementById('editAccountModeInvite');
    var activeRadio = document.getElementById('editAccountModeActive');
    var departmentSelect = document.getElementById('editManagedUserDepartment');
    var usernameInput = document.getElementById('editManagedUserUsername');
    var statusField = document.getElementById('editManagedUserStatus');
    var worksUnderSelect = document.getElementById('editManagedWorksUnder');
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

        hierarchyUsers.forEach(function (userOption) {
            var userDepartmentId = parseInt(userOption.department_id || '0', 10);
            var includeUser = !selectedDepartmentId || userDepartmentId === selectedDepartmentId || String(userOption.user_id) === selectedValue;

            if (!includeUser) {
                return;
            }

            var label = userOption.name + ' - ' + (userOption.department_name || 'No Department');
            if (userOption.role_name) {
                label += ' / ' + formatRoleName(userOption.role_name);
            }

            options.push('<option value="' + userOption.user_id + '">' + escapeHtml(label) + '</option>');
        });

        worksUnderSelect.innerHTML = options.join('');
        worksUnderSelect.value = selectedValue;
        worksUnderSelect.setAttribute('data-selected', worksUnderSelect.value || '');
    }

    function syncMode() {
        var isActive = activeRadio.checked;
        usernameInput.required = isActive;
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
