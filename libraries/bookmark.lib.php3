<?php
/* $Id$ */


/**
 * Set of functions used with the bookmark feature
 */



if (!defined('PMA_BOOKMARK_LIB_INCLUDED')){
    define('PMA_BOOKMARK_LIB_INCLUDED', 1);

    /**
     * Defines the bookmark parameters for the current user
     *
     * @return  array    the bookmark parameters for the current user
     *
     * @global  array    the list of settings for the current server
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
        $cfgBookmark['db']    = $GLOBALS['cfg']['Server']['bookmarkdb'];
        $cfgBookmark['table'] = $GLOBALS['cfg']['Server']['bookmarktable'];

        return $cfgBookmark;
    } // end of the 'PMA_getBookmarksParam()' function


    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     *
     * @return  mixed    the bookmarks list if defined, false else
     *
     * @access  public
     */
    function PMA_listBookmarks($db, $cfgBookmark)
    {
        $query  = 'SELECT label, id FROM '. PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
                . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
                . ' AND user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\'';
        if (isset($GLOBALS['dbh'])) {
            $result = PMA_mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = PMA_mysql_query($query);
        }

        // There is some bookmarks -> store them
        if ($result > 0 && mysql_num_rows($result) > 0) {
            $flag = 1;
            while ($row = PMA_mysql_fetch_row($result)) {
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
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     * @param   integer  the id of the bookmark to get
     *
     * @return  string   the sql query
     *
     * @access  public
     */
    function PMA_queryBookmarks($db, $cfgBookmark, $id)
    {
        $query          = 'SELECT query FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
                        . ' WHERE dbase = \'' . PMA_sqlAddslashes($db) . '\''
                        . ' AND user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
                        . ' AND id = ' . $id;
        if (isset($GLOBALS['dbh'])) {
            $result = PMA_mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = PMA_mysql_query($query);
        }
        $bookmark_query = PMA_mysql_result($result, 0, 'query');

        return $bookmark_query;
    } // end of the 'PMA_queryBookmarks()' function


    /**
     * Adds a bookmark
     *
     * @param   array    the properties of the bookmark to add
     * @param   array    the bookmark parameters for the current user
     *
     * @access  public
     */
    function PMA_addBookmarks($fields, $cfgBookmark)
    {
        $query = 'INSERT INTO ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
               . ' (id, dbase, user, query, label) VALUES (\'\', \'' . PMA_sqlAddslashes($fields['dbase']) . '\', \'' . PMA_sqlAddslashes($fields['user']) . '\', \'' . PMA_sqlAddslashes(urldecode($fields['query'])) . '\', \'' . PMA_sqlAddslashes($fields['label']) . '\')';
        if (isset($GLOBALS['dbh'])) {
            $result = PMA_mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = PMA_mysql_query($query);
        }
    } // end of the 'PMA_addBookmarks()' function


    /**
     * Deletes a bookmark
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     * @param   integer  the id of the bookmark to get
     *
     * @access  public
     */
    function PMA_deleteBookmarks($db, $cfgBookmark, $id)
    {
        $query  = 'DELETE FROM ' . PMA_backquote($cfgBookmark['db']) . '.' . PMA_backquote($cfgBookmark['table'])
                . ' WHERE user = \'' . PMA_sqlAddslashes($cfgBookmark['user']) . '\''
                . ' AND id = ' . $id;
        if (isset($GLOBALS['dbh'])) {
            $result = PMA_mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = PMA_mysql_query($query);
        }
    } // end of the 'PMA_deleteBookmarks()' function


    /**
     * Bookmark Support
     */
    $cfg['Bookmark'] = PMA_getBookmarksParam();


} // $__PMA_BOOKMARK_LIB__
?>
