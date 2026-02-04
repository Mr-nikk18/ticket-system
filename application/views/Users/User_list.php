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
            <h1>Developer List</h1>
           
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
                 <a class=" btn btn-dark float-sm-right" href="javascript:void(0)" data-toggle="modal" data-target="#modal-success">Add Developer/Admin</a>
                <h3 class="card-title"></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->

<table class="table table-bordered">
<tr>
<th>No.</th>
<th>Name</th>
<th>Username</th>
<th>Role</th>
<th>Status</th>
<th>Action</th>
</tr>
  <?php $n=1; ?>
<?php foreach ($users as $u) { ?>

<tr>

<td><?= $n ?></td>
<td><?= $u['name'] ?></td>
<td><?= $u['user_name'] ?></td>
<td><?= $u['role_id'] == 2 ? 'Developer' : 'IT Head' ?></td>
<td><?= $u['status'] ?></td>
<td>
   <button onclick="editUser(<?= $u['user_id'] ?>)"
 class="btn btn-sm btn-primary mb-1">
 Edit
</button>
 <br>
     <a href="<?= base_url('TRS/delete_userlist/'.$u['user_id']) ?>" class="btn btn-sm btn-danger mb-1"  onclick="return confirm('Are you sure you want to delete?');">Delete</a>
</td>

</tr>
<?php $n++; ?>
<?php } ?>

</table>






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



      <div class="modal fade" id="modal-success">
        <div class="modal-dialog">
          <div class="modal-content bg-success">
            <div class="modal-header">
              <h4 class="modal-title">Add Developer/Admin</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
        <div class="modal-body">
<form method="post" id="adduserlist">

<div class="card-body">

<div class="form-group">
<label>Full Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="form-group">
<label>User Name</label>
<input type="text" name="user_name" class="form-control" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone" class="form-control">
</div>

<div class="form-group">
<label>Company Name</label>
<input type="text" name="company_name" class="form-control">
</div>

<div class="form-group">
<label>Department</label>
<input type="text" name="department" class="form-control">
</div>

<div class="form-group">
<label>Role</label>
<select name="role_id" class="form-control" required>
    <option value="2">Developer</option>
    <option value="3">IT Head</option>
</select>
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

</div>

<div class="modal-footer justify-content-between">
  <button type="button" class="btn btn-outline-light" data-dismiss="modal">Close</button>
  <button type="submit" class="btn btn-outline-light">Create user</button>
</div>

</form>
</div>
</div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- /.modal -->

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal">
  <div class="modal-dialog">
    <div class="modal-content bg-info">

      <div class="modal-header">
        <h4 class="modal-title">Edit User</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <form id="editUserForm">

          <!-- hidden user id -->
          <input type="hidden" name="user_id" id="edit_user_id">

          <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="edit_name" name="name" class="form-control" required>
          </div>

          <div class="form-group">
            <label>User Name</label>
            <input type="text" id="edit_user_name" name="user_name" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" id="edit_email" name="email" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Phone</label>
            <input type="text" id="edit_phone" name="phone" class="form-control">
          </div>

          <div class="form-group">
            <label>Company Name</label>
            <input type="text" id="edit_company" name="company_name" class="form-control">
          </div>

          <div class="form-group">
            <label>Department</label>
            <input type="text" id="edit_department" name="department" class="form-control">
          </div>

          <div class="form-group">
            <label>Role</label>
            <select id="edit_role_id" name="role_id" class="form-control">
              <option value="2">Developer</option>
              <option value="3">IT Head</option>
            </select>
          </div>

          <div class="form-group">
            <label>New Password (optional)</label>
            <input type="password" name="password" class="form-control">
          </div>

          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-outline-light" data-dismiss="modal">
              Close
            </button>
            <button type="submit" class="btn btn-outline-light">
              Update User
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>
</div>
<!-- /.EDIT MODAL -->




  <?php 
$this->load->view('layout/Footer');
?>