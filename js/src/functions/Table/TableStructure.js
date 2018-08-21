/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import CommonParams from '../../variables/common_params';

/**
 * Reload fields table
 */
export function reloadFieldForm () {
    $.post($('#fieldsForm').attr('action'), $('#fieldsForm').serialize() + CommonParams.get('arg_separator') + 'ajax_request=true', function (form_data) {
        var $temp_div = $('<div id=\'temp_div\'><div>').append(form_data.message);
        $('#fieldsForm').replaceWith($temp_div.find('#fieldsForm'));
        $('#addColumns').replaceWith($temp_div.find('#addColumns'));
        $('#move_columns_dialog').find('ul').replaceWith($temp_div.find('#move_columns_dialog ul'));
        $('#moveColumns').removeClass('move-active');
    });
    $('#page_content').show();
}

export function checkFirst () {
    if ($('select[name=after_field] option:selected').data('pos') === 'first') {
        $('input[name=field_where]').val('first');
    } else {
        $('input[name=field_where]').val('after');
    }
}
