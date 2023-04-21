import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { Functions } from '../modules/functions.ts';
import { CommonParams } from '../modules/common.ts';
import { Navigation } from '../modules/navigation.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';
import getImageTag from '../modules/functions/getImageTag.ts';

/**
 * Export privileges modal handler
 *
 * @param {object} data
 *
 * @param {JQuery} msgbox
 *
 */
function exportPrivilegesModalHandler (data, msgbox) {
    if (typeof data !== 'undefined' && data.success === true) {
        var modal = $('#exportPrivilegesModal');
        // Remove any previous privilege modal data, if any
        modal.find('.modal-body').first().html('');
        $('#exportPrivilegesModalLabel').first().html('Loading');
        modal.modal('show');
        modal.on('shown.bs.modal', function () {
            modal.find('.modal-body').first().html(data.message);
            $('#exportPrivilegesModalLabel').first().html(data.title);
            ajaxRemoveMessage(msgbox);
            // Attach syntax highlighted editor to export dialog
            Functions.getSqlEditor(modal.find('textarea'));
        });

        return;
    }

    ajaxShowMessage(data.error, false);
}

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const EditUserGroup = {
    /**
     * @param {MouseEvent} event
     */
    handleEvent: function (event) {
        const editUserGroupModal = document.getElementById('editUserGroupModal');
        const button = event.relatedTarget;
        const username = button.getAttribute('data-username');

        $.get(
            'index.php?route=/server/user-groups/edit-form',
            {
                'username': username,
                'server': CommonParams.get('server')
            },
            data => {
                if (typeof data === 'undefined' || data.success !== true) {
                    ajaxShowMessage(data.error, false, 'error');

                    return;
                }

                const modal = window.bootstrap.Modal.getInstance(editUserGroupModal);
                const modalBody = editUserGroupModal.querySelector('.modal-body');
                const saveButton = editUserGroupModal.querySelector('#editUserGroupModalSaveButton');

                modalBody.innerHTML = data.message;

                saveButton.addEventListener('click', () => {
                    const form = $(editUserGroupModal.querySelector('#changeUserGroupForm'));

                    $.post(
                        'index.php?route=/server/privileges',
                        form.serialize() + CommonParams.get('arg_separator') + 'ajax_request=1',
                        data => {
                            if (typeof data === 'undefined' || data.success !== true) {
                                ajaxShowMessage(data.error, false, 'error');

                                return;
                            }

                            const userGroup = form.serializeArray().find(el => el.name === 'userGroup').value;
                            // button -> td -> tr -> td.usrGroup
                            const userGroupTableCell = button.parentElement.parentElement.querySelector('.usrGroup');
                            userGroupTableCell.textContent = userGroup;
                        }
                    );

                    modal.hide();
                });
            }
        );
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const AccountLocking = {
    handleEvent: function () {
        const button = this;
        const isLocked = button.dataset.isLocked === 'true';
        const url = isLocked
            ? 'index.php?route=/server/privileges/account-unlock'
            : 'index.php?route=/server/privileges/account-lock';
        const params = {
            'username': button.dataset.userName,
            'hostname': button.dataset.hostName,
            'ajax_request': true,
            'server': CommonParams.get('server'),
        };

        $.post(url, params, data => {
            if (data.success === false) {
                ajaxShowMessage(data.error);

                return;
            }

            if (isLocked) {
                const lockIcon = getImageTag('s_lock', window.Messages.strLock, {}).toString();
                button.innerHTML = '<span class="text-nowrap">' + lockIcon + ' ' + window.Messages.strLock + '</span>';
                button.title = window.Messages.strLockAccount;
                button.dataset.isLocked = 'false';
            } else {
                const unlockIcon = getImageTag('s_unlock', window.Messages.strUnlock, {}).toString();
                button.innerHTML = '<span class="text-nowrap">' + unlockIcon + ' ' + window.Messages.strUnlock + '</span>';
                button.title = window.Messages.strUnlockAccount;
                button.dataset.isLocked = 'true';
            }

            ajaxShowMessage(data.message);
        });
    }
};

/**
 * Display a warning if there is already a user by the name entered as the username.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const AddUserLoginCheckUsername = {
    handleEvent: function () {
        var username = $(this).val();
        var $warning = $('#user_exists_warning');
        if ($('#select_pred_username').val() === 'userdefined' && username !== '') {
            var href = $('form[name=\'usersForm\']').attr('action');
            var params = {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'validate_username': true,
                'username': username
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
    }
};

/**
 * Indicating password strength
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const PasswordStrength = {
    handleEvent: function () {
        var meterObj = $('#password_strength_meter');
        var meterObjLabel = $('#password_strength');
        var username = $('input[name="username"]');
        Functions.checkPasswordStrength($(this).val(), meterObj, meterObjLabel, username.val());
    }
};

/**
 * Automatically switching to 'Use Text field' from 'No password' once start writing in text area
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const SwitchToUseTextField = {
    handleEvent: function () {
        if ($('#text_pma_pw').val() !== '') {
            $('#select_pred_password').val('userdefined');
        }
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const ChangePasswordStrength = {
    handleEvent: function () {
        var meterObj = $('#change_password_strength_meter');
        var meterObjLabel = $('#change_password_strength');
        Functions.checkPasswordStrength($(this).val(), meterObj, meterObjLabel, CommonParams.get('user'));
    }
};

/**
 * Display a notice if sha256_password is selected
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const ShowSha256PasswordNotice = {
    handleEvent: function () {
        var selectedPlugin = $(this).val();
        if (selectedPlugin === 'sha256_password') {
            $('#ssl_reqd_warning').show();
        } else {
            $('#ssl_reqd_warning').hide();
        }
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const RevokeUser = {
    /**
     * @param {Event} event
     */
    handleEvent: function (event) {
        event.preventDefault();

        var $thisButton = $(this);
        var $form = $('#usersForm');

        $thisButton.confirm(window.Messages.strDropUserWarning, $form.attr('action'), function (url) {
            var $dropUsersDbCheckbox = $('#dropUsersDbCheckbox');
            if ($dropUsersDbCheckbox.is(':checked')) {
                var isConfirmed = confirm(window.Messages.strDropDatabaseStrongWarning + '\n' + window.sprintf(window.Messages.strDoYouReally, 'DROP DATABASE'));
                if (! isConfirmed) {
                    // Uncheck the drop users database checkbox
                    $dropUsersDbCheckbox.prop('checked', false);
                }
            }

            ajaxShowMessage(window.Messages.strRemovingSelectedUsers);

            var argsep = CommonParams.get('arg_separator');
            $.post(url, $form.serialize() + argsep + 'delete=' + $thisButton.val() + argsep + 'ajax_request=true', function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    ajaxShowMessage(data.message);
                    // Refresh navigation, if we dropped some databases with the name
                    // that is the same as the username of the deleted user
                    if ($('#dropUsersDbCheckbox:checked').length) {
                        Navigation.reload();
                    }

                    // Remove the revoked user from the users list
                    $form.find('input:checkbox:checked').parents('tr').slideUp('medium', function () {
                        var thisUserInitial = ($(this).find('input:checkbox').val() as string).charAt(0).toUpperCase();
                        $(this).remove();

                        // If this is the last user with thisUserInitial, remove the link from #userAccountsPagination
                        if ($('#userRightsTable').find('input:checkbox[value^="' + thisUserInitial + '"], input:checkbox[value^="' + thisUserInitial.toLowerCase() + '"]').length === 0) {
                            $('#userAccountsPagination')
                                .find('.page-item > .page-link:contains(' + thisUserInitial + ')')
                                .parent('.page-item')
                                .addClass('disabled')
                                .html('<a class="page-link" href="#" tabindex="-1" aria-disabled="true">' + thisUserInitial + '</a>');
                        }

                        // Re-check the classes of each row
                        $form
                            .find('tbody').find('tr').each(function (index) {
                                if (index >= 0 && index % 2 === 0) {
                                    $(this).removeClass('odd').addClass('even');
                                } else if (index >= 0 && index % 2 !== 0) {
                                    $(this).removeClass('even').addClass('odd');
                                }
                            });

                        // update the checkall checkbox
                        $(Functions.checkboxesSel).trigger('change');
                    });
                } else {
                    ajaxShowMessage(data.error, false);
                }
            }); // end $.post()
        });
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const ExportPrivileges = {
    /**
     * @param {Event} event
     */
    handleEvent: function (event) {
        event.preventDefault();
        // can't export if no users checked
        if ($(this.form).find('input:checked').length === 0) {
            ajaxShowMessage(window.Messages.strNoAccountSelected, 2000, 'success');

            return;
        }

        var msgbox = ajaxShowMessage();
        var argsep = CommonParams.get('arg_separator');
        var serverId = CommonParams.get('server');
        var selectedUsers = $('#usersForm input[name*=\'selected_usr\']:checkbox').serialize();
        var postStr = selectedUsers + '&submit_mult=export' + argsep + 'ajax_request=true&server=' + serverId;

        $.post($(this.form).prop('action'), postStr, function (data) {
            exportPrivilegesModalHandler(data, msgbox);
        }); // end $.post
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const ExportUser = {
    /**
     * @param {Event} event
     */
    handleEvent: function (event) {
        event.preventDefault();
        var msgbox = ajaxShowMessage();
        $.get($(this).attr('href'), { 'ajax_request': true }, function (data) {
            exportPrivilegesModalHandler(data, msgbox);
        });
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const SslTypeToggle = {
    handleEvent: function () {
        var $div = $('#specified_div');
        if ($('#ssl_type_SPECIFIED').is(':checked')) {
            $div.find('input').prop('disabled', false);
        } else {
            $div.find('input').prop('disabled', true);
        }
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const SslPrivilegeToggle = {
    handleEvent: function () {
        var $div = $('#require_ssl_div');
        if ($(this).is(':checked')) {
            $div.find('input').prop('disabled', false);
            $('#ssl_type_SPECIFIED').trigger('change');
        } else {
            $div.find('input').prop('disabled', true);
        }
    }
};

/**
 * Create submenu for simpler interface
 */
function addOrUpdateSubmenu () {
    var $editUserDialog = $('#edit_user_dialog');
    if ($editUserDialog.length === 0) {
        return;
    }

    var $subNav = $('.nav-pills');
    var submenuLabel;
    var submenuLink;
    var linkNumber;

    // if submenu exists yet, remove it first
    if ($subNav.length > 0) {
        $subNav.remove();
    }

    // construct a submenu from the existing fieldsets
    $subNav = $('<ul></ul>').prop('class', 'nav nav-pills m-2');

    $('#edit_user_dialog .submenu-item').each(function () {
        submenuLabel = $(this).find('.js-submenu-label[data-submenu-label]').data('submenu-label');

        submenuLink = $('<a></a>')
            .prop('class', 'nav-link')
            .prop('href', '#')
            .html(submenuLabel);

        $('<li></li>')
            .prop('class', 'nav-item')
            .append(submenuLink)
            .appendTo($subNav);
    });

    // click handlers for submenu
    $subNav.find('a').on('click', function (e) {
        e.preventDefault();
        // if already active, ignore click
        if ($(this).hasClass('active')) {
            return;
        }

        $subNav.find('a').removeClass('active');
        $(this).addClass('active');

        // which section to show now?
        linkNumber = $subNav.find('a').index($(this));
        // hide all sections but the one to show
        $('#edit_user_dialog .submenu-item').hide().eq(linkNumber).show();
    });

    // make first menu item active
    // TODO: support URL hash history
    $subNav.find('> :first-child a').addClass('active');
    $editUserDialog.prepend($subNav);

    // hide all sections but the first
    $('#edit_user_dialog .submenu-item').hide().eq(0).show();

    // scroll to the top
    $('html, body').animate({ scrollTop: 0 }, 'fast');
}

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const SelectAllPrivileges = {
    /**
     * @param {Event} event
     */
    handleEvent: function (event) {
        const method = event.target.getAttribute('data-select-target');
        var options = $(method).first().children();
        options.each(function (_, obj: HTMLOptionElement) {
            obj.selected = true;
        });
    }
};

function setMaxWidth () {
    var windowWidth = $(window).width();
    $('.jsresponsive').css('max-width', (windowWidth - 35) + 'px');
}

/**
 * Validates the "add a user" form
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const CheckAddUser = {
    handleEvent: function () {
        const theForm = this;

        if (theForm.elements.hostname && theForm.elements.hostname.value === '') {
            alert(window.Messages.strHostEmpty);
            theForm.elements.hostname.focus();

            return false;
        }

        if ((theForm.elements.pred_username && theForm.elements.pred_username.value === 'userdefined') && theForm.elements.username.value === '') {
            alert(window.Messages.strUserEmpty);
            theForm.elements.username.focus();

            return false;
        }

        return Functions.checkPassword($(theForm));
    }
};

const selectPasswordRadioWhenChangingPassword = () => {
    $('#nopass_0').prop('checked', true);
};

/**
 * AJAX scripts for /server/privileges page.
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
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/privileges.js', function () {
    $('#fieldset_add_user_login').off('change', 'input[name=\'username\']');
    $(document).off('click', '#deleteUserCard .btn.ajax');
    $('#text_pma_change_pw').off('keyup');
    $('#text_pma_change_pw').off('change');
    $('#text_pma_change_pw2').off('change');

    const editUserGroupModal = document.getElementById('editUserGroupModal');
    if (editUserGroupModal) {
        editUserGroupModal.removeEventListener('show.bs.modal', EditUserGroup);
    }

    $(document).off('click', 'button.mult_submit[value=export]');
    $(document).off('click', 'a.export_user_anchor.ajax');
    $('button.jsAccountLocking').off('click');
    $('#dropUsersDbCheckbox').off('click');
    $(document).off('click', '.checkall_box');
    $(document).off('change', '#checkbox_SSL_priv');
    $(document).off('change', 'input[name="ssl_type"]');
    $(document).off('change', '#select_authentication_plugin');
});

AJAX.registerOnload('server/privileges.js', function () {
    $('#fieldset_add_user_login').on('change', 'input[name=\'username\']', AddUserLoginCheckUsername.handleEvent);
    $('#text_pma_pw').on('keyup', PasswordStrength.handleEvent);
    $('#text_pma_pw').on('input', SwitchToUseTextField.handleEvent);
    $('#text_pma_change_pw').on('keyup', ChangePasswordStrength.handleEvent);
    $('#text_pma_change_pw').on('change', selectPasswordRadioWhenChangingPassword);
    $('#text_pma_change_pw2').on('change', selectPasswordRadioWhenChangingPassword);
    $(document).on('change', '#select_authentication_plugin', ShowSha256PasswordNotice.handleEvent);
    $(document).on('click', '#deleteUserCard .btn.ajax', RevokeUser.handleEvent);

    const editUserGroupModal = document.getElementById('editUserGroupModal');
    if (editUserGroupModal) {
        editUserGroupModal.addEventListener('show.bs.modal', EditUserGroup);
    }

    $(document).on('click', 'button.mult_submit[value=export]', ExportPrivileges.handleEvent);

    // if exporting non-ajax, highlight anyways
    Functions.getSqlEditor($('textarea.export'));

    $(document).on('click', 'a.export_user_anchor.ajax', ExportUser.handleEvent);
    $('button.jsAccountLocking').on('click', AccountLocking.handleEvent);
    $(document).on('change', 'input[name="ssl_type"]', SslTypeToggle.handleEvent);

    $(document).on('change', '#checkbox_SSL_priv', SslPrivilegeToggle.handleEvent);
    $('#checkbox_SSL_priv').trigger('change');

    $('input.autofocus').trigger('focus');
    $(Functions.checkboxesSel).trigger('change');
    Functions.displayPasswordGenerateButton();
    addOrUpdateSubmenu();

    $('#select_priv_all').on('click', SelectAllPrivileges.handleEvent);
    $('#insert_priv_all').on('click', SelectAllPrivileges.handleEvent);
    $('#update_priv_all').on('click', SelectAllPrivileges.handleEvent);
    $('#references_priv_all').on('click', SelectAllPrivileges.handleEvent);

    setMaxWidth();

    $('#addUsersForm').on('submit', CheckAddUser.handleEvent);
    $('#copyUserForm').on('submit', CheckAddUser.handleEvent);
});
