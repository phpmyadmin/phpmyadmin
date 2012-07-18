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
     * Public Constructor
     *
     * @param string $db         Database name
     *
     */
    public function __construct($db)
    {
        $this->_db = $db;
        // Sets criteria parameters
        $this->_setSearchParams();
        $this->_setCriteriaTablesAndColumns();
    }

    /**
     * Get CommmonFunctions
     * 
     * @return CommonFunctions object
     */
    public function getCommonFunctions()
    {
        if (is_null($this->_common_functions)) {
            $this->_common_functions = PMA_CommonFunctions::getInstance();
        }
        return $this->_common_functions;
    }

    /**
     * Sets search parameters
     *
     */
    private function _setSearchParams()
    {
        // sets column count
        $criteriaColumnCount = PMA_ifSetOr($_REQUEST['criteriaColumnCount'], 3, 'numeric');
        $criteriaColumnAdd = PMA_ifSetOr($_REQUEST['criteriaColumnAdd'], 0, 'numeric');
        $this->_criteria_column_count = max($criteriaColumnCount + $criteriaColumnAdd, 0);

        // sets row count
        $rows = PMA_ifSetOr($_REQUEST['rows'],    0, 'numeric');
        $criteriaRowAdd = PMA_ifSetOr($_REQUEST['criteriaRowAdd'], 0, 'numeric');
        $this->_criteria_row_count = max($rows + $criteriaRowAdd, 0);

        $this->_criteriaColumnInsert = PMA_ifSetOr($_REQUEST['criteriaColumnInsert'], null, 'array');
        $this->_criteriaColumnDelete = PMA_ifSetOr($_REQUEST['criteriaColumnDelete'], null, 'array');

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
    }

    /**
     * Sets criteria tables and columns
     *
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
            'SHOW TABLES FROM ' . $common_functions->backquote($db) . ';',
            null, PMA_DBI_QUERY_STORE
        );
        $all_tables_count = PMA_DBI_num_rows($all_tables);
        if (0 == $all_tables_count) {
            PMA_Message::error(__('No tables found in database.'))->display();
            exit;
        }
        // The tables list gets from MySQL
        while (list($table) = PMA_DBI_fetch_row($all_tables)) {
            $columns = PMA_DBI_get_columns($this->_db, $table);

            if (empty($this->_criteriaTables[$table]) && ! empty($_REQUEST['TableList'])) {
                $this->_criteriaTables[$table] = '';
            } else {
                $this->_criteriaTables[$table] = ' selected="selected"';
            } //  end if

            // The fields list per selected tables
            if ($this->_criteriaTables[$table] == ' selected="selected"') {
                $each_table = $common_functions->backquote($table);
                $this->_columnNames[]  = $each_table . '.*';
                foreach ($columns as $each_column) {
                    $each_column = $each_table . '.' . $common_functions->backquote($each_column['Field']);
                    $this->_columnNames[] = $each_column;
                    // increase the width if necessary
                    $this->_form_column_width = max(strlen($each_column), $this->_form_column_width);
                } // end foreach
            } // end if
        } // end while
        PMA_DBI_free_result($all_tables);

        // sets the largest width found
        $this->_realwidth = $this->_form_column_width . 'ex';
    }
}
?>
