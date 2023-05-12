<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Utils\SessionCache;
use Stringable;

use function __;
use function _pgettext;
use function abs;
use function array_key_exists;
use function array_map;
use function array_shift;
use function array_unique;
use function bin2hex;
use function chr;
use function count;
use function ctype_digit;
use function date;
use function decbin;
use function explode;
use function extension_loaded;
use function fclose;
use function floatval;
use function floor;
use function fread;
use function function_exists;
use function html_entity_decode;
use function htmlentities;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function log10;
use function mb_detect_encoding;
use function mb_strlen;
use function mb_strpos;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function number_format;
use function ord;
use function parse_url;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function random_bytes;
use function range;
use function reset;
use function round;
use function rtrim;
use function set_time_limit;
use function sort;
use function sprintf;
use function str_contains;
use function str_pad;
use function str_replace;
use function strcasecmp;
use function strftime;
use function strlen;
use function strrev;
use function strtolower;
use function strtr;
use function substr;
use function time;
use function trim;
use function uksort;

use const ENT_COMPAT;
use const ENT_QUOTES;
use const PHP_INT_SIZE;
use const PHP_MAJOR_VERSION;
use const STR_PAD_LEFT;

/**
 * Misc functions used all over the scripts.
 */
class Util
{
    /**
     * Checks whether configuration value tells to show icons.
     *
     * @param string $value Configuration option name
     */
    public static function showIcons(string $value): bool
    {
        return in_array($GLOBALS['cfg'][$value], ['icons', 'both']);
    }

    /**
     * Checks whether configuration value tells to show text.
     *
     * @param string $value Configuration option name
     */
    public static function showText(string $value): bool
    {
        return in_array($GLOBALS['cfg'][$value], ['text', 'both']);
    }

    /**
     * Returns the formatted maximum size for an upload
     *
     * @param int|float|string $maxUploadSize the size
     *
     * @return string the message
     */
    public static function getFormattedMaximumUploadSize(int|float|string $maxUploadSize): string
    {
        // I have to reduce the second parameter (sensitiveness) from 6 to 4
        // to avoid weird results like 512 kKib
        [$maxSize, $maxUnit] = self::formatByteDown($maxUploadSize, 4);

        return '(' . sprintf(__('Max: %s%s'), $maxSize, $maxUnit) . ')';
    }

    /**
     * removes quotes (',",`) from a quoted string
     *
     * checks if the string is quoted and removes this quotes
     *
     * @param string $quotedString string to remove quotes from
     * @param string $quote        type of quote to remove
     *
     * @return string unquoted string
     */
    public static function unQuote(string $quotedString, string|null $quote = null): string
    {
        $quotes = [];

        if ($quote === null) {
            $quotes[] = '`';
            $quotes[] = '"';
            $quotes[] = "'";
        } else {
            $quotes[] = $quote;
        }

        foreach ($quotes as $quote) {
            if (mb_substr($quotedString, 0, 1) === $quote && mb_substr($quotedString, -1, 1) === $quote) {
                // replace escaped quotes
                return str_replace($quote . $quote, $quote, mb_substr($quotedString, 1, -1));
            }
        }

        return $quotedString;
    }

    /**
     * Get a URL link to the official MySQL documentation
     *
     * @param string $link   contains name of page/anchor that is being linked
     * @param string $anchor anchor to page part
     *
     * @return string  the URL link
     */
    public static function getMySQLDocuURL(string $link, string $anchor = ''): string
    {
        // Fixup for newly used names:
        $link = str_replace('_', '-', mb_strtolower($link));

        if ($link === '') {
            $link = 'index';
        }

        $mysql = '5.5';
        $lang = 'en';
        if (isset($GLOBALS['dbi'])) {
            $serverVersion = $GLOBALS['dbi']->getVersion();
            if ($serverVersion >= 80000) {
                $mysql = '8.0';
            } elseif ($serverVersion >= 50700) {
                $mysql = '5.7';
            } elseif ($serverVersion >= 50600) {
                $mysql = '5.6';
            }
        }

        $url = 'https://dev.mysql.com/doc/refman/'
            . $mysql . '/' . $lang . '/' . $link . '.html';
        if ($anchor !== '') {
            $url .= '#' . $anchor;
        }

        return Core::linkURL($url);
    }

    /**
     * Get a URL link to the official documentation page of either MySQL
     * or MariaDB depending on the database server
     * of the user.
     *
     * @param bool $isMariaDB if the database server is MariaDB
     *
     * @return string The URL link
     */
    public static function getDocuURL(bool $isMariaDB = false): string
    {
        if ($isMariaDB) {
            $url = 'https://mariadb.com/kb/en/documentation/';

            return Core::linkURL($url);
        }

        return self::getMySQLDocuURL('');
    }

    /* ----------------------- Set of misc functions ----------------------- */

    /**
     * Adds backquotes on both sides of a database, table or field name.
     * and escapes backquotes inside the name with another backquote
     *
     * example:
     * <code>
     * echo backquote('owner`s db'); // `owner``s db`
     *
     * </code>
     *
     * @param Stringable|string|null $identifier the database, table or field name to "backquote"
     */
    public static function backquote(Stringable|string|null $identifier): string
    {
        return static::backquoteCompat($identifier, 'NONE', true);
    }

    /**
     * Adds backquotes on both sides of a database, table or field name.
     * in compatibility mode
     *
     * example:
     * <code>
     * echo backquoteCompat('owner`s db'); // `owner``s db`
     *
     * </code>
     *
     * @param Stringable|string|null $identifier    the database, table or field name to "backquote"
     * @param string                 $compatibility string compatibility mode (used by dump functions)
     * @param bool|null              $doIt          a flag to bypass this function (used by dump functions)
     */
    public static function backquoteCompat(
        Stringable|string|null $identifier,
        string $compatibility = 'MSSQL',
        bool|null $doIt = true,
    ): string {
        $identifier = (string) $identifier;
        if ($identifier === '' || $identifier === '*') {
            return $identifier;
        }

        if (! $doIt && ! ((int) Context::isKeyword($identifier) & Token::FLAG_KEYWORD_RESERVED)) {
            return $identifier;
        }

        $quote = '`';
        $escapeChar = '`';
        if ($compatibility === 'MSSQL') {
            $quote = '"';
            $escapeChar = '\\';
        }

        return $quote . str_replace($quote, $escapeChar . $quote, $identifier) . $quote;
    }

    /**
     * Formats $value to byte view
     *
     * @param float|int|string|null $value the value to format
     * @param int                   $limes the sensitiveness
     * @param int                   $comma the number of decimals to retain
     *
     * @return mixed[]|null the formatted value and its unit
     */
    public static function formatByteDown(float|int|string|null $value, int $limes = 6, int $comma = 0): array|null
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = (float) $value;
        }

        $byteUnits = [
            /* l10n: shortcuts for Byte */
            __('B'),
            /* l10n: shortcuts for Kilobyte */
            __('KiB'),
            /* l10n: shortcuts for Megabyte */
            __('MiB'),
            /* l10n: shortcuts for Gigabyte */
            __('GiB'),
            /* l10n: shortcuts for Terabyte */
            __('TiB'),
            /* l10n: shortcuts for Petabyte */
            __('PiB'),
            /* l10n: shortcuts for Exabyte */
            __('EiB'),
        ];

        $dh = 10 ** $comma;
        $li = 10 ** $limes;
        $unit = $byteUnits[0];

        for ($d = 6, $ex = 15; $d >= 1; $d--, $ex -= 3) {
            $unitSize = $li * 10 ** $ex;
            if (isset($byteUnits[$d]) && $value >= $unitSize) {
                // use 1024.0 to avoid integer overflow on 64-bit machines
                $value = round($value / (1024 ** $d / $dh)) / $dh;
                $unit = $byteUnits[$d];
                break 1;
            }
        }

        if ($unit !== $byteUnits[0]) {
            // if the unit is not bytes (as represented in current language)
            // reformat with max length of 5
            // 4th parameter=true means do not reformat if value < 1
            $returnValue = self::formatNumber($value, 5, $comma, true, false);
        } else {
            // do not reformat, just handle the locale
            $returnValue = self::formatNumber($value, 0);
        }

        return [trim($returnValue), $unit];
    }

    /**
     * Formats $value to the given length and appends SI prefixes
     * with a $length of 0 no truncation occurs, number is only formatted
     * to the current locale
     *
     * examples:
     * <code>
     * echo formatNumber(123456789, 6);     // 123,457 k
     * echo formatNumber(-123456789, 4, 2); //    -123.46 M
     * echo formatNumber(-0.003, 6);        //      -3 m
     * echo formatNumber(0.003, 3, 3);      //       0.003
     * echo formatNumber(0.00003, 3, 2);    //       0.03 m
     * echo formatNumber(0, 6);             //       0
     * </code>
     *
     * @param float|int|string $value          the value to format
     * @param int              $digitsLeft     number of digits left of the comma
     * @param int              $digitsRight    number of digits right of the comma
     * @param bool             $onlyDown       do not reformat numbers below 1
     * @param bool             $noTrailingZero removes trailing zeros right of the comma (default: true)
     *
     * @return string   the formatted value and its unit
     */
    public static function formatNumber(
        float|int|string $value,
        int $digitsLeft = 3,
        int $digitsRight = 0,
        bool $onlyDown = false,
        bool $noTrailingZero = true,
    ): string {
        if ($value == 0) {
            return '0';
        }

        if (is_string($value)) {
            $value = (float) $value;
        }

        $originalValue = $value;
        //number_format is not multibyte safe, str_replace is safe
        if ($digitsLeft === 0) {
            $value = number_format(
                (float) $value,
                $digitsRight,
                /* l10n: Decimal separator */
                __('.'),
                /* l10n: Thousands separator */
                __(','),
            );
            if (($originalValue != 0) && (floatval($value) == 0)) {
                return ' <' . (1 / 10 ** $digitsRight);
            }

            return $value;
        }

        // this units needs no translation, ISO
        $units = [
            -8 => 'y',
            -7 => 'z',
            -6 => 'a',
            -5 => 'f',
            -4 => 'p',
            -3 => 'n',
            -2 => 'µ',
            -1 => 'm',
            0 => ' ',
            1 => 'k',
            2 => 'M',
            3 => 'G',
            4 => 'T',
            5 => 'P',
            6 => 'E',
            7 => 'Z',
            8 => 'Y',
        ];
        /* l10n: Decimal separator */
        $decimalSep = __('.');
        /* l10n: Thousands separator */
        $thousandsSep = __(',');

        // check for negative value to retain sign
        if ($value < 0) {
            $sign = '-';
            $value = abs($value);
        } else {
            $sign = '';
        }

        $dh = 10 ** $digitsRight;

        // This gives us the right SI prefix already, but $digits_left parameter not incorporated
        $d = floor(log10((float) $value) / 3);
        // Lowering the SI prefix by 1 gives us an additional 3 zeros
        // So if we have 3,6,9,12.. free digits ($digits_left - $cur_digits) to use, then lower the SI prefix
        $curDigits = floor(log10($value / 1000 ** $d) + 1);
        if ($digitsLeft > $curDigits) {
            $d -= floor(($digitsLeft - $curDigits) / 3);
        }

        if ($d < 0 && $onlyDown) {
            $d = 0;
        }

        $value = round($value / (1000 ** $d / $dh)) / $dh;
        $unit = $units[$d];

        // number_format is not multibyte safe, str_replace is safe
        $formattedValue = number_format($value, $digitsRight, $decimalSep, $thousandsSep);
        // If we don't want any zeros, remove them now
        if ($noTrailingZero && str_contains($formattedValue, $decimalSep)) {
            $formattedValue = preg_replace('/' . preg_quote($decimalSep, '/') . '?0+$/', '', $formattedValue);
        }

        if ($originalValue != 0 && floatval($value) == 0) {
            return ' <' . number_format(1 / 10 ** $digitsRight, $digitsRight, $decimalSep, $thousandsSep) . ' ' . $unit;
        }

        return $sign . $formattedValue . ' ' . $unit;
    }

    /**
     * Returns the number of bytes when a formatted size is given
     *
     * @param string|int $formattedSize the size expression (for example 8MB)
     *
     * @return int|float The numerical part of the expression (for example 8)
     */
    public static function extractValueFromFormattedSize(string|int $formattedSize): int|float
    {
        $returnValue = -1;

        $formattedSize = (string) $formattedSize;

        if (preg_match('/^[0-9]+GB$/', $formattedSize)) {
            $returnValue = (int) mb_substr($formattedSize, 0, -2) * 1024 ** 3;
        } elseif (preg_match('/^[0-9]+MB$/', $formattedSize)) {
            $returnValue = (int) mb_substr($formattedSize, 0, -2) * 1024 ** 2;
        } elseif (preg_match('/^[0-9]+K$/', $formattedSize)) {
            $returnValue = (int) mb_substr($formattedSize, 0, -1) * 1024 ** 1;
        }

        return $returnValue;
    }

    /**
     * Writes localised date
     *
     * @param int    $timestamp the current timestamp
     * @param string $format    format
     *
     * @return string   the formatted date
     */
    public static function localisedDate(int $timestamp = -1, string $format = ''): string
    {
        $month = [
            _pgettext('Short month name for January', 'Jan'),
            _pgettext('Short month name for February', 'Feb'),
            _pgettext('Short month name for March', 'Mar'),
            _pgettext('Short month name for April', 'Apr'),
            _pgettext('Short month name for May', 'May'),
            _pgettext('Short month name for June', 'Jun'),
            _pgettext('Short month name for July', 'Jul'),
            _pgettext('Short month name for August', 'Aug'),
            _pgettext('Short month name for September', 'Sep'),
            _pgettext('Short month name for October', 'Oct'),
            _pgettext('Short month name for November', 'Nov'),
            _pgettext('Short month name for December', 'Dec'),
        ];
        $dayOfWeek = [
            _pgettext('Short week day name for Sunday', 'Sun'),
            _pgettext('Short week day name for Monday', 'Mon'),
            _pgettext('Short week day name for Tuesday', 'Tue'),
            _pgettext('Short week day name for Wednesday', 'Wed'),
            _pgettext('Short week day name for Thursday', 'Thu'),
            _pgettext('Short week day name for Friday', 'Fri'),
            _pgettext('Short week day name for Saturday', 'Sat'),
        ];

        if ($format == '') {
            /* l10n: See https://www.php.net/manual/en/function.strftime.php */
            $format = __('%B %d, %Y at %I:%M %p');
        }

        if ($timestamp == -1) {
            $timestamp = time();
        }

        $date = (string) preg_replace(
            '@%[aA]@',
            // phpcs:ignore Generic.PHP.DeprecatedFunctions
            $dayOfWeek[(int) @strftime('%w', $timestamp)],
            $format,
        );
        $date = (string) preg_replace(
            '@%[bB]@',
            // phpcs:ignore Generic.PHP.DeprecatedFunctions
            $month[(int) @strftime('%m', $timestamp) - 1],
            $date,
        );

        /* Fill in AM/PM */
        $hours = (int) date('H', $timestamp);
        if ($hours >= 12) {
            $amPm = _pgettext('AM/PM indication in time', 'PM');
        } else {
            $amPm = _pgettext('AM/PM indication in time', 'AM');
        }

        $date = (string) preg_replace('@%[pP]@', $amPm, $date);

        // Can return false on windows for Japanese language
        // See https://github.com/phpmyadmin/phpmyadmin/issues/15830
        // phpcs:ignore Generic.PHP.DeprecatedFunctions
        $ret = @strftime($date, $timestamp);
        // Some OSes such as Win8.1 Traditional Chinese version did not produce UTF-8
        // output here. See https://github.com/phpmyadmin/phpmyadmin/issues/10598
        if ($ret === false || mb_detect_encoding($ret, 'UTF-8', true) !== 'UTF-8') {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return $ret;
    }

    /**
     * Splits a URL string by parameter
     *
     * @param string $url the URL
     *
     * @return array<int, string> the parameter/value pairs, for example [0] db=sakila
     */
    public static function splitURLQuery(string $url): array
    {
        // decode encoded url separators
        $separator = Url::getArgSeparator();
        // on most places separator is still hard coded ...
        if ($separator !== '&') {
            // ... so always replace & with $separator
            $url = str_replace([htmlentities('&'), '&'], [$separator, $separator], $url);
        }

        $url = str_replace(htmlentities($separator), $separator, $url);
        // end decode

        $urlParts = parse_url($url);

        if (is_array($urlParts) && isset($urlParts['query']) && strlen($separator) > 0) {
            return explode($separator, $urlParts['query']);
        }

        return [];
    }

    /**
     * Returns a given timespan value in a readable format.
     *
     * @param int $seconds the timespan
     *
     * @return string the formatted value
     */
    public static function timespanFormat(int $seconds): string
    {
        $days = floor($seconds / 86400);
        if ($days > 0) {
            $seconds -= $days * 86400;
        }

        $hours = floor($seconds / 3600);
        if ($days > 0 || $hours > 0) {
            $seconds -= $hours * 3600;
        }

        $minutes = floor($seconds / 60);
        if ($days > 0 || $hours > 0 || $minutes > 0) {
            $seconds -= $minutes * 60;
        }

        return sprintf(
            __('%s days, %s hours, %s minutes and %s seconds'),
            (string) $days,
            (string) $hours,
            (string) $minutes,
            (string) $seconds,
        );
    }

    /**
     * Build a condition and with a value
     *
     * @param string|int|float|null $row          The row value
     * @param FieldMetadata         $meta         The field metadata
     * @param int                   $fieldsCount  A number of fields
     * @param string                $conditionKey A key used for BINARY fields functions
     * @param string                $condition    The condition
     *
     * @return array<int,string|null>
     * @psalm-return array{string|null, string}
     */
    private static function getConditionValue(
        string|int|float|null $row,
        FieldMetadata $meta,
        int $fieldsCount,
        string $conditionKey,
        string $condition,
    ): array {
        if ($row === null) {
            return ['IS NULL', $condition];
        }

        $conditionValue = '';
        $isBinaryString = $meta->isType(FieldMetadata::TYPE_STRING) && $meta->isBinary();
        // 63 is the binary charset, see: https://dev.mysql.com/doc/internals/en/charsets.html
        $isBlobAndIsBinaryCharset = $meta->isType(FieldMetadata::TYPE_BLOB) && $meta->charsetnr === 63;
        if ($meta->isNumeric) {
            $conditionValue = '= ' . $row;
        } elseif ($isBlobAndIsBinaryCharset || (! empty($row) && $isBinaryString)) {
            // hexify only if this is a true not empty BLOB or a BINARY

            // do not waste memory building a too big condition
            $rowLength = mb_strlen((string) $row);
            if ($rowLength > 0 && $rowLength < 1000) {
                // use a CAST if possible, to avoid problems
                // if the field contains wildcard characters % or _
                $conditionValue = '= CAST(0x' . bin2hex((string) $row) . ' AS BINARY)';
            } elseif ($fieldsCount === 1) {
                // when this blob is the only field present
                // try settling with length comparison
                $condition = ' CHAR_LENGTH(' . $conditionKey . ') ';
                $conditionValue = ' = ' . $rowLength;
            } else {
                // this blob won't be part of the final condition
                $conditionValue = null;
            }
        } elseif ($meta->isMappedTypeGeometry && ! empty($row)) {
            // do not build a too big condition
            if (mb_strlen((string) $row) < 5000) {
                $condition .= '= CAST(0x' . bin2hex((string) $row) . ' AS BINARY)';
            } else {
                $condition = '';
            }
        } elseif ($meta->isMappedTypeBit) {
            $conditionValue = "= b'"
                . self::printableBitValue((int) $row, $meta->length) . "'";
        } else {
            $conditionValue = '= ' . $GLOBALS['dbi']->quoteString((string) $row);
        }

        return [$conditionValue, $condition];
    }

    /**
     * Function to generate unique condition for specified row.
     *
     * @param int             $fieldsCount     number of fields
     * @param FieldMetadata[] $fieldsMeta      meta information about fields
     * @param array           $row             current row
     * @param bool            $forceUnique     generate condition only on pk or unique
     * @param string|bool     $restrictToTable restrict the unique condition to this table or false if none
     * @param Expression[]    $expressions     An array of Expression instances.
     * @psalm-param array<int, mixed> $row
     *
     * @return mixed[] the calculated condition and whether condition is unique
     * @psalm-return array{string, bool, array}
     */
    public static function getUniqueCondition(
        int $fieldsCount,
        array $fieldsMeta,
        array $row,
        bool $forceUnique = false,
        string|bool $restrictToTable = false,
        array $expressions = [],
    ): array {
        $primaryKey = '';
        $uniqueKey = '';
        $nonPrimaryCondition = '';
        $preferredCondition = '';
        $primaryKeyArray = [];
        $uniqueKeyArray = [];
        $nonPrimaryConditionArray = [];

        for ($i = 0; $i < $fieldsCount; ++$i) {
            $meta = $fieldsMeta[$i];

            // do not use a column alias in a condition
            if ($meta->orgname === '') {
                $meta->orgname = $meta->name;

                foreach ($expressions as $expression) {
                    if (empty($expression->alias) || empty($expression->column)) {
                        continue;
                    }

                    if (strcasecmp($meta->name, $expression->alias) == 0) {
                        $meta->orgname = $expression->column;
                        break;
                    }
                }
            }

            // Do not use a table alias in a condition.
            // Test case is:
            // select * from galerie x WHERE
            //(select count(*) from galerie y where y.datum=x.datum)>1
            //
            // But orgtable is present only with mysqli extension so the
            // fix is only for mysqli.
            // Also, do not use the original table name if we are dealing with
            // a view because this view might be updatable.
            // (The isView() verification should not be costly in most cases
            // because there is some caching in the function).
            if (
                $meta->table !== $meta->orgtable
                && ! $GLOBALS['dbi']->getTable($GLOBALS['db'], $meta->table)->isView()
            ) {
                $meta->table = $meta->orgtable;
            }

            // If this field is not from the table which the unique clause needs
            // to be restricted to.
            if ($restrictToTable && $restrictToTable != $meta->table) {
                continue;
            }

            // to fix the bug where float fields (primary or not)
            // can't be matched because of the imprecision of
            // floating comparison, use CONCAT
            // (also, the syntax "CONCAT(field) IS NULL"
            // that we need on the next "if" will work)
            if ($meta->isType(FieldMetadata::TYPE_REAL)) {
                $conKey = 'CONCAT(' . self::backquote($meta->table) . '.'
                    . self::backquote($meta->orgname) . ')';
            } else {
                $conKey = self::backquote($meta->table) . '.'
                    . self::backquote($meta->orgname);
            }

            $condition = ' ' . $conKey . ' ';

            [$conVal, $condition] = self::getConditionValue($row[$i] ?? null, $meta, $fieldsCount, $conKey, $condition);

            if ($conVal === null) {
                continue;
            }

            $condition .= $conVal . ' AND';

            if ($meta->isPrimaryKey()) {
                $primaryKey .= $condition;
                $primaryKeyArray[$conKey] = $conVal;
            } elseif ($meta->isUniqueKey()) {
                $uniqueKey .= $condition;
                $uniqueKeyArray[$conKey] = $conVal;
            }

            $nonPrimaryCondition .= $condition;
            $nonPrimaryConditionArray[$conKey] = $conVal;
        }

        // Correction University of Virginia 19991216:
        // prefer primary or unique keys for condition,
        // but use conjunction of all values if no primary key
        $clauseIsUnique = true;

        $conditionArray = [];
        if ($primaryKey) {
            $preferredCondition = $primaryKey;
            $conditionArray = $primaryKeyArray;
        } elseif ($uniqueKey) {
            $preferredCondition = $uniqueKey;
            $conditionArray = $uniqueKeyArray;
        } elseif (! $forceUnique) {
            $preferredCondition = $nonPrimaryCondition;
            $conditionArray = $nonPrimaryConditionArray;
            $clauseIsUnique = false;
        }

        $whereClause = trim((string) preg_replace('|\s?AND$|', '', $preferredCondition));

        return [$whereClause, $clauseIsUnique, $conditionArray];
    }

    /**
     * Generate the charset query part
     *
     * @param string $collation Collation
     * @param bool   $override  (optional) force 'CHARACTER SET' keyword
     */
    public static function getCharsetQueryPart(string $collation, bool $override = false): string
    {
        [$charset] = explode('_', $collation);
        $keyword = ' CHARSET=';

        if ($override) {
            $keyword = ' CHARACTER SET ';
        }

        return $keyword . $charset
            . ($charset === $collation ? '' : ' COLLATE ' . $collation);
    }

    /**
     * Generate a pagination selector for browsing resultsets
     *
     * @param string $name        The name for the request parameter
     * @param int    $rows        Number of rows in the pagination set
     * @param int    $pageNow     current page number
     * @param int    $nbTotalPage number of total pages
     * @param int    $showAll     If the number of pages is lower than this
     *                            variable, no pages will be omitted in pagination
     * @param int    $sliceStart  How many rows at the beginning should always
     *                            be shown?
     * @param int    $sliceEnd    How many rows at the end should always be shown?
     * @param int    $percent     Percentage of calculation page offsets to hop to a
     *                            next page
     * @param int    $range       Near the current page, how many pages should
     *                            be considered "nearby" and displayed as well?
     * @param string $prompt      The prompt to display (sometimes empty)
     */
    public static function pageselector(
        string $name,
        int $rows,
        int $pageNow = 1,
        int $nbTotalPage = 1,
        int $showAll = 200,
        int $sliceStart = 5,
        int $sliceEnd = 5,
        int $percent = 20,
        int $range = 10,
        string $prompt = '',
    ): string {
        $increment = floor($nbTotalPage / $percent);
        $pageNowMinusRange = $pageNow - $range;
        $pageNowPlusRange = $pageNow + $range;

        $gotoPage = $prompt . ' <select class="pageselector ajax"';

        $gotoPage .= ' name="' . $name . '" >';
        if ($nbTotalPage < $showAll) {
            $pages = range(1, $nbTotalPage);
        } else {
            $pages = [];

            // Always show first X pages
            for ($i = 1; $i <= $sliceStart; $i++) {
                $pages[] = $i;
            }

            // Always show last X pages
            for ($i = $nbTotalPage - $sliceEnd; $i <= $nbTotalPage; $i++) {
                $pages[] = $i;
            }

            // Based on the number of results we add the specified
            // $percent percentage to each page number,
            // so that we have a representing page number every now and then to
            // immediately jump to specific pages.
            // As soon as we get near our currently chosen page ($pageNow -
            // $range), every page number will be shown.
            $i = $sliceStart;
            $x = $nbTotalPage - $sliceEnd;
            $metBoundary = false;

            while ($i <= $x) {
                if ($i >= $pageNowMinusRange && $i <= $pageNowPlusRange) {
                    // If our pageselector comes near the current page, we use 1
                    // counter increments
                    $i++;
                    $metBoundary = true;
                } else {
                    // We add the percentage increment to our current page to
                    // hop to the next one in range
                    $i += $increment;

                    // Make sure that we do not cross our boundaries.
                    if ($i > $pageNowMinusRange && ! $metBoundary) {
                        $i = $pageNowMinusRange;
                    }
                }

                if ($i <= 0 || $i > $x) {
                    continue;
                }

                $pages[] = $i;
            }

            /*
            Add page numbers with "geometrically increasing" distances.

            This helps me a lot when navigating through giant tables.

            Test case: table with 2.28 million sets, 76190 pages. Page of interest
            is between 72376 and 76190.
            Selecting page 72376.
            Now, old version enumerated only +/- 10 pages around 72376 and the
            percentage increment produced steps of about 3000.

            The following code adds page numbers +/- 2,4,8,16,32,64,128,256 etc.
            around the current page.
            */
            $i = $pageNow;
            $dist = 1;
            while ($i < $x) {
                $dist = 2 * $dist;
                $i = $pageNow + $dist;
                if ($i <= 0 || $i > $x) {
                    continue;
                }

                $pages[] = $i;
            }

            $i = $pageNow;
            $dist = 1;
            while ($i > 0) {
                $dist = 2 * $dist;
                $i = $pageNow - $dist;
                if ($i <= 0 || $i > $x) {
                    continue;
                }

                $pages[] = $i;
            }

            // Since because of ellipsing of the current page some numbers may be
            // double, we unify our array:
            sort($pages);
            $pages = array_unique($pages);
        }

        if ($pageNow > $nbTotalPage) {
            $pages[] = $pageNow;
        }

        foreach ($pages as $i) {
            if ($i == $pageNow) {
                $selected = 'selected="selected" style="font-weight: bold"';
            } else {
                $selected = '';
            }

            $gotoPage .= '                <option ' . $selected
                . ' value="' . (($i - 1) * $rows) . '">' . $i . '</option>' . "\n";
        }

        $gotoPage .= ' </select>';

        return $gotoPage;
    }

    /**
     * Calculate page number through position
     *
     * @param int $pos      position of first item
     * @param int $maxCount number of items per page
     *
     * @return int $page_num
     */
    public static function getPageFromPosition(int $pos, int $maxCount): int
    {
        return (int) floor($pos / $maxCount) + 1;
    }

    /**
     * replaces %u in given path with current user name
     *
     * example:
     * <code>
     * $user_dir = userDir('/var/pma_tmp/%u/'); // '/var/pma_tmp/root/'
     *
     * </code>
     *
     * @param string $dir with wildcard for user
     *
     * @return string per user directory
     */
    public static function userDir(string $dir): string
    {
        // add trailing slash
        if (mb_substr($dir, -1) !== '/') {
            $dir .= '/';
        }

        return str_replace('%u', Core::securePath($GLOBALS['cfg']['Server']['user']), $dir);
    }

    /**
     * Clears cache content which needs to be refreshed on user change.
     */
    public static function clearUserCache(): void
    {
        SessionCache::remove('is_superuser');
        SessionCache::remove('is_createuser');
        SessionCache::remove('is_grantuser');
        SessionCache::remove('mysql_cur_user');
    }

    /**
     * Converts a bit value to printable format;
     * in MySQL a BIT field can be from 1 to 64 bits so we need this
     * function because in PHP, decbin() supports only 32 bits
     * on 32-bit servers
     *
     * @param int $value  coming from a BIT field
     * @param int $length length
     *
     * @return string the printable value
     */
    public static function printableBitValue(int $value, int $length): string
    {
        // if running on a 64-bit server or the length is safe for decbin()
        if (PHP_INT_SIZE == 8 || $length < 33) {
            $printable = decbin($value);
        } else {
            // FIXME: does not work for the leftmost bit of a 64-bit value
            $i = 0;
            $printable = '';
            while ($value >= 2 ** $i) {
                ++$i;
            }

            if ($i != 0) {
                --$i;
            }

            while ($i >= 0) {
                if ($value - 2 ** $i < 0) {
                    $printable = '0' . $printable;
                } else {
                    $printable = '1' . $printable;
                    $value -= 2 ** $i;
                }

                --$i;
            }

            $printable = strrev($printable);
        }

        return str_pad($printable, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Converts a BIT type default value
     * for example, b'010' becomes 010
     *
     * @param string|null $bitDefaultValue value
     *
     * @return string the converted value
     */
    public static function convertBitDefaultValue(string|null $bitDefaultValue): string
    {
        return (string) preg_replace(
            "/^b'(\d*)'?$/",
            '$1',
            htmlspecialchars_decode((string) $bitDefaultValue, ENT_QUOTES),
            1,
        );
    }

    /**
     * Extracts the various parts from a column spec
     *
     * @param string $columnSpecification Column specification
     *
     * @return mixed[] associative array containing type, spec_in_brackets
     *          and possibly enum_set_values (another array)
     */
    public static function extractColumnSpec(string $columnSpecification): array
    {
        $firstBracketPos = mb_strpos($columnSpecification, '(');
        if ($firstBracketPos) {
            $specInBrackets = rtrim(
                mb_substr(
                    $columnSpecification,
                    $firstBracketPos + 1,
                    mb_strrpos($columnSpecification, ')') - $firstBracketPos - 1,
                ),
            );
            // convert to lowercase just to be sure
            $type = mb_strtolower(
                rtrim(mb_substr($columnSpecification, 0, $firstBracketPos)),
            );
        } else {
            // Split trailing attributes such as unsigned,
            // binary, zerofill and get data type name
            $typeParts = explode(' ', $columnSpecification);
            $type = mb_strtolower($typeParts[0]);
            $specInBrackets = '';
        }

        if ($type === 'enum' || $type === 'set') {
            // Define our working vars
            $enumSetValues = self::parseEnumSetValues($columnSpecification, false);
            $printType = $type
                . '(' . str_replace("','", "', '", $specInBrackets) . ')';
            $binary = false;
            $unsigned = false;
            $zerofill = false;
            $compressed = false;
        } else {
            $enumSetValues = [];

            /* Create printable type name */
            $printType = mb_strtolower($columnSpecification);

            // Strip the "BINARY" attribute, except if we find "BINARY(" because
            // this would be a BINARY or VARBINARY column type;
            // by the way, a BLOB should not show the BINARY attribute
            // because this is not accepted in MySQL syntax.
            if (str_contains($printType, 'binary') && ! preg_match('@binary[\(]@', $printType)) {
                $printType = str_replace('binary', '', $printType);
                $binary = true;
            } else {
                $binary = false;
            }

            $printType = (string) preg_replace('@zerofill@', '', $printType, -1, $zerofillCount);
            $zerofill = ($zerofillCount > 0);
            $printType = (string) preg_replace('@unsigned@', '', $printType, -1, $unsignedCount);
            $unsigned = ($unsignedCount > 0);
            $printType = (string) preg_replace('@\/\*!100301 compressed\*\/@', '', $printType, -1, $compressedCount);
            $compressed = ($compressedCount > 0);
            $printType = trim($printType);
        }

        $attribute = ' ';
        if ($binary) {
            $attribute = 'BINARY';
        }

        if ($unsigned) {
            $attribute = 'UNSIGNED';
        }

        if ($zerofill) {
            $attribute = 'UNSIGNED ZEROFILL';
        }

        if ($compressed) {
            // With InnoDB page compression, multiple compression algorithms are supported.
            // In contrast, with InnoDB's COMPRESSED row format, zlib is the only supported compression algorithm.
            // This means that the COMPRESSED row format has less compression options than InnoDB page compression does.
            // @see https://mariadb.com/kb/en/innodb-page-compression/#comparison-with-the-compressed-row-format
            $attribute = 'COMPRESSED=zlib';
        }

        $canContainCollation = false;
        if (! $binary && preg_match('@^(char|varchar|text|tinytext|mediumtext|longtext|set|enum)@', $type)) {
            $canContainCollation = true;
        }

        // for the case ENUM('&#8211;','&ldquo;')
        $displayedType = htmlspecialchars($printType, ENT_COMPAT);
        if (mb_strlen($printType) > $GLOBALS['cfg']['LimitChars']) {
            $displayedType = '<abbr title="' . htmlspecialchars($printType) . '">';
            $displayedType .= htmlspecialchars(
                mb_substr(
                    $printType,
                    0,
                    (int) $GLOBALS['cfg']['LimitChars'],
                ) . '...',
                ENT_COMPAT,
            );
            $displayedType .= '</abbr>';
        }

        return [
            'type' => $type,
            'spec_in_brackets' => $specInBrackets,
            'enum_set_values' => $enumSetValues,
            'print_type' => $printType,
            'binary' => $binary,
            'unsigned' => $unsigned,
            'zerofill' => $zerofill,
            'attribute' => $attribute,
            'can_contain_collation' => $canContainCollation,
            'displayed_type' => $displayedType,
        ];
    }

    /**
     * If the string starts with a \r\n pair (0x0d0a) add an extra \n
     *
     * @return string with the chars replaced
     */
    public static function duplicateFirstNewline(string $string): string
    {
        $firstOccurrence = mb_strpos($string, "\r\n");
        if ($firstOccurrence === 0) {
            return "\n" . $string;
        }

        return $string;
    }

    /**
     * Get the action word corresponding to a script name
     * in order to display it as a title in navigation panel
     *
     * @param string $target a valid value for $cfg['NavigationTreeDefaultTabTable'],
     *                       $cfg['NavigationTreeDefaultTabTable2'],
     *                       $cfg['DefaultTabTable'] or $cfg['DefaultTabDatabase']
     *
     * @return string|bool Title for the $cfg value
     */
    public static function getTitleForTarget(string $target): string|bool
    {
        $mapping = [
            'structure' => __('Structure'),
            'sql' => __('SQL'),
            'search' => __('Search'),
            'insert' => __('Insert'),
            'browse' => __('Browse'),
            'operations' => __('Operations'),
        ];

        return $mapping[$target] ?? false;
    }

    /**
     * Get the script name corresponding to a plain English config word
     * in order to append in links on navigation and main panel
     *
     * @param string $target   a valid value for
     *                         $cfg['NavigationTreeDefaultTabTable'],
     *                         $cfg['NavigationTreeDefaultTabTable2'],
     *                         $cfg['DefaultTabTable'], $cfg['DefaultTabDatabase'] or
     *                         $cfg['DefaultTabServer']
     * @param string $location one out of 'server', 'table', 'database'
     *
     * @return string script name corresponding to the config word
     */
    public static function getScriptNameForOption(string $target, string $location): string
    {
        return Url::getFromRoute(self::getUrlForOption($target, $location));
    }

    /**
     * Get the URL corresponding to a plain English config word
     * in order to append in links on navigation and main panel
     *
     * @param string $target   a valid value for
     *                         $cfg['NavigationTreeDefaultTabTable'],
     *                         $cfg['NavigationTreeDefaultTabTable2'],
     *                         $cfg['DefaultTabTable'], $cfg['DefaultTabDatabase'] or
     *                         $cfg['DefaultTabServer']
     * @param string $location one out of 'server', 'table', 'database'
     *
     * @return string The URL corresponding to the config word
     */
    public static function getUrlForOption(string $target, string $location): string
    {
        if ($location === 'server') {
            // Values for $cfg['DefaultTabServer']
            switch ($target) {
                case 'welcome':
                case 'index.php':
                    return '/';

                case 'databases':
                case 'server_databases.php':
                    return '/server/databases';

                case 'status':
                case 'server_status.php':
                    return '/server/status';

                case 'variables':
                case 'server_variables.php':
                    return '/server/variables';

                case 'privileges':
                case 'server_privileges.php':
                    return '/server/privileges';
            }
        } elseif ($location === 'database') {
            // Values for $cfg['DefaultTabDatabase']
            switch ($target) {
                case 'structure':
                case 'db_structure.php':
                    return '/database/structure';

                case 'sql':
                case 'db_sql.php':
                    return '/database/sql';

                case 'search':
                case 'db_search.php':
                    return '/database/search';

                case 'operations':
                case 'db_operations.php':
                    return '/database/operations';
            }
        } elseif ($location === 'table') {
            // Values for $cfg['DefaultTabTable'],
            // $cfg['NavigationTreeDefaultTabTable'] and
            // $cfg['NavigationTreeDefaultTabTable2']
            switch ($target) {
                case 'structure':
                case 'tbl_structure.php':
                    return '/table/structure';

                case 'sql':
                case 'tbl_sql.php':
                    return '/table/sql';

                case 'search':
                case 'tbl_select.php':
                    return '/table/search';

                case 'insert':
                case 'tbl_change.php':
                    return '/table/change';

                case 'browse':
                case 'sql.php':
                    return '/sql';
            }
        }

        return '/';
    }

    /**
     * Formats user string, expanding @VARIABLES@, accepting strftime format
     * string.
     *
     * @param string                     $string  Text where to do expansion.
     * @param callable|null              $escape  Function to call for escaping variable values.
     * @param array<string, string|null> $updates Array with overrides for default parameters (obtained from GLOBALS).
     * @psalm-param callable(string):string|null $escape
     */
    public static function expandUserString(
        string $string,
        callable|null $escape = null,
        array $updates = [],
    ): string {
        /* Content */
        $vars = [];
        $vars['http_host'] = Core::getenv('HTTP_HOST');
        $vars['server_name'] = $GLOBALS['cfg']['Server']['host'];
        $vars['server_verbose'] = $GLOBALS['cfg']['Server']['verbose'];

        if (empty($GLOBALS['cfg']['Server']['verbose'])) {
            $vars['server_verbose_or_name'] = $GLOBALS['cfg']['Server']['host'];
        } else {
            $vars['server_verbose_or_name'] = $GLOBALS['cfg']['Server']['verbose'];
        }

        $vars['database'] = $GLOBALS['db'];
        $vars['table'] = $GLOBALS['table'];
        $vars['phpmyadmin_version'] = 'phpMyAdmin ' . Version::VERSION;

        /* Update forced variables */
        foreach ($updates as $key => $val) {
            $vars[$key] = $val;
        }

        /**
         * Replacement mapping
         *
         * The __VAR__ ones are for backward compatibility, because user might still have it in cookies.
         */
        $replace = [
            '@HTTP_HOST@' => $vars['http_host'],
            '@SERVER@' => $vars['server_name'],
            '__SERVER__' => $vars['server_name'],
            '@VERBOSE@' => $vars['server_verbose'],
            '@VSERVER@' => $vars['server_verbose_or_name'],
            '@DATABASE@' => $vars['database'],
            '__DB__' => $vars['database'],
            '@TABLE@' => $vars['table'],
            '__TABLE__' => $vars['table'],
            '@PHPMYADMIN@' => $vars['phpmyadmin_version'],
        ];

        /* Optional escaping */
        if ($escape !== null) {
            $replace = array_map($escape, $replace);
        }

        /* Backward compatibility in 3.5.x */
        if (str_contains($string, '@FIELDS@')) {
            $string = strtr($string, ['@FIELDS@' => '@COLUMNS@']);
        }

        /* Fetch columns list if required */
        if (str_contains($string, '@COLUMNS@')) {
            $columnsList = $GLOBALS['dbi']->getColumns($GLOBALS['db'], $GLOBALS['table']);

            $columnNames = [];
            foreach ($columnsList as $column) {
                if ($escape !== null) {
                    $columnNames[] = self::$escape($column['Field']);
                } else {
                    $columnNames[] = $column['Field'];
                }
            }

            $replace['@COLUMNS@'] = implode(',', $columnNames);
        }

        /* Do the replacement */
        // phpcs:ignore Generic.PHP.DeprecatedFunctions
        return strtr((string) @strftime($string), $replace);
    }

    /**
     * This function processes the datatypes supported by the DB,
     * as specified in Types->getColumns() and returns an array
     * (useful for quickly checking if a datatype is supported).
     *
     * @return mixed[] An array of datatypes.
     */
    public static function getSupportedDatatypes(): array
    {
        $retval = [];
        foreach ($GLOBALS['dbi']->types->getColumns() as $value) {
            if (is_array($value)) {
                foreach ($value as $subvalue) {
                    if ($subvalue === '-') {
                        continue;
                    }

                    $retval[] = $subvalue;
                }
            } elseif ($value !== '-') {
                $retval[] = $value;
            }
        }

        return $retval;
    }

    /**
     * Returns a list of datatypes that are not (yet) handled by PMA.
     * Used by: /table/change and libraries/Routines.php
     *
     * @return mixed[] list of datatypes
     */
    public static function unsupportedDatatypes(): array
    {
        return [];
    }

    /**
     * This function is to check whether database support UUID
     */
    public static function isUUIDSupported(): bool
    {
        return Compatibility::isUUIDSupported($GLOBALS['dbi']);
    }

    /**
     * Checks if the current user has a specific privilege and returns true if the
     * user indeed has that privilege or false if they don't. This function must
     * only be used for features that are available since MySQL 5, because it
     * relies on the INFORMATION_SCHEMA database to be present.
     *
     * Example:   currentUserHasPrivilege('CREATE ROUTINE', 'mydb');
     *            // Checks if the currently logged in user has the global
     *            // 'CREATE ROUTINE' privilege or, if not, checks if the
     *            // user has this privilege on database 'mydb'.
     *
     * @param string      $priv The privilege to check
     * @param string|null $db   null, to only check global privileges
     *                          string, db name where to also check
     *                          for privileges
     * @param string|null $tbl  null, to only check global/db privileges
     *                          string, table name where to also check
     *                          for privileges
     */
    public static function currentUserHasPrivilege(string $priv, string|null $db = null, string|null $tbl = null): bool
    {
        // Get the username for the current user in the format
        // required to use in the information schema database.
        [$user, $host] = $GLOBALS['dbi']->getCurrentUserAndHost();

        // MySQL is started with --skip-grant-tables
        if ($user === '') {
            return true;
        }

        $username = "''";
        $username .= str_replace("'", "''", $user);
        $username .= "''@''";
        $username .= str_replace("'", "''", $host);
        $username .= "''";

        // Prepare the query
        $query = 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`%s` '
               . "WHERE GRANTEE='%s' AND PRIVILEGE_TYPE='%s'";

        // Check global privileges first.
        $userPrivileges = $GLOBALS['dbi']->fetchValue(
            sprintf(
                $query,
                'USER_PRIVILEGES',
                $username,
                $priv,
            ),
        );
        if ($userPrivileges) {
            return true;
        }

        // If a database name was provided and user does not have the
        // required global privilege, try database-wise permissions.
        if ($db === null) {
            // There was no database name provided and the user
            // does not have the correct global privilege.
            return false;
        }

        $query .= ' AND %s LIKE `TABLE_SCHEMA`';
        $schemaPrivileges = $GLOBALS['dbi']->fetchValue(
            sprintf(
                $query,
                'SCHEMA_PRIVILEGES',
                $username,
                $priv,
                $GLOBALS['dbi']->quoteString($db),
            ),
        );
        if ($schemaPrivileges) {
            return true;
        }

        // If a table name was also provided and we still didn't
        // find any valid privileges, try table-wise privileges.
        if ($tbl !== null) {
            $query .= ' AND TABLE_NAME=%s';
            $tablePrivileges = $GLOBALS['dbi']->fetchValue(
                sprintf(
                    $query,
                    'TABLE_PRIVILEGES',
                    $username,
                    $priv,
                    $GLOBALS['dbi']->quoteString($db),
                    $GLOBALS['dbi']->quoteString($tbl),
                ),
            );
            if ($tablePrivileges) {
                return true;
            }
        }

        /**
         * If we reached this point, the user does not
         * have even valid table-wise privileges.
         */
        return false;
    }

    /**
     * Returns server type for current connection
     *
     * Known types are: MariaDB, Percona Server and MySQL (default)
     *
     * @phpstan-return 'MariaDB'|'Percona Server'|'MySQL'
     */
    public static function getServerType(): string
    {
        if ($GLOBALS['dbi']->isMariaDB()) {
            return 'MariaDB';
        }

        if ($GLOBALS['dbi']->isPercona()) {
            return 'Percona Server';
        }

        return 'MySQL';
    }

    /**
     * Parses ENUM/SET values
     *
     * @param string $definition The definition of the column
     *                           for which to parse the values
     * @param bool   $escapeHtml Whether to escape html entities
     *
     * @return string[]
     */
    public static function parseEnumSetValues(string $definition, bool $escapeHtml = true): array
    {
        $valuesString = htmlentities($definition, ENT_COMPAT, 'UTF-8');
        // There is a JS port of the below parser in functions.js
        // If you are fixing something here,
        // you need to also update the JS port.
        $values = [];
        $inString = false;
        $buffer = '';

        for ($i = 0, $length = mb_strlen($valuesString); $i < $length; $i++) {
            $curr = mb_substr($valuesString, $i, 1);
            $next = $i == mb_strlen($valuesString) - 1
                ? ''
                : mb_substr($valuesString, $i + 1, 1);

            if (! $inString && $curr == "'") {
                $inString = true;
            } elseif (($inString && $curr === '\\') && $next === '\\') {
                $buffer .= '&#92;';
                $i++;
            } elseif (($inString && $next == "'") && ($curr == "'" || $curr === '\\')) {
                $buffer .= '&#39;';
                $i++;
            } elseif ($inString && $curr == "'") {
                $inString = false;
                $values[] = $buffer;
                $buffer = '';
            } elseif ($inString) {
                $buffer .= $curr;
            }
        }

        if (strlen($buffer) > 0) {
            // The leftovers in the buffer are the last value (if any)
            $values[] = $buffer;
        }

        if (! $escapeHtml) {
            foreach ($values as $key => $value) {
                $values[$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $values;
    }

    /**
     * Return the list of tabs for the menu with corresponding names
     *
     * @param string|null $level 'server', 'db' or 'table' level
     *
     * @return mixed[]|null list of tabs for the menu
     */
    public static function getMenuTabList(string|null $level = null): array|null
    {
        $tabList = [
            'server' => [
                'databases' => __('Databases'),
                'sql' => __('SQL'),
                'status' => __('Status'),
                'rights' => __('Users'),
                'export' => __('Export'),
                'import' => __('Import'),
                'settings' => __('Settings'),
                'binlog' => __('Binary log'),
                'replication' => __('Replication'),
                'vars' => __('Variables'),
                'charset' => __('Charsets'),
                'plugins' => __('Plugins'),
                'engine' => __('Engines'),
            ],
            'db' => [
                'structure' => __('Structure'),
                'sql' => __('SQL'),
                'search' => __('Search'),
                'query' => __('Query'),
                'export' => __('Export'),
                'import' => __('Import'),
                'operation' => __('Operations'),
                'privileges' => __('Privileges'),
                'routines' => __('Routines'),
                'events' => __('Events'),
                'triggers' => __('Triggers'),
                'tracking' => __('Tracking'),
                'designer' => __('Designer'),
                'central_columns' => __('Central columns'),
            ],
            'table' => [
                'browse' => __('Browse'),
                'structure' => __('Structure'),
                'sql' => __('SQL'),
                'search' => __('Search'),
                'insert' => __('Insert'),
                'export' => __('Export'),
                'import' => __('Import'),
                'privileges' => __('Privileges'),
                'operation' => __('Operations'),
                'tracking' => __('Tracking'),
                'triggers' => __('Triggers'),
            ],
        ];

        if ($level === null) {
            return $tabList;
        }

        if (array_key_exists($level, $tabList)) {
            return $tabList[$level];
        }

        return null;
    }

    /**
     * Add fractional seconds to time, datetime and timestamp strings.
     * If the string contains fractional seconds,
     * pads it with 0s up to 6 decimal places.
     *
     * @param string $value time, datetime or timestamp strings
     *
     * @return string time, datetime or timestamp strings with fractional seconds
     */
    public static function addMicroseconds(string $value): string
    {
        if ($value === '' || $value === 'CURRENT_TIMESTAMP' || $value === 'current_timestamp()') {
            return $value;
        }

        if (! str_contains($value, '.')) {
            return $value . '.000000';
        }

        $value .= '000000';

        return mb_substr(
            $value,
            0,
            mb_strpos($value, '.') + 7,
        );
    }

    /**
     * Reads the file, detects the compression MIME type, closes the file
     * and returns the MIME type
     *
     * @param resource $file the file handle
     *
     * @return string the MIME type for compression, or 'none'
     */
    public static function getCompressionMimeType($file): string
    {
        $test = fread($file, 4);

        if ($test === false) {
            fclose($file);

            return 'none';
        }

        $len = strlen($test);
        fclose($file);
        if ($len >= 2 && $test[0] === chr(31) && $test[1] === chr(139)) {
            return 'application/gzip';
        }

        if ($len >= 3 && substr($test, 0, 3) === 'BZh') {
            return 'application/bzip2';
        }

        if ($len >= 4 && $test == "PK\003\004") {
            return 'application/zip';
        }

        return 'none';
    }

    /**
     * Provide COLLATE clause, if required, to perform case sensitive comparisons
     * for queries on information_schema.
     *
     * @return string COLLATE clause if needed or empty string.
     */
    public static function getCollateForIS(): string
    {
        $names = $GLOBALS['dbi']->getLowerCaseNames();
        if ($names === 0) {
            return 'COLLATE utf8_bin';
        }

        if ($names === 2) {
            return 'COLLATE utf8_general_ci';
        }

        return '';
    }

    /**
     * Process the index data.
     *
     * @param mixed[] $indexes index data
     *
     * @return mixed[] processes index data
     */
    public static function processIndexData(array $indexes): array
    {
        $lastIndex = '';

        $primary = '';
        $pkArray = []; // will be use to emphasis prim. keys in the table
        $indexesInfo = [];
        $indexesData = [];

        // view
        foreach ($indexes as $row) {
            // Backups the list of primary keys
            if ($row['Key_name'] === 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
                $pkArray[$row['Column_name']] = 1;
            }

            // Retains keys informations
            if ($row['Key_name'] != $lastIndex) {
                $lastIndex = $row['Key_name'];
            }

            $indexesInfo[$row['Key_name']]['Sequences'][] = $row['Seq_in_index'];
            $indexesInfo[$row['Key_name']]['Non_unique'] = $row['Non_unique'];
            if (isset($row['Cardinality'])) {
                $indexesInfo[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
            }

            // I don't know what does following column mean....
            // $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];

            $indexesInfo[$row['Key_name']]['Comment'] = $row['Comment'];

            $indexesData[$row['Key_name']][$row['Seq_in_index']]['Column_name'] = $row['Column_name'];
            if (! isset($row['Sub_part'])) {
                continue;
            }

            $indexesData[$row['Key_name']][$row['Seq_in_index']]['Sub_part'] = $row['Sub_part'];
        }

        return [$primary, $pkArray, $indexesInfo, $indexesData];
    }

    /**
     * Gets the list of tables in the current db and information about these tables if possible.
     *
     * @return array<int, array|int>
     * @psalm-return array{array, int, int}
     */
    public static function getDbInfo(ServerRequest $request, string $db, bool $isResultLimited = true): array
    {
        /**
         * information about tables in db
         */
        $tables = [];

        // Special speedup for newer MySQL Versions (in 4.0 format changed)
        if ($GLOBALS['cfg']['SkipLockedTables'] === true) {
            $dbInfoResult = $GLOBALS['dbi']->query(
                'SHOW OPEN TABLES FROM ' . self::backquote($db) . ' WHERE In_use > 0;',
            );

            // Blending out tables in use
            if ($dbInfoResult->numRows() > 0) {
                $tables = self::getTablesWhenOpen($db, $dbInfoResult);
            }
        }

        $totalNumTables = null;
        if ($tables === []) {
            // Set some sorting defaults
            $sort = 'Name';
            $sortOrder = 'ASC';

            /** @var mixed $sortParam */
            $sortParam = $request->getParam('sort');
            if (is_string($sortParam)) {
                $sortableNameMappings = [
                    'table' => 'Name',
                    'records' => 'Rows',
                    'type' => 'Engine',
                    'collation' => 'Collation',
                    'size' => 'Data_length',
                    'overhead' => 'Data_free',
                    'creation' => 'Create_time',
                    'last_update' => 'Update_time',
                    'last_check' => 'Check_time',
                    'comment' => 'Comment',
                ];

                // Make sure the sort type is implemented
                if (isset($sortableNameMappings[$sortParam])) {
                    $sort = $sortableNameMappings[$sortParam];
                    if ($request->getParam('sort_order') === 'DESC') {
                        $sortOrder = 'DESC';
                    }
                }
            }

            $groupWithSeparator = false;
            $tableType = null;
            $limitOffset = 0;
            $limitCount = false;
            $groupTable = [];

            /** @var mixed $tableGroupParam */
            $tableGroupParam = $request->getParam('tbl_group');
            /** @var mixed $tableTypeParam */
            $tableTypeParam = $request->getParam('tbl_type');
            if (
                is_string($tableGroupParam) && $tableGroupParam !== ''
                || is_string($tableTypeParam) && $tableTypeParam !== ''
            ) {
                if (is_string($tableTypeParam) && $tableTypeParam !== '') {
                    // only tables for selected type
                    $tableType = $tableTypeParam;
                }

                if (is_string($tableGroupParam) && $tableGroupParam !== '') {
                    // only tables for selected group
                    // include the table with the exact name of the group if such exists
                    $groupTable = $GLOBALS['dbi']->getTablesFull(
                        $db,
                        $tableGroupParam,
                        false,
                        0,
                        false,
                        $sort,
                        $sortOrder,
                        $tableType,
                    );
                    $groupWithSeparator = $tableGroupParam . $GLOBALS['cfg']['NavigationTreeTableSeparator'];
                }
            } else {
                // all tables in db
                // - get the total number of tables
                //  (needed for proper working of the MaxTableList feature)
                $tables = $GLOBALS['dbi']->getTables($db);
                $totalNumTables = count($tables);
                if ($isResultLimited) {
                    // fetch the details for a possible limited subset
                    $limitOffset = self::getTableListPosition($request, $db);
                    $limitCount = true;
                }
            }

            // We must use union operator here instead of array_merge to preserve numerical keys
            $tables = $groupTable + $GLOBALS['dbi']->getTablesFull(
                $db,
                $groupWithSeparator !== false ? $groupWithSeparator : $tables,
                $groupWithSeparator !== false,
                $limitOffset,
                $limitCount,
                $sort,
                $sortOrder,
                $tableType,
            );
        }

        $numTables = count($tables);

        return [
            $tables,
            $numTables,
            $totalNumTables ?? $numTables, // needed for proper working of the MaxTableList feature
        ];
    }

    /**
     * Gets the list of tables in the current db, taking into account
     * that they might be "in use"
     *
     * @param string          $db           database name
     * @param ResultInterface $dbInfoResult result set
     *
     * @return mixed[] list of tables
     */
    public static function getTablesWhenOpen(string $db, ResultInterface $dbInfoResult): array
    {
        $sotCache = [];
        $tables = [];

        foreach ($dbInfoResult as $tmp) {
            $sotCache[$tmp['Table']] = true;
        }

        // is there at least one "in use" table?
        if ($sotCache !== []) {
            $tblGroupSql = '';
            $whereAdded = false;
            if (
                isset($_REQUEST['tbl_group'])
                && is_scalar($_REQUEST['tbl_group'])
                && strlen((string) $_REQUEST['tbl_group']) > 0
            ) {
                $group = $GLOBALS['dbi']->escapeMysqlWildcards((string) $_REQUEST['tbl_group']);
                $groupWithSeparator = $GLOBALS['dbi']->escapeMysqlWildcards(
                    $_REQUEST['tbl_group'] . $GLOBALS['cfg']['NavigationTreeTableSeparator'],
                );
                $tblGroupSql .= ' WHERE ('
                    . self::backquote('Tables_in_' . $db)
                    . ' LIKE ' . $GLOBALS['dbi']->quoteString($groupWithSeparator . '%')
                    . ' OR '
                    . self::backquote('Tables_in_' . $db)
                    . ' LIKE ' . $GLOBALS['dbi']->quoteString($group) . ')';
                $whereAdded = true;
            }

            if (isset($_REQUEST['tbl_type']) && in_array($_REQUEST['tbl_type'], ['table', 'view'])) {
                $tblGroupSql .= $whereAdded ? ' AND' : ' WHERE';
                if ($_REQUEST['tbl_type'] === 'view') {
                    $tblGroupSql .= " `Table_type` NOT IN ('BASE TABLE', 'SYSTEM VERSIONED')";
                } else {
                    $tblGroupSql .= " `Table_type` IN ('BASE TABLE', 'SYSTEM VERSIONED')";
                }
            }

            $dbInfoResult = $GLOBALS['dbi']->query('SHOW FULL TABLES FROM ' . self::backquote($db) . $tblGroupSql);
            unset($tblGroupSql, $whereAdded);

            if ($dbInfoResult->numRows() > 0) {
                $names = [];
                while ($tmp = $dbInfoResult->fetchRow()) {
                    if (! isset($sotCache[$tmp[0]])) {
                        $names[] = $tmp[0];
                    } else { // table in use
                        $tables[$tmp[0]] = [
                            'TABLE_NAME' => $tmp[0],
                            'ENGINE' => '',
                            'TABLE_TYPE' => '',
                            'TABLE_ROWS' => 0,
                            'TABLE_COMMENT' => '',
                        ];
                    }
                }

                if ($names !== []) {
                    $tables += $GLOBALS['dbi']->getTablesFull($db, $names);
                }

                if ($GLOBALS['cfg']['NaturalOrder']) {
                    uksort($tables, 'strnatcasecmp');
                }
            }

            unset($sotCache);
        }

        return $tables;
    }

    /**
     * Returns list of used PHP extensions.
     *
     * @return string[]
     */
    public static function listPHPExtensions(): array
    {
        $result = [];
        if (function_exists('mysqli_connect')) {
            $result[] = 'mysqli';
        }

        if (extension_loaded('curl')) {
            $result[] = 'curl';
        }

        if (extension_loaded('mbstring')) {
            $result[] = 'mbstring';
        }

        if (extension_loaded('sodium')) {
            $result[] = 'sodium';
        }

        return $result;
    }

    /**
     * Converts given (request) parameter to string
     *
     * @param mixed $value Value to convert
     */
    public static function requestString(mixed $value): string
    {
        while (is_array($value) || is_object($value)) {
            if (is_object($value)) {
                $value = (array) $value;
            }

            $value = reset($value);
        }

        return trim((string) $value);
    }

    /**
     * Generates random string consisting of ASCII chars
     *
     * @param int  $length Length of string
     * @param bool $asHex  (optional) Send the result as hex
     */
    public static function generateRandom(int $length, bool $asHex = false): string
    {
        $result = '';

        while (strlen($result) < $length) {
            // Get random byte and strip highest bit
            // to get ASCII only range
            $byte = ord(random_bytes(1)) & 0x7f;
            // We want only ASCII chars and no DEL character (127)
            if ($byte <= 32 || $byte === 127) {
                continue;
            }

            $result .= chr($byte);
        }

        return $asHex ? bin2hex($result) : $result;
    }

    /**
     * Wrapper around PHP date function
     *
     * @param string $format Date format string
     */
    public static function date(string $format): string
    {
        return date($format);
    }

    /**
     * Wrapper around php's set_time_limit
     */
    public static function setTimeLimit(): void
    {
        // The function can be disabled in php.ini
        if (! function_exists('set_time_limit')) {
            return;
        }

        @set_time_limit((int) $GLOBALS['cfg']['ExecTimeLimit']);
    }

    /**
     * Access to a multidimensional array by dot notation
     *
     * @param mixed[]        $array   List of values
     * @param string|mixed[] $path    Path to searched value
     * @param mixed          $default Default value
     *
     * @return mixed Searched value
     */
    public static function getValueByKey(array $array, string|array $path, mixed $default = null): mixed
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $p = array_shift($path);
        while (isset($p)) {
            if (! isset($array[$p])) {
                return $default;
            }

            $array = $array[$p];
            $p = array_shift($path);
        }

        return $array;
    }

    /**
     * Creates a clickable column header for table information
     *
     * @param string $title            Title to use for the link
     * @param string $sort             Corresponds to sortable data name mapped
     *                                 in Util::getDbInfo
     * @param string $initialSortOrder Initial sort order
     *
     * @return string Link to be displayed in the table header
     */
    public static function sortableTableHeader(string $title, string $sort, string $initialSortOrder = 'ASC'): string
    {
        $requestedSort = 'table';
        $requestedSortOrder = $futureSortOrder = $initialSortOrder;
        // If the user requested a sort
        if (isset($_REQUEST['sort'])) {
            $requestedSort = $_REQUEST['sort'];
            if (isset($_REQUEST['sort_order'])) {
                $requestedSortOrder = $_REQUEST['sort_order'];
            }
        }

        $orderImg = '';
        $orderLinkParams = [];
        $orderLinkParams['title'] = __('Sort');
        // If this column was requested to be sorted.
        if ($requestedSort == $sort) {
            if ($requestedSortOrder === 'ASC') {
                $futureSortOrder = 'DESC';
                // current sort order is ASC
                $orderImg = ' ' . Generator::getImage(
                    's_asc',
                    __('Ascending'),
                    ['class' => 'sort_arrow', 'title' => ''],
                );
                $orderImg .= ' ' . Generator::getImage(
                    's_desc',
                    __('Descending'),
                    ['class' => 'sort_arrow hide', 'title' => ''],
                );
                // but on mouse over, show the reverse order (DESC)
                $orderLinkParams['onmouseover'] = "$('.sort_arrow').toggle();";
                // on mouse out, show current sort order (ASC)
                $orderLinkParams['onmouseout'] = "$('.sort_arrow').toggle();";
            } else {
                $futureSortOrder = 'ASC';
                // current sort order is DESC
                $orderImg = ' ' . Generator::getImage(
                    's_asc',
                    __('Ascending'),
                    ['class' => 'sort_arrow hide', 'title' => ''],
                );
                $orderImg .= ' ' . Generator::getImage(
                    's_desc',
                    __('Descending'),
                    ['class' => 'sort_arrow', 'title' => ''],
                );
                // but on mouse over, show the reverse order (ASC)
                $orderLinkParams['onmouseover'] = "$('.sort_arrow').toggle();";
                // on mouse out, show current sort order (DESC)
                $orderLinkParams['onmouseout'] = "$('.sort_arrow').toggle();";
            }
        }

        $urlParams = [
            'db' => $_REQUEST['db'],
            'pos' => 0, // We set the position back to 0 every time they sort.
            'sort' => $sort,
            'sort_order' => $futureSortOrder,
        ];

        if (isset($_REQUEST['tbl_type']) && in_array($_REQUEST['tbl_type'], ['view', 'table'])) {
            $urlParams['tbl_type'] = $_REQUEST['tbl_type'];
        }

        if (! empty($_REQUEST['tbl_group'])) {
            $urlParams['tbl_group'] = $_REQUEST['tbl_group'];
        }

        $url = Url::getFromRoute('/database/structure');

        return Generator::linkOrButton($url, $urlParams, $title . $orderImg, $orderLinkParams);
    }

    /**
     * Check that input is an int or an int in a string
     *
     * @param mixed $input input to check
     */
    public static function isInteger(mixed $input): bool
    {
        return is_scalar($input) && ctype_digit((string) $input);
    }

    /**
     * Get the protocol from the RFC 7239 Forwarded header
     *
     * @param string $headerContents The Forwarded header contents
     *
     * @return string the protocol http/https
     */
    public static function getProtoFromForwardedHeader(string $headerContents): string
    {
        if (str_contains($headerContents, '=')) {// does not contain any equal sign
            $hops = explode(',', $headerContents);
            $parts = explode(';', $hops[0]);
            foreach ($parts as $part) {
                $keyValueArray = explode('=', $part, 2);
                if (count($keyValueArray) !== 2) {
                    continue;
                }

                [$keyName, $value] = $keyValueArray;
                $value = trim(strtolower($value));
                if (strtolower(trim($keyName)) === 'proto' && in_array($value, ['http', 'https'])) {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Check if error reporting is available
     */
    public static function isErrorReportingAvailable(): bool
    {
        // issue #16256 - PHP 7.x does not return false for a core function
        if (PHP_MAJOR_VERSION < 8) {
            $disabled = ini_get('disable_functions');
            if (is_string($disabled)) {
                $disabled = explode(',', $disabled);
                $disabled = array_map(trim(...), $disabled);

                return ! in_array('error_reporting', $disabled);
            }
        }

        return function_exists('error_reporting');
    }

    public static function getTableListPosition(ServerRequest $request, string $db): int
    {
        if (! isset($_SESSION['tmpval']['table_limit_offset']) || $_SESSION['tmpval']['table_limit_offset_db'] != $db) {
            $_SESSION['tmpval']['table_limit_offset'] = 0;
            $_SESSION['tmpval']['table_limit_offset_db'] = $db;
        }

        /** @var string|null $posParam */
        $posParam = $request->getParam('pos');
        if (is_numeric($posParam)) {
            $_SESSION['tmpval']['table_limit_offset'] = (int) $posParam;
        }

        return $_SESSION['tmpval']['table_limit_offset'];
    }
}
