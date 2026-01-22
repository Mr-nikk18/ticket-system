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
      <div class="container-fluid" >
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
   <?php if ($this->session->userdata('role_id') == 1) { ?>

<form method="post" action="<?= base_url('TRS/update/'.$value['ticket_id']) ?>">

    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title"
               class="form-control"
               value="<?= $value['title'] ?>" required>
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description"
                  class="form-control"
                  rows="4"
                  required><?= $value['description'] ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Update Ticket</button>
</form>

<?php } ?>

<?php if ($this->session->userdata('role_id') == 2) { ?>

<form method="post" action="<?= base_url('TRS/update/'.$value['ticket_id']) ?>">

    <div class="form-group">
        <label>Title</label>
        <input type="text"
               class="form-control"
               value="<?= $value['title'] ?>"
               readonly>
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea class="form-control"
                  rows="4"
                  readonly><?= $value['description'] ?></textarea>
    </div>

    <div class="form-group">
        <label>Change Status</label>
        <select name="status" class="form-control" required>
            <option value="in_progress"
                <?= $value['status']=='in_progress' ? 'selected' : '' ?>>
                In Progress
            </option>
            <option value="resolved"
                <?= $value['status']=='resolved' ? 'selected' : '' ?>>
                Resolved
            </option>
        </select>
    </div>

    <button type="submit" class="btn btn-success">
        Update Status
    </button>

</form>

<?php } ?>
<?php if ($this->session->userdata('role_id') == 3) { ?>

<form method="post" action="<?= base_url('TRS/update/'.$value['ticket_id']) ?>">

    <div class="form-group">
        <label>Title</label>
        <input type="text"
               class="form-control"
               value="<?= $value['title'] ?>"
               readonly>
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea class="form-control"
                  rows="4"
                  readonly><?= $value['description'] ?></textarea>
    </div>

    <div class="form-group">
        <label>Assign To</label>
        <select name="assigned_engineer_id" class="form-control">
            <option value="">-- Select Developer --</option>

            <option value="<?= $this->session->userdata('user_id') ?>">
                Assign to Me
            </option>

            <?php foreach ($developers as $dev) { ?>
                <option value="<?= $dev['user_id'] ?>"
                    <?= ($value['assigned_engineer_id'] == $dev['user_id']) ? 'selected' : '' ?>>
                    <?= $dev['user_name'] ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control" required>
            <option value="in_progress"
                <?= $value['status']=='in_progress' ? 'selected' : '' ?>>
                In Progress
            </option>
            <option value="resolved"
                <?= $value['status']=='resolved' ? 'selected' : '' ?>>
                Resolved
            </option>
            <option value="closed"
                <?= $value['status']=='closed' ? 'selected' : '' ?>>
                Closed
            </option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">
        Update Ticket
    </button>

</form>

<?php } ?>



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