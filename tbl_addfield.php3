<?php
/* $Id$ */


/**
 * Get some core libraries
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');


/**
 * The form used to define the field to add has been submitted
 */
if (isset($submit)) {
    $query = '';

    // Builds the field creation statement and alters the table
    for ($i = 0; $i < count($field_name); ++$i) {
        $query .= backquote($field_name[$i]) . ' ' . $field_type[$i];
        if ($field_length[$i] != ''
            && !eregi('^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$', $field_type[$i])) {
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
            $query .= ' ' . $field_extra[$i];
        }

        if ($after_field != '--end--') {
            // Only the first field can be added somewhere else than at the end
	        if ($i == 0) {
	            if ($after_field == '--first--') {
	                $query .= ' FIRST';
	            } else {
	                if (get_magic_quotes_gpc()) {
		                $query .= ' AFTER ' . backquote(stripslashes(urldecode($after_field)));
                    } else {
                        $query .= ' AFTER ' . backquote(urldecode($after_field));
	                }
	            }
	        } else {
	            if (get_magic_quotes_gpc()) {
	                $query .= ' AFTER ' . backquote(stripslashes($field_name[$i-1]));
	            } else {
	                $query .= ' AFTER ' . backquote($field_name[$i-1]);
	            }
	        }
	    }
        $query .= ', ADD ';
    } // end for
    if (get_magic_quotes_gpc()) {
        $query = stripslashes(ereg_replace(', ADD $', '', $query));
    } else {
        $query = ereg_replace(', ADD $', '', $query);
    }

    $sql_query = 'ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD ' . $query;
    $result    = mysql_query($sql_query) or mysql_die();

    // Builds the primary keys statements and updates the table
    $primary = '';
    if (isset($field_primary)) {
        for ($i = 0; $i < count($field_primary); $i++) {
            $j       = $field_primary[$i];
            $primary .= backquote($field_name[$j]) . ', ';
        } // end for
        $primary     = ereg_replace(', $', '', $primary);
        if (!empty($primary)) {
            $sql_query .= "\n" . 'ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD PRIMARY KEY (' . $primary . ')';
            $result    = mysql_query('ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD PRIMARY KEY (' . $primary . ')') or mysql_die();
        }
    } // end if
     
    // Builds the indexes statements and updates the table
    $index = '';
    if (isset($field_index)) {
        for ($i = 0; $i < count($field_index); $i++) {
            $j     = $field_index[$i];
            $index .= backquote($field_name[$j]) . ', ';
        } // end for
        $index     = ereg_replace(', $', '', $index);
        if (!empty($index)) {
            $sql_query .= "\n" . 'ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD INDEX (' . $index . ')';
            $result    = mysql_query('ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD INDEX (' . $index . ')') or mysql_die();
        }
    } // end if
     
    // Builds the uniques statements and updates the table
    $unique = '';
    if (isset($field_unique)) {
        for ($i = 0; $i < count($field_unique); $i++) {
            $j      = $field_unique[$i];
            $unique .= backquote($field_name[$j]) . ', ';
        } // end for
        $unique = ereg_replace(', $', '', $unique);
        if (!empty($unique)) {
            $sql_query .= "\n" . 'ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD UNIQUE (' . $unique . ')';
            $result    = mysql_query('ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' ADD UNIQUE (' . $unique . ')') or mysql_die();
        }
    } // end if
     
    // Go back to table properties
    $message = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
    include('./tbl_properties.php3');
    exit();
} // end do alter table

/**
 * Displays the form used to define the new field
 */
else{
    $action = 'tbl_addfield.php3';
    include('./tbl_properties.inc.php3');

    // Diplays the footer
    echo "\n";
    include('./footer.inc.php3');
}

?>
