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
    $('#resizer').resizable({
        minHeight:240,
        minWidth:300,
        // On resize, set the chart size to that of the
        // resizer minus padding. If your chart has a lot of data or other
        // content, the redrawing might be slow. In that case, we recommend
        // that you use the 'stop' event instead of 'resize'.
        resize: function() {
            //currentChart.setSize(
            //    this.offsetWidth - 20,
            //    this.offsetHeight - 20,
            //    false
            //);
        }
    });

    var nonJqplotSettings = {
        chart: {
            type: 'line',
            width: $('#resizer').width() - 20,
            height: $('#resizer').height() - 20
        }
    }

    var currentSettings = {
        axes: {
            xaxis: {
                label: $('input[name="xaxis_label"]').val(),
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer
            },
            yaxis: {
                label: $('input[name="yaxis_label"]').val(),
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer
            },
        },
        title: {
            text: $('input[name="chartTitle"]').attr('value')
            //margin:20
        },
        //plotOptions: {
        //    series: {}
        //}
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
            //$.extend(true,currentSettings,{ plotOptions: { series: { stacking:'normal' } } });
        } else {
            //$.extend(true,currentSettings,{ plotOptions: { series: { stacking:null } } });
        }
        drawChart();
    });

    $('input[name="chartTitle"]').focus(function() {
        temp_chart_title = $(this).val();
    });
    $('input[name="chartTitle"]').keyup(function() {
        var title = $(this).attr('value');
        if (title.length == 0) title = ' ';
        //currentChart.setTitle({ text: title });
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
        nonJqplotSettings.chart.width = $('#resizer').width() - 20;
        nonJqplotSettings.chart.height = $('#resizer').height() - 20;

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
            //xaxis.categories = new Array();

            if (chart_series == 'columns') {
                var j = 0;
                for (var i=0; i < columnNames.length; i++)
                    if (i != chart_xaxis_idx) {
                        series[j] = new Array();
                        //series[j].data = new Array();
                        //series[j].name = columnNames[i];

                        $.each(data,function(key,value) {
                            //series[j].data.push(parseFloat(value[columnNames[i]]));
                            //series[j].data[0].push(parseFloat(value[columnNames[i]]));
                            series[j].push(
                                [
                                 value[columnNames[chart_xaxis_idx]],
                                 // todo: not always a number?
                                 parseFloat(value[columnNames[i]])
                                ]
                            );
                            //if( j== 0 && chart_xaxis_idx != -1 && ! xaxis.categories[value[columnNames[chart_xaxis_idx]]])
                                //xaxis.categories.push(value[columnNames[chart_xaxis_idx]]);
                        });
                        j++;
                    }
            } else {
                var j=0;
                var seriesIndex = new Object();
                // Get series types and build series object from the query data
                $.each(data,function(index,element) {
                    var contains = false;
                    for(var i=0; i < series.length; i++)
                        if(series[i].name == element[chart_series]) contains = true;

                    if(!contains) {
                        seriesIndex[element[chart_series]] = j;
                        series[j] = new Object();
                        series[j].data = new Array();
                        series[j].name = element[chart_series]; // columnNames[i];
                        j++;
                    }
                });

                var type;
                // Get series points from query data
                $.each(data,function(key,value) {
                    type = value[chart_series];
                    series[seriesIndex[type]].data.push(parseFloat(value[columnNames[0]]));

                    if( !in_array(value[columnNames[chart_xaxis_idx]],xaxis.categories))
                        xaxis.categories.push(value[columnNames[chart_xaxis_idx]]);
                });
            }



            if(columnNames.length == 2)
                yaxis.title = { text: columnNames[0] };
            break;

        case 'pie':
            series[0] = new Object();
            series[0].data = new Array();
            $.each(data,function(key,value) {
                    series[0].data.push({
                        name: value[columnNames[chart_xaxis_idx]],
                        y: parseFloat(value[columnNames[0]])
                    });
                });
            break;
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
                renderer: $.jqplot.CategoryAxisRenderer
            },
            yaxis: {
                renderer: $.jqplot.CategoryAxisRenderer
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
                renderer: $.jqplot.CategoryAxisRenderer
            }
        };
    }
    if (passedNonJqplotSettings.chart.type == 'pie') {
        //settings.tooltip.formatter = function() { return '<b>'+columnNames[0]+'</b><br/>'+this.y; }
    }
    // Overwrite/Merge default settings with passedsettings
    $.extend(true, settings, passedSettings);

    return $.jqplot('querychart', series, settings);
}
