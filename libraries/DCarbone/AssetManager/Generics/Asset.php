<?php namespace DCarbone\AssetManager\Generics;

use \DateTime;
use \DateTimeZone;

use \DCarbone\AssetManager\Manager;

/*
    Asset Class for AssetManager CodeIgniter Library
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

abstract class Asset
{
    /**
     * Valid Asset
     * @var boolean
     */
    public $valid = true;

    /**
     * DateTimeZone object
     * @var DateTimeZone
     */
    protected static $dateTimeZone = NULL;

    /**
     * File Extension
     * @var string
     */
    public $extension = NULL;

    /**
     * The groups this asset belongs to
     * @var array
     */
    public $group = array();

    /**
     * Is asset cacheable
     * @var boolean
     */
    public $cache = true;

    /**
     * Dev File Name
     * @var String
     */
    public $dev_file = null;
    /**
     * Dev Cache File Name
     * @var String
     */
    public $dev_cache_file = null;
    /**
     * Dev Minified Cache File Name
     * @var String
     */
    public $dev_cache_file_min = null;

    /**
     * Production File Name
     * @var String
     */
    public $prod_file = null;
    /**
     * Production Cache File Name
     * @var String
     */
    public $prod_cache_file = null;
    /**
     * Production Minified Cache File Name
     * @var String
     */
    public $prod_cache_file_min = null;

    /**
     * Can this asset be minified
     * @var boolean
     */
    public $minify = true;

    /**
     * Name of asset
     * @var String
     */
    public $name = null;

    /**
     * Dev File path
     * @var String
     */
    public $dev_file_path = null;
    /**
     * Dev File URL
     * @var String
     */
    public $dev_file_url = null;
    /**
     * Dev File Name
     * @var String
     */
    public $dev_file_name = null;

    /**
     * Production File Path
     * @var String
     */
    public $prod_file_path = null;
    /**
     * Production File URL
     * @var String
     */
    public $prod_file_url = null;
    /**
     * Production File Name
     * @var String
     */
    public $prod_file_name = null;

    /**
     * Last Modified Date for Dev File
     * @var DateTime
     */
    public $dev_last_modified = null;
    /**
     * Last Modified Date for Prod File
     * @var DateTime
     */
    public $prod_last_modified = null;

    /**
     * Array of names of same-type assets this asset requires
     * @var Array
     */
    public $requires = array();

    /**
     * Is Dev File Remote?
     * @var boolean
     */
    public $dev_is_remote = false;
    /**
     * Is Prod File Remote?
     * @var boolean
     */
    public $prod_is_remote = false;

    /**
     * AssetManager Configuration
     * @var Array
     */
    protected $_config = array();

    /**
     * CodeIgniter Instance
     * @var Object
     */
    protected static $CI = null;

    /**
     * Constructor
     */
    public function __construct(Array $config, Array $args)
    {
        (static::$dateTimeZone instanceof DateTimeZone) OR static::$dateTimeZone = new DateTimeZone("UTC");

        $this->_config = $config;
        $this->ParseArgs($args);
        $this->valid = $this->Validate();

        if ($this->valid === true)
        {
            if (static::$CI === null) static::$CI = &get_instance();
        }
    }

    /**
     * Parse Arguments
     *
     * @name ParseArgs
     * @access protected
     * @param Array  $args arguments
     * @return Void
     */
    protected function ParseArgs(Array $args = array())
    {
        foreach($args as $k=>$v)
        {
            switch($k)
            {
                case "group" :
                case "groups" :
                    if (is_string($v))
                        $v = array($v);

                default : $this->$k = $v;
            }
        }
    }

    /**
     * Input Validation
     *
     * @name Validate
     * @access protected
     * @return Boolean
     */
    protected function Validate()
    {
        if ($this->dev_file === "" && $this->prod_file === "")
        {
            $this->_failure(array('details' => "You have tried to Add an asset to Asset Manager with undefined \"\$dev_file\" and \"\$prod_file\" values!"));
            return false;
        }

        if ($this->dev_file === "")
        {
            $this->dev_file_path = false;
            $this->dev_file_url = false;
        }
        else
        {
            if ($this->FileExists($this->dev_file, 'dev'))
            {
                $this->dev_file_path = $this->GetFilePath($this->dev_file);
                $this->dev_file_url = $this->GetFileUrl($this->dev_file);
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
            if ($this->FileExists($this->prod_file, 'prod'))
            {
                $this->prod_file_path = $this->GetFilePath($this->prod_file);
                $this->prod_file_url = $this->GetFileUrl($this->prod_file);
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
     * @name CanBeCached
     * @access public
     * @return Bool
     */
    public function CanBeCached()
    {
        return $this->cache;
    }

    /**
     * Determines if environment is "development"
     *
     * @name IsDev
     * @access public
     * @return Bool  dev or not
     */
    public function IsDev()
    {
        return $this->_config['dev'];
    }

    /**
     * Determines if being run inside CodeIgniter application
     *
     * @name IsCI
     * @access public
     * @return Bool
     */
    public function IsCI()
    {
        return $this->_config['CI'];
    }

    /**
     * Get Base URL from config
     *
     * @name GetBaseUrl
     * @access public
     * @return String  base url
     */
    public function GetBaseUrl()
    {
        return $this->_config['base_url'];
    }

    /**
     * Get Base File path
     *
     * @name GetBasePath
     * @access public
     * @return String  base filepath
     */
    public function GetBasePath()
    {
        return $this->_config['base_path'];
    }

    /**
     * Get Base Asset URL
     *
     * @name GetBaseAssetUrl
     * @access public
     * @return String  asset url
     */
    public function GetBaseAssetUrl()
    {
        return $this->_config['asset_url'];
    }

    /**
     * Get Base Asset File Path
     *
     * @name GetBaseAssetPath
     * @access public
     * @return String  asset file path
     */
    public function GetBaseAssetPath()
    {
        return $this->_config['asset_path'];
    }

    /**
     * Get Cache File Path
     *
     * @name GetCachePath
     * @access public
     * @return String  cache file path
     */
    public function GetCachePath()
    {
        return $this->_config['cache_path'];
    }

    /**
     * Get Cache URL
     *
     * @name GetCacheUrl
     * @access public
     * @return String  cache url
     */
    public function GetCacheURL()
    {
        return $this->_config['cache_url'];
    }

    /**
     * Get Error Callback Function
     *
     * If this is not being run within CodeIgniter, the user can pass in a custom function that is
     * executed on error.
     *
     * @name GetErrorCallback
     * @access public
     * @return Mixed
     */
    public function GetErrorCallback()
    {
        return ((isset($this->_config['error_callback'])) ? $this->_config['error_callback'] : NULL);
    }

    /**
     * Get Name of current asset
     *
     * Wrapper method for GetFileName
     *
     * @name GetName
     * @access public
     * @return String  name of file
     */
    public function GetName()
    {
        if ($this->name === null || $this->name === "")
        {
            $this->name = $this->GetFileName(false);
            if ($this->name === "") $this->name = $this->GetFileName(true);
        }

        return $this->name;
    }

    /**
     * Get File Name of File
     *
     * @name GetFileName
     * @access public
     * @param Bool  $dev get dev file name
     * @return String  file name
     */
    public function GetFileName($dev = true)
    {
        if ($dev === true)
        {
            if ($this->dev_file_name === null)
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
            if ($this->prod_file_name === null)
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
     * @name GetDateModified
     * @access public
     * @param Bool  $dev dev file
     * @return DateTime object
     */
    public function GetDateModified($dev = false)
    {
        if ($this->dev_last_modified === null || $this->prod_last_modified === null)
        {
            if ($this->dev_file_path === null || $this->dev_file_path === false)
            {
                $this->dev_last_modified = false;
            }
            else if ($this->dev_is_remote === false && is_string($this->dev_file_path))
            {
                $this->dev_last_modified = new DateTime("@".(string)filemtime($this->dev_file_path), static::$dateTimeZone);
            }
            else
            {
                $this->dev_last_modified = new DateTime("0:00:00 January 1, 1970 UTC");
            }

            if ($this->prod_file_path === null || $this->prod_file_path === false)
            {
                $this->prod_last_modified = false;
            }
            else if ($this->prod_is_remote === false && is_string($this->prod_file_path))
            {
                $this->prod_last_modified = new DateTime("@".(string)filemtime($this->prod_file_path), static::$dateTimeZone);
            }
            else
            {
                $this->prod_last_modified = new DateTime("0:00:00 January 1, 1970 UTC");
            }
        }
        if ($dev === false)
        {
            if ($this->prod_last_modified instanceof DateTime)
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
     * @name GetCachedDateModified
     * @access public
     * @param String  $path cached filepath
     * @return DateTime object
     */
    public function GetCachedDateModified($path)
    {
        return new DateTime("@".filemtime($path), static::$dateTimeZone);
    }

    /**
     * Get Groups of this asset
     *
     * @name GetGroups
     * @access public
     * @return Array
     */
    public function GetGroups()
    {
        return $this->group;
    }

    /**
     * Is Asset In Group
     *
     * @name InGroup
     * @access public
     * @param Mixed  array or strings of groups
     * @return Bool
     */
    public function InGroup($group)
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
     * @name AddGroups
     * @access public
     * @param Mixed  array or string of group(s) to Add asset to
     * @return Void
     */
    public function AddGroups($groups)
    {
        if (is_string($groups) && $groups !== "" && !$this->InGroup($groups))
        {
            $this->group[] = $groups;
        }
        else if (is_array($groups) && count($groups) > 0)
        {
            foreach($groups as $group)
            {
                $this->AddGroups($group);
            }
        }
    }

    /**
     * Get Assets required by this asset
     *
     * @name GetRequires
     * @access public
     * @return Array
     */
    public function GetRequires()
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
     * @name GetSrc
     * @access public
     * @param Bool  $dev src of dev
     * @return String
     */
    public function GetSrc($dev = true)
    {
        if ($this->CanBeCached())
        {
            $minify = (!$this->IsDev() && $this->minify);

            $this->CreateCache();
            $url = $this->GetCachedFileUrl($minify);
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
     * @name GetVer()
     * @access public
     * @return String
     */
    public function GetVer()
    {
        $file = (($this->IsDev()) ? $this->dev_file_path : $this->prod_file_path);
        if ($file === null ) $file = $this->dev_file_path;

        if (preg_match("#^(http://|https://|//)#i", $file))
        {
            return "?ver=19700101";
        }

        return "?ver=".date("Ymd", filemtime($file));
    }

    /**
     * Determine if File Exists
     *
     * @name FileExists
     * @access protected
     * @param String  $file file name
     * @param String  $type asset type
     * @return Bool
     */
    protected function FileExists($file, $type)
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
     * @name GetCachedFileUrl
     * @access public
     * @param Bool $minified get url for minified version
     * @return Mixed
     */
    public function GetCachedFileUrl($minified = false)
    {
        if ($minified === false && $this->CacheFileExists($minified))
        {
            return $this->GetCacheUrl().Manager::$filePrependValue.$this->GetName().".parsed.".$this->extension;
        }
        else if ($minified === true && $this->CacheFileExists($minified))
        {
            return $this->GetCacheUrl().Manager::$filePrependValue.$this->GetName().".parsed.min.".$this->extension;
        }

        return false;
    }

    /**
     * Get Full path for cached version of file
     *
     * @name GetCachedFilePath
     * @access public
     * @param Bool  $minified look for minified version
     * @return Mixed
     */
    public function GetCachedFilePath($minified = false)
    {
        if ($minified === false && $this->CacheFileExists($minified))
        {
            return $this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.".$this->extension;
        }
        else if ($minified === true && $this->CacheFileExists($minified))
        {
            return $this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.min.".$this->extension;
        }

        return false;
    }

    /**
     * Check of cache versions exists
     *
     * @name CacheFileExists
     * @access protected
     * @param Bool  $minified check for minified version
     * @return Bool
     */
    protected function CacheFileExists($minified = false)
    {
        $Parsed = $this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.".$this->extension;
        $Parsed_minified = $this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.min.".$this->extension;

        if ($minified === false)
        {
            if (!file_exists($Parsed))
            {
                $this->_failure(array("details" => "Could not find file at \"{$Parsed}\""));
                return false;
            }

            if (!is_readable($Parsed))
            {
                $this->_failure(array("details" => "Could not read asset file at \"{$Parsed}\""));
                return false;
            }
        }
        else
        {
            if (!file_exists($Parsed_minified))
            {
                $this->_failure(array("details" => "Could not find file at \"{$Parsed_minified}\""));
                return false;
            }

            if (!is_readable($Parsed_minified))
            {
                $this->_failure(array("details" => "Could not read asset file at \"{$Parsed_minified}\""));
                return false;
            }
        }

        return true;
    }

    /**
     * Create Cached versions of asset
     *
     * @name CreateCache
     * @access protected
     * @return Bool
     */
    protected function CreateCache()
    {
        if ($this->CanBeCached() === false)
        {
            return null;
        }

        $_createParsed_Cache = false;
        $_createParsed_min_Cache = false;

        $modified = $this->GetDateModified($this->IsDev());

        $parsed = $this->GetCachedFilePath(false);
        $parsed_min = $this->GetCachedFilePath(true);

        if ($parsed !== false)
        {
            $parsed_modified = $this->GetCachedDateModified($parsed);
            if ($parsed_modified instanceof DateTime)
            {
                if ($modified > $parsed_modified)
                {
                    $_createParsed_Cache = true;
                }
            }
            else
            {
                $_createParsed_Cache = true;
            }
        }
        else
        {
            $_createParsed_Cache = true;
        }

        if ($parsed_min !== false)
        {
            $parsed_modified = $this->GetCachedDateModified($parsed_min);
            if ($parsed_modified instanceof DateTime)
            {
                if ($modified > $parsed_modified)
                {
                    $_createParsed_min_Cache = true;
                }
            }
            else
            {
                $_createParsed_min_Cache = true;
            }
        }
        else
        {
            $_createParsed_min_Cache = true;
        }

        // If we do not have to create any cache files.
        if ($_createParsed_Cache === false && $_createParsed_min_Cache === false)
        {
            return true;
        }

        if ($this->prod_file_path !== false && $this->prod_file_path !== null)
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

        $contents = $this->Parse($contents);

        if ($_createParsed_min_Cache === true)
        {
            // If we successfully got the file's contents
            $minified = $this->Minify($contents);

            $min_fopen = fopen($this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.min.".$this->extension, "w");

            if ($min_fopen === false)
            {
                return false;
            }
            fwrite($min_fopen, $minified."\n");
            fclose($min_fopen);
            chmod($this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.min.".$this->extension, 0644);
        }

        if ($_createParsed_Cache === true)
        {
$comment = <<<EOD
/*
|--------------------------------------------------------------------------
| {$this->GetName()}
|--------------------------------------------------------------------------
| Last Modified : {$this->GetDateModified()->format("Y m d")}
*/
EOD;
            $parsed_fopen = fopen($this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.".$this->extension, "w");

            if ($parsed_fopen === false)
            {
                return false;
            }
            fwrite($parsed_fopen, $comment.$contents."\n");
            fclose($parsed_fopen);
            chmod($this->GetCachePath().Manager::$filePrependValue.$this->GetName().".parsed.".$this->extension, 0644);
        }
        return true;
    }


    /**
     * Get Contents for use
     *
     * @name GetContents
     * @access public
     * @return String  asset file contents
     */
    public function GetContents()
    {
        if ($this->CanBeCached())
        {
            return $this->GetCachedContents();
        }
        else
        {
            return $this->_GetContents();
        }
    }

    /**
     * Get Contents of Cached Asset
     *
     * Attempts to return contents of cached equivalent of file.
     * If unable, returns normal content;
     *
     * @name GetCachedContents
     * @access protected
     * @return String
     */
    protected function GetCachedContents()
    {
        $cached = $this->CreateCache();

        if ($cached === true)
        {
            $minify = (!$this->IsDev() && $this->minify);

            $path = $this->GetCachedFilePath($minify);

            if ($path === false)
            {
                return $this->_GetContents();
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
                    return $this->_GetContents();
                }
            }
        }
    }

    /**
     * Get Asset File Contents
     *
     * @name _GetContents
     * @access private
     * @return String;
     */
    private function _GetContents()
    {
        if ($this->prod_file_path !== false && $this->prod_file_path !== null)
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
            if (substr($ref, 0, 2) === "//")
            {
                $ref = "http:".$ref;
            }
            $ch = curl_init($ref);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 5
            ));
            $contents = curl_exec($ch);
//            $info = curl_getinfo($ch);
//            $error = curl_error($ch);
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

        $contents = $this->Parse($contents);

        return $contents;
    }

    /**
     * Error Handling
     *
     * @name _failure
     * @access protected
     * @return Bool  False
     */
    protected function _failure(Array $args = array())
    {
        if ($this->IsCI())
        {
            log_message("error", "Asset Manager: \"{$args['details']}\"");
            return false;
        }
        else
        {
            $callback = $this->GetErrorCallback();
            if ($this->callback !== null && is_callable($callback))
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
    * IsUrl
    * Checks if the provided string is a URL. Allows for port, path and query string validations.
    * This should probably be moved into a helper file, but I hate to Add a whole new file for
    * one little 2-line function.
    * @access   public
    * @param    $string string to be checked
    * @return   boolean
    */
    public static function IsUrl($string)
    {
        $pattern = '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@';
        return preg_match($pattern, $string);
    }

    // These methods must be defined in the child concrete classes
    abstract protected function GetFilePath($file);
    abstract protected function GetFileUrl($file);
    abstract protected function Parse($data);
    abstract protected function Minify($data);

    abstract public function GetOutput();
    abstract public function GetAssetPath();
    abstract public function GetAssetUrl();
}
