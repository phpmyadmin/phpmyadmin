/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used in server status pages
 * @name            Server Status
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    jQueryCookie
 * @requires    jQueryTablesorter
 * @requires    Highcharts
 * @requires    canvg
 * @requires    js/functions.js
 *
 */

// Add a tablesorter parser to properly handle thousands seperated numbers and SI prefixes
$(function() {
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

$(function() {
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

    // Tell highcarts not to use UTC dates (global setting)
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });

    // Add tabs
    $('#serverStatusTabs').tabs({
        // Tab persistence
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        show: function(event, ui) {
            // Fixes line break in the menu bar when the page overflows and scrollbar appears
            menuResize();

            // Initialize selected tab
            if (!$(ui.tab.hash).data('init-done')) {
                initTab($(ui.tab.hash), null);
            }
            // Replot on tab switching
            if (ui.tab.hash == '#statustabs_traffic' && tabChart['statustabs_traffic'] != null) {
                recursiveTimer($('#statustabs_traffic'), "traffic");
            }
            else if (ui.tab.hash == '#statustabs_queries' && tabChart['statustabs_queries'] != null) {
                recursiveTimer($('#statustabs_queries'), "queries");
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
                        'js/jquery/timepicker.js',
                        'js/jquery/jquery.json-2.2.js',
                        'js/jquery/jquery.sortableTable.js'];
                    scripts.push('js/server_status_monitor.js');
                    loadJavascript(scripts);
                }, 50);

                monitorLoaded = true;
            }

            // Run the advisor immediately when the user clicks the tab, but only when this is the first time
            if (ui.tab.hash == '#statustabs_advisor' && $('table#rulesFired').length == 0) {
                // Start with a small delay because the click event hasn't been setup yet
                setTimeout(function() {
                    $('a[href="#startAnalyzer"]').trigger('click');
                }, 25);
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

    // Handles refresh rate changing
    $('.buttonlinks .refreshRate').change(function() {

        var $tab = $(this).parents('div.ui-tabs-panel');
        clearTimeout(chart_replot_timers[$tab.attr('id')]);
        var tabstat = tabStatus[$tab.attr('id')];

        if(tabstat == 'livequeries') {
            recursiveTimer($tab, 'queries');
        } else if(tabstat == 'livetraffic') {
            recursiveTimer($tab, 'traffic');
        } else if(tabstat == 'liveconnections') {
            recursiveTimer($tab, 'proc');
        }

        var chart = tabChart[$(this).parents('div.ui-tabs-panel').attr('id')];
        // Clear current timeout and set timeout with the new refresh rate
        clearTimeout(chart_activeTimeouts[chart.options.chart.renderTo]);
        if (chart.options.realtime.postRequest) {
            chart.options.realtime.postRequest.abort();
        }

        chart.options.realtime.refreshRate = 1000*parseInt(this.value);

        chart.xAxis[0].setExtremes(
            new Date().getTime() - server_time_diff - chart.options.realtime.numMaxPoints * chart.options.realtime.refreshRate,
            new Date().getTime() - server_time_diff,
            true
        );

        chart_activeTimeouts[chart.options.chart.renderTo] = setTimeout(
            chart.options.realtime.timeoutCallBack,
            chart.options.realtime.refreshRate
        );
    });

    // Ajax refresh of variables (always the first element in each tab)
    $('div.buttonlinks a.tabRefresh').click(function() {
        // ui-tabs-panel class is added by the jquery tabs feature
        var tab = $(this).parents('div.ui-tabs-panel');
        var that = this;

        // Show ajax load icon
        $(this).find('img').show();

        $.get($(this).attr('href'), { ajax_request: 1 }, function(data) {
            $(that).find('img').hide();
            initTab(tab, data);
        });

        tabStatus[tab.attr('id')] = 'data';

        return false;
    });


    /** Realtime charting of variables **/

    // variables to hold previous y data value to calculate difference
    var previous_y_line1 = new Object();
    var previous_y_line2 = new Object();
    var series = new Object();

    // Live traffic charting
    $('div.buttonlinks a.livetrafficLink').click(function() {
        // ui-tabs-panel class is added by the jquery tabs feature
        var $tab = $(this).parents('div.ui-tabs-panel');
        var tabstat = tabStatus[$tab.attr('id')];

        if (tabstat == 'static' || tabstat == 'liveconnections') {
            
            setupLiveChart($tab, this, getSettings('traffic'));
            var set_previous = getCurrentDataSet($tab, 'traffic');
            tabChart[$tab.attr('id')] = $.jqplot($tab.attr('id') + '_chart_cnt', [[[0,0]],[[0,0]]], getSettings('traffic'));
            recursiveTimer($tab, 'traffic');

            if (tabstat == 'liveconnections') {
                $tab.find('.buttonlinks a.liveconnectionsLink').html(PMA_messages['strLiveConnChart']);
            }
            tabStatus[$tab.attr('id')] = 'livetraffic';
        } else {
            $(this).html(PMA_messages['strLiveTrafficChart']);
            setupLiveChart($tab, this, null);
        }

        return false;
    });

    // Live connection/process charting
    $('div.buttonlinks a.liveconnectionsLink').click(function() {
        var $tab = $(this).parents('div.ui-tabs-panel');
        var tabstat = tabStatus[$tab.attr('id')];

        if (tabstat == 'static' || tabstat == 'livetraffic') {

            setupLiveChart($tab, this, getSettings('proc'));
            var set_previous = getCurrentDataSet($tab, 'proc');
            tabChart[$tab.attr('id')] = $.jqplot($tab.attr('id') + '_chart_cnt', [[[0,0]],[[0,0]]], getSettings('proc'));
            recursiveTimer($tab, 'proc');
            if (tabstat == 'livetraffic') {
                $tab.find('.buttonlinks a.livetrafficLink').html(PMA_messages['strLiveTrafficChart']);
            }
            tabStatus[$tab.attr('id')] = 'liveconnections';
        } else {
            $(this).html(PMA_messages['strLiveConnChart']);
            setupLiveChart($tab, this, null);
        }

        return false;
    });

    // Live query statistics
    $('div.buttonlinks a.livequeriesLink').click(function() {
        var $tab = $(this).parents('div.ui-tabs-panel');
        if (tabStatus[$tab.attr('id')] == 'static') {

            setupLiveChart($tab, this, getSettings('queries'));
            var set_previous = getCurrentDataSet($tab, 'queries');
            tabChart[$tab.attr('id')] = $.jqplot($tab.attr('id') + '_chart_cnt', [[0,0]], getSettings('queries'));
            recursiveTimer($tab, 'queries');
            tabStatus[$tab.attr('id')] = 'livequeries';

        } else {
            $(this).html(PMA_messages['strLiveQueryChart']);
            setupLiveChart($tab, this, null);
        }
        return false;
    });

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
                'token' : window.parent.token,
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
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    tickOptions: {
                        formatString: '%H:%M:%S'
                    }
                },
                yaxis: {
                    autoscale:true,
                    label: PMA_messages['strTotalCount'],
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
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
                yoffset: 12,        // pixel offset of the legend box from the y (or y2) axis.
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

    function setupLiveChart($tab, link, settings) {
        if (settings != null) {
            // Loading a chart with existing chart => remove old chart first
            if (tabStatus[$tab.attr('id')] != 'static') {
                clearTimeout(chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"]);
                chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"] = null;
                delete tabChart[$tab.attr('id')];
                // Also reset the select list
                $tab.find('.buttonlinks select').get(0).selectedIndex = 2;
            }

            if (! settings.chart) settings.chart = {};
            settings.chart.renderTo = $tab.attr('id') + "_chart_cnt";

            if($('#' + $tab.attr('id') + '_chart_cnt').length == 0) {
                $tab.find('.tabInnerContent')
                    .hide()
                    .after('<div class="liveChart" id="' + $tab.attr('id') + '_chart_cnt"></div>');
            }
            tabChart[$tab.attr('id')] = PMA_createChart(settings);
            $(link).html(PMA_messages['strStaticData']);
            $tab.find('.buttonlinks a.tabRefresh').hide();
            $tab.find('.buttonlinks .refreshList').show();
        } else {
            clearTimeout(chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"]);
            chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"] = null;
            $tab.find('.tabInnerContent').show();
            $tab.find('div#' + $tab.attr('id') + '_chart_cnt').remove();
            tabStatus[$tab.attr('id')] = 'static';
            delete tabChart[$tab.attr('id')];
            $tab.find('.buttonlinks a.tabRefresh').show();
            $tab.find('.buttonlinks select').get(0).selectedIndex = 2;
            $tab.find('.buttonlinks .refreshList').hide();
        }
        clearTimeout(chart_replot_timers[$tab.attr('id')]);
        previous_y_line1[$tab.attr('id')] = 0;
        previous_y_line2[$tab.attr('id')] = 0;
        series[$tab.attr('id')] = new Array();
        series[$tab.attr('id')][0] = new Array();
        series[$tab.attr('id')][1] = new Array();
    }

    /* 3 Filtering functions */
    $('#filterAlert').change(function() {
        alertFilter = this.checked;
        filterVariables();
    });

    $('#filterText').keyup(function(e) {
        var word = $(this).val().replace(/_/g, ' ');

        if (word.length == 0) {
            textFilter = null;
        } else {
            textFilter = new RegExp("(^| )" + word, 'i');
        }

        text = word;

        filterVariables();
    });

    $('#filterCategory').change(function() {
        categoryFilter = $(this).val();
        filterVariables();
    });

    $('input#dontFormat').change(function() {
        // Hiding the table while changing values speeds up the process a lot
        $('#serverstatusvariables').hide();
        $('#serverstatusvariables td.value span.original').toggle(this.checked);
        $('#serverstatusvariables td.value span.formatted').toggle(! this.checked);
        $('#serverstatusvariables').show();
    });

    /* Adjust DOM / Add handlers to the tabs */
    function initTab(tab, data) {
        if ($(tab).data('init-done') && !data) {
            return;
        }
        $(tab).data('init-done', true);
        switch(tab.attr('id')) {
            case 'statustabs_traffic':
                if (data != null) {
                    tab.find('.tabInnerContent').html(data);
                }
                PMA_showHints();
                break;
            case 'statustabs_queries':
                if (data != null) {
                    queryPieChart.destroy();
                    tab.find('.tabInnerContent').html(data);
                }

                // Build query statistics chart
                var cdata = new Array();
                $.each(jQuery.parseJSON($('#serverstatusquerieschart span').html()), function(key, value) {
                    cdata.push([key, parseInt(value)]);
                });

                queryPieChart = PMA_createProfilingChartJqplot(
                    'serverstatusquerieschart', 
                    cdata
                );

                initTableSorter(tab.attr('id'));
                break;

            case 'statustabs_allvars':
                if (data != null) {
                    tab.find('.tabInnerContent').html(data);
                    filterVariables();
                }
                initTableSorter(tab.attr('id'));
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

    /* Filters the status variables by name/category/alert in the variables tab */
    function filterVariables() {
        var useful_links = 0;
        var section = text;

        if (categoryFilter.length > 0) {
            section = categoryFilter;
        }

        if (section.length > 1) {
            $('#linkSuggestions span').each(function() {
                if ($(this).attr('class').indexOf('status_' + section) != -1) {
                    useful_links++;
                    $(this).css('display', '');
                } else {
                    $(this).css('display', 'none');
                }


            });
        }

        if (useful_links > 0) {
            $('#linkSuggestions').css('display', '');
        } else {
            $('#linkSuggestions').css('display', 'none');
        }

        odd_row = false;
        $('#serverstatusvariables th.name').each(function() {
            if ((textFilter == null || textFilter.exec($(this).text()))
                && (! alertFilter || $(this).next().find('span.attention').length>0)
                && (categoryFilter.length == 0 || $(this).parent().hasClass('s_' + categoryFilter))
            ) {
                odd_row = ! odd_row;
                $(this).parent().css('display', '');
                if (odd_row) {
                    $(this).parent().addClass('odd');
                    $(this).parent().removeClass('even');
                } else {
                    $(this).parent().addClass('even');
                    $(this).parent().removeClass('odd');
                }
            } else {
                $(this).parent().css('display', 'none');
            }
        });
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

    /**** Server config advisor ****/

    $('a[href="#openAdvisorInstructions"]').click(function() {
        var dlgBtns = {};

        dlgBtns[PMA_messages['strClose']] = function() {
            $(this).dialog('close');
        };

        $('#advisorInstructionsDialog').attr('title', PMA_messages['strAdvisorSystem']);
        $('#advisorInstructionsDialog').dialog({
            width: 700,
            buttons: dlgBtns
        });
    });

    $('a[href="#startAnalyzer"]').click(function() {
        var $cnt = $('#statustabs_advisor .tabInnerContent');
        $cnt.html('<img class="ajaxIcon" src="' + pmaThemeImage + 'ajax_clock_small.gif" alt="">');

        $.get('server_status.php?' + url_query, { ajax_request: true, advisor: true }, function(data) {
            var $tbody, $tr, str, even = true;

            data = $.parseJSON(data);

            $cnt.html('');

            if (data.parse.errors.length > 0) {
                $cnt.append('<b>Rules file not well formed, following errors were found:</b><br />- ');
                $cnt.append(data.parse.errors.join('<br/>- '));
                $cnt.append('<p></p>');
            }

            if (data.run.errors.length > 0) {
                $cnt.append('<b>Errors occured while executing rule expressions:</b><br />- ');
                $cnt.append(data.run.errors.join('<br/>- '));
                $cnt.append('<p></p>');
            }

            if (data.run.fired.length > 0) {
                $cnt.append('<p><b>' + PMA_messages['strPerformanceIssues'] + '</b></p>');
                $cnt.append('<table class="data" id="rulesFired" border="0"><thead><tr>' +
                            '<th>' + PMA_messages['strIssuse'] + '</th><th>' + PMA_messages['strRecommendation'] +
                            '</th></tr></thead><tbody></tbody></table>');
                $tbody = $cnt.find('table#rulesFired');

                var rc_stripped;

                $.each(data.run.fired, function(key, value) {
                    // recommendation may contain links, don't show those in overview table (clicking on them redirects the user)
                    rc_stripped = $.trim($('<div>').html(value.recommendation).text());
                    $tbody.append($tr = $('<tr class="linkElem noclick ' + (even ? 'even' : 'odd') + '"><td>' +
                                            value.issue + '</td><td>' + rc_stripped + ' </td></tr>'));
                    even = !even;
                    $tr.data('rule', value);

                    $tr.click(function() {
                        var rule = $(this).data('rule');
                        $('div#emptyDialog').dialog({title: PMA_messages['strRuleDetails']});
                        $('div#emptyDialog').html(
                            '<p><b>' + PMA_messages['strIssuse'] + ':</b><br />' + rule.issue + '</p>' +
                            '<p><b>' + PMA_messages['strRecommendation'] + ':</b><br />' + rule.recommendation + '</p>' +
                            '<p><b>' + PMA_messages['strJustification'] + ':</b><br />' + rule.justification + '</p>' +
                            '<p><b>' + PMA_messages['strFormula'] + ':</b><br />' + rule.formula + '</p>' +
                            '<p><b>' + PMA_messages['strTest'] + ':</b><br />' + rule.test + '</p>'
                        );

                        var dlgBtns = {};
                        dlgBtns[PMA_messages['strClose']] = function() {
                            $(this).dialog('close');
                        };

                        $('div#emptyDialog').dialog({ width: 600, buttons: dlgBtns });
                    });
                });
            }
        });

        return false;
    });
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
