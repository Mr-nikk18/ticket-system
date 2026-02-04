<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function index()
{
    if(!$this->session->userdata('is_login')){
        redirect('verify');
    }

   //Header
   $this->load->model('Menu_model');
$this->menu_data = $this->Menu_model
    ->get_menus_by_role(
        $this->session->userdata('role_id')
    );


    // Load models
    $this->load->model('Dashboard_model');
    $this->load->model('TRS_model');

    // Modules list
    $data['modules'] = $this->Dashboard_model
        ->get_modules_by_role(
            $this->session->userdata('role_id')
        );

      
    // Ticket counts

$role_id = $this->session->userdata('role_id');
$user_id = $this->session->userdata('user_id');

$data['open_count'] = $this->TRS_model->get_status_count($role_id,$user_id,'open');
$data['in_process_count'] = $this->TRS_model->get_status_count($role_id,$user_id,'in_progress');
$data['resolved_count'] = $this->TRS_model->get_status_count($role_id,$user_id,'resolved');
$data['closed_count'] = $this->TRS_model->get_status_count($role_id,$user_id,'closed');



    // Recent tickets
 $data['recent_tickets'] =
   $this->TRS_model->get_recent_tickets($role_id,$user_id);

    $data['menus'] = $this->menu_data;

    $this->load->view('Same_pages/Dashboard', $data);
}

}
