import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.js';

/* global maxInputVars */ // templates/javascript/variables.twig

/**
 * Check than forms have less fields than max allowed by PHP.
 * @return {boolean}
 */
export default function checkNumberOfFields () {
    if (typeof maxInputVars === 'undefined') {
        return false;
    }
    if (false === maxInputVars) {
        return false;
    }
    $('form').each(function () {
        var nbInputs = $(this).find(':input').length;
        if (nbInputs > maxInputVars) {
            var warning = window.sprintf(window.Messages.strTooManyInputs, maxInputVars);
            ajaxShowMessage(warning);
            return false;
        }
        return true;
    });

    return true;
}
