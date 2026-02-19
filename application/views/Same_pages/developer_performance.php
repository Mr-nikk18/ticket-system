<?php 
$this->load->view('Layout/Header'); 
?>

<div class="content-wrapper">

  <!-- Page Header -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Developer Analytics</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item">
              <a href="<?= base_url('dashboard') ?>">Home</a>
            </li>
            <li class="breadcrumb-item active">Developer Analytics</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">

<!-- ===================== PERFORMANCE TABLE ===================== -->
      <div class="row">
        <div class="col-md-12">

          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Developer Performance Report</h3>
            </div>

            <div class="card-body">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Developer Name</th>
                    <th>Company</th>
                    <th>Total Tickets</th>
                    <th>Solved</th>
                    <th>Pending</th>
                    <th>Performance</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($developer)): ?>
                    <?php $i=1; foreach ($developer as $dev): ?>
                      <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($dev['name']) ?></td>
                        <td><?= htmlspecialchars($dev['company_name']) ?></td>
                        <td><?= $dev['total_tickets'] ?></td>
                        <td class="text-success"><?= $dev['resolved_tickets'] ?></td>
                        <td class="text-danger"><?= $dev['pending_tickets'] ?></td>
                        <td>
                          <?php
                            $performance = $dev['total_tickets'] > 0
                              ? round(($dev['resolved_tickets'] / $dev['total_tickets']) * 100)
                              : 0;
                          ?>
                          <span class="badge badge-info"><?= $performance ?>%</span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center">No data available</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>

        </div>
      </div>
      <!-- ===================== /PERFORMANCE TABLE ===================== -->


      <!-- ===================== CHARTS ROW ===================== -->
      <div class="row">

        <!-- Interactive Chart -->
        <div class="col-md-12">
          <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">
                <i class="far fa-chart-bar"></i> Ticket Activity (Real Time)
              </h3>
            </div>
            <div class="card-body">
              <div id="interactive" style="height:300px;"></div>
            </div>
          </div>
        </div>

        <!-- Line Chart -->
        <div class="col-md-12">
          <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">Resolved Tickets Trend</h3>
            </div>
            <div class="card-body">
              <div id="line-chart" style="height:300px;"></div>
            </div>
          </div>
        </div>

        <!-- Bar Chart -->
        <div class="col-md-12">
          <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">Developer-wise Tickets</h3>
            </div>
            <div class="card-body">
              <div id="bar-chart" style="height:300px;"></div>
            </div>
          </div>
        </div>

      </div>
      <!-- ===================== /CHARTS ROW ===================== -->

      
    </div>
  </section>

</div>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 3.1.0
    </div>
    <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="<?= base_url() ?>assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?= base_url() ?>assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?= base_url() ?>assets/dist/js/adminlte.min.js"></script>
<!-- FLOT CHARTS -->
<script src="<?= base_url() ?>assets/plugins/flot/jquery.flot.js"></script>
<!-- FLOT RESIZE PLUGIN - allows the chart to redraw when the window is resized -->
<script src="<?= base_url() ?>assets/plugins/flot/plugins/jquery.flot.resize.js"></script>
<!-- FLOT PIE PLUGIN - also used to draw donut charts -->
<script src="<?= base_url() ?>assets/plugins/flot/plugins/jquery.flot.pie.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="<?= base_url() ?>assets/dist/js/demo.js"></script>
<!-- Page specific script -->
 <script src="<?= base_url('assets/plugins/flot/plugins/jquery.flot.time.js') ?>"></script>
<script>
$(document).ready(function() {

    $.ajax({
       url: "<?= base_url('Developer/developer_performance_data') ?>",
        type: "GET",
        dataType: "json",
        success: function(response) {

            var performanceData = [];
            var totalData = [];
            var resolvedData = [];
            var pendingData = [];
            var ticks = [];

            for (var i = 0; i < response.length; i++) {

                var total = parseInt(response[i].total_tickets) || 0;
                var resolved = parseInt(response[i].resolved_tickets) || 0;
                var pending = parseInt(response[i].pending_tickets) || 0;

                var performance = 0;
                if (total > 0) {
                    performance = Math.round((resolved / total) * 100);
                }

                performanceData.push([i + 1, performance]);
                totalData.push([i + 1, total]);
                resolvedData.push([i + 1, resolved]);
                pendingData.push([i + 1, pending]);
                ticks.push([i + 1, response[i].name]);
            }

            

            // BAR CHART (Performance %)
            $.plot('#bar-chart', [{
                data: performanceData,
                bars: { show: true, barWidth: 0.5, align: 'center' }
            }], {
                xaxis: { ticks: ticks },
                yaxis: { min: 0, max: 100 },
                grid: { borderWidth: 1 }
            });

            // LINE CHART (Resolved vs Pending)
            $.plot('#line-chart', [
                { data: resolvedData, label: "Resolved" },
                { data: pendingData, label: "Pending" }
            ], {
                series: { lines: { show: true }, points: { show: true } },
                xaxis: { ticks: ticks }
            });

            // INTERACTIVE CHART (Total Tickets)
            $.plot('#interactive', [{
                data: totalData,
                lines: { show: true, fill: true }
            }], {
                xaxis: { ticks: ticks }
            });

        }
    });

});
</script>



</body>
</html>
