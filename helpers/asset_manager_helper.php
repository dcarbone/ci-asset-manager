<?php

// -- View Helpers ------------------------------------------------------------------------------------

/**
 * @return \asset_manager
 */
function &get_asset_manager()
{
    static $CI = false;
    if (!$CI)
    {
        $CI = &get_instance();
        if (!isset($CI->asset_manager))
            throw new \RuntimeException('ci-asset-manager - Function "get_asset_manager" requires that the asset_manager library be loaded prior to any calls being made.  Please check configuration / autoload parameters.');
    }

    return $CI->asset_manager;
}

/**
 * @param string $content
 * @param array $html_attributes
 * @return string
 */
function javascript_tag($content = null, array $html_attributes = array())
{
    return get_asset_manager()->javascript_tag($content, $html_attributes);
}

/**
 * @param string $content
 * @param array $html_attributes
 * @return string
 */
function stylesheet_tag($content = null, array $html_attributes = array())
{
    return get_asset_manager()->stylesheet_tag($content, $html_attributes);
}
/**
 * @param string $file
 * @param mixed $additional [optional]
 * @return string
 */
function include_javascript($file, $additional = null)
{
    return call_user_func_array(array(get_asset_manager(), 'include_javascript'), func_get_args());
}

/**
 * @param string $file
 * @param mixed $additional [optional]
 * @return string
 */
function include_stylesheet($file, $additional = null)
{
    return call_user_func_array(array(get_asset_manager(), 'include_stylesheet'), func_get_args());
}