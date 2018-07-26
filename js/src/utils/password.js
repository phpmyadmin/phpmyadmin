import { PMA_Messages as PMA_messages } from '../variables/export_variables';
import zxcvbn from 'zxcvbn';

/**
 * Generate a new password and copy it to the password input areas
 *
 * @param passwd_form object   the form that holds the password fields
 *
 * @return boolean  always true
 */
export function suggestPassword (passwd_form) {
    // restrict the password to just letters and numbers to avoid problems:
    // "editors and viewers regard the password as multiple words and
    // things like double click no longer work"
    var pwchars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ';
    var passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
    var passwd = passwd_form.generated_pw;
    var randomWords = new Int32Array(passwordlength);

    passwd.value = '';

    // First we're going to try to use a built-in CSPRNG
    if (window.crypto && window.crypto.getRandomValues) {
        window.crypto.getRandomValues(randomWords);
    } else if (window.msCrypto && window.msCrypto.getRandomValues) {
        // Because of course IE calls it msCrypto instead of being standard
        window.msCrypto.getRandomValues(randomWords);
    } else {
        // Fallback to Math.random
        for (let i = 0; i < passwordlength; i++) {
            randomWords[i] = Math.floor(Math.random() * pwchars.length);
        }
    }

    for (let i = 0; i < passwordlength; i++) {
        passwd.value += pwchars.charAt(Math.abs(randomWords[i]) % pwchars.length);
    }

    var $jquery_passwd_form = $(passwd_form);

    passwd_form.elements.pma_pw.value = passwd.value;
    passwd_form.elements.pma_pw2.value = passwd.value;
    var meter_obj = $jquery_passwd_form.find('meter[name="pw_meter"]').first();
    var meter_obj_label = $jquery_passwd_form.find('span[name="pw_strength"]').first();
    checkPasswordStrength(passwd.value, meter_obj, meter_obj_label);
    return true;
}

/**
 * for PhpMyAdmin\Display\ChangePassword
 *     libraries/user_password.php
 *
 */
export function displayPasswordGenerateButton () {
    var generatePwdRow = $('<tr />').addClass('vmiddle');
    $('<td />').html(PMA_messages.strGeneratePassword).appendTo(generatePwdRow);
    var pwdCell = $('<td />').appendTo(generatePwdRow);
    var pwdButton = $('<input />')
        .attr({ type: 'button', id: 'button_generate_password', value: PMA_messages.strGenerate })
        .addClass('button')
        .on('click', function () {
            suggestPassword(this.form);
        });
    var pwdTextbox = $('<input />')
        .attr({ type: 'text', name: 'generated_pw', id: 'generated_pw' });
    pwdCell.append(pwdButton).append(pwdTextbox);

    $('#tr_element_before_generate_password').parent().append(generatePwdRow);

    var generatePwdDiv = $('<div />').addClass('item');
    $('<label />').attr({ for: 'button_generate_password' })
        .html(PMA_messages.strGeneratePassword + ':')
        .appendTo(generatePwdDiv);
    var optionsSpan = $('<span/>').addClass('options')
        .appendTo(generatePwdDiv);
    pwdButton.clone(true).appendTo(optionsSpan);
    pwdTextbox.clone(true).appendTo(generatePwdDiv);
    $('#div_element_before_generate_password').parent().append(generatePwdDiv);
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
