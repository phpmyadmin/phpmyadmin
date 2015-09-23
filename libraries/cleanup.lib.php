<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for cleanup of user input.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Removes all variables from request except whitelisted ones.
 *
 * @param string &$whitelist list of variables to allow
 *
 * @return void
 * @access public
 */
function PMA_removeRequestVars(&$whitelist)
{
    // do not check only $_REQUEST because it could have been overwritten
    // and use type casting because the variables could have become
    // strings
    $keys = array_keys(
        array_merge((array)$_REQUEST, (array)$_GET, (array)$_POST, (array)$_COOKIE)
    );

    foreach ($keys as $key) {
        if (! in_array($key, $whitelist)) {
            unset($_REQUEST[$key], $_GET[$key], $_POST[$key], $GLOBALS[$key]);
            continue;
        }

        // allowed stuff could be compromised so escape it
        // we require it to be a string
        if (isset($_REQUEST[$key]) && ! is_string($_REQUEST[$key])) {
            unset($_REQUEST[$key]);
        }
        if (isset($_POST[$key]) && ! is_string($_POST[$key])) {
            unset($_POST[$key]);
        }
        if (isset($_COOKIE[$key]) && ! is_string($_COOKIE[$key])) {
            unset($_COOKIE[$key]);
        }
        if (isset($_GET[$key]) && ! is_string($_GET[$key])) {
            unset($_GET[$key]);
        }
    }
}
