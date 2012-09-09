/* vim: set expandtab sw=4 ts=4 sts=4: */

var chart_xaxis_idx = -1;
var chart_series;
var chart_series_index = -1;
var temp_chart_title;

$(document).ready(function() {
    var currentChart = null;
    var chart_data = jQuery.parseJSON($('#querychart').html());
    chart_series = 'columns';
    chart_xaxis_idx = $('select[name="chartXAxis"]').attr('value');

    // from jQuery UI
    $('div#resizer').resizable({
        minHeight:240,
        minWidth:300
    });

    $('div#resizer').bind('resizestop', function(event,ui) {
        // make room so that the handle will still appear
        $('div#querychart').height($('div#resizer').height() * 0.96);
        $('div#querychart').width($('div#resizer').width() * 0.96);
        currentChart.replot( {resetAxes: true})
    });

    var nonJqplotSettings = {
        chart: {
            type: 'line',
            width: $('div#resizer').width() - 20,
            height: $('div#resizer').height() - 20
        }
    }

    var currentSettings = {
        grid: {
            drawBorder: false,
            shadow: false,
            background: 'rgba(0,0,0,0)'
        },
        axes: {
            xaxis: {
                label: $('input[name="xaxis_label"]').val(),
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer
            },
            yaxis: {
                label: $('input[name="yaxis_label"]').val(),
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer
            }
        },
        title: {
            text: $('input[name="chartTitle"]').attr('value')
            //margin:20
        },
        legend: {
            show: true,
            placement: 'outsideGrid',
            location: 'se'
        }
    }

    $('#querychart').html('');

    $('input[name="chartType"]').click(function() {
        nonJqplotSettings.chart.type = $(this).attr('value');

        drawChart();

        if ($(this).attr('value') == 'bar' || $(this).attr('value') == 'column')
            $('span.barStacked').show();
        else
            $('span.barStacked').hide();
    });

    $('input[name="barStacked"]').click(function() {
        if(this.checked) {
            $.extend(true,currentSettings,{ stackSeries: true });
        } else {
            $.extend(true,currentSettings,{ stackSeries: false });
        }
        drawChart();
    });

    $('input[name="chartTitle"]').focus(function() {
        temp_chart_title = $(this).val();
    });
    $('input[name="chartTitle"]').keyup(function() {
        var title = $(this).attr('value');
        if (title.length == 0) {
            title = ' ';
        }
        currentSettings.title = $('input[name="chartTitle"]').val();
        drawChart();
    });
    $('input[name="chartTitle"]').blur(function() {
        if ($(this).val() != temp_chart_title) {
            drawChart();
        }
    });

    $('select[name="chartXAxis"]').change(function() {
        chart_xaxis_idx = this.value;
        drawChart();
    });
    $('select[name="chartSeries"]').change(function() {
        chart_series = this.value;
        chart_series_index = this.selectedIndex;
        drawChart();
    });

    /* Sucks, we cannot just set axis labels, we have to redraw the chart completely */
    $('input[name="xaxis_label"]').keyup(function() {
        currentSettings.axes.xaxis.label = $(this).attr('value');
        drawChart();
    });
    $('input[name="yaxis_label"]').keyup(function() {
        currentSettings.axes.yaxis.label = $(this).attr('value');
        drawChart();
    });

    function drawChart() {
        nonJqplotSettings.chart.width = $('div#resizer').width() - 20;
        nonJqplotSettings.chart.height = $('div#resizer').height() - 20;

        // todo: a better way using .replot() ?
        if (currentChart != null) {
            currentChart.destroy();
        }
        currentChart = PMA_queryChart(chart_data, currentSettings, nonJqplotSettings);
    }

    drawChart();
    $('#querychart').show();
});

function in_array(element,array)
{
    for(var i=0; i < array.length; i++)
        if(array[i] == element) return true;
    return false;
}

function PMA_queryChart(data, passedSettings, passedNonJqplotSettings)
{
    if ($('#querychart').length == 0) return;

    var columnNames = Array();

    var series = new Array();
    var xaxis = { type: 'linear' };
    var yaxis = new Object();

    $.each(data[0],function(index,element) {
        columnNames.push(index);
    });

    switch(passedNonJqplotSettings.chart.type) {
        case 'column':
        case 'spline':
        case 'line':
        case 'bar':
            var j = 0;
            for (var i = 0; i < columnNames.length; i++) {
                if (i != chart_xaxis_idx) {
                    series[j] = new Array();
                    if (chart_series == 'columns' || chart_series == columnNames[i]) {
                        $.each(data,function(key,value) {
                            series[j].push(
                                [
                                value[columnNames[chart_xaxis_idx]],
                                // todo: not always a number?
                                parseFloat(value[columnNames[i]])
                                ]
                            );
                        });
                        j++;
                    }
                }
            }
            if(columnNames.length == 2)
                yaxis.title = { text: columnNames[0] };
            break;

        case 'pie':
            // only available for a specific column
            // todo: warn the user about this
            if (chart_series != 'columns') {
                series[0] = new Array();
                $.each(data,function(key,value) {
                    series[0].push(
                        [
                        value[columnNames[chart_xaxis_idx]],
                        parseFloat(value[chart_series])
                        ]
                     );
                });
                break;
            }
    }

    var settings = {
        title: {
            text: '' 
            //margin:20
        }
    };

    if (passedNonJqplotSettings.chart.type == 'line') {
        settings.axes = {
            xaxis: {
            },
            yaxis: {
            }
        }
    }

    if (passedNonJqplotSettings.chart.type == 'bar') {
        settings.seriesDefaults = {
            renderer: $.jqplot.BarRenderer,
            rendererOptions: {
                barDirection: 'vertical',
                highlightMouseOver: true
            }
        };
        settings.axes = {
            xaxis: {
                renderer: $.jqplot.CategoryAxisRenderer
            },
            yaxis: {
            }
        };
    }

    if (passedNonJqplotSettings.chart.type == 'spline') {
        settings.seriesDefaults = {
            rendererOptions: {
                smooth: true 
            }
        };
    }

    if (passedNonJqplotSettings.chart.type == 'pie') {
        settings.seriesDefaults = {
            renderer: $.jqplot.PieRenderer,
            rendererOptions: {
                showDataLabels: true,
                highlightMouseOver: true,
                showDataLabels: true,
                dataLabels: 'value'
            }
        };
    }
    // Overwrite/Merge default settings with passedsettings
    $.extend(true, settings, passedSettings);

    settings.series = new Array();
    for (var i = 0; i < columnNames.length; i++) {
        if (parseInt(chart_xaxis_idx) != i) {
            if (chart_series == 'columns' || chart_series == columnNames[i]) {
                settings.series.push({ label: columnNames[i] });
            }
        }
    }

    return $.jqplot('querychart', series, settings);
}
