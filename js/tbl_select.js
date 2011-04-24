/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview JavaScript functions used on tbl_select.php
 *
 * @requires    jQuery
 * @requires    js/functions.js
 */

/**
 * Ajax event handlers for this page
 *
 * Actions ajaxified here:
 * Table Search
 */
$(document).ready(function() {

    /**
     * Set a parameter for all Ajax queries made on this page.  Don't let the
     * web server serve cached pages
     */
    $.ajaxSetup({
        cache: 'false'
    });

    /**
     * Prepare a div containing a link, otherwise it's incorrectly displayed 
     * after a couple of clicks
     */
    $('<div id="togglesearchformdiv"><a id="togglesearchformlink"></a></div>')
     .insertAfter('#tbl_search_form')
     // don't show it until we have results on-screen
     .hide();

    $('#togglesearchformlink')
        .html(PMA_messages['strShowSearchCriteria'])
        .bind('click', function() {
            var $link = $(this);
            $('#tbl_search_form').slideToggle();
            if ($link.text() == PMA_messages['strHideSearchCriteria']) {
                $link.text(PMA_messages['strShowSearchCriteria']);
            } else {
                $link.text(PMA_messages['strHideSearchCriteria']);
            }
            // avoid default click action
            return false;
        });

    /**
     * Ajax event handler for Table Search
     * 
     * (see $GLOBALS['cfg']['AjaxEnable'])
     * @uses    PMA_ajaxShowMessage()
     */
    $("#tbl_search_form.ajax").live('submit', function(event) {
        // jQuery object to reuse
        $search_form = $(this);
        event.preventDefault();

        // empty previous search results while we are waiting for new results
        $("#sqlqueryresults").empty();
        var msgbox = PMA_ajaxShowMessage(PMA_messages['strSearching']);

        PMA_prepareForAjaxRequest($search_form);

        $.post($search_form.attr('action'), $search_form.serialize(), function(response) {
            if (typeof response == 'string') {
                // found results
                $("#sqlqueryresults").html(response);
                $("#sqlqueryresults").trigger('appendAnchor');
                $('#tbl_search_form')
                // work around for bug #3168569 - Issue on toggling the "Hide search criteria" in chrome.
                 .slideToggle()	
                 .hide();
                $('#togglesearchformlink')
                 // always start with the Show message
                 .text(PMA_messages['strShowSearchCriteria'])
                $('#togglesearchformdiv')
                 // now it's time to show the div containing the link 
                 .show();
            } else {
                // error message (zero rows)
                $("#sqlqueryresults").html(response['message']);
            }
            
            msgbox.clearQueue().fadeOut('medium', function() {
                $(this).hide();
            });
        }) // end $.post()
    })
}, 'top.frame_content'); // end $(document).ready()
