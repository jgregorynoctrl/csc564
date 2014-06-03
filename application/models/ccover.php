<?php

class Ccover extends CI_Model {

    var $file   = '';
    var $error  = '';
    var $processed_file = '';
    var $rules = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    /*
     * read in file and find canonical cover
     */
    public function process($data)
    {
        $status = $this->validateFile($data);
        if($status){
          $this->file = read_file($data['full_path']);
          $this->splitdata();
          $this->process_lines();
          if(isset($this->processed_file['rules']) && !empty($this->processed_file['rules']))
          {
              $this->rules = $this->processed_file['rules'];
          }
          return $this->rules;
        }
        return $data['error'] = $this->error;
    }
    
    /*
     *  the do_upload file takes care of alot of errors for us, but lets make sure
     *  we have some content and validate anything else
     */
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
    
    /*
     *  breaks file into array for each line
     */
    private function splitdata()
    {
        if(!empty($this->file))
        {
            $this->file = explode("\r\n",$this->file);
        }
    }
    
    /*
     *  process the data file to split attributes and rules
     */
    private function process_lines()
    {
        // initiate file var
        $file = $this->file;
        
        //if the data is empty and not in array format
        if(empty($file) && !is_array($file)){
            return;
        }
        
        // set up variables to assign rows to
        $attributes = array(); // 1
        $rules = array(); // 0
        $flag = 0;
        
        // iterate over each line
        foreach($file as $row => $value)
        {
            // if we hit a number change which array we are creating
            if(is_numeric($value))
            {
                $flag = !$flag;
                unset($row);
            }else{
                ($flag)? $attributes[] = $value : $rules[] = $value;
            }
        }
        
        // process each attribute and rules arrays
        $attributes = $this->process_attrs($attributes);
        $rules = $this->process_rules($rules);
        
        $data = array(
            'attributes' => $attributes,
            'rules' => $rules
        );
        
        // set the processed file variable
        $this->processed_file = $data;
    }
    
    /*
     *  create the attribute array
     */
    private function process_attrs($arg)
    {
        $attributes = array();      
        if(!empty($arg)){
            foreach($arg as $row => $value){
                // split the attribute and values
                $line = explode(' ',trim($value));
                $attr = $line[0];
                unset($line[0]);
                
                // build attr => value array
                foreach($line as $r => $q){
                    $attributes[$attr][] =$q;
                }
            }
        }
        return $attributes;    
    }
    
    /*
     * create the rules array
     */
    private function process_rules($arg)
    {
       $rules = array(); 
       if(!empty($arg)){ 
           foreach($arg as $row => $value){
               
                // split the line to create our left and right side
                $line = explode('==',trim($value)); 
                
                // run the reflexivity rule here
                $rule = $this->reflexivity($line);
                
                // build the rules array
                $rules[trim($rule[0])] = trim($rule[1]);
            }
        }
        
        return $rules;
    }
    
    /*
     *  Perform Axiom of Reflexivity
     */
    private function reflexivity($rule)
    {
        // if we have an empty rule set
        if(empty($rule)){
            return;
        }
        
        // create the left and right side arrays of the rule
        $left_side = explode(' ',trim($rule[0]));
        $right_side = explode(' ',trim($rule[1]));
        
        // check for matches on both sides 
        $matches = array_intersect($left_side, $right_side);
        
        //if there are matches, remove them from both sides
        if($matches){
           foreach($matches as $key => $match){
            if(($key = array_search($match, $left_side)) !== false) {
                unset($left_side[$key]);
            }
            if(($key = array_search($match, $right_side)) !== false) {
                unset($right_side[$key]);
            }   
           }
           
           //recreate the rule array
           $rule = array();
           $rule[] = implode(' ',$left_side);
           $rule[] = implode(' ',$right_side);
        }
        
        //return rule
        return $rule;
    }
    
    
}

