/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * XML editor plugin
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/xml_editor.js', function() {
    $.each($('textarea.transform_xml_editor'), function (i, e) {
        CodeMirror.fromTextArea(e, {
            lineNumbers: true,
            indentUnit: 4,
            mode: "application/xml",
            lineWrapping: true
        });
    });
});
