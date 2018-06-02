import { Variables } from './global_variables';
import { PMA_commonParams } from './common_params';

var jqueryValidations = {
    validateFormat: window.validateFormat,
    validationMessage: window.validationMessage
};
// console.log('random');
// console.log(window.PMA_messages);
Variables.setAllMessages(window.PMA_messages);
Variables.setTimePickerVars(window.timePicker);
Variables.setValidatorMessages(jqueryValidations);
Variables.setGlobalVars(window.globalVars);
// console.log(Variables.getMessages());

/**
 * This statement to be placed in the file going to be
 * executed firstly like functions.js
 */
PMA_commonParams.setAll(window.common_params);
