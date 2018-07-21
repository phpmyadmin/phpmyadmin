/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import './variables/import_variables';
import { $ } from './utils/JqueryExtended';
import { AJAX } from './ajax';
import './variables/get_config';
import './variables/InlineScripting';
import files from './consts/files';
import Console from './console';
import { PMA_sprintf } from './utils/sprintf';
import { PMA_Messages as PMA_messages } from './variables/export_variables';
import { escapeHtml } from './utils/Sanitise';
import { PMA_ajaxShowMessage } from './utils/show_ajax_messages';
import PMA_commonParams from './variables/common_params';
import PMA_MicroHistory from './classes/MicroHistory';

/**
 * Page load event handler
 */
$(function () {
    var menuContent = $('<div></div>')
        .append($('#serverinfo').clone())
        .append($('#topmenucontainer').clone())
        .html();
    if (history && history.pushState) {
        // set initial state reload
        var initState = ('state' in window.history && window.history.state !== null);
        var initURL = $('#selflink').find('> a').attr('href') || location.href;
        var state = {
            url : initURL,
            menu : menuContent
        };
        history.replaceState(state, null);

        $(window).on('popstate', function (event) {
            var initPop = (! initState && location.href === initURL);
            initState = true;
            // check if popstate fired on first page itself
            if (initPop) {
                return;
            }
            var state = event.originalEvent.state;
            if (state && state.menu) {
                AJAX.$msgbox = PMA_ajaxShowMessage();
                var params = 'ajax_request=true' + PMA_commonParams.get('arg_separator') + 'ajax_page_request=true';
                var url = state.url || location.href;
                $.get(url, params, AJAX.responseHandler);
                // TODO: Check if sometimes menu is not retrieved from server,
                // Not sure but it seems menu was missing only for printview which
                // been removed lately, so if it's right some dead menu checks/fallbacks
                // may need to be removed from this file and Header.php
                // AJAX.handleMenu.replace(event.originalEvent.state.menu);
            }
        });
    } else {
        // Fallback to microhistory mechanism
        AJAX.scriptHandler
            .load([{ 'name' : 'microhistory.js', 'fire' : 1 }], function () {
                // The cache primer is set by the footer class
                if (PMA_MicroHistory.primer.url) {
                    PMA_MicroHistory.menus.add(
                        PMA_MicroHistory.primer.menuHash,
                        menuContent
                    );
                }
                $(function () {
                    // Queue up this event twice to make sure that we get a copy
                    // of the page after all other onload events have been fired
                    if (PMA_MicroHistory.primer.url) {
                        PMA_MicroHistory.add(
                            PMA_MicroHistory.primer.url,
                            PMA_MicroHistory.primer.scripts,
                            PMA_MicroHistory.primer.menuHash
                        );
                    }
                });
            });
    }
});

$(document).ajaxError(function (event, request, settings) {
    if (AJAX._debug) {
        console.log('AJAX error: status=' + request.status + ', text=' + request.statusText);
    }
    // Don't handle aborted requests
    if (request.status !== 0 || request.statusText !== 'abort') {
        var details = '';
        var state = request.state();

        if (request.status !== 0) {
            details += '<div>' + escapeHtml(PMA_sprintf(PMA_messages.strErrorCode, request.status)) + '</div>';
        }
        details += '<div>' + escapeHtml(PMA_sprintf(PMA_messages.strErrorText, request.statusText + ' (' + state + ')')) + '</div>';
        if (state === 'rejected' || state === 'timeout') {
            details += '<div>' + escapeHtml(PMA_messages.strErrorConnection) + '</div>';
        }
        PMA_ajaxShowMessage(
            '<div class="error">' +
            PMA_messages.strErrorProcessingRequest +
            details +
            '</div>',
            false
        );
        AJAX.active = false;
        AJAX.xhr = null;
    }
});

/**
 * Adding common files for every page
 */
for (let i in files.global) {
    AJAX.scriptHandler.add(files.global[i], 1);
}
/**
 * This block of code is for importing javascript files needed
 * for the first time loading of the page.
 */
let firstPage = window.location.pathname.replace('/', '').replace('.php', '');
let indexStart = window.location.search.indexOf('target') + 7;
let indexEnd = window.location.search.indexOf('.php');
let indexPage = window.location.search.slice(indexStart, indexEnd);
if (typeof files[firstPage] !== 'undefined' && firstPage.toLocaleLowerCase() !== 'index') {
    for (let i in files[firstPage]) {
        AJAX.scriptHandler.add(files[firstPage][i], 1);
    }
} else if (typeof files[indexPage] !== 'undefined' && firstPage.toLocaleLowerCase() === 'index') {
    for (let i in files[indexPage]) {
        AJAX.scriptHandler.add(files[indexPage][i], 1);
    }
}

$(function () {
    Console.initialize();
});
