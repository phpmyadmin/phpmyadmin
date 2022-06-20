/* eslint-disable no-unused-vars */
/**
 * Dummy implementation of the ajax page loader
 */
window.AJAX = {
    registerOnload: function (idx, func) {
        $(func);
    },
    registerTeardown: function (idx, func) {
    }
};
