/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Module import
 */
import {
    createTemplate,
    loadTemplate,
    updateTemplate,
    deleteTemplate,
    toggleSaveToFile,
    toggleStructureDataOpts,
    checkTableSelectAll,
    handleAddProcCheckbox,
    checkTableSelectStrutureOrData,
    checkTableSelected,
    toggleTableSelect,
    toggleTableSelectAllStr,
    checkSelectedTables,
    toggleTableSelectAllData,
    setupTableStructureOrData,
    toggleQuickOrCustom,
    toggleSqlIncludeComments,
    disableDumpSomeRowsSubOptions,
    enableDumpSomeRowsSubOptions,
    aliasToggleRow,
    addAlias,
    createAliasModal
} from './functions/export';
import { PMA_Messages as messages } from './variables/export_variables';
import CommonParams from './variables/common_params';
import { PMA_ajaxShowMessage } from './utils/show_ajax_messages';

/**
 * @package PhpMyAdmin
 *
 * Export
 */

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownExport () {
    $('#plugins').off('change');
    $('input[type=\'radio\'][name=\'sql_structure_or_data\']').off('change');
    $('input[type=\'radio\'][name$=\'_structure_or_data\']').off('change');
    $('input[type=\'radio\'][name=\'output_format\']').off('change');
    $('#checkbox_sql_include_comments').off('change');
    $('input[type=\'radio\'][name=\'quick_or_custom\']').off('change');
    $('input[type=\'radio\'][name=\'allrows\']').off('change');
    $('#btn_alias_config').off('click');
    $('.alias_remove').off('click');
    $('#db_alias_button').off('click');
    $('#table_alias_button').off('click');
    $('#column_alias_button').off('click');
    $('input[name="table_select[]"]').off('change');
    $('input[name="table_structure[]"]').off('change');
    $('input[name="table_data[]"]').off('change');
    $('#table_structure_all').off('change');
    $('#table_data_all').off('change');
    $('input[name="createTemplate"]').off('click');
    $('select[name="template"]').off('change');
    $('input[name="updateTemplate"]').off('click');
    $('input[name="deleteTemplate"]').off('click');
}

function onloadExportTemplate () {
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
    $('#plugins').on('change', function () {
        $('#format_specific_opts').find('div.format_specific_options').hide();
        var selectedPluginName = $('#plugins').find('option:selected').val();
        $('#' + selectedPluginName + '_options').show();
    });

    /**
     * Toggles the enabling and disabling of the SQL plugin's comment options that apply only when exporting structure
     */
    $('input[type=\'radio\'][name=\'sql_structure_or_data\']').on('change', function () {
        var commentsArePresent = $('#checkbox_sql_include_comments').prop('checked');
        var show = $('input[type=\'radio\'][name=\'sql_structure_or_data\']:checked').val();
        if (show === 'data') {
            // disable the SQL comment options
            if (commentsArePresent) {
                $('#checkbox_sql_dates').prop('disabled', true).parent().fadeTo('fast', 0.4);
            }
            $('#checkbox_sql_relation').prop('disabled', true).parent().fadeTo('fast', 0.4);
            $('#checkbox_sql_mime').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            // enable the SQL comment options
            if (commentsArePresent) {
                $('#checkbox_sql_dates').prop('disabled', false).parent().fadeTo('fast', 1);
            }
            $('#checkbox_sql_relation').prop('disabled', false).parent().fadeTo('fast', 1);
            $('#checkbox_sql_mime').prop('disabled', false).parent().fadeTo('fast', 1);
        }

        if (show === 'structure') {
            $('#checkbox_sql_auto_increment').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            $('#checkbox_sql_auto_increment').prop('disabled', false).parent().fadeTo('fast', 1);
        }
    });

    // For separate-file exports only ZIP compression is allowed
    $('input[type="checkbox"][name="as_separate_files"]').on('change', function () {
        if ($(this).is(':checked')) {
            $('#compression').val('zip');
        }
    });

    $('#compression').on('change', function () {
        if ($('option:selected').val() !== 'zip') {
            $('input[type="checkbox"][name="as_separate_files"]').prop('checked', false);
        }
    });
}

function onloadSaveToFile () {
    toggleSaveToFile();
    $('input[type=\'radio\'][name=\'output_format\']').on('change', toggleSaveToFile);
}

function onloadExportOptions () {
    /**
     * For SQL plugin, if "CREATE TABLE options" is checked/unchecked, check/uncheck each of its sub-options
     */
    var $create = $('#checkbox_sql_create_table_statements');
    var $createOptions = $('#ul_create_table_statements').find('input');
    $create.on('change', function () {
        $createOptions.prop('checked', $(this).prop('checked'));
    });
    $createOptions.on('change', function () {
        if ($createOptions.is(':checked')) {
            $create.prop('checked', true);
        }
    });

    /**
     * Disables the view output as text option if the output must be saved as a file
     */
    $('#plugins').on('change', function () {
        var activePlugin = $('#plugins').find('option:selected').val();
        var forceFile = $('#force_file_' + activePlugin).val();
        if (forceFile === 'true') {
            if ($('#radio_dump_asfile').prop('checked') !== true) {
                $('#radio_dump_asfile').prop('checked', true);
                toggleSaveToFile();
            }
            $('#radio_view_as_text').prop('disabled', true).parent().fadeTo('fast', 0.4);
        } else {
            $('#radio_view_as_text').prop('disabled', false).parent().fadeTo('fast', 1);
        }
    });

    $('input[type=\'radio\'][name$=\'_structure_or_data\']').on('change', function () {
        toggleStructureDataOpts();
    });

    $('input[name="table_select[]"]').on('change', function () {
        toggleTableSelect($(this).closest('tr'));
        checkTableSelectAll();
        handleAddProcCheckbox();
        checkTableSelectStrutureOrData();
    });

    $('input[name="table_structure[]"]').on('change', function () {
        checkTableSelected($(this).closest('tr'));
        checkTableSelectAll();
        handleAddProcCheckbox();
        checkTableSelectStrutureOrData();
    });

    $('input[name="table_data[]"]').on('change', function () {
        checkTableSelected($(this).closest('tr'));
        checkTableSelectAll();
        handleAddProcCheckbox();
        checkTableSelectStrutureOrData();
    });

    $('#table_structure_all').on('change', function () {
        toggleTableSelectAllStr();
        checkSelectedTables();
        handleAddProcCheckbox();
        checkTableSelectStrutureOrData();
    });

    $('#table_data_all').on('change', function () {
        toggleTableSelectAllData();
        checkSelectedTables();
        handleAddProcCheckbox();
        checkTableSelectStrutureOrData();
    });

    if ($('input[name=\'export_type\']').val() === 'database') {
        // Hide structure or data radio buttons
        $('input[type=\'radio\'][name$=\'_structure_or_data\']').each(function () {
            var $this = $(this);
            var name = $this.prop('name');
            var val = $('input[name="' + name + '"]:checked').val();
            var nameDefault = name + '_default';
            if (!$('input[name="' + nameDefault + '"]').length) {
                $this
                    .after(
                        $('<input type="hidden" name="' + nameDefault + '" value="' + val + '" disabled>')
                    )
                    .after(
                        $('<input type="hidden" name="' + name + '" value="structure_and_data">')
                    );
                $this.parent().find('label').remove();
            } else {
                $this.parent().remove();
            }
        });
        $('input[type=\'radio\'][name$=\'_structure_or_data\']').remove();

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

        setupTableStructureOrData();
    }

    /**
     * Handle force structure_or_data
     */
    $('#plugins').on('change', setupTableStructureOrData);
}

function onloadExportSubmit () {
    $('input[type=\'radio\'][name=\'quick_or_custom\']').on('change', toggleQuickOrCustom);

    $('#scroll_to_options_msg').hide();
    $('#format_specific_opts').find('div.format_specific_options')
        .hide()
        .css({
            'border': 0,
            'margin': 0,
            'padding': 0
        })
        .find('h3')
        .remove();
    toggleQuickOrCustom();
    toggleStructureDataOpts();
    toggleSqlIncludeComments();
    checkTableSelectAll();
    handleAddProcCheckbox();

    /**
     * Initially disables the "Dump some row(s)" sub-options
     */
    disableDumpSomeRowsSubOptions();

    /**
     * Disables the "Dump some row(s)" sub-options when it is not selected
     */
    $('input[type=\'radio\'][name=\'allrows\']').on('change', function () {
        if ($('input[type=\'radio\'][name=\'allrows\']').prop('checked')) {
            enableDumpSomeRowsSubOptions();
        } else {
            disableDumpSomeRowsSubOptions();
        }
    });

    // Open Alias Modal Dialog on click
    $('#btn_alias_config').on('click', createAliasModal);
    $('.alias_remove').on('click', function () {
        $(this).parents('tr').remove();
    });
    $('#db_alias_select').on('change', function () {
        aliasToggleRow($(this));
        // var db = $(this).val();
        var table = CommonParams.get('table');
        if (table) {
            var option = $('<option></option>');
            option.text(table);
            option.attr('value', table);
            $('#table_alias_select').append(option).val(table).trigger('change');
        } else {
            var params = {
                ajax_request : true,
                server : CommonParams.get('server'),
                db : $(this).val(),
                type: 'list-tables'
            };
            $.post('ajax.php', params, function (response) {
                if (response.success === true) {
                    $.each(response.tables, function (idx, value) {
                        var option = $('<option></option>');
                        option.text(value);
                        option.attr('value', value);
                        $('#table_alias_select').append(option);
                    });
                } else {
                    PMA_ajaxShowMessage(response.error, false);
                }
            });
        }
    });
    $('#table_alias_select').on('change', function () {
        aliasToggleRow($(this));
        var params = {
            ajax_request : true,
            server : CommonParams.get('server'),
            db : $('#db_alias_select').val(),
            table: $(this).val(),
            type: 'list-columns'
        };
        $.post('ajax.php', params, function (response) {
            if (response.success === true) {
                $.each(response.columns, function (idx, value) {
                    var option = $('<option></option>');
                    option.text(value);
                    option.attr('value', value);
                    $('#column_alias_select').append(option);
                });
            } else {
                PMA_ajaxShowMessage(response.error, false);
            }
        });
    });
    $('#column_alias_select').on('change', function () {
        aliasToggleRow($(this));
    });
    $('#db_alias_button').on('click', function (e) {
        e.preventDefault();
        var db = $('#db_alias_select').val();
        addAlias(
            messages.strAliasDatabase,
            db,
            'aliases[' + db + '][alias]',
            $('#db_alias_name').val()
        );
        $('#db_alias_name').val('');
    });
    $('#table_alias_button').on('click', function (e) {
        e.preventDefault();
        var db = $('#db_alias_select').val();
        var table = $('#table_alias_select').val();
        addAlias(
            messages.strAliasTable,
            db + '.' + table,
            'aliases[' + db + '][tables][' + table + '][alias]',
            $('#table_alias_name').val()
        );
        $('#table_alias_name').val('');
    });
    $('#column_alias_button').on('click', function (e) {
        e.preventDefault();
        var db = $('#db_alias_select').val();
        var table = $('#table_alias_select').val();
        var column = $('#column_alias_select').val();
        addAlias(
            messages.strAliasColumn,
            db + '.' + table + '.' + column,
            'aliases[' + db + '][tables][' + table + '][colums][' + column + ']',
            $('#column_alias_name').val()
        );
        $('#column_alias_name').val('');
    });
}

/**
 * Module export
 */
export {
    teardownExport,
    onloadExportOptions,
    onloadExportSubmit,
    onloadExportTemplate,
    onloadSaveToFile
};
