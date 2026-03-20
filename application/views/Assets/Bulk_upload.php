<?php
$this->load->view('Layout/Header');

$departmentMap = [];
foreach (($departments ?? []) as $department) {
    $departmentMap[strtolower(trim((string) ($department['department_name'] ?? '')))] = (int) ($department['department_id'] ?? 0);
}
?>

<div class="content-wrapper asset-page">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">Bulk Asset Upload</h1>
                    <p class="text-muted mb-0">Upload spreadsheet data, preview the rows, and let the system auto-generate QR codes with serial-based ticket links for every asset.</p>
                </div>
                <div class="col-sm-5 text-sm-right">
                    <a href="<?= base_url('assets/create') ?>" class="btn btn-outline-primary btn-sm mr-2">Single Asset Page</a>
                    <a href="<?= base_url('assets/manage') ?>" class="btn btn-outline-dark btn-sm mr-2">Manage Assets</a>
                    <a href="<?= base_url('assets/qr-print-center') ?>" class="btn btn-outline-secondary btn-sm mr-2">QR Print Center</a>
                    <a href="<?= htmlspecialchars((string) ($asset_import_template_url ?? '#')) ?>" class="btn btn-primary btn-sm">Download Template</a>
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
                                <h3>Spreadsheet Upload</h3>
                                <p>Select an Excel or CSV file. QR code values and QR images will be generated automatically during import.</p>
                            </div>
                            <div class="asset-shell-pill">Bulk Import</div>
                        </div>

                        <form id="assetBulkForm" method="post" action="<?= base_url('assets/store-bulk') ?>">
                            <div class="form-group">
                                <label for="assetSpreadsheetFile">Excel / CSV File</label>
                                <input id="assetSpreadsheetFile" type="file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                                <small class="form-text text-muted">
                                    Required columns: `asset_name`, `asset_type`, `serial_number`. Optional: `department_name`, `department_id`, `location`, `assigned_user_email`, `assigned_user_id`, `status`.
                                </small>
                            </div>

                            <input type="hidden" name="rows_json" id="assetImportRowsJson">

                            <button type="submit" id="submitAssetBulkUpload" class="btn btn-success" disabled>Upload Assets</button>
                        </form>

                        <div class="asset-note mt-4">
                            The import flow now ignores manual QR columns. Every saved row gets its own generated QR code, QR image, and ticket scan URL ending with the asset serial number.
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="asset-shell-card h-100">
                        <div class="asset-shell-card__header">
                            <div>
                                <h3>Preview Before Insert</h3>
                                <p>The preview adds generated QR and ticket-link columns so you can review the final scan flow before upload.</p>
                            </div>
                            <div class="asset-shell-pill">Client Preview</div>
                        </div>

                        <div class="asset-import-summary">
                            <div class="asset-import-summary__item">
                                <span class="asset-import-summary__label">Rows Parsed</span>
                                <span class="asset-import-summary__value" id="assetImportRowCount">0</span>
                            </div>
                            <div class="asset-import-summary__item">
                                <span class="asset-import-summary__label">Columns Found</span>
                                <span class="asset-import-summary__value" id="assetImportColumnCount">0</span>
                            </div>
                            <div class="asset-import-summary__item">
                                <span class="asset-import-summary__label">Rows Previewed</span>
                                <span class="asset-import-summary__value" id="assetImportPreviewCount">0</span>
                            </div>
                        </div>

                        <div id="assetImportEmpty" class="asset-empty-state">
                            Choose a spreadsheet file and the parsed rows will appear here before upload.
                        </div>

                        <div id="assetImportPreviewWrap" class="asset-import-table-wrap d-none">
                            <table class="table table-striped table-bordered mb-0 asset-import-table">
                                <thead id="assetImportPreviewHead"></thead>
                                <tbody id="assetImportPreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
window.AssetBulkUploadConfig = {
    assetQrBaseUrl: <?= json_encode((string) ($asset_qr_base_url ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    departmentMap: <?= json_encode($departmentMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
};
</script>

<?php $this->load->view('Layout/Footer'); ?>
