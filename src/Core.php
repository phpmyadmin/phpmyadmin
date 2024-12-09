<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Exceptions\MissingExtensionException;
use PhpMyAdmin\Http\ServerRequest;

use function __;
use function array_pop;
use function array_walk_recursive;
use function chr;
use function count;
use function explode;
use function filter_var;
use function function_exists;
use function getenv;
use function gmdate;
use function hash_equals;
use function hash_hmac;
use function header;
use function header_remove;
use function htmlspecialchars;
use function http_build_query;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function json_decode;
use function mb_strpos;
use function mb_substr;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function stripos;
use function strlen;
use function strpos;
use function substr;
use function unserialize;
use function urldecode;

use const DATE_RFC1123;
use const E_USER_WARNING;
use const FILTER_VALIDATE_IP;

/**
 * Core functions used all over the scripts.
 */
class Core
{
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
     * Returns a link to the PHP documentation
     *
     * @param string $target anchor in documentation
     *
     * @return string  the URL
     */
    public static function getPHPDocLink(string $target): string
    {
        /* List of PHP documentation translations */
        $phpDocLanguages = ['pt_BR', 'zh_CN', 'fr', 'de', 'ja', 'ru', 'es', 'tr'];

        $lang = 'en';
        if (isset($GLOBALS['lang']) && in_array($GLOBALS['lang'], $phpDocLanguages, true)) {
            $lang = $GLOBALS['lang'] === 'zh_CN' ? 'zh' : $GLOBALS['lang'];
        }

        return self::linkURL('https://www.php.net/manual/' . $lang . '/' . $target);
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
        string $extra = '',
    ): void {
        $message = 'The %s extension is missing. Please check your PHP configuration.';

        /* Gettext does not have to be loaded yet here */
        if (function_exists('__')) {
            $message = __('The %s extension is missing. Please check your PHP configuration.');
        }

        $doclink = self::getPHPDocLink('book.' . $extension . '.php');
        $message = sprintf($message, '[a@' . $doclink . '@Documentation][em]' . $extension . '[/em][/a]');
        if ($extra !== '') {
            $message .= ' ' . $extra;
        }

        if ($fatal) {
            throw new MissingExtensionException(Sanitize::convertBBCode($message));
        }

        ErrorHandler::getInstance()->addError($message, E_USER_WARNING, '', 0, false);
    }

    /**
     * Converts numbers like 10M into bytes
     * Used with permission from Moodle (https://moodle.org) by Martin Dougiamas
     * (renamed with PMA prefix to avoid double definition when embedded
     * in Moodle)
     *
     * @param string|int $size size (Default = 0)
     */
    public static function getRealSize(string|int $size = 0): int
    {
        if (! $size) {
            return 0;
        }

        $binaryprefixes = [
            'T' => 1099511627776,
            't' => 1099511627776,
            'G' => 1073741824,
            'g' => 1073741824,
            'M' => 1048576,
            'm' => 1048576,
            'K' => 1024,
            'k' => 1024,
        ];

        if (preg_match('/^([0-9]+)([KMGT])/i', (string) $size, $matches) === 1) {
            return (int) ($matches[1] * $binaryprefixes[$matches[2]]);
        }

        return (int) $size;
    }

    /**
     * Checks if the given $page is index.php and returns true if valid.
     * It ignores query parameters in $page (script.php?ignored)
     */
    public static function checkPageValidity(string $page): bool
    {
        if ($page === '') {
            return false;
        }

        if ($page === 'index.php') {
            return true;
        }

        $newPage = mb_substr(
            $page,
            0,
            (int) mb_strpos($page . '?', '?'),
        );
        if ($newPage === 'index.php') {
            return true;
        }

        $newPage = urldecode($page);
        $newPage = mb_substr(
            $newPage,
            0,
            (int) mb_strpos($newPage . '?', '?'),
        );

        return $newPage === 'index.php';
    }

    /**
     * Tries to find the value for the given environment variable name
     *
     * Searches in $_SERVER, $_ENV then tries getenv() and apache_getenv() in this order.
     *
     * @psalm-param non-empty-string $variableName
     */
    public static function getEnv(string $variableName): string
    {
        $value = $_SERVER[$variableName] ?? $_ENV[$variableName] ?? getenv($variableName);
        if (is_scalar($value) && (string) $value !== '') {
            return (string) $value;
        }

        if (function_exists('apache_getenv')) {
            return (string) apache_getenv($variableName, true); // @codeCoverageIgnore
        }

        return '';
    }

    /**
     * Returns application/json headers. This includes no caching.
     *
     * @return array<string, string>
     */
    public static function headerJSON(): array
    {
        // No caching
        $headers = self::getNoCacheHeaders();

        // Media type
        $headers['Content-Type'] = 'application/json; charset=UTF-8';

        /**
         * Disable content sniffing in browser.
         * This is needed in case we include HTML in JSON, browser might assume it's html to display.
         */
        $headers['X-Content-Type-Options'] = 'nosniff';

        return $headers;
    }

    /** @return array<string, string> */
    public static function getNoCacheHeaders(): array
    {
        $headers = [];
        $date = gmdate(DATE_RFC1123);

        // rfc2616 - Section 14.21
        $headers['Expires'] = $date;

        // HTTP/1.1
        $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0';

        // HTTP/1.0
        $headers['Pragma'] = 'no-cache';

        // test case: exporting a database into a .gz file with Safari
        // would produce files not having the current time
        // (added this header for Safari but should not harm other browsers)
        $headers['Last-Modified'] = $date;

        return $headers;
    }

    /**
     * Sends header indicating file download.
     *
     * @param string $filename Filename to include in headers if empty,
     *                         none Content-Disposition header will be sent.
     * @param string $mimetype MIME type to include in headers.
     * @param int    $length   Length of content (optional)
     * @param bool   $noCache  Whether to include no-caching headers.
     */
    public static function downloadHeader(
        string $filename,
        string $mimetype,
        int $length = 0,
        bool $noCache = true,
    ): void {
        $headers = [];

        if ($noCache) {
            $headers = self::getNoCacheHeaders();
        }

        /* Replace all possibly dangerous chars in filename */
        $filename = Sanitize::sanitizeFilename($filename);
        if ($filename !== '') {
            $headers['Content-Description'] = 'File Transfer';
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }

        $headers['Content-Type'] = $mimetype;

        // The default output in PMA uses gzip,
        // so if we want to output uncompressed file, we should reset the encoding.
        // See PHP bug https://github.com/php/php-src/issues/8218
        header_remove('Content-Encoding');

        $headers['Content-Transfer-Encoding'] = 'binary';

        if ($length > 0) {
            $headers['Content-Length'] = (string) $length;
        }

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    /**
     * Returns value of an element in $array given by $path.
     * $path is a string describing position of an element in an associative array,
     * eg. Servers/1/host refers to $array[Servers][1][host]
     *
     * @param string  $path    path in the array
     * @param mixed[] $array   the array
     * @param mixed   $default default value
     *
     * @return mixed[]|mixed|null array element or $default
     */
    public static function arrayRead(string $path, array $array, mixed $default = null): mixed
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
     * @param string  $path  path in the array
     * @param mixed[] $array the array
     * @param mixed   $value value to store
     */
    public static function arrayWrite(string $path, array &$array, mixed $value): void
    {
        $keys = explode('/', $path);
        $lastKey = array_pop($keys);
        $a =& $array;
        foreach ($keys as $key) {
            if (! isset($a[$key])) {
                $a[$key] = [];
            }

            $a =& $a[$key];
        }

        $a[$lastKey] = $value;
    }

    /**
     * Removes value from an array
     *
     * @param string  $path  path in the array
     * @param mixed[] $array the array
     */
    public static function arrayRemove(string $path, array &$array): void
    {
        $keys = explode('/', $path);
        $keysLast = array_pop($keys);
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
            unset($path[$depth][$keysLast]);
            $depth--;
        }

        // remove empty nested arrays
        /** @infection-ignore-all */
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
        if (preg_match('#^https?://#', $url) !== 1) {
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

        if (Config::getInstance()->isSetup()) {
            return '../index.php?route=/url&' . $query;
        }

        return 'index.php?route=/url&' . $query;
    }

    /**
     * Checks whether domain of URL is an allowed domain or not.
     * Use only for URLs of external sites.
     *
     * @param string $url URL of external site.
     */
    public static function isAllowedDomain(string $url): bool
    {
        $parsedUrl = parse_url($url);
        if (
            ! is_array($parsedUrl)
            || ! isset($parsedUrl['host'])
            || isset($parsedUrl['user'])
            || isset($parsedUrl['pass'])
            || isset($parsedUrl['port'])
        ) {
            return false;
        }

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
            'www.php.net',
            /* Github domains*/
            'github.com',
            'www.github.com',
            /* Percona domains */
            'www.percona.com',
            /* CVE domain */
            'www.cve.org',
            /* Following are doubtful ones. */
            'mysqldatabaseadministration.blogspot.com',
        ];

        return in_array($parsedUrl['host'], $domainAllowList, true);
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
     * @param mixed[]|string $queryData Array containing queries or query itself
     */
    public static function previewSQL(array|string $queryData): void
    {
        $retval = '<div class="preview_sql">';
        if ($queryData === '' || $queryData === []) {
            $retval .= __('No change');
        } elseif (is_array($queryData)) {
            foreach ($queryData as $query) {
                $retval .= Html\Generator::formatSql($query);
            }
        } else {
            $retval .= Html\Generator::formatSql($queryData);
        }

        $retval .= '</div>';
        $response = ResponseRenderer::getInstance();
        $response->addJSON('sql_data', $retval);
    }

    /**
     * recursively check if variable is empty
     *
     * @param mixed $value the variable
     */
    public static function emptyRecursive(mixed $value): bool
    {
        if (is_array($value)) {
            $empty = true;
            array_walk_recursive(
                $value,
                /** @param mixed $item */
                static function ($item) use (&$empty): void {
                    $empty = $empty && empty($item);
                },
            );

            return $empty;
        }

        return empty($value);
    }

    /**
     * Gets the "true" IP address of the current user
     *
     * @return string|bool the ip of the user
     */
    public static function getIp(): string|bool
    {
        /* Get the address of user */
        if (empty($_SERVER['REMOTE_ADDR'])) {
            /* We do not know remote IP */
            return false;
        }

        $directIp = $_SERVER['REMOTE_ADDR'];

        /* Do we trust this IP as a proxy? If yes we will use it's header. */
        $config = Config::getInstance();
        if (! isset($config->settings['TrustedProxies'][$directIp])) {
            /* Return true IP */
            return $directIp;
        }

        /**
         * Parse header in form:
         * X-Forwarded-For: client, proxy1, proxy2
         */
        // Get header content
        $value = self::getEnv($config->settings['TrustedProxies'][$directIp]);
        // Grab first element what is client adddress
        $value = explode(',', $value)[0];
        // checks that the header contains only one IP address,
        $isIp = filter_var($value, FILTER_VALIDATE_IP);

        if ($isIp !== false) {
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
        while (stripos($name, 'p:') === 0) {
            /** @infection-ignore-all */
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
     */
    public static function safeUnserialize(string $data): mixed
    {
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
                    $strlen = (int) substr($data, $i + 2);
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
     * Sign the sql query using hmac using the session token
     *
     * @param string $sqlQuery The sql query
     */
    public static function signSqlQuery(string $sqlQuery): string
    {
        $secret = $_SESSION[' HMAC_secret '] ?? '';

        return hash_hmac('sha256', $sqlQuery, $secret . Config::getInstance()->settings['blowfish_secret']);
    }

    /**
     * Check that the sql query has a valid hmac signature
     *
     * @param string $sqlQuery  The sql query
     * @param string $signature The Signature to check
     */
    public static function checkSqlQuerySignature(string $sqlQuery, string $signature): bool
    {
        $secret = $_SESSION[' HMAC_secret '] ?? '';
        $hmac = hash_hmac('sha256', $sqlQuery, $secret . Config::getInstance()->settings['blowfish_secret']);

        return hash_equals($hmac, $signature);
    }

    public static function populateRequestWithEncryptedQueryParams(ServerRequest $request): ServerRequest
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        unset($_GET['eq'], $_POST['eq'], $_REQUEST['eq']);

        if (! isset($queryParams['eq']) && (! is_array($parsedBody) || ! isset($parsedBody['eq']))) {
            return $request;
        }

        $encryptedQuery = '';
        if (
            is_array($parsedBody)
            && isset($parsedBody['eq'])
            && is_string($parsedBody['eq'])
            && $parsedBody['eq'] !== ''
        ) {
            $encryptedQuery = $parsedBody['eq'];
            unset($parsedBody['eq'], $queryParams['eq']);
        } elseif (isset($queryParams['eq']) && is_string($queryParams['eq']) && $queryParams['eq'] !== '') {
            $encryptedQuery = $queryParams['eq'];
            unset($queryParams['eq']);
        }

        $decryptedQuery = null;
        if ($encryptedQuery !== '') {
            $decryptedQuery = Url::decryptQuery($encryptedQuery);
        }

        if ($decryptedQuery === null) {
            $request = $request->withQueryParams($queryParams);
            if (is_array($parsedBody)) {
                return $request->withParsedBody($parsedBody);
            }

            return $request;
        }

        $urlQueryParams = (array) json_decode($decryptedQuery);
        foreach ($urlQueryParams as $urlQueryParamKey => $urlQueryParamValue) {
            if (is_array($parsedBody)) {
                $parsedBody[$urlQueryParamKey] = $urlQueryParamValue;
                $_POST[$urlQueryParamKey] = $urlQueryParamValue;
            } else {
                $queryParams[$urlQueryParamKey] = $urlQueryParamValue;
                $_GET[$urlQueryParamKey] = $urlQueryParamValue;
            }

            $_REQUEST[$urlQueryParamKey] = $urlQueryParamValue;
        }

        $request = $request->withQueryParams($queryParams);
        if (is_array($parsedBody)) {
            return $request->withParsedBody($parsedBody);
        }

        return $request;
    }
}
