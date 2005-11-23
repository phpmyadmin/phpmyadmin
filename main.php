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
// Defines the "item" image depending on text direction

$item_img = $GLOBALS['pmaThemeImage'] . 'item_' . $GLOBALS['text_dir'] . '.png';

// Defines for MainPageIconic
$str_iconic_list    = '';
$str_normal_list    = '<td valign="top" width="16">'
    .'<img class="icon" src="'.$item_img.'" alt="*" /></td>';
if ( $cfg['MainPageIconic'] ) {
    $str_iconic_list = '<td width="16" valign="top" >%1$s'
                      .'<img class="icon" src="' . $pmaThemeImage . '%2$s" '
                      .' width="16" height="16" alt="%3$s" />'
                      .'%4$s</td>';
}

// Handles some variables that may have been sent by the calling script
if (isset($db)) {
    unset($db);
}
if (isset($table)) {
    unset($table);
}
$show_query = '1';
require_once('./header.inc.php');


/**
 * Displays the welcome message and the server informations
 */
if ( @file_exists($pmaThemeImage . 'logo_right.png') ) {
    ?>
    <img id="pmalogoright" src="<?php echo $pmaThemeImage; ?>logo_right.png"
        alt="phpMyAdmin" />
    <?php
}
?>
<h1>
<?php
echo sprintf( $strWelcome,
    '<bdo dir="ltr" xml:lang="en">phpMyAdmin ' . PMA_VERSION . '</bdo>');
?>
</h1>
<?php
// Don't display server info if $server == 0 (no server selected)
// loic1: modified in order to have a valid words order whatever is the
//        language used
if ( $server > 0 ) {
    // robbat2: Use the verbose name of the server instead of the hostname
    //          if a value is set
    if (!empty($cfg['Server']['verbose'])) {
        $server_info = $cfg['Server']['verbose'];
    } else {
        $server_info = $cfg['Server']['host'];
        $server_info .= (empty($cfg['Server']['port']) ? '' : ':' . $cfg['Server']['port']);
    }
    // loic1: skip this because it's not a so good idea to display sockets
    //        used to everybody
    // if (!empty($cfg['Server']['socket']) && PMA_PHP_INT_VERSION >= 30010) {
    //     $server_info .= ':' . $cfg['Server']['socket'];
    // }
    $res                           = PMA_DBI_query('SELECT USER();');
    list($mysql_cur_user_and_host) = PMA_DBI_fetch_row($res);
    $mysql_cur_user                = substr($mysql_cur_user_and_host, 0, strrpos($mysql_cur_user_and_host, '@'));

    PMA_DBI_free_result($res);
    unset($res, $row);

    $full_string     = str_replace('%pma_s1%', '<bdo dir="ltr" xml:lang="en">' . PMA_MYSQL_STR_VERSION . '</bdo>', $strMySQLServerProcess);
    $full_string     = str_replace('%pma_s2%', htmlspecialchars($server_info), $full_string);
    $full_string     = str_replace('%pma_s3%', htmlspecialchars($mysql_cur_user_and_host), $full_string);

    echo '    <p><strong>' . $full_string . '</strong></p>' . "\n";
} // end if $server > 0
?>
<hr class="clearfloat" />

<?php
// Any message to display?

if ( ! empty( $message ) ) {
    PMA_showMessage($message);
    unset( $message );
}

/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $sql_query = 'FLUSH PRIVILEGES';
    $result = PMA_DBI_query($sql_query);
    if ($result != 0) {
        $message = $strMySQLReloaded;
    } else {
        $show_error_header = TRUE;
        $message = $strReloadFailed;
    }
    PMA_showMessage($message);
    $show_error_header = FALSE;
    unset($result);
    unset($sql_query);
    unset($message);
}

/**
 * Displays the MySQL servers choice form
 */
if (!$cfg['LeftDisplayServers'] && count($cfg['Servers']) > 1) {
    include('./libraries/select_server.lib.php');
    PMA_select_server(TRUE, FALSE);
}

// nested table needed
?>
<table>
<tr><td valign="top">
<?php
/**
 * Displays the mysql server related links
 */
$is_superuser = false;

if ( $server > 0 ) {

    require_once('./libraries/check_user_privileges.lib.php');
    $is_superuser = PMA_isSuperuser();

    $common_url_query =  PMA_generate_common_url();

    if ($is_superuser) {
        $cfg['ShowChgPassword'] = TRUE;
    }
    if ($cfg['Server']['auth_type'] == 'config') {
        $cfg['ShowChgPassword'] = FALSE;
    }

    ?>
<table cellpadding="3" cellspacing="0">
<tr><th class="tblHeaders" colspan="2" xml:lang="en" dir="ltr">MySQL</th></tr>
<tr>
        <?php
        echo $str_iconic_list != '' ? sprintf($str_iconic_list,'','b_newdb.png',$strCreateNewDatabase,'') : $str_normal_list;
        ?>
    <td valign="top">
        <?php require('./libraries/display_create_database.lib.php'); ?>
    </td>
</tr>
<tr>
            <?php
            echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_status.php?'.$common_url_query.'">','s_status.png',$strMySQLShowStatus,'</a>') : $str_normal_list);
            ?>
    <td><a href="./server_status.php?<?php echo $common_url_query; ?>">
            <?php echo $strMySQLShowStatus; ?>
        </a>
    </td>
</tr>
<tr>        <?php
            echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_variables.php?'.$common_url_query.'">','s_vars.png',$strMySQLShowVars,'</a>') : $str_normal_list);
            ?>
    <td><a href="./server_variables.php?<?php echo $common_url_query; ?>">
            <?php echo $strMySQLShowVars;?></a>
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'SHOW_VARIABLES'); ?>
    </td>
</tr>
<tr>    <?php
        echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_processlist.php?'.$common_url_query.'">','s_process.png',$strMySQLShowProcess,'</a>') : $str_normal_list);
        ?>
    <td><a href="./server_processlist.php?<?php echo $common_url_query; ?>">
            <?php echo $strMySQLShowProcess; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'SHOW_PROCESSLIST'); ?>
    </td>
</tr>
        <?php
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            ?>
<tr>        <?php
            echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_collations.php?'.$common_url_query.'">','s_asci.png',$strCharsetsAndCollations,'</a>') : $str_normal_list);
            ?>
    <td><a href="./server_collations.php?<?php echo $common_url_query; ?>">
            <?php echo $strCharsetsAndCollations; ?></a>
    </td>
</tr>
            <?php
        }
        ?>
<tr>    <?php
        echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_engines.php?'.$common_url_query.'">','b_engine.png',$strStorageEngines,'</a>') : $str_normal_list);
        ?>
    <td><a href="./server_engines.php?<?php echo $common_url_query; ?>">
            <?php echo $strStorageEngines; ?></a>
    </td>
</tr>
        <?php
        if ($is_reload_priv) {
            echo "\n";
            ?>
<tr>        <?php
            echo ($str_iconic_list!='' ? sprintf($str_iconic_list,'<a href="main.php?'.$common_url_query.'&amp;mode=reload">','s_reload.png',$strReloadMySQL,'</a>') : $str_normal_list);
            ?>
    <td><a href="main.php?<?php echo $common_url_query; ?>&amp;mode=reload">
            <?php echo $strReloadMySQL; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'FLUSH') . "\n"; ?>
    </td>
</tr>
            <?php
        }
        if ($is_superuser) {
            ?>
<tr>        <?php
            echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_privileges.php?'.$common_url_query.'">','s_rights.png',$strPrivileges,'</a>') : $str_normal_list);
            ?>
    <td><a href="server_privileges.php?<?php echo $common_url_query; ?>">
            <?php echo $strPrivileges; ?></a>
    </td>
</tr>
            <?php
        }
        $binlogs = PMA_DBI_try_query('SHOW MASTER LOGS', NULL, PMA_DBI_QUERY_STORE);
        if ($binlogs) {
            if (PMA_DBI_num_rows($binlogs) > 0) {
                ?>
<tr>            <?php
                echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_binlog.php?'.$common_url_query.'">','s_tbl.png',$strBinaryLog,'</a>') : $str_normal_list);
                ?>
    <td><a href="server_binlog.php?<?php echo $common_url_query; ?>">
            <?php echo $strBinaryLog; ?></a>
    </td>
</tr>
                <?php
            }
            PMA_DBI_free_result($binlogs);
        }
        unset($binlogs);
        ?>
<tr>    <?php
        echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_databases.php?'.$common_url_query.'">','s_db.png',$strDatabases,'</a>') : $str_normal_list);
        ?>
    <td><a href="./server_databases.php?<?php echo $common_url_query; ?>">
            <?php echo $strDatabases; ?></a>
    </td>
</tr>
<tr>
        <?php
        echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_export.php?'.$common_url_query.'">','b_export.png',$strExport,'</a>') : $str_normal_list);
        ?>
    <td><a href="./server_export.php?<?php echo $common_url_query; ?>">
            <?php echo $strExport; ?></a>
    </td>
</tr>
<tr>
        <?php
        echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_import.php?'.$common_url_query.'">','b_import.png',$strImport,'</a>') : $str_normal_list);
        ?>
    <td><a href="./server_import.php?<?php echo $common_url_query; ?>">
            <?php echo $strImport; ?></a>
    </td>
</tr>
        <?php
        // Change password (needs another message)
        if ($cfg['ShowChgPassword']) {
            ?>
<tr>
            <?php
            echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="user_password.php?'.$common_url_query.'">','s_passwd.png',$strChangePassword,'</a>') : $str_normal_list);
            ?>
    <td><a href="user_password.php?<?php echo $common_url_query; ?>">
            <?php echo ($strChangePassword); ?></a>
    </td>
</tr>
            <?php
        } // end if

        // Logout for advanced authentication
        if ($cfg['Server']['auth_type'] != 'config') {
            $http_logout = ($cfg['Server']['auth_type'] == 'http')
                         ? '<a href="./Documentation.html#login_bug" target="documentation">'
                            . ($cfg['ReplaceHelpImg'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_info.png" width="11" height="11" alt="Info" />' : '(*)') . '</a>'
                         : '';
            ?>
<tr>
            <?php
            echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="index.php?'.$common_url_query.'&amp;old_usr='.urlencode($PHP_AUTH_USER).'">','s_loggoff.png',$strLogout,'</a>') : $str_normal_list);
            ?>
    <td><a href="index.php?<?php echo $common_url_query; ?>&amp;old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>"
            target="_parent">
            <b><?php echo $strLogout; ?></b></a>
            <?php echo $http_logout; ?>
    </td>
</tr>
            <?php
        } // end if
} // end of if ($server > 0)
?>
</table>
</td>
<td width="20">&nbsp;</td>
<td valign="top">
<table border="0" cellpadding="3" cellspacing="0">
<tr><th class="tblHeaders" colspan="2" xml:lang="en" dir="ltr">phpMyAdmin</th></tr>
<?php
// Displays language selection combo
if (empty($cfg['Lang'])) {
    require_once('./libraries/display_select_lang.lib.php');
    ?>
<tr><?php
    echo ($str_iconic_list !='' ? sprintf($str_iconic_list,'<a href="./translators.html" target="documentation">','s_lang.png','Language','</a>') : $str_normal_list);
    ?>
    <td><?php PMA_select_language(); ?></td>
    </tr>
    <?php
}

if ( isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding']
    && $server != 0 && $allow_recoding && PMA_MYSQL_INT_VERSION < 40100) {
    ?>
<tr><?php
    echo $str_iconic_list != '' ? sprintf($str_iconic_list,'','s_asci.png',$strMySQLCharset,'') : $str_normal_list;
    ?>
    <td><form method="post" action="index.php" target="_parent">
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
        echo '                        '
           . '<option value="' . $tmpcharset . '"' . $selected . '>' . $tmpcharset . '</option>' . "\n";
    }
    ?>
            </select>
            <noscript><input type="submit" value="<?php echo $strGo;?>" /></noscript>
        </form>
    </td>
</tr>
    <?php
} elseif ($server != 0 && PMA_MYSQL_INT_VERSION >= 40100) {
    echo '    <!-- Charset Info -->' . "\n"
       . '    <tr>' .  "\n"
       .'        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'','s_asci.png',$strMySQLCharset,'') : $str_normal_list) . "\n"
       . '        <td>' . "\n"
       . '            ' . $strMySQLCharset . ': '
       . '            <strong xml:lang="en" dir="ltr">'
       . '               ' . $mysql_charsets_descriptions[$mysql_charset_map[strtolower($charset)]] . "\n"
       . '               (' . $mysql_charset_map[strtolower($charset)] . ')' . "\n"
       . '            </strong>' . "\n"
       . '        </td>' . "\n"
       . '    </tr>' . "\n"
       . '    <!-- MySQL Connection Collation -->' . "\n"
       . '    <tr>' .  "\n"
       .'        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'','s_asci.png',$strMySQLConnectionCollation,'') : $str_normal_list) . "\n"
       . '        <td>' . "\n"
       . '            <form method="post" action="index.php" target="_parent">' . "\n"
       . PMA_generate_common_hidden_inputs(NULL, NULL, 4, 'collation_connection')
       . '                <label for="select_collation_connection">' . "\n"
       . '                    ' . $strMySQLConnectionCollation . ': ' . "\n"
       . '                </label>' . "\n"
       . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'collation_connection', 'select_collation_connection', $collation_connection, TRUE, 4, TRUE)
       . '                <noscript><input type="submit" value="' . $strGo . '" /></noscript>' . "\n"
       // put the doc link in the form so that it appears on the same line
       . PMA_showMySQLDocu('MySQL_Database_Administration', 'Charset-connection') . "\n"
       . '            </form>' . "\n"
       . '        </td>' . "\n"
       . '    </tr>' . "\n";
}

// added by Michael Keck <mail_at_michaelkeck_dot_de>
// ThemeManager if available

if (isset($available_themes_choices) && $available_themes_choices > 1) {
    $theme_selected = FALSE;
    $theme_preview_path= './themes.php';
    $theme_preview_href = '<a href="' . $theme_preview_path . '" onclick="'
                        . "window.open('" . $theme_preview_path . "','themes','left=10,top=20,width=510,height=350,scrollbars=yes,status=yes,resizable=yes'); return false;"
                        . '">';
    ?>
    <tr>
    <?php
    echo ($str_iconic_list != '' ? sprintf($str_iconic_list,$theme_preview_href,'s_theme.png',$strTheme ,'</a>') : $str_normal_list) . "\n";
    ?>
    <td><form name="setTheme" method="post" action="index.php" target="_parent">
    <?php
    echo PMA_generate_common_hidden_inputs( '', '', 5 );
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
    </td>
</tr>
    <?php
}
?>
<tr><?php
    echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="Documentation.html" target="documentation">','b_docs.png',$strPmaDocumentation,'</a>') : $str_normal_list);
?>
    <td><a href="Documentation.html" target="documentation">
            <b><?php echo $strPmaDocumentation; ?></b></a>
    </td>
</tr>

<?php
if ($cfg['ShowPhpInfo']) {
    ?>
    <tr><?php
        echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="phpinfo.php?' . PMA_generate_common_url() . '" target="_blank">','php_sym.png',$strShowPHPInfo,'</a>') : $str_normal_list);
    ?>
        <td><a href="phpinfo.php?<?php echo PMA_generate_common_url(); ?>"
                target="_blank"><?php echo $strShowPHPInfo; ?></a>
        </td>
    </tr>
    <?php
}
?>
<tr>
<?php
echo ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="http://www.phpMyAdmin.net/" target="_blank">','b_home.png',$strHomepageOfficial,'</a>') : $str_normal_list);
?>
    <td><a href="http://www.phpMyAdmin.net/" target="_blank">
            <?php echo $strHomepageOfficial; ?></a>
    </td>
</tr>
<tr><td></td>
    <td><bdo xml:lang="en" dir="ltr">
        [<a href="changelog.php" target="_blank">ChangeLog</a>]
        [<a href="http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/phpmyadmin/phpMyAdmin/"
            target="_blank">CVS</a>]
        [<a href="http://sourceforge.net/mail/?group_id=23067"
            target="_blank">Lists</a>]
        </bdo>
    </td>
</tr>
</table>

</td>
</tr>
</table>

<hr />

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
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
