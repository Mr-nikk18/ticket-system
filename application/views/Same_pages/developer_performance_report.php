<?php
$year = isset($year) ? (int) $year : (int) date('Y');
$from_date = isset($from_date) ? (string) $from_date : date('Y-01-01');
$to_date = isset($to_date) ? (string) $to_date : date('Y-m-d');
$overview = isset($overview) && is_array($overview) ? $overview : [];
$developers = isset($developers) && is_array($developers) ? $developers : [];
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
    th { background: #d9e7f5; font-weight: 700; }
    .meta { margin-bottom: 16px; }
    .meta td { width: 25%; }
    .section-title { background: #1f4e78; color: #fff; font-weight: 700; }
  </style>
</head>
<body>
  <table class="meta">
    <tr>
      <th colspan="4" class="section-title">Developer Performance Export</th>
    </tr>
    <tr>
      <td><strong>Year</strong></td>
      <td><?= $year ?></td>
      <td><strong>Date Range</strong></td>
      <td><?= htmlspecialchars($from_date) ?> to <?= htmlspecialchars($to_date) ?></td>
    </tr>
    <tr>
      <td><strong>Generated At</strong></td>
      <td><?= date('Y-m-d H:i:s') ?></td>
      <td><strong>Total Developers</strong></td>
      <td><?= count($developers) ?></td>
    </tr>
  </table>

  <table>
    <tr>
      <th colspan="8" class="section-title">Overview</th>
    </tr>
    <tr>
      <th>Hierarchy Members</th>
      <th>Direct Reports</th>
      <th>Assigned By You</th>
      <th>Accepted By Team</th>
      <th>Open Tickets</th>
      <th>In Progress Tickets</th>
      <th>Resolved Tickets</th>
      <th>Closed Tickets</th>
    </tr>
    <tr>
      <td><?= (int) ($overview['total_reports'] ?? 0) ?></td>
      <td><?= (int) ($overview['direct_reports'] ?? 0) ?></td>
      <td><?= (int) ($overview['reviewer_assigned'] ?? 0) ?></td>
      <td><?= (int) ($overview['accepted_total'] ?? 0) ?></td>
      <td><?= (int) ($overview['open_tickets'] ?? 0) ?></td>
      <td><?= (int) ($overview['in_progress_tickets'] ?? 0) ?></td>
      <td><?= (int) ($overview['resolved_tickets'] ?? 0) ?></td>
      <td><?= (int) ($overview['closed_tickets'] ?? 0) ?></td>
    </tr>
    <tr>
      <th>Delegation Absence</th>
      <th>Active Delegations</th>
      <th>Leave Days Total</th>
      <th>Present Days Total</th>
      <th colspan="4"></th>
    </tr>
    <tr>
      <td><?= (int) ($overview['delegation_absence'] ?? 0) ?></td>
      <td><?= (int) ($overview['active_delegation_absence'] ?? 0) ?></td>
      <td><?= (int) ($overview['leave_days_total'] ?? 0) ?></td>
      <td><?= (int) ($overview['present_days_total'] ?? 0) ?></td>
      <td colspan="4"></td>
    </tr>
  </table>

  <br>

  <table>
    <tr>
      <th colspan="17" class="section-title">Developer Summary</th>
    </tr>
    <tr>
      <th>Name</th>
      <th>Company</th>
      <th>Department</th>
      <th>Total Tickets</th>
      <th>Assigned Tickets</th>
      <th>Accepted Tickets</th>
      <th>Resolved Tickets</th>
      <th>Pending Tickets</th>
      <th>Reviewer Assigned</th>
      <th>Avg Resolution Hours</th>
      <th>Invested Hours</th>
      <th>Incoming Delegations</th>
      <th>Outgoing Delegations</th>
      <th>Active Delegations</th>
      <th>Direct Reports</th>
      <th>Leave Days</th>
      <th>Present Days</th>
    </tr>
    <?php if (!empty($developers)) { ?>
      <?php foreach ($developers as $dev) { ?>
        <tr>
          <td><?= htmlspecialchars((string) ($dev['name'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string) ($dev['company_name'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string) ($dev['department_name'] ?? '')) ?></td>
          <td><?= (int) ($dev['total_tickets'] ?? 0) ?></td>
          <td><?= (int) ($dev['assigned_tickets'] ?? 0) ?></td>
          <td><?= (int) ($dev['accepted_tickets'] ?? 0) ?></td>
          <td><?= (int) ($dev['resolved_tickets'] ?? 0) ?></td>
          <td><?= (int) ($dev['pending_tickets'] ?? 0) ?></td>
          <td><?= (int) ($dev['reviewer_assigned_tickets'] ?? 0) ?></td>
          <td><?= number_format((float) ($dev['avg_resolution_hours'] ?? 0), 1, '.', '') ?></td>
          <td><?= number_format((float) ($dev['invest_hours'] ?? 0), 1, '.', '') ?></td>
          <td><?= (int) ($dev['incoming_delegations'] ?? 0) ?></td>
          <td><?= (int) ($dev['outgoing_delegations'] ?? 0) ?></td>
          <td><?= (int) ($dev['active_delegations'] ?? 0) ?></td>
          <td><?= (int) ($dev['direct_reports'] ?? 0) ?></td>
          <td><?= (int) ($dev['leave_days'] ?? 0) ?></td>
          <td><?= (int) ($dev['present_days'] ?? 0) ?></td>
        </tr>
      <?php } ?>
    <?php } else { ?>
      <tr>
        <td colspan="17">No data available.</td>
      </tr>
    <?php } ?>
  </table>
</body>
</html>
