/* $Id$ */


/**
 * Validates the password field in a form
 *
 * @param   object   the form
 *
 * @return  boolean  whether the field value is valid or not
 */
function checkPassword(the_form)
{
    // Did the user select 'no password'?
    if (typeof(the_form.elements['nopass']) != 'undefined' && the_form.elements['nopass'][0].checked) {
        return true;
    } else if (typeof(the_form.elements['pred_password']) != 'undefined' && (the_form.elements['pred_password'].value == 'none' || the_form.elements['pred_password'].value == 'keep')) {
        return true;
    }

    // Validates
    if (the_form.elements['pma_pw'].value == '') {
        alert(jsPasswordEmpty);
        the_form.elements['pma_pw2'].value = '';
        the_form.elements['pma_pw'].focus();
        return false;
    } else if (the_form.elements['pma_pw'].value != the_form.elements['pma_pw2'].value) {
        alert(jsPasswordNotSame);
        the_form.elements['pma_pw'].value  = '';
        the_form.elements['pma_pw2'].value = '';
        the_form.elements['pma_pw'].focus();
        return false;
    } // end if...else if

    return true;
} // end of the 'checkPassword()' function


/**
 * Validates the "add a user" form
 *
 * @return  boolean  whether the form is validated or not
 */
function checkAddUser(the_form)
{
    if (the_form.elements['pred_hostname'].value == 'userdefined' && the_form.elements['hostname'].value == '') {
        alert(jsHostEmpty);
        the_form.elements['hostname'].focus();
        return false;
    }

    if (the_form.elements['pred_username'].value == 'userdefined' && the_form.elements['username'].value == '') {
        alert(jsUserEmpty);
        the_form.elements['username'].focus();
        return false;
    }

    return checkPassword(the_form);
} // end of the 'checkAddUser()' function


/**
 * Checks/unchecks all checkboxes
 *
 * @param   string   the form name
 * @param   atring   the name of the array with the checlboxes
 * @param   boolean  whether to check or to uncheck the element
 *
 * @return  boolean  always true
 */
function setCheckboxes(the_form, the_checkboxes, do_check)
{
    var elts      = document.forms[the_form].elements[the_checkboxes + '[]'];
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;

    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
            elts[i].checked = do_check;
        } // end for
    } else {
        elts.checked        = do_check;
    } // end if... else

    return true;
} // end of the 'setCheckboxes()' function