<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * DEFINES VARIABLES & CONSTANTS
 * Overview:
 *    PMA_VERSION              (string) - phpMyAdmin version string
 *    PMA_PHP_INT_VERSION      (int)    - eg: 30017 instead of 3.0.17 or
 *                                        40006 instead of 4.0.6RC3
 *    PMA_MYSQL_CLIENT_API     (int)    - the version number of the MySQL client
 *                                          API which php is built against.
 *    PMA_IS_WINDOWS           (bool)   - mark if phpMyAdmin running on windows
 *                                        server
 *    PMA_IS_GD2               (bool)   - true is GD2 is present
 */
// phpMyAdmin release
if (!defined('PMA_VERSION')) {
    define('PMA_VERSION', '2.5.0-rc2');
}

// php version
if (!defined('PMA_PHP_INT_VERSION')) {
    if (!ereg('([0-9]{1,2}).([0-9]{1,2}).([0-9]{1,2})', phpversion(), $match)) {
        $result = ereg('([0-9]{1,2}).([0-9]{1,2})', phpversion(), $match);
    }
    if (isset($match) && !empty($match[1])) {
        if (!isset($match[2])) {
            $match[2] = 0;
        }
        if (!isset($match[3])) {
            $match[3] = 0;
        }
        define('PMA_PHP_INT_VERSION', (int)sprintf('%d%02d%02d', $match[1], $match[2], $match[3]));
        unset($match);
    } else {
        define('PMA_PHP_INT_VERSION', 0);
    }
    define('PMA_PHP_STR_VERSION', phpversion());
}

// MySQL client API
if (!defined('PMA_MYSQL_CLIENT_API')) {
    if (function_exists('mysql_get_client_info')) {
        $client_api = mysql_get_client_info();
    } else {
        // for compatibility with php <= 4.0.5
        // expect the worst!
        $client_api = '3.21.0';
    }
    $client_api = explode('.', $client_api);
    define('PMA_MYSQL_CLIENT_API', (int)sprintf('%d%02d%02d', $client_api[0], $client_api[1], intval($client_api[2])));
    unset($client_api);
}

// Whether the os php is running on is windows or not
if (!defined('PMA_IS_WINDOWS')) {
    if (defined('PHP_OS') && eregi('win', PHP_OS)) {
        define('PMA_IS_WINDOWS', 1);
    } else {
        define('PMA_IS_WINDOWS', 0);
    }
}

// Whether GD2 is present
if (!defined('PMA_IS_GD2')) {
    if (function_exists("get_extension_funcs")) {
        $testGD = @get_extension_funcs("gd");
        if ($testGD && in_array("imagegd2",$testGD)) {
            define('PMA_IS_GD2', 1);
        } else {
            define('PMA_IS_GD2', 0);
        }
        unset($testGD);
    } else {
        define('PMA_IS_GD2', 0);
    }
}
// $__PMA_DEFINES_PHP_LIB__
?>
