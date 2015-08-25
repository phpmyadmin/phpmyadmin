<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/** String Functions for phpMyAdmin
 *
 * If mb_* functions don't exist, we create the ones we need and they'll use the
 * standard string functions.
 *
 * All mb_* functions created by PMA should behave as mb_* functions.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (!defined('MULTIBYTES_ON')) {
    define('MULTIBYTES_ON', true);
    define('MULTIBYTES_OFF', false);
}

if (@function_exists('mb_strlen')) {
    if (!defined('MULTIBYTES_STATUS')) {
        define('MULTIBYTES_STATUS', MULTIBYTES_ON);
    }

    include_once 'libraries/stringMb.lib.php';
} else {
    if (!defined('MULTIBYTES_STATUS')) {
        define('MULTIBYTES_STATUS', MULTIBYTES_OFF);
    }

    include_once 'libraries/stringNative.lib.php';
}
