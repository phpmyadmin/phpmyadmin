import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';

/**
 * XML syntax highlighting transformation plugin
 */
AJAX.registerOnload('transformations/xml.js', function () {
    const $elm = $('#page_content').find('code.xml');
    $elm.each(function () {
        const $json = $(this);
        const $pre = $json.closest('pre');
        /* We only care about visible elements to avoid double processing */
        if ($json.is(':visible')) {
            const $highlight = $('<div class="xml-highlight cm-s-default"></div>');
            $pre.append($highlight);
            // @ts-ignore
            window.CodeMirror.runMode($json.text(), 'application/xml', $highlight[0]);
            $json.hide();
        }
    });
});
