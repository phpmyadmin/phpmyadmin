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
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
// Puts the language to use in a cookie that will expire in 30 days
if (!isset($pma_uri_parts)) {
    $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
    $cookie_path   = substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/'));
    $is_https      = (isset($pma_uri_parts['scheme']) && $pma_uri_parts['scheme'] == 'https') ? 1 : 0;
}
setcookie('pma_lang', $lang, time() + 60*60*24*30, $cookie_path, '', $is_https);
/**
 * Includes the ThemeManager
 */
require_once('./libraries/select_theme.lib.php');
// Defines the "item" image depending on text direction
$item_img = $GLOBALS['pmaThemeImage'] . 'item_ltr.png';
// Defines for MainPageIconic
$str_iconic_list    = '';
$str_iconic_colspan = '';
$str_normal_list    = '<td valign="top" align="right" width="16"><img src="'.$item_img.'" border="0" hspace="2" vspace="5"></td>';
if ($cfg['MainPageIconic']) {
    $str_iconic_list .= "<td width=\"16\" valign=\"top\" align=\"center\" nowrap=\"nowrap\">%1\$s"
                      . "<img src=\"" . $pmaThemeImage . "%2\$s\" border=\"0\" width=\"16\" height=\"16\" hspace=\"2\" alt=\"%3\$s\" />"
                      . "%4\$s</td>";
    $str_iconic_colspan .= ' colspan="2"';
} else {
    $str_iconic_list = '';
    $str_iconic_colspan = ' colspan="2"';
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
if (isset($message)) {
    PMA_showMessage($message);
}
else if (isset($reload) && $reload) {
    // Reloads the navigation frame via JavaScript if required
    echo "\n";
    ?>
<script type="text/javascript" language="javascript1.2">
<!--
window.parent.frames['nav'].GoTo('./left.php?<?php echo PMA_generate_common_url('', '', '&');?>&hash=' + <?php echo (($cfg['QueryFrame'] && $cfg['QueryFrameJS']) ? 'window.parent.frames[\'queryframe\'].document.hashform.hash.value' : "'" . md5($cfg['PmaAbsoluteUri']) . "'"); ?>););
//-->
</script>
    <?php
}
echo "\n";


/**
 * Displays the welcome message and the server informations
 */
?>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td align="left" valign="top">
        <h1>
        <?php
        echo sprintf($strWelcome, ' phpMyAdmin ' . PMA_VERSION . ''); 
        ?>
        </h1>
<?php
// Don't display server info if $server == 0 (no server selected)
// loic1: modified in order to have a valid words order whatever is the
//        language used
if ($server > 0) {
    // robbat2: Use the verbose name of the server instead of the hostname
    //          if a value is set
    if (!empty($cfg['Server']['verbose'])) {
        $server_info = $cfg['Server']['verbose'];
    } else {
        $server_info = $cfg['Server']['host'];
    }
    $server_info            .= (empty($cfg['Server']['port']) ? '' : ':' . $cfg['Server']['port']);
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

    $full_string     = str_replace('%pma_s1%', PMA_MYSQL_STR_VERSION, $strMySQLServerProcess);
    $full_string     = str_replace('%pma_s2%', $server_info, $full_string);
    $full_string     = str_replace('%pma_s3%', $mysql_cur_user_and_host, $full_string);

    echo '<p><b>' . $full_string . '</b></p>' . "\n";
} // end if


/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = PMA_DBI_query('FLUSH PRIVILEGES');
    echo '<p><b>';
    if ($result != 0) {
        echo $strMySQLReloaded;
    } else {
        echo $strReloadFailed;
    }
    unset($result);
    echo '</b></p>' . "\n\n";
}
?>
        </td>
        <?php
        if (@file_exists($pmaThemeImage . 'logo_right.png')) {
            echo '        <td align="right" valign="top">' . "\n";
            echo '            <img src="' . $pmaThemeImage . 'logo_right.png" alt="phpMyAdmin - Logo" border="0" hspace="5" vspace="5" align="right" />' . "\n";
            echo '        </td>';
        }
        ?>
</tr></table>
<hr />
<?php

/**
 * Displays the MySQL servers choice form
 */
if ($server == 0 || count($cfg['Servers']) > 1) {
    ?>
<!-- MySQL servers choice form -->
<form method="post" action="index.php" target="_parent" class="nospace">
<table border="0" cellpadding="3" cellspacing="0" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
    <tr>
        <th class="tblHeaders"><?php echo $strServerChoice; ?></th>
    </tr>
    <tr>
        <td>
            <select name="server">
    <?php
    echo "\n";
    foreach ($cfg['Servers'] AS $key => $val) {
        if (!empty($val['host'])) {
            echo '                <option value="' . $key . '"';
            if (!empty($server) && ($server == $key)) {
                echo ' selected="selected"';
            }
            echo '>';
            if (!empty($val['verbose'])) {
                echo $val['verbose'];
            } else {
                echo $val['host'];
                if (!empty($val['port'])) {
                    echo ':' . $val['port'];
                }
            }
            // loic1: if 'only_db' is an array and there is more than one
            //        value, displaying such informations may not be a so good
            //        idea
            if (!empty($val['only_db'])) {
                echo ' - ' . (is_array($val['only_db']) ? implode(', ', $val['only_db']) : $val['only_db']);
            }
            if (!empty($val['user']) && ($val['auth_type'] == 'config')) {
                echo '  (' . $val['user'] . ')';
            }
            echo '&nbsp;</option>' . "\n";
        } // end if (!empty($val['host']))
    } // end while
    ?>
            </select>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
            <input type="submit" value="<?php echo $strGo; ?>" />
        </td>
    </tr>
</table>
</form><br />
    <?php
} // end of the servers choice form

// neted table needed
?>
<table border="0" cellpadding="0" cellspacing="0">
<tr>
<td valign="top">
<!-- MySQL and phpMyAdmin related links -->
<?php
/**
 * Displays the mysql server related links
 */
$is_superuser        = FALSE;
if ($server > 0) {
    // Get user's global privileges ($dbh and $userlink are links to MySQL
    // defined in the "common.lib.php" library)
    // Note: if no controluser is defined, $dbh contains $userlink

    $is_create_priv  = FALSE;
    $is_process_priv = TRUE;
    $is_reload_priv  = FALSE;

// We were checking privileges with 'USE mysql' but users with the global
// priv CREATE TEMPORARY TABLES or LOCK TABLES can do a 'USE mysql'
// (even if they cannot see the tables)
    $is_superuser    = PMA_DBI_try_query('SELECT COUNT(*) FROM mysql.user', $userlink, PMA_DBI_QUERY_STORE);
    if ($dbh) {
        $local_query = 'SELECT Create_priv, Reload_priv FROM mysql.user WHERE User = \'' . PMA_sqlAddslashes($mysql_cur_user) . '\'';
        $rs_usr      = PMA_DBI_try_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
        if ($rs_usr) {
            while ($result_usr = PMA_DBI_fetch_assoc($rs_usr)) {
                if (!$is_create_priv) {
                    $is_create_priv  = ($result_usr['Create_priv'] == 'Y');
                }
                if (!$is_reload_priv) {
                    $is_reload_priv  = ($result_usr['Reload_priv'] == 'Y');
                }
            } // end while
            PMA_DBI_free_result($rs_usr);
            unset($rs_usr, $result_usr);
        } // end if
    } // end if
    // If the user has Create priv on a inexistant db, show him in the dialog
    // the first inexistant db name that we find, in most cases it's probably
    // the one he just dropped :)
    if (!$is_create_priv) {
        $rs_usr      = PMA_DBI_try_query('SELECT DISTINCT Db FROM mysql.db WHERE Create_priv = \'Y\' AND User = \'' . PMA_sqlAddslashes($mysql_cur_user) . '\';', $dbh, PMA_DBI_QUERY_STORE);
        if ($rs_usr) {
            $re0     = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
            $re1     = '(^|[^\])(\\\)+';       // escaped wildcards
            while ($row = PMA_DBI_fetch_assoc($rs_usr)) {
                if (ereg($re0 . '(%|_)', $row['Db'])
                    || (!PMA_DBI_try_query('USE ' . ereg_replace($re1 . '(%|_)', '\\1\\3', $row['Db'])) && substr(PMA_DBI_getError(), 1, 4) != 1044)) {
                    $db_to_create   = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $row['Db']));
                    $db_to_create   = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                    $is_create_priv = TRUE;
                    break;
                } // end if
            } // end while
            PMA_DBI_free_result($rs_usr);
            unset($rs_usr, $row, $re0, $re1);
        } // end if
        else {
            // Finally, let's try to get the user's privileges by using SHOW
            // GRANTS...
            // Maybe we'll find a little CREATE priv there :)
            $rs_usr      = PMA_DBI_try_query('SHOW GRANTS FOR ' . $mysql_cur_user_and_host . ';', $dbh, PMA_DBI_QUERY_STORE);
            if (!$rs_usr) {
                // OK, now we'd have to guess the user's hostname, but we
                // only try out the 'username'@'%' case.
                $rs_usr      = PMA_DBI_try_query('SHOW GRANTS FOR ' . $mysql_cur_user . ';', $dbh, PMA_DBI_QUERY_STORE);
            }
            unset($local_query);
            if ($rs_usr) {
                $re0 = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
                $re1 = '(^|[^\])(\\\)+'; // escaped wildcards
                while ($row = PMA_DBI_fetch_row($rs_usr)) {
                    $show_grants_dbname = substr($row[0], strpos($row[0], ' ON ') + 4,(strpos($row[0], '.', strpos($row[0], ' ON ')) - strpos($row[0], ' ON ') - 4));
                    $show_grants_dbname = ereg_replace('^`(.*)`','\\1',  $show_grants_dbname);
                    $show_grants_str    = substr($row[0],6,(strpos($row[0],' ON ')-6));
                    if (($show_grants_str == 'ALL') || ($show_grants_str == 'ALL PRIVILEGES') || ($show_grants_str == 'CREATE') || strpos($show_grants_str, 'CREATE')) {
                        if ($show_grants_dbname == '*') {
                            $is_create_priv = TRUE;
                            $db_to_create   = '';
                            break;
                        } // end if
                        else if ( (ereg($re0 . '%|_', $show_grants_dbname)
                                  && !ereg('\\\\%|\\\\_', $show_grants_dbname))
                                || (!PMA_DBI_try_query('USE ' . ereg_replace($re1 .'(%|_)', '\\1\\3', $show_grants_dbname)) && substr(PMA_DBI_getError(), 1, 4) != 1044)
                            ) {
                            $db_to_create = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $show_grants_dbname));
                            $db_to_create = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                            $is_create_priv     = TRUE;
                            break;
                        } // end elseif
                    } // end if
                } // end while
                unset($show_grants_dbname, $show_grants_str, $re0, $re1);
                PMA_DBI_free_result($rs_usr);
                unset($rs_usr);
            } // end if
        } // end elseif
    } // end if
    else {
        $db_to_create = '';
    } // end else

    if (!$cfg['SuggestDBName']) {
        $db_to_create = '';
    }

    $common_url_query =  PMA_generate_common_url();

    if ($is_superuser) {
        $cfg['ShowMysqlInfo']   = TRUE;
        $cfg['ShowMysqlVars']   = TRUE;
        $cfg['ShowChgPassword'] = TRUE;
    }
    if ($cfg['Server']['auth_type'] == 'config') {
        $cfg['ShowChgPassword'] = FALSE;
    }

    // loic1: Displays the MySQL column only if at least one feature has to be
    //        displayed
    if ($is_superuser || $is_create_priv || $is_process_priv || $is_reload_priv
        || $cfg['ShowMysqlInfo'] || $cfg['ShowMysqlVars'] || $cfg['ShowChgPassword']
        || $cfg['Server']['auth_type'] != 'config') {
?>
<!-- MySQL server related links -->
<table cellpadding="3" cellspacing="0">
    <tr>
        <th<?php if(isset($theme) && $theme!='original'){ ?> class="tblHeaders"<?php } echo $str_iconic_colspan; ?>>&nbsp;&nbsp;MySQL</th>
    </tr>
    <tr><?php
        echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'','b_newdb.png',$strCreateNewDatabase,'') : $str_normal_list); 
?>
    <!-- db creation form -->
        <td valign="top" align="<?php echo $cell_align_left; ?>" nowrap="nowrap">
<?php
        if ($is_create_priv) {
            // The user is allowed to create a db
            ?>
                <form method="post" action="db_create.php"><b>
                    <?php echo $strCreateNewDatabase . '&nbsp;' . PMA_showMySQLDocu('Reference', 'CREATE_DATABASE'); ?></b><br />
                    <?php echo PMA_generate_common_hidden_inputs(5); ?>
                    <input type="hidden" name="reload" value="1" />
                    <input type="text" name="db" value="<?php echo $db_to_create; ?>" maxlength="64" class="textfield" id="listTable" /><br />
                    <?php
            if (PMA_MYSQL_INT_VERSION >= 40101) {
                require_once('./libraries/mysql_charsets.lib.php');
                echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'db_collation', NULL, NULL, TRUE, 5);
            }
                    ?>
                    <input type="submit" value="<?php echo $strCreate; ?>" id="buttonGo" />
                </form>
            <?php
        } else {
            ?>
            <!-- db creation no privileges message -->
                <b><?php echo $strCreateNewDatabase . ':&nbsp;' . PMA_showMySQLDocu('Reference', 'CREATE_DATABASE'); ?></b><br />
                <?php 
                      echo '<span class="noPrivileges">' 
                         . ($cfg['ErrorIconic'] ? '<img src="' . $pmaThemeImage . 's_error2.png" width="11" height="11" hspace="2" border="0" align="absmiddle" />' : '')
                         . '' . $strNoPrivileges .'</span>';
        } // end create db form or message
        ?>
        </td>
    </tr>
        <?php
        echo "\n";

        // Server related links
        ?>
        <!-- server-related links -->
        <?php
        if ($cfg['ShowMysqlInfo']) {
?>
    <tr><?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_status.php?'.$common_url_query.'">','s_status.png',$strMySQLShowStatus,'</a>') : $str_normal_list);
?>
        <td>
                <a href="./server_status.php?<?php echo $common_url_query; ?>">
                    <?php echo $strMySQLShowStatus . "\n"; ?>
                </a>
        </td>
    </tr>
            <?php
        } // end if
        if ($cfg['ShowMysqlVars']) {
?>
    <tr><?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_variables.php?'.$common_url_query.'">','s_vars.png',$strMySQLShowVars,'</a>') : $str_normal_list);
?>
        <td>
                <a href="./server_variables.php?<?php echo $common_url_query; ?>">
                <?php echo $strMySQLShowVars;?></a>&nbsp;
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'SHOW_VARIABLES') . "\n"; ?>
        </td>
    </tr>
        <?php
        }
?>
    <tr><?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_processlist.php?'.$common_url_query.'">','s_process.png',$strMySQLShowProcess,'</a>') : $str_normal_list);
?>
        <td>
                <a href="./server_processlist.php?<?php echo $common_url_query; ?>">
                    <?php echo $strMySQLShowProcess; ?></a>&nbsp;
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'SHOW_PROCESSLIST') . "\n"; ?>
        </td>
    </tr>
        <?php

        if (PMA_MYSQL_INT_VERSION >= 40100) {
            echo "\n";
            ?>
    <tr><?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="./server_collations.php.php?'.$common_url_query.'">','s_asci.png',$strCharsetsAndCollations,'</a>') : $str_normal_list); 
?>
        <td>
                <a href="./server_collations.php?<?php echo $common_url_query; ?>">
                    <?php echo $strCharsetsAndCollations; ?></a>&nbsp;
        </td>
    </tr>
            <?php
        }

        if ($is_reload_priv) {
            echo "\n";
            ?>
    <tr><?php
            echo '        ' . ($str_iconic_list!='' ? sprintf($str_iconic_list,'<a href="main.php?'.$common_url_query.'&amp;mode=reload">','s_reload.png',$strReloadMySQL,'</a>') : $str_normal_list);  
?>
        <td>
                <a href="main.php?<?php echo $common_url_query; ?>&amp;mode=reload">
                    <?php echo $strReloadMySQL; ?></a>&nbsp;
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'FLUSH') . "\n"; ?>
        </td>
    </tr>
            <?php
        }

        if ($is_superuser) {
            echo "\n";
            ?>
    <tr><?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_privileges.php?'.$common_url_query.'">','s_rights.png',$strPrivileges,'</a>') : $str_normal_list);   
?>
        <td>
                <a href="server_privileges.php?<?php echo $common_url_query; ?>">
                    <?php echo $strPrivileges; ?></a>&nbsp;
        </td>
    </tr>
            <?php
        }
        ?>
    <tr><?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_databases.php?'.$common_url_query.'">','s_db.png',$strDatabases,'</a>') : $str_normal_list);  
?>
        <td>
                <a href="./server_databases.php?<?php echo $common_url_query; ?>">
                    <?php echo $strDatabases; ?></a>
        </td>
    </tr>
    <tr>
<?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="server_export.php?'.$common_url_query.'">','b_export.png',$strExport,'</a>') : $str_normal_list);
?>
        <td>
                <a href="./server_export.php?<?php echo $common_url_query; ?>">
                    <?php echo $strExport; ?></a>
        </td>
    </tr>
        <?php

        // Change password (needs another message)
        if ($cfg['ShowChgPassword']) {
            echo "\n";
            ?>
    <tr>
<?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="user_password.php?'.$common_url_query.'">','s_passwd.png',$strChangePassword,'</a>') : $str_normal_list);
?>
        <td>
                <a href="user_password.php?<?php echo $common_url_query; ?>">
                    <?php echo ($strChangePassword); ?></a>
        </td>
    </tr>
            <?php
        } // end if

        // Logout for advanced authentication
        if ($cfg['Server']['auth_type'] != 'config') {
            $http_logout = ($cfg['Server']['auth_type'] == 'http')
                         ? "\n" 
. '                <a href="./Documentation.html#login_bug" target="documentation">'
                         . ($cfg['ReplaceHelpImg'] ? '<img src="' . $pmaThemeImage . 'b_info.png" width="11" height="11" border="0" alt="Info" align="absmiddle" />' : '(*)') . '</a>'
                         : '';
            echo "\n";
            ?>
    <tr>
<?php
            echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="index.php?'.$common_url_query.'&amp;old_usr='.urlencode($PHP_AUTH_USER).'">','s_loggoff.png',$strChangePassword,'</a>') : $str_normal_list);
?>
        <td>

                <a href="index.php?<?php echo $common_url_query; ?>&amp;old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>" target="_parent">
                    <b><?php echo $strLogout; ?></b></a>&nbsp;<?php echo $http_logout . "\n"; ?>
        </td>
    </tr>
            <?php
        } // end if
        ?>
</table>
<?php
    } // end if
} // end of if ($server > 0)
echo "\n";

?>
</td>
<td width="20">&nbsp;</td>
<td valign="top">
<table border="0" cellpadding="3" cellspacing="0">
    <tr>
        <th<?php if(isset($theme) && $theme!='none'){ ?> class="tblHeaders"<?php } echo $str_iconic_colspan; ?>>&nbsp;&nbsp;phpMyAdmin</th>
    </tr>
<?php
// Displays language selection combo
if (empty($cfg['Lang'])) {
    ?>
    <!-- Language Selection -->
    <tr><?php
        echo '        ' . ($str_iconic_list !='' ? sprintf($str_iconic_list,'<a href="./translators.html" target="documentation">','s_lang.png','Language','</a>') : $str_normal_list); 
?>
        <td nowrap="nowrap">
            <form method="post" action="index.php" target="_parent">
                <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                Language <a href="./translators.html" target="documentation"><?php
                if ($cfg['ReplaceHelpImg']){
                    echo '<img src="' . $pmaThemeImage . 'b_info.png" border="0" width="11" height="11" alt="Info" hspace="1" vspace="1" />';
                }else{ echo '(*)'; }
?></a>: <select name="lang" dir="ltr" onChange="this.form.submit();" style="vertical-align: middle">
    <?php
    echo "\n";

    /**
     * Sorts available languages by their true names
     *
     * @param   array   the array to be sorted
     * @param   mixed   a required parameter
     *
     * @return  the sorted array
     *
     * @access  private
     */
    function PMA_cmp(&$a, $b)
    {
        return (strcmp($a[1], $b[1]));
    } // end of the 'PMA_cmp()' function

    uasort($available_languages, 'PMA_cmp');
    foreach ($available_languages AS $id => $tmplang) {
        $lang_name = ucfirst(substr(strstr($tmplang[0], '|'), 1));
        if ($lang == $id) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        echo '                        ';
        echo '<option value="' . $id . '"' . $selected . '>' . $lang_name . ' (' . $id . ')</option>' . "\n";
    }
    ?>
                </select>
                <noscript><input type="submit" value="Go" style="vertical-align: middle" /></noscript>
            </form>
        </td>
    </tr>
       
    <?php
}

if (isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding']
    && $allow_recoding && PMA_MYSQL_INT_VERSION < 40100) {
    echo "\n";
?>
    <!-- Charset Selection -->
    <tr><?php
        echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'','s_asci.png',$strMySQLCharset,'') : $str_normal_list);
?>
        <td>
            <form method="post" action="index.php" target="_parent">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="set_theme" value="<?php echo $theme; ?>" />
                <?php echo $strMySQLCharset;?>:
                <select name="convcharset" dir="ltr" onChange="this.form.submit();" style="vertical-align: middle">
    <?php
    echo "\n";
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
                <noscript><input type="submit" value="Go" style="vertical-align: middle" /></noscript>
            </form>
        </td>
    </tr>
    <?php
} elseif (PMA_MYSQL_INT_VERSION >= 40100) {
    echo '    <!-- Charset Info -->' . "\n"
       . '    <tr>' .  "\n"
       .'        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'','s_asci.png',$strMySQLCharset,'') : $str_normal_list) . "\n" 
       . '        <td>' . "\n"
       . '            ' . $strMySQLCharset . ': '
       . '        <b>'
       . '               ' . $mysql_charsets_descriptions[$mysql_charset_map[strtolower($charset)]] . "\n"
       . '               (' . $mysql_charset_map[strtolower($charset)] . ')' . "\n"
       . '        </b></td>' . "\n"
       . '    </tr>' . "\n";
}
echo "\n";

// added by Michael Keck <mail_at_michaelkeck_dot_de>
// ThemeManager if available

if (count($available_themes_choices)>1) {
    $theme_selected = FALSE;
    $theme_preview_path= './open.php?get=./' . $cfg['ThemePath'] . '/index.php';
    $theme_preview_href = '<a href="' . $theme_preview_path . '" target="themes" onclick="'
                        . "window.open('" . $theme_preview_path . "','themes','left=10,top=20,width=510,height=350,scrollbars=yes,status=yes,resizable=yes');"
                        . '">';
?>
    <!-- Theme Manager -->
    <tr>
<?php
        echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,$theme_preview_href,'s_theme.png','Theme (Style)','</a>') : $str_normal_list) . "\n";
?>
        <td>
            <form name="setTheme" method="post" action="index.php" target="_parent">
                <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <?php
                echo $theme_preview_href
                   . (isset($strTheme) ? $strTheme : 'Theme (Style)')
                   . '</a>:' . "\n";
                ?> 
                <select name="set_theme" dir="ltr" onChange="this.form.submit();" style="vertical-align: middle">
                <?php
                    for($i=0;$i<count($available_themes_choices);$i++){
                        echo '<option value="' . $available_themes_choices[$i] . '"';
                        if (isset($theme) && $available_themes_choices[$i] == $theme) {
                            echo ' selected="selected"';
                            $theme_selected = TRUE;
                        }
                        echo '>' . $available_themes_choices[$i] . '</option>';
                    }
                    if ($theme_selected != TRUE) {
                        echo '<option value="" selected="selected">' . (isset($strSelectTheme) ? $strSelectTheme : 'please select ...') . '</option>';
                    }
                ?>
                </select>
                <noscript><input type="submit" value="Go" style="vertical-align: middle" /></noscript>
            </form>
        </td>
    </tr>
<?php
}
?>
    <!-- Documentation -->
    <tr><?php
        echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="Documentation.html" target="documentation">','b_docs.png',$strPmaDocumentation,'</a>') : $str_normal_list); 
?>
        <td nowrap="nowrap">
            <a href="Documentation.html" target="documentation"><b><?php echo $strPmaDocumentation; ?></b></a>
        </td>
    </tr>

<?php
if ($is_superuser || $cfg['ShowPhpInfo']) {
    ?>
    <!-- PHP Information -->
    <tr><?php
        echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="phpinfo.php?' . PMA_generate_common_url() . '" target="_blank">','php_sym.png',$strShowPHPInfo,'</a>') : $str_normal_list);  
?>
        <td nowrap="nowrap">
            <a href="phpinfo.php?<?php echo PMA_generate_common_url(); ?>" target="_blank"><?php echo $strShowPHPInfo; ?></a>
        </td>
    </tr>
    <?php
}
echo "\n";
?>

        <!-- phpMyAdmin related urls -->
    <tr><?php
        echo '        ' . ($str_iconic_list != '' ? sprintf($str_iconic_list,'<a href="http://www.phpMyAdmin.net/" target="_blank">','b_home.png',$strHomepageOfficial,'</a>') : $str_normal_list);  
?>
        <td nowrap="nowrap">
            <a href="http://www.phpMyAdmin.net/" target="_blank"><?php echo $strHomepageOfficial; ?></a>
       </td>
    </tr> 
    <tr>
<?php
        echo '<td><img src="./images/spacer.gif" width="1" height="1" border="0" /></td>'; 
?>
       <td nowrap="nowrap">
            [<a href="ChangeLog" target="_blank">ChangeLog</a>]
            &nbsp;&nbsp;&nbsp;[<a href="http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/phpmyadmin/phpMyAdmin/" target="_blank">CVS</a>]
            &nbsp;&nbsp;&nbsp;[<a href="http://sourceforge.net/mail/?group_id=23067" target="_blank">Lists</a>]
       </td>
    </tr>
</table>

</td>
</tr>
</table>

<hr />


<?php
/**
 * Displays the "empty $cfg['PmaAbsoluteUri'] warning"
 * modified: 2004-05-05 mkkeck
 */
if ($display_pmaAbsoluteUri_warning) {
    echo '<div class="warning">' . $strPmaUriError . '</div>' . "\n";
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
echo "\n";
require_once('./footer.inc.php');
?>
