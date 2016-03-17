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

/**
 * This function returns the horizontal space available for the menu in pixels.
 * To calculate this value we start we the width of the main panel, then we
 * substract the margin of the page content, then we substract any cellspacing
 * that the table may have (original theme only) and finally we substract the
 * width of all columns of the table except for the last one (which is where
 * the menu will go). What we should end up with is the distance between the
 * start of the last column on the table and the edge of the page, again this
 * is the space available for the menu.
 *
 * In the case where the table cell where the menu will be displayed is already
 * off-screen (the table is wider than the page), a negative value will be returned,
 * but this will be treated as a zero by the menuResizer plugin.
 *
 * @return int
 */
function PMA_tbl_structure_menu_resizer_callback() {
    var pagewidth = $('body').width();
    var $page = $('#page_content');
    pagewidth -= $page.outerWidth(true) - $page.outerWidth();
    var columnsWidth = 0;
    var $columns = $('#tablestructure').find('tr:eq(1)').find('td,th');
    $columns.not(':last').each(function () {
        columnsWidth += $(this).outerWidth(true);
    });
    var totalCellSpacing = $('#tablestructure').width();
    $columns.each(function () {
        totalCellSpacing -= $(this).outerWidth(true);
    });
    return pagewidth - columnsWidth - totalCellSpacing - 15; // 15px extra margin
}

/**
 * Reload fields table
 */
function reloadFieldForm() {
    $.post($("#fieldsForm").attr('action'), $("#fieldsForm").serialize() + "&ajax_request=true", function (form_data) {
        var $temp_div = $("<div id='temp_div'><div>").append(form_data.message);
        $("#fieldsForm").replaceWith($temp_div.find("#fieldsForm"));
        $("#addColumns").replaceWith($temp_div.find("#addColumns"));
        $('#move_columns_dialog').find('ul').replaceWith($temp_div.find("#move_columns_dialog ul"));
        $("#moveColumns").removeClass("move-active");
        /* reinitialise the more options in table */
        if ($('#fieldsForm').hasClass('HideStructureActions')) {
            $('#fieldsForm').find('ul.table-structure-actions').menuResizer(PMA_tbl_structure_menu_resizer_callback);
        }
    });
    $('#page_content').show();
}

function checkFirst() {
    if ($("select[name=after_field] option:selected").data('pos') === 'first') {
        $("input[name=field_where]").val('first');
    } else {
        $("input[name=field_where]").val('after');
    }
}
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_structure.js', function () {
    $(document).off('click', "a.drop_column_anchor.ajax");
    $(document).off('click', "a.add_key.ajax");
    $(document).off('click', "#move_columns_anchor");
    $(document).off('click', "#printView");
    $(document).off('submit', ".append_fields_form.ajax");
    $('body').off('click', '#fieldsForm.ajax button[name="submit_mult"], #fieldsForm.ajax input[name="submit_mult"]');
    $(document).off('click', 'a[name^=partition_action].ajax');
    $(document).off('click', '#remove_partitioning.ajax');
});

AJAX.registerOnload('tbl_structure.js', function () {

    // Re-initialize variables.
    primary_indexes = [];
    indexes = [];
    fulltext_indexes = [];
    spatial_indexes = [];

    /**
     *Ajax action for submitting the "Column Change" and "Add Column" form
     */
    $(".append_fields_form.ajax").off();
    $(document).on('submit', ".append_fields_form.ajax", function (event) {
        event.preventDefault();
        /**
         * @var    the_form    object referring to the export form
         */
        var $form = $(this);
        var field_cnt = $form.find('input[name=orig_num_fields]').val();


        function submitForm(){
            $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $.post($form.attr('action'), $form.serialize() + '&do_save_data=1', function (data) {
                if ($(".sqlqueryresults").length !== 0) {
                    $(".sqlqueryresults").remove();
                } else if ($(".error:not(.tab)").length !== 0) {
                    $(".error:not(.tab)").remove();
                }
                if (typeof data.success != 'undefined' && data.success === true) {
                    $("#page_content")
                        .empty()
                        .append(data.message)
                        .show();
                    PMA_highlightSQL($('#page_content'));
                    $(".result_query .notice").remove();
                    reloadFieldForm();
                    $form.remove();
                    PMA_ajaxRemoveMessage($msg);
                    PMA_init_slider();
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }); // end $.post()
        }

        function checkIfConfirmRequired($form, $field_cnt) {
            var i = 0, id, elm, val, name_orig, elm_orig, val_orig;
            var checkRequired = false;
            for (i = 0; i < field_cnt; i++) {
                id = "#field_" + i + "_5";
                elm = $(id);
                val = elm.val();

                name_orig = "input[name=field_collation_orig\\[" + i + "\\]]";
                elm_orig = $form.find(name_orig);
                val_orig = elm_orig.val();

                if (val && val_orig && val !== val_orig){
                    checkRequired = true;
                    break;
                }
            }
            return checkRequired;
        }

        /*
         * First validate the form; if there is a problem, avoid submitting it
         *
         * checkTableEditForm() needs a pure element and not a jQuery object,
         * this is why we pass $form[0] as a parameter (the jQuery object
         * is actually an array of DOM elements)
         */
        if (checkTableEditForm($form[0], field_cnt)) {
            // OK, form passed validation step

            PMA_prepareForAjaxRequest($form);
            if (PMA_checkReservedWordColumns($form)) {
                //User wants to submit the form

                // If Collation is changed, Warn and Confirm
                if (checkIfConfirmRequired($form, field_cnt)){
                    var question = sprintf(
                        PMA_messages.strChangeColumnCollation, 'http://wiki.phpmyadmin.net/pma/Garbled_data'
                    );
                    $form.PMA_confirm(question, $form.attr('action'), function (url) {
                        submitForm();
                    });
                } else {
                    submitForm();
                }
            }
        }
    }); // end change table button "do_save_data"

    /**
     * Attach Event Handler for 'Drop Column'
     */
    $(document).on('click', "a.drop_column_anchor.ajax", function (event) {
        event.preventDefault();
        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = $(this).closest('form').find('input[name=table]').val();
        /**
         * @var curr_row    Object reference to the currently selected row (i.e. field in the table)
         */
        var $curr_row = $(this).parents('tr');
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $curr_row.children('th').children('label').text();
        curr_column_name = escapeHtml(curr_column_name);
        /**
         * @var $after_field_item    Corresponding entry in the 'After' field.
         */
        var $after_field_item = $("select[name='after_field'] option[value='" + curr_column_name + "']");
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_sprintf(PMA_messages.strDoYouReally, 'ALTER TABLE `' + escapeHtml(curr_table_name) + '` DROP `' + escapeHtml(curr_column_name) + '`;');
        $(this).PMA_confirm(question, $(this).attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(PMA_messages.strDroppingColumn, false);
            $.post(url, {'is_js_confirmed' : 1, 'ajax_request' : true, 'ajax_page_request' : true}, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    if ($('.result_query').length) {
                        $('.result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div class="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#structure_content');
                        PMA_highlightSQL($('#page_content'));
                    }
                    toggleRowColors($curr_row.next());
                    // Adjust the row numbers
                    for (var $row = $curr_row.next(); $row.length > 0; $row = $row.next()) {
                        var new_val = parseInt($row.find('td:nth-child(2)').text(), 10) - 1;
                        $row.find('td:nth-child(2)').text(new_val);
                    }
                    $after_field_item.remove();
                    $curr_row.hide("medium").remove();
                    //by default select the last option to add new column (in case last column is dropped)
                    $("select[name=after_field] option:last").attr("selected","selected");
                    //refresh table stats
                    if (data.tableStat) {
                        $('#tablestatistics').html(data.tableStat);
                    }
                    // refresh the list of indexes (comes from sql.php)
                    $('.index_info').replaceWith(data.indexes_list);
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + " : " + data.error, false);
                }
            }); // end $.post()
        }); // end $.PMA_confirm()
    }); //end of Drop Column Anchor action

    /**
     * Attach Event Handler for 'Print' link
     */
    $(document).on('click', "#printView", function (event) {
        event.preventDefault();

        // Take to preview mode
        printPreview();
    }); //end of Print View action

    /**
     * Ajax Event handler for adding keys
     */
    $(document).on('click', "a.add_key.ajax", function (event) {
        event.preventDefault();

        var $this = $(this);
        var curr_table_name = $this.closest('form').find('input[name=table]').val();
        var curr_column_name = $this.parents('tr').children('th').children('label').text();

        var add_clause = '';
        if ($this.is('.add_primary_key_anchor')) {
            add_clause = 'ADD PRIMARY KEY';
        } else if ($this.is('.add_index_anchor')) {
            add_clause = 'ADD INDEX';
        } else if ($this.is('.add_unique_anchor')) {
            add_clause = 'ADD UNIQUE';
        } else if ($this.is('.add_spatial_anchor')) {
            add_clause = 'ADD SPATIAL';
        } else if ($this.is('.add_fulltext_anchor')) {
            add_clause = 'ADD FULLTEXT';
        }
        var question = PMA_sprintf(PMA_messages.strDoYouReally, 'ALTER TABLE `' +
                escapeHtml(curr_table_name) + '` ' + add_clause + '(`' + escapeHtml(curr_column_name) + '`);');

        $(this).PMA_confirm(question, $(this).attr('href'), function (url) {
            PMA_ajaxShowMessage();
            AJAX.source = $this;
            $.post(url, {'ajax_request' : true, 'ajax_page_request' : true}, AJAX.responseHandler);
        }); // end $.PMA_confirm()
    }); //end Add key

    /**
     * Inline move columns
    **/
    $(document).on('click', "#move_columns_anchor", function (e) {
        e.preventDefault();

        if ($(this).hasClass("move-active")) {
            return;
        }

        /**
         * @var    button_options  Object that stores the options passed to jQueryUI
         *                          dialog
         */
        var button_options = {};

        button_options[PMA_messages.strGo] = function (event) {
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
                if (data.success === false) {
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
                    if ($('#fieldsForm').hasClass('HideStructureActions')) {
                        $('#fieldsForm').find('ul.table-structure-actions').menuResizer('destroy');
                    }
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
                        .addClass($row.index() % 2 === 0 ? "odd" : "even");
                    }
                    PMA_ajaxShowMessage(data.message);
                    $this.dialog('close');
                    if ($('#fieldsForm').hasClass('HideStructureActions')) {
                        $('#fieldsForm').find('ul.table-structure-actions').menuResizer(PMA_tbl_structure_menu_resizer_callback);
                    }
                }
            });
        };
        button_options[PMA_messages.strCancel] = function () {
            $(this).dialog('close');
        };

        var button_options_error = {};
        button_options_error[PMA_messages.strOK] = function () {
            $(this).dialog('close').remove();
        };

        var columns = [];

        $("#tablestructure").find("tbody tr").each(function () {
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

        var col_list = $("#move_columns_dialog").find("ul")
            .find("li").remove().end();
        for (var i in columns) {
            col_list.append(columns[i]);
        }
        col_list.sortable({
            axis: 'y',
            containment: $("#move_columns_dialog").find("div"),
            tolerance: 'pointer'
        }).disableSelection();
        var $form = $("#move_columns_dialog").find("form");
        $form.data("serialized-unmoved", $form.serialize());

        $("#move_columns_dialog").dialog({
            modal: true,
            buttons: button_options,
            open: function () {
                if ($('#move_columns_dialog').parents('.ui-dialog').height() > $(window).height()) {
                    $('#move_columns_dialog').dialog("option", "height", $(window).height());
                }
            },
            beforeClose: function () {
                $("#move_columns_anchor").removeClass("move-active");
            }
        });
    });

    /**
     * Handles multi submits in table structure page such as change, browse, drop, primary etc.
     */
    $('body').on('click', '#fieldsForm.ajax button[name="submit_mult"], #fieldsForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.parents('form');
        var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true&submit_mult=' + $button.val();
        PMA_ajaxShowMessage();
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });

    /**
     * Handles clicks on Action links in partition table
     */
    $(document).on('click', 'a[name^=partition_action].ajax', function (e) {
        e.preventDefault();
        var $link = $(this);

        function submitPartitionAction(url) {
            var submitData = '&ajax_request=true&ajax_page_request=true';
            PMA_ajaxShowMessage();
            AJAX.source = $link;
            $.post(url, submitData, AJAX.responseHandler);
        }

        if ($link.is('#partition_action_DROP')) {
            var question = PMA_messages.strDropPartitionWarning;
            $link.PMA_confirm(question, $link.attr('href'), function (url) {
                submitPartitionAction(url);
            });
        } else if ($link.is('#partition_action_TRUNCATE')) {
            var question = PMA_messages.strTruncatePartitionWarning;
            $link.PMA_confirm(question, $link.attr('href'), function (url) {
                submitPartitionAction(url);
            });
        } else {
            submitPartitionAction($link.attr('href'));
        }
    });

    /**
     * Handles remove partitioning
     */
    $(document).on('click', '#remove_partitioning.ajax', function (e) {
        e.preventDefault();
        var $link = $(this);
        var question = PMA_messages.strRemovePartitioningWarning;
        $link.PMA_confirm(question, $link.attr('href'), function (url) {
            var submitData = '&ajax_request=true&ajax_page_request=true';
            PMA_ajaxShowMessage();
            AJAX.source = $link;
            $.post(url, submitData, AJAX.responseHandler);
        });
    });
});

/** Handler for "More" dropdown in structure table rows */
AJAX.registerOnload('tbl_structure.js', function () {
    if ($('#fieldsForm').hasClass('HideStructureActions')) {
        $('#fieldsForm').find('ul.table-structure-actions').menuResizer(PMA_tbl_structure_menu_resizer_callback);
    } else {
        $('.table-structure-actions').width(function () {
            var width = 5;
            $(this).find('li').each(function () {
                width += $(this).outerWidth(true);
            });
            return width;
        });
    }
});
AJAX.registerTeardown('tbl_structure.js', function () {
    if ($('#fieldsForm').hasClass('HideStructureActions')) {
        $('#fieldsForm').find('ul.table-structure-actions').menuResizer('destroy');
    }
});
$(function () {
    $(window).resize($.throttle(function () {
        if ($('#fieldsForm').length && $('#fieldsForm').hasClass('HideStructureActions')) {
            $('#fieldsForm').find('ul.table-structure-actions').menuResizer('resize');
        }
    }));
});
