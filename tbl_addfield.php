<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Get some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
$js_to_run = 'functions.js';
require_once('./header.inc.php');

// Check parameters
PMA_checkParameters(array('db', 'table'));


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php?' . PMA_generate_common_url($db, $table);


/**
 * The form used to define the field to add has been submitted
 */
$abort = false;
if (isset($submit)) {
    $query = '';

    // Transforms the radio button field_key into 3 arrays
    $field_cnt = count($field_name);
    for ($i = 0; $i < $field_cnt; ++$i) {
        if (isset(${'field_key_' . $i})) {
            if (${'field_key_' . $i} == 'primary_' . $i) {
                $field_primary[] = $i;
            }
            if (${'field_key_' . $i} == 'index_' . $i) {
                $field_index[]   = $i;
            }
            if (${'field_key_' . $i} == 'unique_' . $i) {
                $field_unique[]  = $i;
            }
        } // end if
    } // end for
    // Builds the field creation statement and alters the table
    for ($i = 0; $i < $field_cnt; ++$i) {
        if (empty($field_name[$i])) {
            continue;
        }

        $query .= PMA_backquote($field_name[$i]) . ' ' . $field_type[$i];
        if ($field_length[$i] != ''
            && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i', $field_type[$i])) {
            $query .= '(' . $field_length[$i] . ')';
        }
        if ($field_attribute[$i] != '') {
            $query .= ' ' . $field_attribute[$i];
        } else if (PMA_MYSQL_INT_VERSION >= 40100 && $field_charset[$i] != '') {
            $query .= ' CHARACTER SET ' . $field_charset[$i];
        }
        if ($field_default[$i] != '') {
            if (strtoupper($field_default[$i]) == 'NULL') {
                $query .= ' DEFAULT NULL';
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

        if ($after_field != '--end--') {
            // Only the first field can be added somewhere else than at the end
            if ($i == 0) {
                if ($after_field == '--first--') {
                    $query .= ' FIRST';
                } else {
                    $query .= ' AFTER ' . PMA_backquote(urldecode($after_field));
                }
            } else {
                $query .= ' AFTER ' . PMA_backquote($field_name[$i-1]);
            }
        }
        $query .= ', ADD ';
    } // end for
    $query = preg_replace('@, ADD $@', '', $query);

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    $sql_query     = 'USE ' . PMA_backquote($db);
    $result        = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD ' . $query;
    $error_create = false;
    $result        = PMA_mysql_query($sql_query)  or $error_create = true;

    if ($error_create == false) {

        $sql_query_cpy = $sql_query . ';';

        // Builds the primary keys statements and updates the table
        $primary = '';
        if (isset($field_primary)) {
            $primary_cnt = count($field_primary);
            for ($i = 0; $i < $primary_cnt; $i++) {
                $j       = $field_primary[$i];
                if (!empty($field_name[$j])) {
                    $primary .= PMA_backquote($field_name[$j]) . ', ';
                }
            } // end for
            $primary     = preg_replace('@, $@', '', $primary);
            if (!empty($primary)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD PRIMARY KEY (' . $primary . ')';
                $result         = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if

        // Builds the indexes statements and updates the table
        $index = '';
        if (isset($field_index)) {
            $index_cnt = count($field_index);
            for ($i = 0; $i < $index_cnt; $i++) {
                $j     = $field_index[$i];
                if (!empty($field_name[$j])) {
                    $index .= PMA_backquote($field_name[$j]) . ', ';
                }
            } // end for
            $index     = preg_replace('@, $@', '', $index);
            if (!empty($index)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX (' . $index . ')';
                $result         = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if

        // Builds the uniques statements and updates the table
        $unique = '';
        if (isset($field_unique)) {
            $unique_cnt = count($field_unique);
            for ($i = 0; $i < $unique_cnt; $i++) {
                $j      = $field_unique[$i];
                if (!empty($field_name[$j])) {
                    $unique .= PMA_backquote($field_name[$j]) . ', ';
                }
            } // end for
            $unique = preg_replace('@, $@', '', $unique);
            if (!empty($unique)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE (' . $unique . ')';
                $result         = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if


        // Builds the fulltext statements and updates the table
        $fulltext = '';
        if (isset($field_fulltext)) {
            $fulltext_cnt = count($field_fulltext);
            for ($i = 0; $i < $fulltext_cnt; $i++) {
                $j        = $field_fulltext[$i];
                $fulltext .= PMA_backquote($field_name[$j]) . ', ';
            } // end for
            $fulltext = preg_replace('@, $@', '', $fulltext);
            if (!empty($fulltext)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT (' . $fulltext . ')';
                $result         = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if

        // garvin: If comments were sent, enable relation stuff
        require_once('./libraries/relation.lib.php');
        require_once('./libraries/transformations.lib.php');

        $cfgRelation = PMA_getRelationsParam();

        // garvin: Update comment table, if a comment was set.
        if (isset($field_comments) && is_array($field_comments) && $cfgRelation['commwork']) {
            foreach($field_comments AS $fieldindex => $fieldcomment) {
                PMA_setComment($db, $table, $field_name[$fieldindex], $fieldcomment);
            }
        }

        // garvin: Update comment table for mime types [MIME]
        if (isset($field_mimetype) && is_array($field_mimetype) && $cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME']) {
            foreach($field_mimetype AS $fieldindex => $mimetype) {
                PMA_setMIME($db, $table, $field_name[$fieldindex], $mimetype, $field_transformation[$fieldindex], $field_transformation_options[$fieldindex]);
            }
        }

        // Go back to the structure sub-page
        $sql_query = $sql_query_cpy;
        unset($sql_query_cpy);
        $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
        $active_page = 'tbl_properties_structure.php';
        require('./tbl_properties_structure.php');
    } else {
        PMA_mysqlDie('', '', '', $err_url, FALSE);
        // garvin: An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in tbl_properties.inc.php
        $num_fields = $orig_num_fields;
        if (isset($orig_after_field)) {
            $after_field = $orig_after_field;
        }
        $regenerate = true;
    }
} // end do alter table

/**
 * Displays the form used to define the new field
 */
if ($abort == FALSE) {
    $action = 'tbl_addfield.php';
    require('./tbl_properties.inc.php');

    // Diplays the footer
    echo "\n";
    require_once('./footer.inc.php');
}

?>
