/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used wherever an sql query form is used
 *
 * @version $Id$
 */

/**
 * Get the field name for the current field.  Required to construct the query
 * for inline editing
 *
 * @param   this_field_obj  jQuery object that points to the current field's tr
 * @param   disp_mode       string
 */
function getFieldName(this_field_obj, disp_mode) {

    if(disp_mode == 'vertical') {
        var field_name = $(this_field_obj).siblings('th').find('a').text();
    }
    else {
        var this_field_index = $(this_field_obj).index();
        if(window.parent.text_dir == 'ltr') {
            // 4 columns to account for the checkbox, edit, delete and appended inline edit anchors
            var field_name = $(this_field_obj).parents('table').find('thead').find('th:nth('+ (this_field_index-4 )+') a').text();
        }
        else {
            var field_name = $(this_field_obj).parents('table').find('thead').find('th:nth('+ this_field_index+') a').text();
        }
    }

    field_name = $.trim(field_name);

    return field_name;
}

/**
 * The function that iterates over each row in the table_results and appends a
 * new inline edit anchor to each table row.
 *
 * @param   string  disp_mode
 */
function appendInlineAnchor(disp_mode) {
    if(disp_mode == 'vertical') {
        var cloned_row = $('.edit_row_anchor').removeClass('edit_row_anchor').parent('tr').clone();

        $(cloned_row).find('td').addClass('edit_row_anchor')
        .find('a').attr('href', '#')
        .find('img').attr('title', 'Inline Edit');

        $(cloned_row).insertBefore($('.where_clause').parent('tr'));

        $("#table_results").find('tr:first').find('th')
        .attr('rowspan', '4');
    }
    else {
        $('.edit_row_anchor').each(function() {

            $(this).removeClass('edit_row_anchor');

            var cloned_anchor = $(this).clone();

            $(cloned_anchor).addClass('edit_row_anchor')
            .find('a').attr('href', '#')
            .find('img').attr('title', 'Inline Edit');

            $(this).siblings('.where_clause')
            .before(cloned_anchor);
        });

        $('#rowsDeleteForm').find('thead').find('th').each(function() {
            if($(this).attr('colspan') == 3) {
                $(this).attr('colspan', '4')
            }
        })
    }
}

$(document).ready(function() {

    /**
     * @var disp_mode   current value of the direction in which the table is displayed
     */
    var disp_mode = $("#top_direction_dropdown").val();

    $("#top_direction_dropdown, #bottom_direction_dropdown").live('change', function(event) {
        disp_mode = $(this).val();
    })

    $("#sqlqueryresults").live('appendAnchor',function() {
        appendInlineAnchor(disp_mode);
    })

    $("#sqlqueryresults").trigger('appendAnchor');

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
                $("#sqlqueryresults").trigger('appendAnchor');
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
            $("#sqlqueryresults").trigger('appendAnchor');
        })
    })// end Paginate results table

    //Paginate results with Page Selector
    $("#pageselector").live('change', function(event) {
        event.preventDefault();

        //PMA_ajaxShowMessage();

        $.get($(this).attr('href'), $(this).serialize() + '&ajax_request=true', function(data) {
            $("#sqlqueryresults").html(data);
            $("#sqlqueryresults").trigger('appendAnchor');
        })
    })// end Paginate results with Page Selector

    //Sort results table
    $("#table_results").find("a[title=Sort]").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        $.get($(this).attr('href'), $(this).serialize() + '&ajax_request=true', function(data) {
            $("#sqlqueryresults").html(data);
            $("#sqlqueryresults").trigger('appendAnchor');
        })
    })//end Sort results table

    //displayOptionsForm handler
    $("#displayOptionsForm").live('submit', function(event) {
        event.preventDefault();

        $.post($(this).attr('action'), $(this).serialize() + '&ajax_request=true' , function(data) {
            $("#sqlqueryresults").html(data);
            $("#sqlqueryresults").trigger('appendAnchor');
        })
    })
    //end displayOptionsForm handler

    // Inline Edit

    // On click, replace the current field with an input/textarea
    $(".edit_row_anchor").live('click', function(event) {
        event.preventDefault();

        $(this).removeClass('edit_row_anchor').addClass('edit_row_anchor_active');

        // Initialize some variables
        if(disp_mode == 'vertical') {
            var this_row_index = $(this).index();
            var input_siblings = $(this).parents('tbody').find('tr').find('.data_inline_edit:nth('+this_row_index+')');
            var where_clause = $(this).parents('tbody').find('tr').find('.where_clause:nth('+this_row_index+')').val();
        }
        else {
            var input_siblings = $(this).parent('tr').find('.data_inline_edit');
            var where_clause = $(this).parent('tr').find('.where_clause').val();
        }

        $(input_siblings).each(function() {
            var data_value = $(this).html();

            // We need to retrieve the value from the server for truncated/relation fields
            // Find the field name
            var this_field = $(this);
            var field_name = getFieldName($(this), disp_mode);

            // In each input sibling, wrap the current value in a textarea
            // and store the current value in a hidden span
            if($(this).is(':not(.truncated, .transformed, .relation, .enum)')) {
                // handle non-truncated, non-transformed, non-relation values
                // We don't need to get any more data, just wrap the value
                $(this).html('<textarea>'+data_value+'</textarea>')
                .append('<span class="original_data">'+data_value+'</span>');
                $(".original_data").hide();
            }
            else if($(this).is('.truncated, .transformed')) {
                //handle truncated/transformed values values

                var sql_query = 'SELECT ' + field_name + ' FROM ' + window.parent.table + ' WHERE ' + where_clause;

                // Make the Ajax call and get the data, wrap it and insert it
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
            else if($(this).is('.relation')) {
                //handle relations

                var curr_value = $(this).find('a').text();

                var post_params = {
                        'ajax_request' : true,
                        'get_relational_values' : true,
                        'db' : window.parent.db,
                        'table' : window.parent.table,
                        'column' : field_name,
                        'token' : window.parent.token,
                        'curr_value' : curr_value
                }

                $.post('sql.php', post_params, function(data) {
                    $(this_field).html(data.dropdown)
                    .append('<span class="original_data">'+data_value+'</span>');
                    $(".original_data").hide();
                })
            }
            else if($(this).is('.enum')) {
                //handle enum fields
                var curr_value = $(this).text();

                var post_params = {
                        'ajax_request' : true,
                        'get_enum_values' : true,
                        'db' : window.parent.db,
                        'table' : window.parent.table,
                        'column' : field_name,
                        'token' : window.parent.token,
                        'curr_value' : curr_value
                }

                $.post('sql.php', post_params, function(data) {
                    $(this_field).html(data.dropdown)
                    .append('<span class="original_data">'+data_value+'</span>');
                    $(".original_data").hide();
                })
            }
        })
    }) // End On click, replace the current field with an input/textarea

    // After editing, clicking again should post data
    $(".edit_row_anchor_active").live('click', function(event) {
        event.preventDefault();

        var this_row = $(this);

        // Initialize variables
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
            var nonunique = 0;
        }
        else {
            var nonunique = 1;
        }

        // Collect values of all fields to submit, we don't know which changed
        var params_to_submit = {};
        var relation_fields = {};
        var transform_fields = {};
        var transformation_fields = false;

        $(input_siblings).each(function() {

            var this_field = $(this);
            var field_name = getFieldName($(this), disp_mode);

            var this_field_params = {};

            if($(this).is('.transformed')) {
                transformation_fields =  true;
            }

            if($(this).is(":not(.relation, .enum)")) {
                this_field_params[field_name] = $(this).find('textarea').val();
                if($(this).is('.transformed')) {
                    $.extend(transform_fields, this_field_params);
                }
            }
            else {
                this_field_params[field_name] = $(this).find('select').val();

                if($(this).is('.relation')) {
                    $.extend(relation_fields, this_field_params);
                }
            }

            $.extend(params_to_submit, this_field_params);
        })

        //generate the SQL query to update this row
        var sql_query = 'UPDATE ' + window.parent.table + ' SET ';

        $.each(params_to_submit, function(key, value) {
            sql_query += ' ' + key + "='" + value + "' , ";
        })
        sql_query = sql_query.replace(/,\s$/, '');
        sql_query += ' WHERE ' + where_clause;

        var rel_fields_list = $.param(relation_fields);
        
        var transform_fields_list = $.param(transform_fields);

        // Make the Ajax post after setting all parameters
        var post_params = {'ajax_request' : true,
                            'sql_query' : sql_query,
                            'disp_direction' : disp_mode,
                            'token' : window.parent.token,
                            'db' : window.parent.db,
                            'table' : window.parent.table,
                            'clause_is_unique' : nonunique,
                            'where_clause' : where_clause,
                            'rel_fields_list' : rel_fields_list,
                            'do_transformations' : transformation_fields,
                            'transform_fields_list' : transform_fields_list,
                            'goto' : 'sql.php'
                          };

        $.post('tbl_replace.php', post_params, function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
                $(this_row).removeClass('edit_row_anchor_active').addClass('edit_row_anchor');

                $(input_siblings).each(function() {
                    // Inline edit post has been successful.
                    if($(this).is(':not(.relation, .enum)')) {
                        var new_html = $(this).find('textarea').val();

                        if($(this).is('.transformed')) {
                            var field_name = getFieldName($(this), disp_mode);
                            var this_field = $(this);

                            $.each(data.transformations, function(key, value) {
                                if(key == field_name) {
                                    if($(this_field).is('.text_plain, .application_octetstream')) {
                                        new_html = value;
                                        return false;
                                    }
                                    else {
                                        var new_value = $(this_field).find('textarea').val();
                                        new_html = $(value).append(new_value);
                                        return false;
                                    }
                                }
                            })
                        }
                    }
                    else {
                        var new_html = $(this).find('select').val();
                        if($(this).is('.relation')) {
                            var field_name = getFieldName($(this), disp_mode);
                            var this_field = $(this);

                            $.each(data.relations, function(key, value) {
                                if(key == field_name) {
                                    var new_value = $(this_field).find('select').val();
                                    new_html = $(value).append(new_value);
                                    return false;
                                }
                            })
                        }
                    }
                    $(this).html(new_html);
                })
            }
            else {
                PMA_ajaxShowMessage(data.error);
            };
        })
    }) // End After editing, clicking again should post data
})
