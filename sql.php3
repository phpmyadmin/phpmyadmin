<?php
/* $Id$ */


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
             . '?lang=' . $lang
             . '&amp;convcharset=' . $convcharset
             . '&amp;server=' . $server
             . (isset($db) ? '&amp;db=' . urlencode($db) : '')
             . ((strpos(' ' . $goto, 'db_details') != 1 && isset($table)) ? '&amp;table=' . urlencode($table) : '');
} // end if


/**
 * SK -- Patch
 *
 * Does some preliminary formatting of the $sql_query to avoid problems with
 * eregi and split:
 *   1) separates reserved words in $sql_str from the next backquoted or
 *      parenthesized expression with a space;
 *   2) capitalizes reserved words
 *   3) removes repeated spaces
 *
 * @param   string  original query
 *
 * @return  string  formatted query
 */
function PMA_sqlFormat($sql_str) {
    // Defines reserved words to deal with
    $res_words_arr = array('DROP', 'SELECT', 'DELETE', 'UPDATE', 'INSERT', 'LOAD', 'EXPLAIN', 'SHOW', 'FROM', 'INTO', 'OUTFILE', 'DATA', 'REPLACE', 'CHECK', 'ANALYZE', 'REPAIR', 'OPTIMIZE', 'TABLE', 'ORDER', 'HAVING', 'LIMIT', 'GROUP', 'DISTINCT');

    while (list(, $w) = each($res_words_arr)) {
        // Separates a backquoted expression with spaces
        $pattern = '[[:space:]]' . $w . '`([^`]*)`(.*)';
        $replace = ' ' . $w . ' `\\1` \\2';
        $sql_str = substr(eregi_replace($pattern, $replace, ' ' . $sql_str), 1);

        // Separates a parenthesized expression with spaces
        $pattern = '[[:space:]]' . $w . '\(([^)]*)\)(.*)';
        $replace = ' ' . $w . ' (\\1) \\2';
        $sql_str = substr(eregi_replace($pattern, $replace, ' ' . $sql_str), 1);

        // Converts reservered words to upper case if not yet done
        $sql_str = substr(eregi_replace('[[:space:]]' . $w . '[[:space:]]', ' ' . $w  . ' ', ' ' . $sql_str), 1);
    } // end while

    // Removes repeated spaces
    $sql_str = ereg_replace('[[:space:]]+', ' ', $sql_str);

    // GROUP or ORDER: "BY" to uppercase too
    $sql_str = eregi_replace('(GROUP|ORDER) BY', '\\1 BY', $sql_str);

    return $sql_str;
} // end of the "PMA_sqlFormat()" function


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
    if (get_magic_quotes_gpc()) {
        $fields['label'] = stripslashes($fields['label']);
    }
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

// SK -- Patch : Reformats query - adds spaces when omitted and removes extra
//               spaces; converts reserved words to uppercase
$sql_query = PMA_sqlFormat($sql_query);


// If the query is a Select, extract the db and table names and modify
// $db and $table, to have correct page headers, links and left frame.
// db and table name may be enclosed with backquotes, db is optionnal,
// query may contain aliases.
// (todo: check for embedded comments...)

// (todo: if there are more than one table name in the Select:
// - do not extract the first table name
// - do not show a table name in the page header
// - do not display the sub-pages links)

$is_select = eregi('^SELECT[[:space:]]+', $sql_query);
if ($is_select) {
    eregi('^SELECT[[:space:]]+(.*)[[:space:]]+FROM[[:space:]]+(`[^`]+`|[A-Za-z0-9_$]+)([\.]*)(`[^`]*`|[A-Za-z0-9_$]*)', $sql_query, $tmp);

    if ($tmp[3] == '.') {
        $prev_db = $db;
        $db      = str_replace('`', '', $tmp[2]);
        $reload  = ($db == $prev_db) ? 0 : 1;
        $table   = str_replace('`', '', $tmp[4]);
    }
    else {
        $table   = str_replace('`', '', $tmp[2]);
    }
} // end if


/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php3') {
    $goto = 'sql.php3'
          . '?lang=' . $lang
          . '&amp;convcharset=' . $convcharset
          . '&amp;server=' . $server
          . '&amp;db=' . urlencode($db)
          . '&amp;table=' . urlencode($table)
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
     /* SQL-Parser-Analyzer */
    $do_confirm = (eregi('DROP[[:space:]]+(IF[[:space:]]+EXISTS[[:space:]]+)?(TABLE|DATABASE[[:space:]])|ALTER[[:space:]]+TABLE[[:space:]]+((`[^`]+`)|([A-Za-z0-9_$]+))[[:space:]]+DROP[[:space:]]|DELETE[[:space:]]+FROM[[:space:]]', $sql_query));
}

if ($do_confirm) {
    if (get_magic_quotes_gpc()) {
        $stripped_sql_query = stripslashes($sql_query);
    } else {
        $stripped_sql_query = $sql_query;
    }
    include('./header.inc.php3');
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . htmlspecialchars($stripped_sql_query) . '</tt>&nbsp;?<br/>' . "\n";
    ?>
<form action="sql.php3" method="post">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo isset($table) ? $table : ''; ?>" />
    <input type="hidden" name="sql_query" value="<?php echo urlencode($sql_query); ?>" />
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? $zero_rows : ''; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="<?php echo isset($back) ? $back : ''; ?>" />
    <input type="hidden" name="reload" value="<?php echo isset($reload) ? $reload : 0; ?>" />
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
    } else if (get_magic_quotes_gpc()) {
        $sql_query = stripslashes($sql_query);
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
        $is_func =  !$is_group && (eregi('[[:space:]]+(SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND)\s*\(', $sql_query));
        $is_group = eregi('[[:space:]]+(GROUP BY|HAVING|SELECT[[:space:]]+DISTINCT)[[:space:]]+', $sql_query);
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
        && $is_select
        && !($is_count || $is_export || $is_func || $is_analyse)
        && eregi('[[:space:]]FROM[[:space:]]', $sql_query)
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
    // Executes the query
    // Only if we didn't ask to see the php code (mikebeck)
    if (!empty($GLOBALS['show_as_php']) || !empty($GLOBALS['validatequery'])) {
        unset($result);
        $num_rows = 0;
    }
    else {
        $result   = @PMA_mysql_query($full_sql_query);
        // Displays an error message if required and stop parsing the script
        if (PMA_mysql_error()) {
            $error        = PMA_mysql_error();
            include('./header.inc.php3');
            $full_err_url = (ereg('^(db_details|tbl_properties)', $err_url))
                          ? $err_url . '&amp;show_query=1&amp;sql_query=' . urlencode($sql_query)
                          : $err_url;
            PMA_mysqlDie($error, $full_sql_query, '', $full_err_url);
        }

        // tmpfile remove after convert encoding appended by Y.Kawada
        if (function_exists('PMA_kanji_file_conv')
            && (isset($textfile) && file_exists($textfile))) {
            unlink($textfile);
        }

        // Gets the number of rows affected/returned
        if (!$is_affected) {
            $num_rows = ($result) ? @mysql_num_rows($result) : 0;
        } else if (!isset($num_rows)) {
            $num_rows = @mysql_affected_rows();
        }

        // Counts the total number of rows for the same 'SELECT' query without the
        // 'LIMIT' clause that may have been programatically added
        if (empty($sql_limit_to_append)) {
            $unlim_num_rows         = $num_rows;
        }
        else if ($is_select) {
        // SK -- Patch : correct calculations for GROUP BY, HAVING, DISTINCT

        // Reads only the from-part of the query...
        // NOTE: here the presence of LIMIT is impossible, HAVING and GROUP BY
        // are necessary for correct calculation, and extra spaces and
        // lowercase reserved words are removed, so we have a simple split
        // pattern:

            $array = split('[[:space:]]+(FROM|ORDER BY)[[:space:]]+', $sql_query);

        // if $array[1] is empty here, there is an error in the query:
        // "... FROM [ORDER BY ...]", but the query is already executed with
        // success so this check is redundant???

            if (!empty($array[1])) {
            // ... and makes a count(*) to count the entries
            // Special case: SELECT DISTINCT ... FROM ...
            // the count of resulting rows can be found as:
            // SELECT COUNT(DISTINCT ...) FROM ...
                if (eregi('^SELECT DISTINCT(.*)', $array[0], $array_dist)) {
                    $count_what = 'DISTINCT ' . $array_dist[1];
                } else {
                    $count_what = '*';
                }
                $count_query = 'SELECT COUNT(' . $count_what . ') AS count FROM ' . $array[1];
                if ($cnt_all_result = mysql_query($count_query)) {
                    if ($is_group) {
                        $unlim_num_rows = @mysql_num_rows($cnt_all_result);
                    } else {
                        $unlim_num_rows = mysql_result($cnt_all_result, 0, 'count');
                    }
                    mysql_free_result($cnt_all_result);
                }
            } else {
                $unlim_num_rows         = 0;
            }
        } // end rows total count
    } // end else "didn't ask to see php code"

    // No rows returned -> move back to the calling page
    if ($num_rows < 1 || $is_affected) {
        if ($is_delete) {
            $message = $strDeletedRows . '&nbsp;' . $num_rows;
        } else if ($is_insert) {
            $message = $strInsertedRows . '&nbsp;' . $num_rows;
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
            if (strpos(' ' . $goto, 'db_details.php3') == 1) {
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
        // Displays the results in a table
        include('./libraries/display_tbl.lib.php3');
        if (empty($disp_mode)) {
            // see the "PMA_setDisplayMode()" function in
            // libraries/display_tbl.lib.php3
            $disp_mode = 'urdr111101';
        }
        PMA_displayTable($result, $disp_mode);
        mysql_free_result($result);

        if ($disp_mode[6] == '1' || $disp_mode[9] == '1') {
            echo "\n";
            echo '<p>' . "\n";

            // Displays "Insert a new row" link if required
            if ($disp_mode[6] == '1') {
                $lnk_goto  = 'sql.php3'
                           . '?lang=' . $lang
                           . '&amp;convcharset=' . $convcharset
                           . '&amp;server=' . $server
                           . '&amp;db=' . urlencode($db)
                           . '&amp;table=' . urlencode($table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
                           . '&amp;sql_query=' . urlencode($sql_query);
                $url_query = '?lang=' . $lang
                           . '&amp;convcharset=' . $convcharset
                           . '&amp;server=' . $server
                           . '&amp;db=' . urlencode($db)
                           . '&amp;table=' . urlencode($table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
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
                $url_query = '?lang=' . $lang
                           . '&amp;convcharset=' . $convcharset
                           . '&amp;server=' . $server
                           . '&amp;db=' . urlencode($db)
                           . '&amp;table=' . urlencode($table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
                           . '&amp;printview=1'
                           . (($dontlimitchars == '1') ? '&amp;dontlimitchars=1' : '')
                           . '&amp;sql_query=' . urlencode($sql_query);
                echo '    <!-- Print view -->' . "\n"
                   . '    <a href="sql.php3' . $url_query . '" target="print_view">' . $strPrintView . '</a>' . "\n";
            } // end displays "printable view"

            echo '</p>' . "\n";
        }

        // Bookmark Support if required
        if ($disp_mode[7] == '1'
            && ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table'] && empty($id_bookmark))
            && !empty($sql_query)) {
            echo "\n";

            $goto = 'sql.php3'
                  . '?lang=' . $lang
                  . '&amp;convcharset=' . $convcharset
                  . '&amp;server=' . $server
                  . '&amp;db=' . urlencode($db)
                  . '&amp;table=' . urlencode($table)
                  . '&amp;pos=' . $pos
                  . '&amp;session_max_rows=' . $session_max_rows
                  . '&amp;disp_direction=' . $disp_direction
                  . '&amp;repeat_cells=' . $repeat_cells
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
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="fields[dbase]" value="<?php echo $db; ?>" />
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
