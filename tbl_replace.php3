<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');


/**
 * Initializes some variables
 */
// Defines the url to return in case of success of the query
if (isset($sql_query)) {
    $sql_query = urldecode($sql_query);
}
if (isset($after_insert) && $after_insert == 'new_insert') {
    $goto = 'tbl_change.php3'
          . '?lang=' . $lang
          . '&server=' . $server
          . '&db=' . urlencode($db)
          . '&table=' . urlencode($table)
          . '&goto=' . urlencode($goto)
          . (empty($primary_key) ? '' : '&primary_key=' . $primary_key)
          . '&pos=' . $pos
          . '&session_max_rows=' . $session_max_rows
          . '&disp_direction=' . $disp_direction
          . '&repeat_cells=' . $repeat_cells
          . (empty($sql_query) ? '' : '&sql_query=' . urlencode($sql_query));
} else if ($goto == 'sql.php3') {
    $goto = 'sql.php3?'
          . 'lang=' . $lang
          . '&server=' . $server
          . '&db=' . urlencode($db)
          . '&table=' . urlencode($table)
          . '&pos=' . $pos
          . '&session_max_rows=' . $session_max_rows
          . '&disp_direction=' . $disp_direction
          . '&repeat_cells=' . $repeat_cells
          . '&sql_query=' . urlencode($sql_query);
}
// Defines the url to return in case of failure of the query
if (isset($url_err)) {
    $url_err = urldecode($url_err);
} else {
    $url_err = str_replace('&', '&amp;', $goto);
}
// Resets tables defined in the configuration file
reset($fields);
reset($funcs);
// Misc
if (get_magic_quotes_gpc()) {
    $submit_type = stripslashes($submit_type);
}


/**
 * Prepares the update of a row
 */
if (isset($primary_key) && ($submit_type != $strInsertAsNewRow)) {
    // Restore the "primary key" to a convenient format
    $primary_key = urldecode($primary_key);

    // Defines the SET part of the sql query
    $valuelist = '';
    while (list($key, $val) = each($fields)) {
        $encoded_key = $key;
        $key         = urldecode($key);

        switch (strtolower($val)) {
            case 'null':
                break;
            case '$enum$':
                // if we have an enum, then construct the value
                $f = 'field_' . md5($key);
                if (!empty($$f)) {
                    $val     = implode(',', $$f);
                    if ($val == 'null') {
                        // void
                    } else {
                        $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                    }
                } else {
                    $val     = "''";
                }
                break;
            case '$set$':
                // if we have a set, then construct the value
                $f = 'field_' . md5($key);
                if (!empty($$f)) {
                    $val = implode(',', $$f);
                    $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                } else {
                    $val = "''";
                }
                break;
            default:
                if (get_magic_quotes_gpc()) {
                    $val = "'" . str_replace('\\"', '"', $val) . "'";
                } else {
                    $val = "'" . PMA_sqlAddslashes($val) . "'";
                }
                break;
        } // end switch

        // Was the Null checkbox checked for this field?
        if (isset($fields_null) && isset($fields_null[$encoded_key])) {
            $val = 'NULL';
        }

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
                       && (ereg('^(NOW|CURDATE|CURTIME|UNIX_TIMESTAMP|RAND|USER|LAST_INSERT_ID)$', $funcs[$encoded_key]))) {
                $valuelist .= PMA_backquote($key) . ' = ' . $funcs[$encoded_key] . '(), ';
            } else {
                $valuelist .= PMA_backquote($key) . ' = ' . $funcs[$encoded_key] . "($val), ";
            }
        }
    } // end while

    // Builds the sql upate query
    $valuelist    = ereg_replace(', $', '', $valuelist);
    if (!empty($valuelist)) {
        $query    = 'UPDATE ' . PMA_backquote($table) . ' SET ' . $valuelist . ' WHERE' . $primary_key
                  . ((PMA_MYSQL_INT_VERSION >= 32300) ? ' LIMIT 1' : '');
        $message  = $strAffectedRows . '&nbsp;';
    }
    // No change -> move back to the calling script
    else {
        $message = $strNoModification;
        if (@file_exists('./' . $goto)) {
            $js_to_run = 'functions.js';
            include('./header.inc.php3');
            include('./' . ereg_replace('\.\.*', '.', $goto));
        } else {
            header('Location: ' . $cfgPmaAbsoluteUri . $goto . '&message=' . urlencode($message));
        }
        exit();
    }
} // end row update


/**
 *  Prepares the insert of a row
 */
else {
    $fieldlist = '';
    $valuelist = '';
    while (list($key, $val) = each($fields)) {
        $encoded_key = $key;
        $key         = urldecode($key);
        $fieldlist   .= PMA_backquote($key) . ', ';

        switch (strtolower($val)) {
            case 'null':
                break;
            case '$enum$':
                // if we have a set, then construct the value
                $f = 'field_' . md5($key);
                if (!empty($$f)) {
                    $val     = implode(',', $$f);
                    if ($val == 'null') {
                        // void
                    } else {
                        $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                    }
                } else {
                    $val     = "''";
                }
                break;
            case '$set$':
                // if we have a set, then construct the value
                $f = 'field_' . md5($key);
                if (!empty($$f)) {
                    $val = implode(',', $$f);
                    $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                } else {
                    $val = "''";
                }
                break;
            default:
                if (get_magic_quotes_gpc()) {
                    $val = "'" . str_replace('\\"', '"', $val) . "'";
                } else {
                    $val = "'" . PMA_sqlAddslashes($val) . "'";
                }
                break;
        } // end switch

        // Was the Null checkbox checked for this field?
        if (isset($fields_null) && isset($fields_null[$encoded_key])) {
            $val = 'NULL';
        }

        if (empty($funcs[$encoded_key])) {
            $valuelist .= $val . ', ';
        } else if ($val == '\'\''
                   && (ereg('^(NOW|CURDATE|CURTIME|UNIX_TIMESTAMP|RAND|USER|LAST_INSERT_ID)$', $funcs[$encoded_key]))) {
            $valuelist .= $funcs[$encoded_key] . '(), ';
        } else {
            $valuelist .= $funcs[$encoded_key] . '(' . $val . '), ';
        }
    } // end while

    // Builds the sql insert query
    $fieldlist = ereg_replace(', $', '', $fieldlist);
    $valuelist = ereg_replace(', $', '', $valuelist);
    $query     = 'INSERT INTO ' . PMA_backquote($table) . ' (' . $fieldlist . ') VALUES (' . $valuelist . ')';
    $message   = $strInsertedRows . '&nbsp;';
} // end row insertion


/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
mysql_select_db($db);
$sql_query = $query . ';';
$result    = mysql_query($query);

if (!$result) {
    $error = mysql_error();
    include('./header.inc.php3');
    PMA_mysqlDie($error, '', '', $url_err);
} else {
    if (@mysql_affected_rows()) {
        $message .= @mysql_affected_rows();
    } else {
        $message = $strModifications;
    }
    if (@file_exists('./' . $goto)) {
        if ($goto == 'db_details.php3' && !empty($table)) {
            unset($table);
        }
        $js_to_run = 'functions.js';
        include('./header.inc.php3');
        include('./' . ereg_replace('\.\.*', '.', $goto));
    } else {
        $add_query = (strpos(' ' . $goto, 'tbl_change') ? '&disp_query=' . urlencode($sql_query) : '');
        header('Location: ' . $cfgPmaAbsoluteUri . $goto . '&message=' . urlencode($message) . $add_query);
    }
    exit();
} // end if
?>
