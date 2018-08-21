/* vim: set expandtab sw=4 ts=4 sts=4: */

var prevScrollX = 0;
/*
 * Set position, left, top, width of sticky_columns div
 */
function setStickyColumnsPosition ($sticky_columns, $table_results, position, top, left, margin_left) {
    $sticky_columns
        .css('position', position)
        .css('top', top)
        .css('left', left ? left : 'auto')
        .css('margin-left', margin_left ? margin_left : '0px')
        .css('width', $table_results.width());
}

/*
 * Initialize sticky columns
 */
export function initStickyColumns ($table_results) {
    return $('<table class="sticky_columns"></table>')
        .insertBefore($table_results)
        .css('position', 'fixed')
        .css('z-index', '99')
        .css('width', $table_results.width())
        .css('margin-left', $('#page_content').css('margin-left'))
        .css('top', $('#floating_menubar').height())
        .css('display', 'none');
}

/*
 * Arrange/Rearrange columns in sticky header
 */
export function rearrangeStickyColumns ($sticky_columns, $table_results) {
    var $originalHeader = $table_results.find('thead');
    var $originalColumns = $originalHeader.find('tr:first').children();
    var $clonedHeader = $originalHeader.clone();
    // clone width per cell
    $clonedHeader.find('tr:first').children().width(function (i,val) {
        var width = $originalColumns.eq(i).width();
        var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
        if (! is_firefox) {
            width += 1;
        }
        return width;
    });
    $sticky_columns.empty().append($clonedHeader);
}

/*
 * Adjust sticky columns on horizontal/vertical scroll for all tables
 */
export function handleAllStickyColumns () {
    $('.sticky_columns').each(function () {
        handleStickyColumns($(this), $(this).next('.table_results'));
    });
}

/*
 * Adjust sticky columns on horizontal/vertical scroll
 */
export function handleStickyColumns ($sticky_columns, $table_results) {
    var currentScrollX = $(window).scrollLeft();
    var windowOffset = $(window).scrollTop();
    var tableStartOffset = $table_results.offset().top;
    var tableEndOffset = tableStartOffset + $table_results.height();
    if (windowOffset >= tableStartOffset && windowOffset <= tableEndOffset) {
        // for horizontal scrolling
        if (prevScrollX !== currentScrollX) {
            prevScrollX = currentScrollX;
            setStickyColumnsPosition($sticky_columns, $table_results, 'absolute', $('#floating_menubar').height() + windowOffset - tableStartOffset);
        // for vertical scrolling
        } else {
            setStickyColumnsPosition($sticky_columns, $table_results, 'fixed', $('#floating_menubar').height(), $('#pma_navigation').width() - currentScrollX, $('#page_content').css('margin-left'));
        }
        $sticky_columns.show();
    } else {
        $sticky_columns.hide();
    }
}
