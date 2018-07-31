import JQPlotChartFactory, { DataTable, ColumnType } from '../../classes/Chart';
import { escapeHtml } from '../../utils/Sanitise';
import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
import { PMA_ajaxShowMessage } from '../../utils/show_ajax_messages';

export var TableChartEnum = {
    chart_data: {},
    temp_chart_title: null,

    currentChart: null,
    currentSettings: null,

    dateTimeCols: [],
    numericCols: []
};

function extractDate (dateString) {
    var matches;
    var match;
    var dateTimeRegExp = /[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/;
    var dateRegExp = /[0-9]{4}-[0-9]{2}-[0-9]{2}/;

    matches = dateTimeRegExp.exec(dateString);
    if (matches !== null && matches.length > 0) {
        match = matches[0];
        return new Date(match.substr(0, 4), parseInt(match.substr(5, 2), 10) - 1, match.substr(8, 2), match.substr(11, 2), match.substr(14, 2), match.substr(17, 2));
    } else {
        matches = dateRegExp.exec(dateString);
        if (matches !== null && matches.length > 0) {
            match = matches[0];
            return new Date(match.substr(0, 4), parseInt(match.substr(5, 2), 10) - 1, match.substr(8, 2));
        }
    }
    return null;
}

function PMA_queryChart (data, columnNames, settings) {
    if ($('#querychart').length === 0) {
        return;
    }

    var plotSettings = {
        title : {
            text : settings.title,
            escapeHtml: true
        },
        grid : {
            drawBorder : false,
            shadow : false,
            background : 'rgba(0,0,0,0)'
        },
        legend : {
            show : true,
            placement : 'outsideGrid',
            location : 'e',
            rendererOptions: {
                numberColumns: 2
            }
        },
        axes : {
            xaxis : {
                label : escapeHtml(settings.xaxisLabel)
            },
            yaxis : {
                label : settings.yaxisLabel
            }
        },
        stackSeries : settings.stackSeries
    };

    // create the chart
    var factory = new JQPlotChartFactory();
    var chart = factory.createChart(settings.type, 'querychart');

    // create the data table and add columns
    var dataTable = new DataTable();
    if (settings.type === 'timeline') {
        dataTable.addColumn(ColumnType.DATE, columnNames[settings.mainAxis]);
    } else if (settings.type === 'scatter') {
        dataTable.addColumn(ColumnType.NUMBER, columnNames[settings.mainAxis]);
    } else {
        dataTable.addColumn(ColumnType.STRING, columnNames[settings.mainAxis]);
    }

    var i;
    if (settings.seriesColumn === null) {
        $.each(settings.selectedSeries, function (index, element) {
            dataTable.addColumn(ColumnType.NUMBER, columnNames[element]);
        });

        // set data to the data table
        var columnsToExtract = [settings.mainAxis];
        $.each(settings.selectedSeries, function (index, element) {
            columnsToExtract.push(element);
        });
        var values = [];
        var newRow;
        var row;
        var col;
        for (i = 0; i < data.length; i++) {
            row = data[i];
            newRow = [];
            for (var j = 0; j < columnsToExtract.length; j++) {
                col = columnNames[columnsToExtract[j]];
                if (j === 0) {
                    if (settings.type === 'timeline') { // first column is date type
                        newRow.push(extractDate(row[col]));
                    } else if (settings.type === 'scatter') {
                        newRow.push(parseFloat(row[col]));
                    } else { // first column is string type
                        newRow.push(row[col]);
                    }
                } else { // subsequent columns are of type, number
                    newRow.push(parseFloat(row[col]));
                }
            }
            values.push(newRow);
        }
        dataTable.setData(values);
    } else {
        var seriesNames = {};
        var seriesNumber = 1;
        var seriesColumnName = columnNames[settings.seriesColumn];
        for (i = 0; i < data.length; i++) {
            if (! seriesNames[data[i][seriesColumnName]]) {
                seriesNames[data[i][seriesColumnName]] = seriesNumber;
                seriesNumber++;
            }
        }

        $.each(seriesNames, function (seriesName, seriesNumber) {
            dataTable.addColumn(ColumnType.NUMBER, seriesName);
        });

        var valueMap = {};
        var xValue;
        var value;
        var mainAxisName = columnNames[settings.mainAxis];
        var valueColumnName = columnNames[settings.valueColumn];
        for (i = 0; i < data.length; i++) {
            xValue = data[i][mainAxisName];
            value = valueMap[xValue];
            if (! value) {
                value = [xValue];
                valueMap[xValue] = value;
            }
            seriesNumber = seriesNames[data[i][seriesColumnName]];
            value[seriesNumber] = parseFloat(data[i][valueColumnName]);
        }

        var values = [];
        $.each(valueMap, function (index, value) {
            values.push(value);
        });
        dataTable.setData(values);
    }

    // draw the chart and return the chart object
    chart.draw(dataTable, plotSettings);
    return chart;
}

export function drawChart () {
    TableChartEnum.currentSettings.width = $('#resizer').width() - 20;
    TableChartEnum.currentSettings.height = $('#resizer').height() - 20;

    // TODO: a better way using .redraw() ?
    if (TableChartEnum.currentChart !== null) {
        TableChartEnum.currentChart.destroy();
    }

    var columnNames = [];
    $('select[name="chartXAxis"] option').each(function () {
        columnNames.push(escapeHtml($(this).text()));
    });
    try {
        TableChartEnum.currentChart = PMA_queryChart(TableChartEnum.chart_data, columnNames, TableChartEnum.currentSettings);
        if (TableChartEnum.currentChart !== null) {
            $('#saveChart').attr('href', TableChartEnum.currentChart.toImageString());
        }
    } catch (err) {
        PMA_ajaxShowMessage(err.message, false);
    }
}

export function getSelectedSeries () {
    var val = $('select[name="chartSeries"]').val() || [];
    var ret = [];
    $.each(val, function (i, v) {
        ret.push(parseInt(v, 10));
    });
    return ret;
}

export function onXAxisChange () {
    var $xAxisSelect = $('select[name="chartXAxis"]');
    TableChartEnum.currentSettings.mainAxis = parseInt($xAxisSelect.val(), 10);
    if (TableChartEnum.dateTimeCols.indexOf(TableChartEnum.currentSettings.mainAxis) !== -1) {
        $('span.span_timeline').show();
    } else {
        $('span.span_timeline').hide();
        if (TableChartEnum.currentSettings.type === 'timeline') {
            $('input#radio_line').prop('checked', true);
            TableChartEnum.currentSettings.type = 'line';
        }
    }
    if (TableChartEnum.numericCols.indexOf(TableChartEnum.currentSettings.mainAxis) !== -1) {
        $('span.span_scatter').show();
    } else {
        $('span.span_scatter').hide();
        if (TableChartEnum.currentSettings.type === 'scatter') {
            $('input#radio_line').prop('checked', true);
            TableChartEnum.currentSettings.type = 'line';
        }
    }
    var xaxis_title = $xAxisSelect.children('option:selected').text();
    $('input[name="xaxis_label"]').val(xaxis_title);
    TableChartEnum.currentSettings.xaxisLabel = xaxis_title;
}

export function onDataSeriesChange () {
    var $seriesSelect = $('select[name="chartSeries"]');
    TableChartEnum.currentSettings.selectedSeries = getSelectedSeries();
    var yaxis_title;
    if (TableChartEnum.currentSettings.selectedSeries.length === 1) {
        $('span.span_pie').show();
        yaxis_title = $seriesSelect.children('option:selected').text();
    } else {
        $('span.span_pie').hide();
        if (TableChartEnum.currentSettings.type === 'pie') {
            $('input#radio_line').prop('checked', true);
            TableChartEnum.currentSettings.type = 'line';
        }
        yaxis_title = PMA_messages.strYValues;
    }
    $('input[name="yaxis_label"]').val(yaxis_title);
    TableChartEnum.currentSettings.yaxisLabel = yaxis_title;
}
