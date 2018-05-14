import { isStorageSupported } from './config';
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
            if (db === stored_db && table === stored_table) {
                if (codemirror_editor) {
                    codemirror_editor.setValue(stored_query);
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
