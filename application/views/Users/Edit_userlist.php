<?php $this->load->view('Layout/Header'); ?>

<div class="content-wrapper">
<section class="content">
<div class="container-fluid">
<div class="card card-primary">
<div class="card-header">
<h3 class="card-title">Edit User</h3>
</div>

<form method="post" action="<?= base_url('TRS/update_userlist/'.$users['user_id']) ?>">
  <!-- <input type="hidden" name="id" value="<?= $users['user_id'] ?>"> -->

<div class="card-body">

<div class="form-group">
<label>Full Name</label>
<input type="text" name="name" class="form-control"  value="<?= $users['name'] ?>">
</div>

<div class="form-group">
<label>User Name</label>
<input type="text" name="user_name" class="form-control"  value="<?= $users['user_name'] ?>">
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" class="form-control"  value="<?= $users['email'] ?>">
</div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone" class="form-control" value="<?= $users['phone'] ?>">
</div>

<div class="form-group">
<label>Company Name</label>
<input type="text" name="company_name" class="form-control" value="<?= $users['company_name'] ?>">
</div>

<div class="form-group">
<label>Department</label>
<input type="text" name="department" class="form-control" value="<?= $users['department'] ?>">
</div>

<div class="form-group">
<label>Role</label>
<select name="role_id" class="form-control" required value="<?= $users['role_id'] ?>">
    <option value="2">Developer</option>
    <option value="3">IT Head</option>
</select>
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password" class="form-control" placeholder="Leave blank to keep old password"
 required value="<?= $users['password'] ?>">
</div>

</div>

<div class="card-footer">
<button type="submit" class="btn btn-primary">Update User</button>
</div>

</form>
</div>
</div>
</section>
</div>

<?php $this->load->view('Layout/Footer'); ?>
