<?php
/* $Id$ */

/**
 * Set of functions used with the relation and pdf feature
 */


if (!defined('PMA_RELATION_LIB_INCLUDED')){
    define('PMA_RELATION_LIB_INCLUDED', 1);

    /**
     * Executes a query as controluser if possible, otherwise as normal user
     *
     * @param   string   the query to execute
     * @param   boolean  whether to display SQL error messages or not
     *
     * @return  integer  the result id
     *
     * @global  string   the URL of the page to show in case of error
     * @global  string   the name of db to come back to
     * @global  integer  the ressource id of DB connect as controluser
     * @global  array    configuration infos about the relations stuff
     *
     * @access  public
     *
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
     function PMA_query_as_cu($sql, $show_error = TRUE) {
        global $err_url_0, $db, $dbh, $cfgRelation;

        if (isset($dbh)) {
            PMA_mysql_select_db($cfgRelation['db'], $dbh);
            $result = @PMA_mysql_query($sql, $dbh);
            if (!$result && $show_error == TRUE) {
                PMA_mysqlDie(mysql_error($dbh), $sql, '', $err_url_0);
            }
            PMA_mysql_select_db($db, $dbh);
        } else {
            PMA_mysql_select_db($cfgRelation['db']);
            $result = @PMA_mysql_query($sql);
            if ($result && $show_error == TRUE) {
                PMA_mysqlDie('', $sql, '', $err_url_0);
            }
            PMA_mysql_select_db($db);
        } // end if... else...

        if ($result) {
            return $result;
        } else {
            return FALSE;
        }
     } // end of the "PMA_query_as_cu()" function


    /**
     * Defines the relation parameters for the current user
     * just a copy of the functions used for relations ;-)
     * but added some stuff to check what will work
     *
     * @return  array    the relation parameters for the current user
     *
     * @global  array    the list of settings for servers
     * @global  integer  the id of the current server
     * @global  string   the URL of the page to show in case of error
     * @global  string   the name of the current db
     * @global  string   the name of the current table
     *
     * @access  public
     *
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
    function PMA_getRelationsParam()
    {
        global $cfg, $server, $err_url_0, $db, $table;

        $cfgRelation                = array();
        $cfgRelation['relwork']     = FALSE;
        $cfgRelation['displaywork'] = FALSE;
        $cfgRelation['pdfwork']     = FALSE;
        $cfgRelation['commwork']    = FALSE;

        // No server selected -> no bookmark table
        // we return the array with the FALSEs in it,
        // to avoid some 'Unitialized string offset' errors later
        if ($server == 0
           || empty($cfg['Server'])
           || empty($cfg['Server']['pmadb'])) {
            return $cfgRelation;
        }

        $cfgRelation['user']  = $cfg['Server']['user'];
        $cfgRelation['db']    = $cfg['Server']['pmadb'];

        //  Now I just check if all tables that i need are present so I can for
        //  example enable relations but not pdf...
        //  I was thinking of checking if they have all required columns but I
        //  fear it might be too slow
        // PMA_mysql_select_db($cfgRelation['db']);

        $tab_query = 'SHOW TABLES FROM ' . PMA_backquote($cfgRelation['db']);
        $tab_rs    = PMA_query_as_cu($tab_query, FALSE);

        while ($curr_table = @PMA_mysql_fetch_array($tab_rs)) {
            if ($curr_table[0] == $cfg['Server']['bookmarktable']) {
                continue;
            } else if ($curr_table[0] == $cfg['Server']['relation']) {
                $cfgRelation['relation']        = $curr_table[0];
            } else if ($curr_table[0] == $cfg['Server']['table_info']) {
                $cfgRelation['table_info']      = $curr_table[0];
            } else if ($curr_table[0] == $cfg['Server']['table_coords']) {
                $cfgRelation['table_coords']    = $curr_table[0];
            } else if ($curr_table[0] == $cfg['Server']['column_comments']) {
                $cfgRelation['column_comments'] = $curr_table[0];
            } else if ($curr_table[0] == $cfg['Server']['pdf_pages']) {
                $cfgRelation['pdf_pages']       = $curr_table[0];
            }
        } // end while
        if (isset($cfgRelation['relation'])) {
            $cfgRelation['relwork']         = TRUE;
            if (isset($cfgRelation['table_info'])) {
                $cfgRelation['displaywork'] = TRUE;
            }
            if (isset($cfgRelation['table_coords']) && isset($cfgRelation['pdf_pages'])) {
                $cfgRelation['pdfwork']     = TRUE;
            }
            if (isset($cfgRelation['column_comments'])) {
                $cfgRelation['commwork']    = TRUE;
            }
        } // end if

        if ($tab_rs) {
            mysql_free_result($tab_rs);
        } else {
            $cfg['Server']['pmadb'] = FALSE;
        }

        return $cfgRelation;
    } // end of the 'PMA_getRelationsParam()' function


    /**
     * Gets all Relations to foreign tables for a given table or
     * optionally a given column in a table
     *
     * @param   string   the name of the db to check for
     * @param   string   the name of the table to check for
     * @param   string   the name of the column to check for
     *
     * @return  array    db,table,column
     *
     * @global  array    the list of relations settings
     * @global  string   the URL of the page to show in case of error
     *
     * @access  public
     *
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
    function PMA_getForeigners($db, $table, $column = '') {
        global $cfgRelation, $err_url_0;

        $rel_query     = 'SELECT master_field, foreign_db, foreign_table, foreign_field'
                       . ' FROM ' . PMA_backquote($cfgRelation['relation'])
                       . ' WHERE master_db =  \'' . PMA_sqlAddslashes($db) . '\' '
                       . ' AND   master_table = \'' . PMA_sqlAddslashes($table) . '\' ';
        if (!empty($column)) {
            $rel_query .= ' AND master_field = \'' . PMA_sqlAddslashes($column) . '\'';
        }
        $relations     = PMA_query_as_cu($rel_query);
        $i = 0;
        while ($relrow = @PMA_mysql_fetch_array($relations)) {
            $field                            = $relrow['master_field'];
            $foreign[$field]['foreign_db']    = $relrow['foreign_db'];
            $foreign[$field]['foreign_table'] = $relrow['foreign_table'];
            $foreign[$field]['foreign_field'] = $relrow['foreign_field'];
            $i++;
         } // end while

         if (isset($foreign) && is_array($foreign)) {
            return $foreign;
         } else {
            return FALSE;
         }
    } // end of the 'PMA_getForeigners()' function


    /**
     * Gets the display field of a table
     *
     * @param   string   the name of the db to check for
     * @param   string   the name of the table to check for
     *
     * @return  string   field name
     *
     * @global  array    the list of relations settings
     *
     * @access  public
     *
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
    function PMA_getDisplayField($db, $table) {
        global $cfgRelation;

        $disp_query = 'SELECT display_field FROM ' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';

        $disp_res   = PMA_query_as_cu($disp_query);
        $row        = ($disp_res ? PMA_mysql_fetch_array($disp_res) : '');
        if (isset($row['display_field'])) {
            return $row['display_field'];
        } else {
            return FALSE;
        }
    } // end of the 'PMA_getDisplayField()' function


    /**
     * Gets the comments for all rows of a table
     *
     * @param   string   the name of the db to check for
     * @param   string   the name of the table to check for
     *
     * @return  array    [field_name] = comment
     *
     * @global  array    the list of relations settings
     *
     * @access  public
     *
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
    function PMA_getComments($db, $table) {
        global $cfgRelation;

        $com_qry  = 'SELECT column_name, comment FROM ' . PMA_backquote($cfgRelation['column_comments'])
                  . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                  . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        $com_rs   = PMA_query_as_cu($com_qry);

        while ($row = @PMA_mysql_fetch_array($com_rs)) {
            $col           = $row['column_name'];
            $comment[$col] = $row['comment'];
        } // end while

        if (isset($comment) && is_array($comment)) {
            return $comment;
         } else {
            return FALSE;
         }
     } // end of the 'PMA_getComments()' function
} // $__PMA_RELATION_LIB__
?>
