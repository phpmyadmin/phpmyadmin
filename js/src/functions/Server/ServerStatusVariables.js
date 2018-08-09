/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Filters the status variables by name/category/alert in the variables tab
 *
 * @access public
 *
 * @param {Object} textFilter    Regular expression for filtering text
 *
 * @param {boolean} alertFilter  For filtering alert variables
 *
 * @param {string} categoryFilter
 *
 * @param {string} text          Text based filtering
*/
function filterVariables (
    textFilter,
    alertFilter,
    categoryFilter,
    text
) {
    var usefulLinks = 0;
    var section = text;

    if (categoryFilter.length > 0) {
        section = categoryFilter;
    }

    if (section.length > 1) {
        $('#linkSuggestions').find('span').each(function () {
            if ($(this).attr('class').indexOf('status_' + section) !== -1) {
                usefulLinks++;
                $(this).css('display', '');
            } else {
                $(this).css('display', 'none');
            }
        });
    }

    if (usefulLinks > 0) {
        $('#linkSuggestions').css('display', '');
    } else {
        $('#linkSuggestions').css('display', 'none');
    }

    $('#serverstatusvariables').find('th.name').each(function () {
        if ((textFilter === null || textFilter.exec($(this).text())) &&
            (! alertFilter || $(this).next().find('span.attention').length > 0) &&
            (categoryFilter.length === 0 || $(this).parent().hasClass('s_' + categoryFilter))
        ) {
            $(this).parent().css('display', '');
        } else {
            $(this).parent().css('display', 'none');
        }
    });
}

/**
 * Module export
 */
export {
    filterVariables
};
