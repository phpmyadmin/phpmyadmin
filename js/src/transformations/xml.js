import $ from 'jquery';

/**
 * XML syntax highlighting transformation plugin
 */
window.AJAX.registerOnload('transformations/xml.js', function () {
    var $elm = $('#page_content').find('code.xml');
    $elm.each(function () {
        var $json = $(this);
        var $pre = $json.find('pre');
        /* We only care about visible elements to avoid double processing */
        if ($pre.is(':visible')) {
            var $highlight = $('<div class="xml-highlight cm-s-default"></div>');
            $json.append($highlight);
            window.CodeMirror.runMode($json.text(), 'application/xml', $highlight[0]);
            $pre.hide();
        }
    });
});
