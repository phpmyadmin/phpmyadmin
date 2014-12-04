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
        if ($submit_mult == 'print') {
            include './tbl_printview.php';
        } else {
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
                $show_create = PMA_getHtmlShowCreate($GLOBALS['db'], $selected);
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
        }
    } elseif (isset($selected_fld) && !empty($selected_fld)) {
        // coming from table structure view - do something with
        // selected columns
        $selected = $selected_fld;
        list(
                $what_ret, $query_type_ret, $is_unset_submit_mult, $mult_btn_ret,
                $centralColsError
                )
                    = PMA_getDataForSubmitMult(
                        $submit_mult, $GLOBALS['db'], $table,
                        $selected, $action
                    );
        //update the existing variables
        if (isset($what_ret)) {
            $what = $what_ret;
        }
        if (isset($query_type_ret)) {
            $query_type = $query_type_ret;
        }
        if ($is_unset_submit_mult) {
            unset($submit_mult);
        }
        if (isset($mult_btn_ret)) {
            $mult_btn = $mult_btn_ret;
        }
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
        include './libraries/db_info.inc.php';
    } else {
        include_once './libraries/server_common.inc.php';
    }

    // Builds the query
    list($full_query, $reload, $full_query_views)
        = PMA_getQueryFromSelected(
            $what, $db, $table, $selected, $views
        );

    // Displays the confirmation form
    $_url_params = PMA_getUrlParams(
        $what, $reload, $action, $db, $table, $selected, $views,
        isset($original_sql_query)? $original_sql_query : null,
        isset($original_url_query)? $original_url_query : null
    );

    if ($what == 'replace_prefix_tbl' || $what == 'copy_tbl_change_prefix') {
        echo PMA_getHtmlForReplacePrefixTable($what, $action, $_url_params);
    } elseif ($what == 'add_prefix_tbl') {
        echo PMA_getHtmlForAddPrefixTable($action, $_url_params);
    } else {
        echo PMA_getHtmlForOtherActions($what, $action, $_url_params, $full_query);
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
        $default_fk_check_value = $GLOBALS['dbi']->fetchValue(
            'SHOW VARIABLES LIKE \'foreign_key_checks\';', 0, 1
        ) == 'ON';
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
            $analyzed_sql_results, false, $db, $table, null, null, null,
            false, null, null, null, null, $goto, $pmaThemeImage, null, null,
            $query_type, $sql_query, $selected, null
        );
    } elseif (!$run_parts) {
        $GLOBALS['dbi']->selectDb($db);
        // for disabling foreign key checks while dropping tables
        if (! isset($_REQUEST['fk_check']) && $query_type == 'drop_tbl') {
            $GLOBALS['dbi']->query('SET FOREIGN_KEY_CHECKS = 0;');
        }
        $result = $GLOBALS['dbi']->tryQuery($sql_query);
        if (! isset($_REQUEST['fk_check'])
            && $query_type == 'drop_tbl'
            && $default_fk_check_value
        ) {
            $GLOBALS['dbi']->query('SET FOREIGN_KEY_CHECKS = 1;');
        }
        if ($result && !empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = $GLOBALS['dbi']->tryQuery($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = PMA_Message::error($GLOBALS['dbi']->getError());
        }
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
?>
