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
 * Sets global variables.
 * Here it's better to use a if, instead of the '?' operator
 * to avoid setting a variable to '' when it's not present in $_REQUEST
 */

if (isset($_REQUEST['where_clause'])) {
    $where_clause = $_REQUEST['where_clause'];
}
if (isset($_SESSION['edit_next'])) {
    $where_clause = $_SESSION['edit_next'];
    unset($_SESSION['edit_next']);
    $after_insert = 'edit_next';
}
if (isset($_REQUEST['ShowFunctionFields'])) {
    $cfg['ShowFunctionFields'] = $_REQUEST['ShowFunctionFields'];
}
if (isset($_REQUEST['ShowFieldTypesInDataEditView'])) {
    $cfg['ShowFieldTypesInDataEditView'] = $_REQUEST['ShowFieldTypesInDataEditView'];
}
if (isset($_REQUEST['after_insert'])) {
    $after_insert = $_REQUEST['after_insert'];
}
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
/**
 * @todo check if we could replace by "db_|tbl_" - please clarify!?
 */
$_url_params = array(
    'db' => $db,
    'sql_query' => $_REQUEST['sql_query']
);

if (preg_match('@^tbl_@', $GLOBALS['goto'])) {
    $_url_params['table'] = $table;
}

$err_url = $GLOBALS['goto'] . PMA_generate_common_url($_url_params);
unset($_url_params);


/**
 * Sets parameters for links
 * where is this variable used?
 * replace by PMA_generate_common_url($url_params);
 */
$url_query = PMA_generate_common_url($url_params, 'html', '');

/**
 * get table information
 * @todo should be done by a Table object
 */
require_once 'libraries/tbl_info.inc.php';

/**
 * Get comments for table fileds/columns
 */
$comments_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    $comments_map = PMA_getComments($db, $table);
}

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
 * @todo where does $disp_message and $disp_query come from???
 */
if (! empty($disp_message)) {
    if (! isset($disp_query)) {
        $disp_query     = null;
    }
    $response->addHTML(PMA_Util::getMessage($disp_message, $disp_query));
}

/**
 * Get the analysis of SHOW CREATE TABLE for this table
 */
$analyzed_sql = PMA_Table::analyzeStructure($db, $table);

/**
 * Get the list of the fields of the current table
 */
PMA_DBI_select_db($db);
$table_fields = array_values(PMA_DBI_get_columns($db, $table));

$paramTableDbArray = array($table, $db);

/**
 * Determine what to do, edit or insert? 
 */
if (isset($where_clause)) {
    // we are editing
    $insert_mode = false;
    $where_clause_array = PMA_getWhereClauseArray($where_clause);
    list($where_clauses, $result, $rows, $found_unique_key)
        = PMA_analyzeWhereClauses($where_clause_array, $table, $db);
} else {
    // we are inserting
    $insert_mode = true;
    $where_clause = null;
    list($result, $rows) = PMA_loadFirstRow($table, $db);
    $where_clauses = null;
    $where_clause_array = null;
    $found_unique_key = false;
}

// Copying a row - fetched data will be inserted as a new row,
// therefore the where clause is needless.
if (isset($_REQUEST['default_action']) && $_REQUEST['default_action'] === 'insert') {
    $where_clause = $where_clauses = null;
}

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
$columns_cnt     = count($table_fields);

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

//Insert/Edit form
//If table has blob fields we have to disable ajax.
$has_blob_field = false;
foreach ($table_fields as $column) {
    if (PMA_isColumnBlob($column)) {
        $has_blob_field = true;
        break;
    }
}
$html_output .='<form id="insertForm" ';
if ($has_blob_field && $is_upload) {
    $html_output .='class="disableAjax" ';
}
$html_output .='method="post" action="tbl_replace.php" name="insertForm" ';
if ($is_upload) {
    $html_output .= ' enctype="multipart/form-data"';
}
$html_output .= '>';
$html_output .= PMA_generate_common_hidden_inputs($_form_params);

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
    $rownumber_param = '&amp;rownumber=' . $row_id;
    $vkey = '[multi_edit][' . $jsvkey . ']';

    $current_result = (isset($result) && is_array($result) && isset($result[$row_id])
        ? $result[$row_id]
        : $result);
    if ($insert_mode && $row_id > 0) {
        $html_output .= '<input type="checkbox" checked="checked"'
            . ' name="insert_ignore_' . $row_id . '"'
            . ' id="insert_ignore_' . $row_id . '" />'
            .'<label for="insert_ignore_' . $row_id . '">'
            . __('Ignore')
            . '</label><br />' . "\n";
    }

    $html_output .= PMA_getHeadAndFootOfInsertRowTable($url_params)
        . '<tbody>';

    // Sets a multiplier used for input-field counts
    // (as zero cannot be used, advance the counter plus one)
    $m_rows = $o_rows + 1;
    //store the default value for CharEditing
    $default_char_editing  = $cfg['CharEditing'];

    $odd_row = true;
    for ($i = 0; $i < $columns_cnt; $i++) {
        if (! isset($table_fields[$i]['processed'])) {
            $column = $table_fields[$i];
            $column = PMA_analyzeTableColumnsArray(
                $column, $comments_map, $timestamp_seen
            );
        }

        $extracted_columnspec
            = PMA_Util::extractColumnSpec($column['Type']);

        if (-1 === $column['len']) {
            $column['len'] = PMA_DBI_field_len($current_result, $i);
            // length is unknown for geometry fields,
            // make enough space to edit very simple WKTs
            if (-1 === $column['len']) {
                $column['len'] = 30;
            }
        }
        //Call validation when the form submited...
        $unnullify_trigger = $chg_evt_handler
            . "=\"return verificationsAfterFieldChange('"
            . PMA_escapeJsString($column['Field_md5']) . "', '"
            . PMA_escapeJsString($jsvkey) . "','".$column['pma_type'] . "')\"";

        // Use an MD5 as an array index to avoid having special characters
        // in the name atttibute (see bug #1746964 )
        $column_name_appendix = $vkey . '[' . $column['Field_md5'] . ']';

        if ($column['Type'] == 'datetime'
            && ! isset($column['Default'])
            && ! is_null($column['Default'])
            && ($insert_mode || ! isset($current_row[$column['Field']]))
        ) {
            // INSERT case or
            // UPDATE case with an NULL value
            $current_row[$column['Field']] = date('Y-m-d H:i:s', time());
        }

        $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even' ) . '">'
            . '<td ' . ($cfg['LongtextDoubleTextarea'] && strstr($column['True_Type'], 'longtext') ? 'rowspan="2"' : '') . 'class="center">'
            . $column['Field_title']
            . '<input type="hidden" name="fields_name' . $column_name_appendix . '" value="' . $column['Field_html'] . '"/>'
            . '</td>';
        if ($cfg['ShowFieldTypesInDataEditView']) {
             $html_output .= '<td class="center' . $column['wrap'] . '">'
                . '<span class="column_type">' . $column['pma_type'] . '</span>'
                . '</td>';
        } //End if

        // Get a list of GIS data types.
        $gis_data_types = PMA_Util::getGISDatatypes();

        // Prepares the field value
        $real_null_value = false;
        $special_chars_encoded = '';
        if (isset($current_row)) {
            // (we are editing)
            list(
                $real_null_value, $special_chars_encoded, $special_chars,
                $data, $backup_field
            )
                = PMA_getSpecialCharsAndBackupFieldForExistingRow(
                    $current_row, $column, $extracted_columnspec,
                    $real_null_value, $gis_data_types, $column_name_appendix
                );
        } else {
            // (we are inserting)
            // display default values
            list($real_null_value, $data, $special_chars, $backup_field, $special_chars_encoded)
                = PMA_getSpecialCharsAndBackupFieldForInsertingMode($column, $real_null_value);
        }

        $idindex = ($o_rows * $columns_cnt) + $i + 1;
        $tabindex = $idindex;

        // Get a list of data types that are not yet supported.
        $no_support_types = PMA_Util::unsupportedDatatypes();

        // The function column
        // -------------------
        if ($cfg['ShowFunctionFields']) {
            $html_output .= PMA_getFunctionColumn(
                $column, $is_upload, $column_name_appendix,
                $unnullify_trigger, $no_support_types, $tabindex_for_function,
                $tabindex, $idindex, $insert_mode
            );
        }

        // The null column
        // ---------------
        $foreignData = PMA_getForeignData(
            $foreigners, $column['Field'], false, '', ''
        );
        $html_output .= PMA_getNullColumn(
            $column, $column_name_appendix, $real_null_value,
            $tabindex, $tabindex_for_null, $idindex, $vkey, $foreigners,
            $foreignData
        );

        // The value column (depends on type)
        // ----------------
        // See bug #1667887 for the reason why we don't use the maxlength
        // HTML attribute
        $html_output .= '        <td>' . "\n";
        // Will be used by js/tbl_change.js to set the default value
        // for the "Continue insertion" feature
        $html_output .= '<span class="default_value hide">'
            . $special_chars . '</span>';

        $html_output .= PMA_getValueColumn(
            $column, $backup_field, $column_name_appendix, $unnullify_trigger,
            $tabindex, $tabindex_for_value, $idindex, $data, $special_chars,
            $foreignData, $odd_row, $paramTableDbArray, $rownumber_param, $titles,
            $text_dir, $special_chars_encoded, $vkey, $is_upload,
            $biggest_max_file_size, $default_char_editing,
            $no_support_types, $gis_data_types, $extracted_columnspec
        );

        $html_output .= '</td>'
        . '</tr>';

        $odd_row = !$odd_row;
    } // end for
    $o_rows++;
    $html_output .= '  </tbody>'
        . '</table><br />';
} // end foreach on multi-edit

$html_output .='<div id="gis_editor"></div>'
    . '<div id="popup_background"></div>'
    . '<br />';

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
