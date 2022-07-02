import $ from 'jquery';

/**
 * XML editor plugin
 *
 * @package PhpMyAdmin
 */
window.AJAX.registerOnload('transformations/xml_editor.js', function () {
    $('textarea.transform_xml_editor').each(function () {
        window.CodeMirror.fromTextArea(this, {
            lineNumbers: true,
            indentUnit: 4,
            mode: 'application/xml',
            lineWrapping: true
        });
    });
});
