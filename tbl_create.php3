<?php
/* $Id$ */


/**
 * Get some core libraries
 */
require('./libraries/grab_globals.lib.php3');
if (isset($submit)) {
    $js_to_run = 'functions.js';
}
require('./header.inc.php3');


/**
 * Selects the database to work with
 */
mysql_select_db($db);


/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($submit)) {
    $sql_query = $query_cpy = '';

    // Builds the fields creation statements
    $fields_cnt = count($field_name);
    for ($i = 0; $i < $fields_cnt; $i++) {
        if (empty($field_name[$i])) {
            continue;
        }
        if (get_magic_quotes_gpc()) {
            $field_name[$i] = stripslashes($field_name[$i]);
        }
        if (MYSQL_INT_VERSION < 32306) {
            check_reserved_words($field_name[$i]);
        }
        $query = backquote($field_name[$i]) . ' ' . $field_type[$i];
        if ($field_length[$i] != '') {
            if (get_magic_quotes_gpc()) {
                $query .= '(' . stripslashes($field_length[$i]) . ')';
            } else {
                $query .= '(' . $field_length[$i] . ')';
            }
        }
        if ($field_attribute[$i] != '') {
            $query .= ' ' . $field_attribute[$i];
        }
        if ($field_default[$i] != '') {
            if (strtoupper($field_default[$i]) == 'NULL') {
                $query .= ' DEFAULT NULL';
            } else if (get_magic_quotes_gpc()) {
                $query .= ' DEFAULT \'' . sql_addslashes(stripslashes($field_default[$i])) . '\'';
            } else {
                $query .= ' DEFAULT \'' . sql_addslashes($field_default[$i]) . '\'';
            }
        }
        if ($field_null[$i] != '') {
            $query .= ' ' . $field_null[$i];
        }
        if ($field_extra[$i] != '') {
            $query .= ' ' . $field_extra[$i] . ', ';
        } else {
            $query .= ', ';
        }
        $sql_query .= $query;
        $query_cpy .= "\n" . '  ' . $query;
    } // end for
    unset($fields_cnt);
    unset($query);
    $sql_query = ereg_replace(', $', '', $sql_query);
    $query_cpy = ereg_replace(', $', '', $query_cpy);

    // Builds the primary keys statements
    $primary     = '';
    $primary_cnt = (isset($field_primary) ? count($field_primary) : 0);
    for ($i = 0; $i < $primary_cnt; $i++) {
        $j = $field_primary[$i];
        if (!empty($field_name[$j])) {
            if (get_magic_quotes_gpc()) {
                $field_name[$j] = stripslashes($field_name[$j]);
            }
            $primary .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($primary_cnt);
    $primary = ereg_replace(', $', '', $primary);
    if (!empty($primary)) {
        $sql_query .= ', PRIMARY KEY (' . $primary . ')';
        $query_cpy .= ',' . "\n" . '  PRIMARY KEY (' . $primary . ')';
    }
    unset($primary);

    // Builds the indexes statements
    $index     = '';
    $index_cnt = (isset($field_index) ? count($field_index) : 0);
    for ($i = 0;$i < $index_cnt; $i++) {
        $j = $field_index[$i];
        if (!empty($field_name[$j])) {
            if (get_magic_quotes_gpc()) {
                $field_name[$j] = stripslashes($field_name[$j]);
            }
            $index .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($index_cnt);
    $index = ereg_replace(', $', '', $index);
    if (!empty($index)) {
        $sql_query .= ', INDEX (' . $index . ')';
        $query_cpy .= ',' . "\n" . '  INDEX (' . $index . ')';
    }
    unset($index);

    // Builds the uniques statements
    $unique     = '';
    $unique_cnt = (isset($field_unique) ? count($field_unique) : 0);
    for ($i = 0; $i < $unique_cnt; $i++) {
        $j = $field_unique[$i];
        if (!empty($field_name[$j])) {
            if (get_magic_quotes_gpc()) {
                $field_name[$j] = stripslashes($field_name[$j]);
            }
           $unique .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($unique_cnt);
    $unique = ereg_replace(', $', '', $unique);
    if (!empty($unique)) {
        $sql_query .= ', UNIQUE (' . $unique . ')';
        $query_cpy .= ',' . "\n" . '  UNIQUE (' . $unique . ')';
    }
    unset($unique);

    // Builds the fulltextes statements
    $fulltext     = '';
    $fulltext_cnt = (isset($field_fulltext) ? count($field_fulltext) : 0);
    for ($i = 0; $i < $fulltext_cnt; $i++) {
        $j = $field_fulltext[$i];
        if (!empty($field_name[$j])) {
            if (get_magic_quotes_gpc()) {
                $field_name[$j] = stripslashes($field_name[$j]);
            }
           $fulltext .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($field_fulltext);
    $fulltext = ereg_replace(', $', '', $fulltext);
    if (!empty($fulltext)) {
        $sql_query .= ', FULLTEXT (' . $fulltext . ')';
        $query_cpy .= ',' . "\n" . '  FULLTEXT (' . $fulltext . ')';
    }
    unset($fulltext);

    // Builds the 'create table' statement
    $sql_query      = 'CREATE TABLE ' . backquote($table) . ' (' . $sql_query . ')';
    $query_cpy      = 'CREATE TABLE ' . backquote($table) . ' (' . $query_cpy . "\n" . ')';

    // Adds table type and comments (2 May 2001 - Robbat2)
    if (!empty($tbl_type) && ($tbl_type != 'Default')) {
        $sql_query .= ' TYPE = ' . $tbl_type;
        $query_cpy .= ' TYPE = ' . $tbl_type;
    }
    if (MYSQL_INT_VERSION >= 32300 && !empty($comment)) {
        if (get_magic_quotes_gpc()) {
            $comment = stripslashes($comment);
        }
        $sql_query .= ' COMMENT = \'' . sql_addslashes($comment) . '\'';
        $query_cpy .= "\n" . 'COMMENT = \'' . sql_addslashes($comment) . '\'';
    }

    // Executes the query
    $result    = mysql_query($sql_query) or mysql_die();
    $sql_query = $query_cpy . ';';
    unset($query_cpy);
    $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenCreated;
    include('./tbl_properties.php3');
    exit();
} // end do create table


/**
 * Displays the form used to define the structure of the table
 */
else {
    if (isset($num_fields)) {
        $num_fields = intval($num_fields);
    }
    // No table name
    if (!isset($table) || trim($table) == '') {
        mysql_die($strTableEmpty);
    }
    // No valid number of fields
    else if (empty($num_fields) || !is_int($num_fields)) {
        mysql_die($strFieldsEmpty);
    }
    // Table name and number of fields are valid -> show the form
    else {
        // Ensures the table name is valid
        if (get_magic_quotes_gpc()) {
            $table = stripslashes($table);
        }
        if (MYSQL_INT_VERSION < 32306) {
            check_reserved_words($table);
        }

        $action = 'tbl_create.php3';
        include('./tbl_properties.inc.php3');
        // Diplays the footer
        echo "\n";
        include('./footer.inc.php3');
   }
}

?>
