<?php namespace DCarbone\AssetManager\Asset;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

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
    public function generate_output();

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
     * @return string
     */
    public function get_file_extension();

    /**
     * @param string $data
     * @return mixed
     */
    public function minify($data);
}