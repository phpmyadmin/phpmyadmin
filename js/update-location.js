/* vim: set expandtab sw=4 ts=4 sts=4: */
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
function setURLHash(hash)
{
    if (jQuery.browser.webkit) {
        /*
         * Setting hash leads to reload in webkit:
         * http://www.quirksmode.org/bugreports/archives/2005/05/Safari_13_visual_anomaly_with_windowlocationhref.html
         */
        return;
    }
    if (window.parent != window && window.parent.setURLHash) {
        window.parent.setURLHash(hash);
    } else {
        /* Do not set if we're not updating frameset */
        var path = window.location.pathname;
        if (path.substring(path.length - 9, path.length) != "index.php") {
            return;
        }
        if (hash_init_done) {
            window.location.hash = "PMAURL:" + hash;
            fix_favicon();
        } else {
            hash_to_set = "PMAURL:" + hash;
        }
    }
}

// Fix favicon disappearing in Firefox when setting location.hash
// See bug #3448485
function fix_favicon() {
    if (jQuery.browser.mozilla) {
        // Move the link tags for the favicon to the bottom
        // of the head element to force a reload of the favicon
        $('head > link[href=\\.\\/favicon\\.ico]').appendTo('head');
    }
}

/**
 * Handler for changing url according to the hash part, which is updated
 * on each page to allow bookmarks.
 */
$(document).ready(function(){
    /* Don't do anything if we're not root Window */
    if (window.parent != window && window.parent.setURLHash) {
        return;
    }
    /* Check if hash contains parameters */
    if (window.location.hash.substring(0, 8) == '#PMAURL:') {
        window.location = 'index.php?' + window.location.hash.substring(8);
        return;
    }
    /* Check if we should set URL */
    if (hash_to_set != "") {
        window.location.hash = hash_to_set;
        hash_to_set = "";
        fix_favicon();
    }
    /* Indicate that we're done (and we are not going to change location */
    hash_init_done = 1;
});
