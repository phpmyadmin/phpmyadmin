<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL executor
 *
 * @todo    we must handle the case if sql.php is called directly with a query
 *          that returns 0 rows - to prevent cyclic redirects or includes
 * @package PhpMyAdmin
 */
use PMA\libraries\config\PageSettings;
use PMA\libraries\Response;
use PMA\libraries\Util;

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/check_user_privileges.lib.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/sql.lib.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

PageSettings::showGroup('Browse');


$response = Response::getInstance();
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
$is_gotofile  = true;
if (empty($goto)) {
    if (empty($table)) {
        $goto = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
        );
    } else {
        $goto = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabTable'], 'table'
        );
    }
} // end if

if (! isset($err_url)) {
    $err_url = (! empty($back) ? $back : $goto)
        . '?' . PMA_URL_getCommon(array('db' => $GLOBALS['db']))
        . ((mb_strpos(' ' . $goto, 'db_') != 1
            && mb_strlen($table))
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

if (isset($_REQUEST['get_default_fk_check_value'])
    && $_REQUEST['get_default_fk_check_value'] == true
) {
    $response = Response::getInstance();
    $response->addJSON(
        'default_fk_check_value', Util::isForeignKeyCheck()
    );
    exit;
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
$tableLength = mb_strlen($table);
$dbLength = mb_strlen($db);
if (empty($sql_query) && $tableLength && $dbLength) {
    $sql_query = PMA_getDefaultSqlQueryForBrowse($db, $table);

    // set $goto to what will be displayed if query returns 0 rows
    $goto = '';
} else {
    // Now we can check the parameters
    Util::checkParameters(array('sql_query'));
}

/**
 * Parse and analyze the query
 */
require_once 'libraries/parse_analyze.lib.php';
list(
    $analyzed_sql_results,
    $db,
    $table_from_sql
) = PMA_parseAnalyze($sql_query, $db);
// @todo: possibly refactor
extract($analyzed_sql_results);

if ($table != $table_from_sql && !empty($table_from_sql)) {
    $table = $table_from_sql;
}


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
    Util::mysqlDie(
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
    PMA_addBookmark($goto);
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
    $analyzed_sql_results, // analyzed_sql_results
    $is_gotofile, // is_gotofile
    $db, // db
    $table, // table
    isset($find_real_end) ? $find_real_end : null, // find_real_end
    isset($import_text) ? $import_text : null, // sql_query_for_bookmark
    isset($extra_data) ? $extra_data : null, // extra_data
    isset($message_to_show) ? $message_to_show : null, // message_to_show
    isset($message) ? $message : null, // message
    isset($sql_data) ? $sql_data : null, // sql_data
    $goto, // goto
    $pmaThemeImage, // pmaThemeImage
    isset($disp_query) ? $display_query : null, // disp_query
    isset($disp_message) ? $disp_message : null, // disp_message
    isset($query_type) ? $query_type : null, // query_type
    $sql_query, // sql_query
    isset($selected) ? $selected : null, // selectedTables
    isset($complete_query) ? $complete_query : null // complete_query
);
