<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server privileges and users manipulations
 *
 * @package PhpMyAdmin
 */

/**
 * include common file
 */
require_once 'libraries/common.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/display_change_password.lib.php';
require_once 'libraries/server_privileges.lib.php';
require_once 'libraries/check_user_privileges.lib.php';

$cfgRelation = PMA_getRelationsParam();

/**
 * Does the common work
 */
$response = PMA\libraries\Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');

if ((isset($_REQUEST['viewing_mode'])
    && $_REQUEST['viewing_mode'] == 'server')
    && $GLOBALS['cfgRelation']['menuswork']
) {
    include_once 'libraries/server_users.lib.php';
    $response->addHTML('<div>');
    $response->addHTML(PMA_getHtmlForSubMenusOnUsersPage('server_privileges.php'));
}

/**
 * Sets globals from $_POST patterns, for privileges and max_* vars
 */

$post_patterns = array(
    '/_priv$/i',
    '/^max_/i'
);

PMA_setPostAsGlobal($post_patterns);

require 'libraries/server_common.inc.php';

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
$strPrivDescEvent = __('Allows to set up events for the event scheduler.');
$strPrivDescExecute = __('Allows executing stored routines.');
$strPrivDescFile = __('Allows importing data from and exporting data into files.');
$strPrivDescGrantTbl = __(
    'Allows user to give to other users or remove from other users the privileges '
    . 'that user possess yourself.'
);
$strPrivDescIndex = __('Allows creating and dropping indexes.');
$strPrivDescInsert = __('Allows inserting and replacing data.');
$strPrivDescLockTables = __('Allows locking tables for the current thread.');
$strPrivDescMaxConnections = __(
    'Limits the number of new connections the user may open per hour.'
);
$strPrivDescMaxQuestions = __(
    'Limits the number of queries the user may send to the server per hour.'
);
$strPrivDescMaxUpdates = __(
    'Limits the number of commands that change any table or database '
    . 'the user may execute per hour.'
);
$strPrivDescMaxUserConnections = __(
    'Limits the number of simultaneous connections the user may have.'
);
$strPrivDescProcess = __('Allows viewing processes of all users.');
$strPrivDescReferences = __('Has no effect in this MySQL version.');
$strPrivDescReload = __(
    'Allows reloading server settings and flushing the server\'s caches.'
);
$strPrivDescReplClient = __(
    'Allows the user to ask where the slaves / masters are.'
);
$strPrivDescReplSlave = __('Needed for the replication slaves.');
$strPrivDescSelect = __('Allows reading data.');
$strPrivDescShowDb = __('Gives access to the complete list of databases.');
$strPrivDescShowView = __('Allows performing SHOW CREATE VIEW queries.');
$strPrivDescShutdown = __('Allows shutting down the server.');
$strPrivDescSuper = __(
    'Allows connecting, even if maximum number of connections is reached; '
    . 'required for most administrative operations like setting global variables '
    . 'or killing threads of other users.'
);
$strPrivDescTrigger = __('Allows creating and dropping triggers.');
$strPrivDescUpdate = __('Allows changing data.');
$strPrivDescUsage = __('No privileges.');

$_add_user_error = false;
/**
 * Get DB information: username, hostname, dbname,
 * tablename, db_and_table, dbname_is_wildcard
 */
list(
    $username, $hostname, $dbname, $tablename, $routinename,
    $db_and_table, $dbname_is_wildcard
) = PMA_getDataForDBInfo();

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$GLOBALS['is_superuser'] && !$GLOBALS['is_grantuser']
    && !$GLOBALS['is_createuser']
) {
    $response->addHTML(PMA_getHtmlForSubPageHeader('privileges', '', false));
    $response->addHTML(
        PMA\libraries\Message::error(__('No Privileges'))
            ->getDisplay()
    );
    exit;
}

/**
 * Checks if the user is using "Change Login Information / Copy User" dialog
 * only to update the password
 */
if (isset($_REQUEST['change_copy']) && $username == $_REQUEST['old_username']
    && $hostname == $_REQUEST['old_hostname']
) {
    $response->addHTML(
        PMA\libraries\Message::error(
            __(
                "Username and hostname didn't change. "
                . "If you only want to change the password, "
                . "'Change password' tab should be used."
            )
        )->getDisplay()
    );
    $response->setRequestStatus(false);
    exit;
}

/**
 * Changes / copies a user, part I
 */
list($queries, $password) = PMA_getDataForChangeOrCopyUser();

/**
 * Adds a user
 *   (Changes / copies a user, part II)
 */
list($ret_message, $ret_queries, $queries_for_display, $sql_query, $_add_user_error)
    = PMA_addUser(
        isset($dbname)? $dbname : null,
        isset($username)? $username : null,
        isset($hostname)? $hostname : null,
        isset($password)? $password : null,
        $cfgRelation['menuswork']
    );
//update the old variables
if (isset($ret_queries)) {
    $queries = $ret_queries;
    unset($ret_queries);
}
if (isset($ret_message)) {
    $message = $ret_message;
    unset($ret_message);
}

/**
 * Changes / copies a user, part III
 */
if (isset($_REQUEST['change_copy'])) {
    $queries = PMA_getDbSpecificPrivsQueriesForChangeOrCopyUser(
        $queries, $username, $hostname
    );
}

$itemType = '';
if (! empty($routinename)) {
    $itemType = PMA_getRoutineType($dbname, $routinename);
}

/**
 * Updates privileges
 */
if (! empty($_POST['update_privs'])) {
    if (is_array($dbname)) {
        foreach ($dbname as $key => $db_name) {
            list($sql_query[$key], $message) = PMA_updatePrivileges(
                (isset($username) ? $username : ''),
                (isset($hostname) ? $hostname : ''),
                (isset($tablename)
                    ? $tablename
                    : (isset($routinename) ? $routinename : '')),
                (isset($db_name) ? $db_name : ''),
                $itemType
            );
        }

        $sql_query = implode("\n", $sql_query);
    } else {
        list($sql_query, $message) = PMA_updatePrivileges(
            (isset($username) ? $username : ''),
            (isset($hostname) ? $hostname : ''),
            (isset($tablename)
                ? $tablename
                : (isset($routinename) ? $routinename : '')),
            (isset($dbname) ? $dbname : ''),
            $itemType
        );
    }
}

/**
 * Assign users to user groups
 */
if (! empty($_REQUEST['changeUserGroup']) && $cfgRelation['menuswork']
    && $GLOBALS['is_superuser'] && $GLOBALS['is_createuser']
) {
    PMA_setUserGroup($username, $_REQUEST['userGroup']);
    $message = PMA\libraries\Message::success();
}

/**
 * Revokes Privileges
 */
if (isset($_REQUEST['revokeall'])) {
    list ($message, $sql_query) = PMA_getMessageAndSqlQueryForPrivilegesRevoke(
        (isset($dbname) ? $dbname : ''),
        (isset($tablename)
            ? $tablename
            : (isset($routinename) ? $routinename : '')),
        $username,
        $hostname,
        $itemType
    );
}

/**
 * Updates the password
 */
if (isset($_REQUEST['change_pw'])) {
    $message = PMA_updatePassword(
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
    include_once 'libraries/relation_cleanup.lib.php';
    $queries = PMA_getDataForDeleteUsers($queries);
    if (empty($_REQUEST['change_copy'])) {
        list($sql_query, $message) = PMA_deleteUser($queries);
    }
}

/**
 * Changes / copies a user, part V
 */
if (isset($_REQUEST['change_copy'])) {
    $queries = PMA_getDataForQueries($queries, $queries_for_display);
    $message = PMA\libraries\Message::success();
    $sql_query = join("\n", $queries);
}

/**
 * Reloads the privilege tables into memory
 */
$message_ret = PMA_updateMessageForReload();
if (isset($message_ret)) {
    $message = $message_ret;
    unset($message_ret);
}

/**
 * If we are in an Ajax request for Create User/Edit User/Revoke User/
 * Flush Privileges, show $message and exit.
 */
if ($GLOBALS['is_ajax_request']
    && empty($_REQUEST['ajax_page_request'])
    && ! isset($_REQUEST['export'])
    && (! isset($_REQUEST['submit_mult']) || $_REQUEST['submit_mult'] != 'export')
    && ((! isset($_REQUEST['initial']) || $_REQUEST['initial'] === null
    || $_REQUEST['initial'] === '')
    || (isset($_REQUEST['delete']) && $_REQUEST['delete'] === __('Go')))
    && ! isset($_REQUEST['showall'])
    && ! isset($_REQUEST['edit_user_group_dialog'])
    && ! isset($_REQUEST['db_specific'])
) {
    $extra_data = PMA_getExtraDataForAjaxBehavior(
        (isset($password) ? $password : ''),
        (isset($sql_query) ? $sql_query : ''),
        (isset($hostname) ? $hostname : ''),
        (isset($username) ? $username : '')
    );

    if (! empty($message) && $message instanceof PMA\libraries\Message) {
        $response = PMA\libraries\Response::getInstance();
        $response->setRequestStatus($message->isSuccess());
        $response->addJSON('message', $message);
        $response->addJSON($extra_data);
        exit;
    }
}

/**
 * Displays the links
 */
if (isset($_REQUEST['viewing_mode']) && $_REQUEST['viewing_mode'] == 'db') {
    $GLOBALS['db'] = $_REQUEST['db'] = $_REQUEST['checkprivsdb'];

    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    ob_start();

    list(
        $tables,
        $num_tables,
        $total_num_tables,
        $sub_part,
        $is_show_stats,
        $db_is_system_schema,
        $tooltip_truename,
        $tooltip_aliasname,
        $pos
    ) = PMA\libraries\Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

    $content = ob_get_contents();
    ob_end_clean();
    $response->addHTML($content . "\n");
} else {
    if (! empty($GLOBALS['message'])) {
        $response->addHTML(PMA\libraries\Util::getMessage($GLOBALS['message']));
        unset($GLOBALS['message']);
    }
}

/**
 * Displays the page
 */
$response->addHTML(
    PMA_getHtmlForUserGroupDialog(
        isset($username)? $username : null,
        $cfgRelation['menuswork']
    )
);

// export user definition
if (isset($_REQUEST['export'])
    || (isset($_REQUEST['submit_mult']) && $_REQUEST['submit_mult'] == 'export')
) {
    list($title, $export) = PMA_getListForExportUserDefinition(
        isset($username) ? $username : null,
        isset($hostname) ? $hostname : null
    );

    unset($username, $hostname, $grants, $one_grant);

    $response = PMA\libraries\Response::getInstance();
    if ($GLOBALS['is_ajax_request']) {
        $response->addJSON('message', $export);
        $response->addJSON('title', $title);
        exit;
    } else {
        $response->addHTML("<h2>$title</h2>$export");
    }
}

if (isset($_REQUEST['adduser'])) {
    // Add user
    $response->addHTML(
        PMA_getHtmlForAddUser((isset($dbname) ? $dbname : ''))
    );
} elseif (isset($_REQUEST['checkprivsdb'])) {
    if (isset($_REQUEST['checkprivstable'])) {
        // check the privileges for a particular table.
        $response->addHTML(
            PMA_getHtmlForSpecificTablePrivileges(
                $_REQUEST['checkprivsdb'], $_REQUEST['checkprivstable']
            )
        );
    } else {
        // check the privileges for a particular database.
        $response->addHTML(
            PMA_getHtmlForSpecificDbPrivileges($_REQUEST['checkprivsdb'])
        );
    }
} else {
    if (isset($dbname) && ! is_array($dbname)) {
        $url_dbname = urlencode(
            str_replace(
                array('\_', '\%'),
                array('_', '%'),
                $_REQUEST['dbname']
            )
        );
    }

    if (! isset($username)) {
        // No username is given --> display the overview
        $response->addHTML(
            PMA_getHtmlForUserOverview($pmaThemeImage, $text_dir)
        );
    } else if (!empty($routinename)) {
        $response->addHTML(
            PMA_getHtmlForRoutineSpecificPrivilges(
                $username, $hostname, $dbname, $routinename,
                (isset($url_dbname) ? $url_dbname : '')
            )
        );
    } else {
        // A user was selected -> display the user's properties
        // In an Ajax request, prevent cached values from showing
        if ($GLOBALS['is_ajax_request'] == true) {
            header('Cache-Control: no-cache');
        }

        $response->addHTML(
            PMA_getHtmlForUserProperties(
                (isset($dbname_is_wildcard) ? $dbname_is_wildcard : ''),
                (isset($url_dbname) ? $url_dbname : ''),
                $username, $hostname,
                (isset($dbname) ? $dbname : ''),
                (isset($tablename) ? $tablename : '')
            )
        );
    }
}

if ((isset($_REQUEST['viewing_mode']) && $_REQUEST['viewing_mode'] == 'server')
    && $GLOBALS['cfgRelation']['menuswork']
) {
    $response->addHTML('</div>');
}
