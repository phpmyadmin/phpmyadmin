import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';

/**
 * XML editor plugin
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/xml_editor.js', function () {
    ($('textarea.transform_xml_editor') as JQuery<HTMLTextAreaElement>).each(function () {
        window.CodeMirror.fromTextArea(this, {
            lineNumbers: true,
            indentUnit: 4,
            mode: 'application/xml',
            lineWrapping: true
        });
    });
});
