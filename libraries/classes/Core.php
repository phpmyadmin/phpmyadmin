<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Core functions used all over the scripts.
 * This script is distinct from libraries/common.inc.php because this
 * script is called from /test.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Core class
 *
 * @package PhpMyAdmin
 */
class Core
{
    /**
     * the whitelist for goto parameter
     * @static array $goto_whitelist
     */
    public static $goto_whitelist = array(
        'db_datadict.php',
        'db_sql.php',
        'db_events.php',
        'db_export.php',
        'db_importdocsql.php',
        'db_multi_table_query.php',
        'db_qbe.php',
        'db_structure.php',
        'db_import.php',
        'db_operations.php',
        'db_search.php',
        'db_routines.php',
        'export.php',
        'import.php',
        'index.php',
        'pdf_pages.php',
        'pdf_schema.php',
        'server_binlog.php',
        'server_collations.php',
        'server_databases.php',
        'server_engines.php',
        'server_export.php',
        'server_import.php',
        'server_privileges.php',
        'server_sql.php',
        'server_status.php',
        'server_status_advisor.php',
        'server_status_monitor.php',
        'server_status_queries.php',
        'server_status_variables.php',
        'server_variables.php',
        'sql.php',
        'tbl_addfield.php',
        'tbl_change.php',
        'tbl_create.php',
        'tbl_import.php',
        'tbl_indexes.php',
        'tbl_sql.php',
        'tbl_export.php',
        'tbl_operations.php',
        'tbl_structure.php',
        'tbl_relation.php',
        'tbl_replace.php',
        'tbl_row_action.php',
        'tbl_select.php',
        'tbl_zoom_select.php',
        'transformation_overview.php',
        'transformation_wrapper.php',
        'user_password.php',
    );

    /**
     * checks given $var and returns it if valid, or $default of not valid
     * given $var is also checked for type being 'similar' as $default
     * or against any other type if $type is provided
     *
     * <code>
     * // $_REQUEST['db'] not set
     * echo Core::ifSetOr($_REQUEST['db'], ''); // ''
     * // $_POST['sql_query'] not set
     * echo Core::ifSetOr($_POST['sql_query']); // null
     * // $cfg['EnableFoo'] not set
     * echo Core::ifSetOr($cfg['EnableFoo'], false, 'boolean'); // false
     * echo Core::ifSetOr($cfg['EnableFoo']); // null
     * // $cfg['EnableFoo'] set to 1
     * echo Core::ifSetOr($cfg['EnableFoo'], false, 'boolean'); // false
     * echo Core::ifSetOr($cfg['EnableFoo'], false, 'similar'); // 1
     * echo Core::ifSetOr($cfg['EnableFoo'], false); // 1
     * // $cfg['EnableFoo'] set to true
     * echo Core::ifSetOr($cfg['EnableFoo'], false, 'boolean'); // true
     * </code>
     *
     * @param mixed &$var    param to check
     * @param mixed $default default value
     * @param mixed $type    var type or array of values to check against $var
     *
     * @return mixed   $var or $default
     *
     * @see self::isValid()
     */
    public static function ifSetOr(&$var, $default = null, $type = 'similar')
    {
        if (! self::isValid($var, $type, $default)) {
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
     * Core::isValid($_REQUEST['doit'], 'identical', 'true'); // false
     * // $_REQUEST['doit'] = 'true';
     * Core::isValid($_REQUEST['doit'], 'identical', 'true'); // true
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
     * @see https://secure.php.net/gettype
     */
    public static function isValid(&$var, $type = 'length', $compare = null)
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
                return strlen($var) > 0;
            }
            return $is_scalar;
        }

        if ($type === 'numeric') {
            return is_numeric($var);
        }

        return gettype($var) === $type;
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
    public static function securePath($path)
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
     * @param string       $error_message the error message or named error message
     * @param string|array $message_args  arguments applied to $error_message
     *
     * @return void
     */
    public static function fatalError($error_message, $message_args = null) {
        /* Use format string if applicable */
        if (is_string($message_args)) {
            $error_message = sprintf($error_message, $message_args);
        } elseif (is_array($message_args)) {
            $error_message = vsprintf($error_message, $message_args);
        }

        /*
         * Avoid using Response class as config does not have to be loaded yet
         * (this can happen on early fatal error)
         */
        if (isset($GLOBALS['dbi']) && !is_null($GLOBALS['dbi']) && isset($GLOBALS['PMA_Config']) && $GLOBALS['PMA_Config']->get('is_setup') === false && Response::getInstance()->isAjax()) {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', Message::error($error_message));
        } elseif (! empty($_REQUEST['ajax_request'])) {
            // Generate JSON manually
            self::headerJSON();
            echo json_encode(
                array(
                    'success' => false,
                    'message' => Message::error($error_message)->getDisplay(),
                )
            );
        } else {
            $error_message = strtr($error_message, array('<br />' => '[br]'));
            $error_header = __('Error');
            $lang = isset($GLOBALS['lang']) ? $GLOBALS['lang'] : 'en';
            $dir = isset($GLOBALS['text_dir']) ? $GLOBALS['text_dir'] : 'ltr';

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
    public static function getPHPDocLink($target)
    {
        /* List of PHP documentation translations */
        $php_doc_languages = array(
            'pt_BR', 'zh', 'fr', 'de', 'it', 'ja', 'pl', 'ro', 'ru', 'fa', 'es', 'tr'
        );

        $lang = 'en';
        if (in_array($GLOBALS['lang'], $php_doc_languages)) {
            $lang = $GLOBALS['lang'];
        }

        return self::linkURL('https://secure.php.net/manual/' . $lang . '/' . $target);
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
    public static function warnMissingExtension($extension, $fatal = false, $extra = '')
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
        $doclink = self::getPHPDocLink('book.' . $extension . '.php');
        $message = sprintf(
            $message,
            '[a@' . $doclink . '@Documentation][em]' . $extension . '[/em][/a]'
        );
        if ($extra != '') {
            $message .= ' ' . $extra;
        }
        if ($fatal) {
            self::fatalError($message);
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
    public static function getTableCount($db)
    {
        $tables = $GLOBALS['dbi']->tryQuery(
            'SHOW TABLES FROM ' . Util::backquote($db) . ';',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
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
     * Used with permission from Moodle (https://moodle.org) by Martin Dougiamas
     * (renamed with PMA prefix to avoid double definition when embedded
     * in Moodle)
     *
     * @param string|int $size size (Default = 0)
     *
     * @return integer $size
     */
    public static function getRealSize($size = 0)
    {
        if (! $size) {
            return 0;
        }

        $binaryprefixes = array(
            'T' => 1099511627776,
            't' => 1099511627776,
            'G' =>    1073741824,
            'g' =>    1073741824,
            'M' =>       1048576,
            'm' =>       1048576,
            'K' =>          1024,
            'k' =>          1024,
        );

        if (preg_match('/^([0-9]+)([KMGT])/i', $size, $matches)) {
            return $matches[1] * $binaryprefixes[$matches[2]];
        }

        return (int) $size;
    } // end getRealSize()

    /**
     * boolean phpMyAdmin.Core::checkPageValidity(string &$page, array $whitelist)
     *
     * checks given $page against given $whitelist and returns true if valid
     * it optionally ignores query parameters in $page (script.php?ignored)
     *
     * @param string  &$page     page to check
     * @param array   $whitelist whitelist to check page against
     * @param boolean $include   whether the page is going to be included
     *
     * @return boolean whether $page is valid or not (in $whitelist or not)
     */
    public static function checkPageValidity(&$page, array $whitelist = [], $include = false)
    {
        if (empty($whitelist)) {
            $whitelist = self::$goto_whitelist;
        }
        if (! isset($page) || !is_string($page)) {
            return false;
        }

        if (in_array($page, $whitelist)) {
            return true;
        }
        if ($include) {
            return false;
        }

        $_page = mb_substr(
            $page,
            0,
            mb_strpos($page . '?', '?')
        );
        if (in_array($_page, $whitelist)) {
            return true;
        }

        $_page = urldecode($page);
        $_page = mb_substr(
            $_page,
            0,
            mb_strpos($_page . '?', '?')
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
    public static function getenv($var_name)
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
     * @return void
     */
    public static function sendHeaderLocation($uri, $use_refresh = false)
    {
        if ($GLOBALS['PMA_Config']->get('PMA_IS_IIS') && mb_strlen($uri) > 600) {
            Response::getInstance()->disable();

            echo Template::get('header_location')
                ->render(array('uri' => $uri));

            return;
        }

        /*
         * Avoid relative path redirect problems in case user entered URL
         * like /phpmyadmin/index.php/ which some web servers happily accept.
         */
        if ($uri[0] == '.') {
            $uri = $GLOBALS['PMA_Config']->getRootPath() . substr($uri, 2);
        }

        $response = Response::getInstance();

        session_write_close();
        if ($response->headersSent()) {
            trigger_error(
                'Core::sendHeaderLocation called when headers are already sent!',
                E_USER_ERROR
            );
        }
        // bug #1523784: IE6 does not like 'Refresh: 0', it
        // results in a blank page
        // but we need it when coming from the cookie login panel)
        if ($GLOBALS['PMA_Config']->get('PMA_IS_IIS') && $use_refresh) {
            $response->header('Refresh: 0; ' . $uri);
        } else {
            $response->header('Location: ' . $uri);
        }
    }

    /**
     * Outputs application/json headers. This includes no caching.
     *
     * @return void
     */
    public static function headerJSON()
    {
        if (defined('TESTSUITE')) {
            return;
        }
        // No caching
        self::noCacheHeader();
        // MIME type
        header('Content-Type: application/json; charset=UTF-8');
        // Disable content sniffing in browser
        // This is needed in case we include HTML in JSON, browser might assume it's
        // html to display
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Outputs headers to prevent caching in browser (and on the way).
     *
     * @return void
     */
    public static function noCacheHeader()
    {
        if (defined('TESTSUITE')) {
            return;
        }
        // rfc2616 - Section 14.21
        header('Expires: ' . gmdate(DATE_RFC1123));
        // HTTP/1.1
        header(
            'Cache-Control: no-store, no-cache, must-revalidate,'
            . '  pre-check=0, post-check=0, max-age=0'
        );

        header('Pragma: no-cache'); // HTTP/1.0
        // test case: exporting a database into a .gz file with Safari
        // would produce files not having the current time
        // (added this header for Safari but should not harm other browsers)
        header('Last-Modified: ' . gmdate(DATE_RFC1123));
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
    public static function downloadHeader($filename, $mimetype, $length = 0, $no_cache = true)
    {
        if ($no_cache) {
            self::noCacheHeader();
        }
        /* Replace all possibly dangerous chars in filename */
        $filename = Sanitize::sanitizeFilename($filename);
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
    public static function arrayRead($path, array $array, $default = null)
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
    public static function arrayWrite($path, array &$array, $value)
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
    public static function arrayRemove($path, array &$array)
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
    public static function linkURL($url)
    {
        if (!preg_match('#^https?://#', $url)) {
            return $url;
        }

        $params = array();
        $params['url'] = $url;

        $url = Url::getCommon($params);
        //strip off token and such sensitive information. Just keep url.
        $arr = parse_url($url);
        parse_str($arr["query"], $vars);
        $query = http_build_query(array("url" => $vars["url"]));

        if (!is_null($GLOBALS['PMA_Config']) && $GLOBALS['PMA_Config']->get('is_setup')) {
            $url = '../url.php?' . $query;
        } else {
            $url = './url.php?' . $query;
        }

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
    public static function isAllowedDomain($url)
    {
        $arr = parse_url($url);
        // We need host to be set
        if (! isset($arr['host']) || strlen($arr['host']) == 0) {
            return false;
        }
        // We do not want these to be present
        $blocked = array('user', 'pass', 'port');
        foreach ($blocked as $part) {
            if (isset($arr[$part]) && strlen($arr[$part]) != 0) {
                return false;
            }
        }
        $domain = $arr["host"];
        $domainWhiteList = array(
            /* Include current domain */
            $_SERVER['SERVER_NAME'],
            /* phpMyAdmin domains */
            'wiki.phpmyadmin.net',
            'www.phpmyadmin.net',
            'phpmyadmin.net',
            'demo.phpmyadmin.net',
            'docs.phpmyadmin.net',
            /* mysql.com domains */
            'dev.mysql.com','bugs.mysql.com',
            /* mariadb domains */
            'mariadb.org', 'mariadb.com',
            /* php.net domains */
            'php.net',
            'secure.php.net',
            /* Github domains*/
            'github.com','www.github.com',
            /* Percona domains */
            'www.percona.com',
            /* Following are doubtful ones. */
            'mysqldatabaseadministration.blogspot.com',
        );

        return in_array($domain, $domainWhiteList);
    }

    /**
     * Replace some html-unfriendly stuff
     *
     * @param string $buffer String to process
     *
     * @return string Escaped and cleaned up text suitable for html
     */
    public static function mimeDefaultFunction($buffer)
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
    public static function previewSQL($query_data)
    {
        $retval = '<div class="preview_sql">';
        if (empty($query_data)) {
            $retval .= __('No change');
        } elseif (is_array($query_data)) {
            foreach ($query_data as $query) {
                $retval .= Util::formatSql($query);
            }
        } else {
            $retval .= Util::formatSql($query_data);
        }
        $retval .= '</div>';
        $response = Response::getInstance();
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
    public static function emptyRecursive($value)
    {
        $empty = true;
        if (is_array($value)) {
            array_walk_recursive(
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
    public static function setPostAsGlobal(array $post_patterns)
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
    public static function setGlobalDbOrTable($param)
    {
        $GLOBALS[$param] = '';
        if (self::isValid($_REQUEST[$param])) {
            // can we strip tags from this?
            // only \ and / is not allowed in db names for MySQL
            $GLOBALS[$param] = $_REQUEST[$param];
            $GLOBALS['url_params'][$param] = $GLOBALS[$param];
        }
    }

    /**
     * PATH_INFO could be compromised if set, so remove it from PHP_SELF
     * and provide a clean PHP_SELF here
     *
     * @return void
     */
    public static function cleanupPathInfo()
    {
        global $PMA_PHP_SELF;

        $PMA_PHP_SELF = self::getenv('PHP_SELF');
        if (empty($PMA_PHP_SELF)) {
            $PMA_PHP_SELF = urldecode(self::getenv('REQUEST_URI'));
        }
        $_PATH_INFO = self::getenv('PATH_INFO');
        if (! empty($_PATH_INFO) && ! empty($PMA_PHP_SELF)) {
            $question_pos = mb_strpos($PMA_PHP_SELF, '?');
            if ($question_pos != false) {
                $PMA_PHP_SELF = mb_substr($PMA_PHP_SELF, 0, $question_pos);
            }
            $path_info_pos = mb_strrpos($PMA_PHP_SELF, $_PATH_INFO);
            if ($path_info_pos !== false) {
                $path_info_part = mb_substr($PMA_PHP_SELF, $path_info_pos, mb_strlen($_PATH_INFO));
                if ($path_info_part == $_PATH_INFO) {
                    $PMA_PHP_SELF = mb_substr($PMA_PHP_SELF, 0, $path_info_pos);
                }
            }
        }

        $path = [];
        foreach(explode('/', $PMA_PHP_SELF) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') continue;

            if ($part !== '..') {
                // cool, we found a new part
                array_push($path, $part);
            } elseif (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            }
            // Here we intentionall ignore case where we go too up
            // as there is nothing sane to do
        }

        $PMA_PHP_SELF = htmlspecialchars('/' . join('/', $path));
    }

    /**
     * Checks that required PHP extensions are there.
     * @return void
     */
    public static function checkExtensions()
    {
        /**
         * Warning about mbstring.
         */
        if (! function_exists('mb_detect_encoding')) {
            self::warnMissingExtension('mbstring');
        }

        /**
         * We really need this one!
         */
        if (! function_exists('preg_replace')) {
            self::warnMissingExtension('pcre', true);
        }

        /**
         * JSON is required in several places.
         */
        if (! function_exists('json_encode')) {
            self::warnMissingExtension('json', true);
        }

        /**
         * ctype is required for Twig.
         */
        if (! function_exists('ctype_alpha')) {
            self::warnMissingExtension('ctype', true);
        }

        /**
         * hash is required for cookie authentication.
         */
        if (! function_exists('hash_hmac')) {
            self::warnMissingExtension('hash', true);
        }
    }

    /**
     * Gets the "true" IP address of the current user
     *
     * @return string   the ip of the user
     *
     * @access  private
     */
    public static function getIp()
    {
        /* Get the address of user */
        if (empty($_SERVER['REMOTE_ADDR'])) {
            /* We do not know remote IP */
            return false;
        }

        $direct_ip = $_SERVER['REMOTE_ADDR'];

        /* Do we trust this IP as a proxy? If yes we will use it's header. */
        if (!isset($GLOBALS['cfg']['TrustedProxies'][$direct_ip])) {
            /* Return true IP */
            return $direct_ip;
        }

        /**
         * Parse header in form:
         * X-Forwarded-For: client, proxy1, proxy2
         */
        // Get header content
        $value = self::getenv($GLOBALS['cfg']['TrustedProxies'][$direct_ip]);
        // Grab first element what is client adddress
        $value = explode(',', $value)[0];
        // checks that the header contains only one IP address,
        $is_ip = filter_var($value, FILTER_VALIDATE_IP);

        if ($is_ip !== false) {
            // True IP behind a proxy
            return $value;
        }

        // We could not parse header
        return false;
    } // end of the 'getIp()' function

    /**
     * Sanitizes MySQL hostname
     *
     * * strips p: prefix(es)
     *
     * @param string $name User given hostname
     *
     * @return string
     */
    public static function sanitizeMySQLHost($name)
    {
        while (strtolower(substr($name, 0, 2)) == 'p:') {
            $name = substr($name, 2);
        }

        return $name;
    }

    /**
     * Sanitizes MySQL username
     *
     * * strips part behind null byte
     *
     * @param string $name User given username
     *
     * @return string
     */
    public static function sanitizeMySQLUser($name)
    {
        $position = strpos($name, chr(0));
        if ($position !== false) {
            return substr($name, 0, $position);
        }
        return $name;
    }

    /**
     * Safe unserializer wrapper
     *
     * It does not unserialize data containing objects
     *
     * @param string $data Data to unserialize
     *
     * @return mixed
     */
    public static function safeUnserialize($data)
    {
        if (! is_string($data)) {
            return null;
        }

        /* validate serialized data */
        $length = strlen($data);
        $depth = 0;
        for ($i = 0; $i < $length; $i++) {
            $value = $data[$i];

            switch ($value)
            {
                case '}':
                    /* end of array */
                    if ($depth <= 0) {
                        return null;
                    }
                    $depth--;
                    break;
                case 's':
                    /* string */
                    // parse sting length
                    $strlen = intval(substr($data, $i + 2));
                    // string start
                    $i = strpos($data, ':', $i + 2);
                    if ($i === false) {
                        return null;
                    }
                    // skip string, quotes and ;
                    $i += 2 + $strlen + 1;
                    if ($data[$i] != ';') {
                        return null;
                    }
                    break;

                case 'b':
                case 'i':
                case 'd':
                    /* bool, integer or double */
                    // skip value to sepearator
                    $i = strpos($data, ';', $i);
                    if ($i === false) {
                        return null;
                    }
                    break;
                case 'a':
                    /* array */
                    // find array start
                    $i = strpos($data, '{', $i);
                    if ($i === false) {
                        return null;
                    }
                    // remember nesting
                    $depth++;
                    break;
                case 'N':
                    /* null */
                    // skip to end
                    $i = strpos($data, ';', $i);
                    if ($i === false) {
                        return null;
                    }
                    break;
                default:
                    /* any other elements are not wanted */
                    return null;
            }
        }

        // check unterminated arrays
        if ($depth > 0) {
            return null;
        }

        return unserialize($data);
    }

    /**
     * Applies changes to PHP configuration.
     *
     * @return void
     */
    public static function configure()
    {
        /**
         * Set utf-8 encoding for PHP
         */
        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        /**
         * Set precision to sane value, with higher values
         * things behave slightly unexpectedly, for example
         * round(1.2, 2) returns 1.199999999999999956.
         */
        ini_set('precision', 14);

        /**
         * check timezone setting
         * this could produce an E_WARNING - but only once,
         * if not done here it will produce E_WARNING on every date/time function
         */
        date_default_timezone_set(@date_default_timezone_get());
    }

    /**
     * Check whether PHP configuration matches our needs.
     *
     * @return void
     */
    public static function checkConfiguration()
    {
        /**
         * As we try to handle charsets by ourself, mbstring overloads just
         * break it, see bug 1063821.
         *
         * We specifically use empty here as we are looking for anything else than
         * empty value or 0.
         */
        if (extension_loaded('mbstring') && !empty(ini_get('mbstring.func_overload'))) {
            self::fatalError(
                __(
                    'You have enabled mbstring.func_overload in your PHP '
                    . 'configuration. This option is incompatible with phpMyAdmin '
                    . 'and might cause some data to be corrupted!'
                )
            );
        }

        /**
         * The ini_set and ini_get functions can be disabled using
         * disable_functions but we're relying quite a lot of them.
         */
        if (! function_exists('ini_get') || ! function_exists('ini_set')) {
            self::fatalError(
                __(
                    'You have disabled ini_get and/or ini_set in php.ini. '
                    . 'This option is incompatible with phpMyAdmin!'
                )
            );
        }
    }

    /**
     * prints list item for main page
     *
     * @param string $name            displayed text
     * @param string $listId          id, used for css styles
     * @param string $url             make item as link with $url as target
     * @param string $mysql_help_page display a link to MySQL's manual
     * @param string $target          special target for $url
     * @param string $a_id            id for the anchor,
     *                                used for jQuery to hook in functions
     * @param string $class           class for the li element
     * @param string $a_class         class for the anchor element
     *
     * @return void
     */
    public static function printListItem($name, $listId = null, $url = null,
        $mysql_help_page = null, $target = null, $a_id = null, $class = null,
        $a_class = null
    ) {
        echo Template::get('list/item')
            ->render(
                array(
                    'content' => $name,
                    'id' => $listId,
                    'class' => $class,
                    'url' => array(
                        'href' => $url,
                        'target' => $target,
                        'id' => $a_id,
                        'class' => $a_class,
                    ),
                    'mysql_help_page' => $mysql_help_page,
                )
            );
    }

    /**
     * Checks request and fails with fatal error if something problematic is found
     *
     * @return void
     */
    public static function checkRequest()
    {
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            self::fatalError(__("GLOBALS overwrite attempt"));
        }

        /**
         * protect against possible exploits - there is no need to have so much variables
         */
        if (count($_REQUEST) > 1000) {
            self::fatalError(__('possible exploit'));
        }
    }

    /**
     * Sign the sql query using hmac using the session token
     *
     * @param string $sqlQuery The sql query
     * @return string
     */
    public static function signSqlQuery($sqlQuery)
    {
        /** @var array $cfg */
        global $cfg;
        return hash_hmac('sha256', $sqlQuery, $_SESSION[' HMAC_secret '] . $cfg['blowfish_secret']);
    }

    /**
     * Check that the sql query has a valid hmac signature
     *
     * @param string $sqlQuery The sql query
     * @param string $signature The Signature to check
     * @return bool
     */
    public static function checkSqlQuerySignature($sqlQuery, $signature)
    {
        /** @var array $cfg */
        global $cfg;
        $hmac = hash_hmac('sha256', $sqlQuery, $_SESSION[' HMAC_secret '] . $cfg['blowfish_secret']);
        return hash_equals($hmac, $signature);
    }
}
