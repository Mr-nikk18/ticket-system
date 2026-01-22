<?php
$this->load->view('Layout/Header');

?>

<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
           <ol class="breadcrumb float-sm-right mr-3 align-items-center">
    <li class="breadcrumb-item">
        <a href="#">Home</a>
    </li>

    <li class="ml-2">
        <a href="<?= base_url('index.php/Auth/logout') ?>" 
           class="btn btn-danger btn-sm px-2 py-1">
           Logout
        </a>
    </li>
</ol>

          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
  <div class="ajax">
  <div class="container-fluid">

    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
          <div class="inner text-center">
            <h3><?= $open_count ?></h3>
<p>Open Ticket</p>

          </div>
          <div class="icon">
            <i class="ion ion-bag"></i>
          </div>
          <a href="<?= base_url('TRS/list/open') ?>" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
          <div class="inner text-center">
             <h3><?= $in_process_count ?></h3>
            <p>In Process<p>
          </div>
          <div class="icon">
            <i class="ion ion-stats-bars"></i>
          </div>
          <a href="<?= base_url('TRS/list/in_progress') ?>" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
          <div class="inner text-center">
            <h3>Resolved Ticket</h3>
             <p><?= $resolved_count ?><p>
          </div>
          <div class="icon">
            <i class="ion ion-person-add"></i>
          </div>
          <a href="<?= base_url('TRS/list/resolved') ?>" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
          <div class="inner text-center">
            <h3>Closed Ticket</h3>
             <p><?= $closed_count ?><p>
          </div>
          <div class="icon">
            <i class="ion ion-pie-graph"></i>
          </div>
          <a href="<?= base_url('TRS/list/closed') ?>" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
    </div>
    <!-- /.row -->

    <!-- Recent Tickets (FULL WIDTH) -->
    <div class="row mt-3">
      <div class="col-12">

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
              <i class="fas fa-ticket-alt mr-1"></i>
              Recent Tickets
            </h3>
            <br>

            <a href="<?= base_url('list') ?>" class="btn btn-sm btn-primary px-2 py-1" style="margin-right: -488px;" >
              View All
            </a>
          </div>

          <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped w-100">
              <thead>
                <tr>
                  <th>Ticket ID</th>
                  <th>Tittle</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Handle By:</th>
                  <?php if ($this->session->userdata('role_id') == 2) { ?>
<th>Action</th>
<?php } ?>

                </tr>
              </thead>
              <tbody>

        <?php if (!empty($recent_tickets)) { ?>
          <?php foreach ($recent_tickets as $ticket) { ?>
            <tr>
              <td><?= $ticket['ticket_id'] ?></td>
              <td><?= $ticket['title'] ?></td>
              <td><?= $ticket['description'] ?></td>
              <td>
                <?php if ($ticket['status'] == 'open') { ?>
                  <span class="badge badge-success">Open</span>
                <?php } elseif ($ticket['status'] == 'in_progress') { ?>
                  <span class="badge badge-warning">In Process</span>
                <?php } elseif ($ticket['status'] == 'resolved') { ?>
                  <span class="badge badge-info">Resolved</span>
                <?php } else { ?>
                  <span class="badge badge-secondary">Closed</span>
                <?php } ?>
              </td>
              <td><?= date('d-m-Y', strtotime($ticket['created_at'])) ?></td>
                <td><?= $ticket['assigned_engineer_name'] ?></td>
                
<?php if ($this->session->userdata('role_id') == 2) { ?>
    <td>
        <?php if ($ticket['assigned_engineer_id'] == NULL && $ticket['status'] == 'open') { ?>
            <a href="<?= base_url('TRS/accept_ticket/'.$ticket['ticket_id']) ?>"
               class="btn btn-sm btn-success">
               Accept
            </a>
        <?php } else{ ?>
            <span class="badge badge-secondary">Assigned</span>
        <?php } ?>
    </td>
<?php } ?>



            </tr>
          <?php } ?>
        <?php } else{ ?>
          <tr>
            <td colspan="6" class="text-center">No tickets found</td>
          </tr>
        <?php } ?>

      </tbody>
            </table>
          </div>

        </div>

      </div>
    </div>
  </div>
  </div>
</section>
<!-- /.content -->

    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->



  <?php
$this->load->view('Layout/Footer');
?>