<?php
$this->load->view('Layout/Header');

$asset_name = htmlspecialchars((string) ($asset->asset_name ?? 'Unknown Asset'));
$asset_type = htmlspecialchars((string) ($asset->asset_type ?? 'Not set'));
$serial_number = htmlspecialchars((string) ($asset->serial_number ?? 'Not set'));
$location = htmlspecialchars((string) ($asset->location ?? 'Not set'));
$asset_status = htmlspecialchars((string) ($asset->status ?? 'Not set'));
$assigned_user = htmlspecialchars((string) ($asset->assigned_user_name ?? 'Not assigned'));
$assigned_email = htmlspecialchars((string) ($asset->assigned_user_email ?? ''));
$qr_code_value = htmlspecialchars((string) ($asset->qr_code ?? ''));
$default_title_value = htmlspecialchars((string) ($default_title ?? 'QR Scan Ticket'));
?>

<div class="content-wrapper qr-ticket-page" id="mainContent">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-7">
                    <h1 class="m-0">QR Scanned Ticket Raise</h1>
                    <small class="text-muted">Scan recognized, asset details loaded, ticket can be raised from this page.</small>
                </div>
                <div class="col-sm-5">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('Dashboard') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('list') ?>">Tickets</a></li>
                        <li class="breadcrumb-item active">QR Raise</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content pb-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card qr-ticket-card qr-ticket-card--asset h-100">
                        <div class="card-body">
                            <div class="qr-ticket-card__eyebrow">Scanned Asset</div>
                            <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                                <div>
                                    <h2 class="qr-ticket-card__title mb-1"><?= $asset_name ?></h2>
                                    <p class="text-muted mb-0">This ticket will stay linked to the scanned company product.</p>
                                </div>
                                <span class="badge qr-ticket-badge"><?= $asset_status ?></span>
                            </div>

                            <div class="qr-ticket-info-grid">
                                <div class="qr-ticket-info-item">
                                    <span class="qr-ticket-info-label">Asset Type</span>
                                    <span class="qr-ticket-info-value"><?= $asset_type ?></span>
                                </div>
                                <div class="qr-ticket-info-item">
                                    <span class="qr-ticket-info-label">Serial Number</span>
                                    <span class="qr-ticket-info-value"><?= $serial_number ?></span>
                                </div>
                                <div class="qr-ticket-info-item">
                                    <span class="qr-ticket-info-label">Location</span>
                                    <span class="qr-ticket-info-value"><?= $location ?></span>
                                </div>
                                <div class="qr-ticket-info-item">
                                    <span class="qr-ticket-info-label">Assigned To</span>
                                    <span class="qr-ticket-info-value"><?= $assigned_user ?></span>
                                </div>
                                <div class="qr-ticket-info-item qr-ticket-info-item--full">
                                    <span class="qr-ticket-info-label">QR Code</span>
                                    <span class="qr-ticket-info-value qr-ticket-code"><?= $qr_code_value ?></span>
                                </div>
                                <?php if ($assigned_email !== '') { ?>
                                    <div class="qr-ticket-info-item qr-ticket-info-item--full">
                                        <span class="qr-ticket-info-label">Assigned Email</span>
                                        <span class="qr-ticket-info-value"><?= $assigned_email ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="qr-ticket-note mt-4">
                                Important details are preloaded from the asset master, so the user only needs to explain the issue and submit.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <form method="post" action="<?= base_url('qr-ticket/submit') ?>">
                        <input type="hidden" name="qr_code" value="<?= $qr_code_value ?>">

                        <div class="card qr-ticket-card qr-ticket-card--form">
                            <div class="card-body">
                                <div class="qr-ticket-card__eyebrow">Raise Ticket</div>
                                <h2 class="qr-ticket-card__title">Describe the problem after scan</h2>
                                <p class="text-muted mb-4">The ticket will be created in the existing TRS workflow and visible in the normal ticket list.</p>

                                <div class="form-group">
                                    <label for="qrTicketTitle">Title</label>
                                    <input
                                        id="qrTicketTitle"
                                        type="text"
                                        name="title"
                                        class="form-control form-control-lg"
                                        value="<?= $default_title_value ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="qrTicketDescription">Issue Description</label>
                                    <textarea
                                        id="qrTicketDescription"
                                        name="description"
                                        class="form-control qr-ticket-textarea"
                                        placeholder="Explain what problem happened after scanning this product or asset."
                                        required
                                    ></textarea>
                                </div>

                                <div class="form-group mb-4">
                                    <label>Tasks or checkpoints</label>
                                    <div id="taskWrapper" class="qr-ticket-task-list">
                                        <div class="input-group mb-2">
                                            <input type="text" name="tasks[]" class="form-control" placeholder="Example: Check power status">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-danger removeTask">X</button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addTaskFieldCreate" data-disable-on-click="false">
                                        Add More Task
                                    </button>
                                </div>

                                <div class="qr-ticket-submit-bar">
                                    <div>
                                        <div class="qr-ticket-submit-bar__label">Main flow</div>
                                        <div class="qr-ticket-submit-bar__text">QR scanned, asset detected, ticket raised.</div>
                                    </div>
                                    <div class="d-flex flex-wrap" style="gap:0.75rem;">
                                        <a href="<?= base_url('list') ?>" class="btn btn-light border">Back to Tickets</a>
                                        <button type="submit" class="btn btn-primary px-4">Submit Ticket</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.qr-ticket-page {
    background:
        radial-gradient(circle at top right, rgba(222, 126, 33, 0.14), transparent 24%),
        linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(239, 244, 249, 0.96));
}

.qr-ticket-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 24px;
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.qr-ticket-card--asset {
    background: linear-gradient(160deg, rgba(252, 248, 242, 0.98), rgba(245, 237, 225, 0.96));
}

.qr-ticket-card--form {
    background: rgba(255, 255, 255, 0.98);
}

.qr-ticket-card .card-body {
    padding: 1.6rem;
}

.qr-ticket-card__eyebrow {
    color: #8b5e34;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    margin-bottom: 0.7rem;
    text-transform: uppercase;
}

.qr-ticket-card__title {
    color: #1f2937;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.6rem;
}

.qr-ticket-badge {
    background: #123c66;
    color: #fff;
    font-size: 0.82rem;
    padding: 0.55rem 0.8rem;
}

.qr-ticket-info-grid {
    display: grid;
    gap: 0.9rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.qr-ticket-info-item {
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(139, 94, 52, 0.12);
    border-radius: 16px;
    padding: 0.95rem 1rem;
}

.qr-ticket-info-item--full {
    grid-column: 1 / -1;
}

.qr-ticket-info-label {
    color: #8b5e34;
    display: block;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-bottom: 0.35rem;
    text-transform: uppercase;
}

.qr-ticket-info-value {
    color: #243447;
    display: block;
    font-size: 0.96rem;
    line-height: 1.45;
}

.qr-ticket-code {
    font-family: "Courier New", monospace;
}

.qr-ticket-note {
    background: rgba(18, 60, 102, 0.08);
    border: 1px solid rgba(18, 60, 102, 0.12);
    border-radius: 18px;
    color: #23415e;
    padding: 1rem 1.1rem;
}

.qr-ticket-textarea {
    min-height: 180px;
    resize: vertical;
}

.qr-ticket-task-list .input-group .form-control {
    min-height: 50px;
}

.qr-ticket-submit-bar {
    align-items: center;
    background: linear-gradient(135deg, rgba(18, 60, 102, 0.06), rgba(222, 126, 33, 0.08));
    border: 1px solid rgba(18, 60, 102, 0.08);
    border-radius: 18px;
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    padding: 1rem 1.1rem;
}

.qr-ticket-submit-bar__label {
    color: #8b5e34;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.qr-ticket-submit-bar__text {
    color: #1f2937;
    font-size: 0.98rem;
    margin-top: 0.2rem;
}

@media (max-width: 767.98px) {
    .qr-ticket-card .card-body {
        padding: 1.15rem;
    }

    .qr-ticket-info-grid {
        grid-template-columns: 1fr;
    }

    .qr-ticket-submit-bar {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>

<?php
$this->load->view('Layout/Footer');
?>
