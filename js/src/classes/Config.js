import { PMA_Messages as PMA_messages } from '../variables/export_variables';
import { PMA_sprintf } from '../utils/sprintf';

// form validator list
export var validators = {
    // regexp: numeric value
    _regexp_numeric: /^[0-9]+$/,
    // regexp: extract parts from PCRE expression
    _regexp_pcre_extract: /(.)(.*)\1(.*)?/,
    /**
     * Validates positive number
     *
     * @param {boolean} isKeyUp
     */
    PMA_validatePositiveNumber: function (isKeyUp) {
        if (isKeyUp && this.value === '') {
            return true;
        }
        var result = this.value !== '0' && validators._regexp_numeric.test(this.value);
        return result ? true : PMA_messages.error_nan_p;
    },
    /**
     * Validates non-negative number
     *
     * @param {boolean} isKeyUp
     */
    PMA_validateNonNegativeNumber: function (isKeyUp) {
        if (isKeyUp && this.value === '') {
            return true;
        }
        var result = validators._regexp_numeric.test(this.value);
        return result ? true : PMA_messages.error_nan_nneg;
    },
    /**
     * Validates port number
     *
     * @param {boolean} isKeyUp
     */
    PMA_validatePortNumber: function () {
        if (this.value === '') {
            return true;
        }
        var result = validators._regexp_numeric.test(this.value) && this.value !== '0';
        return result && this.value <= 65535 ? true : PMA_messages.error_incorrect_port;
    },
    /**
     * Validates value according to given regular expression
     *
     * @param {boolean} isKeyUp
     * @param {string}  regexp
     */
    PMA_validateByRegex: function (isKeyUp, regexp) {
        if (isKeyUp && this.value === '') {
            return true;
        }
        // convert PCRE regexp
        var parts = regexp.match(validators._regexp_pcre_extract);
        var valid = this.value.match(new RegExp(parts[2], parts[3])) !== null;
        return valid ? true : PMA_messages.error_invalid_value;
    },
    /**
     * Validates upper bound for numeric inputs
     *
     * @param {boolean} isKeyUp
     * @param {int} max_value
     */
    PMA_validateUpperBound: function (isKeyUp, max_value) {
        var val = parseInt(this.value, 10);
        if (isNaN(val)) {
            return true;
        }
        return val <= max_value ? true : PMA_sprintf(PMA_messages.error_value_lte, max_value);
    },
    // field validators
    _field: {
    },
    // fieldset validators
    _fieldset: {
    }
};
