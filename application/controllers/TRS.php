<?php

class TRS extends My_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        if ($this->session->userdata('is_login') != true) {
            redirect('Auth/index');
        }
    }

    private function sendSystemEmail($to, $subject, $message)
    {
        if (empty($to)) {
            return false;
        }

        $this->email->clear(true);
        $this->email->from('patelniket972@gmail.com', 'TRS');
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        return (bool) $this->email->send();
    }

    private function notifyUsers(array $users, $message, $ticket_id, $notification_type = 'ticket')
    {
        $this->load->model('TRS_model');
        $seen = [];

        foreach ($users as $user) {
            if (empty($user->user_id) || isset($seen[$user->user_id])) {
                continue;
            }

            $seen[$user->user_id] = true;

            $this->TRS_model->create_notification([
                'ticket_id' => (int) $ticket_id,
                'schedule_task_id' => null,
                'notification_type' => $notification_type,
                'task_id' => null,
                'sender_id' => $this->session->userdata('user_id') ?: null,
                'receiver_id' => (int) $user->user_id,
                'message' => $message,
                'is_read' => 0,
            ]);
        }
    }

    private function notifyTicketCreated($ticket_id)
    {
        $this->load->model('TRS_model');
        $ticket = $this->TRS_model->get_ticket_owner($ticket_id);

        if (!$ticket) {
            return;
        }

        $it_users = $this->TRS_model->get_it_team_users();
        $message = 'New ticket "' . $ticket->title . '" has been raised and is waiting for IT action.';

        $this->notifyUsers($it_users, $message, $ticket_id);

        foreach ($it_users as $user) {
            $this->sendSystemEmail(
                $user->email,
                'New Ticket Raised: ' . $ticket->title,
                $message . PHP_EOL . 'Owner: ' . $ticket->owner_name
            );
        }
    }

    private function notifyTicketOwnerAssignment($ticket_id, $engineer_id, $action_label)
    {
        $this->load->model('TRS_model');
        $ticket = $this->TRS_model->get_ticket_owner($ticket_id);
        $engineer = $this->TRS_model->get_engineer_user($engineer_id);

        if (!$ticket || !$engineer) {
            return;
        }

        $message = 'Your ticket "' . $ticket->title . '" has been ' . $action_label . ' by ' . $engineer->name . '.';

        $this->TRS_model->create_notification([
            'ticket_id' => (int) $ticket_id,
            'schedule_task_id' => null,
            'notification_type' => 'ticket',
            'task_id' => null,
            'sender_id' => (int) $engineer->user_id,
            'receiver_id' => (int) $ticket->owner_user_id,
            'message' => $message,
            'is_read' => 0,
        ]);

        $this->sendSystemEmail(
            $ticket->owner_email,
            'Ticket Update: ' . $ticket->title,
            $message
        );
    }

    protected function getCurrentUserContext()
    {
        return [
            'role_id' => (int) $this->session->userdata('role_id'),
            'department_id' => (int) $this->session->userdata('department_id'),
            'user_id' => (int) $this->session->userdata('user_id'),
        ];
    }

    protected function ensureRoleAccess($roles)
    {
        $roles = (array) $roles;

        if (!in_array((int) $this->session->userdata('role_id'), $roles, true)) {
            show_error('Unauthorized');
        }
    }

    protected function ensureDepartmentAccess($departments)
    {
        $departments = (array) $departments;

        if (!in_array((int) $this->session->userdata('department_id'), $departments, true)) {
            show_error('Unauthorized');
        }
    }

    protected function respondJson(array $payload)
    {
        echo json_encode($payload);
    }

    private function getTicketClosedReferenceTimestamp($ticket)
    {
        if (!is_array($ticket) && !is_object($ticket)) {
            return false;
        }

        $statusId = (int) (is_array($ticket) ? ($ticket['status_id'] ?? 0) : ($ticket->status_id ?? 0));
        if ($statusId !== 4) {
            return false;
        }

        $candidates = is_array($ticket)
            ? [
                $ticket['closed_at'] ?? null,
                $ticket['updated_at'] ?? null,
                $ticket['created_at'] ?? null,
            ]
            : [
                $ticket->closed_at ?? null,
                $ticket->updated_at ?? null,
                $ticket->created_at ?? null,
            ];

        foreach ($candidates as $candidate) {
            if (empty($candidate)) {
                continue;
            }

            $timestamp = strtotime((string) $candidate);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return false;
    }

    private function isTicketWithinClosedVisibilityWindow($ticket, $days = 7)
    {
        $closedTimestamp = $this->getTicketClosedReferenceTimestamp($ticket);
        if ($closedTimestamp === false) {
            return false;
        }

        return $closedTimestamp >= strtotime('-' . (int) $days . ' days');
    }

    private function getTicketClosedExpiryTimestamp($ticket, $days = 7)
    {
        $closedTimestamp = $this->getTicketClosedReferenceTimestamp($ticket);
        if ($closedTimestamp === false) {
            return false;
        }

        return $closedTimestamp + ((int) $days * 24 * 60 * 60);
    }

    private function canEditTicket(array $ticket, $ticket_id, $department_id)
    {
        if ((int) $department_id === 2) {
            return true;
        }

        if (in_array((int) $ticket['status_id'], [3, 4], true)) {
            return false;
        }

        if ((int) $ticket['status_id'] === 1 && !empty($ticket['assigned_engineer_id'])) {
            return false;
        }

        if ((int) $ticket['status_id'] !== 2) {
            return true;
        }

        $reopen = $this->session->userdata('reopen_edit_allowed');

        return is_array($reopen)
            && isset($reopen[$ticket_id])
            && $reopen[$ticket_id] === true;
    }

    private function syncTicketTasks($ticket_id, $tasks, $created_by)
    {
        if (empty($tasks) || !is_array($tasks)) {
            return;
        }

        $this->db->where('ticket_id', $ticket_id)->delete('ticket_tasks');

        $position = 1;

        foreach ($tasks as $task) {
            $task = trim($task);

            if ($task === '') {
                continue;
            }

            $this->db->insert('ticket_tasks', [
                'ticket_id' => $ticket_id,
                'task_title' => $task,
                'is_completed' => 0,
                'position' => $position,
                'created_by' => $created_by
            ]);

            $position++;
        }
    }

    private function buildUserPayload($departmentColumn, $departmentPostKey, $includePassword = true)
    {
        $data = [
            'user_name' => $this->input->post('user_name'),
            'name' => $this->input->post('name'),
            'email' => $this->input->post('email'),
            'company_name' => $this->input->post('company_name'),
            'phone' => $this->input->post('phone'),
            $departmentColumn => $this->input->post($departmentPostKey),
            'role_id' => $this->input->post('role_id'),
            'status' => 'Active'
        ];

        if ($includePassword) {
            $password = $this->input->post('password');
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $password = trim($this->input->post('password'));

            if ($password !== '') {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        return $data;
    }

    private function setUserOldInput(array $payload)
    {
        $this->session->set_flashdata('old_user', $payload);
    }

    private function getUserManagementOptions($excludeUserId = null)
    {
        $this->load->model('User_model');

        return [
            'roles' => $this->User_model->get_all_roles(),
            'departments' => $this->User_model->get_all_departments(),
            'hierarchy_users' => $this->User_model->get_hierarchy_candidates($excludeUserId),
        ];
    }

    private function generateAvailableUsername($seed, $excludeUserId = null)
    {
        $this->load->model('User_model');

        $base = preg_replace('/[^a-z0-9]+/i', '.', strtolower(trim((string) $seed)));
        $base = trim((string) $base, '.');

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $suffix = 1;

        while ($this->User_model->username_exists_except($username, $excludeUserId)) {
            $suffix++;
            $username = $base . $suffix;
        }

        return $username;
    }

    private function buildManagedUserPayload($userId = null)
    {
        $this->load->model('User_model');

        $name = trim((string) $this->input->post('name', true));
        $email = strtolower(trim((string) $this->input->post('email', true)));
        $phone = trim((string) $this->input->post('phone', true));
        $companyName = trim((string) $this->input->post('company_name', true));
        $departmentId = $this->normalizeNullableInt($this->input->post('department_id'));
        $roleId = $this->normalizeNullableInt($this->input->post('role_id'));
        $worksUnderUserId = $this->normalizeNullableInt($this->input->post('works_under_user_id'));
        $userName = trim((string) $this->input->post('user_name', true));
        $password = trim((string) $this->input->post('password'));
        $accountMode = trim((string) $this->input->post('account_mode', true)) === 'active' ? 'active' : 'invite';
        $requestedStatus = trim((string) $this->input->post('status', true));

        $oldInput = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company_name' => $companyName,
            'department_id' => $departmentId,
            'role_id' => $roleId,
            'works_under_user_id' => $worksUnderUserId,
            'user_name' => $userName,
            'account_mode' => $accountMode,
            'status' => $requestedStatus,
        ];

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'A valid name and email are required.', 'old' => $oldInput];
        }

        if ($departmentId === null || $roleId === null) {
            return ['success' => false, 'message' => 'Department and role are required.', 'old' => $oldInput];
        }

        $roleExists = false;
        foreach ($this->User_model->get_all_roles() as $role) {
            if ((int) $role['role_id'] === $roleId) {
                $roleExists = true;
                break;
            }
        }

        if (!$roleExists) {
            return ['success' => false, 'message' => 'Selected role is invalid.', 'old' => $oldInput];
        }

        $departmentExists = false;
        foreach ($this->User_model->get_all_departments() as $department) {
            if ((int) $department['department_id'] === $departmentId) {
                $departmentExists = true;
                break;
            }
        }

        if (!$departmentExists) {
            return ['success' => false, 'message' => 'Selected department is invalid.', 'old' => $oldInput];
        }

        if ($accountMode === 'active' && $userName === '') {
            return ['success' => false, 'message' => 'Username is required for active accounts.', 'old' => $oldInput];
        }

        if ($accountMode === 'invite' && $userName === '') {
            $userName = $this->generateAvailableUsername($name !== '' ? $name : $email, $userId);
            $oldInput['user_name'] = $userName;
        }

        if ($this->User_model->email_exists_except($email, $userId)) {
            return ['success' => false, 'message' => 'Email already exists.', 'old' => $oldInput];
        }

        if ($this->User_model->username_exists_except($userName, $userId)) {
            return ['success' => false, 'message' => 'Username already exists.', 'old' => $oldInput];
        }

        if ($worksUnderUserId !== null) {
            if ($userId !== null && (int) $worksUnderUserId === (int) $userId) {
                return ['success' => false, 'message' => 'A user cannot work under themselves.', 'old' => $oldInput];
            }

            $managerUser = $this->User_model->get_user_with_meta($worksUnderUserId);
            if (!$managerUser) {
                return ['success' => false, 'message' => 'Selected hierarchy owner is invalid.', 'old' => $oldInput];
            }
        }

        $department = $this->db
            ->select('department_head_id')
            ->from('departments')
            ->where('department_id', $departmentId)
            ->get()
            ->row();

        $reportsTo = $worksUnderUserId !== null
            ? (int) $worksUnderUserId
            : (int) ($department->department_head_id ?? 0);

        if ($reportsTo <= 0) {
            $reportsTo = null;
        }

        $status = $accountMode === 'invite'
            ? 'Inactive'
            : ($requestedStatus === 'Inactive' ? 'Inactive' : 'Active');

        $data = [
            'name' => $name,
            'user_name' => $userName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : '0000000000',
            'company_name' => $companyName !== '' ? $companyName : 'TRS',
            'department_id' => $departmentId,
            'role_id' => $roleId,
            'reports_to' => $reportsTo,
            'status' => $status,
            'is_registered' => $accountMode === 'active' ? 1 : 0,
        ];

        if ($userId === null) {
            $data['created_by'] = (int) $this->session->userdata('user_id');
        }

        if ($accountMode === 'active') {
            if ($userId === null && $password === '') {
                return ['success' => false, 'message' => 'Password is required for active accounts.', 'old' => $oldInput];
            }

            if ($password !== '') {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
        } else {
            $data['status'] = 'Inactive';
            $data['is_registered'] = 0;

            if ($userId === null) {
                $data['password'] = null;
            }
        }

        return ['success' => true, 'data' => $data, 'old' => $oldInput];
    }

    private function normalizeNullableInt($value)
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function getSubmittedTasks()
    {
        $tasks = $this->input->post('tasks');

        return is_array($tasks) ? $tasks : [];
    }

    private function buildTicketPayload(array $overrides = [])
    {
        $payload = [
            'user_id' => (int) $this->session->userdata('user_id'),
            'title' => trim((string) $this->input->post('title', true)),
            'description' => trim((string) $this->input->post('description', true)),
            'status_id' => 1,
            'assignment_due_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];

        $asset_id = $this->normalizeNullableInt($this->input->post('asset_id'));
        $category_id = $this->normalizeNullableInt($this->input->post('category_id'));
        $priority_id = $this->normalizeNullableInt($this->input->post('priority_id'));

        if ($asset_id !== null) {
            $payload['asset_id'] = $asset_id;
        }

        if ($category_id !== null) {
            $payload['category_id'] = $category_id;
        }

        if ($priority_id !== null) {
            $payload['priority_id'] = $priority_id;
        }

        return array_merge($payload, $overrides);
    }

    private function validateTicketPayload(array $payload)
    {
        if (trim((string) ($payload['title'] ?? '')) === '') {
            return 'Ticket title is required.';
        }

        if (trim((string) ($payload['description'] ?? '')) === '') {
            return 'Ticket description is required.';
        }

        return '';
    }

    private function createTicketRecord(array $payload, array $tasks)
    {
        $this->load->model('TRS_model');

        $ticket_id = $this->TRS_model->insert_ticket($payload);

        if ($ticket_id) {
            $this->syncTicketTasks(
                $ticket_id,
                $tasks,
                (int) $this->session->userdata('user_id')
            );
        }

        return (int) $ticket_id;
    }

    private function getAssetRouteKey($assetOrCode)
    {
        if (is_object($assetOrCode)) {
            $serialNumber = trim((string) ($assetOrCode->serial_number ?? ''));
            if ($serialNumber !== '') {
                return $serialNumber;
            }

            return trim((string) ($assetOrCode->qr_code ?? ''));
        }

        return trim(rawurldecode((string) $assetOrCode));
    }

    private function getQrTicketFormRoute($assetOrCode, $departmentId = null)
    {
        return 'qr-ticket/form/' . rawurlencode($this->getAssetRouteKey($assetOrCode));
    }

    private function renderTicketCreatePage($asset = null)
    {
        $data['asset'] = $asset;
        $data['default_title'] = $asset
            ? trim('QR Scan - ' . $asset->asset_name . ' ' . (!empty($asset->serial_number) ? '(' . $asset->serial_number . ')' : ''))
            : '';

        $this->load->view('Users/Add_ticket', $data);
    }

    private function resolveQrAssetOrRedirect($routeKey)
    {
        $this->load->model('Asset_model');

        $routeKey = trim(rawurldecode((string) $routeKey));

        if ($routeKey === '') {
            $this->session->set_flashdata('failed', 'QR code is missing.');
            redirect('dashboard');
        }

        $departmentId = (int) $this->input->get('department_id', true);
        $asset = $this->Asset_model->get_by_qr_code($routeKey);

        if (!$asset) {
            $asset = $this->Asset_model->get_by_serial_number($routeKey, $departmentId > 0 ? $departmentId : null);
        }

        if (!$asset) {
            $this->session->set_flashdata('failed', 'This QR code or serial number is not linked to any asset yet.');
            redirect('dashboard');
        }

        return $asset;
    }

    /* ================= DASHBOARD ================= */

    public function dashboard()
    {
        $this->load->model('TRS_model');

        $user = $this->getCurrentUserContext();
        $dep_id= $this->session->userdata('department_id');
        $user_id = $this->session->userdata('user_id');

        $uid = ($dep_id == 2) ? $user_id : null;

        $data['open_count']       = $this->TRS_model->count_tickets_by_status(1, $uid);
        $data['in_proess_count'] = $this->TRS_model->count_tickets_by_status(2, $uid);
        $data['resolved_count']   = $this->TRS_model->count_tickets_by_status(3, $uid);
        $data['closed_count']     = $this->TRS_model->count_tickets_by_status(4, $uid);
        $data['total_count']      = $data['open_count'] + $data['in_proess_count'] + $data['resolved_count'] + $data['closed_count'];



        if ($dep_id != 2) {
            $data['recent_tickets'] = $this->TRS_model->get_user_recent_tickets($user_id, 5);
        } else {
            $data['recent_tickets'] = $this->TRS_model->get_all_recent_tickets(5);
        }

        $this->load->view('Pages/Dashboard/index', $data);
    }

    public function confirm_ticket($ticket_id, $answer)
    {
        $this->load->model('TRS_model');
        $this->load->library('session');

        $ticket = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket || $ticket['user_id'] != $this->session->userdata('user_id')) {
            show_error('Unauthorized');
        }

        if ($answer === 'yes') {

            $this->TRS_model->update_ticket($ticket_id, [
                'status_id' => $this->TRS_model->get_status_id('closed'),
                'closed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->session->set_flashdata('success', 'Ticket closed successfully');
        } else {

            $this->TRS_model->update_ticket($ticket_id, [
                'status_id' => $this->TRS_model->get_status_id('in_progress'),
                'closed_at' => null
            ]);


            // 🔥 SET ONE-TIME EDIT FLAG
            $reopen = $this->session->userdata('reopen_edit_allowed');
            if (!is_array($reopen)) {
                $reopen = [];
            }

            $reopen[$ticket_id] = true;
            $this->session->set_userdata('reopen_edit_allowed', $reopen);

            $this->session->set_flashdata('error', 'Issue not solved. You can edit this ticket only once.');
        }

        // 🔥 MUST REDIRECT — OTHERWISE BLANK PAGE
        redirect('TRS/list');
    }



    /* ================= LIST ================= */

   public function list($status = null)
{
    if (!$this->session->userdata('is_login')) {
        redirect('verify');
    }

    $this->load->model('Menu_model');
    $data['menus'] = $this->Menu_model
            ->get_menus_by_role($this->session->userdata('role_id'));

    $this->load->model('TRS_model');
    $user = $this->getCurrentUserContext();
    $ticket_scope = (string) $this->input->get('ticket_scope', true);
    $allowed_scopes = ['all_tickets', 'my_tickets', 'my_accepted'];

    if (!in_array($ticket_scope, $allowed_scopes, true)) {
        $ticket_scope = 'all_tickets';
    }

    $allowed_status = [1, 2, 3, 4];
    $status_query = (int) $this->input->get('status_id');

    if ($status_query > 0) {
        $status = $status_query;
    }

    if ($status != null && !in_array((int)$status, $allowed_status, true)) {
        show_error('Invalid status');
    }

    $status = $status != null ? (int)$status : null;
    $data['val'] = $this->TRS_model->get_visible_tickets_for_list(
        $user['role_id'],
        $user['department_id'],
        $user['user_id'],
        $status,
        $ticket_scope
    );

    $data['current_status'] = $status;
    $data['status_filter'] = $status;
    $data['ticket_scope'] = $ticket_scope;

    $this->load->view('Users/List', $data);
}
    /* ================= ADD ================= */

    public function see()
    {
        $qr_code = trim((string) $this->input->get('qr_code', true));
        $asset = null;

        if ($qr_code !== '') {
            $asset = $this->resolveQrAssetOrRedirect($qr_code);
        }

        $this->renderTicketCreatePage($asset);
    }

    public function qr_ticket_form($qr_code = '')
    {
        $asset = $this->resolveQrAssetOrRedirect($qr_code);
        $this->renderTicketCreatePage($asset);
    }

    public function add()
    {
        $payload = $this->buildTicketPayload();
        $validation_error = $this->validateTicketPayload($payload);

        if ($validation_error !== '') {
            $this->session->set_flashdata('failed', $validation_error);
            redirect('TRS/list');
        }

        $ticket_id = $this->createTicketRecord($payload, $this->getSubmittedTasks());

        if ($ticket_id) {
            $this->notifyTicketCreated($ticket_id);
            $this->session->set_flashdata('success', 'Ticket added successfully');
        } else {
            $this->session->set_flashdata('failed', 'Unable to move forward');
        }

        redirect('TRS/list');
        return;

        $this->load->model('TRS_model');

        $ticket_id = $this->TRS_model->insert_ticket([
            'user_id' => $this->session->userdata('user_id'),
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'status_id' => 1,
            'assignment_due_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ]);

        $tasks = $this->input->post('tasks');

        if (!empty($tasks)) {
            $this->TRS_model->add_insert_tasks($ticket_id, $tasks);
        }

        if ($ticket_id) {
            $this->notifyTicketCreated($ticket_id);

            // ✅ flashdata for page reload
            $this->session->set_flashdata('success', 'Ticket added successfully');

            // ✅ ajax response
            //echo json_encode(['status' => true]);
        } else {
            $this->session->set_flashdata('failed', 'Unable to move forward');
            //echo json_encode(['status' => false]);
        }

        redirect('TRS/list');
    }


    public function add_qr_ticket()
    {
        $qr_code = trim((string) $this->input->post('qr_code', true));
        $asset = $this->resolveQrAssetOrRedirect($qr_code);
        $payload = $this->buildTicketPayload([
            'asset_id' => (int) $asset->asset_id
        ]);
        $validation_error = $this->validateTicketPayload($payload);

        if ($validation_error !== '') {
            $this->session->set_flashdata('failed', $validation_error);
            redirect($this->getQrTicketFormRoute($asset, (int) ($asset->department_id ?? $asset->effective_department_id ?? 0)));
        }

        $ticket_id = $this->createTicketRecord($payload, $this->getSubmittedTasks());

        if ($ticket_id) {
            $this->notifyTicketCreated($ticket_id);
            $this->session->set_flashdata('success', 'QR scanned ticket raised successfully.');
            redirect('TRS/list');
        }

        $this->session->set_flashdata('failed', 'Unable to move forward');
        redirect($this->getQrTicketFormRoute($asset, (int) ($asset->department_id ?? $asset->effective_department_id ?? 0)));
    }

    public function add_ajax()
    {
        $payload = $this->buildTicketPayload();
        $validation_error = $this->validateTicketPayload($payload);

        if ($validation_error !== '') {
            return $this->respondJson([
                'status' => false,
                'msg' => $validation_error
            ]);
        }

        $ticket_id = $this->createTicketRecord($payload, $this->getSubmittedTasks());

        if (!$ticket_id) {
            return $this->respondJson([
                'status' => false,
                'msg' => 'Unable to move forward'
            ]);
        }

        $this->session->set_flashdata('success', 'Ticket added successfully');
        $this->notifyTicketCreated($ticket_id);

        $this->respondJson(['status' => true]);
        return;

        $this->load->model('TRS_model');

        // 1️⃣ Insert Ticket
        $this->db->insert('tickets', [
            'user_id'     => $this->session->userdata('user_id'),
            'title'       => $this->input->post('title', true),
            'description' => $this->input->post('description', true),
            'status_id'   => 1,
            'assignment_due_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ]);

        $ticket_id = $this->db->insert_id();

        $tasks = $this->input->post('tasks');

        if (!empty($tasks)) {
            foreach ($tasks as $position => $task) {
                if (!empty($task)) {
                    $this->db->insert('ticket_tasks', [
                        'ticket_id'   => $ticket_id,
                        'task_title'  => $task,
                        'is_completed' => 0,
                        'position'    => $position,
                        'created_by'  => $this->session->userdata('user_id')
                    ]);
                }
            }
        }

        // ✅ Flash message for redirect page
        $this->session->set_flashdata('success', 'Ticket added successfully');
        $this->notifyTicketCreated($ticket_id);

        // ✅ SINGLE JSON response
        echo json_encode(['status' => true]);
    }



    /* ================= ACCEPT / LEAVE ================= */

    public function accept_ticket($ticket_id)
    {
        $this->ensureDepartmentAccess([2]);
        $this->load->model('TRS_model');
        $user = $this->getCurrentUserContext();

        // 1️⃣ Update ticket
        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => $user['user_id'],
            'status_id' => 2,
            'accepted_at' => date('Y-m-d H:i:s')
        ]);

        // 2️⃣ Insert history (ACCEPT)
        $this->TRS_model->insert_assignment_history([
            'ticket_id'   => $ticket_id,
            'action_type' => 'accept',
            'assigned_to' => $user['user_id'],
            'assigned_by' => $user['user_id'],
            'remarks'     => 'Ticket accepted by developer',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $this->notifyTicketOwnerAssignment($ticket_id, $user['user_id'], 'accepted');

        redirect('TRS/my_tickets');
    }


    public function leave_ticket($ticket_id)
    {
        $this->ensureDepartmentAccess([2]);

        $this->load->model('TRS_model');

        $dev_id = $this->session->userdata('user_id');  // 🔥 MISSING LINE

        // 1️⃣ Update main ticket
        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => null,
            'status_id' => 1
        ]);

        // 🔥 INSERT HISTORY (ONLY ONCE)
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'    => $ticket_id,
            'action_type' => 'leave',
            'assigned_to' => null,          // ab kisi ke paas nahi
            'assigned_by' => $this->session->userdata('user_id'), // 🔥 IMPORTANT
            'remarks'     => 'Developer left the ticket',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        redirect('TRS/list');
    }


    /* ================= EDIT ================= */


    public function edit($ticket_id)
    {
        $this->load->model('TRS_model');

        $user = $this->getCurrentUserContext();
         $dep_id = $this->session->userdata('department_id');  // 🔥 MISSING LINE

        $ticket  = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket) show_error('Ticket not found');

        if (!$this->canEditTicket($ticket, $ticket_id, (int) $this->session->userdata('department_id'))) {
            show_error('Unauthorized');
        }

        // ---- USER RULES ----
        if ($dep_id != 2) {

            // 🚫 Never allow edit if CLOSED or RESOLVED
            if (in_array($ticket['status_id'], [4, 3])) {
                show_error('Unauthorized');
            }

            // 🚫 Open but already assigned (normal flow)
            if ($ticket['status_id'] == 1 && $ticket['assigned_engineer_id'] != null) {
                show_error('Unauthorized');
            }

            // 🔥 IN_PROGRESS case (this is the important part)
            if ($ticket['status_id'] == 2) {

                $reopen = $this->session->userdata('reopen_edit_allowed');

                // ✅ Allow ONLY if this ticket was reopened and flag exists
                if (is_array($reopen) && isset($reopen[$ticket_id]) && $reopen[$ticket_id] === true) {
                    // allowed ONCE ✅
                } else {
                    // 🚫 Normal in_progress OR already edited once
                    show_error('Unauthorized');
                }
            }
        }

        // IT Head → developer list
        if ($role_id == 2) {
            $data['developers'] = $this->TRS_model->get_all_developers();
        }

        $data['value'] = $ticket;
        $data['assign_mode'] = $this->input->get('assign');

        $this->load->view('Users/Edit', $data);
    }

    public function edit_ajax()
    {
        $this->load->model('TRS_model');
        $ticket_id = $this->input->post('ticket_id');

        $role_id = $this->session->userdata('role_id');
        $dept_id = $this->session->userdata('department_id');  // 🔥 MISSING LINE
        $ticket  = $this->TRS_model->get_data_by_id($ticket_id);
        $developers = $this->TRS_model->get_all_developers();
        $tasks = $this->TRS_model->get_tasks_by_ticket($ticket_id); // 🔥 NEW

        if (!$ticket) {
            $this->respondJson(['status' => false, 'msg' => 'Ticket not found']);
            return;
        }

        if (!$this->canEditTicket($ticket, $ticket_id, (int) $this->session->userdata('department_id'))) {
            $this->respondJson(['status' => false, 'msg' => 'Unauthorized']);
            return;
        }

        /* USER RULES (unchanged) */

        if ($dept_id != 2) {

            if (in_array($ticket['status_id'], [4, 3])) {
                echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
                exit;
            }

            if ($ticket['status_id'] == 1 && $ticket['assigned_engineer_id'] != null) {
                echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
                exit;
            }

            if ($ticket['status_id'] == 2) {
                $reopen = $this->session->userdata('reopen_edit_allowed');

                if (!is_array($reopen) || !isset($reopen[$ticket_id]) || $reopen[$ticket_id] != true) {
                    echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
                    exit;
                }
            }
        }

        echo json_encode([
            'status'     => true,
            'data'       => $ticket,
            'developers' => $developers,
            'tasks'      => $tasks  // 🔥 SEND TASKS
        ]);
        return;
    }

    public function update_ajax()
    {
        $ticket_id = $this->input->post('ticket_id');
        $user = $this->getCurrentUserContext();
         $dept_id = $this->session->userdata('department_id');  // 🔥 MISSING LINE
        if (!$ticket_id) {
            $this->respondJson([
                'status' => false,
                'msg' => 'Invalid ticket'
            ]);
            return;
        }

        $data = [];

        // USER
        if ($user['role_id'] == 1) {
            $data['title']       = $this->input->post('title', true);
            $data['description'] = $this->input->post('description', true);
        }

        // DEVELOPER
        if ($user['role_id'] == 1 && $this->session->userdata('department_id') == 2 ) {
            $data['status_id'] = $this->input->post('status_id');
        }

        // ADMIN
        if ($user['role_id'] == 2) {
            $data['assigned_engineer_id'] = $this->input->post('assigned_engineer_id');
            $data['status_id']            = $this->input->post('status_id');
        }

        $this->load->model('TRS_model');

        // 🔥 SAFE UPDATE (only if data exists)
        if (!empty($data)) {
            $this->TRS_model->update_ticket($ticket_id, $data);
        }

        // 🔥 TASK UPDATE (only for user)
        if ($user['role_id'] == 1 && $this->session->userdata('department_id') != 2) {
            $this->syncTicketTasks($ticket_id, $this->input->post('tasks'), $user['user_id']);
        }

        $this->respondJson(['status' => true]);
    }

    public function do_assign_ajax()
    {
        // Only Admin (3) allow
        if ($this->session->userdata('role_id') != 2) {
            show_error('Unauthorized');
        }

        $ticket_id = $this->input->post('ticket_id');
        $dev_id    = $this->input->post('assigned_engineer_id');
        $reason    = trim($this->input->post('reason'));

        if (!$ticket_id || !$dev_id) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Required data missing'
            ]);
            return;
        }

        // 🔍 Check ticket exists
        $ticket = $this->db->get_where('tickets', [
            'ticket_id' => $ticket_id
        ])->row_array();

        if (!$ticket) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Ticket not found'
            ]);
            return;
        }

        // ❌ Already assigned check
        if (!empty($ticket['assigned_engineer_id'])) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Ticket already assigned'
            ]);
            return;
        }

        // ✅ Update ticket (status_id = 2)
        $this->db->where('ticket_id', $ticket_id);
        $this->db->update('tickets', [
            'assigned_engineer_id' => $dev_id, 
            'status_id'            => 2
        ]);

        // ✅ Insert history
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'   => $ticket_id,
            'action_type' => 'assign',
            'assigned_to' => $dev_id,
            'assigned_by' => $user['user_id'],
            'remarks'     => $reason,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $this->notifyTicketOwnerAssignment($ticket_id, $dev_id, 'assigned');

        echo json_encode([
            'status' => true,
            'msg'    => 'Assigned successfully'
        ]);
    }

    public function update($ticket_id)
    {
        $this->load->model('TRS_model');

        $role_id = $this->session->userdata('role_id');
        $ticket  = $this->TRS_model->get_data_by_id($ticket_id);
        $dept_id = $this->session->userdata('department_id');  // 🔥 MISSING LINE


        if (!$ticket) show_error('Ticket not found');

        $data = []; // start empty

        /* ================= USER PERMISSION CHECK ================= */
        if ($role_id == 1 && $dept_id != 2) {

            // 🚫 Never allow update if CLOSED or RESOLVED
            if (in_array($ticket['status_id'], [4, 3])) {
                show_error('Unauthorized');
            }

            // 🚫 If OPEN but already assigned → block
            if ($ticket['status_id'] == 1 && $ticket['assigned_engineer_id'] != null) {
                show_error('Unauthorized');
            }

            // 🔥 If IN_PROGRESS (reopened case) → allow ONLY ONCE using session flag
            if ($ticket['status_id'] == 2) {
                $reopen = $this->session->userdata('reopen_edit_allowed');

                if (!isset($reopen[$ticket_id]) || $reopen[$ticket_id] != true) {
                    show_error('Unauthorized');
                }
            }

            // ✅ USER can update only title & description
            $data = [
                'title'       => $this->input->post('title'),
                'description' => $this->input->post('description')
            ];
        }

        /* ================= DEVELOPER ================= */ elseif ($role_id == 1 && $dept_id == 2) {
            $data = [
                'status' => $this->input->post('status_id')
            ];
        }

        /* ================= IT HEAD ================= */ elseif ($role_id == 2) {   // IT Head / Admin

            $posted_status = $this->input->post('status_id');
            $new_assigned  = $this->input->post('assigned_engineer_id');

            $data = [
                'status' => $posted_status
            ];

            // old assigned engineer
            $old_assigned = $ticket['assigned_engineer_id'];

            $assignmentChanged = false;

            // Assign developer (optional)
            if (!empty($new_assigned)) {

                $data['assigned_engineer_id'] = $new_assigned;

                // status logic
                if (!in_array($posted_status, [3, 4])) {
                    $data['status'] = 2;
                }

                // 🔥 check if assignment really changed
                if ($old_assigned != $new_assigned) {
                    $assignmentChanged = true;
                }
            }

            /* ================= UPDATE ================= */
            if (!empty($data)) {
                $this->TRS_model->update_ticket($ticket_id, $data);
            }

            /* ================= HISTORY LOG (ONLY WHEN ASSIGNMENT CHANGES) ================= */
            if ($assignmentChanged) {

                $this->db->insert('ticket_assignment_history', [
                    'ticket_id'    => $ticket_id,
                    'assigned_to' => $new_assigned,                          // kisko diya
                    'assigned_by' => $this->session->userdata('user_id'),   // admin
                    'remarks'     => 'Ticket assigned by admin',
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
            }
        }


        /* ================= UPDATE ================= */
        if (!empty($data)) {
            $this->TRS_model->update_ticket($ticket_id, $data);
        }

        /* ================= REMOVE ONE-TIME FLAG AFTER USER EDIT ================= */
        if ($role_id == 1 && $ticket['status'] == 2) {
            $reopen = $this->session->userdata('reopen_edit_allowed');

            if (isset($reopen[$ticket_id])) {
                unset($reopen[$ticket_id]);
                $this->session->set_userdata('reopen_edit_allowed', $reopen);
            }
        }

        redirect('TRS/list');
    }

    
    /* ================= DELETE ================= */

    public function delete($ticket_id)
    {
        $this->load->model('TRS_model');
        $this->TRS_model->delete_ticket($ticket_id);
        redirect('TRS/list');
    }

    /* ================= DEV MY TICKETS ================= */

    public function my_tickets()
    {
        if ($this->session->userdata('department_id') != 2) {
            $this->session->set_flashdata('failed', '🚫Unauthorized🚫');
            redirect('Dashboard');
        }

        $this->load->model('TRS_model');

        $data['val'] = $this->TRS_model
            ->get_my_accepted_tickets($this->session->userdata('user_id'));
        $data['ticket_scope'] = 'my_accepted';
        $data['status_filter'] = null;
        $data['current_status'] = null;


        $this->load->view('Users/List', $data);
    }

    /* ================= ADD USER FORM ================= */
    public function add_user()
    {
        if ($this->session->userdata('role_id') != 2) {
            $this->session->set_flashdata('failed', '🚫Unauthorized🚫');
            redirect('TRS/user_list');
        }

        $this->load->view('Users/Add_user');
    }

    /* ================= SAVE USER ================= */
    public function save_user()
    {
        $this->ensureDepartmentAccess([2]);
        $this->load->model('User_model');
        $data = $this->buildUserPayload('department', 'department');
        $data['user_id'] = $this->input->post('user_id');

        $this->User_model->insert_user($data);

        $this->session->set_flashdata('success', 'User created successfully');
        redirect('TRS/user_list');
    }


    public function save_userlist_ajax()
    {
        if ($this->session->userdata('role_id') != 2) {
            $this->respondJson([
                'status' => false,
                'message' => 'Unauthorized access'
            ]);
            return;
        }

        $this->load->model('User_model');
        $data = $this->buildUserPayload('department', 'department');
        $data['user_id'] = $this->input->post('user_id');

        if ($this->User_model->insert_user($data)) {
            $this->respondJson([
                'status' => true,
                'message' => 'User created successfully'
            ]);
        } else {
            $this->respondJson([
                'status' => false,
                'message' => 'Failed to create user'
            ]);
        }
    }


    /* ================= USER LIST ================= */
    public function user_list()
    {
        if ($this->session->userdata('role_id') != 2) {
            $this->session->set_flashdata('failed', '🚫Unauthorized🚫');
            redirect('TRS/list');
        }

        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_all_staff();

        $this->load->view('Users/User_list', $data);
    }

    public function edit_userlist($user_id)
    {
        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_user_staff($user_id);


        $this->load->view('Users/Edit_userlist', $data);
    }

    public function edit_userlist_ajax()
    {
        $user_id = $this->input->post('user_id');

        $this->load->model('User_model');
        $user = $this->User_model->get_user_staff($user_id);

        if ($user) {
            echo json_encode([
                'status' => true,
                'data'   => $user
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'msg' => 'User not found'
            ]);
        }
    }


    public function delete_userlist($user_id)
    {
        $this->load->model('User_model');
        $this->User_model->delete_user($user_id);

        $this->session->set_flashdata('success', 'User deleted successfully');
        redirect('TRS/user_list');
    }


    public function update_userlist($user_id)
    {


        $this->load->model('User_model');

        // user id coming from edit form

        if (!$user_id) {
            show_error('Invalid User ID');
        }

        $email = $this->input->post('email');

        // 🔥 DUPLICATE EMAIL CHECK (IGNORE CURRENT USER)
        $check = $this->db
            ->where('email', $email)
            ->where('user_id !=', $user_id)   // 👈 current user ignore
            ->get('users')
            ->row();

        if ($check) {
            $this->session->set_flashdata('failed', 'Email already exists');
            redirect('TRS/Edit_userlist' . $user_id);   // apne edit page ka URL yahan rakho
        }



        $data = $this->buildUserPayload('department_id', 'department_id', false);
        $data['email'] = $email;
        $this->User_model->update_user_stuff($user_id, $data);
        redirect('TRS/User_list');
    }

    public function update_userlist_ajax()
    {
        $this->load->model('User_model');

        $user_id = $this->input->post('user_id');

        if (!$user_id) {
            $this->respondJson(['status' => false, 'msg' => 'Invalid User ID']);
            return;
        }

        $email = $this->input->post('email');

        // Duplicate email check
        $check = $this->db
            ->where('email', $email)
            ->where('user_id !=', $user_id)
            ->get('users')
            ->row();

        if ($check) {
            $this->respondJson([
                'status' => false,
                'msg' => 'Email already exists'
            ]);
            return;
        }

        $data = $this->buildUserPayload('department_id', 'department_id', false);
        $data['email'] = $email;

        if ($this->User_model->update_user_stuff($user_id, $data)) {
            $this->respondJson([
                'status' => true,
                'msg' => 'User updated successfully'
            ]);
        } else {
            $this->respondJson([
                'status' => false,
                'msg' => 'Update failed'
            ]);
        }
    }

    public function add_user_portal()
    {
        $this->ensureRoleAccess([2]);

        $data = $this->getUserManagementOptions();
        $data['old_user'] = (array) $this->session->flashdata('old_user');

        $this->setPageAssets(['assets/dist/css/pages/assets.css']);
        $this->render('Users/Add_user', $data);
    }

    public function save_user_portal()
    {
        $this->ensureRoleAccess([2]);
        $result = $this->buildManagedUserPayload();

        if (empty($result['success'])) {
            $this->setUserOldInput((array) ($result['old'] ?? []));
            $this->session->set_flashdata('failed', (string) ($result['message'] ?? 'Unable to create user.'));
            redirect('TRS/add_user');
        }

        $this->load->model('User_model');
        if (!$this->User_model->insert_user($result['data'])) {
            $this->setUserOldInput((array) ($result['old'] ?? []));
            $this->session->set_flashdata('failed', 'Unable to create user right now.');
            redirect('TRS/add_user');
        }

        $mode = ((int) ($result['data']['is_registered'] ?? 0) === 1) ? 'active account' : 'pending activation user';
        $this->session->set_flashdata('success', 'User created successfully as a ' . $mode . '.');
        redirect('TRS/user_list');
    }

    public function user_list_portal()
    {
        $this->ensureRoleAccess([2]);

        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_all_users_with_meta();

        $this->setPageAssets(['assets/dist/css/pages/assets.css']);
        $this->render('Users/User_list', $data);
    }

    public function edit_user_portal($user_id)
    {
        $this->ensureRoleAccess([2]);

        $this->load->model('User_model');
        $data['user'] = $this->User_model->get_user_with_meta($user_id);

        if (!$data['user']) {
            show_error('User not found', 404);
        }

        $oldUser = (array) $this->session->flashdata('old_user');
        if (!empty($oldUser)) {
            $data['user'] = array_merge($data['user'], $oldUser);
        }

        $data = array_merge($data, $this->getUserManagementOptions((int) $user_id));

        $this->setPageAssets(['assets/dist/css/pages/assets.css']);
        $this->render('Users/Edit_userlist', $data);
    }

    public function update_user_portal($user_id)
    {
        $this->ensureRoleAccess([2]);

        if (!(int) $user_id) {
            show_error('Invalid User ID');
        }

        $this->load->model('User_model');
        $result = $this->buildManagedUserPayload((int) $user_id);

        if (empty($result['success'])) {
            $this->setUserOldInput((array) ($result['old'] ?? []));
            $this->session->set_flashdata('failed', (string) ($result['message'] ?? 'Unable to update user.'));
            redirect('TRS/edit_userlist/' . (int) $user_id);
        }

        if (!$this->User_model->update_user_stuff($user_id, $result['data'])) {
            $this->setUserOldInput((array) ($result['old'] ?? []));
            $this->session->set_flashdata('failed', 'Unable to update user right now.');
            redirect('TRS/edit_userlist/' . (int) $user_id);
        }

        $this->session->set_flashdata('success', 'User updated successfully.');
        redirect('TRS/user_list');
    }

    public function delete_user_portal($user_id)
    {
        $this->ensureRoleAccess([2]);

        if ((int) $user_id === (int) $this->session->userdata('user_id')) {
            $this->session->set_flashdata('failed', 'You cannot delete your own account.');
            redirect('TRS/user_list');
        }

        $this->load->model('User_model');
        if ($this->User_model->delete_user($user_id)) {
            $this->session->set_flashdata('success', 'User deleted successfully.');
        } else {
            $this->session->set_flashdata('failed', 'Unable to delete user right now.');
        }

        redirect('TRS/user_list');
    }


    public function ticket()
    {
        $ticket_id = $this->input->post('ticket_id');
        if ($this->session->userdata('department_id') != 2) {
            $this->session->flashdata("failed", "🚫Unauthorized🚫");
            redirect('verify');
        }

        $this->load->model('Ticket_model');
        $data['history'] = $this->Ticket_model->TicketHistory($ticket_id);
        $this->load->view('Same_pages/Ticket_history', $data);
    }
    public function reassign_form($ticket_id)
    {
        // only admin and developer
        if ($this->session->userdata('department_id') != 2) {
            show_error('Unauthorized');
        }

        $this->load->model('TRS_model');

        $data['ticket_id'] = $ticket_id;
        $data['developers'] = $this->TRS_model->get_all_developers();

        $this->load->view('Users/reassign_form', $data);
    }
    public function do_reassign()
    {
        // admin (3) OR developer (2)
        if (!in_array($this->session->userdata('department_id'), [2])) {
            show_error('Unauthorized');
        }

        $ticket_id  = $this->input->post('ticket_id');
        $new_dev_id = $this->input->post('developer_id');
        $actor_id   = $this->session->userdata('user_id');
        $role_id    = $this->session->userdata('role_id');

        if (!$ticket_id || !$new_dev_id) {
            show_error('Invalid request');
        }

        // 1️⃣ Update ticket
        $this->db->where('ticket_id', $ticket_id)
            ->update('tickets', [
                'assigned_engineer_id' => $new_dev_id,
                'status_id'               => 2
            ]);

        // 2️⃣ History insert (REASSIGN)
        $this->db->insert('ticket_assignment_history', [
            'ticket_id'   => $ticket_id,
            'action_type' => 'reassign',
            'assigned_to' => $new_dev_id,
            'assigned_by' => $actor_id,
            'remarks'     => ($role_id == 2)
                ? 'Ticket reassigned by IT Head'
                : 'Ticket reassigned by developer',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $this->session->set_flashdata('success', 'Ticket reassigned successfully');
        redirect('TRS/ticket');
    }

    public function do_reassign_ajax()
    {
        if (!in_array($this->session->userdata('department_id'), [2])) {
            show_error('Unauthorized');
        }

        $ticket_id  = $this->input->post('ticket_id');
        $new_dev_id = $this->input->post('assigned_engineer_id');
        $reason     = trim($this->input->post('reason'));
        $actor_id   = $this->session->userdata('user_id');
        $role_id    = $this->session->userdata('role_id');

        if ($new_dev_id == $actor_id) {
            echo json_encode(['status' => false, 'msg' => 'You cannot reassign ticket to yourself']);
            return;
        }

        if ($reason === '') {
            echo json_encode(['status' => false, 'msg' => 'Reason is required for reassign']);
            return;
        }

        $this->load->model('TRS_model');

        $this->db->trans_start();

        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => $new_dev_id,
            'status_id' => 2
        ]);

        $systemRemark = ($role_id == 2)
            ? 'Ticket reassigned by IT Head'
            : 'Ticket reassigned by Developer';

        $this->TRS_model->insert_reassignment_history([
            'ticket_id'   => $ticket_id,
            'action_type' => 'reassign',
            'assigned_to' => $new_dev_id,
            'assigned_by' => $actor_id,
            'remarks'     => $systemRemark . "\nReason: " . $reason,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['status' => false, 'msg' => 'Reassignment failed']);
            return;
        }

        echo json_encode(['status' => true, 'msg' => 'Ticket reassigned successfully']);
    }
    public function do_leave_ajax()
    {
        // 🔐 Only Developer (2) and IT Head (3) allowed
        if (!in_array($this->session->userdata('department_id'), [2])) {
            show_error('Unauthorized');
        }

        $ticket_id = $this->input->post('ticket_id');
        $reason    = trim($this->input->post('reason'));
        $user_id   = $this->session->userdata('user_id');
        $role_id   = $this->session->userdata('role_id');

        // 🔍 Minimum 5 words validation
        $wordCount = str_word_count($reason);

        if ($wordCount < 3) {
            echo json_encode([
                'status' => false,
                'msg'    => 'Please enter minimum 3 words for leave reason.'
            ]);
            return;
        }

        $this->load->model('TRS_model');

        // 🔐 Ensure user is assigned to ticket
        $ticket = $this->TRS_model->get_data_by_id($ticket_id);

        if (!$ticket || $ticket['assigned_engineer_id'] != $user_id) {
            echo json_encode([
                'status' => false,
                'msg'    => 'You are not assigned to this ticket'
            ]);
            return;
        }

        // 1️⃣ Update ticket (Remove assignment + Reset status)
        $this->TRS_model->update_ticket($ticket_id, [
            'assigned_engineer_id' => NULL,
            'status_id'            => 1
        ]);

        // 2️⃣ Insert history
        $systemRemark = ($role_id == 2)
            ? 'Ticket left by IT Head'
            : 'Ticket left by Developer';

        $this->TRS_model->insert_reassignment_history([
            'ticket_id'   => $ticket_id,
            'action_type' => 'leave',
            'assigned_to' => NULL,
            'assigned_by' => $user_id,
            'remarks'     => $systemRemark . "\nReason: " . $reason,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'status' => true,
            'msg'    => 'Ticket left successfully'
        ]);
    }
public function board()
{
    $this->load->model('Ticket_model');

    $data['statuses'] = $this->db
        ->order_by('display_order', 'ASC')
        ->get('ticket_statuses')
        ->result();

    $permissions = $this->db
        ->where('role_id', $this->session->userdata('role_id'))
        ->where('department_id', $this->session->userdata('department_id'))
        ->where('allowed', 1)
        ->get('status_permissions')
        ->result();

    $formattedPermissions = [];

    foreach ($permissions as $p) {
        $formattedPermissions[$p->from_status][] = (int)$p->to_status;
    }

    $data['permissions'] = $formattedPermissions;
    $data['page_css'] = ['assets/dist/css/pages/kanban-board.css'];
    $data['page_js'] = [
        'assets/dist/js/roles/developer/kanban-board.js',
        'assets/dist/js/roles/developer/kanban-task-guard.js'
    ];

    $this->load->view('Developer/Karban_Board', $data);  // 🔥 MISSING LINE
}
    public function ajax_get_board_tickets()
{
    $this->load->model('Ticket_model');

    $tickets = $this->Ticket_model->get_board_tickets();
  
    echo json_encode($tickets);
}
    public function reopen_ticket()
    {
        $current_user_id = (int) $this->session->userdata('user_id');
        $current_role_id = (int) $this->session->userdata('role_id');
        $current_department_id = (int) $this->session->userdata('department_id');
        $ticket_id = (int) $this->input->post('ticket_id');

        if ($ticket_id <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid ticket.']);
            return;
        }

        $ticket = $this->db
            ->where('ticket_id', $ticket_id)
            ->where('deleted_at IS NULL', null, false)
            ->get('tickets')
            ->row();

        if (!$ticket || (int) $ticket->status_id !== 4) {
            echo json_encode(['status' => false, 'message' => 'Only closed tickets can be reopened.']);
            return;
        }

        $closedAtOk = $this->isTicketWithinClosedVisibilityWindow($ticket);
        if (!$closedAtOk) {
            echo json_encode(['status' => false, 'message' => 'This ticket can no longer be reopened (closed more than 7 days ago).']);
            return;
        }

        // Dept 2 (IT): allow IT Head to reopen to Open while keeping the same assignee.
        if ($current_department_id === 2 && $current_role_id === 2) {
            $this->load->model('TRS_model');

            $this->TRS_model->update_ticket($ticket_id, [
                'status_id' => 1,
                'closed_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->TRS_model->insert_assignment_history([
                'ticket_id' => $ticket_id,
                'action_type' => 'reopen',
                'assigned_to' => $ticket->assigned_engineer_id,
                'assigned_by' => $current_user_id,
                'remarks' => 'Ticket reopened from Closed to Open (kept assignee)',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            echo json_encode(['status' => true]);
            return;
        }

        // Non-IT flow: only the ticket raiser can reopen.
        if ($current_role_id !== 1 || $current_department_id === 2) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized reopen request.']);
            return;
        }

        if ((int) $ticket->user_id !== $current_user_id) {
            echo json_encode(['status' => false, 'message' => 'Only the ticket raiser can reopen a closed ticket.']);
            return;
        }

        $this->db->where('ticket_id', $ticket_id);
        $this->db->update('tickets', [
            'status_id' => 2,
            'closed_at' => null,
        ]);

        echo json_encode(['status' => true]);
    }
    public function confirm_resolution()
    {
        $ticket_id = (int)$this->input->post('ticket_id');
        $answer    = $this->input->post('answer');

        if (!$ticket_id) {
            echo json_encode(['success' => false]);
            return;
        }

        $this->load->model('Ticket_model');

        if ($answer === 'yes') {

            $this->db->where('ticket_id', $ticket_id);
            $this->db->update('tickets', [
                'status_id' => 4, // Closed
                'closed_at' => date('Y-m-d H:i:s')
            ]);
        } else {

            $this->db->where('ticket_id', $ticket_id);
            $this->db->update('tickets', [
                'status_id' => 2, // In Progress
                'closed_at' => NULL
            ]);
        }

        echo json_encode(['success' => true]);
    }

    public function save_ticket_feedback()
    {
        $ticket_id = (int)$this->input->post('ticket_id');
        $rating = (int)$this->input->post('rating');
        $comment = trim((string) $this->input->post('comment'));

        if (!$ticket_id || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid rating data.']);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');

        $ticket = $this->db
            ->select('ticket_id, user_id, assigned_engineer_id, title')
            ->where('ticket_id', $ticket_id)
            ->where('deleted_at IS NULL', null, false)
            ->get('tickets')
            ->row();

        if (!$ticket || (int)$ticket->user_id !== $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $this->load->model('Ticket_model');
        $saved = $this->Ticket_model->save_feedback($ticket_id, $rating, $comment, $user_id);

        if ($saved) {
            // Notify assigned engineer if exists
            if (!empty($ticket->assigned_engineer_id)) {
                $message = 'Ticket "' . $ticket->title . '" was rated ' . $rating . '/5.';

                if (!empty($comment)) {
                    $message .= ' Comment: "' . $comment . '"';
                }

                $this->notifyUsers([
                    (object)['user_id' => $ticket->assigned_engineer_id]
                ], $message, $ticket_id, 'rating');

                // send an email as well
                $engineer = $this->db
                    ->select('email')
                    ->from('users')
                    ->where('user_id', $ticket->assigned_engineer_id)
                    ->get()
                    ->row();

                if ($engineer && !empty($engineer->email)) {
                    $this->sendSystemEmail($engineer->email, 'Ticket Rating Received', $message);
                }
            }

            echo json_encode(['success' => true]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Unable to save rating.']);
    }

    public function update_board_position()
    {
        header('Content-Type: application/json');

        $order = json_decode($this->input->post('order'), true);
        $status_id = $this->input->post('status_id');

        $current_role_id = $this->session->userdata('role_id');
        $current_user_id = $this->session->userdata('user_id');

        $this->load->model('TRS_model');

        foreach ($order as $item) {

            $ticket = $this->db->where('ticket_id', $item['ticket_id'])
                ->where('deleted_at IS NULL')
                ->get('tickets')
                ->row();

            if (!$ticket) {
                continue;
            }

            $from_status = $ticket->status_id;
            $to_status   = $status_id;

            /*
        =====================================================
        1️⃣ STATUS PERMISSION CHECK (DB Driven)
        =====================================================
        */

            if ($from_status != $to_status) {

                $permission = $this->TRS_model->check_status_permission(
                    $current_role_id,
                    $from_status,
                    $to_status
                );

                if (!$permission) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Status change not allowed'
                    ]);
                    return;
                }
            }

            /*
        =====================================================
        2️⃣ ROLE OWNERSHIP CHECK (DB Driven)
        =====================================================
        */

            $current_department_id = (int) $this->session->userdata('department_id');

            if ($current_department_id === 2 && (int) $current_role_id === 1) {
                $is_open_unassigned = ((int) $ticket->status_id === 1 && empty($ticket->assigned_engineer_id));
                $is_own_assigned = ((int) $ticket->assigned_engineer_id === (int) $current_user_id);

                if (!$is_open_unassigned && !$is_own_assigned) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'You can only move open queue tickets or tickets assigned to you.'
                    ]);
                    return;
                }
            }

            if ((int) $current_role_id === 2 && $current_department_id === 2) {
                $is_open_unassigned = ((int) $ticket->status_id === 1 && empty($ticket->assigned_engineer_id));
                $is_own_workflow_ticket = ((int) $ticket->assigned_engineer_id === (int) $current_user_id && in_array((int) $ticket->status_id, [2, 3], true));

                if (!$is_open_unassigned && !$is_own_workflow_ticket) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'IT Head can move only open queue tickets or workflow tickets assigned to self.'
                    ]);
                    return;
                }
            }

            /*
        =====================================================
        3️⃣ TASK COMPLETION CHECK (DB Driven)
        =====================================================
        */

            $status = $this->db->where('status_id', $to_status)
                ->get('ticket_statuses')
                ->row();

            if ($status && $status->require_all_tasks_completed == 1) {

                $total_tasks = $this->db
                    ->where('ticket_id', $ticket->ticket_id)
                    ->count_all_results('ticket_tasks');

                $completed_tasks = $this->db
                    ->where('ticket_id', $ticket->ticket_id)
                    ->where('is_completed', 1)
                    ->count_all_results('ticket_tasks');

                if ($total_tasks == 0 || $total_tasks != $completed_tasks) {

                    echo json_encode([
                        'success' => false,
                        'message' => 'Complete all tasks before moving'
                    ]);
                    return;
                }
            }

            /*
        =====================================================
        4️⃣ UPDATE TICKET
        =====================================================
        */

            $closed_status = $this->db
                ->where('LOWER(status_slug)', 'closed')
                ->get('ticket_statuses')
                ->row();

            $update_data = [
                'board_position' => $item['board_position'],
                'status_id'      => $to_status,
                'updated_at'     => date('Y-m-d H:i:s')
            ];

            // Auto assign when moving to In Progress
            $in_progress = $this->db
                ->where('status_slug', 'in_progress')
                ->get('ticket_statuses')
                ->row();

            if ($in_progress && $to_status == $in_progress->status_id) {
                $update_data['assigned_engineer_id'] = $current_user_id;
            }

            if ($closed_status && (int) $to_status === (int) $closed_status->status_id) {
                $update_data['closed_at'] = date('Y-m-d H:i:s');
            } else {
                $update_data['closed_at'] = null;
            }

            $this->db->where('ticket_id', $ticket->ticket_id);
            $this->db->update('tickets', $update_data);
        }

        echo json_encode(['success' => true]);
    }

    public function get_ticket_details()
    {
        $ticket_id = (int)$this->input->post('ticket_id');

        if (!$ticket_id) {
            echo json_encode(['error' => true]);
            return;
        }

        $this->load->model('Ticket_model');

        $ticket = $this->Ticket_model->get_ticket_by_id($ticket_id);

        if (!$ticket || !$this->canViewTicketTasks($ticket)) {
            echo json_encode(['error' => true]);
            return;
        }

        $tasks = $this->Ticket_model->get_tasks_by_ticket($ticket_id);
        $feedback = $this->Ticket_model->get_feedback_by_ticket($ticket_id);

        echo json_encode([
            'error'    => false,
            'ticket'   => $ticket,
            'tasks'    => $tasks ? $tasks : [],
            'feedback' => $feedback ? $feedback : null
        ]);
    }

    public function update_task_status()
    {
        $task_id = (int)$this->input->post('task_id');
        $is_completed = (int)$this->input->post('is_completed');

        if (!$task_id || !in_array($is_completed, [0, 1], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid task request.']);
            return;
        }

        $task = $this->db
            ->select('tt.task_id, tt.ticket_id, t.user_id, t.assigned_engineer_id, t.deleted_at')
            ->from('ticket_tasks tt')
            ->join('tickets t', 't.ticket_id = tt.ticket_id')
            ->where('tt.task_id', $task_id)
            ->get()
            ->row_array();

        if (!$task || !empty($task['deleted_at'])) {
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
            return;
        }

        if (!$this->canToggleTicketTaskStatus($task)) {
            echo json_encode(['success' => false, 'message' => 'You are not allowed to update this task.']);
            return;
        }

        $this->db->where('task_id', $task_id);
        $this->db->update('ticket_tasks', [
            'is_completed' => $is_completed
        ]);

        $pending = $this->db
            ->where('ticket_id', (int) ($task['ticket_id'] ?? 0))
            ->where('is_completed', 0)
            ->count_all_results('ticket_tasks');

        $this->respondJson([
            'success' => true,
            'ticket_id' => (int) ($task['ticket_id'] ?? 0),
            'can_resolve' => $pending == 0 ? 1 : 0
        ]);
    }

    public function timer_ticket_status()
    {
        $ticket_id = (int) $this->input->get('ticket_id');
        if ($ticket_id <= 0) {
            $this->respondJson(['success' => false, 'message' => 'Invalid ticket.']);
            return;
        }

        $this->load->model('Ticket_model');
        $this->load->model('Timer_model');

        $ticket = $this->Ticket_model->get_ticket_by_id($ticket_id);
        if (!$ticket || !$this->canViewTicketTasks($ticket)) {
            $this->respondJson(['success' => false, 'message' => 'Unauthorized.']);
            return;
        }

        $isClosed = ((int) $ticket->status_id === 4);
        $expired = false;
        $expiresAt = null;

        $expiresAtTimestamp = $this->getTicketClosedExpiryTimestamp($ticket);
        if ($expiresAtTimestamp !== false) {
            $expiresAt = date('Y-m-d H:i', $expiresAtTimestamp);
            $expired = time() >= $expiresAtTimestamp;
        }

        $timer = $this->Timer_model->getTicketTimer($ticket_id);

        if ($expired) {
            $timer = $this->Timer_model->stopTicketTimerPermanently($ticket_id, (int) $this->session->userdata('user_id'));
        }

        $locked = $expired || (!empty($timer['stopped_permanently_at']));
        $isRunning = !empty($timer['is_running']);
        $totalSeconds = $this->Timer_model->getTicketTimerTotalSeconds($timer);

        $this->respondJson([
            'success' => true,
            'ticket_id' => $ticket_id,
            'total_seconds' => $totalSeconds,
            'is_running' => $isRunning ? 1 : 0,
            'locked' => $locked ? 1 : 0,
            'is_closed' => $isClosed ? 1 : 0,
            'expires_at' => $expiresAt,
            'can_start' => (!$locked && !$isClosed),
            'can_pause' => (!$locked && $isRunning)
        ]);
    }

    public function timer_ticket_start()
    {
        $ticket_id = (int) $this->input->post('ticket_id');
        if ($ticket_id <= 0) {
            $this->respondJson(['success' => false, 'message' => 'Invalid ticket.']);
            return;
        }

        $this->load->model('Ticket_model');
        $this->load->model('Timer_model');

        $ticket = $this->Ticket_model->get_ticket_by_id($ticket_id);
        if (!$ticket || !$this->canViewTicketTasks($ticket)) {
            $this->respondJson(['success' => false, 'message' => 'Unauthorized.']);
            return;
        }

        if ((int) $ticket->status_id === 4) {
            $this->respondJson(['success' => false, 'message' => 'Closed tickets cannot start the timer.']);
            return;
        }

        $expiresAtTimestamp = $this->getTicketClosedExpiryTimestamp($ticket);
        if ($expiresAtTimestamp !== false && time() >= $expiresAtTimestamp) {
            $this->Timer_model->stopTicketTimerPermanently($ticket_id, (int) $this->session->userdata('user_id'));
            $this->respondJson(['success' => false, 'message' => 'Timer locked for this ticket.']);
            return;
        }

        $timer = $this->Timer_model->getTicketTimer($ticket_id);
        if (!empty($timer['stopped_permanently_at'])) {
            $this->respondJson(['success' => false, 'message' => 'Timer locked for this ticket.']);
            return;
        }

        $timer = $this->Timer_model->startTicketTimer($ticket_id, (int) $this->session->userdata('user_id'));
        $totalSeconds = $this->Timer_model->getTicketTimerTotalSeconds($timer);

        $this->respondJson([
            'success' => true,
            'ticket_id' => $ticket_id,
            'total_seconds' => $totalSeconds,
            'is_running' => 1,
            'locked' => 0,
            'is_closed' => 0,
            'can_start' => 0,
            'can_pause' => 1
        ]);
    }

    public function timer_ticket_pause()
    {
        $ticket_id = (int) $this->input->post('ticket_id');
        if ($ticket_id <= 0) {
            $this->respondJson(['success' => false, 'message' => 'Invalid ticket.']);
            return;
        }

        $this->load->model('Ticket_model');
        $this->load->model('Timer_model');

        $ticket = $this->Ticket_model->get_ticket_by_id($ticket_id);
        if (!$ticket || !$this->canViewTicketTasks($ticket)) {
            $this->respondJson(['success' => false, 'message' => 'Unauthorized.']);
            return;
        }

        $expiresAtTimestamp = $this->getTicketClosedExpiryTimestamp($ticket);
        if ($expiresAtTimestamp !== false && time() >= $expiresAtTimestamp) {
            $this->Timer_model->stopTicketTimerPermanently($ticket_id, (int) $this->session->userdata('user_id'));
            $this->respondJson(['success' => false, 'message' => 'Timer locked for this ticket.']);
            return;
        }

        $timer = $this->Timer_model->pauseTicketTimer($ticket_id, (int) $this->session->userdata('user_id'));
        if (!$timer) {
            $this->respondJson(['success' => false, 'message' => 'Timer not found.']);
            return;
        }

        $totalSeconds = $this->Timer_model->getTicketTimerTotalSeconds($timer);

        $this->respondJson([
            'success' => true,
            'ticket_id' => $ticket_id,
            'total_seconds' => $totalSeconds,
            'is_running' => 0,
            'locked' => !empty($timer['stopped_permanently_at']) ? 1 : 0,
            'is_closed' => ((int) $ticket->status_id === 4) ? 1 : 0,
            'can_start' => ((int) $ticket->status_id !== 4) && empty($timer['stopped_permanently_at']),
            'can_pause' => 0
        ]);
    }

    public function add_task()
    {
        $ticket_id = (int) $this->input->post('ticket_id');
        $task_title = trim((string) $this->input->post('task_title'));

        if ($ticket_id <= 0 || $task_title === '' || mb_strlen($task_title) > 255) {
            echo json_encode(['success' => false, 'message' => 'Invalid task details.']);
            return;
        }

        $ticket = $this->db
            ->select('ticket_id, user_id, assigned_engineer_id, deleted_at')
            ->where('ticket_id', $ticket_id)
            ->get('tickets')
            ->row_array();

        if (!$ticket || !empty($ticket['deleted_at'])) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
            return;
        }

        if (!$this->canManageTicketTasks($ticket)) {
            echo json_encode(['success' => false, 'message' => 'You are not allowed to add tasks here.']);
            return;
        }

        $maxPosition = $this->db
            ->select_max('position')
            ->where('ticket_id', $ticket_id)
            ->get('ticket_tasks')
            ->row();

        $nextPosition = isset($maxPosition->position) ? ((int) $maxPosition->position + 1) : 1;

        $this->db->insert('ticket_tasks', [
            'ticket_id' => $ticket_id,
            'task_title' => $task_title,
            'is_completed' => 0,
            'position' => $nextPosition,
            'created_by' => $this->session->userdata('user_id')
        ]);

        $task_id = $this->db->insert_id();

        echo json_encode([
            'success' => true,
            'task' => [
                'task_id' => $task_id,
                'task_title' => $task_title
            ]
        ]);
    }

    public function update_task_title()
    {
        $task_id = (int) $this->input->post('task_id');
        $task_title = trim((string) $this->input->post('task_title'));

        if ($task_id <= 0 || $task_title === '' || mb_strlen($task_title) > 255) {
            echo json_encode(['success' => false, 'message' => 'Invalid task title.']);
            return;
        }

        $task = $this->db
            ->select('tt.task_id, tt.ticket_id, t.user_id, t.assigned_engineer_id, t.deleted_at')
            ->from('ticket_tasks tt')
            ->join('tickets t', 't.ticket_id = tt.ticket_id')
            ->where('tt.task_id', $task_id)
            ->get()
            ->row_array();

        if (!$task || !empty($task['deleted_at'])) {
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
            return;
        }

        if (!$this->canManageTicketTasks($task)) {
            echo json_encode(['success' => false, 'message' => 'You are not allowed to edit this task.']);
            return;
        }

        $this->db->where('task_id', $task_id);
        $this->db->update('ticket_tasks', [
            'task_title' => $task_title
        ]);

        echo json_encode(['success' => true]);
    }
    public function update_task_position()
    {
        $order = $this->input->post('order');

        if (!is_array($order) || empty($order)) {
            echo json_encode(['success' => false, 'message' => 'Invalid task order.']);
            return;
        }

        $taskIds = [];
        foreach ($order as $item) {
            if (!isset($item['task_id'], $item['position'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid task order payload.']);
                return;
            }

            $taskIds[] = (int) $item['task_id'];
        }

        $tasks = $this->db
            ->select('tt.task_id, tt.ticket_id, t.user_id, t.assigned_engineer_id, t.deleted_at')
            ->from('ticket_tasks tt')
            ->join('tickets t', 't.ticket_id = tt.ticket_id')
            ->where_in('tt.task_id', $taskIds)
            ->get()
            ->result_array();

        if (count($tasks) !== count($taskIds)) {
            echo json_encode(['success' => false, 'message' => 'One or more tasks are invalid.']);
            return;
        }

        $ticketIds = array_unique(array_map(function ($task) {
            return (int) $task['ticket_id'];
        }, $tasks));

        if (count($ticketIds) !== 1) {
            echo json_encode(['success' => false, 'message' => 'Tasks must belong to the same ticket.']);
            return;
        }

        if (!$this->canManageTicketTasks($tasks[0]) || !empty($tasks[0]['deleted_at'])) {
            echo json_encode(['success' => false, 'message' => 'You are not allowed to reorder these tasks.']);
            return;
        }

        foreach ($order as $item) {
            $this->db->where('task_id', $item['task_id']);
            $this->db->update('ticket_tasks', [
                'position' => (int) $item['position']
            ]);
        }

        echo json_encode(['success' => true]);
    }
    public function get_notifications()
    {
        $user_id = $this->session->userdata('user_id');

        $this->load->model('TRS_model');
        $notifications = $this->TRS_model->get_unread_notifications($user_id);

        echo json_encode([
            'count' => count($notifications),
            'notifications' => $notifications
        ]);
    }
    public function process_assignment_queue()
    {
        if (!$this->input->is_cli_request() && $this->session->userdata('role_id') != 2) {
            show_error('Unauthorized', 403);
        }

        $this->load->model('TRS_model');

        $now = date('Y-m-d H:i:s');
        $it_users = $this->TRS_model->get_it_team_users();
        $it_heads = array_filter($it_users, function ($user) {
            return (int) $user->role_id === 2;
        });

        $reminder_count = 0;
        foreach ($this->TRS_model->get_assignment_reminder_candidates($now) as $ticket) {
            $message = 'Reminder: ticket "' . $ticket->title . '" is still unassigned and needs IT Head action before auto-assignment.';

            $this->notifyUsers($it_heads, $message, $ticket->ticket_id);
            foreach ($it_heads as $head) {
                $this->sendSystemEmail($head->email, 'Assignment Reminder: ' . $ticket->title, $message);
            }

            $this->TRS_model->mark_assignment_reminder_sent($ticket->ticket_id);
            $reminder_count++;
        }

        $auto_assign_count = 0;
        foreach ($this->TRS_model->get_unassigned_open_tickets_for_assignment($now) as $ticket) {
            $developer = $this->TRS_model->get_auto_assign_candidate();
            if (!$developer) {
                continue;
            }

            $this->TRS_model->update_ticket($ticket->ticket_id, [
                'assigned_engineer_id' => $developer->user_id,
                'status_id' => 2,
                'auto_assigned_at' => $now,
            ]);

            $this->TRS_model->insert_assignment_history([
                'ticket_id'   => $ticket->ticket_id,
                'action_type' => 'auto_assign',
                'assigned_to' => $developer->user_id,
                'assigned_by' => null,
                'remarks'     => 'Ticket auto-assigned after 24 hours without acceptance',
                'created_at'  => $now
            ]);

            $this->notifyTicketOwnerAssignment($ticket->ticket_id, $developer->user_id, 'auto-assigned');
            $auto_assign_count++;
        }

        echo json_encode([
            'status' => 'success',
            'reminders' => $reminder_count,
            'auto_assigned' => $auto_assign_count,
            'run_at' => $now,
        ]);
    }
    public function mark_notification_read()
    {
        $id = $this->input->post('id');

        $this->db->where('id', $id)
            ->update('task_messages', ['is_read' => 1]);
    }

    private function can_access_task_notification($task_id, $user_id)
    {
        if ($task_id <= 0 || $user_id <= 0) {
            return false;
        }

        $currentRoleId = (int) $this->session->userdata('role_id');
        $currentDepartmentId = (int) $this->session->userdata('department_id');

        if ($currentRoleId === 2) {
            return (bool) $this->db
                ->select('tt.task_id')
                ->from('ticket_tasks tt')
                ->join('tickets t', 't.ticket_id = tt.ticket_id')
                ->where('tt.task_id', $task_id)
                ->where('t.deleted_at IS NULL', null, false)
                ->get()
                ->row_array();
        }

        return (bool) $this->db
            ->select('tt.task_id')
            ->from('ticket_tasks tt')
            ->join('tickets t', 't.ticket_id = tt.ticket_id')
            ->join('users owner', 'owner.user_id = t.user_id', 'left')
            ->where('tt.task_id', $task_id)
            ->where('t.deleted_at IS NULL', null, false)
            ->group_start()
                ->where('t.user_id', $user_id)
                ->or_where('t.assigned_engineer_id', $user_id)
                ->or_where('owner.department_id', $currentDepartmentId)
            ->group_end()
            ->get()
            ->row_array();
    }

    private function canManageTicketTasks($ticket)
    {
        $currentUserId = (int) $this->session->userdata('user_id');
        $currentRoleId = (int) $this->session->userdata('role_id');
        $currentDepartmentId = (int) $this->session->userdata('department_id');

        if ($currentUserId <= 0 || empty($ticket)) {
            return false;
        }

        $ticketOwnerId = isset($ticket['user_id']) ? (int) $ticket['user_id'] : 0;
        $assignedEngineerId = isset($ticket['assigned_engineer_id']) ? (int) $ticket['assigned_engineer_id'] : 0;

        if ($ticketOwnerId === $currentUserId) {
            return true;
        }

        if ($assignedEngineerId === $currentUserId) {
            return true;
        }

        return ($currentDepartmentId === 2 && $currentRoleId === 2);
    }

    private function canToggleTicketTaskStatus($ticket)
    {
        $currentUserId = (int) $this->session->userdata('user_id');
        $currentRoleId = (int) $this->session->userdata('role_id');
        $currentDepartmentId = (int) $this->session->userdata('department_id');

        if ($currentUserId <= 0 || empty($ticket)) {
            return false;
        }

        $ticketOwnerId = isset($ticket['user_id']) ? (int) $ticket['user_id'] : 0;
        $assignedEngineerId = isset($ticket['assigned_engineer_id']) ? (int) $ticket['assigned_engineer_id'] : 0;

        if ($ticketOwnerId === $currentUserId) {
            return false;
        }

        if ($assignedEngineerId === $currentUserId) {
            return true;
        }

        return ($currentDepartmentId === 2 && $currentRoleId === 2);
    }

    private function canViewTicketTasks($ticket)
    {
        if ($this->canManageTicketTasks((array) $ticket)) {
            return true;
        }

        $currentUserId = (int) $this->session->userdata('user_id');
        $currentRoleId = (int) $this->session->userdata('role_id');
        $currentDepartmentId = (int) $this->session->userdata('department_id');
        if ($currentUserId <= 0 || empty($ticket)) {
            return false;
        }

        $ticketOwnerId = isset($ticket->user_id) ? (int) $ticket->user_id : (isset($ticket['user_id']) ? (int) $ticket['user_id'] : 0);
        $assignedEngineerId = isset($ticket->assigned_engineer_id) ? (int) $ticket->assigned_engineer_id : (isset($ticket['assigned_engineer_id']) ? (int) $ticket['assigned_engineer_id'] : 0);
        $ticketOwnerDepartmentId = isset($ticket->owner_department_id) ? (int) $ticket->owner_department_id : (isset($ticket['owner_department_id']) ? (int) $ticket['owner_department_id'] : 0);

        if ($ticketOwnerId === $currentUserId || $assignedEngineerId === $currentUserId) {
            return true;
        }

        if ($currentRoleId === 2) {
            return true;
        }

        return ($ticketOwnerDepartmentId > 0 && $ticketOwnerDepartmentId === $currentDepartmentId);
    }

    public function load_notification_comments()
    {
        $task_id = (int) $this->input->post('task_id');
        $user_id = (int) $this->session->userdata('user_id');

        if ($task_id <= 0 || $user_id <= 0) {
            echo json_encode([
                'status' => false,
                'html' => '<div class="text-danger text-center py-2">Invalid comment request.</div>',
                'unread_count' => 0
            ]);
            return;
        }

        if (!$this->can_access_task_notification($task_id, $user_id)) {
            echo json_encode([
                'status' => false,
                'html' => '<div class="text-danger text-center py-2">You are not allowed to view these comments.</div>',
                'unread_count' => 0
            ]);
            return;
        }

        $this->db
            ->where('receiver_id', $user_id)
            ->where('task_id', $task_id)
            ->where('is_read', 0)
            ->update('task_messages', ['is_read' => 1]);

        $comments = $this->db
            ->select('task_comments.comment, task_comments.created_at, task_comments.user_id, users.name as user_name')
            ->from('task_comments')
            ->join('users', 'users.user_id = task_comments.user_id')
            ->where('task_comments.task_id', $task_id)
            ->order_by('task_comments.comment_id', 'ASC')
            ->get()
            ->result();

        if (empty($comments)) {
            $html = '<div class="text-muted text-center py-2">No comments found.</div>';
        } else {
            $html = '';
            foreach ($comments as $comment) {
                $side = ((int) $comment->user_id === $user_id) ? 'chat-right' : 'chat-left';
                $html .= '<div class="chat-message ' . $side . '">';
                $html .= '<strong>' . html_escape($comment->user_name) . ':</strong> ';
                $html .= nl2br(html_escape($comment->comment));
                $html .= '<div class="text-muted text-xs mt-1">' . html_escape($comment->created_at) . '</div>';
                $html .= '</div>';
            }
        }

        $unread_count = (int) $this->db
            ->where('receiver_id', $user_id)
            ->where('is_read', 0)
            ->count_all_results('task_messages');

        echo json_encode([
            'status' => true,
            'html' => $html,
            'unread_count' => $unread_count
        ]);
    }

    public function add_task_comment()
    {
        $ticket_id = (int) $this->input->post('ticket_id');
        $task_id   = (int) $this->input->post('task_id');
        $message   = trim((string) ($this->input->post('message') ?: $this->input->post('comment')));
        $sender_id = (int) $this->session->userdata('user_id');

        // 1️⃣ Insert comment (agar alag table me rakhte ho to)
        if ($ticket_id <= 0 || $task_id <= 0 || $sender_id <= 0 || $message === '') {
            echo json_encode(['status' => false, 'message' => 'Invalid comment request.']);
            return;
        }

        $ticket = $this->db
            ->select('ticket_id, user_id, assigned_engineer_id, title')
            ->where('ticket_id', $ticket_id)
            ->get('tickets')
            ->row();

        if (!$ticket) {
            echo json_encode(['status' => false, 'message' => 'Ticket not found.']);
            return;
        }

        $this->db->insert('task_comments', [
            'task_id'   => $task_id,
            'user_id'   => $sender_id,
            'comment'   => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 2️⃣ Receiver decide karo
        $receivers = [];

        if ((int) $ticket->user_id === $sender_id) {
            if (!empty($ticket->assigned_engineer_id)) {
                $receivers[] = (int) $ticket->assigned_engineer_id;
            } else {
                $heads = $this->db
                    ->select('user_id')
                    ->from('users')
                    ->where('department_id', 2)
                    ->where('role_id', 2)
                    ->where('status', 'Active')
                    ->get()
                    ->result();

                foreach ($heads as $head) {
                    $receivers[] = (int) $head->user_id;
                }
            }
        } else {
            $receivers[] = (int) $ticket->user_id;
        }

        // 3️⃣ Insert notification in task_messages
        $receivers = array_values(array_unique(array_filter($receivers)));
        $sender_name = $this->session->userdata('name') ?: 'A user';

        foreach ($receivers as $receiver_id) {
            if ($receiver_id === $sender_id) {
                continue;
            }

            $this->db->insert('task_messages', [
                'ticket_id' => $ticket_id,
                'task_id'   => $task_id,
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message'   => 'New comment on ticket "' . $ticket->title . '" from ' . $sender_name,
                'is_read'   => 0
            ]);
        }

        echo json_encode(['status' => true]);
    }

    public function mark_ticket_comment_notifications_read()
    {
        $ticket_id = (int) $this->input->post('ticket_id');
        $user_id = (int) $this->session->userdata('user_id');

        if ($ticket_id <= 0 || $user_id <= 0) {
            echo json_encode(['status' => false]);
            return;
        }

        $this->db
            ->where('ticket_id', $ticket_id)
            ->where('receiver_id', $user_id)
            ->where('task_id IS NOT NULL', null, false)
            ->update('task_messages', ['is_read' => 1]);

        echo json_encode(['status' => true]);
    }
    public function get_all_task_counts()
    {
        $tickets = $this->db->select('ticket_id')
            ->get('tickets')
            ->result();

        $data = [];

        foreach ($tickets as $t) {

            $total = $this->db->where('ticket_id', $t->ticket_id)
                ->count_all_results('ticket_tasks');

            $completed = $this->db->where('ticket_id', $t->ticket_id)
                ->where('is_completed', 1)
                ->count_all_results('ticket_tasks');

            $data[] = [
                'ticket_id' => $t->ticket_id,
                'total' => $total,
                'completed' => $completed
            ];
        }

        echo json_encode($data);
    }
}
