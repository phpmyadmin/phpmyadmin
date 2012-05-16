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
require_once 'libraries/common.lib.php';

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
}
if (isset($_REQUEST['ShowFunctionFields'])) {
    $cfg['ShowFunctionFields'] = $_REQUEST['ShowFunctionFields'];
}
if (isset($_REQUEST['ShowFieldTypesInDataEditView'])) {
    $cfg['ShowFieldTypesInDataEditView'] = $_REQUEST['ShowFieldTypesInDataEditView'];
}

/**
 * file listing
 */
require_once 'libraries/file_listing.php';


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
 * used in ./libraries/header.inc.php to load JavaScript library file
 */
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'tbl_change.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'gis_data_editor.js';

/**
 * HTTP and HTML headers
 */
require_once 'libraries/header.inc.php';

/**
 * Displays the query submitted and its result
 *
 * @todo where does $disp_message and $disp_query come from???
 */
if (! empty($disp_message)) {
    if (! isset($disp_query)) {
        $disp_query     = null;
    }
    PMA_showMessage($disp_message, $disp_query);
}

/**
 * Get the analysis of SHOW CREATE TABLE for this table
 * @todo should be handled by class Table
 */
$show_create_table = PMA_DBI_fetch_value(
    'SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table),
    0, 1
);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
unset($show_create_table);

/**
 * Get the list of the fields of the current table
 */
PMA_DBI_select_db($db);
$table_fields = array_values(PMA_DBI_get_columns($db, $table));

$paramTableDbArray = array($table, $db);
//Retrieve values for data edit view
list($insert_mode, $where_clauses, $result, $rows, $where_clause_array, $found_unique_key)
    = PMA_getValuesForEditMode($paramTableDbArray);

// Copying a row - fetched data will be inserted as a new row, therefore the where clause is needless.
if (isset($_REQUEST['default_action']) && $_REQUEST['default_action'] === 'insert') {
    unset($where_clause, $where_clauses);
}

// retrieve keys into foreign fields, if any
$foreigners = PMA_getForeigners($db, $table);

// Retrieve form parameters for insert/edit form
$_form_params = PMA_getFormParametersForInsertForm(
    $paramTableDbArray, $where_clauses, $where_clause_array, $err_url);

/**
 * Displays the form
 */
// autocomplete feature of IE kills the "onchange" event handler and it
//        must be replaced by the "onpropertychange" one in this case
$chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 5 && PMA_USR_BROWSER_VER < 7)
     ? 'onpropertychange'
     : 'onchange';
// Had to put the URI because when hosted on an https server,
// some browsers send wrongly this form to the http server.

$html_output = '';
//Set on key handler for moving using by Ctrl+arrows
$html_output .= '<script src="js/keyhandler.js" type="text/javascript"></script>'
    . '<script type="text/javascript">'
    . 'var switch_movement = 0;'
    . 'document.onkeydown = onKeyDownArrowsHandler;'
    . '</script>';
// Set if we passed the first timestamp field
$timestamp_seen = 0;
$columns_cnt     = count($table_fields);

$tabindex = 0;
$tabindex_for_function = +3000;
$tabindex_for_null     = +6000;
$tabindex_for_value    = 0;
$o_rows   = 0;
$biggest_max_file_size = 0;

$url_params['db'] = $db;
$url_params['table'] = $table;
$url_params = PMA_urlParamsInEditMode($url_params);

//Insert/Edit form
$html_output .= '<form id="insertForm" method="post" action="tbl_replace.php" name="insertForm" ';
if ($is_upload) {
    $html_output .= ' enctype="multipart/form-data"';
}
$html_output .= '>';
    

$html_output .= PMA_generate_common_hidden_inputs($_form_params);

$titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));

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

foreach ($rows as $row_id => $vrow) {
    if ($vrow === false) {
        unset($vrow);
    }

    $jsvkey = $row_id;
    $rownumber_param = '&amp;rownumber=' . $row_id;
    $vkey = '[multi_edit][' . $jsvkey . ']';

    $vresult = (isset($result) && is_array($result) && isset($result[$row_id]) ? $result[$row_id] : $result);
    if ($insert_mode && $row_id > 0) {
        $html_output .= '<input type="checkbox" checked="checked" name="insert_ignore_' . $row_id . '" id="insert_ignore_' . $row_id . '" />'
            .'<label for="insert_ignore_' . $row_id . '">' . __('Ignore') . '</label><br />' . "\n";
    }
    
    $html_output .= PMA_getHeadAndFootOfInsertRowTable($url_params)
        . '<tbody>';

    // Sets a multiplier used for input-field counts (as zero cannot be used, advance the counter plus one)
    $m_rows = $o_rows + 1;
    //store the default value for CharEditing
    $default_char_editing  = $cfg['CharEditing'];

    $odd_row = true;
    for ($i = 0; $i < $columns_cnt; $i++) {
        if (! isset($table_fields[$i]['processed'])) {
            $column = $table_fields[$i];
            $column = PMA_analyzeTableColumnsArray($column, $comments_map, $timestamp_seen);
        }
        
        $extracted_columnspec = PMA_extractColumnSpec($column['Type']);

        if (-1 === $column['len']) {
            $column['len'] = PMA_DBI_field_len($vresult, $i);
            // length is unknown for geometry fields, make enough space to edit very simple WKTs
            if (-1 === $column['len']) {
                $column['len'] = 30;
            }
        }
        //Call validation when the form submited...
        $unnullify_trigger = $chg_evt_handler . "=\"return verificationsAfterFieldChange('"
            . PMA_escapeJsString($column['Field_md5']) . "', '"
            . PMA_escapeJsString($jsvkey) . "','".$column['pma_type'] . "')\"";

        // Use an MD5 as an array index to avoid having special characters in the name atttibute (see bug #1746964 )
        $column_name_appendix = $vkey . '[' . $column['Field_md5'] . ']';

        if ($column['Type'] == 'datetime'
            && ! isset($column['Default'])
            && ! is_null($column['Default'])
            && ($insert_mode || ! isset($vrow[$column['Field']]))
        ) {
            // INSERT case or
            // UPDATE case with an NULL value
            $vrow[$column['Field']] = date('Y-m-d H:i:s', time());
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
        $gis_data_types = PMA_getGISDatatypes();

        // Prepares the field value
        $real_null_value = false;
        $special_chars_encoded = '';
        if (isset($vrow)) {
            // (we are editing)
            if (is_null($vrow[$column['Field']])) {
                $real_null_value = true;
                $vrow[$column['Field']] = '';
                $special_chars = '';
                $data = $vrow[$column['Field']];
            } elseif ($column['True_Type'] == 'bit') {
                $special_chars = PMA_printable_bit_value(
                    $vrow[$column['Field']], $extracted_columnspec['spec_in_brackets']
                );
            } elseif (in_array($column['True_Type'], $gis_data_types)) {
                // Convert gis data to Well Know Text format
                $vrow[$column['Field']] = PMA_asWKT($vrow[$column['Field']], true);
                $special_chars = htmlspecialchars($vrow[$column['Field']]);
            } else {
                // special binary "characters"
                if ($column['is_binary'] || ($column['is_blob'] && ! $cfg['ProtectBinary'])) {
                    if ($_SESSION['tmp_user_values']['display_binary_as_hex'] && $cfg['ShowFunctionFields']) {
                        $vrow[$column['Field']] = bin2hex($vrow[$column['Field']]);
                        $column['display_binary_as_hex'] = true;
                    } else {
                        $vrow[$column['Field']] = PMA_replace_binary_contents($vrow[$column['Field']]);
                    }
                } // end if
                $special_chars = htmlspecialchars($vrow[$column['Field']]);

                //We need to duplicate the first \n or otherwise we will lose
                //the first newline entered in a VARCHAR or TEXT column
                $special_chars_encoded = PMA_duplicateFirstNewline($special_chars);

                $data = $vrow[$column['Field']];
            } // end if... else...

            //when copying row, it is useful to empty auto-increment column to prevent duplicate key error
            if (isset($_REQUEST['default_action']) && $_REQUEST['default_action'] === 'insert') {
                if ($column['Key'] === 'PRI' && strpos($column['Extra'], 'auto_increment') !== false) {
                    $data = $special_chars_encoded = $special_chars = null;
                }
            }
            // If a timestamp field value is not included in an update
            // statement MySQL auto-update it to the current timestamp;
            // however, things have changed since MySQL 4.1, so
            // it's better to set a fields_prev in this situation
            $backup_field = '<input type="hidden" name="fields_prev'
                . $column_name_appendix . '" value="'
                . htmlspecialchars($vrow[$column['Field']]) . '" />';
        } else {
            // (we are inserting)
            // display default values
            if (! isset($column['Default'])) {
                $column['Default'] 		  = '';
                $real_null_value          = true;
                $data                     = '';
            } else {
                $data                     = $column['Default'];
            }

            if ($column['True_Type'] == 'bit') {
                $special_chars = PMA_convert_bit_default_value($column['Default']);
            } else {
                $special_chars = htmlspecialchars($column['Default']);
            }
            $backup_field = '';
            $special_chars_encoded = PMA_duplicateFirstNewline($special_chars);
            // this will select the UNHEX function while inserting
            if (($column['is_binary'] || ($column['is_blob'] && ! $cfg['ProtectBinary']))
                && (isset($_SESSION['tmp_user_values']['display_binary_as_hex'])
                && $_SESSION['tmp_user_values']['display_binary_as_hex'])
                && $cfg['ShowFunctionFields']
            ) {
                $column['display_binary_as_hex'] = true;
            }
        }

        $idindex = ($o_rows * $columns_cnt) + $i + 1;
        $tabindex = $idindex;

        // Get a list of data types that are not yet supported.
        $no_support_types = PMA_unsupportedDatatypes();

        // The function column
        // -------------------
        if ($cfg['ShowFunctionFields']) {
            $html_output .= PMA_getFunctionColumn($column, $is_upload, $column_name_appendix,
                $unnullify_trigger, $no_support_types, $tabindex_for_function,
                $tabindex, $idindex, $insert_mode);
        }

        // The null column
        // ---------------
        $foreignData = PMA_getForeignData($foreigners, $column['Field'], false, '', '');
        $html_output .= PMA_getNullColumn($column, $column_name_appendix, $real_null_value,
            $tabindex, $tabindex_for_null, $idindex, $vkey, $foreigners, $foreignData);

        // The value column (depends on type)
        // ----------------
        // See bug #1667887 for the reason why we don't use the maxlength
        // HTML attribute
        $html_output .= '        <td>' . "\n";
        // Will be used by js/tbl_change.js to set the default value
        // for the "Continue insertion" feature
        $html_output .= '<span class="default_value hide">' . $special_chars . '</span>';
        
        $html_output .= PMA_getValueColumn(
            $column, $backup_field, $column_name_appendix, $unnullify_trigger,$tabindex,
            $tabindex_for_value, $idindex, $data,$special_chars, $foreignData, $odd_row,
            $paramTableDbArray,$rownumber_param, $titles, $text_dir, $special_chars_encoded,
            $vkey,$is_upload, $biggest_max_file_size, $default_char_editing,
            $no_support_types, $gis_data_types, $extracted_columnspec);
        
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

//action panel
$html_output .= PMA_getActionsPanel($tabindex, $tabindex_for_value, $found_unique_key);
if ($biggest_max_file_size > 0) {
    $html_output .= '        ' . PMA_generateHiddenMaxFileSize($biggest_max_file_size) . "\n";
}
$html_output .= '</form>'; 
// end Insert/Edit form

if ($insert_mode) {
    //Continue insertion form
    $html_output .= PMA_getContinueInsertionForm($paramTableDbArray, $where_clause_array, $err_url);
}
echo $html_output;
/**
 * Displays the footer
 */
require 'libraries/footer.inc.php';

?>
