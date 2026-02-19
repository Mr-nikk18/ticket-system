<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset password</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo base_url() ?>assets/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="<?php echo base_url() ?>assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo base_url() ?>assets/dist/css/adminlte.min.css">
</head>



  <body class="hold-transition login-page">
     
<div class="login-box">
  <div class="login-logo">
    <a href="assets/index2.html"><b>TRS</b>Portal</a>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
      

<form action="<?= base_url('check') ?>" method="post">
      
        <input type="email" id="email" name="email" class="form-control">
<div id="email-msg" style="display:block;margin-top:5px;"></div>

<button type="button" 
        class="btn btn-secondary"
        onclick="history.back()">
    ← Back
</button>

<button type="submit" id="submit-btn" style="float:right;" class="btn btn-primary" disabled>
    Send Reset Link
</button>



      </form>
      
<script>


  document.querySelector('#email').addEventListener('blur', function() {

    let email = this.value;

    fetch("Auth/check_email_status_ajax", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "email=" + encodeURIComponent(email)
    })
    .then(res => res.json())
    .then(response => {

        let msg = document.getElementById("email-msg");
        let btn = document.getElementById("submit-btn");

     if(response.status === "NotFound"){

 //   msg.innerText = "User not registered. You can continue.";
    msg.innerText = "Account verified ✔";
    msg.style.color = "green";

    btn.disabled = false;   // ✅ Enable

}
         else if(response.status !== "Active"){
            msg.innerText = "Account not activated yet";
            msg.style.color = "orange";
            btn.disabled = true;
        }
        else{
            msg.innerText = "Account verified ✔";
            msg.style.color = "green";
            btn.disabled = false;
        }
    });

});

</script>
   </body>
</html>

