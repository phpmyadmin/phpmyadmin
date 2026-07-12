import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';
import { sprintf } from 'locutus/php/strings/sprintf';

/**
 * Check than forms have less fields than max allowed by PHP.
 * @return {boolean}
 */
export default function checkNumberOfFields () {
    if (typeof window.maxInputVars === 'undefined') {
        return false;
    }

    // @ts-ignore
    if (false === window.maxInputVars) {
        return false;
    }

    $('form').each(function () {
        const nbInputs = $(this).find(':input').length;
        if (nbInputs > window.maxInputVars) {
            const warning = sprintf(window.Messages.strTooManyInputs, window.maxInputVars);
            ajaxShowMessage(warning);

            return false;
        }
    });

    return true;
}
