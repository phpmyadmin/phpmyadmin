/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 */
import { PMA_createProfilingChart } from './functions/chart';
import { jQuery as $ } from './utils/JqueryExtended';
/**
 * Unbind all event handlers before tearing down a page
 */
import { initTableSorter } from './server_status_sorter';

export function teardown1 () {
    var queryPieChart = $('#serverstatusquerieschart').data('queryPieChart');
    if (queryPieChart) {
        queryPieChart.destroy();
    }
}

export function onload1 () {
    // Build query statistics chart
    var cdata = [];
    try {
        $.each($('#serverstatusquerieschart').data('chart'), function (key, value) {
            cdata.push([key, parseInt(value, 10)]);
        });
        $('#serverstatusquerieschart').data(
            'queryPieChart',
            PMA_createProfilingChart(
                'serverstatusquerieschart',
                cdata
            )
        );
    } catch (exception) {
        // Could not load chart, no big deal...
    }

    initTableSorter('statustabs_queries');
}
