<?php namespace DCarbone\AssetManager\Collection;

use DCarbone\AssetManager\Asset\AbstractAsset;
use DCarbone\AssetManager\Asset\Combined\AbstractCombinedAsset;
use DCarbone\AssetManager\Asset\Combined\CombinedScriptAsset;

/**
 * Class ScriptAssetCollection
 * @package DCarbone\AssetManager\Collection
 */
class ScriptAssetCollection extends AbstractAssetCollection
{
    /**
     * @return string
     */
    public function generate_output()
    {
       $this->prepare_output();

        ob_start();
        foreach($this->output_assets as $asset_name)
        {
            if (isset($this[$asset_name]))
                echo $this[$asset_name]->generate_output();
        }
        return ob_get_clean();
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
        // Load existing cache files
        $this->load_existing_cached_assets();

        $newest_file = $this->get_newest_date_modified($this->output_assets);
        $asset_names = array_keys($this->output_assets);

        $combined_asset_name = md5(\AssetManager::$file_prepend_value.implode('', $asset_names));
        $cache_file = $this->cache_file_exists($combined_asset_name);

        if ($cache_file === false || ($cache_file !== false && $newest_file > $this[$combined_asset_name]->get_file_date_modified()))
        {
            $combine_files = array();
            foreach($this->output_assets as $asset_name)
            {
                $combine_files[] = &$this[$asset_name];
            }

            $combined_asset = CombinedScriptAsset::init_new($combine_files, $combined_asset_name);

            if ($combined_asset === false)
                return false;

            $this->set($combined_asset_name, $combined_asset);
        }

        $this->output_assets = array($combined_asset_name);

        return true;
    }

    /**
     * @return array
     */
    protected function load_existing_cached_assets()
    {
        $config = \AssetManager::get_config();
        foreach(glob($config['cache_path'].'*.'.\AssetManager::$script_file_extension) as $script_cache_file)
        {
            $this->set(basename($script_cache_file, '.'.\AssetManager::$script_file_extension), CombinedScriptAsset::init_existing($script_cache_file));
        }
    }
}