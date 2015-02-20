/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * JSON syntax highlighting transformation plugin
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/json_editor.js', function() {
    $.each($('textarea.transform_json_editor'), function (i, e) {
        CodeMirror.fromTextArea(e, {
            lineNumbers: true,
            matchBrackets: true,
            indentUnit: 4,
            mode: "application/json",
            lineWrapping: true
        });
    });
});
