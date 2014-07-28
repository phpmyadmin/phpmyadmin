<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying chart
 *
 * @usedby  tbl_chart.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Function to get html for pma_token and url_query
 *
 * @param string $url_query url query
 *
 * @return string
 */
function PMA_getHtmlForPmaTokenAndUrlQuery($url_query)
{
    $htmlString = '<script type="text/javascript">'
        . "pma_token = '" . $_SESSION[' PMA_token '] . "';"
        . "url_query = '" . $url_query . "';"
        . '</script>';
    return $htmlString;
}

/**
 * Function to get html for the chart type options
 *
 * @return string
 */
function PMA_getHtmlForChartTypeOptions()
{
    $html = '<input type="radio" name="chartType" value="bar" id="radio_bar" />'
        . '<label for ="radio_bar">' . _pgettext('Chart type', 'Bar') . '</label>'
        . '<input type="radio" name="chartType" value="column" id="radio_column" />'
        . '<label for ="radio_column">' . _pgettext('Chart type', 'Column')
        . '</label>'
        . '<input type="radio" name="chartType" value="line" id="radio_line"'
        . ' checked="checked" />'
        . '<label for ="radio_line">' . _pgettext('Chart type', 'Line') . '</label>'
        . '<input type="radio" name="chartType" value="spline" id="radio_spline" />'
        . '<label for ="radio_spline">' . _pgettext('Chart type', 'Spline')
        . '</label>'
        . '<input type="radio" name="chartType" value="area" id="radio_area" />'
        . '<label for ="radio_area">' . _pgettext('Chart type', 'Area') . '</label>'
        . '<span class="span_pie" style="display:none;">'
        . '<input type="radio" name="chartType" value="pie" id="radio_pie" />'
        . '<label for ="radio_pie">' . _pgettext('Chart type', 'Pie') . '</label>'
        . '</span>'
        . '<span class="span_timeline" style="display:none;">'
        . '<input type="radio" name="chartType" '
        . 'value="timeline" id="radio_timeline" />'
        . '<label for ="radio_timeline">' . _pgettext('Chart type', 'Timeline')
        . '</label>'
        . '</span>'
        . '<span class="span_scatter" style="display:none;">'
        . '<input type="radio" name="chartType" '
        . 'value="scatter" id="radio_scatter" />'
        . '<label for ="radio_scatter">' . _pgettext('Chart type', 'Scatter')
        . '</label>'
        . '</span>'
        . '<br /><br />';

    return $html;
}

/**
 * Function to get html for the bar stacked option
 *
 * @return string
 */
function PMA_getHtmlForStackedOption()
{
    $html = '<span class="barStacked" style="display:none;">'
    . '<input type="checkbox" name="barStacked" value="1"'
    . ' id="checkbox_barStacked" />'
    . '<label for ="checkbox_barStacked">' . __('Stacked') . '</label>'
    . '</span>'
    . '<br /><br />';

    return $html;
}

/**
 * Function to get html for the chart x axis options
 *
 * @param array $keys   keys
 * @param int   &$yaxis y axis
 *
 * @return string
 */
function PMA_getHtmlForChartXAxisOptions($keys, &$yaxis)
{
    $htmlString = '<div style="float:left; padding-left:40px;">'
        . '<label for="select_chartXAxis">' .  __('X-Axis:') . '</label>'
        . '<select name="chartXAxis" id="select_chartXAxis">';

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
    $htmlString .= '</select>';

    return $htmlString;
}


/**
 * Function to get html for chart series options
 *
 * @param array $keys                 keys
 * @param array $fields_meta          fields meta
 * @param array $numeric_types        numeric types
 * @param int   $yaxis                y axis
 * @param int   $numeric_column_count numeric column count
 *
 * @return string
 */
function PMA_getHtmlForChartSeriesOptions($keys, $fields_meta, $numeric_types,
    $yaxis, $numeric_column_count
) {
    $htmlString = '<br />'
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
    $htmlString .= '</select>';
    return $htmlString;
}

/**
 * Function to get html for date time columns
 *
 * @param array $keys        keys
 * @param array $fields_meta fields meta
 *
 * @return string
 */
function PMA_getHtmlForDateTimeCols($keys, $fields_meta)
{
    $htmlString = '<input type="hidden" name="dateTimeCols" value="';

    $date_time_types = array('date', 'datetime', 'timestamp');
    foreach ($keys as $idx => $key) {
        if (in_array($fields_meta[$idx]->type, $date_time_types)) {
            $htmlString .= $idx . " ";
        }
    }
    $htmlString .= '" />';

    return $htmlString;
}

/**
 * Function to get html for date time columns
 *
 * @param array $keys          keys
 * @param array $fields_meta   fields meta
 * @param array $numeric_types numeric types
 *
 * @return string
 */
function PMA_getHtmlForNumericCols($keys, $fields_meta, $numeric_types)
{
    $htmlString = '<input type="hidden" name="numericCols" value="';
    foreach ($keys as $idx => $key) {
        if (in_array($fields_meta[$idx]->type, $numeric_types)) {
            $htmlString .= $idx . " ";
        }
    }
    $htmlString .= '" />';

    return $htmlString;
}

/**
 * Function to get html for the table axis label options
 *
 * @param int   $yaxis y axis
 * @param array $keys  keys
 *
 * @return string
 */
function PMA_getHtmlForTableAxisLabelOptions($yaxis, $keys)
{
    $htmlString = '<div style="float:left; padding-left:40px;">'
    . '<label for="xaxis_label">' . __('X-Axis label:') . '</label>'
    . '<input style="margin-top:0;" type="text" name="xaxis_label" id="xaxis_label"'
    . ' value="'
    . (($yaxis == -1) ? __('X Values') : htmlspecialchars($keys[$yaxis]))
    . '" /><br />'
    . '<label for="yaxis_label">' . __('Y-Axis label:') . '</label>'
    . '<input type="text" name="yaxis_label" id="yaxis_label" value="'
    . __('Y Values') . '" /><br />'
    . '</div>';

    return $htmlString;
}

/**
 * Function to get html for the start row and number of rows options
 *
 * @param string $sql_query sql query
 *
 * @return string
 */
function PMA_getHtmlForStartAndNumberOfRowsOptions($sql_query)
{
    $htmlString = '<p style="clear:both;">&nbsp;</p>'
        . '<fieldset>'
        . '<div>'
        . '<label for="pos">' . __('Start row:') . '</label>'
        . '<input type="text" name="pos" size="3" value="'
        . $_SESSION['tmpval']['pos'] . '" />'
        . '<label for="session_max_rows">'
        . __('Number of rows:') . '</label>'
        . '<input type="text" name="session_max_rows" size="3" value="'
        . (($_SESSION['tmpval']['max_rows'] != 'all')
            ? $_SESSION['tmpval']['max_rows']
            : $GLOBALS['cfg']['MaxRows'])
        . '" />'
        . '<input type="submit" name="submit" class="Go" value="' . __('Go')
        . '" />'
        . '<input type="hidden" name="sql_query" value="'
        . htmlspecialchars($sql_query) . '" />'
        . '</div>'
        . '</fieldset>';

    return $htmlString;
}

/**
 * Function to get html for the chart area div
 *
 * @return string
 */
function PMA_getHtmlForChartAreaDiv()
{
    $htmlString = '<p style="clear:both;">&nbsp;</p>'
        . '<div id="resizer" style="width:600px; height:400px;">'
        . '<div id="querychart">'
        . '</div>'
        . '</div>';

    return $htmlString;
}

/**
 * Function to get html for displaying table chart
 *
 * @param string $url_query            url query
 * @param array  $url_params           url parameters
 * @param array  $keys                 keys
 * @param array  $fields_meta          fields meta
 * @param array  $numeric_types        numeric types
 * @param int    $numeric_column_count numeric column count
 * @param string $sql_query            sql query
 *
 * @return string
 */
function PMA_getHtmlForTableChartDisplay($url_query, $url_params, $keys,
    $fields_meta, $numeric_types, $numeric_column_count, $sql_query
) {
    // pma_token/url_query needed for chart export
    $htmlString = PMA_getHtmlForPmaTokenAndUrlQuery($url_query);
    $htmlString .= '<!-- Display Chart options -->'
        . '<div id="div_view_options">'
        . '<form method="post" id="tblchartform" action="tbl_chart.php" '
        . 'class="ajax">'
        . PMA_URL_getHiddenInputs($url_params)
        . '<fieldset>'
        . '<legend>' . __('Display chart') . '</legend>'
        . '<div style="float:left; width:420px;">';
    $htmlString .= PMA_getHtmlForChartTypeOptions();
    $htmlString .= PMA_getHtmlForStackedOption();

    $htmlString .= '<input type="text" name="chartTitle" value="'
        . __('Chart title')
        . '">'
        . '</div>';
    $yaxis = null;
    $htmlString .= PMA_getHtmlForChartXAxisOptions($keys, $yaxis);
    $htmlString .= PMA_getHtmlForChartSeriesOptions(
        $keys, $fields_meta, $numeric_types, $yaxis, $numeric_column_count
    );
    $htmlString .= PMA_getHtmlForDateTimeCols($keys, $fields_meta);
    $htmlString .= PMA_getHtmlForNumericCols($keys, $fields_meta, $numeric_types);
    $htmlString .= '</div>';

    $htmlString .= PMA_getHtmlForTableAxisLabelOptions($yaxis, $keys);
    $htmlString .= PMA_getHtmlForStartAndNumberOfRowsOptions($sql_query);

    $htmlString .= PMA_getHtmlForChartAreaDiv();

    $htmlString .= '</fieldset>'
        . '</form>'
        . '</div>';

    return $htmlString;
}
?>
