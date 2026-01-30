<?php
$this->load->view('Layout/Header');
?>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show text-center flash-msg" role="alert">
    <?= $this->session->flashdata('error'); ?>
  </div>
<?php endif; ?>

<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show text-center flash-msg" role="alert">
    <?= $this->session->flashdata('success'); ?>
  </div>
<?php endif; ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>DataTables</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">DataTables</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex align-items-center">
              <h3 class="card-title mb-0">
                <?= isset($current_status) && $current_status
                  ? ucfirst(str_replace('_', ' ', $current_status)) . ' Tickets'
                  : 'All Tickets' ?>
              </h3>

              <?php if ($this->session->userdata('role_id') == 1) { ?>
                <button type="button" data-toggle="modal" data-target="#createModal"
                  class="btn btn-primary btn-sm ml-auto">
                  Generate Ticket
                  </a>
                <?php } ?>
            </div>


            <!-- /.card-header -->
            <div class="card-body">
              <table id="example2" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th>No.</th>
                    <?php if (in_array($this->session->userdata('role_id'), [2, 3])) { ?>
                      <th>User Name</th>
                      <th>Department</th>
                    <?php } ?>

                    <th>Ticket ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Handled by</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>


                  </tr>
                </thead>
                <tbody>
                  <?php $n = 1 ?>
                  <?php foreach ($val as $value) { ?>
                    <tr>
                      <td><?= $n ?></td>
                      <?php if (in_array($this->session->userdata('role_id'), [2, 3])) { ?>
                        <td><?= $value['user_full_name'] ?></td>
                        <td><?= $value['user_department'] ?></td>
                      <?php } ?>

                      <td><?= $value['ticket_id'] ?></td>
                      <td><?= $value['title'] ?></td>
                      <td><?= $value['description'] ?></td>
                      <td>
                        <?= !empty($value['assigned_engineer_name'])
                          ? $value['assigned_engineer_name']
                          : 'Not Assigned' ?>
                      </td>
                      <td>
                        <?php if ($value['status'] == 'open') { ?>
                          <span class="fas fa-circle text-success">Open</span>

                        <?php } elseif ($value['status'] == 'in_progress') { ?>
                          <span class="fas fa-circle text-warning">In Process</span>

                        <?php } elseif ($value['status'] == 'resolved') { ?>
                          <span class="fas fa-circle text-warning">resolved</span>

                        <?php } else { ?>
                          <span class="fas fa-circle text-danger">Closed</span>
                        <?php } ?>
                      </td>
                      <td><?= $value['created_at'] ?></td>


                      <!-- EDIT -->
                      <td>
                        <?php
                        $role_id = $this->session->userdata('role_id');
                        $user_id = $this->session->userdata('user_id');
                        ?>

                        <!-- ================= USER ================= -->
                        <?php if ($role_id == 1) { ?>

                          <?php
                          $reopen = $this->session->userdata('reopen_edit_allowed');
                          ?>

                          <!-- 🟡 RESOLVED → ASK CONFIRMATION -->
                          <?php if ($value['status'] == 'resolved') { ?>

                            <span class="text-info d-block mb-1">Is issue solved?</span>

                            <a href="<?= base_url('TRS/confirm_ticket/' . $value['ticket_id'] . '/yes') ?>"
                              class="btn btn-sm btn-success mb-1">Yes</a>

                            <a href="<?= base_url('TRS/confirm_ticket/' . $value['ticket_id'] . '/no') ?>"
                              class="btn btn-sm btn-warning mb-1">No</a>

                            <!-- 🔵 OPEN & NOT ASSIGNED -->
                          <?php } elseif ($value['status'] == 'open' && $value['assigned_engineer_id'] == null) { ?>

                            <!--     <a href="<?= base_url('TRS/edit/' . $value['ticket_id']) ?>"
           class="btn btn-sm btn-primary mb-1">Edit</a> -->

                            <a
                              class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#editModal" onclick="editFun(<?= $value['ticket_id'] ?>)">Edit</a>


                            <br>
                            <a href="<?= base_url('TRS/delete/' . $value['ticket_id']) ?>"
                              class="btn btn-sm btn-danger mb-1"
                              onclick="return confirm('Are you sure?');">
                              Delete
                            </a>

                            <!-- 🟢 REOPENED (IN_PROGRESS WITH FLAG) → ALLOW ONLY EDIT -->
                          <?php } elseif (
                            $value['status'] == 'in_progress' &&
                            is_array($reopen) &&
                            isset($reopen[$value['ticket_id']]) &&
                            $reopen[$value['ticket_id']] === true
                          ) { ?>

                            <a
                              class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#editModal"

                              onclick="editFun(<?= $value['ticket_id'] ?>)">Edit</a>


                            <!-- 🔴 ALL OTHER CASES -->
                          <?php } else { ?>

                            <span class="badge badge-secondary">No Action</span>

                          <?php } ?>

                        <?php } ?>





                        <!-- ================= DEVELOPER ================= -->

                        <?php if ($role_id == 2) { ?>

                          <?php if ($value['status'] == 'open' && empty($value['assigned_engineer_id'])) { ?>

                            <!-- ACCEPT -->
                            <a href="<?= base_url('TRS/accept_ticket/' . $value['ticket_id']) ?>"
                              class="btn btn-sm btn-success mb-1">Accept</a><br>

                            <a class="btn btn-sm btn-dark mb-1"
                              data-toggle="modal" data-target="#historyModal"
                              onclick="history(<?= $value['ticket_id'] ?>)">History</a><br>

                          <?php } elseif (
                            $value['status'] == 'in_progress'
                            && $value['assigned_engineer_id'] == $user_id
                          ) { ?>

                            <!-- EDIT -->
                            <a class="btn btn-sm btn-primary mb-1"
                              data-toggle="modal" data-target="#editModal"
                              onclick="editFun(<?= $value['ticket_id'] ?>)">Edit</a><br>

                            <!-- LEAVE -->
                            <!-- LEAVE -->
                          <button 
                            class="btn btn-sm btn-warning mb-1"
                            onclick="openLeaveModal(<?= $value['ticket_id'] ?>)">
                            Leave
                          </button>


                            <!-- HANDOVER / REASSIGN -->
                            <?php if ($value['assigned_engineer_id'] == $user_id) { ?>
                              <a class="btn btn-sm btn-info mb-1"
                                data-toggle="modal" data-target="#reassignModal"
                                onclick="reassign(<?= $value['ticket_id'] ?>)">Handover</a><br>
                            <?php } ?>
                            <a class="btn btn-sm btn-dark mb-1"
                              data-toggle="modal" data-target="#historyModal"
                              onclick="history(<?= $value['ticket_id'] ?>)">History</a><br>

                          <?php } else { ?>

                            <!-- ONLY HISTORY -->
                            <a class="btn btn-sm btn-dark mb-1"
                              data-toggle="modal" data-target="#historyModal"
                              onclick="history(<?= $value['ticket_id'] ?>)">History</a><br>

                          <?php } ?>

                        <?php } ?>



                        <!-- ================= IT HEAD ================= -->
                        <?php if ($role_id == 3) { ?>

                          <?php if ($value['status'] == 'open') { ?>
                            <a href="<?= base_url('TRS/accept_ticket/' . $value['ticket_id']) ?>"
                              class="btn btn-sm btn-success mb-1">Accept</a><br>

                            <a class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#assignModal"
                              onclick="assign(<?= $value['ticket_id'] ?>)">Assign</a><br>

                            <a
                              class="btn btn-sm btn-dark mb-1 view-history" data-toggle="modal" data-target="#historyModal"
                              onclick="history(<?= $value['ticket_id'] ?>)">History</a><br>

                          <?php } elseif ($value['status'] == 'in_progress' || ($value['status'] == 'resolved')) { ?>

                            <a class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#editModal"
                              onclick="editFun(<?= $value['ticket_id'] ?>)">Edit</a><br>
                              <?php if($value['assigned_engineer_id'] == $user_id){ ?>
                            <a href="<?= base_url('TRS/leave_ticket/' . $value['ticket_id']) ?>"
                              class="btn btn-sm btn-warning">Leave</a><br>
                              <?php } ?>
                               <?php if($value['assigned_engineer_id'] == $user_id){ ?>
                            <a class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#reassignModal"
                              onclick="reassign(<?= $value['ticket_id'] ?>)">Handover</a><br>
                              <?php } ?>
                            <a href="<?= base_url('TRS/delete/' . $value['ticket_id']) ?>"
                              class="btn btn-sm btn-danger"
                              onclick="return confirm('Delete ticket?');">
                              Delete
                            </a><br>

                            <a
                              class="btn btn-sm btn-dark mb-1 view-history" data-toggle="modal" data-target="#historyModal"
                              onclick="history(<?= $value['ticket_id'] ?>)">History</a><br>

                          <?php } else { ?>

                            <a
                              class="btn btn-sm btn-dark mb-1 view-history" data-toggle="modal" data-target="#historyModal"
                              onclick="history(<?= $value['ticket_id'] ?>)">History</a>

                          <?php } ?>
                        <?php } ?>



                      </td>

                    </tr>
                    <?php $n++ ?>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </section>

  <!-- /.content -->
</div>
<!-- /.content-wrapper -->


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





<div class="modal fade" id="editModal">
  <div class="modal-dialog">
    <div class="modal-content bg-light">

      <div class="modal-header">
        <h4 class="modal-title">Edit Ticket</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <form id="editForm">
          <input type="hidden" name="ticket_id" id="edit_ticket_id">

          <!-- USER -->
          <?php if ($this->session->userdata('role_id') == 1) { ?>
            <div id="userSection" class="role-section">
              <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="edit_title" class="form-control">
              </div>

              <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control"></textarea>
              </div>
            </div>
          <?php } ?>

          <!-- DEVELOPER -->
          <?php if ($this->session->userdata('role_id') == 2) { ?>
            <div id="devSection" class="role-section">
              <div class="form-group">
                <label>Title</label>
                <input type="text" id="edit_title" class="form-control" readonly>
              </div>

              <div class="form-group">
                <label>Description</label>
                <textarea id="edit_description" class="form-control" readonly></textarea>
              </div>

              <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status_dev" class="form-control">
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                </select>
              </div>
            </div>
          <?php } ?>

          <!-- ADMIN -->
          <?php if ($this->session->userdata('role_id') == 3) { ?>
            <div id="adminSection" class="role-section">
              <div class="form-group">
                <label>Title</label>
                <input type="text" id="edit_title" class="form-control" readonly>
              </div>

              <div class="form-group">
                <label>Description</label>
                <textarea id="edit_description" class="form-control" readonly></textarea>
              </div>

              <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status_admin" class="form-control">
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
            </div>
          <?php } ?>

          <button type="submit" class="btn btn-primary mt-2">
            Update Ticket
          </button>
        </form>

      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-lg ">
    <div class="modal-content">

      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">
          <i class="fas fa-history mr-2"></i>
          Ticket History
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">
          &times;
        </button>
      </div>

      <div class="modal-body" id="historyContainer">
        <input type="hidden" name="ticket_id" id="history_ticket_id">


      </div>

    </div>
  </div>
</div>



<div class="modal fade" id="assignModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title">Assign Developer</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <form id="assignForm">

        <div class="modal-body">

          <input type="hidden" name="ticket_id" id="assign_ticket_id">

          <div class="form-group">
            <label>Title</label>
            <input type="text" id="edit_title" class="form-control" readonly>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea id="edit_description" class="form-control" readonly></textarea>
          </div>

          <div class="form-group">
            <label>Assign To</label>
            <select name="assigned_engineer_id" id="edit_assigned" class="form-control">
              <option value="">-- Select Developer --</option>
            </select>
          </div>


        </div>

        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Assign Developer</button>
        </div>

      </form>

    </div>
  </div>
</div>

<div class="modal fade" id="reassignModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title">Reassign Developer</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <form id="reassignForm">

        <div class="modal-body">

          <input type="hidden" name="ticket_id" id="assign_ticket_id">

          <div class="form-group">
            <label>Title</label>
            <input type="text" id="edit_title" class="form-control" readonly>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea id="edit_description" class="form-control" readonly></textarea>
          </div>

          <div class="form-group">
            <label>Reassign To</label>
            <select name="assigned_engineer_id" id="edit_assigned" class="form-control">
              <option value="">-- Select Developer --</option>
            </select>
          </div>

         <div class="form-group">
            <label>Reason</label>
            <textarea name="reason"  id="edit_reason" class="form-control" required></textarea>
          </div>
          

        </div>

        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Reassign</button>
        </div>

      </form>

    </div>
  </div>
</div>


<div class="modal fade" id="leaveModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <form id="leaveForm">

        <div class="modal-header">
          <h5 class="modal-title">Leave Ticket</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="ticket_id" id="leave_ticket_id">

          <div class="form-group">
            <label>Title</label>
            <input type="text" id="leave_title" class="form-control" readonly>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea id="leave_description" class="form-control" readonly></textarea>
          </div>

          <div class="form-group">
            <label>Reason</label>
            <textarea
              name="reason"
              id="leave_reason"
              class="form-control"
              required></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-warning">
            Confirm Leave
          </button>
        </div>

      </form>

    </div>
  </div>
</div>



<?php
$this->load->view('Layout/Footer');
?>