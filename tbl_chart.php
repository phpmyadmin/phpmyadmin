<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_chart.lib.php';

/*
 * Execute the query and return the result
 */
if (isset($_REQUEST['ajax_request'])
    && isset($_REQUEST['pos'])
    && isset($_REQUEST['session_max_rows'])
) {
    $response = PMA_Response::getInstance();

    $tableLength = /*overload*/mb_strlen($GLOBALS['table']);
    $dbLength = /*overload*/mb_strlen($GLOBALS['db']);
    if ($tableLength && $dbLength) {
        include './libraries/tbl_common.inc.php';
    }

    $sql_with_limit = 'SELECT * FROM( ' . $sql_query . ' ) AS `temp_res` LIMIT '
        . $_REQUEST['pos'] . ', ' . $_REQUEST['session_max_rows'];
    $data = array();
    $result = $GLOBALS['dbi']->tryQuery($sql_with_limit);
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        $data[] = $row;
    }

    if (empty($data)) {
        $response->isSuccess(false);
        $response->addJSON('message', __('No data to display'));
        exit;
    }
    $sanitized_data = array();

    foreach ($data as $data_row_number => $data_row) {
        $tmp_row = array();
        foreach ($data_row as $data_column => $data_value) {
            $tmp_row[htmlspecialchars($data_column)] = htmlspecialchars($data_value);
        }
        $sanitized_data[] = $tmp_row;
    }
    $response->isSuccess(true);
    $response->addJSON('message', null);
    $response->addJSON('chartData', json_encode($sanitized_data));
    unset($sanitized_data);
    exit;
}

$response = PMA_Response::getInstance();
// Throw error if no sql query is set
if (! isset($sql_query) || $sql_query == '') {
    $response->isSuccess(false);
    $response->addHTML(
        PMA_Message::error(__('No SQL query was set to fetch data.'))
    );
    exit;
}
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('chart.js');
$scripts->addFile('tbl_chart.js');
$scripts->addFile('jqplot/jquery.jqplot.js');
$scripts->addFile('jqplot/plugins/jqplot.barRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.categoryAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.pointLabels.js');
$scripts->addFile('jqplot/plugins/jqplot.pieRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.highlighter.js');

/**
 * Runs common work
 */
if (/*overload*/mb_strlen($GLOBALS['table'])) {
    $url_params['goto'] = $cfg['DefaultTabTable'];
    $url_params['back'] = 'tbl_sql.php';
    include 'libraries/tbl_common.inc.php';
    include 'libraries/tbl_info.inc.php';
} elseif (/*overload*/mb_strlen($GLOBALS['db'])) {
    $url_params['goto'] = $cfg['DefaultTabDatabase'];
    $url_params['back'] = 'sql.php';
    include 'libraries/db_common.inc.php';
    include 'libraries/db_info.inc.php';
} else {
    $url_params['goto'] = $cfg['DefaultTabServer'];
    $url_params['back'] = 'sql.php';
    include 'libraries/server_common.inc.php';
}

$data = array();

$result = $GLOBALS['dbi']->tryQuery($sql_query);
$fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
    $data[] = $row;
}

$keys = array_keys($data[0]);

$numeric_types = array('int', 'real');
$numeric_column_count = 0;
foreach ($keys as $idx => $key) {
    if (in_array($fields_meta[$idx]->type, $numeric_types)) {
        $numeric_column_count++;
    }
}
if ($numeric_column_count == 0) {
    $response->isSuccess(false);
    $response->addJSON(
        'message',
        __('No numeric columns present in the table to plot.')
    );
    exit;
}

// get settings if any posted
$chartSettings = array();
if (PMA_isValid($_REQUEST['chartSettings'], 'array')) {
    $chartSettings = $_REQUEST['chartSettings'];
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

/**
 * Displays the page
 */
$htmlString = PMA_getHtmlForTableChartDisplay(
    $url_query, $url_params, $keys, $fields_meta, $numeric_types,
    $numeric_column_count, $sql_query
);

$response->addHTML($htmlString);
?>
