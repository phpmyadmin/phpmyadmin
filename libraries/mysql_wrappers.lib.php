<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Functions to wrap around MySQL database functions. This is basically made
 * to allow charset conversion, but later may be useful for other stuff.
 */


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

function PMA_mysql_fetch_array($result, $type = FALSE) {
    global $cfg, $allow_recoding, $charset, $convcharset;

    if ($type != FALSE) {
        $data = mysql_fetch_array($result, $type);
    } else {
        $data = mysql_fetch_array($result);
    }
    if (!(isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)) {
        /* No recoding -> return data as we got them */
        return $data;
    } else {
        $ret = array();
        $num = mysql_num_fields($result);
        $i = 0;
        for($i = 0; $i < $num; $i++) {
            $meta = mysql_fetch_field($result);
            $name = mysql_field_name($result, $i);
            if (!$meta) {
                /* No meta information available -> we guess that it should be converted */
                if (isset($data[$i])) $ret[$i] = PMA_convert_display_charset($data[$i]);
                if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = PMA_convert_display_charset($data[$name]);
            } else {
                /* Meta information available -> check type of field and convert it according to the type */
                if ($meta->blob || stristr($meta->type, 'BINARY')) {
                    if (isset($data[$i])) $ret[$i] = $data[$i];
                    if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = $data[$name];
                } else {
                    if (isset($data[$i])) $ret[$i] = PMA_convert_display_charset($data[$i]);
                    if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = PMA_convert_display_charset($data[$name]);
                }
            }
        }
        return $ret;
    }
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
    return PMA_convert_display_charset(mysql_field_flags($result, $field_offset));
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
