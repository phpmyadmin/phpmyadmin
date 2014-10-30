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
$scripts->addFile('jquery/jquery.uitablefilter.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('indexes.js');
$scripts->addFile('gis_data_editor.js');
$scripts->addFile('multi_column_sort.js');

/**
 * Set ajax_reload in the response if it was already set
 */
if (isset($ajax_reload) && $ajax_reload['reload'] === true) {
    $response->addJSON('ajax_reload', $ajax_reload);
}


/**
 * Defines the url to return to in case of error in a sql statement
 */
// Security checks
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

/** @var PMA_String $pmaString */
$pmaString = $GLOBALS['PMA_String'];
if (! isset($err_url)) {
    $err_url = (! empty($back) ? $back : $goto)
        . '?' . PMA_URL_getCommon(array('db' => $GLOBALS['db']))
        . ((/*overload*/mb_strpos(' ' . $goto, 'db_') != 1
            && /*overload*/mb_strlen($table))
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
    PMA_getRelationalValues($db, $table);
    // script has exited at this point
}

// Just like above, find possible values for enum fields during grid edit.
if (isset($_REQUEST['get_enum_values']) && $_REQUEST['get_enum_values'] == true) {
    PMA_getEnumOrSetValues($db, $table, "enum");
    // script has exited at this point
}


// Find possible values for set fields during grid edit.
if (isset($_REQUEST['get_set_values']) && $_REQUEST['get_set_values'] == true) {
    PMA_getEnumOrSetValues($db, $table, "set");
    // script has exited at this point
}

/**
 * Check ajax request to set the column order and visibility
 */
if (isset($_REQUEST['set_col_prefs']) && $_REQUEST['set_col_prefs'] == true) {
    PMA_setColumnOrderOrVisibility($table, $db);
    // script has exited at this point
}

// Default to browse if no query set and we have table
// (needed for browsing from DefaultTabTable)
$tableLength = /*overload*/mb_strlen($table);
$dbLength = /*overload*/mb_strlen($db);
if (empty($sql_query) && $tableLength && $dbLength) {
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
        false,
        $err_url
    );
} // end if

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
    // script has exited at this point
} // end if


/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php') {
    $is_gotofile = false;
    $goto = 'sql.php' . PMA_URL_getCommon(
        array(
            'db' => $db,
            'table' => $table,
            'sql_query' => $sql_query
        )
    );
} // end if

PMA_executeQueryAndSendQueryResponse(
    $analyzed_sql_results,
    $is_gotofile,
    $db,
    $table,
    isset($find_real_end) ? $find_real_end : null,
    isset($import_text) ? $import_text : null,
    isset($extra_data) ? $extra_data : null,
    $is_affected,
    isset($message_to_show) ? $message_to_show : null,
    isset($disp_mode) ? $disp_mode : null,
    isset($message) ? $message : null,
    isset($sql_data) ? $sql_data : null,
    $goto,
    $pmaThemeImage,
    isset($disp_query) ? $display_query : null,
    isset($disp_message) ? $disp_message : null,
    isset($query_type) ? $query_type : null,
    $sql_query,
    isset($selected) ? $selected : null,
    isset($complete_query) ? $complete_query : null
);

?>
