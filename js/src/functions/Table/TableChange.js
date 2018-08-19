import { $ } from '../../utils/JqueryExtended';
import { PMA_Messages as PMA_messages } from '../../variables/export_variables';

/**
 * Modify form controls when the "NULL" checkbox is checked
 *
 * @param theType     string   the MySQL field type
 * @param urlField    string   the urlencoded field name - OBSOLETE
 * @param md5Field    string   the md5 hashed field name
 * @param multi_edit  string   the multi_edit row sequence number
 *
 * @return boolean  always true
 */
export function nullify (theType, urlField, md5Field, multi_edit) {
    var rowForm = document.forms.insertForm;

    if (typeof(rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']']) !== 'undefined') {
        rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']'].selectedIndex = -1;
    }

    // "ENUM" field with more than 20 characters
    if (theType === 1) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'][1].selectedIndex = -1;
    // Other "ENUM" field
    } else if (theType === 2) {
        var elts     = rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'];
        // when there is just one option in ENUM:
        if (elts.checked) {
            elts.checked = false;
        } else {
            var elts_cnt = elts.length;
            for (var i = 0; i < elts_cnt; i++) {
                elts[i].checked = false;
            } // end for
        } // end if
    // "SET" field
    } else if (theType === 3) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  '][]'].selectedIndex = -1;
    // Foreign key field (drop-down)
    } else if (theType === 4) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'].selectedIndex = -1;
    // foreign key field (with browsing icon for foreign values)
    } else if (theType === 6) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    // Other field types
    } else /* if (theType === 5)*/ {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function

/**
 * javascript DateTime format validation.
 * its used to prevent adding default (0000-00-00 00:00:00) to database when user enter wrong values
 * Start of validation part
 */
// function checks the number of days in febuary
function daysInFebruary (year) {
    return (((year % 4 === 0) && (((year % 100 !== 0)) || (year % 400 === 0))) ? 29 : 28);
}
// function to convert single digit to double digit
function fractionReplace (num) {
    num = parseInt(num, 10);
    return num >= 1 && num <= 9 ? '0' + num : '00';
}

/* function to check the validity of date
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2001-12-23
* 2) 2001-1-2
* 3) 02-12-23
* 4) And instead of using '-' the following punctuations can be used (+,.,*,^,@,/) All these are accepted by mysql as well. Therefore no issues
*/
export function isDate (val, tmstmp) {
    val = val.replace(/[.|*|^|+|//|@]/g, '-');
    var arrayVal = val.split('-');
    for (var a = 0; a < arrayVal.length; a++) {
        if (arrayVal[a].length === 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join('-');
    var pos = 2;
    var dtexp = new RegExp(/^([0-9]{4})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30))|((00)-(00)))$/);
    if (val.length === 8) {
        pos = 0;
    }
    if (dtexp.test(val)) {
        var month = parseInt(val.substring(pos + 3, pos + 5), 10);
        var day = parseInt(val.substring(pos + 6, pos + 8), 10);
        var year = parseInt(val.substring(0, pos + 2), 10);
        if (month === 2 && day > daysInFebruary(year)) {
            return false;
        }
        if (val.substring(0, pos + 2).length === 2) {
            year = parseInt('20' + val.substring(0, pos + 2), 10);
        }
        if (tmstmp === true) {
            if (year < 1978) {
                return false;
            }
            if (year > 2038 || (year > 2037 && day > 19 && month >= 1) || (year > 2037 && month > 1)) {
                return false;
            }
        }
    } else {
        return false;
    }
    return true;
}

/* function to check the validity of time
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2:3:4
* 2) 2:23:43
* 3) 2:23:43.123456
*/
export function isTime (val) {
    var arrayVal = val.split(':');
    for (var a = 0, l = arrayVal.length; a < l; a++) {
        if (arrayVal[a].length === 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join(':');
    var tmexp = new RegExp(/^(-)?(([0-7]?[0-9][0-9])|(8[0-2][0-9])|(83[0-8])):((0[0-9])|([1-5][0-9])):((0[0-9])|([1-5][0-9]))(\.[0-9]{1,6}){0,1}$/);
    return tmexp.test(val);
}

/**
 * To check whether insert section is ignored or not
 */
function checkForCheckbox (multi_edit) {
    if ($('#insert_ignore_' + multi_edit).length) {
        return $('#insert_ignore_' + multi_edit).is(':unchecked');
    }
    return true;
}

export function verificationsAfterFieldChange (urlField, multi_edit, theType) {
    var evt = window.event || arguments.callee.caller.arguments[0];
    var target = evt.target || evt.srcElement;
    var $this_input = $(':input[name^=\'fields[multi_edit][' + multi_edit + '][' +
        urlField + ']\']');
    // the function drop-down that corresponds to this input field
    var $this_function = $('select[name=\'funcs[multi_edit][' + multi_edit + '][' +
        urlField + ']\']');
    var function_selected = false;
    if (typeof $this_function.val() !== 'undefined' &&
        $this_function.val() !== null &&
        $this_function.val().length > 0
    ) {
        function_selected = true;
    }

    // To generate the textbox that can take the salt
    var new_salt_box = '<br><input type=text name=salt[multi_edit][' + multi_edit + '][' + urlField + ']' +
        ' id=salt_' + target.id + ' placeholder=\'' + PMA_messages.strEncryptionKey + '\'>';

    // If encrypting or decrypting functions that take salt as input is selected append the new textbox for salt
    if (target.value === 'AES_ENCRYPT' ||
            target.value === 'AES_DECRYPT' ||
            target.value === 'DES_ENCRYPT' ||
            target.value === 'DES_DECRYPT' ||
            target.value === 'ENCRYPT') {
        if (!($('#salt_' + target.id).length)) {
            $this_input.after(new_salt_box);
        }
    } else {
        // Remove the textbox for salt
        $('#salt_' + target.id).prev('br').remove();
        $('#salt_' + target.id).remove();
    }

    // call validate before adding rules
    $($this_input[0].form).validate();

    if (target.value === 'AES_DECRYPT'
            || target.value === 'AES_ENCRYPT'
            || target.value === 'MD5') {
        $('#' + target.id).rules('add', {
            validationFunctionForFuns: {
                param: $this_input,
                depends: function () {
                    return checkForCheckbox(multi_edit);
                }
            }
        });
    }

    // Unchecks the corresponding "NULL" control
    $('input[name=\'fields_null[multi_edit][' + multi_edit + '][' + urlField + ']\']').prop('checked', false);

    // Unchecks the Ignore checkbox for the current row
    $('input[name=\'insert_ignore_' + multi_edit + '\']').prop('checked', false);

    var charExceptionHandling;
    if (theType.substring(0,4) === 'char') {
        charExceptionHandling = theType.substring(5,6);
    } else if (theType.substring(0,7) === 'varchar') {
        charExceptionHandling = theType.substring(8,9);
    }
    if (function_selected) {
        $this_input.removeAttr('min');
        $this_input.removeAttr('max');
        // @todo: put back attributes if corresponding function is deselected
    }

    if ($this_input.data('rulesadded') === null && ! function_selected) {
        // validate for date time
        if (theType === 'datetime' || theType === 'time' || theType === 'date' || theType === 'timestamp') {
            $this_input.rules('add', {
                validationFunctionForDateTime: {
                    param: theType,
                    depends: function () {
                        return checkForCheckbox(multi_edit);
                    }
                }
            });
        }
        // validation for integer type
        if ($this_input.data('type') === 'INT') {
            var mini = parseInt($this_input.attr('min'));
            var maxi = parseInt($this_input.attr('max'));
            $this_input.rules('add', {
                number: {
                    param : true,
                    depends: function () {
                        return checkForCheckbox(multi_edit);
                    }
                },
                min: {
                    param: mini,
                    depends: function () {
                        if (isNaN($this_input.val())) {
                            return false;
                        } else {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                },
                max: {
                    param: maxi,
                    depends: function () {
                        if (isNaN($this_input.val())) {
                            return false;
                        } else {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                }
            });
            // validation for CHAR types
        } else if ($this_input.data('type') === 'CHAR') {
            var maxlen = $this_input.data('maxlength');
            if (typeof maxlen !== 'undefined') {
                if (maxlen <= 4) {
                    maxlen = charExceptionHandling;
                }
                $this_input.rules('add', {
                    maxlength: {
                        param: maxlen,
                        depends: function () {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                });
            }
            // validate binary & blob types
        } else if ($this_input.data('type') === 'HEX') {
            $this_input.rules('add', {
                validationFunctionForHex: {
                    param: true,
                    depends: function () {
                        return checkForCheckbox(multi_edit);
                    }
                }
            });
        }
        $this_input.data('rulesadded', true);
    } else if ($this_input.data('rulesadded') === true && function_selected) {
        // remove any rules added
        $this_input.rules('remove');
        // remove any error messages
        $this_input
            .removeClass('error')
            .removeAttr('aria-invalid')
            .siblings('.error')
            .remove();
        $this_input.data('rulesadded', null);
    }
}

window.verificationsAfterFieldChange = verificationsAfterFieldChange;
/* End of fields validation*/
