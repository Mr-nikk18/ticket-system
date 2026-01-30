<?php $this->load->view('Layout/Header'); ?>

<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Ticket History</h3>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover">
            <thead class="thead-light">
              <tr>
                <th>Ticket ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Current Handler</th>
                <th>History</th>
                <th>Last Updated</th>
              </tr>
            </thead>

            <tbody>
            <?php if (!empty($history)): ?>

              <?php
              // group by ticket
              $tickets = [];
              foreach ($history as $row) {
                  $tickets[$row['ticket_id']][] = $row;
              }
              ?>

              <?php foreach ($tickets as $ticket_id => $rows): 
                    $latest = $rows[0];
              ?>
                <tr>
                  <td><?= $ticket_id ?></td>

                  <td><?= htmlspecialchars($latest['title']) ?></td>

                  <td>
                    <span class="badge badge-info">
                      <?= ucfirst($latest['recent_status']) ?>
                    </span>
                  </td>

                  <td><?= htmlspecialchars($latest['ticket_owner']) ?></td>

                  <td><?= $latest['now_handled_by'] ?: 'Not Assigned' ?></td>

                  <td>
                    <ul class="mb-0 pl-3">
                      <?php foreach ($rows as $row): ?>
                        <li>
                          <?php
                          switch ($row['action_type']) {
                            case 'assign':
                              echo "<b>{$row['action_by']}</b> assigned to <b>{$row['assigned_to_name']}</b>";
                              break;

                            case 'accept':
                              echo "<b>{$row['action_by']}</b> accepted the ticket";
                              break;

                            case 'leave':
                              echo "<b>{$row['action_by']}</b> left the ticket";
                              break;

                            case 'reassign':
                              echo "<b>{$row['action_by']}</b> reassigned to <b>{$row['assigned_to_name']}</b>";
                              break;

                            default:
                              echo htmlspecialchars($row['remarks']);
                          }
                          ?>
                          <br>
                          <small class="text-muted">
                            <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                          </small>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </td>

                  <td>
                    <?= date('d M Y, H:i', strtotime($latest['created_at'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>

            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">
                  No history found
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<?php $this->load->view('Layout/Footer'); ?>
