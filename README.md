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
	            "group" => array("default"),
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
	            "group"     => array("default"),
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
