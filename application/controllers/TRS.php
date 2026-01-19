<?php  

class TRS extends My_Controller{
 public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // 🔐 Login protection (sab methods ke liye)
        if ($this->session->userdata('is_login') !== true) {
            redirect('Auth/index');
        }
    }


public function see(){
    
   $this->load->view('Users/Add_ticket');
}

    public function list(){
        $user_id=$this->session->userdata('user_id');
        $this->load->model('TRS_model');
        $data['val']=$this->TRS_model->show($user_id);
        $this->load->view('Users/List',$data);
    }
  

public function edit($ticket_id){
   $this->load->model('TRS_model');
   $data['value']=$this->TRS_model->get_data_by_id($ticket_id);
   $this->load->view('Users/Edit',$data);
}


  public function update($ticket_id){

  $arr=array(
      'user_id'=>$this->session->userdata('user_id'),
      'title'=>$this->input->post('title'),
      'description'=>$this->input->post('description')
  );


  $this->load->model('TRS_model');
  $this->TRS_model->update_data($ticket_id,$arr);
  redirect('TRS/List');
        
    }
    public function delete($id){
   $this->load->model('TRS_model');
   $this->TRS_model->get_data_by_id($id);

   $this->db->where('ticket_id',$id);
   $this->db->delete('tickets');

   redirect('TRS/List');
    }

    public function add(){
        
         $arr=array(
            'user_id'=>$this->session->userdata('user_id'),
            'title'=>$this->input->post('title'),
            'description'=>$this->input->post('description')
         );
        

        $this->load->model('TRS_model');
        $this->TRS_model->insertdata($arr);
        redirect('TRS/List');
    }

public function setformdata(){
    $arr=array(
    'title'=>$this->input->post('title'),
    'description'=>$this->input->post('description'),
    'created_at'=> date('Y-m-d H:i:s')
    );

    $this->load->model('TRS_model');
    $this->TRS_model->insert_ticket_form_data($arr);
    redirect('List');
}


}

?>