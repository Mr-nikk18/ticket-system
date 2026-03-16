<?php
$this->load->view('Layout/Header');

$section_shell = [
    'id' => 'manual-ticket-workspace',
    'default' => 'details',
    'eyebrow' => 'Manual Ticket Entry',
    'title' => 'Create ticket in guided sections',
    'description' => 'Enter issue details, break work into tasks, then review and submit from the same page.',
    'badge' => 'Manual flow',
    'sections' => [
        ['id' => 'details', 'label' => 'Details', 'hint' => 'Title and issue summary'],
        ['id' => 'tasks', 'label' => 'Tasks', 'hint' => 'Breakdown and execution items'],
        ['id' => 'review', 'label' => 'Review', 'hint' => 'Checklist before submit'],
    ],
];
?>

<div class="content-wrapper ticket-manual-page" id="mainContent">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Ticket</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('Dashboard') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Create Ticket</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content pb-4">
        <div class="container-fluid">
            <form id="quickForm" method="post" action="<?= base_url('TRS/add') ?>">
                <div class="trs-section-workspace" data-section-shell="manual-ticket-workspace" data-default-section="details">
                    <?php $this->load->view('Layout/section_shell_nav', ['section_shell' => $section_shell]); ?>

                    <div class="trs-section-panels">
                        <section class="trs-section-panel" data-section-panel="details" hidden>
                            <div class="trs-section-panel__body">
                                <div class="ticket-manual-card">
                                    <div class="ticket-manual-card__header">
                                        <div>
                                            <h3>Issue Details</h3>
                                            <p>Capture the ticket title and a clear description so assignment stays accurate.</p>
                                        </div>
                                        <div class="ticket-manual-card__pill">Step 1</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="manualTicketTitle">Title</label>
                                                <input id="manualTicketTitle" type="text" name="title" class="form-control form-control-lg" placeholder="Enter ticket title" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group mb-0">
                                                <label for="manualTicketDescription">Description</label>
                                                <textarea id="manualTicketDescription" name="description" class="form-control ticket-manual-textarea" placeholder="Enter issue description with useful context" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="trs-section-panel" data-section-panel="tasks" hidden>
                            <div class="trs-section-panel__body">
                                <div class="ticket-manual-card">
                                    <div class="ticket-manual-card__header">
                                        <div>
                                            <h3>Task Breakdown</h3>
                                            <p>Create actionable task items for the engineer who will work on this ticket.</p>
                                        </div>
                                        <div class="ticket-manual-card__pill">Step 2</div>
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

                                    <div class="ticket-manual-note">
                                        Keep tasks short and specific. Example: `Check motherboard`, `Collect system logs`, `Replace RAM if failed`.
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="trs-section-panel" data-section-panel="review" hidden>
                            <div class="trs-section-panel__body">
                                <div class="ticket-manual-card">
                                    <div class="ticket-manual-card__header">
                                        <div>
                                            <h3>Review and Submit</h3>
                                            <p>Final checklist before the ticket enters the live workflow.</p>
                                        </div>
                                        <div class="ticket-manual-card__pill">Step 3</div>
                                    </div>

                                    <div class="ticket-review-grid">
                                        <div class="ticket-review-item">
                                            <span class="ticket-review-label">Checklist</span>
                                            <ul class="ticket-review-list">
                                                <li>Title clearly identifies the issue</li>
                                                <li>Description contains enough troubleshooting context</li>
                                                <li>Tasks are actionable and complete</li>
                                            </ul>
                                        </div>
                                        <div class="ticket-review-item">
                                            <span class="ticket-review-label">Submission</span>
                                            <div class="custom-control custom-checkbox mb-4">
                                                <input type="checkbox" name="terms" class="custom-control-input" id="terms" required>
                                                <label class="custom-control-label" for="terms">
                                                    I agree to the <a href="#">terms of service</a>
                                                </label>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-lg px-4">
                                                Add Ticket
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
.ticket-manual-page {
    background:
        radial-gradient(circle at top left, rgba(217, 164, 65, 0.15), transparent 24%),
        linear-gradient(180deg, rgba(255, 248, 238, 0.94), rgba(245, 238, 227, 0.94));
}

.ticket-manual-card {
    background: rgba(255, 253, 249, 0.95);
    border: 1px solid rgba(77, 57, 31, 0.12);
    border-radius: 24px;
    box-shadow: 0 18px 42px rgba(38, 25, 8, 0.08);
    padding: 1.5rem;
}

.ticket-manual-card__header {
    align-items: flex-start;
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    margin-bottom: 1.4rem;
}

.ticket-manual-card__header h3 {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.5rem;
    margin: 0;
}

.ticket-manual-card__header p {
    color: #6b7280;
    margin: 0.4rem 0 0;
    max-width: 660px;
}

.ticket-manual-card__pill {
    background: linear-gradient(135deg, #f7c35f, #f59e0b);
    border-radius: 999px;
    color: #1f2937;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.45rem 0.85rem;
    white-space: nowrap;
}

.ticket-manual-textarea {
    min-height: 180px;
    resize: vertical;
}

.ticket-manual-task-list .input-group .form-control {
    min-height: 50px;
}

.ticket-manual-note {
    background: #fff5df;
    border: 1px solid rgba(217, 164, 65, 0.28);
    border-radius: 16px;
    color: #7a5522;
    font-size: 0.92rem;
    padding: 0.95rem 1rem;
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

.ticket-review-label {
    color: #8b6b42;
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-bottom: 0.85rem;
    text-transform: uppercase;
}

.ticket-review-list {
    margin: 0;
    padding-left: 1.1rem;
}

.ticket-review-list li + li {
    margin-top: 0.55rem;
}

@media (max-width: 767.98px) {
    .ticket-manual-card {
        padding: 1.1rem;
    }

    .ticket-manual-card__header {
        flex-direction: column;
    }

    .ticket-review-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$this->load->view('Layout/Footer');
?>
