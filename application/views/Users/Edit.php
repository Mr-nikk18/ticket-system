<?php 
$this->load->view('layout/Header');
?>


<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Validation</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Validation</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- left column -->
          <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
             <form method="post" action="<?= base_url('TRS/update/'.$value['ticket_id']); ?>">

<input type="hidden" name="ticket_id" value="<?= $value['ticket_id']; ?>">

<div class="form-group">
  <label>Title</label>
  <input type="text" name="title" class="form-control"
         value="<?= $value['title']; ?>">
</div>

<div class="form-group">
  <label>Description</label>
  <textarea name="description" class="form-control" required><?= $value['description']; ?></textarea>
</div>


<button type="submit" class="btn btn-primary">Update</button>
</form>
      </div>
            <!-- /.card -->
            </div>
          <!--/.col (left) -->
          <!-- right column -->
          <div class="col-md-6">

          </div>
          <!--/.col (right) -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <?php 
$this->load->view('layout/Footer');
?>