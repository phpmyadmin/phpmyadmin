/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used in server privilege pages
 * @name            Server Privileges
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 */

/**
 * Module import
 */
import { PMA_sprintf } from './utils/sprintf';
import { checkPasswordStrength, displayPasswordGenerateButton } from './utils/password';
import { PMA_Messages as messages } from './variables/export_variables';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage } from './utils/show_ajax_messages';
import CommonParams from './variables/common_params';
<<<<<<< HEAD
import { jQuery as $ } from './utils/JqueryExtended';
=======
import { $ } from './utils/extend_jquery';
>>>>>>> Weekly progress.
import { PMA_getSQLEditor } from './utils/sql';

/**
 * @package PhpMyAdmin
 *
 * Server Privileges
 */

/**
 * AJAX scripts for server_privileges page.
 *
 * Actions ajaxified here:
 * Add user
 * Revoke a user
 * Edit privileges
 * Export privileges
 * Paginate table of users
 * Flush privileges
 *
 * @memberOf    jQuery
 * @name        document.ready
 */

/**
 * Unbind all event handlers before tearing down a page.
 */
function teardownServerPrivileges () {
    $('#fieldset_add_user_login').off('change', 'input[name=\'username\']');
    $(document).off('click', '#fieldset_delete_user_footer #buttonGo.ajax');
    $(document).off('click', 'a.edit_user_group_anchor.ajax');
    $(document).off('click', 'button.mult_submit[value=export]');
    $(document).off('click', 'a.export_user_anchor.ajax');
    $(document).off('click',  '#initials_table a.ajax');
    $('#checkbox_drop_users_db').off('click');
    $(document).off('click', '.checkall_box');
    $(document).off('change', '#checkbox_SSL_priv');
    $(document).off('change', 'input[name="ssl_type"]');
    $(document).off('change', '#select_authentication_plugin');
}

/**
 * Binding event handlers on page load.
 */
function onloadServerPrivileges () {
    /**
     * Display a warning if there is already a user by the name entered as the username.
     */
    $('#fieldset_add_user_login').on('change', 'input[name=\'username\']', function () {
        var username = $(this).val();
        var $warning = $('#user_exists_warning');
        if ($('#select_pred_username').val() === 'userdefined' && username !== '') {
            var href = $('form[name=\'usersForm\']').attr('action');
            var params = {
                'ajax_request' : true,
                'server' : CommonParams.get('server'),
                'validate_username' : true,
                'username' : username
            };
            $.get(href, params, function (data) {
                if (data.user_exists) {
                    $warning.show();
                } else {
                    $warning.hide();
                }
            });
        } else {
            $warning.hide();
        }
    });

    /**
     * Indicating password strength
     */
    var meterObj;
    var meterObjLabel;
    var username;
    $(document).on('keyup', '#text_pma_pw', function () {
        meterObj = $('#password_strength_meter');
        meterObjLabel = $('#password_strength');
        username = $('input[name="username"]');
        username = username.val();
        checkPasswordStrength($(this).val(), meterObj, meterObjLabel, username);
    });

    $(document).on('keyup', '#text_pma_change_pw', function () {
        meterObj = $('#change_password_strength_meter');
        meterObjLabel = $('#change_password_strength');
        checkPasswordStrength($(this).val(), meterObj, meterObjLabel, CommonParams.get('user'));
    });

    /**
     * Display a notice if sha256_password is selected
     */
    $(document).on('change', '#select_authentication_plugin', function () {
        var selectedPlugin = $(this).val();
        if (selectedPlugin === 'sha256_password') {
            $('#ssl_reqd_warning').show();
        } else {
            $('#ssl_reqd_warning').hide();
        }
    });

    /**
     * AJAX handler for 'Revoke User'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        revoke_user_click
     */
    $(document).on('click', '#fieldset_delete_user_footer #buttonGo.ajax', function (event) {
        event.preventDefault();

        var $thisButton = $(this);
        var $form = $('#usersForm');

        $thisButton.PMA_confirm(messages.strDropUserWarning, $form.attr('action'), function (url) {
            var $dropUsersDbCheckbox = $('#checkbox_drop_users_db');
            if ($dropUsersDbCheckbox.is(':checked')) {
                var isConfirmed = confirm(messages.strDropDatabaseStrongWarning + '\n' + PMA_sprintf(messages.strDoYouReally, 'DROP DATABASE'));
                if (! isConfirmed) {
                    // Uncheck the drop users database checkbox
                    $dropUsersDbCheckbox.prop('checked', false);
                }
            }

            PMA_ajaxShowMessage(messages.strRemovingSelectedUsers);

            var argsep = CommonParams.get('arg_separator');
            $.post(url, $form.serialize() + argsep + 'delete=' + $thisButton.val() + argsep + 'ajax_request=true', function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    // Refresh navigation, if we droppped some databases with the name
                    // that is the same as the username of the deleted user
                    if ($('#checkbox_drop_users_db:checked').length) {
                        PMA_reloadNavigation();
                    }
                    // Remove the revoked user from the users list
                    $form.find('input:checkbox:checked').parents('tr').slideUp('medium', function () {
                        var thisUserInitial = $(this).find('input:checkbox').val().charAt(0).toUpperCase();
                        $(this).remove();

                        // If this is the last user with thisUserInitial, remove the link from #initials_table
                        if ($('#tableuserrights').find('input:checkbox[value^="' + thisUserInitial + '"], input:checkbox[value^="' + thisUserInitial.toLowerCase() + '"]').length === 0) {
                            $('#initials_table').find('td > a:contains(' + thisUserInitial + ')').parent('td').html(thisUserInitial);
                        }

                        // Re-check the classes of each row
                        $form
                            .find('tbody').find('tr:odd')
                            .removeClass('even').addClass('odd')
                            .end()
                            .find('tr:even')
                            .removeClass('odd').addClass('even');

                        // update the checkall checkbox
                        $(checkboxes_sel).trigger('change');
                    });
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }); // end $.post()
        });
    }); // end Revoke User

    $(document).on('click', 'a.edit_user_group_anchor.ajax', function (event) {
        event.preventDefault();
        $(this).parents('tr').addClass('current_row');
        var $msg = PMA_ajaxShowMessage();
        $.get(
            $(this).attr('href'),
            {
                'ajax_request': true,
                'edit_user_group_dialog': true
            },
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    var buttonOptions = {};
                    buttonOptions[messages.strGo] = function () {
                        var usrGroup = $('#changeUserGroupDialog')
                            .find('select[name="userGroup"]')
                            .val();
                        var $message = PMA_ajaxShowMessage();
                        var argsep = CommonParams.get('arg_separator');
                        $.post(
                            'server_privileges.php',
                            $('#changeUserGroupDialog').find('form').serialize() + argsep + 'ajax_request=1',
                            function (data) {
                                PMA_ajaxRemoveMessage($message);
                                if (typeof data !== 'undefined' && data.success === true) {
                                    $('#usersForm')
                                        .find('.current_row')
                                        .removeClass('current_row')
                                        .find('.usrGroup')
                                        .text(usrGroup);
                                } else {
                                    PMA_ajaxShowMessage(data.error, false);
                                    $('#usersForm')
                                        .find('.current_row')
                                        .removeClass('current_row');
                                }
                            }
                        );
                        $(this).dialog('close');
                    };
                    buttonOptions[messages.strClose] = function () {
                        $(this).dialog('close');
                    };
                    var $dialog = $('<div/>')
                        .attr('id', 'changeUserGroupDialog')
                        .append(data.message)
                        .dialog({
                            width: 500,
                            minWidth: 300,
                            modal: true,
                            buttons: buttonOptions,
                            title: $('legend', $(data.message)).text(),
                            close: function () {
                                $(this).remove();
                            }
                        });
                    $dialog.find('legend').remove();
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                    $('#usersForm')
                        .find('.current_row')
                        .removeClass('current_row');
                }
            }
        );
    });

    /**
     * AJAX handler for 'Export Privileges'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        export_user_click
     */
    $(document).on('click', 'button.mult_submit[value=export]', function (event) {
        event.preventDefault();
        // can't export if no users checked
        if ($(this.form).find('input:checked').length === 0) {
            PMA_ajaxShowMessage(messages.strNoAccountSelected, 2000, 'success');
            return;
        }
        var $msgbox = PMA_ajaxShowMessage();
        var buttonOptions = {};
        buttonOptions[messages.strClose] = function () {
            $(this).dialog('close');
        };
        var argsep = CommonParams.get('arg_separator');
        $.post(
            $(this.form).prop('action'),
            $(this.form).serialize() + argsep + 'submit_mult=export' + argsep + 'ajax_request=true',
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    var $ajaxDialog = $('<div />')
                        .append(data.message)
                        .dialog({
                            title: data.title,
                            width: 500,
                            buttons: buttonOptions,
                            close: function () {
                                $(this).remove();
                            }
                        });
                    PMA_ajaxRemoveMessage($msgbox);
                    // Attach syntax highlighted editor to export dialog
                    PMA_getSQLEditor($ajaxDialog.find('textarea'));
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        ); // end $.post
    });
    // if exporting non-ajax, highlight anyways
    PMA_getSQLEditor($('textarea.export'));

    $(document).on('click', 'a.export_user_anchor.ajax', function (event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        /**
         * @var buttonOptions  Object containing options for jQueryUI dialog buttons
         */
        var buttonOptions = {};
        buttonOptions[messages.strClose] = function () {
            $(this).dialog('close');
        };
        $.get($(this).attr('href'), { 'ajax_request': true }, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                var $ajaxDialog = $('<div />')
                    .append(data.message)
                    .dialog({
                        title: data.title,
                        width: 500,
                        buttons: buttonOptions,
                        close: function () {
                            $(this).remove();
                        }
                    });
                PMA_ajaxRemoveMessage($msgbox);
                // Attach syntax highlighted editor to export dialog
                PMA_getSQLEditor($ajaxDialog.find('textarea'));
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get
    }); // end export privileges

    /**
     * AJAX handler to Paginate the Users Table
     *
     * @see         PMA_ajaxShowMessage()
     * @name        paginate_users_table_click
     * @memberOf    jQuery
     */
    $(document).on('click', '#initials_table a.ajax', function (event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), { 'ajax_request' : true }, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxRemoveMessage($msgbox);
                // This form is not on screen when first entering Privileges
                // if there are more than 50 users
                $('div.notice').remove();
                $('#usersForm').hide('medium').remove();
                $('#fieldset_add_user').hide('medium').remove();
                $('#initials_table')
                    .prop('id', 'initials_table_old')
                    .after(data.message).show('medium')
                    .siblings('h2').not(':first').remove();
                // prevent double initials table
                $('#initials_table_old').remove();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get
    }); // end of the paginate users table

    $(document).on('change', 'input[name="ssl_type"]', function () {
        var $div = $('#specified_div');
        if ($('#ssl_type_SPECIFIED').is(':checked')) {
            $div.find('input').prop('disabled', false);
        } else {
            $div.find('input').prop('disabled', true);
        }
    });

    $(document).on('change', '#checkbox_SSL_priv', function () {
        var $div = $('#require_ssl_div');
        if ($(this).is(':checked')) {
            $div.find('input').prop('disabled', false);
            $('#ssl_type_SPECIFIED').trigger('change');
        } else {
            $div.find('input').prop('disabled', true);
        }
    });

    $('#checkbox_SSL_priv').trigger('change');

    /*
     * Create submenu for simpler interface
     */
    var addOrUpdateSubmenu = function () {
        var $topmenu2 = $('#topmenu2');
        var $editUserDialog = $('#edit_user_dialog');
        var submenuLabel;
        var submenuLink;
        var linkNumber;

        // if submenu exists yet, remove it first
        if ($topmenu2.length > 0) {
            $topmenu2.remove();
        }

        // construct a submenu from the existing fieldsets
        $topmenu2 = $('<ul/>').prop('id', 'topmenu2');

        $('#edit_user_dialog .submenu-item').each(function () {
            submenuLabel = $(this).find('legend[data-submenu-label]').data('submenu-label');

            submenuLink = $('<a/>')
                .prop('href', '#')
                .html(submenuLabel);

            $('<li/>')
                .append(submenuLink)
                .appendTo($topmenu2);
        });

        // click handlers for submenu
        $topmenu2.find('a').click(function (e) {
            e.preventDefault();
            // if already active, ignore click
            if ($(this).hasClass('tabactive')) {
                return;
            }
            $topmenu2.find('a').removeClass('tabactive');
            $(this).addClass('tabactive');

            // which section to show now?
            linkNumber = $topmenu2.find('a').index($(this));
            // hide all sections but the one to show
            $('#edit_user_dialog .submenu-item').hide().eq(linkNumber).show();
        });

        // make first menu item active
        // TODO: support URL hash history
        $topmenu2.find('> :first-child a').addClass('tabactive');
        $editUserDialog.prepend($topmenu2);

        // hide all sections but the first
        $('#edit_user_dialog .submenu-item').hide().eq(0).show();

        // scroll to the top
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    };

    $('input.autofocus').focus();
    $(checkboxes_sel).trigger('change');
    displayPasswordGenerateButton();
    if ($('#edit_user_dialog').length > 0) {
        addOrUpdateSubmenu();
    }

    var windowwidth = $(window).width();
    $('.jsresponsive').css('max-width', (windowwidth - 35) + 'px');
}

/**
 * Module export
 */
export {
    onloadServerPrivileges,
    teardownServerPrivileges
};
