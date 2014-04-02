<?php

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


// Define a few file path constants
define('ASSET_MANAGER_PATH', realpath(__DIR__.DIRECTORY_SEPARATOR.'DCarbone'.DIRECTORY_SEPARATOR.'AssetManager').DIRECTORY_SEPARATOR);
define('ASSET_MANAGER_ASSET_CLASSPATH', ASSET_MANAGER_PATH.'Asset'.DIRECTORY_SEPARATOR);
define('ASSET_MANAGER_COLLECTION_CLASSPATH', ASSET_MANAGER_PATH.'Collection'.DIRECTORY_SEPARATOR);

if (!class_exists('CssMin'))
    require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'CssMin.php');

// Ensure JShrink is loaded
if (!class_exists('\JShrink\Minifier'))
    require_once 'JShrink'.DIRECTORY_SEPARATOR.'Minifier.php';

// Require and load the Less Autoloader class
if (!class_exists('Less_Autoloader'))
{
    require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'Less'.DIRECTORY_SEPARATOR.'Autoloader.php');
    Less_Autoloader::register();
}

// Require Asset Interface definition
require_once ASSET_MANAGER_ASSET_CLASSPATH.'IAsset.php';

// Require class files
require_once ASSET_MANAGER_ASSET_CLASSPATH.'AbstractAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'ScriptAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'StyleAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'LessStyleAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'AbstractCombinedAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'CombinedScriptAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'CombinedStyleAsset.php';
require_once ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'CombinedLessStyleAsset.php';
require_once ASSET_MANAGER_COLLECTION_CLASSPATH.'AbstractAssetCollection.php';
require_once ASSET_MANAGER_COLLECTION_CLASSPATH.'StyleAssetCollection.php';
require_once ASSET_MANAGER_COLLECTION_CLASSPATH.'ScriptAssetCollection.php';
require_once ASSET_MANAGER_COLLECTION_CLASSPATH.'LessStyleAssetCollection.php';

use DCarbone\AssetManager\Asset\LessStyleAsset;
use DCarbone\AssetManager\Asset\ScriptAsset;
use DCarbone\AssetManager\Asset\StyleAsset;
use DCarbone\AssetManager\Collection\LessStyleAssetCollection;
use DCarbone\AssetManager\Collection\ScriptAssetCollection;
use DCarbone\AssetManager\Collection\StyleAssetCollection;

/**
 * Class AssetManager
 */
class AssetManager
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
//    protected $less_style_url = '';
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
    /** @var bool */
    protected $force_curl = false;
    /** @var bool */
    protected $styles_output = false;
    /** @var bool */
    protected $scripts_output = false;
    /** @var \Closure|null */
    protected $error_callback = null;

    /** @var array */
    protected $groups = array();
    /** @var array */
    protected $loaded = array();

    /** @var StyleAssetCollection */
    protected $StyleAssetCollection;
    /** @var ScriptAssetCollection */
    protected $ScriptAssetCollection;
    /** @var LessStyleAssetCollection */
    protected $LessStyleAssetCollection;

    /** @var array */
    protected static $portable_config = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var $CFG CI_Config */
        global $CFG;

        if (!($CFG instanceof \CI_Config))
            throw new Exception('Unable to load CI_Config global');

        if (function_exists('log_message'))
            log_message('info', 'Asset Manager: Library initialized.');

        if ($CFG->load('assetmanager', false, true))
        {
            if (function_exists('log_message'))
                log_message('info', 'Asset Manager: config Loaded from config file.');

            $config_file = $CFG->item('assetmanager');

            // Initialize our AssetCollections
            $this->StyleAssetCollection = new StyleAssetCollection();
            $this->ScriptAssetCollection = new ScriptAssetCollection();
            $this->LessStyleAssetCollection = new LessStyleAssetCollection();

            // Parse configuration
            $this->_parse_config($config_file);

            // Load up the default group
            $this->load_groups('default');
        }
        else
        {
            if (function_exists('log_message'))
                log_message('error', 'Asset Manager config file unable to Load.');

            throw new Exception('AssetManager:  Unable to load "assetmanager.php" configuration file');
        }
    }

    /**
     * Parse Config File
     *
     * @param $config $config Configuration array defined in /config/assetmanager.php
     * @return void
     */
    protected function _parse_config(array $config = array())
    {
        /** @var $CFG \CI_Config */
        global $CFG;

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
            'force_curl'        => false
        );

        // Loop through the configuration file to get our settings, skipping the Groups for now.
        foreach ($defaults as $k=>$v)
        {
            if (isset($config[$k]) && $config[$k] !== '' && $config[$k] !== null)
                $this->{'set_'.$k}($config[$k]);
            else
                $this->{'set_'.$k}($v);
        }

        // set the default value for base_url from the config
        if($this->base_url === '')
            $this->set_base_url($CFG->item('base_url'));

        if ($this->base_path === '' && defined('FCPATH'))
            $this->set_base_path(FCPATH);
        else if ($this->base_path === '')
            $this->set_base_path(realpath(dirname(dirname(__FILE__))));

        // Define the base asset url and path
        $this->set_asset_url(str_ireplace(array('http://', 'https://'), '//', $this->base_url) . str_replace('\\', '/', $this->asset_dir) .'/');
        $this->set_asset_path($this->base_path . str_replace('\\', '/', $this->asset_dir) . '/');

        // Define the script url and path
        $this->set_script_url($this->asset_url . str_replace('\\', '/', $this->script_dir) . '/');
        $this->set_script_path($this->asset_path . $this->script_dir . DIRECTORY_SEPARATOR);

        // Define the style url and path
        $this->set_style_url($this->asset_url . str_replace('\\', '/', $this->style_dir) . '/');
        $this->set_style_path($this->asset_path . $this->style_dir . DIRECTORY_SEPARATOR);
        $this->set_less_style_path($this->asset_path . $this->less_style_dir . DIRECTORY_SEPARATOR);

        // Define the cache url and path
        $this->set_cache_url($this->asset_url . str_replace('\\', '/', $this->cache_dir) . '/');
        $this->set_cache_path($this->asset_path . $this->cache_dir . DIRECTORY_SEPARATOR);
        $this->set_less_style_url($this->cache_url);

        // Get a DateTimeZone instance
        static::$DateTimeZone = new \DateTimeZone('UTC');

        // Now that we have our settings set, get any defined groups!
        if (isset($config['groups']) && is_array($config['groups']))
        {
            foreach($config['groups'] as $group_name => $assets)
            {
                $scripts    = (isset($assets['scripts']) ? $assets['scripts'] : array());
                $styles     = (isset($assets['styles'])  ? $assets['styles']  : array());
                $less       = (isset($assets['less_styles'])    ? $assets['less_styles']    : array());
                $groups     = (isset($assets['groups'])  ? $assets['groups']  : array());
                $this->add_asset_group($group_name, $scripts, $styles, $less, $groups);
            }
        }

        if (isset($config['scripts']) && is_array($config['scripts']))
            foreach($config['scripts'] as $script_name=>$script)
                $this->add_script_file($script, $script_name);

        if (isset($config['styles']) && is_array($config['styles']))
            foreach($config['styles'] as $style_name=>$style)
                $this->add_style_file($style, $style_name);

        if (isset($config['less_styles']) && is_array($config['less_styles']))
            foreach($config['less_styles'] as $less_name=>$less)
                $this->add_less_style_file($less, $less_name);

        if (function_exists('log_message'))
            log_message('debug', 'Asset Manager: library configured.');
    }

    /**
     * @return array|null
     */
    public static function get_config()
    {
        return static::$portable_config;
    }

    /**
     * @return bool
     */
    public static function is_dev()
    {
        return static::$portable_config['dev'];
    }

    /**
     * Reset all groups
     *
     * @param   boolean $keep_default keep the default group loaded
     * @return  void
     */
    public function reset_assets($keep_default = true)
    {
        $this->LessStyleAssetCollection->reset();
        $this->ScriptAssetCollection->reset();
        $this->StyleAssetCollection->reset();

        if ($keep_default === true)
            $this->load_groups('default');
    }

    /**
     * Add Script File
     *
     * @param array $params
     * @param string $script_name
     * @return void
     */
    public function add_script_file(array $params, $script_name = '')
    {
        $defaults = array(
            'file' => '',
            'minify' => true,
            'cache' => true,
            'name' => (is_numeric($script_name) ? '' : $script_name),
            'group' => array('default'),
            'requires' => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $params['minify_able'] = ($params['minify'] && $this->minify_scripts);

        $asset = new ScriptAsset($params);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            $groups = $asset->get_groups();

            foreach($groups as $group)
            {
                $this->init_group($group);

                if (!in_array($name, $this->groups[$group]['scripts']))
                    $this->groups[$group]['scripts'][] = $name;
            }

            // If an asset with the same name already exists, merge groups and move on
            if (isset($this->ScriptAssetCollection[$name]))
            {
                /** @var ScriptAsset $current_asset */
                $current_asset = $this->ScriptAssetCollection[$name];
                $current_asset->add_groups($groups);
                $this->ScriptAssetCollection->set($name, $current_asset);
            }
            else
            {
                $this->ScriptAssetCollection->set($name, $asset);
            }
        }
    }

    /**
     * Add Style File
     *
     * @param array $params
     * @param string $style_name
     * @return void
     */
    public function add_style_file(array $params, $style_name = '')
    {
        $defaults = array(
            'file'  => '',
            'media'     => 'all',
            'minify'    => true,
            'cache'     => true,
            'name'      => (is_numeric($style_name) ? '' : $style_name),
            'group'     => array('default'),
            'requires'  => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $params['minify_able'] = ($params['minify'] && $this->minify_styles);

        // Do a quick sanity check on $group
        if (is_string($params['group']) && $params['group'] !== '')
            $params['group'] = array($params['group']);

        // Create a new Asset
        $asset = new StyleAsset($params);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            $groups = $asset->get_groups();

            foreach($groups as $group)
            {
                $this->init_group($group);

                if (!in_array($name, $this->groups[$group]['styles']))
                    $this->groups[$group]['styles'][] = $name;
            }

            if (isset($this->StyleAssetCollection[$name]))
            {
                /** @var StyleAsset $current_asset */
                $current_asset = $this->StyleAssetCollection[$name];
                $current_asset->add_groups($groups);
                $this->StyleAssetCollection->set($name, $current_asset);
            }
            else
            {
                $this->StyleAssetCollection->set($name, $asset);
            }
        }
    }

    /**
     * @param array $params
     * @param string $less_style_name
     */
    public function add_less_style_file(array $params, $less_style_name = '')
    {
        $defaults = array(
            'file'  => '',
            'media'     => 'all',
            'name'      => (is_numeric($less_style_name) ? '' : $less_style_name),
            'group'     => array('default'),
            'requires'  => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        // Do a quick sanity check on $group
        if (is_string($params['group']) && $params['group'] !== '')
            $params['group'] = array($params['group']);

        // Create a new Asset
        $asset = new LessStyleAsset($params);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            $groups = $asset->get_groups();

            foreach($groups as $group)
            {
                $this->init_group($group);

                if (!in_array($name, $this->groups[$group]['less_styles']))
                    $this->groups[$group]['less_styles'][] = $name;
            }

            if (isset($this->LessStyleAssetCollection[$name]))
            {
                /** @var LessStyleAsset $current_asset */
                $current_asset = $this->LessStyleAssetCollection[$name];
                $current_asset->add_groups($groups);
                $this->LessStyleAssetCollection->set($name, $current_asset);
            }
            else
            {
                $this->LessStyleAssetCollection->set($name, $asset);
            }
        }
    }

    /**
     * @param string $group_name
     * @return void
     */
    protected function init_group($group_name)
    {
        if (array_key_exists($group_name, $this->groups))
            return;

        $this->groups[$group_name] = array(
            'styles' => array(),
            'scripts' => array(),
            'less_styles' => array(),
            'groups' => array(),
        );
    }

    /**
     * Add Asset Group
     *
     * @param string $group_name name of group
     * @param array $scripts array of script files
     * @param array $styles array of style files
     * @param array $less array of less style files
     * @param array $include_groups array of groups to include with this group
     * @return void
     */
    public function add_asset_group(
        $group_name = '',
        array $scripts = array(),
        array $styles = array(),
        array $less = array(),
        array $include_groups = array())
    {
        // Determine if this is a new group or Adding to one that already exists;
        $this->init_group($group_name);

        // If this group requires another group...
        if (count($include_groups) > 0)
        {
            $merged = array_merge($this->groups[$group_name]['groups'], $include_groups);
            $unique = array_unique($merged);
            $this->groups[$group_name]['groups'] = $unique;
        }

        // Parse our script files
        foreach($scripts as $script_name=>$asset)
        {
            $parsed = $this->parse_asset($asset, $group_name);
            $this->add_script_file($parsed, $script_name);
        }
        // Do this so we are sure to have a clean $asset variable
        unset($asset);

        // Parse our style files
        foreach($styles as $style_name=>$asset)
        {
            $parsed = $this->parse_asset($asset, $group_name);
            $this->add_style_file($parsed, $style_name);
        }
        unset($asset);

        // Parse less style files
        foreach($less as $less_name=>$asset)
        {
            $parsed = $this->parse_asset($asset, $group_name);
            $this->add_less_style_file($parsed, $less_name);
        }
    }

    /**
     * Parse an asset array
     *
     * @param   array   $asset_params       pre-parsing asset array
     * @param   string  $group_name  Name of group defined with asset
     * @return  array                Parsed asset array
     */
    protected function parse_asset(array $asset_params, $group_name)
    {
        // If they pass in multiple groups for a specific item within this group.
        $groups = array();
        if (!isset($asset_params['group']))
            $asset_params['group'] = array();

        if (is_string($asset_params['group']))
            $asset_params['group'] = array($asset_params['group']);

        foreach($asset_params['group'] as $g)
        {
            if (is_string($g) && ($g = trim($g)) !== '')
                $groups[] = $g;
        }

        // Group name handling
        if (is_string($group_name))
            $group_name = array($group_name);

        foreach($group_name as $gn)
        {
            if (is_string($gn) && ($gn = trim($gn)) !== '')
                $groups[] = $gn;
        }

        // Ensure we don't have duplicate groups here.
        $groups = array_unique($groups);
        $asset_params['group'] = $groups;

        return $asset_params;
    }

    /**
     * Load Scripts for Output
     *
     * @param array|string $script_names
     * @return bool
     */
    public function load_scripts($script_names)
    {
        if ((is_string($script_names) && $script_names === '') || (is_array($script_names) && count($script_names) < 1))
            return false;

        if (is_string($script_names))
            $script_names = array($script_names);

        foreach($script_names as $name)
        {
            $this->ScriptAssetCollection->add_asset_to_output($name);
        }

        return true;
    }

    /**
     * Load Style Files for Output
     *
     * @param array|string
     * @return bool
     */
    public function load_styles($style_names)
    {
        if ((is_string($style_names) && $style_names == '') || (is_array($style_names) && count($style_names) < 1))
            return false;

        if (is_string($style_names))
            $style_names = array($style_names);

        foreach($style_names as $name)
        {
            $this->StyleAssetCollection->add_asset_to_output($name);
        }

        return true;
    }

    /**
     * @param $less_style_names
     * @return bool
     */
    public function load_less_styles($less_style_names)
    {
        if ((is_string($less_style_names) && $less_style_names == '') || (is_array($less_style_names) && count($less_style_names) < 1))
            return false;

        if (is_string($less_style_names))
            $less_style_names = array($less_style_names);

        foreach($less_style_names as $name)
        {
            $this->LessStyleAssetCollection->add_asset_to_output($name);
        }

        return true;
    }

    /**
     * Load Asset Group for Output
     *
     * @param string|array
     * @return bool
     */
    public function load_groups($groups)
    {
        if ((is_string($groups) && $groups == '') || (is_array($groups) && count($groups) < 1))
            return false;

        if (!isset($this->loaded['groups']))
            $this->loaded['groups'] = array();

        if (is_string($groups))
            $groups = array($groups);

        foreach($groups as $group)
        {
            if (array_key_exists($group, $this->groups) && array_key_exists('groups', $this->groups[$group]) && !array_key_exists($group, $this->loaded['groups']))
            {
                foreach($this->groups[$group]['groups'] as $rgroup)
                {
                    $this->load_groups($rgroup);
                }

                $this->load_styles($this->groups[$group]['styles']);
                $this->load_scripts($this->groups[$group]['scripts']);
                $this->load_less_styles($this->groups[$group]['less_styles']);
                $this->loaded['groups'][$group] = $this->groups[$group];
            }
        }

        return true;
    }

    /**
     * Generate Output for page
     *
     * @return string  Output HTML for Styles and Scripts
     */
    public function generate_output()
    {
        // This will hold the final output string.
        $output = $this->LessStyleAssetCollection->generate_output();
        $output .= $this->StyleAssetCollection->generate_output();
        $output .= $this->ScriptAssetCollection->generate_output();

        $this->styles_output = true;
        $this->scripts_output = true;

        return $output;
    }

    /**
     * Generate Script tag Output
     *
     * @param array  array of scripts ot Output
     * @return string  html script elements
     */
    public function generate_output_for_scripts(array $script_names)
    {
        $this->ScriptAssetCollection->reset();
        foreach($script_names as $script_name)
        {
            $this->ScriptAssetCollection->add_asset_to_output($script_name);
        }

        return $this->ScriptAssetCollection->generate_output();
    }

    /**
     * @param array $style_names
     * @return string
     */
    public function generate_output_for_styles(array $style_names)
    {
        $this->StyleAssetCollection->reset();
        foreach($style_names as $style_name)
        {
            $this->StyleAssetCollection->add_asset_to_output($style_name);
        }

        return $this->StyleAssetCollection->generate_output();
    }

    /**
     * @param array $less_style_names
     * @return string
     */
    public function generate_output_for_less_styles(array $less_style_names)
    {
        $this->LessStyleAssetCollection->reset();
        foreach($less_style_names as $less_style_name)
        {
            $this->LessStyleAssetCollection->add_asset_to_output($less_style_name);
        }

        return $this->LessStyleAssetCollection->generate_output();
    }

    /**
     * @param string $script_name
     * @return string
     */
    public function generate_output_for_script($script_name)
    {
        if (isset($this->ScriptAssetCollection[$script_name]))
            return $this->ScriptAssetCollection[$script_name]->generate_output();

        return null;
    }

    /**
     * @param string $style_name
     * @return string
     */
    public function generate_output_for_style($style_name)
    {
        if (isset($this->StyleAssetCollection[$style_name]))
            return $this->StyleAssetCollection[$style_name]->generate_output();

        return null;
    }

    /**
     * @param string $less_style_name
     * @return string
     */
    public function generate_output_for_less_style($less_style_name)
    {
        if (isset($this->LessStyleAssetCollection[$less_style_name]))
            return $this->LessStyleAssetCollection[$less_style_name]->generate_output();

        return null;
    }

    /**
     *
     *
     *
     * Getters and Setters.  Should not have to modify these.
     *
     *
     *
     */

    /**
     * @param string $asset_dir
     */
    public function set_asset_dir($asset_dir)
    {
        $this->asset_dir = $asset_dir;
        $this->update_portable_config(__FUNCTION__, $asset_dir);
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
        $this->update_portable_config(__FUNCTION__, $asset_path);
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
        $this->update_portable_config(__FUNCTION__, $asset_url);
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
        $this->update_portable_config(__FUNCTION__, $base_path);
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
        $this->update_portable_config(__FUNCTION__, $base_url);
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
        $this->update_portable_config(__FUNCTION__, $cache_dir);
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
        $this->update_portable_config(__FUNCTION__, $cache_path);
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
        $this->update_portable_config(__FUNCTION__, $cache_url);
    }

    /**
     * @return string
     */
    public function get_cache_url()
    {
        return $this->cache_url;
    }

    /**
     * @param string $less_url
     * @return void
     */
    public function set_less_style_url($less_url)
    {
        $this->less_style_url = $less_url;
        $this->update_portable_config(__FUNCTION__, $less_url);
    }

    /**
     * @return string
     */
    public function get_less_style_url()
    {
        return $this->less_style_url;
    }

    /**
     * @param boolean $combine
     */
    public function set_combine($combine)
    {
        $this->combine = $combine;
        $this->update_portable_config(__FUNCTION__, $combine);
    }

    /**
     * @return boolean
     */
    public function get_combine()
    {
        return $this->combine;
    }

    /**
     * @param boolean $dev
     */
    public function set_dev($dev)
    {
        $this->dev = $dev;
        $this->update_portable_config(__FUNCTION__, $dev);
    }

    /**
     * @return boolean
     */
    public function get_dev()
    {
        return $this->dev;
    }

    /**
     * @param callable|null $error_callback
     */
    public function set_error_callback($error_callback)
    {
        $this->error_callback = $error_callback;
        $this->update_portable_config(__FUNCTION__, $error_callback);
    }

    /**
     * @return callable|null
     */
    public function get_error_callback()
    {
        return $this->error_callback;
    }

    /**
     * @param boolean $force_curl
     */
    public function set_force_curl($force_curl)
    {
        $this->force_curl = $force_curl;
        $this->update_portable_config(__FUNCTION__, $force_curl);
    }

    /**
     * @return boolean
     */
    public function get_force_curl()
    {
        return $this->force_curl;
    }

    /**
     * @param string $less_style_dir
     */
    public function set_less_style_dir($less_style_dir)
    {
        $this->less_style_dir = $less_style_dir;
        $this->update_portable_config(__FUNCTION__, $less_style_dir);
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
        $this->update_portable_config(__FUNCTION__, $less_style_path);
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
        $this->minify_scripts = $minify_scripts;
        $this->update_portable_config(__FUNCTION__, $minify_scripts);
    }

    /**
     * @return boolean
     */
    public function get_minify_scripts()
    {
        return $this->minify_scripts;
    }

    /**
     * @param boolean $minify_styles
     */
    public function set_minify_styles($minify_styles)
    {
        $this->minify_styles = $minify_styles;
        $this->update_portable_config(__FUNCTION__, $minify_styles);
    }

    /**
     * @return boolean
     */
    public function get_minify_styles()
    {
        return $this->minify_styles;
    }

    /**
     * @param string $script_dir
     */
    public function set_script_dir($script_dir)
    {
        $this->script_dir = $script_dir;
        $this->update_portable_config(__FUNCTION__, $script_dir);
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
        $this->update_portable_config(__FUNCTION__, $script_path);
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
        $this->update_portable_config(__FUNCTION__, $script_url);
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
        $this->update_portable_config(__FUNCTION__, $style_dir);
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
        $this->update_portable_config(__FUNCTION__, $style_path);
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
        $this->update_portable_config(__FUNCTION__, $style_url);
    }

    /**
     * @return string
     */
    public function get_style_url()
    {
        return $this->style_url;
    }

    /**
     * @param string $function
     * @param mixed $value
     */
    protected function update_portable_config($function, $value)
    {
        $variable = substr($function, 4);
        static::$portable_config[$variable] = $value;
    }
}