/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL syntax highlighting transformation plugin js
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/sql_editor.js', function() {

    $.each($('textarea.transform_sql_editor'), function (i, e) {
        var height = $(e).css('height');
        var codemirror_editor = CodeMirror.fromTextArea(e, {
            lineNumbers: true,
            matchBrackets: true,
            extraKeys: {"Ctrl-Space": "autocomplete"},
            hintOptions: {"completeSingle": false, "completeOnSingleClick": true},
            indentUnit: 4,
            mode: "text/x-mysql",
            lineWrapping: true
        });
        codemirror_editor.on("inputRead", codemirrorAutocompleteOnInputRead);
        codemirror_editor.getScrollerElement().style.height = height;
        codemirror_editor.refresh();
        codemirror_editor.focus();
        $(codemirror_editor.getWrapperElement()).bind(
            'keydown',
            catchKeypressesFromSqlTextboxes
        );
    });

});
