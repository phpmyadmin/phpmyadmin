/** global: AJAX */
/** global: PMA_messages */
/** global: u2f */
AJAX.registerOnload('u2f.js', function () {
    var $inputReg = $('#u2f_registration_response');
    if ($inputReg.length > 0) {
        var $formReg = $inputReg.parents('form');
        $formReg.find('input[type=submit]').hide();
        setTimeout(function () {
            // A magic JS function that talks to the USB device. This function will keep polling for the USB device until it finds one.
            var request = JSON.parse($inputReg.attr('data-request'));
            u2f.register(request.appId, [request], JSON.parse($inputReg.attr('data-signatures')), function (data) {
                // Handle returning error data
                if (data.errorCode && data.errorCode !== 0) {
                    if (data.errorCode === 5) {
                        PMA_ajaxShowMessage(PMA_messages.strU2FTimeout, false);
                    } else {
                        PMA_ajaxShowMessage(
                            PMA_sprintf(PMA_messages.strU2FError, data.errorCode), false
                        );
                    }
                    return;
                }

                // Fill and submit form.
                $inputReg.val(JSON.stringify(data));
                $formReg.submit();
            });
        }, 1000);
    }
    var $inputAuth = $('#u2f_authentication_response');
    if ($inputAuth.length > 0) {
        var $formAuth = $inputAuth.parents('form');
        $formAuth.find('input[type=submit]').hide();
        setTimeout(function () {
            // Magic JavaScript talking to your HID
            // appid, challenge, authenticateRequests
            var request = JSON.parse($inputAuth.attr('data-request'));
            var handles = [request[0].keyHandle];
            u2f.sign(request[0].appId, request[0].challenge, request, function (data) {
                // Handle returning error data
                if (data.errorCode && data.errorCode !== 0) {
                    if (data.errorCode === 5) {
                        PMA_ajaxShowMessage(PMA_messages.strU2FTimeout, false);
                    } else {
                        PMA_ajaxShowMessage(
                            PMA_sprintf(PMA_messages.strU2FError, data.errorCode), false
                        );
                    }
                    return;
                }

                // Fill and submit form.
                $inputAuth.val(JSON.stringify(data));
                $formAuth.submit();
            });
        }, 1000);
    }
});
