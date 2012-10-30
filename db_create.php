<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

require_once 'libraries/mysql_charsets.lib.php';
if (! PMA_DRIZZLE) {
    include_once 'libraries/replication.inc.php';
}
require 'libraries/build_html_for_db.lib.php';

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'db_collation',
    'new_db'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

PMA_Util::checkParameters(array('new_db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'index.php?' . PMA_generate_common_url();

/**
 * Builds and executes the db creation sql query
 */
$sql_query = 'CREATE DATABASE ' . PMA_Util::backquote($new_db);
if (! empty($db_collation)) {
    list($db_charset) = explode('_', $db_collation);
    if (in_array($db_charset, $mysql_charsets)
        && in_array($db_collation, $mysql_collations[$db_charset])
    ) {
        $sql_query .= ' DEFAULT' . PMA_generateCharsetQueryPart($db_collation);
    }
    $db_collation_for_ajax = $db_collation;
    unset($db_charset, $db_collation);
}
$sql_query .= ';';

$result = PMA_DBI_try_query($sql_query);

if (! $result) {
    $message = PMA_Message::rawError(PMA_DBI_getError());
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
    $message->addParam($new_db);
    $GLOBALS['db'] = $new_db;

    /**
     * If in an Ajax request, build the output and send it
     */
    if ($GLOBALS['is_ajax_request'] == true) {
        //Construct the html for the new database, so that it can be appended to
        // the list of databases on server_databases.php

        /**
         * Build the array to be passed to {@link PMA_generate_common_url}
         * to generate the links
         *
         * @global array $GLOBALS['db_url_params']
         * @name $db_url_params
         */
        $db_url_params['db'] = $new_db;

        $is_superuser = PMA_isSuperuser();
        $column_order = PMA_getColumnOrder();
        $url_query = PMA_generate_common_url($new_db);

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
                'SCHEMA_NAME' => $new_db,
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
                'SCHEMA_NAME' => $new_db
            );
        }

        list($column_order, $generated_html) = PMA_buildHtmlForDb(
            $current, $is_superuser, $url_query,
            $column_order, $replication_types, $replication_info
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
    } else {
        include_once '' . $cfg['DefaultTabDatabase'];
    }
}
?>
