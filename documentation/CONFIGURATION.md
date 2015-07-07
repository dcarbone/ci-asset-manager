# Configuration

[Home](../README.md)

Below is the default config array present in [config/asset_manager.php](../config/asset_manager.php):

```php
$config['asset_manager'] = array(

    'asset_dir_relative_path' => 'assets',
    'javascript_dir_name' => 'js',
    'stylesheet_dir_name' => 'css',
    'cache_dir_name' => 'cache',
    'combine_groups' => false,
    
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

### combine_groups

This will globally turn on or off asset combining.

For more information on combining, see [here](COMBINING.md).
