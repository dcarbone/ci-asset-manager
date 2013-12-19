<?php namespace DCarbone\AssetManager\Asset;

/*
    Style Asset Class for AssetManager Library
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
 * Class StyleAsset
 * @package DCarbone\AssetManager\Asset
 */
class StyleAsset extends AbstractAsset
{
    /**
     * Type of media this CSS file is for
     * @var string
     */
    public $media;

    /**
     * @param array $config
     * @param array $args
     */
    public function __construct(array $config, array $args)
    {
        parent::__construct($config, $args);
        $this->extension = \AssetManager::$style_file_extension;
    }

    /**
     * @return string
     */
    public function get_output()
    {
        $output = "<link rel='stylesheet' type='text/css'";
        $output .= " href='".str_ireplace(array("http:", "https:"), "", $this->get_file_src());
        $output .= $this->get_file_version()."' media='{$this->media}' />";

        return $output;
    }

    /**
     * Get Asset Path for specific asset
     *
     * @return string
     */
    public function get_asset_path()
    {
        return $this->config['style_path'];
    }

    /**
     * Get Full Filepath for asset
     *
     * @param string  $file file name
     * @return string  file path
     */
    protected function get_file_path($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $file;

        return $filepath = $this->get_asset_path().$file;
    }

    /**
     * Get Asset Url for specific asset
     *
     * @return string  asset url
     */
    public function get_asset_url()
    {
        return $this->config['style_url'];
    }

    /**
     * Get File Url
     *
     * @param string  $file filename
     * @return string  full url with file
     */
    protected function get_file_url($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $file;

        return $filepath = $this->get_asset_url().$file;
    }

    /**
     * Minify Asset Data
     *
     * @param string  $data file contents
     * @return string  minified file contents
     */
    protected function minify($data)
    {
        return \CssMin::minify($data);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @param string  $data file contents
     * @return string  parsed file contents
     */
    protected function parse_asset_file($data)
    {
        $replace_keys = array_keys(\AssetManager::$style_brackets);

        $replace_values = array_values(\AssetManager::$style_brackets);

        return str_replace($replace_keys, $replace_values, $data);
    }
}