/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module Imports
 */
import Variables from './global_variables';
import CommonParams from './common_params';

/**
 * Importing message strings from window of document which need
 * to be used in the files for messages.
 *
 * @argument {hash} window.PMA_messages
 */
Variables.setAllMessages(window.PMA_messages);

/**
 * Importing time and date rrelated strings like day, date, time
 * etc for using with different languages.
 *
 * @argument {hash} window.timePicker
 */
Variables.setTimePickerVars(window.timePicker);

/**
 * Importing validation strings for jQuery validations for diifferent.
 * language validations.
 *
 * @argument {hash} Object
 */
Variables.setValidatorMessages({
    validateFormat: window.validateFormat,
    validationMessage: window.validationMessage
});

/**
 * Importing global variable from window for theme and doc template.
 *
 * @argument {hash} window.globalVars
 */
Variables.setGlobalVars(window.globalVars);

/**
 * Importing common parameters like db, table, url etc
 *
 * @argument {hash} window.common_params
 */
CommonParams.setAll(window.common_params);
