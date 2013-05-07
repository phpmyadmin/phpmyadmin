<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the date format transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage DateFormat
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

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
     * @return void
     */
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        // possibly use a global transform and feed it with special options

        // further operations on $buffer using the $options[] array.
        if (empty($options[0])) {
            $options[0] = 0;
        }

        if (empty($options[2])) {
            $options[2] = 'local';
        } else {
            $options[2] = strtolower($options[2]);
        }

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
        } else if (preg_match('/^(\d{2}){3,7}$/', $buffer)) {

            if (strlen($buffer) == 14 || strlen($buffer) == 8) {
                $offset = 4;
            } else {
                $offset = 2;
            }

            $d = array();
            $d['year']   = substr($buffer, 0, $offset);
            $d['month']  = substr($buffer, $offset, 2);
            $d['day']    = substr($buffer, $offset + 2, 2);
            $d['hour']   = substr($buffer, $offset + 4, 2);
            $d['minute'] = substr($buffer, $offset + 6, 2);
            $d['second'] = substr($buffer, $offset + 8, 2);

            if (checkdate($d['month'], $d['day'], $d['year'])) {
                $timestamp = mktime(
                    $d['hour'],
                    $d['minute'],
                    $d['second'],
                    $d['month'],
                    $d['day'],
                    $d['year']
                );
            }
            // If all fails, assume one of the dozens of valid strtime() syntaxes
            // (http://www.gnu.org/manual/tar-1.12/html_chapter/tar_7.html)
        } else {
            if (preg_match('/^[0-9]\d{1,9}$/', $buffer)) {
                $timestamp = (int)$buffer;
            } else {
                $timestamp = strtotime($buffer);
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
                $text = PMA_Util::localisedDate(
                    $timestamp,
                    $options[1]
                );
            } elseif ($options[2] == 'utc') {
                $text = gmdate($options[1], $timestamp);
            } else {
                $text = 'INVALID DATE TYPE';
            }
            $buffer = '<dfn onclick="alert(\'' . $source . '\');" title="'
                . $source . '">' . $text . '</dfn>';
        }

        return $buffer;
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @todo implement
     * @return void
     */
    public function update (SplSubject $subject)
    {
        ;
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
?>