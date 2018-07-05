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
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('check_constraint.js', function () {
    $('.tableNameSelect').each(function () {
        $(this).off('change');
    });
    $('#add_column_button').off('click');
});

AJAX.registerOnload('check_constraint.js', function () {
    var column_count = 1;
    $('#add_column_button').on('click', function () {
        column_count++;
        $new_column_dom = $($('#new_column_layout').html()).clone();
        $new_column_dom.find('div').first().find('div').first().attr('id', column_count.toString());
        $new_column_dom.find('.pma_auto_slider').first().unwrap();
        $new_column_dom.find('.pma_auto_slider').first().attr('title', 'criteria');
        if(column_count === 1) {
            $new_column_dom.find('tr.logical_operator').remove();
        }
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
        if(column_count === 1) {
            $('.column_details:eq(1)').find('tr.logical_operator').remove();
        }
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
});
