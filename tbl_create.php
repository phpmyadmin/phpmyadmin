<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin
 */

/**
 * Get some core libraries
 */
require_once './libraries/common.inc.php';

$action = 'tbl_create.php';

require_once './libraries/header.inc.php';
$titles = PMA_buildActionTitles();

// Check parameters
PMA_checkParameters(array('db'));

/* Check if database name is empty */
if (strlen($db) == 0) {
    PMA_mysqlDie(__('The database name is empty!'), '', '', 'main.php');
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (PMA_DBI_get_columns($db, $table)) {
    // table exists already
    PMA_mysqlDie(
        sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
        '',
        '',
        'db_structure.php?' . PMA_generate_common_url($db)
    );
}

$err_url = 'tbl_create.php?' . PMA_generate_common_url($db, $table);

// check number of fields to be created
if (isset($_REQUEST['submit_num_fields'])) {
    $regenerate = true; // for libraries/tbl_properties.inc.php
    $num_fields = $_REQUEST['orig_num_fields'] + $_REQUEST['added_fields'];
} elseif (isset($_REQUEST['num_fields']) && intval($_REQUEST['num_fields']) > 0) {
    $num_fields = (int) $_REQUEST['num_fields'];
} else {
    $num_fields = 4;
}

/**
 * Selects the database to work with
 */
if (!PMA_DBI_select_db($db)) {
    PMA_mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        '',
        'main.php'
    );
}

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_REQUEST['do_save_data'])) {
    $sql_query = '';

    // Transforms the radio button field_key into 3 arrays
    $field_cnt = count($_REQUEST['field_name']);
    for ($i = 0; $i < $field_cnt; ++$i) {
        if (isset($_REQUEST['field_key'][$i])) {
            if ($_REQUEST['field_key'][$i] == 'primary_' . $i) {
                $field_primary[] = $i;
            }
            if ($_REQUEST['field_key'][$i] == 'index_' . $i) {
                $field_index[]   = $i;
            }
            if ($_REQUEST['field_key'][$i] == 'unique_' . $i) {
                $field_unique[]  = $i;
            }
        } // end if
    } // end for

    // Builds the fields creation statements
    for ($i = 0; $i < $field_cnt; $i++) {
        // '0' is also empty for php :-(
        if (empty($_REQUEST['field_name'][$i]) && $_REQUEST['field_name'][$i] != '0') {
            continue;
        }

        $query = PMA_Table::generateFieldSpec(
            $_REQUEST['field_name'][$i],
            $_REQUEST['field_type'][$i],
            $_REQUEST['field_length'][$i],
            $_REQUEST['field_attribute'][$i],
            isset($_REQUEST['field_collation'][$i])
                ? $_REQUEST['field_collation'][$i]
                : '',
            isset($_REQUEST['field_null'][$i])
                ? $_REQUEST['field_null'][$i]
                : 'NOT NULL',
            $_REQUEST['field_default_type'][$i],
            $_REQUEST['field_default_value'][$i],
            isset($_REQUEST['field_extra'][$i])
                ? $_REQUEST['field_extra'][$i]
                : false,
            isset($_REQUEST['field_comments'][$i])
                ? $_REQUEST['field_comments'][$i]
                : '',
            $field_primary,
            $i
        );

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
        if (isset($_REQUEST['field_name'][$j]) && strlen($_REQUEST['field_name'][$j])) {
            $primary .= PMA_backquote($_REQUEST['field_name'][$j]) . ', ';
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
        if (isset($_REQUEST['field_name'][$j]) && strlen($_REQUEST['field_name'][$j])) {
            $index .= PMA_backquote($_REQUEST['field_name'][$j]) . ', ';
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
        if (isset($_REQUEST['field_name'][$j]) && strlen($_REQUEST['field_name'][$j])) {
           $unique .= PMA_backquote($_REQUEST['field_name'][$j]) . ', ';
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
        if (isset($_REQUEST['field_name'][$j]) && strlen($_REQUEST['field_name'][$j])) {
           $fulltext .= PMA_backquote($_REQUEST['field_name'][$j]) . ', ';
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

    // Adds table type, character set, comments and partition definition
    if (!empty($_REQUEST['tbl_type']) && ($_REQUEST['tbl_type'] != 'Default')) {
        $sql_query .= ' ENGINE = ' . $_REQUEST['tbl_type'];
    }
    if (!empty($_REQUEST['tbl_collation'])) {
        $sql_query .= PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }
    if (!empty($_REQUEST['comment'])) {
        $sql_query .= ' COMMENT = \'' . PMA_sqlAddSlashes($_REQUEST['comment']) . '\'';
    }
    if (!empty($_REQUEST['partition_definition'])) {
        $sql_query .= ' ' . PMA_sqlAddSlashes($_REQUEST['partition_definition']);
    }
    $sql_query .= ';';

    // Executes the query
    $result = PMA_DBI_try_query($sql_query);

    if ($result) {

        // If comments were sent, enable relation stuff
        include_once './libraries/transformations.lib.php';

        // Update comment table for mime types [MIME]
        if (isset($_REQUEST['field_mimetype'])
         && is_array($_REQUEST['field_mimetype'])
         && $cfg['BrowseMIME']) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                 && strlen($_REQUEST['field_name'][$fieldindex])) {
                    PMA_setMIME(
                        $db, $table, $_REQUEST['field_name'][$fieldindex], $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex]
                    );
                }
            }
        }

        $message = PMA_Message::success(__('Table %1$s has been created.'));
        $message->addParam(PMA_backquote($db) . '.' . PMA_backquote($table));

        if ($GLOBALS['is_ajax_request'] == true) {

            /**
             * construct the html for the newly created table's row to be appended
             * to the list of tables.
             *
             * Logic taken from db_structure.php
             */

            $tbl_url_params = array();
            $tbl_url_params['db'] = $db;
            $tbl_url_params['table'] = $table;
            $is_show_stats = $cfg['ShowStats'];

            $tbl_stats_result = PMA_DBI_query('SHOW TABLE STATUS FROM '
                    . PMA_backquote($db) . ' LIKE \'' . PMA_sqlAddSlashes($table, true) . '\';');
            $tbl_stats = PMA_DBI_fetch_assoc($tbl_stats_result);
            PMA_DBI_free_result($tbl_stats_result);
            unset($tbl_stats_result);

            if ($is_show_stats) {
                $sum_size       = (double) 0;
                $overhead_size  = (double) 0;
                $overhead_check = '';

                $tblsize                    =  doubleval($tbl_stats['Data_length']) + doubleval($tbl_stats['Index_length']);
                $sum_size                   += $tblsize;
                list($formatted_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                if (isset($tbl_stats['Data_free']) && $tbl_stats['Data_free'] > 0) {
                    list($formatted_overhead, $overhead_unit)     = PMA_formatByteDown($tbl_stats['Data_free'], 3, ($tbl_stats['Data_free'] > 0) ? 1 : 0);
                    $overhead_size           += $tbl_stats['Data_free'];
                }

                if (isset($formatted_overhead)) {
                        $overhead = '<span>' . $formatted_overhead . '</span> <span class="unit">' . $overhead_unit . '</span>';
                        unset($formatted_overhead);
                    } else {
                        $overhead = '-';
                }
           }

            $new_table_string = '<tr>' . "\n";
            $new_table_string .= '<td align="center"> <input type="checkbox" id="checkbox_tbl_" name="selected_tbl[]" value="'.htmlspecialchars($table).'" /> </td>' . "\n";

            $new_table_string .= '<th>';
            $new_table_string .= '<a href="sql.php'
                . PMA_generate_common_url($tbl_url_params) . '">'
                . htmlspecialchars($table) . '</a>';

            if (PMA_Tracker::isActive()) {
                $truename = str_replace(' ', '&nbsp;', htmlspecialchars($table));
                if (PMA_Tracker::isTracked($db, $truename)) {
                    $new_table_string .= '<a href="tbl_tracking.php' . PMA_generate_common_url($tbl_url_params) . '">';
                    $new_table_string .= PMA_getImage('eye.png', __('Tracking is active.'));
                } elseif (PMA_Tracker::getVersion($db, $truename) > 0) {
                    $new_table_string .= '<a href="tbl_tracking.php' . PMA_generate_common_url($tbl_url_params) . '">';
                    $new_table_string .= PMA_getImage('eye_grey.png', __('Tracking is not active.'));
                }
                unset($truename);
            }
            $new_table_string .= '</th>' . "\n";

            $new_table_string .= '<td>' . $titles['NoBrowse'] . '</td>' . "\n";

            $new_table_string .= '<td><a href="tbl_structure.php' . PMA_generate_common_url($tbl_url_params) . '">' . $titles['Structure'] . '</a></td>' . "\n";

            $new_table_string .= '<td>' . $titles['NoSearch'] . '</td>' . "\n";

            $new_table_string .= '<td><a href="tbl_change.php' . PMA_generate_common_url($tbl_url_params) . '">' . $titles['Insert'] . '</a></td>' . "\n";

            $new_table_string .= '<td>' . $titles['NoEmpty'] . '</td>' . "\n";

            $new_table_string .= '<td><a class="drop_table_anchor" href="sql.php' . PMA_generate_common_url($tbl_url_params) . '&amp;sql_query=';
            $new_table_string .= urlencode('DROP TABLE ' . PMA_backquote($table));
            $new_table_string .= '">';
            $new_table_string .= $titles['Drop'];
            $new_table_string .= '</a></td>' . "\n";

            $new_table_string .= '<td class="value">' . $tbl_stats['Rows'] . '</td>' . "\n";

            $new_table_string .= '<td nowrap="nowrap">' . $tbl_stats['Engine'] . '</td>' . "\n";

            $new_table_string .= '<td> <dfn title="' . PMA_getCollationDescr($tbl_stats['Collation']) . '">'. $tbl_stats['Collation'] .'</dfn></td>' . "\n";

            if ($is_show_stats) {
                $new_table_string .= '<td class="value tbl_size"> <a href="tbl_structure.php' . PMA_generate_common_url($tbl_url_params) . '#showusage" ><span>' . $formatted_size . '</span> <span class="unit">' . $unit . '</class></a> </td>' . "\n" ;
                $new_table_string .= '<td class="value tbl_overhead">' . $overhead . '</td>' . "\n" ;
            }

            $new_table_string .= '</tr>' . "\n";

            $extra_data['new_table_string'] = $new_table_string;

            PMA_ajaxResponse($message, $message->isSuccess(), $extra_data);
        }

        $display_query = $sql_query;
        $sql_query = '';

        // read table info on this newly created table, in case
        // the next page is Structure
        $reread_info = true;
        include './libraries/tbl_info.inc.php';

        // do not switch to sql.php - as there is no row to be displayed on a new table
        if ($cfg['DefaultTabTable'] === 'sql.php') {
            include './tbl_structure.php';
        } else {
            include './' . $cfg['DefaultTabTable'];
        }
        exit;
    } else {
        if ($GLOBALS['is_ajax_request'] == true) {
            PMA_ajaxResponse(PMA_DBI_getError(), false);
        } else {
            PMA_mysqlDie('', '', '', $err_url, false);
            // An error happened while inserting/updating a table definition.
            // to prevent total loss of that data, we embed the form once again.
            // The variable $regenerate will be used to restore data in libraries/tbl_properties.inc.php
            $num_fields = $_REQUEST['orig_num_fields'];
            $regenerate = true;
        }
    }
} // end do create table

/**
 * Displays the form used to define the structure of the table
 */

// This div is used to show the content(eg: create table form with more columns) fetched with AJAX subsequently.
if ($GLOBALS['is_ajax_request'] != true) {
    echo('<div id="create_table_div">');
}

require './libraries/tbl_properties.inc.php';
// Displays the footer
require './libraries/footer.inc.php';

if ($GLOBALS['is_ajax_request'] != true) {
    echo('</div>');
}
?>
