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
function checkFormElementInRange(theForm, theFieldName, min, max )
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
    else if (val < min || val > max)  {
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
