<?php namespace DCarbone\AssetManager\Asset;

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
class ScriptAsset extends AbstractAsset
{
    /**
     * Constructor
     */
    public function __construct(array $config, array $args)
    {
        parent::__construct($config, $args);
        $this->extension = \AssetManager::$script_file_extension;
    }

    /**
     * Get <script /> tag Output for this file
     *
     * @return string  html Output
     */
    public function get_output()
    {
        $output = "<script type='text/javascript' language='javascript'";
        $output .= " src='".str_ireplace(array("http:", "https:"), "", $this->get_file_src());
        $output .= $this->get_file_version()."'></script>";

        return $output;
    }

    /**
     * Determine if script file exists
     *
     * @param string  $file file path / Address
     * @param string  $type type of file
     * @return Bool
     */
    protected function file_exists($file, $type = "")
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
        return $this->config['script_path'];
    }

    /**
     * Get full file path for asset
     *
     * @param string  $file file name
     * @return string  asset path
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
        return $this->config['script_url'];
    }

    /**
     * Get Full URL to file
     *
     * @param string  $file file name
     * @return string  asset url
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
        return \JSMin::minify($data);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @param string  $data file contents
     * @return string  parsed file contents
     */
    protected function parse_asset_file($data)
    {
        $replace_keys = array_keys(\AssetManager::$script_brackets);

        $replace_values = array_values(\AssetManager::$script_brackets);

        return str_replace($replace_keys, $replace_values, $data);
    }
}