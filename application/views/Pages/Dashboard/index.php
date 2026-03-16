<?php
$this->load->view('Layout/Header');
?>

<div class="content-wrapper" id="mainContent">
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
      <?php
        $overview_modules = [];
        $activity_modules = [];
        $other_modules = [];

        foreach ($modules as $m) {
          $view_file = isset($m['view_file']) ? trim((string) $m['view_file']) : '';
          $view_name = preg_replace('/\.php$/i', '', $view_file);

          if (in_array($view_name, [
            'modules/total_ticket_box',
            'modules/open_ticket_box',
            'modules/inprocess_ticket_box',
            'modules/resolved_ticket_box',
            'modules/closed_ticket_box'
          ], true)) {
            $overview_modules[] = $view_name;
          } elseif ($view_name === 'modules/recent_ticket_table') {
            $activity_modules[] = $view_name;
          } elseif ($view_name !== '') {
            $other_modules[] = $view_name;
          }
        }

        $section_shell = [
          'id' => 'dashboard-workspace',
          'default' => 'overview',
          'eyebrow' => 'Trading-Style Workspace',
          'title' => 'Navigate dashboard sections from one surface',
          'description' => 'Switch between snapshot cards and live activity without leaving the dashboard route.',
          'badge' => count($modules) . ' live modules',
          'sections' => [
            ['id' => 'overview', 'label' => 'Overview', 'hint' => 'Ticket counters and quick state'],
            ['id' => 'activity', 'label' => 'Activity', 'hint' => 'Recent ticket flow'],
          ],
        ];

        if (!empty($other_modules)) {
          $section_shell['sections'][] = ['id' => 'extras', 'label' => 'Extras', 'hint' => 'Additional workspace modules'];
        }

      ?>
      <div class="trs-section-workspace" data-section-shell="dashboard-workspace" data-default-section="overview">
        <?php $this->load->view('Layout/section_shell_nav', ['section_shell' => $section_shell]); ?>

        <div class="trs-section-panels">
          <section class="trs-section-panel" data-section-panel="overview" hidden>
            <div class="trs-section-panel__body">
              <div class="row">
                <?php foreach ($overview_modules as $view_name) { ?>
                  <?php $view_path = APPPATH . 'views/' . $view_name . '.php'; ?>
                  <?php if ($view_name !== '' && is_file($view_path)) { ?>
                    <?php $this->load->view($view_name); ?>
                  <?php } else { ?>
                    <?php log_message('error', 'Dashboard module view not found: ' . $view_name); ?>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
          </section>

          <section class="trs-section-panel" data-section-panel="activity" hidden>
            <div class="trs-section-panel__body">
              <div class="row">
                <?php foreach ($activity_modules as $view_name) { ?>
                  <?php $view_path = APPPATH . 'views/' . $view_name . '.php'; ?>
                  <?php if ($view_name !== '' && is_file($view_path)) { ?>
                    <?php $this->load->view($view_name); ?>
                  <?php } else { ?>
                    <?php log_message('error', 'Dashboard module view not found: ' . $view_name); ?>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
          </section>

          <?php if (!empty($other_modules)) { ?>
            <section class="trs-section-panel" data-section-panel="extras" hidden>
              <div class="trs-section-panel__body">
                <div class="row">
                  <?php foreach ($other_modules as $view_name) { ?>
                    <?php $view_path = APPPATH . 'views/' . $view_name . '.php'; ?>
                    <?php if ($view_name !== '' && is_file($view_path)) { ?>
                      <?php $this->load->view($view_name); ?>
                    <?php } else { ?>
                      <?php log_message('error', 'Dashboard module view not found: ' . $view_name); ?>
                    <?php } ?>
                  <?php } ?>
                </div>
              </div>
            </section>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php
$this->load->view('Layout/Footer');
?>
