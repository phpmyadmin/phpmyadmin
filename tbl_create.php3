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
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php3'
         . '?lang=' . $lang
         . '&amp;server=' . $server
         . '&amp;db=' . urlencode($db)
         . '&amp;table=' . urlencode($table);


/**
 * Selects the database to work with
 */
mysql_select_db($db);


/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($submit)) {
    $sql_query = $query_cpy = '';

    // transform the radio button field_key into 3 arrays
    $field_cnt = count($field_name);
    for ($i = 0; $i < $field_cnt; ++$i) {
        if (${'field_key_'.$i} == 'primary_'.$i) {
           $field_primary[]=$i;
        }
        if (${'field_key_'.$i} == 'index_'.$i) {
           $field_index[]=$i;
        }
        if (${'field_key_'.$i} == 'unique_'.$i) {
           $field_unique[]=$i;
        }
    }
    // Builds the fields creation statements
    for ($i = 0; $i < $field_cnt; $i++) {
        if (empty($field_name[$i])) {
            continue;
        }
        if (get_magic_quotes_gpc()) {
            $field_name[$i] = stripslashes($field_name[$i]);
        }
        if (PMA_MYSQL_INT_VERSION < 32306) {
            PMA_checkReservedWords($field_name[$i], $err_url);
        }
        $query = PMA_backquote($field_name[$i]) . ' ' . $field_type[$i];
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
                $query .= ' DEFAULT \'' . PMA_sqlAddslashes(stripslashes($field_default[$i])) . '\'';
            } else {
                $query .= ' DEFAULT \'' . PMA_sqlAddslashes($field_default[$i]) . '\'';
            }
        }
        if ($field_null[$i] != '') {
            $query .= ' ' . $field_null[$i];
        }
        if ($field_extra[$i] != '') {
            $query .= ' ' . $field_extra[$i];
            // An auto_increment field must be use as a primary key
            if ($field_extra[$i] == 'AUTO_INCREMENT' && isset($field_primary)) {
                $primary_cnt = count($field_primary);
                for ($j = 0; $j < $primary_cnt && $field_primary[$j] != $i; $j++) {
                    // void
                } // end for
                if ($field_primary[$j] == $i) {
                    $query .= ' PRIMARY KEY';
                    unset($field_primary[$j]);
                } // end if
            } // end if (auto_increment)
        }
        $query .= ', ';
        $sql_query .= $query;
        $query_cpy .= "\n" . '  ' . $query;
    } // end for
    unset($field_cnt);
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
            $primary .= PMA_backquote($field_name[$j]) . ', ';
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
            $index .= PMA_backquote($field_name[$j]) . ', ';
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
           $unique .= PMA_backquote($field_name[$j]) . ', ';
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
           $fulltext .= PMA_backquote($field_name[$j]) . ', ';
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
    $sql_query      = 'CREATE TABLE ' . PMA_backquote($table) . ' (' . $sql_query . ')';
    $query_cpy      = 'CREATE TABLE ' . PMA_backquote($table) . ' (' . $query_cpy . "\n" . ')';

    // Adds table type and comments (2 May 2001 - Robbat2)
    if (!empty($tbl_type) && ($tbl_type != 'Default')) {
        $sql_query .= ' TYPE = ' . $tbl_type;
        $query_cpy .= ' TYPE = ' . $tbl_type;
    }
    if (PMA_MYSQL_INT_VERSION >= 32300 && !empty($comment)) {
        if (get_magic_quotes_gpc()) {
            $comment = stripslashes($comment);
        }
        $sql_query .= ' COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
        $query_cpy .= "\n" . 'COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
    }

    // Executes the query
    $result    = mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
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
        PMA_mysqlDie($strTableEmpty, '', '', $err_url);
    }
    // No valid number of fields
    else if (empty($num_fields) || !is_int($num_fields)) {
        PMA_mysqlDie($strFieldsEmpty, '', '', $err_url);
    }
    // Table name and number of fields are valid -> show the form
    else {
        // Ensures the table name is valid
        if (get_magic_quotes_gpc()) {
            $table = stripslashes($table);
        }
        if (PMA_MYSQL_INT_VERSION < 32306) {
            PMA_checkReservedWords($table, $err_url);
        }

        $action = 'tbl_create.php3';
        include('./tbl_properties.inc.php3');
        // Diplays the footer
        echo "\n";
        include('./footer.inc.php3');
   }
}

?>
