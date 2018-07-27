import PMA_commonParams from '../../variables/common_params';
import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
/**
 * Check if a form's element is empty.
 * An element containing only spaces is also considered empty
 *
 * @param object   the form
 * @param string   the name of the form field to put the focus on
 *
 * @return boolean  whether the form field is empty or not
 */
function emptyCheckTheField (theForm, theFieldName) {
    var theField = theForm.elements[theFieldName];
    var space_re = new RegExp('\\s+');
    return theField.value.replace(space_re, '') === '';
} // end of the 'emptyCheckTheField()' function

export function checkTableEditForm (theForm, fieldsCnt) {
    // TODO: avoid sending a message if user just wants to add a line
    // on the form but has not completed at least one field name

    var atLeastOneField = 0;
    var i;
    var elm;
    var elm2;
    var elm3;
    var val;
    var id;

    for (i = 0; i < fieldsCnt; i++) {
        id = '#field_' + i + '_2';
        elm = $(id);
        val = elm.val();
        if (val === 'VARCHAR' || val === 'CHAR' || val === 'BIT' || val === 'VARBINARY' || val === 'BINARY') {
            elm2 = $('#field_' + i + '_3');
            val = parseInt(elm2.val(), 10);
            elm3 = $('#field_' + i + '_1');
            if (isNaN(val) && elm3.val() !== '') {
                elm2.select();
                alert(PMA_messages.strEnterValidLength);
                elm2.focus();
                return false;
            }
        }

        if (atLeastOneField === 0) {
            id = 'field_' + i + '_1';
            if (!emptyCheckTheField(theForm, id)) {
                atLeastOneField = 1;
            }
        }
    }
    if (atLeastOneField === 0) {
        var theField = theForm.elements.field_0_1;
        alert(PMA_messages.strFormEmpty);
        theField.focus();
        return false;
    }

    // at least this section is under jQuery
    var $input = $('input.textfield[name=\'table\']');
    if ($input.val() === '') {
        alert(PMA_messages.strFormEmpty);
        $input.focus();
        return false;
    }

    return true;
} // enf of the 'checkTableEditForm()' function

/**
 * check for reserved keyword column name
 *
 * @param jQuery Object $form Form
 *
 * @returns true|false
 */

export function PMA_checkReservedWordColumns ($form) {
    var is_confirmed = true;
    $.ajax({
        type: 'POST',
        url: 'tbl_structure.php',
        data: $form.serialize() + PMA_commonParams.get('arg_separator') + 'reserved_word_check=1',
        success: function (data) {
            if (typeof data.success !== 'undefined' && data.success === true) {
                is_confirmed = confirm(data.message);
            }
        },
        async:false
    });
    return is_confirmed;
}
