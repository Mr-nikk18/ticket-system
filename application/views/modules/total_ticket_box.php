<div class="col-lg-3 col-6">
  <div class="small-box bg-info">
    <div class="inner text-center">
      <h3>Total Tickets</h3>
      <p><?= isset($total_count) ? (int) $total_count : 0 ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-document-text"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=0') ?>" class="small-box-footer js-dashboard-status-link" data-status="0" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
