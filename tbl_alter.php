<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

$js_to_run = 'functions.js';
require_once './libraries/header.inc.php';

// Check parameters
PMA_checkParameters(array('db', 'table'));

/**
 * Gets tables informations
 */
require_once './libraries/tbl_common.php';
require_once './libraries/tbl_info.inc.php';
/**
 * Displays top menu links
 */
$active_page = 'tbl_structure.php';
// I don't see the need to display the links here, they will be displayed later
//require './libraries/tbl_links.inc.php';


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);


/**
 * Modifications have been submitted -> updates the table
 */
$abort = false;
if (isset($do_save_data)) {
    $field_cnt = count($field_orig);
    for ($i = 0; $i < $field_cnt; $i++) {
        // to "&quot;" in tbl_sql.php
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

        $query .= PMA_Table::generateAlter($field_orig[$i], $field_name[$i], $field_type[$i], $field_length[$i], $field_attribute[$i], isset($field_collation[$i]) ? $field_collation[$i] : '', $field_null[$i], $field_default[$i], isset($field_default_current_timestamp[$i]), $field_extra[$i], (isset($field_comments[$i]) ? $field_comments[$i] : ''), $field_default_orig[$i]);
    } // end for

    // To allow replication, we first select the db to use and then run queries
    // on this db.
     PMA_DBI_select_db($db) or PMA_mysqlDie(PMA_DBI_getError(), 'USE ' . PMA_backquote($db) . ';', '', $err_url);
    // Optimization fix - 2 May 2001 - Robbat2
    $sql_query = 'ALTER TABLE ' . PMA_backquote($table) . ' CHANGE ' . $query;
    $error_create = FALSE;
    $result    = PMA_DBI_try_query($sql_query) or $error_create = TRUE;

    if ($error_create == FALSE) {
        $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
        $btnDrop   = 'Fake';

        // garvin: If comments were sent, enable relation stuff
        require_once './libraries/relation.lib.php';
        require_once './libraries/transformations.lib.php';

        $cfgRelation = PMA_getRelationsParam();

        // take care of pmadb internal comments here
        // garvin: Update comment table, if a comment was set.
        if (PMA_MYSQL_INT_VERSION < 40100 && isset($field_comments) && is_array($field_comments) && $cfgRelation['commwork']) {
            foreach ($field_comments AS $fieldindex => $fieldcomment) {
                if (isset($field_name[$fieldindex]) && strlen($field_name[$fieldindex])) {
                    PMA_setComment($db, $table, $field_name[$fieldindex], $fieldcomment, $field_orig[$fieldindex], 'pmadb');
                }
            }
        }

        // garvin: Rename relations&display fields, if altered.
        if (($cfgRelation['displaywork'] || $cfgRelation['relwork']) && isset($field_orig) && is_array($field_orig)) {
            foreach ($field_orig AS $fieldindex => $fieldcontent) {
                if ($field_name[$fieldindex] != $fieldcontent) {
                    if ($cfgRelation['displaywork']) {
                        $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                                      . ' SET     display_field = \'' . PMA_sqlAddslashes($field_name[$fieldindex]) . '\''
                                      . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                      . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                                      . ' AND display_field = \'' . PMA_sqlAddslashes($fieldcontent) . '\'';
                        $tb_rs    = PMA_query_as_cu($table_query);
                        unset($table_query);
                        unset($tb_rs);
                    }

                    if ($cfgRelation['relwork']) {
                        $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                                      . ' SET     master_field = \'' . PMA_sqlAddslashes($field_name[$fieldindex]) . '\''
                                      . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                      . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                                      . ' AND master_field = \'' . PMA_sqlAddslashes($fieldcontent) . '\'';
                        $tb_rs    = PMA_query_as_cu($table_query);
                        unset($table_query);
                        unset($tb_rs);

                        $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
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
            foreach ($field_mimetype AS $fieldindex => $mimetype) {
                if (isset($field_name[$fieldindex]) && strlen($field_name[$fieldindex])) {
                    PMA_setMIME($db, $table, $field_name[$fieldindex], $mimetype, $field_transformation[$fieldindex], $field_transformation_options[$fieldindex]);
                }
            }
        }

        $active_page = 'tbl_structure.php';
        require './tbl_structure.php';
    } else {
        PMA_mysqlDie('', '', '', $err_url, FALSE);
        // garvin: An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in libraries/tbl_properties.inc.php
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

    /**
     * @todo optimize in case of multiple fields to modify
     */
    for ($i = 0; $i < $selected_cnt; $i++) {
        if (!empty($submit_mult)) {
            $field = PMA_sqlAddslashes(urldecode($selected[$i]), TRUE);
        } else {
            $field = PMA_sqlAddslashes($selected[$i], TRUE);
        }
        $result        = PMA_DBI_query('SHOW FULL FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ' LIKE \'' . $field . '\';');
        $fields_meta[] = PMA_DBI_fetch_assoc($result);
        PMA_DBI_free_result($result);
    }
    $num_fields  = count($fields_meta);
    $action      = 'tbl_alter.php';

    // Get more complete field information
    // For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
    // but later, if the analyser returns more information, it
    // could be executed for any MySQL version and replace
    // the info given by SHOW FULL FIELDS FROM.
    /**
     * @todo put this code into a require()
     * or maybe make it part of PMA_DBI_get_fields();
     */

    // We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
    // SHOW FULL FIELDS says NULL and SHOW CREATE TABLE says NOT NULL (tested
    // in MySQL 4.0.25).

    $show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table),
        0, 1);
    $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

    require './libraries/tbl_properties.inc.php';
}


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
