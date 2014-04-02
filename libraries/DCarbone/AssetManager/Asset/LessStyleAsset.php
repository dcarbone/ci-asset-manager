<?php namespace DCarbone\AssetManager\Asset;

/**
 * Class LessStyleAsset
 * @package DCarbone\AssetManager\Asset
 */
class LessStyleAsset extends StyleAsset implements IAsset
{
    /**
     * @return string
     */
    public function get_asset_path()
    {
        $config = \AssetManager::get_config();
        return $config['less_style_path'];
    }

    /**
     * @return string
     */
    public function get_asset_url()
    {
        $config = \AssetManager::get_config();
        return $config['less_style_url'];
    }

    /**
     * Create Cached versions of asset
     *
     * @return bool
     */
    public function create_cache()
    {
        return false;
    }

    /**
     * Get Contents for use
     *
     * @return string  asset file contents
     */
    public function get_asset_contents()
    {
        return $this->_get_asset_contents();
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function minify($data)
    {
        return false;
    }

    /**
     * @return string
     */
    public function get_file_extension()
    {
        return \AssetManager::$less_file_extension;
    }
}