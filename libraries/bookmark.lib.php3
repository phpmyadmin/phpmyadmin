<?php
/* $Id$ */


/**
 * Set of functions used with the bookmark feature
 */



if (!defined('__LIB_BOOKMARK__')){
    define('__LIB_BOOKMARK__', 1);

    /**
     * Defines the bookmark parameters for the current user
     *
     * @return  array    the bookmark parameters for the current user
     *
     * @global  array    the list of settings for the current server
     * @global  integer  the id of the current server
     *
     * @access	public
     */
    function get_bookmarks_param()
    {
        global $cfgServer;
        global $server;

        $cfgBookmark = '';

        // No server selected -> no bookmark table
        if ($server == 0) {
            return '';
        }

        $cfgBookmark['user']  = $cfgServer['user'];
        $cfgBookmark['db']    = $cfgServer['bookmarkdb'];
        $cfgBookmark['table'] = $cfgServer['bookmarktable'];

        return $cfgBookmark;
    } // end of the 'get_bookmarks_param()' function


    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     *
     * @return  mixed    the bookmarks list if defined, false else
     *
     * @access	public
     */
    function list_bookmarks($db, $cfgBookmark)
    {
        $query  = 'SELECT label, id FROM '. backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
                . ' WHERE dbase = \'' . sql_addslashes($db) . '\''
                . ' AND user = \'' . sql_addslashes($cfgBookmark['user']) . '\'';
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }

        // There is some bookmarks -> store them
        if ($result > 0 && mysql_num_rows($result) > 0) {
            $flag = 1;
            while ($row = mysql_fetch_row($result)) {
                $bookmark_list[$flag . ' - ' . $row[0]] = $row[1];
                $flag++;
            } // end while
            return $bookmark_list;
        }
        // No bookmarks for the current database
        else {
            return FALSE;
        }
    } // end of the 'list_bookmarks()' function


    /**
     * Gets the sql command from a bookmark
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     * @param   integer  the id of the bookmark to get
     *
     * @return  string   the sql query
     *
     * @access	public
     */
    function query_bookmarks($db, $cfgBookmark, $id)
    {
        $query          = 'SELECT query FROM ' . backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
                        . ' WHERE dbase = \'' . sql_addslashes($db) . '\''
                        . ' AND user = \'' . sql_addslashes($cfgBookmark['user']) . '\''
                        . ' AND id = ' . $id;
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }
        $bookmark_query = mysql_result($result, 0, 'query');

        return $bookmark_query;
    } // end of the 'query_bookmarks()' function


    /**
     * Adds a bookmark
     *
     * @param   array    the properties of the bookmark to add
     * @param   array    the bookmark parameters for the current user
     *
     * @access	public
     */
    function add_bookmarks($fields, $cfgBookmark)
    {
        $query = 'INSERT INTO ' . backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
               . ' (id, dbase, user, query, label) VALUES (\'\', \'' . sql_addslashes($fields['dbase']) . '\', \'' . sql_addslashes($fields['user']) . '\', \'' . sql_addslashes(urldecode($fields['query'])) . '\', \'' . sql_addslashes($fields['label']) . '\')';
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }
    } // end of the 'add_bookmarks()' function


    /**
     * Deletes a bookmark
     *
     * @param   string   the current database name
     * @param   array    the bookmark parameters for the current user
     * @param   integer  the id of the bookmark to get
     *
     * @access	public
     */
    function delete_bookmarks($db, $cfgBookmark, $id)
    {
        $query  = 'DELETE FROM ' . backquote($cfgBookmark['db']) . '.' . backquote($cfgBookmark['table'])
                . ' WHERE user = \'' . sql_addslashes($cfgBookmark['user']) . '\''
                . ' AND id = ' . $id;
        if (isset($GLOBALS['dbh'])) {
            $result = mysql_query($query, $GLOBALS['dbh']);
        } else {
            $result = mysql_query($query);
        }
    } // end of the 'delete_bookmarks()' function


    /**
     * Bookmark Support
     */
    $cfgBookmark = get_bookmarks_param();


} // $__LIB_BOOKMARK__
?>
