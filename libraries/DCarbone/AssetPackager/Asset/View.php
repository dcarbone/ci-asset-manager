<?php namespace DCarbone\AssetPackager\Asset;

/**
 * Asset Packager Script View Asset class
 * 
 * @version 1.0
 * @author Daniel Carbone (daniel.p.carbone@gmail.com)
 * 
 */
 
class View extends \DCarbone\AssetPackager\Asset
{
    /**
     * @Constructor
     * 
     * @param Array $config
     * @param Array $args
     */
    public function __construct(Array $config, Array $args)
    {
        parent::__construct($config, $args);
        $this->extension = "js";
    }
    
    /**
     * File Validation
     * 
     * @override
     * @name _validate
     * @access protected
     * @return Boolean
     */
    protected function _validate()
    {
        if(!is_string($this->dev_file) || (is_string($this->dev_file) && $this->dev_file === ""))
        {
            $this->_failure(array("details" => "View filename must be a non-empty String!"));
            return false;
        }
        
        if ($this->_fileExists($this->dev_file))
        {
            $this->file_path = $this->_getFilePath($this->dev_file);
        }
        else
        {
            return false;
        }
        return true;
    }
    
    /**
     * Get File Name
     * 
     * @override
     * @access public
     * @return Mixed  Filename or False
     * 
     * This differs from the standard getFileName
     * in that it utilizes $this->dev_file rather than
     * $this->dev_file or $this->prod_file, as views
     * do not have a dev / prod difference
     */
    public function getFileName($dev = true)
    {
        if (is_null($this->dev_file))
        {
            if ($this->dev_file !== "")
            {
                $ex = explode("/", $this->dev_file);
                $this->dev_file = end($ex);
            }
        }
        return $this->dev_file;
    }
    
    /**
     * Check if File Exists
     * 
     * Script Views should never be remote
     * $type is not used for Views
     * 
     * @Override
     * @name _fileExists
     * @access protected
     * @param String  file name
     * @param String  asset type
     * @return Bool
     */
    protected function _fileExists($file, $type = "dev")
    {
        // Views should never be remote resources
        if (preg_match("#^(http|//)#i", $file))
        {
            $this->_failure(array("details" => "Script views cannot be remote files!"));
            return false;
        }
        
        $filepath = $this->getAssetPath().$file;
        if (!file_exists($filepath))
        {
            $this->_failure(array("details" => "Could not find script view file at \"{$filepath}\" (views cannot be remote files)"));
            return false;
        }
        
        if (!is_readable($filepath))
        {
            $this->_failure(array("details" => "Could not read script view file at \"{$filepath}\""));
            return false;
        }
        
        return true;
    }

    /**
     * Get Asset Path for specific asset
     * 
     * @name getAssetPath
     * @access public
     * @return String
     */
    public function getAssetPath()
    {
        return $this->_config['script_view_path'];
    }
    
    /**
     * Get Full Filepath for asset
     * 
     * @name _getFilePath
     * @access protected
     * @param String  file name
     * @return String  file path
     */
    protected function _getFilePath($file = "")
    {
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
        return $this->_config['script_view_url'];
    }
    
    /**
     * Get <script src=""> attribute for asset
     * 
     * @override
     * @name getSrc
     * @access public
     * @param Boolean  is envirnment development
     * @return String  src string for file
     */
    public function getSrc($dev = true)
    {
        if ($this->canBeCached())
        {
            $minify = (!$this->isDev() && $this->minify);
            $this->_createCache();
            $url = $this->getCachedFileUrl($minify);
            
            
            if ($url !== false) return $url;
        }
        return $this->_getFileUrl($this->dev_file);
    }
    
    /**
     * Get File Url
     * 
     * @name _getFileUrl
     * @access protected
     * @param String  filename
     * @return String  full url with file
     */
    protected function _getFileUrl($file = "")
    {
        return $filepath = $this->getAssetUrl().$file;
    }
    
    /**
     * Get datemodified version
     * 
     * @override
     * @name getVer
     * @access public
     * @return String  version appendage
     */
    public function getVer()
    {
        $file = $this->file_path;
        
        return "?ver=".date("Ymd", filemtime($file));
    }
    
    public function getOutput()
    {
        $output = "<script type='text/javascript' language='javascript'";
        $output .= " src='".$this->getSrc($this->isDev()).$this->getVer()."'";
        $output .= "></script>";
        return $output;
    }
    
    
    public function getDateModified($dev = true)
    {
        if (is_null($this->dev_last_modified))
        {
            $zone = new \DateTimeZone("UTC");
            
            $this->dev_last_modified = new \DateTime("@".(string)filemtime($this->file_path), $zone);
        }
        return $this->dev_last_modified;
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

    /**
     * Create Cached versions of asset
     * 
     * @name _createCache
     * @access protected
     * @return Bool
     */
    protected function _createCache()
    {
        if ($this->canBeCached() === false)
        {
            return;
        }
        
        $_create_parsed_cache = false;
        $_create_parsed_min_cache = false;
        
        $modified = $this->getDateModified($this->isDev());
        
        $parsed = $this->getCachedFilePath(false);
        $parsed_min = $this->getCachedFilePath(true);
        
        if ($parsed !== false)
        {
            $parsed_modified = $this->getCachedDateModified($parsed);
            if ($parsed_modified instanceof \DateTime)
            {
                if ($modified > $parsed_modified)
                {
                    $_create_parsed_cache = true;
                }
            }
            else
            {
                $_create_parsed_cache = true;
            }
        }
        else
        {
            $_create_parsed_cache = true;
        }
        
        if ($parsed_min !== false)
        {
            $parsed_modified = $this->getCachedDateModified($parsed_min);
            if ($parsed_modified instanceof \DateTime)
            {
                if ($modified > $parsed_modified)
                {
                    $_create_parsed_min_cache = true;
                }
            }
            else
            {
                $_create_parsed_min_cache = true;
            }
        }
        else
        {
            $_create_parsed_min_cache = true;
        }
        
        if ($_create_parsed_cache === false && $_create_parsed_min_cache === false)
        {
            return true;
        }
        
        $contents = file_get_contents($this->file_path);
                
        // If there was some issue getting the contents of the file
        if (!is_string($contents) || $contents === false)
        {
            $this->_failure(array("details" => "Could not get file contents for \"{$ref}\""));
            return false;
        }
        
        $contents = $this->_parse($contents);

        if ($_create_parsed_min_cache === true)
        {
            // If we successfully got the file's contents
            $minified = $this->_minify($contents);
            
            $min_fopen = fopen($this->getCachePath().$this->getName().".parsed.min.".$this->extension, "w");
            
            if ($min_fopen === false)
            {
                return false;
            }
            fwrite($min_fopen, $minified."\n");
            fclose($min_fopen);
            chmod($this->getCachePath().$this->getName().".parsed.min.".$this->extension, 0644);
        }
        
        if ($_create_parsed_cache === true)
        {
$comment = <<<EOD
/*
|--------------------------------------------------------------------------
| {$this->getName()}
|--------------------------------------------------------------------------
| Last Modified : {$this->getDateModified()->format("Y m d")}
*/
EOD;
            $parsed_fopen = fopen($this->getCachePath().$this->getName().".parsed.".$this->extension, "w");
            
            if ($parsed_fopen === false)
            {
                return false;
            }
            fwrite($parsed_fopen, $comment.$contents."\n");
            fclose($parsed_fopen);
            chmod($this->getCachePath().$this->getName().".parsed.".$this->extension, 0644);
        }
        return true;
    }

}
 