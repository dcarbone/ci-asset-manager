<?php
if (!class_exists('CssMin'))
    require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'CssMin.php');

if (!class_exists('JSMin'))
    require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'JSMin.php');

require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'DCarbone'.DIRECTORY_SEPARATOR.'AssetManager'.DIRECTORY_SEPARATOR.'Asset'.DIRECTORY_SEPARATOR.'AbstractAsset.php');
require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'DCarbone'.DIRECTORY_SEPARATOR.'AssetManager'.DIRECTORY_SEPARATOR.'Asset'.DIRECTORY_SEPARATOR.'ScriptAsset.php');
require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'DCarbone'.DIRECTORY_SEPARATOR.'AssetManager'.DIRECTORY_SEPARATOR.'Asset'.DIRECTORY_SEPARATOR.'StyleAsset.php');
require_once realpath(__DIR__.DIRECTORY_SEPARATOR.'DCarbone'.DIRECTORY_SEPARATOR.'AssetManager'.DIRECTORY_SEPARATOR.'ComplexOutput.php');

use DCarbone\AssetManager\Asset\AbstractAsset;
use DCarbone\AssetManager\Asset\ScriptAsset;
use DCarbone\AssetManager\Asset\StyleAsset;
use DCarbone\AssetManager\ComplexOutput;

/*
    Asset Management Library for CodeIgniter
    Copyright (C) 2013  Daniel Carbone (https://github.com/dcarbone)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

    /** @var array */
    public static $script_brackets = array();
    /** @var array */
    public static $style_brackets = array();

    /** @var string */
    public $base_url = '';
    /** @var string */
    public $base_path = '';
    /** @var string */
    public $asset_dir = '';
    /** @var string */
    public $asset_path = '';
    /** @var string */
    public $asset_url = '';
    /** @var string */
    public $script_dir  = '';
    /** @var string */
    public $script_path = '';
    /** @var string */
    public $script_url  = '';
    /** @var string */
    public $style_dir  = '';
    /** @var string */
    public $style_path = '';
    /** @var string */
    public $style_url  = '';
    /** @var string */
    public $cache_dir  = '';
    /** @var string */
    public $cache_path = '';
    /** @var string */
    public $cache_url  = '';

    /** @var bool */
    public $dev = false;
    /** @var bool */
    public $combine = true;
    /** @var bool */
    public $minify_scripts = true;
    /** @var bool */
    public $minify_styles = true;
    /** @var bool */
    public $force_curl = false;
    /** @var bool */
    public $styles_output = false;
    /** @var bool */
    public $scripts_output = false;
    /** @var Closure|null */
    public $error_callback = null;

    /** @var array */
    protected $scripts = array();
    /** @var array */
    protected $script_views = array();
    /** @var array */
    protected $styles = array();
    /** @var array */
    protected $groups = array();
    /** @var array */
    protected $loaded = array();
    /** @var \CI_Config|null */
    protected $ci_config = null;
    /** @var array */
    protected $config = null;

    /**
     * @Constructor
     */
    public function __construct()
    {
        /** @var $CFG CI_Config */
        global $CFG;

        if (!($CFG instanceof CI_Config))
            throw new Exception('Unable to load CI_Config global');

        // Set internal reference to global $CFG CI_Config instance
        $this->ci_config = &$CFG;

        if (function_exists('log_message'))
            log_message('info', 'Asset Manager: Library initialized.');

        if ($this->ci_config->load('assetmanager', true, true))
        {
            if (function_exists('log_message'))
                log_message('info', 'Asset Manager: config Loaded from config file.');

            $config_file = $this->ci_config->item('assetmanager');

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
    private function _parse_config(array $config = array())
    {
        // Set some defaults in case they don't pass anything.
        $defaults = array(
            'base_url'          => '',
            'base_path'         => '',

            'asset_dir'         => '',
            'script_dir'        => 'scripts',
            'style_dir'         => 'styles',
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
                $this->$k = $config[$k];
            else
                $this->$k = $v;
        }

        // set the default value for base_url from the config
        if($this->base_url === '' && $this->ci_config !== null)
            $this->base_url = $this->ci_config->item('base_url');
        else if ($this->base_url === '')
            $this->base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'];

        if ($this->base_path === '' && defined('FCPATH'))
            $this->base_path = FCPATH;
        else if ($this->base_path === '')
            $this->base_path = realpath(dirname(dirname(__FILE__)));

        // Define the base asset url and path
        $this->asset_url    = str_ireplace(array('http://', 'https://'), '//', $this->base_url) . (($this->asset_dir !== '') ? $this->asset_dir . '/' : '');
        $this->asset_path   = $this->base_path . (($this->asset_dir !== '') ? $this->asset_dir . '/' : '');

        // Define the script url and path
        $this->script_url   = $this->asset_url . $this->script_dir . '/';
        $this->script_path  = $this->asset_path . $this->script_dir . '/';

        // Define the style url and path
        $this->style_url    = $this->asset_url . $this->style_dir . '/';
        $this->style_path   = $this->asset_path . $this->style_dir . '/';

        // Define the cache url and path
        $this->cache_url    = $this->asset_url . $this->cache_dir . '/';
        $this->cache_path   = $this->asset_path . $this->cache_dir . '/';

        // Now that we have our settings set, get any defined groups!
        if (isset($config['groups']) && is_array($config['groups']))
        {
            foreach($config['groups'] as $group_name => $assets)
            {
                $scripts = ((isset($assets['scripts'])) ? $assets['scripts'] : array());
                $styles = ((isset($assets['styles'])) ? $assets['styles'] : array());
                $groups = ((isset($assets['groups'])) ? $assets['groups'] : array());
                $this->add_asset_group($group_name, $scripts, $styles, $groups);
            }
        }

        if (isset($config['scripts']) && is_array($config['scripts']))
            foreach($config['scripts'] as $scriptName=>$script)
                $this->add_script_file($script, $scriptName);

        if (isset($config['styles']) && is_array($config['styles']))
            foreach($config['styles'] as $styleName=>$style)
                $this->add_style_file($style, $styleName);

        if (function_exists('log_message'))
            log_message('debug', 'Asset Manager: library configured.');
    }

    /**
     * Get array of Configuration Variables
     *
     * This method will be called when creating a new Asset object
     * to allow that object access to these values.
     *
     * @return array
     */
    private function _get_config()
    {
        if ($this->config === null)
        {
            $this->config = array(
                'base_url'      => $this->base_url,
                'base_path'     => $this->base_path,

                'asset_path'    => $this->asset_path,
                'asset_url'     => $this->asset_url,

                'script_dir'    => $this->script_dir,
                'script_url'    => $this->script_url,
                'script_path'   => $this->script_path,

                'style_dir'     => $this->style_dir,
                'style_url'     => $this->style_url,
                'style_path'    => $this->style_path,

                'cache_dir'     => $this->cache_dir,
                'cache_url'     => $this->cache_url,
                'cache_path'    => $this->cache_path,

                'dev'           => $this->dev,
                'combine'       => $this->combine,

                'minify_scripts'    => $this->minify_scripts,
                'minify_styles'     => $this->minify_styles,

                'force_curl'    => $this->force_curl,

                'error_callback' => $this->error_callback
            );
        }
        return $this->config;
    }

    /**
     * Reset all groups
     *
     * @param   boolean $keep_default keep the default group loaded
     * @return  void
     */
    public function reset_assets($keep_default = true)
    {
        $this->styles_output = false;
        $this->scripts_output = false;
        $this->loaded = array();

        if ($keep_default === true)
            $this->load_groups('default');
    }

    /**
     * Add Script File
     *
     * @param array $params
     * @param string $key_name
     * @return void
     */
    public function add_script_file(array $params, $key_name = '')
    {
        $defaults = array(
            'file' => '',
            'minify' => true,
            'cache' => true,
            'name' => (is_numeric($key_name) ? '' : $key_name),
            'group' => array('default'),
            'requires' => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $params['minify'] = ($params['minify'] && $this->minify_scripts);

        $asset = new ScriptAsset($this->_get_config(), $params);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            $groups = $asset->get_groups();

            foreach($groups as $group)
            {
                if (!array_key_exists($group, $this->groups))
                    $this->groups[$group] = array('styles' => array(), 'scripts' => array());

                if (!in_array($name, $this->groups[$group]['scripts']))
                    $this->groups[$group]['scripts'][] = $name;
            }

            if (!array_key_exists($name, $this->scripts))
                $this->scripts[$name] = $asset;
            else
                $this->scripts[$name]->add_groups($groups);
        }
    }

    /**
     * Add Style File
     *
     * @param array $params
     * @param string $keyName
     * @return void
     */
    public function add_style_file(array $params, $keyName = '')
    {
        $defaults = array(
            'file'  => '',
            'media'     => 'all',
            'minify'    => true,
            'cache'     => true,
            'name'      => (is_numeric($keyName) ? '' : $keyName),
            'group'     => array('default'),
            'requires'  => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $params['minify'] = ($params['minify'] && $this->minify_styles);

        // Do a quick sanity check on $group
        if (is_string($params['group']) && $params['group'] !== '')
            $params['group'] = array($params['group']);

        // Create a new Asset
        $asset = new StyleAsset($this->_get_config(), $params);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            $groups = $asset->get_groups();

            foreach($groups as $group)
            {
                if (!array_key_exists($group, $this->groups))
                    $this->groups[$group] = array('styles' => array(), 'scripts' => array());

                if (!in_array($name, $this->groups[$group]['styles']))
                    $this->groups[$group]['styles'][] = $name;
            }

            if (!array_key_exists($name, $this->styles))
                $this->styles[$name] = $asset;
            else
                $this->styles[$name]->add_groups($groups);
        }
    }

    /**
     * Add Asset Group
     *
     * @param string  $group_name name of group
     * @param array  $scripts array of script files
     * @param array  $styles array of style files
     * @param array  $include_groups array of groups to include with this group
     * @return void
     */
    public function add_asset_group($group_name = '', array $scripts = array(), array $styles = array(), array $include_groups = array())
    {
        // Determine if this is a new group or Adding to one that already exists;
        if (!array_key_exists($group_name, $this->groups))
        {
            $this->groups[$group_name] = array(
                'scripts' => array(),
                'styles' => array(),
                'groups' => array()
            );
        }

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
        foreach($styles as $styleName=>$asset)
        {
            $parsed = $this->parse_asset($asset, $group_name);
            $this->add_style_file($parsed, $styleName);
        }
    }

    /**
     * Parse an asset array
     *
     * @param   array   $asset       pre-parsing asset array
     * @param   string  $group_name  Name of group defined with asset
     * @return  array                Parsed asset array
     */
    protected function parse_asset(array $asset, $group_name)
    {
        // If they pass in multiple groups for a specific item within this group.
        $groups = array();
        if (isset($asset['group']))
        {
            if (is_string($asset['group']) && $asset['group'] !== '')
            {
                $groups[] = $asset['group'];
            }
            else if (is_array($asset['group']) && count($asset['group']) > 0)
            {
                foreach($asset['group'] as $g)
                {
                    if (is_string($g) && ($g = trim($g)) !== '')
                        $groups[] = $g;
                }
            }
        }
        else
        {
            $asset['group'] = array();
        }

        // Group name handling
        if (is_string($group_name) && $group_name !== '')
        {
            $overgroup = array($group_name);
            $groups = array_merge($groups, $overgroup);
        }
        else if (is_array($group_name) && count($group_name) > 0)
        {
            foreach($group_name as $gn)
            {
                if (is_string($gn) && ($gn = trim($gn)) !== '')
                    $groups[] = $gn;
            }
        }

        // Ensure we don't have duplicate groups here.
        $groups = array_unique($groups);
        $asset['group'] = $groups;

        return $asset;
    }

    /**
     * Load Scripts for Output
     *
     * @param array|string
     * @return bool
     */
    public function load_scripts($names)
    {
        if (!isset($this->loaded['scripts']))
            $this->loaded['scripts'] = array();

        if ((is_string($names) && $names === '') || (is_array($names) && count($names) < 1))
            return false;

        if (is_string($names))
            $names = array($names);

        foreach($names as $name)
        {
            if (array_key_exists($name, $this->scripts) && !array_key_exists($name, $this->loaded['scripts']))
                $this->loaded['scripts'][$name] = $this->scripts[$name]->get_requires();
        }

        return true;
    }

    /**
     * Load Style Files for Output
     *
     * @param array|string
     * @return bool
     */
    public function load_styles($names)
    {
        if (!isset($this->loaded['styles']))
            $this->loaded['styles'] = array();

        if ((is_string($names) && $names == '') || (is_array($names) && count($names) < 1))
            return false;

        if (is_string($names))
            $names = array($names);

        foreach($names as $name)
        {
            if (array_key_exists($name, $this->styles) && !array_key_exists($name, $this->loaded['styles']))
                $this->loaded['styles'][$name] = $this->styles[$name]->get_requires();
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
            if (array_key_exists($group, $this->groups) && !array_key_exists($group, $this->loaded['groups']))
            {
                foreach($this->groups[$group]['groups'] as $rgroup)
                {
                    $this->load_groups($rgroup);
                }

                $this->load_styles($this->groups[$group]['styles']);
                $this->load_scripts($this->groups[$group]['scripts']);
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
        $required = array();
        $loaded_styles = array();
        $loaded_scripts = array();

        foreach(array('styles', 'scripts') as $type)
        {
            foreach($this->loaded[$type] as $name=>$requires)
            {
                foreach($requires as $require)
                {
                    if (array_key_exists($require, $required))
                        $required[$require]++;
                    else
                        $required[$require] = 1;
                }
            }

            if (count($required) > 0)
            {
                asort($required, SORT_NUMERIC);
                $required = array_reverse($required);
                $reqs = array_keys($required);
                ${'loaded_'.$type} = array_unique(array_merge($reqs, array_keys($this->loaded[$type])));
            }
            else
            {
                ${'loaded_'.$type} = array_keys($this->loaded[$type]);
            }
        }

        // This will hold the final output string.
        $output = '';
        if ($this->dev === false && $this->combine === true)
        {
            $style_assets = (($this->styles_output === false) ? $loaded_styles : array());
            $script_assets = (($this->scripts_output === false) ? $loaded_scripts : array());
            $output .= $this->_generate_complex_output($style_assets, $script_assets);
        }
        else if ($this->dev === true || $this->combine === false)
        {
            $output .= $this->output_styles($loaded_styles);
            $this->styles_output = true;

            $output .= $this->output_scripts($loaded_scripts);
            $this->scripts_output = true;
        }
        return $output;
    }

    /**
     * Generate Complex Output
     *
     * @param array  array of styles to Output
     * @param array  array of scripts to Output
     * @return string
     */
    protected function _generate_complex_output(array $styles, array $scripts)
    {
        /*
        * Build arrays:
        * array(
        *   'asset_name' => Asset_object
        * );
        */
        $_styles = array();
        $_scripts = array();
        // Style files (Cascading Style Sheets)
        foreach($styles as $style)
        {
            if (array_key_exists($style, $this->styles))
                $_styles[$style] = $this->styles[$style];
        }
        // Script files (Javascript)
        foreach($scripts as $script)
        {
            if(array_key_exists($script, $this->scripts))
                $_scripts[$script] = $this->scripts[$script];
            else if (array_key_exists($script, $this->script_views))
                $_scripts[$script] = $this->script_views[$script];
        }

        $complex = new ComplexOutput($_styles, $_scripts, $this->_get_config());

        $output = '';

        if ($this->styles_output === false)
        {
            $styleOutput = $complex->output_styles();
            if ($styleOutput === false)
                $output .= $this->output_styles(array_keys($styles));

            $this->styles_output = true;
        }

        if ($this->scripts_output === false)
        {
            $scriptOutput = $complex->output_scripts();
            if ($scriptOutput === false)
                $output .= $this->output_scripts(array_keys($scripts));

            $this->scripts_output = true;
        }
        return $output;
    }

    /**
     * Generate Style tag Output
     *
     * @param array  array of styles to Output
     * @return string  html style elements
     */
    protected function output_styles(array $styles)
    {
        $medias = array();

        foreach($styles as $style)
        {
            if (array_key_exists($style, $this->styles) && ($this->styles[$style] instanceof StyleAsset) === true)
            {
                if (!isset($medias[$this->styles[$style]->media]))
                    $medias[$this->styles[$style]->media] = array();

                $medias[$this->styles[$style]->media][] = $this->styles[$style];
            }
        }

        ob_start();
        if (isset($medias['all']))
        {
            foreach($medias['all'] as $asset)
            {
                /** @var $asset AbstractAsset */
                echo $asset->get_output();
                echo "\n";
            }
            unset($medias['all']);
            unset($asset);
        }
        if (isset($medias['screen']))
        {
            foreach($medias['screen'] as $asset)
            {
                echo $asset->get_output();
                echo "\n";
            }
            unset($medias['screen']);
            unset($asset);
        }
        if (isset($medias['print']))
        {
            foreach($medias['print'] as $asset)
            {
                echo $asset->get_output();
                echo "\n";
            }
            unset($medias['print']);
            unset($asset);
        }
        return ob_get_clean();
    }

    /**
     * Generate Script tag Output
     *
     * @param array  array of scripts ot Output
     * @return string  html script elements
     */
    protected function output_scripts(array $scripts)
    {
        ob_start();
        foreach($scripts as $script)
        {
            if (array_key_exists($script, $this->scripts) && ($this->scripts[$script] instanceof ScriptAsset) === true)
            {
                echo $this->scripts[$script]->get_output();
                echo "\n";
            }
        }

        return ob_get_clean();
    }
}