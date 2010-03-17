<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
define('PMA_MOORAINBOW', true);
require_once './libraries/common.inc.php';
$GLOBALS['js_include'][] = 'mootools.js';
$GLOBALS['js_include'][] = 'mooRainbow/mooRainbow.js';
$GLOBALS['js_include'][] = 'mootools-domready-rainbow.js';

// Handles some variables that may have been sent by the calling script
$GLOBALS['db'] = '';
$GLOBALS['table'] = '';
$show_query = '1';
require_once './libraries/header.inc.php';

// Any message to display?
if (! empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

$common_url_query =  PMA_generate_common_url('', '');

// when $server > 0, a server has been chosen so we can display
// all MySQL-related information
if ($server > 0) {
    require './libraries/server_common.inc.php';
    require './libraries/StorageEngine.class.php';
    require './libraries/server_links.inc.php';

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
        $server_info .= PMA_DBI_get_host_info();
    }
    if (! empty($cfg['Server']['verbose']) && $GLOBALS['cfg']['ShowServerInfo']) {
    $server_info .= ')';
    }
    $mysql_cur_user_and_host = PMA_DBI_fetch_value('SELECT USER();');

    // should we add the port info here?
    $short_server_info = (!empty($GLOBALS['cfg']['Server']['verbose'])
                    ? $GLOBALS['cfg']['Server']['verbose']
                    : $GLOBALS['cfg']['Server']['host']);
}

echo '<div id="maincontainer">' . "\n";
echo '<div id="main_pane_left">';

if ($server > 0
 || (! $cfg['LeftDisplayServers'] && count($cfg['Servers']) > 1)) {
    echo '<div class="group">';
    echo '<h2>' . $strActions . '</h2>';
    echo '<ul>';

    /**
     * Displays the MySQL servers choice form
     */
    if (! $cfg['LeftDisplayServers']
     && (count($cfg['Servers']) > 1 || $server == 0 && count($cfg['Servers']) == 1)) {
        echo '<li id="li_select_server">';
        require_once './libraries/select_server.lib.php';
        PMA_select_server(true, true);
        echo '</li>';
    }

    /**
     * Displays the mysql server related links
     */
    if ($server > 0) {
        require_once './libraries/check_user_privileges.lib.php';

        // Logout for advanced authentication
        if ($cfg['Server']['auth_type'] != 'config') {
            if ($cfg['ShowChgPassword']) {
                PMA_printListItem($strChangePassword, 'li_change_password',
                    './user_password.php?' . $common_url_query);
            }

            $http_logout = ($cfg['Server']['auth_type'] == 'http')
                         ? '<a href="./Documentation.html#login_bug" target="documentation">'
                            . ($cfg['ReplaceHelpImg'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_info.png" width="11" height="11" alt="Info" />' : '(*)') . '</a>'
                         : '';
            PMA_printListItem('<strong>' . $strLogout . '</strong> ' . $http_logout,
                'li_log_out',
                './index.php?' . $common_url_query . '&amp;old_usr=' . urlencode($PHP_AUTH_USER), null, '_parent');
        } // end if
    } // end of if ($server > 0)

    echo '</ul>';
    echo '</div>';
}


if ($server > 0) {
    echo '<div class="group">';
    echo '<h2>MySQL ' . $short_server_info . '</h2>';
    echo '<ul>' . "\n";

    if ($cfg['ShowCreateDb']) {
        echo '<li id="li_create_database">';
        require './libraries/display_create_database.lib.php';
        echo '</li>' . "\n";
    }

    echo '    <li id="li_select_mysql_collation">';
    echo '        <form method="post" action="index.php" target="_parent">' . "\n"
       . PMA_generate_common_hidden_inputs(null, null, 4, 'collation_connection')
       . '            <label for="select_collation_connection">' . "\n"
       . '                ' . $strMySQLConnectionCollation . ': ' . "\n"
       . '            </label>' . "\n"
       . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'collation_connection', 'select_collation_connection', $collation_connection, true, 4, true)
       . '            <noscript><input type="submit" value="' . $strGo . '" /></noscript>' . "\n"
       // put the doc link in the form so that it appears on the same line
       . PMA_showMySQLDocu('MySQL_Database_Administration', 'Charset-connection') . "\n"
       . '        </form>' . "\n"
       . '    </li>' . "\n";

    echo '  </ul>';
    echo ' </div>';
}

echo '<div class="group">';
echo '<h2>' . $strInterface . '</h2>';
echo '  <ul>';

// Displays language selection combo
if (empty($cfg['Lang'])) {
    echo '<li id="li_select_lang">';
    require_once './libraries/display_select_lang.lib.php';
    PMA_select_language();
    echo '</li>';
}

// added by Michael Keck <mail_at_michaelkeck_dot_de>
// ThemeManager if available

if ($GLOBALS['cfg']['ThemeManager']) {
    echo '<li id="li_select_theme">';
    echo $_SESSION['PMA_Theme_Manager']->getHtmlSelectBox();
    echo '</li>';
?>
    <script type="text/javascript">
    //<![CDATA[
    document.write('<li id="li_custom_color">');
    document.write('<?php echo PMA_escapeJsString($strCustomColor) . ': '; ?>');
    document.write('<img id="myRainbow" src="js/mooRainbow/images/rainbow.png" alt="[r]" width="16" height="16" />');
    document.write('<form name="rainbowform" id="rainbowform" method="post" action="index.php" target="_parent">');
    document.write('<?php echo PMA_generate_common_hidden_inputs(); ?>');
    document.write('<input type="hidden" name="custom_color" />');
    document.write('<input type="hidden" name="custom_color_rgb" />');
    document.write('<input type="submit" name="custom_color_reset" value="<?php echo $strReset; ?>" />');
    document.write('</form>');
    document.write('</li>');
    //]]>
    </script>
    <?php
}
echo '<li id="li_select_fontsize">';
echo PMA_Config::getFontsizeForm();
echo '</li>';

echo '</ul>';
echo '</div>';


echo '</div>';
echo '<div id="main_pane_right">';


if ($server > 0 && $GLOBALS['cfg']['ShowServerInfo']) {
    echo '<div class="group">';
    echo '<h2>MySQL</h2>';
    echo '<ul>' . "\n";
    PMA_printListItem($strServer . ': ' . $server_info, 'li_server_info');
    PMA_printListItem($strServerVersion . ': ' . PMA_MYSQL_STR_VERSION, 'li_server_version');
    PMA_printListItem($strProtocolVersion . ': ' . PMA_DBI_get_proto_info(),
        'li_mysql_proto');
    PMA_printListItem($strUser . ': ' . htmlspecialchars($mysql_cur_user_and_host),
        'li_user_info');

    echo '    <li id="li_select_mysql_charset">';
    echo '        ' . $strMySQLCharset . ': '
       . '        <span xml:lang="en" dir="ltr">'
       . '           ' . $mysql_charsets_descriptions[$mysql_charset_map[strtolower($charset)]] . "\n"
       . '           (' . $mysql_charset_map[strtolower($charset)] . ')' . "\n"
       . '        </span>' . "\n"
       . '    </li>' . "\n";
    echo '  </ul>';
    echo ' </div>';
}

if ($GLOBALS['cfg']['ShowServerInfo'] || $GLOBALS['cfg']['ShowPhpInfo']) {
    echo '<div class="group">';
    echo '<h2>' . $strWebServer . '</h2>';
    echo '<ul>';
    if ($GLOBALS['cfg']['ShowServerInfo']) {
        PMA_printListItem($_SERVER['SERVER_SOFTWARE'], 'li_web_server_software');

        if ($server > 0) {
            PMA_printListItem($strMysqlClientVersion . ': ' . PMA_DBI_get_client_info(),
                'li_mysql_client_version');
            PMA_printListItem($strPHPExtension . ': ' . $GLOBALS['cfg']['Server']['extension'],
                'li_used_php_extension');
        }
    }

    if ($cfg['ShowPhpInfo']) {
        PMA_printListItem($strShowPHPInfo, 'li_phpinfo', './phpinfo.php?' . $common_url_query);
    }
    echo '  </ul>';
    echo ' </div>';
}

echo '<div class="group">';
echo '<h2>phpMyAdmin</h2>';
echo '<ul>';
PMA_printListItem($strVersionInformation . ': ' . PMA_VERSION, 'li_pma_version');
PMA_printListItem($strDocu, 'li_pma_docs', 'Documentation.html', null, '_blank');
PMA_printListItem($strWiki, 'li_pma_wiki', 'http://wiki.phpmyadmin.net', null, '_blank');

// does not work if no target specified, don't know why
PMA_printListItem($strHomepageOfficial, 'li_pma_homepage', 'http://www.phpMyAdmin.net/', null, '_blank');
?>
    <li><bdo xml:lang="en" dir="ltr">
        [<a href="changelog.php" target="_blank">ChangeLog</a>]
        [<a href="http://phpmyadmin.git.sourceforge.net/git/gitweb-index.cgi"
            target="_blank">Git</a>]
        [<a href="http://sourceforge.net/mail/?group_id=23067"
            target="_blank">Lists</a>]
        </bdo>
    </li>
    </ul>
 </div>

</div>

<?php
/**
 * BUG: MSIE needs two <br /> here, otherwise it will not extend the outer div to the
 * full height of the inner divs
 */
?>
<br class="clearfloat" />
<br class="clearfloat" />
</div>

<?php
/**
 * Warning if using the default MySQL privileged account
 * modified: 2004-05-05 mkkeck
 */
if ($server != 0
 && $cfg['Server']['user'] == 'root'
 && $cfg['Server']['password'] == '') {
    trigger_error($strInsecureMySQL, E_USER_WARNING);
}

/**
 * Nijel: As we try to handle charsets by ourself, mbstring overloads just
 * break it, see bug 1063821.
 */
if (@extension_loaded('mbstring') && @ini_get('mbstring.func_overload') > 1) {
    trigger_error($strMbOverloadWarning, E_USER_WARNING);
}

/**
 * Nijel: mbstring is used for handling multibyte inside parser, so it is good
 * to tell user something might be broken without it, see bug #1063149.
 */
if (! @extension_loaded('mbstring')) {
    trigger_error($strMbExtensionMissing, E_USER_WARNING);
}

/**
 * Check whether session.gc_maxlifetime limits session validity.
 */
$gc_time = (int)@ini_get('session.gc_maxlifetime');
if ($gc_time < $GLOBALS['cfg']['LoginCookieValidity'] ) {
    trigger_error(PMA_Message::decodeBB($strSessionGCWarning), E_USER_WARNING);
}

/**
 * Check if user does not have defined blowfish secret and it is being used.
 */
if (!empty($_SESSION['auto_blowfish_secret']) &&
        empty($GLOBALS['cfg']['blowfish_secret'])) {
    trigger_error($strSecretRequired, E_USER_WARNING);
}

/**
 * Check for existence of config directory which should not exist in
 * production environment.
 */
if (file_exists('./config')) {
    trigger_error($strConfigDirectoryWarning, E_USER_WARNING);
}

/**
 * Check whether relations are supported.
 */
if ($server > 0) {
    require_once './libraries/relation.lib.php';
    $cfgRelation = PMA_getRelationsParam();
    if(!$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == false) {
        $message = PMA_Message::notice('strRelationNotWorking');
        $message->addParam('<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $common_url_query . '">', false);
        $message->addParam('</a>', false);
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            $message->isError(true);
        }
        $message->display();
    } // end if
}

/**
 * Warning about different MySQL library and server version
 * (a difference on the third digit does not count).
 * If someday there is a constant that we can check about mysqlnd, we can use it instead
 * of strpos().
 * If no default server is set, PMA_DBI_get_client_info() is not defined yet.
 */
if (function_exists('PMA_DBI_get_client_info')) {
    $_client_info = PMA_DBI_get_client_info();
    if ($server > 0 && strpos($_client_info, 'mysqlnd') === false && substr(PMA_MYSQL_CLIENT_API, 0, 3) != substr(PMA_MYSQL_INT_VERSION, 0, 3)) {
        trigger_error(PMA_sanitize(sprintf($strMysqlLibDiffersServerVersion,
                $_client_info,
                substr(PMA_MYSQL_STR_VERSION, 0, strpos(PMA_MYSQL_STR_VERSION . '-', '-')))),
            E_USER_NOTICE);
    }
    unset($_client_info);
}

/**
 * Warning about Suhosin
 */
if ($cfg['SuhosinDisableWarning'] == false && @ini_get('suhosin.request.max_value_length')) {
    trigger_error(PMA_sanitize(sprintf($strSuhosin, '[a@./Documentation.html#faq1_38@_blank]', '[/a]')), E_USER_WARNING);
    }

/**
 * prints list item for main page
 *
 * @param   string  $name   displayed text
 * @param   string  $id     id, used for css styles
 * @param   string  $url    make item as link with $url as target
 * @param   string  $mysql_help_page  display a link to MySQL's manual
 * @param   string  $target special target for $url
 */
function PMA_printListItem($name, $id = null, $url = null, $mysql_help_page = null, $target = null)
{
    echo '<li id="' . $id . '">';
    if (null !== $url) {
        echo '<a href="' . $url . '"';
        if (null !== $target) {
           echo ' target="' . $target . '"';
        }
        echo '>';
    }

    echo $name;

    if (null !== $url) {
        echo '</a>' . "\n";
    }
    if (null !== $mysql_help_page) {
        echo PMA_showMySQLDocu('', $mysql_help_page);
    }
    echo '</li>';
}

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
