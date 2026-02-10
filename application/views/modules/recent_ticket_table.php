<div class="col-12 mt-3">


    <div class="card">

      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
          <i class="fas fa-ticket-alt mr-1"></i>
          Recent Tickets
        </h3>

        <a href="<?= base_url('list') ?>"
           class="btn btn-primary btn-sm ml-auto">
          View All
        </a>
      </div>

      <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped w-100">

          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Title</th>
              <th>Description</th>
              <th>Status</th>
              <th>Date</th>
              <th>Handle By:</th>
                <th>Action</th>
            </tr>
          </thead>

          <tbody>

          <?php if (!empty($recent_tickets)) { ?>
            <?php foreach ($recent_tickets as $ticket) { ?>
              <tr>
                <td><?= $ticket['ticket_id'] ?></td>
                <td><?= $ticket['title'] ?></td>
                <td><?= $ticket['description'] ?></td>

                <td>
                  <?php if ($ticket['status'] == 'open') { ?>
                    <span class="badge badge-success">Open</span>
                  <?php } elseif ($ticket['status'] == 'in_progress') { ?>
                    <span class="badge badge-warning">In Process</span>
                  <?php } elseif ($ticket['status'] == 'resolved') { ?>
                    <span class="badge badge-info">Resolved</span>
                  <?php } else { ?>
                    <span class="badge badge-secondary">Closed</span>
                  <?php } ?>
                </td>

                <td><?= date('d-m-Y', strtotime($ticket['created_at'])) ?></td>
                <td><?= $ticket['assigned_engineer_name'] ?? 'Not Assigned' ?>
</td>

                <td>
  <?php if ($ticket['can_accept'] == 1) { ?>
      <a href="<?= base_url('TRS/accept_ticket/'.$ticket['ticket_id']) ?>"
         class="btn btn-sm btn-success">
         Accept
      </a>
  <?php } else { ?>
      <span class="badge badge-secondary">No action </span>
  <?php } ?>
</td>


              </tr>
            <?php } ?>
          <?php } else { ?>
            <tr>
              <td colspan="7" class="text-center">No tickets found</td>
            </tr>
          <?php } ?>

          </tbody>

        </table>
      </div>

    

  </div>
</div>
