/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used wherever an sql query form is used
 *
 * @version $Id$
 */

$(document).ready(function() {

    $('<span id="togglequerybox"></span>')
    .html(PMA_messages['strToggleQueryBox'])
    .appendTo("#sqlqueryform");

    $("#togglequerybox").live('click', function() {
        $(this).siblings().slideToggle("medium");
    })
    
    //SQL Query Submit
    $("#sqlqueryform").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        $.post($(this).attr('action'), $(this).serialize() , function(data) {
            $("#sqlqueryresults").html(data);
            if($("#togglequerybox").siblings(":visible").length > 0) {
            $("#togglequerybox").trigger('click');
            }
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

    //Sort results table
    $("#table_results").find("a[title=Sort]").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        $.get($(this).attr('href'), $(this).serialize() + '&ajax_request=true', function(data) {
            $("#sqlqueryresults").html(data);
        })
    })//end Sort results table
})