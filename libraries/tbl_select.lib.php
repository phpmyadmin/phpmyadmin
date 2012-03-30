<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for the table-search page and zoom-search page
 *
 * @package PhpMyAdmin
 */

require_once 'url_generating.lib.php';

/**
 * Gets all the fields of a table along with their types, collations
 * and whether null or not.
 *
 * @param string $db    Selected database
 * @param string $table Selected table
 *
 * @return array Array containing the field list, field types, collations
 * and null constraint
 */
function PMA_tbl_getFields($db, $table)
{
    // Gets the list and number of fields
    $fields = PMA_DBI_get_columns($db, $table, null, true);
    $fields_list = $fields_null = $fields_type = $fields_collation = array();
    $geom_column_present = false;
    $geom_types = PMA_getGISDatatypes();

    foreach ($fields as $key => $row) {
        $fields_list[] = $row['Field'];
        $type          = $row['Type'];

        // check whether table contains geometric columns
        if (in_array($type, $geom_types)) {
            $geom_column_present = true;
        }

        // reformat mysql query output
        if (strncasecmp($type, 'set', 3) == 0
            || strncasecmp($type, 'enum', 4) == 0
        ) {
            $type = str_replace(',', ', ', $type);
        } else {
            // strip the "BINARY" attribute, except if we find "BINARY(" because
            // this would be a BINARY or VARBINARY field type
            if (!preg_match('@BINARY[\(]@i', $type)) {
                $type = preg_replace('@BINARY@i', '', $type);
            }
            $type = preg_replace('@ZEROFILL@i', '', $type);
            $type = preg_replace('@UNSIGNED@i', '', $type);

            $type = strtolower($type);
        }
        if (empty($type)) {
            $type = '&nbsp;';
        }
        $fields_null[] = $row['Null'];
        $fields_type[] = $type;
        $fields_collation[] = ! empty($row['Collation']) && $row['Collation'] != 'NULL'
            ? $row['Collation']
            : '';
    } // end while

    return array($fields_list, $fields_type, $fields_collation, $fields_null, $geom_column_present);
}

/**
 * Sets the table header for displaying a table in query-by-example format.
 *
 * @param bool $geom_column_present whether a geometry column is present
 *
 * @return HTML content, the tags and content for table header
 */
function PMA_tbl_setTableHeader($geom_column_present = false)
{
    // Display the Function column only if there is alteast one geomety colum
    $func = '';
    if ($geom_column_present) {
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
function PMA_tbl_getSubTabs()
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
 * Creates the HTML content for:
 * 1) Browsing foreign data for a field.
 * 2) Creating elements for search criteria input on fields.
 *
 * @param array  $foreigners      Array of foreign keys
 * @param array  $foreignData     Foreign keys data
 * @param string $field           Column name
 * @param string $tbl_fields_type Column type
 * @param int    $i               Column index
 * @param string $db              Selected database
 * @param string $table           Selected table
 * @param array  $titles          Selected title
 * @param int    $foreignMaxLimit Max limit of displaying foreign elements
 * @param array  $fields          Array of search criteria inputs
 * @param bool   $in_fbs          Whether we are in 'function based search'
 * @param bool   $in_zoom_search_edit  Whether we are in zoom search edit
 *
 * @return string HTML content for viewing foreing data and elements
 * for search criteria input.
 */
function PMA_getForeignFields_Values($foreigners, $foreignData, $field, $tbl_fields_type, $i, $db, $table, $titles, $foreignMaxLimit, $fields, $in_fbs = false, $in_zoom_search_edit = false)
{
    $str = '';
    if ($foreigners && isset($foreigners[$field]) && is_array($foreignData['disp_row'])) {
        // f o r e i g n    k e y s
        $str .=  '<select name="fields[' . $i . ']" id="fieldID_' . $i .'">' . "\n";
        // go back to first row
        // here, the 4th parameter is empty because there is no current
        // value of data for the dropdown (the search page initial values
        // are displayed empty)
        $str .= PMA_foreignDropdown(
            $foreignData['disp_row'], $foreignData['foreign_field'],
            $foreignData['foreign_display'], '', $foreignMaxLimit
        );
        $str .= '</select>' . "\n";

    } elseif ($foreignData['foreign_link'] == true) {
        if (isset($fields[$i]) && is_string($fields[$i])) {
            $str .= '<input type="text" id="fieldID_' . $i . '" name="fields[' . $i . ']" value="' . $fields[$i] . '" id="field_' . md5($field) . '[' . $i .']" class="textfield" />' ;
        } else {
            $str .= '<input type="text" id="fieldID_' . $i . '" name="fields[' . $i . ']" id="field_' . md5($field) . '[' . $i .']" class="textfield" />' ;
        }
        $str .=  <<<EOT
<a target="_blank" onclick="window.open(this.href, 'foreigners', 'width=640,height=240,scrollbars=yes'); return false" href="browse_foreigners.php?
EOT;
        $str .= '' . PMA_generate_common_url($db, $table) .  '&amp;field=' . urlencode($field) . '&amp;fieldkey=' . $i . '"';
        if ($in_zoom_search_edit) {
            $str .= ' class="browse_foreign"';
        }
        $str .= '>' . str_replace("'", "\'", $titles['Browse']) . '</a>';

    } elseif (in_array($tbl_fields_type[$i], PMA_getGISDatatypes())) {
        // g e o m e t r y
        $str .= '<input type="text" name="fields[' . $i . ']"'
        .' size="40" class="textfield" id="field_' . $i . '" />' .  "\n";

        if ($in_fbs) {
            $edit_url = 'gis_data_editor.php?' . PMA_generate_common_url();
            $edit_str = PMA_getIcon('b_edit.png', __('Edit/Insert'));
            $str .= '<span class="open_search_gis_editor">';
            $str .= PMA_linkOrButton($edit_url, $edit_str, array(), false, false, '_blank');
            $str .= '</span>';
        }

    } elseif (strncasecmp($tbl_fields_type[$i], 'enum', 4) == 0
        || (strncasecmp($tbl_fields_type[$i], 'set', 3) == 0 && $in_zoom_search_edit)
    ) {
        // e n u m s   a n d   s e t s

        // Enum in edit mode   --> dropdown
        // Enum in search mode --> multiselect
        // Set in edit mode    --> multiselect
        // Set in search mode  --> input (skipped here, so the 'else'
        //                                 section would handle it)

        $value = explode(', ', str_replace("'", '', substr($tbl_fields_type[$i], 5, -1)));
        $cnt_value = count($value);

        if ((strncasecmp($tbl_fields_type[$i], 'enum', 4) && ! $in_zoom_search_edit)
            || (strncasecmp($tbl_fields_type[$i], 'set', 3) && $in_zoom_search_edit)
        ) {
            $str .= '<select name="fields[' . ($i) . '][]" id="fieldID_' . $i .'">' . "\n";
        } else {
            $str .= '<select name="fields[' . ($i) . '][]" id="fieldID_' . $i .'"'
                . ' multiple="multiple" size="' . min(3, $cnt_value) . '">' . "\n";
        }

        for ($j = 0; $j < $cnt_value; $j++) {
            if (isset($fields[$i])
                && is_array($fields[$i])
                && in_array($value[$j], $fields[$i])
            ) {
                $str .= '<option value="' . $value[$j] . '" Selected>'
                    . $value[$j] . '</option>';
            } else {
                $str .= '<option value="' . $value[$j] . '">'
                    . $value[$j] . '</option>';
            }
        } // end for
        $str .= '</select>' . "\n";

    } else {
        // o t h e r   c a s e s
        $the_class = 'textfield';
        $type = $tbl_fields_type[$i];

        if ($type == 'date') {
            $the_class .= ' datefield';
        } elseif ($type == 'datetime' || substr($type, 0, 9) == 'timestamp') {
            $the_class .= ' datetimefield';
        } elseif (substr($type, 0, 3) == 'bit') {
            $the_class .= ' bit';
        }

        if (isset($fields[$i]) && is_string($fields[$i])) {
            $str .= '<input type="text" name="fields[' . $i . ']"'
                .' size="40" class="' . $the_class . '" id="fieldID_'
                . $i .'" value = "' . $fields[$i] . '"/>' .  "\n";
        } else {
            $str .= '<input type="text" name="fields[' . $i . ']"'
                .' size="40" class="' . $the_class . '" id="fieldID_'
                . $i .'" />' .  "\n";
        }
    }
    return $str;
}

/**
 * Return the where clause for query generation based on the inputs provided.
 *
 * @param mixed  $fields     Search criteria input
 * @param string $names      Name of the column on which search is submitted
 * @param string $types      Type of the field
 * @param string $collations Field collation
 * @param string $func_type  Search fucntion/operator
 * @param bool   $unaryFlag  Whether operator unary or not
 * @param bool   $geom_func  Whether geometry functions should be applied
 *
 * @return string HTML content for viewing foreing data and elements
 * for search criteria input.
 */
function PMA_tbl_search_getWhereClause($fields, $names, $types, $collations, $func_type, $unaryFlag, $geom_func = null)
{
    /**
     * @todo move this to a more apropriate place
     */
    $geom_unary_functions = array(
        'IsEmpty' => 1,
        'IsSimple' => 1,
        'IsRing' => 1,
        'IsClosed' => 1,
    );

    $w = '';
    // If geometry function is set apply it to the field name
    if ($geom_func != null && trim($geom_func) != '') {
        // Get details about the geometry fucntions
        $geom_funcs = PMA_getGISFunctions($types, true, false);

        // If the function takes a single parameter
        if ($geom_funcs[$geom_func]['params'] == 1) {
            $backquoted_name = $geom_func . '(' . PMA_backquote($names) . ')';
        } else {
            // If the function takes two parameters
            // create gis data from the string
            $gis_data = PMA_createGISData($fields);

            $w = $geom_func . '(' . PMA_backquote($names) . ',' . $gis_data . ')';
            return $w;
        }

        // New output type is the output type of the function being applied
        $types = $geom_funcs[$geom_func]['type'];

        // If the where clause is something like 'IsEmpty(`spatial_col_name`)'
        if (isset($geom_unary_functions[$geom_func]) && trim($fields) == '') {
            $w = $backquoted_name;
            return $w;
        }
    } else {
        $backquoted_name = PMA_backquote($names);
    }

    if ($unaryFlag) {
        $fields = '';
        $w = $backquoted_name . ' ' . $func_type;

    } elseif (in_array($types, PMA_getGISDatatypes()) && ! empty($fields)) {
        // create gis data from the string
        $gis_data = PMA_createGISData($fields);
        $w = $backquoted_name . ' ' . $func_type . ' ' . $gis_data;

    } elseif (strncasecmp($types, 'enum', 4) == 0) {
        if (!empty($fields)) {
            if (! is_array($fields)) {
                $fields = explode(',', $fields);
            }
            $enum_selected_count = count($fields);
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
            $enum_where = '\'' . PMA_sqlAddslashes($fields[0]) . '\'';
            for ($e = 1; $e < $enum_selected_count; $e++) {
                $enum_where .= ', \'' . PMA_sqlAddslashes($fields[$e]) . '\'';
            }

            $w = $backquoted_name . ' ' . $func_type . ' ' . $parens_open . $enum_where . $parens_close;
        }

    } elseif ($fields != '') {
        // For these types we quote the value. Even if it's another type (like INT),
        // for a LIKE we always quote the value. MySQL converts strings to numbers
        // and numbers to strings as necessary during the comparison
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
            $fields = '%' . $fields . '%';
        }
        if ($func_type == 'REGEXP ^...$') {
            $func_type = 'REGEXP';
            $fields = '^' . $fields . '$';
        }

        if ($func_type == 'IN (...)'
            || $func_type == 'NOT IN (...)'
            || $func_type == 'BETWEEN'
            || $func_type == 'NOT BETWEEN'
        ) {
            $func_type = str_replace(' (...)', '', $func_type);

            // quote values one by one
            $values = explode(',', $fields);
            foreach ($values as &$value) {
                $value = $quot . PMA_sqlAddslashes(trim($value)) . $quot;
            }

            if ($func_type == 'BETWEEN' || $func_type == 'NOT BETWEEN') {
                $w = $backquoted_name . ' ' . $func_type . ' ' . (isset($values[0]) ? $values[0] : '')
                    . ' AND ' . (isset($values[1]) ? $values[1] : '');
            } else {
                $w = $backquoted_name . ' ' . $func_type . ' (' . implode(',', $values) . ')';
            }
        } else {
            $w = $backquoted_name . ' ' . $func_type . ' ' . $quot . PMA_sqlAddslashes($fields) . $quot;;
        }
    } // end if

    return $w;
}
?>
