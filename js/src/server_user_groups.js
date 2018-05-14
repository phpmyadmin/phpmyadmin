/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from './variables/export_variables';
import { PMA_sprintf } from './utils/sprintf';
import { escapeHtml } from './utils/Sanitise';

/**
 * @package PhpMyAdmin
 *
 * Server User Groups
 */

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownServerUserGroups () {
    $(document).off('click', 'a.deleteUserGroup.ajax');
}

/**
 * Bind event handlers on page load.
 */
function onloadServerUserGroups () {
    // update the checkall checkbox on Edit user group page
    // console.log($('input.checkall:checkbox:enabled'));
    $(checkboxes_sel).trigger('change');

    $(document).on('click', 'a.deleteUserGroup.ajax', function (event) {
        event.preventDefault();
        var $link = $(this);
        var groupName = $link.parents('tr').find('td:first').text();
        var buttonOptions = {};
        buttonOptions[messages.strGo] = function () {
            $(this).dialog('close');
            $link.removeClass('ajax').trigger('click');
        };
        buttonOptions[messages.strClose] = function () {
            $(this).dialog('close');
        };
        $('<div/>')
            .attr('id', 'confirmUserGroupDeleteDialog')
            .append(PMA_sprintf(messages.strDropUserGroupWarning, escapeHtml(groupName)))
            .dialog({
                width: 300,
                minWidth: 200,
                modal: true,
                buttons: buttonOptions,
                title: messages.strConfirm,
                close: function () {
                    $(this).remove();
                }
            });
    });
}

/**
 * Module export
 */
export {
    teardownServerUserGroups,
    onloadServerUserGroups
};
