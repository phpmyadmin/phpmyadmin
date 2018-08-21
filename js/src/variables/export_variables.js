/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module Imports
 */
import Variables from './global_variables';

/**
 * @type {hash} Messages Contains the message string to be used
 *                       inside PMA.
 */
const PMA_Messages = Variables.getMessages();

/**
 * @type {hash} TimePicker Contains the strings for time and date.
 */
const timePicker = Variables.getTimePickerVars();

/**
 * @type {hash} GlobalVariables Global variables to bee used inside for PMA
 *                              like doc template, theme etc
 */
const GlobalVariables = Variables.getGlobalVars();

/**
 * @type {hash} JqueryValidations Contains the hash for replacing the default
 *                                jQuery validation with language specific validations.
 */
const JqueryValidations = Variables.getValidatorMessages();

/**
 * Module Export
 */
export {
    PMA_Messages,
    timePicker,
    GlobalVariables,
    JqueryValidations
};
