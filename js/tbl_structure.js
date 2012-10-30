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
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_structure.js', function() {
    $("a.drop_column_anchor.ajax").die('click');
    $("a.action_primary.ajax").die('click');
    $('a.drop_primary_key_index_anchor.ajax').die('click');
    $("#table_index tbody tr td.edit_index.ajax, #indexes .add_index.ajax").die('click');
    $('#index_frm input[type=submit]').die('click');
    $("#move_columns_anchor").die('click');
    $("#addColumns.ajax input[type=submit]").die('click');
});

AJAX.registerOnload('tbl_structure.js', function() {
    /**
     * Attach Event Handler for 'Drop Column'
     *
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $("a.drop_column_anchor.ajax").live('click', function(event) {
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
                    PMA_reloadNavigation();
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
    $("a.action_primary.ajax").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = $(this).closest('form').find('input[name=table]').val();
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
                        PMA_commonActions.refreshMain();
                    }
                    PMA_reloadNavigation();
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
    $('a.drop_primary_key_index_anchor.ajax').live('click', function(event) {
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
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end Drop Primary Key/Index

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
        indexEditorDialog(url, title);
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
});

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
function moreOptsMenuResize() {
    var $table = $("#tablestructure");

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
    var window_width = $(window).width()
        - $('#pma_navigation').width()
        - $('#pma_navigation_resizer').width();

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
AJAX.registerOnload('tbl_structure.js', function () {
    $(window).resize(moreOptsMenuResize); // FIXME: shouldn't register that can't be unbound easily
    $("div.replace_in_more").hide();
    moreOptsMenuResize();
});
