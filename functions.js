/* $Id$ */


var isFormElementInRange;


/**
 * Ensures a value submitted in a form is numeric and is in a range
 *
 * @param   object   the form
 * @param   string   the name of the form field to check
 * @param   integer  the minimum authorized value + 1
 * @param   integer  the maximum authorized value + 1
 *
 * @return  boolean  whether a valid number has been submitted or not
 */
function checkFormElementInRange(theForm, theFieldName, min, max)
{
    isFormElementInRange = true;
    var theField         = theForm.elements[theFieldName];
    var val              = parseInt(theField.value);

    // It's not a number
    if (isNaN(val)) {
        alert(errorMsg1);
        isFormElementInRange = false;
        theField.select();
        theField.focus();
        return false;
    }
    // It's a number but it is not between min and max
    else if (val < min || val > max) {
        alert(val + errorMsg2);
        isFormElementInRange = false;
        theField.select();
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
 * Ensures the choice between 'transmit', 'gzipped' and 'bzipped' checkboxes is
 * consistant
 *
 * @param   object   the form
 * @param   string   a code for the action that causes this function to be run
 *
 * @return  boolean  always true
 */
function checkTransmitDump(theForm, theAction)
{
	var formElts = theForm.elements;

    // 'gzipped' option has been checked
    if (theAction == 'gzip' && formElts['gzip'].checked) {
        if (!formElts['asfile'].checked) {
            theForm.elements['asfile'].checked = true;
        }
        if (typeof(formElts['bzip']) != 'undefined' && formElts['bzip'].checked) {
            theForm.elements['bzip'].checked = false;
        }
    }
    // 'bzipped' option has been checked
    else if (theAction == 'bzip' && formElts['bzip'].checked) {
        if (!formElts['asfile'].checked) {
            theForm.elements['asfile'].checked = true;
        }
        if (typeof(formElts['gzip']) != 'undefined' && formElts['gzip'].checked) {
            theForm.elements['gzip'].checked = false;
        }
    }
    // 'transmit' option has been unchecked
    else if (theAction == 'transmit' && !formElts['asfile'].checked) {
        if ((typeof(formElts['gzip']) != 'undefined' && formElts['gzip'].checked)) {
            theForm.elements['gzip'].checked = false;
        }
        if ((typeof(formElts['bzip']) != 'undefined' && formElts['bzip'].checked)) {
            theForm.elements['bzip'].checked = false;
        }
    }

    return true;
} // end of the 'checkTransmitDump()' function

