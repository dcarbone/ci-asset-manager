<?php namespace DCarbone\AssetManager\Collection;

use DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset;

/**
 * Class LessAssetCollection
 * @package DCarbone\AssetManager\Collection
 */
class LessAssetCollection extends AbstractAssetCollection
{
    /**
     * @return array
     */
    protected function load_existing_cached_assets()
    {
        $config = \AssetManager::get_config();
        foreach(glob($config['cache_path'].'*.'.\AssetManager::$less_file_extension) as $less_cache_file)
        {
            $this->set(basename($less_cache_file), AbstractCombinedAsset::init_existing($less_cache_file));
        }
    }

    /**
     * @return string
     */
    public function generate_output()
    {
        // TODO: Implement generate_output() method.
    }

    /**
     * Combine Asset Files
     *
     * This method actually combines the assets passed to it and saves it to a file
     *
     * @return bool
     */
    protected function build_combined_assets()
    {
        // TODO: Implement build_combined_assets() method.
    }
}