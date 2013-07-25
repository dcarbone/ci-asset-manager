<?php namespace DCarbone\AssetManager\Generics;

use \DateTime;
use \DateTimeZone;

use \DCarbone\AssetManager\Manager;

use \DCarbone\AssetManager\Generics\Asset;

use \CssMin;
use \JSMin;

use \DCarbone\AssetManager\Specials\Assets\Script;
use \DCarbone\AssetManager\Specials\Assets\Style;
use \DCarbone\AssetManager\Specials\Assets\ScriptView;

/*
    Complex Output Class for Asset Management Library
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

class Complex
{
    protected static $CI = null;

    private $_config = array();

    private $_styles = array();
    private $_scripts = array();

    private $Output = array();

    private $_Cache_files = array();

    /**
     * Constructor
     *
     * @param Array $styles  [description]
     * @param Array $scripts [description]
     * @param Array $config  [description]
     */
    public function __construct(Array $styles, Array $scripts, Array $config)
    {
        static::$CI =& get_instance();

        $this->_config = $config;

        $this->_Cache_files = $this->_GetCacheFileArray();

        $this->_GenerateOutput($styles, $scripts);
    }

    /**
     * Get a list of the currently cached asset files
     *
     * @name _GetCacheFileArray
     * @access private
     * @return Array  array of cached assets
     */
    private function _GetCacheFileArray()
    {
        $build_name_array = function(Array $arr)
        {
            $return = array();
            foreach($arr as $a)
            {
                $return[basename($a)] = array(
                    "path" => $a,
                    "datetime" => new DateTime("@".(string)filemtime($a))
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
     * @name _CacheFileExists
     * @access private
     * @param String  $file file name
     * @param String  $type file type
     * @return Bool
     */
    private function _CacheFileExists($file = "", $type = "")
    {
        switch($type)
        {
            case "style" :
                if (array_key_exists($file, $this->_Cache_files['styles']))
                {
                    return $this->_Cache_files['styles'][$file];
                }
                return false;
            break;
            case "script" :
                if (array_key_exists($file, $this->_Cache_files['scripts']))
                {
                    return $this->_Cache_files['scripts'][$file];
                }
                return false;
            break;
            default :
                if (array_key_exists($file, $this->_Cache_files['all']))
                {
                    return $this->_Cache_files['all'][$file];
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
    private function _GetCacheFileInfo($file = "")
    {

    }

    /**
     * Get newest modification date of files within cache container
     *
     * @name _GetNewestModifiedDate
     * @access private
     * @param Array  array of files
     * @return \DateTime
     */
    private function _GetNewestModifiedDate(Array $files)
    {
        $date = new DateTime("0:00:00 January 1, 1970 UTC");
        foreach($files as $name=>$obj)
        {
            $d = $obj->GetDateModified();

            if (!($d instanceof DateTime))
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
     * @name OutputStyles
     * @access public
     * @return Bool
     */
    public function OutputStyles()
    {
        if ($this->_styles !== false && is_array($this->_styles))
        {
            $medias = array();
            foreach($this->_styles as $file=>$atts)
            {
                if (!isset($medias[$atts['media']]))
                {
                    $medias[$atts['media']] = array();
                }
                $surl = $this->_config['cache_url'].$file;
                $medias[$atts['media']][] = "\n<link rel='stylesheet' type='text/css' media='{$atts['media']}' href='{$surl}?={$atts['datetime']->format("Ymd")}' />";
            }

            ob_start();
            if (isset($medias['all']))
            {
                foreach($medias['all'] as $asset)
                {
                    echo $asset;
                }
                unset($medias['all']);
                unset($asset);
            }
            if (isset($medias['screen']))
            {
                foreach($medias['screen'] as $asset)
                {
                    echo $asset;
                }
                unset($medias['screen']);
                unset($asset);
            }
            if (isset($medias['print']))
            {
                foreach($medias['print'] as $asset)
                {
                    echo $asset;
                }
                unset($medias['print']);
                unset($asset);
            }
            echo ob_get_clean();

            return true;
        }
        return false;
    }

    /**
     * Output Scripts
     *
     * Echoes out js <script> tags
     *
     * @name OutputScripts
     * @access public
     * @return Bool
     */
    public function OutputScripts()
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
     * This method calls one or more of the 2 specific combination Output methods
     *
     * @name _GenerateOutput
     * @access private
     * @param Array  array of styles
     * @param Array  array of scripts
     * @return void
     */
    private function _GenerateOutput(Array $styles, Array $scripts)
    {
        if (count($styles) > 0)
        {
            $this->_GenerateCombinedStyles($styles);
        }
        if (count($scripts) > 0)
        {
            $this->_GenerateCombinedScripts($scripts);
        }
    }

    /**
     * Generate Combined Stylesheet string
     *
     * This method takes all of the desired styles, orders them, then combines into a single string for caching
     *
     * @name _GenerateCombinedStyles
     * @access private
     * @param Array  array of styles
     * @return Void
     */
    private function _GenerateCombinedStyles(Array $styles)
    {
        $medias = array();
        $get_media = function (Style $style) use (&$medias)
        {
            if (isset($style->media) && !array_key_exists($style->media, $medias))
            {
                $medias[$style->media] = array($style);
            }
            else if (isset($style->media) && array_key_exists($style->media, $medias))
            {
                $medias[$style->media][$style->GetName()] = $style;
            }
            else
            {
                if (isset($medias['screen']))
                {
                    $medias['screen'][$style->GetName()] = $style;
                }
                else
                {
                    $medias['screen'] = array($style->GetName() => $style);
                }
            }
        };

        array_map($get_media, $styles);

        foreach($medias as $media=>$styles)
        {

            $newest_file = $this->_GetNewestModifiedDate($styles);
            $style_names = array_keys($styles);

            $combined_style_name = md5(Manager::$filePrependValue.implode("", $style_names)).".css";
            $cachefile = $this->_CacheFileExists($combined_style_name, "style");
            $combined = true;
            if ($cachefile !== false)
            {
                if ($newest_file > $cachefile['datetime'])
                {
                    $combined = $this->_CombineAssets($styles, $combined_style_name);
                }
            }
            else if ($cachefile === false)
            {
                $combined = $this->_CombineAssets($styles, $combined_style_name);
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
     * This method takes all desired Output script files, orders them, then combines into a single string for caching
     *
     * @name _GenerateCombinedScripts
     * @access private
     * @param Array  array of script files
     * @return Void
     */
    private function _GenerateCombinedScripts(Array $scripts)
    {
        $script_names = array_keys($scripts);
        $combined_script_name = md5(Manager::$filePrependValue.implode("", $script_names)).".js";
        $newest_file = $this->_GetNewestModifiedDate($scripts);
        $cachefile = $this->_CacheFileExists($combined_script_name, "script");
        $combined = true;
        if ($cachefile !== false && $newest_file > $cachefile['datetime'])
        {
            $combined = $this->_CombineAssets($scripts, $combined_script_name);
        }
        else if ($cachefile === false)
        {
            $combined = $this->_CombineAssets($scripts, $combined_script_name);
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
     * @name _CombineAssets
     * @access private
     * @param Array  $assets array of assets
     * @param String  $combined_name name of combined file
     * @return bool
     */
    private function _CombineAssets(Array $assets, $combined_name)
    {
        $combine_file = $this->_config['cache_path'].$combined_name;

        $tmp = array();
        foreach($assets as $asset)
        {
            $contents = $asset->GetContents();
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
