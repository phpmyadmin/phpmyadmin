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
             . (empty($primary_key) ? '' : '&amp;primary_key=' . $primary_key);
}

// Resets tables defined in the configuration file
if (isset($funcs)) {
    reset($funcs);
}

// Misc
$seen_binary = FALSE;

/**
 * Prepares the update of a row
 */
if (isset($primary_key) && ($submit_type != $strInsertAsNewRow)) {
    // Restore the "primary key" to a convenient format
    $primary_key = urldecode($primary_key);

    // Defines the SET part of the sql query
    $valuelist = '';

    foreach($fields AS $key => $val) {
        $encoded_key = $key;
        $key         = urldecode($key);

        require('./tbl_replace_fields.php');

        // No change for this column and no MySQL function is used -> next column
        if (empty($funcs[$encoded_key])
            && isset($fields_prev) && isset($fields_prev[$encoded_key])
            && ("'" . PMA_sqlAddslashes(urldecode($fields_prev[$encoded_key])) . "'" == $val)) {
            continue;
        }
        else if (!empty($val)) {
            if (empty($funcs[$encoded_key])) {
                $valuelist .= PMA_backquote($key) . ' = ' . $val . ', ';
            } else if ($val == '\'\''
                       && (preg_match('@^(NOW|CURDATE|CURTIME|UNIX_TIMESTAMP|RAND|USER|LAST_INSERT_ID)$@', $funcs[$encoded_key]))) {
                $valuelist .= PMA_backquote($key) . ' = ' . $funcs[$encoded_key] . '(), ';
            } else {
                $valuelist .= PMA_backquote($key) . ' = ' . $funcs[$encoded_key] . "($val), ";
            }
        }
    } // end while

    // Builds the sql update query
    $valuelist    = preg_replace('@, $@', '', $valuelist);
    if (!empty($valuelist)) {
        PMA_mysql_select_db($db);
        $query    = 'UPDATE ' . PMA_backquote($table) . ' SET ' . $valuelist . ' WHERE' . $primary_key
                  . ' LIMIT 1';
        $message  = $strAffectedRows . '&nbsp;';
    }
    // No change -> move back to the calling script
    else {
        $message = $strNoModification;
        if ($is_gotofile) {
            $js_to_run = 'functions.js';
            require_once('./header.inc.php');
            require('./' . preg_replace('@\.\.*@', '.', $goto));
        } else {
            header('Location: ' . $cfg['PmaAbsoluteUri'] . $goto . '&disp_message=' . urlencode($message) . '&disp_query=');
        }
        exit();
    }
} // end row update


/**
 *  Prepares the insert of a row
 */
else {
    PMA_mysql_select_db($db);

    $fieldlist = '';
    $valuelist = '';

    // garvin: Get, if sent, any protected fields to insert them here:
    if (isset($fields_type) && is_array($fields_type) && isset($primary_key)) {
        $prot_local_query = 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . urldecode($primary_key);
        $prot_result      = PMA_mysql_query($prot_local_query) or PMA_mysqlDie('', $prot_local_query, '', $err_url);
        $prot_row         = PMA_mysql_fetch_array($prot_result);
    }

    foreach($fields AS $key => $val) {
        $encoded_key = $key;
        $key         = urldecode($key);
        $fieldlist   .= PMA_backquote($key) . ', ';

        require('./tbl_replace_fields.php');

        if (empty($funcs[$encoded_key])) {
            $valuelist .= $val . ', ';
        } else if (($val == '\'\''
                   && preg_match('@^(UNIX_TIMESTAMP|RAND|LAST_INSERT_ID)$@', $funcs[$encoded_key]))
                   || preg_match('@^(NOW|CURDATE|CURTIME|USER)$@', $funcs[$encoded_key])) {
            $valuelist .= $funcs[$encoded_key] . '(), ';
        } else {
            $valuelist .= $funcs[$encoded_key] . '(' . $val . '), ';
        }
    } // end while

    // Builds the sql insert query
    $fieldlist = preg_replace('@, $@', '', $fieldlist);
    $valuelist = preg_replace('@, $@', '', $valuelist);
    $query     = 'INSERT INTO ' . PMA_backquote($table) . ' (' . $fieldlist . ') VALUES (' . $valuelist . ')';
    $message   = $strInsertedRows . '&nbsp;';
} // end row insertion


/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
$sql_query = $query . ';';
$result    = PMA_mysql_query($query);
if (!$result) {
    $error = PMA_mysql_error();
    require_once('./header.inc.php');
    PMA_mysqlDie($error, '', '', $err_url);
} else {
    if (@mysql_affected_rows()) {
        $message .= @mysql_affected_rows();
    } else {
        $message = $strModifications;
    }
    $insert_id = mysql_insert_id();
    if ($insert_id != 0) {
        $message .= '<br />'.$strInsertedRowId . '&nbsp;' . $insert_id;
    }
    if ($is_gotofile) {
        if ($goto == 'db_details.php' && !empty($table)) {
            unset($table);
        }
        $js_to_run = 'functions.js';
        $active_page = $goto;
        require_once('./header.inc.php');
        require('./' . preg_replace('@\.\.*@', '.', $goto));
    } else {
        // I don't understand this one:
        //$add_query = (strpos(' ' . $goto, 'tbl_change') ? '&disp_query=' . urlencode($sql_query) : '');

        // if we have seen binary,
        // we do not append the query to the Location so it won't be displayed
        // on the resulting page
        // Nijel: we also need to limit size of url...
        $add_query = (!$seen_binary && strlen($sql_query) < 1024 ? '&disp_query=' . urlencode($sql_query) : '');
        header('Location: ' . $cfg['PmaAbsoluteUri'] . $goto . '&disp_message=' . urlencode($message) . $add_query);
    }
    exit();
} // end if
?>
