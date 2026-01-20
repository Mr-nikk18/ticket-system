<?php
$this->load->view('Layout/Header');
?>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>DataTables</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">DataTables</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
            <div class="card-header d-flex align-items-center">
    <h3 class="card-title mb-0">
        <?= isset($current_status) && $current_status
            ? ucfirst(str_replace('_',' ',$current_status)).' Tickets'
            : 'All Tickets' ?>
    </h3>

    <?php if ($this->session->userdata('role_id') == 1) { ?>
        <a href="<?= base_url('TRS/see') ?>"
           class="btn btn-primary btn-sm ml-auto">
           <i class="fas fa-plus-circle"></i> Generate Ticket
        </a>
    <?php } ?>
</div>


              <!-- /.card-header -->
              <div class="card-body">
                <table id="example2" class="table table-bordered table-hover">
                  <thead>
                  <tr>
                    <th>No.</th>
                    <?php if (in_array($this->session->userdata('role_id'), [2,3])) { ?>
    <th>User Name</th>
    <th>Department</th>
<?php } ?>

                    <th>Ticket ID</th>
                    <th>Title</th>
                    <th>Description</th>
                      <th>Handled by</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>

                    
                  </tr>
                  </thead>
                  <tbody>
                    <?php $n=1 ?>
                    <?php foreach($val as $value){ ?>
                  <tr>
                    <td><?= $n ?></td>
                    <?php if (in_array($this->session->userdata('role_id'), [2,3])) { ?>
    <td><?= $value['user_full_name'] ?></td>
    <td><?= $value['user_department'] ?></td>
<?php } ?>

                    <td><?= $value['ticket_id'] ?></td>
                    <td><?= $value['title'] ?></td>
                    <td><?= $value['description'] ?></td>
 <td>
    <?= !empty($value['assigned_engineer_name'])
        ? $value['assigned_engineer_name']
        : 'Not Assigned' ?>
</td>
                 <td>
  <?php if ($value['status'] == 'open') { ?>
    <span class="fas fa-circle text-success">Open</span>

  <?php } elseif ($value['status'] == 'in_progress') { ?>
    <span class="fas fa-circle text-warning">In Process</span>

  <?php } elseif ($value['status'] == 'resolved') { ?>
    <span class="fas fa-circle text-warning">resolved</span>

  <?php } else { ?>
    <span class="fas fa-circle text-danger">Closed</span>
  <?php } ?>
</td>
                    <td><?= $value['created_at'] ?></td>


<!-- EDIT -->
<td>
<?php
$role_id = $this->session->userdata('role_id');
$user_id = $this->session->userdata('user_id');
?>

<!-- ================= USER ================= -->
<?php if ($role_id == 1) { ?>

    <?php if ($value['status'] == 'resolved') { ?>

        <!-- FIRST: only confirmation -->
        <span class="text-info d-block mb-1 mt-1">
            Is issue solved?
        </span>

        <a href="<?= base_url('TRS/confirm_ticket/'.$value['ticket_id'].'/yes') ?>"
           class="btn btn-sm btn-success mb-1">Yes</a>

        <a href="<?= base_url('TRS/confirm_ticket/'.$value['ticket_id'].'/no') ?>"
           class="btn btn-sm btn-warning mb-1">No</a>

    <?php } elseif ($value['status'] == 'open' || $value['status'] == 'in_progress') { ?>

        <!-- AFTER NO or normal open → allow edit/delete -->
        <a href="<?= base_url('TRS/edit/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-primary mb-1">Edit</a><br>

        <a href="<?= base_url('TRS/delete/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-danger mb-1"
           onclick="return confirm('Are you sure?');">
           Delete
        </a>

    <?php } else { ?>

        <!-- closed -->
        <span class="badge badge-secondary">No Action</span>

    <?php } ?>

<?php } ?>





<!-- ================= DEVELOPER ================= -->
<?php if ($role_id == 2) { ?>

    <?php if ($value['status'] == 'open' && empty($value['assigned_engineer_id'])) { ?>
        <a href="<?= base_url('TRS/accept_ticket/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-success mb-1">Accept</a>

    <?php } elseif (
        $value['status'] == 'in_progress' &&
        $value['assigned_engineer_id'] == $user_id
    ) { ?>
        <a href="<?= base_url('TRS/edit/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-primary mb-1">Edit</a><br>

        <a href="<?= base_url('TRS/leave_ticket/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-warning">Leave</a><br>

               <a href="<?= base_url('TRS/delete/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this ticket?');">
           Delete
        </a>

    <?php } else { ?>
        <span class="badge badge-secondary">Assigned</span>
    <?php } ?>

<?php } ?>


<!-- ================= IT HEAD ================= -->
<?php if ($role_id == 3) { ?>

    <?php if ($value['status'] == 'open') { ?>
        <a href="<?= base_url('TRS/accept_ticket/'.$value['ticket_id']) ?>"
           class="btn btn-sm btn-success mb-1">Accept</a><br>

        <a href="<?= base_url('TRS/edit/'.$value['ticket_id'].'?assign=1') ?>"
           class="btn btn-sm btn-info mb-1">Assign</a><br>
    <?php } ?>

    <a href="<?= base_url('TRS/edit/'.$value['ticket_id']) ?>"
       class="btn btn-sm btn-primary mb-1">Edit</a><br>

    <a href="<?= base_url('TRS/delete/'.$value['ticket_id']) ?>"
       class="btn btn-sm btn-danger"
       onclick="return confirm('Delete ticket?');">
       Delete
    </a>

<?php } ?>

</td>



                  </tr>
                  <?php $n++ ?>
                  <?php } ?>
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

<?php
$this->load->view('Layout/Footer');
?>