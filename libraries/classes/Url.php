<?php
/**
 * Static methods for URL/hidden inputs generating
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Crypto\Crypto;

use function htmlentities;
use function htmlspecialchars;
use function http_build_query;
use function ini_get;
use function is_array;
use function mb_strpos;
use function strlen;
use function in_array;
use function json_encode;
use function strtr;
use function base64_encode;
use function base64_decode;

/**
 * Static methods for URL/hidden inputs generating
 */
class Url
{
    /**
     * Generates text with hidden inputs.
     *
     * @see Url::getCommon()
     *
     * @param string|array $db     optional database name
     *                             (can also be an array of parameters)
     * @param string       $table  optional table name
     * @param int          $indent indenting level
     * @param string|array $skip   do not generate a hidden field for this parameter
     *                             (can be an array of strings)
     *
     * @return string   string with input fields
     *
     * @access public
     */
    public static function getHiddenInputs(
        $db = '',
        $table = '',
        $indent = 0,
        $skip = []
    ) {
        global $PMA_Config;

        if (is_array($db)) {
            $params  =& $db;
        } else {
            $params = [];
            if (strlen((string) $db) > 0) {
                $params['db'] = $db;
            }
            if (strlen((string) $table) > 0) {
                $params['table'] = $table;
            }
        }

        if (! empty($GLOBALS['server'])
            && $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']
        ) {
            $params['server'] = $GLOBALS['server'];
        }
        if (empty($PMA_Config->getCookie('pma_lang')) && ! empty($GLOBALS['lang'])) {
            $params['lang'] = $GLOBALS['lang'];
        }

        if (! is_array($skip)) {
            if (isset($params[$skip])) {
                unset($params[$skip]);
            }
        } else {
            foreach ($skip as $skipping) {
                if (! isset($params[$skipping])) {
                    continue;
                }

                unset($params[$skipping]);
            }
        }

        return self::getHiddenFields($params);
    }

    /**
     * create hidden form fields from array with name => value
     *
     * <code>
     * $values = array(
     *     'aaa' => aaa,
     *     'bbb' => array(
     *          'bbb_0',
     *          'bbb_1',
     *     ),
     *     'ccc' => array(
     *          'a' => 'ccc_a',
     *          'b' => 'ccc_b',
     *     ),
     * );
     * echo Url::getHiddenFields($values);
     *
     * // produces:
     * <input type="hidden" name="aaa" Value="aaa">
     * <input type="hidden" name="bbb[0]" Value="bbb_0">
     * <input type="hidden" name="bbb[1]" Value="bbb_1">
     * <input type="hidden" name="ccc[a]" Value="ccc_a">
     * <input type="hidden" name="ccc[b]" Value="ccc_b">
     * </code>
     *
     * @param array  $values   hidden values
     * @param string $pre      prefix
     * @param bool   $is_token if token already added in hidden input field
     *
     * @return string form fields of type hidden
     */
    public static function getHiddenFields(array $values, $pre = '', $is_token = false)
    {
        $fields = '';

        /* Always include token in plain forms */
        if ($is_token === false && isset($_SESSION[' PMA_token '])) {
            $values['token'] = $_SESSION[' PMA_token '];
        }

        foreach ($values as $name => $value) {
            if (! empty($pre)) {
                $name = $pre . '[' . $name . ']';
            }

            if (is_array($value)) {
                $fields .= self::getHiddenFields($value, $name, true);
            } else {
                // do not generate an ending "\n" because
                // Url::getHiddenInputs() is sometimes called
                // from a JS document.write()
                $fields .= '<input type="hidden" name="' . htmlspecialchars((string) $name)
                    . '" value="' . htmlspecialchars((string) $value) . '">';
            }
        }

        return $fields;
    }

    /**
     * Generates text with URL parameters.
     *
     * <code>
     * $params['myparam'] = 'myvalue';
     * $params['db']      = 'mysql';
     * $params['table']   = 'rights';
     * // note the missing ?
     * echo 'script.php' . Url::getCommon($params);
     * // produces with cookies enabled:
     * // script.php?myparam=myvalue&db=mysql&table=rights
     * // with cookies disabled:
     * // script.php?server=1&lang=en&myparam=myvalue&db=mysql
     * // &table=rights
     *
     * // note the missing ?
     * echo 'script.php' . Url::getCommon();
     * // produces with cookies enabled:
     * // script.php
     * // with cookies disabled:
     * // script.php?server=1&lang=en
     * </code>
     *
     * @param array<string,int|string|bool> $params  optional, Contains an associative array with url params
     * @param string                        $divider optional character to use instead of '?'
     * @param bool                          $encrypt whether to encrypt URL params
     *
     * @return string   string with URL parameters
     *
     * @access public
     */
    public static function getCommon(array $params = [], $divider = '?', $encrypt = true)
    {
        return self::getCommonRaw($params, $divider, $encrypt);
    }

    /**
     * Generates text with URL parameters.
     *
     * <code>
     * $params['myparam'] = 'myvalue';
     * $params['db']      = 'mysql';
     * $params['table']   = 'rights';
     * // note the missing ?
     * echo 'script.php' . Url::getCommon($params);
     * // produces with cookies enabled:
     * // script.php?myparam=myvalue&db=mysql&table=rights
     * // with cookies disabled:
     * // script.php?server=1&lang=en&myparam=myvalue&db=mysql
     * // &table=rights
     *
     * // note the missing ?
     * echo 'script.php' . Url::getCommon();
     * // produces with cookies enabled:
     * // script.php
     * // with cookies disabled:
     * // script.php?server=1&lang=en
     * </code>
     *
     * @param array<string|int,int|string|bool> $params  optional, Contains an associative array with url params
     * @param string                            $divider optional character to use instead of '?'
     * @param bool                              $encrypt whether to encrypt URL params
     *
     * @return string   string with URL parameters
     *
     * @access public
     */
    public static function getCommonRaw(array $params = [], $divider = '?', $encrypt = true)
    {
        global $PMA_Config;

        // avoid overwriting when creating navigation panel links to servers
        if (isset($GLOBALS['server'])
            && $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']
            && ! isset($params['server'])
            && ! $PMA_Config->get('is_setup')
        ) {
            $params['server'] = $GLOBALS['server'];
        }

        // Can be null when the user is missing an extension.
        // See: Core::checkExtensions()
        if ($PMA_Config !== null && empty($PMA_Config->getCookie('pma_lang')) && ! empty($GLOBALS['lang'])) {
            $params['lang'] = $GLOBALS['lang'];
        }

        $query = self::buildHttpQuery($params, $encrypt);

        if (($divider !== '?' && $divider !== '&') || strlen($query) > 0) {
            return $divider . $query;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $params
     * @param bool                 $encrypt whether to encrypt URL params
     *
     * @return string
     */
    public static function buildHttpQuery($params, $encrypt = true)
    {
        global $PMA_Config;

        $separator = self::getArgSeparator();

        if (! $encrypt || ! $PMA_Config->get('URLQueryEncryption')) {
            return http_build_query($params, '', $separator);
        }

        $data = $params;
        $keys = [
            'db',
            'table',
            'field',
            'sql_query',
            'sql_signature',
            'where_clause',
            'goto',
            'back',
            'message_to_show',
            'username',
            'hostname',
            'dbname',
            'tablename',
            'checkprivsdb',
            'checkprivstable',
        ];
        $paramsToEncrypt = [];
        foreach ($params as $paramKey => $paramValue) {
            if (! in_array($paramKey, $keys)) {
                continue;
            }

            $paramsToEncrypt[$paramKey] = $paramValue;
            unset($data[$paramKey]);
        }

        if ($paramsToEncrypt !== []) {
            $data['eq'] = self::encryptQuery(json_encode($paramsToEncrypt));
        }

        return http_build_query($data, '', $separator);
    }

    /**
     * @param string $query
     *
     * @return string
     */
    public static function encryptQuery($query)
    {
        $crypto = new Crypto();

        return strtr(base64_encode($crypto->encrypt($query)), '+/', '-_');
    }

    /**
     * @param string $query
     *
     * @return string|null
     */
    public static function decryptQuery($query)
    {
        $crypto = new Crypto();

        return $crypto->decrypt(base64_decode(strtr($query, '-_', '+/')));
    }

    /**
     * Returns url separator
     *
     * extracted from arg_separator.input as set in php.ini
     * we do not use arg_separator.output to avoid problems with & and &
     *
     * @param string $encode whether to encode separator or not,
     *                       currently 'none' or 'html'
     *
     * @return string  character used for separating url parts usually ; or &
     *
     * @access public
     */
    public static function getArgSeparator($encode = 'none')
    {
        static $separator = null;
        static $html_separator = null;

        if ($separator === null) {
            // use separators defined by php, but prefer ';'
            // as recommended by W3C
            // (see https://www.w3.org/TR/1999/REC-html401-19991224/appendix
            // /notes.html#h-B.2.2)
            $arg_separator = (string) ini_get('arg_separator.input');
            if (mb_strpos($arg_separator, ';') !== false) {
                $separator = ';';
            } elseif (strlen($arg_separator) > 0) {
                $separator = $arg_separator[0];
            } else {
                $separator = '&';
            }
            $html_separator = htmlentities($separator);
        }

        switch ($encode) {
            case 'html':
                return $html_separator;
            case 'text':
            case 'none':
            default:
                return $separator;
        }
    }

    /**
     * @param string $route                Route to use
     * @param array  $additionalParameters Additional URL parameters
     */
    public static function getFromRoute(string $route, array $additionalParameters = []): string
    {
        return 'index.php?route=' . $route . self::getCommon($additionalParameters, '&');
    }
}
