<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Process extends CI_Controller {

    	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url','file'));
	}
	public function index()
	{
		$this->load->view('process/default', array('error' => ' ' ));
	}
        
        public function do_upload()
	{
		$config['upload_path'] = 'uploads/';
		$config['allowed_types'] = 'txt';

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload())
		{
			$error = array('error' => $this->upload->display_errors());

			$this->load->view('process/default', $error);
		}
		else
		{
                        //load our Canonical Cover Model
                        $this->load->model('Ccover');
                    
			$data = array('upload_data' => $this->upload->data());
                        
                        $data = $this->Ccover->proccess($data);

			$this->load->view('process/success', $data);
		}
	}
}
