<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
$js_to_run = 'functions.js';
require_once('./header.inc.php');

// Check parameters
PMA_checkParameters(array('db', 'table'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties_structure.php?' . PMA_generate_common_url($db, $table);


/**
 * Modifications have been submitted -> updates the table
 */
$abort = false;
if (isset($submit)) {
    $field_cnt = count($field_orig);
    for ($i = 0; $i < $field_cnt; $i++) {
        // to "&quot;" in tbl_properties.php
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
        }
    } // end for

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    $sql_query     = 'USE ' . PMA_backquote($db);
    $result        = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
    // Optimization fix - 2 May 2001 - Robbat2
    $sql_query = 'ALTER TABLE ' . PMA_backquote($table) . ' CHANGE ' . $query;
    $error_create = false;
    $result    = PMA_mysql_query($sql_query) or $error_create = true;

    if ($error_create == false) {
        $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
        $btnDrop   = 'Fake';

        // garvin: If comments were sent, enable relation stuff
        require_once('./libraries/relation.lib.php');
        require_once('./libraries/transformations.lib.php');

        $cfgRelation = PMA_getRelationsParam();

        // garvin: Update comment table, if a comment was set.
        if (isset($field_comments) && is_array($field_comments) && $cfgRelation['commwork']) {
            foreach($field_comments AS $fieldindex => $fieldcomment) {
                PMA_setComment($db, $table, $field_name[$fieldindex], $fieldcomment, $field_orig[$fieldindex]);
            }
        }

        // garvin: Rename relations&display fields, if altered.
        if (($cfgRelation['displaywork'] || $cfgRelation['relwork']) && isset($field_orig) && is_array($field_orig)) {
            foreach($field_orig AS $fieldindex => $fieldcontent) {
                if ($field_name[$fieldindex] != $fieldcontent) {
                    if ($cfgRelation['displaywork']) {
                        $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                                      . ' SET     display_field = \'' . PMA_sqlAddslashes($field_name[$fieldindex]) . '\''
                                      . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                      . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                                      . ' AND display_field = \'' . PMA_sqlAddslashes($fieldcontent) . '\'';
                        $tb_rs    = PMA_query_as_cu($table_query);
                        unset($table_query);
                        unset($tb_rs);
                    }

                    if ($cfgRelation['relwork']) {
                        $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['relation'])
                                      . ' SET     master_field = \'' . PMA_sqlAddslashes($field_name[$fieldindex]) . '\''
                                      . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                      . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                                      . ' AND master_field = \'' . PMA_sqlAddslashes($fieldcontent) . '\'';
                        $tb_rs    = PMA_query_as_cu($table_query);
                        unset($table_query);
                        unset($tb_rs);

                        $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['relation'])
                                      . ' SET     foreign_field = \'' . PMA_sqlAddslashes($field_name[$fieldindex]) . '\''
                                      . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                      . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\''
                                      . ' AND foreign_field = \'' . PMA_sqlAddslashes($fieldcontent) . '\'';
                        $tb_rs    = PMA_query_as_cu($table_query);
                        unset($table_query);
                        unset($tb_rs);
                    } // end if relwork
                } // end if fieldname has changed
            } // end while check fieldnames
        } // end if relations/display has to be changed

        // garvin: Update comment table for mime types [MIME]
        if (isset($field_mimetype) && is_array($field_mimetype) && $cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME']) {
            foreach($field_mimetype AS $fieldindex => $mimetype) {
                PMA_setMIME($db, $table, $field_name[$fieldindex], $mimetype, $field_transformation[$fieldindex], $field_transformation_options[$fieldindex]);
            }
        }

        $active_page = 'tbl_properties_structure.php';
        require('./tbl_properties_structure.php');
    } else {
        PMA_mysqlDie('', '', '', $err_url, FALSE);
        // garvin: An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in tbl_properties.inc.php
        if (isset($orig_field)) {
                $field = $orig_field;
        }

        $regenerate = true;
    }
}

/**
 * No modifications yet required -> displays the table fields
 */
if ($abort == FALSE) {
    if (!isset($selected)) {
        PMA_checkParameters(array('field'));
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
    $action      = 'tbl_alter.php';
    require('./tbl_properties.inc.php');
}


/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
