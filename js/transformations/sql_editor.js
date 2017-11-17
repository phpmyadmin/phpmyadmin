/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL syntax highlighting transformation plugin js
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/sql_editor.js', function () {
    $('textarea.transform_sql_editor').each(function () {
        PMA_getSQLEditor($(this), {}, 'both');
    });
});
