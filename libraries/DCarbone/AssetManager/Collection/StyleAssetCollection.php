<?php namespace DCarbone\AssetManager\Collection;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

use DCarbone\AssetManager\Asset\Combined\CombinedStyleAsset;
use DCarbone\AssetManager\Asset\StyleAsset;

/**
 * Class StyleAssetCollection
 * @package DCarbone\AssetManager\Collection
 */
class StyleAssetCollection extends AbstractAssetCollection
{
    /** @var array */
    protected $style_medias = array();

    /**
     * @return mixed|void
     */
    public function build_output_sequence()
    {
        parent::build_output_sequence();

        foreach($this->output_assets as $asset_name)
        {
            /** @var StyleAsset $style */
            $style = $this[$asset_name];
            if (!array_key_exists($style->media, $this->style_medias))
                $this->style_medias[$style->media] = array();

            $this->style_medias[$style->media][] = $asset_name;
        }
    }

    /**
     * @return string
     */
    public function generate_output()
    {
        $this->prepare_output();

        ob_start();
        foreach(\AssetManager::$style_media_output_order as $media_type)
        {
            if (isset($this->style_medias[$media_type]))
            {
                foreach($this->style_medias[$media_type] as $asset_name)
                {
                    if (isset($this[$asset_name]))
                        echo $this[$asset_name]->generate_output()."\n";
                }
            }

            unset($this->style_medias[$media_type]);
        }

        // Afterwards, echo out everything else
        foreach($this->style_medias as $media_type=>$assets)
        {
            foreach($this->style_medias[$media_type] as $asset_name)
            {
                if (isset($this[$asset_name]))
                    echo $this[$asset_name]->generate_output()."\n";
            }
        }

        return ob_get_clean();
    }

    /**
     * @return bool
     */
    protected function build_combined_assets()
    {
        $output_assets = array();
        $style_medias = array();

        // Loop through each media type and ensure we have the proper combined asset
        foreach($this->style_medias as $media=>$asset_names)
        {
            $newest_file = $this->get_newest_date_modified($asset_names);

            $combined_asset_name = md5(\AssetManager::$file_prepend_value.implode('', $asset_names));
            $combined_file_name = $combined_asset_name.'.'.\AssetManager::$style_file_extension;
            $cache_file = $this->load_existing_cached_asset($combined_file_name, $media);

            if ($cache_file === false || ($cache_file !== false && $newest_file > $this[$combined_asset_name]->get_file_date_modified()))
            {
                $combine_files = array();
                foreach($asset_names as $asset_name)
                {
                    $combine_files[] = &$this[$asset_name];
                }

                /** @var CombinedStyleAsset $combined_asset */
                $combined_asset = CombinedStyleAsset::init_new($combine_files, $combined_asset_name);

                if ($combined_asset === false)
                    continue;

                $combined_asset->set_media($media);

                $this->set($combined_asset_name, $combined_asset);
            }

            $style_medias[$media] = array($combined_asset_name);
            $output_assets[] = $combined_asset_name;
        }

        $this->style_medias = $style_medias;
        $this->output_assets = $output_assets;

        return true;
    }

    /**
     * @return void
     */
    protected function load_existing_cached_assets() {}

    /**
     * @param string $file_name
     * @param string $media
     * @return bool
     */
    protected function load_existing_cached_asset($file_name, $media)
    {
        $config = \AssetManager::get_config();
        if (file_exists($config['cache_path'].$file_name))
        {
            /** @var CombinedStyleAsset $asset */
            $asset = CombinedStyleAsset::init_existing($config['cache_path'].$file_name);
            $asset->set_media($media);
            $this->set($asset->get_name(), $asset);
            return true;
        }

        return false;
    }
}