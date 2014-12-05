/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used in server privilege pages
 * @name            Server Privileges
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Validates the "add a user" form
 *
 * @return boolean  whether the form is validated or not
 */
function checkAddUser(the_form)
{
    if (the_form.elements.pred_hostname.value == 'userdefined' && the_form.elements.hostname.value === '') {
        alert(PMA_messages.strHostEmpty);
        the_form.elements.hostname.focus();
        return false;
    }

    if (the_form.elements.pred_username.value == 'userdefined' && the_form.elements.username.value === '') {
        alert(PMA_messages.strUserEmpty);
        the_form.elements.username.focus();
        return false;
    }

    return PMA_checkPassword($(the_form));
} // end of the 'checkAddUser()' function

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
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_privileges.js', function () {
    $("#fieldset_add_user_login input[name='username']").die("focusout");
    $("#fieldset_delete_user_footer #buttonGo.ajax").die('click');
    $("a.edit_user_group_anchor.ajax").die('click');
    $("button.mult_submit[value=export]").die('click');
    $("a.export_user_anchor.ajax").die('click');
    $("#initials_table").find("a.ajax").die('click');
    $('#checkbox_drop_users_db').unbind('click');
    $(".checkall_box").die("click");
});

AJAX.registerOnload('server_privileges.js', function () {
    /**
     * Display a warning if there is already a user by the name entered as the username.
     */
    $("#fieldset_add_user_login input[name='username']").live("focusout", function () {
        var username = $(this).val();
        var $warning = $("#user_exists_warning");
        if ($("#select_pred_username").val() == 'userdefined' && username !== '') {
            var href = $("form[name='usersForm']").attr('action');
            var params = {
                'ajax_request' : true,
                'token' : PMA_commonParams.get('token'),
                'server' : PMA_commonParams.get('server'),
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
     * AJAX handler for 'Revoke User'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        revoke_user_click
     */
    $("#fieldset_delete_user_footer #buttonGo.ajax").live('click', function (event) {
        event.preventDefault();

        var $thisButton = $(this);
        var $form = $("#usersForm");

        $thisButton.PMA_confirm(PMA_messages.strDropUserWarning, $form.attr('action'), function (url) {

            $drop_users_db_checkbox = $("#checkbox_drop_users_db");
            if ($drop_users_db_checkbox.is(':checked')) {
                var is_confirmed = confirm(PMA_messages.strDropDatabaseStrongWarning + '\n' + PMA_sprintf(PMA_messages.strDoYouReally, 'DROP DATABASE'));
                if (! is_confirmed) {
                    // Uncheck the drop users database checkbox
                    $drop_users_db_checkbox.prop('checked', false);
                }
            }

            PMA_ajaxShowMessage(PMA_messages.strRemovingSelectedUsers);

            $.post(url, $form.serialize() + "&delete=" + $thisButton.val() + "&ajax_request=true", function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    // Refresh navigation, if we droppped some databases with the name
                    // that is the same as the username of the deleted user
                    if ($('#checkbox_drop_users_db:checked').length) {
                        PMA_reloadNavigation();
                    }
                    //Remove the revoked user from the users list
                    $form.find("input:checkbox:checked").parents("tr").slideUp("medium", function () {
                        var this_user_initial = $(this).find('input:checkbox').val().charAt(0).toUpperCase();
                        $(this).remove();

                        //If this is the last user with this_user_initial, remove the link from #initials_table
                        if ($("#tableuserrights").find('input:checkbox[value^="' + this_user_initial + '"], input:checkbox[value^="' + this_user_initial.toLowerCase() + '"]').length === 0) {
                            $("#initials_table").find('td > a:contains(' + this_user_initial + ')').parent('td').html(this_user_initial);
                        }

                        //Re-check the classes of each row
                        $form
                        .find('tbody').find('tr:odd')
                        .removeClass('even').addClass('odd')
                        .end()
                        .find('tr:even')
                        .removeClass('odd').addClass('even');

                        //update the checkall checkbox
                        $(checkboxes_sel).trigger("change");
                    });
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }); // end $.post()

        });

    }); // end Revoke User

    $("a.edit_user_group_anchor.ajax").live('click', function (event) {
        event.preventDefault();
        $(this).parents('tr').addClass('current_row');
        var token = $(this).parents('form').find('input[name="token"]').val();
        var $msg = PMA_ajaxShowMessage();
        $.get(
            $(this).attr('href'),
            {
                'ajax_request': true,
                'edit_user_group_dialog': true,
                'token': token
            },
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    var buttonOptions = {};
                    buttonOptions[PMA_messages.strGo] = function () {
                        var usrGroup = $('#changeUserGroupDialog')
                            .find('select[name="userGroup"]')
                            .val();
                        var $message = PMA_ajaxShowMessage();
                        $.get(
                            'server_privileges.php',
                            $('#changeUserGroupDialog').find('form').serialize() + '&ajax_request=1',
                            function (data) {
                                PMA_ajaxRemoveMessage($message);
                                if (typeof data !== 'undefined' && data.success === true) {
                                    $("#usersForm")
                                        .find('.current_row')
                                        .removeClass('current_row')
                                        .find('.usrGroup')
                                        .text(usrGroup);
                                } else {
                                    PMA_ajaxShowMessage(data.error, false);
                                    $("#usersForm")
                                        .find('.current_row')
                                        .removeClass('current_row');
                                }
                            }
                        );
                        $(this).dialog("close");
                    };
                    buttonOptions[PMA_messages.strClose] = function () {
                        $(this).dialog("close");
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
                    $("#usersForm")
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
    $("button.mult_submit[value=export]").live('click', function (event) {
        event.preventDefault();
        // can't export if no users checked
        if ($(this.form).find("input:checked").length === 0) {
            return;
        }
        var $msgbox = PMA_ajaxShowMessage();
        var button_options = {};
        button_options[PMA_messages.strClose] = function () {
            $(this).dialog("close");
        };
        $.post(
            $(this.form).prop('action'),
            $(this.form).serialize() + '&submit_mult=export&ajax_request=true',
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    var $ajaxDialog = $('<div />')
                    .append(data.message)
                    .dialog({
                        title: data.title,
                        width: 500,
                        buttons: button_options,
                        close: function () {
                            $(this).remove();
                        }
                    });
                    PMA_ajaxRemoveMessage($msgbox);
                    // Attach syntax highlighted editor to export dialog
                    if (typeof CodeMirror != 'undefined') {
                        CodeMirror.fromTextArea(
                            $ajaxDialog.find('textarea')[0],
                            {
                                lineNumbers: true,
                                matchBrackets: true,
                                indentUnit: 4,
                                mode: "text/x-mysql",
                                lineWrapping: true
                            }
                        );
                    }
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        ); //end $.post
    });
    // if exporting non-ajax, highlight anyways
    if ($("textarea.export").length > 0 && typeof CodeMirror != 'undefined') {
        CodeMirror.fromTextArea(
            $('textarea.export')[0],
            {
                lineNumbers: true,
                matchBrackets: true,
                indentUnit: 4,
                mode: "text/x-mysql",
                lineWrapping: true
            }
        );
    }

    $("a.export_user_anchor.ajax").live('click', function (event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        /**
         * @var button_options  Object containing options for jQueryUI dialog buttons
         */
        var button_options = {};
        button_options[PMA_messages.strClose] = function () {
            $(this).dialog("close");
        };
        $.get($(this).attr('href'), {'ajax_request': true}, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                var $ajaxDialog = $('<div />')
                .append(data.message)
                .dialog({
                    title: data.title,
                    width: 500,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
                PMA_ajaxRemoveMessage($msgbox);
                // Attach syntax highlighted editor to export dialog
                if (typeof CodeMirror != 'undefined') {
                    CodeMirror.fromTextArea(
                        $ajaxDialog.find('textarea')[0],
                        {
                            lineNumbers: true,
                            matchBrackets: true,
                            indentUnit: 4,
                            mode: "text/x-mysql",
                            lineWrapping: true
                        }
                    );
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); //end $.get
    }); //end export privileges

    /**
     * AJAX handler to Paginate the Users Table
     *
     * @see         PMA_ajaxShowMessage()
     * @name        paginate_users_table_click
     * @memberOf    jQuery
     */
    $("#initials_table").find("a.ajax").live('click', function (event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), {'ajax_request' : true}, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxRemoveMessage($msgbox);
                // This form is not on screen when first entering Privileges
                // if there are more than 50 users
                $("div.notice").remove();
                $("#usersForm").hide("medium").remove();
                $("#fieldset_add_user").hide("medium").remove();
                $("#initials_table")
                    .prop("id", "initials_table_old")
                    .after(data.message).show("medium")
                    .siblings("h2").not(":first").remove();
                // prevent double initials table
                $("#initials_table_old").remove();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get
    }); // end of the paginate users table

    /*
     * Create submenu for simpler interface
     */
    var addOrUpdateSubmenu = function () {
        var $topmenu2 = $("#topmenu2"),
            $edit_user_dialog = $("#edit_user_dialog"),
            submenu_label,
            submenu_link,
            link_number;

        // if submenu exists yet, remove it first
        if ($topmenu2.length > 0) {
            $topmenu2.remove();
        }

        // construct a submenu from the existing fieldsets
        $topmenu2 = $("<ul/>").prop("id", "topmenu2");

        $("#edit_user_dialog .submenu-item").each(function () {
            submenu_label = $(this).find("legend[data-submenu-label]").data("submenu-label");

            submenu_link = $("<a/>")
            .prop("href", "#")
            .html(submenu_label);

            $("<li/>")
            .append(submenu_link)
            .appendTo($topmenu2);
        });

        // click handlers for submenu
        $topmenu2.find("a").click(function (e) {
            e.preventDefault();
            // if already active, ignore click
            if ($(this).hasClass("tabactive")) {
                return;
            }
            $topmenu2.find("a").removeClass("tabactive");
            $(this).addClass("tabactive");

            // which section to show now?
            link_number = $topmenu2.find("a").index($(this));
            // hide all sections but the one to show
            $("#edit_user_dialog .submenu-item").hide().eq(link_number).show();
        });

        // make first menu item active
        // TODO: support URL hash history
        $topmenu2.find("> :first-child a").addClass("tabactive");
        $edit_user_dialog.prepend($topmenu2);

        // hide all sections but the first
        $("#edit_user_dialog .submenu-item").hide().eq(0).show();

        // scroll to the top
        $('html, body').animate({scrollTop: 0}, 'fast');
    };

    $("input.autofocus").focus();
    $(checkboxes_sel).trigger("change");
    displayPasswordGenerateButton();
    if ($("#edit_user_dialog").length > 0) {
        addOrUpdateSubmenu();
    }
});
