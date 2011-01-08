<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the relation and pdf feature
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
 * @return  integer   the result set, or false if no result set
 *
 * @access  public
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 */
function PMA_query_as_controluser($sql, $show_error = true, $options = 0)
{
    // Avoid caching of the number of rows affected; for example, this function 
    // is called for tracking purposes but we want to display the correct number
    // of rows affected by the original query, not by the query generated for
    // tracking.
    $cache_affected_rows = false;

    if ($show_error) {
        $result = PMA_DBI_query($sql, $GLOBALS['controllink'], $options, $cache_affected_rows);
    } else {
        $result = @PMA_DBI_try_query($sql, $GLOBALS['controllink'], $options, $cache_affected_rows);
    } // end if... else...

    if ($result) {
        return $result;
    } else {
        return false;
    }
} // end of the "PMA_query_as_controluser()" function

/**
 * @uses    $_SESSION['relation'][$GLOBALS['server']] for caching
 * @uses    $GLOBALS['cfgRelation'] to set it
 * @uses    $GLOBALS['server'] to ensure we are using server-specific pmadb
 * @uses    PMA__getRelationsParam()
 * @uses    PMA_printRelationsParamDiagnostic()
 * @param   bool    $verbose    whether to print diagnostic info
 * @return  array   $cfgRelation
 */
function PMA_getRelationsParam($verbose = false)
{
    if (empty($_SESSION['relation'][$GLOBALS['server']])) {
        $_SESSION['relation'][$GLOBALS['server']] = PMA__getRelationsParam();
    }

    // just for BC but needs to be before PMA_printRelationsParamDiagnostic()
    // which uses it
    $GLOBALS['cfgRelation'] = $_SESSION['relation'][$GLOBALS['server']];

    if ($verbose) {
        PMA_printRelationsParamDiagnostic($_SESSION['relation'][$GLOBALS['server']]);
    }

    return $_SESSION['relation'][$GLOBALS['server']];
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
 * @uses    sprintf()
 * @uses    PMA_printDiagMessageForFeature()
 * @uses    PMA_printDiagMessageForParameter()
 * @param   array   $cfgRelation
 */
function PMA_printRelationsParamDiagnostic($cfgRelation)
{
    $messages['error'] = '<font color="red"><strong>' . $GLOBALS['strNotOK']
                   . '</strong></font> [ <a href="Documentation.html#%s" target="documentation">'
                   . $GLOBALS['strDocu'] . '</a> ]';

    $messages['ok'] = '<font color="green"><strong>' . $GLOBALS['strOK'] . '</strong></font>';
    $messages['enabled']  = '<font color="green">' . $GLOBALS['strEnabled'] . '</font>';
    $messages['disabled'] = '<font color="red">'   . $GLOBALS['strDisabled'] . '</font>';

    if (false === $GLOBALS['cfg']['Server']['pmadb']) {
        echo 'PMA Database ... '
             . sprintf($messages['error'], 'pmadb')
             . '<br />' . "\n"
             . $GLOBALS['strGeneralRelationFeat']
             . ' <font color="green">' . $GLOBALS['strDisabled']
             . '</font>' . "\n";
        return;
    }

    echo '<table>' . "\n";

    PMA_printDiagMessageForParameter('pmadb', $GLOBALS['cfg']['Server']['pmadb'], $messages, 'pmadb');

    PMA_printDiagMessageForParameter('relation', isset($cfgRelation['relation']), $messages, 'relation');

    PMA_printDiagMessageForFeature('strGeneralRelationFeat', 'relwork', $messages);

    PMA_printDiagMessageForParameter('table_info', isset($cfgRelation['table_info']), $messages, 'table_info');

    PMA_printDiagMessageForFeature('strDisplayFeat', 'displaywork', $messages);

    PMA_printDiagMessageForParameter('table_coords', isset($cfgRelation['table_coords']), $messages, 'table_coords');

    PMA_printDiagMessageForParameter('pdf_pages', isset($cfgRelation['pdf_pages']), $messages, 'table_coords');

    PMA_printDiagMessageForFeature('strCreatePdfFeat', 'pdfwork', $messages);

    PMA_printDiagMessageForParameter('column_info', isset($cfgRelation['column_info']), $messages, 'col_com');

    PMA_printDiagMessageForFeature('strColComFeat', 'commwork', $messages, false);

    PMA_printDiagMessageForFeature('strMIME_transformation', 'mimework', $messages);

    if ($cfgRelation['commwork'] && ! $cfgRelation['mimework']) {
        echo '<tr><td colspan=2 align="left">' . $GLOBALS['strUpdComTab'] . '</td></tr>' . "\n";
    }

    PMA_printDiagMessageForParameter('bookmarktable', isset($cfgRelation['bookmark']), $messages, 'bookmark');

    PMA_printDiagMessageForFeature('strBookmarkQuery', 'bookmarkwork', $messages);

    PMA_printDiagMessageForParameter('history', isset($cfgRelation['history']), $messages, 'history');

    PMA_printDiagMessageForFeature('strQuerySQLHistory', 'historywork', $messages);

    PMA_printDiagMessageForParameter('designer_coords', isset($cfgRelation['designer_coords']), $messages, 'designer_coords');

    PMA_printDiagMessageForFeature('strDesigner', 'designerwork', $messages);

    PMA_printDiagMessageForParameter('tracking', isset($cfgRelation['tracking']), $messages, 'tracking');

    PMA_printDiagMessageForFeature('strTracking', 'trackingwork', $messages);

    echo '</table>' . "\n";
}

/**
 * prints out one diagnostic message for a feature
 *
 * @param   string  feature name in a message string
 * @param   string  the $GLOBALS['cfgRelation'] parameter to check
 * @param   array   utility messages
 * @param   boolean whether to skip a line after the message
 */
function PMA_printDiagMessageForFeature($feature_name, $relation_parameter, $messages, $skip_line=true)
{
    echo '    <tr><td colspan=2 align="right">' . $GLOBALS[$feature_name] . ': '
         . ($GLOBALS['cfgRelation'][$relation_parameter] ? $messages['enabled'] : $messages['disabled'])
         . '</td></tr>' . "\n";
    if ($skip_line) {
        echo '    <tr><td>&nbsp;</td></tr>' . "\n";
    }
}

/**
 * prints out one diagnostic message for a configuration parameter
 *
 * @param   string  config parameter name to display
 * @param   boolean whether this parameter is set
 * @param   array   utility messages
 * @param   string  anchor in Documentation.html
 */
function PMA_printDiagMessageForParameter($parameter, $relation_parameter_set, $messages, $doc_anchor)
{
    echo '    <tr><th align="left">';
    echo '$cfg[\'Servers\'][$i][\'' . $parameter . '\']  ... </th><td align="right">';
    echo ($relation_parameter_set ? $messages['ok'] : sprintf($messages['error'], $doc_anchor)) . '</td></tr>' . "\n";
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
 * @uses    PMA_query_as_controluser()
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
    $cfgRelation['trackingwork'] = false;
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
    $tab_rs    = PMA_query_as_controluser($tab_query, false, PMA_DBI_QUERY_STORE);

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
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['tracking']) {
            $cfgRelation['tracking'] = $curr_table[0];
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
            $mime_rs     = PMA_query_as_controluser($mime_query, false);

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

    if (isset($cfgRelation['tracking'])) {
        $cfgRelation['trackingwork']     = true;
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
     && $cfgRelation['trackingwork']
     && $cfgRelation['bookmarkwork'] && $cfgRelation['designerwork']) {
        $cfgRelation['allworks'] = true;
    }

    return $cfgRelation;
} // end of the 'PMA_getRelationsParam()' function

/**
 * Gets all Relations to foreign tables for a given table or
 * optionally a given column in a table
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @author  Marc Delisle
 * @access  public
 * @uses    $GLOBALS['controllink']
 * @uses    $GLOBALS['information_schema_relations']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_DBI_fetch_value()
 * @uses    PMA_SQP_analyze()
 * @uses    PMA_SQP_parse()
 * @uses    count()
 * @uses    strlen()
 * @param   string   $db        the name of the db to check for
 * @param   string   $table     the name of the table to check for
 * @param   string   $column    the name of the column to check for
 * @param   string   $source    the source for foreign key information
 * @return  array    db,table,column
 */
function PMA_getForeigners($db, $table, $column = '', $source = 'both')
{
    $cfgRelation = PMA_getRelationsParam();
    $foreign = array();

    if ($cfgRelation['relwork'] && ($source == 'both' || $source == 'internal')) {
        $rel_query = '
             SELECT `master_field`,
                    `foreign_db`,
                    `foreign_table`,
                    `foreign_field`
               FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation']) . '
              WHERE `master_db`    = \'' . PMA_sqlAddslashes($db) . '\'
                AND `master_table` = \'' . PMA_sqlAddslashes($table) . '\' ';
        if (strlen($column)) {
            $rel_query .= ' AND `master_field` = \'' . PMA_sqlAddslashes($column) . '\'';
        }
        $foreign = PMA_DBI_fetch_result($rel_query, 'master_field', null, $GLOBALS['controllink']);
    }

    if (($source == 'both' || $source == 'foreign') && strlen($table)) {
        $show_create_table_query = 'SHOW CREATE TABLE '
            . PMA_backquote($db) . '.' . PMA_backquote($table);
        $show_create_table = PMA_DBI_fetch_value($show_create_table_query, 0, 1);
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

        foreach ($analyzed_sql[0]['foreign_keys'] as $one_key) {
            // The analyzer may return more than one column name in the
            // index list or the ref_index_list; if this happens,
            // the current logic just discards the whole index; having
            // more than one index field is currently unsupported (see FAQ 3.6)
            if (count($one_key['index_list']) == 1) {
                foreach ($one_key['index_list'] as $i => $field) {
                    // If a foreign key is defined in the 'internal' source (pmadb)
                    // and as a native foreign key, we won't get it twice
                    // if $source='both' because we use $field as key

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
                        $foreign[$field]['foreign_db'] = $one_key['ref_db_name'];
                    } else {
                        $foreign[$field]['foreign_db'] = $db;
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
    }

    /**
     * Emulating relations for some information_schema tables
     */
    if ($db == 'information_schema'
     && ($source == 'internal' || $source == 'both')) {
        require_once './libraries/information_schema_relations.lib.php';

        if (isset($GLOBALS['information_schema_relations'][$table])) {
            foreach ($GLOBALS['information_schema_relations'][$table] as $field => $relations) {
                if ((! strlen($column) || $column == $field)
                 && (! isset($foreign[$field]) || ! strlen($foreign[$field]))) {
                    $foreign[$field] = $relations;
                }
            }
        }
    }

    return $foreign;
} // end of the 'PMA_getForeigners()' function

/**
 * Gets the display field of a table
 *
 * @access  public
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @uses    $GLOBALS['controllink']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_single_row()
 * @uses    trim()
 * @param   string   $db    the name of the db to check for
 * @param   string   $table the name of the table to check for
 * @return  string   field name
 */
function PMA_getDisplayField($db, $table)
{
    $cfgRelation = PMA_getRelationsParam();

    /**
     * Try to fetch the display field from DB.
     */
    if ($cfgRelation['displaywork']) {
        $disp_query = '
             SELECT `display_field`
               FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info']) . '
              WHERE `db_name`    = \'' . PMA_sqlAddslashes($db) . '\'
                AND `table_name` = \'' . PMA_sqlAddslashes($table) . '\'';

        $row = PMA_DBI_fetch_single_row($disp_query, 'ASSOC', $GLOBALS['controllink']);
        if (isset($row['display_field'])) {
            return $row['display_field'];
        }
    }

    /**
     * Emulating the display field for some information_schema tables.
     */
    if ($db == 'information_schema') {
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
 * Gets the comments for all rows of a table or the db itself
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @author  lem9
 * @access  public
 * @uses    PMA_DBI_get_fields()
 * @uses    PMA_getDbComment()
 * @param   string   the name of the db to check for
 * @param   string   the name of the table to check for
 * @return  array    [field_name] = comment
 */
function PMA_getComments($db, $table = '')
{
    $comments = array();

    if ($table != '') {
        // MySQL native column comments
        $fields = PMA_DBI_get_fields($db, $table);
        if ($fields) {
            foreach ($fields as $key => $field) {
                if (! empty($field['Comment'])) {
                    $comments[$field['Field']] = $field['Comment'];
                }
            }
        }
    } else {
        $comments[] = PMA_getDbComment($db);
    }

    return $comments;
} // end of the 'PMA_getComments()' function

/**
 * Gets the comment for a db
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @author  lem9
 * @access  public
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_free_result()
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_query_as_controluser()
 * @uses    strlen()
 * @param   string   the name of the db to check for
 * @return  string   comment
 */
function PMA_getDbComment($db)
{
    $cfgRelation = PMA_getRelationsParam();
    $comment = '';

    if ($cfgRelation['commwork']) {
        // pmadb internal db comment
        $com_qry = "
             SELECT `comment`
               FROM " . PMA_backquote($cfgRelation['db']) . "." . PMA_backquote($cfgRelation['column_info']) . "
              WHERE db_name     = '" . PMA_sqlAddslashes($db) . "'
                AND table_name  = ''
                AND column_name = '(db_comment)'";
        $com_rs = PMA_query_as_controluser($com_qry, true, PMA_DBI_QUERY_STORE);

        if ($com_rs && PMA_DBI_num_rows($com_rs) > 0) {
            $row = PMA_DBI_fetch_assoc($com_rs);
            $comment = $row['comment'];
        }
        PMA_DBI_free_result($com_rs);
    }

    return $comment;
} // end of the 'PMA_getDbComment()' function

/**
 * Gets the comment for a db
 *
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @author  lem9
 * @access  public
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_free_result()
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_query_as_controluser()
 * @uses    strlen()
 * @param   string   the name of the db to check for
 * @return  string   comment
 */
function PMA_getDbComments()
{
    $cfgRelation = PMA_getRelationsParam();
    $comments = array();

    if ($cfgRelation['commwork']) {
        // pmadb internal db comment
        $com_qry = "
             SELECT `db_name`, `comment`
               FROM " . PMA_backquote($cfgRelation['db']) . "." . PMA_backquote($cfgRelation['column_info']) . "
              WHERE `column_name` = '(db_comment)'";
        $com_rs = PMA_query_as_controluser($com_qry, true, PMA_DBI_QUERY_STORE);

        if ($com_rs && PMA_DBI_num_rows($com_rs) > 0) {
            while ($row = PMA_DBI_fetch_assoc($com_rs)) {
                $comments[$row['db_name']] = $row['comment'];
            }
        }
        PMA_DBI_free_result($com_rs);
    }

    return $comments;
} // end of the 'PMA_getDbComments()' function

/**
 * Set a database comment to a certain value.
 *
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_query_as_controluser()
 * @uses    strlen()
 * @access  public
 * @param   string   $db        the name of the db
 * @param   string   $comment   the value of the column
 * @return  boolean  true, if comment-query was made.
 */
function PMA_setDbComment($db, $comment = '')
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['commwork']) {
        return false;
    }

    if (strlen($comment)) {
        $upd_query = "
             INSERT INTO
                    " . PMA_backquote($cfgRelation['db']) . "." . PMA_backquote($cfgRelation['column_info']) . "
                    (`db_name`, `table_name`, `column_name`, `comment`)
             VALUES (
                   '" . PMA_sqlAddslashes($db) . "',
                   '',
                   '(db_comment)',
                   '" . PMA_sqlAddslashes($comment) . "')
             ON DUPLICATE KEY UPDATE
                `comment` = '" . PMA_sqlAddslashes($comment) . "'";
    } else {
        $upd_query = '
             DELETE FROM
                    ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
              WHERE `db_name`     = \'' . PMA_sqlAddslashes($db) . '\'
                AND `table_name`  = \'\'
                AND `column_name` = \'(db_comment)\'';
    }

    if (isset($upd_query)){
        return PMA_query_as_controluser($upd_query);
    }

    return false;
} // end of 'PMA_setDbComment()' function

/**
 * Set a SQL history entry
 *
 * @uses    $_SESSION['sql_history']
 * @uses    $cfg['QueryHistoryDB']
 * @uses    $cfg['QueryHistoryMax']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_query_as_controluser()
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
    if (strlen($sqlquery) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
        return;
    }

    $cfgRelation = PMA_getRelationsParam();

    if (! isset($_SESSION['sql_history'])) {
        $_SESSION['sql_history'] = array();
    }

    $key = md5($sqlquery . $db . $table);

    if (isset($_SESSION['sql_history'][$key])) {
        unset($_SESSION['sql_history'][$key]);
    }

    $_SESSION['sql_history'][$key] = array(
        'db' => $db,
        'table' => $table,
        'sqlquery' => $sqlquery,
    );

    if (count($_SESSION['sql_history']) > $GLOBALS['cfg']['QueryHistoryMax']) {
        // history should not exceed a maximum count
        array_shift($_SESSION['sql_history']);
    }

    if (! $cfgRelation['historywork'] || ! $GLOBALS['cfg']['QueryHistoryDB']) {
        return;
    }

    PMA_query_as_controluser('
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

    if (! $cfgRelation['historywork']) {
        return false;
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
 * @uses    PMA_query_as_controluser()
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

    if (! $cfgRelation['historywork']) {
        return;
    }

    $search_query = '
         SELECT `timevalue`
           FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['history']) . '
          WHERE `username` = \'' . PMA_sqlAddSlashes($username) . '\'
       ORDER BY `timevalue` DESC
          LIMIT ' . $GLOBALS['cfg']['QueryHistoryMax'] . ', 1';

    if ($max_time = PMA_DBI_fetch_value($search_query, 0, 0, $GLOBALS['controllink'])) {
        PMA_query_as_controluser('
             DELETE FROM
                    ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['history']) . '
              WHERE `username` = \'' . PMA_sqlAddSlashes($username) . '\'
                AND `timevalue` <= \'' . $max_time . '\'');
    }
} // end of 'PMA_purgeHistory()' function

/**
 * Prepares the dropdown for one mode
 *
 * @uses    $cfg['LimitChars']
 * @uses    $cfg['NaturalOrder']
 * @uses    PMA_strlen()
 * @uses    htmlspecialchars()
 * @uses    substr()
 * @uses    uksort()
 * @uses    ksort()
 * @uses    natcasesort()
 * @uses    asort()
 * @param   array    $foreign   the keys and values for foreigns
 * @param   string   $data      the current data of the dropdown
 * @param   string   $mode      the needed mode
 *
 * @return  array   the <option value=""><option>s
 *
 * @access  protected
 */
function PMA__foreignDropdownBuild($foreign, $data, $mode)
{
    $reloptions = array();

    if ($mode == 'id-content') {
        // sort for id-content
        if ($GLOBALS['cfg']['NaturalOrder']) {
            uksort($foreign, 'strnatcasecmp');
        } else {
            ksort($foreign);
        }
    } elseif ($mode == 'content-id') {
        // sort for content-id
        if ($GLOBALS['cfg']['NaturalOrder']) {
            natcasesort($foreign);
        } else {
            asort($foreign);
        }
    }

    foreach ($foreign as $key => $value) {

        if (PMA_strlen($value) <= $GLOBALS['cfg']['LimitChars']) {
            $vtitle = '';
            $value  = htmlspecialchars($value);
        } else {
            $vtitle  = htmlspecialchars($value);
            $value  = htmlspecialchars(substr($value, 0, $GLOBALS['cfg']['LimitChars']) . '...');
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
} // end of 'PMA__foreignDropdownBuild' function

/**
 * Outputs dropdown with values of foreign fields
 *
 * @uses    $cfg['ForeignKeyMaxLimit']
 * @uses    $cfg['ForeignKeyDropdownOrder']
 * @uses    PMA__foreignDropdownBuild()
 * @uses    PMA_isValid()
 * @uses    implode()
 * @param   array    array of the displayed row
 * @param   string   the foreign field
 * @param   string   the foreign field to display
 * @param   string   the current data of the dropdown (field in row)
 * @return  string   the <option value=""><option>s
 * @access  public
 */
function PMA_foreignDropdown($disp_row, $foreign_field, $foreign_display, $data,
    $max = null)
{
    if (null === $max) {
        $max = $GLOBALS['cfg']['ForeignKeyMaxLimit'];
    }

    $foreign = array();

    // collect the data
    foreach ($disp_row as $relrow) {
        $key   = $relrow[$foreign_field];

        // if the display field has been defined for this foreign table
        if ($foreign_display) {
            $value  = $relrow[$foreign_display];
        } else {
            $value = '';
        } // end if ($foreign_display)

        $foreign[$key] = $value;
    } // end foreach

    // put the dropdown sections in correct order
    $top = array();
    $bot = array();
    if (PMA_isValid($GLOBALS['cfg']['ForeignKeyDropdownOrder'], 'array')) {
        if (PMA_isValid($GLOBALS['cfg']['ForeignKeyDropdownOrder'][0])) {
            $top = PMA__foreignDropdownBuild($foreign, $data,
                $GLOBALS['cfg']['ForeignKeyDropdownOrder'][0]);
        }
        if (PMA_isValid($GLOBALS['cfg']['ForeignKeyDropdownOrder'][1])) {
            $bot = PMA__foreignDropdownBuild($foreign, $data,
                $GLOBALS['cfg']['ForeignKeyDropdownOrder'][1]);
        }
    } else {
        $top = PMA__foreignDropdownBuild($foreign, $data, 'id-content');
        $bot = PMA__foreignDropdownBuild($foreign, $data, 'content-id');
    }

    // beginning of dropdown
    $ret = '<option value="">&nbsp;</option>' . "\n";

    $top_count = count($top);
    if ($max == -1 || $top_count < $max) {
        $ret .= implode('', $top);
        if ($top_count > 0) {
            $ret .= '                <option value="">&nbsp;</option>' . "\n";
            $ret .= '                <option value="">&nbsp;</option>' . "\n";
        }
    }
    $ret .= implode('', $bot);

    return $ret;
} // end of 'PMA_foreignDropdown()' function

/**
 * Gets foreign keys in preparation for a drop-down selector
 * Thanks to <markus@noga.de>
 *
 * @uses    PMA_Table::countRecords()
 * @uses    PMA_backquote()
 * @uses    PMA_getDisplayField()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_value()
 * @uses    PMA_DBI_free_result()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_assoc()
 * @param   array    array of the foreign keys
 * @param   string   the foreign field name
 * @param   bool     whether to override the total
 * @param   string   a possible filter
 * @param   string   a possible LIMIT clause
 * @return  array    data about the foreign keys
 * @access  public
 */

function PMA_getForeignData($foreigners, $field, $override_total, $foreign_filter, $foreign_limit)
{
    // we always show the foreign field in the drop-down; if a display
    // field is defined, we show it besides the foreign field
    $foreign_link = false;
    if ($foreigners && isset($foreigners[$field])) {
        $foreigner       = $foreigners[$field];
        $foreign_db      = $foreigner['foreign_db'];
        $foreign_table   = $foreigner['foreign_table'];
        $foreign_field   = $foreigner['foreign_field'];

        // Count number of rows in the foreign table. Currently we do
        // not use a drop-down if more than 200 rows in the foreign table,
        // for speed reasons and because we need a better interface for this.
        //
        // We could also do the SELECT anyway, with a LIMIT, and ensure that
        // the current value of the field is one of the choices.

        $the_total   = PMA_Table::countRecords($foreign_db, $foreign_table);

        if ($override_total == true || $the_total < $GLOBALS['cfg']['ForeignKeyMaxLimit']) {
            // foreign_display can be FALSE if no display field defined:
            $foreign_display = PMA_getDisplayField($foreign_db, $foreign_table);

            $f_query_main = 'SELECT ' . PMA_backquote($foreign_field)
                        . (($foreign_display == FALSE) ? '' : ', ' . PMA_backquote($foreign_display));
            $f_query_from = ' FROM ' . PMA_backquote($foreign_db) . '.' . PMA_backquote($foreign_table);
            $f_query_filter = empty($foreign_filter) ? '' : ' WHERE ' . PMA_backquote($foreign_field)
                            . ' LIKE "%' . PMA_sqlAddslashes($foreign_filter, TRUE) . '%"'
                            . (($foreign_display == FALSE) ? '' : ' OR ' . PMA_backquote($foreign_display)
                                . ' LIKE "%' . PMA_sqlAddslashes($foreign_filter, TRUE) . '%"'
                                );
            $f_query_order = ($foreign_display == FALSE) ? '' :' ORDER BY ' . PMA_backquote($foreign_table) . '.' . PMA_backquote($foreign_display);
            $f_query_limit = isset($foreign_limit) ? $foreign_limit : '';

            if (!empty($foreign_filter)) {
                $res = PMA_DBI_query('SELECT COUNT(*)' . $f_query_from . $f_query_filter);
                if ($res) {
                    $the_total = PMA_DBI_fetch_value($res);
                    @PMA_DBI_free_result($res);
                } else {
                    $the_total = 0;
                }
            }

            $disp  = PMA_DBI_query($f_query_main . $f_query_from . $f_query_filter . $f_query_order . $f_query_limit);
            if ($disp && PMA_DBI_num_rows($disp) > 0) {
                // If a resultset has been created, pre-cache it in the $disp_row array
                // This helps us from not needing to use mysql_data_seek by accessing a pre-cached
                // PHP array. Usually those resultsets are not that big, so a performance hit should
                // not be expected.
                $disp_row = array();
                while ($single_disp_row = @PMA_DBI_fetch_assoc($disp)) {
                    $disp_row[] = $single_disp_row;
                }
                @PMA_DBI_free_result($disp);
            }
        } else {
            $disp_row = null;
            $foreign_link = true;
        }
    }  // end if $foreigners

    $foreignData['foreign_link'] = $foreign_link;
    $foreignData['the_total'] = isset($the_total) ? $the_total : null;
    $foreignData['foreign_display'] = isset($foreign_display) ? $foreign_display : null;
    $foreignData['disp_row'] = isset($disp_row) ? $disp_row : null;
    $foreignData['foreign_field'] = isset($foreign_field) ? $foreign_field : null;
    return $foreignData;
} // end of 'PMA_getForeignData()' function

/**
 * Finds all related tables
 *
 * @uses    $GLOBALS['controllink']
 * @uses    $GLOBALS['cfgRelation']
 * @uses    $GLOBALS['db']
 * @param   string   whether to go from master to foreign or vice versa
 * @return  boolean  always TRUE
 * @global  array    $tab_left the list of tables that we still couldn't connect
 * @global  array    $tab_know the list of allready connected tables
 * @global  string   $fromclause
 *
 * @access  private
 */
function PMA_getRelatives($from)
{
    global $tab_left, $tab_know, $fromclause;

    if ($from == 'master') {
        $to    = 'foreign';
    } else {
        $to    = 'master';
    }
    $in_know = '(\'' . implode('\', \'', $tab_know) . '\')';
    $in_left = '(\'' . implode('\', \'', $tab_left) . '\')';

    $rel_query = 'SELECT *'
               . '  FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db'])
               .       '.' . PMA_backquote($GLOBALS['cfgRelation']['relation'])
               . ' WHERE ' . $from . '_db = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\''
               . '   AND ' . $to   . '_db = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\''
               . '   AND ' . $from . '_table IN ' . $in_know
               . '   AND ' . $to   . '_table IN ' . $in_left;
    $relations = @PMA_DBI_query($rel_query, $GLOBALS['controllink']);
    while ($row = PMA_DBI_fetch_assoc($relations)) {
        $found_table                = $row[$to . '_table'];
        if (isset($tab_left[$found_table])) {
            $fromclause
                .= "\n" . ' LEFT JOIN '
                . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($row[$to . '_table']) . ' ON '
                . PMA_backquote($row[$from . '_table']) . '.'
                . PMA_backquote($row[$from . '_field']) . ' = '
                . PMA_backquote($row[$to . '_table']) . '.'
                . PMA_backquote($row[$to . '_field']) . ' ';
            $tab_know[$found_table] = $found_table;
            unset($tab_left[$found_table]);
        }
    } // end while

    return true;
} // end of the "PMA_getRelatives()" function

/**
 * Rename a field in relation tables
 *
 * usually called after a field in a table was renamed in tbl_alter.php
 *
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_query_as_controluser()
 * @param string $db
 * @param string $table
 * @param string $field
 * @param string $new_name
 */
function PMA_REL_renameField($db, $table, $field, $new_name)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['displaywork']) {
        $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                      . '   SET display_field = \'' . PMA_sqlAddslashes($new_name) . '\''
                      . ' WHERE db_name       = \'' . PMA_sqlAddslashes($db) . '\''
                      . '   AND table_name    = \'' . PMA_sqlAddslashes($table) . '\''
                      . '   AND display_field = \'' . PMA_sqlAddslashes($field) . '\'';
        PMA_query_as_controluser($table_query);
    }

    if ($cfgRelation['relwork']) {
        $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                      . '   SET master_field = \'' . PMA_sqlAddslashes($new_name) . '\''
                      . ' WHERE master_db    = \'' . PMA_sqlAddslashes($db) . '\''
                      . '   AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                      . '   AND master_field = \'' . PMA_sqlAddslashes($field) . '\'';
        PMA_query_as_controluser($table_query);

        $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                      . '   SET foreign_field = \'' . PMA_sqlAddslashes($new_name) . '\''
                      . ' WHERE foreign_db    = \'' . PMA_sqlAddslashes($db) . '\''
                      . '   AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\''
                      . '   AND foreign_field = \'' . PMA_sqlAddslashes($field) . '\'';
        PMA_query_as_controluser($table_query);
    } // end if relwork
}

/**
 * Create a PDF page
 *
 * @uses    $GLOBALS['strNoDescription']
 * @uses    PMA_backquote()
 * @uses    $GLOBALS['cfgRelation']['db']
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_query_as_controluser()
 * @uses    PMA_DBI_insert_id()
 * @uses    $GLOBALS['controllink']
 * @param string    $newpage
 * @param array     $cfgRelation
 * @param string    $db
 * @param string    $query_default_option
 * @return string   $pdf_page_number
 */
function PMA_REL_create_page($newpage, $cfgRelation, $db, $query_default_option) {
    if (! isset($newpage) || $newpage == '') {
        $newpage = $GLOBALS['strNoDescription'];
    }
    $ins_query   = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                 . ' (db_name, page_descr)'
                 . ' VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($newpage) . '\')';
    PMA_query_as_controluser($ins_query, FALSE, $query_default_option);
    return PMA_DBI_insert_id(isset($GLOBALS['controllink']) ? $GLOBALS['controllink'] : '');
}
?>
