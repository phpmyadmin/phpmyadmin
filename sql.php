<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');


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

// This one is just to fill $db
if (isset($fields['dbase'])) {
    $db = $fields['dbase'];
}

// Now we can check the parameters
PMA_checkParameters(array('sql_query', 'db'));


/**
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if (!defined('PMA_CHK_DROP')
    && !$cfg['AllowUserDropDatabase']
    && preg_match('@DROP[[:space:]]+DATABASE[[:space:]]+@i', $sql_query)) {
    // Checks if the user is a Superuser
    // TODO: set a global variable with this information
    // loic1: optimized query
    $result = @PMA_mysql_query('USE mysql');
    if (PMA_mysql_error()) {
        require_once('./header.inc.php');
        PMA_mysqlDie($strNoDropDatabases, '', '', $err_url);
    } // end if
} // end if


/**
 * Bookmark add
 */
if (isset($store_bkm)) {
    require_once('./libraries/bookmark.lib.php');
    PMA_addBookmarks($fields, $cfg['Bookmark'], (isset($bkm_all_users) && $bkm_all_users == 'true' ? true : false));
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

$GLOBALS['unparsed_sql'] = $sql_query;
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
if ($goto == 'sql.php') {
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
        require('./' . preg_replace('@\.\.*@', '.', $goto));
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

    // if we are coming from a "Create PHP code" or a "Without PHP Code"
    // dialog, we won't execute the query anyway, so don't confirm
    //|| !empty($GLOBALS['show_as_php'])
    || isset($GLOBALS['show_as_php'])

    || !empty($GLOBALS['validatequery'])) {
    $do_confirm = FALSE;
} else {
    //$do_confirm = (eregi('DROP[[:space:]]+(IF[[:space:]]+EXISTS[[:space:]]+)?(TABLE|DATABASE[[:space:]])|ALTER[[:space:]]+TABLE[[:space:]]+((`[^`]+`)|([A-Za-z0-9_$]+))[[:space:]]+DROP[[:space:]]|DELETE[[:space:]]+FROM[[:space:]]', $sql_query));

    $do_confirm = isset($analyzed_sql[0]['queryflags']['need_confirm']);
}

if ($do_confirm) {
    $stripped_sql_query = $sql_query;
    require_once('./header.inc.php');
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . htmlspecialchars($stripped_sql_query) . '</tt>&nbsp;?<br/>' . "\n";
    ?>
<form action="sql.php" method="post">
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
        && preg_match('@^CREATE TABLE[[:space:]]+(.*)@i', $sql_query)) {
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
        && !preg_match('@[[:space:]]LIMIT[[:space:]0-9,-]+$@i', $sql_query)) {
        $sql_limit_to_append = " LIMIT $pos, ".$cfg['MaxRows'];
        if (preg_match('@(.*)([[:space:]](PROCEDURE[[:space:]](.*)|FOR[[:space:]]+UPDATE|LOCK[[:space:]]+IN[[:space:]]+SHARE[[:space:]]+MODE))$@i', $sql_query, $regs)) {
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
        && preg_match('@^DELETE([[:space:]].+)?([[:space:]]FROM[[:space:]](.+))$@i', $sql_query, $parts)
        && !preg_match('@[[:space:]]WHERE[[:space:]]@i', $parts[3])) {
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
            require_once('./header.inc.php');
            $full_err_url = (preg_match('@^(db_details|tbl_properties)@', $err_url))
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
        if (is_array($row) && isset($row['db']) && $db != $row['db']) {
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

                if (!$is_group
                 && !isset($analyzed_sql[0]['queryflags']['union'])
                 && !isset($analyzed_sql[0]['table_ref'][1]['table_name'])
                 && (empty($analyzed_sql[0]['where_clause'])
                   || $analyzed_sql[0]['where_clause'] == '1 ')) {

                    // "j u s t   b r o w s i n g"
                    $unlim_num_rows = PMA_countRecords($db, $table, TRUE);

                } else { // n o t   " j u s t   b r o w s i n g "

                    if (PMA_MYSQL_INT_VERSION < 40000) {
                        // TODO: detect DISTINCT in the parser
                        if (stristr($sql_query, 'DISTINCT')) {
                            $count_what = 'DISTINCT ' . $analyzed_sql[0]['select_expr_clause'];
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
//                        if (eregi('DISTINCT(.*)', $sql_query)) {
//                            $count_query .= 'DISTINCT ' . $analyzed_sql[0]['select_expr_clause'];
//                        } else {
                            //$count_query .= $analyzed_sql[0]['select_expr_clause'];

                            // for UNION, just adding SQL_CALC_FOUND_ROWS
                            // after the first SELECT works.

                            // take the left part, could be:
                            // SELECT
                            // (SELECT
                            $count_query = PMA_SQP_formatHtml($parsed_sql, 'query_only', 0, $analyzed_sql[0]['position_of_first_select'] + 1);
                            $count_query .= ' SQL_CALC_FOUND_ROWS ';

                            // add everything that was after the first SELECT
                            $count_query .= PMA_SQP_formatHtml($parsed_sql, 'query_only', $analyzed_sql[0]['position_of_first_select']+1);
//                        }
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
//DEBUG echo "trace cq=" . $count_query . "<br/>";

                    if (PMA_MYSQL_INT_VERSION < 40000) {
                        if ($cnt_all_result = PMA_mysql_query($count_query)) {
                            if ($is_group && $count_what == '*') {
                                $unlim_num_rows = @mysql_num_rows($cnt_all_result);
                            } else {
                                $unlim_num_rows = PMA_mysql_result($cnt_all_result, 0, 'count');
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
                        PMA_mysql_query($count_query);
                        // if (mysql_error()) {
                        // void. I tried the case
                        // (SELECT `User`, `Host`, `Db`, `Select_priv` FROM `db`)
                        // UNION (SELECT `User`, `Host`, "%" AS "Db",
                        // `Select_priv`
                        // FROM `user`) ORDER BY `User`, `Host`, `Db`;
                        // and although the generated count_query is wrong
                        // the SELECT FOUND_ROWS() work!
                        // }
                        $cnt_all_result = PMA_mysql_query('SELECT FOUND_ROWS() as count');
                        $unlim_num_rows = PMA_mysql_result($cnt_all_result,0,'count');
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
            $goto = preg_replace('@\.\.*@', '.', $goto);
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
                    $goto     = 'db_details.php';
                } else {
                    $is_table = @PMA_mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
                    if (!($is_table && @mysql_numrows($is_table))) {
                        $goto = 'db_details.php';
                        unset($table);
                    }
                } // end if... else...
            }
            if (strpos(' ' . $goto, 'db_details') == 1) {
                if (isset($table)) {
                    unset($table);
                }
                if (!isset($db)) {
                    $goto     = 'main.php';
                } else {
                    $is_db    = @PMA_mysql_select_db($db);
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
                require_once('./header.inc.php');
            }
            $active_page = $goto;
            require('./' . $goto);
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
            require_once('./header_printview.inc.php');
        } else {
            $js_to_run = 'functions.js';
            unset($message);
            if (!empty($table)) {
                require('./tbl_properties_common.php');
                $url_query .= '&amp;goto=tbl_properties.php&amp;back=tbl_properties.php';
                require('./tbl_properties_table_info.php');
            }
            else {
                require('./db_details_common.php');
                require('./db_details_db_info.php');
            }
        }

        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();

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
        require_once('./libraries/display_tbl.lib.php');
        if (empty($disp_mode)) {
            // see the "PMA_setDisplayMode()" function in
            // libraries/display_tbl.lib.php
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

                echo '    <!-- Insert a new row -->' . "\n"
                   . '    <a href="tbl_change.php' . $url_query . '">' . $strInsertNewRow . '</a>';
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
                           . '&amp;sql_query=' . urlencode($sql_query);
                echo '    <!-- Print view -->' . "\n"
                   . '    <a href="sql.php' . $url_query
                   . ((isset($dontlimitchars) && $dontlimitchars == '1') ? '&amp;dontlimitchars=1' : '')
                   . '" target="print_view">' . $strPrintView . '</a>' . "\n";
                if (!$dontlimitchars) {
                   echo   '    <br />' . "\n"
                        . '    <a href="sql.php' . $url_query
                        . '&amp;dontlimitchars=1'
                        . '" target="print_view">' . $strPrintViewFull . '</a>' . "\n";
                }
            } // end displays "printable view"

            echo '</p>' . "\n";
        }

        // Export link, if only one table
        // (the url_query has extra parameters that won't be used to export)
        if (!isset($printview)) {
            if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name']) && !isset($analyzed_sql[0]['table_ref'][1]['table_true_name'])) {
                $single_table   = '&amp;single_table=true';
            } else {
                $single_table   = '';
            }
            echo '    <!-- Export -->' . "\n"
                   . '    <a href="tbl_properties_export.php' . $url_query
                   . '&amp;unlim_num_rows=' . $unlim_num_rows
                   . $single_table
                   . '">' . $strExport . '</a>' . "\n";
        }

        // Bookmark Support if required
        if ($disp_mode[7] == '1'
            && ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table'] && empty($id_bookmark))
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
            ?>
<!-- Bookmark the query -->
<form action="sql.php" method="post" onsubmit="return emptyFormElements(this, 'fields[label]');">
            <?php
            echo "\n";
            if ($disp_mode[3] == '1') {
                echo '    <i>' . $strOr . '</i>' . "\n";
            }
            ?>
    <br /><br />
    <?php echo $strBookmarkLabel; ?>:
    <?php echo PMA_generate_common_hidden_inputs(); ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="fields[dbase]" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="fields[user]" value="<?php echo $cfg['Bookmark']['user']; ?>" />
    <input type="hidden" name="fields[query]" value="<?php echo urlencode(isset($complete_query) ? $complete_query : $sql_query); ?>" />
    <input type="text" name="fields[label]" value="" />
    <input type="checkbox" name="bkm_all_users" id="bkm_all_users" value="true" /><label for="bkm_all_users"><?php echo $strBookmarkAllUsers; ?></label>
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
require_once('./footer.inc.php');
?>
