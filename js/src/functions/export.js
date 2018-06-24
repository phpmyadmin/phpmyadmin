/**
 * Functions used in the export tab
 *
 */
/**
 * Disables the "Dump some row(s)" sub-options
 */
export function disable_dump_some_rows_sub_options () {
    $('label[for=\'limit_to\']').fadeTo('fast', 0.4);
    $('label[for=\'limit_from\']').fadeTo('fast', 0.4);
    $('input[type=\'text\'][name=\'limit_to\']').prop('disabled', 'disabled');
    $('input[type=\'text\'][name=\'limit_from\']').prop('disabled', 'disabled');
}

/**
 * Enables the "Dump some row(s)" sub-options
 */
export function enable_dump_some_rows_sub_options () {
    $('label[for=\'limit_to\']').fadeTo('fast', 1);
    $('label[for=\'limit_from\']').fadeTo('fast', 1);
    $('input[type=\'text\'][name=\'limit_to\']').prop('disabled', '');
    $('input[type=\'text\'][name=\'limit_from\']').prop('disabled', '');
}

/**
 * Return template data as a json object
 *
 * @returns template data
 */
export function getTemplateData () {
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
        if ($(this).find('option:selected').length === 0) {
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
export function createTemplate (name) {
    var templateData = getTemplateData();

    var params = {
        ajax_request : true,
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
            $('#template').find('option').each(function () {
                if ($(this).text() === name) {
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
export function loadTemplate (id) {
    var params = {
        ajax_request : true,
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
            var options = JSON.parse(response.data);
            $.each(options, function (key, value) {
                var $element = $form.find('[name="' + key + '"]');
                if ($element.length) {
                    if (($element.is('input') && $element.attr('type') === 'checkbox') && value === null) {
                        $element.prop('checked', false);
                    } else {
                        if (($element.is('input') && $element.attr('type') === 'checkbox') ||
                            ($element.is('input') && $element.attr('type') === 'radio') ||
                            ($element.is('select') && $element.attr('multiple') === 'multiple')) {
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
export function updateTemplate (id) {
    var templateData = getTemplateData();

    var params = {
        ajax_request : true,
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
export function deleteTemplate (id) {
    var params = {
        ajax_request : true,
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

export function setup_table_structure_or_data () {
    if ($('input[name=\'export_type\']').val() !== 'database') {
        return;
    }
    var pluginName = $('#plugins').find('option:selected').val();
    var formElemName = pluginName + '_structure_or_data';
    var force_structure_or_data = !($('input[name=\'' + formElemName + '_default\']').length);

    if (force_structure_or_data === true) {
        $('input[name="structure_or_data_forced"]').val(1);
        $('.export_structure input[type="checkbox"], .export_data input[type="checkbox"]')
            .prop('disabled', true);
        $('.export_structure, .export_data').fadeTo('fast', 0.4);
    } else {
        $('input[name="structure_or_data_forced"]').val(0);
        $('.export_structure input[type="checkbox"], .export_data input[type="checkbox"]')
            .prop('disabled', false);
        $('.export_structure, .export_data').fadeTo('fast', 1);

        var structure_or_data = $('input[name="' + formElemName + '_default"]').val();

        if (structure_or_data === 'structure') {
            $('.export_data input[type="checkbox"]')
                .prop('checked', false);
        } else if (structure_or_data === 'data') {
            $('.export_structure input[type="checkbox"]')
                .prop('checked', false);
        }
        if (structure_or_data === 'structure' || structure_or_data === 'structure_and_data') {
            if (!$('.export_structure input[type="checkbox"]:checked').length) {
                $('input[name="table_select[]"]:checked')
                    .closest('tr')
                    .find('.export_structure input[type="checkbox"]')
                    .prop('checked', true);
            }
        }
        if (structure_or_data === 'data' || structure_or_data === 'structure_and_data') {
            if (!$('.export_data input[type="checkbox"]:checked').length) {
                $('input[name="table_select[]"]:checked')
                    .closest('tr')
                    .find('.export_data input[type="checkbox"]')
                    .prop('checked', true);
            }
        }

        check_selected_tables();
        check_table_select_all();
        check_table_select_struture_or_data();
    }
}

/**
 * Toggles the hiding and showing of plugin structure-specific and data-specific
 * options
 */
export function toggle_structure_data_opts () {
    var pluginName = $('select#plugins').val();
    var radioFormName = pluginName + '_structure_or_data';
    var dataDiv = '#' + pluginName + '_data';
    var structureDiv = '#' + pluginName + '_structure';
    var show = $('input[type=\'radio\'][name=\'' + radioFormName + '\']:checked').val();
    if (show === 'data') {
        $(dataDiv).slideDown('slow');
        $(structureDiv).slideUp('slow');
    } else {
        $(structureDiv).slideDown('slow');
        if (show === 'structure') {
            $(dataDiv).slideUp('slow');
        } else {
            $(dataDiv).slideDown('slow');
        }
    }
}

/**
 * Toggles the disabling of the "save to file" options
 */
export function toggle_save_to_file () {
    var $ulSaveAsfile = $('#ul_save_asfile');
    if (!$('#radio_dump_asfile').prop('checked')) {
        $ulSaveAsfile.find('> li').fadeTo('fast', 0.4);
        $ulSaveAsfile.find('> li > input').prop('disabled', true);
        $ulSaveAsfile.find('> li > select').prop('disabled', true);
    } else {
        $ulSaveAsfile.find('> li').fadeTo('fast', 1);
        $ulSaveAsfile.find('> li > input').prop('disabled', false);
        $ulSaveAsfile.find('> li > select').prop('disabled', false);
    }
}

/**
 * For SQL plugin, toggles the disabling of the "display comments" options
 */
export function toggle_sql_include_comments () {
    $('#checkbox_sql_include_comments').on('change', function () {
        var $ulIncludeComments = $('#ul_include_comments');
        if (!$('#checkbox_sql_include_comments').prop('checked')) {
            $ulIncludeComments.find('> li').fadeTo('fast', 0.4);
            $ulIncludeComments.find('> li > input').prop('disabled', true);
        } else {
            // If structure is not being exported, the comment options for structure should not be enabled
            if ($('#radio_sql_structure_or_data_data').prop('checked')) {
                $('#text_sql_header_comment').prop('disabled', false).parent('li').fadeTo('fast', 1);
            } else {
                $ulIncludeComments.find('> li').fadeTo('fast', 1);
                $ulIncludeComments.find('> li > input').prop('disabled', false);
            }
        }
    });
}

export function check_table_select_all () {
    var total = $('input[name="table_select[]"]').length;
    var str_checked = $('input[name="table_structure[]"]:checked').length;
    var data_checked = $('input[name="table_data[]"]:checked').length;
    var str_all = $('#table_structure_all');
    var data_all = $('#table_data_all');

    if (str_checked === total) {
        str_all
            .prop('indeterminate', false)
            .prop('checked', true);
    } else if (str_checked === 0) {
        str_all
            .prop('indeterminate', false)
            .prop('checked', false);
    } else {
        str_all
            .prop('indeterminate', true)
            .prop('checked', false);
    }

    if (data_checked === total) {
        data_all
            .prop('indeterminate', false)
            .prop('checked', true);
    } else if (data_checked === 0) {
        data_all
            .prop('indeterminate', false)
            .prop('checked', false);
    } else {
        data_all
            .prop('indeterminate', true)
            .prop('checked', false);
    }
}

export function check_table_select_struture_or_data () {
    var str_checked = $('input[name="table_structure[]"]:checked').length;
    var data_checked = $('input[name="table_data[]"]:checked').length;
    var auto_increment = $('#checkbox_sql_auto_increment');

    var pluginName = $('select#plugins').val();
    var dataDiv = '#' + pluginName + '_data';
    var structureDiv = '#' + pluginName + '_structure';

    if (str_checked === 0) {
        $(structureDiv).slideUp('slow');
    } else {
        $(structureDiv).slideDown('slow');
    }

    if (data_checked === 0) {
        $(dataDiv).slideUp('slow');
        auto_increment.prop('disabled', true).parent().fadeTo('fast', 0.4);
    } else {
        $(dataDiv).slideDown('slow');
        auto_increment.prop('disabled', false).parent().fadeTo('fast', 1);
    }
}

export function toggle_table_select_all_str () {
    var str_all = $('#table_structure_all').is(':checked');
    if (str_all) {
        $('input[name="table_structure[]"]').prop('checked', true);
    } else {
        $('input[name="table_structure[]"]').prop('checked', false);
    }
}

export function toggle_table_select_all_data () {
    var data_all = $('#table_data_all').is(':checked');
    if (data_all) {
        $('input[name="table_data[]"]').prop('checked', true);
    } else {
        $('input[name="table_data[]"]').prop('checked', false);
    }
}

export function check_selected_tables (argument) {
    $('.export_table_select tbody tr').each(function () {
        check_table_selected(this);
    });
}

export function check_table_selected (row) {
    var $row = $(row);
    var table_select = $row.find('input[name="table_select[]"]');
    var str_check = $row.find('input[name="table_structure[]"]');
    var data_check = $row.find('input[name="table_data[]"]');

    var data = data_check.is(':checked:not(:disabled)');
    var structure = str_check.is(':checked:not(:disabled)');

    if (data && structure) {
        table_select.prop({ checked: true, indeterminate: false });
        $row.addClass('marked');
    } else if (data || structure) {
        table_select.prop({ checked: true, indeterminate: true });
        $row.removeClass('marked');
    } else {
        table_select.prop({ checked: false, indeterminate: false });
        $row.removeClass('marked');
    }
}

export function toggle_table_select (row) {
    var $row = $(row);
    var table_selected = $row.find('input[name="table_select[]"]').is(':checked');

    if (table_selected) {
        $row.find('input[type="checkbox"]:not(:disabled)').prop('checked', true);
        $row.addClass('marked');
    } else {
        $row.find('input[type="checkbox"]:not(:disabled)').prop('checked', false);
        $row.removeClass('marked');
    }
}

export function handleAddProcCheckbox () {
    if ($('#table_structure_all').is(':checked') === true
        && $('#table_data_all').is(':checked') === true
    ) {
        $('#checkbox_sql_procedure_function').prop('checked', true);
    } else {
        $('#checkbox_sql_procedure_function').prop('checked', false);
    }
}

/**
 * Toggles display of options when quick and custom export are selected
 */
export function toggle_quick_or_custom () {
    if ($('input[name=\'quick_or_custom\']').length === 0 // custom_no_form option
        || $('#radio_custom_export').prop('checked') // custom
    ) {
        $('#databases_and_tables').show();
        $('#rows').show();
        $('#output').show();
        $('#format_specific_opts').show();
        $('#output_quick_export').hide();
        var selected_plugin_name = $('#plugins').find('option:selected').val();
        $('#' + selected_plugin_name + '_options').show();
    } else { // quick
        $('#databases_and_tables').hide();
        $('#rows').hide();
        $('#output').hide();
        $('#format_specific_opts').hide();
        $('#output_quick_export').show();
    }
}
var time_out;
export function check_time_out (time_limit) {
    if (typeof time_limit === 'undefined' || time_limit === 0) {
        return true;
    }
    // margin of one second to avoid race condition to set/access session variable
    time_limit = time_limit + 1;
    var href = 'export.php';
    var params = {
        'ajax_request' : true,
        'check_time_out' : true
    };
    clearTimeout(time_out);
    time_out = setTimeout(function () {
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
export function aliasSelectHandler (event) {
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
        $('.table_alias_select:visible').trigger('change');
    }
    $('#alias_modal').dialog('option', 'position', 'center');
}

/**
 * Handler for Alias dialog box
 *
 * @param event object the event object
 *
 * @return void
 */
export function createAliasModal (event) {
    event.preventDefault();
    var dlgButtons = {};
    dlgButtons[PMA_messages.strSaveAndClose] = function () {
        $(this).dialog('close');
        $('#alias_modal').parent().appendTo($('form[name="dump"]'));
    };
    $('#alias_modal').dialog({
        width: Math.min($(window).width() - 100, 700),
        maxHeight: $(window).height(),
        modal: true,
        dialogClass: 'alias-dialog',
        buttons: dlgButtons,
        create: function () {
            $(this).css('maxHeight', $(window).height() - 150);
            var db = PMA_commonParams.get('db');
            if (db) {
                var option = $('<option></option>');
                option.text(db);
                option.attr('value', db);
                $('#db_alias_select').append(option).val(db).trigger('change');
            } else {
                var params = {
                    ajax_request : true,
                    server : PMA_commonParams.get('server'),
                    type: 'list-databases'
                };
                $.post('ajax.php', params, function (response) {
                    if (response.success === true) {
                        $.each(response.databases, function (idx, value) {
                            var option = $('<option></option>');
                            option.text(value);
                            option.attr('value', value);
                            $('#db_alias_select').append(option);
                        });
                    } else {
                        PMA_ajaxShowMessage(response.error, false);
                    }
                });
            }
        },
        close: function () {
            var isEmpty = true;
            $(this).find('input[type="text"]').each(function () {
                // trim empty input fields on close
                if ($(this).val()) {
                    isEmpty = false;
                } else {
                    $(this).parents('tr').remove();
                }
            });
            // Toggle checkbox based on aliases
            $('input#btn_alias_config').prop('checked', !isEmpty);
        },
        position: { my: 'center top', at: 'center top', of: window }
    });
}

export function aliasToggleRow (elm) {
    var inputs = elm.parents('tr').find('input,button');
    if (elm.val()) {
        inputs.attr('disabled', false);
    } else {
        inputs.attr('disabled', true);
    }
}

export function addAlias (type, name, field, value) {
    if (value === '') {
        return;
    }

    var row = $('#alias_data tfoot tr').clone();
    row.find('th').text(type);
    row.find('td:first').text(name);
    row.find('input').attr('name', field);
    row.find('input').val(value);
    row.find('.alias_remove').on('click', function () {
        $(this).parents('tr').remove();
    });

    var matching = $('#alias_data [name="' + $.escapeSelector(field) + '"]');
    if (matching.length > 0) {
        matching.parents('tr').remove();
    }

    $('#alias_data tbody').append(row);
}
