<?php
/**
 * Core functions used all over the scripts.
 * This script is distinct from libraries/common.inc.php because this
 * script is called from /test.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Plugins\AuthenticationPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use const DATE_RFC1123;
use const E_USER_ERROR;
use const E_USER_WARNING;
use const FILTER_VALIDATE_IP;
use function array_keys;
use function array_pop;
use function array_walk_recursive;
use function chr;
use function count;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function defined;
use function explode;
use function extension_loaded;
use function filter_var;
use function function_exists;
use function getenv;
use function gettype;
use function gmdate;
use function hash_equals;
use function hash_hmac;
use function header;
use function htmlspecialchars;
use function http_build_query;
use function implode;
use function in_array;
use function ini_get;
use function ini_set;
use function intval;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function json_encode;
use function mb_internal_encoding;
use function mb_strlen;
use function mb_strpos;
use function mb_strrpos;
use function mb_substr;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_replace;
use function session_id;
use function session_write_close;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtr;
use function substr;
use function trigger_error;
use function unserialize;
use function urldecode;
use function vsprintf;

/**
 * Core class
 */
class Core
{
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
     * @see self::isValid()
     *
     * @param mixed $var     param to check
     * @param mixed $default default value
     * @param mixed $type    var type or array of values to check against $var
     *
     * @return mixed $var or $default
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
     * @see https://secure.php.net/gettype
     *
     * @param mixed $var     variable to check
     * @param mixed $type    var type or array of valid values to check against $var
     * @param mixed $compare var to compare with $var
     *
     * @return bool whether valid or not
     *
     * @todo add some more var types like hex, bin, ...?
     */
    public static function isValid(&$var, $type = 'length', $compare = null): bool
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
            case 'identic':
                $type = 'identical';
                break;
            case 'len':
                $type = 'length';
                break;
            case 'bool':
                $type = 'boolean';
                break;
            case 'float':
                $type = 'double';
                break;
            case 'int':
                $type = 'integer';
                break;
            case 'null':
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
                return strlen((string) $var) > 0;
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
     */
    public static function securePath(string $path): string
    {
        // change .. to .
        return (string) preg_replace('@\.\.*@', '.', $path);
    }

    /**
     * displays the given error message on phpMyAdmin error page in foreign language,
     * ends script execution and closes session
     *
     * loads language file if not loaded already
     *
     * @param string       $error_message the error message or named error message
     * @param string|array $message_args  arguments applied to $error_message
     */
    public static function fatalError(
        string $error_message,
        $message_args = null
    ): void {
        global $dbi;

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
        if (isset($dbi, $GLOBALS['PMA_Config']) && $dbi !== null
            && $GLOBALS['PMA_Config']->get('is_setup') === false
            && Response::getInstance()->isAjax()
        ) {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', Message::error($error_message));
        } elseif (! empty($_REQUEST['ajax_request'])) {
            // Generate JSON manually
            self::headerJSON();
            echo json_encode(
                [
                    'success' => false,
                    'message' => Message::error($error_message)->getDisplay(),
                ]
            );
        } else {
            $error_message = strtr($error_message, ['<br>' => '[br]']);
            $template = new Template();

            echo $template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => $GLOBALS['text_dir'] ?? 'ltr',
                'error_message' => Sanitize::sanitizeMessage($error_message),
            ]);
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
     * @access public
     */
    public static function getPHPDocLink(string $target): string
    {
        /* List of PHP documentation translations */
        $php_doc_languages = [
            'pt_BR',
            'zh',
            'fr',
            'de',
            'it',
            'ja',
            'pl',
            'ro',
            'ru',
            'fa',
            'es',
            'tr',
        ];

        $lang = 'en';
        if (isset($GLOBALS['lang']) && in_array($GLOBALS['lang'], $php_doc_languages)) {
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
     */
    public static function warnMissingExtension(
        string $extension,
        bool $fatal = false,
        string $extra = ''
    ): void {
        /** @var ErrorHandler $error_handler */
        global $error_handler;

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

        $error_handler->addError(
            $message,
            E_USER_WARNING,
            '',
            0,
            false
        );
    }

    /**
     * returns count of tables in given db
     *
     * @param string $db database to count tables for
     *
     * @return int count of tables in $db
     */
    public static function getTableCount(string $db): int
    {
        global $dbi;

        $tables = $dbi->tryQuery(
            'SHOW TABLES FROM ' . Util::backquote($db) . ';',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        if ($tables) {
            $num_tables = $dbi->numRows($tables);
            $dbi->freeResult($tables);
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
     */
    public static function getRealSize($size = 0): int
    {
        if (! $size) {
            return 0;
        }

        $binaryprefixes = [
            'T' => 1099511627776,
            't' => 1099511627776,
            'G' =>    1073741824,
            'g' =>    1073741824,
            'M' =>       1048576,
            'm' =>       1048576,
            'K' =>          1024,
            'k' =>          1024,
        ];

        if (preg_match('/^([0-9]+)([KMGT])/i', (string) $size, $matches)) {
            return (int) ($matches[1] * $binaryprefixes[$matches[2]]);
        }

        return (int) $size;
    }

    /**
     * Checks given $page against given $allowList and returns true if valid
     * it optionally ignores query parameters in $page (script.php?ignored)
     *
     * @param string $page      page to check
     * @param array  $allowList allow list to check page against
     * @param bool   $include   whether the page is going to be included
     *
     * @return bool whether $page is valid or not (in $allowList or not)
     */
    public static function checkPageValidity(&$page, array $allowList = [], $include = false): bool
    {
        if (empty($allowList)) {
            $allowList = ['index.php'];
        }
        if (empty($page)) {
            return false;
        }

        if (in_array($page, $allowList)) {
            return true;
        }
        if ($include) {
            return false;
        }

        $_page = mb_substr(
            $page,
            0,
            (int) mb_strpos($page . '?', '?')
        );
        if (in_array($_page, $allowList)) {
            return true;
        }

        $_page = urldecode($page);
        $_page = mb_substr(
            $_page,
            0,
            (int) mb_strpos($_page . '?', '?')
        );

        return in_array($_page, $allowList);
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
    public static function getenv(string $var_name): string
    {
        if (isset($_SERVER[$var_name])) {
            return (string) $_SERVER[$var_name];
        }

        if (isset($_ENV[$var_name])) {
            return (string) $_ENV[$var_name];
        }

        if (getenv($var_name)) {
            return (string) getenv($var_name);
        }

        if (function_exists('apache_getenv')
            && apache_getenv($var_name, true)
        ) {
            return (string) apache_getenv($var_name, true);
        }

        return '';
    }

    /**
     * Send HTTP header, taking IIS limits into account (600 seems ok)
     *
     * @param string $uri         the header to send
     * @param bool   $use_refresh whether to use Refresh: header when running on IIS
     */
    public static function sendHeaderLocation(string $uri, bool $use_refresh = false): void
    {
        if ($GLOBALS['PMA_Config']->get('PMA_IS_IIS') && mb_strlen($uri) > 600) {
            Response::getInstance()->disable();

            $template = new Template();
            echo $template->render('header_location', ['uri' => $uri]);

            return;
        }

        /*
         * Avoid relative path redirect problems in case user entered URL
         * like /phpmyadmin/index.php/ which some web servers happily accept.
         */
        if ($uri[0] === '.') {
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
     */
    public static function headerJSON(): void
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
     */
    public static function noCacheHeader(): void
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
     */
    public static function downloadHeader(
        string $filename,
        string $mimetype,
        int $length = 0,
        bool $no_cache = true
    ): void {
        if ($no_cache) {
            self::noCacheHeader();
        }
        /* Replace all possibly dangerous chars in filename */
        $filename = Sanitize::sanitizeFilename($filename);
        if (! empty($filename)) {
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
        if ($length <= 0) {
            return;
        }

        header('Content-Length: ' . $length);
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
     * @return array|mixed|null array element or $default
     */
    public static function arrayRead(string $path, array $array, $default = null)
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
     * @param string $path  path in the array
     * @param array  $array the array
     * @param mixed  $value value to store
     */
    public static function arrayWrite(string $path, array &$array, $value): void
    {
        $keys = explode('/', $path);
        $last_key = array_pop($keys);
        $a =& $array;
        foreach ($keys as $key) {
            if (! isset($a[$key])) {
                $a[$key] = [];
            }
            $a =& $a[$key];
        }
        $a[$last_key] = $value;
    }

    /**
     * Removes value from an array
     *
     * @param string $path  path in the array
     * @param array  $array the array
     */
    public static function arrayRemove(string $path, array &$array): void
    {
        $keys = explode('/', $path);
        $keys_last = array_pop($keys);
        $path = [];
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
            if (isset($path[$depth + 1]) && count($path[$depth + 1]) !== 0) {
                break;
            }

            unset($path[$depth][$keys[$depth]]);
        }
    }

    /**
     * Returns link to (possibly) external site using defined redirector.
     *
     * @param string $url URL where to go.
     *
     * @return string URL for a link.
     */
    public static function linkURL(string $url): string
    {
        if (! preg_match('#^https?://#', $url)) {
            return $url;
        }

        $params = [];
        $params['url'] = $url;

        $url = Url::getCommon($params);
        //strip off token and such sensitive information. Just keep url.
        $arr = parse_url($url);

        if (! is_array($arr)) {
            $arr = [];
        }

        parse_str($arr['query'] ?? '', $vars);
        $query = http_build_query(['url' => $vars['url']]);

        if ($GLOBALS['PMA_Config'] !== null && $GLOBALS['PMA_Config']->get('is_setup')) {
            $url = '../url.php?' . $query;
        } else {
            $url = './url.php?' . $query;
        }

        return $url;
    }

    /**
     * Checks whether domain of URL is an allowed domain or not.
     * Use only for URLs of external sites.
     *
     * @param string $url URL of external site.
     *
     * @return bool True: if domain of $url is allowed domain,
     * False: otherwise.
     */
    public static function isAllowedDomain(string $url): bool
    {
        $arr = parse_url($url);

        if (! is_array($arr)) {
            $arr = [];
        }

        // We need host to be set
        if (! isset($arr['host']) || strlen($arr['host']) == 0) {
            return false;
        }
        // We do not want these to be present
        $blocked = [
            'user',
            'pass',
            'port',
        ];
        foreach ($blocked as $part) {
            if (isset($arr[$part]) && strlen((string) $arr[$part]) != 0) {
                return false;
            }
        }
        $domain = $arr['host'];
        $domainAllowList = [
            /* Include current domain */
            $_SERVER['SERVER_NAME'],
            /* phpMyAdmin domains */
            'wiki.phpmyadmin.net',
            'www.phpmyadmin.net',
            'phpmyadmin.net',
            'demo.phpmyadmin.net',
            'docs.phpmyadmin.net',
            /* mysql.com domains */
            'dev.mysql.com',
            'bugs.mysql.com',
            /* mariadb domains */
            'mariadb.org',
            'mariadb.com',
            /* php.net domains */
            'php.net',
            'secure.php.net',
            /* Github domains*/
            'github.com',
            'www.github.com',
            /* Percona domains */
            'www.percona.com',
            /* Following are doubtful ones. */
            'mysqldatabaseadministration.blogspot.com',
        ];

        return in_array($domain, $domainAllowList);
    }

    /**
     * Replace some html-unfriendly stuff
     *
     * @param string $buffer String to process
     *
     * @return string Escaped and cleaned up text suitable for html
     */
    public static function mimeDefaultFunction(string $buffer): string
    {
        $buffer = htmlspecialchars($buffer);
        $buffer = str_replace('  ', ' &nbsp;', $buffer);

        return (string) preg_replace("@((\015\012)|(\015)|(\012))@", '<br>' . "\n", $buffer);
    }

    /**
     * Displays SQL query before executing.
     *
     * @param array|string $query_data Array containing queries or query itself
     */
    public static function previewSQL($query_data): void
    {
        $retval = '<div class="preview_sql">';
        if (empty($query_data)) {
            $retval .= __('No change');
        } elseif (is_array($query_data)) {
            foreach ($query_data as $query) {
                $retval .= Html\Generator::formatSql($query);
            }
        } else {
            $retval .= Html\Generator::formatSql($query_data);
        }
        $retval .= '</div>';
        $response = Response::getInstance();
        $response->addJSON('sql_data', $retval);
    }

    /**
     * recursively check if variable is empty
     *
     * @param mixed $value the variable
     *
     * @return bool true if empty
     */
    public static function emptyRecursive($value): bool
    {
        $empty = true;
        if (is_array($value)) {
            array_walk_recursive(
                $value,
                /**
                 * @param mixed $item
                 */
                static function ($item) use (&$empty) {
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
     */
    public static function setPostAsGlobal(array $post_patterns): void
    {
        global $containerBuilder;

        foreach (array_keys($_POST) as $post_key) {
            foreach ($post_patterns as $one_post_pattern) {
                if (! preg_match($one_post_pattern, $post_key)) {
                    continue;
                }

                $GLOBALS[$post_key] = $_POST[$post_key];
                $containerBuilder->setParameter($post_key, $GLOBALS[$post_key]);
            }
        }
    }

    public static function setDatabaseAndTableFromRequest(ContainerInterface $containerBuilder): void
    {
        global $db, $table, $url_params;

        $databaseFromRequest = $_POST['db'] ?? $_GET['db'] ?? $_REQUEST['db'] ?? null;
        $tableFromRequest = $_POST['table'] ?? $_GET['table'] ?? $_REQUEST['table'] ?? null;

        $db = self::isValid($databaseFromRequest) ? $databaseFromRequest : '';
        $table = self::isValid($tableFromRequest) ? $tableFromRequest : '';

        $url_params['db'] = $db;
        $url_params['table'] = $table;
        $containerBuilder->setParameter('db', $db);
        $containerBuilder->setParameter('table', $table);
        $containerBuilder->setParameter('url_params', $url_params);
    }

    /**
     * PATH_INFO could be compromised if set, so remove it from PHP_SELF
     * and provide a clean PHP_SELF here
     */
    public static function cleanupPathInfo(): void
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
        foreach (explode('/', $PMA_PHP_SELF) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') {
                continue;
            }

            if ($part !== '..') {
                // cool, we found a new part
                $path[] = $part;
            } elseif (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            }
            // Here we intentionall ignore case where we go too up
            // as there is nothing sane to do
        }

        $PMA_PHP_SELF = htmlspecialchars('/' . implode('/', $path));
    }

    /**
     * Checks that required PHP extensions are there.
     */
    public static function checkExtensions(): void
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
        if (function_exists('hash_hmac')) {
            return;
        }

        self::warnMissingExtension('hash', true);
    }

    /**
     * Gets the "true" IP address of the current user
     *
     * @return string|bool the ip of the user
     *
     * @access private
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
        if (! isset($GLOBALS['cfg']['TrustedProxies'][$direct_ip])) {
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
    }

    /**
     * Sanitizes MySQL hostname
     *
     * * strips p: prefix(es)
     *
     * @param string $name User given hostname
     */
    public static function sanitizeMySQLHost(string $name): string
    {
        while (strtolower(substr($name, 0, 2)) === 'p:') {
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
     */
    public static function sanitizeMySQLUser(string $name): string
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
     * @return mixed|null
     */
    public static function safeUnserialize(string $data)
    {
        if (! is_string($data)) {
            return null;
        }

        /* validate serialized data */
        $length = strlen($data);
        $depth = 0;
        for ($i = 0; $i < $length; $i++) {
            $value = $data[$i];

            switch ($value) {
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
                    if ($data[$i] !== ';') {
                        return null;
                    }
                    break;

                case 'b':
                case 'i':
                case 'd':
                    /* bool, integer or double */
                    // skip value to separator
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
     */
    public static function configure(): void
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
        ini_set('precision', '14');

        /**
         * check timezone setting
         * this could produce an E_WARNING - but only once,
         * if not done here it will produce E_WARNING on every date/time function
         */
        date_default_timezone_set(@date_default_timezone_get());
    }

    /**
     * Check whether PHP configuration matches our needs.
     */
    public static function checkConfiguration(): void
    {
        /**
         * As we try to handle charsets by ourself, mbstring overloads just
         * break it, see bug 1063821.
         *
         * We specifically use empty here as we are looking for anything else than
         * empty value or 0.
         */
        if (extension_loaded('mbstring') && ! empty(ini_get('mbstring.func_overload'))) {
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
        if (function_exists('ini_get') && function_exists('ini_set')) {
            return;
        }

        self::fatalError(
            __(
                'The ini_get and/or ini_set functions are disabled in php.ini. '
                . 'phpMyAdmin requires these functions!'
            )
        );
    }

    /**
     * Checks request and fails with fatal error if something problematic is found
     */
    public static function checkRequest(): void
    {
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            self::fatalError(__('GLOBALS overwrite attempt'));
        }

        /**
         * protect against possible exploits - there is no need to have so much variables
         */
        if (count($_REQUEST) <= 1000) {
            return;
        }

        self::fatalError(__('possible exploit'));
    }

    /**
     * Sign the sql query using hmac using the session token
     *
     * @param string $sqlQuery The sql query
     *
     * @return string
     */
    public static function signSqlQuery($sqlQuery)
    {
        global $cfg;

        $secret = $_SESSION[' HMAC_secret '] ?? '';

        return hash_hmac('sha256', $sqlQuery, $secret . $cfg['blowfish_secret']);
    }

    /**
     * Check that the sql query has a valid hmac signature
     *
     * @param string $sqlQuery  The sql query
     * @param string $signature The Signature to check
     *
     * @return bool
     */
    public static function checkSqlQuerySignature($sqlQuery, $signature)
    {
        global $cfg;

        $secret = $_SESSION[' HMAC_secret '] ?? '';
        $hmac = hash_hmac('sha256', $sqlQuery, $secret . $cfg['blowfish_secret']);

        return hash_equals($hmac, $signature);
    }

    /**
     * Check whether user supplied token is valid, if not remove any possibly
     * dangerous stuff from request.
     *
     * Check for token mismatch only if the Request method is POST.
     * GET Requests would never have token and therefore checking
     * mis-match does not make sense.
     */
    public static function checkTokenRequestParam(): void
    {
        global $token_mismatch, $token_provided;

        $token_mismatch = true;
        $token_provided = false;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (self::isValid($_POST['token'])) {
            $token_provided = true;
            $token_mismatch = ! @hash_equals($_SESSION[' PMA_token '], $_POST['token']);
        }

        if (! $token_mismatch) {
            return;
        }

        // Warn in case the mismatch is result of failed setting of session cookie
        if (isset($_POST['set_session']) && $_POST['set_session'] !== session_id()) {
            trigger_error(
                __(
                    'Failed to set session cookie. Maybe you are using '
                    . 'HTTP instead of HTTPS to access phpMyAdmin.'
                ),
                E_USER_ERROR
            );
        }

        /**
         * We don't allow any POST operation parameters if the token is mismatched
         * or is not provided.
         */
        $allowList = ['ajax_request'];
        Sanitize::removeRequestVars($allowList);
    }

    public static function setGotoAndBackGlobals(ContainerInterface $container, Config $config): void
    {
        global $goto, $back, $url_params;

        // Holds page that should be displayed.
        $goto = '';
        $container->setParameter('goto', $goto);

        if (isset($_REQUEST['goto']) && self::checkPageValidity($_REQUEST['goto'])) {
            $goto = $_REQUEST['goto'];
            $url_params['goto'] = $goto;
            $container->setParameter('goto', $goto);
            $container->setParameter('url_params', $url_params);
        } else {
            if ($config->issetCookie('goto')) {
                $config->removeCookie('goto');
            }
            unset($_REQUEST['goto'], $_GET['goto'], $_POST['goto']);
        }

        if (isset($_REQUEST['back']) && self::checkPageValidity($_REQUEST['back'])) {
            // Returning page.
            $back = $_REQUEST['back'];
            $container->setParameter('back', $back);
        } else {
            if ($config->issetCookie('back')) {
                $config->removeCookie('back');
            }
            unset($_REQUEST['back'], $_GET['back'], $_POST['back']);
        }
    }

    public static function connectToDatabaseServer(DatabaseInterface $dbi, AuthenticationPlugin $auth): void
    {
        global $cfg;

        /**
         * Try to connect MySQL with the control user profile (will be used to get the privileges list for the current
         * user but the true user link must be open after this one so it would be default one for all the scripts).
         */
        $controlLink = false;
        if ($cfg['Server']['controluser'] !== '') {
            $controlLink = $dbi->connect(DatabaseInterface::CONNECT_CONTROL);
        }

        // Connects to the server (validates user's login)
        $userLink = $dbi->connect(DatabaseInterface::CONNECT_USER);

        if ($userLink === false) {
            $auth->showFailure('mysql-denied');
        }

        if ($controlLink) {
            return;
        }

        /**
         * Open separate connection for control queries, this is needed to avoid problems with table locking used in
         * main connection and phpMyAdmin issuing queries to configuration storage, which is not locked by that time.
         */
        $dbi->connect(DatabaseInterface::CONNECT_USER, null, DatabaseInterface::CONNECT_CONTROL);
    }
}
