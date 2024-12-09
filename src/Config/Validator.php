<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;

use function __;
use function array_map;
use function array_merge;
use function array_shift;
use function count;
use function error_clear_last;
use function error_get_last;
use function explode;
use function filter_var;
use function htmlspecialchars;
use function is_array;
use function is_object;
use function mb_strpos;
use function mb_substr;
use function mysqli_close;
use function mysqli_connect;
use function mysqli_report;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function trim;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const MYSQLI_REPORT_OFF;
use const PHP_INT_MAX;

/**
 * Form validation for configuration editor
 *
 * Validation function takes two argument: id for which it is called
 * and array of fields' values (usually values for entire formset).
 * The function must always return an array with an error (or error array)
 * assigned to a form element (formset name or field path). Even if there are
 * no errors, key must be set with an empty value.
 *
 * Validation functions are assigned in $cfg_db['_validators'] (config.values.php).
 */
class Validator
{
    /** @var mixed[]|null */
    private static array|null $validators = null;

    /**
     * Returns validator list
     *
     * @param ConfigFile $cf Config file instance
     *
     * @return mixed[]
     */
    public static function getValidators(ConfigFile $cf): array
    {
        if (self::$validators !== null) {
            return self::$validators;
        }

        self::$validators = $cf->getDbEntry('_validators', []);
        $config = Config::getInstance();
        if ($config->isSetup()) {
            return self::$validators;
        }

        // not in setup script: load additional validators for user
        // preferences we need original config values not overwritten
        // by user preferences, creating a new PhpMyAdmin\Config instance is a
        // better idea than hacking into its code
        $uvs = $cf->getDbEntry('_userValidators', []);
        foreach ($uvs as $field => $uvList) {
            $uvList = (array) $uvList;
            foreach ($uvList as &$uv) {
                if (! is_array($uv)) {
                    continue;
                }

                for ($i = 1, $nb = count($uv); $i < $nb; $i++) {
                    if (! str_starts_with($uv[$i], 'value:')) {
                        continue;
                    }

                    $uv[$i] = Core::arrayRead(
                        mb_substr($uv[$i], 6),
                        $config->baseSettings,
                    );
                }
            }

            self::$validators[$field] = isset(self::$validators[$field])
                ? array_merge((array) self::$validators[$field], $uvList)
                : $uvList;
        }

        return self::$validators;
    }

    /**
     * Runs validation $validator_id on values $values and returns error list.
     *
     * Return values:
     * o array, keys - field path or formset id, values - array of errors
     *   when $isPostSource is true values is an empty array to allow for error list
     *   cleanup in HTML document
     * o false - when no validators match name(s) given by $validator_id
     *
     * @param ConfigFile      $cf           Config file instance
     * @param string|string[] $validatorId  ID of validator(s) to run
     * @param mixed[]         $values       Values to validate
     * @param bool            $isPostSource tells whether $values are directly from POST request
     *
     * @return mixed[]|bool
     */
    public static function validate(
        ConfigFile $cf,
        string|array $validatorId,
        array $values,
        bool $isPostSource,
    ): bool|array {
        // find validators
        $validatorId = (array) $validatorId;
        $validators = self::getValidators($cf);
        $vids = [];
        foreach ($validatorId as &$vid) {
            $vid = $cf->getCanonicalPath($vid);
            if (! isset($validators[$vid])) {
                continue;
            }

            $vids[] = $vid;
        }

        if ($vids === []) {
            return false;
        }

        // create argument list with canonical paths and remember path mapping
        $arguments = [];
        $keyMap = [];
        foreach ($values as $k => $v) {
            $k2 = $isPostSource ? str_replace('-', '/', $k) : $k;
            $k2 = mb_strpos($k2, '/')
                ? $cf->getCanonicalPath($k2)
                : $k2;
            $keyMap[$k2] = $k;
            $arguments[$k2] = $v;
        }

        // validate
        $result = [];
        foreach ($vids as $vid) {
            // call appropriate validation functions
            foreach ((array) $validators[$vid] as $validator) {
                $vdef = (array) $validator;
                $vname = array_shift($vdef);
                $args = array_merge([$vid, &$arguments], $vdef);

                $validationResult = match ($vname) {
                    'validateServer' => self::validateServer(...$args),
                    'validatePMAStorage' => self::validatePMAStorage(...$args),
                    'validateRegex' => self::validateRegex(...$args),
                    'validateTrustedProxies' => self::validateTrustedProxies(...$args),
                    'validatePortNumber' => self::validatePortNumber(...$args),
                    'validatePositiveNumber' => self::validatePositiveNumber(...$args),
                    'validateNonNegativeNumber' => self::validateNonNegativeNumber(...$args),
                    'validateByRegex' => self::validateByRegex(...$args),
                    'validateUpperBound' => self::validateUpperBound(...$args),
                    default => null,
                };

                // merge results
                if (! is_array($validationResult)) {
                    continue;
                }

                foreach ($validationResult as $key => $errorList) {
                    // skip empty values if $isPostSource is false
                    if (! $isPostSource && empty($errorList)) {
                        continue;
                    }

                    if (! isset($result[$key])) {
                        $result[$key] = [];
                    }

                    $errorList = array_map(Sanitize::convertBBCode(...), (array) $errorList);
                    $result[$key] = array_merge($result[$key], $errorList);
                }
            }
        }

        // restore original paths
        $newResult = [];
        foreach ($result as $k => $v) {
            $k2 = $keyMap[$k] ?? $k;
            $newResult[$k2] = $v;
        }

        return $newResult === [] ? true : $newResult;
    }

    /**
     * Test database connection
     *
     * @param string $host     host name
     * @param string $port     tcp port to use
     * @param string $socket   socket to use
     * @param string $user     username to use
     * @param string $pass     password to use
     * @param string $errorKey key to use in return array
     *
     * @return array<string, string>|true
     */
    private static function testDBConnection(
        string $host,
        string $port,
        string $socket,
        string $user,
        string $pass,
        string $errorKey = 'Server',
    ): bool|array {
        $config = Config::getInstance();
        if ($config->config->debug->demo) {
            // Connection test disabled on the demo server!
            return true;
        }

        $error = null;
        $host = Core::sanitizeMySQLHost($host);

        error_clear_last();

        /** @var string $socket */
        $socket = $socket === '' ? null : $socket;
        /** @var int $port */
        $port = $port === '' ? null : (int) $port;

        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = @mysqli_connect($host, $user, $pass, '', $port, $socket);
        if (! $conn) {
            $error = __('Could not connect to the database server!');
        } else {
            mysqli_close($conn);
        }

        if ($error !== null) {
            $lastError = error_get_last();
            if ($lastError !== null) {
                $error .= ' - ' . $lastError['message'];
            }
        }

        return $error === null ? true : [$errorKey => $error];
    }

    /**
     * Validate server config
     *
     * @param string  $path   path to config, not used
     *                        keep this parameter since the method is invoked using
     *                        reflection along with other similar methods
     * @param mixed[] $values config values
     *
     * @return array<string, string>
     */
    private static function validateServer(string $path, array $values): array
    {
        $result = [
            'Server' => '',
            'Servers/1/user' => '',
            'Servers/1/SignonSession' => '',
            'Servers/1/SignonURL' => '',
        ];
        $error = false;
        if (empty($values['Servers/1/auth_type'])) {
            $values['Servers/1/auth_type'] = '';
            $result['Servers/1/auth_type'] = __('Invalid authentication type!');
            $error = true;
        }

        if ($values['Servers/1/auth_type'] === 'config' && empty($values['Servers/1/user'])) {
            $result['Servers/1/user'] = __('Empty username while using [kbd]config[/kbd] authentication method!');
            $error = true;
        }

        if ($values['Servers/1/auth_type'] === 'signon' && empty($values['Servers/1/SignonSession'])) {
            $result['Servers/1/SignonSession'] = __(
                'Empty signon session name while using [kbd]signon[/kbd] authentication method!',
            );
            $error = true;
        }

        if ($values['Servers/1/auth_type'] === 'signon' && empty($values['Servers/1/SignonURL'])) {
            $result['Servers/1/SignonURL'] = __(
                'Empty signon URL while using [kbd]signon[/kbd] authentication method!',
            );
            $error = true;
        }

        if (! $error && $values['Servers/1/auth_type'] === 'config') {
            $password = '';
            if (! empty($values['Servers/1/password'])) {
                $password = $values['Servers/1/password'];
            }

            $test = self::testDBConnection(
                empty($values['Servers/1/host']) ? '' : $values['Servers/1/host'],
                empty($values['Servers/1/port']) ? '' : $values['Servers/1/port'],
                empty($values['Servers/1/socket']) ? '' : $values['Servers/1/socket'],
                empty($values['Servers/1/user']) ? '' : $values['Servers/1/user'],
                $password,
            );

            if (is_array($test)) {
                $result = array_merge($result, $test);
            }
        }

        return $result;
    }

    /**
     * Validate pmadb config
     *
     * @param string  $path   path to config, not used
     *                        keep this parameter since the method is invoked using
     *                        reflection along with other similar methods
     * @param mixed[] $values config values
     *
     * @return array<string, string>
     */
    private static function validatePMAStorage(string $path, array $values): array
    {
        $result = [
            'Server_pmadb' => '',
            'Servers/1/controluser' => '',
            'Servers/1/controlpass' => '',
        ];
        $error = false;

        if (empty($values['Servers/1/pmadb'])) {
            return $result;
        }

        $result = [];
        if (empty($values['Servers/1/controluser'])) {
            $result['Servers/1/controluser'] = __(
                'Empty phpMyAdmin control user while using phpMyAdmin configuration storage!',
            );
            $error = true;
        }

        if (empty($values['Servers/1/controlpass'])) {
            $result['Servers/1/controlpass'] = __(
                'Empty phpMyAdmin control user password while using phpMyAdmin configuration storage!',
            );
            $error = true;
        }

        if (! $error) {
            $test = self::testDBConnection(
                empty($values['Servers/1/host']) ? '' : $values['Servers/1/host'],
                empty($values['Servers/1/port']) ? '' : $values['Servers/1/port'],
                empty($values['Servers/1/socket']) ? '' : $values['Servers/1/socket'],
                empty($values['Servers/1/controluser']) ? '' : $values['Servers/1/controluser'],
                empty($values['Servers/1/controlpass']) ? '' : $values['Servers/1/controlpass'],
                'Server_pmadb',
            );
            if (is_array($test)) {
                $result = array_merge($result, $test);
            }
        }

        return $result;
    }

    /**
     * Validates regular expression
     *
     * @param string  $path   path to config
     * @param mixed[] $values config values
     *
     * @return array<string, string|null>
     */
    private static function validateRegex(string $path, array $values): array
    {
        $result = [$path => ''];

        if (empty($values[$path])) {
            return $result;
        }

        error_clear_last();

        $matches = [];
        // in libraries/ListDatabase.php _checkHideDatabase(),
        // a '/' is used as the delimiter for hide_db
        @preg_match('/' . Util::requestString($values[$path]) . '/', '', $matches);

        $currentError = error_get_last();

        if ($currentError !== null) {
            $error = preg_replace('/^preg_match\(\): /', '', $currentError['message']);

            return [$path => $error];
        }

        return $result;
    }

    /**
     * Validates TrustedProxies field
     *
     * @param string  $path   path to config
     * @param mixed[] $values config values
     *
     * @return array<string, string[]>
     */
    private static function validateTrustedProxies(string $path, array $values): array
    {
        $result = [$path => []];

        if (empty($values[$path])) {
            return $result;
        }

        if (is_array($values[$path]) || is_object($values[$path])) {
            // value already processed by FormDisplay::save
            $lines = [];
            foreach ($values[$path] as $ip => $v) {
                $v = Util::requestString($v);
                $lines[] = preg_match('/^-\d+$/', $ip) === 1 ? $v : $ip . ': ' . $v;
            }
        } else {
            // AJAX validation
            $lines = explode("\n", $values[$path]);
        }

        foreach ($lines as $line) {
            $line = trim($line);
            $matches = [];
            // we catch anything that may (or may not) be an IP
            if (preg_match('/^(.+):(?:[ ]?)\\w+$/', $line, $matches) !== 1) {
                $result[$path][] = __('Incorrect value:') . ' '
                    . htmlspecialchars($line);
                continue;
            }

            // now let's check whether we really have an IP address
            if (
                filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
                && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false
            ) {
                $ip = htmlspecialchars(trim($matches[1]));
                $result[$path][] = sprintf(__('Incorrect IP address: %s'), $ip);
                continue;
            }
        }

        return $result;
    }

    /**
     * Tests integer value
     *
     * @param string  $path          path to config
     * @param mixed[] $values        config values
     * @param bool    $allowNegative allow negative values
     * @param bool    $allowZero     allow zero
     * @param int     $maxValue      max allowed value
     * @param string  $errorString   error message string
     *
     * @return string  empty string if test is successful
     */
    private static function validateNumber(
        string $path,
        array $values,
        bool $allowNegative,
        bool $allowZero,
        int $maxValue,
        string $errorString,
    ): string {
        if (empty($values[$path])) {
            return '';
        }

        $value = Util::requestString($values[$path]);

        if (
            (int) $value != $value
            || (! $allowNegative && $value < 0)
            || (! $allowZero && $value == 0)
            || $value > $maxValue
        ) {
            return $errorString;
        }

        return '';
    }

    /**
     * Validates port number
     *
     * @param string  $path   path to config
     * @param mixed[] $values config values
     *
     * @return array<string, string>
     */
    private static function validatePortNumber(string $path, array $values): array
    {
        return [
            $path => self::validateNumber(
                $path,
                $values,
                false,
                false,
                65535,
                __('Not a valid port number!'),
            ),
        ];
    }

    /**
     * Validates positive number
     *
     * @param string  $path   path to config
     * @param mixed[] $values config values
     *
     * @return array<string, string>
     */
    private static function validatePositiveNumber(string $path, array $values): array
    {
        return [
            $path => self::validateNumber(
                $path,
                $values,
                false,
                false,
                PHP_INT_MAX,
                __('Not a positive number!'),
            ),
        ];
    }

    /**
     * Validates non-negative number
     *
     * @param string  $path   path to config
     * @param mixed[] $values config values
     *
     * @return array<string, string>
     */
    private static function validateNonNegativeNumber(string $path, array $values): array
    {
        return [
            $path => self::validateNumber(
                $path,
                $values,
                false,
                true,
                PHP_INT_MAX,
                __('Not a non-negative number!'),
            ),
        ];
    }

    /**
     * Validates value according to given regular expression
     * Pattern and modifiers must be a valid for PCRE <b>and</b> JavaScript RegExp
     *
     * @param string           $path   path to config
     * @param mixed[]          $values config values
     * @param non-empty-string $regex  regular expression to match
     *
     * @return array<string, string>|string
     */
    private static function validateByRegex(string $path, array $values, string $regex): array|string
    {
        if (! isset($values[$path])) {
            return '';
        }

        return [$path => preg_match($regex, Util::requestString($values[$path])) === 1 ? '' : __('Incorrect value!')];
    }

    /**
     * Validates upper bound for numeric inputs
     *
     * @param string  $path     path to config
     * @param mixed[] $values   config values
     * @param int     $maxValue maximal allowed value
     *
     * @return array<string, string>
     */
    private static function validateUpperBound(string $path, array $values, int $maxValue): array
    {
        $result = $values[$path] <= $maxValue;

        return [
            $path => $result ? '' : sprintf(
                __('Value must be less than or equal to %s!'),
                $maxValue,
            ),
        ];
    }
}
