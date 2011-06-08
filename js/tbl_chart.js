var chart_xaxis_idx = 0;


$(document).ready(function() {
    var currentChart=null;
    var chart_data = jQuery.parseJSON($('#querychart').html());
    
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
            type:'line',
            width:$('#resizer').width()-20,
            height:$('#resizer').height()-20
        },
        xAxis: {
            title: { text: $('input[name="xaxis_label"]').attr('value') }
        },
        yAxis: {
            title: { text: $('input[name="yaxis_label"]').attr('value') }
        },
        title: { text: $('input[name="chartTitle"]').attr('value'), margin:20 }
    }
    
    $('#querychart').html('');
    
    $('input[name="chartType"]').click(function() {
        currentSettings.chart.type=$(this).attr('value');
        
        drawChart();
        
        if($(this).attr('value')=='bar' || $(this).attr('value')=='column')
            $('span.barStacked').show();
        else
            $('span.barStacked').hide();
    });
    
    $('input[name="barStacked"]').click(function() {
        if(this.checked)
            $.extend(true,currentSettings,{ plotOptions: { series: { stacking:'normal' } } });
        else
            $.extend(true,currentSettings,{ plotOptions: { series: { stacking:null } } });
        drawChart();
    });
    
    $('input[name="chartTitle"]').keyup(function() {
        var title=$(this).attr('value');
        if(title.length==0) title=' ';
        currentChart.setTitle({text: title});
    });
    
    $('select[name="chartXAxis"]').change(function() {
        chart_xaxis_idx = $('select[name="chartXAxis"]').attr('value');
        drawChart();
    });
    
    /* Sucks, we cannot just set axis labels, we have to redraw the chart completely */
    $('input[name="xaxis_label"]').keyup(function() {
        currentSettings.xAxis.title.text = $(this).attr('value');
        drawChart();
    });
    $('input[name="yaxis_label"]').keyup(function() {
        currentSettings.yAxis.title.text = $(this).attr('value');
        drawChart();
    });
    
    function drawChart() {
        currentSettings.chart.width=$('#resizer').width()-20;
        currentSettings.chart.height=$('#resizer').height()-20;
        
        if(currentChart!=null) currentChart.destroy();
        currentChart = PMA_queryChart(chart_data,currentSettings);
    }
    
    drawChart();
});

function PMA_queryChart(data,passedSettings) {
    if($('#querychart').length==0) return;
    
    var columnNames = Array();
    
    var series = new Array();
    var xaxis = new Object();
    var yaxis = new Object();
    
    $.each(data[0],function(index,element) {
        columnNames.push(index);
    });
    
    switch(passedSettings.chart.type) {
        case 'column':
        case 'spline':
        case 'line':
        case 'bar':
            xaxis.categories = new Array();
            //xaxis.title = { text: columnNames[chart_xaxis_idx] };
            var j=0;
            for(var i=0; i<columnNames.length; i++) 
                if(i!=chart_xaxis_idx) {
                    series[j] = new Object();
                    series[j].data = new Array();
                    series[j].name = columnNames[i];
                    $.each(data,function(key,value) {
                        series[j].data.push(parseFloat(value[columnNames[i]]));
                        if(j==0) 
                            xaxis.categories.push(value[columnNames[chart_xaxis_idx]]);
                    });
                    j++;
                }
            if(columnNames.length==2)
                yaxis.title = { text: columnNames[0] };
            break;
            
        case 'pie':
            series[0] = new Object();
            series[0].data = new Array();
            $.each(data,function(key,value) {
                    series[0].data.push({name:value[columnNames[chart_xaxis_idx]],y:parseFloat(value[columnNames[0]])});
                });
            break;
    }
        
    // Prevent the user from seeing the JSON code
    $('div#profilingchart').html('').show();

    var settings = {
        chart: { 
            renderTo: 'querychart',
            backgroundColor: $('fieldset').css('background-color')
        },
        title: { text:'', margin:0 },
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
                        return '<b>'+ this.point.name +'</b><br/>'+ Highcharts.numberFormat(this.percentage, 2) +' %';
                   }
                }
            }
        },
        credits: {
            enabled:false
        },		
        exporting: {
            enabled: true
        },		
        tooltip: {
            formatter: function() { return '<b>'+this.series.name+'</b><br/>'+this.y; }
        }
    };
    
    

    if(passedSettings.chart.type=='pie')
        settings.tooltip.formatter = function() { return '<b>'+columnNames[0]+'</b><br/>'+this.y; }
        
    // Overwrite/Merge default settings with passedsettings
    $.extend(true,settings,passedSettings);

    return new Highcharts.Chart(settings);
}
