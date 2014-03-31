<?php namespace DCarbone\AssetManager\Asset;

/*
    Style Asset Class for AssetManager Library
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

/**
 * Class StyleAsset
 * @package DCarbone\AssetManager\Asset
 */
class StyleAsset extends AbstractAsset implements IAsset
{
    /**
     * Type of media this CSS file is for
     * @var string
     */
    public $media;

    /**
     * Constructor
     *
     * @param array $args
     * @return \DCarbone\AssetManager\Asset\StyleAsset
     */
    public function __construct(array $args)
    {
        parent::__construct($args);
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
        $config = \AssetManager::get_config();
        return $config['style_path'];
    }

    /**
     * Get Asset Url for specific asset
     *
     * @return string  asset url
     */
    public function get_asset_url()
    {
        $config = \AssetManager::get_config();
        return $config['style_url'];
    }

    /**
     * Minify Asset Data
     *
     * @param string  $data file contents
     * @return string  minified file contents
     */
    public function minify($data)
    {
        return \CssMin::minify($data);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @param string  $data file contents
     * @return string  parsed file contents
     */
    public function parse_asset_file($data)
    {
        $replace_keys = array_keys(\AssetManager::$style_brackets);

        $replace_values = array_values(\AssetManager::$style_brackets);

        return str_replace($replace_keys, $replace_values, $data)."\n";
    }
}