/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** @fileoverview JavaScript functions used on tbl_select.php
 **
 ** @requires    jQuery
 ** @requires    js/functions.js
 **/


function displayHelp() {
        var msgbox = PMA_ajaxShowMessage(PMA_messages['strDisplayHelp']);
}

Array.max = function (array) {
	return Math.max.apply( Math, array );
}

Array.min = function (array) {
	return Math.min.apply( Math, array );
}

function scrollToChart() {
   var x = $('#dataDisplay').offset().top - 100; // 100 provides buffer in viewport
   $('html,body').animate({scrollTop: x}, 500);
}

function ShowDialog(modal) {
      $("#overlay").show();
      $("#dialog").fadeIn(300);
      $("#overlay").click(function (e)
      {
          HideDialog();
      });
    }

function HideDialog() {
    $("#overlay").hide();
    $("#dialog").fadeOut(300);
}

        
$(document).ready(function() {

   /**
    ** Set a parameter for all Ajax queries made on this page.  Don't let the
    ** web server serve cached pages
    **/
    $.ajaxSetup({
        cache: 'false'
    });


    var cursorMode = ($("input[name='mode']:checked").val() == 'edit') ? 'crosshair' : 'pointer'; 
    var currentChart=null;
    /**
     ** Form submit on field change
     **/
    $('#tableid_0').change(function() {
          $('#zoom_search_form').submit();
    })

    $('#tableid_1').change(function() {
          $('#zoom_search_form').submit();
    })

    $('#tableid_2').change(function() {
          $('#zoom_search_form').submit();
    })

    $('#tableid_3').change(function() {
          $('#zoom_search_form').submit();
    })

    $("input[name='mode']").change(function(){
        if($("input[name='mode']:checked").val() == 'edit'){
	    currentSettings.plotOptions.series.cursor = 'crosshair';
	    cursorMode = 'crosshair';
            currentChart = PMA_createChart(currentSettings);
	}
	else{
	    currentSettings.plotOptions.series.cursor = 'pointer';
	    cursorMode = 'pointer';
            currentChart = PMA_createChart(currentSettings);
	}
     });

    /*
     * Edit point data submit
     */

    $('#buttonID').live('click',function(event){
	
        $search_form = $('#zoom_search_form');
	event.preventDefault();
	alert('Working on it!');
	//PMA_prepareForAjaxRequest($search_form);
	//var str = $search_form.serialize();  
        //$.post($search_form.attr('action'), str, function(response){
	//	$('#sqlqueryresults').html(response);
	//	$("#sqlqueryresults").trigger('appendAnchor');

	
	//});
	

    });

    /*
     * Generate plot using Highcharts
     */ 

    // Get query result 
    var data = jQuery.parseJSON($('#querydata').html());
    if (data != null) {
    ShowDialog(false);
    $('#resizer').height($('#dataDisplay').height() + 49); 

        var xLabel = $('#tableid_0').val();
    	var yLabel = $('#tableid_1').val();
    	var dataLabel = $('#dataLabel').val();
    	var columnNames = new Array();
    	var colorCodes = ['#FF0000','#00FFFF','#0000FF','#0000A0','#FF0080','#800080','#FFFF00','#00FF00','#FF00FF'];
    	var series = new Array();
    	var xCord = new Array();
    	var yCord = new Array();
	var temp;
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
            xCord.push(value[xLabel]);
            yCord.push(value[yLabel]);
            series[it].data.push({ name: value[dataLabel], x:value[xLabel], y:value[yLabel], color: colorCodes[it % 8], id: it } );
	    it++;   
        });

        var currentSettings = {
            chart: {
            	renderTo: 'querychart',
            	defaultSeriesType: 'scatter',
	    	zoomType: 'xy',
	    	width:$('#resizer').width() -3,
            	height:$('#resizer').height() 
	    },
	    credits: {
                enabled: false 
            },
	    exporting: { enabled: false },
            label: { text: $('#dataLabel').val() },
	    plotOptions: {
	        series: {
	            allowPointSelect: true,
                    cursor: cursorMode,
		    showInLegend: false,
                    dataLabels: {
                        enabled: false,
                    },
	            point: {
                        events: {
                            click: function() {
			        var id = this.id;
				var j = 4;
                                for( key in data[id]){
					$('#fieldID_' + j).val(data[id][key]);
					j++;
				} 
                            },
                        }
	            }
	        }
	    },
	    tooltip: {
	        formatter: function() {
	            return this.point.name;
	        }
	    },
            series: series,
            title: { text: 'Query Results' },
	    xAxis: {
	        title: { text: $('#tableid_0').val() },
	        max: Array.max(xCord) + 2,
	        min: Array.min(xCord) - 2
            },
            yAxis: {
	        title: { text: $('#tableid_1').val() },
	        max: Array.max(yCord) + 3,
	        min: Array.min(yCord) - 2
	    },
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
        
        currentChart = PMA_createChart(currentSettings);
	scrollToChart();
    }
});
