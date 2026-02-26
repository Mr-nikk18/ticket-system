<?php
$this->load->view('Layout/Header');
?>

 <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" id="mainContent">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Kanban Board</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=base_url('Dashboard')?>">Home</a></li>
              <li class="breadcrumb-item active">Kanban Board</li>
            </ol>
          </div>
        </div>
       <div class="d-flex justify-content-end mb-3">

    <button class="btn btn-secondary mr-2" id="refreshBoard">
        🔄 Refresh
    </button>

    <button class="btn btn-primary"
            data-toggle="modal"
            data-target="#createModal">
        + Generate Ticket
    </button>

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
                        <?php $status->status_slug == 'closed' ? 'data-closed="1"' : '' ?>
                      <?php
                        $total = $this->db->where('ticket_id', $ticket->ticket_id)
                                          ->count_all_results('ticket_tasks');

                        $completed = $this->db->where('ticket_id', $ticket->ticket_id)
                                              ->where('is_completed', 1)
                                              ->count_all_results('ticket_tasks');

                        $can_resolve = ($total > 0 && $total == $completed) ? 1 : 0;
                        ?>
                     
                       <div class="ticket-card mb-2 p-2 border" 
     data-id="<?= $ticket->ticket_id ?>" 
     data-can-resolve="<?= $can_resolve ?>">

    <h5>ticket id: #<?= $ticket->ticket_id ?></h5>
<strong>Title:<br>
    <h6><?= $ticket->title ?></h6></strong>
<div class="task-count">
   <?= $completed ?> / <?= $total ?> Completed
</div>

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

<!-- Ticket Detail Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">

    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Ticket Details</h5>
        <button type="button" class="close text-white" data-dismiss="modal">
          &times;
        </button>
      </div>

      <div class="modal-body">
        <div id="ticketDetailContent">
            Loading...
        </div>
      </div>

    </div>
  </div>
</div>



<form id="createForm">
  <div class="modal fade" id="createModal">
    <div class="modal-dialog">
      <div class="modal-content bg-info">
        <div class="modal-header">
          <h4 class="modal-title">Add Ticket</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="modalBody">

          <!-- form start -->

          <div class="card-body bg-dark">

            <!-- Title -->
            <div class="form-group">
              <label>Title</label>
              <input type="text" name="title" class="form-control" placeholder="Enter Title" required>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control" placeholder="Enter Description" required></textarea>
            </div>

             <!-- Tasks Section -->
            <div class="form-group">
                <label>Tasks</label>

                <div id="taskWrapper">
                    <div class="input-group mb-2">
                        <input type="text" name="tasks[]" class="form-control" placeholder="Enter Task">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-danger removeTask">X</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-primary" id="addTaskFieldCreate">
                    + Add More Task
                </button>
            </div>


            <!-- Terms -->
            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" name="terms" class="custom-control-input" id="terms" required>
                <label class="custom-control-label" for="terms">
                  I agree to the <a href="#">terms of service</a>
                </label>
              </div>
            </div>

          </div>
        </div>


        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-light" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-outline-light">Generate Ticket</button>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.modal -->
</form>


<?php
$this->load->view('Layout/Footer');
?>