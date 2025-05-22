import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { checkSqlQuery, prepareForAjaxRequest } from '../modules/functions.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';
import { escapeHtml } from '../modules/functions/escape.ts';
import { ColumnType, DataTable } from '../modules/chart.ts';

var chartData = {};
var tempChartTitle;

var currentChart = null;
var currentSettings = null;

var dateTimeCols = [];
var numericCols = [];

function extractDate (dateString) {
    var matches;
    var match;
    var dateTimeRegExp = /[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/;
    var dateRegExp = /[0-9]{4}-[0-9]{2}-[0-9]{2}/;

    matches = dateTimeRegExp.exec(dateString);
    if (matches !== null && matches.length > 0) {
        match = matches[0];

        return new Date(match.substring(0, 4), parseInt(match.substring(5, 7), 10) - 1, match.substring(8, 10), match.substring(11, 13), match.substring(14, 16), match.substring(17, 19));
    } else {
        matches = dateRegExp.exec(dateString);
        if (matches !== null && matches.length > 0) {
            match = matches[0];

            return new Date(match.substring(0, 4), parseInt(match.substring(5, 7), 10) - 1, match.substring(8, 10));
        }
    }

    return null;
}

function queryChart (data, columnNames, settings) {
    const queryChartCanvas = document.getElementById('queryChartCanvas') as HTMLCanvasElement;
    if (! queryChartCanvas) {
        return null;
    }

    var plotSettings = {
        title: {
            text: settings.title,
            escapeHtml: true
        },
        grid: {
            drawBorder: false,
            shadow: false,
            background: 'rgba(0,0,0,0)'
        },
        legend: {
            show: true,
            placement: 'outsideGrid',
            location: 'e',
            rendererOptions: {
                numberColumns: 2
            }
        },
        axes: {
            xaxis: {
                label: escapeHtml(settings.xaxisLabel)
            },
            yaxis: {
                label: settings.yaxisLabel
            }
        },
        stackSeries: settings.stackSeries
    };

    // create the data table and add columns
    const dataTable = new DataTable();
    if (settings.type === 'timeline') {
        dataTable.addColumn(ColumnType.DATE, columnNames[settings.mainAxis]);
    } else if (settings.type === 'scatter') {
        dataTable.addColumn(ColumnType.NUMBER, columnNames[settings.mainAxis]);
    } else {
        dataTable.addColumn(ColumnType.STRING, columnNames[settings.mainAxis]);
    }

    var i;
    var values = [];
    if (settings.seriesColumn === null) {
        $.each(settings.selectedSeries, function (index, element) {
            dataTable.addColumn(ColumnType.NUMBER, columnNames[element]);
        });

        // set data to the data table
        var columnsToExtract = [settings.mainAxis];
        $.each(settings.selectedSeries, function (index, element) {
            columnsToExtract.push(element);
        });

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

        $.each(seriesNames, function (seriesName) {
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

        $.each(valueMap, function (index, value) {
            values.push(value);
        });

        dataTable.setData(values);
    }

    let chartType = settings.type;
    if (chartType === 'spline' || chartType === 'timeline' || chartType === 'area') {
        chartType = 'line';
    } else if (chartType === 'column') {
        chartType = 'bar';
    }

    const chartData = dataTable.getData();
    const chartColumns = dataTable.getColumns();
    const labels = chartData.map(row => row[0]);
    const datasets = [];
    for (let i = 1; i < chartColumns.length; i++) {
        const data = chartData.map(function (row: any[]) {
            if (settings.type === 'scatter') {
                return { x: row[0], y: row[i] };
            }

            return row[i];
        });
        const dataset: Chart.ChartDataSets = { label: chartColumns[i].name, data: data };
        if (settings.type === 'area') {
            dataset.fill = 'start';
        }

        datasets.push(dataset);
    }

    const chartOptions = {
        type: chartType,
        data: { labels: labels, datasets: datasets },
        options: {
            animation: false,
            plugins: {
                legend: { position: 'right' },
                title: { display: plotSettings.title.text !== '', text: plotSettings.title.text }
            },
            indexAxis: settings.type === 'bar' ? 'y' : 'x',
            scales: {
                x: {
                    display: true,
                    title: { display: plotSettings.axes.xaxis.label !== '', text: plotSettings.axes.xaxis.label },
                    stacked: plotSettings.stackSeries,
                },
                y: {
                    display: true,
                    title: { display: plotSettings.axes.yaxis.label !== '', text: plotSettings.axes.yaxis.label },
                    stacked: plotSettings.stackSeries,
                }
            }
        }
    };
    if (settings.type === 'timeline') {
        // @ts-ignore
        chartOptions.options.scales.x.type = 'time';
    }

    // @ts-ignore
    let queryChart = window.Chart.getChart('queryChartCanvas');
    if (queryChart) {
        queryChart.destroy();
    }

    // @ts-ignore
    queryChart = new window.Chart(queryChartCanvas, chartOptions);

    if (settings.type === 'spline') {
        queryChart.options.elements.line.tension = 0.4;
        queryChart.update('none');
    }

    return queryChart;
}

function drawChart () {
    currentSettings.width = $('#resizer').width() - 20;
    currentSettings.height = $('#resizer').height() - 20;

    // TODO: a better way using .redraw() ?
    if (currentChart !== null) {
        currentChart.destroy();
    }

    var columnNames = [];
    $('#chartXAxisSelect option').each(function () {
        columnNames.push(escapeHtml($(this).text()));
    });

    try {
        currentChart = queryChart(chartData, columnNames, currentSettings);
        if (currentChart !== null) {
            $('#saveChart').attr('href', currentChart.toBase64Image());
        }
    } catch (err) {
        ajaxShowMessage(err.message, false);
    }
}

function getSelectedSeries () {
    var val = ($('#chartSeriesSelect').val() as string[]) || [];
    var ret = [];
    $.each(val, function (i, v) {
        ret.push(parseInt(v, 10));
    });

    return ret;
}

function onXAxisChange () {
    var $xAxisSelect = $('#chartXAxisSelect');
    currentSettings.mainAxis = parseInt(($xAxisSelect.val() as string), 10);
    if (dateTimeCols.indexOf(currentSettings.mainAxis) !== -1) {
        document.getElementById('timelineChartType').classList.remove('d-none');
    } else {
        document.getElementById('timelineChartType').classList.add('d-none');
        if (currentSettings.type === 'timeline') {
            $('#lineChartTypeRadio').prop('checked', true);
            currentSettings.type = 'line';
        }
    }

    if (numericCols.indexOf(currentSettings.mainAxis) !== -1) {
        document.getElementById('scatterChartType').classList.remove('d-none');
    } else {
        document.getElementById('scatterChartType').classList.add('d-none');
        if (currentSettings.type === 'scatter') {
            $('#lineChartTypeRadio').prop('checked', true);
            currentSettings.type = 'line';
        }
    }

    var xAxisTitle = $xAxisSelect.children('option:selected').text();
    $('#xAxisLabelInput').val(xAxisTitle);
    currentSettings.xaxisLabel = xAxisTitle;
}

function onDataSeriesChange () {
    var $seriesSelect = $('#chartSeriesSelect');
    currentSettings.selectedSeries = getSelectedSeries();
    var yAxisTitle;
    if (currentSettings.selectedSeries.length === 1) {
        document.getElementById('pieChartType').classList.remove('d-none');
        yAxisTitle = $seriesSelect.children('option:selected').text();
    } else {
        document.getElementById('pieChartType').classList.add('d-none');
        if (currentSettings.type === 'pie') {
            $('#lineChartTypeRadio').prop('checked', true);
            currentSettings.type = 'line';
        }

        yAxisTitle = window.Messages.strYValues;
    }

    $('#yAxisLabelInput').val(yAxisTitle);
    currentSettings.yaxisLabel = yAxisTitle;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/chart.js', function () {
    $('input[name="chartType"]').off('click');
    $('#barStackedCheckbox').off('click');
    $('#seriesColumnCheckbox').off('click');
    $('#chartTitleInput').off('focus').off('keyup').off('blur');
    $('#chartXAxisSelect').off('change');
    $('#chartSeriesSelect').off('change');
    $('#chartSeriesColumnSelect').off('change');
    $('#chartValueColumnSelect').off('change');
    $('#xAxisLabelInput').off('keyup');
    $('#yAxisLabelInput').off('keyup');
    $('#resizer').off('resizestop');
    $('#tblchartform').off('submit');
});

AJAX.registerOnload('table/chart.js', function () {
    // handle chart type changes
    $('input[name="chartType"]').on('click', function () {
        var type = currentSettings.type = $(this).val();
        if (type === 'bar' || type === 'column' || type === 'area') {
            document.getElementById('barStacked').classList.remove('d-none');
        } else {
            $('#barStackedCheckbox').prop('checked', false);
            $.extend(true, currentSettings, { stackSeries: false });
            document.getElementById('barStacked').classList.add('d-none');
        }

        drawChart();
    });

    // handle chosing alternative data format
    $('#seriesColumnCheckbox').on('click', function () {
        var $seriesColumn = $('#chartSeriesColumnSelect');
        var $valueColumn = $('#chartValueColumnSelect');
        var $chartSeries = $('#chartSeriesSelect');
        if ($(this).is(':checked')) {
            $seriesColumn.prop('disabled', false);
            $valueColumn.prop('disabled', false);
            $chartSeries.prop('disabled', true);
            currentSettings.seriesColumn = parseInt(($seriesColumn.val() as string), 10);
            currentSettings.valueColumn = parseInt(($valueColumn.val() as string), 10);
        } else {
            $seriesColumn.prop('disabled', true);
            $valueColumn.prop('disabled', true);
            $chartSeries.prop('disabled', false);
            currentSettings.seriesColumn = null;
            currentSettings.valueColumn = null;
        }

        drawChart();
    });

    // handle stacking for bar, column and area charts
    $('#barStackedCheckbox').on('click', function () {
        if ($(this).is(':checked')) {
            $.extend(true, currentSettings, { stackSeries: true });
        } else {
            $.extend(true, currentSettings, { stackSeries: false });
        }

        drawChart();
    });

    // handle changes in chart title
    $('#chartTitleInput')
        .on('focus', function () {
            tempChartTitle = $(this).val();
        })
        .on('keyup', function () {
            currentSettings.title = $('#chartTitleInput').val();
            drawChart();
        })
        .on('blur', function () {
            if ($(this).val() !== tempChartTitle) {
                drawChart();
            }
        });

    // handle changing the x-axis
    $('#chartXAxisSelect').on('change', function () {
        onXAxisChange();
        drawChart();
    });

    // handle changing the selected data series
    $('#chartSeriesSelect').on('change', function () {
        onDataSeriesChange();
        drawChart();
    });

    // handle changing the series column
    $('#chartSeriesColumnSelect').on('change', function () {
        currentSettings.seriesColumn = parseInt(($(this).val() as string), 10);
        drawChart();
    });

    // handle changing the value column
    $('#chartValueColumnSelect').on('change', function () {
        currentSettings.valueColumn = parseInt(($(this).val() as string), 10);
        drawChart();
    });

    // handle manual changes to the chart x-axis labels
    $('#xAxisLabelInput').on('keyup', function () {
        currentSettings.xaxisLabel = $(this).val();
        drawChart();
    });

    // handle manual changes to the chart y-axis labels
    $('#yAxisLabelInput').on('keyup', function () {
        currentSettings.yaxisLabel = $(this).val();
        drawChart();
    });

    // handler for ajax form submission
    ($('#tblchartform') as JQuery<HTMLFormElement>).on('submit', function () {
        var $form = ($(this) as JQuery<HTMLFormElement>);
        if (window.codeMirrorEditor) {
            // @ts-ignore
            $form[0].elements.sql_query.value = window.codeMirrorEditor.getValue();
        }

        if (! checkSqlQuery($form[0])) {
            return false;
        }

        var $msgbox = ajaxShowMessage();
        prepareForAjaxRequest($form);
        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' &&
                data.success === true &&
                typeof data.chartData !== 'undefined') {
                chartData = JSON.parse(data.chartData);
                drawChart();
                ajaxRemoveMessage($msgbox);
            } else {
                ajaxShowMessage(data.error, false);
            }
        }, 'json'); // end $.post()

        return false;
    });

    // from jQuery UI
    $('#resizer').resizable({
        minHeight: 240,
        minWidth: 300
    })
        .width($('#div_view_options').width() - 50)
        .trigger('resizestop');

    currentSettings = {
        type: 'line',
        width: $('#resizer').width() - 20,
        height: $('#resizer').height() - 20,
        xaxisLabel: $('#xAxisLabelInput').val(),
        yaxisLabel: $('#yAxisLabelInput').val(),
        title: $('#chartTitleInput').val(),
        stackSeries: false,
        mainAxis: parseInt(($('#chartXAxisSelect').val() as string), 10),
        selectedSeries: getSelectedSeries(),
        seriesColumn: null
    };

    var vals = ($('input[name="dateTimeCols"]').val() as string).split(' ');
    $.each(vals, function (i, v) {
        dateTimeCols.push(parseInt(v, 10));
    });

    vals = ($('input[name="numericCols"]').val() as string).split(' ');
    $.each(vals, function (i, v) {
        numericCols.push(parseInt(v, 10));
    });

    onXAxisChange();
    onDataSeriesChange();

    $('#tblchartform').trigger('submit');
});
