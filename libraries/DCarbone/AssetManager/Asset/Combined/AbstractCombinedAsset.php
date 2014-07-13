<?php namespace DCarbone\AssetManager\Asset\Combined;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

use DCarbone\AssetManager\Asset\AbstractAsset;
use DCarbone\AssetManager\Config\AssetManagerConfig;

/**
 * Class AbstractCombinedAsset
 * @package DCarbone\AssetManager\Asset\Combined
 */
abstract class AbstractCombinedAsset
{
    /** @var string */
    protected $file_name;

    /** @var string */
    protected $file_path;

    /** @var \DateTime */
    protected $file_date_modified;

    /** @var string */
    protected $name;

    /** @var string */
    protected $media;

    /** @var \DCarbone\AssetManager\Config\AssetManagerConfig */
    protected $config;

    /**
     * Constructor
     */
    protected function __construct() {}

    /**
     * @return string
     */
    abstract public function generate_output();

    /**
     * @return string
     */
    abstract public function get_file_src();

    /**
     * @throws \Exception
     */
    protected static function get_file_extension()
    {
        throw new \Exception('You must override the base declaration of '.__METHOD__);
    }

    /**
     * $assets must be an array of AbstractAsset objects
     *
     * @param array $assets
     * @param string $combined_name
     * @param \DCarbone\AssetManager\Config\AssetManagerConfig $config
     * @return \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset
     */
    public static function init_new(array $assets, $combined_name, AssetManagerConfig &$config)
    {
        /** @var \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset $instance */
        $instance = new static;

        $instance->config = &$config;

        $contents = array();

        foreach($assets as $asset)
        {
            /** @var $asset AbstractAsset */
            $_contents = $asset->get_asset_contents();
            if ($_contents !== false)
                $contents[] = $_contents;
        }

        $combined_file = $config['cache_path'].$combined_name.'.'.static::get_file_extension();

        $fp = fopen($combined_file, "w");

        if ($fp === false)
            return false;

        foreach($contents as $t)
        {
            fwrite($fp, $t);
        }
        fclose($fp);
        chmod($combined_file, 0644);

        $instance->file_name = $combined_name.'.'.static::get_file_extension();
        $instance->file_path = $combined_file;
        $instance->name = $combined_name;
        $instance->file_date_modified = \DateTime::createFromFormat('U', time(), AssetManagerConfig::$DateTimeZone);

        return $instance;
    }

    /**
     * Unlike init_new, this constructor expects the full path to an existing combination file
     *
     * @param string $file
     * @param \DCarbone\AssetManager\Config\AssetManagerConfig $config
     * @return \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset
     */
    public static function init_existing($file, AssetManagerConfig &$config)
    {
        /** @var \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset $instance */
        $instance = new static;

        $instance->config = &$config;

        if (!file_exists($file))
        {
            $instance->_failure(array('details' => 'Could not find file at "'.$file.'"'));
            return false;
        }

        if (!is_writable($file))
        {
            $instance->_failure(array('details' => 'File at "'.$file.'" is not writable'));
            return false;
        }

        $instance->file_date_modified = \DateTime::createFromFormat('U', filemtime($file), AssetManagerConfig::$DateTimeZone);
        $instance->name = basename($file, '.'.static::get_file_extension());
        $instance->file_name = $instance->name.'.'.static::get_file_extension();
        $instance->file_path = $file;

        return $instance;
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
            log_message('error', 'AssetManager: "'.$args['details'].'"');

        $this->config->call_error_callback($args);

        return false;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function get_file_date_modified()
    {
        return $this->file_date_modified;
    }

    /**
     * @return string
     */
    public function get_file_name()
    {
        return $this->file_name;
    }

    /**
     * @return string
     */
    public function get_file_path()
    {
        return $this->file_path;
    }

    /**
     * @return string
     */
    public function get_file_version()
    {
        return $this->file_date_modified->format('Ymd');
    }
}