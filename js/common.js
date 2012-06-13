/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * common functions used for communicating between main, navigation and querywindow
 *
 */

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
 * @param string    db name
 */
function setDb(new_db)
{
    //alert('setDb(' + new_db + ')');
    if (new_db != db) {
        // db has changed
        //alert( new_db + '(' + new_db.length + ') : ' + db );

        var old_db = db;
        db = new_db;

        // the db name as an id exists only when LeftFrameLight is false
        if (window.frame_navigation.document.getElementById(db) == null) {
            // happens when LeftFrameLight is true
            // db is unknown, reload complete left frame
            refreshNavigation();
        } else {
            // happens when LeftFrameLight is false
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
 * @param string    table name
 */
function setTable(new_table)
{
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

/**
 * reloads main frame
 *
 * @param string    url    name of page to be loaded
 */
function refreshMain(url)
{
    if (! url) {
        if (db) {
            url = opendb_url;
        } else {
            url = 'main.php';
        }
    }
    //alert(db);
    goTo(url + '?server=' + encodeURIComponent(server) +
        '&token=' + encodeURIComponent(token) +
        '&db=' + encodeURIComponent(db) +
        '&table=' + encodeURIComponent(table) +
        '&lang=' + encodeURIComponent(lang) +
        '&collation_connection=' + encodeURIComponent(collation_connection),
        'main');
}

/**
 * reloads navigation frame
 *
 * @param boolean    force   force reloading
 */
function refreshNavigation(force)
{
    // The goTo() function won't refresh in case the target
    // url is the same as the url given as parameter, but sometimes
    // we want to refresh anyway.
    if (typeof force != undefined && force && window.parent && window.parent.frame_navigation) {
        window.parent.frame_navigation.location.reload();
    } else {
        goTo('navigation.php?server=' + encodeURIComponent(server) +
            '&token=' + encodeURIComponent(token)  +
            '&db=' + encodeURIComponent(db)  +
            '&table=' + encodeURIComponent(table) +
            '&lang=' + encodeURIComponent(lang) +
            '&collation_connection=' + encodeURIComponent(collation_connection)
            );
    }
}

function unmarkDbTable(db, table)
{
    var element_reference = window.frame_navigation.document.getElementById(db);
    if (element_reference != null) {
        $(element_reference).parent().removeClass('marked');
    }

    element_reference = window.frame_navigation.document.getElementById(db + '.' + table);
    if (element_reference != null) {
        $(element_reference).parent().removeClass('marked');
    }
}

function markDbTable(db, table)
{
    var element_reference = window.frame_navigation.document.getElementById(db);
    if (element_reference != null) {
        $(element_reference).parent().addClass('marked');
        // scrolldown
        element_reference.focus();
        // opera marks the text, we dont want this ...
        element_reference.blur();
    }

    element_reference = window.frame_navigation.document.getElementById(db + '.' + table);
    if (element_reference != null) {
        $(element_reference).parent().addClass('marked');
        // scrolldown
        element_reference.focus();
        // opera marks the text, we dont want this ...
        element_reference.blur();
    }

    // return to main frame ...
    window.frame_content.focus();
}

/**
 * sets current selected server, table and db (called from the footer)
 */
function setAll( new_lang, new_collation_connection, new_server, new_db, new_table, new_token )
{
    if (new_server != server || new_lang != lang
      || new_collation_connection != collation_connection) {
        // something important has changed
        server = new_server;
        db     = new_db;
        table  = new_table;
        collation_connection  = new_collation_connection;
        lang  = new_lang;
        token  = new_token;
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

function reload_querywindow(db, table, sql_query)
{
    if ( ! querywindow.closed && querywindow.location ) {
        if ( ! querywindow.document.sqlform.LockFromUpdate
          || ! querywindow.document.sqlform.LockFromUpdate.checked ) {
            querywindow.document.getElementById('hiddenqueryform').db.value = db;
            querywindow.document.getElementById('hiddenqueryform').table.value = table;

            if (sql_query) {
                querywindow.document.getElementById('hiddenqueryform').sql_query.value = sql_query;
            }

            querywindow.document.getElementById('hiddenqueryform').submit();
        }
    }
}

/**
 * brings query window to front and inserts query to be edited
 */
function focus_querywindow(sql_query)
{
    /* if ( querywindow && !querywindow.closed && querywindow.location) { */
    if ( !querywindow || querywindow.closed || !querywindow.location) {
        // we need first to open the window and cannot pass the query with it
        // as we dont know if the query exceeds max url length
        query_to_load = sql_query;
        open_querywindow();
        insertQuery(0);
    } else {
        //var querywindow = querywindow;
        if ( querywindow.document.getElementById('hiddenqueryform').querydisplay_tab != 'sql' ) {
            querywindow.document.getElementById('hiddenqueryform').querydisplay_tab.value = "sql";
            querywindow.document.getElementById('hiddenqueryform').sql_query.value = sql_query;
            querywindow.document.getElementById('hiddenqueryform').submit();
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
function insertQuery()
{
    if (query_to_load != '' && querywindow.document && querywindow.document.getElementById && querywindow.document.getElementById('sqlquery')) {
        querywindow.document.getElementById('sqlquery').value = query_to_load;
        query_to_load = '';
        return true;
    }
    return false;
}

function open_querywindow( url )
{
    if ( ! url ) {
        url = 'querywindow.php?' + common_query + '&db=' + encodeURIComponent(db) + '&table=' + encodeURIComponent(table);
    }

    if (!querywindow.closed && querywindow.location) {
        goTo( url, 'query' );
        querywindow.focus();
    } else {
        querywindow = window.open( url + '&init=1', '',
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

function refreshQuerywindow( url )
{

    if ( ! querywindow.closed && querywindow.location ) {
        if ( ! querywindow.document.sqlform.LockFromUpdate
          || ! querywindow.document.sqlform.LockFromUpdate.checked ) {
            open_querywindow( url )
        }
    }
}

/**
 * opens new url in target frame, with default being left frame
 * valid is 'main' and 'querywindow' all others leads to 'left'
 *
 * @param string    targeturl    new url to load
 * @param string    target       frame where to load the new url
 */
function goTo(targeturl, target)
{
    //alert(targeturl);
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
function openDb(new_db)
{
    //alert('opendb(' +  new_db + ')');
    setDb(new_db);
    setTable('');
    refreshMain(opendb_url);
    return true;
}

function updateTableTitle( table_link_id, new_title )
{
    //alert('updateTableTitle');
    if ( window.parent.frame_navigation.document && window.parent.frame_navigation.document.getElementById(table_link_id) ) {
        var left = window.parent.frame_navigation.document;

        var link = left.getElementById(table_link_id);
        link.title = window.parent.pma_text_default_tab + ': ' + new_title;

        var link = left.getElementById('quick_' + table_link_id);
        link.title = window.parent.pma_text_left_default_tab + ': ' + new_title;

        return true;
    }

    return false;
}
