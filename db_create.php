<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database creating page
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

require_once 'libraries/mysql_charsets.inc.php';
if (! PMA_DRIZZLE) {
    include_once 'libraries/replication.inc.php';
}
require 'libraries/build_html_for_db.lib.php';

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'index.php' . PMA_URL_getCommon();

/**
 * Builds and executes the db creation sql query
 */
$sql_query = 'CREATE DATABASE ' . PMA_Util::backquote($_POST['new_db']);
if (! empty($_POST['db_collation'])) {
    list($db_charset) = explode('_', $_POST['db_collation']);
    if (in_array($db_charset, $mysql_charsets)
        && in_array($_POST['db_collation'], $mysql_collations[$db_charset])
    ) {
        $sql_query .= ' DEFAULT'
            . PMA_generateCharsetQueryPart($_POST['db_collation']);
    }
    $db_collation_for_ajax = $_POST['db_collation'];
    unset($db_charset);
}
$sql_query .= ';';

$result = $GLOBALS['dbi']->tryQuery($sql_query);

if (! $result) {
    $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
    // avoid displaying the not-created db name in header or navi panel
    $GLOBALS['db'] = '';
    $GLOBALS['table'] = '';

    /**
     * If in an Ajax request, just display the message with {@link PMA_Response}
     */
    if ($GLOBALS['is_ajax_request'] == true) {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $message);
    } else {
        include_once 'index.php';
    }
} else {
    $message = PMA_Message::success(__('Database %1$s has been created.'));
    $message->addParam($_POST['new_db']);
    $GLOBALS['db'] = $_POST['new_db'];

    /**
     * If in an Ajax request, build the output and send it
     */
    if ($GLOBALS['is_ajax_request'] == true) {
        //Construct the html for the new database, so that it can be appended to
        // the list of databases on server_databases.php

        /**
         * Build the array to be passed to {@link PMA_URL_getCommon}
         * to generate the links
         *
         * @global array $GLOBALS['db_url_params']
         * @name $db_url_params
         */
        $db_url_params['db'] = $_POST['new_db'];

        $is_superuser = $GLOBALS['dbi']->isSuperuser();
        $column_order = PMA_getColumnOrder();
        $url_query = PMA_URL_getCommon(array('db' => $_POST['new_db']));

        /**
         * String that will contain the output HTML
         * @name    $new_db_string
         */
        $new_db_string = '<tr>';

        if (empty($db_collation_for_ajax)) {
            $db_collation_for_ajax = PMA_getServerCollation();
        }

        // $dbstats comes from the create table dialog
        if (! empty($dbstats)) {
            $current = array(
                'SCHEMA_NAME' => $_POST['new_db'],
                'DEFAULT_COLLATION_NAME' => $db_collation_for_ajax,
                'SCHEMA_TABLES' => '0',
                'SCHEMA_TABLE_ROWS' => '0',
                'SCHEMA_DATA_LENGTH' => '0',
                'SCHEMA_MAX_DATA_LENGTH' => '0',
                'SCHEMA_INDEX_LENGTH' => '0',
                'SCHEMA_LENGTH' => '0',
                'SCHEMA_DATA_FREE' => '0'
            );
        } else {
            $current = array(
                'SCHEMA_NAME' => $_POST['new_db'],
                'DEFAULT_COLLATION_NAME' => $db_collation_for_ajax
            );
        }

        list($column_order, $generated_html) = PMA_buildHtmlForDb(
            $current, $is_superuser, $url_query,
            $column_order, $replication_types, $GLOBALS['replication_info']
        );
        $new_db_string .= $generated_html;

        $new_db_string .= '</tr>';

        $response = PMA_Response::getInstance();
        $response->addJSON('message', $message);
        $response->addJSON('new_db_string', $new_db_string);
        $response->addJSON(
            'sql_query',
            PMA_Util::getMessage(
                null, $sql_query, 'success'
            )
        );
        $response->addJSON(
            'url_query',
            PMA_Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
            . $url_query . '&amp;db='
            . urlencode($current['SCHEMA_NAME'])
        );
    } else {
        include_once '' . $cfg['DefaultTabDatabase'];
    }
}
