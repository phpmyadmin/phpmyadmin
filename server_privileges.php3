<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
if (!defined('PMA_GRAB_GLOBALS_INCLUDED')) {
    include('./libraries/grab_globals.lib.php3');
}
if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php3');
}

/**
 * Extracts the privilege information of a priv table row
 *
 * @param   array    the row
 * @param   boolean  add <dfn> tag with tooltips
 *
 * @return  array
 */
function PMA_extractPrivInfo($row = '', $enableHTML = FALSE)
{
    $grants = array(
        array('Select_priv', 'SELECT', $GLOBALS['strPrivDescSelect']),
        array('Insert_priv', 'INSERT', $GLOBALS['strPrivDescInsert']),
        array('Update_priv', 'UPDATE', $GLOBALS['strPrivDescUpdate']),
        array('Delete_priv', 'DELETE', $GLOBALS['strPrivDescDelete']),
        array('Create_priv', 'CREATE', $GLOBALS['strPrivDescCreate' . (isset($GOLBALS['tablename']) ? 'Tbl' : 'Db')]),
        array('Drop_priv', 'DROP', $GLOBALS['strPrivDescDrop' . (isset($GOLBALS['tablename']) ? 'Tbl' : 'Db')]),
        array('Reload_priv', 'RELOAD', $GLOBALS['strPrivDescReload']),
        array('Shutdown_priv', 'SHUTDOWN', $GLOBALS['strPrivDescShutdown']),
        array('Process_priv', 'PROCESS', $GLOBALS['strPrivDescProcess' . ((!empty($row) && isset($row['Super_priv'])) || (empty($row) && isset($GLOBALS['Super_priv'])) ? '4' : '3')]),
        array('File_priv', 'FILE', $GLOBALS['strPrivDescFile']),
        array('References_priv', 'REFERENCES', $GLOBALS['strPrivDescReferences']),
        array('Index_priv', 'INDEX', $GLOBALS['strPrivDescIndex']),
        array('Alter_priv', 'ALTER', $GLOBALS['strPrivDescAlter']),
        array('Show_db_priv', 'SHOW DATABASES', $GLOBALS['strPrivDescShowDb']),
        array('Super_priv', 'SUPER', $GLOBALS['strPrivDescSuper']),
        array('Create_tmp_table_priv', 'CREATE TEMPORARY TABLES', $GLOBALS['strPrivDescCreateTmpTable']),
        array('Lock_tables_priv', 'LOCK TABLES', $GLOBALS['strPrivDescLockTables']),
        array('Execute_priv', 'EXECUTE', $GLOBALS['strPrivDescExecute']),
        array('Repl_slave_priv', 'REPLICATION SLAVE', $GLOBALS['strPrivDescReplSlave']),
        array('Repl_client_priv', 'REPLICATION CLIENT', $GLOBALS['strPrivDescReplClient'])
    );
    $privs = array();
    $allPrivileges = TRUE;
    while (list(, $current_grant) = each($grants)) {
        if ((!empty($row) && isset($row[$current_grant[0]])) || (empty($row) && isset($GLOBALS[$current_grant[0]]))) {
            if ((!empty($row) && $row[$current_grant[0]] == 'Y') || (empty($row) && $GLOBALS[$current_grant[0]] == 'Y')) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $current_grant[2] . '">' . str_replace(' ', '&nbsp;', $current_grant[1]) . '</dfn>';
                } else {
                    $privs[] = $current_grant[1];
                }
            } else {
                $allPrivileges = FALSE;
            }
        }
    }
    if (empty($privs)) {
        if ($enableHTML) {
            $privs[] = '<dfn title="' . $GLOBALS['strPrivDescUsage'] . '">USAGE</dfn>';
        } else {
            $privs[] = 'USAGE';
        }
    } else if ($allPrivileges && (!isset($GLOBALS['grant_count']) || count($privs) == $GLOBALS['grant_count'])) {
        if ($enableHTML) {
            $privs = array('<dfn title="' . $GLOBALS['strPrivDescAllPrivileges'] . '">ALL&nbsp;PRIVILEGES</dfn>');
        } else {
            $privs = array('ALL PRIVILEGES');
        }
    }
    return $privs;
}

/**
 * Updates privileges
 */
if (!empty($update_privs)) {
    $sql_query0 = 'REVOKE ALL PRIVILEGES ON *.* FROM "' . $username . '"@"' . $hostname . '";';
    $sql_query1 = 'REVOKE GRANT OPTION ON *.* FROM "' . $username . '"@"' . $hostname . '";';
    $sql_query2 = 'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON *.* TO "' . $username . '"@"' . $hostname . '"';
    if (isset($Grant_priv) || isset($max_questions) || isset($max_connections) || isset($max_updates)) {
        $sql_query2 .= 'WITH';
        if (isset($Grant_priv) && $Grant_priv == 'Y') {
            $sql_query2 .= ' GRANT OPTION';
        }
        if (isset($max_questions)) {
            $sql_query2 .= ' MAX_QUERIES_PER_HOUR ' . (int)$max_questions;
        }
        if (isset($max_connections)) {
            $sql_query2 .= ' MAX_CONNECTIONS_PER_HOUR ' . (int)$max_connections;
        }
        if (isset($max_updates)) {
            $sql_query2 .= ' MAX_UPDATES_PER_HOUR ' . (int)$max_updates;
        }
    }
    $sql_query2 .= ';';
    if (!@PMA_mysql_query($sql_query0, $userlink)) {
        PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query0);
    }
    if (!@PMA_mysql_query($sql_query1, $userlink)) {
        PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query1);
    }
    if (!@PMA_mysql_query($sql_query2, $userlink)) {
        PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query2);
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1 . ' ' . $sql_query2;
    $message = sprintf($strUpdatePrivMessage, '\'' . $username . '\'@\'' . $hostname . '\'');
}

/**
 * Reloads the privilege tables into memory
 */
if (!empty($flush_privileges)) {
    $sql_query = 'FLUSH PRIVILEGES';
    if (@PMA_mysql_query($sql_query, $userlink)) {
        $message = $strPrivilegesReloaded;
    } else {
        PMA_mysqlDie(PMA_mysql_error($userlink));
    }
}

/**
 * Does the common work
 */
require('./server_common.inc.php3');

/**
 * Displays the links
 */
require('./server_links.inc.php3');

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$is_superuser) {
    echo '<h2>' . "\n"
       . '    ' . $strUsers . "\n"
       . '</h2>' . "\n"
       . $strNoPrivileges . "\n";
    include('./footer.inc.php3');
    exit;
}

if (!isset($username) && !isset($hostname)) {
    // No username is given --> display the overview
    echo '<h2>' . "\n"
       . '    ' . $strUserOverview . "\n"
       . '</h2>' . "\n";
    $oldPrivTables = FALSE;
    if (PMA_MYSQL_INT_VERSION >= 40002) {
        $res = PMA_mysql_query('SELECT `User`, `Host`, IF(`Password` = "", "N", "Y") AS "Password", `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Reload_priv`, `Shutdown_priv`, `Process_priv`, `File_priv`, `Grant_priv`, `References_priv`, `Index_priv`, `Alter_priv`, `Show_db_priv`, `Super_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Execute_priv`, `Repl_slave_priv`, `Repl_client_priv` FROM `user`;', $userlink);
        if (!$res) {
            // the query failed! This may have two reasons:
            // - the user has not enough privileges
            // - the privilege tables use a structure of an earlier version.
            $oldPrivTables = TRUE;
        }
    }
    if (!isset($res) || empty($res) || (PMA_MYSQL_INT_VERSION >= 32211 && PMA_MYSQL_INT_VERSION < 40002)) {
        $res = PMA_mysql_query('SELECT `User`, `Host`, IF(`Password` = "", "N", "Y") AS "Password", `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Index_priv`, `Alter_priv`, `Create_priv`, `Drop_priv`, `Grant_priv`, `References_priv`, `Reload_priv`, `Shutdown_priv`, `Process_priv`, `File_priv` FROM `user`;', $userlink);
        if (!$res) {
            // the query failed! This may have two reasons:
            // - the user has not enough privileges
            // - the privilege tables use a structure of an earlier version.
            $oldPrivTables = TRUE;
        }
    }
    if (!$res || PMA_MYSQL_INT_VERSION < 32211) {
        $res = PMA_mysql_query('SELECT * FROM `user`;', $userlink);
    }
    if (!$res) {
        echo '<i>' . $strNoPrivileges . '</i>' . "\n";
        @mysql_free_result($res);
        unset($res);
    } else {
        if ($oldPrivTables) {
            // rabus: This message is hardcoded because I will replace it by
            // a automatic repair feature soon.
            echo '<div class="warning">' . "\n"
               . '    Warning: Your privilege table structure seem to be older than this MySQL version!<br />' . "\n"
               . '    Please run the script <tt>mysql_fix_privilege_tables</tt> that should be included in your MySQL server distribution to solve this problem!' . "\n"
               . '</div><br />' . "\n";
        }
        echo '<table border="0">' . "\n"
           . '    <tr>' . "\n"
           . '        <th></th>' . "\n"
           . '        <th>&nbsp;' . $strUser . '&nbsp;</th>' . "\n"
           . '        <th>&nbsp;' . $strHost . '&nbsp;</th>' . "\n"
           . '        <th>&nbsp;' . $strPassword . '&nbsp;</th>' . "\n"
           . '        <th>&nbsp;' . $strGlobalPrivileges . '&nbsp;</th>' . "\n"
           . '        <th>&nbsp;' . $strGrantOption . '&nbsp;</th>' . "\n"
           . '        <th>&nbsp;' . $strAction . '&nbsp;</th>' . "\n";
        echo '    </tr>' . "\n";
        $useBgcolorOne = TRUE;
        while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
            echo '    <tr>' . "\n"
               . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"></td>' . "\n"
               . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . (empty($row['User']) ? '<span style="color: #FF0000">' . $strAny . '</span>' : htmlspecialchars($row['User'])) . '</td>' . "\n"
               . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . htmlspecialchars($row['Host']) . '</td>' . "\n";
            $privs = PMA_extractPrivInfo($row, TRUE);
            echo '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . ($row['Password'] == 'Y' ? $strYes : '<span style="color: #FF0000">' . $strNo . '</span>') . '</td>' . "\n"
               . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><tt>' . "\n"
               . '            ' . join(',' . "\n" . '            ', $privs) . "\n"
               . '        </tt></td>' . "\n"
               . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . ($row['Grant_priv'] == 'Y' ? $strYes : $strNo) . '</td>' . "\n"
               . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php3?' . $url_query . '&amp;username=' . urlencode($row['User']) . ($row['Host'] == '%' ? '' : '&amp;hostname=' . urlencode($row['Host'])) . '">' . $strEdit . '</a></td>' . "\n"
               . '    </tr>' . "\n";
            $useBgcolorOne = !$useBgcolorOne;
        }
        @mysql_free_result($res);
        unset($res);
        unset ($row);
        echo '    <tr>' . "\n"
           . '        <td colspan="6">' . "\n"
           . '            &nbsp;<i>' . $strEnglishPrivileges . '</i>&nbsp;' . "\n"
           . '        </td>' . "\n"
           . '    </tr>' . "\n"
           . '</table><br />' . "\n"
           . '<div>' . "\n"
           . '    ' . sprintf($strFlushPrivilegesNote, '<a href="server_privileges.php3?' . $url_query . '&amp;flush_privileges=1">', '</a>');
    }
} else if (isset($username)) {
    if (!isset($hostname)) {
        $hostname = '%';
    }
    echo '<h2>' . "\n"
       . '    ' . $strEditPrivileges . '<br />' . "\n"
       . '</h2>' . "\n"
       . '<h3>' . "\n"
       . '   ' . $strUser . ' <i>\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'</i>' . "\n"
       . '</h3>' . "\n";
    $res = PMA_mysql_query('SELECT * FROM `user` WHERE `User` = "' . $username . '" AND `Host` = "' . $hostname . '"', $userlink);
    $row = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
    @mysql_free_result($res);
    unset($res);
    $privTable[0] = array(
        array('Select', 'SELECT', $strPrivDescSelect),
        array('Insert', 'INSERT', $strPrivDescInsert),
        array('Update', 'UPDATE', $strPrivDescUpdate),
        array('Delete', 'DELETE', $strPrivDescDelete),
        array('File', 'FILE', $strPrivDescFile)
    );
    if (isset($row['Lock_tables_priv'])) {
        $privDataAccess[1][] = array('Lock_tables', 'LOCK&nbsp;TABLES', $strPrivDescLockTables);
    }
    $privTable[1] = array(
        array('Create', 'CREATE', $strPrivDescCreateDb),
        array('Alter', 'ALTER', $strPrivDescAlter),
        array('Index', 'INDEX', $strPrivDescIndex),
        array('Drop', 'DROP', $strPrivDescDropDb)
    );
    if (isset($row['Create_tmp_table_priv'])) {
        $privTable[1][] = array('Create_tmp_table', 'CREATE&nbsp;TEMPORARAY&nbsp;TABLES', $strPrivDescCreateTmpTable);
    }
    $privTable[2] = array();
    if (isset($row['Grant_priv'])) {
        $privTable[2][] = array('Grant', 'GRANT', $strPrivDescGrant);
    }
    if (isset($row['Super_priv'])) {
        $privTable[2][] = array('Super', 'SUPER', $strPrivDescSuper);
        $privTable[2][] = array('Process', 'PROCESS', $strPrivDescProcess4);
    } else {
        $privTable[2][] = array('Process', 'PROCESS', $strPrivDescProcess3);
    }
    $privTable[2][] = array('Reload', 'RELOAD', $strPrivDescReload);
    $privTable[2][] = array('Shutdown', 'SHUTDOWN', $strPrivDescShutdown);
    if (isset($row['Show_db_priv'])) {
        $privTable[2][] = array('Show_db', 'SHOW&nbsp;DATABASES', $strPrivDescShowDb);
    }
    $privTable[2][] = array('References', 'REFERENCES', $strPrivDescReferences);
    if (isset($row['Execute_priv'])) {
        $privTable[2][] = array('Execute', 'EXECUTE', $strPrivDescExecute);
    }
    if (isset($row['Repl_client_priv'])) {
        $privTable[2][] = array('Repl_client', 'REPLICATION&nbsp;CLIENT', $strPrivDescReplClient);
    }
    if (isset($row['Repl_slave_priv'])) {
        $privTable[2][] = array('Repl_slave', 'REPLICATION&nbsp;SLAVE', $strPrivDescReplSlave);
    }
    echo '<form action="server_privileges.php3" method="post">' . "\n"
       . '    <input type="hidden" name="lang" value="' . $lang . '" />' . "\n"
       . '    <input type="hidden" name="convcharset" value="' . $convcharset . '" />' . "\n"
       . '    <input type="hidden" name="server" value="' . $server . '" />' . "\n"
       . '    <input type="hidden" name="username" value="' . urlencode($username) . '" />' . "\n";
    if ($hostname != '%') {
        echo '    <input type="hidden" name="hostname" value="' . urlencode($hostname) . '" />' . "\n";
    }
    echo '    <input type="hidden" name="grant_count" value="' . (count($privTable[0]) + count($privTable[1]) + count($privTable[2])) . '" />' . "\n"
       . '    <table border="0">' . "\n"
       . '        <tr>' . "\n"
       . '            <th colspan="6">&nbsp;' . $strGlobalPrivileges . '&nbsp;</th>' . "\n"
       . '        </tr>' . "\n"
       . '        <tr>' . "\n"
       . '            <td bgcolor="' . $cfg['BgcolorTwo'] . '" colspan="6"><small><i>' . $strEnglishPrivileges . '</i></small></th>' . "\n"
       . '        </tr>' . "\n"
       . '        <tr>'
       . '            <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2">&nbsp;<b><i>' . $strData . '</i></b>&nbsp;</td>' . "\n"
       . '            <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2">&nbsp;<b><i>' . $strStructure . '</i></b>&nbsp;</td>' . "\n"
       . '            <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2">&nbsp;<b><i>' . $strAdministration . '</i></b>&nbsp;</td>' . "\n"
       . '        </tr>' . "\n";
    $limitTable = FALSE;
    for ($i = 0; isset($privTable[0][$i]) || isset($privTable[1][$i]) || isset($privTable[2][$i]); $i++) {
        echo '        <tr>' . "\n";
        for ($j = 0; $j < 3; $j++) {
            if (isset($privTable[$j][$i])) {
                echo '            <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="checkbox" name="' . $privTable[$j][$i][0] . '_priv" id="checkbox_' . $privTable[$j][$i][0] . '_priv" value="Y" ' . ($row[$privTable[$j][$i][0] . '_priv'] == 'Y' ? 'checked="checked" ' : '') . 'title="' . $privTable[$j][$i][2] . '"/></td>' . "\n"
                   . '            <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="checkbox_' . $privTable[$j][$i][0] . '_priv"><tt><dfn title="' . $privTable[$j][$i][2] . '">' . $privTable[$j][$i][1] . '</dfn></tt></label></td>' . "\n";
            } else if (!isset($privTable[0][$i]) && !isset($privTable[1][$i])
                && isset($row['max_questions']) && isset($row['max_updates']) && isset($row['max_connections'])
                && !$limitTable) {
                echo '            <td colspan="4" rowspan="' . (count($privTable[2]) - $i) . '">' . "\n"
                   . '                <table border="0">' . "\n"
                   . '                    <tr>' . "\n"
                   . '                        <th colspan="2">&nbsp;' . $strResourceLimits . '&nbsp;</th>' . "\n"
                   . '                    </tr>' . "\n"
                   . '                    <tr>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '" colspan="2"><small><i>' . $strZeroRemovesTheLimit . '</i></small></td>' . "\n"
                   . '                    </tr>' . "\n"
                   . '                    <tr>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="text_max_questions"><tt><dfn title="' . $strPrivDescMaxQuestions . '">MAX&nbsp;QUERIES&nbsp;PER&nbsp;HOUR</dfn></tt></label></td>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="text" name="max_questions" id="text_max_questions" value="' . $row['max_questions'] . '" size="11" maxlength="11" title="' . $strPrivDescMaxQuestions . '" /></td>' . "\n"
                   . '                    </tr>' . "\n"
                   . '                    <tr>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="text_max_updates"><tt><dfn title="' . $strPrivDescMaxUpdates . '">MAX&nbsp;UPDATES&nbsp;PER&nbsp;HOUR</dfn></tt></label></td>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="text" name="max_updates" id="text_max_updates" value="' . $row['max_updates'] . '" size="11" maxlength="11" title="' . $strPrivDescMaxUpdates . '" /></td>' . "\n"
                   . '                    </tr>' . "\n"
                   . '                    <tr>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="text_max_connections"><tt><dfn title="' . $strPrivDescMaxConnections . '">MAX&nbsp;CONNECTIONS&nbsp;PER&nbsp;HOUR</dfn></tt></label></td>' . "\n"
                   . '                        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="text" name="max_connections" id="text_max_connections" value="' . $row['max_connections'] . '" size="11" maxlength="11" title="' . $strPrivDescMaxConnections . '" /></td>' . "\n"
                   . '                    </tr>' . "\n"
                   . '                </table>' . "\n"
                   . '            </td>' . "\n";
                $limitTable = TRUE;
            } else if (!$limitTable) {
                echo '            <td colspan="2"></td>' . "\n";
            }
        }
        echo '        </tr>' . "\n";
    }
    echo '        <tr>' . "\n"
       . '            <td colspan="6" align="center">' . "\n"
       . '                <input type="submit" name="update_privs" value="' . $strGo . '" />' . "\n"
       . '            </td>' . "\n"
       . '        </tr>' . "\n"
       . '    </table>' . "\n"
       . '</form>' . "\n";
} else if (isset($hostname)) {
    // TODO: Host privilege editor
}

/**
 * Displays the footer
 */
require('./footer.inc.php3');

?>
