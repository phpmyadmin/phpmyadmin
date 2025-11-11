/**
 * JSON syntax highlighting transformation plugin
 */
AJAX.registerOnload('transformations/json.js', function () {
    Functions.highlightJson($('#page_content'));
});
