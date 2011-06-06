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

// Add a tablesorter parser to properly handle thousands seperated numbers and SI units
$(function() {
    jQuery.tablesorter.addParser({
        id: "fancyNumber",
        is: function(s) {
            return /^[0-9]?[0-9,\.]*\s?(k|M|G|T|%)?$/.test(s);
        },
        format: function(s) {
            var num = jQuery.tablesorter.formatFloat( s.replace(PMA_messages['strThousandsSeperator'],'').replace(PMA_messages['strDecimalSeperator'],'.') );
            var factor = 1;
            switch (s.charAt(s.length-1)) {
                case '%': factor = -2; break;
                // Todo: Complete this list (as well as in the regexp a few lines up)
                case 'k': factor = 3; break;
                case 'M': factor = 6; break;
                case 'G': factor = 9; break;
                case 'T': factor = 12; break;
            }
            return num*Math.pow(10,factor);
        },
        type: "numeric"
    });
});

$(function() {
    // Filters for status variables
    var textFilter=null;
    var alertFilter = false;
    var categoryFilter='';
    var odd_row=false;
    var text=''; // Holds filter text
    /* Chart configuration */
    // Amount of points the chart should hold
    var numMaxPoints=30;
    // Time between each refresh 
    var refreshRate=5000;
    
    // Defines what the tabs are currently displaying (realtime or data)
    var tabStatus = new Object();
    // active timeouts that refresh the charts
    var activeTimeouts = new Object();
    
    // Add tabs
    $('#serverStatusTabs').tabs({
        // Tab persistence
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        show: function() { menuResize(); }
    });
    
    // Fixes wrong tab height with floated elements. See also http://bugs.jqueryui.com/ticket/5601
    $(".ui-widget-content:not(.ui-tabs):not(.ui-helper-clearfix)").addClass("ui-helper-clearfix");
    
    // Load chart asynchronly so the page loads faster
    $.get($('#serverstatusquerieschart a').first().attr('href'),{ajax_request:1}, function(data) {
        $('#serverstatusquerieschart').html(data);
        // Init imagemap again
        imageMap.init();
    });
    
    // Table sorting
    initTableSorter('statustabs_queries');
    initTableSorter('statustabs_allvars');
    
    // Ajax reload of variables (always the first link)
    $('.statuslinks a:nth-child(1)').click(function() { return refreshHandler(this); });
    
    /** Realtime charting of variables (always the second link) **/
    $('.statuslinks a:nth-child(2)').click(function() {
        // ui-tabs-panel class is added by the jquery tabs feature
        var tab=$(this).parents('div.ui-tabs-panel');
        
        if(tabStatus[tab.attr('id')]!='realtime') {
            var series, title;
            var settings = new Object();
            
            switch(tab.attr('id')) {
                case 'statustabs_traffic':
                    settings = {
                        series: [{name:'Connections', data:[]},{name:'Processes', data:[]}],
                        title: {text:'Connections / Processes'},
                        refresh:{ type: 'proc',
                                  callback: function(chartObj, curVal, lastVal,numLoadedPoints) {
                                        chartObj.series[0].addPoint(
                                            { x:curVal.x, y:curVal.y_conn-lastVal.y_conn },
                                            true, numLoadedPoints >= numMaxPoints
                                        );
                                        chartObj.series[1].addPoint(
                                            { x:curVal.x, y:curVal.y_proc },
                                            true, numLoadedPoints >= numMaxPoints
                                        );                                            
                                    }
                                }
                    };
                    break;
                case 'statustabs_queries':
                    settings = {
                        series: [{name:'Issued queries since last refresh', data:[]}],
                        title: {text:'Issued queries'},
                        tooltip: { formatter:function() { return this.point.name; } },
                        refresh:{ type: 'queries',
                                  callback: function(chartObj, curVal, lastVal,numLoadedPoints) {
                                        chartObj.series[0].addPoint(
                                            { x:curVal.x,  y:curVal.y-lastVal.y, name:sortedQueriesPointInfo(curVal,lastVal) },
                                            true, numLoadedPoints >= numMaxPoints
                                        );
                                    }
                                }
                    };
                    break;

                default:
                    return;
            }
            
            if(!settings.chart) settings.chart = {};
            settings.chart.renderTo=tab.attr('id')+"_chart_cnt";
                        
            tab.find('.tabInnerContent')
                .hide()
                .after('<div style="width:700px; height:400px; padding-bottom:80px;" id="'+tab.attr('id')+'_chart_cnt"></div>');
            tabStatus[tab.attr('id')]='realtime';            
            initChart(settings);
            $(this).html(PMA_messages['strStaticData']);
            tab.find('.statuslinks a:nth-child(1)').hide();
        } else {
            clearTimeout(activeTimeouts[tab.attr('id')+"_chart_cnt"]);
            activeTimeouts[tab.attr('id')+"_chart_cnt"]=null;
            tab.find('.tabInnerContent').show();
            tab.find('div#'+tab.attr('id')+'_chart_cnt').remove();
            tabStatus[tab.attr('id')]='data';
            $(this).html(PMA_messages['strRealtimeChart']);
            tab.find('.statuslinks a:nth-child(1)').show();
        }
        return false; 
    });
    
    /* 3 Filtering functions */
    $('#filterAlert').change(function() {
        alertFilter = this.checked;
        filterVariables();
    });
    
    $('#filterText').keyup(function(e) {
        if($(this).val().length==0) textFilter=null;
        else textFilter = new RegExp("(^|_)"+$(this).val(),'i');
        text=$(this).val();
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
                tab.find('.tabInnerContent').html(data);
                initTooltips();
                break;
            case 'statustabs_queries':
                tab.find('.tabInnerContent').html(data);
                break;
            case 'statustabs_allvars':
                tab.find('.tabInnerContent').html(data);
                filterVariables();
                break;
        }
        
        initTableSorter(tab.attr('id'));        
    }
    
    function refreshHandler(element) {
        // ui-tabs-panel class is added by the jquery tabs feature
        var tab=$(element).parents('div.ui-tabs-panel');
        
        // Show ajax load icon
        $(element).find('img').show();

        $.get($(element).attr('href'),{ajax_request:1},function(data) {
            initTab(tab,data);
            $(element).find('img').hide();
        });
        
        tabStatus[tab.attr('id')]='data';
        
        return false;
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
                    .append('<img class="sortableIcon" src="'+pma_theme_image+'cleardot.gif" alt="">');
                    
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
                    .append('<img class="sortableIcon" src="'+pma_theme_image+'cleardot.gif" alt="">');
                    
                break;
        }
    }
    
    /* Filters the status variables by name/category/alert in the variables tab */
    function filterVariables() {
        var useful_links=0;
        var section = text;
        
        if(categoryFilter.length>0) section = categoryFilter;
        
        if(section.length>1) {
            $('#linkSuggestions span').each(function() {
                if($(this).attr('class').indexOf('status_'+section)!=-1) {
                    useful_links++;
                    $(this).css('display','');
                } else {
                    $(this).css('display','none');
                }
                
                
            });
        }
        
        if(useful_links>0) 
            $('#linkSuggestions').css('display','');
        else $('#linkSuggestions').css('display','none');
        
        odd_row=false;
        $('#serverstatusvariables th.name').each(function() {
            if((textFilter==null || textFilter.exec($(this).text()))
                && (!alertFilter || $(this).next().find('span.attention').length>0)
                && (categoryFilter.length==0 || $(this).parent().hasClass('s_'+categoryFilter))) {
                odd_row = !odd_row;                    
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
    
    function initChart(passedSettings) {
        var container = passedSettings.chart.renderTo;
        
        var settings = {
            chart: {
                defaultSeriesType: 'spline',
                marginRight: 10,
                events: {
                    load: function() {
                        var thisChart = this;
                        var lastValue=null;
                        var numLoadedPoints=0;
                        var otherSum=0;
                        
                        // No realtime updates for graphs that are being exported
                        if(thisChart.options.chart.forExport==true) return;
                                
                        var addnewPoint = function() {
                            $.get('server_status.php?'+url_query,{ajax_request:1, chart_data:1, type:passedSettings.refresh.type},function(data) {
                                if(activeTimeouts[container]==null) return;
                                
                                var chartData = jQuery.parseJSON(data);
                                var pointInfo='';
                                var i=0;
                                
                                chartData.x = parseInt(chartData.x);
                                chartData.y = parseInt(chartData.y);
                                
                                if(lastValue==null) lastValue = chartData;
                                
                                passedSettings.refresh.callback(thisChart,chartData,lastValue,numLoadedPoints)
                                    
                                lastValue = chartData;
                                numLoadedPoints++;
                                activeTimeouts[container] = setTimeout(addnewPoint, refreshRate);
                            });
                        }
                        
                        activeTimeouts[container] = setTimeout(addnewPoint, 0);
                    }
                }
            },
            credits: {
                enabled:false
            },
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 150
            },
            yAxis: {
                title: {
                    text: 'Total count'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                formatter: function() {
                        return '<b>'+ this.series.name +'</b><br/>'+
                        Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) +'<br/>'+ 
                        Highcharts.numberFormat(this.y, 2);
                }
            },
            exporting: {
                enabled: true
            },
            series: []
        }
        
        // Overwrite/Merge default settings with passedsettings
        $.extend(true,settings,passedSettings);

        chart = new Highcharts.Chart(settings);
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
                sumTotal+=value-lastQueries.pointInfo[key];
            }
        });
        var numQueries = queryKeys.length;
        var pointInfo = '<b>' + PMA_messages['strTotal'] + ': ' + sumTotal + '</b><br>';
        
        while(queryKeys.length > 0) {
            max=0;
            for(var i=0; i<queryKeys.length; i++) {
                if(queryValues[i] > max) {
                    max = queryValues[i];
                    maxIdx = i;
                }
            }
            if(numQueries > 8 && num>=6)
                sumOther+=queryValues[maxIdx];
            else pointInfo += queryKeys[maxIdx].substr(4).replace('_',' ') + ': ' + queryValues[maxIdx] + '<br>'; 
            
            queryKeys.splice(maxIdx,1);
            queryValues.splice(maxIdx,1);
            num++;
        }
        
        if(sumOther>0) 
            pointInfo += PMA_messages['strOther'] + ': ' + sumOther;

        return pointInfo;
    }
    
});