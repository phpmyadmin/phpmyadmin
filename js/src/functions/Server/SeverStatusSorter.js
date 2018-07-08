/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { $ } from '../../utils/extend_jquery';
import '../../plugins/jquery/jquery.tablesorter';
// TODO: tablesorter shouldn't sort already sorted columns
/**
 * @access public
 *
 * @param {string} tabid Table id for chart drawing
 *
 * @return {void}
 */
function initTableSorter (tabid) {
    var $table;
    var opts;
    switch (tabid) {
    case 'statustabs_queries':
        $table = $('#serverstatusqueriesdetails');
        opts = {
            sortList: [[3, 1]],
            headers: {
                1: { sorter: 'fancyNumber' },
                2: { sorter: 'fancyNumber' }
            }
        };
        break;
    }
    $table.tablesorter(opts);
    $table.find('tr:first th')
        .append('<div class="sorticon"></div>')
        .addClass('header');
}

/**
 * Module export
 */
export {
    initTableSorter
};
