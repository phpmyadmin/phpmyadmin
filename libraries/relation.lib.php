<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used with the relation and pdf feature
 */


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
 * @param   boolean  whether to check validity of settings or not
 *
 * @return  array    the relation parameters for the current user
 *
 * @global  array    the list of settings for servers
 * @global  integer  the id of the current server
 * @global  string   the URL of the page to show in case of error
 * @global  string   the name of the current db
 * @global  string   the name of the current table
 * @global  array    configuration infos about the relations stuff
 *
 * @access  public
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 */
function PMA_getRelationsParam($verbose = FALSE)
{
    global $cfg, $server, $err_url_0, $db, $table;
    global $cfgRelation;

    $cfgRelation                = array();
    $cfgRelation['relwork']     = FALSE;
    $cfgRelation['displaywork'] = FALSE;
    $cfgRelation['bookmarkwork']= FALSE;
    $cfgRelation['pdfwork']     = FALSE;
    $cfgRelation['commwork']    = FALSE;
    $cfgRelation['mimework']    = FALSE;
    $cfgRelation['historywork'] = FALSE;
    $cfgRelation['allworks']    = FALSE;

    // No server selected -> no bookmark table
    // we return the array with the FALSEs in it,
    // to avoid some 'Unitialized string offset' errors later
    if ($server == 0
       || empty($cfg['Server'])
       || empty($cfg['Server']['pmadb'])) {
        if ($verbose == TRUE) {
            echo 'PMA Database ... '
                 . '<font color="red"><b>' . $GLOBALS['strNotOK'] . '</b></font>'
                 . '[ <a href="Documentation.html#pmadb">' . $GLOBALS['strDocu'] . '</a> ]<br />' . "\n"
                 . $GLOBALS['strGeneralRelationFeat']
                 . ' <font color="green">' . $GLOBALS['strDisabled'] . '</font>' . "\n";
        }
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
            $cfgRelation['bookmark']        = $curr_table[0];
        } else if ($curr_table[0] == $cfg['Server']['relation']) {
            $cfgRelation['relation']        = $curr_table[0];
        } else if ($curr_table[0] == $cfg['Server']['table_info']) {
            $cfgRelation['table_info']      = $curr_table[0];
        } else if ($curr_table[0] == $cfg['Server']['table_coords']) {
            $cfgRelation['table_coords']    = $curr_table[0];
        } else if ($curr_table[0] == $cfg['Server']['column_info']) {
            $cfgRelation['column_info'] = $curr_table[0];
        } else if ($curr_table[0] == $cfg['Server']['pdf_pages']) {
            $cfgRelation['pdf_pages']       = $curr_table[0];
        } else if ($curr_table[0] == $cfg['Server']['history']) {
            $cfgRelation['history'] = $curr_table[0];
        }
    } // end while
    if (isset($cfgRelation['relation'])) {
        $cfgRelation['relwork']         = TRUE;
        if (isset($cfgRelation['table_info'])) {
                $cfgRelation['displaywork'] = TRUE;
        }
    }
    if (isset($cfgRelation['table_coords']) && isset($cfgRelation['pdf_pages'])) {
        $cfgRelation['pdfwork']     = TRUE;
    }
    if (isset($cfgRelation['column_info'])) {
        $cfgRelation['commwork']    = TRUE;

        if ($cfg['Server']['verbose_check']) {
            $mime_query  = 'SHOW FIELDS FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']);
            $mime_rs     = PMA_query_as_cu($mime_query, FALSE);

            $mime_field_mimetype                = FALSE;
            $mime_field_transformation          = FALSE;
            $mime_field_transformation_options  = FALSE;
            while ($curr_mime_field = @PMA_mysql_fetch_array($mime_rs)) {
                if ($curr_mime_field[0] == 'mimetype') {
                    $mime_field_mimetype               = TRUE;
                } else if ($curr_mime_field[0] == 'transformation') {
                    $mime_field_transformation         = TRUE;
                } else if ($curr_mime_field[0] == 'transformation_options') {
                    $mime_field_transformation_options = TRUE;
                }
            }

            if ($mime_field_mimetype == TRUE
                && $mime_field_transformation == TRUE
                && $mime_field_transformation_options == TRUE) {
                $cfgRelation['mimework'] = TRUE;
            }
        } else {
            $cfgRelation['mimework'] = TRUE;
        }
    }

    if (isset($cfgRelation['history'])) {
        $cfgRelation['historywork']     = TRUE;
    }

    if (isset($cfgRelation['bookmark'])) {
        $cfgRelation['bookmarkwork']     = TRUE;
    }

    if ($cfgRelation['relwork'] == TRUE && $cfgRelation['displaywork'] == TRUE
        && $cfgRelation['pdfwork'] == TRUE && $cfgRelation['commwork'] == TRUE
        && $cfgRelation['mimework'] == TRUE && $cfgRelation['historywork'] == TRUE
        && $cfgRelation['bookmarkwork'] == TRUE) {
        $cfgRelation['allworks'] = TRUE;
    }
    if ($tab_rs) {
        mysql_free_result($tab_rs);
    } else {
        $cfg['Server']['pmadb'] = FALSE;
    }

    if ($verbose == TRUE) {
        $shit     = '<font color="red"><b>' . $GLOBALS['strNotOK'] . '</b></font> [ <a href="Documentation.html#%s">' . $GLOBALS['strDocu'] . '</a> ]';
        $hit      = '<font color="green"><b>' . $GLOBALS['strOK'] . '</b></font>';
        $enabled  = '<font color="green">' . $GLOBALS['strEnabled'] . '</font>';
        $disabled = '<font color="red">'   . $GLOBALS['strDisabled'] . '</font>';

        echo '<table>' . "\n";
        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'pmadb\'] ... </th><td align="right">'
             . (($cfg['Server']['pmadb'] == FALSE) ? sprintf($shit, 'pmadb') : $hit)
             . '</td></tr>' . "\n";
        echo '    <tr><td>&nbsp;</td></tr>' . "\n";

        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'relation\'] ... </th><td align="right">'
             . ((isset($cfgRelation['relation'])) ? $hit : sprintf($shit, 'relation'))
             . '</td></tr>' . "\n";
        echo '    <tr><td colspan=2 align="center">'. $GLOBALS['strGeneralRelationFeat'] . ': '
             . (($cfgRelation['relwork'] == TRUE) ? $enabled :  $disabled)
             . '</td></tr>' . "\n";
        echo '    <tr><td>&nbsp;</td></tr>' . "\n";

        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'table_info\']   ... </th><td align="right">'
             . (($cfgRelation['displaywork'] == FALSE) ? sprintf($shit, 'table_info') : $hit)
             . '</td></tr>' . "\n";
        echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strDisplayFeat'] . ': '
             . (($cfgRelation['displaywork'] == TRUE) ? $enabled : $disabled)
             . '</td></tr>' . "\n";
        echo '    <tr><td>&nbsp;</td></tr>' . "\n";

        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'table_coords\'] ... </th><td align="right">'
             . ((isset($cfgRelation['table_coords'])) ? $hit : sprintf($shit, 'table_coords'))
             . '</td></tr>' . "\n";
        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'pdf_pages\'] ... </th><td align="right">'
             . ((isset($cfgRelation['pdf_pages'])) ? $hit : sprintf($shit, 'table_coords'))
             . '</td></tr>' . "\n";
        echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strCreatePdfFeat'] . ': '
             . (($cfgRelation['pdfwork'] == TRUE) ? $enabled : $disabled)
             . '</td></tr>' . "\n";
        echo '    <tr><td>&nbsp;</td></tr>' . "\n";

        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'column_info\'] ... </th><td align="right">'
             . ((isset($cfgRelation['column_info'])) ? $hit : sprintf($shit, 'col_com'))
             . '</td></tr>' . "\n";
        echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strColComFeat'] . ': '
             . (($cfgRelation['commwork'] == TRUE) ? $enabled : $disabled)
             . '</td></tr>' . "\n";
        echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strBookmarkQuery'] . ': '
             . (($cfgRelation['bookmarkwork'] == TRUE) ? $enabled : $disabled)
             . '</td></tr>' . "\n";
        echo '    <tr><th align="left">MIME ...</th><td align="right">'
             . (($cfgRelation['mimework'] == TRUE) ? $hit : sprintf($shit, 'col_com'))
             . '</td></tr>' . "\n";

             if (($cfgRelation['commwork'] == TRUE) && ($cfgRelation['mimework'] != TRUE)) {
                 echo '<tr><td colspan=2 align="left">' . $GLOBALS['strUpdComTab'] . '</td></tr>' . "\n";
             }

        echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'history\'] ... </th><td align="right">'
             . ((isset($cfgRelation['history'])) ? $hit : sprintf($shit, 'history'))
             . '</td></tr>' . "\n";
        echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strQuerySQLHistory'] . ': '
             . (($cfgRelation['historywork'] == TRUE) ? $enabled : $disabled)
             . '</td></tr>' . "\n";

        echo '</table>' . "\n";
    } // end if ($verbose == TRUE) {

    return $cfgRelation;
} // end of the 'PMA_getRelationsParam()' function


/**
 * Gets all Relations to foreign tables for a given table or
 * optionally a given column in a table
 *
 * @param   string   the name of the db to check for
 * @param   string   the name of the table to check for
 * @param   string   the name of the column to check for
 * @param   string   the source for foreign key information
 *
 * @return  array    db,table,column
 *
 * @global  array    the list of relations settings
 * @global  string   the URL of the page to show in case of error
 *
 * @access  public
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net> and Marc Delisle
 */
function PMA_getForeigners($db, $table, $column = '', $source = 'both') {
    global $cfgRelation, $err_url_0;

    if ($cfgRelation['relwork'] && ($source == 'both' || $source == 'internal')) {
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
    }

    if (($source == 'both' || $source == 'innodb') && !empty($table)) {
        $show_create_table_query = 'SHOW CREATE TABLE '
            . PMA_backquote($db) . '.' . PMA_backquote($table);
        $show_create_table_res = PMA_mysql_query($show_create_table_query);
        list(,$show_create_table) = PMA_mysql_fetch_row($show_create_table_res);

        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

        foreach($analyzed_sql[0]['foreign_keys'] AS $one_key) {

        // the analyzer may return more than one column name in the
        // index list or the ref_index_list
            foreach($one_key['index_list'] AS $i => $field) {

        // If a foreign key is defined in the 'internal' source (pmadb)
        // and in 'innodb', we won't get it twice if $source='both'
        // because we use $field as key

                $foreign[$field]['constraint'] = $one_key['constraint'];

                if (isset($one_key['ref_db_name'])) {
                    $foreign[$field]['foreign_db']    = $one_key['ref_db_name'];
                } else {
                    $foreign[$field]['foreign_db']    = $db;
                }
                $foreign[$field]['foreign_table'] = $one_key['ref_table_name'];
                $foreign[$field]['foreign_field'] = $one_key['ref_index_list'][$i];
                if (isset($one_key['on_delete'])) {
                    $foreign[$field]['on_delete'] = $one_key['on_delete'];
                }
                if (isset($one_key['on_update'])) {
                    $foreign[$field]['on_update'] = $one_key['on_update'];
                }
            }
        }
    }

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
function PMA_getComments($db, $table = '') {
    global $cfgRelation;

    if ($table != '') {
        $com_qry  = 'SELECT column_name, ' . PMA_backquote('comment') . ' FROM ' . PMA_backquote($cfgRelation['column_info'])
                  . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                  . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        $com_rs   = PMA_query_as_cu($com_qry);
    } else {
        $com_qry  = 'SELECT comment FROM ' . PMA_backquote($cfgRelation['column_info'])
                  . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                  . ' AND table_name = \'\''
                  . ' AND column_name = \'(db_comment)\'';
        $com_rs   = PMA_query_as_cu($com_qry);
    }

    $i = 0;
    while ($row = @PMA_mysql_fetch_array($com_rs)) {
        $i++;
        $col           = ($table != '' ? $row['column_name'] : $i);

        if (strlen($row['comment']) > 0) {
            $comment[$col] = $row['comment'];
        }

    } // end while

    if (isset($comment) && is_array($comment)) {
        return $comment;
     } else {
        return FALSE;
     }
 } // end of the 'PMA_getComments()' function

/**
* Adds/removes slashes if required
*
* @param   string  the string to slash
*
* @return  string  the slashed string
*
* @access  public
*/
function PMA_handleSlashes($val) {
  return (get_magic_quotes_gpc() ? str_replace('\\"', '"', $val) : PMA_sqlAddslashes($val));
} // end of the "PMA_handleSlashes()" function

/**
* Set a single comment to a certain value.
*
* @param   string   the name of the db
* @param   string   the name of the table
* @param   string   the name of the column
* @param   string   the value of the column
* @param   string   (optional) if a column is renamed, this is the name of the former key which will get deleted
*
* @return  boolean  true, if comment-query was made.
*
* @global  array    the list of relations settings
*
* @access  public
*/
function PMA_setComment($db, $table, $key, $value, $removekey = '') {
    global $cfgRelation;

    if ($removekey != '' AND $removekey != $key) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND column_name = \'' . PMA_sqlAddslashes($removekey) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    $test_qry  = 'SELECT ' . PMA_backquote('comment') . ', mimetype, transformation, transformation_options FROM ' . PMA_backquote($cfgRelation['column_info'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                . ' AND column_name = \'' . PMA_sqlAddslashes($key) . '\'';
    $test_rs   = PMA_query_as_cu($test_qry);

    if ($test_rs && mysql_num_rows($test_rs) > 0) {
        $row = @PMA_mysql_fetch_array($test_rs);

        if (strlen($value) > 0 || strlen($row['mimetype']) > 0 || strlen($row['transformation']) > 0 || strlen($row['transformation_options']) > 0) {
            $upd_query = 'UPDATE ' . PMA_backquote($cfgRelation['column_info'])
                   . ' SET ' . PMA_backquote('comment') . ' = \'' . PMA_sqlAddslashes($value) . '\''
                   . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                   . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                   . ' AND column_name = \'' . PMA_sqlAddSlashes($key) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                   . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                   . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                   . ' AND column_name = \'' . PMA_sqlAddslashes($key) . '\'';
        }
    } else if (strlen($value) > 0) {
        $upd_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['column_info'])
                   . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ') '
                   . ' VALUES('
                   . '\'' . PMA_sqlAddslashes($db) . '\','
                   . '\'' . PMA_sqlAddslashes($table) . '\','
                   . '\'' . PMA_sqlAddslashes($key) . '\','
                   . '\'' . PMA_sqlAddslashes($value) . '\')';
    }

    if (isset($upd_query)){
        $upd_rs    = PMA_query_as_cu($upd_query);
        unset($upd_query);
        return true;
    } else {
        return false;
    }
} // end of 'PMA_setComment()' function

/**
* Set a SQL history entry
*
* @param   string   the name of the db
* @param   string   the name of the table
* @param   string   the username
* @param   string   the sql query
*
* @return  boolean  true
*
* @access  public
*/
function PMA_setHistory($db, $table, $username, $sqlquery) {
    global $cfgRelation;

    $hist_rs    = PMA_query_as_cu('INSERT INTO ' . PMA_backquote($cfgRelation['history']) . ' ('
                . PMA_backquote('username') . ','
                . PMA_backquote('db') . ','
                . PMA_backquote('table') . ','
                . PMA_backquote('timevalue') . ','
                . PMA_backquote('sqlquery')
                . ') VALUES ('
                . '\'' . PMA_sqlAddslashes($username) . '\','
                . '\'' . PMA_sqlAddslashes($db) . '\','
                . '\'' . PMA_sqlAddslashes($table) . '\','
                . 'NOW(),'
                . '\'' . PMA_sqlAddslashes($sqlquery) . '\')');
    return true;
} // end of 'PMA_setHistory()' function

/**
* Gets a SQL history entry
*
* @param   string   the username
*
* @return  array    list of history items
*
* @access  public
*/
function PMA_getHistory($username) {
    global $cfgRelation;

    $hist_rs    = PMA_query_as_cu('SELECT '
                    . PMA_backquote('db') . ','
                    . PMA_backquote('table') . ','
                    . PMA_backquote('sqlquery')
                    . ' FROM ' . PMA_backquote($cfgRelation['history']) . ' WHERE username = \'' . PMA_sqlAddslashes($username) . '\' ORDER BY id DESC');

    $history = array();

    while ($row = @PMA_mysql_fetch_array($hist_rs)) {
        $history[] = $row;
    }

    return $history;

} // end of 'PMA_getHistory()' function

/**
* Set a SQL history entry
*
* @param   string   the name of the db
* @param   string   the name of the table
* @param   string   the username
* @param   string   the sql query
*
* @return  boolean  true
*
* @access  public
*/
function PMA_purgeHistory($username) {
    global $cfgRelation, $cfg;

    $purge_rs = PMA_query_as_cu('SELECT timevalue FROM ' . PMA_backquote($cfgRelation['history']) . ' WHERE username = \'' . PMA_sqlAddSlashes($username) . '\' ORDER BY timevalue DESC LIMIT ' . $cfg['QueryHistoryMax'] . ', 1');
    $i = 0;
    $row = @PMA_mysql_fetch_array($purge_rs);

    if (is_array($row) && isset($row[0]) && $row[0] > 0) {
        $maxtime = $row[0];
        // quotes added around $maxtime to prevent a difficult to
        // reproduce problem
        $remove_rs = PMA_query_as_cu('DELETE FROM ' . PMA_backquote($cfgRelation['history']) . ' WHERE timevalue <= "' . $maxtime . '"');
    }

    return true;
} // end of 'PMA_purgeHistory()' function

/**
* Outputs dropdown with values of foreign fields
*
* @param   string   the query of the foreign keys
* @param   string   the foreign field
* @param   string   the foreign field to display
* @param   string   the current data of the dropdown
*
* @return  string   the <option value=""><option>s
*
* @access  public
*/
function PMA_foreignDropdown($disp, $foreign_field, $foreign_display, $data, $max = 100) {
    global $cfg;

    $ret = '<option value=""></option>' . "\n";

    $reloptions = array('content-id' => array(), 'id-content' => array());
    while ($relrow = @PMA_mysql_fetch_array($disp)) {
        $key   = $relrow[$foreign_field];
        if (strlen($relrow[$foreign_display]) <= $cfg['LimitChars']) {
            $value  = (($foreign_display != FALSE) ? htmlspecialchars($relrow[$foreign_display]) : '');
            $vtitle = '';
        } else {
            $vtitle = htmlspecialchars($relrow[$foreign_display]);
            $value  = (($foreign_display != FALSE) ? htmlspecialchars(substr($vtitle, 0, $cfg['LimitChars']) . '...') : '');
        }

        $reloption = '<option value="' . htmlspecialchars($key) . '"';
        if ($vtitle != '') {
            $reloption .= ' title="' . $vtitle . '"';
        }

        if ($key == $data) {
           $reloption .= ' selected="selected"';
        } // end if

        $reloptions['id-content'][] = $reloption . '>' . $value . '&nbsp;-&nbsp;' . htmlspecialchars($key) .  '</option>' . "\n";
        $reloptions['content-id'][] = $reloption . '>' . htmlspecialchars($key) .  '&nbsp;-&nbsp;' . $value . '</option>' . "\n";
    } // end while

    if ($max == -1 || count($reloptions['content-id']) < $max) {
        $ret .= implode('', $reloptions['content-id']);
        if (count($reloptions['content-id']) > 0) {
            $ret .= '<option value=""></option>' . "\n";
            $ret .= '<option value=""></option>' . "\n";
        }
    }

    $ret .= implode('', $reloptions['id-content']);

    return $ret;
} // end of 'PMA_foreignDropdown()' function

?>
