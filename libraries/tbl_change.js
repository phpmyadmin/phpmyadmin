/* $Id$ */


/**
 * Modify from controls when the "NULL" checkbox is selected
 *
 * @param   string   the MySQL field type
 * @param   string   the urlencoded field name
 * @param   string   the md5 hashed field name
 *
 * @return  boolean  always true
 */
function nullify(theType, urlField, md5Field)
{
    var rowForm = document.forms['insertForm'];

    if (typeof(rowForm.elements['funcs[' + urlField + ']']) != 'undefined') {
        rowForm.elements['funcs[' + urlField + ']'].selectedIndex = -1;
    }

    // "SET" field or "ENUM" field with more than 20 characters
    if (theType == 1 || theType == 3) {
        rowForm.elements['field_' + md5Field + '[]'].selectedIndex = -1;
    }
    // Other "ENUM" field
    else if (theType == 2) {
        var elts     = rowForm.elements['field_' + md5Field + '[]'];
        var elts_cnt = elts.length;
        for (var i = 0; i < elts_cnt; i++ ) {
            elts[i].checked = false;
        } // end for
    }
    // Other field types
    else /*if (theType == 4)*/ {
        rowForm.elements['fields[' + urlField + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * Unchecks the "NULL" control when a function has been selected or a value
 * entered
 *
 * @param   string   the urlencoded field name
 *
 * @return  boolean  always true
 */
function unNullify(urlField)
{
    var rowForm = document.forms['insertForm'];

    if (typeof(rowForm.elements['fields_null[' + urlField + ']']) != 'undefined') {
        rowForm.elements['fields_null[' + urlField + ']'].checked = false
    } // end if

    return true;
} // end of the 'unNullify()' function
