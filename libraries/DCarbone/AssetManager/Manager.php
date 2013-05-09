<?php namespace DCarbone\AssetManager;

use \DCarbone\AssetManager\Generics\Asset;
use \DCarbone\AssetManager\Generics\Complex;

use \CssMin;
use \JSMin;

use \DCarbone\AssetManager\Specials\Assets\Script;
use \DCarbone\AssetManager\Specials\Assets\Style;
use \DCarbone\AssetManager\Specials\Assets\ScriptView;

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

class Manager
{
    /* ------------------------------------------
     *  CONSTANTS
     ----------------------------------------- */
    /**
     * What to pretend to the file name output
     * @var string
     */
    public static $filePrependValue = "";

    /**
     * Extension for script files
     */
    public static $scriptFileExtension = "js";

    /**
     * Extension for style files
     */
    public static $styleFileExtension = "css";

    /**
     * Full Url to application root
     * @var string
     */
    public $base_url = "";

    /**
     * Base Full Filepath to application root
     * @var string
     */
    public $base_path = "";

    /**
     * Asset directory name
     * @var string
     */
    public $asset_dir = "";

    /**
     * Script Directory name
     * @var string
     */
    public $script_dir  = "";
    /**
     * Full path to script directory
     * @var string
     */
    public $script_path = "";
    /**
     * Full URL to script directory
     * @var string
     */
    public $script_url  = "";

    /**
     * Script View directory name
     * @var string
     */
    public $script_view_dir = "";
    /**
     * Full path to script view directory
     * @var string
     */
    public $script_view_path = "";
    /**
     * Full URL to script view directory
     * @var string
     */
    public $script_view_url = "";

    /**
     * Style directory name
     * @var string
     */
    public $style_dir  = "";
    /**
     * Full path to style directory
     * @var string
     */
    public $style_path = "";
    /**
     * Full URL to style directory
     * @var string
     */
    public $style_url  = "";

    /**
     * Cache directory name
     * @var string
     */
    public $cache_dir  = "";
    /**
     * Full path to cache directory
     * @var string
     */
    public $cache_path = "";
    /**
     * Full URL to cache directory
     * @var string
     */
    public $cache_url  = "";

    /**
     * Development environment
     * @var boolean
     */
    public $dev        = FALSE;

    /**
     * Combine like files
     * @var boolean
     */
    public $combine    = TRUE;

    /**
     * Minify Script files
     * @var boolean
     */
    public $minify_scripts = TRUE;
    /**
     * Minify Style Files
     * @var boolean
     */
    public $minify_styles = TRUE;

    /**
     * Force CURL to be used over file_get_contents
     * @var boolean
     */
    public $force_curl = FALSE;

    /**
     * Callable error function
     * @var [type]
     */
    public $error_callback = NULL;

    /**
     * Scripts
     * @var array
     */
    protected $_scripts    = array();
    /**
     * Script Views
     * @var array
     */
    protected $_script_views = array();
    /**
     * Styles
     * @var array
     */
    protected $_styles     = array();
    /**
     * Groups
     * @var array
     */
    protected $_groups     = array();

    /**
     * Loaded Assets
     * @var array
     */
    protected $_loaded     = array();

    /**
     * Style Output
     * @var boolean
     */
    public $stylesOutput   = false;
    /**
     * Script Output
     * @var boolean
     */
    public $scriptsOutput  = false;

    /**
     * Array of bracketed values to
     * replace when parsing JS files
     * @var array
     */
    public static $scriptBrackets = array();

    /**
     * Array of bracketed values to
     * replace when parsing CSS files
     * @var array
     */
    public static $styleBrackets = array();

    /**
     * CodeIgniter Instance
     * @var Object
     */
    protected static $CI = NULL;

    /**
     * AssetManager Configuration
     * @var Array
     */
    protected $_config = NULL;

    /**
     * @Constructor
     */
    public function __construct()
    {
        // Find out exactly where this file is
        $realpath = realpath(dirname(__FILE__));

        require $realpath."/Generics/CssMin.php";
        require $realpath."/Generics/JSMin.php";

        if (static::$CI === null)
        {
            static::$CI =& get_instance();
        }

        log_message('info', 'Asset Packager: Library initialized.');

        if( static::$CI->config->Load('assetpackager', TRUE, TRUE) ){

            log_message('info', 'Asset Packager: config Loaded from config file.');

            $config_file = static::$CI->config->item('assetpackager');

            $this->_ParseConfig($config_file);
        }
        else
        {
            log_message("error", "Asset Packager config file unable to Load.");
        }

        // Load up the default group
        $this->LoadGroups("default");
    }

    /**
     * Parse Config File
     *
     * @name _ParseConfig
     * @access protected
     * @param $config Configuration array defined in /config/assetpackager.php
     */
    protected function _ParseConfig(Array $config = array())
    {
        // Set some defaults in case they don't pass anything.
        $defaults = array(
            "base_url"          => "",
            "base_path"         => "",

            "asset_dir"         => "",
            "script_dir"        => "scripts",
            "script_view_dir"   => "scripts/views",
            "style_dir"         => "styles",
            "cache_dir"         => "cache",

            "dev"               => FALSE,
            "combine"           => TRUE,

            "minify_scripts"    => TRUE,
            "minify_styles"     => TRUE,
            "force_curl"        => FALSE
        );

        // Loop through the configuration file to get our settings, skipping the Groups for now.
        foreach ($defaults as $k=>$v)
        {
            if (isset($config[$k]) && $config[$k] !== "" && $config[$k] !== null)
            {
                $this->$k = $config[$k];
            }
            else
            {
                $this->$k = $v;
            }
        }

        // set the default value for base_url from the config
        if($this->base_url === "" && static::$CI !== null)
        {
            $this->base_url = static::$CI->config->item('base_url');
        }
        else if ($this->base_url === "")
        {
            $this->base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") ? "https://" : "http://") . $_SERVER['SERVER_NAME'];
        }

        if ($this->base_path === "" && defined("FCPATH"))
        {
            $this->base_path = FCPATH;
        }
        else if ($this->base_path === "")
        {
            $this->base_path = realpath(dirname(dirname(__FILE__)));
        }

        // Define the base asset url and path
        $this->asset_url    = str_ireplace(array("http://", "https://"), "//", $this->base_url) . (($this->asset_dir !== "") ? $this->asset_dir . "/" : "");
        $this->asset_path   = $this->base_path . (($this->asset_dir !== "") ? $this->asset_dir . "/" : "");

        // Define the script url and path
        $this->script_url   = $this->asset_url . $this->script_dir . "/";
        $this->script_path  = $this->asset_path . $this->script_dir . "/";

        // Define the script view url and path
        $this->script_view_url  = $this->asset_url . $this->script_view_dir . "/";
        $this->script_view_path = $this->asset_path . $this->script_view_dir . "/";

        // Define the style url and path
        $this->style_url    = $this->asset_url . $this->style_dir . "/";
        $this->style_path   = $this->asset_path . $this->style_dir . "/";

        // Define the cache url and path
        $this->cache_url    = $this->asset_url . $this->cache_dir . "/";
        $this->cache_path   = $this->asset_path . $this->cache_dir . "/";

        // Now that we have our settings set, get any defined groups!
        if (isset($config['groups']) && is_array($config['groups']))
        {
            foreach($config['groups'] as $group_name => $assets)
            {
                $scripts = ((isset($assets['scripts'])) ? $assets['scripts'] : array());
                $styles = ((isset($assets['styles'])) ? $assets['styles'] : array());
                $groups = ((isset($assets['groups'])) ? $assets['groups'] : array());
                $views = ((isset($assets['views'])) ? $assets['views'] : array());
                $this->AddAssetGroup($group_name, $scripts, $styles, $groups, $views);
            }
        }

        log_message('debug', 'Asset Packager: library configured.');
    }

    /**
     * Get Array of Configuration Variables
     *
     * This method will be called when creating a new Asset object
     * to allow that object access to these values.
     *
     * @name _GetConfig
     * @access protected
     */
    protected function _GetConfig()
    {
        if ($this->_config === null)
        {
            $this->_config = array(
                "base_url"      => $this->base_url,
                "base_path"     => $this->base_path,

                "asset_path"    => $this->asset_path,
                "asset_url"     => $this->asset_url,

                "script_dir"    => $this->script_dir,
                "script_url"    => $this->script_url,
                "script_path"   => $this->script_path,

                "script_view_dir"   => $this->script_view_dir,
                "script_view_url"   => $this->script_view_url,
                "script_view_path"  => $this->script_view_path,

                "style_dir"     => $this->style_dir,
                "style_url"     => $this->style_url,
                "style_path"    => $this->style_path,

                "cache_dir"     => $this->cache_dir,
                "cache_url"     => $this->cache_url,
                "cache_path"    => $this->cache_path,

                "dev"           => $this->dev,
                "combine"       => $this->combine,

                "minify_scripts"    => $this->minify_scripts,
                "minify_styles"     => $this->minify_styles,

                "force_curl"    => $this->force_curl,

                "error_callback" => $this->error_callback,

                "CI"            => !(static::$CI === null)
            );
        }
        return $this->_config;
    }

    /**
     * Reset all groups
     *
     * @param   boolean $keepDefault keep the default group loaded
     * @return  void
     */
    public function Reset($keepDefault = true)
    {
        $this->stylesOutput = false;
        $this->scriptsOutput = false;
        $this->_loaded = array();

        if ($keepDefault === true)
        {
            $this->LoadGroups("default");
        }
    }

    /**
     * Add Script View File
     *
     * @name AddScriptViewFile
     * @access public
     * @param Array $params
     * @return VOID
     */
    public function AddScriptViewFile(Array $params)
    {
        $defaults = array(
            "dev_file" => "",
            "prod_file" => "",
            "minify" => TRUE,
            "cache" => TRUE,
            "name" => "",
            "group" => array("default"),
            "requires" => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
            {
                $params[$k] = $v;
            }
        }

        $params['minify'] = ($params['minify'] && $this->minify_scripts);

        $asset = new ScriptView($this->_GetConfig(), $params);

        if ($asset->valid === true)
        {
            $name = $asset->GetName();

            if (!array_key_exists($name, $this->_script_views))
            {
                $this->_script_views[$name] = $asset;
            }
        }
    }

    /**
     * Add Normal Script File
     *
     * @name AddScriptFile
     * @access public
     * @param Array $params
     * @return void
     */
    public function AddScriptFile(Array $params)
    {
        $defaults = array(
            "dev_file" => "",
            "prod_file" => "",
            "minify" => TRUE,
            "cache" => TRUE,
            "name" => "",
            "group" => array("default"),
            "requires" => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
            {
                $params[$k] = $v;
            }
        }

        $params['minify'] = ($params['minify'] && $this->minify_scripts);

        $asset = new Script($this->_GetConfig(), $params);

        if ($asset->valid === true)
        {
            $name = $asset->GetName();
            $groups = $asset->GetGroups();

            foreach($groups as $group)
            {
                if (!array_key_exists($group, $this->_groups))
                {
                    $this->_groups[$group] = array("styles" => array(), "scripts" => array());
                }

                if (!in_array($name, $this->_groups[$group]['scripts']))
                {
                    $this->_groups[$group]["scripts"][] = $name;
                }

            }
            if (!array_key_exists($name, $this->_scripts))
            {
                $this->_scripts[$name] = $asset;
            }
            else
            {
                $this->_scripts[$name]->AddGroups($groups);
            }
        }
    }

    /**
     * Add Style File
     *
     * @name AddStyleFile
     * @access public
     * @param Array $params
     * @return void
     */
    public function AddStyleFile(Array $params)
    {
        $defaults = array(
            "dev_file"  => "",
            "prod_file" => "",
            "media"     => "all",
            "minify"    => TRUE,
            "cache"     => TRUE,
            "name"      => "",
            "group"     => array("default"),
            "requires"  => array()
        );

        // Sanitize our parameters
        foreach($defaults as $k=>$v)
        {
            if (!isset($params[$k]))
            {
                $params[$k] = $v;
            }
        }

        $params['minify'] = ($params['minify'] && $this->minify_styles);

        // Do a quick sanity check on $group
        if (is_string($params['group']) && $params['group'] !== "")
        {
            $params['group'] = array($params['group']);
        }

        $asset = new Style($this->_GetConfig(), $params);

        if ($asset->valid === true)
        {
            $name = $asset->GetName();
            $groups = $asset->GetGroups();

            foreach($groups as $group)
            {
                if (!array_key_exists($group, $this->_groups))
                {
                    $this->_groups[$group] = array("styles" => array(), "scripts" => array());
                }

                if (!in_array($name, $this->_groups[$group]['styles']))
                {
                    $this->_groups[$group]["styles"][] = $name;
                }
            }
            if (!array_key_exists($name, $this->_styles))
            {
                $this->_styles[$name] = $asset;
            }
            else
            {
                $this->_styles[$name]->AddGroups($groups);
            }
        }
    }

    /**
     * Add Asset Group
     *
     * @name AddAssetGroup
     * @access public
     * @param String  name of group
     * @param Array  array of script files
     * @param Array  array of style files
     * @param Array  array of groups to include with this group
     * @param Array  script views to require with this group
     * @return VOID
     */
    public function AddAssetGroup(
        $group_name = "",
        Array $scripts = array(),
        Array $styles = array(),
        Array $include_groups = array(),
        Array $views = array())
    {
        // Determine if this is a new group or Adding to one that already exists;
        if (!array_key_exists($group_name, $this->_groups))
        {
            $this->_groups[$group_name] = array(
                "scripts" => array(),
                "styles" => array(),
                "groups" => array(),
                "views" => array()
            );
        }

        // If this group requires another group...
        if (count($include_groups) > 0)
        {
            $merged = array_merge($this->_groups[$group_name]['groups'], $include_groups);
            $unique = array_unique($merged);
            $this->_groups[$group_name]['groups'] = $unique;
        }

        // If this group requires any script views
        if (count($views) > 0)
        {
            foreach($views as $view)
            {
                $this->AddScriptViewFile(array("group" => $group_name, "dev_file" => $view));
            }
            $this->_groups[$group_name]['views'] = $views;
        }

        // Parse our script files
        foreach($scripts as $asset)
        {
            $parsed = $this->ParseAsset($asset, $group_name);
            $this->AddScriptFile($parsed);
        }
        // Do this so we are sure to have a clean $asset variable
        unset($asset);

        // Parse our style files
        foreach($styles as $asset)
        {
            $parsed = $this->ParseAsset($asset, $group_name);
            $this->AddStyleFile($parsed);
        }
    }

    /**
     * Parse an asset array
     *
     * @param   Array   $asset       pre-parsing asset array
     * @param   String  $group_name  Name of group defined with asset
     * @return  Array                Parsed asset array
     */
    protected function ParseAsset(Array $asset, $group_name)
    {
        // If they pass in multiple groups for a specific item within this group.
        $groups = array();
        if (isset($asset['group']))
        {
            if (is_string($asset['group']) && $asset['group'] !== "")
            {
                $groups[] = $asset['group'];
            }
            else if (is_array($asset['group']) && count($asset['group']) > 0)
            {
                foreach($asset['group'] as $g)
                {
                    if (is_string($g) && ($g = trim($g)) !== "")
                    {
                        $groups[] = $g;
                    }
                }
            }
        }
        else
        {
            $asset['group'] = array();
        }

        // Group name handling
        if (is_string($group_name) && $group_name !== "")
        {
            $overgroup = array($group_name);
            $groups = array_merge($groups, $overgroup);
        }
        else if (is_array($group_name) && count($group_name) > 0)
        {
            foreach($group_name as $gn)
            {
                if (is_string($gn) && ($gn = trim($gn)) !== "")
                {
                    $groups[] = $gn;
                }
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
     * @name LoadScripts
     * @access public
     * @param Mixed  string or array of script files to Load
     * @return Mixed
     */
    public function LoadScripts($names)
    {
        if (!isset($this->_loaded['scripts']))
        {
            $this->_loaded['scripts'] = array();
        }

        if ((is_string($names) && $names === "") || (is_array($names) && count($names) < 1))
        {
            return false;
        }
        if (is_string($names))
        {
            $names = array($names);
        }
        foreach($names as $name)
        {
            if (array_key_exists($name, $this->_scripts) && !array_key_exists($name, $this->_loaded['scripts']))
            {
                $this->_loaded['scripts'][$name] = $this->_scripts[$name]->GetRequires();
            }
        }
    }

    /**
     * Load Script Views for Output
     *
     * @name LoadScriptViews
     * @access public
     * @param Mixed  string or array of script view files to Load
     * @return Mixed
     */
    public function LoadScriptViews($names)
    {
        if (!isset($this->_loaded['scripts']))
        {
            $this->_loaded['scripts'] = array();
        }

        if ((is_string($names) && $names === "") || (is_array($names) && count($names) < 1))
        {
            return false;
        }

        if (is_string($names))
        {
            $names = array($names);
        }

        foreach($names as $name)
        {
            if (array_key_exists($name, $this->_script_views) && !array_key_exists($name, $this->_loaded['scripts']))
            {
                $this->_loaded['scripts'][$name] = $this->_script_views[$name]->GetRequires();
            }
        }
    }

    /**
     * Load Style Files for Output
     *
     * @name LoadStyles
     * @access public
     * @param Mixed  string or array of script views to Load
     * @return Mixed
     */
    public function LoadStyles($names)
    {
        if (!isset($this->_loaded['styles']))
            $this->_loaded['styles'] = array();

        if ((is_string($names) && $names == "") || (is_array($names) && count($names) < 1))
        {
            return false;
        }
        if (is_string($names))
        {
            $names = array($names);
        }
        foreach($names as $name)
        {
            if (array_key_exists($name, $this->_styles) && !array_key_exists($name, $this->_loaded['styles']))
            {

                $this->_loaded['styles'][$name] = $this->_styles[$name]->GetRequires();
            }
        }
    }

    /**
     * Load Asset Group for Output
     *
     * @name LoadGroups
     * @access public
     * @param Mixed  string or array of groups to Load for Output
     * @return Mixed
     */
    public function LoadGroups($groups)
    {
        if (!isset($this->_loaded['groups']))
        {
            $this->_loaded['groups'] = array();
        }

        if ((is_string($groups) && $groups == "") || (is_array($groups) && count($groups) < 1))
        {
            return false;
        }
        if (is_string($groups))
        {
            $groups = array($groups);
        }

        foreach($groups as $group)
        {
            if (array_key_exists($group, $this->_groups) && !array_key_exists($group, $this->_loaded['groups']))
            {
                foreach($this->_groups[$group]['groups'] as $rgroup)
                {
                    $this->LoadGroups($rgroup);
                }
                $this->LoadStyles($this->_groups[$group]['styles']);
                $this->LoadScriptViews($this->_groups[$group]['views']);
                $this->LoadScripts($this->_groups[$group]['scripts']);
                $this->_loaded['groups'][$group] = $this->_groups[$group];
            }
        }
    }

    /**
     * Generate Output for page
     *
     * @name GenerateOutput
     * @access public
     * @return String  Output HTML for Styles and Scripts
     */
    public function GenerateOutput()
    {
        $required = array();
        $Loaded_styles = array();
        $Loaded_scripts = array();

        foreach(array("styles", "scripts") as $type)
        {
            foreach($this->_loaded[$type] as $name=>$requires)
            {
                foreach($requires as $require)
                {
                    if (array_key_exists($require, $required))
                    {
                        $required[$require]++;
                    }
                    else
                    {
                        $required[$require] = 1;
                    }
                }
            }
            if (count($required) > 0)
            {
                asort($required, SORT_NUMERIC);
                $required = array_reverse($required);
                $reqs = array_keys($required);
                ${"Loaded_".$type} = array_unique(array_merge($reqs, array_keys($this->_loaded[$type])));
            }
            else
            {
                ${"Loaded_".$type} = array_keys($this->_loaded[$type]);
            }
        }

        if ($this->dev === false && $this->combine === true)
        {
            $_S = (($this->stylesOutput === false) ? $Loaded_styles : array());
            $_S2 = (($this->scriptsOutput === false) ? $Loaded_scripts : array());
            $this->_GenerateComplexOutput($_S, $_S2);
        }
        else if ($this->dev === true || $this->combine === false)
        {
            $this->OutputStyles($Loaded_styles);
            $this->stylesOutput = true;

            $this->OutputScripts($Loaded_scripts);
            $this->scriptsOutput = true;
        }

    }

    /**
     * Generate Complex Output
     *
     * @name _GenerateComplexOutput
     * @access protected
     * @param Array  array of styles to Output
     * @param Array  array of scripts to Output
     */
    protected function _GenerateComplexOutput(Array $styles, Array $scripts)
    {
         /*
         * Build arrays:
         * array(
         *   "asset_name" => Asset_object
         * );
         */
        $_styles = array();
        $_scripts = array();
        // Style files (Cascading Style Sheets)
        foreach($styles as $style)
        {
            if (array_key_exists($style, $this->_styles))
            {
                $_styles[$style] = $this->_styles[$style];
            }
        }
        // Script files (Javascript)
        foreach($scripts as $script)
        {
            if(array_key_exists($script, $this->_scripts))
            {
                $_scripts[$script] = $this->_scripts[$script];
            }
            else if (array_key_exists($script, $this->_script_views))
            {
                $_scripts[$script] = $this->_script_views[$script];
            }
        }

        $complex = new Complex($_styles, $_scripts, $this->_GetConfig());

        if ($this->stylesOutput === false)
        {
            $styleOutput = $complex->OutputStyles();
            if ($styleOutput === false)
            {
                $this->OutputStyles(array_keys($styles));
            }
            $this->stylesOutput = true;
        }

        if ($this->scriptsOutput === false)
        {
            $scriptOutput = $complex->OutputScripts();
            if ($scriptOutput === false)
            {
                $this->OutputScripts(array_keys($scripts));
            }
            $this->scriptOutput = true;
        }
    }

    /**
     * Generate Style tag Output
     *
     * @name OutputStyles
     * @access protected
     * @param Array  array of styles to Output
     * @return String  html style elements
     */
    protected function OutputStyles(Array $styles)
    {
        $medias = array();

        foreach($styles as $style)
        {
            if (array_key_exists($style, $this->_styles) &&
                ($this->_styles[$style] instanceof Style) === true)
            {
                if (!isset($medias[$this->_styles[$style]->media]))
                {
                    $medias[$this->_styles[$style]->media] = array();
                }
                $medias[$this->_styles[$style]->media][] = $this->_styles[$style];
            }
        }

        ob_start();
        if (isset($medias['all']))
        {
            foreach($medias['all'] as $asset)
            {
                echo $asset->GetOutput();
                echo "\n";
            }
            unset($medias['all']);
            unset($asset);
        }
        if (isset($medias['screen']))
        {
            foreach($medias['screen'] as $asset)
            {
                echo $asset->GetOutput();
                echo "\n";
            }
            unset($medias['screen']);
            unset($asset);
        }
        if (isset($medias['print']))
        {
            foreach($medias['print'] as $asset)
            {
                echo $asset->GetOutput();
                echo "\n";
            }
            unset($medias['print']);
            unset($asset);
        }
        echo ob_Get_clean();
    }

    /**
     * Generate Script tag Output
     *
     * @name OutputScripts
     * @access protected
     * @param Array  array of scripts ot Output
     * @return String  html script elements
     */
    protected function OutputScripts(Array $scripts)
    {
        ob_start();
        foreach($scripts as $script)
        {
            if (array_key_exists($script, $this->_scripts) && ($this->_scripts[$script] instanceof Script) === true)
            {
                echo $this->_scripts[$script]->GetOutput();
                echo "\n";
            }
            else if (array_key_exists($script, $this->_script_views) && ($this->_script_views[$script] instanceof ScriptView) === true)
            {
                echo $this->_script_views[$script]->GetOutput();
                echo "\n";
            }
        }
        echo ob_Get_clean();
    }

}
