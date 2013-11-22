<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
* Asset Packager Config File
*/

// Copyright (c) 2012-2013 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/*
|--------------------------------------------------------------------------
| Asset Directory
|--------------------------------------------------------------------------
|
| Path to the asset directory.  Relative to the CI front controller.
| 
| If you do not have an ~"assets" directory, set this value to "", or empty string.
|
*/

$config['asset_dir'] = "assets";

/*
|--------------------------------------------------------------------------
| Script Directory
|--------------------------------------------------------------------------
|
| Path to the script directory.  Relative to the asset_dir.
|
*/

$config['script_dir'] = 'js';


/*
|--------------------------------------------------------------------------
| Style Directory
|--------------------------------------------------------------------------
|
| Path to the style directory.  Relative to the asset_dir.
|
*/

$config['style_dir'] = 'css';

/*
|--------------------------------------------------------------------------
| Cache Directory
|--------------------------------------------------------------------------
|
| Path to the cache directory. Must be writable. Relative to the asset_dir.
|
*/

$config['cache_dir'] = 'cache';

/*
|--------------------------------------------------------------------------
| Scripts View Directory
|--------------------------------------------------------------------------
|
| Path to the Javscript View Directory. Must be writable. Relative to the asset_dir.
|
*/

$config['script_view_dir'] = $config['script_dir'].'/views';

/*
|--------------------------------------------------------------------------
| Base URL
|--------------------------------------------------------------------------
|
|  Base url of the site, like http://www.example.com/ Defaults to the CI 
|  config value for base_url.
|
*/

//$config['base_url'] = 'http://www.example.com/';


/*
|--------------------------------------------------------------------------
| Development Flag
|--------------------------------------------------------------------------
|
|  Flags whether your in a development environment or not. Defaults to FALSE.
|
*/

$config['dev'] =  FALSE;


/*
|--------------------------------------------------------------------------
| Combine
|--------------------------------------------------------------------------
|
| Flags whether files should be combined. Defaults to opposite of $dev flag.
|
*/

$config['combine'] = !$config['dev'];


/*
|--------------------------------------------------------------------------
| Minify Javascript
|--------------------------------------------------------------------------
|
| Global flag for whether JS should be minified. Defaults opposite of $dev flag.
|
*/

$config['minify_scripts'] = !$config['dev'];


/*
|--------------------------------------------------------------------------
| Minify CSS
|--------------------------------------------------------------------------
|
| Global flag for whether CSS should be minified. Defaults to opposite of $dev flag.
|
*/

$config['minify_styles'] = !$config['dev'];

/*
|--------------------------------------------------------------------------
| Force cURL
|--------------------------------------------------------------------------
|
| Global flag for whether to force the use of cURL instead of file_get_contents()
| Defaults to FALSE.
|
*/

$config['force_curl'] = true;

#--------------------------------------------------------------------------
# jQuery
#--------------------------------------------------------------------------
#
$config['groups']['jquery'] = array(
    'scripts' => array(
        array(
            "dev_file" => 'http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js',
            "minify" => false,
            "cache" => false,
            "name" => "jquery"
        )
    )
);

#--------------------------------------------------------------------------
# jQuery UI
#--------------------------------------------------------------------------
#
$config['groups']['jqueryui'] = array(
    "scripts" => array(
        array(
            "dev_file" => "http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js",
            "name" => 'jqueryui',
            "minify" => false,
            "cache" => false,
            "requires" => array("jquery")
        )
    )
);

#--------------------------------------------------------------------------
# Default Group
#--------------------------------------------------------------------------
#
$config['groups']['default'] = array(
    'styles' => array(
        array(
            "dev_file" => 'reset.css',
            "name" => "reset"
        ),
        array(
            "dev_file" => 'grid.css',
            "name" => "grid"
        ),
        array(
            "dev_file" => 'basic.css',
            "name" => "basic"
        )
    ),
    'scripts' => array(
        array(
            "dev_file" => 'underscore.js',
            "cache" => false,
            "minify" => false,
            "name" => "underscore"
        ),
        array(
            "dev_file" => 'backbone.js',
            "cache" => false,
            "minify" => false,
            'name' => 'backbone'
        ),
        array(
            "dev_file" => "setup.js",
            "name" => "setup"
        )
    ),
    "groups" => array("jquery", "customjqueryui")
);
#
# The group "default" will be included on every page.
#


/* End of file assetpackager.php */
/* Location: ./application/config/assetpackager.php */