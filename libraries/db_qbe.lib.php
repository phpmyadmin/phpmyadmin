<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for handling Database QBE search
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

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

/**
 * Provides rows for criteria inputbox Insert/Delete options
 * with AND/OR relationship modification options 
 *
 * @param array   $criteria_column_count Number of criteria columns
 * @param integer $new_row_index         New row index if rows are added/deleted
 * @param integer $row_index             Row index
 * @param string  $criteriaColumnInsert  If a new criteria column is needed
 * @param string  $criteriaColumnDelete  If a criteria column is to be deleted
 * @param string  $realwidth             Largest column width found
 *
 * @return HTML table rows
 */
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
?>
