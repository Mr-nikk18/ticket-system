<?php
$this->load->view('Layout/Header');
?>

<?php $dashScope = in_array((string) ($dashboard_scope ?? 'all'), ['mine', 'all', 'assigned'], true) ? (string) $dashboard_scope : 'all'; ?>
<div class="content-wrapper" id="mainContent" data-dashboard-scope="<?= htmlspecialchars($dashScope) ?>">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Dashboard</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right mr-3 align-items-center">
            <li class="breadcrumb-item">
              <a href="#">Home</a>
            </li>
            <li class="ml-2">
              <select id="dashboardScopeSelect" class="form-control form-control-sm" aria-label="Dashboard Ticket Scope">
                <option value="mine" <?= $dashScope === 'mine' ? 'selected' : '' ?>>Mine</option>
                <option value="all" <?= $dashScope === 'all' ? 'selected' : '' ?>>All</option>
                <option value="assigned" <?= $dashScope === 'assigned' ? 'selected' : '' ?>>Assigned</option>
              </select>
            </li>
            <li class="ml-2">
              <a href="<?= base_url('index.php/Auth/logout') ?>" class="btn btn-danger btn-sm px-2 py-1">
                Logout
              </a>
            </li>
          </ol>
          <div class="text-right mr-3 dashboard-scope-note">
            <?php if ((int) $this->session->userdata('department_id') === 2) { ?>
              <small>All = full IT dashboard totals, Mine = tickets created by you, Assigned = global Open + your assigned In Process, Resolved, and Closed.</small>
            <?php } else { ?>
              <small>All = all visible tickets, Mine = tickets created by you, Assigned = tickets assigned to you.</small>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ((int) $this->session->userdata('department_id') === 2) { ?>
        <div class="card dashboard-support-callout mb-4">
          <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
              <span class="dashboard-support-callout__eyebrow">Asset + QR Workspace</span>
              <h3 class="dashboard-support-callout__title">Asset entry and QR setup are ready to move</h3>
              <p class="mb-0 text-muted">Open the asset workspace to add single assets, prepare QR-linked records, and continue the ticket flow from one place.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex flex-wrap">
              <a href="<?= site_url('assets/create') ?>" class="btn btn-primary btn-sm px-3">Open Asset Entry</a>
            </div>
          </div>
        </div>
      <?php } ?>
      <?php
        $overview_modules = [];
        $activity_modules = [];
        $other_modules = [];

        foreach ($modules as $m) {
          $view_file = isset($m['view_file']) ? trim((string) $m['view_file']) : '';
          $view_name = preg_replace('/\.php$/i', '', $view_file);

          if (in_array($view_name, [
            'modules/total_ticket_box',
            'modules/open_ticket_box',
            'modules/inprocess_ticket_box',
            'modules/resolved_ticket_box',
            'modules/closed_ticket_box'
          ], true)) {
            $overview_modules[] = $view_name;
          } elseif ($view_name === 'modules/recent_ticket_table') {
            $activity_modules[] = $view_name;
          } elseif ($view_name !== '') {
            $other_modules[] = $view_name;
          }
        }

        $section_shell = [
          'id' => 'dashboard-workspace',
          'default' => 'overview',
          'eyebrow' => 'Trading-Style Workspace',
          'title' => 'Navigate dashboard sections from one surface',
          'description' => 'Switch between snapshot cards and live activity without leaving the dashboard route.',
          'badge' => count($modules) . ' live modules',
          'sections' => [
            ['id' => 'overview', 'label' => 'Overview', 'hint' => 'Ticket counters and quick state'],
            ['id' => 'activity', 'label' => 'Activity', 'hint' => 'Recent ticket flow'],
          ],
        ];

        if (!empty($other_modules)) {
          $section_shell['sections'][] = ['id' => 'extras', 'label' => 'Extras', 'hint' => 'Additional workspace modules'];
        }

      ?>
      <div class="trs-section-workspace" data-section-shell="dashboard-workspace" data-default-section="overview">
        <?php $this->load->view('Layout/section_shell_nav', ['section_shell' => $section_shell]); ?>

        <div class="trs-section-panels">
          <section class="trs-section-panel" data-section-panel="overview" hidden>
            <div class="trs-section-panel__body">
              <div class="row dashboard-overview-row">
                <?php foreach ($overview_modules as $view_name) { ?>
                  <?php $view_path = APPPATH . 'views/' . $view_name . '.php'; ?>
                  <?php if ($view_name !== '' && is_file($view_path)) { ?>
                    <?php $this->load->view($view_name); ?>
                  <?php } else { ?>
                    <?php log_message('error', 'Dashboard module view not found: ' . $view_name); ?>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
          </section>

          <section class="trs-section-panel" data-section-panel="activity" hidden>
            <div class="trs-section-panel__body">
              <div class="row">
                <?php foreach ($activity_modules as $view_name) { ?>
                  <?php $view_path = APPPATH . 'views/' . $view_name . '.php'; ?>
                  <?php if ($view_name !== '' && is_file($view_path)) { ?>
                    <?php $this->load->view($view_name); ?>
                  <?php } else { ?>
                    <?php log_message('error', 'Dashboard module view not found: ' . $view_name); ?>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
          </section>

          <?php if (!empty($other_modules)) { ?>
            <section class="trs-section-panel" data-section-panel="extras" hidden>
              <div class="trs-section-panel__body">
                <div class="row">
                  <?php foreach ($other_modules as $view_name) { ?>
                    <?php $view_path = APPPATH . 'views/' . $view_name . '.php'; ?>
                    <?php if ($view_name !== '' && is_file($view_path)) { ?>
                      <?php $this->load->view($view_name); ?>
                    <?php } else { ?>
                      <?php log_message('error', 'Dashboard module view not found: ' . $view_name); ?>
                    <?php } ?>
                  <?php } ?>
                </div>
              </div>
            </section>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
#mainContent {
  font-size: 0.95rem;
}

#mainContent .content-header h1,
#mainContent .card-title,
#mainContent .table th,
#mainContent .table td,
#mainContent .btn,
#mainContent .badge,
#mainContent .form-control,
#mainContent .custom-select {
  font-size: 0.92rem;
}

#mainContent .small-box .inner h3 {
  font-size: 1.2rem;
  margin-bottom: 0.35rem;
}

#mainContent .small-box .inner p {
  font-size: 1.1rem;
  margin-bottom: 0;
}

#mainContent .small-box .small-box-footer {
  font-size: 0.84rem;
}

.dashboard-scope-note small {
  color: #6c757d;
  font-size: 0.8rem;
}
.dashboard-support-callout {
  border: 1px solid rgba(31, 79, 140, 0.08);
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(231, 242, 255, 0.92));
}
.dashboard-support-callout__eyebrow {
  display: inline-block;
  margin-bottom: 0.45rem;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #1f6fb2;
}
.dashboard-support-callout__title {
  font-size: 1.1rem;
  margin-bottom: 0.35rem;
  color: #173a5e;
}
#mainContent .dashboard-overview-row {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 1rem;
  margin: 0;
}

#mainContent .dashboard-overview-row > .dashboard-overview-card {
  max-width: none;
  padding: 0;
  width: auto;
  flex: none;
  margin: 0;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box {
  position: relative;
  min-height: 160px;
  margin-bottom: 0;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  box-shadow: 0 18px 36px rgba(9, 16, 28, 0.2);
  color: #f8fbff;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0));
  pointer-events: none;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .inner,
#mainContent .dashboard-overview-row .dashboard-ticket-box .icon,
#mainContent .dashboard-overview-row .dashboard-ticket-box .small-box-footer {
  position: relative;
  z-index: 1;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .inner {
  padding-top: 1.15rem;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .inner h3,
#mainContent .dashboard-overview-row .dashboard-ticket-box .inner p,
#mainContent .dashboard-overview-row .dashboard-ticket-box .small-box-footer {
  color: #f8fbff !important;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .inner h3 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.7rem;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .inner p {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .icon {
  color: rgba(255, 255, 255, 0.2);
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .small-box-footer {
  background: rgba(3, 10, 20, 0.16);
  font-size: 0.86rem;
  font-weight: 600;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box .small-box-footer:hover {
  background: rgba(3, 10, 20, 0.26);
}

#mainContent .dashboard-overview-row .dashboard-ticket-box--total {
  background: linear-gradient(135deg, #12304a, #1d678f) !important;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box--open {
  background: linear-gradient(135deg, #184628, #27814d) !important;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box--process {
  background: linear-gradient(135deg, #7b4308, #c87e1f) !important;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box--resolved {
  background: linear-gradient(135deg, #253a72, #4970c2) !important;
}

#mainContent .dashboard-overview-row .dashboard-ticket-box--closed {
  background: linear-gradient(135deg, #6b2032, #b14765) !important;
}

@media (max-width: 1399.98px) {
  #mainContent .dashboard-overview-row {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  #mainContent .dashboard-overview-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 575.98px) {
  #mainContent .dashboard-overview-row {
    grid-template-columns: 1fr;
  }
}
</style>

<?php
$this->load->view('Layout/Footer');
?>



