<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
    protected $page_css = [];
    protected $page_js = [];
    protected $current_user = [];

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper('url');

        // 🔐 Login required
        if ($this->session->userdata('is_login') !== true) {
         $this->session->set_flashdata('failed', 'Please login first');
        redirect('verify');
        }

       // ⏳ Session timeout (30 seconds)
        $timeout = 600;

        $last = $this->session->userdata('last_activity');

        if ($last && time() - $last > $timeout) {

            $this->session->set_flashdata('timeout', 'Session expired due to inactivity.');
            $this->session->sess_destroy();
            redirect('verify');
            exit;
        }

        $this->session->set_userdata('last_activity', time());

        // ✅ LOAD SIDEBAR MENUS FOR ALL PAGES
        $this->load->model('Menu_model');

        $menus = $this->Menu_model
            ->get_menus_by_role(
                $this->session->userdata('role_id')
            );

        $this->current_user = [
            'user_id' => (int) $this->session->userdata('user_id'),
            'role_id' => (int) $this->session->userdata('role_id'),
            'department_id' => (int) $this->session->userdata('department_id'),
        ];

        $this->load->vars([
            'menus' => $menus,
            'current_user_id' => $this->current_user['user_id'],
            'current_role_id' => $this->current_user['role_id'],
            'current_department_id' => $this->current_user['department_id'],
        ]);
    }

    protected function getCurrentUserId()
    {
        return (int) $this->current_user['user_id'];
    }

    protected function getCurrentRoleId()
    {
        return (int) $this->current_user['role_id'];
    }

    protected function getCurrentDepartmentId()
    {
        return (int) $this->current_user['department_id'];
    }

    protected function getCurrentUserContext()
    {
        return $this->current_user;
    }

    protected function isDepartmentHeadUser()
    {
        if (!isset($this->User_model)) {
            $this->load->model('User_model');
        }

        return $this->User_model->isDepartmentHead(
            $this->getCurrentUserId(),
            $this->getCurrentDepartmentId()
        );
    }

    protected function canViewAcrossDepartments()
    {
        return $this->getCurrentRoleId() === 2 || $this->isDepartmentHeadUser();
    }

    protected function getOwnDepartmentUserIds()
    {
        if (!isset($this->User_model)) {
            $this->load->model('User_model');
        }

        return $this->User_model->getDepartmentUserIds($this->getCurrentDepartmentId());
    }

    protected function getVisibleUserIdsByHierarchy()
    {
        if (!isset($this->User_model)) {
            $this->load->model('User_model');
        }

        return $this->User_model->getVisibleUserIdsForScope(
            $this->getCurrentUserId(),
            $this->getCurrentDepartmentId(),
            $this->getCurrentRoleId()
        );
    }

    protected function canViewDepartment($departmentId)
    {
        $departmentId = (int) $departmentId;

        return $departmentId > 0 && (
            $departmentId === $this->getCurrentDepartmentId() ||
            $this->canViewAcrossDepartments()
        );
    }

    protected function ensureRoleAccess($role_ids)
    {
        if (!in_array($this->getCurrentRoleId(), (array) $role_ids, true)) {
            show_error('Unauthorized');
        }
    }

    protected function ensureDepartmentAccess($department_ids)
    {
        if (!in_array($this->getCurrentDepartmentId(), (array) $department_ids, true)) {
            show_error('Unauthorized');
        }
    }

    protected function respondJson(array $payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    protected function isRole($role_id)
    {
        return $this->getCurrentRoleId() === (int) $role_id;
    }

    protected function isDepartment($department_id)
    {
        return $this->getCurrentDepartmentId() === (int) $department_id;
    }

    protected function setPageAssets(array $css = [], array $js = [])
    {
        $this->page_css = $css;
        $this->page_js = $js;
    }

    protected function render($view, array $data = [])
    {
        $data['page_css'] = array_values(array_unique(array_merge($this->page_css, isset($data['page_css']) && is_array($data['page_css']) ? $data['page_css'] : [])));
        $data['page_js'] = array_values(array_unique(array_merge($this->page_js, isset($data['page_js']) && is_array($data['page_js']) ? $data['page_js'] : [])));

        $this->load->view($view, $data);
    }

    public function force_logout()
{
    $this->session->sess_destroy();
    redirect('verify');
}
public function renew()
{
    if ($this->session->userdata('is_login')) {
        $this->session->set_userdata('last_activity', time());
    }

    redirect($_SERVER['HTTP_REFERER']);
}
}
