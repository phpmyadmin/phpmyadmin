import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { copyToClipboard, displayCopyNotification, getSqlEditor } from '../modules/functions.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxShowMessage } from '../modules/ajax-message.ts';
import { escapeBacktick } from '../modules/functions/escape.ts';

/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQueryUI
 * @requires    js/database/query_generator.js
 */

/**
 * js file for handling AJAX and other events in /database/multi-table-query
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('database/multi_table_query.js', function () {
    $('.tableNameSelect').each(function () {
        $(this).off('change');
    });

    $('.columnNameSelect').each(function () {
        $(this).off('change');
    });

    $('.criteria_op').each(function () {
        $(this).off('change');
    });

    $('#update_query_button').off('click');
    $('#copy_query').off('click');
    $('#add_column_button').off('click');
    $('body').off('click', 'input.add-option');
    $('body').off('click', 'input.remove-option');
});

AJAX.registerOnload('database/multi_table_query.js', function () {
    var editor = getSqlEditor($('#MultiSqlquery'), {}, 'vertical');
    $('.CodeMirror-line').css('text-align', 'left');
    editor.setSize(-1, -1);

    var columnCount = 3;
    addNewColumnCallbacks();

    function opsWithMultipleArgs (): string[] {
        return ['IN (...)', 'NOT IN (...)'];
    }

    function opsWithTwoArgs (): string[] {
        return ['BETWEEN', 'NOT BETWEEN'];
    }

    $('#update_query_button').on('click', function () {
        var columns = [];
        var tableAliases = {};
        $('.tableNameSelect').each(function () {
            var $show = $(this).siblings('.show_col').first();
            if ($(this).val() !== '' && $show.prop('checked')) {
                var tableAlias = $(this).siblings('.table_alias').first().val();
                var columnAlias = $(this).siblings('.col_alias').first().val();

                if (tableAlias !== '') {
                    columns.push([tableAlias, $(this).siblings('.columnNameSelect').first().val()]);
                } else {
                    columns.push([$(this).val(), $(this).siblings('.columnNameSelect').first().val()]);
                }

                columns[columns.length - 1].push(columnAlias);

                if (($(this).val() as string) in tableAliases) {
                    if (! (tableAliases[($(this).val() as string)].includes(tableAlias))) {
                        tableAliases[($(this).val() as string)].push(tableAlias);
                    }
                } else {
                    tableAliases[($(this).val() as string)] = [tableAlias];
                }
            }
        });

        if (Object.keys(tableAliases).length === 0) {
            ajaxShowMessage('Nothing selected', false, 'error');

            return;
        }

        var foreignKeys;
        $.ajax({
            type: 'GET',
            async: false,
            url: 'index.php?route=/database/multi-table-query/tables',
            data: {
                'server': sessionStorage.server,
                'db': $('#db_name').val(),
                'tables': Object.keys(tableAliases),
                'ajax_request': '1',
                'token': CommonParams.get('token')
            },
            success: function (response) {
                foreignKeys = response.foreignKeyConstrains;
            }
        });

        var query = 'SELECT ' + '`' + escapeBacktick(columns[0][0]) + '`.';
        if (columns[0][1] === '*') {
            query += '*';
        } else {
            query += '`' + escapeBacktick(columns[0][1]) + '`';
        }

        if (columns[0][2] !== '') {
            query += ' AS `' + escapeBacktick(columns[0][2]) + '`';
        }

        for (var i = 1; i < columns.length; i++) {
            query += ', `' + escapeBacktick(columns[i][0]) + '`.';
            if (columns[i][1] === '*') {
                query += '*';
            } else {
                query += '`' + escapeBacktick(columns[i][1]) + '`';
            }

            if (columns[i][2] !== '') {
                query += ' AS `' + escapeBacktick(columns[i][2]) + '`';
            }
        }

        query += '\nFROM ';

        query += window.generateFromBlock(tableAliases, foreignKeys);

        var $criteriaColCount = $('.criteria_col:checked').length;
        if ($criteriaColCount > 0) {
            query += '\nWHERE ';
            query += window.generateWhereBlock();
        }

        query += ';';
        editor.getDoc().setValue(query);
    });

    $('#submit_query').on('click', function () {
        var query = editor.getDoc().getValue();
        // Verifying that the query is not empty
        if (query === '') {
            ajaxShowMessage(window.Messages.strEmptyQuery, false, 'error');

            return;
        }

        var data = {
            'db': $('#db_name').val(),
            'sql_query': query,
            'ajax_request': '1',
            'server': CommonParams.get('server'),
            'token': CommonParams.get('token')
        };
        $.ajax({
            type: 'POST',
            url: 'index.php?route=/database/multi-table-query/query',
            data: data,
            success: function (data) {
                var $resultsDom = $(data.message);
                $resultsDom.find('.ajax:not(.pageselector)').each(function () {
                    $(this).on('click', function (event) {
                        event.preventDefault();
                    });
                });

                $resultsDom.find('.autosubmit, .pageselector, .showAllRows, .filter_rows').each(function () {
                    $(this).on('change click select focus', function (event) {
                        event.preventDefault();
                    });
                });

                // @ts-ignore
                $('#sql_results').html($resultsDom);
                $('#slide-handle').trigger('click');// Collapse search criteria area
            }
        });
    });

    editor.getDoc().on('change', function (): void {
        const query: string = editor.getDoc().getValue();

        $('#copy_query').prop('disabled', query === '');
    });

    $('#copy_query').on('click', function (): void {
        const query: string = editor.getDoc().getValue();

        const copyStatus = copyToClipboard(query, '<textarea>');
        displayCopyNotification(copyStatus);
    });

    $('#add_column_button').on('click', function () {
        columnCount++;
        var $newColumnDom = $('<div class="col"></div>').html($('#new_column_layout').html());
        $newColumnDom.find('.jsCriteriaButton').first().attr('data-bs-target', '#criteriaOptionsExtra' + columnCount.toString());
        $newColumnDom.find('.jsCriteriaButton').first().attr('aria-controls', 'criteriaOptionsExtra' + columnCount.toString());
        $newColumnDom.find('.jsCriteriaOptions').first().attr('id', 'criteriaOptionsExtra' + columnCount.toString());
        $('#add_column_button').parent().siblings('.row').append($newColumnDom);
        addNewColumnCallbacks();
    });

    $('.columnNameSelect').each(function () {
        $(this).on('change', function () {
            const colIsStar = $(this).val() === '*';

            colIsStar && $(this).siblings('.col_alias').val('');
            $(this).siblings('.col_alias').prop('disabled', colIsStar);
        });
    });

    const acceptsMultipleArgs: string[] = opsWithMultipleArgs();
    const acceptsTwoArgs: string[] = opsWithTwoArgs();
    $('.criteria_op').each(function () {
        $(this).on('change', function () {
            if (acceptsMultipleArgs.includes($(this).val().toString())) {
                showMultiFields($(this));
            } else if (acceptsTwoArgs.includes($(this).val().toString())) {
                showTwoFields($(this));
            } else {
                const options: JQuery<HTMLElement> = $(this).closest('table').find('.options');
                options.parent().prepend('<input type="text" class="rhs_text_val query-form__input--wide" placeholder="Enter criteria as free text"></input>');
                options.remove();
            }
        });
    });

    function showTwoFields (opSelect: JQuery<HTMLElement>) {
        const critetiaRow: JQuery<HTMLElement> = opSelect.closest('table').find('.rhs_text');
        const critetiaCol: JQuery<HTMLElement> = critetiaRow.find('td').last();
        const criteriaInput: JQuery<HTMLElement> = critetiaCol.find('input').first();

        if (critetiaCol.find('.binary').length === 0) {
            critetiaCol.empty();

            critetiaCol.append(`
                <div class="options binary">
                    <input type="text" class="val" placeholder="${window.Messages.strFirstValuePlaceholder}" value="${criteriaInput.val()}" />
                    <input type="text" class="val" placeholder="${window.Messages.strSecondValuePlaceholder}" />
                </div>
            `);
        }
    }

    function showMultiFields (opSelect: JQuery<HTMLElement>) {
        const critetiaRow: JQuery<HTMLElement> = opSelect.closest('table').find('.rhs_text');
        const critetiaCol: JQuery<HTMLElement> = critetiaRow.find('td').last();
        const criteriaInput: JQuery<HTMLElement> = critetiaCol.find('input').first();

        if (critetiaCol.find('.multi').length === 0) {
            critetiaCol.empty();

            critetiaCol.append(`
                <div class="options multi">
                    <div class="option">
                        <input type="text" class="val" placeholder="Enter an option" value="${criteriaInput.val()}" />
                        <input type="button" class="btn btn-secondary add-option" value="+" />
                    </div>
                </div>
            `);
        }
    }

    $('body').on('click', 'input.add-option', function () {
        const options: JQuery<HTMLElement> = $(this).closest('.options');

        options.find('.option').first().clone().appendTo(options);

        const newAdded: JQuery<HTMLElement> = options.find('.option').last();

        newAdded.find('input.val').val('');
        newAdded.append('<input type="button" class="btn btn-secondary remove-option" value="-" />');
    });

    $('body').on('click', 'input.remove-option', function () {
        $(this).closest('.option').remove();
    });

    function addNewColumnCallbacks () {
        $('.tableNameSelect').each(function () {
            $(this).on('change', function () {
                const $table = $(this);
                const $alias = $table.siblings('.col_alias');
                const $colsSelect = $table.parent().find('.columnNameSelect');

                $alias.prop('disabled', true);

                $colsSelect.each(function () {
                    $(this).show();
                    $(this).first().html($('#' + $table.find(':selected').data('hash')).html());
                    if ($(this).hasClass('opColumn')) {
                        $(this).find('option[value="*"]').remove();
                    }
                });
            });
        });

        $('.jsRemoveColumn').each(function () {
            $(this).on('click', function () {
                $(this).parent().parent().parent().remove();
            });
        });

        $('.jsCriteriaButton').each(function () {
            $(this).on('click', function (event, from) {
                if (from === null) {
                    var $checkbox = $(this).siblings('.criteria_col').first();
                    $checkbox.prop('checked', ! $checkbox.prop('checked'));
                }

                var $criteriaColCount = $('.criteria_col:checked').length;
                if ($criteriaColCount > 1) {
                    $(this).siblings('.jsCriteriaOptions').first().find('.logical_operator').first().css('display', 'table-row');
                }
            });
        });

        $('.criteria_col').each(function () {
            $(this).on('change', function () {
                var $anchor = $(this).siblings('.jsCriteriaButton').first();
                if (
                    ($(this).is(':checked') && ! $anchor.hasClass('collapsed'))
                    || (! $(this).is(':checked') && $anchor.hasClass('collapsed'))
                ) {
                    // Do not collapse on checkbox tick as it does not make sense
                    // The user has it open and wants to tick the box
                    return;
                }

                $anchor.trigger('click', ['Trigger']);
            });
        });

        $('.criteria_rhs').each(function () {
            $(this).on('change', function () {
                var $rhsCol = $(this).parent().parent().siblings('.rhs_table').first();
                var $rhsText = $(this).parent().parent().siblings('.rhs_text').first();
                if ($(this).val() === 'text') {
                    $rhsCol.css('display', 'none');
                    $rhsText.css('display', 'table-row');
                } else if ($(this).val() === 'anotherColumn') {
                    $rhsText.css('display', 'none');
                    $rhsCol.css('display', 'table-row');
                } else {
                    $rhsText.css('display', 'none');
                    $rhsCol.css('display', 'none');
                }
            });
        });
    }
});
