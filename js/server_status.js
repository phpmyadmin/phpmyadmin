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
    $('div.popupMenu a[href="#popupLink"]').click( function() {
        $(this).parent().find('.popupContent').show();
        return false;
    });
    
    $(document).click( function(event) {
        var $cnt = $('div.popupMenu .popupContent');
        var pos = $cnt.offset();
        
        // Hide if the mouseclick is outside the popupcontent
        if(event.pageX < pos.left || event.pageY < pos.top || event.pageX > pos.left + $cnt.outerWidth() || event.pageY > pos.top + $cnt.outerHeight())
            $cnt.hide();
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
        if($(this).val().length == 0) textFilter = null;
        else textFilter = new RegExp("(^|_)" + $(this).val(),'i');
        
        text = $(this).val();
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
                    .append('<img class="sortableIcon" src="' + pma_theme_image + 'cleardot.gif" alt="">');
                    
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
                    .append('<img class="sortableIcon" src="' + pma_theme_image + 'cleardot.gif" alt="">');
                    
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

    
    
    
    /**** Grid/Table charting implementation ****/
    
    // global settings
    $('div#statustabs_charting div.popupMenu input[name="setSize"]').click(function() {
        chartSize = {
            width: parseInt($('div#statustabs_charting div.popupMenu input[name="width"]').attr('value')) || 300,
            height: parseInt($('div#statustabs_charting div.popupMenu input[name="height"]').attr('value')) || 300
        };
        
        $.each(chartGrid, function(key, value) {
            value.chart.setSize(chartSize.width, chartSize.height);
        });
    });
    
    $('div#statustabs_charting div.popupMenu select[name="gridChartRefresh"]').change(function() {
        gridRefresh = this.value * 1000;
        clearTimeout(refreshTimeout);
        
        $.each(chartGrid, function(key, value) {
            value.chart.xAxis[0].setExtremes(
                new Date().getTime() - server_time_diff - gridMaxPoints * gridRefresh,
                new Date().getTime() - server_time_diff + gridRefresh,
                true
            );
        });
        
        refreshTimeout = setTimeout(refreshChartGrid, gridRefresh);
    });
    
    Highcharts.setOptions({
        lang: {
            settings: 'Settings',
            removeChart: 'Remove chart',
            editChart: 'Edit labels and series'
        }
    });
    
    var gridbuttons = { cogButton: {
        //enabled: true,
        symbol:  'url(' + pma_theme_image  + 's_cog.png)',
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
    } }
    

    $('a[href="#addNewChart"]').click(function() {
        $('div#addChartDialog').dialog({
            width:'auto',
            height:'auto',
            buttons: {
                'Add chart to grid': function() {
                    if(newChart.nodes.length == 0) return;
                    
                    var type = $('input[name="chartType"]').find(':checked').val();
                    if(type=='cpu' || type='memory');
                        newChart = presetCharts[type + '-' + server_os];
                    
                    newChart.title = $('input[name="chartTitle"]').attr('value');
                    // Add a cloned object to the chart grid
                    addChart($.extend(true, {}, newChart));
                    
                    newChart.nodes = [];
                    
                    $( this ).dialog( "close" );
                }
            },
            close: function() {
                newChart=null;
                $('span#clearSeriesLink').hide();
                $('#seriesPreview').html('');
            }
        });
        return false;
    });
    
    $('input[name="chartType"]').change(function() {
        $('#chartVariableSettings').toggle(this.checked && this.value == 'variable');
    });
    
    $('input[name="useDivisor"]').change(function() {
        $('span.divisorInput').toggle(this.checked);
    });
    
    $('select[name="varChartList"]').change(function () {
        if(this.selectedIndex!=0)
            $('#variableInput').attr('value',this.value);
    });
    
    $('a[href="#kibDivisor"]').click(function() {
        $('input[name="valueDivisor"]').attr('value',1024);
        return false;
    });
    $('a[href="#mibDivisor"]').click(function() {
        $('input[name="valueDivisor"]').attr('value',1024*1024);
        return false;
    });

    $('a[href="#submitClearSeries"]').click(function() {
        $('#seriesPreview').html('<i>None</i>');
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
        
        if(serie.name = 'Processes') serie.dataType='proc';
        
        if($('input[name="useDivisor"]').attr('checked')) 
            serie.valueDivisor = parseInt($('input[name="valueDivisor"]').attr('value'));
        
        var str = serie.display == 'differential' ? ', differential' : '';
        str += serie.valueDivisor ? ', divided by ' + serie.valueDivisor : '';
        $('#seriesPreview').append('- ' + serie.name + str + '<br>');
        
        newChart.nodes.push(serie);
        
        $('input#variableInput').attr('value','');
        $('input[name="differentialValue"]').attr('checked',true);
        $('input[name="useDivisor"]').attr('checked',false);
        $('input[name="useDivisor"]').trigger('change');
        $('select[name="varChartList"]').get(0).selectedIndex=0;
        
        $('span#clearSeriesLink').show();
        
        return false;
    });
    
    $("input#variableInput").autocomplete({
            source: variableNames
    });
    
    var chartGrid;
    /* Object that contains a list of required nodes that need to be retrieved from the server for chart updates */
    var requiredData = [];
    /* Refresh rate of all grids in ms */
    var gridRefresh = 5000;
    /* Saves the previous ajax response for differential values */
    var oldChartData = null;
    /* Stores the timeout handler so it can be cleared */
    var refreshTimeout = null;
    
    var gridMaxPoints = 20;
    
    var newChart = null;
    // Chart auto increment
    var chartAI = 0;
    
    var chartSize = { width: 300, height: 300 };
    
    var presetCharts = {
        'cpu-WINNT': {
            title: 'System CPU Usage',
            nodes: [{ dataType: 'cpu', name: 'loadavg'}]
        },
        'memory-WINNT': {
            title: 'System memory (MiB)',
            nodes: [
                { dataType: 'memory', name: 'MemTotal', valueDivisor: 1024 }, 
                { dataType: 'memory', name: 'MemUsed', valueDivisor: 1024  }, 
            ]
        },
        'swap-WINNT': {
            title: 'System swap (MiB)',
            nodes: [
                { dataType: 'memory', name: 'SwapTotal', valueDivisor: 1024  }, 
                { dataType: 'memory', name: 'SwapUsed', valueDivisor: 1024  }, 
            ]
        },
        'cpu-Linux': {
            title: 'System CPU Usage',
            nodes: [
                { dataType: 'cpu', 
                  name: 'none', 
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
            title: 'System memory (in MiB)',
            nodes: [
                { dataType: 'memory', name: 'MemUsed', valueDivisor: 1024  }, 
                { dataType: 'memory', name: 'Cached', valueDivisor: 1024  }, 
                { dataType: 'memory', name: 'Buffers', valueDivisor: 1024  },
                { dataType: 'memory', name: 'MemFree', valueDivisor: 1024  },
            ],
            settings: {
                chart: {
                    type: 'area'
                },
                plotOptions: {
                    area: {
                        stacking: 'percent'
                    }
                }
            }
        },
        'swap-Linux': {
            title: 'System swap (in MiB)',
            nodes: [
                { dataType: 'memory', name: 'SwapUsed', valueDivisor: 1024  }, 
                { dataType: 'memory', name: 'SwapCached', valueDivisor: 1024  }, 
                { dataType: 'memory', name: 'SwapFree', valueDivisor: 1024  }, 
            ],
            settings: {
                chart: {
                    type: 'area'
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
    chartGrid = {
        '0': presetCharts['cpu-'+server_os],
        '1': presetCharts['memory-'+server_os],
        '2': presetCharts['swap-'+server_os],
        '3': {  title: 'Questions',
                nodes: [{ dataType:'statusvar', name:'Questions', display: 'differential'}]
            }, 
         '4': {
             title: 'Connections / Processes',
             nodes: [ { dataType:'statusvar', name:'Connections', display: 'differential'},
                      { dataType:'proc', name:'Processes'}
                    ]
         },
         '5': {
             title: 'Traffic (in KiB)',
             nodes: [
                { dataType:'statusvar', name: 'Bytes_sent', display: 'differential', valueDivisor: 1024},
                { dataType:'statusvar', name: 'Bytes_received', display: 'differential', valueDivisor: 1024}
            ]
         }
    };
     
    initGrid();
    
    function initGrid() {
        var settings;
        var series;

        $.each(chartGrid, function(key, value) {
            addChart(value,true);
        });
        
        buildRequiredDataList();
        refreshChartGrid();
        
        $( "#chartGrid" ).sortable();
        $( "#chartGrid" ).disableSelection();
    }
    
    function addChart(chartObj, initialize) {
        series = [];
        for(var j=0; j<chartObj.nodes.length; j++)
            series.push(chartObj.nodes[j]);
        
        settings = {
            chart: {
                renderTo: 'gridchart' + chartAI,
                width: chartSize.width,
                height: chartSize.height
            },
            xAxis: {
                min: new Date().getTime() - server_time_diff - gridMaxPoints * gridRefresh,
                max: new Date().getTime() - server_time_diff + gridRefresh
            },
            yAxis: {
                title: {
                    text: ''
                }
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
            chartGrid[chartAI] = chartObj;
            $("#chartGrid").sortable('refresh');
            buildRequiredDataList();
        }
        
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
    }
    
    function refreshChartGrid() {
        /* Send to server */
        $.post('server_status.php?'+url_query, { ajax_request: true, chart_data: 1, type: 'chartgrid', requiredData: $.toJSON(requiredData) },function(data) {
            var chartData = $.parseJSON(data);
            var value;

            /* Update values in each graph */
            $.each(chartGrid, function(key, elem) {
                for(var j=0; j < elem.nodes.length; j++) {
                    value = chartData[key][j].y;

                    if(oldChartData==null) diff = chartData.x - elem.chart.xAxis[0].getExtremes().max;
                    else diff = parseInt(chartData.x - oldChartData.x);
                    
                    elem.chart.xAxis[0].setExtremes(
                        elem.chart.xAxis[0].getExtremes().min+diff, 
                        elem.chart.xAxis[0].getExtremes().max+diff, 
                        false
                    );
                    
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
                            j == elem.nodes.length - 1, 
                            elem.numPoints >= gridMaxPoints
                        );
                }
                
                chartGrid[key].numPoints++;
            });
            
            oldChartData = chartData;
            
            refreshTimeout = setTimeout(refreshChartGrid, gridRefresh);
        });
    }
    
    /* Build list of nodes that need to be retrieved */
    function buildRequiredDataList() {
        requiredData = {};
        $.each(chartGrid, function(key, chart) {
            requiredData[key] = chart.nodes;
        });
    }
});