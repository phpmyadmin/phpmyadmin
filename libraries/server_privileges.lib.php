<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions with the Privileges section in pma
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get Html for User Group Dialog
 *
 * @param string $username     username
 * @param bool   $is_menuswork Is menuswork set in configuration
 *
 * @return string html
 */
function PMA_getHtmlForUserGroupDialog($username, $is_menuswork)
{
    $html = '';
    if (! empty($_REQUEST['edit_user_group_dialog']) && $is_menuswork) {
        $dialog = PMA_getHtmlToChooseUserGroup($username);
        $response = PMA_Response::getInstance();
        if ($GLOBALS['is_ajax_request']) {
            $response->addJSON('message', $dialog);
            exit;
        } else {
            $html .= $dialog;
        }
    }

    return $html;
}

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
            $db_and_table = PMA_Util::backquote(
                PMA_Util::unescapeMysqlWildcards($dbname)
            )
            . '.' . PMA_Util::backquote($tablename);
        } else {
            $db_and_table = PMA_Util::backquote($dbname) . '.*';
        }
    }
    return $db_and_table;
}

/**
 * Generates a condition on the user name
 *
 * @param string $initial the user's initial
 *
 * @return string   the generated condition
 */
function PMA_rangeOfUsers($initial = '')
{
    // strtolower() is used because the User field
    // might be BINARY, so LIKE would be case sensitive
    if ($initial === null || $initial === '') {
        return '';
    }

    $ret = " WHERE `User` LIKE '"
        . PMA_Util::sqlAddSlashes($initial, true) . "%'"
        . " OR `User` LIKE '"
        . PMA_Util::sqlAddSlashes(strtolower($initial), true) . "%'";

    return $ret;
} // end function

/**
 * Extracts the privilege information of a priv table row
 *
 * @param array   $row        the row
 * @param boolean $enableHTML add <dfn> tag with tooltips
 * @param boolean $tablePrivs whether row contains table privileges
 *
 * @global  resource $user_link the database connection
 *
 * @return array
 */
function PMA_extractPrivInfo($row = '', $enableHTML = false, $tablePrivs = false)
{
    if ($tablePrivs) {
        $grants = PMA_getTableGrantsArray();
    } else {
        $grants = PMA_getGrantsArray();
    }

    if (! empty($row) && isset($row['Table_priv'])) {
        $row1 = $GLOBALS['dbi']->fetchSingleRow(
            'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
            'ASSOC', $GLOBALS['userlink']
        );
        $av_grants = explode(
            '\',\'',
            substr($row1['Type'], 5, strlen($row1['Type']) - 7)
        );
        unset($row1);
        $users_grants = explode(',', $row['Table_priv']);
        foreach ($av_grants as $current_grant) {
            $row[$current_grant . '_priv']
                = in_array($current_grant, $users_grants) ? 'Y' : 'N';
        }
        unset($current_grant);
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
                && count($GLOBALS[$current_grant[0]]) == $_REQUEST['column_count']
                && empty($GLOBALS[$current_grant[0] . '_none']))))
            ) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $current_grant[2] . '">'
                        . $current_grant[1] . '</dfn>';
                } else {
                    $privs[] = $current_grant[1];
                }
            } elseif (! empty($GLOBALS[$current_grant[0]])
                && is_array($GLOBALS[$current_grant[0]])
                && empty($GLOBALS[$current_grant[0] . '_none'])
            ) {
                if ($enableHTML) {
                    $priv_string = '<dfn title="' . $current_grant[2] . '">'
                        . $current_grant[1] . '</dfn>';
                } else {
                    $priv_string = $current_grant[1];
                }
                $privs[] = $priv_string . ' (`'
                    . join('`, `', $GLOBALS[$current_grant[0]]) . '`)';
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
    } elseif ($allPrivileges
        && (! isset($_POST['grant_count']) || count($privs) == $_POST['grant_count'])
    ) {
        if ($enableHTML) {
            $privs = array('<dfn title="'
                . __('Includes all privileges except GRANT.')
                . '">ALL PRIVILEGES</dfn>'
            );
        } else {
            $privs = array('ALL PRIVILEGES');
        }
    }
    return $privs;
} // end of the 'PMA_extractPrivInfo()' function

/**
 * Returns an array of table grants and their descriptions
 *
 * @return array array of table grants
 */
function PMA_getTableGrantsArray()
{
    return array(
        array(
            'Delete',
            'DELETE',
            $GLOBALS['strPrivDescDelete']
        ),
        array(
            'Create',
            'CREATE',
            $GLOBALS['strPrivDescCreateTbl']
        ),
        array(
            'Drop',
            'DROP',
            $GLOBALS['strPrivDescDropTbl']
        ),
        array(
            'Index',
            'INDEX',
            $GLOBALS['strPrivDescIndex']
        ),
        array(
            'Alter',
            'ALTER',
            $GLOBALS['strPrivDescAlter']
        ),
        array(
            'Create View',
            'CREATE_VIEW',
            $GLOBALS['strPrivDescCreateView']
        ),
        array(
            'Show view',
            'SHOW_VIEW',
            $GLOBALS['strPrivDescShowView']
        ),
        array(
            'Trigger',
            'TRIGGER',
            $GLOBALS['strPrivDescTrigger']
        ),
    );
}

/**
 * Get the grants array which contains all the privilege types
 * and relevent grant messages
 *
 * @return array
 */
function PMA_getGrantsArray()
{
    return array(
        array(
            'Select_priv',
            'SELECT',
            __('Allows reading data.')
        ),
        array(
            'Insert_priv',
            'INSERT',
            __('Allows inserting and replacing data.')
        ),
        array(
            'Update_priv',
            'UPDATE',
            __('Allows changing data.')
        ),
        array(
            'Delete_priv',
            'DELETE',
            __('Allows deleting data.')
        ),
        array(
            'Create_priv',
            'CREATE',
            __('Allows creating new databases and tables.')
        ),
        array(
            'Drop_priv',
            'DROP',
            __('Allows dropping databases and tables.')
        ),
        array(
            'Reload_priv',
            'RELOAD',
            __('Allows reloading server settings and flushing the server\'s caches.')
        ),
        array(
            'Shutdown_priv',
            'SHUTDOWN',
            __('Allows shutting down the server.')
        ),
        array(
            'Process_priv',
            'PROCESS',
            __('Allows viewing processes of all users.')
        ),
        array(
            'File_priv',
            'FILE',
            __('Allows importing data from and exporting data into files.')
        ),
        array(
            'References_priv',
            'REFERENCES',
            __('Has no effect in this MySQL version.')
        ),
        array(
            'Index_priv',
            'INDEX',
            __('Allows creating and dropping indexes.')
        ),
        array(
            'Alter_priv',
            'ALTER',
            __('Allows altering the structure of existing tables.')
        ),
        array(
            'Show_db_priv',
            'SHOW DATABASES',
            __('Gives access to the complete list of databases.')
        ),
        array(
            'Super_priv',
            'SUPER',
            __(
                'Allows connecting, even if maximum number of connections '
                . 'is reached; required for most administrative operations '
                . 'like setting global variables or killing threads of other users.'
            )
        ),
        array(
            'Create_tmp_table_priv',
            'CREATE TEMPORARY TABLES',
            __('Allows creating temporary tables.')
        ),
        array(
            'Lock_tables_priv',
            'LOCK TABLES',
            __('Allows locking tables for the current thread.')
        ),
        array(
            'Repl_slave_priv',
            'REPLICATION SLAVE',
            __('Needed for the replication slaves.')
        ),
        array(
            'Repl_client_priv',
            'REPLICATION CLIENT',
            __('Allows the user to ask where the slaves / masters are.')
        ),
        array(
            'Create_view_priv',
            'CREATE VIEW',
            __('Allows creating new views.')
        ),
        array(
            'Event_priv',
            'EVENT',
            __('Allows to set up events for the event scheduler.')
        ),
        array(
            'Trigger_priv',
            'TRIGGER',
            __('Allows creating and dropping triggers.')
        ),
        // for table privs:
        array(
            'Create View_priv',
            'CREATE VIEW',
            __('Allows creating new views.')
        ),
        array(
            'Show_view_priv',
            'SHOW VIEW',
            __('Allows performing SHOW CREATE VIEW queries.')
        ),
        // for table privs:
        array(
            'Show view_priv',
            'SHOW VIEW',
            __('Allows performing SHOW CREATE VIEW queries.')
        ),
        array(
            'Create_routine_priv',
            'CREATE ROUTINE',
            __('Allows creating stored routines.')
        ),
        array(
            'Alter_routine_priv',
            'ALTER ROUTINE',
            __('Allows altering and dropping stored routines.')
        ),
        array(
            'Create_user_priv',
            'CREATE USER',
            __('Allows creating, dropping and renaming user accounts.')
        ),
        array(
            'Execute_priv',
            'EXECUTE',
            __('Allows executing stored routines.')
        ),
    );
}

/**
 * Displays on which column(s) a table-specific privilege is granted
 *
 * @param array  $columns          columns array
 * @param array  $row              first row from result or boolean false
 * @param string $name_for_select  privilege types - Select_priv, Insert_priv
 *                                 Update_priv, References_priv
 * @param string $priv_for_header  privilege for header
 * @param string $name             privilege name: insert, select, update, references
 * @param string $name_for_dfn     name for dfn
 * @param string $name_for_current name for current
 *
 * @return string $html_output html snippet
 */
function PMA_getHtmlForColumnPrivileges($columns, $row, $name_for_select,
    $priv_for_header, $name, $name_for_dfn, $name_for_current
) {
    $html_output = '<div class="item" id="div_item_' . $name . '">' . "\n"
        . '<label for="select_' . $name . '_priv">' . "\n"
        . '<code><dfn title="' . $name_for_dfn . '">'
        . $priv_for_header . '</dfn></code>' . "\n"
        . '</label><br />' . "\n"
        . '<select id="select_' . $name . '_priv" name="'
        . $name_for_select . '[]" multiple="multiple" size="8">' . "\n";

    foreach ($columns as $currCol => $currColPrivs) {
        $html_output .= '<option '
            . 'value="' . htmlspecialchars($currCol) . '"';
        if ($row[$name_for_select] == 'Y'
            || $currColPrivs[$name_for_current]
        ) {
            $html_output .= ' selected="selected"';
        }
        $html_output .= '>'
            . htmlspecialchars($currCol) . '</option>' . "\n";
    }

    $html_output .= '</select>' . "\n"
        . '<i>' . __('Or') . '</i>' . "\n"
        . '<label for="checkbox_' . $name_for_select
        . '_none"><input type="checkbox"'
        . ' name="' . $name_for_select . '_none" id="checkbox_'
        . $name_for_select . '_none" title="'
        . _pgettext('None privileges', 'None') . '" />'
        . _pgettext('None privileges', 'None') . '</label>' . "\n"
        . '</div>' . "\n";
    return $html_output;
} // end function

/**
 * Get sql query for display privileges table
 *
 * @param string $db       the database
 * @param string $table    the table
 * @param string $username username for database connection
 * @param string $hostname hostname for database connection
 *
 * @return string sql query
 */
function PMA_getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname)
{
    if ($db == '*') {
        return "SELECT * FROM `mysql`.`user`"
            . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";
    } elseif ($table == '*') {
        return "SELECT * FROM `mysql`.`db`"
            . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "'"
            . " AND '" . PMA_Util::unescapeMysqlWildcards($db) . "'"
            . " LIKE `Db`;";
    }
    return "SELECT `Table_priv`"
        . " FROM `mysql`.`tables_priv`"
        . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
        . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "'"
        . " AND `Db` = '" . PMA_Util::unescapeMysqlWildcards($db) . "'"
        . " AND `Table_name` = '" . PMA_Util::sqlAddSlashes($table) . "';";
}

/**
 * Displays a dropdown to select the user group
 * with menu items configured to each of them.
 *
 * @param string $username username
 *
 * @return string html to select the user group
 */
function PMA_getHtmlToChooseUserGroup($username)
{
    $html_output = '<form class="ajax" id="changeUserGroupForm"'
            . ' action="server_privileges.php" method="post">';
    $params = array('username' => $username);
    $html_output .= PMA_URL_getHiddenInputs($params);
    $html_output .= '<fieldset id="fieldset_user_group_selection">';
    $html_output .= '<legend>' . __('User group') . '</legend>';

    $groupTable = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
        . "." . PMA_Util::backquote($GLOBALS['cfg']['Server']['usergroups']);
    $userTable = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
        . "." . PMA_Util::backquote($GLOBALS['cfg']['Server']['users']);

    $userGroups = array();
    $sql_query = "SELECT DISTINCT `usergroup` FROM " . $groupTable;
    $result = PMA_queryAsControlUser($sql_query, false);
    if ($result) {
        while ($row = $GLOBALS['dbi']->fetchRow($result)) {
            $userGroups[] = $row[0];
        }
    }
    $GLOBALS['dbi']->freeResult($result);

    $userGroup = '';
    if (isset($GLOBALS['username'])) {
        $sql_query = "SELECT `usergroup` FROM " . $userTable
            . " WHERE `username` = '" . PMA_Util::sqlAddSlashes($username) . "'";
        $userGroup = $GLOBALS['dbi']->fetchValue(
            $sql_query, 0, 0, $GLOBALS['controllink']
        );
    }

    $html_output .= __('User group') . ': ';
    $html_output .= '<select name="userGroup">';
    $html_output .= '<option value=""></option>';
    foreach ($userGroups as $oneUserGroup) {
        $html_output .= '<option value="' . htmlspecialchars($oneUserGroup) . '"'
            . ($oneUserGroup == $userGroup ? ' selected="selected"' : '')
            . '>'
            . htmlspecialchars($oneUserGroup)
            . '</option>';
    }
    $html_output .= '</select>';
    $html_output .= '<input type="hidden" name="changeUserGroup" value="1">';
    $html_output .= '</fieldset>';
    $html_output .= '</form>';
    return $html_output;
}

/**
 * Sets the user group from request values
 *
 * @param string $username  username
 * @param string $userGroup user group to set
 *
 * @return void
 */
function PMA_setUserGroup($username, $userGroup)
{
    $userTable = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
        . "." . PMA_Util::backquote($GLOBALS['cfg']['Server']['users']);

    $sql_query = "SELECT `usergroup` FROM " . $userTable
        . " WHERE `username` = '" . PMA_Util::sqlAddSlashes($username) . "'";
    $oldUserGroup = $GLOBALS['dbi']->fetchValue(
        $sql_query, 0, 0, $GLOBALS['controllink']
    );

    if ($oldUserGroup === false) {
        $upd_query = "INSERT INTO " . $userTable . "(`username`, `usergroup`)"
            . " VALUES ('" . PMA_Util::sqlAddSlashes($username) . "', "
            . "'" . PMA_Util::sqlAddSlashes($userGroup) . "')";
    } else {
        if (empty($userGroup)) {
            $upd_query = "DELETE FROM " . $userTable
                . " WHERE `username`='" . PMA_Util::sqlAddSlashes($username) . "'";
        } elseif ($oldUserGroup != $userGroup) {
            $upd_query = "UPDATE " . $userTable
                . " SET `usergroup`='" . PMA_Util::sqlAddSlashes($userGroup) . "'"
                . " WHERE `username`='" . PMA_Util::sqlAddSlashes($username) . "'";
        }
    }
    if (isset($upd_query)) {
        PMA_queryAsControlUser($upd_query);
    }
}

/**
 * Displays the privileges form table
 *
 * @param string  $db     the database
 * @param string  $table  the table
 * @param boolean $submit whether to display the submit button or not
 *
 * @global  array      $cfg         the phpMyAdmin configuration
 * @global  ressource  $user_link   the database connection
 *
 * @return string html snippet
 */
function PMA_getHtmlToDisplayPrivilegesTable($db = '*',
    $table = '*', $submit = true
) {
    $html_output = '';

    if ($db == '*') {
        $table = '*';
    }

    if (isset($GLOBALS['username'])) {
        $username = $GLOBALS['username'];
        $hostname = $GLOBALS['hostname'];
        $sql_query = PMA_getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
        );
        $row = $GLOBALS['dbi']->fetchSingleRow($sql_query);
    }
    if (empty($row)) {
        if ($table == '*') {
            if ($db == '*') {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`user`;';
            } elseif ($table == '*') {
                $sql_query = 'SHOW COLUMNS FROM `mysql`.`db`;';
            }
            $res = $GLOBALS['dbi']->query($sql_query);
            while ($row1 = $GLOBALS['dbi']->fetchRow($res)) {
                if (substr($row1[0], 0, 4) == 'max_') {
                    $row[$row1[0]] = 0;
                } else {
                    $row[$row1[0]] = 'N';
                }
            }
            $GLOBALS['dbi']->freeResult($res);
        } else {
            $row = array('Table_priv' => '');
        }
    }
    if (isset($row['Table_priv'])) {
        $row1 = $GLOBALS['dbi']->fetchSingleRow(
            'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
            'ASSOC', $GLOBALS['userlink']
        );
        // note: in MySQL 5.0.3 we get "Create View', 'Show view';
        // the View for Create is spelled with uppercase V
        // the view for Show is spelled with lowercase v
        // and there is a space between the words

        $av_grants = explode(
            '\',\'',
            substr(
                $row1['Type'],
                strpos($row1['Type'], '(') + 2,
                strpos($row1['Type'], ')') - strpos($row1['Type'], '(') - 3
            )
        );
        unset($row1);
        $users_grants = explode(',', $row['Table_priv']);

        foreach ($av_grants as $current_grant) {
            $row[$current_grant . '_priv']
                = in_array($current_grant, $users_grants) ? 'Y' : 'N';
        }
        unset($row['Table_priv'], $current_grant, $av_grants, $users_grants);

        // get columns
        $res = $GLOBALS['dbi']->tryQuery(
            'SHOW COLUMNS FROM '
            . PMA_Util::backquote(
                PMA_Util::unescapeMysqlWildcards($db)
            )
            . '.' . PMA_Util::backquote($table) . ';'
        );
        $columns = array();
        if ($res) {
            while ($row1 = $GLOBALS['dbi']->fetchRow($res)) {
                $columns[$row1[0]] = array(
                    'Select' => false,
                    'Insert' => false,
                    'Update' => false,
                    'References' => false
                );
            }
            $GLOBALS['dbi']->freeResult($res);
        }
        unset($res, $row1);
    }
    // table-specific privileges
    if (! empty($columns)) {
        $html_output .= PMA_getHtmlForTableSpecificPrivileges(
            $username, $hostname, $db, $table, $columns, $row
        );
    } else {
        // global or db-specific
        $html_output .= PMA_getHtmlForGlobalOrDbSpecificPrivs($db, $table, $row);
    }
    $html_output .= '</fieldset>' . "\n";
    if ($submit) {
        $html_output .= '<fieldset id="fieldset_user_privtable_footer" '
            . 'class="tblFooters">' . "\n"
           . '<input type="submit" name="update_privs" '
            . 'value="' . __('Go') . '" />' . "\n"
           . '</fieldset>' . "\n";
    }
    return $html_output;
} // end of the 'PMA_displayPrivTable()' function

/**
 * Get HTML for "Resource limits"
 *
 * @param array $row first row from result or boolean false
 *
 * @return string html snippet
 */
function PMA_getHtmlForResourceLimits($row)
{
    $html_output = '<fieldset>' . "\n"
        . '<legend>' . __('Resource limits') . '</legend>' . "\n"
        . '<p><small>'
        . '<i>' . __('Note: Setting these options to 0 (zero) removes the limit.')
        . '</i></small></p>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_questions">'
        . '<code><dfn title="'
        . __(
            'Limits the number of queries the user may send to the server per hour.'
        )
        . '">'
        . 'MAX QUERIES PER HOUR'
        . '</dfn></code></label>' . "\n"
        . '<input type="number" name="max_questions" id="text_max_questions" '
        . 'value="' . $row['max_questions'] . '" min="0" '
        . 'title="'
        . __(
            'Limits the number of queries the user may send to the server per hour.'
        )
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_updates">'
        . '<code><dfn title="'
        . __(
            'Limits the number of commands that change any table '
            . 'or database the user may execute per hour.'
        ) . '">'
        . 'MAX UPDATES PER HOUR'
        . '</dfn></code></label>' . "\n"
        . '<input type="number" name="max_updates" id="text_max_updates" '
        . 'value="' . $row['max_updates'] . '" min="0" '
        . 'title="'
        . __(
            'Limits the number of commands that change any table '
            . 'or database the user may execute per hour.'
        )
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_connections">'
        . '<code><dfn title="'
        . __(
            'Limits the number of new connections the user may open per hour.'
        ) . '">'
        . 'MAX CONNECTIONS PER HOUR'
        . '</dfn></code></label>' . "\n"
        . '<input type="number" name="max_connections" id="text_max_connections" '
        . 'value="' . $row['max_connections'] . '" min="0" '
        . 'title="' . __(
            'Limits the number of new connections the user may open per hour.'
        )
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_user_connections">'
        . '<code><dfn title="'
        . __('Limits the number of simultaneous connections the user may have.')
        . '">'
        . 'MAX USER_CONNECTIONS'
        . '</dfn></code></label>' . "\n"
        . '<input type="number" name="max_user_connections" '
        . 'id="text_max_user_connections" '
        . 'value="' . $row['max_user_connections'] . '" '
        . 'title="'
        . __('Limits the number of simultaneous connections the user may have.')
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '</fieldset>' . "\n";

    return $html_output;
}

/**
 * Get the HTML snippet for table specific privileges
 *
 * @param string  $username username for database connection
 * @param string  $hostname hostname for database connection
 * @param string  $db       the database
 * @param string  $table    the table
 * @param boolean $columns  columns array
 * @param array   $row      current privileges row
 *
 * @return string $html_output
 */
function PMA_getHtmlForTableSpecificPrivileges(
    $username, $hostname, $db, $table, $columns, $row
) {
    $res = $GLOBALS['dbi']->query(
        'SELECT `Column_name`, `Column_priv`'
        . ' FROM `mysql`.`columns_priv`'
        . ' WHERE `User`'
        . ' = \'' . PMA_Util::sqlAddSlashes($username) . "'"
        . ' AND `Host`'
        . ' = \'' . PMA_Util::sqlAddSlashes($hostname) . "'"
        . ' AND `Db`'
        . ' = \'' . PMA_Util::sqlAddSlashes(
            PMA_Util::unescapeMysqlWildcards($db)
        ) . "'"
        . ' AND `Table_name`'
        . ' = \'' . PMA_Util::sqlAddSlashes($table) . '\';'
    );

    while ($row1 = $GLOBALS['dbi']->fetchRow($res)) {
        $row1[1] = explode(',', $row1[1]);
        foreach ($row1[1] as $current) {
            $columns[$row1[0]][$current] = true;
        }
    }
    $GLOBALS['dbi']->freeResult($res);
    unset($res, $row1, $current);

    $html_output = '<input type="hidden" name="grant_count" '
        . 'value="' . count($row) . '" />' . "\n"
        . '<input type="hidden" name="column_count" '
        . 'value="' . count($columns) . '" />' . "\n"
        . '<fieldset id="fieldset_user_priv">' . "\n"
        . '<legend data-submenu-label="Table">' . __('Table-specific privileges')
        . PMA_Util::showHint(
            __('Note: MySQL privilege names are expressed in English.')
        )
        . '</legend>' . "\n";

    // privs that are attached to a specific column
    $html_output .= PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn(
        $columns, $row
    );

    // privs that are not attached to a specific column
    $html_output .= '<div class="item">' . "\n"
        . PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn($row)
        . '</div>' . "\n";

    // for Safari 2.0.2
    $html_output .= '<div class="clearfloat"></div>' . "\n";

    return $html_output;
}

/**
 * Get HTML snippet for privileges that are attached to a specific column
 *
 * @param string $columns olumns array
 * @param array  $row     first row from result or boolean false
 *
 * @return string $html_output
 */
function PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn($columns, $row)
{
    $html_output = PMA_getHtmlForColumnPrivileges(
        $columns, $row, 'Select_priv', 'SELECT',
        'select', __('Allows reading data.'), 'Select'
    );

    $html_output .= PMA_getHtmlForColumnPrivileges(
        $columns, $row, 'Insert_priv', 'INSERT',
        'insert', __('Allows inserting and replacing data.'), 'Insert'
    );

    $html_output .= PMA_getHtmlForColumnPrivileges(
        $columns, $row, 'Update_priv', 'UPDATE',
        'update', __('Allows changing data.'), 'Update'
    );

    $html_output .= PMA_getHtmlForColumnPrivileges(
        $columns, $row, 'References_priv', 'REFERENCES', 'references',
        __('Has no effect in this MySQL version.'), 'References'
    );
    return $html_output;
}

/**
 * Get HTML for privileges that are not attached to a specific column
 *
 * @param array $row first row from result or boolean false
 *
 * @return string $html_output
 */
function PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn($row)
{
    $html_output = '';
    foreach ($row as $current_grant => $current_grant_value) {
        $grant_type = substr($current_grant, 0, (strlen($current_grant) - 5));
        if (in_array($grant_type, array('Select', 'Insert', 'Update', 'References'))
        ) {
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

        $html_output .= '<div class="item">' . "\n"
           . '<input type="checkbox"'
           . ' name="' . $current_grant . '" id="checkbox_' . $current_grant
           . '" value="Y" '
           . ($current_grant_value == 'Y' ? 'checked="checked" ' : '')
           . 'title="';

        $html_output .= (isset($GLOBALS[
                    'strPrivDesc' . substr(
                        $tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)
                    )
                ] )
                ? $GLOBALS[
                    'strPrivDesc' . substr(
                        $tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)
                    )
                ]
                : $GLOBALS[
                    'strPrivDesc' . substr(
                        $tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)
                    ) . 'Tbl'
                ]
            )
            . '"/>' . "\n";

        $html_output .= '<label for="checkbox_' . $current_grant
            . '"><code><dfn title="'
            . (isset($GLOBALS[
                    'strPrivDesc' . substr(
                        $tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)
                    )
                ])
                ? $GLOBALS[
                    'strPrivDesc' . substr(
                        $tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)
                    )
                ]
                : $GLOBALS[
                    'strPrivDesc' . substr(
                        $tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)
                    ) . 'Tbl'
                ]
            )
            . '">'
            . strtoupper(
                substr($current_grant, 0, strlen($current_grant) - 5)
            )
            . '</dfn></code></label>' . "\n"
            . '</div>' . "\n";
    } // end foreach ()
    return $html_output;
}

/**
 * Get HTML for global or database specific privileges
 *
 * @param string $db    the database
 * @param string $table the table
 * @param string $row   first row from result or boolean false
 *
 * @return string $html_output
 */
function PMA_getHtmlForGlobalOrDbSpecificPrivs($db, $table, $row)
{
    $privTable_names = array(0 => __('Data'),
        1 => __('Structure'),
        2 => __('Administration')
    );
    $privTable = array();
    // d a t a
    $privTable[0] = PMA_getDataPrivilegeTable($db);

    // s t r u c t u r e
    $privTable[1] = PMA_getStructurePrivilegeTable($table, $row);

    // a d m i n i s t r a t i o n
    $privTable[2] = PMA_getAdministrationPrivilegeTable($db);

    $html_output = '<input type="hidden" name="grant_count" value="'
        . (count($privTable[0])
            + count($privTable[1])
            + count($privTable[2])
            - (isset($row['Grant_priv']) ? 1 : 0)
        )
        . '" />';
    $legend = $menu_label = '';
    if ($db == '*') {
        $legend     = __('Global privileges');
        $menu_label = __('Global');
    } else if ($table == '*') {
        $legend     = __('Database-specific privileges');
        $menu_label = __('Database');
    } else {
        $legend     = __('Table-specific privileges');
        $menu_label = __('Table');
    }
    $html_output .= '<fieldset id="fieldset_user_global_rights">'
        . '<legend data-submenu-label="' . $menu_label . '">' . $legend
        . '<input type="checkbox" id="addUsersForm_checkall" '
        . 'class="checkall_box" title="' . __('Check All') . '" /> '
        . '<label for="addUsersForm_checkall">' . __('Check All') . '</label> '
        . '</legend>'
        . '<p><small><i>'
        . __('Note: MySQL privilege names are expressed in English.')
        . '</i></small></p>';

    // Output the Global privilege tables with checkboxes
    $html_output .= PMA_getHtmlForGlobalPrivTableWithCheckboxes(
        $privTable, $privTable_names, $row
    );

    // The "Resource limits" box is not displayed for db-specific privs
    if ($db == '*') {
        $html_output .= PMA_getHtmlForResourceLimits($row);
    }
    // for Safari 2.0.2
    $html_output .= '<div class="clearfloat"></div>';

    return $html_output;
}

/**
 * Get data privilege table as an array
 *
 * @param string $db the database
 *
 * @return string data privilege table
 */
function PMA_getDataPrivilegeTable($db)
{
    $data_privTable = array(
        array('Select', 'SELECT', __('Allows reading data.')),
        array('Insert', 'INSERT', __('Allows inserting and replacing data.')),
        array('Update', 'UPDATE', __('Allows changing data.')),
        array('Delete', 'DELETE', __('Allows deleting data.'))
    );
    if ($db == '*') {
        $data_privTable[]
            = array('File',
                'FILE',
                __('Allows importing data from and exporting data into files.')
            );
    }
    return $data_privTable;
}

/**
 * Get structure privilege table as an array
 *
 * @param string $table the table
 * @param array  $row   first row from result or boolean false
 *
 * @return string structure privilege table
 */
function PMA_getStructurePrivilegeTable($table, $row)
{
    $structure_privTable = array(
        array('Create',
            'CREATE',
            ($table == '*'
                ? __('Allows creating new databases and tables.')
                : __('Allows creating new tables.')
            )
        ),
        array('Alter',
            'ALTER',
            __('Allows altering the structure of existing tables.')
        ),
        array('Index', 'INDEX', __('Allows creating and dropping indexes.')),
        array('Drop',
            'DROP',
            ($table == '*'
                ? __('Allows dropping databases and tables.')
                : __('Allows dropping tables.')
            )
        ),
        array('Create_tmp_table',
            'CREATE TEMPORARY TABLES',
            __('Allows creating temporary tables.')
        ),
        array('Show_view',
            'SHOW VIEW',
            __('Allows performing SHOW CREATE VIEW queries.')
        ),
        array('Create_routine',
            'CREATE ROUTINE',
            __('Allows creating stored routines.')
        ),
        array('Alter_routine',
            'ALTER ROUTINE',
            __('Allows altering and dropping stored routines.')
        ),
        array('Execute', 'EXECUTE', __('Allows executing stored routines.')),
    );
    // this one is for a db-specific priv: Create_view_priv
    if (isset($row['Create_view_priv'])) {
        $structure_privTable[] = array('Create_view',
            'CREATE VIEW',
            __('Allows creating new views.')
        );
    }
    // this one is for a table-specific priv: Create View_priv
    if (isset($row['Create View_priv'])) {
        $structure_privTable[] = array('Create View',
            'CREATE VIEW',
            __('Allows creating new views.')
        );
    }
    if (isset($row['Event_priv'])) {
        // MySQL 5.1.6
        $structure_privTable[] = array('Event',
            'EVENT',
            __('Allows to set up events for the event scheduler.')
        );
        $structure_privTable[] = array('Trigger',
            'TRIGGER',
            __('Allows creating and dropping triggers.')
        );
    }
    return $structure_privTable;
}

/**
 * Get administration privilege table as an array
 *
 * @param string $db the table
 *
 * @return string administration privilege table
 */
function PMA_getAdministrationPrivilegeTable($db)
{
    $adminPrivTable = array(
        array('Grant',
            'GRANT',
            __(
                'Allows adding users and privileges '
                . 'without reloading the privilege tables.'
            )
        ),
    );
    if ($db == '*') {
        $adminPrivTable[] = array('Super',
            'SUPER',
            __(
                'Allows connecting, even if maximum number '
                . 'of connections is reached; required for '
                . 'most administrative operations like '
                . 'setting global variables or killing threads of other users.'
            )
        );
        $adminPrivTable[] = array('Process',
            'PROCESS',
            __('Allows viewing processes of all users.')
        );
        $adminPrivTable[] = array('Reload',
            'RELOAD',
            __('Allows reloading server settings and flushing the server\'s caches.')
        );
        $adminPrivTable[] = array('Shutdown',
            'SHUTDOWN',
            __('Allows shutting down the server.')
        );
        $adminPrivTable[] = array('Show_db',
            'SHOW DATABASES',
            __('Gives access to the complete list of databases.')
        );
    }
    $adminPrivTable[] = array('Lock_tables',
        'LOCK TABLES',
        __('Allows locking tables for the current thread.')
    );
    $adminPrivTable[] = array('References',
        'REFERENCES',
        __('Has no effect in this MySQL version.')
    );
    if ($db == '*') {
        $adminPrivTable[] = array('Repl_client',
            'REPLICATION CLIENT',
            __('Allows the user to ask where the slaves / masters are.')
        );
        $adminPrivTable[] = array('Repl_slave',
            'REPLICATION SLAVE',
            __('Needed for the replication slaves.')
        );
        $adminPrivTable[] = array('Create_user',
            'CREATE USER',
            __('Allows creating, dropping and renaming user accounts.')
        );
    }
    return $adminPrivTable;
}

/**
 * Get HTML snippet for global privileges table with check boxes
 *
 * @param array $privTable       privileges table array
 * @param array $privTable_names names of the privilege tables
 *                               (Data, Structure, Administration)
 * @param array $row             first row from result or boolean false
 *
 * @return string $html_output
 */
function PMA_getHtmlForGlobalPrivTableWithCheckboxes(
    $privTable, $privTable_names, $row
) {
    $html_output = '';
    foreach ($privTable as $i => $table) {
        $html_output .= '<fieldset>' . "\n"
            . '<legend>' . $privTable_names[$i] . '</legend>' . "\n";
        foreach ($table as $priv) {
            $html_output .= '<div class="item">' . "\n"
                . '<input type="checkbox" class="checkall"'
                . ' name="' . $priv[0] . '_priv" '
                . 'id="checkbox_' . $priv[0] . '_priv"'
                . ' value="Y" title="' . $priv[2] . '"'
                . (($row[$priv[0] . '_priv'] == 'Y')
                    ?  ' checked="checked"'
                    : ''
                )
                . '/>' . "\n"
                . '<label for="checkbox_' . $priv[0] . '_priv">'
                . '<code><dfn title="' . $priv[2] . '">'
                . $priv[1] . '</dfn></code></label>' . "\n"
                . '</div>' . "\n";
        }
        $html_output .= '</fieldset>' . "\n";
    }
    return $html_output;
}

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
 * @return string $html_output  a HTML snippet
 */
function PMA_getHtmlForLoginInformationFields($mode = 'new')
{
    list($username_length, $hostname_length) = PMA_getUsernameAndHostnameLength();

    if (isset($GLOBALS['username']) && strlen($GLOBALS['username']) === 0) {
        $GLOBALS['pred_username'] = 'any';
    }
    $html_output = '<fieldset id="fieldset_add_user_login">' . "\n"
        . '<legend>' . __('Login Information') . '</legend>' . "\n"
        . '<div class="item">' . "\n"
        . '<label for="select_pred_username">' . "\n"
        . '    ' . __('User name:') . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n";

    $html_output .= '<select name="pred_username" id="select_pred_username" '
        . 'title="' . __('User name') . '"' . "\n";


    $html_output .= '        onchange="'
        . 'if (this.value == \'any\') {'
        . '    username.value = \'\'; '
        . '    user_exists_warning.style.display = \'none\'; '
        . '    username.required = false; '
        . '} else if (this.value == \'userdefined\') {'
        . '    username.focus(); username.select(); '
        . '    username.required = true; '
        . '}">' . "\n";

    $html_output .= '<option value="any"'
        . ((isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] == 'any')
            ? ' selected="selected"'
            : '') . '>'
        . __('Any user')
        . '</option>' . "\n";

    $html_output .= '<option value="userdefined"'
        . ((! isset($GLOBALS['pred_username'])
                || $GLOBALS['pred_username'] == 'userdefined'
            )
            ? ' selected="selected"'
            : '') . '>'
        . __('Use text field')
        . ':</option>' . "\n";

    $html_output .= '</select>' . "\n"
        . '</span>' . "\n";

    $html_output .= '<input type="text" name="username" class="autofocus"'
        . ' maxlength="' . $username_length . '" title="' . __('User name') . '"'
        . (empty($GLOBALS['username'])
           ? ''
           : ' value="' . htmlspecialchars(
               isset($GLOBALS['new_username'])
               ? $GLOBALS['new_username']
               : $GLOBALS['username']
           ) . '"'
        )
        . ' onchange="pred_username.value = \'userdefined\'; this.required = true;" '
        . ((! isset($GLOBALS['pred_username'])
                || $GLOBALS['pred_username'] == 'userdefined'
            )
            ? 'required="required"'
            : '') . ' />' . "\n";

    $html_output .= '<div id="user_exists_warning"'
        . ' name="user_exists_warning" style="display:none;">'
        . PMA_Message::notice(
            __(
                'An account already exists with the same username '
                . 'but possibly a different hostname.'
            )
        )->getDisplay()
        . '</div>';
    $html_output .= '</div>';

    $html_output .= '<div class="item">' . "\n"
        . '<label for="select_pred_hostname">' . "\n"
        . '    ' . __('Host:') . "\n"
        . '</label>' . "\n";

    $html_output .= '<span class="options">' . "\n"
        . '    <select name="pred_hostname" id="select_pred_hostname" '
        . 'title="' . __('Host') . '"' . "\n";
    $_current_user = $GLOBALS['dbi']->fetchValue('SELECT USER();');
    if (! empty($_current_user)) {
        $thishost = str_replace(
            "'",
            '',
            substr($_current_user, (strrpos($_current_user, '@') + 1))
        );
        if ($thishost == 'localhost' || $thishost == '127.0.0.1') {
            unset($thishost);
        }
    }
    $html_output .= '    onchange="'
        . 'if (this.value == \'any\') { '
        . '     hostname.value = \'%\'; '
        . '} else if (this.value == \'localhost\') { '
        . '    hostname.value = \'localhost\'; '
        . '} '
        . (empty($thishost)
            ? ''
            : 'else if (this.value == \'thishost\') { '
            . '    hostname.value = \'' . addslashes(htmlspecialchars($thishost))
            . '\'; '
            . '} '
        )
        . 'else if (this.value == \'hosttable\') { '
        . '    hostname.value = \'\'; '
        . '    hostname.required = false; '
        . '} else if (this.value == \'userdefined\') {'
        . '    hostname.focus(); hostname.select(); '
        . '    hostname.required = true; '
        . '}">' . "\n";
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
    $html_output .=  '<option value="any"'
        . ((isset($GLOBALS['pred_hostname'])
                && $GLOBALS['pred_hostname'] == 'any'
            )
            ? ' selected="selected"'
            : '') . '>'
        . __('Any host')
        . '</option>' . "\n"
        . '<option value="localhost"'
        . ((isset($GLOBALS['pred_hostname'])
                && $GLOBALS['pred_hostname'] == 'localhost'
            )
            ? ' selected="selected"'
            : '') . '>'
        . __('Local')
        . '</option>' . "\n";
    if (! empty($thishost)) {
        $html_output .= '<option value="thishost"'
            . ((isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] == 'thishost'
                )
                ? ' selected="selected"'
                : '') . '>'
            . __('This Host')
            . '</option>' . "\n";
    }
    unset($thishost);
    $html_output .= '<option value="hosttable"'
        . ((isset($GLOBALS['pred_hostname'])
                && $GLOBALS['pred_hostname'] == 'hosttable'
            )
            ? ' selected="selected"'
            : '') . '>'
        . __('Use Host Table')
        . '</option>' . "\n";

    $html_output .= '<option value="userdefined"'
        . ((isset($GLOBALS['pred_hostname'])
                && $GLOBALS['pred_hostname'] == 'userdefined'
            )
            ? ' selected="selected"'
            : '') . '>'
        . __('Use text field:') . '</option>' . "\n"
        . '</select>' . "\n"
        . '</span>' . "\n";

    $html_output .= '<input type="text" name="hostname" maxlength="'
        . $hostname_length . '" value="'
        // use default value of '%' to match with the default 'Any host'
        . htmlspecialchars(isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '%')
        . '" title="' . __('Host')
        . '" onchange="pred_hostname.value = \'userdefined\'; this.required = true;" '
        . ((isset($GLOBALS['pred_hostname'])
                && $GLOBALS['pred_hostname'] == 'userdefined'
            )
            ? 'required="required"'
            : '')
        . ' />' . "\n"
        . PMA_Util::showHint(
            __(
                'When Host table is used, this field is ignored '
                . 'and values stored in Host table are used instead.'
            )
        )
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="select_pred_password">' . "\n"
        . '    ' . __('Password:') . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '<select name="pred_password" id="select_pred_password" title="'
        . __('Password') . '"' . "\n";

    $html_output .= '            onchange="'
        . 'if (this.value == \'none\') { '
        . '    pma_pw.value = \'\'; pma_pw2.value = \'\'; '
        . '    pma_pw.required = false; pma_pw2.required = false; '
        . '} else if (this.value == \'userdefined\') { '
        . '    pma_pw.focus(); pma_pw.select(); '
        . '    pma_pw.required = true; pma_pw2.required = true; '
        . '} else { '
        . '    pma_pw.required = false; pma_pw2.required = false; '
        . '}">' . "\n"
        . ($mode == 'change' ? '<option value="keep" selected="selected">'
            . __('Do not change the password')
            . '</option>' . "\n" : '')
        . '<option value="none"';

    if (isset($GLOBALS['username']) && $mode != 'change') {
        $html_output .= '  selected="selected"';
    }
    $html_output .= '>' . __('No Password') . '</option>' . "\n"
        . '<option value="userdefined"'
        . (isset($GLOBALS['username']) ? '' : ' selected="selected"') . '>'
        . __('Use text field')
        . ':</option>' . "\n"
        . '</select>' . "\n"
        . '</span>' . "\n"
        . '<input type="password" id="text_pma_pw" name="pma_pw" '
        . 'title="' . __('Password') . '" '
        . 'onchange="pred_password.value = \'userdefined\'; this.required = true; pma_pw2.required = true;" '
        . (isset($GLOBALS['username']) ? '' : 'required="required"')
        . '/>' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item" '
        . 'id="div_element_before_generate_password">' . "\n"
        . '<label for="text_pma_pw2">' . "\n"
        . '    ' . __('Re-type:') . "\n"
        . '</label>' . "\n"
        . '<span class="options">&nbsp;</span>' . "\n"
        . '<input type="password" name="pma_pw2" id="text_pma_pw2" '
        . 'title="' . __('Re-type') . '" '
        . 'onchange="pred_password.value = \'userdefined\'; this.required = true; pma_pw.required = true;" '
        . (isset($GLOBALS['username']) ? '' : 'required="required"')
        . '/>' . "\n"
        . '</div>' . "\n"
       // Generate password added here via jQuery
       . '</fieldset>' . "\n";

    return $html_output;
} // end of the 'PMA_displayUserAndHostFields()' function

/**
 * Get username and hostname length
 *
 * @return array username length and hostname length
 */
function PMA_getUsernameAndHostnameLength()
{
    /* Fallback values */
    $username_length = 16;
    $hostname_length = 41;

    /* Try to get real lengths from the database */
    $fields_info = $GLOBALS['dbi']->fetchResult(
        'SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH '
        . 'FROM information_schema.columns '
        . "WHERE table_schema = 'mysql' AND table_name = 'user' "
        . "AND COLUMN_NAME IN ('User', 'Host')"
    );
    foreach ($fields_info as $val) {
        if ($val['COLUMN_NAME'] == 'User') {
            $username_length = $val['CHARACTER_MAXIMUM_LENGTH'];
        } elseif ($val['COLUMN_NAME'] == 'Host') {
            $hostname_length = $val['CHARACTER_MAXIMUM_LENGTH'];
        }
    }
    return array($username_length, $hostname_length);
}

/**
 * Returns all the grants for a certain user on a certain host
 * Used in the export privileges for all users section
 *
 * @param string $user User name
 * @param string $host Host name
 *
 * @return string containing all the grants text
 */
function PMA_getGrants($user, $host)
{
    $grants = $GLOBALS['dbi']->fetchResult(
        "SHOW GRANTS FOR '"
        . PMA_Util::sqlAddSlashes($user) . "'@'"
        . PMA_Util::sqlAddSlashes($host) . "'"
    );
    $response = '';
    foreach ($grants as $one_grant) {
        $response .= $one_grant . ";\n\n";
    }
    return $response;
} // end of the 'PMA_getGrants()' function

/**
 * Update password and get message for password updating
 *
 * @param string $err_url  error url
 * @param string $username username
 * @param string $hostname hostname
 *
 * @return string $message  success or error message after updating password
 */
function PMA_updatePassword($err_url, $username, $hostname)
{
    // similar logic in user_password.php
    $message = '';

    if (empty($_REQUEST['nopass'])
        && isset($_POST['pma_pw'])
        && isset($_POST['pma_pw2'])
    ) {
        if ($_POST['pma_pw'] != $_POST['pma_pw2']) {
            $message = PMA_Message::error(__('The passwords aren\'t the same!'));
        } elseif (empty($_POST['pma_pw']) || empty($_POST['pma_pw2'])) {
            $message = PMA_Message::error(__('The password is empty!'));
        }
    }

    // here $nopass could be == 1
    if (empty($message)) {

        $hashing_function
            = (! empty($_REQUEST['pw_hash']) && $_REQUEST['pw_hash'] == 'old'
                ? 'OLD_'
                : ''
            )
            . 'PASSWORD';

        // in $sql_query which will be displayed, hide the password
        $sql_query        = 'SET PASSWORD FOR \''
            . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\' = '
            . (($_POST['pma_pw'] == '')
                ? '\'\''
                : $hashing_function . '(\''
                . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

        $local_query      = 'SET PASSWORD FOR \''
            . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\' = '
            . (($_POST['pma_pw'] == '') ? '\'\'' : $hashing_function
            . '(\'' . PMA_Util::sqlAddSlashes($_POST['pma_pw']) . '\')');

        $GLOBALS['dbi']->tryQuery($local_query)
            or PMA_Util::mysqlDie(
                $GLOBALS['dbi']->getError(), $sql_query, false, $err_url
            );
        $message = PMA_Message::success(
            __('The password for %s was changed successfully.')
        );
        $message->addParam(
            '\'' . htmlspecialchars($username)
            . '\'@\'' . htmlspecialchars($hostname) . '\''
        );
    }
    return $message;
}

/**
 * Revokes privileges and get message and SQL query for privileges revokes
 *
 * @param string $db_and_table wildcard Escaped database+table specification
 * @param string $dbname       database name
 * @param string $tablename    table name
 * @param string $username     username
 * @param string $hostname     host name
 *
 * @return array ($message, $sql_query)
 */
function PMA_getMessageAndSqlQueryForPrivilegesRevoke($db_and_table, $dbname,
    $tablename, $username, $hostname
) {
    $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);

    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \''
        . PMA_Util::sqlAddSlashes($username) . '\'@\''
        . PMA_Util::sqlAddSlashes($hostname) . '\';';

    $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table
        . ' FROM \'' . PMA_Util::sqlAddSlashes($username) . '\'@\''
        . PMA_Util::sqlAddSlashes($hostname) . '\';';

    $GLOBALS['dbi']->query($sql_query0);
    if (! $GLOBALS['dbi']->tryQuery($sql_query1)) {
        // this one may fail, too...
        $sql_query1 = '';
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1;
    $message = PMA_Message::success(
        __('You have revoked the privileges for %s.')
    );
    $message->addParam(
        '\'' . htmlspecialchars($username)
        . '\'@\'' . htmlspecialchars($hostname) . '\''
    );

    return array($message, $sql_query);
}

/**
 * Get a WITH clause for 'update privileges' and 'add user'
 *
 * @return string $sql_query
 */
function PMA_getWithClauseForAddUserAndUpdatePrivs()
{
    $sql_query = '';
    if (isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y') {
        $sql_query .= ' GRANT OPTION';
    }
    if (isset($_POST['max_questions'])) {
        $max_questions = max(0, (int)$_POST['max_questions']);
        $sql_query .= ' MAX_QUERIES_PER_HOUR ' . $max_questions;
    }
    if (isset($_POST['max_connections'])) {
        $max_connections = max(0, (int)$_POST['max_connections']);
        $sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . $max_connections;
    }
    if (isset($_POST['max_updates'])) {
        $max_updates = max(0, (int)$_POST['max_updates']);
        $sql_query .= ' MAX_UPDATES_PER_HOUR ' . $max_updates;
    }
    if (isset($_POST['max_user_connections'])) {
        $max_user_connections = max(0, (int)$_POST['max_user_connections']);
        $sql_query .= ' MAX_USER_CONNECTIONS ' . $max_user_connections;
    }
    return ((!empty($sql_query)) ? 'WITH' . $sql_query : '');
}

/**
 * Get HTML for addUsersForm, This function call if isset($_REQUEST['adduser'])
 *
 * @param string $dbname database name
 *
 * @return string HTML for addUserForm
 */
function PMA_getHtmlForAddUser($dbname)
{
    $html_output = '<h2>' . "\n"
       . PMA_Util::getIcon('b_usradd.png') . __('Add user') . "\n"
       . '</h2>' . "\n"
       . '<form name="usersForm" class="ajax" id="addUsersForm"'
       . ' action="server_privileges.php" method="post" autocomplete="off" >' . "\n"
       . PMA_URL_getHiddenInputs('', '')
       . PMA_getHtmlForLoginInformationFields('new');

    $html_output .= '<fieldset id="fieldset_add_user_database">' . "\n"
        . '<legend>' . __('Database for user') . '</legend>' . "\n";

    $html_output .= PMA_Util::getCheckbox(
        'createdb-1',
        __('Create database with same name and grant all privileges.'),
        false, false
    );
    $html_output .= '<br />' . "\n";
    $html_output .= PMA_Util::getCheckbox(
        'createdb-2',
        __('Grant all privileges on wildcard name (username\\_%).'),
        false, false
    );
    $html_output .= '<br />' . "\n";

    if (! empty($dbname) ) {
        $html_output .= PMA_Util::getCheckbox(
            'createdb-3',
            sprintf(
                __('Grant all privileges on database "%s".'),
                htmlspecialchars($dbname)
            ),
            true,
            false
        );
        $html_output .= '<input type="hidden" name="dbname" value="'
            . htmlspecialchars($dbname) . '" />' . "\n";
        $html_output .= '<br />' . "\n";
    }

    $html_output .= '</fieldset>' . "\n";
    $html_output .= PMA_getHtmlToDisplayPrivilegesTable('*', '*', false);
    $html_output .= '<fieldset id="fieldset_add_user_footer" class="tblFooters">'
        . "\n"
        . '<input type="submit" name="adduser_submit" '
        . 'value="' . __('Go') . '" />' . "\n"
        . '</fieldset>' . "\n"
        . '</form>' . "\n";

    return $html_output;
}

/**
 * Get the list of privileges and list of compared privileges as strings
 * and return a array that contains both strings
 *
 * @return array $list_of_privileges, $list_of_compared_privileges
 */
function PMA_getListOfPrivilegesAndComparedPrivileges()
{
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

    $listOfComparedPrivs
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
        $listOfComparedPrivs .=
            ' AND `Event_priv` = \'N\''
            . ' AND `Trigger_priv` = \'N\'';
    }
    return array($list_of_privileges, $listOfComparedPrivs);
}

/**
 * Get the HTML for user form and check the privileges for a particular database.
 *
 * @param string $db database name
 *
 * @return string $html_output
 */
function PMA_getHtmlForSpecificDbPrivileges($db)
{
    // check the privileges for a particular database.
    $html_output = '<form id="usersForm" action="server_privileges.php">'
        . '<fieldset>' . "\n";
    $html_output .= '<legend>' . "\n"
        . PMA_Util::getIcon('b_usrcheck.png')
        . '    '
        . sprintf(
            __('Users having access to "%s"'),
            '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?'
            . PMA_URL_getCommon($db) . '">'
            .  htmlspecialchars($db)
            . '</a>'
        )
        . "\n"
        . '</legend>' . "\n";

    $html_output .= '<table id="dbspecificuserrights" class="data">' . "\n"
        . '<thead>' . "\n"
        . '<tr><th>' . __('User') . '</th>' . "\n"
        . '<th>' . __('Host') . '</th>' . "\n"
        . '<th>' . __('Type') . '</th>' . "\n"
        . '<th>' . __('Privileges') . '</th>' . "\n"
        . '<th>' . __('Grant') . '</th>' . "\n"
        . '<th>' . __('Action') . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n";
    // now, we build the table...
    list($listOfPrivs, $listOfComparedPrivs)
        = PMA_getListOfPrivilegesAndComparedPrivileges();

    $sql_query = '(SELECT ' . $listOfPrivs . ', `Db`, \'d\' AS `Type`'
        . ' FROM `mysql`.`db`'
        . ' WHERE \'' . PMA_Util::sqlAddSlashes($db)
        . "'"
        . ' LIKE `Db`'
        . ' AND NOT (' . $listOfComparedPrivs . ')) '
        . 'UNION '
        . '(SELECT ' . $listOfPrivs . ', \'*\' AS `Db`, \'g\' AS `Type`'
        . ' FROM `mysql`.`user` '
        . ' WHERE NOT (' . $listOfComparedPrivs . ')) '
        . ' ORDER BY `User` ASC,'
        . '  `Host` ASC,'
        . '  `Db` ASC;';
    $res = $GLOBALS['dbi']->query($sql_query);

    $privMap = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $user = $row['User'];
        $host = $row['Host'];
        if (! isset($privMap[$user])) {
            $privMap[$user] = array();
        }
        if (! isset($privMap[$user][$host])) {
            $privMap[$user][$host] = array();
        }
        $privMap[$user][$host][] = $row;
    }

    $html_output .= PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
    $html_output .= '</table>'
        . '</fieldset>'
        . '</form>' . "\n";

    if ($GLOBALS['is_ajax_request'] == true
        && empty($_REQUEST['ajax_page_request'])
    ) {
        $message = PMA_Message::success(__('User has been added.'));
        $response = PMA_Response::getInstance();
        $response->addJSON('message', $message);
        $response->addJSON('user_form', $html_output);
        exit;
    } else {
        // Offer to create a new user for the current database
        $html_output .= '<fieldset id="fieldset_add_user">' . "\n"
           . '<legend>' . _pgettext('Create new user', 'New') . '</legend>' . "\n";

        $html_output .= '<a href="server_privileges.php'
            . PMA_URL_getCommon(
                array(
                    'adduser' => 1,
                    'dbname' => $db,
                )
            )
            . '" rel="'
            . PMA_URL_getCommon(array('checkprivsdb' => $db))
            . '" class="ajax" name="db_specific">' . "\n"
            . PMA_Util::getIcon('b_usradd.png')
            . '        ' . __('Add user') . '</a>' . "\n";

        $html_output .= '</fieldset>' . "\n";
    }
    return $html_output;
}

/**
 * Get the HTML for user form and check the privileges for a particular table.
 *
 * @param string $db    database name
 * @param string $table table name
 *
 * @return string $html_output
 */
function PMA_getHtmlForSpecificTablePrivileges($db, $table)
{
    // check the privileges for a particular table.
    $html_output  = '<form id="usersForm" action="server_privileges.php">';
    $html_output .= '<fieldset>';
    $html_output .= '<legend>'
        . PMA_Util::getIcon('b_usrcheck.png')
        . sprintf(
            __('Users having access to "%s"'),
            '<a href="' . $GLOBALS['cfg']['DefaultTabTable']
            . PMA_URL_getCommon(
                array(
                    'db' => $db,
                    'table' => $table,
                )
            ) . '">'
            .  htmlspecialchars($db) . '.' . htmlspecialchars($table)
            . '</a>'
        )
        . '</legend>';

    $html_output .= '<table id="tablespecificuserrights" class="data">';
    $html_output .= '<thead>'
        . '<tr><th>' . __('User') . '</th>'
        . '<th>' . __('Host') . '</th>'
        . '<th>' . __('Type') . '</th>'
        . '<th>' . __('Privileges') . '</th>'
        . '<th>' . __('Grant') . '</th>'
        . '<th>' . __('Action') . '</th>'
        . '</tr>'
        . '</thead>';

    list($listOfPrivs, $listOfComparedPrivs)
        = PMA_getListOfPrivilegesAndComparedPrivileges();
    $sql_query
        = "("
        . " SELECT " . $listOfPrivs . ", '*' AS `Db`, 'g' AS `Type`"
        . " FROM `mysql`.`user`"
        . " WHERE NOT (" . $listOfComparedPrivs . ")"
        . ")"
        . " UNION "
        . "("
        . " SELECT " . $listOfPrivs . ", `Db`, 'd' AS `Type`"
        . " FROM `mysql`.`db`"
        . " WHERE '" . PMA_Util::sqlAddSlashes($db) . "' LIKE `Db`"
        . "     AND NOT (" . $listOfComparedPrivs . ")"
        . ")"
        . " ORDER BY `User` ASC, `Host` ASC, `Db` ASC;";
    $res = $GLOBALS['dbi']->query($sql_query);

    $privMap = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $user = $row['User'];
        $host = $row['Host'];
        if (! isset($privMap[$user])) {
            $privMap[$user] = array();
        }
        if (! isset($privMap[$user][$host])) {
            $privMap[$user][$host] = array();
        }
        $privMap[$user][$host][] = $row;
    }

    $sql_query = "SELECT `User`, `Host`, `Db`,"
        . " 't' AS `Type`, `Table_name`, `Table_priv`"
        . " FROM `mysql`.`tables_priv`"
        . " WHERE '" . PMA_Util::sqlAddSlashes($db) . "' LIKE `Db`"
        . "     AND '" . PMA_Util::sqlAddSlashes($table) . "' LIKE `Table_name`"
        . "     AND NOT (`Table_priv` = '' AND Column_priv = '')"
        . " ORDER BY `User` ASC, `Host` ASC, `Db` ASC, `Table_priv` ASC;";
    $res = $GLOBALS['dbi']->query($sql_query);

    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $user = $row['User'];
        $host = $row['Host'];
        if (! isset($privMap[$user])) {
            $privMap[$user] = array();
        }
        if (! isset($privMap[$user][$host])) {
            $privMap[$user][$host] = array();
        }
        $privMap[$user][$host][] = $row;
    }

    $html_output .= PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
    $html_output .= '</table>';
    $html_output .= '</fieldset>';
    $html_output .= '</form>';

    // Offer to create a new user for the current database
    $html_output .= '<fieldset id="fieldset_add_user">'
        . '<legend>' . _pgettext('Create new user', 'New') . '</legend>';
    $html_output .= '<a href="server_privileges.php'
        . PMA_URL_getCommon(
            array(
                'adduser' => 1,
                'dbname' => $db,
                'tablename' => $table
            )
        )
        . '" rel="' . PMA_URL_getCommon(
            array('checkprivsdb' => $db, 'checkprivstable' => $table)
        )
        . '" class="ajax" name="table_specific">'
        . PMA_Util::getIcon('b_usradd.png') . __('Add user') . '</a>';

    $html_output .= '</fieldset>';
    return $html_output;
}

/**
 * Get HTML snippet for table body of specific database or table privileges
 *
 * @param array   $privMap priviledge map
 * @param boolean $db      database
 *
 * @return string $html_output
 */
function PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db)
{
    $html_output = '<tbody>';
    $odd_row = true;
    if (empty($privMap)) {
        $html_output .= '<tr class="odd">'
            . '<td colspan="6">'
            . __('No user found.')
            . '</td>'
            . '</tr>'
            . '</tbody>';
        return $html_output;
    }

    foreach ($privMap as $current_user => $val) {
        foreach ($val as $current_host => $current_privileges) {
            $nbPrivileges = count($current_privileges);
            $html_output .= '<tr class="noclick '
                . ($odd_row ? 'odd' : 'even') . '">';

            // user
            $html_output .= '<td';
            if ($nbPrivileges > 1) {
                $html_output .= ' rowspan="' . $nbPrivileges . '"';
            }
            $html_output .= '>';
            if (empty($current_user)) {
                $html_output .= '<span style="color: #FF0000">'
                    . __('Any') . '</span>';
            } else {
                $html_output .= htmlspecialchars($current_user);
            }
            $html_output .= '</td>';

            // host
            $html_output .= '<td';
            if ($nbPrivileges > 1) {
                $html_output .= ' rowspan="' . $nbPrivileges . '"';
            }
            $html_output .= '>';
            $html_output .= htmlspecialchars($current_host);
            $html_output .= '</td>';

            $html_output .= PMA_getHtmlListOfPrivs(
                $db, $current_privileges, $current_user,
                $current_host, $odd_row
            );

            $odd_row = ! $odd_row;
        }
    }
    $html_output .= '</tbody>';

    return $html_output;
}

/**
 * Get HTML to display privileges
 *
 * @param string  $db                 Database name
 * @param array   $current_privileges List of privileges
 * @param string  $current_user       Current user
 * @param string  $current_host       Current host
 * @param boolean $odd_row            Current row is odd
 *
 * @return string HTML to display privileges
 */
function PMA_getHtmlListOfPrivs(
    $db, $current_privileges, $current_user,
    $current_host, $odd_row
) {
    $nbPrivileges = count($current_privileges);
    $html_output = null;
    for ($i = 0; $i < $nbPrivileges; $i++) {
        $current = $current_privileges[$i];

        // type
        $html_output .= '<td>';
        if ($current['Type'] == 'g') {
            $html_output .= __('global');
        } elseif ($current['Type'] == 'd') {
            if ($current['Db'] == PMA_Util::escapeMysqlWildcards($db)) {
                $html_output .= __('database-specific');
            } else {
                $html_output .= __('wildcard') . ': '
                    . '<code>'
                    . htmlspecialchars($current['Db'])
                    . '</code>';
            }
        } elseif ($current['Type'] == 't') {
            $html_output .= __('table-specific');
        }
        $html_output .= '</td>';

        // privileges
        $html_output .= '<td>';
        if (isset($current['Table_name'])) {
            $privList = explode(',', $current['Table_priv']);
            $privs = array();
            $grantsArr = PMA_getTableGrantsArray();
            foreach ($grantsArr as $grant) {
                $privs[$grant[0]] = 'N';
                foreach ($privList as $priv) {
                    if ($grant[0] == $priv) {
                        $privs[$grant[0]] = 'Y';
                    }
                }
            }
            $html_output .= '<code>'
                . join(
                    ',',
                    PMA_extractPrivInfo($privs, true, true)
                )
                . '</code>';
        } else {
            $html_output .= '<code>'
                . join(
                    ',',
                    PMA_extractPrivInfo($current, true, false)
                )
                . '</code>';
        }
        $html_output .= '</td>';

        // grant
        $html_output .= '<td>';
        $containsGrant = false;
        if (isset($current['Table_name'])) {
            $privList = explode(',', $current['Table_priv']);
            foreach ($privList as $priv) {
                if ($priv == 'Grant') {
                    $containsGrant = true;
                }
            }
        } else {
            $containsGrant = $current['Grant_priv'] == 'Y';
        }
        $html_output .= ($containsGrant ? __('Yes') : __('No'));
        $html_output .= '</td>';

        // action
        $html_output .= '<td>';
        $specific_db = (isset($current['Db']) && $current['Db'] != '*')
            ? $current['Db'] : '';
        $specific_table = (isset($current['Table_name'])
            && $current['Table_name'] != '*')
            ? $current['Table_name'] : '';
        $html_output .= PMA_getUserEditLink(
            $current_user,
            $current_host,
            $specific_db,
            $specific_table
        );
        $html_output .= '</td>';

        $html_output .= '</tr>';
        if (($i + 1) < $nbPrivileges) {
            $html_output .= '<tr class="noclick '
                . ($odd_row ? 'odd' : 'even') . '">';
        }
    }
    return $html_output;
}

/**
 * Returns edit link for a user.
 *
 * @param string $username  User name
 * @param string $hostname  Host name
 * @param string $dbname    Database name
 * @param string $tablename Table name
 *
 * @return string HTML code with link
 */
function PMA_getUserEditLink($username, $hostname, $dbname = '', $tablename = '')
{
    return '<a class="edit_user_anchor ajax"'
        . ' href="server_privileges.php'
        . PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
            )
        )
        . '">'
        . PMA_Util::getIcon('b_usredit.png', __('Edit Privileges'))
        . '</a>';
}

/**
 * Returns revoke link for a user.
 *
 * @param string $username  User name
 * @param string $hostname  Host name
 * @param string $dbname    Database name
 * @param string $tablename Table name
 *
 * @return string HTML code with link
 */
function PMA_getUserRevokeLink($username, $hostname, $dbname = '', $tablename = '')
{
    return '<a  href="server_privileges.php'
        . PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'revokeall' => 1,
            )
        )
        . '">'
        . PMA_Util::getIcon('b_usrdrop.png', __('Revoke'))
        . '</a>';
}

/**
 * Returns export link for a user.
 *
 * @param string $username User name
 * @param string $hostname Host name
 * @param string $initial  Initial value
 *
 * @return HTML code with link
 */
function PMA_getUserExportLink($username, $hostname, $initial = '')
{
    return '<a class="export_user_anchor ajax"'
        . ' href="server_privileges.php'
        . PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'initial' => $initial,
                'export' => 1,
            )
        )
        . '">'
        . PMA_Util::getIcon('b_tblexport.png', __('Export'))
        . '</a>';
}

/**
 * Returns user group edit link
 *
 * @param string $username User name
 *
 * @return HTML code with link
 */
function PMA_getUserGroupEditLink($username)
{
     return '<a class="edit_user_group_anchor ajax"'
        . ' href="server_privileges.php'
        . PMA_URL_getCommon(array('username' => $username))
        . '">'
        . PMA_Util::getIcon('b_usrlist.png', __('Edit user group'))
        . '</a>';
}

/**
 * Returns number of defined user groups
 *
 * @return integer $user_group_count
 */
function PMA_getUserGroupCount()
{
    $user_group_table = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
        . '.' . PMA_Util::backquote($GLOBALS['cfg']['Server']['usergroups']);
    $sql_query = 'SELECT COUNT(*) FROM ' . $user_group_table;
    $user_group_count = $GLOBALS['dbi']->fetchValue(
        $sql_query, 0, 0, $GLOBALS['controllink']
    );

    return $user_group_count;
}

/**
 * This function return the extra data array for the ajax behavior
 *
 * @param string $password  password
 * @param string $sql_query sql query
 * @param string $hostname  hostname
 * @param string $username  username
 *
 * @return array $extra_data
 */
function PMA_getExtraDataForAjaxBehavior(
    $password, $sql_query, $hostname, $username
) {
    if (isset($GLOBALS['dbname'])) {
        //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
        if (preg_match('/(?<!\\\\)(?:_|%)/i', $GLOBALS['dbname'])) {
            $dbname_is_wildcard = true;
        } else {
            $dbname_is_wildcard = false;
        }
    }

    $user_group_count = 0;
    if ($GLOBALS['cfgRelation']['menuswork']) {
        $user_group_count = PMA_getUserGroupCount();
    }

    $extra_data = array();
    if (strlen($sql_query)) {
        $extra_data['sql_query']
            = PMA_Util::getMessage(null, $sql_query);
    }

    if (isset($_REQUEST['adduser_submit']) || isset($_REQUEST['change_copy'])) {
        /**
         * generate html on the fly for the new user that was just created.
         */
        $new_user_string = '<tr>' . "\n"
            . '<td> <input type="checkbox" name="selected_usr[]" '
            . 'id="checkbox_sel_users_"'
            . 'value="'
            . htmlspecialchars($username)
            . '&amp;#27;' . htmlspecialchars($hostname) . '" />'
            . '</td>' . "\n"
            . '<td><label for="checkbox_sel_users_">'
            . (empty($_REQUEST['username'])
                    ? '<span style="color: #FF0000">' . __('Any') . '</span>'
                    : htmlspecialchars($username) ) . '</label></td>' . "\n"
            . '<td>' . htmlspecialchars($hostname) . '</td>' . "\n";

        $new_user_string .= '<td>';

        if (! empty($password) || isset($_POST['pma_pw'])) {
            $new_user_string .= __('Yes');
        } else {
            $new_user_string .= '<span style="color: #FF0000">'
                . __('No')
            . '</span>';
        };

        $new_user_string .= '</td>' . "\n";
        $new_user_string .= '<td>'
            . '<code>' . join(', ', PMA_extractPrivInfo('', true)) . '</code>'
            . '</td>'; //Fill in privileges here

        // if $cfg['Servers'][$i]['users'] and $cfg['Servers'][$i]['usergroups'] are
        // enabled
        $cfgRelation = PMA_getRelationsParam();
        if (isset($cfgRelation['users']) && isset($cfgRelation['usergroups'])) {
            $new_user_string .= '<td class="usrGroup"></td>';
        }

        $new_user_string .= '<td>';

        if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')) {
            $new_user_string .= __('Yes');
        } else {
            $new_user_string .= __('No');
        }

        $new_user_string .='</td>';

        $new_user_string .= '<td>'
            . PMA_getUserEditLink($username, $hostname)
            . '</td>' . "\n";

        if (isset($cfgRelation['menuswork']) && $user_group_count > 0) {
            $new_user_string .= '<td>'
                . PMA_getUserGroupEditLink($username)
                . '</td>' . "\n";
        }

        $new_user_string .= '<td>'
            . PMA_getUserExportLink(
                $username,
                $hostname,
                isset($_GET['initial']) ? $_GET['initial'] : ''
            )
            . '</td>' . "\n";

        $new_user_string .= '</tr>';

        $extra_data['new_user_string'] = $new_user_string;

        /**
         * Generate the string for this alphabet's initial, to update the user
         * pagination
         */
        $new_user_initial = strtoupper(substr($username, 0, 1));
        $newUserInitialString = '<a href="server_privileges.php'
            . PMA_URL_getCommon(array('initial' => $new_user_initial)) . '">'
            . $new_user_initial . '</a>';
        $extra_data['new_user_initial'] = $new_user_initial;
        $extra_data['new_user_initial_string'] = $newUserInitialString;
    }

    if (isset($_POST['update_privs'])) {
        $extra_data['db_specific_privs'] = false;
        $extra_data['db_wildcard_privs'] = false;
        if (isset($dbname_is_wildcard)) {
            $extra_data['db_specific_privs'] = ! $dbname_is_wildcard;
            $extra_data['db_wildcard_privs'] = $dbname_is_wildcard;
        }
        $new_privileges = join(', ', PMA_extractPrivInfo('', true));

        $extra_data['new_privileges'] = $new_privileges;
    }

    if (isset($_REQUEST['validate_username'])) {
        $sql_query = "SELECT * FROM `mysql`.`user` WHERE `User` = '"
            . $_REQUEST['username'] . "';";
        $res = $GLOBALS['dbi']->query($sql_query);
        $row = $GLOBALS['dbi']->fetchRow($res);
        if (empty($row)) {
            $extra_data['user_exists'] = false;
        } else {
            $extra_data['user_exists'] = true;
        }
    }

    return $extra_data;
}

/**
 * Get the HTML snippet for change user login information
 *
 * @param string $username username
 * @param string $hostname host name
 *
 * @return string HTML snippet
 */
function PMA_getChangeLoginInformationHtmlForm($username, $hostname)
{
    $choices = array(
        '4' => __('… keep the old one.'),
        '1' => __('… delete the old one from the user tables.'),
        '2' => __(
            '… revoke all active privileges from '
            . 'the old one and delete it afterwards.'
        ),
        '3' => __(
            '… delete the old one from the user tables '
            . 'and reload the privileges afterwards.'
        )
    );

    $html_output = '<form action="server_privileges.php" '
        . 'method="post" class="copyUserForm ajax submenu-item">' . "\n"
        . PMA_URL_getHiddenInputs('', '')
        . '<input type="hidden" name="old_username" '
        . 'value="' . htmlspecialchars($username) . '" />' . "\n"
        . '<input type="hidden" name="old_hostname" '
        . 'value="' . htmlspecialchars($hostname) . '" />' . "\n"
        . '<fieldset id="fieldset_change_copy_user">' . "\n"
        . '<legend data-submenu-label="' . __('Login Information') . '">' . "\n"
        . __('Change Login Information / Copy User')
        . '</legend>' . "\n"
        . PMA_getHtmlForLoginInformationFields('change');

    $html_output .= '<fieldset id="fieldset_mode">' . "\n"
        . ' <legend>'
        . __('Create a new user with the same privileges and …')
        . '</legend>' . "\n";
    $html_output .= PMA_Util::getRadioFields(
        'mode', $choices, '4', true
    );
    $html_output .= '</fieldset>' . "\n"
       . '</fieldset>' . "\n";

    $html_output .= '<fieldset id="fieldset_change_copy_user_footer" '
        . 'class="tblFooters">' . "\n"
        . '<input type="submit" name="change_copy" '
        . 'value="' . __('Go') . '" />' . "\n"
        . '</fieldset>' . "\n"
        . '</form>' . "\n";

    return $html_output;
}

/**
 * Provide a line with links to the relevant database and table
 *
 * @param string $url_dbname url database name that urlencode() string
 * @param string $dbname     database name
 * @param string $tablename  table name
 *
 * @return string HTML snippet
 */
function PMA_getLinkToDbAndTable($url_dbname, $dbname, $tablename)
{
    $html_output = '[ ' . __('Database')
        . ' <a href="' . $GLOBALS['cfg']['DefaultTabDatabase']
        . PMA_URL_getCommon(
            array(
                'db' => $url_dbname,
                'reload' => 1
            )
        )
        . '">'
        . htmlspecialchars($dbname) . ': '
        . PMA_Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabDatabase']
        )
        . "</a> ]\n";

    if (strlen($tablename)) {
        $html_output .= ' [ ' . __('Table') . ' <a href="'
            . $GLOBALS['cfg']['DefaultTabTable']
            . PMA_URL_getCommon(
                array(
                    'db' => $url_dbname,
                    'table' => $tablename,
                    'reload' => 1,
                )
            )
            . '">' . htmlspecialchars($tablename) . ': '
            . PMA_Util::getTitleForTarget(
                $GLOBALS['cfg']['DefaultTabTable']
            )
            . "</a> ]\n";
    }
    return $html_output;
}

/**
 * no db name given, so we want all privs for the given user
 * db name was given, so we want all user specific rights for this db
 * So this function returns user rights as an array
 *
 * @param array  $tables              tables
 * @param string $user_host_condition a where clause that containd user's host
 *                                    condition
 * @param string $dbname              database name
 *
 * @return array $db_rights database rights
 */
function PMA_getUserSpecificRights($tables, $user_host_condition, $dbname)
{
    if (! strlen($dbname)) {
        $tables_to_search_for_users = array(
            'tables_priv', 'columns_priv',
        );
        $dbOrTableName = 'Db';
    } else {
        $user_host_condition .=
            ' AND `Db`'
            . ' LIKE \''
            . PMA_Util::sqlAddSlashes($dbname, true) . "'";
        $tables_to_search_for_users = array('columns_priv',);
        $dbOrTableName = 'Table_name';
    }

    $db_rights_sqls = array();
    foreach ($tables_to_search_for_users as $table_search_in) {
        if (in_array($table_search_in, $tables)) {
            $db_rights_sqls[] = '
                SELECT DISTINCT `' . $dbOrTableName . '`
                FROM `mysql`.' . PMA_Util::backquote($table_search_in)
               . $user_host_condition;
        }
    }

    $user_defaults = array(
        $dbOrTableName  => '',
        'Grant_priv'    => 'N',
        'privs'         => array('USAGE'),
        'Column_priv'   => true,
    );

    // for the rights
    $db_rights = array();

    $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
        . ' ORDER BY `' . $dbOrTableName . '` ASC';

    $db_rights_result = $GLOBALS['dbi']->query($db_rights_sql);

    while ($db_rights_row = $GLOBALS['dbi']->fetchAssoc($db_rights_result)) {
        $db_rights_row = array_merge($user_defaults, $db_rights_row);
        if (! strlen($dbname)) {
            // only Db names in the table `mysql`.`db` uses wildcards
            // as we are in the db specific rights display we want
            // all db names escaped, also from other sources
            $db_rights_row['Db'] = PMA_Util::escapeMysqlWildcards(
                $db_rights_row['Db']
            );
        }
        $db_rights[$db_rights_row[$dbOrTableName]] = $db_rights_row;
    }

    $GLOBALS['dbi']->freeResult($db_rights_result);

    if (! strlen($dbname)) {
        $sql_query = 'SELECT * FROM `mysql`.`db`'
            . $user_host_condition . ' ORDER BY `Db` ASC';
    } else {
        $sql_query = 'SELECT `Table_name`,'
            . ' `Table_priv`,'
            . ' IF(`Column_priv` = _latin1 \'\', 0, 1)'
            . ' AS \'Column_priv\''
            . ' FROM `mysql`.`tables_priv`'
            . $user_host_condition
            . ' ORDER BY `Table_name` ASC;';
    }

    $result = $GLOBALS['dbi']->query($sql_query);
    $sql_query = '';

    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        if (isset($db_rights[$row[$dbOrTableName]])) {
            $db_rights[$row[$dbOrTableName]]
                = array_merge($db_rights[$row[$dbOrTableName]], $row);
        } else {
            $db_rights[$row[$dbOrTableName]] = $row;
        }
        if (! strlen($dbname)) {
            // there are db specific rights for this user
            // so we can drop this db rights
            $db_rights[$row['Db']]['can_delete'] = true;
        }
    }
    $GLOBALS['dbi']->freeResult($result);
    return $db_rights;
}

/**
 * Display user rights in table rows(Table specific or database specific privs)
 *
 * @param array  $db_rights user's database rights array
 * @param string $dbname    database name
 * @param string $hostname  host name
 * @param string $username  username
 *
 * @return array $found_rows, $html_output
 */
function PMA_getHtmlForUserRights($db_rights, $dbname,
    $hostname, $username
) {
    $html_output = '';
    $found_rows = array();
    // display rows
    if (count($db_rights) < 1) {
        $html_output .= '<tr class="odd">' . "\n"
           . '<td colspan="6"><center><i>' . __('None') . '</i></center></td>' . "\n"
           . '</tr>' . "\n";
    } else {
        $odd_row = true;
        //while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        foreach ($db_rights as $row) {
            $found_rows[] = (! strlen($dbname)) ? $row['Db'] : $row['Table_name'];

            $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                . '<td>'
                . htmlspecialchars(
                    (! strlen($dbname)) ? $row['Db'] : $row['Table_name']
                )
                . '</td>' . "\n"
                . '<td><code>' . "\n"
                . '        '
                . join(
                    ',' . "\n" . '            ',
                    PMA_extractPrivInfo($row, true)
                ) . "\n"
                . '</code></td>' . "\n"
                . '<td>'
                    . ((((! strlen($dbname)) && $row['Grant_priv'] == 'Y')
                        || (strlen($dbname)
                        && in_array('Grant', explode(',', $row['Table_priv']))))
                    ? __('Yes')
                    : __('No'))
                . '</td>' . "\n"
                . '<td>';
            if (! empty($row['Table_privs']) || ! empty ($row['Column_priv'])) {
                $html_output .= __('Yes');
            } else {
                $html_output .= __('No');
            }
            $html_output .= '</td>' . "\n"
               . '<td>';
            $html_output .= PMA_getUserEditLink(
                $username,
                $hostname,
                (! strlen($dbname)) ? $row['Db'] : $dbname,
                (! strlen($dbname)) ? '' : $row['Table_name']
            );
            $html_output .= '</td>' . "\n"
               . '    <td>';
            if (! empty($row['can_delete'])
                || isset($row['Table_name'])
                && strlen($row['Table_name'])
            ) {
                $html_output .= PMA_getUserRevokeLink(
                    $username,
                    $hostname,
                    (! strlen($dbname)) ? $row['Db'] : $dbname,
                    (! strlen($dbname)) ? '' : $row['Table_name']
                );
            }
            $html_output .= '</td>' . "\n"
               . '</tr>' . "\n";
            $odd_row = ! $odd_row;
        } // end while
    } //end if
    return array($found_rows, $html_output);
}

/**
 * Get a HTML table for display user's tabel specific or database specific rights
 *
 * @param string $username username
 * @param string $hostname host name
 * @param string $dbname   database name
 *
 * @return array $html_output, $found_rows
 */
function PMA_getHtmlForAllTableSpecificRights(
    $username, $hostname, $dbname
) {
    // table header
    $html_output = PMA_URL_getHiddenInputs('', '')
        . '<input type="hidden" name="username" '
        . 'value="' . htmlspecialchars($username) . '" />' . "\n"
        . '<input type="hidden" name="hostname" '
        . 'value="' . htmlspecialchars($hostname) . '" />' . "\n"
        . '<fieldset>' . "\n"
        . '<legend data-submenu-label="'
        . (! strlen($dbname)
            ? __('Database')
            : __('Table')
        )
        . '">'
        . (! strlen($dbname)
            ? __('Database-specific privileges')
            : __('Table-specific privileges')
        )
        . '</legend>' . "\n"
        . '<table class="data">' . "\n"
        . '<thead>' . "\n"
        . '<tr><th>'
        . (! strlen($dbname) ? __('Database') : __('Table'))
        . '</th>' . "\n"
        . '<th>' . __('Privileges') . '</th>' . "\n"
        . '<th>' . __('Grant') . '</th>' . "\n"
        . '<th>'
        . (! strlen($dbname)
            ? __('Table-specific privileges')
            : __('Column-specific privileges')
        )
        . '</th>' . "\n"
        . '<th colspan="2">' . __('Action') . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n";

    $user_host_condition = ' WHERE `User`'
        . ' = \'' . PMA_Util::sqlAddSlashes($username) . "'"
        . ' AND `Host`'
        . ' = \'' . PMA_Util::sqlAddSlashes($hostname) . "'";

    // table body
    // get data

    // we also want privielgs for this user not in table `db` but in other table
    $tables = $GLOBALS['dbi']->fetchResult('SHOW TABLES FROM `mysql`;');

    /**
     * no db name given, so we want all privs for the given user
     * db name was given, so we want all user specific rights for this db
     */
    $db_rights = PMA_getUserSpecificRights($tables, $user_host_condition, $dbname);

    ksort($db_rights);

    $html_output .= '<tbody>' . "\n";
    // display rows
    list ($found_rows, $html_out) =  PMA_getHtmlForUserRights(
        $db_rights, $dbname, $hostname, $username
    );

    $html_output .= $html_out;
    $html_output .= '</tbody>' . "\n";
    $html_output .='</table>' . "\n";

    return array($html_output, $found_rows);
}

/**
 * Get HTML for display select db
 *
 * @param array $found_rows isset($dbname)) ? $row['Db'] : $row['Table_name']
 *
 * @return string HTML snippet
 */
function PMA_getHtmlForSelectDbInEditPrivs($found_rows)
{
    // we already have the list of databases from libraries/common.inc.php
    // via $pma = new PMA;
    $pred_db_array = $GLOBALS['pma']->databases;

    $databases_to_skip = array('information_schema', 'performance_schema');

    $html_output = '<label for="text_dbname">'
        . __('Add privileges on the following database:') . '</label>' . "\n";
    if (! empty($pred_db_array)) {
        $html_output .= '<select name="pred_dbname" class="autosubmit">' . "\n"
            . '<option value="" selected="selected">'
            . __('Use text field:') . '</option>' . "\n";
        foreach ($pred_db_array as $current_db) {
            if (in_array($current_db, $databases_to_skip)) {
                continue;
            }
            $current_db_show = $current_db;
            $current_db = PMA_Util::escapeMysqlWildcards($current_db);
            // cannot use array_diff() once, outside of the loop,
            // because the list of databases has special characters
            // already escaped in $found_rows,
            // contrary to the output of SHOW DATABASES
            if (empty($found_rows) || ! in_array($current_db, $found_rows)) {
                $html_output .= '<option value="'
                    . htmlspecialchars($current_db) . '">'
                    . htmlspecialchars($current_db_show) . '</option>' . "\n";
            }
        }
        $html_output .= '</select>' . "\n";
    }
    $html_output .= '<input type="text" id="text_dbname" name="dbname" '
        . 'required="required" />'
        . "\n"
        . PMA_Util::showHint(
            __('Wildcards % and _ should be escaped with a \ to use them literally.')
        );
    return $html_output;
}

/**
 * Get HTML for display table in edit privilege
 *
 * @param string $dbname     database naame
 * @param array  $found_rows isset($dbname)) ? $row['Db'] : $row['Table_name']
 *
 * @return string HTML snippet
 */
function PMA_displayTablesInEditPrivs($dbname, $found_rows)
{
    $html_output = '<input type="hidden" name="dbname"
        ' . 'value="' . htmlspecialchars($dbname) . '"/>' . "\n";
    $html_output .= '<label for="text_tablename">'
        . __('Add privileges on the following table:') . '</label>' . "\n";

    $result = @$GLOBALS['dbi']->tryQuery(
        'SHOW TABLES FROM ' . PMA_Util::backquote(
            PMA_Util::unescapeMysqlWildcards($dbname)
        ) . ';',
        null,
        PMA_DatabaseInterface::QUERY_STORE
    );

    if ($result) {
        $pred_tbl_array = array();
        while ($row = $GLOBALS['dbi']->fetchRow($result)) {
            if (! isset($found_rows) || ! in_array($row[0], $found_rows)) {
                $pred_tbl_array[] = $row[0];
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        if (! empty($pred_tbl_array)) {
            $html_output .= '<select name="pred_tablename" '
                . 'class="autosubmit">' . "\n"
                . '<option value="" selected="selected">' . __('Use text field')
                . ':</option>' . "\n";
            foreach ($pred_tbl_array as $current_table) {
                $html_output .= '<option '
                    . 'value="' . htmlspecialchars($current_table) . '">'
                    . htmlspecialchars($current_table)
                    . '</option>' . "\n";
            }
            $html_output .= '</select>' . "\n";
        }
    }
    $html_output .= '<input type="text" id="text_tablename" name="tablename" />'
        . "\n";

    return $html_output;
}

/**
 * Get HTML for display the users overview
 * (if less than 50 users, display them immediately)
 *
 * @param array  $result        ran sql query
 * @param array  $db_rights     user's database rights array
 * @param string $pmaThemeImage a image source link
 * @param string $text_dir      text directory
 *
 * @return string HTML snippet
 */
function PMA_getUsersOverview($result, $db_rights, $pmaThemeImage, $text_dir)
{
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        $row['privs'] = PMA_extractPrivInfo($row, true);
        $db_rights[$row['User']][$row['Host']] = $row;
    }
    @$GLOBALS['dbi']->freeResult($result);
    $user_group_count = 0;
    if ($GLOBALS['cfgRelation']['menuswork']) {
        $user_group_count = PMA_getUserGroupCount();
    }

    $html_output
        = '<form name="usersForm" id="usersForm" action="server_privileges.php" '
        . 'method="post">' . "\n"
        . PMA_URL_getHiddenInputs('', '')
        . '<table id="tableuserrights" class="data">' . "\n"
        . '<thead>' . "\n"
        . '<tr><th></th>' . "\n"
        . '<th>' . __('User') . '</th>' . "\n"
        . '<th>' . __('Host') . '</th>' . "\n"
        . '<th>' . __('Password') . '</th>' . "\n"
        . '<th>' . __('Global privileges') . ' '
        . PMA_Util::showHint(
            __('Note: MySQL privilege names are expressed in English.')
        )
        . '</th>' . "\n";
    if ($GLOBALS['cfgRelation']['menuswork']) {
        $html_output .= '<th>' . __('User group') . '</th>' . "\n";
    }
    $html_output .= '<th>' . __('Grant') . '</th>' . "\n"
        . '<th colspan="' . ($user_group_count > 0 ? '3' : '2') . '">'
        . __('Action') . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n";

    $html_output .= '<tbody>' . "\n";
    $html_output .= PMA_getHtmlTableBodyForUserRights($db_rights);
    $html_output .= '</tbody>'
        . '</table>' . "\n";

    $html_output .= '<div style="float:left;">'
        . '<img class="selectallarrow"'
        . ' src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png"'
        . ' width="38" height="22"'
        . ' alt="' . __('With selected:') . '" />' . "\n"
        . '<input type="checkbox" id="usersForm_checkall" class="checkall_box" '
        . 'title="' . __('Check All') . '" /> '
        . '<label for="usersForm_checkall">' . __('Check All') . '</label> '
        . '<i style="margin-left: 2em">' . __('With selected:') . '</i>' . "\n";

    $html_output .= PMA_Util::getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_export',
        __('Export'), 'b_tblexport.png', 'export'
    );
    $html_output .= '<input type="hidden" name="initial" '
        . 'value="' . (isset($_GET['initial']) ? $_GET['initial'] : '') . '" />';
    $html_output .= '</div>'
        . '<div class="clear_both" style="clear:both"></div>';

    // add/delete user fieldset
    $html_output .= PMA_getFieldsetForAddDeleteUser();
    $html_output .= '</form>' . "\n";

    return $html_output;
}

/**
 * Get table body for 'tableuserrights' table in userform
 *
 * @param array $db_rights user's database rights array
 *
 * @return string HTML snippet
 */
function PMA_getHtmlTableBodyForUserRights($db_rights)
{
    if ($GLOBALS['cfgRelation']['menuswork']) {
        $users_table = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
            . "." . PMA_Util::backquote($GLOBALS['cfg']['Server']['users']);
        $sql_query = 'SELECT * FROM ' . $users_table;
        $result = PMA_queryAsControlUser($sql_query, false);
        $group_assignment = array();
        if ($result) {
            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                $group_assignment[$row['username']] = $row['usergroup'];
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        $user_group_count = PMA_getUserGroupCount();
    }

    $odd_row = true;
    $index_checkbox = 0;
    $html_output = '';
    foreach ($db_rights as $user) {
        ksort($user);
        foreach ($user as $host) {
            $index_checkbox++;
            $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
                . "\n";
            $html_output .= '<td>'
                . '<input type="checkbox" class="checkall" name="selected_usr[]" '
                . 'id="checkbox_sel_users_'
                . $index_checkbox . '" value="'
                . htmlspecialchars($host['User'] . '&amp;#27;' . $host['Host'])
                . '"'
                . ' /></td>' . "\n";

            $html_output .= '<td><label '
                . 'for="checkbox_sel_users_' . $index_checkbox . '">'
                . (empty($host['User'])
                    ? '<span style="color: #FF0000">' . __('Any') . '</span>'
                    : htmlspecialchars($host['User'])) . '</label></td>' . "\n"
                . '<td>' . htmlspecialchars($host['Host']) . '</td>' . "\n";

            $html_output .= '<td>';
            switch ($host['Password']) {
            case 'Y':
                $html_output .= __('Yes');
                break;
            case 'N':
                $html_output .= '<span style="color: #FF0000">' . __('No')
                    . '</span>';
                break;
            // this happens if this is a definition not coming from mysql.user
            default:
                $html_output .= '--'; // in future version, replace by "not present"
                break;
            } // end switch
            $html_output .= '</td>' . "\n";

            $html_output .= '<td><code>' . "\n"
                . '' . implode(',' . "\n" . '            ', $host['privs']) . "\n"
                . '</code></td>' . "\n";
            if ($GLOBALS['cfgRelation']['menuswork']) {
                $html_output .= '<td class="usrGroup">' . "\n"
                    . (isset($group_assignment[$host['User']])
                        ? $group_assignment[$host['User']]
                        : ''
                    )
                    . '</td>' . "\n";
            }
            $html_output .= '<td>'
                . ($host['Grant_priv'] == 'Y' ? __('Yes') : __('No'))
                . '</td>' . "\n";

            $html_output .= '<td class="center">'
                . PMA_getUserEditLink(
                    $host['User'],
                    $host['Host']
                )
                . '</td>';
            if ($GLOBALS['cfgRelation']['menuswork'] && $user_group_count > 0) {
                if (empty($host['User'])) {
                    $html_output .= '<td class="center"></td>';
                } else {
                    $html_output .= '<td class="center">'
                        . PMA_getUserGroupEditLink($host['User'])
                        . '</td>';
                }
            }
            $html_output .= '<td class="center">'
                . PMA_getUserExportLink(
                    $host['User'],
                    $host['Host'],
                    isset($_GET['initial']) ? $_GET['initial'] : ''
                )
                . '</td>';
            $html_output .= '</tr>';
            $odd_row = ! $odd_row;
        }
    }
    return $html_output;
}

/**
 * Get HTML fieldset for Add/Delete user
 *
 * @return string HTML snippet
 */
function PMA_getFieldsetForAddDeleteUser()
{
    $html_output = '<fieldset id="fieldset_add_user">' . "\n";
    $html_output .= '<a href="server_privileges.php'
        . PMA_URL_getCommon(array('adduser' => 1))
        . '" class="ajax">' . "\n"
        . PMA_Util::getIcon('b_usradd.png')
        . '            ' . __('Add user') . '</a>' . "\n";
    $html_output .= '</fieldset>' . "\n";

    $html_output .= '<fieldset id="fieldset_delete_user">'
        . '<legend>' . "\n"
        . PMA_Util::getIcon('b_usrdrop.png')
        . '            ' . __('Remove selected users') . '' . "\n"
        . '</legend>' . "\n";

    $html_output .= '<input type="hidden" name="mode" value="2" />' . "\n"
        . '('
        . __(
            'Revoke all active privileges from the users '
            . 'and delete them afterwards.'
        )
        . ')'
        . '<br />' . "\n";

    $html_output .= '<input type="checkbox" '
        . 'title="'
        . __('Drop the databases that have the same names as the users.')
        . '" '
        . 'name="drop_users_db" id="checkbox_drop_users_db" />' . "\n";

    $html_output .= '<label for="checkbox_drop_users_db" '
        . 'title="'
        . __('Drop the databases that have the same names as the users.')
        . '">' . "\n"
        . '            '
        . __('Drop the databases that have the same names as the users.')
        . "\n"
        . '</label>' . "\n"
        . '</fieldset>' . "\n";

    $html_output .= '<fieldset id="fieldset_delete_user_footer" class="tblFooters">'
        . "\n";
    $html_output .= '<input type="submit" name="delete" '
        . 'value="' . __('Go') . '" id="buttonGo" '
        . 'class="ajax"/>' . "\n";

    $html_output .= '</fieldset>' . "\n";

    return $html_output;
}

/**
 * Get HTML for Displays the initials
 *
 * @param array $array_initials array for all initials, even non A-Z
 *
 * @return string HTML snippet
 */
function PMA_getHtmlForInitials($array_initials)
{
    // initialize to false the letters A-Z
    for ($letter_counter = 1; $letter_counter < 27; $letter_counter++) {
        if (! isset($array_initials[chr($letter_counter + 64)])) {
            $array_initials[chr($letter_counter + 64)] = false;
        }
    }

    $initials = $GLOBALS['dbi']->tryQuery(
        'SELECT DISTINCT UPPER(LEFT(`User`,1)) FROM `user` ORDER BY `User` ASC',
        null,
        PMA_DatabaseInterface::QUERY_STORE
    );
    while (list($tmp_initial) = $GLOBALS['dbi']->fetchRow($initials)) {
        $array_initials[$tmp_initial] = true;
    }

    // Display the initials, which can be any characters, not
    // just letters. For letters A-Z, we add the non-used letters
    // as greyed out.

    uksort($array_initials, "strnatcasecmp");

    $html_output = '<table id="initials_table" cellspacing="5">'
        . '<tr>';
    foreach ($array_initials as $tmp_initial => $initial_was_found) {
        if ($tmp_initial === null) {
            continue;
        }

        if (!$initial_was_found) {
            $html_output .= '<td>' . $tmp_initial . '</td>';
            continue;
        }

        $html_output .= '<td>'
            . '<a class="ajax'
            . ((isset($_REQUEST['initial'])
                && $_REQUEST['initial'] === $tmp_initial
                ) ? ' active' : '')
            . '" href="server_privileges.php'
            . PMA_URL_getCommon(array('initial' => $tmp_initial))
            . '">' . $tmp_initial
            . '</a>'
            . '</td>' . "\n";
    }
    $html_output .= '<td>'
        . '<a href="server_privileges.php'
        . PMA_URL_getCommon(array('showall' => 1))
        . '" class="nowrap">' . __('Show all') . '</a></td>' . "\n";
    $html_output .= '</tr></table>';

    return $html_output;
}

/**
 * Get the database rigths array for Display user overview
 *
 * @return array  $db_rights    database rights array
 */
function PMA_getDbRightsForUserOverview()
{
    // we also want users not in table `user` but in other table
    $tables = $GLOBALS['dbi']->fetchResult('SHOW TABLES FROM `mysql`;');

    $tablesSearchForUsers = array(
        'user', 'db', 'tables_priv', 'columns_priv', 'procs_priv',
    );

    $db_rights_sqls = array();
    foreach ($tablesSearchForUsers as $table_search_in) {
        if (in_array($table_search_in, $tables)) {
            $db_rights_sqls[] = 'SELECT DISTINCT `User`, `Host` FROM `mysql`.`'
                . $table_search_in . '` '
                . (isset($_GET['initial'])
                ? PMA_rangeOfUsers($_GET['initial'])
                : '');
        }
    }
    $user_defaults = array(
        'User'       => '',
        'Host'       => '%',
        'Password'   => '?',
        'Grant_priv' => 'N',
        'privs'      => array('USAGE'),
    );

    // for the rights
    $db_rights = array();

    $db_rights_sql = '(' . implode(') UNION (', $db_rights_sqls) . ')'
        . ' ORDER BY `User` ASC, `Host` ASC';

    $db_rights_result = $GLOBALS['dbi']->query($db_rights_sql);

    while ($db_rights_row = $GLOBALS['dbi']->fetchAssoc($db_rights_result)) {
        $db_rights_row = array_merge($user_defaults, $db_rights_row);
        $db_rights[$db_rights_row['User']][$db_rights_row['Host']]
            = $db_rights_row;
    }
    $GLOBALS['dbi']->freeResult($db_rights_result);
    ksort($db_rights);

    return $db_rights;
}

/**
 * Delete user and get message and sql query for delete user in privileges
 *
 * @param string $queries queries
 *
 * @return PMA_message
 */
function PMA_deleteUser($queries)
{
    if (empty($queries)) {
        $message = PMA_Message::error(__('No users selected for deleting!'));
    } else {
        if ($_REQUEST['mode'] == 3) {
            $queries[] = '# ' . __('Reloading the privileges') . ' …';
            $queries[] = 'FLUSH PRIVILEGES;';
        }
        $drop_user_error = '';
        foreach ($queries as $sql_query) {
            if ($sql_query{0} != '#') {
                if (! $GLOBALS['dbi']->tryQuery($sql_query, $GLOBALS['userlink'])) {
                    $drop_user_error .= $GLOBALS['dbi']->getError() . "\n";
                }
            }
        }
        // tracking sets this, causing the deleted db to be shown in navi
        unset($GLOBALS['db']);

        $sql_query = join("\n", $queries);
        if (! empty($drop_user_error)) {
            $message = PMA_Message::rawError($drop_user_error);
        } else {
            $message = PMA_Message::success(
                __('The selected users have been deleted successfully.')
            );
        }
    }
    return array($sql_query, $message);
}

/**
 * Update the privileges and return the success or error message
 *
 * @param string $username  username
 * @param string $hostname  host name
 * @param string $tablename table name
 * @param string $dbname    database name
 *
 * @return PMA_message success message or error message for update
 */
function PMA_updatePrivileges($username, $hostname, $tablename, $dbname)
{
    $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);

    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . PMA_Util::sqlAddSlashes($username)
        . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\';';

    if (! isset($_POST['Grant_priv']) || $_POST['Grant_priv'] != 'Y') {
        $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table
            . ' FROM \'' . PMA_Util::sqlAddSlashes($username) . '\'@\''
            . PMA_Util::sqlAddSlashes($hostname) . '\';';
    } else {
        $sql_query1 = '';
    }

    // Should not do a GRANT USAGE for a table-specific privilege, it
    // causes problems later (cannot revoke it)
    if (! (strlen($tablename) && 'USAGE' == implode('', PMA_extractPrivInfo()))) {
        $sql_query2 = 'GRANT ' . join(', ', PMA_extractPrivInfo())
            . ' ON ' . $db_and_table
            . ' TO \'' . PMA_Util::sqlAddSlashes($username) . '\'@\''
            . PMA_Util::sqlAddSlashes($hostname) . '\'';

        if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')
            || (! strlen($dbname)
            && (isset($_POST['max_questions']) || isset($_POST['max_connections'])
            || isset($_POST['max_updates'])
            || isset($_POST['max_user_connections'])))
        ) {
            $sql_query2 .= PMA_getWithClauseForAddUserAndUpdatePrivs();
        }
        $sql_query2 .= ';';
    }
    if (! $GLOBALS['dbi']->tryQuery($sql_query0)) {
        // This might fail when the executing user does not have
        // ALL PRIVILEGES himself.
        // See https://sourceforge.net/p/phpmyadmin/bugs/3270/
        $sql_query0 = '';
    }
    if (! empty($sql_query1) && ! $GLOBALS['dbi']->tryQuery($sql_query1)) {
        // this one may fail, too...
        $sql_query1 = '';
    }
    if (! empty($sql_query2)) {
        $GLOBALS['dbi']->query($sql_query2);
    } else {
        $sql_query2 = '';
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1 . ' ' . $sql_query2;
    $message = PMA_Message::success(__('You have updated the privileges for %s.'));
    $message->addParam(
        '\'' . htmlspecialchars($username)
        . '\'@\'' . htmlspecialchars($hostname) . '\''
    );

    return array($sql_query, $message);
}

/**
 * Get List of information: Changes / copies a user
 *
 * @return array()
 */
function PMA_getDataForChangeOrCopyUser()
{
    $row = null;
    $queries = null;
    $password = null;

    if (isset($_REQUEST['change_copy'])) {
        $user_host_condition = ' WHERE `User` = '
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . "'"
            . ' AND `Host` = '
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['old_hostname']) . "';";
        $row = $GLOBALS['dbi']->fetchSingleRow(
            'SELECT * FROM `mysql`.`user` ' . $user_host_condition
        );
        if (! $row) {
            $response = PMA_Response::getInstance();
            $response->addHTML(
                PMA_Message::notice(__('No user found.'))->getDisplay()
            );
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

    return array($queries, $password);
}

/**
 * Update Data for information: Deletes users
 *
 * @param array $queries queries array
 *
 * @return array
 */
function PMA_getDataForDeleteUsers($queries)
{
    if (isset($_REQUEST['change_copy'])) {
        $selected_usr = array(
            $_REQUEST['old_username'] . '&amp;#27;' . $_REQUEST['old_hostname']
        );
    } else {
        $selected_usr = $_REQUEST['selected_usr'];
        $queries = array();
    }
    foreach ($selected_usr as $each_user) {
        list($this_user, $this_host) = explode('&amp;#27;', $each_user);
        $queries[] = '# '
            . sprintf(
                __('Deleting %s'),
                '\'' . $this_user . '\'@\'' . $this_host . '\''
            )
            . ' ...';
        $queries[] = 'DROP USER \''
            . PMA_Util::sqlAddSlashes($this_user)
            . '\'@\'' . PMA_Util::sqlAddSlashes($this_host) . '\';';

        if (isset($_REQUEST['drop_users_db'])) {
            $queries[] = 'DROP DATABASE IF EXISTS '
                . PMA_Util::backquote($this_user) . ';';
            $GLOBALS['reload'] = true;
        }
    }
    return $queries;
}

/**
 * update Message For Reload
 *
 * @return array
 */
function PMA_updateMessageForReload()
{
    $message = null;
    if (isset($_REQUEST['flush_privileges'])) {
        $sql_query = 'FLUSH PRIVILEGES;';
        $GLOBALS['dbi']->query($sql_query);
        $message = PMA_Message::success(
            __('The privileges were reloaded successfully.')
        );
    }

    if (isset($_REQUEST['validate_username'])) {
        $message = PMA_Message::success();
    }

    return $message;
}

/**
 * update Data For Queries from queries_for_display
 *
 * @param array $queries             queries array
 * @param array $queries_for_display queries arry for display
 *
 * @return null
 */
function PMA_getDataForQueries($queries, $queries_for_display)
{
    $tmp_count = 0;
    foreach ($queries as $sql_query) {
        if ($sql_query{0} != '#') {
            $GLOBALS['dbi']->query($sql_query);
        }
        // when there is a query containing a hidden password, take it
        // instead of the real query sent
        if (isset($queries_for_display[$tmp_count])) {
            $queries[$tmp_count] = $queries_for_display[$tmp_count];
        }
        $tmp_count++;
    }

    return $queries;
}

/**
 * update Data for information: Adds a user
 *
 * @param string $dbname      db name
 * @param string $username    user name
 * @param string $hostname    host name
 * @param string $password    password
 * @param bool   $is_menuwork is_menuwork set?
 *
 * @return array
 */
function PMA_addUser(
    $dbname, $username, $hostname,
    $password, $is_menuwork
) {
    $_add_user_error = false;
    $message = null;
    $queries = null;
    $queries_for_display = null;
    $sql_query = null;
    if (isset($_REQUEST['adduser_submit']) || isset($_REQUEST['change_copy'])) {
        $sql_query = '';
        if ($_POST['pred_username'] == 'any') {
            $username = '';
        }
        switch ($_POST['pred_hostname']) {
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
            $_user_name = $GLOBALS['dbi']->fetchValue('SELECT USER()');
            $hostname = substr($_user_name, (strrpos($_user_name, '@') + 1));
            unset($_user_name);
            break;
        }
        $sql = "SELECT '1' FROM `mysql`.`user`"
            . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";
        if ($GLOBALS['dbi']->fetchValue($sql) == 1) {
            $message = PMA_Message::error(__('The user %s already exists!'));
            $message->addParam(
                '[em]\'' . $username . '\'@\'' . $hostname . '\'[/em]'
            );
            $_REQUEST['adduser'] = true;
            $_add_user_error = true;
        } else {
            list($create_user_real, $create_user_show, $real_sql_query, $sql_query)
                = PMA_getSqlQueriesForDisplayAndAddUser(
                    $username, $hostname, (isset ($password) ? $password : '')
                );

            if (empty($_REQUEST['change_copy'])) {
                $_error = false;

                if (isset($create_user_real)) {
                    if (! $GLOBALS['dbi']->tryQuery($create_user_real)) {
                        $_error = true;
                    }
                    $sql_query = $create_user_show . $sql_query;
                }
                list($sql_query, $message) = PMA_addUserAndCreateDatabase(
                    $_error, $real_sql_query, $sql_query, $username, $hostname,
                    isset($dbname) ? $dbname : null
                );
                if (! empty($_REQUEST['userGroup']) && $is_menuwork) {
                    PMA_setUserGroup($GLOBALS['username'], $_REQUEST['userGroup']);
                }

            } else {
                if (isset($create_user_real)) {
                    $queries[] = $create_user_real;
                }
                $queries[] = $real_sql_query;
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
        }
    }

    return array(
        $message, $queries, $queries_for_display, $sql_query, $_add_user_error
    );
}

/**
 * Update DB information: DB, Table, isWildcard
 *
 * @return array
 */
function PMA_getDataForDBInfo()
{
    $username = null;
    $hostname = null;
    $dbname = null;
    $tablename = null;
    $db_and_table = null;
    $dbname_is_wildcard = null;

    if (isset ($_REQUEST['username'])) {
        $username = $_REQUEST['username'];
    }
    if (isset ($_REQUEST['hostname'])) {
        $hostname = $_REQUEST['hostname'];
    }
    /**
     * Checks if a dropdown box has been used for selecting a database / table
     */
    if (PMA_isValid($_REQUEST['pred_tablename'])) {
        $tablename = $_REQUEST['pred_tablename'];
    } elseif (PMA_isValid($_REQUEST['tablename'])) {
        $tablename = $_REQUEST['tablename'];
    } else {
        unset($tablename);
    }

    if (PMA_isValid($_REQUEST['pred_dbname'])) {
        $dbname = $_REQUEST['pred_dbname'];
    } elseif (PMA_isValid($_REQUEST['dbname'])) {
        $dbname = $_REQUEST['dbname'];
    } else {
        unset($dbname);
        unset($tablename);
    }

    if (isset($dbname)) {
        $unescaped_db = PMA_Util::unescapeMysqlWildcards($dbname);
        $db_and_table = PMA_Util::backquote($unescaped_db) . '.';
        if (isset($tablename)) {
            $db_and_table .= PMA_Util::backquote($tablename);
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

    return array(
        $username, $hostname,
        isset($dbname)? $dbname : null,
        isset($tablename)? $tablename : null,
        $db_and_table,
        $dbname_is_wildcard,
    );
}

/**
 * Get title and textarea for export user definition in Privileges
 *
 * @param string $username username
 * @param string $hostname host name
 *
 * @return array ($title, $export)
 */
function PMA_getListForExportUserDefinition($username, $hostname)
{
    $export = '<textarea class="export" cols="' . $GLOBALS['cfg']['TextareaCols']
        . '" rows="' . $GLOBALS['cfg']['TextareaRows'] . '">';

    if (isset($_REQUEST['selected_usr'])) {
        // export privileges for selected users
        $title = __('Privileges');
        foreach ($_REQUEST['selected_usr'] as $export_user) {
            $export_username = substr($export_user, 0, strpos($export_user, '&'));
            $export_hostname = substr($export_user, strrpos($export_user, ';') + 1);
            $export .= '# '
                . sprintf(
                    __('Privileges for %s'),
                    '`' . htmlspecialchars($export_username)
                    . '`@`' . htmlspecialchars($export_hostname) . '`'
                )
                . "\n\n";
            $export .= PMA_getGrants($export_username, $export_hostname) . "\n";
        }
    } else {
        // export privileges for a single user
        $title = __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
        $export .= PMA_getGrants($username, $hostname);
    }
    // remove trailing whitespace
    $export = trim($export);

    $export .= '</textarea>';

    return array($title, $export);
}

/**
 * Get HTML for display Add userfieldset
 *
 * @return string html output
 */
function PMA_getAddUserHtmlFieldset()
{
    return '<fieldset id="fieldset_add_user">' . "\n"
        . '<a href="server_privileges.php'
        . PMA_URL_getCommon(array('adduser' => 1))
        . '" class="ajax">' . "\n"
        . PMA_Util::getIcon('b_usradd.png')
        . '            ' . __('Add user') . '</a>' . "\n"
        . '</fieldset>' . "\n";
}

/**
 * Get HTML header for display User's properties
 *
 * @param boolean $dbname_is_wildcard whether database name is wildcard or not
 * @param string  $url_dbname         url database name that urlencode() string
 * @param string  $dbname             database name
 * @param string  $username           username
 * @param string  $hostname           host name
 * @param string  $tablename          table name
 *
 * @return string $html_output
 */
function PMA_getHtmlHeaderForUserProperties(
    $dbname_is_wildcard, $url_dbname, $dbname, $username, $hostname, $tablename
) {
    $html_output = '<h2>' . "\n"
       . PMA_Util::getIcon('b_usredit.png')
       . __('Edit Privileges:') . ' '
       . __('User');

    if (! empty($dbname)) {
        $html_output .= ' <i><a class="edit_user_anchor ajax"'
            . ' href="server_privileges.php'
            . PMA_URL_getCommon(
                array(
                    'username' => $username,
                    'hostname' => $hostname,
                    'dbname' => '',
                    'tablename' => '',
                )
            )
            . '">\'' . htmlspecialchars($username)
            . '\'@\'' . htmlspecialchars($hostname)
            . '\'</a></i>' . "\n";

        $html_output .= ' - ';
        $html_output .= $dbname_is_wildcard ? __('Databases') : __('Database');
        if (! empty($_REQUEST['tablename'])) {
            $html_output .= ' <i><a href="server_privileges.php'
                . PMA_URL_getCommon(
                    array(
                        'username' => $username,
                        'hostname' => $hostname,
                        'dbname' => $url_dbname,
                        'tablename' => '',
                    )
                )
                . '">' . htmlspecialchars($dbname)
                . '</a></i>';

            $html_output .= ' - ' . __('Table')
                . ' <i>' . htmlspecialchars($tablename) . '</i>';
        } else {
            $html_output .= ' <i>' . htmlspecialchars($dbname) . '</i>';
        }

    } else {
        $html_output .= ' <i>\'' . htmlspecialchars($username)
            . '\'@\'' . htmlspecialchars($hostname)
            . '\'</i>' . "\n";

    }
    $html_output .= '</h2>' . "\n";

    return $html_output;
}

/**
 * Get HTML snippet for display user overview page
 *
 * @param string $pmaThemeImage a image source link
 * @param string $text_dir      text directory
 *
 * @return string $html_output
 */
function PMA_getHtmlForUserOverview($pmaThemeImage, $text_dir)
{
    $html_output = '<h2>' . "\n"
       . PMA_Util::getIcon('b_usrlist.png')
       . __('Users overview') . "\n"
       . '</h2>' . "\n";

    // $sql_query is for the initial-filtered,
    // $sql_query_all is for counting the total no. of users

    $sql_query = $sql_query_all = 'SELECT *,' .
        "       IF(`Password` = _latin1 '', 'N', 'Y') AS 'Password'" .
        '  FROM `mysql`.`user`';

    $sql_query .= (isset($_REQUEST['initial'])
        ? PMA_rangeOfUsers($_REQUEST['initial'])
        : '');

    $sql_query .= ' ORDER BY `User` ASC, `Host` ASC;';
    $sql_query_all .= ' ;';

    $res = $GLOBALS['dbi']->tryQuery(
        $sql_query, null, PMA_DatabaseInterface::QUERY_STORE
    );
    $res_all = $GLOBALS['dbi']->tryQuery(
        $sql_query_all, null, PMA_DatabaseInterface::QUERY_STORE
    );


    if (! $res) {
        // the query failed! This may have two reasons:
        // - the user does not have enough privileges
        // - the privilege tables use a structure of an earlier version.
        // so let's try a more simple query

        $sql_query = 'SELECT * FROM `mysql`.`user`';
        $res = $GLOBALS['dbi']->tryQuery(
            $sql_query, null, PMA_DatabaseInterface::QUERY_STORE
        );

        if (! $res) {
            $html_output .= PMA_Message::error(__('No Privileges'))->getDisplay();
            $GLOBALS['dbi']->freeResult($res);
            unset($res);
        } else {
            // This message is hardcoded because I will replace it by
            // a automatic repair feature soon.
            $raw = 'Your privilege table structure seems to be older than'
                . ' this MySQL version!<br />'
                . 'Please run the <code>mysql_upgrade</code> command'
                . '(<code>mysql_fix_privilege_tables</code> on older systems)'
                . ' that should be included in your MySQL server distribution'
                . ' to solve this problem!';
            $html_output .= PMA_Message::rawError($raw)->getDisplay();
        }
    } else {
        $db_rights = PMA_getDbRightsForUserOverview();
        // for all initials, even non A-Z
        $array_initials = array();

        /**
         * Displays the initials
         * Also not necessary if there is less than 20 privileges
         */
        if ($GLOBALS['dbi']->numRows($res_all) > 20) {
            $html_output .= PMA_getHtmlForInitials($array_initials);
        }

        /**
        * Display the user overview
        * (if less than 50 users, display them immediately)
        */
        if (isset($_REQUEST['initial'])
            || isset($_REQUEST['showall'])
            || $GLOBALS['dbi']->numRows($res) < 50
        ) {
            $html_output .= PMA_getUsersOverview(
                $res, $db_rights, $pmaThemeImage, $text_dir
            );
        } else {
            $html_output .= PMA_getAddUserHtmlFieldset();
        } // end if (display overview)

        if (! $GLOBALS['is_ajax_request']
            || ! empty($_REQUEST['ajax_page_request'])
        ) {
            $flushnote = new PMA_Message(
                __(
                    'Note: phpMyAdmin gets the users\' privileges directly '
                    . 'from MySQL\'s privilege tables. The content of these tables '
                    . 'may differ from the privileges the server uses, '
                    . 'if they have been changed manually. In this case, '
                    . 'you should %sreload the privileges%s before you continue.'
                ),
                PMA_Message::NOTICE
            );
            $flushLink = '<a href="server_privileges.php'
                . PMA_URL_getCommon(array('flush_privileges' => 1))
                . '" id="reload_privileges_anchor">';
            $flushnote->addParam(
                $flushLink,
                false
            );
            $flushnote->addParam('</a>', false);
            $html_output .= $flushnote->getDisplay();
        }
    }

    return $html_output;
}

/**
 * Get HTML snippet for display user properties
 *
 * @param boolean $dbname_is_wildcard whether database name is wildcard or not
 * @param string  $url_dbname         url database name that urlencode() string
 * @param string  $username           username
 * @param string  $hostname           host name
 * @param string  $dbname             database name
 * @param string  $tablename          table name
 *
 * @return string $html_output
 */
function PMA_getHtmlForUserProperties($dbname_is_wildcard,$url_dbname,
    $username, $hostname, $dbname, $tablename
) {
    $html_output = PMA_getHtmlHeaderForUserProperties(
        $dbname_is_wildcard, $url_dbname, $dbname, $username, $hostname, $tablename
    );

    $sql = "SELECT '1' FROM `mysql`.`user`"
        . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
        . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";

    $user_does_not_exists = (bool) ! $GLOBALS['dbi']->fetchValue($sql);

    if ($user_does_not_exists) {
        $html_output .= PMA_Message::error(
            __('The selected user was not found in the privilege table.')
        )->getDisplay();
        $html_output .= PMA_getHtmlForLoginInformationFields();
            //exit;
    }

    $_params = array(
        'username' => $username,
        'hostname' => $hostname,
    );
    if (strlen($dbname)) {
        $_params['dbname'] = $dbname;
        if (strlen($tablename)) {
            $_params['tablename'] = $tablename;
        }
    }

    $html_output .= '<form class="ajax submenu-item" name="usersForm" '
        . 'id="addUsersForm" action="server_privileges.php" method="post">' . "\n";
    $html_output .= PMA_URL_getHiddenInputs($_params);
    $html_output .= PMA_getHtmlToDisplayPrivilegesTable(
        PMA_ifSetOr($dbname, '*', 'length'),
        PMA_ifSetOr($tablename, '*', 'length')
    );

    $html_output .= '</form>' . "\n";

    if (! strlen($tablename) && empty($dbname_is_wildcard)) {

        // no table name was given, display all table specific rights
        // but only if $dbname contains no wildcards

        $html_output .= '<form class="submenu-item" action="server_privileges.php" '
            . 'id="db_or_table_specific_priv" method="post">' . "\n";

        // unescape wildcards in dbname at table level
        $unescaped_db = PMA_Util::unescapeMysqlWildcards($dbname);
        list($html_rightsTable, $found_rows)
            = PMA_getHtmlForAllTableSpecificRights(
                $username, $hostname, $unescaped_db
            );
        $html_output .= $html_rightsTable;

        if (! strlen($dbname)) {
            // no database name was given, display select db
            $html_output .= PMA_getHtmlForSelectDbInEditPrivs($found_rows);

        } else {
            $html_output .= PMA_displayTablesInEditPrivs($dbname, $found_rows);
        }
        $html_output .= '</fieldset>' . "\n";

        $html_output .= '<fieldset class="tblFooters">' . "\n"
           . '    <input type="submit" value="' . __('Go') . '" />'
           . '</fieldset>' . "\n"
           . '</form>' . "\n";
    }

    // Provide a line with links to the relevant database and table
    if (strlen($dbname) && empty($dbname_is_wildcard)) {
        $html_output .= PMA_getLinkToDbAndTable($url_dbname, $dbname, $tablename);

    }

    if (! strlen($dbname) && ! $user_does_not_exists) {
        //change login information
        $html_output .= PMA_getHtmlForChangePassword($username, $hostname);
        $html_output .= PMA_getChangeLoginInformationHtmlForm($username, $hostname);
    }

    return $html_output;
}

/**
 * Get queries for Table privileges to change or copy user
 *
 * @param string $user_host_condition user host condition to
 *                                    select relevent table privileges
 * @param array  $queries             queries array
 * @param string $username            username
 * @param string $hostname            host name
 *
 * @return array  $queries
 */
function PMA_getTablePrivsQueriesForChangeOrCopyUser($user_host_condition,
    $queries, $username, $hostname
) {
    $res = $GLOBALS['dbi']->query(
        'SELECT `Db`, `Table_name`, `Table_priv` FROM `mysql`.`tables_priv`'
        . $user_host_condition,
        $GLOBALS['userlink'],
        PMA_DatabaseInterface::QUERY_STORE
    );
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {

        $res2 = $GLOBALS['dbi']->query(
            'SELECT `Column_name`, `Column_priv`'
            . ' FROM `mysql`.`columns_priv`'
            . ' WHERE `User`'
            . ' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . "'"
            . ' AND `Host`'
            . ' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . '\''
            . ' AND `Db`'
            . ' = \'' . PMA_Util::sqlAddSlashes($row['Db']) . "'"
            . ' AND `Table_name`'
            . ' = \'' . PMA_Util::sqlAddSlashes($row['Table_name']) . "'"
            . ';',
            null,
            PMA_DatabaseInterface::QUERY_STORE
        );

        $tmp_privs1 = PMA_extractPrivInfo($row);
        $tmp_privs2 = array(
            'Select' => array(),
            'Insert' => array(),
            'Update' => array(),
            'References' => array()
        );

        while ($row2 = $GLOBALS['dbi']->fetchAssoc($res2)) {
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
        if (count($tmp_privs2['Select']) > 0 && ! in_array('SELECT', $tmp_privs1)) {
            $tmp_privs1[] = 'SELECT (`' . join('`, `', $tmp_privs2['Select']) . '`)';
        }
        if (count($tmp_privs2['Insert']) > 0 && ! in_array('INSERT', $tmp_privs1)) {
            $tmp_privs1[] = 'INSERT (`' . join('`, `', $tmp_privs2['Insert']) . '`)';
        }
        if (count($tmp_privs2['Update']) > 0 && ! in_array('UPDATE', $tmp_privs1)) {
            $tmp_privs1[] = 'UPDATE (`' . join('`, `', $tmp_privs2['Update']) . '`)';
        }
        if (count($tmp_privs2['References']) > 0
            && ! in_array('REFERENCES', $tmp_privs1)
        ) {
            $tmp_privs1[]
                = 'REFERENCES (`' . join('`, `', $tmp_privs2['References']) . '`)';
        }

        $queries[] = 'GRANT ' . join(', ', $tmp_privs1)
            . ' ON ' . PMA_Util::backquote($row['Db']) . '.'
            . PMA_Util::backquote($row['Table_name'])
            . ' TO \'' . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\''
            . (in_array('Grant', explode(',', $row['Table_priv']))
            ? ' WITH GRANT OPTION;'
            : ';');
    }
    return $queries;
}

/**
 * Get queries for database specific privileges for change or copy user
 *
 * @param array  $queries  queries array with string
 * @param string $username username
 * @param string $hostname host name
 *
 * @return array $queries
 */
function PMA_getDbSpecificPrivsQueriesForChangeOrCopyUser(
    $queries, $username, $hostname
) {
    $user_host_condition = ' WHERE `User`'
        . ' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . "'"
        . ' AND `Host`'
        . ' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_hostname']) . '\';';

    $res = $GLOBALS['dbi']->query(
        'SELECT * FROM `mysql`.`db`' . $user_host_condition
    );

    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $queries[] = 'GRANT ' . join(', ', PMA_extractPrivInfo($row))
            . ' ON ' . PMA_Util::backquote($row['Db']) . '.*'
            . ' TO \'' . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\''
            . ($row['Grant_priv'] == 'Y' ? ' WITH GRANT OPTION;' : ';');
    }
    $GLOBALS['dbi']->freeResult($res);

    $queries = PMA_getTablePrivsQueriesForChangeOrCopyUser(
        $user_host_condition, $queries, $username, $hostname
    );

    return $queries;
}

/**
 * Prepares queries for adding users and
 * also create database and return query and message
 *
 * @param boolean $_error         whether user create or not
 * @param string  $real_sql_query SQL query for add a user
 * @param string  $sql_query      SQL query to be displayed
 * @param string  $username       username
 * @param string  $hostname       host name
 * @param string  $dbname         database name
 *
 * @return array  $sql_query, $message
 */
function PMA_addUserAndCreateDatabase($_error, $real_sql_query, $sql_query,
    $username, $hostname, $dbname
) {
    if ($_error || ! $GLOBALS['dbi']->tryQuery($real_sql_query)) {
        $_REQUEST['createdb-1'] = $_REQUEST['createdb-2']
            = $_REQUEST['createdb-3'] = false;
        $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
    } else {
        $message = PMA_Message::success(__('You have added a new user.'));
    }

    if (isset($_REQUEST['createdb-1'])) {
        // Create database with same name and grant all privileges
        $q = 'CREATE DATABASE IF NOT EXISTS '
            . PMA_Util::backquote(
                PMA_Util::sqlAddSlashes($username)
            ) . ';';
        $sql_query .= $q;
        if (! $GLOBALS['dbi']->tryQuery($q)) {
            $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
        }

        /**
         * Reload the navigation
         */
        $GLOBALS['reload'] = true;
        $GLOBALS['db'] = $username;

        $q = 'GRANT ALL PRIVILEGES ON '
            . PMA_Util::backquote(
                PMA_Util::escapeMysqlWildcards(
                    PMA_Util::sqlAddSlashes($username)
                )
            ) . '.* TO \''
            . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\';';
        $sql_query .= $q;
        if (! $GLOBALS['dbi']->tryQuery($q)) {
            $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
        }
    }

    if (isset($_REQUEST['createdb-2'])) {
        // Grant all privileges on wildcard name (username\_%)
        $q = 'GRANT ALL PRIVILEGES ON '
            . PMA_Util::backquote(
                PMA_Util::sqlAddSlashes($username) . '\_%'
            ) . '.* TO \''
            . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\';';
        $sql_query .= $q;
        if (! $GLOBALS['dbi']->tryQuery($q)) {
            $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
        }
    }

    if (isset($_REQUEST['createdb-3'])) {
        // Grant all privileges on the specified database to the new user
        $q = 'GRANT ALL PRIVILEGES ON '
        . PMA_Util::backquote(
            PMA_Util::sqlAddSlashes($dbname)
        ) . '.* TO \''
        . PMA_Util::sqlAddSlashes($username)
        . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\';';
        $sql_query .= $q;
        if (! $GLOBALS['dbi']->tryQuery($q)) {
            $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
        }
    }
    return array($sql_query, $message);
}

/**
 * Get SQL queries for Display and Add user
 *
 * @param string $username usernam
 * @param string $hostname host name
 * @param string $password password
 *
 * @return array ($create_user_real, $create_user_show,$real_sql_query, $sql_query)
 */
function PMA_getSqlQueriesForDisplayAndAddUser($username, $hostname, $password)
{
    $sql_query = '';
    $create_user_real = 'CREATE USER \''
        . PMA_Util::sqlAddSlashes($username) . '\'@\''
        . PMA_Util::sqlAddSlashes($hostname) . '\'';

    $real_sql_query = 'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON *.* TO \''
        . PMA_Util::sqlAddSlashes($username) . '\'@\''
        . PMA_Util::sqlAddSlashes($hostname) . '\'';

    if ($_POST['pred_password'] != 'none' && $_POST['pred_password'] != 'keep') {
        $sql_query = $real_sql_query . ' IDENTIFIED BY \'***\'';
        $real_sql_query .= ' IDENTIFIED BY \''
            . PMA_Util::sqlAddSlashes($_POST['pma_pw']) . '\'';
        if (isset($create_user_real)) {
            $create_user_show = $create_user_real . ' IDENTIFIED BY \'***\'';
            $create_user_real .= ' IDENTIFIED BY \''
                . PMA_Util::sqlAddSlashes($_POST['pma_pw']) . '\'';
        }
    } else {
        if ($_POST['pred_password'] == 'keep' && ! empty($password)) {
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

    if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')
        || (isset($_POST['max_questions']) || isset($_POST['max_connections'])
        || isset($_POST['max_updates']) || isset($_POST['max_user_connections']))
    ) {
        $with_clause = PMA_getWithClauseForAddUserAndUpdatePrivs();
        $real_sql_query .= ' ' . $with_clause;
        $sql_query .= ' ' . $with_clause;
    }

    if (isset($create_user_real)) {
        $create_user_real .= ';';
        $create_user_show .= ';';
    }
    $real_sql_query .= ';';
    $sql_query .= ';';

    return array($create_user_real,
        $create_user_show,
        $real_sql_query,
        $sql_query
    );
}
?>
