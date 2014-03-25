<?php namespace DCarbone\AssetManager;

/*
    Complex Output Class for Asset Management Library
    Copyright (C) 2012-2014  Daniel Carbone (https://github.com/dcarbone)

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

use DCarbone\AssetManager\Asset\AbstractAsset;
use DCarbone\AssetManager\Asset\StyleAsset;

/**
 * Class ComplexOutput
 * @package DCarbone\AssetManager
 */
class ComplexOutput
{
    private $_config = array();

    private $_styles = array();
    private $_scripts = array();

    private $_cache_files = array();

    /**
     * Constructor
     *
     * @param array $styles
     * @param array $scripts
     * @param array $config
     */
    public function __construct(array $styles, array $scripts, array $config)
    {
        $this->_config = $config;

        $this->_cache_files = $this->_get_cache_file_array();

        $this->_generate_output($styles, $scripts);
    }

    /**
     * Get a list of the currently cached asset files
     *
     * @return array  array of cached assets
     */
    private function _get_cache_file_array()
    {
        $build_name_array = function(array $arr)
        {
            $return = array();
            foreach($arr as $a)
            {
                $return[basename($a)] = array(
                    'path' => $a,
                    'datetime' => new \DateTime('@'.(string)filemtime($a))
                );
            }
            return $return;
        };
        $style_files = $build_name_array(glob($this->_config['cache_path'].'*.css'));
        $script_files = $build_name_array(glob($this->_config['cache_path'].'*.js'));

        return array(
            'styles' => $style_files,
            'scripts' => $script_files,
            'all' => array_merge($style_files + $script_files)
        );
    }

    /**
     * Determine if file exists in cache
     *
     * @param string  $file file name
     * @param string  $type file type
     * @return bool
     */
    private function _cache_file_exists($file = "", $type = "")
    {
        if ($type === 'style' && array_key_exists($file, $this->_cache_files['styles']))
            return $this->_cache_files['styles'][$file];

        if ($type === 'script' && array_key_exists($file, $this->_cache_files['scripts']))
            return $this->_cache_files['scripts'][$file];

        if (array_key_exists($file, $this->_cache_files['all']))
            return $this->_cache_files['all'][$file];

        return false;
    }

    /**
     * Get Cached File Information
     *
     * @TODO finish this
     */
    private function _get_cache_file_info($file = "")
    {

    }

    /**
     * Get newest modification date of files within cache container
     *
     * @param array  array of files
     * @return \DateTime
     */
    private function _get_newest_date_modified(array $files)
    {
        $date = new \DateTime("0:00:00 January 1, 1970 UTC");
        foreach($files as $name=>$obj)
        {
            /** @var $obj AbstractAsset */
            $d = $obj->get_file_date_modified();

            if (!($d instanceof \DateTime))
                continue;
            else if ($d > $date)
                $date = $d;
        }
        return $date;
    }

    /**
     * Output Styles
     *
     * Echoes out css <link> tags
     *
     * @return bool
     */
    public function output_styles()
    {
        if ($this->_styles !== false && is_array($this->_styles))
        {
            $medias = array();
            foreach($this->_styles as $file=>$atts)
            {
                if (!isset($medias[$atts['media']]))
                    $medias[$atts['media']] = array();

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
     * @return bool
     */
    public function output_scripts()
    {
        if ($this->_scripts !== false && is_array($this->_scripts))
        {
            foreach($this->_scripts as $file=>$atts)
            {
                $surl = $this->_config['cache_url'].$file;
                echo "\n<script type='text/javascript' language='javascript' src='{$surl}?={$atts['datetime']->format("Ymd")}'></script>";
            }
            return true;
        }
        return false;
    }

    /**
     * Generate Output helper method
     *
     * This method calls one or more of the 2 specific combination Output methods
     *
     * @param array  array of styles
     * @param array  array of scripts
     * @return void
     */
    private function _generate_output(array $styles, array $scripts)
    {
        if (count($styles) > 0)
            $this->_generate_combined_styles($styles);

        if (count($scripts) > 0)
            $this->_generate_combined_scripts($scripts);
    }

    /**
     * Generate Combined Stylesheet string
     *
     * This method takes all of the desired styles, orders them, then combines into a single string for caching
     *
     * @param array  array of styles
     * @return void
     */
    private function _generate_combined_styles(array $styles)
    {
        $medias = array();
        $get_media = function (StyleAsset $style) use (&$medias)
        {
            if (isset($style->media) && !array_key_exists($style->media, $medias))
                $medias[$style->media] = array($style);
            else if (isset($style->media) && array_key_exists($style->media, $medias))
                $medias[$style->media][$style->get_name()] = $style;
            else if (isset($medias['screen']))
                $medias['screen'][$style->get_name()] = $style;
            else
                $medias['screen'] = array($style->get_name() => $style);
        };

        array_map($get_media, $styles);

        foreach($medias as $media=>$styles)
        {
            $newest_file = $this->_get_newest_date_modified($styles);
            $style_names = array_keys($styles);

            $combined_style_name = md5(\AssetManager::$file_prepend_value.implode("", $style_names)).".css";
            $cache_file = $this->_cache_file_exists($combined_style_name, "style");
            $combined = true;

            if ($cache_file !== false && $newest_file > $cache_file['datetime'])
                $combined = $this->_combine_assets($styles, $combined_style_name);
            else if ($cache_file === false)
                $combined = $this->_combine_assets($styles, $combined_style_name);

            // If there was an error combining the files
            if ($combined === false)
                $this->_styles = false;
            else if ($this->_styles === false)
                continue;
            else if (is_array($this->_styles))
                $this->_styles[$combined_style_name] = array("media" => $media, "datetime" => $newest_file);
            else
                $this->_styles = array($combined_style_name => array("media" => $media, "datetime" => $newest_file));
        }
    }

    /**
     * Generate Combined Script string
     *
     * This method takes all desired Output script files, orders them, then combines into a single string for caching
     *
     * @param array  array of script files
     * @return void
     */
    private function _generate_combined_scripts(array $scripts)
    {
        $script_names = array_keys($scripts);
        $combined_script_name = md5(\AssetManager::$file_prepend_value.implode("", $script_names)).".js";
        $newest_file = $this->_get_newest_date_modified($scripts);
        $cache_file = $this->_cache_file_exists($combined_script_name, "script");
        $combined = true;

        if ($cache_file !== false && $newest_file > $cache_file['datetime'])
            $combined = $this->_combine_assets($scripts, $combined_script_name);
        else if ($cache_file === false)
            $combined = $this->_combine_assets($scripts, $combined_script_name);

        // If there was an error combining the files
        if ($combined === false)
            $this->_scripts = false;
        else
            $this->_scripts = array($combined_script_name => array("datetime" => $newest_file));
    }

    /**
     * Combine Asset Files
     *
     * This method actually combines the assets passed to it and saves it to a file
     *
     * @param array  $assets array of assets
     * @param string  $combined_name name of combined file
     * @return bool
     */
    private function _combine_assets(array $assets, $combined_name)
    {
        $combine_file = $this->_config['cache_path'].$combined_name;

        $tmp = array();
        foreach($assets as $asset)
        {
            /** @var $asset AbstractAsset */
            $contents = $asset->get_asset_contents();

            if ($contents !== false)
                $tmp[] = $contents;
        }
        $fp = fopen($combine_file, "w");

        if ($fp === false)
            return false;

        foreach($tmp as $t)
        {
            fwrite($fp, $t);
        }
        fclose($fp);
        chmod($combine_file, 0644);

        return true;
    }
}