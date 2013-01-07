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
