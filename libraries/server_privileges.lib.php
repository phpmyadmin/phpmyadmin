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
            $db_and_table
                = PMA_CommonFunctions::getInstance()->backquote(PMA_unescapeMysqlWildcards($dbname)) . '.'
                . PMA_CommonFunctions::getInstance()->backquote($tablename);
        } else {
            $db_and_table = PMA_CommonFunctions::getInstance()->backquote($dbname) . '.*';
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
    if (! empty($initial)) {
        $ret = " WHERE `User` LIKE '"
            . PMA_CommonFunctions::getInstance()->sqlAddSlashes($initial, true) . "%'"
            . " OR `User` LIKE '"
            . PMA_CommonFunctions::getInstance()->sqlAddSlashes(strtolower($initial), true) . "%'";
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
 * @return array
 */
function PMA_extractPrivInfo($row = '', $enableHTML = false)
{
    $grants = PMA_getGrantsArray();

    if (! empty($row) && isset($row['Table_priv'])) {
        $row1 = PMA_DBI_fetch_single_row(
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
                && count($GLOBALS[$current_grant[0]]) == $GLOBALS['column_count']
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
             && empty($GLOBALS[$current_grant[0] . '_none'])) {
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
            && (! isset($GLOBALS['grant_count'])
            || count($privs) == $GLOBALS['grant_count'])
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
}

/**
 * Displays on which column(s) a table-specific privilege is granted
 *
 * @param array  $columns           columns array
 * @param array  $row               first row from result or boolean false
 * @param string $name_for_select   privilege types - Select_priv, Insert_priv
 *                                  Update_priv, References_priv
 * @param string $priv_for_header   privilege for header
 * @param string $name              privilege name - insert, select, update, references
 * @param string $name_for_dfn      name for dfn
 * @param string $name_for_current  name for current
 *
 * @return $html_output             html snippet
 */
function PMA_getHtmlForDisplayColumnPrivileges($columns, $row, $name_for_select,
    $priv_for_header, $name, $name_for_dfn, $name_for_current
) {
    $html_output = '<div class="item" id="div_item_' . $name . '">' . "\n"
        . '<label for="select_' . $name . '_priv">' . "\n"
        . '<code><dfn title="' . $name_for_dfn . '">'
        . $priv_for_header . '</dfn></code>' . "\n"
        . '</label><br />' . "\n"
        . '<select id="select_' . $name . '_priv" name="'
        . $name_for_select . '[]" multiple="multiple" size="8">' . "\n";

    foreach ($columns as $current_column => $current_column_privileges) {
        $html_output .= '<option value="' . htmlspecialchars($current_column) . '"';
        if ($row[$name_for_select] == 'Y' || $current_column_privileges[$name_for_current]) {
            $html_output .= ' selected="selected"';
        }
        $html_output .= '>' . htmlspecialchars($current_column) . '</option>' . "\n";
    }

    $html_output .= '</select>' . "\n"
        . '<i>' . __('Or') . '</i>' . "\n"
        . '<label for="checkbox_' . $name_for_select
        . '_none"><input type="checkbox"'
        . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"')
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
 * @param string $db      the database
 * @param string $table   the table
 * @param string $username   username for database connection
 * @param string $hostname   hostname for database connection
 * 
 * @return string sql query
 */
function PMA_getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname)
{
    $common_functions = PMA_CommonFunctions::getInstance();
    if ($db == '*') {
        return "SELECT * FROM `mysql`.`user`"
            ." WHERE `User` = '" . $common_functions->sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . $common_functions->sqlAddSlashes($hostname) . "';";
    } elseif ($table == '*') {
        return "SELECT * FROM `mysql`.`db`"
            ." WHERE `User` = '" . $common_functions->sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . $common_functions->sqlAddSlashes($hostname) . "'"
            ." AND '" . $common_functions->unescapeMysqlWildcards($db) . "'"
            ." LIKE `Db`;";
    }
    return "SELECT `Table_priv`"
        ." FROM `mysql`.`tables_priv`"
        ." WHERE `User` = '" . $common_functions->sqlAddSlashes($username) . "'"
        ." AND `Host` = '" . $common_functions->sqlAddSlashes($hostname) . "'"
        ." AND `Db` = '" . $common_functions->unescapeMysqlWildcards($db) . "'"
        ." AND `Table_name` = '" . $common_functions->sqlAddSlashes($table) . "';";
}
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
 * @return string html snippet
 */
function PMA_getHtmlToDisplayPrivilegesTable($random_n, $db = '*', $table = '*', $submit = true)
{
    $html_output = '';
    
    if ($db == '*') {
        $table = '*';
    }

    if (isset($GLOBALS['username'])) {
        $username = $GLOBALS['username'];
        $hostname = $GLOBALS['hostname'];
        $sql_query = PMA_getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
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

        // get collumns
        $res = PMA_DBI_try_query(
            'SHOW COLUMNS FROM '
            . PMA_backquote(PMA_unescapeMysqlWildcards($db))
            . '.' . PMA_backquote($table) . ';'
        );
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
        $html_output .= PMA_getHtmlForTableSpecificPrivileges($username, $hostname, $db, $table, $columns);
    } else {
        // g l o b a l    o r    d b - s p e c i f i c
        $html_output .= PMA_getHtmlForGlobalOrDbSpecificPrivs($db, $table, $row, $random_n);
    }
    $html_output .= '</fieldset>' . "\n";
    if ($submit) {
        $html_output .= '<fieldset id="fieldset_user_privtable_footer" class="tblFooters">' . "\n"
           . '<input type="submit" name="update_privs" value="' . __('Go') . '" />' . "\n"
           . '</fieldset>' . "\n";
    }
    return $html_output;
} // end of the 'PMA_displayPrivTable()' function

/**
 * Get HTML for "Resource limits"
 * 
 * @param array $row    first row from result or boolean false
 * 
 * @return string       html snippet 
 */
function PMA_getHtmlForDisplayResourceLimits($row)
{
    return '<fieldset>' . "\n"
       . '<legend>' . __('Resource limits') . '</legend>' . "\n"
       . '<p><small><i>' . __('Note: Setting these options to 0 (zero) removes the limit.') . '</i></small></p>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="text_max_questions"><code><dfn title="'
       . __('Limits the number of queries the user may send to the server per hour.') . '">MAX QUERIES PER HOUR</dfn></code></label>' . "\n"
       . '<input type="text" name="max_questions" id="text_max_questions" value="'
       . $row['max_questions'] . '" size="11" maxlength="11" title="' . __('Limits the number of queries the user may send to the server per hour.') . '" />' . "\n"
       . '</div>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="text_max_updates"><code><dfn title="'
       . __('Limits the number of commands that change any table or database the user may execute per hour.') . '">MAX UPDATES PER HOUR</dfn></code></label>' . "\n"
       . '<input type="text" name="max_updates" id="text_max_updates" value="'
       . $row['max_updates'] . '" size="11" maxlength="11" title="' . __('Limits the number of commands that change any table or database the user may execute per hour.') . '" />' . "\n"
       . '</div>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="text_max_connections"><code><dfn title="'
       . __('Limits the number of new connections the user may open per hour.') . '">MAX CONNECTIONS PER HOUR</dfn></code></label>' . "\n"
       . '<input type="text" name="max_connections" id="text_max_connections" value="'
       . $row['max_connections'] . '" size="11" maxlength="11" title="' . __('Limits the number of new connections the user may open per hour.') . '" />' . "\n"
       . '</div>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="text_max_user_connections"><code><dfn title="'
       . __('Limits the number of simultaneous connections the user may have.') . '">MAX USER_CONNECTIONS</dfn></code></label>' . "\n"
       . '<input type="text" name="max_user_connections" id="text_max_user_connections" value="'
       . $row['max_user_connections'] . '" size="11" maxlength="11" title="' . __('Limits the number of simultaneous connections the user may have.') . '" />' . "\n"
       . '</div>' . "\n"
       . '</fieldset>' . "\n";
}

/**
 * Get the HTML snippet for table specific privileges
 * 
 * @param string $username  username for database connection
 * @param string $hostname  hostname for database connection
 * @param string $db        the database
 * @param string $table     the table
 * @param boolean $columns  columns array
 * 
 * @return string $html_output
 */
function PMA_getHtmlForTableSpecificPrivileges($username, $hostname, $db, $table, $columns)
{
    $res = PMA_DBI_query(
        'SELECT `Column_name`, `Column_priv`'
        .' FROM `mysql`.`columns_priv`'
        .' WHERE `User`'
        .' = \'' . PMA_CommonFunctions::getInstance()->sqlAddSlashes($username) . "'"
        .' AND `Host`'
        .' = \'' . PMA_CommonFunctions::getInstance()->sqlAddSlashes($hostname) . "'"
        .' AND `Db`'
        .' = \'' . PMA_CommonFunctions::getInstance()->sqlAddSlashes(PMA_unescapeMysqlWildcards($db)) . "'"
        .' AND `Table_name`'
        .' = \'' . PMA_CommonFunctions::getInstance()->sqlAddSlashes($table) . '\';'
    );

    while ($row1 = PMA_DBI_fetch_row($res)) {
        $row1[1] = explode(',', $row1[1]);
        foreach ($row1[1] as $current) {
            $columns[$row1[0]][$current] = true;
        }
    }
    PMA_DBI_free_result($res);
    unset($res, $row1, $current);

    $html_output = '<input type="hidden" name="grant_count" value="' . count($row) . '" />' . "\n"
       . '<input type="hidden" name="column_count" value="' . count($columns) . '" />' . "\n"
       . '<fieldset id="fieldset_user_priv">' . "\n"
       . '    <legend>' . __('Table-specific privileges')
       . PMA_CommonFunctions::getInstance()->showHint(
               __('Note: MySQL privilege names are expressed in English')
           )
       . '</legend>' . "\n";

    // privs that are attached to a specific column
    $html_output .= PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn($columns, $row);
    
    // privs that are not attached to a specific column   
    $html_output .= '<div class="item">' . "\n"
        . PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn($row, $grant_type)
        . '</div>' . "\n";
    
    // for Safari 2.0.2
    $html_output .= '<div class="clearfloat"></div>' . "\n";
    
    return $html_output;
}

/**
 * Get HTML snippet for privileges that are attached to a specific column
 * 
 * @param string $columns   columns array
 * @param array $row        first row from result or boolean false
 * 
 * @return string $html_output
 */
function PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn($columns, $row)
{
    $html_output = PMA_getHtmlForDisplayColumnPrivileges(
        $columns, $row, 'Select_priv', 'SELECT',
        'select', __('Allows reading data.'), 'Select'
    );

    $html_output .= PMA_getHtmlForDisplayColumnPrivileges(
        $columns, $row, 'Insert_priv', 'INSERT',
        'insert', __('Allows inserting and replacing data.'), 'Insert'
    );

    $html_output .= PMA_getHtmlForDisplayColumnPrivileges(
        $columns, $row, 'Update_priv', 'UPDATE',
        'update', __('Allows changing data.'), 'Update'
    );

    $html_output .= PMA_getHtmlForDisplayColumnPrivileges(
        $columns, $row, 'References_priv', 'REFERENCES', 'references',
        __('Has no effect in this MySQL version.'), 'References'
    );
    return $html_output;
}

/** 
 * Get HTML for privileges that are not attached to a specific column
 * 
 * @param array $row         first row from result or boolean false
 * @param array $grant_type  privilrge type
 * 
 * @return string $html_output
 */
function PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn($row, $grant_type)
{
    $html_output = '';
    foreach ($row as $current_grant => $current_grant_value) {
        $grant_type = substr($current_grant, 0, (strlen($current_grant) - 5));
        if (in_array($grant_type, array('Select', 'Insert', 'Update', 'References'))) {
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
           . (empty($GLOBALS['checkall']) ?  '' : ' checked="checked"')
           . ' name="' . $current_grant . '" id="checkbox_' . $current_grant
           . '" value="Y" '
           . ($current_grant_value == 'Y' ? 'checked="checked" ' : '')
           . 'title="';

        $html_output .= (isset($GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))])
            ? $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))]
            : $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)) . 'Tbl']) . '"/>' . "\n";

        $html_output .= '<label for="checkbox_' . $current_grant
            . '"><code><dfn title="'
            . (isset($GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))])
                ? $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5))]
                : $GLOBALS['strPrivDesc' . substr($tmp_current_grant, 0, (strlen($tmp_current_grant) - 5)) . 'Tbl'])
            . '">' . strtoupper(substr($current_grant, 0, strlen($current_grant) - 5)) . '</dfn></code></label>' . "\n"
            . '</div>' . "\n";
    } // end foreach ()
    return $html_output;
}

/**
 * Get HTML for global or database specific privileges
 * 
 * @param string $db        the database
 * @param string $table     the table
 * @param string $row       first row from result or boolean false
 * @param string $random_n  a random number that will be appended
 *                          to the id of the user forms
 * 
 * @return string $html_output
 */
function PMA_getHtmlForGlobalOrDbSpecificPrivs($db, $table, $row, $random_n)
{
    $privTable_names = array(0 => __('Data'), 1 => __('Structure'), 2 => __('Administration'));
    $privTable = array();
    // d a t a
    $privTable[0] = PMA_getDataPrivilegeTable($db);

    // s t r u c t u r e
    $privTable[1] = PMA_getStructurePrivilegeTable($table, $row);

    // a d m i n i s t r a t i o n
    $privTable[2] = PMA_getAdministrationPrivilegeTable($db);
    
    $html_output = '<input type="hidden" name="grant_count" value="'
        . (count($privTable[0]) + count($privTable[1]) + count($privTable[2]) - (isset($row['Grant_priv']) ? 1 : 0))
        . '" />' . "\n"
        . '<fieldset id="fieldset_user_global_rights">' . "\n"
        . '<legend>' . "\n"
        . '        '
        . ($db == '*'
            ? __('Global privileges')
            : ($table == '*'
                ? __('Database-specific privileges')
                : __('Table-specific privileges'))) . "\n"
        . '(<a href="server_privileges.php?'
        . $GLOBALS['url_query'] . '&amp;checkall=1" onclick="setCheckboxes(\'addUsersForm_' . $random_n . '\', true); return false;">'
        . __('Check All') . '</a> /' . "\n"
        . '<a href="server_privileges.php?'
        . $GLOBALS['url_query'] . '" onclick="setCheckboxes(\'addUsersForm_' . $random_n . '\', false); return false;">'
        . __('Uncheck All') . '</a>)' . "\n"
        . '</legend>' . "\n"
        . '<p><small><i>' . __('Note: MySQL privilege names are expressed in English') . '</i></small></p>' . "\n";

    // Output the Global privilege tables with checkboxes
    $html_output .= PMA_getHtmlForGlobalPrivTableWithCheckboxes($privTable, $privTable_names, $row);

    // The "Resource limits" box is not displayed for db-specific privs
    if ($db == '*') {
        $html_output .= PMA_getHtmlForDisplayResourceLimits($row);
    }
    // for Safari 2.0.2
    $html_output .= '<div class="clearfloat"></div>' . "\n";
    
    return $html_output;
}

/**
 * Get data privilege table as an array
 * 
 * @param string $db    the database
 * 
 * @return string       data privilege table
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
 * @param array $row    first row from result or boolean false
 * 
 * @return string       structure privilege table
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
            __('Allows to set up events for the event scheduler')
        );
        $structure_privTable[] = array('Trigger',
            'TRIGGER',
            __('Allows creating and dropping triggers')
        );
    }
    return $structure_privTable;
}

/**
 * Get administration privilege table as an array
 * 
 * @param string $db    the table
 * 
 * @return string       administration privilege table
 */
function PMA_getAdministrationPrivilegeTable($db)
{
    $administration_privTable = array(
        array('Grant', 'GRANT', __('Allows adding users and privileges without reloading the privilege tables.')),
    );
    if ($db == '*') {
        $administration_privTable[] = array('Super',
            'SUPER',
            __('Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.')
        );
        $administration_privTable[] = array('Process',
            'PROCESS',
            __('Allows viewing processes of all users')
        );
        $administration_privTable[] = array('Reload',
            'RELOAD',
            __('Allows reloading server settings and flushing the server\'s caches.')
        );
        $administration_privTable[] = array('Shutdown',
            'SHUTDOWN',
            __('Allows shutting down the server.')
        );
        $administration_privTable[] = array('Show_db',
            'SHOW DATABASES',
            __('Gives access to the complete list of databases.')
        );
    }
    $administration_privTable[] = array('Lock_tables',
        'LOCK TABLES',
        __('Allows locking tables for the current thread.')
    );
    $administration_privTable[] = array('References',
        'REFERENCES',
        __('Has no effect in this MySQL version.')
    );
    if ($db == '*') {
        $administration_privTable[] = array('Repl_client',
            'REPLICATION CLIENT',
            __('Allows the user to ask where the slaves / masters are.')
        );
        $administration_privTable[] = array('Repl_slave',
            'REPLICATION SLAVE',
            __('Needed for the replication slaves.')
        );
        $administration_privTable[] = array('Create_user',
            'CREATE USER',
            __('Allows creating, dropping and renaming user accounts.')
        );
    }
    return $administration_privTable;
}

/**
 * Get HTML snippet for global privileges table with check boxes
 * 
 * @param array $privTable          privileges table array
 * @param array $privTable_names    names of the privilege tables (Data, Structure, Administration)
 * @param array $row                first row from result or boolean false
 * 
 * @return string $html_output
 */
function PMA_getHtmlForGlobalPrivTableWithCheckboxes($privTable, $privTable_names, $row)
{
    $html_output = '';
    foreach ($privTable as $i => $table) {
        $html_output .= '<fieldset>' . "\n"
            . '<legend>' . __($privTable_names[$i]) . '</legend>' . "\n";
        foreach ($table as $priv) {
            $html_output .= '<div class="item">' . "\n"
                . '<input type="checkbox"'
                . ' name="' . $priv[0] . '_priv" id="checkbox_' . $priv[0] . '_priv"'
                . ' value="Y" title="' . $priv[2] . '"'
                . ((! empty($GLOBALS['checkall']) || $row[$priv[0] . '_priv'] == 'Y') ?  ' checked="checked"' : '')
                . '/>' . "\n"
                . '<label for="checkbox_' . $priv[0] . '_priv"><code><dfn title="' . $priv[2] . '">'
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
function PMA_getHtmlForDisplayLoginInformationFields($mode = 'new')
{
    list($username_length, $hostname_length) = PMA_getUsernameAndHostnameLength();

    if (isset($GLOBALS['username']) && strlen($GLOBALS['username']) === 0) {
        $GLOBALS['pred_username'] = 'any';
    }
    $html_output = '<fieldset id="fieldset_add_user_login">' . "\n"
       . '<legend>' . __('Login Information') . '</legend>' . "\n"
       . '<div class="item">' . "\n"
       . '<label for="select_pred_username">' . "\n"
       . '    ' . __('User name') . ':' . "\n"
       . '</label>' . "\n"
       . '<span class="options">' . "\n"
       . '<select name="pred_username" id="select_pred_username" title="' . __('User name') . '"' . "\n"
       . '        onchange="if (this.value == \'any\') { username.value = \'\'; } else if (this.value == \'userdefined\') { username.focus(); username.select(); }">' . "\n"
       . '<option value="any"' . ((isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] == 'any') ? ' selected="selected"' : '') . '>' . __('Any user') . '</option>' . "\n"
       . '<option value="userdefined"' . ((! isset($GLOBALS['pred_username']) || $GLOBALS['pred_username'] == 'userdefined') ? ' selected="selected"' : '') . '>' . __('Use text field') . ':</option>' . "\n"
       . '</select>' . "\n"
       . '</span>' . "\n"
       . '<input type="text" name="username" maxlength="'
       . $username_length . '" title="' . __('User name') . '"'
       . (empty($GLOBALS['username'])
           ? ''
           : ' value="' . htmlspecialchars(
               isset($GLOBALS['new_username'])
               ? $GLOBALS['new_username']
               : $GLOBALS['username']
           ) . '"'
       )
       . ' onchange="pred_username.value = \'userdefined\';" autofocus="autofocus" />' . "\n"
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
    $html_output .= '    onchange="if (this.value == \'any\') { hostname.value = \'%\'; } else if (this.value == \'localhost\') { hostname.value = \'localhost\'; } '
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
    $html_output .=  '        <option value="any"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'any')
            ? ' selected="selected"' : '') . '>' . __('Any host')
        . '</option>' . "\n"
        . '<option value="localhost"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'localhost')
            ? ' selected="selected"' : '') . '>' . __('Local')
        . '</option>' . "\n";
    if (! empty($thishost)) {
        $html_output .= '        <option value="thishost"'
            . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'thishost')
                ? ' selected="selected"' : '') . '>' . __('This Host')
            . '</option>' . "\n";
    }
    unset($thishost);
    $html_output .= '        <option value="hosttable"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'hosttable')
            ? ' selected="selected"' : '') . '>' . __('Use Host Table')
        . '</option>' . "\n"
        . '        <option value="userdefined"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'userdefined')
            ? ' selected="selected"' : '')
        . '>' . __('Use text field') . ':</option>' . "\n"
        . '</select>' . "\n"
        . '</span>' . "\n"
        . '<input type="text" name="hostname" maxlength="'
        . $hostname_length . '" value="'
        . htmlspecialchars(isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '')
        . '" title="' . __('Host')
        . '" onchange="pred_hostname.value = \'userdefined\';" />' . "\n"
        . PMA_CommonFunctions::getInstance()->showHint(__('When Host table is used, this field is ignored and values stored in Host table are used instead.'))
        . '</div>' . "\n"
        . '<div class="item">' . "\n"
        . '<label for="select_pred_password">' . "\n"
        . '    ' . __('Password') . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '<select name="pred_password" id="select_pred_password" title="'
        . __('Password') . '"' . "\n"
        . '            onchange="if (this.value == \'none\') { pma_pw.value = \'\'; pma_pw2.value = \'\'; } else if (this.value == \'userdefined\') { pma_pw.focus(); pma_pw.select(); }">' . "\n"
        . ($mode == 'change' ? '            <option value="keep" selected="selected">' . __('Do not change the password') . '</option>' . "\n" : '')
        . '<option value="none"';
    if (isset($GLOBALS['username']) && $mode != 'change') {
        $html_output .= '  selected="selected"';
    }
    $html_output .= '>' . __('No Password') . '</option>' . "\n"
       . '<option value="userdefined"' . (isset($GLOBALS['username']) ? '' : ' selected="selected"') . '>' . __('Use text field') . ':</option>' . "\n"
       . '</select>' . "\n"
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
    
    return $html_output;
} // end of the 'PMA_displayUserAndHostFields()' function

/**
 * Get username and hostname length
 * 
 * @return array username length and hostname length 
 */
function PMA_getUsernameAndHostnameLength()
{
    $fields_info = PMA_DBI_get_columns('mysql', 'user', null, true);
    $username_length = 16;
    $hostname_length = 41;
    foreach ($fields_info as $val) {
        if ($val['Field'] == 'User') {
            strtok($val['Type'], '()');
            $value = strtok('()');
            if (is_int($value)) {
                $username_length = $value;
            }
        } elseif ($val['Field'] == 'Host') {
            strtok($val['Type'], '()');
            $value = strtok('()');
            if (is_int($value)) {
                $hostname_length = $value;
            }
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
    $grants = PMA_DBI_fetch_result("SHOW GRANTS FOR '" 
        . PMA_CommonFunctions::getInstance()->sqlAddSlashes($user) . "'@'" 
        . PMA_CommonFunctions::getInstance()->sqlAddSlashes($host) . "'");
    $response = '';
    foreach ($grants as $one_grant) {
        $response .= $one_grant . ";\n\n";
    }
    return $response;
} // end of the 'PMA_getGrants()' function

/**
 * Update password and get message for password updating
 * 
 * @param string $pma_pw    password that user entered for change, comming from request
 * @param string $pma_pw2   Re typed password, comming from request
 * @param string $err_url   error url
 * @param string $username  username
 * @param string $hostname  hostname
 * 
 * @return string $message  success or error message after updating password
 */
function PMA_getMessageForUpdatePassword($pma_pw, $pma_pw2, $err_url, $username, $hostname)
{
    // similar logic in user_password.php
    $message = '';
    
    if (empty($_REQUEST['nopass']) && isset($pma_pw) && isset($pma_pw2)) {
        if ($pma_pw != $pma_pw2) {
            $message = PMA_Message::error(__('The passwords aren\'t the same!'));
        } elseif (empty($pma_pw) || empty($pma_pw2)) {
            $message = PMA_Message::error(__('The password is empty!'));
        }
    }
    
    // here $nopass could be == 1
    if (empty($message)) {
        $common_functions = PMA_CommonFunctions::getInstance();
        
        $hashing_function = (! empty($_REQUEST['pw_hash']) && $_REQUEST['pw_hash'] == 'old' ? 'OLD_' : '')
                      . 'PASSWORD';

        // in $sql_query which will be displayed, hide the password
        $sql_query        = 'SET PASSWORD FOR \'' 
            . $common_functions->sqlAddSlashes($username) 
            . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\' = ' 
            . (($pma_pw == '') ? '\'\'' : $hashing_function . '(\'' . preg_replace('@.@s', '*', $pma_pw) . '\')');
        
        $local_query      = 'SET PASSWORD FOR \'' 
            . $common_functions->sqlAddSlashes($username)
            . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\' = '
            . (($pma_pw == '') ? '\'\'' : $hashing_function 
            . '(\'' . $common_functions->sqlAddSlashes($pma_pw) . '\')');
        
        PMA_DBI_try_query($local_query)
            or $common_functions->mysqlDie(PMA_DBI_getError(), $sql_query, false, $err_url);
        $message = PMA_Message::success(__('The password for %s was changed successfully.'));
        $message->addParam('\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'');
    }
    return $message;
}

/**
 * Revokes privileges and get message and SQL query for privileges revokes
 * 
 * @param string $db_and_table  wildcard Escaped database+table specification
 * @param string $dbname        database name
 * @param string $tablename     table name
 * @param string $username      username
 * @param string $hostname      host name
 * @return array ($message, $sql_query)
 */
function PMA_getMessageAndSqlQueryForPrivilegesRevoke($db_and_table, $dbname,
    $tablename, $username, $hostname
) {
    $db_and_table = PMA_wildcardEscapeForGrant($dbname, isset($tablename) ? $tablename : '');

    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . PMA_CommonFunctions::getInstance()->sqlAddSlashes($username) . '\'@\''
        . PMA_CommonFunctions::getInstance()->sqlAddSlashes($hostname) . '\';';
    
    $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table
        . ' FROM \'' . PMA_CommonFunctions::getInstance()->sqlAddSlashes($username) . '\'@\''
        . PMA_CommonFunctions::getInstance()->sqlAddSlashes($hostname) . '\';';

    PMA_DBI_query($sql_query0);
    if (! PMA_DBI_try_query($sql_query1)) {
        // this one may fail, too...
        $sql_query1 = '';
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1;
    $message = PMA_Message::success(__('You have revoked the privileges for %s'));
    $message->addParam('\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\'');
    
    return array($message, $sql_query);
}

/**
 * Get a common SQL query for 'update privileges' and 'add user'
 * 
 * @param type $Grant_priv              grant privileges
 * @param type $max_questions           maximum questions
 * @param type $max_connections         maximum connections
 * @param type $max_updates             maximum updates
 * @param type $max_user_connections    maximum userconnections
 * 
 * @return string $sql_query
 */
function PMA_getCommonSQlQueryForAddUserAndUpdatePrivs($Grant_priv, $max_questions,
    $max_connections, $max_updates, $max_user_connections
) {
    $sql_query = 'WITH';
    if ($Grant_priv == 'Y') {
        $sql_query .= ' GRANT OPTION';
    }
    $max_questions = max(0, (int)$max_questions);
    $sql_query .= ' MAX_QUERIES_PER_HOUR ' . $max_questions;

    $max_connections = max(0, (int)$max_connections);
    $sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . $max_connections;

    $max_updates = max(0, (int)$max_updates);
    $sql_query .= ' MAX_UPDATES_PER_HOUR ' . $max_updates;

    $max_user_connections = max(0, (int)$max_user_connections);
    $sql_query .= ' MAX_USER_CONNECTIONS ' . $max_user_connections;

    return $sql_query;
}

/**
 * Get HTML for addUsersForm, This function call if isset($_REQUEST['adduser'])
 * 
 * @param int $random_n
 * @param string $dbname
 * 
 * @return string HTML for addUserForm
 */
function PMA_getHtmlForAddUser($random_n, $dbname)
{
    $common_functions = PMA_CommonFunctions::getInstance();
    $GLOBALS['url_query'] .= '&amp;adduser=1';
    
    $html_output = '<h2>' . "\n"
       . $common_functions->getIcon('b_usradd.png') . __('Add user') . "\n"
       . '</h2>' . "\n"
       . '<form name="usersForm" id="addUsersForm_' . $random_n . '" action="server_privileges.php" method="post">' . "\n"
       . PMA_generate_common_hidden_inputs('', '')
       . PMA_getHtmlForDisplayLoginInformationFields('new');
    
    $html_output .= '<fieldset id="fieldset_add_user_database">' . "\n"
        . '<legend>' . __('Database for user') . '</legend>' . "\n";

    $html_output .= $common_functions->getCheckbox(
        'createdb-1',
        __('Create database with same name and grant all privileges'),
        false, false
    );
    $html_output .= '<br />' . "\n";
    $html_output .= $common_functions->getCheckbox(
        'createdb-2',
        __('Grant all privileges on wildcard name (username\\_%)'),
        false, false
    );
    $html_output .= '<br />' . "\n";

    if (! empty($dbname) ) {
        $html_output .= $common_functions->getCheckbox(
            'createdb-3',
            sprintf(__('Grant all privileges on database &quot;%s&quot;'),
            htmlspecialchars($dbname)), true, false
        );
        $html_output .= '<input type="hidden" name="dbname" value="'
            . htmlspecialchars($dbname) . '" />' . "\n";
        $html_output .= '<br />' . "\n";
    }

    $html_output .= '</fieldset>' . "\n";
    $html_output .= PMA_getHtmlToDisplayPrivilegesTable($random_n, '*', '*', false);
    $html_output .= '<fieldset id="fieldset_add_user_footer" class="tblFooters">' . "\n"
       . '<input type="submit" name="adduser_submit" value="' . __('Go') . '" />' . "\n"
       . '</fieldset>' . "\n"
       . '</form>' . "\n";
    
    return $html_output;
}

/**
 * Get the list of privileges and list of compared privileges as strings
 * and return a array that contains both strings
 * 
 * @return array  $list_of_privileges, $list_of_compared_privileges
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
    return array($list_of_privileges, $list_of_compared_privileges);
}

/**
 * Get the HTML for user form and check the privileges for a particular database.
 * 
 * @param string $checkprivs        check privileges
 * @param string $link_edit         standard link for edit
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 * 
 * @return string $html_output
 */
function PMA_getUserForm($checkprivs, $link_edit, $conditional_class)
{
    $common_functions = PMA_CommonFunctions::getInstance();
    // check the privileges for a particular database.
    $html_output = '<form id="usersForm" action="server_privileges.php"><fieldset>' . "\n";
    $html_output .= '<legend>' . "\n"
        . $common_functions->getIcon('b_usrcheck.png')
        . '    '
        . sprintf(
           __('Users having access to &quot;%s&quot;'),
           '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url($checkprivs) . '">' .  htmlspecialchars($checkprivs) . '</a>'
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
    $odd_row = true;
    // now, we build the table...
    list($list_of_privileges, $list_of_compared_privileges)
        = PMA_getListOfPrivilegesAndComparedPrivileges();

    $sql_query = '(SELECT ' . $list_of_privileges . ', `Db`'
        .' FROM `mysql`.`db`'
        .' WHERE \'' . $common_functions->sqlAddSlashes($checkprivs) . "'"
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
    $html_output .= PMA_getUserFormTableBody(
        $found, $row, $odd_row, $link_edit, $res
    );
    $html_output .= '</table>'
        . '</fieldset>'
        . '</form>' . "\n";

    if ($GLOBALS['is_ajax_request'] == true) {
        $message = PMA_Message::success(__('User has been added.'));
        $response = PMA_Response::getInstance();
        $response->addJSON('message', $message);
        $response->addJSON('user_form', $html_output);
        exit;
    } else {
        // Offer to create a new user for the current database
        $html_output .= '<fieldset id="fieldset_add_user">' . "\n"
           . '<legend>' . __('New') . '</legend>' . "\n";
        
        $html_output .= '<a href="server_privileges.php?'
            . $GLOBALS['url_query'] . '&amp;adduser=1&amp;dbname=' . htmlspecialchars($checkprivs)
            .'" rel="'.'checkprivs='.htmlspecialchars($checkprivs). '&amp;'.$GLOBALS['url_query']
            . '" class="'.$conditional_class
            .'" name="db_specific">' . "\n"
            . $common_functions->getIcon('b_usradd.png')
            . '        ' . __('Add user') . '</a>' . "\n";
                
        $html_output .= '</fieldset>' . "\n";
    }
    return $html_output;
}

/**
 * Get HTML snippet for table body of user form
 * 
 * @param boolean $found        whether user found or not
 * @param array $row            array of rows from mysql , db table with list of privileges  
 * @param boolean $odd_row      whether odd or not
 * @param string $link_edit     standard link for edit
 * @param string $res           ran sql query
 *            
 * @return string $html_output
 */
function PMA_getUserFormTableBody($found, $row, $odd_row, $link_edit, $res)
{
    $html_output = '<tbody>' . "\n";
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
            $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                . '<td';
            if (count($current_privileges) > 1) {
                $html_output .= ' rowspan="' . count($current_privileges) . '"';
            }
            $html_output .= '>' 
                . (empty($current_user)
                    ? '<span style="color: #FF0000">' . __('Any') . '</span>'
                    : htmlspecialchars($current_user)) . "\n"
                . '</td>' . "\n";
            
            $html_output .= '<td';
            if (count($current_privileges) > 1) {
                $html_output .= ' rowspan="' . count($current_privileges) . '"';
            }
            $html_output .= '>'
                . htmlspecialchars($current_host) . '</td>' . "\n";
            for ($i = 0; $i < count($current_privileges); $i++) {
                $current = $current_privileges[$i];
                $html_output .= '<td>' . "\n"
                   . '            ';
                if (! isset($current['Db']) || $current['Db'] == '*') {
                    $html_output .= __('global');
                } elseif ($current['Db'] == PMA_CommonFunctions::getInstance()->escapeMysqlWildcards($checkprivs)) {
                    $html_output .= __('database-specific');
                } else {
                    $html_output .= __('wildcard'). ': <code>' . htmlspecialchars($current['Db']) . '</code>';
                }
                $html_output .= "\n"
                   . '</td>' . "\n";
                
                $html_output .='<td>' . "\n"
                   . '<code>' . "\n"
                   . '' . join(',' . "\n" . '                ', PMA_extractPrivInfo($current, true)) . "\n"
                   . '</code>' . "\n"
                   . '</td>' . "\n";
                
                $html_output .= '<td>' . "\n"
                   . '' . ($current['Grant_priv'] == 'Y' ? __('Yes') : __('No')) . "\n"
                   . '</td>' . "\n"
                   . '<td>' . "\n";
                $html_output .= sprintf(
                    $link_edit,
                    urlencode($current_user),
                    urlencode($current_host),
                    urlencode(! isset($current['Db']) || $current['Db'] == '*' ? '' : $current['Db']),
                    ''
                );
                $html_output .= '</td>' . "\n"
                   . '    </tr>' . "\n";
                if (($i + 1) < count($current_privileges)) {
                    $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">' . "\n";
                }
            }
            if (empty($row)) {
                break;
            }
            $odd_row = ! $odd_row;
        }
    } else {
        $html_output .= '<tr class="odd">' . "\n"
           . '<td colspan="6">' . "\n"
           . '            ' . __('No user found.') . "\n"
           . '</td>' . "\n"
           . '</tr>' . "\n";
    }
    $html_output .= '</tbody>' . "\n";
    
    return $html_output;
}

?>
