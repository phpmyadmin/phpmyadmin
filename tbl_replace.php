<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * manipulation of table data like inserting, replacing and updating
 *
 * usally called as form action from tbl_change.php to insert or update table rows
 *
 * @version $Id$
 *
 * @todo 'edit_next' tends to not work as expected if used ... at least there is no order by
 *       it needs the original query and the row number and than replace the LIMIT clause
 * @uses    PMA_checkParameters()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_DBI_get_fields_meta()
 * @uses    PMA_DBI_free_result()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_DBI_getError()
 * @uses    PMA_DBI_affected_rows()
 * @uses    PMA_DBI_insert_id()
 * @uses    PMA_backquote()
 * @uses    PMA_getUniqueCondition()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_securePath()
 * @uses    PMA_sendHeaderLocation()
 * @uses    str_replace()
 * @uses    count()
 * @uses    file_exists()
 * @uses    strlen()
 * @uses    str_replace()
 * @uses    preg_replace()
 * @uses    is_array()
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['goto']
 * @uses    $GLOBALS['sql_query']
 * @uses    PMA_File::getRecentBLOBReference()
 * @package phpMyAdmin
 */

/**
 * do not import request variable into global scope
 *
 * cannot be used as long as it could happen that the $goto file that is included
 * at the end of this script is not updated to work without imported request variables
 *
 * @todo uncomment this if all possible included files to rely on import request variables
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
 */
/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
$blob_streaming_active = $_SESSION['PMA_Config']->get('BLOBSTREAMING_PLUGINS_EXIST');

// Check parameters
PMA_checkParameters(array('db', 'table', 'goto'));

PMA_DBI_select_db($GLOBALS['db']);

/**
 * Initializes some variables
 */
$goto_include = false;

if (isset($_REQUEST['insert_rows']) && is_numeric($_REQUEST['insert_rows']) && $_REQUEST['insert_rows'] != $cfg['InsertRows']) {
    $cfg['InsertRows'] = $_REQUEST['insert_rows'];
    $GLOBALS['js_include'][] = 'tbl_change.js';
    require_once './libraries/header.inc.php';
    require './tbl_change.php';
    exit;
}

if (isset($_REQUEST['after_insert'])
 && in_array($_REQUEST['after_insert'], array('new_insert', 'same_insert', 'edit_next'))) {
    $url_params['after_insert'] = $_REQUEST['after_insert'];
    //$GLOBALS['goto'] = 'tbl_change.php';
    $goto_include = 'tbl_change.php';

    if (isset($_REQUEST['where_clause'])) {
        if ($_REQUEST['after_insert'] == 'same_insert') {
            foreach ($_REQUEST['where_clause'] as $one_where_clause) {
                $url_params['where_clause'][] = $one_where_clause;
            }
        } elseif ($_REQUEST['after_insert'] == 'edit_next') {
            foreach ($_REQUEST['where_clause'] as $one_where_clause) {
                $local_query    = 'SELECT * FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($GLOBALS['table'])
                                . ' WHERE ' . str_replace('` =', '` >', $one_where_clause)
                                . ' LIMIT 1;';
                $res            = PMA_DBI_query($local_query);
                $row            = PMA_DBI_fetch_row($res);
                $meta           = PMA_DBI_get_fields_meta($res);
                // must find a unique condition based on unique key,
                // not a combination of all fields
                list($unique_condition, $clause_is_unique) = PMA_getUniqueCondition($res, count($meta), $meta, $row, true);
                if (! empty($unique_condition)) { 
                    $_SESSION['edit_next'] = $unique_condition;
                }
                unset($unique_condition, $clause_is_unique);
            }
        }
    }
} elseif (! empty($GLOBALS['goto'])) {
    if (! preg_match('@^[a-z_]+\.php$@', $GLOBALS['goto'])) {
        // this should NOT happen
        //$GLOBALS['goto'] = false;
        $goto_include = false;
    } else {
        $goto_include = $GLOBALS['goto'];
    }
    if ($GLOBALS['goto'] == 'db_sql.php' && strlen($GLOBALS['table'])) {
        $GLOBALS['table'] = '';
    }
}

if (! $goto_include) {
    if (! strlen($GLOBALS['table'])) {
        $goto_include = 'db_sql.php';
    } else {
        $goto_include = 'tbl_sql.php';
    }
}

// Defines the url to return in case of failure of the query
if (isset($_REQUEST['err_url'])) {
    $err_url = $_REQUEST['err_url'];
} else {
    $err_url = 'tbl_change.php' . PMA_generate_common_url($url_params);
}

/**
 * Prepares the update/insert of a row
 */
if (isset($_REQUEST['where_clause'])) {
    // we were editing something => use the WHERE clause
    $loop_array = (is_array($_REQUEST['where_clause']) ? $_REQUEST['where_clause'] : array($_REQUEST['where_clause']));
    $using_key  = true;
    $is_insert  = ($_REQUEST['submit_type'] == $GLOBALS['strInsertAsNewRow']);
} else {
    // new row => use indexes
    $loop_array = array();
    foreach ($_REQUEST['fields']['multi_edit'] as $key => $dummy) {
        $loop_array[] = $key;
    }
    $using_key  = false;
    $is_insert  = true;
}

$query = array();
$value_sets = array();
$func_no_param = array(
    'NOW',
    'CURDATE',
    'CURTIME',
    'UTC_DATE',
    'UTC_TIME',
    'UTC_TIMESTAMP',
    'UNIX_TIMESTAMP',
    'RAND',
    'USER',
    'LAST_INSERT_ID',
    'UUID',
    'CURRENT_USER',
);

foreach ($loop_array as $rowcount => $where_clause) {
    // skip fields to be ignored
    if (! $using_key && isset($_REQUEST['insert_ignore_' . $where_clause])) {
        continue;
    }

    // Defines the SET part of the sql query
    $query_values = array();

    // Map multi-edit keys to single-level arrays, dependent on how we got the fields
    $me_fields =
        isset($_REQUEST['fields']['multi_edit'][$rowcount])
        ? $_REQUEST['fields']['multi_edit'][$rowcount]
        : array();
    $me_fields_name =
        isset($_REQUEST['fields_name']['multi_edit'][$rowcount])
        ? $_REQUEST['fields_name']['multi_edit'][$rowcount]
        : null;
    $me_fields_prev =
        isset($_REQUEST['fields_prev']['multi_edit'][$rowcount])
        ? $_REQUEST['fields_prev']['multi_edit'][$rowcount]
        : null;
    $me_funcs =
        isset($_REQUEST['funcs']['multi_edit'][$rowcount])
        ? $_REQUEST['funcs']['multi_edit'][$rowcount]
        : null;
    $me_fields_type =
        isset($_REQUEST['fields_type']['multi_edit'][$rowcount])
        ? $_REQUEST['fields_type']['multi_edit'][$rowcount]
        : null;
    $me_fields_null =
        isset($_REQUEST['fields_null']['multi_edit'][$rowcount])
        ? $_REQUEST['fields_null']['multi_edit'][$rowcount]
        : null;
    $me_fields_null_prev =
        isset($_REQUEST['fields_null_prev']['multi_edit'][$rowcount])
        ? $_REQUEST['fields_null_prev']['multi_edit'][$rowcount]
        : null;
    $me_auto_increment =
        isset($_REQUEST['auto_increment']['multi_edit'][$rowcount])
        ? $_REQUEST['auto_increment']['multi_edit'][$rowcount]
        : null;

    if ($blob_streaming_active) {
        $primary_field = PMA_BS_GetPrimaryField($GLOBALS['db'], $GLOBALS['table']);
    }

    // Fetch the current values of a row to use in case we have a protected field
    // @todo possibly move to ./libraries/tbl_replace_fields.inc.php
    if ($is_insert && $using_key && isset($me_fields_type) && is_array($me_fields_type) && isset($where_clause)) {
        $prot_row = PMA_DBI_fetch_single_row('SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . $where_clause . ';');
    }
    foreach ($me_fields as $key => $val) {

        // Note: $key is an md5 of the fieldname. The actual fieldname is available in $me_fields_name[$key]

        require './libraries/tbl_replace_fields.inc.php';

        // rajk - for blobstreaming
	if ($blob_streaming_active && (NULL != $primary_field || strlen($primary_field) > 0)) {
            $remove_blob_repo = isset($_REQUEST['remove_blob_repo_' . $key]) ? $_REQUEST['remove_blob_repo_' . $key] : NULL;
            $upload_blob_repo = isset($_REQUEST['upload_blob_repo_' . $key]) ? $_REQUEST['upload_blob_repo_' . $key] : NULL;

            // checks if an existing blob repository reference should be removed
            if (isset($remove_blob_repo) && !isset($upload_blob_repo)) {
		$remove_blob_reference = $_REQUEST['remove_blob_ref_' . $key];
		if (isset($remove_blob_reference)) {
                    $val = "''";
		}

		// checks if this field requires a bs reference attached to it
		if (isset($upload_blob_repo)) {
			// get the most recent BLOB reference
                    $bs_reference = PMA_File::getRecentBLOBReference();

                    // if the most recent BLOB reference exists, set it as a field value
                    if (!is_null($bs_reference)) {
			$val = "'" . PMA_sqlAddslashes($bs_reference) . "'";
                    }
		}
            }
	}

        if (empty($me_funcs[$key])) {
            $cur_value = $val;
        } elseif ('UNIX_TIMESTAMP' === $me_funcs[$key] && $val != "''") {
            $cur_value = $me_funcs[$key] . '(' . $val . ')';
        } elseif (in_array($me_funcs[$key], $func_no_param)) {
            $cur_value = $me_funcs[$key] . '()';
        } else {
            $cur_value = $me_funcs[$key] . '(' . $val . ')';
        }

        //  i n s e r t
        if ($is_insert) {
            // no need to add column into the valuelist
            if (strlen($cur_value)) {
                $query_values[] = $cur_value;
                // first inserted row so prepare the list of fields
                if (empty($value_sets)) {
                    $query_fields[] = PMA_backquote($me_fields_name[$key]);
                }
            }

        //  u p d a t e
        } elseif (!empty($me_fields_null_prev[$key])
         && !isset($me_fields_null[$key])) {
            // field had the null checkbox before the update
            // field no longer has the null checkbox
            $query_values[] = PMA_backquote($me_fields_name[$key]) . ' = ' . $cur_value;
        } elseif (empty($me_funcs[$key])
         && isset($me_fields_prev[$key])
         && ("'" . PMA_sqlAddslashes($me_fields_prev[$key]) . "'" == $val)) {
            // No change for this column and no MySQL function is used -> next column
            continue;
        } elseif (! empty($val)) {
            // avoid setting a field to NULL when it's already NULL
            // (field had the null checkbox before the update
            //  field still has the null checkbox)
            if (!(! empty($me_fields_null_prev[$key])
             && isset($me_fields_null[$key]))) {
                 $query_values[] = PMA_backquote($me_fields_name[$key]) . ' = ' . $cur_value;
            }
        }
    } // end foreach ($me_fields as $key => $val)

    if (count($query_values) > 0) {
        if ($is_insert) {
            $value_sets[] = implode(', ', $query_values);
        } else {
            // build update query
            $query[] = 'UPDATE ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($GLOBALS['table'])
                . ' SET ' . implode(', ', $query_values) . ' WHERE ' . $where_clause . ($_REQUEST['clause_is_unique'] ? '' : ' LIMIT 1');

        }
    }
} // end foreach ($loop_array as $where_clause)
unset($me_fields_name, $me_fields_prev, $me_funcs, $me_fields_type, $me_fields_null, $me_fields_null_prev,
    $me_auto_increment, $cur_value, $key, $val, $loop_array, $where_clause, $using_key,
    $func_no_param);


// Builds the sql query
if ($is_insert && count($value_sets) > 0) {
    $query[] = 'INSERT INTO ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($GLOBALS['table'])
        . ' (' . implode(', ', $query_fields) . ') VALUES (' . implode('), (', $value_sets) . ')';

    unset($query_fields, $value_sets);

    $message = PMA_Message::success('strRowsInserted');
} elseif (! empty($query)) {
    $message = PMA_Message::success('strRowsAffected');
} else {
    // No change -> move back to the calling script
    $message = PMA_Message::success('strNoModification');
    $GLOBALS['js_include'][] = 'functions.js';
    $active_page = $goto_include;
    require_once './libraries/header.inc.php';
    require './' . PMA_securePath($goto_include);
    exit;
}
unset($me_fields, $is_insert);

/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
if (! empty($GLOBALS['sql_query'])) {
    $url_params['sql_query'] = $GLOBALS['sql_query'];
    $return_to_sql_query = $GLOBALS['sql_query'];
}
$GLOBALS['sql_query'] = implode('; ', $query) . ';';
// to ensure that the query is displayed in case of 
// "insert as new row" and then "insert another new row"
$GLOBALS['display_query'] = $GLOBALS['sql_query'];

$total_affected_rows = 0;
$last_messages = array();
$warning_messages = array();
$error_messages = array();

foreach ($query as $single_query) {
    if ($GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
        $result = PMA_DBI_try_query($single_query);
    } else {
        $result = PMA_DBI_query($single_query);
    }

    if (! $result) {
        $error_messages[] = PMA_DBI_getError();
    } else {
        // The next line contains a real assignment, it's not a typo
        if ($tmp = @PMA_DBI_affected_rows()) {
            $total_affected_rows += $tmp;
        }
        unset($tmp);

        $insert_id = PMA_DBI_insert_id();
        if ($insert_id != 0) {
            // insert_id is id of FIRST record inserted in one insert, so if we
            // inserted multiple rows, we had to increment this

            if ($total_affected_rows > 0) {
                $insert_id = $insert_id + $total_affected_rows - 1;
            }
            $last_message = PMA_Message::notice('strInsertedRowId');
            $last_message->addParam($insert_id);
            $last_messages[] = $last_message;
        }
        PMA_DBI_free_result($result);
    } // end if

    foreach (PMA_DBI_get_warnings() as $warning) {
        $warning_messages[] = $warning['Level'] . ': #' . $warning['Code']
            . ' ' . $warning['Message'];
    }

    unset($result);
}
unset($single_query, $query);

$message->addParam($total_affected_rows);
$message->addMessages($last_messages, '<br />');

if (! empty($warning_messages)) {
    /**
     * @todo use a <div class="warning"> in PMA_showMessage() for this part of
     * the message
     */
    $message->addMessages($warning_messages, '<br />');
    $message->isWarning(true);
}
if (! empty($error_messages)) {
    $message->addMessages($error_messages);
    $message->isError(true);
}
unset($error_messages, $warning_messages, $total_affected_rows, $last_messages, $last_message);

if (isset($return_to_sql_query)) {
    $disp_query = $GLOBALS['sql_query'];
    $disp_message = $message;
    unset($message);
    $GLOBALS['sql_query'] = $return_to_sql_query;
}

$GLOBALS['js_include'][] = 'tbl_change.js';
$GLOBALS['js_include'][] = 'functions.js';
// in case we call sql.php which needs those:
$GLOBALS['js_include'][] = 'mootools.js';

$active_page = $goto_include;

/**
 * If user asked for "and then Insert another new row" we have to remove
 * WHERE clause information so that tbl_change.php does not go back
 * to the current record
 */
if (isset($_REQUEST['after_insert']) && 'new_insert' == $_REQUEST['after_insert']) {
        unset($_REQUEST['where_clause']);
}

/**
 * Load header.
 */
require_once './libraries/header.inc.php';
/**
 * Load target page.
 */
require './' . PMA_securePath($goto_include);
exit;
?>
