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

$geom_types = PMA_getGISDatatypes();

$post_params = array(
    'ajax_request',
    'collations',
    'db',
    'distinct',
    'fields',
    'func',
    'max_number_of_fields',
    'names',
    'order',
    'orderField',
    'param',
    'session_max_rows',
    'table',
    'types',
    'where',
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}


/**
 * Not selection yet required -> displays the selection form
 */
if (! isset($param) || $param[0] == '') {
    // Gets some core libraries
    include_once 'libraries/tbl_common.inc.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets tables informations
     */
    include_once 'libraries/tbl_info.inc.php';

    if (! isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    list($fields_list, $fields_type, $fields_collation, $fields_null, $geom_column_present)
        = PMA_tbl_getFields($db, $table);
    $fields_cnt = count($fields_list);

    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreigners = PMA_getForeigners($db, $table);

    PMA_tblSearchDisplaySelectionForm(
        $goto, $fields_list, $fields_type, $fields_collation, $fields_null,
        $geom_column_present, $geom_types, $fields_cnt, $foreigners, $db, $table
    );

    include 'libraries/footer.inc.php';
} else {
    /**
     * Selection criteria have been submitted -> do the work
     */

    // Builds the query

    $sql_query = 'SELECT ' . (isset($distinct) ? 'DISTINCT ' : '');

    // if all fields were selected to display, we do a SELECT *
    // (more efficient and this helps prevent a problem in IE
    // if one of the rows is edited and we come back to the Select results)

    if (count($param) == $max_number_of_fields) {
        $sql_query .= '* ';
    } else {
        $param = PMA_backquote($param);
        $sql_query .= implode(', ', $param);
    } // end if

    // avoid a loop, for example when $cfg['DefaultTabTable'] is set
    // to 'tbl_select.php'
    unset($param);

    $sql_query .= ' FROM ' . PMA_backquote($table);

    // The where clause
    if (trim($where) != '') {
        $sql_query .= ' WHERE ' . $where;
    } else {
        $w = $charsets = array();
        $cnt_func = count($func);
        reset($func);
        while (list($i, $func_type) = each($func)) {

            list($charsets[$i]) = explode('_', $collations[$i]);
            $unaryFlag =  $GLOBALS['PMA_Types']->isUnaryOperator($func_type);

            $tmp_geom_func = isset($geom_func[$i]) ? $geom_func[$i] : null;
            $whereClause = PMA_tbl_search_getWhereClause(
                $fields[$i], $names[$i], $types[$i], $collations[$i],
                $func_type, $unaryFlag, $tmp_geom_func
            );

            if ($whereClause) {
                $w[] = $whereClause;
            }
        } // end while
        if ($w) {
            $sql_query .= ' WHERE ' . implode(' AND ', $w);
        }
    } // end if

    if ($orderField != '--nil--') {
        $sql_query .= ' ORDER BY ' . PMA_backquote($orderField) . ' ' . $order;
    } // end if
    include 'sql.php';
}

/**
 * Generates HTML for a geometrical function column to be displayed in table
 * search selection form
 *
 * @param boolean $geom_column_present whether a geometry column is present
 * @param array   $fields_type         array containing types of all columns
 *                                     in the table
 * @param array   $geom_types          array of GIS data types
 * @param integer $column_index        index of current column in $fields_type array
 *
 * @return string the generated HTML
 */
function PMA_tblSearchGetGeomFuncHtml($geom_column_present, $fields_type,
$geom_types, $column_index)
{    
    $html_output = '';
    // return if geometrical column is not present
    if (!$geom_column_present) {
        return $html_output;
    }

    /**
     * Displays 'Function' column if it is present
     */    
    $html_output .= '<td>';
    // if a geometry column is present
    if (in_array($fields_type[$column_index], $geom_types)) {
        $html_output .= '<select class="geom_func" name="geom_func[' . $column_index . ']">';
        // get the relevant list of GIS functions
        $funcs = PMA_getGISFunctions($fields_type[$column_index], true, true);
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
 * Displays formatted HTML for extra search options (slider) in table search form
 *
 * @param array   $fields_list array containing types of all columns
 *                             in the table
 * @param integer $fields_cnt  number of fields in the table
 *
 * @return void
 */
function PMA_tblSearchDisplaySliderOptions($fields_list, $fields_cnt)
{
    PMA_generateSliderEffect('searchoptions', __('Options'));
    $html_output = '';

    /**
     * Displays columns select list for selecting distinct columns in the search
     */
    $html_output = '<fieldset id="fieldset_select_fields">
        <legend>' . __('Select columns (at least one):') . '</legend>
        <select name="param[]" size="' . min($fields_cnt, 10) . '" multiple="multiple">';
    // Displays the list of the fields
    foreach ($fields_list as $each_field) {
        $html_output .= '        '
            . '<option value="' . htmlspecialchars($each_field) . '"'
            . ' selected="selected">' . htmlspecialchars($each_field)
            . '</option>' . "\n";
    } // end for
    $html_output .= '</select>
        <input type="checkbox" name="distinct" value="DISTINCT" id="oDistinct" />
        <label for="oDistinct">DISTINCT</label></fieldset>';

    /**
     * Displays input box for custom 'Where' clause to be used in the search
     */
    $html_output .= '<fieldset id="fieldset_search_conditions">
        <legend>' . '<em>' . __('Or') . '</em> ' .
        __('Add search conditions (body of the "where" clause):') . '</legend>';
    $html_output .= PMA_showMySQLDocu('SQL-Syntax', 'Functions');
    $html_output .= '<input type="text" name="where" class="textfield" size="64" />
        </fieldset>';

    /**
     * Displays option of changing default number of rows displayed per page
     */
    $html_output .= '<fieldset id="fieldset_limit_rows">
        <legend>' . __('Number of rows per page') . '</legend>
        <input type="text" size="4" name="session_max_rows"
        value="' . $GLOBALS['cfg']['MaxRows'] . '" class="textfield" />
        </fieldset>';

    /**
     * Displays option for ordering search results by a column value (Asc or Desc)
     */
    $html_output .= '<fieldset id="fieldset_display_order">
        <legend>' . __('Display order:') . '</legend>
        <select name="orderField"><option value="--nil--"></option>';
    foreach ($fields_list as $each_field) {
        $html_output .= '        '
            . '<option value="' . htmlspecialchars($each_field) . '">'
            . htmlspecialchars($each_field) . '</option>' . "\n";
    } // end for
    $html_output .= '</select>';
    $choices = array(
        'ASC' => __('Ascending'),
        'DESC' => __('Descending')
    );
    echo $html_output;
    PMA_displayHtmlRadio('order', $choices, 'ASC', false, true, "formelement");
    unset($choices);    
}

/**
 * Generates HTML for displaying fields table in search form
 *
 * @param array   $fields_list         Names of columns in the table
 * @param array   $fields_type         Types of columns in the table
 * @param array   $fields_collation    Collation of all columns
 * @param array   $fields_null         Null information of columns
 * @param boolean $geom_column_present Whether a geometry column is present
 * @param array   $geom_types          array of GIS data types
 * @param integer $fields_cnt          Number of columns in the table
 * @param array   $foreigners          Array of foreign keys
 * @param string  $db                  Selected database
 * @param string  $table               Selected table
 *
 * @return string the generated HTML
 */
function PMA_tblSearchGetFieldsTableHtml($fields_list, $fields_type,
$fields_collation, $fields_null, $geom_column_present, $geom_types, $fields_cnt,
$foreigners, $db, $table)
{
    $html_output = '';
    $html_output .= '<table class="data">';
    $html_output .= PMA_tbl_setTableHeader($geom_column_present) . '<tbody>';
    $odd_row = true;
    $titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));

    // for every column present in table
    for ($i = 0; $i < $fields_cnt; $i++) {
        $html_output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $odd_row = !$odd_row;

        /**
         * If 'Function' column is present
         */
        $html_output .= PMA_tblSearchGetGeomFuncHtml(
            $geom_column_present, $fields_type, $geom_types, $i
        );
        /**
         * Displays column's name, type, collation
         */
        $html_output .= '<th>' . htmlspecialchars($fields_list[$i]) . '</th>';
        $html_output .= '<td>' . htmlspecialchars($fields_type[$i]) . '</td>';
        $html_output .= '<td>' . $fields_collation[$i] . '</td>';
        /**
         * Displays column's comparison operators depending on column type
         */
        $html_output .= '<td><select name="func[]">';
        $html_output .= $GLOBALS['PMA_Types']->getTypeOperatorsHtml(
            $fields_type[$i], $fields_null[$i]
        );
        $html_output .= '</select></td><td>';
        /**
         * Displays column's foreign relations if any
         */
        $field = $fields_list[$i];
        $foreignData = PMA_getForeignData($foreigners, $field, false, '', '');
        $html_output .= PMA_getForeignFields_Values(
            $foreigners, $foreignData, $field, $fields_type, $i, $db, $table,
            $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], '', true
        );

        $html_output .= '<input type="hidden" name="names[' . $i . ']" value="' 
            . htmlspecialchars($fields_list[$i]) . '" /><input type="hidden" '
            . 'name="types[' . $i . ']" value="' . $fields_type[$i] . '" />'
            . '<input type="hidden" name="collations[' . $i . ']" value="'
            . $fields_collation[$i] . '" /></td></tr>';
    } // end for

    $html_output .= '</tbody></table>';
    return $html_output;
}

/**
 * Displays the table search form under table search tab
 *
 * @param string  $goto                
 * @param array   $fields_list         Names of columns in the table
 * @param array   $fields_type         Types of columns in the table
 * @param array   $fields_collation    Collation of all columns
 * @param array   $fields_null         Null information of columns
 * @param boolean $geom_column_present Whether a geometry column is present
 * @param array   $geom_types          array of GIS data types
 * @param integer $fields_cnt          Number of columns in the table
 * @param array   $foreigners          Array of foreign keys
 * @param string  $db                  Selected database
 * @param string  $table               Selected table
 *
 * @return void
 */
function PMA_tblSearchDisplaySelectionForm($goto, $fields_list, $fields_type,
$fields_collation, $fields_null, $geom_column_present, $geom_types, $fields_cnt,
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
        $fields_list, $fields_type, $fields_collation, $fields_null,
        $geom_column_present, $geom_types, $fields_cnt, $foreigners, $db, $table    
    );

    $html_output .= '<div id="gis_editor"></div><div id="popup_background"></div>'
        . '</fieldset>';
    echo $html_output;

    /**
     * Displays slider options form
     */
    PMA_tblSearchDisplaySliderOptions($fields_list, $fields_cnt);
    $html_output = '</fieldset><br style="clear: both;"/></div></fieldset>';

    /**
     * Displays selection form's footer elements
     */
    $html_output .= '<fieldset class="tblFooters"><input type="hidden" '
        . 'name="max_number_of_fields" value="' . $fields_cnt . '" />'
        . '<input type="submit" name="submit" value="' . __('Go') . '" />'
        . '</fieldset></form><div id="sqlqueryresults"></div></fieldset>';
    echo $html_output;
}
?>
