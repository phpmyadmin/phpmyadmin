<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Functions to wrap around MySQL database functions. This is basically made
 * to allow charset conversion, but later may be useful for other stuff.
 */


// this one is no longer used:
function PMA_mysql_dbname($result, $row, $field = FALSE) {
    if ($field != FALSE) {
        return PMA_convert_display_charset(mysql_dbname($result, $row, $field));
    } else {
        return PMA_convert_display_charset(mysql_dbname($result, $row));
    }
}

function PMA_mysql_error($id = FALSE) {
    if ($id != FALSE) {
        if (mysql_errno($id) != 0) {
            return PMA_convert_display_charset('#' . mysql_errno($id) . ' - ' . mysql_error($id));
        }
    } elseif (mysql_errno() != 0) {
        return PMA_convert_display_charset('#' . mysql_errno() . ' - ' . mysql_error());
    }

    return FALSE;
}

function PMA_mysql_fetch_field($result , $field_offset = FALSE) {
    if ($field_offset != FALSE) {
        return PMA_convert_display_charset(mysql_fetch_field($result, $field_offset));
    } else {
        return PMA_convert_display_charset(mysql_fetch_field($result));
    }
}

function PMA_mysql_fetch_row($result) {
    /* nijel: This is not optimal, but keeps us from duplicating code, if
     * speed really matters, duplicate here code from PMA_mysql_fetch_array
     * with removing rows working with associative array. */
    return PMA_mysql_fetch_array($result, MYSQL_NUM);
}

function PMA_mysql_field_flags($result, $field_offset) {
    /* ne0x/swix: added a switch for the mysqli extention because mysqli does not know 
       the function mysqli_field_flags */

    global $cfg;
    
    switch ($cfg['Server']['extension']) {
        case "mysqli":
            $f = mysqli_fetch_field_direct($result, $field_offset);
            $f = $f->flags;
            $flags = '';
            while ($f > 0) {
                if (floor($f / 65536)) {
                    $flags .= 'unique ';
                    $f -= 65536;
                    continue;
                }
                if (floor($f / 32768)) {
                    $flags .= 'num ';
                    $f -= 32768;
                    continue;
                }
                if (floor($f / 16384)) {
                    $flags .= 'part_key ';
                    $f -= 16384;
                    continue;
                }
                if (floor($f / 2048)) {
                    $flags .= 'set ';
                    $f -= 2048;
                    continue;
                }
                if (floor($f / 1024)) {
                    $flags .= 'timestamp ';
                    $f -= 1024;
                    continue;
                }if (floor($f / 512)) {
                    $flags .= 'auto_increment ';
                    $f -= 512;
                    continue;
                }
                if (floor($f / 256)) {
                    $flags .= 'enum ';
                    $f -= 256;
                    continue;
                }
                if (floor($f / 128)) {
                    $flags .= 'binary ';
                    $f -= 128;
                    continue;
                }
                if (floor($f / 64)) {
                    $flags .= 'zerofill ';
                    $f -= 64;
                    continue;
                }
                if (floor($f / 32)) {
                    $flags .= 'unsigned ';
                    $f -= 32;
                    continue;
                }
                if (floor($f / 16)) {
                    $flags .= 'blob ';
                    $f -= 16;
                    continue;
                }
                if (floor($f / 8)) {
                    $flags .= 'multiple_key ';
                    $f -= 8;
                    continue;
                }
                if (floor($f / 4)) {
                    $flags .= 'unique_key ';
                    $f -= 4;
                    continue;
                }
                if (floor($f / 2)) {
                    $flags .= 'primary_key ';
                    $f -= 2;
                    continue;
                }
                if (floor($f / 1)) {
                    $flags .= 'not_null ';
                    $f -= 1;
                    continue;
                }
            }
            return PMA_convert_display_charset(trim($flags));

        case "mysql":
        default:
            return PMA_convert_display_charset(mysql_field_flags($result, $field_offset));


    }
}

function PMA_mysql_field_name($result, $field_index) {
    return PMA_convert_display_charset(mysql_field_name($result, $field_index));
}

function PMA_mysql_field_type($result, $field_index) {
    return PMA_convert_display_charset(mysql_field_type($result, $field_index));
}

function PMA_mysql_query($query, $link_identifier = FALSE, $result_mode = FALSE) {
    if ($link_identifier != FALSE) {
        if ($result_mode != FALSE) {
            return mysql_query(PMA_convert_charset($query), $link_identifier, $result_mode);
        } else {
            return mysql_query(PMA_convert_charset($query), $link_identifier);
        }
    } else {
        return mysql_query(PMA_convert_charset($query));
    }
}

// mysql_list_tables() is deprecated, also we got report about weird results
// under some circumstances

function PMA_mysql_list_tables($database_name, $link_identifier = FALSE) {
    if ($link_identifier != FALSE) {
        return PMA_mysql_query('SHOW TABLES FROM ' . PMA_backquote(PMA_convert_charset($database_name)), $link_identifier);
    } else {
        return PMA_mysql_query('SHOW TABLES FROM ' . PMA_backquote(PMA_convert_charset($database_name)));
    }
}

// mysql_list_fields() is deprecated, also we got report about weird results
// under some circumstances
//
// using SELECT * FROM db.table
// lets us use functions like mysql_field_name() on the result set

function PMA_mysql_list_fields_alternate($database_name, $table_name, $link_identifier = FALSE) {
    if ($link_identifier != FALSE) {
        $result = PMA_mysql_query('SHOW FIELDS FROM '
         . PMA_backquote(PMA_convert_charset($database_name)) . '.'
         . PMA_backquote(PMA_convert_charset($table_name)), $link_identifier);
    } else {
        $result = PMA_mysql_query('SHOW FIELDS FROM '
         . PMA_backquote(PMA_convert_charset($database_name)) . '.'
         . PMA_backquote(PMA_convert_charset($table_name)));
    }

    $fields = array();
    while ($row = PMA_mysql_fetch_array($result)) {
        $fields[] = $row;
    }

    return $fields;
}

function PMA_mysql_list_fields($database_name, $table_name, $link_identifier = FALSE) {
    if ($link_identifier != FALSE) {
        return mysql_list_fields(PMA_convert_charset($database_name), PMA_convert_charset($table_name), $link_identifier);
    } else {
        return mysql_list_fields(PMA_convert_charset($database_name), PMA_convert_charset($table_name));
    }
}

function PMA_mysql_result($result, $row, $field = FALSE) {
    if ($field != FALSE) {
        return PMA_convert_display_charset(mysql_result($result, $row, PMA_convert_charset($field)));
    } else {
        return PMA_convert_display_charset(mysql_result($result, $row));
    }
}

function PMA_mysql_select_db($database_name, $link_identifier = FALSE) {
    if ($link_identifier != FALSE) {
        return mysql_select_db(PMA_convert_charset($database_name), $link_identifier);
    } else {
        return mysql_select_db(PMA_convert_charset($database_name));
    }
}

function PMA_mysql_tablename($result, $i) {
    return PMA_convert_display_charset(mysql_tablename($result, $i));
}

?>
