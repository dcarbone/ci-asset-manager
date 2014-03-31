<?php namespace DCarbone\AssetManager\Asset;

/**
 * Interface IAsset
 * @package DCarbone\AssetManager\Asset
 */
interface IAsset
{
    /**
     * @return bool
     */
    public function can_be_cached();

    /**
     * @return string
     */
    public function get_name();

    /**
     * @return string
     */
    public function get_file_name();

    /**
     * @return \DateTime
     */
    public function get_file_date_modified();

    /**
     * @param string $path
     * @return \DateTime
     */
    public function get_cached_date_modified($path);

    /**
     * @return array
     */
    public function get_groups();

    /**
     * @param string $group
     * @return bool
     */
    public function in_group($group);

    /**
     * @param string|array $groups
     * @return IAsset
     */
    public function add_groups($groups);

    /**
     * @return array
     */
    public function get_requires();

    /**
     * @return string
     */
    public function get_file_src();

    /**
     * @return string
     */
    public function get_file_version();

    /**
     * @param string $file
     * @return bool
     */
    public function asset_file_exists($file);

    /**
     * @param bool $minified
     * @return string|bool
     */
    public function get_cached_file_url($minified = false);

    /**
     * @param bool $minified
     * @return string|bool
     */
    public function get_cached_file_path($minified = false);

    /**
     * @param bool $minified
     * @return bool
     */
    public function cache_file_exists($minified = false);

    /**
     * @return bool
     */
    public function create_cache();

    /**
     * @return string
     */
    public function get_asset_contents();

    /**
     * @return mixed
     */
    public function get_output();

    /**
     * @return string
     */
    public function get_asset_path();

    /**
     * @return string
     */
    public function get_asset_url();

    /**
     * @return string
     */
    public function get_file_path();

    /**
     * @return string
     */
    public function get_file_url();

    /**
     * @param string $data
     * @return mixed
     */
    public function parse_asset_file($data);

    /**
     * @param string $data
     * @return mixed
     */
    public function minify($data);
}