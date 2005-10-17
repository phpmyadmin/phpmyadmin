var querywindow = '';

// sets current selected table (called from footer.inc.php)
function setTable( new_db, new_table ) {
    if ( new_db != db || new_table != table ) {
        // table or db has changed
        
        //alert( new_table + '(' + new_table.length + ') : ' + table );
        if ( window.frames[0].document.getElementById( new_db ) == null
          && window.frames[0].document.getElementById( new_db + '.' + new_table ) == null ) {
            // table or db is unknown, reload complete left frame
            goTo('left.php?&db=' + new_db + '&table=' + new_table);
        }
        
        // save new db and table
        db = new_db;
        table = new_table;
        
        // refresh querywindow
        refreshQuerywindow();
    }
}

function reload_querywindow( db, table, sql_query ) {
    if ( ! querywindow.closed && querywindow.location ) {
        if ( ! querywindow.document.sqlform.LockFromUpdate
          || ! querywindow.document.sqlform.LockFromUpdate.checked ) {
            querywindow.document.querywindow.db.value = db;
            querywindow.document.querywindow.query_history_latest_db.value = db;
            querywindow.document.querywindow.table.value = table;
            querywindow.document.querywindow.query_history_latest_table.value = table;
            
            if ( sql_query ) {
                querywindow.document.querywindow.query_history_latest.value = sql_query;
            }

            querywindow.document.querywindow.submit();
        }
    }
}

function focus_querywindow( sql_query ) {
    if ( querywindow && !querywindow.closed && querywindow.location) {
        var querywindow = querywindow;
        if ( querywindow.document.querywindow.querydisplay_tab != 'sql' ) {
            querywindow.document.querywindow.querydisplay_tab.value = "sql";
            querywindow.document.querywindow.query_history_latest.value = sql_query;
            querywindow.document.querywindow.submit();
            querywindow.focus();
        } else {
            querywindow.focus();
        }
    } else {
        url = 'querywindow.php?' + common_query + '&db=' + db + '&table=' + table + '&sql_query=' + sql_query;
        open_querywindow( url );
    }
}

function open_querywindow( url ) {
    if ( ! url ) {
        url = 'querywindow.php?' + common_query + '&db=' + db + '&table=' + table;
    }

    if (!querywindow.closed && querywindow.location) {
        goTo( url, 'query' );
        querywindow.focus();
    } else {
        querywindow=window.open( url, '',
            'toolbar=0,location=0,directories=0,status=1,menubar=0,' +
            'scrollbars=yes,resizable=yes,' +
            'width=' + querywindow_width + ',' +
            'height=' + querywindow_height );
    }

    if ( ! querywindow.opener ) {
       querywindow.opener = window.window;
    }

    if ( window.focus ) {
        querywindow.focus();
    }

    return true;
}

function refreshQuerywindow( url ) {
    if ( ! querywindow.closed && querywindow.location ) {
        open_querywindow( url )
    }
}

function goTo( targeturl, target ) {
    if ( target == 'main' ) {
        target = window.frames[1];
    } else if ( target == 'query' ) {
        target = querywindow;
        //return open_querywindow( targeturl );
    } else if ( ! target ) {
        target = window.frames[0];
    }

    if ( target ) {
        if ( target.location.href == targeturl ) {
            return true;
        } else if ( target.location.href == pma_absolute_uri + targeturl ) {
            return true;
        }
        
        if ( safari_browser ) {
            target.location.href = targeturl;
        } else {
            target.location.replace(targeturl);
        }
    }

    return true;
}

// opens selected db in main frame
function openDb( db ) {
    goTo( opendb_url + '?' + common_query + '&db=' + db,
        window.parent.frames[1] );
    return true;
}
