<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Helper for multi submit forms
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/transformations.lib.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/sql.lib.php';
require_once 'libraries/mult_submits.lib.php';

$request_params = array(
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
    'url_query'
);

foreach ($request_params as $one_request_param) {
    if (isset($_REQUEST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
    }
}

global $db, $table,  $clause_is_unique, $from_prefix, $goto,
       $mult_btn, $original_sql_query, $query_type, $reload,
       $selected, $selected_fld, $selected_recent_table, $sql_query,
       $submit_mult, $table_type, $to_prefix, $url_query, $pmaThemeImage;

/**
 * Prepares the work and runs some other scripts if required
 */
if (! empty($submit_mult)
    && $submit_mult != __('With selected:')
    && (! empty($selected_db)
    || ! empty($_POST['selected_tbl'])
    || ! empty($selected_fld)
    || ! empty($_REQUEST['rows_to_delete']))
) {
    define('PMA_SUBMIT_MULT', 1);
    if (isset($selected_db) && !empty($selected_db)) {
        // coming from server database view - do something with
        // selected databases
        $selected     = $selected_db;
        $what         = 'drop_db';
    } elseif (! empty($_POST['selected_tbl'])) {
        // coming from database structure view - do something with
        // selected tables
        $selected = $_POST['selected_tbl'];
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
            include 'db_export.php';
            exit;
            break;
        case 'show_create':
            $show_create = PMA\Template::get('database/structure/show_create')
                ->render(
                    array(
                        'db'         => $GLOBALS['db'],
                        'db_objects' => $selected,
                    )
                );
            // Send response to client.
            $response = PMA_Response::getInstance();
            $response->addJSON('message', $show_create);
            exit;
        case 'sync_unique_columns_central_list':
            include_once 'libraries/central_columns.lib.php';
            $centralColsError = PMA_syncUniqueColumns($selected);
            break;
        case 'delete_unique_columns_central_list':
            include_once 'libraries/central_columns.lib.php';
            $centralColsError = PMA_deleteColumnsFromList($selected);
            break;
        case 'make_consistent_with_central_list':
            include_once 'libraries/central_columns.lib.php';
            $centralColsError = PMA_makeConsistentWithList(
                $GLOBALS['db'],
                $selected
            );
            break;
        } // end switch
    } elseif (isset($selected_fld) && !empty($selected_fld)) {
        // coming from table structure view - do something with
        // selected columns
        // handled in StructrueController
    } else {
        // coming from browsing - do something with selected rows
        $what = 'row_delete';
        $selected = $_REQUEST['rows_to_delete'];
    }
} // end if

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
if (!empty($submit_mult) && !empty($what)) {
    unset($message);

    /** @var PMA_String $pmaString */
    $pmaString = $GLOBALS['PMA_String'];
    if (/*overload*/mb_strlen($table)) {
        include './libraries/tbl_common.inc.php';
        $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
        include './libraries/tbl_info.inc.php';
    } elseif (/*overload*/mb_strlen($db)) {
        include './libraries/db_common.inc.php';

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
        ) = PMA_Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

    } else {
        include_once './libraries/server_common.inc.php';
    }

    // Builds the query
    list($full_query, $reload, $full_query_views)
        = PMA_getQueryFromSelected(
            $what, $table, $selected, $views
        );

    // Displays the confirmation form
    $_url_params = PMA_getUrlParams(
        $what, $reload, $action, $db, $table, $selected, $views,
        isset($original_sql_query)? $original_sql_query : null,
        isset($original_url_query)? $original_url_query : null
    );

    $response = PMA_Response::getInstance();

    if ($what == 'replace_prefix_tbl' || $what == 'copy_tbl_change_prefix') {
        $response->addHTML(
            PMA_getHtmlForReplacePrefixTable($what, $action, $_url_params)
        );
    } elseif ($what == 'add_prefix_tbl') {
        $response->addHTML(PMA_getHtmlForAddPrefixTable($action, $_url_params));
    } else {
        $response->addHTML(
            PMA_getHtmlForOtherActions($what, $action, $_url_params, $full_query)
        );
    }
    exit;

} elseif (! empty($mult_btn) && $mult_btn == __('Yes')) {
    /**
     * Executes the query - dropping rows, columns/fields, tables or dbs
     */
    if ($query_type == 'drop_db'
        || $query_type == 'drop_tbl'
        || $query_type == 'drop_fld'
    ) {
        include_once './libraries/relation_cleanup.lib.php';
    }

    if ($query_type == 'primary_fld') {
        // Gets table primary key
        $GLOBALS['dbi']->selectDb($db);
        $result = $GLOBALS['dbi']->query(
            'SHOW KEYS FROM ' . PMA_Util::backquote($table) . ';'
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
        $default_fk_check_value = PMA_Util::handleDisableFKCheckInit();
    }

    list(
        $result, $rebuild_database_list, $reload_ret,
        $run_parts, $use_sql, $sql_query, $sql_query_views
    ) = PMA_getQueryStrFromSelected(
        $query_type, $selected, $db, $table, $views,
        isset($primary) ? $primary : null,
        isset($from_prefix) ? $from_prefix : null,
        isset($to_prefix) ? $to_prefix : null
    );
    //update the existed variable
    if (isset($reload_ret)) {
        $reload = $reload_ret;
    }

    if ($query_type == 'drop_tbl') {
        if (!empty($sql_query)) {
            $sql_query .= ';';
        } elseif (!empty($sql_query_views)) {
            $sql_query = $sql_query_views . ';';
            unset($sql_query_views);
        }
    }

    if ($use_sql) {

        /**
         * Parse and analyze the query
         */
        include_once 'libraries/parse_analyze.inc.php';

        PMA_executeQueryAndSendQueryResponse(
            $analyzed_sql_results, // analyzed_sql_results
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
    } elseif (!$run_parts) {
        $GLOBALS['dbi']->selectDb($db);
        $result = $GLOBALS['dbi']->tryQuery($sql_query);
        if ($result && !empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = $GLOBALS['dbi']->tryQuery($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = PMA_Message::error($GLOBALS['dbi']->getError());
        }
    }
    if ($query_type == 'drop_tbl'
        || $query_type == 'empty_tbl'
        || $query_type == 'row_delete'
    ) {
        PMA_Util::handleDisableFKCheckCleanup($default_fk_check_value);
    }
    if ($rebuild_database_list) {
        // avoid a problem with the database list navigator
        // when dropping a db from server_databases
        $GLOBALS['pma']->databases->build();
    }
} else {
    if (isset($submit_mult)
        && ($submit_mult == 'sync_unique_columns_central_list'
        || $submit_mult == 'delete_unique_columns_central_list'
        || $submit_mult == 'add_to_central_columns'
        || $submit_mult == 'remove_from_central_columns'
        || $submit_mult == 'make_consistent_with_central_list')
    ) {
        if (isset($centralColsError) && $centralColsError !== true) {
            $message = $centralColsError;
        } else {
            $message = PMA_Message::success(__('Success!'));
        }
    } else {
        $message = PMA_Message::success(__('No change'));
    }
}
