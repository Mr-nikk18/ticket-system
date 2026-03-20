<?php
$this->load->view('Layout/Header');

$section_shell = [
    'id' => 'project-support-workspace',
    'default' => 'ai',
    'eyebrow' => 'Project Delivery Workspace',
    'title' => 'AI support and QR generation from one place',
    'description' => 'Use the AI assistant for ticket-ready drafts, then create a QR link for quick sharing or handoff.',
    'badge' => $ai_enabled ? ('AI ready on ' . $ai_model) : 'AI ready after API key setup',
    'sections' => [
        ['id' => 'ai', 'label' => 'AI Support', 'hint' => 'Drafts, summaries, and handoffs'],
        ['id' => 'qr', 'label' => 'QR Studio', 'hint' => 'Ticket and share link QR generation'],
    ],
];

$project_support_js = [
    'ticketSnapshotUrl' => site_url('project-support/ticket-snapshot'),
    'aiAssistUrl' => site_url('project-support/ai-assist'),
    'dashboardUrl' => site_url('dashboard'),
    'ticketViewBaseUrl' => rtrim(base_url('TRS/view/'), '/'),
    'qrImageTemplate' => $qr_image_template,
    'aiEnabled' => (bool) $ai_enabled,
    'aiModel' => $ai_model,
];
?>

<div class="content-wrapper project-support-page">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <h1 class="m-0">Project Support Hub</h1>
          <p class="text-muted mb-0">AI support drafting plus QR generation for tickets and share links.</p>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Project Support</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card project-support-hero mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
          <div class="project-support-hero__copy">
            <span class="project-support-hero__eyebrow">Closeout Acceleration</span>
            <h2 class="project-support-hero__title">Keep today’s support, handoff, and QR tasks in one workflow</h2>
            <p class="project-support-hero__text mb-0">Load a ticket when you have one, generate internal or customer-facing AI output, and create a QR code for the shareable ticket link.</p>
          </div>
          <div class="project-support-hero__meta text-right">
            <div class="mb-2">
              <?php if ($ai_enabled) { ?>
                <span class="badge badge-success project-support-badge">AI Connected</span>
              <?php } else { ?>
                <span class="badge badge-warning project-support-badge">AI Needs `OPENAI_API_KEY`</span>
              <?php } ?>
            </div>
            <div class="small text-muted mb-3">Model target: <strong><?= htmlspecialchars($ai_model) ?></strong></div>
            <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-primary btn-sm">Back To Dashboard</a>
          </div>
        </div>
      </div>

      <?php if (!$ai_enabled) { ?>
        <div class="alert alert-warning">
          AI support is wired into the project, but it will stay disabled until the server has an `OPENAI_API_KEY` configured.
        </div>
      <?php } ?>

      <div class="trs-section-workspace" data-section-shell="project-support-workspace" data-default-section="ai">
        <?php $this->load->view('Layout/section_shell_nav', ['section_shell' => $section_shell]); ?>

        <div class="trs-section-panels">
          <section class="trs-section-panel" data-section-panel="ai" hidden>
            <div class="trs-section-panel__body">
              <div class="row">
                <div class="col-lg-5">
                  <div class="card h-100">
                    <div class="card-header">
                      <h3 class="card-title">AI Support Input</h3>
                    </div>
                    <div class="card-body">
                      <form id="projectSupportAiForm" data-double-submit-lock="false">
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            <label for="supportAction">Action</label>
                            <select id="supportAction" name="action" class="form-control">
                              <?php foreach ($support_actions as $value => $label) { ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                              <?php } ?>
                            </select>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="supportTone">Tone</label>
                            <select id="supportTone" name="tone" class="form-control">
                              <?php foreach ($support_tones as $value => $label) { ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>

                        <div class="form-group">
                          <label for="supportTicketId">Ticket ID</label>
                          <div class="input-group">
                            <input type="number" min="1" id="supportTicketId" name="ticket_id" class="form-control" placeholder="Load a ticket if you want project context">
                            <div class="input-group-append">
                              <button type="button" id="loadTicketForAi" class="btn btn-outline-secondary">Load Ticket</button>
                            </div>
                          </div>
                          <small class="form-text text-muted">Optional. If visible to you, title, description, tasks, and link will be pulled in.</small>
                        </div>

                        <div class="form-group">
                          <label for="supportTitle">Title</label>
                          <input type="text" id="supportTitle" name="title" class="form-control" placeholder="Short issue title">
                        </div>

                        <div class="form-group">
                          <label for="supportDescription">Description</label>
                          <textarea id="supportDescription" name="description" rows="6" class="form-control" placeholder="Issue description or problem statement"></textarea>
                        </div>

                        <div class="form-group">
                          <label for="supportContext">Additional Context</label>
                          <textarea id="supportContext" name="context" rows="5" class="form-control" placeholder="Logs, business impact, next steps, blockers, or manual notes"></textarea>
                        </div>

                        <div class="d-flex flex-wrap">
                          <button type="submit" id="generateSupportDraft" class="btn btn-primary mr-2">Generate AI Draft</button>
                          <button type="button" id="clearSupportForm" class="btn btn-outline-secondary">Clear</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="col-lg-7">
                  <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h3 class="card-title mb-0">Loaded Ticket Snapshot</h3>
                      <span id="supportTicketState" class="badge badge-light">No ticket loaded</span>
                    </div>
                    <div class="card-body">
                      <div id="supportTicketSnapshot" class="project-support-empty">
                        Load a visible ticket to pull project context into the assistant and QR generator.
                      </div>
                    </div>
                  </div>

                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h3 class="card-title mb-0">AI Output</h3>
                      <button type="button" id="copyAiOutput" class="btn btn-outline-secondary btn-sm">Copy Output</button>
                    </div>
                    <div class="card-body">
                      <div id="supportAiStatus" class="alert alert-light mb-3">Ready for a prompt.</div>
                      <textarea id="supportAiOutput" class="form-control project-support-output" rows="18" readonly placeholder="Generated AI output will appear here."></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="trs-section-panel" data-section-panel="qr" hidden>
            <div class="trs-section-panel__body">
              <div class="row">
                <div class="col-lg-5">
                  <div class="card h-100">
                    <div class="card-header">
                      <h3 class="card-title">QR Setup</h3>
                    </div>
                    <div class="card-body">
                      <div class="form-group">
                        <label for="qrTicketId">Ticket ID</label>
                        <div class="input-group">
                          <input type="number" min="1" id="qrTicketId" class="form-control" placeholder="Optional ticket ID">
                          <div class="input-group-append">
                            <button type="button" id="loadTicketForQr" class="btn btn-outline-secondary">Load Ticket</button>
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label for="qrPayload">QR Text / URL</label>
                        <textarea id="qrPayload" class="form-control" rows="5" placeholder="Paste a URL or use a ticket ID to build one automatically"></textarea>
                      </div>

                      <div class="form-row">
                        <div class="form-group col-md-6">
                          <label for="qrSize">Size</label>
                          <select id="qrSize" class="form-control">
                            <option value="180">180 x 180</option>
                            <option value="220" selected>220 x 220</option>
                            <option value="280">280 x 280</option>
                            <option value="360">360 x 360</option>
                          </select>
                        </div>
                        <div class="form-group col-md-6">
                          <label for="qrLabel">Label</label>
                          <input type="text" id="qrLabel" class="form-control" placeholder="Optional label">
                        </div>
                      </div>

                      <div class="d-flex flex-wrap">
                        <button type="button" id="generateQrCode" class="btn btn-primary mr-2">Generate QR</button>
                        <button type="button" id="copyQrPayload" class="btn btn-outline-secondary mr-2">Copy Link</button>
                        <a href="#" id="openQrPayload" class="btn btn-outline-dark" target="_blank" rel="noopener">Open Link</a>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-lg-7">
                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h3 class="card-title mb-0">QR Preview</h3>
                      <span id="qrState" class="badge badge-light">Waiting for content</span>
                    </div>
                    <div class="card-body">
                      <div class="project-support-qr-preview" id="qrPreviewFrame">
                        <img id="qrPreviewImage" alt="QR preview" class="project-support-qr-image d-none">
                        <div id="qrPreviewEmpty" class="project-support-empty">
                          Enter a ticket or paste a URL to generate the next QR code.
                        </div>
                      </div>
                      <div class="mt-3">
                        <label for="qrResolvedPayload">Resolved Payload</label>
                        <textarea id="qrResolvedPayload" class="form-control" rows="4" readonly placeholder="Resolved QR payload appears here"></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
window.ProjectSupportConfig = <?= json_encode($project_support_js, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php
$this->load->view('Layout/Footer');
?>
