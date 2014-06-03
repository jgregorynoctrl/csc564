<?php

class Ccover extends CI_Model {

    var $file   = '';
    var $error  = '';
    var $processed_file = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    //read in file 
    public function process($data)
    {
        $status = $this->validateFile($data);
        if($status){
          $this->file = read_file($data['full_path']);
          $this->splitdata();
          $this->process_lines();
          return $this->processed_file;
        }
        return $data['error'] = $this->error;
    }
    
    // the do_upload file takes care of alot of errors for us, but lets make sure
    // we have some content and validate anything else
    private function validateFile($data)
    {
        if(empty($data) OR !is_array($data)){
            $this->error = 'An Error Occurred uploading the file, please try again.';
            return false;
        }
        if(isset($data['file_size']) && $data['file_size'] < 0 ){
            $this->error = 'The file uploded is empty.';
            return false;
        }
        return true;
       
    }
    
    // breaks file into array for each line
    private function splitdata()
    {
        if(!empty($this->file))
        {
            $this->file = explode("\r\n",$this->file);
        }
    }
    
    // process the data file to split attributes and rules
    private function process_lines()
    {
        $file = $this->file;
        
        //if the data is empty and not in array format
        if(empty($file) && !is_array($file)){
            return;
        }
        
        $attributes = array(); // 1
        $rules = array(); // 0
        $flag = 0;
        
        foreach($file as $row => $value)
        {
            if(is_numeric($value))
            {
                $flag = !$flag;
                unset($row);
            }else{
                ($flag)? $attributes[] = $value : $rules[] = $value;
            }
        }
        
        $attributes = $this->process_attrs($attributes);
        $rules = $this->process_rules($rules);
        
        $data = array(
            'attributes' => $attributes,
            'rules' => $rules
        );
        $this->processed_file = $data;
    }
    
    // process the attribute array
    private function process_attrs($arg)
    {
        $attributes = array();
        
        if(!empty($arg)){

            foreach($arg as $row => $value){
                $line = explode(' ',trim($value));
                $attr = $line[0];
                unset($line[0]);
                foreach($line as $r => $q){
                    $attributes[$attr][] =$q;
                }
            }
        }
        return $attributes;    
    }
    
    //process the rules array
    private function process_rules($arg)
    {
       $rules = array(); 
       if(!empty($arg)){
           foreach($arg as $row => $value){
                $line = explode('==',trim($value));           
                $attr = explode(' ',trim($line[0]));               
                $rule = explode(' ',trim($line[1]));         
                $rules[trim($line[0])] = trim($line[1]);
            }
        }
        
        return $rules;
    }
}

