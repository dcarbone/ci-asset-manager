<?php namespace AssetPackager;

/**
 * Abstract Asset Packager Asset class
 * 
 * @version 1.0
 * @author Daniel Carbone (daniel.p.carbone@gmail.com)
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

abstract class Asset
{
    public $valid = true;
    
    public static $dateTimeZone = NULL;
    
    public $extension = NULL;
    
    public $group;
    
    public $cache;
    
    public $dev_file;
    public $dev_cache_file;
    public $dev_cache_file_min;
    
    public $prod_file;
    public $prod_cache_file;
    public $prod_cache_file_min;
    
    public $minify;
    
    public $name = null;
   
    public $dev_file_path = null;
    public $prod_file_path = null;
    
    public $dev_file_url = null;
    public $prod_file_url = null;
    
    public $dev_file_name = null;
    public $prod_file_name = null;
    
    
    public $dev_last_modified = null;
    public $prod_last_modified = null;
    
    // Array of names of same-type assets this asset requires
    public $requires = NULL;

    // so we don't have to keep checking if it is remote.
    public $dev_is_remote = false;
    public $prod_is_remote = false;
   
    // config from Assetpackager class
    protected $_config;
    
    // CI Instance
    protected static $_CI = null;
    
    /**
     * Constructor
     */
    public function __construct(Array $config, Array $args)
    {
        self::$dateTimeZone = new \DateTimeZone("UTC");
        
        $this->_config = $config;
        $this->_parseArgs($args);
        $this->valid = $this->_validate();
        
        if ($this->valid === true && $this->isCI())
        {
            if (is_null(self::$_CI)) self::$_CI = &get_instance();
        }
    }
    
    /**
     * Parse Arguments
     * 
     * @name _parseArgs
     * @access Protected
     * @param Array  arguments
     * @return Void
     */
    protected function _parseArgs(Array $args = array())
    {
        foreach($args as $k=>$v)
        {
            $this->$k = $v;
        }
    }
    
    /**
     * Input Validation
     * 
     * @name _validate
     * @access Protected
     * @return Boolean
     */
    protected function _validate()
    {
        if ($this->dev_file === "" && $this->prod_file === "")
        {
            $this->_failure(array('details' => "You have tried to add an asset to Asset Packager with undefined \"\$dev_file\" and \"\$prod_file\" values!"));
            return false;
        }
        
        if ($this->dev_file === "")
        {
            $this->dev_file_path = false;
            $this->dev_file_url = false;
        }
        else
        {
            if ($this->_fileExists($this->dev_file, 'dev'))
            {
                $this->dev_file_path = $this->_getFilePath($this->dev_file);
                $this->dev_file_url = $this->_getFileUrl($this->dev_file);
            }
            else
            {
                $this->_failure(array("details" => "You have specified an invalid file. FileName: \"{$this->dev_file}\""));
                return false;
            }
        }
        
        if ($this->prod_file === "")
        {
            $this->prod_file_path = false;
            $this->prod_file_url = false;
        }
        else
        {
            if ($this->_fileExists($this->prod_file, 'prod'))
            {
                $this->prod_file_path = $this->_getFilePath($this->prod_file);
                $this->prod_file_url = $this->_getFileUrl($this->prod_file);
            }
            else
            {
                $this->_failure(array("details" => "You have specified an invalid file. FileName: \"{$this->prod_file}\""));
                return false;
            }
        }
        return true;
    }
    
    /**
     * Determines if this asset is locally cacheable
     * 
     * @name canBeCached
     * @access Public
     * @return Bool
     */
    public function canBeCached()
    {
        return $this->cache;
    }
    
    /**
     * Determines if environment is "development"
     * 
     * @name isDev
     * @access Public
     * @return Bool  dev or not
     */
    public function isDev()
    {
        return $this->_config['dev'];
    }
    
    /**
     * Determines if being run inside CodeIgniter application
     * 
     * @name isCI
     * @access Public
     * @return Bool
     */
    public function isCI()
    {
        return $this->_config['CI'];
    }
    
    /**
     * Get Base URL from config
     * 
     * @name getBaseUrl
     * @access Public
     * @return String  base url
     */
    public function getBaseUrl()
    {
        return $this->_config['base_url'];
    }
    
    /**
     * Get Base File path
     * 
     * @name getBasePath
     * @access Public
     * @return String  base filepath
     */
    public function getBasePath()
    {
        return $this->_config['base_path'];
    }
    
    /**
     * Get Base Asset URL
     * 
     * @name getBaseAssetUrl
     * @access Public
     * @return String  asset url
     */
    public function getBaseAssetUrl()
    {
        return $this->_config['asset_url'];
    }
    
    /**
     * Get Base Asset File Path
     * 
     * @name getBaseAssetPath
     * @access Public
     * @return String  asset file path
     */
    public function getBaseAssetPath()
    {
        return $this->_config['asset_path'];
    }
    
    /**
     * Get Cache File Path
     * 
     * @name getCachePath
     * @access Public
     * @return String  cache file path
     */
    public function getCachePath()
    {
        return $this->_config['cache_path'];
    }
    
    /**
     * Get Cache URL
     * 
     * @name getCacheUrl
     * @access Public
     * @return String  cache url
     */
    public function getCacheURL()
    {
        return $this->_config['cache_url'];
    }
    
    /**
     * Get Error Callback Function
     * 
     * If this is not being run within CodeIgniter, the user can pass in a custom function that is
     * executed on error.
     * 
     * @name getErrorCallback
     * @access Public
     * @return Mixed
     */
    public function getErrorCallback()
    {
        return ((isset($this->_config['error_callback'])) ? $this->_config['error_callback'] : NULL);
    }
    
    /**
     * Get Name of current asset
     * 
     * Wrapper method for getFileName
     * 
     * @name getName
     * @access Public
     * @return String  name of file
     */
    public function getName()
    {
        if (is_null($this->name) || $this->name === "")
        {
            $this->name = $this->getFileName(false);
            if ($this->name === "") $this->name = $this->getFileName(true);
        }
        
        return $this->name;
    }
    
    /**
     * Get File Name of File
     * 
     * @name getFileName
     * @access Public
     * @param Bool  get dev file name
     * @return String  file name
     */
    public function getFileName($dev = true)
    {
        if ($dev === true)
        {
            if (is_null($this->dev_file_name))
            {
                $this->dev_file_name = "";
                if ($this->dev_file !== "")
                {
                    $ex = explode("/", $this->dev_file);
                    $this->dev_file_name = end($ex);
                }
            }
            return $this->dev_file_name;
        }
        else
        {
            if (is_null($this->prod_file_name))
            {
                $this->prod_file_name = "";
                if ($this->prod_file !== "")
                {
                    $ex = explode("/", $this->prod_file);
                    $this->prod_file_name = end($ex);
                }
            }
            return $this->prod_file_name;
        }
    }
    
    /**
     * Get Date Modified for Asset
     * 
     * @name getDateModified
     * @access Public
     * @param Bool  dev file
     * @return \DateTime object
     */
    public function getDateModified($dev = false)
    {
        if (is_null($this->dev_last_modified) || is_null($this->prod_last_modified))
        {
            if (is_null($this->dev_file_path) || $this->dev_file_path === false)
            {
                $this->dev_last_modified = false;
            }
            else if ($this->dev_is_remote === false && is_string($this->dev_file_path))
            {
                $this->dev_last_modified = new \DateTime("@".(string)filemtime($this->dev_file_path), self::$dateTimeZone);
            }
            else
            {
                $this->dev_last_modified = new \DateTime("0:00:00 January 1, 1970 UTC");
            }
            
            if (is_null($this->prod_file_path) || $this->prod_file_path === false)
            {
                $this->prod_last_modified = false;
            }
            else if ($this->prod_is_remote === false && is_string($this->prod_file_path))
            {
                $this->prod_last_modified = new \DateTime("@".(string)filemtime($this->prod_file_path), self::$dateTimeZone);
            }
            else
            {
                $this->prod_last_modified = new \DateTime("0:00:00 January 1, 1970 UTC");
            }
        }
        if ($dev === false)
        {
            if ($this->prod_last_modified instanceof \DateTime)
            {
                return $this->prod_last_modified;
            }
            else
            {
                return $this->dev_last_modified;
            }
        }
        else
        {
            return $this->dev_last_modified;
        }
    }

    /**
     * Get Date Modified for Cached Asset File
     * 
     * This differs from above in that there is no logic.  Find the path before executing.
     * 
     * @name getCachedDateModified
     * @access Public
     * @param String  cached filepath
     * @return \DateTime object
     */
    public function getCachedDateModified($path)
    {
        return new \DateTime("@".filemtime($path), self::$dateTimeZone);
    }
    
    /**
     * Get Groups of this asset
     * 
     * @name getGroups
     * @access Public
     * @return Array
     */
    public function getGroups()
    {
        return $this->group;
    }
    
    /**
     * Is Asset In Group
     * 
     * @name inGroup
     * @access Public
     * @param Mixed  array or strings of groups
     * @return Bool
     */
    public function inGroup($group)
    {
        if(in_array($group, $this->group))
        {
            return true;
        }
        return false;
    }
    
    /**
     * Add Asset to group
     * 
     * @name addGroups
     * @access Public
     * @param Mixed  array or string of group(s) to add asset to
     * @return Void
     */
    public function addGroups($groups)
    {
        if (is_string($groups) && $groups !== "" && !$this->inGroup($groups))
        {
            $this->group[] = $groups;
        }
        else if (is_array($groups) && count($groups) > 0)
        {
            foreach($groups as $group)
            {
                $this->addGroups($group);
            }
        }
    }
    
    /**
     * Get Assets required by this asset
     * 
     * @name getRequires
     * @access public
     * @return Array
     */
    public function getRequires()
    {
        if (is_string($this->requires))
            return array($this->requires);
        else if (is_array($this->requires))
            return $this->requires;
        else
            return array();
    }
    
    /**
     * Get src for asset
     * 
     * @name getSrc
     * @access Public
     * @param Bool  src of dev
     * @return String
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
        
        if ($dev === true)
        {
            return $this->dev_file_url;
        }
        else
        {
            return $this->prod_file_url;
        }
    }
    
    /**
     * Get File Version
     * 
     * @name getVer()
     * @access Public
     * @return String
     */
    public function getVer()
    {
        $file = (($this->isDev()) ? $this->dev_file_path : $this->prod_file_path);
        if (is_null($file)) $file = $this->dev_file_path;
        
        if (preg_match("#^(http://|https://|//)#i", $file))
        {
            return "?ver=19700101";
        }
        
        return "?ver=".date("Ymd", filemtime($file));
    }
    
    /**
     * Determine if File Exists
     * 
     * @name _fileExists
     * @access Protected
     * @param String  file name
     * @param String  asset type
     * @return Bool
     */
    protected function _fileExists($file, $type)
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
        
        if (!is_readable($filepath))
        {
            $this->_failure(array("details" => "Could not read asset file at \"{$filepath}\""));
            return false;
        }
        
        return true;
    }
    
    /**
     * Get fill url for cached file
     * 
     * @name getCachedFileUrl
     * @access Public
     * @param Bool get url for minified version
     * @return Mixed
     */
    public function getCachedFileUrl($minified = false)
    {
        if ($minified === false && $this->_cacheFileExists($minified))
        {
            return $this->getCacheUrl().$this->getName().".parsed.".$this->extension;
        }
        else if ($minified === true && $this->_cacheFileExists($minified))
        {
            return $this->getCacheUrl().$this->getName().".parsed.min.".$this->extension;
        }
        
        return false;
    }
    
    /**
     * Get Full path for cached version of file
     * 
     * @name getCachedFilePath
     * @access Public
     * @param Bool  look for minified version
     * @return Mixed
     */
    public function getCachedFilePath($minified = false)
    {
        if ($minified === false && $this->_cacheFileExists($minified))
        {
            return $this->getCachePath().$this->getName().".parsed.".$this->extension;
        }
        else if ($minified === true && $this->_cacheFileExists($minified))
        {
            return $this->getCachePath().$this->getName().".parsed.min.".$this->extension;
        }
        
        return false;
    }
    
    /**
     * Check of cache versions exists
     * 
     * @name _cacheFileExists
     * @access Protected
     * @param Bool  check for minified version
     * @return Bool
     */
    protected function _cacheFileExists($minified = false)
    {
        $_parsed = $this->getCachePath().$this->getName().".parsed.".$this->extension;
        $_parsed_minified = $this->getCachePath().$this->getName().".parsed.min.".$this->extension;
        
        if ($minified === false)
        {
            if (!file_exists($_parsed))
            {
                $this->_failure(array("details" => "Could not find file at \"{$_parsed}\""));
                return false;
            }
            
            if (!is_readable($_parsed))
            {
                $this->_failure(array("details" => "Could not read asset file at \"{$_parsed}\""));
                return false;
            }
        }
        else
        {
            if (!file_exists($_parsed_minified))
            {
                $this->_failure(array("details" => "Could not find file at \"{$_parsed_minified}\""));
                return false;
            }
            
            if (!is_readable($_parsed_minified))
            {
                $this->_failure(array("details" => "Could not read asset file at \"{$_parsed_minified}\""));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Create Cached versions of asset
     * 
     * @name _createCache
     * @access Protected
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
        
        if ($this->prod_file_path !== false && !is_null($this->prod_file_path))
        {
            $ref = $this->prod_file_path;
            $remote = $this->prod_is_remote;
        }
        else
        {
            $ref = $this->dev_file_path;
            $remote = $this->dev_is_remote;
        }
        
        if($remote || $this->_config['force_curl'])
        {
            $ch = curl_init($ref);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 5
            ));
            $contents = curl_exec($ch);
            curl_close($ch);
        }
        else
        {
            $contents = file_get_contents($ref);
        }
        
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

    
    /**
     * Get Contents for use
     * 
     * @name getContents
     * @access public
     * @return String  asset file contents
     */
    public function getContents()
    {
        if ($this->canBeCached())
        {
            return $this->_getCachedContents();
        }
        else
        {
            return $this->_getContents();
        }
    }
    
    /**
     * Get Contents of Cached Asset
     * 
     * Attempts to return contents of cached equivalent of file.
     * If unable, returns normal content;
     * 
     * @name _getCachedContents
     * @access Protected
     * @param Bool  get minified version
     * @return String
     */
    protected function _getCachedContents()
    {
        $cached = $this->_createCache();
        
        if ($cached === true)
        {
            $minify = (!$this->isDev() && $this->minify);
            
            $path = $this->getCachedFilePath($minify);
            
            if ($path === false)
            {
                return $this->_getContents();
            }
            else
            {
                $contents = file_get_contents($path);
                if (is_string($contents))
                {
                    return $contents;
                }
                else
                {
                    $this->_getContents();
                }
            }
        }
    }
    
    /**
     * Get Asset File Contents
     * 
     * @name _getContents
     * @access Protected
     * @return String;
     */
    protected function _getContents()
    {
        if ($this->prod_file_path !== false && !is_null($this->prod_file_path))
        {
            $ref = $this->prod_file_path;
            $remote = $this->prod_is_remote;
        }
        else
        {
            $ref = $this->dev_file_path;
            $remote = $this->dev_is_remote;
        }
        
        if($remote || $this->_config['force_curl'])
        {
            $ch = curl_init($ref);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 5
            ));
            $contents = curl_exec($ch);
            curl_close($ch);
        }
        else
        {
            $contents = file_get_contents($ref);
        }
        
        // If there was some issue getting the contents of the file
        if (!is_string($contents) || $contents === false)
        {
            $this->_failure(array("details" => "Could not get file contents for \"{$ref}\""));
            return false;
        }
        
        $contents = $this->_parse($contents);
        
        return $contents;
    }
    
    /**
     * Error Handling
     * 
     * @name _failure
     * @access Protected
     * @return Bool  False
     */
    protected function _failure(Array $args = array())
    {
        if ($this->isCI())
        {
            log_message("error", "Asset Manager: \"{$args['details']}\"");
            return false;
        }
        else
        {
            $callback = $this->getErrorCallback();
            if (!is_null($callback) && is_callable($callback))
            {
                $callback($args);
                return false;
            }
            else
            {
                return false;
            }
        }
    }
    
    /**
    * isURL
    * Checks if the provided string is a URL. Allows for port, path and query string validations.  
    * This should probably be moved into a helper file, but I hate to add a whole new file for 
    * one little 2-line function.
    * @access   public
    * @param    string to be checked
    * @return   boolean Returns TRUE/FALSE
    */
    public static function isURL($string)
    {
        $pattern = '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@';
        return preg_match($pattern, $string);
    }
    
    // These methods must be defined in the child concrete classes
    abstract protected function _getFilePath($file);
    abstract protected function _getFileUrl($file);
    abstract protected function _parse($data);
    abstract protected function _minify($data);
    
    abstract public function getOutput();
    abstract public function getAssetPath();
    abstract public function getAssetUrl();
}
