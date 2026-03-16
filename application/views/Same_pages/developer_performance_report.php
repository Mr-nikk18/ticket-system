<?php
$this->load->view('Layout/Header');
$year = isset($year) ? (int) $year : (int) date('Y');
$from_date = isset($from_date) ? $from_date : date('Y-01-01');
$to_date = isset($to_date) ? $to_date : date('Y-m-d');
$overview = isset($overview) ? $overview : [];
$developers = isset($developers) ? $developers : [];
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-end">
        <div>
          <h1>Annual Performance Report</h1>
          <p>Report for year <?= $year ?> (<?= htmlspecialchars($from_date) ?> to <?= htmlspecialchars($to_date) ?>)</p>
        </div>
        <button class="btn btn-primary" onclick="window.print();">Print / Save as PDF</button>
      </div>
      <hr>
    </div>
  </section>

  <section class="content"><div class="container-fluid">
    <div class="row">
      <div class="col-md-3"><div class="card p-3"><strong>Hierarchy Members</strong><div><?= (int) ($overview['total_reports'] ?? 0) ?></div></div></div>
      <div class="col-md-3"><div class="card p-3"><strong>Assigned By You</strong><div><?= (int) ($overview['reviewer_assigned'] ?? 0) ?></div></div></div>
      <div class="col-md-3"><div class="card p-3"><strong>Accepted</strong><div><?= (int) ($overview['accepted_total'] ?? 0) ?></div></div></div>
      <div class="col-md-3"><div class="card p-3"><strong>Open Tickets</strong><div><?= (int) ($overview['open_tickets'] ?? 0) ?></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-header"><h3 class="card-title">Developer Score Table</h3></div><div class="card-body p-0"><table class="table table-sm"><thead><tr><th>Name</th><th>Dept</th><th>Total</th><th>Resolved</th><th>Pending</th><th>Avg Hours</th><th>Invested</th><th>Incoming Del</th><th>Outgoing Del</th></tr></thead><tbody><?php if (!empty($developers)) { foreach ($developers as $dev) { ?><tr><td><?= htmlspecialchars($dev['name']) ?></td><td><?= htmlspecialchars($dev['department_name']) ?></td><td><?= (int) ($dev['total_tickets'] ?? 0) ?></td><td><?= (int) ($dev['resolved_tickets'] ?? 0) ?></td><td><?= (int) ($dev['pending_tickets'] ?? 0) ?></td><td><?= number_format((float) ($dev['avg_resolution_hours'] ?? 0),1) ?></td><td><?= number_format((float) ($dev['invest_hours'] ?? 0),1) ?></td><td><?= (int) ($dev['incoming_delegations'] ?? 0) ?></td><td><?= (int) ($dev['outgoing_delegations'] ?? 0) ?></td></tr><?php } } else { ?><tr><td colspan="9" class="text-center">No data</td></tr><?php } ?></tbody></table></div></div>
  </div></section>
</div>
<?php $this->load->view('Layout/Footer'); ?>
