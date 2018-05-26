import zxcvbn from 'zxcvbn';
import { PMA_Messages as PMA_messages } from '../variables/export_variables';

/**
 * Validates the password field in a form
 *
 * @see    PMA_messages.strPasswordEmpty
 * @see    PMA_messages.strPasswordNotSame
 * @param  object $the_form The form to be validated
 * @return bool
 */
function PMA_checkPassword ($the_form) {
    // Did the user select 'no password'?
    if ($the_form.find('#nopass_1').is(':checked')) {
        return true;
    } else {
        var $pred = $the_form.find('#select_pred_password');
        if ($pred.length && ($pred.val() === 'none' || $pred.val() === 'keep')) {
            return true;
        }
    }

    var $password = $the_form.find('input[name=pma_pw]');
    var $password_repeat = $the_form.find('input[name=pma_pw2]');
    var alert_msg = false;

    if ($password.val() === '') {
        alert_msg = PMA_messages.strPasswordEmpty;
    } else if ($password.val() !== $password_repeat.val()) {
        alert_msg = PMA_messages.strPasswordNotSame;
    }

    if (alert_msg) {
        alert(alert_msg);
        $password.val('');
        $password_repeat.val('');
        $password.focus();
        return false;
    }
    return true;
}

/**
 * Validates the "add a user" form
 *
 * @return {boolean}  whether the form is validated or not
 */
export function checkAddUser (the_form) {
    if (the_form.elements.pred_hostname.value === 'userdefined' && the_form.elements.hostname.value === '') {
        alert(PMA_messages.strHostEmpty);
        the_form.elements.hostname.focus();
        return false;
    }

    if (the_form.elements.pred_username.value === 'userdefined' && the_form.elements.username.value === '') {
        alert(PMA_messages.strUserEmpty);
        the_form.elements.username.focus();
        return false;
    }

    return PMA_checkPassword($(the_form));
} // end of the 'checkAddUser()' function

/**
 * Function to check the password strength
 *
 * @param {string} value Passworrd string
 * @param {object} meter_obj jQuery object to show strength in meter
 * @param {object} meter_object_label jQuery object to show text of password strnegth
 * @param {string} username Username string
 *
 * @returns {void}
 */
export function checkPasswordStrength (value, meter_obj, meter_object_label, username) {
    // List of words we don't want to appear in the password
    var customDict = [
        'phpmyadmin',
        'mariadb',
        'mysql',
        'php',
        'my',
        'admin',
    ];
    if (username !== null) {
        customDict.push(username);
    }
    var zxcvbn_obj = zxcvbn(value, customDict);
    var strength = zxcvbn_obj.score;
    strength = parseInt(strength);
    meter_obj.val(strength);
    switch (strength) {
    case 0: meter_object_label.html(PMA_messages.strExtrWeak);
        break;
    case 1: meter_object_label.html(PMA_messages.strVeryWeak);
        break;
    case 2: meter_object_label.html(PMA_messages.strWeak);
        break;
    case 3: meter_object_label.html(PMA_messages.strGood);
        break;
    case 4: meter_object_label.html(PMA_messages.strStrong);
    }
}
