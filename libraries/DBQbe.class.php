<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles DB QBE search
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class to handle database QBE search
 *
 * @package PhpMyAdmin
 */
class PMA_DbQbe
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;
    /**
     * Table Names (selected/non-selected)
     *
     * @access private
     * @var array
     */
    private $_criteriaTables;
    /**
     * Column Names
     *
     * @access private
     * @var array
     */
    private $_columnNames;
    /**
     * Number of columns
     *
     * @access private
     * @var integer
     */
    private $_criteria_column_count;
    /**
     * Number of Rows
     *
     * @access private
     * @var integer
     */
    private $_criteria_row_count;
    /**
     * Whether to insert a new column
     *
     * @access private
     * @var array
     */
    private $_criteriaColumnInsert;
    /**
     * Whether to delete a column
     *
     * @access private
     * @var array
     */
    private $_criteriaColumnDelete;
    /**
     * Whether to insert a new row
     *
     * @access private
     * @var array
     */
    private $_criteriaRowInsert;
    /**
     * Already set criteria values
     *
     * @access private
     * @var array
     */
    private $_criteria;
    /**
     * Previously set criteria values
     *
     * @access private
     * @var array
     */
    private $_prev_criteria;
    /**
     * AND/OR relation b/w criteria columns
     *
     * @access private
     * @var array
     */
    private $_criteriaAndOrColumn;
    /**
     * AND/OR relation b/w criteria rows
     *
     * @access private
     * @var array
     */
    private $_criteriaAndOrRow;
    /**
     * Larget width of a column
     *
     * @access private
     * @var string
     */
    private $_realwidth;
    /**
     * Minimum width of a column
     *
     * @access private
     * @var string
     */
    private $_form_column_width;
    /**
     * Current criteria field
     *
     * @access private
     * @var array
     */
    private $_curField;
    /**
     * Current criteria Sort options
     *
     * @access private
     * @var array
     */
    private $_curSort;
    /**
     * Current criteria Show options
     *
     * @access private
     * @var array
     */
    private $_curShow;
    /**
     * Current criteria values
     *
     * @access private
     * @var array
     */
    private $_curCriteria;
    /**
     * Current criteria AND/OR column realtions
     *
     * @access private
     * @var array
     */
    private $_curAndOrCol;
    /**
     * New column count in case of add/delete
     *
     * @access private
     * @var integer
     */
    private $_new_column_count;
    /**
     * New row count in case of add/delete
     *
     * @access private
     * @var integer
     */
    private $_new_row_count;

    /**
     * Public Constructor
     *
     * @param string $db Database name
     */
    public function __construct($db)
    {
        $this->_db = $db;
        // Sets criteria parameters
        $this->_setSearchParams();
        $this->_setCriteriaTablesAndColumns();
    }

    /**
     * Sets search parameters
     *
     * @return void
     */
    private function _setSearchParams()
    {
        // sets column count
        $criteriaColumnCount = PMA_ifSetOr(
            $_REQUEST['criteriaColumnCount'],
            3,
            'numeric'
        );
        $criteriaColumnAdd = PMA_ifSetOr(
            $_REQUEST['criteriaColumnAdd'],
            0,
            'numeric'
        );
        $this->_criteria_column_count = max(
            $criteriaColumnCount + $criteriaColumnAdd,
            0
        );

        // sets row count
        $rows = PMA_ifSetOr($_REQUEST['rows'],    0, 'numeric');
        $criteriaRowAdd = PMA_ifSetOr($_REQUEST['criteriaRowAdd'], 0, 'numeric');
        $this->_criteria_row_count = max($rows + $criteriaRowAdd, 0);

        $this->_criteriaColumnInsert = PMA_ifSetOr(
            $_REQUEST['criteriaColumnInsert'],
            null,
            'array'
        );
        $this->_criteriaColumnDelete = PMA_ifSetOr(
            $_REQUEST['criteriaColumnDelete'],
            null,
            'array'
        );

        $this->_prev_criteria = isset($_REQUEST['prev_criteria'])
            ? $_REQUEST['prev_criteria']
            : array();
        $this->_criteria = isset($_REQUEST['criteria'])
            ? $_REQUEST['criteria']
            : array_fill(0, $criteriaColumnCount, '');

        $this->_criteriaRowInsert = isset($_REQUEST['criteriaRowInsert'])
            ? $_REQUEST['criteriaRowInsert']
            : array_fill(0, $criteriaColumnCount, '');
        $this->_criteriaRowDelete = isset($_REQUEST['criteriaRowDelete'])
            ? $_REQUEST['criteriaRowDelete']
            : array_fill(0, $criteriaColumnCount, '');
        $this->_criteriaAndOrRow = isset($_REQUEST['criteriaAndOrRow'])
            ? $_REQUEST['criteriaAndOrRow']
            : array_fill(0, $criteriaColumnCount, '');
        $this->_criteriaAndOrColumn = isset($_REQUEST['criteriaAndOrColumn'])
            ? $_REQUEST['criteriaAndOrColumn']
            : array_fill(0, $criteriaColumnCount, '');
        // sets minimum width
        $this->_form_column_width = 12;
        $this->_curField = array();
        $this->_curSort = array();
        $this->_curShow = array();
        $this->_curCriteria = array();
        $this->_curAndOrRow = array();
        $this->_curAndOrCol = array();
    }

    /**
     * Sets criteria tables and columns
     *
     * @return void
     */
    private function _setCriteriaTablesAndColumns()
    {
        // The tables list sent by a previously submitted form
        if (PMA_isValid($_REQUEST['TableList'], 'array')) {
            foreach ($_REQUEST['TableList'] as $each_table) {
                $this->_criteriaTables[$each_table] = ' selected="selected"';
            }
        } // end if
        $all_tables = PMA_DBI_query(
            'SHOW TABLES FROM ' . PMA_Util::backquote($this->_db) . ';',
            null,
            PMA_DBI_QUERY_STORE
        );
        $all_tables_count = PMA_DBI_num_rows($all_tables);
        if (0 == $all_tables_count) {
            PMA_Message::error(__('No tables found in database.'))->display();
            exit;
        }
        // The tables list gets from MySQL
        while (list($table) = PMA_DBI_fetch_row($all_tables)) {
            $columns = PMA_DBI_get_columns($this->_db, $table);

            if (empty($this->_criteriaTables[$table])
                && ! empty($_REQUEST['TableList'])
            ) {
                $this->_criteriaTables[$table] = '';
            } else {
                $this->_criteriaTables[$table] = ' selected="selected"';
            } //  end if

            // The fields list per selected tables
            if ($this->_criteriaTables[$table] == ' selected="selected"') {
                $each_table = PMA_Util::backquote($table);
                $this->_columnNames[]  = $each_table . '.*';
                foreach ($columns as $each_column) {
                    $each_column = $each_table . '.'
                        . PMA_Util::backquote($each_column['Field']);
                    $this->_columnNames[] = $each_column;
                    // increase the width if necessary
                    $this->_form_column_width = max(
                        strlen($each_column),
                        $this->_form_column_width
                    );
                } // end foreach
            } // end if
        } // end while
        PMA_DBI_free_result($all_tables);

        // sets the largest width found
        $this->_realwidth = $this->_form_column_width . 'ex';
    }
    /**
     * Provides select options list containing column names
     *
     * @param integer $column_number Column Number (0,1,2) or more
     * @param string  $selected      Selected criteria column name
     *
     * @return HTML for select options
     */
    private function _showColumnSelectCell($column_number, $selected = '')
    {
        $html_output = '';
        $html_output .= '<td class="center">';
        $html_output .= '<select name="criteriaColumn[' . $column_number . ']" size="1">';
        $html_output .= '<option value="">&nbsp;</option>';
        foreach ($this->_columnNames as $column) {
            $html_output .= '<option value="' . htmlspecialchars($column) . '"'
                . (($column === $selected) ? ' selected="selected"' : '') . '>'
                . str_replace(' ', '&nbsp;', htmlspecialchars($column))
                . '</option>';
        }
        $html_output .= '</select>';
        $html_output .= '</td>';
        return $html_output;
    }

    /**
     * Provides select options list containing sort options (ASC/DESC)
     *
     * @param integer $column_number Column Number (0,1,2) or more
     * @param string  $asc_selected  Selected criteria 'Ascending'
     * @param string  $desc_selected Selected criteria 'Descending'
     *
     * @return HTML for select options
     */
    private function _getSortSelectCell($column_number, $asc_selected = '',
        $desc_selected = ''
    ) {
        $html_output = '<td class="center">';
        $html_output .= '<select style="width: ' . $this->_realwidth
            . '" name="criteriaSort[' . $column_number . ']" size="1">';
        $html_output .= '<option value="">&nbsp;</option>';
        $html_output .= '<option value="ASC"' . $asc_selected . '>'
            . __('Ascending')
            . '</option>';
        $html_output .= '<option value="DESC"' . $desc_selected . '>'
            . __('Descending')
            . '</option>';
        $html_output .= '</select>';
        $html_output .= '</td>';
        return $html_output;
    }

    /**
     * Provides search form's row containing column select options
     *
     * @return HTML for search table's row
     */
    private function _getColumnNamesRow()
    {
        $html_output = '<tr class="odd noclick">';
        $html_output .= '<th>' . __('Column') . ':</th>';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $html_output .= $this->_showColumnSelectCell(
                    $new_column_count
                );
                $new_column_count++;
            }
            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }
            $selected = '';
            if (isset($_REQUEST['criteriaColumn'][$column_index])) {
                $selected = $_REQUEST['criteriaColumn'][$column_index];
                $this->_curField[$new_column_count]
                    = $_REQUEST['criteriaColumn'][$column_index];
            }
            $html_output .= $this->_showColumnSelectCell(
                $new_column_count,
                $selected
            );
            $new_column_count++;
        } // end for
        $this->_new_column_count = $new_column_count;
        $html_output .= '</tr>';
        return $html_output;
    }

    /**
     * Provides search form's row containing sort(ASC/DESC) select options
     *
     * @return HTML for search table's row
     */
    private function _getSortRow()
    {
        $html_output = '<tr class="even noclick">';
        $html_output .= '<th>' . __('Sort') . ':</th>';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $html_output .= $this->_getSortSelectCell($new_column_count);
                $new_column_count++;
            } // end if

            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }
            // If they have chosen all fields using the * selector,
            // then sorting is not available, Fix for Bug #570698
            if (isset($_REQUEST['criteriaSort'][$column_index])
                && isset($_REQUEST['criteriaColumn'][$column_index])
                && substr($_REQUEST['criteriaColumn'][$column_index], -2) == '.*'
            ) {
                $_REQUEST['criteriaSort'][$column_index] = '';
            } //end if
            // Set asc_selected
            if (isset($_REQUEST['criteriaSort'][$column_index])
                && $_REQUEST['criteriaSort'][$column_index] == 'ASC'
            ) {
                $this->_curSort[$new_column_count]
                    = $_REQUEST['criteriaSort'][$column_index];
                $asc_selected = ' selected="selected"';
            } else {
                $asc_selected = '';
            } // end if
            // Set desc selected
            if (isset($_REQUEST['criteriaSort'][$column_index])
                && $_REQUEST['criteriaSort'][$column_index] == 'DESC'
            ) {
                $this->_curSort[$new_column_count]
                    = $_REQUEST['criteriaSort'][$column_index];
                $desc_selected = ' selected="selected"';
            } else {
                $desc_selected = '';
            } // end if
            $html_output .= $this->_getSortSelectCell(
                $new_column_count, $asc_selected, $desc_selected
            );
            $new_column_count++;
        } // end for
        $html_output .= '</tr>';
        return $html_output;
    }

    /**
     * Provides search form's row containing SHOW checkboxes
     *
     * @return HTML for search table's row
     */
    private function _getShowRow()
    {
        $html_output = '<tr class="odd noclick">';
        $html_output .= '<th>' . __('Show') . ':</th>';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $html_output .= '<td class="center">';
                $html_output .= '<input type="checkbox"'
                    . ' name="criteriaShow[' . $new_column_count . ']" />';
                $html_output .= '</td>';
                $new_column_count++;
            } // end if
            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }
            if (isset($_REQUEST['criteriaShow'][$column_index])) {
                $checked_options = ' checked="checked"';
                $this->_curShow[$new_column_count]
                    = $_REQUEST['criteriaShow'][$column_index];
            } else {
                $checked_options = '';
            }
            $html_output .= '<td class="center">';
            $html_output .= '<input type="checkbox"'
                . ' name="criteriaShow[' . $new_column_count . ']"'
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
     * @return HTML for search table's row
     */
    private function _getCriteriaInputboxRow()
    {
        $html_output = '<tr class="even noclick">';
        $html_output .= '<th>' . __('Criteria') . ':</th>';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $html_output .= '<td class="center">';
                $html_output .= '<input type="text"'
                    . ' name="criteria[' . $new_column_count . ']"'
                    . ' value=""'
                    . ' class="textfield"'
                    . ' style="width: ' . $this->_realwidth . '"'
                    . ' size="20" />';
                $html_output .= '</td>';
                $new_column_count++;
            } // end if
            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }
            if (isset($this->_criteria[$column_index])) {
                $tmp_criteria = $this->_criteria[$column_index];
            }
            if ((empty($this->_prev_criteria)
                || ! isset($this->_prev_criteria[$column_index]))
                || $this->_prev_criteria[$column_index] != htmlspecialchars($tmp_criteria)
            ) {
                $this->_curCriteria[$new_column_count] = $tmp_criteria;
            } else {
                $this->_curCriteria[$new_column_count]
                    = $this->_prev_criteria[$column_index];
            }
            $html_output .= '<td class="center">';
            $html_output .= '<input type="hidden"'
                . ' name="prev_criteria[' . $new_column_count . ']"'
                . ' value="' . htmlspecialchars($this->_curCriteria[$new_column_count]) . '" />';
            $html_output .= '<input type="text"'
                . ' name="criteria[' . $new_column_count . ']"'
                . ' value="' . htmlspecialchars($tmp_criteria) . '"'
                . ' class="textfield"'
                . ' style="width: ' . $this->_realwidth . '"'
                . ' size="20" />';
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
    private function _getFootersOptions($type)
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
    private function _getTableFooters()
    {
        $html_output = '<fieldset class="tblFooters">';
        $html_output .= $this->_getFootersOptions("row");
        $html_output .= $this->_getFootersOptions("column");
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
     * @return HTML for table select list
     */
    private function _getTablesList()
    {
        $html_output = '<div class="floatleft">';
        $html_output .= '<fieldset>';
        $html_output .= '<legend>' . __('Use Tables') . '</legend>';
        // Build the options list for each table name
        $options = '';
        $numTableListOptions = 0;
        foreach ($this->_criteriaTables as $key => $val) {
            $options .= '<option value="' . htmlspecialchars($key) . '"' . $val . '>'
                . (str_replace(' ', '&nbsp;', htmlspecialchars($key))) . '</option>';
            $numTableListOptions++;
        }
        $html_output .= '<select name="TableList[]"'
            . ' multiple="multiple" id="listTable"'
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
    private function _getAndOrColCell($column_number, $selected = null)
    {
        $html_output = '<td class="center">';
        $html_output .= '<strong>' . __('Or') . ':</strong>';
        $html_output .= '<input type="radio"'
            . ' name="criteriaAndOrColumn[' . $column_number . ']"'
            . ' value="or"' . $selected['or'] . ' />';
        $html_output .= '&nbsp;&nbsp;<strong>' . __('And') . ':</strong>';
        $html_output .= '<input type="radio"'
            . ' name="criteriaAndOrColumn[' . $column_number . ']"'
            . ' value="and"' . $selected['and'] . ' />';
        $html_output .= '<br />' . __('Ins');
        $html_output .= '<input type="checkbox"'
            . ' name="criteriaColumnInsert[' . $column_number . ']" />';
        $html_output .= '&nbsp;&nbsp;' . __('Del');
        $html_output .= '<input type="checkbox"'
            . ' name="criteriaColumnDelete[' . $column_number . ']" />';
        $html_output .= '</td>';
        return $html_output;
    }

    /**
     * Provides search form's row containing column modifications options
     * (For modifying search form's table columns)
     *
     * @return HTML for search table's row
     */
    private function _getModifyColumnsRow()
    {
        $html_output = '<tr class="even noclick">';
        $html_output .= '<th>' . __('Modify') . ':</th>';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $html_output .= $this->_getAndOrColCell($new_column_count);
                $new_column_count++;
            } // end if

            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }

            if (isset($this->_criteriaAndOrColumn[$column_index])) {
                $this->_curAndOrCol[$new_column_count]
                    = $this->_criteriaAndOrColumn[$column_index];
            }
            if (isset($this->_criteriaAndOrColumn[$column_index])
                && $this->_criteriaAndOrColumn[$column_index] == 'or'
            ) {
                $checked_options['or']  = ' checked="checked"';
                $checked_options['and'] = '';
            } else {
                $checked_options['and'] = ' checked="checked"';
                $checked_options['or']  = '';
            }
            $html_output .= $this->_getAndOrColCell(
                $new_column_count,
                $checked_options
            );
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
    private function _getInsDelAndOrCell($row_index, $checked_options)
    {
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
        $html_output .= '<input type="radio"'
            . ' name="criteriaAndOrRow[' . $row_index . ']"'
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
     * @param integer $new_row_index New row index if rows are added/deleted
     * @param integer $row_index     Row index
     *
     * @return HTML table rows
     */
    private function _getInputboxRow($new_row_index, $row_index)
    {
        $html_output = '';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $or = 'Or' . $new_row_index . '[' . $new_column_count . ']';
                $html_output .= '<td class="center">';
                $html_output .= '<input type="text"'
                    . ' name="Or' . $or . '" class="textfield"'
                    . ' style="width: ' . $this->_realwidth . '" size="20" />';
                $html_output .= '</td>';
                $new_column_count++;
            } // end if
            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }
            $or = 'Or' . $new_row_index;
            if (! empty($_POST[$or]) && isset($_POST[$or][$column_index])) {
                $tmp_or = $_POST[$or][$column_index];
            } else {
                $tmp_or     = '';
            }
            $html_output .= '<td class="center">';
            $html_output .= '<input type="text"'
                . ' name="Or' . $new_row_index . '[' . $new_column_count . ']' . '"'
                . ' value="' . htmlspecialchars($tmp_or) . '" class="textfield"'
                . ' style="width: ' . $this->_realwidth . '" size="20" />';
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
     * @return HTML table rows
     */
    private function _getInsDelAndOrCriteriaRows()
    {
        $html_output = '';
        $new_row_count = 0;
        $odd_row = true;
        for ($row_index = 0; $row_index <= $this->_criteria_row_count; $row_index++) {
            if (isset($this->_criteriaRowInsert[$row_index])
                && $this->_criteriaRowInsert[$row_index] == 'on'
            ) {
                $checked_options['or']  = ' checked="checked"';
                $checked_options['and'] = '';
                $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . ' noclick">';
                $html_output .= $this->_getInsDelAndOrCell(
                    $new_row_count, $checked_options
                );
                $html_output .= $this->_getInputboxRow(
                    $new_row_count, $row_index
                );
                $new_row_count++;
                $html_output .= '</tr>';
                $odd_row =! $odd_row;
            } // end if
            if (isset($this->_criteriaRowDelete[$row_index])
                && $this->_criteriaRowDelete[$row_index] == 'on'
            ) {
                continue;
            }
            if (isset($this->_criteriaAndOrRow[$row_index])) {
                $this->_curAndOrRow[$new_row_count]
                    = $this->_criteriaAndOrRow[$row_index];
            }
            if (isset($this->_criteriaAndOrRow[$row_index])
                && $this->_criteriaAndOrRow[$row_index] == 'and'
            ) {
                $checked_options['and'] =  ' checked="checked"';
                $checked_options['or']  =  '';
            } else {
                $checked_options['or']  =  ' checked="checked"';
                $checked_options['and'] =  '';
            }
            $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . ' noclick">';
            $html_output .= $this->_getInsDelAndOrCell(
                $new_row_count, $checked_options
            );
            $html_output .= $this->_getInputboxRow(
                $new_row_count, $row_index
            );
            $new_row_count++;
            $html_output .= '</tr>';
            $odd_row =! $odd_row;
        } // end for
        $this->_new_row_count = $new_row_count;
        return $html_output;
    }

    /**
     * Provides SELECT clause for building SQL query
     *
     * @return Select clause
     */
    private function _getSelectClause()
    {
        $select_clause = '';
        $select_clauses = array();
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_curField[$column_index])
                && isset($this->_curShow[$column_index])
                && $this->_curShow[$column_index] == 'on'
            ) {
                $select_clauses[] = $this->_curField[$column_index];
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
     * @return Where clause
     */
    private function _getWhereClause()
    {
        $where_clause = '';
        $criteria_cnt = 0;
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            if (! empty($this->_curField[$column_index])
                && ! empty($this->_curCriteria[$column_index])
                && $column_index
                && isset($last_where)
                && isset($this->_curAndOrCol)
            ) {
                $where_clause .= ' ' . strtoupper($this->_curAndOrCol[$last_where]) . ' ';
            }
            if (! empty($this->_curField[$column_index])
                && ! empty($this->_curCriteria[$column_index])
            ) {
                $where_clause .= '(' . $this->_curField[$column_index] . ' '
                    . $this->_curCriteria[$column_index] . ')';
                $last_where = $column_index;
                $criteria_cnt++;
            }
        } // end for
        if ($criteria_cnt > 1) {
            $where_clause = '(' . $where_clause . ')';
        }
        // OR rows ${'cur' . $or}[$column_index]
        if (! isset($this->_curAndOrRow)) {
            $this->_curAndOrRow = array();
        }
        for ($row_index = 0; $row_index <= $this->_criteria_row_count; $row_index++) {
            $criteria_cnt = 0;
            $qry_orwhere = '';
            $last_orwhere = '';
            for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
                if (! empty($this->_curField[$column_index])
                    && ! empty(${'curOr' . $row_index}[$column_index])
                    && $column_index
                ) {
                    $qry_orwhere .= ' ' . strtoupper($this->_curAndOrCol[$last_orwhere]) . ' ';
                }
                if (! empty($this->_curField[$column_index])
                    && ! empty(${'curOr' . $row_index}[$column_index])
                ) {
                    $qry_orwhere .= '(' . $this->_curField[$column_index]
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
                    .  strtoupper(
                        isset($this->_curAndOrRow[$row_index])
                        ? $this->_curAndOrRow[$row_index] . ' '
                        : ''
                    )
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
     * @return Order By clause
     */
    private function _getOrderByClause()
    {
        $orderby_clause = '';
        $orderby_clauses = array();
        for ($column_index = 0; $column_index < $this->_criteria_column_count; $column_index++) {
            // if all columns are chosen with * selector,
            // then sorting isn't available
            // Fix for Bug #570698
            if (! empty($this->_curField[$column_index])
                && ! empty($this->_curSort[$column_index])
            ) {
                if (substr($this->_curField[$column_index], -2) == '.*') {
                    continue;
                }
                $orderby_clauses[] = $this->_curField[$column_index] . ' '
                    . $this->_curSort[$column_index];
            }
        } // end for
        if ($orderby_clauses) {
            $orderby_clause = 'ORDER BY '
                . htmlspecialchars(implode(", ", $orderby_clauses)) . "\n";
        }
        return $orderby_clause;
    }

    /**
     * Provides UNIQUE columns and INDEX columns present in criteria tables
     *
     * @param array $all_tables           Tables involved in the search
     * @param array $all_columns          Columns involved in the search
     * @param array $where_clause_columns Columns having criteria where clause
     *
     * @return array having UNIQUE and INDEX columns
     */
    private function _getIndexes($all_tables, $all_columns,
        $where_clause_columns
    ) {
        $unique_columns = array();
        $index_columns = array();

        foreach ($all_tables as $table) {
            $indexes = PMA_DBI_get_table_indexes($this->_db, $table);
            foreach ($indexes as $index) {
                $column = $table . '.' . $index['Column_name'];
                if (isset($all_columns[$column])) {
                    if ($index['Non_unique'] == 0) {
                        if (isset($where_clause_columns[$column])) {
                            $unique_columns[$column] = 'Y';
                        } else {
                            $unique_columns[$column] = 'N';
                        }
                    } else {
                        if (isset($where_clause_columns[$column])) {
                            $index_columns[$column] = 'Y';
                        } else {
                            $index_columns[$column] = 'N';
                        }
                    }
                }
            } // end while (each index of a table)
        } // end while (each table)

        return array(
            'unique' => $unique_columns,
            'index' => $index_columns
        );
    }

    /**
     * Provides UNIQUE columns and INDEX columns present in criteria tables
     *
     * @param array $all_tables           Tables involved in the search
     * @param array $all_columns          Columns involved in the search
     * @param array $where_clause_columns Columns having criteria where clause
     *
     * @return array having UNIQUE and INDEX columns
     */
    private function _getLeftJoinColumnCandidates($all_tables, $all_columns,
        $where_clause_columns
    ) {
        PMA_DBI_select_db($this->_db);
        $candidate_columns = array();

        // Get unique columns and index columns
        $indexes = $this->_getIndexes(
            $all_tables, $all_columns, $where_clause_columns
        );
        $unique_columns = $indexes['unique'];
        $index_columns = $indexes['index'];

        // now we want to find the best.
        if (isset($unique_columns) && count($unique_columns) > 0) {
            $candidate_columns = $unique_columns;
            $needsort = 1;
        } elseif (isset($index_columns) && count($index_columns) > 0) {
            $candidate_columns = $index_columns;
            $needsort = 1;
        } elseif (isset($where_clause_columns) && count($where_clause_columns) > 0) {
            $candidate_columns = $where_clause_columns;
            $needsort = 0;
        } else {
            $candidate_columns = $all_tables;
            $needsort = 0;
        }

        // If we came up with $unique_columns (very good) or $index_columns (still
        // good) as $candidate_columns we want to check if we have any 'Y' there
        // (that would mean that they were also found in the whereclauses
        // which would be great). if yes, we take only those
        if ($needsort == 1) {
            foreach ($candidate_columns as $column => $is_where) {
                $table = explode('.', $column);
                $table = $table[0];
                if ($is_where == 'Y') {
                    $vg[$column] = $table;
                } else {
                    $sg[$column] = $table;
                }
            }
            if (isset($vg)) {
                $candidate_columns = $vg;
                // Candidates restricted in index+where
            } else {
                $candidate_columns = $sg;
                // None of the candidates where in a where-clause
            }
        }

        return $candidate_columns;
    }

    /**
     * Provides the main table to form the LEFT JOIN clause
     *
     * @param array $all_tables           Tables involved in the search
     * @param array $all_columns          Columns involved in the search
     * @param array $where_clause_columns Columns having criteria where clause
     * @param array $where_clause_tables  Tables having criteria where clause
     *
     * @return string table name
     */
    private function _getMasterTable($all_tables, $all_columns,
        $where_clause_columns, $where_clause_tables
    ) {
        $master = '';
        if (count($where_clause_tables) == 1) {
            // If there is exactly one column that has a decent where-clause
            // we will just use this
            $master = key($where_clause_tables);
        } else {
            // Now let's find out which of the tables has an index
            // (When the control user is the same as the normal user
            // because he is using one of his databases as pmadb,
            // the last db selected is not always the one where we need to work)
            $candidate_columns = $this->_getLeftJoinColumnCandidates(
                $all_tables, $all_columns, $where_clause_columns
            );
            // If our array of candidates has more than one member we'll just
            // find the smallest table.
            // Of course the actual query would be faster if we check for
            // the Criteria which gives the smallest result set in its table,
            // but it would take too much time to check this
            if (count($candidate_columns) > 1) {
                // Of course we only want to check each table once
                $checked_tables = $candidate_columns;
                foreach ($candidate_columns as $table) {
                    if ($checked_tables[$table] != 1) {
                        $tsize[$table] = PMA_Table::countRecords(
                            $this->_db,
                            $table,
                            false
                        );
                        $checked_tables[$table] = 1;
                    }
                    $csize[$table] = $tsize[$table];
                }
                asort($csize);
                reset($csize);
                $master = key($csize); // Smallest
            } else {
                reset($candidate_columns);
                $master = current($candidate_columns); // Only one single candidate
            }
        } // end if (exactly one where clause)
        return $master;
    }

    /**
     * Provides columns and tables that have valid where clause criteria
     *
     * @return array
     */
    private function _getWhereClauseTablesAndColumns()
    {
        $where_clause_columns = array();
        $where_clause_tables = array();
        // Now we need all tables that we have in the where clause
        for ($column_index = 0; $column_index < count($this->_criteria); $column_index++) {
            $current_table = explode('.', $_POST['criteriaColumn'][$column_index]);
            if (empty($current_table[0]) || empty($current_table[1])) {
                continue;
            } // end if
            $table = str_replace('`', '', $current_table[0]);
            $column = str_replace('`', '', $current_table[1]);
            $column = $table . '.' . $column;
            // Now we know that our array has the same numbers as $criteria
            // we can check which of our columns has a where clause
            if (! empty($this->_criteria[$column_index])) {
                if (substr($this->_criteria[$column_index], 0, 1) == '='
                    || stristr($this->_criteria[$column_index], 'is')
                ) {
                    $where_clause_columns[$column] = $column;
                    $where_clause_tables[$table]  = $table;
                }
            } // end if
        } // end for
        return array(
            'where_clause_tables' => $where_clause_tables,
            'where_clause_columns' => $where_clause_columns
        );
    }

    /**
     * Provides FROM clause for building SQL query
     *
     * @param string $cfgRelation Relation Settings
     *
     * @return FROM clause
     */
    private function _getFromClause($cfgRelation)
    {
        $from_clause = '';
        if (isset($_POST['criteriaColumn']) && count($_POST['criteriaColumn']) > 0) {
            // Initialize some variables
            $all_tables = $all_columns = $known_tables = $remaining_tables = array();
            $left_join = '';

            // We only start this if we have fields, otherwise it would be dumb
            foreach ($_POST['criteriaColumn'] as $value) {
                $parts = explode('.', $value);
                if (! empty($parts[0]) && ! empty($parts[1])) {
                    $table = str_replace('`', '', $parts[0]);
                    $all_tables[$table] = $table;
                    $all_columns[] = $table . '.' . str_replace('`', '', $parts[1]);
                }
            } // end while

            // Create LEFT JOINS out of Relations
            if ($cfgRelation['relwork'] && count($all_tables) > 0) {
                // Get tables and columns with valid where clauses
                $valid_where_clauses = $this->_getWhereClauseTablesAndColumns();
                $where_clause_tables = $valid_where_clauses['where_clause_tables'];
                $where_clause_columns = $valid_where_clauses['where_clause_columns'];
                // Get master table
                $master = $this->_getMasterTable(
                    $all_tables, $all_columns,
                    $where_clause_columns, $where_clause_tables
                );
                $from_clause = PMA_Util::backquote($master)
                    . PMA_getRelatives($all_tables, $master);

            } // end if ($cfgRelation['relwork'] && count($all_tables) > 0)
        } // end count($_POST['criteriaColumn']) > 0

        // In case relations are not defined, just generate the FROM clause
        // from the list of tables, however we don't generate any JOIN
        if (empty($from_clause) && isset($all_tables)) {
            $from_clause = implode(', ', $all_tables);
        }
        return $from_clause;
    }

    /**
     * Provides the generated SQL query
     *
     * @param string $cfgRelation Relation Settings
     *
     * @return string SQL query
     */
    private function _getSQLQuery($cfgRelation)
    {
        $sql_query = '';
        // get SELECT clause
        $sql_query .= $this->_getSelectClause();
        // get FROM clause
        $from_clause = $this->_getFromClause($cfgRelation);
        if (! empty($from_clause)) {
            $sql_query .= 'FROM ' . htmlspecialchars($from_clause) . "\n";
        }
        // get WHERE clause
        $sql_query .= $this->_getWhereClause();
        // get ORDER BY clause
        $sql_query .= $this->_getOrderByClause();
        return $sql_query;
    }

    /**
     * Provides the generated QBE form
     *
     * @param string $cfgRelation Relation Settings
     *
     * @return string QBE form
     */
    public function getSelectionForm($cfgRelation)
    {
        $html_output = '<form action="db_qbe.php" method="post">';
        $html_output .= '<fieldset>';
        $html_output .= '<table class="data" style="width: 100%;">';
        // Get table's <tr> elements
        $html_output .= $this->_getColumnNamesRow();
        $html_output .= $this->_getSortRow();
        $html_output .= $this->_getShowRow();
        $html_output .= $this->_getCriteriaInputboxRow();
        $html_output .= $this->_getInsDelAndOrCriteriaRows();
        $html_output .= $this->_getModifyColumnsRow();
        $html_output .= '</table>';
        $this->_new_row_count--;
        $url_params['db'] = $this->_db;
        $url_params['criteriaColumnCount'] = $this->_new_column_count;
        $url_params['rows'] = $this->_new_row_count;
        $html_output .= PMA_generate_common_hidden_inputs($url_params);
        $html_output .= '</fieldset>';
        // get footers
        $html_output .= $this->_getTableFooters();
        // get tables select list
        $html_output .= $this->_getTablesList();
        $html_output .= '</form>';
        $html_output .= '<form action="db_qbe.php" method="post">';
        $html_output .= PMA_generate_common_hidden_inputs(array('db' => $this->_db));
        // get SQL query
        $html_output .= '<div class="floatleft">';
        $html_output .= '<fieldset>';
        $html_output .= '<legend>'
            . sprintf(
                __('SQL query on database <b>%s</b>:'),
                PMA_Util::getDbLink($this->_db)
            );
        $html_output .= '</legend>';
        $text_dir = 'ltr';
        $html_output .= '<textarea cols="80" name="sql_query" id="textSqlquery"'
            . ' rows="' . ((count($this->_criteriaTables) > 30) ? '15' : '7') . '"'
            . ' dir="' . $text_dir . '">';
        $html_output .= $this->_getSQLQuery($cfgRelation);
        $html_output .= '</textarea>';
        $html_output .= '</fieldset>';
        // displays form's footers
        $html_output .= '<fieldset class="tblFooters">';
        $html_output .= '<input type="hidden" name="submit_sql" value="1" />';
        $html_output .= '<input type="submit" value="' . __('Submit Query') . '" />';
        $html_output .= '</fieldset>';
        $html_output .= '</div>';
        $html_output .= '</form>';
        return $html_output;
    }
}
?>
