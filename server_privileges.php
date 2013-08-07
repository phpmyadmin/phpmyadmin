<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server privileges and users manipulations
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/display_change_password.lib.php';

/**
 * Does the common work
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_privileges.js');

$cfgRelation = PMA_getRelationsParam();
if ($GLOBALS['cfgRelation']['menuswork']) {
    $response->addHTML('<div>');
    $response->addHTML(PMA_getHtmlForSubMenusOnUsersPage('server_privileges.php'));
}

/**
 * Make the required data ready for server privileges
 */
require_once 'libraries/server_privileges.inc.php';

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
$response->addHTML(
    PMA_getHtmlForUserGroupDialog($username, $cfgRelation['menuswork'])
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

if ($GLOBALS['cfgRelation']['menuswork']) {
    $response->addHTML('</div>');
}

?>
