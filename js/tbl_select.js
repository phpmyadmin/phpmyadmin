/**
 * JavaScript functions used on tbl_select.php
 */
$(document).ready(function() {

    $("#tbl_search_form").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strSearching']);

        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        $.post($(this).attr('action'), $(this).serialize(), function(data) {
            $("#searchresults").html(data);
        })
    })
}, 'top.frame_content');