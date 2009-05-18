<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the bookmark feature
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/relation.lib.php';

/**
 * Defines the bookmark parameters for the current user
 *
 * @uses    $GLOBALS['server']
 * @uses    PMA_getRelationsParam()
 * @uses    $GLOBALS['cfg']['Server']['user']
 * @uses    $GLOBALS['cfg']['Server']['pmadb']
 * @uses    $GLOBALS['cfg']['Server']['bookmarktable']
 * @return  array    the bookmark parameters for the current user
 * @access  public
 */
function PMA_Bookmark_getParams()
{
    static $cfgBookmark = null;

    if (null !== $cfgBookmark) {
        return $cfgBookmark;
    }

    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['bookmarkwork']) {
        $cfgBookmark = array(
            'user'  => $GLOBALS['cfg']['Server']['user'],
            'db'    => $GLOBALS['cfg']['Server']['pmadb'],
            'table' => $GLOBALS['cfg']['Server']['bookmarktable'],
        );
    } else {
        $cfgBookmark = false;
    }

    return $cfgBookmark;
} // end of the 'PMA_Bookmark_getParams()' function


/**
 * Gets the list of bookmarks defined for the current database
 *
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_Bookmark_getParams()
 * @global  resource  the controluser db connection handle
 *
 * @param   string    the current database name
 *
 * @return  array     the bookmarks list (key as index, label as value)
 *
 * @access  public
 */
function PMA_Bookmark_getList($db)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return array();
    }

    $query  = 'SELECT label, id FROM '. PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
            . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
            . ' AND (user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
            . '      OR user = \'\')'
            . ' ORDER BY label';
    return PMA_DBI_fetch_result($query, 'id', 'label', $controllink, PMA_DBI_QUERY_STORE);
} // end of the 'PMA_Bookmark_getList()' function


/**
 * Gets the sql command from a bookmark
 *
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_value()
 * @uses    PMA_Bookmark_getParams()
 * @global  resource  the controluser db connection handle
 *
 * @param   string    the current database name
 * @param   mixed     the id of the bookmark to get
 * @param   string    which field to look up the $id
 * @param   boolean  TRUE: get all bookmarks regardless of the owning user
 *
 * @return  string    the sql query
 *
 * @access  public
 */
function PMA_Bookmark_get($db, $id, $id_field = 'id', $action_bookmark_all = FALSE)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return '';
    }

    $query = 'SELECT query FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
        . ($action_bookmark_all? '' : ' AND (user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
        . '      OR user = \'\')')
        . ' AND ' . PMA_backquote($id_field) . ' = ' . $id;
    return PMA_DBI_fetch_value($query, 0, 0, $controllink);
} // end of the 'PMA_Bookmark_get()' function

/**
 * Adds a bookmark
 *
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_query()
 * @uses    PMA_Bookmark_getParams()
 * @global  resource  the controluser db connection handle
 *
 * @param   array     the properties of the bookmark to add; here,
 *                    $fields['query'] is urlencoded
 * @param   boolean   whether to make the bookmark available for all users
 *
 * @return  boolean   whether the INSERT succeeds or not
 *
 * @access  public
 */
function PMA_Bookmark_save($fields, $all_users = false)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return false;
    }

    $query = 'INSERT INTO ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
           . ' (id, dbase, user, query, label) VALUES (NULL, \'' . PMA_sqlAddslashes($fields['dbase']) . '\', \'' . ($all_users ? '' : PMA_sqlAddslashes($fields['user'])) . '\', \'' . PMA_sqlAddslashes(urldecode($fields['query'])) . '\', \'' . PMA_sqlAddslashes($fields['label']) . '\')';
    return PMA_DBI_query($query, $controllink);
} // end of the 'PMA_Bookmark_save()' function


/**
 * Deletes a bookmark
 *
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_Bookmark_getParams()
 * @global  resource  the controluser db connection handle
 *
 * @param   string   the current database name
 * @param   integer  the id of the bookmark to get
 *
 * @access  public
 */
function PMA_Bookmark_delete($db, $id)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return false;
    }

    $query  = 'DELETE FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
            . ' WHERE (user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
            . '        OR user = \'\')'
            . ' AND id = ' . $id;
    return PMA_DBI_try_query($query, $controllink);
} // end of the 'PMA_Bookmark_delete()' function


/**
 * Bookmark Support
 */
$GLOBALS['cfg']['Bookmark'] = PMA_Bookmark_getParams();

?>
