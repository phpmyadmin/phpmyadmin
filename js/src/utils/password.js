/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import zxcvbn from 'zxcvbn';
import { PMA_Messages as messages } from '../variables/export_variables';

/**
 * Generate a new password and copy it to the password input areas
 *
 * @access private
 *
 * @param {Object} passwdForm   the form that holds the password fields
 *
 * @return {boolean}  always true
 */
function suggestPassword (passwdForm) {
    // restrict the password to just letters and numbers to avoid problems:
    // "editors and viewers regard the password as multiple words and
    // things like double click no longer work"
    var pwchars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ';
    var passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
    var passwd = passwdForm.generated_pw;
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

    var $jqueryPasswdForm = $(passwdForm);

    passwdForm.elements.pma_pw.value = passwd.value;
    passwdForm.elements.pma_pw2.value = passwd.value;
    var meterObj = $jqueryPasswdForm.find('meter[name="pw_meter"]').first();
    var meterObjLabel = $jqueryPasswdForm.find('span[name="pw_strength"]').first();
    checkPasswordStrength(passwd.value, meterObj, meterObjLabel);
    return true;
}

/**
 * for PhpMyAdmin\Display\ChangePassword
 *     libraries/user_password.php
 *
 * @access public
 *
 * @return {void}
 */
function displayPasswordGenerateButton () {
    var generatePwdRow = $('<tr />').addClass('vmiddle');
    $('<td />').html(messages.strGeneratePassword).appendTo(generatePwdRow);
    var pwdCell = $('<td />').appendTo(generatePwdRow);
    var pwdButton = $('<input />')
        .attr({ type: 'button', id: 'button_generate_password', value: messages.strGenerate })
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
        .html(messages.strGeneratePassword + ':')
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
 * @access public
 *
 * @return {boolean}  whether the form is validated or not
 */
function checkAddUser (theForm) {
    if (theForm.elements.pred_hostname.value === 'userdefined' && theForm.elements.hostname.value === '') {
        alert(messages.strHostEmpty);
        theForm.elements.hostname.focus();
        return false;
    }

    if (theForm.elements.pred_username.value === 'userdefined' && theForm.elements.username.value === '') {
        alert(messages.strUserEmpty);
        theForm.elements.username.focus();
        return false;
    }

    return checkPassword($(theForm));
} // end of the 'checkAddUser()' function

/**
 * Function to check the password strength
 *
 * @access public
 *
 * @param {string} value Passworrd string
 *
 * @param {object} meterObj jQuery object to show strength in meter
 *
 * @param {object} meterObjectLabel jQuery object to show text of password strnegth
 *
 * @param {string} username Username string
 *
 * @returns {void}
 */
function checkPasswordStrength (value, meterObj, meterObjectLabel, username) {
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
    var zxcvbnObj = zxcvbn(value, customDict);
    var strength = zxcvbnObj.score;
    strength = parseInt(strength);
    meterObj.val(strength);
    switch (strength) {
    case 0: meterObjectLabel.html(messages.strExtrWeak);
        break;
    case 1: meterObjectLabel.html(messages.strVeryWeak);
        break;
    case 2: meterObjectLabel.html(messages.strWeak);
        break;
    case 3: meterObjectLabel.html(messages.strGood);
        break;
    case 4: meterObjectLabel.html(messages.strStrong);
    }
}

/**
 * Validates the password field in a form
 *
 * @access private
 *
 * @see    PMA_Messages.strPasswordEmpty
 *
 * @see    PMA_Messages.strPasswordNotSame
 *
 * @param  {Object} $the_form The form to be validated
 *
 * @return {bool}
 */
function checkPassword ($theForm) {
    // Did the user select 'no password'?
    if ($theForm.find('#nopass_1').is(':checked')) {
        return true;
    } else {
        var $pred = $theForm.find('#select_pred_password');
        if ($pred.length && ($pred.val() === 'none' || $pred.val() === 'keep')) {
            return true;
        }
    }

    var $password = $theForm.find('input[name=pma_pw]');
    var $passwordRepeat = $theForm.find('input[name=pma_pw2]');
    var alertMsg = false;

    if ($password.val() === '') {
        alertMsg = messages.strPasswordEmpty;
    } else if ($password.val() !== $passwordRepeat.val()) {
        alertMsg = messages.strPasswordNotSame;
    }

    if (alertMsg) {
        alert(alertMsg);
        $password.val('');
        $passwordRepeat.val('');
        $password.focus();
        return false;
    }
    return true;
}

/**
 * Module export
 */
export {
    checkAddUser,
    checkPasswordStrength,
    displayPasswordGenerateButton
};
