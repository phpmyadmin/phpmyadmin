/**
 * @fileoverview    Implements the shiftkey + click remove column
 *                  from order by clause functionality
 * @name            columndelete
 *
 * @requires    jQuery
 */

function captureURL (url) {
    var newUrl = '' + url;
    var URL = {};
    // Exclude the url part till HTTP
    newUrl = newUrl.substr(newUrl.search('index.php?route=/sql'), newUrl.length);
    // The url part between ORDER BY and &session_max_rows needs to be replaced.
    URL.head = newUrl.substr(0, newUrl.indexOf('ORDER+BY') + 9);
    URL.tail = newUrl.substr(newUrl.indexOf('&session_max_rows'), newUrl.length);
    return URL;
}

/**
 * This function is for navigating to the generated URL
 *
 * @param object   target HTMLAnchor element
 * @param object   parent HTMLDom Object
 */

function removeColumnFromMultiSort (target, parent) {
    var URL = captureURL(target);
    var begin = target.indexOf('ORDER+BY') + 8;
    var end = target.indexOf(CommonParams.get('arg_separator') + 'session_max_rows');
    // get the names of the columns involved
    var betweenPart = target.substr(begin, end - begin);
    var columns = betweenPart.split('%2C+');
    // If the given column is not part of the order clause exit from this function
    var index = parent.find('small').length ? parent.find('small').text() : '';
    if (index === '') {
        return '';
    }
    // Remove the current clicked column
    columns.splice(index - 1, 1);
    // If all the columns have been removed dont submit a query with nothing
    // After order by clause.
    if (columns.length === 0) {
        var head = URL.head;
        head = head.slice(0,head.indexOf('ORDER+BY'));
        URL.head = head;
        // removing the last sort order should have priority over what
        // is remembered via the RememberSorting directive
        URL.tail += CommonParams.get('arg_separator') + 'discard_remembered_sort=1';
    }
    URL.head = URL.head.substring(URL.head.indexOf('?') + 1);
    var middlePart = columns.join('%2C+');
    var params = URL.head + middlePart + URL.tail;
    return params;
}

AJAX.registerOnload('keyhandler.js', function () {
    $('th.draggable.column_heading.pointer.marker a').on('click', function (event) {
        var url = $(this).parent().find('input').val();
        var argsep = CommonParams.get('arg_separator');
        var params;
        if (event.ctrlKey || event.altKey) {
            event.preventDefault();
            params = removeColumnFromMultiSort(url, $(this).parent());
            if (params) {
                AJAX.source = $(this);
                Functions.ajaxShowMessage();
                params += argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
                $.post('index.php?route=/sql', params, AJAX.responseHandler);
            }
        } else if (event.shiftKey) {
            event.preventDefault();
            AJAX.source = $(this);
            Functions.ajaxShowMessage();
            params = url.substring(url.indexOf('?') + 1);
            params += argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            $.post('index.php?route=/sql', params, AJAX.responseHandler);
        }
    });
});

AJAX.registerTeardown('keyhandler.js', function () {
    $(document).off('click', 'th.draggable.column_heading.pointer.marker a');
});
