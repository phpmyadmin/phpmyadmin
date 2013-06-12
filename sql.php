<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL executor
 *
 * @todo    we must handle the case if sql.php is called directly with a query
 *          that returns 0 rows - to prevent cyclic redirects or includes
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/Header.class.php';
require_once 'libraries/check_user_privileges.lib.php';
require_once 'libraries/bookmark.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
$scripts->addFile('tbl_change.js');
// the next one needed because sql.php may do a "goto" to tbl_structure.php
$scripts->addFile('tbl_structure.js');
$scripts->addFile('indexes.js');
$scripts->addFile('gis_data_editor.js');

/**
 * Set ajax_reload in the response if it was already set
 */
if (isset($ajax_reload) && $ajax_reload['reload'] === true) {
    $response->addJSON('ajax_reload', $ajax_reload);
}

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'bkm_all_users',
    'fields',
    'store_bkm'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

/**
 * Sets globals from $_GET
 */
$get_params = array(
    'id_bookmark',
    'label',
    'sql_query'
);
foreach ($get_params as $one_get_param) {
    if (isset($_GET[$one_get_param])) {
        $GLOBALS[$one_get_param] = $_GET[$one_get_param];
    }
}


if (isset($_REQUEST['printview'])) {
    $GLOBALS['printview'] = $_REQUEST['printview'];
}

if (!isset($_SESSION['is_multi_query'])) {
    $_SESSION['is_multi_query'] = false;
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
// Security checkings
if (! empty($goto)) {
    $is_gotofile     = preg_replace('@^([^?]+).*$@s', '\\1', $goto);
    if (! @file_exists('' . $is_gotofile)) {
        unset($goto);
    } else {
        $is_gotofile = ($is_gotofile == $goto);
    }
} else {
    if (empty($table)) {
        $goto = $cfg['DefaultTabDatabase'];
    } else {
        $goto = $cfg['DefaultTabTable'];
    }
    $is_gotofile  = true;
} // end if

if (! isset($err_url)) {
    $err_url = (! empty($back) ? $back : $goto)
        . '?' . PMA_generate_common_url($db)
        . ((strpos(' ' . $goto, 'db_') != 1 && strlen($table))
            ? '&amp;table=' . urlencode($table)
            : ''
        );
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
 * Logic taken from libraries/DisplayResults.class.php
 *
 * This doesn't seem to be the right place to do this, but I can't think of any
 * better place either.
 */
if (isset($_REQUEST['get_relational_values'])
    && $_REQUEST['get_relational_values'] == true
) {
    $column = $_REQUEST['column'];
    $foreigners = PMA_getForeigners($db, $table, $column);

    $display_field = PMA_getDisplayField(
        $foreigners[$column]['foreign_db'],
        $foreigners[$column]['foreign_table']
    );

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
        //Handle the case when number of values
        //is more than $cfg['ForeignKeyMaxLimit']
        $_url_params = array(
                'db' => $db,
                'table' => $table,
                'field' => $column
        );

        $dropdown = '<span class="curr_value">'
            . htmlspecialchars($_REQUEST['curr_value'])
            . '</span>'
            . '<a href="browse_foreigners.php'
            . PMA_generate_common_url($_url_params) . '"'
            . ' target="_blank" class="browse_foreign" ' .'>'
            . __('Browse foreign values')
            . '</a>';
    } else {
        $dropdown = PMA_foreignDropdown(
            $foreignData['disp_row'],
            $foreignData['foreign_field'],
            $foreignData['foreign_display'],
            $curr_value,
            $cfg['ForeignKeyMaxLimit']
        );
        $dropdown = '<select>' . $dropdown . '</select>';
    }

    $response = PMA_Response::getInstance();
    $response->addJSON('dropdown', $dropdown);
    exit;
}

/**
 * Just like above, find possible values for enum fields during grid edit.
 *
 * Logic taken from libraries/DisplayResults.class.php
 */
if (isset($_REQUEST['get_enum_values']) && $_REQUEST['get_enum_values'] == true) {
    $field_info_query = PMA_DBI_get_columns_sql($db, $table, $_REQUEST['column']);

    $field_info_result = PMA_DBI_fetch_result(
        $field_info_query, null, null, null, PMA_DBI_QUERY_STORE
    );

    $values = PMA_Util::parseEnumSetValues($field_info_result[0]['Type']);

    $dropdown = '<option value="">&nbsp;</option>';
    foreach ($values as $value) {
        $dropdown .= '<option value="' . $value . '"';
        if ($value == $_REQUEST['curr_value']) {
            $dropdown .= ' selected="selected"';
        }
        $dropdown .= '>' . $value . '</option>';
    }

    $dropdown = '<select>' . $dropdown . '</select>';

    $response = PMA_Response::getInstance();
    $response->addJSON('dropdown', $dropdown);
    exit;
}

/**
 * Find possible values for set fields during grid edit.
 */
if (isset($_REQUEST['get_set_values']) && $_REQUEST['get_set_values'] == true) {
    $field_info_query = PMA_DBI_get_columns_sql($db, $table, $_REQUEST['column']);

    $field_info_result = PMA_DBI_fetch_result(
        $field_info_query, null, null, null, PMA_DBI_QUERY_STORE
    );

    $values = PMA_Util::parseEnumSetValues($field_info_result[0]['Type']);

    $select = '';
       
    //converts characters of $_REQUEST['curr_value'] to HTML entities
    $converted_curr_value = htmlentities(
        $_REQUEST['curr_value'], ENT_COMPAT, "UTF-8"
    );

    $selected_values = explode(',', $converted_curr_value);
    
    foreach ($values as $value) {       
        $select .= '<option value="' . $value . '"';
        if ($value == $converted_curr_value 
            || in_array($value, $selected_values, true)
        ) {
            $select .= ' selected="selected" ';
        }
        $select .= '>' . $value . '</option>';
    }  

    $select_size = (sizeof($values) > 10) ? 10 : sizeof($values);
    $select = '<select multiple="multiple" size="' . $select_size . '">'
        . $select . '</select>';

    $response = PMA_Response::getInstance();
    $response->addJSON('select', $select);
    exit;
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
        $retval = $pmatable->setUiProp(
            PMA_Table::PROP_COLUMN_ORDER,
            $col_order,
            $_REQUEST['table_create_time']
        );
        if (gettype($retval) != 'boolean') {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $retval->getString());
            exit;
        }
    }

    // set column visibility
    if ($retval === true && isset($_REQUEST['col_visib'])) {
        $col_visib = explode(',', $_REQUEST['col_visib']);
        $retval = $pmatable->setUiProp(
            PMA_Table::PROP_COLUMN_VISIB, $col_visib,
            $_REQUEST['table_create_time']
        );
        if (gettype($retval) != 'boolean') {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $retval->getString());
            exit;
        }
    }

    $response = PMA_Response::getInstance();
    $response->isSuccess($retval == true);
    exit;
}

// Default to browse if no query set and we have table
// (needed for browsing from DefaultTabTable)
if (empty($sql_query) && strlen($table) && strlen($db)) {
    include_once 'libraries/bookmark.lib.php';
    $book_sql_query = PMA_Bookmark_get(
        $db,
        '\'' . PMA_Util::sqlAddSlashes($table) . '\'',
        'label',
        false,
        true
    );

    if (! empty($book_sql_query)) {
        $GLOBALS['using_bookmark_message'] = PMA_message::notice(
            __('Using bookmark "%s" as default browse query.')
        );
        $GLOBALS['using_bookmark_message']->addParam($table);
        $GLOBALS['using_bookmark_message']->addMessage(
            PMA_Util::showDocu('faq', 'faq6-22')
        );
        $sql_query = $book_sql_query;
    } else {
        $sql_query = 'SELECT * FROM ' . PMA_Util::backquote($table);
    }
    unset($book_sql_query);

    // set $goto to what will be displayed if query returns 0 rows
    $goto = '';
} else {
    // Now we can check the parameters
    PMA_Util::checkParameters(array('sql_query'));
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
    PMA_Util::mysqlDie(
        __('"DROP DATABASE" statements are disabled.'),
        '',
        '',
        $err_url
    );
} // end if

// Include PMA_Index class for use in PMA_DisplayResults class
require_once './libraries/Index.class.php';

require_once 'libraries/DisplayResults.class.php';

$displayResultsObject = new PMA_DisplayResults(
    $GLOBALS['db'], $GLOBALS['table'], $GLOBALS['goto'], $GLOBALS['sql_query']
);

$displayResultsObject->setConfigParamsForDisplayTable();

/**
 * Need to find the real end of rows?
 */
if (isset($find_real_end) && $find_real_end) {
    $unlim_num_rows = PMA_Table::countRecords($db, $table, true);
    $_SESSION['tmp_user_values']['pos'] = @((ceil(
        $unlim_num_rows / $_SESSION['tmp_user_values']['max_rows']
    ) - 1) * $_SESSION['tmp_user_values']['max_rows']);
}


/**
 * Bookmark add
 */
if (isset($store_bkm)) {
    $result = PMA_Bookmark_save(
        $fields,
        (isset($bkm_all_users) && $bkm_all_users == 'true' ? true : false)
    );
    $response = PMA_Response::getInstance();
    if ($response->isAjax()) {
        if ($result) {
            $msg = PMA_message::success(__('Bookmark %s created'));
            $msg->addParam($fields['label']);
            $response->addJSON('message', $msg);
        } else {
            $msg = PMA_message::error(__('Bookmark not created'));
            $response->isSuccess(false);
            $response->addJSON('message', $msg);
        }
        exit;
    } else {
        // go back to sql.php to redisplay query; do not use &amp; in this case:
        PMA_sendHeaderLocation(
            $cfg['PmaAbsoluteUri'] . $goto . '&label=' . $fields['label']
        );
    }
} // end if

/**
 * Parse and analyze the query
 */
require_once 'libraries/parse_analyze.lib.php';

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
if (isset($_REQUEST['btnDrop']) && $_REQUEST['btnDrop'] == __('No')) {
    if (! empty($back)) {
        $goto = $back;
    }
    if ($is_gotofile) {
        if (strpos($goto, 'db_') === 0 && strlen($table)) {
            $table = '';
        }
        $active_page = $goto;
        include '' . PMA_securePath($goto);
    } else {
        PMA_sendHeaderLocation(
            $cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto)
        );
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
// if we are coming from a "Create PHP code" or a "Without PHP Code"
// dialog, we won't execute the query anyway, so don't confirm
if (! $cfg['Confirm']
    || isset($_REQUEST['is_js_confirmed'])
    || isset($_REQUEST['btnDrop'])
    || isset($GLOBALS['show_as_php'])
    || ! empty($GLOBALS['validatequery'])
) {
    $do_confirm = false;
} else {
    $do_confirm = isset($analyzed_sql[0]['queryflags']['need_confirm']);
}

if ($do_confirm) {
    $stripped_sql_query = $sql_query;
    $input = '<input type="hidden" name="%s" value="%s" />';
    $output = '';
    if ($is_drop_database) {
        $output .= '<h1 class="error">';
        $output .= __('You are about to DESTROY a complete database!');
        $output .= '</h1>';
    }
    $form  = '<form class="disableAjax" action="sql.php" method="post">';
    $form .= PMA_generate_common_hidden_inputs($db, $table);

    $form .= sprintf(
        $input, 'sql_query', htmlspecialchars($sql_query)
    );
    $form .= sprintf(
        $input, 'message_to_show',
        (isset($message_to_show) ? PMA_sanitize($message_to_show, true) : '')
    );
    $form .= sprintf(
        $input, 'goto', $goto
    );
    $form .= sprintf(
        $input, 'back',
        (isset($back) ? PMA_sanitize($back, true) : '')
    );
    $form .= sprintf(
        $input, 'reload',
        (isset($reload) ? PMA_sanitize($reload, true) : '')
    );
    $form .= sprintf(
        $input, 'purge',
        (isset($purge) ? PMA_sanitize($purge, true) : '')
    );
    $form .= sprintf(
        $input, 'dropped_column',
        (isset($dropped_column) ? PMA_sanitize($dropped_column, true) : '')
    );
    $form .= sprintf(
        $input, 'show_query',
        (isset($message_to_show) ? PMA_sanitize($show_query, true) : '')
    );
    $form = str_replace('%', '%%', $form) . '%s</form>';

    $output .='<fieldset class="confirmation">'
        .'<legend>'
        . __('Do you really want to execute the following query?')
        . '</legend>'
        .'<code>' . htmlspecialchars($stripped_sql_query) . '</code>'
        .'</fieldset>'
        .'<fieldset class="tblFooters">';

    $yes_input  = sprintf($input, 'btnDrop', __('Yes'));
    $yes_input .= '<input type="submit" value="' . __('Yes') . '" id="buttonYes" />';
    $no_input   = sprintf($input, 'btnDrop', __('No'));
    $no_input  .= '<input type="submit" value="' . __('No') . '" id="buttonNo" />';

    $output .= sprintf($form, $yes_input);
    $output .= sprintf($form, $no_input);

    $output .='</fieldset>';
    $output .= '';

    PMA_Response::getInstance()->addHTML($output);

    exit;
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

// $is_group added for use in calculation of total number of rows.
// $is_count is changed for more correct "LIMIT" clause
//  appending in queries like
//  "SELECT COUNT(...) FROM ... GROUP BY ..."

/**
 * @todo detect all this with the parser, to avoid problems finding
 * those strings in comments or backquoted identifiers
 */
list($is_group, $is_func, $is_count, $is_export, $is_analyse, $is_explain,
    $is_delete, $is_affected, $is_insert, $is_replace, $is_show, $is_maint)
        = PMA_getDisplayPropertyParams(
            $sql_query, $is_select
        );

// assign default full_sql_query
$full_sql_query = $sql_query;

// Handle remembered sorting order, only for single table query
if ($GLOBALS['cfg']['RememberSorting']
    && ! ($is_count || $is_export || $is_func || $is_analyse)
    && isset($analyzed_sql[0]['select_expr'])
    && (count($analyzed_sql[0]['select_expr']) == 0)
    && isset($analyzed_sql[0]['queryflags']['select_from'])
    && count($analyzed_sql[0]['table_ref']) == 1
) {
    PMA_handleSortOrder($db, $table, $analyzed_sql, $full_sql_query);
}

$sql_limit_to_append = '';
// Do append a "LIMIT" clause?
if (($_SESSION['tmp_user_values']['max_rows'] != 'all')
    && ! ($is_count || $is_export || $is_func || $is_analyse)
    && isset($analyzed_sql[0]['queryflags']['select_from'])
    && ! isset($analyzed_sql[0]['queryflags']['offset'])
    && empty($analyzed_sql[0]['limit_clause'])
) {
    $sql_limit_to_append = ' LIMIT ' . $_SESSION['tmp_user_values']['pos']
        . ', ' . $_SESSION['tmp_user_values']['max_rows'] . " ";
    $full_sql_query = PMA_getSqlWithLimitClause(
        $full_sql_query,
        $analyzed_sql,
        $sql_limit_to_append
    );

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
            $analyzed_display_query = PMA_SQP_analyze(
                PMA_SQP_parse($display_query)
            );
            $display_query  = $analyzed_display_query[0]['section_before_limit']
                . "\n" . $sql_limit_to_append
                . $analyzed_display_query[0]['section_after_limit'];
        }
    }
}

if (strlen($db)) {
    PMA_DBI_select_db($db);
}

//  E x e c u t e    t h e    q u e r y

// Only if we didn't ask to see the php code
if (isset($GLOBALS['show_as_php']) || ! empty($GLOBALS['validatequery'])) {
    unset($result);
    $num_rows = 0;
    $unlim_num_rows = 0;
} else {
    if (isset($_SESSION['profiling']) && PMA_Util::profilingSupported()) {
        PMA_DBI_query('SET PROFILING=1;');
    }

    // Measure query time.
    $querytime_before = array_sum(explode(' ', microtime()));

    $result   = @PMA_DBI_try_query($full_sql_query, null, PMA_DBI_QUERY_STORE);

    // If a stored procedure was called, there may be more results that are
    // queued up and waiting to be flushed from the buffer. So let's do that.
    do {
        PMA_DBI_store_result();
        if (! PMA_DBI_more_results()) {
            break;
        }
    } while (PMA_DBI_next_result());

    $is_procedure = false;
    
    // Since multiple query execution is anyway handled,
    // ignore the WHERE clause of the first sql statement
    // which might contain a phrase like 'call '
    if (preg_match("/\bcall\b/i", $full_sql_query)
        && empty($analyzed_sql[0]['where_clause'])
    ) {
        $is_procedure = true;
    }

    $querytime_after = array_sum(explode(' ', microtime()));

    $GLOBALS['querytime'] = $querytime_after - $querytime_before;

    // Displays an error message if required and stop parsing the script
    $error = PMA_DBI_getError();
    if ($error) {
        if ($is_gotofile) {
            if (strpos($goto, 'db_') === 0 && strlen($table)) {
                $table = '';
            }
            $active_page = $goto;
            $message = PMA_Message::rawError($error);

            if ($GLOBALS['is_ajax_request'] == true) {
                $response = PMA_Response::getInstance();
                $response->isSuccess(false);
                $response->addJSON('message', $message);
                exit;
            }

            /**
             * Go to target path.
             */
            include '' . PMA_securePath($goto);
        } else {
            $full_err_url = $err_url;
            if (preg_match('@^(db|tbl)_@', $err_url)) {
                $full_err_url .=  '&amp;show_query=1&amp;sql_query='
                    . urlencode($sql_query);
            }
            PMA_Util::mysqlDie($error, $full_sql_query, '', $full_err_url);
        }
        exit;
    }
    unset($error);

    // If there are no errors and bookmarklabel was given,
    // store the query as a bookmark
    if (! empty($bkm_label) && ! empty($import_text)) {
        include_once 'libraries/bookmark.lib.php';
        $bfields = array(
                     'dbase' => $db,
                     'user'  => $cfg['Bookmark']['user'],
                     'query' => urlencode($import_text),
                     'label' => $bkm_label
        );

        // Should we replace bookmark?
        if (isset($bkm_replace)) {
            $bookmarks = PMA_Bookmark_getList($db);
            foreach ($bookmarks as $key => $val) {
                if ($val == $bkm_label) {
                    PMA_Bookmark_delete($db, $key);
                }
            }
        }

        PMA_Bookmark_save($bfields, isset($bkm_all_users));

        $bookmark_created = true;
    } // end store bookmarks

    // Gets the number of rows affected/returned
    // (This must be done immediately after the query because
    // mysql_affected_rows() reports about the last query done)

    if (! $is_affected) {
        $num_rows = ($result) ? @PMA_DBI_num_rows($result) : 0;
    } elseif (! isset($num_rows)) {
        $num_rows = @PMA_DBI_affected_rows();
    }

    // Grabs the profiling results
    if (isset($_SESSION['profiling']) && PMA_Util::profilingSupported()) {
        $profiling_results = PMA_DBI_fetch_result('SHOW PROFILE;');
    }

    // Checks if the current database has changed
    // This could happen if the user sends a query like "USE `database`;"
    /**
     * commented out auto-switching to active database - really required?
     * bug #2558 win: table list disappears (mixed case db names)
     * https://sourceforge.net/p/phpmyadmin/bugs/2558/
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

    $justBrowsing = false;
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
            && (empty($analyzed_sql[0]['where_clause'])
            || $analyzed_sql[0]['where_clause'] == '1 ')
            && ! isset($find_real_end)
        ) {
            // "j u s t   b r o w s i n g"
            $justBrowsing = true;
            $unlim_num_rows = PMA_Table::countRecords(
                $db, 
                $table, 
                $force_exact = true
            );

        } else { // n o t   " j u s t   b r o w s i n g "

            // add select expression after the SQL_CALC_FOUND_ROWS

            // for UNION, just adding SQL_CALC_FOUND_ROWS
            // after the first SELECT works.

            // take the left part, could be:
            // SELECT
            // (SELECT
            $count_query = PMA_SQP_formatHtml(
                $parsed_sql,
                'query_only',
                0,
                $analyzed_sql[0]['position_of_first_select'] + 1
            );
            $count_query .= ' SQL_CALC_FOUND_ROWS ';
            // add everything that was after the first SELECT
            $count_query .= PMA_SQP_formatHtml(
                $parsed_sql,
                'query_only',
                $analyzed_sql[0]['position_of_first_select'] + 1
            );
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
        include_once 'libraries/relation_cleanup.lib.php';

        if (strlen($table) && strlen($db)) {
            PMA_relationsCleanupTable($db, $table);
        } elseif (strlen($db)) {
            PMA_relationsCleanupDatabase($db);
        } else {
            // VOID. No DB/Table gets deleted.
        } // end if relation-stuff
    } // end if ($purge)

    // If a column gets dropped, do relation magic.
    if (isset($dropped_column)
        && strlen($db)
        && strlen($table)
        && ! empty($dropped_column)
    ) {
        include_once 'libraries/relation_cleanup.lib.php';
        PMA_relationsCleanupColumn($db, $table, $dropped_column);
        // to refresh the list of indexes (Ajax mode)
        $extra_data['indexes_list'] = PMA_Index::getView($table, $db);
    } // end if column was dropped
} // end else "didn't ask to see php code"

// No rows returned -> move back to the calling page
if ((0 == $num_rows && 0 == $unlim_num_rows) || $is_affected) {
    // Delete related tranformation information
    if (!empty($analyzed_sql[0]['querytype'])
        && (($analyzed_sql[0]['querytype'] == 'ALTER')
        || ($analyzed_sql[0]['querytype'] == 'DROP'))
    ) {
        include_once 'libraries/transformations.lib.php';
        if ($analyzed_sql[0]['querytype'] == 'ALTER') {
            if (stripos($analyzed_sql[0]['unsorted_query'], 'DROP') !== false) {
                $drop_column = PMA_getColumnNameInColumnDropSql(
                    $analyzed_sql[0]['unsorted_query']
                );

                if ($drop_column != '') {
                    PMA_clearTransformations($db, $table, $drop_column);
                }
            }

        } else if (($analyzed_sql[0]['querytype'] == 'DROP') && ($table != '')) {
            PMA_clearTransformations($db, $table);
        }
    }

    if ($is_delete) {
        $message = PMA_Message::getMessageForDeletedRows($num_rows);
    } elseif ($is_insert) {
        if ($is_replace) {
            // For replace we get DELETED + INSERTED row count,
            // so we have to call it affected
            $message = PMA_Message::getMessageForAffectedRows($num_rows);
        } else {
            $message = PMA_Message::getMessageForInsertedRows($num_rows);
        }
        $insert_id = PMA_DBI_insert_id();
        if ($insert_id != 0) {
            // insert_id is id of FIRST record inserted in one insert,
            // so if we inserted multiple rows, we had to increment this
            $message->addMessage('[br]');
            // need to use a temporary because the Message class
            // currently supports adding parameters only to the first
            // message
            $_inserted = PMA_Message::notice(__('Inserted row id: %1$d'));
            $_inserted->addParam($insert_id + $num_rows - 1);
            $message->addMessage($_inserted);
        }
    } elseif ($is_affected) {
        $message = PMA_Message::getMessageForAffectedRows($num_rows);

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
        $message = PMA_Message::success(
            __('MySQL returned an empty result set (i.e. zero rows).')
        );
    }

    if (isset($GLOBALS['querytime'])) {
        $_querytime = PMA_Message::notice('(' . __('Query took %01.4f sec') . ')');
        $_querytime->addParam($GLOBALS['querytime']);
        $message->addMessage($_querytime);
    }

    if ($GLOBALS['is_ajax_request'] == true) {
        if ($cfg['ShowSQL']) {
            $extra_data['sql_query'] = PMA_Util::getMessage(
                $message, $GLOBALS['sql_query'], 'success'
            );
        }
        if (isset($GLOBALS['reload']) && $GLOBALS['reload'] == 1) {
            $extra_data['reload'] = 1;
            $extra_data['db'] = $GLOBALS['db'];
        }
        $response = PMA_Response::getInstance();
        $response->isSuccess($message->isSuccess());
        // No need to manually send the message
        // The Response class will handle that automatically
        $response->addJSON(isset($extra_data) ? $extra_data : array());
        if (empty($_REQUEST['ajax_page_request'])) {
            $response->addJSON('message', $message);
            exit;
        }
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
            $goto = 'index.php';
        }
        // Loads to target script
        if (strlen($goto) > 0) {
            $active_page = $goto;
            include '' . $goto;
        } else {
            // Echo at least one character to prevent showing last page from history
            echo " ";
        }
        
    } else {
        // avoid a redirect loop when last record was deleted
        if (0 == $num_rows && 'sql.php' == $cfg['DefaultTabTable']) {
            $goto = str_replace('sql.php', 'tbl_structure.php', $goto);
        }
        PMA_sendHeaderLocation(
            $cfg['PmaAbsoluteUri'] . str_replace('&amp;', '&', $goto)
            . '&message=' . urlencode($message)
        );
    } // end else
    exit();
    // end no rows returned
} else {
    // At least one row is returned -> displays a table with results
    //If we are retrieving the full value of a truncated field or the original
    // value of a transformed field, show it here and exit
    if ($GLOBALS['grid_edit'] == true) {
        $row = PMA_DBI_fetch_row($result);
        $response = PMA_Response::getInstance();
        $response->addJSON('value', $row[0]);
        exit;
    }

    if (isset($_REQUEST['ajax_request']) && isset($_REQUEST['table_maintenance'])) {
        $response = PMA_Response::getInstance();
        $header   = $response->getHeader();
        $scripts  = $header->getScripts();
        $scripts->addFile('makegrid.js');
        $scripts->addFile('sql.js');

        // Gets the list of fields properties
        if (isset($result) && $result) {
            $fields_meta = PMA_DBI_get_fields_meta($result);
            $fields_cnt  = count($fields_meta);
        }

        if (empty($disp_mode)) {
            // see the "PMA_setDisplayMode()" function in
            // libraries/DisplayResults.class.php
            $disp_mode = 'urdr111101';
        }

        // hide edit and delete links for information_schema
        if (PMA_is_system_schema($db)) {
            $disp_mode = 'nnnn110111';
        }

        if (isset($message)) {
            $message = PMA_Message::success($message);
            echo PMA_Util::getMessage(
                $message, $GLOBALS['sql_query'], 'success'
            );
        }

        // Should be initialized these parameters before parsing
        $showtable = isset($showtable) ? $showtable : null;
        $printview = isset($printview) ? $printview : null;
        $url_query = isset($url_query) ? $url_query : null;

        if (!empty($sql_data) && ($sql_data['valid_queries'] > 1)) {

            $_SESSION['is_multi_query'] = true;
            echo getTableHtmlForMultipleQueries(
                $displayResultsObject, $db, $sql_data, $goto,
                $pmaThemeImage, $text_dir, $printview, $url_query,
                $disp_mode, $sql_limit_to_append, false
            );
        } else {
            $_SESSION['is_multi_query'] = false;
            $displayResultsObject->setProperties(
                $unlim_num_rows, $fields_meta, $is_count, $is_export, $is_func,
                $is_analyse, $num_rows, $fields_cnt, $querytime, $pmaThemeImage,
                $text_dir, $is_maint, $is_explain, $is_show, $showtable,
                $printview, $url_query, false
            );

            echo $displayResultsObject->getTable(
                $result, $disp_mode, $analyzed_sql
            );
            exit();
        }
    }

    // Displays the headers
    if (isset($show_query)) {
        unset($show_query);
    }
    if (isset($printview) && $printview == '1') {
        PMA_Util::checkParameters(array('db', 'full_sql_query'));

        $response = PMA_Response::getInstance();
        $header = $response->getHeader();
        $header->enablePrintView();

        $hostname = '';
        if ($cfg['Server']['verbose']) {
            $hostname = $cfg['Server']['verbose'];
        } else {
            $hostname = $cfg['Server']['host'];
            if (! empty($cfg['Server']['port'])) {
                $hostname .= $cfg['Server']['port'];
            }
        }

        $versions  = "phpMyAdmin&nbsp;" . PMA_VERSION;
        $versions .= "&nbsp;/&nbsp;";
        $versions .= "MySQL&nbsp;" . PMA_MYSQL_STR_VERSION;

        echo "<h1>" . __('SQL result') . "</h1>";
        echo "<p>";
        echo "<strong>" . __('Host') . ":</strong> $hostname<br />";
        echo "<strong>" . __('Database') . ":</strong> "
            . htmlspecialchars($db) . "<br />";
        echo "<strong>" . __('Generation Time') . ":</strong> "
            . PMA_Util::localisedDate() . "<br />";
        echo "<strong>" . __('Generated by') . ":</strong> $versions<br />";
        echo "<strong>" . __('SQL query') . ":</strong> "
            . htmlspecialchars($full_sql_query) . ";";
        if (isset($num_rows)) {
            echo "<br />";
            echo "<strong>" . __('Rows') . ":</strong> $num_rows";
        }
        echo "</p>";
    } else {
        $response = PMA_Response::getInstance();
        $header = $response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('makegrid.js');
        $scripts->addFile('sql.js');

        unset($message);

        if (! $GLOBALS['is_ajax_request']) {
            if (strlen($table)) {
                include 'libraries/tbl_common.inc.php';
                $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
                include 'libraries/tbl_info.inc.php';
            } elseif (strlen($db)) {
                include 'libraries/db_common.inc.php';
                include 'libraries/db_info.inc.php';
            } else {
                include 'libraries/server_common.inc.php';
            }
        } else {
            //we don't need to buffer the output in getMessage here.
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

    //begin the sqlqueryresults div here. container div
    echo '<div id="sqlqueryresults"';
    echo ' class="ajax"';
    echo '>';

    // Display previous update query (from tbl_replace)
    if (isset($disp_query) && ($cfg['ShowSQL'] == true) && empty($sql_data)) {
        echo PMA_Util::getMessage($disp_message, $disp_query, 'success');
    }

    if (isset($profiling_results)) {
        // pma_token/url_query needed for chart export
        echo '<script type="text/javascript">';
        echo 'pma_token = \'' . $_SESSION[' PMA_token '] . '\';';
        echo 'url_query = \''
            . (isset($url_query) ? $url_query : PMA_generate_common_url($db))
            . '\';';
        echo 'AJAX.registerOnload(\'sql.js\',makeProfilingChart);';
        echo '</script>';

        echo '<fieldset><legend>' . __('Profiling') . '</legend>' . "\n";
        echo '<div style="float: left;">';
        echo '<table>' . "\n";
        echo ' <tr>' .  "\n";
        echo '  <th>' . __('Status')
            . PMA_Util::showMySQLDocu(
                'general-thread-states', 'general-thread-states'
            )
            .  '</th>' . "\n";
        echo '  <th>' . __('Time') . '</th>' . "\n";
        echo ' </tr>' .  "\n";

        $chart_json = Array();
        foreach ($profiling_results as $one_result) {
            echo ' <tr>' .  "\n";
            echo '<td>' . ucwords($one_result['Status']) . '</td>' .  "\n";
            echo '<td class="right">'
                . (PMA_Util::formatNumber($one_result['Duration'], 3, 1))
                . 's</td>' .  "\n";
            if (isset($chart_json[ucwords($one_result['Status'])])) {
                $chart_json[ucwords($one_result['Status'])]
                    += $one_result['Duration'];
            } else {
                $chart_json[ucwords($one_result['Status'])]
                    = $one_result['Duration'];
            }
        }

        echo '</table>' . "\n";
        echo '</div>';
        //require_once 'libraries/chart.lib.php';
        echo '<div id="profilingChartData" style="display:none;">';
        echo json_encode($chart_json);
        echo '</div>';
        echo '<div id="profilingchart" style="display:none;">';
        echo '</div>';
        echo '<script type="text/javascript">';
        echo 'if($.jqplot !== undefined && $.jqplot.PieRenderer !== undefined) {';
        echo 'makeProfilingChart();';
        echo '}';
        echo '</script>';
        echo '</fieldset>' . "\n";
    }

    // Displays the results in a table
    if (empty($disp_mode)) {
        // see the "PMA_setDisplayMode()" function in
        // libraries/DisplayResults.class.php
        $disp_mode = 'urdr111101';
    }

    $resultSetContainsUniqueKey = PMA_resultSetContainsUniqueKey(
        $db, $table, $fields_meta
    );

    // hide edit and delete links:
    // - for information_schema
    // - if the result set does not contain all the columns of a unique key
    //   and we are not just browing all the columns of an updatable view
    $updatableView
        = $justBrowsing
        && trim($analyzed_sql[0]['select_expr_clause']) == '*'
        && PMA_Table::isUpdatableView($db, $table);
    $editable = $resultSetContainsUniqueKey || $updatableView;
    if (!empty($table) && (PMA_is_system_schema($db) || !$editable)) {
        $disp_mode = 'nnnn110111';
        $msg = PMA_message::notice(
            __(
                'This table does not contain a unique column.'
                . ' Grid edit, checkbox, Edit, Copy and Delete features'
                . ' are not available.'
            )
        );
        $msg->display();
    }

    if (isset($label)) {
        $msg = PMA_message::success(__('Bookmark %s created'));
        $msg->addParam($label);
        $msg->display();
    }

    // Should be initialized these parameters before parsing
    $showtable = isset($showtable) ? $showtable : null;
    $printview = isset($printview) ? $printview : null;
    $url_query = isset($url_query) ? $url_query : null;

    if (! empty($sql_data) && ($sql_data['valid_queries'] > 1) || $is_procedure) {

        $_SESSION['is_multi_query'] = true;
        echo getTableHtmlForMultipleQueries(
            $displayResultsObject, $db, $sql_data, $goto,
            $pmaThemeImage, $text_dir, $printview, $url_query,
            $disp_mode, $sql_limit_to_append, $editable
        );
    } else {
        $_SESSION['is_multi_query'] = false;
        $displayResultsObject->setProperties(
            $unlim_num_rows, $fields_meta, $is_count, $is_export, $is_func,
            $is_analyse, $num_rows, $fields_cnt, $querytime, $pmaThemeImage,
            $text_dir, $is_maint, $is_explain, $is_show, $showtable,
            $printview, $url_query, $editable
        );

        echo $displayResultsObject->getTable($result, $disp_mode, $analyzed_sql);
        PMA_DBI_free_result($result);
    }

    // BEGIN INDEX CHECK See if indexes should be checked.
    if (isset($query_type)
        && $query_type == 'check_tbl'
        && isset($selected)
        && is_array($selected)
    ) {
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

        echo '<form action="sql.php" method="post"'
            . ' onsubmit="return ! emptyFormElements(this, \'fields[label]\');"'
            . ' id="bookmarkQueryForm">';
        echo PMA_generate_common_hidden_inputs();
        echo '<input type="hidden" name="goto" value="' . $goto . '" />';
        echo '<input type="hidden" name="fields[dbase]"'
            . ' value="' . htmlspecialchars($db) . '" />';
        echo '<input type="hidden" name="fields[user]"'
            . ' value="' . $cfg['Bookmark']['user'] . '" />';
        echo '<input type="hidden" name="fields[query]"' . ' value="'
            . urlencode(isset($complete_query) ? $complete_query : $sql_query)
            . '" />';
        echo '<fieldset>';
        echo '<legend>';
        echo PMA_Util::getIcon(
            'b_bookmark.png', __('Bookmark this SQL query'), true
        );
        echo '</legend>';
        echo '<div class="formelement">';
        echo '<label for="fields_label_">' . __('Label') . ':</label>';
        echo '<input type="text" id="fields_label_"'
            . ' name="fields[label]" value="" />';
        echo '</div>';
        echo '<div class="formelement">';
        echo '<input type="checkbox" name="bkm_all_users"'
            . ' id="bkm_all_users" value="true" />';
        echo '<label for="bkm_all_users">'
            . __('Let every user access this bookmark')
            . '</label>';
        echo '</div>';
        echo '<div class="clearfloat"></div>';
        echo '</fieldset>';
        echo '<fieldset class="tblFooters">';
        echo '<input type="hidden" name="store_bkm" value="1" />';
        echo '<input type="submit"'
            . ' value="' . __('Bookmark this SQL query') . '" />';
        echo '</fieldset>';
        echo '</form>';
    } // end bookmark support

    // Do print the page if required
    if (isset($printview) && $printview == '1') {
        echo PMA_Util::getButton();
    } // end print case
    echo '</div>'; // end sqlqueryresults div
} // end rows returned

$_SESSION['is_multi_query'] = false;

/**
 * Displays the footer
 */
if (! isset($_REQUEST['table_maintenance'])) {
    exit;
}


// These functions will need for use set the required parameters for display results

/**
 * Initialize some parameters needed to display results
 *
 * @param string  $sql_query SQL statement
 * @param boolean $is_select select query or not
 *
 * @return  array set of parameters
 *
 * @access  public
 */
function PMA_getDisplayPropertyParams($sql_query, $is_select)
{
    $is_explain = $is_count = $is_export = $is_delete = $is_insert = $is_affected = $is_show = $is_maint = $is_analyse = $is_group = $is_func = $is_replace = false;

    if ($is_select) {
        $is_group = preg_match('@(GROUP[[:space:]]+BY|HAVING|SELECT[[:space:]]+DISTINCT)[[:space:]]+@i', $sql_query);
        $is_func =  ! $is_group && (preg_match('@[[:space:]]+(SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND)\s*\(@i', $sql_query));
        $is_count = ! $is_group && (preg_match('@^SELECT[[:space:]]+COUNT\((.*\.+)?.*\)@i', $sql_query));
        $is_export   = preg_match('@[[:space:]]+INTO[[:space:]]+OUTFILE[[:space:]]+@i', $sql_query);
        $is_analyse  = preg_match('@[[:space:]]+PROCEDURE[[:space:]]+ANALYSE@i', $sql_query);
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

    return array(
        $is_group, $is_func, $is_count, $is_export, $is_analyse, $is_explain,
        $is_delete, $is_affected, $is_insert, $is_replace,$is_show, $is_maint
    );
}

/**
 * Get the database name inside a USE query
 *
 * @param string $sql       SQL query
 * @param array  $databases array with all databases
 *
 * @return strin $db new database name
 */
function PMA_getNewDatabase($sql, $databases)
{
    $db = '';
    // loop through all the databases
    foreach ($databases as $database) {
        if (strpos($sql, $database['SCHEMA_NAME']) !== false) {
            $db = $database;
            break;
        }
    }
    return $db;
}

/**
 * Get the table name in a sql query
 * If there are several tables in the SQL query,
 * first table wil lreturn
 *
 * @param string $sql    SQL query
 * @param array  $tables array of names in current database
 *
 * @return string $table table name
 */
function PMA_getTableNameBySQL($sql, $tables)
{
    $table = '';

    // loop through all the tables in the database
    foreach ($tables as $tbl) {
        if (strpos($sql, $tbl)) {
            $table .= ' ' . $tbl;
        }
    }

    if (count(explode(' ', trim($table))) > 1) {
        $tmp_array = explode(' ', trim($table));
        return $tmp_array[0];
    }

    return trim($table);
}


/**
 * Generate table html when SQL statement have multiple queries
 * which return displayable results
 *
 * @param PMA_DisplayResults $displayResultsObject object
 * @param string             $db                   database name
 * @param array              $sql_data             information about SQL statement
 * @param string             $goto                 URL to go back in case of errors
 * @param string             $pmaThemeImage        path for theme images directory
 * @param string             $text_dir             text direction
 * @param string             $printview            whether printview is enabled
 * @param string             $url_query            URL query
 * @param array              $disp_mode            the display mode
 * @param string             $sql_limit_to_append  limit clause
 * @param bool               $editable             whether result set is editable
 *
 * @return string   $table_html   html content
 */
function getTableHtmlForMultipleQueries(
    $displayResultsObject, $db, $sql_data, $goto, $pmaThemeImage,
    $text_dir, $printview, $url_query, $disp_mode, $sql_limit_to_append,
    $editable
) {
    $table_html = '';

    $tables_array = PMA_DBI_get_tables($db);
    $databases_array = PMA_DBI_get_databases_full();
    $multi_sql = implode(";", $sql_data['valid_sql']);
    $querytime_before = array_sum(explode(' ', microtime()));

    // Assignment for variable is not needed since the results are
    // looiping using the connection
    @PMA_DBI_try_multi_query($multi_sql);

    $querytime_after = array_sum(explode(' ', microtime()));
    $querytime = $querytime_after - $querytime_before;
    $sql_no = 0;

    do {
        $analyzed_sql = array();
        $is_affected = false;

        $result = PMA_DBI_store_result();
        $fields_meta = ($result !== false)
            ? PMA_DBI_get_fields_meta($result)
            : array();
        $fields_cnt  = count($fields_meta);

        // Initialize needed params related to each query in multiquery statement
        if (isset($sql_data['valid_sql'][$sql_no])) {
            // 'Use' query can change the database
            if (stripos($sql_data['valid_sql'][$sql_no], "use ")) {
                $db = PMA_getNewDatabase(
                    $sql_data['valid_sql'][$sql_no],
                    $databases_array
                );
            }
            $parsed_sql = PMA_SQP_parse($sql_data['valid_sql'][$sql_no]);
            $table = PMA_getTableNameBySQL(
                $sql_data['valid_sql'][$sql_no],
                $tables_array
            );

            $analyzed_sql = PMA_SQP_analyze($parsed_sql);
            $is_select = isset($analyzed_sql[0]['queryflags']['select_from']);
            $unlim_num_rows = PMA_Table::countRecords($db, $table, true);
            $showtable = PMA_Table::sGetStatusInfo($db, $table, null, true);
            $url_query = PMA_generate_common_url($db, $table);

            list($is_group, $is_func, $is_count, $is_export, $is_analyse,
                $is_explain, $is_delete, $is_affected, $is_insert, $is_replace,
                $is_show, $is_maint)
                    = PMA_getDisplayPropertyParams(
                        $sql_data['valid_sql'][$sql_no], $is_select
                    );

            // Handle remembered sorting order, only for single table query
            if ($GLOBALS['cfg']['RememberSorting']
                && ! ($is_count || $is_export || $is_func || $is_analyse)
                && isset($analyzed_sql[0]['select_expr'])
                && (count($analyzed_sql[0]['select_expr']) == 0)
                && isset($analyzed_sql[0]['queryflags']['select_from'])
                && count($analyzed_sql[0]['table_ref']) == 1
            ) {
                PMA_handleSortOrder(
                    $db,
                    $table,
                    $analyzed_sql,
                    $sql_data['valid_sql'][$sql_no]
                );
            }

            // Do append a "LIMIT" clause?
            if (($_SESSION['tmp_user_values']['max_rows'] != 'all')
                && ! ($is_count || $is_export || $is_func || $is_analyse)
                && isset($analyzed_sql[0]['queryflags']['select_from'])
                && ! isset($analyzed_sql[0]['queryflags']['offset'])
                && empty($analyzed_sql[0]['limit_clause'])
            ) {
                $sql_limit_to_append = ' LIMIT '
                    . $_SESSION['tmp_user_values']['pos']
                    . ', ' . $_SESSION['tmp_user_values']['max_rows'] . " ";
                $sql_data['valid_sql'][$sql_no] = PMA_getSqlWithLimitClause(
                    $sql_data['valid_sql'][$sql_no],
                    $analyzed_sql,
                    $sql_limit_to_append
                );
            }

            // Set the needed properties related to executing sql query
            $displayResultsObject->__set('db', $db);
            $displayResultsObject->__set('table', $table);
            $displayResultsObject->__set('goto', $goto);
        }

        if (! $is_affected) {
            $num_rows = ($result) ? @PMA_DBI_num_rows($result) : 0;
        } elseif (! isset($num_rows)) {
            $num_rows = @PMA_DBI_affected_rows();
        }

        if (isset($sql_data['valid_sql'][$sql_no])) {

            $displayResultsObject->__set(
                'sql_query',
                $sql_data['valid_sql'][$sql_no]
            );
            $displayResultsObject->setProperties(
                $unlim_num_rows, $fields_meta, $is_count, $is_export, $is_func,
                $is_analyse, $num_rows, $fields_cnt, $querytime, $pmaThemeImage,
                $text_dir, $is_maint, $is_explain, $is_show, $showtable,
                $printview, $url_query, $editable
            );
        }

        if ($num_rows == 0) {
            continue;
        }

        // With multiple results, operations are limied
        $disp_mode = 'nnnn000000';
        $is_limited_display = true;

        // Collect the tables
        $table_html .= $displayResultsObject->getTable(
            $result, $disp_mode, $analyzed_sql, $is_limited_display
        );

        // Free the result to save the memory
        PMA_DBI_free_result($result);

        $sql_no++;

    } while (PMA_DBI_more_results() && PMA_DBI_next_result());

    return $table_html;
}

/**
 * Handle remembered sorting order, only for single table query
 *
 * @param string $db              database name
 * @param string $table           table name
 * @param array  &$analyzed_sql   the analyzed query
 * @param string &$full_sql_query SQL query
 *
 * @return void
 */
function PMA_handleSortOrder($db, $table, &$analyzed_sql, &$full_sql_query)
{
    $pmatable = new PMA_Table($table, $db);
    if (empty($analyzed_sql[0]['order_by_clause'])) {
        $sorted_col = $pmatable->getUiProp(PMA_Table::PROP_SORTED_COLUMN);
        if ($sorted_col) {
            // retrieve the remembered sorting order for current table
            $sql_order_to_append = ' ORDER BY ' . $sorted_col . ' ';
            $full_sql_query = $analyzed_sql[0]['section_before_limit']
                . $sql_order_to_append . $analyzed_sql[0]['limit_clause']
                . ' ' . $analyzed_sql[0]['section_after_limit'];

            // update the $analyzed_sql
            $analyzed_sql[0]['section_before_limit'] .= $sql_order_to_append;
            $analyzed_sql[0]['order_by_clause'] = $sorted_col;
        }
    } else {
        // store the remembered table into session
        $pmatable->setUiProp(
            PMA_Table::PROP_SORTED_COLUMN,
            $analyzed_sql[0]['order_by_clause']
        );
    }
}

/**
 * Append limit clause to SQL query
 *
 * @param string $full_sql_query      SQL query
 * @param array  $analyzed_sql        the analyzed query
 * @param string $sql_limit_to_append clause to append
 *
 * @return string limit clause appended SQL query
 */
function PMA_getSqlWithLimitClause($full_sql_query, $analyzed_sql,
    $sql_limit_to_append
) {
    return $analyzed_sql[0]['section_before_limit'] . "\n"
        . $sql_limit_to_append . $analyzed_sql[0]['section_after_limit'];
}


/**
 * Get column name from a drop SQL statement
 *
 * @param string $sql SQL query
 *
 * @return string $drop_column Name of the column
 */
function PMA_getColumnNameInColumnDropSql($sql)
{
    $tmpArray1 = explode('DROP', $sql);
    $str_to_check = trim($tmpArray1[1]);

    if (stripos($str_to_check, 'COLUMN') !== false) {
        $tmpArray2 = explode('COLUMN', $str_to_check);
        $str_to_check = trim($tmpArray2[1]);
    }

    $tmpArray3 = explode(' ', $str_to_check);
    $str_to_check = trim($tmpArray3[0]);

    $drop_column = str_replace(';', '', trim($str_to_check));
    $drop_column = str_replace('`', '', $drop_column);

    return $drop_column;
}

/**
 * Verify whether the result set contains all the columns 
 * of at least one unique key
 *
 * @param string $db          database name
 * @param string $table       table name
 * @param string $fields_meta meta fields
 *
 * @return boolean whether the result set contains a unique key
 */
function PMA_resultSetContainsUniqueKey($db, $table, $fields_meta)
{
    $resultSetColumnNames = array();
    foreach ($fields_meta as $oneMeta) {
        $resultSetColumnNames[] = $oneMeta->name;
    }
    foreach (PMA_Index::getFromTable($table, $db) as $index) {
        if ($index->isUnique()) {
            $indexColumns = $index->getColumns();
            $numberFound = 0;
            foreach ($indexColumns as $indexColumnName => $dummy) {
                if (in_array($indexColumnName, $resultSetColumnNames)) {
                    $numberFound++;
                }
            }
            if ($numberFound == count($indexColumns)) {
                return true;
            }
        }
    }
    return false;
}

?>
