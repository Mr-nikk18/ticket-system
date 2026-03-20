<?php $dashboardScopeValue = in_array((string) ($dashboard_scope ?? 'all'), ['mine', 'all', 'assigned'], true) ? (string) $dashboard_scope : 'all'; ?>
<div class="col-lg-3 col-6 dashboard-overview-card">
  <div class="small-box bg-success dashboard-ticket-box dashboard-ticket-box--open">
    <div class="inner text-center">
      <h3>Open Ticket</h3>
      <p data-dashboard-count="open"><?= $open_count ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-bag"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=1&dashboard_scope=' . urlencode($dashboardScopeValue)) ?>" class="small-box-footer js-dashboard-status-link" data-status="1" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
