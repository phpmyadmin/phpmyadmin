<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the date format transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage DateFormat
 */
namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;

/**
 * Provides common methods for all of the date format transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class DateFormatTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
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
            . ' is done using gmdate() function.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string $buffer  text to be transformed
     * @param array  $options transformation options
     * @param string $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = array(), $meta = '')
    {
        // possibly use a global transform and feed it with special options
        $cfg = $GLOBALS['cfg'];
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['DateFormat']);

        // further operations on $buffer using the $options[] array.
        $options[2] = mb_strtolower($options[2]);

        if (empty($options[1])) {
            if ($options[2] == 'local') {
                $options[1] = __('%B %d, %Y at %I:%M %p');
            } else {
                $options[1] = 'Y-m-d  H:i:s';
            }
        }

        $timestamp = -1;

        // INT columns will be treated as UNIX timestamps
        // and need to be detected before the verification for
        // MySQL TIMESTAMP
        if ($meta->type == 'int') {
            $timestamp = $buffer;

            // Detect TIMESTAMP(6 | 8 | 10 | 12 | 14)
            // TIMESTAMP (2 | 4) not supported here.
            // (Note: prior to MySQL 4.1, TIMESTAMP has a display size
            // for example TIMESTAMP(8) means YYYYMMDD)
        } else {
            if (preg_match('/^(\d{2}){3,7}$/', $buffer)) {

                if (mb_strlen($buffer) == 14 || mb_strlen($buffer) == 8) {
                    $offset = 4;
                } else {
                    $offset = 2;
                }

                $aDate = array();
                $aDate['year'] = (int)
                mb_substr($buffer, 0, $offset);
                $aDate['month'] = (int)
                mb_substr($buffer, $offset, 2);
                $aDate['day'] = (int)
                mb_substr($buffer, $offset + 2, 2);
                $aDate['hour'] = (int)
                mb_substr($buffer, $offset + 4, 2);
                $aDate['minute'] = (int)
                mb_substr($buffer, $offset + 6, 2);
                $aDate['second'] = (int)
                mb_substr($buffer, $offset + 8, 2);

                if (checkdate($aDate['month'], $aDate['day'], $aDate['year'])) {
                    $timestamp = mktime(
                        $aDate['hour'],
                        $aDate['minute'],
                        $aDate['second'],
                        $aDate['month'],
                        $aDate['day'],
                        $aDate['year']
                    );
                }
                // If all fails, assume one of the dozens of valid strtime() syntaxes
                // (https://www.gnu.org/manual/tar-1.12/html_chapter/tar_7.html)
            } else {
                if (preg_match('/^[0-9]\d{1,9}$/', $buffer)) {
                    $timestamp = (int)$buffer;
                } else {
                    $timestamp = strtotime($buffer);
                }
            }
        }

        // If all above failed, maybe it's a Unix timestamp already?
        if ($timestamp < 0 && preg_match('/^[1-9]\d{1,9}$/', $buffer)) {
            $timestamp = $buffer;
        }

        // Reformat a valid timestamp
        if ($timestamp >= 0) {
            $timestamp -= $options[0] * 60 * 60;
            $source = $buffer;
            if ($options[2] == 'local') {
                $text = Util::localisedDate(
                    $timestamp,
                    $options[1]
                );
            } elseif ($options[2] == 'utc') {
                $text = gmdate($options[1], $timestamp);
            } else {
                $text = 'INVALID DATE TYPE';
            }
            return '<dfn onclick="alert(\'' . Sanitize::jsFormat($source, false) . '\');" title="'
                . htmlspecialchars($source) . '">' . htmlspecialchars($text) . '</dfn>';
        }

        return htmlspecialchars($buffer);
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Date Format";
    }
}
