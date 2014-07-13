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
define('ASSET_MANAGER_ROOT_DIR', realpath(__DIR__).DIRECTORY_SEPARATOR);
define('ASSET_MANAGER_CLASSPATH', ASSET_MANAGER_ROOT_DIR.'DCarbone'.DIRECTORY_SEPARATOR.'AssetManager'.DIRECTORY_SEPARATOR);
define('ASSET_MANAGER_ASSET_CLASSPATH', ASSET_MANAGER_CLASSPATH.'Asset'.DIRECTORY_SEPARATOR);
define('ASSET_MANAGER_COLLECTION_CLASSPATH', ASSET_MANAGER_CLASSPATH.'Collection'.DIRECTORY_SEPARATOR);

if (!class_exists('CssMin'))
    require ASSET_MANAGER_ROOT_DIR.'CssMin.php';

// Ensure JShrink is loaded
if (!class_exists('\JShrink\Minifier'))
    require ASSET_MANAGER_ROOT_DIR.'JShrink'.DIRECTORY_SEPARATOR.'Minifier.php';

// Require and load the Less Autoloader class
if (!class_exists('Less_Autoloader'))
{
    require ASSET_MANAGER_ROOT_DIR.'Less'.DIRECTORY_SEPARATOR.'Autoloader.php';
    Less_Autoloader::register();
}

// Require config class
require ASSET_MANAGER_CLASSPATH.'Config'.DIRECTORY_SEPARATOR.'AssetManagerConfig.php';

// Require Asset Interface definition
require ASSET_MANAGER_ASSET_CLASSPATH.'IAsset.php';

// Require class files
require ASSET_MANAGER_ASSET_CLASSPATH.'AbstractAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'ScriptAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'StyleAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'LessStyleAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'AbstractCombinedAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'CombinedScriptAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'CombinedStyleAsset.php';
require ASSET_MANAGER_ASSET_CLASSPATH.'Combined'.DIRECTORY_SEPARATOR.'CombinedLessStyleAsset.php';
require ASSET_MANAGER_COLLECTION_CLASSPATH.'AbstractAssetCollection.php';
require ASSET_MANAGER_COLLECTION_CLASSPATH.'StyleAssetCollection.php';
require ASSET_MANAGER_COLLECTION_CLASSPATH.'ScriptAssetCollection.php';
require ASSET_MANAGER_COLLECTION_CLASSPATH.'LessStyleAssetCollection.php';

use DCarbone\AssetManager\Asset\LessStyleAsset;
use DCarbone\AssetManager\Asset\ScriptAsset;
use DCarbone\AssetManager\Asset\StyleAsset;
use DCarbone\AssetManager\Collection\LessStyleAssetCollection;
use DCarbone\AssetManager\Collection\ScriptAssetCollection;
use DCarbone\AssetManager\Collection\StyleAssetCollection;

/**
 * Class asset_manager
 */
class asset_manager
{
    /** @var \DCarbone\AssetManager\Config\AssetManagerConfig */
    protected $config;

    /** @var array */
    protected $groups = array();
    /** @var array */
    protected $loaded = array();

    /** @var bool */
    protected $styles_output = false;
    /** @var bool */
    protected $scripts_output = false;

    /** @var StyleAssetCollection */
    protected $style_asset_collection;
    /** @var ScriptAssetCollection */
    protected $script_asset_collection;
    /** @var LessStyleAssetCollection */
    protected $less_style_asset_collection;

    /**
     * Constructor
     */
    public function __construct(array $config = array())
    {
        /** @var $CFG \CI_Config */

        global $CFG;

        if (count($config) > 0)
        {
            if (function_exists('log_message'))
                log_message('debug', __CLASS__.': Config loaded from array param');

            $this->config = new \DCarbone\AssetManager\Config\AssetManagerConfig($config);
        }
        else if ($CFG instanceof \CI_Config && $CFG->load('asset_manager', false, true))
        {
            if (function_exists('log_message'))
                log_message('debug', __CLASS__.': Config Loaded from file.');

            $config_file = $CFG->item('asset_manager');
            $this->config = new \DCarbone\AssetManager\Config\AssetManagerConfig($config_file);
        }
        else
        {
            throw new \RuntimeException('asset_manager::__construct - Unable to initialize asset_manager, no "$CFG" global or "$config" array param seen');
        }

        // Initialize our AssetCollections
        $this->style_asset_collection = new StyleAssetCollection(array(), $this->config);
        $this->script_asset_collection = new ScriptAssetCollection(array(), $this->config);
        $this->less_style_asset_collection = new LessStyleAssetCollection(array(), $this->config);

        if (function_exists('log_message'))
            log_message('debug', 'Asset Manager: Library initialized.');

        // Load up the default group
        $this->load_groups('default');
    }

    /**
     * @return \DCarbone\AssetManager\Config\AssetManagerConfig
     */
    public function &get_config()
    {
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
        $this->less_style_asset_collection->reset();
        $this->script_asset_collection->reset();
        $this->style_asset_collection->reset();

        $this->loaded = array();

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

        $asset = new ScriptAsset($params, $this->config);

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
            if (isset($this->script_asset_collection[$name]))
            {
                /** @var ScriptAsset $current_asset */
                $current_asset = $this->script_asset_collection[$name];
                $current_asset->add_groups($groups);
                $this->script_asset_collection->set($name, $current_asset);
            }
            else
            {
                $this->script_asset_collection->set($name, $asset);
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
        $asset = new StyleAsset($params, $this->config);

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

            if (isset($this->style_asset_collection[$name]))
            {
                /** @var StyleAsset $current_asset */
                $current_asset = $this->style_asset_collection[$name];
                $current_asset->add_groups($groups);
                $this->style_asset_collection->set($name, $current_asset);
            }
            else
            {
                $this->style_asset_collection->set($name, $asset);
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
        $asset = new LessStyleAsset($params, $this->config);

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

            if (isset($this->less_style_asset_collection[$name]))
            {
                /** @var LessStyleAsset $current_asset */
                $current_asset = $this->less_style_asset_collection[$name];
                $current_asset->add_groups($groups);
                $this->less_style_asset_collection->set($name, $current_asset);
            }
            else
            {
                $this->less_style_asset_collection->set($name, $asset);
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
            $this->script_asset_collection->add_asset_to_output($name);
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
            $this->style_asset_collection->add_asset_to_output($name);
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
            $this->less_style_asset_collection->add_asset_to_output($name);
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
        foreach($this->config->get_config_groups() as $group_name => $assets)
        {
            $scripts    = (isset($assets['scripts']) ? $assets['scripts'] : array());
            $styles     = (isset($assets['styles'])  ? $assets['styles']  : array());
            $less       = (isset($assets['less_styles'])    ? $assets['less_styles']    : array());
            $groups     = (isset($assets['groups'])  ? $assets['groups']  : array());
            $this->add_asset_group($group_name, $scripts, $styles, $less, $groups);
        }

        foreach($this->config->get_config_scripts() as $script_name=>$script)
            $this->add_script_file($script, $script_name);


        foreach($this->config->get_config_styles() as $style_name=>$style)
            $this->add_style_file($style, $style_name);


        foreach($this->config->get_config_less_styles() as $less_name=>$less)
            $this->add_less_style_file($less, $less_name);

        // This will hold the final output string.
        $output = $this->less_style_asset_collection->generate_output();
        $output .= $this->style_asset_collection->generate_output();
        $output .= $this->script_asset_collection->generate_output();

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
        $this->script_asset_collection->reset();
        foreach($script_names as $script_name)
        {
            $this->script_asset_collection->add_asset_to_output($script_name);
        }

        return $this->script_asset_collection->generate_output();
    }

    /**
     * @param array $style_names
     * @return string
     */
    public function generate_output_for_styles(array $style_names)
    {
        $this->style_asset_collection->reset();
        foreach($style_names as $style_name)
        {
            $this->style_asset_collection->add_asset_to_output($style_name);
        }

        return $this->style_asset_collection->generate_output();
    }

    /**
     * @param array $less_style_names
     * @return string
     */
    public function generate_output_for_less_styles(array $less_style_names)
    {
        $this->less_style_asset_collection->reset();
        foreach($less_style_names as $less_style_name)
        {
            $this->less_style_asset_collection->add_asset_to_output($less_style_name);
        }

        return $this->less_style_asset_collection->generate_output();
    }

    /**
     * @param string $script_name
     * @return string
     */
    public function generate_output_for_script($script_name)
    {
        if (isset($this->script_asset_collection[$script_name]))
            return $this->script_asset_collection[$script_name]->generate_output();

        return null;
    }

    /**
     * @param string $style_name
     * @return string
     */
    public function generate_output_for_style($style_name)
    {
        if (isset($this->style_asset_collection[$style_name]))
            return $this->style_asset_collection[$style_name]->generate_output();

        return null;
    }

    /**
     * @param string $less_style_name
     * @return string
     */
    public function generate_output_for_less_style($less_style_name)
    {
        if (isset($this->less_style_asset_collection[$less_style_name]))
            return $this->less_style_asset_collection[$less_style_name]->generate_output();

        return null;
    }
}