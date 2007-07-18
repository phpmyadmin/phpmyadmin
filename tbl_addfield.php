<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Get some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

$js_to_run = 'functions.js';
require_once './libraries/header.inc.php';

// Check parameters
PMA_checkParameters(array('db', 'table'));


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_sql.php?' . PMA_generate_common_url($db, $table);

/**
 * The form used to define the field to add has been submitted
 */
$abort = false;
if (isset($submit_num_fields)) {
    if (isset($orig_after_field)) {
        $after_field = $orig_after_field;
    }
    if (isset($orig_field_where)) {
        $field_where = $orig_field_where;
    }
    $num_fields = $orig_num_fields + $added_fields;
    $regenerate = TRUE;
} elseif (isset($do_save_data)) {
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
        // '0' is also empty for php :-(
        if (empty($field_name[$i]) && $field_name[$i] != '0') {
            continue;
        }

        $query .= PMA_Table::generateFieldSpec($field_name[$i], $field_type[$i], $field_length[$i], $field_attribute[$i], isset($field_collation[$i]) ? $field_collation[$i] : '', $field_null[$i], $field_default[$i], isset($field_default_current_timestamp[$i]), $field_extra[$i], isset($field_comments[$i]) ? $field_comments[$i] : '', $field_primary, $i);

        if ($field_where != 'last') {
            // Only the first field can be added somewhere other than at the end
            if ($i == 0) {
                if ($field_where == 'first') {
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
    PMA_DBI_select_db($db) or PMA_mysqlDie(PMA_getError(), 'USE ' . PMA_backquotes($db), '', $err_url);
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD ' . $query;
    $error_create = FALSE;
    PMA_DBI_try_query($sql_query) or $error_create = TRUE;

    if ($error_create == false) {

        $sql_query_cpy = $sql_query . ';';

        // Builds the primary keys statements and updates the table
        $primary = '';
        if (isset($field_primary)) {
            $primary_cnt = count($field_primary);
            for ($i = 0; $i < $primary_cnt; $i++) {
                $j       = $field_primary[$i];
                if (isset($field_name[$j]) && strlen($field_name[$j])) {
                    $primary .= PMA_backquote($field_name[$j]) . ', ';
                }
            } // end for
            $primary     = preg_replace('@, $@', '', $primary);
            if (strlen($primary)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD PRIMARY KEY (' . $primary . ');';
                $result         = PMA_DBI_query($sql_query);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if

        // Builds the indexes statements and updates the table
        $index = '';
        if (isset($field_index)) {
            $index_cnt = count($field_index);
            for ($i = 0; $i < $index_cnt; $i++) {
                $j     = $field_index[$i];
                if (isset($field_name[$j]) && strlen($field_name[$j])) {
                    $index .= PMA_backquote($field_name[$j]) . ', ';
                }
            } // end for
            $index     = preg_replace('@, $@', '', $index);
            if (strlen($index)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX (' . $index . ')';
                $result         = PMA_DBI_query($sql_query);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if

        // Builds the uniques statements and updates the table
        $unique = '';
        if (isset($field_unique)) {
            $unique_cnt = count($field_unique);
            for ($i = 0; $i < $unique_cnt; $i++) {
                $j      = $field_unique[$i];
                if (isset($field_name[$j]) && strlen($field_name[$j])) {
                    $unique .= PMA_backquote($field_name[$j]) . ', ';
                }
            } // end for
            $unique = preg_replace('@, $@', '', $unique);
            if (strlen($unique)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE (' . $unique . ')';
                $result         = PMA_DBI_query($sql_query);
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
            if (strlen($fulltext)) {
                $sql_query      = 'ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT (' . $fulltext . ')';
                $result         = PMA_DBI_query($sql_query);
                $sql_query_cpy  .= "\n" . $sql_query . ';';
            }
        } // end if

        // garvin: If comments were sent, enable relation stuff
        require_once './libraries/relation.lib.php';
        require_once './libraries/transformations.lib.php';

        $cfgRelation = PMA_getRelationsParam();

        // garvin: Update comment table, if a comment was set.
        if (isset($field_comments) && is_array($field_comments) && $cfgRelation['commwork'] && PMA_MYSQL_INT_VERSION < 40100) {
            foreach ($field_comments AS $fieldindex => $fieldcomment) {
                if (isset($field_name[$fieldindex]) && strlen($field_name[$fieldindex])) {
                    PMA_setComment($db, $table, $field_name[$fieldindex], $fieldcomment, '', 'pmadb');
                }
            }
        }

        // garvin: Update comment table for mime types [MIME]
        if (isset($field_mimetype) && is_array($field_mimetype) && $cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME']) {
            foreach ($field_mimetype AS $fieldindex => $mimetype) {
                if (isset($field_name[$fieldindex]) && strlen($field_name[$fieldindex])) {
                    PMA_setMIME($db, $table, $field_name[$fieldindex], $mimetype, $field_transformation[$fieldindex], $field_transformation_options[$fieldindex]);
                }
            }
        }

        // Go back to the structure sub-page
        $sql_query = $sql_query_cpy;
        unset($sql_query_cpy);
        $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
        $active_page = 'tbl_structure.php';
        require './tbl_structure.php';
    } else {
        PMA_mysqlDie('', '', '', $err_url, FALSE);
        // garvin: An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in libraries/tbl_properties.inc.php
        $num_fields = $orig_num_fields;
        if (isset($orig_after_field)) {
            $after_field = $orig_after_field;
        }
        if (isset($orig_field_where)) {
            $field_where = $orig_field_where;
        }
        $regenerate = true;
    }
} // end do alter table

/**
 * Displays the form used to define the new field
 */
if ($abort == FALSE) {
    /**
     * Gets tables informations
     */
    require_once './libraries/tbl_common.php';
    require_once './libraries/tbl_info.inc.php';
    /**
     * Displays top menu links
     */
    $active_page = 'tbl_structure.php';
    require_once './libraries/tbl_links.inc.php';
    /**
     * Display the form
     */
    $action = 'tbl_addfield.php';
    require_once './libraries/tbl_properties.inc.php';

    // Diplays the footer
    echo "\n";
    require_once './libraries/footer.inc.php';
}

?>
