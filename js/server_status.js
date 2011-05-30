$(function() {
    var textFilter=null;
    var alertFilter = false;
    var categoryFilter='';
    var odd_row=false;
    var text='';
    
    // Process chart
    initChart();
    
    // Add tabs
    $('#serverStatusTabs').tabs({
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        show: function() { menuResize(); }
    });
    // Fixes wrong tab height with floated elements. See also http://bugs.jqueryui.com/ticket/5601
    $(".ui-widget-content:not(.ui-tabs):not(.ui-helper-clearfix)").addClass("ui-helper-clearfix");
    
    // Enable table sorting
    $('#serverstatusvariables').tablesorter({sortList: [[0,0]]});
    $('#serverstatusqueriesdetails').tablesorter();
    
    // Load chart asynchronly so the page loads faster
    $.get($('#serverstatusquerieschart a').first().attr('href'),{ajax_request:1}, function(data) {
        $('#serverstatusquerieschart').html(data);
        // Init imagemap again
        imageMap.init();
    });
    
    // Ajax reload of variables
    $('.statuslinks a').click(function() { return refreshHandler(this); });
    
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
                tab.html(data);
                initTooltips();
                $('#statustabs_traffic .statuslinks a').click(function() { return refreshHandler(this); });
                break;
            case 'statustabs_queries':
                tab.html(data);
                $('#statustabs_queries .statuslinks a').click(function() { return refreshHandler(this); });
                break;
            case 'statustabs_allvars':
                tab.find('#serverstatusvariables').html(data);
                filterVariables();
                tab.find('.statuslinks a img').hide();
                break;
        }
    }
    
    function refreshHandler(element) {
        // ui-tabs-panel class is added by the jquery tabs feature
        var tab=$(element).parents('div.ui-tabs-panel');
        
        // Show ajax load icon
        $(element).find('img').show();
        
        $.get($(element).attr('href'),{ajax_request:1},function(data) {
            initTab(tab,data);
        });
        
        return false;
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
    
    function initChart() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'container',
                defaultSeriesType: 'spline',
                marginRight: 10,
                events: {
                    load: function() {
        
                        // set up the updating of the chart each second
                        var series = this.series[0];
                        
                        var addnewPoint = function() {
                            $.get('server_status.php?'+url_query,{ajax_request:1, chart_data:1},function(data) {
                                var x=parseInt(data.split(',')[0]),
                                    y=parseInt(data.split(',')[1]);
                                
                                series.addPoint([x,y], true, true);
                                
                                setTimeout(addnewPoint, 2000);
                            });
                        }
                        
                        setTimeout(addnewPoint, 2000);
                    }
                }
            },
            title: {
                text: 'Processes'
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
            series: [{
                name: '# Processes',
                data: (function() {
                    // generate an array of random data
                    var data = [],
                        time = (new Date()).getTime(),
                        i;
                    for (i = -19; i <= 0; i++) {
                        data.push({
                            x: time + i * 2000,
                            y: 0 //Math.random()
                        });
                    }
                    return data;
                })()
            }]
        });
        }
});