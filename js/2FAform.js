/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Javascript for setup2FA.php
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * js file for handling AJAX and other events in setup2FA.php
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('2FAform.js', function () {
    $('#submit').off('click');
});

AJAX.registerOnload('2FAform.js', function () {
    $('#submit').click(function() {
        $.post('setup2FA.php', {
            verification: $('#shared_code').val(),
            logincheck: 'true'
        }).done(function(data) {
            if (data === 'true') {
                window.location = 'index.php'
            } else {
                PMA_ajaxShowMessage(data, false, 'error');
            }
        });
    });
});
