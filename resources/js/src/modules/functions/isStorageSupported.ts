import { ajaxShowMessage } from '../ajax-message.ts';

/**
 * checks whether browser supports web storage
 *
 * @param {'localStorage' | 'sessionStorage'} type the type of storage i.e. localStorage or sessionStorage
 * @param {boolean} warn Wether to show a warning on error
 *
 * @return {boolean}
 */
export default function isStorageSupported (type: 'localStorage' | 'sessionStorage', warn = false): boolean {
    try {
        window[type].setItem('PMATest', 'test');
        // Check whether key-value pair was set successfully
        if (window[type].getItem('PMATest') === 'test') {
            // Supported, remove test variable from storage
            window[type].removeItem('PMATest');

            return true;
        }
    } catch (error) {
        // Not supported
        if (warn) {
            ajaxShowMessage(window.Messages.strNoLocalStorage, false);
        }
    }

    return false;
}
