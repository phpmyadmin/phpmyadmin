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
    $('.jsfeature').show();

    jQuery.tablesorter.addParser({
        id: "fancyNumber",
        is: function(s) {
            return /^[0-9]?[0-9,\.]*\s?(k|M|G|T|%)?$/.test(s);
        },
        format: function(s) {
            var num = jQuery.tablesorter.formatFloat(
                s.replace(PMA_messages['strThousandsSeperator'], '')
                 .replace(PMA_messages['strDecimalSeperator'], '.')
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

        $('.' + $link.attr('href').substr(1))
            .show()
            .offset({ top: $link.offset().top + $link.height() + 5, left: $link.offset().left })
            .addClass('openedPopup');

        return false;
    });

    $(document).click( function(event) {
        $('.openedPopup').each(function() {
            var $cnt = $(this);
            var pos = $(this).offset();

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

    /*** Table sort tooltip ***/
    PMA_createqTip($('table.sortable thead th'), PMA_messages['strSortHint']);

    // Tell highcarts not to use UTC dates (global setting)
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });

    $.ajaxSetup({
        cache: false
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

            // Load Server status monitor
            if (ui.tab.hash == '#statustabs_charting' && ! monitorLoaded) {
                $('div#statustabs_charting').append( //PMA_messages['strLoadingMonitor'] + ' ' +
                    '<img class="ajaxIcon" id="loadingMonitorIcon" src="' +
                    pmaThemeImage + 'ajax_clock_small.gif" alt="">'
                );
                // Delay loading a bit so the tab loads and the user gets to see a ajax loading icon
                setTimeout(function() {
                    loadJavascript(['js/jquery/timepicker.js', 'js/jquery/jquery.json-2.2.js',
                                    'js/jquery/jquery.sprintf.js', 'js/jquery/jquery.sortableTable.js',
                                    'js/codemirror/lib/codemirror.js', 'js/codemirror/mode/mysql/mysql.js',
                                    'js/server_status_monitor.js']);
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
    $('.buttonlinks select').change(function() {
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
    $('.buttonlinks a.tabRefresh').click(function() {
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

    // Live traffic charting
    $('.buttonlinks a.livetrafficLink').click(function() {
        // ui-tabs-panel class is added by the jquery tabs feature
        var $tab = $(this).parents('div.ui-tabs-panel');
        var tabstat = tabStatus[$tab.attr('id')];

        if (tabstat == 'static' || tabstat == 'liveconnections') {
            var settings = {
                series: [
                    { name: PMA_messages['strChartKBSent'], data: [] },
                    { name: PMA_messages['strChartKBReceived'], data: [] }
                ],
                title: { text: PMA_messages['strChartServerTraffic'] },
                realtime: { url: 'server_status.php?' + url_query,
                           type: 'traffic',
                           callback: function(chartObj, curVal, lastVal, numLoadedPoints) {
                               if (lastVal == null) {
                                   return;
                                }
                                chartObj.series[0].addPoint(
                                    { x: curVal.x, y: (curVal.y_sent - lastVal.y_sent) / 1024 },
                                    false,
                                    numLoadedPoints >= chartObj.options.realtime.numMaxPoints
                                );
                                chartObj.series[1].addPoint(
                                    { x: curVal.x, y: (curVal.y_received - lastVal.y_received) / 1024 },
                                    true,
                                    numLoadedPoints >= chartObj.options.realtime.numMaxPoints
                                );
                            },
                            error: function() { serverResponseError(); }
                         }
            };

            setupLiveChart($tab, this, settings);
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
    $('.buttonlinks a.liveconnectionsLink').click(function() {
        var $tab = $(this).parents('div.ui-tabs-panel');
        var tabstat = tabStatus[$tab.attr('id')];

        if (tabstat == 'static' || tabstat == 'livetraffic') {
            var settings = {
                series: [
                    { name: PMA_messages['strChartConnections'], data: [] },
                    { name: PMA_messages['strChartProcesses'], data: [] }
                ],
                title: { text: PMA_messages['strChartConnectionsTitle'] },
                realtime: { url: 'server_status.php?' + url_query,
                           type: 'proc',
                           callback: function(chartObj, curVal, lastVal, numLoadedPoints) {
                                if (lastVal == null) {
                                    return;
                                }
                                chartObj.series[0].addPoint(
                                    { x: curVal.x, y: curVal.y_conn - lastVal.y_conn },
                                    false,
                                    numLoadedPoints >= chartObj.options.realtime.numMaxPoints
                                );
                                chartObj.series[1].addPoint(
                                    { x: curVal.x, y: curVal.y_proc },
                                    true,
                                    numLoadedPoints >= chartObj.options.realtime.numMaxPoints
                                );
                            },
                            error: function() { serverResponseError(); }
                         }
            };

            setupLiveChart($tab, this, settings);
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
    $('.buttonlinks a.livequeriesLink').click(function() {
        var $tab = $(this).parents('div.ui-tabs-panel');
        var settings = null;

        if (tabStatus[$tab.attr('id')] == 'static') {
            settings = {
                series: [ { name: PMA_messages['strChartIssuedQueries'], data: [] } ],
                title: { text: PMA_messages['strChartIssuedQueriesTitle'] },
                tooltip: { formatter: function() { return this.point.name; } },
                realtime: { url: 'server_status.php?' + url_query,
                          type: 'queries',
                          callback: function(chartObj, curVal, lastVal, numLoadedPoints) {
                                if (lastVal == null) { return; }
                                chartObj.series[0].addPoint({
                                        x: curVal.x,
                                        y: curVal.y - lastVal.y,
                                        name: sortedQueriesPointInfo(curVal, lastVal)
                                    },
                                    true,
                                    numLoadedPoints >= chartObj.options.realtime.numMaxPoints
                                );
                            },
                            error: function() { serverResponseError(); }
                         }
            };
        } else {
            $(this).html(PMA_messages['strLiveQueryChart']);
        }

        setupLiveChart($tab, this, settings);
        tabStatus[$tab.attr('id')] = 'livequeries';
        return false;
    });

    function setupLiveChart($tab, link, settings) {
        if (settings != null) {
            // Loading a chart with existing chart => remove old chart first
            if (tabStatus[$tab.attr('id')] != 'static') {
                clearTimeout(chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"]);
                chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"] = null;
                tabChart[$tab.attr('id')].destroy();
                // Also reset the select list
                $tab.find('.buttonlinks select').get(0).selectedIndex = 2;
            }

            if (! settings.chart) settings.chart = {};
            settings.chart.renderTo = $tab.attr('id') + "_chart_cnt";

            $tab.find('.tabInnerContent')
                .hide()
                .after('<div class="liveChart" id="' + $tab.attr('id') + '_chart_cnt"></div>');
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
            tabChart[$tab.attr('id')].destroy();
            $tab.find('.buttonlinks a.tabRefresh').show();
            $tab.find('.buttonlinks select').get(0).selectedIndex = 2;
            $tab.find('.buttonlinks .refreshList').hide();
        }
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
        }
        else textFilter = new RegExp("(^| )" + word, 'i');

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
                PMA_convertFootnotesToTooltips();
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

                queryPieChart = PMA_createChart({
                    chart: {
                        renderTo: 'serverstatusquerieschart'
                    },
                    title: {
                        text: '',
                        margin: 0
                    },
                    series: [{
                        type: 'pie',
                        name: PMA_messages['strChartQueryPie'],
                        data: cdata
                    }],
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                formatter: function() {
                                    return '<b>' + this.point.name +'</b><br/> ' + 
                                            Highcharts.numberFormat(this.percentage, 2) + ' %';
                               }
                            }
                        }
                    },
                    tooltip: {
                        formatter: function() {
                            return '<b>' + this.point.name + '</b><br/>' + 
                                    Highcharts.numberFormat(this.y, 2) + '<br/>(' + 
                                    Highcharts.numberFormat(this.percentage, 2) + ' %)';
                        }
                    }
                });
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
                        $('div#emptyDialog').attr('title', PMA_messages['strRuleDetails']);
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
    $('#emptyDialog').attr('title', PMA_messages['strRefreshFailed']);
    $('#emptyDialog').html(
        PMA_getImage('s_attention.png') +
        PMA_messages['strInvalidResponseExplanation']
    );
    $('#emptyDialog').dialog({ buttons: btns });
}
