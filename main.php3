<?php
/* $Id$ */


/**
 * Gets some core libraries and displays a top message if required
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
// Puts the language to use in a cookie that will expire in 30 days
if (!isset($pma_uri_parts)) {
    $pma_uri_parts = parse_url($cfgPmaAbsoluteUri);
    $cookie_path   = substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/'));
    $is_https      = ($pma_uri_parts['scheme'] == 'https') ? 1 : 0;
}
setcookie('lang', $lang, time() + 60*60*24*30, $cookie_path, '', $is_https);
// Defines the "item" image depending on text direction
$item_img = 'images/item_' . $text_dir . '.gif';
// Handles some variables that may have been sent by the calling script
if (isset($db)) {
    unset($db);
}
if (isset($table)) {
    unset($table);
}
$show_query = 'y';
require('./header.inc.php3');
if (isset($message)) {
    PMA_showMessage($message);
}
else if (isset($reload) && $reload) {
    // Reloads the navigation frame via JavaScript if required
    echo "\n";
    ?>
<script type="text/javascript" language="javascript1.2">
<!--
window.parent.frames['nav'].location.replace('./left.php3?lang=<?php echo $lang; ?>&server=<?php echo $server; ?>');
//-->
</script>
    <?php
}
echo "\n";


/**
 * Displays the welcome message and the server informations
 */
?>
<h1><?php echo sprintf($strWelcome, ' phpMyAdmin ' . PMA_VERSION); ?></h1>

<?php
// Don't display server info if $server == 0 (no server selected)
// loic1: modified in order to have a valid words order whatever is the
//        language used
if ($server > 0) {
    $server_info     = $cfgServer['host']
                     . (empty($cfgServer['port']) ? '' : ':' . $cfgServer['port']);
    // loic1: skip this because it's not a so good idea to display sockets
    //        used to everybody
    // if (!empty($cfgServer['socket']) && PMA_PHP_INT_VERSION >= 30010) {
    //     $server_info .= ':' . $cfgServer['socket'];
    // }
    $local_query             = 'SELECT VERSION() as version, USER() as user';
    $res                     = mysql_query($local_query) or PMA_mysqlDie('', $local_query, FALSE, '');
    $mysql_cur_user_and_host = mysql_result($res, 0, 'user');
    $mysql_cur_user          = substr($mysql_cur_user_and_host, 0, strpos($mysql_cur_user_and_host, '@'));

    $full_string     = str_replace('%pma_s1%', mysql_result($res, 0, 'version'), $strMySQLServerProcess);
    $full_string     = str_replace('%pma_s2%', $server_info, $full_string);
    $full_string     = str_replace('%pma_s3%', $mysql_cur_user_and_host, $full_string);

    echo '<p><b>' . $full_string . '</b></p><br />' . "\n";
} // end if


/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = mysql_query('FLUSH PRIVILEGES'); // Debug: or PMA_mysqlDie('', 'FLUSH PRIVILEGES', FALSE, 'main.php3?lang=' . $lang . '&amp;server=' . $server);
    echo '<p><b>';
    if ($result != 0) {
      echo $strMySQLReloaded;
    } else {
      echo $strReloadFailed;
    }
    echo '</b></p>' . "\n\n";
}


/**
 * Displays the MySQL servers choice form
 */
if ($server == 0 || count($cfgServers) > 1) {
    ?>
<!-- MySQL servers choice form -->
<table>
<tr>
    <th><?php echo $strServerChoice; ?></th>
</tr>
<tr>
    <td>
        <form method="post" action="index.php3" target="_parent">
            <select name="server">
    <?php
    echo "\n";
    reset($cfgServers);
    while (list($key, $val) = each($cfgServers)) {
        if (!empty($val['host'])) {
            echo '                <option value="' . $key . '"';
            if (!empty($server) && ($server == $key)) {
                echo ' selected="selected"';
            }
            echo '>';
            if ($val['verbose'] != '') {
                echo $val['verbose'];
            } else {
                echo $val['host'];
                if (!empty($val['port'])) {
                    echo ':' . $val['port'];
                }
                // loic1: skip this because it's not a so good idea to display
                //        sockets used to everybody
                // if (!empty($val['socket']) && PMA_PHP_INT_VERSION >= 30010) {
                //     echo ':' . $val['socket'];
                // }
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
            <input type="submit" value="<?php echo $strGo; ?>" />
        </form>
    </td>
</tr>
</table>
<br />
    <?php
} // end of the servers choice form
?>

<!-- MySQL and phpMyAdmin related links -->
<table>
<tr>

<?php
/**
 * Displays the mysql server related links
 */
$is_superuser        = FALSE;
if ($server > 0) {
    // Get user's global privileges ($dbh and $userlink are links to MySQL
    // defined in the "common.lib.php3" library)
    $is_create_priv  = FALSE;
    $is_process_priv = FALSE;
    $is_reload_priv  = FALSE;
    $is_superuser    = @mysql_query('USE mysql', $userlink);
    if ($dbh) {
        $local_query = 'SELECT Create_priv, Process_priv, Reload_priv FROM mysql.user WHERE User = \'' . PMA_sqlAddslashes($mysql_cur_user) . '\'';
        $rs_usr      = mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
        if ($rs_usr) {
            while ($result_usr = mysql_fetch_array($rs_usr)) {
                if (!$is_create_priv) {
                    $is_create_priv  = ($result_usr['Create_priv'] == 'Y');
                }
                if (!$is_process_priv) {
                    $is_process_priv = ($result_usr['Process_priv'] == 'Y');
                }
                if (!$is_reload_priv) {
                    $is_reload_priv  = ($result_usr['Reload_priv'] == 'Y');
                }
            } // end while
            mysql_free_result($rs_usr);
        } // end if
    } // end if

    // If the user has Create priv on a inexistant db, show him in the dialog
    // the first inexistant db name that we find, in most cases it's probably
    // the one he just dropped :)
    if (!$is_create_priv) {
        $local_query = 'SELECT DISTINCT Db FROM mysql.db WHERE Create_priv = \'Y\' AND User = \'' . PMA_sqlAddslashes($mysql_cur_user) . '\'';
        $rs_usr      = mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
        if ($rs_usr) {
            $re0     = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
            $re1     = '(^|[^\])(\\\)+';       // escaped wildcards
            while ($row = mysql_fetch_array($rs_usr)) {
                if (ereg($re0 . '(%|_)', $row['Db'])
                    || (!mysql_select_db(ereg_replace($re1 . '(%|_)', '\\1\\3', $row['Db']), $userlink) && @mysql_errno() != 1044)) {
                    $db_to_create   = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $row['Db']));
                    $db_to_create   = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                    $is_create_priv = TRUE;
                    break;
                } // end if
            } // end while
            mysql_free_result($rs_usr);
        } // end if
        else if (PMA_MYSQL_INT_VERSION >= 32304) {
            // Finally, let's try to get the user's privileges by using SHOW
            // GRANTS...
            // Maybe we'll find a little CREATE priv there :)
            $local_query = 'SHOW GRANTS FOR ' . $mysql_cur_user_and_host;
            $rs_usr      = mysql_query($local_query, $dbh);
            if (!$rs_usr) {
                // OK, now we'd have to guess the user's hostname, but we
                // only try out the 'username'@'%' case.
                $local_query = 'SHOW GRANTS FOR ' . $mysql_cur_user;
                $rs_usr      = mysql_query($local_query, $dbh);
            }
            if ($rs_usr) {
                $re      = '(^|(\\\\\\\\)+|[^\])';
                while ($row = mysql_fetch_row($rs_usr)) {
                    $show_grants_dbname = substr($row[0], strpos($row[0], ' ON ') + 4,(strpos($row[0], '.', strpos($row[0], ' ON ')) - strpos($row[0], ' ON ') - 4));
                    $show_grants_str    = substr($row[0],6,(strpos($row[0],' ON ')-6));
                    if (($show_grants_str == 'ALL') || ($show_grants_str == 'ALL PRIVILEGES') || ($show_grants_str == 'CREATE') || strpos($show_grants_str, 'CREATE')) {
                        if ($show_grants_dbname == '*') {
                            $is_create_priv = TRUE;
                            $db_to_create   = '';
                            break;
                        } // end if
                        elseif (ereg($re . '%|_', $show_grants_dbname) || !mysql_select_db($show_grants_dbname, $userlink) && @mysql_errno() != 1044) {
                            $show_grants_dbname = ereg_replace($re . '%', '\\1...', ereg_replace($re . '_', '\\1?', $show_grants_dbname));
                            $db_to_create       = $show_grants_dbname;
                            $is_create_priv     = TRUE;
                            break;
                        } // end elseif
                    } // end if
                } // end while
                unset($show_grants_dbname);
                unset($show_grants_str);
                mysql_free_result($rs_usr);
            } // end if
        } // end elseif
    } // end if
    else {
        $db_to_create = '';
    } // end else

    $common_url_query = 'lang=' . $lang . '&amp;server=' . $server;

    if ($is_superuser) {
        $cfgShowMysqlInfo   = TRUE;
        $cfgShowMysqlVars   = TRUE;
        $cfgShowChgPassword = TRUE;
    }
    if ($cfgServer['auth_type'] == 'config') {
        $cfgShowChgPassword = FALSE;
    }

    // loic1: Displays the MySQL column only if at least one feature has to be
    //        displayed
    if ($is_superuser || $is_create_priv || $is_process_priv || $is_reload_priv
        || $cfgShowMysqlInfo || $cfgShowMysqlVars || $cfgShowChgPassword
        || $cfgServer['auth_type'] != 'config') {
        ?>
    <!-- MySQL server related links -->
    <td valign="top" align="<?php echo $cell_align_left; ?>">
        <table>
        <tr>
            <th colspan="2">&nbsp;&nbsp;MySQL</th>
        </tr>
        <?php
        // The user is allowed to create a db
        if ($is_create_priv) {
            echo "\n";
            ?>
        <!-- db creation form -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
            <form method="post" action="db_create.php3">
                <?php echo $strCreateNewDatabase . '&nbsp;' . PMA_showDocuShort('C/R/CREATE_DATABASE.html'); ?><br />
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="reload" value="1" />
                <input type="text" name="db" value="<?php echo $db_to_create; ?>" maxlength="64" class="textfield" />
                <input type="submit" value="<?php echo $strCreate; ?>" />
            </form>
            </td>
        </tr>
            <?php
        } // end create db form
        echo "\n";

        // Server related links
        ?>
        <!-- server-related links -->
        <?php
        if ($cfgShowMysqlInfo) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;sql_query=<?php echo urlencode('SHOW STATUS'); ?>&amp;goto=main.php3">
                    <?php echo $strMySQLShowStatus; ?></a>&nbsp;
                <?php echo PMA_showDocuShort('S/H/SHOW_STATUS.html') . "\n"; ?>
            </td>
        </tr>
            <?php
        } // end if
        if ($cfgShowMysqlVars) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;sql_query=<?php echo urlencode('SHOW VARIABLES'); ?>&amp;goto=main.php3">
                <?php echo $strMySQLShowVars;?></a>&nbsp;
                <?php echo PMA_showDocuShort('S/H/SHOW_VARIABLES.html') . "\n"; ?>
            </td>
        </tr>
            <?php
        }

        if ($is_process_priv) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;sql_query=<?php echo urlencode('SHOW PROCESSLIST'); ?>&amp;goto=main.php3">
                    <?php echo $strMySQLShowProcess; ?></a>&nbsp;
                <?php echo PMA_showDocuShort('S/H/SHOW_PROCESSLIST.html') . "\n"; ?>
            </td>
        </tr>
            <?php
        } // end if

        if ($is_reload_priv) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="main.php3?<?php echo $common_url_query; ?>&amp;mode=reload">
                    <?php echo $strReloadMySQL; ?></a>&nbsp;
                <?php echo PMA_showDocuShort('F/L/FLUSH.html') . "\n"; ?>
            </td>
        </tr>
            <?php
        }

        if ($is_superuser) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="user_details.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;table=user">
                    <?php echo $strUsers; ?></a>&nbsp;
                <?php echo PMA_showDocuShort('P/r/Privilege_system.html') . "\n"; ?>
            </td>
        </tr>
            <?php
            if (PMA_MYSQL_INT_VERSION >= 32303) {
                echo "\n";
                ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="db_stats.php3?<?php echo $common_url_query; ?>">
                    <?php echo $strDatabasesStats; ?></a>
            </td>
        </tr>
                <?php
            }
        }

        // Change password (needs another message)
        if ($cfgShowChgPassword) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="user_password.php3?<?php echo $common_url_query; ?>">
                    <?php echo ($strChangePassword); ?></a>
            </td>
        </tr>
            <?php
        } // end if

        // Logout for advanced authentication
        if ($cfgServer['auth_type'] != 'config') {
            $http_logout = ($cfgServer['auth_type'] == 'http')
                         ? "\n" . '                <a href="' . $cfgPmaAbsoluteUri . 'Documentation.html#login_bug" target="documentation">(*)</a>'
                         : '';
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="index.php3?<?php echo $common_url_query; ?>&amp;old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>" target="_parent">
                    <b><?php echo $strLogout; ?></b></a>&nbsp;<?php echo $http_logout . "\n"; ?>
            </td>
        </tr>
            <?php
        } // end if
        echo "\n";
        ?>
        </table>
    </td>

    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <?php
    } // end if
} // end of if ($server > 0)
echo "\n";


/**
 * Displays the phpMyAdmin related links
 */
?>

    <!-- phpMyAdmin related links -->
    <td valign="top" align="<?php echo $cell_align_left; ?>">
        <table>
        <tr>
            <th colspan="2">&nbsp;&nbsp;phpMyAdmin</th>
        </tr>

<?php
// Displays language selection combo
if (empty($cfgLang)) {
    ?>
        <!-- Language Selection -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td nowrap="nowrap">
                <form method="post" action="index.php3" target="_parent">
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    Language:
                    <select name="lang" dir="ltr" onchange="this.form.submit();">
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
    reset($available_languages);
    while (list($id, $tmplang) = each($available_languages)) {
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
                    <noscript><input type="submit" value="Go" /></noscript>
                </form>
            </td>
        </tr>
    <?php
}
echo "\n";
?>

        <!-- Documentation -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="Documentation.html" target="documentation"><b><?php echo $strPmaDocumentation; ?></b></a>
            </td>
        </tr>

<?php
if ($is_superuser || $cfgShowPhpInfo) {
    ?>
        <!-- PHP Information -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="phpinfo.php3" target="_new"><?php echo $strShowPHPInfo; ?></a>
            </td>
        </tr>
    <?php
}
echo "\n";
?>

        <!-- phpMyAdmin related urls -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="http://www.phpMyAdmin.net/" target="_new"><?php echo $strHomepageOfficial; ?></a><br />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[<a href="ChangeLog" target="_new">ChangeLog</a>]
                &nbsp;&nbsp;&nbsp;[<a href="http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/phpmyadmin/phpMyAdmin/" target="_new">CVS</a>]
                &nbsp;&nbsp;&nbsp;[<a href="http://sourceforge.net/mail/?group_id=23067" target="_new">Lists</a>]
            </td>
        </tr>
        </table>
    </td>

</tr>
</table>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
