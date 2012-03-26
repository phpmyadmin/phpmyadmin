var chart_xaxis_idx = -1;
var chart_series;
var chart_series_index = -1;

$(document).ready(function() {
    var currentChart = null;
    var chart_data = jQuery.parseJSON($('#querychart').html());
    chart_series = 'columns';
    chart_xaxis_idx = $('select[name="chartXAxis"]').attr('value');

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
            title: { text: $('input[name="xaxis_label"]').attr('value') }
        },
        yAxis: {
            title: { text: $('input[name="yaxis_label"]').attr('value') }
        },
        title: {
            text: $('input[name="chartTitle"]').attr('value'),
            margin:20
        },
        plotOptions: {
            series: {}
        }
    };

    $('#querychart').html('');

    $('input[name="chartType"]').click(function() {
        currentSettings.chart.type = $(this).attr('value');

        drawChart();

        if ($(this).attr('value') == 'bar' || $(this).attr('value') == 'column') {
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

    $('input[name="chartTitle"]').keyup(function() {
        var title = $(this).attr('value');
        if (title.length == 0) {
            title = ' ';
        }
        currentChart.setTitle({ text: title });
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
        currentSettings.xAxis.title.text = $(this).attr('value');
        drawChart(true);
    });
    $('input[name="yaxis_label"]').keyup(function() {
        currentSettings.yAxis.title.text = $(this).attr('value');
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
    for (var i = 0; i < array.length; i++) {
        if (array[i] == element) {
            return true;
        }
    }
    return false;
}

function PMA_queryChart(data, passedSettings)
{
    if ($('#querychart').length == 0) {
        return;
    }

    var columnNames = Array();

    var series = new Array();
    var xaxis = { type: 'linear' };
    var yaxis = new Object();

    $.each(data[0], function(index, element) {
        columnNames.push(index);
    });

    switch(passedSettings.chart.type) {
        case 'column':
        case 'spline':
        case 'line':
        case 'bar':
            xaxis.categories = new Array();

            if (chart_series == 'columns') {
                var j = 0;
                for (var i = 0; i<columnNames.length; i++)
                    if (i != chart_xaxis_idx) {
                        series[j] = new Object();
                        series[j].data = new Array();
                        series[j].name = columnNames[i];

                        $.each(data, function(key, value) {
                            series[j].data.push(parseFloat(value[columnNames[i]]));
                            if ( j == 0 && chart_xaxis_idx != -1 && ! xaxis.categories[value[columnNames[chart_xaxis_idx]]])
                                xaxis.categories.push(value[columnNames[chart_xaxis_idx]]);
                        });
                        j++;
                    }
            } else {
                var j = 0;
                var seriesIndex = new Object();
                // Get series types and build series object from the query data
                $.each(data, function(index, element) {
                    var contains = false;
                    for (var i = 0; i < series.length; i++) {
                        if (series[i].name == element[chart_series]) {
                            contains = true;
                        }
                    }

                    if (! contains) {
                        seriesIndex[element[chart_series]] = j;
                        series[j] = new Object();
                        series[j].data = new Array();
                        series[j].name = element[chart_series]; // columnNames[i];
                        j++;
                    }
                });

                var type;
                // Get series points from query data
                $.each(data, function(key, value) {
                    type = value[chart_series];
                    series[seriesIndex[type]].data.push(parseFloat(value[columnNames[0]]));

                    if ( ! in_array(value[columnNames[chart_xaxis_idx]], xaxis.categories)) {
                        xaxis.categories.push(value[columnNames[chart_xaxis_idx]]);
                    }
                });
            }



            if (columnNames.length == 2) {
                yaxis.title = { text: columnNames[0] };
            }
            break;

        case 'pie':
            series[0] = new Object();
            series[0].data = new Array();
            $.each(data, function(key, value) {
                    series[0].data.push({
                        name: value[columnNames[chart_xaxis_idx]],
                        y: parseFloat(value[columnNames[0]])
                    });
                });
            break;
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

    if (passedSettings.chart.type == 'pie') {
        settings.tooltip.formatter = function() { 
            return '<b>' + columnNames[0] + '</b><br/>' + this.y;
        };
    }

    // Overwrite/Merge default settings with passedsettings
    $.extend(true, settings, passedSettings);

    return PMA_createChart(settings);
}
