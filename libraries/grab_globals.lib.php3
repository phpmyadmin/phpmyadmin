<?php
/* $Id$ */


/**
 * This library grabs the names and values of the variables sent or posted to a
 * script in the '$HTTP_*_VARS' arrays and sets simple globals variables from
 * them
 */
if (!defined('__LIB_GRAB_GLOBALS__')) {
    define('__LIB_GRAB_GLOBALS__', 1);

    if (!empty($HTTP_GET_VARS)) {
        extract($HTTP_GET_VARS);
    } // end if

    if (!empty($HTTP_POST_VARS)) {
        extract($HTTP_POST_VARS);
    } // end if

    if (!empty($HTTP_POST_FILES)) {
        while (list($name, $value) = each($HTTP_POST_FILES)) {
            $$name = $value['tmp_name'];
        }
    } // end if

} // end __LIB_GRAB_GLOBALS__
?>