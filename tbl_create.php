<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table create form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Get some core libraries
 */
require_once 'libraries/common.inc.php';

$action = 'tbl_create.php';

$titles = PMA_Util::buildActionTitles();

// Check parameters
PMA_Util::checkParameters(array('db'));

/* Check if database name is empty */
if (strlen($db) == 0) {
    PMA_Util::mysqlDie(
        __('The database name is empty!'), '', '', 'index.php'
    );
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (PMA_DBI_getColumns($db, $table)) {
    // table exists already
    PMA_Util::mysqlDie(
        sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
        '',
        '',
        'db_structure.php?' . PMA_generate_common_url($db)
    );
}

$err_url = 'tbl_create.php?' . PMA_generate_common_url($db, $table);

// check number of fields to be created
if (isset($_REQUEST['submit_num_fields'])) {
    $regenerate = true; // for libraries/tbl_columns_definition_form.inc.php
    $num_fields = $_REQUEST['orig_num_fields'] + $_REQUEST['added_fields'];
} elseif (isset($_REQUEST['num_fields']) && intval($_REQUEST['num_fields']) > 0) {
    $num_fields = (int) $_REQUEST['num_fields'];
} else {
    $num_fields = 4;
}

/**
 * Selects the database to work with
 */
if (!PMA_DBI_selectDb($db)) {
    PMA_Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        '',
        'index.php'
    );
}

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_REQUEST['do_save_data'])) {

	// Call the query generator for SQL query
    $sql_query = SQLQueryGenerator($db, $table); // Pass db and table
	
    // Executes the query (including empty queries)
    $result = PMA_DBI_tryQuery($sql_query);

    if ($result) {

        // If comments were sent, enable relation stuff
        include_once 'libraries/transformations.lib.php';

        // Update comment table for mime types [MIME]
        if (isset($_REQUEST['field_mimetype'])
            && is_array($_REQUEST['field_mimetype'])
            && $cfg['BrowseMIME']
        ) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && strlen($_REQUEST['field_name'][$fieldindex])
                ) {
                    PMA_setMIME(
                        $db, $table, $_REQUEST['field_name'][$fieldindex], $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex]
                    );
                }
            }
        }

        $message = PMA_Message::success(__('Table %1$s has been created.'));
        $message->addParam(
            PMA_Util::backquote($db) . '.' . PMA_Util::backquote($table)
        );

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

            $tbl_stats_result = PMA_DBI_query(
                'SHOW TABLE STATUS FROM ' . PMA_Util::backquote($db)
                . ' LIKE \'' . PMA_Util::sqlAddSlashes($table, true) . '\';'
            );
            $tbl_stats = PMA_DBI_fetchAssoc($tbl_stats_result);
            PMA_DBI_freeResult($tbl_stats_result);
            unset($tbl_stats_result);

            if ($is_show_stats) {
                $sum_size       = (double) 0;
                $overhead_size  = (double) 0;
                $overhead_check = '';

                $tblsize = doubleval($tbl_stats['Data_length'])
                    + doubleval($tbl_stats['Index_length']);
                $sum_size += $tblsize;
                list($formatted_size, $unit) = PMA_Util::formatByteDown(
                    $tblsize,
                    3,
                    ($tblsize > 0) ? 1 : 0
                );
                if (isset($tbl_stats['Data_free']) && $tbl_stats['Data_free'] > 0) {
                    list($formatted_overhead, $overhead_unit)
                        = PMA_Util::formatByteDown(
                            $tbl_stats['Data_free'],
                            3,
                            ($tbl_stats['Data_free'] > 0) ? 1 : 0
                        );
                    $overhead_size += $tbl_stats['Data_free'];
                }

                if (isset($formatted_overhead)) {
                    $overhead = '<span>' . $formatted_overhead . '</span>'
                        . '<span class="unit">' . $overhead_unit . '</span>';
                    unset($formatted_overhead);
                } else {
                    $overhead = '-';
                }
            }

            $new_table_string = '<tr>' . "\n";
            $new_table_string .= '<td class="center">'
                . '<input type="checkbox" id="checkbox_tbl_"'
                . ' name="selected_tbl[]" value="'.htmlspecialchars($table).'" />'
                . '</td>' . "\n";

            $new_table_string .= '<th>';
            $new_table_string .= '<a href="sql.php'
                . PMA_generate_common_url($tbl_url_params) . '">'
                . htmlspecialchars($table) . '</a>';

            if (PMA_Tracker::isActive()) {
                $truename = str_replace(' ', '&nbsp;', htmlspecialchars($table));
                if (PMA_Tracker::isTracked($db, $truename)) {
                    $new_table_string .= '<a href="tbl_tracking.php'
                        . PMA_generate_common_url($tbl_url_params) . '">';
                    $new_table_string .= PMA_Util::getImage(
                        'eye.png', __('Tracking is active.')
                    );
                } elseif (PMA_Tracker::getVersion($db, $truename) > 0) {
                    $new_table_string .= '<a href="tbl_tracking.php'
                       . PMA_generate_common_url($tbl_url_params) . '">';
                    $new_table_string .= PMA_Util::getImage(
                        'eye_grey.png', __('Tracking is not active.')
                    );
                }
                unset($truename);
            }
            $new_table_string .= '</th>' . "\n";

            $new_table_string .= '<td>' . $titles['NoBrowse'] . '</td>' . "\n";

            $new_table_string .= '<td>'
                . '<a href="tbl_structure.php'
                . PMA_generate_common_url($tbl_url_params) . '">'
                . $titles['Structure']
                . '</a>'
                . '</td>' . "\n";

            $new_table_string .= '<td>' . $titles['NoSearch'] . '</td>' . "\n";

            $new_table_string .= '<td>'
                . '<a href="tbl_change.php'
                . PMA_generate_common_url($tbl_url_params) . '">'
                . $titles['Insert']
                . '</a>'
                . '</td>' . "\n";

            $new_table_string .= '<td>' . $titles['NoEmpty'] . '</td>' . "\n";

            $new_table_string .= '<td>'
                . '<a class="drop_table_anchor" href="sql.php'
                . PMA_generate_common_url($tbl_url_params) . '&amp;sql_query='
                . urlencode('DROP TABLE ' . PMA_Util::backquote($table)) . '">'
                . $titles['Drop']
                . '</a>'
                . '</td>' . "\n";

            $new_table_string .= '<td class="value">'
                . $tbl_stats['Rows']
                . '</td>' . "\n";

            $new_table_string .= '<td class="nowrap">'
                . $tbl_stats['Engine']
                . '</td>' . "\n";

            $new_table_string .= '<td>'
                . '<dfn title="'
                . PMA_getCollationDescr($tbl_stats['Collation']) . '">'
                . $tbl_stats['Collation']
                .'</dfn>'
                . '</td>' . "\n";

            if ($is_show_stats) {
                $new_table_string .= '<td class="value tbl_size">'
                    . '<a href="tbl_structure.php'
                    . PMA_generate_common_url($tbl_url_params) . '#showusage" >'
                    . '<span>' . $formatted_size . '</span>'
                    . '<span class="unit">' . $unit . '</span>'
                    . '</a>'
                    . '</td>' . "\n" ;

                $new_table_string .= '<td class="value tbl_overhead">'
                    . $overhead
                    . '</td>' . "\n" ;
            }
            $new_table_string .= '</tr>' . "\n";

            $formatted_sql = PMA_Util::getMessage(
                $message, $sql_query, 'success'
            );

            $response = PMA_Response::getInstance();
            $response->addJSON('message', $message);
            $response->addJSON('formatted_sql', $formatted_sql);
            $response->addJSON('new_table_string', $new_table_string);
        } else {

            $display_query = $sql_query;
            $sql_query = '';

            // read table info on this newly created table, in case
            // the next page is Structure
            $reread_info = true;
            include 'libraries/tbl_info.inc.php';

            // do not switch to sql.php
            // as there is no row to be displayed on a new table
            if ($cfg['DefaultTabTable'] === 'sql.php') {
                include 'tbl_structure.php';
            } else {
                include '' . $cfg['DefaultTabTable'];
            }
        }
    } else {
        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', PMA_DBI_getError());
        } else {
            echo PMA_Util::mysqlDie('', '', '', $err_url, false);
            // An error happened while inserting/updating a table definition.
            // To prevent total loss of that data, we embed the form once again.
            // The variable $regenerate will be used to restore data in
            // libraries/tbl_columns_definition_form.inc.php
            $num_fields = $_REQUEST['orig_num_fields'];
            $regenerate = true;
        }
    }
    exit;
} // end do create table

   /**
    * Transforms the radio button field_key into 4 arrays
    * 
    * @return an array of dicts for access of array elements
    */ 
function transformArray(){
	$field_primary = array();
	$field_index = array();
	$field_unique = array();
	$field_fulltext = array();
	
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
			if ($_REQUEST['field_key'][$i] == 'fulltext_' . $i) {
                $field_fulltext[]  = $i;
            }
        } // end if
    } // end for
    unset($field_cnt);
	return array(
				"primary" => $field_primary, 
				"index" => $field_index, 
				"unique" => $field_unique, 
				"fulltext" => $field_fulltext
				);
}
	
	
    /**
     * Builds the fields creation statements
	 * 
	 * @return a string of fields
     */
function buildFieldsCreation(){
    $statement = '';
	$field_cnt = count($_REQUEST['field_name']);
    for ($i = 0; $i < $field_cnt; $i++) {
        // '0' is also empty for php :-(
        if (empty($_REQUEST['field_name'][$i])
            && $_REQUEST['field_name'][$i] != '0'
        ) {
            continue;
        }

        $query = PMA_Table::generateFieldSpec(
            $_REQUEST['field_name'][$i],
            $_REQUEST['field_type'][$i],
            $i,
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
            ''
        );

        $query .= ', ';
        $statement .= $query;
    } // end for
    unset($field_cnt, $query);
    $statement = preg_replace('@, $@', '', $statement);

	return $statement;
}

    /**
     * Builds the index part of PRIMARY, INDEX, UNIQUE, and FULLTEXT statements 
	 * 
	 * @param array $fieldName: the field name of the array of the indexes
	 * @return a string of corresponding indexes
     */
function buildStatement($fieldName){
    $statement     = '';
    $statement_cnt = (isset($fieldName) ? count($fieldName) : 0);
    for ($i = 0; $i < $statement_cnt; $i++) {
        $j = $fieldName[$i];
        if (isset($_REQUEST['field_name'][$j])
            && strlen($_REQUEST['field_name'][$j])
        ) {
            $statement .= PMA_Util::backquote($_REQUEST['field_name'][$j]) . ', ';
        }
    } // end for
    unset($statement_cnt);
    $statement = preg_replace('@, $@', '', $statement);
	
	return $statement;
}


    /**
     * Adds table type, character set, comments and partition definition
	 * 
	 * @return a string of miscellaneous items 
     */
function buildMisc(){
	$statement = '';
    if (!empty($_REQUEST['tbl_storage_engine'])
        && ($_REQUEST['tbl_storage_engine'] != 'Default')
    ) {
        $statement .= ' ENGINE = ' . $_REQUEST['tbl_storage_engine'];
    }
    if (!empty($_REQUEST['tbl_collation'])) {
        $statement .= PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }
    if (!empty($_REQUEST['comment'])) {
        $statement .= ' COMMENT = \''
            . PMA_Util::sqlAddSlashes($_REQUEST['comment']) . '\'';
    }
    if (!empty($_REQUEST['partition_definition'])) {
        $statement .= ' ' . PMA_Util::sqlAddSlashes(
            $_REQUEST['partition_definition']
        );
    }
    $statement .= ';';
	
	return $statement;
}

	/**
	 * Builds the SQL query required to create the table
	 * 
	 * @param string $db: the database selected to perform the action
	 * @param string $table: the table which is about to create
	 * @return a complete SQL query
	 */
function SQLQueryGenerator($db, $table){
	
	$sql_query = '';
		
    // Transforms the radio button field_key into 4 arrays
	$fieldArray= transformArray();

    // Builds the fields creation statements
    $fields_creation = buildFieldsCreation();

    // Builds the primary keys statements
    $primary = buildStatement($fieldArray[primary]);

    // Builds the indexes statements
    $index = buildStatement($fieldArray[index]);

    // Builds the uniques statements
    $unique = buildStatement($fieldArray[unique]);

    // Builds the FULLTEXT statements
    $fulltext = buildStatement($fieldArray[fulltext]);
	
	// Builds table type, character set, comments and partition definition
	$misc_statement = buildMisc();

	// Combine statements together
	if (strlen($fields_creation)) {
        $sql_query .= $fields_creation;
    }
    if (strlen($primary)) {
        $sql_query .= ', PRIMARY KEY (' . $primary . ')';
    }
	if (strlen($index)) {
        $sql_query .= ', INDEX (' . $index . ')';
    }
    if (strlen($unique)) {
        $sql_query .= ', UNIQUE (' . $unique . ')';
    }
    if (strlen($fulltext)) {
        $sql_query .= ', FULLTEXT (' . $fulltext . ')';
    }
		
    // Builds the 'create table' statement
    $sql_query = 'CREATE TABLE ' . PMA_Util::backquote($db) . '.'
        . PMA_Util::backquote($table) . ' (' . $sql_query . ')';

	// Combine table type, character set, comments and partition definition
	if (strlen($misc_statement)) {
        $sql_query .= $misc_statement;
    }// Normally, misc_statement will at least has a semicolon
		
	// Clear used variables
	unset($primary, $index, $unique, $fulltext, $misc_statement);
	
	return $sql_query;
}


/**
 * Displays the form used to define the structure of the table
 */

// This div is used to show the content(eg: create table form with more columns)
// fetched with AJAX subsequently.
if ($GLOBALS['is_ajax_request'] != true) {
    echo('<div id="create_table_div">');
}

require 'libraries/tbl_columns_definition_form.inc.php';

if ($GLOBALS['is_ajax_request'] != true) {
    echo('</div>');
}
?>
