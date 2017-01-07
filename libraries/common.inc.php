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
 * @package PhpMyAdmin
 */
use PMA\libraries\Config;
use PMA\libraries\DatabaseInterface;
use PMA\libraries\ErrorHandler;
use PMA\libraries\Message;
use PMA\libraries\plugins\AuthenticationPlugin;
use PMA\libraries\DbList;
use PMA\libraries\ThemeManager;
use PMA\libraries\Tracker;
use PMA\libraries\Response;
use PMA\libraries\TypesMySQL;
use PMA\libraries\Util;
use PMA\libraries\LanguageManager;

/**
 * block attempts to directly run this script
 */
if (getcwd() == dirname(__FILE__)) {
    die('Attack stopped');
}

/**
 * Minimum PHP version; can't call PMA_fatalError() which uses a
 * PHP 5 function, so cannot easily localize this message.
 */
if (version_compare(PHP_VERSION, '5.5.0', 'lt')) {
    die(
        'PHP 5.5+ is required. <br /> Currently installed version is: '
        . phpversion()
    );
}

/**
 * for verification in all procedural scripts under libraries
 */
define('PHPMYADMIN', true);

/**
 * Load vendor configuration.
 */
require_once './libraries/vendor_config.php';

/**
 * Load gettext functions.
 */
require_once GETTEXT_INC;

/**
 * Activate autoloader
 */
require_once './libraries/autoloader.php';

/**
 * initialize the error handler
 */
$GLOBALS['error_handler'] = new ErrorHandler();

/**
 * core functions
 */
require './libraries/core.lib.php';

/**
 * Input sanitizing
 */
require './libraries/sanitizing.lib.php';

/**
 * Warning about missing PHP extensions.
 */
PMA_checkExtensions();

/**
 * Set utf-8 encoding for PHP
 */
ini_set('default_charset', 'utf-8');
mb_internal_encoding('utf-8');

/**
 * Set precision to sane value, with higher values
 * things behave slightly unexpectedly, for example
 * round(1.2, 2) returns 1.199999999999999956.
 */
ini_set('precision', 14);

/**
 * the relation lib, tracker needs it
 */
require './libraries/relation.lib.php';

if (! defined('PMA_MINIMUM_COMMON') || defined('PMA_SETUP')) {
    /**
     * JavaScript escaping.
     */
    include_once './libraries/js_escape.lib.php';

    /**
     * Include URL/hidden inputs generating.
     */
    include_once './libraries/url_generating.lib.php';
}

/******************************************************************************/
/* start procedural code                       label_start_procedural         */

PMA_cleanupPathInfo();

/**
 * just to be sure there was no import (registering) before here
 * we empty the global space (but avoid unsetting $variables_list
 * and $key in the foreach (), we still need them!)
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
    'key',
    /* gettext globals */
    'text_domains', 'default_domain', 'LC_CATEGORIES', 'EMULATEGETTEXT', 'CURRENTLOCALE',
);

foreach (get_defined_vars() as $key => $value) {
    if (! in_array($key, $variables_whitelist)) {
        unset($$key);
    }
}
unset($key, $value, $variables_whitelist);

/**
 * @global boolean $GLOBALS['is_ajax_request']
 * @todo should this be moved to the variables init section above?
 *
 * Check if the current request is an AJAX request, and set is_ajax_request
 * accordingly.  Suppress headers, footers and unnecessary output if set to
 * true
 */
if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    $GLOBALS['is_ajax_request'] = true;
} else {
    $GLOBALS['is_ajax_request'] = false;
}


/**
 * Subforms - some functions need to be called by form, cause of the limited URL
 * length, but if this functions inside another form you cannot just open a new
 * form - so phpMyAdmin uses 'arrays' inside this form
 *
 * <code>
 * <form ...>
 * ... main form elements ...
 * <input type="hidden" name="subform[action1][id]" value="1" />
 * ... other subform data ...
 * <input type="submit" name="usesubform[action1]" value="do action1" />
 * ... other subforms ...
 * <input type="hidden" name="subform[actionX][id]" value="X" />
 * ... other subform data ...
 * <input type="submit" name="usesubform[actionX]" value="do actionX" />
 * ... main form elements ...
 * <input type="submit" name="main_action" value="submit form" />
 * </form>
 * </code>
 *
 * so we now check if a subform is submitted
 */
$__redirect = null;
if (isset($_POST['usesubform']) && ! defined('PMA_MINIMUM_COMMON')) {
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
        && $_POST['redirect'] != basename($PMA_PHP_SELF)
    ) {
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

/**
 * check timezone setting
 * this could produce an E_WARNING - but only once,
 * if not done here it will produce E_WARNING on every date/time function
 */
date_default_timezone_set(@date_default_timezone_get());

/******************************************************************************/
/* parsing configuration file                  LABEL_parsing_config_file      */

/**
 * @global Config $GLOBALS['PMA_Config']
 * force reading of config file, because we removed sensitive values
 * in the previous iteration
 */
$GLOBALS['PMA_Config'] = new Config(CONFIG_FILE);

/**
 * BC - enable backward compatibility
 * exports all configuration settings into $GLOBALS ($GLOBALS['cfg'])
 */
$GLOBALS['PMA_Config']->enableBc();

/**
 * clean cookies on upgrade
 * when changing something related to PMA cookies, increment the cookie version
 */
$pma_cookie_version = 5;
if (isset($_COOKIE)) {
    if (! isset($_COOKIE['pmaCookieVer'])
        || $_COOKIE['pmaCookieVer'] != $pma_cookie_version
    ) {
        // delete all cookies
        foreach ($_COOKIE as $cookie_name => $tmp) {
            $GLOBALS['PMA_Config']->removeCookie($cookie_name);
        }
        $_COOKIE = array();
        $GLOBALS['PMA_Config']->setCookie('pmaCookieVer', $pma_cookie_version);
    }
}

/**
 * include session handling after the globals, to prevent overwriting
 */
require './libraries/session.inc.php';

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
    'db_datadict.php',
    'db_sql.php',
    'db_events.php',
    'db_export.php',
    'db_importdocsql.php',
    'db_qbe.php',
    'db_structure.php',
    'db_import.php',
    'db_operations.php',
    'db_search.php',
    'db_routines.php',
    'export.php',
    'import.php',
    'index.php',
    'pdf_pages.php',
    'pdf_schema.php',
    'server_binlog.php',
    'server_collations.php',
    'server_databases.php',
    'server_engines.php',
    'server_export.php',
    'server_import.php',
    'server_privileges.php',
    'server_sql.php',
    'server_status.php',
    'server_status_advisor.php',
    'server_status_monitor.php',
    'server_status_queries.php',
    'server_status_variables.php',
    'server_variables.php',
    'sql.php',
    'tbl_addfield.php',
    'tbl_change.php',
    'tbl_create.php',
    'tbl_import.php',
    'tbl_indexes.php',
    'tbl_sql.php',
    'tbl_export.php',
    'tbl_operations.php',
    'tbl_structure.php',
    'tbl_relation.php',
    'tbl_replace.php',
    'tbl_row_action.php',
    'tbl_select.php',
    'tbl_zoom_select.php',
    'transformation_overview.php',
    'transformation_wrapper.php',
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
 * f.e. PMA\libraries\Config: fontsize
 *
 * @todo variables should be handled by their respective owners (objects)
 * f.e. lang, server, collation_connection in PMA\libraries\Config
 */
$token_mismatch = true;
$token_provided = false;
if (PMA_isValid($_REQUEST['token'])) {
    $token_provided = true;
    $token_mismatch = ! hash_equals($_SESSION[' PMA_token '], $_REQUEST['token']);
}

if ($token_mismatch) {
    /**
     *  List of parameters which are allowed from unsafe source
     */
    $allow_list = array(
        /* needed for direct access, see FAQ 1.34
         * also, server needed for cookie login screen (multi-server)
         */
        'server', 'db', 'table', 'target', 'lang',
        /* Session ID */
        'phpMyAdmin',
        /* Cookie preferences */
        'pma_lang', 'pma_collation_connection',
        /* Possible login form */
        'pma_servername', 'pma_username', 'pma_password',
        'g-recaptcha-response',
        /* Needed to send the correct reply */
        'ajax_request',
        /* Permit to log out even if there is a token mismatch */
        'old_usr',
        /* Permit redirection with token-mismatch in url.php */
        'url',
        /* Permit session expiry flag */
        'session_expired',
        /* JS loading */
        'scripts', 'call_done'
    );
    /**
     * Allow changing themes in test/theme.php
     */
    if (defined('PMA_TEST_THEME')) {
        $allow_list[] = 'set_theme';
    }
    /**
     * Require cleanup functions
     */
    include './libraries/cleanup.lib.php';
    /**
     * Do actual cleanup
     */
    PMA_removeRequestVars($allow_list);

}


/**
 * current selected database
 * @global string $GLOBALS['db']
 */
PMA_setGlobalDbOrTable('db');

/**
 * current selected table
 * @global string $GLOBALS['table']
 */
PMA_setGlobalDbOrTable('table');

/**
 * Store currently selected recent table.
 * Affect $GLOBALS['db'] and $GLOBALS['table']
 */
if (PMA_isValid($_REQUEST['selected_recent_table'])) {
    $recent_table = json_decode($_REQUEST['selected_recent_table'], true);

    $GLOBALS['db']
        = (array_key_exists('db', $recent_table) && is_string($recent_table['db'])) ?
            $recent_table['db'] : '';
    $GLOBALS['url_params']['db'] = $GLOBALS['db'];

    $GLOBALS['table']
        = (array_key_exists('table', $recent_table) && is_string($recent_table['table'])) ?
            $recent_table['table'] : '';
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

//$_REQUEST['set_theme'] // checked later in this file LABEL_theme_setup
//$_REQUEST['server']; // checked later in this file
//$_REQUEST['lang'];   // checked by LABEL_loading_language_file

/******************************************************************************/
/* loading language file                       LABEL_loading_language_file    */

/**
 * lang detection is done here
 */
$language = LanguageManager::getInstance()->selectLanguage();
$language->activate();

// Defines the cell alignment values depending on text direction
if ($GLOBALS['text_dir'] == 'ltr') {
    $GLOBALS['cell_align_left']  = 'left';
    $GLOBALS['cell_align_right'] = 'right';
} else {
    $GLOBALS['cell_align_left']  = 'right';
    $GLOBALS['cell_align_right'] = 'left';
}

/**
 * check for errors occurred while loading configuration
 * this check is done here after loading language files to present errors in locale
 */
$GLOBALS['PMA_Config']->checkPermissions();

if ($GLOBALS['PMA_Config']->error_config_file) {
    $error = '[strong]' . __('Failed to read configuration file!') . '[/strong]'
        . '[br][br]'
        . __(
            'This usually means there is a syntax error in it, '
            . 'please check any errors shown below.'
        )
        . '[br][br]'
        . '[conferr]';
    trigger_error($error, E_USER_ERROR);
}
if ($GLOBALS['PMA_Config']->error_config_default_file) {
    $error = sprintf(
        __('Could not load default configuration from: %1$s'),
        $GLOBALS['PMA_Config']->default_source
    );
    trigger_error($error, E_USER_ERROR);
}

/**
 * As we try to handle charsets by ourself, mbstring overloads just
 * break it, see bug 1063821.
 */
if (@extension_loaded('mbstring') && @ini_get('mbstring.func_overload') != '0') {
    PMA_fatalError(
        __(
            'You have enabled mbstring.func_overload in your PHP '
            . 'configuration. This option is incompatible with phpMyAdmin '
            . 'and might cause some data to be corrupted!'
        )
    );
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
 * $default_server comes from PMA\libraries\Config::enableBc()
 * @todo merge into PMA\libraries\Config
 */
// Do we have some server?
if (! isset($cfg['Servers']) || count($cfg['Servers']) == 0) {
    // No server => create one with defaults
    $cfg['Servers'] = array(1 => $default_server);
} else {
    // We have server(s) => apply default configuration
    $new_servers = array();

    foreach ($cfg['Servers'] as $server_index => $each_server) {

        // Detect wrong configuration
        if (!is_int($server_index) || $server_index < 1) {
            trigger_error(
                sprintf(__('Invalid server index: %s'), $server_index),
                E_USER_ERROR
            );
        }

        $each_server = array_merge($default_server, $each_server);

        // Don't use servers with no hostname
        if ($each_server['connect_type'] == 'tcp' && empty($each_server['host'])) {
            trigger_error(
                sprintf(
                    __(
                        'Invalid hostname for server %1$s. '
                        . 'Please review your configuration.'
                    ),
                    $server_index
                ),
                E_USER_ERROR
            );
        }

        // Final solution to bug #582890
        // If we are using a socket connection
        // and there is nothing in the verbose server name
        // or the host field, then generate a name for the server
        // in the form of "Server 2", localized of course!
        if ($each_server['connect_type'] == 'socket'
            && empty($each_server['host'])
            && empty($each_server['verbose'])
        ) {
            $each_server['verbose'] = sprintf(__('Server %d'), $server_index);
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

ThemeManager::initializeTheme();

if (! defined('PMA_MINIMUM_COMMON')) {
    /**
     * Character set conversion.
     */
    include_once './libraries/charset_conversion.lib.php';

    /**
     * Lookup server by name
     * (see FAQ 4.8)
     */
    if (! empty($_REQUEST['server'])
        && is_string($_REQUEST['server'])
        && ! is_numeric($_REQUEST['server'])
    ) {
        foreach ($cfg['Servers'] as $i => $server) {
            $verboseToLower = mb_strtolower($server['verbose']);
            $serverToLower = mb_strtolower($_REQUEST['server']);
            if ($server['host'] == $_REQUEST['server']
                || $server['verbose'] == $_REQUEST['server']
                || $verboseToLower == $serverToLower
                || md5($verboseToLower) === $serverToLower
            ) {
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

    if (isset($_REQUEST['server'])
        && (is_string($_REQUEST['server']) || is_numeric($_REQUEST['server']))
        && ! empty($_REQUEST['server'])
        && ! empty($cfg['Servers'][$_REQUEST['server']])
    ) {
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
        && $lang == 'ja'
    ) {
        include_once './libraries/kanji-encoding.lib.php';
    } // end if

    /**
     * save some settings in cookies
     * @todo should be done in PMA\libraries\Config
     */
    $GLOBALS['PMA_Config']->setCookie('pma_lang', $GLOBALS['lang']);
    if (isset($GLOBALS['collation_connection'])) {
        $GLOBALS['PMA_Config']->setCookie(
            'pma_collation_connection',
            $GLOBALS['collation_connection']
        );
    }

    $_SESSION['PMA_Theme_Manager']->setThemeCookie();

    if (! empty($cfg['Server'])) {

        /**
         * Loads the proper database interface for this server
         */
        include_once './libraries/database_interface.inc.php';

        include_once './libraries/logging.lib.php';

        // get LoginCookieValidity from preferences cache
        // no generic solution for loading preferences from cache as some settings
        // need to be kept for processing in
        // PMA\libraries\Config::loadUserPreferences()
        $cache_key = 'server_' . $GLOBALS['server'];
        if (isset($_SESSION['cache'][$cache_key]['userprefs']['LoginCookieValidity'])
        ) {
            $value
                = $_SESSION['cache'][$cache_key]['userprefs']['LoginCookieValidity'];
            $GLOBALS['PMA_Config']->set('LoginCookieValidity', $value);
            $GLOBALS['cfg']['LoginCookieValidity'] = $value;
            unset($value);
        }
        unset($cache_key);

        // Gets the authentication library that fits the $cfg['Server'] settings
        // and run authentication

        // to allow HTTP or http
        $cfg['Server']['auth_type']
            = mb_strtolower($cfg['Server']['auth_type']);

        /**
         * the required auth type plugin
         */
        $auth_class = "Authentication" . ucfirst($cfg['Server']['auth_type']);
        if (! file_exists(
            './libraries/plugins/auth/'
            . $auth_class . '.php'
        )) {
            PMA_fatalError(
                __('Invalid authentication method set in configuration:')
                . ' ' . $cfg['Server']['auth_type']
            );
        }
        if (isset($_REQUEST['pma_password'])) {
            $_REQUEST['pma_password'] = substr($_REQUEST['pma_password'], 0, 256);
        }
        $fqnAuthClass = 'PMA\libraries\plugins\auth\\' . $auth_class;
        // todo: add plugin manager
        $plugin_manager = null;
        /** @var AuthenticationPlugin $auth_plugin */
        $auth_plugin = new $fqnAuthClass($plugin_manager);

        if (! $auth_plugin->authCheck()) {
            /* Force generating of new session on login */
            PMA_secureSession();
            $auth_plugin->auth();
        } else {
            $auth_plugin->authSetUser();
        }

        // Check IP-based Allow/Deny rules as soon as possible to reject the
        // user based on mod_access in Apache
        if (isset($cfg['Server']['AllowDeny'])
            && isset($cfg['Server']['AllowDeny']['order'])
        ) {

            /**
             * ip based access library
             */
            include_once './libraries/ip_allow_deny.lib.php';

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
                if (PMA_allowDeny('allow') && ! PMA_allowDeny('deny')) {
                    $allowDeny_forbidden = false;
                } else {
                    $allowDeny_forbidden = true;
                }
            } // end if ... elseif ... elseif

            // Ejects the user if banished
            if ($allowDeny_forbidden) {
                PMA_logUser($cfg['Server']['user'], 'allow-denied');
                $auth_plugin->authFails();
            }
        } // end if

        // is root allowed?
        if (! $cfg['Server']['AllowRoot'] && $cfg['Server']['user'] == 'root') {
            $allowDeny_forbidden = true;
            PMA_logUser($cfg['Server']['user'], 'root-denied');
            $auth_plugin->authFails();
        }

        // is a login without password allowed?
        if (! $cfg['Server']['AllowNoPassword']
            && $cfg['Server']['password'] == ''
        ) {
            $login_without_password_is_forbidden = true;
            PMA_logUser($cfg['Server']['user'], 'empty-denied');
            $auth_plugin->authFails();
        }

        // if using TCP socket is not needed
        if (mb_strtolower($cfg['Server']['connect_type']) == 'tcp') {
            $cfg['Server']['socket'] = '';
        }

        // Try to connect MySQL with the control user profile (will be used to
        // get the privileges list for the current user but the true user link
        // must be open after this one so it would be default one for all the
        // scripts)
        $controllink = false;
        if ($cfg['Server']['controluser'] != '') {
            if (! empty($cfg['Server']['controlhost'])
                || ! empty($cfg['Server']['controlport'])
            ) {
                $server_details = array();
                if (! empty($cfg['Server']['controlhost'])) {
                    $server_details['host'] = $cfg['Server']['controlhost'];
                } else {
                    $server_details['host'] = $cfg['Server']['host'];
                }
                if (! empty($cfg['Server']['controlport'])) {
                    $server_details['port'] = $cfg['Server']['controlport'];
                } elseif ($server_details['host'] == $cfg['Server']['host']) {
                    // Evaluates to true when controlhost == host
                    // or controlhost is not defined (hence it defaults to host)
                    // In such case we can use the value of port.
                    $server_details['port'] = $cfg['Server']['port'];
                }
                // otherwise we leave the $server_details['port'] unset,
                // allowing it to take default mysql port

                $controllink = $GLOBALS['dbi']->connect(
                    $cfg['Server']['controluser'],
                    $cfg['Server']['controlpass'],
                    true,
                    $server_details
                );
            } else {
                $controllink = $GLOBALS['dbi']->connect(
                    $cfg['Server']['controluser'],
                    $cfg['Server']['controlpass'],
                    true
                );
            }
        }

        // Connects to the server (validates user's login)
        /** @var DatabaseInterface $userlink */
        $userlink = $GLOBALS['dbi']->connect(
            $cfg['Server']['user'], $cfg['Server']['password'], false
        );

        // Set timestamp for the session, if required.
        if ($cfg['Server']['SessionTimeZone'] != '') {
            $sql_query_tz = 'SET ' . Util::backquote('time_zone') . ' = '
                . '\''
                . $GLOBALS['dbi']->escapeString($cfg['Server']['SessionTimeZone'])
                . '\'';

            if (! $userlink->query($sql_query_tz)) {
                $error_message_tz = sprintf(
                    __(
                        'Unable to use timezone %1$s for server %2$d. '
                        . 'Please check your configuration setting for '
                        . '[em]$cfg[\'Servers\'][%3$d][\'SessionTimeZone\'][/em]. '
                        . 'phpMyAdmin is currently using the default time zone '
                        . 'of the database server.'
                    ),
                    $cfg['Servers'][$GLOBALS['server']]['SessionTimeZone'],
                    $GLOBALS['server'],
                    $GLOBALS['server']
                );

                $GLOBALS['error_handler']->addError(
                    $error_message_tz,
                    E_USER_WARNING,
                    '',
                    '',
                    false
                );
            }
        }

        if (! $controllink) {
            /*
             * Open separate connection for control queries, this is needed
             * to avoid problems with table locking used in main connection
             * and phpMyAdmin issuing queries to configuration storage, which
             * is not locked by that time.
             */
            $controllink = $GLOBALS['dbi']->connect(
                $cfg['Server']['user'], $cfg['Server']['password'], false
            );
        }

        $auth_plugin->storeUserCredentials();

        /* Log success */
        PMA_logUser($cfg['Server']['user']);

        if (PMA_MYSQL_INT_VERSION < $cfg['MysqlMinVersion']['internal']) {
            PMA_fatalError(
                __('You should upgrade to %s %s or later.'),
                array('MySQL', $cfg['MysqlMinVersion']['human'])
            );
        }

        /**
         * Type handling object.
         */
        $GLOBALS['PMA_Types'] = new TypesMySQL();

        /**
         * Charset information
         */
        include_once './libraries/mysql_charsets.inc.php';

        if (!isset($mysql_charsets)) {
            $mysql_charsets = array();
            $mysql_collations_flat = array();
        }

        /**
         * Initializes the SQL parsing library.
         */
        include_once SQL_PARSER_AUTOLOAD;

        // Loads closest context to this version.
        SqlParser\Context::loadClosest('MySql' . PMA_MYSQL_INT_VERSION);

        // Sets the default delimiter (if specified).
        if (!empty($_REQUEST['sql_delimiter'])) {
            SqlParser\Lexer::$DEFAULT_DELIMITER = $_REQUEST['sql_delimiter'];
        }

        // TODO: Set SQL modes too.

        /**
         * the DbList class as a stub for the ListDatabase class
         */
        $dblist = new DbList;
        $dblist->userlink = $userlink;
        $dblist->controllink = $controllink;

        /**
         * some resetting has to be done when switching servers
         */
        if (isset($_SESSION['tmpval']['previous_server'])
            && $_SESSION['tmpval']['previous_server'] != $GLOBALS['server']
        ) {
            unset($_SESSION['tmpval']['navi_limit_offset']);
        }
        $_SESSION['tmpval']['previous_server'] = $GLOBALS['server'];

    } else { // end server connecting
        // No need to check for 'PMA_BYPASS_GET_INSTANCE' since this execution path
        // applies only to initial login
        $response = Response::getInstance();
        $response->getHeader()->disableMenuAndConsole();
        $response->getFooter()->setMinimal();
    }

    /**
     * check if profiling was requested and remember it
     * (note: when $cfg['ServerDefault'] = 0, constant is not defined)
     */
    if (isset($_REQUEST['profiling'])
        && Util::profilingSupported()
    ) {
        $_SESSION['profiling'] = true;
    } elseif (isset($_REQUEST['profiling_form'])) {
        // the checkbox was unchecked
        unset($_SESSION['profiling']);
    }

    // load user preferences
    $GLOBALS['PMA_Config']->loadUserPreferences();

    /**
     * Inclusion of profiling scripts is needed on various
     * pages like sql, tbl_sql, db_sql, tbl_select
     */
    if (! defined('PMA_BYPASS_GET_INSTANCE')) {
        $response = Response::getInstance();
    }
    if (isset($_SESSION['profiling'])) {
        $header   = $response->getHeader();
        $scripts  = $header->getScripts();
        $scripts->addFile('chart.js');
        $scripts->addFile('jqplot/jquery.jqplot.js');
        $scripts->addFile('jqplot/plugins/jqplot.pieRenderer.js');
        $scripts->addFile('jqplot/plugins/jqplot.highlighter.js');
        $scripts->addFile('jquery/jquery.tablesorter.js');
    }

    /*
     * There is no point in even attempting to process
     * an ajax request if there is a token mismatch
     */
    if (isset($response) && $response->isAjax() && $token_mismatch) {
        $response->setRequestStatus(false);
        $response->addJSON(
            'message',
            Message::error(__('Error: Token mismatch'))
        );
        exit;
    }
} else { // end if !defined('PMA_MINIMUM_COMMON')
    // load user preferences
    $GLOBALS['PMA_Config']->loadUserPreferences();
}

// remove sensitive values from session
$GLOBALS['PMA_Config']->set('blowfish_secret', '');
$GLOBALS['PMA_Config']->set('Servers', '');
$GLOBALS['PMA_Config']->set('default_server', '');

/* Tell tracker that it can actually work */
Tracker::enable();

if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
    PMA_fatalError(__("GLOBALS overwrite attempt"));
}

/**
 * protect against possible exploits - there is no need to have so much variables
 */
if (count($_REQUEST) > 1000) {
    PMA_fatalError(__('possible exploit'));
}

/**
 * Check for numeric keys
 * (if register_globals is on, numeric key can be found in $GLOBALS)
 */
foreach ($GLOBALS as $key => $dummy) {
    if (is_numeric($key)) {
        PMA_fatalError(__('numeric key detected'));
    }
}
unset($dummy);

// here, the function does not exist with this configuration:
// $cfg['ServerDefault'] = 0;
$GLOBALS['is_superuser']
    = isset($GLOBALS['dbi']) && $GLOBALS['dbi']->isSuperuser();

if (!empty($__redirect) && in_array($__redirect, $goto_whitelist)) {
    /**
     * include subform target page
     */
    include $__redirect;
    exit();
}

// If Zero configuration mode enabled, check PMA tables in current db.
if (! defined('PMA_MINIMUM_COMMON')
    && ! empty($GLOBALS['server'])
    && isset($GLOBALS['cfg']['ZeroConf'])
    && $GLOBALS['cfg']['ZeroConf'] == true
) {
    if (! empty($GLOBALS['db'])) {
        $cfgRelation = PMA_getRelationsParam();
        if (empty($cfgRelation['db'])) {
            PMA_fixPMATables($GLOBALS['db'], false);
        }
    }
    $cfgRelation = PMA_getRelationsParam();
    if (empty($cfgRelation['db'])) {
        foreach ($GLOBALS['dblist']->databases as $database) {
            if ($database == 'phpmyadmin') {
                PMA_fixPMATables($database, false);
            }
        }
    }
}

if (! defined('PMA_MINIMUM_COMMON')) {
    include_once 'libraries/config/messages.inc.php';
    include 'libraries/config/user_preferences.forms.php';
    include_once 'libraries/config/page_settings.forms.php';
}

/**
 * @global array MySQL charsets map
 */
$GLOBALS['mysql_charset_map'] = array(
    'big5'         => 'big5',
    'cp-866'       => 'cp866',
    'euc-jp'       => 'ujis',
    'euc-kr'       => 'euckr',
    'gb2312'       => 'gb2312',
    'gbk'          => 'gbk',
    'iso-8859-1'   => 'latin1',
    'iso-8859-2'   => 'latin2',
    'iso-8859-7'   => 'greek',
    'iso-8859-8'   => 'hebrew',
    'iso-8859-8-i' => 'hebrew',
    'iso-8859-9'   => 'latin5',
    'iso-8859-13'  => 'latin7',
    'iso-8859-15'  => 'latin1',
    'koi8-r'       => 'koi8r',
    'shift_jis'    => 'sjis',
    'tis-620'      => 'tis620',
    'utf-8'        => 'utf8',
    'windows-1250' => 'cp1250',
    'windows-1251' => 'cp1251',
    'windows-1252' => 'latin1',
    'windows-1256' => 'cp1256',
    'windows-1257' => 'cp1257',
);
