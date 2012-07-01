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
    $db_and_table = PMA_backquote(PMA_unescapeMysqlWildcards($dbname)) . '.';
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
    echo '<h2>' . "\n"
       . PMA_getIcon('b_usrlist.png')
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
                $_REQUEST['createdb-1'] = $_REQUEST['createdb-2'] = $_REQUEST['createdb-3'] = false;
                $message = PMA_Message::rawError(PMA_DBI_getError());
            } else {
                $message = PMA_Message::success(__('You have added a new user.'));
            }

            if (isset($_REQUEST['createdb-1'])) {
                // Create database with same name and grant all privileges
                $q = 'CREATE DATABASE IF NOT EXISTS '
                    . PMA_backquote(PMA_sqlAddSlashes($username)) . ';';
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
                    echo PMA_getReloadNavigationScript();
                }

                $q = 'GRANT ALL PRIVILEGES ON '
                    . PMA_backquote(PMA_escapeMysqlWildcards(PMA_sqlAddSlashes($username))) . '.* TO \''
                    . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
            }

            if (isset($_REQUEST['createdb-2'])) {
                // Grant all privileges on wildcard name (username\_%)
                $q = 'GRANT ALL PRIVILEGES ON '
                    . PMA_backquote(PMA_sqlAddSlashes($username) . '\_%') . '.* TO \''
                    . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
                $sql_query .= $q;
                if (! PMA_DBI_try_query($q)) {
                    $message = PMA_Message::rawError(PMA_DBI_getError());
                }
            }

            if (isset($_REQUEST['createdb-3'])) {
                // Grant all privileges on the specified database to the new user
                $q = 'GRANT ALL PRIVILEGES ON '
                . PMA_backquote(PMA_sqlAddSlashes($dbname)) . '.* TO \''
                . PMA_sqlAddSlashes($username) . '\'@\'' . PMA_sqlAddSlashes($hostname) . '\';';
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
    list ($message, $sql_query) = PMA_getMessageAndSqlQueryForPrivilegesRevoke(
        $db_and_table, $dbname, $tablename, $sql_query0, $sql_query1, $username,
        $hostname
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
        $queries[] = 'DROP USER \'' . PMA_sqlAddSlashes($this_user) . '\'@\'' . PMA_sqlAddSlashes($this_host) . '\';';

        if (isset($_REQUEST['drop_users_db'])) {
            $queries[] = 'DROP DATABASE IF EXISTS ' . PMA_backquote($this_user) . ';';
            $GLOBALS['reload'] = true;

            if ($GLOBALS['is_ajax_request'] != true) {
                echo PMA_getReloadNavigationScript();
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

$link_export_all = '<a class="export_user_anchor ' . $conditional_class . '" href="server_privileges.php?' . str_replace('%', '%%', $GLOBALS['url_query'])
    . '&amp;username=%s'
    . '&amp;hostname=%s'
    . '&amp;initial=%s'
    . '&amp;export=1">'
    . PMA_getIcon('b_tblexport.png', __('Export all'))
    . '</a>';

/**
 * If we are in an Ajax request for Create User/Edit User/Revoke User/
 * Flush Privileges, show $message and exit.
 */
if ($GLOBALS['is_ajax_request'] && ! isset($_REQUEST['export']) && (! isset($_REQUEST['submit_mult']) || $_REQUEST['submit_mult'] != 'export') && (! isset($_REQUEST['adduser']) || $_add_user_error) && (! isset($_REQUEST['initial']) || empty($_REQUEST['initial'])) && ! isset($_REQUEST['showall']) && ! isset($_REQUEST['edit_user_dialog']) && ! isset($_REQUEST['db_specific'])) {

    if (isset($sql_query)) {
        $extra_data['sql_query'] = PMA_getMessage(null, $sql_query);
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
        $new_user_string .= '<td><code>' . join(', ', PMA_extractPrivInfo('', true)) . '</code></td>'; //Fill in privileges here
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
        echo PMA_getMessage($GLOBALS['message']);
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

                $_SESSION['user_host_pairs'] = array();
                $pair_count = 0;
                $odd_row = true;
                $index_checkbox = -1;
                foreach ($db_rights as $user) {
                    $index_checkbox++;
                    ksort($user);
                    foreach ($user as $host) {
                        $index_checkbox++;
                        echo '        <tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                           . '            <td><input type="checkbox" class="checkall" name="selected_usr[]" id="checkbox_sel_users_'
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
                           . '            <td><code>' . "\n"
                           . '                ' . implode(',' . "\n" . '            ', $host['privs']) . "\n"
                           . '                </code></td>' . "\n"
                           . '            <td>' . ($host['Grant_priv'] == 'Y' ? __('Yes') : __('No')) . '</td>' . "\n"
                           . '            <td class="center">';
                        printf($link_edit, urlencode($host['User']), urlencode($host['Host']), '', '');
                        echo '</td>';
                        echo '<td class="center">';
                        printf($link_export, urlencode($host['User']), urlencode($host['Host']), (isset($initial) ? $initial : ''));
                        echo '</td>';
                        echo '</tr>';
                        $odd_row = ! $odd_row;

                        $_SESSION['user_host_pairs'][$pair_count]['user'] = $host['User'];
                        $_SESSION['user_host_pairs'][$pair_count]['host'] = $host['Host'];
                        $pair_count ++;
                    }
                }

                unset($user, $host, $odd_row);
                echo '    </tbody></table>' . "\n"
                   .'<div>'
                   .'<div style="float:left;">'
                   .'<img class="selectallarrow"'
                   .' src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png"'
                   .' width="38" height="22"'
                   .' alt="' . __('With selected:') . '" />' . "\n"
                   .'<input type="checkbox" id="checkall" title="' . __('Check All') . '" /> '
                   .'<label for="checkall">' . __('Check All') . '</label> '
                   .'<i style="margin-left: 2em">' . __('With selected:') . '</i>' . "\n";

                echo PMA_getButtonOrImage(
                        'submit_mult', 'mult_submit', 'submit_mult_export',
                        __('Export'), 'b_tblexport.png', 'export'
                    );
                echo '<input type="hidden" name="initial" value="' . (isset($initial) ? $initial : '') . '" />';
                echo '</div>'
                   . '<div class="clear_both" style="clear:both"></div>'
                   . '<div style="float:left; padding-left:10px;">';
                printf($link_export_all, urlencode('%'), urlencode('%'), (isset($initial) ? $initial : ''));
                echo '</div>'
                   . '</div>'
                   . '<div class="clear_both" style="clear:both"></div>';

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
                    $db_rights_row['Db'] = PMA_escapeMysqlWildcards(
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
                       . '    <td><code>' . "\n"
                       . '        ' . join(',' . "\n" . '            ', PMA_extractPrivInfo($row, true)) . "\n"
                       . '        </code></td>' . "\n"
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
                        $current_db = PMA_escapeMysqlWildcards($current_db);
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
                if ($res = @PMA_DBI_try_query('SHOW TABLES FROM ' . PMA_backquote(PMA_unescapeMysqlWildcards($dbname)) . ';', null, PMA_DBI_QUERY_STORE)) {
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
            include_once 'libraries/display_change_password.lib.php';

            echo '<form action="server_privileges.php" method="post" class="copyUserForm">' . "\n"
               . PMA_generate_common_hidden_inputs('', '')
               . '<input type="hidden" name="old_username" value="' . htmlspecialchars($username) . '" />' . "\n"
               . '<input type="hidden" name="old_hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n"
               . '<fieldset id="fieldset_change_copy_user">' . "\n"
               . '    <legend>' . __('Change Login Information / Copy User') . '</legend>' . "\n"
               . PMA_getHtmlForDisplayLoginInformationFields('change');
            echo '    <fieldset>' . "\n"
                . '        <legend>' . __('Create a new user with the same privileges and ...') . '</legend>' . "\n";
            $choices = array(
                '4' => __('... keep the old one.'),
                '1' => __('... delete the old one from the user tables.'),
                '2' => __('... revoke all active privileges from the old one and delete it afterwards.'),
                '3' => __('... delete the old one from the user tables and reload the privileges afterwards.'));
            echo PMA_getRadioFields('mode', $choices, '4', true);
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
       . PMA_generate_common_hidden_inputs('', '')
       . PMA_getHtmlForDisplayLoginInformationFields('new');
    echo '<fieldset id="fieldset_add_user_database">' . "\n"
        . '<legend>' . __('Database for user') . '</legend>' . "\n";

    echo PMA_getCheckbox('createdb-1', __('Create database with same name and grant all privileges'), false, false);
    echo '<br />' . "\n";
    echo PMA_getCheckbox('createdb-2', __('Grant all privileges on wildcard name (username\\_%)'), false, false);
    echo '<br />' . "\n";

    if (! empty($dbname) ) {
        echo PMA_getCheckbox('createdb-3', sprintf(__('Grant all privileges on database &quot;%s&quot;'), htmlspecialchars($dbname)), true, false);
        echo '<input type="hidden" name="dbname" value="' . htmlspecialchars($dbname) . '" />' . "\n";
        echo '<br />' . "\n";
    }

    echo '</fieldset>' . "\n";
    echo PMA_getHtmlToDisplayPrivilegesTable($random_n, '*', '*', false);
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
                } elseif ($current['Db'] == PMA_escapeMysqlWildcards($checkprivs)) {
                    $user_form .= __('database-specific');
                } else {
                    $user_form .= __('wildcard'). ': <code>' . htmlspecialchars($current['Db']) . '</code>';
                }
                $user_form .= "\n"
                   . '        </td>' . "\n"
                   . '        <td>' . "\n"
                   . '            <code>' . "\n"
                   . '                ' . join(',' . "\n" . '                ', PMA_extractPrivInfo($current, true)) . "\n"
                   . '            </code>' . "\n"
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
        $message = PMA_Message::success(__('User has been added.'));
        $response = PMA_Response::getInstance();
        $response->addJSON('message', $message);
        $response->addJSON('user_form', $user_form);
        exit;
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

?>
