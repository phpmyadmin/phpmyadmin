<?php
/* $Id$ */


/**
 * Functions to wrap around MySQL database functions. This is basically made
 * to allow charset conversion, but later may be useful for other stuff.
 */



if (!defined('PMA_MYSQL_WRAPPERS_LIB_INCLUDED')){
    define('PMA_MYSQL_WRAPPERS_LIB_INCLUDED', 1);

    function PMA_mysql_dbname($result, $row, $field = FALSE) {
        if ($field != FALSE) {
            return PMA_convert_display_charset(mysql_dbname($result, $row, $field));
        } else {
            return PMA_convert_display_charset(mysql_dbname($result, $row));
        }
    }

    function PMA_mysql_error($id = FALSE) {
        if ($id != FALSE) {
            return PMA_convert_display_charset(mysql_error($param));
        } else {
            return PMA_convert_display_charset(mysql_error());
        }
    }

    function PMA_mysql_fetch_array($result, $type = FALSE) {
        if ($type != FALSE) {
            return PMA_convert_display_charset(mysql_fetch_array($result, $type));
        } else {
            return PMA_convert_display_charset(mysql_fetch_array($result));
        }
    }

    function PMA_mysql_fetch_field($result , $field_offset = FALSE) {
        if ($field_offset != FALSE) {
            return PMA_convert_display_charset(mysql_fetch_field($result, $field_offset));
        } else {
            return PMA_convert_display_charset(mysql_fetch_field($result));
        }
    }

    function PMA_mysql_fetch_object($result) {
        return PMA_convert_display_charset(mysql_fetch_object($result));
    }

    function PMA_mysql_fetch_row($result) {
        return PMA_convert_display_charset(mysql_fetch_row($result));
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

    function PMA_mysql_list_fields($database_name, $table_name, $link_identifier = FALSE) {
        if ($link_identifier != FALSE) {
            return mysql_list_fields($database_name, PMA_convert_charset($table_name), $link_identifier);
        } else {
            return mysql_list_fields($database_name, PMA_convert_charset($table_name));
        }
    }

    function PMA_mysql_list_tables($database_name, $link_identifier = FALSE) {
        if ($link_identifier != FALSE) {
            return mysql_list_tables(PMA_convert_charset($database_name), $link_identifier);
        } else {
            return mysql_list_tables(PMA_convert_charset($database_name));
        }
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


} // PMA_MYSQL_WRAPPERS_LIB_INCLUDED
?>
