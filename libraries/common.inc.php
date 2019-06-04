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

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Response;
use PhpMyAdmin\Session;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Util;

/**
 * block attempts to directly run this script
 */
if (getcwd() == dirname(__FILE__)) {
    die('Attack stopped');
}

/**
 * Minimum PHP version; can't call Core::fatalError() which uses a
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
 * Load hash polyfill.
 */
require_once './libraries/hash.lib.php';

/**
 * Activate autoloader
 */
if (! @is_readable(AUTOLOAD_FILE)) {
    die(
        'File <tt>' . AUTOLOAD_FILE . '</tt> missing or not readable. <br />'
        . 'Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">install library files</a>.'
    );
}
require_once AUTOLOAD_FILE;

/**
 * Load gettext functions.
 */
PhpMyAdmin\MoTranslator\Loader::loadFunctions();

/**
 * initialize the error handler
 */
$GLOBALS['error_handler'] = new ErrorHandler();

/**
 * Warning about missing PHP extensions.
 */
Core::checkExtensions();

/**
 * Configure required PHP settings.
 */
Core::configure();

/******************************************************************************/
/* start procedural code                       label_start_procedural         */

Core::cleanupPathInfo();

/******************************************************************************/
/* parsing configuration file                  LABEL_parsing_config_file      */

/**
 * @global Config $GLOBALS['PMA_Config']
 * force reading of config file, because we removed sensitive values
 * in the previous iteration
 */
$GLOBALS['PMA_Config'] = new Config(CONFIG_FILE);

/**
 * include session handling after the globals, to prevent overwriting
 */
if (! defined('PMA_NO_SESSION')) {
    Session::setUp($GLOBALS['PMA_Config'], $GLOBALS['error_handler']);
}

/**
 * init some variables LABEL_variables_init
 */

/**
 * holds parameters to be passed to next page
 * @global array $GLOBALS['url_params']
 */
$GLOBALS['url_params'] = array();

/**
 * holds page that should be displayed
 * @global string $GLOBALS['goto']
 */
$GLOBALS['goto'] = '';
// Security fix: disallow accessing serious server files via "?goto="
if (Core::checkPageValidity($_REQUEST['goto'])) {
    $GLOBALS['goto'] = $_REQUEST['goto'];
    $GLOBALS['url_params']['goto'] = $_REQUEST['goto'];
} else {
    unset($_REQUEST['goto'], $_GET['goto'], $_POST['goto'], $_COOKIE['goto']);
}

/**
 * returning page
 * @global string $GLOBALS['back']
 */
if (Core::checkPageValidity($_REQUEST['back'])) {
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
 * f.e. PhpMyAdmin\Config: fontsize
 *
 * Check for token mismatch only if the Request method is POST
 * GET Requests would never have token and therefore checking
 * mis-match does not make sense
 *
 * @todo variables should be handled by their respective owners (objects)
 * f.e. lang, server in PhpMyAdmin\Config
 */

$token_mismatch = true;
$token_provided = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (Core::isValid($_POST['token'])) {
        $token_provided = true;
        $token_mismatch = ! @hash_equals($_SESSION[' PMA_token '], $_POST['token']);
    }

    if ($token_mismatch) {
        /* Warn in case the mismatch is result of failed setting of session cookie */
        if (isset($_POST['set_session']) && $_POST['set_session'] != session_id()) {
            trigger_error(
                __(
                    'Failed to set session cookie. Maybe you are using '
                    . 'HTTP instead of HTTPS to access phpMyAdmin.'
                ),
                E_USER_ERROR
            );
        }
        /**
         * We don't allow any POST operation parameters if the token is mismatched
         * or is not provided
         */
        $whitelist = array('ajax_request');
        PhpMyAdmin\Sanitize::removeRequestVars($whitelist);
    }
}


/**
 * current selected database
 * @global string $GLOBALS['db']
 */
Core::setGlobalDbOrTable('db');

/**
 * current selected table
 * @global string $GLOBALS['table']
 */
Core::setGlobalDbOrTable('table');

/**
 * Store currently selected recent table.
 * Affect $GLOBALS['db'] and $GLOBALS['table']
 */
if (Core::isValid($_REQUEST['selected_recent_table'])) {
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
if (Core::isValid($_POST['sql_query'])) {
    $GLOBALS['sql_query'] = $_POST['sql_query'];
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

/**
 * check for errors occurred while loading configuration
 * this check is done here after loading language files to present errors in locale
 */
$GLOBALS['PMA_Config']->checkPermissions();
$GLOBALS['PMA_Config']->checkErrors();

/* Check server configuration */
Core::checkConfiguration();

/* Check request for possible attacks */
Core::checkRequest();

/******************************************************************************/
/* setup servers                                       LABEL_setup_servers    */

$GLOBALS['PMA_Config']->checkServers();

/**
 * current server
 * @global integer $GLOBALS['server']
 */
$GLOBALS['server'] = $GLOBALS['PMA_Config']->selectServer();
$GLOBALS['url_params']['server'] = $GLOBALS['server'];

/**
 * BC - enable backward compatibility
 * exports all configuration settings into $GLOBALS ($GLOBALS['cfg'])
 */
$GLOBALS['PMA_Config']->enableBc();

/******************************************************************************/
/* setup themes                                          LABEL_theme_setup    */

ThemeManager::initializeTheme();

if (! defined('PMA_MINIMUM_COMMON')) {
    /**
     * save some settings in cookies
     * @todo should be done in PhpMyAdmin\Config
     */
    $GLOBALS['PMA_Config']->setCookie('pma_lang', $GLOBALS['lang']);

    ThemeManager::getInstance()->setThemeCookie();

    if (! empty($cfg['Server'])) {

        /**
         * Loads the proper database interface for this server
         */
        DatabaseInterface::load();

        // get LoginCookieValidity from preferences cache
        // no generic solution for loading preferences from cache as some settings
        // need to be kept for processing in
        // PhpMyAdmin\Config::loadUserPreferences()
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

        /**
         * the required auth type plugin
         */
        $auth_class = 'PhpMyAdmin\\Plugins\\Auth\\Authentication' . ucfirst(strtolower($cfg['Server']['auth_type']));
        if (! @class_exists($auth_class)) {
            Core::fatalError(
                __('Invalid authentication method set in configuration:')
                . ' ' . $cfg['Server']['auth_type']
            );
        }
        if (isset($_POST['pma_password']) && strlen($_POST['pma_password']) > 256) {
            $_POST['pma_password'] = substr($_POST['pma_password'], 0, 256);
        }
        $auth_plugin = new $auth_class();

        $auth_plugin->authenticate();

        // Try to connect MySQL with the control user profile (will be used to
        // get the privileges list for the current user but the true user link
        // must be open after this one so it would be default one for all the
        // scripts)
        $controllink = false;
        if ($cfg['Server']['controluser'] != '') {
            $controllink = $GLOBALS['dbi']->connect(
                DatabaseInterface::CONNECT_CONTROL
            );
        }

        // Connects to the server (validates user's login)
        /** @var DatabaseInterface $userlink */
        $userlink = $GLOBALS['dbi']->connect(DatabaseInterface::CONNECT_USER);

        if ($userlink === false) {
            $auth_plugin->showFailure('mysql-denied');
        }

        if (! $controllink) {
            /*
             * Open separate connection for control queries, this is needed
             * to avoid problems with table locking used in main connection
             * and phpMyAdmin issuing queries to configuration storage, which
             * is not locked by that time.
             */
            $controllink = $GLOBALS['dbi']->connect(
                DatabaseInterface::CONNECT_USER,
                null,
                DatabaseInterface::CONNECT_CONTROL
            );
        }

        $auth_plugin->rememberCredentials();

        $auth_plugin->checkTwoFactor();

        /* Log success */
        Logging::logUser($cfg['Server']['user']);

        if ($GLOBALS['dbi']->getVersion() < $cfg['MysqlMinVersion']['internal']) {
            Core::fatalError(
                __('You should upgrade to %s %s or later.'),
                array('MySQL', $cfg['MysqlMinVersion']['human'])
            );
        }

        // Sets the default delimiter (if specified).
        if (!empty($_REQUEST['sql_delimiter'])) {
            PhpMyAdmin\SqlParser\Lexer::$DEFAULT_DELIMITER = $_REQUEST['sql_delimiter'];
        }

        // TODO: Set SQL modes too.

    } else { // end server connecting
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

    /**
     * Inclusion of profiling scripts is needed on various
     * pages like sql, tbl_sql, db_sql, tbl_select
     */
    $response = Response::getInstance();
    if (isset($_SESSION['profiling'])) {
        $scripts  = $response->getHeader()->getScripts();
        $scripts->addFile('chart.js');
        $scripts->addFile('vendor/jqplot/jquery.jqplot.js');
        $scripts->addFile('vendor/jqplot/plugins/jqplot.pieRenderer.js');
        $scripts->addFile('vendor/jqplot/plugins/jqplot.highlighter.js');
        $scripts->addFile('vendor/jquery/jquery.tablesorter.js');
    }

    /*
     * There is no point in even attempting to process
     * an ajax request if there is a token mismatch
     */
    if ($response->isAjax() && $_SERVER['REQUEST_METHOD'] == 'POST' && $token_mismatch) {
        $response->setRequestStatus(false);
        $response->addJSON(
            'message',
            Message::error(__('Error: Token mismatch'))
        );
        exit;
    }
}

// load user preferences
$GLOBALS['PMA_Config']->loadUserPreferences();

/* Tell tracker that it can actually work */
Tracker::enable();
