<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

if (!class_exists('\Composer\Autoload\ClassLoader'))
    require FCPATH.'vendor/autoload.php';

use DCarbone\AssetManager\Asset\LessStyleAsset;
use DCarbone\AssetManager\Asset\ScriptAsset;
use DCarbone\AssetManager\Asset\StyleAsset;
use DCarbone\AssetManager\Collection\LessStyleAssetCollection;
use DCarbone\AssetManager\Collection\ScriptAssetCollection;
use DCarbone\AssetManager\Collection\StyleAssetCollection;

/**
 * Class asset_manager
 */
class asset_manager implements \SplObserver
{
    // These are used as part of the Observer implementation
    const ASSET_REMOVED = 0;
    const ASSET_ADDED = 1;
    const ASSET_MODIFIED = 2;

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

        // Attach self as observer
        $this->style_asset_collection->attach($this);
        $this->script_asset_collection->attach($this);
        $this->less_style_asset_collection->attach($this);

        if (function_exists('log_message'))
            log_message('debug', 'Asset Manager: Library initialized.');
        
        foreach($this->config->get_config_groups() as $k=>$v)
        {
            $this->add_asset_group(
                $k,
                (isset($v['scripts']) ? $v['scripts'] : array()),
                (isset($v['styles']) ? $v['styles'] : array()),
                (isset($v['less_styles']) ? $v['less_styles'] : array()),
                (isset($v['groups'])  ? $v['groups']  : array())
            );
        }

        foreach($this->config->get_config_styles() as $k=>$v)
            $this->add_style_file($v, $k);
        
        foreach($this->config->get_config_scripts() as $k=>$v)
            $this->add_script_file($v, $k);
        
        foreach($this->config->get_config_less_styles() as $k=>$v)
            $this->add_less_style_file($v, $k);
        
        // Load up the default group
        $this->load_groups('default');
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
     * @return ScriptAsset|null
     */
    public function add_script_file(array $params, $script_name = '')
    {
        $defaults = array(
            'file' => '',
            'minify' => true,
            'cache' => true,
            'name' => (is_numeric($script_name) ? '' : $script_name),
            'group' => '',
            'requires' => '',
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $params['minify_able'] = ($params['minify'] && $this->config->can_minify_scripts());

        $asset = new ScriptAsset($params, $this->config);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            if (isset($this->script_asset_collection[$name]))
                $this->script_asset_collection[$name]->add_groups($asset->get_groups());
            else
                $this->script_asset_collection->set($name, $asset);

            return $this->script_asset_collection[$name];
        }
    }

    /**
     * Add Style File
     *
     * @param array $params
     * @param string $style_name
     * @return StyleAsset|null
     */
    public function add_style_file(array $params, $style_name = '')
    {
        $defaults = array(
            'file'  => '',
            'media'     => 'all',
            'minify'    => true,
            'cache'     => true,
            'name'      => (is_numeric($style_name) ? '' : $style_name),
            'group'     => '',
            'requires'  => '',
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $params['minify_able'] = ($params['minify'] && $this->config->can_minify_styles());

        // Do a quick sanity check on $group
        if (is_string($params['group']) && $params['group'] !== '')
            $params['group'] = array($params['group']);

        // Create a new Asset
        $asset = new StyleAsset($params, $this->config);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            if (isset($this->style_asset_collection[$name]))
                $this->style_asset_collection[$name]->add_groups($asset->get_groups());
            else
                $this->style_asset_collection->set($name, $asset);

            return $this->style_asset_collection[$name];
        }
    }

    /**
     * @param array $params
     * @param string $less_style_name
     * @return LessStyleAsset|null
     */
    public function add_less_style_file(array $params, $less_style_name = '')
    {
        $defaults = array(
            'file'  => '',
            'media'     => 'all',
            'name'      => (is_numeric($less_style_name) ? '' : $less_style_name),
            'group'     => '',
            'requires'  => '',
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        // Create a new Asset
        $asset = new LessStyleAsset($params, $this->config);

        if ($asset->valid === true)
        {
            $name = $asset->get_name();
            if (isset($this->less_style_asset_collection[$name]))
                $this->less_style_asset_collection[$name]->add_groups($asset->get_groups());
            else
                $this->less_style_asset_collection->set($name, $asset);

            return $this->less_style_asset_collection[$name];
        }
    }

    /**
     * @param string $group_name
     * @return void
     */
    protected function init_group($group_name)
    {
        if (isset($this->groups[$group_name]))
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
     * @param array $requires_groups array of groups to include with this group
     * @return void
     */
    public function add_asset_group(
        $group_name,
        array $scripts = array(),
        array $styles = array(),
        array $less = array(),
        array $requires_groups = array())
    {
        // Determine if this is a new group or Adding to one that already exists;
        $this->init_group($group_name);

        // If this group requires another group...
        if (count($requires_groups) > 0)
        {
            $merged = array_merge($this->groups[$group_name]['groups'], $requires_groups);
            $unique = array_unique($merged);
            $this->groups[$group_name]['groups'] = $unique;
        }

        // Parse our script files
        foreach($scripts as $name=>$asset)
        {
            $asset = $this->add_script_file($asset, $name);

            if ($asset !== null)
                $asset->add_groups($group_name);
        }

        // Parse our style files
        foreach($styles as $name=>$asset)
        {
            $asset = $this->add_style_file($asset, $name);
            if ($asset !== null)
                $asset->add_groups($group_name);
        }

        // Parse less style files
        foreach($less as $name=>$asset)
        {
            $asset = $this->add_less_style_file($asset, $name);
            if ($asset !== null)
                $asset->add_groups($group_name);
        }
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

    /**
     * @param array $asset_groups
     * @param string $asset_type
     * @param string $asset_name
     */
    protected function add_groups_for_asset(array $asset_groups, $asset_type, $asset_name)
    {
        foreach($asset_groups as $asset_group)
        {
            if (!isset($this->groups[$asset_group]))
                $this->init_group($asset_group);

            if (!in_array($asset_name, $this->groups[$asset_group][$asset_type]))
                $this->groups[$asset_group][$asset_type][] = $asset_name;
        }
    }

    /**
     * @param array $asset_groups
     * @param string $asset_type
     * @param string $asset_name
     */
    protected function remove_asset_from_groups(array $asset_groups, $asset_type, $asset_name)
    {
        foreach($asset_groups as $asset_group)
        {
            if (isset($this->groups[$asset_group]))
            {
                $idx = array_search($asset_name, $this->groups[$asset_group][$asset_type]);
                if ($idx !== false)
                    unset($this->groups[$asset_group][$asset_type][$idx]);
            }
        }
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     *
     * @param SplSubject $subject the SplSubject notifying the observer of an update.
     * @return void
     */
    public function update(SplSubject $subject)
    {
        if (func_num_args() === 3)
        {
            switch(true)
            {
                case ($subject instanceof LessStyleAssetCollection) :
                    $asset_type = 'less_styles';
                    break;

                case ($subject instanceof  ScriptAssetCollection) :
                    $asset_type = 'scripts';
                    break;

                case ($subject instanceof StyleAssetCollection) :
                    $asset_type = 'styles';
                    break;

                default : return;
            }

            $type = func_get_arg(1);
            $resource = func_get_arg(2);

            switch($type)
            {
                case self::ASSET_ADDED :
                    if (!isset($subject[$resource]))
                        return;

                    $this->add_groups_for_asset(
                        $subject[$resource]->get_groups(),
                        $asset_type,
                        $subject[$resource]->get_name()
                    );

                    break;

                case self::ASSET_REMOVED :
                    if (!($resource instanceof \DCarbone\AssetManager\Asset\IAsset))
                        return;

                    $this->remove_asset_from_groups(
                        $resource->get_groups(),
                        $asset_type,
                        $resource->get_name()
                    );

                    break;


                // For now, groups can only be ADDED to assets.
                case self::ASSET_MODIFIED :
                    if (!($resource instanceof \DCarbone\AssetManager\Asset\IAsset))
                        return;

                    $this->add_groups_for_asset(
                        $resource->get_groups(),
                        $asset_type,
                        $resource->get_name()
                    );

                    break;
            }
        }
    }
}