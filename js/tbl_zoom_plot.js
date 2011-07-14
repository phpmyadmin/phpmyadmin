/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** @fileoverview JavaScript functions used on tbl_select.php
 **
 ** @requires    jQuery
 ** @requires    js/functions.js
 **/


/**
 **  Display Help/Info
 **/
function displayHelp() {
        var msgbox = PMA_ajaxShowMessage(PMA_messages['strDisplayHelp'],10000);
	msgbox.click(function() {
               $(this).remove();
	});
}

/**
 ** Extend the array object for max function
 ** @param array
 **/
Array.max = function (array) {
	return Math.max.apply( Math, array );
}

/**
 ** Extend the array object for min function
 ** @param array
 **/
Array.min = function (array) {
	return Math.min.apply( Math, array );
}

/**
 ** Checks if a string contains only numeric value
 ** @param n: String (to be checked) 
 **/
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

/**
 ** Checks if an object is empty
 ** @param n: Object (to be checked) 
 **/
function isEmpty(obj) {
    var name;
    for (name in obj) {
        return false;
    }
    return true;
}

/**
 ** Scrolls the view to the display section
 **/
function scrollToChart() {
   var x = $('#dataDisplay').offset().top - 100; // 100 provides buffer in viewport
   $('html,body').animate({scrollTop: x}, 500);
}

/**
 ** Displays the query result display section
 ** @param modal: type of dialog
 **/
function ShowDialog(modal) {
      $("#overlay").show();
      $("#dialog").fadeIn(300);
      $("#overlay").click(function (e)
      {
          HideDialog();
      });
}

/**
 ** Hides the query result display section
 ** @param modal : type of dialog
 **/
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
    var currentChart = null;
    var currentData = null;
    var xLabel = $('#tableid_0').val();
    var yLabel = $('#tableid_1').val();
    var dataLabel = $('#dataLabel').val();

    // Get query result 
    var data = jQuery.parseJSON($('#querydata').html());

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

    /**
      ** Prepare a div containing a link, otherwise it's incorrectly displayed 
      ** after a couple of clicks
      **/
    $('<div id="togglesearchformdiv"><a id="togglesearchformlink"></a></div>')
    .insertAfter('#zoom_search_form')
    // don't show it until we have results on-screen
    .hide();

    $('#togglesearchformlink')
        .html(PMA_messages['strShowSearchCriteria'])
        .bind('click', function() {
            var $link = $(this);
            $('#zoom_search_form').slideToggle();
            if ($link.text() == PMA_messages['strHideSearchCriteria']) {
                $link.text(PMA_messages['strShowSearchCriteria']);
            } else {
                $link.text(PMA_messages['strHideSearchCriteria']);
            }
	     // avoid default click action
	    return false;
	 });
   
    /*
     * Handle submit of zoom_display_form 
     */
     
    $("#zoom_display_form.ajax").live('submit', function(event) {
	
        //Prevent default submission of form
        event.preventDefault();
	
	//Update the data, find changed values
	var newValues = new Array();//Stores the values changed from original
        var it = 4;
	for (key in data[currentData]) {
	    if (key != 'where_clause'){
		var oldVal = data[currentData][key];
		var newVal = ($('#fields_null_id_' + it).attr('checked')) ? null : $('#fieldID_' + it).val();
		if (oldVal != newVal){
		    data[currentData][key] = newVal;
		    newValues[key] = newVal;
		}
	    }
	    it++    
	}//End data update

        //Update the chart series and replot
        series[currentData].data = {
	    name : data[currentData][dataLabel],
            x : data[currentData][xLabel],
            y : data[currentData][yLabel],
	    color : colorCodes[currentData % 8],
	    id : currentData,
	};

	xCord[currentData] = data[currentData][xLabel]
	yCord[currentData] = data[currentData][yLabel]

	currentSettings.series = series;
	currentSettings.xAxis.max = Array.max(xCord) + 2;
	currentSettings.xAxis.min = Array.min(xCord) - 2;
	currentSettings.yAxis.max = Array.max(yCord) + 2;
	currentSettings.yAxis.min = Array.min(yCord) - 2;
	
        currentChart = PMA_createChart(currentSettings); 
	currentChart.series[currentData].data[0].select();
	//End plot update	

	//Generate SQL query for update
	if (!isEmpty(newValues)) {
            var sql_query = 'UPDATE `' + window.parent.table + '` SET ';
	    for (key in newValues) {
	        if(key != 'where_clause') {
	            sql_query += '`' + key + '`=' ;
		    var value = newValues[key];
		    if(!isNumeric(value) && value != null) 
		        sql_query += '\'' + value + '\' ,';
		    else
		        sql_query += value + ' ,';
	        }
	    }
	    sql_query = sql_query.substring(0, sql_query.length - 1);
	    sql_query += ' WHERE ' + PMA_urldecode(data[currentData]['where_clause']);
	    
	    //Post SQL query to sql.php	
	    $.post('sql.php', {
                'token' : window.parent.token,
                'db' : window.parent.db,
                'ajax_request' : true,
                'sql_query' : sql_query,
	        'inline_edit' : false
	        }, function(data) {
	            if(data.success == true) {
	                $('#sqlqueryresults').html(data.sql_query);
		        $("#sqlqueryresults").trigger('appendAnchor');
	            }
	            else 
	                PMA_ajaxShowMessage(data.error);
	    })//End $.post
	}//End database update
    });//End submit handler 

    /*
     * Generate plot using Highcharts
     */ 

    if (data != null) {
        $('#zoom_search_form')
         .slideToggle()
         .hide();
        $('#togglesearchformlink')
         .text(PMA_messages['strShowSearchCriteria'])
	$('#togglesearchformdiv').show();
        
        ShowDialog(false);
	//$('#resizer').height($('#dataDisplay').height() + 50); 

    	var columnNames = new Array();
    	var colorCodes = ['#FF0000','#00FFFF','#0000FF','#0000A0','#FF0080','#800080','#FFFF00','#00FF00','#FF00FF'];
    	var series = new Array();
    	var xCord = new Array();
    	var yCord = new Array();
	var temp;
    	var it = 0;

    	// Get column names
    	for (key in data[0]) columnNames.push(key);

        // Form series 
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

        // Set the plot settings
        var currentSettings = {
            chart: {
            	renderTo: 'querychart',
            	defaultSeriesType: 'scatter',
	    	zoomType: 'xy',
	    	width:$('#resizer').width() -3,
            	height:$('#resizer').height()-20 
	    },
	    credits: {
                enabled: false 
            },
	    exporting: { enabled: false },
            label: { text: $('#dataLabel').val() },
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
			        var id = this.id;
				var j = 4;
                                for( key in data[id]) {
					if (data[id][key] == null)
					    $('#fields_null_id_' + j).attr('checked', true);
					else
					    $('#fieldID_' + j).val(data[id][key]);
					j++;
				} 
				currentData = id;
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
		type: 'linear',
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
                    this.offsetHeight -20,
                    false
                );
            }
        });
        
        currentChart = PMA_createChart(currentSettings);
	scrollToChart();
    }
});
