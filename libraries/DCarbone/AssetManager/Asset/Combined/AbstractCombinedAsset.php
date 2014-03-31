<?php namespace DCarbone\AssetManager\Asset\Combined;

use DCarbone\AssetManager\Asset\AbstractAsset;

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
    protected $date_modified;

    /** @var string */
    protected $name;

    /** @var string */
    protected $media;

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
     * @return \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset
     */
    public static function init_new(array $assets, $combined_name)
    {
        /** @var \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset $instance */
        $instance = new static;

        $config = \AssetManager::get_config();

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
        $instance->date_modified = \DateTime::createFromFormat('U', filemtime($combined_file), \AssetManager::$DateTimeZone);

        return $instance;
    }

    /**
     * Unlike init_new, this constructor expects the full path to an existing combination file
     *
     * @param string $file
     * @return \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset
     */
    public static function init_existing($file)
    {
        /** @var \DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset $instance */
        $instance = new static;

        if (!file_exists($file))
        {
            static::_failure(array('details' => 'Could not find file at "'.$file.'"'));
            return false;
        }

        if (!is_writable($file))
        {
            static::_failure(array('details' => 'File at "'.$file.'" is not writable'));
            return false;
        }

        $instance->date_modified = \DateTime::createFromFormat('U', filemtime($file), \AssetManager::$DateTimeZone);
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
    protected static function _failure(array $args = array())
    {
        if (function_exists('log_message'))
            log_message('error', 'Asset Manager: "'.$args['details'].'"');

        $callback = static::get_error_callback();
        if (is_callable($callback))
            return $callback($args);

        return false;
    }

    /**
     * Get Error Callback Function
     *
     * @return \Closure
     */
    protected static function get_error_callback()
    {
        return ((isset($config['error_callback'])) ? $config['error_callback'] : null);
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
    public function get_date_modified()
    {
        return $this->date_modified;
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
        return $this->date_modified->format('Ymd');
    }
}