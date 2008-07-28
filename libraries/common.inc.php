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
 * LABEL_variables_init
 *  - initialize some variables always needed
 * LABEL_parsing_config_file
 *  - parsing of the configuration file
 * LABEL_loading_language_file
 *  - loading language file
 * LABEL_theme_setup
 *  - setting up themes
 *
 * - load of MySQL extension (if necessary)
 * - loading of an authentication library
 * - db connection
 * - authentication work
 *
 * @version $Id$
 */

/**
 * For now, avoid warnings of E_STRICT mode
 * (this must be done before function definitions)
 */
if (defined('E_STRICT')) {
    $old_error_reporting = error_reporting(0);
    if ($old_error_reporting & E_STRICT) {
        error_reporting($old_error_reporting ^ E_STRICT);
    } else {
        error_reporting($old_error_reporting);
    }
    unset($old_error_reporting);
}

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
 * protect against older PHP versions' bug about GLOBALS overwrite
 * (no need to localize this message :))
 * but what if script.php?GLOBALS[admin]=1&GLOBALS[_REQUEST]=1 ???
 */
if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])
  || isset($_SERVER['GLOBALS']) || isset($_COOKIE['GLOBALS'])
  || isset($_ENV['GLOBALS'])) {
    die('GLOBALS overwrite attempt');
}

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
 * </code
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
// (get_magic_quotes_gpc() is deprecated in PHP 5.3, but compare with 5.2.99
// to be able to test with 5.3.0-dev)
if (function_exists('get_magic_quotes_gpc') && -1 == version_compare(PHP_VERSION, '5.2.99') && get_magic_quotes_gpc()) {
    PMA_arrayWalkRecursive($_GET, 'stripslashes', true);
    PMA_arrayWalkRecursive($_POST, 'stripslashes', true);
    PMA_arrayWalkRecursive($_COOKIE, 'stripslashes', true);
    PMA_arrayWalkRecursive($_REQUEST, 'stripslashes', true);
}

/**
 * clean cookies on new install or upgrade
 * when changing something with increment the cookie version
 */
$pma_cookie_version = 4;
if (isset($_COOKIE)
 && (! isset($_COOKIE['pmaCookieVer'])
  || $_COOKIE['pmaCookieVer'] < $pma_cookie_version)) {
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
 * include session handling after the globals, to prevent overwriting
 */
require_once './libraries/session.inc.php';

/**
 * init some variables LABEL_variables_init
 */

/**
 * holds errors
 * @global array $GLOBALS['PMA_errors']
 */
$GLOBALS['PMA_errors'] = array();

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
        /* to change the language on login screen or main page */
        'lang',
        /* Session ID */
        'phpMyAdmin',
        /* Cookie preferences */
        'pma_lang', 'pma_charset', 'pma_collation_connection',
        /* Possible login form */
        'pma_servername', 'pma_username', 'pma_password',
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
 * @global string $convcharset
 * @see select_lang.lib.php
 */
if (isset($_REQUEST['convcharset'])) {
    $convcharset = strip_tags($_REQUEST['convcharset']);
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
        . PMA_generate_common_url($_GET));
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
 * Includes the language file if it hasn't been included yet
 */
require './libraries/language.lib.php';


/**
 * check for errors occurred while loading configuration
 * this check is done here after loading language files to present errors in locale
 */
if ($_SESSION['PMA_Config']->error_config_file) {
    $GLOBALS['PMA_errors'][] = $strConfigFileError
        . '<br /><br />'
        . ($_SESSION['PMA_Config']->getSource() == './config.inc.php' ?
        '<a href="show_config_errors.php"'
        .' target="_blank">' . $_SESSION['PMA_Config']->getSource() . '</a>'
        :
        '<a href="' . $_SESSION['PMA_Config']->getSource() . '"'
        .' target="_blank">' . $_SESSION['PMA_Config']->getSource() . '</a>');
}
if ($_SESSION['PMA_Config']->error_config_default_file) {
    $GLOBALS['PMA_errors'][] = sprintf($strConfigDefaultFileError,
        $_SESSION['PMA_Config']->default_source);
}
if ($_SESSION['PMA_Config']->error_pma_uri) {
    $GLOBALS['PMA_errors'][] = sprintf($strPmaUriError);
}

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
            $GLOBALS['PMA_errors'][] = sprintf($strInvalidServerIndex, $server_index);
        }

        $each_server = array_merge($default_server, $each_server);

        // Don't use servers with no hostname
        if ($each_server['connect_type'] == 'tcp' && empty($each_server['host'])) {
            $GLOBALS['PMA_errors'][] = sprintf($strInvalidServerHostname, $server_index);
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

    if (! empty($cfg['Server'])) {

        /**
         * Loads the proper database interface for this server
         */
        require_once './libraries/database_interface.lib.php';

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
               PMA_auth_fails();
            }
            unset($allowDeny_forbidden); //Clean up after you!
        } // end if

        // is root allowed?
        if (!$cfg['Server']['AllowRoot'] && $cfg['Server']['user'] == 'root') {
            $allowDeny_forbidden = true;
            PMA_auth_fails();
            unset($allowDeny_forbidden); //Clean up after you!
        }

        $bkp_track_err = @ini_set('track_errors', 1);

        // Try to connect MySQL with the control user profile (will be used to
        // get the privileges list for the current user but the true user link
        // must be open after this one so it would be default one for all the
        // scripts)
        $controllink = false;
        if ($cfg['Server']['controluser'] != '') {
            $controllink = PMA_DBI_connect($cfg['Server']['controluser'],
                $cfg['Server']['controlpass'], true);
        }
        if (! $controllink) {
            $controllink = PMA_DBI_connect($cfg['Server']['user'],
                $cfg['Server']['password'], true);
        } // end if ... else

        // Pass #1 of DB-Config to read in master level DB-Config will go here
        // Robbat2 - May 11, 2002

        // Connects to the server (validates user's login)
        $userlink = PMA_DBI_connect($cfg['Server']['user'],
            $cfg['Server']['password'], false);

        // Pass #2 of DB-Config to read in user level DB-Config will go here
        // Robbat2 - May 11, 2002

        @ini_set('track_errors', $bkp_track_err);
        unset($bkp_track_err);

        /**
         * If we auto switched to utf-8 we need to reread messages here
         */
        if (defined('PMA_LANG_RELOAD')) {
            require './libraries/language.lib.php';
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
        require_once './libraries/List_Database.class.php';
        $PMA_List_Database = new PMA_List_Database($userlink, $controllink);

        /**
         * some resetting has to be done when switching servers
         */
        if (isset($_SESSION['userconf']['previous_server']) && $_SESSION['userconf']['previous_server'] != $GLOBALS['server']) {
            unset($_SESSION['userconf']['navi_limit_offset']);
        }
        $_SESSION['userconf']['previous_server'] = $GLOBALS['server'];

    } // end server connecting

    /**
     * Kanji encoding convert feature appended by Y.Kawada (2002/2/20)
     */
    if (@function_exists('mb_convert_encoding')
        && strpos(' ' . $lang, 'ja-')
        && file_exists('./libraries/kanji-encoding.lib.php')) {
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

    /**
     * check if profiling was requested and remember it
     * (note: when $cfg['ServerDefault'] = 0, constant is not defined)
     */

    if (PMA_profilingSupported() && isset($_REQUEST['profiling'])) {
        $_SESSION['profiling'] = true;
    } elseif (isset($_REQUEST['profiling_form'])) {
        // the checkbox was unchecked
        unset($_SESSION['profiling']);
    }

} // end if !defined('PMA_MINIMUM_COMMON')

// remove sensitive values from session
$_SESSION['PMA_Config']->set('blowfish_secret', '');
$_SESSION['PMA_Config']->set('Servers', '');
$_SESSION['PMA_Config']->set('default_server', '');

if (!empty($__redirect) && in_array($__redirect, $goto_whitelist)) {
    /**
     * include subform target page
     */
    require $__redirect;
    exit();
}
?>
