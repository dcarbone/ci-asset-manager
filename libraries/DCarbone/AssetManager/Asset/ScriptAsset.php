<?php namespace DCarbone\AssetManager\Asset;

/*
    Script Asset Class for AssetManager Library
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
     * @return String  html Output
     */
    public function get_output()
    {
        $output = "<script type='text/javascript' language='javascript'";
        if ($this->is_dev())
            $output .= " src='".str_ireplace(array("http:", "https:"), "", $this->get_dev_src());
        else
            $output .= " src='".str_ireplace(array("http:", "https:"), "", $this->get_prod_src());

        $output .= $this->get_file_version()."'></script>";

        return $output;
    }

    /**
     * Determine if script file exists
     *
     * @param String  $file file path / Address
     * @param String  $type type of file
     * @return Bool
     */
    protected function file_exists($file, $type = "")
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
        {
            switch($type)
            {
                case "dev" : $this->dev_is_remote = true; break;
                case "prod" : $this->prod_is_remote = true; break;
            }
            return true;
        }

        $filepath = $this->get_asset_path().$file;
        if (!file_exists($filepath))
        {
            $this->_failure(array("details" => "Could not find file at \"{$filepath}\""));
            return false;
        }

        return true;
    }

    /**
     * Get Asset Path for specific asset
     *
     * @Override
     * @name GetAssetPath
     * @access public
     * @return String
     */
    public function get_asset_path()
    {
        return $this->config['script_path'];
    }

    /**
     * Get full file path for asset
     *
     * @Override
     * @name GetFilePath
     * @access protected
     * @param String  $file file name
     * @return String  asset path
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
     * @Override
     * @name GetAssetUrl
     * @access public
     * @return String  asset url
     */
    public function get_asset_url()
    {
        return $this->config['script_url'];
    }

    /**
     * Get Full URL to file
     *
     * @Override
     * @name GetFileUrl
     * @access protected
     * @param String  $file file name
     * @return String  asset url
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
     * @Override
     * @name Minify
     * @access protected
     * @param String  $data file contents
     * @return String  minified file contents
     */
    protected function minify($data)
    {
        return \JSMin::minify($data);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @name Parse
     * @access protected
     * @param String  $data file contents
     * @return String  parsed file contents
     */
    protected function parse_asset_file($data)
    {
        $replace_keys = array_keys(\AssetManager::$script_brackets);

        $replace_values = array_values(\AssetManager::$script_brackets);

        return str_replace($replace_keys, $replace_values, $data);
    }
}