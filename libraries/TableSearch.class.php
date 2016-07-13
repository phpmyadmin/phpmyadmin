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
        $columns = PMA_DBI_get_columns($this->_db, $this->_table, null, true);
        // Get details about the geometry fucntions
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
                $type = strtolower($type);
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
     * Sets the table header for displaying a table in query-by-example format.
     *
     * @return HTML content, the tags and content for table header
     */
    private function _getTableHeader()
    {
        // Display the Function column only if there is at least one geometry column
        $func = '';
        if ($this->_geomColumnFlag) {
            $func = '<th>' . __('Function') . '</th>';
        }

        return '<thead>
            <tr>' . $func . '<th>' .  __('Column') . '</th>
            <th>' .  __('Type') . '</th>
            <th>' .  __('Collation') . '</th>
            <th>' .  __('Operator') . '</th>
            <th>' .  __('Value') . '</th>
            </tr>
            </thead>';
    }

    /**
     * Returns an array with necessary configrations to create
     * sub-tabs(Table Search and Zoom Search) in the table_select page.
     *
     * @return array Array containing configuration (icon, text, link, id, args)
     * of sub-tabs for Table Search and Zoom search
     */
    private function _getSubTabs()
    {
        $subtabs = array();
        $subtabs['search']['icon'] = 'b_search.png';
        $subtabs['search']['text'] = __('Table Search');
        $subtabs['search']['link'] = 'tbl_select.php';
        $subtabs['search']['id'] = 'tbl_search_id';
        $subtabs['search']['args']['pos'] = 0;

        $subtabs['zoom']['icon'] = 'b_props.png';
        $subtabs['zoom']['link'] = 'tbl_zoom_select.php';
        $subtabs['zoom']['text'] = __('Zoom Search');
        $subtabs['zoom']['id'] = 'zoom_search_id';

        return $subtabs;
    }

    /**
     * Provides html elements for search criteria inputbox
     * in case the column's type is geometrical
     *
     * @param int  $column_index Column's index
     * @param bool $in_fbs       Whether we are in 'function based search'
     *
     * @return HTML elements.
     */
    private function _getGeometricalInputBox($column_index, $in_fbs)
    {
        $html_output = '<input type="text" name="criteriaValues[' . $column_index . ']"'
            . ' size="40" class="textfield" id="field_' . $column_index . '" />';

        if ($in_fbs) {
            $edit_url = 'gis_data_editor.php?' . PMA_generate_common_url();
            $edit_str = PMA_Util::getIcon('b_edit.png', __('Edit/Insert'));
            $html_output .= '<span class="open_search_gis_editor">';
            $html_output .= PMA_Util::linkOrButton(
                $edit_url, $edit_str, array(), false, false, '_blank'
            );
            $html_output .= '</span>';
        }
        return $html_output;
    }

    /**
     * Provides html elements for search criteria inputbox
     * in case the column is a Foreign Key
     *
     * @param array  $foreignData         Foreign keys data
     * @param string $column_name         Column name
     * @param int    $column_index        Column index
     * @param array  $titles              Selected title
     * @param int    $foreignMaxLimit     Max limit of displaying foreign elements
     * @param array  $criteriaValues      Array of search criteria inputs
     * @param string $column_id           Column's inputbox's id
     * @param bool   $in_zoom_search_edit Whether we are in zoom search edit
     *
     * @return HTML elements.
     */
    private function _getForeignKeyInputBox($foreignData, $column_name,
        $column_index, $titles, $foreignMaxLimit, $criteriaValues, $column_id,
        $in_zoom_search_edit = false
    ) {
        $html_output = '';
        if (is_array($foreignData['disp_row'])) {
            $html_output .=  '<select name="criteriaValues[' . $column_index . ']"'
                . ' id="' . $column_id . $column_index .'">';
            $html_output .= PMA_foreignDropdown(
                $foreignData['disp_row'], $foreignData['foreign_field'],
                $foreignData['foreign_display'], '', $foreignMaxLimit
            );
            $html_output .= '</select>';

        } elseif ($foreignData['foreign_link'] == true) {
            $html_output .= '<input type="text" id="' . $column_id . $column_index . '"'
                . ' name="criteriaValues[' . $column_index . ']" id="field_'
                . md5($column_name) . '[' . $column_index .']" class="textfield"'
                . (isset($criteriaValues[$column_index])
                    && is_string($criteriaValues[$column_index])
                    ? (' value="' . $criteriaValues[$column_index] . '"')
                    : '')
                . ' />';

            $html_output .=  <<<EOT
<a target="_blank" onclick="window.open(this.href, 'foreigners', 'width=640,height=240,scrollbars=yes'); return false" href="browse_foreigners.php?
EOT;
            $html_output .= '' . PMA_generate_common_url($this->_db, $this->_table)
                . '&amp;field=' . urlencode($column_name) . '&amp;fieldkey='
                . $column_index . '&amp;fromsearch=1"';
            if ($in_zoom_search_edit) {
                $html_output .= ' class="browse_foreign"';
            }
            $html_output .= '>' . str_replace("'", "\'", $titles['Browse']) . '</a>';
        }
        return $html_output;
    }

    /**
     * Provides html elements for search criteria inputbox
     * in case the column is of ENUM or SET type
     *
     * @param int    $column_index        Column index
     * @param array  $criteriaValues      Array of search criteria inputs
     * @param string $column_type         Column type
     * @param string $column_id           Column's inputbox's id
     * @param bool   $in_zoom_search_edit Whether we are in zoom search edit
     *
     * @return HTML elements.
     */
    private function _getEnumSetInputBox($column_index, $criteriaValues,
        $column_type, $column_id, $in_zoom_search_edit = false
    ) {
        $column_type = htmlspecialchars($column_type);
        $html_output = '';
        $value = explode(
            ', ',
            str_replace("'", '', substr($column_type, 5, -1))
        );
        $cnt_value = count($value);

        /*
         * Enum in edit mode   --> dropdown
         * Enum in search mode --> multiselect
         * Set in edit mode    --> multiselect
         * Set in search mode  --> input (skipped here, so the 'else'
         *                                 section would handle it)
         */
        if ((strncasecmp($column_type, 'enum', 4) && ! $in_zoom_search_edit)
            || (strncasecmp($column_type, 'set', 3) && $in_zoom_search_edit)
        ) {
            $html_output .= '<select name="criteriaValues[' . ($column_index)
                . ']" id="' . $column_id . $column_index .'">';
        } else {
            $html_output .= '<select name="criteriaValues[' . $column_index . ']"'
                . ' id="' . $column_id . $column_index . '" multiple="multiple"'
                . ' size="' . min(3, $cnt_value) . '">';
        }

        //Add select options
        for ($j = 0; $j < $cnt_value; $j++) {
            if (isset($criteriaValues[$column_index])
                && is_array($criteriaValues[$column_index])
                && in_array($value[$j], $criteriaValues[$column_index])
            ) {
                $html_output .= '<option value="' . $value[$j] . '" Selected>'
                    . $value[$j] . '</option>';
            } else {
                $html_output .= '<option value="' . $value[$j] . '">'
                    . $value[$j] . '</option>';
            }
        } // end for
        $html_output .= '</select>';
        return $html_output;
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
        $str = '';
        $column_type = (string)$column_type;
        $column_id = ($in_zoom_search_edit) ? 'edit_fieldID_' : 'fieldID_';

        // Get inputbox based on different column types
        // (Foreign key, geometrical, enum)
        if ($this->_foreigners && isset($this->_foreigners[$column_name])) {
            $str .= $this->_getForeignKeyInputBox(
                $foreignData, $column_name, $column_index, $titles,
                $foreignMaxLimit, $criteriaValues, $column_id
            );

        } elseif (in_array($column_type, PMA_Util::getGISDatatypes())) {
            $str .= $this->_getGeometricalInputBox($column_index, $in_fbs);

        } elseif (strncasecmp($column_type, 'enum', 4) == 0
            || (strncasecmp($column_type, 'set', 3) == 0 && $in_zoom_search_edit)
        ) {
            $str .= $this->_getEnumSetInputBox(
                $column_index, $criteriaValues, $column_type, $column_id,
                $in_zoom_search_edit = false
            );

        } else {
            // other cases
            $the_class = 'textfield';

            if ($column_type == 'date') {
                $the_class .= ' datefield';
            } elseif ($column_type == 'datetime'
                || substr($column_type, 0, 9) == 'timestamp'
            ) {
                $the_class .= ' datetimefield';
            } elseif (substr($column_type, 0, 3) == 'bit') {
                $the_class .= ' bit';
            }

            $str .= '<input type="text" name="criteriaValues[' . $column_index . ']"'
                .' size="40" class="' . $the_class . '" id="'
                . $column_id . $column_index . '"'
                . (isset($criteriaValues[$column_index])
                    && is_string($criteriaValues[$column_index])
                    ? (' value="' . $criteriaValues[$column_index] . '"')
                    : '')
                . ' />';
        }
        return $str;
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
            $where = $geom_func . '(' . PMA_Util::backquote($names) . ',' . $gis_data . ')';
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
     * @param string $collations     Field collation
     * @param string $func_type      Search function/operator
     * @param bool   $unaryFlag      Whether operator unary or not
     * @param bool   $geom_func      Whether geometry functions should be applied
     *
     * @return string generated where clause.
     */
    private function _getWhereClause($criteriaValues, $names, $types, $collations,
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
            $criteriaValues = '';
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
                || strpos(' ' . $func_type, 'LIKE')
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

            if ($func_type == 'IN (...)'
                || $func_type == 'NOT IN (...)'
                || $func_type == 'BETWEEN'
                || $func_type == 'NOT BETWEEN'
            ) {
                $func_type = str_replace(' (...)', '', $func_type);

                // quote values one by one
                $values = explode(',', $criteriaValues);
                foreach ($values as &$value) {
                    $value = $quot . PMA_Util::sqlAddSlashes(trim($value))
                        . $quot;
                }

                if ($func_type == 'BETWEEN' || $func_type == 'NOT BETWEEN') {
                    $where = $backquoted_name . ' ' . $func_type . ' '
                        . (isset($values[0]) ? $values[0] : '')
                        . ' AND ' . (isset($values[1]) ? $values[1] : '');
                } else {
                    $where = $backquoted_name . ' ' . $func_type
                        . ' (' . implode(',', $values) . ')';
                }
            } else {
                if ($func_type == 'LIKE %...%' || $func_type == 'LIKE') {
                    $where = $backquoted_name . ' ' . $func_type . ' ' . $quot
                        . PMA_Util::sqlAddSlashes($criteriaValues, true) . $quot;
                } else {
                    $where = $backquoted_name . ' ' . $func_type . ' ' . $quot
                        . PMA_Util::sqlAddSlashes($criteriaValues) . $quot;
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

        // If there are no search criteria set or no unary criteria operators, return
        if (! isset($_POST['criteriaValues'])
            && ! isset($_POST['criteriaColumnOperators'])
        ) {
            return '';
        }

        // else continue to form the where clause from column criteria values
        $fullWhereClause = $charsets = array();
        reset($_POST['criteriaColumnOperators']);
        while (list($column_index, $operator) = each($_POST['criteriaColumnOperators'])) {
            list($charsets[$column_index]) = explode(
                '_', $_POST['criteriaColumnCollations'][$column_index]
            );
            $unaryFlag =  $GLOBALS['PMA_Types']->isUnaryOperator($operator);
            $tmp_geom_func = isset($geom_func[$column_index])
                ? $geom_func[$column_index] : null;

            $whereClause = $this->_getWhereClause(
                $_POST['criteriaValues'][$column_index],
                $_POST['criteriaColumnNames'][$column_index],
                $_POST['criteriaColumnTypes'][$column_index],
                $_POST['criteriaColumnCollations'][$column_index],
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
     * Generates HTML for a geometrical function column to be displayed in table
     * search selection form
     *
     * @param integer $column_index index of current column in $columnTypes array
     *
     * @return string the generated HTML
     */
    private function _getGeomFuncHtml($column_index)
    {
        $html_output = '';
        // return if geometrical column is not present
        if (! $this->_geomColumnFlag) {
            return $html_output;
        }

        /**
         * Displays 'Function' column if it is present
         */
        $html_output .= '<td>';
        $geom_types = PMA_Util::getGISDatatypes();
        // if a geometry column is present
        if (in_array($this->_columnTypes[$column_index], $geom_types)) {
            $html_output .= '<select class="geom_func" name="geom_func['
                . $column_index . ']">';
            // get the relevant list of GIS functions
            $funcs = PMA_Util::getGISFunctions($this->_columnTypes[$column_index], true, true);
            /**
             * For each function in the list of functions,
             * add an option to select list
             */
            foreach ($funcs as $func_name => $func) {
                $name = isset($func['display']) ? $func['display'] : $func_name;
                $html_output .= '<option value="' . htmlspecialchars($name) . '">'
                        . htmlspecialchars($name) . '</option>';
            }
            $html_output .= '</select>';
        } else {
            $html_output .= '&nbsp;';
        }
        $html_output .= '</td>';
        return $html_output;
    }

    /**
     * Generates formatted HTML for extra search options in table search form
     *
     * @return string the generated HTML
     */
    private function _getOptions()
    {
        $html_output = '';
        $html_output .= PMA_Util::getDivForSliderEffect(
            'searchoptions', __('Options')
        );

        /**
         * Displays columns select list for selecting distinct columns in the search
         */
        $html_output .= '<fieldset id="fieldset_select_fields">'
            . '<legend>' . __('Select columns (at least one):') . '</legend>'
            . '<select name="columnsToDisplay[]"'
            . ' size="' . min(count($this->_columnNames), 10) . '"'
            . ' multiple="multiple">';
        // Displays the list of the fields
        foreach ($this->_columnNames as $each_field) {
            $html_output .= '        '
                . '<option value="' . htmlspecialchars($each_field) . '"'
                . ' selected="selected">' . htmlspecialchars($each_field)
                . '</option>' . "\n";
        } // end for
        $html_output .= '</select>'
            . '<input type="checkbox" name="distinct" value="DISTINCT" id="oDistinct" />'
            . '<label for="oDistinct">DISTINCT</label></fieldset>';

        /**
         * Displays input box for custom 'Where' clause to be used in the search
         */
        $html_output .= '<fieldset id="fieldset_search_conditions">'
            . '<legend>' . '<em>' . __('Or') . '</em> '
            . __('Add search conditions (body of the "where" clause):') . '</legend>';
        $html_output .= PMA_Util::showMySQLDocu(
            'SQL-Syntax', 'Functions'
        );
        $html_output .= '<input type="text" name="customWhereClause"'
            . ' class="textfield" size="64" />';
        $html_output .= '</fieldset>';

        /**
         * Displays option of changing default number of rows displayed per page
         */
        $html_output .= '<fieldset id="fieldset_limit_rows">'
            . '<legend>' . __('Number of rows per page') . '</legend>'
            . '<input type="text" size="4" name="session_max_rows" '
            . 'value="' . $GLOBALS['cfg']['MaxRows'] . '" class="textfield" />'
            . '</fieldset>';

        /**
         * Displays option for ordering search results
         * by a column value (Asc or Desc)
         */
        $html_output .= '<fieldset id="fieldset_display_order">'
            . '<legend>' . __('Display order:') . '</legend>'
            . '<select name="orderByColumn"><option value="--nil--"></option>';
        foreach ($this->_columnNames as $each_field) {
            $html_output .= '        '
                . '<option value="' . htmlspecialchars($each_field) . '">'
                . htmlspecialchars($each_field) . '</option>' . "\n";
        } // end for
        $html_output .= '</select>';
        $choices = array(
            'ASC' => __('Ascending'),
            'DESC' => __('Descending')
        );
        $html_output .= PMA_Util::getRadioFields(
            'order', $choices, 'ASC', false, true, "formelement"
        );
        unset($choices);

        $html_output .= '</fieldset><br style="clear: both;"/></div>';
        return $html_output;
    }

    /**
     * Other search criteria like data label
     * (for tbl_zoom_select.php)
     *
     * @param array $dataLabel Label for points in zoom plot
     *
     * @return string the generated html
     */
    private function _getOptionsZoom($dataLabel)
    {
        $html_output = '';
        $html_output .= '<table class="data">';
        //Select options for datalabel
        $html_output .= '<tr>';
        $html_output .= '<td><label for="dataLabel">'
            . __("Use this column to label each point") . '</label></td>';
        $html_output .= '<td><select name="dataLabel" id="dataLabel" >'
            . '<option value = "">' . __('None') . '</option>';
        for ($j = 0; $j < count($this->_columnNames); $j++) {
            if (isset($dataLabel)
                && $dataLabel == htmlspecialchars($this->_columnNames[$j])
            ) {
                $html_output .= '<option value="'
                    . htmlspecialchars($this->_columnNames[$j]) . '" selected="selected">'
                    . htmlspecialchars($this->_columnNames[$j]) . '</option>';
            } else {
                $html_output .= '<option value="'
                    . htmlspecialchars($this->_columnNames[$j]) . '" >'
                    . htmlspecialchars($this->_columnNames[$j]) . '</option>';
            }
        }
        $html_output .= '</select></td>';
        $html_output .= '</tr>';
        //Inputbox for changing default maximum rows to plot
        $html_output .= '<tr>';
        $html_output .= '<td><label for="maxRowPlotLimit">'
            . __("Maximum rows to plot") . '</label></td>';
        $html_output .= '<td>';
        $html_output .= '<input type="text" name="maxPlotLimit"'
            . ' id="maxRowPlotLimit"'
            . ' value="' . ((! empty($_POST['maxPlotLimit']))
                ? htmlspecialchars($_POST['maxPlotLimit'])
                : $GLOBALS['cfg']['maxRowPlotLimit'])
            . '" />';
        $html_output .= '</td></tr>';
        $html_output .= '</table>';
        return $html_output;
    }

    /**
     * Provides a column's type, collation, operators list, and crietria value
     * to display in table search form
     *
     * @param integer $search_index Row number in table search form
     * @param integer $column_index Column index in ColumnNames array
     *
     * @return array Array contaning column's properties
     */
    public function getColumnProperties($search_index, $column_index)
    {
        $selected_operator = (isset($_POST['criteriaColumnOperators'])
            ? $_POST['criteriaColumnOperators'][$search_index] : '');
        $entered_value = (isset($_POST['criteriaValues'])
            ? $_POST['criteriaValues'] : '');
        $titles['Browse'] = PMA_Util::getIcon('b_browse.png', __('Browse foreign values'));
        //Gets column's type and collation
        $type = $this->_columnTypes[$column_index];
        $collation = $this->_columnCollations[$column_index];
        //Gets column's comparison operators depending on column type
        $func = '<select name="criteriaColumnOperators[' . $search_index . ']">';
        $func .= $GLOBALS['PMA_Types']->getTypeOperatorsHtml(
            preg_replace('@\(.*@s', '', $this->_columnTypes[$column_index]),
            $this->_columnNullFlags[$column_index], $selected_operator
        );
        $func .= '</select>';
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
     * Provides the search form's table row in case of Normal Search
     * (for tbl_select.php)
     *
     * @return string the generated table row
     */
    private function _getRowsNormal()
    {
        $odd_row = true;
        $html_output = '';
        // for every column present in table
        for ($column_index = 0; $column_index < count($this->_columnNames); $column_index++) {
            $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
            $odd_row = !$odd_row;
            //If 'Function' column is present
            $html_output .= $this->_getGeomFuncHtml($column_index);
            //Displays column's name, type, collation and value
            $html_output .= '<th>'
                . htmlspecialchars($this->_columnNames[$column_index]) . '</th>';
            $properties = $this->getColumnProperties($column_index, $column_index);
            $html_output .= '<td>'
                . htmlspecialchars($properties['type'])
                . '</td>';
            $html_output .= '<td>' . $properties['collation'] . '</td>';
            $html_output .= '<td>' . $properties['func'] . '</td>';
            $html_output .= '<td>' . $properties['value'] . '</td>';
            $html_output .= '</tr>';
            //Displays hidden fields
            $html_output .= '<tr><td>';
            $html_output .= '<input type="hidden"'
                . ' name="criteriaColumnNames[' . $column_index . ']"'
                . ' value="'
                . htmlspecialchars($this->_columnNames[$column_index])
                . '" />';
            $html_output .= '<input type="hidden"'
                . ' name="criteriaColumnTypes[' . $column_index . ']"'
                . ' value="'
                . htmlspecialchars($this->_columnTypes[$column_index]) . '" />';
            $html_output .= '<input type="hidden"'
                . ' name="criteriaColumnCollations[' . $column_index . ']"'
                . ' value="' . $this->_columnCollations[$column_index] . '" />';
            $html_output .= '</td></tr>';
        } // end for

        return $html_output;
    }

    /**
     * Provides the search form's table row in case of Zoom Search
     * (for tbl_zoom_select.php)
     *
     * @return string the generated table row
     */
    private function _getRowsZoom()
    {
        $odd_row = true;
        $html_output = '';
        /**
         * Get already set search criteria (if any)
         */

        //Displays column rows for search criteria input
        for ($i = 0; $i < 4; $i++) {
            //After X-Axis and Y-Axis column rows, display additional criteria option
            if ($i == 2) {
                $html_output .= '<tr><td>';
                $html_output .= __("Additional search criteria");
                $html_output .= '</td></tr>';
            }
            $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
            $odd_row = ! $odd_row;
            //Select options for column names
            $html_output .= '<th><select name="criteriaColumnNames[]" id="'
                . 'tableid_' . $i . '" >';
            $html_output .= '<option value="' . 'pma_null' . '">' . __('None')
                . '</option>';
            for ($j = 0 ; $j < count($this->_columnNames); $j++) {
                if (isset($_POST['criteriaColumnNames'][$i])
                    && $_POST['criteriaColumnNames'][$i] == htmlspecialchars($this->_columnNames[$j])
                ) {
                    $html_output .= '<option value="'
                        . htmlspecialchars($this->_columnNames[$j]) . '" selected="selected">'
                        . htmlspecialchars($this->_columnNames[$j]) . '</option>';
                } else {
                    $html_output .= '<option value="'
                        . htmlspecialchars($this->_columnNames[$j]) . '">'
                        . htmlspecialchars($this->_columnNames[$j]) . '</option>';
                }
            }
            $html_output .= '</select></th>';
            if (isset($_POST['criteriaColumnNames'])
                && $_POST['criteriaColumnNames'][$i] != 'pma_null'
            ) {
                $key = array_search(
                    $_POST['criteriaColumnNames'][$i],
                    $this->_columnNames
                );
                $properties = $this->getColumnProperties($i, $key);
                $type[$i] = $properties['type'];
                $collation[$i] = $properties['collation'];
                $func[$i] = $properties['func'];
                $value[$i] = $properties['value'];
            }
            //Column type
            $html_output .= '<td>' . (isset($type[$i]) ? htmlspecialchars($type[$i]) : '') . '</td>';
            //Column Collation
            $html_output .= '<td>' . (isset($collation[$i]) ? $collation[$i] : '')
                . '</td>';
            //Select options for column operators
            $html_output .= '<td>' . (isset($func[$i]) ? $func[$i] : '') . '</td>';
            //Inputbox for search criteria value
            $html_output .= '<td>' . (isset($value[$i]) ? htmlspecialchars($value[$i]) : '') . '</td>';
            $html_output .= '</tr>';
            //Displays hidden fields
            $html_output .= '<tr><td>';
            $html_output .= '<input type="hidden" name="criteriaColumnTypes[' . $i . ']"'
                . ' id="types_' . $i . '" ';
            if (isset($_POST['criteriaColumnTypes'][$i])) {
                $html_output .= 'value="' . htmlspecialchars($_POST['criteriaColumnTypes'][$i]) . '" ';
            }
            $html_output .= '/>';
            $html_output .= '<input type="hidden" name="criteriaColumnCollations['
                . $i . ']" id="collations_' . $i . '" />';
            $html_output .= '</td></tr>';
        }//end for
        return $html_output;
    }

    /**
     * Generates HTML for displaying fields table in search form
     *
     * @return string the generated HTML
     */
    private function _getFieldsTableHtml()
    {
        $html_output = '';
        $html_output .= '<table class="data"'
            . ($this->_searchType == 'zoom' ? ' id="tableFieldsId"' : '') . '>';
        $html_output .= $this->_getTableHeader();
        $html_output .= '<tbody>';

        if ($this->_searchType == 'zoom') {
            $html_output .= $this->_getRowsZoom();
        } else {
            $html_output .= $this->_getRowsNormal();
        }

        $html_output .= '</tbody></table>';
        return $html_output;
    }

    /**
     * Provides the form tag for table search form
     * (normal search or zoom search)
     *
     * @param string $goto Goto URL
     *
     * @return string the HTML for form tag
     */
    private function _getFormTag($goto)
    {
        $html_output = '';
        $scriptName = ($this->_searchType == 'zoom' ? 'tbl_zoom_select.php' : 'tbl_select.php');
        $formId = ($this->_searchType == 'zoom' ? 'zoom_search_form' : 'tbl_search_form');

        $html_output .= '<form method="post" action="' . $scriptName . '" '
            . 'name="insertForm" id="' . $formId . '" '
            . 'class="ajax"' . '>';

        $html_output .= PMA_generate_common_hidden_inputs($this->_db, $this->_table);
        $html_output .= '<input type="hidden" name="goto" value="' . $goto . '" />';
        $html_output .= '<input type="hidden" name="back" value="' . $scriptName
            . '" />';

        return $html_output;
    }

    /**
     * Generates the table search form under table search tab
     *
     * @param string $goto      Goto URL
     * @param string $dataLabel Label for points in zoom plot
     *
     * @return string the generated HTML for table search form
     */
    public function getSelectionForm($goto, $dataLabel = null)
    {
        $url_params = array();
        $url_params['db'] = $this->_db;
        $url_params['table'] = $this->_table;

        $html_output = '<ul id="topmenu2">';
        foreach ($this->_getSubTabs() as $tab) {
            $html_output .= PMA_Util::getHtmlTab($tab, $url_params);
        }
        $html_output .= '</ul>';
        $html_output .= '<div class="clearfloat"></div>';

        $html_output .= $this->_getFormTag($goto);

        if ($this->_searchType == 'zoom') {
            $html_output .= '<fieldset id="fieldset_zoom_search">';
            $html_output .= '<fieldset id="inputSection">';
            $html_output .= '<legend>'
                . __('Do a "query by example" (wildcard: "%") for two different columns')
                . '</legend>';
            $html_output .= $this->_getFieldsTableHtml();
            $html_output .= $this->_getOptionsZoom($dataLabel);
            $html_output .= '</fieldset>';
            $html_output .= '</fieldset>';
        } else {
            $html_output .= '<fieldset id="fieldset_table_search">';
            $html_output .= '<fieldset id="fieldset_table_qbe">';
            $html_output .= '<legend>'
                . __('Do a "query by example" (wildcard: "%")')
                . '</legend>';
            $html_output .= $this->_getFieldsTableHtml();
            $html_output .= '<div id="gis_editor"></div>';
            $html_output .= '<div id="popup_background"></div>';
            $html_output .= '</fieldset>';
            $html_output .= $this->_getOptions();
            $html_output .= '</fieldset>';
        }

        /**
         * Displays selection form's footer elements
         */
        $html_output .= '<fieldset class="tblFooters">';
        $html_output .= '<input type="submit" name="'
            . ($this->_searchType == 'zoom' ? 'zoom_submit' : 'submit')
            . ($this->_searchType == 'zoom' ? '" id="inputFormSubmitId"' : '" ')
            . 'value="' . __('Go') . '" />';
        $html_output .= '</fieldset></form>';
        $html_output .= '<div id="sqlqueryresults"></div>';
        return $html_output;
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
        $html_output = '';
        $titles['Browse'] = PMA_Util::getIcon('b_browse.png', __('Browse foreign values'));
        $html_output .= '<form method="post" action="tbl_zoom_select.php"'
            . ' name="displayResultForm" id="zoom_display_form"'
            . ' class="ajax"' . '>';
        $html_output .= PMA_generate_common_hidden_inputs($this->_db, $this->_table);
        $html_output .= '<input type="hidden" name="goto" value="' . $goto . '" />';
        $html_output .= '<input type="hidden" name="back" value="tbl_zoom_select.php" />';

        $html_output .= '<fieldset id="displaySection">';
        $html_output .= '<legend>' . __('Browse/Edit the points') . '</legend>';

        //JSON encode the data(query result)
        $html_output .= '<center>';
        if (isset($_POST['zoom_submit']) && ! empty($data)) {
            $html_output .= '<div id="resizer">';
            $html_output .= '<center><a href="#" onclick="displayHelp();">'
                . __('How to use') . '</a></center>';
            $html_output .= '<div id="querydata" style="display:none">'
                . htmlspecialchars(json_encode($data)) . '</div>';
            $html_output .= '<div id="querychart"></div>';
            $html_output .= '<button class="button-reset">'
                . __('Reset zoom') . '</button>';
            $html_output .= '</div>';
        }
        $html_output .= '</center>';

        //Displays rows in point edit form
        $html_output .= '<div id="dataDisplay" style="display:none">';
        $html_output .= '<table><thead>';
        $html_output .= '<tr>';
        $html_output .= '<th>' . __('Column') . '</th>'
            . '<th>' . __('Null') . '</th>'
            . '<th>' . __('Value') . '</th>';
        $html_output .= '</tr>';
        $html_output .= '</thead>';

        $html_output .= '<tbody>';
        $odd_row = true;
        for ($column_index = 0; $column_index < count($this->_columnNames); $column_index++) {
            $fieldpopup = $this->_columnNames[$column_index];
            $foreignData = PMA_getForeignData($this->_foreigners, $fieldpopup, false, '', '');
            $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
            $odd_row = ! $odd_row;
            //Display column Names
            $html_output .= '<th>' . htmlspecialchars($this->_columnNames[$column_index])
                . '</th>';
            //Null checkbox if column can be null
            $html_output .= '<th>' . (($this->_columnNullFlags[$column_index] == 'YES')
                ? '<input type="checkbox" class="checkbox_null"'
                    . ' name="criteriaColumnNullFlags[' . $column_index . ']"'
                    . ' id="edit_fields_null_id_' . $column_index . '" />'
                : '');
            $html_output .= '</th>';
            //Column's Input box
            $html_output .= '<th>';
            $html_output .= $this->_getInputbox(
                $foreignData, $fieldpopup, $this->_columnTypes[$column_index],
                $column_index, $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'],
                '', false, true
            );
            $html_output .= '</th></tr>';
        }
        $html_output .= '</tbody></table>';
        $html_output .= '</div>';
        $html_output .= '<input type="hidden" id="queryID" name="sql_query" />';
        $html_output .= '</form>';
        return $html_output;
    }
}
?>
