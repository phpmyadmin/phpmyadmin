/**
 * @fileoverview    JavaScript functions used on Database Search page
 * @name            Database Search
 *
 * @requires    jQuery
 * @requires    js/functions.js
 */

/**
 * AJAX script for the Database Search page.
 *
 * Actions ajaxified here:
 * Retrieve result of SQL query
 */

$(document).ready(function() {

    /**
     * Set a parameter for all Ajax queries made on this page.  Append a random
     * number to tell server that each Ajax request is a new one
     */
    $.ajaxSetup({
        data: {'random': function() {
                return Math.random();
        }}
    });

    /**
     * Ajax Event handler for retrieving the result of an SQL Query
     *
     * @uses    PMA_ajaxShowMessage()
     */
    $("#db_search_form").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strSearching']);

        $(this).append('<input type="hidden" name="ajax_request" value="true"');

        $.get($(this).attr('action'), $(this).serialize() + "&submit_search=" + $("#buttonGo").val(), function(data) {
            $("#searchresults").html(data);
        }) // end $.get()
    })
}, 'top.frame_content'); // end $(document).ready()