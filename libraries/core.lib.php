<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Core functions used all over the scripts.
 * This script is distinct from libraries/common.inc.php because this
 * script is called from /test.
 *
 * @package phpMyAdmin
 */

/**
 * checks given $var and returns it if valid, or $default of not valid
 * given $var is also checked for type being 'similar' as $default
 * or against any other type if $type is provided
 *
 * <code>
 * // $_REQUEST['db'] not set
 * echo PMA_ifSetOr($_REQUEST['db'], ''); // ''
 * // $_REQUEST['sql_query'] not set
 * echo PMA_ifSetOr($_REQUEST['sql_query']); // null
 * // $cfg['ForceSSL'] not set
 * echo PMA_ifSetOr($cfg['ForceSSL'], false, 'boolean'); // false
 * echo PMA_ifSetOr($cfg['ForceSSL']); // null
 * // $cfg['ForceSSL'] set to 1
 * echo PMA_ifSetOr($cfg['ForceSSL'], false, 'boolean'); // false
 * echo PMA_ifSetOr($cfg['ForceSSL'], false, 'similar'); // 1
 * echo PMA_ifSetOr($cfg['ForceSSL'], false); // 1
 * // $cfg['ForceSSL'] set to true
 * echo PMA_ifSetOr($cfg['ForceSSL'], false, 'boolean'); // true
 * </code>
 *
 * @uses    PMA_isValid()
 * @see     PMA_isValid()
 * @param   mixed   $var        param to check
 * @param   mixed   $default    default value
 * @param   mixed   $type       var type or array of values to check against $var
 * @return  mixed   $var or $default
 */
function PMA_ifSetOr(&$var, $default = null, $type = 'similar')
{
    if (! PMA_isValid($var, $type, $default)) {
        return $default;
    }

    return $var;
}

/**
 * checks given $var against $type or $compare
 *
 * $type can be:
 * - false       : no type checking
 * - 'scalar'    : whether type of $var is integer, float, string or boolean
 * - 'numeric'   : whether type of $var is any number repesentation
 * - 'length'    : whether type of $var is scalar with a string length > 0
 * - 'similar'   : whether type of $var is similar to type of $compare
 * - 'equal'     : whether type of $var is identical to type of $compare
 * - 'identical' : whether $var is identical to $compare, not only the type!
 * - or any other valid PHP variable type
 *
 * <code>
 * // $_REQUEST['doit'] = true;
 * PMA_isValid($_REQUEST['doit'], 'identical', 'true'); // false
 * // $_REQUEST['doit'] = 'true';
 * PMA_isValid($_REQUEST['doit'], 'identical', 'true'); // true
 * </code>
 *
 * NOTE: call-by-reference is used to not get NOTICE on undefined vars,
 * but the var is not altered inside this function, also after checking a var
 * this var exists nut is not set, example:
 * <code>
 * // $var is not set
 * isset($var); // false
 * functionCallByReference($var); // false
 * isset($var); // true
 * functionCallByReference($var); // true
 * </code>
 *
 * to avoid this we set this var to null if not isset
 *
 * @todo create some testsuites
 * @todo add some more var types like hex, bin, ...?
 * @uses    is_scalar()
 * @uses    is_numeric()
 * @uses    is_array()
 * @uses    in_array()
 * @uses    gettype()
 * @uses    strtolower()
 * @see     http://php.net/gettype
 * @param   mixed   $var        variable to check
 * @param   mixed   $type       var type or array of valid values to check against $var
 * @param   mixed   $compare    var to compare with $var
 * @return  boolean whether valid or not
 */
function PMA_isValid(&$var, $type = 'length', $compare = null)
{
    if (! isset($var)) {
        // var is not even set
        return false;
    }

    if ($type === false) {
        // no vartype requested
        return true;
    }

    if (is_array($type)) {
        return in_array($var, $type);
    }

    // allow some aliaes of var types
    $type = strtolower($type);
    switch ($type) {
        case 'identic' :
            $type = 'identical';
            break;
        case 'len' :
            $type = 'length';
            break;
        case 'bool' :
            $type = 'boolean';
            break;
        case 'float' :
            $type = 'double';
            break;
        case 'int' :
            $type = 'integer';
            break;
        case 'null' :
            $type = 'NULL';
            break;
    }

    if ($type === 'identical') {
        return $var === $compare;
    }

    // whether we should check against given $compare
    if ($type === 'similar') {
        switch (gettype($compare)) {
            case 'string':
            case 'boolean':
                $type = 'scalar';
                break;
            case 'integer':
            case 'double':
                $type = 'numeric';
                break;
            default:
                $type = gettype($compare);
        }
    } elseif ($type === 'equal') {
        $type = gettype($compare);
    }

    // do the check
    if ($type === 'length' || $type === 'scalar') {
        $is_scalar = is_scalar($var);
        if ($is_scalar && $type === 'length') {
            return (bool) strlen($var);
        }
        return $is_scalar;
    }

    if ($type === 'numeric') {
        return is_numeric($var);
    }

    if (gettype($var) === $type) {
        return true;
    }

    return false;
}

/**
 * Removes insecure parts in a path; used before include() or
 * require() when a part of the path comes from an insecure source
 * like a cookie or form.
 *
 * @param    string  The path to check
 *
 * @return   string  The secured path
 *
 * @access  public
 */
function PMA_securePath($path)
{
    // change .. to .
    $path = preg_replace('@\.\.*@', '.', $path);

    return $path;
} // end function

/**
 * displays the given error message on phpMyAdmin error page in foreign language,
 * ends script execution and closes session
 *
 * loads language file if not loaded already
 *
 * @todo    use detected argument separator (PMA_Config)
 * @uses    $GLOBALS['session_name']
 * @uses    $GLOBALS['text_dir']
 * @uses    $GLOBALS['available_languages']
 * @uses    $GLOBALS['lang']
 * @uses    $GLOBALS['PMA_Config']->removeCookie()
 * @uses    select_lang.lib.php
 * @uses    $_COOKIE
 * @uses    substr()
 * @uses    header()
 * @uses    http_build_query()
 * @uses    is_string()
 * @uses    sprintf()
 * @uses    vsprintf()
 * @uses    strtr()
 * @uses    defined()
 * @param   string $error_message the error message or named error message
 * @param   string|array $message_args arguments applied to $error_message
 * @return  exit
 */
function PMA_fatalError($error_message, $message_args = null)
{
    /* Use format string if applicable */
    if (is_string($message_args)) {
        $error_message = sprintf($error_message, $message_args);
    } elseif (is_array($message_args)) {
        $error_message = vsprintf($error_message, $message_args);
    }
    $error_message = strtr($error_message, array('<br />' => '[br]'));

    if (function_exists('__')) {
        $error_header = __('Error');
    } else {
        $error_header = 'Error';
    }

    // Displays the error message
    $lang = $GLOBALS['available_languages'][$GLOBALS['lang']][1];
    $dir = $GLOBALS['text_dir'];
    $type = $error_header;
    $error = $error_message;

    // on fatal errors it cannot hurt to always delete the current session
    if (isset($GLOBALS['session_name']) && isset($_COOKIE[$GLOBALS['session_name']])) {
        $GLOBALS['PMA_Config']->removeCookie($GLOBALS['session_name']);
    }

    require('./libraries/error.inc.php');

    exit;
}

/**
 * Returns a link to the PHP documentation
 *
 * @param string  anchor in documentation
 *
 * @return  string  the URL
 *
 * @access  public
 */
function PMA_getPHPDocLink($target) {
    /* l10n: Language to use for PHP documentation, please use only languages which do exist in official documentation. */
    $lang = _pgettext('PHP documentation language', 'en');

    return 'http://php.net/manual/' . $lang . '/' . $target;
}

/**
 * Warn or fail on missing extension.
 *
 * @param string $extension Extension name
 * @param bool $fatal Whether the error is fatal.
 / @param string $extra Extra string to append to messsage.
 */
function PMA_warnMissingExtension($extension, $fatal = false, $extra = '')
{
    $message = sprintf(__('The %s extension is missing. Please check your PHP configuration.'),
        '[a@' . PMA_getPHPDocLink('book.' . $extension . '.php') . '@Documentation][em]' . $extension . '[/em][/a]');
    if ($extra != '') {
        $message .= ' ' . $extra;
    }
    if ($fatal) {
        PMA_fatalError($message);
    } else {
        trigger_error($message, E_USER_WARNING);
    }
}

/**
 * returns count of tables in given db
 *
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_QUERY_STORE()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_free_result()
 * @param   string  $db database to count tables for
 * @return  integer count of tables in $db
 */
function PMA_getTableCount($db)
{
    $tables = PMA_DBI_try_query(
        'SHOW TABLES FROM ' . PMA_backquote($db) . ';',
        null, PMA_DBI_QUERY_STORE);
    if ($tables) {
        $num_tables = PMA_DBI_num_rows($tables);

        // do not count hidden blobstreaming tables
        while ((($num_tables > 0)) && $data = PMA_DBI_fetch_assoc($tables)) {
            if (PMA_BS_IsHiddenTable($data['Tables_in_' . $db])) {
                $num_tables--;
            }
        }

        PMA_DBI_free_result($tables);
    } else {
        $num_tables = 0;
    }

    return $num_tables;
}

/**
 * Converts numbers like 10M into bytes
 * Used with permission from Moodle (http://moodle.org) by Martin Dougiamas
 * (renamed with PMA prefix to avoid double definition when embedded
 * in Moodle)
 *
 * @uses    each()
 * @uses    strlen()
 * @uses    substr()
 * @param   string  $size
 * @return  integer $size
 */
function PMA_get_real_size($size = 0)
{
    if (! $size) {
        return 0;
    }

    $scan['gb'] = 1073741824; //1024 * 1024 * 1024;
    $scan['g']  = 1073741824; //1024 * 1024 * 1024;
    $scan['mb'] = 1048576;
    $scan['m']  = 1048576;
    $scan['kb'] =    1024;
    $scan['k']  =    1024;
    $scan['b']  =       1;

    foreach ($scan as $unit => $factor) {
        if (strlen($size) > strlen($unit)
         && strtolower(substr($size, strlen($size) - strlen($unit))) == $unit) {
            return substr($size, 0, strlen($size) - strlen($unit)) * $factor;
        }
    }

    return $size;
} // end function PMA_get_real_size()

/**
 * merges array recursive like array_merge_recursive() but keyed-values are
 * always overwritten.
 *
 * array PMA_array_merge_recursive(array $array1[, array $array2[, array ...]])
 *
 * @see     http://php.net/array_merge
 * @see     http://php.net/array_merge_recursive
 * @uses    func_num_args()
 * @uses    func_get_arg()
 * @uses    is_array()
 * @uses    call_user_func_array()
 * @param   array   array to merge
 * @param   array   array to merge
 * @param   array   ...
 * @return  array   merged array
 */
function PMA_array_merge_recursive()
{
    switch(func_num_args()) {
        case 0 :
            return false;
            break;
        case 1 :
            // when does that happen?
            return func_get_arg(0);
            break;
        case 2 :
            $args = func_get_args();
            if (!is_array($args[0]) || !is_array($args[1])) {
                return $args[1];
            }
            foreach ($args[1] as $key2 => $value2) {
                if (isset($args[0][$key2]) && !is_int($key2)) {
                    $args[0][$key2] = PMA_array_merge_recursive($args[0][$key2],
                        $value2);
                } else {
                    // we erase the parent array, otherwise we cannot override a directive that
                    // contains array elements, like this:
                    // (in config.default.php) $cfg['ForeignKeyDropdownOrder'] = array('id-content','content-id');
                    // (in config.inc.php) $cfg['ForeignKeyDropdownOrder'] = array('content-id');
                    if (is_int($key2) && $key2 == 0) {
                        unset($args[0]);
                    }
                    $args[0][$key2] = $value2;
                }
            }
            return $args[0];
            break;
        default :
            $args = func_get_args();
            $args[1] = PMA_array_merge_recursive($args[0], $args[1]);
            array_shift($args);
            return call_user_func_array('PMA_array_merge_recursive', $args);
            break;
    }
}

/**
 * calls $function vor every element in $array recursively
 *
 * this function is protected against deep recursion attack CVE-2006-1549,
 * 1000 seems to be more than enough
 *
 * @see http://www.php-security.org/MOPB/MOPB-02-2007.html
 * @see http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2006-1549
 *
 * @uses    PMA_arrayWalkRecursive()
 * @uses    is_array()
 * @uses    is_string()
 * @param   array   $array      array to walk
 * @param   string  $function   function to call for every array element
 */
function PMA_arrayWalkRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            PMA_arrayWalkRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

/**
 * boolean phpMyAdmin.PMA_checkPageValidity(string &$page, array $whitelist)
 *
 * checks given given $page against given $whitelist and returns true if valid
 * it ignores optionaly query paramters in $page (script.php?ignored)
 *
 * @uses    in_array()
 * @uses    urldecode()
 * @uses    substr()
 * @uses    strpos()
 * @param   string  &$page      page to check
 * @param   array   $whitelist  whitelist to check page against
 * @return  boolean whether $page is valid or not (in $whitelist or not)
 */
function PMA_checkPageValidity(&$page, $whitelist)
{
    if (! isset($page) || !is_string($page)) {
        return false;
    }

    if (in_array($page, $whitelist)) {
        return true;
    } elseif (in_array(substr($page, 0, strpos($page . '?', '?')), $whitelist)) {
        return true;
    } else {
        $_page = urldecode($page);
        if (in_array(substr($_page, 0, strpos($_page . '?', '?')), $whitelist)) {
            return true;
        }
    }
    return false;
}

/**
 * trys to find the value for the given environment vriable name
 *
 * searchs in $_SERVER, $_ENV than trys getenv() and apache_getenv()
 * in this order
 *
 * @uses    $_SERVER
 * @uses    $_ENV
 * @uses    getenv()
 * @uses    function_exists()
 * @uses    apache_getenv()
 * @param   string  $var_name   variable name
 * @return  string  value of $var or empty string
 */
function PMA_getenv($var_name) {
    if (isset($_SERVER[$var_name])) {
        return $_SERVER[$var_name];
    } elseif (isset($_ENV[$var_name])) {
        return $_ENV[$var_name];
    } elseif (getenv($var_name)) {
        return getenv($var_name);
    } elseif (function_exists('apache_getenv')
     && apache_getenv($var_name, true)) {
        return apache_getenv($var_name, true);
    }

    return '';
}

/**
 * Send HTTP header, taking IIS limits into account (600 seems ok)
 *
 * @uses    PMA_IS_IIS
 * @uses    PMA_COMING_FROM_COOKIE_LOGIN
 * @uses    PMA_get_arg_separator()
 * @uses    SID
 * @uses    strlen()
 * @uses    strpos()
 * @uses    header()
 * @uses    session_write_close()
 * @uses    headers_sent()
 * @uses    function_exists()
 * @uses    debug_print_backtrace()
 * @uses    trigger_error()
 * @uses    defined()
 * @param   string   $uri the header to send
 * @return  boolean  always true
 */
function PMA_sendHeaderLocation($uri)
{
    if (PMA_IS_IIS && strlen($uri) > 600) {
        require_once './libraries/js_escape.lib.php';

        echo '<html><head><title>- - -</title>' . "\n";
        echo '<meta http-equiv="expires" content="0">' . "\n";
        echo '<meta http-equiv="Pragma" content="no-cache">' . "\n";
        echo '<meta http-equiv="Cache-Control" content="no-cache">' . "\n";
        echo '<meta http-equiv="Refresh" content="0;url=' .  htmlspecialchars($uri) . '">' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo '//<![CDATA[' . "\n";
        echo 'setTimeout("window.location = unescape(\'"' . PMA_escapeJsString($uri) . '"\')", 2000);' . "\n";
        echo '//]]>' . "\n";
        echo '</script>' . "\n";
        echo '</head>' . "\n";
        echo '<body>' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo '//<![CDATA[' . "\n";
        echo 'document.write(\'<p><a href="' . htmlspecialchars($uri) . '">' . __('Go') . '</a></p>\');' . "\n";
        echo '//]]>' . "\n";
        echo '</script></body></html>' . "\n";

    } else {
        if (SID) {
            if (strpos($uri, '?') === false) {
                header('Location: ' . $uri . '?' . SID);
            } else {
                $separator = PMA_get_arg_separator();
                header('Location: ' . $uri . $separator . SID);
            }
        } else {
            session_write_close();
            if (headers_sent()) {
                if (function_exists('debug_print_backtrace')) {
                    echo '<pre>';
                    debug_print_backtrace();
                    echo '</pre>';
                }
                trigger_error('PMA_sendHeaderLocation called when headers are already sent!', E_USER_ERROR);
            }
            // bug #1523784: IE6 does not like 'Refresh: 0', it
            // results in a blank page
            // but we need it when coming from the cookie login panel)
            if (PMA_IS_IIS && defined('PMA_COMING_FROM_COOKIE_LOGIN')) {
                header('Refresh: 0; ' . $uri);
            } else {
                header('Location: ' . $uri);
            }
        }
    }
}

/**
 * Returns value of an element in $array given by $path.
 * $path is a string describing position of an element in an associative array,
 * eg. Servers/1/host refers to $array[Servers][1][host]
 *
 * @param  string   $path
 * @param  array    $array
 * @param  mixed    $default
 * @return mixed    array element or $default
 */
function PMA_array_read($path, $array, $default = null)
{
    $keys = explode('/', $path);
    $value =& $array;
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return $default;
        }
        $value =& $value[$key];
    }
    return $value;
}

/**
 * Stores value in an array
 *
 * @param  string   $path
 * @param  array    &$array
 * @param  mixed    $value
 */
function PMA_array_write($path, &$array, $value)
{
    $keys = explode('/', $path);
    $last_key = array_pop($keys);
    $a =& $array;
    foreach ($keys as $key) {
        if (!isset($a[$key])) {
            $a[$key] = array();
        }
        $a =& $a[$key];
    }
    $a[$last_key] = $value;
}

/**
 * Removes value from an array
 *
 * @param  string   $path
 * @param  array    &$array
 * @param  mixed    $value
 */
function PMA_array_remove($path, &$array)
{
    $keys = explode('/', $path);
    $keys_last = array_pop($keys);
    $path = array();
    $depth = 0;

    $path[0] =& $array;
    $found = true;
    // go as deep as required or possible
    foreach ($keys as $key) {
        if (!isset($path[$depth][$key])) {
            $found = false;
            break;
        }
        $depth++;
        $path[$depth] =& $path[$depth-1][$key];
    }
    // if element found, remove it
    if ($found) {
        unset($path[$depth][$keys_last]);
        $depth--;
    }

    // remove empty nested arrays
    for (; $depth >= 0; $depth--) {
        if (!isset($path[$depth+1]) || count($path[$depth+1]) == 0) {
            unset($path[$depth][$keys[$depth]]);
        } else {
            break;
        }
    }
}

/**
 * Returns link to (possibly) external site using defined redirector.
 *
 * @param string $url  URL where to go.
 *
 * @return string URL for a link.
 */
function PMA_linkURL($url) {
    if (!preg_match('#^https?://#', $url) || defined('PMA_SETUP')) {
        return $url;
    } else {
        $params = array();
        $params['url'] = $url;
        return './url.php' . PMA_generate_common_url($params);
    }
}

/**
 * Returns HTML code to include javascript file.
 *
 * @param string $url Location of javascript, relative to js/ folder.
 *
 * @return string HTML code for javascript inclusion.
 */
function PMA_includeJS($url) {
    if (strpos($url, '?') === FALSE) {
        return '<script src="./js/' . $url . '?ts=' . filemtime('./js/' . $url) . '" type="text/javascript"></script>' . "\n";
    } else {
        return '<script src="./js/' . $url . '" type="text/javascript"></script>' . "\n";
    }
}
?>
