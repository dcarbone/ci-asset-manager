<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['asset_manager'] = array(

    'asset_dir_relative_path' => 'assets',
    'javascript_dir_name' => 'js',
    'stylesheet_dir_name' => 'css',
    'cache_dir_name' => 'cache',

    'default_attributes' => array(
        'link' => array('rel' => 'stylesheet'),
        'script' => array('type' => 'text/javascript'),
        'style' => array('type' => 'text/css'),
    ),

    'combine_groups' => true,
    'always_rebuild_combined' => false

);

/* End of file asset_manager.php */
/* Location: ./application/config/asset_manager.php */