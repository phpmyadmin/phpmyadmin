<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'criteriaColumn',
    'criteriaShow',
    'criteriaSort'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

/**
 * Sets globals from $_POST patterns, for Or* variables 
 * (additional criteria lines)
 */

$post_patterns = array(
    '/^Or/i'
);
foreach (array_keys($_POST) as $post_key) {
    foreach ($post_patterns as $one_post_pattern) {
        if (preg_match($one_post_pattern, $post_key)) {
            $GLOBALS[$post_key] = $_POST[$post_key];
        }
    }
}
/**
 * Initialize some more global variables
 */
$GLOBALS['curField'] = array();
$GLOBALS['curSort'] = array();
$GLOBALS['curShow'] = array();
$GLOBALS['curCriteria'] = array();
$GLOBALS['curAndOrRow'] = array();
$GLOBALS['curAndOrCol'] = array();

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

$common_functions = PMA_CommonFunctions::getInstance();

/**
 * A query has been submitted -> (maybe) execute it
 */
$message_to_display = false;
if (isset($_REQUEST['submit_sql']) && ! empty($sql_query)) {
    if (! preg_match('@^SELECT@i', $sql_query)) {
        $message_to_display = true;
    } else {
        $goto      = 'db_sql.php';
        include 'sql.php';
        exit;
    }
}

$sub_part  = '_qbe';
require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_qbe.php';
$url_params['goto'] = 'db_qbe.php';
require 'libraries/db_info.inc.php';

if ($message_to_display) {
    PMA_Message::error(__('You have to choose at least one column to display'))->display();
}
unset($message_to_display);

/**
 * Initialize some variables
 */
$criteriaColumnCount = PMA_ifSetOr($_REQUEST['criteriaColumnCount'], 3, 'numeric');
$criteriaColumnAdd = PMA_ifSetOr($_REQUEST['criteriaColumnAdd'], 0, 'numeric');
$criteriaRowAdd = PMA_ifSetOr($_REQUEST['criteriaRowAdd'], 0, 'numeric');

$rows    = PMA_ifSetOr($_REQUEST['rows'],    0, 'numeric');
$criteriaColumnInsert = PMA_ifSetOr($_REQUEST['criteriaColumnInsert'], null, 'array');
$criteriaColumnDelete = PMA_ifSetOr($_REQUEST['criteriaColumnDelete'], null, 'array');

$prev_criteria = isset($_REQUEST['prev_criteria'])
    ? $_REQUEST['prev_criteria']
    : array();
$criteria = isset($_REQUEST['criteria'])
    ? $_REQUEST['criteria']
    : array_fill(0, $criteriaColumnCount, '');

$criteriaRowInsert = isset($_REQUEST['criteriaRowInsert'])
    ? $_REQUEST['criteriaRowInsert']
    : array_fill(0, $criteriaColumnCount, '');
$criteriaRowDelete = isset($_REQUEST['criteriaRowDelete'])
    ? $_REQUEST['criteriaRowDelete']
    : array_fill(0, $criteriaColumnCount, '');
$criteriaAndOrRow = isset($_REQUEST['criteriaAndOrRow'])
    ? $_REQUEST['criteriaAndOrRow']
    : array_fill(0, $criteriaColumnCount, '');
$criteriaAndOrColumn = isset($_REQUEST['criteriaAndOrColumn'])
    ? $_REQUEST['criteriaAndOrColumn']
    : array_fill(0, $criteriaColumnCount, '');

// minimum width
$form_column_width = 12;
$criteria_column_count = max($criteriaColumnCount + $criteriaColumnAdd, 0);
$criteria_row_count = max($rows + $criteriaRowAdd, 0);


// The tables list sent by a previously submitted form
if (PMA_isValid($_REQUEST['TableList'], 'array')) {
    foreach ($_REQUEST['TableList'] as $each_table) {
        $tbl_names[$each_table] = ' selected="selected"';
    }
} // end if


// this was a work in progress, deactivated for now
//$columns = PMA_DBI_get_columns_full($GLOBALS['db']);
//$tables  = PMA_DBI_get_columns_full($GLOBALS['db']);


/**
 * Prepares the form
 */
$tbl_result = PMA_DBI_query(
    'SHOW TABLES FROM ' . $common_functions->backquote($db) . ';',
    null, PMA_DBI_QUERY_STORE
);
$tbl_result_cnt = PMA_DBI_num_rows($tbl_result);
if (0 == $tbl_result_cnt) {
    PMA_Message::error(__('No tables found in database.'))->display();
    exit;
}

// The tables list gets from MySQL
while (list($tbl) = PMA_DBI_fetch_row($tbl_result)) {
    $fld_results = PMA_DBI_get_columns($db, $tbl);

    if (empty($tbl_names[$tbl]) && ! empty($_REQUEST['TableList'])) {
        $tbl_names[$tbl] = '';
    } else {
        $tbl_names[$tbl] = ' selected="selected"';
    } //  end if

    // The fields list per selected tables
    if ($tbl_names[$tbl] == ' selected="selected"') {
        $each_table = $common_functions->backquote($tbl);
        $fld[]  = $each_table . '.*';
        foreach ($fld_results as $each_field) {
            $each_field = $each_table . '.' . $common_functions->backquote($each_field['Field']);
            $fld[] = $each_field;

            // increase the width if necessary
            $form_column_width = max(strlen($each_field), $form_column_width);
        } // end foreach
    } // end if
} // end while
PMA_DBI_free_result($tbl_result);

// largest width found
$realwidth = $form_column_width . 'ex';


/**
 * Displays the Query by example form
 */

/**
 * Provides select options list containing column names
 *
 * @param array   $columns       All Column Names
 * @param integer $column_number Column Number (0,1,2) or more
 * @param string  $selected      Selected criteria column name
 *
 * @return HTML for select options
 */
function showColumnSelectCell($columns, $column_number, $selected = '')
{
    $html_output = '';
    $html_output .= '<td class="center">';
    $html_output .= '<select name="criteriaColumn[' . $column_number . ']" size="1">';
    $html_output .= '<option value="">&nbsp;</option>';
    foreach ($columns as $column) {
        $html_output .= '<option value="' . htmlspecialchars($column) . '"'
            . (($column === $selected) ? ' selected="selected"' : '') . '>'
            . str_replace(' ', '&nbsp;', htmlspecialchars($column)) . '</option>';
    }
    $html_output .= '</select>';
    $html_output .= '</td>';
    return $html_output;
}

/**
 * Provides select options list containing sort options (ASC/DESC)
 *
 * @param integer $column_number Column Number (0,1,2) or more
 * @param string  $realwidth     Largest column width found
 * @param string  $asc_selected  Selected criteria 'Ascending'
 * @param string  $desc_selected Selected criteria 'Descending'
 *
 * @return HTML for select options
 */
function getSortSelectCell($column_number, $realwidth, $asc_selected = '',
    $desc_selected = '')
{
    $html_output = '<td class="center">';
    $html_output .= '<select style="width: ' . $realwidth
        . '" name="criteriaSort[' . $column_number . ']" size="1">';
    $html_output .= '<option value="">&nbsp;</option>';
    $html_output .= '<option value="ASC"' . $asc_selected . '>' . __('Ascending')
        . '</option>';
    $html_output .= '<option value="DESC"' . $desc_selected . '>' . __('Descending')
        . '</option>';
    $html_output .= '</select>';
    $html_output .= '</td>';
    return $html_output;
}

/**
 * Provides search form's row containing column select options
 *
 * @param array   $criteria_column_count Number of criteria columns
 * @param integer $columns               All column names
 * @param string  $criteriaColumnInsert  If a new criteria column is needed
 * @param string  $criteriaColumnDelete               If a criteria column is to be deleted
 *
 * @return HTML for search table's row
 */
function PMA_dbQbegetColumnNamesRow(
    $criteria_column_count, $columns, $criteriaColumnInsert = null, $criteriaColumnDelete = null
) {
    $html_output = '<tr class="odd noclick">';
    $html_output .= '<th>' . __('Column') . ':</th>';
    $new_column_count = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++)
    {
        if (isset($criteriaColumnInsert[$column_index]) && $criteriaColumnInsert[$column_index] == 'on') {
            $html_output .= showColumnSelectCell($columns, $new_column_count);
            $new_column_count++;
        }
        if (! empty($criteriaColumnDelete) && isset($criteriaColumnDelete[$column_index]) && $criteriaColumnDelete[$column_index] == 'on') {
            continue;
        }
        $selected = '';
        if (isset($_REQUEST['criteriaColumn'][$column_index])) {
            $selected = $_REQUEST['criteriaColumn'][$column_index];
            $GLOBALS['curField'][$new_column_count] = $_REQUEST['criteriaColumn'][$column_index];
        }
        $html_output .= showColumnSelectCell($columns, $new_column_count, $selected);
        $new_column_count++;
    } // end for
    $html_output .= '</tr>';
    return $html_output;
}

/**
 * Provides search form's row containing sort(ASC/DESC) select options
 *
 * @param array  $criteria_column_count Number of criteria columns
 * @param string $realwidth             Largest column width found
 * @param string $criteriaColumnInsert  If a new criteria column is needed
 * @param string $criteriaColumnDelete               If a criteria column is to be deleted
 *
 * @return HTML for search table's row
 */
function PMA_dbQbegetSortRow(
    $criteria_column_count, $realwidth, $criteriaColumnInsert = null, $criteriaColumnDelete = null
) {
    $html_output = '<tr class="even noclick">';
    $html_output .= '<th>' . __('Sort') . ':</th>';
    $new_column_count = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++)
    {
        if (! empty($criteriaColumnInsert) && isset($criteriaColumnInsert[$column_index]) && $criteriaColumnInsert[$column_index] == 'on') {
            $html_output .= getSortSelectCell($new_column_count, $realwidth);
            $new_column_count++;
        } // end if

        if (! empty($criteriaColumnDelete) && isset($criteriaColumnDelete[$column_index]) && $criteriaColumnDelete[$column_index] == 'on') {
            continue;
        }
        // If they have chosen all fields using the * selector,
        // then sorting is not available, Fix for Bug #570698
        if (isset($_REQUEST['criteriaSort'][$column_index]) && isset($_REQUEST['criteriaColumn'][$column_index])
            && substr($_REQUEST['criteriaColumn'][$column_index], -2) == '.*'
        ) {
            $_REQUEST['criteriaSort'][$column_index] = '';
        } //end if
        // Set asc_selected
        if (isset($_REQUEST['criteriaSort'][$column_index]) && $_REQUEST['criteriaSort'][$column_index] == 'ASC') {
            $GLOBALS['curSort'][$new_column_count] = $_REQUEST['criteriaSort'][$column_index];
            $asc_selected = ' selected="selected"';
        } else {
            $asc_selected = '';
        } // end if
        // Set desc selected
        if (isset($_REQUEST['criteriaSort'][$column_index]) && $_REQUEST['criteriaSort'][$column_index] == 'DESC') {
            $GLOBALS['curSort'][$new_column_count] = $_REQUEST['criteriaSort'][$column_index];
            $desc_selected = ' selected="selected"';
        } else {
            $desc_selected = '';
        } // end if
        $html_output .= getSortSelectCell(
            $new_column_count, $realwidth, $asc_selected, $desc_selected
        );
        $new_column_count++;
    } // end for
    $html_output .= '</tr>';
    return $html_output;
}

/**
 * Provides search form's row containing SHOW checkboxes
 *
 * @param array  $criteria_column_count Number of criteria columns
 * @param string $criteriaColumnInsert  If a new criteria column is needed
 * @param string $criteriaColumnDelete               If a criteria column is to be deleted
 *
 * @return HTML for search table's row
 */
function PMA_dbQbegetShowRow(
    $criteria_column_count, $criteriaColumnInsert = null, $criteriaColumnDelete = null
) {
    $html_output = '<tr class="odd noclick">';
    $html_output .= '<th>' . __('Show') . ':</th>';
    $new_column_count = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++)
    {
        if (! empty($criteriaColumnInsert) && isset($criteriaColumnInsert[$column_index]) && $criteriaColumnInsert[$column_index] == 'on') {
            $html_output .= '<td class="center">';
            $html_output .= '<input type="checkbox" name="criteriaShow[' . $new_column_count . ']" />';
            $html_output .= '</td>';
            $new_column_count++;
        } // end if
        if (! empty($criteriaColumnDelete) && isset($criteriaColumnDelete[$column_index]) && $criteriaColumnDelete[$column_index] == 'on') {
            continue;
        }
        if (isset($_REQUEST['criteriaShow'][$column_index])) {
            $checked_options = ' checked="checked"';
            $GLOBALS['curShow'][$new_column_count] = $_REQUEST['criteriaShow'][$column_index];
        } else {
            $checked_options = '';
        }
        $html_output .= '<td class="center">';
        $html_output .= '<input type="checkbox" name="criteriaShow[' . $new_column_count . ']"'
            . $checked_options . ' />';
        $html_output .= '</td>';
        $new_column_count++;
    } // end for
    $html_output .= '</tr>';
    return $html_output;
}

/**
 * Provides search form's row containing criteria Inputboxes
 *
 * @param array  $criteria_column_count Number of criteria columns
 * @param string $realwidth             Largest column width found
 * @param string $criteria              Already Filled criteria
 * @param string $prev_criteria         Previously filled criteria(hidden form field)
 * @param string $criteriaColumnInsert  If a new criteria column is needed
 * @param string $criteriaColumnDelete               If a criteria column is to be deleted
 *
 * @return HTML for search table's row
 */
function PMA_dbQbegetCriteriaInputboxRow(
    $criteria_column_count, $realwidth, $criteria, $prev_criteria,
    $criteriaColumnInsert = null, $criteriaColumnDelete = null
) {
    $html_output = '<tr class="even noclick">';
    $html_output .= '<th>' . __('Criteria') . ':</th>';
    $new_column_count = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++)
    {
        if (! empty($criteriaColumnInsert) && isset($criteriaColumnInsert[$column_index]) && $criteriaColumnInsert[$column_index] == 'on') {
            $html_output .= '<td class="center">';
            $html_output .= '<input type="text" name="criteria[' . $new_column_count . ']"'
                . ' value="" class="textfield" style="width: ' . $realwidth
                . '" size="20" />';
            $html_output .= '</td>';
            $new_column_count++;
        } // end if
        if (! empty($criteriaColumnDelete) && isset($criteriaColumnDelete[$column_index]) && $criteriaColumnDelete[$column_index] == 'on') {
            continue;
        }
        if (isset($criteria[$column_index])) {
            $tmp_criteria = $criteria[$column_index];
        }
        if ((empty($prev_criteria) || ! isset($prev_criteria[$column_index]))
            || $prev_criteria[$column_index] != htmlspecialchars($tmp_criteria)
        ) {
            $GLOBALS['curCriteria'][$new_column_count]   = $tmp_criteria;
        } else {
            $GLOBALS['curCriteria'][$new_column_count]   = $prev_criteria[$column_index];
        }
        $html_output .= '<td class="center">';
        $html_output .= '<input type="hidden" name="prev_criteria[' . $new_column_count . ']"'
            . ' value="' . htmlspecialchars($GLOBALS['curCriteria'][$new_column_count]) . '" />';
        $html_output .= '<input type="text" name="criteria[' . $new_column_count . ']"'
        . ' value="' . htmlspecialchars($tmp_criteria) . '" class="textfield"'
        . ' style="width: ' . $realwidth . '" size="20" />';
        $html_output .= '</td>';
        $new_column_count++;
    } // end for
    $html_output .= '</tr>';
    return $html_output;
}

/**
 * Provides footer options for adding/deleting row/columns
 *
 * @param string $type Whether row or column
 *
 * @return HTML for footer options
 */
function PMA_dbQbeGetFootersOptions($type)
{
    $html_output = '<div class="floatleft">';
    $html_output .= (($type == 'row')
        ? __('Add/Delete criteria rows') : __('Add/Delete columns'));
    $html_output .= ':<select size="1" name="'
        . (($type == 'row') ? 'criteriaRowAdd' : 'criteriaColumnAdd') . '">';
    $html_output .= '<option value="-3">-3</option>';
    $html_output .= '<option value="-2">-2</option>';
    $html_output .= '<option value="-1">-1</option>';
    $html_output .= '<option value="0" selected="selected">0</option>';
    $html_output .= '<option value="1">1</option>';
    $html_output .= '<option value="2">2</option>';
    $html_output .= '<option value="3">3</option>';
    $html_output .= '</select>';
    $html_output .= '</div>';
    return $html_output;
}

/**
 * Provides search form table's footer options
 *
 * @return HTML for table footer
 */
function PMA_dbQbeGetTableFooters()
{
    $html_output = '<fieldset class="tblFooters">';
    $html_output .= PMA_dbQbeGetFootersOptions("row");
    $html_output .= PMA_dbQbeGetFootersOptions("column");
    $html_output .= '<div class="floatleft">';
    $html_output .= '<input type="submit" name="modify"'
        . 'value="' . __('Update Query') . '" />';
    $html_output .= '</div>';
    $html_output .= '</fieldset>';
    return $html_output;
}

/**
 * Provides a select list of database tables
 *
 * @param array $table_names Names of all the tables
 *
 * @return HTML for table select list
 */
function PMA_dbQbeGetTablesList($table_names)
{
    $html_output = '<div class="floatleft">';
    $html_output .= '<fieldset>';
    $html_output .= '<legend>' . __('Use Tables') . '</legend>';
    // Build the options list for each table name
    $options = '';
    $numTableListOptions = 0;
    foreach ($table_names as $key => $val) {
        $options .= '<option value="' . htmlspecialchars($key) . '"' . $val . '>'
            . (str_replace(' ', '&nbsp;', htmlspecialchars($key))) . '</option>';
        $numTableListOptions++;
    }
    $html_output .= '<select name="TableList[]" multiple="multiple" id="listTable"'
        . ' size="' . (($numTableListOptions > 30) ? '15' : '7') . '">';
    $html_output .= $options;
    $html_output .= '</select>';
    $html_output .= '</fieldset>';
    $html_output .= '<fieldset class="tblFooters">';
    $html_output .= '<input type="submit" name="modify" value="'
        . __('Update Query') . '" />';
    $html_output .= '</fieldset>';
    $html_output .= '</div>';
    return $html_output;
}

/**
 * Provides And/Or modification cell along with Insert/Delete options
 * (For modifying search form's table columns)
 *
 * @param integer $column_number Column Number (0,1,2) or more
 * @param array   $selected      Selected criteria column name
 *
 * @return HTML for modification cell
 */
function PMA_dbQbeGetAndOrColCell($column_number, $selected = null)
{
    $html_output = '<td class="center">';
    $html_output .= '<strong>' . __('Or') . ':</strong>';
    $html_output .= '<input type="radio" name="criteriaAndOrColumn[' . $column_number . ']"'
        . ' value="or"' . $selected['or'] . ' />';
    $html_output .= '&nbsp;&nbsp;<strong>' . __('And') . ':</strong>';
    $html_output .= '<input type="radio" name="criteriaAndOrColumn[' . $column_number . ']"'
        . ' value="and"' . $selected['and'] . ' />';
    $html_output .= '<br />' . __('Ins');
    $html_output .= '<input type="checkbox" name="criteriaColumnInsert[' . $column_number . ']" />';
    $html_output .= '&nbsp;&nbsp;' . __('Del');
    $html_output .= '<input type="checkbox" name="criteriaColumnDelete[' . $column_number . ']" />';
    $html_output .= '</td>';
    return $html_output;
}

/**
 * Provides search form's row containing column modifications options
 * (For modifying search form's table columns)
 *
 * @param array  $criteria_column_count Number of criteria columns
 * @param string $realwidth             Largest column width found
 * @param string $criteria              Already Filled criteria
 * @param string $prev_criteria         Previously filled criteria(hidden form field)
 * @param string $criteriaColumnInsert  If a new criteria column is needed
 * @param string $criteriaColumnDelete  If a criteria column is to be deleted
 *
 * @return HTML for search table's row
 */
function PMA_dbQbeGetModifyColumnsRow($criteria_column_count, $criteriaAndOrColumn,
    $criteriaColumnInsert = null, $criteriaColumnDelete = null
) {
    $html_output = '<tr class="even noclick">';
    $html_output .= '<th>' . __('Modify') . ':</th>';
    $new_column_count = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++) {
        if (! empty($criteriaColumnInsert)
            && isset($criteriaColumnInsert[$column_index])
            && $criteriaColumnInsert[$column_index] == 'on'
        ) {
            $html_output .= PMA_dbQbeGetAndOrColCell($new_column_count);
            $new_column_count++;
        } // end if

        if (! empty($criteriaColumnDelete) && isset($criteriaColumnDelete[$column_index]) && $criteriaColumnDelete[$column_index] == 'on') {
            continue;
        }

        if (isset($criteriaAndOrColumn[$column_index])) {
            $GLOBALS['curAndOrCol'][$new_column_count] = $criteriaAndOrColumn[$column_index];
        }
        if (isset($criteriaAndOrColumn[$column_index]) && $criteriaAndOrColumn[$column_index] == 'or') {
            $checked_options['or']  = ' checked="checked"';
            $checked_options['and'] = '';
        } else {
            $checked_options['and'] = ' checked="checked"';
            $checked_options['or']  = '';
        }
        $html_output .= PMA_dbQbeGetAndOrColCell($new_column_count, $checked_options);
        $new_column_count++;
    } // end for
    $html_output .= '</tr>';
    return $html_output;
}

/**
 * Provides Insert/Delete options for criteria inputbox
 * with AND/OR relationship modification options
 *
 * @param integer $row_index       Number of criteria row
 * @param string  $checked_options If checked
 *
 * @return HTML
 */
function PMA_dbQbeGetInsDelAndOrCell($row_index, $checked_options) {
    $html_output = '<td class="' . $GLOBALS['cell_align_right'] . ' nowrap">';
    $html_output .= '<!-- Row controls -->';
    $html_output .= '<table class="nospacing nopadding">';
    $html_output .= '<tr>';
    $html_output .= '<td class="' . $GLOBALS['cell_align_right'] . ' nowrap">';
    $html_output .= '<small>' . __('Ins') . ':</small>';
    $html_output .= '<input type="checkbox"'
        . ' name="criteriaRowInsert[' . $row_index . ']" />';
    $html_output .= '</td>';
    $html_output .= '<td class="' . $GLOBALS['cell_align_right'] . '">';
    $html_output .= '<strong>' . __('And') . ':</strong>';
    $html_output .= '</td>';
    $html_output .= '<td>';
    $html_output .= '<input type="radio"'
        . ' name="criteriaAndOrRow[' . $row_index . ']" value="and"'
        . $checked_options['and'] . ' />';
    $html_output .= '</td>';
    $html_output .= '</tr>';
    $html_output .= '<tr>';
    $html_output .= '<td class="' . $GLOBALS['cell_align_right'] . ' nowrap">';
    $html_output .= '<small>' . __('Del') . ':</small>';
    $html_output .= '<input type="checkbox"'
        . ' name="criteriaRowDelete[' . $row_index . ']" />';
    $html_output .= '</td>';
    $html_output .= '<td class="' . $GLOBALS['cell_align_right'] . '">';
    $html_output .= '<strong>' . __('Or') . ':</strong>';
    $html_output .= '</td>';
    $html_output .= '<td>';
    $html_output .= '<input type="radio" name="criteriaAndOrRow[' . $row_index . ']"'
        . ' value="or"' . $checked_options['or'] . ' />';
    $html_output .= '</td>';
    $html_output .= '</tr>';
    $html_output .= '</table>';
    $html_output .= '</td>';
    return $html_output;
}

function PMA_dbQbeGetInputboxRow($criteria_column_count, $new_row_index, $row_index,
    $criteriaColumnInsert, $criteriaColumnDelete, $realwidth
) {
    $html_output = '';
    $new_column_count = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++) {
        if (! empty($criteriaColumnInsert)
            && isset($criteriaColumnInsert[$column_index])
            && $criteriaColumnInsert[$column_index] == 'on'
        ) {
            $or = 'Or' . $new_row_index . '[' . $new_column_count . ']';
            $html_output .= '<td class="center">';
            $html_output .= '<input type="text" name="Or' . $or . '" class="textfield"'
                . ' style="width: ' . $realwidth . '" size="20" />';
            $html_output .= '</td>';
            $new_column_count++;
        } // end if
        if (! empty($criteriaColumnDelete)
            && isset($criteriaColumnDelete[$column_index])
            && $criteriaColumnDelete[$column_index] == 'on'
        ) {
            continue;
        }
        $or = 'Or' . $row_index;
        if (! isset(${$or})) {
            ${$or} = '';
        }
        if (! empty(${$or}) && isset(${$or}[$column_index])) {
            $tmp_or = ${$or}[$column_index];
        } else {
            $tmp_or     = '';
        }
        $html_output .= '<td class="center">';
        $html_output .= '<input type="text" name="Or' . $new_row_index . '[' . $new_column_count . ']' . '"'
            . ' value="' . htmlspecialchars($tmp_or) . '" class="textfield"'
            . ' style="width: ' . $realwidth . '" size="20" />';
        $html_output .= '</td>';
        if (! empty(${$or}) && isset(${$or}[$column_index])) {
            $GLOBALS[${'cur' . $or}][$new_column_count] = ${$or}[$column_index];
        }
        $new_column_count++;
    } // end for
    return $html_output;
}

/**
 * Provides rows for criteria inputbox Insert/Delete options
 * with AND/OR relationship modification options 
 *
 * @param integer $criteria_row_count    Number of criteria rows
 * @param array   $criteria_column_count Number of criteria columns
 * @param string  $realwidth             Largest column width found
 * @param string  $criteriaColumnInsert  If a new criteria column is needed
 * @param string  $criteriaColumnDelete  If a criteria column is to be deleted
 * @param string  $criteriaAndOrRow      If AND or OR is to be checked
 *
 * @return HTML table rows
 */
function PMA_dbQbeGetInsDelAndOrCriteriaRows($criteria_row_count,
    $criteria_column_count, $realwidth, $criteriaColumnInsert, $criteriaColumnDelete,
    $criteriaAndOrRow
) {
    $html_output = '';
    $new_row_count = 0;
    $odd_row = true;
    for ($row_index = 0; $row_index <= $criteria_row_count; $row_index++) {
        if (isset($criteriaRowInsert[$row_index]) && $criteriaRowInsert[$row_index] == 'on') {
            $checked_options['or']  = ' checked="checked"';
            $checked_options['and'] = '';
            $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . ' noclick">';
            $html_output .= PMA_dbQbeGetInsDelAndOrCell($new_row_count, $checked_options);
            $html_output .= PMA_dbQbeGetInputboxRow(
                $criteria_column_count, $new_row_count, $row_index, $criteriaColumnInsert,
                $criteriaColumnDelete, $realwidth
            );
            $new_row_count++;
            $html_output .= '</tr>';
            $odd_row =! $odd_row;
        } // end if
        if (isset($criteriaRowDelete[$row_index]) && $criteriaRowDelete[$row_index] == 'on') {
            continue;
        }
        if (isset($criteriaAndOrRow[$row_index])) {
            $GLOBALS['curAndOrRow'][$new_row_count] = $criteriaAndOrRow[$row_index];
        }
        if (isset($criteriaAndOrRow[$row_index]) && $criteriaAndOrRow[$row_index] == 'and') {
            $checked_options['and'] =  ' checked="checked"';
            $checked_options['or']  =  '';
        } else {
            $checked_options['or']  =  ' checked="checked"';
            $checked_options['and'] =  '';
        }
        $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . ' noclick">';
        $html_output .= PMA_dbQbeGetInsDelAndOrCell($new_row_count, $checked_options);
        $html_output .= PMA_dbQbeGetInputboxRow(
            $criteria_column_count, $new_row_count, $row_index, $criteriaColumnInsert,
            $criteriaColumnDelete, $realwidth
        );
        $new_row_count++;
        $html_output .= '</tr>';
        $odd_row =! $odd_row;
    } // end for
    return $html_output;
}

/**
 * Provides SELECT clause for building SQL query
 *
 * @param array  $criteria_column_count Number of criteria columns
 *
 * @return Select clause
 */
function PMA_dbQbeGetSelectClause($criteria_column_count){
    $select_clause = '';
    $select_clauses = array();
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++) {
        if (! empty($GLOBALS['curField'][$column_index])
            && isset($GLOBALS['curShow'][$column_index])
            && $GLOBALS['curShow'][$column_index] == 'on')
        {
            $select_clauses[] = $GLOBALS['curField'][$column_index];
        }
    } // end for
    if ($select_clauses) {
        $select_clause = 'SELECT '
            . htmlspecialchars(implode(", ", $select_clauses)) . "\n";
    }
    return $select_clause;
}

/**
 * Provides WHERE clause for building SQL query
 *
 * @param array  $criteria_column_count Number of criteria columns
 * @param array  $criteria_row_count    Number of criteria rows
 *
 * @return Where clause
 */
function PMA_dbQbeGetWhereClause($criteria_column_count, $criteria_row_count) {
    $where_clause = '';
    $criteria_cnt = 0;
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++) {
        if (! empty($GLOBALS['curField'][$column_index])
            && ! empty($GLOBALS['curCriteria'][$column_index])
            && $column_index
            && isset($last_where)
            && isset($GLOBALS['curAndOrCol'])) {
            $where_clause .= ' ' . strtoupper($GLOBALS['curAndOrCol'][$last_where]) . ' ';
        }
        if (! empty($GLOBALS['curField'][$column_index]) && ! empty($GLOBALS['curCriteria'][$column_index])) {
            $where_clause .= '(' . $GLOBALS['curField'][$column_index] . ' '
                . $GLOBALS['curCriteria'][$column_index] . ')';
            $last_where = $column_index;
            $criteria_cnt++;
        }
    } // end for
    if ($criteria_cnt > 1) {
        $where_clause = '(' . $where_clause . ')';
    }
    // OR rows ${'cur' . $or}[$column_index]
    if (! isset($GLOBALS['curAndOrRow'])) {
        $GLOBALS['curAndOrRow'] = array();
    }
    for ($row_index = 0; $row_index <= $criteria_row_count; $row_index++) {
        $criteria_cnt = 0;
        $qry_orwhere = '';
        $last_orwhere = '';
        for ($column_index = 0; $column_index < $criteria_column_count; $column_index++) {
            if (! empty($GLOBALS['curField'][$column_index]) && ! empty(${'curOr' . $row_index}[$column_index]) && $column_index) {
                $qry_orwhere .= ' ' . strtoupper($GLOBALS['curAndOrCol'][$last_orwhere]) . ' ';
            }
            if (! empty($GLOBALS['curField'][$column_index]) && ! empty(${'curOr' . $row_index}[$column_index])) {
                $qry_orwhere .= '(' . $GLOBALS['curField'][$column_index]
                    .  ' '
                    .  ${'curOr' . $row_index}[$column_index]
                    .  ')';
                $last_orwhere = $column_index;
                $criteria_cnt++;
            }
        } // end for
        if ($criteria_cnt > 1) {
            $qry_orwhere      = '(' . $qry_orwhere . ')';
        }
        if (! empty($qry_orwhere)) {
            $where_clause .= "\n"
                .  strtoupper(isset($GLOBALS['curAndOrRow'][$row_index]) ? $GLOBALS['curAndOrRow'][$row_index] . ' ' : '')
                .  $qry_orwhere;
        } // end if
    } // end for

    if (! empty($where_clause) && $where_clause != '()') {
        $where_clause = 'WHERE ' . htmlspecialchars($where_clause) . "\n";
    } // end if
    return $where_clause;
}

/**
 * Provides ORDER BY clause for building SQL query
 *
 * @param array  $criteria_column_count Number of criteria columns
 *
 * @return Order By clause
 */
function PMA_dbQbeGetOrderByClause($criteria_column_count)
{
    $orderby_clause = '';
    $orderby_clauses = array();
    for ($column_index = 0; $column_index < $criteria_column_count; $column_index++)
    {
        // if all columns are chosen with * selector, then sorting isn't available
        // Fix for Bug #570698
        if (! empty($GLOBALS['curField'][$column_index])
            && ! empty($GLOBALS['curSort'][$column_index])
        ) {
            if (substr($GLOBALS['curField'][$column_index], -2) == '.*') {
                continue;
            }
            $orderby_clauses[] = $GLOBALS['curField'][$column_index] . ' '
                . $GLOBALS['curSort'][$column_index];
        }
    } // end for
    if ($orderby_clauses) {
        $orderby_clause = 'ORDER BY '
            . htmlspecialchars(implode(", ", $orderby_clauses)) . "\n";
    }
    return $orderby_clause;
}

if ($cfgRelation['designerwork']) {
    $url = 'pmd_general.php' . PMA_generate_common_url(
        array_merge(
            $url_params,
            array('query' => 1)
        )
    );
    PMA_Message::notice(
        sprintf(
            __('Switch to %svisual builder%s'),
            '<a href="' . $url . '">',
            '</a>'
        )
    )->display();
}
?>
<form action="db_qbe.php" method="post">
<fieldset>
<table class="data" style="width: 100%;">
<?php
echo PMA_dbQbegetColumnNamesRow(
    $criteria_column_count, $fld, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbegetSortRow(
    $criteria_column_count, $realwidth, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbegetShowRow(
    $criteria_column_count, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbegetCriteriaInputboxRow(
    $criteria_column_count, $realwidth, $criteria, $prev_criteria, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbeGetInsDelAndOrCriteriaRows($criteria_row_count, $criteria_column_count, $realwidth,
    $criteriaColumnInsert, $criteriaColumnDelete, $criteriaAndOrRow
);
echo PMA_dbQbeGetModifyColumnsRow(
    $criteria_column_count, $criteriaAndOrColumn, $criteriaColumnInsert, $criteriaColumnDelete 
);
?>
</table>
<?php
$new_row_count--;
$url_params['db']       = $db;
$url_params['criteriaColumnCount']  = $new_column_count;
$url_params['rows']     = $new_row_count;
echo PMA_generate_common_hidden_inputs($url_params);
?>
</fieldset>

<?php
echo PMA_dbQbeGetTableFooters();
echo PMA_dbQbeGetTablesList($tbl_names);
?>

<div class="floatleft">
    <fieldset>
        <legend><?php echo sprintf(__('SQL query on database <b>%s</b>:'), $common_functions->getDbLink($db)); ?>
            </legend>
        <textarea cols="80" name="sql_query" id="textSqlquery"
            rows="<?php echo ($numTableListOptions > 30) ? '15' : '7'; ?>"
            dir="<?php echo $text_dir; ?>">
<?php
// 1. SELECT
echo PMA_dbQbeGetSelectClause($criteria_column_count);
// 2. FROM

// Create LEFT JOINS out of Relations
// If we can use Relations we could make some left joins.
// First find out if relations are available in this database.

// First we need the really needed Tables - those in TableList might still be
// all Tables.
if (isset($criteriaColumn) && count($criteriaColumn) > 0) {
    // Initialize some variables
    $tab_all    = array();
    $col_all    = array();
    $tab_wher   = array();
    $tab_know   = array();
    $tab_left   = array();
    $col_where  = array();
    $fromclause = '';

    // We only start this if we have fields, otherwise it would be dumb
    foreach ($criteriaColumn as $value) {
        $parts             = explode('.', $value);
        if (! empty($parts[0]) && ! empty($parts[1])) {
            $tab_raw       = $parts[0];
            $tab           = str_replace('`', '', $tab_raw);
            $tab_all[$tab] = $tab;

            $col_raw       = $parts[1];
            $col_all[]     = $tab . '.' . str_replace('`', '', $col_raw);
        }
    } // end while

    // Check 'where' clauses
    if ($cfgRelation['relwork'] && count($tab_all) > 0) {
        // Now we need all tables that we have in the where clause
        $crit_cnt         = count($criteria);
        for ($column_index = 0; $column_index < $crit_cnt; $column_index++) {
            $curr_tab     = explode('.', $criteriaColumn[$column_index]);
            if (! empty($curr_tab[0]) && ! empty($curr_tab[1])) {
                $tab_raw  = $curr_tab[0];
                $tab      = str_replace('`', '', $tab_raw);

                $col_raw  = $curr_tab[1];
                $col1     = str_replace('`', '', $col_raw);
                $col1     = $tab . '.' . $col1;
                // Now we know that our array has the same numbers as $criteria
                // we can check which of our columns has a where clause
                if (! empty($criteria[$column_index])) {
                    if (substr($criteria[$column_index], 0, 1) == '=' || stristr($criteria[$column_index], 'is')) {
                        $col_where[$criteria_column_count] = $col1;
                        $tab_wher[$tab]  = $tab;
                    }
                } // end if
            } // end if
        } // end for

        // Cleans temp vars w/o further use
        unset($tab_raw);
        unset($col_raw);
        unset($col1);

        if (count($tab_wher) == 1) {
            // If there is exactly one column that has a decent where-clause
            // we will just use this
            $master = key($tab_wher);
        } else {
            // Now let's find out which of the tables has an index
            // (When the control user is the same as the normal user
            // because he is using one of his databases as pmadb,
            // the last db selected is not always the one where we need to work)
            PMA_DBI_select_db($db);

            foreach ($tab_all as $tab) {
                $indexes = PMA_DBI_get_table_indexes($db, $tab);
                foreach ($indexes as $ind) {
                    $col1 = $tab . '.' . $ind['Column_name'];
                    if (isset($col_all[$col1])) {
                        if ($ind['Non_unique'] == 0) {
                            if (isset($col_where[$col1])) {
                                $col_unique[$col1] = 'Y';
                            } else {
                                $col_unique[$col1] = 'N';
                            }
                        } else {
                            if (isset($col_where[$col1])) {
                                $col_index[$col1] = 'Y';
                            } else {
                                $col_index[$col1] = 'N';
                            }
                        }
                    }
                } // end while (each col of tab)
            } // end while (each tab)
            // now we want to find the best.
            if (isset($col_unique) && count($col_unique) > 0) {
                $col_cand = $col_unique;
                $needsort = 1;
            } elseif (isset($col_index) && count($col_index) > 0) {
                $col_cand = $col_index;
                $needsort = 1;
            } elseif (isset($col_where) && count($col_where) > 0) {
                $col_cand = $tab_wher;
                $needsort = 0;
            } else {
                $col_cand = $tab_all;
                $needsort = 0;
            }

            // If we came up with $col_unique (very good) or $col_index (still
            // good) as $col_cand we want to check if we have any 'Y' there
            // (that would mean that they were also found in the whereclauses
            // which would be great). if yes, we take only those
            if ($needsort == 1) {
                foreach ($col_cand as $criteria_column_count => $is_where) {
                    $tab           = explode('.', $criteria_column_count);
                    $tab           = $tab[0];
                    if ($is_where == 'Y') {
                        $vg[$criteria_column_count]  = $tab;
                    } else {
                        $sg[$criteria_column_count]  = $tab;
                    }
                }
                if (isset($vg)) {
                    $col_cand      = $vg;
                    // Candidates restricted in index+where
                } else {
                    $col_cand      = $sg;
                    // None of the candidates where in a where-clause
                }
            }

            // If our array of candidates has more than one member we'll just
            // find the smallest table.
            // Of course the actual query would be faster if we check for
            // the Criteria which gives the smallest result set in its table,
            // but it would take too much time to check this
            if (count($col_cand) > 1) {
                // Of course we only want to check each table once
                $checked_tables = $col_cand;
                foreach ($col_cand as $tab) {
                    if ($checked_tables[$tab] != 1) {
                        $tsize[$tab] = PMA_Table::countRecords($db, $tab, false);
                        $checked_tables[$tab] = 1;
                    }
                    $csize[$tab] = $tsize[$tab];
                }
                asort($csize);
                reset($csize);
                $master = key($csize); // Smallest
            } else {
                reset($col_cand);
                $master = current($col_cand); // Only one single candidate
            }
        } // end if (exactly one where clause)

        $tab_left = $tab_all;
        unset($tab_left[$master]);
        $tab_know[$master] = $master;

        $run   = 0;
        $emerg = '';
        while (count($tab_left) > 0) {
            if ($run % 2 == 0) {
                PMA_getRelatives('master');
            } else {
                PMA_getRelatives('foreign');
            }
            $run++;
            if ($run > 5) {

                foreach ($tab_left as $tab) {
                    $emerg .= ', ' . $common_functions->backquote($tab);
                    unset($tab_left[$tab]);
                }
            }
        } // end while
        $qry_from = $common_functions->backquote($master) . $emerg . $fromclause;
    } // end if ($cfgRelation['relwork'] && count($tab_all) > 0)

} // end count($criteriaColumn) > 0

// In case relations are not defined, just generate the FROM clause
// from the list of tables, however we don't generate any JOIN

if (empty($qry_from) && isset($tab_all)) {
    $qry_from = implode(', ', $tab_all);
}
// Now let's see what we got
if (! empty($qry_from)) {
    echo 'FROM ' . htmlspecialchars($qry_from) . "\n";
}

// 3. WHERE
echo PMA_dbQbeGetWhereClause($criteria_column_count, $criteria_row_count);

// 4. ORDER BY
echo PMA_dbQbeGetOrderByClause($criteria_column_count);
?>
    </textarea>
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" name="submit_sql" value="<?php echo __('Submit Query'); ?>" />
    </fieldset>
</div>
</form>

