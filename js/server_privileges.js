/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in server privilege pages
 *
 * @version $Id$
 */

/**
 * Validates the password field in a form
 *
 * @uses    PMA_messages['strPasswordEmpty']
 * @uses    PMA_messages['strPasswordNotSame']
 * @param   object   the form
 * @return  boolean  whether the field value is valid or not
 */
function checkPassword(the_form)
{
    // Did the user select 'no password'?
    if (typeof(the_form.elements['nopass']) != 'undefined'
     && the_form.elements['nopass'][0].checked) {
        return true;
    } else if (typeof(the_form.elements['pred_password']) != 'undefined'
     && (the_form.elements['pred_password'].value == 'none'
      || the_form.elements['pred_password'].value == 'keep')) {
        return true;
    }

    var password = the_form.elements['pma_pw'];
    var password_repeat = the_form.elements['pma_pw2'];
    var alert_msg = false;

    if (password.value == '') {
        alert_msg = PMA_messages['strPasswordEmpty'];
    } else if (password.value != password_repeat.value) {
        alert_msg = PMA_messages['strPasswordNotSame'];
    }

    if (alert_msg) {
        alert(alert_msg);
        password.value  = '';
        password_repeat.value = '';
        password.focus();
        return false;
    }

    return true;
} // end of the 'checkPassword()' function


/**
 * Validates the "add a user" form
 *
 * @return  boolean  whether the form is validated or not
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

    return checkPassword(the_form);
} // end of the 'checkAddUser()' function


/**
 * Generate a new password and copy it to the password input areas
 *
 * @param   object   the form that holds the password fields
 *
 * @return  boolean  always true
 */
function suggestPassword(passwd_form) {
    // restrict the password to just letters and numbers to avoid problems:
    // "editors and viewers regard the password as multiple words and
    // things like double click no longer work"
    var pwchars = "abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWYXZ";
    var passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
    var passwd = passwd_form.generated_pw;
    passwd.value = '';

    for ( i = 0; i < passwordlength; i++ ) {
        passwd.value += pwchars.charAt( Math.floor( Math.random() * pwchars.length ) )
    }
    passwd_form.text_pma_pw.value = passwd.value;
    passwd_form.text_pma_pw2.value = passwd.value;
    return true;
}

/**
 * Add all AJAX scripts for server_privileges page here.
 *
 * Actions ajaxified here:
 * Add a new user
 * Revoke a user
 * Edit privileges
 * Export privileges
 * Paginate table of users - use ajax, replace #usersForm
 * Flush privileges
 */

$(document).ready(function() {
    
    /**
     * Attach AJAX event handlers to 'Add a New User'
     *
     * @todo create standard options for dialog boxes
     */
    $("#fieldset_add_user a").live("click", function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        var button_options = {};
        button_options[PMA_messages['strCreateUser']] = function() {

                                                        var the_form = $(this).find("#addUsersForm");

                                                        if( ! checkAddUser($(the_form).get(0)) ) {
                                                            PMA_ajaxShowMessage(PMA_messages['strFormEmpty']);
                                                            return false;
                                                        }

                                                        //We also need to post the value of the submit button in order to get this to work correctly
                                                        $.post($(the_form).attr('action'), $(the_form).serialize() + "&adduser_submit=" + $(this).find("input[name=adduser_submit]").attr('value'), function(data) {
                                                            if(data.success == true) {
                                                                $("#add_user_dialog").dialog("close").remove();
                                                                PMA_ajaxShowMessage(data.message);
                                                            }
                                                            else {
                                                                PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : "+data.error, "7000");
                                                            }
                                                        })
                                                    };
        button_options[PMA_messages['strCancel']] = function() { $(this).dialog("close").remove(); }

        $.get($(this).attr("href"), {'ajax_request':true}, function(data) {
            $('<div id="add_user_dialog"></div>')
            .prepend(data)
            .find("#fieldset_add_user_footer").hide() //showing the "Go" and "Create User" buttons together will confuse the user
            .end()
            .find("#addUsersForm").append('<input type="hidden" name="ajax_request" value="true" />')
            .end()
            .dialog({
                title: PMA_messages['strAddNewUser'],
                width: 800,
                modal: true,
                buttons: button_options
            }); //dialog options end
        });

    });//end of Add New User AJAX event handler


    /**
     * Attach Ajax event handler to 'Reload Privileges' anchor
     */
    $("#reload_privileges_anchor").live("click", function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strReloadingPrivileges']);
        $.get($(this).attr("href"), {'ajax_request': true}, function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
            }
            else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    }); //end of Reload Privileges Ajax event handler

    //Revoke User
    $("#fieldset_delete_user_footer #buttonGo").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strRemovingSelectedUsers']);
        
        $.post($("#usersForm").attr('action'), $("#usersForm").serialize() + "&delete=" + $(this).attr('value') + "&ajax_request=true", function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
                $("#usersForm").find("input:checkbox:checked").parents("tr").slideUp("medium", function() {
                    $(this).remove();
                })
            }
            else {
                PMA_ajaxShowMessage(data.error);
            }
        })
    }) // end Revoke User

    //Edit User
    $(".edit_user_anchor").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        var button_options = {};
        button_options[PMA_messages['strCancel']] = function() { $(this).dialog("close").remove(); }

        $.get($(this).attr('href'), {'ajax_request':true, 'edit_user_dialog': true}, function(data) {
            $('<div id="edit_user_dialog"></div>')
            .append(data)
            .dialog({
                width: 900,
                buttons: button_options
            })
        })
    })

    $("#edit_user_dialog").find("form").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        var curr_submit_name = $(this).find('.tblFooters').find('input:submit').attr('name');
        var curr_submit_value = $(this).find('.tblFooters').find('input:submit').val();

        $.post($(this).attr('action'), $(this).serialize() + '&' + curr_submit_name + '=' + curr_submit_value, function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
                $("#edit_user_dialog").dialog("close").remove();
            }
            else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    })
    //end Edit user

    //Export Privileges
    $(".export_user_anchor").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        var button_options = {};
        button_options[PMA_messages['strClose']] = function() { $(this).dialog("close").remove(); }

        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            $('<div id="export_dialog"></div>')
            .prepend(data)
            .dialog({
                width : 500,
                buttons: button_options
            });
        }) //end $.get
    }) //end export privileges

    //Paginate Users Table
    $("#initials_table").find("a").live('click', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage();

        $.get($(this).attr('href'), {'ajax_request' : true}, function(data) {
            $("#usersForm")
            .hide("medium")
            .siblings("#initials_table")
            .after(data)
            .show("medium")
            .end()
            .remove();
            $("#initials_table").siblings("h2").not(":first").remove();
        })
    })

}); //end $(document).ready()