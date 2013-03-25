<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

/*
 * Execute the query and return the result
 */
if (isset($_REQUEST['ajax_request'])
    && isset($_REQUEST['pos'])
    && isset($_REQUEST['session_max_rows'])
) {
    $response = PMA_Response::getInstance();

    if (strlen($GLOBALS['table']) && strlen($GLOBALS['db'])) {
        include './libraries/tbl_common.inc.php';
    } else {
        $response->isSuccess(false);
        $response->addJSON('message', __('Error'));
        exit;
    }

    $sql_with_limit = 'SELECT * FROM( ' . $sql_query . ' ) AS `temp_res` LIMIT '
        . $_REQUEST['pos'] . ', ' . $_REQUEST['session_max_rows'];
    $data = array();
    $result = PMA_DBI_try_query($sql_with_limit);
    while ($row = PMA_DBI_fetch_assoc($result)) {
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
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $scripts->addFile('canvg/flashcanvas.js');
}

/**
 * Runs common work
 */
if (strlen($GLOBALS['table'])) {
    $url_params['goto'] = $cfg['DefaultTabTable'];
    $url_params['back'] = 'tbl_sql.php';
    include 'libraries/tbl_common.inc.php';
    include 'libraries/tbl_info.inc.php';
} elseif (strlen($GLOBALS['db'])) {
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

$result = PMA_DBI_try_query($sql_query);
$fields_meta = PMA_DBI_get_fields_meta($result);
while ($row = PMA_DBI_fetch_assoc($result)) {
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
// pma_token/url_query needed for chart export
$htmlString = '<script type="text/javascript">'
    . "pma_token = '" . $_SESSION[' PMA_token '] . "';"
    . "url_query = '" . $url_query . "';"
    . '</script>'
    . '<!-- Display Chart options -->'
    . '<div id="div_view_options">'
    . '<form method="post" id="tblchartform" action="tbl_chart.php" class="ajax">'
    . PMA_generate_common_hidden_inputs($url_params)
    . '<fieldset>'
    . '<legend>' . __('Display chart') . '</legend>'
    . '<div style="float:left; width:420px;">'
    . '<input type="radio" name="chartType" value="bar" id="radio_bar" />'
    . '<label for ="radio_bar">' . _pgettext('Chart type', 'Bar') . '</label>'
    . '<input type="radio" name="chartType" value="column" id="radio_column" />'
    . '<label for ="radio_column">' . _pgettext('Chart type', 'Column') . '</label>'
    . '<input type="radio" name="chartType" value="line" id="radio_line"'
    . ' checked="checked" />'
    . '<label for ="radio_line">' . _pgettext('Chart type', 'Line') . '</label>'
    . '<input type="radio" name="chartType" value="spline" id="radio_spline" />'
    . '<label for ="radio_spline">' . _pgettext('Chart type', 'Spline') . '</label>'
    . '<input type="radio" name="chartType" value="area" id="radio_area" />'
    . '<label for ="radio_area">' . _pgettext('Chart type', 'Area') . '</label>'
    . '<span class="span_pie" style="display:none;">'
    . '<input type="radio" name="chartType" value="pie" id="radio_pie" />'
    . '<label for ="radio_pie">' . _pgettext('Chart type', 'Pie') . '</label>'
    . '</span>'
    . '<span class="span_timeline" style="display:none;">'
    . '<input type="radio" name="chartType" value="timeline" id="radio_timeline" />'
    . '<label for ="radio_timeline">' . _pgettext('Chart type', 'Timeline')
    . '</label>'
    . '</span>'
    . '<br /><br />'
    . '<span class="barStacked">'
    . '<input type="checkbox" name="barStacked" value="1"'
    . ' id="checkbox_barStacked" />'
    . '<label for ="checkbox_barStacked">' . __('Stacked') . '</label>'
    . '</span>'
    . '<br /><br />'
    . '<input type="text" name="chartTitle" value="' . __('Chart title') . '">'
    . '</div>';

$htmlString .= '<div style="float:left; padding-left:40px;">'
    . '<label for="select_chartXAxis">' .  __('X-Axis:') . '</label>'
    . '<select name="chartXAxis" id="select_chartXAxis">';

$yaxis = null;
foreach ($keys as $idx => $key) {
    if ($yaxis === null) {
        $htmlString .= '<option value="' . htmlspecialchars($idx)
            . '" selected="selected">' . htmlspecialchars($key) . '</option>';
        $yaxis = $idx;
    } else {
        $htmlString .= '<option value="' . htmlspecialchars($idx) . '">'
            . htmlspecialchars($key) . '</option>';
    }
}

$htmlString .= '</select><br />'
    . '<label for="select_chartSeries">' . __('Series:') . '</label>'
    . '<select name="chartSeries" id="select_chartSeries" multiple="multiple">';

foreach ($keys as $idx => $key) {
    if (in_array($fields_meta[$idx]->type, $numeric_types)) {
        if ($idx == $yaxis && $numeric_column_count > 1) {
            $htmlString .= '<option value="' . htmlspecialchars($idx) . '">'
                . htmlspecialchars($key) . '</option>';
        } else {
            $htmlString .= '<option value="' . htmlspecialchars($idx)
                . '" selected="selected">' . htmlspecialchars($key)
                . '</option>';
        }
    }
}

$htmlString .= '</select>'
    . '<input type="hidden" name="dateTimeCols" value="';

$date_time_types = array('date', 'datetime', 'timestamp');
foreach ($keys as $idx => $key) {
    if (in_array($fields_meta[$idx]->type, $date_time_types)) {
        $htmlString .= $idx . " ";
    }
}
$htmlString .= '" />'
    . '</div>';

$htmlString .= '<div style="float:left; padding-left:40px;">'
    . '<label for="xaxis_label">' . __('X-Axis label:') . '</label>'
    . '<input style="margin-top:0;" type="text" name="xaxis_label" id="xaxis_label"'
    . ' value="'
    . (($yaxis == -1) ? __('X Values') : htmlspecialchars($keys[$yaxis]))
    . '" /><br />'
    . '<label for="yaxis_label">' . __('Y-Axis label:') . '</label>'
    . '<input type="text" name="yaxis_label" id="yaxis_label" value="'
    . __('Y Values') . '" /><br />'
    . '</div>'
    . '<p style="clear:both;">&nbsp;</p>'
    . '<fieldset>'
    . '<div>'
    . '<label for="pos">' . __('Start row') . ': ' . "\n" . '</label>'
    . '<input type="text" name="pos" size="3" value="'
    . $_SESSION['tmp_user_values']['pos'] . '" />'
    . '<label for="session_max_rows">'
    . __('Number of rows') . ': ' . "\n" . '</label>'
    . '<input type="text" name="session_max_rows" size="3" value="'
    . (($_SESSION['tmp_user_values']['max_rows'] != 'all')
        ? $_SESSION['tmp_user_values']['max_rows']
        : $GLOBALS['cfg']['MaxRows'])
    . '" />'
    . '<input type="submit" name="submit" class="Go" value="' . __('Go') . '" />'
    . '<input type="hidden" name="sql_query" value="'
    . htmlspecialchars($sql_query) . '" />'
    . '</div>'
    . '</fieldset>'
    . '<p style="clear:both;">&nbsp;</p>'
    . '<div id="resizer" style="width:600px; height:400px;">'
    . '<div id="querychart">'
    . '</div>'
    . '</div>'
    . '</fieldset>'
    . '</form>'
    . '</div>';

$response->addHTML($htmlString);
?>
