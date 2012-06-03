<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Does the common work
 */
$GLOBALS['js_include'][] = 'server_privileges.js';
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'codemirror/lib/codemirror.js';
$GLOBALS['js_include'][] = 'codemirror/mode/mysql/mysql.js';
$_add_user_error = false;

require './libraries/server_common.inc.php';

if ($GLOBALS['cfg']['AjaxEnable']) {
    $conditional_class = 'ajax';
} else {
    $conditional_class = '';
}

/**
 * Messages are built using the message name
 */
$strPrivDescAllPrivileges = __('Includes all privileges except GRANT.');
$strPrivDescAlter = __('Allows altering the structure of existing tables.');
$strPrivDescAlterRoutine = __('Allows altering and dropping stored routines.');
$strPrivDescCreateDb = __('Allows creating new databases and tables.');
$strPrivDescCreateRoutine = __('Allows creating stored routines.');
$strPrivDescCreateTbl = __('Allows creating new tables.');
$strPrivDescCreateTmpTable = __('Allows creating temporary tables.');
$strPrivDescCreateUser = __('Allows creating, dropping and renaming user accounts.');
$strPrivDescCreateView = __('Allows creating new views.');
$strPrivDescDelete = __('Allows deleting data.');
$strPrivDescDropDb = __('Allows dropping databases and tables.');
$strPrivDescDropTbl = __('Allows dropping tables.');
$strPrivDescEvent = __('Allows to set up events for the event scheduler');
$strPrivDescExecute = __('Allows executing stored routines.');
$strPrivDescFile = __('Allows importing data from and exporting data into files.');
$strPrivDescGrant = __('Allows adding users and privileges without reloading the privilege tables.');
$strPrivDescIndex = __('Allows creating and dropping indexes.');
$strPrivDescInsert = __('Allows inserting and replacing data.');
$strPrivDescLockTables = __('Allows locking tables for the current thread.');
$strPrivDescMaxConnections = __('Limits the number of new connections the user may open per hour.');
$strPrivDescMaxQuestions = __('Limits the number of queries the user may send to the server per hour.');
$strPrivDescMaxUpdates = __('Limits the number of commands that change any table or database the user may execute per hour.');
$strPrivDescMaxUserConnections = __('Limits the number of simultaneous connections the user may have.');
$strPrivDescProcess = __('Allows viewing processes of all users');
$strPrivDescReferences = __('Has no effect in this MySQL version.');
$strPrivDescReload = __('Allows reloading server settings and flushing the server\'s caches.');
$strPrivDescReplClient = __('Allows the user to ask where the slaves / masters are.');
$strPrivDescReplSlave = __('Needed for the replication slaves.');
$strPrivDescSelect = __('Allows reading data.');
$strPrivDescShowDb = __('Gives access to the complete list of databases.');
$strPrivDescShowView = __('Allows performing SHOW CREATE VIEW queries.');
$strPrivDescShutdown = __('Allows shutting down the server.');
$strPrivDescSuper = __('Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.');
$strPrivDescTrigger = __('Allows creating and dropping triggers');
$strPrivDescUpdate = __('Allows changing data.');
$strPrivDescUsage = __('No privileges.');

/**
 * Checks if a dropdown box has been used for selecting a database / table
 */
if (PMA_isValid($_REQUEST['pred_tablename'])) {
    $tablename = $_REQUEST['pred_tablename'];
    unset($pred_tablename);
} elseif (PMA_isValid($_REQUEST['tablename'])) {
    $tablename = $_REQUEST['tablename'];
} else {
    unset($tablename);
}

if (PMA_isValid($_REQUEST['pred_dbname'])) {
    $dbname = $_REQUEST['pred_dbname'];
    unset($pred_dbname);
} elseif (PMA_isValid($_REQUEST['dbname'])) {
    $dbname = $_REQUEST['dbname'];
} else {
    unset($dbname);
    unset($tablename);
}

if (isset($dbname)) {
    $db_and_table = PMA_backquote(PMA_unescape_mysql_wildcards($dbname)) . '.';
    if (isset($tablename)) {
        $db_and_table .= PMA_backquote($tablename);
    } else {
        $db_and_table .= '*';
    }
} else {
    $db_and_table = '*.*';
}

// check if given $dbname is a wildcard or not
if (isset($dbname)) {
    //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
    if (preg_match('/(?<!\\\\)(?:_|%)/i', $dbname)) {
        $dbname_is_wildcard = true;
    } else {
        $dbname_is_wildcard = false;
    }
}

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $is_superuser) {
    include './libraries/server_links.inc.php';
    echo '<h2>' . "\n"
       . PMA_getIcon('b_usrlist.png')
       . __('Privileges') . "\n"
       . '</h2>' . "\n";
    PMA_Message::error(__('No Privileges'))->display();
    include './libraries/footer.inc.php';
}

$random_n = mt_rand(0, 1000000); // a random number that will be appended to the id of the user forms

/**
 * Escapes wildcard in a database+table specification
 * before using it in a GRANT statement.
 *
 * Escaping a wildcard character in a GRANT is only accepted at the global
 * or database level, not at table level; this is why I remove
 * the escaping character. Internally, in mysql.tables_priv.Db there are
 * no escaping (for example test_db) but in mysql.db you'll see test\_db
 * for a db-specific privilege.
 *
 * @param string $dbname    Database name
 * @param string $tablename Table name
 *
 * @return string the escaped (if necessary) database.table
 */
function PMA_wildcardEscapeForGrant($dbname, $tablename)
{

    if (! strlen($dbname)) {
        $db_and_table = '*.*';
    } else {
        if (strlen($tablename)) {
            $db_and_table = PMA_backquote(PMA_unescape_mysql_wildcards($dbname)) . '.';
            $db_and_table .= PMA_backquote($tablename);
        } else {
            $db_and_table = PMA_backquote($dbname) . '.';
            $db_and_table .= '*';
        }
    }
    return $db_and_table;
}

/**
 * Generates a condition on the user name
 *
 * @param string $initial the user's initial
 *
 * @return  string   the generated condition
 */
function PMA_rangeOfUsers($initial = '')
{
    // strtolower() is used because the User field
    // might be BINARY, so LIKE would be case sensitive
    if (! empty($initial)) {
        $ret = " WHERE `User` LIKE '" . PMA_sqlAddSlashes($initial, true) . "%'"
            . " OR `User` LIKE '" . PMA_sqlAddSlashes(strtolower($initial), true) . "%'";
    } else {
        $ret = '';
    }
    return $ret;
} // end function

/**
 * Extracts the privilege information of a priv table row
 *
 * @param array   $row        the row
 * @param boolean $enableHTML add <dfn> tag with tooltips
 *
 * @global  resource $user_link the database connection
 *
 * @return  array
 */
function PMA_extractPrivInfo($row = '', $enableHTML = false)
{
    $grants = array(
        array(
            'Select_priv',
            'SELECT',
            __('Allows reading data.')),
        array(
            'Insert_priv',
            'INSERT',
            __('Allows inserting and replacing data.')),
        array(
            'Update_priv',
            'UPDATE',
            __('Allows changing data.')),
        array(
            'Delete_priv',
            'DELETE',
            __('Allows deleting data.')),
        array(
            'Create_priv',
            'CREATE',
            __('Allows creating new databases and tables.')),
        array(
            'Drop_priv',
            'DROP',
            __('Allows dropping databases and tables.')),
        array(
            'Reload_priv',
            'RELOAD',
            __('Allows reloading server settings and flushing the server\'s caches.')),
        array(
            'Shutdown_priv',
            'SHUTDOWN',
            __('Allows shutting down the server.')),
        array(
            'Process_priv',
            'PROCESS',
            __('Allows viewing processes of all users')),
        array(
            'File_priv',
            'FILE',
            __('Allows importing data from and exporting data into files.')),
        array(
            'References_priv',
            'REFERENCES',
            __('Has no effect in this MySQL version.')),
        array(
            'Index_priv',
            'INDEX',
            __('Allows creating and dropping indexes.')),
        array(
            'Alter_priv',
            'ALTER',
            __('Allows altering the structure of existing tables.')),
        array(
            'Show_db_priv',
            'SHOW DATABASES',
            __('Gives access to the complete list of databases.')),
        array(
            'Super_priv',
            'SUPER',
            __('Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.')),
        array(
            'Create_tmp_table_priv',
            'CREATE TEMPORARY TABLES',
            __('Allows creating temporary tables.')),
        array(
            'Lock_tables_priv',
            'LOCK TABLES',
            __('Allows locking tables for the current thread.')),
        array(
            'Repl_slave_priv',
            'REPLICATION SLAVE',
            __('Needed for the replication slaves.')),
        array(
            'Repl_client_priv',
            'REPLICATION CLIENT',
            __('Allows the user to ask where the slaves / masters are.')),
        array(
            'Create_view_priv',
            'CREATE VIEW',
            __('Allows creating new views.')),
        array(
            'Event_priv',
            'EVENT',
            __('Allows to set up events for the event scheduler')),
        array(
            'Trigger_priv',
            'TRIGGER',
            __('Allows creating and dropping triggers')),
        // for table privs:
        array(
            'Create View_priv',
            'CREATE VIEW',
            __('Allows creating new views.')),
        array(
            'Show_view_priv',
            'SHOW VIEW',
            __('Allows performing SHOW CREATE VIEW queries.')),
        // for table privs:
        array(
            'Show view_priv',
            'SHOW VIEW',
            __('Allows performing SHOW CREATE VIEW queries.')),
        array(
            'Create_routine_priv',
            'CREATE ROUTINE',
            __('Allows creating stored routines.')),
        array(
            'Alter_routine_priv',
            'ALTER ROUTINE',
            __('Allows altering and dropping stored routines.')),
        array(
            'Create_user_priv',
            'CREATE USER',
            __('Allows creating, dropping and renaming user accounts.')),
        array(
            'Execute_priv',
            'EXECUTE',
            __('Allows executing stored routines.')),
    );

    if (! empty($row) && isset($row['Table_priv'])) {
        $row1 = PMA_DBI_fetch_single_row(
            'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
            'ASSOC', $GLOBALS['userlink']
        );
        $av_grants = explode('\',\'', substr($row1['Type'], 5, strlen($row1['Type']) - 7));
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
    $allPrivileges = true;
    foreach ($grants as $current_grant) {
        if ((! empty($row) && isset($row[$current_grant[0]]))
            || (empty($row) && isset($GLOBALS[$current_grant[0]]))
        ) {
            if ((! empty($row) && $row[$current_grant[0]] == 'Y')
                || (empty($row)
                && ($GLOBALS[$current_grant[0]] == 'Y'
                || (is_array($GLOBALS[$current_grant[0]])
                && count($GLOBALS[$current_grant[0]]) == $GLOBALS['column_count']
                && empty($GLOBALS[$current_grant[0] . '_none']))))
            ) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $current_grant[2] . '">' . $current_grant[1] . '</dfn>';
                } else {
                    $privs[] = $current_grant[1];
                }
            } elseif (! empty($GLOBALS[$current_grant[0]])
             && is_array($GLOBALS[$current_grant[0]])
             && empty($GLOBALS[$current_grant[0] . '_none'])) {
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
            $privs[] = '<dfn title="' . __('No privileges.') . '">USAGE</dfn>';
        } else {
            $privs[] = 'USAGE';
        }
    } elseif ($allPrivileges && (! isset($GLOBALS['grant_count']) || count($privs) == $GLOBALS['grant_count'])) {
        if ($enableHTML) {
            $privs = array('<dfn title="' . __('Includes all privileges except GRANT.') . '">ALL PRIVILEGES</dfn>');
        } else {
            $privs = array('ALL PRIVILEGES');
        }
    }
    return $privs;
} // end of the 'PMA_extractPrivInfo()' function

/**
 * Displays on which column(s) a table-specific privilege is granted
 */
function PMA_display_column_privs($columns, $row, $name_for_select,
    $priv_for_header, $name, $name_for_dfn, $name_for_current)
{
    echo '    <div class="item" id="div_item_' . $name . '">' . "\n"
       . '        <label for="select_' . $name . '_priv">' . "\n"
       . '            <tt><dfn title="' . $name_for_dfn . '">'
        . $priv_for_header . '</dfn></tt>' . "\n"
       . '        </label><br />' . "\n"
       . '        <select id="select_' . $name . '_priv" name="'
        . $name_for_select . '[]" multiple="multiple" size="8">' . "\n";

    foreach ($columns as $current_column => $current_column_privileges) {
        echo '            <option value="' . htmlspecialchars($current_column) . '"';
        if ($row[$name_for_select] == 'Y' || $current_column_privileges[$name_for_current]) {
            echo ' selected="selected"';
        }
        echo '>' . htmlspecialchars($current_column) . '</option>' . "\n";
    }

    echo '        </select>' . "\n"
       . '        <i>' . __('Or') . '</i>' . "\n"
       . '        <label for="checkbox_' . $name_for_select
        . '_none"><input type="checkbox"'
        . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"')
        . ' name="' . $name_for_select . '_none" id="checkbox_'
        . $name_for_select . '_none" title="' . _pgettext('None privileges', 'None') . '" />'
        . _pgettext('None privileges', 'None') . '</label>' . "\n"
       . '    </div>' . "\n";
} // end function


/**
 * Displays the privileges form table
 *
 * @param string  $db     the database
 * @param string  $table  the table
 * @param boolean $submit wheather to display the submit button or not
 *
 * @global  array      $cfg         the phpMyAdmin configuration
 * @global  ressource  $user_link   the database connection
 *
 * @return  void
 */
function PMA_displayPrivTable($db = '*', $table = '*', $submit = true)
{
    global $random_n;

    if ($db == '*') {
        $table = '*';
    }

    if (isset($GLOBALS['username'])) {
        $username = $GLOBALS['username'];
        $hostname = $GLOBALS['hostname'];
        if ($db == '*') {
            $sql_query = "SELECT * FROM `mysql`.`user`"
                ." WHERE `User` = '" . PMA_sqlAddSlashes($username) . "'"
                ." AND `Host` = '" . PMA_sqlAddSlashes($hostname) . "';";
        } elseif ($table == '*') {
            $sql_query = "SELECT * FROM `mysql`.`db`"
                ." WHERE `User` = '" . PMA_sqlAddSlashes($username) . "'"
                ." AND `Host` = '" . PMA_sqlAddSlashes($hostname) . "'"
                ." AND '" . PMA_unescape_mysql_wildcards($db) . "'"
                ." LIKE `Db`;";
        } else {
            $sql_query = "SELECT `Table_priv`"
                ." FROM `mysql`.`tables_priv`"
                ." WHERE `User` = '" . PMA_sqlAddSlashes($username) . "'"
                ." AND `Host` = '" . PMA_sqlAddSlashes($hostname) . "'"
                ." AND `Db` = '" . PMA_unescape_mysql_wildcards($db) . "'"
                ." AND `Table_name` = '" . PMA_sqlAddSlashes($table) . "';";
        }
        $row = PMA_DBI_fetch_single_row($sql_query);
    }
    if (empty($row)) {
        if ($table == '*') {
            if ($db == '*') {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`user`;';
            } elseif ($table == '*') {
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
        $row1 = PMA_DBI_fetch_single_row(
            'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
            'ASSOC', $GLOBALS['userlink']
        );
        // note: in MySQL 5.0.3 we get "Create View', 'Show view';
        // the View for Create is spelled with uppercase V
        // the view for Show is spelled with lowercase v
        // and there is a space between the words

        $av_grants = explode('\',\'', substr($row1['Type'], strpos($row1['Type'], '(') + 2, strpos($row1['Type'], ')') - strpos($row1['Type'], '(') - 3));
        unset($row1);
        $users_grants = explode(',', $row['Table_priv']);

        foreach ($av_grants as $current_grant) {
            $row[$current_grant . '_priv'] = in_array($current_grant, $users_grants) ? 'Y' : 'N';
        }
        unset($row['Table_priv'], $current_grant, $av_grants, $users_grants);

        // get collumns
        $res = PMA_DBI_try_query('SHOW COLUMNS FROM ' . PMA_backquote(PMA_unescape_mysql_wildcards($db)) . '.' . PMA_backquote($table) . ';');
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
            .' WHERE `User`'
            .' = \'' . PMA_sqlAddSlashes($username) . "'"
            .' AND `Host`'
            .' = \'' . PMA_sqlAddSlashes($hostname) . "'"
            .' AND `Db`'
            .' = \'' . PMA_sqlAddSlashes(PMA_unescape_mysql_wildcards($db)) . "'"
            .' AND `Table_name`'
            .' = \'' . PMA_sqlAddSlashes($table) . '\';'
        );

        while ($row1 = PMA_DBI_fetch_row($res)) {
            $row1[1] = explode(',', $row1[1]);
            foreach ($row1[1] as $current) {
                $columns[$row1[0]][$current] = true;
            }
        }
        PMA_DBI_free_result($res);
        unset($res, $row1, $current);

        echo '<input type="hidden" name="grant_count" value="' . count($row) . '" />' . "\n"
           . '<input type="hidden" name="column_count" value="' . count($columns) . '" />' . "\n"
           . '<fieldset id="fieldset_user_priv">' . "\n"
           . '    <legend>' . __('Table-specific privileges')
           . PMA_showHint(__('Note: MySQL privilege names are expressed in English'))
           . '</legend>' . "\n";



        // privs that are attached to a specific column
        PMA_display_column_privs(
            $columns, $row, 'Select_priv', 'SELECT',
            'select', __('Allows reading data.'), 'Select'
        );

        PMA_display_column_privs(
            $columns, $row, 'Insert_priv', 'INSERT',
            'insert', __('Allows inserting and replacing data.'), 'Insert'
        );

        PMA_display_column_privs(
            $columns, $row, 'Update_priv', 'UPDATE',
            'update', __('Allows changing data.'), 'Update'
        );

        PMA_display_column_privs(
            $columns, $row, 'References_priv', 'REFERENCES', 'references',
            __('Has no effect in this MySQL version.'), 'References'
        );

        // privs that are not attached to a specific column

        echo '    <div class="item">' . "\n";
        foreach ($row as $current_grant => $current_grant_value) {
            if (in_array(substr($current_grant, 0, (strlen($current_grant) - 5)),
                    array('Select', 'Insert', 'Update', 'References'))) {
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

            echo '        <div class="item">' . "\n"
               . '            <input type="checkbox"'
               . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"')
               . ' name="' . $current_grant . '" id="checkbox_' . $current_grant
               . '" value="Y" '
               . ($current_grant_value == 'Y' ? 'checked="checked" ' : '')
               . 'title="';

            echo (isset($GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))])
                ? $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))]
                : $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)) . 'Tbl']) . '"/>' . "\n";

            echo '            <label for="checkbox_' . $current_grant
                . '"><tt><dfn title="'
                . (isset($GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))])
                    ? $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))]
                    : $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)) . 'Tbl'])
               . '">' . strtoupper(substr($current_grant, 0, strlen($current_grant) - 5)) . '</dfn></tt></label>' . "\n"
               . '        </div>' . "\n";
        } // end foreach ()

        echo '    </div>' . "\n";
        // for Safari 2.0.2
        echo '    <div class="clearfloat"></div>' . "\n";

    } else {

        // g l o b a l    o r    d b - s p e c i f i c
        //
        $privTable_names = array(0 => __('Data'), 1 => __('Structure'), 2 => __('Administration'));

        // d a t a
        $privTable[0] = array(
            array('Select', 'SELECT', __('Allows reading data.')),
            array('Insert', 'INSERT', __('Allows inserting and replacing data.')),
            array('Update', 'UPDATE', __('Allows changing data.')),
            array('Delete', 'DELETE', __('Allows deleting data.'))
        );
        if ($db == '*') {
            $privTable[0][] = array('File', 'FILE', __('Allows importing data from and exporting data into files.'));
        }

        // s t r u c t u r e
        $privTable[1] = array(
            array('Create', 'CREATE', ($table == '*' ? __('Allows creating new databases and tables.') : __('Allows creating new tables.'))),
            array('Alter', 'ALTER', __('Allows altering the structure of existing tables.')),
            array('Index', 'INDEX', __('Allows creating and dropping indexes.')),
            array('Drop', 'DROP', ($table == '*' ? __('Allows dropping databases and tables.') : __('Allows dropping tables.'))),
            array('Create_tmp_table', 'CREATE TEMPORARY TABLES', __('Allows creating temporary tables.')),
            array('Show_view', 'SHOW VIEW', __('Allows performing SHOW CREATE VIEW queries.')),
            array('Create_routine', 'CREATE ROUTINE', __('Allows creating stored routines.')),
            array('Alter_routine', 'ALTER ROUTINE', __('Allows altering and dropping stored routines.')),
            array('Execute', 'EXECUTE', __('Allows executing stored routines.')),
        );
        // this one is for a db-specific priv: Create_view_priv
        if (isset($row['Create_view_priv'])) {
            $privTable[1][] = array('Create_view', 'CREATE VIEW', __('Allows creating new views.'));
        }
        // this one is for a table-specific priv: Create View_priv
        if (isset($row['Create View_priv'])) {
            $privTable[1][] = array('Create View', 'CREATE VIEW', __('Allows creating new views.'));
        }
        if (isset($row['Event_priv'])) {
            // MySQL 5.1.6
            $privTable[1][] = array('Event', 'EVENT', __('Allows to set up events for the event scheduler'));
            $privTable[1][] = array('Trigger', 'TRIGGER', __('Allows creating and dropping triggers'));
        }

        // a d m i n i s t r a t i o n
        $privTable[2] = array(
            array('Grant', 'GRANT', __('Allows adding users and privileges without reloading the privilege tables.')),
        );
        if ($db == '*') {
            $privTable[2][] = array('Super', 'SUPER', __('Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.'));
            $privTable[2][] = array('Process', 'PROCESS', __('Allows viewing processes of all users'));
            $privTable[2][] = array('Reload', 'RELOAD', __('Allows reloading server settings and flushing the server\'s caches.'));
            $privTable[2][] = array('Shutdown', 'SHUTDOWN', __('Allows shutting down the server.'));
            $privTable[2][] = array('Show_db', 'SHOW DATABASES', __('Gives access to the complete list of databases.'));
        }
        $privTable[2][] = array('Lock_tables', 'LOCK TABLES', __('Allows locking tables for the current thread.'));
        $privTable[2][] = array('References', 'REFERENCES', __('Has no effect in this MySQL version.'));
        if ($db == '*') {
            $privTable[2][] = array('Repl_client', 'REPLICATION CLIENT', __('Allows the user to ask where the slaves / masters are.'));
            $privTable[2][] = array('Repl_slave', 'REPLICATION SLAVE', __('Needed for the replication slaves.'));
            $privTable[2][] = array('Create_user', 'CREATE USER', __('Allows creating, dropping and renaming user accounts.'));
        }
        echo '<input type="hidden" name="grant_count" value="'
            . (count($privTable[0]) + count($privTable[1]) + count($privTable[2]) - (isset($row['Grant_priv']) ? 1 : 0))
            . '" />' . "\n"
           . '<fieldset id="fieldset_user_global_rights">' . "\n"
           . '    <legend>' . "\n"
           . '        '
            . ($db == '*'
                ? __('Global privileges')
                : ($table == '*'
                    ? __('Database-specific privileges')
                    : __('Table-specific privileges'))) . "\n"
           . '        (<a href="server_privileges.php?'
            . $GLOBALS['url_query'] . '&amp;checkall=1" onclick="setCheckboxes(\'addUsersForm_' . $random_n . '\', true); return false;">'
            . __('Check All') . '</a> /' . "\n"
           . '        <a href="server_privileges.php?'
            . $GLOBALS['url_query'] . '" onclick="setCheckboxes(\'addUsersForm_' . $random_n . '\', false); return false;">'
            . __('Uncheck All') . '</a>)' . "\n"
           . '    </legend>' . "\n"
           . '    <p><small><i>' . __('Note: MySQL privilege names are expressed in English') . '</i></small></p>' . "\n";

        // Output the Global privilege tables with checkboxes
        foreach ($privTable as $i => $table) {
            echo '    <fieldset>' . "\n"
                . '        <legend>' . __($privTable_names[$i]) . '</legend>' . "\n";
            foreach ($table as $priv) {
                echo '        <div class="item">' . "\n"
                    . '            <input type="checkbox"'
                    .                   ' name="' . $priv[0] . '_priv" id="checkbox_' . $priv[0] . '_priv"'
                    .                   ' value="Y" title="' . $priv[2] . '"'
                    .                   ((! empty($GLOBALS['checkall']) || $row[$priv[0] . '_priv'] == 'Y') ?  ' checked="checked"' : '')
                    .               '/>' . "\n"
                    . '            <label for="checkbox_' . $priv[0] . '_priv"><tt><dfn title="' . $priv[2] . '">'
                    .                    $priv[1] . '</dfn></tt></label>' . "\n"
                    . '        </div>' . "\n";
            }
            echo '    </fieldset>' . "\n";
        }

        // The "Resource limits" box is not displayed for db-specific privs
        if ($db == '*') {
            echo '    <fieldset>' . "\n"
               . '        <legend>' . __('Resource limits') . '</legend>' . "\n"
               . '        <p><small><i>' . __('Note: Setting these options to 0 (zero) removes the limit.') . '</i></small></p>' . "\n"
               . '        <div class="item">' . "\n"
               . '            <label for="text_max_questions"><tt><dfn title="'
                . __('Limits the number of queries the user may send to the server per hour.') . '">MAX QUERIES PER HOUR</dfn></tt></label>' . "\n"
               . '            <input type="text" name="max_questions" id="text_max_questions" value="'
                . $row['max_questions'] . '" size="11" maxlength="11" title="' . __('Limits the number of queries the user may send to the server per hour.') . '" />' . "\n"
               . '        </div>' . "\n"
               . '        <div class="item">' . "\n"
               . '            <label for="text_max_updates"><tt><dfn title="'
                . __('Limits the number of commands that change any table or database the user may execute per hour.') . '">MAX UPDATES PER HOUR</dfn></tt></label>' . "\n"
               . '            <input type="text" name="max_updates" id="text_max_updates" value="'
                . $row['max_updates'] . '" size="11" maxlength="11" title="' . __('Limits the number of commands that change any table or database the user may execute per hour.') . '" />' . "\n"
               . '        </div>' . "\n"
               . '        <div class="item">' . "\n"
               . '            <label for="text_max_connections"><tt><dfn title="'
                . __('Limits the number of new connections the user may open per hour.') . '">MAX CONNECTIONS PER HOUR</dfn></tt></label>' . "\n"
               . '            <input type="text" name="max_connections" id="text_max_connections" value="'
                . $row['max_connections'] . '" size="11" maxlength="11" title="' . __('Limits the number of new connections the user may open per hour.') . '" />' . "\n"
               . '        </div>' . "\n"
               . '        <div class="item">' . "\n"
               . '            <label for="text_max_user_connections"><tt><dfn title="'
                . __('Limits the number of simultaneous connections the user may have.') . '">MAX USER_CONNECTIONS</dfn></tt></label>' . "\n"
               . '            <input type="text" name="max_user_connections" id="text_max_user_connections" value="'
                . $row['max_user_connections'] . '" size="11" maxlength="11" title="' . __('Limits the number of simultaneous connections the user may have.') . '" />' . "\n"
               . '        </div>' . "\n"
               . '    </fieldset>' . "\n";
        }
        // for Safari 2.0.2
        echo '    <div class="clearfloat"></div>' . "\n";
    }
    echo '</fieldset>' . "\n";
    if ($submit) {
        echo '<fieldset id="fieldset_user_privtable_footer" class="tblFooters">' . "\n"
           . '    <input type="submit" name="update_privs" value="' . __('Go') . '" />' . "\n"
           . '</fieldset>' . "\n";
    }
} // end of the 'PMA_displayPrivTable()' function


/**
 * Displays the fields used by the "new user" form as well as the
 * "change login information / copy user" form.
 *
 * @param string $mode are we creating a new user or are we just
 *                     changing  one? (allowed values: 'new', 'change')
 *
 * @global  array      $cfg     the phpMyAdmin configuration
 * @global  ressource  $user_link the database connection
 *
 * @return  void
 */
function PMA_displayLoginInformationFields($mode = 'new')
{
    // Get user/host name lengths
    $fields_info = PMA_DBI_get_columns('mysql', 'user', null, true);
    $username_length = 16;
    $hostname_length = 41;
    foreach ($fields_info as $val) {
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
    unset($fields_info);

    if (isset($GLOBALS['username']) && strlen($GLOBALS['username']) === 0) {
        $GLOBALS['pred_username'] = 'any';
    }
    echo '<fieldset id="fieldset_add_user_login">' . "\n"
       . '<legend>' . __('Login Information') . '</legend>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="select_pred_username">' . "\n"
       . '    ' . __('User name') . ':' . "\n"
       . '</label>' . "\n"
       . '<span class="options">' . "\n"
       . '    <select name="pred_username" id="select_pred_username" title="' . __('User name') . '"' . "\n"
       . '        onchange="if (this.value == \'any\') { username.value = \'\'; } else if (this.value == \'userdefined\') { username.focus(); username.select(); }">' . "\n"
       . '        <option value="any"' . ((isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] == 'any') ? ' selected="selected"' : '') . '>' . __('Any user') . '</option>' . "\n"
       . '        <option value="userdefined"' . ((! isset($GLOBALS['pred_username']) || $GLOBALS['pred_username'] == 'userdefined') ? ' selected="selected"' : '') . '>' . __('Use text field') . ':</option>' . "\n"
       . '    </select>' . "\n"
       . '</span>' . "\n"
       . '<input type="text" name="username" maxlength="'
        . $username_length . '" title="' . __('User name') . '"'
        . (empty($GLOBALS['username'])
            ? ''
            : ' value="' . htmlspecialchars(isset($GLOBALS['new_username'])
                ? $GLOBALS['new_username']
                : $GLOBALS['username']) . '"')
        . ' onchange="pred_username.value = \'userdefined\';" />' . "\n"
       . '</div>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="select_pred_hostname">' . "\n"
       . '    ' . __('Host') . ':' . "\n"
       . '</label>' . "\n"
       . '<span class="options">' . "\n"
       . '    <select name="pred_hostname" id="select_pred_hostname" title="' . __('Host') . '"' . "\n";
    $_current_user = PMA_DBI_fetch_value('SELECT USER();');
    if (! empty($_current_user)) {
        $thishost = str_replace("'", '', substr($_current_user, (strrpos($_current_user, '@') + 1)));
        if ($thishost == 'localhost' || $thishost == '127.0.0.1') {
            unset($thishost);
        }
    }
    echo '    onchange="if (this.value == \'any\') { hostname.value = \'%\'; } else if (this.value == \'localhost\') { hostname.value = \'localhost\'; } '
       . (empty($thishost) ? '' : 'else if (this.value == \'thishost\') { hostname.value = \'' . addslashes(htmlspecialchars($thishost)) . '\'; } ')
       . 'else if (this.value == \'hosttable\') { hostname.value = \'\'; } else if (this.value == \'userdefined\') { hostname.focus(); hostname.select(); }">' . "\n";
    unset($_current_user);

    // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
    if (! isset($GLOBALS['pred_hostname']) && isset($GLOBALS['hostname'])) {
        switch (strtolower($GLOBALS['hostname'])) {
        case 'localhost':
        case '127.0.0.1':
            $GLOBALS['pred_hostname'] = 'localhost';
            break;
        case '%':
            $GLOBALS['pred_hostname'] = 'any';
            break;
        default:
            $GLOBALS['pred_hostname'] = 'userdefined';
            break;
        }
    }
    echo '        <option value="any"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'any')
            ? ' selected="selected"' : '') . '>' . __('Any host')
        . '</option>' . "\n"
       . '        <option value="localhost"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'localhost')
            ? ' selected="selected"' : '') . '>' . __('Local')
        . '</option>' . "\n";
    if (! empty($thishost)) {
        echo '        <option value="thishost"'
            . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'thishost')
                ? ' selected="selected"' : '') . '>' . __('This Host')
            . '</option>' . "\n";
    }
    unset($thishost);
    echo '        <option value="hosttable"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'hosttable')
            ? ' selected="selected"' : '') . '>' . __('Use Host Table')
        . '</option>' . "\n"
       . '        <option value="userdefined"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'userdefined')
            ? ' selected="selected"' : '')
        . '>' . __('Use text field') . ':</option>' . "\n"
       . '    </select>' . "\n"
       . '</span>' . "\n"
       . '<input type="text" name="hostname" maxlength="'
        . $hostname_length . '" value="'
        . htmlspecialchars(isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '')
        . '" title="' . __('Host')
        . '" onchange="pred_hostname.value = \'userdefined\';" />' . "\n"
       . PMA_showHint(__('When Host table is used, this field is ignored and values stored in Host table are used instead.'))
       . '</div>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="select_pred_password">' . "\n"
       . '    ' . __('Password') . ':' . "\n"
       . '</label>' . "\n"
       . '<span class="options">' . "\n"
       . '    <select name="pred_password" id="select_pred_password" title="'
        . __('Password') . '"' . "\n"
       . '            onchange="if (this.value == \'none\') { pma_pw.value = \'\'; pma_pw2.value = \'\'; } else if (this.value == \'userdefined\') { pma_pw.focus(); pma_pw.select(); }">' . "\n"
       . ($mode == 'change' ? '            <option value="keep" selected="selected">' . __('Do not change the password') . '</option>' . "\n" : '')
       . '        <option value="none"';
    if (isset($GLOBALS['username']) && $mode != 'change') {
        echo '  selected="selected"';
    }
    echo '>' . __('No Password') . '</option>' . "\n"
       . '        <option value="userdefined"' . (isset($GLOBALS['username']) ? '' : ' selected="selected"') . '>' . __('Use text field') . ':</option>' . "\n"
       . '    </select>' . "\n"
       . '</span>' . "\n"
       . '<input type="password" id="text_pma_pw" name="pma_pw" title="' . __('Password') . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
       . '</div>' . "\n"
       . '<div class="item" id="div_element_before_generate_password">' . "\n"
       . '<label for="text_pma_pw2">' . "\n"
       . '    ' . __('Re-type') . ':' . "\n"
       . '</label>' . "\n"
       . '<span class="options">&nbsp;</span>' . "\n"
       . '<input type="password" name="pma_pw2" id="text_pma_pw2" title="' . __('Re-type') . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
       . '</div>' . "\n"
       // Generate password added here via jQuery
       . '</fieldset>' . "\n";
} // end of the 'PMA_displayUserAndHostFields()' function

/**
 * Changes / copies a user, part I
 */
if (isset($_REQUEST['change_copy'])) {
    $user_host_condition = ' WHERE `User`'
        .' = \'' . PMA_sqlAddSlashes($old_username) . "'"
        .' AND `Host`'
        .' = \'' . PMA_sqlAddSlashes($old_hostname) . '\';';
    $row = PMA_DBI_fetch_single_row('SELECT * FROM `mysql`.`user` ' . $user_host_condition);
    if (! $row) {
        PMA_Message::notice(__('No user found.'))->display();
        unset($_REQUEST['change_copy']);
    } else {
        extract($row, EXTR_OVERWRITE);
        // Recent MySQL versions have the field "Password" in mysql.user,
        // so the previous extract creates $Password but this script
        // uses $password
        if (! isset($password) && isset($Password)) {
            $password = $Password;
        }
        $queries = array();
    }
}


/**
 * Adds a user
 *   (Changes / copies a user, part II)
 */
if (isset($_REQUEST['adduser_submit']) || isset($_REQUEST['change_copy'])) {
    $sql_query = '';
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
        $_user_name = PMA_DBI_fetch_value('SELECT USER()');
        $hostname = substr($_user_name, (strrpos($_user_name, '@') + 1));
        unset($_user_name);
        break;
    }
    $sql = "SELECT '1' FROM `mysql`.`user`"
        . " WHERE `User` = '" . PMA_sqlAddSlashes($username) . "'"
        . " AND `Host` = '" . PMA_sqlAddSlashes($hostname) . "';";
    if (PMA_DBI_fetch_value($sql) == 1) {
        $message = PMA_Message::error(__('The user %s already exists!'));
        $message->addParam('[i]\'' . $username . '\'@\'' . $hostname . '\'[/i]');
        $_REQUEST['adduser'] = true;
        $_add_user_error = true;
    } else {

        $create_user_real = 'CREATE USER \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\'';

        $real_sql_query = 'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON *.* TO \''
            . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\'';
        if ($pred_password != 'none' && $pred_password != 'keep') {
            $sql_query = $real_sql_query . ' IDENTIFIED BY \'***\'';
            $real_sql_query .= ' IDENTIFIED BY \'' . PMA_sqlAddSlashes($pma_pw) . '\'';
            if (isset($create_user_real)) {
                $create_user_show = $create_user_real . ' IDENTIFIED BY \'***\'';
                $create_user_real .= ' IDENTIFIED BY \'' . PMA_sqlAddSlashes($pma_pw) . '\'';
            }
        } else {
            if ($pred_password == 'keep' && ! empty($password)) {
                $real_sql_query .= ' IDENTIFIED BY PASSWORD \'' . $password . '\'';
                if (isset($create_user_real)) {
                    $create_user_real .= ' IDENTIFIED BY PASSWORD \'' . $password . '\'';
                }
            }
            $sql_query = $real_sql_query;
            if (isset($create_user_real)) {
                $create_user_show = $create_user_real;
            }
        }
        /**
         * @todo similar code appears twice in this script
         */
        if ((isset($Grant_priv) && $Grant_priv == 'Y')
            || (isset($max_questions) || isset($max_connections)
            || isset($max_updates) || isset($max_user_connections))
        ) {
            $real_sql_query .= ' WITH';
            $sql_query .= ' WITH';
            if (isset($Grant_priv) && $Grant_priv == 'Y') {
                $real_sql_query .= ' GRANT OPTION';
                $sql_query .= ' GRANT OPTION';
            }
            if (isset($max_questions)) {
                // avoid negative values
                $max_questions = max(0, (int)$max_questions);
                $real_sql_query .= ' MAX_QUERIES_PER_HOUR ' . $max_questions;
                $sql_query .= ' MAX_QUERIES_PER_HOUR ' . $max_questions;
            }
            if (isset($max_connections)) {
                $max_connections = max(0, (int)$max_connections);
                $real_sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . $max_connections;
                $sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . $max_connections;
            }
            if (isset($max_updates)) {
                $max_updates = max(0, (int)$max_updates);
                $real_sql_query .= ' MAX_UPDATES_PER_HOUR ' . $max_updates;
                $sql_query .= ' MAX_UPDATES_PER_HOUR ' . $max_updates;
            }
            if (isset($max_user_connections)) {
                $max_user_connections = max(0, (int)$max_user_connections);
                $real_sql_query .= ' MAX_USER_CONNECTIONS ' . $max_user_connections;
                $sql_query .= ' MAX_USER_CONNECTIONS ' . $max_user_connections;
            }
        }
        if (isset($create_user_real)) {
            $create_user_real .= ';';
            $create_user_show .= ';';
        }
        $real_sql_query .= ';';
        $sql_query .= ';';
        if (empty($_REQUEST['change_copy'])) {
            $_error = false;

            if (isset($create_user_real)) {
                if (! PMA_DBI_try_query($create_user_real)) {
                    $_error = true;
                }
                $sql_query = $create_user_show . $sql_query;
            }

            if ($_error || ! PMA_DBI_try_query($real_sql_query)) {
                $_REQUEST['createdb'] = false;
                $message = PMA_Message::rawError(PMA_DBI_getError());
            } else {
                $message = PMA_Message::success(__('You have added a new user.'));
            }

            switch (PMA_ifSetOr($_REQUEST['createdb'], '0')) {
            case '1' :
                // Create database with same name and grant all privileges
                $q = 'CREATE DATABASE IF NOT EXISTS '
                    . PMA_backquote(PMA_sqlAddSlashes($username)) . ';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                    break;
                }


                /**
                 * If we are not in an Ajax request, we can't reload navigation now
                 */
                if ($GLOBALS['is_ajax_request'] != true) {
                    // this is needed in case tracking is on:
                    $GLOBALS['db'] = $username;
                    $GLOBALS['reload'] = true;
                    PMA_reloadNavigation();
                }

                $q = 'GRANT ALL PRIVILEGES ON '
                    . PMA_backquote(PMA_escape_mysql_wildcards(PMA_sqlAddSlashes($username))) . '.* TO \''
                    . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
                break;
            case '2' :
                // Grant all privileges on wildcard name (username\_%)
                $q = 'GRANT ALL PRIVILEGES ON '
                    . PMA_backquote(PMA_sqlAddSlashes($username) . '\_%') . '.* TO \''
                    . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
                break;
            case '3' :
                // Grant all privileges on the specified database to the new user
                $q = 'GRANT ALL PRIVILEGES ON '
                . PMA_backquote(PMA_sqlAddSlashes($dbname)) . '.* TO \''
                . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
                break;
            case '0' :
            default :
                break;
            }
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
        unset($res, $real_sql_query);
    }
}


/**
 * Changes / copies a user, part III
 */
if (isset($_REQUEST['change_copy'])) {
    $user_host_condition = ' WHERE `User`'
        .' = \'' . PMA_sqlAddSlashes($old_username) . "'"
        .' AND `Host`'
        .' = \'' . PMA_sqlAddSlashes($old_hostname) . '\';';
    $res = PMA_DBI_query('SELECT * FROM `mysql`.`db`' . $user_host_condition);
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $queries[] = 'GRANT ' . join(', ', PMA_extractPrivInfo($row))
            .' ON ' . PMA_backquote($row['Db']) . '.*'
            .' TO \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\''
            . ($row['Grant_priv'] == 'Y' ? ' WITH GRANT OPTION;' : ';');
    }
    PMA_DBI_free_result($res);
    $res = PMA_DBI_query(
        'SELECT `Db`, `Table_name`, `Table_priv` FROM `mysql`.`tables_priv`' . $user_host_condition,
        $GLOBALS['userlink'],
        PMA_DBI_QUERY_STORE
    );
    while ($row = PMA_DBI_fetch_assoc($res)) {

        $res2 = PMA_DBI_QUERY(
            'SELECT `Column_name`, `Column_priv`'
            .' FROM `mysql`.`columns_priv`'
            .' WHERE `User`'
            .' = \'' . PMA_sqlAddSlashes($old_username) . "'"
            .' AND `Host`'
            .' = \'' . PMA_sqlAddSlashes($old_hostname) . '\''
            .' AND `Db`'
            .' = \'' . PMA_sqlAddSlashes($row['Db']) . "'"
            .' AND `Table_name`'
            .' = \'' . PMA_sqlAddSlashes($row['Table_name']) . "'"
            .';',
            null,
            PMA_DBI_QUERY_STORE
        );

        $tmp_privs1 = PMA_extractPrivInfo($row);
        $tmp_privs2 = array(
            'Select' => array(),
            'Insert' => array(),
            'Update' => array(),
            'References' => array()
        );

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
            unset($tmp_array);
        }
        if (count($tmp_privs2['Select']) > 0 && ! in_array('SELECT', $tmp_privs1)) {
            $tmp_privs1[] = 'SELECT (`' . join('`, `', $tmp_privs2['Select']) . '`)';
        }
        if (count($tmp_privs2['Insert']) > 0 && ! in_array('INSERT', $tmp_privs1)) {
            $tmp_privs1[] = 'INSERT (`' . join('`, `', $tmp_privs2['Insert']) . '`)';
        }
        if (count($tmp_privs2['Update']) > 0 && ! in_array('UPDATE', $tmp_privs1)) {
            $tmp_privs1[] = 'UPDATE (`' . join('`, `', $tmp_privs2['Update']) . '`)';
        }
        if (count($tmp_privs2['References']) > 0 && ! in_array('REFERENCES', $tmp_privs1)) {
            $tmp_privs1[] = 'REFERENCES (`' . join('`, `', $tmp_privs2['References']) . '`)';
        }
        unset($tmp_privs2);
        $queries[] = 'GRANT ' . join(', ', $tmp_privs1)
            . ' ON ' . PMA_backquote($row['Db']) . '.' . PMA_backquote($row['Table_name'])
            . ' TO \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\''
            . (in_array('Grant', explode(',', $row['Table_priv'])) ? ' WITH GRANT OPTION;' : ';');
    }
}


/**
 * Updates privileges
 */
if (! empty($update_privs)) {
    $db_and_table = PMA_wildcardEscapeForGrant($dbname, (isset($tablename) ? $tablename : ''));

    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
    if (! isset($Grant_priv) || $Grant_priv != 'Y') {
        $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table
            . ' FROM \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
    } else {
        $sql_query1 = '';
    }

    // Should not do a GRANT USAGE for a table-specific privilege, it
    // causes problems later (cannot revoke it)
    if (! (isset($tablename) && 'USAGE' == implode('', PMA_extractPrivInfo()))) {
        $sql_query2 = 'GRANT ' . join(', ', PMA_extractPrivInfo())
            . ' ON ' . $db_and_table
            . ' TO \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\'';

        /**
         * @todo similar code appears twice in this script
         */
        if ((isset($Grant_priv) && $Grant_priv == 'Y')
            || (! isset($dbname)
            && (isset($max_questions) || isset($max_connections)
            || isset($max_updates) || isset($max_user_connections)))
        ) {
            $sql_query2 .= 'WITH';
            if (isset($Grant_priv) && $Grant_priv == 'Y') {
                $sql_query2 .= ' GRANT OPTION';
            }
            if (isset($max_questions)) {
                $max_questions = max(0, (int)$max_questions);
                $sql_query2 .= ' MAX_QUERIES_PER_HOUR ' . $max_questions;
            }
            if (isset($max_connections)) {
                $max_connections = max(0, (int)$max_connections);
                $sql_query2 .= ' MAX_CONNECTIONS_PER_HOUR ' . $max_connections;
            }
            if (isset($max_updates)) {
                $max_updates = max(0, (int)$max_updates);
                $sql_query2 .= ' MAX_UPDATES_PER_HOUR ' . $max_updates;
            }
            if (isset($max_user_connections)) {
                $max_user_connections = max(0, (int)$max_user_connections);
                $sql_query2 .= ' MAX_USER_CONNECTIONS ' . $max_user_connections;
            }
        }
        $sql_query2 .= ';';
    }
    if (! PMA_DBI_try_query($sql_query0)) {
        // This might fail when the executing user does not have ALL PRIVILEGES himself.
        // See https://sourceforge.net/tracker/index.php?func=detail&aid=3285929&group_id=23067&atid=377408
        $sql_query0 = '';
    }
    if (isset($sql_query1) && ! PMA_DBI_try_query($sql_query1)) {
        // this one may fail, too...
        $sql_query1 = '';
    }
    if (isset($sql_query2)) {
        PMA_DBI_query($sql_query2);
    } else {
        $sql_query2 = '';
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1 . ' ' . $sql_query2;
    $message = PMA_Message::success(__('You have updated the privileges for %s.'));
    $message->addParam('\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'');
}


/**
 * Revokes Privileges
 */
if (isset($_REQUEST['revokeall'])) {
    $db_and_table = PMA_wildcardEscapeForGrant($dbname, isset($tablename) ? $tablename : '');

    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
    $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table
        . ' FROM \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';

    PMA_DBI_query($sql_query0);
    if (! PMA_DBI_try_query($sql_query1)) {
        // this one may fail, too...
        $sql_query1 = '';
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1;
    $message = PMA_Message::success(__('You have revoked the privileges for %s'));
    $message->addParam('\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'');
    if (! isset($tablename)) {
        unset($dbname);
    } else {
        unset($tablename);
    }
}


/**
 * Updates the password
 */
if (isset($_REQUEST['change_pw'])) {
    // similar logic in user_password.php
    $message = '';

    if ($nopass == 0 && isset($pma_pw) && isset($pma_pw2)) {
        if ($pma_pw != $pma_pw2) {
            $message = PMA_Message::error(__('The passwords aren\'t the same!'));
        } elseif (empty($pma_pw) || empty($pma_pw2)) {
            $message = PMA_Message::error(__('The password is empty!'));
        }
    } // end if

    // here $nopass could be == 1
    if (empty($message)) {

        $hashing_function = (! empty($pw_hash) && $pw_hash == 'old' ? 'OLD_' : '')
                      . 'PASSWORD';

        // in $sql_query which will be displayed, hide the password
        $sql_query        = 'SET PASSWORD FOR \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\' = ' . (($pma_pw == '') ? '\'\'' : $hashing_function . '(\'' . preg_replace('@.@s', '*', $pma_pw) . '\')');
        $local_query      = 'SET PASSWORD FOR \'' . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\' = ' . (($pma_pw == '') ? '\'\'' : $hashing_function . '(\'' . PMA_sqlAddSlashes($pma_pw) . '\')');
        PMA_DBI_try_query($local_query)
            or PMA_mysqlDie(PMA_DBI_getError(), $sql_query, false, $err_url);
        $message = PMA_Message::success(__('The password for %s was changed successfully.'));
        $message->addParam('\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'');
    }
}


/**
 * Deletes users
 *   (Changes / copies a user, part IV)
 */

if (isset($_REQUEST['delete']) || (isset($_REQUEST['change_copy']) && $_REQUEST['mode'] < 4)) {
    if (isset($_REQUEST['change_copy'])) {
        $selected_usr = array($old_username . '&amp;#27;' . $old_hostname);
    } else {
        $selected_usr = $_REQUEST['selected_usr'];
        $queries = array();
    }
    foreach ($selected_usr as $each_user) {
        list($this_user, $this_host) = explode('&amp;#27;', $each_user);
        $queries[] = '# ' . sprintf(__('Deleting %s'), '\'' . $this_user . '\'@\'' . $this_host . '\'') . ' ...';
        $queries[] = 'DROP USER \'' . PMA_sqlAddSlashes($this_user) . '\'@\'' . PMA_sqlAddSlashes($this_host) . '\';';

        if (isset($_REQUEST['drop_users_db'])) {
            $queries[] = 'DROP DATABASE IF EXISTS ' . PMA_backquote($this_user) . ';';
            $GLOBALS['reload'] = true;

            if ($GLOBALS['is_ajax_request'] != true) {
                PMA_reloadNavigation();
            }
        }
    }
    if (empty($_REQUEST['change_copy'])) {
        if (empty($queries)) {
            $message = PMA_Message::error(__('No users selected for deleting!'));
        } else {
            if ($_REQUEST['mode'] == 3) {
                $queries[] = '# ' . __('Reloading the privileges') . ' ...';
                $queries[] = 'FLUSH PRIVILEGES;';
            }
            $drop_user_error = '';
            foreach ($queries as $sql_query) {
                if ($sql_query{0} != '#') {
                    if (! PMA_DBI_try_query($sql_query, $GLOBALS['userlink'])) {
                        $drop_user_error .= PMA_DBI_getError() . "\n";
                    }
                }
            }
            // tracking sets this, causing the deleted db to be shown in navi
            unset($GLOBALS['db']);

            $sql_query = join("\n", $queries);
            if (! empty($drop_user_error)) {
                $message = PMA_Message::rawError($drop_user_error);
            } else {
                $message = PMA_Message::success(__('The selected users have been deleted successfully.'));
            }
        }
        unset($queries);
    }
}


/**
 * Changes / copies a user, part V
 */
if (isset($_REQUEST['change_copy'])) {
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
    $message = PMA_Message::success();
    $sql_query = join("\n", $queries);
}


/**
 * Reloads the privilege tables into memory
 */
if (isset($_REQUEST['flush_privileges'])) {
    $sql_query = 'FLUSH PRIVILEGES;';
    PMA_DBI_query($sql_query);
    $message = PMA_Message::success(__('The privileges were reloaded successfully.'));
}

/**
 * defines some standard links
 */
$link_edit = '<a class="edit_user_anchor ' . $conditional_class . '" href="server_privileges.php?' . str_replace('%', '%%', $GLOBALS['url_query'])
    . '&amp;username=%s'
    . '&amp;hostname=%s'
    . '&amp;dbname=%s'
    . '&amp;tablename=%s">'
    . PMA_getIcon('b_usredit.png', __('Edit Privileges'))
    . '</a>';

$link_revoke = '<a href="server_privileges.php?' . str_replace('%', '%%', $GLOBALS['url_query'])
    . '&amp;username=%s'
    . '&amp;hostname=%s'
    . '&amp;dbname=%s'
    . '&amp;tablename=%s'
    . '&amp;revokeall=1">'
    . PMA_getIcon('b_usrdrop.png', __('Revoke'))
    . '</a>';

$link_export = '<a class="export_user_anchor ' . $conditional_class . '" href="server_privileges.php?' . str_replace('%', '%%', $GLOBALS['url_query'])
    . '&amp;username=%s'
    . '&amp;hostname=%s'
    . '&amp;initial=%s'
    . '&amp;export=1">'
    . PMA_getIcon('b_tblexport.png', __('Export'))
    . '</a>';

/**
 * If we are in an Ajax request for Create User/Edit User/Revoke User/
 * Flush Privileges, show $message and exit.
 */
if ($GLOBALS['is_ajax_request'] && ! isset($_REQUEST['export']) && (! isset($_REQUEST['adduser']) || $_add_user_error) && ! isset($_REQUEST['initial']) && ! isset($_REQUEST['showall']) && ! isset($_REQUEST['edit_user_dialog']) && ! isset($_REQUEST['db_specific'])) {

    if (isset($sql_query)) {
        $extra_data['sql_query'] = PMA_showMessage(null, $sql_query);
    }

    if (isset($_REQUEST['adduser_submit']) || isset($_REQUEST['change_copy'])) {
        /**
         * generate html on the fly for the new user that was just created.
         */
        $new_user_string = '<tr>'."\n"
                           .'<td> <input type="checkbox" name="selected_usr[]" id="checkbox_sel_users_" value="' . htmlspecialchars($username) . '&amp;#27;' . htmlspecialchars($hostname) . '" /> </td>' . "\n"
                           .'<td><label for="checkbox_sel_users_">' . (empty($username) ? '<span style="color: #FF0000">' . __('Any') . '</span>' : htmlspecialchars($username) ) . '</label></td>' . "\n"
                           .'<td>' . htmlspecialchars($hostname) . '</td>' . "\n";
        $new_user_string .= '<td>';

        if (! empty($password) || isset($pma_pw)) {
            $new_user_string .= __('Yes');
        } else {
            $new_user_string .= '<span style="color: #FF0000">' . __('No') . '</span>';
        };

        $new_user_string .= '</td>'."\n";
        $new_user_string .= '<td><tt>' . join(', ', PMA_extractPrivInfo('', true)) . '</tt></td>'; //Fill in privileges here
        $new_user_string .= '<td>';

        if ((isset($Grant_priv) && $Grant_priv == 'Y')) {
            $new_user_string .= __('Yes');
        } else {
            $new_user_string .= __('No');
        }

        $new_user_string .='</td>';

        $new_user_string .= '<td>' . sprintf($link_edit, urlencode($username), urlencode($hostname), '', '') . '</td>' . "\n";
        $new_user_string .= '<td>' . sprintf($link_export, urlencode($username), urlencode($hostname), (isset($initial) ? $initial : '')) . '</td>' . "\n";

        $new_user_string .= '</tr>';

        $extra_data['new_user_string'] = $new_user_string;

        /**
         * Generate the string for this alphabet's initial, to update the user
         * pagination
         */
        $new_user_initial = strtoupper(substr($username, 0, 1));
        $new_user_initial_string = '<a href="server_privileges.php?' . $GLOBALS['url_query'] . '&initial=' . $new_user_initial
            .'">' . $new_user_initial . '</a>';
        $extra_data['new_user_initial'] = $new_user_initial;
        $extra_data['new_user_initial_string'] = $new_user_initial_string;
    }

    if (isset($update_privs)) {
        $extra_data['db_specific_privs'] = false;
        if (isset($dbname_is_wildcard)) {
            $extra_data['db_specific_privs'] = ! $dbname_is_wildcard;
        }
        $new_privileges = join(', ', PMA_extractPrivInfo('', true));

        $extra_data['new_privileges'] = $new_privileges;
    }

    if ($message instanceof PMA_Message) {
        PMA_ajaxResponse($message, $message->isSuccess(), $extra_data);
    }
}

/**
 * Displays the links
 */
if (isset($viewing_mode) && $viewing_mode == 'db') {
    $db = $checkprivs;
    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    include './libraries/db_info.inc.php';
    echo "\n";
} else {
    include './libraries/server_links.inc.php';
}


/**
 * Displays the page
 */

// export user definition
if (isset($_REQUEST['export'])) {
    $title = __('User') . ' `' . htmlspecialchars($username) . '`@`' . htmlspecialchars($hostname) . '`';
    $response = '<textarea cols="' . $GLOBALS['cfg']['TextareaCols'] . '" rows="' . $GLOBALS['cfg']['TextareaRows'] . '">';
    $grants = PMA_DBI_fetch_result("SHOW GRANTS FOR '" . PMA_sqlAddSlashes($username) . "'@'" . PMA_sqlAddSlashes($hostname) . "'");
    foreach ($grants as $one_grant) {
        $response .= $one_grant . ";\n\n";
    }
    $response .= '</textarea>';
    unset($username, $hostname, $grants, $one_grant);
    if ($GLOBALS['is_ajax_request']) {
        PMA_ajaxResponse($response, 1, array('title' => $title));
    } else {
        echo "<h2>$title</h2>$response";
    }
}

if (empty($_REQUEST['adduser']) && (! isset($checkprivs) || ! strlen($checkprivs))) {
    if (! isset($username)) {
        // No username is given --> display the overview
        echo '<h2>' . "\n"
           . PMA_getIcon('b_usrlist.png')
           . __('Users overview') . "\n"
           . '</h2>' . "\n";

        $sql_query = 'SELECT *,' .
            "       IF(`Password` = _latin1 '', 'N', 'Y') AS 'Password'" .
            '  FROM `mysql`.`user`';

        $sql_query .= (isset($initial) ? PMA_rangeOfUsers($initial) : '');

        $sql_query .= ' ORDER BY `User` ASC, `Host` ASC;';
        $res = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_STORE);

        if (! $res) {
            // the query failed! This may have two reasons:
            // - the user does not have enough privileges
            // - the privilege tables use a structure of an earlier version.
            // so let's try a more simple query

            $sql_query = 'SELECT * FROM `mysql`.`user`';
            $res = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_STORE);

            if (! $res) {
                PMA_Message::error(__('No Privileges'))->display();
                PMA_DBI_free_result($res);
                unset($res);
            } else {
                // This message is hardcoded because I will replace it by
                // a automatic repair feature soon.
                $raw = 'Your privilege table structure seems to be older than'
                    . ' this MySQL version!<br />'
                    . 'Please run the <tt>mysql_upgrade</tt> command'
                    . '(<tt>mysql_fix_privilege_tables</tt> on older systems)'
                    . ' that should be included in your MySQL server distribution'
                    . ' to solve this problem!';
                PMA_Message::rawError($raw)->display();
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
                    $db_rights_sqls[] = 'SELECT DISTINCT `User`, `Host` FROM `mysql`.`' . $table_search_in . '` ' . (isset($initial) ? PMA_rangeOfUsers($initial) : '');
                }
            }

            $user_defaults = array(
                'User'      => '',
                'Host'      => '%',
                'Password'  => '?',
                'Grant_priv' => 'N',
                'privs'     => array('USAGE'),
            );

            // for all initials, even non A-Z
            $array_initials = array();
            // for the rights
            $db_rights = array();

            $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
                .' ORDER BY `User` ASC, `Host` ASC';

            $db_rights_result = PMA_DBI_query($db_rights_sql);

            while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                $db_rights_row = array_merge($user_defaults, $db_rights_row);
                $db_rights[$db_rights_row['User']][$db_rights_row['Host']]
                    = $db_rights_row;
            }
            PMA_DBI_free_result($db_rights_result);
            unset($db_rights_sql, $db_rights_sqls, $db_rights_result, $db_rights_row);
            ksort($db_rights);

            /**
             * Displays the initials
             * In an Ajax request, we don't need to show this.
             * Also not necassary if there is less than 20 privileges
             */
            if ($GLOBALS['is_ajax_request'] != true && PMA_DBI_num_rows($res) > 20 ) {

                // initialize to false the letters A-Z
                for ($letter_counter = 1; $letter_counter < 27; $letter_counter++) {
                    if (! isset($array_initials[chr($letter_counter + 64)])) {
                        $array_initials[chr($letter_counter + 64)] = false;
                    }
                }

                $initials = PMA_DBI_try_query('SELECT DISTINCT UPPER(LEFT(`User`,1)) FROM `user` ORDER BY `User` ASC', null, PMA_DBI_QUERY_STORE);
                while (list($tmp_initial) = PMA_DBI_fetch_row($initials)) {
                    $array_initials[$tmp_initial] = true;
                }

                // Display the initials, which can be any characters, not
                // just letters. For letters A-Z, we add the non-used letters
                // as greyed out.

                uksort($array_initials, "strnatcasecmp");

                echo '<table id="initials_table" class="' . $conditional_class . '" <cellspacing="5"><tr>';
                foreach ($array_initials as $tmp_initial => $initial_was_found) {
                    if ($initial_was_found) {
                        echo '<td><a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;initial=' . urlencode($tmp_initial) . '">' . $tmp_initial . '</a></td>' . "\n";
                    } else {
                        echo '<td>' . $tmp_initial . '</td>';
                    }
                }
                echo '<td><a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;showall=1" class="nowrap">[' . __('Show all') . ']</a></td>' . "\n";
                echo '</tr></table>';
            }

            /**
            * Display the user overview
            * (if less than 50 users, display them immediately)
            */

            if (isset($initial) || isset($showall) || PMA_DBI_num_rows($res) < 50) {

                while ($row = PMA_DBI_fetch_assoc($res)) {
                    $row['privs'] = PMA_extractPrivInfo($row, true);
                    $db_rights[$row['User']][$row['Host']] = $row;
                }
                @PMA_DBI_free_result($res);
                unset($res);

                echo '<form name="usersForm" id="usersForm" action="server_privileges.php" method="post">' . "\n"
                   . PMA_generate_common_hidden_inputs('', '')
                   . '    <table id="tableuserrights" class="data">' . "\n"
                   . '    <thead>' . "\n"
                   . '        <tr><th></th>' . "\n"
                   . '            <th>' . __('User') . '</th>' . "\n"
                   . '            <th>' . __('Host') . '</th>' . "\n"
                   . '            <th>' . __('Password') . '</th>' . "\n"
                   . '            <th>' . __('Global privileges') . ' '
                   . PMA_showHint(__('Note: MySQL privilege names are expressed in English')) . '</th>' . "\n"
                   . '            <th>' . __('Grant') . '</th>' . "\n"
                   . '            <th colspan="2">' . __('Action') . '</th>' . "\n";
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
                           . '            <td><input type="checkbox" name="selected_usr[]" id="checkbox_sel_users_'
                            . $index_checkbox . '" value="'
                            . htmlspecialchars($host['User'] . '&amp;#27;' . $host['Host'])
                            . '"'
                            . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"')
                            . ' /></td>' . "\n"
                           . '            <td><label for="checkbox_sel_users_' . $index_checkbox . '">' . (empty($host['User']) ? '<span style="color: #FF0000">' . __('Any') . '</span>' : htmlspecialchars($host['User'])) . '</label></td>' . "\n"
                           . '            <td>' . htmlspecialchars($host['Host']) . '</td>' . "\n";
                        echo '            <td>';
                        switch ($host['Password']) {
                        case 'Y':
                            echo __('Yes');
                            break;
                        case 'N':
                            echo '<span style="color: #FF0000">' . __('No') . '</span>';
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
                           . '            <td>' . ($host['Grant_priv'] == 'Y' ? __('Yes') : __('No')) . '</td>' . "\n"
                           . '            <td align="center">';
                        printf($link_edit, urlencode($host['User']), urlencode($host['Host']), '', '');
                        echo '</td>';
                        echo '<td align="center">';
                        printf($link_export, urlencode($host['User']), urlencode($host['Host']), (isset($initial) ? $initial : ''));
                        echo '</td>';
                        echo '</tr>';
                        $odd_row = ! $odd_row;
                    }
                }

                unset($user, $host, $odd_row);
                echo '    </tbody></table>' . "\n"
                   .'<img class="selectallarrow"'
                   .' src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png"'
                   .' width="38" height="22"'
                   .' alt="' . __('With selected:') . '" />' . "\n"
                   .'<a href="server_privileges.php?' . $GLOBALS['url_query'] .  '&amp;checkall=1"'
                   .' onclick="if (markAllRows(\'usersForm\')) return false;">'
                   . __('Check All') . '</a>' . "\n"
                   .'/' . "\n"
                   .'<a href="server_privileges.php?' . $GLOBALS['url_query'] .  '"'
                   .' onclick="if (unMarkAllRows(\'usersForm\')) return false;">'
                   . __('Uncheck All') . '</a>' . "\n";

                // add/delete user fieldset
                echo '    <fieldset id="fieldset_add_user">' . "\n"
                   . '        <a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;adduser=1" class="' . $conditional_class . '">' . "\n"
                   . PMA_getIcon('b_usradd.png')
                   . '            ' . __('Add user') . '</a>' . "\n"
                   . '    </fieldset>' . "\n"
                   . '    <fieldset id="fieldset_delete_user">'
                   . '        <legend>' . "\n"
                   . PMA_getIcon('b_usrdrop.png')
                   . '            ' . __('Remove selected users') . '' . "\n"
                   . '        </legend>' . "\n"
                   . '        <input type="hidden" name="mode" value="2" />' . "\n"
                   . '(' . __('Revoke all active privileges from the users and delete them afterwards.') . ')<br />' . "\n"
                   . '        <input type="checkbox" title="' . __('Drop the databases that have the same names as the users.') . '" name="drop_users_db" id="checkbox_drop_users_db" />' . "\n"
                   . '        <label for="checkbox_drop_users_db" title="' . __('Drop the databases that have the same names as the users.') . '">' . "\n"
                   . '            ' . __('Drop the databases that have the same names as the users.') . "\n"
                   . '        </label>' . "\n"
                   . '    </fieldset>' . "\n"
                   . '    <fieldset id="fieldset_delete_user_footer" class="tblFooters">' . "\n"
                   . '        <input type="submit" name="delete" value="' . __('Go') . '" id="buttonGo" class="' . $conditional_class . '"/>' . "\n"
                   . '    </fieldset>' . "\n"
                   . '</form>' . "\n";
            } else {

                unset ($row);
                echo '    <fieldset id="fieldset_add_user">' . "\n"
                   . '        <a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;adduser=1" class="' . $conditional_class . '">' . "\n"
                   . PMA_getIcon('b_usradd.png')
                   . '            ' . __('Add user') . '</a>' . "\n"
                   . '    </fieldset>' . "\n";
            } // end if (display overview)

            if ($GLOBALS['is_ajax_request']) {
                exit;
            }

            $flushnote = new PMA_Message(__('Note: phpMyAdmin gets the users\' privileges directly from MySQL\'s privilege tables. The content of these tables may differ from the privileges the server uses, if they have been changed manually. In this case, you should %sreload the privileges%s before you continue.'), PMA_Message::NOTICE);
            $flushnote->addParam('<a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;flush_privileges=1" id="reload_privileges_anchor" class="' . $conditional_class . '">', false);
            $flushnote->addParam('</a>', false);
            $flushnote->display();
        }


    } else {

        // A user was selected -> display the user's properties

        // In an Ajax request, prevent cached values from showing
        if ($GLOBALS['is_ajax_request'] == true) {
            header('Cache-Control: no-cache');
        }

        echo '<h2>' . "\n"
           . PMA_getIcon('b_usredit.png')
           . __('Edit Privileges') . ': '
           . __('User');

        if (isset($dbname)) {
            echo ' <i><a href="server_privileges.php?'
                . $GLOBALS['url_query'] . '&amp;username=' . htmlspecialchars(urlencode($username))
                . '&amp;hostname=' . htmlspecialchars(urlencode($hostname)) . '&amp;dbname=&amp;tablename=">\''
                . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname)
                . '\'</a></i>' . "\n";
            $url_dbname = urlencode(str_replace(array('\_', '\%'), array('_', '%'), $dbname));

            echo ' - ' . ($dbname_is_wildcard ? __('Databases') : __('Database') );
            if (isset($tablename)) {
                echo ' <i><a href="server_privileges.php?' . $GLOBALS['url_query']
                    . '&amp;username=' . htmlspecialchars(urlencode($username)) . '&amp;hostname=' . htmlspecialchars(urlencode($hostname))
                    . '&amp;dbname=' . htmlspecialchars($url_dbname) . '&amp;tablename=">' . htmlspecialchars($dbname) . '</a></i>';
                echo ' - ' . __('Table') . ' <i>' . htmlspecialchars($tablename) . '</i>';
            } else {
                echo ' <i>' . htmlspecialchars($dbname) . '</i>';
            }

        } else {
            echo ' <i>\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname)
                . '\'</i>' . "\n";

        }
        echo '</h2>' . "\n";


        $sql = "SELECT '1' FROM `mysql`.`user`"
            . " WHERE `User` = '" . PMA_sqlAddSlashes($username) . "'"
            . " AND `Host` = '" . PMA_sqlAddSlashes($hostname) . "';";
        $user_does_not_exists = (bool) ! PMA_DBI_fetch_value($sql);
        unset($sql);
        if ($user_does_not_exists) {
            PMA_Message::error(__('The selected user was not found in the privilege table.'))->display();
            PMA_displayLoginInformationFields();
            //require './libraries/footer.inc.php';
        }

        echo '<form name="usersForm" id="addUsersForm_' . $random_n . '" action="server_privileges.php" method="post">' . "\n";
        $_params = array(
            'username' => $username,
            'hostname' => $hostname,
        );
        if (isset($dbname)) {
            $_params['dbname'] = $dbname;
            if (isset($tablename)) {
                $_params['tablename'] = $tablename;
            }
        }
        echo PMA_generate_common_hidden_inputs($_params);

        PMA_displayPrivTable(
            PMA_ifSetOr($dbname, '*', 'length'),
            PMA_ifSetOr($tablename, '*', 'length')
        );

        echo '</form>' . "\n";

        if (! isset($tablename) && empty($dbname_is_wildcard)) {

            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards

            // table header
            echo '<form action="server_privileges.php" id="db_or_table_specific_priv" method="post">' . "\n"
               . PMA_generate_common_hidden_inputs('', '')
               . '<input type="hidden" name="username" value="' . htmlspecialchars($username) . '" />' . "\n"
               . '<input type="hidden" name="hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n"
               . '<fieldset>' . "\n"
               . '<legend>' . (! isset($dbname) ? __('Database-specific privileges') : __('Table-specific privileges')) . '</legend>' . "\n"
               . '<table class="data">' . "\n"
               . '<thead>' . "\n"
               . '<tr><th>' . (! isset($dbname) ? __('Database') : __('Table')) . '</th>' . "\n"
               . '    <th>' . __('Privileges') . '</th>' . "\n"
               . '    <th>' . __('Grant') . '</th>' . "\n"
               . '    <th>' . (! isset($dbname) ? __('Table-specific privileges') : __('Column-specific privileges')) . '</th>' . "\n"
               . '    <th colspan="2">' . __('Action') . '</th>' . "\n"
               . '</tr>' . "\n"
               . '</thead>' . "\n"
               . '<tbody>' . "\n";

            $user_host_condition = ' WHERE `User`'
                . ' = \'' . PMA_sqlAddSlashes($username) . "'"
                . ' AND `Host`'
                . ' = \'' . PMA_sqlAddSlashes($hostname) . "'";

            // table body
            // get data

            // we also want privielgs for this user not in table `db` but in other table
            $tables = PMA_DBI_fetch_result('SHOW TABLES FROM `mysql`;');
            if (! isset($dbname)) {

                // no db name given, so we want all privs for the given user

                $tables_to_search_for_users = array(
                    'tables_priv', 'columns_priv',
                );

                $db_rights_sqls = array();
                foreach ($tables_to_search_for_users as $table_search_in) {
                    if (in_array($table_search_in, $tables)) {
                        $db_rights_sqls[] = '
                            SELECT DISTINCT `Db`
                                   FROM `mysql`.' . PMA_backquote($table_search_in)
                                   . $user_host_condition;
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

                $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
                    .' ORDER BY `Db` ASC';

                $db_rights_result = PMA_DBI_query($db_rights_sql);

                while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                    $db_rights_row = array_merge($user_defaults, $db_rights_row);
                    // only Db names in the table `mysql`.`db` uses wildcards
                    // as we are in the db specific rights display we want
                    // all db names escaped, also from other sources
                    $db_rights_row['Db'] = PMA_escape_mysql_wildcards(
                        $db_rights_row['Db']
                    );
                    $db_rights[$db_rights_row['Db']] = $db_rights_row;
                }

                PMA_DBI_free_result($db_rights_result);
                unset($db_rights_sql, $db_rights_sqls, $db_rights_result, $db_rights_row);

                $sql_query = 'SELECT * FROM `mysql`.`db`' . $user_host_condition . ' ORDER BY `Db` ASC';
                $res = PMA_DBI_query($sql_query);
                $sql_query = '';

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
                    ' AND `Db`'
                    .' LIKE \'' . PMA_sqlAddSlashes($dbname, true) . "'";

                $tables_to_search_for_users = array(
                    'columns_priv',
                );

                $db_rights_sqls = array();
                foreach ($tables_to_search_for_users as $table_search_in) {
                    if (in_array($table_search_in, $tables)) {
                        $db_rights_sqls[] = '
                            SELECT DISTINCT `Table_name`
                                   FROM `mysql`.' . PMA_backquote($table_search_in)
                                   . $user_host_condition;
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

                $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
                    .' ORDER BY `Table_name` ASC';

                $db_rights_result = PMA_DBI_query($db_rights_sql);

                while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
                    $db_rights_row = array_merge($user_defaults, $db_rights_row);
                    $db_rights[$db_rights_row['Table_name']] = $db_rights_row;
                }
                PMA_DBI_free_result($db_rights_result);
                unset($db_rights_sql, $db_rights_sqls, $db_rights_result, $db_rights_row);

                $sql_query = 'SELECT `Table_name`,'
                    .' `Table_priv`,'
                    .' IF(`Column_priv` = _latin1 \'\', 0, 1)'
                    .' AS \'Column_priv\''
                    .' FROM `mysql`.`tables_priv`'
                    . $user_host_condition
                    .' ORDER BY `Table_name` ASC;';
                $res = PMA_DBI_query($sql_query);
                $sql_query = '';

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
                   . '    <td colspan="6"><center><i>' . __('None') . '</i></center></td>' . "\n"
                   . '</tr>' . "\n";
            } else {
                $odd_row = true;
                $found_rows = array();
                //while ($row = PMA_DBI_fetch_assoc($res)) {
                foreach ($db_rights as $row) {
                    $found_rows[] = (! isset($dbname)) ? $row['Db'] : $row['Table_name'];

                    echo '<tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                       . '    <td>' . htmlspecialchars((! isset($dbname)) ? $row['Db'] : $row['Table_name']) . '</td>' . "\n"
                       . '    <td><tt>' . "\n"
                       . '        ' . join(',' . "\n" . '            ', PMA_extractPrivInfo($row, true)) . "\n"
                       . '        </tt></td>' . "\n"
                       . '    <td>' . ((((! isset($dbname)) && $row['Grant_priv'] == 'Y') || (isset($dbname) && in_array('Grant', explode(',', $row['Table_priv'])))) ? __('Yes') : __('No')) . '</td>' . "\n"
                       . '    <td>';
                    if (! empty($row['Table_privs']) || ! empty ($row['Column_priv'])) {
                        echo __('Yes');
                    } else {
                        echo __('No');
                    }
                    echo '</td>' . "\n"
                       . '    <td>';
                    printf(
                        $link_edit,
                        htmlspecialchars(urlencode($username)),
                        urlencode(htmlspecialchars($hostname)),
                        urlencode((! isset($dbname)) ? $row['Db'] : htmlspecialchars($dbname)),
                        urlencode((! isset($dbname)) ? '' : $row['Table_name'])
                    );
                    echo '</td>' . "\n"
                       . '    <td>';
                    if (! empty($row['can_delete']) || isset($row['Table_name']) && strlen($row['Table_name'])) {
                        printf(
                            $link_revoke,
                            htmlspecialchars(urlencode($username)),
                            urlencode(htmlspecialchars($hostname)),
                            urlencode((! isset($dbname)) ? $row['Db'] : htmlspecialchars($dbname)),
                            urlencode((! isset($dbname)) ? '' : $row['Table_name'])
                        );
                    }
                    echo '</td>' . "\n"
                       . '</tr>' . "\n";
                    $odd_row = ! $odd_row;
                } // end while
            }
            unset($row);
            echo '</tbody>' . "\n"
               . '</table>' . "\n";

            if (! isset($dbname)) {

                // no database name was given, display select db

                $pred_db_array =PMA_DBI_fetch_result('SHOW DATABASES;');

                echo '    <label for="text_dbname">' . __('Add privileges on the following database') . ':</label>' . "\n";
                if (! empty($pred_db_array)) {
                    echo '    <select name="pred_dbname" class="autosubmit">' . "\n"
                       . '        <option value="" selected="selected">' . __('Use text field') . ':</option>' . "\n";
                    foreach ($pred_db_array as $current_db) {
                        $current_db = PMA_escape_mysql_wildcards($current_db);
                        // cannot use array_diff() once, outside of the loop,
                        // because the list of databases has special characters
                        // already escaped in $found_rows,
                        // contrary to the output of SHOW DATABASES
                        if (empty($found_rows) || ! in_array($current_db, $found_rows)) {
                            echo '        <option value="' . htmlspecialchars($current_db) . '">'
                                . htmlspecialchars($current_db) . '</option>' . "\n";
                        }
                    }
                    echo '    </select>' . "\n";
                }
                echo '    <input type="text" id="text_dbname" name="dbname" />' . "\n"
                    . PMA_showHint(__('Wildcards % and _ should be escaped with a \ to use them literally'));
            } else {
                echo '    <input type="hidden" name="dbname" value="' . htmlspecialchars($dbname) . '"/>' . "\n"
                   . '    <label for="text_tablename">' . __('Add privileges on the following table') . ':</label>' . "\n";
                if ($res = @PMA_DBI_try_query('SHOW TABLES FROM ' . PMA_backquote(PMA_unescape_mysql_wildcards($dbname)) . ';', null, PMA_DBI_QUERY_STORE)) {
                    $pred_tbl_array = array();
                    while ($row = PMA_DBI_fetch_row($res)) {
                        if (! isset($found_rows) || ! in_array($row[0], $found_rows)) {
                            $pred_tbl_array[] = $row[0];
                        }
                    }
                    PMA_DBI_free_result($res);
                    unset($res, $row);
                    if (! empty($pred_tbl_array)) {
                        echo '    <select name="pred_tablename" class="autosubmit">' . "\n"
                           . '        <option value="" selected="selected">' . __('Use text field') . ':</option>' . "\n";
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
               . '    <input type="submit" value="' . __('Go') . '" />'
               . '</fieldset>' . "\n"
               . '</form>' . "\n";

        }

        // Provide a line with links to the relevant database and table
        if (isset($dbname) && empty($dbname_is_wildcard)) {
            echo '[ ' . __('Database')
                . ' <a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?'
                . $GLOBALS['url_query'] . '&amp;db=' . $url_dbname . '&amp;reload=1">'
                . htmlspecialchars($dbname) . ': ' . PMA_getTitleForTarget($GLOBALS['cfg']['DefaultTabDatabase']) . "</a> ]\n";

            if (isset($tablename)) {
                echo ' [ ' . __('Table') . ' <a href="'
                    . $GLOBALS['cfg']['DefaultTabTable'] . '?' . $GLOBALS['url_query']
                    . '&amp;db=' . $url_dbname . '&amp;table=' . htmlspecialchars(urlencode($tablename))
                    . '&amp;reload=1">' . htmlspecialchars($tablename) . ': '
                    . PMA_getTitleForTarget($GLOBALS['cfg']['DefaultTabTable'])
                    . "</a> ]\n";
            }
            unset($url_dbname);
        }

        if (! isset($dbname) && ! $user_does_not_exists) {
            include_once './libraries/display_change_password.lib.php';

            echo '<form action="server_privileges.php" method="post" class="copyUserForm">' . "\n"
               . PMA_generate_common_hidden_inputs('', '')
               . '<input type="hidden" name="old_username" value="' . htmlspecialchars($username) . '" />' . "\n"
               . '<input type="hidden" name="old_hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n"
               . '<fieldset id="fieldset_change_copy_user">' . "\n"
               . '    <legend>' . __('Change Login Information / Copy User') . '</legend>' . "\n";
            PMA_displayLoginInformationFields('change');
            echo '    <fieldset>' . "\n"
                . '        <legend>' . __('Create a new user with the same privileges and ...') . '</legend>' . "\n";
            $choices = array(
                '4' => __('... keep the old one.'),
                '1' => __('... delete the old one from the user tables.'),
                '2' => __('... revoke all active privileges from the old one and delete it afterwards.'),
                '3' => __('... delete the old one from the user tables and reload the privileges afterwards.'));
            PMA_display_html_radio('mode', $choices, '4', true);
            unset($choices);

            echo '    </fieldset>' . "\n"
               . '</fieldset>' . "\n"
               . '<fieldset id="fieldset_change_copy_user_footer" class="tblFooters">' . "\n"
               . '    <input type="submit" name="change_copy" value="' . __('Go') . '" />' . "\n"
               . '</fieldset>' . "\n"
               . '</form>' . "\n";
        }
    }
} elseif (isset($_REQUEST['adduser'])) {

    // Add user
    $GLOBALS['url_query'] .= '&amp;adduser=1';
    echo '<h2>' . "\n"
       . PMA_getIcon('b_usradd.png') . __('Add user') . "\n"
       . '</h2>' . "\n"
       . '<form name="usersForm" id="addUsersForm_' . $random_n . '" action="server_privileges.php" method="post">' . "\n"
       . PMA_generate_common_hidden_inputs('', '');
    PMA_displayLoginInformationFields('new');
    echo '<fieldset id="fieldset_add_user_database">' . "\n"
        . '<legend>' . __('Database for user') . '</legend>' . "\n";

    $default_choice = 0;
    $choices = array(
        '0' => _pgettext('Create none database for user', 'None'),
        '1' => __('Create database with same name and grant all privileges'),
        '2' => __('Grant all privileges on wildcard name (username\\_%)'));

    if (! empty($dbname) ) {
        $choices['3'] = sprintf(
            __('Grant all privileges on database &quot;%s&quot;'),
            htmlspecialchars($dbname)
        );
        $default_choice = 3;
        echo '<input type="hidden" name="dbname" value="' . htmlspecialchars($dbname) . '" />' . "\n";
    }

    // 4th parameter set to true to add line breaks
    // 5th parameter set to false to avoid htmlspecialchars() escaping in the label
    //  since we have some HTML in some labels
    PMA_display_html_radio('createdb', $choices, $default_choice, true, false);
    unset($choices);
    unset($default_choice);

    echo '</fieldset>' . "\n";
    PMA_displayPrivTable('*', '*', false);
    echo '    <fieldset id="fieldset_add_user_footer" class="tblFooters">' . "\n"
       . '        <input type="submit" name="adduser_submit" value="' . __('Go') . '" />' . "\n"
       . '    </fieldset>' . "\n"
       . '</form>' . "\n";
} else {
    // check the privileges for a particular database.
    $user_form = '<form id="usersForm" action="server_privileges.php"><fieldset>' . "\n"
       . '<legend>' . "\n"
       . PMA_getIcon('b_usrcheck.png')
       . '    ' . sprintf(__('Users having access to &quot;%s&quot;'), '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url($checkprivs) . '">' .  htmlspecialchars($checkprivs) . '</a>') . "\n"
       . '</legend>' . "\n"
       . '<table id="dbspecificuserrights" class="data">' . "\n"
       . '<thead>' . "\n"
       . '    <tr><th>' . __('User') . '</th>' . "\n"
       . '        <th>' . __('Host') . '</th>' . "\n"
       . '        <th>' . __('Type') . '</th>' . "\n"
       . '        <th>' . __('Privileges') . '</th>' . "\n"
       . '        <th>' . __('Grant') . '</th>' . "\n"
       . '        <th>' . __('Action') . '</th>' . "\n"
       . '    </tr>' . "\n"
       . '</thead>' . "\n"
       . '<tbody>' . "\n";
    $odd_row = true;
    unset($row, $row1, $row2);

    // now, we build the table...
    $list_of_privileges
        = '`User`, '
        . '`Host`, '
        . '`Select_priv`, '
        . '`Insert_priv`, '
        . '`Update_priv`, '
        . '`Delete_priv`, '
        . '`Create_priv`, '
        . '`Drop_priv`, '
        . '`Grant_priv`, '
        . '`Index_priv`, '
        . '`Alter_priv`, '
        . '`References_priv`, '
        . '`Create_tmp_table_priv`, '
        . '`Lock_tables_priv`, '
        . '`Create_view_priv`, '
        . '`Show_view_priv`, '
        . '`Create_routine_priv`, '
        . '`Alter_routine_priv`, '
        . '`Execute_priv`';

    $list_of_compared_privileges
        = '`Select_priv` = \'N\''
        . ' AND `Insert_priv` = \'N\''
        . ' AND `Update_priv` = \'N\''
        . ' AND `Delete_priv` = \'N\''
        . ' AND `Create_priv` = \'N\''
        . ' AND `Drop_priv` = \'N\''
        . ' AND `Grant_priv` = \'N\''
        . ' AND `References_priv` = \'N\''
        . ' AND `Create_tmp_table_priv` = \'N\''
        . ' AND `Lock_tables_priv` = \'N\''
        . ' AND `Create_view_priv` = \'N\''
        . ' AND `Show_view_priv` = \'N\''
        . ' AND `Create_routine_priv` = \'N\''
        . ' AND `Alter_routine_priv` = \'N\''
        . ' AND `Execute_priv` = \'N\'';

    if (PMA_MYSQL_INT_VERSION >= 50106) {
        $list_of_privileges .=
            ', `Event_priv`, '
            . '`Trigger_priv`';
        $list_of_compared_privileges .=
            ' AND `Event_priv` = \'N\''
            . ' AND `Trigger_priv` = \'N\'';
    }

    $sql_query = '(SELECT ' . $list_of_privileges . ', `Db`'
        .' FROM `mysql`.`db`'
        .' WHERE \'' . PMA_sqlAddSlashes($checkprivs) . "'"
        .' LIKE `Db`'
        .' AND NOT (' . $list_of_compared_privileges. ')) '
        .'UNION '
        .'(SELECT ' . $list_of_privileges . ', \'*\' AS `Db`'
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

    if ($found) {
        while (true) {
            // prepare the current user
            $current_privileges = array();
            $current_user = $row['User'];
            $current_host = $row['Host'];
            while ($row && $current_user == $row['User'] && $current_host == $row['Host']) {
                $current_privileges[] = $row;
                $row = PMA_DBI_fetch_assoc($res);
            }
            $user_form .= '    <tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
               . '        <td';
            if (count($current_privileges) > 1) {
                $user_form .= ' rowspan="' . count($current_privileges) . '"';
            }
            $user_form .= '>' . (empty($current_user) ? '<span style="color: #FF0000">' . __('Any') . '</span>' : htmlspecialchars($current_user)) . "\n"
               . '        </td>' . "\n"
               . '        <td';
            if (count($current_privileges) > 1) {
                $user_form .= ' rowspan="' . count($current_privileges) . '"';
            }
            $user_form .= '>' . htmlspecialchars($current_host) . '</td>' . "\n";
            for ($i = 0; $i < count($current_privileges); $i++) {
                $current = $current_privileges[$i];
                $user_form .= '        <td>' . "\n"
                   . '            ';
                if (! isset($current['Db']) || $current['Db'] == '*') {
                    $user_form .= __('global');
                } elseif ($current['Db'] == PMA_escape_mysql_wildcards($checkprivs)) {
                    $user_form .= __('database-specific');
                } else {
                    $user_form .= __('wildcard'). ': <tt>' . htmlspecialchars($current['Db']) . '</tt>';
                }
                $user_form .= "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n"
                   . '            <tt>' . "\n"
                   . '                ' . join(',' . "\n" . '                ', PMA_extractPrivInfo($current, true)) . "\n"
                   . '            </tt>' . "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n"
                   . '            ' . ($current['Grant_priv'] == 'Y' ? __('Yes') : __('No')) . "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n";
                $user_form .= sprintf(
                    $link_edit,
                    urlencode($current_user),
                    urlencode($current_host),
                    urlencode(! isset($current['Db']) || $current['Db'] == '*' ? '' : $current['Db']),
                    ''
                );
                $user_form .= '</td>' . "\n"
                   . '    </tr>' . "\n";
                if (($i + 1) < count($current_privileges)) {
                    $user_form .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">' . "\n";
                }
            }
            if (empty($row) && empty($row1) && empty($row2)) {
                break;
            }
            $odd_row = ! $odd_row;
        }
    } else {
        $user_form .= '    <tr class="odd">' . "\n"
           . '        <td colspan="6">' . "\n"
           . '            ' . __('No user found.') . "\n"
           . '        </td>' . "\n"
           . '    </tr>' . "\n";
    }
    $user_form .= '</tbody>' . "\n"
       . '</table></fieldset></form>' . "\n";

    if ($GLOBALS['is_ajax_request'] == true) {
        $extra_data['user_form'] = $user_form;
        $message = PMA_Message::success(__('User has been added.'));
        PMA_ajaxResponse($message, $message->isSuccess(), $extra_data);
    } else {
        // Offer to create a new user for the current database
        $user_form .= '<fieldset id="fieldset_add_user">' . "\n"
           . '<legend>' . __('New') . '</legend>' . "\n"
           . '    <a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;adduser=1&amp;dbname=' . htmlspecialchars($checkprivs) .'" rel="'.'checkprivs='.htmlspecialchars($checkprivs). '&amp;'.$GLOBALS['url_query'] . '" class="'.$conditional_class.'" name="db_specific">' . "\n"
           . PMA_getIcon('b_usradd.png')
           . '        ' . __('Add user') . '</a>' . "\n"
           . '</fieldset>' . "\n";
        echo $user_form ;
    }

} // end if (empty($_REQUEST['adduser']) && empty($checkprivs)) ... elseif ... else ...


/**
 * Displays the footer
 */
echo "\n\n";
require './libraries/footer.inc.php';

?>
