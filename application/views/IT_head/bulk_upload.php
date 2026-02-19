<?php 
$this->load->view('Layout/Header');
?>

<div class="content-wrapper">

    <section class="content-header">
        <div class="container-fluid">
            <h4><i class="fas fa-file-upload mr-2"></i> Bulk User Upload</h4>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-12">

                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Upload CSV File</h3>
                        </div>

                        <div class="card-body">

                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success">
                                    <?= $this->session->flashdata('success'); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger">
                                    <?= $this->session->flashdata('error'); ?>
                                </div>
                            <?php endif; ?>

                            <form action="<?= base_url('Admin/upload_csv') ?>" 
                                  method="post" 
                                  enctype="multipart/form-data">

                                <div class="form-group">
                                    <label>Select CSV File</label>
                                    <input type="file" 
                                           name="csv_file" 
                                           class="form-control" 
                                           accept=".csv" 
                                           required>

                                    <small class="text-muted">
                                        Format: name,email,department,role
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-success mt-3">
                                    <i class="fas fa-upload mr-1"></i> Upload Users
                                </button>

                            </form>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

</div>




<?php 
$this->load->view('Layout/Footer');
?>