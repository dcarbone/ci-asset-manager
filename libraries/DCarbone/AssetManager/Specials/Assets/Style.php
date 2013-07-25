<?php namespace DCarbone\AssetManager\Specials\Assets;

use \DCarbone\AssetManager\Manager;

use \CssMin;
use \JSMin;
use \DCarbone\AssetManager\Generics\Asset;

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

class Style extends Asset
{
    /**
     * Type of media this CSS file is for
     * @var String
     */
    public $media;

    public function __construct(Array $config, Array $args)
    {
        parent::__construct($config, $args);
        $this->extension = Manager::$styleFileExtension;
    }

    /**
     *
     */
    public function GetOutput()
    {
        $Output = "<link rel='stylesheet' type='text/css'";
        $Output .= " href='".str_ireplace(array("http:", "https:"), "", $this->GetSrc($this->IsDev())).$this->GetVer()."'";
        $Output .= " media='{$this->media}' />";

        return $Output;
    }

    /**
     * Get Asset Path for specific asset
     *
     * @name GetAssetPath
     * @access public
     * @return String
     */
    public function GetAssetPath()
    {
        return $this->_config['style_path'];
    }

    /**
     * Get Full Filepath for asset
     *
     * @name GetFilePath
     * @access protected
     * @param String  $file file name
     * @return String  file path
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
        return $this->_config['style_url'];
    }

    /**
     * Get File Url
     *
     * @Override
     * @name GetFileUrl
     * @access protected
     * @param String  $file filename
     * @return String  full url with file
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
     * @param String  $data file contents
     * @return String  minified file contents
     */
    protected function Minify($data)
    {
        return CssMin::minify($data);
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @name Parse
     * @access protected
     * @param String  $data file contents
     * @return String  parsed file contents
     */
    protected function Parse($data)
    {
        $replace_keys = array_keys(Manager::$styleBrackets);

        $replace_values = array_values(Manager::$styleBrackets);

        return str_replace($replace_keys, $replace_values, $data);
    }
}
