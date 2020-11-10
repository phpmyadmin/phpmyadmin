/**
 * Used for generating previews
 */

AJAX.registerTeardown('theme_generator/preview.js',function () {
});
function rgbToHex (col) {
    if (col.charAt(0) === 'r') {
        var newCol = col.replace('rgb(','').replace(')','').split(',');
        var r = parseInt(newCol[0], 10).toString(16);
        var g = parseInt(newCol[1], 10).toString(16);
        var b = parseInt(newCol[2], 10).toString(16);
        r = r.length === 1 ? '0' + r : r;
        g = g.length === 1 ? '0' + g : g;
        b = b.length === 1 ? '0' + b : b;
        var colHex = '#' + r + g + b;
        return colHex;
    }
}

$(document).on('click', '#save_theme', function () {
    var selected = document.getElementById('theme').options.selectedIndex;
    var palette = document.getElementsByClassName('palette')[selected];
    var paletteLength = palette.childNodes.length;

    var colorPalette = {};
    for (var i = 0; i < paletteLength; i++) {
        var temp = palette.childNodes[i].style.backgroundColor;
        var tempHex = rgbToHex(temp);
        colorPalette[palette.childNodes[i].title] = tempHex;
    }
    // eslint-disable-next-line no-undef
    sassArr['_variables.scss'] = getVariablesFile(colorPalette);
    // eslint-disable-next-line no-undef
    generateCss(sassArr, $('#theme_name').val());
});

var tablePreview = function (selectedTheme) {
    var tableRow = document.querySelectorAll('[title="Table Row Background"]')[selectedTheme].style.backgroundColor;
    var tableAlternateRow = window.rgbToHex(tableRow) + '80';
    var hoverRow = window.rgbToHex(tableRow) + '13';
    $('#table_preview').removeClass('d-none');
    $('.row_preview').css('background', tableRow);
    $('.row_alternate_preview').css('background', tableAlternateRow);
    var style = document.createElement('style');
    var cssText = 'tr.row_preview:not(.nopointer):hover, tr.row_alternate_preview:not(.nopointer):hover , tr.marked:not(.nomarker) td { background:' + hoverRow + '!important }';
    var hoverDeclarations = document.createTextNode(cssText);
    style.type = 'text/css';
    if (style.styleSheet) {
        style.styleSheet.cssText = hoverDeclarations.nodeValue;
    } else {
        style.appendChild(hoverDeclarations);
    }
    var head = document.getElementsByTagName('head')[0];
    head.appendChild(style);
};

var navigationPreview = function (selectedTheme) {
    var navColor = document.querySelectorAll('[title="Navigation Panel"]')[selectedTheme].style.backgroundColor;
    var navHover = document.querySelectorAll('[title="Navigation Hover"]')[selectedTheme].style.backgroundColor;
    var backgroundColor = document.querySelectorAll('[title="Background Colour"]')[selectedTheme].style.backgroundColor;
    document.getElementById('pma_navigation').style.background = 'linear-gradient(to right, ' + navColor + ',' + backgroundColor + ')';
    $(document).on(
        'mouseover',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            if ($('li:visible', this).length === 0) {
                $(this).css('background-color', navHover);
            }
        }
    );
    $(document).on(
        'mouseout',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            $(this).css('background-color','transparent');
        }
    );
};

var bodyPreview = function (selectedTheme) {
    var backgroundColor = document.querySelectorAll('[title="Background Colour"]')[selectedTheme].style.backgroundColor;
    var textColor = document.querySelectorAll('[title="Text Colour"]')[selectedTheme].style.backgroundColor;
    document.getElementsByTagName('body')[0].style.background = backgroundColor;
    document.getElementsByTagName('body')[0].style.color = textColor;
};

var headerPreview = function (selectedTheme) {
    var headerColor = document.querySelectorAll('[title="Header"]')[selectedTheme].style.backgroundColor;
    $('.breadcrumb').css('background-color', headerColor);
    document.getElementById('lock_page_icon').style.background = headerColor;
    document.getElementById('page_settings_icon').style.background = headerColor;
    document.getElementById('goto_pagetop').style.background = headerColor;
};

var groupPreview = function (selectedTheme) {
    var groupColor = document.querySelectorAll('[title="Navigation Panel"]')[selectedTheme].style.backgroundColor;
    $('.card').css('background-color', groupColor);
};

$(document).on('click', '#preview' ,function () {
    var selectedTheme = $('#theme').val();
    tablePreview(selectedTheme);
    navigationPreview(selectedTheme);
    bodyPreview(selectedTheme);
    headerPreview(selectedTheme);
    groupPreview(selectedTheme);
});
