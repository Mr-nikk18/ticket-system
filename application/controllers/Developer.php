<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Developer extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // login check
        if (!$this->session->userdata('is_login')) {
            redirect('login');
        }

        $this->load->model('Developer_model');
    }

    public function developer_performance()
    {
        $data['developers'] = $this->Developer_model->getDeveloperPerformance();

        
        $this->load->view('Same_pages/developer_performance', $data);
        
    }
public function developerLeaveTicket($ticket_id)
{
    $developer_id = $this->session->userdata('user_id');

    // Update ticket
    $this->db->where('ticket_id', $ticket_id)
             ->update('tickets', [
                 'assigned_engineer_id' => NULL,
                 'status' => 'Open'
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

    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="thead-light">
          <tr>
            <th>Ticket ID</th>
            <th>Owner</th>
            <th>Title</th>
            <th>Current Handler</th>
            <th>Status</th>
            <th>History</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= $latest['ticket_id'] ?></td>

            <td><?= htmlspecialchars($latest['ticket_owner']) ?></td>

            <td><?= htmlspecialchars($latest['title']) ?></td>

            <td>
              <?= $latest['now_handled_by'] ?: 'Not Assigned' ?>
            </td>
        
            <td>
              <span class="badge badge-info">
                <?= ucfirst($latest['recent_status']) ?>
              </span>
            </td>

            <td>
  <ul class="mb-0 pl-3">
    <?php foreach ($history as $row): ?>
      <li>
        <?php
          switch ($row['action_type']) {

            case 'assign':
              echo "<b>{$row['action_by']}</b> assigned ticket to <b>{$row['assigned_to_name']}</b>";
              break;

            case 'accept':
              echo "<b>{$row['action_by']}</b> accepted the ticket";
              break;

            case 'leave':
              echo "<b>{$row['action_by']}</b> left the ticket";
              break;

            case 'reassign':
              echo "<b>{$row['action_by']}</b> reassigned ticket to <b>{$row['assigned_to_name']}</b>";
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

          </tr>
        </tbody>
      </table>
    </div>

    <?php
}



}
