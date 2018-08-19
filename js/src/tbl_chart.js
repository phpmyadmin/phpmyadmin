/* vim: set expandtab sw=4 ts=4 sts=4: */
import { TableChartEnum,
    drawChart,
    onDataSeriesChange,
    onXAxisChange,
    getSelectedSeries
} from './functions/Table/TableChart';
import { PMA_ajaxRemoveMessage, PMA_ajaxShowMessage } from './utils/show_ajax_messages';
import { checkSqlQuery } from './functions/Sql/SqlQuery';
import { PMA_prepareForAjaxRequest } from './functions/AjaxRequest';
import { sqlQueryOptions } from './utils/sql';

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardown1 () {
    $('input[name="chartType"]').off('click');
    $('input[name="barStacked"]').off('click');
    $('input[name="chkAlternative"]').off('click');
    $('input[name="chartTitle"]').off('focus').off('keyup').off('blur');
    $('select[name="chartXAxis"]').off('change');
    $('select[name="chartSeries"]').off('change');
    $('select[name="chartSeriesColumn"]').off('change');
    $('select[name="chartValueColumn"]').off('change');
    $('input[name="xaxis_label"]').off('keyup');
    $('input[name="yaxis_label"]').off('keyup');
    $('#resizer').off('resizestop');
    $('#tblchartform').off('submit');
}

export function onload1 () {
    // handle manual resize
    $('#resizer').on('resizestop', function (event, ui) {
        // make room so that the handle will still appear
        $('#querychart').height($('#resizer').height() * 0.96);
        $('#querychart').width($('#resizer').width() * 0.96);
        if (TableChartEnum.currentChart !== null) {
            TableChartEnum.currentChart.redraw({
                resetAxes : true
            });
        }
    });

    // handle chart type changes
    $('input[name="chartType"]').on('click', function () {
        var type = TableChartEnum.currentSettings.type = $(this).val();
        if (type === 'bar' || type === 'column' || type === 'area') {
            $('span.barStacked').show();
        } else {
            $('input[name="barStacked"]').prop('checked', false);
            $.extend(true, TableChartEnum.currentSettings, { stackSeries : false });
            $('span.barStacked').hide();
        }
        drawChart();
    });

    // handle chosing alternative data format
    $('input[name="chkAlternative"]').on('click', function () {
        var $seriesColumn = $('select[name="chartSeriesColumn"]');
        var $valueColumn  = $('select[name="chartValueColumn"]');
        var $chartSeries  = $('select[name="chartSeries"]');
        if ($(this).is(':checked')) {
            $seriesColumn.prop('disabled', false);
            $valueColumn.prop('disabled', false);
            $chartSeries.prop('disabled', true);
            TableChartEnum.currentSettings.seriesColumn = parseInt($seriesColumn.val(), 10);
            TableChartEnum.currentSettings.valueColumn = parseInt($valueColumn.val(), 10);
        } else {
            $seriesColumn.prop('disabled', true);
            $valueColumn.prop('disabled', true);
            $chartSeries.prop('disabled', false);
            TableChartEnum.currentSettings.seriesColumn = null;
            TableChartEnum.currentSettings.valueColumn = null;
        }
        drawChart();
    });

    // handle stacking for bar, column and area charts
    $('input[name="barStacked"]').on('click', function () {
        if ($(this).is(':checked')) {
            $.extend(true, TableChartEnum.currentSettings, { stackSeries : true });
        } else {
            $.extend(true, TableChartEnum.currentSettings, { stackSeries : false });
        }
        drawChart();
    });

    // handle changes in chart title
    $('input[name="chartTitle"]')
        .focus(function () {
            TableChartEnum.temp_chart_title = $(this).val();
        })
        .on('keyup', function () {
            TableChartEnum.currentSettings.title = $('input[name="chartTitle"]').val();
            drawChart();
        })
        .blur(function () {
            if ($(this).val() !== TableChartEnum.temp_chart_title) {
                drawChart();
            }
        });

    // handle changing the x-axis
    $('select[name="chartXAxis"]').on('change', function () {
        onXAxisChange();
        drawChart();
    });

    // handle changing the selected data series
    $('select[name="chartSeries"]').on('change', function () {
        onDataSeriesChange();
        drawChart();
    });

    // handle changing the series column
    $('select[name="chartSeriesColumn"]').on('change', function () {
        TableChartEnum.currentSettings.seriesColumn = parseInt($(this).val(), 10);
        drawChart();
    });

    // handle changing the value column
    $('select[name="chartValueColumn"]').on('change', function () {
        TableChartEnum.currentSettings.valueColumn = parseInt($(this).val(), 10);
        drawChart();
    });

    // handle manual changes to the chart x-axis labels
    $('input[name="xaxis_label"]').on('keyup', function () {
        TableChartEnum.currentSettings.xaxisLabel = $(this).val();
        drawChart();
    });

    // handle manual changes to the chart y-axis labels
    $('input[name="yaxis_label"]').on('keyup', function () {
        TableChartEnum.currentSettings.yaxisLabel = $(this).val();
        drawChart();
    });

    // handler for ajax form submission
    $('#tblchartform').submit(function (event) {
        var $form = $(this);
        if (sqlQueryOptions.codemirror_editor) {
            $form[0].elements.sql_query.value = sqlQueryOptions.codemirror_editor.getValue();
        }
        if (!checkSqlQuery($form[0])) {
            return false;
        }

        var $msgbox = PMA_ajaxShowMessage();
        PMA_prepareForAjaxRequest($form);
        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' &&
                    data.success === true &&
                    typeof data.chartData !== 'undefined') {
                TableChartEnum.chart_data = JSON.parse(data.chartData);
                drawChart();
                PMA_ajaxRemoveMessage($msgbox);
            } else {
                PMA_ajaxShowMessage(data.error, false);
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

    TableChartEnum.currentSettings = {
        type : 'line',
        width : $('#resizer').width() - 20,
        height : $('#resizer').height() - 20,
        xaxisLabel : $('input[name="xaxis_label"]').val(),
        yaxisLabel : $('input[name="yaxis_label"]').val(),
        title : $('input[name="chartTitle"]').val(),
        stackSeries : false,
        mainAxis : parseInt($('select[name="chartXAxis"]').val(), 10),
        selectedSeries : getSelectedSeries(),
        seriesColumn : null
    };

    var vals = $('input[name="dateTimeCols"]').val().split(' ');
    $.each(vals, function (i, v) {
        TableChartEnum.dateTimeCols.push(parseInt(v, 10));
    });

    vals = $('input[name="numericCols"]').val().split(' ');
    $.each(vals, function (i, v) {
        TableChartEnum.numericCols.push(parseInt(v, 10));
    });

    onXAxisChange();
    onDataSeriesChange();

    $('#tblchartform').submit();
}
