<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Don't display the page heading
 */
if (!defined('PMA_DISPLAY_HEADING')) {
    define('PMA_DISPLAY_HEADING', 0);
}

/**
 * Gets some core libraries and displays a top message if required
 */
require_once './libraries/common.inc.php';

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

// this div is required for containing divs can be 50%
echo '<div id="maincontainer">' . "\n";

/**
 * Displays the mysql server related links
 */
if ($server > 0) {

    require_once './libraries/check_user_privileges.lib.php';
    // why this? a non-priv user should be able to change his
    // password if the configuration permits
    //$cfg['ShowChgPassword'] = $is_superuser = PMA_isSuperuser();
    $is_superuser = PMA_isSuperuser();

    if ($cfg['Server']['auth_type'] == 'config') {
        $cfg['ShowChgPassword'] = false;
    }
}
?>

    <div id="mysqlmaininformation">
<?php
if ($server > 0) {
    // robbat2: Use the verbose name of the server instead of the hostname
    //          if a value is set
    $server_info = '';
    if (!empty($cfg['Server']['verbose'])) {
        $server_info .= htmlspecialchars($cfg['Server']['verbose']);
        if ($GLOBALS['cfg']['ShowServerInfo']) {
            $server_info .= ' (';
        }
    }
    if ($GLOBALS['cfg']['ShowServerInfo'] || empty($cfg['Server']['verbose'])) {
        $server_info .= PMA_DBI_get_host_info();
    }

    if (!empty($cfg['Server']['verbose']) && $GLOBALS['cfg']['ShowServerInfo']) {
        $server_info .= ')';
    }
    // loic1: skip this because it's not a so good idea to display sockets
    //        used to everybody
    // if (!empty($cfg['Server']['socket']) && PMA_PHP_INT_VERSION >= 30010) {
    //     $server_info .= ':' . $cfg['Server']['socket'];
    // }
    $mysql_cur_user_and_host = PMA_DBI_fetch_value('SELECT USER();');


    // should we add the port info here?
    $short_server_info = (!empty($GLOBALS['cfg']['Server']['verbose'])
                        ? $GLOBALS['cfg']['Server']['verbose']
                        : $GLOBALS['cfg']['Server']['host']);
    echo '<h1 xml:lang="en" dir="ltr">' . $short_server_info .'</h1>' . "\n";
    unset($short_server_info);
} else {
    // Case when no server selected
    //echo '<h1 xml:lang="en" dir="ltr">MySQL</h1>' . "\n";
}

if ($server > 0) {
    echo '<ul>' . "\n";

    if ($GLOBALS['cfg']['ShowServerInfo']) {
        PMA_printListItem($strServerVersion . ': ' . PMA_MYSQL_STR_VERSION, 'li_server_info');
        PMA_printListItem($strProtocolVersion . ': ' . PMA_DBI_get_proto_info(),
            'li_mysql_proto');
    /**
     * @todo tweak the CSS to use same image as li_server_info 
     */
        PMA_printListItem($strServer . ': ' . $server_info, 'li_server_info2');
        PMA_printListItem($strUser . ': ' . htmlspecialchars($mysql_cur_user_and_host),
            'li_user_info');
    } else {
        PMA_printListItem($strServerVersion . ': ' . PMA_MYSQL_STR_VERSION, 'li_server_info');
        PMA_printListItem($strServer . ': ' . $server_info, 'li_server_info2');
    }

    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding && PMA_MYSQL_INT_VERSION < 40100) {
        echo '<li id="li_select_mysql_charset">';
        ?>
            <form method="post" action="index.php" target="_parent">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <?php echo $strMySQLCharset;?>:
            <select name="convcharset"  xml:lang="en" dir="ltr"
                onchange="this.form.submit();">
        <?php
        foreach ($cfg['AvailableCharsets'] as $tmpcharset) {
            if ($convcharset == $tmpcharset) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            echo '            '
               . '<option value="' . $tmpcharset . '"' . $selected . '>' . $tmpcharset . '</option>' . "\n";
        }
        ?>
            </select>
            <noscript><input type="submit" value="<?php echo $strGo;?>" /></noscript>
            </form>
        </li>
        <?php
    } elseif (PMA_MYSQL_INT_VERSION >= 40100) {
        echo '    <li id="li_select_mysql_charset">';
        echo '        ' . $strMySQLCharset . ': '
           . '        <strong xml:lang="en" dir="ltr">'
           . '           ' . $mysql_charsets_descriptions[$mysql_charset_map[strtolower($charset)]] . "\n"
           . '           (' . $mysql_charset_map[strtolower($charset)] . ')' . "\n"
           . '        </strong>' . "\n"
           . '    </li>' . "\n"
           . '    <li id="li_select_mysql_collation">';
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
    }

    if ($cfg['ShowCreateDb']) {
        echo '<li id="li_create_database">';
        require './libraries/display_create_database.lib.php';
        echo '</li>' . "\n";
    }

    PMA_printListItem($strMySQLShowStatus, 'li_mysql_status',
        './server_status.php?' . $common_url_query);
    PMA_printListItem($strMySQLShowVars, 'li_mysql_variables',
        './server_variables.php?' . $common_url_query, 'show-variables');
    PMA_printListItem($strProcesses, 'li_mysql_processes',
        './server_processlist.php?' . $common_url_query, 'show-processlist');

    if (PMA_MYSQL_INT_VERSION >= 40100) {
        PMA_printListItem($strCharsetsAndCollations, 'li_mysql_collations',
            './server_collations.php?' . $common_url_query);
    }

    PMA_printListItem($strStorageEngines, 'li_mysql_engines',
        './server_engines.php?' . $common_url_query);

    if ($is_reload_priv) {
        PMA_printListItem($strReloadPrivileges, 'li_flush_privileges',
            './server_privileges.php?flush_privileges=1&amp;' . $common_url_query, 'flush');
    }

    if ($is_superuser) {
        PMA_printListItem($strPrivileges, 'li_mysql_privilegs',
            './server_privileges.php?' . $common_url_query);
    }

    $binlogs = PMA_DBI_try_query('SHOW MASTER LOGS', null, PMA_DBI_QUERY_STORE);
    if ($binlogs) {
        if (PMA_DBI_num_rows($binlogs) > 0) {
            PMA_printListItem($strBinaryLog, 'li_mysql_binlogs',
                './server_binlog.php?' . $common_url_query);
        }
        PMA_DBI_free_result($binlogs);
    }
    unset($binlogs);

    PMA_printListItem($strDatabases, 'li_mysql_databases',
        './server_databases.php?' . $common_url_query);
    PMA_printListItem($strExport, 'li_export',
        './server_export.php?' . $common_url_query);
    PMA_printListItem($strImport, 'li_import',
        './server_import.php?' . $common_url_query);

    /**
     * Change password
     *
     * @todo ? needs another message
     */
    if ($cfg['ShowChgPassword']) {
        PMA_printListItem($strChangePassword, 'li_change_password',
            './user_password.php?' . $common_url_query);
    } // end if

    // Logout for advanced authentication
    if ($cfg['Server']['auth_type'] != 'config') {
        $http_logout = ($cfg['Server']['auth_type'] == 'http')
                     ? '<a href="./Documentation.html#login_bug" target="documentation">'
                        . ($cfg['ReplaceHelpImg'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_info.png" width="11" height="11" alt="Info" />' : '(*)') . '</a>'
                     : '';
        PMA_printListItem('<strong>' . $strLogout . '</strong> ' . $http_logout,
            'li_log_out',
            './index.php?' . $common_url_query . '&amp;old_usr=' . urlencode($PHP_AUTH_USER), null, '_parent');
    } // end if

    echo '</ul>';
} // end of if ($server > 0)
?>
</div>
<div id="pmamaininformation">
<?php

echo '<h1 xml:lang="en" dir="ltr">phpMyAdmin - ' . PMA_VERSION . '</h1>'
    . "\n";

echo '<ul>' . "\n";

/**
 * Displays the MySQL servers choice form
 */
if (!$cfg['LeftDisplayServers'] && (count($cfg['Servers']) > 1 || $server == 0 && count($cfg['Servers']) == 1)) {
    echo '<li id="li_select_server">';
    require_once './libraries/select_server.lib.php';
    PMA_select_server(true, true);
    echo '</li>';
}

if ($server > 0) {
    PMA_printListItem($strMysqlClientVersion . ': ' . PMA_DBI_get_client_info(),
        'li_mysql_client_version');
    PMA_printListItem($strUsedPhpExtensions . ': ' . $GLOBALS['cfg']['Server']['extension'],
        'li_used_php_extension');
}

// Displays language selection combo
if (empty($cfg['Lang'])) {
    echo '<li id="li_select_lang">';
    require_once './libraries/display_select_lang.lib.php';
    PMA_select_language();
    echo '</li>';
}


if (isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding']
  && $server != 0 && $allow_recoding && PMA_MYSQL_INT_VERSION < 40100) {
    echo '<li id="li_select_charset">';
    ?>
    <form method="post" action="index.php" target="_parent">
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <?php echo $strMySQLCharset;?>:
    <select name="convcharset"  xml:lang="en" dir="ltr"
        onchange="this.form.submit();">
    <?php
    foreach ($cfg['AvailableCharsets'] AS $id => $tmpcharset) {
        if ($convcharset == $tmpcharset) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        echo '        '
           . '<option value="' . $tmpcharset . '"' . $selected . '>' . $tmpcharset . '</option>' . "\n";
    }
    ?>
    </select>
    <noscript><input type="submit" value="<?php echo $strGo;?>" /></noscript>
    </form>
    </li>
    <?php
}

// added by Michael Keck <mail_at_michaelkeck_dot_de>
// ThemeManager if available

if ($GLOBALS['cfg']['ThemeManager']) {
    echo '<li id="li_select_theme">';
    echo $_SESSION['PMA_Theme_Manager']->getHtmlSelectBox();
    echo '</li>';
}
echo '<li id="li_select_fontsize">';
echo PMA_Config::getFontsizeForm();
echo '</li>';
PMA_printListItem($strPmaDocumentation, 'li_pma_docs', 'Documentation.html', null, '_blank');
PMA_printListItem($strPmaWiki, 'li_pma_docs2', 'http://wiki.phpmyadmin.net', null, '_blank');

if ($cfg['ShowPhpInfo']) {
    PMA_printListItem($strShowPHPInfo, 'li_phpinfo', './phpinfo.php?' . $common_url_query);
}

// does not work if no target specified, don't know why
PMA_printListItem($strHomepageOfficial, 'li_pma_homepage', 'http://www.phpMyAdmin.net/', null, '_blank');
?>
    <li><bdo xml:lang="en" dir="ltr">
        [<a href="changelog.php" target="_blank">ChangeLog</a>]
        [<a href="http://phpmyadmin.svn.sourceforge.net/viewvc/phpmyadmin/"
            target="_blank">Subversion</a>]
        [<a href="http://sourceforge.net/mail/?group_id=23067"
            target="_blank">Lists</a>]
        </bdo>
    </li>
    </ul>
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
if (! empty($GLOBALS['PMA_errors']) && is_array($GLOBALS['PMA_errors'])) {
    foreach ($GLOBALS['PMA_errors'] as $error) {
        echo '<div class="error">' . $error . '</div>' . "\n";
    }
}

/**
 * Warning if using the default MySQL privileged account
 * modified: 2004-05-05 mkkeck
 */
if ($server != 0
 && $cfg['Server']['user'] == 'root'
 && $cfg['Server']['password'] == '') {
    echo '<div class="warning">' . $strInsecureMySQL . '</div>' . "\n";
}

/**
 * Warning for PHP 4.2.3
 * modified: 2004-05-05 mkkeck
 */
if (PMA_PHP_INT_VERSION == 40203 && @extension_loaded('mbstring')) {
    echo '<div class="warning">' . $strPHP40203 . '</div>' . "\n";
}

/**
 * Nijel: As we try to handle charsets by ourself, mbstring overloads just
 * break it, see bug 1063821.
 */
if (@extension_loaded('mbstring') && @ini_get('mbstring.func_overload') > 1) {
    echo '<div class="warning">' . $strMbOverloadWarning . '</div>' . "\n";
}

/**
 * Nijel: mbstring is used for handling multibyte inside parser, so it is good
 * to tell user something might be broken without it, see bug #1063149.
 */
if ($GLOBALS['using_mb_charset'] && !@extension_loaded('mbstring')) {
    echo '<div class="warning">' . $strMbExtensionMissing . '</div>' . "\n";
}

/**
 * Warning for old PHP version
 */
if (PMA_PHP_INT_VERSION < 40200) {
    echo '<div class="warning">' . sprintf($strUpgrade, 'PHP', '4.2.0') . '</div>' . "\n";
}

/**
 * Warning for old MySQL version
 * modified: 2004-05-05 mkkeck
 */
// not yet defined before the server choice
if (defined('PMA_MYSQL_INT_VERSION') && PMA_MYSQL_INT_VERSION < 32332) {
    echo '<div class="warning">' . sprintf($strUpgrade, 'MySQL', '3.23.32') . '</div>' . "\n";
}

/**
 * Warning about different MySQL library and server version
 * (a difference on the third digit does not count)
 */
if ($server > 0 && substr(PMA_MYSQL_CLIENT_API, 0, 3) != substr(PMA_MYSQL_INT_VERSION, 0, 3)) {
    echo '<div class="notice">'
     . PMA_sanitize(sprintf($strMysqlLibDiffersServerVersion,
            PMA_DBI_get_client_info(),
            substr(PMA_MYSQL_STR_VERSION, 0, strpos(PMA_MYSQL_STR_VERSION . '-', '-'))))
     . '</div>' . "\n";
}

/**
 * Warning about wrong controluser settings
 */
if (defined('PMA_DBI_CONNECT_FAILED_CONTROLUSER')) {
    echo '<div class="warning">' . $strControluserFailed . '</div>' . "\n";
}

/**
 * Warning about missing mcrypt extension 
 */
if (defined('PMA_WARN_FOR_MCRYPT')) {
    echo '<div class="warning">' . PMA_sanitize(sprintf($strCantLoad, 'mcrypt')) . '</div>' . "\n";
}

/**
 * Warning about Suhosin 
 */
if ($cfg['SuhosinDisableWarning'] == false && @ini_get('suhosin.request.max_value_length')) {
    echo '<div class="warning">' . PMA_sanitize(sprintf($strSuhosin, '[a@./Documentation.html#faq1_38@_blank]', '[/a]')) . '</div>' . "\n";
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
