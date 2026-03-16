<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Developer extends MY_Controller
{
  protected function getCurrentUserId()
  {
    return (int) $this->session->userdata('user_id');
  }

  private function getPerformanceScopeUserIds()
  {
    $currentUserId = $this->getCurrentUserId();
    $subordinates = array_values(array_unique(array_map('intval', $this->User_model->getAllSubordinates($currentUserId))));

    return array_values(array_filter($subordinates, function ($userId) use ($currentUserId) {
      return $userId > 0 && $userId !== $currentUserId;
    }));
  }

  private function canAccessPerformance()
  {
    return (int) $this->session->userdata('role_id') === 2;
  }

  private function isUserInPerformanceScope($targetUserId, array $scopeUserIds)
  {
    return in_array((int) $targetUserId, array_map('intval', $scopeUserIds), true);
  }

  private function outputJson($payload, $statusCode = 200)
  {
    $this->output
      ->set_status_header($statusCode)
      ->set_content_type('application/json')
      ->set_output(json_encode($payload));
  }

  public function __construct()
  {
    parent::__construct();
    $this->load->model('User_model');
    $this->load->model('Ticket_model');
    if (!$this->session->userdata('is_login')) {
      redirect('login');
    }

    $this->load->model('Developer_model');
  }

  public function index()
  {
    $currentUserId = $this->session->userdata('user_id');
    $subordinates = $this->User_model->getAllSubordinates($currentUserId);
    $visibleUsers = array_merge([$currentUserId], $subordinates);

    $data['tickets'] = $this->Ticket_model->getVisibleTickets($currentUserId, $visibleUsers);
    $data['stats'] = $this->Ticket_model->getDashboardCounts($visibleUsers);

    $this->load->view('dashboard', $data);
  }

  public function developer_performance()
  {
    if (!$this->canAccessPerformance()) {
      $this->session->set_flashdata('failed', 'Unauthorized');
      redirect('Dashboard');
    }

    $year = (int) $this->input->get('year');
    if ($year <= 0) {
      $year = (int) date('Y');
    }

    $scopeUserIds = $this->getPerformanceScopeUserIds();
    $currentUserId = $this->getCurrentUserId();

    $developer['selected_year'] = $year;
    $developer['filters'] = $this->Developer_model->getDeveloperPerformanceFilters($year, $scopeUserIds);
    $developer['developer'] = $this->Developer_model->getDeveloperPerformance($year, $scopeUserIds, $currentUserId);
    $developer['overview'] = $this->Developer_model->getDeveloperPerformanceOverview($currentUserId, $year, $scopeUserIds);
    $developer['page_js'] = ['assets/dist/js/pages/developer-performance.js'];
    $this->load->view('Same_pages/developer_performance', $developer);
  }

  public function developer_performance_data()
  {
    if (!$this->canAccessPerformance()) {
      return $this->outputJson(['status' => false, 'message' => 'Unauthorized'], 403);
    }

    $year = (int) $this->input->get('year');
    if ($year <= 0) {
      $year = (int) date('Y');
    }

    $scopeUserIds = $this->getPerformanceScopeUserIds();
    $currentUserId = $this->getCurrentUserId();

    return $this->outputJson([
      'status' => true,
      'overview' => $this->Developer_model->getDeveloperPerformanceOverview($currentUserId, $year, $scopeUserIds),
      'developers' => $this->Developer_model->getDeveloperPerformance($year, $scopeUserIds, $currentUserId)
    ]);
  }

  public function developer_performance_detail()
  {
    if (!$this->canAccessPerformance()) {
      return $this->outputJson(['status' => false, 'message' => 'Unauthorized'], 403);
    }

    $developerId = (int) $this->input->get('developer_id');
    $year = (int) $this->input->get('year');

    if ($developerId <= 0) {
      return $this->outputJson(['status' => false, 'message' => 'Invalid developer'], 422);
    }

    if ($year <= 0) {
      $year = (int) date('Y');
    }

    $scopeUserIds = $this->getPerformanceScopeUserIds();
    $detail = $this->Developer_model->getDeveloperPerformanceDetail($developerId, $year, $scopeUserIds, $this->getCurrentUserId());

    if (!$detail) {
      return $this->outputJson(['status' => false, 'message' => 'Developer detail not found'], 404);
    }

    return $this->outputJson([
      'status' => true,
      'data' => $detail
    ]);
  }

  public function developer_performance_report()
  {
    if (!$this->canAccessPerformance()) {
      $this->session->set_flashdata('failed', 'Unauthorized');
      redirect('Developer/developer_performance');
      return;
    }

    $year = (int) $this->input->get('year');
    if ($year <= 0) {
      $year = (int) date('Y');
    }
    $from_date = $this->input->get('from_date');
    $to_date = $this->input->get('to_date');

    if (!$from_date || strtotime($from_date) === false) {
      $from_date = date('Y-01-01', strtotime($year . '-01-01'));
    }
    if (!$to_date || strtotime($to_date) === false) {
      $to_date = date('Y-m-d');
    }

    $scopeUserIds = $this->getPerformanceScopeUserIds();
    $currentUserId = $this->getCurrentUserId();

    $data = [
      'year' => $year,
      'from_date' => $from_date,
      'to_date' => $to_date,
      'overview' => $this->Developer_model->getDeveloperPerformanceOverview($currentUserId, $year, $scopeUserIds),
      'developers' => $this->Developer_model->getDeveloperPerformance($year, $scopeUserIds, $currentUserId),
    ];

    $this->load->view('Same_pages/developer_performance_report', $data);
  }

  public function developer_hierarchy_data()
  {
    if (!$this->canAccessPerformance()) {
      return $this->outputJson(['status' => false, 'message' => 'Unauthorized'], 403);
    }

    $year = (int) $this->input->get('year');
    if ($year <= 0) {
      $year = (int) date('Y');
    }

    $reviewerId = $this->getCurrentUserId();
    $scopeUserIds = $this->getPerformanceScopeUserIds();

    return $this->outputJson([
      'status' => true,
      'tree' => $this->Developer_model->getHierarchyTree($reviewerId),
      'eligible_users' => $this->Developer_model->getHierarchyEligibleUsers($reviewerId, $scopeUserIds),
      'overview' => $this->Developer_model->getDeveloperPerformanceOverview($reviewerId, $year, $scopeUserIds)
    ]);
  }

  public function developer_hierarchy_member()
  {
    if (!$this->canAccessPerformance()) {
      return $this->outputJson(['status' => false, 'message' => 'Unauthorized'], 403);
    }

    $userId = (int) $this->input->get('user_id');
    $year = (int) $this->input->get('year');

    if ($year <= 0) {
      $year = (int) date('Y');
    }

    if ($userId <= 0) {
      return $this->outputJson(['status' => false, 'message' => 'Invalid user'], 422);
    }

    $scopeUserIds = $this->getPerformanceScopeUserIds();
    $reviewerId = $this->getCurrentUserId();
    $eligibleIds = array_map('intval', array_column($this->Developer_model->getHierarchyEligibleUsers($reviewerId, $scopeUserIds), 'user_id'));

    if (
      $userId !== $reviewerId &&
      !$this->isUserInPerformanceScope($userId, $scopeUserIds) &&
      !in_array($userId, $eligibleIds, true)
    ) {
      return $this->outputJson(['status' => false, 'message' => 'User is outside your hierarchy scope'], 403);
    }

    $summary = $this->Developer_model->getHierarchyUserSummary($userId, $year);
    if (!$summary) {
      return $this->outputJson(['status' => false, 'message' => 'User not found'], 404);
    }

    return $this->outputJson(['status' => true, 'data' => $summary]);
  }

  public function developer_hierarchy_update()
  {
    if (!$this->canAccessPerformance()) {
      return $this->outputJson(['status' => false, 'message' => 'Unauthorized'], 403);
    }

    $targetUserId = (int) $this->input->post('target_user_id');
    $reportsToUserId = (int) $this->input->post('reports_to_user_id');
    $reviewerId = $this->getCurrentUserId();
    $scopeUserIds = $this->getPerformanceScopeUserIds();
    $eligibleIds = array_map('intval', array_column($this->Developer_model->getHierarchyEligibleUsers($reviewerId, $scopeUserIds), 'user_id'));
    $allowedManagerIds = array_merge([$reviewerId], array_map('intval', $scopeUserIds));

    if ($targetUserId <= 0 || $reportsToUserId <= 0) {
      return $this->outputJson(['status' => false, 'message' => 'Invalid hierarchy request'], 422);
    }

    if ($targetUserId === $reviewerId || $targetUserId === $reportsToUserId) {
      return $this->outputJson(['status' => false, 'message' => 'Invalid reporting relationship'], 422);
    }

    if (!in_array($reportsToUserId, $allowedManagerIds, true)) {
      return $this->outputJson(['status' => false, 'message' => 'Manager is outside your hierarchy scope'], 403);
    }

    $targetAllowed = $this->isUserInPerformanceScope($targetUserId, $scopeUserIds) || in_array($targetUserId, $eligibleIds, true);
    if (!$targetAllowed) {
      return $this->outputJson(['status' => false, 'message' => 'User is outside your hierarchy scope'], 403);
    }

    $targetDescendants = $this->Developer_model->getDescendantUserIds($targetUserId);
    if (in_array($reportsToUserId, array_map('intval', $targetDescendants), true)) {
      return $this->outputJson(['status' => false, 'message' => 'Circular hierarchy is not allowed'], 422);
    }

    if (!$this->Developer_model->updateReportingManager($targetUserId, $reportsToUserId)) {
      return $this->outputJson(['status' => false, 'message' => 'Unable to update hierarchy'], 500);
    }

    return $this->outputJson(['status' => true, 'message' => 'Hierarchy updated']);
  }

  public function developerLeaveTicket($ticket_id)
  {
    $developer_id = $this->session->userdata('user_id');

    $this->db->where('ticket_id', $ticket_id)
      ->update('tickets', [
        'assigned_engineer_id' => null,
        'status_id' => 1
      ]);

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

    <div class="mb-3">
      <h6>
        <strong>Currently handled by:</strong>
        <?= $latest['now_handled_by'] ?: 'Not Assigned' ?>

        <?php
        $status = strtolower($latest['recent_status']);

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
          <?= ucfirst(str_replace('_', ' ', $latest['recent_status'])) ?>

        </span>
      </h6>
    </div>

    <hr>

    <div class="approval-history with-line">

      <?php foreach ($history as $row): ?>
        <div class="approval-row">
          <div class="approval-date">
            <?= date('d-m-Y H:i:s', strtotime($row['created_at'])) ?>
          </div>

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
                <?= ucfirst($latest['recent_status']) ?>
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
    if ($this->session->userdata('role_id') == 2) {
      $this->load->model('Developer_model');

      $data['devBarData'] = $this->Developer_model->getDeveloperWiseStatus();

      $this->load->view('IT_head/Status', $data);
    } else {
      $this->session->set_flashdata("failed", " Unauthorized");
      redirect('Dashboard');
    }
  }

  public function getDeveloperTickets()
  {
    $dev_id = $this->input->post('developer_id');
    $this->load->model('Developer_model');

    $tickets = $this->Developer_model->getClosedResolvedProcessTickets($dev_id);
    $counts = $this->Developer_model->getStatusCountsForDeveloper($dev_id);

    $html = "";
    $html .= "
      <ul>
        <li>Open : {$counts['open']}</li>
        <li>In Process : {$counts['process']}</li>
        <li>Resolved : {$counts['resolved']}</li>
        <li>Closed : {$counts['closed']}</li>
      </ul>
      <hr>
    ";

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
   <td>" . ucfirst(str_replace('_', ' ', $t['recent_status'])) . "</td>
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

  public function Karban_Board()
  {
    $this->load->view('Developer/Karban_Board');
  }
}
