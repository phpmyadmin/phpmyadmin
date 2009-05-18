<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-Transformation
 * @version $Id$
 */

/**
 *
 */
function PMA_transformation_text_plain__dateformat($buffer, $options = array(), $meta = '') {
    // possibly use a global transform and feed it with special options:
    // include './libraries/transformations/global.inc.php';

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
            $options[1] = $GLOBALS['datefmt'];
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
    // (Note: prior to MySQL 4.1, TIMESTAMP has a display size, for example
    // TIMESTAMP(8) means YYYYMMDD)
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
            $timestamp = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'], $d['year']);
        }
    // If all fails, assume one of the dozens of valid strtime() syntaxes (http://www.gnu.org/manual/tar-1.12/html_chapter/tar_7.html)
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
            $text = PMA_localisedDate($timestamp, $options[1]);
        } elseif ($options[2] == 'utc') {
            $text = gmdate($options[1], $timestamp);
        } else {
            $text = 'INVALID DATE TYPE';
        }
        $buffer = '<dfn onclick="alert(\'' . $source . '\');" title="' . $source . '">' . $text . '</dfn>';
    }

    return $buffer;
}

?>
