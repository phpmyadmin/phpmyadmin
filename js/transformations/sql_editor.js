/* vim: set expandtab sw=4 ts=4 sts=4: */
import { AJAX } from './../ajax.js';
import { PMA_getSQLEditor } from './../functions.js';

/**
 * SQL syntax highlighting transformation plugin js
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/sql_editor.js', function() {

    $('textarea.transform_sql_editor').each(function () {
        PMA_getSQLEditor($(this), {}, 'both');
    });
});
