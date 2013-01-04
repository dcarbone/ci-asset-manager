<?php namespace AssetPackager\Asset;

/**
 * Asset Packager Style Asset class
 * 
 * @version 1.0
 * @author Daniel Carbone (daniel.p.carbone@vanderbilt.edu)
 * 
 */
 
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
            str_replace(array("http:", "https:"), "", asset_url())
        );
        
        return str_replace($replace_keys, $replace_with, $data);
    }
}
