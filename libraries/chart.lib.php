<?php

require_once './libraries/chart/pma_ofc_pie.php';

require_once './libraries/chart/pma_pChart_pie.php';
require_once './libraries/chart/pma_pChart_bar.php';

/**
 * Chart functions used to generate various types
 * of charts.
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/*
 * Formats a chart for status page.
 */
function PMA_chart_status($data)
{
    // format keys which will be shown in the chart
    $chartData = array();
    foreach($data as $dataKey => $dataValue) {
        $key = ucwords(str_replace(array('Com_', '_'), array('', ' '), $dataKey));
        $value = (int)$dataValue;
        $chartData[$key] = $value;
    }
    
    //$chart = new PMA_OFC_Pie(__('Query type'), $chartData, $options);
    $chart = new PMA_pChart_Pie(
            __('Query statistics'),
            $chartData);
    echo $chart->toString();
}

/*
 * Formats a chart for profiling page.
 */
function PMA_chart_profiling($data)
{
    $chartData = array();
    foreach($data as $dataValue) {
        $value = (int)($dataValue['Duration']*1000000);
        $key = ucwords($dataValue['Status']);
        $chartData[$key] = $value;
    }

    $chart = new PMA_pChart_Pie(
            __('Query execution time comparison (in microseconds)'),
            $chartData);
    echo $chart->toString();
}

/*
 * Formats a chart for query results page.
 */
function PMA_chart_results($data)
{
    $chartData = array();

    // loop through the rows
    foreach ($data as $row) {

        // loop through the columns in the row
        foreach ($row as $key => $value) {
            $chartData[$key][] = $value;
        }
    }

    $chart = new PMA_pChart_bar(
            __('Query results'),
            $chartData);
    echo $chart->toString();
}

?>
