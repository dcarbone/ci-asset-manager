<?php namespace DCarbone\AssetManager\Asset\Combined;

/**
 * Class CombinedStyleAsset
 * @package DCarbone\AssetManager\Asset\Combined
 */
class CombinedStyleAsset extends AbstractCombinedAsset
{
    /**
     * @return string
     */
    protected static function get_file_extension()
    {
        return \AssetManager::$style_file_extension;
    }

    /**
     * @param string $media
     * @return void
     */
    public function set_media($media)
    {
        $this->media = $media;
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
    public function get_media()
    {
        return $this->media;
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