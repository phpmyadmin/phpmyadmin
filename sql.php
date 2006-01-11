<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/tbl_indexes.lib.php');
require_once('./libraries/check_user_privileges.lib.php');
require_once('./libraries/bookmark.lib.php');

/**
 * Defines the url to return to in case of error in a sql statement
 */
// Security checkings
if (!empty($goto)) {
    $is_gotofile     = preg_replace('@^([^?]+).*$@s', '\\1', $goto);
    if (!@file_exists('./' . $is_gotofile)) {
        unset($goto);
    } else {
        $is_gotofile = ($is_gotofile == $goto);
    }
} // end if (security checkings)

if (empty($goto)) {
    $goto = (empty($table)) ? $cfg['DefaultTabDatabase'] : $cfg['DefaultTabTable'];
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

// This one is just to fill $db
if (isset($fields['dbase'])) {
    $db = $fields['dbase'];
}

// Default to browse if no query set an we have table (needed for browsing from DefaultTabTable)
if (!isset($sql_query) && isset($table) && isset($db)) {
    require_once('./libraries/bookmark.lib.php');
    $book_sql_query = PMA_queryBookmarks($db, $GLOBALS['cfg']['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');
    if (!empty($book_sql_query)) {
        $sql_query = $book_sql_query;
    } else {
        $sql_query = 'SELECT * FROM ' . PMA_backquote($table);
    }
    unset($book_sql_query);
} else {
    // Now we can check the parameters
    PMA_checkParameters(array('sql_query'));
}

// instead of doing the test twice
$is_drop_database = preg_match('@DROP[[:space:]]+DATABASE[[:space:]]+@i', $sql_query);

/**
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if (!defined('PMA_CHK_DROP')
    && !$cfg['AllowUserDropDatabase']
    && $is_drop_database
    && !$is_superuser) {
    require_once('./libraries/header.inc.php');
    PMA_mysqlDie($strNoDropDatabases, '', '', $err_url);
} // end if


/**
 * Need to find the real end of rows?
 */

if (isset($find_real_end) && $find_real_end) {
    $unlim_num_rows = PMA_countRecords($db, $table, TRUE, TRUE);
    $pos = @((ceil($unlim_num_rows / $session_max_rows) - 1) * $session_max_rows);
}
/**
 * Avoids undefined variables
 */
elseif (!isset($pos)) {
    $pos = 0;
}

/**
 * Bookmark add
 */
if (isset($store_bkm)) {
    PMA_addBookmarks($fields, $cfg['Bookmark'], (isset($bkm_all_users) && $bkm_all_users == 'true' ? true : false));
    PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . $goto);
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

$GLOBALS['unparsed_sql'] = $sql_query;
$parsed_sql = PMA_SQP_parse($sql_query);
$analyzed_sql = PMA_SQP_analyze($parsed_sql);

// Bug #641765 - Robbat2 - 12 January 2003, 10:49PM
// Reverted - Robbat2 - 13 January 2003, 2:40PM

// lem9: for bug 780516: now that we use case insensitive preg_match
// or flags from the analyser, do not put back the reformatted query
// into $sql_query, to make this kind of query work without
// capitalizing keywords:
//
// CREATE TABLE SG_Persons (
//  id int(10) unsigned NOT NULL auto_increment,
//  first varchar(64) NOT NULL default '',
//  PRIMARY KEY  (`id`)
// )
//
// Note: now we probably do not need to fill and use $GLOBALS['unparsed_sql']
// but I let this intact for now.
//
//$sql_query = PMA_SQP_formatHtml($parsed_sql, 'query_only');


// check for a real SELECT ... FROM
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
    // Nijel: don't change reload, if we already decided to reload in import
    if ( empty( $reload ) ) {
        $reload  = ($db == $prev_db) ? 0 : 1;
    }
}

/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php') {
    $is_gotofile = FALSE;
    $goto = 'sql.php?'
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
        $active_page = $goto;
        require('./' . PMA_securePath($goto));
    } else {
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto));
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

    // if we are coming from a "Create PHP code" or a "Without PHP Code"
    // dialog, we won't execute the query anyway, so don't confirm
    //|| !empty($GLOBALS['show_as_php'])
    || isset($GLOBALS['show_as_php'])

    || !empty($GLOBALS['validatequery'])) {
    $do_confirm = FALSE;
} else {
    $do_confirm = isset($analyzed_sql[0]['queryflags']['need_confirm']);
}

if ( $do_confirm ) {
    $stripped_sql_query = $sql_query;
    require_once('./libraries/header.inc.php');
    if ( $is_drop_database) {
        echo '<h1 class="warning">' . $strDropDatabaseStrongWarning . '</h1>';
    }
    echo '<form action="sql.php" method="post">' . "\n"
        .PMA_generate_common_hidden_inputs($db, (isset($table)?$table:''));
    ?>
    <input type="hidden" name="sql_query" value="<?php echo urlencode($sql_query); ?>" />
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? PMA_sanitize($zero_rows) : ''; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="<?php echo isset($back) ? PMA_sanitize($back) : ''; ?>" />
    <input type="hidden" name="reload" value="<?php echo isset($reload) ? PMA_sanitize($reload) : 0; ?>" />
    <input type="hidden" name="purge" value="<?php echo isset($purge) ? PMA_sanitize($purge) : ''; ?>" />
    <input type="hidden" name="cpurge" value="<?php echo isset($cpurge) ? PMA_sanitize($cpurge) : ''; ?>" />
    <input type="hidden" name="purgekey" value="<?php echo isset($purgekey) ? PMA_sanitize($purgekey) : ''; ?>" />
    <input type="hidden" name="show_query" value="<?php echo isset($show_query) ? PMA_sanitize($show_query) : ''; ?>" />
    <?php
    echo '<fieldset class="confirmation">' . "\n"
        .'    <legend>' . $strDoYouReally . '</legend>'
        .'    <tt>' . htmlspecialchars( $stripped_sql_query ) . '</tt>' . "\n"
        .'</fieldset>' . "\n"
        .'<fieldset class="tblFooters">' . "\n";
    ?>
    <input type="submit" name="btnDrop" value="<?php echo $strYes; ?>" id="buttonYes" />
    <input type="submit" name="btnDrop" value="<?php echo $strNo; ?>" id="buttonNo" />
    <?php
    echo '</fieldset>' . "\n"
       . '</form>' . "\n";
} // end if $do_confirm


/**
 * Executes the query and displays results
 */
else {
    if (!isset($sql_query)) {
        $sql_query = '';
    }
    // Defines some variables
    // A table has to be created or renamed -> left frame should be reloaded
    // TODO: use the parser/analyzer

    if ( empty( $reload )
        && (preg_match('@^CREATE (VIEW|TABLE)\s+@i', $sql_query)
         || preg_match('@^ALTER TABLE\s+.*RENAME@i', $sql_query))) {
        $reload           = 1;
    }
    // Gets the number of rows per page
    if (empty($session_max_rows)) {
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

    // TODO: detect all this with the parser, to avoid problems finding
    // those strings in comments or backquoted identifiers

    $is_explain = $is_count = $is_export = $is_delete = $is_insert = $is_affected = $is_show = $is_maint = $is_analyse = $is_group = $is_func = FALSE;
    if ($is_select) { // see line 141
        $is_group = preg_match('@(GROUP[[:space:]]+BY|HAVING|SELECT[[:space:]]+DISTINCT)[[:space:]]+@i', $sql_query);
        $is_func =  !$is_group && (preg_match('@[[:space:]]+(SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND)\s*\(@i', $sql_query));
        $is_count = !$is_group && (preg_match('@^SELECT[[:space:]]+COUNT\((.*\.+)?.*\)@i', $sql_query));
        $is_export   = (preg_match('@[[:space:]]+INTO[[:space:]]+OUTFILE[[:space:]]+@i', $sql_query));
        $is_analyse  = (preg_match('@[[:space:]]+PROCEDURE[[:space:]]+ANALYSE@i', $sql_query));
    } else if (preg_match('@^EXPLAIN[[:space:]]+@i', $sql_query)) {
        $is_explain  = TRUE;
    } else if (preg_match('@^DELETE[[:space:]]+@i', $sql_query)) {
        $is_delete   = TRUE;
        $is_affected = TRUE;
    } else if (preg_match('@^(INSERT|LOAD[[:space:]]+DATA|REPLACE)[[:space:]]+@i', $sql_query)) {
        $is_insert   = TRUE;
        $is_affected = TRUE;
    } else if (preg_match('@^UPDATE[[:space:]]+@i', $sql_query)) {
        $is_affected = TRUE;
    } else if (preg_match('@^SHOW[[:space:]]+@i', $sql_query)) {
        $is_show     = TRUE;
    } else if (preg_match('@^(CHECK|ANALYZE|REPAIR|OPTIMIZE)[[:space:]]+TABLE[[:space:]]+@i', $sql_query)) {
        $is_maint    = TRUE;
    }

    // Do append a "LIMIT" clause?
    if (isset($pos)
        && (!$cfg['ShowAll'] || $session_max_rows != 'all')
        && !($is_count || $is_export || $is_func || $is_analyse)
        && isset($analyzed_sql[0]['queryflags']['select_from'])
        && !isset($analyzed_sql[0]['queryflags']['offset'])
        && !preg_match('@[[:space:]]LIMIT[[:space:]0-9,-]+$@i', $sql_query)) {
        $sql_limit_to_append = " LIMIT $pos, ".$cfg['MaxRows'] . " ";

        $full_sql_query  = $analyzed_sql[0]['section_before_limit'] . "\n" . $sql_limit_to_append . $analyzed_sql[0]['section_after_limit'];
        // FIXME: pretty printing of this modified query

        if (isset($display_query)) {
            // if the analysis of the original query revealed that we found
            // a section_after_limit, we now have to analyze $display_query
            // to display it correctly

            if (!empty($analyzed_sql[0]['section_after_limit']) && trim($analyzed_sql[0]['section_after_limit']) != ';') {
                $analyzed_display_query = PMA_SQP_analyze(PMA_SQP_parse($display_query));
                $display_query  = $analyzed_display_query[0]['section_before_limit'] . "\n" . $sql_limit_to_append . $analyzed_display_query[0]['section_after_limit'];
            }
        }

    } else {
        $full_sql_query      = $sql_query;
    } // end if...else

    if (isset($db)) {
        PMA_DBI_select_db($db);
    }

    // If the query is a DELETE query with no WHERE clause, get the number of
    // rows that will be deleted (mysql_affected_rows will always return 0 in
    // this case)

    if ($is_delete
        && preg_match('@^DELETE([[:space:]].+)?(FROM[[:space:]](.+))$@i', $sql_query, $parts)
        && !preg_match('@[[:space:]]WHERE[[:space:]]@i', $parts[3])) {
        $cnt_all_result = @PMA_DBI_try_query('SELECT COUNT(*) as count ' .  $parts[2]);
        if ($cnt_all_result) {
            list($num_rows) = PMA_DBI_fetch_row($cnt_all_result);
            PMA_DBI_free_result($cnt_all_result);
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

        $result   = @PMA_DBI_try_query($full_sql_query, NULL, PMA_DBI_QUERY_STORE);

        list($usec, $sec) = explode(' ',microtime());
        $querytime_after = ((float)$usec + (float)$sec);

        $GLOBALS['querytime'] = $querytime_after - $querytime_before;

        // Displays an error message if required and stop parsing the script
        if ($error        = PMA_DBI_getError()) {
            require_once('./libraries/header.inc.php');
            $full_err_url = (preg_match('@^(db_details|tbl_properties)@', $err_url))
                          ? $err_url . '&amp;show_query=1&amp;sql_query=' . urlencode($sql_query)
                          : $err_url;
            PMA_mysqlDie($error, $full_sql_query, '', $full_err_url);
        }
        unset($error);

        // Gets the number of rows affected/returned
        // (This must be done immediately after the query because
        // mysql_affected_rows() reports about the last query done)

        if (!$is_affected) {
            $num_rows = ($result) ? @PMA_DBI_num_rows($result) : 0;
        } else if (!isset($num_rows)) {
            $num_rows = @PMA_DBI_affected_rows();
        }

        // Checks if the current database has changed
        // This could happen if the user sends a query like "USE `database`;"
        $res = PMA_DBI_query('SELECT DATABASE() AS \'db\';');
        $row = PMA_DBI_fetch_row($res);
        if (is_array($row) && isset($row[0]) && (strcasecmp($db,$row[0]) != 0)) {
            $db     = $row[0];
            $reload = 1;
        }
        @PMA_DBI_free_result($res);
        unset($res, $row);

        // tmpfile remove after convert encoding appended by Y.Kawada
        if (function_exists('PMA_kanji_file_conv')
            && (isset($textfile) && file_exists($textfile))) {
            unlink($textfile);
        }

        // Counts the total number of rows for the same 'SELECT' query without the
        // 'LIMIT' clause that may have been programatically added

        if (empty($sql_limit_to_append)) {
            $unlim_num_rows         = $num_rows;
            // if we did not append a limit, set this to get a correct
            // "Showing rows..." message
            $GLOBALS['session_max_rows'] = 'all';
        }
        else if ($is_select) {

                //    c o u n t    q u e r y

                // If we are "just browsing", there is only one table,
                // and no where clause (or just 'WHERE 1 '),
                // so we do a quick count (which uses MaxExactCount)
                // because SQL_CALC_FOUND_ROWS
                // is not quick on large InnoDB tables

                // but do not count again if we did it previously
                // due to $find_real_end == TRUE

                if (!$is_group
                 && !isset($analyzed_sql[0]['queryflags']['union'])
                 && !isset($analyzed_sql[0]['table_ref'][1]['table_name'])
                 && (empty($analyzed_sql[0]['where_clause'])
                   || $analyzed_sql[0]['where_clause'] == '1 ')
                 && !isset($find_real_end)
                 ) {

                    // "j u s t   b r o w s i n g"
                    $unlim_num_rows = PMA_countRecords($db, $table, TRUE);

                } else { // n o t   " j u s t   b r o w s i n g "

                    if (PMA_MYSQL_INT_VERSION < 40000) {

                        // detect this case:
                        // SELECT DISTINCT x AS foo, y AS bar FROM sometable

                        if (isset($analyzed_sql[0]['queryflags']['distinct'])) {
                            $count_what = 'DISTINCT ';
                            $first_expr = TRUE;
                            foreach($analyzed_sql[0]['select_expr'] as $part) {
                                $count_what .= (!$first_expr ? ', ' : '') . $part['expr'];
                                $first_expr = FALSE;
                            }
                         } else {
                             $count_what = '*';
                         }
                        $count_query = 'SELECT COUNT(' . $count_what . ') AS count';
                    }

                    // add the remaining of select expression if there is
                    // a GROUP BY or HAVING clause
                    if (PMA_MYSQL_INT_VERSION < 40000
                     && $count_what =='*'
                     && (!empty($analyzed_sql[0]['group_by_clause'])
                        || !empty($analyzed_sql[0]['having_clause']))) {
                        $count_query .= ' ,' . $analyzed_sql[0]['select_expr_clause'];
                    }

                    if (PMA_MYSQL_INT_VERSION >= 40000) {
                         // add select expression after the SQL_CALC_FOUND_ROWS

                            // for UNION, just adding SQL_CALC_FOUND_ROWS
                            // after the first SELECT works.

                            // take the left part, could be:
                            // SELECT
                            // (SELECT
                            $count_query = PMA_SQP_formatHtml($parsed_sql, 'query_only', 0, $analyzed_sql[0]['position_of_first_select'] + 1);
                            $count_query .= ' SQL_CALC_FOUND_ROWS ';
                            // add everything that was after the first SELECT
                            $count_query .= PMA_SQP_formatHtml($parsed_sql, 'query_only', $analyzed_sql[0]['position_of_first_select']+1);
                    } else { // PMA_MYSQL_INT_VERSION < 40000

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
                    } // end if

                    // if using SQL_CALC_FOUND_ROWS, add a LIMIT to avoid
                    // long delays. Returned count will be complete anyway.
                    // (but a LIMIT would disrupt results in an UNION)

                    if (PMA_MYSQL_INT_VERSION >= 40000
                    && !isset($analyzed_sql[0]['queryflags']['union'])) {
                        $count_query .= ' LIMIT 1';
                    }

                    // run the count query

                    if (PMA_MYSQL_INT_VERSION < 40000) {
                        if ($cnt_all_result = PMA_DBI_try_query($count_query)) {
                            if ($is_group && $count_what == '*') {
                                $unlim_num_rows = @PMA_DBI_num_rows($cnt_all_result);
                            } else {
                                $unlim_num_rows = PMA_DBI_fetch_assoc($cnt_all_result);
                                $unlim_num_rows = $unlim_num_rows['count'];
                            }
                            PMA_DBI_free_result($cnt_all_result);
                        } else {
                            if (PMA_DBI_getError()) {

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
                        PMA_DBI_try_query($count_query);
                        // if (mysql_error()) {
                        // void.
                        // I tried the case
                        // (SELECT `User`, `Host`, `Db`, `Select_priv` FROM `db`)
                        // UNION (SELECT `User`, `Host`, "%" AS "Db",
                        // `Select_priv`
                        // FROM `user`) ORDER BY `User`, `Host`, `Db`;
                        // and although the generated count_query is wrong
                        // the SELECT FOUND_ROWS() work! (maybe it gets the
                        // count from the latest query that worked)
                        //
                        // another case where the count_query is wrong:
                        // SELECT COUNT( * ), f1 from t1 group by f1
                        // and you click to sort on count( * )
                        // }
                        $cnt_all_result       = PMA_DBI_query('SELECT FOUND_ROWS() as count;');
                        list($unlim_num_rows) = PMA_DBI_fetch_row($cnt_all_result);
                        @PMA_DBI_free_result($cnt_all_result);
                    }
            } // end else "just browsing"

        } else { // not $is_select
             $unlim_num_rows         = 0;
        } // end rows total count

        // garvin: if a table or database gets dropped, check column comments.
        if (isset($purge) && $purge == '1') {
            require_once('./libraries/relation_cleanup.lib.php');

            if (isset($table) && isset($db) && !empty($table) && !empty($db)) {
                PMA_relationsCleanupTable($db, $table);
            } elseif (isset($db) && !empty($db)) {
                PMA_relationsCleanupDatabase($db);
            } else {
                // garvin: VOID. No DB/Table gets deleted.
            } // end if relation-stuff
         } // end if ($purge)

        // garvin: If a column gets dropped, do relation magic.
        if (isset($cpurge) && $cpurge == '1' && isset($purgekey)
            && isset($db) && isset($table)
            && !empty($db) && !empty($table) && !empty($purgekey)) {
            require_once('./libraries/relation_cleanup.lib.php');
            PMA_relationsCleanupColumn($db, $table, $purgekey);

        } // end if column PMA_* purge
    } // end else "didn't ask to see php code"

    // No rows returned -> move back to the calling page
    if ($num_rows < 1 || $is_affected) {
        if ($is_delete) {
            $message = $strDeletedRows . '&nbsp;' . $num_rows;
        } else if ($is_insert) {
            $message = $strInsertedRows . '&nbsp;' . $num_rows;
            $insert_id = PMA_DBI_insert_id();
            if ($insert_id != 0) {
                // insert_id is id of FIRST record inserted in one insert, so if we inserted multiple rows, we had to increment this
                $message .= '[br]'.$strInsertedRowId . '&nbsp;' . ($insert_id + $num_rows - 1);
            }
        } else if ($is_affected) {
            $message = $strAffectedRows . '&nbsp;' . $num_rows;

            // Ok, here is an explanation for the !$is_select.
            // The form generated by sql_query_form.lib.php
            // and db_details.php has many submit buttons
            // on the same form, and some confusion arises from the
            // fact that $zero_rows is sent for every case.
            // The $zero_rows containing $strSuccess and sent with
            // the form should not have priority over
            // errors like $strEmptyResultSet
        } else if (!empty($zero_rows) && !$is_select) {
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
            $goto = PMA_securePath($goto);
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
                    $goto = 'db_details.php';
                } else {
                    $is_table = @PMA_DBI_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\';', NULL, PMA_DBI_QUERY_STORE);
                    if (!($is_table && @PMA_DBI_num_rows($is_table))) {
                        $goto = 'db_details.php';
                        unset($table);
                    }
                    @PMA_DBI_free_result($is_table);
                } // end if... else...
            }
            if (strpos(' ' . $goto, 'db_details') == 1) {
                if (isset($table)) {
                    unset($table);
                }
                if (!isset($db)) {
                    $goto = 'main.php';
                } else {
                    $is_db    = @PMA_DBI_select_db($db);
                    if (!$is_db) {
                        $goto = 'main.php';
                        unset($db);
                    }
                } // end if... else...
            }
            // Loads to target script
            if (strpos(' ' . $goto, 'db_details') == 1
                || strpos(' ' . $goto, 'tbl_properties') == 1) {
                $js_to_run = 'functions.js';
            }
            if ($goto != 'main.php') {
                require_once('./libraries/header.inc.php');
            }
            $active_page = $goto;
            require('./' . $goto);
        } // end if file_exist
        else {
            PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto) . '&message=' . urlencode($message));
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
            require_once('./libraries/header_printview.inc.php');
        } else {
            $js_to_run = 'functions.js';
            unset($message);
            if (!empty($table)) {
                require('./libraries/tbl_properties_common.php');
                $url_query .= '&amp;goto=tbl_properties.php&amp;back=tbl_properties.php';
                require('./libraries/tbl_properties_table_info.inc.php');
                require('./libraries/tbl_properties_links.inc.php');
            }
            elseif (!empty($db)) {
                require('./libraries/db_details_common.inc.php');
                require('./libraries/db_details_db_info.inc.php');
            }
            else {
                require('./libraries/server_common.inc.php');
                require('./libraries/server_links.inc.php');
            }
        }

        if (!empty($db)) {
            require_once('./libraries/relation.lib.php');
            $cfgRelation = PMA_getRelationsParam();
        }

        // Gets the list of fields properties
        if (isset($result) && $result) {
            $fields_meta = PMA_DBI_get_fields_meta($result);
            $fields_cnt  = count($fields_meta);
        }

        // Display previous update query (from tbl_replace)
        if (isset($disp_query) && $cfg['ShowSQL'] == TRUE) {
            $tmp_sql_query = $GLOBALS['sql_query'];
            $GLOBALS['sql_query'] = $disp_query;
            PMA_showMessage($disp_message);
            $GLOBALS['sql_query'] = $tmp_sql_query;
        }

        // Displays the results in a table
        require_once('./libraries/display_tbl.lib.php');
        if (empty($disp_mode)) {
            // see the "PMA_setDisplayMode()" function in
            // libraries/display_tbl.lib.php
            $disp_mode = 'urdr111101';
        }
        if (!isset($dontlimitchars)) {
            $dontlimitchars = 0;
        }

        // hide edit and delete links for information_schema
        if ( PMA_MYSQL_INT_VERSION >= 50002 && $db == 'information_schema' ) {
            $disp_mode = 'nnnn110111';
        }

        PMA_displayTable($result, $disp_mode, $analyzed_sql);
        PMA_DBI_free_result($result);

        // BEGIN INDEX CHECK See if indexes should be checked.
        if (isset($query_type) && $query_type == 'check_tbl' && isset($selected) && is_array($selected)) {
            foreach($selected AS $idx => $tbl_name) {
                $indexes        = $indexes_info = $indexes_data = array();
                $tbl_ret_keys   = PMA_get_indexes(urldecode($tbl_name), $err_url_0);

                PMA_extract_indexes($tbl_ret_keys, $indexes, $indexes_info, $indexes_data);

                $idx_collection = PMA_show_indexes(urldecode($tbl_name), $indexes, $indexes_info, $indexes_data, false);
                $check          = PMA_check_indexes($idx_collection);
                if (!empty($check)) {
                ?>
<table border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td class="tblHeaders" colspan="7"><?php printf($strIndexWarningTable, urldecode($tbl_name)); ?></td>
    </tr>
    <?php echo $check; ?>
</table>
                <?php
                }
            }
        } // End INDEX CHECK

        if ($disp_mode[6] == '1' || $disp_mode[9] == '1') {
            echo "\n";
            echo '<hr />' . "\n";

            // Displays "Insert a new row" link if required
            if ($disp_mode[6] == '1') {
                $lnk_goto  = 'sql.php?'
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

                echo '    <!-- Insert a new row -->' . "\n";
                echo PMA_linkOrButton(
                    'tbl_change.php' . $url_query,
                    ($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_insrow.png" height="16" width="16" alt="' . $strInsertNewRow . '"/>' : '') . $strInsertNewRow,
                    '', TRUE, TRUE, '') . "\n";

                if ($disp_mode[9] == '1') {
                    echo '&nbsp;&nbsp;';
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
                           . '&amp;sql_query=' . urlencode($sql_query);
                echo '    <!-- Print view -->' . "\n";
                echo PMA_linkOrButton(
                    'sql.php' . $url_query . ((isset($dontlimitchars) && $dontlimitchars == '1') ? '&amp;dontlimitchars=1' : ''),
                    ($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_print.png" height="16" width="16" alt="' . $strPrintView . '"/>' : '') . $strPrintView,
                    '', TRUE, TRUE, 'print_view') . "\n";

                if (!$dontlimitchars) {
                    echo   '    &nbsp;&nbsp;' . "\n";
                    echo PMA_linkOrButton(
                        'sql.php' . $url_query . '&amp;dontlimitchars=1',
                        ($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_print.png" height="16" width="16" alt="' . $strPrintViewFull . '"/>' : '') . $strPrintViewFull,
                        '', TRUE, TRUE, 'print_view') . "\n";
                }
            } // end displays "printable view"

            echo "\n";
        }

        // Export link
        // (the url_query has extra parameters that won't be used to export)
        // (the single_table parameter is used in display_export.lib.php
        //  to hide the SQL and the structure export dialogs)
        if (isset($analyzed_sql[0]) && $analyzed_sql[0]['querytype'] == 'SELECT' && !isset($printview)) {
            if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name']) && !isset($analyzed_sql[0]['table_ref'][1]['table_true_name'])) {
                $single_table   = '&amp;single_table=true';
            } else {
                $single_table   = '';
            }
            echo '    <!-- Export -->' . "\n";
            echo   '    &nbsp;&nbsp;' . "\n";
            echo PMA_linkOrButton(
                'tbl_properties_export.php' . $url_query . '&amp;unlim_num_rows=' . $unlim_num_rows . $single_table,
                ($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_tblexport.png" height="16" width="16" alt="' . $strExport . '" />' : '') . $strExport,
                '', TRUE, TRUE, '') . "\n";
        }

        // Bookmark Support if required
        if ($disp_mode[7] == '1'
            && ( isset( $cfg['Bookmark'] ) && $cfg['Bookmark']['db'] && $cfg['Bookmark']['table'] && empty($id_bookmark))
            && !empty($sql_query)) {
            echo "\n";

            $goto = 'sql.php?'
                  . PMA_generate_common_url($db, $table)
                  . '&amp;pos=' . $pos
                  . '&amp;session_max_rows=' . $session_max_rows
                  . '&amp;disp_direction=' . $disp_direction
                  . '&amp;repeat_cells=' . $repeat_cells
                  . '&amp;dontlimitchars=' . $dontlimitchars
                  . '&amp;sql_query=' . urlencode($sql_query)
                  . '&amp;id_bookmark=1';

            if ($disp_mode[3] == '1') {
                echo '    <i>' . $strOr . '</i>';
            } else {
                echo '<br /><br />';
            }
            ?>
<form action="sql.php" method="post" onsubmit="return emptyFormElements(this, 'fields[label]');">
<?php echo PMA_generate_common_hidden_inputs(); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="fields[dbase]" value="<?php echo htmlspecialchars($db); ?>" />
<input type="hidden" name="fields[user]" value="<?php echo $cfg['Bookmark']['user']; ?>" />
<input type="hidden" name="fields[query]" value="<?php echo urlencode(isset($complete_query) ? $complete_query : $sql_query); ?>" />
<fieldset>
    <legend><?php
     echo ($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_bookmark.png" width="16" height="16" alt="' . $strBookmarkThis . '" />' : '')
        . $strBookmarkThis;
?>
    </legend>

    <div class="formelement">
        <label for="fields_label_"><?php echo $strBookmarkLabel; ?>:</label>
        <input type="text" id="fields_label_" name="fields[label]" value="" />
    </div>

    <div class="formelement">
        <input type="checkbox" name="bkm_all_users" id="bkm_all_users" value="true" />
        <label for="bkm_all_users"><?php echo $strBookmarkAllUsers; ?></label>
    </div>
    
    <div class="clearfloat"></div>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="store_bkm" value="<?php echo $strBookmarkThis; ?>" />
</fieldset>
</form>
            <?php
        } // end bookmark support

        // Do print the page if required
        if (isset($printview) && $printview == '1') {
            echo "\n";
            ?>
<script type="text/javascript" language="javascript">
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
require_once('./libraries/footer.inc.php');
?>
