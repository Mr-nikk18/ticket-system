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

              <!-- form start -->
              <form id="quickForm" method="post" action="<?= base_url('TRS/add') ?>"  >
        <div class="card-body bg-dark">

  <!-- Title -->
  <div class="form-group ">
    <label>Title</label>
    <input type="text" name="title" class="form-control" placeholder="Enter Title" required>
  </div>

  <!-- Description -->
  <div class="form-group">
    <label>Description</label>
    <textarea name="description" class="form-control" placeholder="Enter Description" required></textarea>
  </div>

 <!-- Tasks Section -->
<div class="form-group">
    <label>Tasks</label>

    <div id="taskWrapper">
        <div class="input-group mb-2">
            <input type="text" name="tasks[]" class="form-control" placeholder="Enter Task">
            <div class="input-group-append">
                <button type="button" class="btn btn-danger removeTask">X</button>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-sm btn-primary" id="addTaskField">
        + Add More Task
    </button>
</div>

  <!-- Terms -->
  <div class="form-group">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" name="terms" class="custom-control-input" id="terms" required>
      <label class="custom-control-label" for="terms">
        I agree to the <a href="#">terms of service</a>
      </label>
    </div>
  </div>
 <button type="submit" class="btn btn-success">
        Add Ticket
    </button>
</div>

 </div>
      <!-- /.container-fluid -->
    </section>

    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

</form>
<?php 
$this->load->view('Layout/Footer');
?>