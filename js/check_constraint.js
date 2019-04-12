/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in add check constraint for table
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('check_constraint.js', function () {
    $('.tableNameSelect').each(function () {
        $(this).off('change');
    });
    $(document).off('click', '#add_column_button');
    $(document).off('click', '.removeColumn');
    $(document).off('click', 'a.drop_constraint_anchor.ajax');
    $(document).off('click', '.criteria_rhs');
    $(document).off('click', '.criteria_col');
    $(document).off('click', 'a.edit_constraint_anchor.ajax');
    $(document).off('submit', '#constraint_frm');
});

AJAX.registerOnload('check_constraint.js', function () {
    var column_count = 1;
    $(document).on('click', '#add_column_button', function () {
        column_count++;
        $new_column_dom = $($('#new_column_layout').html()).clone();
        $new_column_dom.find('div').first().find('div').first().attr('id', column_count.toString());
        $new_column_dom.find('.pma_auto_slider').first().unwrap();
        $new_column_dom.find('.pma_auto_slider').first().attr('title', 'criteria');
        $('.column_details:eq(1)').find('tr.logical_operator').remove();
        $('#add_column_button').parent().before($new_column_dom);
        PMA_init_slider();
    });
    $(document).on('change', '.tableNameSelect', function () {
        $sibs = $(this).siblings('.columnNameSelect');
        if ($sibs.length === 0) {
            $sibs = $(this).parent().parent().find('.columnNameSelect');
        }
        $sibs.first().html($('#' + $.md5($(this).val())).html());
    });

    $(document).on('click', '.removeColumn', function () {
        $(this).parent().remove();
        column_count--;
        $('.column_details:eq(1)').find('tr.logical_operator').remove();
    });

    $(document).on('click', 'a.ajax', function (event, from) {
        if (from === null) {
            $checkbox = $(this).siblings('.criteria_col').first();
            $checkbox.prop('checked', !$checkbox.prop('checked'));
        }
        $criteria_col_count = $('.criteria_col:checked').length;
        if ($criteria_col_count > 1) {
            $(this).siblings('.slide-wrapper').first().find('.logical_operator').first().css('display','table-row');
        }
    });

    $(document).on('change', '.criteria_col', function () {
        $anchor = $(this).siblings('a.ajax').first();
        $anchor.trigger('click', ['Trigger']);
    });

    $(document).on('change', '.criteria_rhs', function () {
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
    /**
     * Ajax Event handler for 'Drop check constraint'
     */
    $(document).on('click', 'a.drop_constraint_anchor.ajax', function (event) {
        event.preventDefault();
        var $anchor = $(this);
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

        var question = escapeHtml(
            $curr_row.children('td')
                .children('.drop_constraint_msg')
                .val()
        );

        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(PMA_messages.strDroppingCheckConstraint, false);
            var params = getJSConfirmCommonParam(this, $anchor.getPostData());
            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    var $table_ref = $rows_to_hide.closest('table');
                    if ($rows_to_hide.length === $table_ref.find('tbody > tr').length) {
                        // We are about to remove all rows from the table
                        $table_ref.hide('medium', function () {
                            $('div.no_constraints_defined').show('medium');
                            $rows_to_hide.remove();
                        });
                        $table_ref.siblings('div.notice').hide('medium');
                    } else {
                        // We are removing some of the rows only
                        $rows_to_hide.hide('medium', function () {
                            $(this).remove();
                        });
                    }
                    if ($('.result_query').length) {
                        $('.result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div class="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#structure_content');
                        PMA_highlightSQL($('#page_content'));
                    }
                    PMA_reloadNavigation();
                    PMA_ajaxShowMessage(data.message, false);
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            });
        });
    });

    function ProcessEditForm () {
        if ($('#hidden_fields_old').length > 0) {
            $name = $('#constName').val();
            $columns = JSON.parse($('#constcolumns').val());
            $logical_op = JSON.parse($('#constLogicalOps').val());
            $criteria_op = JSON.parse($('#constCriteriaOps').val());
            $criteria_rhs = JSON.parse($('#constCriteriaRhs').val());
            $criteria_val = JSON.parse($('#constCriteriaVal').val());
            $tableNameSelect = JSON.parse($('#constTblName').val());
            $columnNameSelect = JSON.parse($('#constColName').val());
            $('#const_name').val($name);
            $('.column_details').each(function (i) {
                if (i > 0) {
                    $(this).find('.columnName').val($columns[i]);
                    $(this).find('.rhs_text_val').val($criteria_val[i]);
                    $(this).find('.criteria_op').val($criteria_op[i]);
                    $(this).find('.criteria_rhs').val($criteria_rhs[i]);
                    $(this).find('.tableNameSelect').val($tableNameSelect[i]);
                    $(this).find('.columnNameSelect').val($columnNameSelect[i]);
                    if (i > 1) {
                        $(this).find('.logical_op').val($logical_op[i - 2]);
                    }
                }
            });
        }
    }

    /**
     * Ajax Event handler for 'Edit check constraint'
     */
    $(document).on('click', 'a.edit_constraint_anchor.ajax', function (event) {
        event.preventDefault();
        var $anchor = $(this);
        var params =  $anchor.getPostData();
        $.post($anchor.attr('href'), params, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                if (data.message && data.message.length > 0) {
                    $('#page_content').replaceWith(
                        '<div id=\'page_content\'>' + data.message + '</div>'
                    );
                }
                ProcessEditForm();
            } else {
                PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + ' : ' + data.error, false);
            }
        });
    });

    /**
     * Ajax Event handler for Submitting constraint form
     */
    $(document).on('submit', '#constraint_frm', function (event) {
        event.preventDefault();
        var argsep = PMA_commonParams.get('arg_separator');
        var params = 'ajax_request=true' + argsep + 'ajax_page_request=true' + argsep + 'do_save_data=1';
        params += argsep + $(this).serialize();
        $.post($(this).attr('action'), params, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                window.location.reload();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });
});
