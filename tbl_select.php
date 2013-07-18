<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table search tab
 *
 * display table search form, create SQL query from form data
 * and include sql.php to execute it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/TableSearch.class.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/sql.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('sql.js');
$scripts->addFile('tbl_select.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
$scripts->addFile('gis_data_editor.js');

$post_params = array(
    'ajax_request',
    'session_max_rows'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

$table_search = new PMA_TableSearch($db, $table, "normal");

/**
 * No selection criteria received -> display the selection form
 */
if (! isset($_POST['columnsToDisplay']) && ! isset($_POST['displayAllColumns'])) {
    // Gets some core libraries
    include_once 'libraries/tbl_common.inc.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';
    /**
     * Gets table's information
     */
    include_once 'libraries/tbl_info.inc.php';

    if (! isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);
    // Displays the table search form
    $response->addHTML($table_search->getSecondaryTabs());
    $response->addHTML($table_search->getSelectionForm($goto));

} else {
    /**
     * Selection criteria have been submitted -> do the work
     */
    $sql_query = $table_search->buildSqlQuery();

    /**
     * Parse and analyze the query
     */
    require_once 'libraries/parse_analyze.inc.php';

    // Include PMA_Index class for use in PMA_DisplayResults class
    require_once './libraries/Index.class.php';

    require_once 'libraries/DisplayResults.class.php';

    $displayResultsObject = new PMA_DisplayResults(
        $GLOBALS['db'], $GLOBALS['table'], $GLOBALS['goto'], $GLOBALS['sql_query']
    );

    $displayResultsObject->setConfigParamsForDisplayTable();
    
    // assign default full_sql_query
    $full_sql_query = $sql_query;

    // Handle remembered sorting order, only for single table query
    if (PMA_isRememberSortingOrder($analyzed_sql_results)) {
        PMA_handleSortOrder($db, $table, $analyzed_sql, $full_sql_query);
    }

    // Do append a "LIMIT" clause?
    if (PMA_isAppendLimitClause($analyzed_sql_results)) {
        list($sql_limit_to_append, $full_sql_query,
            $analyzed_display_query, $display_query
        ) = PMA_appendLimitClause(
            $full_sql_query, $analyzed_sql, isset($display_query)
        );
    } else {
        $sql_limit_to_append = '';
    }

    $reload = PMA_hasCurrentDbChanged($db);

    // Execute the query
    list($result, $num_rows, $unlim_num_rows, $profiling_results,
        $justBrowsing, $extra_data
    ) = PMA_executeTheQuery(
        $analyzed_sql_results, $full_sql_query, false, $db, $table,
        null, null, $cfg['Bookmark']['user'], null
    );

    if ((0 == $num_rows && 0 == $unlim_num_rows) || $is_affected) {
        // No rows returned -> move back to the calling page
        PMA_sendResponseForNoResultsReturned(
            $analyzed_sql_results, $db, $table, null,
            $num_rows, $displayResultsObject, $extra_data, $cfg
        );
    } else {
        // At least one row is returned -> displays a table with results    
        PMA_sendResponseForResultsReturned(
            $result, $justBrowsing, $analyzed_sql_results, $db, $table, null, null,
            null, $displayResultsObject, $goto, $pmaThemeImage,
            $sql_limit_to_append, $unlim_num_rows, $num_rows, $querytime,
            $full_sql_query, null, null, $profiling_results, null, null, $sql_query,
            null, $cfg
        );
    } // end rows returned
}
?>
