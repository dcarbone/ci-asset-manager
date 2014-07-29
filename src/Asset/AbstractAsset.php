<?php namespace DCarbone\AssetManager\Asset;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

use DCarbone\AssetManager\Config\AssetManagerConfig;

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
    public $process_brackets = true;
    /** @var string */
    public $file = null;
    /** @var string */
    public $cached_file = null;
    /** @var string */
    public $cached_file_min = null;
    /** @var bool */
    public $minify_able = true;
    /** @var string */
    public $asset_name = null;
    /** @var string */
    public $source_file_path = null;
    /** @var string */
    public $source_file_url = null;
    /** @var string */
    public $file_name = null;
    /** @var array */
    public $requires = array();
    /** @var bool */
    public $file_is_remote = false;

    /** @var \DateTime */
    public $date_modified;

    /** @var array */
    protected $observers = array();

    /**
     * Constructor
     *
     * @param array $asset_params
     * @param \DCarbone\AssetManager\Config\AssetManagerConfig $config
     */
    public function __construct(array $asset_params, AssetManagerConfig $config)
    {
        $this->config = $config;

        foreach($asset_params as $k=>$v)
        {
            // Set some class params
            switch($k)
            {
                // Parse groups later on
                case 'groups' :
                    break;

                case 'requires' :
                    if (is_string($v) && ($v = trim($v)) !== '')
                        $this->$k = array($v);
                    else if (is_array($v))
                        $this->$k = $v;
                    else
                        $this->$k = array();

                    break;

                default :
                    if (property_exists($this, $k))
                        $this->$k = $v;
            }
        }

        if ($this->file === null || $this->file === '')
        {
            $this->source_file_path = false;
            $this->source_file_url = false;
            $this->_failure(array('details' => __CLASS__.'::__construct - Undefined "$file" value seen!'));
            $this->valid = false;
        }
        else if (!$this->asset_file_exists($this->file))
        {
            $this->_failure(array('details' => __CLASS__.'::__construct - Asset file "'.$this->file.'" not found!'));
            $this->valid = false;
        }
        else
        {
            $this->valid = true;

            // Set up path and URL properties
            if (preg_match("#^(http://|https://|//)#i", $this->file))
            {
                $this->source_file_url = $this->source_file_path = $this->file;
                $this->file_is_remote = true;
            }
            else
            {
                $this->source_file_url = $this->get_asset_url().$this->file;
                $this->source_file_path = $this->get_asset_path().$this->file;
            }

            // If no file name is defined, get it.
            if (!isset($this->file_name) || $this->file_name === null)
            {
                preg_match('/[^\\|\/]+$/', $this->file, $match);
                if (count($match) > 0)
                    $this->file_name = $match[0];
            }

            // Do a bit of parsing on group value
            if (isset($asset_params['groups']))
                $groups = $asset_params['groups'];
            else
                $groups = array();

            if (is_string($groups) && $groups = trim($groups) !== '')
                $groups = array($groups);

            $this->groups = array_unique($groups);

            if ($this->source_file_path === null || $this->source_file_path === false)
                $this->date_modified = false;
            else if ($this->file_is_remote === false && is_string($this->source_file_path))
                $this->date_modified = new \DateTime('@'.(string)filemtime($this->source_file_path), AssetManagerConfig::$DateTimeZone);
            else
                $this->date_modified = new \DateTime('0:00:00 January 1, 1970 UTC');

            if (function_exists('log_message'))
                log_message('debug', __CLASS__.' "'.$this->get_asset_name().'" initialized');
        }
    }

    /**
     * Determines if this asset utilizes the brackets system
     *
     * @return bool
     */
    public function can_process_brackets()
    {
        return $this->process_brackets;
    }

    /**
     * Get Name of current asset
     *
     * @return string  name of file
     */
    public function get_asset_name()
    {
        if ($this->asset_name === null || $this->asset_name === '')
            $this->asset_name = $this->get_file_name();

        return $this->asset_name;
    }

    /**
     * @return string
     */
    public function get_file_name()
    {
        return $this->file_name;
    }

    /**
     * Get Full URL to file
     *
     * @return string  asset url
     */
    public function get_source_file_url()
    {
        return $this->source_file_url;
    }


    /**
     * Get full file path for asset
     *
     * @return string  asset path
     */
    public function get_source_file_path()
    {
        return $this->source_file_path;
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
        return new \DateTime('@'.filemtime($path), AssetManagerConfig::$DateTimeZone);
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
        if (is_string($groups) && ($groups = trim($groups)) !== '' && !$this->in_group($groups))
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

        $this->notify();

        return $this;
    }

    /**
     * Get Assets required by this asset
     *
     * @return array
     */
    public function get_requires()
    {
        return $this->requires;
    }

    /**
     * @return string
     */
    public function get_file_src()
    {
        if ($this->can_process_brackets())
        {
            $minify = (!$this->config->is_dev() && $this->minify_able);
            $this->create_cache();
            $url = $this->get_cached_file_url($minify);

            if ($url !== false)
                return $url;
        }

        return $this->source_file_url;
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
        $cache_url = $this->config->get_cache_url();
        $ext = $this->get_file_extension();
        $name = $this->get_asset_name();

        if ($minified === false && $this->cached_file_exists($minified))
            return $cache_url.AssetManagerConfig::$file_prepend_value.$name.'.parsed.'.$ext;

        if ($minified === true && $this->cached_file_exists($minified))
            return $cache_url.AssetManagerConfig::$file_prepend_value.$name.'.parsed.min.'.$ext;

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
        $cache_path = $this->config->get_cache_path();
        $ext = $this->get_file_extension();
        $name = $this->get_asset_name();

        if ($minified === false && $this->cached_file_exists($minified))
            return $cache_path.AssetManagerConfig::$file_prepend_value.$name.'.parsed.'.$ext;

        if ($minified === true && $this->cached_file_exists($minified))
            return $cache_path.AssetManagerConfig::$file_prepend_value.$name.'.parsed.min.'.$ext;

        return false;
    }

    /**
     * Check of cache versions exists
     *
     * @param bool  $minified check for minified version
     * @return bool
     */
    public function cached_file_exists($minified = false)
    {
        $cache_path = $this->config->get_cache_path();
        $name = $this->get_asset_name();
        $ext = $this->get_file_extension();

        $parsed = $cache_path.AssetManagerConfig::$file_prepend_value.$name.'.parsed.'.$ext;
        $parsed_minified = $cache_path.AssetManagerConfig::$file_prepend_value.$name.'.parsed.min.'.$ext;

        if ($minified === false)
        {
            if (!file_exists($parsed))
            {
                $this->_failure(array('details' => 'Could not find file at "'.$parsed.'"'));
                return false;
            }

            if (!is_readable($parsed))
            {
                $this->_failure(array('details' => 'Could not read asset file at "'.$parsed.'"'));
                return false;
            }
        }
        else
        {
            if (!file_exists($parsed_minified))
            {
                $this->_failure(array('details' => 'Could not find file at "'.$parsed_minified.'"'));
                return false;
            }

            if (!is_readable($parsed_minified))
            {
                $this->_failure(array('details' => 'Could not read asset file at "'.$parsed_minified.'"'));
                return false;
            }
        }

        return true;
    }

    public function localize_remote()
    {
        var_dump($this->source_file_url);
        $file = fopen($this->config->get_cache_path().'localized.'.$this->file_name, 'w+');
        $ch = curl_init($this->source_file_url);
        curl_setopt_array($ch, array(
            CURLOPT_FILE => $file,
            CURLOPT_CONNECTTIMEOUT => 5
        ));
        $resp = curl_exec($ch);
        curl_close($ch);

        fclose($file);

        var_dump($resp);exit;
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

        $this->config->call_error_callback($args);

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


    /*
     *
     *
     * Observer methods
     *
     *
     *
     */


    /**
     * (PHP 5 >= 5.1.0)
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     *
     * @param \SplObserver $observer the SplObserver to attach.
     * @return void
     */
    public function attach(\SplObserver $observer)
    {
        if (!in_array($observer, $this->observers, true))
            $this->observers[] = $observer;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     *
     * @param \SplObserver $observer the SplObserver to detach.
     * @return void
     */
    public function detach(\SplObserver $observer)
    {
        $idx = array_search($observer, $this->observers, true);
        if ($idx !== false)
            unset($this->observers[$idx]);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     *
     * @return void
     */
    public function notify()
    {
        foreach($this->observers as $observer)
        {
            /** @var \SplObserver $observer */
            $observer->update($this);
        }
    }
}