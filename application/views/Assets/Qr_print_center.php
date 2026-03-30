<?php
$this->load->view('Layout/Header');

$filters = isset($filters) && is_array($filters) ? $filters : [];
$selectedDepartmentId = (int) ($filters['department_id'] ?? 0);
$selectedAssetType = (string) ($filters['asset_type'] ?? '');
$selectedSerialNumber = htmlspecialchars((string) ($filters['serial_number'] ?? ''));
$searchQuery = htmlspecialchars((string) ($filters['q'] ?? ''));
$defaultPrintCopies = (int) ($default_print_copies ?? 1);
$defaultPrintCopies = $defaultPrintCopies > 0 ? $defaultPrintCopies : 1;
$printDepartmentOptions = isset($print_department_options) && is_array($print_department_options) ? $print_department_options : [];
$printAssetTypeOptions = isset($print_asset_type_options) && is_array($print_asset_type_options) ? $print_asset_type_options : [];
$printAssets = isset($print_assets) && is_array($print_assets) ? $print_assets : [];
?>

<div class="content-wrapper asset-page">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">QR Print Center</h1>
                    <p class="text-muted mb-0">Search department-linked QR records, keep asset details visible with every QR, and print one by one or in bulk.</p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= base_url('assets/create') ?>" class="btn btn-outline-primary btn-sm mr-2">Single Asset Page</a>
                    <a href="<?= base_url('assets/manage') ?>" class="btn btn-outline-dark btn-sm mr-2">Manage Assets</a>
                    <a href="<?= base_url('assets/bulk-upload') ?>" class="btn btn-outline-secondary btn-sm">Bulk Upload Assets</a>
                </div>
            </div>
        </div>
    </div>

    <section class="content pb-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="asset-shell-card h-100">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>Filters</h3>
                                <p>Filter by related values, then choose whether to print selected QR labels or all visible labels.</p>
                            </div>
                            <div class="asset-shell-pill">Search</div>
                        </div>

                        <form method="get" action="<?= base_url('assets/qr-print-center') ?>">
                            <div class="form-group">
                                <label for="qrPrintSearch">Search</label>
                                <input id="qrPrintSearch" type="text" name="q" class="form-control" value="<?= $searchQuery ?>" placeholder="QR code, asset, department, user">
                            </div>

                            <div class="form-group">
                                <label for="qrPrintDepartment">Department</label>
                                <select id="qrPrintDepartment" name="department_id" class="form-control">
                                    <option value="">All departments</option>
                                    <?php foreach ($printDepartmentOptions as $department): ?>
                                        <option value="<?= (int) $department['department_id'] ?>" <?= $selectedDepartmentId === (int) $department['department_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $department['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="qrPrintAssetType">Asset Type</label>
                                <select id="qrPrintAssetType" name="asset_type" class="form-control">
                                    <option value="">All asset types</option>
                                    <?php foreach ($printAssetTypeOptions as $assetTypeOption): ?>
                                        <option value="<?= htmlspecialchars((string) $assetTypeOption) ?>" <?= $selectedAssetType === (string) $assetTypeOption ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $assetTypeOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="qrPrintSerialNumber">Serial Number</label>
                                <input id="qrPrintSerialNumber" type="text" name="serial_number" class="form-control" value="<?= $selectedSerialNumber ?>" placeholder="7152 or IT-MOUSE-7152">
                            </div>

                            <div class="form-group">
                                <label for="qrPrintMode">Print Mode</label>
                                <select id="qrPrintMode" class="form-control">
                                    <option value="all">Bulk Print All Visible QR</option>
                                    <option value="selected">Print Selected QR Only</option>
                                </select>
                                <small class="form-text text-muted">For one-by-one printing, use the `Print This` button on any QR card.</small>
                            </div>

                            <div class="form-group">
                                <label for="qrPrintCopies">Print Copies Per QR</label>
                                <input id="qrPrintCopies" type="number" name="copies" class="form-control" min="1" max="20" value="<?= (int) $defaultPrintCopies ?>">
                                <small class="form-text text-muted">Every printed QR label will repeat this many times.</small>
                            </div>

                            <div class="d-flex flex-wrap">
                                <button type="submit" class="btn btn-primary mr-2 mb-2">Search QR Records</button>
                                <button type="button" class="btn btn-success mb-2" id="printFilteredQrBtn" <?= empty($printAssets) ? 'disabled' : '' ?>>Print QR Labels</button>
                            </div>
                        </form>

                        <div class="asset-note mt-4">
                            QR cards keep a compact QR on one side and small attached detail blocks on the other, closer to the earlier layout.
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="asset-shell-card h-100">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>QR Records</h3>
                                <p><?= count($printAssets) ?> QR record(s) matched the current filters.</p>
                            </div>
                            <div class="asset-shell-pill">Print Ready</div>
                        </div>

                        <?php if (empty($printAssets)): ?>
                            <div class="asset-empty-state asset-empty-state--wide">
                                No QR records matched the selected filters. Try broadening the search or add more assets first.
                            </div>
                        <?php else: ?>
                            <div class="qr-print-grid" id="qrPrintGrid">
                                <?php foreach ($printAssets as $assetRow): ?>
                                    <article class="qr-print-card" data-print-card="true" data-serial="<?= htmlspecialchars((string) ($assetRow['display_serial_number'] ?? '')) ?>">
                                        <div class="qr-print-card__toolbar" data-print-control="true">
                                            <label class="qr-print-select">
                                                <input type="checkbox" class="qr-print-checkbox">
                                                <span>Select</span>
                                            </label>
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-print-single="true">Print This</button>
                                        </div>

                                        <div class="qr-print-card__top">
                                            <div class="qr-print-card__main">
                                                <div class="qr-print-card__serial">
                                                    <?= htmlspecialchars((string) ($assetRow['display_serial_number'] ?? 'Serial')) ?>
                                                </div>

                                                <div class="qr-print-card__media">
                                                    <?php if (!empty($assetRow['qr_image_url'])): ?>
                                                        <img src="<?= htmlspecialchars((string) $assetRow['qr_image_url']) ?>" alt="QR for <?= htmlspecialchars((string) ($assetRow['display_serial_number'] ?? 'Asset')) ?>" loading="lazy" decoding="async">
                                                    <?php else: ?>
                                                        <div class="asset-empty-state">QR image unavailable</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="qr-print-card__side">
                                                <div class="qr-print-card__details">
                                                    <div class="qr-print-card__detail">
                                                        <span class="qr-print-card__detail-label">Asset Name</span>
                                                        <span class="qr-print-card__detail-value"><?= htmlspecialchars((string) ($assetRow['asset_name'] ?? 'Not set')) ?></span>
                                                    </div>
                                                    <div class="qr-print-card__detail">
                                                        <span class="qr-print-card__detail-label">Asset Type</span>
                                                        <span class="qr-print-card__detail-value"><?= htmlspecialchars((string) ($assetRow['asset_type'] ?? 'Not set')) ?></span>
                                                    </div>
                                                    <div class="qr-print-card__detail">
                                                        <span class="qr-print-card__detail-label">Department</span>
                                                        <span class="qr-print-card__detail-value"><?= htmlspecialchars((string) ($assetRow['asset_department_name'] ?? 'Not set')) ?></span>
                                                    </div>
                                                   
                                                </div>
                                            </div>
                                        </div>

                                        <div class="qr-print-card__meta">
                                            <div class="qr-print-card__meta-row">
                                                <span class="qr-print-card__meta-label">Location</span>
                                                <span class="qr-print-card__meta-value"><?= htmlspecialchars((string) ($assetRow['location'] ?? 'Not set')) ?></span>
                                            </div>
                                            <div class="qr-print-card__meta-row">
                                                <span class="qr-print-card__meta-label">Assigned</span>
                                                <span class="qr-print-card__meta-value"><?= htmlspecialchars((string) ($assetRow['assigned_user_name'] ?? 'Not assigned')) ?></span>
                                            </div>
                                            <div class="qr-print-card__meta-row qr-print-card__meta-row--wide">
                                                <span class="qr-print-card__meta-label">Scan URL</span>
                                                <span class="qr-print-card__meta-value"><?= htmlspecialchars((string) ($assetRow['scan_url'] ?? '')) ?></span>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.qr-print-card__top {
    align-items: start;
    display: grid;
    gap: 1rem;
    grid-template-columns: 190px minmax(150px, 185px);
    justify-content: space-between;
}

.qr-print-card__main {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.qr-print-card__serial {
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(19, 78, 61, 0.12);
    border-radius: 12px;
    color: #173a2c;
    display: flex;
    font-size: 1.1rem;
    font-weight: 800;
    justify-content: center;
    letter-spacing: 0.08em;
    min-height: 42px;
    padding: 0.35rem 0.7rem;
}

.qr-print-card__side {
    min-width: 0;
}

.qr-print-card__details {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    text-align: left;
}

.qr-print-card__detail-label {
    color: #5c6d63;
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-bottom: 0.15rem;
    text-transform: uppercase;
}

.qr-print-card__detail-value {
    color: #173a2c;
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.28;
    word-break: break-word;
}

.qr-print-card__detail {
    background: rgba(243, 249, 245, 0.9);
    border: 1px solid rgba(19, 78, 61, 0.08);
    border-radius: 12px;
    min-height: 68px;
    padding: 0.55rem 0.65rem;
}

.qr-print-card__meta {
    border-top: 1px dashed rgba(19, 78, 61, 0.12);
    display: grid;
    gap: 0.45rem 0.9rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-top: 0.7rem;
    padding-top: 0.55rem;
    text-align: left;
}

.qr-print-card__meta-row {
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
}

.qr-print-card__meta-label {
    color: #6d7b74;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.qr-print-card__meta-value {
    color: #21473b;
    font-size: 0.72rem;
    line-height: 1.22;
    word-break: break-word;
}

.qr-print-card__media {
    min-height: 190px;
    padding: 0.6rem;
}

.qr-print-card__media img {
    max-height: 170px;
    max-width: 170px;
}

.qr-print-card__meta-row--wide {
    grid-column: 3;
}

@media (max-width: 991.98px) {
    .qr-print-card__top {
        grid-template-columns: 1fr;
    }

    .qr-print-card__meta {
        grid-template-columns: 1fr;
    }

    .qr-print-card__media {
        min-height: 220px;
    }

    .qr-print-card__media img {
        max-height: 190px;
        max-width: 190px;
    }

    .qr-print-card__meta-row--wide {
        grid-column: auto;
    }
}
</style>

<?php $this->load->view('Layout/Footer'); ?>
