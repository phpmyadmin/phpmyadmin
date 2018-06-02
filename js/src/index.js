import { AJAX } from './ajax';
import './variables/import_variables';

/**
 * Page load event handler
 */
$(function () {
    console.log('check1');
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
 * Attach a generic event handler to clicks
 * on pages and submissions of forms
 */
$(document).on('click', 'a', AJAX.requestHandler);
$(document).on('submit', 'form', AJAX.requestHandler);

import('./server_databases')
.then((module) => {

        console.log('adasdsadassadasdsasad');
        AJAX.registerOnload('server_databases_new.js', module.onload1);
        AJAX.registerTeardown('server_databases_new.js', module.teardown1);
        AJAX.fireOnload('server_databases_new.js');
        // AJAX.fireTeardown('server_databases_new.js');


})
.catch(e => console.log(e));

if( 1 === 1 ) {
    import('./server_privileges')
    .then((module) => {
        AJAX.registerOnload('server_privileges_new.js', module.onload1);
        AJAX.registerTeardown('server_privileges_new.js', module.teardown1);
        AJAX.fireOnload('server_privileges_new.js');
        // AJAX.fireTeardown('server_databases_new.js');
    })
    .catch(e => console.log(e));
}

// server_databases();
