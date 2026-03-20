<?php
$this->load->view('Layout/Header');

$old = isset($old_asset) && is_array($old_asset) ? $old_asset : [];

$assetName = htmlspecialchars((string) ($old['asset_name'] ?? ''));
$assetType = htmlspecialchars((string) ($old['asset_type'] ?? ''));
$serialNumber = htmlspecialchars((string) ($old['serial_number'] ?? ''));
$location = htmlspecialchars((string) ($old['location'] ?? ''));
$assignedUserId = (int) ($old['assigned_user_id'] ?? 0);
$selectedDepartmentId = (int) ($old['department_id'] ?? 0);
$selectedStatus = (string) ($old['status'] ?? 'Working');
$seriesStep = max(1, (int) ($old['series_step'] ?? 1));
$formMode = (($form_mode ?? 'create') === 'edit') ? 'edit' : 'create';
$formAction = (string) ($form_action ?? base_url('assets/store'));
$formSubmitLabel = (string) ($form_submit_label ?? 'Save Asset');
$formContinueLabel = (string) ($form_continue_label ?? 'Save + Continue Series');
$pageTitle = (string) ($page_title ?? 'Single Asset + QR Entry');
$pageDescription = (string) ($page_description ?? 'Type the asset details once, generate a department-linked QR, and continue the series for the next serial in one flow.');
$manageUrl = (string) ($asset_manage_url ?? base_url('assets/manage'));
?>

<div class="content-wrapper asset-page">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0"><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="text-muted mb-0"><?= htmlspecialchars($pageDescription) ?></p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= htmlspecialchars($manageUrl) ?>" class="btn btn-outline-dark btn-sm mr-2">Manage Assets</a>
                    <a href="<?= base_url('assets/bulk-upload') ?>" class="btn btn-outline-primary btn-sm mr-2">Bulk Upload Assets</a>
                    <a href="<?= base_url('assets/qr-print-center') ?>" class="btn btn-outline-secondary btn-sm mr-2">QR Print Center</a>
                    <a href="<?= base_url('TRS/see') ?>" class="btn btn-primary btn-sm">Open Ticket Page</a>
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
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="asset-shell-card">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>Asset Details</h3>
                                <p>Department, serial number, and QR link all stay connected. Leave serial blank if you want the system to use the last serial number and increase it by the selected step.</p>
                            </div>
                            <div class="asset-shell-pill"><?= $formMode === 'edit' ? 'Edit Record' : 'Single Entry' ?></div>
                        </div>

                        <form method="post" action="<?= htmlspecialchars($formAction) ?>" id="assetCreateForm">
                            <input type="hidden" name="qr_code" id="assetGeneratedQrCodeInput" value="">

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="assetName">Asset Name</label>
                                    <input id="assetName" type="text" name="asset_name" class="form-control" value="<?= $assetName ?>" placeholder="Mouse 7152 or Dell Latitude 5440" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="assetType">Asset Type</label>
                                    <input id="assetType" type="text" name="asset_type" class="form-control" value="<?= $assetType ?>" placeholder="Laptop / Mouse / Printer" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="assetSerialNumber">Serial Number</label>
                                    <input id="assetSerialNumber" type="text" name="serial_number" class="form-control" value="<?= $serialNumber ?>" placeholder="Leave blank for auto next serial, or enter 7152 / IT-MOUSE-7152">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="assetDepartment">Department</label>
                                    <select id="assetDepartment" name="department_id" class="form-control" required>
                                        <option value="">Select department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= (int) $department['department_id'] ?>" <?= $selectedDepartmentId === (int) $department['department_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) $department['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="assetLocation">Location</label>
                                    <input id="assetLocation" type="text" name="location" class="form-control" value="<?= $location ?>" placeholder="IT Lab - Desk 01">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="assetAssignedUser">Assigned User</label>
                                    <select id="assetAssignedUser" name="assigned_user_id" class="form-control">
                                        <option value="">Not assigned</option>
                                        <?php foreach ($assignable_users as $user): ?>
                                            <option value="<?= (int) $user['user_id'] ?>" data-department-id="<?= (int) ($user['department_id'] ?? 0) ?>" <?= $assignedUserId === (int) $user['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) $user['name']) ?><?= !empty($user['email']) ? ' (' . htmlspecialchars((string) $user['email']) . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="assetStatus">Status</label>
                                    <select id="assetStatus" name="status" class="form-control">
                                        <?php foreach ($status_options as $statusOption): ?>
                                            <option value="<?= htmlspecialchars($statusOption) ?>" <?= $selectedStatus === $statusOption ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($statusOption) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="assetSeriesStep">Series Step</label>
                                    <input id="assetSeriesStep" type="number" name="series_step" class="form-control" min="1" value="<?= (int) $seriesStep ?>">
                                    <small class="form-text text-muted">If the last serial number ends in digits, auto-suggest and continue-series will increase it by this step.</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label for="assetGeneratedQrCodeDisplay">Generated QR Code</label>
                                    <input id="assetGeneratedQrCodeDisplay" type="text" class="form-control" value="" readonly placeholder="Fill serial number and department to generate">
                                    <small class="form-text text-muted">The QR code is generated automatically and the ticket link carries the selected department ID.</small>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="assetQrSize">Preview Size</label>
                                    <select id="assetQrSize" class="form-control">
                                        <option value="220">220 x 220</option>
                                        <option value="280" selected>280 x 280</option>
                                        <option value="360">360 x 360</option>
                                    </select>
                                </div>
                            </div>

                            <div class="asset-note mb-4">
                                Scan flow: QR -> login if needed -> ticket form opens with the asset already linked. The scan URL now keeps only the serial number at the end, and existing ticket details stay visible after scan.
                            </div>

                            <div class="d-flex flex-wrap">
                                <button type="submit" class="btn btn-success mr-2 mb-2"><?= htmlspecialchars($formSubmitLabel) ?></button>
                                <?php if ($formMode !== 'edit' && $formContinueLabel !== ''): ?>
                                    <button type="submit" class="btn btn-outline-success mb-2" name="continue_series" value="1"><?= htmlspecialchars($formContinueLabel) ?></button>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($manageUrl) ?>" class="btn btn-outline-secondary mb-2">Back To Manage</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="asset-shell-card h-100">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>QR Preview</h3>
                                <p>The QR preview uses the real ticket-generation link, shows the current serial number above the code, and keeps the route clean.</p>
                            </div>
                            <div class="asset-shell-pill">Scan Ready</div>
                        </div>

                        <div class="asset-preview-serial" id="assetPreviewSerialLabel">Serial</div>

                        <div class="asset-preview-frame mb-3">
                            <img id="assetQrPreviewImage" src="" alt="Asset QR Preview" class="d-none">
                            <div id="assetQrPreviewEmpty" class="asset-empty-state">
                                Add the serial number and department. The QR preview and next-series-ready code will appear automatically.
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="assetQrPayload">Resolved Scan URL</label>
                            <textarea id="assetQrPayload" class="form-control" rows="4" readonly placeholder="The scan URL will appear here"></textarea>
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshAssetQrPreview">Refresh Preview</button>

                        <div class="asset-note mt-3">
                            Only the serial number appears at the end of the scan URL. Older QR-code-based records still continue to work in the background.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
window.AssetCreateConfig = {
    qrImageTemplate: <?= json_encode((string) ($qr_image_template ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    assetQrBaseUrl: <?= json_encode((string) ($asset_qr_base_url ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    previewEndpoint: <?= json_encode((string) ($asset_qr_preview_url ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    nextSerialEndpoint: <?= json_encode((string) ($asset_next_serial_url ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
};
</script>

<?php $this->load->view('Layout/Footer'); ?>
