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
    var msgbox = PMA_ajaxShowMessage(PMA_messages['strDisplayHelp'], 10000);
    msgbox.click(function() {
        PMA_ajaxRemoveMessage(msgbox);
    });
}

/**
 ** Extend the array object for max function
 ** @param array
 **/
Array.max = function (array) {
    return Math.max.apply( Math, array );
};

/**
 ** Extend the array object for min function
 ** @param array
 **/
Array.min = function (array) {
    return Math.min.apply( Math, array );
};

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
 ** Converts a timestamp into the format of its field type
 ** @param val  Integer Timestamp
 ** @param type String  Field type(datetime/timestamp/time/date)
 **/
function getDate(val, type) {
    if (type.toString().search(/datetime/i) != -1 || type.toString().search(/timestamp/i) != -1) {
        //return Highcharts.dateFormat('%Y-%m-%e %H:%M:%S', val);
    }
    else if (type.toString().search(/time/i) != -1) {
        //return Highcharts.dateFormat('%H:%M:%S', val);
    }
    else if (type.toString().search(/date/i) != -1) {
        //return Highcharts.dateFormat('%Y-%m-%e', val);
    }
}

/**
 ** Converts a date/time into timestamp
 ** @param val  String Date
 ** @param type Sring  Field type(datetime/timestamp/time/date)
 **/
function getTimeStamp(val, type) {
    if (type.toString().search(/datetime/i) != -1 || type.toString().search(/timestamp/i) != -1) {
        return getDateFromFormat(val, 'yyyy-MM-dd HH:mm:ss', val);
    }
    else if (type.toString().search(/time/i) != -1) {
        return getDateFromFormat('1970-01-01 ' + val, 'yyyy-MM-dd HH:mm:ss');
    }
    else if (type.toString().search(/date/i) != -1) {
        return getDateFromFormat(val, 'yyyy-MM-dd');
    }
}

/**
 ** Classifies the field type into numeric,timeseries or text
 ** @param field: field type (as in database structure)
 **/
function getType(field) {
    if (field.toString().search(/int/i) != -1 || field.toString().search(/decimal/i) != -1
        || field.toString().search(/year/i) != -1) {
        return 'numeric';
    } else if (field.toString().search(/time/i) != -1 || field.toString().search(/date/i) != -1) {
        return 'time';
    } else {
        return 'text';
    }
}
/**
 ** Converts a categorical array into numeric array
 ** @param array categorical values array
 **/
function getCord(arr) {
    var newCord = new Array();
    var original = $.extend(true, [], arr);
    var arr = jQuery.unique(arr).sort();
    $.each(original, function(index, value) {
        newCord.push(jQuery.inArray(value, arr));
    });
    return [newCord, arr, original];
}

/**
 ** Scrolls the view to the display section
 **/
function scrollToChart() {
   var x = $('#dataDisplay').offset().top - 100; // 100 provides buffer in viewport
   $('html,body').animate({scrollTop: x}, 500);
}

/**
 ** Handlers for panning feature
 **/
function includePan(currentChart) {
    var mouseDown;
    var lastX;
    var lastY;
    var chartWidth = $('#resizer').width() - 3;
    var chartHeight = $('#resizer').height() - 20;
    $('#querychart').mousedown(function() {
        mouseDown = 1;
    });

    $('#querychart').mouseup(function() {
        mouseDown = 0;
    });
    $('#querychart').mousemove(function(e) {
        if (mouseDown == 1) {
            if (e.pageX > lastX) {
                var xExtremes = currentChart.xAxis[0].getExtremes();
                var diff = (e.pageX - lastX) * (xExtremes.max - xExtremes.min) / chartWidth;
                currentChart.xAxis[0].setExtremes(xExtremes.min - diff, xExtremes.max - diff);
            } else if (e.pageX < lastX) {
                var xExtremes = currentChart.xAxis[0].getExtremes();
                var diff = (lastX - e.pageX) * (xExtremes.max - xExtremes.min) / chartWidth;
                currentChart.xAxis[0].setExtremes(xExtremes.min + diff, xExtremes.max + diff);
            }

            if (e.pageY > lastY) {
                var yExtremes = currentChart.yAxis[0].getExtremes();
                var ydiff = 1.0 * (e.pageY - lastY) * (yExtremes.max - yExtremes.min) / chartHeight;
                currentChart.yAxis[0].setExtremes(yExtremes.min + ydiff, yExtremes.max + ydiff);
            } else if (e.pageY < lastY) {
                var yExtremes = currentChart.yAxis[0].getExtremes();
                var ydiff = 1.0 * (lastY - e.pageY) * (yExtremes.max - yExtremes.min) / chartHeight;
                currentChart.yAxis[0].setExtremes(yExtremes.min - ydiff, yExtremes.max - ydiff);
            }
        }
        lastX = e.pageX;
        lastY = e.pageY;
    });
}

$(document).ready(function() {
    var cursorMode = ($("input[name='mode']:checked").val() == 'edit') ? 'crosshair' : 'pointer';
    var currentChart = null;
    var currentData = null;
    var xLabel = $('#tableid_0').val();
    var yLabel = $('#tableid_1').val();
    var xType = $('#types_0').val();
    var yType = $('#types_1').val();
    var dataLabel = $('#dataLabel').val();
    var lastX;
    var lastY;
    var zoomRatio = 1;


    // Get query result
    var data = jQuery.parseJSON($('#querydata').html());

    /**
     ** Input form submit on field change
     **/
    $('#tableid_0').change(function() {
          $('#zoom_search_form').submit();
    });

    $('#tableid_1').change(function() {
          $('#zoom_search_form').submit();
    });

    $('#tableid_2').change(function() {
          $('#zoom_search_form').submit();
    });

    $('#tableid_3').change(function() {
          $('#zoom_search_form').submit();
    });

    /**
     * Input form validation
     **/
    $('#inputFormSubmitId').click(function() {
        if ($('#tableid_0').get(0).selectedIndex == 0 || $('#tableid_1').get(0).selectedIndex == 0) {
            PMA_ajaxShowMessage(PMA_messages['strInputNull']);
        } else if (xLabel == yLabel) {
            PMA_ajaxShowMessage(PMA_messages['strSameInputs']);
        }
    });

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

    /**
     ** Set dialog properties for the data display form
     **/
    var buttonOptions = {};
    /*
     * Handle saving of a row in the editor
     */
    buttonOptions[PMA_messages['strSave']] = function () {
        //Find changed values by comparing form values with selectedRow Object
        var newValues = new Object();//Stores the values changed from original
        var sqlTypes = new Object();
        var it = 4;
        var xChange = false;
        var yChange = false;
        for (key in selectedRow) {
            var oldVal = selectedRow[key];
            var newVal = ($('#fields_null_id_' + it).attr('checked')) ? null : $('#fieldID_' + it).val();
            if (newVal instanceof Array) { // when the column is of type SET
                newVal =  $('#fieldID_' + it).map(function(){
                    return $(this).val();
                }).get().join(",");
            }
            if (oldVal != newVal) {
                selectedRow[key] = newVal;
                newValues[key] = newVal;
                if (key == xLabel) {
                    xChange = true;
                    data[currentData][xLabel] = newVal;
                } else if (key == yLabel) {
                    yChange = true;
                    data[currentData][yLabel] = newVal;
                }
            }
            var $input = $('#fieldID_' + it);
            if ($input.hasClass('bit')) {
                sqlTypes[key] = 'bit';
            }
            it++;
        } //End data update

        //Update the chart series and replot
        if (xChange || yChange) {
            var newSeries = new Array();
            newSeries[0] = new Object();
            newSeries[0].marker = {
                symbol: 'circle'
            };
            //Logic similar to plot generation, replot only if xAxis changes or yAxis changes. 
            //Code includes a lot of checks so as to replot only when necessary
            if (xChange) {
                xCord[currentData] = selectedRow[xLabel];
                if (xType == 'numeric') {
                    currentChart.series[0].data[currentData].update({ x : selectedRow[xLabel] });
                    currentChart.xAxis[0].setExtremes(Array.min(xCord) - 6, Array.max(xCord) + 6);
                } else if (xType == 'time') {
                    currentChart.series[0].data[currentData].update({ 
                        x : getTimeStamp(selectedRow[xLabel], $('#types_0').val())
                    });
                } else {
                    var tempX = getCord(xCord);
                    var tempY = getCord(yCord);
                    var i = 0;
                    newSeries[0].data = new Array();
                    xCord = tempX[2];
                    yCord = tempY[2];

                    $.each(data, function(key, value) {
                        if (yType != 'text') {
                            newSeries[0].data.push({ 
                                name: value[dataLabel], 
                                x: tempX[0][i], 
                                y: value[yLabel], 
                                marker: {fillColor: colorCodes[i % 8]}, 
                                id: i 
                            });
                        } else {
                            newSeries[0].data.push({ 
                                name: value[dataLabel], 
                                x: tempX[0][i], 
                                y: tempY[0][i], 
                                marker: {fillColor: colorCodes[i % 8]}, 
                                id: i 
                            });
                        }
                        i++;
                    });
                    currentSettings.xAxis.labels = {
                        formatter : function() {
                            if (tempX[1][this.value] && tempX[1][this.value].length > 10) {
                                return tempX[1][this.value].substring(0, 10);
                            } else {
                                return tempX[1][this.value];
                            }
                        }
                     };
                     currentSettings.series = newSeries;
                     currentChart = PMA_createChart(currentSettings);
                 }

            }
            if (yChange) {
                yCord[currentData] = selectedRow[yLabel];
                if (yType == 'numeric') {
                    currentChart.series[0].data[currentData].update({ y : selectedRow[yLabel] });
                    currentChart.yAxis[0].setExtremes(Array.min(yCord) - 6, Array.max(yCord) + 6);
                } else if (yType == 'time') {
                    currentChart.series[0].data[currentData].update({ 
                        y : getTimeStamp(selectedRow[yLabel], $('#types_1').val())
                    });
                } else {
                    var tempX = getCord(xCord);
                    var tempY = getCord(yCord);
                    var i = 0;
                    newSeries[0].data = new Array();
                    xCord = tempX[2];
                    yCord = tempY[2];

                    $.each(data, function(key, value) {
                        if (xType != 'text' ) {
                            newSeries[0].data.push({ 
                                name: value[dataLabel], 
                                x: value[xLabel], 
                                y: tempY[0][i], 
                                marker: {fillColor: colorCodes[i % 8]}, 
                                id: i 
                            });
                        } else {
                            newSeries[0].data.push({ 
                                name: value[dataLabel], 
                                x: tempX[0][i], 
                                y: tempY[0][i], 
                                marker: {fillColor: colorCodes[i % 8]}, 
                                id: i 
                            });
                        }
                        i++;
                    });
                    currentSettings.yAxis.labels = {
                        formatter : function() {
                            if (tempY[1][this.value] && tempY[1][this.value].length > 10) {
                                return tempY[1][this.value].substring(0, 10);
                            } else {
                                return tempY[1][this.value];
                            }
                        }
                     };
                     currentSettings.series = newSeries;
                     currentChart = PMA_createChart(currentSettings);
                }
            }
            currentChart.series[0].data[currentData].select();
        } //End plot update

        //Generate SQL query for update
        if (!isEmpty(newValues)) {
            var sql_query = 'UPDATE `' + window.parent.table + '` SET ';
            for (key in newValues) {
                sql_query += '`' + key + '`=' ;
                var value = newValues[key];

                // null
                if (value == null) {
                    sql_query += 'NULL, ';

                // empty
                } else if ($.trim(value) == '') {
                    sql_query += "'', ";

                // other
                } else {
                    // type explicitly identified
                    if (sqlTypes[key] != null) {
                        if (sqlTypes[key] == 'bit') {
                            sql_query += "b'" + value + "', ";
                        }
                    // type not explicitly identified
                    } else {
                        if (!isNumeric(value)) {
                            sql_query += "'" + value + "', ";
                        } else {
                            sql_query += value + ', ';
                        }
                    }
                }
            }
            sql_query = sql_query.substring(0, sql_query.length - 2);
            sql_query += ' WHERE ' + PMA_urldecode(data[currentData]['where_clause']);

            //Post SQL query to sql.php
            $.post('sql.php', {
                    'token' : window.parent.token,
                    'db' : window.parent.db,
                    'ajax_request' : true,
                    'sql_query' : sql_query,
                    'inline_edit' : false
                }, function(data) {
                    if (data.success == true) {
                        $('#sqlqueryresults').html(data.sql_query);
                        $("#sqlqueryresults").trigger('appendAnchor');
                    } else {
                        PMA_ajaxShowMessage(data.error, false);
                    }
            }); //End $.post
        }//End database update
        $("#dataDisplay").dialog('close');
    };
    buttonOptions[PMA_messages['strCancel']] = function () {
        $(this).dialog('close');
    };
    $("#dataDisplay").dialog({
        autoOpen: false,
        title: PMA_messages['strDataPointContent'],
        modal: true,
        buttons: buttonOptions,
        width: $('#dataDisplay').width() + 80,
        open: function () {
            $(this).find('input[type=checkbox]').css('margin', '0.5em');
        }
    });
    /**
     * Attach Ajax event handlers for input fields
     * in the dialog. Used to submit the Ajax
     * request when the ENTER key is pressed.
     */
    $("#dataDisplay").find(':input').live('keydown', function (e) {
        if (e.which === 13) { // 13 is the ENTER key
            e.preventDefault();
            if (typeof buttonOptions[PMA_messages['strSave']] === 'function') {
                buttonOptions[PMA_messages['strSave']].call();
            }
        }
    });


    /*
     * Generate plot using jqplot 
     */

    if (data != null) {
        $('#zoom_search_form')
         .slideToggle()
         .hide();
        $('#togglesearchformlink')
         .text(PMA_messages['strShowSearchCriteria']);
        $('#togglesearchformdiv').show();
        var selectedRow;
        var colorCodes = ['#FF0000', '#00FFFF', '#0000FF', '#0000A0', '#FF0080', '#800080', '#FFFF00', '#00FF00', '#FF00FF'];
        var series = new Array();
        var xCord = new Array();
        var yCord = new Array();
        var tempX, tempY;
        var it = 0;
        var xMax; // xAxis extreme max
        var xMin; // xAxis extreme min
        var yMax; // yAxis extreme max
        var yMin; // yAxis extreme min

        var options = {
            series: [
                // for a scatter plot
                { showLine: false }
            ],
            axes: {
                xaxis: {
                    label: $('#tableid_0').val(),
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer 
                },
                yaxis: {
                    label: $('#tableid_1').val(),
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer 
                },
            },
            highlighter: {
                show: true,
                tooltipAxes: 'y',
                yvalues: 2,
                // hide the first y value
                formatString: '<span class="hide">%s</span>%s'
            },
            cursor: {
                show: true,
                zoom: true,
                showTooltip: false
            }
        }

        // If data label is not set, do not show tooltips
        if (dataLabel == '') {
            options.highlighter.show = false;
        }

        // Classify types as either numeric,time,text
        xType = getType(xType);
        yType = getType(yType);

        // could have multiple series but we'll have just one
        series[0] = new Array();

        if (xType == 'numeric' && yType == 'numeric') {
            $.each(data, function(key, value) {
                var xVal = parseFloat(value[xLabel]);
                var yVal = parseFloat(value[yLabel]);
                series[0].push([
                    xVal, 
                    yVal, 
                    value[dataLabel], // extra Y value for highlighter
                                      // (may set an undefined value)
                    value['where_clause'] // extra Y value for click on point
                ]);
            });
        }

        currentChart = $.jqplot('querychart', series, options);

        $('button.button-reset').click(function(event) {
            event.preventDefault();
            currentChart.resetZoom();
        });

        $('div#querychart').bind('jqplotDataClick',
            function(event, seriesIndex, pointIndex, data) {
            }
        );
    }
});
