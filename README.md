asset_manager
=============

A simple asset management library for the [CodeIgniter](http://www.codeigniter.com/) framework.

## Installation
Simply drag each file under each directory in this lib to the corresponding directories in your CI 2 application.

## Configuration
There are only 3 configuration parameters:

- asset_dir_relative_path
- javascript_dir_name
- stylesheet_dir_name

*asset_dir_relative_path*

Set this value to the name of the directory you store your assets in, relative to the root of your project.
So, for instance, if your project looks like this:

```
|-- src/
|   |-- application/
|   |-- assets/
|   |   |-- js/
|   |   |-- css/
|   |-- system/
|   |-- index.php
```

..then you would specify `'assets'` for this value.

*javascript_dir_name*

This is the name of the directory under the above defined assets directory you store your javascript assets.
Using the above example, this value would be `'js'`.

*stylesheet_dir_name*

This is the name of the directory under the above defined assets directory you store your stylesheet assets.
Using the above example, this value would be `'css'`.

## Loading
You will either need to manually load up the asset_manager config, helper, and library, or add them to your CI autoload.php conf file.

## Usage
Usage is quite simple.  There are a number of helper functions provided:

*Note*: All examples below will assume the above example dir structure

*Note*: For each of the below functions, inclusion of the filetype extension is optional

### tag Functions
There are two tag functions:

- `javascript_tag`
- `stylesheet_tag`

Both of these functions accept 2 arguments:

1. Content of tag
2. HTML Attributes (optional array)

Both return the appropriate HTML as a string.

### include Functions
There are two include functions:

- include_javascript
- include_stylesheet

These functions accept multiple different input types.

### Basic
```php
echo include_stylesheet('basic');
echo include_javascript('jquery-1.11.1.min');
```

Will result in the following (using the dir structure in the example above):

```html
<link rel="stylesheet" href="http://your-url/assets/css/basic.css" />
<script type="text/javascript" src="http://your-url/assets/js/jquery-1.11.1.min.js"></script>
```

### Multiple
```php
echo include_javascript('jquery-1.11.1.min', 'my-js-lib');
```

This will result in:
```html
<script type="text/javascript" src="http://your-url/assets/js/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="http://your-url/assets/js/my-js-lib.js"></script>
```

### GLOB
Any of the input on any of the include helper functions will accept a string formatted with
a valid [PHP GLOB](http://php.net/manual/en/function.glob.php) string.  I have used `GLOB_NOSORT | GLOB_BRACE`
as options.

This allows you to do something like this:

```php
echo include_javascript(
    'jquery-1.11.1.min',
    'noty/packaged/jquery.noty.packaged.min',
    'noty/themes/default',
    'noty/layouts/*'); // Notice the astrix
```

...which will output the following:

```html
<script src="http://your-url/assets/js/jquery-1.11.1.min.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/packaged/jquery.noty.packaged.min.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/themes/default.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/bottom.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/bottomCenter.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/bottomLeft.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/bottomRight.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/center.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/centerLeft.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/centerRight.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/inline.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/top.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/topCenter.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/topLeft.js" type="text/javascript"></script>
<script src="http://your-url/assets/js/noty/layouts/topRight.js" type="text/javascript"></script>
```

### Attributes

If you wish to apply a set of HTML attributes to multiple items, you may pass in an array with the following structure:
```php
array('attr_name' => 'attr_value')
```
...as the last argument.

Example:
```php
echo include_javascript('jquery-1.11.1.min', 'my-js-lib', array('language' => 'javascript'));
```

...which will output:

```html
<script language="javascript" type="text/javascript" src="http://your-url/assets/js/jquery-1.11.1.min.js"></script>
<script language="javascript" type="text/javascript" src="http://your-url/assets/js/my-js-lib.js"></script>
```

## Future improvements
- Assetic wrapper
