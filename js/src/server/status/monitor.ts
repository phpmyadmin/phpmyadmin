import $ from 'jquery';
import { AJAX } from '../../modules/ajax.ts';
import { Functions } from '../../modules/functions.ts';
import { CommonParams } from '../../modules/common.ts';
import tooltip from '../../modules/tooltip.ts';
import createProfilingChart from '../../modules/functions/createProfilingChart.ts';
import { escapeHtml } from '../../modules/functions/escape.ts';
import getImageTag from '../../modules/functions/getImageTag.ts';
import isStorageSupported from '../../modules/functions/isStorageSupported.ts';

/**
 * @fileoverview    Javascript functions used in server status monitor page
 * @name            Server Status Monitor
 *
 * @requires    jQueryUI
 */

var runtime: { [k: string]: any } = {};
var serverTimeDiff;
var serverOs;
var isSuperUser;
var serverDbIsLocal;
var chartSize;
var monitorSettings;

function serverResponseError () {
    var btns = {
        [window.Messages.strReloadPage]: {
            text: window.Messages.strReloadPage,
            class: 'btn btn-primary',
            click: function () {
                window.location.reload();
            },
        },
    };
    $('#emptyDialog').dialog({
        classes: {
            'ui-dialog-titlebar-close': 'btn-close'
        },
        title: window.Messages.strRefreshFailed
    });

    $('#emptyDialog').html(
        getImageTag('s_attention') +
        window.Messages.strInvalidResponseExplanation
    );

    $('#emptyDialog').dialog({
        classes: {
            'ui-dialog-titlebar-close': 'btn-close'
        },
        // @ts-ignore
        buttons: btns
    });
}

/**
 * Destroys all monitor related resources
 */
function destroyGrid () {
    if (runtime.charts) {
        $.each(runtime.charts, function (key, value) {
            try {
                value.chart.destroy();
            } catch (err) {
                // continue regardless of error
            }
        });
    }

    try {
        runtime.refreshRequest.abort();
    } catch (err) {
        // continue regardless of error
    }

    try {
        clearTimeout(runtime.refreshTimeout);
    } catch (err) {
        // continue regardless of error
    }

    $('#chartGrid').html('');
    runtime.charts = null;
    runtime.chartAI = 0;
    monitorSettings = null;
}

AJAX.registerOnload('server/status/monitor.js', function () {
    var $jsDataForm = $('#js_data');
    serverTimeDiff = new Date().getTime() - Number($jsDataForm.find('input[name=server_time]').val());
    serverOs = $jsDataForm.find('input[name=server_os]').val();
    isSuperUser = $jsDataForm.find('input[name=is_superuser]').val();
    serverDbIsLocal = $jsDataForm.find('input[name=server_db_isLocal]').val();
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/status/monitor.js', function () {
    $('#emptyDialog').remove();
    $('a.popupLink').off('click');
    $('body').off('click');
});

/**
 * Popup behaviour
 */
AJAX.registerOnload('server/status/monitor.js', function () {
    $('<div></div>')
        .attr('id', 'emptyDialog')
        .appendTo('#page_content');

    $('a.popupLink').on('click', function () {
        var $link = $(this);
        $('div.' + $link.attr('href').substring(1))
            .show()
            .offset({ top: $link.offset().top + $link.height() + 5, left: $link.offset().left })
            .addClass('openedPopup');

        return false;
    });

    $('body').on('click', function (event) {
        $('div.openedPopup').each(function () {
            var $cnt = $(this);
            var pos = $cnt.offset();
            // Hide if the mouseclick is outside the popupcontent
            if (event.pageX > pos.left + $cnt.outerWidth() ||
                event.pageY > pos.top + $cnt.outerHeight()
            ) {
                $cnt.hide().removeClass('openedPopup');
            }
        });
    });
});

AJAX.registerTeardown('server/status/monitor.js', function () {
    $('a[href="#rearrangeCharts"], a[href="#endChartEditMode"]').off('click');
    $('div.popupContent select[name="chartColumns"]').off('change');
    $('div.popupContent select[name="gridChartRefresh"]').off('change');
    $('a[href="#addNewChart"]').off('click');
    $('a[href="#exportMonitorConfig"]').off('click');
    $('a[href="#importMonitorConfig"]').off('click');
    $('a[href="#clearMonitorConfig"]').off('click');
    $('a[href="#pauseCharts"]').off('click');
    $('a[href="#monitorInstructionsDialog"]').off('click');
    $('input[name="chartType"]').off('click');
    $('input[name="useDivisor"]').off('click');
    $('input[name="useUnit"]').off('click');
    $('select[name="varChartList"]').off('click');
    $('a[href="#kibDivisor"]').off('click');
    $('a[href="#mibDivisor"]').off('click');
    $('a[href="#submitClearSeries"]').off('click');
    $('a[href="#submitAddSeries"]').off('click');
    // $("input#variableInput").destroy();
    $('#chartPreset').off('click');
    $('#chartStatusVar').off('click');
    destroyGrid();
});

AJAX.registerOnload('server/status/monitor.js', function () {
    // Show tab links
    $('div.tabLinks').show();
    $('#loadingMonitorIcon').remove();
    // Codemirror is loaded on demand so we might need to initialize it
    if (! window.codeMirrorEditor) {
        var $elm = ($('#sqlquery') as JQuery<HTMLTextAreaElement>);
        if ($elm.length > 0 && typeof window.CodeMirror !== 'undefined') {
            window.codeMirrorEditor = window.CodeMirror.fromTextArea(
                $elm[0],
                {
                    lineNumbers: true,
                    // @ts-ignore
                    matchBrackets: true,
                    indentUnit: 4,
                    mode: 'text/x-mysql',
                    lineWrapping: true
                }
            );
        }
    }

    // Timepicker is loaded on demand so we need to initialize
    // datetime fields from the 'load log' dialog
    $('#logAnalyseDialog').find('.datetimefield').each(function () {
        Functions.addDatepicker($(this));
    });

    /** ** Monitor charting implementation ****/
    /* Saves the previous ajax response for differential values */
    var oldChartData = null;
    // Holds about to be created chart
    var newChart = null;
    var chartSpacing;

    // Whenever the monitor object (runtime.charts) or the settings object
    // (monitorSettings) changes in a way incompatible to the previous version,
    // increase this number. It will reset the users monitor and settings object
    // in their localStorage to the default configuration
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

    monitorSettings = null;

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
    var presetCharts: { [p: string]: any } = {
        // Query cache efficiency
        'qce': {
            title: window.Messages.strQueryCacheEfficiency,
            series: [
                {
                    label: window.Messages.strQueryCacheEfficiency
                }
            ],
            nodes: [
                {
                    dataPoints: [{ type: 'statusvar', name: 'Qcache_hits' }, { type: 'statusvar', name: 'Com_select' }],
                    transformFn: 'qce'
                }
            ],
            maxYLabel: 0
        },
        // Query cache usage
        'qcu': {
            title: window.Messages.strQueryCacheUsage,
            series: [
                {
                    label: window.Messages.strQueryCacheUsed
                }
            ],
            nodes: [
                {
                    dataPoints: [
                        { type: 'statusvar', name: 'Qcache_free_memory' }, {
                            type: 'servervar',
                            name: 'query_cache_size'
                        }
                    ],
                    transformFn: 'qcu'
                }
            ],
            maxYLabel: 0
        }
    };

    // time span selection
    var selectionTimeDiff = [];
    var selectionStartX;
    var selectionStartY;
    var drawTimeSpan = false;

    /* Add OS specific system info charts to the preset chart list */
    switch (serverOs) {
    case 'WINNT':
        $.extend(presetCharts, {
            'cpu': {
                title: window.Messages.strSystemCPUUsage,
                series: [
                    {
                        label: window.Messages.strAverageLoad
                    }
                ],
                nodes: [
                    {
                        dataPoints: [{ type: 'cpu', name: 'loadavg' }]
                    }
                ],
                maxYLabel: 100
            },

            'memory': {
                title: window.Messages.strSystemMemory,
                series: [
                    {
                        dataType: 'memory',
                        label: window.Messages.strUsedMemory,
                        fill: true
                    }, {
                        label: window.Messages.strFreeMemory,
                        fill: true
                    }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },

            'swap': {
                title: window.Messages.strSystemSwap,
                series: [
                    {
                        label: window.Messages.strUsedSwap,
                        fill: true
                    }, {
                        label: window.Messages.strFreeSwap,
                        fill: true
                    }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'SwapUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            }
        });

        break;

    case 'Linux':
        $.extend(presetCharts, {
            'cpu': {
                title: window.Messages.strSystemCPUUsage,
                series: [
                    {
                        label: window.Messages.strAverageLoad
                    }
                ],
                nodes: [{ dataPoints: [{ type: 'cpu', name: 'irrelevant' }], transformFn: 'cpu-linux' }],
                maxYLabel: 0
            },
            'memory': {
                title: window.Messages.strSystemMemory,
                series: [
                    { label: window.Messages.strBufferedMemory, fill: true },
                    { label: window.Messages.strUsedMemory, fill: true },
                    { label: window.Messages.strCachedMemory, fill: true },
                    { label: window.Messages.strFreeMemory, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'Buffers' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'Cached' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: window.Messages.strSystemSwap,
                series: [
                    { label: window.Messages.strCachedSwap, fill: true },
                    { label: window.Messages.strUsedSwap, fill: true },
                    { label: window.Messages.strFreeSwap, fill: true }
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
                title: window.Messages.strSystemCPUUsage,
                series: [
                    {
                        label: window.Messages.strAverageLoad
                    }
                ],
                nodes: [
                    {
                        dataPoints: [{ type: 'cpu', name: 'loadavg' }]
                    }
                ],
                maxYLabel: 0
            },
            'memory': {
                title: window.Messages.strSystemMemory,
                series: [
                    { label: window.Messages.strUsedMemory, fill: true },
                    { label: window.Messages.strFreeMemory, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: window.Messages.strSystemSwap,
                series: [
                    { label: window.Messages.strUsedSwap, fill: true },
                    { label: window.Messages.strFreeSwap, fill: true }
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
    var defaultChartGrid: { [p: string]: any } = {
        'c0': {
            title: window.Messages.strQuestions,
            series: [{ label: window.Messages.strQuestions }],
            nodes: [{ dataPoints: [{ type: 'statusvar', name: 'Questions' }], display: 'differential' }],
            maxYLabel: 0
        },
        'c1': {
            title: window.Messages.strChartConnectionsTitle,
            series: [
                { label: window.Messages.strConnections },
                { label: window.Messages.strProcesses }
            ],
            nodes: [
                { dataPoints: [{ type: 'statusvar', name: 'Connections' }], display: 'differential' },
                { dataPoints: [{ type: 'proc', name: 'processes' }] }
            ],
            maxYLabel: 0
        },
        'c2': {
            title: window.Messages.strTraffic,
            series: [
                { label: window.Messages.strBytesSent },
                { label: window.Messages.strBytesReceived }
            ],
            nodes: [
                {
                    dataPoints: [{ type: 'statusvar', name: 'Bytes_sent' }],
                    display: 'differential',
                    valueDivisor: 1024
                },
                {
                    dataPoints: [{ type: 'statusvar', name: 'Bytes_received' }],
                    display: 'differential',
                    valueDivisor: 1024
                }
            ],
            maxYLabel: 0
        }
    };

    // Server is localhost => We can add cpu/memory/swap to the default chart
    if (serverDbIsLocal && typeof presetCharts.cpu !== 'undefined') {
        defaultChartGrid.c3 = presetCharts.cpu;
        defaultChartGrid.c4 = presetCharts.memory;
        defaultChartGrid.c5 = presetCharts.swap;
    }

    $('a[href="#rearrangeCharts"], a[href="#endChartEditMode"]').on('click', function (event) {
        event.preventDefault();
        editMode = ! editMode;
        if ($(this).attr('href') === '#endChartEditMode') {
            editMode = false;
        }

        $('a[href="#endChartEditMode"]').toggle(editMode);

        if (editMode) {
            // Close the settings popup
            $('div.popupContent').hide().removeClass('openedPopup');

            $('#chartGrid').sortableTable({
                ignoreRect: {
                    top: 8,
                    left: chartSize.width - 63,
                    width: 54,
                    height: 24
                }
            });
        } else {
            $('#chartGrid').sortableTable('destroy');
        }

        saveMonitor(); // Save settings

        return false;
    });

    // global settings
    ($('div.popupContent select[name="chartColumns"]') as JQuery<HTMLSelectElement>).on('change', function () {
        monitorSettings.columns = parseInt(this.value, 10);

        calculateChartSize();
        // Empty cells should keep their size so you can drop onto them
        $('#chartGrid').find('tr td').css('width', chartSize.width + 'px');
        $('#chartGrid').find('.monitorChart').css({
            width: chartSize.width + 'px',
            height: chartSize.height + 'px'
        });

        /* Reorder all charts that it fills all column cells */
        var numColumns;
        var $tr = $('#chartGrid').find('tr').first();

        var tempManageCols = function () {
            if (numColumns > monitorSettings.columns) {
                if ($tr.next().length === 0) {
                    $tr.after('<tr></tr>');
                }

                $tr.next().prepend($(this));
            }

            numColumns++;
        };

        var tempAddCol = function () {
            if ($(this).next().length !== 0) {
                $(this).append($(this).next().find('td').first());
            }
        };

        while ($tr.length !== 0) {
            numColumns = 1;
            // To many cells in one row => put into next row
            $tr.find('td').each(tempManageCols);

            // To little cells in one row => for each cell to little,
            // move all cells backwards by 1
            if ($tr.next().length > 0) {
                var cnt = monitorSettings.columns - $tr.find('td').length;
                for (var i = 0; i < cnt; i++) {
                    $tr.append($tr.next().find('td').first());
                    $tr.nextAll().each(tempAddCol);
                }
            }

            $tr = $tr.next();
        }

        if (monitorSettings.gridMaxPoints === 'auto') {
            runtime.gridMaxPoints = Math.round((chartSize.width - 40) / 12);
        }

        runtime.xmin = new Date().getTime() - serverTimeDiff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - serverTimeDiff + monitorSettings.gridRefresh;

        if (editMode) {
            $('#chartGrid').sortableTable('refresh');
        }

        refreshChartGrid();
        saveMonitor(); // Save settings
    });

    ($('div.popupContent select[name="gridChartRefresh"]') as JQuery<HTMLSelectElement>).on('change', function () {
        monitorSettings.gridRefresh = parseInt(this.value, 10) * 1000;
        clearTimeout(runtime.refreshTimeout);

        if (runtime.refreshRequest) {
            runtime.refreshRequest.abort();
        }

        runtime.xmin = new Date().getTime() - serverTimeDiff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        // fixing chart shift towards left on refresh rate change
        // runtime.xmax = new Date().getTime() - serverTimeDiff + monitorSettings.gridRefresh;
        runtime.refreshTimeout = setTimeout(refreshChartGrid, monitorSettings.gridRefresh);

        saveMonitor(); // Save settings
    });

    $('a[href="#addNewChart"]').on('click', function (event) {
        event.preventDefault();

        $('#addChartButton').on('click', function () {
            var type = $('input[name="chartType"]:checked').val();

            if (type === 'preset') {
                newChart = presetCharts[$('#addChartModal').find('select[name="presetCharts"]').prop('value')];
            } else {
                // If user builds their own chart, it's being set/updated
                // each time they add a series
                // So here we only warn if they didn't add a series yet
                if (! newChart || ! newChart.nodes || newChart.nodes.length === 0) {
                    alert(window.Messages.strAddOneSeriesWarning);

                    return;
                }
            }

            newChart.title = $('input[name="chartTitle"]').val();
            // Add a cloned object to the chart grid
            addChart($.extend(true, {}, newChart));

            newChart = null;

            saveMonitor(); // Save settings

            $('#closeModalButton').off('click');
        });

        $('#closeModalButton').on('click', function () {
            newChart = null;
            $('span#clearSeriesLink').hide();
            $('#seriesPreview').html('');
            $('#closeModalButton').off('click');
        });

        var $presetList = $('#addChartModal').find('select[name="presetCharts"]');
        if ($presetList.html().length === 0) {
            $.each(presetCharts, function (key, value) {
                $presetList.append('<option value="' + key + '">' + value.title + '</option>');
            });

            $presetList.on('change', function () {
                $('input[name="chartTitle"]').val(
                    $presetList.find(':selected').text()
                );

                $('#chartPreset').prop('checked', true);
            });

            $('#chartPreset').on('click', function () {
                $('input[name="chartTitle"]').val(
                    $presetList.find(':selected').text()
                );
            });

            $('#chartStatusVar').on('click', function () {
                $('input[name="chartTitle"]').val(
                    $('#chartSeries').find(':selected').text().replace(/_/g, ' ')
                );
            });

            $('#chartSeries').on('change', function () {
                $('input[name="chartTitle"]').val(
                    $('#chartSeries').find(':selected').text().replace(/_/g, ' ')
                );
            });
        }

        $('#addChartModal').modal('show');

        $('#seriesPreview').html('<i>' + window.Messages.strNone + '</i>');

        return false;
    });

    $('a[href="#exportMonitorConfig"]').on('click', function (event) {
        event.preventDefault();
        var gridCopy = {};
        $.each(runtime.charts, function (key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].series = elem.series;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
            gridCopy[key].maxYLabel = elem.maxYLabel;
        });

        var exportData = {
            monitorCharts: gridCopy,
            monitorSettings: monitorSettings
        };

        var blob = new Blob([JSON.stringify(exportData)], { type: 'application/octet-stream' });
        var url = null;
        var fileName = 'monitor-config.json';
        // @ts-ignore
        if (window.navigator && window.navigator.msSaveOrOpenBlob) {
            // @ts-ignore
            window.navigator.msSaveOrOpenBlob(blob, fileName);
        } else {
            url = URL.createObjectURL(blob);
            window.location.href = url;
        }

        setTimeout(function () {
            // For some browsers it is necessary to delay revoking the ObjectURL
            if (url !== null) {
                window.URL.revokeObjectURL(url);
            }

            url = undefined;
            blob = undefined;
        }, 100);
    });

    $('a[href="#importMonitorConfig"]').on('click', function (event) {
        event.preventDefault();
        $('#emptyDialog').dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            title: window.Messages.strImportDialogTitle
        });

        $('#emptyDialog').html(window.Messages.strImportDialogMessage + '<br><form>' +
            '<input type="file" name="file" id="import_file"> </form>');

        var dlgBtns = {
            [window.Messages.strImport]: {
                text: window.Messages.strImport,
                class: 'btn btn-primary',
            },
            [window.Messages.strCancel]: {
                text: window.Messages.strCancel,
                class: 'btn btn-secondary',
            },
        };

        // @ts-ignore
        dlgBtns[window.Messages.strImport].click = function () {
            var input = ($('#emptyDialog').find('#import_file') as JQuery<HTMLInputElement>)[0];
            var reader = new FileReader();

            reader.onerror = function (event) {
                alert(window.Messages.strFailedParsingConfig + '\n' + event.target.error.code);
            };

            reader.onload = function (e) {
                var data = (e.target.result as string);
                var json = null;
                // Try loading config
                try {
                    json = JSON.parse(data);
                } catch (err) {
                    alert(window.Messages.strFailedParsingConfig);
                    $('#emptyDialog').dialog('close');

                    return;
                }

                // Basic check, is this a monitor config json?
                if (! json || ! json.monitorCharts || ! json.monitorCharts) {
                    alert(window.Messages.strFailedParsingConfig);
                    $('#emptyDialog').dialog('close');

                    return;
                }

                // If json ok, try applying config
                try {
                    if (isStorageSupported('localStorage')) {
                        window.localStorage.monitorCharts = JSON.stringify(json.monitorCharts);
                        window.localStorage.monitorSettings = JSON.stringify(json.monitorSettings);
                    }

                    rebuildGrid();
                } catch (err) {
                    alert(window.Messages.strFailedBuildingGrid);
                    // If an exception is thrown, load default again
                    if (isStorageSupported('localStorage')) {
                        window.localStorage.removeItem('monitorCharts');
                        window.localStorage.removeItem('monitorSettings');
                    }

                    rebuildGrid();
                }

                $('#emptyDialog').dialog('close');
            };

            if (input.files[0]) {
                reader.readAsText(input.files[0]);
            }
        };

        // @ts-ignore
        dlgBtns[window.Messages.strCancel].click = function () {
            $(this).dialog('close');
        };

        $('#emptyDialog').dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            width: 'auto',
            height: 'auto',
            // @ts-ignore
            buttons: dlgBtns
        });
    });

    $('a[href="#clearMonitorConfig"]').on('click', function (event) {
        event.preventDefault();
        if (isStorageSupported('localStorage')) {
            window.localStorage.removeItem('monitorCharts');
            window.localStorage.removeItem('monitorSettings');
            window.localStorage.removeItem('monitorVersion');
        }

        $(this).hide();
        rebuildGrid();
    });

    $('a[href="#pauseCharts"]').on('click', function (event) {
        event.preventDefault();
        runtime.redrawCharts = ! runtime.redrawCharts;
        if (! runtime.redrawCharts) {
            $(this).html(getImageTag('play') + window.Messages.strResumeMonitor);
        } else {
            $(this).html(getImageTag('pause') + window.Messages.strPauseMonitor);
            if (! runtime.charts) {
                initGrid();
                $('a[href="#settingsPopup"]').show();
            }
        }

        return false;
    });

    $('a[href="#monitorInstructionsDialog"]').on('click', function (event) {
        event.preventDefault();

        var $dialog = $('#monitorInstructionsDialog');
        var dlgBtns = {
            [window.Messages.strClose]: {
                text: window.Messages.strClose,
                class: 'btn btn-primary',
                click: function () {
                    $(this).dialog('close');
                }
            },
        };
        $dialog.dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            width: '60%',
            height: 'auto',
            // @ts-ignore
            buttons: dlgBtns
        }).find('img.ajaxIcon').show();

        var loadLogVars = function (getvars = undefined) {
            var vars = {
                'ajax_request': true,
                'server': CommonParams.get('server')
            };
            if (getvars) {
                $.extend(vars, getvars);
            }

            $.post('index.php?route=/server/status/monitor/log-vars', vars,
                function (data) {
                    var logVars;
                    if (typeof data !== 'undefined' && data.success === true) {
                        logVars = data.message;
                    } else {
                        return serverResponseError();
                    }

                    var icon = getImageTag('s_success');
                    var msg = '';
                    var str = '';

                    if (logVars.general_log === 'ON') {
                        if (logVars.slow_query_log === 'ON') {
                            msg = window.Messages.strBothLogOn;
                        } else {
                            msg = window.Messages.strGenLogOn;
                        }
                    }

                    if (msg.length === 0 && logVars.slow_query_log === 'ON') {
                        msg = window.Messages.strSlowLogOn;
                    }

                    if (msg.length === 0) {
                        icon = getImageTag('s_error');
                        msg = window.Messages.strBothLogOff;
                    }

                    str = '<b>' + window.Messages.strCurrentSettings + '</b><br><div class="smallIndent">';
                    str += icon + msg + '<br>';

                    if (logVars.log_output !== 'TABLE') {
                        str += getImageTag('s_error') + ' ' + window.Messages.strLogOutNotTable + '<br>';
                    } else {
                        str += getImageTag('s_success') + ' ' + window.Messages.strLogOutIsTable + '<br>';
                    }

                    if (logVars.slow_query_log === 'ON') {
                        if (logVars.long_query_time > 2) {
                            str += getImageTag('s_attention') + ' ';
                            str += window.sprintf(window.Messages.strSmallerLongQueryTimeAdvice, logVars.long_query_time);
                            str += '<br>';
                        }

                        if (logVars.long_query_time < 2) {
                            str += getImageTag('s_success') + ' ';
                            str += window.sprintf(window.Messages.strLongQueryTimeSet, logVars.long_query_time);
                            str += '<br>';
                        }
                    }

                    str += '</div>';

                    if (isSuperUser) {
                        str += '<p></p><b>' + window.Messages.strChangeSettings + '</b>';
                        str += '<div class="smallIndent">';
                        str += window.Messages.strSettingsAppliedGlobal + '<br>';

                        var varValue: string | number = 'TABLE';
                        if (logVars.log_output === 'TABLE') {
                            varValue = 'FILE';
                        }

                        str += '- <a class="set" href="#log_output-' + varValue + '">';
                        str += window.sprintf(window.Messages.strSetLogOutput, varValue);
                        str += ' </a><br>';

                        if (logVars.general_log !== 'ON') {
                            str += '- <a class="set" href="#general_log-ON">';
                            str += window.sprintf(window.Messages.strEnableVar, 'general_log');
                            str += ' </a><br>';
                        } else {
                            str += '- <a class="set" href="#general_log-OFF">';
                            str += window.sprintf(window.Messages.strDisableVar, 'general_log');
                            str += ' </a><br>';
                        }

                        if (logVars.slow_query_log !== 'ON') {
                            str += '- <a class="set" href="#slow_query_log-ON">';
                            str += window.sprintf(window.Messages.strEnableVar, 'slow_query_log');
                            str += ' </a><br>';
                        } else {
                            str += '- <a class="set" href="#slow_query_log-OFF">';
                            str += window.sprintf(window.Messages.strDisableVar, 'slow_query_log');
                            str += ' </a><br>';
                        }

                        varValue = 5;
                        if (logVars.long_query_time > 2) {
                            varValue = 1;
                        }

                        str += '- <a class="set" href="#long_query_time-' + varValue + '">';
                        str += window.sprintf(window.Messages.setSetLongQueryTime, varValue);
                        str += ' </a><br>';
                    } else {
                        str += window.Messages.strNoSuperUser + '<br>';
                    }

                    str += '</div>';

                    $dialog.find('div.monitorUse').toggle(
                        logVars.log_output === 'TABLE' && (logVars.slow_query_log === 'ON' || logVars.general_log === 'ON')
                    );

                    $dialog.find('div.ajaxContent').html(str);
                    $dialog.find('img.ajaxIcon').hide();
                    $dialog.find('a.set').on('click', function () {
                        var nameValue = $(this).attr('href').split('-');
                        loadLogVars({ varName: nameValue[0].substring(1), varValue: nameValue[1] });
                        $dialog.find('img.ajaxIcon').show();
                    });
                }
            );
        };


        loadLogVars();

        return false;
    });

    ($('input[name="chartType"]') as JQuery<HTMLInputElement>).on('change', function () {
        $('#chartVariableSettings').toggle(this.checked && this.value === 'variable');
        var title = $('input[name="chartTitle"]').val();
        if (title === window.Messages.strChartTitle ||
            title === $('label[for="' + $('input[name="chartTitle"]').data('lastRadio') + '"]').text()
        ) {
            $('input[name="chartTitle"]')
                .data('lastRadio', $(this).attr('id'))
                .val($('label[for="' + $(this).attr('id') + '"]').text());
        }
    });

    ($('input[name="useDivisor"]') as JQuery<HTMLInputElement>).on('change', function () {
        $('span.divisorInput').toggle(this.checked);
    });

    ($('input[name="useUnit"]') as JQuery<HTMLInputElement>).on('change', function () {
        $('span.unitInput').toggle(this.checked);
    });

    ($('select[name="varChartList"]') as JQuery<HTMLSelectElement>).on('change', function () {
        if (this.selectedIndex !== 0) {
            $('#variableInput').val(this.value);
        }
    });

    $('a[href="#kibDivisor"]').on('click', function (event) {
        event.preventDefault();
        $('input[name="valueDivisor"]').val(1024);
        $('input[name="valueUnit"]').val(window.Messages.strKiB);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked', true);

        return false;
    });

    $('a[href="#mibDivisor"]').on('click', function (event) {
        event.preventDefault();
        $('input[name="valueDivisor"]').val(1024 * 1024);
        $('input[name="valueUnit"]').val(window.Messages.strMiB);
        $('span.unitInput').toggle(true);
        $('input[name="useUnit"]').prop('checked', true);

        return false;
    });

    $('a[href="#submitClearSeries"]').on('click', function (event) {
        event.preventDefault();
        $('#seriesPreview').html('<i>' + window.Messages.strNone + '</i>');
        newChart = null;
        $('#clearSeriesLink').hide();
    });

    $('a[href="#submitAddSeries"]').on('click', function (event) {
        event.preventDefault();
        if ($('#variableInput').val() === '') {
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

        var serie: { [p: string]: any } = {
            dataPoints: [{ type: 'statusvar', name: $('#variableInput').val() }],
            display: $('input[name="differentialValue"]').prop('checked') ? 'differential' : ''
        };

        if (serie.dataPoints[0].name === 'Processes') {
            serie.dataPoints[0].type = 'proc';
        }

        if ($('input[name="useDivisor"]').prop('checked')) {
            serie.valueDivisor = parseInt(($('input[name="valueDivisor"]').val() as string), 10);
        }

        if ($('input[name="useUnit"]').prop('checked')) {
            serie.unit = $('input[name="valueUnit"]').val();
        }

        var str = serie.display === 'differential' ? ', ' + window.Messages.strDifferential : '';
        str += serie.valueDivisor ? (', ' + window.sprintf(window.Messages.strDividedBy, serie.valueDivisor)) : '';
        str += serie.unit ? (', ' + window.Messages.strUnit + ': ' + serie.unit) : '';

        var newSeries = {
            label: ($('#variableInput').val() as string).replace(/_/g, ' ')
        };
        newChart.series.push(newSeries);
        $('#seriesPreview').append('- ' + escapeHtml(newSeries.label + str) + '<br>');
        newChart.nodes.push(serie);
        $('#variableInput').val('');
        $('input[name="differentialValue"]').prop('checked', true);
        $('input[name="useDivisor"]').prop('checked', false);
        $('input[name="useUnit"]').prop('checked', false);
        $('input[name="useDivisor"]').trigger('change');
        $('input[name="useUnit"]').trigger('change');
        ($('select[name="varChartList"]') as JQuery<HTMLSelectElement>).get(0).selectedIndex = 0;

        $('#clearSeriesLink').show();

        return false;
    });

    $('#variableInput').autocomplete({
        source: window.variableNames
    });

    /* Initializes the monitor, called only once */
    function initGrid () {
        var i;

        /* Apply default values & config */
        if (isStorageSupported('localStorage')) {
            if (typeof window.localStorage.monitorCharts !== 'undefined') {
                runtime.charts = JSON.parse(window.localStorage.monitorCharts);
            }

            if (typeof window.localStorage.monitorSettings !== 'undefined') {
                monitorSettings = JSON.parse(window.localStorage.monitorSettings);
            }

            $('a[href="#clearMonitorConfig"]').toggle(runtime.charts !== null);

            if (runtime.charts !== null
                && typeof window.localStorage.monitorVersion !== 'undefined'
                && monitorProtocolVersion !== window.localStorage.monitorVersion
            ) {
                $('#emptyDialog').dialog({
                    classes: {
                        'ui-dialog-titlebar-close': 'btn-close'
                    },
                    title: window.Messages.strIncompatibleMonitorConfig
                });

                $('#emptyDialog').html(window.Messages.strIncompatibleMonitorConfigDescription);

                var dlgBtns = {
                    [window.Messages.strClose]: {
                        text: window.Messages.strClose,
                        class: 'btn btn-primary',
                        click: function () {
                            $(this).dialog('close');
                        }
                    },
                };

                $('#emptyDialog').dialog({
                    classes: {
                        'ui-dialog-titlebar-close': 'btn-close'
                    },
                    width: 400,
                    // @ts-ignore
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

        if (monitorSettings.gridMaxPoints === 'auto') {
            runtime.gridMaxPoints = Math.round((monitorSettings.chartSize.width - 40) / 12);
        } else {
            runtime.gridMaxPoints = monitorSettings.gridMaxPoints;
        }

        runtime.xmin = new Date().getTime() - serverTimeDiff - runtime.gridMaxPoints * monitorSettings.gridRefresh;
        runtime.xmax = new Date().getTime() - serverTimeDiff + monitorSettings.gridRefresh;

        /* Calculate how much spacing there is between each chart */
        $('#chartGrid').html('<tr><td></td><td></td></tr><tr><td></td><td></td></tr>');
        chartSpacing = {
            width: $('#chartGrid').find('td').eq(1).offset().left -
                $('#chartGrid').find('td').eq(0).offset().left,
            height: $('#chartGrid').find('tr').eq(1).find('td').eq(1).offset().top -
                $('#chartGrid').find('tr').eq(0).find('td').eq(0).offset().top
        };

        $('#chartGrid').html('');

        /* Add all charts - in correct order */
        var keys = [];
        $.each(runtime.charts, function (key) {
            keys.push(key);
        });

        keys.sort();
        for (i = 0; i < keys.length; i++) {
            addChart(runtime.charts[keys[i]], true);
        }

        /* Fill in missing cells */
        var numCharts = $('#chartGrid').find('.monitorChart').length;
        var numMissingCells = (monitorSettings.columns - numCharts % monitorSettings.columns) % monitorSettings.columns;
        for (i = 0; i < numMissingCells; i++) {
            $('#chartGrid').find('tr').last().append('<td></td>');
        }

        // Empty cells should keep their size so you can drop onto them
        calculateChartSize();
        $('#chartGrid').find('tr td').css('width', chartSize.width + 'px');

        buildRequiredDataList();
        refreshChartGrid();
    }

    /* Calls destroyGrid() and initGrid(), but before doing so it saves the chart
     * data from each chart and restores it after the monitor is initialized again */
    function rebuildGrid () {
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

    /* Calculates the dynamic chart size that depends on the column width */
    function calculateChartSize () {
        var panelWidth;
        if ($('body').height() > $(window).height()) { // has vertical scroll bar
            panelWidth = $('#logTable').innerWidth();
        } else {
            panelWidth = $('#logTable').innerWidth() - 10; // leave some space for vertical scroll bar
        }

        var wdt = panelWidth;
        var windowWidth = $(window).width();

        if (windowWidth > 768) {
            wdt = (panelWidth - monitorSettings.columns * Math.abs(chartSpacing.width)) / monitorSettings.columns;
        }

        chartSize = {
            width: Math.floor(wdt),
            height: Math.floor(0.75 * wdt)
        };
    }

    /* Adds a chart to the chart grid */
    function addChart (chartObj, initialize = undefined) {
        var i;
        var settings: { [p: string]: any } = {
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
                lineWidth: 2,
                markerOptions: {
                    size: 6
                }
            },
            highlighter: {
                show: true
            }
        };

        if (settings.title === window.Messages.strSystemCPUUsage ||
            settings.title === window.Messages.strQueryCacheEfficiency
        ) {
            settings.axes.yaxis.tickOptions = {
                formatString: '%d %%'
            };
        } else if (settings.title === window.Messages.strSystemMemory ||
            settings.title === window.Messages.strSystemSwap
        ) {
            settings.stackSeries = true;
            settings.axes.yaxis.tickOptions = {
                formatter: $.jqplot.byteFormatter(2) // MiB
            };
        } else if (settings.title === window.Messages.strTraffic) {
            settings.axes.yaxis.tickOptions = {
                formatter: $.jqplot.byteFormatter(1) // KiB
            };
        } else if (settings.title === window.Messages.strQuestions ||
            settings.title === window.Messages.strConnections
        ) {
            settings.axes.yaxis.tickOptions = {
                formatter: function (format, val) {
                    if (Math.abs(val) >= 1000000) {
                        return $.jqplot.sprintf('%.3g M', val / 1000000);
                    } else if (Math.abs(val) >= 1000) {
                        return $.jqplot.sprintf('%.3g k', val / 1000);
                    } else {
                        return $.jqplot.sprintf('%d', val);
                    }
                }
            };
        }

        settings.series = chartObj.series;

        if ($('#' + 'gridchart' + runtime.chartAI).length === 0) {
            var numCharts = $('#chartGrid').find('.monitorChart').length;

            if (numCharts === 0 || (numCharts % monitorSettings.columns === 0)) {
                $('#chartGrid').append('<tr></tr>');
            }

            if (! chartSize) {
                calculateChartSize();
            }

            $('#chartGrid').find('tr').last().append(
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

        var tempTooltipContentEditor = function (str, seriesIndex, pointIndex, plot) {
            var j;
            // TODO: move style to theme CSS
            var tooltipHtml = '<div id="tooltip_editor">';
            // x value i.e. time
            var timeValue = str.split(',')[0];
            var seriesValue;
            tooltipHtml += 'Time: ' + timeValue;
            tooltipHtml += '<span id="tooltip_font">';
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
                if (plot.series[0]._yaxis.tickOptions.formatter) { // eslint-disable-line no-underscore-dangle
                    // using formatter function
                    // eslint-disable-next-line no-underscore-dangle
                    seriesValue = plot.series[0]._yaxis.tickOptions.formatter('%s', seriesValue);
                } else if (plot.series[0]._yaxis.tickOptions.formatString) { // eslint-disable-line no-underscore-dangle
                    // using format string
                    // eslint-disable-next-line no-underscore-dangle
                    seriesValue = window.sprintf(plot.series[0]._yaxis.tickOptions.formatString, seriesValue);
                }

                tooltipHtml += '<br><span style="color:' + seriesColor + '">' +
                    seriesLabel + ': ' + seriesValue + '</span>';
            }

            tooltipHtml += '</span></div>';

            return tooltipHtml;
        };

        // set Tooltip for each series
        for (i in settings.series) {
            settings.series[i].highlighter = {
                show: true,
                tooltipContentEditor: tempTooltipContentEditor
            };
        }

        chartObj.chart = $.jqplot('gridchart' + runtime.chartAI, series, settings);
        // remove [0,0] after plotting
        for (i in chartObj.chart.series) {
            chartObj.chart.series[i].data.shift();
        }

        var $legend = $('<div></div>').css('padding', '0.5em');
        for (i in chartObj.chart.series) {
            $legend.append(
                $('<div></div>').append(
                    $('<div>').css({
                        width: '1em',
                        height: '1em',
                        background: chartObj.chart.seriesColors[i]
                    }).addClass('float-start')
                ).append(
                    $('<div>').text(
                        chartObj.chart.series[i].label
                    ).addClass('float-start')
                ).append(
                    $('<div class="clearfloat">')
                ).addClass('float-start')
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
        $('#gridchart' + runtime.chartAI).on('jqplotMouseDown', function (ev, gridpos, datapos) {
            drawTimeSpan = true;
            selectionTimeDiff.push(datapos.xaxis);
            if ($('#selection_box').length) {
                $('#selection_box').remove();
            }

            var selectionBox = $('<div id="selection_box" >');
            // eslint-disable-next-line compat/compat
            $(document.body).append(selectionBox);
            selectionStartX = ev.pageX;
            selectionStartY = ev.pageY;
            selectionBox
                .attr({ id: 'selection_box' })
                .css({
                    top: selectionStartY - gridpos.y,
                    left: selectionStartX
                })
                .fadeIn();
        });

        $('#gridchart' + runtime.chartAI).on('jqplotMouseUp', function (ev, gridpos, datapos) {
            if (! drawTimeSpan || editMode) {
                return;
            }

            selectionTimeDiff.push(datapos.xaxis);

            if (selectionTimeDiff[1] <= selectionTimeDiff[0]) {
                selectionTimeDiff = [];

                return;
            }

            // get date from timestamp
            var min = new Date(Math.ceil(selectionTimeDiff[0]));
            var max = new Date(Math.ceil(selectionTimeDiff[1]));
            getLogAnalyseDialog(min, max);
            selectionTimeDiff = [];
            drawTimeSpan = false;
        });

        $('#gridchart' + runtime.chartAI).on('jqplotMouseMove', function (ev) {
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

        $('#gridchart' + runtime.chartAI).on('jqplotMouseLeave', function () {
            drawTimeSpan = false;
        });

        // eslint-disable-next-line compat/compat
        $(document.body).on('mouseup', function () {
            if ($('#selection_box').length) {
                $('#selection_box').remove();
            }
        });

        // Edit, Print icon only in edit mode
        $('#chartGrid').find('div svg').find('*[zIndex=20], *[zIndex=21], *[zIndex=19]').toggle(editMode);

        runtime.chartAI++;
    }

    function getLogAnalyseDialog (min, max) {
        var $logAnalyseDialog = $('#logAnalyseDialog');
        var $dateStart = $logAnalyseDialog.find('input[name="dateStart"]');
        var $dateEnd = $logAnalyseDialog.find('input[name="dateEnd"]');
        $dateStart.prop('readonly', true);
        $dateEnd.prop('readonly', true);

        var dlgBtns = {
            [window.Messages.strFromSlowLog]: {
                text: window.Messages.strFromSlowLog,
                class: 'btn btn-secondary',
            },
            [window.Messages.strFromGeneralLog]: {
                text: window.Messages.strFromGeneralLog,
                class: 'btn btn-secondary',
            },
        };

        // @ts-ignore
        dlgBtns[window.Messages.strFromSlowLog].click = function () {
            loadLog('slow', min, max);
            $(this).dialog('close');
        };

        // @ts-ignore
        dlgBtns[window.Messages.strFromGeneralLog].click = function () {
            loadLog('general', min, max);
            $(this).dialog('close');
        };

        $logAnalyseDialog.dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            width: 'auto',
            height: 'auto',
            // @ts-ignore
            buttons: dlgBtns
        });

        Functions.addDatepicker($dateStart, 'datetime', {
            showMillisec: false,
            showMicrosec: false,
            timeFormat: 'HH:mm:ss',
            firstDay: window.firstDayOfCalendar
        });

        Functions.addDatepicker($dateEnd, 'datetime', {
            showMillisec: false,
            showMicrosec: false,
            timeFormat: 'HH:mm:ss',
            firstDay: window.firstDayOfCalendar
        });

        $dateStart.datepicker('setDate', min);
        $dateEnd.datepicker('setDate', max);
    }

    function loadLog (type, min, max) {
        var dateStart = Date.parse($('#logAnalyseDialog').find('input[name="dateStart"]').datepicker('getDate').toString()) || min;
        var dateEnd = Date.parse($('#logAnalyseDialog').find('input[name="dateEnd"]').datepicker('getDate').toString()) || max;

        loadLogStatistics({
            src: type,
            start: dateStart,
            end: dateEnd,
            removeVariables: $('#removeVariables').prop('checked'),
            limitTypes: $('#limitTypes').prop('checked')
        });
    }

    /* Called in regular intervals, this function updates the values of each chart in the grid */
    function refreshChartGrid () {
        /* Send to server */
        runtime.refreshRequest = $.post('index.php?route=/server/status/monitor/chart', {
            'ajax_request': true,
            'requiredData': JSON.stringify(runtime.dataList),
            'server': CommonParams.get('server')
        }, function (data) {
            var chartData;
            if (typeof data !== 'undefined' && data.success === true) {
                chartData = data.message;
            } else {
                return serverResponseError();
            }

            var value;
            var i = 0;
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
                            diff = parseInt((chartData.x - oldChartData.x).toString(), 10);
                        }

                        runtime.xmin += diff;
                        runtime.xmax += diff;
                    }

                    // elem.chart.xAxis[0].setExtremes(runtime.xmin, runtime.xmax, false);
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

                        if (elem.nodes[j].display === 'differential') {
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

                        if (elem.title === window.Messages.strSystemMemory ||
                            elem.title === window.Messages.strSystemSwap
                        ) {
                            total += value;
                        }
                    }
                }

                // update chart options
                // keep ticks number/positioning consistent while refreshrate changes
                var tickInterval = (runtime.xmax - runtime.xmin) / 5;
                elem.chart.axes.xaxis.ticks = [
                    (runtime.xmax - tickInterval * 4),
                    (runtime.xmax - tickInterval * 3),
                    (runtime.xmax - tickInterval * 2),
                    (runtime.xmax - tickInterval),
                    runtime.xmax
                ];

                if (elem.title !== window.Messages.strSystemCPUUsage &&
                    elem.title !== window.Messages.strQueryCacheEfficiency &&
                    elem.title !== window.Messages.strSystemMemory &&
                    elem.title !== window.Messages.strSystemSwap
                ) {
                    elem.chart.axes.yaxis.max = Math.ceil(elem.maxYLabel * 1.1);
                    elem.chart.axes.yaxis.tickInterval = Math.ceil(elem.maxYLabel * 1.1 / 5);
                } else if (elem.title === window.Messages.strSystemMemory ||
                    elem.title === window.Messages.strSystemSwap
                ) {
                    elem.chart.axes.yaxis.max = Math.ceil(total * 1.1 / 100) * 100;
                    elem.chart.axes.yaxis.tickInterval = Math.ceil(total * 1.1 / 5);
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
    function getMaxYLabel (dataValues) {
        var maxY = dataValues[0][1];
        $.each(dataValues, function (k, v) {
            maxY = (v[1] > maxY) ? v[1] : maxY;
        });

        return maxY;
    }

    /* Function that supplies special value transform functions for chart values */
    function chartValueTransform (name, cur, prev) {
        switch (name) {
        case 'cpu-linux':
            if (prev === null) {
                return undefined;
            }

            // cur and prev are datapoint arrays, but containing
            // only 1 element for cpu-linux
            var newCur = cur[0];
            var newPrev = prev[0];

            var diffTotal = newCur.busy + newCur.idle - (newPrev.busy + newPrev.idle);
            var diffIdle = newCur.idle - newPrev.idle;

            return 100 * (diffTotal - diffIdle) / diffTotal;

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
    function buildRequiredDataList () {
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
    function loadLogStatistics (opts) {
        var logRequest = null;

        if (! opts.removeVariables) {
            opts.removeVariables = false;
        }

        if (! opts.limitTypes) {
            opts.limitTypes = false;
        }

        $('#emptyDialog').dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            title: window.Messages.strAnalysingLogsTitle
        });

        $('#emptyDialog').html(window.Messages.strAnalysingLogs +
            ' <img class="ajaxIcon" src="' + window.themeImagePath +
            'ajax_clock_small.gif" alt="">');

        var dlgBtns = {
            [window.Messages.strCancelRequest]: {
                text: window.Messages.strCancelRequest,
                class: 'btn btn-primary',
            },
        };

        // @ts-ignore
        dlgBtns[window.Messages.strCancelRequest].click = function () {
            if (logRequest !== null) {
                logRequest.abort();
            }

            $(this).dialog('close');
        };

        $('#emptyDialog').dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            width: 'auto',
            height: 'auto',
            // @ts-ignore
            buttons: dlgBtns
        });

        var url = 'index.php?route=/server/status/monitor/slow-log';
        if (opts.src === 'general') {
            url = 'index.php?route=/server/status/monitor/general-log';
        }

        logRequest = $.post(
            url,
            {
                'ajax_request': true,
                'time_start': Math.round(opts.start / 1000),
                'time_end': Math.round(opts.end / 1000),
                'removeVariables': opts.removeVariables,
                'limitTypes': opts.limitTypes,
                'server': CommonParams.get('server')
            },
            function (data) {
                var logData;
                var dlgBtns = {
                    [window.Messages.strClose]: {
                        text: window.Messages.strClose,
                        class: 'btn btn-primary',
                    },
                };
                if (typeof data !== 'undefined' && data.success === true) {
                    logData = data.message;
                } else {
                    return serverResponseError();
                }

                if (logData.rows.length === 0) {
                    $('#emptyDialog').dialog({
                        classes: {
                            'ui-dialog-titlebar-close': 'btn-close'
                        },
                        title: window.Messages.strNoDataFoundTitle,
                    });

                    $('#emptyDialog').html('<p>' + window.Messages.strNoDataFound + '</p>');

                    // @ts-ignore
                    dlgBtns[window.Messages.strClose].click = function () {
                        $(this).dialog('close');
                    };

                    $('#emptyDialog').dialog('option', 'buttons', dlgBtns);

                    return;
                }

                runtime.logDataCols = buildLogTable(logData, opts.removeVariables);

                /* Show some stats in the dialog */
                $('#emptyDialog').dialog({
                    classes: {
                        'ui-dialog-titlebar-close': 'btn-close'
                    },
                    title: window.Messages.strLoadingLogs
                });

                $('#emptyDialog').html('<p>' + window.Messages.strLogDataLoaded + '</p>');
                $.each(logData.sum, function (key: string, value) {
                    var newKey = key.charAt(0).toUpperCase() + key.slice(1).toLowerCase();
                    if (newKey === 'Total') {
                        newKey = '<b>' + newKey + '</b>';
                    }

                    $('#emptyDialog').append(newKey + ': ' + value + '<br>');
                });

                /* Add filter options if more than a bunch of rows there to filter */
                if (logData.numRows > 12) {
                    $('#logTable').prepend(
                        '<fieldset class="pma-fieldset" id="logDataFilter">' +
                        '    <legend>' + window.Messages.strFiltersForLogTable + '</legend>' +
                        '    <div class="formelement">' +
                        '        <label for="filterQueryText">' + window.Messages.strFilterByWordRegexp + '</label>' +
                        '        <input name="filterQueryText" type="text" id="filterQueryText">' +
                        '    </div>' +
                        ((logData.numRows > 250) ? ' <div class="formelement"><button class="btn btn-secondary" name="startFilterQueryText" id="startFilterQueryText">' + window.Messages.strFilter + '</button></div>' : '') +
                        '    <div class="formelement">' +
                        '       <input type="checkbox" id="noWHEREData" name="noWHEREData" value="1"> ' +
                        '       <label for="noWHEREData"> ' + window.Messages.strIgnoreWhereAndGroup + '</label>' +
                        '   </div' +
                        '</fieldset>'
                    );

                    $('#noWHEREData').on('change', function () {
                        filterQueries(true);
                    });

                    if (logData.numRows > 250) {
                        $('#startFilterQueryText').on('click', filterQueries);
                    } else {
                        $('#filterQueryText').on('keyup', filterQueries);
                    }
                }

                dlgBtns[window.Messages.strJumpToTable] = {
                    text: window.Messages.strJumpToTable,
                    class: 'btn btn-secondary',
                    // @ts-ignore
                    click: function () {
                        $(this).dialog('close');
                        $(document).scrollTop($('#logTable').offset().top);
                    },
                };

                $('#emptyDialog').dialog('option', 'buttons', dlgBtns);
            }
        );

        /**
         * Handles the actions performed when the user uses any of the
         * log table filters which are the filter by name and grouping
         * with ignoring data in WHERE clauses
         *
         * @param {boolean} varFilterChange Should be true when the users enabled or disabled
         *                to group queries ignoring data in WHERE clauses
         */
        function filterQueries (varFilterChange) {
            var textFilter;
            var val = ($('#filterQueryText').val() as string);

            if (val.length === 0) {
                textFilter = null;
            } else {
                try {
                    textFilter = new RegExp(val, 'i');
                    $('#filterQueryText').removeClass('error');
                } catch (e) {
                    if (e instanceof SyntaxError) {
                        $('#filterQueryText').addClass('error');
                        textFilter = null;
                    }
                }
            }

            var rowSum = 0;
            var totalSum = 0;
            var i = 0;
            var q;
            var noVars = $('#noWHEREData').prop('checked');
            var equalsFilter = /([^=]+)=(\d+|(('|"|).*?[^\\])\4((\s+)|$))/gi;
            var functionFilter = /([a-z0-9_]+)\(.+?\)/gi;
            var filteredQueries = {};
            var filteredQueriesLines = {};
            var hide = false;
            var rowData;
            var queryColumnName = runtime.logDataCols[runtime.logDataCols.length - 2];
            var sumColumnName = runtime.logDataCols[runtime.logDataCols.length - 1];
            var isSlowLog = opts.src === 'slow';
            var columnSums = {};

            // For the slow log we have to count many columns (query_time, lock_time, rows_examined, rows_sent, etc.)
            var countRow = function (query, row) {
                var cells = row.match(/<td>(.*?)<\/td>/gi);
                if (! columnSums[query]) {
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
            $('#logTable').find('table tbody tr td.queryCell').each(function () {
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
                            $t.parent().children('td').eq(2).text(rowData.query_time);
                            $t.parent().children('td').eq(3).text(rowData.lock_time);
                            $t.parent().children('td').eq(4).text(rowData.rows_sent);
                            $t.parent().children('td').eq(5).text(rowData.rows_examined);
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
                    $t.parent().css('display', '');
                }

                hide = false;
                i++;
            });

            // We finished summarizing counts => Update count values of all grouped entries
            if (varFilterChange) {
                if (noVars) {
                    var numCol;
                    var row;
                    var $table = $('#logTable').find('table tbody');
                    $.each(filteredQueriesLines, function (key, value) {
                        if (filteredQueries[key] <= 1) {
                            return;
                        }

                        row = $table.children('tr').eq(value);
                        numCol = row.children().eq(runtime.logDataCols.length - 1);
                        numCol.text(filteredQueries[key]);

                        if (isSlowLog) {
                            row.children('td').eq(2).text(secToTime(columnSums[key][0]));
                            row.children('td').eq(3).text(secToTime(columnSums[key][1]));
                            row.children('td').eq(4).text(columnSums[key][2]);
                            row.children('td').eq(5).text(columnSums[key][3]);
                        }
                    });
                }

                $('#logTable').find('table').trigger('update');
                setTimeout(function () {
                    $('#logTable').find('table').trigger('sorton', [[[runtime.logDataCols.length - 1, 1]]]);
                }, 0);
            }

            // Display some stats at the bottom of the table
            $('#logTable').find('table tfoot tr')
                .html('<th colspan="' + (runtime.logDataCols.length - 1) + '">' +
                    window.Messages.strSumRows + ' ' + rowSum + '<span class="float-end">' +
                    window.Messages.strTotal + '</span></th><th class="text-end">' + totalSum + '</th>');
        }
    }

    /* Turns a timespan (12:12:12) into a number */
    function timeToSec (timeStr) {
        var time = timeStr.split(':');

        return (parseInt(time[0], 10) * 3600) + (parseInt(time[1], 10) * 60) + parseInt(time[2], 10);
    }

    /* Turns a number into a timespan (100 into 00:01:40) */
    function secToTime (timeInt) {
        var time = timeInt;
        var hours: number | string = Math.floor(time / 3600);
        time -= hours * 3600;
        var minutes: number | string = Math.floor(time / 60);
        time -= minutes * 60;

        if (hours < 10) {
            hours = '0' + hours;
        }

        if (minutes < 10) {
            minutes = '0' + minutes;
        }

        if (time < 10) {
            time = '0' + time;
        }

        return hours + ':' + minutes + ':' + time;
    }

    /* Constructs the log table out of the retrieved server data */
    function buildLogTable (data, groupInserts) {
        var rows = data.rows;
        var cols = [];
        var $table = $('<table class="table table-striped table-hover align-middle sortable"></table>');
        var $tBody;
        var $tRow;
        var $tCell;

        // @ts-ignore
        $('#logTable').html($table);

        var tempPushKey = function (key) {
            cols.push(key);
        };

        var formatValue = function (name, value) {
            if (name === 'user_host') {
                return value.replace(/(\[.*?\])+/g, '');
            }

            return escapeHtml(value);
        };

        for (var i = 0, l = rows.length; i < l; i++) {
            if (i === 0) {
                $.each(rows[0], tempPushKey);
                $table.append('<thead>' +
                    '<tr><th class="text-nowrap">' + cols.join('</th><th class="text-nowrap">') + '</th></tr>' +
                    '</thead>'
                );

                $table.append($tBody = $('<tbody></tbody>'));
            }

            $tBody.append($tRow = $('<tr class="noclick"></tr>'));
            for (var j = 0, ll = cols.length; j < ll; j++) {
                // Assuming the query column is the second last
                if (j === cols.length - 2 && rows[i][cols[j]].match(/^SELECT/i)) {
                    $tRow.append($tCell = $('<td class="linkElem queryCell">' + formatValue(cols[j], rows[i][cols[j]]) + '</td>'));
                    $tCell.on('click', openQueryAnalyzer);
                } else {
                    $tRow.append('<td>' + formatValue(cols[j], rows[i][cols[j]]) + '</td>');
                }

                $tRow.data('query', rows[i]);
            }
        }

        $table.append('<tfoot>' +
            '<tr><th colspan="' + (cols.length - 1) + '">' + window.Messages.strSumRows +
            ' ' + data.numRows + '<span class="float-end">' + window.Messages.strTotal +
            '</span></th><th class="text-end">' + data.sum.TOTAL + '</th></tr></tfoot>');

        // Append a tooltip to the count column, if there exist one
        if ($('#logTable').find('tr').first().find('th').last().text().indexOf('#') > -1) {
            $('#logTable').find('tr').first().find('th').last().append('&nbsp;' + getImageTag('b_help', '', { 'class': 'qroupedQueryInfoIcon' }));

            var tooltipContent = window.Messages.strCountColumnExplanation;
            if (groupInserts) {
                tooltipContent += '<p>' + window.Messages.strMoreCountColumnExplanation + '</p>';
            }

            tooltip($('img.qroupedQueryInfoIcon'), 'img', tooltipContent);
        }

        $('#logTable').find('table').tablesorter({
            sortList: [[cols.length - 1, 1]],
            widgets: ['fast-zebra']
        });

        $('#logTable').find('table thead th')
            .append('<div class="sorticon"></div>');

        return cols;
    }

    /* Opens the query analyzer dialog */
    function openQueryAnalyzer () {
        var rowData = $(this).parent().data('query');
        var query = rowData.argument || rowData.sql_text;

        if (window.codeMirrorEditor) {
            // TODO: somehow Functions.sqlPrettyPrint messes up the query, needs be fixed
            // query = Functions.sqlPrettyPrint(query);
            window.codeMirrorEditor.setValue(query);
            // Codemirror is bugged, it doesn't refresh properly sometimes.
            // Following lines seem to fix that
            setTimeout(function () {
                window.codeMirrorEditor.refresh();
            }, 50);
        } else {
            $('#sqlquery').val(query);
        }

        var profilingChart = null;
        var dlgBtns = {
            [window.Messages.strAnalyzeQuery]: {
                text: window.Messages.strAnalyzeQuery,
                class: 'btn btn-primary',
            },
            [window.Messages.strClose]: {
                text: window.Messages.strClose,
                class: 'btn btn-secondary',
            },
        };

        // @ts-ignore
        dlgBtns[window.Messages.strAnalyzeQuery].click = function () {
            profilingChart = loadQueryAnalysis(rowData);
        };

        // @ts-ignore
        dlgBtns[window.Messages.strClose].click = function () {
            $(this).dialog('close');
        };

        $('#queryAnalyzerDialog').dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            width: 'auto',
            height: 'auto',
            resizable: false,
            // @ts-ignore
            buttons: dlgBtns,
            close: function () {
                if (profilingChart !== null) {
                    profilingChart.destroy();
                }

                $('#queryAnalyzerDialog').find('div.placeHolder').html('');
                if (window.codeMirrorEditor) {
                    window.codeMirrorEditor.setValue('');
                } else {
                    $('#sqlquery').val('');
                }
            }
        });
    }

    /* Loads and displays the analyzed query data */
    function loadQueryAnalysis (rowData) {
        var db = rowData.db || '';
        var profilingChart = null;

        $('#queryAnalyzerDialog').find('div.placeHolder').html(
            window.Messages.strAnalyzing + ' <img class="ajaxIcon" src="' +
            window.themeImagePath + 'ajax_clock_small.gif" alt="">');

        $.post('index.php?route=/server/status/monitor/query', {
            'ajax_request': true,
            'query': window.codeMirrorEditor ? window.codeMirrorEditor.getValue() : $('#sqlquery').val(),
            'database': db,
            'server': CommonParams.get('server')
        }, function (responseData) {
            var data = responseData;
            var i;
            var l;
            if (typeof data !== 'undefined' && data.success === true) {
                data = data.message;
            }

            if (data.error) {
                if (data.error.indexOf('1146') !== -1 || data.error.indexOf('1046') !== -1) {
                    data.error = window.Messages.strServerLogError;
                }

                $('#queryAnalyzerDialog').find('div.placeHolder').html('<div class="alert alert-danger" role="alert">' + data.error + '</div>');

                return;
            }

            var totalTime = 0;
            // Float sux, I'll use table :(
            $('#queryAnalyzerDialog').find('div.placeHolder')
                .html('<table class="table table-borderless"><tr><td class="explain"></td><td class="chart"></td></tr></table>');

            var explain = '<b>' + window.Messages.strExplainOutput + '</b> ' + $('#explain_docu').html();
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

            var tempExplain = function (key, value) {
                var newValue = (value === null) ? 'null' : escapeHtml(value);

                if (key === 'type' && newValue.toLowerCase() === 'all') {
                    newValue = '<span class="text-danger">' + newValue + '</span>';
                }

                if (key === 'Extra') {
                    newValue = newValue.replace(/(using (temporary|filesort))/gi, '<span class="text-danger">$1</span>');
                }

                explain += key + ': ' + newValue + '<br>';
            };

            for (i = 0, l = data.explain.length; i < l; i++) {
                explain += '<div class="explain-' + i + '"' + (i > 0 ? 'style="display:none;"' : '') + '>';
                $.each(data.explain[i], tempExplain);
                explain += '</div>';
            }

            explain += '<p><b>' + window.Messages.strAffectedRows + '</b> ' + data.affectedRows;

            $('#queryAnalyzerDialog').find('div.placeHolder td.explain').append(explain);

            $('#queryAnalyzerDialog').find('div.placeHolder a[href*="#showExplain"]').on('click', function () {
                var id = $(this).attr('href').split('-')[1];
                $(this).parent().find('div[class*="explain"]').hide();
                $(this).parent().find('div[class*="explain-' + id + '"]').show();
            });

            if (data.profiling) {
                var chartData = [];
                var numberTable = '<table class="table table-sm table-striped table-hover w-auto queryNums"><thead><tr><th>' + window.Messages.strStatus + '</th><th>' + window.Messages.strTime + '</th></tr></thead><tbody>';
                var duration;
                var otherTime = 0;

                for (i = 0, l = data.profiling.length; i < l; i++) {
                    duration = parseFloat(data.profiling[i].duration);

                    totalTime += duration;

                    numberTable += '<tr><td>' + data.profiling[i].state + ' </td><td> ' + Functions.prettyProfilingNum(duration, 2) + '</td></tr>';
                }

                // Only put those values in the pie which are > 2%
                for (i = 0, l = data.profiling.length; i < l; i++) {
                    duration = parseFloat(data.profiling[i].duration);

                    if (duration / totalTime > 0.02) {
                        chartData.push([Functions.prettyProfilingNum(duration, 2) + ' ' + data.profiling[i].state, duration]);
                    } else {
                        otherTime += duration;
                    }
                }

                if (otherTime > 0) {
                    chartData.push([Functions.prettyProfilingNum(otherTime, 2) + ' ' + window.Messages.strOther, otherTime]);
                }

                numberTable += '<tr><td><b>' + window.Messages.strTotalTime + '</b></td><td>' + Functions.prettyProfilingNum(totalTime, 2) + '</td></tr>';
                numberTable += '</tbody></table>';

                $('#queryAnalyzerDialog').find('div.placeHolder td.chart').append(
                    '<b>' + window.Messages.strProfilingResults + ' ' + $('#profiling_docu').html() + '</b> ' +
                    '(<a href="#showNums">' + window.Messages.strTable + '</a>, <a href="#showChart">' + window.Messages.strChart + '</a>)<br>' +
                    numberTable + ' <div id="queryProfiling"></div>');

                $('#queryAnalyzerDialog').find('div.placeHolder a[href="#showNums"]').on('click', function () {
                    $('#queryAnalyzerDialog').find('#queryProfiling').hide();
                    $('#queryAnalyzerDialog').find('table.queryNums').show();

                    return false;
                });

                $('#queryAnalyzerDialog').find('div.placeHolder a[href="#showChart"]').on('click', function () {
                    $('#queryAnalyzerDialog').find('#queryProfiling').show();
                    $('#queryAnalyzerDialog').find('table.queryNums').hide();

                    return false;
                });

                profilingChart = createProfilingChart('queryProfiling', chartData);
            }
        });

        return profilingChart;
    }

    /* Saves the monitor to localstorage */
    function saveMonitor () {
        var gridCopy = {};

        $.each(runtime.charts, function (key, elem) {
            gridCopy[key] = {};
            gridCopy[key].nodes = elem.nodes;
            gridCopy[key].settings = elem.settings;
            gridCopy[key].title = elem.title;
            gridCopy[key].series = elem.series;
            gridCopy[key].maxYLabel = elem.maxYLabel;
        });

        if (isStorageSupported('localStorage')) {
            window.localStorage.monitorCharts = JSON.stringify(gridCopy);
            window.localStorage.monitorSettings = JSON.stringify(monitorSettings);
            window.localStorage.monitorVersion = monitorProtocolVersion;
        }

        $('a[href="#clearMonitorConfig"]').show();
    }
});

// Run the monitor once loaded
AJAX.registerOnload('server/status/monitor.js', function () {
    $('a[href="#pauseCharts"]').trigger('click');
});
