/* $Id$ */


/**
 * Displays an confirmation box beforme to submit a "DROP/DELETE/ALTER" query.
 * This function is called while clicking links
 *
 * @param   object   the link
 * @param   object   the sql query to submit
 *
 * @return  boolean  whether to run the query or not
 */
function confirmLink(theLink, theSqlQuery)
{
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (confirmMsg == '' || typeof(window.opera) != 'undefined') {
        return true;
    }

    var is_confirmed = confirm(confirmMsg + ' :\n' + theSqlQuery);
    if (is_confirmed) {
        theLink.href += '&is_js_confirmed=1';
    }

    return is_confirmed;
} // end of the 'confirmLink()' function


/**
 * Displays an error message if a "DROP DATABASE" statement is submitted
 * while it isn't allowed, else confirms a "DROP/DELETE/ALTER" query before
 * sumitting it if required.
 * This function is called by the 'checkSqlQuery()' js function.
 *
 * @param   object   the form
 * @param   object   the sql query textarea
 *
 * @return  boolean  whether to run the query or not
 *
 * @see     checkSqlQuery()
 */
function confirmQuery(theForm1, sqlQuery1)
{
    // Confirmation is not required in the configuration file
    if (confirmMsg == '') {
        return true;
    }

    // The replace function (js1.2) isn't supported
    else if (typeof(sqlQuery1.value.replace) == 'undefined') {
        return true;
    }

    // js1.2+ -> validation with regular expressions
    else {
        // "DROP DATABASE" statement isn't allowed
        if (noDropDbMsg != '') {
            var drop_re = new RegExp('DROP\\s+(IF EXISTS\\s+)?DATABASE\\s', 'i');
            if (drop_re.test(sqlQuery1.value)) {
                alert(noDropDbMsg);
                theForm1.reset();
                sqlQuery1.focus();
                return false;
            } // end if
        } // end if

        // Confirms a "DROP/DELETE/ALTER" statement
        //
        // TODO: find a way (if possible) to use the parser-analyser
        // for this kind of verification
        // For now, I just added a ^ to check for the statement at
        // beginning of expression
        
        //var do_confirm_re_0 = new RegExp('DROP\\s+(IF EXISTS\\s+)?(TABLE|DATABASE)\\s', 'i');
        //var do_confirm_re_1 = new RegExp('ALTER\\s+TABLE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+DROP\\s', 'i');
        //var do_confirm_re_2 = new RegExp('DELETE\\s+FROM\\s', 'i');
        var do_confirm_re_0 = new RegExp('^DROP\\s+(IF EXISTS\\s+)?(TABLE|DATABASE)\\s', 'i');
        var do_confirm_re_1 = new RegExp('^ALTER\\s+TABLE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+DROP\\s', 'i');
        var do_confirm_re_2 = new RegExp('^DELETE\\s+FROM\\s', 'i');
        if (do_confirm_re_0.test(sqlQuery1.value)
            || do_confirm_re_1.test(sqlQuery1.value)
            || do_confirm_re_2.test(sqlQuery1.value)) {
            var message      = (sqlQuery1.value.length > 100)
                             ? sqlQuery1.value.substr(0, 100) + '\n    ...'
                             : sqlQuery1.value;
            var is_confirmed = confirm(confirmMsg + ' :\n' + message);
            // drop/delete/alter statement is confirmed -> update the
            // "is_js_confirmed" form field so the confirm test won't be
            // run on the server side and allows to submit the form
            if (is_confirmed) {
                theForm1.elements['is_js_confirmed'].value = 1;
                return true;
            }
            // "DROP/DELETE/ALTER" statement is rejected -> do not submit
            // the form
            else {
                window.focus();
                sqlQuery1.focus();
                return false;
            } // end if (handle confirm box result)
        } // end if (display confirm box)
    } // end confirmation stuff

    return true;
} // end of the 'confirmQuery()' function


/**
 * Displays an error message if the user submitted the sql query form with no
 * sql query, else checks for "DROP/DELETE/ALTER" statements
 *
 * @param   object   the form
 *
 * @return  boolean  always false
 *
 * @see     confirmQuery()
 */
function checkSqlQuery(theForm)
{
    var sqlQuery = theForm.elements['sql_query'];
    var isEmpty  = 1;

    // The replace function (js1.2) isn't supported -> basic tests
    if (typeof(sqlQuery.value.replace) == 'undefined') {
        isEmpty      = (sqlQuery.value == '') ? 1 : 0;
        if (isEmpty && typeof(theForm.elements['sql_file']) != 'undefined') {
            isEmpty  = (theForm.elements['sql_file'].value == '') ? 1 : 0;
        }
        if (isEmpty && typeof(theForm.elements['sql_localfile']) != 'undefined') {
            isEmpty  = (theForm.elements['sql_localfile'].value == '') ? 1 : 0;
        }
        if (isEmpty && typeof(theForm.elements['id_bookmark']) != 'undefined') {
            isEmpty  = (theForm.elements['id_bookmark'].value == null || theForm.elements['id_bookmark'].value == '');
        }
    }
    // js1.2+ -> validation with regular expressions
    else {
        var space_re = new RegExp('\\s+');
        isEmpty      = (sqlQuery.value.replace(space_re, '') == '') ? 1 : 0;
        // Checks for "DROP/DELETE/ALTER" statements
        if (!isEmpty && !confirmQuery(theForm, sqlQuery)) {
            return false;
        }
        if (isEmpty && typeof(theForm.elements['sql_file']) != 'undefined') {
            isEmpty  = (theForm.elements['sql_file'].value.replace(space_re, '') == '') ? 1 : 0;
        }
        if (isEmpty && typeof(theForm.elements['sql_localfile']) != 'undefined') {
            isEmpty  = (theForm.elements['sql_localfile'].value.replace(space_re, '') == '') ? 1 : 0;
        }
        if (isEmpty && typeof(theForm.elements['id_bookmark']) != 'undefined') {
            isEmpty  = (theForm.elements['id_bookmark'].value == null || theForm.elements['id_bookmark'].value == '');
            isEmpty  = (theForm.elements['id_bookmark'].selectedIndex == 0);
        }
        if (isEmpty) {
            theForm.reset();
        }
    }

    if (isEmpty) {
        sqlQuery.select();
        alert(errorMsg0);
        sqlQuery.focus();
        return false;
    }

    return true;
} // end of the 'checkSqlQuery()' function


/**
 * Displays an error message if an element of a form hasn't been completed and
 * should be
 *
 * @param   object   the form
 * @param   string   the name of the form field to put the focus on
 *
 * @return  boolean  whether the form field is empty or not
 */
function emptyFormElements(theForm, theFieldName)
{
    var isEmpty  = 1;
    var theField = theForm.elements[theFieldName];
    // Whether the replace function (js1.2) is supported or not
    var isRegExp = (typeof(theField.value.replace) != 'undefined');

    if (!isRegExp) {
        isEmpty      = (theField.value == '') ? 1 : 0;
    } else {
        var space_re = new RegExp('\\s+');
        isEmpty      = (theField.value.replace(space_re, '') == '') ? 1 : 0;
    }
    if (isEmpty) {
        theForm.reset();
        theField.select();
        alert(errorMsg0);
        theField.focus();
        return false;
    }

    return true;
} // end of the 'emptyFormElements()' function


/**
 * Ensures a value submitted in a form is numeric and is in a range
 *
 * @param   object   the form
 * @param   string   the name of the form field to check
 * @param   integer  the minimum authorized value
 * @param   integer  the maximum authorized value
 *
 * @return  boolean  whether a valid number has been submitted or not
 */
function checkFormElementInRange(theForm, theFieldName, min, max)
{
    var theField         = theForm.elements[theFieldName];
    var val              = parseInt(theField.value);

    if (typeof(min) == 'undefined') {
        min = 0;
    }
    if (typeof(max) == 'undefined') {
        max = Number.MAX_VALUE;
    }

    // It's not a number
    if (isNaN(val)) {
        theField.select();
        alert(errorMsg1);
        theField.focus();
        return false;
    }
    // It's a number but it is not between min and max
    else if (val < min || val > max) {
        theField.select();
        alert(val + errorMsg2);
        theField.focus();
        return false;
    }
    // It's a valid number
    else {
        theField.value = val;
    }

    return true;
} // end of the 'checkFormElementInRange()' function


/**
 * Ensures the choice between 'transmit', 'zipped', 'gzipped' and 'bzipped'
 * checkboxes is consistant
 *
 * @param   object   the form
 * @param   string   a code for the action that causes this function to be run
 *
 * @return  boolean  always true
 */
function checkTransmitDump(theForm, theAction)
{
    var formElts = theForm.elements;

    // 'zipped' option has been checked
    if (theAction == 'zip' && formElts['zip'].checked) {
        if (!formElts['asfile'].checked) {
            theForm.elements['asfile'].checked = true;
        }
        if (typeof(formElts['gzip']) != 'undefined' && formElts['gzip'].checked) {
            theForm.elements['gzip'].checked = false;
        }
        if (typeof(formElts['bzip']) != 'undefined' && formElts['bzip'].checked) {
            theForm.elements['bzip'].checked = false;
        }
    }
    // 'gzipped' option has been checked
    else if (theAction == 'gzip' && formElts['gzip'].checked) {
        if (!formElts['asfile'].checked) {
            theForm.elements['asfile'].checked = true;
        }
        if (typeof(formElts['zip']) != 'undefined' && formElts['zip'].checked) {
            theForm.elements['zip'].checked = false;
        }
        if (typeof(formElts['bzip']) != 'undefined' && formElts['bzip'].checked) {
            theForm.elements['bzip'].checked = false;
        }
    }
    // 'bzipped' option has been checked
    else if (theAction == 'bzip' && formElts['bzip'].checked) {
        if (!formElts['asfile'].checked) {
            theForm.elements['asfile'].checked = true;
        }
        if (typeof(formElts['zip']) != 'undefined' && formElts['zip'].checked) {
            theForm.elements['zip'].checked = false;
        }
        if (typeof(formElts['gzip']) != 'undefined' && formElts['gzip'].checked) {
            theForm.elements['gzip'].checked = false;
        }
    }
    // 'transmit' option has been unchecked
    else if (theAction == 'transmit' && !formElts['asfile'].checked) {
        if (typeof(formElts['zip']) != 'undefined' && formElts['zip'].checked) {
            theForm.elements['zip'].checked = false;
        }
        if ((typeof(formElts['gzip']) != 'undefined' && formElts['gzip'].checked)) {
            theForm.elements['gzip'].checked = false;
        }
        if ((typeof(formElts['bzip']) != 'undefined' && formElts['bzip'].checked)) {
            theForm.elements['bzip'].checked = false;
        }
    }

    return true;
} // end of the 'checkTransmitDump()' function


/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;


/**
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param   object    the table row
 * @param   interger  the row number
 * @param   string    the action calling this script (over, out or click)
 * @param   string    the default background color
 * @param   string    the color to use for mouseover
 * @param   string    the color to use for marking a row
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theRow, theRowNum, theAction, theDefaultColor, thePointerColor, theMarkColor)
{
    var theCells = null;

    // 1. Pointer and mark feature are disabled or the browser can't get the
    //    row -> exits
    if ((thePointerColor == '' && theMarkColor == '')
        || typeof(theRow.style) == 'undefined') {
        return false;
    }

    // 2. Gets the current row and exits if the browser can't get it
    if (typeof(document.getElementsByTagName) != 'undefined') {
        theCells = theRow.getElementsByTagName('td');
    }
    else if (typeof(theRow.cells) != 'undefined') {
        theCells = theRow.cells;
    }
    else {
        return false;
    }

    // 3. Gets the current color...
    var rowCellsCnt  = theCells.length;
    var domDetect    = null;
    var currentColor = null;
    var newColor     = null;
    // 3.1 ... with DOM compatible browsers except Opera that does not return
    //         valid values with "getAttribute"
    if (typeof(window.opera) == 'undefined'
        && typeof(theCells[0].getAttribute) != 'undefined') {
        currentColor = theCells[0].getAttribute('bgcolor');
        domDetect    = true;
    }
    // 3.2 ... with other browsers
    else {
        currentColor = theCells[0].style.backgroundColor;
        domDetect    = false;
    } // end 3

    // 4. Defines the new color
    // 4.1 Current color is the default one
    if (currentColor == ''
        || currentColor.toLowerCase() == theDefaultColor.toLowerCase()) {
        if (theAction == 'over' && thePointerColor != '') {
            newColor              = thePointerColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
        }
    }
    // 4.1.2 Current color is the pointer one
    else if (currentColor.toLowerCase() == thePointerColor.toLowerCase()
             && (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])) {
        if (theAction == 'out') {
            newColor              = theDefaultColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
        }
    }
    // 4.1.3 Current color is the marker one
    else if (currentColor.toLowerCase() == theMarkColor.toLowerCase()) {
        if (theAction == 'click') {
            newColor              = (thePointerColor != '')
                                  ? thePointerColor
                                  : theDefaultColor;
            marked_row[theRowNum] = (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])
                                  ? true
                                  : null;
        }
    } // end 4

    // 5. Sets the new color...
    if (newColor) {
        var c = null;
        // 5.1 ... with DOM compatible browsers except Opera
        if (domDetect) {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].setAttribute('bgcolor', newColor, 0);
            } // end for
        }
        // 5.2 ... with other browsers
        else {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].style.backgroundColor = newColor;
            }
        }
    } // end 5

    return true;
} // end of the 'setPointer()' function


/**
 * Checks/unchecks all tables
 *
 * @param   string   the form name
 * @param   boolean  whether to check or to uncheck the element
 *
 * @return  boolean  always true
 */
function setCheckboxes(the_form, do_check)
{
    var elts      = (typeof(document.forms[the_form].elements['selected_db[]']) != 'undefined')
                  ? document.forms[the_form].elements['selected_db[]']
                  : (typeof(document.forms[the_form].elements['selected_tbl[]']) != 'undefined')
		  ? document.forms[the_form].elements['selected_tbl[]']
		  : document.forms[the_form].elements['selected_fld[]'];
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;

    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
            elts[i].checked = do_check;
        } // end for
    } else {
        elts.checked        = do_check;
    } // end if... else

    return true;
} // end of the 'setCheckboxes()' function


/**
  * Checks/unchecks all options of a <select> element
  *
  * @param   string   the form name
  * @param   string   the element name
  * @param   boolean  whether to check or to uncheck the element
  *
  * @return  boolean  always true
  */
function setSelectOptions(the_form, the_select, do_check)
{
    var selectObject = document.forms[the_form].elements[the_select];
    var selectCount  = selectObject.length;

    for (var i = 0; i < selectCount; i++) {
        selectObject.options[i].selected = do_check;
    } // end for

    return true;
} // end of the 'setSelectOptions()' function
