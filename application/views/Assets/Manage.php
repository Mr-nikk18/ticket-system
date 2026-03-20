<?php
$this->load->view('Layout/Header');

$filters = isset($filters) && is_array($filters) ? $filters : [];
$searchQuery = htmlspecialchars((string) ($filters['q'] ?? ''));
$selectedDepartmentId = (int) ($filters['department_id'] ?? 0);
$selectedAssetType = (string) ($filters['asset_type'] ?? '');
$selectedStatus = (string) ($filters['status'] ?? '');
$selectedLimit = (int) ($filters['limit'] ?? 150);
$selectedLimit = $selectedLimit > 0 ? $selectedLimit : 150;
$manageAssets = isset($manage_assets) && is_array($manage_assets) ? $manage_assets : [];
$manageUrl = (string) ($asset_manage_url ?? base_url('assets/manage'));
?>

<div class="content-wrapper asset-page">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Manage Assets</h1>
                    <p class="text-muted mb-0">Edit records, delete unused assets, and review the clean serial-based QR route from one workspace.</p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= base_url('assets/create') ?>" class="btn btn-outline-primary btn-sm mr-2">Single Asset Page</a>
                    <a href="<?= base_url('assets/bulk-upload') ?>" class="btn btn-outline-secondary btn-sm mr-2">Bulk Upload Assets</a>
                    <a href="<?= base_url('assets/qr-print-center') ?>" class="btn btn-primary btn-sm">QR Print Center</a>
                </div>
            </div>
        </div>
    </div>

    <section class="content pb-4">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('failed')): ?>
                <div class="alert alert-danger js-auto-dismiss-alert"><?= $this->session->flashdata('failed'); ?></div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success js-auto-dismiss-alert"><?= $this->session->flashdata('success'); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="asset-shell-card h-100">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>Filters</h3>
                                <p>Search asset records by serial, QR, department, user, or status before editing.</p>
                            </div>
                            <div class="asset-shell-pill">Manage</div>
                        </div>

                        <form method="get" action="<?= htmlspecialchars($manageUrl) ?>">
                            <div class="form-group">
                                <label for="assetManageSearch">Search</label>
                                <input id="assetManageSearch" type="text" name="q" class="form-control" value="<?= $searchQuery ?>" placeholder="Asset, serial, QR, department, user">
                            </div>

                            <div class="form-group">
                                <label for="assetManageDepartment">Department</label>
                                <select id="assetManageDepartment" name="department_id" class="form-control">
                                    <option value="">All departments</option>
                                    <?php foreach (($print_department_options ?? []) as $department): ?>
                                        <option value="<?= (int) $department['department_id'] ?>" <?= $selectedDepartmentId === (int) $department['department_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $department['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assetManageType">Asset Type</label>
                                <select id="assetManageType" name="asset_type" class="form-control">
                                    <option value="">All asset types</option>
                                    <?php foreach (($print_asset_type_options ?? []) as $assetTypeOption): ?>
                                        <option value="<?= htmlspecialchars((string) $assetTypeOption) ?>" <?= $selectedAssetType === (string) $assetTypeOption ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $assetTypeOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assetManageStatus">Status</label>
                                <select id="assetManageStatus" name="status" class="form-control">
                                    <option value="">All statuses</option>
                                    <?php foreach (($status_options ?? []) as $statusOption): ?>
                                        <option value="<?= htmlspecialchars((string) $statusOption) ?>" <?= $selectedStatus === (string) $statusOption ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $statusOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assetManageLimit">Rows</label>
                                <select id="assetManageLimit" name="limit" class="form-control">
                                    <?php foreach ([50, 100, 150, 250, 300] as $limitOption): ?>
                                        <option value="<?= $limitOption ?>" <?= $selectedLimit === $limitOption ? 'selected' : '' ?>><?= $limitOption ?> rows</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex flex-wrap">
                                <button type="submit" class="btn btn-primary mr-2 mb-2">Search Assets</button>
                                <a href="<?= htmlspecialchars($manageUrl) ?>" class="btn btn-outline-secondary mb-2">Reset</a>
                            </div>
                        </form>

                        <div class="asset-note mt-4">
                            Delete works only when no live ticket is linked to the asset. That keeps existing ticket history safe.
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="asset-shell-card h-100">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>Asset Records</h3>
                                <p><?= count($manageAssets) ?> record(s) matched the current filters.</p>
                            </div>
                            <div class="asset-shell-pill">Action Ready</div>
                        </div>

                        <?php if (empty($manageAssets)): ?>
                            <div class="asset-empty-state asset-empty-state--wide">
                                No asset record matched the current filters.
                            </div>
                        <?php else: ?>
                            <div class="asset-manage-table-wrap">
                                <table class="table table-bordered table-striped mb-0 asset-manage-table">
                                    <thead>
                                        <tr>
                                            <th>Asset</th>
                                            <th>Serial / QR</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Assigned</th>
                                            <th>Route</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($manageAssets as $assetRow): ?>
                                            <tr>
                                                <td>
                                                    <div class="asset-manage-title"><?= htmlspecialchars((string) ($assetRow['asset_name'] ?? 'Unnamed Asset')) ?></div>
                                                    <div class="asset-manage-meta">
                                                        <span><?= htmlspecialchars((string) ($assetRow['asset_type'] ?? 'Unknown Type')) ?></span>
                                                        <span><?= htmlspecialchars((string) ($assetRow['location'] ?? 'No location')) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="asset-manage-code"><?= htmlspecialchars((string) ($assetRow['display_serial_number'] ?? '')) ?></div>
                                                    <div class="asset-manage-subcode"><?= htmlspecialchars((string) ($assetRow['qr_code'] ?? '')) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string) ($assetRow['asset_department_name'] ?? 'Not set')) ?></td>
                                                <td>
                                                    <span class="asset-status-chip asset-status-chip--<?= strtolower(str_replace(' ', '-', (string) ($assetRow['status'] ?? 'working'))) ?>">
                                                        <?= htmlspecialchars((string) ($assetRow['status'] ?? 'Working')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars((string) ($assetRow['assigned_user_name'] ?? 'Not assigned')) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars((string) ($assetRow['assigned_user_email'] ?? '')) ?></small>
                                                </td>
                                                <td>
                                                    <a href="<?= htmlspecialchars((string) ($assetRow['scan_url'] ?? '#')) ?>" target="_blank" rel="noopener" class="asset-manage-route">
                                                        <?= htmlspecialchars((string) ($assetRow['scan_url'] ?? '')) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="asset-manage-actions">
                                                        <a href="<?= base_url('assets/edit/' . (int) ($assetRow['asset_id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                                        <form method="post" action="<?= base_url('assets/delete/' . (int) ($assetRow['asset_id'] ?? 0)) ?>" onsubmit="return confirm('Delete this asset record?');">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.asset-manage-table-wrap {
    border: 1px solid rgba(19, 78, 61, 0.09);
    border-radius: 18px;
    overflow: auto;
}

.asset-manage-table th {
    background: #f3faf5;
    position: sticky;
    top: 0;
    z-index: 1;
}

.asset-manage-title {
    color: #173a2c;
    font-weight: 700;
}

.asset-manage-meta {
    color: #5c6d63;
    display: flex;
    flex-direction: column;
    font-size: 0.84rem;
    gap: 0.15rem;
    margin-top: 0.3rem;
}

.asset-manage-code {
    color: #173a2c;
    font-family: "Courier New", monospace;
    font-size: 0.95rem;
    font-weight: 700;
}

.asset-manage-subcode {
    color: #5c6d63;
    font-size: 0.78rem;
    margin-top: 0.25rem;
    word-break: break-word;
}

.asset-manage-route {
    color: #0f766e;
    display: inline-block;
    font-size: 0.8rem;
    max-width: 240px;
    word-break: break-word;
}

.asset-manage-actions {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.asset-manage-actions form {
    margin: 0;
}

.asset-status-chip {
    border-radius: 999px;
    display: inline-block;
    font-size: 0.76rem;
    font-weight: 700;
    padding: 0.35rem 0.65rem;
}

.asset-status-chip--working {
    background: rgba(25, 135, 84, 0.12);
    color: #146c43;
}

.asset-status-chip--faulty {
    background: rgba(220, 53, 69, 0.12);
    color: #b02a37;
}

.asset-status-chip--in-repair {
    background: rgba(255, 193, 7, 0.18);
    color: #8b6b00;
}
</style>

<?php $this->load->view('Layout/Footer'); ?>
