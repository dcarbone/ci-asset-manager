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

        $this->_determine_root_asset_paths(
            isset($config['asset_dir_relative_path']) ? $config['asset_dir_relative_path'] : null
        );

        $this->_determine_asset_type_paths($config);
    }

    /**
     * @param string $content
     * @param array $html_attributes
     * @return string
     */
    public function javascript_tag($content = null, array $html_attributes = array())
    {
        return vsprintf('<script%s>%s</script>',
            array(
                static::_generate_attribute_string(array_merge(array('type' => 'text/javascript'), $html_attributes)),
                (string)$content
            )
        );
    }

    /**
     * @param string $content
     * @param array $html_attributes
     * @return string
     */
    public function stylesheet_tag($content = null, array $html_attributes = array())
    {
        return vsprintf('<style%s>%s</style>',
            array(
                static::_generate_attribute_string(array_merge(array('rel' => 'stylesheet'), $html_attributes)),
                (string)$content
            )
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
        $parsed = static::_parse_include_args($args);
        $files = $parsed[0];
        $html_attributes = $parsed[1];

        $output = '';
        foreach($files as $file)
        {
            if ('javascript' === $type)
            {
                if (!preg_match(static::GLOB_REGEX, $file))
                {
                    $output = vsprintf(
                        '%s%s',
                        array(
                            $output,
                            $this->_include_javascript_asset($file, $html_attributes)
                        ));
                }
                else
                {
                    $new_args = static::_parse_glob_string($this->javascript_dir_full_path, $file);
                    $new_args[] = $html_attributes;
                    $output = vsprintf(
                        '%s%s',
                        array(
                            $output,
                            $this->_include_assets($new_args, $type)
                        )
                    );
                }
            }
            else if ('stylesheet' === $type)
            {
                if (!preg_match(static::GLOB_REGEX, $file))
                {
                    $output = vsprintf(
                        '%s%s',
                        array(
                            $output,
                            $this->output_stylesheet_asset($file, $html_attributes)
                        )
                    );
                }
                else
                {
                    $new_args = static::_parse_glob_string($this->stylesheet_dir_full_path, $file);
                    $new_args[] = $html_attributes;
                    $output = vsprintf(
                        '%s%s',
                        array(
                            $output,
                            $this->_include_assets($new_args, $type)
                        )
                    );
                }
            }
        }

        return $output;
    }

    /**
     * @param array $args
     * @return array
     */
    private static function _parse_include_args(array $args)
    {
        $files = array();
        $html_attributes = array();
        foreach($args as $arg)
        {
            switch(true)
            {
                case is_string($arg):
                    $files[] = $arg;
                    break;
                case is_array($arg):
                    $html_attributes = $arg;
                    break 2;

                default:
                    break 2;
            }
        }

        return array(
            $files,
            $html_attributes
        );
    }

    /**
     * @param string $file
     * @param array $html_attributes
     * @return string
     */
    protected function _include_javascript_asset($file, array $html_attributes)
    {
        $file = static::_trim_path($file);
        if (false === strpos($file, '.js'))
            $file = vsprintf('%s.js', array($file));

        if (file_exists(vsprintf('%s%s', array($this->javascript_dir_full_path, $file))))
        {
            return vsprintf("<script src=\"%s%s\"%s></script>\n", array(
                $this->javascript_uri,
                $file,
                static::_generate_attribute_string(
                    array_merge(array('type' => 'text/javascript'), $html_attributes)
                )
            ));
        }

        $msg = vsprintf(
            'ci-asset-manager - Could not locate requested javascript asset "%s".',
            array($file));
        log_message('error', $msg);
        throw new \RuntimeException($msg);
    }

    /**
     * @param string $file
     * @param array $html_attributes
     * @return string
     */
    public function output_stylesheet_asset($file, array $html_attributes)
    {
        $file = static::_trim_path($file);
        if (false === strpos($file, '.css'))
            $file = vsprintf('%s.css', array($file));

        if (file_exists(vsprintf('%s%s', array($this->stylesheet_dir_full_path, $file))))
        {
            return vsprintf("<link href=\"%s%s\"%s />\n", array(
                $this->stylesheet_uri,
                $file,
                static::_generate_attribute_string(
                    array_merge(array('rel' => 'stylesheet'), $html_attributes)
                )
            ));
        }

        $msg = vsprintf(
            'ci-asset-manager - Could not locate requested stylesheet asset "%s".',
            array($file));
        log_message('error', $msg);
        throw new \RuntimeException($msg);
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function _uri_from_relative_path($path)
    {
        return static::_trim_path(base_url(static::_cleanup_path($path))).'/';
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
        return trim($path, " \t\n\r\0\x0B/");
    }

    /**
     * @param string $root
     * @param string $input
     * @return string[]
     */
    protected static function _parse_glob_string($root, $input)
    {
        $glob = glob(
            vsprintf(
                '%s%s',
                array(
                    $root,
                    $input
                )
            ),
            GLOB_NOSORT | GLOB_BRACE);
        $files = array();
        for ($i = 0, $count = count($glob); $i < $count; $i++)
        {
            if (substr($glob[$i], -1) === '.')
                continue;

            $files[] = str_ireplace($root, '', $glob[$i]);
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
            array('#[/\\\]+#S',
                vsprintf('#%s#iS', array($asset_dir_full_path))),
            array(DIRECTORY_SEPARATOR,
                ''),
            $file_realpath);
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
     * @param string|null $config_value
     */
    protected function _determine_root_asset_paths($config_value = null)
    {
        if ($config_value)
            $path = trim($config_value, "/\\");
        else
            $path = 'assets';

        $realpath = realpath($path);

        if (false === $realpath)
        {
            $msg = vsprintf('ci-asset-manager - Asset directory "%s" does not appear to exist.', array($path));
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->asset_dir_relative_path = vsprintf('%s/', array($path));
            $this->asset_dir_full_path = vsprintf('%s/', array($realpath));
            $this->asset_dir_uri = static::_uri_from_relative_path($this->asset_dir_relative_path);
        }
        else
        {
            $msg = vsprintf('ci-asset-manager - Specified asset path "%s" is not readable or writable.', array($path));
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * @param array $config
     */
    protected function _determine_asset_type_paths(array $config)
    {
        if (isset($config['javascript_dir_name']))
            $js_dir = static::_trim_path($config['javascript_dir_name']);
        else
            $js_dir = 'js';

        $realpath = realpath(vsprintf('%s%s', array($this->asset_dir_full_path, $js_dir)));

        if (false === $realpath)
        {
            $msg = vsprintf(
                'ci-asset-manager - Specified Javascript directory "%s" does not appear to exist.',
                array($js_dir));
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->javascript_dir_name = $js_dir;
            $this->javascript_dir_full_path = vsprintf('%s/', array($realpath));
            $this->javascript_uri = static::_uri_from_relative_path(
                vsprintf('%s%s/', array(
                    $this->asset_dir_relative_path,
                    $js_dir
                ))
            );
        }
        else
        {
            $msg = vsprintf(
                'ci-asset-manager - Specified Javascript directory "%s" does not appear to be readable or writable.',
                array($js_dir));
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (isset($config['stylesheet_dir_name']))
            $css_dir = static::_trim_path($config['stylesheet_dir_name']);
        else
            $css_dir = 'css';

        $realpath = realpath(vsprintf('%s%s', array($this->asset_dir_full_path, $css_dir)));

        if (false === $realpath)
        {
            $msg = vsprintf(
                'ci-asset-manager - Specified Stylesheet directory %s does not appear to exist.',
                array($css_dir));
            log_message('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (static::_determine_path_readable_writeable($realpath))
        {
            $this->stylesheet_dir_name = $css_dir;
            $this->stylesheet_dir_full_path = vsprintf('%s/', array($realpath));
            $this->stylesheet_uri = static::_uri_from_relative_path(
                vsprintf('%s%s/', array(
                    $this->asset_dir_relative_path,
                    $css_dir
                ))
            );
        }
        else
        {
            $msg = vsprintf(
                'ci-asset-manager - Specified Stylesheet directory "%s" does not appear to be readable or writable.',
                array($css_dir));
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
     * @return string
     */
    protected static function _generate_attribute_string(array $attribute_map)
    {
        $attr_string = '';
        if (count($attribute_map) > 0)
        {
            foreach($attribute_map as $k=>$v)
            {
                $attr_string = vsprintf('%s %s="%s"', array($attr_string, trim($k), trim($v)));
            }
        }

        return $attr_string;
    }
}
