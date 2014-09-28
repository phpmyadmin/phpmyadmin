<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/** String Functions for phpMyAdmin
 *
 * If mb_* functions don't exist, we create the ones we need and they'll use the
 * standard string functions.
 *
 * All mb_* functions created by pMA should behave as mb_* functions.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (@function_exists('mb_strlen')) {
    include_once 'libraries/stringMb.lib.php';
} else {
    include_once 'libraries/stringNative.lib.php';
}
