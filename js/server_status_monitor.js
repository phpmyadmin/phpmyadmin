$(function() {
    // Show tab links
    $('div#statustabs_charting div.tabLinks').show();
    $('div#statustabs_charting img#loadingMonitorIcon').remove();
    // Codemirror is loaded on demand so we might need to initialize it
    if (! codemirror_editor) {
        var elm = $('#sqlquery');
        if (elm.length > 0 && typeof CodeMirror != 'undefined') {
            codemirror_editor = CodeMirror.fromTextArea(elm[0], { lineNumbers: true, matchBrackets: true, indentUnit: 4, mode: "text/x-mysql" });
        }
    }
    // Timepicker is loaded on demand so we need to initialize datetime fields from the 'load log' dialog
    $('div#logAnalyseDialog .datetimefield').each(function() {
        PMA_addDatepicker($(this));
    });
    
    /**** Monitor charting implementation ****/
    /* Saves the previous ajax response for differential values */
    var oldChartData = null;
    // Holds about to created chart
    var newChart = null;
    var chartSpacing;
    
    // Whenever the monitor object (runtime.charts) or the settings object (monitorSettings) 
    // changes in a way incompatible to the previous version, increase this number
    // It will reset the users monitor and settings object in his localStorage to the default configuration
    var monitorProtocolVersion = '1.0';

    // Runtime parameter of the monitor, is being fully set in initGrid()
    var runtime = {
        // Holds all visible charts in the grid
        charts: null,
        // Stores the timeout handler so it can be cleared
        refreshTimeout: null,
        // Stores the GET request to refresh the charts
        refreshRequest: null,
        // Chart auto increment
        chartAI: 0,
        // To play/pause the monitor
        redrawCharts: false,
        // Object that contains a list of nodes that need to be retrieved from the server for chart updates
        dataList: [],
        // Current max points per chart (needed for auto calculation)
        gridMaxPoints: 20,
        // displayed time frame
        xmin: -1,
        xmax: -1
    };
    
    var monitorSettings = null;

    var defaultMonitorSettings = {
        columns: 3,
        chartSize: { width: 295, height: 250 },
        // Max points in each chart. Settings it to 'auto' sets gridMaxPoints to (chartwidth - 40) / 12
        gridMaxPoints: 'auto',
        /* Refresh rate of all grid charts in ms */
        gridRefresh: 5000
    };

    // Allows drag and drop rearrange and print/edit icons on charts
    var editMode = false;
    
    /* List of preconfigured charts that the user may select */
    var presetCharts = {
        // Query cache efficiency
        'qce': {
            title: PMA_messages['strQueryCacheEfficiency'],
            nodes: [ {
                name: PMA_messages['strQueryCacheEfficiency'],
                dataPoints: [{type: 'statusvar', name: 'Qcache_hits'}, {type: 'statusvar', name: 'Com_select'}],
                unit: '%',
                transformFn: 'qce'
            } ]
        },
        // Query cache usage
        'qcu': {
            title: PMA_messages['strQueryCacheUsage'],
            nodes: [ {
                name: PMA_messages['strQueryCacheUsed'],
                dataPoints: [{type: 'statusvar', name: 'Qcache_free_memory'}, {type: 'servervar', name: 'query_cache_size'}],
                unit: '%',
                transformFn: 'qcu'
            } ]
        }
    };
    
    /* Add OS specific system info charts to the preset chart list */
    switch(server_os) {
    case 'WINNT': 
        $.extend(presetCharts, { 
            'cpu': {
                title: PMA_messages['strSystemCPUUsage'],
                nodes: [ { 
                    name: PMA_messages['strAverageLoad'], 
                    dataPoints: [{ type: 'cpu', name: 'loadavg'}], 
                    unit: '%'
                } ]
            },
            
            'memory': {
                title: PMA_messages['strSystemMemory'],
                nodes: [ {
                    name: PMA_messages['strTotalMemory'],
                    dataPoints: [{ type: 'memory', name: 'MemTotal' }],
                    valueDivisor: 1024,
                    unit: PMA_messages['strMiB']
                }, {
                    dataType: 'memory',
                      name: PMA_messages['strUsedMemory'],
                      dataPoints: [{ type: 'memory', name: 'MemUsed' }],
                      valueDivisor: 1024,
                      unit: PMA_messages['strMiB']
                } ]
            },
            
            'swap': {
                title: PMA_messages['strSystemSwap'],
                nodes: [ {
                    name: PMA_messages['strTotalSwap'],
                    dataPoints: [{ type: 'memory', name: 'SwapTotal' }],
                    valueDivisor: 1024,
                    unit: PMA_messages['strMiB']
                }, {
                    name: PMA_messages['strUsedSwap'],
                    dataPoints: [{ type: 'memory', name: 'SwapUsed' }],
                    valueDivisor: 1024,
                    unit: PMA_messages['strMiB']
                } ]
            }
        });
        break;
        
    case 'Linux':
        $.extend(presetCharts, {
            'cpu': {
                title: PMA_messages['strSystemCPUUsage'],
                nodes: [ {
                    name: PMA_messages['strAverageLoad'],
                    dataPoints: [{ type: 'cpu', name: 'irrelevant' }],
                    unit: '%',
                    transformFn: 'cpu-linux'
                } ]
            },
            'memory': {
                title: PMA_messages['strSystemMemory'],
                nodes: [
                    { name: PMA_messages['strUsedMemory'], dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024, unit: PMA_messages['strMiB'] },
                    { name: PMA_messages['strCachedMemory'], dataPoints: [{ type: 'memory', name: 'Cached' }],  valueDivisor: 1024, unit: PMA_messages['strMiB'] },
                    { name: PMA_messages['strBufferedMemory'], dataPoints: [{ type: 'memory', name: 'Buffers' }], valueDivisor: 1024, unit: PMA_messages['strMiB'] },
                    { name: PMA_messages['strFreeMemory'], dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024, unit: PMA_messages['strMiB'] }
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
            'swap': {
                title: PMA_messages['strSystemSwap'],
                nodes: [
                    { name: PMA_messages['strUsedSwap'], dataPoints: [{ type: 'memory', name: 'SwapUsed' }], valueDivisor: 1024, unit: PMA_messages['strMiB'] },
                    { name: PMA_messages['strCachedSwap'], dataPoints: [{ type: 'memory', name: 'SwapCached' }], valueDivisor: 1024, unit: PMA_messages['strMiB'] },
                    { name: PMA_messages['strFreeSwap'], dataPoints: [{ type: 'memory', name: 'SwapFree' }], valueDivisor: 1024, unit: PMA_messages['strMiB'] }
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
        });
        break;
    }

    // Default setting for the chart grid
    defaultChartGrid = {
        'c0': {  title: PMA_messages['strQuestions'],
                 nodes: [{name: PMA_messages['strQuestions'], dataPoints: [{ type: 'statusvar', name: 'Questions' }], display: 'differential' }]
        },
        'c1': {
                 title: PMA_messages['strChartConnectionsTitle'],
                 nodes: [ { name: PMA_messages['strConnections'], dataPoints: [{ type: 'statusvar', name: 'Connections' }], display: 'differential' },
                          { name: PMA_messages['strProcesses'], dataPoints: [{ type: 'proc', name: 'processes' }] } ]
        },
        'c2': {
                 title: PMA_messages['strTraffic'],
                 nodes: [
                    { name: PMA_messages['strBytesSent'], dataPoints: [{ type: 'statusvar', name: 'Bytes_sent' }], display: 'differential', valueDivisor: 1024, unit: PMA_messages['strKiB'] },
                    { name: PMA_messages['strBytesReceived'], dataPoints: [{ type: 'statusvar', name: 'Bytes_received' }], display: 'differential', valueDivisor: 1024, unit: PMA_messages['strKiB'] }
                 ]
         }
    };

    // Server is localhost => We can add cpu/memory/swap to the default chart
    if (server_db_isLocal) {
        defaultChartGrid['c3'] = presetCharts['cpu'];
        defaultChartGrid['c4'] = presetCharts['memory'];
        defaultChartGrid['c5'] = presetCharts['swap'];
    }

    /* Buttons that are on the top right corner of each chart */
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
                    editChart(this);
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
        if ($(this).attr('href') == '#endChartEditMode') {
            editMode = false;
        }

        // Icon graphics have zIndex 19, 20 and 21. Let's just hope nothing else has the same zIndex
        $('table#chartGrid div svg').find('*[zIndex=20], *[zIndex=21], *[zIndex=19]').toggle(editMode);

        $('a[href="#endChartEditMode"]').toggle(editMode);

        if (editMode) {
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
                    // Drop event. The drag child element is moved into the drop element
                    // and vice versa. So the parameters are switched.
                    drop: function(drag, drop, pos) {
                        var dragKey, dropKey, dropRender;
                        var dragRender = $(drag).children().first().attr('id');

                        if ($(drop).children().length > 0) {
                            dropRender = $(drop).children().first().attr('id');
                        }

                        // Find the charts in the array
                        $.each(runtime.charts, function(key, value) {
                            if (value.chart.options.chart.renderTo == dragRender) {
                                dragKey = key;
                            }
                            if (dropRender && value.chart.options.chart.renderTo == dropRender) {
                                dropKey = key;
                            }
                        });

                        // Case 1: drag and drop are charts -> Switch keys
                        if (dropKey) {
                            if (dragKey) {
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
                                    if (key != dropKey) {
                                        keys.push(key);
                                    }
                                });

                                keys.sort();

                                // Rebuilds all ids, with the dragged chart correctly inserted
                                for (var i = 0; i<keys.length; i++) {
                                    if (keys[i] == insertBefore) {
                                        newChartList['c' + (c++)] = runtime.charts[dropKey];
                                        insertBefore = -1; // Insert ok
                                    }
                                    newChartList['c' + (c++)] = runtime.charts[keys[i]];
                                }

                                // Not inserted => put at the end
                                if (insertBefore != -1) {
                                    newChartList['c' + (c++)] = runtime.charts[dropKey];
                                }

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
        $('table#chartGrid tr td').css('width', newSize.width + 'px');

        /* Reorder all charts that it fills all column cells */
        var numColumns;
        var $tr = $('table#chartGrid tr:first');
        var row = 0;
        while($tr.length != 0) {
            numColumns = 1;
            // To many cells in one row => put into next row
            $tr.find('td').each(function() {
                if (numColumns > monitorSettings.columns) {
                    if ($tr.next().length == 0) { 
                        $tr.after('<tr></tr>');
                    }
                    $tr.next().prepend($(this));
                }
                numColumns++;
            });

            // To little cells in one row => for each cell to little, move all cells backwards by 1
            if ($tr.next().length > 0) {
                var cnt = monitorSettings.columns - $tr.find('td').length;
                for (var i = 0; i < cnt; i++) {
                    $tr.append($tr.next().find('td:first'));
                    $tr.nextAll().each(function() {
                        if ($(this).next().length != 0) {
                            $(this).append($(this).next().find('td:first'));
                        }
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

        if (monitorSettings.gridMaxPoints == 'auto') {
            runtime.gridMaxPoints = Math.round((newSize.width - 40) / 12);
        }

        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;

        if (editMode) {
            $("#chartGrid").sortableTable('refresh');
        }

        saveMonitor(); // Save settings
    });

    $('div#statustabs_charting div.popupContent select[name="gridChartRefresh"]').change(function() {
        monitorSettings.gridRefresh = parseInt(this.value) * 1000;
        clearTimeout(runtime.refreshTimeout);

        if (runtime.refreshRequest) {
            runtime.refreshRequest.abort();
        }

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

            if (type == 'preset') {
                newChart = presetCharts[$('div#addChartDialog select[name="presetCharts"]').prop('value')];
            } else {
                // If user builds his own chart, it's being set/updated each time he adds a series
                // So here we only warn if he didn't add a series yet
                if (! newChart || ! newChart.nodes || newChart.nodes.length == 0) {
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
        };
        
        dlgButtons[PMA_messages['strClose']] = function() {
            newChart = null;
            $('span#clearSeriesLink').hide();
            $('#seriesPreview').html('');
            $(this).dialog("close");
        };
        
        var $presetList = $('div#addChartDialog select[name="presetCharts"]');
        if ($presetList.html().length == 0) {
            $.each(presetCharts, function(key, value) {
                $presetList.append('<option value="' + key + '">' + value.title + '</option>');
            });
            $presetList.change(function() {
                $('input#chartPreset').trigger('click');
                $('input[name="chartTitle"]').attr('value', presetCharts[$(this).prop('value')].title);
            });
        }
        
        $('div#addChartDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgButtons
        });

        $('div#addChartDialog #seriesPreview').html('<i>' + PMA_messages['strNone'] + '</i>');

        return false;
    });
    
    $('a[href="#exportMonitorConfig"]').click(function() {
        var gridCopy = {};

        $.each(runtime.charts, function(key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
        });
        
        var exportData = {
            monitorCharts: gridCopy,
            monitorSettings: monitorSettings
        };
        var $form;
        
        $('body').append($form = $('<form method="post" action="file_echo.php?' + url_query + '&filename=1" style="display:none;"></form>'));
        
        $form.append('<input type="hidden" name="monitorconfig" value="' + encodeURI($.toJSON(exportData)) + '">');
        $form.submit();
        $form.remove();
    });

    $('a[href="#importMonitorConfig"]').click(function() {
        $('div#emptyDialog').attr('title', 'Import monitor configuration');
        $('div#emptyDialog').html('Please select the file you want to import:<br/><form action="file_echo.php?' + url_query + '&import=1" method="post" enctype="multipart/form-data">' +
            '<input type="file" name="file"> <input type="hidden" name="import" value="1"> </form>');
        
        var dlgBtns = {};
        
        dlgBtns[PMA_messages['strImport']] = function() {
            var $iframe, $form;
            $('body').append($iframe = $('<iframe id="monitorConfigUpload" style="display:none;"></iframe>'));
            var d = $iframe[0].contentWindow.document;
            d.open(); d.close();
            mew = d;
            
            $iframe.load(function() {
                var json;

                // Try loading config
                try {
                    var data = $('body', $('iframe#monitorConfigUpload')[0].contentWindow.document).html();
                    // Chrome wraps around '<pre style="word-wrap: break-word; white-space: pre-wrap;">' to any text content -.-
                    json = $.secureEvalJSON(data.substring(data.indexOf("{"), data.lastIndexOf("}") + 1));
                } catch (err) {
                    alert(PMA_messages['strFailedParsingConfig']);
                    $('div#emptyDialog').dialog('close');
                    return;
                }
            
                // Basic check, is this a monitor config json?
                if (!json || ! json.monitorCharts || ! json.monitorCharts) {
                    alert(PMA_messages['strFailedParsingConfig']);
                    $('div#emptyDialog').dialog('close');
                    return;
                }
                
                // If json ok, try applying config
                try {
                    window.localStorage['monitorCharts'] = $.toJSON(json.monitorCharts);
                    window.localStorage['monitorSettings'] = $.toJSON(json.monitorSettings);
                    rebuildGrid();
                } catch(err) {
                    alert(PMA_messages['strFailedBuildingGrid']);
                    // If an exception is thrown, load default again
                    window.localStorage.removeItem('monitorCharts');
                    window.localStorage.removeItem('monitorSettings');
                    rebuildGrid();
                }
                
                $('div#emptyDialog').dialog('close');
            });
            
            $("body", d).append($form = $('div#emptyDialog').find('form'));
            $form.submit();
            $('div#emptyDialog').append('<img class="ajaxIcon" src="' + pmaThemeImage + 'ajax_clock_small.gif" alt="">');
        };
        
        dlgBtns[PMA_messages['strCancel']] = function() {
            $(this).dialog('close');
        };
        
        
        $('div#emptyDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgBtns
        });
    });

    $('a[href="#clearMonitorConfig"]').click(function() {
        window.localStorage.removeItem('monitorCharts');
        window.localStorage.removeItem('monitorSettings');
        window.localStorage.removeItem('monitorVersion');
        $(this).hide();
        rebuildGrid();
    });

    $('a[href="#pauseCharts"]').click(function() {
        runtime.redrawCharts = ! runtime.redrawCharts;
        if (! runtime.redrawCharts) {
            $(this).html(PMA_getImage('play.png') + ' ' + PMA_messages['strResumeMonitor']);
        } else {
            $(this).html(PMA_getImage('pause.png') + ' ' + PMA_messages['strPauseMonitor']);
            if (! runtime.charts) {
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
            if (getvars) {
                $.extend(vars, getvars);
            }

            $.get('server_status.php?' + url_query, vars,
                function(data) {
                    var logVars = $.parseJSON(data),
                        icon = PMA_getImage('s_success.png'), msg='', str='';

                    if (logVars['general_log'] == 'ON') {
                        if (logVars['slow_query_log'] == 'ON') {
                            msg = PMA_messages['strBothLogOn'];
                        } else {
                            msg = PMA_messages['strGenLogOn'];
                        }
                    }

                    if (msg.length == 0 && logVars['slow_query_log'] == 'ON') {
                        msg = PMA_messages['strSlowLogOn'];
                    }

                    if (msg.length == 0) {
                        icon = PMA_getImage('s_error.png');
                        msg = PMA_messages['strBothLogOff'];
                    }

                    str = '<b>' + PMA_messages['strCurrentSettings'] + '</b><br><div class="smallIndent">';
                    str += icon + msg + '<br />';

                    if (logVars['log_output'] != 'TABLE') {
                        str += PMA_getImage('s_error.png') + ' ' + PMA_messages['strLogOutNotTable'] + '<br />';
                    } else {
                        str += PMA_getImage('s_success.png') + ' ' + PMA_messages['strLogOutIsTable'] + '<br />';
                    }

                    if (logVars['slow_query_log'] == 'ON') {
                        if (logVars['long_query_time'] > 2) {
                            str += PMA_getImage('s_attention.png') + ' '
                                + $.sprintf(PMA_messages['strSmallerLongQueryTimeAdvice'], logVars['long_query_time'])
                                + '<br />';
                        }
                        
                        if (logVars['long_query_time'] < 2) {
                            str += PMA_getImage('s_success.png') + ' '
                                + $.sprintf(PMA_messages['strLongQueryTimeSet'], logVars['long_query_time'])
                                + '<br />';
                        }
                    }

                    str += '</div>';

                    if (is_superuser) {
                        str += '<p></p><b>' + PMA_messages['strChangeSettings'] + '</b>';
                        str += '<div class="smallIndent">';
                        str += PMA_messages['strSettingsAppliedGlobal'] + '<br/>';

                        var varValue = 'TABLE';
                        if (logVars['log_output'] == 'TABLE') {
                            varValue = 'FILE';
                        }
                        
                        str += '- <a class="set" href="#log_output-' + varValue + '">'
                            + $.sprintf(PMA_messages['strSetLogOutput'], varValue)
                            + ' </a><br />';

                        if (logVars['general_log'] != 'ON') {
                            str += '- <a class="set" href="#general_log-ON">'
                                + $.sprintf(PMA_messages['strEnableVar'], 'general_log')
                                + ' </a><br />';
                        } else {
                            str += '- <a class="set" href="#general_log-OFF">'
                                + $.sprintf(PMA_messages['strDisableVar'], 'general_log')
                                + ' </a><br />';
                        }

                        if (logVars['slow_query_log'] != 'ON') {
                            str += '- <a class="set" href="#slow_query_log-ON">'
                                +  $.sprintf(PMA_messages['strEnableVar'], 'slow_query_log')
                                + ' </a><br />';
                        } else {
                            str += '- <a class="set" href="#slow_query_log-OFF">'
                                +  $.sprintf(PMA_messages['strDisableVar'], 'slow_query_log')
                                + ' </a><br />';
                        }

                        varValue = 5;
                        if (logVars['long_query_time'] > 2) {
                            varValue = 1;
                        }

                        str += '- <a class="set" href="#long_query_time-' + varValue + '">'
                            + $.sprintf(PMA_messages['setSetLongQueryTime'], varValue)
                            + ' </a><br />';

                    } else {
                        str += PMA_messages['strNoSuperUser'] + '<br/>';
                    }

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
        };
        
        
        loadLogVars();

        return false;
    });

    $('input[name="chartType"]').change(function() {
        $('#chartVariableSettings').toggle(this.checked && this.value == 'variable');
        var title = $('input[name="chartTitle"]').attr('value');
        if (title == PMA_messages['strChartTitle'] 
           || title == $('label[for="' + $('input[name="chartTitle"]').data('lastRadio') + '"]').text()
        ) {
            $('input[name="chartTitle"]')
                .data('lastRadio', $(this).attr('id'))
                .attr('value', $('label[for="' + $(this).attr('id') + '"]').text());
        }

    });

    $('input[name="useDivisor"]').change(function() {
        $('span.divisorInput').toggle(this.checked);
    });
    $('input[name="useUnit"]').change(function() {
        $('span.unitInput').toggle(this.checked);
    });

    $('select[name="varChartList"]').change(function () {
        if (this.selectedIndex != 0) {
            $('#variableInput').attr('value', this.value);
        }
    });

    $('a[href="#kibDivisor"]').click(function() {
        $('input[name="valueDivisor"]').attr('value', 1024);
        $('input[name="valueUnit"]').attr('value', PMA_messages['strKiB']);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked', true);
        return false;
    });

    $('a[href="#mibDivisor"]').click(function() {
        $('input[name="valueDivisor"]').attr('value', 1024*1024);
        $('input[name="valueUnit"]').attr('value', PMA_messages['strMiB']);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked', true);
        return false;
    });

    $('a[href="#submitClearSeries"]').click(function() {
        $('#seriesPreview').html('<i>' + PMA_messages['strNone'] + '</i>');
        newChart = null;
        $('span#clearSeriesLink').hide();
    });

    $('a[href="#submitAddSeries"]').click(function() {
        if ($('input#variableInput').attr('value').length == 0) {
            return false;
        }
        
        if (newChart == null) {
            $('#seriesPreview').html('');

            newChart = {
                title: $('input[name="chartTitle"]').attr('value'),
                nodes: []
            };
        }

        var serie = {
            dataPoints: [{ type: 'statusvar', name: $('input#variableInput').attr('value') }],
            name: $('input#variableInput').attr('value'),
            display: $('input[name="differentialValue"]').attr('checked') ? 'differential' : ''
        };

        if (serie.dataPoint == 'Processes') {
            serie.dataType='proc';
        }

        if ($('input[name="useDivisor"]').attr('checked')) {
            serie.valueDivisor = parseInt($('input[name="valueDivisor"]').attr('value'));
        }

        if ($('input[name="useUnit"]').attr('checked')) {
            serie.unit = $('input[name="valueUnit"]').attr('value');
        }

        var str = serie.display == 'differential' ? ', ' + PMA_messages['strDifferential'] : '';
        str += serie.valueDivisor ? (', ' + $.sprintf(PMA_messages['strDividedBy'], serie.valueDivisor)) : '';
        str += serie.unit ? (', ' + PMA_messages['strUnit'] + ': ' + serie.unit) : '';

        $('#seriesPreview').append('- ' + serie.name + str + '<br>');

        newChart.nodes.push(serie);

        $('input#variableInput').attr('value', '');
        $('input[name="differentialValue"]').attr('checked', true);
        $('input[name="useDivisor"]').attr('checked', false);
        $('input[name="useUnit"]').attr('checked', false);
        $('input[name="useDivisor"]').trigger('change');
        $('input[name="useUnit"]').trigger('change');
        $('select[name="varChartList"]').get(0).selectedIndex = 0;

        $('span#clearSeriesLink').show();

        return false;
    });

    $("input#variableInput").autocomplete({
            source: variableNames
    });

    /* Initializes the monitor, called only once */
    function initGrid() {
        var settings;
        var series;

        /* Apply default values & config */
        if (window.localStorage) {
            if (window.localStorage['monitorCharts']) {
                runtime.charts = $.parseJSON(window.localStorage['monitorCharts']);
            }
            if (window.localStorage['monitorSettings']) {
                monitorSettings = $.parseJSON(window.localStorage['monitorSettings']);
            }

            $('a[href="#clearMonitorConfig"]').toggle(runtime.charts != null);

            if (runtime.charts != null && monitorProtocolVersion != window.localStorage['monitorVersion']) {
                $('div#emptyDialog').attr('title',PMA_messages['strIncompatibleMonitorConfig']);
                $('div#emptyDialog').html(PMA_messages['strIncompatibleMonitorConfigDescription']);

                var dlgBtns = {};
                dlgBtns[PMA_messages['strClose']] = function() { $(this).dialog('close'); };

                $('div#emptyDialog').dialog({ 
                    width: 400,
                    buttons: dlgBtns 
                });
            }            
        }

        if (runtime.charts == null) {
            runtime.charts = defaultChartGrid;
        }
        if (monitorSettings == null) {
            monitorSettings = defaultMonitorSettings;
        }

        $('select[name="gridChartRefresh"]').attr('value', monitorSettings.gridRefresh / 1000);
        $('select[name="chartColumns"]').attr('value', monitorSettings.columns);

        if (monitorSettings.gridMaxPoints == 'auto') {
            runtime.gridMaxPoints = Math.round((monitorSettings.chartSize.width - 40) / 12);
        } else {
            runtime.gridMaxPoints = monitorSettings.gridMaxPoints;
        }

        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;

        /* Calculate how much spacing there is between each chart */
        $('table#chartGrid').html('<tr><td></td><td></td></tr><tr><td></td><td></td></tr>');
        chartSpacing = {
            width: $('table#chartGrid td:nth-child(2)').offset().left 
                    - $('table#chartGrid td:nth-child(1)').offset().left,
            height: $('table#chartGrid tr:nth-child(2) td:nth-child(2)').offset().top 
                    - $('table#chartGrid tr:nth-child(1) td:nth-child(1)').offset().top
        };
        $('table#chartGrid').html('');
        
        /* Add all charts - in correct order */
        var keys = [];
        $.each(runtime.charts, function(key, value) {
            keys.push(key);
        });
        keys.sort();
        for (var i = 0; i<keys.length; i++)
            addChart(runtime.charts[keys[i]], true);

        /* Fill in missing cells */
        var numCharts = $('table#chartGrid .monitorChart').length;
        var numMissingCells = (monitorSettings.columns - numCharts % monitorSettings.columns) % monitorSettings.columns;
        for (var i = 0; i < numMissingCells; i++) {
            $('table#chartGrid tr:last').append('<td></td>');
        }

        // Empty cells should keep their size so you can drop onto them
        $('table#chartGrid tr td').css('width', chartSize().width + 'px');
        
        buildRequiredDataList();
        refreshChartGrid();
    }
    
    /* Destroys all monitor related resources */
    function destroyGrid() {
        if (runtime.charts) {
            $.each(runtime.charts, function(key, value) {
                try {
                    value.chart.destroy();
                } catch(err) {}
            });
        }
        
        try {
            runtime.refreshRequest.abort();
        } catch(err) {}
        try {    
            clearTimeout(runtime.refreshTimeout);
        } catch(err) {}
            
        $('table#chartGrid').html('');

        runtime.charts = null;
        runtime.chartAI = 0;
        monitorSettings = null;
    }
    
    /* Calls destroyGrid() and initGrid(), but before doing so it saves the chart 
     * data from each chart and restores it after the monitor is initialized again */
    function rebuildGrid() {
        var oldData = null;
        if (runtime.charts) {
            oldData = {};
            $.each(runtime.charts, function(key, chartObj) {
                for (var i = 0; i < chartObj.nodes.length; i++) {
                    oldData[chartObj.nodes[i].dataPoint] = [];
                    for (var j = 0; j < chartObj.chart.series[i].data.length; j++)
                        oldData[chartObj.nodes[i].dataPoint].push([chartObj.chart.series[i].data[j].x, chartObj.chart.series[i].data[j].y]);
                }
            });
        }
        
        destroyGrid();
        initGrid();
        
        if (oldData) {
            $.each(runtime.charts, function(key, chartObj) {
                for (var j = 0; j < chartObj.nodes.length; j++) {
                    if (oldData[chartObj.nodes[j].dataPoint]) {
                        chartObj.chart.series[j].setData(oldData[chartObj.nodes[j].dataPoint]);
                    }
                }
            });
        }      
    }

    /* Calculactes the dynamic chart size that depends on the column width */
    function chartSize() {
        var wdt = $('div#logTable').innerWidth() / monitorSettings.columns - (monitorSettings.columns - 1) * chartSpacing.width;
        return {
            width: wdt,
            height: 0.75 * wdt
        };
    }

    /* Adds a chart to the chart grid */
    function addChart(chartObj, initialize) {
        series = [];
        for (var j = 0; j<chartObj.nodes.length; j++)
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
                        if (editMode || $('#logAnalyseDialog').length == 0) {
                            return false;
                        }

                        var extremesObject = event.xAxis[0],
                            min = extremesObject.min,
                            max = extremesObject.max;

                        $('#logAnalyseDialog input[name="dateStart"]')
                            .attr('value', Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', new Date(min)));
                        $('#logAnalyseDialog input[name="dateEnd"]')
                            .attr('value', Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', new Date(max)));

                        var dlgBtns = { };

                        dlgBtns[PMA_messages['strFromSlowLog']] = function() {
                            loadLog('slow');
                            $(this).dialog("close");
                        };
                        
                        dlgBtns[PMA_messages['strFromGeneralLog']] = function() {
                            loadLog('general');
                            $(this).dialog("close");
                        };
                        
                        function loadLog(type) {
                            var dateStart = Date.parse($('#logAnalyseDialog input[name="dateStart"]').prop('value')) || min;
                            var dateEnd = Date.parse($('#logAnalyseDialog input[name="dateEnd"]').prop('value')) || max;

                            loadLogStatistics({
                                src: type,
                                start: dateStart,
                                end: dateEnd,
                                removeVariables: $('input#removeVariables').prop('checked'),
                                limitTypes: $('input#limitTypes').prop('checked')
                            });
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
                        var s = '<b>' + Highcharts.dateFormat('%H:%M:%S', this.x) + '</b>';

                        $.each(this.points, function(i, point) {
                            s += '<br/><span style="color:' + point.series.color + '">' + point.series.name + ':</span> ' +
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
            title: { text: chartObj.title }
        };

        if (chartObj.settings) {
            $.extend(true, settings, chartObj.settings);
        }

        if ($('#' + settings.chart.renderTo).length == 0) {
            var numCharts = $('table#chartGrid .monitorChart').length;

            if (numCharts == 0 || !( numCharts % monitorSettings.columns)) {
                $('table#chartGrid').append('<tr></tr>');
            }

            $('table#chartGrid tr:last').append('<td><div class="ui-state-default monitorChart" id="' + settings.chart.renderTo + '"></div></td>');
        }

        chartObj.chart = PMA_createChart(settings);
        chartObj.numPoints = 0;
        
        if (initialize != true) {
            runtime.charts['c' + runtime.chartAI] = chartObj;
            buildRequiredDataList();
        }

        // Edit, Print icon only in edit mode
        $('table#chartGrid div svg').find('*[zIndex=20], *[zIndex=21], *[zIndex=19]').toggle(editMode);

        runtime.chartAI++;
    }

    /* Opens a dialog that allows one to edit the title and series labels of the supplied chart */
    function editChart(chartObj) {
        var htmlnode = chartObj.options.chart.renderTo;
        if (! htmlnode ) {
            return;
        }
        
        var chart = null;
        var chartKey = null;
        $.each(runtime.charts, function(key, value) {
            if (value.chart.options.chart.renderTo == htmlnode) {
                chart = value;
                chartKey = key;
                return false;
            }
        });
        
        if (chart == null) {
            return;
        }

        var htmlStr = '<p><b>Chart title: </b> <br/> <input type="text" size="35" name="chartTitle" value="' + chart.title + '" />';
        htmlStr += '</p><p><b>Series:</b> </p><ol>';
        for (var i = 0; i<chart.nodes.length; i++) {
            htmlStr += '<li><i>' + chart.nodes[i].dataPoints[0].name  + ': </i><br/><input type="text" name="chartSerie-' + i + '" value="' + chart.nodes[i].name + '" /></li>';
        }
        
        dlgBtns = {};
        dlgBtns['Save'] = function() {
            runtime.charts[chartKey].title = $('div#emptyDialog input[name="chartTitle"]').attr('value');
            runtime.charts[chartKey].chart.setTitle({ text: runtime.charts[chartKey].title });
            
            $('div#emptyDialog input[name*="chartSerie"]').each(function() {
                var idx = $(this).attr('name').split('-')[1];
                runtime.charts[chartKey].nodes[idx].name = $(this).attr('value');
                runtime.charts[chartKey].chart.series[idx].name = $(this).attr('value');
            });
            
            $(this).dialog('close');
            saveMonitor();
        };
        dlgBtns['Cancel'] = function() {
            $(this).dialog('close');
        };
        
        $('div#emptyDialog').attr('title', 'Edit chart');
        $('div#emptyDialog').html(htmlStr + '</ol>');
        $('div#emptyDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgBtns
        });
    }
    
    /* Removes a chart from the grid */
    function removeChart(chartObj) {
        var htmlnode = chartObj.options.chart.renderTo;
        if (! htmlnode ) {
            return;
        }
        
        $.each(runtime.charts, function(key, value) {
            if (value.chart.options.chart.renderTo == htmlnode) {
                delete runtime.charts[key];
                return false;
            }
        });

        buildRequiredDataList();

        // Using settimeout() because clicking the remove link fires an onclick event
        // which throws an error when the chart is destroyed
        setTimeout(function() {
            chartObj.destroy();
            $('div#' + htmlnode).remove();
        }, 10);

        saveMonitor(); // Save settings
    }

    /* Called in regular intervalls, this function updates the values of each chart in the grid */
    function refreshChartGrid() {
        /* Send to server */
        runtime.refreshRequest = $.post('server_status.php?' + url_query, {
            ajax_request: true, 
            chart_data: 1, 
            type: 'chartgrid', 
            requiredData: $.toJSON(runtime.dataList) 
        }, function(data) {
            var chartData;
            try {
                chartData = $.parseJSON(data);
            } catch(err) {
                return serverResponseError();
            }
            var value, i = 0;
            var diff;
            
            /* Update values in each graph */
            $.each(runtime.charts, function(orderKey, elem) {
                var key = elem.chartID;
                // If newly added chart, we have no data for it yet
                if (! chartData[key]) {
                    return;
                }
                // Draw all series
                for (var j = 0; j < elem.nodes.length; j++) {
                    // Update x-axis
                    if (i == 0 && j == 0) {
                        if (oldChartData == null) {
                            diff = chartData.x - runtime.xmax;
                        } else {
                            diff = parseInt(chartData.x - oldChartData.x);
                        }

                        runtime.xmin += diff;
                        runtime.xmax += diff;
                    }

                    elem.chart.xAxis[0].setExtremes(runtime.xmin, runtime.xmax, false);

                    /* Calculate y value */
                    
                    // If transform function given, use it
                    if (elem.nodes[j].transformFn) {
                        value = chartValueTransform(
                            elem.nodes[j].transformFn,
                            chartData[key][j],
                            // Check if first iteration (oldChartData==null), or if newly added chart oldChartData[key]==null
                            (oldChartData == null || oldChartData[key] == null ? null : oldChartData[key][j])
                        );

                    // Otherwise use original value and apply differential and divisor if given,
                    // in this case we have only one data point per series - located at chartData[key][j][0]
                    } else {
                        value = parseFloat(chartData[key][j][0].value);

                        if (elem.nodes[j].display == 'differential') {
                            if (oldChartData == null || oldChartData[key] == null) { 
                                continue;
                            }
                            value -= oldChartData[key][j][0].value;
                        }

                        if (elem.nodes[j].valueDivisor) {
                            value = value / elem.nodes[j].valueDivisor;
                        }
                    }
                    
                    // Set y value, if defined
                    if (value != undefined) {
                        elem.chart.series[j].addPoint(
                            { x: chartData.x, y: value },
                            false,
                            elem.numPoints >= runtime.gridMaxPoints
                        );
                    }
                }

                i++;

                runtime.charts[orderKey].numPoints++;
                if (runtime.redrawCharts) {
                    elem.chart.redraw();
                }
            });

            oldChartData = chartData;

            runtime.refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);
        });
    }
    
    /* Function that supplies special value transform functions for chart values */
    function chartValueTransform(name, cur, prev) {        
        switch(name) {
        case 'cpu-linux':
            if (prev == null) {
                return undefined;
            }
            // cur and prev are datapoint arrays, but containing only 1 element for cpu-linux
            cur = cur[0], prev = prev[0];

            var diff_total = cur.busy + cur.idle - (prev.busy + prev.idle);
            var diff_idle = cur.idle - prev.idle;
            return 100 * (diff_total - diff_idle) / diff_total;

        // Query cache efficiency (%)
        case 'qce':
            if (prev == null) {
                return undefined;
            }
            // cur[0].value is Qcache_hits, cur[1].value is Com_select
            var diffQHits = cur[0].value - prev[0].value;
            // No NaN please :-)
            if (cur[1].value - prev[1].value == 0) return 0;

            return diffQHits / (cur[1].value - prev[1].value + diffQHits) * 100;

        // Query cache usage (%)
        case 'qcu':
            if (cur[1].value == 0) return 0;
            // cur[0].value is Qcache_free_memory, cur[1].value is query_cache_size
            return 100 - cur[0].value / cur[1].value * 100;

        }
        return undefined;
    }

    /* Build list of nodes that need to be retrieved from server.
     * It creates something like a stripped down version of the runtime.charts object.
     */
    function buildRequiredDataList() {
        runtime.dataList = {};
        // Store an own id, because the property name is subject of reordering, 
        // thus destroying our mapping with runtime.charts <=> runtime.dataList
        var chartID = 0;
        $.each(runtime.charts, function(key, chart) {
            runtime.dataList[chartID] = [];
            for(var i=0; i < chart.nodes.length; i++) {
                runtime.dataList[chartID][i] = chart.nodes[i].dataPoints;
            }
            runtime.charts[key].chartID = chartID;
            chartID++;
        });
    }

    /* Loads the log table data, generates the table and handles the filters */
    function loadLogStatistics(opts) {
        var tableStr = '';
        var logRequest = null;

        if (! opts.removeVariables) {
            opts.removeVariables = false;
        }
        if (! opts.limitTypes) {
            opts.limitTypes = false;
        }
        
        $('#emptyDialog').html(PMA_messages['strAnalysingLogs'] + 
                                ' <img class="ajaxIcon" src="' + pmaThemeImage + 
                                'ajax_clock_small.gif" alt="">');
        var dlgBtns = {};

        dlgBtns[PMA_messages['strCancelRequest']] = function() {
            if (logRequest != null) {
                logRequest.abort();
            }

            $(this).dialog("close");
        };

        $('#emptyDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgBtns
        });


        logRequest = $.get('server_status.php?' + url_query,
            {   ajax_request: true,
                log_data: 1,
                type: opts.src,
                time_start: Math.round(opts.start / 1000),
                time_end: Math.round(opts.end / 1000),
                removeVariables: opts.removeVariables,
                limitTypes: opts.limitTypes
            },
            function(data) { 
                var logData;
                try {
                    logData = $.parseJSON(data);
                } catch(err) {
                    return serverResponseError();
                }
                
                if (logData.rows.length != 0) {
                    runtime.logDataCols = buildLogTable(logData);

                    /* Show some stats in the dialog */
                    $('#emptyDialog').attr('title', PMA_messages['strLoadingLogs']);
                    $('#emptyDialog').html('<p>' + PMA_messages['strLogDataLoaded'] + '</p>');
                    $.each(logData.sum, function(key, value) {
                        key = key.charAt(0).toUpperCase() + key.slice(1).toLowerCase();
                        if (key == 'Total') {
                            key = '<b>' + key + '</b>';
                        }
                        $('#emptyDialog').append(key + ': ' + value + '<br/>');
                    });

                    /* Add filter options if more than a bunch of rows there to filter */
                    if (logData.numRows > 12) {
                        $('div#logTable').prepend(
                            '<fieldset id="logDataFilter">' +
                            '	<legend>' + PMA_messages['strFiltersForLogTable'] + '</legend>' +
                            '	<div class="formelement">' +
                            '		<label for="filterQueryText">' + PMA_messages['strFilterByWordRegexp'] + '</label>' +
                            '		<input name="filterQueryText" type="text" id="filterQueryText" style="vertical-align: baseline;" />' +
                            '	</div>' +
                            ((logData.numRows > 250) ? ' <div class="formelement"><button name="startFilterQueryText" id="startFilterQueryText">' + PMA_messages['strFilter'] + '</button></div>' : '') +
                            '	<div class="formelement">' +
                            '       <input type="checkbox" id="noWHEREData" name="noWHEREData" value="1" /> ' +
                            '       <label for="noWHEREData"> ' + PMA_messages['strIgnoreWhereAndGroup'] + '</label>' +
                            '   </div' +
                            '</fieldset>'
                        );

                        $('div#logTable input#noWHEREData').change(function() {
                            filterQueries(true);
                        });

                        if (logData.numRows > 250) {
                            $('div#logTable button#startFilterQueryText').click(filterQueries);
                        } else {
                            $('div#logTable input#filterQueryText').keyup(filterQueries);
                        }

                    }

                    var dlgBtns = {};
                    dlgBtns[PMA_messages['strJumpToTable']] = function() {
                        $(this).dialog("close");
                        $(document).scrollTop($('div#logTable').offset().top);
                    };
                    
                    $('#emptyDialog').dialog( "option", "buttons", dlgBtns);
                    
                } else {
                    $('#emptyDialog').html('<p>' + PMA_messages['strNoDataFound'] + '</p>');
                    
                    var dlgBtns = {};
                    dlgBtns[PMA_messages['strClose']] = function() { 
                        $(this).dialog("close"); 
                    };
                    
                    $('#emptyDialog').dialog( "option", "buttons", dlgBtns );
                }
            }
        );

        /* Handles the actions performed when the user uses any of the log table filters 
         * which are the filter by name and grouping with ignoring data in WHERE clauses
         * 
         * @param boolean Should be true when the users enabled or disabled to group queries ignoring data in WHERE clauses
        */
        function filterQueries(varFilterChange) {
            var odd_row = false, cell, textFilter;
            var val = $('div#logTable input#filterQueryText').val();

            if (val.length == 0) {
                textFilter = null;
            } else {
                textFilter = new RegExp(val, 'i');
            }
            
            var rowSum = 0, totalSum = 0, i = 0, q;
            var noVars = $('div#logTable input#noWHEREData').attr('checked');
            var equalsFilter = /([^=]+)=(\d+|((\'|"|).*?[^\\])\4((\s+)|$))/gi;
            var functionFilter = /([a-z0-9_]+)\(.+?\)/gi;
            var filteredQueries = {}, filteredQueriesLines = {};
            var hide = false, rowData;
            var queryColumnName = runtime.logDataCols[runtime.logDataCols.length - 2];
            var sumColumnName = runtime.logDataCols[runtime.logDataCols.length - 1];
            var isSlowLog = opts.src == 'slow';
            var columnSums = {};
            
            // For the slow log we have to count many columns (query_time, lock_time, rows_examined, rows_sent, etc.)
            var countRow = function(query, row) {
                var cells = row.match(/<td>(.*?)<\/td>/gi);
                if (!columnSums[query]) {
                    columnSums[query] = [0, 0, 0, 0];
                }

                // lock_time and query_time and displayed in timespan format
                columnSums[query][0] += timeToSec(cells[2].replace(/(<td>|<\/td>)/gi, ''));
                columnSums[query][1] += timeToSec(cells[3].replace(/(<td>|<\/td>)/gi, ''));
                // rows_examind and rows_sent are just numbers
                columnSums[query][2] += parseInt(cells[4].replace(/(<td>|<\/td>)/gi, ''));
                columnSums[query][3] += parseInt(cells[5].replace(/(<td>|<\/td>)/gi, ''));
            };
            
            // We just assume the sql text is always in the second last column, and that the total count is right of it
            $('div#logTable table tbody tr td:nth-child(' + (runtime.logDataCols.length - 1) + ')').each(function() {
                // If query is a SELECT and user enabled or disabled to group queries ignoring data in where statements, we 
                // need to re-calculate the sums of each row
                if (varFilterChange && $(this).html().match(/^SELECT/i)) {
                    if (noVars) {
                        // Group on => Sum up identical columns, and hide all but 1
                        
                        q = $(this).text().replace(equalsFilter, '$1=...$6').trim();
                        q = q.replace(functionFilter, ' $1(...)');

                        // Js does not specify a limit on property name length, so we can abuse it as index :-)
                        if (filteredQueries[q]) {
                            filteredQueries[q] += parseInt($(this).next().text());
                            totalSum += parseInt($(this).next().text());
                            hide = true;
                        } else {
                            filteredQueries[q] = parseInt($(this).next().text());;
                            filteredQueriesLines[q] = i;
                            $(this).text(q);
                        }
                        if (isSlowLog) {
                            countRow(q, $(this).parent().html());
                        }

                    } else {
                        // Group off: Restore original columns

                        rowData = $(this).parent().data('query');
                        // Restore SQL text
                        $(this).text(rowData[queryColumnName]);
                        // Restore total count
                        $(this).next().text(rowData[sumColumnName]);
                        // Restore slow log columns
                        if (isSlowLog) {
                            $(this).parent().children('td:nth-child(3)').text(rowData['query_time']);
                            $(this).parent().children('td:nth-child(4)').text(rowData['lock_time']);
                            $(this).parent().children('td:nth-child(5)').text(rowData['rows_sent']);
                            $(this).parent().children('td:nth-child(6)').text(rowData['rows_examined']);
                        }
                    }
                }

                // If not required to be hidden, do we need to hide because of a not matching text filter?
                if (! hide && (textFilter != null && ! textFilter.exec($(this).text()))) {
                    hide = true;
                }

                // Now display or hide this column
                if (hide) {
                    $(this).parent().css('display', 'none');
                } else {
                    totalSum += parseInt($(this).next().text());
                    rowSum++;

                    odd_row = ! odd_row;
                    $(this).parent().css('display', '');
                    if (odd_row) {
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
                       
            // We finished summarizing counts => Update count values of all grouped entries
            if (varFilterChange) {
                if (noVars) {
                    var numCol, row, $table = $('div#logTable table tbody');
                    $.each(filteredQueriesLines, function(key, value) {
                        if (filteredQueries[key] <= 1) {
                            return;
                        }
                        
                        row =  $table.children('tr:nth-child(' + (value + 1) + ')');
                        numCol = row.children(':nth-child(' + (runtime.logDataCols.length) + ')');
                        numCol.text(filteredQueries[key]);
                        
                        if (isSlowLog) {
                            row.children('td:nth-child(3)').text(secToTime(columnSums[key][0]));
                            row.children('td:nth-child(4)').text(secToTime(columnSums[key][1]));
                            row.children('td:nth-child(5)').text(columnSums[key][2]);
                            row.children('td:nth-child(6)').text(columnSums[key][3]);
                        }
                    });
                }
                
                $('div#logTable table').trigger("update"); 
                setTimeout(function() {                    
                    $('div#logTable table').trigger('sorton', [[[runtime.logDataCols.length - 1, 1]]]);
                }, 0);
            }

            // Display some stats at the bottom of the table
            $('div#logTable table tfoot tr')
                .html('<th colspan="' + (runtime.logDataCols.length - 1) + '">' +
                      PMA_messages['strSumRows'] + ' ' + rowSum + '<span style="float:right">' +
                      PMA_messages['strTotal'] + '</span></th><th class="right">' + totalSum + '</th>');
        }
    }

    /* Turns a timespan (12:12:12) into a number */
    function timeToSec(timeStr) {
        var time = timeStr.split(':');
        return parseInt(time[0]*3600) + parseInt(time[1]*60) + parseInt(time[2]);
    }
    
    /* Turns a number into a timespan (100 into 00:01:40) */
    function secToTime(timeInt) {
        hours = Math.floor(timeInt / 3600);
        timeInt -= hours*3600;
        minutes = Math.floor(timeInt / 60);
        timeInt -= minutes*60;
        
        if (hours < 10) {
            hours = '0' + hours;
        }
        if (minutes < 10) {
            minutes = '0' + minutes;
        }
        if (timeInt < 10) {
            timeInt = '0' + timeInt;
        }
        
        return hours + ':' + minutes + ':' + timeInt;
    }
    
    /* Constructs the log table out of the retrieved server data */
    function buildLogTable(data) {
        var rows = data.rows;
        var cols = new Array();
        var $table = $('<table class="sortable"></table>');
        var $tBody, $tRow, $tCell;

        $('#logTable').html($table);

        var formatValue = function(name, value) {
            switch(name) {
                case 'user_host':
                    return value.replace(/(\[.*?\])+/g, '');
            }
            return value;
        };
        
        for (var i = 0; i < rows.length; i++) {
            if (i == 0) {
                $.each(rows[0], function(key, value) {
                    cols.push(key);
                });
                $table.append( '<thead>' +
                               '<tr><th class="nowrap">' + cols.join('</th><th class="nowrap">') + '</th></tr>' +
                               '</thead>');

                $table.append($tBody = $('<tbody></tbody>'));
            }

            $tBody.append($tRow = $('<tr class="noclick"></tr>'));
            var cl = '';
            for (var j = 0; j < cols.length; j++) {
                // Assuming the query column is the second last
                if (j == cols.length - 2 && rows[i][cols[j]].match(/^SELECT/i)) {
                    $tRow.append($tCell = $('<td class="linkElem">' + formatValue(cols[j], rows[i][cols[j]]) + '</td>'));
                    $tCell.click(openQueryAnalyzer);
                } else
                    $tRow.append('<td>' + formatValue(cols[j], rows[i][cols[j]]) + '</td>');


                $tRow.data('query', rows[i]);
            }
        }

        $table.append('<tfoot>' +
                    '<tr><th colspan="' + (cols.length - 1) + '">' + PMA_messages['strSumRows'] +
                    ' ' + data.numRows + '<span style="float:right">' + PMA_messages['strTotal'] +
                    '</span></th><th class="right">' + data.sum.TOTAL + '</th></tr></tfoot>');

        // Append a tooltip to the count column, if there exist one
        if ($('#logTable th:last').html() == '#') {
            $('#logTable th:last').append('&nbsp;' + PMA_getImage('b_docs.png', '', {'class': 'qroupedQueryInfoIcon'}));

            var qtipContent = PMA_messages['strCountColumnExplanation'];
            if (groupInserts) {
                qtipContent += '<p>' + PMA_messages['strMoreCountColumnExplanation'] + '</p>';
            }

            $('img.qroupedQueryInfoIcon').qtip({
                content: qtipContent,
                position: {
                    corner: {
                        target: 'bottomMiddle',
                        tooltip: 'topRight'
                    }

                },
                hide: { delay: 1000 }
            });
        }

        $('div#logTable table').tablesorter({
            sortList: [[cols.length - 1, 1]],
            widgets: ['fast-zebra']
        });

        $('div#logTable table thead th')
            .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');

        return cols;
    }
        
    /* Opens the query analyzer dialog */
    function openQueryAnalyzer() {
        var rowData = $(this).parent().data('query');
        var query = rowData.argument || rowData.sql_text;

        query = PMA_SQLPrettyPrint(query);
        codemirror_editor.setValue(query);
        // Codemirror is bugged, it doesn't refresh properly sometimes. Following lines seem to fix that
        setTimeout(function() {
            codemirror_editor.refresh();
        },50);

        var profilingChart = null;
        var dlgBtns = {};
        
        dlgBtns[PMA_messages['strAnalyzeQuery']] = function() { 
            loadQueryAnalysis(rowData); 
        };
        dlgBtns[PMA_messages['strClose']] = function() { 
            if (profilingChart != null) {
                profilingChart.destroy();
            }
            $('div#queryAnalyzerDialog div.placeHolder').html('');
            codemirror_editor.setValue('');
            $(this).dialog("close");
        };

        $('div#queryAnalyzerDialog').dialog({
            width: 'auto',
            height: 'auto',
            resizable: false,
            buttons: dlgBtns
        });
    }
    
    /* Loads and displays the analyzed query data */
    function loadQueryAnalysis(rowData) {
        var db = rowData.db || '';
        
        $('div#queryAnalyzerDialog div.placeHolder').html(
            PMA_messages['strAnalyzing'] + ' <img class="ajaxIcon" src="' + 
            pmaThemeImage + 'ajax_clock_small.gif" alt="">');

        $.post('server_status.php?' + url_query, {
            ajax_request: true,
            query_analyzer: true,
            query: codemirror_editor.getValue(),
            database: db
        }, function(data) {
            data = $.parseJSON(data);
            var totalTime = 0;

            if (data.error) {
                $('div#queryAnalyzerDialog div.placeHolder').html('<div class="error">' + data.error + '</div>');
                return;
            }

            // Float sux, I'll use table :(
            $('div#queryAnalyzerDialog div.placeHolder')
                .html('<table width="100%" border="0"><tr><td class="explain"></td><td class="chart"></td></tr></table>');
            
            var explain = '<b>' + PMA_messages['strExplainOutput'] + '</b> ' + explain_docu;
            if (data.explain.length > 1) {
                explain += ' (';
                for (var i = 0; i < data.explain.length; i++) {
                    if (i > 0) {
                        explain += ', ';
                    }
                    explain += '<a href="#showExplain-' + i + '">' + i + '</a>';
                }
                explain += ')';
            }
            explain += '<p></p>';
            for (var i = 0; i < data.explain.length; i++) {
                explain += '<div class="explain-' + i + '"' + (i>0? 'style="display:none;"' : '' ) + '>';
                $.each(data.explain[i], function(key, value) {
                    value = (value == null)?'null':value;
                    
                    if (key == 'type' && value.toLowerCase() == 'all') {
                        value = '<span class="attention">' + value + '</span>';
                    }
                    if (key == 'Extra') {
                        value = value.replace(/(using (temporary|filesort))/gi, '<span class="attention">$1</span>');
                    }
                    explain += key + ': ' + value + '<br />';
                });
                explain += '</div>';
            }
            
            explain += '<p><b>' + PMA_messages['strAffectedRows'] + '</b> ' + data.affectedRows;

            $('div#queryAnalyzerDialog div.placeHolder td.explain').append(explain);
            
            $('div#queryAnalyzerDialog div.placeHolder a[href*="#showExplain"]').click(function() {
                var id = $(this).attr('href').split('-')[1];
                $(this).parent().find('div[class*="explain"]').hide();
                $(this).parent().find('div[class*="explain-' + id + '"]').show();
            });
            
            if (data.profiling) {
                var chartData = [];
                var numberTable = '<table class="queryNums"><thead><tr><th>' + PMA_messages['strStatus'] + '</th><th>' + PMA_messages['strTime'] + '</th></tr></thead><tbody>';
                var duration;

                for (var i = 0; i < data.profiling.length; i++) {
                    duration = parseFloat(data.profiling[i].duration);

                    chartData.push([data.profiling[i].state, duration]);
                    totalTime += duration;

                    numberTable += '<tr><td>' + data.profiling[i].state + ' </td><td> ' + PMA_prettyProfilingNum(duration, 2) + '</td></tr>';
                }
                numberTable += '<tr><td><b>' + PMA_messages['strTotalTime'] + '</b></td><td>' + PMA_prettyProfilingNum(totalTime, 2) + '</td></tr>';
                numberTable += '</tbody></table>';
                
                $('div#queryAnalyzerDialog div.placeHolder td.chart').append(
                    '<b>' + PMA_messages['strProfilingResults'] + ' ' + profiling_docu + '</b> ' +
                    '(<a href="#showNums">' + PMA_messages['strTable'] + '</a>, <a href="#showChart">' + PMA_messages['strChart'] + '</a>)<br/>' +
                    numberTable + ' <div id="queryProfiling"></div>');
                
                $('div#queryAnalyzerDialog div.placeHolder a[href="#showNums"]').click(function() {
                    $('div#queryAnalyzerDialog div#queryProfiling').hide();
                    $('div#queryAnalyzerDialog table.queryNums').show();
                    return false;
                });

                $('div#queryAnalyzerDialog div.placeHolder a[href="#showChart"]').click(function() {
                    $('div#queryAnalyzerDialog div#queryProfiling').show();
                    $('div#queryAnalyzerDialog table.queryNums').hide();
                    return false;
                });

                profilingChart = PMA_createProfilingChart(chartData, {
                    chart: {
                        renderTo: 'queryProfiling'
                    },
                    plotOptions: {
                        pie: {
                            size: '50%'
                        }
                    }
                });


                $('div#queryProfiling').resizable();
            }
        });
    }

    /* Saves the monitor to localstorage */
    function saveMonitor() {
        var gridCopy = {};

        $.each(runtime.charts, function(key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
        });

        if (window.localStorage) {
            window.localStorage['monitorCharts'] = $.toJSON(gridCopy);
            window.localStorage['monitorSettings'] = $.toJSON(monitorSettings);
            window.localStorage['monitorVersion'] = monitorProtocolVersion;
        }

        $('a[href="#clearMonitorConfig"]').show();
    }
});

// Run the monitor once loaded
$(function() {
    $('a[href="#pauseCharts"]').trigger('click');
});
