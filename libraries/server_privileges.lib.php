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
    if (! empty($initial)) {
        $ret = " WHERE `User` LIKE '"
            . PMA_Util::sqlAddSlashes($initial, true) . "%'"
            . " OR `User` LIKE '"
            . PMA_Util::sqlAddSlashes(strtolower($initial), true) . "%'";
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
            && (! isset($_POST['grant_count'])
            || count($privs) == $_POST['grant_count'])
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
 * @param array  $columns          columns array
 * @param array  $row              first row from result or boolean false
 * @param string $name_for_select  privilege types - Select_priv, Insert_priv
 *                                 Update_priv, References_priv
 * @param string $priv_for_header  privilege for header
 * @param string $name             privilege name: insert, select, update, references
 * @param string $name_for_dfn     name for dfn
 * @param string $name_for_current name for current
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
        $html_output .= '<option '
            . 'value="' . htmlspecialchars($current_column) . '"';
        if ($row[$name_for_select] == 'Y'
            || $current_column_privileges[$name_for_current]
        ) {
            $html_output .= ' selected="selected"';
        }
        $html_output .= '>'
            . htmlspecialchars($current_column) . '</option>' . "\n";
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
            ." WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";
    } elseif ($table == '*') {
        return "SELECT * FROM `mysql`.`db`"
            ." WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "'"
            ." AND '" . PMA_Util::unescapeMysqlWildcards($db) . "'"
            ." LIKE `Db`;";
    }
    return "SELECT `Table_priv`"
        ." FROM `mysql`.`tables_priv`"
        ." WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
        ." AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "'"
        ." AND `Db` = '" . PMA_Util::unescapeMysqlWildcards($db) . "'"
        ." AND `Table_name` = '" . PMA_Util::sqlAddSlashes($table) . "';";
}
/**
 * Displays the privileges form table
 *
 * @param string  $db       the database
 * @param string  $table    the table
 * @param boolean $submit   wheather to display the submit button or not
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

        // get columns
        $res = PMA_DBI_try_query(
            'SHOW COLUMNS FROM '
            . PMA_Util::backquote(
                PMA_Util::unescapeMysqlWildcards($db)
            )
            . '.' . PMA_Util::backquote($table) . ';'
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
        $html_output .= PMA_getHtmlForTableSpecificPrivileges(
            $username, $hostname, $db, $table, $columns, $row
        );
    } else {
        // g l o b a l    o r    d b - s p e c i f i c
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
function PMA_getHtmlForDisplayResourceLimits($row)
{
    $html_output = '<fieldset>' . "\n"
        . '<legend>' . __('Resource limits') . '</legend>' . "\n"
        . '<p><small>'
        . '<i>' . __('Note: Setting these options to 0 (zero) removes the limit.')
        . '</i></small></p>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_questions">'
        . '<code><dfn title="'
        . __('Limits the number of queries the user may send to the server per hour.')
        . '">'
        . 'MAX QUERIES PER HOUR'
        . '</dfn></code></label>' . "\n"
        . '<input type="text" name="max_questions" id="text_max_questions" '
        . 'value="' . $row['max_questions'] . '" '
        . 'size="11" maxlength="11" '
        . 'title="'
        . __('Limits the number of queries the user may send to the server per hour.')
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_updates">'
        . '<code><dfn title="'
        . __('Limits the number of commands that change any table or database the user may execute per hour.') . '">'
        . 'MAX UPDATES PER HOUR'
        . '</dfn></code></label>' . "\n"
        . '<input type="text" name="max_updates" id="text_max_updates" '
        . 'value="' . $row['max_updates'] . '" size="11" maxlength="11" '
        . 'title="'
        . __('Limits the number of commands that change any table or database the user may execute per hour.')
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_connections">'
        . '<code><dfn title="'
        . __('Limits the number of new connections the user may open per hour.') . '">'
        . 'MAX CONNECTIONS PER HOUR'
        . '</dfn></code></label>' . "\n"
        . '<input type="text" name="max_connections" id="text_max_connections" '
        . 'value="' . $row['max_connections'] . '" size="11" maxlength="11" '
        . 'title="' . __('Limits the number of new connections the user may open per hour.')
        . '" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="text_max_user_connections">'
        . '<code><dfn title="'
        . __('Limits the number of simultaneous connections the user may have.')
        . '">'
        . 'MAX USER_CONNECTIONS'
        . '</dfn></code></label>' . "\n"
        . '<input type="text" name="max_user_connections" '
        . 'id="text_max_user_connections" '
        . 'value="' . $row['max_user_connections'] . '" size="11" maxlength="11" '
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
 * @param         $row
 *
 * @return string $html_output
 */
function PMA_getHtmlForTableSpecificPrivileges($username, $hostname, $db,
    $table, $columns, $row
) {
    $res = PMA_DBI_query(
        'SELECT `Column_name`, `Column_priv`'
        .' FROM `mysql`.`columns_priv`'
        .' WHERE `User`'
        .' = \'' . PMA_Util::sqlAddSlashes($username) . "'"
        .' AND `Host`'
        .' = \'' . PMA_Util::sqlAddSlashes($hostname) . "'"
        .' AND `Db`'
        .' = \'' . PMA_Util::sqlAddSlashes(
            PMA_Util::unescapeMysqlWildcards($db)
        ) . "'"
        .' AND `Table_name`'
        .' = \'' . PMA_Util::sqlAddSlashes($table) . '\';'
    );

    while ($row1 = PMA_DBI_fetch_row($res)) {
        $row1[1] = explode(',', $row1[1]);
        foreach ($row1[1] as $current) {
            $columns[$row1[0]][$current] = true;
        }
    }
    PMA_DBI_free_result($res);
    unset($res, $row1, $current);

    $html_output = '<input type="hidden" name="grant_count" '
        . 'value="' . count($row) . '" />' . "\n"
        . '<input type="hidden" name="column_count" '
        . 'value="' . count($columns) . '" />' . "\n"
        . '<fieldset id="fieldset_user_priv">' . "\n"
        . '<legend>' . __('Table-specific privileges')
        . PMA_Util::showHint(
            __('Note: MySQL privilege names are expressed in English')
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
 * @param array $row first row from result or boolean false
 *
 * @return string $html_output
 */
function PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn($row)
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
 * @param string $db       the database
 * @param string $table    the table
 * @param string $row      first row from result or boolean false
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
    $html_output .= '<fieldset id="fieldset_user_global_rights"><legend>';
    if ($db == '*') {
        $html_output .= __('Global privileges');
    } else if ($table == '*') {
        $html_output .= __('Database-specific privileges');
    } else {
        $html_output .= __('Table-specific privileges');
    }
    $html_output .= ' (<a href="#" '
        . 'onclick="setCheckboxes(\'fieldset_user_global_rights\', true); '
        . 'return false;">' . __('Check All') . '</a> /'
        . '<a href="#" '
        . 'onclick="setCheckboxes(\'fieldset_user_global_rights\', false); '
        . 'return false;">' . __('Uncheck All') . '</a>)';
    $html_output .= '</legend>';
    $html_output .= '<p><small><i>'
        . __('Note: MySQL privilege names are expressed in English')
        . '</i></small></p>';

    // Output the Global privilege tables with checkboxes
    $html_output .= PMA_getHtmlForGlobalPrivTableWithCheckboxes(
        $privTable, $privTable_names, $row
    );

    // The "Resource limits" box is not displayed for db-specific privs
    if ($db == '*') {
        $html_output .= PMA_getHtmlForDisplayResourceLimits($row);
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
 * @param string $db the table
 *
 * @return string administration privilege table
 */
function PMA_getAdministrationPrivilegeTable($db)
{
    $administration_privTable = array(
        array('Grant',
            'GRANT',
            __('Allows adding users and privileges without reloading the privilege tables.')
        ),
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
                . '<input type="checkbox"'
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
        . '<span class="options">' . "\n";

    $html_output .= '<select name="pred_username" id="select_pred_username" '
        . 'title="' . __('User name') . '"' . "\n";


    $html_output .= '        onchange="'
        . 'if (this.value == \'any\') {'
        . '    username.value = \'\'; '
        . '} else if (this.value == \'userdefined\') {'
        . '    username.focus(); username.select(); '
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
        . ' onchange="pred_username.value = \'userdefined\';" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="select_pred_hostname">' . "\n"
        . '    ' . __('Host') . ':' . "\n"
        . '</label>' . "\n";

    $html_output .= '<span class="options">' . "\n"
        . '    <select name="pred_hostname" id="select_pred_hostname" '
        . 'title="' . __('Host') . '"' . "\n";
    $_current_user = PMA_DBI_fetch_value('SELECT USER();');
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
        . '} else if (this.value == \'userdefined\') {'
        . '    hostname.focus(); hostname.select(); '
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
        . __('Use text field') . ':</option>' . "\n"
        . '</select>' . "\n"
        . '</span>' . "\n";

    $html_output .= '<input type="text" name="hostname" maxlength="'
        . $hostname_length . '" value="'
        . htmlspecialchars(isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '')
        . '" title="' . __('Host')
        . '" onchange="pred_hostname.value = \'userdefined\';" />' . "\n"
        . PMA_Util::showHint(
            __('When Host table is used, this field is ignored and values stored in Host table are used instead.')
        )
        . '</div>' . "\n";

    $html_output .= '<div class="item">' . "\n"
        . '<label for="select_pred_password">' . "\n"
        . '    ' . __('Password') . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '<select name="pred_password" id="select_pred_password" title="'
        . __('Password') . '"' . "\n";

    $html_output .= '            onchange="'
        . 'if (this.value == \'none\') { '
        . '    pma_pw.value = \'\'; pma_pw2.value = \'\'; '
        . '} else if (this.value == \'userdefined\') { '
        . '    pma_pw.focus(); pma_pw.select(); '
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
        . 'onchange="pred_password.value = \'userdefined\';" />' . "\n"
        . '</div>' . "\n";

    $html_output .= '<div class="item" '
        . 'id="div_element_before_generate_password">' . "\n"
        . '<label for="text_pma_pw2">' . "\n"
        . '    ' . __('Re-type') . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">&nbsp;</span>' . "\n"
        . '<input type="password" name="pma_pw2" id="text_pma_pw2" '
        . 'title="' . __('Re-type') . '" '
        . 'onchange="pred_password.value = \'userdefined\';" />' . "\n"
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
    $grants = PMA_DBI_fetch_result(
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
function PMA_getMessageForUpdatePassword($err_url, $username, $hostname)
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

        PMA_DBI_try_query($local_query)
            or PMA_Util::mysqlDie(
                PMA_DBI_getError(), $sql_query, false, $err_url
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

    PMA_DBI_query($sql_query0);
    if (! PMA_DBI_try_query($sql_query1)) {
        // this one may fail, too...
        $sql_query1 = '';
    }
    $sql_query = $sql_query0 . ' ' . $sql_query1;
    $message = PMA_Message::success(
        __('You have revoked the privileges for %s')
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
 * @param string $dbname
 *
 * @return string HTML for addUserForm
 */
function PMA_getHtmlForAddUser($dbname)
{
    $GLOBALS['url_query'] .= '&amp;adduser=1';

    $html_output = '<h2>' . "\n"
       . PMA_Util::getIcon('b_usradd.png') . __('Add user') . "\n"
       . '</h2>' . "\n"
       . '<form name="usersForm" class="ajax" id="addUsersForm"'
       . ' action="server_privileges.php" method="post" autocomplete="off" >' . "\n"
       . PMA_generate_common_hidden_inputs('', '')
       . PMA_getHtmlForDisplayLoginInformationFields('new');

    $html_output .= '<fieldset id="fieldset_add_user_database">' . "\n"
        . '<legend>' . __('Database for user') . '</legend>' . "\n";

    $html_output .= PMA_Util::getCheckbox(
        'createdb-1',
        __('Create database with same name and grant all privileges'),
        false, false
    );
    $html_output .= '<br />' . "\n";
    $html_output .= PMA_Util::getCheckbox(
        'createdb-2',
        __('Grant all privileges on wildcard name (username\\_%)'),
        false, false
    );
    $html_output .= '<br />' . "\n";

    if (! empty($dbname) ) {
        $html_output .= PMA_Util::getCheckbox(
            'createdb-3',
            sprintf(__('Grant all privileges on database &quot;%s&quot;'), htmlspecialchars($dbname)),
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
 * @param string $link_edit         standard link for edit
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 *
 * @return string $html_output
 */
function PMA_getHtmlForSpecificDbPrivileges($link_edit, $conditional_class)
{
    // check the privileges for a particular database.
    $html_output = '<form id="usersForm" action="server_privileges.php">'
        . '<fieldset>' . "\n";
    $html_output .= '<legend>' . "\n"
        . PMA_Util::getIcon('b_usrcheck.png')
        . '    '
        . sprintf(
            __('Users having access to &quot;%s&quot;'),
            '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?'
            . PMA_generate_common_url($_REQUEST['checkprivs']) . '">'
            .  htmlspecialchars($_REQUEST['checkprivs'])
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
    $odd_row = true;
    // now, we build the table...
    list($list_of_privileges, $list_of_compared_privileges)
        = PMA_getListOfPrivilegesAndComparedPrivileges();

    $sql_query = '(SELECT ' . $list_of_privileges . ', `Db`'
        .' FROM `mysql`.`db`'
        .' WHERE \'' . PMA_Util::sqlAddSlashes($_REQUEST['checkprivs'])
        . "'"
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
    $html_output .= PMA_getHtmlTableBodyForSpecificDbPrivs(
        $found, $row, $odd_row, $link_edit, $res
    );
    $html_output .= '</table>'
        . '</fieldset>'
        . '</form>' . "\n";

    if ($GLOBALS['is_ajax_request'] == true && empty($_REQUEST['ajax_page_request'])) {
        $message = PMA_Message::success(__('User has been added.'));
        $response = PMA_Response::getInstance();
        $response->addJSON('message', $message);
        $response->addJSON('user_form', $html_output);
        exit;
    } else {
        // Offer to create a new user for the current database
        $html_output .= '<fieldset id="fieldset_add_user">' . "\n"
           . '<legend>' . _pgettext('Create new user', 'New') . '</legend>' . "\n";

        $html_output .= '<a href="server_privileges.php?'
            . $GLOBALS['url_query'] . '&amp;adduser=1&amp;'
            . 'dbname=' . htmlspecialchars($_REQUEST['checkprivs'])
            .'" rel="'
            .'checkprivs='.htmlspecialchars($_REQUEST['checkprivs'])
            . '&amp;'.$GLOBALS['url_query']
            . '" class="'.$conditional_class
            .'" name="db_specific">' . "\n"
            . PMA_Util::getIcon('b_usradd.png')
            . '        ' . __('Add user') . '</a>' . "\n";

        $html_output .= '</fieldset>' . "\n";
    }
    return $html_output;
}

/**
 * Get HTML snippet for table body of specific database privileges
 *
 * @param boolean $found     whether user found or not
 * @param array   $row       array of rows from mysql,
 *                           db table with list of privileges
 * @param boolean $odd_row   whether odd or not
 * @param string  $link_edit standard link for edit
 * @param string  $res       ran sql query
 *
 * @return string $html_output
 */
function PMA_getHtmlTableBodyForSpecificDbPrivs($found, $row, $odd_row,
    $link_edit, $res
) {
    $html_output = '<tbody>' . "\n";
    if ($found) {
        while (true) {
            // prepare the current user
            $current_privileges = array();
            $current_user = $row['User'];
            $current_host = $row['Host'];
            while ($row
                    && $current_user == $row['User']
                    && $current_host == $row['Host']
            ) {
                $current_privileges[] = $row;
                $row = PMA_DBI_fetch_assoc($res);
            }
            $html_output .= '<tr '
                . 'class="noclick ' . ($odd_row ? 'odd' : 'even')
                . '">' . "\n"
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
                } elseif (
                    $current['Db'] == PMA_Util::escapeMysqlWildcards(
                        $_REQUEST['checkprivs']
                    )
                ) {
                    $html_output .= __('database-specific');
                } else {
                    $html_output .= __('wildcard'). ': '
                        . '<code>' . htmlspecialchars($current['Db']) . '</code>';
                }
                $html_output .= "\n"
                   . '</td>' . "\n";

                $html_output .='<td>' . "\n"
                   . '<code>' . "\n"
                   . ''
                   . join(
                       ',' . "\n" . '                ',
                       PMA_extractPrivInfo($current, true)
                   )
                   . "\n"
                   . '</code>' . "\n"
                   . '</td>' . "\n";

                $html_output .= '<td>' . "\n"
                    . '' . ($current['Grant_priv'] == 'Y' ? __('Yes') : __('No'))
                    . "\n"
                    . '</td>' . "\n"
                    . '<td>' . "\n";
                $html_output .= sprintf(
                    $link_edit,
                    urlencode($current_user),
                    urlencode($current_host),
                    urlencode(
                        ! (isset($current['Db']) || $current['Db'] == '*') ? '' : $current['Db']
                    ),
                    ''
                );
                $html_output .= '</td>' . "\n"
                   . '    </tr>' . "\n";
                if (($i + 1) < count($current_privileges)) {
                    $html_output .= '<tr '
                        . 'class="noclick ' . ($odd_row ? 'odd' : 'even') . '">'
                        . "\n";
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

/**
 * Define some standard links
 * $link_edit, $link_revoke, $link_export
 *
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 *
 * @return array with some standard links
 */
function PMA_getStandardLinks($conditional_class)
{
    $link_edit = '<a class="edit_user_anchor ' . $conditional_class . '"'
        . ' href="server_privileges.php?'
        . str_replace('%', '%%', $GLOBALS['url_query'])
        . '&amp;username=%s'
        . '&amp;hostname=%s'
        . '&amp;dbname=%s'
        . '&amp;tablename=%s">'
        . PMA_Util::getIcon('b_usredit.png', __('Edit Privileges'))
        . '</a>';

    $link_revoke = '<a href='
        .'"server_privileges.php?'
        . str_replace('%', '%%', $GLOBALS['url_query'])
        . '&amp;username=%s'
        . '&amp;hostname=%s'
        . '&amp;dbname=%s'
        . '&amp;tablename=%s'
        . '&amp;revokeall=1">'
        . PMA_Util::getIcon('b_usrdrop.png', __('Revoke'))
        . '</a>';

    $link_export = '<a class="export_user_anchor ' . $conditional_class . '"'
        . ' href="server_privileges.php?'
        . str_replace('%', '%%', $GLOBALS['url_query'])
        . '&amp;username=%s'
        . '&amp;hostname=%s'
        . '&amp;initial=%s'
        . '&amp;export=1">'
        . PMA_Util::getIcon('b_tblexport.png', __('Export'))
        . '</a>';

    return array($link_edit, $link_revoke, $link_export);
}

/**
 * This function return the extra data array for the ajax behavior
 *
 * @param string $password    password
 * @param string $link_export export link
 * @param string $sql_query   sql query
 * @param string $link_edit   standard link for edit
 * @param string $hostname    hostname
 * @param string $username    username
 *
 * @return array $extra_data
 */
function PMA_getExtraDataForAjaxBehavior($password, $link_export, $sql_query,
    $link_edit, $hostname, $username
) {
    if (isset($GLOBALS['dbname'])) {
        //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
        if (preg_match('/(?<!\\\\)(?:_|%)/i', $GLOBALS['dbname'])) {
            $dbname_is_wildcard = true;
        } else {
            $dbname_is_wildcard = false;
        }
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
        $new_user_string = '<tr>'."\n"
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

        $new_user_string .= '</td>'."\n";
        $new_user_string .= '<td>'
            . '<code>' . join(', ', PMA_extractPrivInfo('', true)) . '</code>'
            . '</td>'; //Fill in privileges here
        $new_user_string .= '<td>';

        if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')) {
            $new_user_string .= __('Yes');
        } else {
            $new_user_string .= __('No');
        }

        $new_user_string .='</td>';

        $new_user_string .= '<td>'
            . sprintf(
                $link_edit,
                urlencode($username),
                urlencode($hostname),
                '',
                ''
            )
            . '</td>' . "\n";
        $new_user_string .= '<td>'
            . sprintf(
                $link_export,
                urlencode($username),
                urlencode($hostname),
                (isset($_GET['initial']) ? $_GET['initial'] : '')
            )
            . '</td>' . "\n";

        $new_user_string .= '</tr>';

        $extra_data['new_user_string'] = $new_user_string;

        /**
         * Generate the string for this alphabet's initial, to update the user
         * pagination
         */
        $new_user_initial = strtoupper(substr($username, 0, 1));
        $new_user_initial_string = '<a href="server_privileges.php?'
            . $GLOBALS['url_query'] . '&initial=' . $new_user_initial .'">'
            . $new_user_initial . '</a>';
        $extra_data['new_user_initial'] = $new_user_initial;
        $extra_data['new_user_initial_string'] = $new_user_initial_string;
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
        '2' => __('… revoke all active privileges from the old one and delete it afterwards.'),
        '3' => __('… delete the old one from the user tables and reload the privileges afterwards.'));

    $class = ' ajax';
    $html_output = '<form action="server_privileges.php" '
        . 'method="post" class="copyUserForm' . $class .'">' . "\n"
        . PMA_generate_common_hidden_inputs('', '')
        . '<input type="hidden" name="old_username" '
        . 'value="' . htmlspecialchars($username) . '" />' . "\n"
        . '<input type="hidden" name="old_hostname" '
        . 'value="' . htmlspecialchars($hostname) . '" />' . "\n"
        . '<fieldset id="fieldset_change_copy_user">' . "\n"
        . '<legend>' . __('Change Login Information / Copy User')
        . '</legend>' . "\n"
        . PMA_getHtmlForDisplayLoginInformationFields('change');

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
        . ' <a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?'
        . $GLOBALS['url_query'] . '&amp;db=' . $url_dbname . '&amp;reload=1">'
        . htmlspecialchars($dbname) . ': '
        . PMA_Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabDatabase']
        )
        . "</a> ]\n";

    if (strlen($tablename)) {
        $html_output .= ' [ ' . __('Table') . ' <a href="'
            . $GLOBALS['cfg']['DefaultTabTable'] . '?' . $GLOBALS['url_query']
            . '&amp;db=' . $url_dbname
            . '&amp;table=' . htmlspecialchars(urlencode($tablename))
            . '&amp;reload=1">' . htmlspecialchars($tablename) . ': '
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
 * @param string $user_host_condition a where clause that containd user's host condition
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
            .' LIKE \''
            . PMA_Util::sqlAddSlashes($dbname, true) . "'";
        $tables_to_search_for_users = array('columns_priv',);
        $dbOrTableName = 'Table_name';
    }

    $db_rights_sqls = array();
    foreach ($tables_to_search_for_users as $table_search_in) {
        if (in_array($table_search_in, $tables)) {
            $db_rights_sqls[] = '
                SELECT DISTINCT `' . $dbOrTableName .'`
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
        .' ORDER BY `' . $dbOrTableName .'` ASC';

    $db_rights_result = PMA_DBI_query($db_rights_sql);

    while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
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

    PMA_DBI_free_result($db_rights_result);

    if (! strlen($dbname)) {
        $sql_query = 'SELECT * FROM `mysql`.`db`'
            . $user_host_condition . ' ORDER BY `Db` ASC';
    } else {
        $sql_query = 'SELECT `Table_name`,'
            .' `Table_priv`,'
            .' IF(`Column_priv` = _latin1 \'\', 0, 1)'
            .' AS \'Column_priv\''
            .' FROM `mysql`.`tables_priv`'
            . $user_host_condition
            .' ORDER BY `Table_name` ASC;';
    }

    $result = PMA_DBI_query($sql_query);
    $sql_query = '';

    while ($row = PMA_DBI_fetch_assoc($result)) {
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
    PMA_DBI_free_result($result);
    return $db_rights;
}

/**
 * Display user rights in table rows(Table specific or database specific privs)
 *
 * @param array  $db_rights   user's database rights array
 * @param string $link_edit   standard link to edit privileges
 * @param string $dbname      database name
 * @param string $link_revoke standard link to revoke
 * @param string $hostname    host name
 * @param string $username    username
 *
 * @return array $found_rows, $html_output
 */
function PMA_getHtmlForDisplayUserRightsInRows($db_rights, $link_edit, $dbname,
    $link_revoke, $hostname, $username
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
        //while ($row = PMA_DBI_fetch_assoc($res)) {
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
                        || (strlen($dbname) && in_array('Grant', explode(',', $row['Table_priv']))))
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
            $html_output .= sprintf(
                $link_edit,
                htmlspecialchars(urlencode($username)),
                urlencode(htmlspecialchars($hostname)),
                urlencode(
                    (! strlen($dbname)) ? $row['Db'] : htmlspecialchars($dbname)
                ),
                urlencode((! strlen($dbname)) ? '' : $row['Table_name'])
            );
            $html_output .= '</td>' . "\n"
               . '    <td>';
            if (! empty($row['can_delete'])
                || isset($row['Table_name'])
                && strlen($row['Table_name'])
            ) {
                $html_output .= sprintf(
                    $link_revoke,
                    htmlspecialchars(urlencode($username)),
                    urlencode(htmlspecialchars($hostname)),
                    urlencode(
                        (! strlen($dbname)) ? $row['Db'] : htmlspecialchars($dbname)
                    ),
                    urlencode((! strlen($dbname)) ? '' : $row['Table_name'])
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
 * @param string $username    username
 * @param string $hostname    host name
 * @param string $link_edit   standard link to edit privileges
 * @param string $link_revoke standard link to revoke
 * @param string $dbname      database name
 *
 * @return array $html_output, $found_rows
 */
function PMA_getTableForDisplayAllTableSpecificRights($username, $hostname
    , $link_edit, $link_revoke, $dbname
) {
    // table header
    $html_output = PMA_generate_common_hidden_inputs('', '')
        . '<input type="hidden" name="username" '
        . 'value="' . htmlspecialchars($username) . '" />' . "\n"
        . '<input type="hidden" name="hostname" '
        . 'value="' . htmlspecialchars($hostname) . '" />' . "\n"
        . '<fieldset>' . "\n"
        . '<legend>'
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
    $tables = PMA_DBI_fetch_result('SHOW TABLES FROM `mysql`;');

    /**
     * no db name given, so we want all privs for the given user
     * db name was given, so we want all user specific rights for this db
     */
    $db_rights = PMA_getUserSpecificRights($tables, $user_host_condition, $dbname);

    ksort($db_rights);

    $html_output .= '<tbody>' . "\n";
    // display rows
    list ($found_rows, $html_out) =  PMA_getHtmlForDisplayUserRightsInRows(
        $db_rights, $link_edit, $dbname, $link_revoke, $hostname, $username
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
function PMA_getHtmlForDisplaySelectDbInEditPrivs($found_rows)
{
    $pred_db_array = PMA_DBI_fetch_result('SHOW DATABASES;');

    $html_output = '<label for="text_dbname">'
        . __('Add privileges on the following database') . ':</label>' . "\n";
    if (! empty($pred_db_array)) {
        $html_output .= '<select name="pred_dbname" class="autosubmit">' . "\n"
            . '<option value="" selected="selected">'
            . __('Use text field') . ':</option>' . "\n";
        foreach ($pred_db_array as $current_db) {
            $current_db_show = $current_db;
            $current_db = PMA_Util::escapeMysqlWildcards($current_db);
            // cannot use array_diff() once, outside of the loop,
            // because the list of databases has special characters
            // already escaped in $found_rows,
            // contrary to the output of SHOW DATABASES
            if (empty($found_rows) || ! in_array($current_db, $found_rows)) {
                $html_output .= '<option value="' . htmlspecialchars($current_db) . '">'
                    . htmlspecialchars($current_db_show) . '</option>' . "\n";
            }
        }
        $html_output .= '</select>' . "\n";
    }
    $html_output .= '<input type="text" id="text_dbname" name="dbname" />' . "\n"
        . PMA_Util::showHint(
            __('Wildcards % and _ should be escaped with a \ to use them literally')
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
        '. 'value="' . htmlspecialchars($dbname) . '"/>' . "\n";
    $html_output .= '<label for="text_tablename">'
        . __('Add privileges on the following table') . ':</label>' . "\n";

    $result = @PMA_DBI_try_query(
        'SHOW TABLES FROM ' . PMA_Util::backquote(
            PMA_Util::unescapeMysqlWildcards($dbname)
        ) . ';',
        null,
        PMA_DBI_QUERY_STORE
    );

    if ($result) {
        $pred_tbl_array = array();
        while ($row = PMA_DBI_fetch_row($result)) {
            if (! isset($found_rows) || ! in_array($row[0], $found_rows)) {
                $pred_tbl_array[] = $row[0];
            }
        }
        PMA_DBI_free_result($result);

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
 * @param array  $result            ran sql query
 * @param array  $db_rights         user's database rights array
 * @param string $link_edit         standard link to edit privileges
 * @param string $pmaThemeImage     a image source link
 * @param string $text_dir          text directory
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 * @param string $link_export       standard link to export privileges
 *
 * @return string HTML snippet
 */
function PMA_getUsersOverview($result, $db_rights, $link_edit, $pmaThemeImage,
    $text_dir, $conditional_class, $link_export
) {
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $row['privs'] = PMA_extractPrivInfo($row, true);
        $db_rights[$row['User']][$row['Host']] = $row;
    }
    @PMA_DBI_free_result($result);

    $html_output
        = '<form name="usersForm" id="usersForm" action="server_privileges.php" '
        . 'method="post">' . "\n"
        . PMA_generate_common_hidden_inputs('', '')
        . '<table id="tableuserrights" class="data">' . "\n"
        . '<thead>' . "\n"
        . '<tr><th></th>' . "\n"
        . '<th>' . __('User') . '</th>' . "\n"
        . '<th>' . __('Host') . '</th>' . "\n"
        . '<th>' . __('Password') . '</th>' . "\n"
        . '<th>' . __('Global privileges') . ' '
        . PMA_Util::showHint(
            __('Note: MySQL privilege names are expressed in English')
        )
        . '</th>' . "\n"
        . '<th>' . __('Grant') . '</th>' . "\n"
        . '<th colspan="2">' . __('Action') . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n";

    $html_output .= '<tbody>' . "\n";
    $html_output .= PMA_getTableBodyForUserRightsTable(
        $db_rights, $link_edit, $link_export
    );
    $html_output .= '</tbody>'
        . '</table>' . "\n";

    $html_output .= '<div style="float:left;">'
        .'<img class="selectallarrow"'
        .' src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png"'
        .' width="38" height="22"'
        .' alt="' . __('With selected:') . '" />' . "\n"
        .'<input type="checkbox" id="checkall" title="' . __('Check All') . '" /> '
        .'<label for="checkall">' . __('Check All') . '</label> '
        .'<i style="margin-left: 2em">' . __('With selected:') . '</i>' . "\n";

    $html_output .= PMA_Util::getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_export',
        __('Export'), 'b_tblexport.png', 'export'
    );
    $html_output .= '<input type="hidden" name="initial" '
        . 'value="' . (isset($_GET['initial']) ? $_GET['initial'] : '') . '" />';
    $html_output .= '</div>'
        . '<div class="clear_both" style="clear:both"></div>';

    // add/delete user fieldset
    $html_output .= PMA_getFieldsetForAddDeleteUser($conditional_class);
    $html_output .= '</form>' . "\n";

    return $html_output;
}

/**
 * Get table body for 'tableuserrights' table in userform
 *
 * @param array  $db_rights   user's database rights array
 * @param string $link_edit   standard link to edit privileges
 * @param string $link_export Link for export all users
 *
 * @return string HTML snippet
 */
function PMA_getTableBodyForUserRightsTable($db_rights, $link_edit, $link_export)
{
    $odd_row = true;
    $index_checkbox = -1;
    $html_output = '';
    foreach ($db_rights as $user) {
        $index_checkbox++;
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
                . '</code></td>' . "\n"
                . '<td>'
                . ($host['Grant_priv'] == 'Y' ? __('Yes') : __('No'))
                . '</td>' . "\n"
                . '<td class="center">'
                . sprintf(
                    $link_edit,
                    urlencode($host['User']),
                    urlencode($host['Host']),
                    '',
                    ''
                );
            $html_output .= '</td>';

            $html_output .= '<td class="center">';
            $html_output .= sprintf(
                $link_export,
                urlencode($host['User']),
                urlencode($host['Host']),
                (isset($_GET['initial']) ? $_GET['initial'] : '')
            );
            $html_output .= '</td>';
            $html_output .= '</tr>';
            $odd_row = ! $odd_row;
        }
    }
    return $html_output;
}

/**
 * Get HTML fieldset for Add/Delete user
 *
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 *
 * @return string HTML snippet
 */
function PMA_getFieldsetForAddDeleteUser($conditional_class)
{
    $html_output = '<fieldset id="fieldset_add_user">' . "\n";
    $html_output .= '<a href="server_privileges.php?'
        . $GLOBALS['url_query'] . '&amp;adduser=1"'
        . 'class="' . $conditional_class . '">' . "\n"
        . PMA_Util::getIcon('b_usradd.png')
        . '            ' . __('Add user') . '</a>' . "\n";
    $html_output .= '</fieldset>' . "\n";

    $html_output .= '<fieldset id="fieldset_delete_user">'
        . '<legend>' . "\n"
        . PMA_Util::getIcon('b_usrdrop.png')
        . '            ' . __('Remove selected users') . '' . "\n"
        . '</legend>' . "\n";

    $html_output .= '<input type="hidden" name="mode" value="2" />' . "\n"
        . '(' . __('Revoke all active privileges from the users and delete them afterwards.') . ')'
        . '<br />' . "\n";

    $html_output .= '<input type="checkbox" '
        . 'title="' . __('Drop the databases that have the same names as the users.') . '" '
        . 'name="drop_users_db" id="checkbox_drop_users_db" />' . "\n";

    $html_output .= '<label for="checkbox_drop_users_db" '
        . 'title="' . __('Drop the databases that have the same names as the users.') . '">' . "\n"
        . '            ' . __('Drop the databases that have the same names as the users.') . "\n"
        . '</label>' . "\n"
        . '</fieldset>' . "\n";

    $html_output .= '<fieldset id="fieldset_delete_user_footer" class="tblFooters">' . "\n";
    $html_output .= '<input type="submit" name="delete" '
        . 'value="' . __('Go') . '" id="buttonGo" '
        . 'class="' . $conditional_class . '"/>' . "\n";

    $html_output .= '</fieldset>' . "\n";

    return $html_output;
}

/**
 * Get HTML for Displays the initials
 *
 * @param array  $array_initials    array for all initials, even non A-Z
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 *
 * @return string HTML snippet
 */
function PMA_getHtmlForDisplayTheInitials($array_initials, $conditional_class)
{
    // initialize to false the letters A-Z
    for ($letter_counter = 1; $letter_counter < 27; $letter_counter++) {
        if (! isset($array_initials[chr($letter_counter + 64)])) {
            $array_initials[chr($letter_counter + 64)] = false;
        }
    }

    $initials = PMA_DBI_try_query(
        'SELECT DISTINCT UPPER(LEFT(`User`,1)) FROM `user` ORDER BY `User` ASC',
        null,
        PMA_DBI_QUERY_STORE
    );
    while (list($tmp_initial) = PMA_DBI_fetch_row($initials)) {
        $array_initials[$tmp_initial] = true;
    }

    // Display the initials, which can be any characters, not
    // just letters. For letters A-Z, we add the non-used letters
    // as greyed out.

    uksort($array_initials, "strnatcasecmp");

    $html_output = '<table id="initials_table" <cellspacing="5">'
        . '<tr>';
    foreach ($array_initials as $tmp_initial => $initial_was_found) {
        if (! empty($tmp_initial)) {
            if ($initial_was_found) {
                $html_output .= '<td>'
                    . '<a class="' . $conditional_class . '"'
                    . ' href="server_privileges.php?'
                    . $GLOBALS['url_query'] . '&amp;'
                    . 'initial=' . urlencode($tmp_initial) . '">' . $tmp_initial
                    . '</a>'
                    . '</td>' . "\n";
            } else {
                $html_output .= '<td>' . $tmp_initial . '</td>';
            }
        }
    }
    $html_output .= '<td>'
        . '<a href="server_privileges.php?' . $GLOBALS['url_query']
        . '&amp;showall=1" '
        . 'class="nowrap">[' . __('Show all') . ']</a></td>' . "\n";
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
    $tables = PMA_DBI_fetch_result('SHOW TABLES FROM `mysql`;');

    $tables_to_search_for_users = array(
        'user', 'db', 'tables_priv', 'columns_priv', 'procs_priv',
    );

    $db_rights_sqls = array();
    foreach ($tables_to_search_for_users as $table_search_in) {
        if (in_array($table_search_in, $tables)) {
            $db_rights_sqls[] = 'SELECT DISTINCT `User`, `Host` FROM `mysql`.`'
                . $table_search_in . '` '
                . (isset($_GET['initial']) ? PMA_rangeOfUsers($_GET['initial']) : '');
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
        .' ORDER BY `User` ASC, `Host` ASC';

    $db_rights_result = PMA_DBI_query($db_rights_sql);

    while ($db_rights_row = PMA_DBI_fetch_assoc($db_rights_result)) {
        $db_rights_row = array_merge($user_defaults, $db_rights_row);
        $db_rights[$db_rights_row['User']][$db_rights_row['Host']]
            = $db_rights_row;
    }
    PMA_DBI_free_result($db_rights_result);
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
            || isset($_POST['max_updates']) || isset($_POST['max_user_connections'])))
        ) {
            $sql_query2 .= PMA_getWithClauseForAddUserAndUpdatePrivs();
        }
        $sql_query2 .= ';';
    }
    if (! PMA_DBI_try_query($sql_query0)) {
        // This might fail when the executing user does not have
        // ALL PRIVILEGES himself.
        // See https://sourceforge.net/p/phpmyadmin/bugs/3270/
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
    $message->addParam(
        '\'' . htmlspecialchars($username) . '\'@\'' . htmlspecialchars($hostname) . '\''
    );

    return array($sql_query, $message);
}

/**
 * Get title and textarea for export user definition in Privileges
 *
 * @param string $username username
 * @param string $hostname host name
 *
 * @return array ($title, $export)
 */
function PMA_getHtmlForExportUserDefinition($username, $hostname)
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
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 *
 * @return string html output
 */
function PMA_getAddUserHtmlFieldset($conditional_class)
{
    return '<fieldset id="fieldset_add_user">' . "\n"
        . '<a href="server_privileges.php?' . $GLOBALS['url_query']
        . '&amp;adduser=1" '
        . 'class="' . $conditional_class . '">' . "\n"
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
function PMA_getHtmlHeaderForDisplayUserProperties(
    $dbname_is_wildcard, $url_dbname, $dbname, $username, $hostname, $tablename
) {
    $html_output = '<h2>' . "\n"
       . PMA_Util::getIcon('b_usredit.png')
       . __('Edit Privileges') . ': '
       . __('User');

    if (isset($dbname)) {
        $html_output .= ' <i><a href="server_privileges.php?'
            . $GLOBALS['url_query']
            . '&amp;username=' . htmlspecialchars(urlencode($username))
            . '&amp;hostname=' . htmlspecialchars(urlencode($hostname))
            . '&amp;dbname=&amp;tablename=">\'' . htmlspecialchars($username)
            . '\'@\'' . htmlspecialchars($hostname)
            . '\'</a></i>' . "\n";

        $html_output .= ' - ' . ($dbname_is_wildcard ? __('Databases') : __('Database') );
        if (isset($_REQUEST['tablename'])) {
            $html_output .= ' <i><a href="server_privileges.php?' . $GLOBALS['url_query']
                . '&amp;username=' . htmlspecialchars(urlencode($username))
                . '&amp;hostname=' . htmlspecialchars(urlencode($hostname))
                . '&amp;dbname=' . htmlspecialchars($url_dbname)
                . '&amp;tablename=">' . htmlspecialchars($dbname)
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
 * @param string $link_edit         standard link to edit privileges
 * @param string $pmaThemeImage     a image source link
 * @param string $text_dir          text directory
 * @param string $conditional_class if ajaxable 'Ajax' otherwise ''
 * @param string $link_export       standard link to export privileges
 *
 * @return string $html_output
 */
function PMA_getHtmlForDisplayUserOverviewPage($link_edit, $pmaThemeImage,
    $text_dir, $conditional_class, $link_export
) {
    $html_output = '<h2>' . "\n"
       . PMA_Util::getIcon('b_usrlist.png')
       . __('Users overview') . "\n"
       . '</h2>' . "\n";

    $sql_query = 'SELECT *,' .
        "       IF(`Password` = _latin1 '', 'N', 'Y') AS 'Password'" .
        '  FROM `mysql`.`user`';

    $sql_query .= (isset($_REQUEST['initial'])
        ? PMA_rangeOfUsers($_REQUEST['initial'])
        : '');

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
            $html_output .= PMA_Message::error(__('No Privileges'))->getDisplay();
            PMA_DBI_free_result($res);
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
         * Also not necassary if there is less than 20 privileges
         */
        if (PMA_DBI_num_rows($res) > 20 ) {
            $html_output .= PMA_getHtmlForDisplayTheInitials(
                $array_initials, $conditional_class
            );
        }

        /**
        * Display the user overview
        * (if less than 50 users, display them immediately)
        */
        if (isset($_REQUEST['initial'])
            || isset($_REQUEST['showall'])
            || PMA_DBI_num_rows($res) < 50
        ) {
            $html_output .= PMA_getUsersOverview(
                $res, $db_rights, $link_edit, $pmaThemeImage,
                $text_dir, $conditional_class, $link_export
            );
        } else {
            $html_output .= PMA_getAddUserHtmlFieldset($conditional_class);
        } // end if (display overview)

        if (! $GLOBALS['is_ajax_request'] || ! empty($_REQUEST['ajax_page_request'])) {
            $flushnote = new PMA_Message(
                __('Note: phpMyAdmin gets the users\' privileges directly from MySQL\'s privilege tables. The content of these tables may differ from the privileges the server uses, if they have been changed manually. In this case, you should %sreload the privileges%s before you continue.'),
                PMA_Message::NOTICE
            );
            $flushLink = '<a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;'
                . 'flush_privileges=1" id="reload_privileges_anchor">';
            $flushnote->addParam(
                $flushLink,
                false
            );
            $flushnote->addParam('</a>', false);
            $html_output .= $flushnote->getDisplay();
        }
        return $html_output;
    }
}

/**
 * Get HTML snippet for display user properties
 *
 * @param boolean $dbname_is_wildcard whether database name is wildcard or not
 * @param type    $url_dbname         url database name that urlencode() string
 * @param string  $username           username
 * @param string  $hostname           host name
 * @param string  $link_edit          standard link to edit privileges
 * @param string  $link_revoke        standard link to revoke
 * @param string  $dbname             database name
 * @param string  $tablename          table name
 *
 * @return string $html_output
 */
function PMA_getHtmlForDisplayUserProperties($dbname_is_wildcard,$url_dbname,
    $username, $hostname, $link_edit, $link_revoke, $dbname, $tablename
) {
    $html_output = PMA_getHtmlHeaderForDisplayUserProperties(
        $dbname_is_wildcard, $url_dbname, $dbname, $username, $hostname, $tablename
    );

    $sql = "SELECT '1' FROM `mysql`.`user`"
        . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
        . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";

    $user_does_not_exists = (bool) ! PMA_DBI_fetch_value($sql);

    if ($user_does_not_exists) {
        $html_output .= PMA_Message::error(
            __('The selected user was not found in the privilege table.')
        )->getDisplay();
        $html_output .= PMA_getHtmlForDisplayLoginInformationFields();
            //exit;
    }

    $class = ' class="ajax"';
    $html_output .= '<form' . $class . ' name="usersForm" id="addUsersForm"'
        . ' action="server_privileges.php" method="post">' . "\n";

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
    $html_output .= PMA_generate_common_hidden_inputs($_params);

    $html_output .= PMA_getHtmlToDisplayPrivilegesTable(
        PMA_ifSetOr($dbname, '*', 'length'),
        PMA_ifSetOr($tablename, '*', 'length')
    );

    $html_output .= '</form>' . "\n";

    if (! strlen($tablename) && empty($dbname_is_wildcard)) {

        // no table name was given, display all table specific rights
        // but only if $dbname contains no wildcards

        $html_output .= '<form action="server_privileges.php" '
            . 'id="db_or_table_specific_priv" method="post">' . "\n";

        // unescape wildcards in dbname at table level
        $unescaped_db = PMA_Util::unescapeMysqlWildcards($dbname);
        list($html_rightsTable, $found_rows)
            = PMA_getTableForDisplayAllTableSpecificRights(
                $username, $hostname, $link_edit, $link_revoke, $unescaped_db
            );
        $html_output .= $html_rightsTable;

        if (! strlen($dbname)) {
            // no database name was given, display select db
            $html_output .= PMA_getHtmlForDisplaySelectDbInEditPrivs($found_rows);

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
 * @param string $user_host_condition user host condition to select relevent table privileges
 * @param array  $queries             queries array
 * @param string $username            username
 * @param string $hostname            host name
 *
 * @return array  $queries
 */
function PMA_getTablePrivsQueriesForChangeOrCopyUser($user_host_condition,
    $queries, $username, $hostname
) {
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
            .' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . "'"
            .' AND `Host`'
            .' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . '\''
            .' AND `Db`'
            .' = \'' . PMA_Util::sqlAddSlashes($row['Db']) . "'"
            .' AND `Table_name`'
            .' = \'' . PMA_Util::sqlAddSlashes($row['Table_name']) . "'"
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

        $queries[] = 'GRANT ' . join(', ', $tmp_privs1)
            . ' ON ' . PMA_Util::backquote($row['Db']) . '.'
            . PMA_Util::backquote($row['Table_name'])
            . ' TO \'' . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\''
            . (in_array('Grant', explode(',', $row['Table_priv'])) ? ' WITH GRANT OPTION;' : ';');
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
        .' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . "'"
        .' AND `Host`'
        .' = \'' . PMA_Util::sqlAddSlashes($_REQUEST['old_hostname']) . '\';';

    $res = PMA_DBI_query('SELECT * FROM `mysql`.`db`' . $user_host_condition);

    while ($row = PMA_DBI_fetch_assoc($res)) {
        $queries[] = 'GRANT ' . join(', ', PMA_extractPrivInfo($row))
            .' ON ' . PMA_Util::backquote($row['Db']) . '.*'
            .' TO \'' . PMA_Util::sqlAddSlashes($username)
            . '\'@\'' . PMA_Util::sqlAddSlashes($hostname) . '\''
            . ($row['Grant_priv'] == 'Y' ? ' WITH GRANT OPTION;' : ';');
    }
    PMA_DBI_free_result($res);

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
    if ($_error || ! PMA_DBI_try_query($real_sql_query)) {
        $_REQUEST['createdb-1'] = $_REQUEST['createdb-2']
            = $_REQUEST['createdb-3'] = false;
        $message = PMA_Message::rawError(PMA_DBI_getError());
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
        if (! PMA_DBI_try_query($q)) {
            $message = PMA_Message::rawError(PMA_DBI_getError());
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
        if (! PMA_DBI_try_query($q)) {
            $message = PMA_Message::rawError(PMA_DBI_getError());
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
        if (! PMA_DBI_try_query($q)) {
            $message = PMA_Message::rawError(PMA_DBI_getError());
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
        if (! PMA_DBI_try_query($q)) {
            $message = PMA_Message::rawError(PMA_DBI_getError());
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
