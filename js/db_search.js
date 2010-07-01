/**
 * JavaScript functions used on db_search.php
 */

$(document).ready(function() {
    $("#db_search_form").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strSearching']);

        $(this).append('<input type="hidden" name="ajax_request" value="true"');

        $.get($(this).attr('action'), $(this).serialize() + "&submit_search=" + $("#buttonGo").val(), function(data) {
            $("#searchresults").html(data);
        }) // end $.get()
    })
}, 'top.frame_content'); // end $(document).ready()