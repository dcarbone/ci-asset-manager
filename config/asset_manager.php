<?php

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

$isDev = ((defined('ENVIRONMENT') && constant('ENVIRONMENT') === 'development') ? true : false);

$config['asset_manager'] = array(

    // Path to the asset directory, relative to the CI Front Controller (FCPATH)
    'asset_dir' => 'assets',

    // Paths to each asset type directory, will be appended to asset_dir value
    'script_dir' => 'js',
    'style_dir' => 'css',
    'less_style_dir' => 'less',
    'cache_dir' => 'cache',

    // Base url of site, defaults to CI_Config base_url value
//    'base_url' => 'http://www.example.com',

    // Is this a development env?
    'dev' => $isDev,

    // Global combine flag.  If this is false, ignores individual combine values
    'combine' => !$isDev,

    'minify_scripts' => !$isDev,
    'minify_styles' => !$isDev,


    // Define scripts
    'scripts' => array(
        'jquery2' => array(
            'file' => 'http://ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js',
            'cache' => false,
            'minify' => false,
        )
    ),

    // Define asset groups
    'groups' => array(

        'jquery' => array(
            'scripts' => array(
                'jquery' => array(
                    'file' => '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js',
                    'minify' => false,
                    'cache' => false
                )
            )
        ),

        'jqueryui' => array(
            'scripts' => array(
                array(
                    'file' => '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js',
                    'name' => 'jqueryui',
                    'minify' => false,
                    'cache' => false,
                    'requires' => array('jquery')
                )
            ),
            'styles' => array(
                array(
                    'file' => '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css',
                    'name' => 'jqueryui',
                    'minify' => false,
                    'cache' => false
                )
            )
        ),
    ),
);


/* End of file asset_manager.php */
/* Location: ./application/config/asset_manager.php */