<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['asset_manager'] = array(

    'asset_dir_relative_path' => 'assets',

    'asset_cache_dir_relative_path' => 'assets/cache',

    'minify' => true,

    'logical_groups' => array(
        'noty' => array(
            'js/noty/packaged/jquery.noty.packaged.min.js',
            'js/noty/themes/default.js',
            'js/noty/layouts/*.js',
        ),
    ),

);


/* End of file asset_manager.php */
/* Location: ./application/config/asset_manager.php */