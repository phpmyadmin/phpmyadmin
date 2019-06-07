/* vim: set expandtab sw=4 ts=4 sts=4: */
/* eslint-disable no-unused-vars */
/**
 * Dummy implementation of the ajax page loader
 */
var AJAX = {
    registerOnload: function (idx, func) {
        $(func);
    },
    registerTeardown: function (idx, func) {
    }
};
