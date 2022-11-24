import $ from 'jquery';
import { AJAX } from '../ajax.js';
import { Functions } from '../functions.js';

/**
 * SQL syntax highlighting transformation plugin js
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/sql_editor.js', function () {
    $('textarea.transform_sql_editor').each(function () {
        Functions.getSqlEditor($(this), {}, 'both');
    });
});
