<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This library grabs the names and values of the variables sent or posted to a
 * script in the $_* arrays and sets simple globals variables from them. It does
 * the same work for the $PHP_SELF, $HTTP_ACCEPT_LANGUAGE and
 * $HTTP_AUTHORIZATION variables.
 *
 * @version $Id$
 */

/**
 * copy values from one array to another, usally from a superglobal into $GLOBALS
 *
 * @uses    $GLOBALS['_import_blacklist']
 * @uses    preg_replace()
 * @uses    array_keys()
 * @uses    array_unique()
 * @uses    stripslashes()
 * @param   array   $array      values from
 * @param   array   $target     values to
 * @param   boolean $sanitize   prevent importing key names in $_import_blacklist
 */
function PMA_gpc_extract($array, &$target, $sanitize = true)
{
    if (! is_array($array)) {
        return false;
    }

    if ($sanitize) {
        $valid_variables = preg_replace($GLOBALS['_import_blacklist'], '',
            array_keys($array));
        $valid_variables = array_unique($valid_variables);
    } else {
        $valid_variables = array_keys($array);
    }

    foreach ($valid_variables as $key) {

        if (strlen($key) === 0) {
            continue;
        }

        if (is_array($array[$key])) {
            // there could be a variable coming from a cookie of
            // another application, with the same name as this array
            unset($target[$key]);

            PMA_gpc_extract($array[$key], $target[$key], false);
        } else {
            $target[$key] = $array[$key];
        }
    }
    return true;
}


/**
 * @var array $_import_blacklist variable names that should NEVER be imported
 *                              from superglobals
 */
$_import_blacklist = array(
    '/^cfg$/i',         // PMA configuration
    '/^server$/i',      // selected server
    '/^db$/i',          // page to display
    '/^table$/i',       // page to display
    '/^goto$/i',        // page to display
    '/^back$/i',        // the page go back
    '/^lang$/i',        // selected language
    '/^convcharset$/i', // PMA convert charset
    '/^collation_connection$/i', //
    '/^set_theme$/i',   //
    '/^sql_query$/i',   // the query to be executed
    '/^GLOBALS$/i',     // the global scope
    '/^str.*$/i',       // PMA localized strings
    '/^error_handler.*$/i',       // the error handler
    '/^_.*$/i',         // PMA does not use variables starting with _ from extern
    '/^.*\s+.*$/i',     // no whitespaces anywhere
    '/^[0-9]+.*$/i',    // numeric variable names
    //'/^PMA_.*$/i',      // other PMA variables
);

if (! empty($_GET)) {
    PMA_gpc_extract($_GET, $GLOBALS);
}

if (! empty($_POST)) {
    PMA_gpc_extract($_POST, $GLOBALS);
}

if (! empty($_FILES)) {
    $_valid_variables = preg_replace($GLOBALS['_import_blacklist'], '', array_keys($_FILES));
    foreach ($_valid_variables as $name) {
        if (strlen($name) != 0) {
            $$name = $_FILES[$name]['tmp_name'];
            ${$name . '_name'} = $_FILES[$name]['name'];
        }
    }
    unset($name, $value);
}

/**
 * globalize some environment variables
 */
$server_vars = array('HTTP_ACCEPT_LANGUAGE', 'HTTP_AUTHORIZATION');
foreach ($server_vars as $current) {
    // its not important HOW we detect html tags
    // its more important to prevent XSS
    // so its not important if we result in an invalid string,
    // its even better than a XSS capable string
    if (PMA_getenv($current) && false === strpos(PMA_getenv($current), '<')) {
        $$current = PMA_getenv($current);
    // already importet by register_globals?
    } elseif (! isset($$current) || false !== strpos($$current, '<')) {
        $$current = '';
    }
}
unset($server_vars, $current, $_import_blacklist);

?>
