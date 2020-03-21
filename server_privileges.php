<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server privileges and users manipulations
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Server\Users;
use PhpMyAdmin\Template;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db, $pmaThemeImage, $text_dir, $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$checkUserPrivileges = new CheckUserPrivileges($dbi);
$checkUserPrivileges->getPrivileges();

/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$cfgRelation = $relation->getRelationsParam();

/**
 * Does the common work
 */
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server/privileges.js');
$scripts->addFile('vendor/zxcvbn.js');

/** @var Template $template */
$template = $containerBuilder->get('template');
$relationCleanup = new RelationCleanup($dbi, $relation);
$serverPrivileges = new Privileges($template, $dbi, $relation, $relationCleanup);

if ((isset($_GET['viewing_mode'])
    && $_GET['viewing_mode'] == 'server')
    && $cfgRelation['menuswork']
) {
    $response->addHTML('<div>');
    $response->addHTML(Users::getHtmlForSubMenusOnUsersPage('server_privileges.php'));
}

/**
 * Sets globals from $_POST patterns, for privileges and max_* vars
 */

$post_patterns = [
    '/_priv$/i',
    '/^max_/i',
];

Core::setPostAsGlobal($post_patterns);

require ROOT_PATH . 'libraries/server_common.inc.php';

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
$strPrivDescDeleteHistoricalRows = __('Allows deleting historical rows.');
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
) = $serverPrivileges->getDataForDBInfo();

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (! $dbi->isSuperuser() && ! $GLOBALS['is_grantuser']
    && ! $GLOBALS['is_createuser']
) {
    $response->addHTML(
        $template->render('server/sub_page_header', [
            'type' => 'privileges',
            'is_image' => false,
        ])
    );
    $response->addHTML(
        Message::error(__('No Privileges'))
            ->getDisplay()
    );
    exit;
}
if (! $GLOBALS['is_grantuser'] && ! $GLOBALS['is_createuser']) {
    $response->addHTML(Message::notice(
        __('You do not have the privileges to administrate the users!')
    )->getDisplay());
}

/**
 * Checks if the user is using "Change Login Information / Copy User" dialog
 * only to update the password
 */
if (isset($_POST['change_copy']) && $username == $_POST['old_username']
    && $hostname == $_POST['old_hostname']
) {
    $response->addHTML(
        Message::error(
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
list($queries, $password) = $serverPrivileges->getDataForChangeOrCopyUser();

/**
 * Adds a user
 *   (Changes / copies a user, part II)
 */
list($ret_message, $ret_queries, $queries_for_display, $sql_query, $_add_user_error)
    = $serverPrivileges->addUser(
        isset($dbname) ? $dbname : null,
        isset($username) ? $username : null,
        isset($hostname) ? $hostname : null,
        isset($password) ? $password : null,
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
if (isset($_POST['change_copy'])) {
    $queries = $serverPrivileges->getDbSpecificPrivsQueriesForChangeOrCopyUser(
        $queries,
        $username,
        $hostname
    );
}

$itemType = '';
if (! empty($routinename)) {
    $itemType = $serverPrivileges->getRoutineType($dbname, $routinename);
}

/**
 * Updates privileges
 */
if (! empty($_POST['update_privs'])) {
    if (is_array($dbname)) {
        foreach ($dbname as $key => $db_name) {
            list($sql_query[$key], $message) = $serverPrivileges->updatePrivileges(
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
        list($sql_query, $message) = $serverPrivileges->updatePrivileges(
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
if (! empty($_POST['changeUserGroup']) && $cfgRelation['menuswork']
    && $dbi->isSuperuser() && $GLOBALS['is_createuser']
) {
    $serverPrivileges->setUserGroup($username, $_POST['userGroup']);
    $message = Message::success();
}

/**
 * Revokes Privileges
 */
if (isset($_POST['revokeall'])) {
    list ($message, $sql_query) = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
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
if (isset($_POST['change_pw'])) {
    $message = $serverPrivileges->updatePassword(
        $err_url,
        $username,
        $hostname
    );
}

/**
 * Deletes users
 *   (Changes / copies a user, part IV)
 */
if (isset($_POST['delete'])
    || (isset($_POST['change_copy']) && $_POST['mode'] < 4)
) {
    $queries = $serverPrivileges->getDataForDeleteUsers($queries);
    if (empty($_POST['change_copy'])) {
        list($sql_query, $message) = $serverPrivileges->deleteUser($queries);
    }
}

/**
 * Changes / copies a user, part V
 */
if (isset($_POST['change_copy'])) {
    $queries = $serverPrivileges->getDataForQueries($queries, $queries_for_display);
    $message = Message::success();
    $sql_query = implode("\n", $queries);
}

/**
 * Reloads the privilege tables into memory
 */
$message_ret = $serverPrivileges->updateMessageForReload();
if ($message_ret !== null) {
    $message = $message_ret;
    unset($message_ret);
}

/**
 * If we are in an Ajax request for Create User/Edit User/Revoke User/
 * Flush Privileges, show $message and exit.
 */
if ($response->isAjax()
    && empty($_REQUEST['ajax_page_request'])
    && ! isset($_GET['export'])
    && (! isset($_POST['submit_mult']) || $_POST['submit_mult'] != 'export')
    && ((! isset($_GET['initial']) || $_GET['initial'] === null
    || $_GET['initial'] === '')
    || (isset($_POST['delete']) && $_POST['delete'] === __('Go')))
    && ! isset($_GET['showall'])
    && ! isset($_GET['edit_user_group_dialog'])
) {
    $extra_data = $serverPrivileges->getExtraDataForAjaxBehavior(
        (isset($password) ? $password : ''),
        (isset($sql_query) ? $sql_query : ''),
        (isset($hostname) ? $hostname : ''),
        (isset($username) ? $username : '')
    );

    if (! empty($message) && $message instanceof Message) {
        $response->setRequestStatus($message->isSuccess());
        $response->addJSON('message', $message);
        $response->addJSON($extra_data);
        exit;
    }
}

/**
 * Displays the links
 */
if (isset($_GET['viewing_mode']) && $_GET['viewing_mode'] == 'db') {
    $db = $_REQUEST['db'] = $_GET['checkprivsdb'];

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
    ) = PhpMyAdmin\Util::getDbInfo($db, $sub_part === null ? '' : $sub_part);

    $content = ob_get_contents();
    ob_end_clean();
    $response->addHTML($content . "\n");
} elseif (! empty($GLOBALS['message'])) {
    $response->addHTML(PhpMyAdmin\Util::getMessage($GLOBALS['message']));
    unset($GLOBALS['message']);
}

/**
 * Displays the page
 */
$response->addHTML(
    $serverPrivileges->getHtmlForUserGroupDialog(
        isset($username) ? $username : null,
        $cfgRelation['menuswork']
    )
);

// export user definition
if (isset($_GET['export'])
    || (isset($_POST['submit_mult']) && $_POST['submit_mult'] == 'export')
) {
    list($title, $export) = $serverPrivileges->getListForExportUserDefinition(
        isset($username) ? $username : '',
        isset($hostname) ? $hostname : ''
    );

    unset($username, $hostname, $grants, $one_grant);

    if ($response->isAjax()) {
        $response->addJSON('message', $export);
        $response->addJSON('title', $title);
        exit;
    } else {
        $response->addHTML("<h2>$title</h2>$export");
    }
}

if (isset($_GET['adduser'])) {
    // Add user
    $response->addHTML(
        $serverPrivileges->getHtmlForAddUser((isset($dbname) ? $dbname : ''))
    );
} elseif (isset($_GET['checkprivsdb'])) {
    if (isset($_GET['checkprivstable'])) {
        // check the privileges for a particular table.
        $response->addHTML(
            $serverPrivileges->getHtmlForSpecificTablePrivileges(
                $_GET['checkprivsdb'],
                $_GET['checkprivstable']
            )
        );
    } else {
        // check the privileges for a particular database.
        $response->addHTML(
            $serverPrivileges->getHtmlForSpecificDbPrivileges($_GET['checkprivsdb'])
        );
    }
} else {
    if (isset($dbname) && ! is_array($dbname)) {
        $url_dbname = urlencode(
            str_replace(
                [
                    '\_',
                    '\%',
                ],
                [
                    '_',
                    '%',
                ],
                $dbname
            )
        );
    }

    if (! isset($username)) {
        // No username is given --> display the overview
        $response->addHTML(
            $serverPrivileges->getHtmlForUserOverview($pmaThemeImage, $text_dir)
        );
    } elseif (! empty($routinename)) {
        $response->addHTML(
            $serverPrivileges->getHtmlForRoutineSpecificPrivileges(
                $username,
                $hostname ?? '',
                $dbname,
                $routinename,
                (isset($url_dbname) ? $url_dbname : '')
            )
        );
    } else {
        // A user was selected -> display the user's properties
        // In an Ajax request, prevent cached values from showing
        if ($response->isAjax()) {
            header('Cache-Control: no-cache');
        }

        $response->addHTML(
            $serverPrivileges->getHtmlForUserProperties(
                (isset($dbname_is_wildcard) ? $dbname_is_wildcard : ''),
                (isset($url_dbname) ? $url_dbname : ''),
                $username,
                $hostname ?? '',
                (isset($dbname) ? $dbname : ''),
                (isset($tablename) ? $tablename : '')
            )
        );
    }
}

if ((isset($_GET['viewing_mode']) && $_GET['viewing_mode'] == 'server')
    && $GLOBALS['cfgRelation']['menuswork']
) {
    $response->addHTML('</div>');
}
