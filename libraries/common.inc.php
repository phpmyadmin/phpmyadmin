<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Misc stuff and REQUIRED by ALL the scripts.
 * MUST be included by every script
 *
 * Among other things, it contains the advanced authentication work.
 *
 * Order of sections for common.inc.php:
 *
 * the authentication libraries must be before the connection to db
 *
 * ... so the required order is:
 *
 * LABEL_variables_init
 *  - initialize some variables always needed
 * LABEL_parsing_config_file
 *  - parsing of the configuration file
 * LABEL_loading_language_file
 *  - loading language file
 * LABEL_setup_servers
 *  - check and setup configured servers
 * LABEL_theme_setup
 *  - setting up themes
 *
 * - load of MySQL extension (if necessary)
 * - loading of an authentication library
 * - db connection
 * - authentication work
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Minimum PHP version; can't call PMA_fatalError() which uses a
 * PHP 5 function, so cannot easily localize this message.
 */
if (version_compare(PHP_VERSION, '5.2.0', 'lt')) {
    die('PHP 5.2+ is required');
}

/**
  * Backward compatibility for PHP 5.2
  */
if (!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}

/**
 * the error handler
 */
require_once './libraries/Error_Handler.class.php';

/**
 * initialize the error handler
 */
$GLOBALS['error_handler'] = new PMA_Error_Handler();
$cfg['Error_Handler']['display'] = TRUE;

// at this point PMA_PHP_INT_VERSION is not yet defined
if (version_compare(phpversion(), '6', 'lt')) {
    /**
     * Avoid object cloning errors
     */
    @ini_set('zend.ze1_compatibility_mode', false);

    /**
     * Avoid problems with magic_quotes_runtime
     */
    @ini_set('magic_quotes_runtime', false);
}

/**
 * for verification in all procedural scripts under libraries
 */
define('PHPMYADMIN', true);

/**
 * core functions
 */
require_once './libraries/core.lib.php';

/**
 * Input sanitizing
 */
require_once './libraries/sanitizing.lib.php';

/**
 * the PMA_Theme class
 */
require_once './libraries/Theme.class.php';

/**
 * the PMA_Theme_Manager class
 */
require_once './libraries/Theme_Manager.class.php';

/**
 * the PMA_Config class
 */
require_once './libraries/Config.class.php';

/**
 * the relation lib, tracker needs it
 */
require_once './libraries/relation.lib.php';

/**
 * the PMA_Tracker class
 */
require_once './libraries/Tracker.class.php';

/**
 * the PMA_Table class
 */
require_once './libraries/Table.class.php';

if (!defined('PMA_MINIMUM_COMMON')) {
    /**
     * common functions
     */
    require_once './libraries/common.lib.php';

    /**
     * Java script escaping.
     */
    require_once './libraries/js_escape.lib.php';

    /**
     * Include URL/hidden inputs generating.
     */
    require_once './libraries/url_generating.lib.php';
}

/******************************************************************************/
/* start procedural code                       label_start_procedural         */

/**
 * protect against possible exploits - there is no need to have so much variables
 */
if (count($_REQUEST) > 1000) {
    die('possible exploit');
}

/**
 * Check for numeric keys
 * (if register_globals is on, numeric key can be found in $GLOBALS)
 */
foreach ($GLOBALS as $key => $dummy) {
    if (is_numeric($key)) {
        die('numeric key detected');
    }
}
unset($dummy);

/**
 * PATH_INFO could be compromised if set, so remove it from PHP_SELF
 * and provide a clean PHP_SELF here
 */
$PMA_PHP_SELF = PMA_getenv('PHP_SELF');
$_PATH_INFO = PMA_getenv('PATH_INFO');
if (! empty($_PATH_INFO) && ! empty($PMA_PHP_SELF)) {
    $path_info_pos = strrpos($PMA_PHP_SELF, $_PATH_INFO);
    if ($path_info_pos + strlen($_PATH_INFO) === strlen($PMA_PHP_SELF)) {
        $PMA_PHP_SELF = substr($PMA_PHP_SELF, 0, $path_info_pos);
    }
}
$PMA_PHP_SELF = htmlspecialchars($PMA_PHP_SELF);


/**
 * just to be sure there was no import (registering) before here
 * we empty the global space (but avoid unsetting $variables_list
 * and $key in the foreach(), we still need them!)
 */
$variables_whitelist = array (
    'GLOBALS',
    '_SERVER',
    '_GET',
    '_POST',
    '_REQUEST',
    '_FILES',
    '_ENV',
    '_COOKIE',
    '_SESSION',
    'error_handler',
    'PMA_PHP_SELF',
    'variables_whitelist',
    'key'
);

foreach (get_defined_vars() as $key => $value) {
    if (! in_array($key, $variables_whitelist)) {
        unset($$key);
    }
}
unset($key, $value, $variables_whitelist);


/**
 * Subforms - some functions need to be called by form, cause of the limited URL
 * length, but if this functions inside another form you cannot just open a new
 * form - so phpMyAdmin uses 'arrays' inside this form
 *
 * <code>
 * <form ...>
 * ... main form elments ...
 * <input type="hidden" name="subform[action1][id]" value="1" />
 * ... other subform data ...
 * <input type="submit" name="usesubform[action1]" value="do action1" />
 * ... other subforms ...
 * <input type="hidden" name="subform[actionX][id]" value="X" />
 * ... other subform data ...
 * <input type="submit" name="usesubform[actionX]" value="do actionX" />
 * ... main form elments ...
 * <input type="submit" name="main_action" value="submit form" />
 * </form>
 * </code>
 *
 * so we now check if a subform is submitted
 */
$__redirect = null;
if (isset($_POST['usesubform'])) {
    // if a subform is present and should be used
    // the rest of the form is deprecated
    $subform_id = key($_POST['usesubform']);
    $subform    = $_POST['subform'][$subform_id];
    $_POST      = $subform;
    $_REQUEST   = $subform;
    /**
     * some subforms need another page than the main form, so we will just
     * include this page at the end of this script - we use $__redirect to
     * track this
     */
    if (isset($_POST['redirect'])
      && $_POST['redirect'] != basename($PMA_PHP_SELF)) {
        $__redirect = $_POST['redirect'];
        unset($_POST['redirect']);
    }
    unset($subform_id, $subform);
} else {
    // Note: here we overwrite $_REQUEST so that it does not contain cookies,
    // because another application for the same domain could have set
    // a cookie (with a compatible path) that overrides a variable
    // we expect from GET or POST.
    // We'll refer to cookies explicitly with the $_COOKIE syntax.
    $_REQUEST = array_merge($_GET, $_POST);
}
// end check if a subform is submitted

// remove quotes added by php
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    PMA_arrayWalkRecursive($_GET, 'stripslashes', true);
    PMA_arrayWalkRecursive($_POST, 'stripslashes', true);
    PMA_arrayWalkRecursive($_COOKIE, 'stripslashes', true);
    PMA_arrayWalkRecursive($_REQUEST, 'stripslashes', true);
}

/**
 * clean cookies on upgrade
 * when changing something related to PMA cookies, increment the cookie version
 */
$pma_cookie_version = 4;
if (isset($_COOKIE)
 && (isset($_COOKIE['pmaCookieVer'])
  && $_COOKIE['pmaCookieVer'] < $pma_cookie_version)) {
    // delete all cookies
    foreach($_COOKIE as $cookie_name => $tmp) {
        PMA_removeCookie($cookie_name);
    }
    $_COOKIE = array();
    PMA_setCookie('pmaCookieVer', $pma_cookie_version);
}

/**
 * include deprecated grab_globals only if required
 */
if (empty($__redirect) && !defined('PMA_NO_VARIABLES_IMPORT')) {
    require './libraries/grab_globals.lib.php';
}

/**
 * check timezone setting
 * this could produce an E_STRICT - but only once,
 * if not done here it will produce E_STRICT on every date/time function
 *
 * @todo need to decide how we should handle this (without @)
 */
date_default_timezone_set(@date_default_timezone_get());

/**
 * include session handling after the globals, to prevent overwriting
 */
require_once './libraries/session.inc.php';

/**
 * init some variables LABEL_variables_init
 */

/**
 * holds parameters to be passed to next page
 * @global array $GLOBALS['url_params']
 */
$GLOBALS['url_params'] = array();

/**
 * the whitelist for $GLOBALS['goto']
 * @global array $goto_whitelist
 */
$goto_whitelist = array(
    //'browse_foreigners.php',
    //'calendar.php',
    //'changelog.php',
    //'chk_rel.php',
    'db_create.php',
    'db_datadict.php',
    'db_sql.php',
    'db_export.php',
    'db_importdocsql.php',
    'db_qbe.php',
    'db_structure.php',
    'db_import.php',
    'db_operations.php',
    'db_printview.php',
    'db_search.php',
    //'Documentation.html',
    //'error.php',
    'export.php',
    'import.php',
    //'index.php',
    //'navigation.php',
    //'license.php',
    'main.php',
    'pdf_pages.php',
    'pdf_schema.php',
    //'phpinfo.php',
    'querywindow.php',
    //'readme.php',
    'server_binlog.php',
    'server_collations.php',
    'server_databases.php',
    'server_engines.php',
    'server_export.php',
    'server_import.php',
    'server_privileges.php',
    'server_processlist.php',
    'server_sql.php',
    'server_status.php',
    'server_variables.php',
    'sql.php',
    'tbl_addfield.php',
    'tbl_alter.php',
    'tbl_change.php',
    'tbl_create.php',
    'tbl_import.php',
    'tbl_indexes.php',
    'tbl_move_copy.php',
    'tbl_printview.php',
    'tbl_sql.php',
    'tbl_export.php',
    'tbl_operations.php',
    'tbl_structure.php',
    'tbl_relation.php',
    'tbl_replace.php',
    'tbl_row_action.php',
    'tbl_select.php',
    //'themes.php',
    'transformation_overview.php',
    'transformation_wrapper.php',
    'translators.html',
    'user_password.php',
);

/**
 * check $__redirect against whitelist
 */
if (! PMA_checkPageValidity($__redirect, $goto_whitelist)) {
    $__redirect = null;
}

/**
 * holds page that should be displayed
 * @global string $GLOBALS['goto']
 */
$GLOBALS['goto'] = '';
// Security fix: disallow accessing serious server files via "?goto="
if (PMA_checkPageValidity($_REQUEST['goto'], $goto_whitelist)) {
    $GLOBALS['goto'] = $_REQUEST['goto'];
    $GLOBALS['url_params']['goto'] = $_REQUEST['goto'];
} else {
    unset($_REQUEST['goto'], $_GET['goto'], $_POST['goto'], $_COOKIE['goto']);
}

/**
 * returning page
 * @global string $GLOBALS['back']
 */
if (PMA_checkPageValidity($_REQUEST['back'], $goto_whitelist)) {
    $GLOBALS['back'] = $_REQUEST['back'];
} else {
    unset($_REQUEST['back'], $_GET['back'], $_POST['back'], $_COOKIE['back']);
}

/**
 * Check whether user supplied token is valid, if not remove any possibly
 * dangerous stuff from request.
 *
 * remember that some objects in the session with session_start and __wakeup()
 * could access this variables before we reach this point
 * f.e. PMA_Config: fontsize
 *
 * @todo variables should be handled by their respective owners (objects)
 * f.e. lang, server, convcharset, collation_connection in PMA_Config
 */
if (! PMA_isValid($_REQUEST['token']) || $_SESSION[' PMA_token '] != $_REQUEST['token']) {
    /**
     *  List of parameters which are allowed from unsafe source
     */
    $allow_list = array(
        /* needed for direct access, see FAQ 1.34
         * also, server needed for cookie login screen (multi-server)
         */
        'server', 'db', 'table', 'target',
        /* Session ID */
        'phpMyAdmin',
        /* Cookie preferences */
        'pma_lang', 'pma_charset', 'pma_collation_connection',
        /* Possible login form */
        'pma_servername', 'pma_username', 'pma_password',
        /* rajk - for playing blobstreamable media */
        'media_type', 'custom_type', 'bs_reference',
        /* rajk - for changing BLOB repository file MIME type */
        'bs_db', 'bs_table', 'bs_ref', 'bs_new_mime_type'
    );
    /**
     * Require cleanup functions
     */
    require_once './libraries/cleanup.lib.php';
    /**
     * Do actual cleanup
     */
    PMA_remove_request_vars($allow_list);

}


/**
 * @global string $GLOBALS['convcharset']
 * @see select_lang.lib.php
 */
if (isset($_REQUEST['convcharset'])) {
    $GLOBALS['convcharset'] = strip_tags($_REQUEST['convcharset']);
}

/**
 * current selected database
 * @global string $GLOBALS['db']
 */
$GLOBALS['db'] = '';
if (PMA_isValid($_REQUEST['db'])) {
    // can we strip tags from this?
    // only \ and / is not allowed in db names for MySQL
    $GLOBALS['db'] = $_REQUEST['db'];
    $GLOBALS['url_params']['db'] = $GLOBALS['db'];
}

/**
 * current selected table
 * @global string $GLOBALS['table']
 */
$GLOBALS['table'] = '';
if (PMA_isValid($_REQUEST['table'])) {
    // can we strip tags from this?
    // only \ and / is not allowed in table names for MySQL
    $GLOBALS['table'] = $_REQUEST['table'];
    $GLOBALS['url_params']['table'] = $GLOBALS['table'];
}

/**
 * SQL query to be executed
 * @global string $GLOBALS['sql_query']
 */
$GLOBALS['sql_query'] = '';
if (PMA_isValid($_REQUEST['sql_query'])) {
    $GLOBALS['sql_query'] = $_REQUEST['sql_query'];
}

/**
 * avoid problems in phpmyadmin.css.php in some cases
 * @global string $js_frame
 */
$_REQUEST['js_frame'] = PMA_ifSetOr($_REQUEST['js_frame'], '');

//$_REQUEST['set_theme'] // checked later in this file LABEL_theme_setup
//$_REQUEST['server']; // checked later in this file
//$_REQUEST['lang'];   // checked by LABEL_loading_language_file


/**
 * holds name of JavaScript files to be included in HTML header
 * @global array $js_include
 */
$GLOBALS['js_include'] = array();

/**
 * holds locale messages required by JavaScript function
 * @global array $js_messages
 */
$GLOBALS['js_messages'] = array();

/**
 * JavaScript events that will be registered
 * @global array $js_events
 */
$GLOBALS['js_events'] = array();

/**
 * footnotes to be displayed ot the page bottom
 * @global array $footnotes
 */
$GLOBALS['footnotes'] = array();

/******************************************************************************/
/* parsing configuration file                         LABEL_parsing_config_file      */

/**
 * We really need this one!
 */
if (! function_exists('preg_replace')) {
    PMA_fatalError('strCantLoad', 'pcre');
}

/**
 * @global PMA_Config $_SESSION['PMA_Config']
 * force reading of config file, because we removed sensitive values
 * in the previous iteration
 */
$_SESSION['PMA_Config'] = new PMA_Config('./config.inc.php');

if (!defined('PMA_MINIMUM_COMMON')) {
    $_SESSION['PMA_Config']->checkPmaAbsoluteUri();
}

/**
 * BC - enable backward compatibility
 * exports all configuration settings into $GLOBALS ($GLOBALS['cfg'])
 */
$_SESSION['PMA_Config']->enableBc();


/**
 * check HTTPS connection
 */
if ($_SESSION['PMA_Config']->get('ForceSSL')
  && !$_SESSION['PMA_Config']->get('is_https')) {
    PMA_sendHeaderLocation(
        preg_replace('/^http/', 'https',
            $_SESSION['PMA_Config']->get('PmaAbsoluteUri'))
        . PMA_generate_common_url($_GET, 'text'));
    // delete the current session, otherwise we get problems (see bug #2397877)
    PMA_removeCookie($GLOBALS['session_name']);
    exit;
}


/******************************************************************************/
/* loading language file                       LABEL_loading_language_file    */

/**
 * Added messages while developing:
 */
if (file_exists('./lang/added_messages.php')) {
    include './lang/added_messages.php';
}

/**
 * lang detection is done here
 */
require_once './libraries/select_lang.lib.php';

/**
 * check for errors occurred while loading configuration
 * this check is done here after loading language files to present errors in locale
 */
if ($_SESSION['PMA_Config']->error_config_file) {
    $error = $strConfigFileError
        . '<br /><br />'
        . ($_SESSION['PMA_Config']->getSource() == './config.inc.php' ?
        '<a href="show_config_errors.php"'
        .' target="_blank">' . $_SESSION['PMA_Config']->getSource() . '</a>'
        :
        '<a href="' . $_SESSION['PMA_Config']->getSource() . '"'
        .' target="_blank">' . $_SESSION['PMA_Config']->getSource() . '</a>');
    trigger_error($error, E_USER_ERROR);
}
if ($_SESSION['PMA_Config']->error_config_default_file) {
    $error = sprintf($strConfigDefaultFileError,
        $_SESSION['PMA_Config']->default_source);
    trigger_error($error, E_USER_ERROR);
}
if ($_SESSION['PMA_Config']->error_pma_uri) {
    trigger_error($strPmaUriError, E_USER_ERROR);
}


/******************************************************************************/
/* setup servers                                       LABEL_setup_servers    */

/**
 * current server
 * @global integer $GLOBALS['server']
 */
$GLOBALS['server'] = 0;

/**
 * Servers array fixups.
 * $default_server comes from PMA_Config::enableBc()
 * @todo merge into PMA_Config
 */
// Do we have some server?
if (!isset($cfg['Servers']) || count($cfg['Servers']) == 0) {
    // No server => create one with defaults
    $cfg['Servers'] = array(1 => $default_server);
} else {
    // We have server(s) => apply default configuration
    $new_servers = array();

    foreach ($cfg['Servers'] as $server_index => $each_server) {

        // Detect wrong configuration
        if (!is_int($server_index) || $server_index < 1) {
            trigger_error(sprintf($strInvalidServerIndex, $server_index), E_USER_ERROR);
        }

        $each_server = array_merge($default_server, $each_server);

        // Don't use servers with no hostname
        if ($each_server['connect_type'] == 'tcp' && empty($each_server['host'])) {
            trigger_error(sprintf($strInvalidServerHostname, $server_index), E_USER_ERROR);
        }

        // Final solution to bug #582890
        // If we are using a socket connection
        // and there is nothing in the verbose server name
        // or the host field, then generate a name for the server
        // in the form of "Server 2", localized of course!
        if ($each_server['connect_type'] == 'socket' && empty($each_server['host']) && empty($each_server['verbose'])) {
            $each_server['verbose'] = $GLOBALS['strServer'] . $server_index;
        }

        $new_servers[$server_index] = $each_server;
    }
    $cfg['Servers'] = $new_servers;
    unset($new_servers, $server_index, $each_server);
}

// Cleanup
unset($default_server);


/******************************************************************************/
/* setup themes                                          LABEL_theme_setup    */

if (isset($_REQUEST['custom_color_reset'])) {
    unset($_SESSION['tmp_user_values']['custom_color']);
    unset($_SESSION['tmp_user_values']['custom_color_rgb']);
} elseif (isset($_REQUEST['custom_color'])) {
    $_SESSION['tmp_user_values']['custom_color'] = $_REQUEST['custom_color'];
    $_SESSION['tmp_user_values']['custom_color_rgb'] = $_REQUEST['custom_color_rgb'];
}
/**
 * @global PMA_Theme_Manager $_SESSION['PMA_Theme_Manager']
 */
if (! isset($_SESSION['PMA_Theme_Manager'])) {
    $_SESSION['PMA_Theme_Manager'] = new PMA_Theme_Manager;
} else {
    /**
     * @todo move all __wakeup() functionality into session.inc.php
     */
    $_SESSION['PMA_Theme_Manager']->checkConfig();
}

// for the theme per server feature
if (isset($_REQUEST['server']) && !isset($_REQUEST['set_theme'])) {
    $GLOBALS['server'] = $_REQUEST['server'];
    $tmp = $_SESSION['PMA_Theme_Manager']->getThemeCookie();
    if (empty($tmp)) {
        $tmp = $_SESSION['PMA_Theme_Manager']->theme_default;
    }
    $_SESSION['PMA_Theme_Manager']->setActiveTheme($tmp);
    unset($tmp);
}
/**
 * @todo move into PMA_Theme_Manager::__wakeup()
 */
if (isset($_REQUEST['set_theme'])) {
    // if user selected a theme
    $_SESSION['PMA_Theme_Manager']->setActiveTheme($_REQUEST['set_theme']);
}

/**
 * the theme object
 * @global PMA_Theme $_SESSION['PMA_Theme']
 */
$_SESSION['PMA_Theme'] = $_SESSION['PMA_Theme_Manager']->theme;

// BC
/**
 * the active theme
 * @global string $GLOBALS['theme']
 */
$GLOBALS['theme']           = $_SESSION['PMA_Theme']->getName();
/**
 * the theme path
 * @global string $GLOBALS['pmaThemePath']
 */
$GLOBALS['pmaThemePath']    = $_SESSION['PMA_Theme']->getPath();
/**
 * the theme image path
 * @global string $GLOBALS['pmaThemeImage']
 */
$GLOBALS['pmaThemeImage']   = $_SESSION['PMA_Theme']->getImgPath();

/**
 * load layout file if exists
 */
if (@file_exists($_SESSION['PMA_Theme']->getLayoutFile())) {
    include $_SESSION['PMA_Theme']->getLayoutFile();
    /**
     * @todo remove if all themes are update use Navi instead of Left as frame name
     */
    if (! isset($GLOBALS['cfg']['NaviWidth'])
     && isset($GLOBALS['cfg']['LeftWidth'])) {
        $GLOBALS['cfg']['NaviWidth'] = $GLOBALS['cfg']['LeftWidth'];
    }
}

if (! defined('PMA_MINIMUM_COMMON')) {
    /**
     * Character set conversion.
     */
    require_once './libraries/charset_conversion.lib.php';

    /**
     * String handling
     */
    require_once './libraries/string.lib.php';

    /**
     * Lookup server by name
     * by Arnold - Helder Hosting
     * (see FAQ 4.8)
     */
    if (! empty($_REQUEST['server']) && is_string($_REQUEST['server'])
     && ! is_numeric($_REQUEST['server'])) {
        foreach ($cfg['Servers'] as $i => $server) {
            if ($server['host'] == $_REQUEST['server']) {
                $_REQUEST['server'] = $i;
                break;
            }
        }
        if (is_string($_REQUEST['server'])) {
            unset($_REQUEST['server']);
        }
        unset($i);
    }

    /**
     * If no server is selected, make sure that $cfg['Server'] is empty (so
     * that nothing will work), and skip server authentication.
     * We do NOT exit here, but continue on without logging into any server.
     * This way, the welcome page will still come up (with no server info) and
     * present a choice of servers in the case that there are multiple servers
     * and '$cfg['ServerDefault'] = 0' is set.
     */

    if (isset($_REQUEST['server']) && (is_string($_REQUEST['server']) || is_numeric($_REQUEST['server'])) && ! empty($_REQUEST['server']) && ! empty($cfg['Servers'][$_REQUEST['server']])) {
        $GLOBALS['server'] = $_REQUEST['server'];
        $cfg['Server'] = $cfg['Servers'][$GLOBALS['server']];
    } else {
        if (!empty($cfg['Servers'][$cfg['ServerDefault']])) {
            $GLOBALS['server'] = $cfg['ServerDefault'];
            $cfg['Server'] = $cfg['Servers'][$GLOBALS['server']];
        } else {
            $GLOBALS['server'] = 0;
            $cfg['Server'] = array();
        }
    }
    $GLOBALS['url_params']['server'] = $GLOBALS['server'];

    /**
     * Kanji encoding convert feature appended by Y.Kawada (2002/2/20)
     */
    if (function_exists('mb_convert_encoding')
     && strpos($lang, 'ja-') !== false) {
        require_once './libraries/kanji-encoding.lib.php';
        /**
         * enable multibyte string support
         */
        define('PMA_MULTIBYTE_ENCODING', 1);
    } // end if

    /**
     * save some settings in cookies
     * @todo should be done in PMA_Config
     */
    PMA_setCookie('pma_lang', $GLOBALS['lang']);
    PMA_setCookie('pma_charset', $GLOBALS['convcharset']);
    PMA_setCookie('pma_collation_connection', $GLOBALS['collation_connection']);

    $_SESSION['PMA_Theme_Manager']->setThemeCookie();

    if (! empty($cfg['Server'])) {

        /**
         * Loads the proper database interface for this server
         */
        require_once './libraries/database_interface.lib.php';

        require_once './libraries/logging.lib.php';

        // Gets the authentication library that fits the $cfg['Server'] settings
        // and run authentication

        // to allow HTTP or http
        $cfg['Server']['auth_type'] = strtolower($cfg['Server']['auth_type']);
        if (! file_exists('./libraries/auth/' . $cfg['Server']['auth_type'] . '.auth.lib.php')) {
            PMA_fatalError($strInvalidAuthMethod . ' ' . $cfg['Server']['auth_type']);
        }
        /**
         * the required auth type plugin
         */
        require_once './libraries/auth/' . $cfg['Server']['auth_type'] . '.auth.lib.php';

        if (!PMA_auth_check()) {
            PMA_auth();
        } else {
            PMA_auth_set_user();
        }

        // Check IP-based Allow/Deny rules as soon as possible to reject the
        // user
        // Based on mod_access in Apache:
        // http://cvs.apache.org/viewcvs.cgi/httpd-2.0/modules/aaa/mod_access.c?rev=1.37&content-type=text/vnd.viewcvs-markup
        // Look at: "static int check_dir_access(request_rec *r)"
        // Robbat2 - May 10, 2002
        if (isset($cfg['Server']['AllowDeny'])
          && isset($cfg['Server']['AllowDeny']['order'])) {

            /**
             * ip based access library
             */
            require_once './libraries/ip_allow_deny.lib.php';

            $allowDeny_forbidden         = false; // default
            if ($cfg['Server']['AllowDeny']['order'] == 'allow,deny') {
                $allowDeny_forbidden     = true;
                if (PMA_allowDeny('allow')) {
                    $allowDeny_forbidden = false;
                }
                if (PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = true;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] == 'deny,allow') {
                if (PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = true;
                }
                if (PMA_allowDeny('allow')) {
                    $allowDeny_forbidden = false;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] == 'explicit') {
                if (PMA_allowDeny('allow')
                  && !PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = false;
                } else {
                    $allowDeny_forbidden = true;
                }
            } // end if ... elseif ... elseif

            // Ejects the user if banished
            if ($allowDeny_forbidden) {
                PMA_log_user($cfg['Server']['user'], 'allow-denied');
                PMA_auth_fails();
            }
            unset($allowDeny_forbidden); //Clean up after you!
        } // end if

        // is root allowed?
        if (!$cfg['Server']['AllowRoot'] && $cfg['Server']['user'] == 'root') {
            $allowDeny_forbidden = true;
            PMA_log_user($cfg['Server']['user'], 'root-denied');
            PMA_auth_fails();
            unset($allowDeny_forbidden); //Clean up after you!
        }

        // is a login without password allowed?
        if (!$cfg['Server']['AllowNoPassword'] && $cfg['Server']['password'] == '') {
            $login_without_password_is_forbidden = true;
            PMA_log_user($cfg['Server']['user'], 'empty-denied');
            PMA_auth_fails();
            unset($login_without_password_is_forbidden); //Clean up after you!
        }

        // Try to connect MySQL with the control user profile (will be used to
        // get the privileges list for the current user but the true user link
        // must be open after this one so it would be default one for all the
        // scripts)
        $controllink = false;
        if ($cfg['Server']['controluser'] != '') {
            $controllink = PMA_DBI_connect($cfg['Server']['controluser'],
                $cfg['Server']['controlpass'], true);
        }

        // Connects to the server (validates user's login)
        $userlink = PMA_DBI_connect($cfg['Server']['user'],
            $cfg['Server']['password'], false);

        if (! $controllink) {
            $controllink = $userlink;
        }

        /* Log success */
        PMA_log_user($cfg['Server']['user']);

        /**
         * with phpMyAdmin 3 we support MySQL >=5
         * but only production releases:
         *  - > 5.0.15
         */
        if (PMA_MYSQL_INT_VERSION < 50015) {
            PMA_fatalError('strUpgrade', array('MySQL', '5.0.15'));
        }

        /**
         * SQL Parser code
         */
        require_once './libraries/sqlparser.lib.php';

        /**
         * SQL Validator interface code
         */
        require_once './libraries/sqlvalidator.lib.php';

        /**
         * the PMA_List_Database class
         */
        require_once './libraries/PMA.php';
        $pma = new PMA;
        $pma->userlink = $userlink;
        $pma->controllink = $controllink;

        /**
         * some resetting has to be done when switching servers
         */
        if (isset($_SESSION['tmp_user_values']['previous_server']) && $_SESSION['tmp_user_values']['previous_server'] != $GLOBALS['server']) {
            unset($_SESSION['tmp_user_values']['navi_limit_offset']);
        }
        $_SESSION['tmp_user_values']['previous_server'] = $GLOBALS['server'];

    } // end server connecting

    /**
     * check if profiling was requested and remember it
     * (note: when $cfg['ServerDefault'] = 0, constant is not defined)
     */
    if (isset($_REQUEST['profiling']) && PMA_profilingSupported()) {
        $_SESSION['profiling'] = true;
    } elseif (isset($_REQUEST['profiling_form'])) {
        // the checkbox was unchecked
        unset($_SESSION['profiling']);
    }

    // rajk - library file for blobstreaming
    require_once './libraries/blobstreaming.lib.php';

    // rajk - checks for blobstreaming plugins and databases that support
    // blobstreaming (by having the necessary tables for blobstreaming)
    if (checkBLOBStreamingPlugins()) {
        checkBLOBStreamableDatabases();
    }
} // end if !defined('PMA_MINIMUM_COMMON')

// remove sensitive values from session
$_SESSION['PMA_Config']->set('blowfish_secret', '');
$_SESSION['PMA_Config']->set('Servers', '');
$_SESSION['PMA_Config']->set('default_server', '');

/* Tell tracker that it can actually work */
PMA_Tracker::enable();

if (!empty($__redirect) && in_array($__redirect, $goto_whitelist)) {
    /**
     * include subform target page
     */
    require $__redirect;
    exit();
}
?>
