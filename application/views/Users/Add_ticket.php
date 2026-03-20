<?php
$this->load->view('Layout/Header');

$asset = isset($asset) && is_object($asset) ? $asset : null;
$has_asset = $asset !== null;
$form_action = $has_asset ? base_url('qr-ticket/submit') : base_url('TRS/add');
$default_title_value = htmlspecialchars((string) ($default_title ?? ''));
$asset_name = htmlspecialchars((string) ($asset->asset_name ?? 'No asset linked'));
$asset_type = htmlspecialchars((string) ($asset->asset_type ?? 'Not set'));
$serial_number = htmlspecialchars((string) ($asset->serial_number ?? 'Not set'));
$qr_code = htmlspecialchars((string) ($asset->qr_code ?? ''));
$location = htmlspecialchars((string) ($asset->location ?? 'Not set'));
$asset_status = htmlspecialchars((string) ($asset->status ?? 'Working'));
$assigned_user = htmlspecialchars((string) ($asset->assigned_user_name ?? 'Not assigned'));
$assigned_email = htmlspecialchars((string) ($asset->assigned_user_email ?? ''));
$qr_image_path = htmlspecialchars((string) ($asset->qr_image_path ?? ''));

$section_shell = [
    'id' => 'ticket-create-workspace',
    'default' => 'context',
    'eyebrow' => $has_asset ? 'QR + Manual Ticket Workspace' : 'Unified Ticket Workspace',
    'title' => 'Create ticket from one integrated page',
    'description' => $has_asset
        ? 'QR scan has already linked an asset. Review the context, add issue details, and submit from the same form.'
        : 'Create manual tickets or attach an asset by QR code without leaving this page.',
    'badge' => $has_asset ? 'QR asset loaded' : 'Manual and QR flow',
    'sections' => [
        ['id' => 'context', 'label' => 'Context', 'hint' => 'QR link and asset details'],
        ['id' => 'details', 'label' => 'Details', 'hint' => 'Title and issue summary'],
        ['id' => 'tasks', 'label' => 'Tasks', 'hint' => 'Break work into steps'],
        ['id' => 'review', 'label' => 'Review', 'hint' => 'Check and submit'],
    ],
];
?>

<div class="content-wrapper unified-ticket-page" id="mainContent">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $has_asset ? 'Raise Ticket From QR Asset' : 'Create Ticket' ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('Dashboard') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Generate Ticket</li>
                    </ol>
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

            <form id="quickForm" method="post" action="<?= $form_action ?>">
                <?php if ($has_asset): ?>
                    <input type="hidden" name="qr_code" value="<?= $qr_code ?>">
                    <input type="hidden" name="asset_id" value="<?= (int) $asset->asset_id ?>">
                <?php endif; ?>

                <div class="trs-section-workspace" data-section-shell="ticket-create-workspace" data-default-section="context">
                    <?php $this->load->view('Layout/section_shell_nav', ['section_shell' => $section_shell]); ?>

                    <div class="trs-section-panels">
                        <section class="trs-section-panel" data-section-panel="context" hidden>
                            <div class="trs-section-panel__body">
                                <div class="row">
                                    <div class="col-lg-5 mb-3 mb-lg-0">
                                        <div class="ticket-unified-card h-100">
                                            <div class="ticket-unified-card__header">
                                                <div>
                                                    <h3><?= $has_asset ? 'Scanned QR Context' : 'QR Link Asset' ?></h3>
                                                    <p><?= $has_asset ? 'This ticket came from a QR scan. The linked asset context is locked for this submission.' : 'Paste or scan a QR code value here to load an asset into the same ticket form.' ?></p>
                                                </div>
                                                <div class="ticket-unified-card__pill"><?= $has_asset ? 'Locked' : 'Optional' ?></div>
                                            </div>

                                            <?php if ($has_asset): ?>
                                                <div class="form-group">
                                                    <label for="assetQrLookup">Scanned QR / Serial</label>
                                                    <input
                                                        id="assetQrLookup"
                                                        type="text"
                                                        class="form-control"
                                                        value="<?= $serial_number !== 'Not set' ? $serial_number : $qr_code ?>"
                                                        readonly
                                                    >
                                                    <small class="form-text text-muted">
                                                        Scan route already completed. Login happened first, then this asset-specific ticket form opened with the linked asset details.
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <div class="form-group">
                                                    <label for="assetQrLookup">QR Code / Serial Number</label>
                                                    <div class="input-group">
                                                        <input
                                                            id="assetQrLookup"
                                                            type="text"
                                                            class="form-control"
                                                            value="<?= $qr_code ?>"
                                                            placeholder="Example: 62133 or TRS-ASSET-62133"
                                                        >
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-primary" id="loadTicketAssetByQr">Load</button>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        This redirects through the QR route and returns to this same ticket page with asset data attached. Serial-based routes are supported too.
                                                    </small>
                                                </div>
                                            <?php endif; ?>

                                            <div class="ticket-unified-note">
                                                <strong>One page flow:</strong> QR scan always points to the ticket route. If login is needed, the app asks for login first and then reopens this same form with asset values already attached.
                                            </div>

                                            <?php if ($has_asset): ?>
                                                <a href="<?= base_url('TRS/see') ?>" class="btn btn-outline-secondary btn-sm mt-3">Start Manual Ticket Instead</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-lg-7">
                                        <div class="ticket-unified-card h-100">
                                                <div class="ticket-unified-card__header">
                                                    <div>
                                                        <h3><?= $has_asset ? 'Linked Asset' : 'Ticket Context' ?></h3>
                                                        <p><?= $has_asset ? 'These asset values are auto-filled from the QR scan and are not editable in the ticket form.' : 'No asset linked yet. You can continue as a manual ticket or load one by QR.' ?></p>
                                                    </div>
                                                    <div class="ticket-unified-card__pill"><?= $has_asset ? $asset_status : 'Manual' ?></div>
                                                </div>

                                            <?php if ($has_asset): ?>
                                                <div class="ticket-asset-grid">
                                                    <div class="ticket-asset-card">
                                                        <span class="ticket-asset-label">Asset Name</span>
                                                        <span class="ticket-asset-value"><?= $asset_name ?></span>
                                                    </div>
                                                    <div class="ticket-asset-card">
                                                        <span class="ticket-asset-label">Asset Type</span>
                                                        <span class="ticket-asset-value"><?= $asset_type ?></span>
                                                    </div>
                                                    <div class="ticket-asset-card">
                                                        <span class="ticket-asset-label">Serial Number</span>
                                                        <span class="ticket-asset-value"><?= $serial_number ?></span>
                                                    </div>
                                                    <div class="ticket-asset-card">
                                                        <span class="ticket-asset-label">Location</span>
                                                        <span class="ticket-asset-value"><?= $location ?></span>
                                                    </div>
                                                    <div class="ticket-asset-card">
                                                        <span class="ticket-asset-label">Assigned To</span>
                                                        <span class="ticket-asset-value"><?= $assigned_user ?></span>
                                                    </div>
                                                    <div class="ticket-asset-card">
                                                        <span class="ticket-asset-label">Assigned Email</span>
                                                        <span class="ticket-asset-value"><?= $assigned_email !== '' ? $assigned_email : 'Not set' ?></span>
                                                    </div>
                                                    <div class="ticket-asset-card ticket-asset-card--wide">
                                                        <span class="ticket-asset-label">QR Code</span>
                                                        <span class="ticket-asset-value ticket-asset-value--code"><?= $qr_code ?></span>
                                                    </div>
                                                </div>

                                                <?php if ($qr_image_path !== ''): ?>
                                                    <div class="ticket-asset-image">
                                                        <img src="<?= base_url($qr_image_path) ?>" alt="Asset QR">
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="ticket-unified-empty">
                                                    Manual ticket creation is ready. If you already have an asset QR, load it above and the ticket will attach to that asset automatically.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="trs-section-panel" data-section-panel="details" hidden>
                            <div class="trs-section-panel__body">
                                <div class="ticket-unified-card">
                                    <div class="ticket-unified-card__header">
                                        <div>
                                            <h3>Issue Details</h3>
                                            <p>Write a clear title and enough detail so the assigned team can act immediately.</p>
                                        </div>
                                        <div class="ticket-unified-card__pill">Step 1</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="manualTicketTitle">Title</label>
                                        <input
                                            id="manualTicketTitle"
                                            type="text"
                                            name="title"
                                            class="form-control form-control-lg"
                                            placeholder="Enter ticket title"
                                            value="<?= $default_title_value ?>"
                                            required
                                        >
                                    </div>

                                    <div class="form-group mb-0">
                                        <label for="manualTicketDescription">Description</label>
                                        <textarea
                                            id="manualTicketDescription"
                                            name="description"
                                            class="form-control ticket-manual-textarea"
                                            placeholder="Explain the issue with all helpful context"
                                            required
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="trs-section-panel" data-section-panel="tasks" hidden>
                            <div class="trs-section-panel__body">
                                <div class="ticket-unified-card">
                                    <div class="ticket-unified-card__header">
                                        <div>
                                            <h3>Task Breakdown</h3>
                                            <p>List the steps, checks, or actions that should be handled for this ticket.</p>
                                        </div>
                                        <div class="ticket-unified-card__pill">Step 2</div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label>Tasks</label>
                                        <div id="taskWrapper" class="ticket-manual-task-list">
                                            <div class="input-group mb-2">
                                                <input type="text" name="tasks[]" class="form-control" placeholder="Enter task">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-danger removeTask">X</button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="addTaskFieldCreate" data-disable-on-click="false">
                                            + Add More Task
                                        </button>
                                    </div>

                                    <div class="ticket-unified-note">
                                        Keep tasks short and actionable. Example: `Check logs`, `Replace adapter`, `Confirm battery health`.
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="trs-section-panel" data-section-panel="review" hidden>
                            <div class="trs-section-panel__body">
                                <div class="ticket-unified-card">
                                    <div class="ticket-unified-card__header">
                                        <div>
                                            <h3>Review and Submit</h3>
                                            <p>Final review before the ticket enters the live workflow.</p>
                                        </div>
                                        <div class="ticket-unified-card__pill">Step 3</div>
                                    </div>

                                    <div class="ticket-review-grid">
                                        <div class="ticket-review-item">
                                            <span class="ticket-review-label">Checklist</span>
                                            <ul class="ticket-review-list">
                                                <li>Title clearly identifies the issue</li>
                                                <li>Description contains troubleshooting context</li>
                                                <li>Tasks are actionable and complete</li>
                                                <li><?= $has_asset ? 'The linked asset details look correct' : 'Asset link is optional for this ticket' ?></li>
                                            </ul>
                                        </div>

                                        <div class="ticket-review-item">
                                            <span class="ticket-review-label">Submission</span>
                                            <?php if ($has_asset): ?>
                                                <div class="ticket-review-asset">
                                                    <div class="ticket-review-asset__eyebrow">Asset Attached</div>
                                                    <div class="ticket-review-asset__title"><?= $asset_name ?></div>
                                                    <div class="ticket-review-asset__meta"><?= $qr_code ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="custom-control custom-checkbox mb-4">
                                                <input type="checkbox" name="terms" class="custom-control-input" id="terms" required>
                                                <label class="custom-control-label" for="terms">
                                                    I agree to the <a href="#">terms of service</a>
                                                </label>
                                            </div>

                                            <button type="submit" class="btn btn-success btn-lg px-4">
                                                <?= $has_asset ? 'Raise QR Ticket' : 'Add Ticket' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<style>
.unified-ticket-page {
    background:
        radial-gradient(circle at top left, rgba(217, 164, 65, 0.14), transparent 22%),
        linear-gradient(180deg, rgba(255, 248, 238, 0.96), rgba(245, 238, 227, 0.96));
}

.ticket-unified-card {
    background: rgba(255, 253, 249, 0.96);
    border: 1px solid rgba(77, 57, 31, 0.12);
    border-radius: 24px;
    box-shadow: 0 18px 42px rgba(38, 25, 8, 0.08);
    padding: 1.5rem;
}

.ticket-unified-card__header {
    align-items: flex-start;
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    margin-bottom: 1.35rem;
}

.ticket-unified-card__header h3 {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.45rem;
    margin: 0;
}

.ticket-unified-card__header p {
    color: #6b7280;
    margin: 0.35rem 0 0;
    max-width: 620px;
}

.ticket-unified-card__pill {
    background: linear-gradient(135deg, #f7c35f, #f59e0b);
    border-radius: 999px;
    color: #1f2937;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.45rem 0.85rem;
    white-space: nowrap;
}

.ticket-unified-note,
.ticket-unified-empty {
    background: #fff5df;
    border: 1px solid rgba(217, 164, 65, 0.28);
    border-radius: 16px;
    color: #7a5522;
    font-size: 0.93rem;
    padding: 0.95rem 1rem;
}

.ticket-asset-grid {
    display: grid;
    gap: 0.9rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.ticket-asset-card {
    background: rgba(255, 249, 240, 0.9);
    border: 1px solid rgba(77, 57, 31, 0.08);
    border-radius: 18px;
    padding: 1rem;
}

.ticket-asset-card--wide {
    grid-column: 1 / -1;
}

.ticket-asset-label,
.ticket-review-label {
    color: #8b6b42;
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-bottom: 0.55rem;
    text-transform: uppercase;
}

.ticket-asset-value {
    color: #1f2937;
    display: block;
    font-size: 1rem;
    font-weight: 600;
}

.ticket-asset-value--code {
    font-family: "Courier New", monospace;
    font-size: 0.95rem;
}

.ticket-asset-image {
    margin-top: 1.2rem;
    text-align: right;
}

.ticket-asset-image img {
    border: 1px solid rgba(31, 41, 55, 0.12);
    border-radius: 18px;
    max-height: 180px;
    max-width: 180px;
    padding: 0.5rem;
}

.ticket-manual-textarea {
    min-height: 180px;
    resize: vertical;
}

.ticket-manual-task-list .input-group .form-control {
    min-height: 50px;
}

.ticket-review-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.ticket-review-item {
    background: rgba(255, 249, 240, 0.88);
    border: 1px solid rgba(77, 57, 31, 0.09);
    border-radius: 20px;
    padding: 1.25rem;
}

.ticket-review-list {
    margin: 0;
    padding-left: 1.1rem;
}

.ticket-review-list li + li {
    margin-top: 0.55rem;
}

.ticket-review-asset {
    background: #ffffff;
    border: 1px solid rgba(31, 111, 178, 0.14);
    border-radius: 18px;
    margin-bottom: 1rem;
    padding: 0.95rem 1rem;
}

.ticket-review-asset__eyebrow {
    color: #1f6fb2;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    margin-bottom: 0.35rem;
    text-transform: uppercase;
}

.ticket-review-asset__title {
    color: #173a5e;
    font-weight: 700;
}

.ticket-review-asset__meta {
    color: #6b7280;
    font-family: "Courier New", monospace;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

@media (max-width: 991.98px) {
    .ticket-asset-grid,
    .ticket-review-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .ticket-unified-card {
        padding: 1.1rem;
    }

    .ticket-unified-card__header {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var loadButton = document.getElementById('loadTicketAssetByQr');
    var qrInput = document.getElementById('assetQrLookup');

    if (!loadButton || !qrInput) {
        return;
    }

    loadButton.addEventListener('click', function () {
        var value = (qrInput.value || '').trim();

        if (!value) {
            qrInput.focus();
            return;
        }

        window.location.href = '<?= base_url('qr-ticket/') ?>' + encodeURIComponent(value);
    });
});
</script>

<?php
$this->load->view('Layout/Footer');
?>
