<?php
/**
 * Handles DB QBE search
 */

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\SavedSearches;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use function array_diff;
use function array_fill;
use function array_keys;
use function array_map;
use function array_multisort;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function key;
use function max;
use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;
use function min;
use function reset;
use function str_replace;
use function stripos;
use function strlen;

/**
 * Class to handle database QBE search
 */
class Qbe
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $db;
    /**
     * Table Names (selected/non-selected)
     *
     * @access private
     * @var array
     */
    private $criteriaTables;
    /**
     * Column Names
     *
     * @access private
     * @var array
     */
    private $columnNames;
    /**
     * Number of columns
     *
     * @access private
     * @var int
     */
    private $criteriaColumnCount;
    /**
     * Number of Rows
     *
     * @access private
     * @var int
     */
    private $criteriaRowCount;
    /**
     * Whether to insert a new column
     *
     * @access private
     * @var array
     */
    private $criteriaColumnInsert;
    /**
     * Whether to delete a column
     *
     * @access private
     * @var array
     */
    private $criteriaColumnDelete;
    /**
     * Whether to insert a new row
     *
     * @access private
     * @var array
     */
    private $criteriaRowInsert;
    /**
     * Whether to delete a row
     *
     * @access private
     * @var array
     */
    private $criteriaRowDelete;
    /**
     * Already set criteria values
     *
     * @access private
     * @var array
     */
    private $criteria;
    /**
     * Previously set criteria values
     *
     * @access private
     * @var array
     */
    private $prevCriteria;
    /**
     * AND/OR relation b/w criteria columns
     *
     * @access private
     * @var array
     */
    private $criteriaAndOrColumn;
    /**
     * AND/OR relation b/w criteria rows
     *
     * @access private
     * @var array
     */
    private $criteriaAndOrRow;
    /**
     * Large width of a column
     *
     * @access private
     * @var string
     */
    private $realwidth;
    /**
     * Minimum width of a column
     *
     * @access private
     * @var int
     */
    private $formColumnWidth;
    /**
     * Selected columns in the form
     *
     * @access private
     * @var array
     */
    private $formColumns;
    /**
     * Entered aliases in the form
     *
     * @access private
     * @var array
     */
    private $formAliases;
    /**
     * Chosen sort options in the form
     *
     * @access private
     * @var array
     */
    private $formSorts;
    /**
     * Chosen sort orders in the form
     *
     * @access private
     * @var array
     */
    private $formSortOrders;
    /**
     * Show checkboxes in the form
     *
     * @access private
     * @var array
     */
    private $formShows;
    /**
     * Entered criteria values in the form
     *
     * @access private
     * @var array
     */
    private $formCriterions;
    /**
     * AND/OR column radio buttons in the form
     *
     * @access private
     * @var array
     */
    private $formAndOrCols;
    /**
     * AND/OR row radio buttons in the form
     *
     * @access private
     * @var array
     */
    private $formAndOrRows;
    /**
     * New column count in case of add/delete
     *
     * @access private
     * @var int
     */
    private $newColumnCount;
    /**
     * New row count in case of add/delete
     *
     * @access private
     * @var int
     */
    private $newRowCount;
    /**
     * List of saved searches
     *
     * @access private
     * @var array
     */
    private $savedSearchList = null;
    /**
     * Current search
     *
     * @access private
     * @var SavedSearches
     */
    private $currentSearch = null;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    public $dbi;

    /** @var Template */
    public $template;

    /**
     * @param Relation          $relation        Relation object
     * @param Template          $template        Template object
     * @param DatabaseInterface $dbi             DatabaseInterface object
     * @param string            $dbname          Database name
     * @param array             $savedSearchList List of saved searches
     * @param SavedSearches     $currentSearch   Current search id
     */
    public function __construct(
        Relation $relation,
        Template $template,
        $dbi,
        $dbname,
        array $savedSearchList = [],
        $currentSearch = null
    ) {
        $this->db = $dbname;
        $this->savedSearchList = $savedSearchList;
        $this->currentSearch = $currentSearch;
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->template = $template;

        $this->loadCriterias();
        // Sets criteria parameters
        $this->setSearchParams();
        $this->setCriteriaTablesAndColumns();
    }

    /**
     * Initialize criterias
     *
     * @return static
     */
    private function loadCriterias()
    {
        if ($this->currentSearch === null
            || $this->currentSearch->getCriterias() === null
        ) {
            return $this;
        }

        $criterias = $this->currentSearch->getCriterias();
        $_POST = $criterias + $_POST;

        return $this;
    }

    /**
     * Getter for current search
     *
     * @return SavedSearches
     */
    private function getCurrentSearch()
    {
        return $this->currentSearch;
    }

    /**
     * Sets search parameters
     *
     * @return void
     */
    private function setSearchParams()
    {
        $criteriaColumnCount = $this->initializeCriteriasCount();

        $this->criteriaColumnInsert = Core::ifSetOr(
            $_POST['criteriaColumnInsert'],
            null,
            'array'
        );
        $this->criteriaColumnDelete = Core::ifSetOr(
            $_POST['criteriaColumnDelete'],
            null,
            'array'
        );

        $this->prevCriteria = $_POST['prev_criteria'] ?? [];
        $this->criteria = $_POST['criteria'] ?? array_fill(0, $criteriaColumnCount, '');

        $this->criteriaRowInsert = $_POST['criteriaRowInsert'] ?? array_fill(0, $criteriaColumnCount, '');
        $this->criteriaRowDelete = $_POST['criteriaRowDelete'] ?? array_fill(0, $criteriaColumnCount, '');
        $this->criteriaAndOrRow = $_POST['criteriaAndOrRow'] ?? array_fill(0, $criteriaColumnCount, '');
        $this->criteriaAndOrColumn = $_POST['criteriaAndOrColumn'] ?? array_fill(0, $criteriaColumnCount, '');
        // sets minimum width
        $this->formColumnWidth = 12;
        $this->formColumns = [];
        $this->formSorts = [];
        $this->formShows = [];
        $this->formCriterions = [];
        $this->formAndOrRows = [];
        $this->formAndOrCols = [];
    }

    /**
     * Sets criteria tables and columns
     *
     * @return void
     */
    private function setCriteriaTablesAndColumns()
    {
        // The tables list sent by a previously submitted form
        if (Core::isValid($_POST['TableList'], 'array')) {
            foreach ($_POST['TableList'] as $each_table) {
                $this->criteriaTables[$each_table] = ' selected="selected"';
            }
        }
        $all_tables = $this->dbi->query(
            'SHOW TABLES FROM ' . Util::backquote($this->db) . ';',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $all_tables_count = $this->dbi->numRows($all_tables);
        if ($all_tables_count == 0) {
            echo Message::error(__('No tables found in database.'))->getDisplay();
            exit;
        }
        // The tables list gets from MySQL
        while ([$table] = $this->dbi->fetchRow($all_tables)) {
            $columns = $this->dbi->getColumns($this->db, $table);

            if (empty($this->criteriaTables[$table])
                && ! empty($_POST['TableList'])
            ) {
                $this->criteriaTables[$table] = '';
            } else {
                $this->criteriaTables[$table] = ' selected="selected"';
            }

            // The fields list per selected tables
            if ($this->criteriaTables[$table] !== ' selected="selected"') {
                continue;
            }

            $each_table = Util::backquote($table);
            $this->columnNames[]  = $each_table . '.*';
            foreach ($columns as $each_column) {
                $each_column = $each_table . '.'
                    . Util::backquote($each_column['Field']);
                $this->columnNames[] = $each_column;
                // increase the width if necessary
                $this->formColumnWidth = max(
                    mb_strlen($each_column),
                    $this->formColumnWidth
                );
            }
        }
        $this->dbi->freeResult($all_tables);

        // sets the largest width found
        $this->realwidth = $this->formColumnWidth . 'ex';
    }

    /**
     * Provides select options list containing column names
     *
     * @param int    $column_number Column Number (0,1,2) or more
     * @param string $selected      Selected criteria column name
     *
     * @return string HTML for select options
     */
    private function showColumnSelectCell($column_number, $selected = '')
    {
        return $this->template->render('database/qbe/column_select_cell', [
            'column_number' => $column_number,
            'column_names' => $this->columnNames,
            'selected' => $selected,
        ]);
    }

    /**
     * Provides select options list containing sort options (ASC/DESC)
     *
     * @param int    $columnNumber Column Number (0,1,2) or more
     * @param string $selected     Selected criteria 'ASC' or 'DESC'
     *
     * @return string HTML for select options
     */
    private function getSortSelectCell(
        $columnNumber,
        $selected = ''
    ) {
        return $this->template->render('database/qbe/sort_select_cell', [
            'real_width' => $this->realwidth,
            'column_number' => $columnNumber,
            'selected' => $selected,
        ]);
    }

    /**
     * Provides select options list containing sort order
     *
     * @param int $columnNumber Column Number (0,1,2) or more
     * @param int $sortOrder    Sort order
     *
     * @return string HTML for select options
     */
    private function getSortOrderSelectCell($columnNumber, $sortOrder)
    {
        $totalColumnCount = $this->getNewColumnCount();

        return $this->template->render('database/qbe/sort_order_select_cell', [
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
    private function getNewColumnCount()
    {
        $totalColumnCount = $this->criteriaColumnCount;
        if (! empty($this->criteriaColumnInsert)) {
            $totalColumnCount += count($this->criteriaColumnInsert);
        }
        if (! empty($this->criteriaColumnDelete)) {
            $totalColumnCount -= count($this->criteriaColumnDelete);
        }

        return $totalColumnCount;
    }

    /**
     * Provides search form's row containing column select options
     *
     * @return string HTML for search table's row
     */
    private function getColumnNamesRow()
    {
        $html_output = '';

        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (isset($this->criteriaColumnInsert[$column_index])
                && $this->criteriaColumnInsert[$column_index] === 'on'
            ) {
                $html_output .= $this->showColumnSelectCell(
                    $new_column_count
                );
                $new_column_count++;
            }
            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$column_index])
                && $this->criteriaColumnDelete[$column_index] === 'on'
            ) {
                continue;
            }
            $selected = '';
            if (isset($_POST['criteriaColumn'][$column_index])) {
                $selected = $_POST['criteriaColumn'][$column_index];
                $this->formColumns[$new_column_count]
                    = $_POST['criteriaColumn'][$column_index];
            }
            $html_output .= $this->showColumnSelectCell(
                $new_column_count,
                $selected
            );
            $new_column_count++;
        }
        $this->newColumnCount = $new_column_count;

        return $html_output;
    }

    /**
     * Provides search form's row containing column aliases
     *
     * @return string HTML for search table's row
     */
    private function getColumnAliasRow()
    {
        $html_output = '';

        $new_column_count = 0;

        for ($colInd = 0; $colInd < $this->criteriaColumnCount; $colInd++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$colInd])
                && $this->criteriaColumnInsert[$colInd] === 'on'
            ) {
                $html_output .= '<td class="text-center">';
                $html_output .= '<input type="text"'
                    . ' name="criteriaAlias[' . $new_column_count . ']">';
                $html_output .= '</td>';
                $new_column_count++;
            }

            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$colInd])
                && $this->criteriaColumnDelete[$colInd] === 'on'
            ) {
                continue;
            }

            $tmp_alias = '';
            if (! empty($_POST['criteriaAlias'][$colInd])) {
                $tmp_alias
                    = $this->formAliases[$new_column_count]
                        = $_POST['criteriaAlias'][$colInd];
            }

            $html_output .= '<td class="text-center">';
            $html_output .= '<input type="text"'
                . ' name="criteriaAlias[' . $new_column_count . ']"'
                . ' value="' . htmlspecialchars($tmp_alias) . '">';
            $html_output .= '</td>';
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides search form's row containing sort(ASC/DESC) select options
     *
     * @return string HTML for search table's row
     */
    private function getSortRow()
    {
        $html_output = '';

        $new_column_count = 0;

        for ($colInd = 0; $colInd < $this->criteriaColumnCount; $colInd++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$colInd])
                && $this->criteriaColumnInsert[$colInd] === 'on'
            ) {
                $html_output .= $this->getSortSelectCell($new_column_count);
                $new_column_count++;
            }

            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$colInd])
                && $this->criteriaColumnDelete[$colInd] === 'on'
            ) {
                continue;
            }
            // If they have chosen all fields using the * selector,
            // then sorting is not available, Fix for Bug #570698
            if (isset($_POST['criteriaSort'][$colInd], $_POST['criteriaColumn'][$colInd])
                && mb_substr($_POST['criteriaColumn'][$colInd], -2) === '.*'
            ) {
                $_POST['criteriaSort'][$colInd] = '';
            }

            $selected = '';
            if (isset($_POST['criteriaSort'][$colInd])) {
                $this->formSorts[$new_column_count]
                    = $_POST['criteriaSort'][$colInd];

                if ($_POST['criteriaSort'][$colInd] === 'ASC') {
                    $selected = 'ASC';
                } elseif ($_POST['criteriaSort'][$colInd] === 'DESC') {
                    $selected = 'DESC';
                }
            } else {
                $this->formSorts[$new_column_count] = '';
            }

            $html_output .= $this->getSortSelectCell(
                $new_column_count,
                $selected
            );
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides search form's row containing sort order
     *
     * @return string HTML for search table's row
     */
    private function getSortOrder()
    {
        $html_output = '';

        $new_column_count = 0;

        for ($colInd = 0; $colInd < $this->criteriaColumnCount; $colInd++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$colInd])
                && $this->criteriaColumnInsert[$colInd] === 'on'
            ) {
                $html_output .= $this->getSortOrderSelectCell(
                    $new_column_count,
                    null
                );
                $new_column_count++;
            }

            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$colInd])
                && $this->criteriaColumnDelete[$colInd] === 'on'
            ) {
                continue;
            }

            $sortOrder = null;
            if (! empty($_POST['criteriaSortOrder'][$colInd])) {
                $sortOrder
                    = $this->formSortOrders[$new_column_count]
                        = $_POST['criteriaSortOrder'][$colInd];
            }

            $html_output .= $this->getSortOrderSelectCell(
                $new_column_count,
                $sortOrder
            );
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides search form's row containing SHOW checkboxes
     *
     * @return string HTML for search table's row
     */
    private function getShowRow()
    {
        $html_output = '';

        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$column_index])
                && $this->criteriaColumnInsert[$column_index] === 'on'
            ) {
                $html_output .= '<td class="text-center">';
                $html_output .= '<input type="checkbox"'
                    . ' name="criteriaShow[' . $new_column_count . ']">';
                $html_output .= '</td>';
                $new_column_count++;
            }
            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$column_index])
                && $this->criteriaColumnDelete[$column_index] === 'on'
            ) {
                continue;
            }
            if (isset($_POST['criteriaShow'][$column_index])) {
                $checked_options = ' checked="checked"';
                $this->formShows[$new_column_count]
                    = $_POST['criteriaShow'][$column_index];
            } else {
                $checked_options = '';
            }
            $html_output .= '<td class="text-center">';
            $html_output .= '<input type="checkbox"'
                . ' name="criteriaShow[' . $new_column_count . ']"'
                . $checked_options . '>';
            $html_output .= '</td>';
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides search form's row containing criteria Inputboxes
     *
     * @return string HTML for search table's row
     */
    private function getCriteriaInputboxRow()
    {
        $html_output = '';

        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$column_index])
                && $this->criteriaColumnInsert[$column_index] === 'on'
            ) {
                $html_output .= '<td class="text-center">';
                $html_output .= '<input type="text"'
                    . ' name="criteria[' . $new_column_count . ']"'
                    . ' class="textfield"'
                    . ' style="width: ' . $this->realwidth . '"'
                    . ' size="20">';
                $html_output .= '</td>';
                $new_column_count++;
            }
            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$column_index])
                && $this->criteriaColumnDelete[$column_index] === 'on'
            ) {
                continue;
            }
            $tmp_criteria = '';
            if (isset($this->criteria[$column_index])) {
                $tmp_criteria = $this->criteria[$column_index];
            }
            if ((empty($this->prevCriteria)
                || ! isset($this->prevCriteria[$column_index]))
                || $this->prevCriteria[$column_index] != htmlspecialchars($tmp_criteria)
            ) {
                $this->formCriterions[$new_column_count] = $tmp_criteria;
            } else {
                $this->formCriterions[$new_column_count]
                    = $this->prevCriteria[$column_index];
            }
            $html_output .= '<td class="text-center">';
            $html_output .= '<input type="hidden"'
                . ' name="prev_criteria[' . $new_column_count . ']"'
                . ' value="'
                . htmlspecialchars($this->formCriterions[$new_column_count])
                . '">';
            $html_output .= '<input type="text"'
                . ' name="criteria[' . $new_column_count . ']"'
                . ' value="' . htmlspecialchars($tmp_criteria) . '"'
                . ' class="textfield"'
                . ' style="width: ' . $this->realwidth . '"'
                . ' size="20">';
            $html_output .= '</td>';
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides And/Or modification cell along with Insert/Delete options
     * (For modifying search form's table columns)
     *
     * @param int        $column_number Column Number (0,1,2) or more
     * @param array|null $selected      Selected criteria column name
     * @param bool       $last_column   Whether this is the last column
     *
     * @return string HTML for modification cell
     */
    private function getAndOrColCell(
        $column_number,
        $selected = null,
        $last_column = false
    ) {
        $html_output = '<td class="text-center">';
        if (! $last_column) {
            $html_output .= '<strong>' . __('Or:') . '</strong>';
            $html_output .= '<input type="radio"'
                . ' name="criteriaAndOrColumn[' . $column_number . ']"'
                . ' value="or"' . ($selected['or'] ?? '') . '>';
            $html_output .= '&nbsp;&nbsp;<strong>' . __('And:') . '</strong>';
            $html_output .= '<input type="radio"'
                . ' name="criteriaAndOrColumn[' . $column_number . ']"'
                . ' value="and"' . ($selected['and'] ?? '') . '>';
        }
        $html_output .= '<br>' . __('Ins');
        $html_output .= '<input type="checkbox"'
            . ' name="criteriaColumnInsert[' . $column_number . ']">';
        $html_output .= '&nbsp;&nbsp;' . __('Del');
        $html_output .= '<input type="checkbox"'
            . ' name="criteriaColumnDelete[' . $column_number . ']">';
        $html_output .= '</td>';

        return $html_output;
    }

    /**
     * Provides search form's row containing column modifications options
     * (For modifying search form's table columns)
     *
     * @return string HTML for search table's row
     */
    private function getModifyColumnsRow()
    {
        $html_output = '';

        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$column_index])
                && $this->criteriaColumnInsert[$column_index] === 'on'
            ) {
                $html_output .= $this->getAndOrColCell($new_column_count);
                $new_column_count++;
            }

            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$column_index])
                && $this->criteriaColumnDelete[$column_index] === 'on'
            ) {
                continue;
            }

            if (isset($this->criteriaAndOrColumn[$column_index])) {
                $this->formAndOrCols[$new_column_count]
                    = $this->criteriaAndOrColumn[$column_index];
            }
            $checked_options = [];
            if (isset($this->criteriaAndOrColumn[$column_index])
                && $this->criteriaAndOrColumn[$column_index] === 'or'
            ) {
                $checked_options['or']  = ' checked="checked"';
                $checked_options['and'] = '';
            } else {
                $checked_options['and'] = ' checked="checked"';
                $checked_options['or']  = '';
            }
            $html_output .= $this->getAndOrColCell(
                $new_column_count,
                $checked_options,
                $column_index + 1 == $this->criteriaColumnCount
            );
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides rows for criteria inputbox Insert/Delete options
     * with AND/OR relationship modification options
     *
     * @param int $new_row_index New row index if rows are added/deleted
     *
     * @return string HTML table rows
     */
    private function getInputboxRow($new_row_index)
    {
        $html_output = '';
        $new_column_count = 0;
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (! empty($this->criteriaColumnInsert)
                && isset($this->criteriaColumnInsert[$column_index])
                && $this->criteriaColumnInsert[$column_index] === 'on'
            ) {
                $orFieldName = 'Or' . $new_row_index . '[' . $new_column_count . ']';
                $html_output .= '<td class="text-center">';
                $html_output .= '<input type="text"'
                    . ' name="Or' . $orFieldName . '" class="textfield"'
                    . ' style="width: ' . $this->realwidth . '" size="20">';
                $html_output .= '</td>';
                $new_column_count++;
            }
            if (! empty($this->criteriaColumnDelete)
                && isset($this->criteriaColumnDelete[$column_index])
                && $this->criteriaColumnDelete[$column_index] === 'on'
            ) {
                continue;
            }
            $or = 'Or' . $new_row_index;
            if (! empty($_POST[$or]) && isset($_POST[$or][$column_index])) {
                $tmp_or = $_POST[$or][$column_index];
            } else {
                $tmp_or     = '';
            }
            $html_output .= '<td class="text-center">';
            $html_output .= '<input type="text"'
                . ' name="Or' . $new_row_index . '[' . $new_column_count . ']"'
                . ' value="' . htmlspecialchars($tmp_or) . '" class="textfield"'
                . ' style="width: ' . $this->realwidth . '" size="20">';
            $html_output .= '</td>';
            if (! empty(${$or}) && isset(${$or}[$column_index])) {
                $GLOBALS[${'cur' . $or}][$new_column_count]
                    = ${$or}[$column_index];
            }
            $new_column_count++;
        }

        return $html_output;
    }

    /**
     * Provides rows for criteria inputbox Insert/Delete options
     * with AND/OR relationship modification options
     *
     * @return string HTML table rows
     */
    private function getInsDelAndOrCriteriaRows()
    {
        $html_output = '';
        $new_row_count = 0;
        $checked_options = [];
        for ($row_index = 0; $row_index <= $this->criteriaRowCount; $row_index++) {
            if (isset($this->criteriaRowInsert[$row_index])
                && $this->criteriaRowInsert[$row_index] === 'on'
            ) {
                $checked_options['or'] = true;
                $checked_options['and'] = false;
                $html_output .= '<tr class="noclick">';
                $html_output .= $this->template->render('database/qbe/ins_del_and_or_cell', [
                    'row_index' => $new_row_count,
                    'checked_options' => $checked_options,
                ]);
                $html_output .= $this->getInputboxRow(
                    $new_row_count
                );
                $new_row_count++;
                $html_output .= '</tr>';
            }
            if (isset($this->criteriaRowDelete[$row_index])
                && $this->criteriaRowDelete[$row_index] === 'on'
            ) {
                continue;
            }
            if (isset($this->criteriaAndOrRow[$row_index])) {
                $this->formAndOrRows[$new_row_count]
                    = $this->criteriaAndOrRow[$row_index];
            }
            if (isset($this->criteriaAndOrRow[$row_index])
                && $this->criteriaAndOrRow[$row_index] === 'and'
            ) {
                $checked_options['and'] = true;
                $checked_options['or'] = false;
            } else {
                $checked_options['or'] = true;
                $checked_options['and'] = false;
            }
            $html_output .= '<tr class="noclick">';
            $html_output .= $this->template->render('database/qbe/ins_del_and_or_cell', [
                'row_index' => $new_row_count,
                'checked_options' => $checked_options,
            ]);
            $html_output .= $this->getInputboxRow(
                $new_row_count
            );
            $new_row_count++;
            $html_output .= '</tr>';
        }
        $this->newRowCount = $new_row_count;

        return $html_output;
    }

    /**
     * Provides SELECT clause for building SQL query
     *
     * @return string Select clause
     */
    private function getSelectClause()
    {
        $select_clause = '';
        $select_clauses = [];
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (empty($this->formColumns[$column_index])
                || ! isset($this->formShows[$column_index])
                || $this->formShows[$column_index] !== 'on'
            ) {
                continue;
            }

            $select = $this->formColumns[$column_index];
            if (! empty($this->formAliases[$column_index])) {
                $select .= ' AS '
                    . Util::backquote($this->formAliases[$column_index]);
            }
            $select_clauses[] = $select;
        }
        if (! empty($select_clauses)) {
            $select_clause = 'SELECT '
                . htmlspecialchars(implode(', ', $select_clauses)) . "\n";
        }

        return $select_clause;
    }

    /**
     * Provides WHERE clause for building SQL query
     *
     * @return string Where clause
     */
    private function getWhereClause()
    {
        $where_clause = '';
        $criteria_cnt = 0;
        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            if (isset($last_where, $this->formAndOrCols)
                && ! empty($this->formColumns[$column_index])
                && ! empty($this->formCriterions[$column_index])
                && $column_index
            ) {
                $where_clause .= ' '
                    . mb_strtoupper($this->formAndOrCols[$last_where])
                    . ' ';
            }
            if (empty($this->formColumns[$column_index])
                || empty($this->formCriterions[$column_index])
            ) {
                continue;
            }

            $where_clause .= '(' . $this->formColumns[$column_index] . ' '
                . $this->formCriterions[$column_index] . ')';
            $last_where = $column_index;
            $criteria_cnt++;
        }
        if ($criteria_cnt > 1) {
            $where_clause = '(' . $where_clause . ')';
        }
        // OR rows ${'cur' . $or}[$column_index]
        if (! isset($this->formAndOrRows)) {
            $this->formAndOrRows = [];
        }
        for ($row_index = 0; $row_index <= $this->criteriaRowCount; $row_index++) {
            $criteria_cnt = 0;
            $qry_orwhere = '';
            $last_orwhere = '';
            for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
                if (! empty($this->formColumns[$column_index])
                    && ! empty($_POST['Or' . $row_index][$column_index])
                    && $column_index
                ) {
                    $qry_orwhere .= ' '
                        . mb_strtoupper(
                            $this->formAndOrCols[$last_orwhere]
                        )
                        . ' ';
                }
                if (empty($this->formColumns[$column_index])
                    || empty($_POST['Or' . $row_index][$column_index])
                ) {
                    continue;
                }

                $qry_orwhere .= '(' . $this->formColumns[$column_index]
                    . ' '
                    . $_POST['Or' . $row_index][$column_index]
                    . ')';
                $last_orwhere = $column_index;
                $criteria_cnt++;
            }
            if ($criteria_cnt > 1) {
                $qry_orwhere      = '(' . $qry_orwhere . ')';
            }
            if (empty($qry_orwhere)) {
                continue;
            }

            $where_clause .= "\n"
                . mb_strtoupper(
                    isset($this->formAndOrRows[$row_index])
                    ? $this->formAndOrRows[$row_index] . ' '
                    : ''
                )
                . $qry_orwhere;
        }

        if (! empty($where_clause) && $where_clause !== '()') {
            $where_clause = 'WHERE ' . $where_clause . "\n";
        }

        return $where_clause;
    }

    /**
     * Provides ORDER BY clause for building SQL query
     *
     * @return string Order By clause
     */
    private function getOrderByClause()
    {
        $orderby_clause = '';
        $orderby_clauses = [];

        // Create copy of instance variables
        $columns = $this->formColumns;
        $sort = $this->formSorts;
        $sortOrder = $this->formSortOrders;
        if (! empty($sortOrder)
            && count($sortOrder) == count($sort)
            && count($sortOrder) == count($columns)
        ) {
            // Sort all three arrays based on sort order
            array_multisort($sortOrder, $sort, $columns);
        }

        for ($column_index = 0; $column_index < $this->criteriaColumnCount; $column_index++) {
            // if all columns are chosen with * selector,
            // then sorting isn't available
            // Fix for Bug #570698
            if (empty($columns[$column_index])
                && empty($sort[$column_index])
            ) {
                continue;
            }

            if (mb_substr($columns[$column_index], -2) === '.*') {
                continue;
            }

            if (empty($sort[$column_index])) {
                continue;
            }

            $orderby_clauses[] = $columns[$column_index] . ' '
                . $sort[$column_index];
        }
        if (! empty($orderby_clauses)) {
            $orderby_clause = 'ORDER BY '
                . htmlspecialchars(implode(', ', $orderby_clauses)) . "\n";
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
    private function getIndexes(
        array $search_tables,
        array $search_columns,
        array $where_clause_columns
    ) {
        $unique_columns = [];
        $index_columns = [];

        foreach ($search_tables as $table) {
            $indexes = $this->dbi->getTableIndexes($this->db, $table);
            foreach ($indexes as $index) {
                $column = $table . '.' . $index['Column_name'];
                if (! isset($search_columns[$column])) {
                    continue;
                }

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
        }

        return [
            'unique' => $unique_columns,
            'index' => $index_columns,
        ];
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
    private function getLeftJoinColumnCandidates(
        array $search_tables,
        array $search_columns,
        array $where_clause_columns
    ) {
        $this->dbi->selectDb($this->db);

        // Get unique columns and index columns
        $indexes = $this->getIndexes(
            $search_tables,
            $search_columns,
            $where_clause_columns
        );
        $unique_columns = $indexes['unique'];
        $index_columns = $indexes['index'];

        [$candidate_columns, $needsort]
            = $this->getLeftJoinColumnCandidatesBest(
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

        $very_good = [];
        $still_good = [];
        foreach ($candidate_columns as $column => $is_where) {
            $table = explode('.', $column);
            $table = $table[0];
            if ($is_where === 'Y') {
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
    private function getMasterTable(
        array $search_tables,
        array $search_columns,
        array $where_clause_columns,
        array $where_clause_tables
    ) {
        if (count($where_clause_tables) === 1) {
            // If there is exactly one column that has a decent where-clause
            // we will just use this
            return key($where_clause_tables);
        }

        // Now let's find out which of the tables has an index
        // (When the control user is the same as the normal user
        // because they are using one of their databases as pmadb,
        // the last db selected is not always the one where we need to work)
        $candidate_columns = $this->getLeftJoinColumnCandidates(
            $search_tables,
            $search_columns,
            $where_clause_columns
        );

        // Generally, we need to display all the rows of foreign (referenced)
        // table, whether they have any matching row in child table or not.
        // So we select candidate tables which are foreign tables.
        $foreign_tables = [];
        foreach ($candidate_columns as $one_table) {
            $foreigners = $this->relation->getForeigners($this->db, $one_table);
            foreach ($foreigners as $key => $foreigner) {
                if ($key !== 'foreign_keys_data') {
                    if (in_array($foreigner['foreign_table'], $candidate_columns)) {
                        $foreign_tables[$foreigner['foreign_table']]
                            = $foreigner['foreign_table'];
                    }
                    continue;
                }
                foreach ($foreigner as $one_key) {
                    if (! in_array($one_key['ref_table_name'], $candidate_columns)) {
                        continue;
                    }

                    $foreign_tables[$one_key['ref_table_name']]
                        = $one_key['ref_table_name'];
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
        if (! (count($candidate_columns) > 1)) {
            // Only one single candidate
            return reset($candidate_columns);
        }

        // Of course we only want to check each table once
        $checked_tables = $candidate_columns;
        $tsize = [];
        $maxsize = -1;
        $result = '';
        foreach ($candidate_columns as $table) {
            if ($checked_tables[$table] != 1) {
                $_table = new Table($table, $this->db);
                $tsize[$table] = $_table->countRecords();
                $checked_tables[$table] = 1;
            }
            if ($tsize[$table] <= $maxsize) {
                continue;
            }

            $maxsize = $tsize[$table];
            $result = $table;
        }

        // Return largest table
        return $result;
    }

    /**
     * Provides columns and tables that have valid where clause criteria
     *
     * @return array
     */
    private function getWhereClauseTablesAndColumns()
    {
        $where_clause_columns = [];
        $where_clause_tables = [];

        // Now we need all tables that we have in the where clause
        for ($column_index = 0, $nb = count($this->criteria); $column_index < $nb; $column_index++) {
            $current_table = explode('.', $_POST['criteriaColumn'][$column_index]);
            if (empty($current_table[0]) || empty($current_table[1])) {
                continue;
            }
            $table = str_replace('`', '', $current_table[0]);
            $column = str_replace('`', '', $current_table[1]);
            $column = $table . '.' . $column;
            // Now we know that our array has the same numbers as $criteria
            // we can check which of our columns has a where clause
            if (empty($this->criteria[$column_index])) {
                continue;
            }

            if (mb_substr($this->criteria[$column_index], 0, 1) !== '='
                && stripos($this->criteria[$column_index], 'is') === false
            ) {
                continue;
            }

            $where_clause_columns[$column] = $column;
            $where_clause_tables[$table]  = $table;
        }

        return [
            'where_clause_tables' => $where_clause_tables,
            'where_clause_columns' => $where_clause_columns,
        ];
    }

    /**
     * Provides FROM clause for building SQL query
     *
     * @param array $formColumns List of selected columns in the form
     *
     * @return string FROM clause
     */
    private function getFromClause(array $formColumns)
    {
        $from_clause = '';
        if (empty($formColumns)) {
            return $from_clause;
        }

        // Initialize some variables
        $search_tables = $search_columns = [];

        // We only start this if we have fields, otherwise it would be dumb
        foreach ($formColumns as $value) {
            $parts = explode('.', $value);
            if (empty($parts[0]) || empty($parts[1])) {
                continue;
            }

            $table = str_replace('`', '', $parts[0]);
            $search_tables[$table] = $table;
            $search_columns[] = $table . '.' . str_replace(
                '`',
                '',
                $parts[1]
            );
        }

        // Create LEFT JOINS out of Relations
        $from_clause = $this->getJoinForFromClause(
            $search_tables,
            $search_columns
        );

        // In case relations are not defined, just generate the FROM clause
        // from the list of tables, however we don't generate any JOIN
        if (empty($from_clause)) {
            // Create cartesian product
            $from_clause = implode(
                ', ',
                array_map([Util::class, 'backquote'], $search_tables)
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
    private function getJoinForFromClause(array $searchTables, array $searchColumns)
    {
        // $relations[master_table][foreign_table] => clause
        $relations = [];

        // Fill $relations with inter table relationship data
        foreach ($searchTables as $oneTable) {
            $this->loadRelationsForTable($relations, $oneTable);
        }

        // Get tables and columns with valid where clauses
        $validWhereClauses = $this->getWhereClauseTablesAndColumns();
        $whereClauseTables = $validWhereClauses['where_clause_tables'];
        $whereClauseColumns = $validWhereClauses['where_clause_columns'];

        // Get master table
        $master = $this->getMasterTable(
            $searchTables,
            $searchColumns,
            $whereClauseColumns,
            $whereClauseTables
        );

        // Will include master tables and all tables that can be combined into
        // a cluster by their relation
        $finalized = [];
        if (strlen((string) $master) > 0) {
            // Add master tables
            $finalized[$master] = '';
        }
        // Fill the $finalized array with JOIN clauses for each table
        $this->fillJoinClauses($finalized, $relations, $searchTables);

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
                $references = $this->relation->getChildReferences($this->db, $oneTable);
                foreach ($references as $column => $columnReferences) {
                    foreach ($columnReferences as $reference) {
                        // Only from this schema
                        if ($reference['table_schema'] != $this->db) {
                            continue;
                        }

                        $table = $reference['table_name'];

                        $this->loadRelationsForTable($relations, $table);

                        // Make copies
                        $tempFinalized = $finalized;
                        $tempSearchTables = $searchTables;
                        $tempSearchTables[] = $table;

                        // Try joining with the added table
                        $this->fillJoinClauses(
                            $tempFinalized,
                            $relations,
                            $tempSearchTables
                        );

                        $tempUnfinalized = array_diff(
                            $tempSearchTables,
                            array_keys($tempFinalized)
                        );
                        // Take greedy approach.
                        // If the unfinalized count drops we keep the new table
                        // and switch temporary varibles with the original ones
                        if (count($tempUnfinalized) < count($unfinalized)) {
                            $finalized = $tempFinalized;
                            $searchTables = $tempSearchTables;
                        }

                        // We are done if no unfinalized tables anymore
                        if (count($tempUnfinalized) === 0) {
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
                    ', ',
                    array_map([Util::class, 'backquote'], $unfinalized)
                );
            }
        }

        $first = true;
        // Add joined tables
        foreach ($finalized as $table => $clause) {
            if ($first) {
                if (! empty($join)) {
                    $join .= ', ';
                }
                $join .= Util::backquote($table);
                $first = false;
            } else {
                $join .= "\n    LEFT JOIN " . Util::backquote(
                    $table
                ) . ' ON ' . $clause;
            }
        }

        return $join;
    }

    /**
     * Loads relations for a given table into the $relations array
     *
     * @param array  $relations array of relations
     * @param string $oneTable  the table
     *
     * @return void
     */
    private function loadRelationsForTable(array &$relations, $oneTable)
    {
        $relations[$oneTable] = [];

        $foreigners = $this->relation->getForeigners($GLOBALS['db'], $oneTable);
        foreach ($foreigners as $field => $foreigner) {
            // Foreign keys data
            if ($field === 'foreign_keys_data') {
                foreach ($foreigner as $oneKey) {
                    $clauses = [];
                    // There may be multiple column relations
                    foreach ($oneKey['index_list'] as $index => $oneField) {
                        $clauses[]
                            = Util::backquote($oneTable) . '.'
                            . Util::backquote($oneField) . ' = '
                            . Util::backquote($oneKey['ref_table_name']) . '.'
                            . Util::backquote($oneKey['ref_index_list'][$index]);
                    }
                    // Combine multiple column relations with AND
                    $relations[$oneTable][$oneKey['ref_table_name']]
                        = implode(' AND ', $clauses);
                }
            } else { // Internal relations
                $relations[$oneTable][$foreigner['foreign_table']]
                    = Util::backquote($oneTable) . '.'
                    . Util::backquote((string) $field) . ' = '
                    . Util::backquote($foreigner['foreign_table']) . '.'
                    . Util::backquote($foreigner['foreign_field']);
            }
        }
    }

    /**
     * Fills the $finalized arrays with JOIN clauses for each of the tables
     *
     * @param array $finalized    JOIN clauses for each table
     * @param array $relations    Relations among tables
     * @param array $searchTables Tables involved in the search
     *
     * @return void
     */
    private function fillJoinClauses(array &$finalized, array $relations, array $searchTables)
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
                    if (! $added) {
                        continue;
                    }

                    // We are done if all tables are in $finalized
                    if (count($finalized) == count($searchTables)) {
                        return;
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
    private function getSQLQuery(array $formColumns)
    {
        $sql_query = '';
        // get SELECT clause
        $sql_query .= $this->getSelectClause();
        // get FROM clause
        $from_clause = $this->getFromClause($formColumns);
        if (! empty($from_clause)) {
            $sql_query .= 'FROM ' . htmlspecialchars($from_clause) . "\n";
        }
        // get WHERE clause
        $sql_query .= $this->getWhereClause();
        // get ORDER BY clause
        $sql_query .= $this->getOrderByClause();

        return $sql_query;
    }

    public function getSelectionForm(): string
    {
        global $cfgRelation;

        $savedSearchesField = $cfgRelation['savedsearcheswork'] ? $this->getSavedSearchesField() : '';

        $columnNamesRow = $this->getColumnNamesRow();
        $columnAliasRow = $this->getColumnAliasRow();
        $showRow = $this->getShowRow();
        $sortRow = $this->getSortRow();
        $sortOrder = $this->getSortOrder();
        $criteriaInputBoxRow = $this->getCriteriaInputboxRow();
        $insDelAndOrCriteriaRows = $this->getInsDelAndOrCriteriaRows();
        $modifyColumnsRow = $this->getModifyColumnsRow();

        $this->newRowCount--;
        $url_params = [];
        $url_params['db'] = $this->db;
        $url_params['criteriaColumnCount'] = $this->newColumnCount;
        $url_params['rows'] = $this->newRowCount;

        if (empty($this->formColumns)) {
            $this->formColumns = [];
        }
        $sqlQuery = $this->getSQLQuery($this->formColumns);

        return $this->template->render('database/qbe/selection_form', [
            'db' => $this->db,
            'url_params' => $url_params,
            'db_link' => Generator::getDbLink($this->db),
            'criteria_tables' => $this->criteriaTables,
            'saved_searches_field' => $savedSearchesField,
            'column_names_row' => $columnNamesRow,
            'column_alias_row' => $columnAliasRow,
            'show_row' => $showRow,
            'sort_row' => $sortRow,
            'sort_order' => $sortOrder,
            'criteria_input_box_row' => $criteriaInputBoxRow,
            'ins_del_and_or_criteria_rows' => $insDelAndOrCriteriaRows,
            'modify_columns_row' => $modifyColumnsRow,
            'sql_query' => $sqlQuery,
        ]);
    }

    /**
     * Get fields to display
     *
     * @return string
     */
    private function getSavedSearchesField()
    {
        $html_output = __('Saved bookmarked search:');
        $html_output .= ' <select name="searchId" id="searchId">';
        $html_output .= '<option value="">' . __('New bookmark') . '</option>';

        $currentSearch = $this->getCurrentSearch();
        $currentSearchId = null;
        $currentSearchName = null;
        if ($currentSearch != null) {
            $currentSearchId = $currentSearch->getId();
            $currentSearchName = $currentSearch->getSearchName();
        }

        foreach ($this->savedSearchList as $id => $name) {
            $html_output .= '<option value="' . htmlspecialchars((string) $id)
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
            . 'value="' . htmlspecialchars((string) $currentSearchName) . '">';
        $html_output .= '<input type="hidden" name="action" id="action" value="">';
        $html_output .= '<input class="btn btn-secondary" type="submit" name="saveSearch" id="saveSearch" '
            . 'value="' . __('Create bookmark') . '">';
        if ($currentSearchId !== null) {
            $html_output .= '<input class="btn btn-secondary" type="submit" name="updateSearch" '
                . 'id="updateSearch" value="' . __('Update bookmark') . '">';
            $html_output .= '<input class="btn btn-secondary" type="submit" name="deleteSearch" '
                . 'id="deleteSearch" value="' . __('Delete bookmark') . '">';
        }

        return $html_output;
    }

    /**
     * Initialize _criteria_column_count
     *
     * @return int Previous number of columns
     */
    private function initializeCriteriasCount(): int
    {
        // sets column count
        $criteriaColumnCount = Core::ifSetOr(
            $_POST['criteriaColumnCount'],
            3,
            'numeric'
        );
        $criteriaColumnAdd = Core::ifSetOr(
            $_POST['criteriaColumnAdd'],
            0,
            'numeric'
        );
        $this->criteriaColumnCount = max(
            $criteriaColumnCount + $criteriaColumnAdd,
            0
        );

        // sets row count
        $rows = Core::ifSetOr($_POST['rows'], 0, 'numeric');
        $criteriaRowAdd = Core::ifSetOr($_POST['criteriaRowAdd'], 0, 'numeric');
        $this->criteriaRowCount = min(
            100,
            max($rows + $criteriaRowAdd, 0)
        );

        return (int) $criteriaColumnCount;
    }

    /**
     * Get best
     *
     * @param array      $search_tables        Tables involved in the search
     * @param array|null $where_clause_columns Columns with where clause
     * @param array|null $unique_columns       Unique columns
     * @param array|null $index_columns        Indexed columns
     *
     * @return array
     */
    private function getLeftJoinColumnCandidatesBest(
        array $search_tables,
        ?array $where_clause_columns,
        ?array $unique_columns,
        ?array $index_columns
    ) {
        // now we want to find the best.
        if (isset($unique_columns) && count($unique_columns) > 0) {
            $candidate_columns = $unique_columns;
            $needsort = 1;

            return [
                $candidate_columns,
                $needsort,
            ];
        }

        if (isset($index_columns) && count($index_columns) > 0) {
            $candidate_columns = $index_columns;
            $needsort = 1;

            return [
                $candidate_columns,
                $needsort,
            ];
        }

        if (isset($where_clause_columns) && count($where_clause_columns) > 0) {
            $candidate_columns = $where_clause_columns;
            $needsort = 0;

            return [
                $candidate_columns,
                $needsort,
            ];
        }

        $candidate_columns = $search_tables;
        $needsort = 0;

        return [
            $candidate_columns,
            $needsort,
        ];
    }
}
