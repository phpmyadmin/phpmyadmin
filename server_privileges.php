<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
$js_to_run = 'server_privileges.js';
require('./server_common.inc.php');


/**
 * Checks if a dropdown box has been used for selecting a database / table
 */
if (!empty($pred_dbname)) {
    $dbname = $pred_dbname;
    unset($pred_dbname);
}
if (!empty($pred_tablename)) {
    $tablename = $pred_tablename;
    unset($pred_tablename);
}


/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$is_superuser) {
    require('./server_links.inc.php');
    echo '<h2>' . "\n"
       . '    ' . $strPrivileges . "\n"
       . '</h2>' . "\n"
       . $strNoPrivileges . "\n";
    require_once('./footer.inc.php');
}


/**
 * Extracts the privilege information of a priv table row
 *
 * @param   array    the row
 * @param   boolean  add <dfn> tag with tooltips
 *
 * @global  ressource  the database connection
 *
 * @return  array
 */
function PMA_extractPrivInfo($row = '', $enableHTML = FALSE)
{
    global $userlink;

    $grants = array(
        array('Select_priv', 'SELECT', $GLOBALS['strPrivDescSelect']),
        array('Insert_priv', 'INSERT', $GLOBALS['strPrivDescInsert']),
        array('Update_priv', 'UPDATE', $GLOBALS['strPrivDescUpdate']),
        array('Delete_priv', 'DELETE', $GLOBALS['strPrivDescDelete']),
        array('Create_priv', 'CREATE', $GLOBALS['strPrivDescCreateDb']),
        array('Drop_priv', 'DROP', $GLOBALS['strPrivDescDropDb']),
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
    if (!empty($row) && isset($row['Table_priv'])) {
        $sql_query = 'SHOW COLUMNS FROM `tables_priv` LIKE "Table_priv";';
        $res = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        unset($sql_query);
        $row1 = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
        mysql_free_result($res);
        $av_grants = explode ('\',\'' , substr($row1['Type'], 5, strlen($row1['Type']) - 7));
        unset($row1);
        $users_grants = explode(',', $row['Table_priv']);
        foreach ($av_grants as $current_grant) {
            $row[$current_grant . '_priv'] = in_array($current_grant, $users_grants) ? 'Y' : 'N';
        }
        unset($current_grant);
        unset($av_grants);
        unset($users_grants);
    }
    $privs = array();
    $allPrivileges = TRUE;
    foreach ($grants as $current_grant) {
        if ((!empty($row) && isset($row[$current_grant[0]])) || (empty($row) && isset($GLOBALS[$current_grant[0]]))) {
            if ((!empty($row) && $row[$current_grant[0]] == 'Y') || (empty($row) && ($GLOBALS[$current_grant[0]] == 'Y' || (is_array($GLOBALS[$current_grant[0]]) && count($GLOBALS[$current_grant[0]]) == $GLOBALS['column_count'] && empty($GLOBALS[$current_grant[0] . '_none']))))) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $current_grant[2] . '">' . str_replace(' ', '&nbsp;', $current_grant[1]) . '</dfn>';
                } else {
                    $privs[] = $current_grant[1];
                }
            } else if (!empty($GLOBALS[$current_grant[0]]) && is_array($GLOBALS[$current_grant[0]]) && empty($GLOBALS[$current_grant[0] . '_none'])) {
                if ($enableHTML) {
                    $priv_string = '<dfn title="' . $current_grant[2] . '">' . str_replace(' ', '&nbsp;', $current_grant[1]) . '</dfn>';
                } else {
                    $priv_string = $current_grant[1];
                }
                $privs[] = $priv_string . ' (`' . join('`, `', $GLOBALS[$current_grant[0]]) . '`)';
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
} // end of the 'PMA_extractPrivInfo()' function

/**
 * Displays the privileges form table
 *
 * @param   string     the database
 * @param   string     the table
 * @param   boolean    wheather to display the submit button or not
 * @param   int        the indenting level of the code
 *
 * @global  array      the phpMyAdmin configuration
 * @global  ressource  the database connection
 *
 * @return  void
 */
function PMA_displayPrivTable($db = '*', $table = '*', $submit = TRUE, $indent = 0)
{
    global $cfg, $userlink;

    if ($db == '*') {
        $table = '*';
    }
    $spaces = '';
    for ($i = 0; $i < $indent; $i++) {
        $spaces .= '    ';
    }
    if (isset($GLOBALS['username'])) {
        $username = $GLOBALS['username'];
        $hostname = $GLOBALS['hostname'];
        if ($db == '*') {
            $sql_query = 'SELECT * FROM `user` WHERE `User` = "' . PMA_sqlAddslashes($username) . '" AND `Host` = "' . $hostname . '";';
        } else if ($table == '*') {
            $sql_query = 'SELECT * FROM `db` WHERE `User` = "' . PMA_sqlAddslashes($username) . '" AND `Host` = "' . $hostname . '" AND `Db` = "' . $db . '";';
        } else {
            $sql_query = 'SELECT `Table_priv` FROM `tables_priv` WHERE `User` = "' . PMA_sqlAddslashes($username) . '" AND `Host` = "' . $hostname . '" AND `Db` = "' . $db . '" AND `Table_name` = "' . $table . '";';
        }
        $res = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        if ($res) {
            $row = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
        }
        @mysql_free_result($res);
    }
    if (empty($row)) {
        if ($table == '*') {
            if ($db == '*') {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`user`;';
            } else if ($table == '*') {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`db`;';
            }
            $res = PMA_mysql_query($sql_query, $userlink)
                or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
            while ($row1 = PMA_mysql_fetch_row($res)) {
                if (substr($row1[0], 0, 4) == 'max_') {
                    $row[$row1[0]] = 0;
                } else {
                    $row[$row1[0]] = 'N';
                }
            }
            mysql_free_result($res);
        } else {
            $row = array('Table_priv' => '');
        }
    }
    if (isset($row['Table_priv'])) {
        $sql_query = 'SHOW COLUMNS FROM `tables_priv` LIKE "Table_priv";';
        $res = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        unset($sql_query);
        $row1 = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
        mysql_free_result($res);
        $av_grants = explode ('\',\'' , substr($row1['Type'], strpos($row1['Type'], '(') + 2, strpos($row1['Type'], ')') - strpos($row1['Type'], '(') - 3));
        unset($row1);
        $users_grants = explode(',', $row['Table_priv']);
        foreach ($av_grants as $current_grant) {
            $row[$current_grant . '_priv'] = in_array($current_grant, $users_grants) ? 'Y' : 'N';
        }
        unset($row['Table_priv']);
        unset($current_grant);
        unset($av_grants);
        unset($users_grants);
        if ($res = PMA_mysql_query('SHOW COLUMNS FROM `' . $db . '`.`' . $table . '`;', $userlink)) {
            $columns = array();
            while ($row1 = PMA_mysql_fetch_row($res)) {
                $columns[$row1[0]] = array(
                    'Select' => FALSE,
                    'Insert' => FALSE,
                    'Update' => FALSE,
                    'References' => FALSE
                );
            }
            mysql_free_result($res);
            unset($res);
            unset($row1);
        }
    }
    if (!empty($columns)) {
        $sql_query = 'SELECT `Column_name`, `Column_priv` FROM `columns_priv` WHERE `User` = "' . PMA_sqlAddslashes($username) . '" AND `Host` = "' . $hostname . '" AND `Db` = "' . $db . '" AND `Table_name` = "' . $table . '";';
        $res = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        while ($row1 = PMA_mysql_fetch_row($res)) {
            $row1[1] = explode(',', $row1[1]);
            foreach ($row1[1] as $current) {
                $columns[$row1[0]][$current] = TRUE;
            }
        }
        mysql_free_result($res);
        unset($res);
        unset($row1);
        unset($current);
        echo $spaces . '<input type="hidden" name="grant_count" value="' . count($row) . '" />' . "\n"
           . $spaces . '<input type="hidden" name="column_count" value="' . count($columns) . '" />' . "\n"
           . $spaces . '<table border="0">' . "\n"
           . $spaces . '    <tr>' . "\n"
           . $spaces . '        <th colspan="6">&nbsp;' . $GLOBALS['strTblPrivileges'] . '&nbsp;</th>' . "\n"
           . $spaces . '    </tr>' . "\n"
           . $spaces . '    <tr>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" colspan="6"><small><i>' . $GLOBALS['strEnglishPrivileges'] . '</i></small></td>' . "\n"
           . $spaces . '    </tr>' . "\n"
           . $spaces . '    <tr>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '">&nbsp;<tt><dfn title="' . $GLOBALS['strPrivDescSelect'] . '">SELECT</dfn></tt>&nbsp;</td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '">&nbsp;<tt><dfn title="' . $GLOBALS['strPrivDescInsert'] . '">INSERT</dfn></tt>&nbsp;</td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '">&nbsp;<tt><dfn title="' . $GLOBALS['strPrivDescUpdate'] . '">UPDATE</dfn></tt>&nbsp;</td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '">&nbsp;<tt><dfn title="' . $GLOBALS['strPrivDescReferences'] . '">REFERENCES</dfn></tt>&nbsp;</td>' . "\n";
        list($current_grant, $current_grant_value) = each($row);
        while (in_array(substr($current_grant, 0, (strlen($current_grant) - 5)), array('Select', 'Insert', 'Update', 'References'))) {
            list($current_grant, $current_grant_value) = each($row);
        }
        echo $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="checkbox" name="' . $current_grant . '" id="checkbox_' . $current_grant . '" value="Y" ' . ($current_grant_value == 'Y' ? 'checked="checked" ' : '') . 'title="' . (isset($GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5)) . 'Tbl']) . '"/></td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="checkbox_' . $current_grant . '"><tt><dfn title="' . (isset($GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5)) . 'Tbl']) . '">' . strtoupper(substr($current_grant, 0, strlen($current_grant) - 5)) . '</dfn></tt></label></td>' . "\n"
           . $spaces . '    </tr>' . "\n"
           . $spaces . '    <tr>' . "\n";
        $rowspan = count($row) - 5;
        echo $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" rowspan="' . $rowspan . '" valign="top">' . "\n"
           . $spaces . '            <select name="Select_priv[]" multiple="multiple">' . "\n";
        foreach ($columns as $current_column => $current_column_privileges) {
            echo $spaces . '                <option value="' . htmlspecialchars($current_column) . '"';
            if ($row['Select_priv'] == 'Y' || $current_column_privileges['Select']) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($current_column) . '</option>' . "\n";
        }
        echo $spaces . '            </select><br />' . "\n"
           . $spaces . '            <i>' . $GLOBALS['strOr'] . '</i><br />' . "\n"
           . $spaces . '            <input type="checkbox" name="Select_priv_none" id="checkbox_Select_priv_none" title="' . $GLOBALS['strNone'] . '" />' . "\n"
           . $spaces . '            <label for="checkbox_Select_priv_none">' . $GLOBALS['strNone'] . '</label>' . "\n"
           . $spaces . '        </td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" rowspan="' . $rowspan . '" valign="top">' . "\n"
           . $spaces . '            <select name="Insert_priv[]" multiple="multiple">' . "\n";
        foreach ($columns as $current_column => $current_column_privileges) {
            echo $spaces . '                <option value="' . htmlspecialchars($current_column) . '"';
            if ($row['Insert_priv'] == 'Y' || $current_column_privileges['Insert']) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($current_column) . '</option>' . "\n";
        }
        echo $spaces . '            </select><br />' . "\n"
           . $spaces . '            <i>' . $GLOBALS['strOr'] . '</i><br />' . "\n"
           . $spaces . '            <input type="checkbox" name="Insert_priv_none" id="checkbox_Insert_priv_none" title="' . $GLOBALS['strNone'] . '" />' . "\n"
           . $spaces . '            <label for="checkbox_Insert_priv_none">' . $GLOBALS['strNone'] . '</label>' . "\n"
           . $spaces . '        </td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" rowspan="' . $rowspan . '" valign="top">' . "\n"
           . $spaces . '            <select name="Update_priv[]" multiple="multiple">' . "\n";
        foreach ($columns as $current_column => $current_column_privileges) {
            echo $spaces . '                <option value="' . htmlspecialchars($current_column) . '"';
            if ($row['Update_priv'] == 'Y' || $current_column_privileges['Update']) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($current_column) . '</option>' . "\n";
        }
        echo $spaces . '            </select><br />' . "\n"
           . $spaces . '            <i>' . $GLOBALS['strOr'] . '</i><br />' . "\n"
           . $spaces . '            <input type="checkbox" name="Update_priv_none" id="checkbox_Update_priv_none" title="' . $GLOBALS['strNone'] . '" />' . "\n"
           . $spaces . '            <label for="checkbox_Update_priv_none">' . $GLOBALS['strNone'] . '</label>' . "\n"
           . $spaces . '        </td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" rowspan="' . $rowspan . '" valign="top">' . "\n"
           . $spaces . '            <select name="References_priv[]" multiple="multiple">' . "\n";
        foreach ($columns as $current_column => $current_column_privileges) {
            echo $spaces . '                <option value="' . htmlspecialchars($current_column) . '"';
            if ($row['References_priv'] == 'Y' || $current_column_privileges['References']) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($current_column) . '</option>' . "\n";
        }
        echo $spaces . '            </select><br />' . "\n"
           . $spaces . '            <i>' . $GLOBALS['strOr'] . '</i><br />' . "\n"
           . $spaces . '            <input type="checkbox" name="References_priv_none" id="checkbox_References_priv_none" title="' . $GLOBALS['strNone'] . '" />' . "\n"
           . $spaces . '            <label for="checkbox_References_priv_none">' . $GLOBALS['strNone'] . '</label>' . "\n"
           . $spaces . '        </td>' . "\n";
        unset($rowspan);
        list($current_grant, $current_grant_value) = each($row);
        while (in_array(substr($current_grant, 0, (strlen($current_grant) - 5)), array('Select', 'Insert', 'Update', 'References'))) {
            list($current_grant, $current_grant_value) = each($row);
        }
        echo $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="checkbox" name="' . $current_grant . '" id="checkbox_' . $current_grant . '" value="Y" ' . ($current_grant_value == 'Y' ? 'checked="checked" ' : '') . 'title="' . (isset($GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5)) . 'Tbl']) . '"/></td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="checkbox_' . $current_grant . '"><tt><dfn title="' . (isset($GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5)) . 'Tbl']) . '">' . strtoupper(substr($current_grant, 0, strlen($current_grant) - 5)) . '</dfn></tt></label></td>' . "\n"
           . $spaces . '    </tr>' . "\n";
        while (list($current_grant, $current_grant_value) = each($row)) {
            if (in_array(substr($current_grant, 0, (strlen($current_grant) - 5)), array('Select', 'Insert', 'Update', 'References'))) {
                continue;
            }
            echo $spaces . '    <tr>' . "\n"
               . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="checkbox" name="' . $current_grant . '" id="checkbox_' . $current_grant . '" value="Y" ' . ($current_grant_value == 'Y' ? 'checked="checked" ' : '') . 'title="' . (isset($GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5)) . 'Tbl']) . '"/></td>' . "\n"
               . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="checkbox_' . $current_grant . '"><tt><dfn title="' . (isset($GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($current_grant, 0, (strlen($current_grant) - 5)) . 'Tbl']) . '">' . strtoupper(substr($current_grant, 0, strlen($current_grant) - 5)) . '</dfn></tt></label></td>' . "\n"
               . $spaces . '    </tr>' . "\n";
        }
    } else {
        $privTable[0] = array(
            array('Select', 'SELECT', $GLOBALS['strPrivDescSelect']),
            array('Insert', 'INSERT', $GLOBALS['strPrivDescInsert']),
            array('Update', 'UPDATE', $GLOBALS['strPrivDescUpdate']),
            array('Delete', 'DELETE', $GLOBALS['strPrivDescDelete'])
        );
        if ($db == '*') {
            $privTable[0][] = array('File', 'FILE', $GLOBALS['strPrivDescFile']);
        }
        $privTable[1] = array(
            array('Create', 'CREATE', ($table == '*' ? $GLOBALS['strPrivDescCreateDb'] : $GLOBALS['strPrivDescCreateTbl'])),
            array('Alter', 'ALTER', $GLOBALS['strPrivDescAlter']),
            array('Index', 'INDEX', $GLOBALS['strPrivDescIndex']),
            array('Drop', 'DROP', ($table == '*' ? $GLOBALS['strPrivDescDropDb'] : $GLOBALS['strPrivDescDropTbl']))
        );
        if (isset($row['Create_tmp_table_priv'])) {
            $privTable[1][] = array('Create_tmp_table', 'CREATE&nbsp;TEMPORARY&nbsp;TABLES', $GLOBALS['strPrivDescCreateTmpTable']);
        }
        $privTable[2] = array();
        if (isset($row['Grant_priv'])) {
            $privTable[2][] = array('Grant', 'GRANT', $GLOBALS['strPrivDescGrant']);
        }
        if ($db == '*') {
            if (isset($row['Super_priv'])) {
                $privTable[2][] = array('Super', 'SUPER', $GLOBALS['strPrivDescSuper']);
                $privTable[2][] = array('Process', 'PROCESS', $GLOBALS['strPrivDescProcess4']);
            } else {
                $privTable[2][] = array('Process', 'PROCESS', $GLOBALS['strPrivDescProcess3']);
            }
            $privTable[2][] = array('Reload', 'RELOAD', $GLOBALS['strPrivDescReload']);
            $privTable[2][] = array('Shutdown', 'SHUTDOWN', $GLOBALS['strPrivDescShutdown']);
            if (isset($row['Show_db_priv'])) {
                $privTable[2][] = array('Show_db', 'SHOW&nbsp;DATABASES', $GLOBALS['strPrivDescShowDb']);
            }
        }
        if (isset($row['Lock_tables_priv'])) {
            $privTable[2][] = array('Lock_tables', 'LOCK&nbsp;TABLES', $GLOBALS['strPrivDescLockTables']);
        }
        $privTable[2][] = array('References', 'REFERENCES', $GLOBALS['strPrivDescReferences']);
        if ($db == '*') {
            if (isset($row['Execute_priv'])) {
                $privTable[2][] = array('Execute', 'EXECUTE', $GLOBALS['strPrivDescExecute']);
            }
            if (isset($row['Repl_client_priv'])) {
                $privTable[2][] = array('Repl_client', 'REPLICATION&nbsp;CLIENT', $GLOBALS['strPrivDescReplClient']);
            }
            if (isset($row['Repl_slave_priv'])) {
                $privTable[2][] = array('Repl_slave', 'REPLICATION&nbsp;SLAVE', $GLOBALS['strPrivDescReplSlave']);
            }
        }
        echo $spaces . '<input type="hidden" name="grant_count" value="' . (count($privTable[0]) + count($privTable[1]) + count($privTable[2]) - (isset($row['Grant_priv']) ? 1 : 0)) . '" />' . "\n"
           . $spaces . '<table border="0">' . "\n"
           . $spaces . '    <tr>' . "\n"
           . $spaces . '        <th colspan="6">&nbsp;' . ($db == '*' ? $GLOBALS['strGlobalPrivileges'] : ($table == '*' ? $GLOBALS['strDbPrivileges'] : $GLOBALS['strTblPrivileges'])) . '&nbsp;</th>' . "\n"
           . $spaces . '    </tr>' . "\n"
           . $spaces . '    <tr>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" colspan="6"><small><i>' . $GLOBALS['strEnglishPrivileges'] . '</i></small></td>' . "\n"
           . $spaces . '    </tr>' . "\n"
           . $spaces . '    <tr>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2">&nbsp;<b><i>' . $GLOBALS['strData'] . '</i></b>&nbsp;</td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2">&nbsp;<b><i>' . $GLOBALS['strStructure'] . '</i></b>&nbsp;</td>' . "\n"
           . $spaces . '        <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2">&nbsp;<b><i>' . $GLOBALS['strAdministration'] . '</i></b>&nbsp;</td>' . "\n"
           . $spaces . '    </tr>' . "\n";
        $limitTable = FALSE;
        for ($i = 0; isset($privTable[0][$i]) || isset($privTable[1][$i]) || isset($privTable[2][$i]); $i++) {
            echo $spaces . '    <tr>' . "\n";
            for ($j = 0; $j < 3; $j++) {
                if (isset($privTable[$j][$i])) {
                    echo $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="checkbox" name="' . $privTable[$j][$i][0] . '_priv" id="checkbox_' . $privTable[$j][$i][0] . '_priv" value="Y" ' . ($row[$privTable[$j][$i][0] . '_priv'] == 'Y' ? 'checked="checked" ' : '') . 'title="' . $privTable[$j][$i][2] . '"/></td>' . "\n"
                       . $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="checkbox_' . $privTable[$j][$i][0] . '_priv"><tt><dfn title="' . $privTable[$j][$i][2] . '">' . $privTable[$j][$i][1] . '</dfn></tt></label></td>' . "\n";
                } else if ($db == '*' && !isset($privTable[0][$i]) && !isset($privTable[1][$i])
                    && isset($row['max_questions']) && isset($row['max_updates']) && isset($row['max_connections'])
                    && !$limitTable) {
                    echo $spaces . '        <td colspan="4" rowspan="' . (count($privTable[2]) - $i) . '">' . "\n"
                       . $spaces . '            <table border="0">' . "\n"
                       . $spaces . '                <tr>' . "\n"
                       . $spaces . '                    <th colspan="2">&nbsp;' . $GLOBALS['strResourceLimits'] . '&nbsp;</th>' . "\n"
                       . $spaces . '                </tr>' . "\n"
                       . $spaces . '                <tr>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '" colspan="2"><small><i>' . $GLOBALS['strZeroRemovesTheLimit'] . '</i></small></td>' . "\n"
                       . $spaces . '                </tr>' . "\n"
                       . $spaces . '                <tr>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="text_max_questions"><tt><dfn title="' . $GLOBALS['strPrivDescMaxQuestions'] . '">MAX&nbsp;QUERIES&nbsp;PER&nbsp;HOUR</dfn></tt></label></td>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="text" class="textfield" name="max_questions" id="text_max_questions" value="' . $row['max_questions'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxQuestions'] . '" /></td>' . "\n"
                       . $spaces . '                </tr>' . "\n"
                       . $spaces . '                <tr>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="text_max_updates"><tt><dfn title="' . $GLOBALS['strPrivDescMaxUpdates'] . '">MAX&nbsp;UPDATES&nbsp;PER&nbsp;HOUR</dfn></tt></label></td>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="text" class="textfield" name="max_updates" id="text_max_updates" value="' . $row['max_updates'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxUpdates'] . '" /></td>' . "\n"
                       . $spaces . '                </tr>' . "\n"
                       . $spaces . '                <tr>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="text_max_connections"><tt><dfn title="' . $GLOBALS['strPrivDescMaxConnections'] . '">MAX&nbsp;CONNECTIONS&nbsp;PER&nbsp;HOUR</dfn></tt></label></td>' . "\n"
                       . $spaces . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="text" class="textfield" name="max_connections" id="text_max_connections" value="' . $row['max_connections'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxConnections'] . '" /></td>' . "\n"
                       . $spaces . '                </tr>' . "\n"
                       . $spaces . '            </table>' . "\n"
                       . $spaces . '        </td>' . "\n";
                    $limitTable = TRUE;
                } else if (!$limitTable) {
                    echo $spaces . '        <td bgcolor="' . $cfg['BgcolorTwo'] . '" colspan="2">&nbsp;</td>' . "\n";
                }
            }
        }
        echo $spaces . '    </tr>' . "\n";
    }
    if ($submit) {
        echo $spaces . '    <tr>' . "\n"
           . $spaces . '        <td colspan="6" align="center">' . "\n"
           . $spaces . '            <input type="submit" name="update_privs" value="' . $GLOBALS['strGo'] . '" />' . "\n"
           . $spaces . '        </td>' . "\n"
           . $spaces . '    </tr>' . "\n";
    }
    echo $spaces . '</table>' . "\n";
} // end of the 'PMA_displayPrivTable()' function


/**
 * Displays the fields used by the "new user" form as well as the
 * "change login information / copy user" form.
 *
 * @param   string     are we creating a new user or are we just changing one?
 *                     (allowed values: 'new', 'change')
 * @param   int        the indenting level of the code
 *
 * @global  array      the phpMyAdmin configuration
 * @global  ressource  the database connection
 *
 * @return  void
 */
function PMA_displayLoginInformationFields($mode = 'new', $indent = 0)
{
    global $cfg, $userlink;
    $spaces = '';
    for ($i = 0; $i < $indent; $i++) {
        $spaces .= '    ';
    }
    echo $spaces . '<tr>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <label for="select_pred_username">' . "\n"
       . $spaces . '            ' . $GLOBALS['strUserName'] . ':' . "\n"
       . $spaces . '        </label>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <select name="pred_username" id="select_pred_username" title="' . $GLOBALS['strUserName'] . '"' . "\n"
       . $spaces . '            onchange="if (this.value == \'any\') { username.value = \'\'; } else if (this.value == \'userdefined\') { username.focus(); username.select(); }">' . "\n"
       . $spaces . '            <option value="any"' . ((isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] == 'any') ? ' selected="selected"' : '') . '>' . $GLOBALS['strAnyUser'] . '</option>' . "\n"
       . $spaces . '            <option value="userdefined"' . ((!isset($GLOBALS['pred_username']) || $GLOBALS['pred_username'] == 'userdefined') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
       . $spaces . '        </select>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <input type="text" class="textfield" name="username" class="textfield" title="' . $GLOBALS['strUserName'] . '"' . (empty($GLOBALS['username']) ? '' : ' value="' . (isset($GLOBALS['new_username']) ? $GLOBALS['new_username'] : $GLOBALS['username']) . '"') . ' onchange="pred_username.value = \'userdefined\';" />' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '</tr>' . "\n"
       . $spaces . '<tr>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <label for="select_pred_hostname">' . "\n"
       . $spaces . '            ' . $GLOBALS['strHost'] . ':' . "\n"
       . $spaces . '        </label>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <select name="pred_hostname" id="select_pred_hostname" title="' . $GLOBALS['strHost'] . '"' . "\n";
    $res = PMA_mysql_query('SELECT USER();', $userlink);
    $row = @PMA_mysql_fetch_row($res);
    @mysql_free_result($res);
    unset($res);
    if (!empty($row[0])) {
        $thishost = str_replace("'", '', substr($row[0], (strrpos($row[0], '@') + 1)));
        if ($thishost == 'localhost' || $thishost == '127.0.0.1') {
            unset($thishost);
        }
    }
    echo $spaces . '            onchange="if (this.value == \'any\') { hostname.value = \'%\'; } else if (this.value == \'localhost\') { hostname.value = \'localhost\'; } '
       . (empty($thishost) ? '' : 'else if (this.value == \'thishost\') { hostname.value = \'' . addslashes(htmlspecialchars($thishost)) . '\'; } ')
       . 'else if (this.value == \'hosttable\') { hostname.value = \'\'; } else if (this.value == \'userdefined\') { hostname.focus(); hostname.select(); }">' . "\n";
    unset($row);
    echo $spaces . '            <option value="any"' . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'any') ? ' selected="selected"' : '') . '>' . $GLOBALS['strAnyHost'] . '</option>' . "\n"
       . $spaces . '            <option value="localhost"' . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'localhost') ? ' selected="selected"' : '') . '>' . $GLOBALS['strLocalhost'] . '</option>' . "\n";
    if (!empty($thishost)) {
        echo $spaces . '            <option value="thishost"' . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'thishost') ? ' selected="selected"' : '') . '>' . $GLOBALS['strThisHost'] . '</option>' . "\n";
    }
    unset($thishost);
    echo $spaces . '            <option value="hosttable"' . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'hosttable') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseHostTable'] . '</option>' . "\n"
       . $spaces . '            <option value="userdefined"' . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'userdefined') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
       . $spaces . '        </select>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <input type="text" class="textfield" name="hostname" value="' . ( isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '' ) . '" class="textfield" title="' . $GLOBALS['strHost'] . '" onchange="pred_hostname.value = \'userdefined\';" />' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '</tr>' . "\n"
       . $spaces . '<tr>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <label for="select_pred_password">' . "\n"
       . $spaces . '            ' . $GLOBALS['strPassword'] . ':' . "\n"
       . $spaces . '        </label>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <select name="pred_password" id="select_pred_password" title="' . $GLOBALS['strPassword'] . '"' . "\n"
       . $spaces . '            onchange="if (this.value == \'none\') { pma_pw.value = \'\'; pma_pw2.value = \'\'; } else if (this.value == \'userdefined\') { pma_pw.focus(); pma_pw.select(); }">' . "\n"
       . ($mode == 'change' ? $spaces . '            <option value="keep" selected="selected">' . $GLOBALS['strKeepPass'] . '</option>' . "\n" : '')
       . $spaces . '            <option value="none">' . $GLOBALS['strNoPassword'] . '</option>' . "\n"
       . $spaces . '            <option value="userdefined"' . ($mode == 'change' ? '' : ' selected="selected"') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
       . $spaces . '        </select>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <input type="password" name="pma_pw" class="textfield" title="' . $GLOBALS['strPassword'] . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '</tr>' . "\n"
       . $spaces . '<tr>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <label for="text_pma_pw2">' . "\n"
       . $spaces . '            ' . $GLOBALS['strReType'] . ':' . "\n"
       . $spaces . '        </label>' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">&nbsp;</td>' . "\n"
       . $spaces . '    <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . $spaces . '        <input type="password" name="pma_pw2" id="text_pma_pw2" class="textfield" title="' . $GLOBALS['strReType'] . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
       . $spaces . '    </td>' . "\n"
       . $spaces . '</tr>' . "\n";
} // end of the 'PMA_displayUserAndHostFields()' function


/**
 * Changes / copies a user, part I
 */
if (!empty($change_copy)) {
    $local_query = 'SELECT * FROM `mysql`.`user` WHERE `User` = "' . PMA_sqlAddslashes($old_username) . '" AND `Host` = "' . $old_hostname . '";';
    $res = PMA_mysql_query($local_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $local_query);
    if (!$res) {
        $message = $strNoUsersFound;
        unset($change_copy);
    } else {
        $row = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
        extract($row, EXTR_OVERWRITE);
        mysql_free_result($res);
        $queries = array();
    }
}


/**
 * Adds a user
 *   (Changes / copies a user, part II)
 */
if (!empty($adduser_submit) || !empty($change_copy)) {
    unset($sql_query);
    if ($pred_username == 'any') {
        $username = '';
    }
    switch ($pred_hostname) {
        case 'any':
            $hostname = '%';
            break;
        case 'localhost':
            $hostname = 'localhost';
            break;
        case 'hosttable':
            $hostname = '';
            break;
        case 'thishost':
            $res = PMA_mysql_query('SELECT USER();', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SELECT USER();');
            $row = PMA_mysql_fetch_row($res);
            mysql_free_result($res);
            unset($res);
            $hostname = substr($row[0], (strrpos($row[0], '@') + 1));
            unset($row);
            break;
    }
    $local_query = 'SELECT "foo" FROM `user` WHERE `User` = "' . PMA_sqlAddslashes($username) . '" AND `Host` = "' . $hostname . '";';
    $res = PMA_mysql_query($local_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $local_query);
    unset($local_query);
    if (mysql_affected_rows($userlink) == 1) {
        $message = sprintf($strUserAlreadyExists, '<i>\'' . $username . '\'@\'' . $hostname . '\'</i>');
        $adduser = 1;
    } else {
        $real_sql_query = 'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON *.* TO "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '"';
        if ($pred_password != 'none' && $pred_password != 'keep') {
            $pma_pw_hidden = '';
            for ($i = 0; $i < strlen($pma_pw); $i++) {
                $pma_pw_hidden .= '*';
            }
            $sql_query = $real_sql_query . ' IDENTIFIED BY "' . $pma_pw_hidden . '"';
            $real_sql_query .= ' IDENTIFIED BY "' . $pma_pw . '"';
        } else {
            if ($pred_password == 'keep' && !empty($password)) {
                $real_sql_query .= ' IDENTIFIED BY PASSWORD "' . $password . '"';
            }
            $sql_query = $real_sql_query;
        }
        if ((isset($Grant_priv) && $Grant_priv == 'Y') || (PMA_MYSQL_INT_VERSION >= 40002 && (isset($max_questions) || isset($max_connections) || isset($max_updates)))) {
            $real_sql_query .= 'WITH';
            $sql_query .= 'WITH';
            if (isset($Grant_priv) && $Grant_priv == 'Y') {
                $real_sql_query .= ' GRANT OPTION';
                $sql_query .= ' GRANT OPTION';
            }
            if (PMA_MYSQL_INT_VERSION >= 40002) {
                if (isset($max_questions)) {
                    $real_sql_query .= ' MAX_QUERIES_PER_HOUR ' . (int)$max_questions;
                    $sql_query .= ' MAX_QUERIES_PER_HOUR ' . (int)$max_questions;
                }
                if (isset($max_connections)) {
                    $real_sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . (int)$max_connections;
                    $sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . (int)$max_connections;
                }
                if (isset($max_updates)) {
                    $real_sql_query .= ' MAX_UPDATES_PER_HOUR ' . (int)$max_updates;
                    $sql_query .= ' MAX_UPDATES_PER_HOUR ' . (int)$max_updates;
                }
            }
        }
        $real_sql_query .= ';';
        $sql_query .= ';';
        if (empty($change_copy)) {
            PMA_mysql_query($real_sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
            $message = $strAddUserMessage;
        } else {
            $queries[] = $sql_query;
        }
        unset($real_sql_query);
        mysql_free_result($res);
        unset($res);
    }
}


/**
 * Changes / copies a user, part III
 */
if (!empty($change_copy)) {
    $local_query = 'SELECT * FROM `mysql`.`db` WHERE `User` = "' . PMA_sqlAddslashes($old_username) . '" AND `Host` = "' . $old_hostname . '";';
    $res = PMA_mysql_query($local_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $local_query);
    while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
        $queries[] = 'GRANT ' . join(', ', PMA_extractPrivInfo($row)) . ' ON `' . $row['Db'] . '`.* TO "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '"' . ($row['Grant_priv'] == 'Y' ? ' WITH GRANT OPTION' : '') . ';';
    }
    mysql_free_result($res);
    $local_query = 'SELECT `Db`, `Table_name`, `Table_priv` FROM `mysql`.`tables_priv` WHERE `User` = "' . PMA_sqlAddslashes($old_username) . '" AND `Host` = "' . $old_hostname . '";';
    $res = PMA_mysql_query($local_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $local_query);
    while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
        $local_query = 'SELECT `Column_name`, `Column_priv` FROM `mysql`.`columns_priv` WHERE `User` = "' . PMA_sqlAddslashes($old_username) . '" AND `Host` = "' . $old_hostname . '" AND `Db` = "' . $row['Db'] . '";';
        $res2 = PMA_mysql_query($local_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $local_query);
        $tmp_privs1 = PMA_extractPrivInfo($row);
        $tmp_privs2 = array(
            'Select' => array(),
            'Insert' => array(),
            'Update' => array(),
            'References' => array()
        );
        while ($row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC)) {
            $tmp_array = explode(',', $row2['Column_priv']);
            if (in_array('Select', $tmp_array)) {
                $tmp_privs2['Select'][] = $row2['Column_name'];
            }
            if (in_array('Insert', $tmp_array)) {
                $tmp_privs2['Insert'][] = $row2['Column_name'];
            }
            if (in_array('Update', $tmp_array)) {
                $tmp_privs2['Update'][] = $row2['Column_name'];
            }
            if (in_array('References', $tmp_array)) {
                $tmp_privs2['References'][] = $row2['Column_name'];
            }
            unset($tmp_array);
        }
        if (count($tmp_privs2['Select']) > 0 && !in_array('SELECT', $tmp_privs1)) {
            $tmp_privs1[] = 'SELECT (`' . join('`, `', $tmp_privs2['Select']) . '`)';
        }
        if (count($tmp_privs2['Insert']) > 0 && !in_array('INSERT', $tmp_privs1)) {
            $tmp_privs1[] = 'INSERT (`' . join(', ', $tmp_privs2['Insert']) . '`)';
        }
        if (count($tmp_privs2['Update']) > 0 && !in_array('UPDATE', $tmp_privs1)) {
            $tmp_privs1[] = 'UPDATE (`' . join(', ', $tmp_privs2['Update']) . '`)';
        }
        if (count($tmp_privs2['References']) > 0 && !in_array('REFERENCES', $tmp_privs1)) {
            $tmp_privs1[] = 'REFERENCES (`' . join(', ', $tmp_privs2['References']) . '`)';
        }
        unset($tmp_privs2);
        $queries[] = 'GRANT ' . join(', ', $tmp_privs1) . ' ON `' . $row['Db'] . '`.`' . $row['Table_name'] . '` TO "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '"' . (in_array('Grant', explode(',', $row['Table_priv'])) ? ' WITH GRANT OPTION' : '') . ';';
    }
}


/**
 * Updates privileges
 */
if (!empty($update_privs)) {
    $db_and_table = empty($dbname) ? '*.*' : PMA_backquote($dbname) . '.' . (empty($tablename) ? '*' : PMA_backquote($tablename));
    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table . ' FROM "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '";';
    if (!isset($Grant_priv) || $Grant_priv != 'Y') {
        $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table . ' FROM "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '";';
    }
    $sql_query2 = 'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON ' . $db_and_table . ' TO "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '"';
    if ((isset($Grant_priv) && $Grant_priv == 'Y') || (empty($dbname) && PMA_MYSQL_INT_VERSION >= 40002 && (isset($max_questions) || isset($max_connections) || isset($max_updates)))) {
        $sql_query2 .= 'WITH';
        if (isset($Grant_priv) && $Grant_priv == 'Y') {
            $sql_query2 .= ' GRANT OPTION';
        }
        if (PMA_MYSQL_INT_VERSION >= 40002) {
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
    }
    $sql_query2 .= ';';
    PMA_mysql_query($sql_query0, $userlink); // this query may fail, but this does not matter :o)
    if (isset($sql_query1)) {
        PMA_mysql_query($sql_query1, $userlink); // this one may fail, too...
    }
    PMA_mysql_query($sql_query2, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query2);
    $sql_query = $sql_query0 . ' ' . (isset($sql_query1) ? $sql_query1 . ' ' : '') . $sql_query2;
    $message = sprintf($strUpdatePrivMessage, '\'' . $username . '\'@\'' . $hostname . '\'');
}


/**
 * Revokes Privileges
 */
if (!empty($revokeall)) {
    $db_and_table = PMA_backquote($dbname) . '.' . (empty($tablename) ? '*' : PMA_backquote($tablename));
    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table . ' FROM "' . $username . '"@"' . $hostname . '";';
    $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table . ' FROM "' . $username . '"@"' . $hostname . '";';
    PMA_mysql_query($sql_query0, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query0);
    PMA_mysql_query($sql_query1, $userlink); // this one may fail, too...
    $sql_query = $sql_query0 . ' ' . $sql_query1;
    $message = sprintf($strRevokeMessage, '\'' . $username . '\'@\'' . $hostname . '\'');
    if (empty($tablename)) {
        unset($dbname);
    } else {
        unset($tablename);
    }
}


/**
 * Updates the password
 */
if (!empty($change_pw)) {
    if ($nopass == 1) {
        $sql_query = 'SET PASSWORD FOR "' . $username . '"@"' . $hostname . '" = ""';
        PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink));
        $message = sprintf($strPasswordChanged, '\'' . $username . '\'@\'' . $hostname . '\'');
    } else if (empty($pma_pw) || empty($pma_pw2)) {
        $message = $strPasswordEmpty;
    } else if ($pma_pw != $pma_pw2) {
        $message = $strPasswordNotSame;
    } else {
        $hidden_pw = '';
        for ($i = 0; $i < strlen($pma_pw); $i++) {
            $hidden_pw .= '*';
        }
        $local_query = 'SET PASSWORD FOR "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '" = PASSWORD("' . PMA_sqlAddslashes($pma_pw) . '")';
        $sql_query = 'SET PASSWORD FOR "' . PMA_sqlAddslashes($username) . '"@"' . $hostname . '" = PASSWORD("' . $hidden_pw . '")';
        PMA_mysql_query($local_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink));
        $message = sprintf($strPasswordChanged, '\'' . $username . '\'@\'' . $hostname . '\'');
    }
}


/**
 * Deletes users
 *   (Changes / copies a user, part IV)
 */
if (!empty($delete) || (!empty($change_copy) && $mode < 4)) {
    if (!empty($change_copy)) {
        $selected_usr = array($old_username . '@' . $old_hostname);
    } else {
        $queries = array();
    }
    for ($i = 0; isset($selected_usr[$i]); $i++) {
        list($this_user, $this_host) = explode('@', $selected_usr[$i]);
        $queries[] = '# ' . sprintf($strDeleting, '\'' . $this_user . '\'@\'' . $this_host . '\'') . ' ...';
        if ($mode == 2) {
            // The SHOW GRANTS query may fail if the user has not been loaded
            // into memory
            $res = PMA_mysql_query('SHOW GRANTS FOR "' . PMA_sqlAddslashes($this_user) . '"@"' . $this_host . '";', $userlink);
            if ($res) {
                $queries[] = 'REVOKE ALL PRIVILEGES ON *.* FROM "' . PMA_sqlAddslashes($this_user) . '"@"' . $this_host . '";';
                while ($row = PMA_mysql_fetch_row($res)) {
                    $this_table = substr($row[0], (strpos($row[0], 'ON') + 3), (strpos($row[0], ' TO ') - strpos($row[0], 'ON') - 3));
                    if ($this_table != '*.*') {
                        $queries[] = 'REVOKE ALL PRIVILEGES ON ' . $this_table . ' FROM "' . PMA_sqlAddslashes($this_user) . '"@"' . $this_host . '";';

                        if (strpos($row[0], 'WITH GRANT OPTION')) {
                            $queries[] = 'REVOKE GRANT OPTION ON ' . $this_table . ' FROM "' . PMA_sqlAddslashes($this_user) . '"@"' . $this_host . '";';
                        }
                    }
                    unset($this_table);
                }
                mysql_free_result($res);
            }
            unset($res);
        }
        $queries[] = 'DELETE FROM `user` WHERE `User` = "' . PMA_sqlAddslashes($this_user) . '" AND `Host` = "' . $this_host . '";';
        if ($mode != 2) {
            // If we REVOKE the table grants, we should not need to modify the
            // `db`, `tables_priv` and `columns_priv` tables manually...
            $queries[] = 'DELETE FROM `db` WHERE `User` = "' . PMA_sqlAddslashes($this_user) . '" AND `Host` = "' . $this_host . '";';
            $queries[] = 'DELETE FROM `tables_priv` WHERE `User` = "' . PMA_sqlAddslashes($this_user) . '" AND `Host` = "' . $this_host . '";';
            $queries[] = 'DELETE FROM `columns_priv` WHERE `User` = "' . PMA_sqlAddslashes($this_user) . '" AND `Host` = "' . $this_host . '";';
        }
        if (!empty($drop_users_db)) {
            $queries[] = 'DROP DATABASE IF EXISTS ' . PMA_backquote($this_user) . ';';
        }
    }
    if (empty($change_copy)) {
        if (empty($queries)) {
            $message = $strError . ': ' . $strDeleteNoUsersSelected;
        } else {
            if ($mode == 3) {
                $queries[] = '# ' . $strReloadingThePrivileges . ' ...';
                $queries[] = 'FLUSH PRIVILEGES;';
            }
            foreach ($queries as $sql_query) {
                if ($sql_query{0} != '#') {
                    PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink));
                }
            }
            $sql_query = join("\n", $queries);
            $message = $strUsersDeleted;
        }
        unset($queries);
    }
}


/**
 * Changes / copies a user, part V
 */
if (!empty($change_copy)) {
    foreach ($queries as $sql_query) {
        if ($sql_query{0} != '#') {
            PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink));
        }
    }
    $message = $strSuccess;
    $sql_query = join("\n", $queries);
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
 * Displays the links
 */
require('./server_links.inc.php');


/**
 * Displays the page
 */
if (empty($adduser) && empty($checkprivs)) {
    if (!isset($username)) {
        // No username is given --> display the overview
        echo '<h2>' . "\n"
           . '    ' . $strUserOverview . "\n"
           . '</h2>' . "\n";
        $oldPrivTables = FALSE;
        if (PMA_MYSQL_INT_VERSION >= 40002) {
            $res = PMA_mysql_query('SELECT `User`, `Host`, IF(`Password` = "", "N", "Y") AS "Password", `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Reload_priv`, `Shutdown_priv`, `Process_priv`, `File_priv`, `Grant_priv`, `References_priv`, `Index_priv`, `Alter_priv`, `Show_db_priv`, `Super_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Execute_priv`, `Repl_slave_priv`, `Repl_client_priv` FROM `user` ORDER BY `User` ASC, `Host` ASC;', $userlink);
            if (!$res) {
                // the query failed! This may have two reasons:
                // - the user has not enough privileges
                // - the privilege tables use a structure of an earlier version.
                $oldPrivTables = TRUE;
            }
        }
        if (empty($res) || PMA_MYSQL_INT_VERSION < 40002) {
            $res = PMA_mysql_query('SELECT `User`, `Host`, IF(`Password` = "", "N", "Y") AS "Password", `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Index_priv`, `Alter_priv`, `Create_priv`, `Drop_priv`, `Grant_priv`, `References_priv`, `Reload_priv`, `Shutdown_priv`, `Process_priv`, `File_priv` FROM `user`  ORDER BY `User` ASC, `Host` ASC;', $userlink);
            if (!$res) {
                // the query failed! This may have two reasons:
                // - the user has not enough privileges
                // - the privilege tables use a structure of an earlier version.
                $oldPrivTables = TRUE;
            }
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
            echo '<form name="usersForm" action="server_privileges.php" method="post" />' . "\n"
               . PMA_generate_common_hidden_inputs('', '', 1)
               . '    <table border="0">' . "\n"
               . '        <tr>' . "\n"
               . '            <th></th>' . "\n"
               . '            <th>&nbsp;' . $strUser . '&nbsp;</th>' . "\n"
               . '            <th>&nbsp;' . $strHost . '&nbsp;</th>' . "\n"
               . '            <th>&nbsp;' . $strPassword . '&nbsp;</th>' . "\n"
               . '            <th>&nbsp;' . $strGlobalPrivileges . '&nbsp;</th>' . "\n"
               . '            <th>&nbsp;' . $strGrantOption . '&nbsp;</th>' . "\n"
               . '            <th>&nbsp;' . $strAction . '&nbsp;</th>' . "\n";
            echo '        </tr>' . "\n";
            $useBgcolorOne = TRUE;
            for ($i = 0; $row = PMA_mysql_fetch_array($res, MYSQL_ASSOC); $i++) {
                echo '        <tr>' . "\n"
                   . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><input type="checkbox" name="selected_usr[]" id="checkbox_sel_users_' . $i . '" value="' . htmlspecialchars($row['User'] . '@' . $row['Host']) . '"' . (empty($checkall) ?  '' : ' checked="checked"') . ' /></td>' . "\n"
                   . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><label for="checkbox_sel_users_' . $i . '">' . (empty($row['User']) ? '<span style="color: #FF0000">' . $strAny . '</span>' : htmlspecialchars($row['User'])) . '</label></td>' . "\n"
                   . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . htmlspecialchars($row['Host']) . '</td>' . "\n";
                $privs = PMA_extractPrivInfo($row, TRUE);
                echo '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . ($row['Password'] == 'Y' ? $strYes : '<span style="color: #FF0000">' . $strNo . '</span>') . '</td>' . "\n"
                   . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><tt>' . "\n"
                   . '                ' . join(',' . "\n" . '            ', $privs) . "\n"
                   . '            </tt></td>' . "\n"
                   . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . ($row['Grant_priv'] == 'Y' ? $strYes : $strNo) . '</td>' . "\n"
                   . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($row['User']) . '&amp;hostname=' . urlencode($row['Host']) . '">' . $strEdit . '</a></td>' . "\n"
                   . '        </tr>' . "\n";
                $useBgcolorOne = !$useBgcolorOne;
            }
            @mysql_free_result($res);
            unset($res);
            unset ($row);
            echo '        <tr>' . "\n"
               . '            <td></td>' . "\n"
               . '            <td colspan="5">' . "\n"
               . '                &nbsp;<i>' . $strEnglishPrivileges . '</i>&nbsp;' . "\n"
               . '            </td>' . "\n"
               . '        </tr>' . "\n"
               . '        <tr>' . "\n"
               . '            <td colspan="6" valign="bottom">' . "\n"
               . '                <img src="./images/arrow_' . $text_dir . '.gif" border="0" width="38" height="22" alt="' . $strWithChecked . '" />' . "\n"
               . '                <a href="./server_privileges.php?' . $url_query .  '&amp;checkall=1" onclick="setCheckboxes(\'usersForm\', \'selected_usr\', true); return false;">' . $strCheckAll . '</a>' . "\n"
               . '                &nbsp;/&nbsp;' . "\n"
               . '                <a href="server_privileges.php?' . $url_query .  '" onclick="setCheckboxes(\'usersForm\', \'selected_usr\', false); return false;">' . $strUncheckAll . '</a>' . "\n"
               . '            </td>' . "\n"
               . '        </tr>' . "\n"
               . '    </table>' . "\n"
               . '    <ul>' . "\n"
               . '        <li>' . "\n"
               . '            <b><a href="server_privileges.php?' . $url_query . '&amp;adduser=1">' . $strAddUser . '</a></b><br />' . "\n"
               . '        </li><br /><br />' . "\n"
               . '        <li>' . "\n"
               . '            <b>' . $strRemoveSelectedUsers . '</b><br />' . "\n"
               . '            <input type="radio" title="' . $strJustDelete . ' ' . $strJustDeleteDescr . '" name="mode" id="radio_mode_1" value="1" checked="checked" />' . "\n"
               . '            <label for="radio_mode_1" title="' . $strJustDelete . ' ' . $strJustDeleteDescr . '">' . "\n"
               . '                ' . $strJustDelete . "\n"
               . '            </label><br />' . "\n"
               . '            <input type="radio" title="' . $strRevokeAndDelete . ' ' . $strRevokeAndDeleteDescr . '" name="mode" id="radio_mode_2" value="2" />' . "\n"
               . '            <label for="radio_mode_2" title="' . $strRevokeAndDelete . ' ' . $strRevokeAndDeleteDescr . '">' . "\n"
               . '                ' . $strRevokeAndDelete . "\n"
               . '            </label><br />' . "\n"
               . '            <input type="radio" title="' . $strDeleteAndFlush . ' ' . $strDeleteAndFlushDescr . '" name="mode" id="radio_mode_3" value="3" />' . "\n"
               . '            <label for="radio_mode_3" title="' . $strDeleteAndFlush . ' ' . $strDeleteAndFlushDescr . '">' . "\n"
               . '                ' . $strDeleteAndFlush . "\n"
               . '            </label><br />' . "\n"
               . '            <input type="checkbox" title="' . $strDropUsersDb . '" name="drop_users_db" id="checkbox_drop_users_db" />' . "\n"
               . '            <label for="checkbox_drop_users_db" title="' . $strDropUsersDb . '">' . "\n"
               . '                ' . $strDropUsersDb . "\n"
               . '            </label><br />' . "\n"
               . '            <input type="submit" name="delete" value="' . $strGo . '" />' . "\n"
               . '        </li>' . "\n"
               . '    </ul>' . "\n"
               . '</form>' . "\n"
               . '<div>' . "\n"
               . '    ' . sprintf($strFlushPrivilegesNote, '<a href="server_privileges.php?' . $url_query . '&amp;flush_privileges=1">', '</a>') . "\n"
               . '</div>' . "\n";
        }
    } else {
        // A user was selected -> display the user's properties
        echo '<h2>' . "\n"
           . '    ' . $strUser . ' <i><a class="h2" href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '">\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'</a></i>' . "\n";
        if (!empty($dbname)) {
            echo '    - ' . $strDatabase . ' <i><a class="h2" href="' . $cfg['DefaultTabDatabase'] . '?' . $url_query . '&amp;db=' . urlencode($dbname) . '&amp;reload=1">' . htmlspecialchars($dbname) . '</a></i>' . "\n";
            if (!empty($tablename)) {
                echo '    - ' . $strTable . ' <i><a class="h2" href="' . $cfg['DefaultTabTable'] . '?' . $url_query . '&amp;db=' . urlencode($dbname) . '&amp;table=' . urlencode($tablename) . '&amp;reload=1">' . htmlspecialchars($tablename) . '</a></i>' . "\n";
            }
        }
        echo '</h2>' . "\n";
        $res = PMA_mysql_query('SELECT "foo" FROM `user` WHERE `User` = "' . PMA_sqlAddslashes($username) . '" AND `Host` = "' . $hostname . '";', $userlink);
        if (mysql_affected_rows($userlink) <= 0) {
            echo $strUserNotFound;
            require_once('./footer.inc.php');
        }
        mysql_free_result($res);
        unset($res);
        echo '<ul>' . "\n"
           . '    <li>' . "\n"
           . '        <form action="server_privileges.php" method="post">' . "\n"
           . PMA_generate_common_hidden_inputs('', '', 3)
           . '            <input type="hidden" name="username" value="' . htmlspecialchars($username) . '" />' . "\n"
           . '            <input type="hidden" name="hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n";
        if (!empty($dbname)) {
            echo '            <input type="hidden" name="dbname" value="' . htmlspecialchars($dbname) . '" />' . "\n";
            if (!empty($tablename)) {
                echo '            <input type="hidden" name="tablename" value="' . htmlspecialchars($tablename) . '" />' . "\n";
            }
        }
        echo '            <b>' . $strEditPrivileges . '</b><br />' . "\n";
        PMA_displayPrivTable((empty($dbname) ? '*' : $dbname), ((empty($dbname) || empty($tablename)) ? '*' : $tablename), TRUE, 3);
        echo '        </form>' . "\n"
           . '    </li>' . "\n";
        if (empty($tablename)) {
            echo '    <li>' . "\n"
               . '        <b>' . (empty($dbname) ? $strDbPrivileges : $strTblPrivileges) . '</b><br />' . "\n"
               . '        <table border="0">' . "\n"
               . '            <tr>' . "\n"
               . '                <th>&nbsp;' . (empty($dbname) ? $strDatabase : $strTable) . '&nbsp;</th>' . "\n"
               . '                <th>&nbsp;' . $strPrivileges . '&nbsp;</th>' . "\n"
               . '                <th>&nbsp;' . $strGrantOption . '&nbsp;</th>' . "\n"
               . '                <th>&nbsp;' . (empty($dbname) ? $strTblPrivileges : $strColumnPrivileges) . '&nbsp;</th>' . "\n"
               . '                <th colspan="2">&nbsp;' . $strAction . '&nbsp;</th>' . "\n"
               . '            </tr>' . "\n";
            if (empty($dbname)) {
                $sql_query = 'SELECT * FROM `db` WHERE `Host` = "' . $hostname . '" AND `User` = "' . PMA_sqlAddslashes($username) . '" ORDER BY `Db` ASC;';
            } else {
                $sql_query = 'SELECT `Table_name`, `Table_priv`, IF(`Column_priv` = "", 0, 1) AS "Column_priv" FROM `tables_priv` WHERE `Host` = "' . $hostname . '" AND `User` = "' . PMA_sqlAddslashes($username) . '" AND `Db` = "' . $dbname . '" ORDER BY `Table_name` ASC;';
            }
            $res = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
            if (mysql_affected_rows($userlink) == 0) {
                echo '            <tr>' . "\n"
                   . '                <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="6"><center><i>' . $strNone . '</i></center></td>' . "\n"
                   . '            </tr>' . "\n";
            } else {
                $useBgcolorOne = TRUE;
                if (empty($dbname)) {
                    $res2 = PMA_mysql_query('SELECT `Db` FROM `tables_priv` WHERE `Host` = "' . $hostname . '" AND `User` = "' . PMA_sqlAddslashes($username) . '" GROUP BY `Db` ORDER BY `Db` ASC;') or PMA_mysqlDie(PMA_mysql_error($userlink), 'SELECT `Db` FROM `tables_priv` WHERE `Host` = "' . $hostname . '" AND `User` = "' . PMA_sqlAddslashes($username) . '" GROUP BY `Db` ORDER BY `Db` ASC;');
                    $row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC);
                }
                $found_rows = array();
                while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {

                    while (empty($dbname) && $row2 && $row['Db'] > $row2['Db']) {
                        $found_rows[] = $row2['Db'];

                        echo '            <tr>' . "\n"
                           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . htmlspecialchars($row2['Db']) . '</td>' . "\n"
                           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><tt>' . "\n"
                           . '                    <dfn title="' . $strPrivDescUsage . '">USAGE</dfn>' . "\n"
                           . '                </tt></td>' . "\n"
                           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . $strNo . '</td>' . "\n"
                           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . $strYes . '</td>' . "\n"
                           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '&amp;dbname=' . urlencode($row2['Db']) . '">' . $strEdit . '</a></td>' . "\n"
                           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '&amp;dbname=' . urlencode($row2['Db']) . '&amp;revokeall=1">' . $strRevoke . '</a></td>' . "\n"
                           . '            </tr>' . "\n";
                        $row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC);
                        $useBgcolorOne = !$useBgcolorOne;
                    } // end while
                    $found_rows[] = empty($dbname) ? $row['Db'] : $row['Table_name'];

                    echo '            <tr>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . htmlspecialchars(empty($dbname) ? $row['Db'] : $row['Table_name']) . '</td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><tt>' . "\n"
                       . '                    ' . join(',' . "\n" . '            ', PMA_extractPrivInfo($row, TRUE)) . "\n"
                       . '                </tt></td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . (((empty($dbname) && $row['Grant_priv'] == 'Y') || (!empty($dbname) && in_array('Grant', explode(',', $row['Table_priv'])))) ? $strYes : $strNo) . '</td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">';
                    if ((empty($dbname) && $row2 && $row['Db'] == $row2['Db'])
                        || (!empty($dbname) && $row['Column_priv'])) {
                        echo $strYes;
                        if (empty($dbname)) {
                            $row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC);
                        }
                    } else {
                        echo $strNo;
                    }
                    echo '</td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '&amp;dbname=' . (empty($dbname) ? urlencode($row['Db']) : urlencode($dbname) . '&amp;tablename=' . urlencode($row['Table_name'])) . '">' . $strEdit . '</a></td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '&amp;dbname=' . (empty($dbname) ? urlencode($row['Db']) : urlencode($dbname) . '&amp;tablename=' . urlencode($row['Table_name'])) . '&amp;revokeall=1">' . $strRevoke . '</a></td>' . "\n"
                       . '            </tr>' . "\n";
                    $useBgcolorOne = !$useBgcolorOne;
                } // end while


                while (empty($dbname) && $row2) {

                    $found_rows[] = $row2['Db'];
                    echo '            <tr>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . htmlspecialchars($row2['Db']) . '</td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><tt>' . "\n"
                       . '                    <dfn title="' . $strPrivDescUsage . '">USAGE</dfn>' . "\n"
                       . '                </tt></td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . $strNo . '</td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . $strYes . '</td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '&amp;dbname=' . urlencode($row2['Db']) . '">' . $strEdit . '</a></td>' . "\n"
                       . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '"><a href="server_privileges.php?' . $url_query . '&amp;username=' . urlencode($username) . '&amp;hostname=' . urlencode($hostname) . '&amp;dbname=' . urlencode($row2['Db']) . '&amp;revokeall=1">' . $strRevoke . '</a></td>' . "\n"
                       . '            </tr>' . "\n";
                    $row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC);

                    $useBgcolorOne = !$useBgcolorOne;
                } // end while
                if (empty($dbname)) {
                    mysql_free_result($res2);
                    unset($res2);
                    unset($row2);
                }
            }
            mysql_free_result($res);
            unset($res);
            unset($row);
            echo '            <tr>' . "\n"
               . '                <td colspan="5">' . "\n"
               . '                    <form action="server_privileges.php" method="post">' . "\n"
               . PMA_generate_common_hidden_inputs('', '', 6)
               . '                        <input type="hidden" name="username" value="' . htmlspecialchars($username) . '" />' . "\n"
               . '                        <input type="hidden" name="hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n";
            if (empty($dbname)) {
                echo '                        <label for="text_dbname">' . $strAddPrivilegesOnDb . ':</label>' . "\n";
                $res = PMA_mysql_query('SHOW DATABASES;', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW DATABASES;');
                $pred_db_array = array();
                while ($row = PMA_mysql_fetch_row($res)) {
                    if (!isset($found_rows) || !in_array($row[0], $found_rows)) {
                        $pred_db_array[] = $row[0];
                    }
                }
                mysql_free_result($res);
                unset($res);
                unset($row);
                if (!empty($pred_db_array)) {
                    echo '                        <select name="pred_dbname" onchange="this.form.submit();">' . "\n"
                       . '                            <option value="" selected="selected">' . $strUseTextField . ':</option>' . "\n";
                    foreach ($pred_db_array as $current_db) {
                        echo '                            <option value="' . htmlspecialchars($current_db) . '">' . htmlspecialchars($current_db) . '</option>' . "\n";
                    }
                    echo '                        </select>' . "\n";
                }
                echo '                        <input type="text" id="text_dbname" name="dbname" class="textfield" />' . "\n";
            } else {
                echo '                        <input type="hidden" name="dbname" value="' . htmlspecialchars($dbname) . '"/>' . "\n"
                   . '                        <label for="text_tablename">' . $strAddPrivilegesOnTbl . ':</label>' . "\n";
                if ($res = @PMA_mysql_query('SHOW TABLES FROM ' . PMA_backquote($dbname) . ';', $userlink)) {
                    $pred_tbl_array = array();
                    while ($row = PMA_mysql_fetch_row($res)) {
                        if (!isset($found_rows) || !in_array($row[0], $found_rows)) {
                            $pred_tbl_array[] = $row[0];
                        }
                    }
                    mysql_free_result($res);
                    unset($res);
                    unset($row);
                    if (!empty($pred_tbl_array)) {
                        echo '                        <select name="pred_tablename" onchange="this.form.submit();">' . "\n"
                           . '                            <option value="" selected="selected">' . $strUseTextField . ':</option>' . "\n";
                        foreach ($pred_tbl_array as $current_table) {
                            echo '                            <option value="' . htmlspecialchars($current_table) . '">' . htmlspecialchars($current_table) . '</option>' . "\n";
                        }
                        echo '                        </select>' . "\n";
                    }
                } else {
                    unset($res);
                }
                echo '                        <input type="text" id="text_tablename" name="tablename" class="textfield" />' . "\n";
            }
            echo '                        <input type="submit" value="' . $strGo . '" />' . "\n"
               . '                    </form>' . "\n"
               . '                </td>' . "\n"
               . '            </tr>' . "\n"
               . '        </table><br />' . "\n"
               . '    </li>' . "\n";
        }
        if (empty($dbname)) {
            echo '    <li>' . "\n"
               . '        <form action="server_privileges.php" method="post" onsubmit="checkPassword(this);">' . "\n"
               . PMA_generate_common_hidden_inputs('', '', 3)
               . '            <input type="hidden" name="username" value="' . htmlspecialchars($username) . '" />' . "\n"
               . '            <input type="hidden" name="hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n";
            echo '            <b>' . $strChangePassword . '</b><br />' . "\n"
               . '            <table border="0">' . "\n"
               . '                <tr>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorOne'] . '"><input type="radio" name="nopass" value="1" id="radio_nopass_1" onclick="pma_pw.value=\'\'; pma_pw2.value=\'\';" /></td>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorOne'] . '" colspan="2"><label for="radio_nopass_1">' . $strNoPassword . '</label></td>' . "\n"
               . '                </tr>' . "\n"
               . '                <tr>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="radio" name="nopass" value="0" id="radio_nopass_0" onclick="document.getElementById(\'pw_pma_pw\').focus();" /></td>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="radio_nopass_0">' . $strPassword . ':</label></td>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="password" name="pma_pw" id="pw_pma_pw" class="textfield" onchange="nopass[1].checked = true;" /></td>' . "\n"
               . '                </tr>' . "\n"
               . '                <tr>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '">&nbsp;</td>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><label for="pw_pma_pw2">' . $strReType . ':</label></td>' . "\n"
               . '                    <td bgcolor="' . $cfg['BgcolorTwo'] . '"><input type="password" name="pma_pw2" id="pw_pma_pw2" class="textfield" onchange="nopass[1].checked = true;" /></td>' . "\n"
               . '                </tr>' . "\n"
               . '                <tr>' . "\n"
               . '                    <td colspan="3" align="center">' . "\n"
               . '                        <input type="submit" name="change_pw" value="' . $strGo . '" />' . "\n"
               . '                    </td>' . "\n"
               . '                </tr>' . "\n"
               . '            </table>' . "\n"
               . '        </form>' . "\n"
               . '    </li>' . "\n"
               . '    <li>' . "\n"
               . '        <form action="server_privileges.php" method="post" onsubmit="checkPassword(this);">' . "\n"
               . PMA_generate_common_hidden_inputs('', '', 3)
               . '            <input type="hidden" name="old_username" value="' . htmlspecialchars($username) . '" />' . "\n"
               . '            <input type="hidden" name="old_hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n"
               . '            <b>' . $strChangeCopyUser . '</b><br />' . "\n"
               . '            <table border="0">' . "\n";
            PMA_displayLoginInformationFields('change', 3);
            echo '            </table>' . "\n"
               . '            ' . $strChangeCopyMode . '<br />' . "\n"
               . '            <input type="radio" name="mode" value="4" id="radio_mode_4" checked="checked" />' . "\n"
               . '            <label for="radio_mode_4">' . "\n"
               . '                ' . $strChangeCopyModeCopy . "\n"
               . '            </label>' . "\n"
               . '            <br />' . "\n"
               . '            <input type="radio" name="mode" value="1" id="radio_mode_1" />' . "\n"
               . '            <label for="radio_mode_1">' . "\n"
               . '                ' . $strChangeCopyModeJustDelete . "\n"
               . '            </label>' . "\n"
               . '            <br />' . "\n"
               . '            <input type="radio" name="mode" value="2" id="radio_mode_2" />' . "\n"
               . '            <label for="radio_mode_2">' . "\n"
               . '                ' . $strChangeCopyModeRevoke . "\n"
               . '            </label>' . "\n"
               . '            <br />' . "\n"
               . '            <input type="radio" name="mode" value="3" id="radio_mode_3" />' . "\n"
               . '            <label for="radio_mode_3">' . "\n"
               . '                ' . $strChangeCopyModeDeleteAndReload . "\n"
               . '            </label>' . "\n"
               . '            <br />' . "\n"
               . '            <input type="submit" name="change_copy" value="' . $strGo . '" />' . "\n"
               . '        </form>' . "\n"
               . '    </li>' . "\n";
        }
        echo '</ul>' . "\n";
    }
} else if (!empty($adduser)) {
    // Add a new user
    echo '<h2>' . "\n"
       . '    ' . $strAddUser . "\n"
       . '</h2>' . "\n"
       . '<form action="server_privileges.php" method="post" onsubmit="return checkAddUser(this);">' . "\n"
       . PMA_generate_common_hidden_inputs('', '', 1)
       . '    <table border="0">' . "\n"
       . '        <tr>' . "\n"
       . '            <th colspan="3">' . "\n"
       . '                ' . $strLoginInformation . "\n"
       . '            </th>' . "\n"
       . '        </tr>' . "\n";
    PMA_displayLoginInformationFields('new', 2);
    echo '    </table><br />' . "\n";
    PMA_displayPrivTable('*', '*', FALSE, 1);
    echo '    <br />' . "\n"
       . '    <input type="submit" name="adduser_submit" value="' . $strGo . '" />' . "\n"
       . '</form>' . "\n";
} else {
    // check the privileges for a particular database.
    echo '<h2>' . "\n"
       . '    ' . sprintf($strUsersHavingAccessToDb, htmlspecialchars($checkprivs)) . "\n"
       . '</h2>' . "\n"
       . '<table border="0">' . "\n"
       . '    <tr>' . "\n"
       . '        <th>' . "\n"
       . '            &nbsp;' . $strUser . '&nbsp;' . "\n"
       . '        </th>' . "\n"
       . '        <th>' . "\n"
       . '            &nbsp;' . $strHost . '&nbsp;' . "\n"
       . '        </th>' . "\n"
       . '        <th>' . "\n"
       . '            &nbsp;' . $strType . '&nbsp;' . "\n"
       . '        </th>' . "\n"
       . '        <th>' . "\n"
       . '            &nbsp;' . $strPrivileges . '&nbsp;' . "\n"
       . '        </th>' . "\n"
       . '        <th>' . "\n"
       . '            &nbsp;' . $strGrantOption . '&nbsp;' . "\n"
       . '        </th>' . "\n"
       . '        <th>' . "\n"
       . '            &nbsp;' . $strAction . '&nbsp;' . "\n"
       . '        </th>' . "\n"
       . '    </tr>' . "\n";
    $useBgcolorOne = TRUE;
    unset($row);
    unset($row1);
    unset($row2);
    // now, we build the table...
    if (PMA_MYSQL_INT_VERSION >= 40000) {
        // Starting with MySQL 4.0.0, we may use UNION SELECTs and this makes
        // the job much easier here!
        $sql_query = '(SELECT `User`, `Host`, `Db`, `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Grant_priv`, `References_priv` FROM `db` WHERE "' . $checkprivs . '" LIKE `Db` AND NOT (`Select_priv` = "N" AND `Insert_priv` = "N" AND `Update_priv` = "N" AND `Delete_priv` = "N" AND `Create_priv` = "N" AND `Drop_priv` = "N" AND `Grant_priv` = "N" AND `References_priv` = "N")) UNION (SELECT `User`, `Host`, "*" AS "Db", `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Grant_priv`, `References_priv` FROM `user` WHERE NOT (`Select_priv` = "N" AND `Insert_priv` = "N" AND `Update_priv` = "N" AND `Delete_priv` = "N" AND `Create_priv` = "N" AND `Drop_priv` = "N" AND `Grant_priv` = "N" AND `References_priv` = "N")) ORDER BY `User` ASC, `Host` ASC, `Db` ASC;';
        $res = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        $row = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
        if ($row) {
            $found = TRUE;
        }
    } else {
        // With MySQL 3, we need 2 seperate queries here.
        $sql_query = 'SELECT * FROM `user` WHERE NOT (`Select_priv` = "N" AND `Insert_priv` = "N" AND `Update_priv` = "N" AND `Delete_priv` = "N" AND `Create_priv` = "N" AND `Drop_priv` = "N" AND `Grant_priv` = "N" AND `References_priv` = "N") ORDER BY `User` ASC, `Host` ASC;';
        $res1 = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        $row1 = PMA_mysql_fetch_array($res1, MYSQL_ASSOC);
        $sql_query = 'SELECT * FROM `db` WHERE "' . $checkprivs . '" LIKE `Db` AND NOT (`Select_priv` = "N" AND `Insert_priv` = "N" AND `Update_priv` = "N" AND `Delete_priv` = "N" AND `Create_priv` = "N" AND `Drop_priv` = "N" AND `Grant_priv` = "N" AND `References_priv` = "N") ORDER BY `User` ASC, `Host` ASC;';
        $res2 = PMA_mysql_query($sql_query, $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), $sql_query);
        $row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC);
        if ($row1 || $row2) {
            $found = TRUE;
        }
    } // end if (PMA_MYSQL_INT_VERSION >= 40000) ... else ...
    if ($found) {
        while (TRUE) {
            // prepare the current user
            if (PMA_MYSQL_INT_VERSION >= 40000) {
                $current_privileges = array();
                $current_user = $row['User'];
                $current_host = $row['Host'];
                while ($row && $current_user == $row['User'] && $current_host == $row['Host']) {
                    $current_privileges[] = $row;
                    $row = PMA_mysql_fetch_array($res, MYSQL_ASSOC);
                }
            } else {
                $current_privileges = array();
                if ($row1 && (!$row2 || ($row1['User'] < $row2['User'] || ($row1['User'] == $row2['User'] && $row1['Host'] <= $row2['Host'])))) {
                    $current_user = $row1['User'];
                    $current_host = $row1['Host'];
                    $current_privileges = array($row1);
                    $row1 = PMA_mysql_fetch_array($res1, MYSQL_ASSOC);
                } else {
                    $current_user = $row2['User'];
                    $current_host = $row2['Host'];
                    $current_privileges = array();
                }
                while ($row2 && $current_user == $row2['User'] && $current_host == $row2['Host']) {
                    $current_privileges[] = $row2;
                    $row2 = PMA_mysql_fetch_array($res2, MYSQL_ASSOC);
                }
            }
            echo '    <tr>' . "\n"
               . '        <td';
            if (count($current_privileges) > 1) {
                echo ' rowspan="' . count($current_privileges) . '"';
            }
            echo ' bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '            ' . (empty($current_user) ? '<span style="color: #FF0000">' . $strAny . '</span>' : htmlspecialchars($current_user)) . "\n"
               . '        </td>' . "\n"
               . '        <td';
            if (count($current_privileges) > 1) {
                echo ' rowspan="' . count($current_privileges) . '"';
            }
            echo ' bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '            ' . htmlspecialchars($current_host) . "\n"
               . '        </td>' . "\n";
            foreach ($current_privileges as $current) {
                echo '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
                   . '            ';
                if (!isset($current['Db']) || $current['Db'] == '*') {
                    echo $strGlobal;
                } else if ($current['Db'] == $checkprivs) {
                    echo $strDbSpecific;
                } else {
                    echo $strWildcard, ': <tt>' . htmlspecialchars($current['Db']) . '</tt>';
                }
                echo "\n"
                   . '        </td>' . "\n"
                   . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
                   . '            <tt>' . "\n"
                   . '                ' . join(',' . "\n" . '                ', PMA_extractPrivInfo($current, TRUE)) . "\n"
                   . '            <tt>' . "\n"
                   . '        </td>' . "\n"
                   . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
                   . '            ' . ($current['Grant_priv'] == 'Y' ? $strYes : $strNo) . "\n"
                   . '        </td>' . "\n"
                   . '        <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
                   . '            <a href="./server_privileges.php?' . $url_query . '&amp;username=' . urlencode($current_user) . '&amp;hostname=' . urlencode($current_host) . (!isset($current['Db']) || $current['Db'] == '*' ? '' : '&amp;dbname=' . urlencode($current['Db'])) . '">' . "\n"
                   . '                ' . $strEdit . "\n"
                   . '            </a>' . "\n"
                   . '        </td>' . "\n"
                   . '    </tr>' . "\n";
            }
            if (empty($row) && empty($row1) && empty($row2)) {
                break;
            }
            $useBgcolorOne = !$useBgcolorOne;
        }
    } else {
        echo '    <tr>' . "\n"
           . '        <td colspan="6" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
           . '            ' . $strNoUsersFound . "\n"
           . '        </td>' . "\n"
           . '    </tr>' . "\n";
    }
    echo '</table>' . "\n";
} // end if (empty($adduser) && empty($checkprivs)) ... else if ... else ...


/**
 * Displays the footer
 */
echo "\n\n";
require_once('./footer.inc.php');

?>
