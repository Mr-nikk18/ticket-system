<?php $this->load->view('Layout/Header'); ?>

<div class="content-wrapper">
<section class="content">
<div class="container-fluid">
<div class="card card-primary">
<div class="card-header">
<h3 class="card-title">Add New User</h3>
</div>

<form method="post" action="<?= base_url('TRS/save_user') ?>">
<div class="card-body">

<div class="form-group">
<label>User Name</label>
<input type="text" name="user_name" class="form-control" required>
</div>

<div class="form-group">
<label>Full Name</label>
<input type="text" name="name" class="form-control" required>
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

<div class="card-footer">
<button type="submit" class="btn btn-primary">Create User</button>
</div>

</form>
</div>
</div>
</section>
</div>

<?php $this->load->view('Layout/Footer'); ?>
