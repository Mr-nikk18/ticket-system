<?php
$this->load->view('Layout/Header');

$developers = isset($developer) && is_array($developer) ? $developer : [];
$filters = isset($filters) && is_array($filters) ? $filters : ['companies' => [], 'departments' => []];
$overview = isset($overview) && is_array($overview) ? $overview : [];
$selectedYearValue = isset($selected_year) ? (int) $selected_year : (int) date('Y');
$fyLabel = 'FY ' . $selectedYearValue . '-' . substr((string) ($selectedYearValue + 1), -2);

$totalDevelopers = count($developers);
$companies = [];
$totalTickets = 0;
$totalResolved = 0;
$totalPending = 0;
$reviewerAssigned = (int) ($overview['reviewer_assigned'] ?? 0);
$acceptedTotal = (int) ($overview['accepted_total'] ?? 0);
$directReports = (int) ($overview['direct_reports'] ?? 0);
$totalReports = (int) ($overview['total_reports'] ?? 0);
$performanceSum = 0;

foreach ($developers as $dev) {
    if (!empty($dev['company_name'])) {
        $companies[$dev['company_name']] = true;
    }

    $devTotal = (int) $dev['total_tickets'];
    $devResolved = (int) $dev['resolved_tickets'];
    $devPending = (int) $dev['pending_tickets'];

    $totalTickets += $devTotal;
    $totalResolved += $devResolved;
    $totalPending += $devPending;
    $performanceSum += $devTotal > 0 ? round(($devResolved / $devTotal) * 100) : 0;
}

$avgPerformance = $totalDevelopers > 0 ? round($performanceSum / $totalDevelopers) : 0;
$totalCompanies = count($companies);
?>

<div class="content-wrapper developer-performance-page">
  <section class="content-header pb-0">
    <div class="container-fluid">
      <div class="perf-hero">
        <div>
          <span class="perf-kicker">Performance Command Center</span>
          <h1>Developer Performance</h1>
          <p>Promotion review, assignment control, live delivery state and reporting hierarchy for <?= htmlspecialchars($fyLabel) ?></p>
        </div>
        <form method="get" class="perf-year-form">
          <label for="developerPerfYear" class="mb-1">Financial Year</label>
          <div class="d-flex">
            <input
              id="developerPerfYear"
              type="number"
              name="year"
              class="form-control"
              value="<?= $selectedYearValue ?>"
              min="2024"
              max="<?= date('Y') + 1 ?>">
            <button type="submit" class="btn btn-warning ml-2">Apply</button>
          </div>
        </form>
      </div>

      <div class="row mt-4">
        <div class="col-xl-3 col-md-6 mb-3">
          <div class="perf-stat-card theme-amber">
            <div class="perf-stat-icon"><i class="fas fa-sitemap"></i></div>
            <div>
              <div class="perf-stat-value" id="perfOverviewTotalReports"><?= $totalReports ?></div>
              <div class="perf-stat-label">Hierarchy Members</div>
              <div class="perf-stat-sub">Direct reports <?= $directReports ?></div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
          <div class="perf-stat-card theme-indigo">
            <div class="perf-stat-icon"><i class="fas fa-hand-point-right"></i></div>
            <div>
              <div class="perf-stat-value" id="perfOverviewAssignedByYou"><?= $reviewerAssigned ?></div>
              <div class="perf-stat-label">Assigned By You</div>
              <div class="perf-stat-sub">Tracked from assignment history</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
          <div class="perf-stat-card theme-teal">
            <div class="perf-stat-icon"><i class="fas fa-check-double"></i></div>
            <div>
              <div class="perf-stat-value" id="perfOverviewAccepted"><?= $acceptedTotal ?></div>
              <div class="perf-stat-label">Accepted By Team</div>
              <div class="perf-stat-sub">Self-accept entries across reports</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
          <div class="perf-stat-card theme-carbon">
            <div class="perf-stat-icon"><i class="fas fa-chart-line"></i></div>
            <div>
              <div class="perf-stat-value"><?= $avgPerformance ?>%</div>
              <div class="perf-stat-label">Avg Resolution Score</div>
              <div class="perf-stat-sub"><?= $totalTickets ?> tickets reviewed</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="content pb-4">
    <div class="container-fluid">
      <div class="row">
        <div class="col-xl-7">
          <div class="perf-panel perf-panel-lg mb-4">
            <div class="perf-panel-head">
              <div>
                <h3>Review Queue</h3>
                <p>Each card shows assignment load, self-accept count, open pressure and reporting depth.</p>
              </div>
              <div class="perf-panel-tools">
                <button type="button" class="btn perf-filter-trigger" id="developerFilterToggle">
                  <i class="fas fa-filter"></i>
                </button>
              </div>
            </div>
            <div class="perf-filter-panel" id="developerFilterPanel" style="display:none;">
              <div class="row">
                <div class="col-lg-4 mb-3">
                  <label class="perf-field-label">Search</label>
                  <input type="text" id="developerPerformanceSearch" class="form-control" placeholder="Name or company">
                </div>
                <div class="col-lg-4 mb-3">
                  <label class="perf-field-label">Company</label>
                  <select id="developerCompanyFilter" class="form-control">
                    <option value="">All Companies</option>
                    <?php foreach ($filters['companies'] as $company) { ?>
                      <option value="<?= htmlspecialchars($company) ?>"><?= htmlspecialchars($company) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="col-lg-4 mb-3">
                  <label class="perf-field-label">Department</label>
                  <select id="developerPerformanceDepartmentFilter" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($filters['departments'] as $departmentName) { ?>
                      <option value="<?= htmlspecialchars($departmentName) ?>"><?= htmlspecialchars($departmentName) ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="perf-overview-chart-wrap">
              <div>
                <span class="perf-mini-label">Team state distribution</span>
                <h4>Live Ticket Status</h4>
              </div>
              <canvas id="perfOverviewStatusChart" height="120"></canvas>
            </div>
          </div>

          <div class="row" id="developerPerformanceGrid">
            <?php if (!empty($developers)) { ?>
              <?php foreach ($developers as $dev) { ?>
                <?php
                $total = (int) $dev['total_tickets'];
                $assigned = (int) $dev['assigned_tickets'];
                $accepted = (int) $dev['accepted_tickets'];
                $resolved = (int) $dev['resolved_tickets'];
                $pending = (int) $dev['pending_tickets'];
                $reviewerAssignedTickets = (int) ($dev['reviewer_assigned_tickets'] ?? 0);
                $devDirectReports = (int) ($dev['direct_reports'] ?? 0);
                $score = $total > 0 ? round(($resolved / $total) * 100) : 0;
                $assignedPct = $total > 0 ? round(($assigned / $total) * 100) : 0;
                $acceptedPct = $total > 0 ? round(($accepted / $total) * 100) : 0;
                $resolvedPct = $total > 0 ? round(($resolved / $total) * 100) : 0;
                $pendingPct = $total > 0 ? round(($pending / $total) * 100) : 0;
                $initials = strtoupper(substr(trim((string) $dev['name']), 0, 1));
                ?>
                <div
                  class="col-xl-6 developer-performance-item"
                  data-name="<?= htmlspecialchars(strtolower($dev['name'])) ?>"
                  data-company="<?= htmlspecialchars(strtolower($dev['company_name'] ?: 'no company')) ?>"
                  data-department="<?= htmlspecialchars(strtolower($dev['department_name'] ?: 'no department')) ?>">
                  <div class="perf-dev-card" data-developer-id="<?= (int) $dev['user_id'] ?>">
                    <div class="perf-dev-glow"></div>
                    <div class="perf-dev-header">
                      <div class="perf-dev-avatar"><?= htmlspecialchars($initials) ?></div>
                      <div class="perf-dev-meta">
                        <h4><?= htmlspecialchars($dev['name']) ?></h4>
                        <div class="perf-dev-role"><?= htmlspecialchars($dev['company_name'] ?: 'TRS') ?> / <?= htmlspecialchars($dev['department_name'] ?: 'No Department') ?></div>
                      </div>
                      <div class="perf-dev-score"><?= $score ?>%</div>
                    </div>

                    <div class="perf-chip-row">
                      <span class="perf-chip soft">You assigned <?= $reviewerAssignedTickets ?></span>
                      <span class="perf-chip dark">Accepted <?= $accepted ?></span>
                      <span class="perf-chip amber">Reports <?= $devDirectReports ?></span>
                    </div>

                    <div class="perf-metric-list">
                      <div class="perf-metric-row">
                        <span>Assigned</span>
                        <div class="perf-metric-bar"><span style="width: <?= $assignedPct ?>%"></span></div>
                        <strong><?= $assigned ?></strong>
                      </div>
                      <div class="perf-metric-row">
                        <span>Accepted</span>
                        <div class="perf-metric-bar metric-blue"><span style="width: <?= $acceptedPct ?>%"></span></div>
                        <strong><?= $accepted ?></strong>
                      </div>
                      <div class="perf-metric-row">
                        <span>Resolved</span>
                        <div class="perf-metric-bar metric-green"><span style="width: <?= $resolvedPct ?>%"></span></div>
                        <strong><?= $resolved ?></strong>
                      </div>
                      <div class="perf-metric-row">
                        <span>Pending</span>
                        <div class="perf-metric-bar metric-amber"><span style="width: <?= $pendingPct ?>%"></span></div>
                        <strong><?= $pending ?></strong>
                      </div>
                    </div>

                    <div class="perf-dev-footer">
                      <div>
                        <span class="perf-mini-label">Current load</span>
                        <strong><?= $total ?> tickets</strong>
                      </div>
                      <div>
                        <span class="perf-mini-label">Open balance</span>
                        <strong><?= $pending ?></strong>
                      </div>
                      <div class="perf-link-label">Click to inspect</div>
                    </div>
                  </div>
                </div>
              <?php } ?>
            <?php } else { ?>
              <div class="col-12">
                <div class="perf-panel text-center text-muted py-5">
                  No developer data available for <?= $selectedYearValue ?>.
                </div>
              </div>
            <?php } ?>
          </div>
        </div>

        <div class="col-xl-5">
          <div class="perf-panel perf-sticky-panel">
            <div class="perf-workspace-tabs">
              <button type="button" class="perf-tab-btn is-active" data-target="detail">Performance</button>
              <button type="button" class="perf-tab-btn" data-target="hierarchy">Hierarchy Chart</button>
            </div>

            <div class="perf-workspace-pane" id="perfDetailPane">
              <div class="perf-pane-head">
                <div>
                  <span class="perf-mini-label">Inspection workspace</span>
                  <h3 id="perfDetailHeading">Select a developer</h3>
                </div>
                <div class="perf-inline-badge"><?= htmlspecialchars($fyLabel) ?></div>
              </div>
              <div id="developerPerformanceDetailBody" class="perf-detail-body">
                <div class="perf-empty-state">
                  <i class="fas fa-user-chart"></i>
                  <h4>Detail will load here</h4>
                  <p>Card click se accepted tickets, assigned by you, live state, monthly load aur tables isi panel me khulenge.</p>
                </div>
              </div>
            </div>

            <div class="perf-workspace-pane" id="perfHierarchyPane" style="display:none;">
              <div class="perf-pane-head">
                <div>
                  <span class="perf-mini-label">Reporting structure</span>
                  <h3>Hierarchy Manager</h3>
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm" id="refreshHierarchyBtn">Refresh</button>
              </div>
              <div class="perf-hierarchy-layout">
                <div class="perf-hierarchy-tree-wrap">
                  <div id="developerHierarchyTree" class="perf-hierarchy-tree">
                    <div class="text-muted py-4 text-center">Hierarchy loading...</div>
                  </div>
                </div>
                <div class="perf-hierarchy-side">
                  <div id="developerHierarchyInspector" class="perf-hierarchy-inspector">
                    <div class="perf-empty-state compact">
                      <i class="fas fa-project-diagram"></i>
                      <h4>Select a node</h4>
                      <p>Hierarchy node click karke summary dekho aur reporting manager update karo.</p>
                    </div>
                  </div>
                  <div class="perf-inline-form">
                    <div class="perf-form-title" id="developerHierarchyFormTitle">Attach or Reassign User</div>
                    <div class="perf-inline-helper" id="developerHierarchyFormHelper">Hierarchy node select karke uske niche direct member add ya move karo.</div>
                    <form id="developerHierarchyForm">
                      <div class="form-group">
                        <label class="perf-field-label">Team Member</label>
                        <select id="hierarchyTargetUser" name="target_user_id" class="form-control" required>
                          <option value="">Select user</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label class="perf-field-label">Reports To</label>
                        <select id="hierarchyManagerUser" name="reports_to_user_id" class="form-control" required>
                          <option value="">Select manager</option>
                        </select>
                      </div>
                      <button type="submit" class="btn btn-dark btn-block">Save Hierarchy</button>
                    </form>
                    <div id="developerHierarchyMessage" class="perf-inline-message"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
:root {
  --perf-bg: #f6f1e7;
  --perf-panel: #fffaf2;
  --perf-panel-strong: #ffffff;
  --perf-ink: #1f2937;
  --perf-muted: #6b7280;
  --perf-line: rgba(99, 69, 31, 0.12);
  --perf-shadow: 0 18px 48px rgba(45, 31, 12, 0.08);
  --perf-gold: #d9a441;
  --perf-amber: #f59e0b;
  --perf-copper: #b45309;
  --perf-navy: #1f3b5c;
  --perf-teal: #0f766e;
  --perf-slate: #334155;
}

.developer-performance-page {
  background:
    radial-gradient(circle at top left, rgba(217, 164, 65, 0.18), transparent 28%),
    linear-gradient(180deg, #fbf7ef 0%, #f3ece1 100%);
  color: var(--perf-ink);
}

.perf-hero {
  background: linear-gradient(135deg, rgba(32, 25, 18, 0.96), rgba(82, 56, 18, 0.92));
  color: #fff6e8;
  border-radius: 28px;
  padding: 1.75rem;
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: end;
  box-shadow: var(--perf-shadow);
}

.perf-kicker,
.perf-mini-label {
  display: inline-block;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.72rem;
  color: rgba(255, 243, 224, 0.74);
}

.perf-hero h1,
.perf-panel h3,
.perf-panel h4,
.perf-empty-state h4 {
  font-family: Georgia, "Times New Roman", serif;
}

.perf-hero h1 {
  margin: 0.35rem 0 0.45rem;
  font-size: 1.95rem;
}

.perf-hero p {
  margin: 0;
  max-width: 700px;
  color: rgba(255, 243, 224, 0.86);
  font-size: 0.98rem;
}

.perf-year-form {
  min-width: 220px;
}

.perf-stat-card,
.perf-panel,
.perf-dev-card {
  background: rgba(255, 251, 244, 0.95);
  border: 1px solid var(--perf-line);
  border-radius: 24px;
  box-shadow: var(--perf-shadow);
}

.perf-stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.95rem 1rem;
  min-height: 98px;
}

.perf-stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #fff;
}

.theme-amber .perf-stat-icon { background: linear-gradient(135deg, #f59e0b, #b45309); }
.theme-indigo .perf-stat-icon { background: linear-gradient(135deg, #345d7f, #172b41); }
.theme-teal .perf-stat-icon { background: linear-gradient(135deg, #0f766e, #134e4a); }
.theme-carbon .perf-stat-icon { background: linear-gradient(135deg, #111827, #475569); }

.perf-stat-value {
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1;
}

.perf-stat-label {
  font-size: 0.94rem;
  color: var(--perf-muted);
  margin-top: 0.2rem;
}

.perf-stat-sub {
  font-size: 0.78rem;
  color: #8b7355;
  margin-top: 0.35rem;
}

.perf-panel {
  padding: 1rem;
}

.perf-panel-lg {
  padding-bottom: 1rem;
}

.perf-panel-head,
.perf-pane-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.perf-panel-head p,
.perf-pane-head p {
  color: var(--perf-muted);
  margin-bottom: 0;
}

.perf-filter-trigger {
  width: 46px;
  height: 46px;
  border-radius: 16px;
  border: 1px solid var(--perf-line);
  background: #fff;
  color: var(--perf-navy);
}

.perf-filter-panel {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed rgba(99, 69, 31, 0.18);
}

.perf-field-label {
  font-size: 0.78rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #7c6748;
}

.perf-overview-chart-wrap {
  margin-top: 1.2rem;
  background: linear-gradient(180deg, rgba(255, 252, 247, 0.95), rgba(249, 242, 230, 0.95));
  border: 1px solid rgba(99, 69, 31, 0.1);
  border-radius: 20px;
  padding: 1rem 1rem 0.5rem;
  min-height: 320px;
  overflow: hidden;
}

.perf-dev-card {
  position: relative;
  overflow: hidden;
  padding: 0.95rem;
  margin-bottom: 1rem;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.perf-dev-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 22px 54px rgba(45, 31, 12, 0.12);
}

.perf-dev-glow {
  position: absolute;
  inset: 0 auto auto 0;
  width: 100%;
  height: 5px;
  background: linear-gradient(90deg, #f59e0b, #fcd34d, #1f3b5c);
}

.perf-dev-header {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  margin-bottom: 1rem;
}

.perf-dev-avatar {
  width: 46px;
  height: 46px;
  border-radius: 16px;
  background: linear-gradient(135deg, #1f3b5c, #5b7ca0);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.perf-dev-meta h4 {
  margin: 0;
  font-size: 0.96rem;
}

.perf-dev-role {
  color: var(--perf-muted);
  font-size: 0.78rem;
}

.perf-dev-score {
  margin-left: auto;
  background: #1f3b5c;
  color: #fff;
  min-width: 54px;
  text-align: center;
  border-radius: 14px;
  padding: 0.45rem 0.6rem;
  font-weight: 700;
}

.perf-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-bottom: 1rem;
}

.perf-chip {
  border-radius: 999px;
  padding: 0.28rem 0.7rem;
  font-size: 0.72rem;
  font-weight: 700;
}

.perf-chip.soft { background: #eef4ff; color: #21436a; }
.perf-chip.dark { background: #1f2937; color: #fff; }
.perf-chip.amber { background: #fff0d3; color: #a16207; }

.perf-metric-list {
  display: grid;
  gap: 0.7rem;
}

.perf-metric-row {
  display: grid;
  grid-template-columns: 86px 1fr 40px;
  gap: 0.7rem;
  align-items: center;
  font-size: 0.86rem;
}

.perf-metric-bar {
  height: 9px;
  border-radius: 999px;
  background: #e8e2d7;
  overflow: hidden;
}

.perf-metric-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #1f3b5c, #5982a7);
}

.metric-blue span { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.metric-green span { background: linear-gradient(90deg, #15803d, #4ade80); }
.metric-amber span { background: linear-gradient(90deg, #b45309, #f59e0b); }

.perf-dev-footer {
  margin-top: 1rem;
  padding-top: 0.8rem;
  border-top: 1px solid rgba(99, 69, 31, 0.12);
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  align-items: center;
  font-size: 0.84rem;
}

.perf-link-label {
  justify-self: end;
  color: #21436a;
  font-weight: 700;
}

.perf-sticky-panel {
  position: sticky;
  top: 1rem;
}

.perf-workspace-tabs {
  display: flex;
  background: #f6ecdb;
  border-radius: 18px;
  padding: 0.3rem;
  margin-bottom: 1rem;
}

.perf-tab-btn {
  flex: 1;
  border: 0;
  background: transparent;
  border-radius: 14px;
  padding: 0.7rem 0.8rem;
  color: #7c6748;
  font-weight: 700;
}

.perf-tab-btn.is-active {
  background: linear-gradient(135deg, #1f3b5c, #35587b);
  color: #fff;
  box-shadow: 0 10px 24px rgba(31, 59, 92, 0.22);
}

.perf-inline-badge {
  background: #fff0d3;
  color: #8a5200;
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  font-weight: 700;
}

.perf-empty-state {
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: var(--perf-muted);
}

.perf-empty-state i {
  font-size: 2rem;
  color: var(--perf-gold);
  margin-bottom: 0.8rem;
}

.perf-empty-state.compact {
  min-height: 180px;
}

.perf-detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.perf-detail-stat {
  background: linear-gradient(180deg, #fffdf9, #f6eee1);
  border: 1px solid rgba(99, 69, 31, 0.12);
  border-radius: 18px;
  padding: 0.95rem 1rem;
}

.perf-detail-stat .label {
  color: var(--perf-muted);
  font-size: 0.84rem;
}

.perf-detail-stat .value {
  font-size: 1.55rem;
  font-weight: 700;
  margin-top: 0.3rem;
}

.perf-section-block {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(99, 69, 31, 0.12);
}

.perf-chart-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.perf-chart-card {
  background: #fffdf9;
  border: 1px solid rgba(99, 69, 31, 0.12);
  border-radius: 20px;
  padding: 0.85rem;
  min-height: 320px;
  overflow: hidden;
}

.perf-chart-card canvas {
  width: 100% !important;
  max-width: 100% !important;
  display: block;
}

#perfOverviewStatusChart {
  height: 240px !important;
  max-height: 240px !important;
}

#perfDetailStatusChart,
#perfDetailVolumeChart {
  height: 240px !important;
  max-height: 240px !important;
}

.perf-subordinate-list,
.perf-assigner-list {
  display: grid;
  gap: 0.65rem;
}

.perf-subordinate-item,
.perf-assigner-item {
  border: 1px solid rgba(99, 69, 31, 0.12);
  background: #fff;
  border-radius: 16px;
  padding: 0.75rem 0.9rem;
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  align-items: center;
}

.perf-subordinate-meta small,
.perf-assigner-item small {
  display: block;
  color: var(--perf-muted);
}

.perf-assigner-count {
  background: #1f3b5c;
  color: #fff;
  border-radius: 999px;
  padding: 0.25rem 0.65rem;
  font-weight: 700;
}

.perf-table-wrap {
  border: 1px solid rgba(99, 69, 31, 0.12);
  border-radius: 18px;
  overflow: hidden;
  background: #fff;
}

.perf-table-wrap table {
  margin-bottom: 0;
}

.perf-status-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.7rem;
  font-size: 0.74rem;
  font-weight: 700;
  background: #edf3ff;
  color: #21436a;
}

.perf-hierarchy-layout {
  display: grid;
  grid-template-columns: 1.2fr 0.95fr;
  gap: 1rem;
}

.perf-hierarchy-tree-wrap,
.perf-hierarchy-inspector,
.perf-inline-form {
  border: 1px solid rgba(99, 69, 31, 0.12);
  border-radius: 20px;
  background: #fffdf9;
}

.perf-hierarchy-tree-wrap {
  padding: 1rem;
  min-height: 560px;
  overflow: auto;
}

.perf-hierarchy-inspector {
  padding: 1rem;
  margin-bottom: 1rem;
}

.perf-inline-form {
  padding: 1rem;
}

.perf-form-title {
  font-weight: 700;
  margin-bottom: 0.85rem;
}

.perf-inline-message {
  margin-top: 0.85rem;
  font-size: 0.85rem;
  min-height: 1.2rem;
}

.perf-inline-helper {
  color: var(--perf-muted);
  font-size: 0.86rem;
  margin-bottom: 0.85rem;
}

.perf-inline-message.success { color: #166534; }
.perf-inline-message.error { color: #b91c1c; }

.perf-tree-root,
.perf-tree-root ul {
  list-style: none;
  margin: 0;
  padding-left: 1.1rem;
}

.perf-tree-root > li {
  padding-left: 0;
}

.perf-tree-root ul {
  position: relative;
}

.perf-tree-root ul::before {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0.35rem;
  width: 1px;
  background: rgba(99, 69, 31, 0.18);
}

.perf-tree-node {
  position: relative;
  margin-bottom: 0.9rem;
}

.perf-tree-node::before {
  content: "";
  position: absolute;
  top: 1rem;
  left: -0.7rem;
  width: 0.7rem;
  height: 1px;
  background: rgba(99, 69, 31, 0.18);
}

.perf-tree-card {
  width: 100%;
  text-align: left;
  border: 1px solid rgba(99, 69, 31, 0.12);
  border-radius: 18px;
  background: linear-gradient(180deg, #fffdf8, #f7efdf);
  padding: 0.85rem 0.9rem;
}

.perf-tree-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.perf-tree-actions {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.perf-tree-action {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  border: 0;
  background: rgba(255, 255, 255, 0.88);
  color: #8a5200;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  box-shadow: 0 6px 14px rgba(31, 22, 13, 0.12);
}

.perf-tree-card.is-root .perf-tree-action {
  background: rgba(255, 255, 255, 0.18);
  color: #fff;
}

.perf-tree-card.is-root {
  background: linear-gradient(135deg, #1f3b5c, #35587b);
  color: #fff;
}

.perf-tree-card.is-selected {
  outline: 2px solid #d9a441;
  box-shadow: 0 10px 24px rgba(217, 164, 65, 0.22);
}

.perf-tree-title {
  display: block;
  font-weight: 700;
}

.perf-tree-meta {
  display: block;
  font-size: 0.8rem;
  color: inherit;
  opacity: 0.82;
  margin-top: 0.2rem;
}

.perf-detail-body .table td,
.perf-detail-body .table th {
  vertical-align: middle;
}

.perf-workspace-pane,
.perf-detail-body {
  overflow: hidden;
}

@media (max-width: 1199.98px) {
  .perf-sticky-panel {
    position: static;
  }
}

@media (max-width: 991.98px) {
  .perf-hero,
  .perf-hierarchy-layout,
  .perf-chart-grid,
  .perf-detail-grid {
    grid-template-columns: 1fr;
    display: grid;
  }

  .perf-hero {
    align-items: flex-start;
  }
}

@media (max-width: 767.98px) {
  .perf-dev-footer,
  .perf-metric-row {
    grid-template-columns: 1fr;
  }

  .perf-link-label {
    justify-self: start;
  }
}
</style>

<script>
window.DeveloperPerformanceConfig = {
  detailUrl: '<?= base_url('Developer/developer_performance_detail') ?>',
  hierarchyUrl: '<?= base_url('Developer/developer_hierarchy_data') ?>',
  hierarchyMemberUrl: '<?= base_url('Developer/developer_hierarchy_member') ?>',
  hierarchyUpdateUrl: '<?= base_url('Developer/developer_hierarchy_update') ?>',
  dataUrl: '<?= base_url('Developer/developer_performance_data') ?>',
  year: <?= $selectedYearValue ?>,
  fyLabel: '<?= htmlspecialchars($fyLabel, ENT_QUOTES) ?>',
  initialOverview: <?= json_encode([
      'open_tickets' => (int) ($overview['open_tickets'] ?? 0),
      'in_progress_tickets' => (int) ($overview['in_progress_tickets'] ?? 0),
      'resolved_tickets' => (int) ($overview['resolved_tickets'] ?? 0),
      'closed_tickets' => (int) ($overview['closed_tickets'] ?? 0),
      'reviewer_assigned' => $reviewerAssigned,
      'accepted_total' => $acceptedTotal,
      'direct_reports' => $directReports,
      'total_reports' => $totalReports,
  ]) ?>
};
</script>

<?php $this->load->view('Layout/Footer'); ?>
