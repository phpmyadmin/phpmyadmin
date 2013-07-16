<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL executor
 *
 * @todo    we must handle the case if sql.php is called directly with a query
 *          that returns 0 rows - to prevent cyclic redirects or includes
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/Header.class.php';
require_once 'libraries/check_user_privileges.lib.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/sql.lib.php';
require_once 'libraries/sqlparser.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
$scripts->addFile('tbl_change.js');
// the next one needed because sql.php may do a "goto" to tbl_structure.php
$scripts->addFile('tbl_structure.js');
$scripts->addFile('indexes.js');
$scripts->addFile('gis_data_editor.js');

/**
 * Set ajax_reload in the response if it was already set
 */
if (isset($ajax_reload) && $ajax_reload['reload'] === true) {
    $response->addJSON('ajax_reload', $ajax_reload);
}


/**
 * Defines the url to return to in case of error in a sql statement
 */
// Security checkings
if (! empty($goto)) {
    $is_gotofile     = preg_replace('@^([^?]+).*$@s', '\\1', $goto);
    if (! @file_exists('' . $is_gotofile)) {
        unset($goto);
    } else {
        $is_gotofile = ($is_gotofile == $goto);
    }
} else {
    if (empty($table)) {
        $goto = $cfg['DefaultTabDatabase'];
    } else {
        $goto = $cfg['DefaultTabTable'];
    }
    $is_gotofile  = true;
} // end if

if (! isset($err_url)) {
    $err_url = (! empty($back) ? $back : $goto)
        . '?' . PMA_generate_common_url($db)
        . ((strpos(' ' . $goto, 'db_') != 1 && strlen($table))
            ? '&amp;table=' . urlencode($table)
            : ''
        );
} // end if

// Coming from a bookmark dialog
if (isset($_POST['bkm_fields']['bkm_sql_query'])) {
    $sql_query = $_POST['bkm_fields']['bkm_sql_query'];
} elseif (isset($_GET['sql_query'])) {
    $sql_query = $_GET['sql_query'];
}

// This one is just to fill $db
if (isset($_POST['bkm_fields']['bkm_database'])) {
    $db = $_POST['bkm_fields']['bkm_database'];
}


// During grid edit, if we have a relational field, show the dropdown for it.
if (isset($_REQUEST['get_relational_values'])
    && $_REQUEST['get_relational_values'] == true
) {
    PMA_getRelationalValues($db, $table, $display_field);
}

// Just like above, find possible values for enum fields during grid edit.
if (isset($_REQUEST['get_enum_values']) && $_REQUEST['get_enum_values'] == true) {
    PMA_getEnumOrSetValues($db, $table, "enum");
}


// Find possible values for set fields during grid edit.
if (isset($_REQUEST['get_set_values']) && $_REQUEST['get_set_values'] == true) {
    PMA_getEnumOrSetValues($db, $table, "set");
}

/**
 * Check ajax request to set the column order and visibility
 */
if (isset($_REQUEST['set_col_prefs']) && $_REQUEST['set_col_prefs'] == true) {
    PMA_setColumnOrderOrVisibility($table, $db);
}

// Default to browse if no query set and we have table
// (needed for browsing from DefaultTabTable)
if (empty($sql_query) && strlen($table) && strlen($db)) {
    $sql_query = PMA_getDefaultSqlQueryForBrowse($db, $table);
    
    // set $goto to what will be displayed if query returns 0 rows
    $goto = '';
} else {
    // Now we can check the parameters
    PMA_Util::checkParameters(array('sql_query'));
}

/**
 * Parse and analyze the query
 */
require_once 'libraries/parse_analyze.inc.php';


/**
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if (PMA_hasNoRightsToDropDatabase(
    $analyzed_sql_results, $cfg['AllowUserDropDatabase'], $is_superuser
)) {
    PMA_Util::mysqlDie(
        __('"DROP DATABASE" statements are disabled.'),
        '',
        '',
        $err_url
    );
} // end if

// Include PMA_Index class for use in PMA_DisplayResults class
require_once './libraries/Index.class.php';

require_once 'libraries/DisplayResults.class.php';

$displayResultsObject = new PMA_DisplayResults(
    $GLOBALS['db'], $GLOBALS['table'], $GLOBALS['goto'], $GLOBALS['sql_query']
);

$displayResultsObject->setConfigParamsForDisplayTable();

/**
 * Need to find the real end of rows?
 */
if (isset($find_real_end) && $find_real_end) {
    $unlim_num_rows = PMA_findRealEndOfRows($db, $table);
}


/**
 * Bookmark add
 */
if (isset($_POST['store_bkm'])) {
    PMA_addBookmark($cfg['PmaAbsoluteUri'], $goto);
} // end if


/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php') {
    $is_gotofile = false;
    $goto = 'sql.php?'
          . PMA_generate_common_url($db, $table)
          . '&amp;sql_query=' . urlencode($sql_query);
} // end if


// assign default full_sql_query
$full_sql_query = $sql_query;

// Handle remembered sorting order, only for single table query
if (PMA_isRememberSortingOrder($analyzed_sql_results)) {
    PMA_handleSortOrder($db, $table, $analyzed_sql, $full_sql_query);
}

// Do append a "LIMIT" clause?
if (PMA_isAppendLimitClause($analyzed_sql_results)) {
    list($sql_limit_to_append,
        $full_sql_query, $analyzed_display_query, $display_query
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
    $analyzed_sql_results, $full_sql_query, $is_gotofile, $db, $table,
    isset($find_real_end) ? $find_real_end : null,
    isset($import_text) ? $import_text : null, $cfg['Bookmark']['user'],
    isset($extra_data) ? $extra_data : null
);


// No rows returned -> move back to the calling page
if ((0 == $num_rows && 0 == $unlim_num_rows) || $is_affected) {
    PMA_sendResponseForNoResultsReturned(
        $analyzed_sql_results, $db, $table,
        isset($message_to_show) ? $message_to_show : null,
        $num_rows, $displayResultsObject, $extra_data, $cfg
    );
   
} else {
    // At least one row is returned -> displays a table with results    
    PMA_sendResponseForResultsReturned(
        isset($result) ? $result : null, $justBrowsing, $analyzed_sql_results,
        $db, $table, isset($disp_mode) ? $disp_mode : null,
        isset($message) ? $message : null, isset($sql_data) ? $sql_data : null,
        $displayResultsObject, $goto, $pmaThemeImage,
        $sql_limit_to_append, $unlim_num_rows,
        $num_rows, $querytime, $full_sql_query,
        isset($disp_query) ? $disp_query : null,
        isset($disp_message) ? $disp_message : null, $profiling_results,
        isset($query_type) ? $query_type : null,
        isset($selected) ? $selected : null, $sql_query,
        isset($complete_query) ? $complete_query : null, $cfg
    );
} // end rows returned
?>
