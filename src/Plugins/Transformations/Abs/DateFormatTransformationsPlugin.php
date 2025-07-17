<?php
/**
 * Abstract class for the date format transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use DateTimeImmutable;
use PhpMyAdmin\Config;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Util;

use function __;
use function checkdate;
use function htmlspecialchars;
use function is_int;
use function is_numeric;
use function is_string;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function mktime;
use function preg_match;
use function strtotime;

use const ENT_COMPAT;

/**
 * Provides common methods for all of the date format transformations plugins.
 */
abstract class DateFormatTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __(
            'Displays a TIME, TIMESTAMP, DATETIME or numeric unix timestamp'
            . ' column as formatted date. The first option is the offset (in'
            . ' hours) which will be added to the timestamp (Default: 0). Use'
            . ' second option to specify a different date/time format string.'
            . ' Third option determines whether you want to see local date or'
            . ' UTC one (use "local" or "utc" strings) for that. According to'
            . ' that, date format has different value - for "local" see the'
            . ' documentation for PHP\'s strftime() function and for "utc" it'
            . ' is done using gmdate() function.',
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param mixed[]            $options transformation options
     * @param FieldMetadata|null $meta    meta information
     */
    public function applyTransformation(string $buffer, array $options = [], FieldMetadata|null $meta = null): string
    {
        // possibly use a global transform and feed it with special options
        $cfg = Config::getInstance()->settings;
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['DateFormat']);

        $timestamp = -1;

        // INT columns will be treated as UNIX timestamps
        // and need to be detected before the verification for
        // MySQL TIMESTAMP
        if ($meta !== null && $meta->isType(FieldMetadata::TYPE_INT)) {
            $timestamp = $buffer;

            // Detect TIMESTAMP(6 | 8 | 10 | 12 | 14)
            // TIMESTAMP (2 | 4) not supported here.
            // (Note: prior to MySQL 4.1, TIMESTAMP has a display size
            // for example TIMESTAMP(8) means YYYYMMDD)
        } elseif (preg_match('/^(\d{2}){3,7}$/', $buffer) === 1) {
            if (mb_strlen($buffer) === 14 || mb_strlen($buffer) === 8) {
                $offset = 4;
            } else {
                $offset = 2;
            }

            $aDate = [];
            $aDate['year'] = (int) mb_substr($buffer, 0, $offset);
            $aDate['month'] = (int) mb_substr($buffer, $offset, 2);
            $aDate['day'] = (int) mb_substr($buffer, $offset + 2, 2);
            $aDate['hour'] = (int) mb_substr($buffer, $offset + 4, 2);
            $aDate['minute'] = (int) mb_substr($buffer, $offset + 6, 2);
            $aDate['second'] = (int) mb_substr($buffer, $offset + 8, 2);
            if (checkdate($aDate['month'], $aDate['day'], $aDate['year'])) {
                $timestamp = mktime(
                    $aDate['hour'],
                    $aDate['minute'],
                    $aDate['second'],
                    $aDate['month'],
                    $aDate['day'],
                    $aDate['year'],
                );
            }

            // If all fails, assume one of the dozens of valid strtime() syntaxes
            // (https://www.gnu.org/manual/tar-1.12/html_chapter/tar_7.html)
        } elseif (preg_match('/^[0-9]\d{1,9}$/', $buffer) === 1) {
            $timestamp = (int) $buffer;
        } else {
            $timestamp = strtotime($buffer);
        }

        // If all above failed, maybe it's a Unix timestamp already?
        if ($timestamp < 0 && preg_match('/^[1-9]\d{1,9}$/', $buffer) === 1) {
            $timestamp = $buffer;
        }

        // Reformat a valid timestamp
        if (! is_numeric($timestamp) || $timestamp < 0) {
            return htmlspecialchars($buffer);
        }

        $timestampOffset = is_int($options[0]) ? $options[0] : 0;
        $timestamp -= $timestampOffset * 60 * 60;
        $source = $buffer;

        $timeZone = $options[2] === 'utc' ? 'utc' : 'local';
        $dateFormat = is_string($options[1]) ? $options[1] : '';
        if ($dateFormat === '' && $timeZone === 'local') {
            $dateFormat = __('%B %d, %Y at %I:%M %p');
        } elseif ($dateFormat === '') {
            $dateFormat = 'Y-m-d  H:i:s';
        }

        $text = match ($timeZone) {
            'local' => Util::localisedDate((new DateTimeImmutable())->setTimestamp((int) $timestamp), $dateFormat),
            'utc' => (new DateTimeImmutable('@' . $timestamp))->format($dateFormat),
        };

        return '<dfn onclick="alert(' . htmlspecialchars((string) json_encode($source), ENT_COMPAT) . ');" title="'
            . htmlspecialchars($source) . '">' . htmlspecialchars($text) . '</dfn>';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'Date Format';
    }
}
