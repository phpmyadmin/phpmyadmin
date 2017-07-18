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
AJAX.registerTeardown('setup2FA.js', function () {
    $('#submit').off('click');
});

AJAX.registerOnload('setup2FA.js', function () {
    $('#submit').click(function() {
        $.post('setup2FA.php', {
            verification: $('#shared_key').val(),
            settingup: 'true'
        }).done(function(data) {
            if (data === 'true') {
                var message = 'Shared key successfully added. You will be prompted for this key whenever you login again.';
                var message_obj = PMA_ajaxShowMessage(message, false, 'success');
                message_obj.on('remove', function() {
                    location.reload();
                });
            } else {
                PMA_ajaxShowMessage(data, false, 'error');
            }
        });
    });
});
