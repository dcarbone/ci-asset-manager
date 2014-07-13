<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class MY_Config
 */
class MY_Config extends CI_Config
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_config_paths[] = realpath(FCPATH.'../').'/';
    }
}