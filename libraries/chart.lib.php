<?php

require_once './libraries/chart/pma_ofc_pie.php';

require_once './libraries/chart/pma_pchart_pie.php';
require_once './libraries/chart/pma_pchart_single_bar.php';
require_once './libraries/chart/pma_pchart_multi_bar.php';
require_once './libraries/chart/pma_pchart_stacked_bar.php';
require_once './libraries/chart/pma_pchart_single_line.php';
require_once './libraries/chart/pma_pchart_multi_line.php';
require_once './libraries/chart/pma_pchart_single_radar.php';
require_once './libraries/chart/pma_pchart_multi_radar.php';

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
            array_slice($chartData, 0, 18, true));
    $chartCode = $chart->toString();
    PMA_handle_chart_err($chart->getErrors());
    echo $chartCode;
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
            array_slice($chartData, 0, 18, true));
    $chartCode = $chart->toString();
    PMA_handle_chart_err($chart->getErrors());
    echo $chartCode;
}

/*
 * Formats a chart for query results page.
 */
function PMA_chart_results($data, &$chartSettings)
{
    $chartData = array();
    $chart = null;

    // set default title if not already set
    if (!empty($chartSettings['title'])) {
        $chartTitle = $chartSettings['title'];
    }
    else {
        $chartTitle = __('Query results');
    }

    // set default type if not already set
    if (empty($chartSettings['type'])) {
        $chartSettings['type'] = 'bar';
    }

    // set default bar type if needed
    if ($chartSettings['type'] == 'bar' && empty($chartSettings['barType'])) {
        $chartSettings['barType'] = 'stacked';
    }

    // default for legend
    $chartSettings['legend'] = false;

    // default for muti series
    $chartSettings['multi'] = false;

    if (!isset($data[0])) {
        // empty data
        return __('No data found for the chart.');
    }

    if (count($data[0]) == 2) {
        // Two columns in every row.
        // This data is suitable for a simple bar chart.

        if ($chartSettings['type'] == 'pie') {
            // loop through the rows, data for pie chart has to be formated
            // in a different way then in other charts.
            foreach ($data as $row) {
                $values = array_values($row);
                $chartData[$values[0]] = $values[1];
            }

            $chartSettings['legend'] = true;
            $chart = new PMA_pChart_pie($chartTitle, $chartData, $chartSettings);
        }
        else {
            // loop through the rows
            foreach ($data as $row) {
                // loop through the columns in the row
                foreach ($row as $key => $value) {
                    $chartData[$key][] = $value;
                }
            }

            switch ($chartSettings['type']) {
                case 'bar':
                default:
                    $chart = new PMA_pChart_single_bar($chartTitle, $chartData, $chartSettings);
                    break;
                case 'line':
                    $chart = new PMA_pChart_single_line($chartTitle, $chartData, $chartSettings);
                    break;
                case 'radar':
                    $chart = new PMA_pChart_single_radar($chartTitle, $chartData, $chartSettings);
                    break;
            }
        }        
    }
    else if (count($data[0]) == 3) {
        // Three columns (x axis, y axis, series) in every row.
        // This data is suitable for a stacked bar chart.
        $chartSettings['multi'] = true;

        $keys = array_keys($data[0]);
        $xAxisKey = $keys[0];
        $yAxisKey = $keys[1];
        $seriesKey = $keys[2];
        
        // get all the series labels
        $seriesLabels = array();
        foreach ($data as $row) {
            $seriesLabels[] = $row[$seriesKey];
        }
        $seriesLabels = array_unique($seriesLabels);

        // loop through the rows
        $currentXLabel = $data[0][$xAxisKey];
        foreach ($data as $row) {

            // save the label
            // use the same value as the key and the value to get rid of duplicate results
            $chartData[$xAxisKey][$row[$xAxisKey]] = $row[$xAxisKey];

            // make sure to set value to every serie
            $currentSeriesLabel = (string)$row[$seriesKey];
            foreach ($seriesLabels as $seriesLabelsValue) {
                if ($currentSeriesLabel == $seriesLabelsValue) {
                    // the value os for this serie
                    $chartData[$yAxisKey][$seriesLabelsValue][$row[$xAxisKey]] = (int)$row[$yAxisKey];
                }
                else if (!isset($chartData[$yAxisKey][$seriesLabelsValue][$row[$xAxisKey]])) {
                    // if the value for this serie is not set, set it to 0
                    $chartData[$yAxisKey][$seriesLabelsValue][$row[$xAxisKey]] = 0;
                }
            }
        }

        $chartSettings['legend'] = true;

        // determine the chart type
        switch ($chartSettings['type']) {
            case 'bar':
            default:

                // determine the bar chart type
                switch ($chartSettings['barType']) {
                    case 'stacked':
                    default:
                        $chart = new PMA_pChart_stacked_bar($chartTitle, $chartData, $chartSettings);
                        break;
                    case 'multi':
                        $chart = new PMA_pChart_multi_bar($chartTitle, $chartData, $chartSettings);
                        break;
                }
                break;
            
            case 'line':
                $chart = new PMA_pChart_multi_line($chartTitle, $chartData, $chartSettings);
                break;
            case 'radar':
                $chart = new PMA_pChart_multi_radar($chartTitle, $chartData, $chartSettings);
                break;
        }
    }
    else {
        // unknown data
        return __('Unknown data format.');
    }

    $chartCode = $chart->toString();
    $chartSettings = $chart->getSettings();
    PMA_handle_chart_err($chart->getErrors());
    return $chartCode;
}

function PMA_handle_chart_err($errors)
{
    PMA_warnMissingExtension('GD', false, 'GD extension is needed for charts.');
}

?>
