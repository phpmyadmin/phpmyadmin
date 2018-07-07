/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used for generating previews
 */

AJAX.registerTeardown('theme_generator/preview.js',function () {
    window.location.reload();
});

$(document).on('click', '#preview' ,function () {
    var id = document.getElementById('theme').options.selectedIndex;
    tablePreview(id);
    navigationPreview(id);
    bodyPreview(id);
    headerPreview(id);
    groupPreview(id);
});

var tablePreview = function (id) {
    var tableRow = document.querySelectorAll('[title="Table Row Background"]')[id].style.backgroundColor;
    var tableAlternateRow = document.querySelectorAll('[title="Table Row Alternate Background"]')[id].style.backgroundColor;
    var hoverRow = document.querySelectorAll('[title="Table Row Hover and Selected"]')[id].style.backgroundColor;
    $('.row').css('background-color',tableRow);
    $('.row_alternate').css('background-color',tableAlternateRow);
    document.getElementById('table_preview').style.display = 'block';
    $(document).on(
        'mouseover',
        '.row , .row_alternate',
        function () {
            $(this).css('background-color',hoverRow);
        }
    );
    $(document).on(
        'mouseout',
        '.row , .row_alternate',
        function () {
            if ($(this).hasClass('row')) {
                $(this).css('background-color',tableRow);
            } else {
                $(this).css('background-color',tableAlternateRow);
            }
        }
    );
};

var navigationPreview = function (id) {
    var navColor = document.querySelectorAll('[title="Navigation Panel"]')[id].style.backgroundColor;
    var navHover = document.querySelectorAll('[title="Navigation Hover"]')[id].style.backgroundColor;
    var backgroundColor = document.querySelectorAll('[title="Background Colour"]')[id].style.backgroundColor;
    document.getElementById('pma_navigation').style.background = 'linear-gradient(to right, ' + navColor + ',' + backgroundColor + ')';
    $(document).on(
        'mouseover',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            if ($('li:visible', this).length === 0) {
                $(this).css('background-color',navHover);
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

var bodyPreview = function (id) {
    var backgroundColor = document.querySelectorAll('[title="Background Colour"]')[id].style.backgroundColor;
    var textColor = document.querySelectorAll('[title="Text Colour"]')[id].style.backgroundColor;
    document.getElementsByTagName('body')[0].style.background = backgroundColor;
    document.getElementsByTagName('body')[0].style.color = textColor;
};

var headerPreview = function (id) {
    var headerColor = document.querySelectorAll('[title="Header"]')[id].style.backgroundColor;
    document.getElementById('serverinfo').style.background = headerColor;
    document.getElementById('lock_page_icon').style.background = headerColor;
    document.getElementById('page_settings_icon').style.background = headerColor;
    document.getElementById('goto_pagetop').style.background = headerColor;
};

var groupPreview = function (id) {
    var groupColor = document.querySelectorAll('[title="Group Background"]')[id].style.backgroundColor;
    document.getElementById('group_preview').style.display = 'block';
    document.getElementById('group_preview').style.background = groupColor;
};
