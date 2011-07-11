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
    jQuery.tablesorter.addParser({
        id: "fancyNumber",
        is: function(s) {
            return /^[0-9]?[0-9,\.]*\s?(k|M|G|T|%)?$/.test(s);
        },
        format: function(s) {
            var num = jQuery.tablesorter.formatFloat( 
                s.replace(PMA_messages['strThousandsSeperator'],'')
                 .replace(PMA_messages['strDecimalSeperator'],'.') 
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
            
            return num * Math.pow(10,factor);
        },
        type: "numeric"
    });

    
    // Popup behaviour
    $('a[rel="popupLink"]').click( function() {
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
            if(event.pageX < pos.left || event.pageY < pos.top || event.pageX > pos.left + $cnt.outerWidth() || event.pageY > pos.top + $cnt.outerHeight())
                $cnt.hide();
        });
    });
});
    
$(function() {
    // Filters for status variables
    var textFilter=null;
    var alertFilter = false;
    var categoryFilter='';
    var odd_row=false;
    var text=''; // Holds filter text
    var queryPieChart = null;
    
    /* Chart configuration */
    // Defines what the tabs are currently displaying (realtime or data)
    var tabStatus = new Object();
    // Holds the current chart instances for each tab
    var tabChart = new Object();
    
    
    /*** Table sort tooltip ***/
    
    var $tableSortHint = $('<div class="dHint" style="display:none;">' + 'Click to sort' + '</div>');
    $('body').append($tableSortHint);
    
    $('table.sortable thead th').live('mouseover mouseout',function(e) {
        if(e.type == 'mouseover') {
            $tableSortHint
                .stop(true, true)
                .css({
                    top: e.clientY + 15,
                    left: e.clientX + 15
                })
                .show('fast')
                .data('shown',true);
        } else {
            $tableSortHint
                .stop(true, true)
                .hide(300,function() {
                    $(this).data('shown',false);
                });
        }
    });
    
    $(document).mousemove(function(e) {
        if($tableSortHint.data('shown') == true)
            $tableSortHint.css({
                top: e.clientY + 15,
                left: e.clientX + 15
            })
    });
    
    
    // Tell highcarts not to use UTC dates (global setting)    
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });

    $.ajaxSetup({
        cache:false
    });
       
    // Add tabs
    $('#serverStatusTabs').tabs({
        // Tab persistence
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        show: function() { menuResize(); }
    });
    
    // Fixes wrong tab height with floated elements. See also http://bugs.jqueryui.com/ticket/5601
    $(".ui-widget-content:not(.ui-tabs):not(.ui-helper-clearfix)").addClass("ui-helper-clearfix");
    
    // Initialize each tab
    $('div.ui-tabs-panel').each(function() { 
        initTab($(this),null); 
        tabStatus[$(this).attr('id')] = 'static';
    });
    
    // Display button links
    $('div.buttonlinks').show();
    
    // Handles refresh rate changing
    $('.buttonlinks select').change(function() {
        var chart=tabChart[$(this).parents('div.ui-tabs-panel').attr('id')];

        // Clear current timeout and set timeout with the new refresh rate
        clearTimeout(chart_activeTimeouts[chart.options.chart.renderTo]);
        if(chart.options.realtime.postRequest)
            chart.options.realtime.postRequest.abort();
        
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
        var tab=$(this).parents('div.ui-tabs-panel');
        var that = this;
        
        // Show ajax load icon
        $(this).find('img').show();

        $.get($(this).attr('href'),{ajax_request:1},function(data) {
            $(that).find('img').hide();
            initTab(tab,data);
        });
        
        tabStatus[tab.attr('id')]='data';
        
        return false;
    });
    
    
    /** Realtime charting of variables **/
    
    // Live traffic charting
    $('.buttonlinks a.livetrafficLink').click(function() {
        // ui-tabs-panel class is added by the jquery tabs feature
        var $tab=$(this).parents('div.ui-tabs-panel');
        var tabstat = tabStatus[$tab.attr('id')];
        
        if(tabstat=='static' || tabstat=='liveconnections') {
            var settings = {
                series: [
                    { name: PMA_messages['strChartKBSent'], data: [] },
                    { name: PMA_messages['strChartKBReceived'], data: [] }
                ],
                title: { text: PMA_messages['strChartServerTraffic'] },
                realtime: { url:'server_status.php?' + url_query,
                           type: 'traffic',
                           callback: function(chartObj, curVal, lastVal, numLoadedPoints) {
                               if(lastVal==null) return;
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
                            }
                         }
            }
            
            setupLiveChart($tab,this,settings);
            if(tabstat == 'liveconnections') 
                $tab.find('.buttonlinks a.liveconnectionsLink').html(PMA_messages['strLiveConnChart']);
            tabStatus[$tab.attr('id')]='livetraffic';
        } else {
            $(this).html(PMA_messages['strLiveTrafficChart']);
            setupLiveChart($tab,this,null);
        }
        
        return false;
    });
    
    // Live connection/process charting
    $('.buttonlinks a.liveconnectionsLink').click(function() {
        var $tab=$(this).parents('div.ui-tabs-panel');
        var tabstat = tabStatus[$tab.attr('id')];
        
        if(tabstat == 'static' || tabstat == 'livetraffic') {
            var settings = {
                series: [
                    { name: PMA_messages['strChartConnections'], data: [] },
                    { name: PMA_messages['strChartProcesses'], data: [] }
                ],
                title: { text: PMA_messages['strChartConnectionsTitle'] },
                realtime: { url:'server_status.php?'+url_query,
                           type: 'proc',
                           callback: function(chartObj, curVal, lastVal,numLoadedPoints) {
                               if(lastVal==null) return;
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
                            }
                         }
            };
            
            setupLiveChart($tab,this,settings);
            if(tabstat == 'livetraffic') 
                $tab.find('.buttonlinks a.livetrafficLink').html(PMA_messages['strLiveTrafficChart']);
            tabStatus[$tab.attr('id')]='liveconnections';
        } else {
            $(this).html(PMA_messages['strLiveConnChart']);
            setupLiveChart($tab,this,null);
        }
        
        return false;
    });

    // Live query statistics
    $('.buttonlinks a.livequeriesLink').click(function() {
        var $tab = $(this).parents('div.ui-tabs-panel');
        var settings = null; 
        
        if(tabStatus[$tab.attr('id')] == 'static') {
            settings = {
                series: [ { name: PMA_messages['strChartIssuedQueries'], data: [] } ],
                title: { text: PMA_messages['strChartIssuedQueriesTitle'] },
                tooltip: { formatter:function() { return this.point.name; } },
                realtime: { url:'server_status.php?'+url_query,
                          type: 'queries',
                          callback: function(chartObj, curVal, lastVal,numLoadedPoints) {
                                if(lastVal == null) return;
                                chartObj.series[0].addPoint(
                                    { x: curVal.x,  y: curVal.y - lastVal.y, name: sortedQueriesPointInfo(curVal,lastVal) },
                                    true, 
                                    numLoadedPoints >= chartObj.options.realtime.numMaxPoints
                                );
                            }
                         }
            };
        } else {
            $(this).html(PMA_messages['strLiveQueryChart']);
        }

        setupLiveChart($tab,this,settings);
        tabStatus[$tab.attr('id')] = 'livequeries';
        return false; 
    });
    
    function setupLiveChart($tab,link,settings) {
        if(settings != null) {
            // Loading a chart with existing chart => remove old chart first
            if(tabStatus[$tab.attr('id')] != 'static') {
                clearTimeout(chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"]);
                chart_activeTimeouts[$tab.attr('id')+"_chart_cnt"] = null;
                tabChart[$tab.attr('id')].destroy();
                // Also reset the select list
                $tab.find('.buttonlinks select').get(0).selectedIndex = 2;
            }

            if(! settings.chart) settings.chart = {};
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
            chart_activeTimeouts[$tab.attr('id') + "_chart_cnt"]=null;
            $tab.find('.tabInnerContent').show();
            $tab.find('div#'+$tab.attr('id') + '_chart_cnt').remove();
            tabStatus[$tab.attr('id')]='static';
            tabChart[$tab.attr('id')].destroy();
            $tab.find('.buttonlinks a.tabRefresh').show();
            $tab.find('.buttonlinks select').get(0).selectedIndex=2;
            $tab.find('.buttonlinks .refreshList').hide();
        }
    }

    /* 3 Filtering functions */
    $('#filterAlert').change(function() {
        alertFilter = this.checked;
        filterVariables();
    });
    
    $('#filterText').keyup(function(e) {
        word = $(this).val().replace('_',' ');
        
        if(word.length == 0) textFilter = null;
        else textFilter = new RegExp("(^|_)" + word,'i');
        
        text = word;
        
        filterVariables();
    });
    
    $('#filterCategory').change(function() {
        categoryFilter = $(this).val();
        filterVariables();
    });
    
    /* Adjust DOM / Add handlers to the tabs */
    function initTab(tab,data) {
        switch(tab.attr('id')) {
            case 'statustabs_traffic':
                if(data != null) tab.find('.tabInnerContent').html(data);
                initTooltips();
                break;
            case 'statustabs_queries':
                if(data != null) {
                    queryPieChart.destroy();
                    tab.find('.tabInnerContent').html(data);
                }

                // Build query statistics chart
                var cdata = new Array();
                $.each(jQuery.parseJSON($('#serverstatusquerieschart span').html()),function(key,value) {
                    cdata.push([key,parseInt(value)]);
                });
                
                queryPieChart = PMA_createChart({
                    chart: {
                        renderTo: 'serverstatusquerieschart'
                    },
                    title: {
                        text:'',
                        margin:0
                    },
                    series: [{
                        type:'pie',
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
                                    return '<b>'+ this.point.name +'</b><br/> ' + Highcharts.numberFormat(this.percentage, 2) + ' %';
                               }
                            }
                        }
                    },		
                    tooltip: {
                        formatter: function() { 
                            return '<b>' + this.point.name + '</b><br/>' + Highcharts.numberFormat(this.y, 2) + '<br/>(' + Highcharts.numberFormat(this.percentage, 2) + ' %)'; 
                        }
                    }
                });
                break;
                
            case 'statustabs_allvars':
                if(data != null) {
                    tab.find('.tabInnerContent').html(data);
                    filterVariables();
                }
                break;
        }
        
        initTableSorter(tab.attr('id'));        
    }
    
    function initTableSorter(tabid) {
        switch(tabid) {
            case 'statustabs_queries':
                $('#serverstatusqueriesdetails').tablesorter({
                        sortList: [[3,1]],
                        widgets: ['zebra'],
                        headers: {
                            1: { sorter: 'fancyNumber' },
                            2: { sorter: 'fancyNumber' }
                        }
                    });
                    
                $('#serverstatusqueriesdetails tr:first th')
                    .append('<img class="sortableIcon" src="' + pmaThemeImage + 'cleardot.gif" alt="">');
                    
                break;
            
            case 'statustabs_allvars':
                $('#serverstatusvariables').tablesorter({
                        sortList: [[0,0]],
                        widgets: ['zebra'],
                        headers: {
                            1: { sorter: 'fancyNumber' }
                        }
                    });
                    
                $('#serverstatusvariables tr:first th')
                    .append('<img class="sortableIcon" src="' + pmaThemeImage + 'cleardot.gif" alt="">');
                    
                break;
        }
    }
    
    /* Filters the status variables by name/category/alert in the variables tab */
    function filterVariables() {
        var useful_links = 0;
        var section = text;
        
        if(categoryFilter.length > 0) section = categoryFilter;
        
        if(section.length > 1) {
            $('#linkSuggestions span').each(function() {
                if($(this).attr('class').indexOf('status_'+section) != -1) {
                    useful_links++;
                    $(this).css('display','');
                } else {
                    $(this).css('display','none');
                }
                
                
            });
        }
        
        if(useful_links > 0) 
            $('#linkSuggestions').css('display','');
        else $('#linkSuggestions').css('display','none');
        
        odd_row=false;
        $('#serverstatusvariables th.name').each(function() {
            if((textFilter == null || textFilter.exec($(this).text()))
                && (! alertFilter || $(this).next().find('span.attention').length>0)
                && (categoryFilter.length == 0 || $(this).parent().hasClass('s_'+categoryFilter))) {
                odd_row = ! odd_row;                    
                $(this).parent().css('display','');
                if(odd_row) {
                    $(this).parent().addClass('odd');
                    $(this).parent().removeClass('even');
                } else {
                    $(this).parent().addClass('even');
                    $(this).parent().removeClass('odd');
                }
            } else {
                $(this).parent().css('display','none');
            }
        });
    }
    
    // Provides a nicely formatted and sorted tooltip of each datapoint of the query statistics
    function sortedQueriesPointInfo(queries, lastQueries){
        var max, maxIdx, num=0;
        var queryKeys = new Array();
        var queryValues = new Array();
        var sumOther=0;
        var sumTotal=0;
        
        // Separate keys and values, then  sort them
        $.each(queries.pointInfo, function(key,value) {
            if(value-lastQueries.pointInfo[key] > 0) {
                queryKeys.push(key);
                queryValues.push(value-lastQueries.pointInfo[key]);
                sumTotal += value-lastQueries.pointInfo[key];
            }
        });
        var numQueries = queryKeys.length;
        var pointInfo = '<b>' + PMA_messages['strTotal'] + ': ' + sumTotal + '</b><br>';
        
        while(queryKeys.length > 0) {
            max = 0;
            for(var i=0; i < queryKeys.length; i++) {
                if(queryValues[i] > max) {
                    max = queryValues[i];
                    maxIdx = i;
                }
            }
            if(numQueries > 8 && num >= 6)
                sumOther += queryValues[maxIdx];
            else pointInfo += queryKeys[maxIdx].substr(4).replace('_',' ') + ': ' + queryValues[maxIdx] + '<br>'; 
            
            queryKeys.splice(maxIdx,1);
            queryValues.splice(maxIdx,1);
            num++;
        }
        
        if(sumOther>0) 
            pointInfo += PMA_messages['strOther'] + ': ' + sumOther;

        return pointInfo;
    }

    
    
    
    /**** Monitor charting implementation ****/
    /* Holds all charts */
    var chartGrid = null;
    /* Object that contains a list of required nodes that need to be retrieved from the server for chart updates */
    var requiredData = [];
    /* Saves the previous ajax response for differential values */
    var oldChartData = null;
    /* Stores the timeout handler so it can be cleared */
    var refreshTimeout = null;
    // Holding about to created chart 
    var newChart = null;
    // Chart auto increment
    var chartAI = 0;
    // To play/pause the monitor
    var redrawCharts = false;
    // To cancel the automatic refresh when gridRefresh has changed
    var gridTimeoutCallBack = null;
    // Holds displayed timespan of all charts
    var xmin=-1, xmax=-1;
    
    var monitorSettings = null;
    
    var defaultMonitorSettings = {
        chartSize: { width: 295, height: 250 },
        // Max points in each chart
        gridMaxPoints: 20,
        /* Refresh rate of all grid charts in ms */
        gridRefresh: 5000
    }
    
    // Allows drag and drop rearrange and print/edit icons on charts
    var editMode = false;
    
    var presetCharts = {
        'cpu-WINNT': {
            title: PMA_messages['strSystemCPUUsage'],
            nodes: [{ dataType: 'cpu', name: 'loadavg', unit: '%'}]
        },
        'memory-WINNT': {
            title: PMA_messages['strSystemMemory'],
            nodes: [
                { dataType: 'memory', name: 'MemTotal', valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
                { dataType: 'memory', name: 'MemUsed', valueDivisor: 1024, unit: PMA_messages['strMiB']  }, 
            ]
        },
        'swap-WINNT': {
            title: PMA_messages['strSystemSwap'],
            nodes: [
                { dataType: 'memory', name: 'SwapTotal', valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
                { dataType: 'memory', name: 'SwapUsed', valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
            ]
        },
        'cpu-Linux': {
            title: PMA_messages['strSystemCPUUsage'],
            nodes: [
                { dataType: 'cpu', 
                  name: PMA_messages['strAverageLoad'], 
                  unit: '%',
                  transformFn: function(cur, prev) {
                      if(prev == null) return undefined;
                      var diff_total = cur.busy + cur.idle - (prev.busy + prev.idle);
                      var diff_idle = cur.idle - prev.idle;
                      return 100*(diff_total - diff_idle) / diff_total;
                  }
                }
            ]
        },
        'memory-Linux': {
            title: PMA_messages['strSystemMemory'],
            nodes: [
                { dataType: 'memory', name: 'MemUsed', valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
                { dataType: 'memory', name: 'Cached',  valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
                { dataType: 'memory', name: 'Buffers', valueDivisor: 1024, unit: PMA_messages['strMiB'] },
                { dataType: 'memory', name: 'MemFree', valueDivisor: 1024, unit: PMA_messages['strMiB'] },
            ],
            settings: {
                chart: {
                    type: 'area',
                    animation: false
                },
                plotOptions: {
                    area: {
                        stacking: 'percent'
                    }
                }
            }
        },
        'swap-Linux': {
            title: PMA_messages['strSystemSwap'],
            nodes: [
                { dataType: 'memory', name: 'SwapUsed',   valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
                { dataType: 'memory', name: 'SwapCached', valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
                { dataType: 'memory', name: 'SwapFree',   valueDivisor: 1024, unit: PMA_messages['strMiB'] }, 
            ],
            settings: {
                chart: {
                    type: 'area',
                    animation: false
                },
                plotOptions: {
                    area: {
                        stacking: 'percent'
                    }
                }
            }
        }
    }
    
    // Default setting
    defaultChartGrid = {
        'c0': {  title: PMA_messages['strQuestions'],
                 nodes: [{ dataType: 'statusvar', name: 'Questions', display: 'differential' }]
              },
         'c1': {
                 title: PMA_messages['strChartConnectionsTitle'],
                 nodes: [ { dataType: 'statusvar', name: 'Connections', display: 'differential' },
                          { dataType: 'proc', name: 'Processes'} ]
               },
         'c2': {
                 title: PMA_messages['strTraffic'],
                 nodes: [
                    { dataType: 'statusvar', name: 'Bytes_sent', display: 'differential', valueDivisor: 1024, unit: PMA_messages['strKiB'] },
                    { dataType: 'statusvar', name: 'Bytes_received', display: 'differential', valueDivisor: 1024, unit: PMA_messages['strKiB'] }
                 ]
         }
    };
    
    // Server is localhost => We can add cpu/memory/swap 
    if(server_db_isLocal) {
        defaultChartGrid['c3'] = presetCharts['cpu-' + server_os];
        defaultChartGrid['c4'] = presetCharts['memory-' + server_os];
        defaultChartGrid['c5'] = presetCharts['swap-' + server_os];
    }
    
    var gridbuttons = { 
        cogButton: {
            //enabled: true,
            symbol: 'url(' + pmaThemeImage  + 's_cog.png)',
            x: -36,
            symbolFill: '#B5C9DF',
            hoverSymbolFill: '#779ABF',
            _titleKey: 'settings',
            menuName: 'gridsettings',
            menuItems: [{
                textKey: 'editChart',
                onclick: function() {
                    alert('tbi');
                }
            }, {
                textKey: 'removeChart',
                onclick: function() {
                    removeChart(this);
                }
            }]
        }
    };
    
    Highcharts.setOptions({
        lang: {
            settings:    PMA_messages['strSettings'],
            removeChart: PMA_messages['strRemoveChart'],
            editChart:   PMA_messages['strEditChart']
        }
    });
    
    $('a[href="#rearrangeCharts"], a[href="#endChartEditMode"]').click(function() {
        editMode = !editMode;
        if($(this).attr('href') == '#endChartEditMode') editMode = false;
        
        // Icon graphics have zIndex 19,20 and 21. Let's just hope nothing else has the same zIndex
        $('ul#chartGrid li svg *[zIndex=20], li svg *[zIndex=21], li svg *[zIndex=19]').toggle(editMode)
        
        $('a[href="#endChartEditMode"]').toggle(editMode);
        
        if(editMode) {
            $( "#chartGrid" ).sortable();
            $( "#chartGrid" ).disableSelection();
        } else {
            $( "#chartGrid" ).sortable('destroy');
            saveMonitor(); // Save settings
        }
        
        return false;
    });
    
    // global settings
    $('div#statustabs_charting div.popupContent input[name="newChartWidth"], ' +
      'div#statustabs_charting div.popupContent input[name="newChartHeight"]').change(function() {
          
        monitorSettings.chartSize = {
            width: parseInt($('div#statustabs_charting div.popupContent input[name="newChartWidth"]')
                    .attr('value')) || monitorSettings.chartSize.width,
            height: parseInt($('div#statustabs_charting div.popupContent input[name="newChartHeight"]')
                    .attr('value')) || monitorSettings.chartSize.height
        };
        
        $.each(chartGrid, function(key, value) {
            value.chart.setSize(
                monitorSettings.chartSize.width,
                monitorSettings.chartSize.height, 
                false
            );
        });
        
        saveMonitor(); // Save settings
    });
    
    $('div#statustabs_charting div.popupContent select[name="gridChartRefresh"]').change(function() {
        monitorSettings.gridRefresh = parseInt(this.value) * 1000;
        clearTimeout(refreshTimeout);
        
        if(gridTimeoutCallBack)
            gridTimeoutCallBack.abort();
        
        xmin = new Date().getTime() - server_time_diff - monitorSettings.gridMaxPoints * monitorSettings.gridRefresh;
        xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;
        
        $.each(chartGrid, function(key, value) {
            value.chart.xAxis[0].setExtremes(xmin, xmax, false);
        });
        
        refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);
        
        saveMonitor(); // Save settings
    });
    
    $('a[href="#addNewChart"]').click(function() {
        var dlgButtons = { };
        
        dlgButtons[PMA_messages['strAddChart']] = function() {
            var type = $('input[name="chartType"]:checked').val();
            
            if(type == 'cpu' || type == 'memory' || type=='swap')
                newChart = presetCharts[type + '-' + server_os];
            else {
                if(! newChart || ! newChart.nodes || newChart.nodes.length == 0) {
                    alert(PMA_messages['strAddOneSeriesWarning']);
                    return;
                }
            }
            
            newChart.title = $('input[name="chartTitle"]').attr('value');
            // Add a cloned object to the chart grid
            addChart($.extend(true, {}, newChart));
            
            newChart = null;
                
            saveMonitor(); // Save settings

            $(this).dialog("close");
        }
        
        dlgButtons[PMA_messages['strClose']] = function() {
            newChart = null;
            $('span#clearSeriesLink').hide();
            $('#seriesPreview').html('');
            $(this).dialog("close");
        }
        
        $('div#addChartDialog').dialog({
            width:'auto',
            height:'auto',
            buttons: dlgButtons
        });
        
        $('div#addChartDialog #seriesPreview').html('<i>' + PMA_messages['strNone'] + '</i>');
        
        return false;
    });
    
    $('a[href="#pauseCharts"]').click(function() {
        redrawCharts = ! redrawCharts;
        if(! redrawCharts)
            $(this).html('<img src="' + pmaThemeImage + 'play.png" alt="" /> ' + PMA_messages['strResumeMonitor']);
        else {
            $(this).html('<img src="' + pmaThemeImage + 'pause.png" alt="" /> ' + PMA_messages['strPauseMonitor']);
            if(chartGrid == null) {
                initGrid();
                $('a[href="#settingsPopup"]').show();
            }
        }
        return false;
    });
    
    $('a[href="#monitorInstructionsDialog"]').click(function() {
        var $dialog = $('div#monitorInstructionsDialog');
        
        $dialog.dialog({
            width: 595,
            height: 'auto'
        }).find('img.ajaxIcon').show();
        
        var loadLogVars = function(getvars) {
            vars = { ajax_request: true, logging_vars: true };
            if(getvars) $.extend(vars,getvars);
            
            $.get('server_status.php?' + url_query, vars,
                function(data) {
                    var logVars = $.parseJSON(data),
                        icon = 's_success.png', msg='', str='';
                    
                    if(logVars['general_log'] == 'ON') {
                        if(logVars['slow_query_log'] == 'ON') 
                            msg = PMA_messages['strBothLogOn'];
                        else 
                            msg = PMA_messages['strGenLogOn'];
                    }
                    
                    if(msg.length == 0 && logVars['slow_query_log'] == 'ON') {
                        msg = PMA_messages['strSlowLogOn'];
                    }
                    
                    if(msg.length == 0) {
                        icon = 's_error.png';
                        msg = PMA_messages['strBothLogOff'];
                    }
                    
                    str = '<b>' + PMA_messages['strCurrentSettings'] + '</b><br><div class="smallIndent">';
                    str += '<img src="' + pmaThemeImage + icon + '" alt=""/> ' + msg + '<br />';
                    
                    if(logVars['log_output'] != 'TABLE')
                        str += '<img src="' + pmaThemeImage + 's_error.png" alt=""/> ' + PMA_messages['strLogOutNotTable'] + '<br />';
                    else 
                        str += '<img src="' + pmaThemeImage + 's_success.png" alt=""/> ' + PMA_messages['strLogOutIsTable'] + '<br />';
                    
                    if(logVars['slow_query_log'] == 'ON') {
                        if(logVars['long_query_time'] > 2)
                            str += '<img src="' + pmaThemeImage + 's_attention.png" alt=""/> '
                                + $.sprintf(PMA_messages['strSmallerLongQueryTimeAdvice'], logVars['long_query_time'])
                                + '<br />';
                        
                        if(logVars['long_query_time'] < 2)
                            str += '<img src="' + pmaThemeImage + 's_success.png" alt=""/> '
                                + $.sprintf(PMA_messages['strLongQueryTimeSet'], logVars['long_query_time'])
                                + '<br />';
                    }
                    
                    str += '</div>';
                    
                    if(is_superuser) {
                        str += '<p></p><b>Change settings</b>';
                        str += '<div class="smallIndent">';
                        str += PMA_messages['strSettingsAppliedGlobal'] + '<br/>';
                        
                        var varValue = 'TABLE';
                        if(logVars['log_output'] == 'TABLE') varValue = 'FILE';
                        
                        str += '- <a class="set" href="#log_output-' + varValue + '">'
                            + $.sprintf(PMA_messages['strSetLogOutput'], varValue)
                            + ' </a><br />';
                        
                        if(logVars['general_log'] != 'ON')
                            str += '- <a class="set" href="#general_log-ON">' 
                                + $.sprintf(PMA_messages['strEnableVar'], 'general_log') 
                                + ' </a><br />';
                        else 
                            str += '- <a class="set" href="#general_log-OFF">' 
                                + $.sprintf(PMA_messages['strDisableVar'], 'general_log') 
                                + ' </a><br />';
                        
                        if(logVars['slow_query_log'] != 'ON')
                            str += '- <a class="set" href="#slow_query_log-ON">' 
                                +  $.sprintf(PMA_messages['strEnableVar'], 'slow_query_log')
                                + ' </a><br />';
                        else 
                            str += '- <a class="set" href="#slow_query_log-OFF">' 
                                +  $.sprintf(PMA_messages['strDisableVar'], 'slow_query_log')
                                + ' </a><br />';
                        
                        
                        varValue = 5;
                        if(logVars['long_query_time'] > 2) varValue = 1;
                        
                        str += '- <a class="set" href="#long_query_time-' + varValue + '">' 
                            + $.sprintf(PMA_messages['setSetLongQueryTime'], varValue)
                            + ' </a><br />';
                            
                    } else 
                        str += PMA_messages['strNoSuperUser'] + '<br/>';
                    
                    str += '</div>';
                    
                    $dialog.find('div.monitorUse').toggle(
                        logVars['log_output'] == 'TABLE' && (logVars['slow_query_log'] == 'ON' || logVars['general_log'] == 'ON')
                    );
                    
                    $dialog.find('div.ajaxContent').html(str);
                    $dialog.find('img.ajaxIcon').hide();
                    $dialog.find('a.set').click(function() {
                        var nameValue = $(this).attr('href').split('-');
                        loadLogVars({ varName: nameValue[0].substr(1), varValue: nameValue[1]});
                        $dialog.find('img.ajaxIcon').show();
                    });
                }
            );
        }
        
        
        loadLogVars();
        
        return false;
    });
    
    $('input[name="chartType"]').change(function() {
        $('#chartVariableSettings').toggle(this.checked && this.value == 'variable');
        var title = $('input[name="chartTitle"]').attr('value');
        if(title == PMA_messages['strChartTitle'] || title == $('label[for="'+$('input[name="chartTitle"]').data('lastRadio')+'"]').text()) {
            $('input[name="chartTitle"]').data('lastRadio',$(this).attr('id'));
            $('input[name="chartTitle"]').attr('value',$('label[for="'+$(this).attr('id')+'"]').text());
        }
        
    });
    
    $('input[name="useDivisor"]').change(function() {
        $('span.divisorInput').toggle(this.checked);
    });
    $('input[name="useUnit"]').change(function() {
        $('span.unitInput').toggle(this.checked);
    });
    
    $('select[name="varChartList"]').change(function () {
        if(this.selectedIndex!=0)
            $('#variableInput').attr('value',this.value);
    });
    
    $('a[href="#kibDivisor"]').click(function() {
        $('input[name="valueDivisor"]').attr('value',1024);
        $('input[name="valueUnit"]').attr('value',PMA_messages['strKiB']);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked',true);
        return false;
    });
    
    $('a[href="#mibDivisor"]').click(function() {
        $('input[name="valueDivisor"]').attr('value',1024*1024);
        $('input[name="valueUnit"]').attr('value',PMA_messages['strMiB']);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked',true);
        return false;
    });
    
    $('a[href="#submitClearSeries"]').click(function() {
        $('#seriesPreview').html('<i>' + PMA_messages['strNone'] + '</i>');
        newChart = null;
        $('span#clearSeriesLink').hide();
    });
    
    $('a[href="#submitAddSeries"]').click(function() {
        if($('input#variableInput').attr('value').length == 0) return false;
        
        if(newChart == null) {
            $('#seriesPreview').html('');
        
            newChart = {
                title: $('input[name="chartTitle"]').attr('value'),
                nodes: []
            }
        }
        
        var serie = {
            dataType:'statusvar',
            name: $('input#variableInput').attr('value'),
            display: $('input[name="differentialValue"]').attr('checked') ? 'differential' : '',
        };
        
        if(serie.name == 'Processes') serie.dataType='proc';
        
        if($('input[name="useDivisor"]').attr('checked')) 
            serie.valueDivisor = parseInt($('input[name="valueDivisor"]').attr('value'));

        if($('input[name="useUnit"]').attr('checked'))
            serie.unit = $('input[name="valueUnit"]').attr('value');
        
        
        
        var str = serie.display == 'differential' ? ', ' + PMA_messages['strDifferential'] : '';
        str += serie.valueDivisor ? (', ' + $.sprintf(PMA_messages['strDividedBy'], serie.valueDivisor)) : '';
        
        $('#seriesPreview').append('- ' + serie.name + str + '<br>');
        
        newChart.nodes.push(serie);
        
        $('input#variableInput').attr('value','');
        $('input[name="differentialValue"]').attr('checked',true);
        $('input[name="useDivisor"]').attr('checked',false);
        $('input[name="useUnit"]').attr('checked',false);
        $('input[name="useDivisor"]').trigger('change');
        $('input[name="useUnit"]').trigger('change');
        $('select[name="varChartList"]').get(0).selectedIndex=0;
        
        $('span#clearSeriesLink').show();

        return false;
    });
    
    $("input#variableInput").autocomplete({
            source: variableNames
    });
    
    
    function initGrid() {
        var settings;
        var series;

        if(window.localStorage) {
            if(window.localStorage['monitorCharts'])
                chartGrid = $.parseJSON(window.localStorage['monitorCharts']);
            if(window.localStorage['monitorSettings'])
                monitorSettings = $.parseJSON(window.localStorage['monitorSettings']);
            
            $('a[href="#clearMonitorConfig"]').toggle(chartGrid != null);
        }
        
        if(chartGrid == null)
            chartGrid = defaultChartGrid;
        if(monitorSettings == null)
            monitorSettings = defaultMonitorSettings;
        
        $('div#statustabs_charting div.popupContent input[name="newChartWidth"]').attr('value',monitorSettings.chartSize.width);
        $('div#statustabs_charting div.popupContent input[name="newChartHeight"]').attr('value',monitorSettings.chartSize.height);
    
        $('select[name="gridChartRefresh"]').attr('value',monitorSettings.gridRefresh / 1000);
        
        $.each(chartGrid, function(key, value) {
            addChart(value,true);
        });
        
        buildRequiredDataList();
        refreshChartGrid();
    }
    
    function addChart(chartObj, initialize) {
        series = [];
        for(var j=0; j<chartObj.nodes.length; j++)
            series.push(chartObj.nodes[j]);
        
        if(xmin == -1)
            xmin = new Date().getTime() - server_time_diff - monitorSettings.gridMaxPoints * monitorSettings.gridRefresh;
        if(xmax == -1) 
            xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;
        
        settings = {
            chart: {
                renderTo: 'gridchart' + chartAI,
                width: monitorSettings.chartSize.width,
                height: monitorSettings.chartSize.height,
                marginRight: 5,
                zoomType: 'x',
                events: {
                    selection: function(event) {
                        if(editMode) return false;
                        
                        var extremesObject = event.xAxis[0], 
                            min = extremesObject.min,
                            max = extremesObject.max;
                        
                        $('#logAnalyseDialog').html(
                            '<p>' + PMA_messages['strSelectedTimeRange']
                            + Highcharts.dateFormat('%H:%M:%S',new Date(min)) + ' - ' 
                            + Highcharts.dateFormat('%H:%M:%S',new Date(max)) + '</p>'
                            + '<input type="checkbox" id="groupInserts" value="1" checked="checked" />'
                            + '<label for="groupData">' + PMA_messages['strGroupInserts'] + '</label>'
                            + PMA_messages['strLogAnalyseInfo']
                        );
                        
                        var dlgBtns = { };
                        
                        dlgBtns[PMA_messages['strFromSlowLog']] = function() {
                            loadLogStatistics(
                                { src: 'slow', start: min, end: max, groupInserts: $('input#groupInserts').attr('checked') } 
                            );
                            
                            $(this).dialog("close");
                        }
                        
                        dlgBtns[PMA_messages['strFromGeneralLog']] = function() {
                            loadLogStatistics(
                                { src: 'general', start: min, end: max, groupInserts: $('input#groupInserts').attr('checked') }
                            );
                            
                            $(this).dialog("close");
                        }
                        
                        $('#logAnalyseDialog').dialog({
                            width: 'auto',
                            height: 'auto',
                            buttons: dlgBtns
                        });
                        
                        return false;
                    }
                }
            },
            xAxis: {
                min: xmin,
                max: xmax
            },

            yAxis: {
                title: {
                    text: ''
                }
            },
            tooltip: {
                formatter: function() {
                        var s = '<b>'+Highcharts.dateFormat('%H:%M:%S', this.x)+'</b>';
                    
                        $.each(this.points, function(i, point) {
                            s += '<br/><span style="color:'+point.series.color+'">'+ point.series.name +':</span> '+
                                ((parseInt(point.y) == point.y) ? point.y : Highcharts.numberFormat(this.y, 2)) + ' ' + (point.series.options.unit || '');
                        });
                        
                        return s;
                },
                shared: true
            },
            legend: {
                enabled: false
            },
            series: series,
            buttons: gridbuttons,
            title: { text: chartObj.title },
        };
        
        if(chartObj.settings)
            $.extend(true,settings,chartObj.settings);
                
        if($('#'+settings.chart.renderTo).length==0) {
            $('ul#chartGrid').append('<li class="ui-state-default" id="'+settings.chart.renderTo+'"></li>');
        }
        
        chartObj.chart = PMA_createChart(settings);
        chartObj.numPoints = 0;
        
        if(initialize != true) {
            chartGrid['c'+chartAI] = chartObj;
            buildRequiredDataList();
        }
        
        // Edit,Print icon only in edit mode
        $('ul#chartGrid li svg *[zIndex=20], li svg *[zIndex=21], li svg *[zIndex=19]').toggle(editMode)
        
        chartAI++;
    }
    
    function removeChart(chartObj) {
        var htmlnode = chartObj.options.chart.renderTo;
        if(! htmlnode ) return;
        
        
        $.each(chartGrid, function(key, value) {
            if(value.chart.options.chart.renderTo == htmlnode) {
                delete chartGrid[key];
                return false;
            }
        });
        
        buildRequiredDataList();
        
        // Using settimeout() because clicking the remove link fires an onclick event 
        // which throws an error when the chart is destroyed
        setTimeout(function() {
            chartObj.destroy();
            $('li#' + htmlnode).remove();
        },10);
        
        saveMonitor(); // Save settings
    }
    
    function refreshChartGrid() {
        /* Send to server */
        gridTimeoutCallBack = $.post('server_status.php?'+url_query, { ajax_request: true, chart_data: 1, type: 'chartgrid', requiredData: $.toJSON(requiredData) },function(data) {
            var chartData = $.parseJSON(data);
            var value, i=0;
            var diff;
    
            /* Update values in each graph */
            $.each(chartGrid, function(key, elem) {
                // If newly added chart, we have no data for it yet
                if(! chartData[key]) return;
                // Draw all points
                for(var j=0; j < elem.nodes.length; j++) {
                    value = chartData[key][j].y;

                    if(i==0 && j==0) {
                        if(oldChartData==null) diff = chartData.x - xmax;
                        else diff = parseInt(chartData.x - oldChartData.x);
                        
                        xmin+= diff;
                        xmax+= diff;
                    }
                    
                    elem.chart.xAxis[0].setExtremes(xmin, xmax, false);
                    
                    if(elem.nodes[j].display == 'differential') {
                        if(oldChartData == null || oldChartData[key] == null) continue;
                        value -= oldChartData[key][j].y;
                    }
                    
                    if(elem.nodes[j].valueDivisor)
                        value = value / elem.nodes[j].valueDivisor;

                    if(elem.nodes[j].transformFn) {
                        value = elem.nodes[j].transformFn(
                            chartData[key][j],
                            (oldChartData == null) ? null : oldChartData[key][j],
                            j
                        );
                    }
                    
                    if(value != undefined)
                        elem.chart.series[j].addPoint(
                            {  x: chartData.x, y: value },
                            false, 
                            elem.numPoints >= monitorSettings.gridMaxPoints
                        );
                }
                
                i++;
                
                chartGrid[key].numPoints++;
                if(redrawCharts)
                    elem.chart.redraw();
            });
            
            oldChartData = chartData;
            
            refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);
        });
    }
    
    /* Build list of nodes that need to be retrieved */
    function buildRequiredDataList() {
        requiredData = {};
        $.each(chartGrid, function(key, chart) {
            requiredData[key] = chart.nodes;
        });
    }
    
    function loadLogStatistics(opts) {
        var tableStr = '';
        var logRequest = null;
        var groupInsert = false;
        
        if(opts.groupInserts)
            groupInserts = true;
        
        $('#loadingLogsDialog').html(PMA_messages['strAnalysingLogs'] + ' <img class="ajaxIcon" src="' + pmaThemeImage + 'ajax_clock_small.gif" alt="">');

        $('#loadingLogsDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: {
                'Cancel request': function() {
                    if(logRequest != null) 
                        logRequest.abort();
                    
                    $(this).dialog("close"); 
                }
            }
        });
        
        
        var formatValue = function(name, value) {
            switch(name) {
                case 'user_host': 
                    return value.replace(/(\[.*?\])+/g,'');
            }
            return value;
        }
        
        logRequest = $.get('server_status.php?'+url_query, 
            { ajax_request: true, log_data: 1, type: opts.src, time_start: Math.round(opts.start / 1000), time_end: Math.round(opts.end / 1000), groupInserts: groupInserts },
            function(data) { 
                var data = $.parseJSON(data);
                var rows = data.rows;
                var cols = new Array();
                
                if(rows.length != 0) {
                    tableStr = '<table border="0" class="sortable">';
                    
                    for(var i=0; i < rows.length; i++) {
                        if(i == 0) {
                            tableStr += '<thead>';
                            $.each(rows[0],function(key, value) {
                                cols.push(key);
                            });
                            tableStr += '<tr><th class="nowrap">' + cols.join('</th><th class="nowrap">') + '</th></tr>';
                            tableStr += '</thead><tbody>';
                        }
                        
                        tableStr += '<tr>';
                        for(var j=0; j < cols.length; j++)
                            tableStr += '<td>' + formatValue(cols[j], rows[i][cols[j]]) + '</td>';
                        tableStr += '</tr>';    
                    }
                    
                    tableStr+='</tbody></table>';
                    
                    $('#logTable').html(tableStr);
                    
                    // Append a tooltip to the count column, if there exist one
                    if($('#logTable th:last').html() == '#') {
                        $('#logTable th:last').append('&nbsp;<img class="qroupedQueryInfoIcon" src="' + pmaThemeImage + 'b_docs.png" alt="" />');
                    
                        var qtipContent = PMA_messages['strCountColumnExplanation'];
                        if(groupInserts) qtipContent += '<p>' + PMA_messages['strMoreCountColumnExplanation'] + '</p>';
                        
                        $('img.qroupedQueryInfoIcon').qtip({
                            content: qtipContent,
                            position: {
                                corner: {
                                    target: 'bottomMiddle',
                                    tooltip: 'topRight'
                                }
                                
                            },
                            hide: { delay: 1000 }
                        })
                    }
                    
                    $('div#logTable table').tablesorter({
                        sortList: [[0,1]],
                        widgets: ['zebra']
                    });
                    
                    $('div#logTable table thead th')
                        .append('<img class="sortableIcon" src="' + pmaThemeImage + 'cleardot.gif" alt="">');

                    
                    $('#loadingLogsDialog').html('<p>' + PMA_messages['strLogDataLoaded'] + '</p>');
                    $.each(data.sum, function(key, value) {
                        key = key.charAt(0).toUpperCase() + key.slice(1).toLowerCase();
                        if(key == 'Total') key = '<b>' + key + '</b>';
                        $('#loadingLogsDialog').append(key + ': ' + value + '<br/>');
                    });
                    
                    var dlgBtns = {};
                    dlgBtns[PMA_messages['strJumpToTable']] = function() { 
                        $(this).dialog("close"); 
                        $(document).scrollTop($('div#logTable').offset().top);
                    }
                    
                    $('#loadingLogsDialog').dialog( "option", "buttons", dlgBtns);
                    
                } else {
                    $('#loadingLogsDialog').html('<p>' + PMA_messages['strNoDataFound'] + '</p>');
                    
                    var dlgBtns = {};
                    dlgBtns[PMA_messages['strClose']] = function() { 
                        $(this).dialog("close"); 
                    }
                    
                    $('#loadingLogsDialog').dialog( "option", "buttons", dlgBtns );
                }
            }
        );
    }
    
    function saveMonitor() {
        var gridCopy = {};
            
        $.each(chartGrid, function(key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
        });
        
        if(window.localStorage) {
            window.localStorage['monitorCharts'] = $.toJSON(gridCopy);
            window.localStorage['monitorSettings'] = $.toJSON(monitorSettings);
        }
        
        $('a[href="#clearMonitorConfig"]').show();
    }
    
    $('a[href="#clearMonitorConfig"]').click(function() {
        window.localStorage.removeItem('monitorCharts');
        window.localStorage.removeItem('monitorSettings');
        $(this).hide();
    });
    
});