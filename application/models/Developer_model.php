<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Developer_model extends CI_Model
{
    private function getYearWindow($year)
    {
        $year = (int) ($year ?: date('Y'));
        $currentYear = (int) date('Y');
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-12-31', $year);

        if ($year > $currentYear) {
            return [
                'start' => $start,
                'end' => $end,
                'elapsed_days' => 0,
            ];
        }

        $windowEnd = $year < $currentYear ? $end : date('Y-m-d');
        $startDate = new DateTime($start);
        $endDate = new DateTime($windowEnd);
        $elapsedDays = $endDate >= $startDate
            ? ((int) $startDate->diff($endDate)->format('%a') + 1)
            : 0;

        return [
            'start' => $start,
            'end' => $end,
            'elapsed_days' => $elapsedDays,
        ];
    }

    public function getDeveloperPerformance($year = null, array $scopeUserIds = [], $reviewerId = 0)
    {
        $year = $year ?: date('Y');
        $reviewerId = (int) $reviewerId;
        $yearWindow = $this->getYearWindow($year);
        $yearStart = $yearWindow['start'];
        $yearEnd = $yearWindow['end'];
        $elapsedDays = (int) $yearWindow['elapsed_days'];

        if (empty($scopeUserIds)) {
            return [];
        }

        $rows = $this->db
            ->select('
                u.user_id,
                u.name,
                u.company_name,
                d.department_name,
                (
                    SELECT COUNT(*)
                    FROM tickets t
                    WHERE t.assigned_engineer_id = u.user_id
                      AND t.deleted_at IS NULL
                      AND YEAR(t.created_at) = ' . (int) $year . '
                ) AS total_tickets,
                (
                    SELECT COUNT(*)
                    FROM tickets t
                    WHERE t.assigned_engineer_id = u.user_id
                      AND t.deleted_at IS NULL
                      AND t.status_id = 4
                      AND YEAR(t.created_at) = ' . (int) $year . '
                ) AS resolved_tickets,
                (
                    SELECT COUNT(*)
                    FROM tickets t
                    WHERE t.assigned_engineer_id = u.user_id
                      AND t.deleted_at IS NULL
                      AND t.status_id != 4
                      AND YEAR(t.created_at) = ' . (int) $year . '
                ) AS pending_tickets,
                (
                    SELECT COUNT(*)
                    FROM ticket_assignment_history tah
                    WHERE tah.assigned_to = u.user_id
                      AND tah.action_type IN ("assign","reassign","auto_assign")
                      AND YEAR(tah.created_at) = ' . (int) $year . '
                ) AS assigned_tickets,
                (
                    SELECT COUNT(*)
                    FROM ticket_assignment_history tah
                    WHERE tah.assigned_to = u.user_id
                      AND tah.action_type = "accept"
                      AND YEAR(tah.created_at) = ' . (int) $year . '
                ) AS accepted_tickets,
                (
                    SELECT COALESCE(ROUND(AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at)),1),0)
                    FROM tickets t
                    WHERE t.assigned_engineer_id = u.user_id
                      AND t.closed_at IS NOT NULL
                      AND YEAR(t.created_at) = ' . (int) $year . '
                ) AS avg_resolution_hours,
                (
                    SELECT COUNT(*)
                    FROM task_delegations td
                    WHERE td.delegated_user_id = u.user_id
                      AND td.approval_status = "approved"
                      AND YEAR(td.start_date) = ' . (int) $year . '
                ) AS incoming_delegations,
                (
                    SELECT COUNT(*)
                    FROM task_delegations td
                    WHERE td.original_user_id = u.user_id
                      AND td.approval_status = "approved"
                      AND YEAR(td.start_date) = ' . (int) $year . '
                ) AS outgoing_delegations,
                (
                    SELECT COUNT(*)
                    FROM task_delegations td
                    WHERE (td.original_user_id = u.user_id OR td.delegated_user_id = u.user_id)
                      AND td.approval_status = "approved"
                      AND td.start_date <= CURDATE()
                      AND td.end_date >= CURDATE()
                ) AS active_delegations,
                (
                    SELECT COUNT(*)
                    FROM ticket_assignment_history tah
                    WHERE tah.assigned_to = u.user_id
                      AND tah.assigned_by = ' . $reviewerId . '
                      AND tah.action_type IN ("assign","reassign","auto_assign")
                      AND YEAR(tah.created_at) = ' . (int) $year . '
                ) AS reviewer_assigned_tickets,
                (
                    SELECT COALESCE(ROUND(SUM(TIMESTAMPDIFF(HOUR, t.created_at, IFNULL(t.closed_at, NOW()))),1),0)
                    FROM tickets t
                    WHERE t.assigned_engineer_id = u.user_id
                      AND t.deleted_at IS NULL
                      AND YEAR(t.created_at) = ' . (int) $year . '
                ) AS invest_hours,
                (
                    SELECT COUNT(*)
                    FROM users ur
                    WHERE ur.reports_to = u.user_id
                      AND ur.status = "Active"
                ) AS direct_reports,
                (
                    SELECT COALESCE(SUM(GREATEST(0, DATEDIFF(LEAST(td.end_date, ' . $this->db->escape($yearEnd) . '), GREATEST(td.start_date, ' . $this->db->escape($yearStart) . ')) + 1)), 0)
                    FROM task_delegations td
                    WHERE td.original_user_id = u.user_id
                      AND td.approval_status = "approved"
                      AND td.start_date <= ' . $this->db->escape($yearEnd) . '
                      AND td.end_date >= ' . $this->db->escape($yearStart) . '
                ) AS leave_days
            ')
            ->from('users u')
            ->join('departments d', 'd.department_id = u.department_id', 'left')
            ->where('u.role_id', 1)
            ->where('u.department_id', 2)
            ->where_in('u.user_id', array_map('intval', $scopeUserIds))
            ->order_by('u.name', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $row['leave_days'] = (int) ($row['leave_days'] ?? 0);
            $row['present_days'] = max($elapsedDays - $row['leave_days'], 0);
        }
        unset($row);

        return $rows;
    }

    public function getDeveloperPerformanceOverview($reviewerId, $year = null, array $scopeUserIds = [])
    {
        $reviewerId = (int) $reviewerId;
        $year = $year ?: date('Y');
        $yearWindow = $this->getYearWindow($year);
        $yearStart = $yearWindow['start'];
        $yearEnd = $yearWindow['end'];
        $elapsedDays = (int) $yearWindow['elapsed_days'];

        if ($reviewerId <= 0 || empty($scopeUserIds)) {
            return [
                'reviewer_assigned' => 0,
                'accepted_total' => 0,
                'direct_reports' => 0,
                'total_reports' => 0,
                'open_tickets' => 0,
                'in_progress_tickets' => 0,
                'resolved_tickets' => 0,
                'closed_tickets' => 0,
                'delegation_absence' => 0,
                'active_delegation_absence' => 0,
                'leave_days_total' => 0,
                'present_days_total' => 0,
            ];
        }

        $reportIds = array_map('intval', $scopeUserIds);

        $statusSummary = $this->db
            ->select('
                SUM(CASE WHEN t.status_id = 1 THEN 1 ELSE 0 END) AS open_tickets,
                SUM(CASE WHEN t.status_id = 2 THEN 1 ELSE 0 END) AS in_progress_tickets,
                SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) AS resolved_tickets,
                SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS closed_tickets
            ', false)
            ->from('tickets t')
            ->where_in('t.assigned_engineer_id', $reportIds)
            ->where('t.deleted_at IS NULL', null, false)
            ->where('YEAR(t.created_at)', (int) $year)
            ->get()
            ->row_array();

        $reviewerAssigned = $this->db
            ->where('assigned_by', $reviewerId)
            ->where_in('assigned_to', $reportIds)
            ->where_in('action_type', ['assign', 'reassign', 'auto_assign'])
            ->where('YEAR(created_at)', (int) $year)
            ->count_all_results('ticket_assignment_history');

        $acceptedTotal = $this->db
            ->where_in('assigned_to', $reportIds)
            ->where('action_type', 'accept')
            ->where('YEAR(created_at)', (int) $year)
            ->count_all_results('ticket_assignment_history');

        $directReports = $this->db
            ->where('reports_to', $reviewerId)
            ->where('status', 'Active')
            ->count_all_results('users');

        $delegationAbsence = $this->db
            ->where_in('original_user_id', $reportIds)
            ->where('approval_status', 'approved')
            ->where('YEAR(start_date)', (int) $year)
            ->count_all_results('task_delegations');

        $activeDelegationAbsence = $this->db
            ->where_in('original_user_id', $reportIds)
            ->where('approval_status', 'approved')
            ->where('start_date <=', date('Y-m-d'))
            ->where('end_date >=', date('Y-m-d'))
            ->count_all_results('task_delegations');

        $leaveDaysTotal = (int) ($this->db
            ->select('COALESCE(SUM(GREATEST(0, DATEDIFF(LEAST(end_date, ' . $this->db->escape($yearEnd) . '), GREATEST(start_date, ' . $this->db->escape($yearStart) . ')) + 1)), 0) AS leave_days_total', false)
            ->from('task_delegations')
            ->where_in('original_user_id', $reportIds)
            ->where('approval_status', 'approved')
            ->where('start_date <=', $yearEnd)
            ->where('end_date >=', $yearStart)
            ->get()
            ->row()->leave_days_total ?? 0);

        $presentDaysTotal = max((count($reportIds) * $elapsedDays) - $leaveDaysTotal, 0);

        return [
            'reviewer_assigned' => (int) $reviewerAssigned,
            'accepted_total' => (int) $acceptedTotal,
            'direct_reports' => (int) $directReports,
            'total_reports' => count($reportIds),
            'open_tickets' => (int) ($statusSummary['open_tickets'] ?? 0),
            'in_progress_tickets' => (int) ($statusSummary['in_progress_tickets'] ?? 0),
            'resolved_tickets' => (int) ($statusSummary['resolved_tickets'] ?? 0),
            'closed_tickets' => (int) ($statusSummary['closed_tickets'] ?? 0),
            'delegation_absence' => (int) $delegationAbsence,
            'active_delegation_absence' => (int) $activeDelegationAbsence,
            'leave_days_total' => (int) $leaveDaysTotal,
            'present_days_total' => (int) $presentDaysTotal,
        ];
    }

    public function getDeveloperWiseStatus()
    {
        return $this->db
            ->select("
                u.user_id,
                u.name,
                (
                    SELECT COUNT(*)
                    FROM tickets
                    WHERE status_id = 1
                      AND deleted_at IS NULL
                ) AS open_cnt,
                SUM(CASE WHEN t.status_id = 2 THEN 1 ELSE 0 END) AS process_cnt,
                SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) AS resolved_cnt,
                SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS closed_cnt
            ")
            ->from('users u')
            ->join('tickets t', 't.assigned_engineer_id = u.user_id', 'left')
            ->where('u.role_id', 1)
            ->where('u.department_id', 2)
            ->group_by('u.user_id')
            ->get()
            ->result_array();
    }

    public function getTicketsByDeveloper($dev_id)
    {
        return $this->db
            ->where('assigned_engineer_id', $dev_id)
            ->where('deleted_at IS NULL', null, false)
            ->get('tickets')
            ->result_array();
    }

    public function getClosedResolvedProcessTickets($dev_id)
    {
        return $this->db
            ->where('assigned_engineer_id', $dev_id)
            ->where_in('status_id', [1, 4, 3, 2])
            ->get('tickets')
            ->result_array();
    }

    public function getStatusCountsForDeveloper($dev_id)
    {
        return $this->db
            ->select("
                (SELECT COUNT(*) FROM tickets
                 WHERE status_id = 1 AND deleted_at IS NULL) as open,
                SUM(status_id = 2) as process,
                SUM(status_id = 3) as resolved,
                SUM(status_id = 4) as closed
            ")
            ->where('assigned_engineer_id', $dev_id)
            ->get('tickets')
            ->row_array();
    }

    public function getDeveloperPerformanceFilters($year = null, array $scopeUserIds = [])
    {
        $year = $year ?: date('Y');

        if (empty($scopeUserIds)) {
            return ['companies' => []];
        }

        $companies = $this->db
            ->distinct()
            ->select('u.company_name')
            ->from('users u')
            ->where('u.role_id', 1)
            ->where('u.department_id', 2)
            ->where_in('u.user_id', array_map('intval', $scopeUserIds))
            ->where('u.company_name IS NOT NULL', null, false)
            ->where('u.company_name !=', '')
            ->order_by('u.company_name', 'ASC')
            ->get()
            ->result_array();

        $departments = $this->db
            ->distinct()
            ->select('d.department_name')
            ->from('users u')
            ->join('departments d', 'd.department_id = u.department_id', 'left')
            ->where('u.role_id', 1)
            ->where('u.department_id', 2)
            ->where_in('u.user_id', array_map('intval', $scopeUserIds))
            ->where('d.department_name IS NOT NULL', null, false)
            ->where('d.department_name !=', '')
            ->order_by('d.department_name', 'ASC')
            ->get()
            ->result_array();

        return [
            'companies' => array_values(array_filter(array_map(function ($row) {
                return $row['company_name'];
            }, $companies))),
            'departments' => array_values(array_filter(array_map(function ($row) {
                return $row['department_name'];
            }, $departments)))
        ];
    }

    public function getDeveloperPerformanceDetail($developerId, $year = null, array $scopeUserIds = [], $reviewerId = 0)
    {
        $developerId = (int) $developerId;
        $year = $year ?: date('Y');
        $reviewerId = (int) $reviewerId;
        $yearWindow = $this->getYearWindow($year);
        $yearStart = $yearWindow['start'];
        $yearEnd = $yearWindow['end'];
        $elapsedDays = (int) $yearWindow['elapsed_days'];

        if ($developerId <= 0 || empty($scopeUserIds) || !in_array($developerId, array_map('intval', $scopeUserIds), true)) {
            return null;
        }

        $developer = $this->db
            ->select('u.user_id, u.name, u.company_name, u.email, d.department_name')
            ->from('users u')
            ->join('departments d', 'd.department_id = u.department_id', 'left')
            ->where('u.user_id', $developerId)
            ->where('u.role_id', 1)
            ->where('u.department_id', 2)
            ->get()
            ->row_array();

        if (!$developer) {
            return null;
        }

        $summary = $this->db
            ->select('
                COUNT(*) AS total_tickets,
                SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS resolved_tickets,
                SUM(CASE WHEN t.status_id != 4 THEN 1 ELSE 0 END) AS pending_tickets,
                SUM(CASE WHEN t.status_id IN (1,2,3) THEN 1 ELSE 0 END) AS active_tickets
            ', false)
            ->from('tickets t')
            ->where('t.assigned_engineer_id', $developerId)
            ->where('t.deleted_at IS NULL', null, false)
            ->where('YEAR(t.created_at)', (int) $year)
            ->get()
            ->row_array();

        $acceptedCount = $this->db
            ->where('assigned_to', $developerId)
            ->where('action_type', 'accept')
            ->where('YEAR(created_at)', (int) $year)
            ->count_all_results('ticket_assignment_history');

        $avgResolutionHours = (float) $this->db
            ->select('COALESCE(ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)),1),0) AS avg_hours', false)
            ->from('tickets')
            ->where('assigned_engineer_id', $developerId)
            ->where('closed_at IS NOT NULL', null, false)
            ->where('YEAR(created_at)', (int) $year)
            ->get()
            ->row()->avg_hours;

        $investHours = (float) $this->db
            ->select('COALESCE(ROUND(SUM(TIMESTAMPDIFF(HOUR, created_at, IFNULL(closed_at, NOW()))),1),0) AS invested', false)
            ->from('tickets')
            ->where('assigned_engineer_id', $developerId)
            ->where('deleted_at IS NULL', null, false)
            ->where('YEAR(created_at)', (int) $year)
            ->get()
            ->row()->invested;

        $incomingDelegationCount = $this->db
            ->where('delegated_user_id', $developerId)
            ->where('approval_status', 'approved')
            ->where('YEAR(start_date)', (int) $year)
            ->count_all_results('task_delegations');

        $outgoingDelegationCount = $this->db
            ->where('original_user_id', $developerId)
            ->where('approval_status', 'approved')
            ->where('YEAR(start_date)', (int) $year)
            ->count_all_results('task_delegations');

        $activeDelegationCount = $this->db
            ->where('(original_user_id = ' . $developerId . ' OR delegated_user_id = ' . $developerId . ')', null, false)
            ->where('approval_status', 'approved')
            ->where('start_date <=', date('Y-m-d'))
            ->where('end_date >=', date('Y-m-d'))
            ->count_all_results('task_delegations');

        $leaveDays = (int) ($this->db
            ->select('COALESCE(SUM(GREATEST(0, DATEDIFF(LEAST(end_date, ' . $this->db->escape($yearEnd) . '), GREATEST(start_date, ' . $this->db->escape($yearStart) . ')) + 1)), 0) AS leave_days', false)
            ->from('task_delegations')
            ->where('original_user_id', $developerId)
            ->where('approval_status', 'approved')
            ->where('start_date <=', $yearEnd)
            ->where('end_date >=', $yearStart)
            ->get()
            ->row()->leave_days ?? 0);

        $presentDays = max($elapsedDays - $leaveDays, 0);

        $reviewerAssignedCount = 0;
        if ($reviewerId > 0) {
            $reviewerAssignedCount = $this->db
                ->where('assigned_to', $developerId)
                ->where('assigned_by', $reviewerId)
                ->where_in('action_type', ['assign', 'reassign', 'auto_assign'])
                ->where('YEAR(created_at)', (int) $year)
                ->count_all_results('ticket_assignment_history');
        }

        $assignmentBreakdown = $this->db
            ->select("
                COALESCE(assigner.name, 'System / Auto') AS assigner_name,
                COUNT(*) AS assign_count
            ", false)
            ->from('ticket_assignment_history tah')
            ->join('users assigner', 'assigner.user_id = tah.assigned_by', 'left')
            ->where('tah.assigned_to', $developerId)
            ->where_in('tah.action_type', ['assign', 'reassign', 'auto_assign'])
            ->where('YEAR(tah.created_at)', (int) $year)
            ->group_by('assigner_name')
            ->order_by('assign_count', 'DESC')
            ->order_by('assigner_name', 'ASC')
            ->get()
            ->result_array();

        $acceptedTickets = $this->db
            ->select("
                tah.ticket_id,
                t.title,
                owner.name AS owner_name,
                ts.status_name,
                tah.created_at AS accepted_at
            ")
            ->from('ticket_assignment_history tah')
            ->join('tickets t', 't.ticket_id = tah.ticket_id')
            ->join('users owner', 'owner.user_id = t.user_id', 'left')
            ->join('ticket_statuses ts', 'ts.status_id = t.status_id', 'left')
            ->where('tah.assigned_to', $developerId)
            ->where('tah.action_type', 'accept')
            ->where('YEAR(tah.created_at)', (int) $year)
            ->order_by('tah.created_at', 'DESC')
            ->get()
            ->result_array();

        $assignedTickets = $this->db
            ->select("
                tah.ticket_id,
                t.title,
                owner.name AS owner_name,
                ts.status_name,
                COALESCE(assigner.name, 'System / Auto') AS assigner_name,
                tah.action_type,
                tah.created_at AS assigned_at
            ", false)
            ->from('ticket_assignment_history tah')
            ->join('tickets t', 't.ticket_id = tah.ticket_id')
            ->join('users owner', 'owner.user_id = t.user_id', 'left')
            ->join('ticket_statuses ts', 'ts.status_id = t.status_id', 'left')
            ->join('users assigner', 'assigner.user_id = tah.assigned_by', 'left')
            ->where('tah.assigned_to', $developerId)
            ->where_in('tah.action_type', ['assign', 'reassign', 'auto_assign'])
            ->where('YEAR(tah.created_at)', (int) $year)
            ->order_by('tah.created_at', 'DESC')
            ->get()
            ->result_array();

        $currentTickets = $this->db
            ->select("
                t.ticket_id,
                t.title,
                owner.name AS owner_name,
                ts.status_name,
                t.created_at,
                DATEDIFF(CURDATE(), t.created_at) AS days_open,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM ticket_tasks tt
                        WHERE tt.ticket_id = t.ticket_id
                          AND tt.is_completed = 0
                    ) THEN 0
                    ELSE 1
                END AS can_resolve
            ", false)
            ->from('tickets t')
            ->join('users owner', 'owner.user_id = t.user_id', 'left')
            ->join('ticket_statuses ts', 'ts.status_id = t.status_id', 'left')
            ->where('t.assigned_engineer_id', $developerId)
            ->where('t.deleted_at IS NULL', null, false)
            ->where('YEAR(t.created_at)', (int) $year)
            ->order_by('t.status_id', 'ASC')
            ->order_by('t.created_at', 'DESC')
            ->get()
            ->result_array();

        $statusBreakdown = $this->db
            ->select('
                SUM(CASE WHEN t.status_id = 1 THEN 1 ELSE 0 END) AS open_tickets,
                SUM(CASE WHEN t.status_id = 2 THEN 1 ELSE 0 END) AS in_progress_tickets,
                SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) AS resolved_tickets,
                SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS closed_tickets
            ', false)
            ->from('tickets t')
            ->where('t.assigned_engineer_id', $developerId)
            ->where('t.deleted_at IS NULL', null, false)
            ->where('YEAR(t.created_at)', (int) $year)
            ->get()
            ->row_array();

        $departmentStatus = $this->db
            ->select('d.department_name,
                SUM(CASE WHEN t.status_id = 1 THEN 1 ELSE 0 END) AS open_tickets,
                SUM(CASE WHEN t.status_id = 2 THEN 1 ELSE 0 END) AS in_progress_tickets,
                SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) AS resolved_tickets,
                SUM(CASE WHEN t.status_id = 4 THEN 1 ELSE 0 END) AS closed_tickets
            ', false)
            ->from('tickets t')
            ->join('users u', 'u.user_id = t.assigned_engineer_id', 'left')
            ->join('departments d', 'd.department_id = u.department_id', 'left')
            ->where('YEAR(t.created_at)', (int) $year)
            ->where('t.deleted_at IS NULL', null, false)
            ->group_by('d.department_name')
            ->order_by('d.department_name', 'ASC')
            ->get()
            ->result_array();

        $monthlyVolumeRaw = $this->db
            ->select('MONTH(t.created_at) AS month_no, COUNT(*) AS total_count', false)
            ->from('tickets t')
            ->where('t.assigned_engineer_id', $developerId)
            ->where('t.deleted_at IS NULL', null, false)
            ->where('YEAR(t.created_at)', (int) $year)
            ->group_by('MONTH(t.created_at)')
            ->order_by('MONTH(t.created_at)', 'ASC')
            ->get()
            ->result_array();

        $monthlyVolume = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyVolume[] = [
                'label' => date('M', mktime(0, 0, 0, $month, 1)),
                'count' => 0,
            ];
        }

        foreach ($monthlyVolumeRaw as $row) {
            $index = (int) $row['month_no'] - 1;
            if (isset($monthlyVolume[$index])) {
                $monthlyVolume[$index]['count'] = (int) $row['total_count'];
            }
        }

        $subordinateUsers = $this->db
            ->select('u.user_id, u.name, u.email, u.role_id, r.role_name, u.company_name')
            ->from('users u')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->where('u.reports_to', $developerId)
            ->where('u.status', 'Active')
            ->order_by('u.name', 'ASC')
            ->get()
            ->result_array();

        return [
            'developer' => $developer,
            'summary' => [
                'total_tickets' => (int) ($summary['total_tickets'] ?? 0),
                'resolved_tickets' => (int) ($summary['resolved_tickets'] ?? 0),
                'pending_tickets' => (int) ($summary['pending_tickets'] ?? 0),
                'active_tickets' => (int) ($summary['active_tickets'] ?? 0),
                'accepted_tickets' => (int) $acceptedCount,
                'reviewer_assigned_tickets' => (int) $reviewerAssignedCount,
                'incoming_delegations' => (int) $incomingDelegationCount,
                'outgoing_delegations' => (int) $outgoingDelegationCount,
                'active_delegations' => (int) $activeDelegationCount,
                'leave_days' => (int) $leaveDays,
                'present_days' => (int) $presentDays,
                'avg_resolution_hours' => $avgResolutionHours,
                'invest_hours' => $investHours,
                'direct_reports' => count($subordinateUsers),
            ],
            'status_breakdown' => [
                'open_tickets' => (int) ($statusBreakdown['open_tickets'] ?? 0),
                'in_progress_tickets' => (int) ($statusBreakdown['in_progress_tickets'] ?? 0),
                'resolved_tickets' => (int) ($statusBreakdown['resolved_tickets'] ?? 0),
                'closed_tickets' => (int) ($statusBreakdown['closed_tickets'] ?? 0),
            ],
            'monthly_volume' => $monthlyVolume,
            'assignment_breakdown' => $assignmentBreakdown,
            'accepted_tickets' => $acceptedTickets,
            'assigned_tickets' => $assignedTickets,
            'current_tickets' => $currentTickets,
            'department_status' => $departmentStatus,
            'avg_resolution_hours' => $avgResolutionHours,
            'invest_hours' => $investHours,
            'subordinates' => $subordinateUsers,
        ];
    }

    public function getHierarchyTree($reviewerId)
    {
        $reviewerId = (int) $reviewerId;
        if ($reviewerId <= 0) {
            return [];
        }

        $users = $this->db
            ->select('u.user_id, u.name, u.email, u.company_name, u.reports_to, u.role_id, u.status, r.role_name, d.department_name')
            ->from('users u')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->join('departments d', 'd.department_id = u.department_id', 'left')
            ->where('u.status', 'Active')
            ->where('u.department_id', 2)
            ->order_by('u.name', 'ASC')
            ->get()
            ->result_array();

        $indexed = [];
        foreach ($users as $user) {
            $user['children'] = [];
            $indexed[(int) $user['user_id']] = $user;
        }

        foreach ($indexed as $userId => $user) {
            $parentId = (int) $user['reports_to'];
            if ($parentId > 0 && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$indexed[$userId];
            }
        }

        return isset($indexed[$reviewerId]) ? $indexed[$reviewerId] : [];
    }

    public function getHierarchyEligibleUsers($reviewerId, array $scopeUserIds = [])
    {
        $reviewerId = (int) $reviewerId;
        $excludedIds = array_unique(array_map('intval', array_merge([$reviewerId], $scopeUserIds)));

        if (empty($excludedIds)) {
            $excludedIds = [0];
        }

        return $this->db
            ->select('u.user_id, u.name, u.email, u.company_name, r.role_name')
            ->from('users u')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->where('u.department_id', 2)
            ->where('u.status', 'Active')
            ->where_not_in('u.user_id', $excludedIds)
            ->order_by('u.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function getHierarchyUserSummary($userId, $year = null)
    {
        $userId = (int) $userId;
        $year = $year ?: date('Y');

        if ($userId <= 0) {
            return null;
        }

        $user = $this->db
            ->select('u.user_id, u.name, u.email, u.company_name, u.reports_to, r.role_name, d.department_name')
            ->from('users u')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->join('departments d', 'd.department_id = u.department_id', 'left')
            ->where('u.user_id', $userId)
            ->where('u.status', 'Active')
            ->get()
            ->row_array();

        if (!$user) {
            return null;
        }

        $ticketCounts = $this->db
            ->select('COUNT(*) AS total_tickets', false)
            ->from('tickets t')
            ->where('t.assigned_engineer_id', $userId)
            ->where('t.deleted_at IS NULL', null, false)
            ->where('YEAR(t.created_at)', (int) $year)
            ->get()
            ->row_array();

        $directReports = $this->db
            ->select('u.user_id, u.name, r.role_name')
            ->from('users u')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->where('u.reports_to', $userId)
            ->where('u.status', 'Active')
            ->order_by('u.name', 'ASC')
            ->get()
            ->result_array();

        return [
            'user' => $user,
            'total_tickets' => (int) ($ticketCounts['total_tickets'] ?? 0),
            'direct_reports' => $directReports,
        ];
    }

    public function updateReportingManager($targetUserId, $reportsToUserId)
    {
        $targetUserId = (int) $targetUserId;
        $reportsToUserId = $reportsToUserId ? (int) $reportsToUserId : null;

        if ($targetUserId <= 0) {
            return false;
        }

        return $this->db
            ->where('user_id', $targetUserId)
            ->update('users', ['reports_to' => $reportsToUserId]);
    }

    public function getDescendantUserIds($userId, &$descendants = [])
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return $descendants;
        }

        $children = $this->db
            ->select('user_id')
            ->from('users')
            ->where('reports_to', $userId)
            ->where('status', 'Active')
            ->get()
            ->result_array();

        foreach ($children as $child) {
            $childId = (int) $child['user_id'];
            if ($childId > 0 && !in_array($childId, $descendants, true)) {
                $descendants[] = $childId;
                $this->getDescendantUserIds($childId, $descendants);
            }
        }

        return $descendants;
    }
}
