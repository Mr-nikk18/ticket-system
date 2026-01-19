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
              <div class="card-header">
                <h3 class="card-title">DataTable with minimal features & hover style</h3>
                <span><a href="<?= base_url('TRS/see') ?>" class="btn btn-primary btn-sm float-right"><b>Generate Ticket</b></a></span>
                
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example2" class="table table-bordered table-hover">
                  <thead>
                  <tr>
                    <th>No.</th>
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
                    <td><?= $value['ticket_id'] ?></td>
                    <td><?= $value['title'] ?></td>
                    <td><?= $value['description'] ?></td>
                    <td><?= $value['assigned_engineer_id'] ?></td>
                 <td>
  <?php if ($value['status'] == 'Open') { ?>
    <span class="fas fa-circle text-success">Open</span>

  <?php } elseif ($value['status'] == 'in_process') { ?>
    <span class="fas fa-circle text-warning">In Process</span>

  <?php } else { ?>
    <span class="fas fa-circle text-danger">Closed</span>
  <?php } ?>
</td>


                    <td><?= $value['created_at'] ?></td>
                    <td>
  <a href="<?= base_url('edit/'.$value['ticket_id']) ?>">
    <b>Edit</b>
  </a>
  <br><br>
  <a href="<?= base_url('delete/'.$value['ticket_id']) ?>"
     onclick="return confirm('Are you sure you want to delete this ticket?');">
    <b>Delete</b>
  </a>
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