<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
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

        // Modules list
        $data['modules'] = $this->Dashboard_model
            ->get_modules_by_role(
                $this->session->userdata('role_id')
            );

        // Ticket counts
        $user_id = $this->session->userdata('user_id');

        $data['open_count']       = $this->TRS_model->get_status_count($role_id, $user_id, 1);
        $data['in_process_count'] = $this->TRS_model->get_status_count($role_id, $user_id, 2);
        $data['resolved_count']   = $this->TRS_model->get_status_count($role_id, $user_id, 3);
        $data['closed_count']     = $this->TRS_model->get_status_count($role_id, $user_id, 4);

        // Recent tickets
        $data['recent_tickets'] = $this->TRS_model->get_recent_tickets($role_id, $user_id, 1);
        $data['menus'] = $this->menu_data;

        $schedule_scope_ids = array_values(array_unique(array_merge([$user_id], $this->User_model->getAllSubordinates($user_id))));
        $selected_schedule_user_id = (int) $this->input->get('schedule_user_id');

        if ($selected_schedule_user_id <= 0 || !in_array($selected_schedule_user_id, $schedule_scope_ids, true)) {
            $selected_schedule_user_id = $user_id;
        }

        $data['schedule_scope_users'] = $this->Schedule_model->getScopeUsers($schedule_scope_ids);
        $data['selected_schedule_user_id'] = $selected_schedule_user_id;
        $data['today_tasks'] = $this->Schedule_model->getTodayTasksForScope($user_id, $schedule_scope_ids, $selected_schedule_user_id, date('Y-m-d'));
        $data['page_css'] = ['assets/dist/css/pages/schedule.css'];
        $data['page_js'] = ['assets/dist/js/pages/schedule.js'];

        $this->load->vars([
            'schedule_scope_users' => $data['schedule_scope_users'],
            'selected_schedule_user_id' => $selected_schedule_user_id,
            'today_tasks' => $data['today_tasks'],
        ]);

        $this->load->view('Pages/Dashboard/index', $data);
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
