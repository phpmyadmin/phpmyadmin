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
 *    USR_OS               (string) - the plateform (os) of the user
 *    USR_BROWSER_AGENT    (string) - the browser of the user
 *    USR_BROWSER_VER      (double) - the version of this browser
 */
// phpMyAdmin release
if (!defined('PHPMYADMIN_VERSION')) {
    define('PHPMYADMIN_VERSION', '2.2.1-rc2');
}

// php version
if (!defined('PHP_INT_VERSION')) {
    if (!ereg('([0-9]{1,2}).([0-9]{1,2}).([0-9]{1,2})', phpversion(), $match)) {
        $result = ereg('([0-9]{1,2}).([0-9]{1,2})', phpversion(), $match);
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
        define('PHP_INT_VERSION', 0);
    }
}

// Whether the os php is running on is windows or not
if (!defined('PMA_WINDOWS')) {
    if (defined('PHP_OS') && eregi('win', PHP_OS)) {
        define('PMA_WINDOWS', 1);
    } else {
        define('PMA_WINDOWS', 0);
    }
}

// MySQL Version
if (!defined('MYSQL_MAJOR_VERSION') && isset($userlink)) {
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


// Determines platform (OS), browser and version of the user
// Based on a phpBuilder article:
//   see http://www.phpbuilder.net/columns/tim20000821.php3
if (!defined('USR_OS')) {
    if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
        $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
    }
    // 1. Platform
    if (strstr($HTTP_USER_AGENT, 'Win')) {
        define('USR_OS', 'Win');
    } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
        define('USR_OS', 'Mac');
    } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
        define('USR_OS', 'Linux');
    } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
        define('USR_OS', 'Unix');
    } else {
        define('USR_OS', 'Other');
    }
    // 2. browser and version
    if (ereg('MSIE ([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('USR_BROWSER_VER', $log_version[1]);
        define('USR_BROWSER_AGENT', 'IE');
    } else if (ereg('Opera(/| )([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('USR_BROWSER_VER', $log_version[2]);
        define('USR_BROWSER_AGENT', 'OPERA');
    } else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('USR_BROWSER_VER', $log_version[1]);
        define('USR_BROWSER_AGENT', 'MOZILLA');
    } else if (ereg('Konqueror/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('USR_BROWSER_VER', $log_version[1]);
        define('USR_BROWSER_AGENT', 'KONQUEROR');
    } else {
        define('USR_BROWSER_VER', 0);
        define('USR_BROWSER_AGENT', 'OTHER');
    }
}
?>
