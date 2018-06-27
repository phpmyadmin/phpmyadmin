/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { createProfilingChart } from './functions/chart';
import { jQuery as $ } from './utils/JqueryExtended';
import { initTableSorter } from './server_status_sorter';

/**
 * @package PhpMyAdmin
 *
 * Server Status Queries
 */

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownServerStatusQueries () {
    var queryPieChart = $('#serverstatusquerieschart').data('queryPieChart');
    if (queryPieChart) {
        queryPieChart.destroy();
    }
}

function onloadServerStatusQueries () {
    // Build query statistics chart
    var cdata = [];
    try {
        $.each($('#serverstatusquerieschart').data('chart'), function (key, value) {
            cdata.push([key, parseInt(value, 10)]);
        });
        $('#serverstatusquerieschart').data(
            'queryPieChart',
            createProfilingChart(
                'serverstatusquerieschart',
                cdata
            )
        );
    } catch (exception) {
        // Could not load chart, no big deal...
    }

    initTableSorter('statustabs_queries');
}

/**
 * Module export
 */
export {
    teardownServerStatusQueries,
    onloadServerStatusQueries
};
