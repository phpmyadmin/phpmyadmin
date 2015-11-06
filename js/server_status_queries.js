/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_status_queries.js', function () {
    var queryPieChart = $('#serverstatusquerieschart').data('queryPieChart');
    if (queryPieChart) {
        queryPieChart.destroy();
    }
});

AJAX.registerOnload('server_status_queries.js', function () {
    // Build query statistics chart
    var cdata = [];
    try {
        $.each(jQuery.parseJSON($('#serverstatusquerieschart_data').text()), function (key, value) {
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
});
