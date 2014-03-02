/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Dummy implementation of the ajax page loader
 */
var AJAX = {
    registerOnload: function (idx, func) {
        $(document).ready(func);
    },
    registerTeardown: function (idx, func) {
    }
};
