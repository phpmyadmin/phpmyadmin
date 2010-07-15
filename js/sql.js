/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used wherever an sql query form is used
 *
 * @version $Id$
 */

$(document).ready(function() {

    /**
     * @var disp_mode   current value of the direction in which the table is displayed
     */
    var disp_mode = $("#top_direction_dropdown").val();

    $("#top_direction_dropdown, #bottom_direction_dropdown").live('change', function(event) {
        disp_mode = $(this).val();
    })

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
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
            }
            else if (data.success == false ) {
                PMA_ajaxShowMessage(data.error);
            }
            else {
                $("#sqlqueryresults").html(data);
                if($("#togglequerybox").siblings(":visible").length > 0) {
                $("#togglequerybox").trigger('click');
                }
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

    //Paginate results with Page Selector
    $("#pageselector").live('change', function(event) {
        event.preventDefault();

        //PMA_ajaxShowMessage();

        $.get($(this).attr('href'), $(this).serialize() + '&ajax_request=true', function(data) {
            $("#sqlqueryresults").html(data);
        })
    })// end Paginate results with Page Selector

    //Sort results table
    $("#table_results").find("a[title=Sort]").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        $.get($(this).attr('href'), $(this).serialize() + '&ajax_request=true', function(data) {
            $("#sqlqueryresults").html(data);
        })
    })//end Sort results table

    //displayOptionsForm handler
    $("#displayOptionsForm").live('submit', function(event) {
        event.preventDefault();

        $.post($(this).attr('action'), $(this).serialize() + '&ajax_request=true' , function(data) {
            $("#sqlqueryresults").html(data);
        })
    })
    //end displayOptionsForm handler

    //Inline Edit
    $(".edit_row_anchor").live('click', function(event) {
        event.preventDefault();

        $(this).removeClass('edit_row_anchor').addClass('edit_row_anchor_active');
        
        if(disp_mode == 'vertical') {
            var this_row_index = $(this).index();
            var input_siblings = $(this).parents('tbody').find('tr').find('.data_inline_edit:nth('+this_row_index+')');
        }
        else {
            var input_siblings = $(this).parent('tr').find('.data_inline_edit');
        }

        $(input_siblings).each(function() {
            var data_value = $(this).html();

            $(this).html('<textarea>'+data_value+'</textarea>');
        })
    })

    $(".edit_row_anchor_active").live('click', function(event) {
        event.preventDefault();

        $(this).removeClass('edit_row_anchor_active').addClass('edit_row_anchor');

        if(disp_mode == 'vertical') {
            var this_row_index = $(this).index();
            var input_siblings = $(this).parents('tbody').find('tr').find('.data_inline_edit:nth('+this_row_index+')');
        }
        else {
            var input_siblings = $(this).parent('tr').find('.data_inline_edit');
        }

        $(input_siblings).each(function() {
            var new_data_value = $(this).find('textarea').html();

            $(this).html(new_data_value);
        })
    })
})