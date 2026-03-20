<?php $dashboardScopeValue = in_array((string) ($dashboard_scope ?? 'all'), ['mine', 'all', 'assigned'], true) ? (string) $dashboard_scope : 'all'; ?>
<div class="col-lg-3 col-6 dashboard-overview-card">
  <div class="small-box bg-danger dashboard-ticket-box dashboard-ticket-box--closed">
    <div class="inner text-center">
      <h3>Closed Ticket</h3>
      <p data-dashboard-count="closed"><?= $closed_count ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-pie-graph"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=4&dashboard_scope=' . urlencode($dashboardScopeValue)) ?>" class="small-box-footer js-dashboard-status-link" data-status="4" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
