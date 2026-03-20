<?php $dashboardScopeValue = in_array((string) ($dashboard_scope ?? 'all'), ['mine', 'all', 'assigned'], true) ? (string) $dashboard_scope : 'all'; ?>
<div class="col-lg-3 col-6 dashboard-overview-card">
  <div class="small-box bg-primary dashboard-ticket-box dashboard-ticket-box--resolved">
    <div class="inner text-center">
      <h3>Resolved Ticket</h3>
      <p data-dashboard-count="resolved"><?= $resolved_count ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-person-add"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=3&dashboard_scope=' . urlencode($dashboardScopeValue)) ?>" class="small-box-footer js-dashboard-status-link" data-status="3" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
