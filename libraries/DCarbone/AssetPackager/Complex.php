<?php namespace DCarbone\AssetPackager;

/**
 * Asset Packager Complex Asset class
 * 
 * @version 1.0
 * @author Daniel Carbone (daniel.p.carbone@gmail.com)
 * 
 */
class Complex
{
    private $_config = array();
    
    private $_styles = array();
    private $_scripts = array();
    
    private $_output = array();
    
    private $_cache_files = array();
    
    public function __construct(Array $styles, Array $scripts, Array $config)
    {
        $this->_config = $config;
        
        $this->_cache_files = $this->_getCacheFileArray();
        
        $this->_generateOutput($styles, $scripts);
    }
    
    /**
     * Get a list of the currently cached asset files
     * 
     * @name _getCacheFileArray
     * @access private
     * @return Array  array of cached assets
     */
    private function _getCacheFileArray()
    {
        $build_name_array = function(Array $arr)
        {
            $return = array();
            foreach($arr as $a)
            {
                $return[basename($a)] = array(
                    "path" => $a,
                    "datetime" => new \DateTime("@".(string)filemtime($a))
                );
            }
            return $return;
        };
        $style_files = $build_name_array(glob($this->_config['cache_path']."*.css"));
        $script_files = $build_name_array(glob($this->_config['cache_path']."*.js"));
        
        return array(
            'styles' => $style_files,
            "scripts" => $script_files,
            "all" => array_merge($style_files + $script_files)
        );
    }
    
    /**
     * Determine if file exists in cache
     * 
     * @name _cacheFileExists
     * @access private
     * @param String  file name
     * @param String  file type
     * @return Bool
     */
    private function _cacheFileExists($file = "", $type = "")
    {
        switch($type)
        {
            case "style" :
                if (array_key_exists($file, $this->_cache_files['styles']))
                {
                    return $this->_cache_files['styles'][$file];
                }
                return false;
            break;
            case "script" :
                if (array_key_exists($file, $this->_cache_files['scripts']))
                {
                    return $this->_cache_files['scripts'][$file];
                }
                return false;
            break;
            default :
                if (array_key_exists($file, $this->_cache_files['all']))
                {
                    return $this->_cache_files['all'][$file];
                }
                return false;
            break;
        }
    }
    
    /**
     * Get Cached File Information
     * 
     * XXX Finish this
     */
    private function _getCacheFileInfo($file = "")
    {
        
    }
    
    /**
     * Get newest modification date of files within cache container
     *
     * @name _getNewestModifiedDate
     * @access private
     * @param Array  array of files
     * @return \DateTime
     */
    private function _getNewestModifiedDate(Array $files)
    {
        $date = new \DateTime("0:00:00 January 1, 1970 UTC");
        foreach($files as $name=>$obj)
        {
            $d = $obj->getDateModified();
            
            if (!($d instanceof \DateTime))
            {
                continue;
            }
            else if ($d > $date)
            {
                $date = $d;
            }
        }
        return $date;
    }
    
    /**
     * Output Styles
     * 
     * Echoes out css <link> tags
     * 
     * @name outputStyles
     * @access public
     * @return Bool
     */
    public function outputStyles()
    {
        if ($this->_styles !== false && is_array($this->_styles))
        {
            foreach($this->_styles as $file=>$atts)
            {
                $surl = $this->_config['cache_url'].$file;
                echo "\n<link rel='stylesheet' type='text/css' media='{$atts['media']}' href='{$surl}?={$atts['datetime']->format("Ymd")}' />";
            }
            return true;
        }
        return false;
    }
    
    /**
     * Output Scripts
     * 
     * Echoes out js <script> tags
     * 
     * @name outputScripts
     * @access public
     * @return Bool
     */
    public function outputScripts()
    {
        if ($this->_scripts !== false && is_array($this->_scripts))
        {
            foreach($this->_scripts as $file=>$atts)
            {
                $surl = $this->_config['cache_url'].$file;
                echo "\n<script type='text/javascript' language='javascript' src='{$surl}?={$atts['datetime']->format("Ymd")}'></script>";
            }
        }
        return false;
    }

    /**
     * Generate Output helper method
     * 
     * This method calls one or more of the 2 specific combination output methods
     * 
     * @name _generateOutput
     * @access private
     * @param Array  array of styles
     * @param Array  array of scripts
     * @return void
     */
    private function _generateOutput(Array $styles, Array $scripts)
    {
        if (count($styles) > 0)
        {
            $this->_generateCombinedStyles($styles);
        }
        if (count($scripts) > 0)
        {
            $this->_generateCombinedScripts($scripts);
        }
    }

    /**
     * Generate Combined Stylesheet string
     * 
     * This method takes all of the desired styles, orders them, then combines into a single string for caching
     * 
     * @name _generateCombinedStyles
     * @access private
     * @param Array  array of styles
     * @return Void
     */
    private function _generateCombinedStyles(Array $styles)
    {
        $medias = array();
        $get_media = function (Asset\Style $style) use (&$medias)
        {
            if (isset($style->media) && !array_key_exists($style->media, $medias))
            {
                $medias[$style->media] = array($style);
            }
            else if (isset($style->media) && array_key_exists($style->media, $medias))
            {
                $medias[$style->media][$style->getName()] = $style;
            }
            else
            {
                if (isset($medias['screen']))
                {
                    $medias['screen'][$style->getName()] = $style;
                }
                else 
                {
                    $medias['screen'] = array($style->getName() => $style);
                }
            }
        };
        
        array_map($get_media, $styles);
        
        foreach($medias as $media=>$styles)
        {
        
            $newest_file = $this->_getNewestModifiedDate($styles);
            $style_names = array_keys($styles);
            
            $combined_style_name = md5(implode("", $style_names)).".css";
            $cachefile = $this->_cacheFileExists($combined_style_name, "style");
            $combined = true;
            if ($cachefile !== false)
            {
                if ($newest_file > $cachefile['datetime'])
                {
                    $combined = $this->_combineAssets($styles, $combined_style_name);
                }
            }
            else if ($cachefile === false)
            {
                $combined = $this->_combineAssets($styles, $combined_style_name);
            }
            
            // If there was an error combining the files
            if ($combined === false)
            {
                $this->_styles = false;
            }
            else
            {
                if ($this->_styles === false) continue;
                else if (is_array($this->_styles))
                {
                    $this->_styles[$combined_style_name] = array("media" => $media, "datetime" => $newest_file);
                }
                else
                {
                    $this->_styles = array($combined_style_name => array("media" => $media, "datetime" => $newest_file));
                }
            }
        }
    }
    
    /**
     * Generate Combined Script string
     * 
     * This method takes all desired output script files, orders them, then combines into a single string for caching
     * 
     * @name _generateCombinedScripts
     * @access private
     * @param Array  array of script files
     * @return Void
     */
    private function _generateCombinedScripts(Array $scripts)
    {
        $script_names = array_keys($scripts);
        $combined_script_name = md5(implode("", $script_names)).".js";
        $newest_file = $this->_getNewestModifiedDate($scripts);
        $cachefile = $this->_cacheFileExists($combined_script_name, "script");
        $combined = true;
        if ($cachefile !== false)
        {
            if ($newest_file > $cachefile['datetime'])
            {
                $combined = $this->_combineAssets($scripts, $combined_script_name);
            }
        }
        else if ($cachefile === false)
        {
            $combined = $this->_combineAssets($scripts, $combined_script_name);
        }
        
        // If there was an error combining the files
        if ($combined === false)
        {
            $this->_scripts = false;
        }
        else
        {
            $this->_scripts = array($combined_script_name => array("datetime" => $newest_file));
        }
    }
    
    /**
     * Combine Asset Files
     * 
     * This method actually combines the assets passed to it and saves it to a file
     * 
     * @name _combineAssets
     * @access private
     * @param Array  array of assets
     * @param String  name of combined file
     * @return bool
     */
    private function _combineAssets(Array $assets, $combined_name)
    {
        $combine_file = $this->_config['cache_path'].$combined_name;
        
        $tmp = array();
        foreach($assets as $asset)
        {
            $contents = $asset->getContents();
            if ($contents !== false)
            {
                $tmp[] = $contents;
            }
        }
        $fp = fopen($combine_file, "w");
        
        if ($fp === false)
        {
            return false;
        }
        foreach($tmp as $t)
        {
            fwrite($fp, $t);
        }
        fclose($fp);
        chmod($combine_file, 0644);
        
        return true;
    }
}