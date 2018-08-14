import PMA_commonParams from '../variables/common_params';

function captureURL (url) {
    var URL = {};
    url = '' + url;
    // Exclude the url part till HTTP
    url = url.substr(url.search('sql.php'), url.length);
    // The url part between ORDER BY and &session_max_rows needs to be replaced.
    URL.head = url.substr(0, url.indexOf('ORDER+BY') + 9);
    URL.tail = url.substr(url.indexOf('&session_max_rows'), url.length);
    return URL;
}

/**
 * This function is for navigating to the generated URL
 *
 * @param object   target HTMLAnchor element
 * @param object   parent HTMLDom Object
 */

export function removeColumnFromMultiSort (target, parent) {
    var URL = captureURL(target);
    var begin = target.indexOf('ORDER+BY') + 8;
    var end = target.indexOf(PMA_commonParams.get('arg_separator') + 'session_max_rows');
    // get the names of the columns involved
    var between_part = target.substr(begin, end - begin);
    var columns = between_part.split('%2C+');
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
        URL.tail += PMA_commonParams.get('arg_separator') + 'discard_remembered_sort=1';
    }
    URL.head = URL.head.substring(URL.head.indexOf('?') + 1);
    var middle_part = columns.join('%2C+');
    var params = URL.head + middle_part + URL.tail;
    return params;
}
