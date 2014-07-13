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
        return $this->config->get_less_style_path();
    }

    /**
     * @return string
     */
    public function get_asset_url()
    {
        return $this->config->get_less_style_path();
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
        return AssetManagerConfig::$less_file_extension;
    }
}