/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used wherever an sql query form is used
 *
 * @version $Id$
 */

$(document).ready(function() {
    
    //SQL Query Submit
    $("#sqlqueryform").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        $.post($(this).attr('action'), $(this).serialize() , function(data) {
            $("#sqlqueryresults").html(data);
        })
    }) // end SQL Query submit

    //Paginate the results table
    $("input[name=navig]").live('click', function(event) {
        event.preventDefault();

        var the_form = $(this).parent("form");

        $(the_form).append('<input type="hidden" name="ajax_request" value="true" />');

        $.post($(the_form).attr('action'), $(the_form).serialize(), function(data) {
            $("#sqlqueryresults").html(data);
        })
    })// end Paginate results table
})