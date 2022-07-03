import $ from 'jquery';

/**
 * SQL syntax highlighting transformation plugin js
 *
 * @package PhpMyAdmin
 */
window.AJAX.registerOnload('transformations/sql_editor.js', function () {
    $('textarea.transform_sql_editor').each(function () {
        Functions.getSqlEditor($(this), {}, 'both');
    });
});
