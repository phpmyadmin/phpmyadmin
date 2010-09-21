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
     * Ajax event handler for Table Search
     * 
     * @uses    PMA_ajaxShowMessage()
     */
    $("#tbl_search_form").live('submit', function(event) {
        // jQuery object to reuse
        $search_form = $(this);
        event.preventDefault();

        // empty previous search results while we are waiting for new results
        $("#searchresults").empty();
        PMA_ajaxShowMessage(PMA_messages['strSearching']);

	    // add this hidden field just once 
	    if (! $search_form.find('input:hidden').is('#ajax_request_hidden')) {
        	$search_form.append('<input type="hidden" id="ajax_request_hidden" name="ajax_request" value="true" />');
	    }

        $.post($search_form.attr('action'), $search_form.serialize(), function(response) {
            if (typeof response == 'string') {
                // found results
                $("#searchresults").html(response);
            } else {
                // error message (zero rows)
                $("#searchresults").html(response['message']);
            }
        })
    })
}, 'top.frame_content'); // end $(document).ready()
