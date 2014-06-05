<?php

class Ccover extends CI_Model {

    var $file   = '';
    var $error  = '';
    var $processed_file = '';
    var $rules = '';
    var $attributes = '';

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
              $this->attributes = $this->processed_file['attributes'];
              
              // re-structure and sort rules by highest on left side by least,
              //  and separating the rules of more than on value on the right
              $this->rules = $this->sort_rules($this->rules);

              $this->canonical_cover();
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
            //remove duplicates before creating array
           $arg = array_unique($arg);
            
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
            //remove duplicates before creating array
           $arg = array_unique($arg);
           
           foreach($arg as $row => $value){
               
                // split the line to create our left and right side
                $rule = explode('==',trim($value)); 
                
                // run the reflexivity rule here
                $rule = $this->aug_reflex($rule);
                
                if($rule){      
                    // build the rules array
                    $rules[][trim($rule[0])] = trim($rule[1]);
                }
                
            }
        }
        
        return $rules;
    }
    
    
    /*
     *  Perform Axiom of augmentation and
     *  Perform Axiom of reflexivity
     *  A,B,C -> C,D reduces to A,B,C -> D
     *  or if A,B,C -> C then remove rule
     */
    private function aug_reflex($rule)
    {
        // if we have an empty rule set
        if (empty($rule)) {
            return;
        }

        // create the left and right side arrays of the rule
        $left_side = explode(' ', trim($rule[0]));
        $right_side = explode(' ', trim($rule[1]));

        // check for matches on both sides 
        $matches = array_intersect($left_side, $right_side);

        //if there are matches
        if ($matches) {

            foreach ($matches as $key => $match) {
                if (($key = array_search($match, $right_side)) !== false) {

                    //remove from both sides if left and right are equal in attribute count  
                    if (count($left_side) == count($right_side)) {
                        unset($left_side[$key]);
                        unset($right_side[$key]);
                    } else {
                        unset($right_side[$key]);
                    }
                }
            }

            //recreate the rule array
            $rule = array();
            $rule[] = implode(' ', $left_side);
            $rule[] = implode(' ', $right_side);
        }

        // if we removed everything on the right side
        if (empty($right_side)) {
            return 0;
        }

        //return rule
        return $rule;
    }
    
    /*
     *  The Conanical Cover function
     *  F = $this->rules
     */
    private function canonical_cover()
    {       
        // store the rules in f
        $f = $this->rules;
        
        // loop over whole list of F
        foreach ($f as $a => $b) {

            // for each array key ,now we are working with the rule $a->$d
            foreach ($b as $c => $d) {
                // iniate some variables for later
                $subset = 0;
                $changed = 1;
                $result = explode(' ', trim($a));
                $rule = explode(' ', trim(trim($a) . ' ' . trim($d)));

                // while their are still rules being added to the result set
                // and $d is a subset
                while (($changed != 0) && !$subset) {
                    
                    // keep track of result set count
                    $result_cmp = count($result);

                    // loop again over whole set
                    $z = $f;
                    foreach ($z as $q => $w) {
                        // loop over array values to get specific rule
                        foreach ($w as $j => $k) {
                            //iniate variable of rule to check against $rule with
                            $rule_2 = explode(' ', trim(trim($q) . ' ' . trim($k)));

                            // don't compare the same rule
                            if (($rule != $rule_2)) {
                                
                                // check if q is subset of result
                                $q_chk = explode(' ', trim($q));
                                $rule2_subset = $this->compare_arrays($result, $q_chk);
                                
                                // is a subset
                                if ($rule2_subset) {
                                    
                                    // add k to result
                                    $hasvalue = in_array(trim($k), $result);
                                    if (!$hasvalue) {
                                        $result[] = trim($k);
                                    }

                                    // check if d is subset of result, if yes drop out of loop
                                    // and remove d from f
                                    $d_chk = explode(' ', trim($d));
                                    $rule_subset = $this->compare_arrays($result, $d_chk);

                                    if ($rule_subset) {
                                        unset($f[$a][$c]);
                                        if (empty($f[$a])) {
                                            unset($f[$a]);
                                        }
                                        $subset = 1;
                                        break;
                                    }
                                    if ($subset)
                                        break;
                                }
                                if ($subset)
                                    break;
                            }
                            if ($subset)
                                break;
                        }
                        if ($subset)
                            break;
                    }
                    // check if result increased, otherwise fall out of loop
                    $changed = ($result_cmp != count($result)) ? 1 : 0;

                    // fall out of loop if rule is subset
                    if ($subset)
                        break;
                }
            }
        }
        // save canonical cover to rules
        $this->rules = $f;
    }

    /*
     * Checks is b is a subset of a
     * @a  = array 
     * @b  = array 
     * returns true if  b is a subset of a or false if not
     */
    private function compare_arrays($a = array(), $b = array())
    {
        $result = array_intersect($a, $b);
                
        // if the interest has the same number of elements as the original
        // it is a subset so return true
        if(count($b) == count($result)){
            return true;
        }
        
        // not a subset
        return false;
    }
    
    /*
     *  Restructures and sorts rules array
     */
    private function sort_rules($rules)
    {
        $data = array();
        // re-structure the rules array so we can use it 
        foreach ($rules as $key => $row) {
            if (array_key_exists(key($row), $data)) {
                $right = explode(' ', trim(current($row)));
                foreach ($right as $r => $v) {
                    $data[key($row)][] = $v;
                }
            } else {
                $data[key($row)] = explode(' ', trim(current($row)));
            }
        }

        // custom sorting function to sort rules array by number of 
        // attributes on left only
        function ccsort($a, $b) {
            $arr1 = explode(' ', trim($a));
            $arr2 = explode(' ', trim($b));

            if (count($arr1) == count($arr2))
                return 0;
            if (count($arr1) < count($arr2))
                return 1;
            return -1;
        }

        uksort($data, 'ccsort');

        return $data;
    }
}

