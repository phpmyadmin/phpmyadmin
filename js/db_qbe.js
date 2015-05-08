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
    $(document).off('change', "#searchId");
    $(document).off('click', "#saveSearch");
    $(document).off('click', "#updateSearch");
    $(document).off('click', "#deleteSearch");
});

AJAX.registerOnload('db_qbe.js', function () {

    bindCodeMirrorToQueryEditor();

    /**
     * Ajax event handlers for 'Select saved search'
     */
    $(document).on('change', "#searchId", function (event) {
        $('#action').val('load');
        $('#formQBE').submit();
    });

    /**
     * Ajax event handlers for 'Create bookmark'
     */
    $(document).on('click', "#saveSearch", function (event) {
        $('#action').val('create');
    });

    /**
     * Ajax event handlers for 'Update bookmark'
     */
    $(document).on('click', "#updateSearch", function (event) {
        $('#action').val('update');
    });

    /**
     * Ajax event handlers for 'Delete bookmark'
     */
    $(document).on('click', "#deleteSearch", function (event) {
        var question = PMA_sprintf(PMA_messages.strConfirmDeleteQBESearch, $("#searchId option:selected").text());
        if (!confirm(question)) {
            return false;
        }

        $('#action').val('delete');
    });
});

/**
 * Binds the CodeMirror to the query editor text area.
 */
function bindCodeMirrorToQueryEditor() {
    var $query_editor = $('#textSqlquery');
    if ($query_editor.length > 0) {
        if (typeof CodeMirror !== 'undefined') {
            var codemirror_query_editor = CodeMirror.fromTextArea($query_editor[0], {
                lineNumbers: true,
                matchBrackets: true,
                extraKeys: {"Ctrl-Space": "autocomplete"},
                hintOptions: {"completeSingle": false, "completeOnSingleClick": true},
                indentUnit: 4,
                mode: "text/x-mysql",
                lineWrapping: true
            });
            codemirror_query_editor.on("inputRead", codemirrorAutocompleteOnInputRead);
            codemirror_query_editor.focus();
            $(codemirror_query_editor.getWrapperElement()).bind(
                'keydown',
                catchKeypressesFromSqlTextboxes
            );
        } else {
            $query_editor.focus().bind(
                'keydown',
                catchKeypressesFromSqlTextboxes
            );
        }
    }
}