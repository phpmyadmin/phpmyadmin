/* vim: set expandtab sw=4 ts=4 sts=4: */
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
     * Set a parameter for all Ajax queries made on this page.  Don't let the
     * web server serve cached pages
     */
    $.ajaxSetup({
        cache: 'false'
    });

    /**
     * Ajax Event handler for retrieving the result of an SQL Query
     * (see $GLOBALS['cfg']['AjaxEnable'])
     *
     * @uses    PMA_ajaxShowMessage()
     */
    $("#db_search_form.ajax").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strSearching']);

        $form = $(this);

        if (! $form.find('input:hidden').is('#ajax_request_hidden')) {
            $form.append('<input type="hidden" id="ajax_request_hidden" name="ajax_request" value="true" />');
        }

        $.get($form.attr('action'), $form.serialize() + "&submit_search=" + $("#buttonGo").val(), function(data) {
            $("#searchresults").html(data);
        }) // end $.get()
    })
}, 'top.frame_content'); // end $(document).ready()
