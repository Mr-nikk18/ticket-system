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
  <div class="container-fluid">
<div class="row">
    <?php foreach($modules as $m){ ?>
        <?php $this->load->view($m['view_file']); ?>  
    <?php } ?>
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