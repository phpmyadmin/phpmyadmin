import { AJAX } from './ajax';
import './variables/import_variables';
import './variables/get_config';
import { jQuery as $ } from './utils/JqueryExtended';
import files from './consts/files';
import Console from './console';

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

/**
 * Adding common files for every page
 */
for (let i in files.global) {
    AJAX.scriptHandler.add(files.global[i]);
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
