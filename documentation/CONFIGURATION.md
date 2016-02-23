# Configuration

[Home](../README.md)

Below is the default config array present in [config/asset_manager.php](../config/asset_manager.php):

```php
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
```

### asset_dir_relative_path

Set this value to the name of the directory you store your assets in, relative to the root of your project.
For instance, if your project looks like this:

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

### javascript_dir_name

This is the name of the directory under the above defined assets directory you store your javascript assets.
Using the above example, this value would be `'js'`.

### stylesheet_dir_name

This is the name of the directory under the above defined assets directory you store your stylesheet assets.
Using the above example, this value would be `'css'`.

### cache_dir_name

This is the name of the directory under the above defined assets directory that asset_manager will use to store
combined versions of assets, if combining is enabled.

### default_attributes

This is an array of each of the different types of HTML elements this library ultimately generates and their
respective default HTML attributes.

Be careful when modifying these values as they will be injected into EVERY element that is written out
unless manually overridden when calling the output functions themselves.

### combine_groups

This will globally turn on or off asset combining.

For more information on combining, see [here](COMBINING.md).

### always_rebuild_combined

This will force asset_manager to rebuild combined asset files even if the same files were previously generated.

*NOTE*: This should only be used during development.
