<?php namespace AssetPackager\Asset;

/**
 * Asset Packager Style Asset class
 * 
 * @version 1.0
 * @author Daniel Carbone (daniel.p.carbone@vanderbilt.edu)
 * 
 */
 
 // Copyright (c) 2012-2013 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

class Style extends \AssetPackager\Asset
{
    public $media;
    
    public function __construct(Array $config, Array $args)
    {
        parent::__construct($config, $args);
        $this->extension = "css";
    }
    
    /**
     * 
     */
    public function getOutput()
    {
        $output = "<link rel='stylesheet' type='text/css'";
        $output .= " href='".$this->getSrc($this->isDev()).$this->getVer()."'";
        $output .= " media='{$this->media}' />";
        
        return $output;
    }
    
    /**
     * Get Asset Path for specific asset
     * 
     * @name getAssetPath
     * @access Public
     * @return String
     */
    public function getAssetPath()
    {
        return $this->_config['style_path'];
    }
    
    /**
     * Get Full Filepath for asset
     * 
     * @name _getFilePath
     * @access Protected
     * @param String  file name
     * @return String  file path
     */
    protected function _getFilePath($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $file;
        
        return $filepath = $this->getAssetPath().$file;
    }
    
    /**
     * Get Asset Url for specific asset
     * 
     * @Override
     * @name getAssetUrl
     * @access Public
     * @return String  asset url
     */
    public function getAssetUrl()
    {
        return $this->_config['style_url'];
    }
    
    /**
     * Get File Url
     * 
     * @Override
     * @name _getFileUrl
     * @access protected
     * @param String  filename
     * @return String  full url with file
     */
    protected function _getFileUrl($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $file;
        
        return $filepath = $this->getAssetUrl().$file;
    }
    
    /**
     * Minify Asset Data
     * 
     * @Override
     * @name _minify
     * @access Protected
     * @param String  file contents
     * @return String  minified file contents
     */
    protected function _minify($data)
    {
        return \CssMin::minify($data);
    }
    
    /**
     * Parse Asset File and replace key markers
     * 
     * @name _parse
     * @access Protected
     * @param String  file contents
     * @return String  parsed file contents
     */
    protected function _parse($data)
    {
        $replace_keys = array(
            "{assetURL}"
        );
        
        $replace_with = array(
            str_replace(array("http:", "https:"), "", $this->_config->asset_url)
        );
        
        return str_replace($replace_keys, $replace_with, $data);
    }
}
