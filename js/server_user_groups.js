/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_user_groups.js', function () {
    $("a.deleteUserGroup.ajax").die('click');
});

/**
 * Bind event handlers
 */
AJAX.registerOnload('server_user_groups.js', function () {

    // update the checkall checkbox on Edit user group page
    $(checkboxes_sel).trigger("change");

    $("a.deleteUserGroup.ajax").live('click', function (event) {
        event.preventDefault();
        var $link = $(this);
        var groupName = $link.parents('tr').find('td:first').text();
        var buttonOptions = {};
        buttonOptions[PMA_messages.strGo] = function () {
            $(this).dialog("close");
            $link.removeClass('ajax').trigger('click');
        };
        buttonOptions[PMA_messages.strClose] = function () {
            $(this).dialog("close");
        };
        $('<div/>')
            .attr('id', 'confirmUserGroupDeleteDialog')
            .append(PMA_sprintf(PMA_messages.strDropUserGroupWarning, escapeHtml(groupName)))
            .dialog({
                width: 300,
                minWidth: 200,
                modal: true,
                buttons: buttonOptions,
                title: PMA_messages.strConfirm,
                close: function () {
                    $(this).remove();
                }
            });
    });
});
