<div class="col-lg-3 col-6">
  <div class="small-box bg-warning">
    <div class="inner text-center">
      <h3>In Process</h3>
      <p><?= $in_process_count ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-stats-bars"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=2') ?>" class="small-box-footer js-dashboard-status-link" data-status="2" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
