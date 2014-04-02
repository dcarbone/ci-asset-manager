<?php namespace DCarbone\AssetManager\Asset\Combined;

/**
 * Class CombinedLessStyleAsset
 * @package DCarbone\AssetManager\Asset\Combined
 */
class CombinedLessStyleAsset extends CombinedStyleAsset
{
    /**
     * @param string $combined_name
     * @param string $contents
     * @return bool|CombinedLessStyleAsset
     */
    public static function init_from_string($combined_name, $contents)
    {
        /** @var CombinedLessStyleAsset $instance */
        $instance = new static;

        $config = \AssetManager::get_config();

        $combined_file = $config['cache_path'].$combined_name.'.'.static::get_file_extension();

        $fp = fopen($combined_file, 'w');

        if ($fp === false)
            return false;

        fwrite($fp, $contents);
        fclose($fp);
        chmod($combined_file, 0644);

        $instance->file_name = $combined_name.'.'.static::get_file_extension();
        $instance->file_path = $combined_file;
        $instance->name = $combined_name;
        $instance->file_date_modified = \DateTime::createFromFormat('U', time(), \AssetManager::$DateTimeZone);

        return $instance;
    }

    /**
     * @return string
     */
    protected static function get_file_extension()
    {
        return \AssetManager::$style_file_extension;
    }

    /**
     * @return string
     */
    public function generate_output()
    {
        $output = "<link rel='stylesheet' type='text/css'";
        $output .= " href='".str_ireplace(array("http:", "https:"), "", $this->get_file_src());
        $output .= '?v='.$this->get_file_version()."' media='{$this->media}' />";

        return $output;
    }

    /**
     * @return string
     */
    public function get_file_src()
    {
        $config = \AssetManager::get_config();
        return $config['cache_url'].$this->file_name;
    }
}