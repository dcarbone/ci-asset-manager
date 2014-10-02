asset_manager
=============

A simple asset management library for the <a href="http://ellislab.com/codeigniter" target="_blank">CodeIgniter</a> framework.

## Under development
I am currently working on a near-complete rewrite of this library, and as such many features present in the older version
are not yet available.


## Libraries this library implements:
- https://github.com/tedious/JShrink/tree/v1.0.0
- https://github.com/natxet/CssMin

These dependencies are REQUIRED.  Since CI 2 does not support <a href="https://getcomposer.org/" target="_blank">Composer</a> yet,
I have included these repos in this one.  I DO NOT claim to own the code for either of those libraries.  They are simply included
here due to a lack of better CI dependency management.

## Demo app
I have included a copy of CI 2.2.0 in this repo as a demonstration tool.  To view it, simply navigate to ` /demo ` in any
browser.  Note: Requires you have the ability to run <a href="http://ellislab.com/codeigniter" target="_blank">CodeIgniter</a>
applications on whatever machine you view it on.

## Installation

Copy contents of /libraries to /APPPATH.libraries
Copy contents of /config to /APPPATH.config

## Requirements

This library has been tested with CI 2.2.0 and requires the CodeIgniter URL Helper function set to be loaded prior to
library initialization.

## Configuration

In an attempt to keep the amount of configuration to a minimum there are currently only a few config params that
this library looks for:

- **asset_dir_relative_path**
- **minify**
- **logical_groups**

For example:

```php
$config['asset_manager'] = array(

    'asset_dir_relative_path' => 'assets',

    'logical_groups' => array(
        'noty' => array(
            'js/noty/packaged/jquery.noty.packaged.min.js',
            'js/noty/themes/default.js',
            'js/noty/layouts/*.js',
        ),
    ),

);
```

### asset_dir_relative_path
This value must be a subdirectory of your CI application's root path, determined by the value of the ` FCPATH ` constant.

### minify
This value must be boolean, and allows you to globally disable / enable minification of assets.

### logical_groups
These values represent logical groupings of your assets.  They are not bound by any physical limitations and can contain
both stylesheet and javascript assets.

A single asset may belong to many groups, but be careful when doing this.  I currently do not check for duplicate asset
output requests, so you may very well end up including the same asset multiple times.

## Initialization

In your controller, call:
```php
$this->load->library('asset_manager', $this->config->load('asset_manager'));
```

This will create an instance of asset_manager on your controller, accessible through
```php
$this->asset_manager->....
```

## Usage

For now, the logic is very, very simple.  Simply execute the following:

```php
echo $this->asset_manager->generate_asset_tag('js/jquery-1.11.1.min.js');
echo $this->asset_manager->generate_asset_tag('css/basic.css');
```

...in a view file of your choosing.

The first parameter is the path to the asset file relative to the ` asset_dir_relative_path `
config parameter described below.

The second parameter is a boolean flag that will tell asset manager if you explicitly want this asset to be minified or not.

The third parameter is an array of key=>value attributes that you wish to be added to the output.

Further minification details below.

## Minify

There are several rules which determine whether an asset will be minified.

1. Source is already minified
    - Most asset (especially javascript libraries) creators provide their users with a pre-minified version of the file.
    These files generally have the word "min" in their file names in some capacity.  Asset manager attempts to determine
    if an asset is already minified by testing the file name with the following regex:
    - ` /[\.\-_]min[\.\-_]/i `
    - In the event that a file is already minified, no further minification attempt will be made
2. Minified version already exists (source file NOT pre-minified)
    - If you decide to include both the minified and non-minified version of an asset in your project, Asset manager will
    attempt to determine if a minified version already exists by looking for a ".min.ext" version of the file in the
    same directory as the source file
    - This detection is not very intelligent yet, and is one of the areas I wish to improve.
    - When an already-minified version is detected, the [filemtime](!http://php.net/manual/en/function.filemtime.php) value
    of both the source and minify files. If the source is newer, the minified version is re-created.
3. Global minify set to ` false `
    - If the "minify" config parameter is defined as ` false `, then no minification will occur unless you explicitly specify
    the second parameter of ` $this->asset_manager->generate_asset_tag() ` to be ` true `.
4. Global minify set to ` true `, asset minify set to ` false `
    - If the "minify" config parameter is defined as ` true ` but the asset's minify parameter is set to ` false `,
    that asset will not be minified unless you explicitly specify the second parameter of
    ` $this->asset_manager->generate_asset_tag() ` to be ` true `.
5. Global minify set to ` true `, asset minify set to ` true `
    - In this case, the asset will always be minified during output unless you explicitly specify the second parameter of
    ` $this->asset_manager->generate_asset_tag() ` to be ` false `.

If you do NOT specify a "minify" config parameter, Asset manager will attempt to determine it's location via the
` ENVIRONMENT ` constant.  If ` ENVIRONMENT !== 'development' `, then minify = ` true `.

## Future improvements
- Logical grouping
- Physical grouping
- LESS & SASS support
- Asset output queuing