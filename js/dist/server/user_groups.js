"use strict";

/**
 * @fileoverview    Javascript functions used in server user groups page
 * @name            Server User Groups
 *
 * @requires    jQuery
 * @requires    jQueryUI
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/user_groups.js', function () {
  $(document).off('click', 'a.deleteUserGroup.ajax');
});
/**
 * Bind event handlers
 */

AJAX.registerOnload('server/user_groups.js', function () {
  // update the checkall checkbox on Edit user group page
  $(Functions.checkboxesSel).trigger('change');
  $(document).on('click', 'a.deleteUserGroup.ajax', function (event) {
    event.preventDefault();
    var $link = $(this);
    var groupName = $link.parents('tr').find('td').first().text();
    var buttonOptions = {};

    buttonOptions[Messages.strGo] = function () {
      $(this).dialog('close');
      $link.removeClass('ajax').trigger('click');
    };

    buttonOptions[Messages.strClose] = function () {
      $(this).dialog('close');
    };

    $('<div></div>').attr('id', 'confirmUserGroupDeleteDialog').append(Functions.sprintf(Messages.strDropUserGroupWarning, Functions.escapeHtml(groupName))).dialog({
      width: 300,
      minWidth: 200,
      modal: true,
      buttons: buttonOptions,
      title: Messages.strConfirm,
      close: function close() {
        $(this).remove();
      }
    });
  });
});