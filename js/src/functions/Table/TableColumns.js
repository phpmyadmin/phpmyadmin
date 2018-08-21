/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { $ } from '../../utils/JqueryExtended';

/**
 * Hides/shows the "Open in ENUM/SET editor" message, depending on the data type of the column currently selected
 */
function PMA_showNoticeForEnum (selectElement) {
    var enum_notice_id = selectElement.attr('id').split('_')[1];
    enum_notice_id += '_' + (parseInt(selectElement.attr('id').split('_')[2], 10) + 1);
    var selectedType = selectElement.val();
    if (selectedType === 'ENUM' || selectedType === 'SET') {
        $('p#enum_notice_' + enum_notice_id).show();
    } else {
        $('p#enum_notice_' + enum_notice_id).hide();
    }
}

/**
 * Hides/shows the default value input field, depending on the default type
 * Ticks the NULL checkbox if NULL is chosen as default value.
 */
function PMA_hideShowDefaultValue ($default_type) {
    if ($default_type.val() === 'USER_DEFINED') {
        $default_type.siblings('.default_value').show().focus();
    } else {
        $default_type.siblings('.default_value').hide();
        if ($default_type.val() === 'NULL') {
            var $null_checkbox = $default_type.closest('tr').find('.allow_null');
            $null_checkbox.prop('checked', true);
        }
    }
}

/**
 * Hides/shows the input field for column expression based on whether
 * VIRTUAL/PERSISTENT is selected
 *
 * @param $virtuality virtuality dropdown
 */
function PMA_hideShowExpression ($virtuality) {
    if ($virtuality.val() === '') {
        $virtuality.siblings('.expression').hide();
    } else {
        $virtuality.siblings('.expression').show();
    }
}

/**
 * Show notices for ENUM columns; add/hide the default value
 *
 */
export function PMA_verifyColumnsProperties () {
    $('select.column_type').each(function () {
        PMA_showNoticeForEnum($(this));
    });
    $('select.default_type').each(function () {
        PMA_hideShowDefaultValue($(this));
    });
    $('select.virtuality').each(function () {
        PMA_hideShowExpression($(this));
    });
}

/**
 * If the chosen storage engine is FEDERATED show connection field. Hide otherwise
 *
 * @param $engine_selector storage engine selector
 */
export function PMA_hideShowConnection ($engine_selector) {
    var $connection = $('.create_table_form input[name=connection]');
    var index = $connection.parent('td').index() + 1;
    var $labelTh = $connection.parents('tr').prev('tr').children('th:nth-child(' + index + ')');
    if ($engine_selector.val() !== 'FEDERATED') {
        $connection
            .prop('disabled', true)
            .parent('td').hide();
        $labelTh.hide();
    } else {
        $connection
            .prop('disabled', false)
            .parent('td').show();
        $labelTh.show();
    }
}
