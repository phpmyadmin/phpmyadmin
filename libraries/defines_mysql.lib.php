<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * DEFINES MYSQL RELATED VARIABLES & CONSTANTS
 * Overview:
 *    PMA_MYSQL_INT_VERSION    (int)    - eg: 32339 instead of 3.23.39
 */

if (!defined('PMA_MYSQL_INT_VERSION') && isset($userlink)) {
    if (!empty($server)) {
        $result = PMA_mysql_query('SELECT VERSION() AS version', $userlink);
        if ($result != FALSE && @mysql_num_rows($result) > 0) {
            $row   = PMA_mysql_fetch_array($result);
            $match = explode('.', $row['version']);
            mysql_free_result($result);
        }
    } // end server id is defined case

    if (!isset($match) || !isset($match[0])) {
        $match[0] = 3;
    }
    if (!isset($match[1])) {
        $match[1] = 23;
    }
    if (!isset($match[2])) {
        $match[2] = 32;
    }

    if(!isset($row)) {
        $row['version'] = '3.23.32';
    }

    define('PMA_MYSQL_INT_VERSION', (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2])));
    define('PMA_MYSQL_STR_VERSION', $row['version']);
    unset($result, $row, $match);
}

?>
