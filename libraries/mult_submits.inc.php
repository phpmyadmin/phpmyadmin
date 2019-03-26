<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Helper for multi submit forms
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\CentralColumns;
use PhpMyAdmin\Message;
use PhpMyAdmin\MultSubmits;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

$request_params = [
    'clause_is_unique',
    'from_prefix',
    'goto',
    'mult_btn',
    'original_sql_query',
    'query_type',
    'reload',
    'selected',
    'selected_fld',
    'selected_recent_table',
    'sql_query',
    'submit_mult',
    'table_type',
    'to_prefix',
    'url_query',
];

foreach ($request_params as $one_request_param) {
    if (isset($_POST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_POST[$one_request_param];
    }
}
$response = Response::getInstance();

global $db, $table,  $clause_is_unique, $from_prefix, $goto,
       $mult_btn, $original_sql_query, $query_type, $reload,
       $selected, $selected_fld, $selected_recent_table, $sql_query,
       $submit_mult, $table_type, $to_prefix, $url_query, $pmaThemeImage;

$multSubmits = new MultSubmits();
$template = new Template();

/**
 * Prepares the work and runs some other scripts if required
 */
if (! empty($submit_mult)
    && $submit_mult != __('With selected:')
    && (! empty($_POST['selected_dbs'])
    || ! empty($_POST['selected_tbl'])
    || ! empty($selected_fld)
    || ! empty($_POST['rows_to_delete']))
) {
    define('PMA_SUBMIT_MULT', 1);
    if (! empty($_POST['selected_dbs'])) {
        // coming from server database view - do something with
        // selected databases
        $selected   = $_POST['selected_dbs'];
        $query_type = 'drop_db';
    } elseif (! empty($_POST['selected_tbl'])) {
        // coming from database structure view - do something with
        // selected tables
        $selected = $_POST['selected_tbl'];
        $centralColumns = new CentralColumns($GLOBALS['dbi']);
        switch ($submit_mult) {
            case 'add_prefix_tbl':
            case 'replace_prefix_tbl':
            case 'copy_tbl_change_prefix':
            case 'drop_db':
            case 'drop_tbl':
            case 'empty_tbl':
                $what = $submit_mult;
                break;
            case 'check_tbl':
            case 'optimize_tbl':
            case 'repair_tbl':
            case 'analyze_tbl':
            case 'checksum_tbl':
                $query_type = $submit_mult;
                unset($submit_mult);
                $mult_btn   = __('Yes');
                break;
            case 'export':
                unset($submit_mult);
                include ROOT_PATH . 'db_export.php';
                exit;
            case 'copy_tbl':
                $views = $GLOBALS['dbi']->getVirtualTables($db);
                list($full_query, $reload, $full_query_views)
                = $multSubmits->getQueryFromSelected(
                    $submit_mult,
                    $table,
                    $selected,
                    $views
                );
                $_url_params = $multSubmits->getUrlParams(
                    $submit_mult,
                    $reload,
                    $action,
                    $db,
                    $table,
                    $selected,
                    $views,
                    isset($original_sql_query) ? $original_sql_query : null,
                    isset($original_url_query) ? $original_url_query : null
                );
                $response->disable();
                $response->addHTML(
                    $multSubmits->getHtmlForCopyMultipleTables($action, $_url_params)
                );
                exit;
            case 'show_create':
                $show_create = $template->render('database/structure/show_create', [
                    'db' => $GLOBALS['db'],
                    'db_objects' => $selected,
                    'dbi' => $GLOBALS['dbi'],
                ]);
                // Send response to client.
                $response->addJSON('message', $show_create);
                exit;
            case 'sync_unique_columns_central_list':
                $centralColsError = $centralColumns->syncUniqueColumns(
                    $selected
                );
                break;
            case 'delete_unique_columns_central_list':
                $centralColsError = $centralColumns->deleteColumnsFromList(
                    $_POST['db'],
                    $selected
                );
                break;
            case 'make_consistent_with_central_list':
                $centralColsError = $centralColumns->makeConsistentWithList(
                    $GLOBALS['db'],
                    $selected
                );
                break;
        } // end switch
    } elseif (! (isset($selected_fld) && ! empty($selected_fld))) {
        // coming from browsing - do something with selected rows
        $what = 'row_delete';
        $selected = $_REQUEST['rows_to_delete'];
    }
}

if (empty($db)) {
    $db = '';
}
if (empty($table)) {
    $table = '';
}
$views = $GLOBALS['dbi']->getVirtualTables($db);

/**
 * Displays the confirmation form if required
 */
if (! empty($submit_mult) && ! empty($what)) {
    unset($message);

    if (strlen($table) > 0) {
        include ROOT_PATH . 'libraries/tbl_common.inc.php';
        $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
    } elseif (strlen($db) > 0) {
        include ROOT_PATH . 'libraries/db_common.inc.php';

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
        ) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
    } else {
        include_once ROOT_PATH . 'libraries/server_common.inc.php';
    }

    // Builds the query
    list($full_query, $reload, $full_query_views)
        = $multSubmits->getQueryFromSelected(
            $what,
            $table,
            $selected,
            $views
        );

    // Displays the confirmation form
    $_url_params = $multSubmits->getUrlParams(
        $what,
        $reload,
        $action,
        $db,
        $table,
        $selected,
        $views,
        isset($original_sql_query) ? $original_sql_query : null,
        isset($original_url_query) ? $original_url_query : null
    );


    if ($what == 'replace_prefix_tbl' || $what == 'copy_tbl_change_prefix') {
        $response->disable();
        $response->addHTML(
            $multSubmits->getHtmlForReplacePrefixTable($action, $_url_params)
        );
    } elseif ($what == 'add_prefix_tbl') {
        $response->disable();
        $response->addHTML($multSubmits->getHtmlForAddPrefixTable($action, $_url_params));
    } else {
        $response->addHTML(
            $multSubmits->getHtmlForOtherActions($what, $action, $_url_params, $full_query)
        );
    }
    exit;
} elseif (! empty($mult_btn) && $mult_btn == __('Yes')) {
    /**
     * Executes the query - dropping rows, columns/fields, tables or dbs
     */
    if ($query_type == 'primary_fld') {
        // Gets table primary key
        $GLOBALS['dbi']->selectDb($db);
        $result = $GLOBALS['dbi']->query(
            'SHOW KEYS FROM ' . Util::backquote($table) . ';'
        );
        $primary = '';
        while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
            }
        } // end while
        $GLOBALS['dbi']->freeResult($result);
    }

    if ($query_type == 'drop_tbl'
        || $query_type == 'empty_tbl'
        || $query_type == 'row_delete'
    ) {
        $default_fk_check_value = Util::handleDisableFKCheckInit();
    }

    list(
        $result, $rebuild_database_list, $reload_ret,
        $run_parts, $execute_query_later, $sql_query, $sql_query_views
    ) = $multSubmits->buildOrExecuteQuery(
        $query_type,
        $selected,
        $db,
        $table,
        $views,
        isset($primary) ? $primary : null,
        isset($from_prefix) ? $from_prefix : null,
        isset($to_prefix) ? $to_prefix : null
    );
    //update the existed variable
    if (isset($reload_ret)) {
        $reload = $reload_ret;
    }

    if ($query_type == 'drop_tbl') {
        if (! empty($sql_query)) {
            $sql_query .= ';';
        } elseif (! empty($sql_query_views)) {
            $sql_query = $sql_query_views . ';';
            unset($sql_query_views);
        }
    }

    // Unset cache values for tables count, issue #14205
    if ($query_type === 'drop_tbl' && isset($_SESSION['tmpval'])) {
        if (isset($_SESSION['tmpval']['table_limit_offset'])) {
            unset($_SESSION['tmpval']['table_limit_offset']);
        }

        if (isset($_SESSION['tmpval']['table_limit_offset_db'])) {
            unset($_SESSION['tmpval']['table_limit_offset_db']);
        }
    }

    if ($execute_query_later) {
        $sql = new Sql();
        $sql->executeQueryAndSendQueryResponse(
            null, // analyzed_sql_results
            false, // is_gotofile
            $db, // db
            $table, // table
            null, // find_real_end
            null, // sql_query_for_bookmark
            null, // extra_data
            null, // message_to_show
            null, // message
            null, // sql_data
            $goto, // goto
            $pmaThemeImage, // pmaThemeImage
            null, // disp_query
            null, // disp_message
            $query_type, // query_type
            $sql_query, // sql_query
            $selected, // selectedTables
            null // complete_query
        );
    } elseif (! $run_parts) {
        $GLOBALS['dbi']->selectDb($db);
        $result = $GLOBALS['dbi']->tryQuery($sql_query);
        if ($result && ! empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = $GLOBALS['dbi']->tryQuery($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = Message::error($GLOBALS['dbi']->getError());
        }
    }
    if ($query_type == 'drop_tbl'
        || $query_type == 'empty_tbl'
        || $query_type == 'row_delete'
    ) {
        Util::handleDisableFKCheckCleanup($default_fk_check_value);
    }
    if ($rebuild_database_list) {
        // avoid a problem with the database list navigator
        // when dropping a db from server_databases
        $GLOBALS['dblist']->databases->build();
    }
} elseif (isset($submit_mult)
    && ($submit_mult == 'sync_unique_columns_central_list'
    || $submit_mult == 'delete_unique_columns_central_list'
    || $submit_mult == 'add_to_central_columns'
    || $submit_mult == 'remove_from_central_columns'
    || $submit_mult == 'make_consistent_with_central_list')
) {
    if (isset($centralColsError) && $centralColsError !== true) {
        $message = $centralColsError;
    } else {
        $message = Message::success(__('Success!'));
    }
} else {
    $message = Message::success(__('No change'));
}
