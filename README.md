asset_manager
=============

A powerful asset management library for the <a href="http://ellislab.com/codeigniter" target="_blank">CodeIgniter</a> framework.

## Under development
I am currently working on a near-complete rewrite of this library, and as such it is not currently in a working state.  I will be working
on this in my spare time, but even so I hope to have it finished very soon.


## Libraries this library implements:
- https://github.com/oyejorge/less.php/tree/v1.7.0.2
- https://github.com/tedious/JShrink/tree/v1.0.0

These dependencies are REQUIRED.  Since CI 2 does not support <a href="https://getcomposer.org/" target="_blank">Composer</a> yet,
I have included these repos in this one.  I DO NOT claim to own the code for either of those libraries.  They are simply included
here due to a lack of better CI dependency management.

Basic Setup and Use
-------------------

Copy contents of /libraries to /APPPATH.libraries
Copy contents of /config to /APPPATH.config

In your controller, call:
```php
$this->load->library('asset_manager');
```

This will create an instance of asset_manager on your controller, accessible through
```php
$this->asset_manager->....
```

## Demo app
I have included a copy of CI 2.2.0 in this repo as a demonstration tool.  To view it, simply navigate to ``` /demo ``` in any
browser.  Note: Requires you have the ability to run <a href="http://ellislab.com/codeigniter" target="_blank">CodeIgniter</a>
applications on whatever machine you view it on.

Config Parameters
-----------------

The root of the Asset Manager config file are the groups you define.

For example:

```php

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
    // 'base_url' => 'http://www.example.com',

    // Is this a development env?
    'dev' => $isDev,

    // Global combine flag.  If this is false, ignores individual combine values
    'combine' => !$isDev,

    'minify_scripts' => !$isDev,
    'minify_styles' => !$isDev,


    'force_curl' => false,

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

        // Default group is always loaded
        'default' => array(
            'scripts' => array(
                array(
                    'file' => 'path_to_file | url_to_file',
                    'minify' => TRUE,
                    'cache' => TRUE,
                    'name' => 'my_awesome_name',
                    'requires' => array()
                ),
                array(
                    // additional script file
                )
            ),
            'styles' => array(
                array(
                    'file'  => '',
                    'media'     => 'screen',
                    'minify'    => TRUE,
                    'cache'     => TRUE,
                    'name'      => '',
                    'requires'  => array()
    
                ),
                array(
                    // additional style file
                )
            ),
            'less_styles' => array(
                array(
                    'file' => '',
                    'media' => '',
                    'name' => '',
                    'requires' => array()
                ),
            ),
            'groups' => array(
                // other groups that are required by this group
            )
    ),
);
```

### Script Parameters

- **file**
This field is required.  Use the full filename of the file relative to the assets/scripts
directory defined further up in the config file.
**If the file is remote, put the full URL here**

- **minify**
This field is optional, it defaults to true.  Minify only affects non-dev environments and the file will only be minified if both
this paramter and the global "minify_scripts" parameter is set to true

- **cache**
This field is optional, it defaults to true
Caching does multiple things and will be explained further below

- **name**
This field defaults to whatever you put in file, however you can specify a
name of your choosing here.

- **requires**
List the other script files by name that this specific file requires


### Additional Style Parameters

- **media**
Styles have the additional attribute of "media", this is used not only for non-combined output but
when the global config "combine" is set to true, styles are grouped by "media" types for output.

### Less Style Parameters

- **file**
- **media**
- **name**
- **requires**

The parameters present here the same as those for Styles, except that *cache* and *minify* are omitted as they are both on by default currently.

### Groups

An array of other groups which this group requires

Cache System
------------
asset_manager's caching system is rather robust, albeit static for the time being.

The first time you use asset_manager to load assets for output on your site with cache set to TRUE,
it creates parsed / cached versions of each asset in the $config['cache'] folder under the root assets folder.

**Example**

If you had:

	$config['groups']['jquery'] = array(
	    'scripts' => array(
	        array(
	            "file" => 'http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js',
	            "minify" => false,
	            "cache" => false,
	            "name" => "jquery"
	        )
	    )
	);

The end result would be that on each page request wherein this group was including on page load,
no local cache file is created for this specific file.

If this is a development environment, or $config['combine'] === FALSE, The output would simply be the < script /> tag
with the src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" (http / https are omitted).

If this was not a development environment and $config['combine'] === TRUE, then for the combined cache file a
CURL request will be made to retrieve the contents of the remote file for inclusion in the combined file output.

### Still TODO

- Improve documentation, pretty not-so-great currently
- Create PHPUnit testing
- SASS Support(?)