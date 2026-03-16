<div class="col-lg-3 col-6">
  <div class="small-box bg-success">
    <div class="inner text-center">
      <h3>Open Ticket</h3>
      <p><?= $open_count ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-bag"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=1') ?>" class="small-box-footer js-dashboard-status-link" data-status="1" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
