<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This library grabs the names and values of the variables sent or posted to a
 * script in the '$HTTP_*_VARS' arrays and sets simple globals variables from
 * them. It does the same work for the $PHP_SELF variable.
 *
 * loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
 */
if (!defined('PMA_GRAB_GLOBALS_INCLUDED')) {
    define('PMA_GRAB_GLOBALS_INCLUDED', 1);

    if (!empty($_GET)) {
        extract($_GET, EXTR_OVERWRITE);
    } else if (!empty($HTTP_GET_VARS)) {
        extract($HTTP_GET_VARS, EXTR_OVERWRITE);
    } // end if

    if (!empty($_POST)) {
        extract($_POST, EXTR_OVERWRITE);
    } else if (!empty($HTTP_POST_VARS)) {
        extract($HTTP_POST_VARS, EXTR_OVERWRITE);
    } // end if

    if (!empty($_FILES)) {
        while (list($name, $value) = each($_FILES)) {
            $$name = $value['tmp_name'];
        }
    } else if (!empty($HTTP_POST_FILES)) {
        while (list($name, $value) = each($HTTP_POST_FILES)) {
            $$name = $value['tmp_name'];
        }
    } // end if

    if (!empty($_SERVER) && isset($_SERVER['PHP_SELF'])) {
        $PHP_SELF = $_SERVER['PHP_SELF'];
    } else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['PHP_SELF'])) {
        $PHP_SELF = $HTTP_SERVER_VARS['PHP_SELF'];
    } // end if

    // Securety fix: disallow accessing serious server files via "?goto="
    if (isset($goto) && strpos(' ' . $goto, '/') > 0 && substr($goto, 0, 2) != './') {
        unset($goto);
    } // end if

    // Strip slahes from $db / $table values
    if (get_magic_quotes_gpc()) {
        if (isset($db)) {
            $db = stripslashes($db);
        }
        if (isset($table)) {
            $table = stripslashes($table);
        }
    }

} // $__PMA_GRAB_GLOBALS_LIB__
?>
