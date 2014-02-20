/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Ajax event handlers here for db_qbe.php
 *
 * Actions Ajaxified here:
 * Select saved search
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('db_qbe.js', function () {
    $("#searchId").die('change');
    $("#saveSearch").die('click');
    $("#deleteSearch").die('click');
});

$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

AJAX.registerOnload('db_qbe.js', function () {

    /**
     * Ajax event handlers for 'Select saved search'
     */
    $("#searchId").live('change', function (event) {
        $('#action').val('load');
        $('#formQBE').submit();
    });

    /**
     * Ajax event handlers for 'Save search'
     */
    $("#saveSearch").live('click', function (event) {
        $('#action').val('save');
    });

    /**
     * Ajax event handlers for 'Delete search'
     */
    $("#deleteSearch").live('click', function (event) {
        $('#action').val('delete');
    });
});
