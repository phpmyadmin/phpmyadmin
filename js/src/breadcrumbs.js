/**
 * @fileoverview This file includes logic to deal with breadcrums
 *
 * @requires js/functions.js
 * @requires js/ajax.js
 * @requires jQuery
 */

$(function () {
    function performCopy () {
        Functions.copyToClipboard($(this).data('copy-text'));
        Functions.ajaxShowMessage(Messages.strCopiedTableNameToClipboard, 1500, 'success');
    }

    AJAX.registerOnload('breadcrumbs.js', function () {
        $('.breadcrumb').on('click', '.breadcrumb-table-copy-button', performCopy);
    });

    AJAX.registerTeardown('breadcrumbs.js', function () {
        $('.breadcrumb').off('click', '.breadcrumb-table-copy-button', performCopy);
    });
});
