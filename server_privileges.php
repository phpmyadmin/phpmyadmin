<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/server_privileges.lib.php';

/**
 * Does the common work
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');
$common_functions = PMA_CommonFunctions::getInstance();

$_add_user_error = false;

/**
 * Sets globals from $_GET
 */

$get_params = array(
    'checkprivs',
    'db',
    'dbname',
    'hostname',
    'initial',
    'old_username',
    'old_hostname',
    'tablename',
    'username',
    'viewing_mode'
);
foreach ($get_params as $one_get_param) {
    if (isset($_REQUEST[$one_get_param])) {
        $GLOBALS[$one_get_param] = $_REQUEST[$one_get_param];
    }
}
/**
 * Sets globals from $_POST
 */

$post_params = array(
    'createdb-1',
    'createdb-2',
    'createdb-3',
    'grant_count',
    'hostname',
    'pma_pw',
    'pma_pw2',
    'pred_hostname',
    'pred_password',
    'pred_username',
    'update_privs',
    'username'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

/**
 * Sets globals from $_POST patterns, for privileges and max_* vars
 */

$post_patterns = array(
    '/_priv$/i',
    '/^max_/i'
);
foreach (array_keys($_POST) as $post_key) {
    foreach ($post_patterns as $one_post_pattern) {
        if (preg_match($one_post_pattern, $post_key)) {
            $GLOBALS[$post_key] = $_POST[$post_key];
        }
    }
}

require 'libraries/server_common.inc.php';

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
    $db_and_table = $common_functions->backquote($common_functions->unescapeMysqlWildcards($dbname)) . '.';
    if (isset($tablename)) {
        $db_and_table .= $common_functions->backquote($tablename);
    } else {
        $db_and_table .= '*';
    }
} else {
    $db_and_table = '*.*';
}

$dbname_is_wildcard = false;
// check if given $dbname is a wildcard or not
if (isset($dbname)) {
    //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
    if (preg_match('/(?<!\\\\)(?:_|%)/i', $dbname)) {
        $dbname_is_wildcard = true;
    }
}

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $is_superuser) {
    echo '<h2>' . "\n"
       . $common_functions->getIcon('b_usrlist.png')
       . __('Privileges') . "\n"
       . '</h2>' . "\n";
    PMA_Message::error(__('No Privileges'))->display();
    exit;
}

// a random number that will be appended to the id of the user forms
$random_n = mt_rand(0, 1000000);

/**
 * Changes / copies a user, part I
 */
if (isset($_REQUEST['change_copy'])) {
    $user_host_condition = ' WHERE `User`'
        .' = \'' . $common_functions->sqlAddSlashes($old_username) . "'"
        .' AND `Host`'
        .' = \'' . $common_functions->sqlAddSlashes($old_hostname) . '\';';
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
        . " WHERE `User` = '" . $common_functions->sqlAddSlashes($username) . "'"
        . " AND `Host` = '" . $common_functions->sqlAddSlashes($hostname) . "';";
    if (PMA_DBI_fetch_value($sql) == 1) {
        $message = PMA_Message::error(__('The user %s already exists!'));
        $message->addParam('[i]\'' . $username . '\'@\'' . $hostname . '\'[/i]');
        $_REQUEST['adduser'] = true;
        $_add_user_error = true;
    } else {

        $create_user_real = 'CREATE USER \'' . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\'';

        $real_sql_query = 'GRANT ' . join(', ', PMA_extractPrivInfo()) . ' ON *.* TO \''
            . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\'';
        if ($pred_password != 'none' && $pred_password != 'keep') {
            $sql_query = $real_sql_query . ' IDENTIFIED BY \'***\'';
            $real_sql_query .= ' IDENTIFIED BY \'' . $common_functions->sqlAddSlashes($pma_pw) . '\'';
            if (isset($create_user_real)) {
                $create_user_show = $create_user_real . ' IDENTIFIED BY \'***\'';
                $create_user_real .= ' IDENTIFIED BY \'' . $common_functions->sqlAddSlashes($pma_pw) . '\'';
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

        if ((isset($Grant_priv) && $Grant_priv == 'Y')
            || (isset($max_questions) || isset($max_connections)
            || isset($max_updates) || isset($max_user_connections))
        ) {
            $real_sql_query .= PMA_getCommonSQlQueryForAddUserAndUpdatePrivs(
                $max_questions, $max_connections,$max_updates, 
                $max_user_connections
            );
            $sql_query .= $real_sql_query;
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
                $_REQUEST['createdb-1'] = $_REQUEST['createdb-2'] = $_REQUEST['createdb-3'] = false;
                $message = PMA_Message::rawError(PMA_DBI_getError());
            } else {
                $message = PMA_Message::success(__('You have added a new user.'));
            }

            if (isset($_REQUEST['createdb-1'])) {
                // Create database with same name and grant all privileges
                $q = 'CREATE DATABASE IF NOT EXISTS '
                    . $common_functions->backquote($common_functions->sqlAddSlashes($username)) . ';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }


                /**
                 * If we are not in an Ajax request, we can't reload navigation now
                 */
                if ($GLOBALS['is_ajax_request'] != true) {
                    // this is needed in case tracking is on:
                    $GLOBALS['db'] = $username;
                    $GLOBALS['reload'] = true;
                    echo $common_functions->getReloadNavigationScript();
                }

                $q = 'GRANT ALL PRIVILEGES ON '
                    . $common_functions->backquote($common_functions->escapeMysqlWildcards($common_functions->sqlAddSlashes($username))) . '.* TO \''
                    . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
            }

            if (isset($_REQUEST['createdb-2'])) {
                // Grant all privileges on wildcard name (username\_%)
                $q = 'GRANT ALL PRIVILEGES ON '
                    . $common_functions->backquote($common_functions->sqlAddSlashes($username) . '\_%') . '.* TO \''
                    . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
            }

            if (isset($_REQUEST['createdb-3'])) {
                // Grant all privileges on the specified database to the new user
                $q = 'GRANT ALL PRIVILEGES ON '
                . $common_functions->backquote($common_functions->sqlAddSlashes($dbname)) . '.* TO \''
                . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
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
        .' = \'' . $common_functions->sqlAddSlashes($old_username) . "'"
        .' AND `Host`'
        .' = \'' . $common_functions->sqlAddSlashes($old_hostname) . '\';';
    $res = PMA_DBI_query('SELECT * FROM `mysql`.`db`' . $user_host_condition);
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $queries[] = 'GRANT ' . join(', ', PMA_extractPrivInfo($row))
            .' ON ' . $common_functions->backquote($row['Db']) . '.*'
            .' TO \'' . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\''
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
            .' = \'' . $common_functions->sqlAddSlashes($old_username) . "'"
            .' AND `Host`'
            .' = \'' . $common_functions->sqlAddSlashes($old_hostname) . '\''
            .' AND `Db`'
            .' = \'' . $common_functions->sqlAddSlashes($row['Db']) . "'"
            .' AND `Table_name`'
            .' = \'' . $common_functions->sqlAddSlashes($row['Table_name']) . "'"
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
            . ' ON ' . $common_functions->backquote($row['Db']) . '.' . $common_functions->backquote($row['Table_name'])
            . ' TO \'' . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\''
            . (in_array('Grant', explode(',', $row['Table_priv'])) ? ' WITH GRANT OPTION;' : ';');
    }
}

/**
 * Updates privileges
 */
if (! empty($update_privs)) {
    $db_and_table = PMA_wildcardEscapeForGrant($dbname, (isset($tablename) ? $tablename : ''));

    $sql_query0 = 'REVOKE ALL PRIVILEGES ON ' . $db_and_table
        . ' FROM \'' . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\';';
    if (! isset($Grant_priv) || $Grant_priv != 'Y') {
        $sql_query1 = 'REVOKE GRANT OPTION ON ' . $db_and_table
            . ' FROM \'' . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\';';
    } else {
        $sql_query1 = '';
    }

    // Should not do a GRANT USAGE for a table-specific privilege, it
    // causes problems later (cannot revoke it)
    if (! (isset($tablename) && 'USAGE' == implode('', PMA_extractPrivInfo()))) {
        $sql_query2 = 'GRANT ' . join(', ', PMA_extractPrivInfo())
            . ' ON ' . $db_and_table
            . ' TO \'' . $common_functions->sqlAddSlashes($username) . '\'@\'' . $common_functions->sqlAddSlashes($hostname) . '\'';

        if ((isset($Grant_priv) && $Grant_priv == 'Y')
            || (! isset($dbname)
            && (isset($max_questions) || isset($max_connections)
            || isset($max_updates) || isset($max_user_connections)))
        ) {
            $sql_query2 .= PMA_getCommonSQlQueryForAddUserAndUpdatePrivs(
                $Grant_priv, $max_questions, $max_connections, $max_updates,
                $max_user_connections
            );
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
    list ($message, $sql_query) = PMA_getMessageAndSqlQueryForPrivilegesRevoke(
        $db_and_table, $dbname, $tablename, $username, $hostname
    );
}

/**
 * Updates the password
 */
if (isset($_REQUEST['change_pw'])) {
    $message = PMA_getMessageForUpdatePassword(
        $pma_pw, $pma_pw2, $err_url, $username, $hostname
    );
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
        $queries[] = 'DROP USER \'' . $common_functions->sqlAddSlashes($this_user) . '\'@\'' . $common_functions->sqlAddSlashes($this_host) . '\';';

        if (isset($_REQUEST['drop_users_db'])) {
            $queries[] = 'DROP DATABASE IF EXISTS ' . $common_functions->backquote($this_user) . ';';
            $GLOBALS['reload'] = true;

            if ($GLOBALS['is_ajax_request'] != true) {
                echo $common_functions->getReloadNavigationScript();
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
 * some standard links
 */
list($link_edit, $link_revoke, $link_export, $link_export_all)
    = PMA_getStandardLinks($conditional_class);

/**
 * If we are in an Ajax request for Create User/Edit User/Revoke User/
 * Flush Privileges, show $message and exit.
 */
if ($GLOBALS['is_ajax_request']
    && ! isset($_REQUEST['export'])
    && (! isset($_REQUEST['submit_mult']) || $_REQUEST['submit_mult'] != 'export')
    && (! isset($_REQUEST['adduser']) || $_add_user_error)
    && (! isset($_REQUEST['initial']) || empty($_REQUEST['initial']))
    && ! isset($_REQUEST['showall'])
    && ! isset($_REQUEST['edit_user_dialog'])
    && ! isset($_REQUEST['db_specific']))
{
    $isPass = false;
    if (isset($password)) {
        $isPass = true;
    }
    $extra_data = PMA_getExtraDataForAjaxBehavior( $isPass,
        $sql_query, $link_edit, $dbname_is_wildcard, $link_export
    );

    if ($message instanceof PMA_Message) {
        $response = PMA_Response::getInstance();
        $response->isSuccess($message->isSuccess());
        $response->addJSON('message', $message);
        $response->addJSON($extra_data);
        exit;
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
    include 'libraries/db_info.inc.php';
    echo "\n";
} else {
    if (! empty($GLOBALS['message'])) {
        echo $common_functions->getMessage($GLOBALS['message']);
        unset($GLOBALS['message']);
    }
}

/**
 * Displays the page
 */

// export user definition
if (isset($_REQUEST['export']) || (isset($_REQUEST['submit_mult']) && $_REQUEST['submit_mult'] == 'export')) {
    $export = '<textarea class="export" cols="' . $GLOBALS['cfg']['TextareaCols'] . '" rows="' . $GLOBALS['cfg']['TextareaRows'] . '">';
    if ($username == '%') {
        // export privileges for all users
        $title = __('Privileges for all users');
        foreach ($_SESSION['user_host_pairs'] as $pair) {
            $export .= PMA_getGrants($pair['user'], $pair['host']);
            $export .= "\n";
        }
    } elseif (isset($_REQUEST['selected_usr'])) {
        // export privileges for selected users
        $title = __('Privileges');
        foreach ($_REQUEST['selected_usr'] as $export_user) {
            $export_username = substr($export_user, 0, strpos($export_user, '&'));
            $export_hostname = substr($export_user, strrpos($export_user, ';') + 1);
            $export .= '# '
                . sprintf(
                    __('Privileges for %s'),
                    '`' . htmlspecialchars($export_username) . '`@`' . htmlspecialchars($export_hostname) . '`'
                )
                . "\n\n";
            $export .= PMA_getGrants($export_username, $export_hostname) . "\n";
        }
    } else {
        // export privileges for a single user
        $title = __('User') . ' `' . htmlspecialchars($username) . '`@`' . htmlspecialchars($hostname) . '`';
        $export .= PMA_getGrants($username, $hostname);
    }
    // remove trailing whitespace
    $export = trim($export);

    $export .= '</textarea>';
    unset($username, $hostname, $grants, $one_grant);
    if ($GLOBALS['is_ajax_request']) {
        $response = PMA_Response::getInstance();
        $response->addJSON('message', $export);
        $response->addJSON('title', $title);
        exit;
    } else {
        echo "<h2>$title</h2>$export";
    }
}

if (empty($_REQUEST['adduser']) && (! isset($checkprivs) || ! strlen($checkprivs))) {
    if (! isset($username)) {
        // No username is given --> display the overview
        echo '<h2>' . "\n"
           . $common_functions->getIcon('b_usrlist.png')
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
                    . 'Please run the <code>mysql_upgrade</code> command'
                    . '(<code>mysql_fix_privilege_tables</code> on older systems)'
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
                echo PMA_getHtmlForDisplayTheInitials($array_initials, $conditional_class);
            }

            /**
            * Display the user overview
            * (if less than 50 users, display them immediately)
            */

            if (isset($initial) || isset($showall) || PMA_DBI_num_rows($res) < 50) {

                echo PMA_displayUserOverview($res, $db_rights, $link_edit,
                    $pmaThemeImage, $text_dir, $conditional_class, $link_export, $link_export_all
                );
            } else {

                unset ($row);
                echo '    <fieldset id="fieldset_add_user">' . "\n"
                   . '        <a href="server_privileges.php?' . $GLOBALS['url_query'] . '&amp;adduser=1" class="' . $conditional_class . '">' . "\n"
                   . $common_functions->getIcon('b_usradd.png')
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
           . $common_functions->getIcon('b_usredit.png')
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
            . " WHERE `User` = '" . $common_functions->sqlAddSlashes($username) . "'"
            . " AND `Host` = '" . $common_functions->sqlAddSlashes($hostname) . "';";
        $user_does_not_exists = (bool) ! PMA_DBI_fetch_value($sql);
        unset($sql);
        if ($user_does_not_exists) {
            PMA_Message::error(__('The selected user was not found in the privilege table.'))->display();
            echo PMA_getHtmlForDisplayLoginInformationFields();
            //exit;
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

        echo PMA_getHtmlToDisplayPrivilegesTable(
            $random_n,
            PMA_ifSetOr($dbname, '*', 'length'),
            PMA_ifSetOr($tablename, '*', 'length')
        );

        echo '</form>' . "\n";

        if (! isset($tablename) && empty($dbname_is_wildcard)) {

            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards

            echo '<form action="server_privileges.php" id="db_or_table_specific_priv" method="post">' . "\n";
            
            list($html_output, $found_rows) = PMA_getTableForDisplayAllTableSpecificRights(
                $username, $hostname, $dbname, $link_edit, $link_revoke
            );
            echo $html_output;

            if (! isset($dbname)) {
                // no database name was given, display select db
                echo PMA_getHTmlForDisplaySelectDbInEditPrivs($found_rows);

            } else {
                echo PMA_displayTablesInEditPrivs($dbname, $found_rows);
            }
            echo '</fieldset>' . "\n";
            echo '<fieldset class="tblFooters">' . "\n"
               . '    <input type="submit" value="' . __('Go') . '" />'
               . '</fieldset>' . "\n"
               . '</form>' . "\n";
        }

        // Provide a line with links to the relevant database and table
        if (isset($dbname) && empty($dbname_is_wildcard)) {
            echo PMA_getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        }

        if (! isset($dbname) && ! $user_does_not_exists) {
            //change login information
            include_once 'libraries/display_change_password.lib.php';
            echo PMA_getChangeLoginInformationHtmlForm($username, $hostname);
        }
    }
} elseif (isset($_REQUEST['adduser'])) {
    // Add user
    $response->addHTML(
        PMA_getHtmlForAddUser($random_n, $dbname)
    );
} else {
    // check the privileges for a particular database.
    $response->addHTML(
        PMA_getUserForm($checkprivs, $link_edit, $conditional_class)
    );    
} // end if (empty($_REQUEST['adduser']) && empty($checkprivs)) ... elseif ... else ...

?>
