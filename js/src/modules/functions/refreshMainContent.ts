import $ from 'jquery';
import { CommonParams } from '../common.js';

/**
 * Refreshes the main frame
 *
 * @param {any} url Undefined to refresh to the same page
 *                  String to go to a different page, e.g: 'index.php'
 *
 * @return {void}
 */
export default function refreshMainContent (url) {
    var newUrl = url;
    if (! newUrl) {
        newUrl = $('#selflink').find('a').attr('href') || window.location.pathname;
        newUrl = newUrl.substring(0, newUrl.indexOf('?'));
    }
    if (newUrl.indexOf('?') !== -1) {
        newUrl += CommonParams.getUrlQuery(CommonParams.get('arg_separator'));
    } else {
        newUrl += CommonParams.getUrlQuery('?');
    }
    $('<a></a>', { href: newUrl })
        .appendTo('body')
        .trigger('click')
        .remove();
}
