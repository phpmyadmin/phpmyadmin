<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * DEFINES VARIABLES & CONSTANTS
 * Overview:
 *    PMA_VERSION              (string) - phpMyAdmin version string
 *    PMA_PHP_INT_VERSION      (int)    - eg: 30017 instead of 3.0.17 or
 *                                        40006 instead of 4.0.6RC3
 *    PMA_IS_WINDOWS           (bool)   - mark if phpMyAdmin running on windows
 *    PMA_IS_IIS               (bool)   - true is phpMyAdmin is running 
 *                                        on Microsoft IIS server
 *    PMA_IS_GD2               (bool)   - true if GD2 is present
 *    PMA_USR_OS               (string) - the plateform (os) of the user
 *    PMA_USR_BROWSER_AGENT    (string) - the browser of the user
 *    PMA_USR_BROWSER_VER      (double) - the version of this browser
 */
// phpMyAdmin release
if (!defined('PMA_VERSION')) {
    define('PMA_VERSION', '2.6.0-dev');
}

// php version
if (!defined('PMA_PHP_INT_VERSION')) {
    if (!preg_match('@([0-9]{1,2}).([0-9]{1,2}).([0-9]{1,2})@', phpversion(), $match)) {
        $result = preg_match('@([0-9]{1,2}).([0-9]{1,2})@', phpversion(), $match);
    }
    if (isset($match) && !empty($match[1])) {
        if (!isset($match[2])) {
            $match[2] = 0;
        }
        if (!isset($match[3])) {
            $match[3] = 0;
        }
        define('PMA_PHP_INT_VERSION', (int)sprintf('%d%02d%02d', $match[1], $match[2], $match[3]));
        unset($match);
    } else {
        define('PMA_PHP_INT_VERSION', 0);
    }
    define('PMA_PHP_STR_VERSION', phpversion());
}

// Whether the os php is running on is windows or not
if (!defined('PMA_IS_WINDOWS')) {
    if (defined('PHP_OS') && stristr(PHP_OS, 'win')) {
        define('PMA_IS_WINDOWS', 1);
    } else {
        define('PMA_IS_WINDOWS', 0);
    }
}

// Whether the Web server php is running on is IIS 
if (!defined('PMA_IS_IIS')) {
    if (isset($_SERVER['SERVER_SOFTWARE']) 
    && stristr($_SERVER['SERVER_SOFTWARE'], 'Microsoft/IIS')) {
        define('PMA_IS_IIS', 1);
    } else {
        define('PMA_IS_IIS', 0);
    }
}

function PMA_dl($module) {
    if (!isset($GLOBALS['PMA_dl_allowed'])) {
        if (!@ini_get('safe_mode') && @ini_get('enable_dl') && @function_exists('dl')) {
            ob_start();
            phpinfo(INFO_GENERAL); /* Only general info */
            $a = strip_tags(ob_get_contents());
            ob_end_clean();
            /* Get GD version string from phpinfo output */
            if (preg_match('@Thread Safety[[:space:]]*enabled@', $a)) {
                if (preg_match('@Server API[[:space:]]*\(CGI\|CLI\)@', $a)) {
                    $GLOBALS['PMA_dl_allowed'] = TRUE;
                } else {
                    $GLOBALS['PMA_dl_allowed'] = FALSE;
                }
            } else {
                $GLOBALS['PMA_dl_allowed'] = TRUE;
            }
        } else {
            $GLOBALS['PMA_dl_allowed'] = FALSE;
        }
    }
    if (PMA_IS_WINDOWS) {
        $suffix = '.dll';
    } else {
        $suffix = '.so';
    }
    if ($GLOBALS['PMA_dl_allowed']) {
        return @dl($module . $suffix);
    } else {
        return FALSE;
    }
}

// Whether GD2 is present
if (!defined('PMA_IS_GD2')) {
    if ($cfg['GD2Available'] == 'yes') {
        define('PMA_IS_GD2', 1);
    } elseif ($cfg['GD2Available'] == 'no') {
        define('PMA_IS_GD2', 0);
    } else {
        if (!@extension_loaded('gd')) {
            PMA_dl('gd');
        }
        if (!@function_exists('imagecreatetruecolor')) {
            define('PMA_IS_GD2', 0);
        } else {
            if (@function_exists('gd_info')) {
                $gd_nfo = gd_info();
                if (strstr($gd_nfo["GD Version"], '2.')) {
                    define('PMA_IS_GD2', 1);
                } else {
                    define('PMA_IS_GD2', 0);
                }
            } else {
                /* We must do hard way... */
                ob_start();
                phpinfo(INFO_MODULES); /* Only modules */
                $a = strip_tags(ob_get_contents());
                ob_end_clean();
                /* Get GD version string from phpinfo output */
                if (preg_match('@GD Version[[:space:]]*\(.*\)@', $a, $v)) {
                    if (strstr($v, '2.')) {
                        define('PMA_IS_GD2', 1);
                    } else {
                        define('PMA_IS_GD2', 0);
                    }
                } else {
                    define('PMA_IS_GD2', 0);
                }
            }
        }
    }
}

// Determines platform (OS), browser and version of the user
// Based on a phpBuilder article:
//   see http://www.phpbuilder.net/columns/tim20000821.php
if (!defined('PMA_USR_OS')) {
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
    } else if (!isset($HTTP_USER_AGENT)) {
        $HTTP_USER_AGENT = '';
    }

    // 1. Platform
    if (strstr($HTTP_USER_AGENT, 'Win')) {
        define('PMA_USR_OS', 'Win');
    } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
        define('PMA_USR_OS', 'Mac');
    } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
        define('PMA_USR_OS', 'Linux');
    } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
        define('PMA_USR_OS', 'Unix');
    } else if (strstr($HTTP_USER_AGENT, 'OS/2')) {
        define('PMA_USR_OS', 'OS/2');
    } else {
        define('PMA_USR_OS', 'Other');
    }

    // 2. browser and version
    // (must check everything else before Mozilla)

    if (preg_match('@Opera(/| )([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[2]);
        define('PMA_USR_BROWSER_AGENT', 'OPERA');
    } else if (preg_match('@MSIE ([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'IE');
    } else if (preg_match('@OmniWeb/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
    //} else if (ereg('Konqueror/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
    // Konqueror 2.2.2 says Konqueror/2.2.2
    // Konqueror 3.0.3 says Konqueror/3
    } else if (preg_match('@(Konqueror/)(.*)(;)@', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[2]);
        define('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
    } else if (preg_match('@Mozilla/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)
               && preg_match('@Safari/([0-9]*)@', $HTTP_USER_AGENT, $log_version2)) {
        define('PMA_USR_BROWSER_VER', $log_version[1] . '.' . $log_version2[1]);
        define('PMA_USR_BROWSER_AGENT', 'SAFARI');
    } else if (preg_match('@Mozilla/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
    } else {
        define('PMA_USR_BROWSER_VER', 0);
        define('PMA_USR_BROWSER_AGENT', 'OTHER');
    }
}

?>
