<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Util file creation
 *
 * @package PhpMyAdmin
 */
namespace PMA\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Access to a multidimensional array by dot notation
 *
 * @param array        $array   List of values
 * @param string|array $path    Path to searched value
 * @param mixed        $default Default value
 *
 * @return mixed Searched value
 */
function get($array, $path, $default = null)
{
    if (is_string($path)) {
        $path = str_getcsv($path,'.','`');
    }

    $p = array_shift($path);
    while (isset($p)) {
        if (!isset($array[$p]) && !isset($array['`' . $p . '`'])) {
            return $default;
        }

        if (isset($array[$p])) {
            $array = $array[$p];
        }

        if (isset($array['`' . $p . '`'])) {
            $array = $array['`' . $p . '`'];
        }

        $p = array_shift($path);
    }
    return $array;
}
