/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for main custom color 
 *
 */
$(document).ready(function() {
    $('#li_custom_color').show();
    // Choosing another id does not work! 
    $("input[type='submit'][name='custom_color_choose']").ColorPicker({
        color: '#0000ff',
        onShow: function (colpkr) {
            $(colpkr).fadeIn(500);
            return false;
        },
        onHide: function (colpkr) {
            $(colpkr).fadeOut(500);
            return false;
        },
        onChange: function(hsb, hex, rgb) {
            top.frame_content.document.body.style.backgroundColor = '#' + hex;
            top.frame_navigation.document.body.style.backgroundColor = '#' + hex;
        },
        onSubmit: function(hsb, hex, rgb) {
            $('#custom_color').val('#' + hex);
            $('#colorform').submit();
        }
    });
});
