/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used for password change form
 *
 * @version $Id$
 */

/**
 * Validates the password field in a form
 *
 * @uses    jsPasswordEmpty
 * @uses    jsPasswordNotSame
 * @param   object   the form
 * @return  boolean  whether the field value is valid or not
 */
function checkPassword(the_form)
{
    // Gets the elements pointers
    // use password radio button
    var use_pass = the_form.elements['nopass'][1].checked;

    // Validates
    if (use_pass) {
        var password = the_form.elements['pma_pw'];
        var password_repeat = the_form.elements['pma_pw2'];
        var alert_msg = false;

        if (password.value == '') {
            alert_msg = jsPasswordEmpty;
        } else if (password.value != password_repeat.value) {
            alert_msg = jsPasswordNotSame;
        }

        if (alert_msg) {
            alert(alert_msg);
            password.value  = '';
            password_repeat.value = '';
            password.focus();
            return false;
        }
    } // end if (use_pass)

    return true;
} // end of the 'checkPassword()' function
