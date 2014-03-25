<?php namespace DCarbone\AssetManager\Asset;
    /*
        AbstractAsset Class for AssetManager CodeIgniter Library
        Copyright (C) 2012-2014  Daniel Carbone (https://github.com/dcarbone)

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
 * Class AbstractAsset
 * @package DCarbone\AssetManager\Asset
 */
abstract class AbstractAsset
{
    /** @var bool */
    public $valid = true;
    /** @var null */
    protected static $date_time_zone = null;
    /** @var null */
    public $extension = null;
    /** @var array */
    public $groups = array();
    /** @var bool */
    public $cache = true;
    /** @var string */
    public $file = null;
    /** @var string */
    public $cached_file = null;
    /** @var string */
    public $cached_file_min = null;
    /** @var bool */
    public $minify_able = true;
    /** @var string */
    public $name = null;
    /** @var string */
    public $file_path = null;
    /** @var string */
    public $file_url = null;
    /** @var string */
    public $file_name = null;
    /** @var \DateTime */
    public $file_last_modified = null;
    /** @var array */
    public $requires = array();
    /** @var bool */
    public $file_is_remote = false;
    /** @var array */
    protected $config = array();

    /**
     * Constructor
     */
    public function __construct(array $config, array $args)
    {
        if (!(static::$date_time_zone) instanceof \DateTimeZone)
            static::$date_time_zone = new \DateTimeZone('UTC');

        $this->config = $config;
        $this->parse_args($args);
        $this->valid = $this->validate();
    }

    /**
     * Parse Arguments
     *
     * @param array  $args arguments
     * @return void
     */
    protected function parse_args(array $args = array())
    {
        foreach($args as $k=>$v)
        {
            switch($k)
            {
                case 'group' :
                    $k = 'groups';
                case 'groups' :
                    if (is_string($v))
                        $v = array($v);

                default : $this->$k = $v;
            }
        }
    }

    /**
     * Input Validation
     *
     * @return bool
     */
    protected function validate()
    {
        if ($this->file === '')
        {
            $this->file_path = false;
            $this->file_url = false;
            $this->_failure(array('details' => 'You have tried to Add an asset to Asset Manager with undefined "$file" values!'));
            return false;
        }

        if ($this->file_exists($this->file))
        {
            $this->file_path = $this->get_file_path($this->file);
            $this->file_url = $this->get_file_url($this->file);
            return true;
        }

        $this->_failure(array('details' => 'You have specified an invalid file. FileName: "'.$this->file.'"'));
        return false;
    }

    /**
     * Determines if this asset is locally cacheable
     *
     * @return bool
     */
    public function can_be_cached()
    {
        return $this->cache;
    }

    /**
     * Determines if environment is 'development'
     *
     * @return bool  dev or not
     */
    public function is_dev()
    {
        return $this->config['dev'];
    }

    /**
     * Get Base URL from config
     *
     * @return string  base url
     */
    public function get_base_url()
    {
        return $this->config['base_url'];
    }

    /**
     * Get Base File path
     *
     * @return string  base filepath
     */
    public function get_base_path()
    {
        return $this->config['base_path'];
    }

    /**
     * Get Base Asset URL
     *
     * @return string  asset url
     */
    public function get_base_asset_url()
    {
        return $this->config['asset_url'];
    }

    /**
     * Get Base Asset File Path
     *
     * @return string  asset file path
     */
    public function get_base_asset_path()
    {
        return $this->config['asset_path'];
    }

    /**
     * Get Cache File Path
     *
     * @return string  cache file path
     */
    public function get_cache_path()
    {
        return $this->config['cache_path'];
    }

    /**
     * Get Cache URL
     *
     * @return string  cache url
     */
    public function get_cache_url()
    {
        return $this->config['cache_url'];
    }

    /**
     * Get Error Callback Function
     *
     * If this is not being run within CodeIgniter, the user can pass in a custom function that is
     * executed on error.
     *
     * @return mixed
     */
    public function get_error_callback()
    {
        return ((isset($this->config['error_callback'])) ? $this->config['error_callback'] : null);
    }

    /**
     * Get Name of current asset
     *
     * Wrapper method for GetFileName
     *
     * @return string  name of file
     */
    public function get_name()
    {
        if ($this->name === null || $this->name === '')
            $this->name = $this->get_file_name();

        return $this->name;
    }

    /**
     * @return mixed|null|string
     */
    public function get_file_name()
    {
        if ($this->file_name === null)
        {
            $this->file_name = '';
            if ($this->file !== '')
            {
                $ex = explode('/', $this->file);
                $this->file_name = end($ex);
            }
        }
        return $this->file_name;
    }

    /**
     * @return \DateTime
     */
    public function get_file_date_modified()
    {
        if ($this->file_last_modified === null)
        {
            if ($this->file_path === null || $this->file_path === false)
                $this->file_last_modified = false;
            else if ($this->file_is_remote === false && is_string($this->file_path))
                $this->file_last_modified = new \DateTime('@'.(string)filemtime($this->file_path), static::$date_time_zone);
            else
                $this->file_last_modified = new \DateTime('0:00:00 January 1, 1970 UTC');
        }

        return $this->file_last_modified;
    }

    /**
     * Get Date Modified for Cached Asset File
     *
     * This differs from above in that there is no logic.  Find the path before executing.
     *
     * @param string  $path cached filepath
     * @return \DateTime
     */
    public function get_cached_date_modified($path)
    {
        return new \DateTime('@'.filemtime($path), static::$date_time_zone);
    }

    /**
     * Get Groups of this asset
     *
     * @return array
     */
    public function get_groups()
    {
        return $this->groups;
    }

    /**
     * Is Asset In Group
     *
     * @param string
     * @return bool
     */
    public function in_group($group)
    {
        return in_array($group, $this->groups, true);
    }

    /**
     * Add Asset to group
     *
     * @param string|array
     * @return void
     */
    public function add_groups($groups)
    {
        if (is_string($groups) && $groups !== '' && !$this->in_group($groups))
        {
            $this->groups[] = $groups;
        }
        else if (is_array($groups) && count($groups) > 0)
        {
            foreach($groups as $group)
            {
                $this->add_groups($group);
            }
        }
    }

    /**
     * Get Assets required by this asset
     *
     * @return array
     */
    public function get_requires()
    {
        if (is_string($this->requires))
            return array($this->requires);
        else if (is_array($this->requires))
            return $this->requires;
        else
            return array();
    }

    /**
     * @return string
     */
    public function get_file_src()
    {
        if ($this->can_be_cached())
        {
            $minify = (!$this->is_dev() && $this->minify_able);
            $this->create_cache();
            $url = $this->get_cached_file_url($minify);

            if ($url !== false)
                return $url;
        }

        return $this->file_url;
    }

    /**
     * Get File Version
     *
     * @return string
     */
    public function get_file_version()
    {
        $file = $this->file_path;
        if ($file === null ) $file = $this->file_path;

        if (preg_match('#^(http://|https://|//)#i', $file))
            return '?ver=19700101';

        return '?ver='.date('Ymd', filemtime($file));
    }

    /**
     * Determine if File Exists
     *
     * @param string  $file file name
     * @return bool
     */
    protected function file_exists($file)
    {
        if (preg_match('#^(http://|https://|//)#i', $file))
            return $this->file_is_remote = true;

        $file_path = $this->get_asset_path().$file;

        if (!file_exists($file_path))
        {
            $this->_failure(array('details' => 'Could not find file at "'.$file_path.'"'));
            return false;
        }

        if (!is_readable($file_path))
        {
            $this->_failure(array('details' => 'Could not read asset file at "'.$file_path.'"'));
            return false;
        }

        return true;
    }

    /**
     * Get fill url for cached file
     *
     * @param bool $minified get url for minified version
     * @return mixed
     */
    public function get_cached_file_url($minified = false)
    {
        if ($minified === false && $this->cache_file_exists($minified))
            return $this->get_cache_url().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension;


        if ($minified === true && $this->cache_file_exists($minified))
            return $this->get_cache_url().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension;

        return false;
    }

    /**
     * Get Full path for cached version of file
     *
     * @param bool  $minified look for minified version
     * @return mixed
     */
    public function get_cached_file_path($minified = false)
    {
        if ($minified === false && $this->cache_file_exists($minified))
            return $this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension;

        if ($minified === true && $this->cache_file_exists($minified))
            return $this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension;

        return false;
    }

    /**
     * Check of cache versions exists
     *
     * @param bool  $minified check for minified version
     * @return bool
     */
    protected function cache_file_exists($minified = false)
    {
        $parsed = $this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension;
        $parsed_minified = $this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension;

        if ($minified === false)
        {
            if (!file_exists($parsed))
            {
                $this->_failure(array('details' => 'Could not find file at \'{$Parsed}\''));
                return false;
            }

            if (!is_readable($parsed))
            {
                $this->_failure(array('details' => 'Could not read asset file at \'{$Parsed}\''));
                return false;
            }
        }
        else
        {
            if (!file_exists($parsed_minified))
            {
                $this->_failure(array('details' => 'Could not find file at \'{$Parsed_minified}\''));
                return false;
            }

            if (!is_readable($parsed_minified))
            {
                $this->_failure(array('details' => 'Could not read asset file at \'{$Parsed_minified}\''));
                return false;
            }
        }

        return true;
    }

    /**
     * Create Cached versions of asset
     *
     * @return bool
     */
    protected function create_cache()
    {
        if ($this->can_be_cached() === false)
            return false;

        $_create_parsed_cache = false;
        $_create_parsed_min_cache = false;

        $modified = $this->get_file_date_modified();

        $parsed = $this->get_cached_file_path(false);
        $parsed_min = $this->get_cached_file_path(true);

        if ($parsed !== false)
        {
            $parsed_modified = $this->get_cached_date_modified($parsed);
            if ($parsed_modified instanceof \DateTime && $modified > $parsed_modified)
                $_create_parsed_cache = true;
        }
        else
        {
            $_create_parsed_cache = true;
        }

        if ($parsed_min !== false)
        {
            $parsed_modified = $this->get_cached_date_modified($parsed_min);
            if ($parsed_modified instanceof \DateTime && $modified > $parsed_modified)
                $_create_parsed_min_cache = true;
        }
        else
        {
            $_create_parsed_min_cache = true;
        }

        // If we do not have to create any cache files.
        if ($_create_parsed_cache === false && $_create_parsed_min_cache === false)
            return true;

        $ref = $this->file_path;
        $remote = $this->file_is_remote;

        if($remote || $this->config['force_curl'])
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
            $this->_failure(array('details' => 'Could not get file contents for \'{$ref}\''));
            return false;
        }

        $contents = $this->parse_asset_file($contents);

        if ($_create_parsed_min_cache === true)
        {
            // If we successfully got the file's contents
            $minified = $this->minify($contents);

            $min_fopen = fopen($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension, 'w');

            if ($min_fopen === false)
                return false;

            fwrite($min_fopen, $minified."\n");
            fclose($min_fopen);
            chmod($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension, 0644);
        }

        if ($_create_parsed_cache === true)
        {
            $comment = <<<COMMENT
/*
|--------------------------------------------------------------------------
| {$this->get_name()}
|--------------------------------------------------------------------------
| Last Modified : {$this->get_file_date_modified()->format('Y m d')}
*/
COMMENT;
            $parsed_fopen = @fopen($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension, 'w');

            if ($parsed_fopen === false)
                return false;

            fwrite($parsed_fopen, $comment.$contents."\n");
            fclose($parsed_fopen);
            chmod($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension, 0644);
        }
        return true;
    }


    /**
     * Get Contents for use
     *
     * @return string  asset file contents
     */
    public function get_asset_contents()
    {
        if ($this->can_be_cached())
            return $this->get_cached_asset_contents();

        return $this->_get_asset_contents();
    }

    /**
     * Get Contents of Cached Asset
     *
     * Attempts to return contents of cached equivalent of file.
     * If unable, returns normal content;
     *
     * @return string
     */
    protected function get_cached_asset_contents()
    {
        $cached = $this->create_cache();

        if ($cached === true)
        {
            $minify = (!$this->is_dev() && $this->minify_able);

            $path = $this->get_cached_file_path($minify);

            if ($path === false)
                return $this->_get_asset_contents();

            $contents = file_get_contents($path);
            if (is_string($contents))
                return $contents;

            return $this->_get_asset_contents();
        }

        return null;
    }

    /**
     * Get Asset File Contents
     *
     * @return string;
     */
    private function _get_asset_contents()
    {
        $ref = $this->file_path;
        $remote = $this->file_is_remote;

        if($remote || $this->config['force_curl'])
        {
            if (substr($ref, 0, 2) === '//')
            {
                $ref = 'http:'.$ref;
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
            $this->_failure(array('details' => 'Could not get file contents for \'{$ref}\''));
            return false;
        }

        $contents = $this->parse_asset_file($contents);

        return $contents;
    }

    /**
     * Error Handling
     *
     * @param array $args
     * @return bool  False
     */
    protected function _failure(array $args = array())
    {
        if (function_exists('log_message'))
            log_message('error', 'Asset Manager: "'.$args['details'].'"');

        $callback = $this->get_error_callback();
        if (is_callable($callback))
            return $callback($args);

        return false;
    }

    /**
     * IsUrl
     * Checks if the provided string is a URL. Allows for port, path and query string validations.
     * This should probably be moved into a helper file, but I hate to Add a whole new file for
     * one little 2-line function.
     * @param    $string string to be checked
     * @return   boolean
     */
    public static function is_url($string)
    {
        $pattern = '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@';
        return preg_match($pattern, $string);
    }

    // These methods must be defined in the child concrete classes
    abstract protected function get_file_path($file);
    abstract protected function get_file_url($file);
    abstract protected function parse_asset_file($data);
    abstract protected function minify($data);

    abstract public function get_output();
    abstract public function get_asset_path();
    abstract public function get_asset_url();
}