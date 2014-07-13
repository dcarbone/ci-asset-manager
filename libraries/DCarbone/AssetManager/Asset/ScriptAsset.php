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
use JShrink\Minifier;

/**
 * Class ScriptAsset
 * @package DCarbone\AssetManager\Asset
 */
class ScriptAsset extends AbstractAsset implements IAsset
{
    /** @var array */
    protected $jshrink_options = array();

    /**
     * Constructor
     *
     * @param array $asset_params
     * @param \DCarbone\AssetManager\Config\AssetManagerConfig $config
     */
    public function __construct(array $asset_params, AssetManagerConfig $config)
    {
        parent::__construct($asset_params, $config);

        if (isset($asset_params['jshrink_options']) && is_array($asset_params['jshrink_options']))
            $this->jshrink_options = $asset_params['jshrink_options'];
    }

    /**
     * Get <script /> tag Output for this file
     *
     * @return string  html Output
     */
    public function generate_output()
    {
        $output = "<script type='text/javascript' language='javascript'";
        $output .= " src='".str_ireplace(array("http:", "https:"), "", $this->get_file_src());
        $output .= '?v='.$this->get_file_version()."'></script>";

        return $output;
    }

    /**
     * Determine if script file exists
     *
     * @param string  $file file path / Address
     * @return Bool
     */
    public function asset_file_exists($file)
    {
        if (preg_match("#^(http://|https://|//)#i", $file))
            return $this->file_is_remote = true;

        $file_path = $this->get_asset_path().$file;
        if (!file_exists($file_path))
        {
            $this->_failure(array("details" => "Could not find file at \"{$file_path}\""));
            return false;
        }

        return true;
    }

    /**
     * Get Asset Path for specific asset
     *
     * @return string
     */
    public function get_asset_path()
    {
        return $this->config->get_script_path();
    }

    /**
     * Get Asset Url for specific asset
     *
     * @return string  asset url
     */
    public function get_asset_url()
    {
        return $this->config->get_script_url();
    }

    /**
     * Minify Asset Data
     *
     * @param string  $data file contents
     * @return string  minified file contents
     */
    public function minify($data)
    {
        return Minifier::minify($data, $this->jshrink_options);
    }

    /**
     * @return array
     */
    public function get_brackets()
    {
        return AssetManagerConfig::$script_brackets;
    }

    /**
     * @param string $data
     * @return string
     */
    public function parse_asset_file($data)
    {
        return parent::parse_asset_file($data)."\n;";
    }

    /**
     * @return string
     */
    public function get_file_extension()
    {
        return AssetManagerConfig::$script_file_extension;
    }
}