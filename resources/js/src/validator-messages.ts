import $ from 'jquery';

function extendingValidatorMessages () {
    $.extend($.validator.messages, {
        required: window.Messages.strValidatorRequired,
        remote: window.Messages.strValidatorRemote,
        email: window.Messages.strValidatorEmail,
        url: window.Messages.strValidatorUrl,
        date: window.Messages.strValidatorDate,
        dateISO: window.Messages.strValidatorDateIso,
        number: window.Messages.strValidatorNumber,
        creditcard: window.Messages.strValidatorCreditCard,
        digits: window.Messages.strValidatorDigits,
        equalTo: window.Messages.strValidatorEqualTo,
        maxlength: $.validator.format(window.Messages.strValidatorMaxLength),
        minlength: $.validator.format(window.Messages.strValidatorMinLength),
        rangelength: $.validator.format(window.Messages.strValidatorRangeLength),
        range: $.validator.format(window.Messages.strValidatorRange),
        max: $.validator.format(window.Messages.strValidatorMax),
        min: $.validator.format(window.Messages.strValidatorMin),
        validationFunctionForDateTime: $.validator.format(window.Messages.strValidationFunctionForDateTime),
        validationFunctionForHex: $.validator.format(window.Messages.strValidationFunctionForHex),
        validationFunctionForMd5: $.validator.format(window.Messages.strValidationFunctionForMd5),
        validationFunctionForAesDesEncrypt: $.validator.format(window.Messages.strValidationFunctionForAesDesEncrypt),
    });
}

declare global {
    interface Window {
        extendingValidatorMessages: typeof extendingValidatorMessages;
    }
}

window.extendingValidatorMessages = extendingValidatorMessages;
