$(function() {
    // Filters for status variables
    var textFilter=null;
    var alertFilter = false;
    var categoryFilter='';
    var odd_row=false;
    var text=''; // Holds filter text
    
    // Holds the tab contents when realtime charts are being displayed
    var tabCache = new Object();
    var tabStatus = new Object();
    
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
    // Realtime charting of variables (always the second link)
    $('.statuslinks a:nth-child(2)').click(function() {
        // ui-tabs-panel class is added by the jquery tabs feature
        var tab=$(this).parents('div.ui-tabs-panel');
        
        if(tabStatus[tab.attr('id')]!='realtime') {
            var series, title;
            var settings = {container:tab.attr('id')+"_chart_cnt"};
            
            switch(tab.attr('id')) {
                case 'statustabs_traffic':
                    break;
                case 'statustabs_queries':
                    settings.series = [{name: 'Queries per second',
                                         data: []
                                        }];
                    settings.differentialData = true;
                    settings.dataType = 'queries';
                    settings.chartTitle = 'Queries per second';
                    break;

                default:
                    return;
            }
            
            tabStatus[tab.attr('id')]='realtime';
            tabCache[tab.attr('id')]=tab.find('.tabInnerContent').html();
            tab.find('.tabInnerContent').html('<div style="width:700px; height:400px; padding-bottom:80px;" id="'+tab.attr('id')+'_chart_cnt"></div>');
            //alert(tab.find('.tabInnerContent #'+tab.attr('id')+'_chart_cnt').length);
            initChart(settings);
        } else {
            tab.find('.tabInnerContent').html(tabCache[tab.attr('id')]);
            tabStatus[tab.attr('id')]='data';
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
                        widgets: ['zebra']
                    });
                    
                $('#serverstatusqueriesdetails tr:first th')
                    .append('<img class="sortableIcon" src="'+pma_theme_image+'cleardot.gif" alt="">');
                    
                break;
            
            case 'statustabs_allvars':
                $('#serverstatusvariables').tablesorter({
                        sortList: [[0,0]],
                        widgets: ['zebra']
                    });
                    
                $('#serverstatusvariables tr:first th')
                    .append('<img class="sortableIcon" src="'+pma_theme_image+'cleardot.gif" alt="">');
                    
                break;
        }
    }
    
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
    
    function initChart(settings) {
        if(settings.differentialData == undefined)
            settings.differentialData = false;
        if(settings.seriesType == undefined)
            settings.seriesType = 'spline';
        if(settings.numPoints == undefined)
            settings.numPoints=30;
        
        var numLoadedPoints=0;
        
        chart = new Highcharts.Chart({
            chart: {
                renderTo: settings.container,
                defaultSeriesType: settings.seriesType,
                marginRight: 10,
                events: {
                    load: function() {
                        var thisChart = this;
                        // set up the updating of the chart each second
                        var lastValue=new Array();
                        
                        var addnewPoint = function() {
                            // Stop loading data, if the chart has been removed
                            if($('#'+settings.container).length==0) return;
        
                            $.get('server_status.php?'+url_query,{ajax_request:1, chart_data:1,type:settings.dataType},function(data) {
                                var splitData = data.split(',');
                                var x,y;
                                for(var i=0; i*2<=splitData.length; i++) {
                                    x=parseFloat(splitData[i*2]);
                                    y=parseFloat(splitData[i*2+1]);
                                    
                                    if(settings.differentialData) {
                                        if(lastValue[i]!=undefined && thisChart.series[i]!=undefined) {
                                            thisChart.series[i].addPoint([x,1000*(y-lastValue[i][1])/(x-lastValue[i][0])], true, numLoadedPoints++ >= settings.numPoints);
                                        }
                                    } else thisChart.series[i].addPoint([x,y], true, numLoadedPoints++ >= settings.numPoints);
                                    
                                    lastValue[i] = [x,y];
                                }
                                
                                setTimeout(addnewPoint, 2000);
                            });
                        }

                        addnewPoint();
                    }
                }
            },
            credits: {
                enabled:false
            },
            title: {
                text: settings.chartTitle
            },
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 150
            },
            yAxis: {
                title: {
                    text: 'Value'
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
            legend: {
                enabled: false
            },
            exporting: {
                enabled: false
            },
            series: settings.series
        });
        }
});