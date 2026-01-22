<?php
$this->load->view('Layout/Header');
?>

<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section >
      <div class="container-fluid">
        <div class="row mb-2">
          
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
                <h4>Ticket Assignment History</h4>
                <h3 class="card-title"></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->


<table class="table table-sm table-bordered">
  <thead>
    <tr>
    <th>Ticket ID</th>
<th>Ticket Owner</th>
<th>Title</th>
<th>Assigned By</th>
<th>Assigned To</th>
<th>Remarks</th>
<th>Date</th>

</tr>

  </thead>
  <tbody>

  <?php if (!empty($history)): ?>

    <?php 
    // 🔥 VERY IMPORTANT:
    // history must be ordered DESC by created_at in query
    // so first row = latest action
    $isFirst = true; 
    ?>

    <?php foreach ($history as $row): ?>

        <tr>
    <td><?= $row['ticket_id'] ?></td>

    <!-- Ticket Owner -->
    <td><?= htmlspecialchars($row['ticket_owner']) ?></td>

    <!-- Ticket Title -->
    <td><?= htmlspecialchars($row['title']) ?></td>

    <!-- Assigned By -->
    <td><?= htmlspecialchars($row['assigned_by_name']) ?></td>

    <!-- Assigned To -->
    <td><?= htmlspecialchars($row['assigned_to_name'] ?? '-') ?></td>

    <!-- Remarks -->
    <td><?= htmlspecialchars($row['remarks']) ?></td>

    <!-- Date -->
    <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>

</tr>


        <?php $isFirst = false; // after first row, no more buttons ?>

    <?php endforeach; ?>

  <?php else: ?>

    <tr>
        <td colspan="8" class="text-center">No history found</td>
    </tr>

  <?php endif; ?>

  </tbody>
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

  <?php 
$this->load->view('layout/Footer');
?>