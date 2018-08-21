/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from './variables/export_variables';
import { PMA_prepareForAjaxRequest } from './functions/AjaxRequest';
import { PMA_ajaxRemoveMessage, PMA_ajaxShowMessage } from './utils/show_ajax_messages';

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownTblFindReplace () {
    $('#find_replace_form').off('submit');
    $('#toggle_find').off('click');
}

/**
 * Bind events
 */
export function onloadTblFindReplace () {
    $('<div id="toggle_find_div"><a id="toggle_find"></a></div>')
        .insertAfter('#find_replace_form')
        .hide();

    $('#toggle_find')
        .html(messages.strHideFindNReplaceCriteria)
        .on('click', function () {
            var $link = $(this);
            $('#find_replace_form').slideToggle();
            if ($link.text() === messages.strHideFindNReplaceCriteria) {
                $link.text(messages.strShowFindNReplaceCriteria);
            } else {
                $link.text(messages.strHideFindNReplaceCriteria);
            }
            return false;
        });

    $('#find_replace_form').submit(function (e) {
        e.preventDefault();
        var findReplaceForm = $('#find_replace_form');
        PMA_prepareForAjaxRequest(findReplaceForm);
        var $msgbox = PMA_ajaxShowMessage();
        $.post(findReplaceForm.attr('action'), findReplaceForm.serialize(), function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (data.success === true) {
                $('#toggle_find_div').show();
                $('#toggle_find').trigger('click');
                $('#sqlqueryresultsouter').html(data.preview);
            } else {
                $('#sqlqueryresultsouter').html(data.error);
            }
        });
    });
}
