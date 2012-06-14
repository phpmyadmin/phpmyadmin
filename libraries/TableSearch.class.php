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
     *
     * @return New PMA_TableSearch
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

        $this->_loadTableInfo($this->_db, $this->_table);
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     *
     * @return array Array containing the column list, column types, collations
     * and null constraint
     */
    private function _loadTableInfo()
    {
        // Gets the list and number of columns
        $columns = PMA_DBI_get_columns($this->_db, $this->_table, null, true);
        // Get details about the geometry fucntions
        $geom_types = PMA_getGISDatatypes();

        foreach ($columns as $key => $row) {
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
            $edit_str = PMA_getIcon('b_edit.png', __('Edit/Insert'));
            $html_output .= '<span class="open_search_gis_editor">';
            $html_output .= PMA_linkOrButton(
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
            $html_output .=  '<select name="criteriaValues[' . $column_index . ']" id="'
                . $column_id . $column_index .'">';
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
                . $column_index . '"';
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
                . '][]" id="' . $column_id . $column_index .'">';
        } else {
            $html_output .= '<select name="criteriaValues[' . ($column_index)
                . '][]" id="' . $column_id . $column_index .'" multiple="multiple" size="'
                . min(3, $cnt_value) . '">';
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
     * @return string HTML content for viewing foreing data and elements
     * for search criteria input.
     */
    private function _getInputbox($foreignData, $column_name, $column_type,
        $column_index, $titles, $foreignMaxLimit, $criteriaValues, $in_fbs = false,
        $in_zoom_search_edit = false
    ) {
        $str = '';
        $column_type = (string)$column_type;
        $column_id = ($in_zoom_search_edit) ? 'edit_fieldID_' : 'fieldID_';

        //Get inputbox based on different column types (Foreign key, geometrical, enum)
        if ($this->_foreigners && isset($this->_foreigners[$column_name])) {
            $str .= $this->_getForeignKeyInputBox(
                $foreignData, $column_name, $column_index, $titles,
                $foreignMaxLimit, $criteriaValues, $column_id
            );

        } elseif (in_array($column_type, PMA_getGISDatatypes())) {
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
            } elseif ($column_type == 'datetime' || substr($column_type, 0, 9) == 'timestamp') {
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
}
?>
