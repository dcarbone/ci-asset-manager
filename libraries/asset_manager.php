<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class asset_manager
 */
class asset_manager
{
    const GLOB_REGEX = '/[\[{*?}\]]+/S';

    /** @var string */
    public $asset_dir_relative_path;
    /** @var string */
    public $asset_dir_full_path;
    /** @var string */
    public $asset_dir_uri;

    /** @var string */
    public $javascript_dir_name;
    /** @var string */
    public $javascript_dir_full_path;
    /** @var string */
    public $javascript_uri;

    /** @var string */
    public $stylesheet_dir_name;
    /** @var string */
    public $stylesheet_dir_full_path;
    /** @var string */
    public $stylesheet_uri;

    /** @var string */
    public $cache_dir_name;
    /** @var string */
    public $cache_dir_full_path;
    /** @var string */
    public $cache_uri;

    /** @var bool */
    public $combine_groups;
    /** @var bool */
    public $always_rebuild_combined;

    /** @var array */
    public $default_link_attributes = array('rel' => 'stylesheet');
    /** @var array */
    public $default_style_attributes = array('type' => 'text/css');
    /** @var array */
    public $default_script_attributes = array('type' => 'text/javascript');

    /**
     * Constructor
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct()
    {
        $config = get_instance()->config->item('asset_manager');
        if (isset($config['asset_manager']))
            $config = $config['asset_manager'];

        $this->_determine_root_asset_paths($config);
        $this->_determine_asset_type_paths($config);
        $this->_determine_cache_paths($config);

        if (isset($config['combine_groups']))
            $this->combine_groups = (bool)$config['combine_groups'];
        else
            $this->combine_groups = false;

        if (isset($config['always_rebuild_combined']))
            $this->always_rebuild_combined = (bool)$config['always_rebuild_combined'];
        else
            $this->always_rebuild_combined = false;

        if (isset($config['default_attributes']))
        {
            $defaults = $config['default_attributes'];

            if (isset($defaults['link']) && is_array($defaults['link']))
                $this->default_link_attributes = $defaults['link'];

            if (isset($defaults['style']) && is_array($defaults['style']))
                $this->default_style_attributes = $defaults['style'];

            if (isset($defaults['script']) && is_array($defaults['script']))
                $this->default_script_attributes = $defaults['script'];
        }
    }

    /**
     * @param string $content
     * @param array $html_attributes
     * @return string
     */
    public function javascript_tag($content = null, array $html_attributes = array())
    {
        return sprintf(
            '<script%s>%s</script>',
            static::_generate_attribute_string($html_attributes, $this->default_script_attributes),
            (string)$content
        );
    }

    /**
     * @param string $content
     * @param array $html_attributes
     * @return string
     */
    public function stylesheet_tag($content = null, array $html_attributes = array())
    {
        return sprintf(
            '<style%s>%s</style>',
            static::_generate_attribute_string($html_attributes, $this->default_style_attributes),
            (string)$content
        );
    }

    /**
     * @param string $file
     * @param mixed $additional [optional]
     * @return string
     */
    public function include_javascript($file, $additional = null)
    {
        return $this->_include_assets(func_get_args(), 'javascript');
    }

    /**
     * @param string $file
     * @param mixed $additional [optional]
     * @return string
     */
    public function include_stylesheet($file, $additional = null)
    {
        return $this->_include_assets(func_get_args(), 'stylesheet');
    }

// -----------------  Internal use only --------------------------------------------------------------

    /**
     * TODO: Maybe break this method up a bit?  Don't want to introduce too much overhead with tons of function calls, however.
     *
     * @param array $args
     * @param string $type
     * @return string
     */
    public function _include_assets(array $args, $type)
    {
        list($files, $html_attributes, $combine) = $this->_parse_include_args($args);

        $output = '';
        if ('javascript' === $type)
        {
            if ($combine)
            {
                $output = $this->_include_combined_javascript_assets($files, $html_attributes);
            }
            else
            {
                foreach($files as $file)
                {
                    if (preg_match(static::GLOB_REGEX, $file))
                    {
                        $new_args = static::_execute_glob($this->javascript_dir_full_path, $file);
                        $new_args[] = $html_attributes;
                        $output = sprintf(
                            '%s%s',
                            $output,
                            $this->_include_assets($new_args, $type)
                        );
                    }
                    else
                    {
                        $output = sprintf(
                            '%s%s',
                            $output,
                            $this->_include_javascript_asset($file, $html_attributes)
                        );
                    }
                }
            }
        }
        else if ('stylesheet' === $type)
        {
            if ($combine)
            {
                $output = $this->_include_combined_stylesheet_assets($files, $html_attributes);
            }
            else
            {
                foreach($files as $file)
                {
                    if (preg_match(static::GLOB_REGEX, $file))
                    {
                        $new_args = static::_execute_glob($this->stylesheet_dir_full_path, $file);
                        $new_args[] = $html_attributes;
                        $output = sprintf(
                            '%s%s',
                            $output,
                            $this->_include_assets($new_args, $type)
                        );
                    }
                    else
                    {
                        $output = sprintf(
                            '%s%s',
                            $output,
                            $this->_include_stylesheet_asset($file, $html_attributes)
                        );
                    }
                }
            }
        }

        return $output;
    }

    /**
     * @param array $args
     * @return array
     */
    protected function _parse_include_args(array $args)
    {
        $files = array();
        $html_attributes = array();
        $combine = $this->combine_groups;
        for ($i = 0, $count = count($args); $i < $count; $i++)
        {
            $arg = $args[$i];
            switch(true)
            {
                case is_string($arg):
                    $files[] = $arg;
                    break;

                case is_array($arg):
                    $html_attributes = $arg;
                    break;

                case is_bool($arg);
                    $combine = $arg;
                    break;

                default:
                    break 2;
            }
        }

        return array(
            $files,
            $html_attributes,
            $combine
        );
    }

    /**
     * @param array $files
     * @param array $html_attributes
     * @return string
     */
    protected function _include_combined_javascript_assets(array $files, array $html_attributes)
    {
        $combine_name = sprintf('%s.js', sha1(implode('', $files)));
        $full_path = sprintf('%s%s', $this->cache_dir_full_path, $combine_name);

        if ($this->always_rebuild_combined || !file_exists($full_path))
            $this->_concatenate_asset_files($full_path, $files, $this->javascript_dir_full_path, '.js');

        return $this->_create_include_script_element($combine_name, $html_attributes, true);
    }

    /**
     * @param array $files
     * @param array $html_attributes
     * @return string
     */
    protected function _include_combined_stylesheet_assets(array $files, array $html_attributes)
    {
        $combine_name = sprintf('%s.css', sha1(implode('', $files)));
        $full_path = sprintf('%s%s', $this->cache_dir_full_path, $combine_name);

        if ($this->always_rebuild_combined || !file_exists($full_path))
            $this->_concatenate_asset_files($full_path, $files, $this->stylesheet_dir_full_path, '.css');

        return $this->_create_include_link_element($combine_name, $html_attributes, true);
    }

    /**
     * @param string $combine_file
     * @param array $input_files
     * @param string $asset_path
     * @param string $asset_ext
     * @param resource $_fh
     * @param bool $_nest
     */
    protected function _concatenate_asset_files($combine_file, array $input_files, $asset_path, $asset_ext, $_fh = null, $_nest = false)
    {
        if (null === $_fh)
            $_fh = fopen($combine_file, 'w+');

        foreach($input_files as $file)
        {
            $file = static::_trim_path(static::_cleanup_path($file));
            if (false === strpos($file, $asset_ext))
                $file = sprintf('%s%s', $file, $asset_ext);

            if (preg_match(static::GLOB_REGEX, $file))
            {
                $this->_concatenate_asset_files($combine_file, static::_execute_glob($asset_path, $file), $asset_path, $asset_ext, $_fh, true);
            }
            else
            {
                fwrite($_fh, file_get_contents(sprintf('%s%s', $asset_path, $file)));
                fwrite($_fh, "\n;\n");
            }
        }

        if (false === $_nest)
            fclose($_fh);
    }

    /**
     * @param string $file
     * @param array $html_attributes
     * @return string
     */
    protected function _include_javascript_asset($file, array $html_attributes)
    {
        $file = static::_trim_path(static::_cleanup_path($file));
        if (false === strpos($file, '.js'))
            $file = sprintf('%s.js', $file);

        if (file_exists(sprintf('%s%s', $this->javascript_dir_full_path, $file)))
            return $this->_create_include_script_element($file, $html_attributes);

        $msg = sprintf(
            'ci-asset-manager - Could not locate requested javascript asset "%s".',
            $file);
        log_message('error', $msg);
        throw new \RuntimeException($msg);
    }

    /**
     * @param string $file
     * @param array $html_attributes
     * @return string
     */
    protected function _include_stylesheet_asset($file, array $html_attributes)
    {
        $file = static::_trim_path(static::_cleanup_path($file));
        if (false === strpos($file, '.css'))
            $file = sprintf('%s.css', $file);

        if (file_exists(sprintf('%s%s', $this->stylesheet_dir_full_path, $file)))
            return $this->_create_include_link_element($file, $html_attributes);

        $msg = sprintf(
            'ci-asset-manager - Could not locate requested stylesheet asset "%s".',
            $file);
        log_message('error', $msg);
        throw new \RuntimeException($msg);
    }

    /**
     * @param string $src
     * @param array $html_attributes
     * @param bool $cache
     * @return string
     */
    protected function _create_include_script_element($src, array $html_attributes, $cache = false)
    {
        return sprintf(
            "<script src=\"%s%s\"%s></script>\n",
            $cache ? $this->cache_uri : $this->javascript_uri,
            $src,
            static::_generate_attribute_string($html_attributes, $this->default_script_attributes)
        );
    }

    /**
     * @param string $href
     * @param array $html_attributes
     * @param bool $cache
     * @return string
     */
    protected function _create_include_link_element($href, array $html_attributes, $cache = false)
    {
        return sprintf(
            "<link href=\"%s%s\"%s />\n",
            $cache ? $this->cache_uri : $this->stylesheet_uri,
            $href,
            static::_generate_attribute_string($html_attributes, $this->default_link_attributes)
        );
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function _uri_from_relative_path($path)
    {
        return sprintf('%s/', static::_trim_path(base_url(static::_cleanup_path($path))));
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function _cleanup_path($path)
    {
        return str_replace(
            array(DIRECTORY_SEPARATOR, '//'),
            '/',
            $path
        );
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function _trim_path($path)
    {
        return rtrim($path, " \t\n\r\0\x0B/");
    }

    /**
     * @param string $root
     * @param string $input
     * @return string[]
     */
    protected static function _execute_glob($root, $input)
    {
        $glob = glob(sprintf('%s%s', $root, $input), GLOB_NOSORT | GLOB_BRACE);
        $files = array();
        foreach($glob as $file)
        {
            if (substr($file, -1) === '.')
                continue;

            if (false !== strpos($file, '.html'))
                continue;

            $files[] = str_ireplace($root, '', $file);
        }

        return $files;
    }

    /**
     * @param string $file_realpath
     * @return string
     */
    protected static function _determine_asset_file_name($file_realpath)
    {
        static $asset_dir_full_path = false;
        if (!$asset_dir_full_path)
            $asset_dir_full_path = addslashes(get_asset_manager()->asset_dir_full_path);

        return preg_replace(
        // search
            array(
                '#[/\\\]+#S',
                sprintf('#%s#iS', $asset_dir_full_path)
            ),
            // replace
            array(
                DIRECTORY_SEPARATOR,
                ''
            ),
            $file_realpath
        );
    }

    /**
     * @param string $file_name
     * @return null|string
     */
    protected static function _determine_asset_type($file_name)
    {
        switch(strrchr($file_name, '.'))
        {
            case '.js': return 'javascript';
            case '.css': return 'stylesheet';

            default:
                return null;
        }
    }

    /**
     * @param array $config
     */
    protected function _determine_root_asset_paths(array $config)
    {
        if (isset($config['asset_dir_relative_path']))
            $path = rtrim($config['asset_dir_relative_path'], "/\\");
        else
            $path = 'assets';

        $realpath = realpath($path);

        if (false === $realpath)
        {
            $msg = sprintf('ci-asset-manager - Asset directory "%s" does not appear to exist.', $path);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->asset_dir_relative_path = sprintf('%s/', $path);
            $this->asset_dir_full_path = sprintf('%s/', $realpath);
            $this->asset_dir_uri = static::_uri_from_relative_path($this->asset_dir_relative_path);
        }
        else
        {
            $msg = sprintf('ci-asset-manager - Specified asset path "%s" is not readable or writable.', $path);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * TODO: Break this up a bit
     *
     * @param array $config
     */
    protected function _determine_asset_type_paths(array $config)
    {
        if (isset($config['javascript_dir_name']))
            $js_dir = static::_trim_path(static::_cleanup_path($config['javascript_dir_name']));
        else
            $js_dir = 'js';

        $realpath = realpath(sprintf('%s%s', $this->asset_dir_full_path, $js_dir));

        if (false === $realpath)
        {
            $msg = sprintf(
                'ci-asset-manager - Specified Javascript directory "%s" does not appear to exist.',
                $js_dir);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->javascript_dir_name = $js_dir;
            $this->javascript_dir_full_path = sprintf('%s/', $realpath);
            $this->javascript_uri = static::_uri_from_relative_path(
                sprintf(
                    '%s%s/',
                    $this->asset_dir_relative_path,
                    $js_dir
                )
            );
        }
        else
        {
            $msg = sprintf(
                'ci-asset-manager - Specified Javascript directory "%s" does not appear to be readable or writable.',
                $js_dir);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (isset($config['stylesheet_dir_name']))
            $css_dir = static::_trim_path(static::_cleanup_path($config['stylesheet_dir_name']));
        else
            $css_dir = 'css';

        $realpath = realpath(sprintf('%s%s', $this->asset_dir_full_path, $css_dir));

        if (false === $realpath)
        {
            $msg = sprintf(
                'ci-asset-manager - Specified Stylesheet directory %s does not appear to exist.',
                $css_dir);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->stylesheet_dir_name = $css_dir;
            $this->stylesheet_dir_full_path = sprintf('%s/', $realpath);
            $this->stylesheet_uri = static::_uri_from_relative_path(
                sprintf(
                    '%s%s/',
                    $this->asset_dir_relative_path,
                    $css_dir
                )
            );
        }
        else
        {
            $msg = sprintf(
                'ci-asset-manager - Specified Stylesheet directory "%s" does not appear to be readable or writable.',
                $css_dir);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * @param array $config
     */
    protected function _determine_cache_paths(array $config)
    {
        if (isset($config['cache_dir_name']))
            $cache_dir = static::_trim_path(static::_cleanup_path($config['cache_dir_name']));
        else
            $cache_dir = 'cache';

        $full_path = sprintf('%s%s', $this->asset_dir_full_path, $cache_dir);
        $realpath = realpath($full_path);

        if (false === $realpath && false === (bool)@mkdir($full_path))
        {
            $msg = sprintf(
                'ci-asset-manager - Specified cache directory "%s" does not appear to exist.',
                $cache_dir);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }
        else
        {
            $realpath = realpath($full_path);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->cache_dir_name = $cache_dir;
            $this->cache_dir_full_path = sprintf('%s/', $realpath);
            $this->cache_uri = static::_uri_from_relative_path(
                sprintf(
                    '%s%s/',
                    $this->asset_dir_relative_path,
                    $cache_dir
                )
            );
        }
        else
        {
            $msg = sprintf(
                'ci-asset-manager - Specified cached directory "%s" does not appear to be readable or writable.',
                $cache_dir);
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    protected static function _determine_path_readable_writeable($path)
    {
        return is_readable($path) && is_writable($path);
    }

    /**
     * @param string $filename
     * @return string
     */
    protected static function _clean_asset_filename($filename)
    {
        return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $filename);
    }

    /**
     * @param array $attribute_map
     * @param array $defaults
     * @return string
     */
    protected static function _generate_attribute_string(array $attribute_map, array $defaults = array())
    {
        $combined = self::_parse_attribute_arrays($attribute_map, $defaults);
        $attr_string = '';

        if (count($combined) > 0)
        {
            foreach($combined as $k=>$v)
            {
                if (is_string($k))
                    $attr_string = sprintf('%s %s="%s"', $attr_string, trim($k), trim($v));
                else
                    $attr_string = sprintf('%s %s', $attr_string, trim($v));
            }
        }

        return $attr_string;
    }

    /**
     * @param array $attribute_map
     * @param array $defaults
     * @return array
     */
    protected static function _parse_attribute_arrays(array $attribute_map, array $defaults)
    {
        $attr_count = count($attribute_map);
        $def_count = count($defaults);

        if (0 < $attr_count && 0 < $def_count)
        {
            $combined = $defaults;

            foreach($attribute_map as $k=>$v)
            {
                if (is_string($k))
                {
                    $combined[$k] = $v;
                }
                else
                {
                    $idx = array_search($v, $combined, true);
                    if (-1 === $idx)
                        $combined[] = $v;
                }
            }
        }

        if (0 < $attr_count)
            return $attribute_map;

        if (0 < $def_count)
            return $defaults;

        return array();
    }
}