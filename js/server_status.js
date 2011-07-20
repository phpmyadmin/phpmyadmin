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
                $cnt.hide().removeClass('openedPopup');
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
                    .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');
                    
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
                    .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');
                    
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
    /* Saves the previous ajax response for differential values */
    var oldChartData = null;
    // Holds about to created chart 
    var newChart = null;
    var chartSpacing;
    
    // Runtime parameter of the monitor
    var runtime = {
        // Holds all visible charts in the grid
        charts: null,
        // Current max points per chart (needed for auto calculation)
        gridMaxPoints: 20,
        // displayed time frame
        xmin: -1, 
        xmax: -1,
        // Stores the timeout handler so it can be cleared
        refreshTimeout: null,
        // Stores the GET request to refresh the charts
        refreshRequest: null,
        // Chart auto increment
        chartAI: 0,
        // To play/pause the monitor
        redrawCharts: false,
        // Object that contains a list of nodes that need to be retrieved from the server for chart updates
        dataList: []
    };
    
    var monitorSettings = null;
    
    var defaultMonitorSettings = {
        columns: 4,
        chartSize: { width: 295, height: 250 },
        // Max points in each chart. Settings it to 'auto' sets gridMaxPoints to (chartwidth - 40) / 12
        gridMaxPoints: 'auto', 
        /* Refresh rate of all grid charts in ms */
        gridRefresh: 5000
    };
    
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
    };
    
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
        $('table#chartGrid div svg').find('*[zIndex=20], *[zIndex=21], *[zIndex=19]').toggle(editMode)
        
        $('a[href="#endChartEditMode"]').toggle(editMode);
        
        if(editMode) {
            // Close the settings popup
            $('#statustabs_charting .popupContent').hide().removeClass('openedPopup');
            
            $("#chartGrid").sortableTable({
                ignoreRect: {
                    top: 8,
                    left: chartSize().width - 63,
                    width: 54,
                    height: 24
                },
                events: {
                    start: function() {
                      //  console.log('start.');
                    },
                    // Drop event. The drag child element is moved into the drop element
                    // and vice versa. So the parameters are switched.
                    drop: function(drag, drop, pos) { 
                        var dragKey, dropKey, dropRender;
                        var dragRender = $(drag).children().first().attr('id');
                        
                        if($(drop).children().length > 0)
                            dropRender = $(drop).children().first().attr('id');
                        
                        // Find the charts in the array
                        $.each(runtime.charts, function(key, value) {
                            if(value.chart.options.chart.renderTo == dragRender)
                                dragKey = key;
                            if(dropRender && value.chart.options.chart.renderTo == dropRender)
                                dropKey = key;
                        });
                        
                        // Case 1: drag and drop are charts -> Switch keys
                        if(dropKey) {
                            if(dragKey) {
                                dragChart = runtime.charts[dragKey];
                                runtime.charts[dragKey] = runtime.charts[dropKey];
                                runtime.charts[dropKey] = dragChart;
                            } else {
                                // Case 2: drop is a empty cell => just completely rebuild the ids
                                var keys = [];
                                var dropKeyNum = parseInt(dropKey.substr(1));
                                var insertBefore = pos.col + pos.row * monitorSettings.columns;
                                var values = [];
                                var newChartList = {};
                                var c = 0;
                                    
                                $.each(runtime.charts, function(key, value) {
                                    if(key != dropKey)
                                        keys.push(key);
                                });
                                
                                keys.sort();
                                
                                // Rebuilds all ids, with the dragged chart correctly inserted
                                for(var i=0; i<keys.length; i++) {
                                    if(keys[i] == insertBefore) {
                                        newChartList['c' + (c++)] = runtime.charts[dropKey];
                                        insertBefore = -1; // Insert ok
                                    }
                                    newChartList['c' + (c++)] = runtime.charts[keys[i]];
                                }
                                
                                // Not inserted => put at the end
                                if(insertBefore != -1)
                                    newChartList['c' + (c++)] = runtime.charts[dropKey];
                                
                                runtime.charts = newChartList;
                            }
                            
                            saveMonitor();
                        }
                    }
                }
            });
           
        } else {
            $("#chartGrid").sortableTable('destroy');
            saveMonitor(); // Save settings
        }
        
        return false;
    });
    
    // global settings
    $('div#statustabs_charting div.popupContent select[name="chartColumns"]').change(function() {
        monitorSettings.columns = parseInt(this.value);
        
        var newSize = chartSize();
        
        // Empty cells should keep their size so you can drop onto them
        $('table#chartGrid tr td').css('width',newSize.width + 'px');
        
        /* Reorder all charts that it fills all column cells */
        var numColumns;
        var $tr = $('table#chartGrid tr:first');
        var row=0;
        while($tr.length != 0) {
            numColumns = 1;
            // To many cells in one row => put into next row
            $tr.find('td').each(function() {
                if(numColumns > monitorSettings.columns) {
                    if($tr.next().length == 0) $tr.after('<tr></tr>');
                    $tr.next().prepend($(this));
                }
                numColumns++;
            });
            
            // To little cells in one row => for each cell to little, move all cells backwards by 1
            if($tr.next().length > 0) {
                var cnt = monitorSettings.columns - $tr.find('td').length;
                for(var i=0; i < cnt; i++) {
                    $tr.append($tr.next().find('td:first'));
                    $tr.nextAll().each(function() {
                        if($(this).next().length != 0)
                            $(this).append($(this).next().find('td:first'));
                    });
                }
            }
            
            $tr = $tr.next();
            row++;
        }
        
        /* Apply new chart size to all charts */
        $.each(runtime.charts, function(key, value) {
            value.chart.setSize(
                newSize.width,
                newSize.height, 
                false
            );
        });
        
        if(monitorSettings.gridMaxPoints == 'auto')
            runtime.gridMaxPoints = Math.round((newSize.width - 40) / 12);
        
        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;
        
        if(editMode)
            $("#chartGrid").sortableTable('refresh');
        
        saveMonitor(); // Save settings
    });
    
    $('div#statustabs_charting div.popupContent select[name="gridChartRefresh"]').change(function() {
        monitorSettings.gridRefresh = parseInt(this.value) * 1000;
        clearTimeout(runtime.refreshTimeout);
        
        if(runtime.refreshRequest)
            runtime.refreshRequest.abort();
        
        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;
        
        $.each(runtime.charts, function(key, value) {
            value.chart.xAxis[0].setExtremes(runtime.xmin, runtime.xmax, false);
        });
        
        runtime.refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);
        
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
        runtime.redrawCharts = ! runtime.redrawCharts;
        if(! runtime.redrawCharts)
            $(this).html('<img src="themes/dot.gif" class="icon ic_play" alt="" /> ' + PMA_messages['strResumeMonitor']);
        else {
            $(this).html('<img src="themes/dot.gif" class="icon ic_pause" alt="" /> ' + PMA_messages['strPauseMonitor']);
            if(runtime.charts == null) {
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
                        icon = 'ic_s_success', msg='', str='';
                    
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
                        icon = 'ic_s_error';
                        msg = PMA_messages['strBothLogOff'];
                    }
                    
                    str = '<b>' + PMA_messages['strCurrentSettings'] + '</b><br><div class="smallIndent">';
                    str += '<img src="themes/dot.gif" class="icon ' + icon + '" alt=""/> ' + msg + '<br />';
                    
                    if(logVars['log_output'] != 'TABLE')
                        str += '<img src="themes/dot.gif" class="icon ic_s_error" alt=""/> ' + PMA_messages['strLogOutNotTable'] + '<br />';
                    else 
                        str += '<img src="themes/dot.gif" class="icon ic_s_success" alt=""/> ' + PMA_messages['strLogOutIsTable'] + '<br />';
                    
                    if(logVars['slow_query_log'] == 'ON') {
                        if(logVars['long_query_time'] > 2)
                            str += '<img src="themes/dot.gif" class="icon ic_s_attention" alt=""/> '
                                + $.sprintf(PMA_messages['strSmallerLongQueryTimeAdvice'], logVars['long_query_time'])
                                + '<br />';
                        
                        if(logVars['long_query_time'] < 2)
                            str += '<img src="themes/dot.gif" class="icon ic_s_success" alt=""/> '
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

        /* Apply default values & config */
        if(window.localStorage) {
            if(window.localStorage['monitorCharts'])
                runtime.charts = $.parseJSON(window.localStorage['monitorCharts']);
            if(window.localStorage['monitorSettings'])
                monitorSettings = $.parseJSON(window.localStorage['monitorSettings']);
            
            $('a[href="#clearMonitorConfig"]').toggle(runtime.charts != null);
        }
        
        if(runtime.charts == null)
            runtime.charts = defaultChartGrid;
        if(monitorSettings == null)
            monitorSettings = defaultMonitorSettings;
         
        $('select[name="gridChartRefresh"]').attr('value',monitorSettings.gridRefresh / 1000);
        $('select[name="chartColumns"]').attr('value',monitorSettings.columns);
        
        if(monitorSettings.gridMaxPoints == 'auto')
            runtime.gridMaxPoints = Math.round((monitorSettings.chartSize.width - 40) / 12);
        else 
            runtime.gridMaxPoints = monitorSettings.gridMaxPoints;
        
        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;

        /* Calculate how much spacing there is between each chart */
        $('table#chartGrid').html('<tr><td></td><td></td></tr><tr><td></td><td></td></tr>');
        chartSpacing = {
            width: $('table#chartGrid td:nth-child(2)').offset().left - $('table#chartGrid td:nth-child(1)').offset().left,
            height: $('table#chartGrid tr:nth-child(2) td:nth-child(2)').offset().top - $('table#chartGrid tr:nth-child(1) td:nth-child(1)').offset().top
        }
        $('table#chartGrid').html('');
        
        /* Add all charts - in correct order */
        var keys = [];
        $.each(runtime.charts, function(key, value) {
            keys.push(key);
        });
        keys.sort();
        for(var i=0; i<keys.length; i++)
            addChart(runtime.charts[keys[i]],true);
        
        /* Fill in missing cells */
        var numCharts = $('table#chartGrid .monitorChart').length;
        var numMissingCells = (monitorSettings.columns - numCharts % monitorSettings.columns) % monitorSettings.columns;
        for(var i=0; i < numMissingCells; i++) {
            $('table#chartGrid tr:last').append('<td></td>');
        }
        
        // Empty cells should keep their size so you can drop onto them
        $('table#chartGrid tr td').css('width',chartSize().width + 'px');

        
        buildRequiredDataList();
        refreshChartGrid();
    }
    
    function chartSize() {
        var wdt = $('div#logTable').innerWidth() / monitorSettings.columns - (monitorSettings.columns - 1) * chartSpacing.width;
        return {
            width: wdt,
            height: 0.75 * wdt
        }
    }
    
    function addChart(chartObj, initialize) {
        series = [];
        for(var j=0; j<chartObj.nodes.length; j++)
            series.push(chartObj.nodes[j]);
        
        settings = {
            chart: {
                renderTo: 'gridchart' + runtime.chartAI,
                width: chartSize().width,
                height: chartSize().height,
                marginRight: 5,
                zoomType: 'x',
                events: {
                    selection: function(event) {
                        if(editMode) return false;
                        
                        var extremesObject = event.xAxis[0], 
                            min = extremesObject.min,
                            max = extremesObject.max;
                        
                        $('#logAnalyseDialog input[name="dateStart"]')
                            .attr('value', Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', new Date(min)));
                        $('#logAnalyseDialog input[name="dateEnd"]')
                            .attr('value', Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', new Date(max)));
                        
                        var dlgBtns = { };
                        
                        dlgBtns[PMA_messages['strFromSlowLog']] = function() {
                            var dateStart = Date.parse($('#logAnalyseDialog input[name="dateStart"]').attr('value')) || min;
                            var dateEnd = Date.parse($('#logAnalyseDialog input[name="dateEnd"]').attr('value')) || max;
                            
                            loadLogStatistics({
                                src: 'slow',
                                start: dateStart,
                                end: dateEnd,
                                removeVariables: $('input#removeVariables').prop('checked'),
                                limitTypes: $('input#limitTypes').prop('checked')
                            });
                                
                            $('#logAnalyseDialog').find('dateStart,dateEnd').datepicker('destroy');
                            
                            $(this).dialog("close");
                        }
                        
                        dlgBtns[PMA_messages['strFromGeneralLog']] = function() {
                            var dateStart = Date.parse($('#logAnalyseDialog input[name="dateStart"]').attr('value')) || min;
                            var dateEnd = Date.parse($('#logAnalyseDialog input[name="dateEnd"]').attr('value')) || max;
                            
                            loadLogStatistics({
                                src: 'general',
                                start: dateStart,
                                end: dateEnd,
                                removeVariables: $('input#removeVariables').prop('checked'),
                                limitTypes: $('input#limitTypes').prop('checked')
                            });
                                
                            $('#logAnalyseDialog').find('dateStart,dateEnd').datepicker('destroy');
                            
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
                min: runtime.xmin,
                max: runtime.xmax
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
            var numCharts = $('table#chartGrid .monitorChart').length;
    
            if(numCharts == 0 || !( numCharts % monitorSettings.columns))
                $('table#chartGrid').append('<tr></tr>');
            
            $('table#chartGrid tr:last').append('<td><div class="ui-state-default monitorChart" id="'+settings.chart.renderTo+'"></div></td>');
        }
        
        chartObj.chart = PMA_createChart(settings);
        chartObj.numPoints = 0;
        
        if(initialize != true) {
            runtime.charts['c'+runtime.chartAI] = chartObj;
            buildRequiredDataList();
        }
        
        // Edit,Print icon only in edit mode
        $('table#chartGrid div svg').find('*[zIndex=20], *[zIndex=21], *[zIndex=19]').toggle(editMode)
        
        runtime.chartAI++;
    }
    
    function removeChart(chartObj) {
        var htmlnode = chartObj.options.chart.renderTo;
        if(! htmlnode ) return;
        
        
        $.each(runtime.charts, function(key, value) {
            if(value.chart.options.chart.renderTo == htmlnode) {
                delete runtime.charts[key];
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
        runtime.refreshRequest = $.post('server_status.php?'+url_query, { ajax_request: true, chart_data: 1, type: 'chartgrid', requiredData: $.toJSON(runtime.dataList) },function(data) {
            var chartData = $.parseJSON(data);
            var value, i=0;
            var diff;
    
            /* Update values in each graph */
            $.each(runtime.charts, function(orderKey, elem) {
                var key = elem.chartID;
                // If newly added chart, we have no data for it yet
                if(! chartData[key]) return;
                // Draw all points
                for(var j=0; j < elem.nodes.length; j++) {
                    value = chartData[key][j].y;

                    if(i==0 && j==0) {
                        if(oldChartData==null) diff = chartData.x - runtime.xmax;
                        else diff = parseInt(chartData.x - oldChartData.x);
                        
                        runtime.xmin+= diff;
                        runtime.xmax+= diff;
                    }
                    
                    elem.chart.xAxis[0].setExtremes(runtime.xmin, runtime.xmax, false);
                    
                    if(elem.nodes[j].display == 'differential') {
                        if(oldChartData == null || oldChartData[key] == null) continue;
                        value -= oldChartData[key][j].y;
                    }
                    
                    if(elem.nodes[j].valueDivisor)
                        value = value / elem.nodes[j].valueDivisor;

                    if(elem.nodes[j].transformFn) {
                        value = elem.nodes[j].transformFn(
                            chartData[key][j],
                            (oldChartData == null) ? null : oldChartData[key][j]
                        );
                    }
                    
                    if(value != undefined)
                        elem.chart.series[j].addPoint(
                            {  x: chartData.x, y: value },
                            false, 
                            elem.numPoints >= runtime.gridMaxPoints
                        );
                }
                
                i++;
                
                runtime.charts[orderKey].numPoints++;
                if(runtime.redrawCharts)
                    elem.chart.redraw();
            });
            
            oldChartData = chartData;
            
            runtime.refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);
        });
    }
    
    /* Build list of nodes that need to be retrieved */
    function buildRequiredDataList() {
        runtime.dataList = {};
        // Store an own id, because the property name is subject of reordering, thus destroying our mapping with runtime.charts <=> runtime.dataList
        var chartID = 0;
        $.each(runtime.charts, function(key, chart) {
            runtime.dataList[chartID] = chart.nodes;
            runtime.charts[key].chartID = chartID;
            chartID++;
        });
    }
    
    function loadLogStatistics(opts) {
        var tableStr = '';
        var logRequest = null;
        
        if(! opts.removeVariables)
            opts.removeVariables = false;
        if(! opts.limitTypes)
            opts.limitTypes = false;
        
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
        
        
        logRequest = $.get('server_status.php?'+url_query, 
            {   ajax_request: true,
                log_data: 1,
                type: opts.src,
                time_start: Math.round(opts.start / 1000),
                time_end: Math.round(opts.end / 1000),
                removeVariables: opts.removeVariables,
                limitTypes: opts.limitTypes
            },
            function(data) { 
                runtime.logData = $.parseJSON(data);
                
                if(runtime.logData.rows.length != 0) {
                    runtime.logDataCols = buildLogTable(runtime.logData);
                    
                    /* Show some stats in the dialog */
                    $('#loadingLogsDialog').html('<p>' + PMA_messages['strLogDataLoaded'] + '</p>');
                    $.each(runtime.logData.sum, function(key, value) {
                        key = key.charAt(0).toUpperCase() + key.slice(1).toLowerCase();
                        if(key == 'Total') key = '<b>' + key + '</b>';
                        $('#loadingLogsDialog').append(key + ': ' + value + '<br/>');
                    });
                    
                    /* Add filter options if more than a bunch of rows there to filter */
                    if(runtime.logData.numRows > 12) {
                        $('div#logTable').prepend(
                            '<fieldset id="logDataFilter">' +
                            '	<legend>' + PMA_messages['strFilters'] + '</legend>' +
                            '	<div class="formelement">' +
                            '		<label for="filterQueryText">' + PMA_messages['strFilterByWordRegexp'] + '</label>' +
                            '		<input name="filterQueryText" type="text" id="filterQueryText" style="vertical-align: baseline;" />' +
                            '	</div>' +
                            ((runtime.logData.numRows > 250) ? ' <div class="formelement"><button name="startFilterQueryText" id="startFilterQueryText">' + PMA_messages['strFilter'] + '</button></div>' : '') +
                            '	<div class="formelement">' +
                            '       <input type="checkbox" id="noWHEREData" name="noWHEREData" value="1" /> ' +
                            '       <label for="noWHEREData"> ' + PMA_messages['strIgnoreWhereAndGroup'] + '</label>' +
                            '   </div' +
                            '</fieldset>'
                        );
                        
                        $('div#logTable input#noWHEREData').change(function() {
                            filterQueries(true);
                        });
                        
                        //preg_replace('/\s+([^=]+)=(\d+|((\'|"|)(?U)(.+)(?<!\\\)\4(\s+|$)))/i',' $1={} ',$str);
                        
                        if(runtime.logData.numRows > 250) {
                            $('div#logTable button#startFilterQueryText').click(filterQueries);
                        } else {
                            $('div#logTable input#filterQueryText').keyup(filterQueries);
                        }
                        
                    }
                    
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
            
        function filterQueries(varFilterChange) {
            var odd_row=false, cell, textFilter;
            var val = $('div#logTable input#filterQueryText').val();
            
            if(val.length == 0) textFilter = null;
            else textFilter = new RegExp(val, 'i');
            
            var rowSum = 0, totalSum = 0;
            
            var i=0, q;
            var noVars = $('div#logTable input#noWHEREData').attr('checked');
            var equalsFilter = /([^=]+)=(\d+|((\'|"|).*?[^\\])\4((\s+)|$))/gi;
            var functionFilter = /([a-z0-9_]+)\(.+?\)/gi;
            var filteredQueries = {};
            var filteredQueriesLines = {};
            var hide = false;
            var queryColumnName = runtime.logDataCols[runtime.logDataCols.length - 2];
            var sumColumnName = runtime.logDataCols[runtime.logDataCols.length - 1];

            // We just assume the sql text is always in the second last column, and that the total count is right of it
            $('div#logTable table tbody tr td:nth-child(' + (runtime.logDataCols.length - 1) + ')').each(function() {
                if(varFilterChange && $(this).html().match(/^SELECT/i)) {
                    if(noVars) {
                        q = $(this).text().replace(equalsFilter, '$1=...$6').trim();
                        q = q.replace(functionFilter, ' $1(...)');
                        
                        // Js does not specify a limit on property name length, so we can abuse it as index :-)
                        if(filteredQueries[q]) {
                            filteredQueries[q] += parseInt($(this).next().text());
                            totalSum += parseInt($(this).next().text());
                            rowSum ++;
                            hide = true;
                        } else {
                            filteredQueries[q] = parseInt($(this).next().text());;
                            filteredQueriesLines[q] = i;
                            $(this).text(q);
                        }
                        
                    } else {
                        $(this).text(runtime.logData.rows[i][queryColumnName]);
                        $(this).next().text('' + runtime.logData.rows[i][sumColumnName]);
                    }
                }
                
                if(! hide && (textFilter != null && ! textFilter.exec($(this).text()))) hide = true;
                
                if(hide) {
                    $(this).parent().css('display','none');
                } else {
                    totalSum += parseInt($(this).next().text());
                    rowSum ++;
                    
                    odd_row = ! odd_row;    
                    $(this).parent().css('display','');
                    if(odd_row) {
                        $(this).parent().addClass('odd');
                        $(this).parent().removeClass('even');
                    } else {
                        $(this).parent().addClass('even');
                        $(this).parent().removeClass('odd');
                    }
                }
                
                hide = false;
                i++;
            });
            
            if(varFilterChange) {
                if(noVars) {
                    $.each(filteredQueriesLines, function(key,value) {
                        if(filteredQueries[value] <= 1) return;
                        
                        var numCol = $('div#logTable table tbody tr:nth-child(' + (value+1) + ')')
                                        .children(':nth-child(' + (runtime.logDataCols.length) + ')');
                        
                        numCol.text(filteredQueries[key]);
                    });
                }
                
                $('div#logTable table').trigger("update"); 
                setTimeout(function() {
                    
                    $('div#logTable table').trigger('sorton',[[[runtime.logDataCols.length - 1,1]]]);
                }, 0);
            }
            
            $('div#logTable table tfoot tr')
                .html('<th colspan="' + (runtime.logDataCols.length - 1) + '">' + 
                      PMA_messages['strSumRows'] + ' '+ rowSum +'<span style="float:right">' + 
                      PMA_messages['strTotal'] + ':</span></th><th align="right">' + totalSum + '</th>');
        }
    }
    
    function buildLogTable(data) {
        var rows = data.rows;
        var cols = new Array();
        
        var tableStr = '<table border="0" class="sortable">';
        
        var formatValue = function(name, value) {
            switch(name) {
                case 'user_host': 
                    return value.replace(/(\[.*?\])+/g,'');
            }
            return value;
        }
        
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
        
        tableStr += '</tbody><tfoot>';
        tableStr += '<tr><th colspan="' + (cols.length - 1) + '">' + PMA_messages['strSumRows'] + 
                    ' '+ data.numRows +'<span style="float:right">' + PMA_messages['strTotal'] + 
                    '</span></th><th align="right">' + data.sum.TOTAL + '</th></tr>';
        tableStr += '</tfoot></table>';
        
        $('#logTable').html(tableStr);
        
        // Append a tooltip to the count column, if there exist one
        if($('#logTable th:last').html() == '#') {
            $('#logTable th:last').append('&nbsp;<img class="qroupedQueryInfoIcon icon ic_b_docs" src="themes/dot.gif" alt="" />');
        
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
            sortList: [[cols.length - 1,1]],
            widgets: ['zebra']
        });
        
        $('div#logTable table thead th')
            .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');

        return cols;
    }
    
    function saveMonitor() {
        var gridCopy = {};
            
        $.each(runtime.charts, function(key, elem) {
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