<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/dist/css/adminlte.min.css">
</head>

<body class="hold-transition register-page">

<?php if ($this->session->flashdata('failed')): ?>
    <div class="alert alert-danger alert-dismissible fade show text-center flash-msg" role="alert">
        <?= $this->session->flashdata('failed'); ?>
    </div>
<?php endif; ?>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show text-center flash-msg" role="alert">
        <?= $this->session->flashdata('success'); ?>
    </div>
<?php endif; ?>

<div class="register-box">
  <div class="register-logo">
    <b>Registration</b>
  </div>

  <div class="card">
    <div class="card-body register-card-body">
      <p class="login-box-msg">Register a new membership</p>

      <form action="<?= base_url('Auth/setdataregistration') ?>" method="post">

        <!-- Full Name -->
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="name" placeholder="Full name" required> 
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-user"></span></div>
          </div>
        </div>

        <!-- Username -->
        <div class="input-group mb-1">
          <input type="text" class="form-control" id="user_name" name="user_name" placeholder="User name" required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-user"></span></div>
          </div>
        </div>
        <div class="text-danger small mb-2" id="username_error"></div>

        <!-- Email -->
        <div class="input-group mb-1">
          <input type="email" class="form-control" id="email" name="email" placeholder="Email"  required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
          </div>
        </div>
        <div class="text-danger small mb-2" id="email_error"></div>

        <!-- Company -->
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="company_name" placeholder="Company name">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
          </div>
        </div>

        <!-- Department -->
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="department" placeholder="Department">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
          </div>
        </div>

        <!-- Password -->
        <div class="input-group mb-3">
          <input type="password" class="form-control" id="password" name="password" placeholder="Password"  minlength="3"
    required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="input-group mb-1">
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password"  minlength="3"
    required >
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
          </div>
        </div>
        <div class="text-danger small mb-3" id="password_error"></div>

        <!-- Terms + Button -->
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="agreeTerms">
              <label for="agreeTerms">I agree to the <a href="#">terms</a></label>
            </div>
          </div>
          <div class="col-4">
            <button type="submit" id="registerBtn" class="btn btn-primary btn-block" disabled>
              Register
            </button>
          </div>
        </div>

      </form>

      <a href="<?= base_url('verify') ?>" class="text-center d-block mt-3">
        I already have a membership
      </a>
    </div>
  </div>
</div>

<script src="<?= base_url() ?>assets/plugins/jquery/jquery.min.js"></script>
<script src="<?= base_url() ?>assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url() ?>assets/dist/js/adminlte.min.js"></script>

<script>
let usernameOk = false;
let emailOk = false;
let passwordOk = false;

function toggleSubmit() {
    $('#registerBtn').prop('disabled', !(usernameOk && emailOk && passwordOk));
}

// Password match
$('#password, #confirm_password').on('keyup blur', function () {
    let pass = $('#password').val();
    let cpass = $('#confirm_password').val();

    if (cpass === '') {
        $('#password_error').text('');
        passwordOk = false;
        toggleSubmit();
        return;
    }

    if (pass !== cpass) {
        $('#password_error').text('Password not matched');
        passwordOk = false;
    } else {
        $('#password_error').text('');
        passwordOk = true;
    }
    toggleSubmit();
});

// Username / Email AJAX
$('#user_name, #email').on('input', function () {
    let user_name = $('#user_name').val();
    let email = $('#email').val();

    $.ajax({
        url: "<?= base_url('Auth/checkAvailability') ?>",
        type: "POST",
        dataType: "json",
        data: { user_name, email },
        success: function (res) {

            if (res.user_name === 'taken') {
                $('#username_error').text('Username already taken');
                usernameOk = false;
            } else {
                $('#username_error').text('');
                usernameOk = true;
            }

            if (res.email === 'taken') {
                $('#email_error').text('Email already registered');
                emailOk = false;
            } else {
                $('#email_error').text('');
                emailOk = true;
            }

            toggleSubmit();
        }
    });
});
</script>

<script>
    setTimeout(function () {
        document.querySelectorAll('.flash-msg').forEach(function (el) {
            el.classList.remove('show');
            el.classList.add('hide');
        });
    }, 3000); // 3 seconds
</script>


</body>
</html>
