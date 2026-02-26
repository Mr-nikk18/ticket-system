<?php
$username = $this->session->userdata('username');
$theme = $this->session->userdata('theme');

$sidebar_color = $theme['sidebar_color'] ?? '#343a40';
$navbar_color  = $theme['navbar_color'] ?? '#ffffff';
$card_color    = $theme['card_color'] ?? '#007bff';
$dark_mode     = $theme['dark_mode'] ?? 0;
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TRS</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/plugins/summernote/summernote-bs4.min.css">
  <script src="<?= base_url() ?>assets/plugins/jquery/jquery.min.js"></script>
<script src="<?= base_url() ?>assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<style>
  .role-section {
  display: none;
}

</style>

<style>
.timeline {
  list-style: none;
  padding-left: 0;
}
.timeline-item {
  position: relative;
  padding: 15px 20px;
  margin-bottom: 20px;
  border-left: 3px solid #007bff;
  background: #f9f9f9;
  border-radius: 4px;
}
.timeline-item::before {
  content: '';
  width: 12px;
  height: 12px;
  background: #007bff;
  border-radius: 50%;
  position: absolute;
  left: -7px;
  top: 20px;
}
.timeline-date {
  font-size: 12px;
  color: #6c757d;
  display: block;
  margin-bottom: 6px;
}
.timeline-content h5 {
  font-weight: 600;
}


</style>
<style>
.border-left-primary {
  border-left: 4px solid #007bff !important;
}
</style>
<style>
.approval-history {
  width: 100%;
}

/* ONE ROW */
.approval-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;
}

/* LEFT DATE (RED BOX) */
.approval-date {
  background: #59e83c;
  color: #fff;
  font-size: 12px;
  padding: 6px 8px;
  border-radius: 3px;
  min-width: 155px;
  text-align: center;
  margin-right: 12px;
  line-height: 1.3;
}

/* RIGHT GREY BOX */
.approval-box {
  background: #f7f7f7;
  border: 1px solid #dcdcdc;
  border-radius: 4px;
  padding: 8px 12px;
  width: 100%;
}

/* TOP LINE */
.approval-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.approval-user {
  font-weight: 600;
  color: #007bff;
  font-size: 14px;
}

/* SMALL STATUS TAG (RIGHT) */
.approval-status {
  background: #28a745;
  color: #fff;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 3px;
  text-transform: capitalize;
}

.ticket-card {
    transition: all 0.2s ease-in-out;
}

.dragging {
    transform: rotate(2deg);
    box-shadow: 0 12px 25px rgba(0,0,0,0.3);
    background: #ffffff;
}

.kanban-placeholder {
    height: 80px;
    background: #e3f2fd;
    border: 2px dashed #2196f3;
    border-radius: 10px;
    margin-bottom: 10px;
}


/* ACTION TEXT */
.approval-text {
  font-size: 14px;
  color: #333;
}

/* REMARKS */
.approval-remarks {
  margin-top: 4px;
  font-size: 13px;
  color: #555;
}

/* Timeline vertical line */
.with-line {
  position: relative;
}

/* Grey vertical line */
.with-line::before {
  content: '';
  position: absolute;
  left: 78px;               /* 👈 aligns with date badge center */
  top: 0;
  bottom: 0;
  width: 2px;
  background: #dee2e6;
}
.brand-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* LOGO ANIMATION */
.trs-logo {
  border-radius: 40%;
    height: 100px;
    width: 111px;
    animation: logoPulse 3s ease-in-out infinite;
}

/* TEXT ANIMATION */
.trs-text {
    font-weight: 700;
    font-size: 18px;
    background: linear-gradient(45deg,#0d6efd,#20c997);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: textPulse 3s ease-in-out infinite;
}

/* Logo glow pulse */
@keyframes logoPulse {
  0% {
        filter: drop-shadow(0 0 3px rgba(243, 242, 240, 1));
    }
    50% {
        filter: drop-shadow(0 0 15px rgba(15, 15, 14, 0.9));
    }
    100% {
        filter: drop-shadow(0 0 3px rgba(245, 221, 6, 0.8));
    }
}

/* Text glow pulse */
@keyframes textPulse {
    0% {
        text-shadow: 0 0 3px rgba(13,110,253,0.3);
        opacity: 0.8;
    }
    50% {
        text-shadow: 0 0 15px rgba(13,110,253,0.9);
        opacity: 1;
    }
    100% {
        text-shadow: 0 0 3px rgba(78, 248, 87, 0.97);
        opacity: 0.8;
    }
}

.user-panel img:hover {
    transform: scale(1.05);
    transition: 0.3s ease;
}
.user-panel {
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Each row */
.approval-row {
  position: relative;
}

/* Stop line after last item */
.approval-row:last-child::after {
  content: '';
  position: absolute;
  left: 78px;
  bottom: 0;
  width: 2px;
  height: 50%;
  background: #fff; /* hides remaining line */
}

.avatar-box input:checked + img {
    box-shadow: 0 0 0 3px #007bff;
}
.avatar-container {
    display: flex;
    justify-content: center;
    gap: 20px;
}

.avatar-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    border-radius: 12px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: 0.3s ease;
}

.avatar-box input {
    display: none;
}

.avatar-box img {
    height: 70px;
    width: 70px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-text {
    margin-top: 8px;
    font-size: 13px;
}

/* THIS IS THE IMPORTANT LINE */
.avatar-box input:checked + img {
    outline: 3px solid #007bff;
}

.avatar-box input:checked {
}

.custom-fullscreen {
    max-width: 95%;
}
.modal-backdrop.show {
    opacity: 0.6;
}


.modal-body {
    max-height: 80vh;
    overflow-y: auto;
    padding:25px;
}

.modal-lg {
    max-width: 850px;
}


.info-box {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #e3e6f0;
}

.task-completed {
    background: #e6f9ed;
    text-decoration: line-through;
}
#ticketDetailContent {
    padding: 20px;
}

.task-card {
    background: #ffffff;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid #e3e6f0;
    margin-bottom: 10px;
    transition: 0.2s ease;
}

.task-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.task-card {
    background: #ffffff;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    margin-bottom: 10px;
    transition: 0.2s ease;
}

.task-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.task-completed {
    background: #e6f9ed;
    text-decoration: line-through;
}
.modal-xl {
    max-width: 1100px;
}


.task-container {
    margin-top: 10px;
}

.task-card {
    background: #ffffff;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #e3e6f0;
    margin-bottom: 12px;
    transition: 0.2s ease;
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.task-completed {
    background: #e6f9ed;
    text-decoration: line-through;
}

.modal-dialog {
    margin-top: 80px;
}
.ticket-hero {
    padding: 18px 25px;
    border-radius: 8px;
}

.modal-content {
    border-radius: 10px;
}

.modal-header {
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.avatar-wrapper {
    text-align: center;
}

.avatar-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
}

.avatar-box {
    display: none; /* hide all by default */
}

.avatar-box img {
    height: 70px;
    width: 70px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-box input {
    display: none;
}

.avatar-box input:checked + img {
    box-shadow: 0 0 0 3px #007bff;
}

.avatar-text {
    font-size: 12px;
    margin-top: 5px;
}

.avatar-nav {
    margin-top: 15px;
}
.task-card {
    cursor: grab;
    transition: 0.2s ease;
}

.task-card:active {
    cursor: grabbing;
}

.task-card:hover {
    background: #f8f9fa;
}

.task-placeholder {
    height: 60px;
    border-radius: 12px;
    border: 2px dashed #007bff;
    background: rgba(0,123,255,0.05);
    margin-bottom: 10px;
}

.ui-sortable-helper {
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.ui-sortable-helper {
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.avatar-nav button {
    border: none;
    background: #007bff;
    color: white;
    padding: 6px 12px;
    margin: 0 5px;
    border-radius: 6px;
    cursor: pointer;
}

.kanban-column {
    min-height: 500px;
    background: #f4f5f7;
    padding: 10px;
    border-radius: 10px;
}
.kanban-header {
    background: linear-gradient(135deg, #343a40, #495057);
    color: white;
    padding: 10px;
    border-radius: 10px 10px 0 0;
    font-weight: 600;
}
.priority-strip {
    height: 4px;
    border-radius: 5px 5px 0 0;
}

.high { background: #dc3545; }
.medium { background: #ffc107; }
.low { background: #28a745; }

.ticket-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 12px;
    cursor: grab;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.ticket-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

.ui-state-highlight {
    height: 70px;
    background: #dfe3e8;
    border: 2px dashed #6c757d;
    border-radius: 8px;
    margin-bottom: 10px;
}

.edit-task {
    border: 1px solid #dee2e6;
    padding: 2px 8px;
}

.edit-task:hover {
    background-color: #f8f9fa;
}

.main-header {
    position: relative;
}

.flash-msg {
    position: absolute;
    top: 1px;              /* navbar ke andar vertically adjust */
    left: 52%;
    transform: translateX(-50%);
    width: auto;           /* fixed width hatao */
    min-width: 250px;
    max-width: 500px;
    padding: 13px 9px;     /* padding kam karo */
    font-size: 18px;       /* text thoda small */
    border-radius: 13px;   /* pill shape */
    z-index: 9999;
}
.chat-box {
    max-height: 200px;
    overflow-y: auto;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
}

.chat-message {
    margin-bottom: 8px;
    padding: 6px 10px;
    border-radius: 15px;
    max-width: 70%;
    font-size: 13px;
}

.chat-left {
    background: #e9ecef;
    text-align: left;
}

.chat-right {
    background: #0d6efd;
    color: white;
    margin-left: auto;
    text-align: right;
}

.flot-x-axis div {
    transform: rotate(-30deg);
    transform-origin: top right;
}

.main-sidebar {
    background-color: <?= $sidebar_color ?> !important;
}

.navbar {
    background-color: <?= $navbar_color ?> !important;
}

.card-primary {
    border-top: 3px solid <?= $card_color ?> !important;
}

.nav-sidebar .nav-link.active {
    background-color: <?= $card_color ?> !important;
}
</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= ($dark_mode ? 'dark-mode' : '') ?>">

  
<div class="wrapper" >
 <!--   <body class="hold-transition login-page"> -->



  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="<?= base_url() ?>assets/dist/img/TRS.png" alt="TRS" height="200" width="200">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light ">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?= base_url('Dashboard') ?>" class="nav-link">Home</a>
      </li>
      
    </ul>
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
      
    

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-comments"></i>
          <span class="badge badge-danger navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="<?= base_url() ?>assets/dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Brad Diesel
                  <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">Call me whenever you can...</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="<?= base_url() ?>assets/dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  John Pierce
                  <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">I got your message bro</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="<?= base_url() ?>assets/dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Nora Silvester
                  <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">The subject goes here</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
        </div>
      </li>
  
<!-- Notifications Dropdown Menu -->
<li class="nav-item dropdown">
  <a class="nav-link" data-toggle="dropdown" href="#">
    <i class="far fa-bell"></i>
    <span id="notificationBadge"
          class="badge badge-warning navbar-badge"
          style="display:none;">
    </span>
  </a>

  <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
    <span class="dropdown-item dropdown-header"
          id="notificationHeader">
        0 Notifications
    </span>

    <div class="dropdown-divider"></div>

    <div id="notificationList">
        <span class="dropdown-item text-muted text-center">
            No new notifications
        </span>
    </div>

    <div class="dropdown-divider"></div>
    <a href="<?= base_url('TRS/all_notifications') ?>"
       class="dropdown-item dropdown-footer">
       See All Notifications
    </a>
  </div>
</li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>
    </ul>

  
  </nav>
  <!-- /.navbar -->
   
    

<!-- Main Sidebar Container -->
<aside class="main-sidebar elevation-4">

    <!-- Brand Logo -->
    <div class="brand-link d-flex align-items-center justify-content-center"
         style="padding:20px 10px;">

        <img src="<?= base_url('assets/dist/img/TRS.png') ?>" class="trs-logo">

        <span style="
            font-weight:700;
            font-size:17px;
            margin-left:10px;
            
            background:linear-gradient(45deg,#0d6efd,#20c997);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        " class="trs-text">
            TRS Portal
        </span>
    </div>
<hr>
    <!-- Sidebar -->
    <div class="sidebar">

        <!-- Compact User Panel -->
        <div class="user-panel d-flex align-items-center"
             style="padding:15px 2px; border-bottom:1px solid rgba(0,0,0,0.05);">

            <img src="<?= base_url('assets/dist/img/'.($this->session->userdata('avatar') ?? 'default.png')) ?>"
                 style="
                    height:48px;
                    width:48px;
                    border-radius:50%;
                    object-fit:cover;
                    border:2px solid #fff;
                    box-shadow:0 4px 10px rgba(0,0,0,0.2);
                    cursor:pointer;
                 "
                 data-toggle="modal"
                 data-target="#avatarModal">

            <div style="margin-left:12px;">
                <div style="font-weight:900; font-size:20px;">
                   <?= ucfirst($username) ?> 
                </div>
                <div style="font-weight:400;font-size:12px; color:#6c757d;">
    <?= ucfirst($this->session->userdata('role_name')); ?>
</div>

            </div>

        </div>



      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>


<!-- Sidebar Menu -->
<!-- Sidebar Menu -->
<nav class="mt-2">
<ul class="nav nav-pills nav-sidebar flex-column"
    data-widget="treeview"
    role="menu"
    data-accordion="false">

<?php
$parents = [];
$children = [];

foreach($menus as $m){
    if($m['parent_id'] == 0){
        $parents[] = $m;
    }else{
        $children[$m['parent_id']][] = $m;
    }
}
?>

<?php foreach($parents as $p){ ?>

<?php if(isset($children[$p['id']])){ ?>

<li class="nav-item has-treeview">
  <a href="#" class="nav-link">
    <i class="nav-icon <?= $p['icon'] ?>"></i>
    <p>
      <?= $p['menu_name'] ?>
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>

  <ul class="nav nav-treeview">
    <?php foreach($children[$p['id']] as $c){ ?>
      <li class="nav-item">
        <a href="<?= base_url($c['url']) ?>" class="nav-link">
          <i class="<?= $c['icon'] ?> nav-icon"></i>
          <p><?= $c['menu_name'] ?></p>
        </a>
      </li>
    <?php } ?>
  </ul>

</li>

<?php } else { ?>

<li class="nav-item">
  <a href="<?= base_url($p['url']) ?>" class="nav-link">
    <i class="nav-icon <?= $p['icon'] ?>"></i>
    <p><?= $p['menu_name'] ?></p>
  </a>
</li>

<?php } ?>
<?php } ?>

</ul>
</nav>


<!-- LOGOUT -->
<li class="nav-item mt-3">
  <a href="<?= base_url('Auth/logout') ?>" class="nav-link d-flex align-items-center text-danger">
    <i class="nav-icon fas fa-sign-out-alt"></i>
    <p class="ml-2 mb-0">Logout</p>
  </a>
</li>




    </div>
    <!-- /.sidebar -->
  </aside>


  <div class="modal fade" id="avatarModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Change Profile Icon</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body text-center">

       <form method="post"
      action="<?= base_url('UserController/upload_avatar') ?>"
      enctype="multipart/form-data">

  <div class="modal-body text-center">

<div class="avatar-wrapper">

    <div class="avatar-container" id="avatarContainer">

        <!-- Avatars -->
        <label class="avatar-box"><input type="radio" name="selected_avatar" value="avatar1.png"><img src="<?= base_url('assets/dist/img/avatar1.png') ?>"><span class="avatar-text">Default</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="avatar2.png"><img src="<?= base_url('assets/dist/img/avatar2.png') ?>"><span class="avatar-text">Option 1</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="avatar3.png"><img src="<?= base_url('assets/dist/img/avatar3.png') ?>"><span class="avatar-text">Option 2</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="avatar4.png"><img src="<?= base_url('assets/dist/img/avatar4.png') ?>"><span class="avatar-text">Option 3</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="avatar5.png"><img src="<?= base_url('assets/dist/img/avatar5.png') ?>"><span class="avatar-text">Option 4</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user1-128x128.jpg"><img src="<?= base_url('assets/dist/img/user1-128x128.jpg') ?>"><span class="avatar-text">Option 5</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user2-128x128.jpg"><img src="<?= base_url('assets/dist/img/user2-160x160.jpg') ?>"><span class="avatar-text">Option 6</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user3-128x128.jpg"><img src="<?= base_url('assets/dist/img/user3-128x128.jpg') ?>"><span class="avatar-text">Option 7</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user4-128x128.jpg"><img src="<?= base_url('assets/dist/img/user4-128x128.jpg') ?>"><span class="avatar-text">Option 8</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user5-128x128.jpg"><img src="<?= base_url('assets/dist/img/user5-128x128.jpg') ?>"><span class="avatar-text">Option 9</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user6-128x128.jpg"><img src="<?= base_url('assets/dist/img/user6-128x128.jpg') ?>"><span class="avatar-text">Option 10</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user7-128x128.jpg"><img src="<?= base_url('assets/dist/img/user7-128x128.jpg') ?>"><span class="avatar-text">Option 11</span></label>

        <label class="avatar-box"><input type="radio" name="selected_avatar" value="user8-128x128.jpg"><img src="<?= base_url('assets/dist/img/user8-128x128.jpg') ?>"><span class="avatar-text">Option 12</span></label>

    </div>

    <!-- Navigation Arrows -->
    <div class="avatar-nav">
        <button type="button" onclick="prevPage()">&#8592;</button>
        <button type="button" onclick="nextPage()">&#8594;</button>
    </div>

</div>



    <hr>

    <div class="text-left mt-3">
      <label><b>Or Upload New Image</b></label>
      <input type="file" name="avatar_file" class="form-control">
    </div>

  </div>

  <div class="modal-footer">
    <button type="submit" class="btn btn-primary">Change</button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
  </div>

</form>


      </div>


    </div>
  </div>
</div>

