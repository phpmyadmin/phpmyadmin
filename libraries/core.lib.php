<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Core functions used all over the scripts.
 * This script is distinct from libraries/common.inc.php because this
 * script is called from /test.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * String handling (security)
 */
require_once 'libraries/string.lib.php';
require_once 'libraries/String.class.php';
$PMA_String = new PMA_String();

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
 * @param mixed &$var    param to check
 * @param mixed $default default value
 * @param mixed $type    var type or array of values to check against $var
 *
 * @return mixed   $var or $default
 *
 * @see     PMA_isValid()
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
 * - 'numeric'   : whether type of $var is any number representation
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
 * @param mixed &$var    variable to check
 * @param mixed $type    var type or array of valid values to check against $var
 * @param mixed $compare var to compare with $var
 *
 * @return boolean whether valid or not
 *
 * @todo add some more var types like hex, bin, ...?
 * @see     http://php.net/gettype
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

    // allow some aliases of var types
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
            return (bool) /*overload*/mb_strlen($var);
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
 * @param string $path The path to check
 *
 * @return string  The secured path
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
 * @param string       $error_message  the error message or named error message
 * @param string|array $message_args   arguments applied to $error_message
 * @param boolean      $delete_session whether to delete session cookie
 *
 * @return void
 */
function PMA_fatalError(
    $error_message, $message_args = null, $delete_session = true
) {
    /* Use format string if applicable */
    if (is_string($message_args)) {
        $error_message = sprintf($error_message, $message_args);
    } elseif (is_array($message_args)) {
        $error_message = vsprintf($error_message, $message_args);
    }

    if ($GLOBALS['is_ajax_request']) {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', PMA_Message::error($error_message));
    } else {
        $error_message = strtr($error_message, array('<br />' => '[br]'));

        /* Load gettext for fatal errors */
        if (!function_exists('__')) {
            // It is possible that PMA_fatalError() is called before including
            // vendor_config.php which defines GETTEXT_INC. See bug #4557
            if (defined(GETTEXT_INC)) {
                include_once GETTEXT_INC;
            } else {
                include_once './libraries/php-gettext/gettext.inc';
            }
        }

        // these variables are used in the included file libraries/error.inc.php
        //first check if php-mbstring is available
        if (function_exists('mb_detect_encoding')) {
            //If present use gettext
            $error_header = __('Error');
        } else {
            $error_header = 'Error';
        }
        $lang = $GLOBALS['available_languages'][$GLOBALS['lang']][1];
        $dir = $GLOBALS['text_dir'];

        // on fatal errors it cannot hurt to always delete the current session
        if ($delete_session
            && isset($GLOBALS['session_name'])
            && isset($_COOKIE[$GLOBALS['session_name']])
        ) {
            $GLOBALS['PMA_Config']->removeCookie($GLOBALS['session_name']);
        }

        // Displays the error message
        include './libraries/error.inc.php';
    }
    if (! defined('TESTSUITE')) {
        exit;
    }
}

/**
 * Returns a link to the PHP documentation
 *
 * @param string $target anchor in documentation
 *
 * @return string  the URL
 *
 * @access  public
 */
function PMA_getPHPDocLink($target)
{
    /* List of PHP documentation translations */
    $php_doc_languages = array(
        'pt_BR', 'zh', 'fr', 'de', 'it', 'ja', 'pl', 'ro', 'ru', 'fa', 'es', 'tr'
    );

    $lang = 'en';
    if (in_array($GLOBALS['lang'], $php_doc_languages)) {
        $lang = $GLOBALS['lang'];
    }

    return PMA_linkURL('http://php.net/manual/' . $lang . '/' . $target);
}

/**
 * Warn or fail on missing extension.
 *
 * @param string $extension Extension name
 * @param bool   $fatal     Whether the error is fatal.
 * @param string $extra     Extra string to append to message.
 *
 * @return void
 */
function PMA_warnMissingExtension($extension, $fatal = false, $extra = '')
{
    /* Gettext does not have to be loaded yet here */
    if (function_exists('__')) {
        $message = __(
            'The %s extension is missing. Please check your PHP configuration.'
        );
    } else {
        $message
            = 'The %s extension is missing. Please check your PHP configuration.';
    }
    $doclink = PMA_getPHPDocLink('book.' . $extension . '.php');
    $message = sprintf(
        $message,
        '[a@' . $doclink . '@Documentation][em]' . $extension . '[/em][/a]'
    );
    if ($extra != '') {
        $message .= ' ' . $extra;
    }
    if ($fatal) {
        PMA_fatalError($message);
        return;
    }

    $GLOBALS['error_handler']->addError(
        $message,
        E_USER_WARNING,
        '',
        '',
        false
    );
}

/**
 * returns count of tables in given db
 *
 * @param string $db database to count tables for
 *
 * @return integer count of tables in $db
 */
function PMA_getTableCount($db)
{
    $tables = $GLOBALS['dbi']->tryQuery(
        'SHOW TABLES FROM ' . PMA_Util::backquote($db) . ';',
        null, PMA_DatabaseInterface::QUERY_STORE
    );
    if ($tables) {
        $num_tables = $GLOBALS['dbi']->numRows($tables);
        $GLOBALS['dbi']->freeResult($tables);
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
 * @param string|int $size size (Default = 0)
 *
 * @return integer $size
 */
function PMA_getRealSize($size = 0)
{
    if (! $size) {
        return 0;
    }

    $scan = array(
        'gb' => 1073741824, //1024 * 1024 * 1024,
        'g'  => 1073741824, //1024 * 1024 * 1024,
        'mb' =>    1048576,
        'm'  =>    1048576,
        'kb' =>       1024,
        'k'  =>       1024,
        'b'  =>          1,
    );

    foreach ($scan as $unit => $factor) {
        $sizeLength = strlen($size);
        $unitLength = strlen($unit);
        if ($sizeLength > $unitLength
            && strtolower(
                substr(
                    $size,
                    $sizeLength - $unitLength
                )
            ) == $unit
        ) {
            return substr(
                $size,
                0,
                $sizeLength - $unitLength
            ) * $factor;
        }
    }

    return $size;
} // end function PMA_getRealSize()

/**
 * merges array recursive like array_merge_recursive() but keyed-values are
 * always overwritten.
 *
 * array PMA_arrayMergeRecursive(array $array1[, array $array2[, array ...]])
 *
 * @return array   merged array
 *
 * @see     http://php.net/array_merge
 * @see     http://php.net/array_merge_recursive
 */
function PMA_arrayMergeRecursive()
{
    switch(func_num_args()) {
    case 0 :
        return false;
    case 1 :
        // when does that happen?
        return func_get_arg(0);
    case 2 :
        $args = func_get_args();
        if (! is_array($args[0]) || ! is_array($args[1])) {
            return $args[1];
        }
        foreach ($args[1] as $key2 => $value2) {
            if (isset($args[0][$key2]) && !is_int($key2)) {
                $args[0][$key2] = PMA_arrayMergeRecursive(
                    $args[0][$key2], $value2
                );
            } else {
                // we erase the parent array, otherwise we cannot override
                // a directive that contains array elements, like this:
                // (in config.default.php)
                // $cfg['ForeignKeyDropdownOrder']= array('id-content','content-id');
                // (in config.inc.php)
                // $cfg['ForeignKeyDropdownOrder']= array('content-id');
                if (is_int($key2) && $key2 == 0) {
                    unset($args[0]);
                }
                $args[0][$key2] = $value2;
            }
        }
        return $args[0];
    default :
        $args = func_get_args();
        $args[1] = PMA_arrayMergeRecursive($args[0], $args[1]);
        array_shift($args);
        return call_user_func_array('PMA_arrayMergeRecursive', $args);
    }
}

/**
 * calls $function for every element in $array recursively
 *
 * this function is protected against deep recursion attack CVE-2006-1549,
 * 1000 seems to be more than enough
 *
 * @param array    &$array             array to walk
 * @param callable $function           function to call for every array element
 * @param bool     $apply_to_keys_also whether to call the function for the keys also
 *
 * @return void
 *
 * @see http://www.php-security.org/MOPB/MOPB-02-2007.html
 * @see http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2006-1549
 */
function PMA_arrayWalkRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    $walked_keys = array();

    if (++$recursive_counter > 1000) {
        PMA_fatalError(__('possible deep recursion attack'));
    }
    foreach ($array as $key => $value) {
        if (isset($walked_keys[$key])) {
            continue;
        }
        $walked_keys[$key] = true;

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
                $walked_keys[$new_key] = true;
            }
        }
    }
    $recursive_counter--;
}

/**
 * boolean phpMyAdmin.PMA_checkPageValidity(string &$page, array $whitelist)
 *
 * checks given $page against given $whitelist and returns true if valid
 * it optionally ignores query parameters in $page (script.php?ignored)
 *
 * @param string &$page     page to check
 * @param array  $whitelist whitelist to check page against
 *
 * @return boolean whether $page is valid or not (in $whitelist or not)
 */
function PMA_checkPageValidity(&$page, $whitelist)
{
    if (! isset($page) || !is_string($page)) {
        return false;
    }

    if (in_array($page, $whitelist)) {
        return true;
    }

    $_page = /*overload*/mb_substr(
        $page,
        0,
        /*overload*/mb_strpos($page . '?', '?')
    );
    if (in_array($_page, $whitelist)) {
        return true;
    }

    $_page = urldecode($page);
    $_page = /*overload*/mb_substr(
        $_page,
        0,
        /*overload*/mb_strpos($_page . '?', '?')
    );
    if (in_array($_page, $whitelist)) {
        return true;
    }

    return false;
}

/**
 * tries to find the value for the given environment variable name
 *
 * searches in $_SERVER, $_ENV then tries getenv() and apache_getenv()
 * in this order
 *
 * @param string $var_name variable name
 *
 * @return string  value of $var or empty string
 */
function PMA_getenv($var_name)
{
    if (isset($_SERVER[$var_name])) {
        return $_SERVER[$var_name];
    }

    if (isset($_ENV[$var_name])) {
        return $_ENV[$var_name];
    }

    if (getenv($var_name)) {
        return getenv($var_name);
    }

    if (function_exists('apache_getenv')
        && apache_getenv($var_name, true)
    ) {
        return apache_getenv($var_name, true);
    }

    return '';
}

/**
 * Send HTTP header, taking IIS limits into account (600 seems ok)
 *
 * @param string $uri         the header to send
 * @param bool   $use_refresh whether to use Refresh: header when running on IIS
 *
 * @return boolean  always true
 */
function PMA_sendHeaderLocation($uri, $use_refresh = false)
{
    if (PMA_IS_IIS && /*overload*/mb_strlen($uri) > 600) {
        include_once './libraries/js_escape.lib.php';
        PMA_Response::getInstance()->disable();

        include_once './libraries/Template.class.php';

        echo PMA\Template::get('header_location')
            ->render(array('uri' => $uri));

        return;
    }

    if (SID) {
        if (/*overload*/mb_strpos($uri, '?') === false) {
            header('Location: ' . $uri . '?' . SID);
        } else {
            $separator = PMA_URL_getArgSeparator();
            header('Location: ' . $uri . $separator . SID);
        }
        return;
    }

    session_write_close();
    if (headers_sent()) {
        if (function_exists('debug_print_backtrace')) {
            echo '<pre>';
            debug_print_backtrace();
            echo '</pre>';
        }
        trigger_error(
            'PMA_sendHeaderLocation called when headers are already sent!',
            E_USER_ERROR
        );
    }
    // bug #1523784: IE6 does not like 'Refresh: 0', it
    // results in a blank page
    // but we need it when coming from the cookie login panel)
    if (PMA_IS_IIS && $use_refresh) {
        header('Refresh: 0; ' . $uri);
    } else {
        header('Location: ' . $uri);
    }
}

/**
 * Outputs headers to prevent caching in browser (and on the way).
 *
 * @return void
 */
function PMA_noCacheHeader()
{
    if (defined('TESTSUITE') && ! defined('PMA_TEST_HEADERS')) {
        return;
    }
    // rfc2616 - Section 14.21
    header('Expires: ' . date(DATE_RFC1123));
    // HTTP/1.1
    header(
        'Cache-Control: no-store, no-cache, must-revalidate,'
        . '  pre-check=0, post-check=0, max-age=0'
    );
    if (PMA_USR_BROWSER_AGENT == 'IE') {
        /* On SSL IE sometimes fails with:
         *
         * Internet Explorer was not able to open this Internet site. The
         * requested site is either unavailable or cannot be found. Please
         * try again later.
         *
         * Adding Pragma: public fixes this.
         */
        header('Pragma: public');
        return;
    }

    header('Pragma: no-cache'); // HTTP/1.0
    // test case: exporting a database into a .gz file with Safari
    // would produce files not having the current time
    // (added this header for Safari but should not harm other browsers)
    header('Last-Modified: ' . date(DATE_RFC1123));
}


/**
 * Sends header indicating file download.
 *
 * @param string $filename Filename to include in headers if empty,
 *                         none Content-Disposition header will be sent.
 * @param string $mimetype MIME type to include in headers.
 * @param int    $length   Length of content (optional)
 * @param bool   $no_cache Whether to include no-caching headers.
 *
 * @return void
 */
function PMA_downloadHeader($filename, $mimetype, $length = 0, $no_cache = true)
{
    if ($no_cache) {
        PMA_noCacheHeader();
    }
    /* Replace all possibly dangerous chars in filename */
    $filename = str_replace(array(';', '"', "\n", "\r"), '-', $filename);
    if (!empty($filename)) {
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }
    header('Content-Type: ' . $mimetype);
    // inform the server that compression has been done,
    // to avoid a double compression (for example with Apache + mod_deflate)
    $notChromeOrLessThan43 = PMA_USR_BROWSER_AGENT != 'CHROME' // see bug #4942
        || (PMA_USR_BROWSER_AGENT == 'CHROME' && PMA_USR_BROWSER_VER < 43);
    if (strpos($mimetype, 'gzip') !== false && $notChromeOrLessThan43) {
        header('Content-Encoding: gzip');
    }
    header('Content-Transfer-Encoding: binary');
    if ($length > 0) {
        header('Content-Length: ' . $length);
    }
}

/**
 * Returns value of an element in $array given by $path.
 * $path is a string describing position of an element in an associative array,
 * eg. Servers/1/host refers to $array[Servers][1][host]
 *
 * @param string $path    path in the array
 * @param array  $array   the array
 * @param mixed  $default default value
 *
 * @return mixed    array element or $default
 */
function PMA_arrayRead($path, $array, $default = null)
{
    $keys = explode('/', $path);
    $value =& $array;
    foreach ($keys as $key) {
        if (! isset($value[$key])) {
            return $default;
        }
        $value =& $value[$key];
    }
    return $value;
}

/**
 * Stores value in an array
 *
 * @param string $path   path in the array
 * @param array  &$array the array
 * @param mixed  $value  value to store
 *
 * @return void
 */
function PMA_arrayWrite($path, &$array, $value)
{
    $keys = explode('/', $path);
    $last_key = array_pop($keys);
    $a =& $array;
    foreach ($keys as $key) {
        if (! isset($a[$key])) {
            $a[$key] = array();
        }
        $a =& $a[$key];
    }
    $a[$last_key] = $value;
}

/**
 * Removes value from an array
 *
 * @param string $path   path in the array
 * @param array  &$array the array
 *
 * @return void
 */
function PMA_arrayRemove($path, &$array)
{
    $keys = explode('/', $path);
    $keys_last = array_pop($keys);
    $path = array();
    $depth = 0;

    $path[0] =& $array;
    $found = true;
    // go as deep as required or possible
    foreach ($keys as $key) {
        if (! isset($path[$depth][$key])) {
            $found = false;
            break;
        }
        $depth++;
        $path[$depth] =& $path[$depth - 1][$key];
    }
    // if element found, remove it
    if ($found) {
        unset($path[$depth][$keys_last]);
        $depth--;
    }

    // remove empty nested arrays
    for (; $depth >= 0; $depth--) {
        if (! isset($path[$depth+1]) || count($path[$depth+1]) == 0) {
            unset($path[$depth][$keys[$depth]]);
        } else {
            break;
        }
    }
}

/**
 * Returns link to (possibly) external site using defined redirector.
 *
 * @param string $url URL where to go.
 *
 * @return string URL for a link.
 */
function PMA_linkURL($url)
{
    if (!preg_match('#^https?://#', $url) || defined('PMA_SETUP')) {
        return $url;
    }

    if (!function_exists('PMA_URL_getCommon')) {
        include_once './libraries/url_generating.lib.php';
    }
    $params = array();
    $params['url'] = $url;

    $url = PMA_URL_getCommon($params);
    //strip off token and such sensitive information. Just keep url.
    $arr = parse_url($url);
    parse_str($arr["query"], $vars);
    $query = http_build_query(array("url" => $vars["url"]));
    $url = './url.php?' . $query;

    return $url;
}

/**
 * Checks whether domain of URL is whitelisted domain or not.
 * Use only for URLs of external sites.
 *
 * @param string $url URL of external site.
 *
 * @return boolean True: if domain of $url is allowed domain,
 *                 False: otherwise.
 */
function PMA_isAllowedDomain($url)
{
    $arr = parse_url($url);
    $domain = $arr["host"];
    $domainWhiteList = array(
        /* Include current domain */
        $_SERVER['SERVER_NAME'],
        /* phpMyAdmin domains */
        'wiki.phpmyadmin.net', 'www.phpmyadmin.net', 'phpmyadmin.net',
        'docs.phpmyadmin.net',
        /* mysql.com domains */
        'dev.mysql.com','bugs.mysql.com',
        /* drizzle.org domains */
        'www.drizzle.org',
        /* mariadb domains */
        'mariadb.org',
        /* php.net domains */
        'php.net',
        /* Github domains*/
        'github.com','www.github.com',
        /* Following are doubtful ones. */
        'www.primebase.com',
        'pbxt.blogspot.com',
        'www.percona.com',
        'mysqldatabaseadministration.blogspot.com',
        'ronaldbradford.com',
        'xaprb.com',
    );
    if (in_array(/*overload*/mb_strtolower($domain), $domainWhiteList)) {
        return true;
    }

    return false;
}


/**
 * Adds JS code snippets to be displayed by the PMA_Response class.
 * Adds a newline to each snippet.
 *
 * @param string $str Js code to be added (e.g. "token=1234;")
 *
 * @return void
 */
function PMA_addJSCode($str)
{
    $response = PMA_Response::getInstance();
    $header   = $response->getHeader();
    $scripts  = $header->getScripts();
    $scripts->addCode($str);
}

/**
 * Adds JS code snippet for variable assignment
 * to be displayed by the PMA_Response class.
 *
 * @param string $key    Name of value to set
 * @param mixed  $value  Value to set, can be either string or array of strings
 * @param bool   $escape Whether to escape value or keep it as it is
 *                       (for inclusion of js code)
 *
 * @return void
 */
function PMA_addJSVar($key, $value, $escape = true)
{
    PMA_addJSCode(PMA_getJsValue($key, $value, $escape));
}

/**
 * Replace some html-unfriendly stuff
 *
 * @param string $buffer String to process
 *
 * @return string Escaped and cleaned up text suitable for html
 */
function PMA_mimeDefaultFunction($buffer)
{
    $buffer = htmlspecialchars($buffer);
    $buffer = str_replace('  ', ' &nbsp;', $buffer);
    $buffer = preg_replace("@((\015\012)|(\015)|(\012))@", '<br />' . "\n", $buffer);

    return $buffer;
}

/**
 * Displays SQL query before executing.
 *
 * @param array|string $query_data Array containing queries or query itself
 *
 * @return void
 */
function PMA_previewSQL($query_data)
{
    $retval = '<div class="preview_sql">';
    if (empty($query_data)) {
        $retval .= __('No change');
    } elseif (is_array($query_data)) {
        foreach ($query_data as $query) {
            $retval .= PMA_Util::formatSql($query);
        }
    } else {
        $retval .= PMA_Util::formatSql($query_data);
    }
    $retval .= '</div>';
    $response = PMA_Response::getInstance();
    $response->addJSON('sql_data', $retval);
    exit;
}

/**
 * recursively check if variable is empty
 *
 * @param mixed $value the variable
 *
 * @return bool true if empty
 */
function PMA_emptyRecursive($value)
{
    $empty = true;
    if (is_array($value)) {
        PMA_arrayWalkRecursive(
            $value,
            function ($item) use (&$empty) {
                $empty = $empty && empty($item);
            }
        );
    } else {
        $empty = empty($value);
    }
    return $empty;
}

/**
 * Creates some globals from $_POST variables matching a pattern
 *
 * @param array $post_patterns The patterns to search for
 *
 * @return void
 */
function PMA_setPostAsGlobal($post_patterns)
{
    foreach (array_keys($_POST) as $post_key) {
        foreach ($post_patterns as $one_post_pattern) {
            if (preg_match($one_post_pattern, $post_key)) {
                $GLOBALS[$post_key] = $_POST[$post_key];
            }
        }
    }
}

/**
 * Creates some globals from $_REQUEST
 *
 * @param string $param db|table
 *
 * @return void
 */
function PMA_setGlobalDbOrTable($param)
{
    $GLOBALS[$param] = '';
    if (PMA_isValid($_REQUEST[$param])) {
        // can we strip tags from this?
        // only \ and / is not allowed in db names for MySQL
        $GLOBALS[$param] = $_REQUEST[$param];
        $GLOBALS['url_params'][$param] = $GLOBALS[$param];
    }
}
