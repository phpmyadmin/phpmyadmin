<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server privileges and users manipulations
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

$cfgRelation = PMA_getRelationsParam();

/**
 * Does the common work
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');

if ($GLOBALS['cfgRelation']['menuswork']) {
    $response->addHTML('<div>');
    $response->addHTML(PMA_getHtmlForSubMenusOnUsersPage('server_privileges.php'));
}

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
 * Get DB information: dbname, tablename, db_and_table, dbname_is_wildcard
 */
$dbname = isset($dbname)? $dbname : null; 
$tablename = isset($tablename)? $tablename : null; 
$db_and_table = isset($db_and_table)? $db_and_table : null; 
$dbname_is_wildcard = isset($dbname_is_wildcard)? $dbname_is_wildcard : null; 

PMA_updateDataForDBInfo($dbname, $tablename, $db_and_table, $dbname_is_wildcard);

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

list($row, $password, $queries) = PMA_getListForChangeOrCopyUser();
    
/**
 * Adds a user
 *   (Changes / copies a user, part II)
 */
$queries_for_display = array();
PMA_getDataForAddUser(
    dbname, $username, $hostname, 
    $_add_user_error, $password, 
    $message, $queries, $queries_for_display
);

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
 * Assign users to user groups
 */
if (! empty($_REQUEST['changeUserGroup']) && $cfgRelation['menuswork']) {
    PMA_setUserGroup($username, $_REQUEST['userGroup']);
    $message = PMA_Message::success();
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
    PMA_getDataForAddUser($queries);
    if (empty($_REQUEST['change_copy'])) {
        list($sql_query, $message) = PMA_deleteUser($queries);
    }
}

/**
 * Changes / copies a user, part V
 */
if (isset($_REQUEST['change_copy'])) {
    PMA_getDataForQueries($queries, $queries_for_display);
    $message = PMA_Message::success();
    $sql_query = join("\n", $queries);
}

/**
 * Reloads the privilege tables into memory
 */
if (isset($_REQUEST['flush_privileges'])) {
    $sql_query = 'FLUSH PRIVILEGES;';
    $GLOBALS['dbi']->query($sql_query);
    $message = PMA_Message::success(__('The privileges were reloaded successfully.'));
}

if (isset($_REQUEST['validate_username'])) {
    $message = PMA_Message::success();
}

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
    && ! isset($_REQUEST['edit_user_group_dialog'])
    && ! isset($_REQUEST['db_specific'])
) {
    $extra_data = PMA_getExtraDataForAjaxBehavior(
        (isset($password) ? $password : ''),
        (isset($sql_query) ? $sql_query : ''),
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


if (! empty($_REQUEST['edit_user_group_dialog']) && $cfgRelation['menuswork']) {
    $dialog = PMA_getHtmlToChoseUserGroup($username);
    $response = PMA_Response::getInstance();
    if ($GLOBALS['is_ajax_request']) {
        $response->addJSON('message', $dialog);
        exit;
    } else {
        $response->addHTML($dialog);
    }
}

// export user definition
if (isset($_REQUEST['export'])
    || (isset($_REQUEST['submit_mult']) && $_REQUEST['submit_mult'] == 'export')
) {
    list($title, $export) = PMA_getListForExportUserDefinition(
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
            PMA_getHtmlForDisplayUserOverviewPage($pmaThemeImage, $text_dir)
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
                $url_dbname, $username, $hostname,
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
        PMA_getHtmlForSpecificDbPrivileges()
    );
} // end if (empty($_REQUEST['adduser']) && empty($checkprivs))... elseif... else...

if ($GLOBALS['cfgRelation']['menuswork']) {
    $response->addHTML('</div>');
}

?>
