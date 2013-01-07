<?php

// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

/**
 * Asset Packager
 * 
 * @version 2.0
 * @author Daniel Carbone (daniel.p.carbone@gmail.com)
 * 
 * This set of classes is based loosely on the Carabiner library for Codeigniter (@link http://codeigniter.com/forums/viewthread/117966/)
 * 
 */

// Copyright (c) 2012-2013 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

class Assetpackager
{
    public $base_url = "";
    public $base_path = "";
    
    public $asset_dir = "";
    
    public $script_dir  = "";
    public $script_path = "";
    public $script_url  = "";
    
    public $script_view_dir = "";
    public $script_view_path = "";
    public $script_view_url = "";
    
    public $style_dir  = "";
    public $style_path = "";
    public $style_url  = "";
    
    public $cache_dir  = "";
    public $cache_path = "";
    public $cache_url  = "";
    
    public $dev        = FALSE;
    public $combine    = TRUE;
    
    public $minify_scripts = TRUE;
    public $minify_styles = TRUE;
    public $force_curl = FALSE;
    
    // This should only be used when Asset Packager is used as a stand-alone class
    public $error_callback = NULL;
    
    // These three vars contain a list of all currently installed assets
    private $_scripts    = array();
    private $_styles     = array();
    private $_groups     = array();
    private $_script_views = array();
    
    // This array is populated by the various "load" methods
    private $_loaded     = array();
    
    // These are used to define if output has occurred.
    public $styles_output   = false;
    public $scripts_output  = false;
    
    // Specifically for use with CodeIgniter
    private static $_CI = NULL;
    
    private $_config = NULL;
    
    /**
     * @Constructor
     */
    public function __construct(Array $args = array())
    {
        // Find out exactly where this file is
        $realpath = realpath(dirname(__FILE__));
        
        require $realpath."/AssetPackager/JSMin.php";
        require $realpath."/AssetPackager/CssMin.php";
        
        // Determine if we are being loaded by Codeigniter or standalone
        if (function_exists("get_instance"))
        {
            if (is_null(self::$_CI)) self::$_CI =& get_instance();
            log_message('debug', 'Asset Packager: Library initialized.');
            
            if( self::$_CI->config->load('assetpackager', TRUE, TRUE) ){
            
                log_message('debug', 'Asset Packager: config loaded from config file.');
                
                $config_file = self::$_CI->config->item('assetpackager');
                
                $this->_parseConfig($config_file);
            }
            else
            {
                log_message("error", "Asset Packager config file unable to load.");
            }
        }
        else
        {
            $this->_parseConfig($args);
        }
        
        // Load up the default group
        $this->loadGroups("default");
    }
    
    /**
     * Parse Config File
     * 
     * @name _parseConfig
     * @access Private
     * @param Array  configuration array
     * @return Void
     */
    private function _parseConfig(Array $config = array())
    {
        // Set some defaults in case they don't pass anything. 
        $defaults = array(
            "base_url"      => "",
            "base_path"     => "",
            
            "asset_dir"     => "",
            "script_dir"    => "scripts",
            "script_view_dir" => "scripts/views",
            "style_dir"     => "styles",
            "cache_dir"     => "cache",
            
            "dev"           => FALSE,
            "combine"       => TRUE,
            
            "minify_scripts"    => TRUE,
            "minify_styles"     => TRUE,
            "force_curl"        => FALSE,
            
            "error_callback"    => NULL  
        );
        
        // If they tried to define an invalid error_callback method, DIE!
        if (!is_null(self::$_CI) && isset($args['error_callback']) && !is_null($args['error_callback']) && !is_callable($args['error_callback'])) {
            die("Specified error_callback variable is not a callable function.  If you do not want error handling, either leave \"error_callback\" out of your construct arguments or pass it in as \"NULL\"");
        }
        
        // Loop through the configuration file to get our settings, skipping the Groups for now.
        foreach ($defaults as $k=>$v)
        {
            if (isset($config[$k]) && $config[$k] !== "" && !is_null($config[$k]))
            {
                $this->$k = $config[$k];
            }
            else
            {
                $this->$k = $v;
            }
        }
        
        // set the default value for base_url from the config
        if($this->base_url === "" && !is_null(self::$_CI))
        {
            $this->base_url = self::$_CI->config->item('base_url');
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
                $this->addAssetGroup($group_name, $scripts, $styles, $groups, $views);
            }
        }
        
        if (!is_null(self::$_CI))
            log_message('debug', 'Asset Packager: library configured.');
    }
    
    /**
     * Get Array of Configuration Variables
     * 
     * This method will be called when creating a new Asset object
     * to allow that object access to these values.
     * 
     * @name _getConfig
     * @access Private
     */
    private function _getConfig()
    {
        if (is_null($this->_config))
        {
            $this->_config = array(
                "base_url"      => $this->base_url,
                "base_path"     => $this->base_path,
                
                "asset_path"    => $this->asset_path,
                "asset_url"     => $this->_asset_url,

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
                
                "CI"            => !is_null(self::$_CI)
            );
        }
        return $this->_config;
    }
    
    /**
     * Add Script View File
     * 
     * @name addScriptViewFile
     * @access Public
     * @param String $filename
     * @return VOID
     */
    public function addScriptViewFile($filename)
    {
        $asset = new \AssetPackager\Asset\View($this->_getConfig(), $filename);
        
        if ($asset->valid === true)
        {
            $name = $asset->getName();
            
            if (!array_key_exists($name, $this->_script_views))
            {
                $this->_script_views[$name] = $asset;
            }
        }
    }
    
    /**
     * Add Normal Script File
     * 
     * @name addScriptFile
     * @access Public
     * @param Array $params
     * @return void
     */
    public function addScriptFile(Array $params)
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
        
        $asset = new \AssetPackager\Asset\Script($this->_getConfig(), $params);
        
        if ($asset->valid === true)
        {
            $name = $asset->getName();
            $groups = $asset->getGroups();
            
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
                $this->_scripts[$name]->addGroups($groups);
            }
        }
    }
    
    /**
     * Add Style File
     * 
     * @name addStyleFile
     * @access Public
     * @param Array $params
     * @return void
     */
    public function addStyleFile(Array $params)
    {
        // Ensure that we don't have repeat values
        $params = array_unique($params);
        
        $defaults = array(
            "dev_file"  => "",
            "prod_file" => "",
            "media"     => "screen",
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
        
        $asset = new \AssetPackager\Asset\Style($this->_getConfig(), $params);
        
        if ($asset->valid === true)
        {
            $name = $asset->getName();
            $groups = $asset->getGroups();
            
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
                $this->_styles[$name]->addGroups($groups);
            }
        }
    }
    
    /**
     * Add Asset Group
     * 
     * @name addAssetGroup
     * @access Public
     * @param String  name of group
     * @param Array  array of script files
     * @param Array  array of style files
     * @param Array  array of groups to include with this group
     * @param Array  script views to require with this group
     * @return VOID
     */
    public function addAssetGroup($group_name = "", Array $scripts = array(), Array $styles = array(), Array $include_groups = array(), Array $views = array())
    {
        // Determine if this is a new group or adding to one that already exists;
        if (!array_key_exists($group_name, $this->_groups))
        {
            $this->_groups[$group_name] = array("scripts" => array(), "styles" => array(), "groups" => array(), "views" => array());
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
                $this->addScriptViewFile($view);
            }
            $this->_groups[$group_name]['views'] = $views;
        }
        
        foreach(array("scripts", "styles") as $type)
        {
            foreach(${$type} as $asset)
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
                
                switch($type)
                {
                    case 'scripts' :
                        $this->addScriptFile($asset);
                    break;
                    case "styles" :
                        $this->addStyleFile($asset);
                    break;
                }
            }
        }
    }
    
    /**
     * Load Scripts for output
     * 
     * @name loadScripts
     * @access Public
     * @param Mixed  string or array of script files to load
     * @return Mixed
     */
    public function loadScripts($names)
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
                $this->_loaded['scripts'][$name] = $this->_scripts[$name]->getRequires();
            }
        }
    }
    
    /**
     * Load Script Views for Output
     * 
     * @name loadScriptViews
     * @access Public
     * @param Mixed  string or array of script view files to load
     * @return Mixed
     */
    public function loadScriptViews($names)
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
                $this->_loaded['scripts'][$name] = $this->_script_views[$name]->getRequires();
            }
        }
    }
    
    /**
     * Load Style Files for output
     * 
     * @name loadStyles
     * @access Public
     * @param Mixed  string or array of script views to load
     * @return Mixed
     */
    public function loadStyles($names)
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
                
                $this->_loaded['styles'][$name] = $this->_styles[$name]->getRequires();
            }
        }
    }
    
    /**
     * Load Asset Group for Output
     * 
     * @name loadGroups
     * @access Public
     * @param Mixed  string or array of groups to load for output
     * @return Mixed
     */
    public function loadGroups($groups)
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
                    $this->loadGroups($rgroup);
                }
                $this->loadStyles($this->_groups[$group]['styles']);
                $this->loadScriptViews($this->_groups[$group]['views']);
                $this->loadScripts($this->_groups[$group]['scripts']);
                $this->_loaded['groups'][$group] = $this->_groups[$group];
            }
        }
    }
    
    /**
     * Generate Output for page
     * 
     * @name generateOutput
     * @access public
     * @return String  output HTML for Styles and Scripts
     */
    public function generateOutput()
    {
        $required = array();
        $loaded_styles = array();
        $loaded_scripts = array();

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
                ${"loaded_".$type} = array_unique(array_merge($reqs, array_keys($this->_loaded[$type])));
            }
            else
            {
                ${"loaded_".$type} = array_keys($this->_loaded[$type]);
            }
        }
        
        if ($this->dev === false && $this->combine === true)
        {
            $_S = (($this->styles_output === false) ? $loaded_styles : array());
            $_S2 = (($this->scripts_output === false) ? $loaded_scripts : array());
            $this->_generateComplexOutput($_S, $_S2);
        }
        else if ($this->dev === true || $this->combine === false)
        {
            $this->_outputStyles($loaded_styles);
            $this->styles_output = true;
            
            $this->_outputScripts($loaded_scripts);
            $this->scripts_output = true;
        }

    }
    
    /**
     * Generate Complex Output
     * 
     * @name _generateComplexOutput
     * @access Private
     * @param Array  array of styles to output
     * @param Array  array of scripts to output
     */
    private function _generateComplexOutput(Array $styles, Array $scripts)
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
        
        $complex = new \AssetPackager\Complex($_styles, $_scripts, $this->_getConfig());
        
        if ($this->styles_output === false)
        {
            $style_output = $complex->outputStyles();
            if ($style_output === false)
            {
                $this->_outputStyles(array_keys($styles));
            }
            $this->styles_output = true;
        }
        
        if ($this->scripts_output === false)
        {
            $script_output = $complex->outputScripts();
            if ($script_output === false)
            {
                $this->_outputScripts(array_keys($scripts));
            }
            $this->script_output = true;
        }
    }
    
    /**
     * Generate Style tag Output
     * 
     * @name _outputStyles
     * @access Private
     * @param Array  array of styles to output
     * @return String  html style elements
     */
    private function _outputStyles(Array $styles)
    {
        ob_start();
        foreach($styles as $style)
        {
            if (array_key_exists($style, $this->_styles) && ($this->_styles[$style] instanceof \AssetPackager\Asset\Style) === true)
            {
                echo $this->_styles[$style]->getOutput();
                echo "\n";
            }
        }
        echo ob_get_clean();
    }
    
    /**
     * Generate Script tag Output
     * 
     * @name _outputScripts
     * @access Private
     * @param Array  array of scripts ot output
     * @return String  html script elements
     */
    private function _outputScripts(Array $scripts)
    {
        ob_start();
        foreach($scripts as $script)
        {
            if (array_key_exists($script, $this->_scripts) && ($this->_scripts[$script] instanceof \AssetPackager\Asset\Script) === true)
            {
                echo $this->_scripts[$script]->getOutput();
                echo "\n";
            }
            else if (array_key_exists($script, $this->_script_views) && ($this->_script_views[$script] instanceof \AssetPackager\Asset\View) === true)
            {
                echo $this->_script_views[$script]->getOutput();
                echo "\n";
            }
        }
        echo ob_get_clean();
    }
    
}
