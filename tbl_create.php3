<?php
/* $Id$ */


/**
 * Get some core libraries
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');


/**
 * Selects the database to work with
 */
mysql_select_db($db);


/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($submit)) {
    if (!isset($query)) {
        $query = '';
    }

    // Builds the fields creation statements
    for ($i = 0; $i < count($field_name); $i++) {
        if (empty($field_name[$i])) {
            continue;
        }
        $query .= backquote($field_name[$i]) . ' ' . $field_type[$i];
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
            if (get_magic_quotes_gpc()) {
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
    } // end for
    $query = ereg_replace(', $', '', $query);

    // Builds the primary keys statements
    if (!isset($primary)) {
        $primary = '';
    }
    if (!isset($field_primary)) {
        $field_primary = array();
    }
    for ($i = 0; $i < count($field_primary); $i++) {
        $j = $field_primary[$i];
        if (!empty($field_name[$j])) {
            $primary .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    $primary = ereg_replace(', $', '', $primary);
    if (!empty($primary)) {
        $primary = ', PRIMARY KEY (' . $primary . ')';
    }

    // Builds the indexes statements
    if (!isset($index)) {
        $index = '';
    }
    if (!isset($field_index)) {
        $field_index = array();
    }
    for ($i = 0;$i < count($field_index); $i++) {
        $j = $field_index[$i];
        if (!empty($field_name[$j])) {
           $index .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    $index = ereg_replace(', $', '', $index);
    if (!empty($index)) {
        $index = ', INDEX (' . $index . ')';
    }

    // Builds the uniques statements
    if (!isset($unique)) {
        $unique = '';
    }
    if (!isset($field_unique)) {
        $field_unique = array();
    }
    for ($i = 0; $i < count($field_unique); $i++) {
        $j = $field_unique[$i];
        if (!empty($field_name[$j])) {
           $unique .= backquote($field_name[$j]) . ', ';
        }
    } // end for
    $unique = ereg_replace(', $', '', $unique);
    if (!empty($unique)) {
        $unique = ', UNIQUE (' . $unique . ')';
    }
    $query_keys = $primary . $index . $unique;
    $query_keys = ereg_replace(', $', '', $query_keys);

    // Builds the 'create table' statement
    $sql_query = 'CREATE TABLE ' . backquote($table) . ' ('
               . $query . ' '
               . $query_keys . ')';
    // Adds table type (2 May 2001 - Robbat2)
    if (!empty($tbl_type) && ($tbl_type != 'Default')) {
        $sql_query .= ' TYPE = ' . $tbl_type;
    }
    if (MYSQL_INT_VERSION >= 32300 && !empty($comment)) {
        $sql_query .= ' comment = \'' . sql_addslashes($comment) . '\'';
    }

    // Executes the query
    $result  = mysql_query($sql_query) or mysql_die();
    $message = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenCreated;
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
        $action = 'tbl_create.php3';
        include('./tbl_properties.inc.php3');
       // Diplays the footer
       echo "\n";
       include('./footer.inc.php3');
   }
}

?>
