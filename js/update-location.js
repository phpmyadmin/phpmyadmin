/* vim: set expandtab sw=4 ts=4 sts=4: */

// TODO: merge this file into ajax.js

/**
 * URL hash management module.
 * Allows direct bookmarking and microhistory.
 */
var PMA_setUrlHash = (function (jQuery, window) {
    /**
     * Indictaes whether we have already completed
     * the initialisation of the hash
     *
     * @access private
     */
    var ready = false;
    /**
     * Stores a hash that needed to be set when we were not ready
     *
     * @access private
     */
    var savedHash = "";
    /**
     * Flag to indicate if the change of hash was triggered
     * by a user pressing the back/forward button or if
     * the change was triggered internally
     *
     * @access private
     */
    var userChange = true;

    /**
     * Sets the hash part of the URL
     *
     * @access public
     */
    function setUrlHash(index, hash) {
        if (jQuery.browser.webkit) {
            /*
             * Setting hash leads to reload in webkit:
             * http://www.quirksmode.org/bugreports/archives/2005/05/Safari_13_visual_anomaly_with_windowlocationhref.html
             */
            return;
        }

        userChange = false;
        if (ready) {
            window.location.hash = "PMAURL-" + index + ":" + hash;
        } else {
            savedHash = "PMAURL-" + index + ":" + hash;
        }
    }

    /**
     * Start initialisation
     */
    if (window.location.hash.substring(0, 8) == '#PMAURL-') {
        // We have a valid hash, let's redirect the user
        // to the page that it's pointing to
        window.location = window.location.hash.substring(
            window.location.hash.indexOf(':') + 1
        );
    } else {
        // We don't have a valid hash, so we'll set it up
        // when the page finishes loading
        jQuery(function(){
            /* Check if we should set URL */
            if (savedHash != "") {
                window.location.hash = savedHash;
                savedHash = "";
            }
            // Indicate that we're done initialising
            ready = true;
        });
    }

    /**
     * Register an event handler for when the url hash changes
     */
    jQuery(function(){
        jQuery(window).hashchange(function () {
            if (userChange === false) {
                // Ignore internally triggered hash changes
                userChange = true;
            } else if (/^#PMAURL-\d+:/.test(window.location.hash)) {
                // Change page if the hash changed was triggered by a user action
                var index = window.location.hash.substring(
                    8, window.location.hash.indexOf(':')
                );
                AJAX.cache.navigate(index);
            }
        });
    });

    return setUrlHash;
})(jQuery, window);
