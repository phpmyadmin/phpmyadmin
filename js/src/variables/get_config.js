/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Module import
 */
import { validators } from '../classes/Config';
import { PMA_Messages as PMA_messages } from './export_variables';

/**
 * @type {Object} defaultValues   Default values for the Settings data.
 */
let defaultValues = {};

/**
 * @type {Object} validate        Validations for the settings input fields.
 */
let validate = {};

/**
 * Registers validator for given field
 *
 * @access private
 *
 * @param {String}  id       field id
 *
 * @param {String}  type     validator (key in validators object)
 *
 * @param {boolean} onKeyUp  whether fire on key up
 *
 * @param {Array}   params   validation function parameters
 *
 * @return {void}
 */
function validateField (id, type, onKeyUp, params) {
    if (typeof validators[type] === 'undefined') {
        return;
    }
    if (typeof validate[id] === 'undefined') {
        validate[id] = [];
    }
    validate[id].push([type, params, onKeyUp]);
}

/**
 * @access public
 */
window.getConfigData = function () {
    // Passing the arguments inside validate for validating fields.
    for (var i = 0; i < arguments.length - 3; i++) {
        validateField(...arguments[i]);
    }

    // Extending the Messages for validation.
    $.extend(PMA_messages, arguments[arguments.length - 2]);

    // Extending defaultValues object for default values of settings.
    $.extend(defaultValues, arguments[arguments.length - 1]);
};

/**
 * Object export
 */
export {
    defaultValues,
    validate
};
