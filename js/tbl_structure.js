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
$(function() {
    /**
     * Attach Event Handler for 'Drop Column'
     *
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $("a.drop_column_anchor").live('click', function(event) {
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
        var question = $.sprintf(PMA_messages['strDoYouReally'], 'ALTER TABLE `' + escapeHtml(curr_table_name) + '` DROP `' + escapeHtml(curr_column_name) + '`;');

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
                } else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }) ; //end of Drop Column Anchor action

    /**
     * Ajax Event handler for 'Add Primary Key'
     *
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $("div.action_primary a").live('click', function(event) {
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
        var question = $.sprintf(PMA_messages['strDoYouReally'], 'ALTER TABLE `' + escapeHtml(curr_table_name) + '` ADD PRIMARY KEY(`' + escapeHtml(curr_column_name) + '`);');

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strAddingPrimaryKey'], false);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(this).remove();
                    if (typeof data.reload != 'undefined') {
                        window.parent.frame_content.location.reload();
                    }
                } else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end Add Primary Key

    /**
     * Ajax Event handler for 'Drop Primary Key/Index'
     *
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $('a.drop_primary_key_index_anchor').live('click', function(event) {
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
                            $('div.no_indexes_defined').show('medium');
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
                } else {
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
                url = url.substring(16, url.length);
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
             * @var    the_form    object referring to the export form
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
                    $('div.no_indexes_defined').hide();
                } else {
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
            if (data.success == false) {
                //in the case of an error, show the error message returned.
                PMA_ajaxShowMessage(data.error, false);
            } else {
                PMA_ajaxRemoveMessage($msgbox);
                // Show dialog if the request was successful
                $div
                .append(data.message)
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
                PMA_showHints($div);
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
                $('a.ui-slider-handle').addClass('ui-state-focus');
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
     * Inline move columns
    **/
    $("#move_columns_anchor").live('click', function(e) {
        e.preventDefault();

        if ($(this).hasClass("move-active")) {
            return;
        }

        /**
         * @var    button_options  Object that stores the options passed to jQueryUI
         *                          dialog
         */
        var button_options = {};

        button_options[PMA_messages['strGo']] = function(event) {
            event.preventDefault();
            var $msgbox = PMA_ajaxShowMessage();
            var $this = $(this);
            var $form = $this.find("form");
            var serialized = $form.serialize();

            // check if any columns were moved at all
            if (serialized == $form.data("serialized-unmoved")) {
                PMA_ajaxRemoveMessage($msgbox);
                $this.dialog('close');
                return;
            }

            $.post($form.prop("action"), serialized + "&ajax_request=true", function (data) {
                if (data.success == false) {
                    PMA_ajaxRemoveMessage($msgbox);
                    $this
                    .clone()
                    .html(data.error)
                    .dialog({
                        title: $(this).prop("title"),
                        height: 230,
                        width: 900,
                        modal: true,
                        buttons: button_options_error
                    }); // end dialog options
                } else {
                    // sort the fields table
                    var $fields_table = $("table#tablestructure tbody");
                    // remove all existing rows and remember them
                    var $rows = $fields_table.find("tr").remove();
                    // loop through the correct order
                    for (var i in data.columns) {
                        var the_column = data.columns[i];
                        var $the_row = $rows
                            .find("input:checkbox[value=" + the_column + "]")
                            .closest("tr");
                        // append the row for this column to the table
                        $fields_table.append($the_row);
                    }
                    var $firstrow = $fields_table.find("tr").eq(0);
                    // Adjust the row numbers and colors
                    for (var $row = $firstrow; $row.length > 0; $row = $row.next()) {
                        $row
                        .find('td:nth-child(2)')
                        .text($row.index() + 1)
                        .end()
                        .removeClass("odd even")
                        .addClass($row.index() % 2 == 0 ? "odd" : "even");
                    }
                    PMA_ajaxShowMessage(data.message);
                    $this.dialog('close');
                }
            });
        };
        button_options[PMA_messages['strCancel']] = function() {
            $(this).dialog('close');
        };

        var button_options_error = {};
        button_options_error[PMA_messages['strOK']] = function() {
            $(this).dialog('close').remove();
        };

        var columns = [];

        $("#tablestructure tbody tr").each(function () {
            var col_name = $(this).find("input:checkbox").eq(0).val();
            var hidden_input = $("<input/>")
                .prop({
                    name: "move_columns[]",
                    type: "hidden"
                })
                .val(col_name);
            columns[columns.length] = $("<li/>")
                .addClass("placeholderDrag")
                .text(col_name)
                .append(hidden_input);
        });

        var col_list = $("#move_columns_dialog ul")
            .find("li").remove().end();
        for(var i in columns) {
            col_list.append(columns[i]);
        }
        col_list.sortable({
            axis: 'y',
            containment: $("#move_columns_dialog div")
        }).disableSelection();
        var $form = $("#move_columns_dialog form");
        $form.data("serialized-unmoved", $form.serialize());

        $("#move_columns_dialog").dialog({
            modal: true,
            buttons: button_options,
            beforeClose: function () {
                $("#move_columns_anchor").removeClass("move-active");
            }
        });
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
         * @var    button_options  Object that stores the options passed to jQueryUI
         *                          dialog
         */
        var button_options = {};
        // in the following function we need to use $(this)
        button_options[PMA_messages['strCancel']] = function() {
            $(this).dialog('close');
        };

        var button_options_error = {};
        button_options_error[PMA_messages['strOK']] = function() {
            $(this).dialog('close');
        };
        var $msgbox = PMA_ajaxShowMessage();

        $.get($form.attr('action') , $form.serialize() + "&ajax_request=true" ,  function(data) {
            //in the case of an error, show the error message returned.
            if (data.success != undefined && data.success == false) {
                $div
                .append(data.error)
                .dialog({
                    title: PMA_messages['strAddColumns'],
                    height: 230,
                    width: 900,
                    open: PMA_verifyColumnsProperties,
                    close: function() {
                        $(this).remove();
                    },
                    modal: true,
                    buttons : button_options_error
                }); // end dialog options
            } else {
                $div
                .append(data.message)
                .dialog({
                    title: PMA_messages['strAddColumns'],
                    height: 600,
                    width: 900,
                    open: PMA_verifyColumnsProperties,
                    close: function() {
                        $(this).remove();
                    },
                    modal: true,
                    buttons : button_options
                }); // end dialog options

                $div = $("#add_columns");
                /*changed the z-index of the enum editor to allow the edit*/
                $("#enum_editor").css("z-index", "1100");
                PMA_showHints($div);
                // set focus on first column name input
                $div.find("input.textfield").eq(0).focus();
            }
            PMA_ajaxRemoveMessage($msgbox);
        }); // end $.get()
    });
}); // end $()

/**
 * Loads the append_fields_form to the Change dialog allowing users
 * to change the columns
 * @param string    action  Variable which parses the name of the
 *                             destination file
 * @param string    $url    Variable which parses the data for the
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
     * @var    button_options  Object that stores the options passed to jQueryUI
     *                          dialog
     */
    var button_options = {};
    // in the following function we need to use $(this)
    button_options[PMA_messages['strCancel']] = function() {
        $(this).dialog('close');
    };

    var button_options_error = {};
    button_options_error[PMA_messages['strOK']] = function() {
        $(this).dialog('close');
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
                close: function() {
                    $(this).remove();
                },
                buttons : button_options_error
            }); // end dialog options
        } else {
            $div
            .append(data.message)
            .dialog({
                title: PMA_messages['strChangeTbl'],
                height: 600,
                width: 900,
                modal: true,
                open: PMA_verifyColumnsProperties,
                close: function() {
                    $(this).remove();
                }, 
                buttons : button_options
            }); // end dialog options
            $("#append_fields_form input[name=do_save_data]").addClass("ajax");
            /*changed the z-index of the enum editor to allow the edit*/
            $("#enum_editor").css("z-index", "1100");
            $div = $("#change_column_dialog");
            PMA_showHints($div);
        }
        PMA_ajaxRemoveMessage($msgbox);
    }); // end $.get()
}

/**
 * jQuery coding for 'Change Table' and 'Add Column'.  Used on tbl_structure.php *
 * Attach Ajax Event handlers for Change Table
 */
$(function() {
    /**
     *Ajax action for submitting the "Column Change" and "Add Column" form
    **/
    $("#append_fields_form input[name=do_save_data]").live('click', function(event) {
        event.preventDefault();
        /**
         * @var    the_form    object referring to the export form
         */
        var $form = $("#append_fields_form");

        /*
         * First validate the form; if there is a problem, avoid submitting it
         *
         * checkTableEditForm() needs a pure element and not a jQuery object,
         * this is why we pass $form[0] as a parameter (the jQuery object
         * is actually an array of DOM elements)
         */
        if (checkTableEditForm($form[0], $form.find('input[name=orig_num_fields]').val())) {
            // OK, form passed validation step
            if ($form.hasClass('ajax')) {
                PMA_prepareForAjaxRequest($form);
                //User wants to submit the form
                PMA_ajaxShowMessage();
                $.post($form.attr('action'), $form.serialize()+"&do_save_data=Save", function(data) {
                    if ($("#sqlqueryresults").length != 0) {
                        $("#sqlqueryresults").remove();
                    } else if ($(".error").length != 0) {
                        $(".error").remove();
                    }
                    if (data.success == true) {
                        $("<div id='sqlqueryresults'></div>").insertAfter("#floating_menubar");
                        $("#sqlqueryresults").html(data.sql_query);
                        $("#result_query .notice").remove();
                        $("#result_query").prepend(data.message);
                        if ($("#change_column_dialog").length > 0) {
                            $("#change_column_dialog").dialog("close").remove();
                        } else if ($("#add_columns").length > 0) {
                            $("#add_columns").dialog("close").remove();
                        }
                        /*Reload the field form*/
                        reloadFieldForm(data.message);
                    } else {
                        var $temp_div = $("<div id='temp_div'><div>").append(data.error);
                        var $error = $temp_div.find(".error code").addClass("error");
                        PMA_ajaxShowMessage($error, false);
                    }
                }); // end $.post()
            } else {
                // non-Ajax submit
                $form.append('<input type="hidden" name="do_save_data" value="Save" />');
                $form.submit();
            }
        }
    }); // end change table button "do_save_data"

}, 'top.frame_content'); //end $(document).ready for 'Change Table'

/**
 * Reload fields table
 */
function reloadFieldForm(message) {
    $.post($("#fieldsForm").attr('action'), $("#fieldsForm").serialize()+"&ajax_request=true", function(form_data) {
        var $temp_div = $("<div id='temp_div'><div>").append(form_data.message);
        $("#fieldsForm").replaceWith($temp_div.find("#fieldsForm"));
        $("#addColumns").replaceWith($temp_div.find("#addColumns"));
        $('#move_columns_dialog ul').replaceWith($temp_div.find("#move_columns_dialog ul"));
        $("#moveColumns").removeClass("move-active");
        /* Call the function to display the more options in table */
        $table_clone = false;
        $("div.replace_in_more").hide(); // fix "more" dropdown
        moreOptsMenuResize();
        setTimeout(function() {
            PMA_ajaxShowMessage(message);
        }, 500);
    });
}

/**
 * Hides certain table structure actions, replacing them
 * with the word "More". They are displayed in a dropdown
 * menu when the user hovers over the word "More."
 */

var $table_clone = false;

function moreOptsMenuResize() {
    var $table = $("table#tablestructure");

    // don't use More menu if we're only showing icons and no text
    if ($table.length == 0 || $table.hasClass("PropertiesIconic")) {
        return;
    }

    // reset table to defaults
    if ($table_clone === false) {
        $table_clone = $table.clone();
    }
    else {
        $table.replaceWith($table_clone);
        $table = $table_clone;
        $table_clone = $table.clone();
    }

    $table.find("td.more_opts").hide();

    var getCurWidth = function() {
        var cur_width = 0;
        $table.find("tr").eq(1)
            .find("td.edit, td.drop, .replaced_by_more:visible, .more_opts:visible")
            .each(function () {
                cur_width += $(this).outerWidth();
            });
        return cur_width;
    };

    // get window width
    var window_width = $(window).width();

    // find out maximum action links width
    var max_width = window_width;
    $table.find("tr").eq(0).children().each(function () {
        if ($(this).index() < 9) {
            max_width -= $(this).outerWidth() + 1;
        }
    });

    // current action links width
    var cur_width = getCurWidth();

    // remove some links if current width is wider than maximum allowed
    if (cur_width > max_width && $table.find("td.more_opts").length != 0) {
        while (cur_width > max_width
            && $(".replaced_by_more:visible").length > 0) {

            // hide last visible element
            var css_class = $table.find("tr").eq(1)
                .find(".replaced_by_more:visible").last().prop("className").split(" ");
            $table.find("." + css_class.join(".")).hide();
            // show corresponding more-menu entry
            $table.find(".replace_in_more.action_" + css_class[0]).show();
            $table.find("td.more_opts").show();
            // recalculate width
            cur_width = getCurWidth();
        }
    }

    if ($(".replaced_by_more:hidden").length == 0) {
        $table.find("td.more_opts").hide();
    }

    // wait for topmenu resize handler
    setTimeout(function () {
        // Position the dropdown
        $(".structure_actions_dropdown").each(function() {
            // Optimize DOM querying
            var $this_dropdown = $(this);
             // The top offset must be set for IE even if it didn't change
            var cell_right_edge_offset = $this_dropdown.parent().position().left + $this_dropdown.parent().innerWidth();
            var left_offset = cell_right_edge_offset - $this_dropdown.innerWidth();
            var top_offset = $this_dropdown.parent().position().top + $this_dropdown.parent().innerHeight();
            $this_dropdown.css({ top: top_offset, left: left_offset });
        });
    }, 100);

    // A hack for IE6 to prevent the after_field select element from being displayed on top of the dropdown by
    // positioning an iframe directly on top of it
    var $after_field = $("select[name='after_field']");
    // This dropdown is only present for a table, not for a view
    if ($after_field.length) {
        $("iframe[class='IE_hack']")
            .width($after_field.width())
            .height($after_field.height())
            .offset({
                top: $after_field.offset().top,
                left: $after_field.offset().left
            });
    }

    // When "more" is hovered over, show the hidden actions
    $table.find("td.more_opts")
        .unbind("mouseenter")
        .bind("mouseenter", function() {
            if($.browser.msie && $.browser.version == "6.0") {
                $("iframe[class='IE_hack']")
                    .show()
                    .width($after_field.width()+4)
                    .height($after_field.height()+4)
                    .offset({
                        top: $after_field.offset().top,
                        left: $after_field.offset().left
                    });
            }
            $(".structure_actions_dropdown").hide(); // Hide all the other ones that may be open
            $(this).children(".structure_actions_dropdown").show();
            // Need to do this again for IE otherwise the offset is wrong
            if($.browser.msie) {
                var left_offset_IE = $(this).offset().left + $(this).innerWidth() - $(this).children(".structure_actions_dropdown").innerWidth();
                var top_offset_IE = $(this).offset().top + $(this).innerHeight();
                $(this).children(".structure_actions_dropdown").offset({
                    top: top_offset_IE,
                    left: left_offset_IE });
            }
        })
        .unbind("mouseleave")
        .bind("mouseleave", function() {
            $(this).children(".structure_actions_dropdown").hide();
            if($.browser.msie && $.browser.version == "6.0") {
                $("iframe[class='IE_hack']").hide();
            }
        });
}
$(window).resize(moreOptsMenuResize);
$(function () {
    $("div.replace_in_more").hide();
    moreOptsMenuResize();
});
