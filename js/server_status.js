/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used in server status pages
 * @name            Server Status
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    jQueryCookie
 * @requires    jQueryTablesorter
 * @requires    jqPlot
 * @requires    canvg
 * @requires    js/functions.js
 *
 */

var pma_token,
    url_query,
    server_time_diff,
    server_os,
    is_superuser,
    server_db_isLocal;

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_status.js', function() {
    $('a.popupLink').unbind('click');
    $(document).unbind('click'); // Am I sure about this? I guess not...
    $('div.buttonlinks select').unbind('click');
    $('div.buttonlinks a.tabRefresh').unbind('click');
});

// Add a tablesorter parser to properly handle thousands seperated numbers and SI prefixes
AJAX.registerOnload('server_status.js', function() {

    var $js_data_form = $('#js_data');
    pma_token =         $js_data_form.find("input[name=pma_token]").val();
    url_query =         $js_data_form.find("input[name=url_query]").val();
    server_time_diff  = eval($js_data_form.find("input[name=server_time_diff]").val());
    server_os =         $js_data_form.find("input[name=server_os]").val();
    is_superuser =      $js_data_form.find("input[name=is_superuser]").val();
    server_db_isLocal = $js_data_form.find("input[name=server_db_isLocal]").val();

    // Show all javascript related parts of the page
    $('#serverstatus .jsfeature').show();

    jQuery.tablesorter.addParser({
        id: "fancyNumber",
        is: function(s) {
            return /^[0-9]?[0-9,\.]*\s?(k|M|G|T|%)?$/.test(s);
        },
        format: function(s) {
            var num = jQuery.tablesorter.formatFloat(
                s.replace(PMA_messages['strThousandsSeparator'], '')
                 .replace(PMA_messages['strDecimalSeparator'], '.')
            );

            var factor = 1;
            switch (s.charAt(s.length - 1)) {
                case '%': factor = -2; break;
                // Todo: Complete this list (as well as in the regexp a few lines up)
                case 'k': factor = 3; break;
                case 'M': factor = 6; break;
                case 'G': factor = 9; break;
                case 'T': factor = 12; break;
            }

            return num * Math.pow(10, factor);
        },
        type: "numeric"
    });

    jQuery.tablesorter.addParser({
        id: "withinSpanNumber",
        is: function(s) {
            return /<span class="original"/.test(s);
        },
        format: function(s, table, html) {
            var res = html.innerHTML.match(/<span(\s*style="display:none;"\s*)?\s*class="original">(.*)?<\/span>/);
            return (res && res.length >= 3) ? res[2] : 0;
        },
        type: "numeric"
    });

    // faster zebra widget: no row visibility check, faster css class switching, no cssChildRow check
    jQuery.tablesorter.addWidget({
        id: "fast-zebra",
        format: function (table) {
            if (table.config.debug) {
                var time = new Date();
            }
            $("tr:even", table.tBodies[0])
                .removeClass(table.config.widgetZebra.css[0])
                .addClass(table.config.widgetZebra.css[1]);
            $("tr:odd", table.tBodies[0])
                .removeClass(table.config.widgetZebra.css[1])
                .addClass(table.config.widgetZebra.css[0]);
            if (table.config.debug) {
                $.tablesorter.benchmark("Applying Fast-Zebra widget", time);
            }
        }
    });

    // Popup behaviour
    $('a.popupLink').click( function() {
        var $link = $(this);

        $('div.' + $link.attr('href').substr(1))
            .show()
            .offset({ top: $link.offset().top + $link.height() + 5, left: $link.offset().left })
            .addClass('openedPopup');

        return false;
    });

    $(document).click( function(event) {
        $('div.openedPopup').each(function() {
            var $cnt = $(this);
            var pos = $cnt.offset();

            // Hide if the mouseclick is outside the popupcontent
            if (event.pageX < pos.left
               || event.pageY < pos.top
               || event.pageX > pos.left + $cnt.outerWidth()
               || event.pageY > pos.top + $cnt.outerHeight()
            ) {
                $cnt.hide().removeClass('openedPopup');
            }
        });
    });
});

AJAX.registerOnload('server_status.js', function() {
    // Filters for status variables
    var textFilter = null;
    var alertFilter = false;
    var categoryFilter = '';
    var odd_row = false;
    var text = ''; // Holds filter text
    var queryPieChart = null;
    var monitorLoaded = false;

    /* Chart configuration */
    // Defines what the tabs are currently displaying (realtime or data)
    var tabStatus = new Object();
    // Holds the current chart instances for each tab
    var tabChart = new Object();
    // Holds current live charts' timeouts
    var chart_replot_timers = new Object();

    /*** Table sort tooltip ***/
    PMA_createqTip($('table.sortable thead th'), PMA_messages['strSortHint']);

    $.ajaxSetup({
        cache: false
    });

    // Add tabs
    $('#serverStatusTabs').tabs({
        // Tab persistence
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        show: function(event, ui) {
            // Fixes line break in the menu bar when the page overflows and scrollbar appears
            $('#topmenu').menuResizer('resize');

            // Initialize selected tab
            if (!$(ui.tab.hash).data('init-done')) {
                initTab($(ui.tab.hash), null);
            }
            // Load Server status monitor
            if (ui.tab.hash == '#statustabs_charting' && ! monitorLoaded) {
                $('div#statustabs_charting').append( //PMA_messages['strLoadingMonitor'] + ' ' +
                    '<img class="ajaxIcon" id="loadingMonitorIcon" src="' +
                    pmaThemeImage + 'ajax_clock_small.gif" alt="">'
                );
                // Delay loading a bit so the tab loads and the user gets to see a ajax loading icon
                setTimeout(function() {
                    var scripts = [
                        {name:'jquery/timepicker.js',fire:0},
                        {name:'jquery/jquery.json-2.2.js',fire:0},
                        {name:'jquery/jquery.sortableTable.js',fire:0},
                        {name:'server_status_monitor.js',fire:1}
                    ];
                    AJAX.scriptHandler.load(scripts);
                }, 50);

                monitorLoaded = true;
            }
        }
    });

    // Fixes wrong tab height with floated elements. See also http://bugs.jqueryui.com/ticket/5601
    $(".ui-widget-content:not(.ui-tabs):not(.ui-helper-clearfix)").addClass("ui-helper-clearfix");

    // Initialize each tab
    $('div.ui-tabs-panel').each(function() {
        var $tab = $(this);
        tabStatus[$tab.attr('id')] = 'static';
        // Initialize tabs after browser catches up with previous changes and displays tabs
        setTimeout(function() {
            initTab($tab, null);
        }, 0.5);
    });

    /** Realtime charting of variables **/

    function recursiveTimer($tab, type) {
            replotLiveChart($tab, type);
            chart_replot_timers[$tab.attr('id')] = setTimeout(function() {
                recursiveTimer($tab, type) }, ($('.refreshRate :selected', $tab).val() * 1000));
    }

    function getCurrentDataSet($tab, type) {
        var ret = null;
        var line1 = null;
        var line2 = null;
        var retval = null;

        $.ajax({
            async: false,
            url: 'server_status.php',
            type: 'post',
            data: {
                'token' : PMA_commonParams.get('token'),
                'server' : PMA_commonParams.get('server'),
                'ajax_request' : true,
                'chart_data' : true,
                'type' : type
            },
            dataType: 'json',
            success: function(data) {
                ret = $.parseJSON(data.message);
            }
        });
        // get data based on chart type
        if(type == 'proc') {
            line1 = [ret.x, ret.y_conn - previous_y_line1[$tab.attr('id')]];
            line2 = [ret.x, ret.y_proc];
            previous_y_line1[$tab.attr('id')] = ret.y_conn;
        }
        else if(type == 'queries') {
            line1 = [ret.x, ret.y-previous_y_line1[$tab.attr('id')]];
            previous_y_line1[$tab.attr('id')] = ret.y;
        }
        else if(type == 'traffic') {
            ret.y_sent = ret.y_sent/1024;
            ret.y_received = ret.y_received/1024;
            line1 = [ret.x, ret.y_sent - previous_y_line1[$tab.attr('id')]];
            line2 = [ret.x, ret.y_received - previous_y_line2[$tab.attr('id')]];
            previous_y_line1[$tab.attr('id')] = ret.y_sent;
            previous_y_line2[$tab.attr('id')] = ret.y_received;
        }

        retval = [line1, line2];
        return retval;
    }

    function getSettings(type) {

        var settings = {
            grid: {
                drawBorder: false,
                shadow: false,
                background: 'rgba(0,0,0,0)'
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    tickOptions: {
                        formatString: '%H:%M:%S',
                        showGridline: false
                    }
                },
                yaxis: {
                    autoscale:true,
                    label: PMA_messages['strTotalCount'],
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                }
            },
            seriesDefaults: {
                rendererOptions: {
                    smooth: true
                }
            },
            legend: {
                show: true,
                location: 's',     // compass direction, nw, n, ne, e, se, s, sw, w.
                xoffset: 12,        // pixel offset of the legend box from the x (or x2) axis.
                yoffset: 12        // pixel offset of the legend box from the y (or y2) axis.
            }
        };

        var title_message;
        var x_legend = new Array();
        if(type == 'proc') {
            title_message = PMA_messages['strChartConnectionsTitle'];
            x_legend[0] = PMA_messages['strChartConnections'];
            x_legend[1] = PMA_messages['strChartProcesses'];
            settings.series = [ {label: x_legend[0]}, {label: x_legend[1]} ];
        }
        else if(type == 'queries') {
            title_message = PMA_messages['strChartIssuedQueriesTitle'];
            x_legend[0] = PMA_messages['strChartIssuedQueries'];
            settings.series = [ {label: x_legend[0]} ];
        }
        else if(type == 'traffic') {
            title_message = PMA_messages['strChartServerTraffic'];
            x_legend[0] = PMA_messages['strChartKBSent'];
            x_legend[1] = PMA_messages['strChartKBReceived'];
            settings.series = [ {label: x_legend[0]}, {label: x_legend[1]} ];
        }
        settings.title = title_message;

        return settings;
    }

    function replotLiveChart($tab, type) {
        var data_set = getCurrentDataSet($tab, type);
        if(type == 'proc' || type == 'traffic') {
            series[$tab.attr('id')][0].push(data_set[0]);
            series[$tab.attr('id')][1].push(data_set[1]);
            // update data set
            tabChart[$tab.attr('id')].series[0].data = series[$tab.attr('id')][0];
            tabChart[$tab.attr('id')].series[1].data = series[$tab.attr('id')][1];
        }
        else if(type == 'queries') {
            // there is just one line to be plotted
            series[$tab.attr('id')][0].push(data_set[0]);
            // update data set
            tabChart[$tab.attr('id')].series[0].data = series[$tab.attr('id')][0];
        }
        tabChart[$tab.attr('id')].resetAxesScale();
        var current_time = new Date().getTime();
        var data_points = $('.dataPointsNumber :selected', $tab).val();
        var refresh_rate = $('.refreshRate :selected', $tab).val() * 1000;
        // Min X would be decided based on refresh rate and number of data points
        var minX = current_time - (refresh_rate * data_points);
        var interval = (((current_time - minX)/data_points) / 1000);
        interval = (data_points > 20) ? (((current_time - minX)/20) / 1000) : interval;
        // update chart options
        tabChart[$tab.attr('id')]['axes']['xaxis']['max'] = current_time;
        tabChart[$tab.attr('id')]['axes']['xaxis']['min'] = minX;
        tabChart[$tab.attr('id')]['axes']['xaxis']['tickInterval'] = interval + " seconds";
        // replot
        tabChart[$tab.attr('id')].replot();
    }

    /* Adjust DOM / Add handlers to the tabs */
    function initTab(tab, data) {
        if ($(tab).data('init-done') && !data) {
            return;
        }
        $(tab).data('init-done', true);
        switch(tab.attr('id')) {
            case 'statustabs_traffic':
                if (data != null) {
                    tab.find('.tabInnerContent').html(data.message);
                }
                PMA_showHints();
                break;
        }
    }

    // TODO: tablesorter shouldn't sort already sorted columns
    function initTableSorter(tabid) {
        var $table, opts;
        switch(tabid) {
            case 'statustabs_queries':
                $table = $('#serverstatusqueriesdetails');
                opts = {
                    sortList: [[3, 1]],
                    widgets: ['fast-zebra'],
                    headers: {
                        1: { sorter: 'fancyNumber' },
                        2: { sorter: 'fancyNumber' }
                    }
                };
                break;
            case 'statustabs_allvars':
                $table = $('#serverstatusvariables');
                opts = {
                    sortList: [[0, 0]],
                    widgets: ['fast-zebra'],
                    headers: {
                        1: { sorter: 'withinSpanNumber' }
                    }
                };
                break;
        }
        $table.tablesorter(opts);
        $table.find('tr:first th')
            .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');
    }

    // Provides a nicely formatted and sorted tooltip of each datapoint of the query statistics
    function sortedQueriesPointInfo(queries, lastQueries){
        var max, maxIdx, num = 0;
        var queryKeys = new Array();
        var queryValues = new Array();
        var sumOther = 0;
        var sumTotal = 0;

        // Separate keys and values, then  sort them
        $.each(queries.pointInfo, function(key, value) {
            if (value-lastQueries.pointInfo[key] > 0) {
                queryKeys.push(key);
                queryValues.push(value-lastQueries.pointInfo[key]);
                sumTotal += value-lastQueries.pointInfo[key];
            }
        });
        var numQueries = queryKeys.length;
        var pointInfo = '<b>' + PMA_messages['strTotal'] + ': ' + sumTotal + '</b><br>';

        while(queryKeys.length > 0) {
            max = 0;
            for (var i = 0; i < queryKeys.length; i++) {
                if (queryValues[i] > max) {
                    max = queryValues[i];
                    maxIdx = i;
                }
            }
            if (numQueries > 8 && num >= 6) {
                sumOther += queryValues[maxIdx];
            } else {
                pointInfo += queryKeys[maxIdx].substr(4).replace('_', ' ') + ': ' + queryValues[maxIdx] + '<br>';
            }

            queryKeys.splice(maxIdx, 1);
            queryValues.splice(maxIdx, 1);
            num++;
        }

        if (sumOther>0) {
            pointInfo += PMA_messages['strOther'] + ': ' + sumOther;
        }

        return pointInfo;
    }
});


// Needs to be global as server_status_monitor.js uses it too
function serverResponseError() {
    var btns = {};
    btns[PMA_messages['strReloadPage']] = function() {
        window.location.reload();
    };
    $('#emptyDialog').dialog({title: PMA_messages['strRefreshFailed']});
    $('#emptyDialog').html(
        PMA_getImage('s_attention.png') +
        PMA_messages['strInvalidResponseExplanation']
    );
    $('#emptyDialog').dialog({ buttons: btns });
}
