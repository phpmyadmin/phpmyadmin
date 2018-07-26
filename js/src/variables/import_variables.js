import { Variables } from './global_variables';
import { PMA_commonParams } from './common_params';

var jqueryValidations = {
    validationFormat: window.validateFormat,
    validationMessage: window.validationMessage
};
Variables.setAllMessages(window.PMA_messages);
Variables.setTimePickerVars({
    datePicker: window.datePicker,
    timePicker: window.timePicker
});
Variables.setValidatorMessages(jqueryValidations);
Variables.setGlobalVars(window.globalVars);

/**
 * This statement to be placed in the file going to be
 * executed firstly like functions.js
 */
PMA_commonParams.setAll(window.common_params);
