<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Don't display the page heading
 */
define('PMA_DISPLAY_HEADING', 0);

/**
 * Gets some core libraries and displays a top message if required
 */
require_once('./libraries/common.lib.php');
setcookie('pma_lang', $lang, time() + 60*60*24*30, $cookie_path, '', $is_https);
if (isset($convcharset)) {
    setcookie('pma_charset', $convcharset, time() + 60*60*24*30, $cookie_path, '', $is_https);
}

/**
 * Includes the ThemeManager
 */
require_once('./libraries/select_theme.lib.php');

// Handles some variables that may have been sent by the calling script
if (isset($db)) {
    unset($db);
}
if (isset($table)) {
    unset($table);
}
$show_query = '1';
require_once('./libraries/header.inc.php');

// Any message to display?
if ( ! empty( $message ) ) {
    PMA_showMessage( $message );
    unset( $message );
}

/**
 * Displays the MySQL servers choice form
 */
if (!$cfg['LeftDisplayServers'] && count($cfg['Servers']) > 1) {
    include('./libraries/select_server.lib.php');
    PMA_select_server(TRUE, FALSE);
}

$common_url_query =  PMA_generate_common_url( '', '' );

// this div is required for containing divs can be 50%
echo '<div id="maincontainer">' . "\n";

/**
 * Displays the mysql server related links
 */
if ( $server > 0 ) {

    require_once('./libraries/check_user_privileges.lib.php');
    $cfg['ShowChgPassword'] = $is_superuser = PMA_isSuperuser();


    if ($cfg['Server']['auth_type'] == 'config') {
        $cfg['ShowChgPassword'] = FALSE;
    }

    ?>
    <div id="mysqlmaininformation">
    <?php
    // robbat2: Use the verbose name of the server instead of the hostname
    //          if a value is set
    $server_info = '';
    if (!empty($cfg['Server']['verbose'])) {
        $server_info .= $cfg['Server']['verbose'];
        $server_info .= ' (';
    }
    $server_info .= PMA_DBI_get_host_info();

    if (!empty($cfg['Server']['verbose'])) {
        $server_info .= ')';
    }
    // loic1: skip this because it's not a so good idea to display sockets
    //        used to everybody
    // if (!empty($cfg['Server']['socket']) && PMA_PHP_INT_VERSION >= 30010) {
    //     $server_info .= ':' . $cfg['Server']['socket'];
    // }
    $mysql_cur_user_and_host = PMA_DBI_fetch_value('SELECT USER();');

    echo '<h1 xml:lang="en" dir="ltr">MySQL - ' . PMA_MYSQL_STR_VERSION
        .'</h1>' . "\n";

    echo '<ul>' . "\n";

    PMA_printListItem( $strProtocolVersion . ': ' . PMA_DBI_get_proto_info()
        , 'li_mysql_proto' );
    PMA_printListItem( $strServer . ': ' . $server_info, 'li_server_info' );
    PMA_printListItem( $strUser . ': ' . htmlspecialchars( $mysql_cur_user_and_host ),
        'li_user_info' );

    if ( $cfg['AllowAnywhereRecoding'] && $allow_recoding && PMA_MYSQL_INT_VERSION < 40100) {
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
    } elseif ( PMA_MYSQL_INT_VERSION >= 40100 ) {
        echo '    <li id="li_select_mysql_charset">';
        echo '        ' . $strMySQLCharset . ': '
           . '        <strong xml:lang="en" dir="ltr">'
           . '           ' . $mysql_charsets_descriptions[$mysql_charset_map[strtolower($charset)]] . "\n"
           . '           (' . $mysql_charset_map[strtolower($charset)] . ')' . "\n"
           . '        </strong>' . "\n"
           . '    </li>' . "\n"
           . '    <li id="li_select_mysql_collation">';
        echo '        <form method="post" action="index.php" target="_parent">' . "\n"
           . PMA_generate_common_hidden_inputs(NULL, NULL, 4, 'collation_connection')
           . '            <label for="select_collation_connection">' . "\n"
           . '                ' . $strMySQLConnectionCollation . ': ' . "\n"
           . '            </label>' . "\n"
           . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'collation_connection', 'select_collation_connection', $collation_connection, TRUE, 4, TRUE)
           . '            <noscript><input type="submit" value="' . $strGo . '" /></noscript>' . "\n"
           // put the doc link in the form so that it appears on the same line
           . PMA_showMySQLDocu('MySQL_Database_Administration', 'Charset-connection') . "\n"
           . '        </form>' . "\n"
           . '    </li>' . "\n";
    }

    echo '<li id="li_create_database">';
    require('./libraries/display_create_database.lib.php');
    echo '</li>' . "\n";

    PMA_printListItem( $strMySQLShowStatus, 'li_mysql_status',
        './server_status.php?' . $common_url_query );
    PMA_printListItem( $strMySQLShowVars, 'li_mysql_variables',
        './server_variables.php?' . $common_url_query, 'show-variables' );
    PMA_printListItem( $strMySQLShowProcess, 'li_mysql_processes',
        './server_processlist.php?' . $common_url_query, 'show-processlist' );

    if (PMA_MYSQL_INT_VERSION >= 40100) {
        PMA_printListItem( $strCharsetsAndCollations, 'li_mysql_collations',
            './server_collations.php?' . $common_url_query );
    }

    PMA_printListItem( $strStorageEngines, 'li_mysql_engines',
        './server_engines.php?' . $common_url_query );

    PMA_printListItem( $strReloadMySQL, 'li_flush_privileges',
        './server_privileges.php?flush_privileges=1&amp;' . $common_url_query,
        'flush' );

    if ($is_superuser) {
        PMA_printListItem( $strPrivileges, 'li_mysql_privilegs',
            './server_privileges.php?' . $common_url_query );
    }

    $binlogs = PMA_DBI_try_query('SHOW MASTER LOGS', NULL, PMA_DBI_QUERY_STORE);
    if ( $binlogs ) {
        if (PMA_DBI_num_rows($binlogs) > 0) {
            PMA_printListItem( $strBinaryLog, 'li_mysql_binlogs',
                './server_binlog.php?' . $common_url_query );
        }
        PMA_DBI_free_result($binlogs);
    }
    unset( $binlogs );

    PMA_printListItem( $strDatabases, 'li_mysql_databases',
        './server_databases.php?' . $common_url_query );
    PMA_printListItem( $strExport, 'li_export',
        './server_export.php?' . $common_url_query );
    PMA_printListItem( $strImport, 'li_import',
        './server_import.php?' . $common_url_query );

    // Change password (TODO ? needs another message)
    if ($cfg['ShowChgPassword']) {
        PMA_printListItem( $strChangePassword, 'li_change_password',
            './user_password.php?' . $common_url_query );
    } // end if

    // Logout for advanced authentication
    if ($cfg['Server']['auth_type'] != 'config') {
        $http_logout = ($cfg['Server']['auth_type'] == 'http')
                     ? '<a href="./Documentation.html#login_bug" target="documentation">'
                        . ($cfg['ReplaceHelpImg'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_info.png" width="11" height="11" alt="Info" />' : '(*)') . '</a>'
                     : '';
        PMA_printListItem( '<strong>' . $strLogout . '</strong> ' . $http_logout,
            'li_log_out',
            './user_password.php?' . $common_url_query . '&amp;old_usr=' . urlencode($PHP_AUTH_USER) );
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

PMA_printListItem( $strMysqlClientVersion . ': ' . PMA_DBI_get_client_info(),
    'li_mysql_client_version' );
PMA_printListItem( $strUsedPhpExtension . ': ' . $GLOBALS['cfg']['Server']['extension'],
    'li_used_php_extension' );

// Displays language selection combo
if (empty($cfg['Lang'])) {
    echo '<li id="li_select_lang">';
    require_once('./libraries/display_select_lang.lib.php');
    PMA_select_language();
    echo '</li>';
}


if ( isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding']
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

if (isset($available_themes_choices) && $available_themes_choices > 1) {
    $theme_selected = FALSE;
    $theme_preview_path= './themes.php';
    $theme_preview_href = '<a href="' . $theme_preview_path . '" target="themes" onclick="'
                        . "window.open('" . $theme_preview_path . "','themes','left=10,top=20,width=510,height=350,scrollbars=yes,status=yes,resizable=yes');"
                        . '">';
    echo '<li id="li_select_theme">';
    ?>
        <form name="setTheme" method="post" action="index.php" target="_parent">
    <?php
    echo PMA_generate_common_hidden_inputs( '', '', 3 );
    echo $theme_preview_href . $strTheme . '</a>:' . "\n";
    ?>
            <select name="set_theme" xml:lang="en" dir="ltr" onchange="this.form.submit();" >
    <?php
    foreach ($available_themes_choices AS $cur_theme) {
        echo '<option value="' . $cur_theme . '"';
        if ($cur_theme == $theme) {
            echo ' selected="selected"';
        }
        echo '>' . htmlspecialchars($available_themes_choices_names[$cur_theme]) . '</option>';
    }
    ?>
            </select>
            <noscript><input type="submit" value="<?php echo $strGo;?>" /></noscript>
        </form>
    </li>
    <?php
}
PMA_printListItem( $strPmaDocumentation, 'li_pma_docs', 'Documentation.html' );

if ( $cfg['ShowPhpInfo'] ) {
    PMA_printListItem( $strShowPHPInfo, 'li_phpinfo', './phpinfo.php?' . $common_url_query );
}

PMA_printListItem( $strHomepageOfficial, 'li_pma_homepage', 'http://www.phpMyAdmin.net/' );
?>
    <li><bdo xml:lang="en" dir="ltr">
        [<a href="changelog.php" target="_blank">ChangeLog</a>]
        [<a href="http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/phpmyadmin/phpMyAdmin/"
            target="_blank">CVS</a>]
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
if ( ! empty( $GLOBALS['PMA_errors'] ) && is_array( $GLOBALS['PMA_errors'] ) ) {
    foreach( $GLOBALS['PMA_errors'] as $error ) {
        echo '<div class="error">' . $error . '</div>' . "\n";
    }
}

/**
 * Removed the "empty $cfg['PmaAbsoluteUri']" warning on 2005-08-23
 * See https://sourceforge.net/tracker/index.php?func=detail&aid=1257134&group_id=23067&atid=377411
 */

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
 * Nijel: As we try to hadle charsets by ourself, mbstring overloads just
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
 * modified: 2004-05-05 mkkeck
 */

if (PMA_PHP_INT_VERSION < 40100) {
    echo '<div class="warning">' . sprintf($strUpgrade, 'PHP', '4.1.0') . '</div>' . "\n";
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
 * prints list item for main page
 *
 * @param   string  $name   dsiplayed text
 * @param   string  $id     id, used for css styles
 * @param   string  $url    make item as link with $url as target
 */
function PMA_printListItem( $name, $id = NULL, $url = NULL, $mysql_help_page = NULL ) {
    echo '<li id="' . $id . '">';
    if ( NULL !== $url ) {
        echo '<a href="' . $url . '">';
    }

    echo $name;

    if ( NULL !== $url ) {
        echo '</a>' . "\n";
    }
    if ( NULL !== $mysql_help_page ) {
        echo PMA_showMySQLDocu( '', $mysql_help_page );
    }
    echo '</li>';
}

/**
 * Displays the footer
 */
require_once('./libraries/footer.inc.php');
?>
