<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class MY_Loader
 */
class MY_Loader extends CI_Loader
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_ci_library_paths[] = realpath(FCPATH.'../libraries').'/';
    }
}