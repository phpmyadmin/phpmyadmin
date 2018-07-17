import { isStorageSupported } from '../config';
import Cookies from 'js-cookie';
import { sqlQueryOptions } from '../../utils/sql';
import { GlobalVariables, PMA_Messages as PMA_messages } from '../../variables/export_variables';
import { PMA_ajaxShowMessage } from '../../utils/show_ajax_messages';
import { PMA_sprintf } from '../../utils/sprintf';

/**
 * Handles 'Simulate query' button on SQL query box.
 *
 * @return void
 */
export function PMA_handleSimulateQueryButton () {
    var update_re = new RegExp('^\\s*UPDATE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+SET\\s', 'i');
    var delete_re = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    var query = '';

    if (sqlQueryOptions.codemirror_editor) {
        query = sqlQueryOptions.codemirror_editor.getValue();
    } else {
        query = $('#sqlquery').val();
    }

    var $simulateDml = $('#simulate_dml');
    if (update_re.test(query) || delete_re.test(query)) {
        if (! $simulateDml.length) {
            $('#button_submit_query')
                .before('<input type="button" id="simulate_dml"' +
                'tabindex="199" value="' +
                PMA_messages.strSimulateDML +
                '" />');
        }
    } else {
        if ($simulateDml.length) {
            $simulateDml.remove();
        }
    }
}

/**
 * Sets current value for query box.
 */
export function setQuery (query) {
    if (sqlQueryOptions.codemirror_editor) {
        sqlQueryOptions.codemirror_editor.setValue(query);
        sqlQueryOptions.codemirror_editor.focus();
    } else if (document.sqlform) {
        document.sqlform.sql_query.value = query;
        document.sqlform.sql_query.focus();
    }
}

/**
  * Create quick sql statements.
  *
  */
export function insertQuery (queryType) {
    if (queryType === 'clear') {
        setQuery('');
        return;
    } else if (queryType === 'format') {
        if (sqlQueryOptions.codemirror_editor) {
            $('#querymessage').html(PMA_messages.strFormatting +
                '&nbsp;<img class="ajaxIcon" src="' +
                GlobalVariables.pmaThemeImage + 'ajax_clock_small.gif" alt="">');
            var href = 'db_sql_format.php';
            var params = {
                'ajax_request': true,
                'sql': sqlQueryOptions.codemirror_editor.getValue()
            };
            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    if (data.success) {
                        sqlQueryOptions.codemirror_editor.setValue(data.sql);
                    }
                    $('#querymessage').html('');
                }
            });
        }
        return;
    } else if (queryType === 'saved') {
        if (isStorageSupported('localStorage')
            && typeof window.localStorage.auto_saved_sql !== 'undefined'
        ) {
            setQuery(window.localStorage.auto_saved_sql);
        } else if (Cookies.get('auto_saved_sql')) {
            setQuery(Cookies.get('auto_saved_sql'));
        } else {
            PMA_ajaxShowMessage(PMA_messages.strNoAutoSavedQuery);
        }
        return;
    }

    var query = '';
    var myListBox = document.sqlform.dummy;
    var table = document.sqlform.table.value;

    if (myListBox.options.length > 0) {
        sql_box_locked = true;
        var columnsList = '';
        var valDis = '';
        var editDis = '';
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            NbSelect++;
            if (NbSelect > 1) {
                columnsList += ', ';
                valDis += ',';
                editDis += ',';
            }
            columnsList += myListBox.options[i].value;
            valDis += '[value-' + NbSelect + ']';
            editDis += myListBox.options[i].value + '=[value-' + NbSelect + ']';
        }
        if (queryType === 'selectall') {
            query = 'SELECT * FROM `' + table + '` WHERE 1';
        } else if (queryType === 'select') {
            query = 'SELECT ' + columnsList + ' FROM `' + table + '` WHERE 1';
        } else if (queryType === 'insert') {
            query = 'INSERT INTO `' + table + '`(' + columnsList + ') VALUES (' + valDis + ')';
        } else if (queryType === 'update') {
            query = 'UPDATE `' + table + '` SET ' + editDis + ' WHERE 1';
        } else if (queryType === 'delete') {
            query = 'DELETE FROM `' + table + '` WHERE 0';
        }
        setQuery(query);
        sql_box_locked = false;
    }
}

/**
 * Confirms a "DROP/DELETE/ALTER" query before
 * submitting it if required.
 * This function is called by the 'checkSqlQuery()' js function.
 *
 * @param theForm1 object   the form
 * @param sqlQuery1 string  the sql query string
 *
 * @return boolean  whether to run the query or not
 *
 * @see     checkSqlQuery()
 */
function confirmQuery (theForm1, sqlQuery1) {
    // Confirmation is not required in the configuration file
    if (PMA_messages.strDoYouReally === '') {
        return true;
    }

    // Confirms a "DROP/DELETE/ALTER/TRUNCATE" statement
    //
    // TODO: find a way (if possible) to use the parser-analyser
    // for this kind of verification
    // For now, I just added a ^ to check for the statement at
    // beginning of expression

    var do_confirm_re_0 = new RegExp('^\\s*DROP\\s+(IF EXISTS\\s+)?(TABLE|PROCEDURE)\\s', 'i');
    var do_confirm_re_1 = new RegExp('^\\s*ALTER\\s+TABLE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+DROP\\s', 'i');
    var do_confirm_re_2 = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    var do_confirm_re_3 = new RegExp('^\\s*TRUNCATE\\s', 'i');

    if (do_confirm_re_0.test(sqlQuery1) ||
        do_confirm_re_1.test(sqlQuery1) ||
        do_confirm_re_2.test(sqlQuery1) ||
        do_confirm_re_3.test(sqlQuery1)) {
        var message;
        if (sqlQuery1.length > 100) {
            message = sqlQuery1.substr(0, 100) + '\n    ...';
        } else {
            message = sqlQuery1;
        }
        var is_confirmed = confirm(PMA_sprintf(PMA_messages.strDoYouReally, message));
        // statement is confirmed -> update the
        // "is_js_confirmed" form field so the confirm test won't be
        // run on the server side and allows to submit the form
        if (is_confirmed) {
            theForm1.elements.is_js_confirmed.value = 1;
            return true;
        } else {
            // statement is rejected -> do not submit the form
            window.focus();
            return false;
        } // end if (handle confirm box result)
    } // end if (display confirm box)

    return true;
} // end of the 'confirmQuery()' function

/**
 * Displays an error message if the user submitted the sql query form with no
 * sql query, else checks for "DROP/DELETE/ALTER" statements
 *
 * @param theForm object the form
 *
 * @return boolean  always false
 *
 * @see     confirmQuery()
 */
export function checkSqlQuery (theForm) {
    // get the textarea element containing the query
    var sqlQuery;
    if (sqlQueryOptions.codemirror_editor) {
        sqlQueryOptions.codemirror_editor.save();
        sqlQuery = sqlQueryOptions.codemirror_editor.getValue();
    } else {
        sqlQuery = theForm.elements.sql_query.value;
    }
    var space_re = new RegExp('\\s+');
    if (typeof(theForm.elements.sql_file) !== 'undefined' &&
            theForm.elements.sql_file.value.replace(space_re, '') !== '') {
        return true;
    }
    if (typeof(theForm.elements.id_bookmark) !== 'undefined' &&
            (theForm.elements.id_bookmark.value !== null || theForm.elements.id_bookmark.value !== '') &&
            theForm.elements.id_bookmark.selectedIndex !== 0) {
        return true;
    }
    var result = false;
    // Checks for "DROP/DELETE/ALTER" statements
    if (sqlQuery.replace(space_re, '') !== '') {
        result = confirmQuery(theForm, sqlQuery);
    } else {
        alert(PMA_messages.strFormEmpty);
    }

    if (sqlQueryOptions.codemirror_editor) {
        sqlQueryOptions.codemirror_editor.focus();
    } else if (sqlQueryOptions.codemirror_inline_editor) {
        sqlQueryOptions.codemirror_inline_editor.focus();
    }
    return result;
} // end of the 'checkSqlQuery()' function

export function checkSavedQuery () {
    if (isStorageSupported('localStorage')
        && window.localStorage.auto_saved_sql !== undefined
    ) {
        PMA_ajaxShowMessage(PMA_messages.strPreviousSaveQuery);
    }
}

/**
 * Set query to codemirror if show this query is
 * checked and query for the db and table pair exists
 */
export function setShowThisQuery () {
    var db = $('input[name="db"]').val();
    var table = $('input[name="table"]').val();
    if (isStorageSupported('localStorage')) {
        if (window.localStorage.show_this_query_object !== undefined) {
            var stored_db = JSON.parse(window.localStorage.show_this_query_object).db;
            var stored_table = JSON.parse(window.localStorage.show_this_query_object).table;
            var stored_query = JSON.parse(window.localStorage.show_this_query_object).query;
        }
        if (window.localStorage.show_this_query !== undefined
            && window.localStorage.show_this_query === '1') {
            $('input[name="show_query"]').prop('checked', true);
            if ((db === stored_db && table === stored_table) || (db === undefined && table === undefined)) {
                if (sqlQueryOptions.codemirror_editor) {
                    sqlQueryOptions.codemirror_editor.setValue(stored_query);
                } else if (document.sqlform) {
                    document.sqlform.sql_query.value = stored_query;
                }
            }
        } else {
            $('input[name="show_query"]').prop('checked', false);
        }
    }
}


/**
 * Saves SQL query in local storage or cookie
 *
 * @param string database name
 * @param string table name
 * @param string SQL query
 * @return void
 */
export function PMA_showThisQuery (db, table, query) {
    var show_this_query_object = {
        'db': db,
        'table': table,
        'query': query
    };
    if (isStorageSupported('localStorage')) {
        window.localStorage.show_this_query = 1;
        window.localStorage.show_this_query_object = JSON.stringify(show_this_query_object);
    } else {
        Cookies.set('show_this_quey', 1);
        Cookies.set('show_this_query_object', JSON.stringify(show_this_query_object));
    }
}

/**
 * Saves SQL query in local storage or cookie
 *
 * @param string SQL query
 * @return void
 */
export function PMA_autosaveSQL (query) {
    if (isStorageSupported('localStorage')) {
        window.localStorage.auto_saved_sql = query;
    } else {
        Cookies.set('auto_saved_sql', query);
    }
}

/**
 * Saves SQL query with sort in local storage or cookie
 *
 * @param string SQL query
 * @return void
 */
export function PMA_autosaveSQLSort (query) {
    if (query) {
        if (isStorageSupported('localStorage')) {
            window.localStorage.auto_saved_sql_sort = query;
        } else {
            Cookies.set('auto_saved_sql_sort', query);
        }
    }
}

/**
  * Inserts multiple fields.
  *
  */
export function insertValueQuery () {
    var myQuery = document.sqlform.sql_query;
    var myListBox = document.sqlform.dummy;

    if (myListBox.options.length > 0) {
        sql_box_locked = true;
        var columnsList = '';
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            if (myListBox.options[i].selected) {
                NbSelect++;
                if (NbSelect > 1) {
                    columnsList += ', ';
                }
                columnsList += myListBox.options[i].value;
            }
        }

        /* CodeMirror support */
        if (sqlQueryOptions.codemirror_editor) {
            sqlQueryOptions.codemirror_editor.replaceSelection(columnsList);
            sqlQueryOptions.codemirror_editor.focus();
        // IE support
        } else if (document.selection) {
            myQuery.focus();
            var sel = document.selection.createRange();
            sel.text = columnsList;
        // MOZILLA/NETSCAPE support
        } else if (document.sqlform.sql_query.selectionStart || document.sqlform.sql_query.selectionStart === '0') {
            var startPos = document.sqlform.sql_query.selectionStart;
            var endPos = document.sqlform.sql_query.selectionEnd;
            var SqlString = document.sqlform.sql_query.value;

            myQuery.value = SqlString.substring(0, startPos) + columnsList + SqlString.substring(endPos, SqlString.length);
            myQuery.focus();
        } else {
            myQuery.value += columnsList;
        }
        sql_box_locked = false;
    }
}
