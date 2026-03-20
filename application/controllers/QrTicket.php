<?php
defined('BASEPATH') or exit('No direct script access allowed');

class QrTicket extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }

    private function getRequestedQrCode($qr_code = '')
    {
        if ($qr_code !== '') {
            return trim(rawurldecode((string) $qr_code));
        }

        return trim((string) $this->input->get('code', true));
    }

    private function getQrTicketFormRoute($routeKey, $department_id = null)
    {
        return 'qr-ticket/form/' . rawurlencode((string) $routeKey);
    }

    public function start($qr_code = '')
    {
        $qr_code = $this->getRequestedQrCode($qr_code);

        if ($qr_code === '') {
            $this->session->set_flashdata('failed', 'QR code is missing or invalid.');
            redirect($this->session->userdata('is_login') ? 'dashboard' : 'verify');
        }

        $form_route = $this->getQrTicketFormRoute($qr_code, $this->input->get('department_id', true));

        if ($this->session->userdata('is_login') === true) {
            redirect($form_route);
        }

        $this->session->set_userdata('post_login_redirect', $form_route);
        $this->session->set_flashdata('failed', 'Please login first to continue with the QR scanned ticket.');
        redirect('verify');
    }
}
