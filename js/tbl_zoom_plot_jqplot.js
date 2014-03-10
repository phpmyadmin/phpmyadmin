// TODO: change the axis
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
    PMA_ajaxShowMessage(PMA_messages.strDisplayHelp, 10000);
}

/**
 ** Extend the array object for max function
 ** @param array
 **/
Array.max = function (array) {
    return Math.max.apply(Math, array);
};

/**
 ** Extend the array object for min function
 ** @param array
 **/
Array.min = function (array) {
    return Math.min.apply(Math, array);
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
 ** Converts a date/time into timestamp
 ** @param val  String Date
 ** @param type Sring  Field type(datetime/timestamp/time/date)
 **/
function getTimeStamp(val, type) {
    if (type.toString().search(/datetime/i) != -1 ||
        type.toString().search(/timestamp/i) != -1
    ) {
        return $.datepicker.parseDateTime('yy-mm-dd', 'HH:mm:ss', val);
    }
    else if (type.toString().search(/time/i) != -1) {
        return $.datepicker.parseDateTime('yy-mm-dd', 'HH:mm:ss', '1970-01-01 ' + val);
    }
    else if (type.toString().search(/date/i) != -1) {
        return $.datepicker.parseDate('yy-mm-dd', val);
    }
}

/**
 ** Classifies the field type into numeric,timeseries or text
 ** @param field: field type (as in database structure)
 **/
function getType(field) {
    if (field.toString().search(/int/i) != -1 ||
        field.toString().search(/decimal/i) != -1 ||
        field.toString().search(/year/i) != -1
    ) {
        return 'numeric';
    } else if (field.toString().search(/time/i) != -1 ||
        field.toString().search(/date/i) != -1
    ) {
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
    var newCord = [];
    var original = $.extend(true, [], arr);
    arr = jQuery.unique(arr).sort();
    $.each(original, function (index, value) {
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
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_zoom_plot_jqplot.js', function () {
    $('#tableid_0').unbind('change');
    $('#tableid_1').unbind('change');
    $('#tableid_2').unbind('change');
    $('#tableid_3').unbind('change');
    $('#inputFormSubmitId').unbind('click');
    $('#togglesearchformlink').unbind('click');
    $("#dataDisplay").find(':input').die('keydown');
    $('button.button-reset').unbind('click');
    $('div#resizer').unbind('resizestop');
    $('div#querychart').unbind('jqplotDataClick');
});

AJAX.registerOnload('tbl_zoom_plot_jqplot.js', function () {
    var cursorMode = ($("input[name='mode']:checked").val() == 'edit') ? 'crosshair' : 'pointer';
    var currentChart = null;
    var searchedDataKey = null;
    var xLabel = $('#tableid_0').val();
    var yLabel = $('#tableid_1').val();
    // will be updated via Ajax
    var xType = $('#types_0').val();
    var yType = $('#types_1').val();
    var dataLabel = $('#dataLabel').val();
    var lastX;
    var lastY;
    var zoomRatio = 1;


    // Get query result
    var searchedData = jQuery.parseJSON($('#querydata').html());

    /**
     ** Input form submit on field change
     **/

    // first column choice corresponds to the X axis
    $('#tableid_0').change(function () {
        //AJAX request for field type, collation, operators, and value field
        $.post('tbl_zoom_select.php', {
            'ajax_request' : true,
            'change_tbl_info' : true,
            'db' : PMA_commonParams.get('db'),
            'table' : PMA_commonParams.get('table'),
            'field' : $('#tableid_0').val(),
            'it' : 0,
            'token' : PMA_commonParams.get('token')
        }, function (data) {
            $('#tableFieldsId tr:eq(1) td:eq(0)').html(data.field_type);
            $('#tableFieldsId tr:eq(1) td:eq(1)').html(data.field_collation);
            $('#tableFieldsId tr:eq(1) td:eq(2)').html(data.field_operators);
            $('#tableFieldsId tr:eq(1) td:eq(3)').html(data.field_value);
            xLabel = $('#tableid_0').val();
            $('#types_0').val(data.field_type);
            xType = data.field_type;
            $('#collations_0').val(data.field_collations);
            addDateTimePicker();
        });
    });

    // second column choice corresponds to the Y axis
    $('#tableid_1').change(function () {
        //AJAX request for field type, collation, operators, and value field
        $.post('tbl_zoom_select.php', {
            'ajax_request' : true,
            'change_tbl_info' : true,
            'db' : PMA_commonParams.get('db'),
            'table' : PMA_commonParams.get('table'),
            'field' : $('#tableid_1').val(),
            'it' : 1,
            'token' : PMA_commonParams.get('token')
        }, function (data) {
            $('#tableFieldsId tr:eq(3) td:eq(0)').html(data.field_type);
            $('#tableFieldsId tr:eq(3) td:eq(1)').html(data.field_collation);
            $('#tableFieldsId tr:eq(3) td:eq(2)').html(data.field_operators);
            $('#tableFieldsId tr:eq(3) td:eq(3)').html(data.field_value);
            yLabel = $('#tableid_1').val();
            $('#types_1').val(data.field_type);
            yType = data.field_type;
            $('#collations_1').val(data.field_collations);
            addDateTimePicker();
        });
    });

    $('#tableid_2').change(function () {
        //AJAX request for field type, collation, operators, and value field
        $.post('tbl_zoom_select.php', {
            'ajax_request' : true,
            'change_tbl_info' : true,
            'db' : PMA_commonParams.get('db'),
            'table' : PMA_commonParams.get('table'),
            'field' : $('#tableid_2').val(),
            'it' : 2,
            'token' : PMA_commonParams.get('token')
        }, function (data) {
            $('#tableFieldsId tr:eq(6) td:eq(0)').html(data.field_type);
            $('#tableFieldsId tr:eq(6) td:eq(1)').html(data.field_collation);
            $('#tableFieldsId tr:eq(6) td:eq(2)').html(data.field_operators);
            $('#tableFieldsId tr:eq(6) td:eq(3)').html(data.field_value);
            $('#types_2').val(data.field_type);
            $('#collations_2').val(data.field_collations);
            addDateTimePicker();
        });
    });

    $('#tableid_3').change(function () {
        //AJAX request for field type, collation, operators, and value field
        $.post('tbl_zoom_select.php', {
            'ajax_request' : true,
            'change_tbl_info' : true,
            'db' : PMA_commonParams.get('db'),
            'table' : PMA_commonParams.get('table'),
            'field' : $('#tableid_3').val(),
            'it' : 3,
            'token' : PMA_commonParams.get('token')
        }, function (data) {
            $('#tableFieldsId tr:eq(8) td:eq(0)').html(data.field_type);
            $('#tableFieldsId tr:eq(8) td:eq(1)').html(data.field_collation);
            $('#tableFieldsId tr:eq(8) td:eq(2)').html(data.field_operators);
            $('#tableFieldsId tr:eq(8) td:eq(3)').html(data.field_value);
            $('#types_3').val(data.field_type);
            $('#collations_3').val(data.field_collations);
            addDateTimePicker();
        });
    });

    /**
     * Input form validation
     **/
    $('#inputFormSubmitId').click(function () {
        if ($('#tableid_0').get(0).selectedIndex === 0 || $('#tableid_1').get(0).selectedIndex === 0) {
            PMA_ajaxShowMessage(PMA_messages.strInputNull);
        } else if (xLabel == yLabel) {
            PMA_ajaxShowMessage(PMA_messages.strSameInputs);
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
        .html(PMA_messages.strShowSearchCriteria)
        .bind('click', function () {
            var $link = $(this);
            $('#zoom_search_form').slideToggle();
            if ($link.text() == PMA_messages.strHideSearchCriteria) {
                $link.text(PMA_messages.strShowSearchCriteria);
            } else {
                $link.text(PMA_messages.strHideSearchCriteria);
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
    buttonOptions[PMA_messages.strSave] = function () {
        //Find changed values by comparing form values with selectedRow Object
        var newValues = {};//Stores the values changed from original
        var sqlTypes = {};
        var it = 0;
        var xChange = false;
        var yChange = false;
        var key;
        for (key in selectedRow) {
            var oldVal = selectedRow[key];
            var newVal = ($('#edit_fields_null_id_' + it).prop('checked')) ? null : $('#edit_fieldID_' + it).val();
            if (newVal instanceof Array) { // when the column is of type SET
                newVal =  $('#edit_fieldID_' + it).map(function () {
                    return $(this).val();
                }).get().join(",");
            }
            if (oldVal != newVal) {
                selectedRow[key] = newVal;
                newValues[key] = newVal;
                if (key == xLabel) {
                    xChange = true;
                    searchedData[searchedDataKey][xLabel] = newVal;
                } else if (key == yLabel) {
                    yChange = true;
                    searchedData[searchedDataKey][yLabel] = newVal;
                }
            }
            var $input = $('#edit_fieldID_' + it);
            if ($input.hasClass('bit')) {
                sqlTypes[key] = 'bit';
            }
            it++;
        } //End data update

        //Update the chart series and replot
        if (xChange || yChange) {
            //Logic similar to plot generation, replot only if xAxis changes or yAxis changes.
            //Code includes a lot of checks so as to replot only when necessary
            if (xChange) {
                xCord[searchedDataKey] = selectedRow[xLabel];
                // [searchedDataKey][0] contains the x value
                if (xType == 'numeric') {
                    series[0][searchedDataKey][0] = selectedRow[xLabel];
                } else if (xType == 'time') {
                    series[0][searchedDataKey][0] =
                        getTimeStamp(selectedRow[xLabel], $('#types_0').val());
                } else {
                    // TODO: text values
                }
                currentChart.series[0].data = series[0];
                // TODO: axis changing
                currentChart.replot();

            }
            if (yChange) {
                yCord[searchedDataKey] = selectedRow[yLabel];
                // [searchedDataKey][1] contains the y value
                if (yType == 'numeric') {
                    series[0][searchedDataKey][1] = selectedRow[yLabel];
                } else if (yType == 'time') {
                    series[0][searchedDataKey][1] =
                        getTimeStamp(selectedRow[yLabel], $('#types_1').val());
                } else {
                    // TODO: text values
                }
                currentChart.series[0].data = series[0];
                // TODO: axis changing
                currentChart.replot();
            }
        } //End plot update

        //Generate SQL query for update
        if (!isEmpty(newValues)) {
            var sql_query = 'UPDATE `' + PMA_commonParams.get('table') + '` SET ';
            for (key in newValues) {
                sql_query += '`' + key + '`=';
                var value = newValues[key];

                // null
                if (value === null) {
                    sql_query += 'NULL, ';

                // empty
                } else if ($.trim(value) === '') {
                    sql_query += "'', ";

                // other
                } else {
                    // type explicitly identified
                    if (sqlTypes[key] !== null) {
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
            // remove two extraneous characters ', '
            sql_query = sql_query.substring(0, sql_query.length - 2);
            sql_query += ' WHERE ' + PMA_urldecode(searchedData[searchedDataKey].where_clause);

            //Post SQL query to sql.php
            $.post('sql.php', {
                    'token' : PMA_commonParams.get('token'),
                    'db' : PMA_commonParams.get('db'),
                    'ajax_request' : true,
                    'sql_query' : sql_query,
                    'inline_edit' : false
                }, function (data) {
                    if (data.success === true) {
                        $('#sqlqueryresults').html(data.sql_query);
                        $("#sqlqueryresults").trigger('appendAnchor');
                    } else {
                        PMA_ajaxShowMessage(data.error, false);
                    }
                }); //End $.post
        }//End database update
        $("#dataDisplay").dialog('close');
    };
    buttonOptions[PMA_messages.strCancel] = function () {
        $(this).dialog('close');
    };
    $("#dataDisplay").dialog({
        autoOpen: false,
        title: PMA_messages.strDataPointContent,
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
            if (typeof buttonOptions[PMA_messages.strSave] === 'function') {
                buttonOptions[PMA_messages.strSave].call();
            }
        }
    });


    /*
     * Generate plot using jqplot
     */

    if (searchedData !== null) {
        $('#zoom_search_form')
         .slideToggle()
         .hide();
        $('#togglesearchformlink')
         .text(PMA_messages.strShowSearchCriteria);
        $('#togglesearchformdiv').show();
        var selectedRow;
        var colorCodes = ['#FF0000', '#00FFFF', '#0000FF', '#0000A0', '#FF0080', '#800080', '#FFFF00', '#00FF00', '#FF00FF'];
        var series = [];
        var xCord = [];
        var yCord = [];
        var tempX, tempY;
        var it = 0;
        var xMax; // xAxis extreme max
        var xMin; // xAxis extreme min
        var yMax; // yAxis extreme max
        var yMin; // yAxis extreme min
        var xVal;
        var yVal;
        var format;

        var options = {
            series: [
                // for a scatter plot
                { showLine: false }
            ],
            grid: {
                drawBorder: false,
                shadow: false,
                background: 'rgba(0,0,0,0)'
            },
            axes: {
                xaxis: {
                    label: $('#tableid_0').val(),
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                },
                yaxis: {
                    label: $('#tableid_1').val(),
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                }
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
        };

        // If data label is not set, do not show tooltips
        if (dataLabel === '') {
            options.highlighter.show = false;
        }

        // Classify types as either numeric,time,text
        xType = getType(xType);
        yType = getType(yType);

        // could have multiple series but we'll have just one
        series[0] = [];

        if (xType == 'time') {
            var originalXType = $('#types_0').val();
            if (originalXType == 'date') {
                format = '%Y-%m-%d';
            }
            // TODO: does not seem to work
            //else if (originalXType == 'time') {
              //  format = '%H:%M';
            //} else {
            //    format = '%Y-%m-%d %H:%M';
            //}
            $.extend(options.axes.xaxis, {
                renderer: $.jqplot.DateAxisRenderer,
                tickOptions: {
                    formatString: format
                }
            });
        }
        if (yType == 'time') {
            var originalYType = $('#types_1').val();
            if (originalYType == 'date') {
                format = '%Y-%m-%d';
            }
            $.extend(options.axes.yaxis, {
                renderer: $.jqplot.DateAxisRenderer,
                tickOptions: {
                    formatString: format
                }
            });
        }

        $.each(searchedData, function (key, value) {
            if (xType == 'numeric') {
                xVal = parseFloat(value[xLabel]);
            }
            if (xType == 'time') {
                xVal = getTimeStamp(value[xLabel], originalXType);
            }
            if (yType == 'numeric') {
                yVal = parseFloat(value[yLabel]);
            }
            if (yType == 'time') {
                yVal = getTimeStamp(value[yLabel], originalYType);
            }
            series[0].push([
                xVal,
                yVal,
                // extra Y values
                value[dataLabel], // for highlighter
                                  // (may set an undefined value)
                value.where_clause, // for click on point
                key               // key from searchedData
            ]);
        });

        // under IE 8, the initial display is mangled; after a manual
        // resizing, it's ok
        // under IE 9, everything is fine
        currentChart = $.jqplot('querychart', series, options);
        currentChart.resetZoom();

        $('button.button-reset').click(function (event) {
            event.preventDefault();
            currentChart.resetZoom();
        });

        $('div#resizer').resizable();
        $('div#resizer').bind('resizestop', function (event, ui) {
            // make room so that the handle will still appear
            $('div#querychart').height($('div#resizer').height() * 0.96);
            $('div#querychart').width($('div#resizer').width() * 0.96);
            currentChart.replot({resetAxes: true});
        });

        $('div#querychart').bind('jqplotDataClick',
            function (event, seriesIndex, pointIndex, data) {
                searchedDataKey = data[4]; // key from searchedData (global)
                var field_id = 0;
                var post_params = {
                    'ajax_request' : true,
                    'get_data_row' : true,
                    'db' : PMA_commonParams.get('db'),
                    'table' : PMA_commonParams.get('table'),
                    'where_clause' : data[3],
                    'token' : PMA_commonParams.get('token')
                };

                $.post('tbl_zoom_select.php', post_params, function (data) {
                    // Row is contained in data.row_info,
                    // now fill the displayResultForm with row values
                    var key;
                    for (key in data.row_info) {
                        $field = $('#edit_fieldID_' + field_id);
                        $field_null = $('#edit_fields_null_id_' + field_id);
                        if (data.row_info[key] === null) {
                            $field_null.prop('checked', true);
                            $field.val('');
                        } else {
                            $field_null.prop('checked', false);
                            if ($field.attr('multiple')) { // when the column is of type SET
                                $field.val(data.row_info[key].split(','));
                            } else {
                                $field.val(data.row_info[key]);
                            }
                        }
                        field_id++;
                    }
                    selectedRow = {};
                    selectedRow = data.row_info;
                });

                $("#dataDisplay").dialog("open");
            }
        );
    }
});
