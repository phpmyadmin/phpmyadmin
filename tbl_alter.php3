<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
if (!isset($submit_mult)) {
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
}


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties_structure.php3?' . PMA_generate_common_url($db, $table);


/**
 * Modifications have been submitted -> updates the table
 */
if (isset($submit)) {
    $field_cnt = count($field_orig);
    for ($i = 0; $i < $field_cnt; $i++) {
        if (PMA_MYSQL_INT_VERSION < 32306) {
            PMA_checkReservedWords($field_name[$i], $err_url);
        }

        // Some fields have been urlencoded or double quotes have been translated
        // to "&quot;" in tbl_properties.php3
        $field_orig[$i]     = urldecode($field_orig[$i]);
        if (strcmp(str_replace('"', '&quot;', $field_orig[$i]), $field_name[$i]) == 0) {
            $field_name[$i] = $field_orig[$i];
        }
        $field_default_orig[$i] = urldecode($field_default_orig[$i]);
        if (strcmp(str_replace('"', '&quot;', $field_default_orig[$i]), $field_default[$i]) == 0) {
            $field_default[$i]  = $field_default_orig[$i];
        }
        $field_length_orig[$i] = urldecode($field_length_orig[$i]);
        if (strcmp(str_replace('"', '&quot;', $field_length_orig[$i]), $field_length[$i]) == 0) {
            $field_length[$i] = $field_length_orig[$i];
        }
        if (!isset($query)) {
            $query = '';
        } else {
            $query .= ', CHANGE ';
        }
        $query .= PMA_backquote($field_orig[$i]) . ' ' . PMA_backquote($field_name[$i]) . ' ' . $field_type[$i];
        // Some field types shouldn't have lengths
        if ($field_length[$i] != ''
            && !eregi('^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$', $field_type[$i])) {
            $query .= '(' . $field_length[$i] . ')';
        }
        if ($field_attribute[$i] != '') {
            $query .= ' ' . $field_attribute[$i];
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
        }
    } // end for

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    $sql_query     = 'USE ' . PMA_backquote($db);
    $result        = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
    // Optimization fix - 2 May 2001 - Robbat2
    $sql_query = 'ALTER TABLE ' . PMA_backquote($table) . ' CHANGE ' . $query;
    $result    = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
    $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
    $btnDrop   = 'Fake';

    // garvin: If comments were sent, enable relation stuff
    require('./libraries/relation.lib.php3');
    require('./libraries/transformations.lib.php3');

    $cfgRelation = PMA_getRelationsParam();
    
    // garvin: Update comment table, if a comment was set.
    if (isset($field_comments) && is_array($field_comments) && $cfgRelation['commwork']) {
        @reset($field_comments);
        while(list($fieldindex, $fieldcomment) = each($field_comments)) {
            PMA_setComment($db, $table, $field_name[$fieldindex], $fieldcomment, $field_orig[$fieldindex]);
        }
    }

    // garvin: Update comment table for mime types [MIME]
    if (isset($field_mimetype) && is_array($field_mimetype) && $cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME']) {
        @reset($field_mimetype);
        while(list($fieldindex, $mimetype) = each($field_mimetype)) {
            PMA_setMIME($db, $table, $field_name[$fieldindex], $mimetype, $field_transformation[$fieldindex], $field_transformation_options[$fieldindex]);
        }
    }

    include('./tbl_properties_structure.php3');
    exit();
}


/**
 * No modifications yet required -> displays the table fields
 */
else {
    if (!isset($selected)) {
        $selected[]   = $field;
        $selected_cnt = 1;
    } else { // from a multiple submit
        $selected_cnt = count($selected);
    }

    // TODO: optimize in case of multiple fields to modify
    for ($i = 0; $i < $selected_cnt; $i++) {
        if (!empty($submit_mult)) {
            $field = PMA_sqlAddslashes(urldecode($selected[$i]), TRUE);
        } else {
            $field = PMA_sqlAddslashes($selected[$i], TRUE);
        }
        $local_query   = 'SHOW FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . " LIKE '$field'";
        $result        = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
        $fields_meta[] = PMA_mysql_fetch_array($result);
        mysql_free_result($result);
    }

    $num_fields  = count($fields_meta);
    $action      = 'tbl_alter.php3';
    include('./tbl_properties.inc.php3');
}


/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
