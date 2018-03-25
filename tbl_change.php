<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for editing and inserting new table rows
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use PhpMyAdmin\Url;

/**
 * Gets the variables sent or posted to this script and displays the header
 */
require_once 'libraries/common.inc.php';

PageSettings::showGroup('Edit');

/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require_once 'libraries/db_table_exists.inc.php';

$insertEdit = new InsertEdit($GLOBALS['dbi']);

/**
 * Determine whether Insert or Edit and set global variables
 */
list(
    $insert_mode, $where_clause, $where_clause_array, $where_clauses,
    $result, $rows, $found_unique_key, $after_insert
) = $insertEdit->determineInsertOrEdit(
    isset($where_clause) ? $where_clause : null, $db, $table
);
// Increase number of rows if unsaved rows are more
if (!empty($unsaved_values) && count($rows) < count($unsaved_values)) {
    $rows = array_fill(0, count($unsaved_values), false);
}

/**
 * Defines the url to return to in case of error in a sql statement
 * (at this point, $GLOBALS['goto'] will be set but could be empty)
 */
if (empty($GLOBALS['goto'])) {
    if (strlen($table) > 0) {
        // avoid a problem (see bug #2202709)
        $GLOBALS['goto'] = 'tbl_sql.php';
    } else {
        $GLOBALS['goto'] = 'db_sql.php';
    }
}


$_url_params = $insertEdit->getUrlParameters($db, $table);
$err_url = $GLOBALS['goto'] . Url::getCommon($_url_params);
unset($_url_params);

$comments_map = $insertEdit->getCommentsMap($db, $table);

/**
 * START REGULAR OUTPUT
 */

/**
 * Load JavaScript files
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('sql.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('vendor/jquery/additional-methods.js');
$scripts->addFile('gis_data_editor.js');

/**
 * Displays the query submitted and its result
 *
 * $disp_message come from tbl_replace.php
 */
if (! empty($disp_message)) {
    $response->addHTML(Util::getMessage($disp_message, null));
}

$table_columns = $insertEdit->getTableColumns($db, $table);

// retrieve keys into foreign fields, if any
$relation = new Relation();
$foreigners = $relation->getForeigners($db, $table);

// Retrieve form parameters for insert/edit form
$_form_params = $insertEdit->getFormParametersForInsertForm(
    $db, $table, $where_clauses, $where_clause_array, $err_url
);

/**
 * Displays the form
 */
// autocomplete feature of IE kills the "onchange" event handler and it
//        must be replaced by the "onpropertychange" one in this case
$chg_evt_handler =  'onchange';
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
$url_params = $insertEdit->urlParamsInEditMode(
    $url_params, $where_clause_array, $where_clause
);

$has_blob_field = false;
foreach ($table_columns as $column) {
    if ($insertEdit->isColumn(
        $column,
        array('blob', 'tinyblob', 'mediumblob', 'longblob')
    )) {
        $has_blob_field = true;
        break;
    }
}

//Insert/Edit form
//If table has blob fields we have to disable ajax.
$html_output .= $insertEdit->getHtmlForInsertEditFormHeader($has_blob_field, $is_upload);

$html_output .= Url::getHiddenInputs($_form_params);

$titles['Browse'] = Util::getIcon('b_browse', __('Browse foreign values'));

// user can toggle the display of Function column and column types
// (currently does not work for multi-edits)
if (! $cfg['ShowFunctionFields'] || ! $cfg['ShowFieldTypesInDataEditView']) {
    $html_output .= __('Show');
}

if (! $cfg['ShowFunctionFields']) {
    $html_output .= $insertEdit->showTypeOrFunction('function', $url_params, false);
}

if (! $cfg['ShowFieldTypesInDataEditView']) {
    $html_output .= $insertEdit->showTypeOrFunction('type', $url_params, false);
}

$GLOBALS['plugin_scripts'] = array();
foreach ($rows as $row_id => $current_row) {
    if (empty($current_row)) {
        $current_row = array();
    }

    $jsvkey = $row_id;
    $vkey = '[multi_edit][' . $jsvkey . ']';

    $current_result = (isset($result) && is_array($result) && isset($result[$row_id])
        ? $result[$row_id]
        : $result);
    $repopulate = array();
    $checked = true;
    if (isset($unsaved_values[$row_id])) {
        $repopulate = $unsaved_values[$row_id];
        $checked = false;
    }
    if ($insert_mode && $row_id > 0) {
        $html_output .= $insertEdit->getHtmlForIgnoreOption($row_id, $checked);
    }

    $html_output .= $insertEdit->getHtmlForInsertEditRow(
        $url_params, $table_columns, $comments_map, $timestamp_seen,
        $current_result, $chg_evt_handler, $jsvkey, $vkey, $insert_mode,
        $current_row, $o_rows, $tabindex, $columns_cnt,
        $is_upload, $tabindex_for_function, $foreigners, $tabindex_for_null,
        $tabindex_for_value, $table, $db, $row_id, $titles,
        $biggest_max_file_size, $text_dir, $repopulate, $where_clause_array
    );
} // end foreach on multi-edit
$scripts->addFiles($GLOBALS['plugin_scripts']);
unset($unsaved_values, $checked, $repopulate, $GLOBALS['plugin_scripts']);

if (! isset($after_insert)) {
    $after_insert = 'back';
}

//action panel
$html_output .= $insertEdit->getActionsPanel(
    $where_clause, $after_insert, $tabindex,
    $tabindex_for_value, $found_unique_key
);

if ($biggest_max_file_size > 0) {
    $html_output .= '        '
        . Util::generateHiddenMaxFileSize(
            $biggest_max_file_size
        ) . "\n";
}
$html_output .= '</form>';

$html_output .= $insertEdit->getHtmlForGisEditor();
// end Insert/Edit form

if ($insert_mode) {
    //Continue insertion form
    $html_output .= $insertEdit->getContinueInsertionForm(
        $table, $db, $where_clause_array, $err_url
    );
}

$response->addHTML($html_output);
