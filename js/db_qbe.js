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
    $("#existingSavedSearches").die('change');
});

AJAX.registerOnload('db_qbe.js', function () {

    /**
     * Ajax event handlers for 'Select saved search'
     */
    $("#existingSavedSearches").live('change', function (event) {
        event.preventDefault();

        var selectedElement = $('#' + this.id + ' option:selected');
        var nameElement = $('#searchName');

        if (selectedElement.val() == '') {
            nameElement.val('');
            return;
        }
        nameElement.val(selectedElement.text());

        //Then : load the data.
    }); // end Select saved search
});
