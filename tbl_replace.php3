<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');


/**
 * Initializes some variables
 */
// Defines the url to return in case of success of the query
if (isset($sql_query)) {
    $sql_query = urldecode($sql_query);
}
if ($goto == 'sql.php3') {
    $goto  = 'sql.php3?'
           . 'lang=' . $lang
           . '&server=' . urlencode($server)
           . '&db=' . urlencode($db)
           . '&table=' . urlencode($table)
           . '&pos=' . $pos
           . '&sql_query=' . urlencode($sql_query);
}
// Resets tables defined in the configuration file
reset($fields);
reset($funcs);
// Misc
$is_encoded = FALSE;
if (isset($submit_type)) {
    if (get_magic_quotes_gpc()) {
        $submit_type = stripslashes($submit_type);
    }
    // values have been urlencoded in tbl_change.php3
    if ($submit_type == $strSave || $submit_type == $strInsertAsNewRow) {
        $is_encoded = TRUE;
    }
}


/**
 * Prepares the update of a row
 */
if (isset($primary_key) && ($submit_type != $strInsertAsNewRow)) {
    // Restore the "primary key" to a convenient format
    if ($is_encoded) {
        $primary_key = urldecode($primary_key);
    }
    else if (get_magic_quotes_gpc()) {
        $primary_key = stripslashes($primary_key);
    }

    // Defines the SET part of the sql query
    $valuelist = '';
    while (list($key, $val) = each($fields)) {
        if ($is_encoded) {
            $key = urldecode($key);
        }

        switch (strtolower($val)) {
            case 'null':
                break;
            case '$enum$':
                // if we have an enum, then construct the value
                if ($is_encoded) {
                    $f = 'field_' . md5($key);
                } else {
                    $f = 'field_' . $key;
                }
                if (!empty($$f)) {
                    $val     = implode(',', $$f);
                    if ($val == 'null') {
                        // void
                    } else if ($is_encoded) {
                        $val = "'" . sql_addslashes(urldecode($val)) . "'";
                    } else if (get_magic_quotes_gpc()) {
                        $val = "'" . str_replace('\\"', '"', $val) . "'";
                    } else {
                        $val = "'" . sql_addslashes($val) . "'";
                    }
                } else {
                    $val = "''";
                }
                break;
            case '$set$':
                // if we have a set, then construct the value
                if ($is_encoded) {
                    $f = 'field_' . md5($key);
                } else {
                    $f = 'field_' . $key;
                }
                if (!empty($$f)) {
                    $val    = implode(',', $$f);
                    if ($is_encoded) {
                        $val = "'" . sql_addslashes(urldecode($val)) . "'";
                    } else if (get_magic_quotes_gpc()) {
                        $val = "'" . str_replace('\\"', '"', $val) . "'";
                    } else {
                        $val = "'" . sql_addslashes($val) . "'";
                    }
                } else {
                    $val = "''";
                }
                break;
            default:
                if (get_magic_quotes_gpc()) {
                    $val = "'" . str_replace('\\"', '"', $val) . "'";
                } else {
                    $val = "'" . sql_addslashes($val) . "'";
                }
                break;
        } // end switch

        // No change for this column -> next column
        if (isset($fields_prev) && isset($fields_prev[urlencode($key)])
            && ("'" . sql_addslashes(urldecode($fields_prev[urlencode($key)])) . "'" == $val)) {
            continue;
        }
        else if (!empty($val)) {
            if (empty($funcs[$key])) {
                $valuelist .= backquote($key) . ' = ' . $val . ', ';
            } else {
                $valuelist .= backquote($key) . " = $funcs[$key]($val), ";
            }
        }
    } // end while

    // Builds the sql upate query
    $valuelist = ereg_replace(', $', '', $valuelist);
    if (!empty($valuelist)) {
        $query = 'UPDATE ' . backquote($table) . ' SET ' . $valuelist . ' WHERE' . $primary_key;
    }
    // No change -> move back to the calling script
    else {
        if (file_exists('./' . $goto)) {
            include('./header.inc.php3');
            $message = $strNoModification;
            include('./' . ereg_replace('\.\.*', '.', $goto));
        } else {
            header('Location: ' . $cfgPmaAbsoluteUri . $goto);
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
        if ($is_encoded) {
            $key = urldecode($key);
        }
        // the 'query' row is urlencoded in sql.php3
        else if ($key == 'query') {
            $val = urldecode($val);
        }
        $fieldlist .= backquote($key) . ', ';

        switch (strtolower($val)) {
            case 'null':
                break;
            case '$enum$':
                // if we have a set, then construct the value
                if ($is_encoded) {
                    $f = 'field_' . md5($key);
                } else {
                    $f = 'field_' . $key;
                }
                if (!empty($$f)) {
                    $val     = implode(',', $$f);
                    if ($val == 'null') {
                        // void
                    } else if ($is_encoded) {
                        $val = "'" . sql_addslashes(urldecode($val)) . "'";
                    } else if (get_magic_quotes_gpc()) {
                        $val = "'" . str_replace('\\"', '"', $val) . "'";
                    } else {
                        $val = "'" . sql_addslashes($val) . "'";
                    }
                } else {
                    $val     = "''";
                }
                break;
            case '$set$':
                // if we have a set, then construct the value
                if ($is_encoded) {
                    $f = 'field_' . md5($key);
                } else {
                    $f = 'field_' . $key;
                }
                if (!empty($$f)) {
                    $val    = implode(',', $$f);
                    if ($is_encoded) {
                        $val = "'" . sql_addslashes(urldecode($val)) . "'";
                    } else if (get_magic_quotes_gpc()) {
                        $val = "'" . str_replace('\\"', '"', $val) . "'";
                    } else {
                        $val = "'" . sql_addslashes($val) . "'";
                    }
                } else {
                    $val     = "''";
                }
                break;
            default:
                if (get_magic_quotes_gpc()) {
                    $val = "'" . str_replace('\\"', '"', $val) . "'";
                } else {
                    $val = "'" . sql_addslashes($val) . "'";
                }
                break;
        } // end switch

        if (empty($funcs[$key])) {
            $valuelist .= $val . ', ';
        } else {
            $valuelist .= "$funcs[$key]($val), ";
        }
    } // end while

    // Builds the sql insert query
    $fieldlist = ereg_replace(', $', '', $fieldlist);
    $valuelist = ereg_replace(', $', '', $valuelist);
    $query     = 'INSERT INTO ' . backquote($table) . ' (' . $fieldlist . ') VALUES (' . $valuelist . ')';
} // end row insertion


/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
mysql_select_db($db);
$sql_query = $query;
$result    = mysql_query($query);

if (!$result) {
    $error = mysql_error();
    include('./header.inc.php3');
    mysql_die($error);
} else {
    if (file_exists('./' . $goto)) {
        include('./header.inc.php3');
        $message = $strModifications;
        include('./' . ereg_replace('\.\.*', '.', $goto));
    } else {
        header('Location: ' . $cfgPmaAbsoluteUri . $goto);
    }
    exit();
} // end if
?>
