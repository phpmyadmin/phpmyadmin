<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used with the bookmark feature
 */


/**
 * Defines the bookmark parameters for the current user
 *
 * @return  array    the bookmark parameters for the current user
 *
 * @global  integer  the id of the current server
 *
 * @access  public
 */
function PMA_getBookmarksParam()
{
    global $server;

    $cfgBookmark = '';

    // No server selected -> no bookmark table
    if ($server == 0) {
        return '';
    }

    $cfgBookmark['user']  = $GLOBALS['cfg']['Server']['user'];
    $cfgBookmark['db']    = $GLOBALS['cfg']['Server']['pmadb'];
    $cfgBookmark['table'] = $GLOBALS['cfg']['Server']['bookmarktable'];

    return $cfgBookmark;
} // end of the 'PMA_getBookmarksParam()' function


/**
 * Gets the list of bookmarks defined for the current database
 *
 * @global  resource  the controluser db connection handle
 *
 * @param   string    the current database name
 * @param   array     the bookmark parameters for the current user
 *
 * @return  mixed     the bookmarks list if defined, false else
 *
 * @access  public
 */
function PMA_listBookmarks($db, $cfgBookmark)
{
    global $dbh;

    $query  = 'SELECT label, id FROM '. PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
            . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
            . ' AND (user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
            . '      OR user = \'\')';
    $result = PMA_DBI_query($query, $dbh, PMA_DBI_QUERY_STORE);

    // There are some bookmarks -> store them
    if ($result > 0 && PMA_DBI_num_rows($result) > 0) {
        $flag = 1;
        while ($row = PMA_DBI_fetch_row($result)) {
            $bookmark_list[$flag . ' - ' . $row[0]] = $row[1];
            $flag++;
        } // end while
        return $bookmark_list;
    }
    // No bookmarks for the current database
    else {
        return FALSE;
    }
} // end of the 'PMA_listBookmarks()' function


/**
 * Gets the sql command from a bookmark
 *
 * @global  resource  the controluser db connection handle
 *
 * @param   string    the current database name
 * @param   array     the bookmark parameters for the current user
 * @param   mixed     the id of the bookmark to get
 * @param   string    which field to look up the $id
 * @param   boolean  TRUE: get all bookmarks regardless of the owning user
 *
 * @return  string    the sql query
 *
 * @access  public
 */
function PMA_queryBookmarks($db, $cfgBookmark, $id, $id_field = 'id', $action_bookmark_all = FALSE)
{
    global $dbh;

    if (empty($cfgBookmark['db']) || empty($cfgBookmark['table'])) {
        return '';
    }

    $query          = 'SELECT query FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
                    . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
                    . ($action_bookmark_all? '' : ' AND (user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
                    . '      OR user = \'\')' )
                    . ' AND ' . PMA_backquote($id_field) . ' = ' . $id;
    $result = PMA_DBI_try_query($query, $dbh);
    if (!$result) return FALSE;
    list($bookmark_query) = PMA_DBI_fetch_row($result) or array(FALSE);

    return $bookmark_query;
} // end of the 'PMA_queryBookmarks()' function


/**
 * Gets bookmarked DefaultQuery for a Table
 *
 * @global  resource  the controluser db connection handle
 *
 * @param   string    the current database name
 * @param   array     the bookmark parameters for the current user
 * @param   array     the list of all labels to look for
 *
 * @return  array     bookmark SQL statements
 *
 * @access  public
 */
function &PMA_queryDBBookmarks($db, $cfgBookmark, &$table_array)
{
    global $dbh;
    $bookmarks = array();

    if (empty($cfgBookmark['db']) || empty($cfgBookmark['table'])) {
        return $bookmarks;
    }

    $search_for = array();
    foreach($table_array AS $table => $table_sortkey) {
        $search_for[] = "'" . PMA_sqlAddslashes($table) . "'";
    }

    $query          = 'SELECT label, query FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
                    . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
                    . (count($search_for) > 0 ? ' AND label IN (' . implode(', ', $search_for) . ')' : '');
    $result = PMA_DBI_try_query($query, $dbh, PMA_DBI_QUERY_STORE);
    if (!$result || PMA_DBI_num_rows($result) < 1) return $bookmarks;
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $bookmarks[$row['label']] = $row['query'];
    }

    return $bookmarks;
} // end of the 'PMA_queryBookmarks()' function

/**
 * Adds a bookmark
 *
 * @global  resource  the controluser db connection handle
 *
 * @param   array     the properties of the bookmark to add
 * @param   array     the bookmark parameters for the current user
 * @param   boolean   whether to make the bookmark available for all users
 *
 * @return  boolean   whether the INSERT succeeds or not
 *
 * @access  public
 */
function PMA_addBookmarks($fields, $cfgBookmark, $all_users = false)
{
    global $dbh;

    $query = 'INSERT INTO ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
           . ' (id, dbase, user, query, label) VALUES (\'\', \'' . PMA_sqlAddslashes($fields['dbase']) . '\', \'' . ($all_users ? '' : PMA_sqlAddslashes($fields['user'])) . '\', \'' . PMA_sqlAddslashes(urldecode($fields['query'])) . '\', \'' . PMA_sqlAddslashes($fields['label']) . '\')';
    $result   = PMA_DBI_query($query, $dbh);

    return TRUE;
} // end of the 'PMA_addBookmarks()' function


/**
 * Deletes a bookmark
 *
 * @global  resource  the controluser db connection handle
 *
 * @param   string   the current database name
 * @param   array    the bookmark parameters for the current user
 * @param   integer  the id of the bookmark to get
 *
 * @access  public
 */
function PMA_deleteBookmarks($db, $cfgBookmark, $id)
{
    global $dbh;

    $query  = 'DELETE FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
            . ' WHERE (user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
            . '        OR user = \'\')'
            . ' AND id = ' . $id;
    $result = PMA_DBI_try_query($query, $dbh);
} // end of the 'PMA_deleteBookmarks()' function


/**
 * Bookmark Support
 */
$cfg['Bookmark'] = PMA_getBookmarksParam();

?>
