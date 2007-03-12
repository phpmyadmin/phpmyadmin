/**
 * holds the browser query window
 */
var querywindow = '';

/**
 * holds the query to be load from a new query window
 */
var query_to_load = '';

/**
 * sets current selected db
 *
 * @param    string    db name
 */
function setDb(new_db) {
    //alert('setDb(' + new_db + ')');
    if (new_db != db) {
        // db has changed
        //alert( new_db + '(' + new_db.length + ') : ' + db );

        var old_db = db;
        db = new_db;

        if (window.frame_navigation.document.getElementById(db) == null) {
            // db is unknown, reload complete left frame
            refreshNavigation();
        } else {
            unmarkDbTable(old_db);
            markDbTable(db);
        }

        // TODO: add code to expand db in lightview mode

        // refresh querywindow
        refreshQuerywindow();
    }
}

/**
 * sets current selected table (called from navigation.php)
 *
 * @param    string    table name
 */
function setTable(new_table) {
    //alert('setTable(' + new_table + ')');
    if (new_table != table) {
        // table has changed
        //alert( new_table + '(' + new_table.length + ') : ' + table );

        table = new_table;

        if (window.frame_navigation.document.getElementById(db + '.' + table) == null
         && table != '') {
            // table is unknown, reload complete left frame
            refreshNavigation();

        }
        // TODO: add code to expand table in lightview mode

        // refresh querywindow
        refreshQuerywindow();
    }
}

function refreshMain(url) {
    if (! url) {
        if (db) {
            url = opendb_url;
        } else {
            url = 'main.php';
        }
    }
    goTo(url + '?server=' + encodeURIComponent(server) +
        '&db=' + encodeURIComponent(db) +
        '&table=' + encodeURIComponent(table) +
        '&lang=' + encodeURIComponent(lang) +
        '&collation_connection=' + encodeURIComponent(collation_connection),
        'main' );
}

function refreshNavigation() {
    goTo('navigation.php?server=' + encodeURIComponent(server) +
        '&db=' + encodeURIComponent(db)  +
        '&table=' + encodeURIComponent(table) +
        '&lang=' + encodeURIComponent(lang) +
        '&collation_connection=' + encodeURIComponent(collation_connection)
        );
}

/**
 * adds class to element
 */
function addClass(element, classname)
{
    if (element != null) {
        element.className += ' ' + classname;
        //alert('set class: ' + classname + ', now: ' + element.className);
    }
}

/**
 * removes class from element
 */
function removeClass(element, classname)
{
    if (element != null) {
        element.className = element.className.replace(' ' + classname, '');
        // if there is no other class anem there is no leading space
        element.className = element.className.replace(classname, '');
        //alert('removed class: ' + classname + ', now: ' + element.className);
    }
}

function unmarkDbTable(db, table)
{
    var element_reference = window.frame_navigation.document.getElementById(db);
    if (element_reference != null) {
        //alert('remove from: ' + db);
        removeClass(element_reference.parentNode, 'marked');
    }

    element_reference = window.frame_navigation.document.getElementById(db + '.' + table);
    if (element_reference != null) {
        //alert('remove from: ' + db + '.' + table);
        removeClass(element_reference.parentNode, 'marked');
    }
}

function markDbTable(db, table)
{
    var element_reference = window.frame_navigation.document.getElementById(db);
    if (element_reference != null) {
        addClass(element_reference.parentNode, 'marked');
        // scrolldown
        element_reference.focus();
        // opera marks the text, we dont want this ...
        element_reference.blur();
    }

    element_reference = window.frame_navigation.document.getElementById(db + '.' + table);
    if (element_reference != null) {
        addClass(element_reference.parentNode, 'marked');
        // scrolldown
        element_reference.focus();
        // opera marks the text, we dont want this ...
        element_reference.blur();
    }

    // return to main frame ...
    window.frame_content.focus();
}

/**
 * sets current selected server, table and db (called from libraries/footer.inc.php)
 */
function setAll( new_lang, new_collation_connection, new_server, new_db, new_table ) {
    //alert('setAll( ' + new_lang + ', ' + new_collation_connection + ', ' + new_server + ', ' + new_db + ', ' + new_table + ' )');
    if (new_server != server || new_lang != lang
      || new_collation_connection != collation_connection) {
        // something important has changed
        server = new_server;
        db     = new_db;
        table  = new_table;
        collation_connection  = new_collation_connection;
        lang  = new_lang;
        refreshNavigation();
    } else if (new_db != db || new_table != table) {
        // save new db and table
        var old_db    = db;
        var old_table = table;
        db        = new_db;
        table     = new_table;

        if (window.frame_navigation.document.getElementById(db) == null
          && window.frame_navigation.document.getElementById(db + '.' + table) == null ) {
            // table or db is unknown, reload complete left frame
            refreshNavigation();
        } else {
            unmarkDbTable(old_db, old_table);
            markDbTable(db, table);
        }

        // TODO: add code to expand db in lightview mode

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

/**
 * brings query window to front and inserts query to be edited
 */
function focus_querywindow( sql_query ) {
    /* if ( querywindow && !querywindow.closed && querywindow.location) { */
    if ( !querywindow || querywindow.closed || !querywindow.location) {
        // we need first to open the window and cannot pass the query with it
        // as we dont know if the query exceeds max url length
        /* url = 'querywindow.php?' + common_query + '&db=' + db + '&table=' + table + '&sql_query=SELECT * FROM'; */
        query_to_load = sql_query;
        open_querywindow();
        insertQuery(0);
    } else {
        //var querywindow = querywindow;
        if ( querywindow.document.querywindow.querydisplay_tab != 'sql' ) {
            querywindow.document.querywindow.querydisplay_tab.value = "sql";
            querywindow.document.querywindow.query_history_latest.value = sql_query;
            querywindow.document.querywindow.submit();
            querywindow.focus();
        } else {
            querywindow.focus();
        }
    }
    return true;
}

/**
 * inserts query string into query window textarea
 * called from script tag in querywindow
 */
function insertQuery() {
    if (query_to_load != '' && querywindow.document && querywindow.document.getElementById && querywindow.document.getElementById('sqlquery')) {
        querywindow.document.getElementById('sqlquery').value = query_to_load;
        query_to_load = '';
        return true;
    }
    return false;
}

function open_querywindow( url ) {
    if ( ! url ) {
        url = 'querywindow.php?' + common_query + '&db=' + encodeURIComponent(db) + '&table=' + encodeURIComponent(table);
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
        if ( ! querywindow.document.sqlform.LockFromUpdate
          || ! querywindow.document.sqlform.LockFromUpdate.checked ) {
            open_querywindow( url )
        }
    }
}

/**
 * opens new url in target frame, with default beeing left frame
 * valid is 'main' and 'querywindow' all others leads to 'left'
 *
 * @param    string    targeturl    new url to load
 * @param    string    target       frame where to load the new url
 */
function goTo(targeturl, target) {
    //alert('goto');
    if ( target == 'main' ) {
        target = window.frame_content;
    } else if ( target == 'query' ) {
        target = querywindow;
        //return open_querywindow( targeturl );
    } else if ( ! target ) {
        target = window.frame_navigation;
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
function openDb(new_db) {
    //alert('opendb(' +  new_db + ')');
    setDb(new_db);
    setTable('');
    refreshMain(opendb_url);
    return true;
}

function updateTableTitle( table_link_id, new_title ) {
    //alert('updateTableTitle');
    if ( window.parent.frame_navigation.document.getElementById(table_link_id) ) {
        var left = window.parent.frame_navigation.document;
        left.getElementById(table_link_id).title = new_title;
        new_title = left.getElementById('icon_' + table_link_id).alt + ': ' + new_title;
        left.getElementById('browse_' + table_link_id).title = new_title;
        return true;
    }

    return false;
}
