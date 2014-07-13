<?php namespace DCarbone\AssetManager\Asset\Combined;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/**
 * Class CombinedLessStyleAsset
 * @package DCarbone\AssetManager\Asset\Combined
 */
class CombinedLessStyleAsset extends CombinedStyleAsset
{
    /**
     * @param string $combined_name
     * @param string $contents
     * @return bool|CombinedLessStyleAsset
     */
    public static function init_from_string($combined_name, $contents)
    {
        /** @var CombinedLessStyleAsset $instance */
        $instance = new static;

        $config = \asset_manager::get_config();

        $combined_file = $config['cache_path'].$combined_name.'.'.static::get_file_extension();

        $fp = fopen($combined_file, 'w');

        if ($fp === false)
            return false;

        fwrite($fp, $contents);
        fclose($fp);
        chmod($combined_file, 0644);

        $instance->file_name = $combined_name.'.'.static::get_file_extension();
        $instance->file_path = $combined_file;
        $instance->name = $combined_name;
        $instance->file_date_modified = \DateTime::createFromFormat('U', time(), \asset_manager::$DateTimeZone);

        return $instance;
    }

    /**
     * @return string
     */
    protected static function get_file_extension()
    {
        return \asset_manager::$style_file_extension;
    }

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
     * @return string
     */
    public function get_file_src()
    {
        $config = \asset_manager::get_config();
        return $config['cache_url'].$this->file_name;
    }
}