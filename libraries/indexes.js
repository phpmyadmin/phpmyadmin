/* $Id$ */


/**
 * Ensures a value submitted in a form is numeric and is in a range
 *
 * @param   object   the form
 * @param   string   the name of the form field to check
 * @param   integer  the minimum authorized value
 * @param   integer  the maximum authorized value
 *
 * @return  boolean  whether a valid number has been submitted or not
 */
function checkFormElementInRange(theForm, theFieldName, min, max)
{
    var theField         = theForm.elements[theFieldName];
    var val              = parseInt(theField.value);

    if (typeof(min) == 'undefined') {
        min = 0;
    }
    if (typeof(max) == 'undefined') {
        max = Number.MAX_VALUE;
    }

    // It's not a number
    if (isNaN(val)) {
        theField.select();
        alert(errorMsg1);
        theField.focus();
        return false;
    }
    // It's a number but it is not between min and max
    else if (val < min || val > max) {
        theField.select();
        alert(val + errorMsg2);
        theField.focus();
        return false;
    }
    // It's a valid number
    else {
        theField.value = val;
    }

    return true;
} // end of the 'checkFormElementInRange()' function


/**
 * Ensures indexes names are valid according to their type and, for a primary
 * key, lock index name to 'PRIMARY'
 *
 * @return  boolean  false if there is no index form, true else
 */
function checkIndexName()
{
    if (typeof(document.forms['index_frm']) == 'undefined') {
        return false;
    }

    // Gets the elements pointers
    var the_idx_name = document.forms['index_frm'].elements['index'];
    var the_idx_type = document.forms['index_frm'].elements['index_type'];

    // Index is a primary key
    if (the_idx_type.options[0].value == 'PRIMARY' && the_idx_type.options[0].selected) {
        document.forms['index_frm'].elements['index'].value = 'PRIMARY';
        if (typeof(the_idx_name.disabled) != 'undefined') {
            document.forms['index_frm'].elements['index'].disabled = true;
        }
    }

    // Other cases
    else {
        if (the_idx_name.value == 'PRIMARY') {
            document.forms['index_frm'].elements['index'].value = '';
        }
        if (typeof(the_idx_name.disabled) != 'undefined') {
            document.forms['index_frm'].elements['index'].disabled = false;
        }
    }

    return true;
} // end of the 'checkIndexName()' function


onload = checkIndexName;
