<?php namespace DCarbone\AssetPackager\Asset;

/**
 * Asset Packager Script Asset class
 * 
 * @version 1.0
 * @author Daniel Carbone (daniel.p.carbone@gmail.com)
 * 
 */
class Script extends \DCarbone\AssetPackager\Asset
{
    
    /**
     * Constructor
     */
    public function __construct(Array $config, Array $args)
    {
        parent::__construct($config, $args);
        $this->extension = "js";
    }
    
    /**
     * Get <script /> tag output for this file
     * 
     * @name getOutput
     * @access public
     * @return String  html output
     */
    public function getOutput()
    {
        $output = "<script type='text/javascript' language='javascript'";
        $output .= " src='".$this->getSrc($this->isDev()).$this->getVer()."'";
        $output .= "></script>";
        
        return $output;
    }
    
    /**
     * Determine if script file exists
     * 
     * @Override
     * @name _fileExists
     * @access protected
     * @param String  file path / address
     * @param String  type of file
     * @return Bool
     */
    protected function _fileExists($file, $type = "")
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
        
        $filepath = $this->getAssetPath().$file;
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
     * @name getAssetPath
     * @access public
     * @return String
     */
    public function getAssetPath()
    {
        return $this->_config['script_path'];
    }
    
    /**
     * Get full file path for asset
     * 
     * @Override
     * @name _getFilePath
     * @access protected
     * @param String  file name
     * @return String  asset path
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
     * @access public
     * @return String  asset url
     */
    public function getAssetUrl()
    {
        return $this->_config['script_url'];
    }
    
    /**
     * Get Full URL to file
     * 
     * @Override
     * @name _getFileUrl
     * @access protected
     * @param String  file name
     * @return String  asset url
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
     * @access protected
     * @param String  file contents
     * @return String  minified file contents
     */
    protected function _minify($data)
    {
        return \JSMin::minify($data);
    }
    
    /**
     * Parse Asset File and replace key markers
     * 
     * @name _parse
     * @access protected
     * @param String  file contents
     * @return String  parsed file contents
     */
    protected function _parse($data)
    {
        $replace_keys = array(
            "{baseURL}",
#            "{currentURL}",
#            "{escapeCurrentURL}",
            "{assetURL}",
            "{environment}",
            "{debug}"
        );
/*    Current URL not currently enabled.
        $current_url = str_replace(array("/index.php/preview", "index.php/"), "", current_url());
        //$url .= "?session_id=".$this->session->userdata("session_id");
        
        $qs = $this->input->server("QUERY_STRING");
        
        if ($qs !== false) $current_url .= "?{$qs}";
*/        
        $replace_with = array(
            base_url(),
#            $current_url,
#            urlencode($current_url),
            str_replace(array("http:", "https:"), "", $this->_config['asset_url']),
            ((defined("ENVIRONMENT")) ? strtolower(constant("ENVIRONMENT")) : "production"),
            ((defined("ENVIRONMENT") && constant("ENVIRONMENT") === "DEVELOPMENT") ? "true" : "false")
        );
        
        return str_replace($replace_keys, $replace_with, $data);
    }
}