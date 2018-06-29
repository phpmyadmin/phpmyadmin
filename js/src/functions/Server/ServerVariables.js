/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from '../../variables/export_variables';
import {
    PMA_ajaxShowMessage,
    PMA_ajaxRemoveMessage
} from '../../utils/show_ajax_messages';

/**
 * Allows the user to edit a server variable
 *
 * @param {Element} link         Edit button element
 *
 * @param {Element} $saveLink    Save button element
 *
 * @param {Element} $cancelLink  Cancel button element
 *
 * @return {void}
 */
function editVariable (link, $saveLink, $cancelLink) {
    var $link = $(link);
    var $cell = $link.parent();
    var $valueCell = $link.parents('.var-row').find('.var-value');
    var varName = $link.data('variable');
    var $mySaveLink = $saveLink.clone().css('display', 'inline-block');
    var $myCancelLink = $cancelLink.clone().css('display', 'inline-block');
    var $msgbox = PMA_ajaxShowMessage();
    var $myEditLink = $cell.find('a.editLink');

    $cell.addClass('edit'); // variable is being edited
    $myEditLink.remove(); // remove edit link

    $mySaveLink.on('click', function () {
        var $msgbox = PMA_ajaxShowMessage(messages.strProcessingRequest);
        $.post($(this).attr('href'), {
            ajax_request: true,
            type: 'setval',
            varName: varName,
            varValue: $valueCell.find('input').val()
        }, function (data) {
            if (data.success) {
                $valueCell
                    .html(data.variable)
                    .data('content', data.variable);
                PMA_ajaxRemoveMessage($msgbox);
            } else {
                if (data.error === '') {
                    PMA_ajaxShowMessage(messages.strRequestFailed, false);
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
                $valueCell.html($valueCell.data('content'));
            }
            $cell.removeClass('edit').html($myEditLink);
        });
        return false;
    });

    $myCancelLink.on('click', function () {
        $valueCell.html($valueCell.data('content'));
        $cell.removeClass('edit').html($myEditLink);
        return false;
    });

    $.get($mySaveLink.attr('href'), {
        ajax_request: true,
        type: 'getval',
        varName: varName
    }, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            var $links = $('<div />')
                .append($myCancelLink)
                .append('&nbsp;&nbsp;&nbsp;')
                .append($mySaveLink);
            var $editor = $('<div />', { 'class': 'serverVariableEditor' })
                .append(
                    $('<div/>').append(
                        $('<input />', { type: 'text' }).val(data.message)
                    )
                );
                // Save and replace content
            $cell
                .html($links)
                .children()
                .css('display', 'flex');
            $valueCell
                .data('content', $valueCell.html())
                .html($editor)
                .find('input')
                .focus()
                .on('keydown', function (event) { // Keyboard shortcuts
                    if (event.keyCode === 13) { // Enter key
                        $mySaveLink.trigger('click');
                    } else if (event.keyCode === 27) { // Escape key
                        $myCancelLink.trigger('click');
                    }
                });
            PMA_ajaxRemoveMessage($msgbox);
        } else {
            $cell.removeClass('edit').html($myEditLink);
            PMA_ajaxShowMessage(data.error);
        }
    });
}

/**
 * Module export
 */
export {
    editVariable
};
