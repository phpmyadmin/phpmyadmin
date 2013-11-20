<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for editing and inserting new table rows
 *
 * register_globals_save (mark this file save for disabling register globals)
 *
 * @package PhpMyAdmin
 */

/**
 * Gets the variables sent or posted to this script and displays the header
 */
require_once 'libraries/common.inc.php';

/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require_once 'libraries/db_table_exists.lib.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/insert_edit.lib.php';

/**
 * Determine whether Insert or Edit and set global variables
 */
list(
    $insert_mode, $where_clause, $where_clause_array, $where_clauses,
    $result, $rows, $found_unique_key, $after_insert
) = PMA_determineInsertOrEdit(
    isset($where_clause) ? $where_clause : null, $db, $table
);

/**
 * file listing
*/
require_once 'libraries/file_listing.lib.php';

/**
 * Defines the url to return to in case of error in a sql statement
 * (at this point, $GLOBALS['goto'] will be set but could be empty)
 */
if (empty($GLOBALS['goto'])) {
    if (strlen($table)) {
        // avoid a problem (see bug #2202709)
        $GLOBALS['goto'] = 'tbl_sql.php';
    } else {
        $GLOBALS['goto'] = 'db_sql.php';
    }
}


$_url_params = PMA_getUrlParameters($db, $table);
$err_url = $GLOBALS['goto'] . PMA_URL_getCommon($_url_params);
unset($_url_params);

$comments_map = PMA_getCommentsMap($db, $table);

/**
 * START REGULAR OUTPUT
 */

/**
 * Load JavaScript files
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('functions.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
$scripts->addFile('gis_data_editor.js');

/**
 * Displays the query submitted and its result
 *
 * $disp_message come from tbl_replace.php
 */
if (! empty($disp_message)) {
    $response->addHTML(PMA_Util::getMessage($disp_message, null));
}


$table_columns = PMA_getTableColumns($db, $table);

// retrieve keys into foreign fields, if any
$foreigners = PMA_getForeigners($db, $table);

// Retrieve form parameters for insert/edit form
$_form_params = PMA_getFormParametersForInsertForm(
    $db, $table, $where_clauses, $where_clause_array, $err_url
);

/**
 * Displays the form
 */
// autocomplete feature of IE kills the "onchange" event handler and it
//        must be replaced by the "onpropertychange" one in this case
$chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE'
    && PMA_USR_BROWSER_VER >= 5
    && PMA_USR_BROWSER_VER < 7
)
     ? 'onpropertychange'
     : 'onchange';
// Had to put the URI because when hosted on an https server,
// some browsers send wrongly this form to the http server.

$html_output = '';
// Set if we passed the first timestamp field
$timestamp_seen = false;
$columns_cnt     = count($table_columns);

$tabindex              = 0;
$tabindex_for_function = +3000;
$tabindex_for_null     = +6000;
$tabindex_for_value    = 0;
$o_rows                = 0;
$biggest_max_file_size = 0;

$url_params['db'] = $db;
$url_params['table'] = $table;
$url_params = PMA_urlParamsInEditMode(
    $url_params, $where_clause_array, $where_clause
);

$has_blob_field = false;
foreach ($table_columns as $column) {
    if (PMA_isColumnBlob($column)) {
        $has_blob_field = true;
        break;
    }
}

//Insert/Edit form
//If table has blob fields we have to disable ajax.
$html_output .= PMA_getHtmlForInsertEditFormHeader($has_blob_field, $is_upload);

$html_output .= PMA_URL_getHiddenInputs($_form_params);

$titles['Browse'] = PMA_Util::getIcon('b_browse.png', __('Browse foreign values'));

// user can toggle the display of Function column and column types
// (currently does not work for multi-edits)
if (! $cfg['ShowFunctionFields'] || ! $cfg['ShowFieldTypesInDataEditView']) {
    $html_output .= __('Show');
}

if (! $cfg['ShowFunctionFields']) {
    $html_output .= PMA_showFunctionFieldsInEditMode($url_params, false);
}

if (! $cfg['ShowFieldTypesInDataEditView']) {
    $html_output .= PMA_showColumnTypesInDataEditView($url_params, false);
}

foreach ($rows as $row_id => $current_row) {
    if ($current_row === false) {
        unset($current_row);
    }

    $jsvkey = $row_id;
    $vkey = '[multi_edit][' . $jsvkey . ']';

    $current_result = (isset($result) && is_array($result) && isset($result[$row_id])
        ? $result[$row_id]
        : $result);
    if ($insert_mode && $row_id > 0) {
        $html_output .= PMA_getHtmlForIgnoreOption($row_id);
    }

    $html_output .= PMA_getHtmlForInsertEditRow(
        $url_params, $table_columns, $column, $comments_map, $timestamp_seen,
        $current_result, $chg_evt_handler, $jsvkey, $vkey, $insert_mode,
        isset($current_row) ? $current_row : null, $o_rows, $tabindex, $columns_cnt,
        $is_upload, $tabindex_for_function, $foreigners, $tabindex_for_null,
        $tabindex_for_value, $table, $db, $row_id, $titles,
        $biggest_max_file_size, $text_dir
    );
} // end foreach on multi-edit

$html_output .= PMA_getHtmlForGisEditor();

if (! isset($after_insert)) {
    $after_insert = 'back';
}

//action panel
$html_output .= PMA_getActionsPanel(
    $where_clause, $after_insert, $tabindex,
    $tabindex_for_value, $found_unique_key
);

if ($biggest_max_file_size > 0) {
    $html_output .= '        '
        . PMA_Util::generateHiddenMaxFileSize(
            $biggest_max_file_size
        ) . "\n";
}
$html_output .= '</form>';
// end Insert/Edit form

if ($insert_mode) {
    //Continue insertion form
    $html_output .= PMA_getContinueInsertionForm(
        $table, $db, $where_clause_array, $err_url
    );
}

$response->addHTML($html_output);
?>
