/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * js file for handling AJAX and other events in db_multi_table_query.php
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('db_multi_table_query.js', function () {
    $('.tableNameSelect').each(function () {
        $(this).off('change');
    });
    $('#update_query_button').off('click');
    $('#add_column_button').off('click');
});

AJAX.registerOnload('db_multi_table_query.js', function () {
    var editor = PMA_getSQLEditor($('#MultiSqlquery'), {}, 'both');
    $('.CodeMirror-line').css('text-align', 'left');
    editor.setSize(-1, 50);

    var column_count = 3;
    PMA_init_slider();
    addNewColumnCallbacks();

    function escapeBacktick (s) {
        return s.replace('`', '``');
    }

    function escapeSingleQuote (s) {
        return s.replace('\\', '\\\\').replace('\'', '\\\'');
    }

    $('#update_query_button').on('click', function () {
        var columns = [];
        var table_aliases = {};
        $('.tableNameSelect').each(function () {
            $show = $(this).siblings('.show_col').first();
            if ($(this).val() !== '' && $show.prop('checked')) {
                var table_alias = $(this).siblings('.table_alias').first().val();
                var column_alias = $(this).siblings('.col_alias').first().val();

                if (table_alias !== '') {
                    columns.push([table_alias, $(this).siblings('.columnNameSelect').first().val()]);
                } else {
                    columns.push([$(this).val(), $(this).siblings('.columnNameSelect').first().val()]);
                }

                columns[columns.length - 1].push(column_alias);

                if ($(this).val() in table_aliases) {
                    if (!(table_aliases[$(this).val()].includes(table_alias))) {
                        table_aliases[$(this).val()].push(table_alias);
                    }
                } else {
                    table_aliases[$(this).val()] = [table_alias];
                }
            }
        });
        if (Object.keys(table_aliases).length === 0) {
            PMA_ajaxShowMessage('Nothing selected', false, 'error');
            return;
        }

        query = 'SELECT ';
        if(columns[0][1] == '*')
            query += '`' + escapeBacktick(columns[0][0]) + '`.' + escapeBacktick(columns[0][1]) + '';
        else
            query += '`' + escapeBacktick(columns[0][0]) + '`.`' + escapeBacktick(columns[0][1]) + '`';
        if (columns[0][2] !== '') {
            query += ' AS ' + columns[0][2];
        }
        for (var i = 1; i < columns.length; i++) {
            if(columns[i][1] == '*')
                query += ', `' + escapeBacktick(columns[i][0]) + '`.' + escapeBacktick(columns[i][1]) + '';
            else
                query += ', `' + escapeBacktick(columns[i][0]) + '`.`' + escapeBacktick(columns[i][1]) + '`';
            if (columns[i][2] !== '') {
                query += ' AS `' + escapeBacktick(columns[0][2]) + '`';
            }
        }
        query += '\nFROM ';
        var table_count = 0;
        for (var table in table_aliases) {
            for (var i = 0; i < table_aliases[table].length; i++) {
                if (table_count > 0) {
                    query += ', ';
                }
                query += '`' + escapeBacktick(table) + '`';
                if (table_aliases[table][i] !== '') {
                    query += ' AS `' + escapeBacktick(table_aliases[table][i]) + '`';
                }
                table_count++;
            }
        }

        $criteria_col_count = $('.criteria_col:checked').length;
        if ($criteria_col_count > 0) {
            query += '\nWHERE ';

            var logical_ops = [];

            var count = 0;

            $('.tableNameSelect').each(function () {
                $criteria_div = $(this).siblings('.slide-wrapper').first();
                $use_criteria = $(this).siblings('.criteria_col').first();
                if ($(this).val() !== '' && $use_criteria.prop('checked')) {
                    if (count > 0) {
                        $criteria_div.find('input.logical_op').each(function () {
                            if ($(this).prop('checked')) {
                                query += ' ' + $(this).val() + ' ';
                            }
                        });
                    }
                    formats_text = {
                        '=' : ' = \'%s\'',
                        '>' : ' > \'%s\'',
                        '>=' : ' >= \'%s\'',
                        '<' : ' < \'%s\'',
                        '<=' : ' <= \'%s\'',
                        '!=' : ' != \'%s\'',
                        'LIKE' : ' LIKE \'%s\'',
                        'LIKE \%...\%' : ' LIKE \'%%%s%%\'',
                        'NOT LIKE' : ' NOT LIKE \'%s\'',
                        'BETWEEN' : ' BETWEEN \'%s\'',
                        'NOT BETWEEN' : ' NOT BETWEEN \'%s\'',
                        'IS NULL' : ' \'%s\' IS NULL',
                        'IS NOT NULL' : ' \'%s\' IS NOT NULL',
                        'REGEXP' : ' REGEXP \'%s\'',
                        'REGEXP ^...$' : ' REGEXP \'^%s$\'',
                        'NOT REGEXP' : ' NOT REGEXP \'%s\''
                    };
                    query += '`' + escapeBacktick($(this).val()) + '`.';
                    query += '`' + escapeBacktick($(this).siblings('.columnNameSelect').first().val()) + '`';
                    if ($criteria_div.find('.criteria_rhs').first().val() === 'text') {
                        // query += " '" + $criteria_div.find('.rhs_text_val').first().val() + "'";
                        query += sprintf(formats_text[$criteria_div.find('.criteria_op').first().val()], escapeSingleQuote($criteria_div.find('.rhs_text_val').first().val()));
                    } else {
                        query += ' ' + $criteria_div.find('.criteria_op').first().val();
                        query += ' `' + escapeBacktick($criteria_div.find('.tableNameSelect').first().val()) + '`.';
                        query += '`' + escapeBacktick($criteria_div.find('.columnNameSelect').first().val()) + '`';
                    }
                    count++;
                }
            });
        }

        query += ';';
        editor.getDoc().setValue(query);
    });

    $('#submit_query').on('click', function () {
        var query = editor.getDoc().getValue();
        var data = {
            'db': $('#db_name').val(),
            'sql_query': query,
            'ajax_request': '1',
            'token': PMA_commonParams.get('token')
        };
        $.ajax({
            type: 'POST',
            url: 'db_multi_table_query.php',
            data: data,
            success: function (data) {
                $results_dom = $(data.message);
                $results_dom.find('.ajax:not(.pageselector)').each(function () {
                    $(this).on('click', function (event) {
                        event.preventDefault();
                    });
                });
                $results_dom.find('.autosubmit, .pageselector, .showAllRows, .filter_rows').each(function () {
                    $(this).on('change click select focus', function (event) {
                        event.preventDefault();
                    });
                });
                $('#sql_results').html($results_dom);
                $('#page_content').find('a').first().click();
            }
        });
    });

    $('#add_column_button').on('click', function () {
        column_count++;
        $new_column_dom = $($('#new_column_layout').html()).clone();
        $new_column_dom.find('div').first().find('div').first().attr('id', column_count.toString());
        $new_column_dom.find('a').first().remove();
        $new_column_dom.find('.pma_auto_slider').first().unwrap();
        $new_column_dom.find('.pma_auto_slider').first().attr('title', 'criteria');
        $('#add_column_button').parent().before($new_column_dom);
        PMA_init_slider();
        addNewColumnCallbacks();
    });

    function addNewColumnCallbacks () {
        $('.tableNameSelect').each(function () {
            $(this).on('change', function () {
                $sibs = $(this).siblings('.columnNameSelect');
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
                    $checkbox = $(this).siblings('.criteria_col').first();
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                }
                $criteria_col_count = $('.criteria_col:checked').length;
                if ($criteria_col_count > 1) {
                    $(this).siblings('.slide-wrapper').first().find('.logical_operator').first().css('display','table-row');
                }
            });
        });

        $('.criteria_col').each(function () {
            $(this).on('change', function () {
                $anchor = $(this).siblings('a.ajax').first();
                $anchor.trigger('click', ['Trigger']);
            });
        });

        $('.criteria_rhs').each(function () {
            $(this).on('change', function () {
                $rhs_col = $(this).parent().parent().siblings('.rhs_table').first();
                $rhs_text = $(this).parent().parent().siblings('.rhs_text').first();
                if ($(this).val() === 'text') {
                    $rhs_col.css('display', 'none');
                    $rhs_text.css('display', 'table-row');
                } else if ($(this).val() === 'anotherColumn') {
                    $rhs_text.css('display', 'none');
                    $rhs_col.css('display', 'table-row');
                } else {
                    $rhs_text.css('display', 'none');
                    $rhs_col.css('display', 'none');
                }
            });
        });
    }
});
