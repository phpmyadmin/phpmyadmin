<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';

require_once './libraries/mysql_charsets.lib.php';

PMA_checkParameters(array('new_db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'main.php?' . PMA_generate_common_url();

/**
 * Builds and executes the db creation sql query
 */
$sql_query = 'CREATE DATABASE ' . PMA_backquote($new_db);
if (!empty($db_collation)) {
    list($db_charset) = explode('_', $db_collation);
    if (in_array($db_charset, $mysql_charsets) && in_array($db_collation, $mysql_collations[$db_charset])) {
        $sql_query .= ' DEFAULT' . PMA_generateCharsetQueryPart($db_collation);
    }
    unset($db_charset, $db_collation);
}
$sql_query .= ';';

$result = PMA_DBI_try_query($sql_query);

if (! $result) {
    $message = PMA_Message::rawError(PMA_DBI_getError());
    // avoid displaying the not-created db name in header or navi panel
    $GLOBALS['db'] = '';
    $GLOBALS['table'] = '';

    if($GLOBALS['is_ajax_request'] == true) {
        PMA_ajaxResponse($message, FALSE);
    }

    require_once './libraries/header.inc.php';
    require_once './main.php';
} else {
    $message = PMA_Message::success(__('Database %1$s has been created.'));
    $message->addParam($new_db);
    $GLOBALS['db'] = $new_db;

    if($GLOBALS['is_ajax_request'] == true) {

        $extra_data['sql_query'] = PMA_showMessage(NULL, $sql_query, 'success');

        //Construct the html for the new database, so that it can be appended to the list of databases on server_databases.php

        $db_url_params['db'] = $new_db;

        $is_superuser = PMA_isSuperuser();

        $new_db_string = '<tr>';

        if ($is_superuser || $cfg['AllowUserDropDatabase']) {
            $new_db_string .= '<td class="tool">';
            $new_db_string .= '<input type="checkbox" title="'. $new_db .'" value="' . $new_db . '" name="selected_dbsp[]" />';
            $new_db_string .='</td>';
        }

        $new_db_string .= '<td class="name">';
        $new_db_string .= '<a target="_parent" title="Jump to database" href="index.php' . PMA_generate_common_url($db_url_params) . '">';
        $new_db_string .= $new_db . '</a>';
        $new_db_string .= '</td>';

        if($is_superuser) {
            
            $db_url_params['checkprivs'] = $new_db;

            $new_db_string .= '<td class="tool">';
            $new_db_string .= '<a title="Check privileges for database" href="server_privileges.php' . PMA_generate_common_url($db_url_params) . '">';
            $new_db_string .= ($cfg['PropertiesIconic']
                                 ? '<img class="icon" src="' . $pmaThemeImage . 's_rights.png" width="16" height="16" alt=" ' . __('Check Privileges') . '" /> '
                                 : __('Check Privileges'))  . '</a>';
            $new_db_string .= '</td>';
        }

        $new_db_string .= '</tr>';

        $extra_data['new_db_string'] = $new_db_string;

        PMA_ajaxResponse($message, true, $extra_data);
    }

    require_once './libraries/header.inc.php';
    require_once './' . $cfg['DefaultTabDatabase'];
}
?>
