<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @todo    we must handle the case if sql.php is called directly with a query
 *          that returns 0 rows - to prevent cyclic redirects or includes
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';
require_once './libraries/check_user_privileges.lib.php';
require_once './libraries/bookmark.lib.php';

$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'tbl_change.js';
// the next one needed because sql.php may do a "goto" to tbl_structure.php
$GLOBALS['js_include'][] = 'tbl_structure.js';
$GLOBALS['js_include'][] = 'indexes.js';
$GLOBALS['js_include'][] = 'gis_data_editor.js';
$GLOBALS['js_include'][] = 'codemirror/lib/codemirror.js';
$GLOBALS['js_include'][] = 'codemirror/mode/mysql/mysql.js';



if (isset($_SESSION['profiling'])) {
    /* < IE 9 doesn't support canvas natively */
    if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
        $GLOBALS['js_include'][] = 'canvg/flashcanvas.js';
    }
    $GLOBALS['js_include'][] = 'jqplot/jquery.jqplot.js';
    $GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.pieRenderer.js';
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
// Security checkings
if (! empty($goto)) {
    $is_gotofile     = preg_replace('@^([^?]+).*$@s', '\\1', $goto);
    if (! @file_exists('./' . $is_gotofile)) {
        unset($goto);
    } else {
        $is_gotofile = ($is_gotofile == $goto);
    }
} else {
    $goto = (! strlen($table)) ? $cfg['DefaultTabDatabase'] : $cfg['DefaultTabTable'];
    $is_gotofile  = true;
} // end if

if (! isset($err_url)) {
    $err_url = (! empty($back) ? $back : $goto)
             . '?' . PMA_generate_common_url($db)
             . ((strpos(' ' . $goto, 'db_') != 1 && strlen($table)) ? '&amp;table=' . urlencode($table) : '');
} // end if

// Coming from a bookmark dialog
if (isset($fields['query'])) {
    $sql_query = $fields['query'];
}

// This one is just to fill $db
if (isset($fields['dbase'])) {
    $db = $fields['dbase'];
}

/**
 * During grid edit, if we have a relational field, show the dropdown for it
 *
 * Logic taken from libraries/display_tbl_lib.php
 *
 * This doesn't seem to be the right place to do this, but I can't think of any
 * better place either.
 */
if (isset($_REQUEST['get_relational_values']) && $_REQUEST['get_relational_values'] == true) {
    include_once 'libraries/relation.lib.php';

    $column = $_REQUEST['column'];
    $foreigners = PMA_getForeigners($db, $table, $column);

    $display_field = PMA_getDisplayField($foreigners[$column]['foreign_db'], $foreigners[$column]['foreign_table']);

    $foreignData = PMA_getForeignData($foreigners, $column, false, '', '');

    if ($_SESSION['tmp_user_values']['relational_display'] == 'D'
        && isset($display_field)
        && strlen($display_field)
        && isset($_REQUEST['relation_key_or_display_column'])
        && $_REQUEST['relation_key_or_display_column']
    ) {
            $curr_value = $_REQUEST['relation_key_or_display_column'];
    } else {
        $curr_value = $_REQUEST['curr_value'];
    }
    if ($foreignData['disp_row'] == null) {
        //Handle the case when number of values is more than $cfg['ForeignKeyMaxLimit']
        $_url_params = array(
                'db' => $db,
                'table' => $table,
                'field' => $column
        );

        $dropdown = '<span class="curr_value">' . htmlspecialchars($_REQUEST['curr_value']) . '</span> <a href="browse_foreigners.php' . PMA_generate_common_url($_url_params) . '"'
                    . ' target="_blank" class="browse_foreign" '
                    .'>' . __('Browse foreign values') . '</a>';
    } else {
        $dropdown = PMA_foreignDropdown($foreignData['disp_row'], $foreignData['foreign_field'], $foreignData['foreign_display'], $curr_value, $cfg['ForeignKeyMaxLimit']);
        $dropdown = '<select>' . $dropdown . '</select>';
    }

    $extra_data['dropdown'] = $dropdown;
    PMA_ajaxResponse(null, true, $extra_data);
}

/**
 * Just like above, find possible values for enum fields during grid edit.
 *
 * Logic taken from libraries/display_tbl_lib.php
 */
if (isset($_REQUEST['get_enum_values']) && $_REQUEST['get_enum_values'] == true) {
    $field_info_query = PMA_DBI_get_columns_sql($db, $table, $_REQUEST['column']);

    $field_info_result = PMA_DBI_fetch_result($field_info_query, null, null, null, PMA_DBI_QUERY_STORE);

    $values = PMA_parseEnumSetValues($field_info_result[0]['Type']);

    $dropdown = '<option value="">&nbsp;</option>';
    foreach ($values as $value) {
        $dropdown .= '<option value="' . $value . '"';
        if ($value == $_REQUEST['curr_value']) {
            $dropdown .= ' selected="selected"';
        }
        $dropdown .= '>' . $value . '</option>';
    }

    $dropdown = '<select>' . $dropdown . '</select>';

    $extra_data['dropdown'] = $dropdown;
    PMA_ajaxResponse(null, true, $extra_data);
}

/**
 * Find possible values for set fields during grid edit.
 */
if (isset($_REQUEST['get_set_values']) && $_REQUEST['get_set_values'] == true) {
    $field_info_query = PMA_DBI_get_columns_sql($db, $table, $_REQUEST['column']);

    $field_info_result = PMA_DBI_fetch_result($field_info_query, null, null, null, PMA_DBI_QUERY_STORE);

    $selected_values = explode(',', $_REQUEST['curr_value']);

    $values = PMA_parseEnumSetValues($field_info_result[0]['Type']);

    $select = '';
    foreach ($values as $value) {
        $select .= '<option value="' . $value . '"';
        if (in_array($value, $selected_values, true)) {
            $select .= ' selected="selected"';
        }
        $select .= '>' . $value . '</option>';
    }

    $select_size = (sizeof($values) > 10) ? 10 : sizeof($values);
    $select = '<select multiple="multiple" size="' . $select_size . '">' . $select . '</select>';

    $extra_data['select'] = $select;
    PMA_ajaxResponse(null, true, $extra_data);
}

/**
 * Check ajax request to set the column order
 */
if (isset($_REQUEST['set_col_prefs']) && $_REQUEST['set_col_prefs'] == true) {
    $pmatable = new PMA_Table($table, $db);
    $retval = false;

    // set column order
    if (isset($_REQUEST['col_order'])) {
        $col_order = explode(',', $_REQUEST['col_order']);
        $retval = $pmatable->setUiProp(PMA_Table::PROP_COLUMN_ORDER, $col_order, $_REQUEST['table_create_time']);
        if (gettype($retval) != 'boolean') {
            PMA_ajaxResponse($retval->getString(), false);
        }
    }

    // set column visibility
    if ($retval === true && isset($_REQUEST['col_visib'])) {
        $col_visib = explode(',', $_REQUEST['col_visib']);
        $retval = $pmatable->setUiProp(PMA_Table::PROP_COLUMN_VISIB, $col_visib, $_REQUEST['table_create_time']);
        if (gettype($retval) != 'boolean') {
            PMA_ajaxResponse($retval->getString(), false);
        }
    }

    PMA_ajaxResponse(null, ($retval == true));
}

// Default to browse if no query set and we have table
// (needed for browsing from DefaultTabTable)
if (empty($sql_query) && strlen($table) && strlen($db)) {
    include_once './libraries/bookmark.lib.php';
    $book_sql_query = PMA_Bookmark_get(
        $db,
        '\'' . PMA_sqlAddSlashes($table) . '\'',
        'label',
        false,
        true
    );

    if (! empty($book_sql_query)) {
        $GLOBALS['using_bookmark_message'] = PMA_message::notice(__('Using bookmark "%s" as default browse query.'));
        $GLOBALS['using_bookmark_message']->addParam($table);
        $GLOBALS['using_bookmark_message']->addMessage(PMA_showDocu('faq6_22'));
        $sql_query = $book_sql_query;
    } else {
        $sql_query = 'SELECT * FROM ' . PMA_backquote($table);
    }
    unset($book_sql_query);

    // set $goto to what will be displayed if query returns 0 rows
    $goto = 'tbl_structure.php';
} else {
    // Now we can check the parameters
    PMA_checkParameters(array('sql_query'));
}

// instead of doing the test twice
$is_drop_database = preg_match(
    '/DROP[[:space:]]+(DATABASE|SCHEMA)[[:space:]]+/i',
    $sql_query
);

/**
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if (! defined('PMA_CHK_DROP')
    && ! $cfg['AllowUserDropDatabase']
    && $is_drop_database
    && ! $is_superuser
) {
    include_once './libraries/header.inc.php';
    PMA_mysqlDie(__('"DROP DATABASE" statements are disabled.'), '', '', $err_url);
} // end if

require_once './libraries/display_tbl.lib.php';
PMA_displayTable_checkConfigParams();

/**
 * Need to find the real end of rows?
 */
if (isset($find_real_end) && $find_real_end) {
    $unlim_num_rows = PMA_Table::countRecords($db, $table, $force_exact = true);
    $_SESSION['tmp_user_values']['pos'] = @((ceil($unlim_num_rows / $_SESSION['tmp_user_values']['max_rows']) - 1) * $_SESSION['tmp_user_values']['max_rows']);
}


/**
 * Bookmark add
 */
if (isset($store_bkm)) {
    PMA_Bookmark_save($fields, (isset($bkm_all_users) && $bkm_all_users == 'true' ? true : false));
    // go back to sql.php to redisplay query; do not use &amp; in this case:
    PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . $goto . '&label=' . $fields['label']);
} // end if

/**
 * Parse and analyze the query
 */
require_once './libraries/parse_analyze.lib.php';

/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php') {
    $is_gotofile = false;
    $goto = 'sql.php?'
          . PMA_generate_common_url($db, $table)
          . '&amp;sql_query=' . urlencode($sql_query);
} // end if


/**
 * Go back to further page if table should not be dropped
 */
if (isset($btnDrop) && $btnDrop == __('No')) {
    if (! empty($back)) {
        $goto = $back;
    }
    if ($is_gotofile) {
        if (strpos($goto, 'db_') === 0 && strlen($table)) {
            $table = '';
        }
        $active_page = $goto;
        include './' . PMA_securePath($goto);
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
if (! $cfg['Confirm']
    || isset($_REQUEST['is_js_confirmed'])
    || isset($btnDrop)
    // if we are coming from a "Create PHP code" or a "Without PHP Code"
    // dialog, we won't execute the query anyway, so don't confirm
    || isset($GLOBALS['show_as_php'])
    || ! empty($GLOBALS['validatequery'])
) {
    $do_confirm = false;
} else {
    $do_confirm = isset($analyzed_sql[0]['queryflags']['need_confirm']);
}

if ($do_confirm) {
    $stripped_sql_query = $sql_query;
    include_once './libraries/header.inc.php';
    if ($is_drop_database) {
        echo '<h1 class="error">' . __('You are about to DESTROY a complete database!') . '</h1>';
    }
    echo '<form action="sql.php" method="post">' . "\n"
        .PMA_generate_common_hidden_inputs($db, $table);
    ?>
    <input type="hidden" name="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />
    <input type="hidden" name="message_to_show" value="<?php echo isset($message_to_show) ? PMA_sanitize($message_to_show, true) : ''; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="<?php echo isset($back) ? PMA_sanitize($back, true) : ''; ?>" />
    <input type="hidden" name="reload" value="<?php echo isset($reload) ? PMA_sanitize($reload, true) : 0; ?>" />
    <input type="hidden" name="purge" value="<?php echo isset($purge) ? PMA_sanitize($purge, true) : ''; ?>" />
    <input type="hidden" name="dropped_column" value="<?php echo isset($dropped_column) ? PMA_sanitize($dropped_column, true) : ''; ?>" />
    <input type="hidden" name="show_query" value="<?php echo isset($show_query) ? PMA_sanitize($show_query, true) : ''; ?>" />
    <?php
    echo '<fieldset class="confirmation">' . "\n"
        .'    <legend>' . __('Do you really want to ') . '</legend>'
        .'    <tt>' . htmlspecialchars($stripped_sql_query) . '</tt>' . "\n"
        .'</fieldset>' . "\n"
        .'<fieldset class="tblFooters">' . "\n";
    ?>
    <input type="submit" name="btnDrop" value="<?php echo __('Yes'); ?>" id="buttonYes" />
    <input type="submit" name="btnDrop" value="<?php echo __('No'); ?>" id="buttonNo" />
    <?php
    echo '</fieldset>' . "\n"
       . '</form>' . "\n";

    /**
     * Displays the footer and exit
     */
    include './libraries/footer.inc.php';
} // end if $do_confirm


// Defines some variables
// A table has to be created, renamed, dropped -> navi frame should be reloaded
/**
 * @todo use the parser/analyzer
 */

if (empty($reload)
    && preg_match('/^(CREATE|ALTER|DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $sql_query)
) {
    $reload = 1;
}

// SK -- Patch: $is_group added for use in calculation of total number of
//              rows.
//              $is_count is changed for more correct "LIMIT" clause
//              appending in queries like
//                "SELECT COUNT(...) FROM ... GROUP BY ..."

/**
 * @todo detect all this with the parser, to avoid problems finding
 * those strings in comments or backquoted identifiers
 */

$is_explain = $is_count = $is_export = $is_delete = $is_insert = $is_affected = $is_show = $is_maint = $is_analyse = $is_group = $is_func = $is_replace = false;
if ($is_select) { // see line 141
    $is_group = preg_match('@(GROUP[[:space:]]+BY|HAVING|SELECT[[:space:]]+DISTINCT)[[:space:]]+@i', $sql_query);
    $is_func =  ! $is_group && (preg_match('@[[:space:]]+(SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND)\s*\(@i', $sql_query));
    $is_count = ! $is_group && (preg_match('@^SELECT[[:space:]]+COUNT\((.*\.+)?.*\)@i', $sql_query));
    $is_export   = (preg_match('@[[:space:]]+INTO[[:space:]]+OUTFILE[[:space:]]+@i', $sql_query));
    $is_analyse  = (preg_match('@[[:space:]]+PROCEDURE[[:space:]]+ANALYSE@i', $sql_query));
} elseif (preg_match('@^EXPLAIN[[:space:]]+@i', $sql_query)) {
    $is_explain  = true;
} elseif (preg_match('@^DELETE[[:space:]]+@i', $sql_query)) {
    $is_delete   = true;
    $is_affected = true;
} elseif (preg_match('@^(INSERT|LOAD[[:space:]]+DATA|REPLACE)[[:space:]]+@i', $sql_query)) {
    $is_insert   = true;
    $is_affected = true;
    if (preg_match('@^(REPLACE)[[:space:]]+@i', $sql_query)) {
        $is_replace = true;
    }
} elseif (preg_match('@^UPDATE[[:space:]]+@i', $sql_query)) {
    $is_affected = true;
} elseif (preg_match('@^[[:space:]]*SHOW[[:space:]]+@i', $sql_query)) {
    $is_show     = true;
} elseif (preg_match('@^(CHECK|ANALYZE|REPAIR|OPTIMIZE)[[:space:]]+TABLE[[:space:]]+@i', $sql_query)) {
    $is_maint    = true;
}

// assign default full_sql_query
$full_sql_query = $sql_query;

// Handle remembered sorting order, only for single table query
if ($GLOBALS['cfg']['RememberSorting']
    && ! ($is_count || $is_export || $is_func || $is_analyse)
    && count($analyzed_sql[0]['select_expr']) == 0
    && isset($analyzed_sql[0]['queryflags']['select_from'])
    && count($analyzed_sql[0]['table_ref']) == 1
) {
    $pmatable = new PMA_Table($table, $db);
    if (empty($analyzed_sql[0]['order_by_clause'])) {
        $sorted_col = $pmatable->getUiProp(PMA_Table::PROP_SORTED_COLUMN);
        if ($sorted_col) {
            // retrieve the remembered sorting order for current table
            $sql_order_to_append = ' ORDER BY ' . $sorted_col . ' ';
            $full_sql_query = $analyzed_sql[0]['section_before_limit'] . $sql_order_to_append
                . $analyzed_sql[0]['limit_clause'] . ' ' . $analyzed_sql[0]['section_after_limit'];

            // update the $analyzed_sql
            $analyzed_sql[0]['section_before_limit'] .= $sql_order_to_append;
            $analyzed_sql[0]['order_by_clause'] = $sorted_col;
        }
    } else {
        // store the remembered table into session
        $pmatable->setUiProp(PMA_Table::PROP_SORTED_COLUMN, $analyzed_sql[0]['order_by_clause']);
    }
}

// Do append a "LIMIT" clause?
if (($_SESSION['tmp_user_values']['max_rows'] != 'all')
    && ! ($is_count || $is_export || $is_func || $is_analyse)
    && isset($analyzed_sql[0]['queryflags']['select_from'])
    && ! isset($analyzed_sql[0]['queryflags']['offset'])
    && empty($analyzed_sql[0]['limit_clause'])
) {
    $sql_limit_to_append = ' LIMIT ' . $_SESSION['tmp_user_values']['pos']
        . ', ' . $_SESSION['tmp_user_values']['max_rows'] . " ";

    $full_sql_query  = $analyzed_sql[0]['section_before_limit'] . "\n"
        . $sql_limit_to_append . $analyzed_sql[0]['section_after_limit'];
    /**
     * @todo pretty printing of this modified query
     */
    if (isset($display_query)) {
        // if the analysis of the original query revealed that we found
        // a section_after_limit, we now have to analyze $display_query
        // to display it correctly

        if (! empty($analyzed_sql[0]['section_after_limit'])
            && trim($analyzed_sql[0]['section_after_limit']) != ';'
        ) {
            $analyzed_display_query = PMA_SQP_analyze(PMA_SQP_parse($display_query));
            $display_query  = $analyzed_display_query[0]['section_before_limit']
                . "\n" . $sql_limit_to_append . $analyzed_display_query[0]['section_after_limit'];
        }
    }

}

if (strlen($db)) {
    PMA_DBI_select_db($db);
}

//  E x e c u t e    t h e    q u e r y

// Only if we didn't ask to see the php code (mikebeck)
if (isset($GLOBALS['show_as_php']) || ! empty($GLOBALS['validatequery'])) {
    unset($result);
    $num_rows = 0;
    $unlim_num_rows = 0;
} else {
    if (isset($_SESSION['profiling']) && PMA_profilingSupported()) {
        PMA_DBI_query('SET PROFILING=1;');
    }

    // Measure query time.
    $querytime_before = array_sum(explode(' ', microtime()));

    $result   = @PMA_DBI_try_query($full_sql_query, null, PMA_DBI_QUERY_STORE);

    // If a stored procedure was called, there may be more results that are
    // queued up and waiting to be flushed from the buffer. So let's do that.
    while (true) {
        if (! PMA_DBI_more_results()) {
            break;
        }
        PMA_DBI_next_result();
    }

    $querytime_after = array_sum(explode(' ', microtime()));

    $GLOBALS['querytime'] = $querytime_after - $querytime_before;

    // Displays an error message if required and stop parsing the script
    if ($error        = PMA_DBI_getError()) {
        if ($is_gotofile) {
            if (strpos($goto, 'db_') === 0 && strlen($table)) {
                $table = '';
            }
            $active_page = $goto;
            $message = PMA_Message::rawError($error);

            if ($GLOBALS['is_ajax_request'] == true) {
                PMA_ajaxResponse($message, false);
            }

            /**
             * Go to target path.
             */
            include './' . PMA_securePath($goto);
        } else {
            $full_err_url = (preg_match('@^(db|tbl)_@', $err_url))
                          ? $err_url . '&amp;show_query=1&amp;sql_query=' . urlencode($sql_query)
                          : $err_url;
            PMA_mysqlDie($error, $full_sql_query, '', $full_err_url);
        }
        exit;
    }
    unset($error);

    // Gets the number of rows affected/returned
    // (This must be done immediately after the query because
    // mysql_affected_rows() reports about the last query done)

    if (! $is_affected) {
        $num_rows = ($result) ? @PMA_DBI_num_rows($result) : 0;
    } elseif (! isset($num_rows)) {
        $num_rows = @PMA_DBI_affected_rows();
    }

    // Grabs the profiling results
    if (isset($_SESSION['profiling']) && PMA_profilingSupported()) {
        $profiling_results = PMA_DBI_fetch_result('SHOW PROFILE;');
    }

    // Checks if the current database has changed
    // This could happen if the user sends a query like "USE `database`;"
    /**
     * commented out auto-switching to active database - really required?
     * bug #1814718 win: table list disappears (mixed case db names)
     * https://sourceforge.net/support/tracker.php?aid=1814718
     * @todo RELEASE test and comit or rollback before release
    $current_db = PMA_DBI_fetch_value('SELECT DATABASE()');
    if ($db !== $current_db) {
        $db     = $current_db;
        $reload = 1;
    }
    unset($current_db);
     */

    // tmpfile remove after convert encoding appended by Y.Kawada
    if (function_exists('PMA_kanji_file_conv')
        && (isset($textfile) && file_exists($textfile))
    ) {
        unlink($textfile);
    }

    // Counts the total number of rows for the same 'SELECT' query without the
    // 'LIMIT' clause that may have been programatically added

    if (empty($sql_limit_to_append)) {
        $unlim_num_rows         = $num_rows;
        // if we did not append a limit, set this to get a correct
        // "Showing rows..." message
        //$_SESSION['tmp_user_values']['max_rows'] = 'all';
    } elseif ($is_select) {

        //    c o u n t    q u e r y

        // If we are "just browsing", there is only one table,
        // and no WHERE clause (or just 'WHERE 1 '),
        // we do a quick count (which uses MaxExactCount) because
        // SQL_CALC_FOUND_ROWS is not quick on large InnoDB tables

        // However, do not count again if we did it previously
        // due to $find_real_end == true

        if (! $is_group
            && ! isset($analyzed_sql[0]['queryflags']['union'])
            && ! isset($analyzed_sql[0]['queryflags']['distinct'])
            && ! isset($analyzed_sql[0]['table_ref'][1]['table_name'])
            && (empty($analyzed_sql[0]['where_clause']) || $analyzed_sql[0]['where_clause'] == '1 ')
            && ! isset($find_real_end)
        ) {

            // "j u s t   b r o w s i n g"
            $unlim_num_rows = PMA_Table::countRecords($db, $table);

        } else { // n o t   " j u s t   b r o w s i n g "

            // add select expression after the SQL_CALC_FOUND_ROWS

            // for UNION, just adding SQL_CALC_FOUND_ROWS
            // after the first SELECT works.

            // take the left part, could be:
            // SELECT
            // (SELECT
            $count_query = PMA_SQP_formatHtml($parsed_sql, 'query_only', 0, $analyzed_sql[0]['position_of_first_select'] + 1);
            $count_query .= ' SQL_CALC_FOUND_ROWS ';
            // add everything that was after the first SELECT
            $count_query .= PMA_SQP_formatHtml($parsed_sql, 'query_only', $analyzed_sql[0]['position_of_first_select'] + 1);
            // ensure there is no semicolon at the end of the
            // count query because we'll probably add
            // a LIMIT 1 clause after it
            $count_query = rtrim($count_query);
            $count_query = rtrim($count_query, ';');

            // if using SQL_CALC_FOUND_ROWS, add a LIMIT to avoid
            // long delays. Returned count will be complete anyway.
            // (but a LIMIT would disrupt results in an UNION)

            if (! isset($analyzed_sql[0]['queryflags']['union'])) {
                $count_query .= ' LIMIT 1';
            }

            // run the count query

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
            // SELECT COUNT(*), f1 from t1 group by f1
            // and you click to sort on count(*)
            // }
            $unlim_num_rows = PMA_DBI_fetch_value('SELECT FOUND_ROWS()');
        } // end else "just browsing"

    } else { // not $is_select
         $unlim_num_rows         = 0;
    } // end rows total count

    // if a table or database gets dropped, check column comments.
    if (isset($purge) && $purge == '1') {
        /**
         * Cleanup relations.
         */
        include_once './libraries/relation_cleanup.lib.php';

        if (strlen($table) && strlen($db)) {
            PMA_relationsCleanupTable($db, $table);
        } elseif (strlen($db)) {
            PMA_relationsCleanupDatabase($db);
        } else {
            // VOID. No DB/Table gets deleted.
        } // end if relation-stuff
    } // end if ($purge)

    // If a column gets dropped, do relation magic.
    if (isset($dropped_column) && strlen($db) && strlen($table) && ! empty($dropped_column)) {
        include_once './libraries/relation_cleanup.lib.php';
        PMA_relationsCleanupColumn($db, $table, $dropped_column);
        // to refresh the list of indexes (Ajax mode)
        $extra_data['indexes_list'] = PMA_Index::getView($table, $db);
    } // end if column was dropped
} // end else "didn't ask to see php code"

// No rows returned -> move back to the calling page
if ((0 == $num_rows && 0 == $unlim_num_rows) || $is_affected) {
    if ($is_delete) {
        $message = PMA_Message::deleted_rows($num_rows);
    } elseif ($is_insert) {
        if ($is_replace) {
            /* For replace we get DELETED + INSERTED row count, so we have to call it affected */
            $message = PMA_Message::affected_rows($num_rows);
        } else {
            $message = PMA_Message::inserted_rows($num_rows);
        }
        $insert_id = PMA_DBI_insert_id();
        if ($insert_id != 0) {
            // insert_id is id of FIRST record inserted in one insert, so if we inserted multiple rows, we had to increment this
            $message->addMessage('[br]');
            // need to use a temporary because the Message class
            // currently supports adding parameters only to the first
            // message
            $_inserted = PMA_Message::notice(__('Inserted row id: %1$d'));
            $_inserted->addParam($insert_id + $num_rows - 1);
            $message->addMessage($_inserted);
        }
    } elseif ($is_affected) {
        $message = PMA_Message::affected_rows($num_rows);

        // Ok, here is an explanation for the !$is_select.
        // The form generated by sql_query_form.lib.php
        // and db_sql.php has many submit buttons
        // on the same form, and some confusion arises from the
        // fact that $message_to_show is sent for every case.
        // The $message_to_show containing a success message and sent with
        // the form should not have priority over errors
    } elseif (! empty($message_to_show) && ! $is_select) {
        $message = PMA_Message::rawSuccess(htmlspecialchars($message_to_show));
    } elseif (! empty($GLOBALS['show_as_php'])) {
        $message = PMA_Message::success(__('Showing as PHP code'));
    } elseif (isset($GLOBALS['show_as_php'])) {
        /* User disable showing as PHP, query is only displayed */
        $message = PMA_Message::notice(__('Showing SQL query'));
    } elseif (! empty($GLOBALS['validatequery'])) {
        $message = PMA_Message::notice(__('Validated SQL'));
    } else {
        $message = PMA_Message::success(__('MySQL returned an empty result set (i.e. zero rows).'));
    }

    if (isset($GLOBALS['querytime'])) {
        $_querytime = PMA_Message::notice(__('Query took %01.4f sec'));
        $_querytime->addParam($GLOBALS['querytime']);
        $message->addMessage('(');
        $message->addMessage($_querytime);
        $message->addMessage(')');
    }

    if ($GLOBALS['is_ajax_request'] == true) {
        if ($cfg['ShowSQL']) {
            $extra_data['sql_query'] = PMA_showMessage($message, $GLOBALS['sql_query'], 'success');
        }
        if (isset($GLOBALS['reload']) && $GLOBALS['reload'] == 1) {
            $extra_data['reload'] = 1;
            $extra_data['db'] = $GLOBALS['db'];
        }
        PMA_ajaxResponse($message, $message->isSuccess(), (isset($extra_data) ? $extra_data : ''));
    }

    if ($is_gotofile) {
        $goto = PMA_securePath($goto);
        // Checks for a valid target script
        $is_db = $is_table = false;
        if (isset($_REQUEST['purge']) && $_REQUEST['purge'] == '1') {
            $table = '';
            unset($url_params['table']);
        }
        include 'libraries/db_table_exists.lib.php';

        if (strpos($goto, 'tbl_') === 0 && ! $is_table) {
            if (strlen($table)) {
                $table = '';
            }
            $goto = 'db_sql.php';
        }
        if (strpos($goto, 'db_') === 0 && ! $is_db) {
            if (strlen($db)) {
                $db = '';
            }
            $goto = 'main.php';
        }
        // Loads to target script
        if ($goto != 'main.php') {
            include_once './libraries/header.inc.php';
        }
        $active_page = $goto;
        include './' . $goto;
    } else {
        // avoid a redirect loop when last record was deleted
        if (0 == $num_rows && 'sql.php' == $cfg['DefaultTabTable']) {
            $goto = str_replace('sql.php', 'tbl_structure.php', $goto);
        }
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto) . '&message=' . urlencode($message));
    } // end else
    exit();
// end no rows returned
} else {
    // At least one row is returned -> displays a table with results
    //If we are retrieving the full value of a truncated field or the original
    // value of a transformed field, show it here and exit
    if ($GLOBALS['grid_edit'] == true && $GLOBALS['cfg']['AjaxEnable']) {
        $row = PMA_DBI_fetch_row($result);
        $extra_data = array();
        $extra_data['value'] = $row[0];
        PMA_ajaxResponse(null, true, $extra_data);
    }

    if (isset($_REQUEST['ajax_request']) && isset($_REQUEST['table_maintenance'])) {
        $GLOBALS['js_include'][] = 'functions.js';
        $GLOBALS['js_include'][] = 'makegrid.js';
        $GLOBALS['js_include'][] = 'sql.js';

        // Gets the list of fields properties
        if (isset($result) && $result) {
            $fields_meta = PMA_DBI_get_fields_meta($result);
            $fields_cnt  = count($fields_meta);
        }

        if (empty($disp_mode)) {
            // see the "PMA_setDisplayMode()" function in
            // libraries/display_tbl.lib.php
            $disp_mode = 'urdr111101';
        }

        // hide edit and delete links for information_schema
        if (PMA_is_system_schema($db)) {
            $disp_mode = 'nnnn110111';
        }

        $message = PMA_Message::success($message);
        echo PMA_showMessage($message, $GLOBALS['sql_query'], 'success');
        PMA_displayTable($result, $disp_mode, $analyzed_sql);
        exit();
    }

    // Displays the headers
    if (isset($show_query)) {
        unset($show_query);
    }
    if (isset($printview) && $printview == '1') {
        include_once './libraries/header_printview.inc.php';
    } else {

        $GLOBALS['js_include'][] = 'functions.js';
        $GLOBALS['js_include'][] = 'makegrid.js';
        $GLOBALS['js_include'][] = 'sql.js';

        unset($message);

        if (! $GLOBALS['is_ajax_request'] || ! $GLOBALS['cfg']['AjaxEnable']) {
            if (strlen($table)) {
                include './libraries/tbl_common.php';
                $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
                include './libraries/tbl_info.inc.php';
                include './libraries/tbl_links.inc.php';
            } elseif (strlen($db)) {
                include './libraries/db_common.inc.php';
                include './libraries/db_info.inc.php';
            } else {
                include './libraries/server_common.inc.php';
                include './libraries/server_links.inc.php';
            }
        } else {
            include_once './libraries/header.inc.php';
            //we don't need to buffer the output in PMA_showMessage here.
            //set a global variable and check against it in the function
            $GLOBALS['buffer_message'] = false;
        }
    }

    if (strlen($db)) {
        $cfgRelation = PMA_getRelationsParam();
    }

    // Gets the list of fields properties
    if (isset($result) && $result) {
        $fields_meta = PMA_DBI_get_fields_meta($result);
        $fields_cnt  = count($fields_meta);
    }

    if (! $GLOBALS['is_ajax_request']) {
        //begin the sqlqueryresults div here. container div
        echo '<div id="sqlqueryresults"';
        if ($GLOBALS['cfg']['AjaxEnable']) {
            echo ' class="ajax"';
        }
        echo '>';
    }

    // Display previous update query (from tbl_replace)
    if (isset($disp_query) && $cfg['ShowSQL'] == true) {
        PMA_showMessage($disp_message, $disp_query, 'success');
    }

    if (isset($profiling_results)) {
        // pma_token/url_query needed for chart export
?>
<script type="text/javascript">
pma_token = '<?php echo $_SESSION[' PMA_token ']; ?>';
url_query = '<?php echo isset($url_query)?$url_query:PMA_generate_common_url($db);?>';
$(document).ready(makeProfilingChart);
</script>
<?php
        echo '<fieldset><legend>' . __('Profiling') . '</legend>' . "\n";
        echo '<div style="float: left;">';
        echo '<table>' . "\n";
        echo ' <tr>' .  "\n";
        echo '  <th>' . __('Status') . PMA_showMySQLDocu('general-thread-states', 'general-thread-states') .  '</th>' . "\n";
        echo '  <th>' . __('Time') . '</th>' . "\n";
        echo ' </tr>' .  "\n";

        $chart_json = Array();
        foreach ($profiling_results as $one_result) {
            echo ' <tr>' .  "\n";
            echo '<td>' . ucwords($one_result['Status']) . '</td>' .  "\n";
            echo '<td align="right">' . (PMA_formatNumber($one_result['Duration'], 3, 1)) . 's</td>' .  "\n";
            if (isset($chart_json[ucwords($one_result['Status'])])) {
                $chart_json[ucwords($one_result['Status'])] += $one_result['Duration'];
            } else {
                $chart_json[ucwords($one_result['Status'])] = $one_result['Duration'];
            }
        }

        echo '</table>' . "\n";
        echo '</div>';
        echo '<div id="profilingchart" style="display:none;">';
        echo json_encode($chart_json);
        echo '</div>';
        echo '</fieldset>' . "\n";
    }

    // Displays the results in a table
    if (empty($disp_mode)) {
        // see the "PMA_setDisplayMode()" function in
        // libraries/display_tbl.lib.php
        $disp_mode = 'urdr111101';
    }

    // hide edit and delete links for information_schema
    if (PMA_is_system_schema($db)) {
        $disp_mode = 'nnnn110111';
    }

    if (isset($label)) {
        $message = PMA_message::success(__('Bookmark %s created'));
        $message->addParam($label);
        $message->display();
    }

    PMA_displayTable($result, $disp_mode, $analyzed_sql);
    PMA_DBI_free_result($result);

    // BEGIN INDEX CHECK See if indexes should be checked.
    if (isset($query_type) && $query_type == 'check_tbl' && isset($selected) && is_array($selected)) {
        foreach ($selected as $idx => $tbl_name) {
            $check = PMA_Index::findDuplicates($tbl_name, $db);
            if (! empty($check)) {
                printf(__('Problems with indexes of table `%s`'), $tbl_name);
                echo $check;
            }
        }
    } // End INDEX CHECK

    // Bookmark support if required
    if ($disp_mode[7] == '1'
        && (! empty($cfg['Bookmark']) && empty($id_bookmark))
        && ! empty($sql_query)
    ) {
        echo "\n";

        $goto = 'sql.php?'
              . PMA_generate_common_url($db, $table)
              . '&amp;sql_query=' . urlencode($sql_query)
              . '&amp;id_bookmark=1';

        ?>
<form action="sql.php" method="post" onsubmit="return emptyFormElements(this, 'fields[label]');">
<?php echo PMA_generate_common_hidden_inputs(); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="fields[dbase]" value="<?php echo htmlspecialchars($db); ?>" />
<input type="hidden" name="fields[user]" value="<?php echo $cfg['Bookmark']['user']; ?>" />
<input type="hidden" name="fields[query]" value="<?php echo urlencode(isset($complete_query) ? $complete_query : $sql_query); ?>" />
<fieldset>
    <legend><?php
    echo PMA_getIcon('b_bookmark.png', __('Bookmark this SQL query'), true);
?>
    </legend>

    <div class="formelement">
        <label for="fields_label_"><?php echo __('Label'); ?>:</label>
        <input type="text" id="fields_label_" name="fields[label]" value="" />
    </div>

    <div class="formelement">
        <input type="checkbox" name="bkm_all_users" id="bkm_all_users" value="true" />
        <label for="bkm_all_users"><?php echo __('Let every user access this bookmark'); ?></label>
    </div>

    <div class="clearfloat"></div>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="store_bkm" value="<?php echo __('Bookmark this SQL query'); ?>" />
</fieldset>
</form>
        <?php
    } // end bookmark support

    // Do print the page if required
    if (isset($printview) && $printview == '1') {
        PMA_printButton();
    } // end print case

    if ($GLOBALS['is_ajax_request'] != true) {
        echo '</div>'; // end sqlqueryresults div
    }
} // end rows returned

/**
 * Displays the footer
 */
if (! isset($_REQUEST['table_maintenance'])) {
    include './libraries/footer.inc.php';
}
?>
