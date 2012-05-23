<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table search tab
 *
 * display table search form, create SQL query from form data
 * and include sql.php to execute it
 *
 * @todo display search form again if no results from previous search
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.lib.php';
require_once 'libraries/tbl_select.lib.php';

$GLOBALS['js_include'][] = 'makegrid.js';
$GLOBALS['js_include'][] = 'sql.js';
$GLOBALS['js_include'][] = 'tbl_select.js';
$GLOBALS['js_include'][] = 'tbl_change.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'gis_data_editor.js';

$post_params = array(
    'ajax_request',
    'criteriaColumnCollations',
    'db',
    'fields',
    'criteriaColumnOperators',
    'criteriaColumnNames',
    'order',
    'orderByColumn',
    'columnsToDisplay',
    'session_max_rows',
    'table',
    'criteriaColumnTypes',
    'customWhereClause',
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}


/**
 * Not selection yet required -> displays the selection form
 */
if (! isset($columnsToDisplay) || $columnsToDisplay[0] == '') {
    // Gets some core libraries
    include_once 'libraries/tbl_common.inc.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets table's information
     */
    include_once 'libraries/tbl_info.inc.php';

    if (! isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    list($columnNames, $columnTypes, $columnCollations, $columnNullFlags, $geomColumnFlag)
        = PMA_tbl_getFields($db, $table);
    $columnCount = count($columnNames);

    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreigners = PMA_getForeigners($db, $table);

    // Displays the table search form
    $table_search_form = PMA_tblSearchGetSelectionForm(
        $goto, $columnNames, $columnTypes, $columnCollations, $columnNullFlags,
        $geomColumnFlag, $columnCount, $foreigners, $db, $table
    );
    echo $table_search_form;

    include 'libraries/footer.inc.php';
} else {
    /**
     * Selection criteria have been submitted -> do the work
     */
    $is_distinct = (isset($_POST['distinct'])) ? 'true' : 'false';
    $sql_query = PMA_tblSearchBuildSqlQuery(
        $table, $fields, $criteriaColumnNames, $criteriaColumnTypes,
        $columnsToDisplay, $is_distinct, $customWhereClause,
        $criteriaColumnCollations, $criteriaColumnOperators, $orderByColumn, $order
    );
    unset($is_distinct);
    include 'sql.php';
}

/**
 * Builds the sql search query from the post parameters
 *
 * @param string  $table                    Selected table
 * @param array   $fields                   Entered values of the columns
 * @param array   $criteriaColumnNames      Names of all columns
 * @param array   $criteriaColumnTypes      Types of all columns
 * @param array   $columnsToDisplay         Columns to be displayed in search results
 * @param bool    $is_distinct              If only distinct values are needed
 * @param string  $customWhereClause        The custom where clause
 * @param array   $criteriaColumnCollations Collations of all columns
 * @param array   $criteriaColumnOperators  Operators for given column type
 * @param string  $orderByColumn            Column by which results are to be ordered
 * @param string  $order                    Whether ASC or DESC
 *
 * @return string the generated SQL query
 */
function PMA_tblSearchBuildSqlQuery($table, $fields, $criteriaColumnNames,
    $criteriaColumnTypes, $columnsToDisplay, $is_distinct, $customWhereClause,
    $criteriaColumnCollations, $criteriaColumnOperators, $orderByColumn, $order)
{
    $sql_query = 'SELECT ';
    if ($is_distinct == 'true') {
        $sql_query .= 'DISTINCT ';
    }

    // if all column names were selected to display, we do a 'SELECT *'
    // (more efficient and this helps prevent a problem in IE
    // if one of the rows is edited and we come back to the Select results)
    if (count($columnsToDisplay) == count($criteriaColumnNames)) {
        $sql_query .= '* ';
    } else {
        $columnsToDisplay = PMA_backquote($columnsToDisplay);
        $sql_query .= implode(', ', $columnsToDisplay);
    } // end if

    // avoid a loop, for example when $cfg['DefaultTabTable'] is set
    // to 'tbl_select.php'
    unset($columnsToDisplay);

    $sql_query .= ' FROM ' . PMA_backquote($table);
    $whereClause = PMA_tblSearchGenerateWhereClause(
        $fields, $criteriaColumnNames, $criteriaColumnTypes, $customWhereClause,
        $criteriaColumnCollations, $criteriaColumnOperators
    );
    $sql_query .= $whereClause;
 
    // if the search results are to be ordered
    if ($orderByColumn != '--nil--') {
        $sql_query .= ' ORDER BY ' . PMA_backquote($orderByColumn) . ' ' . $order;
    } // end if
    return $sql_query;
}

/**
 * Generates the where clause for the SQL search query to be executed
 *
 * @param array  $fields                   Entered values of the columns
 * @param array  $criteriaColumnNames      Names of all columns
 * @param array  $criteriaColumnTypes      Types of all columns
 * @param string $customWhereClause        The custom where clause
 * @param array  $criteriaColumnCollations Collations of all columns
 * @param array  $criteriaColumnOperators  Operators for given column type
 *
 * @return string the generated where clause
 */
function PMA_tblSearchGenerateWhereClause($fields, $criteriaColumnNames,
    $criteriaColumnTypes, $customWhereClause, $criteriaColumnCollations,
    $criteriaColumnOperators)
{
    $fullWhereClause = '';

    if (trim($customWhereClause) != '') {
        $fullWhereClause .= ' WHERE ' . $customWhereClause;
        return $fullWhereClause;
    }
    // If there are no search criterias set, return
    if (!array_filter($fields)) {
        return $fullWhereClause;
    }
    // else continue to form the where clause from column criteria values
    $fullWhereClause = $charsets = array();
    reset($criteriaColumnOperators);
    while (list($i, $operator) = each($criteriaColumnOperators)) {
        list($charsets[$i]) = explode('_', $criteriaColumnCollations[$i]);
        $unaryFlag =  $GLOBALS['PMA_Types']->isUnaryOperator($operator);
        $tmp_geom_func = isset($geom_func[$i]) ? $geom_func[$i] : null;

        $whereClause = PMA_tbl_search_getWhereClause(
            $fields[$i], $criteriaColumnNames[$i], $criteriaColumnTypes[$i],
            $criteriaColumnCollations[$i], $operator, $unaryFlag, $tmp_geom_func
        );

        if ($whereClause) {
            $fullWhereClause[] = $whereClause;
        }
    } // end while

    if ($fullWhereClause) {
        $fullWhereClause = ' WHERE ' . implode(' AND ', $fullWhereClause);
    }
    return $fullWhereClause;
}

/**
 * Generates HTML for a geometrical function column to be displayed in table
 * search selection form
 *
 * @param boolean $geomColumnFlag whether a geometry column is present
 * @param array   $columnTypes    array containing types of all columns in the table
 * @param array   $geom_types     array of GIS data types
 * @param integer $column_index   index of current column in $columnTypes array
 *
 * @return string the generated HTML
 */
function PMA_tblSearchGetGeomFuncHtml($geomColumnFlag, $columnTypes,
$geom_types, $column_index)
{
    $html_output = '';
    // return if geometrical column is not present
    if (!$geomColumnFlag) {
        return $html_output;
    }

    /**
     * Displays 'Function' column if it is present
     */    
    $html_output .= '<td>';
    // if a geometry column is present
    if (in_array($columnTypes[$column_index], $geom_types)) {
        $html_output .= '<select class="geom_func" name="geom_func['
            . $column_index . ']">';
        // get the relevant list of GIS functions
        $funcs = PMA_getGISFunctions($columnTypes[$column_index], true, true);
        /**
         * For each function in the list of functions, add an option to select list
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
 * Generates formatted HTML for extra search options (slider) in table search form
 *
 * @param array   $columnNames Array containing types of all columns in the table
 * @param integer $columnCount Number of columns in the table
 *
 * @return string the generated HTML
 */
function PMA_tblSearchGetSliderOptions($columnNames, $columnCount)
{    
    $html_output = '';
    $html_output .= PMA_getDivForSliderEffect('searchoptions', __('Options'));
    /**
     * Displays columns select list for selecting distinct columns in the search
     */
    $html_output .= '<fieldset id="fieldset_select_fields">'
        . '<legend>' . __('Select columns (at least one):') . '</legend>'
        . '<select name="columnsToDisplay[]" size="' . min($columnCount, 10)
        . '" multiple="multiple">';
    // Displays the list of the fields
    foreach ($columnNames as $each_field) {
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
    $html_output .= PMA_showMySQLDocu('SQL-Syntax', 'Functions');
    $html_output .= '<input type="text" name="customWhereClause" class="textfield" size="64" />'
        . '</fieldset>';

    /**
     * Displays option of changing default number of rows displayed per page
     */
    $html_output .= '<fieldset id="fieldset_limit_rows">'
        . '<legend>' . __('Number of rows per page') . '</legend>'
        . '<input type="text" size="4" name="session_max_rows" '
        . 'value="' . $GLOBALS['cfg']['MaxRows'] . '" class="textfield" />'
        . '</fieldset>';

    /**
     * Displays option for ordering search results by a column value (Asc or Desc)
     */
    $html_output .= '<fieldset id="fieldset_display_order">'
        . '<legend>' . __('Display order:') . '</legend>'
        . '<select name="orderByColumn"><option value="--nil--"></option>';
    foreach ($columnNames as $each_field) {
        $html_output .= '        '
            . '<option value="' . htmlspecialchars($each_field) . '">'
            . htmlspecialchars($each_field) . '</option>' . "\n";
    } // end for
    $html_output .= '</select>';
    $choices = array(
        'ASC' => __('Ascending'),
        'DESC' => __('Descending')
    );
    $html_output .= PMA_getRadioFields('order', $choices, 'ASC', false, true, "formelement");
    unset($choices);

    $html_output .= '</fieldset><br style="clear: both;"/></div></fieldset>';
    return $html_output;
}

/**
 * Generates HTML for displaying fields table in search form
 *
 * @param array   $columnNames      Names of columns in the table
 * @param array   $columnTypes      Types of columns in the table
 * @param array   $columnCollations Collation of all columns
 * @param array   $columnNullFlags  Null information of columns
 * @param boolean $geomColumnFlag   Whether a geometry column is present
 * @param integer $columnCount      Number of columns in the table
 * @param array   $foreigners       Array of foreign keys
 * @param string  $db               Selected database
 * @param string  $table            Selected table
 *
 * @return string the generated HTML
 */
function PMA_tblSearchGetFieldsTableHtml($columnNames, $columnTypes,
$columnCollations, $columnNullFlags, $geomColumnFlag, $columnCount,
$foreigners, $db, $table)
{
    $html_output = '';
    $html_output .= '<table class="data">';
    $html_output .= PMA_tbl_setTableHeader($geomColumnFlag) . '<tbody>';
    $odd_row = true;
    $titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));
    $geom_types = PMA_getGISDatatypes();

    // for every column present in table
    for ($i = 0; $i < $columnCount; $i++) {
        $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $odd_row = !$odd_row;

        /**
         * If 'Function' column is present
         */
        $html_output .= PMA_tblSearchGetGeomFuncHtml(
            $geomColumnFlag, $columnTypes, $geom_types, $i
        );
        /**
         * Displays column's name, type, collation
         */
        $html_output .= '<th>' . htmlspecialchars($columnNames[$i]) . '</th>';
        $html_output .= '<td>' . htmlspecialchars($columnTypes[$i]) . '</td>';
        $html_output .= '<td>' . $columnCollations[$i] . '</td>';
        /**
         * Displays column's comparison operators depending on column type
         */
        $html_output .= '<td><select name="criteriaColumnOperators[]">';
        $html_output .= $GLOBALS['PMA_Types']->getTypeOperatorsHtml(
            $columnTypes[$i], $columnNullFlags[$i]
        );
        $html_output .= '</select></td><td>';
        /**
         * Displays column's foreign relations if any
         */
        $field = $columnNames[$i];
        $foreignData = PMA_getForeignData($foreigners, $field, false, '', '');
        $html_output .= PMA_getForeignFields_Values(
            $foreigners, $foreignData, $field, $columnTypes, $i, $db, $table,
            $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], '', true
        );

        $html_output .= '<input type="hidden" name="criteriaColumnNames[' . $i . ']" value="' 
            . htmlspecialchars($columnNames[$i]) . '" /><input type="hidden" '
            . 'name="criteriaColumnTypes[' . $i . ']" value="' . $columnTypes[$i] . '" />'
            . '<input type="hidden" name="criteriaColumnCollations[' . $i . ']" value="'
            . $columnCollations[$i] . '" /></td></tr>';
    } // end for

    $html_output .= '</tbody></table>';
    return $html_output;
}

/**
 * Generates the table search form under table search tab
 *
 * @param string  $goto             Goto URL   
 * @param array   $columnNames      Names of columns in the table
 * @param array   $columnTypes      Types of columns in the table
 * @param array   $columnCollations Collation of all columns
 * @param array   $columnNullFlags  Null information of columns
 * @param boolean $geomColumnFlag   Whether a geometry column is present
 * @param integer $columnCount      Number of columns in the table
 * @param array   $foreigners       Array of foreign keys
 * @param string  $db               Selected database
 * @param string  $table            Selected table
 *
 * @return string the generated HTML for table search form
 */
function PMA_tblSearchGetSelectionForm($goto, $columnNames, $columnTypes,
$columnCollations, $columnNullFlags, $geomColumnFlag, $columnCount,
$foreigners, $db, $table)
{
    $html_output = '';
    $html_output .= '<fieldset id="fieldset_subtab">';
    $url_params = array();
    $url_params['db'] = $db;
    $url_params['table'] = $table;

    $html_output .= PMA_generateHtmlTabs(PMA_tbl_getSubTabs(), $url_params, 'topmenu2');
    $html_output .= '<form method="post" action="tbl_select.php" name="insertForm"'
        . ' id="tbl_search_form" ' . ($GLOBALS['cfg']['AjaxEnable'] ? 'class="ajax"' : '')
        . '>';
    $html_output .= PMA_generate_common_hidden_inputs($db, $table);
    $html_output .= '<input type="hidden" name="goto" value="' . $goto . '" />';
    $html_output .= '<input type="hidden" name="back" value="tbl_select.php" />'
        . '<fieldset id="fieldset_table_search"><fieldset id="fieldset_table_qbe">'
        . '<legend>' . __('Do a "query by example" (wildcard: "%")') . '</legend>';

    /**
     * Displays table fields
     */
    $html_output .= PMA_tblSearchGetFieldsTableHtml(
        $columnNames, $columnTypes, $columnCollations, $columnNullFlags,
        $geomColumnFlag, $columnCount, $foreigners, $db, $table    
    );

    $html_output .= '<div id="gis_editor"></div><div id="popup_background"></div>'
        . '</fieldset>';

    /**
     * Displays slider options form
     */
    $html_output .= PMA_tblSearchGetSliderOptions($columnNames, $columnCount);

    /**
     * Displays selection form's footer elements
     */
    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" name="submit" value="' . __('Go') . '" />'
        . '</fieldset></form><div id="sqlqueryresults"></div></fieldset>';
    return $html_output;
}
?>
