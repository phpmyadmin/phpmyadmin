/* vim: set expandtab sw=4 ts=4 sts=4: */
var runtime = {},
    server_time_diff,
    server_os,
    is_superuser,
    server_db_isLocal,
    chartSize;
AJAX.registerOnload('server_status_monitor.js', function () {
    var $js_data_form = $('#js_data');
    server_time_diff  = new Date().getTime() - $js_data_form.find("input[name=server_time]").val();
    server_os =         $js_data_form.find("input[name=server_os]").val();
    is_superuser =      $js_data_form.find("input[name=is_superuser]").val();
    server_db_isLocal = $js_data_form.find("input[name=server_db_isLocal]").val();
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_status_monitor.js', function () {
    $('#emptyDialog').remove();
    $('#addChartDialog').remove();
    $('a.popupLink').unbind('click');
    $('body').unbind('click');
});
/**
 * Popup behaviour
 */
AJAX.registerOnload('server_status_monitor.js', function () {
    $('<div />')
        .attr('id', 'emptyDialog')
        .appendTo('#page_content');
    $('#addChartDialog')
        .appendTo('#page_content');

    $('a.popupLink').click(function () {
        var $link = $(this);
        $('div.' + $link.attr('href').substr(1))
            .show()
            .offset({ top: $link.offset().top + $link.height() + 5, left: $link.offset().left })
            .addClass('openedPopup');

        return false;
    });
    $('body').click(function (event) {
        $('div.openedPopup').each(function () {
            var $cnt = $(this);
            var pos = $cnt.offset();
            // Hide if the mouseclick is outside the popupcontent
            if (event.pageX < pos.left ||
                event.pageY < pos.top ||
                event.pageX > pos.left + $cnt.outerWidth() ||
                event.pageY > pos.top + $cnt.outerHeight()
            ) {
                $cnt.hide().removeClass('openedPopup');
            }
        });
    });
});

AJAX.registerTeardown('server_status_monitor.js', function () {
    $('a[href="#rearrangeCharts"], a[href="#endChartEditMode"]').unbind('click');
    $('div.popupContent select[name="chartColumns"]').unbind('change');
    $('div.popupContent select[name="gridChartRefresh"]').unbind('change');
    $('a[href="#addNewChart"]').unbind('click');
    $('a[href="#exportMonitorConfig"]').unbind('click');
    $('a[href="#importMonitorConfig"]').unbind('click');
    $('a[href="#clearMonitorConfig"]').unbind('click');
    $('a[href="#pauseCharts"]').unbind('click');
    $('a[href="#monitorInstructionsDialog"]').unbind('click');
    $('input[name="chartType"]').unbind('click');
    $('input[name="useDivisor"]').unbind('click');
    $('input[name="useUnit"]').unbind('click');
    $('select[name="varChartList"]').unbind('click');
    $('a[href="#kibDivisor"]').unbind('click');
    $('a[href="#mibDivisor"]').unbind('click');
    $('a[href="#submitClearSeries"]').unbind('click');
    $('a[href="#submitAddSeries"]').unbind('click');
    // $("input#variableInput").destroy();
    $('#chartPreset').unbind('click');
    $('#chartStatusVar').unbind('click');
    destroyGrid();
});

AJAX.registerOnload('server_status_monitor.js', function () {
    // Show tab links
    $('div.tabLinks').show();
    $('#loadingMonitorIcon').remove();
    // Codemirror is loaded on demand so we might need to initialize it
    if (! codemirror_editor) {
        var $elm = $('#sqlquery');
        if ($elm.length > 0 && typeof CodeMirror != 'undefined') {
            codemirror_editor = CodeMirror.fromTextArea(
                $elm[0],
                {
                    lineNumbers: true,
                    matchBrackets: true,
                    indentUnit: 4,
                    mode: "text/x-mysql",
                    lineWrapping: true
                }
            );
        }
    }
    // Timepicker is loaded on demand so we need to initialize
    // datetime fields from the 'load log' dialog
    $('#logAnalyseDialog .datetimefield').each(function () {
        PMA_addDatepicker($(this));
    });

    /**** Monitor charting implementation ****/
    /* Saves the previous ajax response for differential values */
    var oldChartData = null;
    // Holds about to be created chart
    var newChart = null;
    var chartSpacing;

    // Whenever the monitor object (runtime.charts) or the settings object
    // (monitorSettings) changes in a way incompatible to the previous version,
    // increase this number. It will reset the users monitor and settings object
    // in his localStorage to the default configuration
    var monitorProtocolVersion = '1.0';

    // Runtime parameter of the monitor, is being fully set in initGrid()
    runtime = {
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
        // Object that contains a list of nodes that need to be retrieved
        // from the server for chart updates
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
        // Max points in each chart. Settings it to 'auto' sets
        // gridMaxPoints to (chartwidth - 40) / 12
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
            title: PMA_messages.strQueryCacheEfficiency,
            series: [ {
                label: PMA_messages.strQueryCacheEfficiency
            } ],
            nodes: [ {
                dataPoints: [{type: 'statusvar', name: 'Qcache_hits'}, {type: 'statusvar', name: 'Com_select'}],
                transformFn: 'qce'
            } ],
            maxYLabel: 0
        },
        // Query cache usage
        'qcu': {
            title: PMA_messages.strQueryCacheUsage,
            series: [ {
                label: PMA_messages.strQueryCacheUsed
            } ],
            nodes: [ {
                dataPoints: [{type: 'statusvar', name: 'Qcache_free_memory'}, {type: 'servervar', name: 'query_cache_size'}],
                transformFn: 'qcu'
            } ],
            maxYLabel: 0
        }
    };

    // time span selection
    var selectionTimeDiff = [];
    var selectionStartX, selectionStartY, selectionEndX, selectionEndY;
    var drawTimeSpan = false;

    // chart tooltip
    var tooltipBox;

    /* Add OS specific system info charts to the preset chart list */
    switch (server_os) {
    case 'WINNT':
        $.extend(presetCharts, {
            'cpu': {
                title: PMA_messages.strSystemCPUUsage,
                series: [ {
                    label: PMA_messages.strAverageLoad
                } ],
                nodes: [ {
                    dataPoints: [{ type: 'cpu', name: 'loadavg'}]
                } ],
                maxYLabel: 100
            },

            'memory': {
                title: PMA_messages.strSystemMemory,
                series: [ {
                    label: PMA_messages.strTotalMemory,
                    fill: true
                }, {
                    dataType: 'memory',
                    label: PMA_messages.strUsedMemory,
                    fill: true
                } ],
                nodes: [{ dataPoints: [{ type: 'memory', name: 'MemTotal' }], valueDivisor: 1024 },
                        { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },

            'swap': {
                title: PMA_messages.strSystemSwap,
                series: [ {
                    label: PMA_messages.strTotalSwap,
                    fill: true
                }, {
                    label: PMA_messages.strUsedSwap,
                    fill: true
                } ],
                nodes: [{ dataPoints: [{ type: 'memory', name: 'SwapTotal' }]},
                        { dataPoints: [{ type: 'memory', name: 'SwapUsed' }]}
                ],
                maxYLabel: 0
            }
        });
        break;

    case 'Linux':
        $.extend(presetCharts, {
            'cpu': {
                title: PMA_messages.strSystemCPUUsage,
                series: [ {
                    label: PMA_messages.strAverageLoad
                } ],
                nodes: [{ dataPoints: [{ type: 'cpu', name: 'irrelevant' }], transformFn: 'cpu-linux'}],
                maxYLabel: 0
            },
            'memory': {
                title: PMA_messages.strSystemMemory,
                series: [
                    { label: PMA_messages.strBufferedMemory, fill: true},
                    { label: PMA_messages.strUsedMemory, fill: true},
                    { label: PMA_messages.strCachedMemory, fill: true},
                    { label: PMA_messages.strFreeMemory, fill: true}
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'Buffers' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'Cached' }],  valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: PMA_messages.strSystemSwap,
                series: [
                    { label: PMA_messages.strCachedSwap, fill: true},
                    { label: PMA_messages.strUsedSwap, fill: true},
                    { label: PMA_messages.strFreeSwap, fill: true}
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'SwapCached' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            }
        });
        break;

    case 'SunOS':
        $.extend(presetCharts, {
            'cpu': {
                title: PMA_messages.strSystemCPUUsage,
                series: [ {
                    label: PMA_messages.strAverageLoad
                } ],
                nodes: [ {
                    dataPoints: [{ type: 'cpu', name: 'loadavg'}]
                } ],
                maxYLabel: 0
            },
            'memory': {
                title: PMA_messages.strSystemMemory,
                series: [
                    { label: PMA_messages.strUsedMemory, fill: true },
                    { label: PMA_messages.strFreeMemory, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: PMA_messages.strSystemSwap,
                series: [
                    { label: PMA_messages.strUsedSwap, fill: true },
                    { label: PMA_messages.strFreeSwap, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'SwapUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            }
        });
        break;
    }

    // Default setting for the chart grid
    var defaultChartGrid = {
        'c0': {
            title: PMA_messages.strQuestions,
            series: [
                {label: PMA_messages.strQuestions}
            ],
            nodes: [
                {dataPoints: [{ type: 'statusvar', name: 'Questions' }], display: 'differential' }
            ],
            maxYLabel: 0
        },
        'c1': {
            title: PMA_messages.strChartConnectionsTitle,
            series: [
                {label: PMA_messages.strConnections},
                {label: PMA_messages.strProcesses}
            ],
            nodes: [
                {dataPoints: [{ type: 'statusvar', name: 'Connections' }], display: 'differential' },
                {dataPoints: [{ type: 'proc', name: 'processes' }] }
            ],
            maxYLabel: 0
        },
        'c2': {
            title: PMA_messages.strTraffic,
            series: [
                {label: PMA_messages.strBytesSent},
                {label: PMA_messages.strBytesReceived}
            ],
            nodes: [
                {dataPoints: [{ type: 'statusvar', name: 'Bytes_sent' }], display: 'differential', valueDivisor: 1024 },
                {dataPoints: [{ type: 'statusvar', name: 'Bytes_received' }], display: 'differential', valueDivisor: 1024 }
            ],
            maxYLabel: 0
        }
    };

    // Server is localhost => We can add cpu/memory/swap to the default chart
    if (server_db_isLocal) {
        defaultChartGrid['c3'] = presetCharts['cpu'];
        defaultChartGrid['c4'] = presetCharts['memory'];
        defaultChartGrid['c5'] = presetCharts['swap'];
    }

    $('a[href="#rearrangeCharts"], a[href="#endChartEditMode"]').click(function (event) {
        event.preventDefault();
        editMode = !editMode;
        if ($(this).attr('href') == '#endChartEditMode') {
            editMode = false;
        }

        $('a[href="#endChartEditMode"]').toggle(editMode);

        if (editMode) {
            // Close the settings popup
            $('div.popupContent').hide().removeClass('openedPopup');

            $("#chartGrid").sortableTable({
                ignoreRect: {
                    top: 8,
                    left: chartSize.width - 63,
                    width: 54,
                    height: 24
                }
            });

        } else {
            $("#chartGrid").sortableTable('destroy');
        }
        saveMonitor(); // Save settings
        return false;
    });

    // global settings
    $('div.popupContent select[name="chartColumns"]').change(function () {
        monitorSettings.columns = parseInt(this.value, 10);

        calculateChartSize();
        // Empty cells should keep their size so you can drop onto them
        $('#chartGrid tr td').css('width', chartSize.width + 'px');
        $('#chartGrid .monitorChart').css({
            width: chartSize.width + 'px',
            height: chartSize.height + 'px'
        });

        /* Reorder all charts that it fills all column cells */
        var numColumns;
        var $tr = $('#chartGrid tr:first');
        var row = 0;
        while ($tr.length !== 0) {
            numColumns = 1;
            // To many cells in one row => put into next row
            $tr.find('td').each(function () {
                if (numColumns > monitorSettings.columns) {
                    if ($tr.next().length === 0) {
                        $tr.after('<tr></tr>');
                    }
                    $tr.next().prepend($(this));
                }
                numColumns++;
            });

            // To little cells in one row => for each cell to little,
            // move all cells backwards by 1
            if ($tr.next().length > 0) {
                var cnt = monitorSettings.columns - $tr.find('td').length;
                for (var i = 0; i < cnt; i++) {
                    $tr.append($tr.next().find('td:first'));
                    $tr.nextAll().each(function () {
                        if ($(this).next().length !== 0) {
                            $(this).append($(this).next().find('td:first'));
                        }
                    });
                }
            }

            $tr = $tr.next();
            row++;
        }

        if (monitorSettings.gridMaxPoints == 'auto') {
            runtime.gridMaxPoints = Math.round((chartSize.width - 40) / 12);
        }

        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;

        if (editMode) {
            $("#chartGrid").sortableTable('refresh');
        }

        refreshChartGrid();
        saveMonitor(); // Save settings
    });

    $('div.popupContent select[name="gridChartRefresh"]').change(function () {
        monitorSettings.gridRefresh = parseInt(this.value, 10) * 1000;
        clearTimeout(runtime.refreshTimeout);

        if (runtime.refreshRequest) {
            runtime.refreshRequest.abort();
        }

        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        // fixing chart shift towards left on refresh rate change
        //runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;
        runtime.refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);

        saveMonitor(); // Save settings
    });

    $('a[href="#addNewChart"]').click(function (event) {
        event.preventDefault();
        var dlgButtons = { };

        dlgButtons[PMA_messages.strAddChart] = function () {
            var type = $('input[name="chartType"]:checked').val();

            if (type == 'preset') {
                newChart = presetCharts[$('#addChartDialog select[name="presetCharts"]').prop('value')];
            } else {
                // If user builds his own chart, it's being set/updated
                // each time he adds a series
                // So here we only warn if he didn't add a series yet
                if (! newChart || ! newChart.nodes || newChart.nodes.length === 0) {
                    alert(PMA_messages.strAddOneSeriesWarning);
                    return;
                }
            }

            newChart.title = $('input[name="chartTitle"]').val();
            // Add a cloned object to the chart grid
            addChart($.extend(true, {}, newChart));

            newChart = null;

            saveMonitor(); // Save settings

            $(this).dialog("close");
        };

        dlgButtons[PMA_messages.strClose] = function () {
            newChart = null;
            $('span#clearSeriesLink').hide();
            $('#seriesPreview').html('');
            $(this).dialog("close");
        };

        var $presetList = $('#addChartDialog select[name="presetCharts"]');
        if ($presetList.html().length === 0) {
            $.each(presetCharts, function (key, value) {
                $presetList.append('<option value="' + key + '">' + value.title + '</option>');
            });
            $presetList.change(function () {
                $('input[name="chartTitle"]').val(
                    $presetList.find(':selected').text()
                );
                $('#chartPreset').prop('checked', true);
            });
            $('#chartPreset').click(function () {
                $('input[name="chartTitle"]').val(
                    $presetList.find(':selected').text()
                );
            });
            $('#chartStatusVar').click(function () {
                $('input[name="chartTitle"]').val(
                    $('#chartSeries').find(':selected').text().replace(/_/g, " ")
                );
            });
            $('#chartSeries').change(function () {
                $('input[name="chartTitle"]').val(
                    $('#chartSeries').find(':selected').text().replace(/_/g, " ")
                );
            });
        }

        $('#addChartDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgButtons
        });

        $('#addChartDialog #seriesPreview').html('<i>' + PMA_messages.strNone + '</i>');

        return false;
    });

    $('a[href="#exportMonitorConfig"]').click(function (event) {
        event.preventDefault();
        var gridCopy = {};
        $.each(runtime.charts, function (key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
        });
        var exportData = {
            monitorCharts: gridCopy,
            monitorSettings: monitorSettings
        };
        $('<form />', {
            "class": "disableAjax",
            method: "post",
            action: "file_echo.php?" + PMA_commonParams.get('common_query') + "&filename=1",
            style: "display:none;"
        })
        .append(
            $('<input />', {
                type: "hidden",
                name: "monitorconfig",
                value: JSON.stringify(exportData)
            })
        )
        .appendTo('body')
        .submit()
        .remove();
    });

    $('a[href="#importMonitorConfig"]').click(function (event) {
        event.preventDefault();
        $('#emptyDialog').dialog({title: PMA_messages.strImportDialogTitle});
        $('#emptyDialog').html(PMA_messages.strImportDialogMessage + ':<br/><form action="file_echo.php?' + PMA_commonParams.get('common_query') + '&import=1" method="post" enctype="multipart/form-data">' +
            '<input type="file" name="file"> <input type="hidden" name="import" value="1"> </form>');

        var dlgBtns = {};

        dlgBtns[PMA_messages.strImport] = function () {
            var $iframe, $form;
            $('body').append($iframe = $('<iframe id="monitorConfigUpload" style="display:none;"></iframe>'));
            var d = $iframe[0].contentWindow.document;
            d.open();
            d.close();
            mew = d;

            $iframe.load(function () {
                var json;

                // Try loading config
                try {
                    var data = $('body', $('iframe#monitorConfigUpload')[0].contentWindow.document).html();
                    // Chrome wraps around '<pre style="word-wrap: break-word; white-space: pre-wrap;">' to any text content -.-
                    json = $.parseJSON(data.substring(data.indexOf("{"), data.lastIndexOf("}") + 1));
                } catch (err) {
                    alert(PMA_messages.strFailedParsingConfig);
                    $('#emptyDialog').dialog('close');
                    return;
                }

                // Basic check, is this a monitor config json?
                if (!json || ! json.monitorCharts || ! json.monitorCharts) {
                    alert(PMA_messages.strFailedParsingConfig);
                    $('#emptyDialog').dialog('close');
                    return;
                }

                // If json ok, try applying config
                try {
                    window.localStorage['monitorCharts'] = JSON.stringify(json.monitorCharts);
                    window.localStorage['monitorSettings'] = JSON.stringify(json.monitorSettings);
                    rebuildGrid();
                } catch (err) {
                    alert(PMA_messages.strFailedBuildingGrid);
                    // If an exception is thrown, load default again
                    window.localStorage.removeItem('monitorCharts');
                    window.localStorage.removeItem('monitorSettings');
                    rebuildGrid();
                }

                $('#emptyDialog').dialog('close');
            });

            $("body", d).append($form = $('#emptyDialog').find('form'));
            $form.submit();
            $('#emptyDialog').append('<img class="ajaxIcon" src="' + pmaThemeImage + 'ajax_clock_small.gif" alt="">');
        };

        dlgBtns[PMA_messages.strCancel] = function () {
            $(this).dialog('close');
        };


        $('#emptyDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgBtns
        });
    });

    $('a[href="#clearMonitorConfig"]').click(function (event) {
        event.preventDefault();
        window.localStorage.removeItem('monitorCharts');
        window.localStorage.removeItem('monitorSettings');
        window.localStorage.removeItem('monitorVersion');
        $(this).hide();
        rebuildGrid();
    });

    $('a[href="#pauseCharts"]').click(function (event) {
        event.preventDefault();
        runtime.redrawCharts = ! runtime.redrawCharts;
        if (! runtime.redrawCharts) {
            $(this).html(PMA_getImage('play.png') + ' ' + PMA_messages.strResumeMonitor);
        } else {
            $(this).html(PMA_getImage('pause.png') + ' ' + PMA_messages.strPauseMonitor);
            if (! runtime.charts) {
                initGrid();
                $('a[href="#settingsPopup"]').show();
            }
        }
        return false;
    });

    $('a[href="#monitorInstructionsDialog"]').click(function (event) {
        event.preventDefault();

        var $dialog = $('#monitorInstructionsDialog');

        $dialog.dialog({
            width: 595,
            height: 'auto'
        }).find('img.ajaxIcon').show();

        var loadLogVars = function (getvars) {
            var vars = { ajax_request: true, logging_vars: true };
            if (getvars) {
                $.extend(vars, getvars);
            }

            $.get('server_status_monitor.php?' + PMA_commonParams.get('common_query'), vars,
                function (data) {
                    var logVars;
                    if (data.success === true) {
                        logVars = data.message;
                    } else {
                        return serverResponseError();
                    }
                    var icon = PMA_getImage('s_success.png'), msg = '', str = '';

                    if (logVars['general_log'] == 'ON') {
                        if (logVars['slow_query_log'] == 'ON') {
                            msg = PMA_messages.strBothLogOn;
                        } else {
                            msg = PMA_messages.strGenLogOn;
                        }
                    }

                    if (msg.length === 0 && logVars['slow_query_log'] == 'ON') {
                        msg = PMA_messages.strSlowLogOn;
                    }

                    if (msg.length === 0) {
                        icon = PMA_getImage('s_error.png');
                        msg = PMA_messages.strBothLogOff;
                    }

                    str = '<b>' + PMA_messages.strCurrentSettings + '</b><br/><div class="smallIndent">';
                    str += icon + msg + '<br />';

                    if (logVars['log_output'] != 'TABLE') {
                        str += PMA_getImage('s_error.png') + ' ' + PMA_messages.strLogOutNotTable + '<br />';
                    } else {
                        str += PMA_getImage('s_success.png') + ' ' + PMA_messages.strLogOutIsTable + '<br />';
                    }

                    if (logVars['slow_query_log'] == 'ON') {
                        if (logVars['long_query_time'] > 2) {
                            str += PMA_getImage('s_attention.png') + ' ';
                            str += $.sprintf(PMA_messages.strSmallerLongQueryTimeAdvice, logVars['long_query_time']);
                            str += '<br />';
                        }

                        if (logVars['long_query_time'] < 2) {
                            str += PMA_getImage('s_success.png') + ' ';
                            str += $.sprintf(PMA_messages.strLongQueryTimeSet, logVars['long_query_time']);
                            str += '<br />';
                        }
                    }

                    str += '</div>';

                    if (is_superuser) {
                        str += '<p></p><b>' + PMA_messages.strChangeSettings + '</b>';
                        str += '<div class="smallIndent">';
                        str += PMA_messages.strSettingsAppliedGlobal + '<br/>';

                        var varValue = 'TABLE';
                        if (logVars['log_output'] == 'TABLE') {
                            varValue = 'FILE';
                        }

                        str += '- <a class="set" href="#log_output-' + varValue + '">';
                        str += $.sprintf(PMA_messages.strSetLogOutput, varValue);
                        str += ' </a><br />';

                        if (logVars['general_log'] != 'ON') {
                            str += '- <a class="set" href="#general_log-ON">';
                            str += $.sprintf(PMA_messages.strEnableVar, 'general_log');
                            str += ' </a><br />';
                        } else {
                            str += '- <a class="set" href="#general_log-OFF">';
                            str += $.sprintf(PMA_messages.strDisableVar, 'general_log');
                            str += ' </a><br />';
                        }

                        if (logVars['slow_query_log'] != 'ON') {
                            str += '- <a class="set" href="#slow_query_log-ON">';
                            str +=  $.sprintf(PMA_messages.strEnableVar, 'slow_query_log');
                            str += ' </a><br />';
                        } else {
                            str += '- <a class="set" href="#slow_query_log-OFF">';
                            str +=  $.sprintf(PMA_messages.strDisableVar, 'slow_query_log');
                            str += ' </a><br />';
                        }

                        varValue = 5;
                        if (logVars['long_query_time'] > 2) {
                            varValue = 1;
                        }

                        str += '- <a class="set" href="#long_query_time-' + varValue + '">';
                        str += $.sprintf(PMA_messages.setSetLongQueryTime, varValue);
                        str += ' </a><br />';

                    } else {
                        str += PMA_messages.strNoSuperUser + '<br/>';
                    }

                    str += '</div>';

                    $dialog.find('div.monitorUse').toggle(
                        logVars['log_output'] == 'TABLE' && (logVars['slow_query_log'] == 'ON' || logVars['general_log'] == 'ON')
                    );

                    $dialog.find('div.ajaxContent').html(str);
                    $dialog.find('img.ajaxIcon').hide();
                    $dialog.find('a.set').click(function () {
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

    $('input[name="chartType"]').change(function () {
        $('#chartVariableSettings').toggle(this.checked && this.value == 'variable');
        var title = $('input[name="chartTitle"]').val();
        if (title == PMA_messages.strChartTitle ||
            title == $('label[for="' + $('input[name="chartTitle"]').data('lastRadio') + '"]').text()
        ) {
            $('input[name="chartTitle"]')
                .data('lastRadio', $(this).attr('id'))
                .val($('label[for="' + $(this).attr('id') + '"]').text());
        }

    });

    $('input[name="useDivisor"]').change(function () {
        $('span.divisorInput').toggle(this.checked);
    });

    $('input[name="useUnit"]').change(function () {
        $('span.unitInput').toggle(this.checked);
    });

    $('select[name="varChartList"]').change(function () {
        if (this.selectedIndex !== 0) {
            $('#variableInput').val(this.value);
        }
    });

    $('a[href="#kibDivisor"]').click(function (event) {
        event.preventDefault();
        $('input[name="valueDivisor"]').val(1024);
        $('input[name="valueUnit"]').val(PMA_messages.strKiB);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked', true);
        return false;
    });

    $('a[href="#mibDivisor"]').click(function (event) {
        event.preventDefault();
        $('input[name="valueDivisor"]').val(1024 * 1024);
        $('input[name="valueUnit"]').val(PMA_messages.strMiB);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked', true);
        return false;
    });

    $('a[href="#submitClearSeries"]').click(function (event) {
        event.preventDefault();
        $('#seriesPreview').html('<i>' + PMA_messages.strNone + '</i>');
        newChart = null;
        $('#clearSeriesLink').hide();
    });

    $('a[href="#submitAddSeries"]').click(function (event) {
        event.preventDefault();
        if ($('#variableInput').val() === "") {
            return false;
        }

        if (newChart === null) {
            $('#seriesPreview').html('');

            newChart = {
                title: $('input[name="chartTitle"]').val(),
                nodes: [],
                series: [],
                maxYLabel: 0
            };
        }

        var serie = {
            dataPoints: [{ type: 'statusvar', name: $('#variableInput').val() }],
            display: $('input[name="differentialValue"]').prop('checked') ? 'differential' : ''
        };

        if (serie.dataPoints[0].name == 'Processes') {
            serie.dataPoints[0].type = 'proc';
        }

        if ($('input[name="useDivisor"]').prop('checked')) {
            serie.valueDivisor = parseInt($('input[name="valueDivisor"]').val(), 10);
        }

        if ($('input[name="useUnit"]').prop('checked')) {
            serie.unit = $('input[name="valueUnit"]').val();
        }

        var str = serie.display == 'differential' ? ', ' + PMA_messages.strDifferential : '';
        str += serie.valueDivisor ? (', ' + $.sprintf(PMA_messages.strDividedBy, serie.valueDivisor)) : '';
        str += serie.unit ? (', ' + PMA_messages.strUnit + ': ' + serie.unit) : '';

        var newSeries = {
            label: $('#variableInput').val().replace(/_/g, " ")
        };
        newChart.series.push(newSeries);
        $('#seriesPreview').append('- ' + newSeries.label + str + '<br/>');
        newChart.nodes.push(serie);
        $('#variableInput').val('');
        $('input[name="differentialValue"]').prop('checked', true);
        $('input[name="useDivisor"]').prop('checked', false);
        $('input[name="useUnit"]').prop('checked', false);
        $('input[name="useDivisor"]').trigger('change');
        $('input[name="useUnit"]').trigger('change');
        $('select[name="varChartList"]').get(0).selectedIndex = 0;

        $('#clearSeriesLink').show();

        return false;
    });

    $("#variableInput").autocomplete({
        source: variableNames
    });

    /* Initializes the monitor, called only once */
    function initGrid() {

        var i;

        /* Apply default values & config */
        if (window.localStorage) {
            if (window.localStorage['monitorCharts']) {
                runtime.charts = $.parseJSON(window.localStorage['monitorCharts']);
            }
            if (window.localStorage['monitorSettings']) {
                monitorSettings = $.parseJSON(window.localStorage['monitorSettings']);
            }

            $('a[href="#clearMonitorConfig"]').toggle(runtime.charts !== null);

            if (runtime.charts !== null && monitorProtocolVersion != window.localStorage['monitorVersion']) {
                $('#emptyDialog').dialog({title: PMA_messages.strIncompatibleMonitorConfig});
                $('#emptyDialog').html(PMA_messages.strIncompatibleMonitorConfigDescription);

                var dlgBtns = {};
                dlgBtns[PMA_messages.strClose] = function () { $(this).dialog('close'); };

                $('#emptyDialog').dialog({
                    width: 400,
                    buttons: dlgBtns
                });
            }
        }

        if (runtime.charts === null) {
            runtime.charts = defaultChartGrid;
        }
        if (monitorSettings === null) {
            monitorSettings = defaultMonitorSettings;
        }

        $('select[name="gridChartRefresh"]').val(monitorSettings.gridRefresh / 1000);
        $('select[name="chartColumns"]').val(monitorSettings.columns);

        if (monitorSettings.gridMaxPoints == 'auto') {
            runtime.gridMaxPoints = Math.round((monitorSettings.chartSize.width - 40) / 12);
        } else {
            runtime.gridMaxPoints = monitorSettings.gridMaxPoints;
        }

        runtime.xmin = new Date().getTime() - server_time_diff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - server_time_diff + monitorSettings.gridRefresh;

        /* Calculate how much spacing there is between each chart */
        $('#chartGrid').html('<tr><td></td><td></td></tr><tr><td></td><td></td></tr>');
        chartSpacing = {
            width: $('#chartGrid td:nth-child(2)').offset().left -
                $('#chartGrid td:nth-child(1)').offset().left,
            height: $('#chartGrid tr:nth-child(2) td:nth-child(2)').offset().top -
                $('#chartGrid tr:nth-child(1) td:nth-child(1)').offset().top
        };
        $('#chartGrid').html('');

        /* Add all charts - in correct order */
        var keys = [];
        $.each(runtime.charts, function (key, value) {
            keys.push(key);
        });
        keys.sort();
        for (i = 0; i < keys.length; i++) {
            addChart(runtime.charts[keys[i]], true);
        }

        /* Fill in missing cells */
        var numCharts = $('#chartGrid .monitorChart').length;
        var numMissingCells = (monitorSettings.columns - numCharts % monitorSettings.columns) % monitorSettings.columns;
        for (i = 0; i < numMissingCells; i++) {
            $('#chartGrid tr:last').append('<td></td>');
        }

        // Empty cells should keep their size so you can drop onto them
        calculateChartSize();
        $('#chartGrid tr td').css('width', chartSize.width + 'px');

        buildRequiredDataList();
        refreshChartGrid();
    }

    /* Calls destroyGrid() and initGrid(), but before doing so it saves the chart
     * data from each chart and restores it after the monitor is initialized again */
    function rebuildGrid() {
        var oldData = null;
        if (runtime.charts) {
            oldData = {};
            $.each(runtime.charts, function (key, chartObj) {
                for (var i = 0, l = chartObj.nodes.length; i < l; i++) {
                    oldData[chartObj.nodes[i].dataPoint] = [];
                    for (var j = 0, ll = chartObj.chart.series[i].data.length; j < ll; j++) {
                        oldData[chartObj.nodes[i].dataPoint].push([chartObj.chart.series[i].data[j].x, chartObj.chart.series[i].data[j].y]);
                    }
                }
            });
        }

        destroyGrid();
        initGrid();
    }

    /* Calculactes the dynamic chart size that depends on the column width */
    function calculateChartSize() {
        var panelWidth;
        if ($("body").height() > $(window).height()) { // has vertical scroll bar
            panelWidth = $('#logTable').innerWidth();
        } else {
            panelWidth = $('#logTable').innerWidth() - 10; // leave some space for vertical scroll bar
        }

        var wdt = (panelWidth - monitorSettings.columns * chartSpacing.width) / monitorSettings.columns;
        chartSize = {
            width: Math.floor(wdt),
            height: Math.floor(0.75 * wdt)
        };
    }

    /* Adds a chart to the chart grid */
    function addChart(chartObj, initialize) {

        var i;
        var settings = {
            title: escapeHtml(chartObj.title),
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
                    },
                    min: runtime.xmin,
                    max: runtime.xmax
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    tickInterval: 20
                }
            },
            seriesDefaults: {
                rendererOptions: {
                    smooth: true
                },
                showLine: true,
                lineWidth: 2
            },
            highlighter: {
                show: true
            }
        };

        if (settings.title === PMA_messages.strSystemCPUUsage ||
            settings.title === PMA_messages.strQueryCacheEfficiency
        ) {
            settings.axes.yaxis.tickOptions = {
                formatString: "%d %%"
            };
        } else if (settings.title === PMA_messages.strSystemMemory ||
            settings.title === PMA_messages.strSystemSwap
        ) {
            settings.stackSeries = true;
            settings.axes.yaxis.tickOptions = {
                formatter: $.jqplot.byteFormatter(2) // MiB
            };
        } else if (settings.title === PMA_messages.strTraffic) {
            settings.axes.yaxis.tickOptions = {
                formatter: $.jqplot.byteFormatter(1) // KiB
            };
        } else if (settings.title === PMA_messages.strQuestions ||
            settings.title === PMA_messages.strConnections
        ) {
            settings.axes.yaxis.tickOptions = {
                formatter: function (format, val) {
                    if (Math.abs(val) >= 1000000) {
                        return $.jqplot.sprintf("%.3g M", val / 1000000);
                    } else if (Math.abs(val) >= 1000) {
                        return $.jqplot.sprintf("%.3g k", val / 1000);
                    } else {
                        return $.jqplot.sprintf("%d", val);
                    }
                }
            };
        }

        settings.series = chartObj.series;

        if ($('#' + 'gridchart' + runtime.chartAI).length === 0) {
            var numCharts = $('#chartGrid .monitorChart').length;

            if (numCharts === 0 || (numCharts % monitorSettings.columns === 0)) {
                $('#chartGrid').append('<tr></tr>');
            }

            if (!chartSize) {
                calculateChartSize();
            }
            $('#chartGrid tr:last').append(
                '<td><div id="gridChartContainer' + runtime.chartAI + '" class="">' +
                '<div class="ui-state-default monitorChart"' +
                ' id="gridchart' + runtime.chartAI + '"' +
                ' style="width:' + chartSize.width + 'px; height:' + chartSize.height + 'px;"></div>' +
                '</div></td>'
            );
        }

        // Set series' data as [0,0], smooth lines won't plot with data array having null values.
        // also chart won't plot initially with no data and data comes on refreshChartGrid()
        var series = [];
        for (i in chartObj.series) {
            series.push([[0, 0]]);
        }

        // set Tooltip for each series
        for (i in settings.series) {
            settings.series[i].highlighter = {
                show: true,
                tooltipContentEditor: function (str, seriesIndex, pointIndex, plot) {
                    var j;
                    // TODO: move style to theme CSS
                    var tooltipHtml = '<div style="font-size:12px;background-color:#FFFFFF;' +
                        'opacity:0.95;filter:alpha(opacity=95);padding:5px;">';
                    // x value i.e. time
                    var timeValue = str.split(",")[0];
                    var seriesValue;
                    tooltipHtml += 'Time: ' + timeValue;
                    tooltipHtml += '<span style="font-weight:bold;">';
                    // Add y values to the tooltip per series
                    for (j in plot.series) {
                        // get y value if present
                        if (plot.series[j].data.length > pointIndex) {
                            seriesValue = plot.series[j].data[pointIndex][1];
                        } else {
                            return;
                        }
                        var seriesLabel = plot.series[j].label;
                        var seriesColor = plot.series[j].color;
                        // format y value
                        if (plot.series[0]._yaxis.tickOptions.formatter) {
                            // using formatter function
                            seriesValue = plot.series[0]._yaxis.tickOptions.formatter('%s', seriesValue);
                        } else if (plot.series[0]._yaxis.tickOptions.formatString) {
                            // using format string
                            seriesValue = $.sprintf(plot.series[0]._yaxis.tickOptions.formatString, seriesValue);
                        }
                        tooltipHtml += '<br /><span style="color:' + seriesColor + '">' +
                            seriesLabel + ': ' + seriesValue + '</span>';
                    }
                    tooltipHtml += '</span></div>';
                    return tooltipHtml;
                }
            };
        }

        chartObj.chart = $.jqplot('gridchart' + runtime.chartAI, series, settings);
        // remove [0,0] after plotting
        for (i in chartObj.chart.series) {
            chartObj.chart.series[i].data.shift();
        }

        var $legend = $('<div />').css('padding', '0.5em');
        for (i in chartObj.chart.series) {
            $legend.append(
                $('<div />').append(
                    $('<div>').css({
                        width: '1em',
                        height: '1em',
                        background: chartObj.chart.seriesColors[i]
                    }).addClass('floatleft')
                ).append(
                    $('<div>').text(
                        chartObj.chart.series[i].label
                    ).addClass('floatleft')
                ).append(
                    $('<div class="clearfloat">')
                ).addClass('floatleft')
            );
        }
        $('#gridchart' + runtime.chartAI)
            .parent()
            .append($legend);

        if (initialize !== true) {
            runtime.charts['c' + runtime.chartAI] = chartObj;
            buildRequiredDataList();
        }

        // time span selection
        $('#gridchart' + runtime.chartAI).bind('jqplotMouseDown', function (ev, gridpos, datapos, neighbor, plot) {
            drawTimeSpan = true;
            selectionTimeDiff.push(datapos.xaxis);
            if ($('#selection_box').length) {
                $('#selection_box').remove();
            }
            selectionBox = $('<div id="selection_box" style="z-index:1000;height:250px;position:absolute;background-color:#87CEEB;opacity:0.4;filter:alpha(opacity=40);pointer-events:none;">');
            $(document.body).append(selectionBox);
            selectionStartX = ev.pageX;
            selectionStartY = ev.pageY;
            selectionBox
                .attr({id: 'selection_box'})
                .css({
                    top: selectionStartY - gridpos.y,
                    left: selectionStartX
                })
                .fadeIn();
        });

        $('#gridchart' + runtime.chartAI).bind('jqplotMouseUp', function (ev, gridpos, datapos, neighbor, plot) {
            if (! drawTimeSpan || editMode) {
                return;
            }

            selectionTimeDiff.push(datapos.xaxis);

            if (selectionTimeDiff[1] <= selectionTimeDiff[0]) {
                selectionTimeDiff = [];
                return;
            }
            //get date from timestamp
            var min = new Date(Math.ceil(selectionTimeDiff[0]));
            var max = new Date(Math.ceil(selectionTimeDiff[1]));
            PMA_getLogAnalyseDialog(min, max);
            selectionTimeDiff = [];
            drawTimeSpan = false;
        });

        $('#gridchart' + runtime.chartAI).bind('jqplotMouseMove', function (ev, gridpos, datapos, neighbor, plot) {
            if (! drawTimeSpan || editMode) {
                return;
            }
            if (selectionStartX !== undefined) {
                $('#selection_box')
                    .css({
                        width: Math.ceil(ev.pageX - selectionStartX)
                    })
                    .fadeIn();
            }
        });

        $('#gridchart' + runtime.chartAI).bind('jqplotMouseLeave', function (ev, gridpos, datapos, neighbor, plot) {
            drawTimeSpan = false;
        });

        $(document.body).mouseup(function () {
            if ($('#selection_box').length) {
                selectionBox.remove();
            }
        });

        // Edit, Print icon only in edit mode
        $('#chartGrid div svg').find('*[zIndex=20], *[zIndex=21], *[zIndex=19]').toggle(editMode);

        runtime.chartAI++;
    }

    function PMA_getLogAnalyseDialog(min, max) {
        var $dateStart = $('#logAnalyseDialog input[name="dateStart"]');
        var $dateEnd = $('#logAnalyseDialog input[name="dateEnd"]');
        $dateStart.prop("readonly", true);
        $dateEnd.prop("readonly", true);

        var dlgBtns = { };

        dlgBtns[PMA_messages.strFromSlowLog] = function () {
            loadLog('slow', min, max);
            $(this).dialog("close");
        };

        dlgBtns[PMA_messages.strFromGeneralLog] = function () {
            loadLog('general', min, max);
            $(this).dialog("close");
        };

        $('#logAnalyseDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgBtns
        });

        PMA_addDatepicker($dateStart, 'datetime', {
            showMillisec: false,
            showMicrosec: false,
            timeFormat: 'HH:mm:ss'
        });
        PMA_addDatepicker($dateEnd, 'datetime', {
            showMillisec: false,
            showMicrosec: false,
            timeFormat: 'HH:mm:ss'
        });
        $('#logAnalyseDialog input[name="dateStart"]').datepicker('setDate', min);
        $('#logAnalyseDialog input[name="dateEnd"]').datepicker('setDate', max);
    }

    function loadLog(type, min, max) {
        var dateStart = Date.parse($('#logAnalyseDialog input[name="dateStart"]').datepicker('getDate')) || min;
        var dateEnd = Date.parse($('#logAnalyseDialog input[name="dateEnd"]').datepicker('getDate')) || max;

        loadLogStatistics({
            src: type,
            start: dateStart,
            end: dateEnd,
            removeVariables: $('#removeVariables').prop('checked'),
            limitTypes: $('#limitTypes').prop('checked')
        });
    }

    /* Called in regular intervalls, this function updates the values of each chart in the grid */
    function refreshChartGrid() {
        /* Send to server */
        runtime.refreshRequest = $.post('server_status_monitor.php?' + PMA_commonParams.get('common_query'), {
            ajax_request: true,
            chart_data: 1,
            type: 'chartgrid',
            requiredData: JSON.stringify(runtime.dataList)
        }, function (data) {
            var chartData;
            if (data.success === true) {
                chartData = data.message;
            } else {
                return serverResponseError();
            }
            var value, i = 0;
            var diff;
            var total;

            /* Update values in each graph */
            $.each(runtime.charts, function (orderKey, elem) {
                var key = elem.chartID;
                // If newly added chart, we have no data for it yet
                if (! chartData[key]) {
                    return;
                }
                // Draw all series
                total = 0;
                for (var j = 0; j < elem.nodes.length; j++) {
                    // Update x-axis
                    if (i === 0 && j === 0) {
                        if (oldChartData === null) {
                            diff = chartData.x - runtime.xmax;
                        } else {
                            diff = parseInt(chartData.x - oldChartData.x, 10);
                        }

                        runtime.xmin += diff;
                        runtime.xmax += diff;
                    }

                    //elem.chart.xAxis[0].setExtremes(runtime.xmin, runtime.xmax, false);
                    /* Calculate y value */

                    // If transform function given, use it
                    if (elem.nodes[j].transformFn) {
                        value = chartValueTransform(
                            elem.nodes[j].transformFn,
                            chartData[key][j],
                            // Check if first iteration (oldChartData==null), or if newly added chart oldChartData[key]==null
                            (
                                oldChartData === null ||
                                oldChartData[key] === null ||
                                oldChartData[key] === undefined ? null : oldChartData[key][j]
                            )
                        );

                    // Otherwise use original value and apply differential and divisor if given,
                    // in this case we have only one data point per series - located at chartData[key][j][0]
                    } else {
                        value = parseFloat(chartData[key][j][0].value);

                        if (elem.nodes[j].display == 'differential') {
                            if (oldChartData === null ||
                                oldChartData[key] === null ||
                                oldChartData[key] === undefined
                            ) {
                                continue;
                            }
                            value -= oldChartData[key][j][0].value;
                        }

                        if (elem.nodes[j].valueDivisor) {
                            value = value / elem.nodes[j].valueDivisor;
                        }
                    }

                    // Set y value, if defined
                    if (value !== undefined) {
                        elem.chart.series[j].data.push([chartData.x, value]);
                        if (value > elem.maxYLabel) {
                            elem.maxYLabel = value;
                        } else if (elem.maxYLabel === 0) {
                            elem.maxYLabel = 0.5;
                        }
                        // free old data point values and update maxYLabel
                        if (elem.chart.series[j].data.length > runtime.gridMaxPoints &&
                            elem.chart.series[j].data[0][0] < runtime.xmin
                        ) {
                            // check if the next freeable point is highest
                            if (elem.maxYLabel <= elem.chart.series[j].data[0][1]) {
                                elem.chart.series[j].data.splice(0, elem.chart.series[j].data.length - runtime.gridMaxPoints);
                                elem.maxYLabel = getMaxYLabel(elem.chart.series[j].data);
                            } else {
                                elem.chart.series[j].data.splice(0, elem.chart.series[j].data.length - runtime.gridMaxPoints);
                            }
                        }
                        if (elem.title === PMA_messages.strSystemMemory ||
                            elem.title === PMA_messages.strSystemSwap
                        ) {
                            total += value;
                        }
                    }
                }

                // update chart options
                // keep ticks number/positioning consistent while refreshrate changes
                var tickInterval = (runtime.xmax - runtime.xmin) / 5;
                elem.chart['axes']['xaxis'].ticks = [(runtime.xmax - tickInterval * 4),
                    (runtime.xmax - tickInterval * 3), (runtime.xmax - tickInterval * 2),
                    (runtime.xmax - tickInterval), runtime.xmax];

                if (elem.title !== PMA_messages.strSystemCPUUsage &&
                    elem.title !== PMA_messages.strQueryCacheEfficiency &&
                    elem.title !== PMA_messages.strSystemMemory &&
                    elem.title !== PMA_messages.strSystemSwap
                ) {
                    elem.chart['axes']['yaxis']['max'] = Math.ceil(elem.maxYLabel * 1.1);
                    elem.chart['axes']['yaxis']['tickInterval'] = Math.ceil(elem.maxYLabel * 1.1 / 5);
                } else if (elem.title === PMA_messages.strSystemMemory ||
                    elem.title === PMA_messages.strSystemSwap
                ) {
                    elem.chart['axes']['yaxis']['max'] = Math.ceil(total * 1.1 / 100) * 100;
                    elem.chart['axes']['yaxis']['tickInterval'] = Math.ceil(total * 1.1 / 5);
                }
                i++;

                if (runtime.redrawCharts) {
                    elem.chart.replot();
                }
            });

            oldChartData = chartData;

            runtime.refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);
        });
    }

    /* Function to get highest plotted point's y label, to scale the chart,
     * TODO: make jqplot's autoscale:true work here
     */
    function getMaxYLabel(dataValues) {
        var maxY = dataValues[0][1];
        $.each(dataValues, function (k, v) {
            maxY = (v[1] > maxY) ? v[1] : maxY;
        });
        return maxY;
    }

    /* Function that supplies special value transform functions for chart values */
    function chartValueTransform(name, cur, prev) {
        switch (name) {
        case 'cpu-linux':
            if (prev === null) {
                return undefined;
            }
            // cur and prev are datapoint arrays, but containing
            // only 1 element for cpu-linux
            cur = cur[0];
            prev = prev[0];

            var diff_total = cur.busy + cur.idle - (prev.busy + prev.idle);
            var diff_idle = cur.idle - prev.idle;
            return 100 * (diff_total - diff_idle) / diff_total;

        // Query cache efficiency (%)
        case 'qce':
            if (prev === null) {
                return undefined;
            }
            // cur[0].value is Qcache_hits, cur[1].value is Com_select
            var diffQHits = cur[0].value - prev[0].value;
            // No NaN please :-)
            if (cur[1].value - prev[1].value === 0) {
                return 0;
            }

            return diffQHits / (cur[1].value - prev[1].value + diffQHits) * 100;

        // Query cache usage (%)
        case 'qcu':
            if (cur[1].value === 0) {
                return 0;
            }
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
        $.each(runtime.charts, function (key, chart) {
            runtime.dataList[chartID] = [];
            for (var i = 0, l = chart.nodes.length; i < l; i++) {
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

        $('#emptyDialog').dialog({title: PMA_messages.strAnalysingLogsTitle});
        $('#emptyDialog').html(PMA_messages.strAnalysingLogs +
                                ' <img class="ajaxIcon" src="' + pmaThemeImage +
                                'ajax_clock_small.gif" alt="">');
        var dlgBtns = {};

        dlgBtns[PMA_messages.strCancelRequest] = function () {
            if (logRequest !== null) {
                logRequest.abort();
            }

            $(this).dialog("close");
        };

        $('#emptyDialog').dialog({
            width: 'auto',
            height: 'auto',
            buttons: dlgBtns
        });


        logRequest = $.get('server_status_monitor.php?' + PMA_commonParams.get('common_query'),
            {   ajax_request: true,
                log_data: 1,
                type: opts.src,
                time_start: Math.round(opts.start / 1000),
                time_end: Math.round(opts.end / 1000),
                removeVariables: opts.removeVariables,
                limitTypes: opts.limitTypes
            },
            function (data) {
                var logData;
                var dlgBtns = {};
                if (data.success === true) {
                    logData = data.message;
                } else {
                    return serverResponseError();
                }

                if (logData.rows.length !== 0) {
                    runtime.logDataCols = buildLogTable(logData);

                    /* Show some stats in the dialog */
                    $('#emptyDialog').dialog({title: PMA_messages.strLoadingLogs});
                    $('#emptyDialog').html('<p>' + PMA_messages.strLogDataLoaded + '</p>');
                    $.each(logData.sum, function (key, value) {
                        key = key.charAt(0).toUpperCase() + key.slice(1).toLowerCase();
                        if (key == 'Total') {
                            key = '<b>' + key + '</b>';
                        }
                        $('#emptyDialog').append(key + ': ' + value + '<br/>');
                    });

                    /* Add filter options if more than a bunch of rows there to filter */
                    if (logData.numRows > 12) {
                        $('#logTable').prepend(
                            '<fieldset id="logDataFilter">' +
                            '    <legend>' + PMA_messages.strFiltersForLogTable + '</legend>' +
                            '    <div class="formelement">' +
                            '        <label for="filterQueryText">' + PMA_messages.strFilterByWordRegexp + '</label>' +
                            '        <input name="filterQueryText" type="text" id="filterQueryText" style="vertical-align: baseline;" />' +
                            '    </div>' +
                            ((logData.numRows > 250) ? ' <div class="formelement"><button name="startFilterQueryText" id="startFilterQueryText">' + PMA_messages.strFilter + '</button></div>' : '') +
                            '    <div class="formelement">' +
                            '       <input type="checkbox" id="noWHEREData" name="noWHEREData" value="1" /> ' +
                            '       <label for="noWHEREData"> ' + PMA_messages.strIgnoreWhereAndGroup + '</label>' +
                            '   </div' +
                            '</fieldset>'
                        );

                        $('#logTable #noWHEREData').change(function () {
                            filterQueries(true);
                        });

                        if (logData.numRows > 250) {
                            $('#logTable #startFilterQueryText').click(filterQueries);
                        } else {
                            $('#logTable #filterQueryText').keyup(filterQueries);
                        }

                    }

                    dlgBtns[PMA_messages.strJumpToTable] = function () {
                        $(this).dialog("close");
                        $(document).scrollTop($('#logTable').offset().top);
                    };

                    $('#emptyDialog').dialog("option", "buttons", dlgBtns);

                } else {
                    $('#emptyDialog').dialog({title: PMA_messages.strNoDataFoundTitle});
                    $('#emptyDialog').html('<p>' + PMA_messages.strNoDataFound + '</p>');

                    dlgBtns[PMA_messages.strClose] = function () {
                        $(this).dialog("close");
                    };

                    $('#emptyDialog').dialog("option", "buttons", dlgBtns);
                }
            }
        );

        /* Handles the actions performed when the user uses any of the
         * log table filters which are the filter by name and grouping
         * with ignoring data in WHERE clauses
         *
         * @param boolean Should be true when the users enabled or disabled
         *                to group queries ignoring data in WHERE clauses
        */
        function filterQueries(varFilterChange) {
            var odd_row = false, cell, textFilter;
            var val = $('#logTable #filterQueryText').val();

            if (val.length === 0) {
                textFilter = null;
            } else {
                textFilter = new RegExp(val, 'i');
            }

            var rowSum = 0, totalSum = 0, i = 0, q;
            var noVars = $('#logTable #noWHEREData').prop('checked');
            var equalsFilter = /([^=]+)=(\d+|((\'|"|).*?[^\\])\4((\s+)|$))/gi;
            var functionFilter = /([a-z0-9_]+)\(.+?\)/gi;
            var filteredQueries = {}, filteredQueriesLines = {};
            var hide = false, rowData;
            var queryColumnName = runtime.logDataCols[runtime.logDataCols.length - 2];
            var sumColumnName = runtime.logDataCols[runtime.logDataCols.length - 1];
            var isSlowLog = opts.src == 'slow';
            var columnSums = {};

            // For the slow log we have to count many columns (query_time, lock_time, rows_examined, rows_sent, etc.)
            var countRow = function (query, row) {
                var cells = row.match(/<td>(.*?)<\/td>/gi);
                if (!columnSums[query]) {
                    columnSums[query] = [0, 0, 0, 0];
                }

                // lock_time and query_time and displayed in timespan format
                columnSums[query][0] += timeToSec(cells[2].replace(/(<td>|<\/td>)/gi, ''));
                columnSums[query][1] += timeToSec(cells[3].replace(/(<td>|<\/td>)/gi, ''));
                // rows_examind and rows_sent are just numbers
                columnSums[query][2] += parseInt(cells[4].replace(/(<td>|<\/td>)/gi, ''), 10);
                columnSums[query][3] += parseInt(cells[5].replace(/(<td>|<\/td>)/gi, ''), 10);
            };

            // We just assume the sql text is always in the second last column, and that the total count is right of it
            $('#logTable table tbody tr td:nth-child(' + (runtime.logDataCols.length - 1) + ')').each(function () {
                var $t = $(this);
                // If query is a SELECT and user enabled or disabled to group
                // queries ignoring data in where statements, we
                // need to re-calculate the sums of each row
                if (varFilterChange && $t.html().match(/^SELECT/i)) {
                    if (noVars) {
                        // Group on => Sum up identical columns, and hide all but 1

                        q = $t.text().replace(equalsFilter, '$1=...$6').trim();
                        q = q.replace(functionFilter, ' $1(...)');

                        // Js does not specify a limit on property name length,
                        // so we can abuse it as index :-)
                        if (filteredQueries[q]) {
                            filteredQueries[q] += parseInt($t.next().text(), 10);
                            totalSum += parseInt($t.next().text(), 10);
                            hide = true;
                        } else {
                            filteredQueries[q] = parseInt($t.next().text(), 10);
                            filteredQueriesLines[q] = i;
                            $t.text(q);
                        }
                        if (isSlowLog) {
                            countRow(q, $t.parent().html());
                        }

                    } else {
                        // Group off: Restore original columns

                        rowData = $t.parent().data('query');
                        // Restore SQL text
                        $t.text(rowData[queryColumnName]);
                        // Restore total count
                        $t.next().text(rowData[sumColumnName]);
                        // Restore slow log columns
                        if (isSlowLog) {
                            $t.parent().children('td:nth-child(3)').text(rowData['query_time']);
                            $t.parent().children('td:nth-child(4)').text(rowData['lock_time']);
                            $t.parent().children('td:nth-child(5)').text(rowData['rows_sent']);
                            $t.parent().children('td:nth-child(6)').text(rowData['rows_examined']);
                        }
                    }
                }

                // If not required to be hidden, do we need
                // to hide because of a not matching text filter?
                if (! hide && (textFilter !== null && ! textFilter.exec($t.text()))) {
                    hide = true;
                }

                // Now display or hide this column
                if (hide) {
                    $t.parent().css('display', 'none');
                } else {
                    totalSum += parseInt($t.next().text(), 10);
                    rowSum++;

                    odd_row = ! odd_row;
                    $t.parent().css('display', '');
                    if (odd_row) {
                        $t.parent().addClass('odd');
                        $t.parent().removeClass('even');
                    } else {
                        $t.parent().addClass('even');
                        $t.parent().removeClass('odd');
                    }
                }

                hide = false;
                i++;
            });

            // We finished summarizing counts => Update count values of all grouped entries
            if (varFilterChange) {
                if (noVars) {
                    var numCol, row, $table = $('#logTable table tbody');
                    $.each(filteredQueriesLines, function (key, value) {
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

                $('#logTable table').trigger("update");
                setTimeout(function () {
                    $('#logTable table').trigger('sorton', [[[runtime.logDataCols.length - 1, 1]]]);
                }, 0);
            }

            // Display some stats at the bottom of the table
            $('#logTable table tfoot tr')
                .html('<th colspan="' + (runtime.logDataCols.length - 1) + '">' +
                      PMA_messages.strSumRows + ' ' + rowSum + '<span style="float:right">' +
                      PMA_messages.strTotal + '</span></th><th class="right">' + totalSum + '</th>');
        }
    }

    /* Turns a timespan (12:12:12) into a number */
    function timeToSec(timeStr) {
        var time = timeStr.split(':');
        return (parseInt(time[0], 10) * 3600) + (parseInt(time[1], 10) * 60) + parseInt(time[2], 10);
    }

    /* Turns a number into a timespan (100 into 00:01:40) */
    function secToTime(timeInt) {
        var hours = Math.floor(timeInt / 3600);
        timeInt -= hours * 3600;
        var minutes = Math.floor(timeInt / 60);
        timeInt -= minutes * 60;

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
        var cols = [];
        var $table = $('<table class="sortable"></table>');
        var $tBody, $tRow, $tCell;

        $('#logTable').html($table);

        var formatValue = function (name, value) {
            if (name == 'user_host') {
                return value.replace(/(\[.*?\])+/g, '');
            }
            return value;
        };

        for (var i = 0, l = rows.length; i < l; i++) {
            if (i === 0) {
                $.each(rows[0], function (key, value) {
                    cols.push(key);
                });
                $table.append('<thead>' +
                              '<tr><th class="nowrap">' + cols.join('</th><th class="nowrap">') + '</th></tr>' +
                              '</thead>'
                );

                $table.append($tBody = $('<tbody></tbody>'));
            }

            $tBody.append($tRow = $('<tr class="noclick"></tr>'));
            var cl = '';
            for (var j = 0, ll = cols.length; j < ll; j++) {
                // Assuming the query column is the second last
                if (j == cols.length - 2 && rows[i][cols[j]].match(/^SELECT/i)) {
                    $tRow.append($tCell = $('<td class="linkElem">' + formatValue(cols[j], rows[i][cols[j]]) + '</td>'));
                    $tCell.click(openQueryAnalyzer);
                } else {
                    $tRow.append('<td>' + formatValue(cols[j], rows[i][cols[j]]) + '</td>');
                }

                $tRow.data('query', rows[i]);
            }
        }

        $table.append('<tfoot>' +
                    '<tr><th colspan="' + (cols.length - 1) + '">' + PMA_messages.strSumRows +
                    ' ' + data.numRows + '<span style="float:right">' + PMA_messages.strTotal +
                    '</span></th><th class="right">' + data.sum.TOTAL + '</th></tr></tfoot>');

        // Append a tooltip to the count column, if there exist one
        if ($('#logTable th:last').html() == '#') {
            $('#logTable th:last').append('&nbsp;' + PMA_getImage('b_docs.png', '', {'class': 'qroupedQueryInfoIcon'}));

            var tooltipContent = PMA_messages.strCountColumnExplanation;
            if (groupInserts) {
                tooltipContent += '<p>' + PMA_messages.strMoreCountColumnExplanation + '</p>';
            }

            PMA_tooltip(
                $('img.qroupedQueryInfoIcon'),
                'img',
                tooltipContent
            );
        }

        $('#logTable table').tablesorter({
            sortList: [[cols.length - 1, 1]],
            widgets: ['fast-zebra']
        });

        $('#logTable table thead th')
            .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');

        return cols;
    }

    /* Opens the query analyzer dialog */
    function openQueryAnalyzer() {
        var rowData = $(this).parent().data('query');
        var query = rowData.argument || rowData.sql_text;

        if (codemirror_editor) {
            //TODO: somehow PMA_SQLPrettyPrint messes up the query, needs be fixed
            //query = PMA_SQLPrettyPrint(query);
            codemirror_editor.setValue(query);
            // Codemirror is bugged, it doesn't refresh properly sometimes.
            // Following lines seem to fix that
            setTimeout(function () {
                codemirror_editor.refresh();
            }, 50);
        }
        else {
            $('#sqlquery').val(query);
        }

        var profilingChart = null;
        var dlgBtns = {};

        dlgBtns[PMA_messages.strAnalyzeQuery] = function () {
            loadQueryAnalysis(rowData);
        };
        dlgBtns[PMA_messages.strClose] = function () {
            $(this).dialog('close');
        };

        $('#queryAnalyzerDialog').dialog({
            width: 'auto',
            height: 'auto',
            resizable: false,
            buttons: dlgBtns,
            close: function () {
                if (profilingChart !== null) {
                    profilingChart.destroy();
                }
                $('#queryAnalyzerDialog div.placeHolder').html('');
                if (codemirror_editor) {
                    codemirror_editor.setValue('');
                } else {
                    $('#sqlquery').val('');
                }
            }
        });
    }

    /* Loads and displays the analyzed query data */
    function loadQueryAnalysis(rowData) {
        var db = rowData.db || '';

        $('#queryAnalyzerDialog div.placeHolder').html(
            PMA_messages.strAnalyzing + ' <img class="ajaxIcon" src="' +
            pmaThemeImage + 'ajax_clock_small.gif" alt="">');

        $.post('server_status_monitor.php?' + PMA_commonParams.get('common_query'), {
            ajax_request: true,
            query_analyzer: true,
            query: codemirror_editor ? codemirror_editor.getValue() : $('#sqlquery').val(),
            database: db
        }, function (data) {
            var i;
            if (data.success === true) {
                data = data.message;
            }
            if (data.error) {
                if (data.error.indexOf('1146') != -1 || data.error.indexOf('1046') != -1) {
                    data.error = PMA_messages['strServerLogError'];
                }
                $('#queryAnalyzerDialog div.placeHolder').html('<div class="error">' + data.error + '</div>');
                return;
            }
            var totalTime = 0;
            // Float sux, I'll use table :(
            $('#queryAnalyzerDialog div.placeHolder')
                .html('<table width="100%" border="0"><tr><td class="explain"></td><td class="chart"></td></tr></table>');

            var explain = '<b>' + PMA_messages.strExplainOutput + '</b> ' + $('#explain_docu').html();
            if (data.explain.length > 1) {
                explain += ' (';
                for (i = 0; i < data.explain.length; i++) {
                    if (i > 0) {
                        explain += ', ';
                    }
                    explain += '<a href="#showExplain-' + i + '">' + i + '</a>';
                }
                explain += ')';
            }
            explain += '<p></p>';
            for (i = 0, l = data.explain.length; i < l; i++) {
                explain += '<div class="explain-' + i + '"' + (i > 0 ?  'style="display:none;"' : '') + '>';
                $.each(data.explain[i], function (key, value) {
                    value = (value === null) ? 'null' : value;

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

            explain += '<p><b>' + PMA_messages.strAffectedRows + '</b> ' + data.affectedRows;

            $('#queryAnalyzerDialog div.placeHolder td.explain').append(explain);

            $('#queryAnalyzerDialog div.placeHolder a[href*="#showExplain"]').click(function () {
                var id = $(this).attr('href').split('-')[1];
                $(this).parent().find('div[class*="explain"]').hide();
                $(this).parent().find('div[class*="explain-' + id + '"]').show();
            });

            if (data.profiling) {
                var chartData = [];
                var numberTable = '<table class="queryNums"><thead><tr><th>' + PMA_messages.strStatus + '</th><th>' + PMA_messages.strTime + '</th></tr></thead><tbody>';
                var duration;
                var otherTime = 0;

                for (i = 0, l = data.profiling.length; i < l; i++) {
                    duration = parseFloat(data.profiling[i].duration);

                    totalTime += duration;

                    numberTable += '<tr><td>' + data.profiling[i].state + ' </td><td> ' + PMA_prettyProfilingNum(duration, 2) + '</td></tr>';
                }

                // Only put those values in the pie which are > 2%
                for (i = 0, l = data.profiling.length; i < l; i++) {
                    duration = parseFloat(data.profiling[i].duration);

                    if (duration / totalTime > 0.02) {
                        chartData.push([PMA_prettyProfilingNum(duration, 2) + ' ' + data.profiling[i].state, duration]);
                    } else {
                        otherTime += duration;
                    }
                }

                if (otherTime > 0) {
                    chartData.push([PMA_prettyProfilingNum(otherTime, 2) + ' ' + PMA_messages.strOther, otherTime]);
                }

                numberTable += '<tr><td><b>' + PMA_messages.strTotalTime + '</b></td><td>' + PMA_prettyProfilingNum(totalTime, 2) + '</td></tr>';
                numberTable += '</tbody></table>';

                $('#queryAnalyzerDialog div.placeHolder td.chart').append(
                    '<b>' + PMA_messages.strProfilingResults + ' ' + $('#profiling_docu').html() + '</b> ' +
                    '(<a href="#showNums">' + PMA_messages.strTable + '</a>, <a href="#showChart">' + PMA_messages.strChart + '</a>)<br/>' +
                    numberTable + ' <div id="queryProfiling"></div>');

                $('#queryAnalyzerDialog div.placeHolder a[href="#showNums"]').click(function () {
                    $('#queryAnalyzerDialog #queryProfiling').hide();
                    $('#queryAnalyzerDialog table.queryNums').show();
                    return false;
                });

                $('#queryAnalyzerDialog div.placeHolder a[href="#showChart"]').click(function () {
                    $('#queryAnalyzerDialog #queryProfiling').show();
                    $('#queryAnalyzerDialog table.queryNums').hide();
                    return false;
                });

                profilingChart = PMA_createProfilingChartJqplot(
                        'queryProfiling',
                        chartData
                );

                //$('#queryProfiling').resizable();
            }
        });
    }

    /* Saves the monitor to localstorage */
    function saveMonitor() {
        var gridCopy = {};

        $.each(runtime.charts, function (key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
            gridCopy[key].series = elem.series;
            gridCopy[key].maxYLabel = elem.maxYLabel;
        });

        if (window.localStorage) {
            window.localStorage['monitorCharts'] = JSON.stringify(gridCopy);
            window.localStorage['monitorSettings'] = JSON.stringify(monitorSettings);
            window.localStorage['monitorVersion'] = monitorProtocolVersion;
        }

        $('a[href="#clearMonitorConfig"]').show();
    }
});

// Run the monitor once loaded
AJAX.registerOnload('server_status_monitor.js', function () {
    $('a[href="#pauseCharts"]').trigger('click');
});

function serverResponseError() {
    var btns = {};
    btns[PMA_messages.strReloadPage] = function () {
        window.location.reload();
    };
    $('#emptyDialog').dialog({title: PMA_messages.strRefreshFailed});
    $('#emptyDialog').html(
        PMA_getImage('s_attention.png') +
        PMA_messages.strInvalidResponseExplanation
    );
    $('#emptyDialog').dialog({ buttons: btns });
}

/* Destroys all monitor related resources */
function destroyGrid() {
    if (runtime.charts) {
        $.each(runtime.charts, function (key, value) {
            try {
                value.chart.destroy();
            } catch (err) {}
        });
    }

    try {
        runtime.refreshRequest.abort();
    } catch (err) {}
    try {
        clearTimeout(runtime.refreshTimeout);
    } catch (err) {}
    $('#chartGrid').html('');
    runtime.charts = null;
    runtime.chartAI = 0;
    monitorSettings = null; //TODO:this not global variable
}
