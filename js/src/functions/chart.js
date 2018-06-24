/**
 * Creates a Profiling Chart. Used in sql.js
 * and in server_status_monitor.js
 */
import JQPlotChartFactory from '../classes/Chart';

export function PMA_createProfilingChart (target, data) {
    // create the chart
    var factory = new JQPlotChartFactory();
    var chart = factory.createChart(ChartType.PIE, target);

    // create the data table and add columns
    var dataTable = new DataTable();
    dataTable.addColumn(ColumnType.STRING, '');
    dataTable.addColumn(ColumnType.NUMBER, '');
    dataTable.setData(data);

    var windowWidth = $(window).width();
    var location = 's';
    if (windowWidth > 768) {
        var location = 'se';
    }

    // draw the chart and return the chart object
    chart.draw(dataTable, {
        seriesDefaults: {
            rendererOptions: {
                showDataLabels:  true
            }
        },
        highlighter: {
            tooltipLocation: 'se',
            sizeAdjust: 0,
            tooltipAxes: 'pieref',
            formatString: '%s, %.9Ps'
        },
        legend: {
            show: true,
            location: location,
            rendererOptions: {
                numberColumns: 2
            }
        },
        // from http://tango.freedesktop.org/Tango_Icon_Theme_Guidelines#Color_Palette
        seriesColors: [
            '#fce94f',
            '#fcaf3e',
            '#e9b96e',
            '#8ae234',
            '#729fcf',
            '#ad7fa8',
            '#ef2929',
            '#888a85',
            '#c4a000',
            '#ce5c00',
            '#8f5902',
            '#4e9a06',
            '#204a87',
            '#5c3566',
            '#a40000',
            '#babdb6',
            '#2e3436'
        ]
    });
    return chart;
}