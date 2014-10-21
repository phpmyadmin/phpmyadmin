/* vim: set expandtab sw=4 ts=4 sts=4: */

var chart_data = {};
var temp_chart_title;

var currentChart = null;
var currentSettings = null;

var dateTimeCols = [];
var numericCols = [];

function extractDate(dateString) {
    var matches, match;
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

function PMA_queryChart(data, columnNames, settings) {
    if ($('#querychart').length === 0) {
        return;
    }

    var jqPlotSettings = {
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
            location : 'e'
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
    var chart = factory.createChart(settings.type, "querychart");

    // create the data table and add columns
    var dataTable = new DataTable();
    if (settings.type == 'timeline') {
        dataTable.addColumn(ColumnType.DATE, columnNames[settings.mainAxis]);
    } else if (settings.type == 'scatter') {
        dataTable.addColumn(ColumnType.NUMBER, columnNames[settings.mainAxis]);
    } else {
        dataTable.addColumn(ColumnType.STRING, columnNames[settings.mainAxis]);
    }
    $.each(settings.selectedSeries, function (index, element) {
        dataTable.addColumn(ColumnType.NUMBER, columnNames[element]);
    });

    // set data to the data table
    var columnsToExtract = [ settings.mainAxis ];
    $.each(settings.selectedSeries, function (index, element) {
        columnsToExtract.push(element);
    });
    var values = [], newRow, row, col;
    for (var i = 0; i < data.length; i++) {
        row = data[i];
        newRow = [];
        for (var j = 0; j < columnsToExtract.length; j++) {
            col = columnNames[columnsToExtract[j]];
            if (j === 0) {
                if (settings.type == 'timeline') { // first column is date type
                    newRow.push(extractDate(row[col]));
                } else if (settings.type == 'scatter') {
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

    // draw the chart and return the chart object
    chart.draw(dataTable, jqPlotSettings);
    return chart;
}

function drawChart() {
    currentSettings.width = $('#resizer').width() - 20;
    currentSettings.height = $('#resizer').height() - 20;

    // TODO: a better way using .redraw() ?
    if (currentChart !== null) {
        currentChart.destroy();
    }

    var columnNames = [];
    $('select[name="chartXAxis"] option').each(function () {
        columnNames.push($(this).text());
    });
    try {
        currentChart = PMA_queryChart(chart_data, columnNames, currentSettings);
    } catch (err) {
        PMA_ajaxShowMessage(err.message, false);
    }
}

function getSelectedSeries() {
    var val = $('select[name="chartSeries"]').val() || [];
    var ret = [];
    $.each(val, function (i, v) {
        ret.push(parseInt(v, 10));
    });
    return ret;
}

function onXAxisChange() {
    var $xAxisSelect = $('select[name="chartXAxis"]');
    currentSettings.mainAxis = parseInt($xAxisSelect.val(), 10);
    if (dateTimeCols.indexOf(currentSettings.mainAxis) != -1) {
        $('span.span_timeline').show();
    } else {
        $('span.span_timeline').hide();
        if (currentSettings.type == 'timeline') {
            $('input#radio_line').prop('checked', true);
            currentSettings.type = 'line';
        }
    }
    if (numericCols.indexOf(currentSettings.mainAxis) != -1) {
        $('span.span_scatter').show();
    } else {
        $('span.span_scatter').hide();
        if (currentSettings.type == 'scatter') {
            $('input#radio_line').prop('checked', true);
            currentSettings.type = 'line';
        }
    }
    var xaxis_title = $xAxisSelect.children('option:selected').text();
    $('input[name="xaxis_label"]').val(xaxis_title);
    currentSettings.xaxisLabel = xaxis_title;
}

function onDataSeriesChange() {
    var $seriesSelect = $('select[name="chartSeries"]');
    currentSettings.selectedSeries = getSelectedSeries();
    var yaxis_title;
    if (currentSettings.selectedSeries.length == 1) {
        $('span.span_pie').show();
        yaxis_title = $seriesSelect.children('option:selected').text();
    } else {
        $('span.span_pie').hide();
        if (currentSettings.type == 'pie') {
            $('input#radio_line').prop('checked', true);
            currentSettings.type = 'line';
        }
        yaxis_title = PMA_messages.strYValues;
    }
    $('input[name="yaxis_label"]').val(yaxis_title);
    currentSettings.yaxisLabel = yaxis_title;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_chart.js', function () {
    $('input[name="chartType"]').unbind('click');
    $('input[name="barStacked"]').unbind('click');
    $('input[name="chartTitle"]').unbind('focus').unbind('keyup').unbind('blur');
    $('select[name="chartXAxis"]').unbind('change');
    $('select[name="chartSeries"]').unbind('change');
    $('input[name="xaxis_label"]').unbind('keyup');
    $('input[name="yaxis_label"]').unbind('keyup');
    $('#resizer').unbind('resizestop');
});

AJAX.registerOnload('tbl_chart.js', function () {

    // from jQuery UI
    $('#resizer').resizable({
        minHeight: 240,
        minWidth: 300
    })
    .width($('#div_view_options').width() - 50);

    $('#resizer').bind('resizestop', function (event, ui) {
        // make room so that the handle will still appear
        $('#querychart').height($('#resizer').height() * 0.96);
        $('#querychart').width($('#resizer').width() * 0.96);
        currentChart.redraw({
            resetAxes : true
        });
    });

    currentSettings = {
        type : 'line',
        width : $('#resizer').width() - 20,
        height : $('#resizer').height() - 20,
        xaxisLabel : $('input[name="xaxis_label"]').val(),
        yaxisLabel : $('input[name="yaxis_label"]').val(),
        title : $('input[name="chartTitle"]').val(),
        stackSeries : false,
        mainAxis : parseInt($('select[name="chartXAxis"]').val(), 10),
        selectedSeries : getSelectedSeries()
    };

    // handle chart type changes
    $('input[name="chartType"]').click(function () {
        var type = currentSettings.type = $(this).val();
        if (type == 'bar' || type == 'column' || type == 'area') {
            $('span.barStacked').show();
        } else {
            $('input[name="barStacked"]').attr('checked', false);
            $.extend(true, currentSettings, {stackSeries : false});
            $('span.barStacked').hide();
        }
        drawChart();
    });

    // handle stacking for bar, column and area charts
    $('input[name="barStacked"]').click(function () {
        if ($(this).is(':checked')) {
            $.extend(true, currentSettings, {stackSeries : true});
        } else {
            $.extend(true, currentSettings, {stackSeries : false});
        }
        drawChart();
    });

    // handle changes in chart title
    $('input[name="chartTitle"]')
    .focus(function () {
        temp_chart_title = $(this).val();
    })
    .keyup(function () {
        var title = $(this).val();
        if (title.length === 0) {
            title = ' ';
        }
        currentSettings.title = $('input[name="chartTitle"]').val();
        drawChart();
    })
    .blur(function () {
        if ($(this).val() != temp_chart_title) {
            drawChart();
        }
    });

    var vals = $('input[name="dateTimeCols"]').val().split(' ');
    $.each(vals, function (i, v) {
        dateTimeCols.push(parseInt(v, 10));
    });

    var vals = $('input[name="numericCols"]').val().split(' ');
    $.each(vals, function (i, v) {
        numericCols.push(parseInt(v, 10));
    });

    // handle changing the x-axis
    $('select[name="chartXAxis"]').change(function () {
        onXAxisChange();
        drawChart();
    });

    // handle changing the selected data series
    $('select[name="chartSeries"]').change(function () {
        onDataSeriesChange();
        drawChart();
    });

    // handle manual changes to the chart axis labels
    $('input[name="xaxis_label"]').keyup(function () {
        currentSettings.xaxisLabel = $(this).val();
        drawChart();
    });
    $('input[name="yaxis_label"]').keyup(function () {
        currentSettings.yaxisLabel = $(this).val();
        drawChart();
    });

    onXAxisChange();
    onDataSeriesChange();

    $("#tblchartform").submit();
});

/**
 * Ajax Event handler for 'Go' button click
 *
 */
$("#tblchartform").live('submit', function (event) {
    if (!checkFormElementInRange(this, 'session_max_rows', PMA_messages.strNotValidRowNumber, 1) ||
        !checkFormElementInRange(this, 'pos', PMA_messages.strNotValidRowNumber, 0 - 1)
    ) {
        return false;
    }

    var $form = $(this);
    if (codemirror_editor) {
        $form[0].elements['sql_query'].value = codemirror_editor.getValue();
    }
    if (!checkSqlQuery($form[0])) {
        return false;
    }
    // remove any div containing a previous error message
    $('.error').remove();
    var $msgbox = PMA_ajaxShowMessage();
    PMA_prepareForAjaxRequest($form);

    $.post($form.attr('action'), $form.serialize(), function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $('.success').fadeOut();
            if (typeof data.chartData != 'undefined') {
                chart_data = jQuery.parseJSON(data.chartData);
                drawChart();
                $('div#querychart').height($('div#resizer').height() * 0.96);
                $('div#querychart').width($('div#resizer').width() * 0.96);
                currentChart.redraw({
                    resetAxes : true
                });
                $('#querychart').show();
            }
        } else {
            PMA_ajaxRemoveMessage($msgbox);
            PMA_ajaxShowMessage(data.error, false);
        }
        PMA_ajaxRemoveMessage($msgbox);
    }, "json"); // end $.post()

    return false;
}); // end
