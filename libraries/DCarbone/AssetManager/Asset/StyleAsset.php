<?php namespace DCarbone\AssetManager\Asset;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

use DCarbone\AssetManager\Config\AssetManagerConfig;

/**
 * Class StyleAsset
 * @package DCarbone\AssetManager\Asset
 */
class StyleAsset extends AbstractAsset implements IAsset
{
    /**
     * Type of media this CSS file is for
     * @var string
     */
    public $media;

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
     * Get Asset Path for specific asset
     *
     * @return string
     */
    public function get_asset_path()
    {
        return $config['style_path'];
    }

    /**
     * Get Asset Url for specific asset
     *
     * @return string  asset url
     */
    public function get_asset_url()
    {
        return $config['style_url'];
    }

    /**
     * Minify Asset Data
     *
     * @param string  $data file contents
     * @return string  minified file contents
     */
    public function minify($data)
    {
        return \CssMin::minify($data);
    }

    /**
     * @return array
     */
    public function get_brackets()
    {
        return AssetManagerConfig::$style_brackets;
    }

    /**
     * @param string $data
     * @return mixed|string
     */
    public function parse_asset_file($data)
    {
        return parent::parse_asset_file($data)."\n";
    }

    /**
     * @return string
     */
    public function get_file_extension()
    {
        return AssetManagerConfig::$style_file_extension;
    }
}