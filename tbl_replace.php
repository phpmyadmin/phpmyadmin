<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

// Check parameters
PMA_checkParameters(array('db','table','goto'));

PMA_DBI_select_db($db);

/**
 * Initializes some variables
 */
// Defines the url to return in case of success of the query
if (isset($sql_query)) {
    $sql_query = urldecode($sql_query);
}
if (!isset($dontlimitchars)) {
    $dontlimitchars = 0;
}
$is_gotofile = FALSE;
if (isset($after_insert) && $after_insert == 'new_insert') {
    $goto = 'tbl_change.php?'
          . PMA_generate_common_url($db, $table, '&')
          . '&goto=' . urlencode($goto)
          . '&pos=' . $pos
          . '&session_max_rows=' . $session_max_rows
          . '&disp_direction=' . $disp_direction
          . '&repeat_cells=' . $repeat_cells
          . '&dontlimitchars=' . $dontlimitchars
          . (empty($sql_query) ? '' : '&sql_query=' . urlencode($sql_query));
} elseif (isset($after_insert) && $after_insert == 'same_insert') {
    $goto = 'tbl_change.php?'
          . PMA_generate_common_url($db, $table, '&')
          . '&goto=' . urlencode($goto)
          . '&pos=' . $pos
          . '&session_max_rows=' . $session_max_rows
          . '&disp_direction=' . $disp_direction
          . '&repeat_cells=' . $repeat_cells
          . '&dontlimitchars=' . $dontlimitchars
          . (empty($sql_query) ? '' : '&sql_query=' . urlencode($sql_query));
    if (isset($primary_key)) {
        foreach ($primary_key AS $pk) {
            $goto .= '&primary_key[]=' . $pk;
        }
    }
} elseif (isset($after_insert) && $after_insert == 'edit_next') {
    $goto = 'tbl_change.php?'
          . PMA_generate_common_url($db, $table, '&')
          . '&goto=' . urlencode($goto)
          . '&pos=' . $pos
          . '&session_max_rows=' . $session_max_rows
          . '&disp_direction=' . $disp_direction
          . '&repeat_cells=' . $repeat_cells
          . '&dontlimitchars=' . $dontlimitchars
          . (empty($sql_query) ? '' : '&sql_query=' . urlencode($sql_query));
    if (isset($primary_key)) {
        foreach ($primary_key AS $pk) {
            $local_query    = 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . str_replace('` =', '` >', urldecode($pk)) . ' LIMIT 1;';
            $res            = PMA_DBI_query($local_query);
            $row            = PMA_DBI_fetch_row($res);
            $meta           = PMA_DBI_get_fields_meta($res);
            $goto .= '&primary_key[]=' . urlencode(PMA_getUvaCondition($res, count($row), $meta, $row));
        }
    }
} else if ($goto == 'sql.php') {
    $goto = 'sql.php?'
          . PMA_generate_common_url($db, $table, '&')
          . '&pos=' . $pos
          . '&session_max_rows=' . $session_max_rows
          . '&disp_direction=' . $disp_direction
          . '&repeat_cells=' . $repeat_cells
          . '&dontlimitchars=' . $dontlimitchars
          . '&sql_query=' . urlencode($sql_query);
} else if (!empty($goto)) {
    // Security checkings
    $is_gotofile     = preg_replace('@^([^?]+).*$@', '\\1', $goto);
    if (!@file_exists('./' . $is_gotofile)) {
        $goto        = (empty($table)) ? 'db_details.php' : 'tbl_properties.php';
        $is_gotofile = TRUE;
    } else {
        $is_gotofile = ($is_gotofile == $goto);
    }
}

// Defines the url to return in case of failure of the query
if (isset($err_url)) {
    $err_url = urldecode($err_url);
} else {
    $err_url = str_replace('&', '&amp;', $goto)
             . (empty($primary_key) ? '' : '&amp;primary_key=' . (is_array($primary_key) ? $primary_key[0] : $primary_key));
}

// Misc
$seen_binary = FALSE;

/**
 * Prepares the update/insert of a row
 */
if (isset($primary_key)) {
    // we were editing something => use primary key
    $loop_array = (is_array($primary_key) ? $primary_key : array(0 => $primary_key));
    $using_key  = TRUE;
    $is_insert  = ($submit_type == $strInsertAsNewRow);
} else {
    // new row => use indexes
    $loop_array = array();
    for ($i = 0; $i < $cfg['InsertRows']; $i++) $loop_array[$i] = $i;
    $using_key  = FALSE;
    $is_insert  = TRUE;
}

$query = array();
$message = '';

foreach ($loop_array AS $primary_key_index => $enc_primary_key) {
    // skip fields to be ignored
    if (!$using_key && isset($GLOBALS['insert_ignore_' . $enc_primary_key])) continue;

    // Restore the "primary key" to a convenient format
    $primary_key = urldecode($enc_primary_key);

    // Defines the SET part of the sql query
    $valuelist = '';
    $fieldlist = '';

    // Map multi-edit keys to single-level arrays, dependent on how we got the fields
    $me_fields      = isset($fields['multi_edit'])      && isset($fields['multi_edit'][$enc_primary_key])      ? $fields['multi_edit'][$enc_primary_key]      : null;
    $me_fields_prev = isset($fields_prev['multi_edit']) && isset($fields_prev['multi_edit'][$enc_primary_key]) ? $fields_prev['multi_edit'][$enc_primary_key] : null;
    $me_funcs       = isset($funcs['multi_edit'])       && isset($funcs['multi_edit'][$enc_primary_key])       ? $funcs['multi_edit'][$enc_primary_key]       : null;
    $me_fields_type = isset($fields_type['multi_edit']) && isset($fields_type['multi_edit'][$enc_primary_key]) ? $fields_type['multi_edit'][$enc_primary_key] : null;
    $me_fields_null = isset($fields_null['multi_edit']) && isset($fields_null['multi_edit'][$enc_primary_key]) ? $fields_null['multi_edit'][$enc_primary_key] : null;

    if ($using_key && isset($me_fields_type) && is_array($me_fields_type) && isset($primary_key)) {
        $prot_result      = PMA_DBI_query('SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . $primary_key . ';');
        $prot_row         = PMA_DBI_fetch_assoc($prot_result);
        PMA_DBI_free_result($prot_result);
        unset($prot_result);
    }

    foreach ($me_fields AS $encoded_key => $val) {
        $key         = urldecode($encoded_key);
        $fieldlist   .= PMA_backquote($key) . ', ';

        require('./tbl_replace_fields.php');

        if (empty($me_funcs[$encoded_key])) {
            $cur_value = $val . ', ';
        } else if (preg_match('@^(UNIX_TIMESTAMP)$@', $me_funcs[$encoded_key]) && $val != '\'\'') {
            $cur_value = $me_funcs[$encoded_key] . '(' . $val . '), ';
        } else if (preg_match('@^(NOW|CURDATE|CURTIME|UNIX_TIMESTAMP|RAND|USER|LAST_INSERT_ID)$@', $me_funcs[$encoded_key])) {
            $cur_value = $me_funcs[$encoded_key] . '(), ';
        } else {
            $cur_value = $me_funcs[$encoded_key] . '(' . $val . '), ';
        }

        if ($is_insert) {
            // insert, no need to add column
            $valuelist .= $cur_value;
        } else if (empty($me_funcs[$encoded_key])
            && isset($me_fields_prev) && isset($me_fields_prev[$encoded_key])
            && ("'" . PMA_sqlAddslashes(urldecode($me_fields_prev[$encoded_key])) . "'" == $val)) {
            // No change for this column and no MySQL function is used -> next column
            continue;
        }
        else if (!empty($val)) {
            $valuelist .= PMA_backquote($key) . ' = ' . $cur_value;
        }
    } // end while

    // get rid of last ,
    $valuelist    = preg_replace('@, $@', '', $valuelist);

    // Builds the sql query
    if ($is_insert) {
        if (empty($query)) {
            // first inserted row -> prepare template
            $fieldlist = preg_replace('@, $@', '', $fieldlist);
            $query = array('INSERT INTO ' . PMA_backquote($table) . ' (' . $fieldlist . ') VALUES ');
        }
        // append current values
        $query[0]  .= '(' . $valuelist . '), ';
        $message   = $strInsertedRows . '&nbsp;';
    } elseif (!empty($valuelist)) {
        // build update query
        $query[]   = 'UPDATE ' . PMA_backquote($table) . ' SET ' . $valuelist . ' WHERE' . $primary_key . ' LIMIT 1';

        $message  = $strAffectedRows . '&nbsp;';
    }
} // end for

// trim last , from insert query
if ($is_insert) {
    $query[0] = preg_replace('@, $@', '', $query[0]);
}

if (empty($valuelist) && empty($query)) {
    // No change -> move back to the calling script
    $message = $strNoModification;
    if ($is_gotofile) {
        $js_to_run = 'functions.js';
        require_once('./header.inc.php');
        require('./' . PMA_securePath($goto));
    } else {
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . $goto . '&disp_message=' . urlencode($message) . '&disp_query=');

    }
    exit();
}

/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
$sql_query = implode(';', $query) . ';';
$total_affected_rows = 0;
$last_message = '';

foreach ($query AS $query_index => $single_query) {
    if ($cfg['IgnoreMultiSubmitErrors']) {
        $result = PMA_DBI_try_query($single_query);
    } else {
        $result = PMA_DBI_query($single_query);
    }
    if (!$result) {
        $message .= PMA_DBI_getError();
    } else {
        if (@PMA_DBI_affected_rows()) {
            $total_affected_rows += @PMA_DBI_affected_rows();
        }

        $insert_id = PMA_DBI_insert_id();
        if ($insert_id != 0) {
            $last_message .= '[br]'.$strInsertedRowId . '&nbsp;' . $insert_id;
        }
    } // end if
    PMA_DBI_free_result($result);
    unset($result);
}

if ($total_affected_rows != 0) {
    $message .= $total_affected_rows;
} else {
    $message .= $strModifications;
}

$message .= $last_message;

if ($is_gotofile) {
    if ($goto == 'db_details.php' && !empty($table)) {
        unset($table);
    }
    $js_to_run = 'functions.js';
    $active_page = $goto;
    require_once('./header.inc.php');
    require('./' . PMA_securePath($goto));
} else {
    // I don't understand this one:
    //$add_query = (strpos(' ' . $goto, 'tbl_change') ? '&disp_query=' . urlencode($sql_query) : '');

    // if we have seen binary,
    // we do not append the query to the Location so it won't be displayed
    // on the resulting page
    // Nijel: we also need to limit size of url...
    $add_query = (!$seen_binary && strlen($sql_query) < 1024 ? '&disp_query=' . urlencode($sql_query) : '');
    PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . $goto . '&disp_message=' . urlencode($message) . $add_query);
}
exit();
?>
