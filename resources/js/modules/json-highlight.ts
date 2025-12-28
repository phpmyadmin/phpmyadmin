
/**
 * Applies JSON syntax highlighting transformation using CodeMirror
 *
 * @param {JQuery} $base base element which contains the JSON code blocks
 */
export default function highlightJson ($base) {
    var $elm = $base.find('code.json');
    $elm.each(function () {
        var $json = $(this);
        var $pre = $json.find('pre');
        /* We only care about visible elements to avoid double processing */
        if ($pre.is(':visible')) {
            var $highlight = $('<div class="json-highlight cm-s-default"></div>');
            $json.append($highlight);
            // @ts-ignore
            if (typeof window.CodeMirror !== 'undefined' && typeof window.CodeMirror.runMode === 'function') {
                // @ts-ignore
                window.CodeMirror.runMode($json.text(), 'application/json', $highlight[0]);
                $pre.hide();
            }
        }
    });
}
