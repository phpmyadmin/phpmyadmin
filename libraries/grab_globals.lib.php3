<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This library grabs the names and values of the variables sent or posted to a
 * script in the '$HTTP_*_VARS' / $_* arrays and sets simple globals variables
 * from them. It does the same work for the $PHP_SELF variable.
 *
 * loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
 */
if (!defined('PMA_GRAB_GLOBALS_INCLUDED')) {
    define('PMA_GRAB_GLOBALS_INCLUDED', 1);

    function PMA_gpc_extract($array, &$target) {
        if (!is_array($array)) {
            return FALSE;
        }
        $is_magic_quotes = get_magic_quotes_gpc();
        reset($array);
        while (list($key, $value) = each($array)) {
            if (is_array($value)) {
                PMA_gpc_extract($value, $target[$key]);
            } else if ($is_magic_quotes) {
                $target[$key] = stripslashes($value);
            } else {
                $target[$key] = $value;
            }
        }
        reset($array);
        return TRUE;
    }

    if (!empty($_GET)) {
        PMA_gpc_extract($_GET, $GLOBALS);
    } else if (!empty($HTTP_GET_VARS)) {
        PMA_gpc_extract($HTTP_GET_VARS, $GLOBALS);
    } // end if

    if (!empty($_POST)) {
        PMA_gpc_extract($_POST, $GLOBALS);
    } else if (!empty($HTTP_POST_VARS)) {
        PMA_gpc_extract($HTTP_POST_VARS, $GLOBALS);
    } // end if

    if (!empty($_FILES)) {
        while (list($name, $value) = each($_FILES)) {
            $$name = $value['tmp_name'];
            ${$name . '_name'} = $value['name'];
        }
    } else if (!empty($HTTP_POST_FILES)) {
        while (list($name, $value) = each($HTTP_POST_FILES)) {
            $$name = $value['tmp_name'];
            ${$name . '_name'} = $value['name'];
        }
    } // end if

    if (!empty($_SERVER)) {
        if (isset($_SERVER['PHP_SELF'])) {
            $PHP_SELF = $_SERVER['PHP_SELF'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $HTTP_ACCEPT_LANGUAGE = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $HTTP_AUTHORIZATION = $_SERVER['HTTP_AUTHORIZATION'];
        }
    } else if (!empty($HTTP_SERVER_VARS)) {
        if (isset($HTTP_SERVER_VARS['PHP_SELF'])) {
            $PHP_SELF = $HTTP_SERVER_VARS['PHP_SELF'];
        }
        if (isset($HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE'])) {
            $HTTP_ACCEPT_LANGUAGE = $HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE'];
        }
        if (isset($HTTP_SERVER_VARS['HTTP_AUTHORIZATION'])) {
            $HTTP_AUTHORIZATION = $HTTP_SERVER_VARS['HTTP_AUTHORIZATION'];
        }
    } // end if

    // Security fix: disallow accessing serious server files via "?goto="
    if (isset($goto) && strpos(' ' . $goto, '/') > 0 && substr($goto, 0, 2) != './') {
        unset($goto);
    } // end if

} // $__PMA_GRAB_GLOBALS_LIB__
?>
