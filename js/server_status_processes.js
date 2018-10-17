/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server Status Processes
 *
 * @package PhpMyAdmin
 */

// object to store process list state information
var processList = {

    // denotes whether auto refresh is on or off
    autoRefresh: false,
    // stores the GET request which refresh process list
    refreshRequest: null,
    // stores the timeout id returned by setTimeout
    refreshTimeout: null,
    // the refresh interval in seconds
    refreshInterval: null,
    // the refresh URL (required to save last used option)
    // i.e. full or sorting url
    refreshUrl: null,

    /**
     * Handles killing of a process
     *
     * @return void
     */
    init: function () {
        processList.setRefreshLabel();
        if (processList.refreshUrl === null) {
            processList.refreshUrl = 'server_status_processes.php' +
                PMA_commonParams.get('common_query');
        }
        if (processList.refreshInterval === null) {
            processList.refreshInterval = $('#id_refreshRate').val();
        } else {
            $('#id_refreshRate').val(processList.refreshInterval);
        }
    },

    /**
     * Handles killing of a process
     *
     * @param object the event object
     *
     * @return void
     */
    killProcessHandler: function (event) {
        event.preventDefault();
        var url = $(this).attr('href');
        // Get row element of the process to be killed.
        var $tr = $(this).closest('tr');
        $.getJSON(url, function (data) {
            // Check if process was killed or not.
            if (data.hasOwnProperty('success') && data.success) {
                // remove the row of killed process.
                $tr.remove();
                // As we just removed a row, reapply odd-even classes
                // to keep table stripes consistent
                var $tableProcessListTr = $('#tableprocesslist').find('> tbody > tr');
                $tableProcessListTr.filter(':even').removeClass('odd').addClass('even');
                $tableProcessListTr.filter(':odd').removeClass('even').addClass('odd');
                // Show process killed message
                PMA_ajaxShowMessage(data.message, false);
            } else {
                // Show process error message
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    },

    /**
     * Handles Auto Refreshing
     *
     * @param object the event object
     *
     * @return void
     */
    refresh: function (event) {
        // abort any previous pending requests
        // this is necessary, it may go into
        // multiple loops causing unnecessary
        // requests even after leaving the page.
        processList.abortRefresh();
        // if auto refresh is enabled
        if (processList.autoRefresh) {
            var interval = parseInt(processList.refreshInterval, 10) * 1000;
            var urlParams = processList.getUrlParams();
            processList.refreshRequest = $.get(processList.refreshUrl,
                urlParams,
                function (data) {
                    if (data.hasOwnProperty('success') && data.success) {
                        $newTable = $(data.message);
                        $('#tableprocesslist').html($newTable.html());
                        PMA_highlightSQL($('#tableprocesslist'));
                    }
                    processList.refreshTimeout = setTimeout(
                        processList.refresh,
                        interval
                    );
                });
        }
    },

    /**
     * Stop current request and clears timeout
     *
     * @return void
     */
    abortRefresh: function () {
        if (processList.refreshRequest !== null) {
            processList.refreshRequest.abort();
            processList.refreshRequest = null;
        }
        clearTimeout(processList.refreshTimeout);
    },

    /**
     * Set label of refresh button
     * change between play & pause
     *
     * @return void
     */
    setRefreshLabel: function () {
        var img = 'play';
        var label = PMA_messages.strStartRefresh;
        if (processList.autoRefresh) {
            img = 'pause';
            label = PMA_messages.strStopRefresh;
            processList.refresh();
        }
        $('a#toggleRefresh').html(PMA_getImage(img) + escapeHtml(label));
    },

    /**
     * Return the Url Parameters
     * for autorefresh request,
     * includes showExecuting if the filter is checked
     *
     * @return urlParams - url parameters with autoRefresh request
     */
    getUrlParams: function () {
        var urlParams = { 'ajax_request': true, 'refresh': true };
        if ($('#showExecuting').is(':checked')) {
            urlParams.showExecuting = true;
            return urlParams;
        }
        return urlParams;
    }
};

AJAX.registerOnload('server_status_processes.js', function () {
    processList.init();
    // Bind event handler for kill_process
    $('#tableprocesslist').on(
        'click',
        'a.kill_process',
        processList.killProcessHandler
    );
    // Bind event handler for toggling refresh of process list
    $('a#toggleRefresh').on('click', function (event) {
        event.preventDefault();
        processList.autoRefresh = !processList.autoRefresh;
        processList.setRefreshLabel();
    });
    // Bind event handler for change in refresh rate
    $('#id_refreshRate').on('change', function (event) {
        processList.refreshInterval = $(this).val();
        processList.refresh();
    });
    // Bind event handler for table header links
    $('#tableprocesslist').on('click', 'thead a', function () {
        processList.refreshUrl = $(this).attr('href');
    });
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_status_processes.js', function () {
    $('#tableprocesslist').off('click', 'a.kill_process');
    $('a#toggleRefresh').off('click');
    $('#id_refreshRate').off('change');
    $('#tableprocesslist').off('click', 'thead a');
    // stop refreshing further
    processList.abortRefresh();
});
