<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions with the Privileges section in pma
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\ChangePassword;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Privileges class
 *
 * @package PhpMyAdmin
 */
class Privileges
{
    /**
     * Get Html for User Group Dialog
     *
     * @param string $username     username
     * @param bool   $is_menuswork Is menuswork set in configuration
     *
     * @return string html
     */
    public static function getHtmlForUserGroupDialog($username, $is_menuswork)
    {
        $html = '';
        if (! empty($_REQUEST['edit_user_group_dialog']) && $is_menuswork) {
            $dialog = self::getHtmlToChooseUserGroup($username);
            $response = Response::getInstance();
            if ($response->isAjax()) {
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
    public static function wildcardEscapeForGrant($dbname, $tablename)
    {
        if (strlen($dbname) === 0) {
            $db_and_table = '*.*';
        } else {
            if (strlen($tablename) > 0) {
                $db_and_table = Util::backquote(
                    Util::unescapeMysqlWildcards($dbname)
                )
                . '.' . Util::backquote($tablename);
            } else {
                $db_and_table = Util::backquote($dbname) . '.*';
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
    public static function rangeOfUsers($initial = '')
    {
        // strtolower() is used because the User field
        // might be BINARY, so LIKE would be case sensitive
        if ($initial === null || $initial === '') {
            return '';
        }

        $ret = " WHERE `User` LIKE '"
            . $GLOBALS['dbi']->escapeString($initial) . "%'"
            . " OR `User` LIKE '"
            . $GLOBALS['dbi']->escapeString(mb_strtolower($initial))
            . "%'";
        return $ret;
    } // end function

    /**
     * Formats privilege name for a display
     *
     * @param array   $privilege Privilege information
     * @param boolean $html      Whether to use HTML
     *
     * @return string
     */
    public static function formatPrivilege(array $privilege, $html)
    {
        if ($html) {
            return '<dfn title="' . $privilege[2] . '">'
                . $privilege[1] . '</dfn>';
        }

        return $privilege[1];
    }

    /**
     * Parses privileges into an array, it modifies the array
     *
     * @param array &$row Results row from
     *
     * @return void
     */
    public static function fillInTablePrivileges(array &$row)
    {
        $row1 = $GLOBALS['dbi']->fetchSingleRow(
            'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
            'ASSOC'
        );
        // note: in MySQL 5.0.3 we get "Create View', 'Show view';
        // the View for Create is spelled with uppercase V
        // the view for Show is spelled with lowercase v
        // and there is a space between the words

        $av_grants = explode(
            '\',\'',
            mb_substr(
                $row1['Type'],
                mb_strpos($row1['Type'], '(') + 2,
                mb_strpos($row1['Type'], ')')
                - mb_strpos($row1['Type'], '(') - 3
            )
        );

        $users_grants = explode(',', $row['Table_priv']);

        foreach ($av_grants as $current_grant) {
            $row[$current_grant . '_priv']
                = in_array($current_grant, $users_grants) ? 'Y' : 'N';
        }
        unset($row['Table_priv']);
    }


    /**
     * Extracts the privilege information of a priv table row
     *
     * @param array|null $row        the row
     * @param boolean    $enableHTML add <dfn> tag with tooltips
     * @param boolean    $tablePrivs whether row contains table privileges
     *
     * @global  resource $user_link the database connection
     *
     * @return array
     */
    public static function extractPrivInfo($row = null, $enableHTML = false, $tablePrivs = false)
    {
        if ($tablePrivs) {
            $grants = self::getTableGrantsArray();
        } else {
            $grants = self::getGrantsArray();
        }

        if (! is_null($row) && isset($row['Table_priv'])) {
            self::fillInTablePrivileges($row);
        }

        $privs = array();
        $allPrivileges = true;
        foreach ($grants as $current_grant) {
            if ((! is_null($row) && isset($row[$current_grant[0]]))
                || (is_null($row) && isset($GLOBALS[$current_grant[0]]))
            ) {
                if ((! is_null($row) && $row[$current_grant[0]] == 'Y')
                    || (is_null($row)
                    && ($GLOBALS[$current_grant[0]] == 'Y'
                    || (is_array($GLOBALS[$current_grant[0]])
                    && count($GLOBALS[$current_grant[0]]) == $_REQUEST['column_count']
                    && empty($GLOBALS[$current_grant[0] . '_none']))))
                ) {
                    $privs[] = self::formatPrivilege($current_grant, $enableHTML);
                } elseif (! empty($GLOBALS[$current_grant[0]])
                    && is_array($GLOBALS[$current_grant[0]])
                    && empty($GLOBALS[$current_grant[0] . '_none'])
                ) {
                    // Required for proper escaping of ` (backtick) in a column name
                    $grant_cols = array_map(
                        function($val) {
                            return Util::backquote($val);
                        },
                        $GLOBALS[$current_grant[0]]
                    );

                    $privs[] = self::formatPrivilege($current_grant, $enableHTML)
                        . ' (' . join(', ', $grant_cols) . ')';
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
    } // end of the 'self::extractPrivInfo()' function

    /**
     * Returns an array of table grants and their descriptions
     *
     * @return array array of table grants
     */
    public static function getTableGrantsArray()
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
     * and relevant grant messages
     *
     * @return array
     */
    public static function getGrantsArray()
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
    public static function getHtmlForColumnPrivileges(array $columns, array $row, $name_for_select,
        $priv_for_header, $name, $name_for_dfn, $name_for_current
    ) {
        $data = array(
            'columns'          => $columns,
            'row'              => $row,
            'name_for_select'  => $name_for_select,
            'priv_for_header'  => $priv_for_header,
            'name'             => $name,
            'name_for_dfn'     => $name_for_dfn,
            'name_for_current' => $name_for_current
        );

        $html_output = Template::get('privileges/column_privileges')
            ->render($data);

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
    public static function getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname)
    {
        if ($db == '*') {
            return "SELECT * FROM `mysql`.`user`"
                . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
                . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "';";
        } elseif ($table == '*') {
            return "SELECT * FROM `mysql`.`db`"
                . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
                . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
                . " AND '" . $GLOBALS['dbi']->escapeString(Util::unescapeMysqlWildcards($db)) . "'"
                . " LIKE `Db`;";
        }
        return "SELECT `Table_priv`"
            . " FROM `mysql`.`tables_priv`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND `Db` = '" . $GLOBALS['dbi']->escapeString(Util::unescapeMysqlWildcards($db)) . "'"
            . " AND `Table_name` = '" . $GLOBALS['dbi']->escapeString($table) . "';";
    }

    /**
     * Displays a dropdown to select the user group
     * with menu items configured to each of them.
     *
     * @param string $username username
     *
     * @return string html to select the user group
     */
    public static function getHtmlToChooseUserGroup($username)
    {
        $relation = new Relation();
        $cfgRelation = $relation->getRelationsParam();
        $groupTable = Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['usergroups']);
        $userTable = Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['users']);

        $userGroup = '';
        if (isset($GLOBALS['username'])) {
            $sql_query = "SELECT `usergroup` FROM " . $userTable
                . " WHERE `username` = '" . $GLOBALS['dbi']->escapeString($username) . "'";
            $userGroup = $GLOBALS['dbi']->fetchValue(
                $sql_query, 0, 0, DatabaseInterface::CONNECT_CONTROL
            );
        }

        $allUserGroups = array('' => '');
        $sql_query = "SELECT DISTINCT `usergroup` FROM " . $groupTable;
        $result = $relation->queryAsControlUser($sql_query, false);
        if ($result) {
            while ($row = $GLOBALS['dbi']->fetchRow($result)) {
                $allUserGroups[$row[0]] = $row[0];
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        // render the template
        $data = array(
            'all_user_groups' => $allUserGroups,
            'user_group'      => $userGroup,
            'params'          => array('username' => $username)
        );
        $html_output = Template::get('privileges/choose_user_group')
            ->render($data);

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
    public static function setUserGroup($username, $userGroup)
    {
        $relation = new Relation();
        $cfgRelation = $relation->getRelationsParam();
        if (empty($cfgRelation['db']) || empty($cfgRelation['users']) || empty($cfgRelation['usergroups'])) {
            return;
        }

        $userTable = Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['users']);

        $sql_query = "SELECT `usergroup` FROM " . $userTable
            . " WHERE `username` = '" . $GLOBALS['dbi']->escapeString($username) . "'";
        $oldUserGroup = $GLOBALS['dbi']->fetchValue(
            $sql_query, 0, 0, DatabaseInterface::CONNECT_CONTROL
        );

        if ($oldUserGroup === false) {
            $upd_query = "INSERT INTO " . $userTable . "(`username`, `usergroup`)"
                . " VALUES ('" . $GLOBALS['dbi']->escapeString($username) . "', "
                . "'" . $GLOBALS['dbi']->escapeString($userGroup) . "')";
        } else {
            if (empty($userGroup)) {
                $upd_query = "DELETE FROM " . $userTable
                    . " WHERE `username`='" . $GLOBALS['dbi']->escapeString($username) . "'";
            } elseif ($oldUserGroup != $userGroup) {
                $upd_query = "UPDATE " . $userTable
                    . " SET `usergroup`='" . $GLOBALS['dbi']->escapeString($userGroup) . "'"
                    . " WHERE `username`='" . $GLOBALS['dbi']->escapeString($username) . "'";
            }
        }
        if (isset($upd_query)) {
            $relation->queryAsControlUser($upd_query);
        }
    }

    /**
     * Displays the privileges form table
     *
     * @param string  $db     the database
     * @param string  $table  the table
     * @param boolean $submit whether to display the submit button or not
     *
     * @global  array     $cfg         the phpMyAdmin configuration
     * @global  resource  $user_link   the database connection
     *
     * @return string html snippet
     */
    public static function getHtmlToDisplayPrivilegesTable($db = '*',
        $table = '*', $submit = true
    ) {
        $html_output = '';
        $sql_query = '';

        if ($db == '*') {
            $table = '*';
        }

        if (isset($GLOBALS['username'])) {
            $username = $GLOBALS['username'];
            $hostname = $GLOBALS['hostname'];
            $sql_query = self::getSqlQueryForDisplayPrivTable(
                $db, $table, $username, $hostname
            );
            $row = $GLOBALS['dbi']->fetchSingleRow($sql_query);
        }
        if (empty($row)) {
            if ($table == '*' && $GLOBALS['dbi']->isSuperuser()) {
                $row = array();
                if ($db == '*') {
                    $sql_query = 'SHOW COLUMNS FROM `mysql`.`user`;';
                } elseif ($table == '*') {
                    $sql_query = 'SHOW COLUMNS FROM `mysql`.`db`;';
                }
                $res = $GLOBALS['dbi']->query($sql_query);
                while ($row1 = $GLOBALS['dbi']->fetchRow($res)) {
                    if (mb_substr($row1[0], 0, 4) == 'max_') {
                        $row[$row1[0]] = 0;
                    } elseif (mb_substr($row1[0], 0, 5) == 'x509_'
                        || mb_substr($row1[0], 0, 4) == 'ssl_'
                    ) {
                        $row[$row1[0]] = '';
                    } else {
                        $row[$row1[0]] = 'N';
                    }
                }
                $GLOBALS['dbi']->freeResult($res);
            } elseif ($table == '*') {
                $row = array();
            } else {
                $row = array('Table_priv' => '');
            }
        }
        if (isset($row['Table_priv'])) {
            self::fillInTablePrivileges($row);

            // get columns
            $res = $GLOBALS['dbi']->tryQuery(
                'SHOW COLUMNS FROM '
                . Util::backquote(
                    Util::unescapeMysqlWildcards($db)
                )
                . '.' . Util::backquote($table) . ';'
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
            $html_output .= self::getHtmlForTableSpecificPrivileges(
                $username, $hostname, $db, $table, $columns, $row
            );
        } else {
            // global or db-specific
            $html_output .= self::getHtmlForGlobalOrDbSpecificPrivs($db, $table, $row);
        }
        $html_output .= '</fieldset>' . "\n";
        if ($submit) {
            $html_output .= '<fieldset id="fieldset_user_privtable_footer" '
                . 'class="tblFooters">' . "\n"
                . '<input type="hidden" name="update_privs" value="1" />' . "\n"
                . '<input type="submit" value="' . __('Go') . '" />' . "\n"
                . '</fieldset>' . "\n";
        }
        return $html_output;
    } // end of the 'PMA_displayPrivTable()' function

    /**
     * Get HTML for "Require"
     *
     * @param array $row privilege array
     *
     * @return string html snippet
     */
    public static function getHtmlForRequires(array $row)
    {
        $specified = (isset($row['ssl_type']) && $row['ssl_type'] == 'SPECIFIED');
        $require_options = array(
            array(
                'name'        => 'ssl_type',
                'value'       => 'NONE',
                'description' => __(
                    'Does not require SSL-encrypted connections.'
                ),
                'label'       => 'REQUIRE NONE',
                'checked'     => ((isset($row['ssl_type'])
                    && ($row['ssl_type'] == 'NONE'
                        || $row['ssl_type'] == ''))
                    ? 'checked="checked"'
                    : ''
                ),
                'disabled'    => false,
                'radio'       => true
            ),
            array(
                'name'        => 'ssl_type',
                'value'       => 'ANY',
                'description' => __(
                    'Requires SSL-encrypted connections.'
                ),
                'label'       => 'REQUIRE SSL',
                'checked'     => (isset($row['ssl_type']) && ($row['ssl_type'] == 'ANY')
                    ? 'checked="checked"'
                    : ''
                ),
                'disabled'    => false,
                'radio'       => true
            ),
            array(
                'name'        => 'ssl_type',
                'value'       => 'X509',
                'description' => __(
                    'Requires a valid X509 certificate.'
                ),
                'label'       => 'REQUIRE X509',
                'checked'     => (isset($row['ssl_type']) && ($row['ssl_type'] == 'X509')
                    ? 'checked="checked"'
                    : ''
                ),
                'disabled'    => false,
                'radio'       => true
            ),
            array(
                'name'        => 'ssl_type',
                'value'       => 'SPECIFIED',
                'description' => '',
                'label'       => 'SPECIFIED',
                'checked'     => ($specified ? 'checked="checked"' : ''),
                'disabled'    => false,
                'radio'       => true
            ),
            array(
                'name'        => 'ssl_cipher',
                'value'       => (isset($row['ssl_cipher'])
                    ? htmlspecialchars($row['ssl_cipher']) : ''
                ),
                'description' => __(
                    'Requires that a specific cipher method be used for a connection.'
                ),
                'label'       => 'REQUIRE CIPHER',
                'checked'     => '',
                'disabled'    => ! $specified,
                'radio'       => false
            ),
            array(
                'name'        => 'x509_issuer',
                'value'       => (isset($row['x509_issuer'])
                    ? htmlspecialchars($row['x509_issuer']) : ''
                ),
                'description' => __(
                    'Requires that a valid X509 certificate issued by this CA be presented.'
                ),
                'label'       => 'REQUIRE ISSUER',
                'checked'     => '',
                'disabled'    => ! $specified,
                'radio'       => false
            ),
            array(
                'name'        => 'x509_subject',
                'value'       => (isset($row['x509_subject'])
                    ? htmlspecialchars($row['x509_subject']) : ''
                ),
                'description' => __(
                    'Requires that a valid X509 certificate with this subject be presented.'
                ),
                'label'       => 'REQUIRE SUBJECT',
                'checked'     => '',
                'disabled'    => ! $specified,
                'radio'       => false
            ),
        );

        $html_output = Template::get('privileges/require_options')
            ->render(array('require_options' => $require_options));

        return $html_output;
    }

    /**
     * Get HTML for "Resource limits"
     *
     * @param array $row first row from result or boolean false
     *
     * @return string html snippet
     */
    public static function getHtmlForResourceLimits(array $row)
    {
        $limits = array(
            array(
                'input_name'  => 'max_questions',
                'name_main'   => 'MAX QUERIES PER HOUR',
                'value'       => (isset($row['max_questions']) ? $row['max_questions'] : '0'),
                'description' => __(
                    'Limits the number of queries the user may send to the server per hour.'
                )
            ),
            array(
                'input_name'  => 'max_updates',
                'name_main'   => 'MAX UPDATES PER HOUR',
                'value'       => (isset($row['max_updates']) ? $row['max_updates'] : '0'),
                'description' => __(
                    'Limits the number of commands that change any table '
                    . 'or database the user may execute per hour.'
                )
            ),
            array(
                'input_name'  => 'max_connections',
                'name_main'   => 'MAX CONNECTIONS PER HOUR',
                'value'       => (isset($row['max_connections']) ? $row['max_connections'] : '0'),
                'description' => __(
                    'Limits the number of new connections the user may open per hour.'
                )
            ),
            array(
                'input_name'  => 'max_user_connections',
                'name_main'   => 'MAX USER_CONNECTIONS',
                'value'       => (isset($row['max_user_connections']) ?
                    $row['max_user_connections'] : '0'),
                'description' => __(
                    'Limits the number of simultaneous connections '
                    . 'the user may have.'
                )
            )
        );

        $html_output = Template::get('privileges/resource_limits')
            ->render(array('limits' => $limits));

        $html_output .= '</fieldset>' . "\n";

        return $html_output;
    }

    /**
     * Get the HTML snippet for routine specific privileges
     *
     * @param string $username   username for database connection
     * @param string $hostname   hostname for database connection
     * @param string $db         the database
     * @param string $routine    the routine
     * @param string $url_dbname url encoded db name
     *
     * @return string $html_output
     */
    public static function getHtmlForRoutineSpecificPrivileges(
        $username, $hostname, $db, $routine, $url_dbname
    ) {
        $header = self::getHtmlHeaderForUserProperties(
            false, $url_dbname, $db, $username, $hostname,
            $routine, 'routine'
        );

        $sql = "SELECT `Proc_priv`"
            . " FROM `mysql`.`procs_priv`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND `Db` = '"
            . $GLOBALS['dbi']->escapeString(Util::unescapeMysqlWildcards($db)) . "'"
            . " AND `Routine_name` LIKE '" . $GLOBALS['dbi']->escapeString($routine) . "';";
        $res = $GLOBALS['dbi']->fetchValue($sql);

        $privs = self::parseProcPriv($res);

        $routineArray   = array(self::getTriggerPrivilegeTable());
        $privTableNames = array(__('Routine'));
        $privCheckboxes = self::getHtmlForGlobalPrivTableWithCheckboxes(
            $routineArray, $privTableNames, $privs
        );

        $data = array(
            'username'       => $username,
            'hostname'       => $hostname,
            'database'       => $db,
            'routine'        => $routine,
            'grant_count'     => count($privs),
            'priv_checkboxes' => $privCheckboxes,
            'header'         => $header,
        );
        $html_output = Template::get('privileges/edit_routine_privileges')
            ->render($data);

        return $html_output;
    }

    /**
     * Get routine privilege table as an array
     *
     * @return privilege type array
     */
    public static function getTriggerPrivilegeTable()
    {
        $routinePrivTable = array(
            array(
                'Grant',
                'GRANT',
                __(
                    'Allows user to give to other users or remove from other users '
                    . 'privileges that user possess on this routine.'
                )
            ),
            array(
                'Alter_routine',
                'ALTER ROUTINE',
                __('Allows altering and dropping this routine.')
            ),
            array(
                'Execute',
                'EXECUTE',
                __('Allows executing this routine.')
            )
        );
        return $routinePrivTable;
    }

    /**
     * Get the HTML snippet for table specific privileges
     *
     * @param string $username username for database connection
     * @param string $hostname hostname for database connection
     * @param string $db       the database
     * @param string $table    the table
     * @param array  $columns  columns array
     * @param array  $row      current privileges row
     *
     * @return string $html_output
     */
    public static function getHtmlForTableSpecificPrivileges(
        $username, $hostname, $db, $table, array $columns, array $row
    ) {
        $res = $GLOBALS['dbi']->query(
            'SELECT `Column_name`, `Column_priv`'
            . ' FROM `mysql`.`columns_priv`'
            . ' WHERE `User`'
            . ' = \'' . $GLOBALS['dbi']->escapeString($username) . "'"
            . ' AND `Host`'
            . ' = \'' . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . ' AND `Db`'
            . ' = \'' . $GLOBALS['dbi']->escapeString(
                Util::unescapeMysqlWildcards($db)
            ) . "'"
            . ' AND `Table_name`'
            . ' = \'' . $GLOBALS['dbi']->escapeString($table) . '\';'
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
            . '<legend data-submenu-label="' . __('Table') . '">' . __('Table-specific privileges')
            . '</legend>'
            . '<p><small><i>'
            . __('Note: MySQL privilege names are expressed in English.')
            . '</i></small></p>';

        // privs that are attached to a specific column
        $html_output .= self::getHtmlForAttachedPrivilegesToTableSpecificColumn(
            $columns, $row
        );

        // privs that are not attached to a specific column
        $html_output .= '<div class="item">' . "\n"
            . self::getHtmlForNotAttachedPrivilegesToTableSpecificColumn($row)
            . '</div>' . "\n";

        // for Safari 2.0.2
        $html_output .= '<div class="clearfloat"></div>' . "\n";

        return $html_output;
    }

    /**
     * Get HTML snippet for privileges that are attached to a specific column
     *
     * @param array $columns columns array
     * @param array $row     first row from result or boolean false
     *
     * @return string $html_output
     */
    public static function getHtmlForAttachedPrivilegesToTableSpecificColumn(array $columns, array $row)
    {
        $html_output = self::getHtmlForColumnPrivileges(
            $columns, $row, 'Select_priv', 'SELECT',
            'select', __('Allows reading data.'), 'Select'
        );

        $html_output .= self::getHtmlForColumnPrivileges(
            $columns, $row, 'Insert_priv', 'INSERT',
            'insert', __('Allows inserting and replacing data.'), 'Insert'
        );

        $html_output .= self::getHtmlForColumnPrivileges(
            $columns, $row, 'Update_priv', 'UPDATE',
            'update', __('Allows changing data.'), 'Update'
        );

        $html_output .= self::getHtmlForColumnPrivileges(
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
    public static function getHtmlForNotAttachedPrivilegesToTableSpecificColumn(array $row)
    {
        $html_output = '';

        foreach ($row as $current_grant => $current_grant_value) {
            $grant_type = substr($current_grant, 0, -5);
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

            $privGlobalName = 'strPrivDesc'
                . mb_substr(
                    $tmp_current_grant,
                    0,
                    (mb_strlen($tmp_current_grant) - 5)
                );
            $html_output .= (isset($GLOBALS[$privGlobalName])
                    ? $GLOBALS[$privGlobalName]
                    : $GLOBALS[$privGlobalName . 'Tbl']
                )
                . '"/>' . "\n";

            $privGlobalName1 = 'strPrivDesc'
                . mb_substr(
                    $tmp_current_grant,
                    0,
                    - 5
                );
            $html_output .= '<label for="checkbox_' . $current_grant
                . '"><code><dfn title="'
                . (isset($GLOBALS[$privGlobalName1])
                    ? $GLOBALS[$privGlobalName1]
                    : $GLOBALS[$privGlobalName1 . 'Tbl']
                )
                . '">'
                . mb_strtoupper(
                    mb_substr(
                        $current_grant,
                        0,
                        -5
                    )
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
     * @param array  $row   first row from result or boolean false
     *
     * @return string $html_output
     */
    public static function getHtmlForGlobalOrDbSpecificPrivs($db, $table, array $row)
    {
        $privTable_names = array(0 => __('Data'),
            1 => __('Structure'),
            2 => __('Administration')
        );
        $privTable = array();
        // d a t a
        $privTable[0] = self::getDataPrivilegeTable($db);

        // s t r u c t u r e
        $privTable[1] = self::getStructurePrivilegeTable($table, $row);

        // a d m i n i s t r a t i o n
        $privTable[2] = self::getAdministrationPrivilegeTable($db);

        $html_output = '<input type="hidden" name="grant_count" value="'
            . (count($privTable[0])
                + count($privTable[1])
                + count($privTable[2])
                - (isset($row['Grant_priv']) ? 1 : 0)
            )
            . '" />';
        if ($db == '*') {
            $legend     = __('Global privileges');
            $menu_label = __('Global');
        } elseif ($table == '*') {
            $legend     = __('Database-specific privileges');
            $menu_label = __('Database');
        } else {
            $legend     = __('Table-specific privileges');
            $menu_label = __('Table');
        }
        $html_output .= '<fieldset id="fieldset_user_global_rights">'
            . '<legend data-submenu-label="' . $menu_label . '">' . $legend
            . '<input type="checkbox" id="addUsersForm_checkall" '
            . 'class="checkall_box" title="' . __('Check all') . '" /> '
            . '<label for="addUsersForm_checkall">' . __('Check all') . '</label> '
            . '</legend>'
            . '<p><small><i>'
            . __('Note: MySQL privilege names are expressed in English.')
            . '</i></small></p>';

        // Output the Global privilege tables with checkboxes
        $html_output .= self::getHtmlForGlobalPrivTableWithCheckboxes(
            $privTable, $privTable_names, $row
        );

        // The "Resource limits" box is not displayed for db-specific privs
        if ($db == '*') {
            $html_output .= self::getHtmlForResourceLimits($row);
            $html_output .= self::getHtmlForRequires($row);
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
    public static function getDataPrivilegeTable($db)
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
    public static function getStructurePrivilegeTable($table, array $row)
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
    public static function getAdministrationPrivilegeTable($db)
    {
        if ($db == '*') {
            $adminPrivTable = array(
                array('Grant',
                    'GRANT',
                    __(
                        'Allows adding users and privileges '
                        . 'without reloading the privilege tables.'
                    )
                ),
            );
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
        else {
            $adminPrivTable = array(
                array('Grant',
                    'GRANT',
                    __(
                        'Allows user to give to other users or remove from other'
                        . ' users the privileges that user possess yourself.'
                    )
               ),
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
     * @param array $privTable      privileges table array
     * @param array $privTableNames names of the privilege tables
     *                              (Data, Structure, Administration)
     * @param array $row            first row from result or boolean false
     *
     * @return string $html_output
     */
    public static function getHtmlForGlobalPrivTableWithCheckboxes(
        array $privTable, array $privTableNames, array $row
    ) {
        return Template::get('privileges/global_priv_table')->render(array(
            'priv_table' => $privTable,
            'priv_table_names' => $privTableNames,
            'row' => $row,
        ));
    }

    /**
     * Gets the currently active authentication plugins
     *
     * @param string $orig_auth_plugin Default Authentication plugin
     * @param string $mode             are we creating a new user or are we just
     *                                 changing  one?
     *                                 (allowed values: 'new', 'edit', 'change_pw')
     * @param string $versions         Is MySQL version newer or older than 5.5.7
     *
     * @return string $html_output
     */
    public static function getHtmlForAuthPluginsDropdown(
        $orig_auth_plugin,
        $mode = 'new',
        $versions = 'new'
    ) {
        $select_id = 'select_authentication_plugin'
            . ($mode =='change_pw' ? '_cp' : '');

        if ($versions == 'new') {
            $active_auth_plugins = self::getActiveAuthPlugins();

            if (isset($active_auth_plugins['mysql_old_password'])) {
                unset($active_auth_plugins['mysql_old_password']);
            }
        } else {
            $active_auth_plugins = array(
                'mysql_native_password' => __('Native MySQL authentication')
            );
        }

        $html_output = Util::getDropdown(
            'authentication_plugin',
            $active_auth_plugins,
            $orig_auth_plugin,
            $select_id
        );

        return $html_output;
    }

    /**
     * Gets the currently active authentication plugins
     *
     * @return array $result  array of plugin names and descriptions
     */
    public static function getActiveAuthPlugins()
    {
        $get_plugins_query = "SELECT `PLUGIN_NAME`, `PLUGIN_DESCRIPTION`"
            . " FROM `information_schema`.`PLUGINS` "
            . "WHERE `PLUGIN_TYPE` = 'AUTHENTICATION';";
        $resultset = $GLOBALS['dbi']->query($get_plugins_query);

        $result = array();

        while ($row = $GLOBALS['dbi']->fetchAssoc($resultset)) {
            // if description is known, enable its translation
            if ('mysql_native_password' == $row['PLUGIN_NAME']) {
                $row['PLUGIN_DESCRIPTION'] = __('Native MySQL authentication');
            } elseif ('sha256_password' == $row['PLUGIN_NAME']) {
                $row['PLUGIN_DESCRIPTION'] = __('SHA256 password authentication');
            }

            $result[$row['PLUGIN_NAME']] = $row['PLUGIN_DESCRIPTION'];
        }

        return $result;
    }

    /**
     * Displays the fields used by the "new user" form as well as the
     * "change login information / copy user" form.
     *
     * @param string $mode     are we creating a new user or are we just
     *                         changing  one? (allowed values: 'new', 'change')
     * @param string $username User name
     * @param string $hostname Host name
     *
     * @global  array      $cfg     the phpMyAdmin configuration
     * @global  resource   $user_link the database connection
     *
     * @return string $html_output  a HTML snippet
     */
    public static function getHtmlForLoginInformationFields(
        $mode = 'new',
        $username = null,
        $hostname = null
    ) {
        list($username_length, $hostname_length) = self::getUsernameAndHostnameLength();

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
            . 'title="' . __('User name') . '">' . "\n";

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

        $html_output .= '<input type="text" name="username" id="pma_username" class="autofocus"'
            . ' maxlength="' . $username_length . '" title="' . __('User name') . '"'
            . (empty($GLOBALS['username'])
               ? ''
               : ' value="' . htmlspecialchars(
                   isset($GLOBALS['new_username'])
                   ? $GLOBALS['new_username']
                   : $GLOBALS['username']
               ) . '"'
            )
            . ((! isset($GLOBALS['pred_username'])
                    || $GLOBALS['pred_username'] == 'userdefined'
                )
                ? 'required="required"'
                : '') . ' />' . "\n";

        $html_output .= '<div id="user_exists_warning"'
            . ' name="user_exists_warning" class="hide">'
            . Message::notice(
                __(
                    'An account already exists with the same username '
                    . 'but possibly a different hostname.'
                )
            )->getDisplay()
            . '</div>';
        $html_output .= '</div>';

        $html_output .= '<div class="item">' . "\n"
            . '<label for="select_pred_hostname">' . "\n"
            . '    ' . __('Host name:') . "\n"
            . '</label>' . "\n";

        $html_output .= '<span class="options">' . "\n"
            . '    <select name="pred_hostname" id="select_pred_hostname" '
            . 'title="' . __('Host name') . '"' . "\n";
        $_current_user = $GLOBALS['dbi']->fetchValue('SELECT USER();');
        if (! empty($_current_user)) {
            $thishost = str_replace(
                "'",
                '',
                mb_substr(
                    $_current_user,
                    (mb_strrpos($_current_user, '@') + 1)
                )
            );
            if ($thishost != 'localhost' && $thishost != '127.0.0.1') {
                $html_output .= ' data-thishost="' . htmlspecialchars($thishost) . '" ';
            } else {
                unset($thishost);
            }
        }
        $html_output .= '>' . "\n";
        unset($_current_user);

        // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
        if (! isset($GLOBALS['pred_hostname']) && isset($GLOBALS['hostname'])) {
            switch (mb_strtolower($GLOBALS['hostname'])) {
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

        $html_output .= '<input type="text" name="hostname" id="pma_hostname" maxlength="'
            . $hostname_length . '" value="'
            // use default value of '%' to match with the default 'Any host'
            . htmlspecialchars(isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '%')
            . '" title="' . __('Host name') . '" '
            . ((isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] == 'userdefined'
                )
                ? 'required="required"'
                : '')
            . ' />' . "\n"
            . Util::showHint(
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
            . __('Password') . '">' . "\n"
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
            . (isset($GLOBALS['username']) ? '' : 'required="required"')
            . '/>' . "\n"
            . '<span>Strength:</span> '
            . '<meter max="4" id="password_strength_meter" name="pw_meter"></meter> '
            . '<span id="password_strength" name="pw_strength"></span>' . "\n"
            . '</div>' . "\n";

        $html_output .= '<div class="item" '
            . 'id="div_element_before_generate_password">' . "\n"
            . '<label for="text_pma_pw2">' . "\n"
            . '    ' . __('Re-type:') . "\n"
            . '</label>' . "\n"
            . '<span class="options">&nbsp;</span>' . "\n"
            . '<input type="password" name="pma_pw2" id="text_pma_pw2" '
            . 'title="' . __('Re-type') . '" '
            . (isset($GLOBALS['username']) ? '' : 'required="required"')
            . '/>' . "\n"
            . '</div>' . "\n"
            . '<div class="item" id="authentication_plugin_div">'
            . '<label for="select_authentication_plugin" >';

        $serverType = Util::getServerType();
        $serverVersion = $GLOBALS['dbi']->getVersion();
        $orig_auth_plugin = self::getCurrentAuthenticationPlugin(
            $mode,
            $username,
            $hostname
        );

        if (($serverType == 'MySQL'
            && $serverVersion >= 50507)
            || ($serverType == 'MariaDB'
            && $serverVersion >= 50200)
        ) {
            $html_output .= __('Authentication Plugin')
            . '</label><span class="options">&nbsp;</span>' . "\n";

            $auth_plugin_dropdown = self::getHtmlForAuthPluginsDropdown(
                $orig_auth_plugin, $mode, 'new'
            );
        } else {
            $html_output .= __('Password Hashing Method')
                . '</label><span class="options">&nbsp;</span>' . "\n";
            $auth_plugin_dropdown = self::getHtmlForAuthPluginsDropdown(
                $orig_auth_plugin, $mode, 'old'
            );
        }
        $html_output .= $auth_plugin_dropdown;

        $html_output .= '<div'
            . ($orig_auth_plugin != 'sha256_password' ? ' class="hide"' : '')
            . ' id="ssl_reqd_warning">'
            . Message::notice(
                __(
                    'This method requires using an \'<i>SSL connection</i>\' '
                    . 'or an \'<i>unencrypted connection that encrypts the password '
                    . 'using RSA</i>\'; while connecting to the server.'
                )
                . Util::showMySQLDocu('sha256-authentication-plugin')
            )
                ->getDisplay()
            . '</div>';

        $html_output .= '</div>' . "\n"
            // Generate password added here via jQuery
           . '</fieldset>' . "\n";

        return $html_output;
    } // end of the 'self::getHtmlForLoginInformationFields()' function

    /**
     * Get username and hostname length
     *
     * @return array username length and hostname length
     */
    public static function getUsernameAndHostnameLength()
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
     * Get current authentication plugin in use - for a user or globally
     *
     * @param string $mode     are we creating a new user or are we just
     *                         changing  one? (allowed values: 'new', 'change')
     * @param string $username User name
     * @param string $hostname Host name
     *
     * @return string authentication plugin in use
     */
    public static function getCurrentAuthenticationPlugin(
        $mode = 'new',
        $username = null,
        $hostname = null
    ) {
        /* Fallback (standard) value */
        $authentication_plugin = 'mysql_native_password';
        $serverVersion = $GLOBALS['dbi']->getVersion();

        if (isset($username) && isset($hostname)
            && $mode == 'change'
        ) {
            $row = $GLOBALS['dbi']->fetchSingleRow(
                'SELECT `plugin` FROM `mysql`.`user` WHERE '
                . '`User` = "' . $username . '" AND `Host` = "' . $hostname . '" LIMIT 1'
            );
            // Table 'mysql'.'user' may not exist for some previous
            // versions of MySQL - in that case consider fallback value
            if (isset($row) && $row) {
                $authentication_plugin = $row['plugin'];
            }
        } elseif ($mode == 'change') {
            list($username, $hostname) = $GLOBALS['dbi']->getCurrentUserAndHost();

            $row = $GLOBALS['dbi']->fetchSingleRow(
                'SELECT `plugin` FROM `mysql`.`user` WHERE '
                . '`User` = "' . $username . '" AND `Host` = "' . $hostname . '"'
            );
            if (isset($row) && $row && ! empty($row['plugin'])) {
                $authentication_plugin = $row['plugin'];
            }
        } elseif ($serverVersion >= 50702) {
            $row = $GLOBALS['dbi']->fetchSingleRow(
                'SELECT @@default_authentication_plugin'
            );
            $authentication_plugin = $row['@@default_authentication_plugin'];
        }

        return $authentication_plugin;
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
    public static function getGrants($user, $host)
    {
        $grants = $GLOBALS['dbi']->fetchResult(
            "SHOW GRANTS FOR '"
            . $GLOBALS['dbi']->escapeString($user) . "'@'"
            . $GLOBALS['dbi']->escapeString($host) . "'"
        );
        $response = '';
        foreach ($grants as $one_grant) {
            $response .= $one_grant . ";\n\n";
        }
        return $response;
    } // end of the 'self::getGrants()' function

    /**
     * Update password and get message for password updating
     *
     * @param string $err_url  error url
     * @param string $username username
     * @param string $hostname hostname
     *
     * @return string $message  success or error message after updating password
     */
    public static function updatePassword($err_url, $username, $hostname)
    {
        // similar logic in user_password.php
        $message = '';

        if (empty($_REQUEST['nopass'])
            && isset($_POST['pma_pw'])
            && isset($_POST['pma_pw2'])
        ) {
            if ($_POST['pma_pw'] != $_POST['pma_pw2']) {
                $message = Message::error(__('The passwords aren\'t the same!'));
            } elseif (empty($_POST['pma_pw']) || empty($_POST['pma_pw2'])) {
                $message = Message::error(__('The password is empty!'));
            }
        }

        // here $nopass could be == 1
        if (empty($message)) {
            $hashing_function = 'PASSWORD';
            $serverType = Util::getServerType();
            $serverVersion = $GLOBALS['dbi']->getVersion();
            $authentication_plugin
                = (isset($_REQUEST['authentication_plugin'])
                ? $_REQUEST['authentication_plugin']
                : self::getCurrentAuthenticationPlugin(
                    'change',
                    $username,
                    $hostname
                ));

            // Use 'ALTER USER ...' syntax for MySQL 5.7.6+
            if ($serverType == 'MySQL'
                && $serverVersion >= 50706
            ) {
                if ($authentication_plugin != 'mysql_old_password') {
                    $query_prefix = "ALTER USER '"
                        . $GLOBALS['dbi']->escapeString($username)
                        . "'@'" . $GLOBALS['dbi']->escapeString($hostname) . "'"
                        . " IDENTIFIED WITH "
                        . $authentication_plugin
                        . " BY '";
                } else {
                    $query_prefix = "ALTER USER '"
                        . $GLOBALS['dbi']->escapeString($username)
                        . "'@'" . $GLOBALS['dbi']->escapeString($hostname) . "'"
                        . " IDENTIFIED BY '";
                }

                // in $sql_query which will be displayed, hide the password
                $sql_query = $query_prefix . "*'";

                $local_query = $query_prefix
                    . $GLOBALS['dbi']->escapeString($_POST['pma_pw']) . "'";
            } elseif ($serverType == 'MariaDB' && $serverVersion >= 10000) {
                // MariaDB uses "SET PASSWORD" syntax to change user password.
                // On Galera cluster only DDL queries are replicated, since
                // users are stored in MyISAM storage engine.
                $query_prefix = "SET PASSWORD FOR  '"
                    . $GLOBALS['dbi']->escapeString($username)
                    . "'@'" . $GLOBALS['dbi']->escapeString($hostname) . "'"
                    . " = PASSWORD ('";
                $sql_query = $local_query = $query_prefix
                    . $GLOBALS['dbi']->escapeString($_POST['pma_pw']) . "')";
            } elseif ($serverType == 'MariaDB'
                && $serverVersion >= 50200
                && $GLOBALS['dbi']->isSuperuser()
            ) {
                // Use 'UPDATE `mysql`.`user` ...' Syntax for MariaDB 5.2+
                if ($authentication_plugin == 'mysql_native_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'mysql_native_password' type
                    $GLOBALS['dbi']->tryQuery('SET old_passwords = 0;');

                } elseif ($authentication_plugin == 'sha256_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'sha256_password' type
                    $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 2;');
                }

                $hashedPassword = self::getHashedPassword($_POST['pma_pw']);

                $sql_query        = 'SET PASSWORD FOR \''
                    . $GLOBALS['dbi']->escapeString($username)
                    . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\' = '
                    . (($_POST['pma_pw'] == '')
                        ? '\'\''
                        : $hashing_function . '(\''
                        . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

                $local_query = "UPDATE `mysql`.`user` SET "
                    . " `authentication_string` = '" . $hashedPassword
                    . "', `Password` = '', "
                    . " `plugin` = '" . $authentication_plugin . "'"
                    . " WHERE `User` = '" . $username . "' AND Host = '"
                    . $hostname . "';";
            } else {
                // USE 'SET PASSWORD ...' syntax for rest of the versions
                // Backup the old value, to be reset later
                $row = $GLOBALS['dbi']->fetchSingleRow(
                    'SELECT @@old_passwords;'
                );
                $orig_value = $row['@@old_passwords'];
                $update_plugin_query = "UPDATE `mysql`.`user` SET"
                    . " `plugin` = '" . $authentication_plugin . "'"
                    . " WHERE `User` = '" . $username . "' AND Host = '"
                    . $hostname . "';";

                // Update the plugin for the user
                if (!($GLOBALS['dbi']->tryQuery($update_plugin_query))) {
                    Util::mysqlDie(
                        $GLOBALS['dbi']->getError(),
                        $update_plugin_query,
                        false, $err_url
                    );
                }
                $GLOBALS['dbi']->tryQuery("FLUSH PRIVILEGES;");

                if ($authentication_plugin == 'mysql_native_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'mysql_native_password' type
                    $GLOBALS['dbi']->tryQuery('SET old_passwords = 0;');
                } elseif ($authentication_plugin == 'sha256_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'sha256_password' type
                    $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 2;');
                }
                $sql_query        = 'SET PASSWORD FOR \''
                    . $GLOBALS['dbi']->escapeString($username)
                    . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\' = '
                    . (($_POST['pma_pw'] == '')
                        ? '\'\''
                        : $hashing_function . '(\''
                        . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

                $local_query      = 'SET PASSWORD FOR \''
                    . $GLOBALS['dbi']->escapeString($username)
                    . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\' = '
                    . (($_POST['pma_pw'] == '') ? '\'\'' : $hashing_function
                    . '(\'' . $GLOBALS['dbi']->escapeString($_POST['pma_pw']) . '\')');
            }

            if (!($GLOBALS['dbi']->tryQuery($local_query))) {
                Util::mysqlDie(
                    $GLOBALS['dbi']->getError(), $sql_query, false, $err_url
                );
            }
            // Flush privileges after successful password change
            $GLOBALS['dbi']->tryQuery("FLUSH PRIVILEGES;");

            $message = Message::success(
                __('The password for %s was changed successfully.')
            );
            $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');
            if (isset($orig_value)) {
                $GLOBALS['dbi']->tryQuery(
                    'SET `old_passwords` = ' . $orig_value . ';'
                );
            }
        }
        return $message;
    }

    /**
     * Revokes privileges and get message and SQL query for privileges revokes
     *
     * @param string $dbname    database name
     * @param string $tablename table name
     * @param string $username  username
     * @param string $hostname  host name
     * @param string $itemType  item type
     *
     * @return array ($message, $sql_query)
     */
    public static function getMessageAndSqlQueryForPrivilegesRevoke($dbname,
        $tablename, $username, $hostname, $itemType
    ) {
        $db_and_table = self::wildcardEscapeForGrant($dbname, $tablename);

        $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $itemType . ' ' . $db_and_table
            . ' FROM \''
            . $GLOBALS['dbi']->escapeString($username) . '\'@\''
            . $GLOBALS['dbi']->escapeString($hostname) . '\';';

        $sql_query1 = 'REVOKE GRANT OPTION ON ' . $itemType . ' ' . $db_and_table
            . ' FROM \'' . $GLOBALS['dbi']->escapeString($username) . '\'@\''
            . $GLOBALS['dbi']->escapeString($hostname) . '\';';

        $GLOBALS['dbi']->query($sql_query0);
        if (! $GLOBALS['dbi']->tryQuery($sql_query1)) {
            // this one may fail, too...
            $sql_query1 = '';
        }
        $sql_query = $sql_query0 . ' ' . $sql_query1;
        $message = Message::success(
            __('You have revoked the privileges for %s.')
        );
        $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');

        return array($message, $sql_query);
    }

    /**
     * Get REQUIRE cluase
     *
     * @return string REQUIRE clause
     */
    public static function getRequireClause()
    {
        $arr = isset($_POST['ssl_type']) ? $_POST : $GLOBALS;
        if (isset($arr['ssl_type']) && $arr['ssl_type'] == 'SPECIFIED') {
            $require = array();
            if (! empty($arr['ssl_cipher'])) {
                $require[] = "CIPHER '"
                        . $GLOBALS['dbi']->escapeString($arr['ssl_cipher']) . "'";
            }
            if (! empty($arr['x509_issuer'])) {
                $require[] = "ISSUER '"
                        . $GLOBALS['dbi']->escapeString($arr['x509_issuer']) . "'";
            }
            if (! empty($arr['x509_subject'])) {
                $require[] = "SUBJECT '"
                        . $GLOBALS['dbi']->escapeString($arr['x509_subject']) . "'";
            }
            if (count($require)) {
                $require_clause = " REQUIRE " . implode(" AND ", $require);
            } else {
                $require_clause = " REQUIRE NONE";
            }
        } elseif (isset($arr['ssl_type']) && $arr['ssl_type'] == 'X509') {
            $require_clause = " REQUIRE X509";
        } elseif (isset($arr['ssl_type']) && $arr['ssl_type'] == 'ANY') {
            $require_clause = " REQUIRE SSL";
        } else {
            $require_clause = " REQUIRE NONE";
        }

        return $require_clause;
    }

    /**
     * Get a WITH clause for 'update privileges' and 'add user'
     *
     * @return string $sql_query
     */
    public static function getWithClauseForAddUserAndUpdatePrivs()
    {
        $sql_query = '';
        if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')
            || (isset($GLOBALS['Grant_priv']) && $GLOBALS['Grant_priv'] == 'Y')
        ) {
            $sql_query .= ' GRANT OPTION';
        }
        if (isset($_POST['max_questions']) || isset($GLOBALS['max_questions'])) {
            $max_questions = isset($_POST['max_questions'])
                ? (int)$_POST['max_questions'] : (int)$GLOBALS['max_questions'];
            $max_questions = max(0, $max_questions);
            $sql_query .= ' MAX_QUERIES_PER_HOUR ' . $max_questions;
        }
        if (isset($_POST['max_connections']) || isset($GLOBALS['max_connections'])) {
            $max_connections = isset($_POST['max_connections'])
                ? (int)$_POST['max_connections'] : (int)$GLOBALS['max_connections'];
            $max_connections = max(0, $max_connections);
            $sql_query .= ' MAX_CONNECTIONS_PER_HOUR ' . $max_connections;
        }
        if (isset($_POST['max_updates']) || isset($GLOBALS['max_updates'])) {
            $max_updates = isset($_POST['max_updates'])
                ? (int)$_POST['max_updates'] : (int)$GLOBALS['max_updates'];
            $max_updates = max(0, $max_updates);
            $sql_query .= ' MAX_UPDATES_PER_HOUR ' . $max_updates;
        }
        if (isset($_POST['max_user_connections'])
            || isset($GLOBALS['max_user_connections'])
        ) {
            $max_user_connections = isset($_POST['max_user_connections'])
                ? (int)$_POST['max_user_connections']
                : (int)$GLOBALS['max_user_connections'];
            $max_user_connections = max(0, $max_user_connections);
            $sql_query .= ' MAX_USER_CONNECTIONS ' . $max_user_connections;
        }
        return ((!empty($sql_query)) ? ' WITH' . $sql_query : '');
    }

    /**
     * Get HTML for addUsersForm, This function call if isset($_REQUEST['adduser'])
     *
     * @param string $dbname database name
     *
     * @return string HTML for addUserForm
     */
    public static function getHtmlForAddUser($dbname)
    {
        $html_output = '<h2>' . "\n"
           . Util::getIcon('b_usradd') . __('Add user account') . "\n"
           . '</h2>' . "\n"
           . '<form name="usersForm" id="addUsersForm"'
           . ' onsubmit="return checkAddUser(this);"'
           . ' action="server_privileges.php" method="post" autocomplete="off" >' . "\n"
           . Url::getHiddenInputs('', '')
           . self::getHtmlForLoginInformationFields('new');

        $html_output .= '<fieldset id="fieldset_add_user_database">' . "\n"
            . '<legend>' . __('Database for user account') . '</legend>' . "\n";

        $html_output .= Template::get('checkbox')
            ->render(
                array(
                    'html_field_name'   => 'createdb-1',
                    'label'             => __('Create database with same name and grant all privileges.'),
                    'checked'           => false,
                    'onclick'           => false,
                    'html_field_id'     => 'createdb-1',
                )
            );
        $html_output .= '<br />' . "\n";
        $html_output .= Template::get('checkbox')
            ->render(
                array(
                    'html_field_name'   => 'createdb-2',
                    'label'             => __('Grant all privileges on wildcard name (username\\_%).'),
                    'checked'           => false,
                    'onclick'           => false,
                    'html_field_id'     => 'createdb-2',
                )
            );
        $html_output .= '<br />' . "\n";

        if (! empty($dbname) ) {
            $html_output .= Template::get('checkbox')
                ->render(
                    array(
                        'html_field_name'   => 'createdb-3',
                        'label'             => sprintf(__('Grant all privileges on database %s.'), htmlspecialchars($dbname)),
                        'checked'           => true,
                        'onclick'           => false,
                        'html_field_id'     => 'createdb-3',
                    )
                );
            $html_output .= '<input type="hidden" name="dbname" value="'
                . htmlspecialchars($dbname) . '" />' . "\n";
            $html_output .= '<br />' . "\n";
        }

        $html_output .= '</fieldset>' . "\n";
        if ($GLOBALS['is_grantuser']) {
            $html_output .= self::getHtmlToDisplayPrivilegesTable('*', '*', false);
        }
        $html_output .= '<fieldset id="fieldset_add_user_footer" class="tblFooters">'
            . "\n"
            . '<input type="hidden" name="adduser_submit" value="1" />' . "\n"
            . '<input type="submit" id="adduser_submit" value="' . __('Go') . '" />'
            . "\n"
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
    public static function getListOfPrivilegesAndComparedPrivileges()
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

        $list_of_privileges .=
            ', `Event_priv`, '
            . '`Trigger_priv`';
        $listOfComparedPrivs .=
            ' AND `Event_priv` = \'N\''
            . ' AND `Trigger_priv` = \'N\'';
        return array($list_of_privileges, $listOfComparedPrivs);
    }

    /**
     * Get the HTML for routine based privileges
     *
     * @param string $db             database name
     * @param string $index_checkbox starting index for rows to be added
     *
     * @return string $html_output
     */
    public static function getHtmlTableBodyForSpecificDbRoutinePrivs($db, $index_checkbox)
    {
        $sql_query = 'SELECT * FROM `mysql`.`procs_priv` WHERE Db = \'' . $GLOBALS['dbi']->escapeString($db) . '\';';
        $res = $GLOBALS['dbi']->query($sql_query);
        $html_output = '';
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {

            $html_output .= '<tr>';

            $html_output .= '<td';
            $value = htmlspecialchars($row['User'] . '&amp;#27;' . $row['Host']);
            $html_output .= '>';
            $html_output .= '<input type="checkbox" class="checkall" '
                . 'name="selected_usr[]" '
                . 'id="checkbox_sel_users_' . ($index_checkbox++) . '" '
                . 'value="' . $value . '" /></td>';

            $html_output .= '<td>' . htmlspecialchars($row['User'])
                . '</td>'
                . '<td>' . htmlspecialchars($row['Host'])
                . '</td>'
                . '<td>' . 'routine'
                . '</td>'
                . '<td>' . '<code>' . htmlspecialchars($row['Routine_name']) . '</code>'
                . '</td>'
                . '<td>' . 'Yes'
                . '</td>';
            $current_user = $row['User'];
            $current_host = $row['Host'];
            $routine = $row['Routine_name'];
            $html_output .= '<td>';
            if ($GLOBALS['is_grantuser']) {
                $specific_db = (isset($row['Db']) && $row['Db'] != '*')
                    ? $row['Db'] : '';
                $specific_table = (isset($row['Table_name'])
                    && $row['Table_name'] != '*')
                    ? $row['Table_name'] : '';
                $html_output .= self::getUserLink(
                    'edit',
                    $current_user,
                    $current_host,
                    $specific_db,
                    $specific_table,
                    $routine
                );
            }
            $html_output .= '</td>';
            $html_output .= '<td>';
            $html_output .= self::getUserLink(
                'export',
                $current_user,
                $current_host,
                $specific_db,
                $specific_table,
                $routine
            );
            $html_output .= '</td>';

            $html_output .= '</tr>';

        }
        return $html_output;
    }

    /**
     * Get the HTML for user form and check the privileges for a particular database.
     *
     * @param string $db database name
     *
     * @return string $html_output
     */
    public static function getHtmlForSpecificDbPrivileges($db)
    {
        $html_output = '';

        if ($GLOBALS['dbi']->isSuperuser()) {
            // check the privileges for a particular database.
            $html_output  = '<form id="usersForm" action="server_privileges.php">';
            $html_output .= Url::getHiddenInputs($db);
            $html_output .= '<div class="width100">';
            $html_output .= '<fieldset>';
            $html_output .= '<legend>' . "\n"
                . Util::getIcon('b_usrcheck')
                . '    '
                . sprintf(
                    __('Users having access to "%s"'),
                    '<a href="' . Util::getScriptNameForOption(
                        $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
                    )
                    . Url::getCommon(array('db' => $db)) . '">'
                    .  htmlspecialchars($db)
                    . '</a>'
                )
                . "\n"
                . '</legend>' . "\n";

            $html_output .= '<div class="responsivetable jsresponsive">';
            $html_output .= '<table id="dbspecificuserrights" class="data">';
            $html_output .= self::getHtmlForPrivsTableHead();
            $privMap = self::getPrivMap($db);
            $html_output .= self::getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
            $html_output .= '</table>';
            $html_output .= '</div>';

            $html_output .= '<div class="floatleft">';
            $html_output .= Template::get('select_all')
                ->render(
                    array(
                        'pma_theme_image' => $GLOBALS['pmaThemeImage'],
                        'text_dir'        => $GLOBALS['text_dir'],
                        'form_name'       => "usersForm",
                    )
                );
            $html_output .= Util::getButtonOrImage(
                'submit_mult', 'mult_submit',
                __('Export'), 'b_tblexport', 'export'
            );

            $html_output .= '</fieldset>';
            $html_output .= '</div>';
            $html_output .= '</form>';
        } else {
            $html_output .= self::getHtmlForViewUsersError();
        }

        $response = Response::getInstance();
        if ($response->isAjax() == true
            && empty($_REQUEST['ajax_page_request'])
        ) {
            $message = Message::success(__('User has been added.'));
            $response->addJSON('message', $message);
            $response->addJSON('user_form', $html_output);
            exit;
        } else {
            // Offer to create a new user for the current database
            $html_output .= self::getAddUserHtmlFieldset($db);
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
    public static function getHtmlForSpecificTablePrivileges($db, $table)
    {
        $html_output = '';
        if ($GLOBALS['dbi']->isSuperuser()) {
            // check the privileges for a particular table.
            $html_output  = '<form id="usersForm" action="server_privileges.php">';
            $html_output .= Url::getHiddenInputs($db, $table);
            $html_output .= '<fieldset>';
            $html_output .= '<legend>'
                . Util::getIcon('b_usrcheck')
                . sprintf(
                    __('Users having access to "%s"'),
                    '<a href="' . Util::getScriptNameForOption(
                        $GLOBALS['cfg']['DefaultTabTable'], 'table'
                    )
                    . Url::getCommon(
                        array(
                            'db' => $db,
                            'table' => $table,
                        )
                    ) . '">'
                    .  htmlspecialchars($db) . '.' . htmlspecialchars($table)
                    . '</a>'
                )
                . '</legend>';

            $html_output .= '<div class="responsivetable jsresponsive">';
            $html_output .= '<table id="tablespecificuserrights" class="data">';
            $html_output .= self::getHtmlForPrivsTableHead();
            $privMap = self::getPrivMap($db);
            $sql_query = "SELECT `User`, `Host`, `Db`,"
                . " 't' AS `Type`, `Table_name`, `Table_priv`"
                . " FROM `mysql`.`tables_priv`"
                . " WHERE '" . $GLOBALS['dbi']->escapeString($db) . "' LIKE `Db`"
                . "     AND '" . $GLOBALS['dbi']->escapeString($table) . "' LIKE `Table_name`"
                . "     AND NOT (`Table_priv` = '' AND Column_priv = '')"
                . " ORDER BY `User` ASC, `Host` ASC, `Db` ASC, `Table_priv` ASC;";
            $res = $GLOBALS['dbi']->query($sql_query);
            self::mergePrivMapFromResult($privMap, $res);
            $html_output .= self::getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
            $html_output .= '</table></div>';

            $html_output .= '<div class="floatleft">';
            $html_output .= Template::get('select_all')
                ->render(
                    array(
                        'pma_theme_image' => $GLOBALS['pmaThemeImage'],
                        'text_dir'        => $GLOBALS['text_dir'],
                        'form_name'       => "usersForm",
                    )
                );
            $html_output .= Util::getButtonOrImage(
                'submit_mult', 'mult_submit',
                __('Export'), 'b_tblexport', 'export'
            );

            $html_output .= '</fieldset>';
            $html_output .= '</form>';
        } else {
            $html_output .= self::getHtmlForViewUsersError();
        }
        // Offer to create a new user for the current database
        $html_output .= self::getAddUserHtmlFieldset($db, $table);
        return $html_output;
    }

    /**
     * gets privilege map
     *
     * @param string $db the database
     *
     * @return array $privMap the privilege map
     */
    public static function getPrivMap($db)
    {
        list($listOfPrivs, $listOfComparedPrivs)
            = self::getListOfPrivilegesAndComparedPrivileges();
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
            . " WHERE '" . $GLOBALS['dbi']->escapeString($db) . "' LIKE `Db`"
            . "     AND NOT (" . $listOfComparedPrivs . ")"
            . ")"
            . " ORDER BY `User` ASC, `Host` ASC, `Db` ASC;";
        $res = $GLOBALS['dbi']->query($sql_query);
        $privMap = array();
        self::mergePrivMapFromResult($privMap, $res);
        return $privMap;
    }

    /**
     * merge privilege map and rows from resultset
     *
     * @param array  &$privMap the privilege map reference
     * @param object $result   the resultset of query
     *
     * @return void
     */
    public static function mergePrivMapFromResult(array &$privMap, $result)
    {
        while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
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
    }

    /**
     * Get HTML snippet for privileges table head
     *
     * @return string $html_output
     */
    public static function getHtmlForPrivsTableHead()
    {
        return '<thead>'
            . '<tr>'
            . '<th></th>'
            . '<th>' . __('User name') . '</th>'
            . '<th>' . __('Host name') . '</th>'
            . '<th>' . __('Type') . '</th>'
            . '<th>' . __('Privileges') . '</th>'
            . '<th>' . __('Grant') . '</th>'
            . '<th colspan="2">' . __('Action') . '</th>'
            . '</tr>'
            . '</thead>';
    }

    /**
     * Get HTML error for View Users form
     * For non superusers such as grant/create users
     *
     * @return string $html_output
     */
    public static function getHtmlForViewUsersError()
    {
        return Message::error(
            __('Not enough privilege to view users.')
        )->getDisplay();
    }

    /**
     * Get HTML snippet for table body of specific database or table privileges
     *
     * @param array  $privMap privilege map
     * @param string $db      database
     *
     * @return string $html_output
     */
    public static function getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db)
    {
        $html_output = '<tbody>';
        $index_checkbox = 0;
        if (empty($privMap)) {
            $html_output .= '<tr>'
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
                $html_output .= '<tr>';

                $value = htmlspecialchars($current_user . '&amp;#27;' . $current_host);
                $html_output .= '<td';
                if ($nbPrivileges > 1) {
                    $html_output .= ' rowspan="' . $nbPrivileges . '"';
                }
                $html_output .= '>';
                $html_output .= '<input type="checkbox" class="checkall" '
                    . 'name="selected_usr[]" '
                    . 'id="checkbox_sel_users_' . ($index_checkbox++) . '" '
                    . 'value="' . $value . '" /></td>' . "\n";

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

                $html_output .= self::getHtmlListOfPrivs(
                    $db, $current_privileges, $current_user,
                    $current_host
                );
            }
        }

        //For fetching routine based privileges
        $html_output .= self::getHtmlTableBodyForSpecificDbRoutinePrivs($db, $index_checkbox);
        $html_output .= '</tbody>';

        return $html_output;
    }

    /**
     * Get HTML to display privileges
     *
     * @param string $db                 Database name
     * @param array  $current_privileges List of privileges
     * @param string $current_user       Current user
     * @param string $current_host       Current host
     *
     * @return string HTML to display privileges
     */
    public static function getHtmlListOfPrivs(
        $db, array $current_privileges, $current_user,
        $current_host
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
                if ($current['Db'] == Util::escapeMysqlWildcards($db)) {
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
                $grantsArr = self::getTableGrantsArray();
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
                        self::extractPrivInfo($privs, true, true)
                    )
                    . '</code>';
            } else {
                $html_output .= '<code>'
                    . join(
                        ',',
                        self::extractPrivInfo($current, true, false)
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
            if ($GLOBALS['is_grantuser']) {
                $html_output .= self::getUserLink(
                    'edit',
                    $current_user,
                    $current_host,
                    $specific_db,
                    $specific_table
                );
            }
            $html_output .= '</td>';
            $html_output .= '<td class="center">'
                . self::getUserLink(
                    'export',
                    $current_user,
                    $current_host,
                    $specific_db,
                    $specific_table
                )
                . '</td>';

            $html_output .= '</tr>';
            if (($i + 1) < $nbPrivileges) {
                $html_output .= '<tr class="noclick">';
            }
        }
        return $html_output;
    }

    /**
     * Returns edit, revoke or export link for a user.
     *
     * @param string $linktype    The link type (edit | revoke | export)
     * @param string $username    User name
     * @param string $hostname    Host name
     * @param string $dbname      Database name
     * @param string $tablename   Table name
     * @param string $routinename Routine name
     * @param string $initial     Initial value
     *
     * @return string HTML code with link
     */
    public static function getUserLink(
        $linktype, $username, $hostname, $dbname = '',
        $tablename = '', $routinename = '', $initial = ''
    ) {
        $html = '<a';
        switch($linktype) {
        case 'edit':
            $html .= ' class="edit_user_anchor"';
            break;
        case 'export':
            $html .= ' class="export_user_anchor ajax"';
            break;
        }
        $params = array(
            'username' => $username,
            'hostname' => $hostname
        );
        switch($linktype) {
        case 'edit':
            $params['dbname'] = $dbname;
            $params['tablename'] = $tablename;
            $params['routinename'] = $routinename;
            break;
        case 'revoke':
            $params['dbname'] = $dbname;
            $params['tablename'] = $tablename;
            $params['routinename'] = $routinename;
            $params['revokeall'] = 1;
            break;
        case 'export':
            $params['initial'] = $initial;
            $params['export'] = 1;
            break;
        }

        $html .= ' href="server_privileges.php'
            . Url::getCommon($params)
            . '">';

        switch($linktype) {
        case 'edit':
            $html .= Util::getIcon('b_usredit', __('Edit privileges'));
            break;
        case 'revoke':
            $html .= Util::getIcon('b_usrdrop', __('Revoke'));
            break;
        case 'export':
            $html .= Util::getIcon('b_tblexport', __('Export'));
            break;
        }
        $html .= '</a>';

        return $html;
    }

    /**
     * Returns user group edit link
     *
     * @param string $username User name
     *
     * @return string HTML code with link
     */
    public static function getUserGroupEditLink($username)
    {
         return '<a class="edit_user_group_anchor ajax"'
            . ' href="server_privileges.php'
            . Url::getCommon(array('username' => $username))
            . '">'
            . Util::getIcon('b_usrlist', __('Edit user group'))
            . '</a>';
    }

    /**
     * Returns number of defined user groups
     *
     * @return integer $user_group_count
     */
    public static function getUserGroupCount()
    {
        $relation = new Relation();
        $cfgRelation = $relation->getRelationsParam();
        $user_group_table = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['usergroups']);
        $sql_query = 'SELECT COUNT(*) FROM ' . $user_group_table;
        $user_group_count = $GLOBALS['dbi']->fetchValue(
            $sql_query, 0, 0, DatabaseInterface::CONNECT_CONTROL
        );

        return $user_group_count;
    }

    /**
     * Returns name of user group that user is part of
     *
     * @param string $username User name
     *
     * @return mixed usergroup if found or null if not found
     */
    public static function getUserGroupForUser($username)
    {
        $relation = new Relation();
        $cfgRelation = $relation->getRelationsParam();

        if (empty($cfgRelation['db'])
            || empty($cfgRelation['users'])
        ) {
            return null;
        }

        $user_table = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['users']);
        $sql_query = 'SELECT `usergroup` FROM ' . $user_table
            . ' WHERE `username` = \'' . $username . '\''
            . ' LIMIT 1';

        $usergroup = $GLOBALS['dbi']->fetchValue(
            $sql_query, 0, 0, DatabaseInterface::CONNECT_CONTROL
        );

        if ($usergroup === false) {
            return null;
        }

        return $usergroup;
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
    public static function getExtraDataForAjaxBehavior(
        $password, $sql_query, $hostname, $username
    ) {
        $relation = new Relation();
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
            $user_group_count = self::getUserGroupCount();
        }

        $extra_data = array();
        if (strlen($sql_query) > 0) {
            $extra_data['sql_query'] = Util::getMessage(null, $sql_query);
        }

        if (isset($_REQUEST['change_copy'])) {
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
                . '<code>' . join(', ', self::extractPrivInfo(null, true)) . '</code>'
                . '</td>'; //Fill in privileges here

            // if $cfg['Servers'][$i]['users'] and $cfg['Servers'][$i]['usergroups'] are
            // enabled
            $cfgRelation = $relation->getRelationsParam();
            if (!empty($cfgRelation['users']) && !empty($cfgRelation['usergroups'])) {
                $new_user_string .= '<td class="usrGroup"></td>';
            }

            $new_user_string .= '<td>';
            if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')) {
                $new_user_string .= __('Yes');
            } else {
                $new_user_string .= __('No');
            }
            $new_user_string .='</td>';

            if ($GLOBALS['is_grantuser']) {
                $new_user_string .= '<td>'
                    . self::getUserLink('edit', $username, $hostname)
                    . '</td>' . "\n";
            }

            if ($cfgRelation['menuswork'] && $user_group_count > 0) {
                $new_user_string .= '<td>'
                    . self::getUserGroupEditLink($username)
                    . '</td>' . "\n";
            }

            $new_user_string .= '<td>'
                . self::getUserLink(
                    'export',
                    $username,
                    $hostname,
                    '',
                    '',
                    '',
                    isset($_GET['initial']) ? $_GET['initial'] : ''
                )
                . '</td>' . "\n";

            $new_user_string .= '</tr>';

            $extra_data['new_user_string'] = $new_user_string;

            /**
             * Generate the string for this alphabet's initial, to update the user
             * pagination
             */
            $new_user_initial = mb_strtoupper(
                mb_substr($username, 0, 1)
            );
            $newUserInitialString = '<a href="server_privileges.php'
                . Url::getCommon(array('initial' => $new_user_initial)) . '">'
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
            $new_privileges = join(', ', self::extractPrivInfo(null, true));

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
    public static function getChangeLoginInformationHtmlForm($username, $hostname)
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
            . 'onsubmit="return checkAddUser(this);" '
            . 'method="post" class="copyUserForm submenu-item">' . "\n"
            . Url::getHiddenInputs('', '')
            . '<input type="hidden" name="old_username" '
            . 'value="' . htmlspecialchars($username) . '" />' . "\n"
            . '<input type="hidden" name="old_hostname" '
            . 'value="' . htmlspecialchars($hostname) . '" />' . "\n";

        $usergroup = self::getUserGroupForUser($username);
        if ($usergroup !== null) {
            $html_output .= '<input type="hidden" name="old_usergroup" '
            . 'value="' . htmlspecialchars($usergroup) . '" />' . "\n";
        }

        $html_output .= '<fieldset id="fieldset_change_copy_user">' . "\n"
            . '<legend data-submenu-label="' . __('Login Information') . '">' . "\n"
            . __('Change login information / Copy user account')
            . '</legend>' . "\n"
            . self::getHtmlForLoginInformationFields('change', $username, $hostname);

        $html_output .= '<fieldset id="fieldset_mode">' . "\n"
            . ' <legend>'
            . __('Create a new user account with the same privileges and …')
            . '</legend>' . "\n";
        $html_output .= Util::getRadioFields(
            'mode', $choices, '4', true
        );
        $html_output .= '</fieldset>' . "\n"
           . '</fieldset>' . "\n";

        $html_output .= '<fieldset id="fieldset_change_copy_user_footer" '
            . 'class="tblFooters">' . "\n"
            . '<input type="hidden" name="change_copy" value="1" />' . "\n"
            . '<input type="submit" value="' . __('Go') . '" />' . "\n"
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
    public static function getLinkToDbAndTable($url_dbname, $dbname, $tablename)
    {
        $html_output = '[ ' . __('Database')
            . ' <a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
            . Url::getCommon(
                array(
                    'db' => $url_dbname,
                    'reload' => 1
                )
            )
            . '">'
            . htmlspecialchars(Util::unescapeMysqlWildcards($dbname)) . ': '
            . Util::getTitleForTarget(
                $GLOBALS['cfg']['DefaultTabDatabase']
            )
            . "</a> ]\n";

        if (strlen($tablename) > 0) {
            $html_output .= ' [ ' . __('Table') . ' <a href="'
                . Util::getScriptNameForOption(
                    $GLOBALS['cfg']['DefaultTabTable'], 'table'
                )
                . Url::getCommon(
                    array(
                        'db' => $url_dbname,
                        'table' => $tablename,
                        'reload' => 1,
                    )
                )
                . '">' . htmlspecialchars($tablename) . ': '
                . Util::getTitleForTarget(
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
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database or table
     * @param string $dbname   database name
     *
     * @return array $db_rights database rights
     */
    public static function getUserSpecificRights($username, $hostname, $type, $dbname = '')
    {
        $user_host_condition = " WHERE `User`"
            . " = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host`"
            . " = '" . $GLOBALS['dbi']->escapeString($hostname) . "'";

        if ($type == 'database') {
            $tables_to_search_for_users = array(
                'tables_priv', 'columns_priv', 'procs_priv'
            );
            $dbOrTableName = 'Db';
        } elseif ($type == 'table') {
            $user_host_condition .= " AND `Db` LIKE '"
                . $GLOBALS['dbi']->escapeString($dbname) . "'";
            $tables_to_search_for_users = array('columns_priv',);
            $dbOrTableName = 'Table_name';
        } else { // routine
            $user_host_condition .= " AND `Db` LIKE '"
                . $GLOBALS['dbi']->escapeString($dbname) . "'";
            $tables_to_search_for_users = array('procs_priv',);
            $dbOrTableName = 'Routine_name';
        }

        // we also want privileges for this user not in table `db` but in other table
        $tables = $GLOBALS['dbi']->fetchResult('SHOW TABLES FROM `mysql`;');

        $db_rights_sqls = array();
        foreach ($tables_to_search_for_users as $table_search_in) {
            if (in_array($table_search_in, $tables)) {
                $db_rights_sqls[] = '
                    SELECT DISTINCT `' . $dbOrTableName . '`
                    FROM `mysql`.' . Util::backquote($table_search_in)
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
            if ($type == 'database') {
                // only Db names in the table `mysql`.`db` uses wildcards
                // as we are in the db specific rights display we want
                // all db names escaped, also from other sources
                $db_rights_row['Db'] = Util::escapeMysqlWildcards(
                    $db_rights_row['Db']
                );
            }
            $db_rights[$db_rights_row[$dbOrTableName]] = $db_rights_row;
        }

        $GLOBALS['dbi']->freeResult($db_rights_result);

        if ($type == 'database') {
            $sql_query = 'SELECT * FROM `mysql`.`db`'
                . $user_host_condition . ' ORDER BY `Db` ASC';
        } elseif ($type == 'table') {
            $sql_query = 'SELECT `Table_name`,'
                . ' `Table_priv`,'
                . ' IF(`Column_priv` = _latin1 \'\', 0, 1)'
                . ' AS \'Column_priv\''
                . ' FROM `mysql`.`tables_priv`'
                . $user_host_condition
                . ' ORDER BY `Table_name` ASC;';
        } else {
            $sql_query = "SELECT `Routine_name`, `Proc_priv`"
                . " FROM `mysql`.`procs_priv`"
                . $user_host_condition
                . " ORDER BY `Routine_name`";

        }

        $result = $GLOBALS['dbi']->query($sql_query);

        while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
            if (isset($db_rights[$row[$dbOrTableName]])) {
                $db_rights[$row[$dbOrTableName]]
                    = array_merge($db_rights[$row[$dbOrTableName]], $row);
            } else {
                $db_rights[$row[$dbOrTableName]] = $row;
            }
            if ($type == 'database') {
                // there are db specific rights for this user
                // so we can drop this db rights
                $db_rights[$row['Db']]['can_delete'] = true;
            }
        }
        $GLOBALS['dbi']->freeResult($result);
        return $db_rights;
    }

    /**
     * Parses Proc_priv data
     *
     * @param string $privs Proc_priv
     *
     * @return array
     */
    public static function parseProcPriv($privs)
    {
        $result = array(
            'Alter_routine_priv' => 'N',
            'Execute_priv'       => 'N',
            'Grant_priv'         => 'N',
        );
        foreach (explode(',', $privs) as $priv) {
            if ($priv == 'Alter Routine') {
                $result['Alter_routine_priv'] = 'Y';
            } else {
                $result[$priv . '_priv'] = 'Y';
            }
        }
        return $result;
    }

    /**
     * Get a HTML table for display user's tabel specific or database specific rights
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database, table or routine
     * @param string $dbname   database name
     *
     * @return array $html_output
     */
    public static function getHtmlForAllTableSpecificRights(
        $username, $hostname, $type, $dbname = ''
    ) {
        $uiData = array(
            'database' => array(
                'form_id'        => 'database_specific_priv',
                'sub_menu_label' => __('Database'),
                'legend'         => __('Database-specific privileges'),
                'type_label'     => __('Database'),
            ),
            'table' => array(
                'form_id'        => 'table_specific_priv',
                'sub_menu_label' => __('Table'),
                'legend'         => __('Table-specific privileges'),
                'type_label'     => __('Table'),
            ),
            'routine' => array(
                'form_id'        => 'routine_specific_priv',
                'sub_menu_label' => __('Routine'),
                'legend'         => __('Routine-specific privileges'),
                'type_label'     => __('Routine'),
            ),
        );

        /**
         * no db name given, so we want all privs for the given user
         * db name was given, so we want all user specific rights for this db
         */
        $db_rights = self::getUserSpecificRights($username, $hostname, $type, $dbname);
        ksort($db_rights);

        $foundRows = array();
        $privileges = array();
        foreach ($db_rights as $row) {
            $onePrivilege = array();

            $paramTableName = '';
            $paramRoutineName = '';

            if ($type == 'database') {
                $name = $row['Db'];
                $onePrivilege['grant']        = $row['Grant_priv'] == 'Y';
                $onePrivilege['table_privs']   = ! empty($row['Table_priv'])
                    || ! empty($row['Column_priv']);
                $onePrivilege['privileges'] = join(',', self::extractPrivInfo($row, true));

                $paramDbName = $row['Db'];

            } elseif ($type == 'table') {
                $name = $row['Table_name'];
                $onePrivilege['grant'] = in_array(
                    'Grant',
                    explode(',', $row['Table_priv'])
                );
                $onePrivilege['column_privs']  = ! empty($row['Column_priv']);
                $onePrivilege['privileges'] = join(',', self::extractPrivInfo($row, true));

                $paramDbName = $dbname;
                $paramTableName = $row['Table_name'];

            } else { // routine
                $name = $row['Routine_name'];
                $onePrivilege['grant'] = in_array(
                    'Grant',
                    explode(',', $row['Proc_priv'])
                );

                $privs = self::parseProcPriv($row['Proc_priv']);
                $onePrivilege['privileges'] = join(
                    ',',
                    self::extractPrivInfo($privs, true)
                );

                $paramDbName = $dbname;
                $paramRoutineName = $row['Routine_name'];
            }

            $foundRows[] = $name;
            $onePrivilege['name'] = $name;

            $onePrivilege['edit_link'] = '';
            if ($GLOBALS['is_grantuser']) {
                $onePrivilege['edit_link'] = self::getUserLink(
                    'edit',
                    $username,
                    $hostname,
                    $paramDbName,
                    $paramTableName,
                    $paramRoutineName
                );
            }

            $onePrivilege['revoke_link'] = '';
            if ($type != 'database' || ! empty($row['can_delete'])) {
                $onePrivilege['revoke_link'] = self::getUserLink(
                    'revoke',
                    $username,
                    $hostname,
                    $paramDbName,
                    $paramTableName,
                    $paramRoutineName
                );
            }

            $privileges[] = $onePrivilege;
        }

        $data = $uiData[$type];
        $data['privileges'] = $privileges;
        $data['username']   = $username;
        $data['hostname']   = $hostname;
        $data['database']   = $dbname;
        $data['type']       = $type;

        if ($type == 'database') {

            // we already have the list of databases from libraries/common.inc.php
            // via $pma = new PMA;
            $pred_db_array = $GLOBALS['dblist']->databases;
            $databases_to_skip = array('information_schema', 'performance_schema');

            $databases = array();
            if (! empty($pred_db_array)) {
                foreach ($pred_db_array as $current_db) {
                    if (in_array($current_db, $databases_to_skip)) {
                        continue;
                    }
                    $current_db_escaped = Util::escapeMysqlWildcards($current_db);
                    // cannot use array_diff() once, outside of the loop,
                    // because the list of databases has special characters
                    // already escaped in $foundRows,
                    // contrary to the output of SHOW DATABASES
                    if (! in_array($current_db_escaped, $foundRows)) {
                        $databases[] = $current_db;
                    }
                }
            }
            $data['databases'] = $databases;

        } elseif ($type == 'table') {
            $result = @$GLOBALS['dbi']->tryQuery(
                "SHOW TABLES FROM " . Util::backquote($dbname),
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );

            $tables = array();
            if ($result) {
                while ($row = $GLOBALS['dbi']->fetchRow($result)) {
                    if (! in_array($row[0], $foundRows)) {
                        $tables[] = $row[0];
                    }
                }
                $GLOBALS['dbi']->freeResult($result);
            }
            $data['tables'] = $tables;

        } else { // routine
            $routineData = $GLOBALS['dbi']->getRoutines($dbname);

            $routines = array();
            foreach ($routineData as $routine) {
                if (! in_array($routine['name'], $foundRows)) {
                    $routines[] = $routine['name'];
                }
            }
            $data['routines'] = $routines;
        }

        $html_output = Template::get('privileges/privileges_summary')
            ->render($data);

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
    public static function getUsersOverview($result, array $db_rights, $pmaThemeImage, $text_dir)
    {
        while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
            $row['privs'] = self::extractPrivInfo($row, true);
            $db_rights[$row['User']][$row['Host']] = $row;
        }
        $GLOBALS['dbi']->freeResult($result);
        $user_group_count = 0;
        if ($GLOBALS['cfgRelation']['menuswork']) {
            $user_group_count = self::getUserGroupCount();
        }

        $html_output
            = '<form name="usersForm" id="usersForm" action="server_privileges.php" '
            . 'method="post">' . "\n"
            . Url::getHiddenInputs('', '')
            . '<div class="responsivetable">'
            . '<table id="tableuserrights" class="data">' . "\n"
            . '<thead>' . "\n"
            . '<tr><th></th>' . "\n"
            . '<th>' . __('User name') . '</th>' . "\n"
            . '<th>' . __('Host name') . '</th>' . "\n"
            . '<th>' . __('Password') . '</th>' . "\n"
            . '<th>' . __('Global privileges') . ' '
            . Util::showHint(
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
        $html_output .= self::getHtmlTableBodyForUserRights($db_rights);
        $html_output .= '</tbody>'
            . '</table></div>' . "\n";

        $html_output .= '<div class="floatleft">'
            . Template::get('select_all')
                ->render(
                    array(
                        'pma_theme_image' => $pmaThemeImage,
                        'text_dir'        => $text_dir,
                        'form_name'       => 'usersForm',
                    )
                ) . "\n";
        $html_output .= Util::getButtonOrImage(
            'submit_mult', 'mult_submit',
            __('Export'), 'b_tblexport', 'export'
        );
        $html_output .= '<input type="hidden" name="initial" '
            . 'value="' . (isset($_GET['initial']) ? htmlspecialchars($_GET['initial']) : '') . '" />';
        $html_output .= '</div>'
            . '<div class="clearfloat"></div>';

        // add/delete user fieldset
        $html_output .= self::getFieldsetForAddDeleteUser();
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
    public static function getHtmlTableBodyForUserRights(array $db_rights)
    {
        $relation = new Relation();
        $cfgRelation = $relation->getRelationsParam();
        if ($cfgRelation['menuswork']) {
            $users_table = Util::backquote($cfgRelation['db'])
                . "." . Util::backquote($cfgRelation['users']);
            $sql_query = 'SELECT * FROM ' . $users_table;
            $result = $relation->queryAsControlUser($sql_query, false);
            $group_assignment = array();
            if ($result) {
                while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                    $group_assignment[$row['username']] = $row['usergroup'];
                }
            }
            $GLOBALS['dbi']->freeResult($result);

            $user_group_count = self::getUserGroupCount();
        }

        $index_checkbox = 0;
        $html_output = '';
        foreach ($db_rights as $user) {
            ksort($user);
            foreach ($user as $host) {
                $index_checkbox++;
                $html_output .= '<tr>'
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

                $password_column = 'Password';

                $check_plugin_query = "SELECT * FROM `mysql`.`user` WHERE "
                    . "`User` = '" . $host['User'] . "' AND `Host` = '"
                    . $host['Host'] . "'";
                $res = $GLOBALS['dbi']->fetchSingleRow($check_plugin_query);

                if ((isset($res['authentication_string'])
                    && ! empty($res['authentication_string']))
                    || (isset($res['Password'])
                    && ! empty($res['Password']))
                ) {
                    $host[$password_column] = 'Y';
                } else {
                    $host[$password_column] = 'N';
                }

                switch ($host[$password_column]) {
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

                if (! isset($host['Select_priv'])) {
                    $html_output .= Util::showHint(
                        __('The selected user was not found in the privilege table.')
                    );
                }

                $html_output .= '</td>' . "\n";

                $html_output .= '<td><code>' . "\n"
                    . '' . implode(',' . "\n" . '            ', $host['privs']) . "\n"
                    . '</code></td>' . "\n";
                if ($cfgRelation['menuswork']) {
                    $html_output .= '<td class="usrGroup">' . "\n"
                        . (isset($group_assignment[$host['User']])
                            ? htmlspecialchars($group_assignment[$host['User']])
                            : ''
                        )
                        . '</td>' . "\n";
                }
                $html_output .= '<td>'
                    . ($host['Grant_priv'] == 'Y' ? __('Yes') : __('No'))
                    . '</td>' . "\n";

                if ($GLOBALS['is_grantuser']) {
                    $html_output .= '<td class="center">'
                        . self::getUserLink(
                            'edit',
                            $host['User'],
                            $host['Host']
                        )
                        . '</td>';
                }
                if ($cfgRelation['menuswork'] && $user_group_count > 0) {
                    if (empty($host['User'])) {
                        $html_output .= '<td class="center"></td>';
                    } else {
                        $html_output .= '<td class="center">'
                            . self::getUserGroupEditLink($host['User'])
                            . '</td>';
                    }
                }
                $html_output .= '<td class="center">'
                    . self::getUserLink(
                        'export',
                        $host['User'],
                        $host['Host'],
                        '',
                        '',
                        '',
                        isset($_GET['initial']) ? $_GET['initial'] : ''
                    )
                    . '</td>';
                $html_output .= '</tr>';
            }
        }
        return $html_output;
    }

    /**
     * Get HTML fieldset for Add/Delete user
     *
     * @return string HTML snippet
     */
    public static function getFieldsetForAddDeleteUser()
    {
        $html_output = self::getAddUserHtmlFieldset();

        $html_output .= Template::get('privileges/delete_user_fieldset')
            ->render(array());

        return $html_output;
    }

    /**
     * Get HTML for Displays the initials
     *
     * @param array $array_initials array for all initials, even non A-Z
     *
     * @return string HTML snippet
     */
    public static function getHtmlForInitials(array $array_initials)
    {
        // initialize to false the letters A-Z
        for ($letter_counter = 1; $letter_counter < 27; $letter_counter++) {
            if (! isset($array_initials[mb_chr($letter_counter + 64)])) {
                $array_initials[mb_chr($letter_counter + 64)] = false;
            }
        }

        $initials = $GLOBALS['dbi']->tryQuery(
            'SELECT DISTINCT UPPER(LEFT(`User`,1)) FROM `user`'
            . ' ORDER BY UPPER(LEFT(`User`,1)) ASC',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        if ($initials) {
            while (list($tmp_initial) = $GLOBALS['dbi']->fetchRow($initials)) {
                $array_initials[$tmp_initial] = true;
            }
        }

        // Display the initials, which can be any characters, not
        // just letters. For letters A-Z, we add the non-used letters
        // as greyed out.

        uksort($array_initials, "strnatcasecmp");

        $html_output = Template::get('privileges/initials_row')
            ->render(
                array(
                    'array_initials' => $array_initials,
                    'initial' => isset($_REQUEST['initial']) ? $_REQUEST['initial'] : null,
                )
            );

        return $html_output;
    }

    /**
     * Get the database rights array for Display user overview
     *
     * @return array  $db_rights    database rights array
     */
    public static function getDbRightsForUserOverview()
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
                    ? self::rangeOfUsers($_GET['initial'])
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
     * @param array $queries queries
     *
     * @return array Message
     */
    public static function deleteUser(array $queries)
    {
        $sql_query = '';
        if (empty($queries)) {
            $message = Message::error(__('No users selected for deleting!'));
        } else {
            if ($_REQUEST['mode'] == 3) {
                $queries[] = '# ' . __('Reloading the privileges') . ' …';
                $queries[] = 'FLUSH PRIVILEGES;';
            }
            $drop_user_error = '';
            foreach ($queries as $sql_query) {
                if ($sql_query{0} != '#') {
                    if (! $GLOBALS['dbi']->tryQuery($sql_query)) {
                        $drop_user_error .= $GLOBALS['dbi']->getError() . "\n";
                    }
                }
            }
            // tracking sets this, causing the deleted db to be shown in navi
            unset($GLOBALS['db']);

            $sql_query = join("\n", $queries);
            if (! empty($drop_user_error)) {
                $message = Message::rawError($drop_user_error);
            } else {
                $message = Message::success(
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
     * @param string $itemType  item type
     *
     * @return Message success message or error message for update
     */
    public static function updatePrivileges($username, $hostname, $tablename, $dbname, $itemType)
    {
        $db_and_table = self::wildcardEscapeForGrant($dbname, $tablename);

        $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $itemType . ' ' . $db_and_table
            . ' FROM \'' . $GLOBALS['dbi']->escapeString($username)
            . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\';';

        if (! isset($_POST['Grant_priv']) || $_POST['Grant_priv'] != 'Y') {
            $sql_query1 = 'REVOKE GRANT OPTION ON ' . $itemType . ' ' . $db_and_table
                . ' FROM \'' . $GLOBALS['dbi']->escapeString($username) . '\'@\''
                . $GLOBALS['dbi']->escapeString($hostname) . '\';';
        } else {
            $sql_query1 = '';
        }

        // Should not do a GRANT USAGE for a table-specific privilege, it
        // causes problems later (cannot revoke it)
        if (! (strlen($tablename) > 0
            && 'USAGE' == implode('', self::extractPrivInfo()))
        ) {
            $sql_query2 = 'GRANT ' . join(', ', self::extractPrivInfo())
                . ' ON ' . $itemType . ' ' . $db_and_table
                . ' TO \'' . $GLOBALS['dbi']->escapeString($username) . '\'@\''
                . $GLOBALS['dbi']->escapeString($hostname) . '\'';

            if (strlen($dbname) === 0) {
                // add REQUIRE clause
                $sql_query2 .= self::getRequireClause();
            }

            if ((isset($_POST['Grant_priv']) && $_POST['Grant_priv'] == 'Y')
                || (strlen($dbname) === 0
                && (isset($_POST['max_questions']) || isset($_POST['max_connections'])
                || isset($_POST['max_updates'])
                || isset($_POST['max_user_connections'])))
            ) {
                $sql_query2 .= self::getWithClauseForAddUserAndUpdatePrivs();
            }
            $sql_query2 .= ';';
        }
        if (! $GLOBALS['dbi']->tryQuery($sql_query0)) {
            // This might fail when the executing user does not have
            // ALL PRIVILEGES himself.
            // See https://github.com/phpmyadmin/phpmyadmin/issues/9673
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
        $message = Message::success(__('You have updated the privileges for %s.'));
        $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');

        return array($sql_query, $message);
    }

    /**
     * Get List of information: Changes / copies a user
     *
     * @return array
     */
    public static function getDataForChangeOrCopyUser()
    {
        $queries = null;
        $password = null;

        if (isset($_REQUEST['change_copy'])) {
            $user_host_condition = ' WHERE `User` = '
                . "'" . $GLOBALS['dbi']->escapeString($_REQUEST['old_username']) . "'"
                . ' AND `Host` = '
                . "'" . $GLOBALS['dbi']->escapeString($_REQUEST['old_hostname']) . "';";
            $row = $GLOBALS['dbi']->fetchSingleRow(
                'SELECT * FROM `mysql`.`user` ' . $user_host_condition
            );
            if (! $row) {
                $response = Response::getInstance();
                $response->addHTML(
                    Message::notice(__('No user found.'))->getDisplay()
                );
                unset($_REQUEST['change_copy']);
            } else {
                extract($row, EXTR_OVERWRITE);
                foreach ($row as $key => $value) {
                    $GLOBALS[$key] = $value;
                }
                $serverVersion = $GLOBALS['dbi']->getVersion();
                // Recent MySQL versions have the field "Password" in mysql.user,
                // so the previous extract creates $Password but this script
                // uses $password
                if (! isset($password) && isset($Password)) {
                    $password = $Password;
                }
                if (Util::getServerType() == 'MySQL'
                    && $serverVersion >= 50606
                    && $serverVersion < 50706
                    && ((isset($authentication_string)
                    && empty($password))
                    || (isset($plugin)
                    && $plugin == 'sha256_password'))
                ) {
                    $password = $authentication_string;
                }

                if (Util::getServerType() == 'MariaDB'
                    && $serverVersion >= 50500
                    && isset($authentication_string)
                    && empty($password)
                ) {
                    $password = $authentication_string;
                }

                // Always use 'authentication_string' column
                // for MySQL 5.7.6+ since it does not have
                // the 'password' column at all
                if (Util::getServerType() == 'MySQL'
                    && $serverVersion >= 50706
                    && isset($authentication_string)
                ) {
                    $password = $authentication_string;
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
    public static function getDataForDeleteUsers($queries)
    {
        if (isset($_REQUEST['change_copy'])) {
            $selected_usr = array(
                $_REQUEST['old_username'] . '&amp;#27;' . $_REQUEST['old_hostname']
            );
        } else {
            $selected_usr = $_REQUEST['selected_usr'];
            $queries = array();
        }

        // this happens, was seen in https://reports.phpmyadmin.net/reports/view/17146
        if (! is_array($selected_usr)) {
            return array();
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
                . $GLOBALS['dbi']->escapeString($this_user)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($this_host) . '\';';
            RelationCleanup::user($this_user);

            if (isset($_REQUEST['drop_users_db'])) {
                $queries[] = 'DROP DATABASE IF EXISTS '
                    . Util::backquote($this_user) . ';';
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
    public static function updateMessageForReload()
    {
        $message = null;
        if (isset($_REQUEST['flush_privileges'])) {
            $sql_query = 'FLUSH PRIVILEGES;';
            $GLOBALS['dbi']->query($sql_query);
            $message = Message::success(
                __('The privileges were reloaded successfully.')
            );
        }

        if (isset($_REQUEST['validate_username'])) {
            $message = Message::success();
        }

        return $message;
    }

    /**
     * update Data For Queries from queries_for_display
     *
     * @param array      $queries             queries array
     * @param array|null $queries_for_display queries array for display
     *
     * @return null
     */
    public static function getDataForQueries(array $queries, $queries_for_display)
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
    public static function addUser(
        $dbname, $username, $hostname,
        $password, $is_menuwork
    ) {
        $_add_user_error = false;
        $message = null;
        $queries = null;
        $queries_for_display = null;
        $sql_query = null;

        if (!isset($_REQUEST['adduser_submit']) && !isset($_REQUEST['change_copy'])) {
            return array(
                $message, $queries, $queries_for_display, $sql_query, $_add_user_error
            );
        }

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
            $hostname = mb_substr(
                $_user_name,
                (mb_strrpos($_user_name, '@') + 1)
            );
            unset($_user_name);
            break;
        }
        $sql = "SELECT '1' FROM `mysql`.`user`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "';";
        if ($GLOBALS['dbi']->fetchValue($sql) == 1) {
            $message = Message::error(__('The user %s already exists!'));
            $message->addParam('[em]\'' . $username . '\'@\'' . $hostname . '\'[/em]');
            $_REQUEST['adduser'] = true;
            $_add_user_error = true;

            return array(
                $message,
                $queries,
                $queries_for_display,
                $sql_query,
                $_add_user_error
            );
        }

        list(
            $create_user_real, $create_user_show, $real_sql_query, $sql_query,
            $password_set_real, $password_set_show
        ) = self::getSqlQueriesForDisplayAndAddUser(
            $username, $hostname, (isset($password) ? $password : '')
        );

        if (empty($_REQUEST['change_copy'])) {
            $_error = false;

            if (isset($create_user_real)) {
                if (!$GLOBALS['dbi']->tryQuery($create_user_real)) {
                    $_error = true;
                }
                if (isset($password_set_real) && !empty($password_set_real)
                    && isset($_REQUEST['authentication_plugin'])
                ) {
                    self::setProperPasswordHashing(
                        $_REQUEST['authentication_plugin']
                    );
                    if ($GLOBALS['dbi']->tryQuery($password_set_real)) {
                        $sql_query .= $password_set_show;
                    }
                }
                $sql_query = $create_user_show . $sql_query;
            }

            list($sql_query, $message) = self::addUserAndCreateDatabase(
                $_error,
                $real_sql_query,
                $sql_query,
                $username,
                $hostname,
                isset($dbname) ? $dbname : null
            );
            if (!empty($_REQUEST['userGroup']) && $is_menuwork) {
                self::setUserGroup($GLOBALS['username'], $_REQUEST['userGroup']);
            }

            return array(
                $message,
                $queries,
                $queries_for_display,
                $sql_query,
                $_add_user_error
            );
        }

        // Copy the user group while copying a user
        $old_usergroup =
            isset($_REQUEST['old_usergroup']) ? $_REQUEST['old_usergroup'] : null;
        self::setUserGroup($_REQUEST['username'], $old_usergroup);

        if (isset($create_user_real)) {
            $queries[] = $create_user_real;
        }
        $queries[] = $real_sql_query;

        if (isset($password_set_real) && ! empty($password_set_real)
            && isset($_REQUEST['authentication_plugin'])
        ) {
            self::setProperPasswordHashing(
                $_REQUEST['authentication_plugin']
            );

            $queries[] = $password_set_real;
        }
        // we put the query containing the hidden password in
        // $queries_for_display, at the same position occupied
        // by the real query in $queries
        $tmp_count = count($queries);
        if (isset($create_user_real)) {
            $queries_for_display[$tmp_count - 2] = $create_user_show;
        }
        if (isset($password_set_real) && ! empty($password_set_real)) {
            $queries_for_display[$tmp_count - 3] = $create_user_show;
            $queries_for_display[$tmp_count - 2] = $sql_query;
            $queries_for_display[$tmp_count - 1] = $password_set_show;
        } else {
            $queries_for_display[$tmp_count - 1] = $sql_query;
        }

        return array(
            $message, $queries, $queries_for_display, $sql_query, $_add_user_error
        );
    }

    /**
     * Sets proper value of `old_passwords` according to
     * the authentication plugin selected
     *
     * @param string $auth_plugin authentication plugin selected
     *
     * @return void
     */
    public static function setProperPasswordHashing($auth_plugin)
    {
        // Set the hashing method used by PASSWORD()
        // to be of type depending upon $authentication_plugin
        if ($auth_plugin == 'sha256_password') {
            $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 2');
        } elseif ($auth_plugin == 'mysql_old_password') {
            $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 1');
        } else {
            $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 0');
        }
    }

    /**
     * Update DB information: DB, Table, isWildcard
     *
     * @return array
     */
    public static function getDataForDBInfo()
    {
        $username = null;
        $hostname = null;
        $dbname = null;
        $tablename = null;
        $routinename = null;
        $dbname_is_wildcard = null;

        if (isset($_REQUEST['username'])) {
            $username = $_REQUEST['username'];
        }
        if (isset($_REQUEST['hostname'])) {
            $hostname = $_REQUEST['hostname'];
        }
        /**
         * Checks if a dropdown box has been used for selecting a database / table
         */
        if (Core::isValid($_REQUEST['pred_tablename'])) {
            $tablename = $_REQUEST['pred_tablename'];
        } elseif (Core::isValid($_REQUEST['tablename'])) {
            $tablename = $_REQUEST['tablename'];
        } else {
            unset($tablename);
        }

        if (Core::isValid($_REQUEST['pred_routinename'])) {
            $routinename = $_REQUEST['pred_routinename'];
        } elseif (Core::isValid($_REQUEST['routinename'])) {
            $routinename = $_REQUEST['routinename'];
        } else {
            unset($routinename);
        }

        if (isset($_REQUEST['pred_dbname'])) {
            $is_valid_pred_dbname = true;
            foreach ($_REQUEST['pred_dbname'] as $key => $db_name) {
                if (! Core::isValid($db_name)) {
                    $is_valid_pred_dbname = false;
                    break;
                }
            }
        }

        if (isset($_REQUEST['dbname'])) {
            $is_valid_dbname = true;
            if (is_array($_REQUEST['dbname'])) {
                foreach ($_REQUEST['dbname'] as $key => $db_name) {
                    if (! Core::isValid($db_name)) {
                        $is_valid_dbname = false;
                        break;
                    }
                }
            } else {
                if (! Core::isValid($_REQUEST['dbname'])) {
                    $is_valid_dbname = false;
                }
            }
        }

        if (isset($is_valid_pred_dbname) && $is_valid_pred_dbname) {
            $dbname = $_REQUEST['pred_dbname'];
            // If dbname contains only one database.
            if (count($dbname) == 1) {
                $dbname = $dbname[0];
            }
        } elseif (isset($is_valid_dbname) && $is_valid_dbname) {
            $dbname = $_REQUEST['dbname'];
        } else {
            unset($dbname);
            unset($tablename);
        }

        if (isset($dbname)) {
            if (is_array($dbname)) {
                $db_and_table = $dbname;
                foreach ($db_and_table as $key => $db_name) {
                    $db_and_table[$key] .= '.';
                }
            } else {
                $unescaped_db = Util::unescapeMysqlWildcards($dbname);
                $db_and_table = Util::backquote($unescaped_db) . '.';
            }
            if (isset($tablename)) {
                $db_and_table .= Util::backquote($tablename);
            } else {
                if (is_array($db_and_table)) {
                    foreach ($db_and_table as $key => $db_name) {
                        $db_and_table[$key] .= '*';
                    }
                } else {
                    $db_and_table .= '*';
                }
            }
        } else {
            $db_and_table = '*.*';
        }

        // check if given $dbname is a wildcard or not
        if (isset($dbname)) {
            //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
            if (! is_array($dbname) && preg_match('/(?<!\\\\)(?:_|%)/i', $dbname)) {
                $dbname_is_wildcard = true;
            } else {
                $dbname_is_wildcard = false;
            }
        }

        return array(
            $username, $hostname,
            isset($dbname)? $dbname : null,
            isset($tablename)? $tablename : null,
            isset($routinename) ? $routinename : null,
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
    public static function getListForExportUserDefinition($username, $hostname)
    {
        $export = '<textarea class="export" cols="60" rows="15">';

        if (isset($_REQUEST['selected_usr'])) {
            // export privileges for selected users
            $title = __('Privileges');

            //For removing duplicate entries of users
            $_REQUEST['selected_usr'] = array_unique($_REQUEST['selected_usr']);

            foreach ($_REQUEST['selected_usr'] as $export_user) {
                $export_username = mb_substr(
                    $export_user, 0, mb_strpos($export_user, '&')
                );
                $export_hostname = mb_substr(
                    $export_user, mb_strrpos($export_user, ';') + 1
                );
                $export .= '# '
                    . sprintf(
                        __('Privileges for %s'),
                        '`' . htmlspecialchars($export_username)
                        . '`@`' . htmlspecialchars($export_hostname) . '`'
                    )
                    . "\n\n";
                $export .= self::getGrants($export_username, $export_hostname) . "\n";
            }
        } else {
            // export privileges for a single user
            $title = __('User') . ' `' . htmlspecialchars($username)
                . '`@`' . htmlspecialchars($hostname) . '`';
            $export .= self::getGrants($username, $hostname);
        }
        // remove trailing whitespace
        $export = trim($export);

        $export .= '</textarea>';

        return array($title, $export);
    }

    /**
     * Get HTML for display Add userfieldset
     *
     * @param string $db    the database
     * @param string $table the table name
     *
     * @return string html output
     */
    public static function getAddUserHtmlFieldset($db = '', $table = '')
    {
        if (!$GLOBALS['is_createuser']) {
            return '';
        }
        $rel_params = array();
        $url_params = array(
            'adduser' => 1
        );
        if (!empty($db)) {
            $url_params['dbname']
                = $rel_params['checkprivsdb']
                    = $db;
        }
        if (!empty($table)) {
            $url_params['tablename']
                = $rel_params['checkprivstable']
                    = $table;
        }

        return Template::get('privileges/add_user_fieldset')
            ->render(
                array(
                    'url_params' => $url_params,
                    'rel_params' => $rel_params
                )
            );
    }

    /**
     * Get HTML header for display User's properties
     *
     * @param boolean $dbname_is_wildcard whether database name is wildcard or not
     * @param string  $url_dbname         url database name that urlencode() string
     * @param string  $dbname             database name
     * @param string  $username           username
     * @param string  $hostname           host name
     * @param string  $entity_name        entity (table or routine) name
     * @param string  $entity_type        optional, type of entity ('table' or 'routine')
     *
     * @return string $html_output
     */
    public static function getHtmlHeaderForUserProperties(
        $dbname_is_wildcard, $url_dbname, $dbname,
        $username, $hostname, $entity_name, $entity_type='table'
    ) {
        $html_output = '<h2>' . "\n"
           . Util::getIcon('b_usredit')
           . __('Edit privileges:') . ' '
           . __('User account');

        if (! empty($dbname)) {
            $html_output .= ' <i><a class="edit_user_anchor"'
                . ' href="server_privileges.php'
                . Url::getCommon(
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
            $html_output .= ($dbname_is_wildcard
                || is_array($dbname) && count($dbname) > 1)
                ? __('Databases') : __('Database');
            if (! empty($entity_name) && $entity_type === 'table') {
                $html_output .= ' <i><a href="server_privileges.php'
                    . Url::getCommon(
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
                    . ' <i>' . htmlspecialchars($entity_name) . '</i>';
            } elseif (! empty($entity_name)) {
                $html_output .= ' <i><a href="server_privileges.php'
                    . Url::getCommon(
                        array(
                            'username' => $username,
                            'hostname' => $hostname,
                            'dbname' => $url_dbname,
                            'routinename' => '',
                        )
                    )
                    . '">' . htmlspecialchars($dbname)
                    . '</a></i>';

                $html_output .= ' - ' . __('Routine')
                    . ' <i>' . htmlspecialchars($entity_name) . '</i>';
            } else {
                if (! is_array($dbname)) {
                    $dbname = array($dbname);
                }
                $html_output .= ' <i>'
                    . htmlspecialchars(implode(', ', $dbname))
                    . '</i>';
            }

        } else {
            $html_output .= ' <i>\'' . htmlspecialchars($username)
                . '\'@\'' . htmlspecialchars($hostname)
                . '\'</i>' . "\n";

        }
        $html_output .= '</h2>' . "\n";
        $cur_user = $GLOBALS['dbi']->getCurrentUser();
        $user = $username . '@' . $hostname;
        // Add a short notice for the user
        // to remind him that he is editing his own privileges
        if ($user === $cur_user) {
            $html_output .= Message::notice(
                __(
                    'Note: You are attempting to edit privileges of the '
                    . 'user with which you are currently logged in.'
                )
            )->getDisplay();
        }
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
    public static function getHtmlForUserOverview($pmaThemeImage, $text_dir)
    {
        $html_output = '<h2>' . "\n"
           . Util::getIcon('b_usrlist')
           . __('User accounts overview') . "\n"
           . '</h2>' . "\n";

        $password_column = 'Password';
        $server_type = Util::getServerType();
        $serverVersion = $GLOBALS['dbi']->getVersion();
        if (($server_type == 'MySQL' || $server_type == 'Percona Server')
            && $serverVersion >= 50706
        ) {
            $password_column = 'authentication_string';
        }
        // $sql_query is for the initial-filtered,
        // $sql_query_all is for counting the total no. of users

        $sql_query = $sql_query_all = 'SELECT *,' .
            " IF(`" . $password_column . "` = _latin1 '', 'N', 'Y') AS 'Password'" .
            ' FROM `mysql`.`user`';

        $sql_query .= (isset($_REQUEST['initial'])
            ? self::rangeOfUsers($_REQUEST['initial'])
            : '');

        $sql_query .= ' ORDER BY `User` ASC, `Host` ASC;';
        $sql_query_all .= ' ;';

        $res = $GLOBALS['dbi']->tryQuery(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $res_all = $GLOBALS['dbi']->tryQuery(
            $sql_query_all,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );

        if (! $res) {
            // the query failed! This may have two reasons:
            // - the user does not have enough privileges
            // - the privilege tables use a structure of an earlier version.
            // so let's try a more simple query

            $GLOBALS['dbi']->freeResult($res);
            $GLOBALS['dbi']->freeResult($res_all);
            $sql_query = 'SELECT * FROM `mysql`.`user`';
            $res = $GLOBALS['dbi']->tryQuery(
                $sql_query,
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );

            if (! $res) {
                $html_output .= self::getHtmlForViewUsersError();
                $html_output .= self::getAddUserHtmlFieldset();
            } else {
                // This message is hardcoded because I will replace it by
                // a automatic repair feature soon.
                $raw = 'Your privilege table structure seems to be older than'
                    . ' this MySQL version!<br />'
                    . 'Please run the <code>mysql_upgrade</code> command'
                    . ' that should be included in your MySQL server distribution'
                    . ' to solve this problem!';
                $html_output .= Message::rawError($raw)->getDisplay();
            }
            $GLOBALS['dbi']->freeResult($res);
        } else {
            $db_rights = self::getDbRightsForUserOverview();
            // for all initials, even non A-Z
            $array_initials = array();

            foreach ($db_rights as $right) {
                foreach ($right as $account) {
                    if (empty($account['User']) && $account['Host'] == 'localhost') {
                        $html_output .= Message::notice(
                            __(
                                'A user account allowing any user from localhost to '
                                . 'connect is present. This will prevent other users '
                                . 'from connecting if the host part of their account '
                                . 'allows a connection from any (%) host.'
                            )
                            . Util::showMySQLDocu('problems-connecting')
                        )->getDisplay();
                        break 2;
                    }
                }
            }

            /**
             * Displays the initials
             * Also not necessary if there is less than 20 privileges
             */
            if ($GLOBALS['dbi']->numRows($res_all) > 20) {
                $html_output .= self::getHtmlForInitials($array_initials);
            }

            /**
            * Display the user overview
            * (if less than 50 users, display them immediately)
            */
            if (isset($_REQUEST['initial'])
                || isset($_REQUEST['showall'])
                || $GLOBALS['dbi']->numRows($res) < 50
            ) {
                $html_output .= self::getUsersOverview(
                    $res, $db_rights, $pmaThemeImage, $text_dir
                );
            } else {
                $html_output .= self::getAddUserHtmlFieldset();
            } // end if (display overview)

            $response = Response::getInstance();
            if (! $response->isAjax()
                || ! empty($_REQUEST['ajax_page_request'])
            ) {
                if ($GLOBALS['is_reload_priv']) {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'you should %sreload the privileges%s before you continue.'
                        ),
                        Message::NOTICE
                    );
                    $flushnote->addParamHtml(
                        '<a href="server_privileges.php'
                        . Url::getCommon(array('flush_privileges' => 1))
                        . '" id="reload_privileges_anchor">'
                    );
                    $flushnote->addParamHtml('</a>');
                } else {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'the privileges have to be reloaded but currently, you '
                            . 'don\'t have the RELOAD privilege.'
                        )
                        . Util::showMySQLDocu(
                            'privileges-provided',
                            false,
                            'priv_reload'
                        ),
                        Message::NOTICE
                    );
                }
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
    public static function getHtmlForUserProperties($dbname_is_wildcard, $url_dbname,
        $username, $hostname, $dbname, $tablename
    ) {
        $html_output  = '<div id="edit_user_dialog">';
        $html_output .= self::getHtmlHeaderForUserProperties(
            $dbname_is_wildcard, $url_dbname, $dbname, $username, $hostname,
            $tablename, 'table'
        );

        $sql = "SELECT '1' FROM `mysql`.`user`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "';";

        $user_does_not_exists = (bool) ! $GLOBALS['dbi']->fetchValue($sql);

        if ($user_does_not_exists) {
            $html_output .= Message::error(
                __('The selected user was not found in the privilege table.')
            )->getDisplay();
            $html_output .= self::getHtmlForLoginInformationFields();
        }

        $_params = array(
            'username' => $username,
            'hostname' => $hostname,
        );
        if (! is_array($dbname) && strlen($dbname) > 0) {
            $_params['dbname'] = $dbname;
            if (strlen($tablename) > 0) {
                $_params['tablename'] = $tablename;
            }
        } else {
            $_params['dbname'] = $dbname;
        }

        $html_output .= '<form class="submenu-item" name="usersForm" '
            . 'id="addUsersForm" action="server_privileges.php" method="post">' . "\n";
        $html_output .= Url::getHiddenInputs($_params);
        $html_output .= self::getHtmlToDisplayPrivilegesTable(
            // If $dbname is an array, pass any one db as all have same privs.
            Core::ifSetOr($dbname, (is_array($dbname)) ? $dbname[0] : '*', 'length'),
            Core::ifSetOr($tablename, '*', 'length')
        );

        $html_output .= '</form>' . "\n";

        if (! is_array($dbname) && strlen($tablename) === 0
            && empty($dbname_is_wildcard)
        ) {
            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards
            if (strlen($dbname) === 0) {
                $html_output .= self::getHtmlForAllTableSpecificRights(
                    $username, $hostname, 'database'
                );
            } else {
                // unescape wildcards in dbname at table level
                $unescaped_db = Util::unescapeMysqlWildcards($dbname);

                $html_output .= self::getHtmlForAllTableSpecificRights(
                    $username, $hostname, 'table', $unescaped_db
                );
                $html_output .= self::getHtmlForAllTableSpecificRights(
                    $username, $hostname, 'routine', $unescaped_db
                );
            }
        }

        // Provide a line with links to the relevant database and table
        if (! is_array($dbname) && strlen($dbname) > 0 && empty($dbname_is_wildcard)) {
            $html_output .= self::getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        }

        if (! is_array($dbname) && strlen($dbname) === 0 && ! $user_does_not_exists) {
            //change login information
            $html_output .= ChangePassword::getHtml(
                'edit_other',
                $username,
                $hostname
            );
            $html_output .= self::getChangeLoginInformationHtmlForm($username, $hostname);
        }
        $html_output .= '</div>';

        return $html_output;
    }

    /**
     * Get queries for Table privileges to change or copy user
     *
     * @param string $user_host_condition user host condition to
     *                                    select relevant table privileges
     * @param array  $queries             queries array
     * @param string $username            username
     * @param string $hostname            host name
     *
     * @return array  $queries
     */
    public static function getTablePrivsQueriesForChangeOrCopyUser($user_host_condition,
        array $queries, $username, $hostname
    ) {
        $res = $GLOBALS['dbi']->query(
            'SELECT `Db`, `Table_name`, `Table_priv` FROM `mysql`.`tables_priv`'
            . $user_host_condition,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {

            $res2 = $GLOBALS['dbi']->query(
                'SELECT `Column_name`, `Column_priv`'
                . ' FROM `mysql`.`columns_priv`'
                . ' WHERE `User`'
                . ' = \'' . $GLOBALS['dbi']->escapeString($_REQUEST['old_username']) . "'"
                . ' AND `Host`'
                . ' = \'' . $GLOBALS['dbi']->escapeString($_REQUEST['old_username']) . '\''
                . ' AND `Db`'
                . ' = \'' . $GLOBALS['dbi']->escapeString($row['Db']) . "'"
                . ' AND `Table_name`'
                . ' = \'' . $GLOBALS['dbi']->escapeString($row['Table_name']) . "'"
                . ';',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );

            $tmp_privs1 = self::extractPrivInfo($row);
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
                . ' ON ' . Util::backquote($row['Db']) . '.'
                . Util::backquote($row['Table_name'])
                . ' TO \'' . $GLOBALS['dbi']->escapeString($username)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\''
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
    public static function getDbSpecificPrivsQueriesForChangeOrCopyUser(
        array $queries, $username, $hostname
    ) {
        $user_host_condition = ' WHERE `User`'
            . ' = \'' . $GLOBALS['dbi']->escapeString($_REQUEST['old_username']) . "'"
            . ' AND `Host`'
            . ' = \'' . $GLOBALS['dbi']->escapeString($_REQUEST['old_hostname']) . '\';';

        $res = $GLOBALS['dbi']->query(
            'SELECT * FROM `mysql`.`db`' . $user_host_condition
        );

        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            $queries[] = 'GRANT ' . join(', ', self::extractPrivInfo($row))
                . ' ON ' . Util::backquote($row['Db']) . '.*'
                . ' TO \'' . $GLOBALS['dbi']->escapeString($username)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\''
                . ($row['Grant_priv'] == 'Y' ? ' WITH GRANT OPTION;' : ';');
        }
        $GLOBALS['dbi']->freeResult($res);

        $queries = self::getTablePrivsQueriesForChangeOrCopyUser(
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
    public static function addUserAndCreateDatabase($_error, $real_sql_query, $sql_query,
        $username, $hostname, $dbname
    ) {
        if ($_error || (!empty($real_sql_query)
            && !$GLOBALS['dbi']->tryQuery($real_sql_query))
        ) {
            $_REQUEST['createdb-1'] = $_REQUEST['createdb-2']
                = $_REQUEST['createdb-3'] = null;
            $message = Message::rawError($GLOBALS['dbi']->getError());
        } else {
            $message = Message::success(__('You have added a new user.'));
        }

        if (isset($_REQUEST['createdb-1'])) {
            // Create database with same name and grant all privileges
            $q = 'CREATE DATABASE IF NOT EXISTS '
                . Util::backquote(
                    $GLOBALS['dbi']->escapeString($username)
                ) . ';';
            $sql_query .= $q;
            if (! $GLOBALS['dbi']->tryQuery($q)) {
                $message = Message::rawError($GLOBALS['dbi']->getError());
            }

            /**
             * Reload the navigation
             */
            $GLOBALS['reload'] = true;
            $GLOBALS['db'] = $username;

            $q = 'GRANT ALL PRIVILEGES ON '
                . Util::backquote(
                    Util::escapeMysqlWildcards(
                        $GLOBALS['dbi']->escapeString($username)
                    )
                ) . '.* TO \''
                . $GLOBALS['dbi']->escapeString($username)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\';';
            $sql_query .= $q;
            if (! $GLOBALS['dbi']->tryQuery($q)) {
                $message = Message::rawError($GLOBALS['dbi']->getError());
            }
        }

        if (isset($_REQUEST['createdb-2'])) {
            // Grant all privileges on wildcard name (username\_%)
            $q = 'GRANT ALL PRIVILEGES ON '
                . Util::backquote(
                    Util::escapeMysqlWildcards(
                        $GLOBALS['dbi']->escapeString($username)
                    ) . '\_%'
                ) . '.* TO \''
                . $GLOBALS['dbi']->escapeString($username)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\';';
            $sql_query .= $q;
            if (! $GLOBALS['dbi']->tryQuery($q)) {
                $message = Message::rawError($GLOBALS['dbi']->getError());
            }
        }

        if (isset($_REQUEST['createdb-3'])) {
            // Grant all privileges on the specified database to the new user
            $q = 'GRANT ALL PRIVILEGES ON '
            . Util::backquote(
                $GLOBALS['dbi']->escapeString($dbname)
            ) . '.* TO \''
            . $GLOBALS['dbi']->escapeString($username)
            . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\';';
            $sql_query .= $q;
            if (! $GLOBALS['dbi']->tryQuery($q)) {
                $message = Message::rawError($GLOBALS['dbi']->getError());
            }
        }
        return array($sql_query, $message);
    }

    /**
     * Get the hashed string for password
     *
     * @param string $password password
     *
     * @return string $hashedPassword
     */
    public static function getHashedPassword($password)
    {
        $result = $GLOBALS['dbi']->fetchSingleRow(
            "SELECT PASSWORD('" . $password . "') AS `password`;"
        );

        $hashedPassword = $result['password'];

        return $hashedPassword;
    }

    /**
     * Check if MariaDB's 'simple_password_check'
     * OR 'cracklib_password_check' is ACTIVE
     *
     * @return boolean if atleast one of the plugins is ACTIVE
     */
    public static function checkIfMariaDBPwdCheckPluginActive()
    {
        $serverVersion = $GLOBALS['dbi']->getVersion();
        if (!(Util::getServerType() == 'MariaDB' && $serverVersion >= 100002)) {
            return false;
        }

        $result = $GLOBALS['dbi']->tryQuery(
            'SHOW PLUGINS SONAME LIKE \'%_password_check%\''
        );

        /* Plugins are not working, for example directory does not exists */
        if ($result === false) {
            return false;
        }

        while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
            if ($row['Status'] === 'ACTIVE') {
                return true;
            }
        }

        return false;
    }


    /**
     * Get SQL queries for Display and Add user
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $password password
     *
     * @return array ($create_user_real, $create_user_show,$real_sql_query, $sql_query
     *                $password_set_real, $password_set_show)
     */
    public static function getSqlQueriesForDisplayAndAddUser($username, $hostname, $password)
    {
        $slashedUsername = $GLOBALS['dbi']->escapeString($username);
        $slashedHostname = $GLOBALS['dbi']->escapeString($hostname);
        $slashedPassword = $GLOBALS['dbi']->escapeString($password);
        $serverType = Util::getServerType();
        $serverVersion = $GLOBALS['dbi']->getVersion();

        $create_user_stmt = sprintf(
            'CREATE USER \'%s\'@\'%s\'',
            $slashedUsername,
            $slashedHostname
        );
        $isMariaDBPwdPluginActive = self::checkIfMariaDBPwdCheckPluginActive();

        // See https://github.com/phpmyadmin/phpmyadmin/pull/11560#issuecomment-147158219
        // for details regarding details of syntax usage for various versions

        // 'IDENTIFIED WITH auth_plugin'
        // is supported by MySQL 5.5.7+
        if (($serverType == 'MySQL' || $serverType == 'Percona Server')
            && $serverVersion >= 50507
            && isset($_REQUEST['authentication_plugin'])
        ) {
            $create_user_stmt .= ' IDENTIFIED WITH '
                . $_REQUEST['authentication_plugin'];
        }

        // 'IDENTIFIED VIA auth_plugin'
        // is supported by MariaDB 5.2+
        if ($serverType == 'MariaDB'
            && $serverVersion >= 50200
            && isset($_REQUEST['authentication_plugin'])
            && ! $isMariaDBPwdPluginActive
        ) {
            $create_user_stmt .= ' IDENTIFIED VIA '
                . $_REQUEST['authentication_plugin'];
        }

        $create_user_real = $create_user_show = $create_user_stmt;

        $password_set_stmt = 'SET PASSWORD FOR \'%s\'@\'%s\' = \'%s\'';
        $password_set_show = sprintf(
            $password_set_stmt,
            $slashedUsername,
            $slashedHostname,
            '***'
        );

        $sql_query_stmt = sprintf(
            'GRANT %s ON *.* TO \'%s\'@\'%s\'',
            join(', ', self::extractPrivInfo()),
            $slashedUsername,
            $slashedHostname
        );
        $real_sql_query = $sql_query = $sql_query_stmt;

        // Set the proper hashing method
        if (isset($_REQUEST['authentication_plugin'])) {
            self::setProperPasswordHashing(
                $_REQUEST['authentication_plugin']
            );
        }

        // Use 'CREATE USER ... WITH ... AS ..' syntax for
        // newer MySQL versions
        // and 'CREATE USER ... VIA .. USING ..' syntax for
        // newer MariaDB versions
        if ((($serverType == 'MySQL' || $serverType == 'Percona Server')
            && $serverVersion >= 50706)
            || ($serverType == 'MariaDB'
            && $serverVersion >= 50200)
        ) {
            $password_set_real = null;

            // Required for binding '%' with '%s'
            $create_user_stmt = str_replace(
                '%', '%%', $create_user_stmt
            );

            // MariaDB uses 'USING' whereas MySQL uses 'AS'
            // but MariaDB with validation plugin needs cleartext password
            if ($serverType == 'MariaDB'
                && ! $isMariaDBPwdPluginActive
            ) {
                $create_user_stmt .= ' USING \'%s\'';
            } elseif ($serverType == 'MariaDB') {
                $create_user_stmt .= ' IDENTIFIED BY \'%s\'';
            } else {
                $create_user_stmt .= ' AS \'%s\'';
            }

            if ($_POST['pred_password'] == 'keep') {
                $create_user_real = sprintf(
                    $create_user_stmt,
                    $slashedPassword
                );
                $create_user_show = sprintf(
                    $create_user_stmt,
                    '***'
                );
            } elseif ($_POST['pred_password'] == 'none') {
                $create_user_real = sprintf(
                    $create_user_stmt,
                    null
                );
                $create_user_show = sprintf(
                    $create_user_stmt,
                    '***'
                );
            } else {
                if (! ($serverType == 'MariaDB'
                    && $isMariaDBPwdPluginActive)
                ) {
                    $hashedPassword = self::getHashedPassword($_POST['pma_pw']);
                } else {
                    // MariaDB with validation plugin needs cleartext password
                    $hashedPassword = $_POST['pma_pw'];
                }
                $create_user_real = sprintf(
                    $create_user_stmt,
                    $hashedPassword
                );
                $create_user_show = sprintf(
                    $create_user_stmt,
                    '***'
                );
            }
        } else {
            // Use 'SET PASSWORD' syntax for pre-5.7.6 MySQL versions
            // and pre-5.2.0 MariaDB versions
            if ($_POST['pred_password'] == 'keep') {
                $password_set_real = sprintf(
                    $password_set_stmt,
                    $slashedUsername,
                    $slashedHostname,
                    $slashedPassword
                );
            } elseif ($_POST['pred_password'] == 'none') {
                $password_set_real = sprintf(
                    $password_set_stmt,
                    $slashedUsername,
                    $slashedHostname,
                    null
                );
            } else {
                $hashedPassword = self::getHashedPassword($_POST['pma_pw']);
                $password_set_real = sprintf(
                    $password_set_stmt,
                    $slashedUsername,
                    $slashedHostname,
                    $hashedPassword
                );
            }
        }

        // add REQUIRE clause
        $require_clause = self::getRequireClause();
        $real_sql_query .= $require_clause;
        $sql_query .= $require_clause;

        $with_clause = self::getWithClauseForAddUserAndUpdatePrivs();
        $real_sql_query .= $with_clause;
        $sql_query .= $with_clause;

        if (isset($create_user_real)) {
            $create_user_real .= ';';
            $create_user_show .= ';';
        }
        $real_sql_query .= ';';
        $sql_query .= ';';
        // No Global GRANT_OPTION privilege
        if (!$GLOBALS['is_grantuser']) {
            $real_sql_query = '';
            $sql_query = '';
        }

        // Use 'SET PASSWORD' for pre-5.7.6 MySQL versions
        // and pre-5.2.0 MariaDB
        if (($serverType == 'MySQL'
            && $serverVersion >= 50706)
            || ($serverType == 'MariaDB'
            && $serverVersion >= 50200)
        ) {
            $password_set_real = null;
            $password_set_show = null;
        } else {
            $password_set_real .= ";";
            $password_set_show .= ";";
        }

        return array($create_user_real,
            $create_user_show,
            $real_sql_query,
            $sql_query,
            $password_set_real,
            $password_set_show
        );
    }

    /**
     * Returns the type ('PROCEDURE' or 'FUNCTION') of the routine
     *
     * @param string $dbname      database
     * @param string $routineName routine
     *
     * @return string type
     */
    public static function getRoutineType($dbname, $routineName)
    {
        $routineData = $GLOBALS['dbi']->getRoutines($dbname);

        foreach ($routineData as $routine) {
            if ($routine['name'] === $routineName) {
                return $routine['type'];
            }
        }
        return '';
    }
}
