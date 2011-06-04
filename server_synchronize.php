<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */

/**
*
*/
require_once './libraries/common.inc.php';

/**
 * Does the common work
 */
$GLOBALS['js_include'][] = 'server_synchronize.js';
require_once './libraries/server_common.inc.php';

/**
* Contains all the functions specific to synchronization
*/
require './libraries/server_synchronize.lib.php';

/**
 * Increases the time limit up to the configured maximum
 */
@set_time_limit($cfg['ExecTimeLimit']);

/**
 * Displays the links
 */
require './libraries/server_links.inc.php';

/**
* Enables warnings on the page
*/
//$cfg['Error_Handler']['display'] = true;
//$cfg['Error_Handler']['gather'] = true;

/**
* Save the value of token generated for this page
*/
if (isset($_REQUEST['token'])) {
    $_SESSION['token'] = $_REQUEST['token'];
}

// variable for code saving
$cons = array ("src", "trg");

/**
 * Displays the page when 'Go' is pressed
 */

if ((isset($_REQUEST['submit_connect']))) {
    foreach ($cons as $con) {
        ${"{$con}_host"}     = $_REQUEST[$con . '_host'];
        ${"{$con}_username"} = $_REQUEST[$con . '_username'];
        ${"{$con}_password"} = $_REQUEST[$con . '_pass'];
        ${"{$con}_port"}     = $_REQUEST[$con . '_port'];
        ${"{$con}_socket"}   = $_REQUEST[$con . '_socket'];
        ${"{$con}_db"}       = $_REQUEST[$con . '_db'];
        ${"{$con}_type"}	 = $_REQUEST[$con . '_type'];

        if (${"{$con}_type"} == 'cur') {
	        ${"{$con}_connection"} = null;
	        ${"{$con}_server"} = null;
	        ${"{$con}_db"}       = $_REQUEST[$con . '_db_sel'];
	        continue;
        }

        if (isset(${"{$con}_socket"}) && ! empty(${"{$con}_socket"})) {
	        ${"{$con}_server"}['socket'] = ${"{$con}_socket"};
        } else {
	        ${"{$con}_server"}['host'] = ${"{$con}_host"};
	        if (isset(${"{$con}_port"}) && ! empty(${"{$con}_port"}) && ((int)${"{$con}_port"} * 1) > 0) {
	            ${"{$con}_server"}['port'] = (int)${"{$con}_port"};
	        }
        }

        ${"{$con}_connection"} = PMA_DBI_connect(${"{$con}_username"}, ${"{$con}_password"}, $is_controluser = false, ${"{$con}_server"}, $auxiliary_connection = true);
    } // end foreach ($cons as $con)

    if ((! $src_connection && $src_type != 'cur') || (! $trg_connection && $trg_type != 'cur')) {
        /**
        * Displays the connection error string if
        * connections are not established
        */

        echo '<div class="error">';
        if(! $src_connection && $src_type != 'cur') {
            echo __('Could not connect to the source') . '<br />';
        }
        if(! $trg_connection && $trg_type != 'cur'){
            echo __('Could not connect to the target');
        }
        echo '</div>';
        unset($_REQUEST['submit_connect']);

    } else {
        /**
        * Creating the link object for both source and target databases and
        * selecting the source and target databases using these links
        */
	    foreach ($cons as $con) {
	        if (${"{$con}_connection"} != null) {
	            ${"{$con}_link"} = PMA_DBI_connect(${"{$con}_username"}, ${"{$con}_password"}, $is_controluser = false, ${"{$con}_server"});
	        } else {
                ${"{$con}_link"} = null;
            }
	        ${"{$con}_db_selected"} = PMA_DBI_select_db(${"{$con}_db"}, ${"{$con}_link"});
	    } // end foreach ($cons as $con)

        if (($src_db_selected != 1) || ($trg_db_selected != 1)) {
            /**
            * Displays error string if the database(s) did not exist
            */
            echo '<div class="error">';
            if ($src_db_selected != 1) {
                echo sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($src_db));
            }
            if ($trg_db_selected != 1) {
                echo sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($trg_db));
            }
            echo '</div>';
            unset($_REQUEST['submit_connect']);

        } else if (($src_db_selected == 1) && ($trg_db_selected == 1)) {

            /**
            * Using PMA_DBI_get_tables() to get all the tables
            * from target and source databases.
            */
            $src_tables = PMA_DBI_get_tables($src_db, $src_link);
            $source_tables_num = sizeof($src_tables);

            $trg_tables = PMA_DBI_get_tables($trg_db, $trg_link);
            $target_tables_num = sizeof($trg_tables);

            /**
            * initializing arrays to save matching and non-matching
            * table names from target and source databases.
            */
            $unmatched_num_src = 0;
            $source_tables_uncommon = array();
            $unmatched_num_trg = 0;
            $target_tables_uncommon = array();
            $matching_tables = array();
            $matching_tables_num = 0;

            /**
            * Using PMA_getMatchingTables to find which of the tables' names match
            * in target and source database.
            */
            PMA_getMatchingTables($trg_tables, $src_tables, $matching_tables, $source_tables_uncommon);
            /**
            * Finding the uncommon tables for the target database
            * using function PMA_getNonMatchingTargetTables()
            */
            PMA_getNonMatchingTargetTables($trg_tables, $matching_tables, $target_tables_uncommon);

            /**
            * Initializing several arrays to save the data and structure
            * difference between the source and target databases.
            */
            $row_count = array();   //number of rows in source table that needs to be created in target database
            $fields_num = array();  //number of fields in each matching table
            $delete_array = array(); //stores the primary key values for target tables that have excessive rows than corresponding source tables.
            $insert_array = array(array(array()));// stores the primary key values for the rows in each source table that are not present in target tables.
            $update_array = array(array(array())); //stores the primary key values, name of field to be updated, value of the field to be updated for
                                                    // each row of matching table.
            $matching_tables_fields = array(); //contains the fields' names for each matching table
            $matching_tables_keys   = array(); //contains the primary keys' names for each matching table
            $uncommon_tables_fields = array(); //coantains the fields for all the source tables that are not present in target
            $matching_tables_num = sizeof($matching_tables);

            $source_columns = array();  //contains the full columns' information for all the source tables' columns
            $target_columns = array();  //contains the full columns' information for all the target tables' columns
            $uncommon_columns = array(); //contains names of columns present in source table but absent from the corresponding target table
            $source_indexes = array();   //contains indexes on all the source tables
            $target_indexes = array();   //contains indexes on all the target tables
            $add_indexes_array = array(); //contains the indexes name present in source but absent from target tables
            $target_tables_keys = array(); //contains the keys of all the target tables
            $alter_indexes_array = array();  //contains the names of all the indexes for each table that need to be altered in target database
            $remove_indexes_array = array();  //contains the names of indexes that are excessive in target tables
            $alter_str_array = array(array());  //contains the criteria for each column that needs to be altered in target tables
            $add_column_array = array(array()); //contains the name of columns that need to be added in target tables
            /**
            * The criteria array contains all the criteria against which columns are compared for differences.
            */
            $criteria = array('Field', 'Type', 'Null', 'Collation', 'Key', 'Default', 'Comment');

            for($i = 0; $i < sizeof($matching_tables); $i++) {
                /**
                * Finding out all the differences structure, data and index diff for all the matching tables only
                */
                PMA_dataDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $matching_tables_fields, $update_array, $insert_array,
                $delete_array, $fields_num, $i, $matching_tables_keys);

                PMA_structureDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns,
                $target_columns, $alter_str_array, $add_column_array, $uncommon_columns, $criteria, $target_tables_keys, $i);

                PMA_indexesDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_indexes, $target_indexes,
                $add_indexes_array, $alter_indexes_array, $remove_indexes_array, $i);
            }

            for($j = 0; $j < sizeof($source_tables_uncommon); $j++) {
                /**
                * Finding out the number of rows to be added in tables that need to be added in target database
                */
                PMA_dataDiffInUncommonTables($source_tables_uncommon, $src_db, $src_link, $j, $row_count);
            }
            /**
            * Storing all arrays in session for use when page is reloaded for each button press
            */
            $_SESSION['matching_tables'] = $matching_tables;
            $_SESSION['update_array'] = $update_array;
            $_SESSION['insert_array'] = $insert_array;
            $_SESSION['src_db'] = $src_db;
            $_SESSION['trg_db'] =  $trg_db;
            $_SESSION['matching_fields'] = $matching_tables_fields;
            $_SESSION['src_uncommon_tables'] = $source_tables_uncommon;
            $_SESSION['src_username'] = $src_username ;
            $_SESSION['trg_username'] = $trg_username;
            $_SESSION['src_password'] = $src_password;
            $_SESSION['trg_password'] = $trg_password;
            $_SESSION['trg_password'] = $trg_password;
    	    $_SESSION['src_server']   = $src_server;
	        $_SESSION['trg_server']   = $trg_server;
	        $_SESSION['src_type']     = $src_type;
    	    $_SESSION['trg_type']     = $trg_type;
            $_SESSION['matching_tables_keys'] = $matching_tables_keys;
            $_SESSION['uncommon_tables_fields'] = $uncommon_tables_fields;
            $_SESSION['uncommon_tables_row_count'] = $row_count;
            $_SESSION['target_tables_uncommon'] = $target_tables_uncommon;
            $_SESSION['uncommon_tables'] = $source_tables_uncommon;
            $_SESSION['delete_array'] = $delete_array;
            $_SESSION['uncommon_columns'] = $uncommon_columns;
            $_SESSION['source_columns'] = $source_columns;
            $_SESSION['alter_str_array'] = $alter_str_array;
            $_SESSION['target_tables_keys'] = $target_tables_keys;
            $_SESSION['add_column_array'] = $add_column_array;
            $_SESSION['criteria'] = $criteria;
            $_SESSION['target_tables'] = $trg_tables;
            $_SESSION['add_indexes_array'] = $add_indexes_array;
            $_SESSION['alter_indexes_array'] = $alter_indexes_array;
            $_SESSION['remove_indexes_array'] = $remove_indexes_array;
            $_SESSION['source_indexes'] = $source_indexes;
            $_SESSION['target_indexes'] = $target_indexes;

            /**
            * Displays the sub-heading and icons showing Structure Synchronization and Data Synchronization
            */
            echo '<form name="synchronize_form" id="synchronize_form" method="post" action="server_synchronize.php">'
            . PMA_generate_common_hidden_inputs('', '');
            echo '<table id="serverstatustraffic" class="data" width = "40%">
            <tr>
            <td>'
            . '<img class="icon" src="' . $pmaThemeImage . 'new_struct.jpg" width="32"'
            . ' height="32" alt="" />'
            . __('Structure Synchronization')
            .'</td>';
            echo '<td>'
            . '<img class="icon" src="' . $pmaThemeImage . 'new_data.jpg" width="32"'
            . ' height="32" alt="" />'
            . __('Data Synchronization')
            . '</td>';
            echo '</tr>
            </table>';

            /**
            * Displays the tables containing the source tables names, their difference with the target tables and target tables names
            */
            PMA_syncDisplayHeaderSource($src_db);
            $odd_row = false;

            /**
            * Display the matching tables' names and difference, first
            */
            for($i = 0; $i < count($matching_tables); $i++) {
                /**
                * Calculating the number of updates for each matching table
                */
                if (isset($update_array[$i]) && isset($update_array[$i][0]) &&
                        isset($update_array[$i][0][$matching_tables_keys[$i][0]])) {
                    $num_of_updates = sizeof($update_array[$i]);
                } else {
                    $num_of_updates = 0;
                }
                /**
                * Calculating the number of insertions for each matching table
                */
                if (isset($insert_array[$i]) && isset($insert_array[$i][0]) &&
                        isset($insert_array[$i][0][$matching_tables_keys[$i][0]])) {
                    $num_of_insertions = sizeof($insert_array[$i]);
                } else {
                    $num_of_insertions = 0;
                }
                /**
                * Displays the name of the matching table
                */
                $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
                echo '<td>' . htmlspecialchars($matching_tables[$i]) . '</td>
                <td align="center">';
                /**
                * Calculating the number of alter columns, number of columns to be added, number of columns to be removed,
                * number of index to be added and removed.
                */
                $num_alter_cols  = 0;
                $num_insert_cols = 0;
                $num_remove_cols = 0;
                $num_add_index   = 0;
                $num_remove_index = 0;

                if (isset($alter_str_array[$i])) {
                    $num_alter_cols = sizeof($alter_str_array[$i]);
                }
                if (isset($add_column_array[$i])) {
                    $num_insert_cols = sizeof($add_column_array[$i]);
                }
                if (isset($uncommon_columns[$i])) {
                    $num_remove_cols = sizeof($uncommon_columns[$i]);
                }
                if (isset($add_indexes_array[$i])) {
                    $num_add_index = sizeof($add_indexes_array[$i]);
                }
                if (isset($remove_indexes_array[$i])) {
                    $num_remove_index = sizeof($remove_indexes_array[$i]);
                }
                if (isset($alter_indexes_array[$i])) {
                    $num_add_index += sizeof($alter_indexes_array[$i]);
                    $num_remove_index += sizeof($alter_indexes_array[$i]);
                }
                /**
                * Display the red button of structure synchronization if there exists any structure difference or index difference.
                */
                if (($num_alter_cols > 0) || ($num_insert_cols > 0) || ($num_remove_cols > 0) || ($num_add_index > 0) || ($num_remove_index > 0)) {

                   echo '<img class="icon struct_img" src="' . $pmaThemeImage . 'new_struct.jpg" width="29"  height="29"
                   alt="' . __('Click to select') . '"
                   onclick="showDetails(' . "'MS" . $i . "','" . $num_alter_cols . "','" .$num_insert_cols .
                   "','" . $num_remove_cols . "','" . $num_add_index . "','" . $num_remove_index . "'"
                   . ', this ,' . "'" . htmlspecialchars($matching_tables[$i]) . "'" . ')"/>';
                }
                /**
                * Display the green button of data synchronization if there exists any data difference.
                */
                if (isset($update_array[$i]) || isset($insert_array[$i])) {
                    if (isset($update_array[$i][0][$matching_tables_keys[$i][0]]) || isset($insert_array[$i][0][$matching_tables_keys[$i][0]])) {

                        echo '<img class="icon data_img" src="' . $pmaThemeImage . 'new_data.jpg" width="29" height="29"
                        alt="' . __('Click to select') . '"
                         onclick="showDetails('. "'MD" . $i . "','" . $num_of_updates . "','" . $num_of_insertions .
                         "','" . null . "','" . null . "','" . null . "'" . ', this ,' . "'" . htmlspecialchars($matching_tables[$i]) . "'" . ')" />';
                    }
                }
                echo '</td>
                </tr>';
            }
            /**
            * Displays the tables' names present in source but missing from target
            */
            for ($j = 0; $j < count($source_tables_uncommon); $j++) {
                $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
                echo '<td> + ' . htmlspecialchars($source_tables_uncommon[$j]) . '</td> ';

                echo '<td align="center"><img class="icon struct_img" src="' . $pmaThemeImage .  'new_struct.jpg" width="29"  height="29"
                alt="' . __('Click to select') . '"
                onclick="showDetails(' . "'US" . $j . "','" . null . "','" . null . "','" . null . "','" . null . "','" . null . "'" . ', this ,'
                . "'" . htmlspecialchars($source_tables_uncommon[$j]) . "'" . ')"/>';

                if ($row_count[$j] > 0)
                {
                    echo '<img class="icon data_img" src="' . $pmaThemeImage . 'new_data.jpg" width="29" height="29"
                    alt="' . __('Click to select') . '"
                    onclick="showDetails(' . "'UD" . $j . "','" . null . "','" . $row_count[$j] . "','" . null .
                    "','" . null . "','" . null . "'" . ', this ,' . "'" . htmlspecialchars($source_tables_uncommon[$j]) . "'" . ')" />';
                }
                echo '</td>
                </tr>';
            }
            foreach ($target_tables_uncommon as $tbl_nc_name) {
                $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
                echo '<td height="32">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td><td></td>';
                echo '</tr>';
            }
            /**
            * Displays the target tables names
            */
            echo '</table>';

            $odd_row = PMA_syncDisplayHeaderTargetAndMatchingTables($trg_db, $matching_tables);
            foreach ($source_tables_uncommon as $tbl_nc_name) {
                $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
                echo '<td height="32">' . htmlspecialchars($tbl_nc_name) . ' (' . __('not present') . ')</td>
                </tr>';
            }
            foreach ($target_tables_uncommon as $tbl_nc_name) {
                $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
                echo '<td> - ' . htmlspecialchars($tbl_nc_name) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

            /**
            * This "list" div will contain a table and each row will depict information about structure/data diffrence in tables.
            * Rows will be generated dynamically as soon as the colored  buttons "D" or "S"  are clicked.
            */

            echo '<div id="list" style = "overflow: auto; width: 1020px; height: 140px;
            border-left: 1px gray solid; border-bottom: 1px gray solid;
            padding:0px; margin: 0px">

            <table>
                <thead>
                <tr style="width: 100%;">
                    <th id="table_name" style="width: 10%;" colspan="1">' . __('Table') . ' </th>
                    <th id="str_diff"   style="width: 65%;" colspan="6">' . __('Structure Difference') . ' </th>
                    <th id="data_diff"  style="width: 20%;" colspan="2">' . __('Data Difference') . '</th>
                </tr>
                <tr style="width: 100%;">
                    <th style="width: 10%;">' . __('Table name') . '</th>
                    <th style="width: 10%;">' . __('Create table'). '</th>
                    <th style="width: 11%;">' . __('Add column(s)') . '</th>
                    <th style="width: 13%;">' . __('Remove column(s)') . '</th>
                    <th style="width: 11%;">' . __('Alter column(s)') . '</th>
                    <th style="width: 12%;">' . __('Remove index(s)') . '</th>
                    <th style="width: 11%;">' . __('Apply index(s)') . '</th>
                    <th style="width: 10%;">'.  __('Update row(s)') . '</th>
                    <th style="width: 10%;">' . __('Insert row(s)') . '</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>';
            /**
            *  This fieldset displays the checkbox to confirm deletion of previous rows from target tables
            */
            echo '<fieldset>
            <p><input type= "checkbox" name="delete_rows" id ="delete_rows" /><label for="delete_rows">' . __('Would you like to delete all the previous rows from target tables?') . '</label> </p>
            </fieldset>
            <fieldset class="tblFooters">';
            echo '<input type="button" name="apply_changes" value="' . __('Apply Selected Changes')
             . '" onclick ="ApplySelectedChanges(' . "'" . htmlspecialchars($_SESSION['token']) . "'" . ')" />';
            echo '<input type="submit" name="synchronize_db" value="' . __('Synchronize Databases') . '" />' . '</fieldset>';
            echo '</form>';
        }
    }
} // end if ((isset($_REQUEST['submit_connect'])))

 /**
 * Display the page when 'Apply Selected Changes' is pressed
 */
if (isset($_REQUEST['Table_ids'])) {
    /**
    * Displays success message
    */
    echo '<div class="success">' . __('Selected target tables have been synchronized with source tables.') . '</div>';

    $src_db = $_SESSION['src_db'];
    $trg_db = $_SESSION['trg_db'];
    $update_array = $_SESSION['update_array'];
    $insert_array = $_SESSION['insert_array'];
    $src_username = $_SESSION['src_username'];
    $trg_username = $_SESSION['trg_username'];
    $src_password = $_SESSION['src_password'];
    $trg_password = $_SESSION['trg_password'];
    $src_server   = $_SESSION['src_server'];
    $trg_server   = $_SESSION['trg_server'];
    $src_type     = $_SESSION['src_type'];
    $trg_type     = $_SESSION['trg_type'];
    $uncommon_tables = $_SESSION['uncommon_tables'];
    $matching_tables = $_SESSION['matching_tables'];
    $matching_tables_keys = $_SESSION['matching_tables_keys'];
    $matching_tables_fields = $_SESSION['matching_fields'];
    $source_tables_uncommon = $_SESSION['src_uncommon_tables'];
    $uncommon_tables_fields = $_SESSION['uncommon_tables_fields'];
    $target_tables_uncommon = $_SESSION['target_tables_uncommon'];
    $row_count = $_SESSION['uncommon_tables_row_count'];
    $target_tables = $_SESSION['target_tables'];

    $delete_array = $_SESSION['delete_array'];
    $uncommon_columns = $_SESSION['uncommon_columns'];
    $source_columns = $_SESSION['source_columns'];
    $alter_str_array = $_SESSION['alter_str_array'];
    $criteria = $_SESSION['criteria'];
    $target_tables_keys = $_SESSION['target_tables_keys'];
    $add_column_array = $_SESSION['add_column_array'];
    $add_indexes_array = $_SESSION['add_indexes_array'];
    $alter_indexes_array = $_SESSION['alter_indexes_array'];
    $remove_indexes_array = $_SESSION['remove_indexes_array'];
    $source_indexes = $_SESSION['source_indexes'];
    $target_indexes = $_SESSION['target_indexes'];
    $uncommon_cols = $uncommon_columns;

    /**
    * Creating link object for source and target databases
    */
    foreach ($cons as $con) {
        if (${"{$con}_type"} != "cur") {
            ${"{$con}_link"} = PMA_DBI_connect(${"{$con}_username"}, ${"{$con}_password"}, $is_controluser = false, ${"{$con}_server"});
        } else {
            ${"{$con}_link"} = null;
            // working on current server, so initialize this for tracking
            // (does not work if user defined current server as a remote one)
            $GLOBALS['db'] = ${"{$con}_db"};
        }
    } // end foreach ($cons as $con)

    /**
    * Initializing arrays to save the table ids whose data and structure difference is to be applied
    */
    $matching_table_data_diff = array();  //stores id of matching table having data difference
    $matching_table_structure_diff = array(); //stores id of matching tables having structure difference
    $uncommon_table_structure_diff = array(); //stores id of uncommon tables having structure difference
    $uncommon_table_data_diff = array();     //stores id of uncommon tables having data difference

    for ($i = 0; isset($_REQUEST[$i]); $i++ ) {
        if (isset($_REQUEST[$i])) {
            $table_id = explode("US", $_REQUEST[$i]);
            if (isset($table_id[1])) {
                $uncommon_table_structure_diff[] = $table_id[1];
            }
            $table_id = explode("UD", $_REQUEST[$i]);
            if (isset($table_id[1])) {
                $uncommon_table_data_diff[] = $table_id[1];
            }
            $table_id = explode("MS", $_REQUEST[$i]);
            if (isset($table_id[1])) {
                $matching_table_structure_diff[] = $table_id[1];
            }

            $table_id = explode("MD", $_REQUEST[$i]);
            if (isset($table_id[1])) {
                 $matching_table_data_diff[] = $table_id[1];
            }
        }
    } // end for
    /**
    * Applying the structure difference on selected matching tables
    */
    for($q = 0; $q < sizeof($matching_table_structure_diff); $q++)
    {
        if (isset($alter_str_array[$matching_table_structure_diff[$q]])) {

            PMA_alterTargetTableStructure($trg_db, $trg_link, $matching_tables, $source_columns, $alter_str_array, $matching_tables_fields,
            $criteria, $matching_tables_keys, $target_tables_keys, $matching_table_structure_diff[$q], false);

            unset($alter_str_array[$matching_table_structure_diff[$q]]);
        }
        if (isset($add_column_array[$matching_table_structure_diff[$q]])) {

            PMA_findDeleteRowsFromTargetTables($delete_array, $matching_tables, $matching_table_structure_diff[$q], $target_tables_keys,
            $matching_tables_keys, $trg_db, $trg_link, $src_db, $src_link);

            if (isset($delete_array[$matching_table_structure_diff[$q]])) {

                PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $matching_table_structure_diff[$q], $target_tables_keys, $delete_array, false);

                unset($delete_array[$matching_table_structure_diff[$q]]);
            }
            PMA_addColumnsInTargetTable($src_db, $trg_db,$src_link, $trg_link, $matching_tables, $source_columns, $add_column_array, $matching_tables_fields,
            $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables,$uncommon_tables_fields, $matching_table_structure_diff[$q], $uncommon_cols, false);

            unset($add_column_array[$matching_table_structure_diff[$q]]);
        }
        if (isset($uncommon_columns[$matching_table_structure_diff[$q]])) {

            PMA_removeColumnsFromTargetTable($trg_db, $trg_link, $matching_tables, $uncommon_columns, $matching_table_structure_diff[$q], false);

            unset($uncommon_columns[$matching_table_structure_diff[$q]]);
        }
        if (isset($add_indexes_array[$matching_table_structure_diff[$q]]) || isset($remove_indexes_array[$matching_table_structure_diff[$q]])
            || isset($alter_indexes_array[$matching_table_structure_diff[$q]])) {

            PMA_applyIndexesDiff ($trg_db, $trg_link, $matching_tables, $source_indexes, $target_indexes, $add_indexes_array, $alter_indexes_array,
            $remove_indexes_array, $matching_table_structure_diff[$q], false);

            unset($add_indexes_array[$matching_table_structure_diff[$q]]);
            unset($alter_indexes_array[$matching_table_structure_diff[$q]]);
            unset($remove_indexes_array[$matching_table_structure_diff[$q]]);
        }
    }
    /**
    * Applying the data difference. First checks if structure diff is applied or not.
    * If not, then apply structure difference first then apply data difference.
    */
    for($p = 0; $p < sizeof($matching_table_data_diff); $p++)
    {
        if ($_REQUEST['checked'] == 'true') {

            PMA_findDeleteRowsFromTargetTables($delete_array, $matching_tables, $matching_table_data_diff[$p], $target_tables_keys,
            $matching_tables_keys, $trg_db, $trg_link, $src_db, $src_link);

            if (isset($delete_array[$matching_table_data_diff[$p]])) {

                PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $matching_table_data_diff[$p], $target_tables_keys, $delete_array, false);

                unset($delete_array[$matching_table_data_diff[$p]]);
            }
        }
        if (isset($alter_str_array[$matching_table_data_diff[$p]])) {

            PMA_alterTargetTableStructure($trg_db, $trg_link, $matching_tables, $source_columns, $alter_str_array, $matching_tables_fields,
            $criteria, $matching_tables_keys, $target_tables_keys, $matching_table_data_diff[$p], false);

            unset($alter_str_array[$matching_table_data_diff[$p]]);
        }
        if (isset($add_column_array[$matching_table_data_diff[$p]])) {

            PMA_findDeleteRowsFromTargetTables($delete_array, $matching_tables, $matching_table_data_diff[$p], $target_tables_keys,
            $matching_tables_keys, $trg_db, $trg_link, $src_db, $src_link);

            if (isset($delete_array[$matching_table_data_diff[$p]])) {

                PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $matching_table_data_diff[$p], $target_tables_keys, $delete_array, false);

                unset($delete_array[$matching_table_data_diff[$p]]);
            }
            PMA_addColumnsInTargetTable($src_db, $trg_db,$src_link, $trg_link, $matching_tables, $source_columns, $add_column_array, $matching_tables_fields,
            $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables, $uncommon_tables_fields, $matching_table_data_diff[$p], $uncommon_cols, false);

            unset($add_column_array[$matching_table_data_diff[$p]]);
        }
        if (isset($uncommon_columns[$matching_table_data_diff[$p]])) {

            PMA_removeColumnsFromTargetTable($trg_db, $trg_link, $matching_tables, $uncommon_columns, $matching_table_data_diff[$p], false);

            unset($uncommon_columns[$matching_table_data_diff[$p]]);
        }
        if ((isset($matching_table_structure_diff[$q]) && isset($add_indexes_array[$matching_table_structure_diff[$q]]))
            || (isset($matching_table_structure_diff[$q]) && isset($remove_indexes_array[$matching_table_structure_diff[$q]]))
            || (isset($matching_table_structure_diff[$q]) && isset($alter_indexes_array[$matching_table_structure_diff[$q]]))) {

            PMA_applyIndexesDiff ($trg_db, $trg_link, $matching_tables, $source_indexes, $target_indexes, $add_indexes_array, $alter_indexes_array,
            $remove_indexes_array, $matching_table_structure_diff[$q], false);

            unset($add_indexes_array[$matching_table_structure_diff[$q]]);
            unset($alter_indexes_array[$matching_table_structure_diff[$q]]);
            unset($remove_indexes_array[$matching_table_structure_diff[$q]]);
        }
        /**
        * Applying the data difference.
        */
        PMA_updateTargetTables($matching_tables, $update_array, $src_db, $trg_db, $trg_link, $matching_table_data_diff[$p], $matching_tables_keys, false);

        PMA_insertIntoTargetTable($matching_tables, $src_db, $trg_db, $src_link, $trg_link , $matching_tables_fields, $insert_array,
        $matching_table_data_diff[$p], $matching_tables_keys, $source_columns, $add_column_array, $criteria, $target_tables_keys,
        $uncommon_tables, $uncommon_tables_fields, $uncommon_cols, $alter_str_array, $source_indexes, $target_indexes, $add_indexes_array,
        $alter_indexes_array, $delete_array, $update_array, false);
    }
    /**
    * Updating the session variables to the latest values of the arrays.
    */
    $_SESSION['delete_array'] = $delete_array;
    $_SESSION['uncommon_columns'] = $uncommon_columns;
    $_SESSION['alter_str_array']  = $alter_str_array;
    $_SESSION['add_column_array'] = $add_column_array;
    $_SESSION['add_indexes_array'] = $add_indexes_array;
    $_SESSION['remove_indexes_array'] = $remove_indexes_array;
    $_SESSION['insert_array'] = $insert_array;
    $_SESSION['update_array'] = $update_array;

    /**
    * Applying structure difference to selected non-matching tables (present in Source but absent from Target).
    */
    for($s = 0; $s < sizeof($uncommon_table_structure_diff); $s++)
    {
        PMA_createTargetTables($src_db, $trg_db, $src_link, $trg_link, $uncommon_tables, $uncommon_table_structure_diff[$s], $uncommon_tables_fields, false);
        $_SESSION['uncommon_tables_fields'] = $uncommon_tables_fields;

        unset($uncommon_tables[$uncommon_table_structure_diff[$s]]);
    }
    /**
    * Applying data difference to selected non-matching tables (present in Source but absent from Target).
    * Before data synchronization, structure synchronization is confirmed.
    */
    for($r = 0; $r < sizeof($uncommon_table_data_diff); $r++)
    {
        if (!(in_array($uncommon_table_data_diff[$r], $uncommon_table_structure_diff))) {
            if (isset($uncommon_tables[$uncommon_table_data_diff[$r]])) {

                PMA_createTargetTables($src_db, $trg_db, $src_link, $trg_link, $uncommon_tables, $uncommon_table_data_diff[$r],
                    $uncommon_tables_fields, false);
                $_SESSION['uncommon_tables_fields'] = $uncommon_tables_fields;

                unset($uncommon_tables[$uncommon_table_data_diff[$r]]);
            }
        }
        PMA_populateTargetTables($src_db, $trg_db, $src_link, $trg_link, $source_tables_uncommon, $uncommon_table_data_diff[$r],
            $_SESSION['uncommon_tables_fields'], false);

        unset($row_count[$uncommon_table_data_diff[$r]]);
    }
    /**
    * Again all the tables from source and target database are displayed with their differences.
    * The differences have been removed from tables that have been synchronized
    */
    echo '<form name="applied_difference" id="synchronize_form" method="post" action="server_synchronize.php">'
        . PMA_generate_common_hidden_inputs('', '');

    PMA_syncDisplayHeaderSource($src_db);
    $odd_row = false;
    for($i = 0; $i < count($matching_tables); $i++) {
        $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
        echo '<td align="center">' . htmlspecialchars($matching_tables[$i]) . '</td>
        <td align="center">';

        $num_alter_cols  = 0;
        $num_insert_cols = 0;
        $num_remove_cols = 0;
        $num_add_index = 0;
        $num_remove_index = 0;

        if (isset($alter_str_array[$i])) {
            $num_alter_cols = sizeof($alter_str_array[$i]);
        }
        if (isset($add_column_array[$i])) {
            $num_insert_cols = sizeof($add_column_array[$i]);
        }
        if (isset($uncommon_columns[$i])) {
            $num_remove_cols = sizeof($uncommon_columns[$i]);
        }
        if (isset($add_indexes_array[$i])) {
            $num_add_index = sizeof($add_indexes_array[$i]);
        }
        if (isset($remove_indexes_array[$i])) {
            $num_remove_index = sizeof($remove_indexes_array[$i]);
        }

        if (($num_alter_cols > 0) || ($num_insert_cols > 0) || ($num_remove_cols > 0) || ($num_add_index > 0) || ($num_remove_index > 0)) {
            echo '<img class="icon struct_img" src="' . $pmaThemeImage .  'new_struct.jpg" width="29"  height="29"
            alt="' . __('Click to select') . '" 
            onclick="showDetails(' . "'MS" . $i . "','" . $num_alter_cols . "','" . $num_insert_cols . "','" . $num_remove_cols . "','" . $num_add_index . "','" . $num_remove_index . "'" .',
            this ,' . "'" . htmlspecialchars($matching_tables[$i]) . "'" . ')"/>';
        }
        if (!(in_array($i, $matching_table_data_diff))) {

            if (isset($matching_tables_keys[$i][0]) && isset($update_array[$i][0][$matching_tables_keys[$i][0]])) {
                if (isset($update_array[$i])) {
                    $num_of_updates = sizeof($update_array[$i]);
                } else {
                    $num_of_updates = 0;
                }
            } else {
                $num_of_updates = 0;
            }
            if (isset($matching_tables_keys[$i][0]) && isset($insert_array[$i][0][$matching_tables_keys[$i][0]])) {
                if (isset($insert_array[$i])) {
                    $num_of_insertions = sizeof($insert_array[$i]);
                } else {
                    $num_of_insertions = 0;
                }
            } else {
                $num_of_insertions = 0;
            }

            if ((isset($matching_tables_keys[$i][0]) && isset($update_array[$i][0][$matching_tables_keys[$i][0]]))
                || (isset($matching_tables_keys[$i][0]) && isset($insert_array[$i][0][$matching_tables_keys[$i][0]]))) {
                echo '<img class="icon data_img" src="' . $pmaThemeImage . 'new_data.jpg" width="29" height="29"
                alt="' . __('Click to select') . '"
                onclick="showDetails(' . "'MD" . $i . "','" . $num_of_updates . "','" . $num_of_insertions .
                "','" . null . "','" . null . "','" . null . "'" .', this ,' . "'" . htmlspecialchars($matching_tables[$i]) . "'" . ')" />';
            }
        } else {
            unset($update_array[$i]);
            unset($insert_array[$i]);
        }
        echo '</td>
        </tr>';
    }
    /**
    * placing updated value of arrays in session
    *
    */
    $_SESSION['update_array'] = $update_array;
    $_SESSION['insert_array'] = $insert_array;

    for ($j = 0; $j < count($source_tables_uncommon); $j++) {
        $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
        echo '<td align="center"> + ' . htmlspecialchars($source_tables_uncommon[$j]) . '</td>
        <td align="center">';
        /**
        * Display the difference only when it has not been applied
        */
        if (!(in_array($j, $uncommon_table_structure_diff))) {
            if (isset($uncommon_tables[$j])) {
                echo '<img class="icon struct_img" src="' . $pmaThemeImage  . 'new_struct.jpg" width="29"  height="29"
                alt="' . __('Click to select') . '"
                onclick="showDetails(' . "'US" . $j . "','" . null . "','" . null . "','" . null . "','" . null . "','" . null . "'" . ', this ,' . "'" . htmlspecialchars($source_tables_uncommon[$j]) . "'" . ')"/>' .' ';
            }
        } else {
            unset($uncommon_tables[$j]);
        }
        /**
        * Display the difference only when it has not been applied
        */
        if (!(in_array($j, $uncommon_table_data_diff))) {
            if (isset($row_count[$j]) && ($row_count > 0)) {
                echo '<img class="icon data_img" src="' . $pmaThemeImage . 'new_data.jpg" width="29" height="29"
                alt="' . __('Click to select') . '" 
                onclick="showDetails(' . "'UD" . $j . "','" . null ."','" . $row_count[$j] ."','"
                . null . "','" . null . "','" . null . "'" . ', this ,' . "'". htmlspecialchars($source_tables_uncommon[$j]) . "'" . ')" />';
            }
        } else {
            unset($row_count[$j]);
        }

        echo '</td>
        </tr>';
    }
    /**
    * placing the latest values of arrays in session
    */

    $_SESSION['uncommon_tables'] = $uncommon_tables;
    $_SESSION['uncommon_tables_row_count'] = $row_count;


    /**
    * Displaying the target database tables
    */
    foreach ($target_tables_uncommon as $tbl_nc_name) {
        $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
        echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td><td></td>';
        echo '</tr>';
    }
    echo '</table>';
    $odd_row = PMA_syncDisplayHeaderTargetAndMatchingTables($trg_db, $matching_tables);
    foreach ($source_tables_uncommon as $tbl_nc_name) {
        $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
        if (in_array($tbl_nc_name, $uncommon_tables)) {
            echo '<td>' . htmlspecialchars($tbl_nc_name) . ' (' .  __('not present') . ')</td>';
        } else {
            echo '<td>' . htmlspecialchars($tbl_nc_name) . '</td>';
        }
        echo '
        </tr>';
    }
    foreach ($target_tables_uncommon as $tbl_nc_name) {
        $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
        echo '<td> - ' . htmlspecialchars($tbl_nc_name) . '</td>';
        echo '</tr>';
    }
    echo '</table>
    </div>';

    /**
    * This "list" div will contain a table and each row will depict information about structure/data diffrence in tables.
    * Rows will be generated dynamically as soon as the colored  buttons "D" or "S"  are clicked.
    */

    echo '<div id="list" style = "overflow: auto; width: 1020px; height: 140px;
          border-left: 1px gray solid; border-bottom: 1px gray solid;
          padding:0px; margin: 0px">';

    echo '<table>
          <thead>
            <tr style="width: 100%;">
                <th id="table_name" style="width: 10%;" colspan="1">' . __('Table') . ' </th>
                <th id="str_diff"   style="width: 65%;" colspan="6">' . __('Structure Difference') . ' </th>
                <th id="data_diff"  style="width: 20%;" colspan="2">' . __('Data Difference') . '</th>
            </tr>
            <tr style="width: 100%;">
                <th style="width: 10%;">' . __('Table name') . '</th>
                <th style="width: 10%;">' . __('Create table'). '</th>
                <th style="width: 11%;">' . __('Add column(s)') . '</th>
                <th style="width: 13%;">' . __('Remove column(s)') . '</th>
                <th style="width: 11%;">' . __('Alter column(s)') . '</th>
                <th style="width: 12%;">' . __('Remove index(s)') . '</th>
                <th style="width: 11%;">' . __('Apply index(s)') . '</th>
                <th style="width: 10%;">' . __('Update row(s)') . '</th>
                <th style="width: 10%;">' . __('Insert row(s)') . '</th>
            </tr>
            </thead>
            <tbody></tbody>
         </table>
        </div>';

    /**
    *  This fieldset displays the checkbox to confirm deletion of previous rows from target tables
    */
    echo '<fieldset>
    <p><input type="checkbox" name="delete_rows" id ="delete_rows" /><label for="delete_rows">' . __('Would you like to delete all the previous rows from target tables?') . '</label> </p>
    </fieldset>';

    echo '<fieldset class="tblFooters">';
    echo '<input type="button" name="apply_changes" value="' . __('Apply Selected Changes') . '"
          onclick ="ApplySelectedChanges(' . "'" . htmlspecialchars($_SESSION['token']) . "'" .')" />';
    echo '<input type="submit" name="synchronize_db" value="' . __('Synchronize Databases') . '" />'
          . '</fieldset>';
    echo '</form>';
}

/**
* Displays the page when 'Synchronize Databases' is pressed.
*/

if (isset($_REQUEST['synchronize_db'])) {

    $src_db = $_SESSION['src_db'];
    $trg_db = $_SESSION['trg_db'];
    $update_array = $_SESSION['update_array'];
    $insert_array = $_SESSION['insert_array'];
    $src_username = $_SESSION['src_username'];
    $trg_username = $_SESSION['trg_username'];
    $src_password = $_SESSION['src_password'];
    $trg_password = $_SESSION['trg_password'];
    $matching_tables = $_SESSION['matching_tables'];
    $matching_tables_keys = $_SESSION['matching_tables_keys'];
    $matching_tables_fields = $_SESSION['matching_fields'];
    $source_tables_uncommon = $_SESSION['src_uncommon_tables'];
    $uncommon_tables_fields = $_SESSION['uncommon_tables_fields'];
    $target_tables_uncommon = $_SESSION['target_tables_uncommon'];
    $row_count = $_SESSION['uncommon_tables_row_count'];
    $uncommon_tables = $_SESSION['uncommon_tables'];
    $target_tables = $_SESSION['target_tables'];

    $delete_array = $_SESSION['delete_array'];
    $uncommon_columns = $_SESSION['uncommon_columns'];
    $source_columns = $_SESSION['source_columns'];
    $alter_str_array = $_SESSION['alter_str_array'];
    $criteria = $_SESSION['criteria'];
    $target_tables_keys = $_SESSION['target_tables_keys'];
    $add_column_array = $_SESSION['add_column_array'];
    $add_indexes_array = $_SESSION['add_indexes_array'];
    $alter_indexes_array = $_SESSION['alter_indexes_array'];
    $remove_indexes_array = $_SESSION['remove_indexes_array'];
    $source_indexes = $_SESSION['source_indexes'];
    $target_indexes = $_SESSION['target_indexes'];
    $uncommon_cols = $uncommon_columns;

   /**
   * Display success message.
   */
    echo '<div class="success">' . __('Target database has been synchronized with source database') . '</div>';
    /**
    * Displaying all the tables of source and target database and now no difference is there.
    */
    PMA_syncDisplayHeaderSource($src_db);

        $odd_row = false;
        for($i = 0; $i < count($matching_tables); $i++)
        {
            $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
            echo '<td>' . htmlspecialchars($matching_tables[$i]) . '</td>
            <td></td>
            </tr>';
        }
        for ($j = 0; $j < count($source_tables_uncommon); $j++) {
            $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
            echo '<td> + ' . htmlspecialchars($source_tables_uncommon[$j]) . '</td> ';
            echo '<td></td>
            </tr>';
        }
        foreach ($target_tables_uncommon as $tbl_nc_name) {
            $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
            echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td><td></td>';
            echo '</tr>';
        }
        echo '</table>';
        $odd_row = PMA_syncDisplayHeaderTargetAndMatchingTables($trg_db, $matching_tables);
        foreach ($source_tables_uncommon as $tbl_nc_name) {
            $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
            echo '<td>' . htmlspecialchars($tbl_nc_name) . ' </td>
            </tr>';
        }
        foreach ($target_tables_uncommon as $tbl_nc_name) {
            $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
            echo '<td>  ' . htmlspecialchars($tbl_nc_name) . '</td>';
            echo '</tr>';
        }
    echo '</table> </div>';

    /**
    * connecting the source and target servers
    */
    if ('cur' != $_SESSION['src_type']) {
        $src_link = PMA_DBI_connect($src_username, $src_password, $is_controluser = false, $_SESSION['src_server']);
    } else {
        $src_link = $GLOBALS['userlink'];
        // working on current server, so initialize this for tracking
        // (does not work if user defined current server as a remote one)
        $GLOBALS['db'] = $_SESSION['src_db'];
    }
    if ('cur' != $_SESSION['trg_type']) {
        $trg_link = PMA_DBI_connect($trg_username, $trg_password, $is_controluser = false, $_SESSION['trg_server']);
    } else {
        $trg_link = $GLOBALS['userlink'];
        // working on current server, so initialize this for tracking
        $GLOBALS['db'] = $_SESSION['trg_db'];
    }

    /**
    * Displaying the queries.
    */
    echo '<h5>' . __('The following queries have been executed:') . '</h5>';
    echo '<div id="serverstatus" style = "overflow: auto; width: 1050px; height: 180px;
         border-left: 1px gray solid; border-bottom: 1px gray solid; padding: 0px; margin: 0px"> ';
    /**
    * Applying all sorts of differences for each matching table
    */
    for($p = 0; $p < sizeof($matching_tables); $p++) {
        /**
        *  If the check box is checked for deleting previous rows from the target database tables then
        *  first find out rows to be deleted and then delete the rows.
        */
        if (isset($_REQUEST['delete_rows'])) {
            PMA_findDeleteRowsFromTargetTables($delete_array, $matching_tables, $p, $target_tables_keys, $matching_tables_keys,
                $trg_db, $trg_link, $src_db, $src_link);

            if (isset($delete_array[$p])) {
                PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $p, $target_tables_keys, $delete_array, true);
                unset($delete_array[$p]);
            }
        }
        if (isset($alter_str_array[$p])) {
            PMA_alterTargetTableStructure($trg_db, $trg_link, $matching_tables, $source_columns, $alter_str_array, $matching_tables_fields,
            $criteria, $matching_tables_keys, $target_tables_keys, $p, true);
            unset($alter_str_array[$p]);
        }
        if (! empty($add_column_array[$p])) {
            PMA_findDeleteRowsFromTargetTables($delete_array, $matching_tables, $p, $target_tables_keys, $matching_tables_keys,
            $trg_db, $trg_link, $src_db, $src_link);

            if (isset($delete_array[$p])) {
                PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $p, $target_tables_keys, $delete_array, true);
                unset($delete_array[$p]);
            }
            PMA_addColumnsInTargetTable($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns, $add_column_array,
                $matching_tables_fields, $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables, $uncommon_tables_fields,
                $p, $uncommon_cols, true);
            unset($add_column_array[$p]);
        }
        if (isset($uncommon_columns[$p])) {
            PMA_removeColumnsFromTargetTable($trg_db, $trg_link, $matching_tables, $uncommon_columns, $p, true);
            unset($uncommon_columns[$p]);
        }
        if (isset($matching_table_structure_diff) &&
            (isset($add_indexes_array[$matching_table_structure_diff[$p]])
            || isset($remove_indexes_array[$matching_table_structure_diff[$p]])
            || isset($alter_indexes_array[$matching_table_structure_diff[$p]]))) {
            PMA_applyIndexesDiff ($trg_db, $trg_link, $matching_tables, $source_indexes, $target_indexes, $add_indexes_array, $alter_indexes_array,
            $remove_indexes_array, $matching_table_structure_diff[$p], true);

            unset($add_indexes_array[$matching_table_structure_diff[$p]]);
            unset($alter_indexes_array[$matching_table_structure_diff[$p]]);
            unset($remove_indexes_array[$matching_table_structure_diff[$p]]);
        }

        PMA_updateTargetTables($matching_tables, $update_array, $src_db, $trg_db, $trg_link, $p, $matching_tables_keys, true);

        PMA_insertIntoTargetTable($matching_tables, $src_db, $trg_db, $src_link, $trg_link , $matching_tables_fields, $insert_array, $p,
            $matching_tables_keys, $matching_tables_keys, $source_columns, $add_column_array, $criteria, $target_tables_keys, $uncommon_tables,
            $uncommon_tables_fields,$uncommon_cols, $alter_str_array,$source_indexes, $target_indexes, $add_indexes_array,
            $alter_indexes_array, $delete_array, $update_array, true);
    }

    /**
    *  Creating and populating tables present in source but absent from target database.
    */
    for($q = 0; $q < sizeof($source_tables_uncommon); $q++) {
        if (isset($uncommon_tables[$q])) {
            PMA_createTargetTables($src_db, $trg_db, $src_link, $trg_link, $source_tables_uncommon, $q, $uncommon_tables_fields, true);
        }
        if (isset($row_count[$q])) {
            PMA_populateTargetTables($src_db, $trg_db, $src_link, $trg_link, $source_tables_uncommon, $q, $uncommon_tables_fields, true);
        }
    }
    echo "</div>";
}

/**
 * Displays the main page when none of the following buttons is pressed
 */

 if (! isset($_REQUEST['submit_connect']) && ! isset($_REQUEST['synchronize_db']) && ! isset($_REQUEST['Table_ids']) )
{
/**
* Displays the sub-page heading
*/
    echo '<h2>' . ($GLOBALS['cfg']['MainPageIconic']
    ? '<img class="icon" src="' . $pmaThemeImage . 's_sync.png" width="18"'
        . ' height="18" alt="" />'
    : '')
    . __('Synchronize')
    .'</h2>';

    echo  '<div id="serverstatus">
    <form name="connection_form" id="connection_form" method="post" action="server_synchronize.php"
   >' // TODO: add check if all var. are filled in
    . PMA_generate_common_hidden_inputs('', '');
    echo '<fieldset>';
    echo '<legend>' . __('Synchronize') . '</legend>';
    /**
     * Displays the forms
     */

    $databases = PMA_DBI_get_databases_full(null, false, null, 'SCHEMA_NAME',
        'ASC', 0, true);

    if ($GLOBALS['cfg']['AllowArbitraryServer'] === false) {
        $possibly_readonly = ' readonly="readonly"';
    } else {
        $possibly_readonly = '';
    }

    foreach ($cons as $type) {
        if ('src' == $type) {
            $database_header = __('Source database');
        } else {
            $database_header = __('Target database');
        }

        $database_header .= PMA_showHint(PMA_sanitize(sprintf('%sAllowArbitraryServer%s', '[a@./Documentation.html#AllowArbitraryServer@_blank]', '[/a]')));
?>
      <table id="serverconnection_<?php echo $type; ?>_remote" class="data">
      <caption class="tblHeaders"><?php echo $database_header; ?></caption>
      <tr class="odd">
	  <td colspan="2" style="text-align: center">
	     <select name="<?php echo $type; ?>_type" id="<?php echo $type; ?>_type" class="server_selector">
<?php
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            $preselected_option = 'rmt';
            echo '<option value="rmt" selected="selected">' . __('Enter manually') . '</option>';
        } else {
            $preselected_option = 'cur';
        }
        echo '<option value="cur"';
        if ('cur' == $preselected_option) {
            echo ' selected="selected"';
        }
        echo '>' .  __('Current connection') . '</option>';

        foreach ($GLOBALS['cfg']['Servers'] as $key => $tmp_server) {
            if (empty($tmp_server['host'])) {
                continue;
            }

            if (!empty($tmp_server['verbose'])) {
                $label = $tmp_server['verbose'];
            } else {
                $label = $tmp_server['host'];
                if (!empty($tmp_server['port'])) {
                    $label .= ':' . $tmp_server['port'];
                }
            }
            $value = $tmp_server['host'];
            $value .= '||||';
            if (empty($tmp_server['port']) && empty($tmp_server['socket'])) {
                $value .= '3306';
            } else {
                $value .= $tmp_server['port'];
            }
            $value .= '||||';
            $value .= $tmp_server['socket'];
            $value .= '||||';
            $value .= $tmp_server['user'];
            $value .= '||||';
            $value .= $tmp_server['only_db'];
            echo '<option value="' . $value . '" >'
                . htmlspecialchars(sprintf(__('Configuration: %s'), $label)) . '</option>';
        } // end foreach
?>
	     </select>
	  </td>
      </tr>
	<tr class="even toggler remote-server">
	    <td><?php echo __('Server'); ?></td>
        <td><input type="text" name="<?php echo $type; ?>_host" class="server-host" <?php echo $possibly_readonly; ?>/></td>
	</tr>
	<tr class="odd toggler remote-server">
	    <td><?php echo __('Port'); ?></td>
        <td><input type="text" name="<?php echo $type; ?>_port" class="server-port" <?php echo $possibly_readonly; ?> value="3306" maxlength="5" size="5" /></td>
	</tr>
	<tr class="even toggler remote-server">
	    <td><?php echo __('Socket'); ?></td>
        <td><input type="text" name="<?php echo $type; ?>_socket" class="server-socket" <?php echo $possibly_readonly; ?>/></td>
	</tr>
	<tr class="odd toggler remote-server">
	    <td><?php echo __('User name'); ?></td>
        <td><input type="text" name="<?php echo $type; ?>_username" class="server-user" /></td>
	</tr>
	<tr class="even toggler remote-server">
	    <td><?php echo __('Password'); ?></td>
        <td><input type="password" name="<?php echo $type; ?>_pass" class="server-pass" /> </td>
	</tr>
	<tr class="odd toggler remote-server">
	    <td><?php echo __('Database'); ?></td>
        <td><input type="text" name="<?php echo $type; ?>_db" class="server-db" /></td>
	</tr>
	<tr class="even toggler current-server" style="display: none;">
	    <td><?php echo __('Database'); ?></td>
	    <td>
<?php
      // these unset() do not complain if the elements do not exist
    unset($databases['mysql']);
    unset($databases['information_schema']);

	if (count($databases) == 0) {
		echo __('No databases');
	} else {
		echo '
	      	<select name="' . $type . '_db_sel">
		';
		foreach ($databases as $db) {
            echo '		<option>' . htmlspecialchars($db['SCHEMA_NAME']) . '</option>';
		}
        echo '</select>';
	}
	echo '</td> </tr>
      </table>';
   }
   unset ($types, $type);

    echo '
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" name="submit_connect" value="' . __('Go') .'" id="buttonGo" />
    </fieldset>
    </form>
    </div>
    <div class="notice">' . __('Target database will be completely synchronized with source database. Source database will remain unchanged.') . '</div>';
}

 /**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
