<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles Table search and Zoom search
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Template.class.php';

/**
 * Class to handle normal-search
 * and zoom-search in a table
 *
 * @package PhpMyAdmin
 */
class PMA_TableSearch
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;
    /**
     * Table name
     *
     * @access private
     * @var string
     */
    private $_table;
    /**
     * Normal search or Zoom search
     *
     * @access private
     * @var string
     */
    private $_searchType;
    /**
     * Names of columns
     *
     * @access private
     * @var array
     */
    private $_columnNames;
    /**
     * Types of columns
     *
     * @access private
     * @var array
     */
    private $_columnTypes;
    /**
     * Collations of columns
     *
     * @access private
     * @var array
     */
    private $_columnCollations;
    /**
     * Null Flags of columns
     *
     * @access private
     * @var array
     */
    private $_columnNullFlags;
    /**
     * Whether a geometry column is present
     *
     * @access private
     * @var boolean
     */
    private $_geomColumnFlag;
    /**
     * Foreign Keys
     *
     * @access private
     * @var array
     */
    private $_foreigners;


    /**
     * Public Constructor
     *
     * @param string $db         Database name
     * @param string $table      Table name
     * @param string $searchType Whether normal or zoom search
     */
    public function __construct($db, $table, $searchType)
    {
        $this->_db = $db;
        $this->_table = $table;
        $this->_searchType = $searchType;
        $this->_columnNames = array();
        $this->_columnNullFlags = array();
        $this->_columnTypes = array();
        $this->_columnCollations = array();
        $this->_geomColumnFlag = false;
        $this->_foreigners = array();
        // Loads table's information
        $this->_loadTableInfo();
    }

    /**
     * Returns Column names array
     *
     * @return array column names
     */
    public function getColumnNames()
    {
        return $this->_columnNames;
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     *
     * @return void
     */
    private function _loadTableInfo()
    {
        // Gets the list and number of columns
        $columns = $GLOBALS['dbi']->getColumns(
            $this->_db, $this->_table, null, true
        );
        // Get details about the geometry functions
        $geom_types = PMA_Util::getGISDatatypes();

        foreach ($columns as $row) {
            // set column name
            $this->_columnNames[] = $row['Field'];

            $type = $row['Type'];
            // check whether table contains geometric columns
            if (in_array($type, $geom_types)) {
                $this->_geomColumnFlag = true;
            }
            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) == 0
                || strncasecmp($type, 'enum', 4) == 0
            ) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (! preg_match('@BINARY[\(]@i', $type)) {
                    $type = preg_replace('@BINARY@i', '', $type);
                }
                $type = preg_replace('@ZEROFILL@i', '', $type);
                $type = preg_replace('@UNSIGNED@i', '', $type);
                $type = /*overload*/mb_strtolower($type);
            }
            if (empty($type)) {
                $type = '&nbsp;';
            }
            $this->_columnTypes[] = $type;
            $this->_columnNullFlags[] = $row['Null'];
            $this->_columnCollations[]
                = ! empty($row['Collation']) && $row['Collation'] != 'NULL'
                ? $row['Collation']
                : '';
        } // end for

        // Retrieve foreign keys
        $this->_foreigners = PMA_getForeigners($this->_db, $this->_table);
    }

    /**
     * Returns an array with necessary configurations to create
     * sub-tabs in the table_select page.
     *
     * @return array Array containing configuration (icon, text, link, id, args)
     * of sub-tabs
     */
    private function _getSubTabs()
    {
        $subtabs = array();
        $subtabs['search']['icon'] = 'b_search.png';
        $subtabs['search']['text'] = __('Table search');
        $subtabs['search']['link'] = 'tbl_select.php';
        $subtabs['search']['id'] = 'tbl_search_id';
        $subtabs['search']['args']['pos'] = 0;

        $subtabs['zoom']['icon'] = 'b_select.png';
        $subtabs['zoom']['link'] = 'tbl_zoom_select.php';
        $subtabs['zoom']['text'] = __('Zoom search');
        $subtabs['zoom']['id'] = 'zoom_search_id';

        $subtabs['replace']['icon'] = 'b_find_replace.png';
        $subtabs['replace']['link'] = 'tbl_find_replace.php';
        $subtabs['replace']['text'] = __('Find and replace');
        $subtabs['replace']['id'] = 'find_replace_id';

        return $subtabs;
    }

    /**
     * Creates the HTML content for:
     * 1) Browsing foreign data for a column.
     * 2) Creating elements for search criteria input on columns.
     *
     * @param array  $foreignData         Foreign keys data
     * @param string $column_name         Column name
     * @param string $column_type         Column type
     * @param int    $column_index        Column index
     * @param array  $titles              Selected title
     * @param int    $foreignMaxLimit     Max limit of displaying foreign elements
     * @param array  $criteriaValues      Array of search criteria inputs
     * @param bool   $in_fbs              Whether we are in 'function based search'
     * @param bool   $in_zoom_search_edit Whether we are in zoom search edit
     *
     * @return string HTML content for viewing foreign data and elements
     * for search criteria input.
     */
    private function _getInputbox($foreignData, $column_name, $column_type,
        $column_index, $titles, $foreignMaxLimit, $criteriaValues, $in_fbs = false,
        $in_zoom_search_edit = false
    ) {
        return PMA\Template::get('table/input_box')->render(
            array(
                'str' => '',
                'column_type' => (string) $column_type,
                'column_id' => ($in_zoom_search_edit) ? 'edit_fieldID_' : 'fieldID_',
                'in_zoom_search_edit' => $in_zoom_search_edit,
                '_foreigners' => $this->_foreigners,
                'column_name' => $column_name,
                'foreignData' => $foreignData,
                'table' => $this->_table,
                'column_index' => $column_index,
                'foreignMaxLimit' => $foreignMaxLimit,
                'criteriaValues' => $criteriaValues,
                'db' => $this->_db,
                'titles' => $titles,
                'in_fbs' => $in_fbs
            )
        );
    }

    /**
     * Return the where clause in case column's type is ENUM.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $func_type      Search function/operator
     *
     * @return string part of where clause.
     */
    private function _getEnumWhereClause($criteriaValues, $func_type)
    {
        if (! is_array($criteriaValues)) {
            $criteriaValues = explode(',', $criteriaValues);
        }
        $enum_selected_count = count($criteriaValues);
        if ($func_type == '=' && $enum_selected_count > 1) {
            $func_type    = 'IN';
            $parens_open  = '(';
            $parens_close = ')';

        } elseif ($func_type == '!=' && $enum_selected_count > 1) {
            $func_type    = 'NOT IN';
            $parens_open  = '(';
            $parens_close = ')';

        } else {
            $parens_open  = '';
            $parens_close = '';
        }
        $enum_where = '\''
            . PMA_Util::sqlAddSlashes($criteriaValues[0]) . '\'';
        for ($e = 1; $e < $enum_selected_count; $e++) {
            $enum_where .= ', \''
                . PMA_Util::sqlAddSlashes($criteriaValues[$e]) . '\'';
        }

        return ' ' . $func_type . ' ' . $parens_open
            . $enum_where . $parens_close;
    }

    /**
     * Return the where clause for a geometrical column.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $names          Name of the column on which search is submitted
     * @param string $func_type      Search function/operator
     * @param string $types          Type of the field
     * @param bool   $geom_func      Whether geometry functions should be applied
     *
     * @return string part of where clause.
     */
    private function _getGeomWhereClause($criteriaValues, $names,
        $func_type, $types, $geom_func = null
    ) {
        $geom_unary_functions = array(
            'IsEmpty' => 1,
            'IsSimple' => 1,
            'IsRing' => 1,
            'IsClosed' => 1,
        );
        $where = '';

        // Get details about the geometry functions
        $geom_funcs = PMA_Util::getGISFunctions($types, true, false);
        // New output type is the output type of the function being applied
        $types = $geom_funcs[$geom_func]['type'];

        // If the function takes a single parameter
        if ($geom_funcs[$geom_func]['params'] == 1) {
            $backquoted_name = $geom_func . '(' . PMA_Util::backquote($names) . ')';
        } else {
            // If the function takes two parameters
            // create gis data from the criteria input
            $gis_data = PMA_Util::createGISData($criteriaValues);
            $where = $geom_func . '(' . PMA_Util::backquote($names)
                . ',' . $gis_data . ')';
            return $where;
        }

        // If the where clause is something like 'IsEmpty(`spatial_col_name`)'
        if (isset($geom_unary_functions[$geom_func])
            && trim($criteriaValues) == ''
        ) {
            $where = $backquoted_name;

        } elseif (in_array($types, PMA_Util::getGISDatatypes())
            && ! empty($criteriaValues)
        ) {
            // create gis data from the criteria input
            $gis_data = PMA_Util::createGISData($criteriaValues);
            $where = $backquoted_name . ' ' . $func_type . ' ' . $gis_data;
        }
        return $where;
    }

    /**
     * Return the where clause for query generation based on the inputs provided.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $names          Name of the column on which search is submitted
     * @param string $types          Type of the field
     * @param string $func_type      Search function/operator
     * @param bool   $unaryFlag      Whether operator unary or not
     * @param bool   $geom_func      Whether geometry functions should be applied
     *
     * @return string generated where clause.
     */
    private function _getWhereClause($criteriaValues, $names, $types,
        $func_type, $unaryFlag, $geom_func = null
    ) {
        // If geometry function is set
        if ($geom_func != null && trim($geom_func) != '') {
            return $this->_getGeomWhereClause(
                $criteriaValues, $names, $func_type, $types, $geom_func
            );
        }

        $backquoted_name = PMA_Util::backquote($names);
        $where = '';
        if ($unaryFlag) {
            $where = $backquoted_name . ' ' . $func_type;

        } elseif (strncasecmp($types, 'enum', 4) == 0 && ! empty($criteriaValues)) {
            $where = $backquoted_name;
            $where .= $this->_getEnumWhereClause($criteriaValues, $func_type);

        } elseif ($criteriaValues != '') {
            // For these types we quote the value. Even if it's another type
            // (like INT), for a LIKE we always quote the value. MySQL converts
            // strings to numbers and numbers to strings as necessary
            // during the comparison
            if (preg_match('@char|binary|blob|text|set|date|time|year@i', $types)
                || /*overload*/mb_strpos(' ' . $func_type, 'LIKE')
            ) {
                $quot = '\'';
            } else {
                $quot = '';
            }

            // LIKE %...%
            if ($func_type == 'LIKE %...%') {
                $func_type = 'LIKE';
                $criteriaValues = '%' . $criteriaValues . '%';
            }
            if ($func_type == 'REGEXP ^...$') {
                $func_type = 'REGEXP';
                $criteriaValues = '^' . $criteriaValues . '$';
            }

            if ('IN (...)' != $func_type
                && 'NOT IN (...)' != $func_type
                && 'BETWEEN' != $func_type
                && 'NOT BETWEEN' != $func_type
            ) {
                if ($func_type == 'LIKE %...%' || $func_type == 'LIKE') {
                    $where = $backquoted_name . ' ' . $func_type . ' ' . $quot
                        . PMA_Util::sqlAddSlashes($criteriaValues, true) . $quot;
                } else {
                    $where = $backquoted_name . ' ' . $func_type . ' ' . $quot
                        . PMA_Util::sqlAddSlashes($criteriaValues) . $quot;
                }
                return $where;
            }
            $func_type = str_replace(' (...)', '', $func_type);

            //Don't explode if this is already an array
            //(Case for (NOT) IN/BETWEEN.)
            if (is_array($criteriaValues)) {
                $values = $criteriaValues;
            } else {
                $values = explode(',', $criteriaValues);
            }
            // quote values one by one
            $emptyKey = false;
            foreach ($values as $key => &$value) {
                if ('' === $value) {
                    $emptyKey = $key;
                    $value = 'NULL';
                    continue;
                }
                $value = $quot . PMA_Util::sqlAddSlashes(trim($value))
                    . $quot;
            }

            if ('BETWEEN' == $func_type || 'NOT BETWEEN' == $func_type) {
                $where = $backquoted_name . ' ' . $func_type . ' '
                    . (isset($values[0]) ? $values[0] : '')
                    . ' AND ' . (isset($values[1]) ? $values[1] : '');
            } else { //[NOT] IN
                if (false !== $emptyKey) {
                    unset($values[$emptyKey]);
                }
                $wheres = array();
                if (!empty($values)) {
                    $wheres[] = $backquoted_name . ' ' . $func_type
                        . ' (' . implode(',', $values) . ')';
                }
                if (false !== $emptyKey) {
                    $wheres[] = $backquoted_name . ' IS NULL';
                }
                $where = implode(' OR ', $wheres);
                if (1 < count($wheres)) {
                    $where = '(' . $where . ')';
                }
            }
        } // end if

        return $where;
    }

    /**
     * Builds the sql search query from the post parameters
     *
     * @return string the generated SQL query
     */
    public function buildSqlQuery()
    {
        $sql_query = 'SELECT ';

        // If only distinct values are needed
        $is_distinct = (isset($_POST['distinct'])) ? 'true' : 'false';
        if ($is_distinct == 'true') {
            $sql_query .= 'DISTINCT ';
        }

        // if all column names were selected to display, we do a 'SELECT *'
        // (more efficient and this helps prevent a problem in IE
        // if one of the rows is edited and we come back to the Select results)
        if (isset($_POST['zoom_submit']) || ! empty($_POST['displayAllColumns'])) {
            $sql_query .= '* ';
        } else {
            $sql_query .= implode(
                ', ',
                PMA_Util::backquote($_POST['columnsToDisplay'])
            );
        } // end if

        $sql_query .= ' FROM '
            . PMA_Util::backquote($_POST['table']);
        $whereClause = $this->_generateWhereClause();
        $sql_query .= $whereClause;

        // if the search results are to be ordered
        if (isset($_POST['orderByColumn']) && $_POST['orderByColumn'] != '--nil--') {
            $sql_query .= ' ORDER BY '
                . PMA_Util::backquote($_POST['orderByColumn'])
                . ' ' . $_POST['order'];
        } // end if
        return $sql_query;
    }

    /**
     * Generates the where clause for the SQL search query to be executed
     *
     * @return string the generated where clause
     */
    private function _generateWhereClause()
    {
        if (isset($_POST['customWhereClause'])
            && trim($_POST['customWhereClause']) != ''
        ) {
            return ' WHERE ' . $_POST['customWhereClause'];
        }

        // If there are no search criteria set or no unary criteria operators,
        // return
        if (! isset($_POST['criteriaValues'])
            && ! isset($_POST['criteriaColumnOperators'])
        ) {
            return '';
        }

        // else continue to form the where clause from column criteria values
        $fullWhereClause = array();
        reset($_POST['criteriaColumnOperators']);
        while (list($column_index, $operator) = each(
            $_POST['criteriaColumnOperators']
        )) {

            $unaryFlag =  $GLOBALS['PMA_Types']->isUnaryOperator($operator);
            $tmp_geom_func = isset($geom_func[$column_index])
                ? $geom_func[$column_index] : null;

            $whereClause = $this->_getWhereClause(
                $_POST['criteriaValues'][$column_index],
                $_POST['criteriaColumnNames'][$column_index],
                $_POST['criteriaColumnTypes'][$column_index],
                $operator,
                $unaryFlag,
                $tmp_geom_func
            );

            if ($whereClause) {
                $fullWhereClause[] = $whereClause;
            }
        } // end while

        if ($fullWhereClause) {
            return ' WHERE ' . implode(' AND ', $fullWhereClause);
        }
        return '';
    }

    /**
     * Provides a column's type, collation, operators list, and criteria value
     * to display in table search form
     *
     * @param integer $search_index Row number in table search form
     * @param integer $column_index Column index in ColumnNames array
     *
     * @return array Array containing column's properties
     */
    public function getColumnProperties($search_index, $column_index)
    {
        $selected_operator = (isset($_POST['criteriaColumnOperators'])
            ? $_POST['criteriaColumnOperators'][$search_index] : '');
        $entered_value = (isset($_POST['criteriaValues'])
            ? $_POST['criteriaValues'] : '');
        $titles = array(
            'Browse' => PMA_Util::getIcon(
                'b_browse.png', __('Browse foreign values')
            )
        );
        //Gets column's type and collation
        $type = $this->_columnTypes[$column_index];
        $collation = $this->_columnCollations[$column_index];
        //Gets column's comparison operators depending on column type
        $func = PMA\Template::get('table/column_comparison_operators')->render(
            array(
                'search_index' => $search_index,
                'columnTypes' => $this->_columnTypes,
                'column_index' => $column_index,
                'columnNullFlags' => $this->_columnNullFlags,
                'selected_operator' => $selected_operator
            )
        );
        //Gets link to browse foreign data(if any) and criteria inputbox
        $foreignData = PMA_getForeignData(
            $this->_foreigners, $this->_columnNames[$column_index], false, '', ''
        );
        $value =  $this->_getInputbox(
            $foreignData, $this->_columnNames[$column_index], $type, $search_index,
            $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], $entered_value
        );
        return array(
            'type' => $type,
            'collation' => $collation,
            'func' => $func,
            'value' => $value
        );
    }

    /**
     * Returns the HTML for secondary levels tabs of the table search page
     *
     * @return string HTML for secondary levels tabs
     */
    public function getSecondaryTabs()
    {
        return PMA\Template::get('table/secondary_tabs')->render(
            array(
                'url_params' => array(
                    'db' => $this->_db,
                    'table' => $this->_table
                ),
                'sub_tabs' => $this->_getSubTabs()
            )
        );
    }

    /**
     * Generates the table search form under table search tab
     *
     * @param string      $goto      Goto URL
     * @param string|null $dataLabel Label for points in zoom plot
     *
     * @return string the generated HTML for table search form
     */
    public function getSelectionForm($goto, $dataLabel = null)
    {
        return PMA\Template::get('table/selection_form')->render(
            array(
                'searchType' => $this->_searchType,
                'db' => $this->_db,
                'table' => $this->_table,
                'goto' => $goto,
                'self' => $this,
                'geomColumnFlag' => $this->_geomColumnFlag,
                'columnNames' => $this->_columnNames,
                'columnTypes' => $this->_columnTypes,
                'columnCollations' => $this->_columnCollations,
                'dataLabel' => $dataLabel
            )
        );
    }

    /**
     * Provides form for displaying point data and also the scatter plot
     * (for tbl_zoom_select.php)
     *
     * @param string $goto Goto URL
     * @param array  $data Array containing SQL query data
     *
     * @return string form's html
     */
    public function getZoomResultsForm($goto, $data)
    {
        $titles = array(
            'Browse' => PMA_Util::getIcon(
                'b_browse.png',
                __('Browse foreign values')
            )
        );

        return PMA\Template::get('table/zoom_result_form')->render(
            array(
                '_db' => $this->_db,
                '_table' => $this->_table,
                '_columnNames' => $this->_columnNames,
                '_foreigners' => $this->_foreigners,
                '_columnNullFlags' => $this->_columnNullFlags,
                '_columnTypes' => $this->_columnTypes,
                'titles' => $titles,
                'goto' => $goto,
                'data' => $data
            )
        );
    }

    /**
     * Displays the 'Find and replace' form
     *
     * @return string HTML for 'Find and replace' form
     */
    function _getSearchAndReplaceHTML()
    {
        return PMA\Template::get('table/search_and_replace')->render(
            array(
                'columnNames' => $this->_columnNames,
                'columnTypes' => $this->_columnTypes
            )
        );
    }

    /**
     * Finds and returns Regex pattern and their replacements
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param string $charSet     character set of the connection
     *
     * @return array Array containing original values, replaced values and count
     */
    function _getRegexReplaceRows($columnIndex, $find, $replaceWith, $charSet)
    {
        $column = $this->_columnNames[$columnIndex];
        $sql_query = "SELECT "
            . PMA_Util::backquote($column) . ","
            . " 1," // to add an extra column that will have replaced value
            . " COUNT(*)"
            . " FROM " . PMA_Util::backquote($this->_db)
            . "." . PMA_Util::backquote($this->_table)
            . " WHERE " . PMA_Util::backquote($column)
            . " RLIKE '" . PMA_Util::sqlAddSlashes($find) . "' COLLATE "
            . $charSet . "_bin"; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison is case sensitive
        $sql_query .= " GROUP BY " . PMA_Util::backquote($column)
            . " ORDER BY " . PMA_Util::backquote($column) . " ASC";

        $result = $GLOBALS['dbi']->fetchResult($sql_query, 0);

        if (is_array($result)) {
            foreach ($result as $index=>$row) {
                $result[$index][1] = preg_replace(
                    "/" . $find . "/",
                    $replaceWith,
                    $row[0]
                );
            }
        }
        return $result;
    }

    /**
     * Returns HTML for previewing strings found and their replacements
     *
     * @param int     $columnIndex index of the column
     * @param string  $find        string to find in the column
     * @param string  $replaceWith string to replace with
     * @param boolean $useRegex    to use Regex replace or not
     * @param string  $charSet     character set of the connection
     *
     * @return string HTML for previewing strings found and their replacements
     */
    function getReplacePreview($columnIndex, $find, $replaceWith, $useRegex,
        $charSet
    ) {
        $column = $this->_columnNames[$columnIndex];
        if ($useRegex) {
            $result = $this->_getRegexReplaceRows(
                $columnIndex, $find, $replaceWith, $charSet
            );
        } else {
            $sql_query = "SELECT "
                . PMA_Util::backquote($column) . ","
                . " REPLACE("
                . PMA_Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "'),"
                . " COUNT(*)"
                . " FROM " . PMA_Util::backquote($this->_db)
                . "." . PMA_Util::backquote($this->_table)
                . " WHERE " . PMA_Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . "_bin"; // here we
                // change the collation of the 2nd operand to a case sensitive
                // binary collation to make sure that the comparison
                // is case sensitive
            $sql_query .= " GROUP BY " . PMA_Util::backquote($column)
                . " ORDER BY " . PMA_Util::backquote($column) . " ASC";

            $result = $GLOBALS['dbi']->fetchResult($sql_query, 0);
        }

        return PMA\Template::get('table/replace_preview')->render(
            array(
                'db' => $this->_db,
                'table' => $this->_table,
                'columnIndex' => $columnIndex,
                'find' => $find,
                'replaceWith' => $replaceWith,
                'useRegex' => $useRegex,
                'result' => $result
            )
        );
    }

    /**
     * Replaces a given string in a column with a give replacement
     *
     * @param int     $columnIndex index of the column
     * @param string  $find        string to find in the column
     * @param string  $replaceWith string to replace with
     * @param boolean $useRegex    to use Regex replace or not
     * @param string  $charSet     character set of the connection
     *
     * @return void
     */
    function replace($columnIndex, $find, $replaceWith, $useRegex, $charSet)
    {
        $column = $this->_columnNames[$columnIndex];
        if ($useRegex) {
            $toReplace = $this->_getRegexReplaceRows(
                $columnIndex, $find, $replaceWith, $charSet
            );
            $sql_query = "UPDATE " . PMA_Util::backquote($this->_db)
                . "." . PMA_Util::backquote($this->_table)
                . " SET " . PMA_Util::backquote($column) . " = CASE";
            if (is_array($toReplace)) {
                foreach ($toReplace as $row) {
                    $sql_query .= "\n WHEN " . PMA_Util::backquote($column)
                        . " = '" . PMA_Util::sqlAddSlashes($row[0])
                        . "' THEN '" . PMA_Util::sqlAddSlashes($row[1]) . "'";
                }
            }
            $sql_query .= " END"
                . " WHERE " . PMA_Util::backquote($column)
                . " RLIKE '" . PMA_Util::sqlAddSlashes($find) . "' COLLATE "
                . $charSet . "_bin"; // here we
                // change the collation of the 2nd operand to a case sensitive
                // binary collation to make sure that the comparison
                // is case sensitive
        } else {
            $sql_query = "UPDATE " . PMA_Util::backquote($this->_db)
                . "." . PMA_Util::backquote($this->_table)
                . " SET " . PMA_Util::backquote($column) . " ="
                . " REPLACE("
                . PMA_Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "')"
                . " WHERE " . PMA_Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . "_bin"; // here we
                // change the collation of the 2nd operand to a case sensitive
                // binary collation to make sure that the comparison
                // is case sensitive
        }
        $GLOBALS['dbi']->query(
            $sql_query, null, PMA_DatabaseInterface::QUERY_STORE
        );
        $GLOBALS['sql_query'] = $sql_query;
    }

    /**
     * Finds minimum and maximum value of a given column.
     *
     * @param string $column Column name
     *
     * @return array
     */
    public function getColumnMinMax($column)
    {
        $sql_query = 'SELECT MIN(' . PMA_Util::backquote($column) . ') AS `min`, '
            . 'MAX(' . PMA_Util::backquote($column) . ') AS `max` '
            . 'FROM ' . PMA_Util::backquote($this->_db) . '.'
            . PMA_Util::backquote($this->_table);

        $result = $GLOBALS['dbi']->fetchSingleRow($sql_query);

        return $result;
    }
}
?>
