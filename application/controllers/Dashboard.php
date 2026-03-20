<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    private function normalizeDashboardScope($scope)
    {
        $scope = strtolower(trim((string) $scope));

        return in_array($scope, ['mine', 'all', 'assigned'], true) ? $scope : 'all';
    }

    private function filterDashboardTicketsByScope(array $tickets, $scope, $user_id, $role_id, $department_id)
    {
        $scope = $this->normalizeDashboardScope($scope);
        if ($scope === 'all') {
            return array_values($tickets);
        }

        $user_id = (int) $user_id;
        $role_id = (int) $role_id;
        $department_id = (int) $department_id;

        return array_values(array_filter($tickets, function ($ticket) use ($user_id, $role_id, $department_id, $scope) {
            $assignedEngineerId = (int) ($ticket['assigned_engineer_id'] ?? 0);
            $statusId = (int) ($ticket['status_id'] ?? 0);
            $ownerId = (int) ($ticket['user_id'] ?? 0);
            $isGloballyOpen = $department_id === 2 && $statusId === 1 && $assignedEngineerId <= 0;
            $isOwnedByUser = $ownerId === $user_id;
            $isAssignedToUser = $assignedEngineerId === $user_id;

            if ($scope === 'mine') {
                return $isOwnedByUser;
            }

            if ($department_id === 2 && $scope === 'assigned') {
                if ($statusId === 1) {
                    return $isGloballyOpen;
                }

                return $isAssignedToUser;
            }

            return $isAssignedToUser;
        }));
    }

    private function getItDashboardTickets($user_id, $scope = 'all', $status = null)
    {
        $user_id = (int) $user_id;
        $scope = $this->normalizeDashboardScope($scope);
        $status = in_array((int) $status, [1, 2, 3, 4], true) ? (int) $status : null;

        $this->db
            ->select('
                t.*,
                owner.name AS user_full_name,
                owner.department_id AS owner_department_id,
                engineer.name AS assigned_engineer_name,
                dept.department_name,
                GROUP_CONCAT(tt.task_title SEPARATOR "||") AS tasks
            ')
            ->from('tickets t')
            ->join('users owner', 'owner.user_id = t.user_id', 'left')
            ->join('users engineer', 'engineer.user_id = t.assigned_engineer_id', 'left')
            ->join('departments dept', 'dept.department_id = owner.department_id', 'left')
            ->join('ticket_tasks tt', 'tt.ticket_id = t.ticket_id', 'left')
            ->where('t.deleted_at IS NULL', null, false)
            ->group_by('t.ticket_id');

        if ($scope === 'mine') {
            $this->db->where('t.user_id', $user_id);
        } elseif ($scope === 'assigned') {
            if ($status === 1) {
                // Assigned: Open stays global for the IT queue.
                $this->db->where('t.status_id', 1);
            } elseif (in_array($status, [2, 3, 4], true)) {
                $this->db->where('t.status_id', $status);
                $this->db->where('t.assigned_engineer_id', $user_id);
            } else {
                $this->db->group_start()
                    ->where('t.status_id', 1)
                    ->or_group_start()
                        ->where('t.assigned_engineer_id', $user_id)
                        ->where_in('t.status_id', [2, 3, 4])
                    ->group_end()
                ->group_end();
            }
        } elseif ($status !== null) {
            $this->db->where('t.status_id', $status);
        }

        return $this->db
            ->order_by('t.ticket_id', 'DESC')
            ->get()
            ->result_array();
    }

    private function getDashboardTickets($role_id, $department_id, $user_id, $scope = 'all', $status = null)
    {
        $department_id = (int) $department_id;
        $role_id = (int) $role_id;
        $user_id = (int) $user_id;
        $scope = $this->normalizeDashboardScope($scope);
        $status = in_array((int) $status, [1, 2, 3, 4], true) ? (int) $status : null;

        if ($department_id === 2) {
            return $this->getItDashboardTickets($user_id, $scope, $status);
        }

        $tickets = $this->TRS_model->get_visible_tickets_for_list(
            $role_id,
            $department_id,
            $user_id,
            null
        );

        $tickets = $this->filterDashboardTicketsByScope(
            $tickets,
            $scope,
            $user_id,
            $role_id,
            $department_id
        );

        if ($status === null) {
            return $tickets;
        }

        return array_values(array_filter($tickets, function ($ticket) use ($status) {
            return (int) ($ticket['status_id'] ?? 0) === $status;
        }));
    }

    private function buildDashboardCounts(array $tickets, $scope)
    {
        $counts = [
            'open_tickets' => 0,
            'in_progress_tickets' => 0,
            'resolved_tickets' => 0,
            'closed_tickets' => 0,
        ];

        foreach ($tickets as $ticket) {
            $statusId = (int) ($ticket['status_id'] ?? 0);

            if ($statusId === 1) {
                $counts['open_tickets']++;
            } elseif ($statusId === 2) {
                $counts['in_progress_tickets']++;
            } elseif ($statusId === 3) {
                $counts['resolved_tickets']++;
            } elseif ($statusId === 4) {
                $counts['closed_tickets']++;
            }
        }

        $counts['total_tickets'] = $counts['open_tickets']
            + $counts['in_progress_tickets']
            + $counts['resolved_tickets']
            + $counts['closed_tickets'];
        $counts['scope'] = strtolower(trim((string) $scope)) === 'mine' ? 'mine' : 'all';
        $counts['year'] = null;

        return $counts;
    }

    private function is_logged_in()
    {
        return (bool) $this->session->userdata('is_login');
    }

    private function can_access_task($task_id, $user_id)
    {
        if ($task_id <= 0 || !$user_id) {
            return false;
        }

        $task = $this->db
            ->select('tt.task_id')
            ->from('ticket_tasks tt')
            ->join('tickets t', 't.ticket_id = tt.ticket_id')
            ->where('tt.task_id', $task_id)
            ->where('t.deleted_at IS NULL', null, false)
            ->group_start()
            ->where('t.user_id', $user_id)
            ->or_where('t.assigned_engineer_id', $user_id)
            ->group_end()
            ->get()
            ->row_array();

        return !empty($task);
    }

    public function index()
    {
        if (!$this->is_logged_in()) {
            redirect('verify');
        }

        // Load Theme Settings
        $role_id = $this->session->userdata('role_id');

        $theme = $this->db
            ->where('role_id', $role_id)
            ->get('role_ui_settings')
            ->row_array();

        // Store in session
        $this->session->set_userdata('theme', $theme);
        $this->load->model('Menu_model');
        $this->menu_data = $this->Menu_model->get_menus_by_role($role_id);

        // Load models
        $this->load->model('Dashboard_model');
        $this->load->model('TRS_model');
        $this->load->model('Schedule_model');
        $this->load->model('User_model');
        $this->load->model('Ticket_model');

        // Modules list
        $data['modules'] = $this->Dashboard_model
            ->get_modules_by_role(
                $this->session->userdata('role_id')
            );

        // Ticket counts
        $user_id = $this->session->userdata('user_id');
        $department_id = (int) $this->session->userdata('department_id');
        $dashboard_ticket_status = (int) $this->input->get('dashboard_ticket_status');
        $dashboard_scope = $this->normalizeDashboardScope($this->input->get('dashboard_scope', true));
        if (!in_array($dashboard_ticket_status, [0, 1, 2, 3, 4], true)) {
            $dashboard_ticket_status = 0;
        }

        $all_visible_tickets = $this->getDashboardTickets(
            $role_id,
            $department_id,
            $user_id,
            $dashboard_scope,
            null
        );

        $counts = $this->buildDashboardCounts($all_visible_tickets, $dashboard_scope);
        $data['open_count'] = (int) ($counts['open_tickets'] ?? 0);
        $data['in_process_count'] = (int) ($counts['in_progress_tickets'] ?? 0);
        $data['resolved_count'] = (int) ($counts['resolved_tickets'] ?? 0);
        $data['closed_count'] = (int) ($counts['closed_tickets'] ?? 0);
        $data['total_count'] = (int) ($counts['total_tickets'] ?? 0);
        $data['dashboard_scope'] = $dashboard_scope;

        $visible_tickets = $dashboard_ticket_status > 0
            ? $this->getDashboardTickets($role_id, $department_id, $user_id, $dashboard_scope, $dashboard_ticket_status)
            : $all_visible_tickets;

        $data['recent_tickets'] = array_slice(array_map(function ($ticket) use ($department_id) {
            $ticket['can_accept'] = ((int) $department_id === 2 && (int) ($ticket['status_id'] ?? 0) === 1 && empty($ticket['assigned_engineer_id'])) ? 1 : 0;
            return $ticket;
        }, $visible_tickets), 0, 10);
        $data['dashboard_ticket_status'] = $dashboard_ticket_status;
        $data['menus'] = $this->menu_data;

        $schedule_scope_ids = $this->User_model->getVisibleUserIdsForScope($user_id, $department_id, $role_id);
        $selected_schedule_user_key = trim((string) $this->input->get('schedule_user_id'));
        $selected_schedule_user_id = strtolower($selected_schedule_user_key) === 'all' ? 0 : (int) $selected_schedule_user_key;

        if ($selected_schedule_user_id !== 0 && !in_array($selected_schedule_user_id, $schedule_scope_ids, true)) {
            $selected_schedule_user_id = 0;
        }

        $data['schedule_scope_users'] = $this->Schedule_model->getScopeUsers($schedule_scope_ids);
        $data['selected_schedule_user_id'] = $selected_schedule_user_id;
        $data['today_tasks'] = $this->Schedule_model->getTodayTasksForScope($user_id, $schedule_scope_ids, $selected_schedule_user_id, date('Y-m-d'), (int) $role_id === 2 ? null : $department_id);
        $dashboardJsPath = 'assets/dist/js/pages/dashboard.js';
        $dashboardJsVersion = @filemtime(FCPATH . $dashboardJsPath);

        $data['page_css'] = ['assets/dist/css/pages/schedule.css'];
        $data['page_js'] = [
            'assets/dist/js/pages/schedule.js',
            $dashboardJsPath . ($dashboardJsVersion ? '?v=' . $dashboardJsVersion : '')
        ];

        $this->load->vars([
            'schedule_scope_users' => $data['schedule_scope_users'],
            'selected_schedule_user_id' => $selected_schedule_user_id,
            'today_tasks' => $data['today_tasks'],
            'dashboard_ticket_status' => $dashboard_ticket_status,
            'dashboard_scope' => $dashboard_scope,
        ]);

        $this->load->view('Pages/Dashboard/index', $data);
    }

    public function ajax_ticket_counts()
    {
        if (!$this->is_logged_in()) {
            return $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        $role_id = (int) $this->session->userdata('role_id');
        $user_id = (int) $this->session->userdata('user_id');
        $department_id = (int) $this->session->userdata('department_id');
        $scope = $this->normalizeDashboardScope($this->input->get('scope', true));

        $this->load->model('TRS_model');
        $tickets = $this->getDashboardTickets(
            $role_id,
            $department_id,
            $user_id,
            $scope,
            null
        );
        $counts = $this->buildDashboardCounts($tickets, $scope);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'scope' => $scope,
                'counts' => $counts,
            ]));
    }

    public function add_task_comment()
    {
        if (!$this->is_logged_in()) {
            $this->output->set_status_header(401);
            return;
        }

        $task_id = (int) $this->input->post('task_id');
        $comment = trim((string) $this->input->post('comment', true));
        $user_id = (int) $this->session->userdata('user_id');

        if ($task_id <= 0 || $comment === '' || mb_strlen($comment) > 2000) {
            $this->output->set_status_header(422);
            return;
        }

        if (!$this->can_access_task($task_id, $user_id)) {
            $this->output->set_status_header(403);
            return;
        }

        $this->db->insert('task_comments', [
            'task_id' => $task_id,
            'user_id' => $user_id,
            'comment' => $comment
        ]);
    }

    public function load_task_comments()
    {
        if (!$this->is_logged_in()) {
            $this->output->set_status_header(401);
            return;
        }

        $task_id = (int) $this->input->post('task_id');
        $user_id = (int) $this->session->userdata('user_id');

        if ($task_id <= 0) {
            $this->output->set_status_header(422);
            return;
        }

        if (!$this->can_access_task($task_id, $user_id)) {
            $this->output->set_status_header(403);
            return;
        }

        $comments = $this->db
            ->select('task_comments.*, users.name as user_name')
            ->from('task_comments')
            ->join('users', 'users.user_id = task_comments.user_id')
            ->where('task_comments.task_id', $task_id)
            ->order_by('task_comments.comment_id', 'ASC')
            ->get()
            ->result();

        foreach ($comments as $c) {
            $side = ((int) $c->user_id === $user_id) ? 'chat-right' : 'chat-left';

            echo '<div class="chat-message ' . $side . '">
                <strong>' . html_escape($c->user_name) . ':</strong>
                ' . nl2br(html_escape($c->comment)) . '
              </div>';
        }
    }
}
