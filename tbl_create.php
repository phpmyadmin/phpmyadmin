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

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db', 'table'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php?' . PMA_generate_common_url($db, $table);


/**
 * Selects the database to work with
 */
PMA_mysql_select_db($db);


/**
 * The form used to define the structure of the table has been submitted
 */
$abort = false;
if (isset($submit)) {
    $sql_query = $query_cpy = '';

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
    // Builds the fields creation statements
    for ($i = 0; $i < $field_cnt; $i++) {
        if (empty($field_name[$i])) {
            continue;
        }
        $query = PMA_backquote($field_name[$i]) . ' ' . $field_type[$i];
        if ($field_length[$i] != '') {
            $query .= '(' . $field_length[$i] . ')';
        }
        if ($field_attribute[$i] != '') {
            $query .= ' ' . $field_attribute[$i];
        } else if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($field_charset[$i])) {
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
        $query .= ', ';
        $sql_query .= $query;
        $query_cpy .= "\n" . '  ' . $query;
    } // end for
    unset($field_cnt);
    unset($query);
    $sql_query = preg_replace('@, $@', '', $sql_query);
    $query_cpy = preg_replace('@, $@', '', $query_cpy);

    // Builds the primary keys statements
    $primary     = '';
    $primary_cnt = (isset($field_primary) ? count($field_primary) : 0);
    for ($i = 0; $i < $primary_cnt; $i++) {
        $j = $field_primary[$i];
        if (!empty($field_name[$j])) {
            $primary .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($primary_cnt);
    $primary = preg_replace('@, $@', '', $primary);
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
            $index .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($index_cnt);
    $index = preg_replace('@, $@', '', $index);
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
           $unique .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($unique_cnt);
    $unique = preg_replace('@, $@', '', $unique);
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
           $fulltext .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for

    $fulltext = preg_replace('@, $@', '', $fulltext);
    if (!empty($fulltext)) {
        $sql_query .= ', FULLTEXT (' . $fulltext . ')';
        $query_cpy .= ',' . "\n" . '  FULLTEXT (' . $fulltext . ')';
    }
    unset($fulltext);

    // Builds the 'create table' statement
    $sql_query      = 'CREATE TABLE ' . PMA_backquote($table) . ' (' . $sql_query . ')';
    $query_cpy      = 'CREATE TABLE ' . PMA_backquote($table) . ' (' . $query_cpy . "\n" . ')';

    // Adds table type, character set and comments
    if (!empty($tbl_type) && ($tbl_type != 'Default')) {
        $sql_query .= ' TYPE = ' . $tbl_type;
        $query_cpy .= "\n" . 'TYPE = ' . $tbl_type;
    }
    if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($tbl_charset)) {
        $sql_query .= ' CHARACTER SET = ' . $tbl_charset;
        $query_cpy .= "\n" . 'CHARACTER SET = ' . $tbl_charset;
    }

    if (!empty($comment)) {
        $sql_query .= ' COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
        $query_cpy .= "\n" . 'COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
    }

    // Executes the query
    $error_create = false;
    $result    = PMA_mysql_query($sql_query) or $error_create = true;

    if ($error_create == false) {
        $sql_query = $query_cpy . ';';
        unset($query_cpy);
        $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenCreated;

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

        require('./' . $cfg['DefaultTabTable']);
        $abort = TRUE;
        exit();
    } else {
        PMA_mysqlDie('', '', '', $err_url, FALSE);
        // garvin: An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in tbl_properties.inc.php
        $num_fields = $orig_num_fields;
        $regenerate = true;
    }
} // end do create table

/**
 * Displays the form used to define the structure of the table
 */
if ($abort == FALSE) {
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
        $action = 'tbl_create.php';
        require('./tbl_properties.inc.php');
        // Diplays the footer
        echo "\n";
        require_once('./footer.inc.php');
   }
}

?>
