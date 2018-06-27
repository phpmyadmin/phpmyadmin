export default class PMA_consoleDebug {
    constructor (instance) {
        this.pmaConsole = null;
        this._config = {
            groupQueries: false,
            orderBy: 'exec', // Possible 'exec' => Execution order, 'time' => Time taken, 'count'
            order: 'asc' // Possible 'asc', 'desc'
        };
        this._lastDebugInfo = {
            debugInfo: null,
            url: null
        };
        this.initialize = this.initialize.bind(this);
        this.setPmaConsole = this.setPmaConsole.bind(this);
        this._formatFunctionCall = this._formatFunctionCall.bind(this);
        this._formatFunctionArgs = this._formatFunctionArgs.bind(this);
        this._formatFileName = this._formatFileName.bind(this);
        this._formatBackTrace = this._formatBackTrace.bind(this);
        this._formatQueryOrGroup = this._formatQueryOrGroup.bind(this);
        this._appendQueryExtraInfo = this._appendQueryExtraInfo.bind(this);
        this.getQueryDetails = this.getQueryDetails.bind(this);
        this.showLog = this.showLog.bind(this);
        this.refresh = this.refresh.bind(this);
        this.setPmaConsole(instance);
    }
    setPmaConsole (instance) {
        this.pmaConsole = instance;
        this.initialize();
    }
    initialize () {
        var self = this;
        // Try to get debug info after every AJAX request
        $(document).ajaxSuccess(function (event, xhr, settings, data) {
            if (data._debug) {
                self.showLog(data._debug, settings.url);
            }
        });

        if (self.pmaConsole.config.GroupQueries) {
            $('#debug_console').addClass('grouped');
        } else {
            $('#debug_console').addClass('ungrouped');
            if (self.pmaConsole.config.OrderBy === 'count') {
                $('#debug_console').find('.button.order_by.sort_exec').addClass('active');
            }
        }
        var orderBy = self.pmaConsole.config.OrderBy;
        var order = self.pmaConsole.config.Order;
        $('#debug_console').find('.button.order_by.sort_' + orderBy).addClass('active');
        $('#debug_console').find('.button.order.order_' + order).addClass('active');

        // Initialize actions in toolbar
        $('#debug_console').find('.button.group_queries').click(function () {
            $('#debug_console').addClass('grouped');
            $('#debug_console').removeClass('ungrouped');
            self.pmaConsole.setConfig('GroupQueries', true);
            self.refresh();
            if (self.pmaConsole.config.OrderBy === 'count') {
                $('#debug_console').find('.button.order_by.sort_exec').removeClass('active');
            }
        });
        $('#debug_console').find('.button.ungroup_queries').click(function () {
            $('#debug_console').addClass('ungrouped');
            $('#debug_console').removeClass('grouped');
            self.pmaConsole.setConfig('GroupQueries', false);
            self.refresh();
            if (self.pmaConsole.config.OrderBy === 'count') {
                $('#debug_console').find('.button.order_by.sort_exec').addClass('active');
            }
        });
        $('#debug_console').find('.button.order_by').click(function () {
            var $this = $(this);
            $('#debug_console').find('.button.order_by').removeClass('active');
            $this.addClass('active');
            if ($this.hasClass('sort_time')) {
                self.pmaConsole.setConfig('OrderBy', 'time');
            } else if ($this.hasClass('sort_exec')) {
                self.pmaConsole.setConfig('OrderBy', 'exec');
            } else if ($this.hasClass('sort_count')) {
                self.pmaConsole.setConfig('OrderBy', 'count');
            }
            self.refresh();
        });
        $('#debug_console').find('.button.order').click(function () {
            var $this = $(this);
            $('#debug_console').find('.button.order').removeClass('active');
            $this.addClass('active');
            if ($this.hasClass('order_asc')) {
                self.pmaConsole.setConfig('Order', 'asc');
            } else if ($this.hasClass('order_desc')) {
                self.pmaConsole.setConfig('Order', 'desc');
            }
            self.refresh();
        });

        // Show SQL debug info for first page load
        if (typeof debugSQLInfo !== 'undefined' && debugSQLInfo !== 'null') {
            $('#pma_console').find('.button.debug').removeClass('hide');
        } else {
            return;
        }
        self.showLog(debugSQLInfo);
    }
    _formatFunctionCall (dbgStep) {
        var functionName = '';
        if ('class' in dbgStep) {
            functionName += dbgStep.class;
            functionName += dbgStep.type;
        }
        functionName += dbgStep.function;
        if (dbgStep.args && dbgStep.args.length) {
            functionName += '(...)';
        } else {
            functionName += '()';
        }
        return functionName;
    }
    _formatFunctionArgs (dbgStep) {
        var $args = $('<div>');
        if (dbgStep.args.length) {
            $args.append('<div class="message welcome">')
                .append(
                    $('<div class="message welcome">')
                        .text(
                            PMA_sprintf(
                                PMA_messages.strConsoleDebugArgsSummary,
                                dbgStep.args.length
                            )
                        )
                );
            for (var i = 0; i < dbgStep.args.length; i++) {
                $args.append(
                    $('<div class="message">')
                        .html(
                            '<pre>' +
                        escapeHtml(JSON.stringify(dbgStep.args[i], null, '  ')) +
                        '</pre>'
                        )
                );
            }
        }
        return $args;
    }
    _formatFileName (dbgStep) {
        var fileName = '';
        if ('file' in dbgStep) {
            fileName += dbgStep.file;
            fileName += '#' + dbgStep.line;
        }
        return fileName;
    }
    _formatBackTrace (dbgTrace) {
        var $traceElem = $('<div class="trace">');
        $traceElem.append(
            $('<div class="message welcome">')
        );
        var step;
        var $stepElem;
        for (var stepId in dbgTrace) {
            if (dbgTrace.hasOwnProperty(stepId)) {
                step = dbgTrace[stepId];
                if (!Array.isArray(step) && typeof step !== 'object') {
                    $stepElem =
                        $('<div class="message traceStep collapsed hide_args">')
                            .append(
                                $('<span>').text(step)
                            );
                } else {
                    if (typeof step.args === 'string' && step.args) {
                        step.args = [step.args];
                    }
                    $stepElem =
                        $('<div class="message traceStep collapsed hide_args">')
                            .append(
                                $('<span class="function">').text(this._formatFunctionCall(step))
                            )
                            .append(
                                $('<span class="file">').text(this._formatFileName(step))
                            );
                    if (step.args && step.args.length) {
                        $stepElem
                            .append(
                                $('<span class="args">').html(this._formatFunctionArgs(step))
                            )
                            .prepend(
                                $('<div class="action_content">')
                                    .append(
                                        '<span class="action dbg_show_args">' +
                                PMA_messages.strConsoleDebugShowArgs +
                                '</span> '
                                    )
                                    .append(
                                        '<span class="action dbg_hide_args">' +
                                PMA_messages.strConsoleDebugHideArgs +
                                '</span> '
                                    )
                            );
                    }
                }
                $traceElem.append($stepElem);
            }
        }
        return $traceElem;
    }
    _formatQueryOrGroup (queryInfo, totalTime) {
        var grouped;
        var queryText;
        var queryTime;
        var count;
        var i;
        if (Array.isArray(queryInfo)) {
            // It is grouped
            grouped = true;

            queryText = queryInfo[0].query;

            queryTime = 0;
            for (i in queryInfo) {
                queryTime += queryInfo[i].time;
            }

            count = queryInfo.length;
        } else {
            queryText = queryInfo.query;
            queryTime = queryInfo.time;
        }

        var $query = $('<div class="message collapsed hide_trace">')
            .append(
                $('#debug_console').find('.templates .debug_query').clone()
            )
            .append(
                $('<div class="query">')
                    .text(queryText)
            )
            .data('queryInfo', queryInfo)
            .data('totalTime', totalTime);
        if (grouped) {
            $query.find('.text.count').removeClass('hide');
            $query.find('.text.count span').text(count);
        }
        $query.find('.text.time span').text(queryTime + 's (' + ((queryTime * 100) / totalTime).toFixed(3) + '%)');

        return $query;
    }
    _appendQueryExtraInfo (query, $elem) {
        if ('error' in query) {
            $elem.append(
                $('<div>').html(query.error)
            );
        }
        $elem.append(this._formatBackTrace(query.trace));
    }
    getQueryDetails (queryInfo, totalTime, $query) {
        if (Array.isArray(queryInfo)) {
            var $singleQuery;
            for (var i in queryInfo) {
                $singleQuery = $('<div class="message welcome trace">')
                    .text((parseInt(i) + 1) + '.')
                    .append(
                        $('<span class="time">').text(
                            PMA_messages.strConsoleDebugTimeTaken +
                        ' ' + queryInfo[i].time + 's' +
                        ' (' + ((queryInfo[i].time * 100) / totalTime).toFixed(3) + '%)'
                        )
                    );
                this._appendQueryExtraInfo(queryInfo[i], $singleQuery);
                $query
                    .append('<div class="message welcome trace">')
                    .append($singleQuery);
            }
        } else {
            this._appendQueryExtraInfo(queryInfo, $query);
        }
    }
    showLog (debugInfo, url) {
        this._lastDebugInfo.debugInfo = debugInfo;
        this._lastDebugInfo.url = url;

        $('#debug_console').find('.debugLog').empty();
        $('#debug_console').find('.debug>.welcome').empty();

        var debugJson = false;
        var i;
        if (typeof debugInfo === 'object' && 'queries' in debugInfo) {
            // Copy it to debugJson, so that it doesn't get changed
            if (!('queries' in debugInfo)) {
                debugJson = false;
            } else {
                debugJson = { queries: [] };
                for (i in debugInfo.queries) {
                    debugJson.queries[i] = debugInfo.queries[i];
                }
            }
        } else if (typeof debugInfo === 'string') {
            try {
                debugJson = JSON.parse(debugInfo);
            } catch (e) {
                debugJson = false;
            }
            if (debugJson && !('queries' in debugJson)) {
                debugJson = false;
            }
        }
        if (debugJson === false) {
            $('#debug_console').find('.debug>.welcome').text(
                PMA_messages.strConsoleDebugError
            );
            return;
        }
        var allQueries = debugJson.queries;
        var uniqueQueries = {};

        var totalExec = allQueries.length;

        // Calculate total time and make unique query array
        var totalTime = 0;
        for (i = 0; i < totalExec; ++i) {
            totalTime += allQueries[i].time;
            if (!(allQueries[i].hash in uniqueQueries)) {
                uniqueQueries[allQueries[i].hash] = [];
            }
            uniqueQueries[allQueries[i].hash].push(allQueries[i]);
        }
        // Count total unique queries, convert uniqueQueries to Array
        var totalUnique = 0;
        var uniqueArray = [];
        for (var hash in uniqueQueries) {
            if (uniqueQueries.hasOwnProperty(hash)) {
                ++totalUnique;
                uniqueArray.push(uniqueQueries[hash]);
            }
        }
        uniqueQueries = uniqueArray;
        // Show summary
        $('#debug_console').find('.debug>.welcome').append(
            $('<span class="debug_summary">').text(
                PMA_sprintf(
                    PMA_messages.strConsoleDebugSummary,
                    totalUnique,
                    totalExec,
                    totalTime
                )
            )
        );
        if (url) {
            $('#debug_console').find('.debug>.welcome').append(
                $('<span class="script_name">').text(url.split('?')[0])
            );
        }

        // For sorting queries
        function sortByTime (a, b) {
            var order = ((PMA_console.config.Order === 'asc') ? 1 : -1);
            if (Array.isArray(a) && Array.isArray(b)) {
                // It is grouped
                var timeA = 0;
                var timeB = 0;
                var i;
                for (i in a) {
                    timeA += a[i].time;
                }
                for (i in b) {
                    timeB += b[i].time;
                }
                return (timeA - timeB) * order;
            } else {
                return (a.time - b.time) * order;
            }
        }

        function sortByCount (a, b) {
            var order = ((PMA_console.config.Oorder === 'asc') ? 1 : -1);
            return (a.length - b.length) * order;
        }

        var orderBy = this.pmaConsole.config.OrderBy;
        var order = this.pmaConsole.config.Order;

        if (this.pmaConsole.config.GroupQueries) {
            // Sort queries
            if (orderBy === 'time') {
                uniqueQueries.sort(sortByTime);
            } else if (orderBy === 'count') {
                uniqueQueries.sort(sortByCount);
            } else if (orderBy === 'exec' && order === 'desc') {
                uniqueQueries.reverse();
            }
            for (i in uniqueQueries) {
                if (orderBy === 'time') {
                    uniqueQueries[i].sort(sortByTime);
                } else if (orderBy === 'exec' && order === 'desc') {
                    uniqueQueries[i].reverse();
                }
                $('#debug_console').find('.debugLog').append(this._formatQueryOrGroup(uniqueQueries[i], totalTime));
            }
        } else {
            if (orderBy === 'time') {
                allQueries.sort(sortByTime);
            } else if (order === 'desc') {
                allQueries.reverse();
            }
            for (i = 0; i < totalExec; ++i) {
                $('#debug_console').find('.debugLog').append(this._formatQueryOrGroup(allQueries[i], totalTime));
            }
        }

        this.pmaConsole.pmaConsoleMessages._msgEventBinds($('#debug_console').find('.message:not(.binded)'));
    }
    refresh () {
        var last = this._lastDebugInfo;
        this.showLog(last.debugInfo, last.url);
    }
}
