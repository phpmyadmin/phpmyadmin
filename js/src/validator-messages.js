// eslint-disable-next-line no-unused-vars
function extendingValidatorMessages () {
    $.extend($.validator.messages, {
        required: Messages.strValidatorRequired,
        remote: Messages.strValidatorRemote,
        email: Messages.strValidatorEmail,
        url: Messages.strValidatorUrl,
        date: Messages.strValidatorDate,
        dateISO: Messages.strValidatorDateIso,
        number: Messages.strValidatorNumber,
        creditcard: Messages.strValidatorCreditCard,
        digits: Messages.strValidatorDigits,
        equalTo: Messages.strValidatorEqualTo,
        maxlength: $.validator.format(Messages.strValidatorMaxLength),
        minlength: $.validator.format(Messages.strValidatorMinLength),
        rangelength: $.validator.format(Messages.strValidatorRangeLength),
        range: $.validator.format(Messages.strValidatorRange),
        max: $.validator.format(Messages.strValidatorMax),
        min: $.validator.format(Messages.strValidatorMin),
        validationFunctionForDateTime: $.validator.format(Messages.strValidationFunctionForDateTime),
        validationFunctionForHex: $.validator.format(Messages.strValidationFunctionForHex),
        validationFunctionForMd5: $.validator.format(Messages.strValidationFunctionForMd5),
        validationFunctionForAesDesEncrypt: $.validator.format(Messages.strValidationFunctionForAesDesEncrypt),
    });
}
