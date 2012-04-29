/* vim: set expandtab sw=4 ts=4 sts=4: */
var chart_xaxis_idx = -1;
var chart_series;
var chart_data;
var temp_chart_title;
var y_values_text;

$(function() {
    var currentChart = null;
    chart_series = $('select[name="chartSeries"]').val();
    // If no series is selected null is returned. 
    // In such case nitialize chart_series to empty array.
    if (chart_series == null) {
        chart_series = new Array();
    }
    chart_xaxis_idx = $('select[name="chartXAxis"]').val();
    y_values_text = $('input[name="yaxis_label"]').val();

    $('#resizer').resizable({
        minHeight:240,
        minWidth:300,
        // On resize, set the chart size to that of the
        // resizer minus padding. If your chart has a lot of data or other
        // content, the redrawing might be slow. In that case, we recommend
        // that you use the 'stop' event instead of 'resize'.
        resize: function() {
            currentChart.setSize(
                this.offsetWidth - 20,
                this.offsetHeight - 20,
                false
            );
        }
    });

    var currentSettings = {
        chart: {
            type: 'line',
            width: $('#resizer').width() - 20,
            height: $('#resizer').height() - 20
        },
        xAxis: {
            title: { text: $('input[name="xaxis_label"]').val() }
        },
        yAxis: {
            title: { text: $('input[name="yaxis_label"]').val() }
        },
        title: {
            text: $('input[name="chartTitle"]').val(),
            margin:20
        },
        plotOptions: {
            series: {}
        }
    };


    $('input[name="chartType"]').click(function() {
        currentSettings.chart.type = $(this).val();

        drawChart();

        if ($(this).val() == 'bar' || $(this).val() == 'column') {
            $('span.barStacked').show();
        } else {
            $('span.barStacked').hide();
        }
    });

    $('input[name="barStacked"]').click(function() {
        if (this.checked) {
            $.extend(true, currentSettings, { plotOptions: { series: { stacking:'normal' } } });
        } else {
            $.extend(true, currentSettings, { plotOptions: { series: { stacking:null } } });
        }
        drawChart();
    });

    $('input[name="chartTitle"]').focus(function() {
        temp_chart_title = $(this).val();
    });
    $('input[name="chartTitle"]').keyup(function() {
        var title = $(this).val();
        if (title.length == 0) {
            title = ' ';
        }
        currentSettings.title.text = title;
        currentChart.setTitle({ text: title });
    });
    $('input[name="chartTitle"]').blur(function() {
        if ($(this).val() != temp_chart_title) {
            drawChart(true);
        }
    });

    $('select[name="chartXAxis"]').change(function() {
        chart_xaxis_idx = $(this).val();
        var xaxis_title = $(this).children('option:selected').text();
        $('input[name="xaxis_label"]').val(xaxis_title);
        currentSettings.xAxis.title.text = xaxis_title;
        drawChart();
    });
    $('select[name="chartSeries"]').change(function() {
        chart_series = $(this).val();

        if (chart_series.length == 1) {
            $('span.span_pie').show();
            var yaxis_title = $(this).children('option:selected').text();            
        } else {
            $('span.span_pie').hide();
            if (currentSettings.chart.type == 'pie') {
                $('input#radio_line').prop('checked', true);
                currentSettings.chart.type = 'line';
            }
            var yaxis_title = y_values_text;        
        }
        $('input[name="yaxis_label"]').val(yaxis_title);
        currentSettings.yAxis.title.text = yaxis_title;

        drawChart();
    });

    /* Sucks, we cannot just set axis labels, we have to redraw the chart completely */
    $('input[name="xaxis_label"]').keyup(function() {
        currentSettings.xAxis.title.text = $(this).val();
        drawChart(true);
    });
    $('input[name="yaxis_label"]').keyup(function() {
        currentSettings.yAxis.title.text = $(this).val();
        drawChart(true);
    });

    function drawChart(noAnimation) {
        currentSettings.chart.width = $('#resizer').width() - 20;
        currentSettings.chart.height = $('#resizer').height() - 20;

        if (currentChart != null) {
            currentChart.destroy();
        }

        if (noAnimation) {
            currentSettings.plotOptions.series.animation = false;
        }
        currentChart = PMA_queryChart(chart_data, currentSettings);
        if (noAnimation) {
            currentSettings.plotOptions.series.animation = true;
        }
    }

    drawChart();
    $('#querychart').show();
});

function in_array(element, array)
{
    for (var i = 0, l = array.length; i < l; i++) {
        if (array[i] == element) {
            return true;
        }
    }
    return false;
}

function isColumnNumeric(columnName)
{
    var first = true; 
    var isNumeric = false;
    $('select[name="chartSeries"] option').each(function() {
        if ($(this).val() == columnName) {
            isNumeric = true;
            return false;
        }
    });
    return isNumeric;
}

function PMA_queryChart(data, passedSettings)
{
    if ($('#querychart').length == 0) {
        return;
    }

    var columnNames = [];
    var series = new Array();
    var xaxis = { 
        type: 'linear', 
        categories: new Array() 
    };
    var yaxis = new Object();

    $.each(data[0], function(index, element) {
        columnNames.push(index);
    });

    var j = 0;
    for (var i = 0; i < columnNames.length; i++) {
        if (! isColumnNumeric(columnNames[i]) || $.inArray(columnNames[i], chart_series) == -1) {
            continue;
        }
        series[j] = new Object();
        series[j].data = new Array();
        series[j].name = columnNames[i];

        $.each(data, function(key, value) {
            var floatVal;
            if (value[columnNames[i]] != null) {
                floatVal = parseFloat(value[columnNames[i]]);
            } else {
                floatVal = null;
            }
            series[j].data.push({
                name: value[columnNames[chart_xaxis_idx]],
                y: floatVal
            });
            if (j == 0 && ! xaxis.categories[value[columnNames[chart_xaxis_idx]]]) {
                xaxis.categories.push(value[columnNames[chart_xaxis_idx]]);
            }
        });
        j++;
    }

    // Prevent the user from seeing the JSON code
    $('div#profilingchart').html('').show();

    var settings = {
        chart: {
            renderTo: 'querychart'
        },
        title: {
            text: '',
            margin: 0
        },
        series: series,
        xAxis: xaxis,
        yAxis: yaxis,
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    distance: 35,
                    formatter: function() {
                        return '<b>'+ this.point.name +'</b><br/>'+ Highcharts.numberFormat(this.percentage, 2) + ' %';
                    }
                }
            }
        },
        tooltip: {
            formatter: function() {
                if (this.point.name) {
                    return '<b>' + this.series.name + '</b><br/>' + this.point.name+'<br/>' + this.y;
                }
                return '<b>' + this.series.name+'</b><br/>'+this.y;
            }
        }
    };

    // Overwrite/Merge default settings with passedsettings
    $.extend(true, settings, passedSettings);

    return PMA_createChart(settings);
}
