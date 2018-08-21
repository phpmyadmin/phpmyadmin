/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { $ } from './utils/JqueryExtended';
import './plugins/jquery/jquery.tablesorter';

/**
 * @package PhpMyAdmin
 *
 * Server Plugins
 */

/**
 * Binding event handlers on page load.
 */
function onloadServerPlugins () {
    // Make columns sortable, but only for tables with more than 1 data row
    var $tables = $('#plugins_plugins table:has(tbody tr + tr)');
    $tables.tablesorter({
        sortList: [[0, 0]],
        headers: {
            1: { sorter: false }
        }
    });
    $tables.find('thead th')
        .append('<div class="sorticon"></div>');
}

/**
 * Module export
 */
export {
    onloadServerPlugins
};
