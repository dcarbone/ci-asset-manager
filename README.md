AssetPackager
=============

A powerful asset management library

This library has been designed to work within the CodeIgniter framework, however it is possible to use it outside of that with minimal effort.


Basic Setup and Use
-------------------

Copy contents of /libraries to /{$appdir}/libraries
Copy contents of /config to /{$appdir}/config

At some point in your code : require APPPATH."libraries/AssetPackager/__autoload.php";

Config Parameters
-----------------

The root of the Asset Packager config file are the groups you define.

For example:

	$config['groups']['GROUPNAME'] = array(
		"scripts" => array(
			array(
				"dev_file" => "", 
	            "prod_file" => "", 
	            "minify" => TRUE,
	            "cache" => TRUE,
	            "name" => "",
	            "requires" => array()
			),
			array(
				// additional script file
			)
		),
		"styles" => array(
			array(
				"dev_file"  => "",
	            "prod_file" => "",
	            "media"     => "screen",
	            "minify"    => TRUE,
	            "cache"     => TRUE,
	            "name"      => "",
	            "requires"  => array()

			),
			array(
				// additional style file
			)
		),
		"views" => array(
			// script view files required by this group
		),
		"groups" => array(
			// other groups that are required by this group
		)
	);

These are the paramters that can make up any given group array.


### Script Parameter Breakdown

**dev_file**  
This field is required.  Use the full filename of the file relative to the assets/scripts
directory defined further up in the config file.
**If the file is remote, put the full URL here**

**prod_file**  
This field is optional.  It only effects non-development environments.

**minify**   
This field is optional, it defaults to true.  Minify only affects non-dev environments and the file will only be minified if both
this paramter and the global "minify_scripts" parameter is set to true

**cache**  
This field is optional, it defaults to true
Caching does multiple things and will be explained further below

**name**  
This field defaults to whatever you put in dev_file, however you can specify a
name of your choosing here.

**requires**  
List the other script files by name that this specific file requires


### Style Parameter Breakdown

**dev_file**  
**prod_file**  
**minify**  
**cache**  
**name**  
**requires**  
These follow the same rules as Scripts

**media**  
Styles have the additional attribute of "media", this is used not only for non-combined output but
when the global config "combine" is set to true, styles are grouped by "media" types for output.

### Views

View files are Javascript files which are not allowed to be remote and have a separate folder.
These files do not have any parameters, simply list their names.

### Groups

An array of other groups which this group requires

Cache System
------------
AssetPackager's caching system is rather robust, albeit static for the time being.  

The first time you use AssetPackager to load assets for output on your site with cache set to TRUE,  
it creates parsed / cached versions of each asset in the $config['cache'] folder under the root assets folder.  

**Example**  
If you had:
	
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

The end result would be that on each page request wherein this group was including on page load,  
no local cache file is created for this specific file.  

If this is a development environment, or $config['combine'] === FALSE, The output would simply be the < script /> tag  
with the src="//ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js" (http / https are omitted).  

If this was not a development environment and $config['combine'] === TRUE, then for the combined cache file a  
CURL request will be made to retrieve the contents of the remote file for inclusion in the combined file output.  

**Value Replacement**  
Another key feature to the caching mechanism is key value replacement.  This works with any type of asset, and looks for these  
specific keywords:

	$replace_keys = array(
        "{baseURL}",
        "{assetURL}",
        "{environment}",
        "{debug}"
    );

    $replace_with = array(
        base_url(),
        str_replace(array("http:", "https:"), "", asset_url()),
        ((defined("ENVIRONMENT")) ? strtolower(constant("ENVIRONMENT")) : "production"),
        ((defined("ENVIRONMENT") && constant("ENVIRONMENT") === "DEVELOPMENT") ? "true" : "false")
    );

Currently these values are statically set.  I have plans to make them configurable, but I just haven't taken  
the time to do it yet.


