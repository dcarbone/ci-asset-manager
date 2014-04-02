<?php namespace DCarbone\AssetManager\Collection;
use DCarbone\AssetManager\Asset\Combined\CombinedLessStyleAsset;
use DCarbone\AssetManager\Asset\LessStyleAsset;

/**
 * Class LessStyleAssetCollection
 * @package DCarbone\AssetManager\Collection
 */
class LessStyleAssetCollection extends StyleAssetCollection
{
    /** @var \Less_Parser */
    protected static $LessParser;

    /** @var array */
    protected $style_medias = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (!isset(static::$LessParser))
            static::$LessParser = new \Less_Parser();
    }

    /**
     * @return void
     */
    public function prepare_output()
    {
        $this->build_output_sequence();
        $this->build_combined_assets();
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
                static::$LessParser->Reset();

                foreach($asset_names as $asset_name)
                {
                    static::$LessParser->parse($this[$asset_name]->get_asset_contents());
                }

                $combined_asset = CombinedLessStyleAsset::init_from_string($combined_asset_name, static::$LessParser->getCss());

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
     * @param string $file_name
     * @param string $media
     * @return bool
     */
    protected function load_existing_cached_asset($file_name, $media)
    {
        $config = \AssetManager::get_config();
        if (file_exists($config['cache_path'].$file_name))
        {
            /** @var CombinedLessStyleAsset $asset */
            $asset = CombinedLessStyleAsset::init_existing($config['cache_path'].$file_name);
            $asset->set_media($media);
            $this->set($asset->get_name(), $asset);
            return true;
        }

        return false;
    }
}