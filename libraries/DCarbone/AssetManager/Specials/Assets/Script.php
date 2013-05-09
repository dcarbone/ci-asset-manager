<?php namespace DCarbone\AssetManager\Specials\Assets;

use \DCarbone\AssetManager\Manager;

use \CssMin;
use \JSMin;
use \DCarbone\AssetManager\Generics\Asset;

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

class Script extends Asset
{

    /**
     * Constructor
     */
    public function __construct(Array $config, Array $args)
    {
        parent::__construct($config, $args);
        $this->extension = Manager::$scriptFileExtension;
    }

    /**
     * Get <script /> tag Output for this file
     *
     * @name GetOutput
     * @access public
     * @return String  html Output
     */
    public function GetOutput()
    {
        $Output = "<script type='text/javascript' language='javascript'";
        $Output .= " src='".str_ireplace(array("http:", "https:"), "", $this->GetSrc($this->IsDev())).$this->GetVer()."'";
        $Output .= "></script>";

        return $Output;
    }

    /**
     * Determine if script file exists
     *
     * @Override
     * @name FileExists
     * @access protected
     * @param String  file path / Address
     * @param String  type of file
     * @return Bool
     */
    protected function FileExists($file, $type = "")
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

        $filepath = $this->GetAssetPath().$file;
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
    public function GetAssetPath()
    {
        return $this->_config['script_path'];
    }

    /**
     * Get full file path for asset
     *
     * @Override
     * @name GetFilePath
     * @access protected
     * @param String  file name
     * @return String  asset path
     */
    protected function GetFilePath($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $file;

        return $filepath = $this->GetAssetPath().$file;
    }

    /**
     * Get Asset Url for specific asset
     *
     * @Override
     * @name GetAssetUrl
     * @access public
     * @return String  asset url
     */
    public function GetAssetUrl()
    {
        return $this->_config['script_url'];
    }

    /**
     * Get Full URL to file
     *
     * @Override
     * @name GetFileUrl
     * @access protected
     * @param String  file name
     * @return String  asset url
     */
    protected function GetFileUrl($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $file;

        return $filepath = $this->GetAssetUrl().$file;
    }

    /**
     * Minify Asset Data
     *
     * @Override
     * @name Minify
     * @access protected
     * @param String  file contents
     * @return String  minified file contents
     */
    protected function Minify($data)
    {
        return JSMin::minify($data);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @name Parse
     * @access protected
     * @param String  file contents
     * @return String  parsed file contents
     */
    protected function Parse($data)
    {
        $replace_keys = array_keys(Manager::$scriptBrackets);

        $replace_values = array_values(Manager::$scriptBrackets);

        return str_replace($replace_keys, $replace_values, $data);
    }
}
