/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import processList from './classes/Server/ProcessList';

/**
 * @package PhpMyAdmin
 *
 * Server Status Processes
 */

/**
 * Binding event handlers on page load
 */
function onloadServerStatusProcesses () {
    processList.init();
    // Bind event handler for kill_process
    $('#tableprocesslist').on('click', 'a.kill_process', function (event) {
        processList.killProcessHandler(event, this);
    });
    // Bind event handler for toggling refresh of process list
    $('a#toggleRefresh').on('click', function (event) {
        event.preventDefault();
        processList.autoRefresh = !processList.autoRefresh;
        processList.setRefreshLabel();
    });
    // Bind event handler for change in refresh rate
    $('#id_refreshRate').on('change', function () {
        processList.refreshInterval = $(this).val();
        processList.refresh();
    });
    // Bind event handler for table header links
    $('#tableprocesslist').on('click', 'thead a', function () {
        processList.refreshUrl = $(this).attr('href');
    });
}

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownServerStatusProcesses () {
    $('#tableprocesslist').off('click', 'a.kill_process');
    $('a#toggleRefresh').off('click');
    $('#id_refreshRate').off('change');
    $('#tableprocesslist').off('click', 'thead a');
    // stop refreshing further
    processList.abortRefresh();
}

/**
 * Module export
 */
export {
    teardownServerStatusProcesses,
    onloadServerStatusProcesses
};
