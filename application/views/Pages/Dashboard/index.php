<?php
$this->load->view('Layout/Header');
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Dashboard</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right mr-3 align-items-center">
            <li class="breadcrumb-item">
              <a href="#">Home</a>
            </li>
            <li class="ml-2">
              <a href="<?= base_url('index.php/Auth/logout') ?>" class="btn btn-danger btn-sm px-2 py-1">
                Logout
              </a>
            </li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <?php foreach ($modules as $m) { ?>
          <?php
            $view_file = isset($m['view_file']) ? trim((string) $m['view_file']) : '';
            $view_name = preg_replace('/\.php$/i', '', $view_file);
            $view_path = APPPATH . 'views/' . $view_name . '.php';
          ?>
          <?php if ($view_name !== '' && is_file($view_path)) { ?>
            <?php $this->load->view($view_name); ?>
          <?php } else { ?>
            <?php log_message('error', 'Dashboard module view not found: ' . $view_file); ?>
          <?php } ?>
        <?php } ?>
      </div>
    </div>
  </section>
</div>

<?php
$this->load->view('Layout/Footer');
?>
