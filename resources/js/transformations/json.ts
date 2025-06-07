import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';

/**
 * JSON syntax highlighting transformation plugin
 */
AJAX.registerOnload('transformations/json.js', function () {
    var $elm = $('#page_content').find('code.json');
    $elm.each(function () {
        var $json = $(this);
        var $pre = $json.closest('pre');
        /* We only care about visible elements to avoid double processing */
        if ($json.is(':visible')) {
            var $highlight = $('<div class="json-highlight cm-s-default"></div>');
            $pre.append($highlight);
            // @ts-ignore
            window.CodeMirror.runMode($json.text(), 'application/json', $highlight[0]);
            $json.hide();
        }
    });
});
