<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This library grabs the names and values of the variables sent or posted to a
 * script in the $_* arrays and sets simple globals variables from them. It does
 * the same work for the $PHP_SELF, $HTTP_ACCEPT_LANGUAGE and
 * $HTTP_AUTHORIZATION variables.
 *
 * loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
 */

// just to be sure there was no import (registering) before here
$variables_whitelist = array (
    'GLOBALS',
    '_SERVER',
    '_GET',
    '_POST',
    '_REQUEST',
    '_FILES',
    '_ENV',
    '_COOKIE',
);

foreach ( get_defined_vars() as $key => $value ) {
    if ( ! in_array( $key, $variables_whitelist )  ) {
        unset( $$key );
    }
}
unset( $key, $value );


// protect against older PHP versions' bug about GLOBALS overwrite
// (no need to translate this one :) )
// but what if script.php?GLOABLS[admin]=1&GLOBALS[_REQUEST]=1 ???
if ( isset( $_REQUEST['GLOBALS'] ) || isset( $_FILES['GLOBALS'] )
  || isset( $_SERVER['GLOBALS'] ) || isset( $_COOKIE['GLOBALS'] )
  || isset( $_ENV['GLOBALS'] ) ) {
    die( 'GLOBALS overwrite attempt' );
}

require_once './libraries/session.inc.php';

/**
 * @var array $import_blacklist variable names that should NEVER be imported
 *                              from superglobals
 */
$import_blacklist = array(
    '/^cfg$/',      // PMA configuration
    '/^GLOBALS$/',  // the global scope
    '/^str.*$/',    // PMA strings
    '/^_.*$/',      // PMA does not use variables starting with _ from extern
    '/^.*\s+.*$/',  // no whitespaces anywhere
    '/^[0-9]+.*$/', // numeric variable names
);

/**
 * copy values from one array to another, usally from a superglobal into $GLOBALS
 *
 * @uses    $GLOBALS['import_blacklist']
 * @uses    preg_replace()
 * @uses    array_keys()
 * @uses    array_unique()
 * @uses    get_magic_quotes_gpc()  to check wether stripslashes or not
 * @uses    stripslashes()
 * @param   array   $array      values from
 * @param   array   $target     values to
 * @param   boolean $sanitize   prevent importing key names in $import_blacklist
 */
function PMA_gpc_extract($array, &$target, $sanitize = TRUE) {
    if (!is_array($array)) {
        return FALSE;
    }

    $valid_variables = preg_replace( $GLOBALS['import_blacklist'], '',
        array_keys( $array ) );
    $valid_variables = array_unique( $valid_variables );

    $is_magic_quotes = get_magic_quotes_gpc();

    foreach ( $valid_variables as $key ) {

        if ( empty( $key ) ) {
            continue;
        }

        if ( is_array( $array[$key] ) ) {
            // there could be a variable coming from a cookie of
            // another application, with the same name as this array
            unset($target[$key]);

            PMA_gpc_extract($array[$key], $target[$key], FALSE);
        } elseif ($is_magic_quotes) {
            $target[$key] = stripslashes($array[$key]);
        } else {
            $target[$key] = $array[$key];
        }
    }
    return TRUE;
}

// check if a subform is submitted
$__redirect = NULL;
if ( isset( $_POST['usesubform'] ) ) {
    // if a subform is present and should be used
    // the rest of the form is deprecated
    $subform_id = key( $_POST['usesubform'] );
    $subform    = $_POST['subform'][$subform_id];
    $_POST      = $subform;
    if ( isset( $_POST['redirect'] )
      && $_POST['redirect'] != basename( $_SERVER['PHP_SELF'] ) ) {
        $__redirect = $_POST['redirect'];
        unset( $_POST['redirect'] );
    } // end if ( isset( $_POST['redirect'] ) )
    unset( $subform_id, $subform );
} // end if ( isset( $_POST['usesubform'] ) )
// end check if a subform is submitted

if (!empty($_GET)) {
    PMA_gpc_extract($_GET, $GLOBALS);
} // end if

if (!empty($_POST)) {
    PMA_gpc_extract($_POST, $GLOBALS);
} // end if (!empty($_POST))

if (!empty($_FILES)) {
    foreach ($_FILES AS $name => $value) {
        $$name = $value['tmp_name'];
        ${$name . '_name'} = $value['name'];
    }
} // end if
unset( $name, $value );

if (!empty($_SERVER)) {
    $server_vars = array('PHP_SELF', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_AUTHORIZATION');
    foreach ( $server_vars as $current ) {
        // its not important HOW we detect html tags
        // its more important to prevent XSS
        // so its not important if we result in an invalid string,
        // its even better than a XSS capable string
        if ( isset( $_SERVER[$current] ) && false === strpos( $_SERVER[$current], '<' ) ) {
            $$current = $_SERVER[$current];
        // already importet by register_globals?
        } elseif ( ! isset( $$current ) || false !== strpos( $$current, '<' ) ) {
            $$current = '';
        }
    }
    unset( $server_vars, $current );
} // end if

// Security fix: disallow accessing serious server files via "?goto="
if (isset($goto) && strpos(' ' . $goto, '/') > 0 && substr($goto, 0, 2) != './') {
    unset($goto);
} // end if

unset( $import_blacklist );

if ( ! empty( $__redirect ) ) {
    // TODO: ensure that PMA_securePath() is defined and available
    // for this script. Meanwhile we duplicate what this function does:
    require('./' . preg_replace('@\.\.*@','.',$__redirect));
    exit();
} // end if ( ! empty( $__redirect ) )
?>
