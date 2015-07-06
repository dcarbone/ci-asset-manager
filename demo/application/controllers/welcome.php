<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Welcome
 */
class Welcome extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
        $this->load->view('header_basic');
		$this->load->view('view_basic');
        $this->load->view('footer');
	}

    public function multiple()
    {
        $this->load->view('header_multiple');
        $this->load->view('view_multiple');
        $this->load->view('footer');
    }

    public function group_demo()
    {
        $this->asset_manager
            ->add_asset_to_output_queue('js/jquery-1.11.1.min.js', true)
            ->add_asset_to_output_queue('css/basic.css', true);

        $this->load->view('header_group');
        $this->load->view('view_group');
        $this->load->view('footer');
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */