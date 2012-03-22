/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used on the table structure page
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * AJAX scripts for tbl_structure.php
 *
 * Actions ajaxified here:
 * Drop Column
 * Add Primary Key
 * Drop Primary Key/Index
 *
 */
$(document).ready(function() {
    /**
     * Attach Event Handler for 'Drop Column'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $(".drop_column_anchor").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = window.parent.table;
        /**
         * @var curr_row    Object reference to the currently selected row (i.e. field in the table)
         */
        var $curr_row = $(this).parents('tr');
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $curr_row.children('th').children('label').text();
        /**
         * @var $after_field_item    Corresponding entry in the 'After' field.
         */
        var $after_field_item = $("select[name='after_field'] option[value='" + curr_column_name + "']");
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDoYouReally'] + '\n ALTER TABLE `' + escapeHtml(curr_table_name) + '` DROP `' + escapeHtml(curr_column_name) + '`';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingColumn'], false);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    toggleRowColors($curr_row.next());
                    // Adjust the row numbers
                    for (var $row = $curr_row.next(); $row.length > 0; $row = $row.next()) {
                        var new_val = parseInt($row.find('td:nth-child(2)').text()) - 1;
                        $row.find('td:nth-child(2)').text(new_val);
                    }
                    $after_field_item.remove();
                    $curr_row.hide("medium").remove();
                    // refresh the list of indexes (comes from sql.php)
                    $('#indexes').html(data.indexes_list);
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }) ; //end of Drop Column Anchor action

    /**
     * Ajax Event handler for 'Add Primary Key'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $(".action_primary a").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = window.parent.table;
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $(this).parents('tr').children('th').children('label').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDoYouReally'] + '\n ALTER TABLE `' + escapeHtml(curr_table_name) + '` ADD PRIMARY KEY(`' + escapeHtml(curr_column_name) + '`)';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strAddingPrimaryKey'], false);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(this).remove();
                    if (typeof data.reload != 'undefined') {
                        window.parent.frame_content.location.reload();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end Add Primary Key

    /**
     * Ajax Event handler for 'Drop Primary Key/Index'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $('.drop_primary_key_index_anchor').live('click', function(event) {
        event.preventDefault();

        $anchor = $(this);

        /**
         * @var $curr_row    Object containing reference to the current field's row
         */
        var $curr_row = $anchor.parents('tr');
        /** @var    Number of columns in the key */
        var rows = $anchor.parents('td').attr('rowspan') || 1;
        /** @var    Rows that should be hidden */
        var $rows_to_hide = $curr_row;
        for (var i = 1, $last_row = $curr_row.next(); i < rows; i++, $last_row = $last_row.next()) {
            $rows_to_hide = $rows_to_hide.add($last_row);
        }

        var question = $curr_row.children('td').children('.drop_primary_key_index_msg').val();

        $anchor.PMA_confirm(question, $anchor.attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingPrimaryKeyIndex'], false);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    var $table_ref = $rows_to_hide.closest('table');
                    if ($rows_to_hide.length == $table_ref.find('tbody > tr').length) {
                        // We are about to remove all rows from the table
                        $table_ref.hide('medium', function() {
                            $('.no_indexes_defined').show('medium');
                            $rows_to_hide.remove();
                        });
                        $table_ref.siblings('div.notice').hide('medium');
                    } else {
                        // We are removing some of the rows only
                        toggleRowColors($rows_to_hide.last().next());
                        $rows_to_hide.hide("medium", function () {
                            $(this).remove();
                        });
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end Drop Primary Key/Index

    /**
     *Ajax event handler for multi column change
    **/
    $("#fieldsForm.ajax .mult_submit[value=change]").live('click', function(event){
        event.preventDefault();

        /*Check whether atleast one row is selected for change*/
        if ($("#tablestructure tbody tr").hasClass("marked")) {
            /*Define the action and $url variabls for the post method*/
            var $form = $("#fieldsForm");
            var action = $form.attr('action');
            var url = $form.serialize()+"&ajax_request=true&submit_mult=change";
            /*Calling for the changeColumns fucntion*/
            changeColumns(action,url);
        } else {
            PMA_ajaxShowMessage(PMA_messages['strNoRowSelected']);
        }
    });

    /**
     *Ajax event handler for single column change
    **/
    $("#fieldsForm.ajax #tablestructure tbody tr td.edit a").live('click', function(event){
        event.preventDefault();
        /*Define the action and $url variabls for the post method*/
        var action = "tbl_alter.php";
        var url = $(this).attr('href');
        if (url.substring(0, 13) == "tbl_alter.php") {
            url = url.substring(14, url.length);
        }
        url = url + "&ajax_request=true";
        /*Calling for the changeColumns fucntion*/
        changeColumns(action,url);
     });

    /**
     *Ajax event handler for index edit
    **/
    $("#table_index tbody tr td.edit_index.ajax, #indexes .add_index.ajax").live('click', function(event) {
        event.preventDefault();
        if ($(this).find("a").length == 0) {
            // Add index
            var valid = checkFormElementInRange(
                $(this).closest('form')[0],
                'added_fields',
                'Column count has to be larger than zero.'
            );
            if (! valid) {
                return;
            }
            var url = $(this).closest('form').serialize();
            var title = PMA_messages['strAddIndex'];
        } else {
            // Edit index
            var url = $(this).find("a").attr("href");
            if (url.substring(0, 16) == "tbl_indexes.php?") {
                url = url.substring(16, url.length );
            }
            var title = PMA_messages['strEditIndex'];
        }
        url += "&ajax_request=true";

        /*Remove the hidden dialogs if there are*/
        if ($('#edit_index_dialog').length != 0) {
            $('#edit_index_dialog').remove();
        }
        var $div = $('<div id="edit_index_dialog"></div>');

        /**
         * @var button_options Object that stores the options
         *                     passed to jQueryUI dialog
         */
        var button_options = {};
        button_options[PMA_messages['strGo']] = function() {
            /**
             *  @var    the_form    object referring to the export form
             */
            var $form = $("#index_frm");
            PMA_prepareForAjaxRequest($form);
            //User wants to submit the form
            $.post($form.attr('action'), $form.serialize()+"&do_save_data=1", function(data) {
                if ($("#sqlqueryresults").length != 0) {
                    $("#sqlqueryresults").remove();
                }
                if (data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $("<div id='sqlqueryresults'></div>").insertAfter("#floating_menubar");
                    $("#sqlqueryresults").html(data.sql_query);
                    $("#result_query .notice").remove();
                    $("#result_query").prepend(data.message);

                    /*Reload the field form*/
                    $("#table_index").remove();
                    var $temp_div = $("<div id='temp_div'><div>").append(data.index_table);
                    $temp_div.find("#table_index").insertAfter("#index_header");
                    if ($("#edit_index_dialog").length > 0) {
                        $("#edit_index_dialog").dialog("close");
                    }
                    $('.no_indexes_defined').hide();
                } else if (data.error != undefined) {
                    var $temp_div = $("<div id='temp_div'><div>").append(data.error);
                    if ($temp_div.find(".error code").length != 0) {
                        var $error = $temp_div.find(".error code").addClass("error");
                    } else {
                        var $error = $temp_div;
                    }
                    PMA_ajaxShowMessage($error, false);
                }
            }); // end $.post()
        };
        button_options[PMA_messages['strCancel']] = function() {
            $(this).dialog('close');
        };
        var $msgbox = PMA_ajaxShowMessage();
        $.get("tbl_indexes.php", url, function(data) {
            if (data.error) {
                //in the case of an error, show the error message returned.
                PMA_ajaxShowMessage(data.error, false);
            } else {
                PMA_ajaxRemoveMessage($msgbox);
                // Show dialog if the request was successful
                $div
                .append(data)
                .dialog({
                    title: title,
                    width: 450,
                    open: PMA_verifyColumnsProperties,
                    modal: true,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
                checkIndexType();
                checkIndexName("index_frm");
                PMA_convertFootnotesToTooltips($div);
                // Add a slider for selecting how many columns to add to the index
                $div.find('.slider').slider({
                    animate: true,
                    value: 1,
                    min: 1,
                    max: 16,
                    slide: function( event, ui ) {
                        $(this).closest('fieldset').find('input[type=submit]').val(
                            PMA_messages['strAddToIndex'].replace(/%d/, ui.value)
                        );
                    }
                });
                // focus index size input on column picked
                $div.find('table#index_columns select').change(function() {
                    if ($(this).find("option:selected").val() == '') {
                        return true;
                    }
                    $(this).closest("tr").find("input").focus();
                });
                // Focus the slider, otherwise it looks nearly transparent
                $('.ui-slider-handle').addClass('ui-state-focus');
                // set focus on index name input, if empty
                var input = $div.find('input#input_index_name');
                input.val() || input.focus();
            }
        }); // end $.get()
    });

    /**
     * Handler for adding more columns to an index in the editor
     */
    $('#index_frm input[type=submit]').live('click', function(event) {
        event.preventDefault();
        var rows_to_add = $(this)
            .closest('fieldset')
            .find('.slider')
            .slider('value');
        while (rows_to_add--) {
            var $newrow = $('#index_columns')
                .find('tbody > tr:first')
                .clone()
                .appendTo(
                    $('#index_columns').find('tbody')
                );
            $newrow.find(':input').each(function() {
                $(this).val('');
            });
            // focus index size input on column picked
            $newrow.find('select').change(function() {
                if ($(this).find("option:selected").val() == '') {
                    return true;
                }
                $(this).closest("tr").find("input").focus();
            });
        }
    });

    /**
     *Ajax event handler for Add column(s)
    **/
    $("#addColumns.ajax input[type=submit]").live('click', function(event){
        event.preventDefault();

        /*Remove the hidden dialogs if there are*/
        if ($('#add_columns').length != 0) {
            $('#add_columns').remove();
        }
        var $div = $('<div id="add_columns"></div>');

        var $form = $("#addColumns");

        /**
         *  @var    button_options  Object that stores the options passed to jQueryUI
         *                          dialog
         */
        var button_options = {};
        // in the following function we need to use $(this)
        button_options[PMA_messages['strCancel']] = function() {
            $(this).dialog('close').remove();
        };

        var button_options_error = {};
        button_options_error[PMA_messages['strOK']] = function() {
            $(this).dialog('close').remove();
        };
        var $msgbox = PMA_ajaxShowMessage();

        $.get( $form.attr('action') , $form.serialize()+"&ajax_request=true" ,  function(data) {
            //in the case of an error, show the error message returned.
            if (data.success != undefined && data.success == false) {
                $div
                .append(data.error)
                .dialog({
                    title: PMA_messages['strAddColumns'],
                    height: 230,
                    width: 900,
                    open: PMA_verifyColumnsProperties,
                    modal: true,
                    buttons : button_options_error
                }); // end dialog options
            } else {
                $div
                .append(data)
                .dialog({
                    title: PMA_messages['strAddColumns'],
                    height: 600,
                    width: 900,
                    open: PMA_verifyColumnsProperties,
                    modal: true,
                    buttons : button_options
                }); // end dialog options

                $div = $("#add_columns");
                /*changed the z-index of the enum editor to allow the edit*/
                $("#enum_editor").css("z-index", "1100");
                PMA_convertFootnotesToTooltips($div);
                // set focus on first column name input
                $div.find("input.textfield").eq(0).focus();
            }
            PMA_ajaxRemoveMessage($msgbox);
        }); // end $.get()
    });
}); // end $(document).ready()

/**
 * Loads the append_fields_form to the Change dialog allowing users
 * to change the columns
 * @param   string    action  Variable which parses the name of the
 *                             destination file
 * @param   string    $url    Variable which parses the data for the
 *                             post action
 */
function changeColumns(action,url)
{
    /*Remove the hidden dialogs if there are*/
    if ($('#change_column_dialog').length != 0) {
        $('#change_column_dialog').remove();
    }
    if ($('#result_query').length != 0) {
        $('#result_query').remove();
    }
    var $div = $('<div id="change_column_dialog"></div>');

    /**
     *  @var    button_options  Object that stores the options passed to jQueryUI
     *                          dialog
     */
    var button_options = {};
    // in the following function we need to use $(this)
    button_options[PMA_messages['strCancel']] = function() {
        $(this).dialog('close').remove();
    };

    var button_options_error = {};
    button_options_error[PMA_messages['strOK']] = function() {
        $(this).dialog('close').remove();
    };
    var $msgbox = PMA_ajaxShowMessage();

    $.get( action , url ,  function(data) {
        //in the case of an error, show the error message returned.
        if (data.success != undefined && data.success == false) {
            $div
            .append(data.error)
            .dialog({
                title: PMA_messages['strChangeTbl'],
                height: 230,
                width: 900,
                modal: true,
                open: PMA_verifyColumnsProperties,
                buttons : button_options_error
            }); // end dialog options
        } else {
            $div
            .append(data)
            .dialog({
                title: PMA_messages['strChangeTbl'],
                height: 600,
                width: 900,
                modal: true,
                open: PMA_verifyColumnsProperties,
                buttons : button_options
            }); // end dialog options
            $("#append_fields_form input[name=do_save_data]").addClass("ajax");
            /*changed the z-index of the enum editor to allow the edit*/
            $("#enum_editor").css("z-index", "1100");
            $div = $("#change_column_dialog");
            PMA_convertFootnotesToTooltips($div);
        }
        PMA_ajaxRemoveMessage($msgbox);
    }); // end $.get()
}

