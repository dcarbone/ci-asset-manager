<?php namespace DCarbone\AssetManager\Config;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/**
 * Class AssetManagerConfig
 * @package DCarbone\AssetManager\Config
 */
class AssetManagerConfig
{
    /** @var string */
    public static $file_prepend_value = '';

    /** @var string */
    public static $script_file_extension = 'js';
    /** @var string */
    public static $style_file_extension = 'css';
    /** @var string */
    public static $less_file_extension = 'less_styles';

    /** @var array */
    public static $script_brackets = array();
    /** @var array */
    public static $style_brackets = array();

    /** @var \DateTimeZone */
    public static $DateTimeZone;

    /** @var array */
    public static $style_media_output_order = array('all', 'screen', 'print');

    /** @var string */
    protected $base_url = '';
    /** @var string */
    protected $base_path = '';
    /** @var string */
    protected $asset_dir = '';
    /** @var string */
    protected $asset_path = '';
    /** @var string */
    protected $asset_url = '';
    /** @var string */
    protected $script_dir  = '';
    /** @var string */
    protected $script_path = '';
    /** @var string */
    protected $script_url  = '';
    /** @var string */
    protected $style_dir  = '';
    /** @var string */
    protected $style_path = '';
    /** @var string */
    protected $style_url  = '';
    /** @var string */
    protected $less_style_dir = '';
    /** @var string */
    protected $less_style_path = '';
    /** @var string */
    protected $less_style_url = '';
    /** @var string */
    protected $cache_dir  = '';
    /** @var string */
    protected $cache_path = '';
    /** @var string */
    protected $cache_url  = '';

    /** @var bool */
    protected $dev = false;
    /** @var bool */
    protected $combine = true;
    /** @var bool */
    protected $minify_scripts = true;
    /** @var bool */
    protected $minify_styles = true;
    /** @var \Closure|null */
    protected $error_callback = null;

    /** @var array */
    protected $config_groups = array();
    /** @var array */
    protected $config_scripts = array();
    /** @var  */
    protected $config_styles = array();
    /** @var array */
    protected $config_less_styles = array();

    /**
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        /** @var $CFG \CI_Config */
        global $CFG;

        if (key($config) === 'asset_manager')
            $config = reset($config);

        // Set some defaults in case they don't pass anything.
        $defaults = array(
            'base_url'          => '',
            'base_path'         => '',

            'asset_dir'         => '',
            'script_dir'        => 'scripts',
            'style_dir'         => 'styles',
            'less_style_dir'    => 'less_styles',
            'cache_dir'         => 'cache',

            'dev'               => false,
            'combine'           => true,

            'minify_scripts'    => true,
            'minify_styles'     => true,
        );

        // Loop through the configuration file to get our settings, skipping the Groups for now.
        foreach ($defaults as $k=>$v)
        {
            if ($k === 'error_callback')
                $this->set_error_callback($v);
            else if (isset($config[$k]) && $config[$k] !== '' && $config[$k] !== null)
                $this->$k = $config[$k];
            else
                $this->$k = $v;
        }

        // set the default value for base_url from the config
        if($this->base_url === '')
            $this->base_url = $CFG->item('base_url');

        if ($this->base_path === '' && defined('FCPATH'))
            $this->base_path = FCPATH;
        else if ($this->base_path === '')
            $this->base_path = realpath(dirname(dirname(__FILE__)));

        // Only do this once.
        $asset_dir_url = str_replace('\\', '/', $this->asset_dir);

        // Define the base asset url and path
        $this->asset_url = str_ireplace(array('http://', 'https://'), '//', $this->base_url) . $asset_dir_url .'/';
        $this->asset_path = $this->base_path . $this->asset_dir . '/';

        // Define the script url and path
        $this->script_url = $this->asset_url . $asset_dir_url . '/';
        $this->script_path = $this->asset_path . $this->script_dir . DIRECTORY_SEPARATOR;

        // Define the style url and path
        $this->style_url = $this->asset_url . $asset_dir_url . '/';
        $this->style_path = $this->asset_path . $this->style_dir . DIRECTORY_SEPARATOR;
        $this->less_style_path = $this->asset_path . $this->less_style_dir . DIRECTORY_SEPARATOR;

        // Define the cache url and path
        $this->cache_url = $this->asset_url . $asset_dir_url . '/';
        $this->cache_path = $this->asset_path . $this->cache_dir . DIRECTORY_SEPARATOR;
        $this->less_style_url = $this->cache_url;

        // Get a DateTimeZone instance
        if (!isset(static::$DateTimeZone))
        {
            $timezone = config_item('time_reference');
            if (!is_string($timezone) || $timezone === '' || $timezone === 'local')
                $timezone = date_default_timezone_get();

            static::$DateTimeZone = new \DateTimeZone($timezone);
        }

        // Now that we have our settings set, get any config defined assets!
        if (isset($config['groups']) && is_array($config['groups']))
            $this->config_groups = $config['groups'];

        if (isset($config['scripts']) && is_array($config['scripts']))
            $this->config_scripts = $config['scripts'];

        if (isset($config['styles']) && is_array($config['styles']))
            $this->config_styles = $config['styles'];

        if (isset($config['less_styles']) && is_array($config['less_styles']))
            $this->config_less_styles = $config['less_styles'];

        if (function_exists('log_message'))
            log_message('debug', 'AssetManagerConfig: Initialization complete.');
    }

    /**
     * @param string $asset_dir
     */
    public function set_asset_dir($asset_dir)
    {
        $this->asset_dir = $asset_dir;
    }

    /**
     * @return string
     */
    public function get_asset_dir()
    {
        return $this->asset_dir;
    }

    /**
     * @param string $asset_path
     */
    public function set_asset_path($asset_path)
    {
        $this->asset_path = $asset_path;
    }

    /**
     * @return string
     */
    public function get_asset_path()
    {
        return $this->asset_path;
    }

    /**
     * @param string $asset_url
     */
    public function set_asset_url($asset_url)
    {
        $this->asset_url = $asset_url;
    }

    /**
     * @return string
     */
    public function get_asset_url()
    {
        return $this->asset_url;
    }

    /**
     * @param string $base_path
     */
    public function set_base_path($base_path)
    {
        $this->base_path = $base_path;
    }

    /**
     * @return string
     */
    public function get_base_path()
    {
        return $this->base_path;
    }

    /**
     * @param string $base_url
     */
    public function set_base_url($base_url)
    {
        $this->base_url = $base_url;
    }

    /**
     * @return string
     */
    public function get_base_url()
    {
        return $this->base_url;
    }

    /**
     * @param string $cache_dir
     */
    public function set_cache_dir($cache_dir)
    {
        $this->cache_dir = $cache_dir;
    }

    /**
     * @return string
     */
    public function get_cache_dir()
    {
        return $this->cache_dir;
    }

    /**
     * @param string $cache_path
     */
    public function set_cache_path($cache_path)
    {
        $this->cache_path = $cache_path;
    }

    /**
     * @return string
     */
    public function get_cache_path()
    {
        return $this->cache_path;
    }

    /**
     * @param string $cache_url
     */
    public function set_cache_url($cache_url)
    {
        $this->cache_url = $cache_url;
    }

    /**
     * @return string
     */
    public function get_cache_url()
    {
        return $this->cache_url;
    }

    /**
     * @param boolean $combine
     */
    public function set_combine($combine)
    {
        $this->combine = (bool)$combine;
    }

    /**
     * @return boolean
     */
    public function can_combine()
    {
        return $this->combine;
    }

    /**
     * @param boolean $dev
     */
    public function set_dev($dev)
    {
        $this->dev = (bool)$dev;
    }

    /**
     * @return boolean
     */
    public function is_dev()
    {
        return $this->dev;
    }

    /**
     * @param callable $error_callback
     * @throws \InvalidArgumentException
     */
    public function set_error_callback($error_callback)
    {
        if (is_callable($error_callback, false, $callable_name) === false)
            throw new \InvalidArgumentException('AssetManagerConfig::set_error_callback - "$error_callback" must be a callable value');

        if (strpos($callable_name, 'Closure::') !== 0)
            $error_callback = $callable_name;

        $this->error_callback = $error_callback;
    }

    /**
     * @return callable
     */
    public function get_error_callback()
    {
        return $this->error_callback;
    }

    /**
     * @param mixed $args
     */
    public function call_error_callback($args)
    {
        if ($this->error_callback !== null)
        {
            $callback = $this->error_callback;
            $callback($args);
        }
    }

    /**
     * @param string $less_style_dir
     */
    public function set_less_style_dir($less_style_dir)
    {
        $this->less_style_dir = $less_style_dir;
    }

    /**
     * @return string
     */
    public function get_less_style_dir()
    {
        return $this->less_style_dir;
    }

    /**
     * @param string $less_style_path
     */
    public function set_less_style_path($less_style_path)
    {
        $this->less_style_path = $less_style_path;
    }

    /**
     * @return string
     */
    public function get_less_style_path()
    {
        return $this->less_style_path;
    }

    /**
     * @param boolean $minify_scripts
     */
    public function set_minify_scripts($minify_scripts)
    {
        $this->minify_scripts = (bool)$minify_scripts;
    }

    /**
     * @return boolean
     */
    public function can_minify_scripts()
    {
        return $this->minify_scripts;
    }

    /**
     * @param boolean $minify_styles
     */
    public function set_minify_styles($minify_styles)
    {
        $this->minify_styles = (bool)$minify_styles;
    }

    /**
     * @return boolean
     */
    public function can_minify_styles()
    {
        return $this->minify_styles;
    }

    /**
     * @param string $script_dir
     */
    public function set_script_dir($script_dir)
    {
        $this->script_dir = $script_dir;
    }

    /**
     * @return string
     */
    public function get_script_dir()
    {
        return $this->script_dir;
    }

    /**
     * @param string $script_path
     */
    public function set_script_path($script_path)
    {
        $this->script_path = $script_path;
    }

    /**
     * @return string
     */
    public function get_script_path()
    {
        return $this->script_path;
    }

    /**
     * @param string $script_url
     */
    public function set_script_url($script_url)
    {
        $this->script_url = $script_url;
    }

    /**
     * @return string
     */
    public function get_script_url()
    {
        return $this->script_url;
    }

    /**
     * @param string $style_dir
     */
    public function set_style_dir($style_dir)
    {
        $this->style_dir = $style_dir;
    }

    /**
     * @return string
     */
    public function get_style_dir()
    {
        return $this->style_dir;
    }

    /**
     * @param string $style_path
     */
    public function set_style_path($style_path)
    {
        $this->style_path = $style_path;
    }

    /**
     * @return string
     */
    public function get_style_path()
    {
        return $this->style_path;
    }

    /**
     * @param string $style_url
     */
    public function set_style_url($style_url)
    {
        $this->style_url = $style_url;
    }

    /**
     * @return string
     */
    public function get_style_url()
    {
        return $this->style_url;
    }

    /**
     * @return array
     */
    public function get_config_groups()
    {
        return $this->config_groups;
    }

    /**
     * @return array
     */
    public function get_config_less_styles()
    {
        return $this->config_less_styles;
    }

    /**
     * @return array
     */
    public function get_config_scripts()
    {
        return $this->config_scripts;
    }

    /**
     * @return array
     */
    public function get_config_styles()
    {
        return $this->config_styles;
    }

    /**
     * @return string
     */
    public function set_less_style_url()
    {
        return $this->less_style_url;
    }
}