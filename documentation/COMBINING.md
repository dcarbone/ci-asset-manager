# Combining

[Home](../README.md)

ci-asset-manager handles combining asset files on a per-include-call basis.

You may enable / disable combining globally by specifying the `combine_groups` parameter in the
[config](CONFIG.md) file.

So, for example:

```php
echo include_stylesheet('basic', 'home', 'grid');
echo include_javascript('jquery-1.11.1.min', 'other-js-file');
echo include_javascript('noty', 'noty/layouts/*');
```

...generates three combined files will be generated, one CSS and two JS.

Combined file names are based on a sha1 hash of the file names you pass to the function.  Each combined file
will only be created if a combined file with the exact same name does not already exist in the specified
cache directory.

It is recommended that you do not enable combination while developing, as changes you make to the source
files will not be reflected in the output unless you remove the combined file in the cache directory.

You may also enable / disable combination on a per-call basis by passing in a boolean value as the
last argument.

For example:

```php
echo include_stylesheet('basic', 'home', 'grid');
echo include_javascript('jquery-1.11.1.min', 'other-js-file', false);
echo include_javascript('noty', 'noty/layouts/*');
```

...will generate 2 combined files.  The final output will be one \<link> and three \<script> tags.
