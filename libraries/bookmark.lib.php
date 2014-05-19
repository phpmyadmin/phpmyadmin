<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the bookmark feature
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Defines the bookmark parameters for the current user
 *
 * @return array    the bookmark parameters for the current user
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
 * @param string $db the current database name
 *
 * @return array the bookmarks list (key as index, label as value)
 *
 * @access public
 *
 * @global resource $controllink the controluser db connection handle
 */
function PMA_Bookmark_getList($db)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return array();
    }

    $query  = 'SELECT label, id FROM ' . PMA_Util::backquote($cfgBookmark['db'])
        . '.' . PMA_Util::backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA_Util::sqlAddSlashes($db) . '\''
        . ' AND user = \'' . PMA_Util::sqlAddSlashes($cfgBookmark['user']) . '\''
        . ' ORDER BY label';
    $per_user = $GLOBALS['dbi']->fetchResult(
        $query, 'id', 'label', $controllink, PMA_DatabaseInterface::QUERY_STORE
    );

    $query  = 'SELECT label, id FROM ' . PMA_Util::backquote($cfgBookmark['db'])
        . '.' . PMA_Util::backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA_Util::sqlAddSlashes($db) . '\''
        . ' AND user = \'\''
        . ' ORDER BY label';
    $global = $GLOBALS['dbi']->fetchResult(
        $query, 'id', 'label', $controllink, PMA_DatabaseInterface::QUERY_STORE
    );

    foreach ($global as $key => $val) {
        $global[$key] = $val . ' (' . __('shared') . ')';
    }

    $ret = $global + $per_user;

    asort($ret);

    return $ret;
} // end of the 'PMA_Bookmark_getList()' function


/**
 * Gets the sql command from a bookmark
 *
 * @param string  $db                  the current database name
 * @param mixed   $id                  the id of the bookmark to get
 * @param string  $id_field            which field to look up the $id
 * @param boolean $action_bookmark_all true: get all bookmarks regardless
 *                                     of the owning user
 * @param boolean $exact_user_match    whether to ignore bookmarks with no user
 *
 * @return string    the sql query
 *
 * @access  public
 *
 * @global  resource $controllink the controluser db connection handle
 *
 */
function PMA_Bookmark_get($db, $id, $id_field = 'id', $action_bookmark_all = false,
    $exact_user_match = false
) {
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return '';
    }

    $query = 'SELECT query FROM ' . PMA_Util::backquote($cfgBookmark['db'])
        . '.' . PMA_Util::backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA_Util::sqlAddSlashes($db) . '\'';

    if (! $action_bookmark_all) {
        $query .= ' AND (user = \''
            . PMA_Util::sqlAddSlashes($cfgBookmark['user']) . '\'';
        if (! $exact_user_match) {
            $query .= ' OR user = \'\'';
        }
        $query .= ')';
    }

    $query .= ' AND ' . PMA_Util::backquote($id_field) . ' = ' . $id;

    return $GLOBALS['dbi']->fetchValue($query, 0, 0, $controllink);
} // end of the 'PMA_Bookmark_get()' function

/**
 * Adds a bookmark
 *
 * @param array   $bkm_fields the properties of the bookmark to add; here,
 *                            $bkm_fields['bkm_sql_query'] is urlencoded
 * @param boolean $all_users  whether to make the bookmark available for all users
 *
 * @return boolean   whether the INSERT succeeds or not
 *
 * @access  public
 *
 * @global  resource $controllink the controluser db connection handle
 */
function PMA_Bookmark_save($bkm_fields, $all_users = false)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return false;
    }

    $query = 'INSERT INTO ' . PMA_Util::backquote($cfgBookmark['db'])
        . '.' . PMA_Util::backquote($cfgBookmark['table'])
        . ' (id, dbase, user, query, label)'
        . ' VALUES (NULL, \''
        . PMA_Util::sqlAddSlashes($bkm_fields['bkm_database']) . '\', '
        . '\''
        . ($all_users ? '' : PMA_Util::sqlAddSlashes($bkm_fields['bkm_user']))
        . '\', '
        . '\''
        . PMA_Util::sqlAddSlashes(urldecode($bkm_fields['bkm_sql_query']))
        . '\', '
        . '\'' . PMA_Util::sqlAddSlashes($bkm_fields['bkm_label']) . '\')';
    return $GLOBALS['dbi']->query($query, $controllink);
} // end of the 'PMA_Bookmark_save()' function


/**
 * Deletes a bookmark
 *
 * @param integer $id the id of the bookmark to delete
 *
 * @return bool true if successful
 *
 * @access  public
 *
 * @global  resource $controllink the controluser db connection handle
 */
function PMA_Bookmark_delete($id)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return false;
    }

    $query  = 'DELETE FROM ' . PMA_Util::backquote($cfgBookmark['db'])
        . '.' . PMA_Util::backquote($cfgBookmark['table'])
        . ' WHERE (user = \'' . PMA_Util::sqlAddSlashes($cfgBookmark['user']) . '\''
        . '        OR user = \'\')'
        . ' AND id = ' . $id;
    return $GLOBALS['dbi']->tryQuery($query, $controllink);
} // end of the 'PMA_Bookmark_delete()' function


/**
 * Bookmark Support
 */
if (!defined('TESTSUITE')) {
    $GLOBALS['cfg']['Bookmark'] = PMA_Bookmark_getParams();
}
?>
