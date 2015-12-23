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
 * Return template data as a json object
 *
 * @returns template data
 */
function getTemplateData()
{
    var $form = $('form[name="dump"]');
    var blacklist = ['token', 'server', 'db', 'table', 'single_table',
        'export_type', 'export_method', 'sql_query', 'template_id'];
    var obj = {};
    var arr = $form.serializeArray();
    $.each(arr, function () {
        if ($.inArray(this.name, blacklist) < 0) {
            if (obj[this.name] !== undefined) {
                if (! obj[this.name].push) {
                    obj[this.name] = [obj[this.name]];
                }
                obj[this.name].push(this.value || '');
            } else {
                obj[this.name] = this.value || '';
            }
        }
    });
    // include unchecked checboxes (which are ignored by serializeArray()) with null
    // to uncheck them when loading the template
    $form.find('input[type="checkbox"]:not(:checked)').each(function () {
        if (obj[this.name] === undefined) {
            obj[this.name] = null;
        }
    });
    // include empty multiselects
    $form.find('select').each(function () {
        if ($(this).find('option:selected').length == 0) {
            obj[this.name] = [];
        }
    });
    return obj;
}

/**
 * Create a template with selected options
 *
 * @param name name of the template
 */
function createTemplate(name)
{
    var templateData = getTemplateData();

    var params = {
        ajax_request : true,
        token : PMA_commonParams.get('token'),
        server : PMA_commonParams.get('server'),
        db : PMA_commonParams.get('db'),
        table : PMA_commonParams.get('table'),
        exportType : $('input[name="export_type"]').val(),
        templateAction : 'create',
        templateName : name,
        templateData : JSON.stringify(templateData)
    };

    PMA_ajaxShowMessage();
    $.post('tbl_export.php', params, function (response) {
        if (response.success === true) {
            $('#templateName').val('');
            $('#template').html(response.data);
            $("#template").find("option").each(function() {
                if ($(this).text() == name) {
                    $(this).prop('selected', true);
                }
            });
            PMA_ajaxShowMessage(PMA_messages.strTemplateCreated);
        } else {
            PMA_ajaxShowMessage(response.error, false);
        }
    });
}

/**
 * Loads a template
 *
 * @param id ID of the template to load
 */
function loadTemplate(id)
{
    var params = {
        ajax_request : true,
        token : PMA_commonParams.get('token'),
        server : PMA_commonParams.get('server'),
        db : PMA_commonParams.get('db'),
        table : PMA_commonParams.get('table'),
        exportType : $('input[name="export_type"]').val(),
        templateAction : 'load',
        templateId : id,
    };

    PMA_ajaxShowMessage();
    $.post('tbl_export.php', params, function (response) {
        if (response.success === true) {
            var $form = $('form[name="dump"]');
            var options = $.parseJSON(response.data);
            $.each(options, function (key, value) {
                var $element = $form.find('[name="' + key + '"]');
                if ($element.length) {
                    if (($element.is('input') && $element.attr('type') == 'checkbox') && value === null) {
                        $element.prop('checked', false);
                    } else {
                        if (($element.is('input') && $element.attr('type') == 'checkbox') ||
                            ($element.is('input') && $element.attr('type') == 'radio') ||
                            ($element.is('select') && $element.attr('multiple') == 'multiple')) {
                            if (! value.push) {
                                value = [value];
                            }
                        }
                        $element.val(value);
                    }
                    $element.trigger('change');
                }
            });
            $('input[name="template_id"]').val(id);
            PMA_ajaxShowMessage(PMA_messages.strTemplateLoaded);
        } else {
            PMA_ajaxShowMessage(response.error, false);
        }
    });
}

/**
 * Updates an existing template with current options
 *
 * @param id ID of the template to update
 */
function updateTemplate(id)
{
    var templateData = getTemplateData();

    var params = {
        ajax_request : true,
        token : PMA_commonParams.get('token'),
        server : PMA_commonParams.get('server'),
        db : PMA_commonParams.get('db'),
        table : PMA_commonParams.get('table'),
        exportType : $('input[name="export_type"]').val(),
        templateAction : 'update',
        templateId : id,
        templateData : JSON.stringify(templateData)
    };

    PMA_ajaxShowMessage();
    $.post('tbl_export.php', params, function (response) {
        if (response.success === true) {
            PMA_ajaxShowMessage(PMA_messages.strTemplateUpdated);
        } else {
            PMA_ajaxShowMessage(response.error, false);
        }
    });
}

/**
 * Delete a template
 *
 * @param id ID of the template to delete
 */
function deleteTemplate(id)
{
    var params = {
        ajax_request : true,
        token : PMA_commonParams.get('token'),
        server : PMA_commonParams.get('server'),
        db : PMA_commonParams.get('db'),
        table : PMA_commonParams.get('table'),
        exportType : $('input[name="export_type"]').val(),
        templateAction : 'delete',
        templateId : id,
    };

    PMA_ajaxShowMessage();
    $.post('tbl_export.php', params, function (response) {
        if (response.success === true) {
            $('#template').find('option[value="' + id + '"]').remove();
            PMA_ajaxShowMessage(PMA_messages.strTemplateDeleted);
        } else {
            PMA_ajaxShowMessage(response.error, false);
        }
    });
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('export.js', function () {
    $("#plugins").unbind('change');
    $("input[type='radio'][name='sql_structure_or_data']").unbind('change');
    $("input[type='radio'][name$='_structure_or_data']").off('change');
    $("input[type='radio'][name='output_format']").unbind('change');
    $("#checkbox_sql_include_comments").unbind('change');
    $("input[type='radio'][name='quick_or_custom']").unbind('change');
    $("input[type='radio'][name='allrows']").unbind('change');
    $('#btn_alias_config').off('click');
    $('#db_alias_select').off('change');
    $('.table_alias_select').off('change');
    $('input[name="table_select[]"]').off('change');
    $('input[name="table_structure[]"]').off('change');
    $('input[name="table_data[]"]').off('change');
    $('#table_structure_all').off('change');
    $('#table_data_all').off('change');
    $('input[name="createTemplate"]').off('click');
    $('select[name="template"]').off('change');
    $('input[name="updateTemplate"]').off('click');
    $('input[name="deleteTemplate"]').off('click');
});

AJAX.registerOnload('export.js', function () {

    /**
     * Export template handling code
     */
    // create a new template
    $('input[name="createTemplate"]').on('click', function (e) {
        e.preventDefault();
        var name = $('input[name="templateName"]').val();
        if (name.length) {
            createTemplate(name);
        }
    });

    // load an existing template
    $('select[name="template"]').on('change', function (e) {
        e.preventDefault();
        var id = $(this).val();
        if (id.length) {
            loadTemplate(id);
        }
    });

    // udpate an existing template with new criteria
    $('input[name="updateTemplate"]').on('click', function (e) {
        e.preventDefault();
        var id = $('select[name="template"]').val();
        if (id.length) {
            updateTemplate(id);
        }
    });

    // delete an existing template
    $('input[name="deleteTemplate"]').on('click', function (e) {
        e.preventDefault();
        var id = $('select[name="template"]').val();
        if (id.length) {
            deleteTemplate(id);
        }
    });

    /**
     * Toggles the hiding and showing of each plugin's options
     * according to the currently selected plugin from the dropdown list
     */
    $("#plugins").change(function () {
        $("#format_specific_opts").find("div.format_specific_options").hide();
        var selected_plugin_name = $("#plugins").find("option:selected").val();
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

        if (show == 'structure') {
            $('#checkbox_sql_auto_increment').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            $("#checkbox_sql_auto_increment").removeProp('disabled').parent().fadeTo('fast', 1);
        }
    });

    // For separate-file exports only ZIP compression is allowed
    $('input[type="checkbox"][name="as_separate_files"]').change(function(){
        if ($(this).is(':checked')) {
            $('#compression').val('zip');
        }
    });

    $('#compression').change(function(){
        if ($('option:selected').val() !== 'zip') {
            $('input[type="checkbox"][name="as_separate_files"]').prop('checked', false);
        }
    });

});

function setup_table_structure_or_data() {
    if ($("input[name='export_type']").val() != 'database') {
        return;
    }
    var pluginName = $("#plugins").find("option:selected").val();
    var formElemName = pluginName + "_structure_or_data";
    var force_structure_or_data = !($("input[name='" + formElemName + "_default']").length);

    if (force_structure_or_data === true) {
        $('input[name="structure_or_data_forced"]').val(1);
        $('.export_structure input[type="checkbox"], .export_data input[type="checkbox"]')
            .prop('disabled', true);
        $('.export_structure, .export_data').fadeTo('fast', 0.4);
    } else {
        $('input[name="structure_or_data_forced"]').val(0);
        $('.export_structure input[type="checkbox"], .export_data input[type="checkbox"]')
            .removeProp('disabled');
        $('.export_structure, .export_data').fadeTo('fast', 1);

        var structure_or_data = $('input[name="' + formElemName + '_default"]').val();

        if (structure_or_data == 'structure') {
            $('.export_data input[type="checkbox"]')
                .prop('checked', false);
        } else if (structure_or_data == 'data') {
            $('.export_structure input[type="checkbox"]')
                .prop('checked', false);
        }
        if (structure_or_data == 'structure' || structure_or_data == 'structure_and_data') {
            if (!$('.export_structure input[type="checkbox"]:checked').length) {
                $('input[name="table_select[]"]:checked')
                    .closest('tr')
                    .find('.export_structure input[type="checkbox"]')
                    .prop('checked', true);
            }
        }
        if (structure_or_data == 'data' || structure_or_data == 'structure_and_data') {
            if (!$('.export_data input[type="checkbox"]:checked').length) {
                $('input[name="table_select[]"]:checked')
                    .closest('tr')
                    .find('.export_data input[type="checkbox"]')
                    .prop('checked', true);
            }
        }

        check_selected_tables();
        check_table_select_all();
    }
}

/**
 * Toggles the hiding and showing of plugin structure-specific and data-specific
 * options
 */
function toggle_structure_data_opts()
{
    var pluginName = $("select#plugins").val();
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

/**
 * Toggles the disabling of the "save to file" options
 */
function toggle_save_to_file()
{
    var $ulSaveAsfile = $("#ul_save_asfile");
    if (!$("#radio_dump_asfile").prop("checked")) {
        $ulSaveAsfile.find("> li").fadeTo('fast', 0.4);
        $ulSaveAsfile.find("> li > input").prop('disabled', true);
        $ulSaveAsfile.find("> li > select").prop('disabled', true);
    } else {
        $ulSaveAsfile.find("> li").fadeTo('fast', 1);
        $ulSaveAsfile.find("> li > input").prop('disabled', false);
        $ulSaveAsfile.find("> li > select").prop('disabled', false);
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
        var $ulIncludeComments = $("#ul_include_comments");
        if (!$("#checkbox_sql_include_comments").prop("checked")) {
            $ulIncludeComments.find("> li").fadeTo('fast', 0.4);
            $ulIncludeComments.find("> li > input").prop('disabled', true);
        } else {
            // If structure is not being exported, the comment options for structure should not be enabled
            if ($("#radio_sql_structure_or_data_data").prop("checked")) {
                $("#text_sql_header_comment").removeProp('disabled').parent("li").fadeTo('fast', 1);
            } else {
                $ulIncludeComments.find("> li").fadeTo('fast', 1);
                $ulIncludeComments.find("> li > input").removeProp('disabled');
            }
        }
    });
}

function check_table_select_all() {
    var total = $('input[name="table_select[]"]').length;
    var str_checked = $('input[name="table_structure[]"]:checked').length;
    var data_checked = $('input[name="table_data[]"]:checked').length;
    var str_all = $('#table_structure_all');
    var data_all = $('#table_data_all');

    if (str_checked == total) {
        str_all
            .prop("indeterminate", false)
            .prop('checked', true);
    } else if (str_checked === 0) {
        str_all
            .prop("indeterminate", false)
            .prop('checked', false);
    } else {
        str_all
            .prop("indeterminate", true)
            .prop('checked', false);
    }

    if (data_checked == total) {
        data_all
            .prop("indeterminate", false)
            .prop('checked', true);
    } else if (data_checked === 0) {
        data_all
            .prop("indeterminate", false)
            .prop('checked', false);
    } else {
        data_all
            .prop("indeterminate", true)
            .prop('checked', false);
    }
}

function toggle_table_select_all_str() {
    var str_all = $('#table_structure_all').is(':checked');
    if (str_all) {
        $('input[name="table_structure[]"]').prop('checked', true);
    } else {
        $('input[name="table_structure[]"]').prop('checked', false);
    }
}

function toggle_table_select_all_data() {
    var data_all = $('#table_data_all').is(':checked');
    if (data_all) {
        $('input[name="table_data[]"]').prop('checked', true);
    } else {
        $('input[name="table_data[]"]').prop('checked', false);
    }
}

function check_selected_tables(argument) {
    $('.export_table_select tbody tr').each(function() {
        check_table_selected(this);
    });
}

function check_table_selected(row) {
    var $row = $(row);
    var table_select = $row.find('input[name="table_select[]"]');
    var str_check = $row.find('input[name="table_structure[]"]');
    var data_check = $row.find('input[name="table_data[]"]');

    var data = data_check.is(':checked:not(:disabled)');
    var structure = str_check.is(':checked:not(:disabled)');

    if (data && structure) {
        table_select.prop({checked: true, indeterminate: false});
    } else if (data || structure) {
        table_select.prop({checked: true, indeterminate: true});
    } else {
        table_select.prop({checked: false, indeterminate: false});
    }
}

function toggle_table_select(row) {
    var $row = $(row);
    var table_selected = $row.find('input[name="table_select[]"]').is(':checked');

    if (table_selected) {
        $row.find('input[type="checkbox"]:not(:disabled)').prop('checked', true);
    } else {
        $row.find('input[type="checkbox"]:not(:disabled)').prop('checked', false);
    }
}

AJAX.registerOnload('export.js', function () {
    /**
     * For SQL plugin, if "CREATE TABLE options" is checked/unchecked, check/uncheck each of its sub-options
     */
    var $create = $("#checkbox_sql_create_table_statements");
    var $create_options = $("#ul_create_table_statements").find("input");
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
        var active_plugin = $("#plugins").find("option:selected").val();
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

    $("input[type='radio'][name$='_structure_or_data']").on('change', function () {
        toggle_structure_data_opts();
    });

    $('input[name="table_select[]"]').on('change', function() {
        toggle_table_select($(this).closest('tr'));
        check_table_select_all();
    });

    $('input[name="table_structure[]"]').on('change', function() {
        check_table_selected($(this).closest('tr'));
        check_table_select_all();
    });

    $('input[name="table_data[]"]').on('change', function() {
        check_table_selected($(this).closest('tr'));
        check_table_select_all();
    });

    $('#table_structure_all').on('change', function() {
        toggle_table_select_all_str();
        check_selected_tables();
    });

    $('#table_data_all').on('change', function() {
        toggle_table_select_all_data();
        check_selected_tables();
    });

    if ($("input[name='export_type']").val() == 'database') {
        // Hide structure or data radio buttons
        $("input[type='radio'][name$='_structure_or_data']").each(function() {
            var $this = $(this);
            var name = $this.prop('name');
            var val = $('input[name="' + name + '"]:checked').val();
            var name_default = name + '_default';
            if (!$('input[name="' + name_default + '"]').length) {
                $this
                    .after(
                        $('<input type="hidden" name="' + name_default + '" value="' + val + '" disabled>')
                    )
                    .after(
                        $('<input type="hidden" name="' + name + '" value="structure_and_data">')
                    );
                $this.parent().find('label').remove();
            } else {
                $this.parent().remove();
            }
        });
        $("input[type='radio'][name$='_structure_or_data']").remove();

        // Disable CREATE table checkbox for sql
        var createTableCheckbox = $('#checkbox_sql_create_table');
        createTableCheckbox.prop('checked', true);
        var dummyCreateTable = $('#checkbox_sql_create_table')
            .clone()
            .removeAttr('id')
            .attr('type', 'hidden');
        createTableCheckbox
            .prop('disabled', true)
            .after(dummyCreateTable)
            .parent()
            .fadeTo('fast', 0.4);

        setup_table_structure_or_data();
    }

    /**
     * Handle force structure_or_data
     */
    $("#plugins").change(setup_table_structure_or_data);
});

/**
 * Toggles display of options when quick and custom export are selected
 */
function toggle_quick_or_custom()
{
    if ($("input[name='quick_or_custom']").length === 0 // custom_no_form option
        || $("#radio_custom_export").prop("checked") // custom
    ) {
        $("#databases_and_tables").show();
        $("#rows").show();
        $("#output").show();
        $("#format_specific_opts").show();
        $("#output_quick_export").hide();
        var selected_plugin_name = $("#plugins").find("option:selected").val();
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
        if (outer.match(regex)) {
            var newTag = outer.replace(regex, '<input');
            // Replace closing tags
            regex = /<\/dummy_inp/gi;
            newTag = newTag.replace(regex, '</input');
            // Assign replacement
            $inputWrapper.replaceWith(newTag);
        }
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
        // do not fillup form submission with empty values
        $.each($(this).find('input[type="text"]'), function (i, e) {
            if ($(e).val().trim().length == 0) {
                $(e).prop('disabled', true);
            }
        });
        $('#alias_modal').parent().appendTo($('form[name="dump"]'));
    };
    $('#alias_modal').find('input[type="text"]').prop('disabled', false);
    $('#alias_modal').dialog({
        width: Math.min($(window).width() - 100, 700),
        maxHeight: $(window).height(),
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
            $('input#btn_alias_config').prop('checked', !isEmpty);
        },
        position: { my: "center top", at: "center top", of: window }
    });
    // Call change event of .table_alias_select
    $('.table_alias_select:visible').trigger('change');
}

AJAX.registerOnload('export.js', function () {
    $("input[type='radio'][name='quick_or_custom']").change(toggle_quick_or_custom);

    $("#scroll_to_options_msg").hide();
    $("#format_specific_opts").find("div.format_specific_options")
        .hide()
        .css({
            "border": 0,
            "margin": 0,
            "padding": 0
        })
        .find("h3")
        .remove();
    toggle_quick_or_custom();
    toggle_structure_data_opts();
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
