<?php
/* $Id$ */


/**
 * DEFINES VARIABLES & CONSTANTS
 * Overview:
 *    PHPMYADMIN_VERSION   (string) - phpMyAdmin version string
 *    PHP_INT_VERSION      (int)    - eg: 30017 instead of 3.0.17 or
 *                                        40006 instead of 4.0.6RC3
 *    PMA_WINDOWS          (bool)   - mark if phpMyAdmin running on windows
 *                                    server
 *    MYSQL_INT_VERSION    (int)    - eg: 32339 instead of 3.23.39
 */
// phpMyAdmin release
if (!defined('PHPMYADMIN_VERSION')) {
    define('PHPMYADMIN_VERSION', '2.2.1-dev');
}

// php version
if (!defined('PHP_INT_VERSION')) {
    if (!ereg('([0-9]).([0-9]).([0-9])', phpversion(), $match)) {
        $result = ereg('([0-9]).([0-9])', phpversion(), $match);
    }
    if (isset($match) && !empty($match[1])) {
        if (!isset($match[2])) {
            $match[2] = 0;
        }
        if (!isset($match[3])) {
            $match[3] = 0;
        }
        define('PHP_INT_VERSION', (int)sprintf('%d%02d%02d', $match[1], $match[2], $match[3]));
        unset($match);
    } else {
        define('PHP_INT_VERSION', FALSE);
    }
}

// Whether the os php is running on is windows or not
if (!defined('PMA_WINDOWS')) {
    if (defined('PHP_OS') && eregi('win', PHP_OS)) {
        define('PMA_WINDOWS', TRUE);
    } else {
        define('PMA_WINDOWS', FALSE);
    }
}

// MySQL Version
if (!defined('MYSQL_MAJOR_VERSION') && isset($link)) {
    if (!empty($server)) {
        $result = mysql_query('SELECT VERSION() AS version');
        if ($result != FALSE && @mysql_num_rows($result) > 0) {
            $row   = mysql_fetch_array($result);
            $match = explode('.', $row['version']);
        } else {
            $result = @mysql_query('SHOW VARIABLES LIKE \'version\'');
            if ($result != FALSE && @mysql_num_rows($result) > 0){
                $row   = mysql_fetch_row($result);
                $match = explode('.', $row[1]);
            }
        }
    } // end server id is defined case

    if (!isset($match) || !isset($match[0])) {
        $match[0] = 3;
    }
    if (!isset($match[1])) {
        $match[1] = 21;
    }
    if (!isset($match[2])) {
        $match[2] = 0;
    }

    define('MYSQL_INT_VERSION', (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2])));
    unset($match);
}
?>
