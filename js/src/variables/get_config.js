import { validators } from '../classes/Config';
import { PMA_Messages as PMA_messages } from './export_variables';

let defaultValues = {};
let validate = {};

/**
 * Registers validator for given field
 *
 * @param {String}  id       field id
 * @param {String}  type     validator (key in validators object)
 * @param {boolean} onKeyUp  whether fire on key up
 * @param {Array}   params   validation function parameters
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

window.getConfigData = function () {
    // debugger;
    for (var i = 0; i < arguments.length - 3; i++) {
        validateField(...arguments[i]);
    }
    $.extend(PMA_messages, arguments[arguments.length - 2]);
    $.extend(defaultValues, arguments[arguments.length - 1]);
    // console.log(PMA_messages);
};

export { defaultValues, validate };
