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
              $this->augmentation();
              $this->transitivity();
              $this->find_canonical_cover();
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
                $rule = $this->reflexivity($rule);
                
                // build the rules array
                $rules[][trim($rule[0])] = trim($rule[1]);
                
            }
        }
        
        return $rules;
    }
    
    /*
     *  Perform Axiom of Reflexivity
     *  A,B,C -> C,D reduces to A,B,C -> D
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
        
        //if there are matches, remove them from the right side
        if($matches){
           foreach($matches as $key => $match){
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
    
    /*
     *  Perform Axiom of augmentation
     *  (Ugly but works)
     *  A,B -> C and A -> C reduces to A->C (A,B -> C is elminated)
     */
    private function augmentation() {
        // only perform if rules array exists and is an array
        if (isset($this->rules) && empty($this->rules) && !is_array($this->rules)) {
            return;
        }
        //assign local rules var
        $rules = $this->rules;
        $k = 0;
        foreach ($rules as $key1 => $rule1) {
            $left1 = explode(' ', key($rule1));
            $right1 = explode(' ', $rule1[key($rule1)]);
            foreach ($rules as $key2 => $rule2) {
                //make sure we aren't comparing the same rule  
                if ($rule1 != $rule2) {
                    $left2 = explode(' ', trim(key($rule2)));
                    $right2 = explode(' ', trim($rule2[key($rule2)]));

                    //the two rules have at least one of the same values on the right
                    arsort($right1);
                    arsort($right2);
                    if ($right1 === $right2) {

                        // make sure they have at least one of the same values on the left
                        if (array_intersect($left1, $left2)) {
                            $elminiate_rule = max($left1, $left2);
                            $elminiate_rule = implode(' ', $elminiate_rule);
                            if (key($rules[$key1]) == $elminiate_rule) {
                                unset($rules[$k]);
                            }
                        }
                    }
                }
            }

            $k++;
        }

        // update object rules var 
        $this->rules = $rules;
    }
    
    /*
     *  Perform Axiom of transitivity
     *  A->C and C-D and A->D reduces to  A->C and C->D (A->D is eliminated) 
     */
    private function transitivity()
    {
        // only perform if rules array exists and is an array
        if(isset($this->rules) && empty($this->rules) && !is_array($this->rules)){
            return;
        }
        //assign local rules var
        $rules = $this->rules;
        foreach ($rules as $key1 => $rule1) {
            $left1 = explode(' ', key($rule1));
            $right1 = explode(' ', $rule1[key($rule1)]);
            arsort($left1);
            arsort($right1);
            
            foreach ($rules as $key2 => $rule2) {
                //make sure we aren't comparing the same rule  
                if ($rule1 != $rule2) {
                    $left2 = explode(' ', trim(key($rule2)));
                    $right2 = explode(' ', trim($rule2[key($rule2)]));

                    //the two rules have at least one of the same values on the right
                    arsort($left2);
                    arsort($right2);
                    if ($right1 === $left2) {
                        foreach ($rules as $key3 => $rule3) {
                            $left3 = explode(' ', key($rule3));
                            $right3 = explode(' ', $rule3[key($rule3)]);
                            
                            arsort($left3);
                            arsort($right3);                
                            if(($left3 == $left1) && ($right3 == $right2)){ 
                                unset($rules[$key3]);
                            } 
                        }
                    }
                }
            }
        }

        // update object rules var 
        $this->rules = $rules;  
    }
    

    /*
     * performs the work to finding the canonical cover
     */
    private function find_canonical_cover()
    {
        // only perform if rules array exists and is an array
        if(isset($this->rules) && empty($this->rules) && !is_array($this->rules)){
            return;
        }

        // re-structure the rules array so we can use it 
        $data = $this->sort_rules($this->rules);

        // start first loop over set of rules
        foreach($data as $left1 => $right1)
        {
            // loop over each value rule is a FD for
            foreach($right1 as $key1 => $value1)
            {
                // add final rule to resultset
                $result[] = $left1;
                
                // begin second loop over set of rules
                foreach($data as $left2 => $right2)
                {
                    // loop over each value rule is a FD for
                    foreach($right2 as $key2 => $value2)
                    {
                        // ignore the rule in the result set
                        if($value1 != $value2)
                        {
                           $subset =  $this->compare_keys($left1,$left2);
                           if($subset){
                               // add value to result if subset
                               $result[] = $value2;
                               echo '============================';
                               echo '<br>';
                               echo '+rule+';
                               var_dump($key1);
                               echo '+rule+';
                               var_dump($result);
                               echo 'left - right';
                               var_dump($left1);
                               var_dump($right1);
                               echo 'possible rule to remove';
                               var_dump($data[$left1][$key1]);
                               echo '============================';
                               // check if result[] == to rule being tested
                                   // if so remove tested rule
                                   //unset($data[$left1][0][$key1])
                                       // set flag to drop out of loops back to the first
                           }
                        }
                    }
                }
            }
            // clear result set out
            $result = array();
        }
        $this->rules =  $data;
    }
    
    /*
     * compares two array keys that are strings
     * @arg1  = string
     * @agr2  = string
     * returns true if arg1 is a is a subset of b
     */
    private function compare_keys($a,$b)
    {
        // explode key strings to arrays and intersect
        $a = explode(' ',trim($a));
        $b = explode(' ',trim($b));
        $result = array_intersect($a, $b);
        
        // if the interest has the same number of elements as the original
        // it is a subset so return tru
        if(count($a) == count($result)){
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

