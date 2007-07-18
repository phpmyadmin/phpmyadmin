/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used for password change form
 *
 * @version $Id$
 */

/**
 * Validates the password field in a form
 *
 * @param   object   the form
 *
 * @return  boolean  whether the field value is valid or not
 */
function checkPassword(the_form)
{
    // Gets the elements pointers
    if (the_form.name == 'addUserForm' || the_form.name == 'chgPassword') {
        var pswd_index = 1;
        var pswd1_name = 'pma_pw';
        var pswd2_name = 'pma_pw2';
    } else {
        pswd_index     = 2;
        pswd1_name     = 'new_pw';
        pswd2_name     = 'new_pw2';
    }

    // Validates
    if (the_form.elements['nopass'][pswd_index].checked) {
        if (the_form.elements[pswd1_name].value == '') {
            alert(jsPasswordEmpty);
            the_form.elements[pswd2_name].value = '';
            the_form.elements[pswd1_name].focus();
            return false;
        } else if (the_form.elements[pswd1_name].value != the_form.elements[pswd2_name].value) {
            alert(jsPasswordNotSame);
            the_form.elements[pswd1_name].value  = '';
            the_form.elements[pswd2_name].value = '';
            the_form.elements[pswd1_name].focus();
            return false;
        } // end if...else if
    } // end if

    return true;
} // end of the 'checkPassword()' function


/**
 * Validates the "add an user" form
 *
 * @return  boolean  whether the form is validated or not
 */
function checkAddUser()
{
    var the_form = document.forms['addUserForm'];

    if (the_form.elements['anyhost'][1].checked && the_form.elements['host'].value == '') {
        alert(jsHostEmpty);
        the_form.elements['host'].focus();
        return false;
    }

    if (the_form.elements['anyuser'][1].checked && the_form.elements['pma_user'].value == '') {
        alert(jsUserEmpty);
        the_form.elements['pma_user'].focus();
        return false;
    }

    return checkPassword(the_form);
} // end of the 'checkAddUser()' function


/**
 * Validates the "update a profile" form
 *
 * @return  boolean  whether the form is validated or not
 */
function checkUpdProfile()
{
    var the_form = document.forms['updUserForm'];

    if (the_form.elements['anyhost'][1].checked && the_form.elements['new_server'].value == '') {
        alert(jsHostEmpty);
        the_form.elements['new_server'].focus();
        return false;
    }

    if (the_form.elements['anyuser'][1].checked && the_form.elements['new_user'].value == '') {
        alert(jsUserEmpty);
        the_form.elements['new_user'].focus();
        return false;
    }

    return checkPassword(the_form);
} // end of the 'checkUpdProfile()' function


/**
 * Gets the list of selected options in combo
 *
 * @param   object  the form to check
 *
 * @return  string  the list of selected options
 */
function getSelected(the_field) {
    var the_list = '';
    var opts     = the_field.options;
    var opts_cnt = opts.length;

    for (var i = 0; i < opts_cnt; i++) {
        if (opts[i].selected) {
            the_list += opts[i].text + ', ';
        }
    } // end for

    return the_list.substring(0, the_list.length - 2);
} // end of the 'getSelected()' function


/**
 * Reloads the page to get tables names in a database or fields names in a
 * table
 *
 * @param  object  the input text box to build the query from
 */
function change(the_field) {
    var l        = location.href;
    var lpos     = l.indexOf('?lang');
    var box_name = the_field.name;
    var the_form = the_field.form.elements;
    var sel_idx  = null;

    if (box_name == 'newdb') {
        the_form['anydb'][0].checked = true;
        the_form['anytable'][0].checked = true;
        the_form['anycolumn'][0].checked = true;
        if (typeof(the_form['dbgrant']) != 'undefined') {
            the_form['dbgrant'].selectedIndex = -1;
        }
        if (typeof(the_form['tablegrant']) != 'undefined') {
            the_form['tablegrant'].selectedIndex = -1;
        }
        if (typeof(the_form['colgrant']) != 'undefined') {
            the_form['colgrant'].selectedIndex = -1;
        }
    }
    else {
        if (lpos <= 0) {
            l        += '?lang=' + the_form['lang'].value
                     +  '&convcharset=' . the_form['convcharset'].value
                     +  '&server=' + the_form['server'].value
                     +  '&grants=1'
                     +  '&host=' + escape(the_form['host'].value)
                     +  '&pma_user=' + escape(the_form['pma_user'].value);
            sel_idx  = the_form['dbgrant'].selectedIndex;
            if (sel_idx > 0) {
                l    += '&dbgrant=' + escape(the_form['dbgrant'].options[sel_idx].text);
            }
            sel_idx  = the_form['tablegrant'].selectedIndex;
            if (sel_idx > 0) {
                l    += '&tablegrant=' + escape(the_form['tablegrant'].options[sel_idx].text);
            }
        }

        var lpos = l.indexOf('&' + box_name);
        if (lpos > 0) {
            l = l.substring(0, lpos);
        } // end if

        location.href = l + '&' + box_name + '=' + escape(getSelected(the_field));
    }

} // end of the 'change()' function


/**
 * Checks/unchecks all privileges
 *
 * @param   string   the form name
 * @param   boolean  whether to check or to uncheck the element
 *
 * @return  boolean  always true
 */
function checkForm(the_form, do_check) {
    var elts      = document.forms[the_form].elements;
    var elts_cnt  = elts.length;

    for (var i = 0; i < elts_cnt; i++) {
        var whichElt = elts[i].name;
        if (whichElt.indexOf('_priv') >= 0) {
            document.forms[the_form].elements[whichElt].checked = do_check;
        } // end if
    } // end for

    return true;
} // end of the 'checkForm()' function
