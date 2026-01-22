<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Developer extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // login check
        if (!$this->session->userdata('is_login')) {
            redirect('login');
        }

        $this->load->model('Developer_model');
    }

    public function developer_performance()
    {
        $data['developers'] = $this->Developer_model->getDeveloperPerformance();

        
        $this->load->view('Same_pages/developer_performance', $data);
        
    }
public function developerLeaveTicket($ticket_id)
{
    $developer_id = $this->session->userdata('user_id');

    // Update ticket
    $this->db->where('ticket_id', $ticket_id)
             ->update('tickets', [
                 'assigned_engineer_id' => NULL,
                 'status' => 'Open'
             ]);

    // Log history via model
    $this->load->model('Ticket_model');
    $this->Ticket_model->addHistory([
        'ticket_id'    => $ticket_id,
        'developer_id' => $developer_id,
        'action'       => 'left',
        'action_by'    => $developer_id,
        'remarks'      => 'Developer left the ticket'
    ]);

    $this->session->set_flashdata('success', 'You have left the ticket');
    redirect('Developer/ticket');
}



}
