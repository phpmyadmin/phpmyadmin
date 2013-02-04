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
require_once 'libraries/display_change_password.lib.php';
require_once 'libraries/server_privileges.lib.php';

/**
 * Does the common work
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');

$_add_user_error = false;

if (isset ($_REQUEST['username'])) {
    $username = $_REQUEST['username'];
}
if (isset ($_REQUEST['hostname'])) {
    $hostname = $_REQUEST['hostname'];
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

$conditional_class = 'ajax';

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

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $is_superuser) {
    $response->addHTML(
        '<h2>' . "\n"
        . PMA_Util::getIcon('b_usrlist.png')
        . __('Privileges') . "\n"
        . '</h2>' . "\n"
    );
    $response->addHTML(PMA_Message::error(__('No Privileges'))->getDisplay());
    exit;
}

/**
 * Changes / copies a user, part I
 */
if (isset($_REQUEST['change_copy'])) {
    $user_host_condition = ' WHERE `User` = '
        . "'". PMA_Util::sqlAddSlashes($_REQUEST['old_username']) . "'"
        . ' AND `Host` = '
        . "'" . PMA_Util::sqlAddSlashes($_REQUEST['old_hostname']) . "';";
    $row = PMA_DBI_fetch_single_row(
        'SELECT * FROM `mysql`.`user` ' . $user_host_condition
    );
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
        $_user_name = PMA_DBI_fetch_value('SELECT USER()');
        $hostname = substr($_user_name, (strrpos($_user_name, '@') + 1));
        unset($_user_name);
        break;
    }
    $sql = "SELECT '1' FROM `mysql`.`user`"
        . " WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
        . " AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";
    if (PMA_DBI_fetch_value($sql) == 1) {
        $message = PMA_Message::error(__('The user %s already exists!'));
        $message->addParam('[em]\'' . $username . '\'@\'' . $hostname . '\'[/em]');
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
                if (! PMA_DBI_try_query($create_user_real)) {
                    $_error = true;
                }
                $sql_query = $create_user_show . $sql_query;
            }
            list($sql_query, $message) = PMA_addUserAndCreateDatabase(
                $_error, $real_sql_query, $sql_query, $username, $hostname,
                isset($dbname) ? $dbname : null
            );

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
        unset($res, $real_sql_query);
    }
}

/**
 * Changes / copies a user, part III
 */
if (isset($_REQUEST['change_copy'])) {
    $queries = PMA_getDbSpecificPrivsQueriesForChangeOrCopyUser(
        $queries, $username, $hostname
    );
}

/**
 * Updates privileges
 */
if (! empty($_POST['update_privs'])) {
    list($sql_query, $message) = PMA_updatePrivileges(
        $username,
        $hostname,
        (isset($tablename) ? $tablename : ''),
        (isset($dbname) ? $dbname : '')
    );
}

/**
 * Revokes Privileges
 */
if (isset($_REQUEST['revokeall'])) {
    list ($message, $sql_query) = PMA_getMessageAndSqlQueryForPrivilegesRevoke(
        $db_and_table,
        (isset($dbname) ? $dbname : ''),
        (isset($tablename) ? $tablename : ''),
        $username, $hostname
    );
}

/**
 * Updates the password
 */
if (isset($_REQUEST['change_pw'])) {
    $message = PMA_getMessageForUpdatePassword(
        $err_url, $username, $hostname
    );
}

/**
 * Deletes users
 *   (Changes / copies a user, part IV)
 */
if (isset($_REQUEST['delete'])
    || (isset($_REQUEST['change_copy']) && $_REQUEST['mode'] < 4)
) {
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
    if (empty($_REQUEST['change_copy'])) {
        list($sql_query, $message) = PMA_deleteUser($queries);
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
list($link_edit, $link_revoke, $link_export)
    = PMA_getStandardLinks($conditional_class);

/**
 * If we are in an Ajax request for Create User/Edit User/Revoke User/
 * Flush Privileges, show $message and exit.
 */
if ($GLOBALS['is_ajax_request']
    && empty($_REQUEST['ajax_page_request'])
    && ! isset($_REQUEST['export'])
    && (! isset($_REQUEST['submit_mult']) || $_REQUEST['submit_mult'] != 'export')
    && (! isset($_REQUEST['adduser']) || $_add_user_error)
    && (! isset($_REQUEST['initial']) || empty($_REQUEST['initial']))
    && ! isset($_REQUEST['showall'])
    && ! isset($_REQUEST['edit_user_dialog'])
    && ! isset($_REQUEST['db_specific'])
) {
    $extra_data = PMA_getExtraDataForAjaxBehavior(
        (isset($password) ? $password : ''),
        $link_export,
        (isset($sql_query) ? $sql_query : ''),
        $link_edit,
        (isset($hostname) ? $hostname : ''),
        (isset($username) ? $username : '')
    );

    if (! empty($message) && $message instanceof PMA_Message) {
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
if (isset($_REQUEST['viewing_mode']) && $_REQUEST['viewing_mode'] == 'db') {
    $_REQUEST['db'] = $_REQUEST['checkprivs'];

    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    ob_start();
    include 'libraries/db_info.inc.php';
    $content = ob_get_contents();
    ob_end_clean();
    $response->addHTML($content . "\n");
} else {
    if (! empty($GLOBALS['message'])) {
        $response->addHTML(PMA_Util::getMessage($GLOBALS['message']));
        unset($GLOBALS['message']);
    }
}

/**
 * Displays the page
 */

// export user definition
if (isset($_REQUEST['export'])
    || (isset($_REQUEST['submit_mult']) && $_REQUEST['submit_mult'] == 'export')
) {
    list($title, $export) = PMA_getHtmlForExportUserDefinition(
        isset($username) ? $username : null,
        isset($hostname) ? $hostname : null
    );

    unset($username, $hostname, $grants, $one_grant);

    $response = PMA_Response::getInstance();
    if ($GLOBALS['is_ajax_request']) {
        $response->addJSON('message', $export);
        $response->addJSON('title', $title);
        exit;
    } else {
        $response->addHTML("<h2>$title</h2>$export");
    }
}

if (empty($_REQUEST['adduser'])
    && (! isset($_REQUEST['checkprivs'])
    || ! strlen($_REQUEST['checkprivs']))
) {
    if (! isset($username)) {
        // No username is given --> display the overview
        $response->addHTML(
            PMA_getHtmlForDisplayUserOverviewPage(
                $link_edit, $pmaThemeImage, $text_dir,
                $conditional_class, $link_export
            )
        );
    } else {
        // A user was selected -> display the user's properties

        // In an Ajax request, prevent cached values from showing
        if ($GLOBALS['is_ajax_request'] == true) {
            header('Cache-Control: no-cache');
        }
        $url_dbname = urlencode(
            str_replace(
                array('\_', '\%'),
                array('_', '%'), $_REQUEST['dbname']
            )
        );
        $response->addHTML(
            PMA_getHtmlForDisplayUserProperties(
                ((isset ($dbname_is_wildcard)) ? $dbname_is_wildcard : ''),
                $url_dbname, $username, $hostname, $link_edit, $link_revoke,
                (isset($dbname) ? $dbname : ''),
                (isset($tablename) ? $tablename : '')
            )
        );
    }
} elseif (isset($_REQUEST['adduser'])) {
    // Add user
    $response->addHTML(
        PMA_getHtmlForAddUser((isset($dbname) ? $dbname : ''))
    );
} else {
    // check the privileges for a particular database.
    $response->addHTML(
        PMA_getHtmlForSpecificDbPrivileges($link_edit, $conditional_class)
    );
} // end if (empty($_REQUEST['adduser']) && empty($checkprivs))... elseif... else...

?>
