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
            var where_clause = $(this).parents('tbody').find('tr').find('.where_clause:nth('+this_row_index+')').val();
        }
        else {
            var input_siblings = $(this).parent('tr').find('.data_inline_edit');
            var where_clause = $(this).parent('tr').find('.where_clause').val();
        }

        if($(this).is('.nonunique')) {
            var nonunique = true;
        }
        else {
            var nonunique = false;
        }

        $(input_siblings).each(function() {
            var data_value = $(this).html();

            if($(this).is(':not(.truncated, .transformed, .relation)')) {
                //handle non-truncated, non-transformed, non-relation values
                $(this).html('<textarea>'+data_value+'</textarea>')
                .append('<span class="original_data">'+data_value+'</span>');
                $(".original_data").hide();
            }
            else if($(this).not('.transformed').is('.truncated')) {
                //handle truncated values
                
                if(disp_mode == 'vertical') {
                    var this_field = $(this);
                    var field_name = $(this).siblings('th').text();
                    field_name = $.trim(field_name);
                    
                    var sql_query = 'SELECT ' + field_name + ' FROM ' + window.parent.table + ' WHERE ' + where_clause;

                    $.post('sql.php', {
                        'token' : window.parent.token,
                        'db' : window.parent.db,
                        'ajax_request' : true,
                        'sql_query' : sql_query,
                        'inline_edit' : true
                    }, function(data) {
                        if(data.success == true) {
                            $(this_field).html('<textarea>'+data.value+'</textarea>')
                            .append('<span class="original_data">'+data_value+'</span>');
                            $(".original_data").hide();
                        }
                        else {
                            PMA_ajaxShowMessage(data.error);
                        }
                    })
                }
                else {
                    alert('where clause '+where_clause);
                    //var field_name = $(this).parents('table').find('thead').find('th:nth('+this_row_index+')').html();
                    //alert(field_name);
                }
            }
            else if($(this).is('.transformed')) {
                //handle transformed values
            }
            else if($(this).is('.relation')) {
                //handle relations
            }
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
            var new_data_value = $(this).find('.original_data').html();

            if($(this).is(':not(.transformed, .relation)')) {
                $(this).html(new_data_value);
            }
        })
    })
})