<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles DB QBE search
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Class to handle database QBE search
 *
 * @package PhpMyAdmin
 */
class Qbe
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
     * Whether to delete a row
     *
     * @access private
     * @var array
     */
    private $_criteriaRowDelete;
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
     * Large width of a column
     *
     * @access private
     * @var string
     */
    private $_realwidth;
    /**
     * Minimum width of a column
     *
     * @access private
     * @var int
     */
    private $_form_column_width;
    /**
     * Selected columns in the form
     *
     * @access private
     * @var array
     */
    private $_formColumns;
    /**
     * Entered aliases in the form
     *
     * @access private
     * @var array
     */
    private $_formAliases;
    /**
     * Chosen sort options in the form
     *
     * @access private
     * @var array
     */
    private $_formSorts;
    /**
     * Chosen sort orders in the form
     *
     * @access private
     * @var array
     */
    private $_formSortOrders;
    /**
     * Show checkboxes in the form
     *
     * @access private
     * @var array
     */
    private $_formShows;
    /**
     * Entered criteria values in the form
     *
     * @access private
     * @var array
     */
    private $_formCriterions;
    /**
     * AND/OR column radio buttons in the form
     *
     * @access private
     * @var array
     */
    private $_formAndOrCols;
    /**
     * AND/OR row radio buttons in the form
     *
     * @access private
     * @var array
     */
    private $_formAndOrRows;
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
     * List of saved searches
     *
     * @access private
     * @var array
     */
    private $_savedSearchList = null;
    /**
     * Current search
     *
     * @access private
     * @var SavedSearches
     */
    private $_currentSearch = null;

    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Public Constructor
     *
     * @param string        $dbname          Database name
     * @param array         $savedSearchList List of saved searches
     * @param SavedSearches $currentSearch   Current search id
     */
    public function __construct(
        $dbname,
        array $savedSearchList = array(),
        $currentSearch = null
    ) {
        $this->_db = $dbname;
        $this->_savedSearchList = $savedSearchList;
        $this->_currentSearch = $currentSearch;
        $this->_loadCriterias();
        // Sets criteria parameters
        $this->_setSearchParams();
        $this->_setCriteriaTablesAndColumns();
        $this->relation = new Relation();
    }

    /**
     * Initialize criterias
     *
     * @return static
     */
    private function _loadCriterias()
    {
        if (null === $this->_currentSearch
            || null === $this->_currentSearch->getCriterias()
        ) {
            return $this;
        }

        $criterias = $this->_currentSearch->getCriterias();
        $_REQUEST = $criterias + $_REQUEST;

        return $this;
    }

    /**
     * Getter for current search
     *
     * @return SavedSearches
     */
    private function _getCurrentSearch()
    {
        return $this->_currentSearch;
    }

    /**
     * Sets search parameters
     *
     * @return void
     */
    private function _setSearchParams()
    {
        $criteriaColumnCount = $this->_initializeCriteriasCount();

        $this->_criteriaColumnInsert = Core::ifSetOr(
            $_REQUEST['criteriaColumnInsert'],
            null,
            'array'
        );
        $this->_criteriaColumnDelete = Core::ifSetOr(
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
        $this->_formColumns = array();
        $this->_formSorts = array();
        $this->_formShows = array();
        $this->_formCriterions = array();
        $this->_formAndOrRows = array();
        $this->_formAndOrCols = array();
    }

    /**
     * Sets criteria tables and columns
     *
     * @return void
     */
    private function _setCriteriaTablesAndColumns()
    {
        // The tables list sent by a previously submitted form
        if (Core::isValid($_REQUEST['TableList'], 'array')) {
            foreach ($_REQUEST['TableList'] as $each_table) {
                $this->_criteriaTables[$each_table] = ' selected="selected"';
            }
        } // end if
        $all_tables = $GLOBALS['dbi']->query(
            'SHOW TABLES FROM ' . Util::backquote($this->_db) . ';',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $all_tables_count = $GLOBALS['dbi']->numRows($all_tables);
        if (0 == $all_tables_count) {
            Message::error(__('No tables found in database.'))->display();
            exit;
        }
        // The tables list gets from MySQL
        while (list($table) = $GLOBALS['dbi']->fetchRow($all_tables)) {
            $columns = $GLOBALS['dbi']->getColumns($this->_db, $table);

            if (empty($this->_criteriaTables[$table])
                && ! empty($_REQUEST['TableList'])
            ) {
                $this->_criteriaTables[$table] = '';
            } else {
                $this->_criteriaTables[$table] = ' selected="selected"';
            } //  end if

            // The fields list per selected tables
            if ($this->_criteriaTables[$table] == ' selected="selected"') {
                $each_table = Util::backquote($table);
                $this->_columnNames[]  = $each_table . '.*';
                foreach ($columns as $each_column) {
                    $each_column = $each_table . '.'
                        . Util::backquote($each_column['Field']);
                    $this->_columnNames[] = $each_column;
                    // increase the width if necessary
                    $this->_form_column_width = max(
                        mb_strlen($each_column),
                        $this->_form_column_width
                    );
                } // end foreach
            } // end if
        } // end while
        $GLOBALS['dbi']->freeResult($all_tables);

        // sets the largest width found
        $this->_realwidth = $this->_form_column_width . 'ex';
    }
    /**
     * Provides select options list containing column names
     *
     * @param integer $column_number Column Number (0,1,2) or more
     * @param string  $selected      Selected criteria column name
     *
     * @return string HTML for select options
     */
    private function _showColumnSelectCell($column_number, $selected = '')
    {
        return Template::get('database/qbe/column_select_cell')->render([
            'column_number' => $column_number,
            'column_names' => $this->_columnNames,
            'selected' => $selected,
        ]);
    }

    /**
     * Provides select options list containing sort options (ASC/DESC)
     *
     * @param integer $columnNumber Column Number (0,1,2) or more
     * @param string  $selected     Selected criteria 'ASC' or 'DESC'
     *
     * @return string HTML for select options
     */
    private function _getSortSelectCell(
        $columnNumber,
        $selected = ''
    ) {
        return Template::get('database/qbe/sort_select_cell')->render([
            'real_width' => $this->_realwidth,
            'column_number' => $columnNumber,
            'selected' => $selected,
        ]);
    }

    /**
     * Provides select options list containing sort order
     *
     * @param integer $columnNumber Column Number (0,1,2) or more
     * @param integer $sortOrder    Sort order
     *
     * @return string HTML for select options
     */
    private function _getSortOrderSelectCell($columnNumber, $sortOrder)
    {
        $totalColumnCount = $this->_getNewColumnCount();
        return Template::get('database/qbe/sort_order_select_cell')->render([
            'total_column_count' => $totalColumnCount,
            'column_number' => $columnNumber,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Returns the new column count after adding and removing columns as instructed
     *
     * @return int new column count
     */
    private function _getNewColumnCount()
    {
        $totalColumnCount = $this->_criteria_column_count;
        if (! empty($this->_criteriaColumnInsert)) {
            $totalColumnCount += count($this->_criteriaColumnInsert);
        }
        if (! empty($this->_criteriaColumnDelete)) {
            $totalColumnCount -= count($this->_criteriaColumnDelete);
        }
        return $totalColumnCount;
    }

    /**
     * Provides search form's row containing column select options
     *
     * @return string HTML for search table's row
     */
    private function _getColumnNamesRow()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Column:') . '</th>';
        $new_column_count = 0;
        for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
        ) {
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
                $this->_formColumns[$new_column_count]
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
     * Provides search form's row containing column aliases
     *
     * @return string HTML for search table's row
     */
    private function _getColumnAliasRow()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Alias:') . '</th>';
        $new_column_count = 0;

        for (
        $colInd = 0;
        $colInd < $this->_criteria_column_count;
        $colInd++
        ) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$colInd])
                && $this->_criteriaColumnInsert[$colInd] == 'on'
            ) {
                $html_output .= '<td class="center">';
                $html_output .= '<input type="text"'
                    . ' name="criteriaAlias[' . $new_column_count . ']" />';
                $html_output .= '</td>';
                $new_column_count++;
            } // end if

            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$colInd])
                && $this->_criteriaColumnDelete[$colInd] == 'on'
            ) {
                continue;
            }

            $tmp_alias = '';
            if (! empty($_REQUEST['criteriaAlias'][$colInd])) {
                $tmp_alias
                    = $this->_formAliases[$new_column_count]
                        = $_REQUEST['criteriaAlias'][$colInd];
            }// end if

            $html_output .= '<td class="center">';
            $html_output .= '<input type="text"'
                . ' name="criteriaAlias[' . $new_column_count . ']"'
                . ' value="' . htmlspecialchars($tmp_alias) . '" />';
            $html_output .= '</td>';
            $new_column_count++;
        } // end for
        $html_output .= '</tr>';
        return $html_output;
    }

    /**
     * Provides search form's row containing sort(ASC/DESC) select options
     *
     * @return string HTML for search table's row
     */
    private function _getSortRow()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Sort:') . '</th>';
        $new_column_count = 0;

        for (
            $colInd = 0;
            $colInd < $this->_criteria_column_count;
            $colInd++
        ) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$colInd])
                && $this->_criteriaColumnInsert[$colInd] == 'on'
            ) {
                $html_output .= $this->_getSortSelectCell($new_column_count);
                $new_column_count++;
            } // end if

            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$colInd])
                && $this->_criteriaColumnDelete[$colInd] == 'on'
            ) {
                continue;
            }
            // If they have chosen all fields using the * selector,
            // then sorting is not available, Fix for Bug #570698
            if (isset($_REQUEST['criteriaSort'][$colInd])
                && isset($_REQUEST['criteriaColumn'][$colInd])
                && mb_substr($_REQUEST['criteriaColumn'][$colInd], -2) == '.*'
            ) {
                $_REQUEST['criteriaSort'][$colInd] = '';
            } //end if

            $selected = '';
            if (isset($_REQUEST['criteriaSort'][$colInd])) {
                $this->_formSorts[$new_column_count]
                    = $_REQUEST['criteriaSort'][$colInd];

                if ($_REQUEST['criteriaSort'][$colInd] == 'ASC') {
                    $selected = 'ASC';
                } elseif ($_REQUEST['criteriaSort'][$colInd] == 'DESC') {
                    $selected = 'DESC';
                }
            } else {
                $this->_formSorts[$new_column_count] = '';
            }

            $html_output .= $this->_getSortSelectCell(
                $new_column_count, $selected
            );
            $new_column_count++;
        } // end for
        $html_output .= '</tr>';
        return $html_output;
    }

    /**
     * Provides search form's row containing sort order
     *
     * @return string HTML for search table's row
     */
    private function _getSortOrder()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Sort order:') . '</th>';
        $new_column_count = 0;

        for (
        $colInd = 0;
        $colInd < $this->_criteria_column_count;
        $colInd++
        ) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$colInd])
                && $this->_criteriaColumnInsert[$colInd] == 'on'
            ) {
                $html_output .= $this->_getSortOrderSelectCell(
                    $new_column_count, null
                );
                $new_column_count++;
            } // end if

            if (! empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$colInd])
                && $this->_criteriaColumnDelete[$colInd] == 'on'
            ) {
                continue;
            }

            $sortOrder = null;
            if (! empty($_REQUEST['criteriaSortOrder'][$colInd])) {
                $sortOrder
                    = $this->_formSortOrders[$new_column_count]
                        = $_REQUEST['criteriaSortOrder'][$colInd];
            }

            $html_output .= $this->_getSortOrderSelectCell(
                $new_column_count, $sortOrder
            );
            $new_column_count++;
        } // end for
        $html_output .= '</tr>';
        return $html_output;
    }

    /**
     * Provides search form's row containing SHOW checkboxes
     *
     * @return string HTML for search table's row
     */
    private function _getShowRow()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Show:') . '</th>';
        $new_column_count = 0;
        for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
        ) {
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
                $this->_formShows[$new_column_count]
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
     * @return string HTML for search table's row
     */
    private function _getCriteriaInputboxRow()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Criteria:') . '</th>';
        $new_column_count = 0;
        for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
        ) {
            if (! empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $html_output .= '<td class="center">';
                $html_output .= '<input type="text"'
                    . ' name="criteria[' . $new_column_count . ']"'
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
                $this->_formCriterions[$new_column_count] = $tmp_criteria;
            } else {
                $this->_formCriterions[$new_column_count]
                    = $this->_prev_criteria[$column_index];
            }
            $html_output .= '<td class="center">';
            $html_output .= '<input type="hidden"'
                . ' name="prev_criteria[' . $new_column_count . ']"'
                . ' value="'
                . htmlspecialchars($this->_formCriterions[$new_column_count])
                . '" />';
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
     * @return string HTML for footer options
     */
    private function _getFootersOptions($type)
    {
        return Template::get('database/qbe/footer_options')->render([
            'type' => $type,
        ]);
    }

    /**
     * Provides search form table's footer options
     *
     * @return string HTML for table footer
     */
    private function _getTableFooters()
    {
        $html_output = '<fieldset class="tblFooters">';
        $html_output .= $this->_getFootersOptions("row");
        $html_output .= $this->_getFootersOptions("column");
        $html_output .= '<div class="floatleft">';
        $html_output .= '<input type="submit" name="modify"'
            . ' value="' . __('Update Query') . '" />';
        $html_output .= '</div>';
        $html_output .= '</fieldset>';
        return $html_output;
    }

    /**
     * Provides a select list of database tables
     *
     * @return string HTML for table select list
     */
    private function _getTablesList()
    {
        $html_output = '<div class="floatleft width100">';
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
     * @param integer    $column_number Column Number (0,1,2) or more
     * @param array|null $selected      Selected criteria column name
     * @param bool       $last_column   Whether this is the last column
     *
     * @return string HTML for modification cell
     */
    private function _getAndOrColCell(
        $column_number, $selected = null, $last_column = false
    ) {
        $html_output = '<td class="center">';
        if (! $last_column) {
            $html_output .= '<strong>' . __('Or:') . '</strong>';
            $html_output .= '<input type="radio"'
                . ' name="criteriaAndOrColumn[' . $column_number . ']"'
                . ' value="or"' . $selected['or'] . ' />';
            $html_output .= '&nbsp;&nbsp;<strong>' . __('And:') . '</strong>';
            $html_output .= '<input type="radio"'
                . ' name="criteriaAndOrColumn[' . $column_number . ']"'
                . ' value="and"' . $selected['and'] . ' />';
        }
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
     * @return string HTML for search table's row
     */
    private function _getModifyColumnsRow()
    {
        $html_output = '<tr class="noclick">';
        $html_output .= '<th>' . __('Modify:') . '</th>';
        $new_column_count = 0;
        for (
        $column_index = 0;
        $column_index < $this->_criteria_column_count;
        $column_index++
        ) {
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
                $this->_formAndOrCols[$new_column_count]
                    = $this->_criteriaAndOrColumn[$column_index];
            }
            $checked_options = array();
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
                $checked_options,
                ($column_index + 1 == $this->_criteria_column_count)
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
     * @param array   $checked_options If checked
     *
     * @return string HTML
     */
    private function _getInsDelAndOrCell($row_index, array $checked_options)
    {
        $html_output = '<td class="value nowrap">';
        $html_output .= '<!-- Row controls -->';
        $html_output .= '<table class="nospacing nopadding">';
        $html_output .= '<tr>';
        $html_output .= '<td class="value nowrap">';
        $html_output .= '<small>' . __('Ins:') . '</small>';
        $html_output .= '<input type="checkbox"'
            . ' name="criteriaRowInsert[' . $row_index . ']" />';
        $html_output .= '</td>';
        $html_output .= '<td class="value">';
        $html_output .= '<strong>' . __('And:') . '</strong>';
        $html_output .= '</td>';
        $html_output .= '<td>';
        $html_output .= '<input type="radio"'
            . ' name="criteriaAndOrRow[' . $row_index . ']" value="and"'
            . $checked_options['and'] . ' />';
        $html_output .= '</td>';
        $html_output .= '</tr>';
        $html_output .= '<tr>';
        $html_output .= '<td class="value nowrap">';
        $html_output .= '<small>' . __('Del:') . '</small>';
        $html_output .= '<input type="checkbox"'
            . ' name="criteriaRowDelete[' . $row_index . ']" />';
        $html_output .= '</td>';
        $html_output .= '<td class="value">';
        $html_output .= '<strong>' . __('Or:') . '</strong>';
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
     *
     * @return string HTML table rows
     */
    private function _getInputboxRow($new_row_index)
    {
        $html_output = '';
        $new_column_count = 0;
        for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
        ) {
            if (!empty($this->_criteriaColumnInsert)
                && isset($this->_criteriaColumnInsert[$column_index])
                && $this->_criteriaColumnInsert[$column_index] == 'on'
            ) {
                $orFieldName = 'Or' . $new_row_index . '[' . $new_column_count . ']';
                $html_output .= '<td class="center">';
                $html_output .= '<input type="text"'
                    . ' name="Or' . $orFieldName . '" class="textfield"'
                    . ' style="width: ' . $this->_realwidth . '" size="20" />';
                $html_output .= '</td>';
                $new_column_count++;
            } // end if
            if (!empty($this->_criteriaColumnDelete)
                && isset($this->_criteriaColumnDelete[$column_index])
                && $this->_criteriaColumnDelete[$column_index] == 'on'
            ) {
                continue;
            }
            $or = 'Or' . $new_row_index;
            if (! empty($_REQUEST[$or]) && isset($_REQUEST[$or][$column_index])) {
                $tmp_or = $_REQUEST[$or][$column_index];
            } else {
                $tmp_or     = '';
            }
            $html_output .= '<td class="center">';
            $html_output .= '<input type="text"'
                . ' name="Or' . $new_row_index . '[' . $new_column_count . ']' . '"'
                . ' value="' . htmlspecialchars($tmp_or) . '" class="textfield"'
                . ' style="width: ' . $this->_realwidth . '" size="20" />';
            $html_output .= '</td>';
            if (!empty(${$or}) && isset(${$or}[$column_index])) {
                $GLOBALS[${'cur' . $or}][$new_column_count]
                    = ${$or}[$column_index];
            }
            $new_column_count++;
        } // end for
        return $html_output;
    }

    /**
     * Provides rows for criteria inputbox Insert/Delete options
     * with AND/OR relationship modification options
     *
     * @return string HTML table rows
     */
    private function _getInsDelAndOrCriteriaRows()
    {
        $html_output = '';
        $new_row_count = 0;
        $checked_options = array();
        for (
        $row_index = 0;
        $row_index <= $this->_criteria_row_count;
        $row_index++
        ) {
            if (isset($this->_criteriaRowInsert[$row_index])
                && $this->_criteriaRowInsert[$row_index] == 'on'
            ) {
                $checked_options['or']  = ' checked="checked"';
                $checked_options['and'] = '';
                $html_output .= '<tr class="noclick">';
                $html_output .= $this->_getInsDelAndOrCell(
                    $new_row_count, $checked_options
                );
                $html_output .= $this->_getInputboxRow(
                    $new_row_count
                );
                $new_row_count++;
                $html_output .= '</tr>';
            } // end if
            if (isset($this->_criteriaRowDelete[$row_index])
                && $this->_criteriaRowDelete[$row_index] == 'on'
            ) {
                continue;
            }
            if (isset($this->_criteriaAndOrRow[$row_index])) {
                $this->_formAndOrRows[$new_row_count]
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
            $html_output .= '<tr class="noclick">';
            $html_output .= $this->_getInsDelAndOrCell(
                $new_row_count, $checked_options
            );
            $html_output .= $this->_getInputboxRow(
                $new_row_count
            );
            $new_row_count++;
            $html_output .= '</tr>';
        } // end for
        $this->_new_row_count = $new_row_count;
        return $html_output;
    }

    /**
     * Provides SELECT clause for building SQL query
     *
     * @return string Select clause
     */
    private function _getSelectClause()
    {
        $select_clause = '';
        $select_clauses = array();
        for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
        ) {
            if (! empty($this->_formColumns[$column_index])
                && isset($this->_formShows[$column_index])
                && $this->_formShows[$column_index] == 'on'
            ) {
                $select = $this->_formColumns[$column_index];
                if (! empty($this->_formAliases[$column_index])) {
                    $select .= " AS "
                        . Util::backquote($this->_formAliases[$column_index]);
                }
                $select_clauses[] = $select;
            }
        } // end for
        if (!empty($select_clauses)) {
            $select_clause = 'SELECT '
                . htmlspecialchars(implode(", ", $select_clauses)) . "\n";
        }
        return $select_clause;
    }

    /**
     * Provides WHERE clause for building SQL query
     *
     * @return string Where clause
     */
    private function _getWhereClause()
    {
        $where_clause = '';
        $criteria_cnt = 0;
        for (
        $column_index = 0;
        $column_index < $this->_criteria_column_count;
        $column_index++
        ) {
            if (! empty($this->_formColumns[$column_index])
                && ! empty($this->_formCriterions[$column_index])
                && $column_index
                && isset($last_where)
                && isset($this->_formAndOrCols)
            ) {
                $where_clause .= ' '
                    . mb_strtoupper($this->_formAndOrCols[$last_where])
                    . ' ';
            }
            if (! empty($this->_formColumns[$column_index])
                && ! empty($this->_formCriterions[$column_index])
            ) {
                $where_clause .= '(' . $this->_formColumns[$column_index] . ' '
                    . $this->_formCriterions[$column_index] . ')';
                $last_where = $column_index;
                $criteria_cnt++;
            }
        } // end for
        if ($criteria_cnt > 1) {
            $where_clause = '(' . $where_clause . ')';
        }
        // OR rows ${'cur' . $or}[$column_index]
        if (! isset($this->_formAndOrRows)) {
            $this->_formAndOrRows = array();
        }
        for (
        $row_index = 0;
        $row_index <= $this->_criteria_row_count;
        $row_index++
        ) {
            $criteria_cnt = 0;
            $qry_orwhere = '';
            $last_orwhere = '';
            for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
            ) {
                if (! empty($this->_formColumns[$column_index])
                    && ! empty($_REQUEST['Or' . $row_index][$column_index])
                    && $column_index
                ) {
                    $qry_orwhere .= ' '
                        . mb_strtoupper(
                            $this->_formAndOrCols[$last_orwhere]
                        )
                        . ' ';
                }
                if (! empty($this->_formColumns[$column_index])
                    && ! empty($_REQUEST['Or' . $row_index][$column_index])
                ) {
                    $qry_orwhere .= '(' . $this->_formColumns[$column_index]
                        .  ' '
                        .  $_REQUEST['Or' . $row_index][$column_index]
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
                    .  mb_strtoupper(
                        isset($this->_formAndOrRows[$row_index])
                        ? $this->_formAndOrRows[$row_index] . ' '
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
     * @return string Order By clause
     */
    private function _getOrderByClause()
    {
        $orderby_clause = '';
        $orderby_clauses = array();

        // Create copy of instance variables
        $columns = $this->_formColumns;
        $sort = $this->_formSorts;
        $sortOrder = $this->_formSortOrders;
        if (!empty($sortOrder)
            && count($sortOrder) == count($sort)
            && count($sortOrder) == count($columns)
        ) {
            // Sort all three arrays based on sort order
            array_multisort($sortOrder, $sort, $columns);
        }

        for (
            $column_index = 0;
            $column_index < $this->_criteria_column_count;
            $column_index++
        ) {
            // if all columns are chosen with * selector,
            // then sorting isn't available
            // Fix for Bug #570698
            if (empty($columns[$column_index])
                && empty($sort[$column_index])
            ) {
                continue;
            }

            if (mb_substr($columns[$column_index], -2) == '.*') {
                continue;
            }

            if (! empty($sort[$column_index])) {
                $orderby_clauses[] = $columns[$column_index] . ' '
                    . $sort[$column_index];
            }
        } // end for
        if (!empty($orderby_clauses)) {
            $orderby_clause = 'ORDER BY '
                . htmlspecialchars(implode(", ", $orderby_clauses)) . "\n";
        }
        return $orderby_clause;
    }

    /**
     * Provides UNIQUE columns and INDEX columns present in criteria tables
     *
     * @param array $search_tables        Tables involved in the search
     * @param array $search_columns       Columns involved in the search
     * @param array $where_clause_columns Columns having criteria where clause
     *
     * @return array having UNIQUE and INDEX columns
     */
    private function _getIndexes(array $search_tables, array $search_columns,
        array $where_clause_columns
    ) {
        $unique_columns = array();
        $index_columns = array();

        foreach ($search_tables as $table) {
            $indexes = $GLOBALS['dbi']->getTableIndexes($this->_db, $table);
            foreach ($indexes as $index) {
                $column = $table . '.' . $index['Column_name'];
                if (isset($search_columns[$column])) {
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
     * @param array $search_tables        Tables involved in the search
     * @param array $search_columns       Columns involved in the search
     * @param array $where_clause_columns Columns having criteria where clause
     *
     * @return array having UNIQUE and INDEX columns
     */
    private function _getLeftJoinColumnCandidates(array $search_tables, array $search_columns,
        array $where_clause_columns
    ) {
        $GLOBALS['dbi']->selectDb($this->_db);

        // Get unique columns and index columns
        $indexes = $this->_getIndexes(
            $search_tables, $search_columns, $where_clause_columns
        );
        $unique_columns = $indexes['unique'];
        $index_columns = $indexes['index'];

        list($candidate_columns, $needsort)
            = $this->_getLeftJoinColumnCandidatesBest(
                $search_tables,
                $where_clause_columns,
                $unique_columns,
                $index_columns
            );

        // If we came up with $unique_columns (very good) or $index_columns (still
        // good) as $candidate_columns we want to check if we have any 'Y' there
        // (that would mean that they were also found in the whereclauses
        // which would be great). if yes, we take only those
        if ($needsort != 1) {
            return $candidate_columns;
        }

        $very_good = array();
        $still_good = array();
        foreach ($candidate_columns as $column => $is_where) {
            $table = explode('.', $column);
            $table = $table[0];
            if ($is_where == 'Y') {
                $very_good[$column] = $table;
            } else {
                $still_good[$column] = $table;
            }
        }
        if (count($very_good) > 0) {
            $candidate_columns = $very_good;
            // Candidates restricted in index+where
        } else {
            $candidate_columns = $still_good;
            // None of the candidates where in a where-clause
        }

        return $candidate_columns;
    }

    /**
     * Provides the main table to form the LEFT JOIN clause
     *
     * @param array $search_tables        Tables involved in the search
     * @param array $search_columns       Columns involved in the search
     * @param array $where_clause_columns Columns having criteria where clause
     * @param array $where_clause_tables  Tables having criteria where clause
     *
     * @return string table name
     */
    private function _getMasterTable(array $search_tables, array $search_columns,
        array $where_clause_columns, array $where_clause_tables
    ) {
        if (count($where_clause_tables) == 1) {
            // If there is exactly one column that has a decent where-clause
            // we will just use this
            $master = key($where_clause_tables);
            return $master;
        }

        // Now let's find out which of the tables has an index
        // (When the control user is the same as the normal user
        // because he is using one of his databases as pmadb,
        // the last db selected is not always the one where we need to work)
        $candidate_columns = $this->_getLeftJoinColumnCandidates(
            $search_tables, $search_columns, $where_clause_columns
        );

        // Generally, we need to display all the rows of foreign (referenced)
        // table, whether they have any matching row in child table or not.
        // So we select candidate tables which are foreign tables.
        $foreign_tables = array();
        foreach ($candidate_columns as $one_table) {
            $foreigners = $this->relation->getForeigners($this->_db, $one_table);
            foreach ($foreigners as $key => $foreigner) {
                if ($key != 'foreign_keys_data') {
                    if (in_array($foreigner['foreign_table'], $candidate_columns)) {
                        $foreign_tables[$foreigner['foreign_table']]
                            = $foreigner['foreign_table'];
                    }
                    continue;
                }
                foreach ($foreigner as $one_key) {
                    if (in_array($one_key['ref_table_name'], $candidate_columns)) {
                        $foreign_tables[$one_key['ref_table_name']]
                            = $one_key['ref_table_name'];
                    }
                }
            }
        }
        if (count($foreign_tables)) {
            $candidate_columns = $foreign_tables;
        }

        // If our array of candidates has more than one member we'll just
        // find the smallest table.
        // Of course the actual query would be faster if we check for
        // the Criteria which gives the smallest result set in its table,
        // but it would take too much time to check this
        if (!(count($candidate_columns) > 1)) {
            // Only one single candidate
            return reset($candidate_columns);
        }

        // Of course we only want to check each table once
        $checked_tables = $candidate_columns;
        $tsize = array();
        $maxsize = -1;
        $result = '';
        foreach ($candidate_columns as $table) {
            if ($checked_tables[$table] != 1) {
                $_table = new Table($table, $this->_db);
                $tsize[$table] = $_table->countRecords();
                $checked_tables[$table] = 1;
            }
            if ($tsize[$table] > $maxsize) {
                $maxsize = $tsize[$table];
                $result = $table;
            }
        }
        // Return largest table
        return $result;
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
        for (
            $column_index = 0, $nb = count($this->_criteria);
            $column_index < $nb;
            $column_index++
        ) {
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
                if (mb_substr($this->_criteria[$column_index], 0, 1) == '='
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
     * @param array $formColumns List of selected columns in the form
     *
     * @return string FROM clause
     */
    private function _getFromClause(array $formColumns)
    {
        $from_clause = '';
        if (empty($formColumns)) {
            return $from_clause;
        }

        // Initialize some variables
        $search_tables = $search_columns = array();

        // We only start this if we have fields, otherwise it would be dumb
        foreach ($formColumns as $value) {
            $parts = explode('.', $value);
            if (! empty($parts[0]) && ! empty($parts[1])) {
                $table = str_replace('`', '', $parts[0]);
                $search_tables[$table] = $table;
                $search_columns[] = $table . '.' . str_replace(
                    '`', '', $parts[1]
                );
            }
        } // end while

        // Create LEFT JOINS out of Relations
        $from_clause = $this->_getJoinForFromClause(
            $search_tables, $search_columns
        );

        // In case relations are not defined, just generate the FROM clause
        // from the list of tables, however we don't generate any JOIN
        if (empty($from_clause)) {
            // Create cartesian product
            $from_clause = implode(
                ", ", array_map(array('PhpMyAdmin\Util', 'backquote'), $search_tables)
            );
        }

        return $from_clause;
    }

    /**
     * Formulates the WHERE clause by JOINing tables
     *
     * @param array $searchTables  Tables involved in the search
     * @param array $searchColumns Columns involved in the search
     *
     * @return string table name
     */
    private function _getJoinForFromClause(array $searchTables, array $searchColumns)
    {
        // $relations[master_table][foreign_table] => clause
        $relations = array();

        // Fill $relations with inter table relationship data
        foreach ($searchTables as $oneTable) {
            $this->_loadRelationsForTable($relations, $oneTable);
        }

        // Get tables and columns with valid where clauses
        $validWhereClauses = $this->_getWhereClauseTablesAndColumns();
        $whereClauseTables = $validWhereClauses['where_clause_tables'];
        $whereClauseColumns = $validWhereClauses['where_clause_columns'];

        // Get master table
        $master = $this->_getMasterTable(
            $searchTables, $searchColumns,
            $whereClauseColumns, $whereClauseTables
        );

        // Will include master tables and all tables that can be combined into
        // a cluster by their relation
        $finalized = array();
        if (strlen($master) > 0) {
            // Add master tables
            $finalized[$master] = '';
        }
        // Fill the $finalized array with JOIN clauses for each table
        $this->_fillJoinClauses($finalized, $relations, $searchTables);

        // JOIN clause
        $join = '';

        // Tables that can not be combined with the table cluster
        // which includes master table
        $unfinalized = array_diff($searchTables, array_keys($finalized));
        if (count($unfinalized) > 0) {

            // We need to look for intermediary tables to JOIN unfinalized tables
            // Heuristic to chose intermediary tables is to look for tables
            // having relationships with unfinalized tables
            foreach ($unfinalized as $oneTable) {

                $references = $this->relation->getChildReferences($this->_db, $oneTable);
                foreach ($references as $column => $columnReferences) {
                    foreach ($columnReferences as $reference) {

                        // Only from this schema
                        if ($reference['table_schema'] != $this->_db) {
                            continue;
                        }

                        $table = $reference['table_name'];

                        $this->_loadRelationsForTable($relations, $table);

                        // Make copies
                        $tempFinalized = $finalized;
                        $tempSearchTables = $searchTables;
                        $tempSearchTables[] = $table;

                        // Try joining with the added table
                        $this->_fillJoinClauses(
                            $tempFinalized, $relations, $tempSearchTables
                        );

                        $tempUnfinalized = array_diff(
                            $tempSearchTables, array_keys($tempFinalized)
                        );
                        // Take greedy approach.
                        // If the unfinalized count drops we keep the new table
                        // and switch temporary varibles with the original ones
                        if (count($tempUnfinalized) < count($unfinalized)) {
                            $finalized = $tempFinalized;
                            $searchTables = $tempSearchTables;
                        }

                        // We are done if no unfinalized tables anymore
                        if (count($tempUnfinalized) == 0) {
                            break 3;
                        }
                    }
                }
            }

            $unfinalized = array_diff($searchTables, array_keys($finalized));
            // If there are still unfinalized tables
            if (count($unfinalized) > 0) {
                // Add these tables as cartesian product before joined tables
                $join .= implode(
                    ', ', array_map(array('PhpMyAdmin\Util', 'backquote'), $unfinalized)
                );
            }
        }

        $first = true;
        // Add joined tables
        foreach ($finalized as $table => $clause) {
            if ($first) {
                if (! empty($join)) {
                    $join .= ", ";
                }
                $join .= Util::backquote($table);
                $first = false;
            } else {
                $join .= "\n    LEFT JOIN " . Util::backquote(
                    $table
                ) . " ON " . $clause;
            }
        }

        return $join;
    }

    /**
     * Loads relations for a given table into the $relations array
     *
     * @param array  &$relations array of relations
     * @param string $oneTable   the table
     *
     * @return void
     */
    private function _loadRelationsForTable(array &$relations, $oneTable)
    {
        $relations[$oneTable] = array();

        $foreigners = $this->relation->getForeigners($GLOBALS['db'], $oneTable);
        foreach ($foreigners as $field => $foreigner) {
            // Foreign keys data
            if ($field == 'foreign_keys_data') {
                foreach ($foreigner as $oneKey) {
                    $clauses = array();
                    // There may be multiple column relations
                    foreach ($oneKey['index_list'] as $index => $oneField) {
                        $clauses[]
                            = Util::backquote($oneTable) . "."
                            . Util::backquote($oneField) . " = "
                            . Util::backquote($oneKey['ref_table_name']) . "."
                            . Util::backquote($oneKey['ref_index_list'][$index]);
                    }
                    // Combine multiple column relations with AND
                    $relations[$oneTable][$oneKey['ref_table_name']]
                        = implode(" AND ", $clauses);
                }
            } else { // Internal relations
                $relations[$oneTable][$foreigner['foreign_table']]
                    = Util::backquote($oneTable) . "."
                    . Util::backquote($field) . " = "
                    . Util::backquote($foreigner['foreign_table']) . "."
                    . Util::backquote($foreigner['foreign_field']);
            }
        }
    }

    /**
     * Fills the $finalized arrays with JOIN clauses for each of the tables
     *
     * @param array &$finalized   JOIN clauses for each table
     * @param array $relations    Relations among tables
     * @param array $searchTables Tables involved in the search
     *
     * @return void
     */
    private function _fillJoinClauses(array &$finalized, array $relations, array $searchTables)
    {
        while (true) {
            $added = false;
            foreach ($searchTables as $masterTable) {
                $foreignData = $relations[$masterTable];
                foreach ($foreignData as $foreignTable => $clause) {
                    if (! isset($finalized[$masterTable])
                        && isset($finalized[$foreignTable])
                    ) {
                        $finalized[$masterTable] = $clause;
                        $added = true;
                    } elseif (! isset($finalized[$foreignTable])
                        && isset($finalized[$masterTable])
                        && in_array($foreignTable, $searchTables)
                    ) {
                        $finalized[$foreignTable] = $clause;
                        $added = true;
                    }
                    if ($added) {
                        // We are done if all tables are in $finalized
                        if (count($finalized) == count($searchTables)) {
                            return;
                        }
                    }
                }
            }
            // If no new tables were added during this iteration, break;
            if (! $added) {
                return;
            }
        }
    }

    /**
     * Provides the generated SQL query
     *
     * @param array $formColumns List of selected columns in the form
     *
     * @return string SQL query
     */
    private function _getSQLQuery(array $formColumns)
    {
        $sql_query = '';
        // get SELECT clause
        $sql_query .= $this->_getSelectClause();
        // get FROM clause
        $from_clause = $this->_getFromClause($formColumns);
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
     * @return string QBE form
     */
    public function getSelectionForm()
    {
        $html_output = '<form action="db_qbe.php" method="post" id="formQBE" '
            . 'class="lock-page">';
        $html_output .= '<div class="width100">';
        $html_output .= '<fieldset>';

        if ($GLOBALS['cfgRelation']['savedsearcheswork']) {
            $html_output .= $this->_getSavedSearchesField();
        }

        $html_output .= '<div class="responsivetable jsresponsive">';
        $html_output .= '<table class="data" style="width: 100%;">';
        // Get table's <tr> elements
        $html_output .= $this->_getColumnNamesRow();
        $html_output .= $this->_getColumnAliasRow();
        $html_output .= $this->_getShowRow();
        $html_output .= $this->_getSortRow();
        $html_output .= $this->_getSortOrder();
        $html_output .= $this->_getCriteriaInputboxRow();
        $html_output .= $this->_getInsDelAndOrCriteriaRows();
        $html_output .= $this->_getModifyColumnsRow();
        $html_output .= '</table>';
        $this->_new_row_count--;
        $url_params = array();
        $url_params['db'] = $this->_db;
        $url_params['criteriaColumnCount'] = $this->_new_column_count;
        $url_params['rows'] = $this->_new_row_count;
        $html_output .= Url::getHiddenInputs($url_params);
        $html_output .= '</div>';
        $html_output .= '</fieldset>';
        $html_output .= '</div>';
        // get footers
        $html_output .= $this->_getTableFooters();
        // get tables select list
        $html_output .= $this->_getTablesList();
        $html_output .= '</form>';
        $html_output .= '<form action="db_qbe.php" method="post" class="lock-page">';
        $html_output .= Url::getHiddenInputs(array('db' => $this->_db));
        // get SQL query
        $html_output .= '<div class="floatleft desktop50">';
        $html_output .= '<fieldset>';
        $html_output .= '<legend>'
            . sprintf(
                __('SQL query on database <b>%s</b>:'),
                Util::getDbLink($this->_db)
            );
        $html_output .= '</legend>';
        $text_dir = 'ltr';
        $html_output .= '<textarea cols="80" name="sql_query" id="textSqlquery"'
            . ' rows="' . ((count($this->_criteriaTables) > 30) ? '15' : '7') . '"'
            . ' dir="' . $text_dir . '">';

        if (empty($this->_formColumns)) {
            $this->_formColumns = array();
        }
        $html_output .= $this->_getSQLQuery($this->_formColumns);

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

    /**
     * Get fields to display
     *
     * @return string
     */
    private function _getSavedSearchesField()
    {
        $html_output = __('Saved bookmarked search:');
        $html_output .= ' <select name="searchId" id="searchId">';
        $html_output .= '<option value="">' . __('New bookmark') . '</option>';

        $currentSearch = $this->_getCurrentSearch();
        $currentSearchId = null;
        $currentSearchName = null;
        if (null != $currentSearch) {
            $currentSearchId = $currentSearch->getId();
            $currentSearchName = $currentSearch->getSearchName();
        }

        foreach ($this->_savedSearchList as $id => $name) {
            $html_output .= '<option value="' . htmlspecialchars($id)
                . '" ' . (
                $id == $currentSearchId
                    ? 'selected="selected" '
                    : ''
                )
                . '>'
                . htmlspecialchars($name)
                . '</option>';
        }
        $html_output .= '</select>';
        $html_output .= '<input type="text" name="searchName" id="searchName" '
            . 'value="' . htmlspecialchars($currentSearchName) . '" />';
        $html_output .= '<input type="hidden" name="action" id="action" value="" />';
        $html_output .= '<input type="submit" name="saveSearch" id="saveSearch" '
            . 'value="' . __('Create bookmark') . '" />';
        if (null !== $currentSearchId) {
            $html_output .= '<input type="submit" name="updateSearch" '
                . 'id="updateSearch" value="' . __('Update bookmark') . '" />';
            $html_output .= '<input type="submit" name="deleteSearch" '
                . 'id="deleteSearch" value="' . __('Delete bookmark') . '" />';
        }

        return $html_output;
    }

    /**
     * Initialize _criteria_column_count
     *
     * @return int Previous number of columns
     */
    private function _initializeCriteriasCount()
    {
        // sets column count
        $criteriaColumnCount = Core::ifSetOr(
            $_REQUEST['criteriaColumnCount'],
            3,
            'numeric'
        );
        $criteriaColumnAdd = Core::ifSetOr(
            $_REQUEST['criteriaColumnAdd'],
            0,
            'numeric'
        );
        $this->_criteria_column_count = max(
            $criteriaColumnCount + $criteriaColumnAdd,
            0
        );

        // sets row count
        $rows = Core::ifSetOr($_REQUEST['rows'], 0, 'numeric');
        $criteriaRowAdd = Core::ifSetOr($_REQUEST['criteriaRowAdd'], 0, 'numeric');
        $this->_criteria_row_count = min(
            100,
            max($rows + $criteriaRowAdd, 0)
        );

        return $criteriaColumnCount;
    }

    /**
     * Get best
     *
     * @param array $search_tables        Tables involved in the search
     * @param array $where_clause_columns Columns with where clause
     * @param array $unique_columns       Unique columns
     * @param array $index_columns        Indexed columns
     *
     * @return array
     */
    private function _getLeftJoinColumnCandidatesBest(
        array $search_tables, array $where_clause_columns, array $unique_columns, array $index_columns
    ) {
        // now we want to find the best.
        if (isset($unique_columns) && count($unique_columns) > 0) {
            $candidate_columns = $unique_columns;
            $needsort = 1;
            return array($candidate_columns, $needsort);
        } elseif (isset($index_columns) && count($index_columns) > 0) {
            $candidate_columns = $index_columns;
            $needsort = 1;
            return array($candidate_columns, $needsort);
        } elseif (isset($where_clause_columns) && count($where_clause_columns) > 0) {
            $candidate_columns = $where_clause_columns;
            $needsort = 0;
            return array($candidate_columns, $needsort);
        }

        $candidate_columns = $search_tables;
        $needsort = 0;
        return array($candidate_columns, $needsort);
    }
}
