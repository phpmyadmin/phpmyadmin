<?php
/* $Id$ */


/**
 * Gets some core libraries and displays a top message if required
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');
if (isset($message)) {
    show_message($message);
}


/**
 * Displays language selection boxes
 */
if (empty($cfgLang)) {
    echo '<p>';
    reset($available_languages);
    while (list($id,$tmplang) = each($available_languages)) {
        $lang_name = ucfirst(substr(strstr($tmplang[0], '|'), 1));
        echo "\n";
        ?>
    [&nbsp;<a href="index.php3?lang=<?php echo $id; ?>&server=<?php echo urlencode($server); ?>" target="_top" title="<?php echo $lang_name; ?>"><?php echo $id; ?></a>&nbsp;]
        <?php
    }
    echo "\n<p><br />\n";
}


/**
 * Displays the welcome message and the server informations
 */
?>
<h1><?php echo $strWelcome . ' phpMyAdmin ' . PHPMYADMIN_VERSION; ?></h1>

<?php
// Don't display server info if $server == 0 (no server selected)
if ($server > 0) {
    $res_version = mysql_query('SELECT Version() as version') or mysql_die();
    $row_version = mysql_fetch_array($res_version);
    echo '<p><b>MySQL ' . $row_version['version'] . ' ' . $strRunning . ' ' . $cfgServer['host'];
    if (!empty($cfgServer['port'])) {
        echo ':' . $cfgServer['port'];
    }
    echo "</b></p>\n";
}


/**
 * Reload mysql (flush privileges)
 */
if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = mysql_query('FLUSH PRIVILEGES');
    echo '<p><b>';
    if ($result != 0) {
      echo $strMySQLReloaded;
    } else {
      echo $strReloadFailed;
    }
    echo '</b></p>' . "\n";
}



/**
 * Displays the servers choice form and/or the server-related links 
 */
?>
<ul>
<?php
// 1. The servers choice form 
if ($server == 0 || count($cfgServers) > 1) {
    ?>
    <li>
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
                echo ' selected';
            }
            echo '>';
            print((!empty($val['verbose'])) ? $val['verbose'] :  $val['host']);
            if (!empty($val['port'])) {
                echo ':' . $val['port'];
            }
            if (!empty($val['only_db'])) {
                echo ' - ' . $val['only_db'];
            }
            echo '&nbsp;</option>' . "\n";
        } // end if (!empty($val['host']))
    } // end while
    ?>
            </select>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="submit" value="<?php echo $strGo; ?>" />
        </form>
    </li>
    <?php
} // end of the servers choice form


// 2. The server-related links if $server > 0 (a server selected)
if ($server > 0
    && empty($cfgServer['only_db']))
{
    $common_url_query = 'lang=' . $lang . '&server=' . urlencode($server);

    // 2.1. With authentication
    if ($cfgServer['adv_auth'])
    {
        // Get user's rights
        if (empty($cfgServer['port'])) {
            $stdlink      = mysql_connect($cfgServer['host'], $cfgServer['stduser'], $cfgServer['stdpass']);
        } else {
            $stdlink      = mysql_connect($cfgServer['host'] . ':' . $cfgServer['port'], $cfgServer['stduser'], $cfgServer['stdpass']);
        }
        // Does user have global Create priv?
        $rs_usr       = mysql_query('SELECT * FROM mysql.user WHERE User = \'' . sql_addslashes($cfgServer['user']) . '\'', $stdlink);
        $result_usr   = mysql_fetch_array($rs_usr);
        $create       = ($result_usr['Create_priv'] == 'Y');
        $db_to_create = '';

        // Does user have Create priv on a inexistant db?
        // if yes, show him in the dialog the first inexistant db name that we
        // find, in most cases it's probably the one he just dropped :)
        // (Note: we only get here after a browser reload, I don't know why)
        if (!$create) {

           if (empty($cfgServer['port'])) {
               $userlink = mysql_connect($cfgServer['host'], $cfgServer['user'],
                           $cfgServer['password']) or mysql_die();
           } else {
               $userlink = mysql_connect($cfgServer['host'].":".
                           $cfgServer['port'], $cfgServer['user'],
                           $cfgServer['password']) or mysql_die();
           }
            $rs_usr = mysql_query('SELECT Db FROM mysql.db WHERE User = \'' . sql_addslashes($cfgServer['user']) . '\'', $stdlink);
            while ($row = mysql_fetch_array($rs_usr)) {
                if (!mysql_select_db($row['Db'],$userlink)) {
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
    <li>
        <form method="post" action="db_create.php3">
            <?php echo $strCreateNewDatabase . '&nbsp;' . show_docu('manual_Reference.html#CREATE_DATABASE'); ?><br />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="reload" value="true" />
            <input type="text" name="db" value="<?php echo $db_to_create; ?>" />
            <input type="submit" value="<?php echo $strCreate; ?>" />
        </form>
    </li>
            <?php
            echo "\n";
        } // end create db form

        // Server related links
        if ($result_usr['References_priv'] == 'Y') {
            ?>
    <!-- server-related links -->
    <li>
        <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW STATUS'); ?>&display=simple">
        <?php echo $strMySQLShowStatus; ?></a>&nbsp;<?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
    </li>
    <li>
        <a href="sql.php3?<?php $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW VARIABLES'); ?>&display=simple">
        <?php echo $strMySQLShowVars;?></a>&nbsp;<?php echo show_docu('manual_Performance.html#Performance') . "\n"; ?>
    </li>
            <?php
            echo "\n";
        }

        if ($result_usr['Process_priv'] == 'Y') {
            ?>
    <li>
        <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW PROCESSLIST'); ?>&display=simple">
        <?php echo $strMySQLShowProcess; ?></a>&nbsp;<?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
    </li>
            <?php
            echo "\n";
        }

        if ($result_usr['Reload_priv'] == 'Y') {
            ?>
    <li>
        <a href="main.php3?<?php echo $common_url_query; ?>&mode=reload">
        <?php echo $strReloadMySQL; ?></a>&nbsp;<?php echo show_docu('manual_Reference.html#FLUSH') . "\n"; ?>
    </li>
            <?php
            echo "\n";
        }

        $result = mysql_query('SELECT * FROM mysql.user');
        $rows   = @mysql_num_rows($result);
        if (!empty($rows)) {
            ?>
    <li>
        <a href="user_details.php3?<?php echo $common_url_query; ?>&db=mysql&table=user">
        <?php echo $strUsers; ?></a>&nbsp;<?php echo show_docu('manual_Privilege_system.html#Privilege_system') . "\n"; ?>
    </li>
            <?php
            echo "\n";
        }
        ?>
    <li>
        <a href="index.php3?<?php echo$common_url_query; ?>&old_usr=<?php echo urlencode($PHP_AUTH_USER); ?>" target="_top">
        <b><?php echo $strLogout; ?></b></a>
    </li>
    <br /><br />
        <?php
        echo "\n";
    } // end of 2.1 (AdvAuth case)

    // 2.2. No authentification
    else
    {
        ?>
    <!-- db creation form -->
    <li>
        <form method="post" action="db_create.php3">
            <?php echo $strCreateNewDatabase . ' &nbsp;' . show_docu('manual_Reference.html#CREATE_DATABASE'); ?><br />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="reload" value="true" />
            <input type="text" name="db" />
            <input type="submit" value="<?php echo $strCreate; ?>" />
        </form>
    </li>

    <!-- server-related links -->
    <li>
        <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW STATUS'); ?>">
        <?php echo $strMySQLShowStatus; ?></a>&nbsp;<?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
    </li>

    <li>
        <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW VARIABLES'); ?>">
        <?php echo $strMySQLShowVars; ?></a>&nbsp;<?php echo show_docu('manual_Performance.html#Performance') . "\n"; ?>
    </li>

    <li>
        <a href="sql.php3?<?php echo $common_url_query; ?>&db=mysql&sql_query=<?php echo urlencode('SHOW PROCESSLIST'); ?>">
        <?php echo $strMySQLShowProcess; ?></a>&nbsp;<?php echo show_docu('manual_Reference.html#SHOW') . "\n"; ?>
    </li>

    <li>
        <a href="main.php3?<?php echo $common_url_query; ?>&mode=reload">
        <?php echo $strReloadMySQL; ?></a>&nbsp;<?php echo show_docu('manual_Reference.html#FLUSH') . "\n"; ?>
    </li>
    <br /><br />
        <?php
    } // end of 2.2 (no AdvAuth case)
} // end of 2: if ($server > 0)
?>

    <!-- documentation -->
    <li>
        <a href="Documentation.html" target="_new">phpMyAdmin <?php echo $strDocu; ?></a>
    </li>
    <li>
        <a href="http://phpwizard.net/projects/phpMyAdmin/" target="_new"><?php echo $strHomepageOfficial; ?></a>
    </li>
    <li>
        <a href="http://phpmyadmin.sourceforge.net/" target="_new">
        <?php echo $strHomepageSourceforge; ?></a>&nbsp;&nbsp;&nbsp;[<a href="ChangeLog" target="_new">ChangeLog</a>]
    </li>
</ul>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
