/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used wherever an sql query form is used
 *
 * @version $Id$
 */

$("#sqlqueryform").live('submit', function(event) {
    event.preventDefault();

    PMA_ajaxShowMessage();

    $(this).append('<input type="hidden" name="ajax_request" value="true" />');

    $.post($(this).attr('action'), $(this).serialize() , function(data) {
        $("#sqlqueryresults").html(data);
    })
})
