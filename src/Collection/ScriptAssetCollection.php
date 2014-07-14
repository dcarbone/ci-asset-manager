<?php namespace DCarbone\AssetManager\Collection;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

use DCarbone\AssetManager\Asset\Combined\CombinedScriptAsset;
use DCarbone\AssetManager\Config\AssetManagerConfig;

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
                echo $this[$asset_name]->generate_output()."\n";
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

        $combined_asset_name = md5(AssetManagerConfig::$file_prepend_value.implode('', $asset_names));
        $cache_file = $this->cache_file_exists($combined_asset_name);

        if ($cache_file === false || ($cache_file !== false && $newest_file > $this[$combined_asset_name]->get_file_date_modified()))
        {
            $combine_files = array();
            foreach($this->output_assets as $asset_name)
            {
                $combine_files[] = &$this[$asset_name];
            }

            $combined_asset = CombinedScriptAsset::init_new($combine_files, $combined_asset_name, $this->config);

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
        $cache_path = $this->config->get_cache_path();
        foreach(glob($cache_path.'*.'.AssetManagerConfig::$script_file_extension) as $script_cache_file)
        {
            $this->set(basename($script_cache_file, '.'.AssetManagerConfig::$script_file_extension), CombinedScriptAsset::init_existing($script_cache_file, $this->config));
        }
    }
}