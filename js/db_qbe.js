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
    $("#updateSearch").die('click');
    $("#deleteSearch").die('click');
});

AJAX.registerOnload('db_qbe.js', function () {

    /**
     * Ajax event handlers for 'Select saved search'
     */
    $("#searchId").live('change', function (event) {
        $('#action').val('load');
        $('#formQBE').submit();
    });

    /**
     * Ajax event handlers for 'Create bookmark'
     */
    $("#saveSearch").live('click', function (event) {
        $('#action').val('create');
    });

    /**
     * Ajax event handlers for 'Update bookmark'
     */
    $("#updateSearch").live('click', function (event) {
        $('#action').val('update');
    });

    /**
     * Ajax event handlers for 'Delete bookmark'
     */
    $("#deleteSearch").live('click', function (event) {
        var question = $.sprintf(PMA_messages.strConfirmDeleteQBESearch, $("#searchId option:selected").text());
        if (!confirm(question)) {
            return false;
        }

        $('#action').val('delete');
    });
});