<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
require_once './libraries/common.lib.php';

/**
 * Does the common work
 */
$js_to_run = 'server_privileges.js';
require './libraries/server_common.inc.php';


/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$is_superuser) {
    require './libraries/server_links.inc.php';
    echo '<h2>' . "\n"
       . ($GLOBALS['cfg']['MainPageIconic'] ? '<img class="icon" src="'. $GLOBALS['pmaThemeImage'] . 'b_usrlist.png" alt="" />' : '')
       . $GLOBALS['strPrivileges'] . "\n"
       . '</h2>' . "\n"
       . $GLOBALS['strNoPrivileges'] . "\n";
    require_once './libraries/footer.inc.php';
}


/**
 * Checks if a dropdown box has been used for selecting a database / table
 */
if (isset($_REQUEST['pred_dbname']) && strlen($_REQUEST['pred_dbname'])) {
    $dbname = $_REQUEST['pred_dbname'];
    unset($_REQUEST['pred_dbname']);
} elseif (isset($_REQUEST['dbname']) && strlen($_REQUEST['dbname'])) {
    $dbname = $_REQUEST['dbname'];
    unset($_REQUEST['dbname']);
} else {
    $dbname = '';
}

if (isset($_REQUEST['pred_tablename']) && strlen($_REQUEST['pred_tablename'])) {
    $tablename = $_REQUEST['pred_tablename'];
    unset($_REQUEST['pred_tablename']);
} else {
    $tablename = '';
}

/**
 * check if given $dbname is a wildcard or not
 */
//if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
//if (preg_match('/(?<!\\\\)(?:_|%)/i', $dbname)) {
if (strlen($dbname) && (
     strpos('\\_', $dbname) !== false
  || strpos('\\%', $dbname) !== false)) {
    $dbname_is_wildcard = true;
} else {
    $dbname_is_wildcard = false;
}

/**
 * array PMA_getTableRights()
 *
 * returns array with all table privilegs
 *
 * note: in MySQL 5.0.3 we get "Create View', 'Show view';
 * the View for Create is spelled with uppercase V
 * the view for Show is spelled with lowercase v
 * and there is a space between the words
 *
 * @return array table privilegs
 */
function PMA_getTablePrivilegs()
{
    $av_grants = PMA_DBI_fetch_value(
        'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
        0, 'Type');
    return explode("','", substr($av_grants, 5, -2));
}

/**
 * void PMA_addUSerTablePrivs(&$user_privs)
 *
 * adds user table privs to given user privilegs array $user_privs
 *
 * @param   array &$user_privs user privilegs
 */
function PMA_addUSerTablePrivs(&$user_privs)
{
    if (empty($user_privs) || ! isset($user_privs['Table_priv'])) {
        return;
    }

    $av_grants = PMA_getTablePrivilegs();
    $users_grants = explode(',', $user_privs['Table_priv']);

    foreach ($av_grants as $current_grant) {
        $user_privs[$current_grant . '_priv'] =
            in_array($current_grant, $users_grants) ? 'Y' : 'N';
    }

    unset($user_privs['Table_priv']);
}

/**
 * Generates a condition on the user name
 *
 * @param   string   the user's initial
 * @return  string   the generated condition
 */
function PMA_RangeOfUsers($initial = '')
{
    // strtolower() is used because the User field
    // might be BINARY, so LIKE would be case sensitive
    if (!empty($initial)) {
        $ret = " WHERE " . PMA_convert_using('User')
         . " LIKE " . PMA_convert_using($initial . '%', 'quoted')
         . " OR ". PMA_convert_using('User')
         . " LIKE " . PMA_convert_using(strtolower($initial) . '%', 'quoted');
    } else {
        $ret = '';
    }
    return $ret;
} // end function

/**
 * Extracts the privilege information of a priv table row
 *
 * @param   array   $row        the row
 * @param   boolean $enableHTML add <dfn> tag with tooltips
 *
 * @global  ressource $user_link the database connection
 *
 * @return  array
 */
function PMA_extractPrivInfo($row = '', $enableHTML = false)
{
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
        array('Repl_slave_priv', 'REPLICATION SLAVE', $GLOBALS['strPrivDescReplSlave']),
        array('Repl_client_priv', 'REPLICATION CLIENT', $GLOBALS['strPrivDescReplClient']),
        array('Create_view_priv', 'CREATE VIEW', $GLOBALS['strPrivDescCreateView']),
        // for table privs:
        array('Create View_priv', 'CREATE VIEW', $GLOBALS['strPrivDescCreateView']),
        array('Show_view_priv', 'SHOW VIEW', $GLOBALS['strPrivDescShowView']),
        // for table privs:
        array('Show view_priv', 'SHOW VIEW', $GLOBALS['strPrivDescShowView']),
        array('Create_routine_priv', 'CREATE ROUTINE', $GLOBALS['strPrivDescCreateRoutine']),
        array('Alter_routine_priv', 'ALTER ROUTINE', $GLOBALS['strPrivDescAlterRoutine']),
        array('Create_user_priv', 'CREATE USER', $GLOBALS['strPrivDescCreateUser'])
    );
    if (PMA_MYSQL_INT_VERSION >= 40002 && PMA_MYSQL_INT_VERSION <50003) {
        $grants[] = array('Execute_priv', 'EXECUTE', $GLOBALS['strPrivDescExecute']);
    } else {
        $grants[] = array('Execute_priv', 'EXECUTE', $GLOBALS['strPrivDescExecute5']);
    }

    PMA_addUSerTablePrivs($row);

    $privs = array();
    $allPrivileges = true;
    foreach ($grants as $current_grant) {
        if ((!empty($row) && isset($row[$current_grant[0]])) || (empty($row) && isset($GLOBALS[$current_grant[0]]))) {
            if ((!empty($row) && $row[$current_grant[0]] == 'Y') || (empty($row) && ($GLOBALS[$current_grant[0]] == 'Y' || (is_array($GLOBALS[$current_grant[0]]) && count($GLOBALS[$current_grant[0]]) == $GLOBALS['column_count'] && empty($GLOBALS[$current_grant[0] . '_none']))))) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $current_grant[2] . '">' . $current_grant[1] . '</dfn>';
                } else {
                    $privs[] = $current_grant[1];
                }
            } elseif (!empty($GLOBALS[$current_grant[0]]) && is_array($GLOBALS[$current_grant[0]]) && empty($GLOBALS[$current_grant[0] . '_none'])) {
                if ($enableHTML) {
                    $priv_string = '<dfn title="' . $current_grant[2] . '">' . $current_grant[1] . '</dfn>';
                } else {
                    $priv_string = $current_grant[1];
                }
                $privs[] = $priv_string . ' (`' . join('`, `', $GLOBALS[$current_grant[0]]) . '`)';
            } else {
                $allPrivileges = false;
            }
        }
    }
    if (empty($privs)) {
        if ($enableHTML) {
            $privs[] = '<dfn title="' . $GLOBALS['strPrivDescUsage'] . '">USAGE</dfn>';
        } else {
            $privs[] = 'USAGE';
        }
    } elseif ($allPrivileges && (!isset($GLOBALS['grant_count']) || count($privs) == $GLOBALS['grant_count'])) {
        if ($enableHTML) {
            $privs = array('<dfn title="' . $GLOBALS['strPrivDescAllPrivileges'] . '">ALL PRIVILEGES</dfn>');
        } else {
            $privs = array('ALL PRIVILEGES');
        }
    }
    return $privs;
} // end of the 'PMA_extractPrivInfo()' function


/**
 * Displays on which column(s) a table-specific privilege is granted
 */
function PMA_display_column_privs($spaces, $columns, $row, $name_for_select,
    $priv_for_header, $name, $name_for_dfn, $name_for_current)
{
    $spaces .= '    ';
    echo $spaces . '<div class="item" id="div_item_' . $name . '">' . "\n"
       . $spaces . '    <label for="select_' . $name . '_priv">' . "\n"
       . $spaces . '        <tt><dfn title="' . $name_for_dfn . '">'
       . $priv_for_header . '</dfn></tt>' . "\n"
       . $spaces . '    </label>' . "\n"
       . $spaces . '    <select id="select_' . $name . '_priv" name="'
       . $name_for_select . '[]" multiple="multiple">' . "\n";

    foreach ($columns as $current_column => $current_column_privileges) {
        echo $spaces . '        <option value="' . htmlspecialchars($current_column) . '"';
        if ($row[$name_for_select] == 'Y' || $current_column_privileges[$name_for_current]) {
            echo ' selected="selected"';
        }
        echo '>' . htmlspecialchars($current_column) . '</option>' . "\n";
    }

    echo $spaces . '    </select>' . "\n"
       . $spaces . '    <i>' . $GLOBALS['strOr'] . '</i>' . "\n"
       . $spaces . '    <label for="checkbox_' . $name_for_select . '_none">'
       . '<input type="checkbox"'
       . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"')
       . ' name="' . $name_for_select . '_none"'
       . ' id="checkbox_' . $name_for_select . '_none"'
       . ' title="' . $GLOBALS['strNone'] . '" />'
       . $GLOBALS['strNone'] . '</label>' . "\n"
       . $spaces . '</div>' . "\n";
} // end function

/**
 * Displays the privileges form table
 *
 * @param   string  $db     the database
 * @param   string  $table  the table
 * @param   boolean $submit wheather to display the submit button or not
 * @param   int     $indent the indenting level of the code
 *
 * @global  array      $cfg         the phpMyAdmin configuration
 * @global  ressource  $user_link   the database connection
 *
 * @return  void
 */
function PMA_displayPrivTable($db = '*', $table = '*', $submit = true, $indent = 0)
{
    if ($db == '*') {
        $table = '*';
    }
    $spaces = str_repeat('    ', $indent);

    if (isset($_REQUEST['username'])) {
        if ($db == '*') {
            $sql_query =
                 'SELECT * FROM `mysql`.`user`'
                .' WHERE ' . PMA_convert_using('User')
                .' = ' . PMA_convert_using(PMA_sqlAddslashes($_REQUEST['username']), 'quoted')
                .' AND ' . PMA_convert_using('Host')
                .' = ' . PMA_convert_using($_REQUEST['hostname'], 'quoted') . ';';
        } elseif ($table == '*') {
            $sql_query =
                'SELECT * FROM `mysql`.`db`'
                .' WHERE ' . PMA_convert_using('`User`')
                .' = ' . PMA_convert_using(PMA_sqlAddslashes($_REQUEST['username']), 'quoted')
                .' AND ' . PMA_convert_using('`Host`')
                .' = ' . PMA_convert_using($_REQUEST['hostname'], 'quoted')
                .' AND ' .  PMA_convert_using(PMA_unescape_mysql_wildcards($db), 'quoted')
                .' LIKE ' . PMA_convert_using('`Db`') . ';';
        } else {
            $sql_query =
                'SELECT `Table_priv`'
                .' FROM `mysql`.`tables_priv`'
                .' WHERE ' . PMA_convert_using('`User`')
                .' = ' . PMA_convert_using(PMA_sqlAddslashes($_REQUEST['username']), 'quoted')
                .' AND ' .PMA_convert_using('`Host`')
                .' = ' . PMA_convert_using($_REQUEST['hostname'], 'quoted')
                .' AND ' .PMA_convert_using('`Db`')
                .' = ' . PMA_convert_using(PMA_unescape_mysql_wildcards($db), 'quoted')
                .' AND ' . PMA_convert_using('`Table_name`')
                .' = ' . PMA_convert_using($table, 'quoted') . ';';
        }
        $row = PMA_DBI_fetch_single_row($sql_query);
    }
    if (empty($row)) {
        if ($table == '*') {
            if ($db == '*') {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`user`;';
            } else {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`db`;';
            }
            $res = PMA_DBI_query($sql_query);
            while ($row1 = PMA_DBI_fetch_row($res)) {
                if (substr($row1[0], 0, 4) == 'max_') {
                    $row[$row1[0]] = 0;
                } else {
                    $row[$row1[0]] = 'N';
                }
            }
            PMA_DBI_free_result($res);
        } else {
            $row = array('Table_priv' => '');
        }
    }
    if (isset($row['Table_priv'])) {
        PMA_addUSerTablePrivs($row);

        // get collumns
        $res = PMA_DBI_try_query('SHOW COLUMNS FROM `' . PMA_unescape_mysql_wildcards($db) . '`.`' . $table . '`;');
        $columns = array();
        if ($res) {
            while ($row1 = PMA_DBI_fetch_row($res)) {
                $columns[$row1[0]] = array(
                    'Select' => false,
                    'Insert' => false,
                    'Update' => false,
                    'References' => false
                );
            }
            PMA_DBI_free_result($res);
        }
        unset($res, $row1);
    }
    // t a b l e - s p e c i f i c    p r i v i l e g e s
    if (! empty($columns)) {
        $res = PMA_DBI_query(
            'SELECT `Column_name`, `Column_priv`'
            .' FROM `mysql`.`columns_priv`'
            .' WHERE ' . PMA_convert_using('`User`')
            .' = ' . PMA_convert_using(PMA_sqlAddslashes($_REQUEST['username']), 'quoted')
            .' AND ' . PMA_convert_using('`Host`')
            .' = ' . PMA_convert_using($_REQUEST['hostname'], 'quoted')
            .' AND ' . PMA_convert_using('`Db`')
            .' = ' . PMA_convert_using(PMA_unescape_mysql_wildcards($db), 'quoted')
            .' AND ' . PMA_convert_using('`Table_name`')
            .' = ' . PMA_convert_using($table, 'quoted') . ';');

        while ($row1 = PMA_DBI_fetch_row($res)) {
            $row1[1] = explode(',', $row1[1]);
            foreach ($row1[1] as $current) {
                $columns[$row1[0]][$current] = true;
            }
        }
        PMA_DBI_free_result($res);
        unset($res, $row1, $current);

        echo $spaces . '<input type="hidden" name="grant_count" value="' . count($row) . '" />' . "\n"
           . $spaces . '<input type="hidden" name="column_count" value="' . count($columns) . '" />' . "\n"
           . $spaces . '<fieldset id="fieldset_user_priv">' . "\n"
           . $spaces . '    <legend>' . $GLOBALS['strTblPrivileges'] . '</legend>' . "\n"
           . $spaces . '    <p><small><i>' . $GLOBALS['strEnglishPrivileges'] . '</i></small></p>' . "\n";


        // privs that are attached to a specific column
        PMA_display_column_privs($spaces, $columns, $row, 'Select_priv', 'SELECT', 'select', $GLOBALS['strPrivDescSelect'], 'Select');

        PMA_display_column_privs($spaces, $columns, $row, 'Insert_priv', 'INSERT', 'insert', $GLOBALS['strPrivDescInsert'], 'Insert');

        PMA_display_column_privs($spaces, $columns, $row, 'Update_priv', 'UPDATE', 'update', $GLOBALS['strPrivDescUpdate'], 'Update');

        PMA_display_column_privs($spaces, $columns, $row, 'References_priv', 'REFERENCES', 'references', $GLOBALS['strPrivDescReferences'], 'References');

        // privs that are not attached to a specific column

        echo $spaces . '    <div class="item">' . "\n";
        foreach ($row as $current_grant => $current_grant_value) {
            if (in_array(substr($current_grant, 0, (strlen($current_grant) - 5)), array('Select', 'Insert', 'Update', 'References'))) {
                continue;
            }
            // make a substitution to match the messages variables;
            // also we must substitute the grant we get, because we can't generate
            // a form variable containing blanks (those would get changed to
            // an underscore when receiving the POST)
            if ($current_grant == 'Create View_priv') {
                $tmp_current_grant = 'CreateView_priv';
                $current_grant = 'Create_view_priv';
            } elseif ($current_grant == 'Show view_priv') {
                $tmp_current_grant = 'ShowView_priv';
                $current_grant = 'Show_view_priv';
            } else {
                $tmp_current_grant = $current_grant;
            }

            echo $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <input type="checkbox"' . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"') . ' name="' . $current_grant . '" id="checkbox_' . $current_grant . '" value="Y" ' . ($current_grant_value == 'Y' ? 'checked="checked" ' : '') . 'title="';

            echo (isset($GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)) . 'Tbl']) . '"/>' . "\n";

            echo $spaces . '            <label for="checkbox_' . $current_grant . '"><tt><dfn title="' . (isset($GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))]) ? $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))] : $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)) . 'Tbl']) . '">' . strtoupper(substr($current_grant, 0, strlen($current_grant) - 5)) . '</dfn></tt></label>' . "\n"
               . $spaces . '        </div>' . "\n";
        } // end foreach ()

        echo $spaces . '    </div>' . "\n";
        // for Safari 2.0.2
        echo $spaces . '    <div class="clearfloat"></div>' . "\n";

    } else {

        // g l o b a l    o r    d b - s p e c i f i c
        //
        // d a t a
        $privTable[0] = array(
            array('Select', 'SELECT', $GLOBALS['strPrivDescSelect']),
            array('Insert', 'INSERT', $GLOBALS['strPrivDescInsert']),
            array('Update', 'UPDATE', $GLOBALS['strPrivDescUpdate']),
            array('Delete', 'DELETE', $GLOBALS['strPrivDescDelete'])
        );
        if ($db == '*') {
            $privTable[0][] = array('File', 'FILE', $GLOBALS['strPrivDescFile']);
        }

        // s t r u c t u r e
        $privTable[1] = array(
            array('Create', 'CREATE', ($table == '*' ? $GLOBALS['strPrivDescCreateDb'] : $GLOBALS['strPrivDescCreateTbl'])),
            array('Alter', 'ALTER', $GLOBALS['strPrivDescAlter']),
            array('Index', 'INDEX', $GLOBALS['strPrivDescIndex']),
            array('Drop', 'DROP', ($table == '*' ? $GLOBALS['strPrivDescDropDb'] : $GLOBALS['strPrivDescDropTbl']))
        );
        if (isset($row['Create_tmp_table_priv'])) {
            $privTable[1][] = array('Create_tmp_table', 'CREATE TEMPORARY TABLES', $GLOBALS['strPrivDescCreateTmpTable']);
        }
        // this one is for a db-specific priv: Create_view_priv
        if (isset($row['Create_view_priv'])) {
            $privTable[1][] = array('Create_view', 'CREATE VIEW', $GLOBALS['strPrivDescCreateView']);
        }
        // this one is for a table-specific priv: Create View_priv
        if (isset($row['Create View_priv'])) {
            $privTable[1][] = array('Create View', 'CREATE VIEW', $GLOBALS['strPrivDescCreateView']);
        }
        if (isset($row['Show_view_priv'])) {
            $privTable[1][] = array('Show_view', 'SHOW VIEW', $GLOBALS['strPrivDescShowView']);
        }
        if (isset($row['Create_routine_priv'])) {
            $privTable[1][] = array('Create_routine', 'CREATE ROUTINE', $GLOBALS['strPrivDescCreateRoutine']);
        }
        if (isset($row['Alter_routine_priv'])) {
            $privTable[1][] = array('Alter_routine', 'ALTER ROUTINE', $GLOBALS['strPrivDescAlterRoutine']);
        }
        if (isset($row['Execute_priv'])) {
            if (PMA_MYSQL_INT_VERSION >= 40002 && PMA_MYSQL_INT_VERSION <50003) {
                $privTable[1][] = array('Execute', 'EXECUTE', $GLOBALS['strPrivDescExecute']);
            } else {
                $privTable[1][] = array('Execute', 'EXECUTE', $GLOBALS['strPrivDescExecute5']);
            }
        }

        // a d m i n i s t r a t i o n
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
                $privTable[2][] = array('Show_db', 'SHOW DATABASES', $GLOBALS['strPrivDescShowDb']);
            }
        }
        if (isset($row['Lock_tables_priv'])) {
            $privTable[2][] = array('Lock_tables', 'LOCK TABLES', $GLOBALS['strPrivDescLockTables']);
        }
        $privTable[2][] = array('References', 'REFERENCES', $GLOBALS['strPrivDescReferences']);
        if ($db == '*') {
            //if (isset($row['Execute_priv'])) {
            //    $privTable[2][] = array('Execute', 'EXECUTE', $GLOBALS['strPrivDescExecute']);
            //}
            if (isset($row['Repl_client_priv'])) {
                $privTable[2][] = array('Repl_client', 'REPLICATION CLIENT', $GLOBALS['strPrivDescReplClient']);
            }
            if (isset($row['Repl_slave_priv'])) {
                $privTable[2][] = array('Repl_slave', 'REPLICATION SLAVE', $GLOBALS['strPrivDescReplSlave']);
            }
            if (isset($row['Create_user_priv'])) {
                $privTable[2][] = array('Create_user', 'CREATE USER', $GLOBALS['strPrivDescCreateUser']);
            }
        }
        echo $spaces . '<input type="hidden" name="grant_count" value="' . (count($privTable[0]) + count($privTable[1]) + count($privTable[2]) - (isset($row['Grant_priv']) ? 1 : 0)) . '" />' . "\n"
           . $spaces . '<fieldset id="fieldset_user_global_rights">' . "\n"
           . $spaces . '    <legend>' . "\n"
           . $spaces . '        ' . ($db == '*' ? $GLOBALS['strGlobalPrivileges'] : ($table == '*' ? $GLOBALS['strDbPrivileges'] : $GLOBALS['strTblPrivileges'])) . "\n"
           . $spaces . '        ( <a href="./server_privileges.php?' . $GLOBALS['url_query'] .  '&amp;checkall=1" onclick="setCheckboxes(\'usersForm\', true); return false;">' . $GLOBALS['strCheckAll'] . '</a> /' . "\n"
           . $spaces . '        <a href="./server_privileges.php?' . $GLOBALS['url_query'] .  '" onclick="setCheckboxes(\'usersForm\', false); return false;">' . $GLOBALS['strUncheckAll'] . '</a> )' . "\n"
           . $spaces . '    </legend>' . "\n"
           . $spaces . '    <p><small><i>' . $GLOBALS['strEnglishPrivileges'] . '</i></small></p>' . "\n"
           . $spaces . '    <fieldset>' . "\n"
           . $spaces . '        <legend>' . $GLOBALS['strData'] . '</legend>' . "\n";
        foreach ($privTable[0] as $priv)
        {
            echo $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <input type="checkbox"' . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"') . ' name="' . $priv[0] . '_priv" id="checkbox_' . $priv[0] . '_priv" value="Y" ' . ($row[$priv[0] . '_priv'] == 'Y' ? 'checked="checked" ' : '') . 'title="' . $priv[2] . '"/>' . "\n"
               . $spaces . '            <label for="checkbox_' . $priv[0] . '_priv"><tt><dfn title="' . $priv[2] . '">' . $priv[1] . '</dfn></tt></label>' . "\n"
               . $spaces . '        </div>' . "\n";
        }
        echo $spaces . '    </fieldset>' . "\n"
           . $spaces . '    <fieldset>' . "\n"
           . $spaces . '        <legend>' . $GLOBALS['strStructure'] . '</legend>' . "\n";
        foreach ($privTable[1] as $priv)
        {
            echo $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <input type="checkbox"' . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"') . ' name="' . $priv[0] . '_priv" id="checkbox_' . $priv[0] . '_priv" value="Y" ' . ($row[$priv[0] . '_priv'] == 'Y' ? 'checked="checked" ' : '') . 'title="' . $priv[2] . '"/>' . "\n"
               . $spaces . '            <label for="checkbox_' . $priv[0] . '_priv"><tt><dfn title="' . $priv[2] . '">' . $priv[1] . '</dfn></tt></label>' . "\n"
               . $spaces . '        </div>' . "\n";
        }
        echo $spaces . '    </fieldset>' . "\n"
           . $spaces . '    <fieldset>' . "\n"
           . $spaces . '        <legend>' . $GLOBALS['strAdministration'] . '</legend>' . "\n";
        foreach ($privTable[2] as $priv)
        {
            echo $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <input type="checkbox"' . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"') . ' name="' . $priv[0] . '_priv" id="checkbox_' . $priv[0] . '_priv" value="Y" ' . ($row[$priv[0] . '_priv'] == 'Y' ? 'checked="checked" ' : '') . 'title="' . $priv[2] . '"/>' . "\n"
               . $spaces . '            <label for="checkbox_' . $priv[0] . '_priv"><tt><dfn title="' . $priv[2] . '">' . $priv[1] . '</dfn></tt></label>' . "\n"
               . $spaces . '        </div>' . "\n";
        }

        echo $spaces . '    </fieldset>' . "\n";
        // The "Resource limits" box is not displayed for db-specific privs
        if ($db == '*' && PMA_MYSQL_INT_VERSION >= 40002) {
            echo $spaces . '    <fieldset>' . "\n"
               . $spaces . '        <legend>' . $GLOBALS['strResourceLimits'] . '</legend>' . "\n"
               . $spaces . '        <p><small><i>' . $GLOBALS['strZeroRemovesTheLimit'] . '</i></small></p>' . "\n"
               . $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <label for="text_max_questions"><tt><dfn title="' . $GLOBALS['strPrivDescMaxQuestions'] . '">MAX QUERIES PER HOUR</dfn></tt></label>' . "\n"
               . $spaces . '            <input type="text" name="max_questions" id="text_max_questions" value="' . $row['max_questions'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxQuestions'] . '" />' . "\n"
               . $spaces . '        </div>' . "\n"
               . $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <label for="text_max_updates"><tt><dfn title="' . $GLOBALS['strPrivDescMaxUpdates'] . '">MAX UPDATES PER HOUR</dfn></tt></label>' . "\n"
               . $spaces . '            <input type="text" name="max_updates" id="text_max_updates" value="' . $row['max_updates'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxUpdates'] . '" />' . "\n"
               . $spaces . '        </div>' . "\n"
               . $spaces . '        <div class="item">' . "\n"
               . $spaces . '            <label for="text_max_connections"><tt><dfn title="' . $GLOBALS['strPrivDescMaxConnections'] . '">MAX CONNECTIONS PER HOUR</dfn></tt></label>' . "\n"
               . $spaces . '            <input type="text" name="max_connections" id="text_max_connections" value="' . $row['max_connections'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxConnections'] . '" />' . "\n"
               . $spaces . '        </div>' . "\n";

            if (PMA_MYSQL_INT_VERSION >= 50003) {
                echo $spaces . '        <div class="item">' . "\n"
                   . $spaces . '            <label for="text_max_user_connections"><tt><dfn title="' . $GLOBALS['strPrivDescMaxUserConnections'] . '">MAX USER_CONNECTIONS</dfn></tt></label>' . "\n"
                   . $spaces . '            <input type="text" name="max_user_connections" id="text_max_user_connections" value="' . $row['max_user_connections'] . '" size="11" maxlength="11" title="' . $GLOBALS['strPrivDescMaxUserConnections'] . '" />' . "\n"
                   . $spaces . '        </div>' . "\n";
            }
            echo $spaces . '    </fieldset>' . "\n";
           }
        // for Safari 2.0.2
        echo $spaces . '    <div class="clearfloat"></div>' . "\n";
    }
    echo $spaces . '</fieldset>' . "\n";
    if ($submit) {
        echo $spaces . '<fieldset id="fieldset_user_privtable_footer" class="tblFooters">' . "\n"
           . $spaces . '    <input type="submit" name="update_privs" value="' . $GLOBALS['strGo'] . '" />' . "\n"
           . $spaces . '</fieldset>' . "\n";
    }
} // end of the 'PMA_displayPrivTable()' function


/**
 * Displays the fields used by the "new user" form as well as the
 * "change login information / copy user" form.
 *
 * @param   string     $mode    are we creating a new user or are we just
 *                              changing  one? (allowed values: 'new', 'change')
 * @param   int        $indent  the indenting level of the code
 *
 * @global  array      $cfg     the phpMyAdmin configuration
 * @global  ressource  $user_link the database connection
 *
 * @return  void
 */
function PMA_displayLoginInformationFields($mode = 'new', $indent = 0)
{
    $spaces = str_repeat('    ', $indent);

    // Get maximum user/host name column lengths from mysql table structure
    $fields_info = PMA_DBI_get_fields('mysql', 'user');
    $username_length = 16;
    $hostname_length = 41;
    foreach ($fields_info as $key => $val) {
        if ($val['Field'] == 'User') {
            strtok($val['Type'], '()');
            $v = strtok('()');
            if (is_int($v)) {
                $username_length = $v;
            }
        } elseif ($val['Field'] == 'Host') {
            strtok($val['Type'], '()');
            $v = strtok('()');
            if (is_int($v)) {
                $hostname_length = $v;
            }
        }
    }
    unset($fields_info, $v, $key, $val);

    if (isset($_REQUEST['username']) && strlen($_REQUEST['username']) === 0) {
        $_REQUEST['pred_username'] = 'any';
    }
    echo $spaces . '<fieldset id="fieldset_add_user_login">' . "\n"
       . $spaces . '<legend>' . $GLOBALS['strLoginInformation'] . '</legend>' . "\n"
       . $spaces . '<div class="item">' . "\n"
       . $spaces . '<label for="select_pred_username">' . "\n"
       . $spaces . '    ' . $GLOBALS['strUserName'] . ':' . "\n"
       . $spaces . '</label>' . "\n"
       . $spaces . '<span class="options">' . "\n"
       . $spaces . '    <select name="pred_username" id="select_pred_username" title="' . $GLOBALS['strUserName'] . '"' . "\n"
       . $spaces . '        onchange="if (this.value == \'any\') { username.value = \'\'; } else if (this.value == \'userdefined\') { username.focus(); username.select(); }">' . "\n"
       . $spaces . '        <option value="any"' . ((isset($_REQUEST['pred_username']) && $_REQUEST['pred_username'] == 'any') ? ' selected="selected"' : '') . '>' . $GLOBALS['strAnyUser'] . '</option>' . "\n"
       . $spaces . '        <option value="userdefined"' . ((!isset($_REQUEST['pred_username']) || $_REQUEST['pred_username'] == 'userdefined') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
       . $spaces . '    </select>' . "\n"
       . $spaces . '</span>' . "\n"
       . $spaces . '<input type="text" name="username" maxlength="' . $username_length . '" title="' . $GLOBALS['strUserName'] . '"' . (empty($_REQUEST['username']) ? '' : ' value="' . (isset($GLOBALS['new_username']) ? $GLOBALS['new_username'] : $_REQUEST['username']) . '"') . ' onchange="pred_username.value = \'userdefined\';" />' . "\n"
       . $spaces . '</div>' . "\n"
       . $spaces . '<div class="item">' . "\n"
       . $spaces . '<label for="select_pred_hostname">' . "\n"
       . $spaces . '    ' . $GLOBALS['strHost'] . ':' . "\n"
       . $spaces . '</label>' . "\n"
       . $spaces . '<span class="options">' . "\n"
       . $spaces . '    <select name="pred_hostname" id="select_pred_hostname" title="' . $GLOBALS['strHost'] . '"' . "\n";

    $thishost = PMA_DBI_fetch_value('SELECT USER();');
    if (!empty($thishost)) {
        $thishost = str_replace("'", '', substr($thishost, (strrpos($thishost, '@') + 1)));
        if ($thishost == 'localhost' || $thishost == '127.0.0.1') {
            unset($thishost);
        }
    }
    echo $spaces . '    onchange="if (this.value == \'any\') { hostname.value = \'%\'; } else if (this.value == \'localhost\') { hostname.value = \'localhost\'; } '
       . (empty($thishost) ? '' : 'else if (this.value == \'thishost\') { hostname.value = \'' . addslashes(htmlspecialchars($thishost)) . '\'; } ')
       . 'else if (this.value == \'hosttable\') { hostname.value = \'\'; } else if (this.value == \'userdefined\') { hostname.focus(); hostname.select(); }">' . "\n";

    // when we start editing a user, $_REQUEST['pred_hostname'] is not defined
    if (!isset($_REQUEST['pred_hostname']) && isset($_REQUEST['hostname'])) {
        switch (strtolower($_REQUEST['hostname'])) {
            case 'localhost':
            case '127.0.0.1':
                $_REQUEST['pred_hostname'] = 'localhost';
                break;
            case '%':
                $_REQUEST['pred_hostname'] = 'any';
                break;
            default:
                $_REQUEST['pred_hostname'] = 'userdefined';
                break;
        }
    }
    echo $spaces . '        <option value="any"' . ((isset($_REQUEST['pred_hostname']) && $_REQUEST['pred_hostname'] == 'any') ? ' selected="selected"' : '') . '>' . $GLOBALS['strAnyHost'] . '</option>' . "\n"
       . $spaces . '        <option value="localhost"' . ((isset($_REQUEST['pred_hostname']) && $_REQUEST['pred_hostname'] == 'localhost') ? ' selected="selected"' : '') . '>' . $GLOBALS['strLocalhost'] . '</option>' . "\n";
    if (!empty($thishost)) {
        echo $spaces . '        <option value="thishost"' . ((isset($_REQUEST['pred_hostname']) && $_REQUEST['pred_hostname'] == 'thishost') ? ' selected="selected"' : '') . '>' . $GLOBALS['strThisHost'] . '</option>' . "\n";
    }
    unset($thishost);
    echo $spaces . '        <option value="hosttable"' . ((isset($_REQUEST['pred_hostname']) && $_REQUEST['pred_hostname'] == 'hosttable') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseHostTable'] . '</option>' . "\n"
       . $spaces . '        <option value="userdefined"' . ((isset($_REQUEST['pred_hostname']) && $_REQUEST['pred_hostname'] == 'userdefined') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
       . $spaces . '    </select>' . "\n"
       . $spaces . '</span>' . "\n"
       . $spaces . '<input type="text" name="hostname" maxlength="' . $hostname_length . '" value="' . (isset($_REQUEST['hostname']) ? $_REQUEST['hostname'] : '') . '" title="' . $GLOBALS['strHost'] . '" onchange="pred_hostname.value = \'userdefined\';" />' . "\n"
       . $spaces . '</div>' . "\n"
       . $spaces . '<div class="item">' . "\n"
       . $spaces . '<label for="select_pred_password">' . "\n"
       . $spaces . '    ' . $GLOBALS['strPassword'] . ':' . "\n"
       . $spaces . '</label>' . "\n"
       . $spaces . '<span class="options">' . "\n"
       . $spaces . '    <select name="pred_password" id="select_pred_password" title="' . $GLOBALS['strPassword'] . '"' . "\n"
       . $spaces . '            onchange="if (this.value == \'none\') { pma_pw.value = \'\'; pma_pw2.value = \'\'; } else if (this.value == \'userdefined\') { pma_pw.focus(); pma_pw.select(); }">' . "\n"
       . ($mode == 'change' ? $spaces . '            <option value="keep" selected="selected">' . $GLOBALS['strKeepPass'] . '</option>' . "\n" : '')
       . $spaces . '        <option value="none"';
    if (isset($_REQUEST['username']) && $mode != 'change') {
        echo '  selected="selected"';
    }
    echo $spaces . '>' . $GLOBALS['strNoPassword'] . '</option>' . "\n"
       . $spaces . '        <option value="userdefined"' . (isset($_REQUEST['username']) ? '' : ' selected="selected"') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
       . $spaces . '    </select>' . "\n"
       . $spaces . '</span>' . "\n"
       . $spaces . '<input type="password" id="text_pma_pw" name="pma_pw" title="' . $GLOBALS['strPassword'] . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
       . $spaces . '</div>' . "\n"
       . $spaces . '<div class="item">' . "\n"
       . $spaces . '<label for="text_pma_pw2">' . "\n"
       . $spaces . '    ' . $GLOBALS['strReType'] . ':' . "\n"
       . $spaces . '</label>' . "\n"
       . $spaces . '<span class="options">&nbsp;</span>' . "\n"
       . $spaces . '<input type="password" name="pma_pw2" id="text_pma_pw2" title="' . $GLOBALS['strReType'] . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
       . $spaces . '</div>' . "\n"
       . $spaces . '<div class="item">' . "\n"
       . $spaces . '<label for="button_generate_password">' . "\n"
       . $spaces . '    ' . $GLOBALS['strGeneratePassword'] . ':' . "\n"
       . $spaces . '</label>' . "\n"
       . $spaces . '<span class="options">' . "\n"
       . $spaces . '    <input type="button" id="button_generate_password" value="' . $GLOBALS['strGenerate'] . '" onclick="suggestPassword()" />' . "\n"
       . $spaces . '    <input type="button" id="button_copy_password" value="' . $GLOBALS['strCopy'] . '" onclick="suggestPasswordCopy(this.form)" />' . "\n"
       . $spaces . '</span>' . "\n"
       . $spaces . '<input type="text" name="generated_pw" id="generated_pw" />' . "\n"
       . $spaces . '</div>' . "\n"
       . $spaces . '</fieldset>' . "\n";
} // end of the 'PMA_displayUserAndHostFields()' function

/**
 * string PMA_getPrivCondition(string $username, string $hostname[,
 *            string $db = null[, string $table = null]])
 *
 * returns (simplified):
 * WEHRE `User` = '$username'
 *   AND `Host` = '$hostname'
 * [  AND `Db` = '$db']
 * [  AND `Table_name` = '$table'];
 *
 * @param   string  $username   user name
 * @param   string  $hostname   host name
 * @return  string              user host WHERE condition
 */
function PMA_getPrivCondition($username, $hostname, $db = null, $table = null)
{
    $return =
        ' WHERE ' . PMA_convert_using('`User`')
        .' = ' . PMA_convert_using(PMA_sqlAddslashes($username), 'quoted')
        .' AND ' . PMA_convert_using('`Host`')
        .' = ' . PMA_convert_using($hostname, 'quoted');

    if ( null !== $db ) {
        $return .=
            ' AND ' . PMA_convert_using('`Db`')
            .' = ' . PMA_convert_using($db, 'quoted');
    }
    if ( null !== $table ) {
        $return .=
            ' AND ' . PMA_convert_using('`Table_name`')
            .' = ' . PMA_convert_using($table, 'quoted');
    }

    return $return . ';';
}

/**
 * string PMA_getDbTable(string $db, string $table)
 *
 * escaping a wildcard character in a GRANT is only accepted at the global
 * or database level, not at table level; this is why I remove
 * the escaping character
 * Note: in the phpMyAdmin list of Database-specific privileges,
 *  we will have for example
 *  test\_db  SELECT (this one is for privileges on a db level)
 *  test_db   USAGE  (this one is for table-specific privileges)
 *
 * It looks curious but reflects the way MySQL works
 *
 * @param   string  $db     database name
 * @param   string  $table  table name
 */
function PMA_getDbTable($db, $table)
{
    if (strlen($db)) {
        if (strlen($table)) {
            $db_and_table = PMA_backquote(PMA_unescape_mysql_wildcards($db));
            $db_and_table .= '.' . PMA_backquote($table);
        } else {
            $db_and_table = PMA_backquote($db) . '.*';
        }
    } else {
        $db_and_table = '*.*';
    }

    return $db_and_table;
}

/**
 * returns WITH privileg options
 *
 * @param   array   $privs  privilegs to include
 * @return  string  WITH privilegs
 */
function PMA_checkWithPrivs(&$privs)
{
    $with = '';

    if (isset($privs['Grant_priv']) && $privs['Grant_priv'] == 'Y') {
        $with .= ' GRANT OPTION';
    }

    if (PMA_MYSQL_INT_VERSION >= 40002) {
        if (isset($privs['max_questions'])) {
            // avoid negative values
            $privs['max_questions'] =
                max(0, (int) $privs['max_questions']);
            $with .= ' MAX_QUERIES_PER_HOUR ' . $privs['max_questions'];
        }
        if (isset($privs['max_connections'])) {
            $privs['max_connections'] =
                max(0, (int) $privs['max_connections']);
            $with .= ' MAX_CONNECTIONS_PER_HOUR ' . $privs['max_connections'];
        }
        if (isset($privs['max_updates'])) {
            $privs['max_updates'] =
                max(0, (int) $privs['max_updates']);
            $with .= ' MAX_UPDATES_PER_HOUR ' . $privs['max_updates'];
        }
    }

    if (PMA_MYSQL_INT_VERSION >= 50003) {
        if (isset($privs['max_user_connections'])) {
            $privs['max_user_connections'] =
                max(0, (int) $privs['max_user_connections']);
            $with .= ' MAX_USER_CONNECTIONS ' . $privs['max_user_connections'];
        }
    }

    if (strlen($with)) {
        $with = ' WITH' . $with . ' ';
    }

    return $with;
}

/**
 * Changes / copies a user, part I
 *
 * chek if given user exists
 */
$mysql_user = array();
if (!empty($_REQUEST['change_copy'])) {
    $user_host_condition = PMA_getPrivCondition($_REQUEST['old_username'],
        $_REQUEST['old_hostname']);
    $mysql_user = PMA_DBI_fetch_single_row(
        'SELECT * FROM `mysql`.`user` ' . $user_host_condition);
    if (!$mysql_user) {
        $mysql_user = array();
        $message = $GLOBALS['strNoUsersFound'];
        unset($_REQUEST['change_copy']);
    } else {
        // Recent MySQL versions have the field "Password" in mysql.user,
        // so the previous extract creates $Password but this script
        // uses $password
        if (!isset($mysql_user['Password']) && isset($mysql_user['password'])) {
            $mysql_user['Password'] =& $mysql_user['password'];
        }
        $queries = array();
    }
}


/**
 * Adds a user
 *   (Changes / copies a user, part II)
 */
if (!empty($_REQUEST['adduser_submit']) || !empty($_REQUEST['change_copy'])) {
    unset($sql_query);
    if ($_REQUEST['pred_username'] == 'any') {
        $_REQUEST['username'] = '';
    }
    switch ($_REQUEST['pred_hostname']) {
        case 'any':
            $_REQUEST['hostname'] = '%';
            break;
        case 'localhost':
            $_REQUEST['hostname'] = 'localhost';
            break;
        case 'hosttable':
            $_REQUEST['hostname'] = '';
            break;
        case 'thishost':
            $_REQUEST['hostname'] = PMA_DBI_fetch_value('SELECT USER();');
            $_REQUEST['hostname'] = substr($_REQUEST['hostname'],
                (strrpos($_REQUEST['hostname'], '@') + 1));
            break;
    }
    $user_exists = PMA_DBI_fetch_value(
        'SELECT COUNT(*) FROM `mysql`.`user`'
        . PMA_getPrivCondition($_REQUEST['username'], $_REQUEST['hostname']));
    if ($user_exists) {
        $message = sprintf($GLOBALS['strUserAlreadyExists'],
            "[i]'" . $_REQUEST['username'] . "'@'" . $_REQUEST['hostname'] . "'[/i]");
        // display add user page again
        $_REQUEST['adduser'] = true;
    } else {
        if (50002 <= PMA_MYSQL_INT_VERSION) {
            // MySQL 5 requires CREATE USER before any GRANT on this user can done
            $create_user_real = 'CREATE USER \''
                . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\''
                . $_REQUEST['hostname'] . '\'';
        }

        $real_sql_query =
            'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON *.* TO \''
            . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\''
            . $_REQUEST['hostname'] . '\'';
        if ($_REQUEST['pred_password'] != 'none'
          && $_REQUEST['pred_password'] != 'keep') {
            $sql_query = $real_sql_query . ' IDENTIFIED BY \'*****\'';
            $real_sql_query .= ' IDENTIFIED BY \'' . $_REQUEST['pma_pw'] . '\'';
            if (isset($create_user_real)) {
                $create_user_show = $create_user_real . ' IDENTIFIED BY \'*****\'';
                $create_user_real .= ' IDENTIFIED BY \'' . $_REQUEST['pma_pw'] . '\'';
            }
        } else {
            if ($_REQUEST['pred_password'] == 'keep'
              && !empty($mysql_user['Password'])) {
                $real_sql_query .= ' IDENTIFIED BY PASSWORD \''
                    . $mysql_user['Password'] . '\'';
                if (isset($create_user_real)) {
                    $create_user_real .= ' IDENTIFIED BY PASSWORD \''
                        . $mysql_user['Password'] . '\'';
                }
            }
            $sql_query = $real_sql_query;
            if (isset($create_user_real)) {
                $create_user_show = $create_user_real;
            }
        }

        $with = PMA_checkWithPrivs($mysql_user);
        $real_sql_query .= $with . ';';
        $sql_query .= $with . ';';
        if (isset($create_user_real)) {
            $create_user_real .= ';';
            $create_user_show .= ';';
        }
        unset($with);

        if (empty($_REQUEST['change_copy'])) {
            if (isset($create_user_real)) {
                PMA_DBI_try_query($create_user_real)
                    or PMA_mysqlDie(PMA_DBI_getError(), $create_user_show);
                $sql_query = $create_user_show . $sql_query;
            }
            PMA_DBI_try_query($real_sql_query)
                or PMA_mysqlDie(PMA_DBI_getError(), $sql_query);
            $message = $GLOBALS['strAddUserMessage'];
        } else {
            if (isset($create_user_real)) {
                $queries[]             = $create_user_real;
            }
            $queries[]             = $real_sql_query;
            // we put the query containing the hidden password in
            // $queries_for_display, at the same position occupied
            // by the real query in $queries
            $tmp_count = count($queries);
            if (isset($create_user_real)) {
                $queries_for_display[$tmp_count - 2] = $create_user_show;
            }
            $queries_for_display[$tmp_count - 1] = $sql_query;
        }
        unset($real_sql_query);
        if (isset($create_user_real)) {
            unset($create_user_show, $create_user_real);
        }
    }
}


/**
 * Changes / copies a user, part III
 */
if (!empty($_REQUEST['change_copy'])) {
    $user_host_condition = PMA_getPrivCondition($_REQUEST['old_username'],
        $_REQUEST['old_hostname']);
    $res = PMA_DBI_query('SELECT * FROM `mysql`.`db`' . $user_host_condition);
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $queries[] =
            'GRANT ' . join(', ', PMA_extractPrivInfo($row))
            . ' ON `' . $row['Db'] . '`.* TO \''
            . PMA_sqlAddslashes($_REQUEST['username']) . "'@'"
            . $_REQUEST['hostname'] . "'"
            . ($row['Grant_priv'] == 'Y' ? ' WITH GRANT OPTION;' : ';');
    }
    PMA_DBI_free_result($res);

    $res = PMA_DBI_query(
        'SELECT `Db`, `Table_name`, `Table_priv`'
        .' FROM `mysql`.`tables_priv`' . $user_host_condition,
        $GLOBALS['userlink'], PMA_DBI_QUERY_STORE);
    while ($row = PMA_DBI_fetch_assoc($res)) {

        $res2 = PMA_DBI_QUERY(
            'SELECT `Column_name`, `Column_priv`'
            . ' FROM `mysql`.`columns_priv`'
            . PMA_getPrivCondition($_REQUEST['old_username'],
                $_REQUEST['old_hostname'], $row['Db'], $row['Table_name']),
            null, PMA_DBI_QUERY_STORE);

        $tmp_privs1 = PMA_extractPrivInfo($row);
        $tmp_privs2 = array(
            'Select' => array(),
            'Insert' => array(),
            'Update' => array(),
            'References' => array()
        );

        $tmp_array = array();
        while ($row2 = PMA_DBI_fetch_assoc($res2)) {
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
        }
        PMA_DBI_free_result($res2);

        if (count($tmp_privs2['Select']) && !in_array('SELECT', $tmp_privs1)) {
            $tmp_privs1[] = 'SELECT (`' . join('`, `', $tmp_privs2['Select']) . '`)';
        }
        if (count($tmp_privs2['Insert']) && !in_array('INSERT', $tmp_privs1)) {
            $tmp_privs1[] = 'INSERT (`' . join('`, `', $tmp_privs2['Insert']) . '`)';
        }
        if (count($tmp_privs2['Update']) && !in_array('UPDATE', $tmp_privs1)) {
            $tmp_privs1[] = 'UPDATE (`' . join('`, `', $tmp_privs2['Update']) . '`)';
        }
        if (count($tmp_privs2['References']) && !in_array('REFERENCES', $tmp_privs1)) {
            $tmp_privs1[] = 'REFERENCES (`' . join('`, `', $tmp_privs2['References']) . '`)';
        }
        $queries[] =
            'GRANT ' . join(', ', $tmp_privs1)
            . ' ON `' . $row['Db'] . '`.`' . $row['Table_name'] . '` TO \''
            . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\''
            . $_REQUEST['hostname'] . '\''
            . (in_array('Grant', explode(',', $row['Table_priv'])) ? ' WITH GRANT OPTION;' : ';');
    }
    PMA_DBI_free_result($res);
    unset($res, $res2, $row2, $tmp_array, $tmp_privs2, $tmp_privs1, $row,
        $user_host_condition);
}


/**
 * Updates privileges
 */
if (!empty($_REQUEST['update_privs'])) {
    $db_and_table = PMA_getDbTable($dbname, $tablename);

    $sql_query0 =
        'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\''
        . $_REQUEST['hostname'] . '\';';
    if (!isset($mysql_user['Grant_priv']) || $mysql_user['Grant_priv'] != 'Y') {
        $sql_query1 =
            'REVOKE GRANT OPTION ON ' . $db_and_table
            . ' FROM \'' . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\''
            . $_REQUEST['hostname'] . '\';';
    }
    $sql_query2 =
        'GRANT ' . join(', ', PMA_extractPrivInfo())
        . ' ON ' . $db_and_table
        . ' TO \'' . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\''
        . $_REQUEST['hostname'] . '\'';

    $sql_query2 .= PMA_checkWithPrivs($mysql_user) . ';';

    if (!PMA_DBI_try_query($sql_query0)) {
        // this query may fail, but this does not matter :o)
        unset($sql_query0);
    }
    if (isset($sql_query1) && !PMA_DBI_try_query($sql_query1)) {
        // this one may fail, too...
        unset($sql_query1);
    }
    PMA_DBI_query($sql_query2);
    $sql_query = (isset($sql_query0) ? $sql_query0 . ' ' : '')
               . (isset($sql_query1) ? $sql_query1 . ' ' : '')
               . $sql_query2;
    $message = sprintf($GLOBALS['strUpdatePrivMessage'], '\'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\'');
    unset($sql_query2, $db_and_table);
}


/**
 * Revokes Privileges
 */
if (!empty($_REQUEST['revokeall'])) {

    $db_and_table = PMA_getDbTable($dbname, $tablename);

    $sql_query0 =
        'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\';';
    $sql_query1 =
        'REVOKE GRANT OPTION ON ' . $db_and_table
        . ' FROM \'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\';';
    PMA_DBI_query($sql_query0);
    if (!PMA_DBI_try_query($sql_query1)) { // this one may fail, too...
        unset($sql_query1);
    }
    $sql_query = $sql_query0 . (isset($sql_query1) ? ' ' . $sql_query1 : '');
    $message = sprintf($GLOBALS['strRevokeMessage'], '\'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\'');
    if (strlen($tablename)) {
        unset($tablename);
    } else {
        unset($dbname);
    }

    unset($sql_query0, $db_and_table);
}


/**
 * Updates the password
 */
if (!empty($_REQUEST['change_pw'])) {
    if ($_REQUEST['$nopass'] == 1) {
        $sql_query = 'SET PASSWORD FOR \'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\' = \'\';';
        PMA_DBI_query($sql_query);
        $message = sprintf($GLOBALS['strPasswordChanged'], '\'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\'');
    } elseif (empty($_REQUEST['pma_pw']) || empty($_REQUEST['pma_pw2'])) {
        $message = $GLOBALS['strPasswordEmpty'];
    } elseif ($_REQUEST['pma_pw'] != $_REQUEST['pma_pw2']) {
        $message = $GLOBALS['strPasswordNotSame'];
    } else {
        $local_query = 'SET PASSWORD FOR \'' . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\'' . $_REQUEST['hostname'] . '\' = PASSWORD(\'' . PMA_sqlAddslashes($_REQUEST['pma_pw']) . '\')';
        $sql_query = 'SET PASSWORD FOR \'' . PMA_sqlAddslashes($_REQUEST['username']) . '\'@\'' . $_REQUEST['hostname'] . '\' = PASSWORD(\'*****\')';
        PMA_DBI_try_query($local_query) or PMA_mysqlDie(PMA_DBI_getError(), $sql_query);
        $message = sprintf($GLOBALS['strPasswordChanged'], '\'' . $_REQUEST['username'] . '\'@\'' . $_REQUEST['hostname'] . '\'');
    }
}


/**
 * Deletes users
 *   (Changes / copies a user, part IV)
 */
$user_host_separator = chr(27);

if (!empty($_REQUEST['delete']) || (!empty($_REQUEST['change_copy']) && $_REQUEST['mode'] < 4)) {
    if (!empty($_REQUEST['change_copy'])) {
        $_REQUEST['selected_usr'] =
            array($_REQUEST['old_username'] . $user_host_separator . $_REQUEST['old_hostname']);
    } else {
        $queries = array();
    }
    foreach ($_REQUEST['selected_usr'] as $each_selected_user) {
        list($this_user, $this_host) = explode($user_host_separator, $each_selected_user);
        $this_user_host = "'" . PMA_sqlAddslashes($this_user) . "'@'" . $this_host . "'";
        $queries[] = '# ' . sprintf($GLOBALS['strDeleting'], $this_user_host) . ' ...';
        if (PMA_MYSQL_INT_VERSION >= 50002) {
            $queries[] = 'DROP USER ' . $this_user_host . ';';
        } else {
            if ($_REQUEST['mode'] == 2) {
                // The SHOW GRANTS query may fail if the user has not been loaded
                // into memory
                $res = PMA_DBI_try_query('SHOW GRANTS FOR ' . $this_user_host . ';');
                if ($res) {
                    $queries[] = 'REVOKE ALL PRIVILEGES ON *.* FROM ' . $this_user_host . ';';
                    while ($row = PMA_DBI_fetch_row($res)) {
                        $this_table = substr($row[0], (strpos($row[0], 'ON') + 3),
                            (strpos($row[0], ' TO ') - strpos($row[0], 'ON') - 3));
                        if ($this_table != '*.*') {
                            $queries[] = 'REVOKE ALL PRIVILEGES ON ' . $this_table
                                . ' FROM ' . $this_user_host . ';';

                            if (strpos($row[0], 'WITH GRANT OPTION')) {
                                $queries[] = 'REVOKE GRANT OPTION ON ' . $this_table
                                    . ' FROM ' . $this_user_host . ';';
                            }
                        }
                        unset($this_table);
                    }
                    PMA_DBI_free_result($res);
                }
                unset($res);
            }
            if (PMA_MYSQL_INT_VERSION >= 40101) {
                $queries[] = 'DROP USER ' . $this_user_host . ';';
            } else {
                $queries[] = 'DELETE FROM `mysql`.`user`'
                    . PMA_getPrivCondition($this_user, $this_host);
            }
            if ($_REQUEST['mode'] != 2) {
                // If we REVOKE the table grants, we should not need to modify the
                // `mysql`.`db`, `mysql`.`tables_priv` and `mysql`.`columns_priv` tables manually...
                $user_host_condition = PMA_getPrivCondition($this_user, $this_host);
                $queries[] = 'DELETE FROM `mysql`.`db`' . $user_host_condition;
                $queries[] = 'DELETE FROM `mysql`.`tables_priv`' . $user_host_condition;
                $queries[] = 'DELETE FROM `mysql`.`columns_priv`' . $user_host_condition;
            }
        }
        if (!empty($drop_users_db)) {
            $queries[] = 'DROP DATABASE IF EXISTS ' . PMA_backquote($this_user) . ';';
            $GLOBALS['reload'] = true;
            PMA_reloadNavigation();
        }
    }
    if (empty($_REQUEST['change_copy'])) {
        if (empty($queries)) {
            $show_error_header = true;
            $message = $GLOBALS['strDeleteNoUsersSelected'];
        } else {
            if ($_REQUEST['mode'] == 3) {
                $queries[] = '# ' . $GLOBALS['strReloadingThePrivileges'] . ' ...';
                $queries[] = 'FLUSH PRIVILEGES;';
            }
            foreach ($queries as $sql_query) {
                if ($sql_query{0} != '#') {
                    PMA_DBI_query($sql_query, $GLOBALS['userlink']);
                }
            }
            $sql_query = join("\n", $queries);
            $message = $GLOBALS['strUsersDeleted'];
            unset($queries);
        }
    }
}


/**
 * Changes / copies a user, part V
 */
if (!empty($_REQUEST['change_copy'])) {
    $tmp_count = 0;
    foreach ($queries as $sql_query) {
        if ($sql_query{0} != '#') {
            PMA_DBI_query($sql_query);
        }
        // when there is a query containing a hidden password, take it
        // instead of the real query sent
        if (isset($queries_for_display[$tmp_count])) {
            $queries[$tmp_count] = $queries_for_display[$tmp_count];
        }
        $tmp_count++;
    }
    $message = $GLOBALS['strSuccess'];
    $sql_query = join("\n", $queries);
}


/**
 * Reloads the privilege tables into memory
 */
if (!empty($_REQUEST['flush_privileges'])) {
    $sql_query = 'FLUSH PRIVILEGES;';
    PMA_DBI_query($sql_query);
    $message = $GLOBALS['strPrivilegesReloaded'];
}


/**
 * Displays the links
 *
 * $_REQUEST['checkprivs'] come from 'Rights'-tab in database pages
 * $_REQUEST['viewing_mode'] come from 'Rights'-tab in database pages
 */
if (isset($_REQUEST['viewing_mode']) && $_REQUEST['viewing_mode'] == 'db') {
     $db = $_REQUEST['checkprivs'];
     $url_query .= '&amp;goto=db_operations.php';
     $url_params['goto'] = 'db_operations.php';

     // Gets the database structure
     $sub_part = '_structure';
     require './libraries/db_details_db_info.inc.php';
} else {
    require './libraries/server_links.inc.php';
}


/**
 * defines some standard links
 */
$link_edit = '<a href="server_privileges.php?' . $GLOBALS['url_query']
    .'&amp;username=%s'
    .'&amp;hostname=%s'
    .'&amp;dbname=%s'
    .'&amp;tablename=%s">';
if ($GLOBALS['cfg']['PropertiesIconic']) {
    $link_edit .= '<img class="icon"'
        . ' src="' . $GLOBALS['pmaThemeImage'] . 'b_usredit.png" width="16"'
        . ' height="16" alt="' . $GLOBALS['strEditPrivileges'] . '"'
        . ' title="' . $GLOBALS['strEditPrivileges'] . '" />';
} else {
    $link_edit .= $GLOBALS['strEditPrivileges'];
}
$link_edit .= '</a>';

$link_revoke = '<a href="server_privileges.php?' . $GLOBALS['url_query']
    .'&amp;username=%s'
    .'&amp;hostname=%s'
    .'&amp;dbname=%s'
    .'&amp;tablename=%s'
    .'&amp;revokeall=1">';
if ($GLOBALS['cfg']['PropertiesIconic']) {
    $link_revoke .= '<img class="icon"'
        . ' src="' . $GLOBALS['pmaThemeImage'] . 'b_usrdrop.png" width="16"'
        . ' height="16" alt="' . $GLOBALS['strRevoke'] . '"'
        . ' title="' . $GLOBALS['strRevoke'] . '" />';
} else {
    $link_revoke .= $GLOBALS['strRevoke'];
}
$link_revoke .= '</a>';

/**
 * Displays the page
 */
if (empty($_REQUEST['adduser']) && (! isset($_REQUEST['checkprivs']) || ! strlen($_REQUEST['checkprivs']))) {
    if (! isset($_REQUEST['username'])) {
        // No username is given --> display the overview
        echo '<h2>' . "\n"
           . ($GLOBALS['cfg']['MainPageIconic']
                ? '<img class="icon" src="'. $GLOBALS['pmaThemeImage'] . 'b_usrlist.png" alt="" />'
                : '')
           . $GLOBALS['strUserOverview'] . "\n"
           . '</h2>' . "\n";

        /**
         * @todo why not SELECT * ???
         */
        $sql_query =
            'SELECT `User`,' .
            '       `Host`,' .
            '       IF(`Password` = ' . (PMA_MYSQL_INT_VERSION >= 40100 ? '_latin1 ' : '') . '\'\', \'N\', \'Y\') AS \'Password\',' .
            '       `Select_priv`,' .
            '       `Insert_priv`,' .
            '       `Update_priv`,' .
            '       `Delete_priv`,' .
            '       `Index_priv`,' .
            '       `Alter_priv`,' .
            '       `Create_priv`,' .
            '       `Drop_priv`,' .
            '       `Grant_priv`,' .
            '       `References_priv`,' .
            '       `Reload_priv`,' .
            '       `Shutdown_priv`,' .
            '       `Process_priv`,' .
            '       `File_priv`';

        if (PMA_MYSQL_INT_VERSION >= 40002) {
            $sql_query .= ', `Show_db_priv`, `Super_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Execute_priv`, `Repl_slave_priv`, `Repl_client_priv`';
        }

        if (PMA_MYSQL_INT_VERSION >= 50001) {
            $sql_query .= ', `Create_view_priv`, `Show_view_priv`';
        }

        if (PMA_MYSQL_INT_VERSION >= 50003) {
            $sql_query .= ', `Create_user_priv`, `Create_routine_priv`, `Alter_routine_priv`';
        }

        $sql_query .= '  FROM `mysql`.`user`';

        $sql_query .= (isset($_REQUEST['initial']) ? PMA_RangeOfUsers($_REQUEST['initial']) : '');

        $sql_query .= ' ORDER BY `User` ASC, `Host` ASC;';
        $res = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_STORE);

        if (! $res) {
            // the query failed! This may have two reasons:
            // - the user does not have enough privileges
            // - the privilege tables use a structure of an earlier version.
            // so let's try a more simple query

            $sql_query = 'SELECT * FROM `mysql`.`user`';
            $res = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_STORE);

            if (!$res) {
                echo '<i>' . $GLOBALS['strNoPrivileges'] . '</i>' . "\n";
                PMA_DBI_free_result($res);
                unset($res);
            } else {
                // rabus: This message is hardcoded because I will replace it by
                // a automatic repair feature soon.
                echo '<div class="warning">' . "\n"
                   . '    Warning: Your privilege table structure seems to be older than this MySQL version!<br />' . "\n"
                   . '    Please run the script <tt>mysql_fix_privilege_tables</tt> that should be included in your MySQL server distribution to solve this problem!' . "\n"
                   . '</div><br />' . "\n";
            }
        } else {

            // we also want users not in table `user` but in other table
            $tables = PMA_DBI_fetch_result('SHOW TABLES FROM `mysql`;');

            $tables_to_search_for_users = array(
                'user', 'db', 'tables_priv', 'columns_priv', 'procs_priv',
            );

            $db_rights_sqls = array();
            foreach ($tables_to_search_for_users as $table_search_in) {
                if (in_array($table_search_in, $tables)) {
                    $db_rights_sqls[] =
                        'SELECT DISTINCT `User`, `Host`'
                         . ' FROM `mysql`.`' . $table_search_in . '` ' . (isset($_REQUEST['initial']) ? PMA_RangeOfUsers($_REQUEST['initial']) : '');
                }
            }

            $user_defaults = array(
                'User'      => '',
                'Host'      => '%',
                'Password'  => '?',
                'Grant_priv' => 'N',
                'privs'     => array('USAGE'),
            );

            // for the rights
            $db_rights = array();

            // do not use UNION DISTINCT, as it's not allowed before
            // MySQL 4.0.17, and because "it does nothing" (cf manual)
            if (PMA_MYSQL_INT_VERSION >= 40000) {
                $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
                    .' ORDER BY `User` ASC, `Host` ASC';

                $db_rights_result = PMA_DBI_query($db_rights_sql);

                while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                    $db_rights_row = array_merge($user_defaults, $db_rights_row);
                    $db_rights[$db_rights_row['User']][$db_rights_row['Host']] =
                        $db_rights_row;
                }
            } else {
                foreach ($db_rights_sqls as $db_rights_sql) {
                    $db_rights_result = PMA_DBI_query($db_rights_sql);

                    while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                        $db_rights_row = array_merge($user_defaults, $db_rights_row);
                        $db_rights[$db_rights_row['User']][$db_rights_row['Host']] =
                            $db_rights_row;
                    }
                }
            }
            PMA_DBI_free_result($db_rights_result);
            unset($db_rights_sql, $db_rights_sqls, $db_rights_result, $db_rights_row);
            ksort($db_rights);

            /**
             * Displays the initials
             */
            // for all initials, even non A-Z
            $array_initials = PMA_DBI_fetch_result(
                'SELECT DISTINCT UPPER(LEFT(' . PMA_convert_using('User') . ',1))'
                    . ' FROM `user` ORDER BY `User` ASC',
                0, 0);

            // initialize to false the letters A-Z
            for ($letter_counter = 1; $letter_counter < 27; $letter_counter++) {
                if (isset($array_initials[chr($letter_counter + 64)])) {
                    $array_initials[chr($letter_counter + 64)] = true;
                } else {
                    $array_initials[chr($letter_counter + 64)] = false;
                }
            }

            // Display the initials, which can be any characters, not
            // just letters. For letters A-Z, we add the non-used letters
            // as greyed out.

            uksort($array_initials, "strnatcasecmp");

            echo '<table cellspacing="5"><tr>';
            foreach ($array_initials as $tmp_initial => $initial_was_found) {
                if ($initial_was_found) {
                    echo '<td><a href="server_privilegs.php?' . $GLOBALS['url_query'] . '&amp;initial=' . urlencode($tmp_initial) . '">' . $tmp_initial . '</a></td>' . "\n";
                } else {
                    echo '<td>' . $tmp_initial . '</td>';
                }
            }
            echo '<td><a href="server_privilegs.php?' . $GLOBALS['url_query'] . '&amp;showall=1">[' . $GLOBALS['strShowAll'] . ']</a></td>' . "\n";
            echo '</tr></table>';

            /**
            * Display the user overview
            * (if less than 50 users, display them immediately)
            */

            if (isset($_REQUEST['initial']) || isset($_REQUEST['showall'])
              || PMA_DBI_num_rows($res) < 50) {

                while ($row = PMA_DBI_fetch_assoc($res)) {
                    $row['privs'] = PMA_extractPrivInfo($row, true);
                    $db_rights[$row['User']][$row['Host']] = $row;
                }
                @PMA_DBI_free_result($res);
                unset($res);

                echo '<form name="usersForm" id="usersForm" action="server_privileges.php" method="post">' . "\n"
                   . PMA_generate_common_hidden_inputs()
                   . '    <table id="tableuserrights" class="data">' . "\n"
                   . '    <thead>' . "\n"
                   . '        <tr><td></td>' . "\n"
                   . '            <th>' . $GLOBALS['strUser'] . '</th>' . "\n"
                   . '            <th>' . $GLOBALS['strHost'] . '</th>' . "\n"
                   . '            <th>' . $GLOBALS['strPassword'] . '</th>' . "\n"
                   . '            <th>' . $GLOBALS['strGlobalPrivileges'] . ' '
                   . PMA_showHint($GLOBALS['strEnglishPrivileges']) . '</th>' . "\n"
                   . '            <th>' . $GLOBALS['strGrantOption'] . '</th>' . "\n"
                   . '            ' . ($GLOBALS['cfg']['PropertiesIconic']
                        ? '<td></td>'
                        : '<th>' . $GLOBALS['strAction'] . '</th>')
                   . "\n";
                echo '        </tr>' . "\n";
                echo '    </thead>' . "\n";
                echo '    <tbody>' . "\n";
                $odd_row = true;
                $index_checkbox = -1;
                foreach ($db_rights as $user) {
                    $index_checkbox++;
                    ksort($user);
                    foreach ($user as $host) {
                        $index_checkbox++;
                        echo '        <tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                           . '            <td><input type="checkbox" name="selected_usr[]"'
                           . ' id="checkbox_sel_users_' . $index_checkbox . '"'
                           . ' value="' . str_replace(chr(27), '&#27;',
                                    htmlentities($host['User'] . $user_host_separator . $host['Host'])) . '"'
                           . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"') . ' /></td>' . "\n"
                           . '            <td><label for="checkbox_sel_users_' . $index_checkbox . '">'
                           . (empty($host['User']) ? '<span style="color: #FF0000">'
                           . $GLOBALS['strAny'] . '</span>' : htmlspecialchars($host['User']))
                           . '</label></td>' . "\n"
                           . '            <td>' . htmlspecialchars($host['Host']) . '</td>' . "\n";
                        echo '            <td>';
                        switch ($host['Password']) {
                            case 'Y':
                                echo $GLOBALS['strYes'];
                                break;
                            case 'N':
                                echo '<span style="color: #FF0000">' . $GLOBALS['strNo'] . '</span>';
                                break;
                            // this happens if this is a definition not coming from mysql.user
                            default:
                                echo '--'; // in future version, replace by "not present"
                                break;
                        } // end switch
                        echo '</td>' . "\n"
                           . '            <td><tt>' . "\n"
                           . '                ' . implode(',' . "\n" . '            ', $host['privs']) . "\n"
                           . '                </tt></td>' . "\n"
                           . '            <td>' . ($host['Grant_priv'] == 'Y' ? $GLOBALS['strYes'] : $GLOBALS['strNo']) . '</td>' . "\n"
                           . '            <td align="center">';
                        printf($link_edit, urlencode($host['User']),
                            urlencode($host['Host']), '', '');
                        echo '</td>' . "\n"
                           . '        </tr>' . "\n";
                        $odd_row = ! $odd_row;
                    }
                }

                unset($user, $host, $odd_row);
                echo '    </tbody></table>' . "\n"
                   .'<img class="selectallarrow"'
                   .' src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png"'
                   .' width="38" height="22"'
                   .' alt="' . $GLOBALS['strWithChecked'] . '" />' . "\n"
                   .'<a href="./server_privileges.php?' . $GLOBALS['url_query'] .  '&amp;checkall=1"'
                   .' onclick="if (markAllRows(\'usersForm\')) return false;">'
                   . $GLOBALS['strCheckAll'] . '</a>' . "\n"
                   .'/' . "\n"
                   .'<a href="server_privileges.php?' . $GLOBALS['url_query'] .  '"'
                   .' onclick="if (unMarkAllRows(\'usersForm\')) return false;">'
                   . $GLOBALS['strUncheckAll'] . '</a>' . "\n";

                // add/delete user fieldset
                echo '    <fieldset id="fieldset_add_user">' . "\n"
                   . '        <a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;adduser=1">' . "\n"
                   . ($GLOBALS['cfg']['PropertiesIconic'] ? '            <img class="icon" src="' . $pmaThemeImage . 'b_usradd.png" width="16" height="16" alt="" />' . "\n" : '')
                   . '            ' . $GLOBALS['strAddUser'] . '</a>' . "\n"
                   . '    </fieldset>' . "\n"
                   . '    <fieldset id="fieldset_delete_user">'
                   . '        <legend>' . "\n"
                   . ($GLOBALS['cfg']['PropertiesIconic'] ? '            <img class="icon" src="' . $pmaThemeImage . 'b_usrdrop.png" width="16" height="16" alt="" />' . "\n" : '')
                   . '            ' . $GLOBALS['strRemoveSelectedUsers'] . '' . "\n"
                   . '        </legend>' . "\n";

                // before MySQL 4.1.1, we offer some choices for the delete
                // mode, but for 4.1.1+, it will be done with REVOKEing the
                // privileges then a DROP USER (even no REVOKE at all
                // for MySQL 5), so no need to offer so many options
                if (PMA_MYSQL_INT_VERSION < 40101) {
                   echo '        <input type="radio" title="' . $GLOBALS['strJustDelete'] . ' ' . $GLOBALS['strJustDeleteDescr'] . '" name="mode" id="radio_mode_1" value="1" checked="checked" />' . "\n"
                   . '        <label for="radio_mode_1" title="' . $GLOBALS['strJustDelete'] . ' ' . $GLOBALS['strJustDeleteDescr'] . '">' . "\n"
                   . '            ' . $GLOBALS['strJustDelete'] . "\n"
                   . '        </label><br />' . "\n"
                   . '        <input type="radio" title="' . $GLOBALS['strRevokeAndDelete'] . ' ' . $GLOBALS['strRevokeAndDeleteDescr'] . '" name="mode" id="radio_mode_2" value="2" />' . "\n"
                   . '        <label for="radio_mode_2" title="' . $GLOBALS['strRevokeAndDelete'] . ' ' . $GLOBALS['strRevokeAndDeleteDescr'] . '">' . "\n"
                   . '            ' . $GLOBALS['strRevokeAndDelete'] . "\n"
                   . '        </label><br />' . "\n"
                   . '        <input type="radio" title="' . $GLOBALS['strDeleteAndFlush'] . ' ' . $GLOBALS['strDeleteAndFlushDescr'] . '" name="mode" id="radio_mode_3" value="3" />' . "\n"
                   . '        <label for="radio_mode_3" title="' . $GLOBALS['strDeleteAndFlush'] . ' ' . $GLOBALS['strDeleteAndFlushDescr'] . '">' . "\n"
                   . '            ' . $GLOBALS['strDeleteAndFlush'] . "\n"
                   . '        </label><br />' . "\n";
                 } else {
                     echo '        <input type="hidden" name="mode" value="2" />' . "\n"
                     . '( ' . $GLOBALS['strRevokeAndDelete'] . ' )<br />' . "\n";
                 }

                 echo '        <input type="checkbox" title="' . $GLOBALS['strDropUsersDb'] . '" name="drop_users_db" id="checkbox_drop_users_db" />' . "\n"
                   . '        <label for="checkbox_drop_users_db" title="' . $GLOBALS['strDropUsersDb'] . '">' . "\n"
                   . '            ' . $GLOBALS['strDropUsersDb'] . "\n"
                   . '        </label>' . "\n"
                   . '    </fieldset>' . "\n"
                   . '    <fieldset id="fieldset_delete_user_footer" class="tblFooters">' . "\n"
                   . '        <input type="submit" name="delete" value="' . $GLOBALS['strGo'] . '" id="buttonGo" />' . "\n"
                   . '    </fieldset>' . "\n";
            } else {

                unset ($row);
                echo '    <fieldset id="fieldset_add_user">' . "\n"
                   . '        <a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;adduser=1">' . "\n"
                   . ($GLOBALS['cfg']['PropertiesIconic'] ? '            <img class="icon" src="' . $pmaThemeImage . 'b_usradd.png" width="16" height="16" alt="" />' . "\n" : '')
                   . '            ' . $GLOBALS['strAddUser'] . '</a>' . "\n"
                   . '    </fieldset>' . "\n";
            } // end if (display overview)
            echo '</form>' . "\n"
               . '<div class="warning">' . "\n"
               . '    ' . sprintf($GLOBALS['strFlushPrivilegesNote'], '<a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;flush_privileges=1">', '</a>') . "\n"
               . '</div>' . "\n";
         }


    } else {

        // A user was selected -> display the user's properties

        echo '<h2>' . "\n"
           . ($GLOBALS['cfg']['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_usredit.png" width="16" height="16" alt="" />' : '')
           . $GLOBALS['strUser'] . ' <i><a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;username=' . urlencode($_REQUEST['username']) . '&amp;hostname=' . urlencode($_REQUEST['hostname']) . '">\'' . htmlspecialchars($_REQUEST['username']) . '\'@\'' . htmlspecialchars($_REQUEST['hostname']) . '\'</a></i>' . "\n";
        if (strlen($dbname)) {
            if ($dbname_is_wildcard) {
                echo '    - ' . $GLOBALS['strDatabases'];
            } else {
                echo '    - ' . $GLOBALS['strDatabase'];
            }
            echo ' <i><a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $GLOBALS['url_query'] . '&amp;db=' . urlencode($dbname) . '&amp;reload=1">' . htmlspecialchars($dbname) . '</a></i>' . "\n";
            if (strlen($tablename)) {
                echo '    - ' . $GLOBALS['strTable'] . ' <i><a href="' . $GLOBALS['cfg']['DefaultTabTable'] . '?' . $GLOBALS['url_query'] . '&amp;db=' . urlencode($dbname) . '&amp;table=' . urlencode($tablename) . '&amp;reload=1">' . htmlspecialchars($tablename) . '</a></i>' . "\n";
            }
        }
        echo ' : ' . $GLOBALS['strEditPrivileges'] . '</h2>' . "\n";
        $res = PMA_DBI_query('SELECT \'foo\' FROM `mysql`.`user` WHERE ' . PMA_convert_using('User') . ' = ' . PMA_convert_using(PMA_sqlAddslashes($_REQUEST['username']), 'quoted') . ' AND ' . PMA_convert_using('Host') . ' = ' . PMA_convert_using($_REQUEST['hostname'], 'quoted') . ';', null, PMA_DBI_QUERY_STORE);
        $user_does_not_exists = (PMA_DBI_num_rows($res) < 1);
        PMA_DBI_free_result($res);
        unset($res);
        if ($user_does_not_exists) {
            echo $GLOBALS['strUserNotFound'];
            PMA_displayLoginInformationFields();
            //require_once('./libraries/footer.inc.php');
        }
        echo '<form name="usersForm" id="usersForm" action="server_privileges.php" method="post">' . "\n";

        $post_params = array('username' => $_REQUEST['username'],
            'hostname' => $_REQUEST['hostname']);
        if (strlen($dbname)) {
            $post_params['dbname'] = $dbname;
            if (strlen($tablename)) {
                $post_params['tablename'] = $tablename;
            }
        }
        echo PMA_generate_common_hidden_inputs($post_params);

        PMA_displayPrivTable((strlen($dbname) ? $dbname : '*'),
            ((strlen($dbname) && strlen($tablename)) ? $tablename : '*'),
            true, 3);
        echo '</form>' . "\n";

        if ((! strlen($tablename)) && ! $dbname_is_wildcard) {

            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards

            // table header
            echo '<form action="server_privileges.php" method="post">' . "\n"
               . PMA_generate_common_hidden_inputs(array('username' => $_REQUEST['username'], 'hostname' => $_REQUEST['hostname']))
               . '<fieldset>' . "\n"
               . '<legend>' . (strlen($dbname) ? $GLOBALS['strTblPrivileges'] : $GLOBALS['strDbPrivileges']) . '</legend>' . "\n"
               . '<table class="data">' . "\n"
               . '<thead>' . "\n"
               . '<tr><th>' . (strlen($dbname) ? $GLOBALS['strTable'] : $GLOBALS['strDatabase']) . '</th>' . "\n"
               . '    <th>' . $GLOBALS['strPrivileges'] . '</th>' . "\n"
               . '    <th>' . $GLOBALS['strGrantOption'] . '</th>' . "\n"
               . '    <th>' . (strlen($dbname) ? $GLOBALS['strColumnPrivileges'] : $GLOBALS['strTblPrivileges']) . '</th>' . "\n"
               . '    <th colspan="2">' . $GLOBALS['strAction'] . '</th>' . "\n"
               . '</tr>' . "\n"
               . '</thead>' . "\n"
               . '<tbody>' . "\n";

            $user_host_condition =
                ' WHERE ' . PMA_convert_using('`User`')
                . ' = ' . PMA_convert_using(PMA_sqlAddslashes($_REQUEST['username']), 'quoted')
                . ' AND ' . PMA_convert_using('`Host`')
                . ' = ' . PMA_convert_using($_REQUEST['hostname'], 'quoted');

            // table body
            // get data

            // we also want privielgs for this user not in table `db` but in other table
            $tables = PMA_DBI_fetch_result('SHOW TABLES FROM `mysql`;');
            if (! strlen($dbname)) {

                // no db name given, so we want all privs for the given user

                $tables_to_search_for_users = array(
                    'tables_priv', 'columns_priv',
                );

                $db_rights_sqls = array();
                foreach ($tables_to_search_for_users as $table_search_in) {
                    if (in_array($table_search_in, $tables)) {
                        $db_rights_sqls[] = '
                            SELECT DISTINCT `Db`
                                   FROM `mysql`.`' . $table_search_in . '`
                                  ' . $user_host_condition;
                    }
                }

                $user_defaults = array(
                    'Db'          => '',
                    'Grant_priv'  => 'N',
                    'privs'       => array('USAGE'),
                    'Table_privs' => true,
                );

                // for the rights
                $db_rights = array();

                if (PMA_MYSQL_INT_VERSION >= 40000) {
                    $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
                        .' ORDER BY `Db` ASC';

                    $db_rights_result = PMA_DBI_query($db_rights_sql);

                    while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                        $db_rights_row = array_merge($user_defaults, $db_rights_row);
                        // only Db names in the table `mysql`.`db` uses wildcards
                        // as we are in the db specific rights display we want
                        // all db names escaped, also from other sources
                        $db_rights_row['Db'] = PMA_escape_mysql_wildcards(
                            $db_rights_row['Db']);
                        $db_rights[$db_rights_row['Db']] = $db_rights_row;
                    }
                } else {
                    foreach ($db_rights_sqls as $db_rights_sql) {
                        $db_rights_result = PMA_DBI_query($db_rights_sql);

                        while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                            $db_rights_row = array_merge($user_defaults, $db_rights_row);
                            $db_rights[$db_rights_row['Db']] = $db_rights_row;
                        }
                    }
                }
                PMA_DBI_free_result($db_rights_result);
                unset($db_rights_sql, $db_rights_sqls, $db_rights_result, $db_rights_row);

                $sql_query = 'SELECT * FROM `mysql`.`db`' . $user_host_condition . ' ORDER BY `Db` ASC';
                $res = PMA_DBI_query($sql_query);
                unset($sql_query);

                while ($row = PMA_DBI_fetch_assoc($res)) {
                    if (isset($db_rights[$row['Db']])) {
                        $db_rights[$row['Db']] = array_merge($db_rights[$row['Db']], $row);
                    } else {
                        $db_rights[$row['Db']] = $row;
                    }
                    // there are db specific rights for this user
                    // so we can drop this db rights
                    $db_rights[$row['Db']]['can_delete'] = true;
                }
                PMA_DBI_free_result($res);
                unset($row, $res);

            } else {

                // db name was given,
                // so we want all user specific rights for this db

                $user_host_condition .=
                    ' AND ' . PMA_convert_using('`Db`')
                    .' LIKE ' . PMA_convert_using($dbname, 'quoted');

                $tables_to_search_for_users = array(
                    'columns_priv',
                );

                $db_rights_sqls = array();
                foreach ($tables_to_search_for_users as $table_search_in) {
                    if (in_array($table_search_in, $tables)) {
                        $db_rights_sqls[] = '
                            SELECT DISTINCT `Table_name`
                                   FROM `mysql`.`' . $table_search_in . '`
                                  ' . $user_host_condition;
                    }
                }

                $user_defaults = array(
                    'Table_name'  => '',
                    'Grant_priv'  => 'N',
                    'privs'       => array('USAGE'),
                    'Column_priv' => true,
                );

                // for the rights
                $db_rights = array();

                if (PMA_MYSQL_INT_VERSION >= 40000) {
                    $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
                        .' ORDER BY `Table_name` ASC';

                    $db_rights_result = PMA_DBI_query($db_rights_sql);

                    while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                        $db_rights_row = array_merge($user_defaults, $db_rights_row);
                        $db_rights[$db_rights_row['Table_name']] = $db_rights_row;
                    }
                } else {
                    foreach ($db_rights_sqls as $db_rights_sql) {
                        $db_rights_result = PMA_DBI_query($db_rights_sql);

                        while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                            $db_rights_row = array_merge($user_defaults, $db_rights_row);
                            $db_rights[$db_rights_row['Table_name']] = $db_rights_row;
                        }
                    }
                }
                PMA_DBI_free_result($db_rights_result);
                unset($db_rights_sql, $db_rights_sqls, $db_rights_result, $db_rights_row);

                $sql_query =
                    'SELECT `Table_name`,'
                    .' `Table_priv`,'
                    .' IF(`Column_priv` = ' . (PMA_MYSQL_INT_VERSION >= 40100 ? '_latin1 ' : '') . ' \'\', 0, 1)'
                    .' AS \'Column_priv\''
                    .' FROM `mysql`.`tables_priv`'
                    . $user_host_condition
                    .' ORDER BY `Table_name` ASC;';
                $res = PMA_DBI_query($sql_query);
                unset($sql_query);

                while ($row = PMA_DBI_fetch_assoc($res)) {
                    if (isset($db_rights[$row['Table_name']])) {
                        $db_rights[$row['Table_name']] = array_merge($db_rights[$row['Table_name']], $row);
                    } else {
                        $db_rights[$row['Table_name']] = $row;
                    }
                }
                PMA_DBI_free_result($res);
                unset($row, $res);
            }
            ksort($db_rights);

            // display rows
            if (count($db_rights) < 1) {
                echo '<tr class="odd">' . "\n"
                   . '    <td colspan="6"><center><i>' . $GLOBALS['strNone'] . '</i></center></td>' . "\n"
                   . '</tr>' . "\n";
            } else {
                $odd_row = true;
                $found_rows = array();
                //while ($row = PMA_DBI_fetch_assoc($res)) {
                foreach ($db_rights as $row) {
                    $found_rows[] = strlen($dbname) ? $row['Table_name'] : $row['Db'];

                    echo '<tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                       . '    <td>' . htmlspecialchars(strlen($dbname) ? $row['Table_name'] : $row['Db']) . '</td>' . "\n"
                       . '    <td><tt>' . "\n"
                       . '        ' . join(',' . "\n" . '            ', PMA_extractPrivInfo($row, true)) . "\n"
                       . '        </tt></td>' . "\n"
                       . '    <td>' . (((! strlen($dbname) && $row['Grant_priv'] == 'Y') || (strlen($dbname) && in_array('Grant', explode(',', $row['Table_priv'])))) ? $GLOBALS['strYes'] : $GLOBALS['strNo']) . '</td>' . "\n"
                       . '    <td>';
                    if (! empty($row['Table_privs']) || ! empty ($row['Column_priv'])) {
                        echo $GLOBALS['strYes'];
                    } else {
                        echo $GLOBALS['strNo'];
                    }
                    echo '</td>' . "\n"
                       . '    <td>';
                    printf($link_edit, urlencode($_REQUEST['username']),
                        urlencode($_REQUEST['hostname']),
                        urlencode(strlen($dbname) ? $dbname : $row['Db']),
                        (strlen($dbname) ? urlencode($row['Table_name']) : ''));
                    echo '</td>' . "\n"
                       . '    <td>';
                    if (! empty($row['can_delete']) || isset($row['Table_name'])
                      && strlen($row['Table_name'])) {
                        printf($link_revoke, urlencode($_REQUEST['username']),
                            urlencode($_REQUEST['hostname']),
                            urlencode(strlen($dbname) ? $dbname : $row['Db']),
                            (strlen($dbname) ? urlencode($row['Table_name']) : ''));
                    }
                    echo '</td>' . "\n"
                       . '</tr>' . "\n";
                    $odd_row = ! $odd_row;
                } // end while
            }
            unset($row);
            echo '</tbody>' . "\n"
               . '</table>' . "\n";

            if (! strlen($dbname)) {

                // no database name was give, display select db

                if (! empty($found_rows)) {
                    $pred_db_array = array_diff(
                        PMA_DBI_fetch_result('SHOW DATABASES;'),
                        $found_rows);
                } else {
                    $pred_db_array =PMA_DBI_fetch_result('SHOW DATABASES;');
                }

                echo '    <label for="text_dbname">' . $GLOBALS['strAddPrivilegesOnDb'] . ':</label>' . "\n";
                if (!empty($pred_db_array)) {
                    echo '    <select name="pred_dbname" onchange="this.form.submit();">' . "\n"
                       . '        <option value="" selected="selected">' . $GLOBALS['strUseTextField'] . ':</option>' . "\n";
                    foreach ($pred_db_array as $current_db) {
                        $current_db = PMA_escape_mysql_wildcards($current_db);
                        echo '        <option value="' . htmlspecialchars($current_db) . '">'
                            . htmlspecialchars($current_db) . '</option>' . "\n";
                    }
                    echo '    </select>' . "\n";
                }
                echo '    <input type="text" id="text_dbname" name="dbname" />' . "\n"
                    .PMA_showHint($GLOBALS['strEscapeWildcards']);
            } else {
                echo '    <input type="hidden" name="dbname" value="' . htmlspecialchars($dbname) . '"/>' . "\n"
                   . '    <label for="text_tablename">' . $GLOBALS['strAddPrivilegesOnTbl'] . ':</label>' . "\n";
                if ($res = @PMA_DBI_try_query('SHOW TABLES FROM ' . PMA_backquote($dbname) . ';', null, PMA_DBI_QUERY_STORE)) {
                    $pred_tbl_array = array();
                    while ($row = PMA_DBI_fetch_row($res)) {
                        if (!isset($found_rows) || !in_array($row[0], $found_rows)) {
                            $pred_tbl_array[] = $row[0];
                        }
                    }
                    PMA_DBI_free_result($res);
                    unset($res, $row);
                    if (!empty($pred_tbl_array)) {
                        echo '    <select name="pred_tablename" onchange="this.form.submit();">' . "\n"
                           . '        <option value="" selected="selected">' . $GLOBALS['strUseTextField'] . ':</option>' . "\n";
                        foreach ($pred_tbl_array as $current_table) {
                            echo '        <option value="' . htmlspecialchars($current_table) . '">' . htmlspecialchars($current_table) . '</option>' . "\n";
                        }
                        echo '    </select>' . "\n";
                    }
                } else {
                    unset($res);
                }
                echo '    <input type="text" id="text_tablename" name="tablename" />' . "\n";
            }
            echo '</fieldset>' . "\n";
            echo '<fieldset class="tblFooters">' . "\n"
               . '    <input type="submit" value="' . $GLOBALS['strGo'] . '" />'
               . '</fieldset>' . "\n"
               . '</form>' . "\n";
        }

        if (! strlen($dbname) && ! $user_does_not_exists) {
            echo '<form action="server_privileges.php" method="post" onsubmit="return checkPassword(this);">' . "\n"
               . PMA_generate_common_hidden_inputs(array('username' => $_REQUEST['username'], 'hostname' => $_REQUEST['hostname']))
               . '<fieldset id="fieldset_change_password">' . "\n"
               . '    <legend>' . $GLOBALS['strChangePassword'] . '</legend>' . "\n"
               . '    <table class="data">' . "\n"
               . '    <tr class="odd noclick">' . "\n"
               . '        <td><input type="radio" name="nopass" value="1" id="radio_nopass_1" onclick="pw_pma_pw.value=\'\'; pw_pma_pw2.value=\'\';" /></td>' . "\n"
               . '        <td colspan="2"><label for="radio_nopass_1">' . $GLOBALS['strNoPassword'] . '</label></td>' . "\n"
               . '    </tr>' . "\n"
               . '    <tr class="even noclick">' . "\n"
               . '        <td><input type="radio" name="nopass" value="0" id="radio_nopass_0" onclick="document.getElementById(\'pw_pma_pw\').focus();" /></td>' . "\n"
               . '        <td><label for="radio_nopass_0">' . $GLOBALS['strPassword'] . ':</label></td>' . "\n"
               . '        <td><input type="password" name="pma_pw" id="pw_pma_pw" onchange="if (this.value != \'\') radio_nopass_0.checked = true; else radio_nopass_1.checked = true;" /></td>' . "\n"
               . '    </tr>' . "\n"
               . '    <tr class="odd noclick">' . "\n"
               . '        <td></td>' . "\n"
               . '        <td><label for="pw_pma_pw2">' . $GLOBALS['strReType'] . ':</label></td>' . "\n"
               . '        <td><input type="password" name="pma_pw2" id="pw_pma_pw2" onchange="if (this.value != \'\') radio_nopass_0.checked = true; else radio_nopass_1.checked = true;" /></td>' . "\n"
               . '    </tr>' . "\n"
               . '    </table>' . "\n"
               . '</fieldset>' . "\n"
               . '<fieldset id="fieldset_change_password_footer" class="tblFooters">' . "\n"
               . '    <input type="submit" name="change_pw" value="' . $GLOBALS['strGo'] . '" />' . "\n"
               . '</fieldset>' . "\n"
               . '</form>' . "\n"
               . '<form action="server_privileges.php" method="post" onsubmit="return checkPassword(this);">' . "\n"
               . PMA_generate_common_hidden_inputs(array('old_username' => $_REQUEST['username'], 'old_hostname' => $_REQUEST['hostname']))
               . '<fieldset id="fieldset_change_copy_user">' . "\n"
               . '    <legend>' . $GLOBALS['strChangeCopyUser'] . '</legend>' . "\n";
            PMA_displayLoginInformationFields('change', 3);
            echo '    <fieldset>' . "\n"
               . '        <legend>' . $GLOBALS['strChangeCopyMode'] . '</legend>' . "\n"
               . '        <input type="radio" name="mode" value="4" id="radio_mode_4" checked="checked" /><label for="radio_mode_4">' . "\n"
               . '            ' . $GLOBALS['strChangeCopyModeCopy'] . "\n"
               . '        </label><br />' . "\n"
               . '        <input type="radio" name="mode" value="1" id="radio_mode_1" /><label for="radio_mode_1">' . "\n"
               . '            ' . $GLOBALS['strChangeCopyModeJustDelete'] . "\n"
               . '        </label><br />' . "\n"
               . '        <input type="radio" name="mode" value="2" id="radio_mode_2" /><label for="radio_mode_2">' . "\n"
               . '            ' . $GLOBALS['strChangeCopyModeRevoke'] . "\n"
               . '        </label><br />' . "\n"
               . '        <input type="radio" name="mode" value="3" id="radio_mode_3" /><label for="radio_mode_3">' . "\n"
               . '            ' . $GLOBALS['strChangeCopyModeDeleteAndReload'] . "\n"
               . '        </label>' . "\n"
               . '    </fieldset>' . "\n"
               . '</fieldset>' . "\n"
               . '<fieldset id="fieldset_change_copy_user_footer" class="tblFooters">' . "\n"
               . '    <input type="submit" name="change_copy" value="' . $GLOBALS['strGo'] . '" />' . "\n"
               . '</fieldset>' . "\n"
               . '</form>' . "\n";
        }
    }
} elseif (!empty($_REQUEST['adduser'])) {
    // Add a new user
    $GLOBALS['url_query'] .= '&amp;adduser=1';
    echo '<h2>' . "\n"
       . ($GLOBALS['cfg']['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_usradd.png" width="16" height="16" alt="" />' : '')
       . '    ' . $GLOBALS['strAddUser'] . "\n"
       . '</h2>' . "\n"
       . '<form name="usersForm" id="usersForm" action="server_privileges.php" method="post" onsubmit="return checkAddUser(this);">' . "\n"
       . PMA_generate_common_hidden_inputs();
    PMA_displayLoginInformationFields('new', 2);
    PMA_displayPrivTable('*', '*', false, 1);
    echo '    <fieldset id="fieldset_add_user_footer" class="tblFooters">' . "\n"
       . '        <input type="submit" name="adduser_submit" value="' . $GLOBALS['strGo'] . '" />' . "\n"
       . '    </fieldset>' . "\n"
       . '</form>' . "\n";
} else {
    // check the privileges for a particular database.
    echo '<table id="tablespecificuserrights" class="data">' . "\n"
       . '<caption class="tblHeaders">' . "\n"
       . ($GLOBALS['cfg']['PropertiesIconic'] ? '            <img class="icon" src="' . $pmaThemeImage . 'b_usrcheck.png" width="16" height="16" alt="" />' . "\n" : '')
       . '    ' . sprintf($GLOBALS['strUsersHavingAccessToDb'], '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url(array('db' => $_REQUEST['checkprivs'])) . '">' .  htmlspecialchars($_REQUEST['checkprivs']) . '</a>') . "\n"
       . '</caption>' . "\n"
       . '<thead>' . "\n"
       . '    <tr><th>' . $GLOBALS['strUser'] . '</th>' . "\n"
       . '        <th>' . $GLOBALS['strHost'] . '</th>' . "\n"
       . '        <th>' . $GLOBALS['strType'] . '</th>' . "\n"
       . '        <th>' . $GLOBALS['strPrivileges'] . '</th>' . "\n"
       . '        <th>' . $GLOBALS['strGrantOption'] . '</th>' . "\n"
       . '        <th>' . $GLOBALS['strAction'] . '</th>' . "\n"
       . '    </tr>' . "\n"
       . '</thead>' . "\n"
       . '<tbody>' . "\n";
    $odd_row = true;
    unset($row, $row1, $row2);
    // now, we build the table...
    if (PMA_MYSQL_INT_VERSION >= 40000) {
        // Starting with MySQL 4.0.0, we may use UNION SELECTs and this makes
        // the job much easier here!

        $no = PMA_convert_using('N', 'quoted');

        $list_of_privileges =
            PMA_convert_using('Select_priv') . ' AS Select_priv, '
            . PMA_convert_using('Insert_priv') . ' AS Insert_priv, '
            . PMA_convert_using('Update_priv') . ' AS Update_priv, '
            . PMA_convert_using('Delete_priv') . ' AS Delete_priv, '
            . PMA_convert_using('Create_priv') . ' AS Create_priv, '
            . PMA_convert_using('Drop_priv') . ' AS Drop_priv, '
            . PMA_convert_using('Grant_priv') . ' AS Grant_priv, '
            . PMA_convert_using('References_priv') . ' AS References_priv';

        $list_of_compared_privileges =
            PMA_convert_using('Select_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('Insert_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('Update_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('Delete_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('Create_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('Drop_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('Grant_priv') . ' = ' . $no
            . ' AND ' . PMA_convert_using('References_priv') . ' = ' . $no;

        $sql_query =
            '(SELECT ' . PMA_convert_using('`User`') . ' AS `User`, '
            .   PMA_convert_using('`Host`') . ' AS `Host`, '
            .   PMA_convert_using('`Db`') . ' AS `Db`, '
            .   $list_of_privileges
            .' FROM `mysql`.`db`'
            .' WHERE ' . PMA_convert_using($_REQUEST['checkprivs'], 'quoted')
            .' LIKE ' . PMA_convert_using('`Db`')
            .' AND NOT (' . $list_of_compared_privileges. ')) '
            .'UNION '
            .'(SELECT ' . PMA_convert_using('`User`') . ' AS `User`, '
            .   PMA_convert_using('`Host`') . ' AS `Host`, '
            .   PMA_convert_using('*', 'quoted') .' AS `Db`, '
            .   $list_of_privileges
            .' FROM `mysql`.`user` '
            .' WHERE NOT (' . $list_of_compared_privileges . ')) '
            .' ORDER BY `User` ASC,'
            .'  `Host` ASC,'
            .'  `Db` ASC;';
        $res = PMA_DBI_query($sql_query);
        $row = PMA_DBI_fetch_assoc($res);
        if ($row) {
            $found = true;
        }
    } else {
        // With MySQL 3, we need 2 seperate queries here.
        $sql_query = 'SELECT * FROM `mysql`.`user` WHERE NOT (`Select_priv` = \'N\' AND `Insert_priv` = \'N\' AND `Update_priv` = \'N\' AND `Delete_priv` = \'N\' AND `Create_priv` = \'N\' AND `Drop_priv` = \'N\' AND `Grant_priv` = \'N\' AND `References_priv` = \'N\') ORDER BY `User` ASC, `Host` ASC;';
        $res1 = PMA_DBI_query($sql_query);
        $row1 = PMA_DBI_fetch_assoc($res1);
        $sql_query =
            'SELECT * FROM `mysql`.`db`'
            .' WHERE \'' . $_REQUEST['checkprivs'] . '\''
            .'   LIKE `Db`'
            .'  AND NOT (`Select_priv` = \'N\''
            .'   AND `Insert_priv` = \'N\''
            .'   AND `Update_priv` = \'N\''
            .'   AND `Delete_priv` = \'N\''
            .'   AND `Create_priv` = \'N\''
            .'   AND `Drop_priv` = \'N\''
            .'   AND `Grant_priv` = \'N\''
            .'   AND `References_priv` = \'N\')'
            .' ORDER BY `User` ASC, `Host` ASC;';
        $res2 = PMA_DBI_query($sql_query);
        $row2 = PMA_DBI_fetch_assoc($res2);
        if ($row1 || $row2) {
            $found = true;
        }
    } // end if (PMA_MYSQL_INT_VERSION >= 40000) ... else ...
    if ($found) {
        while (true) {
            // prepare the current user
            if (PMA_MYSQL_INT_VERSION >= 40000) {
                $current_privileges = array();
                $current_user = $row['User'];
                $current_host = $row['Host'];
                while ($row && $current_user == $row['User'] && $current_host == $row['Host']) {
                    $current_privileges[] = $row;
                    $row = PMA_DBI_fetch_assoc($res);
                }
            } else {
                $current_privileges = array();
                if ($row1 && (!$row2 || ($row1['User'] < $row2['User'] || ($row1['User'] == $row2['User'] && $row1['Host'] <= $row2['Host'])))) {
                    $current_user = $row1['User'];
                    $current_host = $row1['Host'];
                    $current_privileges = array($row1);
                    $row1 = PMA_DBI_fetch_assoc($res1);
                } else {
                    $current_user = $row2['User'];
                    $current_host = $row2['Host'];
                    $current_privileges = array();
                }
                while ($row2 && $current_user == $row2['User'] && $current_host == $row2['Host']) {
                    $current_privileges[] = $row2;
                    $row2 = PMA_DBI_fetch_assoc($res2);
                }
            }
            echo '    <tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
               . '        <td';
            if (count($current_privileges) > 1) {
                echo ' rowspan="' . count($current_privileges) . '"';
            }
            echo '>' . (empty($current_user) ? '<span style="color: #FF0000">' . $GLOBALS['strAny'] . '</span>' : htmlspecialchars($current_user)) . "\n"
               . '        </td>' . "\n"
               . '        <td';
            if (count($current_privileges) > 1) {
                echo ' rowspan="' . count($current_privileges) . '"';
            }
            echo '>' . htmlspecialchars($current_host) . '</td>' . "\n";
            foreach ($current_privileges as $current) {
                echo '        <td>' . "\n"
                   . '            ';
                if (!isset($current['Db']) || $current['Db'] == '*') {
                    echo $GLOBALS['strGlobal'];
                } elseif ($current['Db'] == PMA_escape_mysql_wildcards($_REQUEST['checkprivs'])) {
                    echo $GLOBALS['strDbSpecific'];
                } else {
                    echo $GLOBALS['strWildcard'], ': <tt>' . htmlspecialchars($current['Db']) . '</tt>';
                }
                echo "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n"
                   . '            <tt>' . "\n"
                   . '                ' . join(',' . "\n" . '                ', PMA_extractPrivInfo($current, true)) . "\n"
                   . '            </tt>' . "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n"
                   . '            ' . ($current['Grant_priv'] == 'Y' ? $GLOBALS['strYes'] : $GLOBALS['strNo']) . "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n";
                printf($link_edit, urlencode($current_user),
                    urlencode($current_host),
                    urlencode(! isset($current['Db']) || $current['Db'] == '*' ? '' : $current['Db']),
                    '');
                echo '</td>' . "\n"
                   . '    </tr>' . "\n";
            }
            if (empty($row) && empty($row1) && empty($row2)) {
                break;
            }
            $odd_row = ! $odd_row;
        }
    } else {
        echo '    <tr class="odd">' . "\n"
           . '        <td colspan="6">' . "\n"
           . '            ' . $GLOBALS['strNoUsersFound'] . "\n"
           . '        </td>' . "\n"
           . '    </tr>' . "\n";
    }
    echo '</tbody>' . "\n"
       . '</table>' . "\n";
} // end if (empty($adduser) && empty($_REQUEST['checkprivs'])) ... elseif ... else ...


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';

?>
