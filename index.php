<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Display\GitRevision;
use PhpMyAdmin\Message;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\UserPreferences;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/**
 * pass variables to child pages
 */
$drops = [
    'lang',
    'server',
    'collation_connection',
    'db',
    'table',
];
foreach ($drops as $each_drop) {
    if (array_key_exists($each_drop, $_GET)) {
        unset($_GET[$each_drop]);
    }
}
unset($drops, $each_drop);

/*
 * Black list of all scripts to which front-end must submit data.
 * Such scripts must not be loaded on home page.
 *
 */
$target_blacklist =  [
    'import.php',
    'export.php',
];

// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && 0 !== strpos($_REQUEST['target'], "index")
    && ! in_array($_REQUEST['target'], $target_blacklist)
    && Core::checkPageValidity($_REQUEST['target'], [], true)
) {
    include ROOT_PATH . $_REQUEST['target'];
    exit;
}

if (isset($_REQUEST['ajax_request']) && ! empty($_REQUEST['access_time'])) {
    exit;
}

// if user selected a theme
if (isset($_POST['set_theme'])) {
    $tmanager = ThemeManager::getInstance();
    $tmanager->setActiveTheme($_POST['set_theme']);
    $tmanager->setThemeCookie();

    $userPreferences = new UserPreferences();
    $prefs = $userPreferences->load();
    $prefs["config_data"]["ThemeDefault"] = $_POST['set_theme'];
    $userPreferences->save($prefs["config_data"]);

    header('Location: index.php' . Url::getCommonRaw());
    exit();
}
// Change collation connection
if (isset($_POST['collation_connection'])) {
    $GLOBALS['PMA_Config']->setUserValue(
        null,
        'DefaultConnectionCollation',
        $_POST['collation_connection'],
        'utf8mb4_unicode_ci'
    );
    header('Location: index.php' . Url::getCommonRaw());
    exit();
}


// See FAQ 1.34
if (! empty($_REQUEST['db'])) {
    $page = null;
    if (! empty($_REQUEST['table'])) {
        $page = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabTable'],
            'table'
        );
    } else {
        $page = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            'database'
        );
    }
    include ROOT_PATH . $page;
    exit;
}

$response = Response::getInstance();

$controller = new HomeController(
    $response,
    $GLOBALS['dbi']
);

/**
 * Check if it is an ajax request to reload the recent tables list.
 */
if ($response->isAjax() && ! empty($_REQUEST['recent_table'])) {
    $response->addJSON(
        'list',
        RecentFavoriteTable::getInstance('recent')->getHtmlList()
    );
    exit;
}

if ($GLOBALS['PMA_Config']->isGitRevision()) {
    // If ajax request to get revision
    if (isset($_REQUEST['git_revision']) && $response->isAjax()) {
        GitRevision::display();
        exit;
    }
    // Else show empty html
    echo '<div id="is_git_revision"></div>';
}

// Handles some variables that may have been sent by the calling script
$GLOBALS['db'] = '';
$GLOBALS['table'] = '';
$show_query = '1';

// Any message to display?
if (! empty($message)) {
    echo Util::getMessage($message);
    unset($message);
}
if (isset($_SESSION['partial_logout'])) {
    Message::success(
        __('You were logged out from one server, to logout completely from phpMyAdmin, you need to logout from all servers.')
    )->display();
    unset($_SESSION['partial_logout']);
}

if ($server > 0) {
    include ROOT_PATH . 'libraries/server_common.inc.php';
}

echo $controller->index();

/**
 * mbstring is used for handling multibytes inside parser, so it is good
 * to tell user something might be broken without it, see bug #1063149.
 */
if (! extension_loaded('mbstring')) {
    trigger_error(
        __(
            'The mbstring PHP extension was not found and you seem to be using'
            . ' a multibyte charset. Without the mbstring extension phpMyAdmin'
            . ' is unable to split strings correctly and it may result in'
            . ' unexpected results.'
        ),
        E_USER_WARNING
    );
}

/**
 * Missing functionality
 */
if (! extension_loaded('curl') && ! ini_get('allow_url_fopen')) {
    trigger_error(
        __(
            'The curl extension was not found and allow_url_fopen is '
            . 'disabled. Due to this some features such as error reporting '
            . 'or version check are disabled.'
        )
    );
}

if ($cfg['LoginCookieValidityDisableWarning'] == false) {
    /**
     * Check whether session.gc_maxlifetime limits session validity.
     */
    $gc_time = (int) ini_get('session.gc_maxlifetime');
    if ($gc_time < $GLOBALS['cfg']['LoginCookieValidity']) {
        trigger_error(
            __(
                'Your PHP parameter [a@https://secure.php.net/manual/en/session.' .
                'configuration.php#ini.session.gc-maxlifetime@_blank]session.' .
                'gc_maxlifetime[/a] is lower than cookie validity configured ' .
                'in phpMyAdmin, because of this, your login might expire sooner ' .
                'than configured in phpMyAdmin.'
            ),
            E_USER_WARNING
        );
    }
}

/**
 * Check whether LoginCookieValidity is limited by LoginCookieStore.
 */
if ($GLOBALS['cfg']['LoginCookieStore'] != 0
    && $GLOBALS['cfg']['LoginCookieStore'] < $GLOBALS['cfg']['LoginCookieValidity']
) {
    trigger_error(
        __(
            'Login cookie store is lower than cookie validity configured in ' .
            'phpMyAdmin, because of this, your login will expire sooner than ' .
            'configured in phpMyAdmin.'
        ),
        E_USER_WARNING
    );
}

/**
 * Warning if using the default MySQL controluser account
 */
if ($server != 0
    && isset($GLOBALS['cfg']['Server']['controluser']) && $GLOBALS['cfg']['Server']['controluser'] == 'pma'
    && isset($GLOBALS['cfg']['Server']['controlpass']) && $GLOBALS['cfg']['Server']['controlpass'] == 'pmapass'
) {
    trigger_error(
        __('Your server is running with default values for the controluser and password (controlpass) and is open to intrusion; you really should fix this security weakness by changing the password for controluser \'pma\'.'),
        E_USER_WARNING
    );
}


/**
 * Check if user does not have defined blowfish secret and it is being used.
 */
if (! empty($_SESSION['encryption_key'])) {
    if (empty($GLOBALS['cfg']['blowfish_secret'])) {
        trigger_error(
            __(
                'The configuration file now needs a secret passphrase (blowfish_secret).'
            ),
            E_USER_WARNING
        );
    } elseif (strlen($GLOBALS['cfg']['blowfish_secret']) < 32) {
        trigger_error(
            __(
                'The secret passphrase in configuration (blowfish_secret) is too short.'
            ),
            E_USER_WARNING
        );
    }
}

/**
 * Check for existence of config directory which should not exist in
 * production environment.
 */
if (@file_exists(ROOT_PATH . 'config')) {
    trigger_error(
        __(
            'Directory [code]config[/code], which is used by the setup script, ' .
            'still exists in your phpMyAdmin directory. It is strongly ' .
            'recommended to remove it once phpMyAdmin has been configured. ' .
            'Otherwise the security of your server may be compromised by ' .
            'unauthorized people downloading your configuration.'
        ),
        E_USER_WARNING
    );
}

$relation = new Relation($GLOBALS['dbi']);

if ($server > 0) {
    $cfgRelation = $relation->getRelationsParam();
    if (! $cfgRelation['allworks']
        && $cfg['PmaNoRelation_DisableWarning'] == false
    ) {
        $msg_text = __(
            'The phpMyAdmin configuration storage is not completely '
            . 'configured, some extended features have been deactivated. '
            . '%sFind out why%s. '
        );
        if ($cfg['ZeroConf'] == true) {
            $msg_text .= '<br>' .
                __(
                    'Or alternately go to \'Operations\' tab of any database '
                    . 'to set it up there.'
                );
        }
        $msg = Message::notice($msg_text);
        $msg->addParamHtml('<a href="./chk_rel.php" data-post="' . Url::getCommon() . '">');
        $msg->addParamHtml('</a>');
        /* Show error if user has configured something, notice elsewhere */
        if (! empty($cfg['Servers'][$server]['pmadb'])) {
            $msg->isError(true);
        }
        $msg->display();
    } // end if
}

/**
 * Warning about Suhosin only if its simulation mode is not enabled
 */
if ($cfg['SuhosinDisableWarning'] == false
    && ini_get('suhosin.request.max_value_length')
    && ini_get('suhosin.simulation') == '0'
) {
    trigger_error(
        sprintf(
            __(
                'Server running with Suhosin. Please refer to %sdocumentation%s ' .
                'for possible issues.'
            ),
            '[doc@faq1-38]',
            '[/doc]'
        ),
        E_USER_WARNING
    );
}

/* Missing template cache */
if (is_null($GLOBALS['PMA_Config']->getTempDir('twig'))) {
    trigger_error(
        sprintf(
            __('The $cfg[\'TempDir\'] (%s) is not accessible. phpMyAdmin is not able to cache templates and will be slow because of this.'),
            $GLOBALS['PMA_Config']->get('TempDir')
        ),
        E_USER_WARNING
    );
}

/**
 * Warning about incomplete translations.
 *
 * The data file is created while creating release by ./scripts/remove-incomplete-mo
 */
if (@file_exists(ROOT_PATH . 'libraries/language_stats.inc.php')) {
    include ROOT_PATH . 'libraries/language_stats.inc.php';
    /*
     * This message is intentionally not translated, because we're
     * handling incomplete translations here and focus on english
     * speaking users.
     */
    if (isset($GLOBALS['language_stats'][$lang])
        && $GLOBALS['language_stats'][$lang] < $cfg['TranslationWarningThreshold']
    ) {
        trigger_error(
            'You are using an incomplete translation, please help to make it '
            . 'better by [a@https://www.phpmyadmin.net/translate/'
            . '@_blank]contributing[/a].',
            E_USER_NOTICE
        );
    }
}
