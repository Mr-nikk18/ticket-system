<?php
$this->load->view('Layout/Header');
?>

<div class="content-wrapper" id="mainContent">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Kanban Board</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('Dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Kanban Board</li>
          </ol>
        </div>
      </div>
      <div class="d-flex justify-content-end mb-3">
        <?php if ((int) $this->session->userdata('department_id') === 2 && (int) $this->session->userdata('role_id') === 2) { ?>
          <select id="kanbanBoardMode" class="form-control mr-2" style="width:200px; display:inline-block;">
            <option value="workflow">My Workflow Board</option>
            <option value="team">Team Kanban</option>
          </select>
        <?php } ?>
        <select id="kanbanFilter" class="form-control" style="width:200px; display:inline-block;">
          <?php if ((int) $this->session->userdata('department_id') === 2 && (int) $this->session->userdata('role_id') === 2) { ?>
            <option value="assigned">Assigned + Open Queue</option>
            <option value="raised">Self Raised</option>
            <option value="all">All Tickets</option>
          <?php } elseif ((int) $this->session->userdata('role_id') === 2) { ?>
            <option value="assigned">Department Board</option>
            <option value="raised">Self Raised</option>
            <option value="all">All Departments</option>
          <?php } else { ?>
            <option value="assigned"><?= ((int) $this->session->userdata('department_id') === 2 && (int) $this->session->userdata('role_id') !== 2) ? 'Assigned + Open Queue' : 'Department Board' ?></option>
            <option value="raised">Self Raised</option>
          <?php } ?>
        </select>
        <button class="btn btn-secondary mr-2" id="refreshBoard">
          Refresh
        </button>
        <button class="btn btn-primary" data-toggle="modal" data-target="#createModal">
          + Generate Ticket
        </button>
      </div>
    </div>
  </section>

  <section class="content kanban-shell">
    <div class="kanban-board-box" id="workflowBoardWrap">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">My Workflow Board</h4>
        <small class="text-muted">Assigned flow and self-raised view</small>
      </div>
      <div class="row kanban-board-grid">
        <?php foreach ($statuses as $status): ?>
          <div class="col-md-3">
            <div class="card">
              <div class="card-header bg-dark text-white">
                <?= $status->status_name ?>
              </div>
              <div class="card-body kanban-column" id="column<?= $status->status_id ?>" data-status="<?= $status->status_id ?>">
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ((int) $this->session->userdata('department_id') === 2 && (int) $this->session->userdata('role_id') === 2) { ?>
    <section class="content kanban-shell pt-0">
      <div class="kanban-board-box" id="teamBoardWrap" style="display:none;">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Team Kanban</h4>
          <small class="text-muted">All developers and all ticket statuses</small>
        </div>
        <div class="row kanban-board-grid">
          <?php foreach ($statuses as $status): ?>
            <div class="col-md-3">
              <div class="card">
                <div class="card-header bg-secondary text-white">
                  <?= $status->status_name ?>
                </div>
                <div class="card-body kanban-team-column" id="teamColumn<?= $status->status_id ?>" data-status="<?= $status->status_id ?>">
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php } ?>
</div>

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
          <div class="card-body bg-dark">
            <div class="form-group">
              <label>Title</label>
              <input type="text" name="title" class="form-control" placeholder="Enter Title" required>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control" placeholder="Enter Description" required></textarea>
            </div>

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
    </div>
  </div>
</form>

<script>
  window.KanbanBoardConfig = {
    baseUrl: "<?= base_url() ?>",
    roleId: <?= (int) $this->session->userdata('role_id') ?>,
    departmentId: <?= (int) $this->session->userdata('department_id') ?>,
    currentUserId: <?= (int) $this->session->userdata('user_id') ?>,
    isRoleTwo: <?= (int) $this->session->userdata('role_id') === 2 ? 'true' : 'false' ?>,
    boardTicketsUrl: "<?= base_url('TRS/ajax_get_board_tickets') ?>",
    updateBoardUrl: "<?= base_url('TRS/update_board_position') ?>",
    reopenUrl: "<?= base_url('TRS/reopen_ticket') ?>"
  };
</script>
<?php
$this->load->view('Layout/Footer');
?>
