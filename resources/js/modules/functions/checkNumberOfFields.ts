import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';

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
        var nbInputs = $(this).find(':input').length;
        if (nbInputs > window.maxInputVars) {
            var warning = window.sprintf(window.Messages.strTooManyInputs, window.maxInputVars);
            ajaxShowMessage(warning);

            return false;
        }
    });

    return true;
}
