<?php namespace DCarbone\AssetManager\Asset\Combined;

/**
 * Class CombinedScriptAsset
 * @package DCarbone\AssetManager\Asset\Combined
 */
class CombinedScriptAsset extends AbstractCombinedAsset
{
    /**
     * @return string
     */
    protected static function get_file_extension()
    {
        return \AssetManager::$script_file_extension;
    }

    /**
     * Get <script /> tag Output for this file
     *
     * @return string  html Output
     */
    public function get_output()
    {
        $output = "<script type='text/javascript' language='javascript'";
        $output .= " src='".str_ireplace(array("http:", "https:"), "", $this->get_file_src());
        $output .= '?v='.$this->get_file_version()."'></script>";

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