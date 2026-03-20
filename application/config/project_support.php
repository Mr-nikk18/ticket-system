<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$projectSupportApiKey = getenv('OPENAI_API_KEY');
$projectSupportModel = getenv('OPENAI_MODEL');
$projectSupportBaseUrl = getenv('OPENAI_BASE_URL');
$projectSupportProject = getenv('OPENAI_PROJECT');
$projectSupportOrganization = getenv('OPENAI_ORGANIZATION');
$projectSupportQrTemplate = getenv('TRS_QR_IMAGE_TEMPLATE');

$config['openai_api_key'] = $projectSupportApiKey ? trim($projectSupportApiKey) : '';
$config['openai_model'] = $projectSupportModel ? trim($projectSupportModel) : 'gpt-5-mini';
$config['openai_base_url'] = $projectSupportBaseUrl ? rtrim(trim($projectSupportBaseUrl), '/') : 'https://api.openai.com/v1';
$config['openai_project'] = $projectSupportProject ? trim($projectSupportProject) : '';
$config['openai_organization'] = $projectSupportOrganization ? trim($projectSupportOrganization) : '';
$config['openai_timeout_seconds'] = 45;
$config['openai_max_output_tokens'] = 700;
$config['qr_image_template'] = $projectSupportQrTemplate
    ? trim($projectSupportQrTemplate)
    : 'https://api.qrserver.com/v1/create-qr-code/?size={size}x{size}&data={data}';
