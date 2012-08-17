/* vim: set expandtab sw=4 ts=4 sts=4: */

// TODO: merge this file into ajax.js


/**
 * Scripts to update location to allow bookmarking of frameset
 * and restoring the bookmark once the page is loaded.
 *
 */

var hash_to_set = "";
var hash_init_done = 0;

/**
 * Sets hash part in URL, either calls itself in parent frame or does the
 * work itself. The hash is not set directly if we did not yet process old
 * one.
 */
function setURLHash(index, hash)
{
    settingHash = true;
    if (jQuery.browser.webkit) {
        /*
         * Setting hash leads to reload in webkit:
         * http://www.quirksmode.org/bugreports/archives/2005/05/Safari_13_visual_anomaly_with_windowlocationhref.html
         */
        return;
    }
    if (hash_init_done) {
        window.location.hash = "PMAURL-" + index + ":" + hash;
    } else {
        hash_to_set = "PMAURL-" + index + ":" + hash;
    }
}

/**
 * Handler for changing url according to the hash part, which is updated
 * on each page to allow bookmarks.
 */
$(function(){
    /* Check if hash contains parameters */
    if (window.location.hash.substring(0, 8) == '#PMAURL-') {
        // FIXME: don't reload if the page is the same
        window.location = window.location.hash.substring(
            window.location.hash.indexOf(':') + 1
        );
        return;
    }
    /* Check if we should set URL */
    if (hash_to_set != "") {
        window.location.hash = hash_to_set;
        hash_to_set = "";
    }
    /* Indicate that we're done (and we are not going to change location */
    hash_init_done = 1;
});
