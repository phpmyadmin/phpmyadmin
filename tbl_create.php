<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @uses    $cfg['DefaultTabDatabase']
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['db']
 * @uses    $strTableEmpty
 * @uses    $strTableAlreadyExists
 * @uses    $strTable
 * @uses    $strHasBeenCreated
 * @uses    PMA_ENGINE_KEYWORD
 * @uses    PMA_MYSQL_INT_VERSION
 * @uses    PMA_Table::generateFieldSpec()
 * @uses    PMA_checkParameters()
 * @uses    PMA_generateCharsetQueryPart()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_setComment()
 * @uses    PMA_setMIME()
 * @uses    PMA_mysqlDie()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_DBI_get_columns()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_backquote()
 * @uses    $_REQUEST['do_save_data']
 * @uses    $_REQUEST['submit_num_fields']
 * @uses    $_REQUEST['orig_num_fields']
 * @uses    $_REQUEST['added_fields']
 * @uses    $_REQUEST['num_fields']
 * @uses    preg_replace()
 * @uses    count()
 * @uses    is_array()
 * @uses    strlen()
 * @uses    sprintf()
 * @uses    htmlspecialchars()
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
// @todo PMA_checkParameters does not check db and table proper with strlen()
PMA_checkParameters(array('db', 'table'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (! strlen($table)) {
    // No table name
    PMA_mysqlDie($strTableEmpty, '', '',
        'db_structure.php?' . PMA_generate_common_url($db));
} elseif (PMA_DBI_get_columns($db, $table)) {
    // table exists already
    PMA_mysqlDie(sprintf($strTableAlreadyExists, htmlspecialchars($table)), '',
        '', 'db_structure.php?' . PMA_generate_common_url($db));
}

$err_url = 'tbl_create.php?' . PMA_generate_common_url($db, $table);

/**
 * Selects the database to work with
 */
PMA_DBI_select_db($db);

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_REQUEST['do_save_data'])) {
    $sql_query = '';

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
        // '0' is also empty for php :-(
        if (empty($field_name[$i]) && $field_name[$i] != '0') {
            continue;
        }

        $query = PMA_Table::generateFieldSpec($field_name[$i], $field_type[$i],
            $field_length[$i], $field_attribute[$i],
            isset($field_collation[$i]) ? $field_collation[$i] : '',
            $field_null[$i], $field_default[$i],
            isset($field_default_current_timestamp[$i]), $field_extra[$i],
            isset($field_comments[$i]) ? $field_comments[$i] : '',
            $field_primary, $i);

        $query .= ', ';
        $sql_query .= $query;
    } // end for
    unset($field_cnt, $query);
    $sql_query = preg_replace('@, $@', '', $sql_query);

    // Builds the primary keys statements
    $primary     = '';
    $primary_cnt = (isset($field_primary) ? count($field_primary) : 0);
    for ($i = 0; $i < $primary_cnt; $i++) {
        $j = $field_primary[$i];
        if (isset($field_name[$j]) && strlen($field_name[$j])) {
            $primary .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($primary_cnt);
    $primary = preg_replace('@, $@', '', $primary);
    if (strlen($primary)) {
        $sql_query .= ', PRIMARY KEY (' . $primary . ')';
    }
    unset($primary);

    // Builds the indexes statements
    $index     = '';
    $index_cnt = (isset($field_index) ? count($field_index) : 0);
    for ($i = 0;$i < $index_cnt; $i++) {
        $j = $field_index[$i];
        if (isset($field_name[$j]) && strlen($field_name[$j])) {
            $index .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($index_cnt);
    $index = preg_replace('@, $@', '', $index);
    if (strlen($index)) {
        $sql_query .= ', INDEX (' . $index . ')';
    }
    unset($index);

    // Builds the uniques statements
    $unique     = '';
    $unique_cnt = (isset($field_unique) ? count($field_unique) : 0);
    for ($i = 0; $i < $unique_cnt; $i++) {
        $j = $field_unique[$i];
        if (isset($field_name[$j]) && strlen($field_name[$j])) {
           $unique .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for
    unset($unique_cnt);
    $unique = preg_replace('@, $@', '', $unique);
    if (strlen($unique)) {
        $sql_query .= ', UNIQUE (' . $unique . ')';
    }
    unset($unique);

    // Builds the FULLTEXT statements
    $fulltext     = '';
    $fulltext_cnt = (isset($field_fulltext) ? count($field_fulltext) : 0);
    for ($i = 0; $i < $fulltext_cnt; $i++) {
        $j = $field_fulltext[$i];
        if (isset($field_name[$j]) && strlen($field_name[$j])) {
           $fulltext .= PMA_backquote($field_name[$j]) . ', ';
        }
    } // end for

    $fulltext = preg_replace('@, $@', '', $fulltext);
    if (strlen($fulltext)) {
        $sql_query .= ', FULLTEXT (' . $fulltext . ')';
    }
    unset($fulltext);

    // Builds the 'create table' statement
    $sql_query = 'CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table)
     . ' (' . $sql_query . ')';

    // Adds table type, character set and comments
    if (!empty($tbl_type) && ($tbl_type != 'Default')) {
        $sql_query .= ' ' . PMA_ENGINE_KEYWORD  . ' = ' . $tbl_type;
    }
    if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($tbl_collation)) {
        $sql_query .= PMA_generateCharsetQueryPart($tbl_collation);
    }

    if (!empty($comment)) {
        $sql_query .= ' COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
    }

    // Executes the query
    $result = PMA_DBI_try_query($sql_query);

    if ($result) {

        // garvin: If comments were sent, enable relation stuff
        require_once './libraries/relation.lib.php';
        require_once './libraries/transformations.lib.php';

        $cfgRelation = PMA_getRelationsParam();

        // garvin: Update comment table, if a comment was set.
        if (isset($field_comments) && is_array($field_comments)
         && $cfgRelation['commwork'] && PMA_MYSQL_INT_VERSION < 40100) {
            foreach ($field_comments as $fieldindex => $fieldcomment) {
                if (isset($field_name[$fieldindex]) && strlen($field_name[$fieldindex])) {
                    PMA_setComment($db, $table, $field_name[$fieldindex], $fieldcomment, '', 'pmadb');
                }
            }
        }

        // garvin: Update comment table for mime types [MIME]
        if (isset($field_mimetype) && is_array($field_mimetype)
         && $cfgRelation['commwork'] && $cfgRelation['mimework']
         && $cfg['BrowseMIME']) {
            foreach ($field_mimetype as $fieldindex => $mimetype) {
                if (isset($field_name[$fieldindex]) && strlen($field_name[$fieldindex])) {
                    PMA_setMIME($db, $table, $field_name[$fieldindex], $mimetype,
                            $field_transformation[$fieldindex],
                            $field_transformation_options[$fieldindex]);
                }
            }
        }

        $message = $strTable . ' '
         . htmlspecialchars(PMA_backquote($db) . '.' . PMA_backquote($table))
         . ' ' . $strHasBeenCreated;
        $display_query = $sql_query;
        $sql_query = '';

        // do not switch to sql.php - as there is no row to be displayed on a new table
        if ($cfg['DefaultTabTable'] === 'sql.php') {
            require './tbl_structure.php';
        } else {
            require './' . $cfg['DefaultTabTable'];
        }
        exit;
    } else {
        PMA_mysqlDie('', '', '', $err_url, false);
        // garvin: An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in libraries/tbl_properties.inc.php
        $num_fields = $orig_num_fields;
    }
} // end do create table

/**
 * Displays the form used to define the structure of the table
 */
// check number of fields to be created
if (isset($_REQUEST['submit_num_fields'])) {
    $regenerate = true; // for libraries/tbl_properties.inc.php 
    $num_fields = $_REQUEST['orig_num_fields'] + $_REQUEST['added_fields'];
} elseif (isset($_REQUEST['num_fields']) && intval($_REQUEST['num_fields']) > 0) {
    $num_fields = (int) $_REQUEST['num_fields'];
} else {
    $num_fields = 1;
}

$action = 'tbl_create.php';
require './libraries/tbl_properties.inc.php';
// Displays the footer
require_once './libraries/footer.inc.php';
?>
