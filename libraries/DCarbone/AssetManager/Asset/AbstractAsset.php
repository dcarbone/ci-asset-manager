<?php namespace DCarbone\AssetManager\Asset;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/**
 * Class AbstractAsset
 * @package DCarbone\AssetManager\Asset
 */
abstract class AbstractAsset implements IAsset
{
    /** @var bool */
    public $valid = true;

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
    /** @var array */
    public $requires = array();
    /** @var bool */
    public $file_is_remote = false;

    /** @var \DateTime */
    public $date_modified;

    /**
     * Constructor
     *
     * @param array $asset_params
     * @internal param array $args
     */
    public function __construct(array $asset_params)
    {
        $this->parse_args($asset_params);
        $this->valid = $this->validate();

        if ($this->file_path === null || $this->file_path === false)
            $this->date_modified = false;
        else if ($this->file_is_remote === false && is_string($this->file_path))
            $this->date_modified = new \DateTime('@'.(string)filemtime($this->file_path), \AssetManager::$DateTimeZone);
        else
            $this->date_modified = new \DateTime('0:00:00 January 1, 1970 UTC');
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

        if ($this->asset_file_exists($this->file))
        {
            $this->file_path = $this->get_file_path();
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
     * @return string
     */
    public function get_file_name()
    {
        if ($this->file_name === null)
        {
            $this->file_name = '';
            if ($this->file !== '')
            {
                preg_match('/[a-zA-Z0-9\.\-_\s]+$/', $this->file, $match);

                if (count($match) > 0)
                    $this->file_name = reset($match);
            }
        }
        return $this->file_name;
    }

    /**
     * Get Full URL to file
     *
     * @return string  asset url
     */
    public function get_file_url()
    {
        if ($this->file_url === null)
        {
            if (preg_match("#^(http://|https://|//)#i", $this->file))
                $this->file_url = $this->file;
            else
                $this->file_url = $this->get_asset_url().$this->file;
        }
        return $this->file_url;
    }


    /**
     * Get full file path for asset
     *
     * @return string  asset path
     */
    public function get_file_path()
    {
        if ($this->file_path === null)
        {
            if (preg_match("#^(http://|https://|//)#i", $this->file))
                $this->file_path = $this->file;
            else
                $this->file_path = $this->get_asset_path().$this->file;
        }

        return $this->file_path;
    }

    /**
     * @return \DateTime
     */
    public function get_file_date_modified()
    {
        return $this->date_modified;
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
        return new \DateTime('@'.filemtime($path), \AssetManager::$DateTimeZone);
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
     * @param array|string $groups
     * @return IAsset
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

        return $this;
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

        if (is_array($this->requires))
            return $this->requires;

        return array();
    }

    /**
     * @return string
     */
    public function get_file_src()
    {
        if ($this->can_be_cached())
        {
            $minify = (!\AssetManager::is_dev() && $this->minify_able);
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
        return $this->date_modified->format('Ymd');
    }

    /**
     * Determine if File Exists
     *
     * @param string  $file file name
     * @return bool
     */
    public function asset_file_exists($file)
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
        $config = \AssetManager::get_config();

        if ($minified === false && $this->cache_file_exists($minified))
            return $config['cache_url'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->get_file_extension();


        if ($minified === true && $this->cache_file_exists($minified))
            return $config['cache_url'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->get_file_extension();

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
        $config = \AssetManager::get_config();

        if ($minified === false && $this->cache_file_exists($minified))
            return $config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->get_file_extension();

        if ($minified === true && $this->cache_file_exists($minified))
            return $config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->get_file_extension();

        return false;
    }

    /**
     * Check of cache versions exists
     *
     * @param bool  $minified check for minified version
     * @return bool
     */
    public function cache_file_exists($minified = false)
    {
        $config = \AssetManager::get_config();

        $parsed = $config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->get_file_extension();
        $parsed_minified = $config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->get_file_extension();

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
    public function create_cache()
    {
        if ($this->can_be_cached() === false)
            return false;

        $config = \AssetManager::get_config();

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

        if($remote || $config['force_curl'])
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

            $min_fopen = fopen($config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->get_file_extension(), 'w');

            if ($min_fopen === false)
                return false;

            fwrite($min_fopen, $minified."\n");
            fclose($min_fopen);
            chmod($config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->get_file_extension(), 0644);
        }

        if ($_create_parsed_cache === true)
        {
            $parsed_fopen = @fopen($config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->get_file_extension(), 'w');

            if ($parsed_fopen === false)
                return false;

            fwrite($parsed_fopen, $contents."\n");
            fclose($parsed_fopen);
            chmod($config['cache_path'].\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->get_file_extension(), 0644);
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
            return $this->_get_cached_asset_contents();

        return $this->_get_asset_contents();
    }

    /**
     * Parse Asset File and replace key markers
     *
     * @param string  $data file contents
     * @return string  parsed file contents
     */
    public function parse_asset_file($data)
    {
        foreach($this->get_brackets() as $key=>$value)
        {
            if (is_scalar($value))
                str_replace($key, $value, $data);
            else if (is_callable($value))
                $data = $value($key, $data, $this);
        }

        return $data;
    }

    /**
     * Get Contents of Cached Asset
     *
     * Attempts to return contents of cached equivalent of file.
     * If unable, returns normal content;
     *
     * @return string
     */
    protected function _get_cached_asset_contents()
    {
        $cached = $this->create_cache();

        if ($cached === true)
        {
            $minify = (!\AssetManager::is_dev() && $this->minify_able);

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
    protected function _get_asset_contents()
    {
        $ref = $this->file_path;

        $config = \AssetManager::get_config();

        if($this->file_is_remote || $config['force_curl'])
        {
            if (substr($ref, 0, 2) === '//')
                $ref = 'http:'.$ref;

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
            $this->_failure(array('details' => 'Could not get file contents for "'.$ref.'"'));
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

        $config = \AssetManager::get_config();
        if (isset($config['error_callback']) && is_callable($config['error_callback']))
            return $config['error_callback']($args);

        return false;
    }

    /**
     * @param    $string string to be checked
     * @return   boolean
     */
    public static function is_url($string)
    {
        return filter_var($string, FILTER_VALIDATE_URL);
    }
}