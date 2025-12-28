import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import highlightJson from '../modules/json-highlight.ts';

/**
 * JSON syntax highlighting transformation plugin
 */
AJAX.registerOnload('transformations/json.js', function () {
    highlightJson($('#page_content'));
});
