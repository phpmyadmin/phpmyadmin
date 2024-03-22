<?php
/**
 * Static methods for URL/hidden inputs generating
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Crypto\Crypto;
use PhpMyAdmin\Tests\UrlTest;

use function base64_decode;
use function base64_encode;
use function htmlspecialchars;
use function http_build_query;
use function in_array;
use function ini_get;
use function is_array;
use function is_string;
use function json_encode;
use function method_exists;
use function str_contains;
use function strtr;

/**
 * Static methods for URL/hidden inputs generating
 */
class Url
{
    private static string|null $inputArgSeparator = null;

    /**
     * Generates text with hidden inputs.
     *
     * @see Url::getCommon()
     *
     * @param string|mixed[] $db    optional database name (can also be an array of parameters)
     * @param string         $table optional table name
     * @param string|mixed[] $skip  do not generate a hidden field for this parameter (can be an array of strings)
     *
     * @return string   string with input fields
     */
    public static function getHiddenInputs(
        string|array $db = '',
        string $table = '',
        string|array $skip = [],
    ): string {
        if (is_array($db)) {
            $params =& $db;
        } else {
            $params = [];
            if ($db !== '') {
                $params['db'] = $db;
            }

            if ($table !== '') {
                $params['table'] = $table;
            }
        }

        $config = Config::getInstance();
        if (Current::$server > 0 && Current::$server !== $config->settings['ServerDefault']) {
            $params['server'] = Current::$server;
        }

        if (empty($config->getCookie('pma_lang')) && ! empty($GLOBALS['lang'])) {
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
     * @param mixed[] $values  hidden values
     * @param string  $pre     prefix
     * @param bool    $isToken if token already added in hidden input field
     *
     * @return string form fields of type hidden
     */
    public static function getHiddenFields(array $values, string $pre = '', bool $isToken = false): string
    {
        $fields = '';

        /* Always include token in plain forms */
        if ($isToken === false && isset($_SESSION[' PMA_token '])) {
            $values['token'] = $_SESSION[' PMA_token '];
        }

        foreach ($values as $name => $value) {
            if ($pre !== '') {
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
     */
    public static function getCommon(array $params = [], string $divider = '?', bool $encrypt = true): string
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
     */
    public static function getCommonRaw(array $params = [], string $divider = '?', bool $encrypt = true): string
    {
        // avoid overwriting when creating navigation panel links to servers
        $config = Config::getInstance();
        if (
            Current::$server > 0
            && Current::$server !== $config->settings['ServerDefault']
            && ! isset($params['server'])
            && ! $config->get('is_setup')
        ) {
            $params['server'] = Current::$server;
        }

        // Can be null when the user is missing an extension.
        if (empty($config->getCookie('pma_lang')) && ! empty($GLOBALS['lang'])) {
            $params['lang'] = $GLOBALS['lang'];
        }

        $query = self::buildHttpQuery($params, $encrypt);

        if (($divider !== '?' && $divider !== self::getArgSeparator()) || $query !== '') {
            return $divider . $query;
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $params
     * @param bool                     $encrypt whether to encrypt URL params
     */
    public static function buildHttpQuery(array $params, bool $encrypt = true): string
    {
        if ($params === []) {
            return '';
        }

        $separator = self::getArgSeparator();

        if (! $encrypt || ! Config::getInstance()->get('URLQueryEncryption')) {
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
            $data['eq'] = self::encryptQuery((string) json_encode($paramsToEncrypt));
        }

        return http_build_query($data, '', $separator);
    }

    public static function encryptQuery(string $query): string
    {
        $crypto = new Crypto();

        return strtr(base64_encode($crypto->encrypt($query)), '+/', '-_');
    }

    public static function decryptQuery(string $query): string|null
    {
        $crypto = new Crypto();

        return $crypto->decrypt(base64_decode(strtr($query, '-_', '+/')));
    }

    /**
     * Returns url separator character used for separating url parts.
     *
     * Extracted from 'arg_separator.input' as set in php.ini, but prefers '&' and ';'.
     *
     * @see https://www.php.net/manual/en/ini.core.php#ini.arg-separator.input
     * @see https://www.w3.org/TR/1999/REC-html401-19991224/appendix/notes.html#h-B.2.2
     */
    public static function getArgSeparator(): string
    {
        if (is_string(self::$inputArgSeparator)) {
            return self::$inputArgSeparator;
        }

        $separator = self::getArgSeparatorValueFromIni();
        if (! is_string($separator) || $separator === '' || str_contains($separator, '&')) {
            return self::$inputArgSeparator = '&';
        }

        if (str_contains($separator, ';')) {
            return self::$inputArgSeparator = ';';
        }

        // uses first character
        return self::$inputArgSeparator = $separator[0];
    }

    /** @return string|false */
    private static function getArgSeparatorValueFromIni(): string|bool
    {
        /** @phpstan-ignore-next-line */
        if (method_exists(UrlTest::class, 'getInputArgSeparator')) {
            return UrlTest::getInputArgSeparator();
        }

        return ini_get('arg_separator.input');
    }

    /**
     * @param string  $route                Route to use
     * @param mixed[] $additionalParameters Additional URL parameters
     */
    public static function getFromRoute(string $route, array $additionalParameters = []): string
    {
        return 'index.php?route=' . $route . self::getCommon($additionalParameters, self::getArgSeparator());
    }
}
