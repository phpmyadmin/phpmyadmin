import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxShowMessage } from '../modules/ajax-message.ts';

/**
 * @fileoverview   events handling from central columns page
 * @name            Central columns
 *
 * @requires    jQuery
 */

/**
 * AJAX scripts for /database/central-columns
 *
 * Actions ajaxified here:
 * Inline Edit and save of a result row
 * Delete a row
 * Multiple edit and delete option
 *
 */

AJAX.registerTeardown('database/central_columns.js', function () {
    $('.edit').off('click');
    $('.edit_save_form').off('click');
    $('.edit_cancel_form').off('click');
    $('.del_row').off('click');
    $(document).off('keyup', '.filter_rows');
    $('.edit_cancel_form').off('click');
    $('#table-select').off('change');
    $('#column-select').off('change');
    $('#add_col_div').find('>a').off('click');
    $('#add_new').off('submit');
    $('#multi_edit_central_columns').off('submit');
    $('select.default_type').off('change');
    $('button[name=\'delete_central_columns\']').off('click');
    $('button[name=\'edit_central_columns\']').off('click');
});

AJAX.registerOnload('database/central_columns.js', function () {
    $('#tableslistcontainer input,#tableslistcontainer select,#tableslistcontainer .default_value,#tableslistcontainer .open_enum_editor').hide();
    $('#tableslistcontainer').find('.checkall').show();
    $('#tableslistcontainer').find('.checkall_box').show();
    if ($('#table_columns').find('tbody tr').length > 0) {
        $('#table_columns').tablesorter({
            headers: {
                0: { sorter: false },
                1: { sorter: false }, // hidden column
                4: { sorter: 'integer' }
            }
        });
    }

    $('#tableslistcontainer').find('button[name="delete_central_columns"]').on('click', function (event) {
        event.preventDefault();
        const multiDeleteColumns = $('.checkall:checkbox:checked').serialize();
        if (multiDeleteColumns === '') {
            ajaxShowMessage(window.Messages.strRadioUnchecked);

            return false;
        }

        ajaxShowMessage();
        $('#del_col_name').val(multiDeleteColumns);
        $('#del_form').trigger('submit');
    });

    $('#tableslistcontainer').find('button[name="edit_central_columns"]').on('click', function (event) {
        event.preventDefault();
        const editColumnList = $('.checkall:checkbox:checked').serialize();
        if (editColumnList === '') {
            ajaxShowMessage(window.Messages.strRadioUnchecked);

            return false;
        }

        const argsep = CommonParams.get('arg_separator');
        const editColumnData = editColumnList + '' + argsep + 'edit_central_columns_page=true' + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true' + argsep + 'db=' + encodeURIComponent(CommonParams.get('db')) + argsep + 'server=' + CommonParams.get('server');
        ajaxShowMessage();
        AJAX.source = $(this);
        $.post('index.php?route=/database/central-columns', editColumnData, AJAX.responseHandler);
    });

    $('#multi_edit_central_columns').on('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const argsep = CommonParams.get('arg_separator');
        const multiColumnEditData = $('#multi_edit_central_columns').serialize() + argsep + 'multi_edit_central_column_save=true' + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true' + argsep + 'db=' + encodeURIComponent(CommonParams.get('db')) + argsep + 'server=' + CommonParams.get('server');
        ajaxShowMessage();
        AJAX.source = $(this);
        $.post('index.php?route=/database/central-columns', multiColumnEditData, AJAX.responseHandler);
    });

    $('#add_new').find('td').each(function () {
        if ($(this).attr('name') !== 'undefined') {
            $(this).find('input,select').first().attr('name', $(this).attr('name'));
        }
    });

    $('#field_0_0').attr('required', 'required');
    $('#add_new input[type="text"], #add_new input[type="number"], #add_new select')
        .css({
            'width': '10em',
            'box-sizing': 'border-box'
        });

    window.scrollTo(0, 0);
    $(document).on('keyup', '.filter_rows', function () {
        // get the column names
        const cols = $('th.column_heading').map(function () {
            return $(this).text().trim();
        }).get();
        $.uiTableFilter($('#table_columns'), $(this).val(), cols, null, 'td span');
    });

    $('.edit').on('click', function () {
        const rownum = $(this).parent().data('rownum');
        $('#save_' + rownum).show();
        $(this).hide();
        $('#f_' + rownum + ' td span').hide();
        $('#f_' + rownum + ' input, #f_' + rownum + ' select, #f_' + rownum + ' .open_enum_editor').show();
        const attributeVal = $('#f_' + rownum + ' td[name=col_attribute] span').html();
        $('#f_' + rownum + ' select[name=field_attribute\\[' + rownum + '\\] ] option[value="' + attributeVal + '"]').attr('selected', 'selected');
        if ($('#f_' + rownum + ' .default_type').val() === 'USER_DEFINED') {
            $('#f_' + rownum + ' .default_type').siblings('.default_value').show();
        } else {
            $('#f_' + rownum + ' .default_type').siblings('.default_value').hide();
        }
    });

    $('.del_row').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $td = $(this);
        const question = window.Messages.strDeleteCentralColumnWarning;
        $td.confirm(question, null, function () {
            const rownum = $td.data('rownum');
            $('#del_col_name').val('selected_fld%5B%5D=' + $('#checkbox_row_' + rownum).val());
            $('#del_form').trigger('submit');
        });
    });

    $('.edit_cancel_form').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const rownum = $(this).data('rownum');
        $('#save_' + rownum).hide();
        $('#edit_' + rownum).show();
        $('#f_' + rownum + ' td span').show();
        $('#f_' + rownum + ' input, #f_' + rownum + ' select,#f_' + rownum + ' .default_value, #f_' + rownum + ' .open_enum_editor').hide();
        $('#tableslistcontainer').find('.checkall').show();
    });

    $('.edit_save_form').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const rownum = $(this).data('rownum');
        $('#f_' + rownum + ' td').each(function () {
            if ($(this).attr('name') !== 'undefined') {
                $(this).find(':input[type!="hidden"],select').first()
                    .attr('name', $(this).attr('name'));
            }
        });

        if ($('#f_' + rownum + ' .default_type').val() === 'USER_DEFINED') {
            $('#f_' + rownum + ' .default_type').attr('name', 'col_default_sel');
        } else {
            $('#f_' + rownum + ' .default_value').attr('name', 'col_default_val');
        }

        const datastring = $('#f_' + rownum + ' :input').serialize();
        $.ajax({
            type: 'POST',
            url: 'index.php?route=/database/central-columns',
            data: datastring + CommonParams.get('arg_separator') + 'ajax_request=true',
            dataType: 'json',
            success: function (data) {
                if (data.message !== '1') {
                    ajaxShowMessage(
                        '<div class="alert alert-danger" role="alert">' +
                        data.message +
                        '</div>',
                        false
                    );
                } else {
                    $('#f_' + rownum + ' td input[id=checkbox_row_' + rownum + ']').val($('#f_' + rownum + ' input[name=col_name]').val()).html();
                    $('#f_' + rownum + ' td[name=col_name] span').text(($('#f_' + rownum + ' input[name=col_name]').val() as string)).html();
                    $('#f_' + rownum + ' td[name=col_type] span').text(($('#f_' + rownum + ' select[name=col_type]').val() as string)).html();
                    $('#f_' + rownum + ' td[name=col_length] span').text(($('#f_' + rownum + ' input[name=col_length]').val() as string)).html();
                    $('#f_' + rownum + ' td[name=collation] span').text(($('#f_' + rownum + ' select[name=collation]').val() as string)).html();
                    $('#f_' + rownum + ' td[name=col_attribute] span').text(($('#f_' + rownum + ' select[name=col_attribute]').val() as string)).html();
                    $('#f_' + rownum + ' td[name=col_isNull] span').text($('#f_' + rownum + ' input[name=col_isNull]').is(':checked') ? 'Yes' : 'No').html();
                    $('#f_' + rownum + ' td[name=col_extra] span').text($('#f_' + rownum + ' input[name=col_extra]').is(':checked') ? 'auto_increment' : '').html();
                    $('#f_' + rownum + ' td[name=col_default] span').text(($('#f_' + rownum + ' :input[name=col_default]').val() as string)).html();
                }

                $('#save_' + rownum).hide();
                $('#edit_' + rownum).show();
                $('#f_' + rownum + ' td span').show();
                $('#f_' + rownum + ' input, #f_' + rownum + ' select,#f_' + rownum + ' .default_value, #f_' + rownum + ' .open_enum_editor').hide();
                $('#tableslistcontainer').find('.checkall').show();
            },
            error: function () {
                ajaxShowMessage(
                    '<div class="alert alert-danger" role="alert">' +
                    window.Messages.strErrorProcessingRequest +
                    '</div>',
                    false
                );
            }
        });
    });

    $('#table-select').on('change', function () {
        const selectValue = $(this).val();
        const defaultColumnSelect = $('#column-select').find('option').first();
        const href = 'index.php?route=/database/central-columns/populate';
        const params = {
            'ajax_request': true,
            'server': CommonParams.get('server'),
            'db': CommonParams.get('db'),
            'selectedTable': selectValue,
        };
        $('#column-select').html('<option value="">' + window.Messages.strLoading + '</option>');
        if (selectValue !== '') {
            $.post(href, params, function (data) {
                $('#column-select').empty().append(defaultColumnSelect);
                $('#column-select').append(data.message);
            });
        }
    });

    $('#add_column').on('submit', function (e) {
        const selectvalue = $('#column-select').val();
        if (selectvalue === '') {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    $('#add_col_div').find('>a').on('click', function () {
        $('#add_new').slideToggle('slow');
        const $addColDivLinkSpan = $('#add_col_div').find('>a span');
        if ($addColDivLinkSpan.html() === '+') {
            $addColDivLinkSpan.html('-');
        } else {
            $addColDivLinkSpan.html('+');
        }
    });

    $('#add_new').on('submit', function () {
        $('#add_new').toggle();
    });

    $('#tableslistcontainer').find('select.default_type').on('change', function () {
        if ($(this).val() === 'USER_DEFINED') {
            $(this).siblings('.default_value').attr('name', 'col_default');
            $(this).attr('name', 'col_default_sel');
        } else {
            $(this).attr('name', 'col_default');
            $(this).siblings('.default_value').attr('name', 'col_default_val');
        }
    });
});
