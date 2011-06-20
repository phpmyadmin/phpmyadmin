var chart_xaxis_idx = -1;
var chart_series;
var chart_series_index = -1;

$(document).ready(function() {




    var currentChart=null;

    // Get query result 
    var chart_data = jQuery.parseJSON($('#querydata').html());
    var xLabel = $('#tableid_0').val();
    var yLabel = $('#tableid_1').val();

    var currentSettings = {
        chart: {
            renderTo: 'querychart',
            defaultSeriesType: 'scatter',
	    zoomType: 'xy',
	    width:$('#resizer').width(),
            height:$('#resizer').height()
	},
	xAxis: {
	    title: { text: $('#tableid_0').val() },
        },
        yAxis: {
	    title: { text: $('#tableid_1').val() },
	},
        title: { text: 'Query Results' },
	exporting: { enabled: false },
	credits: {
            enabled: false 
        },
        label: { text: $('#dataLabel').val() },
    }

    function drawChart(noAnimation) {

        currentSettings.chart.width=$('#resizer').width()-3;
        currentSettings.chart.height=$('#resizer').height()-20;

        if(currentChart!=null) currentChart.destroy();
        
        if(noAnimation) currentSettings.plotOptions.series.animation = false;
        currentChart = PMA_queryChart(chart_data,currentSettings);
        if(noAnimation) currentSettings.plotOptions.series.animation = true;
    }

    $('#resizer').resizable({
        resize: function() {
            currentChart.setSize(
                this.offsetWidth -3,
                this.offsetHeight - 20,
                false
            );
        }
    });

    drawChart();
});

function displayHelp() {
	alert("* Each point represents a data row.\n* Hovering over a point will show its label.\n* Drag and select an area in the plot to zoom into it.\n* Click reset zoom link to come back to original state.\n* Click data points to view the data row.\n* The plot can be resized by dragging it along the bottom right corner.");

}

function in_array(element,array) {
	for(var i=0; i<array.length; i++)
		if(array[i]==element) return true;
	return false;
}

Array.max = function (array) {
	return Math.max.apply( Math, array );
}

Array.min = function (array) {
	return Math.min.apply( Math, array );
}

function PMA_queryChart(data,passedSettings) {

    if($('#querydata').length==0) return;

    var columnNames = new Array();
    var colorCodes = ['#FF0000','#00FFFF','#0000FF','#0000A0','#FF0080','#800080','#FFFF00','#00FF00','#FF00FF'];
    var series = new Array();
    var xCord = new Array();
    var yCord = new Array();
    var it = 0;
   
    // Get column names
    for (key in data[0]) columnNames.push(key);

    $.each(data,function(key,value) {
	series[it] = new Object();
        series[it].data = new Array();
	series[it].color = colorCodes[it % 8];
	series[it].marker = {
            symbol: 'circle'
        };
        xCord.push(value[passedSettings.xAxis.title.text]);
        yCord.push(value[passedSettings.yAxis.title.text]);
        series[it].data.push({ name: value[passedSettings.label.text], x:value[passedSettings.xAxis.title.text], y:value[passedSettings.yAxis.title.text], color: colorCodes[it % 8], id: it } );
	it++;   
    });

    var settings = {
        series: series,
	plotOptions: {
	    series: {
	        allowPointSelect: true,
                cursor: 'pointer',
		showInLegend: false,
                dataLabels: {
                    enabled: false,
                },
	        point: {
                    events: {
                        click: function() {
			    var str = '';
			    var id = this.id;
			    $.each(columnNames,function(key,value) {
			        str = str + value + " : " + data[id][value] + "\n";
                            });
			    alert(str);
                        }
                    }
	        }
	    }
	},
	tooltip: {
	    formatter: function() {
	        return this.point.name;
	    }
	},
        xAxis: { 
	    max: Array.max(xCord) + 2,
	    min: Array.min(xCord) - 2
        },
        yAxis: { 
	    max: Array.max(yCord) + 3,
	    min: Array.min(yCord) - 2
        }
    };
 
    // Overwrite/Merge default settings with passedsettings
    $.extend(true,settings,passedSettings);
	
    return new Highcharts.Chart(settings);
}
