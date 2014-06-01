<?php

class Ccover extends CI_Model {

    var $file   = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    //read in file 
    function proccess($data)
    {
        $this->file = read_file($data['upload_data']['full_path']);
        return $this->file;

    }

}