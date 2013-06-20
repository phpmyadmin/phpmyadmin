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
    if (the_form.elements['pred_hostname'].value == 'userdefined' && the_form.elements['hostname'].value == '') {
        alert(PMA_messages['strHostEmpty']);
        the_form.elements['hostname'].focus();
        return false;
    }

    if (the_form.elements['pred_username'].value == 'userdefined' && the_form.elements['username'].value == '') {
        alert(PMA_messages['strUserEmpty']);
        the_form.elements['username'].focus();
        return false;
    }

    return PMA_checkPassword($(the_form));
} // end of the 'checkAddUser()' function

/**
 * When a new user is created and retrieved over Ajax, append the user's row to
 * the user's table
 *
 * @param new_user_string         the html for the new user's row
 * @param new_user_initial        the first alphabet of the user's name
 * @param new_user_initial_string html to replace the initial for pagination
 */
function appendNewUser(new_user_string, new_user_initial, new_user_initial_string)
{
    //Append the newly retrieved user to the table now

    //Calculate the index for the new row
    var $curr_last_row = $("#usersForm").find('tbody').find('tr:last');
    var $curr_first_row = $("#usersForm").find('tbody').find('tr:first');
    var first_row_initial = $curr_first_row.find('label').html().substr(0, 1).toUpperCase();
    var curr_shown_initial = $curr_last_row.find('label').html().substr(0, 1).toUpperCase();
    var curr_last_row_index_string = $curr_last_row.find('input:checkbox').attr('id').match(/\d+/)[0];
    var curr_last_row_index = parseFloat(curr_last_row_index_string);
    var new_last_row_index = curr_last_row_index + 1;
    var new_last_row_id = 'checkbox_sel_users_' + new_last_row_index;
    var is_show_all = (first_row_initial != curr_shown_initial) ? true : false;

    //Append to the table and set the id/names correctly
    if ((curr_shown_initial == new_user_initial) || is_show_all) {
        $(new_user_string)
        .insertAfter($curr_last_row)
        .find('input:checkbox')
        .attr('id', new_last_row_id)
        .val(function() {
            //the insert messes up the &amp;27; part. let's fix it
            return $(this).val().replace(/&/,'&amp;');
        })
        .end()
        .find('label')
        .attr('for', new_last_row_id)
        .end();
    }

    //Let us sort the table alphabetically
    $("#usersForm").find('tbody').PMA_sort_table('label');

    $("#initials_table").find('td:contains('+new_user_initial+')')
    .html(new_user_initial_string);

    //update the checkall checkbox
    $(checkboxes_sel).trigger("change");
}

function addUser($form)
{
    if (! checkAddUser($form.get(0))) {
        return false;
    }

    //We also need to post the value of the submit button in order to get this to work correctly
    $.post($form.attr('action'), $form.serialize() + "&adduser_submit=" + $("input[name=adduser_submit]").val(), function(data) {
        if (data.success == true) {
            // Refresh navigation, if we created a database with the name
            // that is the same as the username of the new user
            if ($('#add_user_dialog #createdb-1:checked').length) {
                PMA_reloadNavigation();
            }

            $('#page_content').show();
            $("#add_user_dialog").remove();

            PMA_ajaxShowMessage(data.message);
            $("#result_query").remove();
            $('#page_content').prepend(data.sql_query);
            $("#result_query").css({
                'margin-top' : '0.5em'
            });

            //Remove the empty notice div generated due to a NULL query passed to PMA_getMessage()
            var $notice_class = $("#result_query").find('.notice');
            if ($notice_class.text() == '') {
                $notice_class.remove();
            }
            if ($('#fieldset_add_user a.ajax').attr('name') == 'db_specific') {

                /*process the fieldset_add_user attribute and get the val of privileges*/
                var url = $('#fieldset_add_user a.ajax').attr('rel');

                if (url.substring(url.length - 23, url.length) == "&goto=db_operations.php") {
                    url = url.substring(0, url.length - 23);
                }
                url = url + "&ajax_request=true&db_specific=true";

                /* post request for get the updated userForm table */
                $.post($form.attr('action'), url, function(priv_data) {

                    /*Remove the old userForm table*/
                    if ($('#userFormDiv').length != 0) {
                        $('#userFormDiv').remove();
                    } else {
                        $("#usersForm").remove();
                    }
                    if (priv_data.success == true) {
                        $('<div id="userFormDiv"></div>')
                            .html(priv_data.user_form)
                            .insertAfter('#result_query');
                    } else {
                        PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + priv_data.error, false);
                    }
                });
            } else {
                appendNewUser(data.new_user_string, data.new_user_initial, data.new_user_initial_string);
            }
        } else {
            PMA_ajaxShowMessage(data.error, false);
        }
    });
}

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
AJAX.registerTeardown('server_privileges.js', function() {
    $("#fieldset_add_user a.ajax").die("click");
    $('form[name=usersForm]').unbind('submit');
    $("#fieldset_delete_user_footer #buttonGo.ajax").die('click');
    $("a.edit_user_anchor.ajax").die('click');
    $("#edit_user_dialog").find("form.ajax").die('submit');
    $("button.mult_submit[value=export]").die('click');
    $("a.export_user_anchor.ajax").die('click');
    $("#initials_table").find("a.ajax").die('click');
    $('#checkbox_drop_users_db').unbind('click');
});

AJAX.registerOnload('server_privileges.js', function() {
    /**
     * AJAX event handler for 'Add a New User'
     *
     * @see         PMA_ajaxShowMessage()
     * @see         appendNewUser()
     * @memberOf    jQuery
     * @name        add_user_click
     *
     */
    $("#fieldset_add_user a.ajax").live("click", function(event) {
        /** @lends jQuery */
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();

        $.get($(this).attr("href"), {'ajax_request':true}, function(data) {
            if (data.success == true) {
                $('#page_content').hide();
                var $div = $('#add_user_dialog');
                if ($div.length == 0) {
                    $div = $('<div id="add_user_dialog" style="margin: 0.5em;"></div>')
                        .insertBefore('#page_content');
                } else {
                    $div.empty();
                }
                $div.html(data.message)
                    .find("form[name=usersForm]")
                    .append('<input type="hidden" name="ajax_request" value="true" />')
                    .end();
                displayPasswordGenerateButton();
                PMA_showHints($div);
                PMA_ajaxRemoveMessage($msgbox);
                $div.find("input.autofocus").focus();

                $div.find('form[name=usersForm]').bind('submit', function (event) {
                    event.preventDefault();
                    addUser($(this));
                });
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()

    });//end of Add New User AJAX event handler

    /**
     * AJAX handler for 'Revoke User'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        revoke_user_click
     */
    $("#fieldset_delete_user_footer #buttonGo.ajax").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strRemovingSelectedUsers']);

        var $form = $("#usersForm");

        $.post($form.attr('action'), $form.serialize() + "&delete=" + $(this).val() + "&ajax_request=true", function(data) {
            if (data.success == true) {
                PMA_ajaxShowMessage(data.message);
                // Refresh navigation, if we droppped some databases with the name
                // that is the same as the username of the deleted user
                if ($('#checkbox_drop_users_db:checked').length) {
                    PMA_reloadNavigation();
                }
                //Remove the revoked user from the users list
                $form.find("input:checkbox:checked").parents("tr").slideUp("medium", function() {
                    var this_user_initial = $(this).find('input:checkbox').val().charAt(0).toUpperCase();
                    $(this).remove();

                    //If this is the last user with this_user_initial, remove the link from #initials_table
                    if ($("#tableuserrights").find('input:checkbox[value^=' + this_user_initial + ']').length == 0) {
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
    }); // end Revoke User

    /**
     * AJAX handler for 'Edit User'
     *
     * @see         PMA_ajaxShowMessage()
     *
     */

    /**
     * Step 1: Load Edit User Dialog
     * @memberOf    jQuery
     * @name        edit_user_click
     */
    $("a.edit_user_anchor.ajax").live('click', function(event) {
        /** @lends jQuery */
        event.preventDefault();

        var $msgbox = PMA_ajaxShowMessage();

        $(this).parents('tr').addClass('current_row');

        var token = $(this).parents('form').find('input[name="token"]').val();
        $.get(
            $(this).attr('href'),
            {
                'ajax_request':true,
                'edit_user_dialog': true,
                'token': token
            },
            function(data) {
                if (data.success == true) {
                    $('#page_content').hide();
                    var $div = $('#edit_user_dialog');
                    if ($div.length == 0) {
                        $div = $('<div id="edit_user_dialog" style="margin: 0.5em;"></div>')
                            .insertBefore('#page_content');
                    } else {
                        $div.empty();
                    }
                    $div.html(data.message);
                    displayPasswordGenerateButton();
                    PMA_ajaxRemoveMessage($msgbox);
                    PMA_showHints($div);
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        ); // end $.get()
    });

    /**
     * Step 2: Submit the Edit User Dialog
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        edit_user_submit
     */
    $("#edit_user_dialog").find("form.ajax").live('submit', function(event) {
        /** @lends jQuery */
        event.preventDefault();

        var $t = $(this);

        if ($t.is('.copyUserForm') && ! PMA_checkPassword($t)) {
            return false;
        }

        PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

        $t.append('<input type="hidden" name="ajax_request" value="true" />');

        /**
         * @var curr_submit_name    name of the current button being submitted
         */
        var curr_submit_name = $t.find('.tblFooters').find('input:submit').attr('name');

        /**
         * @var curr_submit_value    value of the current button being submitted
         */
        var curr_submit_value = $t.find('.tblFooters').find('input:submit').val();

        // If any option other than 'keep the old one'(option 4) is chosen, we need to remove 
        // the old one from the table.
        var $row_to_remove;
        if (curr_submit_name == 'change_copy'
                && $('input[name=mode]:checked', '#fieldset_mode').val() != '4') {
            var old_username = $t.find('input[name="old_username"]').val();
            var old_hostname = $t.find('input[name="old_hostname"]').val();
            $('#usersForm tbody tr').each(function() {
                var $tr = $(this);
                if ($tr.find('td:nth-child(2) label').text() == old_username
                        && $tr.find('td:nth-child(3)').text() == old_hostname) {
                    $row_to_remove = $tr;
                    return false;
                }
            });
        }

        $.post($t.attr('action'), $t.serialize() + '&' + curr_submit_name + '=' + curr_submit_value, function(data) {
            if (data.success == true) {
                $('#page_content').show();
                $("#edit_user_dialog").remove();

                PMA_ajaxShowMessage(data.message);

                if (data.sql_query) {
                    $("#result_query").remove();
                    $('#page_content').prepend(data.sql_query);
                    $("#result_query").css({
                        'margin-top' : '0.5em'
                    });
                    var $notice_class = $("#result_query").find('.notice');
                    if ($notice_class.text() == '') {
                        $notice_class.remove();
                    }
                } //Show SQL Query that was executed

                // Remove the old row if the old user is deleted
                if ($row_to_remove != null) {
                    $row_to_remove.remove();
                }

                //Append new user if necessary
                if (data.new_user_string) {
                    appendNewUser(data.new_user_string, data.new_user_initial, data.new_user_initial_string);
                }

                //Check if we are on the page of the db-specific privileges
                var db_priv_page = !!($('#dbspecificuserrights').length); // the "!!" part is merely there to ensure a value of type boolean
                // we always need to reload on the db-specific privilege page
                // and on the global page when adjusting global privileges,
                // but not on the global page when adjusting db-specific privileges.
                var reload_privs = false;
                if (data.db_specific_privs == false || (db_priv_page == data.db_specific_privs)) {
                    reload_privs = true;
                }
                if (data.db_wildcard_privs) {
                    reload_privs = false;
                }

                //Change privileges, if they were edited and need to be reloaded
                if (data.new_privileges && reload_privs) {
                    $("#usersForm")
                    .find('.current_row')
                    .find('code')
                    .html(data.new_privileges);
                }

                $("#usersForm")
                .find('.current_row')
                .removeClass('current_row');
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });
    //end Edit user

    /**
     * AJAX handler for 'Export Privileges'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        export_user_click
     */
    $("button.mult_submit[value=export]").live('click', function(event) {
        event.preventDefault();
        // can't export if no users checked
        if ($(this.form).find("input:checked").length == 0) {
            return;
        }
        var $msgbox = PMA_ajaxShowMessage();
        var button_options = {};
        button_options[PMA_messages['strClose']] = function() {
            $(this).dialog("close");
        };
        $.post(
            $(this.form).prop('action'),
            $(this.form).serialize() + '&submit_mult=export&ajax_request=true',
            function(data) {
                if (data.success == true) {
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
                    // Attach syntax highlited editor to export dialog
                    if (typeof CodeMirror != 'undefined') {
                        CodeMirror.fromTextArea(
                            $ajaxDialog.find('textarea')[0],
                            {
                                lineNumbers: true,
                                matchBrackets: true,
                                indentUnit: 4,
                                mode: "text/x-mysql"
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
    if ($("textarea.export").length > 0
        && typeof CodeMirror != 'undefined')
    {
        CodeMirror.fromTextArea(
            $('textarea.export')[0],
            {
                lineNumbers: true,
                matchBrackets: true,
                indentUnit: 4,
                mode: "text/x-mysql"
            }
        );
    }

    $("a.export_user_anchor.ajax").live('click', function(event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        /**
         * @var button_options  Object containing options for jQueryUI dialog buttons
         */
        var button_options = {};
        button_options[PMA_messages['strClose']] = function() {
            $(this).dialog("close");
        };
        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            if (data.success == true) {
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
                // Attach syntax highlited editor to export dialog
                if (typeof CodeMirror != 'undefined') {
                    CodeMirror.fromTextArea(
                        $ajaxDialog.find('textarea')[0],
                        {
                            lineNumbers: true,
                            matchBrackets: true,
                            indentUnit: 4,
                            mode: "text/x-mysql"
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
    $("#initials_table").find("a.ajax").live('click', function(event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), {'ajax_request' : true}, function(data) {
            if (data.success == true) {
                PMA_ajaxRemoveMessage($msgbox);
                // This form is not on screen when first entering Privileges
                // if there are more than 50 users
                $("div.notice").remove();
                $("#usersForm").hide("medium").remove();
                $("#fieldset_add_user").hide("medium").remove();
                $("#initials_table")
                    .after(data.message).show("medium")
                    .siblings("h2").not(":first").remove();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get
    }); // end of the paginate users table

    /*
     * Additional confirmation dialog after clicking
     * 'Drop the databases...'
     */
    $('#checkbox_drop_users_db').click(function() {
        var $this_checkbox = $(this);
        if ($this_checkbox.is(':checked')) {
            var is_confirmed = confirm(PMA_messages['strDropDatabaseStrongWarning'] + '\n' + $.sprintf(PMA_messages['strDoYouReally'], 'DROP DATABASE'));
            if (! is_confirmed) {
                $this_checkbox.prop('checked', false);
            }
        }
    });

    displayPasswordGenerateButton();
});
