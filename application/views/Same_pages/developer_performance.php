<?php $this->load->view('Layout/Header'); ?>

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
                    <th>Resolved</th>
                    <th>Pending</th>
                    <th>Performance</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($developers)): ?>
                    <?php $i=1; foreach ($developers as $dev): ?>
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
        <div class="col-md-6">
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
        <div class="col-md-6">
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
  $(function () {
    /*
     * Flot Interactive Chart


     * -----------------------
     */
    // We use an inline data source in the example, usually data would
    // be fetched from a server


  // INTERACTIVE CHART
  var data = [];
  for (var i = 0; i <= 20; i++) {
    data.push([i, Math.random() * 100]);
  }

  $.plot('#interactive', [data], {
    series: { lines: { show: true, fill: true } }
  });

  // LINE CHART
  var sin = [], cos = [];
  for (var i = 0; i < 14; i += 0.5) {
    sin.push([i, Math.sin(i)]);
    cos.push([i, Math.cos(i)]);
  }

  $.plot('#line-chart', [
    { data: sin, label: 'Resolved', color: '#3c8dbc' },
    { data: cos, label: 'Pending',  color: '#00c0ef' }
  ], {
    series: { lines: { show: true }, points: { show: true } }
  });

  // BAR CHART
  $.plot('#bar-chart', [{
    data: [[1,10],[2,8],[3,15],[4,6]],
    bars: { show: true }
  }], {
    xaxis: {
      ticks: [[1,'Dev A'],[2,'Dev B'],[3,'Dev C'],[4,'Dev D']]
    }
  });

});



    var data        = [],
        totalPoints = 100

    function getRandomData() {

      if (data.length > 0) {
        data = data.slice(1)
      }

      // Do a random walk
      while (data.length < totalPoints) {

        var prev = data.length > 0 ? data[data.length - 1] : 50,
            y    = prev + Math.random() * 10 - 5

        if (y < 0) {
          y = 0
        } else if (y > 100) {
          y = 100
        }

        data.push(y)
      }

      // Zip the generated y values with the x values
      var res = []
      for (var i = 0; i < data.length; ++i) {
        res.push([i, data[i]])
      }

      return res
    }

    var interactive_plot = $.plot('#interactive', [
        {
          data: getRandomData(),
        }
      ],
      {
        grid: {
          borderColor: '#f3f3f3',
          borderWidth: 1,
          tickColor: '#f3f3f3'
        },
        series: {
          color: '#3c8dbc',
          lines: {
            lineWidth: 2,
            show: true,
            fill: true,
          },
        },
        yaxis: {
          min: 0,
          max: 100,
          show: true
        },
        xaxis: {
          show: true
        }
      }
    )

    var updateInterval = 500 //Fetch data ever x milliseconds
    var realtime       = 'on' //If == to on then fetch data every x seconds. else stop fetching
    function update() {

      interactive_plot.setData([getRandomData()])

      // Since the axes don't change, we don't need to call plot.setupGrid()
      interactive_plot.draw()
      if (realtime === 'on') {
        setTimeout(update, updateInterval)
      }
    }

    //INITIALIZE REALTIME DATA FETCHING
    if (realtime === 'on') {
      update()
    }
    //REALTIME TOGGLE
    $('#realtime .btn').click(function () {
      if ($(this).data('toggle') === 'on') {
        realtime = 'on'
      }
      else {
        realtime = 'off'
      }
      update()
    
    /*
     * END INTERACTIVE CHART
     */


    /*
     * LINE CHART
     * ----------
     */
    //LINE randomly generated data

    var sin = [],
        cos = []
    for (var i = 0; i < 14; i += 0.5) {
      sin.push([i, Math.sin(i)])
      cos.push([i, Math.cos(i)])
    }
    var line_data1 = {
      data : sin,
      color: '#3c8dbc'
    }
    var line_data2 = {
      data : cos,
      color: '#00c0ef'
    }
    $.plot('#line-chart', [line_data1, line_data2], {
      grid  : {
        hoverable  : true,
        borderColor: '#f3f3f3',
        borderWidth: 1,
        tickColor  : '#f3f3f3'
      },
      series: {
        shadowSize: 0,
        lines     : {
          show: true
        },
        points    : {
          show: true
        }
      },
      lines : {
        fill : false,
        color: ['#3c8dbc', '#f56954']
      },
      yaxis : {
        show: true
      },
      xaxis : {
        show: true
      }
    })
    //Initialize tooltip on hover
    $('<div class="tooltip-inner" id="line-chart-tooltip"></div>').css({
      position: 'absolute',
      display : 'none',
      opacity : 0.8
    }).appendTo('body')
    $('#line-chart').bind('plothover', function (event, pos, item) {

      if (item) {
        var x = item.datapoint[0].toFixed(2),
            y = item.datapoint[1].toFixed(2)

        $('#line-chart-tooltip').html(item.series.label + ' of ' + x + ' = ' + y)
          .css({
            top : item.pageY + 5,
            left: item.pageX + 5
          })
          .fadeIn(200)
      } else {
        $('#line-chart-tooltip').hide()
      }

    })
    /* END LINE CHART */

   
    var areaData = [[2, 88.0], [3, 93.3], [4, 102.0], [5, 108.5], [6, 115.7], [7, 115.6],
      [8, 124.6], [9, 130.3], [10, 134.3], [11, 141.4], [12, 146.5], [13, 151.7], [14, 159.9],
      [15, 165.4], [16, 167.8], [17, 168.7], [18, 169.5], [19, 168.0]]
    $.plot('#area-chart', [areaData], {
      grid  : {
        borderWidth: 0
      },
      series: {
        shadowSize: 0, // Drawing is faster without shadows
        color     : '#00c0ef',
        lines : {
          fill: true //Converts the line chart to area chart
        },
      },
      yaxis : {
        show: false
      },
      xaxis : {
        show: false
      }
    })

    /* END AREA CHART */

    
    var bar_data = {
      data : [[1,10], [2,8], [3,4], [4,13], [5,17], [6,9]],
      bars: { show: true }
    }
    $.plot('#bar-chart', [bar_data], {
      grid  : {
        borderWidth: 1,
        borderColor: '#f3f3f3',
        tickColor  : '#f3f3f3'
      },
      series: {
         bars: {
          show: true, barWidth: 0.5, align: 'center',
        },
      },
      colors: ['#3c8dbc'],
      xaxis : {
        ticks: [[1,'January'], [2,'February'], [3,'March'], [4,'April'], [5,'May'], [6,'June']]
      }
    })
    /* END BAR CHART */

    
  })

  /*
   * Custom Label formatter
   * ----------------------
   */
 
</script>


</body>
</html>
