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
 * @uses    urlencode()
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

// Check parameters
PMA_checkParameters(array('db', 'table', 'goto'));

PMA_DBI_select_db($GLOBALS['db']);

/**
 * Initializes some variables
 */
$goto_include = false;

if (isset($_REQUEST['insert_rows']) && is_numeric($_REQUEST['insert_rows']) && $_REQUEST['insert_rows'] != $cfg['InsertRows']) {
    $cfg['InsertRows'] = $_REQUEST['insert_rows'];
    $js_to_run = 'tbl_change.js';
    require_once './libraries/header.inc.php';
    require './tbl_change.php';
    exit;
}

if (isset($_REQUEST['after_insert'])
 && in_array($_REQUEST['after_insert'], array('new_insert', 'same_insert', 'edit_next'))) {
    $url_params['after_insert'] = $_REQUEST['after_insert'];
    //$GLOBALS['goto'] = 'tbl_change.php';
    $goto_include = 'tbl_change.php';

    if (isset($_REQUEST['primary_key'])) {
        if ($_REQUEST['after_insert'] == 'same_insert') {
            foreach ($_REQUEST['primary_key'] as $pk) {
                $url_params['primary_key'][] = $pk;
            }
        } elseif ($_REQUEST['after_insert'] == 'edit_next') {
            foreach ($_REQUEST['primary_key'] as $pk) {
                $local_query    = 'SELECT * FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($GLOBALS['table'])
                                . ' WHERE ' . str_replace('` =', '` >', $pk)
                                . ' LIMIT 1;';
                $res            = PMA_DBI_query($local_query);
                $row            = PMA_DBI_fetch_row($res);
                $meta           = PMA_DBI_get_fields_meta($res);
                // must find a unique condition based on unique key,
                // not a combination of all fields
                if ($tmp = PMA_getUniqueCondition($res, count($meta), $meta, $row, true)) {
                    $_SESSION['edit_next'] = $tmp;
                }
                unset($tmp);
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
if (isset($_REQUEST['primary_key'])) {
    // we were editing something => use primary key
    $loop_array = (is_array($_REQUEST['primary_key']) ? $_REQUEST['primary_key'] : array($_REQUEST['primary_key']));
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
$message = '';
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
);

foreach ($loop_array as $rowcount => $primary_key) {
    // skip fields to be ignored
    if (! $using_key && isset($_REQUEST['insert_ignore_' . $primary_key])) {
        continue;
    }

    // Defines the SET part of the sql query
    $query_values = array();

    // Map multi-edit keys to single-level arrays, dependent on how we got the fields
    $me_fields =
        isset($_REQUEST['fields']['multi_edit'][$rowcount])
        ? $_REQUEST['fields']['multi_edit'][$rowcount]
        : array();
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

    foreach ($me_fields as $key => $val) {

        require './libraries/tbl_replace_fields.inc.php';

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
                    $query_fields[] = PMA_backquote($key);
                }
            }

        //  u p d a t e
        } elseif (!empty($me_fields_null_prev[$key])
         && !isset($me_fields_null[$key])) {
            // field had the null checkbox before the update
            // field no longer has the null checkbox
            $query_values[] = PMA_backquote($key) . ' = ' . $cur_value;
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
                $query_values[] = PMA_backquote($key) . ' = ' . $cur_value;
            }
        }
    } // end foreach ($me_fields as $key => $val)

    if (count($query_values) > 0) {
        if ($is_insert) {
            $value_sets[] = implode(', ', $query_values);
        } else {
            // build update query
            $query[] = 'UPDATE ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($GLOBALS['table'])
                    . ' SET ' . implode(', ', $query_values) . ' WHERE ' . $primary_key . ' LIMIT 1';

        }
    }
} // end foreach ($loop_array as $primary_key)
unset($me_fields_prev, $me_funcs, $me_fields_type, $me_fields_null, $me_fields_null_prev,
    $me_auto_increment, $cur_value, $key, $val, $loop_array, $primary_key, $using_key,
    $func_no_param);


// Builds the sql query
if ($is_insert && count($value_sets) > 0) {
    $query[] = 'INSERT INTO ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($GLOBALS['table'])
        . ' (' . implode(', ', $query_fields) . ') VALUES (' . implode('), (', $value_sets) . ')';

    unset($query_fields, $value_sets);

    $message .= $GLOBALS['strInsertedRows'] . '&nbsp;';
} elseif (! empty($query)) {
    $message .= $GLOBALS['strAffectedRows'] . '&nbsp;';
} else {
    // No change -> move back to the calling script
    $message .= $GLOBALS['strNoModification'];
    $js_to_run = 'functions.js';
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
$total_affected_rows = 0;
$last_message = '';
$warning_message = '';

foreach ($query as $single_query) {
    if ($GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
        $result = PMA_DBI_try_query($single_query);
    } else {
        $result = PMA_DBI_query($single_query);
    }
    
    if (! $result) {
        $message .= PMA_DBI_getError();
    } else {
        if (@PMA_DBI_affected_rows()) {
            $total_affected_rows += @PMA_DBI_affected_rows();
        }

        $insert_id = PMA_DBI_insert_id();
        if ($insert_id != 0) {
            // insert_id is id of FIRST record inserted in one insert, so if we
            // inserted multiple rows, we had to increment this

            if ($total_affected_rows > 0) {
                $insert_id = $insert_id + $total_affected_rows - 1;
            }
            $last_message .= '[br]' . $GLOBALS['strInsertedRowId'] . '&nbsp;' . $insert_id;
        }
        PMA_DBI_free_result($result);
    } // end if

    foreach (PMA_DBI_get_warnings() as $warning) {
        $warning_message .= $warning['Level'] . ': #' . $warning['Code'] 
            . ' ' . $warning['Message'] . '[br]';
    }
    
    unset($result);
}
unset($single_query, $query);

$message .= $total_affected_rows . $last_message;

if (! empty($warning_message)) {
    /**
     * @todo use a <div class="warning"> in PMA_showMessage() for this part of
     * the message
     */
    $message .= '[br]' . $warning_message;
}
unset($warning_message, $total_affected_rows, $last_message);

if (isset($return_to_sql_query)) {
    $disp_query = $GLOBALS['sql_query'];
    $disp_message = $message;
    unset($message);
    $GLOBALS['sql_query'] = $return_to_sql_query;
}

// if user asked to "Insert another new row", we need tbl_change.js
// otherwise the calendar icon does not work
if ($goto_include == 'tbl_change.php') {
    /**
     * @todo if we really need to run many different js at header time,
     * $js_to_run would become an array and header.inc.php would iterate
     * thru it, instead of the bunch of if/elseif it does now
     */
    $js_to_run = 'tbl_change.js';
} else {
    $js_to_run = 'functions.js';
}
$active_page = $goto_include;
require_once './libraries/header.inc.php';
require './' . PMA_securePath($goto_include);
exit;
?>
