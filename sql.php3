<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');

/**
 * Defines the url to return to in case of error in a sql statement
 */
// Security checkings
if (!empty($goto)) {
    $is_gotofile     = ereg_replace('^([^?]+).*$', '\\1', $goto);
    if (!@file_exists('./' . $is_gotofile)) {
        unset($goto);
    } else {
        $is_gotofile = ($is_gotofile == $goto);
    }
} // end if (security checkings)

if (empty($goto)) {
    $goto         = (empty($table)) ? $cfg['DefaultTabDatabase'] : $cfg['DefaultTabTable'];
    $is_gotofile  = TRUE;
} // end if
if (!isset($err_url)) {
    $err_url = (!empty($back) ? $back : $goto)
             . '?' . PMA_generate_common_url(isset($db) ? $db : '')
             . ((strpos(' ' . $goto, 'db_details') != 1 && isset($table)) ? '&amp;table=' . urlencode($table) : '');
} // end if

// Coming from a bookmark dialog
if (isset($fields['query'])) {
    $sql_query = $fields['query'];
}

/**
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if (!defined('PMA_CHK_DROP')
    && !$cfg['AllowUserDropDatabase']
    && eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?DATABASE[[:space:]]', $sql_query)) {
    // Checks if the user is a Superuser
    // TODO: set a global variable with this information
    // loic1: optimized query
    $result = @PMA_mysql_query('USE mysql');
    if (PMA_mysql_error()) {
        include('./header.inc.php3');
        PMA_mysqlDie($strNoDropDatabases, '', '', $err_url);
    } // end if
} // end if


/**
 * Bookmark add
 */
if (isset($store_bkm)) {
    include('./libraries/bookmark.lib.php3');
    PMA_addBookmarks($fields, $cfg['Bookmark']);
    header('Location: ' . $cfg['PmaAbsoluteUri'] . $goto);
} // end if


/**
 * Gets the true sql query
 */
// $sql_query has been urlencoded in the confirmation form for drop/delete
// queries or in the navigation bar for browsing among records
if (isset($btnDrop) || isset($navig)) {
    $sql_query = urldecode($sql_query);
}

/**
 * Reformat the query
 */

$parsed_sql = PMA_SQP_parse($sql_query);
$analyzed_sql = PMA_SQP_analyze($parsed_sql);
// Bug #641765 - Robbat2 - 12 January 2003, 10:49PM
// Reverted - Robbat2 - 13 January 2003, 2:40PM
$sql_query = PMA_SQP_formatHtml($parsed_sql, 'query_only');

// old code did not work, for example, when there is a bracket
// before the SELECT
// so I guess it's ok to check for a real SELECT ... FROM
//$is_select = eregi('^SELECT[[:space:]]+', $sql_query);
$is_select = isset($analyzed_sql[0]['queryflags']['select_from']);

// If the query is a Select, extract the db and table names and modify
// $db and $table, to have correct page headers, links and left frame.
// db and table name may be enclosed with backquotes, db is optionnal,
// query may contain aliases.

// (TODO: if there are more than one table name in the Select:
// - do not extract the first table name
// - do not show a table name in the page header
// - do not display the sub-pages links)

if ($is_select) {
    $prev_db = $db;
    if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name'])) {
        $table = $analyzed_sql[0]['table_ref'][0]['table_true_name'];
    }
    if (isset($analyzed_sql[0]['table_ref'][0]['db'])
       && !empty($analyzed_sql[0]['table_ref'][0]['db'])) {
        $db    = $analyzed_sql[0]['table_ref'][0]['db'];
    }
    else {
        $db = $prev_db;
    }
    $reload  = ($db == $prev_db) ? 0 : 1;
}

/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php3') {
    $goto = 'sql.php3?'
          . PMA_generate_common_url($db, $table)
          . '&amp;pos=' . $pos
          . '&amp;sql_query=' . urlencode($sql_query);
} // end if


/**
 * Go back to further page if table should not be dropped
 */
if (isset($btnDrop) && $btnDrop == $strNo) {
    if (!empty($back)) {
        $goto = $back;
    }
    if ($is_gotofile) {
        if (strpos(' ' . $goto, 'db_details') == 1 && !empty($table)) {
            unset($table);
        }
        include('./' . ereg_replace('\.\.*', '.', $goto));
    } else {
        header('Location: ' . $cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto));
    }
    exit();
} // end if


/**
 * Displays the confirm page if required
 *
 * This part of the script is bypassed if $is_js_confirmed = 1 (already checked
 * with js) because possible security issue is not so important here: at most,
 * the confirm message isn't displayed.
 *
 * Also bypassed if only showing php code.or validating a SQL query
 */
if (!$cfg['Confirm']
    || (isset($is_js_confirmed) && $is_js_confirmed)
    || isset($btnDrop)
    || !empty($GLOBALS['show_as_php'])
    || !empty($GLOBALS['validatequery'])) {
    $do_confirm = FALSE;
} else {
    //$do_confirm = (eregi('DROP[[:space:]]+(IF[[:space:]]+EXISTS[[:space:]]+)?(TABLE|DATABASE[[:space:]])|ALTER[[:space:]]+TABLE[[:space:]]+((`[^`]+`)|([A-Za-z0-9_$]+))[[:space:]]+DROP[[:space:]]|DELETE[[:space:]]+FROM[[:space:]]', $sql_query));

    $do_confirm = isset($analyzed_sql[0]['queryflags']['need_confirm']);
}

if ($do_confirm) {
    $stripped_sql_query = $sql_query;
    include('./header.inc.php3');
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . htmlspecialchars($stripped_sql_query) . '</tt>&nbsp;?<br/>' . "\n";
    ?>
<form action="sql.php3" method="post">
    <?php echo PMA_generate_common_hidden_inputs($db, (isset($table)?$table:'')); ?>
    <input type="hidden" name="sql_query" value="<?php echo urlencode($sql_query); ?>" />
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? $zero_rows : ''; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="<?php echo isset($back) ? $back : ''; ?>" />
    <input type="hidden" name="reload" value="<?php echo isset($reload) ? $reload : 0; ?>" />
    <input type="hidden" name="purge" value="<?php echo isset($purge) ? $purge : ''; ?>" />
    <input type="hidden" name="cpurge" value="<?php echo isset($cpurge) ? $cpurge : ''; ?>" />
    <input type="hidden" name="purgekey" value="<?php echo isset($purgekey) ? $purgekey : ''; ?>" />
    <input type="hidden" name="show_query" value="<?php echo isset($show_query) ? $show_query : ''; ?>" />
    <input type="submit" name="btnDrop" value="<?php echo $strYes; ?>" />
    <input type="submit" name="btnDrop" value="<?php echo $strNo; ?>" />
</form>
    <?php
    echo "\n";
} // end if


/**
 * Executes the query and displays results
 */
else {
    if (!isset($sql_query)) {
        $sql_query = '';
    }
    // Defines some variables
    // loic1: A table has to be created -> left frame should be reloaded
    if ((!isset($reload) || $reload == 0)
        && eregi('^CREATE TABLE[[:space:]]+(.*)', $sql_query)) {
        $reload           = 1;
    }
    // Gets the number of rows per page
    if (!isset($session_max_rows)) {
        $session_max_rows = $cfg['MaxRows'];
    } else if ($session_max_rows != 'all') {
        $cfg['MaxRows']   = $session_max_rows;
    }
    // Defines the display mode (horizontal/vertical) and header "frequency"
    if (empty($disp_direction)) {
        $disp_direction   = $cfg['DefaultDisplay'];
    }
    if (empty($repeat_cells)) {
        $repeat_cells     = $cfg['RepeatCells'];
    }

    // SK -- Patch: $is_group added for use in calculation of total number of
    //              rows.
    //              $is_count is changed for more correct "LIMIT" clause
    //              appending in queries like
    //                "SELECT COUNT(...) FROM ... GROUP BY ..."
    $is_explain = $is_count = $is_export = $is_delete = $is_insert = $is_affected = $is_show = $is_maint = $is_analyse = $is_group = $is_func = FALSE;
    if ($is_select) { // see line 141
        $is_group = eregi('[[:space:]]+(GROUP[[:space:]]+BY|HAVING|SELECT[[:space:]]+DISTINCT)[[:space:]]+', $sql_query);

        $is_func =  !$is_group && (eregi('[[:space:]]+(SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND)\s*\(', $sql_query));
        $is_count = !$is_group && (eregi('^SELECT[[:space:]]+COUNT\((.*\.+)?.*\)', $sql_query));
        $is_export   = (eregi('[[:space:]]+INTO[[:space:]]+OUTFILE[[:space:]]+', $sql_query));
        $is_analyse  = (eregi('[[:space:]]+PROCEDURE[[:space:]]+ANALYSE\(', $sql_query));
    } else if (eregi('^EXPLAIN[[:space:]]+', $sql_query)) {
        $is_explain  = TRUE;
    } else if (eregi('^DELETE[[:space:]]+', $sql_query)) {
        $is_delete   = TRUE;
        $is_affected = TRUE;
    } else if (eregi('^(INSERT|LOAD[[:space:]]+DATA|REPLACE)[[:space:]]+', $sql_query)) {
        $is_insert   = TRUE;
        $is_affected = TRUE;
    } else if (eregi('^UPDATE[[:space:]]+', $sql_query)) {
        $is_affected = TRUE;
    } else if (eregi('^SHOW[[:space:]]+', $sql_query)) {
        $is_show     = TRUE;
    } else if (eregi('^(CHECK|ANALYZE|REPAIR|OPTIMIZE)[[:space:]]+TABLE[[:space:]]+', $sql_query)) {
        $is_maint    = TRUE;
    }

    // Do append a "LIMIT" clause?
    if (isset($pos)
        && (!$cfg['ShowAll'] || $session_max_rows != 'all')
        && !($is_count || $is_export || $is_func || $is_analyse)
        && isset($analyzed_sql[0]['queryflags']['select_from'])
        && !eregi('[[:space:]]LIMIT[[:space:]0-9,-]+$', $sql_query)) {
        $sql_limit_to_append = " LIMIT $pos, ".$cfg['MaxRows'];
        if (eregi('(.*)([[:space:]](PROCEDURE[[:space:]](.*)|FOR[[:space:]]+UPDATE|LOCK[[:space:]]+IN[[:space:]]+SHARE[[:space:]]+MODE))$', $sql_query, $regs)) {
            $full_sql_query  = $regs[1] . $sql_limit_to_append . $regs[2];
        } else {
            $full_sql_query  = $sql_query . $sql_limit_to_append;
        }
    } else {
        $full_sql_query      = $sql_query;
    } // end if...else

    PMA_mysql_select_db($db);

    // If the query is a DELETE query with no WHERE clause, get the number of
    // rows that will be deleted (mysql_affected_rows will always return 0 in
    // this case)
    if ($is_delete
        && eregi('^DELETE([[:space:]].+)?([[:space:]]FROM[[:space:]](.+))$', $sql_query, $parts)
        && !eregi('[[:space:]]WHERE[[:space:]]', $parts[3])) {
        $cnt_all_result = @PMA_mysql_query('SELECT COUNT(*) as count' .  $parts[2]);
        if ($cnt_all_result) {
            $num_rows   = PMA_mysql_result($cnt_all_result, 0, 'count');
            mysql_free_result($cnt_all_result);
        } else {
            $num_rows   = 0;
        }
    }

    //  E x e c u t e    t h e    q u e r y

    // Only if we didn't ask to see the php code (mikebeck)
    if (isset($GLOBALS['show_as_php']) || !empty($GLOBALS['validatequery'])) {
        unset($result);
        $num_rows = 0;
    }
    else {
        // garvin: Measure query time. TODO-Item http://sourceforge.net/tracker/index.php?func=detail&aid=571934&group_id=23067&atid=377411
        list($usec, $sec) = explode(' ',microtime());
        $querytime_before = ((float)$usec + (float)$sec);

        $result   = @PMA_mysql_query($full_sql_query);

        list($usec, $sec) = explode(' ',microtime());
        $querytime_after = ((float)$usec + (float)$sec);

        $GLOBALS['querytime'] = $querytime_after - $querytime_before;

        // Displays an error message if required and stop parsing the script
        if (PMA_mysql_error()) {
            $error        = PMA_mysql_error();
            include('./header.inc.php3');
            $full_err_url = (ereg('^(db_details|tbl_properties)', $err_url))
                          ? $err_url . '&amp;show_query=1&amp;sql_query=' . urlencode($sql_query)
                          : $err_url;
            PMA_mysqlDie($error, $full_sql_query, '', $full_err_url);
        }

        // Gets the number of rows affected/returned
        // (This must be done immediately after the query because
        // mysql_affected_rows() reports about the last query done)

        if (!$is_affected) {
            $num_rows = ($result) ? @mysql_num_rows($result) : 0;
        } else if (!isset($num_rows)) {
            $num_rows = @mysql_affected_rows();
        }

        // Checks if the current database has changed
        // This could happen if the user sends a query like "USE `database`;"
        $res = PMA_mysql_query('SELECT DATABASE() AS "db";');
        $row = PMA_mysql_fetch_array($res);
        if ($db != $row['db']) {
            $db     = $row['db'];
            $reload = 1;
        }
        @mysql_free_result($res);
        unset($res);
        unset($row);

        // tmpfile remove after convert encoding appended by Y.Kawada
        if (function_exists('PMA_kanji_file_conv')
            && (isset($textfile) && file_exists($textfile))) {
            unlink($textfile);
        }

        // Counts the total number of rows for the same 'SELECT' query without the
        // 'LIMIT' clause that may have been programatically added

        if (empty($sql_limit_to_append)) {
            $unlim_num_rows         = $num_rows;
        }
        else if ($is_select) {

                //    c o u n t    q u e r y

                // If we are just browsing, there is only one table, 
                // and no where clause (or just 'WHERE 1 '),
                // so we do a quick count (which uses MaxExactCount)
                // because SQL_CALC_FOUND_ROWS
                // is not quick on large InnoDB tables

                if (!isset($analyzed_sql[0]['table_ref'][1]['table_name'])
                 && (empty($analyzed_sql[0]['where_clause'])
                   || $analyzed_sql[0]['where_clause'] == '1 ')) {

                    // "just browsing"
                    $unlim_num_rows = PMA_countRecords($db, $table, TRUE);

                } else { // not "just browsing"

                    if (PMA_MYSQL_INT_VERSION < 40000) {
                        if (eregi('DISTINCT(.*)', $sql_query)) {
                            $count_what = 'DISTINCT ' . $analyzed_sql[0]['select_expr_clause'];
                        } else {
                            $count_what = '*';
                        }
 
                        $count_query = 'SELECT COUNT(' . $count_what . ') AS count';
                    }
                    else {
                        $count_query = 'SELECT SQL_CALC_FOUND_ROWS ';
                    } 

                    // add the remaining of select expression if there is
                    // a GROUP BY or HAVING clause
                    if (PMA_MYSQL_INT_VERSION < 40000
                     && $count_what =='*'
                     && (!empty($analyzed_sql[0]['group_by_clause'])
                        || !empty($analyzed_sql[0]['having_clause']))) {
                        $count_query .= ' ,' . $analyzed_sql[0]['select_expr_clause'];
                    }

                    // add select expression after the SQL_CALC_FOUND_ROWS
                    if (PMA_MYSQL_INT_VERSION >= 40000) {
                        $count_query .= $analyzed_sql[0]['select_expr_clause'];
                    }


                    if (!empty($analyzed_sql[0]['from_clause'])) {
                        $count_query .= ' FROM ' . $analyzed_sql[0]['from_clause'];
                    }

                    if (!empty($analyzed_sql[0]['where_clause'])) {
                        $count_query .= ' WHERE ' . $analyzed_sql[0]['where_clause'];
                    }
                    if (!empty($analyzed_sql[0]['group_by_clause'])) {
                        $count_query .= ' GROUP BY ' . $analyzed_sql[0]['group_by_clause'];
                    }

                    if (!empty($analyzed_sql[0]['having_clause'])) {
                        $count_query .= ' HAVING ' . $analyzed_sql[0]['having_clause'];
                    }

                    // if using SQL_CALC_FOUND_ROWS, add a LIMIT to avoid
                    // long delays. Returned count will be complete anyway.

                    if (PMA_MYSQL_INT_VERSION >= 40000) {
                        $count_query .= ' LIMIT 1';
                    }

                    // do not put the order_by_clause, it interferes
                    // run the count query
                    if (PMA_MYSQL_INT_VERSION < 40000) {
                        if ($cnt_all_result = mysql_query($count_query)) {
                            if ($is_group) {
                                $unlim_num_rows = @mysql_num_rows($cnt_all_result);
                            } else {
                                $unlim_num_rows = mysql_result($cnt_all_result, 0, 'count');
                            }
                            mysql_free_result($cnt_all_result);
                        } else {
                            if (mysql_error()) {
                                // there are some cases where the generated
                                // count_query (for MySQL 3) is wrong,
                                // so we get here.
                                //TODO: use a big unlimited query to get
                                // the correct number of rows (depending
                                // on a config variable?)
                                $unlim_num_rows = 0;
                            }
                        }
                    } else {
                        mysql_query($count_query);
                        if (mysql_error()) {
                        // void. I tried the case
                        // (SELECT `User`, `Host`, `Db`, `Select_priv` FROM `db`)
                        // UNION (SELECT `User`, `Host`, "%" AS "Db",
                        // `Select_priv`
                        // FROM `user`) ORDER BY `User`, `Host`, `Db`;
                        // and although the generated count_query is wrong
                        // the SELECT FOUND_ROWS() work!
                        }
                        $cnt_all_result = mysql_query('SELECT FOUND_ROWS() as count');
                        $unlim_num_rows = mysql_result($cnt_all_result,0,'count');
                    }
            } // end else "just browsing"

        } else { // not $is_select
             $unlim_num_rows         = 0;
        } // end rows total count

        // garvin: if a table or database gets dropped, check column comments.
        if (isset($purge) && $purge == '1') {
            include('./libraries/relation.lib.php3');
            $cfgRelation = PMA_getRelationsParam();

            if (isset($table) && isset($db) && !empty($table) && !empty($db)) {
                // garvin: Only a table is deleted. Remove all references in PMA_*
                if ($cfgRelation['commwork']) {
                        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                        $rmv_rs    = PMA_query_as_cu($remove_query);
                        unset($rmv_query);
                }

                if ($cfgRelation['displaywork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }

                if ($cfgRelation['pdfwork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords'])
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }

                if ($cfgRelation['relwork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);

                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }
            } elseif (isset($db) && !empty($db)) {
                // garvin: A whole DB gets deleted. Remove all references in PMA_*
                if ($cfgRelation['commwork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }

                if ($cfgRelation['bookmarkwork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['bookmark'])
                                . ' WHERE dbase  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }

                if ($cfgRelation['displaywork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }
            
                if ($cfgRelation['pdfwork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);

                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords'])
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }

                if ($cfgRelation['relwork']) {
                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);

                    $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\'';
                    $rmv_rs    = PMA_query_as_cu($remove_query);
                    unset($rmv_query);
                }

            } else {
                // garvin: VOID. No DB/Table gets deleted.
            } // end if relation-stuff
         } // end if ($purge)
         
        // garvin: If a column gets dropped, do relation magic.
        if (isset($cpurge) && $cpurge == '1' && isset($purgekey)
            && isset($db) && isset($table)
            && !empty($db) && !empty($table) && !empty($purgekey)) {
            include('./libraries/relation.lib.php3');
            $cfgRelation = PMA_getRelationsParam();

            if ($cfgRelation['commwork']) {
                $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                            . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                            . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND column_name = \'' . PMA_sqlAddslashes(urldecode($purgekey)) . '\'';
                $rmv_rs    = PMA_query_as_cu($remove_query);
                unset($rmv_query);
            }

            if ($cfgRelation['displaywork']) {
                $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                            . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                            . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND display_field = \'' . PMA_sqlAddslashes(urldecode($purgekey)) . '\'';
                $rmv_rs    = PMA_query_as_cu($remove_query);
                unset($rmv_query);
            }

            if ($cfgRelation['relwork']) {
                $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                            . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                            . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND master_field = \'' . PMA_sqlAddslashes(urldecode($purgekey)) . '\'';
                $rmv_rs    = PMA_query_as_cu($remove_query);
                unset($rmv_query);

                $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                            . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                            . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND foreign_field = \'' . PMA_sqlAddslashes(urldecode($purgekey)) . '\'';
                $rmv_rs    = PMA_query_as_cu($remove_query);
                unset($rmv_query);
            }
        } // end if column PMA_* purge
    } // end else "didn't ask to see php code"
        

    // No rows returned -> move back to the calling page
    if ($num_rows < 1 || $is_affected) {
        if ($is_delete) {
            $message = $strDeletedRows . '&nbsp;' . $num_rows;
        } else if ($is_insert) {
            $message = $strInsertedRows . '&nbsp;' . $num_rows;
            $insert_id = mysql_insert_id();
            if ($insert_id != 0) {
                $message .= '<br />'.$strInsertedRowId . '&nbsp;' . $insert_id;
            }
        } else if ($is_affected) {
            $message = $strAffectedRows . '&nbsp;' . $num_rows;
        } else if (!empty($zero_rows)) {
            $message = $zero_rows;
        } else if (!empty($GLOBALS['show_as_php'])) {
            $message = $strPhp;
        } else if (!empty($GLOBALS['validatequery'])) {
            $message = $strValidateSQL;
        } else {
            $message = $strEmptyResultSet;
        }

        $message .= ' ' . (isset($GLOBALS['querytime']) ? '(' . sprintf($strQueryTime, $GLOBALS['querytime']) . ')' : '');

        if ($is_gotofile) {
            $goto = ereg_replace('\.\.*', '.', $goto);
            // Checks for a valid target script
            if (isset($table) && $table == '') {
                unset($table);
            }
            if (isset($db) && $db == '') {
                unset($db);
            }
            $is_db = $is_table = FALSE;
            if (strpos(' ' . $goto, 'tbl_properties') == 1) {
                if (!isset($table)) {
                    $goto     = 'db_details.php3';
                } else {
                    $is_table = @PMA_mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
                    if (!($is_table && @mysql_numrows($is_table))) {
                        $goto = 'db_details.php3';
                        unset($table);
                    }
                } // end if... else...
            }
            if (strpos(' ' . $goto, 'db_details') == 1) {
                if (isset($table)) {
                    unset($table);
                }
                if (!isset($db)) {
                    $goto     = 'main.php3';
                } else {
                    $is_db    = @PMA_mysql_select_db($db);
                    if (!$is_db) {
                        $goto = 'main.php3';
                        unset($db);
                    }
                } // end if... else...
            }
            // Loads to target script
            if (strpos(' ' . $goto, 'db_details') == 1
                || strpos(' ' . $goto, 'tbl_properties') == 1) {
                $js_to_run = 'functions.js';
            }
            if ($goto != 'main.php3') {
                include('./header.inc.php3');
            }
            $active_page = $goto;
            include('./' . $goto);
        } // end if file_exist
        else {
            header('Location: ' . $cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto) . '&message=' . urlencode($message));
        } // end else
        exit();
    } // end no rows returned

    // At least one row is returned -> displays a table with results
    else {
        // Displays the headers
        if (isset($show_query)) {
            unset($show_query);
        }
        if (isset($printview) && $printview == '1') {
            include('./header_printview.inc.php3');
        } else {
            $js_to_run = 'functions.js';
            unset($message);
            if (!empty($table)) {
                include('./tbl_properties_common.php3');
                $url_query .= '&amp;goto=tbl_properties.php3&amp;back=tbl_properties.php3';
                include('./tbl_properties_table_info.php3');
            }
            else {
                include('./db_details_common.php3');
                include('./db_details_db_info.php3');
            }
            include('./libraries/relation.lib.php3');
            $cfgRelation = PMA_getRelationsParam();
        }

        // Gets the list of fields properties
        if (isset($result) && $result) {
            while ($field = PMA_mysql_fetch_field($result)) {
                $fields_meta[] = $field;
            }
            $fields_cnt        = count($fields_meta);
        }

        // Display previous update query (from tbl_replace)
        if (isset($disp_query) && $cfg['ShowSQL'] == TRUE) {
            $tmp_sql_query = $GLOBALS['sql_query'];
            $tmp_sql_limit_to_append = (isset($GLOBALS['sql_limit_to_append'])?$GLOBALS['sql_limit_to_append']:'');
            $GLOBALS['sql_query'] = $disp_query;
            $GLOBALS['sql_limit_to_append'] = '';
            PMA_showMessage($disp_message);
            $GLOBALS['sql_query'] = $tmp_sql_query;
            $GLOBALS['sql_limit_to_append'] = $tmp_sql_limit_to_append;
        }

        // Displays the results in a table
        include('./libraries/display_tbl.lib.php3');
        if (empty($disp_mode)) {
            // see the "PMA_setDisplayMode()" function in
            // libraries/display_tbl.lib.php3
            $disp_mode = 'urdr111101';
        }
        if (!isset($dontlimitchars)) {
            $dontlimitchars = 0;
        }

        PMA_displayTable($result, $disp_mode, $analyzed_sql);
        mysql_free_result($result);

        if ($disp_mode[6] == '1' || $disp_mode[9] == '1') {
            echo "\n";
            echo '<p>' . "\n";

            // Displays "Insert a new row" link if required
            if ($disp_mode[6] == '1') {
                $lnk_goto  = 'sql.php3?'
                           . PMA_generate_common_url($db, $table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
                           . '&amp;dontlimitchars=' . $dontlimitchars
                           . '&amp;sql_query=' . urlencode($sql_query);
                $url_query = '?'
                           . PMA_generate_common_url($db, $table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
                           . '&amp;dontlimitchars=' . $dontlimitchars
                           . '&amp;sql_query=' . urlencode($sql_query)
                           . '&amp;goto=' . urlencode($lnk_goto);

                echo '    <!-- Insert a new row -->' . "\n"
                   . '    <a href="tbl_change.php3' . $url_query . '">' . $strInsertNewRow . '</a>';
                if ($disp_mode[9] == '1') {
                    echo '<br />';
                }
                echo "\n";
            } // end insert new row

            // Displays "printable view" link if required
            if ($disp_mode[9] == '1') {
                $url_query = '?'
                           . PMA_generate_common_url($db, $table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
                           . '&amp;printview=1'
                           . ((isset($dontlimitchars) && $dontlimitchars == '1') ? '&amp;dontlimitchars=1' : '')
                           . '&amp;sql_query=' . urlencode($sql_query);
                echo '    <!-- Print view -->' . "\n"
                   . '    <a href="sql.php3' . $url_query . '" target="print_view">' . $strPrintView . '</a>' . "\n";
            } // end displays "printable view"

            echo '</p>' . "\n";
        }

        // Export link, if only one table
        // (the url_query has extra parameters that won't be used to export)
        if (!isset($printview)
           && isset($analyzed_sql[0]['table_ref'][0]['table_true_name'])
           && !isset($analyzed_sql[0]['table_ref'][1]['table_true_name'])) {
                echo '    <!-- Export -->' . "\n"
                   . '    <a href="tbl_properties_export.php3' . $url_query  . '&amp;unlim_num_rows=' . $unlim_num_rows . '">' . $strExport . '</a>' . "\n";
        }

        // Bookmark Support if required
        if ($disp_mode[7] == '1'
            && ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table'] && empty($id_bookmark))
            && !empty($sql_query)) {
            echo "\n";

            $goto = 'sql.php3?'
                  . PMA_generate_common_url($db, $table)
                  . '&amp;pos=' . $pos
                  . '&amp;session_max_rows=' . $session_max_rows
                  . '&amp;disp_direction=' . $disp_direction
                  . '&amp;repeat_cells=' . $repeat_cells
                  . '&amp;dontlimitchars=' . $dontlimitchars
                  . '&amp;sql_query=' . urlencode($sql_query)
                  . '&amp;id_bookmark=1';
            ?>
<!-- Bookmark the query -->
<form action="sql.php3" method="post" onsubmit="return emptyFormElements(this, 'fields[label]');">
            <?php
            echo "\n";
            if ($disp_mode[3] == '1') {
                echo '    <i>' . $strOr . '</i>' . "\n";
            }
            ?>
    <br /><br />
    <?php echo $strBookmarkLabel; ?>&nbsp;:
    <?php echo PMA_generate_common_hidden_inputs(); ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="fields[dbase]" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="fields[user]" value="<?php echo $cfg['Bookmark']['user']; ?>" />
    <input type="hidden" name="fields[query]" value="<?php echo urlencode($sql_query); ?>" />
    <input type="text" name="fields[label]" value="" />
    <input type="submit" name="store_bkm" value="<?php echo $strBookmarkThis; ?>" />
</form>
            <?php
        } // end bookmark support

        // Do print the page if required
        if (isset($printview) && $printview == '1') {
            echo "\n";
            ?>
<script type="text/javascript" language="javascript1.2">
<!--
// Do print the page
if (typeof(window.print) != 'undefined') {
    window.print();
}
//-->
</script>
            <?php
        } // end print case
    } // end rows returned

} // end executes the query
echo "\n\n";


/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
