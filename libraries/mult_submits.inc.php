<?php
/**
 * Helper for multi submit forms
 */
declare(strict_types=1);

use PhpMyAdmin\CentralColumns;
use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\Database\ExportController;
use PhpMyAdmin\Message;
use PhpMyAdmin\MultSubmits;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder, $db, $table,  $clause_is_unique, $from_prefix, $goto, $message;
global $mult_btn, $original_sql_query, $query_type, $reload, $dbi, $dblist;
global $selected, $selected_fld, $selected_recent_table, $sql_query;
global $submit_mult, $table_type, $to_prefix, $url_query, $pmaThemeImage;

$clause_is_unique = $_POST['clause_is_unique'] ?? $clause_is_unique ?? null;
$from_prefix = $_POST['from_prefix'] ?? $from_prefix ?? null;
$goto = $_POST['goto'] ?? $goto ?? null;
$mult_btn = $_POST['mult_btn'] ?? $mult_btn ?? null;
$original_sql_query = $_POST['original_sql_query'] ?? $original_sql_query ?? null;
$query_type = $_POST['query_type'] ?? $query_type ?? null;
$reload = $_POST['reload'] ?? $reload ?? null;
$selected = $_POST['selected'] ?? $selected ?? null;
$selected_fld = $_POST['selected_fld'] ?? $selected_fld ?? null;
$selected_recent_table = $_POST['selected_recent_table'] ?? $selected_recent_table ?? null;
$sql_query = $_POST['sql_query'] ?? $sql_query ?? null;
$submit_mult = $_POST['submit_mult'] ?? $submit_mult ?? null;
$table_type = $_POST['table_type'] ?? $table_type ?? null;
$to_prefix = $_POST['to_prefix'] ?? $to_prefix ?? null;
$url_query = $_POST['url_query'] ?? $url_query ?? null;

/** @var Response $response */
$response = $containerBuilder->get('response');

/** @var Template $template */
$template = $containerBuilder->get('template');

/** @var MultSubmits $multSubmits */
$multSubmits = $containerBuilder->get('mult_submits');

$action = $action ?? '';

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
    // phpcs:disable PSR1.Files.SideEffects
    define('PMA_SUBMIT_MULT', 1);
    // phpcs:enable

    if (! empty($_POST['selected_dbs'])) {
        // coming from server database view - do something with
        // selected databases
        $selected   = $_POST['selected_dbs'];
        $query_type = 'drop_db';
    } elseif (! empty($_POST['selected_tbl'])) {
        // coming from database structure view - do something with
        // selected tables
        $selected = $_POST['selected_tbl'];
        $centralColumns = new CentralColumns($dbi);
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
                /** @var ExportController $controller */
                $controller = $containerBuilder->get(ExportController::class);
                $controller->index();
                exit;
            case 'copy_tbl':
                $views = $dbi->getVirtualTables($db);
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
                    $original_sql_query ?? null,
                    $original_url_query ?? null
                );
                $response->disable();
                $response->addHTML(
                    $multSubmits->getHtmlForCopyMultipleTables($action, $_url_params)
                );
                exit;
            case 'show_create':
                $show_create = $template->render('database/structure/show_create', [
                    'db' => $db,
                    'db_objects' => $selected,
                    'dbi' => $dbi,
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
                    $db,
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
$views = $dbi->getVirtualTables($db);

/**
 * Displays the confirmation form if required
 */
if (! empty($submit_mult) && ! empty($what)) {
    unset($message);

    if (strlen($table) > 0) {
        Common::table();
        $url_query .= Url::getCommon([
            'goto' => Url::getFromRoute('/table/sql'),
            'back' => Url::getFromRoute('/table/sql'),
        ], '&');
    } elseif (strlen($db) > 0) {
        Common::database();

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
        ) = Util::getDbInfo($db, $sub_part ?? '');
    } else {
        Common::server();
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
        $original_sql_query ?? null,
        $original_url_query ?? null
    );


    if ($what == 'replace_prefix_tbl' || $what == 'copy_tbl_change_prefix') {
        $response->disable();
        $response->addHTML($template->render('mult_submits/replace_prefix_table', [
            'action' => $action,
            'url_params' => $_url_params,
        ]));
    } elseif ($what == 'add_prefix_tbl') {
        $response->disable();
        $response->addHTML($multSubmits->getHtmlForAddPrefixTable($action, $_url_params));
    } else {
        $response->addHTML($template->render('mult_submits/other_actions', [
            'action' => $action,
            'url_params' => $_url_params,
            'what' => $what,
            'full_query' => $full_query,
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
        ]));
    }
    exit;
} elseif (! empty($mult_btn) && $mult_btn == __('Yes')) {
    /**
     * Executes the query - dropping rows, columns/fields, tables or dbs
     */
    if ($query_type == 'primary_fld') {
        // Gets table primary key
        $dbi->selectDb($db);
        $result = $dbi->query(
            'SHOW KEYS FROM ' . Util::backquote($table) . ';'
        );
        $primary = '';
        while ($row = $dbi->fetchAssoc($result)) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
            }
        } // end while
        $dbi->freeResult($result);
    }

    $default_fk_check_value = false;
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
        $primary ?? null,
        $from_prefix ?? null,
        $to_prefix ?? null
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
        $dbi->selectDb($db);
        $result = $dbi->tryQuery($sql_query);
        if ($result && ! empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = $dbi->tryQuery($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = Message::error($dbi->getError());
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
        $dblist->databases->build();
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
