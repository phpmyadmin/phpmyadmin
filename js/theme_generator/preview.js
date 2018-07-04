/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used for generating previews
 */

AJAX.registerTeardown('theme_generator/preview.js',function(){
    document.getElementById("pma_navigation").style.background = "";
    document.getElementsByTagName("body")[0].style.background = "";
});

$(document).on('click', '#preview' ,function () {
    var id = document.getElementById('theme').options.selectedIndex;
    var navColor = document.querySelectorAll('[title="Navigation Panel"]')[id].style.backgroundColor;
    var headerColor = document.querySelectorAll('[title="Header"]')[id].style.backgroundColor;
    var navHover = document.querySelectorAll('[title="Navigation Hover"]')[id].style.backgroundColor;
    var backgroundColor = document.querySelectorAll('[title="Background Colour"]')[id].style.backgroundColor;
    var tableRow = document.querySelectorAll('[title="Table Row Background"]')[id].style.backgroundColor;
    var tableAlternateRow = document.querySelectorAll('[title="Table Row Alternate Background"]')[id].style.backgroundColor;
    document.getElementById("pma_navigation").style.background = "linear-gradient(to right, " + navColor + "," + backgroundColor + ")";
    document.getElementById("table_preview").style.display = "block";
    document.getElementsByTagName("body")[0].style.background = backgroundColor;
    document.getElementById("serverinfo").style.background = headerColor;
    navigationHover(navHover);
    $('.row').css('background-color',tableRow);
    $('.row_alternate').css('background-color',tableAlternateRow);
});

var navigationHover = function(navHover) {
    $(document).on(
        'mouseover',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            if ($('li:visible', this).length === 0 && $('#palette').length > 0) {
                $(this).css('background-color',navHover);
            }
        }
    );
    $(document).on(
        'mouseout',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            if ($('#palette').length > 0) {
                $(this).css('background-color','transparent');
            }
        }
    );
}
