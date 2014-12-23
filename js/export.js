/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in the export tab
 *
 */

/**
 * Disables the "Dump some row(s)" sub-options
 */
function disable_dump_some_rows_sub_options()
{
    $("label[for='limit_to']").fadeTo('fast', 0.4);
    $("label[for='limit_from']").fadeTo('fast', 0.4);
    $("input[type='text'][name='limit_to']").prop('disabled', 'disabled');
    $("input[type='text'][name='limit_from']").prop('disabled', 'disabled');
}

/**
 * Enables the "Dump some row(s)" sub-options
 */
function enable_dump_some_rows_sub_options()
{
    $("label[for='limit_to']").fadeTo('fast', 1);
    $("label[for='limit_from']").fadeTo('fast', 1);
    $("input[type='text'][name='limit_to']").prop('disabled', '');
    $("input[type='text'][name='limit_from']").prop('disabled', '');
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('export.js', function () {
    $("#plugins").unbind('change');
    $("input[type='radio'][name='sql_structure_or_data']").unbind('change');
    $("input[type='radio'][name='latex_structure_or_data']").unbind('change');
    $("input[type='radio'][name='odt_structure_or_data']").unbind('change');
    $("input[type='radio'][name='texytext_structure_or_data']").unbind('change');
    $("input[type='radio'][name='htmlword_structure_or_data']").unbind('change');
    $("input[type='radio'][name='sql_structure_or_data']").unbind('change');
    $("input[type='radio'][name='output_format']").unbind('change');
    $("#checkbox_sql_include_comments").unbind('change');
    $("#plugins").unbind('change');
    $("input[type='radio'][name='quick_or_custom']").unbind('change');
    $("input[type='radio'][name='allrows']").unbind('change');
    $('#btn_alias_config').off('click');
    $('#db_alias_select').off('change');
    $('.table_alias_select').off('change');
});

AJAX.registerOnload('export.js', function () {
    /**
     * Toggles the hiding and showing of each plugin's options
     * according to the currently selected plugin from the dropdown list
     */
    $("#plugins").change(function () {
        $("#format_specific_opts div.format_specific_options").hide();
        var selected_plugin_name = $("#plugins option:selected").val();
        $("#" + selected_plugin_name + "_options").show();
    });

    /**
     * Toggles the enabling and disabling of the SQL plugin's comment options that apply only when exporting structure
     */
    $("input[type='radio'][name='sql_structure_or_data']").change(function () {
        var comments_are_present = $("#checkbox_sql_include_comments").prop("checked");
        var show = $("input[type='radio'][name='sql_structure_or_data']:checked").val();
        if (show == 'data') {
            // disable the SQL comment options
            if (comments_are_present) {
                $("#checkbox_sql_dates").prop('disabled', true).parent().fadeTo('fast', 0.4);
            }
            $("#checkbox_sql_relation").prop('disabled', true).parent().fadeTo('fast', 0.4);
            $("#checkbox_sql_mime").prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            // enable the SQL comment options
            if (comments_are_present) {
                $("#checkbox_sql_dates").removeProp('disabled').parent().fadeTo('fast', 1);
            }
            $("#checkbox_sql_relation").removeProp('disabled').parent().fadeTo('fast', 1);
            $("#checkbox_sql_mime").removeProp('disabled').parent().fadeTo('fast', 1);
        }
    });
});


/**
 * Toggles the hiding and showing of plugin structure-specific and data-specific
 * options
 */
function toggle_structure_data_opts(pluginName)
{
    var radioFormName = pluginName + "_structure_or_data";
    var dataDiv = "#" + pluginName + "_data";
    var structureDiv = "#" + pluginName + "_structure";
    var show = $("input[type='radio'][name='" + radioFormName + "']:checked").val();
    if (show == 'data') {
        $(dataDiv).slideDown('slow');
        $(structureDiv).slideUp('slow');
    } else {
        $(structureDiv).slideDown('slow');
        if (show == 'structure') {
            $(dataDiv).slideUp('slow');
        } else {
            $(dataDiv).slideDown('slow');
        }
    }
}

AJAX.registerOnload('export.js', function () {
    $("input[type='radio'][name='latex_structure_or_data']").change(function () {
        toggle_structure_data_opts("latex");
    });
    $("input[type='radio'][name='odt_structure_or_data']").change(function () {
        toggle_structure_data_opts("odt");
    });
    $("input[type='radio'][name='texytext_structure_or_data']").change(function () {
        toggle_structure_data_opts("texytext");
    });
    $("input[type='radio'][name='htmlword_structure_or_data']").change(function () {
        toggle_structure_data_opts("htmlword");
    });
    $("input[type='radio'][name='sql_structure_or_data']").change(function () {
        toggle_structure_data_opts("sql");
    });
});

/**
 * Toggles the disabling of the "save to file" options
 */
function toggle_save_to_file()
{
    if (!$("#radio_dump_asfile").prop("checked")) {
        $("#ul_save_asfile > li").fadeTo('fast', 0.4);
        $("#ul_save_asfile > li > input").prop('disabled', true);
        $("#ul_save_asfile > li> select").prop('disabled', true);
    } else {
        $("#ul_save_asfile > li").fadeTo('fast', 1);
        $("#ul_save_asfile > li > input").prop('disabled', false);
        $("#ul_save_asfile > li> select").prop('disabled', false);
    }
}

AJAX.registerOnload('export.js', function () {
    toggle_save_to_file();
    $("input[type='radio'][name='output_format']").change(toggle_save_to_file);
});

/**
 * For SQL plugin, toggles the disabling of the "display comments" options
 */
function toggle_sql_include_comments()
{
    $("#checkbox_sql_include_comments").change(function () {
        if (!$("#checkbox_sql_include_comments").prop("checked")) {
            $("#ul_include_comments > li").fadeTo('fast', 0.4);
            $("#ul_include_comments > li > input").prop('disabled', true);
        } else {
            // If structure is not being exported, the comment options for structure should not be enabled
            if ($("#radio_sql_structure_or_data_data").prop("checked")) {
                $("#text_sql_header_comment").removeProp('disabled').parent("li").fadeTo('fast', 1);
            } else {
                $("#ul_include_comments > li").fadeTo('fast', 1);
                $("#ul_include_comments > li > input").removeProp('disabled');
            }
        }
    });
}

AJAX.registerOnload('export.js', function () {
    /**
     * For SQL plugin, if "CREATE TABLE options" is checked/unchecked, check/uncheck each of its sub-options
     */
    var $create = $("#checkbox_sql_create_table_statements");
    var $create_options = $("#ul_create_table_statements input");
    $create.change(function () {
        $create_options.prop('checked', $(this).prop("checked"));
    });
    $create_options.change(function () {
        if ($create_options.is(":checked")) {
            $create.prop('checked', true);
        }
    });

    /**
     * Disables the view output as text option if the output must be saved as a file
     */
    $("#plugins").change(function () {
        var active_plugin = $("#plugins option:selected").val();
        var force_file = $("#force_file_" + active_plugin).val();
        if (force_file == "true") {
            if ($("#radio_dump_asfile").prop('checked') !== true) {
                $("#radio_dump_asfile").prop('checked', true);
                toggle_save_to_file();
            }
            $("#radio_view_as_text").prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            $("#radio_view_as_text").prop('disabled', false).parent().fadeTo('fast', 1);
        }
    });
});

/**
 * Toggles display of options when quick and custom export are selected
 */
function toggle_quick_or_custom()
{
    if ($("input[name='quick_or_custom']").length == 0 // custom_no_form option
        || $("#radio_custom_export").prop("checked") // custom
    ) {
        $("#databases_and_tables").show();
        $("#rows").show();
        $("#output").show();
        $("#format_specific_opts").show();
        $("#output_quick_export").hide();
        var selected_plugin_name = $("#plugins option:selected").val();
        $("#" + selected_plugin_name + "_options").show();
    } else { // quick
        $("#databases_and_tables").hide();
        $("#rows").hide();
        $("#output").hide();
        $("#format_specific_opts").hide();
        $("#output_quick_export").show();
    }
}
var time_out;
function check_time_out(time_limit)
{
    if (typeof time_limit === 'undefined' || time_limit === 0) {
        return true;
    }
    //margin of one second to avoid race condition to set/access session variable
    time_limit = time_limit + 1;
    var href = "export.php";
    var params = {
        'ajax_request' : true,
        'token' : PMA_commonParams.get('token'),
        'check_time_out' : true
    };
    clearTimeout(time_out);
    time_out = setTimeout(function(){
        $.get(href, params, function (data) {
            if (data.message === 'timeout') {
                PMA_ajaxShowMessage(
                    '<div class="error">' +
                    PMA_messages.strTimeOutError +
                    '</div>',
                    false
                );
            }
        });
    }, time_limit * 1000);

}

/**
 * Handler for Database/table alias select
 *
 * @param event object the event object
 *
 * @return void
 */
function aliasSelectHandler(event) {
    var sel = event.data.sel;
    var type = event.data.type;
    var inputId = $(this).val();
    var $label = $(this).next('label');
    $('input#' + $label.attr('for')).addClass('hide');
    $('input#' + inputId).removeClass('hide');
    $label.attr('for', inputId);
    $('#alias_modal ' + sel + '[id$=' + type + ']:visible').addClass('hide');
    var $inputWrapper = $('#alias_modal ' + sel + '#' + inputId + type);
    $inputWrapper.removeClass('hide');
    if (type === '_cols' && $inputWrapper.length > 0) {
        var outer = $inputWrapper[0].outerHTML;
        // Replace opening tags
        var regex = /<dummy_inp/gi;
        var newTag = outer.replace(regex, '<input');
        // Replace closing tags
        regex = /<\/dummy_inp/gi;
        newTag = newTag.replace(regex, '</input');
        // Assign replacement
        $inputWrapper.replaceWith(newTag);
    } else if (type === '_tables') {
        $('.table_alias_select:visible').change();
    }
    $("#alias_modal").dialog("option", "position", "center");
}

/**
 * Handler for Alias dialog box
 *
 * @param event object the event object
 *
 * @return void
 */
function createAliasModal(event) {
    event.preventDefault();
    var dlgButtons = {};
    dlgButtons[PMA_messages.strResetAll] = function() {
        $(this).find('input[type="text"]').val('');
    };
    dlgButtons[PMA_messages.strReset] = function() {
        $(this).find('input[type="text"]:visible').val('');
    };
    dlgButtons[PMA_messages.strSaveAndClose] = function() {
        $(this).dialog("close");
        $('#alias_modal').parent().appendTo($('form[name="dump"]'));
    };
    $('#alias_modal').dialog({
        width: Math.min($(window).width() - 100, 700),
        modal: true,
        dialogClass: "alias-dialog",
        buttons: dlgButtons,
        create: function() {
            $(this).css('maxHeight', $(window).height() - 150);
            $('.alias-dialog .ui-dialog-titlebar-close').remove();
        },
        close: function() {
            var isEmpty = true;
            $(this).find('input[type="text"]').each(function() {
                // trim input fields on close
                $(this).val($(this).val().trim());
                // check if non empty field present
                if ($(this).val()) {
                    isEmpty = false;
                }
            });
            $('input#btn_alias_config').attr('checked', !isEmpty);
        },
        position: 'center'
    });
    // Call change event of .table_alias_select
    $('.table_alias_select:visible').trigger('change');
}

AJAX.registerOnload('export.js', function () {
    $("input[type='radio'][name='quick_or_custom']").change(toggle_quick_or_custom);

    $("#scroll_to_options_msg").hide();
    $("#format_specific_opts div.format_specific_options")
    .hide()
    .css({
        "border": 0,
        "margin": 0,
        "padding": 0
    })
    .find("h3")
    .remove();
    toggle_quick_or_custom();
    toggle_structure_data_opts($("select#plugins").val());
    toggle_sql_include_comments();

    /**
     * Initially disables the "Dump some row(s)" sub-options
     */
    disable_dump_some_rows_sub_options();

    /**
     * Disables the "Dump some row(s)" sub-options when it is not selected
     */
    $("input[type='radio'][name='allrows']").change(function () {
        if ($("input[type='radio'][name='allrows']").prop("checked")) {
            enable_dump_some_rows_sub_options();
        } else {
            disable_dump_some_rows_sub_options();
        }
    });

    // Open Alias Modal Dialog on click
    $('#btn_alias_config').on('click', createAliasModal);

    // Database alias select on change event
    $('#db_alias_select').on(
        'change',
        {sel: 'span', type: '_tables'},
        aliasSelectHandler
    );

    // Table alias select on change event
    $('.table_alias_select').on(
        'change',
        {sel: 'table', type: '_cols'},
        aliasSelectHandler
    );
});
