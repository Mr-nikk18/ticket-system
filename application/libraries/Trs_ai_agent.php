<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Trs_ai_agent
{
    protected $CI;
    protected $settings = [];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('project_support', true);
        $this->settings = (array) $this->CI->config->item('project_support');
    }

    public function isConfigured()
    {
        return trim((string) $this->getSetting('openai_api_key', '')) !== '';
    }

    public function getConfiguredModel()
    {
        return trim((string) $this->getSetting('openai_model', 'gpt-5-mini'));
    }

    public function createSupportDraft(array $payload)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status_code' => 503,
                'message' => 'OpenAI is not configured yet. Add OPENAI_API_KEY on the server to enable AI support.'
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'PHP cURL is required for AI support.'
            ];
        }

        $requestBody = [
            'model' => $this->getConfiguredModel(),
            'instructions' => $this->buildInstructions($payload),
            'input' => $this->buildInput($payload),
            'max_output_tokens' => (int) $this->getSetting('openai_max_output_tokens', 700),
        ];

        $response = $this->postJson(
            rtrim((string) $this->getSetting('openai_base_url', 'https://api.openai.com/v1'), '/') . '/responses',
            $requestBody
        );

        if (!$response['success']) {
            return $response;
        }

        $text = $this->extractOutputText($response['data']);
        if ($text === '') {
            return [
                'success' => false,
                'status_code' => 502,
                'message' => 'OpenAI returned a response, but no readable text was found.'
            ];
        }

        return [
            'success' => true,
            'status_code' => 200,
            'model' => (string) ($response['data']['model'] ?? $requestBody['model']),
            'content' => $text,
            'raw' => $response['data']
        ];
    }

    protected function buildInstructions(array $payload)
    {
        $action = (string) ($payload['action'] ?? 'triage_summary');
        $tone = (string) ($payload['tone'] ?? 'balanced');

        $actionInstructions = [
            'triage_summary' => 'Create a concise support triage summary with sections: Issue summary, likely impact, missing information, and next best actions.',
            'customer_reply' => 'Draft a customer-safe support reply. Be clear, calm, and helpful. Do not promise timelines or fixes that are not supported by the input.',
            'developer_handoff' => 'Write an internal developer handoff with sections: Problem, observed evidence, suspected cause, and recommended next implementation steps.',
            'resolution_steps' => 'Produce step-by-step resolution guidance that an internal support teammate can follow. Keep steps practical and ordered.',
            'qa_checklist' => 'Create a QA checklist that can be used to verify the issue, the fix, and regression coverage.'
        ];

        $toneInstructions = [
            'balanced' => 'Use a professional and practical tone.',
            'concise' => 'Be compact and direct.',
            'detailed' => 'Provide more detail where it reduces ambiguity.',
            'executive' => 'Keep it high-level and decision-oriented.'
        ];

        $actionInstruction = isset($actionInstructions[$action]) ? $actionInstructions[$action] : $actionInstructions['triage_summary'];
        $toneInstruction = isset($toneInstructions[$tone]) ? $toneInstructions[$tone] : $toneInstructions['balanced'];

        return implode("\n", [
            'You are the TRS Project Support AI agent for an internal ticketing and support workflow.',
            'Work only from the information provided. If something is missing, say that it is an assumption or missing detail.',
            'Do not invent policies, dates, ticket states, user actions, or root causes.',
            'Prefer clear headings, bullets, and short paragraphs over long prose.',
            $actionInstruction,
            $toneInstruction
        ]);
    }

    protected function buildInput(array $payload)
    {
        $lines = [];
        $ticket = isset($payload['ticket']) && is_array($payload['ticket']) ? $payload['ticket'] : [];

        $lines[] = 'Requested output: ' . (string) ($payload['action'] ?? 'triage_summary');
        $lines[] = 'Tone: ' . (string) ($payload['tone'] ?? 'balanced');

        if (!empty($payload['title'])) {
            $lines[] = 'Title: ' . (string) $payload['title'];
        }

        if (!empty($payload['description'])) {
            $lines[] = 'Description:';
            $lines[] = (string) $payload['description'];
        }

        if (!empty($ticket['ticket_id'])) {
            $lines[] = 'Ticket ID: ' . (int) $ticket['ticket_id'];
        }

        if (!empty($ticket['status_id'])) {
            $lines[] = 'Status ID: ' . (int) $ticket['status_id'];
        }

        if (!empty($ticket['department_name'])) {
            $lines[] = 'Department: ' . (string) $ticket['department_name'];
        }

        if (!empty($ticket['assigned_engineer_name'])) {
            $lines[] = 'Assigned engineer: ' . (string) $ticket['assigned_engineer_name'];
        }

        if (!empty($ticket['share_url'])) {
            $lines[] = 'Ticket URL: ' . (string) $ticket['share_url'];
        }

        if (!empty($ticket['tasks']) && is_array($ticket['tasks'])) {
            $lines[] = 'Tasks:';
            foreach ($ticket['tasks'] as $task) {
                $taskTitle = trim((string) ($task['task_title'] ?? ''));
                if ($taskTitle === '') {
                    continue;
                }
                $lines[] = '- ' . $taskTitle . ((int) ($task['is_completed'] ?? 0) === 1 ? ' [completed]' : ' [pending]');
            }
        }

        if (!empty($payload['context'])) {
            $lines[] = 'Additional context:';
            $lines[] = (string) $payload['context'];
        }

        return trim(implode("\n", $lines));
    }

    protected function postJson($url, array $body)
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to initialize OpenAI request.'
            ];
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim((string) $this->getSetting('openai_api_key', ''))
        ];

        $organization = trim((string) $this->getSetting('openai_organization', ''));
        if ($organization !== '') {
            $headers[] = 'OpenAI-Organization: ' . $organization;
        }

        $project = trim((string) $this->getSetting('openai_project', ''));
        if ($project !== '') {
            $headers[] = 'OpenAI-Project: ' . $project;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => (int) $this->getSetting('openai_timeout_seconds', 45),
        ]);

        $rawBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            return [
                'success' => false,
                'status_code' => 502,
                'message' => $curlError !== '' ? $curlError : 'OpenAI request failed.'
            ];
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'status_code' => 502,
                'message' => 'OpenAI returned an invalid JSON response.'
            ];
        }

        if ($httpCode >= 400) {
            $apiMessage = (string) ($decoded['error']['message'] ?? 'OpenAI request failed.');
            return [
                'success' => false,
                'status_code' => $httpCode,
                'message' => $apiMessage,
                'data' => $decoded
            ];
        }

        return [
            'success' => true,
            'status_code' => $httpCode > 0 ? $httpCode : 200,
            'data' => $decoded
        ];
    }

    protected function extractOutputText(array $response)
    {
        $outputText = trim((string) ($response['output_text'] ?? ''));
        if ($outputText !== '') {
            return $outputText;
        }

        $fragments = [];
        $outputItems = isset($response['output']) && is_array($response['output']) ? $response['output'] : [];

        foreach ($outputItems as $item) {
            $contentItems = isset($item['content']) && is_array($item['content']) ? $item['content'] : [];
            foreach ($contentItems as $contentItem) {
                if (($contentItem['type'] ?? '') === 'output_text' && isset($contentItem['text'])) {
                    $fragments[] = (string) $contentItem['text'];
                }
            }
        }

        return trim(implode("\n\n", array_filter($fragments, 'strlen')));
    }

    protected function getSetting($key, $default = null)
    {
        return array_key_exists($key, $this->settings) ? $this->settings[$key] : $default;
    }
}
