<?php
$this->load->view('Layout/Header');
?>

 <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Karban Board</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=base_url('Dashboard')?>">Home</a></li>
              <li class="breadcrumb-item active">Karban Board</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
    <!-- Main content -->
  
   <div class="row">

<?php foreach($statuses as $status): ?>
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <?= $status->status_name ?>
            </div>

            <div class="card-body kanban-column"
                 data-status="<?= $status->status_id ?>">

                <?php foreach($tickets as $ticket): ?>
                    <?php if($ticket->status_id == $status->status_id): ?>
                     
                        <div class="ticket-card mb-2 p-2 border"
                        
                             data-id="<?= $ticket->ticket_id ?>">
                             
                            <h5>ticket id: #<?= $ticket->ticket_id ?></h5><br>
                            <strong><?= $ticket->title ?></strong><br>
                            <small><?= $ticket->priority_name ?></small>

                        </div>

                    <?php endif; ?>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
<?php endforeach; ?>

</div>


  </div>
  <!-- /.content-wrapper -->

<?php
$this->load->view('Layout/Footer');
?>