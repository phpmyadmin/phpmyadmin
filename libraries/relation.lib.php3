<?php
/**
 * Set of functions used with the relation and pdf feature
 */
if (!defined('PMA_RELATION_LIB_INCLUDED')){
    define('PMA_RELATION_LIB_INCLUDED', 1);
    /**
     * do a query as controluser if possible, otherwise
     * as normal user
     * @return  integer resultid
     * @global  string  URL of Page to show in case of error
     * @global  string  Name of db to come back to
     * @global  integer Ressourceid of DB connect as controluser
     * @global  array   Configurationinfo about relationstuff
     *
     * @author  Mike Beck<mikebeck@users.sourceforge.net>
     * @access  public
     */
     function PMA_query_as_cu($sql,$showerror=TRUE) {
        global $err_url_0, $db, $dbh, $cfgRelation;

        if (isset($dbh)) {
            PMA_mysql_select_db($cfgRelation['db'],$dbh);
            $result = @PMA_mysql_query($sql, $dbh);
            if(!$result && $showerror==TRUE){
                PMA_mysqlDie(mysql_error($dbh), $sql, '', $err_url_0);
            }
            PMA_mysql_select_db($db,$dbh);
        } else {
            PMA_mysql_select_db($cfgRelation['db']);
            $result = @PMA_mysql_query($sql);
            if($result && $showerror==TRUE){
                PMA_mysqlDie('', $sql, '', $err_url_0);
            }
            PMA_mysql_select_db($db);
        }
        if($result){
            return $result;
        } else {
            return FALSE;
        }
     }


    /**
     * Defines the relation parameters for the current user
     * just a copy of the functions used for relations ;-)
     * but added some stuff to check what will work
     *
     * @return  array    the relation parameters for the current user
     *
     * @global  array    the list of settings for the current server
     * @global  integer  the id of the current server
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     *
     * @access  public
     */
    function PMA_getRelationsParam()
    {
        global $server, $err_url_0, $db, $table,$cfg;
        $cfgRelation = '';

        $cfgRelation['relwork']     = FALSE;
        $cfgRelation['displaywork'] = FALSE;
        $cfgRelation['pdfwork']     = FALSE;
        $cfgRelation['commwork']    = FALSE;

        // No server selected -> no bookmark table
        if ($server == 0 
           || !isset($GLOBALS['cfg']['Server']['pmadb'])
           || empty($GLOBALS['cfg']['Server']['pmadb'])) {
            return '';
        }

        //      check if pmadb exists
        $tab_query    = 'SHOW DATABASES';
        $tab_rs = PMA_query_as_cu($tab_query);

        while ($curr_db = @PMA_mysql_fetch_array($tab_rs)) {
            if($curr_db[0] == $cfg['Server']['pmadb']) {
                $cfgRelation['db']    = $GLOBALS['cfg']['Server']['pmadb'];
            }
        }
        if(!isset($cfgRelation['db'])){
                $GLOBALS['cfg']['Server']['pmadb'] = FALSE;
                return;
        }
        $cfgRelation['user']  = $GLOBALS['cfg']['Server']['user'];
        $cfgRelation['db']    = $GLOBALS['cfg']['Server']['pmadb'];

        //  now i just check if all tables that i need are present
        //  so i can for example enable relations but not pdf...
        //  i was thinking of checking if they have all required columns
        //  but i fear it might be too slow
        // PMA_mysql_select_db($cfgRelation['db']);

        $tab_query    = 'SHOW TABLES FROM ' . PMA_backquote($cfgRelation['db']);
        $tab_rs = PMA_query_as_cu($tab_query);

        //while ($curr_table = @PMA_mysql_fetch_array($tab_rs)) {
        while ($curr_table = @PMA_mysql_fetch_array($tab_rs)) {
            if($curr_table[0] == $cfg['Server']['bookmarktable']) {
                continue;
            } else if ($curr_table[0] == $GLOBALS['cfg']['Server']['relation']) {
                $cfgRelation['relation']        = $curr_table[0];
            } else if ($curr_table[0] == $GLOBALS['cfg']['Server']['table_info']) {
                $cfgRelation['table_info']      = $curr_table[0];
            } else if ($curr_table[0] == $GLOBALS['cfg']['Server']['table_coords']) {
                $cfgRelation['table_coords']    = $curr_table[0];
            } else if ($curr_table[0] == $GLOBALS['cfg']['Server']['column_comments']) {
                $cfgRelation['column_comments'] = $curr_table[0];
            } else if ($curr_table[0] == $GLOBALS['cfg']['Server']['pdf_pages']) {
                $cfgRelation['pdf_pages']       = $curr_table[0];
            }
        }
        if(isset($cfgRelation['relation'])) {
            $cfgRelation['relwork'] = TRUE;
            if(isset($cfgRelation['table_info'])) {
                $cfgRelation['displaywork'] = TRUE;
            }
            if(isset($cfgRelation['table_coords']) && isset($cfgRelation['pdf_pages'])) {
                $cfgRelation['pdfwork'] = TRUE;
            }
            if(isset($cfgRelation['column_comments'])) {
                $cfgRelation['commwork'] = TRUE;
            }
        }
        mysql_free_result($tab_rs);
        return $cfgRelation;
    } // end of the 'PMA_getRelationsParam()' function


    /**
     * Get all Relations to foreign tables for a given table
     * or optionally a given column in a table
     *
     * @return  array    db,table,column
     *
     * @global  array    the list of Relationsettings
     *
     * @access  public
     * @author  Mike Beck <mikebeck@users.sourceforge.net>
     */
    function getForeigners($db,$table,$column=FALSE) {
        global $cfgRelation, $err_url_0;

        $_rel_query = 'SELECT master_field, foreign_db,foreign_table,foreign_field'
                   . ' FROM ' . PMA_backquote($cfgRelation['relation'])
                   . ' WHERE master_db =  \'' . $db . '\' '
                   . ' AND   master_table = \'' . $table . '\' ';
        if(!empty($column)){
            $_rel_query .= ' AND master_field = \'' . $column . '\'';
        }
        $_relations = PMA_query_as_cu($_rel_query);
        $i=0;
        while ($relrow = @PMA_mysql_fetch_array($_relations)) {
            $field = $relrow['master_field'];
            $foreign[$field]['foreign_db']       = $relrow['foreign_db'];
            $foreign[$field]['foreign_table']    = $relrow['foreign_table'];
            $foreign[$field]['foreign_field']    = $relrow['foreign_field'];
            $i++;
         } // end while
         if( isset($foreign) && is_array($foreign) ) {
            return $foreign;
         } else {
            return FALSE;
         }
    }   //  End function getForeigners

    /**
     *  Get the displayfield of a table
     *  @return string  fieldname
     *  @global array the list of Relationsettings
     *  @access public
     *  @author Mike Beck <mikebeck@users.sourceforge.net>
     */
     function getDisplayField($db,$table) {
        global $cfgRelation;
        $_disp_query = 'SELECT display_field FROM ' .  PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';

        $_disp_res = PMA_query_as_cu($_disp_query);
        $row       = ($_disp_res ? PMA_mysql_fetch_array($_disp_res) : '');
        if (isset($row['display_field'])) {
            return $row['display_field'];
        } else {
            return FALSE;
        }
     }
    /**
     *  Get the comments for all rows of a table
     *  @return arry  [field_name] = comment
     *  @global array the list of Relationsettings
     *  @access public
     *  @author Mike Beck <mikebeck@users.sourceforge.net>
     */
     function getComments($db,$table) {
        global $cfgRelation;
        $_com_qry  = 'SELECT column_name,comment FROM ' .  PMA_backquote($cfgRelation['column_comments'])
                   . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                   . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        $_com_rs   = PMA_query_as_cu($_com_qry);
        while ($row = @PMA_mysql_fetch_array($_com_rs)) {
            $col = $row['column_name'];
            $comment[$col] = $row['comment'];
         } // end while

         if( isset($comment) && is_array($comment) ) {
            return $comment;
         } else {
            return FALSE;
         }
     } // end function getComments
} // $__PMA_RELATION_LIB__INCLUDED
?>
