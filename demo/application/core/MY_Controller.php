<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class \CI_Controller
 */
class MY_Controller extends CI_Controller
{
    /** @var \CI_Input */
    public $input;
    /** @var \CI_Router */
    public $router;
    /** @var \CI_Config */
    public $config;
    /** @var \CI_Email */
    public $email;
    /** @var \CI_Output */
    public $output;
    /** @var \CI_Log */
    public $log;

    // Base core classes
    /** @var CI_Session */
    public $session;
    /** @var CI_Loader */
    public $load;
    /** @var CI_User_Agent */
    public $agent;
    /** @var CI_URI */
    public $uri;

    /** @var \asset_manager */
    public $asset_manager;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();


    }
}