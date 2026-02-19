<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Developer extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('User_model');
    $this->load->model('Ticket_model');
    // login check
    if (!$this->session->userdata('is_login')) {
      redirect('login');
    }

    $this->load->model('Developer_model');
  }

  public function index()
  {
    $currentUserId = $this->session->userdata('user_id');

    // Get all subordinates
    $subordinates = $this->User_model->getAllSubordinates($currentUserId);

    // Include self
    $visibleUsers = array_merge([$currentUserId], $subordinates);

    // Fetch tickets
    $data['tickets'] = $this->Ticket_model->getVisibleTickets($currentUserId, $visibleUsers);

    // Dashboard stats
    $data['stats'] = $this->Ticket_model->getDashboardCounts($visibleUsers);

    $this->load->view('dashboard', $data);
  }


  public function developer_performance()
  {

    if ($this->session->userdata('role_id') != 3) {
      $this->session->set_flashdata('failed', '🚫Unauthorized🚫');
      redirect('Dashboard');
    }
    $developer['developer'] = $this->Developer_model->getDeveloperPerformance();
    $this->load->view('Same_pages/developer_performance', $developer);
  }
  public function developer_performance_data()
  {

    $data = $this->Developer_model->getDeveloperPerformance();
    echo json_encode($data);
  }


  public function developerLeaveTicket($ticket_id)
  {
    $developer_id = $this->session->userdata('user_id');

    // Update ticket
    $this->db->where('ticket_id', $ticket_id)
      ->update('tickets', [
        'assigned_engineer_id' => NULL,
        'status_id' => 1
      ]);

    // Log history via model
    $this->load->model('Ticket_model');
    $this->Ticket_model->addHistory([
      'ticket_id'    => $ticket_id,
      'developer_id' => $developer_id,
      'action'       => 'left',
      'action_by'    => $developer_id,
      'remarks'      => 'Developer left the ticket'
    ]);

    $this->session->set_flashdata('success', 'You have left the ticket');
    redirect('Developer/ticket');
  }

  public function history_by_ticket()
{
    $ticket_id = $this->input->post('ticket_id');

    if (!$ticket_id) {
      echo '<p class="text-danger">No ticket id</p>';
      return;
    }

    $this->load->model('Ticket_model');
    $history = $this->Ticket_model->TicketHistory($ticket_id);

    if (empty($history)) {
      echo '<p class="text-muted text-center">No history found</p>';
      return;
    }

    $latest = $history[0];
   ?>


    <!-- ===================== -->
    <!-- 1️⃣ TICKET SUMMARY -->
    <!-- ===================== -->
    <div class="mb-3">
      <h6 class="mb-1"><strong>
          Ticket #<?= $latest['ticket_id'] ?></strong>
      </h6>
      <div class="text-muted">
        <h6> <strong> Owner: <?= htmlspecialchars($latest['ticket_owner']) ?> </strong> </h6>
      </div>
      <div><strong><?= htmlspecialchars($latest['title']) ?></strong></div>
    </div>

    <hr>

    <!-- ===================== -->
    <!-- 2️⃣ CURRENT HANDLER -->
    <!-- ===================== -->
      <div class="mb-3">
        <h6>
          <strong>Currently handled by:</strong>
          <?= $latest['now_handled_by'] ?: 'Not Assigned' ?>

          <?php
          $status = strtolower($latest['status_slug']);

          switch ($status) {
            case 'open':
              $badge = 'badge-success';
              break;
            case 'in_progress':
            case 'in process':
              $badge = 'badge-warning';
              break;
            case 'closed':
              $badge = 'badge-danger';
              break;
            case 'resolved':
              $badge = 'badge-primary';
              break;
            default:
              $badge = 'badge-secondary';
          }
          ?>

          <span class="badge <?= $badge ?> ml-2">
            <?= ucfirst(str_replace('_',' ',$latest['status_slug'])) ?>

          </span>
        </h6>
      </div>

      <hr>

      <!-- ===================== -->
      <!-- 3️⃣ TIMELINE (HISTORY) -->
      <!-- ===================== -->


      <div class="approval-history with-line">

        <?php foreach ($history as $row): ?>
          <div class="approval-row">

            <!-- LEFT DATE -->
            <div class="approval-date">
              <?= date('d-m-Y H:i:s', strtotime($row['created_at'])) ?>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="approval-box">

              <div class="approval-header">
                <span class="approval-user">
                  <?= htmlspecialchars($row['action_by']) ?>
                </span>

                <span class="approval-status">
                  <?= ucfirst($row['action_type']) ?>
                </span>
              </div>

            <div class="approval-text">
              <?php
              switch ($row['action_type']) {
                case 'assign':
                  echo "Assigned ticket to <b>{$row['assigned_to_name']}</b>";
                  break;

                case 'accept':
                  echo "Accepted the ticket";
                  break;

                case 'leave':
                  echo "Left the ticket";
                  break;

                case 'reassign':
                  echo "Reassigned ticket to <b>{$row['assigned_to_name']}</b>";
                  break;
              }
              ?>
            </div>

            <?php if (!empty($row['remarks'])): ?>
              <div class="approval-remarks">
                <?php
                if (stripos($row['remarks'], 'Reason:') !== false) {
                  echo nl2br(htmlspecialchars($row['remarks']));
                } else {
                  echo "Reason: " . htmlspecialchars($row['remarks']);
                }
                ?>
              </div>
            <?php endif; ?>

          </div>

        </div>
      <?php endforeach; ?>

    </div>

    <hr>



    <!-- ===================== -->
    <!-- 4️⃣ TABLE (LAST) -->
    <!-- ===================== -->
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="thead-light">
          <tr>
            <th>Ticket ID</th>
            <th>Owner</th>
            <th>Title</th>
            <th>Current Handler</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= $latest['ticket_id'] ?></td>
            <td><?= htmlspecialchars($latest['ticket_owner']) ?></td>
            <td><?= htmlspecialchars($latest['title']) ?></td>
            <td><?= $latest['now_handled_by'] ?: 'Not Assigned' ?></td>
            <td>
              <span class="badge <?= $badge ?>">
                <?= ucfirst($latest['status_slug']) ?>
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

 <?php
}

  public function status()
  {
    if ($this->session->userdata('role_id') == 3) {
      $this->load->model('Developer_model');

      $data['devBarData'] = $this->Developer_model->getDeveloperWiseStatus();


      $this->load->view('IT_head/Status', $data);
    } else {
      $this->session->set_flashdata("failed", " 🚫Unauthorized🚫");
      redirect('Dashboard');
    }
  }
  public function getDeveloperTickets()
  {
    $dev_id = $this->input->post('developer_id');
    $this->load->model('Developer_model');

    // get tickets
    $tickets = $this->Developer_model
      ->getClosedResolvedProcessTickets($dev_id);

    // get counts
    $counts = $this->Developer_model
      ->getStatusCountsForDeveloper($dev_id);

    // ---------- BUILD HTML ----------
    $html = "";

    // summary
    $html .= "
      <ul>
        <li>Open : {$counts['open']}</li>
        <li>In Process : {$counts['process']}</li>
        <li>Resolved : {$counts['resolved']}</li>
        <li>Closed : {$counts['closed']}</li>
      </ul>
      <hr>
    ";

    // table start
    $html .= "
      <table class='table table-bordered'>
        <thead>
          <tr>
            <th>No</th>
            <th>Title</th>
            <th>Status</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
    ";

    if (!empty($tickets)) {
      $i = 1;
      foreach ($tickets as $t) {
        $html .= "
              <tr>
                <td>{$i}</td>
                <td>{$t['title']}</td>
<td>" . ucfirst(str_replace('_',' ', $t['status_slug'])) . "</td>
                <td>{$t['created_at']}</td>
              </tr>
            ";
        $i++;
      }
    } else {
      $html .= "
          <tr>
            <td colspan='4' class='text-center'>No tickets found</td>
          </tr>
        ";
    }

    $html .= "</tbody></table>";

    echo $html;
  }
  public function Karban_Board(){
    $this->load->view('Developer/Karban_Board');
  }
}
