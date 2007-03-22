<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the relation and pdf feature
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/Table.class.php';

/**
 * Executes a query as controluser if possible, otherwise as normal user
 *
 * @param   string    the query to execute
 * @param   boolean   whether to display SQL error messages or not
 *
 * @return  integer   the result id
 *
 * @access  public
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 */
function PMA_query_as_cu($sql, $show_error = true, $options = 0)
{
    // Comparing resource ids works on PHP 5 because, when no controluser
    // is defined, connecting with the same user for controllink does
    // not create a new connection. However a new connection is created
    // on PHP 4, so we cannot directly compare resource ids.

    if ($GLOBALS['controllink'] == $GLOBALS['userlink'] || PMA_MYSQL_INT_VERSION < 50000) {
        PMA_DBI_select_db($GLOBALS['cfg']['Server']['pmadb'], $GLOBALS['controllink']);
    }
    if ($show_error) {
        $result = PMA_DBI_query($sql, $GLOBALS['controllink'], $options);
    } else {
        $result = @PMA_DBI_try_query($sql, $GLOBALS['controllink'], $options);
    } // end if... else...
    // It makes no sense to restore database on control user
    if ($GLOBALS['controllink'] == $GLOBALS['userlink'] || PMA_MYSQL_INT_VERSION < 50000) {
        PMA_DBI_select_db($GLOBALS['db'], $GLOBALS['controllink']);
    }

    if ($result) {
        return $result;
    } else {
        return false;
    }
} // end of the "PMA_query_as_cu()" function

/**
 * @uses    $GLOBALS['cfgRelation'] to set it
 * @uses    PMA__getRelationsParam()
 * @uses    PMA_printRelationsParamDiagnostic()
 * @param   bool    $verbose    whether to print diagnostic info
 * @return  array   $cfgRelation
 */
function PMA_getRelationsParam($verbose = false)
{
    static $cfgRelation = null;

    if (null === $cfgRelation) {
        $cfgRelation = PMA__getRelationsParam();
    }

    if ($verbose) {
        PMA_printRelationsParamDiagnostic($cfgRelation);
    }

    // just for BC
    $GLOBALS['cfgRelation'] = $cfgRelation;

    return $cfgRelation;
}

/**
 * prints out diagnostic info for pma relation feature
 *
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['controllink']
 * @uses    $GLOBALS['strNotOK']
 * @uses    $GLOBALS['strDocu']
 * @uses    $GLOBALS['strGeneralRelationFeat']
 * @uses    $GLOBALS['strDisabled']
 * @uses    $GLOBALS['strEnabled']
 * @uses    $GLOBALS['strDisplayFeat']
 * @uses    $GLOBALS['strCreatePdfFeat']
 * @uses    $GLOBALS['strColComFeat']
 * @uses    $GLOBALS['strBookmarkQuery']
 * @uses    $GLOBALS['strUpdComTab']
 * @uses    $GLOBALS['strQuerySQLHistory']
 * @uses    $GLOBALS['strDesigner']
 * @uses    $cfg['Server']['pmadb']
 * quses    sprintf()
 * @param   array   $cfgRelation
 */
function PMA_printRelationsParamDiagnostic($cfgRelation)
{
    if (false === $GLOBALS['cfg']['Server']['pmadb']) {
        echo 'PMA Database ... '
             . '<font color="red"><b>' . $GLOBALS['strNotOK'] . '</b></font>'
             . '[ <a href="Documentation.html#pmadb">' . $GLOBALS['strDocu']
             . '</a> ]<br />' . "\n"
             . $GLOBALS['strGeneralRelationFeat']
             . ' <font color="green">' . $GLOBALS['strDisabled']
             . '</font>' . "\n";
        return;
    }

    $shit     = '<font color="red"><b>' . $GLOBALS['strNotOK']
        . '</b></font> [ <a href="Documentation.html#%s">'
        . $GLOBALS['strDocu'] . '</a> ]';
    $hit      = '<font color="green"><b>' . $GLOBALS['strOK'] . '</b></font>';
    $enabled  = '<font color="green">' . $GLOBALS['strEnabled'] . '</font>';
    $disabled = '<font color="red">'   . $GLOBALS['strDisabled'] . '</font>';

    echo '<table>' . "\n";
    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'pmadb\'] ... </th><td align="right">'
         . (($GLOBALS['cfg']['Server']['pmadb'] == false) ? sprintf($shit, 'pmadb') : $hit)
         . '</td></tr>' . "\n";
    echo '    <tr><td>&nbsp;</td></tr>' . "\n";

    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'relation\'] ... </th><td align="right">'
         . ((isset($cfgRelation['relation'])) ? $hit : sprintf($shit, 'relation'))
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">'. $GLOBALS['strGeneralRelationFeat'] . ': '
         . ($cfgRelation['relwork'] ? $enabled :  $disabled)
         . '</td></tr>' . "\n";
    echo '    <tr><td>&nbsp;</td></tr>' . "\n";

    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'table_info\']   ... </th><td align="right">'
         . (($cfgRelation['displaywork'] == false) ? sprintf($shit, 'table_info') : $hit)
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strDisplayFeat'] . ': '
         . ($cfgRelation['displaywork'] ? $enabled : $disabled)
         . '</td></tr>' . "\n";
    echo '    <tr><td>&nbsp;</td></tr>' . "\n";

    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'table_coords\'] ... </th><td align="right">'
         . ((isset($cfgRelation['table_coords'])) ? $hit : sprintf($shit, 'table_coords'))
         . '</td></tr>' . "\n";
    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'pdf_pages\'] ... </th><td align="right">'
         . ((isset($cfgRelation['pdf_pages'])) ? $hit : sprintf($shit, 'table_coords'))
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strCreatePdfFeat'] . ': '
         . ($cfgRelation['pdfwork'] ? $enabled : $disabled)
         . '</td></tr>' . "\n";
    echo '    <tr><td>&nbsp;</td></tr>' . "\n";

    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'column_info\'] ... </th><td align="right">'
         . ((isset($cfgRelation['column_info'])) ? $hit : sprintf($shit, 'col_com'))
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strColComFeat'] . ': '
         . ($cfgRelation['commwork'] ? $enabled : $disabled)
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strBookmarkQuery'] . ': '
         . ($cfgRelation['bookmarkwork'] ? $enabled : $disabled)
         . '</td></tr>' . "\n";
    echo '    <tr><th align="left">MIME ...</th><td align="right">'
         . ($cfgRelation['mimework'] ? $hit : sprintf($shit, 'col_com'))
         . '</td></tr>' . "\n";

    if ($cfgRelation['commwork'] && ! $cfgRelation['mimework']) {
        echo '<tr><td colspan=2 align="left">' . $GLOBALS['strUpdComTab'] . '</td></tr>' . "\n";
    }

    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'history\'] ... </th><td align="right">'
         . ((isset($cfgRelation['history'])) ? $hit : sprintf($shit, 'history'))
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strQuerySQLHistory'] . ': '
         . ($cfgRelation['historywork'] ? $enabled : $disabled)
         . '</td></tr>' . "\n";

    echo '    <tr><th align="left">$cfg[\'Servers\'][$i][\'designer_coords\'] ... </th><td align="right">'
         . ((isset($cfgRelation['designer_coords'])) ? $hit : sprintf($shit, 'designer_coords'))
         . '</td></tr>' . "\n";
    echo '    <tr><td colspan=2 align="center">' . $GLOBALS['strDesigner'] . ': '
         . ($cfgRelation['designerwork'] ? $enabled : $disabled)
         . '</td></tr>' . "\n";

    echo '</table>' . "\n";
}

/**
 * Defines the relation parameters for the current user
 * just a copy of the functions used for relations ;-)
 * but added some stuff to check what will work
 *
 * @uses    $cfg['Server']['user']
 * @uses    $cfg['Server']['pmadb']
 * @uses    $cfg['Server']['verbose_check']
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['controllink']
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_backquote()
 * @uses    PMA_query_as_cu()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_DBI_free_result()
 * @access  protected
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @return  array    the relation parameters for the current user
 */
function PMA__getRelationsParam()
{
    $cfgRelation                = array();
    $cfgRelation['relwork']     = false;
    $cfgRelation['displaywork'] = false;
    $cfgRelation['bookmarkwork']= false;
    $cfgRelation['pdfwork']     = false;
    $cfgRelation['commwork']    = false;
    $cfgRelation['mimework']    = false;
    $cfgRelation['historywork'] = false;
    $cfgRelation['designerwork'] = false;
    $cfgRelation['allworks']    = false;
    $cfgRelation['user']        = null;
    $cfgRelation['db']          = null;

    if ($GLOBALS['server'] == 0 || empty($GLOBALS['cfg']['Server']['pmadb'])
     || ! PMA_DBI_select_db($GLOBALS['cfg']['Server']['pmadb'], $GLOBALS['controllink'])) {
        // No server selected -> no bookmark table
        // we return the array with the falses in it,
        // to avoid some 'Unitialized string offset' errors later
        $GLOBALS['cfg']['Server']['pmadb'] = false;
        return $cfgRelation;
    }


    $cfgRelation['user']  = $GLOBALS['cfg']['Server']['user'];
    $cfgRelation['db']    = $GLOBALS['cfg']['Server']['pmadb'];

    //  Now I just check if all tables that i need are present so I can for
    //  example enable relations but not pdf...
    //  I was thinking of checking if they have all required columns but I
    //  fear it might be too slow

    $tab_query = 'SHOW TABLES FROM ' . PMA_backquote($GLOBALS['cfg']['Server']['pmadb']);
    $tab_rs    = PMA_query_as_cu($tab_query, false, PMA_DBI_QUERY_STORE);

    if (! $tab_rs) {
        // query failed ... ?
        //$GLOBALS['cfg']['Server']['pmadb'] = false;
        return $cfgRelation;
    }

    while ($curr_table = @PMA_DBI_fetch_row($tab_rs)) {
        if ($curr_table[0] == $GLOBALS['cfg']['Server']['bookmarktable']) {
            $cfgRelation['bookmark']        = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['relation']) {
            $cfgRelation['relation']        = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['table_info']) {
            $cfgRelation['table_info']      = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['table_coords']) {
            $cfgRelation['table_coords']    = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['designer_coords']) {
            $cfgRelation['designer_coords']    = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['column_info']) {
            $cfgRelation['column_info'] = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['pdf_pages']) {
            $cfgRelation['pdf_pages']       = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['history']) {
            $cfgRelation['history'] = $curr_table[0];
        }
    } // end while
    PMA_DBI_free_result($tab_rs);

    if (isset($cfgRelation['relation'])) {
        $cfgRelation['relwork']         = true;
        if (isset($cfgRelation['table_info'])) {
                $cfgRelation['displaywork'] = true;
        }
    }
    if (isset($cfgRelation['table_coords']) && isset($cfgRelation['pdf_pages'])) {
        $cfgRelation['pdfwork']     = true;
    }
    if (isset($cfgRelation['column_info'])) {
        $cfgRelation['commwork']    = true;

        if ($GLOBALS['cfg']['Server']['verbose_check']) {
            $mime_query  = 'SHOW FIELDS FROM '
                . PMA_backquote($cfgRelation['db']) . '.'
                . PMA_backquote($cfgRelation['column_info']);
            $mime_rs     = PMA_query_as_cu($mime_query, false);

            $mime_field_mimetype                = false;
            $mime_field_transformation          = false;
            $mime_field_transformation_options  = false;
            while ($curr_mime_field = @PMA_DBI_fetch_row($mime_rs)) {
                if ($curr_mime_field[0] == 'mimetype') {
                    $mime_field_mimetype               = true;
                } elseif ($curr_mime_field[0] == 'transformation') {
                    $mime_field_transformation         = true;
                } elseif ($curr_mime_field[0] == 'transformation_options') {
                    $mime_field_transformation_options = true;
                }
            }
            PMA_DBI_free_result($mime_rs);

            if ($mime_field_mimetype
             && $mime_field_transformation
             && $mime_field_transformation_options) {
                $cfgRelation['mimework'] = true;
            }
        } else {
            $cfgRelation['mimework'] = true;
        }
    }

    if (isset($cfgRelation['history'])) {
        $cfgRelation['historywork']     = true;
    }

    // we do not absolutely need that the internal relations or the PDF
    // schema feature be activated
    if (isset($cfgRelation['designer_coords'])) {
        $cfgRelation['designerwork']     = true;
    }

    if (isset($cfgRelation['bookmark'])) {
        $cfgRelation['bookmarkwork']     = true;
    }

    if ($cfgRelation['relwork'] && $cfgRelation['displaywork']
     && $cfgRelation['pdfwork'] && $cfgRelation['commwork']
     && $cfgRelation['mimework'] && $cfgRelation['historywork']
     && $cfgRelation['bookmarkwork'] && $cfgRelation['designerwork']) {
        $cfgRelation['allworks'] = true;
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
function PMA_getForeigners($db, $table, $column = '', $source = 'both')
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['relwork'] && ($source == 'both' || $source == 'internal')) {
        $rel_query = '
             SELECT master_field,
                    foreign_db,
                    foreign_table,
                    foreign_field
               FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation']) . '
              WHERE master_db =  \'' . PMA_sqlAddslashes($db) . '\'
                AND master_table = \'' . PMA_sqlAddslashes($table) . '\' ';
        if (isset($column) && strlen($column)) {
            $rel_query .= ' AND   master_field = \'' . PMA_sqlAddslashes($column) . '\'';
        }
        $relations     = PMA_query_as_cu($rel_query);
        $i = 0;
        while ($relrow = PMA_DBI_fetch_assoc($relations)) {
            $field                            = $relrow['master_field'];
            $foreign[$field]['foreign_db']    = $relrow['foreign_db'];
            $foreign[$field]['foreign_table'] = $relrow['foreign_table'];
            $foreign[$field]['foreign_field'] = $relrow['foreign_field'];
            $i++;
        } // end while
        PMA_DBI_free_result($relations);
        unset($relations);
    }

    if (($source == 'both' || $source == 'innodb') && isset($table) && strlen($table)) {
        $show_create_table_query = 'SHOW CREATE TABLE '
            . PMA_backquote($db) . '.' . PMA_backquote($table);
        $show_create_table_res = PMA_DBI_query($show_create_table_query);
        list(, $show_create_table) = PMA_DBI_fetch_row($show_create_table_res);
        PMA_DBI_free_result($show_create_table_res);
        unset($show_create_table_res, $show_create_table_query);
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

        foreach ($analyzed_sql[0]['foreign_keys'] AS $one_key) {

        // the analyzer may return more than one column name in the
        // index list or the ref_index_list
            foreach ($one_key['index_list'] AS $i => $field) {

        // If a foreign key is defined in the 'internal' source (pmadb)
        // and in 'innodb', we won't get it twice if $source='both'
        // because we use $field as key

                // The parser looks for a CONSTRAINT clause just before
                // the FOREIGN KEY clause. It finds it (as output from
                // SHOW CREATE TABLE) in MySQL 4.0.13, but not in older
                // versions like 3.23.58.
                // In those cases, the FOREIGN KEY parsing will put numbers
                // like -1, 0, 1... instead of the constraint number.

                if (isset($one_key['constraint'])) {
                    $foreign[$field]['constraint'] = $one_key['constraint'];
                }

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

    /**
     * Emulating relations for some information_schema tables
     */
    if (PMA_MYSQL_INT_VERSION >= 50002 && $db == 'information_schema'
        && ($source == 'internal' || $source == 'both')) {

        require_once './libraries/information_schema_relations.lib.php';

        if (!isset($foreign)) {
            $foreign = array();
        }

        if (isset($GLOBALS['information_schema_relations'][$table])) {
            foreach ($GLOBALS['information_schema_relations'][$table] as $field => $relations) {
                if ((! isset($column) || ! strlen($column) || $column == $field)
                  && (! isset($foreign[$field]) || ! strlen($foreign[$field]))) {
                    $foreign[$field] = $relations;
                }
            }
        }
    }

    if (!empty($foreign) && is_array($foreign)) {
       return $foreign;
    } else {
        return false;
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
function PMA_getDisplayField($db, $table)
{
    $cfgRelation = PMA_getRelationsParam();

    /**
     * Try to fetch the display field from DB.
     */
    if ($cfgRelation['displaywork'] && trim($cfgRelation['table_info']) != '') {

        $disp_query = '
             SELECT display_field
               FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info']) . '
              WHERE db_name    = \'' . PMA_sqlAddslashes($db) . '\'
                AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';

        $disp_res   = PMA_query_as_cu($disp_query);
        $row        = ($disp_res ? PMA_DBI_fetch_assoc($disp_res) : '');
        PMA_DBI_free_result($disp_res);
        if (isset($row['display_field'])) {
            return $row['display_field'];
        }

    }

    /**
     * Emulating the display field for some information_schema tables.
     */
    if (PMA_MYSQL_INT_VERSION >= 50002 && $db == 'information_schema') {
        switch ($table) {
            case 'CHARACTER_SETS': return 'DESCRIPTION';
            case 'TABLES':         return 'TABLE_COMMENT';
        }
    }

    /**
     * No Luck...
     */
    return false;

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
 * @authors  Mike Beck <mikebeck@users.sourceforge.net>
 *           and lem9
 */
function PMA_getComments($db, $table = '')
{
    $cfgRelation = PMA_getRelationsParam();

    if ($table != '') {

        // MySQL 4.1.x native column comments
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            $fields = PMA_DBI_get_fields($db, $table);
            if ($fields) {
                foreach ($fields as $key=>$field) {
                    $tmp_col = $field['Field'];
                    if (!empty($field['Comment'])) {
                        $native_comment[$tmp_col] = $field['Comment'];
                    }
                }
                if (isset($native_comment)) {
                    $comment = $native_comment;
                }
            }
        }

        // pmadb internal column comments
        // (this function can be called even if $cfgRelation['commwork'] is
        // false, to get native column comments, so recheck here)
        if ($cfgRelation['commwork']) {
            $com_qry = '
                 SELECT column_name,
                        comment
                   FROM ' . PMA_backquote($cfgRelation['db']) . '.' .PMA_backquote($cfgRelation['column_info']) . '
                  WHERE db_name    = \'' . PMA_sqlAddslashes($db) . '\'
                    AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
            $com_rs   = PMA_query_as_cu($com_qry, true, PMA_DBI_QUERY_STORE);
        }
    } elseif ($cfgRelation['commwork']) {
        // pmadb internal db comments
        $com_qry = '
             SELECT `comment`
               FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
              WHERE db_name     = \'' . PMA_sqlAddslashes($db) . '\'
                AND table_name  = \'\'
                AND column_name = \'(db_comment)\'';
        $com_rs   = PMA_query_as_cu($com_qry, true, PMA_DBI_QUERY_STORE);
    }


    if (isset($com_rs) && PMA_DBI_num_rows($com_rs) > 0) {
        $i = 0;
        while ($row = PMA_DBI_fetch_assoc($com_rs)) {
            $i++;
            $col           = ($table != '' ? $row['column_name'] : $i);

            if (strlen($row['comment']) > 0) {
                $comment[$col] = $row['comment'];
                // if this version supports native comments and this function
                // was called with a table parameter
                if (PMA_MYSQL_INT_VERSION >= 40100 && isset($table) && strlen($table)) {
                    // if native comment found, use it instead of pmadb
                    if (!empty($native_comment[$col])) {
                        $comment[$col] = $native_comment[$col];
                    } else {
                        // no native comment, so migrate pmadb-style to native
                        PMA_setComment($db, $table, $col, $comment[$col], '', 'native');
                        // and erase the pmadb-style comment
                        PMA_setComment($db, $table, $col, '', '', 'pmadb');
                    }
                }
            }
        } // end while

        PMA_DBI_free_result($com_rs);
        unset($com_rs);
    }

    if (isset($comment) && is_array($comment)) {
        return $comment;
     } else {
        return false;
     }
 } // end of the 'PMA_getComments()' function

/**
 * Set a single comment to a certain value.
 *
 * @param   string   the name of the db
 * @param   string   the name of the table (may be empty in case of a db comment)
 * @param   string   the name of the column
 * @param   string   the value of the column
 * @param   string   (optional) if a column is renamed, this is the name of the former key which will get deleted
 * @param   string   whether we set pmadb comments, native comments or both
 *
 * @return  boolean  true, if comment-query was made.
 *
 * @global  array    the list of relations settings
 *
 * @access  public
 */
function PMA_setComment($db, $table, $col, $comment, $removekey = '', $mode = 'auto')
{
    $cfgRelation = PMA_getRelationsParam();

    if ($mode == 'auto') {
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            $mode = 'native';
        } else {
            $mode = 'pmadb';
        }
    }

    // native mode is only for column comments so we need a table name
    if ($mode == 'native' && strlen($table)) {
        $query = 'ALTER TABLE ' . PMA_backquote($table) . ' CHANGE '
            . PMA_Table::generateAlter($col, $col, '', '', '', '', false, '', false, '', $comment, '', '');
        PMA_DBI_try_query($query, null, PMA_DBI_QUERY_STORE);
        return true;
    }

    if (! $cfgRelation['commwork']) {
        return false;
    }

    // $mode == 'pmadb' section:

    $cols = array(
        'db_name'     => 'db_name    ',
        'table_name'  => 'table_name ',
        'column_name' => 'column_name'
    );

    if ($removekey != '' AND $removekey != $col) {
        $remove_query = '
             DELETE FROM
                    ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
              WHERE ' . $cols['db_name']     . ' = \'' . PMA_sqlAddslashes($db) . '\'
                AND ' . $cols['table_name']  . ' = \'' . PMA_sqlAddslashes($table) . '\'
                AND ' . $cols['column_name'] . ' = \'' . PMA_sqlAddslashes($removekey) . '\'';
        PMA_query_as_cu($remove_query);
        unset($remove_query);
    }

    $test_qry = '
         SELECT `comment`,
                mimetype,
                transformation,
                transformation_options
           FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
          WHERE ' . $cols['db_name']     . ' = \'' . PMA_sqlAddslashes($db) . '\'
            AND ' . $cols['table_name']  . ' = \'' . PMA_sqlAddslashes($table) . '\'
            AND ' . $cols['column_name'] . ' = \'' . PMA_sqlAddslashes($col) . '\'';
    $test_rs   = PMA_query_as_cu($test_qry, true, PMA_DBI_QUERY_STORE);

    if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
        $row = PMA_DBI_fetch_assoc($test_rs);
        PMA_DBI_free_result($test_rs);

        if (strlen($comment) > 0 || strlen($row['mimetype']) > 0 || strlen($row['transformation']) > 0 || strlen($row['transformation_options']) > 0) {
            $upd_query = '
                 UPDATE ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                    SET `comment` = \'' . PMA_sqlAddslashes($comment) . '\'
                  WHERE ' . $cols['db_name']     . ' = \'' . PMA_sqlAddslashes($db) . '\'
                    AND ' . $cols['table_name']  . ' = \'' . PMA_sqlAddslashes($table) . '\'
                    AND ' . $cols['column_name'] . ' = \'' . PMA_sqlAddSlashes($col) . '\'';
        } else {
            $upd_query = '
                 DELETE FROM
                        ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                  WHERE ' . $cols['db_name']     . ' = \'' . PMA_sqlAddslashes($db) . '\'
                    AND ' . $cols['table_name']  . ' = \'' . PMA_sqlAddslashes($table) . '\'
                    AND ' . $cols['column_name'] . ' = \'' . PMA_sqlAddslashes($col) . '\'';
        }
    } elseif (strlen($comment) > 0) {
        $upd_query = '
             INSERT INTO
                    ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                    (db_name, table_name, column_name, `comment`)
             VALUES (
                   \'' . PMA_sqlAddslashes($db) . '\',
                   \'' . PMA_sqlAddslashes($table) . '\',
                   \'' . PMA_sqlAddslashes($col) . '\',
                   \'' . PMA_sqlAddslashes($comment) . '\')';
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
 * @uses    $_SESSION['sql_history']
 * @uses    $cfg['QueryHistoryMax']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_query_as_cu()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    count()
 * @uses    md5()
 * @uses    array_shift()
 * @param   string   $db        the name of the db
 * @param   string   $table     the name of the table
 * @param   string   $username  the username
 * @param   string   $sqlquery  the sql query
 * @access  public
 */
function PMA_setHistory($db, $table, $username, $sqlquery)
{
    $cfgRelation = PMA_getRelationsParam();

    if (! isset($_SESSION['sql_history'])) {
        $_SESSION['sql_history'] = array();
    }

    $key = md5($sqlquery . $db . $table);

    if (isset($_SESSION['sql_history'][$key])) {
        unset($_SESSION['sql_history'][$key]);
    }

    $_SESSION['sql_history'][$key] = array(
        'db' => '',
        'table' => '',
        'sqlquery' => '',
    );

    if (count($_SESSION['sql_history']) > $GLOBALS['cfg']['QueryHistoryMax']) {
        // history should not exceed a maximum count
        array_shift($_SESSION['sql_history']);
    }

    PMA_query_as_cu('
         INSERT INTO
                ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['history']) . '
              (`username`,
                `db`,
                `table`,
                `timevalue`,
                `sqlquery`)
         VALUES
              (\'' . PMA_sqlAddslashes($username) . '\',
               \'' . PMA_sqlAddslashes($db) . '\',
               \'' . PMA_sqlAddslashes($table) . '\',
               NOW(),
               \'' . PMA_sqlAddslashes($sqlquery) . '\')');
} // end of 'PMA_setHistory()' function

/**
 * Gets a SQL history entry
 *
 * @uses    $_SESSION['sql_history']
 * @uses    $GLOBALS['controllink']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_result()
 * @uses    array_reverse()
 * @param   string   $username  the username
 * @return  array    list of history items
 * @access  public
 */
function PMA_getHistory($username)
{
    $cfgRelation = PMA_getRelationsParam();

    if (isset($_SESSION['sql_history'])) {
        return array_reverse($_SESSION['sql_history']);
    }

    $hist_query = '
         SELECT `db`,
                `table`,
                `sqlquery`
           FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['history']) . '
          WHERE `username` = \'' . PMA_sqlAddslashes($username) . '\'
       ORDER BY `id` DESC';

    return PMA_DBI_fetch_result($hist_query, null, null, $GLOBALS['controllink']);
} // end of 'PMA_getHistory()' function

/**
 * purges SQL history
 *
 * deletes entries that exceeds $cfg['QueryHistoryMax'], oldest first, for the
 * given user
 *
 * @uses    $cfg['QueryHistoryMax']
 * @uses    $cfg['QueryHistoryDB']
 * @uses    $GLOBALS['controllink']
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddSlashes()
 * @uses    PMA_query_as_cu()
 * @uses    PMA_DBI_fetch_value()
 * @param   string   $username  the username
 * @access  public
 */
function PMA_purgeHistory($username)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $GLOBALS['cfg']['QueryHistoryDB'] || ! $cfgRelation['historywork']) {
        return;
    }

    $search_query = '
         SELECT `timevalue`
           FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['history']) . '
          WHERE `username` = \'' . PMA_sqlAddSlashes($username) . '\'
       ORDER BY `timevalue` DESC
          LIMIT ' . $GLOBALS['cfg']['QueryHistoryMax'] . ', 1';

    if ($max_time = PMA_DBI_fetch_value($search_query, 0, 0, $GLOBALS['controllink'])) {
        PMA_query_as_cu('
             DELETE FROM
                    ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['history']) . '
              WHERE `username` = \'' . PMA_sqlAddSlashes($username) . '\'
                AND `timevalue` <= \'' . $max_time . '\'');
    }
} // end of 'PMA_purgeHistory()' function

/**
 * Prepares the dropdown for one mode
 *
 * @param   array    the keys and values for foreigns
 * @param   string   the current data of the dropdown
 * @param   string   the needed mode
 *
 * @global  array    global phpMyAdmin configuration
 *
 * @return  array   the <option value=""><option>s
 *
 * @access  private
 */
function PMA_foreignDropdownBuild($foreign, $data, $mode)
{
    global $cfg;

    $reloptions = array();

    foreach ($foreign as $key => $value) {

        if (PMA_strlen($value) <= $cfg['LimitChars']) {
            $vtitle = '';
            $value  = htmlspecialchars($value);
        } else {
            $vtitle  = htmlspecialchars($value);
            $value  = htmlspecialchars(substr($value, 0, $cfg['LimitChars']) . '...');
        }

        $reloption = '                <option value="' . htmlspecialchars($key) . '"';
        if ($vtitle != '') {
            $reloption .= ' title="' . $vtitle . '"';
        }

        if ((string) $key == (string) $data) {
           $reloption .= ' selected="selected"';
        }

        if ($mode == 'content-id') {
            $reloptions[] = $reloption . '>' . $value . '&nbsp;-&nbsp;' . htmlspecialchars($key) .  '</option>' . "\n";
        } else {
            $reloptions[] = $reloption . '>' . htmlspecialchars($key) .  '&nbsp;-&nbsp;' . $value . '</option>' . "\n";
        }
    } // end foreach

    return $reloptions;
} // end of 'PMA_foreignDropdownBuild' function

/**
 * Outputs dropdown with values of foreign fields
 *
 * @param   string   the query of the foreign keys
 * @param   string   the foreign field
 * @param   string   the foreign field to display
 * @param   string   the current data of the dropdown
 *
 * @global  array    global phpMyAdmin configuration
 *
 * @return  string   the <option value=""><option>s
 *
 * @access  public
 */
function PMA_foreignDropdown($disp, $foreign_field, $foreign_display, $data, $max)
{
    global $cfg;

    $foreign = array();

    // collect the data
    foreach ($disp as $relrow) {
        $key   = $relrow[$foreign_field];

        // if the display field has been defined for this foreign table
        if ($foreign_display) {
            $value  = $relrow[$foreign_display];
        } else {
            $value = '';
        } // end if ($foreign_display)

        $foreign[$key] = $value;
    } // end foreach

    // beginning of dropdown
    $ret = '<option value=""></option>' . "\n";

    // master array for dropdowns
    $reloptions = array('content-id' => array(), 'id-content' => array());

    // sort for id-content
    if ($cfg['NaturalOrder']) {
        uksort($foreign, 'strnatcasecmp');
    } else {
        ksort($foreign);
    }

    // build id-content dropdown
    $reloptions['id-content'] = PMA_foreignDropdownBuild($foreign, $data, 'id-content');

    // sort for content-id
    if ($cfg['NaturalOrder']) {
        natcasesort($foreign);
    } else {
        asort($foreign);
    }

    // build content-id dropdown
    $reloptions['content-id'] = PMA_foreignDropdownBuild($foreign, $data, 'content-id');


    // put the dropdown sections in correct order

    $c = count($cfg['ForeignKeyDropdownOrder']);
    if ($c == 2) {
        $top = $reloptions[$cfg['ForeignKeyDropdownOrder'][0]];
        $bot = $reloptions[$cfg['ForeignKeyDropdownOrder'][1]];
    } elseif ($c == 1) {
        $bot = $reloptions[$cfg['ForeignKeyDropdownOrder'][0]];
        $top = null;
    } else {
        $top = $reloptions['id-content'];
        $bot = $reloptions['content-id'];
    }
    $str_bot = implode('', $bot);
    if ($top !== null) {
        $str_top = implode('', $top);
        $top_count = count($top);
        if ($max == -1 || $top_count < $max) {
            $ret .= $str_top;
            if ($top_count > 0) {
                $ret .= '                <option value=""></option>' . "\n";
                $ret .= '                <option value=""></option>' . "\n";
            }
        }
    }
    $ret .= $str_bot;

    return $ret;
} // end of 'PMA_foreignDropdown()' function

?>
