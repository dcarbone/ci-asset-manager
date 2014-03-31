<?php namespace DCarbone\AssetManager\Asset;

/**
 * Class LessAsset
 * @package DCarbone\AssetManager\Asset
 */
class LessAsset extends AbstractAsset implements IAsset
{
    /**
     * Type of media this CSS file is for
     * @var string
     */
    public $media;

    /** @var \Less_Parser */
    protected static $LessParser;

    /**
     * Constructor
     *
     * @param array $asset_params
     */
    public function __construct(array $asset_params)
    {
        parent::__construct($asset_params);

        $config = \AssetManager::get_config();

        if (!isset(static::$LessParser))
        {
            $less_args = array(
                'compress' => $config['minify_styles'],
            );

            static::$LessParser = new \Less_Parser($less_args);
        }
    }

    /**
     * @return mixed
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
    public function get_asset_path()
    {
        $config = \AssetManager::get_config();
        return $config['less_path'];
    }

    /**
     * @return string
     */
    public function get_asset_url()
    {
        $config = \AssetManager::get_config();
        return $config['less_url'];
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function parse_asset_file($data)
    {
        $replace_keys = array_keys(\AssetManager::$style_brackets);

        $replace_values = array_values(\AssetManager::$style_brackets);

        $css = str_replace($replace_keys, $replace_values, $data)."\n";

        static::$LessParser->parse($css);

        return $css;
    }

    /**
     * Create Cached versions of asset
     *
     * @return bool
     */
    public function create_cache()
    {
        return false;
        if ($this->can_be_cached() === false)
            return false;

        $config = \AssetManager::get_config();
        
        $_create_parsed_cache = false;
        $_create_parsed_min_cache = false;

        $modified = $this->get_file_date_modified();

        $parsed = $this->get_cached_file_path(false);
        $parsed_min = $this->get_cached_file_path(true);

        if ($parsed !== false)
        {
            $parsed_modified = $this->get_cached_date_modified($parsed);
            if ($parsed_modified instanceof \DateTime && $modified > $parsed_modified)
                $_create_parsed_cache = true;
        }
        else
        {
            $_create_parsed_cache = true;
        }

        if ($parsed_min !== false)
        {
            $parsed_modified = $this->get_cached_date_modified($parsed_min);
            if ($parsed_modified instanceof \DateTime && $modified > $parsed_modified)
                $_create_parsed_min_cache = true;
        }
        else
        {
            $_create_parsed_min_cache = true;
        }

        // If we do not have to create any cache files.
        if ($_create_parsed_cache === false && $_create_parsed_min_cache === false)
            return true;

        $ref = $this->file_path;
        $remote = $this->file_is_remote;

        if($remote || $config['force_curl'])
        {
            $ch = curl_init($ref);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 5
            ));
            $contents = curl_exec($ch);
            curl_close($ch);
        }
        else
        {
            $contents = file_get_contents($ref);
        }

        // If there was some issue getting the contents of the file
        if (!is_string($contents) || $contents === false)
        {
            $this->_failure(array('details' => 'Could not get file contents for "'.$ref.'"'));
            return false;
        }

        $contents = $this->parse_asset_file($contents);

        if ($_create_parsed_min_cache === true)
        {
            // If we successfully got the file's contents
            $minified = $this->minify($contents);

            $min_fopen = fopen($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension, 'w');

            if ($min_fopen === false)
                return false;

            fwrite($min_fopen, $minified."\n");
            fclose($min_fopen);
            chmod($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.min.'.$this->extension, 0644);
        }

        if ($_create_parsed_cache === true)
        {
            $parsed_fopen = @fopen($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension, 'w');

            if ($parsed_fopen === false)
                return false;

            fwrite($parsed_fopen, $contents."\n");
            fclose($parsed_fopen);
            chmod($this->get_cache_path().\AssetManager::$file_prepend_value.$this->get_name().'.parsed.'.$this->extension, 0644);
        }
        return true;
    }

    /**
     * Get Contents for use
     *
     * @return string  asset file contents
     */
    public function get_asset_contents()
    {
        if ($this->can_be_cached())
            return $this->_get_cached_asset_contents();

        return $this->_get_asset_contents();
    }

    /**
     * Get Contents of Cached Asset
     *
     * Attempts to return contents of cached equivalent of file.
     * If unable, returns normal content;
     *
     * @return string
     */
    protected function _get_cached_asset_contents()
    {
        $cached = $this->create_cache();

        if ($cached === true)
        {
            $minify = (!$this->is_dev() && $this->minify_able);

            $path = $this->get_cached_file_path($minify);

            if ($path === false)
                return $this->_get_asset_contents();

            $contents = file_get_contents($path);
            if (is_string($contents))
                return $contents;

            return $this->_get_asset_contents();
        }

        return null;
    }

    /**
     * Get Asset File Contents
     *
     * @return string;
     */
    protected function _get_asset_contents()
    {
        $ref = $this->file_path;

        $config = \AssetManager::get_config();

        if($this->file_is_remote || $config['force_curl'])
        {
            if (substr($ref, 0, 2) === '//')
            {
                $ref = 'http:'.$ref;
            }
            $ch = curl_init($ref);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 5
            ));
            $contents = curl_exec($ch);
//            $info = curl_getinfo($ch);
//            $error = curl_error($ch);
            curl_close($ch);
        }
        else
        {
            $contents = file_get_contents($ref);
        }

        // If there was some issue getting the contents of the file
        if (!is_string($contents) || $contents === false)
        {
            $this->_failure(array('details' => 'Could not get file contents for "'.$ref.'"'));
            return false;
        }

        $contents = $this->parse_asset_file($contents);

        return $contents;
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function minify($data)
    {
        // TODO: Implement minify() method.
    }

    /**
     * @return string
     */
    public function get_extension()
    {
        // TODO: Implement get_extension() method.
    }
}