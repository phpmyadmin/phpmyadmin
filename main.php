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
setcookie('lang', $lang, time() + 60*60*24*30, $cookie_path, '', $is_https);
// Defines the "item" image depending on text direction
$item_img = 'images/item_' . $text_dir . '.png';
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
window.parent.frames['nav'].location.replace('./left.php?<?php echo PMA_generate_common_url('', '', '&');?>&hash=' + <?php echo (($cfg['QueryFrame'] && $cfg['QueryFrameJS']) ? 'window.parent.frames[\'queryframe\'].document.hashform.hash.value' : "'" . md5($cfg['PmaAbsoluteUri']) . "'"); ?>););
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
    // robbat2: Use the verbose name of the server instead of the hostname
    //          if a value is set
    if(!empty($cfg['Server']['verbose'])) {
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
    $local_query             = 'SELECT VERSION() as version, USER() as user';
    $res                     = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, FALSE, '');
    $mysql_cur_user_and_host = PMA_mysql_result($res, 0, 'user');
    $mysql_cur_user          = substr($mysql_cur_user_and_host, 0, strrpos($mysql_cur_user_and_host, '@'));

    $full_string     = str_replace('%pma_s1%', PMA_mysql_result($res, 0, 'version'), $strMySQLServerProcess);
    $full_string     = str_replace('%pma_s2%', $server_info, $full_string);
    $full_string     = str_replace('%pma_s3%', $mysql_cur_user_and_host, $full_string);

    echo '<p><b>' . $full_string . '</b></p><br />' . "\n";
} // end if


/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = PMA_mysql_query('FLUSH PRIVILEGES'); // Debug: or PMA_mysqlDie('', 'FLUSH PRIVILEGES', FALSE, 'main.php?' . PMA_generate_common_url());
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
if ($server == 0 || count($cfg['Servers']) > 1) {
    ?>
<!-- MySQL servers choice form -->
<table>
<tr>
    <th><?php echo $strServerChoice; ?></th>
</tr>
<tr>
    <td>
        <form method="post" action="index.php" target="_parent">
            <select name="server">
    <?php
    echo "\n";
    foreach($cfg['Servers'] AS $key => $val) {
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
    // defined in the "common.lib.php" library)
    // Note: if no controluser is defined, $dbh contains $userlink

    $is_create_priv  = FALSE;
    $is_process_priv = TRUE;
    $is_reload_priv  = FALSE;

// We were checking privileges with 'USE mysql' but users with the global
// priv CREATE TEMPORARY TABLES or LOCK TABLES can do a 'USE mysql'
// (even if they cannot see the tables)
    $is_superuser    = @PMA_mysql_query('SELECT COUNT(*) FROM mysql.user', $userlink);
    if ($dbh) {
        $local_query = 'SELECT Create_priv, Process_priv, Reload_priv FROM mysql.user WHERE User = \'' . PMA_sqlAddslashes($mysql_cur_user) . '\'';
        $rs_usr      = PMA_mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
        if ($rs_usr) {
            while ($result_usr = PMA_mysql_fetch_array($rs_usr)) {
                if (!$is_create_priv) {
                    $is_create_priv  = ($result_usr['Create_priv'] == 'Y');
                }
                /* 02-12-09 rabus: Every user has access to the process list -
                                   at least to its own :-)
                if (!$is_process_priv) {
                    $is_process_priv = ($result_usr['Process_priv'] == 'Y');
                }
                */
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
        $rs_usr      = PMA_mysql_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
        if ($rs_usr) {
            $re0     = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
            $re1     = '(^|[^\])(\\\)+';       // escaped wildcards
            while ($row = PMA_mysql_fetch_array($rs_usr)) {
                if (ereg($re0 . '(%|_)', $row['Db'])
                    || (!PMA_mysql_select_db(ereg_replace($re1 . '(%|_)', '\\1\\3', $row['Db']), $userlink) && @mysql_errno() != 1044)) {
                    $db_to_create   = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $row['Db']));
                    $db_to_create   = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                    $is_create_priv = TRUE;
                    break;
                } // end if
            } // end while
            mysql_free_result($rs_usr);
        } // end if
        else {
            // Finally, let's try to get the user's privileges by using SHOW
            // GRANTS...
            // Maybe we'll find a little CREATE priv there :)
            $local_query = 'SHOW GRANTS FOR ' . $mysql_cur_user_and_host;
            $rs_usr      = PMA_mysql_query($local_query, $dbh);
            if (!$rs_usr) {
                // OK, now we'd have to guess the user's hostname, but we
                // only try out the 'username'@'%' case.
                $local_query = 'SHOW GRANTS FOR ' . $mysql_cur_user;
                $rs_usr      = PMA_mysql_query($local_query, $dbh);
            }
            if ($rs_usr) {
                $re0 = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
                $re1 = '(^|[^\])(\\\)+'; // escaped wildcards
                while ($row = PMA_mysql_fetch_row($rs_usr)) {
                    $show_grants_dbname = substr($row[0], strpos($row[0], ' ON ') + 4,(strpos($row[0], '.', strpos($row[0], ' ON ')) - strpos($row[0], ' ON ') - 4));
                    $show_grants_str    = substr($row[0],6,(strpos($row[0],' ON ')-6));
                    if (($show_grants_str == 'ALL') || ($show_grants_str == 'ALL PRIVILEGES') || ($show_grants_str == 'CREATE') || strpos($show_grants_str, 'CREATE')) {
                        if ($show_grants_dbname == '*') {
                            $is_create_priv = TRUE;
                            $db_to_create   = '';
                            break;
                        } // end if
                        else if (ereg($re0 . '%|_', $show_grants_dbname) || !PMA_mysql_select_db($show_grants_dbname, $userlink) && @mysql_errno() != 1044) {
                            $db_to_create = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $show_grants_dbname));
                            $db_to_create = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                            // and remove backquotes
                            $db_to_create = str_replace('`','',$db_to_create);
                            $is_create_priv     = TRUE;
                            break;
                        } // end elseif
                    } // end if
                } // end while
                unset($show_grants_dbname, $show_grants_str);
                mysql_free_result($rs_usr);
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
            <form method="post" action="db_create.php">
                <?php echo $strCreateNewDatabase . '&nbsp;' . PMA_showMySQLDocu('Reference', 'CREATE_DATABASE'); ?><br />
                <?php echo PMA_generate_common_hidden_inputs(); ?>
                <input type="hidden" name="reload" value="1" />
                <input type="text" name="db" value="<?php echo $db_to_create; ?>" maxlength="64" class="textfield" />
                <input type="submit" value="<?php echo $strCreate; ?>" />
            </form>
            </td>
        </tr>
            <?php
        } else {
            echo "\n";
            ?>
        <!-- db creation no privileges message -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <?php echo $strCreateNewDatabase . ':&nbsp;' . PMA_showMySQLDocu('Reference', 'CREATE_DATABASE'); ?><br />
                <?php echo '<i>' . $strNoPrivileges .'</i>'; ?><br />
            </td>
        </tr>
            <?php
        } // end create db form or message
        echo "\n";

        // Server related links
        ?>
        <!-- server-related links -->
        <?php
        if ($cfg['ShowMysqlInfo']) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="./server_status.php?<?php echo $common_url_query; ?>">
                    <?php echo $strMySQLShowStatus . "\n"; ?>
                </a>
            </td>
        </tr>
            <?php
        } // end if
        if ($cfg['ShowMysqlVars']) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="./server_variables.php?<?php echo $common_url_query; ?>">
                <?php echo $strMySQLShowVars;?></a>&nbsp;
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'SHOW_VARIABLES') . "\n"; ?>
            </td>
        </tr>
            <?php
        }

        echo "\n";
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
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
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
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
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
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
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="server_privileges.php?<?php echo $common_url_query; ?>">
                    <?php echo $strPrivileges; ?></a>&nbsp;
            </td>
        </tr>
            <?php
        }
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="./server_databases.php?<?php echo $common_url_query; ?>">
                    <?php echo $strDatabases; ?></a>
            </td>
        </tr>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
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
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
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
                         ? "\n" . '                <a href="./Documentation.html#login_bug" target="documentation">(*)</a>'
                         : '';
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="index.php?<?php echo $common_url_query; ?>&amp;old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>" target="_parent">
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
if (empty($cfg['Lang'])) {
    ?>
        <!-- Language Selection -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td nowrap="nowrap">
                <form method="post" action="index.php" target="_parent">
                    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    Language <a href="./translators.html" target="documentation">(*)</a>:
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
    foreach($available_languages AS $id => $tmplang) {
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

if (isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding']
    && $allow_recoding) {
    echo "\n";
    ?>
        <!-- Charset Selection -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td nowrap="nowrap">
                <form method="post" action="index.php" target="_parent">
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                    <?php echo $strMySQLCharset;?>:
                    <select name="convcharset" dir="ltr" onchange="this.form.submit();">
    <?php
    echo "\n";
    foreach($cfg['AvailableCharsets'] AS $id => $tmpcharset) {
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
if ($is_superuser || $cfg['ShowPhpInfo']) {
    ?>
        <!-- PHP Information -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="phpinfo.php?<?php echo PMA_generate_common_url(); ?>" target="_blank"><?php echo $strShowPHPInfo; ?></a>
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
                <a href="http://www.phpMyAdmin.net/" target="_blank"><?php echo $strHomepageOfficial; ?></a><br />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[<a href="ChangeLog" target="_blank">ChangeLog</a>]
                &nbsp;&nbsp;&nbsp;[<a href="http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/phpmyadmin/phpMyAdmin/" target="_blank">CVS</a>]
                &nbsp;&nbsp;&nbsp;[<a href="http://sourceforge.net/mail/?group_id=23067" target="_blank">Lists</a>]
            </td>
        </tr>
        </table>
    </td>

</tr>
</table>


<?php
/**
 * Displays the "empty $cfg['PmaAbsoluteUri'] warning"
 */
if ($display_pmaAbsoluteUri_warning) {
    echo '<p class="warning">' . $strPmaUriError . '</p>' . "\n";
}

/**
 * Warning if using the default MySQL privileged account
 */
if ($server != 0
    && $cfg['Server']['user'] == 'root'
    && $cfg['Server']['password'] == '') {
    echo '<p class="warning">' . $strInsecureMySQL . '</p>' . "\n";
}

/**
 * Warning for PHP 4.2.3
 */

if (PMA_PHP_INT_VERSION == 40203 && @extension_loaded('mbstring')) {
    echo '<p class="warning">' . $strPHP40203 . '</p>' . "\n";
}

/**
 * Warning for old PHP version
 */

if (PMA_PHP_INT_VERSION < 40100) {
    echo '<p class="warning">' . sprintf($strUpgrade, 'PHP', '4.1.0') . '</p>' . "\n";
}

/**
 * Warning for old MySQL version
 */

if (PMA_MYSQL_INT_VERSION < 32332) {
    echo '<p class="warning">' . sprintf($strUpgrade, 'MySQL', '3.23.32') . '</p>' . "\n";
}

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
