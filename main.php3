<?php
/* $Id$ */


/**
 * Gets some core libraries and displays a top message if required
 */
require('./grab_globals.inc.php3');
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
else if (!empty($reload) && $reload == 'true') {
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
<h1><?php echo $strWelcome . ' phpMyAdmin ' . PHPMYADMIN_VERSION; ?></h1>

<?php
// Don't display server info if $server == 0 (no server selected)
if ($server > 0) {
    $local_query = 'SELECT Version() as version';
    $res_version = mysql_query($local_query) or mysql_die('', $local_query, FALSE, FALSE);
    $row_version = mysql_fetch_array($res_version);
    echo '<p><b>MySQL ' . $row_version['version'] . ' ' . $strRunning . ' ' . $cfgServer['host'];
    if (!empty($cfgServer['port'])) {
        echo ':' . $cfgServer['port'];
    }
    if (!empty($cfgServer['socket'])) {
        echo ':' . $cfgServer['socket'];
    }
    echo "</b></p><br />\n";
}


/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = mysql_query('FLUSH PRIVILEGES') or mysql_die('', 'FLUSH PRIVILEGES', FALSE);
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
        <form action="index.php3" target="_top">
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
            print((!empty($val['verbose'])) ? $val['verbose'] :  $val['host']);
            if (!empty($val['port'])) {
                echo ':' . $val['port'];
            }
            if (!empty($val['only_db'])) {
                echo ' - ' . $val['only_db'];
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
if ($server > 0
    && empty($cfgServer['only_db']))
{
    ?>
    <!-- MySQL server related links -->
    <td valign="top" align="left">
        <table>
        <tr>
            <th colspan="2">MySQL</th>
        </tr>
    <?php
    echo "\n";
    
    $common_url_query = 'lang=' . $lang . '&server=' . urlencode($server);

    // 1. With authentication
    if ($cfgServer['adv_auth'])
    {
        // Get user's rights
        $server_port   = (empty($cfgServer['port']))
                       ? ''
                       : ':' . $cfgServer['port'];
        $server_socket = (empty($cfgServer['socket']) || PHP_INT_VERSION < 30010)
                       ? ''
                       : ':' . $cfgServer['socket'];
        $stdlink       = @mysql_connect(
                             $cfgServer['host'] . $server_port . $server_socket,
                             $cfgServer['stduser'],
                             $cfgServer['stdpass']
                         );
        if ($stdlink == FALSE) {
            $local_query = $connect_func . '('
                         . $cfgServer['host'] . $server_port . $server_socket . ', '
                         . $cfgServer['stduser'] . ', '
                         . $cfgServer['stdpass'] . ')';
            mysql_die('', $local_query, FALSE, FALSE);
        }

        // Does user have global Create priv?
        $local_query  = 'SELECT * FROM mysql.user WHERE User = \'' . sql_addslashes($cfgServer['user']) . '\'';
        $rs_usr       = mysql_query($local_query, $stdlink) or mysql_die('', $local_query, FALSE);
        $result_usr   = mysql_fetch_array($rs_usr);
        $create       = ($result_usr['Create_priv'] == 'Y');
        $db_to_create = '';

        // Does user have Create priv on a inexistant db?
        // if yes, show him in the dialog the first inexistant db name that we
        // find, in most cases it's probably the one he just dropped :)
        // (Note: we only get here after a browser reload, I don't know why)
        if (!$create) {
            $userlink      = @mysql_connect(
                                 $cfgServer['host'] . $server_port . $server_socket,
                                 $cfgServer['user'],
                                 $cfgServer['password']
                             );
            if ($userlink == FALSE) {
                $local_query = 'mysql_connect('
                             . $cfgServer['host'] . $server_port . $server_socket . ', '
                             . $cfgServer['user'] . ', '
                             . $cfgServer['password'] . ')';
                mysql_die('', $local_query, FALSE, FALSE);
            }

            $local_query = 'SELECT Db FROM mysql.db WHERE User = \'' . sql_addslashes($cfgServer['user']) . '\'';
            $rs_usr      = mysql_query($local_query, $stdlink) or mysql_die('', $local_query, FALSE);
            while ($row = mysql_fetch_array($rs_usr)) {
                if (!mysql_select_db($row['Db'], $userlink)) {
                    $db_to_create = $row['Db'];
                    $create       = TRUE;
                    break;
                }
            }
        }
        // The user is allowed to create a db
        if ($create) {
            echo "\n";
            ?>
        <!-- db creation form -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
            <form method="post" action="db_create.php3">
                <?php echo $strCreateNewDatabase . '&nbsp;' . show_docu('manual_Reference.html#CREATE_DATABASE'); ?><br />
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="reload" value="true" />
                <input type="text" name="db" value="<?php echo $db_to_create; ?>" />
                <input type="submit" value="<?php echo $strCreate; ?>" />
            </form>
            </td>
        </tr>
            <?php
            echo "\n";
        } // end create db form

        // Server related links
        ?>
        <!-- server-related links -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW STATUS'); ?>&display=simple">
                    <?php echo $strMySQLShowStatus; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW VARIABLES'); ?>&display=simple">
                <?php echo $strMySQLShowVars;?></a>&nbsp;
                <?php echo show_docu('manual_Performance.html#Performance') . "\n"; ?>
            </td>
        </tr>
        <?php
        echo "\n";

        if ($result_usr['Process_priv'] == 'Y') {
            ?>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW PROCESSLIST'); ?>&display=simple">
                    <?php echo $strMySQLShowProcess; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
            </td>
        </tr>
            <?php
            echo "\n";
        }

        if ($result_usr['Reload_priv'] == 'Y') {
            ?>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="main.php3?<?php echo $common_url_query; ?>&mode=reload">
                    <?php echo $strReloadMySQL; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#FLUSH') . "\n"; ?>
            </td>
        </tr>
            <?php
            echo "\n";
        }

        $result = @mysql_query('USE mysql');
        if (!mysql_error()) {
            ?>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="user_details.php3?<?php echo $common_url_query; ?>&db=mysql&table=user">
                    <?php echo $strUsers; ?></a>&nbsp;
                <?php echo show_docu('manual_Privilege_system.html#Privilege_system') . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="db_stats.php3?<?php echo $common_url_query; ?>">
                    <?php echo $strDatabasesStats; ?></a>
            </td>
        </tr>

            <?php
            echo "\n";
        }
        ?>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="index.php3?<?php echo$common_url_query; ?>&old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>" target="_top">
                    <b><?php echo $strLogout; ?></b></a>&nbsp;
                <a href="<?php echo $cfgPmaAbsoluteUri; ?>Documentation.html#login_bug" target="documentation">(*)</a>
            </td>
        </tr>
        <?php
        echo "\n";
    } // end of 1 (AdvAuth case)

    // 2. No authentication
    else
    {
        ?>
        <!-- db creation form -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
            <form method="post" action="db_create.php3">
                <?php echo $strCreateNewDatabase . ' &nbsp;' . show_docu('manual_Reference.html#CREATE_DATABASE'); ?><br />
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="reload" value="true" />
                <input type="text" name="db" />
                <input type="submit" value="<?php echo $strCreate; ?>" />
            </form>
            </td>
        </tr>

        <!-- server-related links -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW STATUS'); ?>">
                    <?php echo $strMySQLShowStatus; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
            </td>
        </tr>

        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW VARIABLES'); ?>">
                    <?php echo $strMySQLShowVars; ?></a>&nbsp;
                <?php echo show_docu('manual_Performance.html#Performance') . "\n"; ?>
            </td>
        </tr>

        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW PROCESSLIST'); ?>">
                    <?php echo $strMySQLShowProcess; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
            </td>
        </tr>

        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="main.php3?<?php echo $common_url_query; ?>&mode=reload">
                    <?php echo $strReloadMySQL; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#FLUSH') . "\n"; ?>
            </td>
        </tr>
        <?php
        $result = @mysql_query('USE mysql');
        if (!mysql_error()) {
            echo "\n";
            ?>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td> 
                <a href="user_details.php3?<?php echo $common_url_query; ?>&db=mysql&table=user">
                    <?php echo $strUsers; ?></a>&nbsp;
                <?php echo show_docu('manual_Privilege_system.html#Privilege_system') . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="db_stats.php3?<?php echo $common_url_query; ?>">
                    <?php echo $strDatabasesStats; ?></a>
            </td>
        </tr>
            <?php
        }
    } // end of 2 (no AdvAuth case)

    echo "\n";
    ?>
        </table>
    </td>
    
    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
    <?php
    echo "\n";
} // end of if ($server > 0)


/**
 * Displays the phpMyAdmin related links 
 */
?>

    <!-- phpMyAdmin related links -->
    <td valign="top" align="left">
        <table>
        <tr>
            <th colspan="2">phpMyAdmin</th>
        </tr>

<?php
// Displays language selection combo
if (empty($cfgLang)) {
    ?>
        <!-- Language Selection -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <form method="post" action="index.php3" target="_top">
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    Language:
                    <select name="lang" onchange="this.form.submit();">
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
                    <input type="submit" value="Go">
                </form>
            </td>
       </tr>
   <?php
}
echo "\n";
?>

        <!-- Documentation -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="Documentation.html" target="documentation"><b><?php echo $strPmaDocumentation; ?></b></a>
            </td>
        </tr>

        <!-- PHP Information -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="phpinfo.php3" target="_new"><?php echo $strShowPHPInfo; ?></a>
            </td>
        </tr>

        <!-- phpMyAdmin related urls -->
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
            <td>
                <a href="http://phpwizard.net/projects/phpMyAdmin/" target="_new"><?php echo $strHomepageOfficial; ?></a>
            </td>
        </tr>
        <tr>
            <td valign="baseline"><img src="images/item.gif" width="7" height="7" alt="item" /></td>
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
