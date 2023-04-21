import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';

/**
 * JSON syntax highlighting transformation plugin
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/json_editor.js', function () {
    ($('textarea.transform_json_editor') as JQuery<HTMLTextAreaElement>).each(function () {
        window.CodeMirror.fromTextArea(this, {
            lineNumbers: true,
            // @ts-ignore
            matchBrackets: true,
            indentUnit: 4,
            mode: 'application/json',
            lineWrapping: true
        });
    });
});
