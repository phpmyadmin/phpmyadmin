/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL syntax highlighting transformation plugin js
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/sql_editor.js', function() {

    $.each($('textarea.transform_sql_editor'), function (i, e) {
        var height = $(e).css('height');
        var codemirror_editor = PMA_getSQLEditor($(e));
        codemirror_editor.getScrollerElement().style.height = height;
        codemirror_editor.refresh();
    });
});
