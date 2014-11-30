<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';

/**
 * display Git revision if requested
 */
require_once 'libraries/display_git_revision.lib.php';

/**
 * pass variables to child pages
 */
$drops = array(
    'lang',
    'server',
    'collation_connection',
    'db',
    'table'
);
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
 $target_blacklist = array (
    'import.php', 'export.php'
    );

// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && ! preg_match('/^index/', $_REQUEST['target'])
    && ! in_array($_REQUEST['target'], $target_blacklist)
    && in_array($_REQUEST['target'], $goto_whitelist)
) {
    include $_REQUEST['target'];
    exit;
}

// See FAQ 1.34
if (! empty($_REQUEST['db'])) {
    $page = null;
    if (! empty($_REQUEST['table'])) {
        $page = $GLOBALS['cfg']['DefaultTabTable'];
    } else {
        $page = $GLOBALS['cfg']['DefaultTabDatabase'];
    }
    include $page;
    exit;
}

/**
 * Check if it is an ajax request to reload the recent tables list.
 */
require_once 'libraries/RecentFavoriteTable.class.php';
if ($GLOBALS['is_ajax_request'] && ! empty($_REQUEST['recent_table'])) {
    $response = PMA_Response::getInstance();
    $response->addJSON(
        'list',
        PMA_RecentFavoriteTable::getInstance('recent')->getHtmlList()
    );
    exit;
}

if ($GLOBALS['PMA_Config']->isGitRevision()) {
    if (isset($_REQUEST['git_revision']) && $GLOBALS['is_ajax_request'] == true) {
        PMA_printGitRevision();
        exit;
    }
    echo '<div id="is_git_revision"></div>';
}

// Handles some variables that may have been sent by the calling script
$GLOBALS['db'] = '';
$GLOBALS['table'] = '';
$show_query = '1';

// Any message to display?
if (! empty($message)) {
    echo PMA_Util::getMessage($message);
    unset($message);
}

$common_url_query =  PMA_URL_getCommon('', '');

// when $server > 0, a server has been chosen so we can display
// all MySQL-related information
if ($server > 0) {
    include 'libraries/server_common.inc.php';
    include 'libraries/StorageEngine.class.php';

    // Use the verbose name of the server instead of the hostname
    // if a value is set
    $server_info = '';
    if (! empty($cfg['Server']['verbose'])) {
        $server_info .= htmlspecialchars($cfg['Server']['verbose']);
        if ($GLOBALS['cfg']['ShowServerInfo']) {
            $server_info .= ' (';
        }
    }
    if ($GLOBALS['cfg']['ShowServerInfo'] || empty($cfg['Server']['verbose'])) {
        $server_info .= $GLOBALS['dbi']->getHostInfo();
    }
    if (! empty($cfg['Server']['verbose']) && $GLOBALS['cfg']['ShowServerInfo']) {
        $server_info .= ')';
    }
    $mysql_cur_user_and_host = $GLOBALS['dbi']->fetchValue('SELECT USER();');

    // should we add the port info here?
    $short_server_info = (!empty($GLOBALS['cfg']['Server']['verbose'])
                ? $GLOBALS['cfg']['Server']['verbose']
                : $GLOBALS['cfg']['Server']['host']);
}

echo '<div id="maincontainer">' . "\n";
// Anchor for favorite tables synchronization.
echo PMA_RecentFavoriteTable::getInstance('favorite')->getHtmlSyncFavoriteTables();
echo '<div id="main_pane_left">';
if ($server > 0 || count($cfg['Servers']) > 1
) {
    if ($cfg['DBG']['demo']) {
        echo '<div class="group">';
        echo '<h2>' . __('phpMyAdmin Demo Server') . '</h2>';
        echo '<p style="margin: 0.5em 1em 0.5em 1em">';
        printf(
            __(
                'You are using the demo server. You can do anything here, but '
                . 'please do not change root, debian-sys-maint and pma users. '
                . 'More information is available at %s.'
            ),
            '<a href="http://demo.phpmyadmin.net/">demo.phpmyadmin.net</a>'
        );
        echo '</p>';
        echo '</div>';
    }
    echo '<div class="group">';
    echo '<h2>' . __('General Settings') . '</h2>';
    echo '<ul>';

    /**
     * Displays the MySQL servers choice form
     */
    if ($cfg['ServerDefault'] == 0
        || (! $cfg['NavigationDisplayServers']
        && (count($cfg['Servers']) > 1
        || ($server == 0 && count($cfg['Servers']) == 1)))
    ) {
        echo '<li id="li_select_server" class="no_bullets" >';
        include_once 'libraries/select_server.lib.php';
        echo PMA_Util::getImage('s_host.png') . " " . PMA_selectServer(true, true);
        echo '</li>';
    }

    /**
     * Displays the mysql server related links
     */
    if ($server > 0 && ! PMA_DRIZZLE) {
        include_once 'libraries/check_user_privileges.lib.php';

        // Logout for advanced authentication
        if ($cfg['Server']['auth_type'] != 'config') {
            if ($cfg['ShowChgPassword']) {
                $conditional_class = 'ajax';
                PMA_printListItem(
                    PMA_Util::getImage('s_passwd.png') . " " . __('Change password'),
                    'li_change_password',
                    'user_password.php?' . $common_url_query,
                    null,
                    null,
                    'change_password_anchor',
                    "no_bullets",
                    $conditional_class
                );
            }
        } // end if
        echo '    <li id="li_select_mysql_collation" class="no_bullets" >';
        echo '        <form method="post" action="index.php">' . "\n"
           . PMA_URL_getHiddenInputs(null, null, 4, 'collation_connection')
           . '            <label for="select_collation_connection">' . "\n"
           . '                ' . PMA_Util::getImage('s_asci.png') . " "
                               . __('Server connection collation') . "\n"
           // put the doc link in the form so that it appears on the same line
           . PMA_Util::showMySQLDocu('Charset-connection')
           . ': ' .  "\n"
           . '            </label>' . "\n"

           . PMA_generateCharsetDropdownBox(
               PMA_CSDROPDOWN_COLLATION,
               'collation_connection',
               'select_collation_connection',
               $collation_connection,
               true,
               4,
               true
           )
           . '        </form>' . "\n"
           . '    </li>' . "\n";
    } // end of if ($server > 0 && !PMA_DRIZZLE)
    echo '</ul>';
    echo '</div>';
}

echo '<div class="group">';
echo '<h2>' . __('Appearance Settings') . '</h2>';
echo '  <ul>';

// Displays language selection combo
if (empty($cfg['Lang']) && count($GLOBALS['available_languages']) > 1) {
    echo '<li id="li_select_lang" class="no_bullets">';
    include_once 'libraries/display_select_lang.lib.php';
    echo PMA_Util::getImage('s_lang.png') . " " . PMA_getLanguageSelectorHtml();
    echo '</li>';
}

// ThemeManager if available

if ($GLOBALS['cfg']['ThemeManager']) {
    echo '<li id="li_select_theme" class="no_bullets">';
    echo PMA_Util::getImage('s_theme.png') . " "
            .  $_SESSION['PMA_Theme_Manager']->getHtmlSelectBox();
    echo '</li>';
}
echo '<li id="li_select_fontsize">';
echo PMA_Config::getFontsizeForm();
echo '</li>';

echo '</ul>';

// User preferences

if ($server > 0) {
    echo '<ul>';
    PMA_printListItem(
        PMA_Util::getImage('b_tblops.png') . " " . __('More settings'),
        'li_user_preferences',
        'prefs_manage.php?' . $common_url_query,
        null,
        null,
        null,
        "no_bullets"
    );
    echo '</ul>';
}

echo '</div>';


echo '</div>';
echo '<div id="main_pane_right">';


if ($server > 0 && $GLOBALS['cfg']['ShowServerInfo']) {

    echo '<div class="group">';
    echo '<h2>' . __('Database server') . '</h2>';
    echo '<ul>' . "\n";
    PMA_printListItem(
        __('Server:') . ' ' . $server_info,
        'li_server_info'
    );
    PMA_printListItem(
        __('Server type:') . ' ' . PMA_Util::getServerType(),
        'li_server_type'
    );
    PMA_printListItem(
        __('Server version:')
        . ' '
        . PMA_MYSQL_STR_VERSION . ' - ' . PMA_MYSQL_VERSION_COMMENT,
        'li_server_version'
    );
    PMA_printListItem(
        __('Protocol version:') . ' ' . $GLOBALS['dbi']->getProtoInfo(),
        'li_mysql_proto'
    );
    PMA_printListItem(
        __('User:') . ' ' . htmlspecialchars($mysql_cur_user_and_host),
        'li_user_info'
    );

    echo '    <li id="li_select_mysql_charset">';
    echo '        ' . __('Server charset:') . ' '
       . '        <span lang="en" dir="ltr">';
    if (! PMA_DRIZZLE) {
        echo '           '
            . $mysql_charsets_descriptions[$mysql_charset_map['utf-8']];
    }
    echo '           (' . $mysql_charset_map['utf-8'] . ')'
       . '        </span>'
       . '    </li>'
       . '  </ul>'
       . ' </div>';
}

if ($GLOBALS['cfg']['ShowServerInfo'] || $GLOBALS['cfg']['ShowPhpInfo']) {
    echo '<div class="group">';
    echo '<h2>' . __('Web server') . '</h2>';
    echo '<ul>';
    if ($GLOBALS['cfg']['ShowServerInfo']) {
        PMA_printListItem($_SERVER['SERVER_SOFTWARE'], 'li_web_server_software');

        if ($server > 0) {
            $client_version_str = $GLOBALS['dbi']->getClientInfo();
            if (preg_match('#\d+\.\d+\.\d+#', $client_version_str)) {
                $client_version_str = 'libmysql - ' . $client_version_str;
            }
            PMA_printListItem(
                __('Database client version:') . ' ' . $client_version_str,
                'li_mysql_client_version'
            );

            $php_ext_string = __('PHP extension:') . ' ';
            if (PMA_DatabaseInterface::checkDbExtension('mysqli')) {
                $extension = 'mysqli';
            } else {
                $extension = 'mysql';
            }
            $php_ext_string  .= $extension . ' '
                . PMA_Util::showPHPDocu('book.' . $extension . '.php');

            PMA_printListItem(
                $php_ext_string,
                'li_used_php_extension'
            );
        }
    }

    if ($cfg['ShowPhpInfo']) {
        PMA_printListItem(
            __('Show PHP information'),
            'li_phpinfo',
            'phpinfo.php?' . $common_url_query,
            null,
            '_blank'
        );
    }
    echo '  </ul>';
    echo ' </div>';
}

echo '<div class="group pmagroup">';
echo '<h2>phpMyAdmin</h2>';
echo '<ul>';
$class = null;
// We rely on CSP to allow access to http://www.phpmyadmin.net, but IE lacks
// support here and does not allow request to http once using https.
if ($GLOBALS['cfg']['VersionCheck']
    && (! $GLOBALS['PMA_Config']->get('is_https') || PMA_USR_BROWSER_AGENT != 'IE')
) {
    $class = 'jsversioncheck';
}
PMA_printListItem(
    __('Version information:') . ' ' . PMA_VERSION,
    'li_pma_version',
    null,
    null,
    null,
    null,
    $class
);
PMA_printListItem(
    __('Documentation'),
    'li_pma_docs',
    PMA_Util::getDocuLink('index'),
    null,
    '_blank'
);
PMA_printListItem(
    __('Wiki'),
    'li_pma_wiki',
    PMA_linkURL('http://wiki.phpmyadmin.net/'),
    null,
    '_blank'
);

// does not work if no target specified, don't know why
PMA_printListItem(
    __('Official Homepage'),
    'li_pma_homepage',
    PMA_linkURL('http://www.phpMyAdmin.net/'),
    null,
    '_blank'
);
PMA_printListItem(
    __('Contribute'),
    'li_pma_contribute',
    PMA_linkURL('http://www.phpmyadmin.net/home_page/improve.php'),
    null,
    '_blank'
);
PMA_printListItem(
    __('Get support'),
    'li_pma_support',
    PMA_linkURL('http://www.phpmyadmin.net/home_page/support.php'),
    null,
    '_blank'
);
PMA_printListItem(
    __('List of changes'),
    'li_pma_changes',
    PMA_linkURL('changelog.php'),
    null,
    '_blank'
);
echo '    </ul>';
echo ' </div>';

echo '</div>';

echo '</div>';

/**
 * Warning if using the default MySQL privileged account
 */
if ($server != 0
    && $cfg['Server']['user'] == 'root'
    && $cfg['Server']['password'] == ''
) {
    trigger_error(
        __(
            'Your configuration file contains settings (root with no password)'
            . ' that correspond to the default MySQL privileged account.'
            . ' Your MySQL server is running with this default, is open to'
            . ' intrusion, and you really should fix this security hole by'
            . ' setting a password for user \'root\'.'
        ),
        E_USER_WARNING
    );
}

/**
 * As we try to handle charsets by ourself, mbstring overloads just
 * break it, see bug 1063821.
 */
if (@extension_loaded('mbstring') && @ini_get('mbstring.func_overload') > 1) {
    trigger_error(
        __(
            'You have enabled mbstring.func_overload in your PHP '
            . 'configuration. This option is incompatible with phpMyAdmin '
            . 'and might cause some data to be corrupted!'
        ),
        E_USER_WARNING
    );
}

/**
 * mbstring is used for handling multibyte inside parser, so it is good
 * to tell user something might be broken without it, see bug #1063149.
 */
if (! @extension_loaded('mbstring')) {
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
 * Check whether session.gc_maxlifetime limits session validity.
 */
$gc_time = (int)@ini_get('session.gc_maxlifetime');
if ($gc_time < $GLOBALS['cfg']['LoginCookieValidity'] ) {
    trigger_error(
        __('Your PHP parameter [a@http://php.net/manual/en/session.configuration.php#ini.session.gc-maxlifetime@_blank]session.gc_maxlifetime[/a] is lower than cookie validity configured in phpMyAdmin, because of this, your login will expire sooner than configured in phpMyAdmin.'),
        E_USER_WARNING
    );
}

/**
 * Check whether LoginCookieValidity is limited by LoginCookieStore.
 */
if ($GLOBALS['cfg']['LoginCookieStore'] != 0
    && $GLOBALS['cfg']['LoginCookieStore'] < $GLOBALS['cfg']['LoginCookieValidity']
) {
    trigger_error(
        __('Login cookie store is lower than cookie validity configured in phpMyAdmin, because of this, your login will expire sooner than configured in phpMyAdmin.'),
        E_USER_WARNING
    );
}

/**
 * Check if user does not have defined blowfish secret and it is being used.
 */
if (! empty($_SESSION['auto_blowfish_secret'])
    && empty($GLOBALS['cfg']['blowfish_secret'])
) {
    trigger_error(
        __('The configuration file now needs a secret passphrase (blowfish_secret).'),
        E_USER_WARNING
    );
}

/**
 * Check for existence of config directory which should not exist in
 * production environment.
 */
if (file_exists('config')) {
    trigger_error(
        __('Directory [code]config[/code], which is used by the setup script, still exists in your phpMyAdmin directory. It is strongly recommended to remove it once phpMyAdmin has been configured. Otherwise the security of your server may be compromised by unauthorized people downloading your configuration.'),
        E_USER_WARNING
    );
}

if ($server > 0) {
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['allworks']
        && $cfg['PmaNoRelation_DisableWarning'] == false
    ) {
        $msg = PMA_Message::notice(__('The phpMyAdmin configuration storage is not completely configured, some extended features have been deactivated. To find out why click %shere%s.'));
        $msg->addParam(
            '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?'
            . $common_url_query . '">',
            false
        );
        $msg->addParam('</a>', false);
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            $msg->isError(true);
        }
        $msg->display();
    } // end if
}

/**
 * Warning about different MySQL library and server version
 * (a difference on the third digit does not count).
 * If someday there is a constant that we can check about mysqlnd,
 * we can use it instead of strpos().
 * If no default server is set, $GLOBALS['dbi'] is not defined yet.
 * Drizzle can speak MySQL protocol, so don't warn about version mismatch for
 * Drizzle servers.
 */
if (isset($GLOBALS['dbi'])
    && !PMA_DRIZZLE
    && $cfg['ServerLibraryDifference_DisableWarning'] == false
) {
    $_client_info = $GLOBALS['dbi']->getClientInfo();
    if ($server > 0
        && strpos($_client_info, 'mysqlnd') === false
        && substr(PMA_MYSQL_CLIENT_API, 0, 3) != substr(PMA_MYSQL_INT_VERSION, 0, 3)
    ) {
        trigger_error(
            PMA_sanitize(
                sprintf(
                    __('Your PHP MySQL library version %s differs from your MySQL server version %s. This may cause unpredictable behavior.'),
                    $_client_info,
                    substr(
                        PMA_MYSQL_STR_VERSION,
                        0,
                        strpos(PMA_MYSQL_STR_VERSION . '-', '-')
                    )
                )
            ),
            E_USER_NOTICE
        );
    }
    unset($_client_info);
}

/**
 * Warning about Suhosin only if its simulation mode is not enabled
 */
if ($cfg['SuhosinDisableWarning'] == false
    && @ini_get('suhosin.request.max_value_length')
    && @ini_get('suhosin.simulation') == '0'
) {
    trigger_error(
        sprintf(
            __('Server running with Suhosin. Please refer to %sdocumentation%s for possible issues.'),
            '[doc@faq1-38]',
            '[/doc]'
        ),
        E_USER_WARNING
    );
}

/**
 * Warning about incomplete translations.
 *
 * The data file is created while creating release by ./scripts/remove-incomplete-mo
 */
if (file_exists('libraries/language_stats.inc.php')) {
    include 'libraries/language_stats.inc.php';
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
            . 'better by [a@http://www.phpmyadmin.net/home_page/improve.php'
            . '#translate@_blank]contributing[/a].',
            E_USER_NOTICE
        );
    }
}

/**
 * prints list item for main page
 *
 * @param string $name            displayed text
 * @param string $listId          id, used for css styles
 * @param string $url             make item as link with $url as target
 * @param string $mysql_help_page display a link to MySQL's manual
 * @param string $target          special target for $url
 * @param string $a_id            id for the anchor,
 *                                used for jQuery to hook in functions
 * @param string $class           class for the li element
 * @param string $a_class         class for the anchor element
 *
 * @return void
 */
function PMA_printListItem($name, $listId = null, $url = null,
    $mysql_help_page = null, $target = null, $a_id = null, $class = null,
    $a_class = null
) {
    echo '<li id="' . $listId . '"';
    if (null !== $class) {
        echo ' class="' . $class . '"';
    }
    echo '>';
    if (null !== $url) {
        echo '<a href="' . $url . '"';
        if (null !== $target) {
            echo ' target="' . $target . '"';
        }
        if (null != $a_id) {
            echo ' id="' . $a_id . '"';
        }
        if (null != $a_class) {
            echo ' class="' . $a_class . '"';
        }
        echo '>';
    }

    echo $name;

    if (null !== $url) {
        echo '</a>' . "\n";
    }
    if (null !== $mysql_help_page) {
        echo PMA_Util::showMySQLDocu($mysql_help_page);
    }
    echo '</li>';
}
?>
