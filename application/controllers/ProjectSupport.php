<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProjectSupport extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('project_support', true);
        $this->load->library('Trs_ai_agent');
        $this->load->model('TRS_model');
    }

    public function index()
    {
        $this->setPageAssets(
            ['assets/dist/css/pages/project-support.css'],
            ['assets/dist/js/pages/project-support.js']
        );

        $projectSupportConfig = (array) $this->config->item('project_support');
        $data = [
            'ai_enabled' => $this->trs_ai_agent->isConfigured(),
            'ai_model' => $this->trs_ai_agent->getConfiguredModel(),
            'qr_image_template' => (string) ($projectSupportConfig['qr_image_template'] ?? ''),
            'support_actions' => [
                'triage_summary' => 'Triage Summary',
                'customer_reply' => 'Customer Reply',
                'developer_handoff' => 'Developer Handoff',
                'resolution_steps' => 'Resolution Steps',
                'qa_checklist' => 'QA Checklist',
            ],
            'support_tones' => [
                'balanced' => 'Balanced',
                'concise' => 'Concise',
                'detailed' => 'Detailed',
                'executive' => 'Executive',
            ],
        ];

        $this->render('Pages/ProjectSupport/index', $data);
    }

    public function ticket_snapshot()
    {
        $ticketId = (int) $this->input->get('ticket_id');
        if ($ticketId <= 0) {
            return $this->respondJsonWithStatus([
                'success' => false,
                'message' => 'A valid ticket ID is required.'
            ], 422);
        }

        $ticket = $this->getVisibleTicketSnapshot($ticketId);
        if (!$ticket) {
            return $this->respondJsonWithStatus([
                'success' => false,
                'message' => 'Ticket not found or outside your visible scope.'
            ], 404);
        }

        return $this->respondJsonWithStatus([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    public function ai_assist()
    {
        $action = trim((string) $this->input->post('action', true));
        $tone = trim((string) $this->input->post('tone', true));
        $ticketId = (int) $this->input->post('ticket_id');
        $title = $this->cleanMultilineInput($this->input->post('title'));
        $description = $this->cleanMultilineInput($this->input->post('description'));
        $context = $this->cleanMultilineInput($this->input->post('context'));

        $allowedActions = ['triage_summary', 'customer_reply', 'developer_handoff', 'resolution_steps', 'qa_checklist'];
        if (!in_array($action, $allowedActions, true)) {
            $action = 'triage_summary';
        }

        $allowedTones = ['balanced', 'concise', 'detailed', 'executive'];
        if (!in_array($tone, $allowedTones, true)) {
            $tone = 'balanced';
        }

        $ticket = [];
        if ($ticketId > 0) {
            $ticket = $this->getVisibleTicketSnapshot($ticketId);
            if (!$ticket) {
                return $this->respondJsonWithStatus([
                    'success' => false,
                    'message' => 'Ticket not found or outside your visible scope.'
                ], 404);
            }

            if ($title === '') {
                $title = trim((string) ($ticket['title'] ?? ''));
            }

            if ($description === '') {
                $description = trim((string) ($ticket['description'] ?? ''));
            }
        }

        if ($title === '' && $description === '' && $context === '') {
            return $this->respondJsonWithStatus([
                'success' => false,
                'message' => 'Add a title, description, context, or a valid ticket ID before generating AI support output.'
            ], 422);
        }

        $result = $this->trs_ai_agent->createSupportDraft([
            'action' => $action,
            'tone' => $tone,
            'title' => $title,
            'description' => $description,
            'context' => $context,
            'ticket' => $ticket
        ]);

        if (!$result['success']) {
            return $this->respondJsonWithStatus([
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Unable to generate AI support output.')
            ], (int) ($result['status_code'] ?? 500));
        }

        return $this->respondJsonWithStatus([
            'success' => true,
            'model' => (string) ($result['model'] ?? $this->trs_ai_agent->getConfiguredModel()),
            'content' => (string) ($result['content'] ?? ''),
            'ticket_id' => (int) ($ticket['ticket_id'] ?? 0)
        ]);
    }

    protected function getVisibleTicketSnapshot($ticketId)
    {
        $tickets = $this->TRS_model->get_visible_tickets_for_list(
            $this->getCurrentRoleId(),
            $this->getCurrentDepartmentId(),
            $this->getCurrentUserId(),
            null
        );

        foreach ($tickets as $ticket) {
            if ((int) ($ticket['ticket_id'] ?? 0) !== (int) $ticketId) {
                continue;
            }

            $ticket['tasks'] = $this->TRS_model->get_tasks_by_ticket($ticketId);
            $ticket['share_url'] = rtrim(base_url('TRS/view/' . $ticketId), '/');

            return $ticket;
        }

        return null;
    }

    protected function cleanMultilineInput($value)
    {
        $value = strip_tags((string) $value);
        $value = preg_replace("/\r\n|\r/", "\n", $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value);

        return trim($value);
    }

    protected function respondJsonWithStatus(array $payload, $statusCode = 200)
    {
        return $this->output
            ->set_status_header((int) $statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
