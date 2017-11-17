/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Conditionally included if framing is not allowed
 */
if (self === top) {
    var style_element = document.getElementById('cfs-style');
    // check if style_element has already been removed
    // to avoid frequently reported js error
    if (typeof(style_element) !== 'undefined' && style_element !== null) {
        style_element.parentNode.removeChild(style_element);
    }
} else {
    top.location = self.location;
}
