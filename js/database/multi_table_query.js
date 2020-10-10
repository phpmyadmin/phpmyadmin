/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 * @requires    js/database/query_generator.js
 *
 */

/* global generateFromBlock, generateWhereBlock */ // js/database/query_generator.js

/**
 * js file for handling AJAX and other events in db_multi_table_query.php
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('database/multi_table_query.js', function () {
    $('.tableNameSelect').each(function () {
        $(this).off('change');
    });
    $('#update_query_button').off('click');
    $('#add_column_button').off('click');
});

AJAX.registerOnload('database/multi_table_query.js', function () {
    var editor = Functions.getSqlEditor($('#MultiSqlquery'), {}, 'both');
    $('.CodeMirror-line').css('text-align', 'left');
    editor.setSize(-1, 50);

    var columnCount = 3;
    Functions.initSlider();
    addNewColumnCallbacks();

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

                if ($(this).val() in tableAliases) {
                    if (!(tableAliases[$(this).val()].includes(tableAlias))) {
                        tableAliases[$(this).val()].push(tableAlias);
                    }
                } else {
                    tableAliases[$(this).val()] = [tableAlias];
                }
            }
        });
        if (Object.keys(tableAliases).length === 0) {
            Functions.ajaxShowMessage('Nothing selected', false, 'error');
            return;
        }

        var foreignKeys;
        $.ajax({
            type: 'GET',
            async: false,
            url: 'db_multi_table_query.php',
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

        var query = 'SELECT ' + '`' + Functions.escapeBacktick(columns[0][0]) + '`.';
        if (columns[0][1] === '*') {
            query += '*';
        } else {
            query += '`' + Functions.escapeBacktick(columns[0][1]) + '`';
        }
        if (columns[0][2] !== '') {
            query += ' AS `' + Functions.escapeBacktick(columns[0][2]) + '`';
        }
        for (var i = 1; i < columns.length; i++) {
            query += ', `' + Functions.escapeBacktick(columns[i][0]) + '`.';
            if (columns[i][1] === '*') {
                query += '*';
            } else {
                query += '`' + Functions.escapeBacktick(columns[i][1]) + '`';
            }
            if (columns[i][2] !== '') {
                query += ' AS `' + Functions.escapeBacktick(columns[i][2]) + '`';
            }
        }
        query += '\nFROM ';

        query += generateFromBlock(tableAliases, foreignKeys);

        var $criteriaColCount = $('.criteria_col:checked').length;
        if ($criteriaColCount > 0) {
            query += '\nWHERE ';
            query += generateWhereBlock();
        }

        query += ';';
        editor.getDoc().setValue(query);
    });

    $('#submit_query').on('click', function () {
        var query = editor.getDoc().getValue();
        // Verifying that the query is not empty
        if (query === '') {
            Functions.ajaxShowMessage(Messages.strEmptyQuery, false, 'error');
            return;
        }
        var data = {
            'db': $('#db_name').val(),
            'sql_query': query,
            'ajax_request': '1',
            'token': CommonParams.get('token')
        };
        $.ajax({
            type: 'POST',
            url: 'db_multi_table_query.php',
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
                $('#sql_results').html($resultsDom);
                $('#page_content').find('a').first().trigger('click');
            }
        });
    });

    $('#add_column_button').on('click', function () {
        columnCount++;
        var $newColumnDom = $($('#new_column_layout').html()).clone();
        $newColumnDom.find('div').first().find('div').first().attr('id', columnCount.toString());
        $newColumnDom.find('a').first().remove();
        $newColumnDom.find('.pma_auto_slider').first().unwrap();
        $newColumnDom.find('.pma_auto_slider').first().attr('title', 'criteria');
        $('#add_column_button').parent().before($newColumnDom);
        Functions.initSlider();
        addNewColumnCallbacks();
    });

    function addNewColumnCallbacks () {
        $('.tableNameSelect').each(function () {
            $(this).on('change', function () {
                var $sibs = $(this).siblings('.columnNameSelect');
                if ($sibs.length === 0) {
                    $sibs = $(this).parent().parent().find('.columnNameSelect');
                }
                $sibs.first().html($('#' + $.md5($(this).val())).html());
            });
        });

        $('.removeColumn').each(function () {
            $(this).on('click', function () {
                $(this).parent().remove();
            });
        });

        $('a.ajax').each(function () {
            $(this).on('click', function (event, from) {
                if (from === null) {
                    var $checkbox = $(this).siblings('.criteria_col').first();
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                }
                var $criteriaColCount = $('.criteria_col:checked').length;
                if ($criteriaColCount > 1) {
                    $(this).siblings('.slide-wrapper').first().find('.logical_operator').first().css('display','table-row');
                }
            });
        });

        $('.criteria_col').each(function () {
            $(this).on('change', function () {
                var $anchor = $(this).siblings('a.ajax').first();
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
