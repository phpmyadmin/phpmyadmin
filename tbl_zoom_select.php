<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.inc.php';
require_once './libraries/TableSearch.class.php';
require_once './libraries/tbl_info.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('sql.js');
$scripts->addFile('jqplot/jquery.jqplot.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('jqplot/plugins/jqplot.cursor.js');
$scripts->addFile('canvg/canvg.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
$scripts->addFile('tbl_zoom_plot_jqplot.js');

$table_search = new PMA_TableSearch($db, $table, "zoom");

/**
 * Handle AJAX request for data row on point select
 * @var post_params Object containing parameters for the POST request
 */

if (isset($_REQUEST['get_data_row']) && $_REQUEST['get_data_row'] == true) {
    $extra_data = array();
    $row_info_query = 'SELECT * FROM `' . $_REQUEST['db'] . '`.`'
        . $_REQUEST['table'] . '` WHERE ' .  $_REQUEST['where_clause'];
    $result = $GLOBALS['dbi']->query(
        $row_info_query . ";", null, PMA_DatabaseInterface::QUERY_STORE
    );
    $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        // for bit fields we need to convert them to printable form
        $i = 0;
        foreach ($row as $col => $val) {
            if ($fields_meta[$i]->type == 'bit') {
                $row[$col] = PMA_Util::printableBitValue(
                    $val, $fields_meta[$i]->length
                );
            }
            $i++;
        }
        $extra_data['row_info'] = $row;
    }
    PMA_Response::getInstance()->addJSON($extra_data);
    exit;
}

/**
 * Handle AJAX request for changing field information
 * (value,collation,operators,field values) in input form
 * @var post_params Object containing parameters for the POST request
 */

if (isset($_REQUEST['change_tbl_info']) && $_REQUEST['change_tbl_info'] == true) {
    $response = PMA_Response::getInstance();
    $field = $_REQUEST['field'];
    if ($field == 'pma_null') {
        $response->addJSON('field_type', '');
        $response->addJSON('field_collation', '');
        $response->addJSON('field_operators', '');
        $response->addJSON('field_value', '');
        exit;
    }
    $key = array_search($field, $table_search->getColumnNames());
    $properties = $table_search->getColumnProperties($_REQUEST['it'], $key);
    $response->addJSON('field_type', htmlspecialchars($properties['type']));
    $response->addJSON('field_collation', $properties['collation']);
    $response->addJSON('field_operators', $properties['func']);
    $response->addJSON('field_value', $properties['value']);
    exit;
}

// Gets some core libraries
require_once './libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

// Gets tables informations
require_once './libraries/tbl_info.inc.php';

if (! isset($goto)) {
    $goto = $GLOBALS['cfg']['DefaultTabTable'];
}
// Defines the url to return to in case of error in the next sql statement
$err_url   = $goto . '?' . PMA_URL_getCommon($db, $table);

//Set default datalabel if not selected
if ( !isset($_POST['zoom_submit']) || $_POST['dataLabel'] == '') {
    $dataLabel = PMA_getDisplayField($db, $table);
} else {
    $dataLabel = $_POST['dataLabel'];
}

// Displays the zoom search form
$response->addHTML($table_search->getSecondaryTabs());
$response->addHTML($table_search->getSelectionForm($goto, $dataLabel));

/*
 * Handle the input criteria and generate the query result
 * Form for displaying query results
 */
if (isset($_POST['zoom_submit'])
    && $_POST['criteriaColumnNames'][0] != 'pma_null'
    && $_POST['criteriaColumnNames'][1] != 'pma_null'
    && $_POST['criteriaColumnNames'][0] != $_POST['criteriaColumnNames'][1]
) {
    //Query generation part
    $sql_query = $table_search->buildSqlQuery();
    $sql_query .= ' LIMIT ' . $_POST['maxPlotLimit'];

    //Query execution part
    $result = $GLOBALS['dbi']->query(
        $sql_query . ";", null, PMA_DatabaseInterface::QUERY_STORE
    );
    $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        //Need a row with indexes as 0,1,2 for the getUniqueCondition
        // hence using a temporary array
        $tmpRow = array();
        foreach ($row as $val) {
            $tmpRow[] = $val;
        }
        //Get unique conditon on each row (will be needed for row update)
        $uniqueCondition = PMA_Util::getUniqueCondition(
            $result, count($table_search->getColumnNames()), $fields_meta, $tmpRow,
            true
        );
        //Append it to row array as where_clause
        $row['where_clause'] = $uniqueCondition[0];

        $tmpData = array(
            $_POST['criteriaColumnNames'][0] =>
                $row[$_POST['criteriaColumnNames'][0]],
            $_POST['criteriaColumnNames'][1] =>
                $row[$_POST['criteriaColumnNames'][1]],
            'where_clause' => $uniqueCondition[0]
        );
        $tmpData[$dataLabel] = ($dataLabel) ? $row[$dataLabel] : '';
        $data[] = $tmpData;
    }
    unset($tmpData);

    //Displays form for point data and scatter plot
    $response->addHTML($table_search->getZoomResultsForm($goto, $data));
}
?>
