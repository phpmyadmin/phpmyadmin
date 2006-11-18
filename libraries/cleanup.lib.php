<?php
/* $Id: common.lib.php 9531 2006-10-10 14:06:56Z nijel $ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Functions for cleanup of user input.
 */

/**
 * Removes all variables from request except whitelisted ones.
 *
 * @param string list of variables to allow
 * @return nothing
 * @access public
 * @author  Michal Cihar (michal@cihar.com)
 */
function PMA_remove_request_vars(&$whitelist) {
    // do not check only $_REQUEST because it could have been overwritten
    // and use type casting because the variables could have become
    // strings
    $keys = array_keys(array_merge((array)$_REQUEST, (array)$_GET, (array)$_POST, (array)$_COOKIE));

    foreach($keys as $key) {
        if (!in_array($key, $whitelist)) {
            unset($_REQUEST[$key], $_GET[$key], $_POST[$key], $GLOBALS[$key]);
        } else {
            // allowed stuff could be compromised so escape it
            // we require it to be a string
            if (isset($_REQUEST[$key]) && is_string($_REQUEST[$key])) {
                $_REQUEST[$key] = htmlspecialchars($_REQUEST[$key], ENT_QUOTES);
            } else {
                unset($_REQUEST[$key]);
            }
            if (isset($_POST[$key]) && is_string($_POST[$key])) {
                $_POST[$key] = htmlspecialchars($_POST[$key], ENT_QUOTES);
            } else {
                unset($_POST[$key]);
            }
            if (isset($_COOKIE[$key]) && is_string($_COOKIE[$key])) {
                $_COOKIE[$key] = htmlspecialchars($_COOKIE[$key], ENT_QUOTES);
            } else {
                unset($_COOKIE[$key]);
            }
            if (isset($_GET[$key]) && is_string($_GET[$key])) {
                $_GET[$key] = htmlspecialchars($_GET[$key], ENT_QUOTES);
            } else {
                unset($_GET[$key]);
            }
        }
    }
}
?>
