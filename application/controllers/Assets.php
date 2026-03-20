<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Assets extends MY_Controller
{
    private $qrImageTemplate = '';

    public function __construct()
    {
        parent::__construct();

        if ((int) $this->session->userdata('department_id') !== 2) {
            $this->session->set_flashdata('failed', 'Unauthorized');
            redirect('dashboard');
        }

        $this->load->model('Asset_model');
        $this->load->model('User_model');
        $this->load->config('project_support', true);

        $config = (array) $this->config->item('project_support');
        $this->qrImageTemplate = (string) ($config['qr_image_template'] ?? '');
    }

    private function getAssetStatuses()
    {
        return ['Working', 'Faulty', 'In Repair'];
    }

    private function getAssetFormData(array $extra = [])
    {
        $oldAsset = $this->populateSuggestedSerial((array) $this->session->flashdata('old_asset'));

        return array_merge([
            'assignable_users' => $this->User_model->get_active_users_for_select(),
            'departments' => $this->User_model->get_all_departments(),
            'status_options' => $this->getAssetStatuses(),
            'qr_image_template' => $this->qrImageTemplate,
            'asset_qr_base_url' => rtrim(base_url('qr-ticket/'), '/'),
            'asset_qr_preview_url' => site_url('assets/preview-qr'),
            'asset_qr_print_url' => site_url('assets/qr-print-center'),
            'asset_next_serial_url' => site_url('assets/next-serial'),
            'asset_manage_url' => site_url('assets/manage'),
            'old_asset' => $oldAsset,
        ], $extra);
    }

    private function setAssetOldInput(array $payload)
    {
        $this->session->set_flashdata('old_asset', $payload);
    }

    private function normalizeNullableInt($value)
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function normalizeAssetStatus($status)
    {
        $status = trim((string) $status);

        return in_array($status, $this->getAssetStatuses(), true) ? $status : 'Working';
    }

    private function getSeriesStep(array $input)
    {
        return max(1, (int) ($input['series_step'] ?? 1));
    }

    private function getAssetRouteKey($serialNumber, $qrCode = '')
    {
        $serialNumber = trim((string) $serialNumber);
        if ($serialNumber !== '') {
            return $serialNumber;
        }

        return trim((string) $qrCode);
    }

    private function getNextSerialValue($departmentId = null, $step = 1)
    {
        $step = max(1, (int) $step);
        $lastSerialNumber = $this->Asset_model->get_latest_serial_number($departmentId);

        if ($lastSerialNumber === '') {
            return '';
        }

        $nextSerialNumber = $this->incrementSeriesValue($lastSerialNumber, $step);

        if ($nextSerialNumber === '' || $nextSerialNumber === $lastSerialNumber) {
            return '';
        }

        return $nextSerialNumber;
    }

    private function populateSuggestedSerial(array $input)
    {
        if (trim((string) ($input['serial_number'] ?? '')) !== '') {
            return $input;
        }

        $nextSerialNumber = $this->getNextSerialValue(
            $this->resolveDepartmentId($input),
            $this->getSeriesStep($input)
        );

        if ($nextSerialNumber !== '') {
            $input['serial_number'] = $nextSerialNumber;
        }

        return $input;
    }

    private function getAssignedUserDepartmentId($assignedUserId)
    {
        $assignedUserId = (int) $assignedUserId;
        if ($assignedUserId <= 0) {
            return null;
        }

        $user = $this->db
            ->select('department_id')
            ->from('users')
            ->where('user_id', $assignedUserId)
            ->get()
            ->row();

        $departmentId = $user ? (int) ($user->department_id ?? 0) : 0;

        return $departmentId > 0 ? $departmentId : null;
    }

    private function resolveDepartmentId(array $input)
    {
        $departmentId = $this->normalizeNullableInt($input['department_id'] ?? null);
        if ($departmentId !== null) {
            return $departmentId;
        }

        return $this->getAssignedUserDepartmentId($this->normalizeNullableInt($input['assigned_user_id'] ?? null));
    }

    private function incrementSeriesValue($value, $step = 1)
    {
        $value = trim((string) $value);
        $step = max(1, (int) $step);

        if ($value === '') {
            return '';
        }

        if (!preg_match('/^(.*?)(\d+)$/', $value, $matches)) {
            return $value;
        }

        $prefix = (string) ($matches[1] ?? '');
        $number = (string) ($matches[2] ?? '');
        $nextNumber = str_pad((string) ((int) $number + $step), strlen($number), '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    private function buildContinuedSeriesInput(array $input)
    {
        $step = $this->getSeriesStep($input);
        $nextInput = $input;
        $nextInput['asset_name'] = $this->incrementSeriesValue($input['asset_name'] ?? '', $step);
        $nextInput['serial_number'] = $this->incrementSeriesValue($input['serial_number'] ?? '', $step);
        $nextInput['series_step'] = $step;
        $nextInput['continue_series'] = '1';
        $nextInput['qr_code'] = '';

        return $nextInput;
    }

    private function buildGeneratedQrCode($assetName, $serialNumber, $departmentId = null, $excludeAssetId = null)
    {
        $seed = trim((string) $serialNumber) !== '' ? $serialNumber : $assetName;
        $seed = strtoupper(trim((string) preg_replace('/[^A-Z0-9]+/i', '-', (string) $seed), '-'));
        $seed = $seed !== '' ? $seed : 'ASSET';
        $departmentId = $this->normalizeNullableInt($departmentId);
        $prefix = 'TRS-ASSET-';

        if ($departmentId !== null) {
            $prefix .= 'D' . $departmentId . '-';
        }

        $candidate = $prefix . $seed;
        $suffix = 1;

        while ($this->Asset_model->qr_code_exists($candidate, $excludeAssetId)) {
            $suffix++;
            $candidate = $prefix . $seed . '-' . $suffix;
        }

        return $candidate;
    }

    private function buildQrPayloadUrl($qrCode, $departmentId = null, $serialNumber = '')
    {
        $routeKey = $this->getAssetRouteKey($serialNumber, $qrCode);

        return rtrim(base_url('qr-ticket/' . rawurlencode((string) $routeKey)), '/');
    }

    private function buildQrImageUrl($payload, $size = 280)
    {
        if ($this->qrImageTemplate === '') {
            return '';
        }

        return str_replace(
            ['{size}', '{data}'],
            [rawurlencode((string) $size), rawurlencode((string) $payload)],
            $this->qrImageTemplate
        );
    }

    private function buildQrPreviewPayload($assetName, $serialNumber, $departmentId = null, $size = 280)
    {
        $assetName = trim((string) $assetName);
        $serialNumber = trim((string) $serialNumber);
        $departmentId = $this->normalizeNullableInt($departmentId);

        if ($assetName === '' && $serialNumber === '') {
            return [
                'success' => false,
                'message' => 'Asset name or serial number is required to generate a QR preview.',
            ];
        }

        if ($departmentId === null) {
            return [
                'success' => false,
                'message' => 'Department is required to generate a department-linked QR preview.',
            ];
        }

        $qrCode = $this->buildGeneratedQrCode($assetName, $serialNumber, $departmentId);
        $scanUrl = $this->buildQrPayloadUrl($qrCode, $departmentId, $serialNumber);

        return [
            'success' => true,
            'qr_code' => $qrCode,
            'scan_url' => $scanUrl,
            'preview_url' => $this->buildQrImageUrl($scanUrl, $size),
            'serial_number' => $serialNumber,
            'route_key' => $this->getAssetRouteKey($serialNumber, $qrCode),
            'department_id' => $departmentId,
        ];
    }

    private function ensureAssetQrDirectory()
    {
        $directory = FCPATH . 'assets/dist/img/asset-qr/';

        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        return $directory;
    }

    private function sanitizeFileSlug($value)
    {
        $value = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $value), '-'));

        return $value !== '' ? $value : 'asset-qr';
    }

    private function saveGeneratedQrImage($qrCode, $departmentId = null, $serialNumber = '')
    {
        $qrUrl = $this->buildQrImageUrl($this->buildQrPayloadUrl($qrCode, $departmentId, $serialNumber));
        if ($qrUrl === '') {
            return '';
        }

        $directory = $this->ensureAssetQrDirectory();
        if (!is_dir($directory)) {
            return '';
        }

        $curl = curl_init($qrUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $binary = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if (!is_string($binary) || $binary === '' || $httpCode >= 400) {
            return '';
        }

        $filename = $this->sanitizeFileSlug($qrCode) . '.png';
        $fullPath = $directory . $filename;

        if (@file_put_contents($fullPath, $binary) === false) {
            return '';
        }

        return 'assets/dist/img/asset-qr/' . $filename;
    }

    private function buildAssetFormInput()
    {
        return [
            'asset_name' => $this->input->post('asset_name', true),
            'asset_type' => $this->input->post('asset_type', true),
            'serial_number' => $this->input->post('serial_number', true),
            'location' => $this->input->post('location', true),
            'assigned_user_id' => $this->input->post('assigned_user_id', true),
            'department_id' => $this->input->post('department_id', true),
            'status' => $this->input->post('status', true),
            'series_step' => $this->input->post('series_step', true),
            'continue_series' => $this->input->post('continue_series', true),
            'qr_code' => $this->input->post('qr_code', true),
        ];
    }

    private function buildAssetFormInputFromAsset($asset)
    {
        return [
            'asset_name' => (string) ($asset->asset_name ?? ''),
            'asset_type' => (string) ($asset->asset_type ?? ''),
            'serial_number' => (string) ($asset->serial_number ?? ''),
            'location' => (string) ($asset->location ?? ''),
            'assigned_user_id' => (int) ($asset->assigned_user_id ?? 0),
            'department_id' => (int) ($asset->department_id ?? ($asset->effective_department_id ?? 0)),
            'status' => (string) ($asset->status ?? 'Working'),
            'series_step' => 1,
            'continue_series' => '',
            'qr_code' => (string) ($asset->qr_code ?? ''),
        ];
    }

    private function buildAssetInsertData(array $input, $generateQrImage = false, $forceGeneratedQrCode = false, $excludeAssetId = null)
    {
        $assetName = trim((string) ($input['asset_name'] ?? ''));
        $assetType = trim((string) ($input['asset_type'] ?? ''));
        $serialNumber = trim((string) ($input['serial_number'] ?? ''));
        $location = trim((string) ($input['location'] ?? ''));
        $qrCode = trim((string) ($input['qr_code'] ?? ''));
        $status = $this->normalizeAssetStatus($input['status'] ?? 'Working');
        $assignedUserId = $this->normalizeNullableInt($input['assigned_user_id'] ?? null);
        $departmentId = $this->resolveDepartmentId($input);
        $seriesStep = $this->getSeriesStep($input);

        if ($serialNumber === '') {
            $serialNumber = $this->getNextSerialValue($departmentId, $seriesStep);
        }

        if ($assetName === '' || $assetType === '') {
            return ['success' => false, 'message' => 'Asset name and asset type are required.'];
        }

        if ($serialNumber === '') {
            return ['success' => false, 'message' => 'Serial number is required. No previous numeric serial was found to continue automatically.'];
        }

        if ($departmentId === null) {
            return ['success' => false, 'message' => 'Department is required for QR asset generation.'];
        }

        if ($this->Asset_model->serial_number_exists($serialNumber, $excludeAssetId)) {
            return ['success' => false, 'message' => 'Serial number already exists: ' . $serialNumber];
        }

        if ($forceGeneratedQrCode || $qrCode === '') {
            $qrCode = $this->buildGeneratedQrCode($assetName, $serialNumber, $departmentId, $excludeAssetId);
        }

        if ($this->Asset_model->qr_code_exists($qrCode, $excludeAssetId)) {
            return ['success' => false, 'message' => 'QR code already exists: ' . $qrCode];
        }

        $data = [
            'asset_name' => $assetName,
            'asset_type' => $assetType,
            'serial_number' => $serialNumber,
            'qr_code' => $qrCode,
            'location' => $location !== '' ? $location : null,
            'assigned_user_id' => $assignedUserId,
            'department_id' => $departmentId,
            'status' => $status,
        ];

        if ($generateQrImage) {
            $data['qr_image_path'] = null;
            $generatedPath = $this->saveGeneratedQrImage($qrCode, $departmentId, $serialNumber);
            $data['qr_image_path'] = $generatedPath !== '' ? $generatedPath : null;
        }

        return [
            'success' => true,
            'data' => $data,
            'qr_code' => $qrCode,
            'serial_number' => $serialNumber,
            'scan_url' => $this->buildQrPayloadUrl($qrCode, $departmentId, $serialNumber),
        ];
    }

    private function normalizeSpreadsheetKey($key)
    {
        $key = strtolower(trim((string) $key));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim((string) $key, '_');
    }

    private function normalizeSpreadsheetRow(array $row)
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[$this->normalizeSpreadsheetKey($key)] = is_string($value) ? trim($value) : $value;
        }

        unset(
            $normalized['qr_code'],
            $normalized['qr_image'],
            $normalized['qr_image_path'],
            $normalized['generated_qr_code'],
            $normalized['ticket_link'],
            $normalized['scan_url']
        );

        if (!isset($normalized['assigned_user_id']) && !empty($normalized['assigned_user_email'])) {
            $user = $this->db
                ->select('user_id')
                ->from('users')
                ->where('email', strtolower(trim((string) $normalized['assigned_user_email'])))
                ->get()
                ->row();

            if ($user) {
                $normalized['assigned_user_id'] = (int) $user->user_id;
            }
        }

        if (!isset($normalized['department_id']) && !empty($normalized['department_name'])) {
            $department = $this->db
                ->select('department_id')
                ->from('departments')
                ->where('LOWER(department_name)', strtolower(trim((string) $normalized['department_name'])), false)
                ->get()
                ->row();

            if ($department) {
                $normalized['department_id'] = (int) $department->department_id;
            }
        }

        if (!isset($normalized['department_id']) && !empty($normalized['assigned_user_id'])) {
            $normalized['department_id'] = $this->getAssignedUserDepartmentId($normalized['assigned_user_id']);
        }

        return $normalized;
    }

    private function getBulkRowsJsonInput()
    {
        $rowsJson = trim((string) $this->input->post('rows_json'));
        if ($rowsJson !== '') {
            return $rowsJson;
        }

        $rawBody = trim((string) $this->input->raw_input_stream);
        if ($rawBody === '') {
            return '';
        }

        if ($rawBody[0] === '[' || $rawBody[0] === '{') {
            return $rawBody;
        }

        parse_str($rawBody, $parsedBody);

        return trim((string) ($parsedBody['rows_json'] ?? ''));
    }

    private function hydratePrintAsset(array $asset)
    {
        $qrCode = trim((string) ($asset['qr_code'] ?? ''));
        $serialNumber = trim((string) ($asset['serial_number'] ?? ''));
        $effectiveDepartmentId = $this->normalizeNullableInt($asset['effective_department_id'] ?? ($asset['department_id'] ?? null));
        $scanUrl = $qrCode !== '' ? $this->buildQrPayloadUrl($qrCode, $effectiveDepartmentId, $serialNumber) : '';
        $imageUrl = '';

        if (!empty($asset['qr_image_path'])) {
            $imageUrl = base_url((string) $asset['qr_image_path']);
        } elseif ($scanUrl !== '') {
            $imageUrl = $this->buildQrImageUrl($scanUrl, 280);
        }

        $asset['scan_url'] = $scanUrl;
        $asset['qr_image_url'] = $imageUrl;
        $asset['display_serial_number'] = $serialNumber !== '' ? $serialNumber : (string) ($asset['qr_code'] ?? '');
        $asset['route_key'] = $this->getAssetRouteKey($serialNumber, $qrCode);

        return $asset;
    }

    public function create()
    {
        $this->setPageAssets(
            ['assets/dist/css/pages/assets.css'],
            [
                'assets/dist/js/pages/asset-ui.js',
                'assets/dist/js/pages/asset-create.js',
            ]
        );
        $this->render('Assets/Create', $this->getAssetFormData([
            'form_mode' => 'create',
            'form_action' => base_url('assets/store'),
            'form_submit_label' => 'Save Asset',
            'form_continue_label' => 'Save + Continue Series',
            'page_title' => 'Single Asset + QR Entry',
            'page_description' => 'Type the asset details once, generate a department-linked QR, and continue the series for the next serial in one flow.',
        ]));
    }

    public function preview_qr()
    {
        $size = (int) $this->input->get('size');
        if ($size <= 0) {
            $size = 280;
        }

        $payload = $this->buildQrPreviewPayload(
            $this->input->get('asset_name', true),
            $this->input->get('serial_number', true),
            $this->input->get('department_id', true),
            $size
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    public function next_serial()
    {
        $departmentId = $this->normalizeNullableInt($this->input->get('department_id', true));
        $step = max(1, (int) $this->input->get('step'));
        $serialNumber = $this->getNextSerialValue($departmentId, $step);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $serialNumber !== '',
                'serial_number' => $serialNumber,
                'message' => $serialNumber !== '' ? '' : 'No previous numeric serial was found for auto-increment.',
            ]));
    }

    public function manage()
    {
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'department_id' => $this->normalizeNullableInt($this->input->get('department_id', true)),
            'asset_type' => trim((string) $this->input->get('asset_type', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'limit' => min(300, max(50, (int) ($this->input->get('limit', true) ?: 150))),
        ];

        $this->setPageAssets(
            ['assets/dist/css/pages/assets.css'],
            ['assets/dist/js/pages/asset-ui.js']
        );

        $this->render('Assets/Manage', $this->getAssetFormData([
            'filters' => $filters,
            'manage_assets' => array_map([$this, 'hydratePrintAsset'], $this->Asset_model->get_manage_assets($filters)),
            'print_department_options' => $this->Asset_model->get_print_department_options(),
            'print_asset_type_options' => $this->Asset_model->get_print_asset_type_options(),
        ]));
    }

    public function edit($assetId = null)
    {
        $asset = $this->Asset_model->get_by_id((int) $assetId);

        if (!$asset) {
            $this->session->set_flashdata('failed', 'Asset record not found.');
            redirect('assets/manage');
        }

        $oldAsset = (array) $this->session->flashdata('old_asset');
        if (empty($oldAsset)) {
            $oldAsset = $this->buildAssetFormInputFromAsset($asset);
        }

        $this->setPageAssets(
            ['assets/dist/css/pages/assets.css'],
            [
                'assets/dist/js/pages/asset-ui.js',
                'assets/dist/js/pages/asset-create.js',
            ]
        );

        $this->render('Assets/Create', $this->getAssetFormData([
            'old_asset' => $oldAsset,
            'form_mode' => 'edit',
            'form_action' => base_url('assets/update/' . (int) $asset->asset_id),
            'form_submit_label' => 'Update Asset',
            'form_continue_label' => '',
            'page_title' => 'Edit Asset + QR Record',
            'page_description' => 'Update asset details from one place. QR route keeps showing the serial number at the end of the link.',
            'editing_asset' => $asset,
        ]));
    }

    public function store()
    {
        $oldInput = $this->buildAssetFormInput();

        $this->setAssetOldInput($oldInput);

        $result = $this->buildAssetInsertData($oldInput, true, true);
        if (empty($result['success'])) {
            $this->session->set_flashdata('failed', (string) ($result['message'] ?? 'Unable to add asset.'));
            redirect('assets/create');
        }

        $assetId = $this->Asset_model->insert_asset($result['data']);
        if (!$assetId) {
            $this->session->set_flashdata('failed', 'Unable to save asset right now.');
            redirect('assets/create');
        }

        if ($this->input->post('continue_series', true) === '1') {
            $continuedInput = $this->buildContinuedSeriesInput(array_merge($oldInput, [
                'serial_number' => (string) ($result['serial_number'] ?? ($result['data']['serial_number'] ?? '')),
            ]));
            $this->setAssetOldInput($continuedInput);
            $this->session->set_flashdata('success', 'Asset added successfully. Series is ready for the next serial number.');
            redirect('assets/create');
        }

        $this->setAssetOldInput([]);
        $this->session->set_flashdata('success', 'Asset added successfully with QR code ' . $result['qr_code'] . '.');
        redirect('assets/create');
    }

    public function update($assetId = null)
    {
        $assetId = (int) $assetId;
        $asset = $this->Asset_model->get_by_id($assetId);

        if (!$asset) {
            $this->session->set_flashdata('failed', 'Asset record not found.');
            redirect('assets/manage');
        }

        $oldInput = $this->buildAssetFormInput();
        $this->setAssetOldInput($oldInput);

        $result = $this->buildAssetInsertData($oldInput, true, true, $assetId);
        if (empty($result['success'])) {
            $this->session->set_flashdata('failed', (string) ($result['message'] ?? 'Unable to update asset.'));
            redirect('assets/edit/' . $assetId);
        }

        if (!$this->Asset_model->update_asset($assetId, $result['data'])) {
            $this->session->set_flashdata('failed', 'Unable to update asset right now.');
            redirect('assets/edit/' . $assetId);
        }

        $this->setAssetOldInput([]);
        $this->session->set_flashdata('success', 'Asset updated successfully.');
        redirect('assets/manage');
    }

    public function delete($assetId = null)
    {
        if (strtoupper((string) $this->input->method(true)) !== 'POST') {
            show_404();
        }

        $assetId = (int) $assetId;
        $asset = $this->Asset_model->get_by_id($assetId);

        if (!$asset) {
            $this->session->set_flashdata('failed', 'Asset record not found.');
            redirect('assets/manage');
        }

        $linkedTicketCount = $this->Asset_model->count_tickets_using_asset($assetId);
        if ($linkedTicketCount > 0) {
            $this->session->set_flashdata('failed', 'This asset is already linked to ticket records, so it cannot be deleted.');
            redirect('assets/manage');
        }

        if (!$this->Asset_model->delete_asset($assetId)) {
            $this->session->set_flashdata('failed', 'Unable to delete asset right now.');
            redirect('assets/manage');
        }

        $this->session->set_flashdata('success', 'Asset deleted successfully.');
        redirect('assets/manage');
    }

    public function bulk_upload()
    {
        $this->setPageAssets(
            ['assets/dist/css/pages/assets.css'],
            [
                'assets/plugins/xlsx/xlsx.full.min.js',
                'assets/dist/js/pages/asset-ui.js',
                'assets/dist/js/pages/asset-bulk-upload.js',
            ]
        );

        $this->render('Assets/Bulk_upload', $this->getAssetFormData([
            'asset_import_template_url' => base_url('assets/templates/asset_bulk_template.csv'),
        ]));
    }

    public function qr_print_center()
    {
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'department_id' => $this->normalizeNullableInt($this->input->get('department_id', true)),
            'asset_type' => trim((string) $this->input->get('asset_type', true)),
            'serial_number' => trim((string) $this->input->get('serial_number', true)),
        ];
        $copies = (int) $this->input->get('copies');

        $this->setPageAssets(
            ['assets/dist/css/pages/assets.css'],
            [
                'assets/dist/js/pages/asset-ui.js',
                'assets/dist/js/pages/asset-qr-print.js',
            ]
        );

        $this->render('Assets/Qr_print_center', $this->getAssetFormData([
            'filters' => $filters,
            'print_assets' => array_map([$this, 'hydratePrintAsset'], $this->Asset_model->get_print_assets($filters)),
            'print_department_options' => $this->Asset_model->get_print_department_options(),
            'print_asset_type_options' => $this->Asset_model->get_print_asset_type_options(),
            'default_print_copies' => $copies > 0 ? min($copies, 20) : 1,
        ]));
    }

    public function store_bulk()
    {
        $rowsJson = $this->getBulkRowsJsonInput();
        if ($rowsJson === '') {
            $this->session->set_flashdata('failed', 'Please choose an Excel or CSV file first.');
            redirect('assets/bulk-upload');
        }

        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) {
            $rows = json_decode(html_entity_decode(stripslashes($rowsJson), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
        }

        if (!is_array($rows) || empty($rows)) {
            $this->session->set_flashdata('failed', 'Uploaded spreadsheet could not be parsed.');
            redirect('assets/bulk-upload');
        }

        $isSequential = array_keys($rows) === range(0, count($rows) - 1);
        if (!$isSequential) {
            $rows = [$rows];
        }

        $successCount = 0;
        $errorRows = [];
        $rowNumber = 2;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $errorRows[] = 'Row ' . $rowNumber . ': invalid data format.';
                $rowNumber++;
                continue;
            }

            $normalized = $this->normalizeSpreadsheetRow($row);
            $result = $this->buildAssetInsertData($normalized, true, true);

            if (empty($result['success'])) {
                $errorRows[] = 'Row ' . $rowNumber . ': ' . $result['message'];
                $rowNumber++;
                continue;
            }

            if ($this->Asset_model->insert_asset($result['data'])) {
                $successCount++;
            } else {
                $errorRows[] = 'Row ' . $rowNumber . ': insert failed.';
            }

            $rowNumber++;
        }

        if ($successCount > 0) {
            $this->session->set_flashdata('success', $successCount . ' asset(s) uploaded successfully.');
        }

        if (!empty($errorRows)) {
            $this->session->set_flashdata('failed', implode('<br>', array_slice($errorRows, 0, 12)));
        }

        redirect('assets/bulk-upload');
    }
}
