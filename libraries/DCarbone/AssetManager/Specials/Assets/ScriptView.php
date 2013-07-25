<?php namespace DCarbone\AssetManager\Specials\Assets;

use \DateTime;
use \DateTimeZone;

use \DCarbone\AssetManager\Manager;

use \CssMin;
use \JSMin;
use \DCarbone\AssetManager\Generics\Asset;

/*
    ScriptView Asset Class for AssetManager Library
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

class ScriptView extends Asset
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
        $this->extension = Manager::$scriptFileExtension;
    }

    /**
     * File Validation
     *
     * @override
     * @name Validate
     * @access protected
     * @return Boolean
     */
    protected function Validate()
    {
        if(!is_string($this->dev_file) || (is_string($this->dev_file) && $this->dev_file === ""))
        {
            $this->_failure(array("details" => "View filename must be a non-empty String!"));
            return false;
        }

        if ($this->FileExists($this->dev_file))
        {
            $this->file_path = $this->GetFilePath($this->dev_file);
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
     * @Override
     * @access public
     * @return Mixed  Filename or False
     *
     * This differs from the standard GetFileName
     * in that it utilizes $this->dev_file rather than
     * $this->dev_file or $this->prod_file, as views
     * do not have a dev / prod difference
     */
    public function GetFileName($dev = true)
    {
        if ($this->dev_file === null)
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
     * @name FileExists
     * @access protected
     * @param String  $file file name
     * @param String  $type asset type
     * @return Bool
     */
    protected function FileExists($file, $type = "dev")
    {
        // Views should never be remote resources
        if (preg_match("#^(http|//)#i", $file))
        {
            $this->_failure(array("details" => "Script views cannot be remote files!"));
            return false;
        }

        $filepath = $this->GetAssetPath().$file;
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
     * @name GetAssetPath
     * @access public
     * @return String
     */
    public function GetAssetPath()
    {
        return $this->_config['script_view_path'];
    }

    /**
     * Get Full Filepath for asset
     *
     * @name GetFilePath
     * @access protected
     * @param String  $file file name
     * @return String  file path
     */
    protected function GetFilePath($file = "")
    {
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
        return $this->_config['script_view_url'];
    }

    /**
     * Get <script src=""> attribute for asset
     *
     * @override
     * @name GetSrc
     * @access public
     * @param Boolean  $dev is envirnment development
     * @return String  src string for file
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
        return $this->GetFileUrl($this->dev_file);
    }

    /**
     * Get File Url
     *
     * @name GetFileUrl
     * @access protected
     * @param String  $file filename
     * @return String  full url with file
     */
    protected function GetFileUrl($file = "")
    {
        return $filepath = $this->GetAssetUrl().$file;
    }

    /**
     * Get datemodified version
     *
     * @override
     * @name GetVer
     * @access public
     * @return String  version appendage
     */
    public function GetVer()
    {
        $file = $this->file_path;

        return "?ver=".date("Ymd", filemtime($file));
    }

    public function GetOutput()
    {
        $Output = "<script type='text/javascript' language='javascript'";
        $Output .= " src='".$this->GetSrc($this->IsDev()).$this->GetVer()."'";
        $Output .= "></script>";
        return $Output;
    }


    public function GetDateModified($dev = true)
    {
        if ($this->dev_last_modified === null)
        {
            $zone = new DateTimeZone("UTC");

            $this->dev_last_modified = new DateTime("@".(string)filemtime($this->file_path), $zone);
        }
        return $this->dev_last_modified;
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
        return JSMin::minify($data);
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
        $replace_keys = array_keys(Manager::$scriptBrackets);

        $replace_values = array_values(Manager::$scriptBrackets);

        return str_replace($replace_keys, $replace_values, $data);
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

        if ($_createParsed_Cache === false && $_createParsed_min_Cache === false)
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

}
