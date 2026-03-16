<div class="col-lg-3 col-6">
  <div class="small-box bg-danger">
    <div class="inner text-center">
      <h3>Closed Ticket</h3>
      <p><?= $closed_count ?></p>
    </div>
    <div class="icon">
      <i class="ion ion-pie-graph"></i>
    </div>
    <a href="<?= base_url('Dashboard?dashboard_ticket_status=4') ?>" class="small-box-footer js-dashboard-status-link" data-status="4" data-target-section="activity">
      More info <i class="fas fa-arrow-circle-right"></i>
    </a>
  </div>
</div>
