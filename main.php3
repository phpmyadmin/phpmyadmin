<?php
/* $Id$ */


/**
 * Gets some core libraries and displays a top message if required
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
// Puts the language to use in a cookie that will expire in 30 days
setcookie('lang', $lang, time() + 60*60*24*30);
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
    show_message($message);
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
<h1><?php echo sprintf($strWelcome, ' phpMyAdmin ' . PHPMYADMIN_VERSION); ?></h1>

<?php
// Don't display server info if $server == 0 (no server selected)
if ($server > 0) {
    $local_query = 'SELECT VERSION() as version, USER() as user';
    $res         = mysql_query($local_query) or mysql_die('', $local_query, FALSE, '');
    echo '<p><b>MySQL ' . mysql_result($res, 0, 'version') . ' ' . $strRunning . ' ' . $cfgServer['host'];
    if (!empty($cfgServer['port'])) {
        echo ':' . $cfgServer['port'];
    }
    // loic1: skip this because it's not a so good idea to display sockets
    //        used to everybody
    // if (!empty($cfgServer['socket']) && PHP_INT_VERSION >= 30010) {
    //     echo ':' . $cfgServer['socket'];
    // }
    echo ' ' . $strRunningAs . ' ' . mysql_result($res, 0, 'user') . '</b></p><br />' . "\n";
} // end if


/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = mysql_query('FLUSH PRIVILEGES'); // Debug: or mysql_die('', 'FLUSH PRIVILEGES', FALSE, 'main.php3?lang=' . $lang . '&amp;server=' . $server);
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
        <form action="index.php3" target="_parent">
            <select name="server">
    <?php
    echo "\n";
    reset($cfgServers);
    while (list($key, $val) = each($cfgServers))
    {
        if (!empty($val['host']))
        {
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
                // if (!empty($val['socket']) && PHP_INT_VERSION >= 30010) {
                //     echo ':' . $val['socket'];
                // }
            }
            // loic1: if 'only_db' is an array and there is more than one
            //        value, displaying such informations may not be a so good
            //        idea
            if (!empty($val['only_db'])) {
                echo ' - ' . (is_array($val['only_db']) ? implode(', ', $val['only_db']) : $val['only_db']);
            }
            if (!empty($val['user']) && !($val['adv_auth'])) {
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
$is_superuser = FALSE;
if ($server > 0) {
    ?>
    <!-- MySQL server related links -->
    <td valign="top" align="<?php echo $cell_align_left; ?>">
        <table>
        <tr>
            <th colspan="2">&nbsp;&nbsp;MySQL</th>
        </tr>
    <?php
    echo "\n";
    
    $common_url_query = 'lang=' . $lang . '&amp;server=' . $server;

    // Get user's global privileges ($dbh and $userlink are links to MySQL
    // defined in the "common.lib.php3" library)
    $is_create_priv  = FALSE;
    $is_process_priv = FALSE;
    $is_reload_priv  = FALSE;
    $is_superuser    = @mysql_query('USE mysql', $userlink);
    if ($dbh) {
        $local_query = 'SELECT Create_priv, Process_priv, Reload_priv FROM mysql.user WHERE User = \'' . sql_addslashes($cfgServer['user']) . '\' OR User = \'\'';
        $rs_usr      = mysql_query($local_query, $dbh); // Debug: or mysql_die('', $local_query, FALSE);
        if ($rs_usr) {
            $result_usr      = mysql_fetch_array($rs_usr);
            $is_create_priv  = ($result_usr['Create_priv'] == 'Y');
            $is_process_priv = ($result_usr['Process_priv'] == 'Y');
            $is_reload_priv  = ($result_usr['Reload_priv'] == 'Y');
            mysql_free_result($rs_usr);
        } // end if
    } // end if

    // If the user has Create priv on a inexistant db, show him in the dialog
    // the first inexistant db name that we find, in most cases it's probably
    // the one he just dropped :)
    // (Note: we only get here after a browser reload, I don't know why)
    if (!$is_create_priv) {
        $local_query = 'SELECT DISTINCT Db FROM mysql.db WHERE Create_priv = \'Y\' AND (User = \'' . sql_addslashes($cfgServer['user']) . '\' OR User = \'\')';
        $rs_usr      = mysql_query($local_query, $dbh); // Debug: or mysql_die('', $local_query, FALSE);
        if (@mysql_numrows($rs_usr) > 0) {
            while ($row = mysql_fetch_array($rs_usr)) {
                if (!mysql_select_db($row['Db'], $dbh)) {
                    $re              = '(^|(\\\\\\\\)+|[^\])';
                    $row['Db']       = ereg_replace($re . '%', '\\1...', ereg_replace($re . '_', '\\1?', $row['Db']));
                    $db_to_create    = $row['Db'];
                    $is_create_priv  = TRUE;
                    break;
                } // end if
            } // end while
            mysql_free_result($rs_usr);
        } // end if
    } // end if
    else {
        $db_to_create = '';
    } // end else


    // The user is allowed to create a db
    if ($is_create_priv) {
        echo "\n";
        ?>
        <!-- db creation form -->
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
            <form method="post" action="db_create.php3">
                <?php echo $strCreateNewDatabase . '&nbsp;' . show_docu('manual_Reference.html#CREATE_DATABASE'); ?><br />
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="reload" value="1" />
                <input type="text" name="db" value="<?php echo $db_to_create; ?>" />
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
    if ($is_superuser || $cfgShowMysqlInfo) {
        echo "\n";
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;sql_query=<?php echo urlencode('SHOW STATUS'); ?>&amp;goto=main.php3">
                    <?php echo $strMySQLShowStatus; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
            </td>
        </tr>
        <?php
    } // end if
    if ($is_superuser || $cfgShowMysqlVars) {
        echo "\n";
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;sql_query=<?php echo urlencode('SHOW VARIABLES'); ?>&amp;goto=main.php3">
                <?php echo $strMySQLShowVars;?></a>&nbsp;
                <?php echo show_docu('manual_Performance.html#Performance') . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    echo "\n";

    if ($is_process_priv) {
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;sql_query=<?php echo urlencode('SHOW PROCESSLIST'); ?>&amp;goto=main.php3">
                    <?php echo $strMySQLShowProcess; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
            </td>
        </tr>
        <?php
    } // end if
    echo "\n";

    if ($is_reload_priv) {
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="main.php3?<?php echo $common_url_query; ?>&amp;mode=reload">
                    <?php echo $strReloadMySQL; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#FLUSH') . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    echo "\n";

    if ($is_superuser) {
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="user_details.php3?<?php echo $common_url_query; ?>&amp;db=mysql&amp;table=user">
                    <?php echo $strUsers; ?></a>&nbsp;
                <?php echo show_docu('manual_Privilege_system.html#Privilege_system') . "\n"; ?>
            </td>
        </tr>
        <?php
        if (MYSQL_INT_VERSION >= 32303) {
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
    echo "\n";

    // Logout for advanced authentication
    if ($cfgServer['adv_auth'])
    {
        ?>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="index.php3?<?php echo $common_url_query; ?>&amp;old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>" target="_parent">
                    <b><?php echo $strLogout; ?></b></a>&nbsp;
                <a href="<?php echo $cfgPmaAbsoluteUri; ?>Documentation.html#login_bug" target="documentation">(*)</a>
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
     * @param	array	the array to be sorted
     * @param	mixed	a required parameter
     *
     * @return	the sorted array
     *
     * @access	private
     */
    function pmaComp(&$a, $b)
    {
        return (strcmp($a[1], $b[1]));
    } // end of the 'pmaComp()' function

    uasort($available_languages, 'pmaComp');
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
                    <input type="submit" value="Go" />
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
                <a href="http://phpwizard.net/projects/phpMyAdmin/" target="_new"><?php echo $strHomepageOfficial; ?></a>
            </td>
        </tr>
        <tr>
            <td valign="baseline"><img src="<?php echo $item_img; ?>" width="7" height="7" alt="item" /></td>
            <td>
                <a href="http://phpmyadmin.sourceforge.net/" target="_new">
                    <?php echo $strHomepageSourceforge; ?></a><br />
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
