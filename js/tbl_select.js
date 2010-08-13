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
     * Ajax event handler for Table Search
     * 
     * @uses    PMA_ajaxShowMessage()
     */
    $("#tbl_search_form").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strSearching']);

        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        $.post($(this).attr('action'), $(this).serialize(), function(data) {
            $("#searchresults").html(data);
        })
    })
}, 'top.frame_content'); // end $(document).ready()