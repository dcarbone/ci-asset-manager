<?php namespace DCarbone\AssetManager\Asset;

use JShrink\Minifier;

/*
    Script Asset Class for AssetManager Library
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
 * Class ScriptAsset
 * @package DCarbone\AssetManager\Asset
 */
class ScriptAsset extends AbstractAsset implements IAsset
{
    /** @var array */
    protected $jshrink_options = array();

    /**
     * Constructor
     *
     * @param array $asset_params
     */
    public function __construct(array $asset_params)
    {
        parent::__construct($asset_params);

        if (isset($args['jshrink_options']) && is_array($args['jshrink_options']))
            $this->jshrink_options = $args['jshrink_options'];
    }

    /**
     * Get <script /> tag Output for this file
     *
     * @return string  html Output
     */
    public function generate_output()
    {
        $output = "<script type='text/javascript' language='javascript'";
        $output .= " src='".str_ireplace(array("http:", "https:"), "", $this->get_file_src());
        $output .= '?v='.$this->get_file_version()."'></script>";

        return $output;
    }

    /**
     * Determine if script file exists
     *
     * @param string  $file file path / Address
     * @return Bool
     */
    public function asset_file_exists($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $this->file_is_remote = true;

        $file_path = $this->get_asset_path().$file;
        if (!file_exists($file_path))
        {
            $this->_failure(array("details" => "Could not find file at \"{$file_path}\""));
            return false;
        }

        return true;
    }

    /**
     * Get Asset Path for specific asset
     *
     * @return string
     */
    public function get_asset_path()
    {
        $config = \AssetManager::get_config();
        return $config['script_path'];
    }

    /**
     * Get Asset Url for specific asset
     *
     * @return string  asset url
     */
    public function get_asset_url()
    {
        $config = \AssetManager::get_config();
        return $config['script_url'];
    }

    /**
     * Minify Asset Data
     *
     * @param string  $data file contents
     * @return string  minified file contents
     */
    public function minify($data)
    {
        return Minifier::minify($data, $this->jshrink_options);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @param string  $data file contents
     * @return string  parsed file contents
     */
    public function parse_asset_file($data)
    {
        $replace_keys = array_keys(\AssetManager::$script_brackets);

        $replace_values = array_values(\AssetManager::$script_brackets);

        return str_replace($replace_keys, $replace_values, $data)."\n;";
    }

    /**
     * @return string
     */
    public function get_file_extension()
    {
        return \AssetManager::$script_file_extension;
    }
}