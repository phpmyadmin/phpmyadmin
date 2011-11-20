<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server synchronisation functions.
 *
 * @package PhpMyAdmin
 */

/**
 * Places matching tables in source and target databases in $matching_tables
 * array whereas $uncommon_source_tables array gets the tables present in
 * source database but are absent from target database.  Criterion for
 * matching tables is just comparing their names.
 *
 * @param array $trg_tables              array of target database table names,
 * @param array $src_tables              array of source database table names,
 * @param array &$matching_tables        empty array passed by reference to save
 *                                       names of matching tables,
 * @param array &$uncommon_source_tables empty array passed by reference to save
 *                                       names of tables present in source database
 *                                       but absent from target database
 */
function PMA_getMatchingTables($trg_tables, $src_tables, &$matching_tables, &$uncommon_source_tables)
{
    for ($k=0; $k< sizeof($src_tables); $k++) {
        $present_in_target = false;
        for ($l=0; $l < sizeof($trg_tables); $l++) {
            if ($src_tables[$k] === $trg_tables[$l]) {
                $present_in_target = true;
                $matching_tables[] = $src_tables[$k];
            }
        }
        if ($present_in_target === false) {
            $uncommon_source_tables[] = $src_tables[$k];
        }
    }
}

/**
 * Places tables present in target database but are absent from source database
 *
 * @param array $trg_tables              array of target database table names,
 * @param array $matching_tables         matching tables array containing names
 *                                       of matching tables,
 * @param array &$uncommon_target_tables empty array passed by reference to save
 *                                       names of tables presnet in target database
 *                                       but absent from source database
 */
function PMA_getNonMatchingTargetTables($trg_tables, $matching_tables, &$uncommon_target_tables)
{
    for ($c=0; $c<sizeof($trg_tables); $c++) {
        $match = false;
        for ($d=0; $d < sizeof($matching_tables); $d++) {
            if ($trg_tables[$c] === $matching_tables[$d]) {
                $match=true;
            }
        }
        if ($match === false) {
            $uncommon_target_tables[] = $trg_tables[$c];
        }
    }
}

/**
 * Finds the difference in source and target matching tables by
 * first comparing source table's primary key entries with target table enteries.
 * It gets the field names for the matching table also for comparisons.
 * If the entry is found in target table also then it is checked for the remaining
 * field values also, in order to check whether update is required or not.
 * If update is required, it is placed in $update_array
 * Otherwise that entry is placed in the $insert_array.
 *
 * @param string  $src_db                  name of source database
 * @param string  $trg_db                  name of target database
 * @param db_link $src_link                connection established with source server
 * @param db_link $trg_link                connection established with target server
 * @param array   &$matching_table         array containing matching table names
 * @param array   &$matching_tables_fields A two dimensional array passed by reference to contain names of fields for each matching table
 * @param array   &$update_array           A three dimensional array passed by reference to
 *                                         contain updates required for each matching table
 * @param array   &$insert_array           A three dimensional array passed by reference to
 *                                         contain inserts required for each matching table
 * @param array   &$delete_array           Unused
 * @param array   &$fields_num             A two dimensional array passed by reference to
 *                                         contain number of fields for each matching table
 * @param int     $matching_table_index    Index of a table from $matching_table array
 * @param array   &$matching_tables_keys   A two dimensional array passed by reference to contain names of keys for each matching table
 */
function PMA_dataDiffInTables($src_db, $trg_db, $src_link, $trg_link, &$matching_table, &$matching_tables_fields,
    &$update_array, &$insert_array, &$delete_array, &$fields_num, $matching_table_index, &$matching_tables_keys)
{
    if (isset($matching_table[$matching_table_index])) {
        $fld = array();
        $fld_results = PMA_DBI_get_columns($src_db, $matching_table[$matching_table_index], null, true, $src_link);
        $is_key = array();
        if (isset($fld_results)) {
            foreach ($fld_results as $each_field) {
                $field_name = $each_field['Field'];
                if ($each_field['Key'] == 'PRI') {
                    $is_key[] = $field_name;
                }
                $fld[] = $field_name;
            }
        }
        $matching_tables_fields[$matching_table_index] = $fld;
        $fields_num[$matching_table_index] = sizeof($fld);
        $matching_tables_keys[$matching_table_index] = $is_key;

        $source_result_set = PMA_get_column_values($src_db, $matching_table[$matching_table_index], $is_key, $src_link);
        $source_size = sizeof($source_result_set);

        $trg_fld_results = PMA_DBI_get_columns($trg_db, $matching_table[$matching_table_index], null, true, $trg_link);
        $all_keys_match = true;
        $trg_keys = array();

        if (isset($trg_fld_results)) {
            foreach ($trg_fld_results as $each_field) {
                if ($each_field['Key'] == 'PRI') {
                    $trg_keys[] = $each_field['Field'];
                    if (! (in_array($each_field['Field'], $is_key))) {
                        $all_keys_match = false;
                    }
                }
            }
        }
        $update_row = 0;
        $insert_row = 0;

        for ($j = 0; $j < $source_size; $j++) {
            $starting_index = 0;
            $update_field = 0;

            if (isset($source_result_set[$j]) && ($all_keys_match)) {

                // Query the target server to see which rows already exist
                $trg_select_query = "SELECT * FROM " . PMA_backquote($trg_db) . "."
                    . PMA_backquote($matching_table[$matching_table_index]) . " WHERE ";

                if (sizeof($is_key) == 1) {
                    $trg_select_query .= PMA_backquote($is_key[0]). "='" . $source_result_set[$j] . "'";
                } elseif (sizeof($is_key) > 1) {
                    for ($k=0; $k < sizeof($is_key); $k++) {
                        $trg_select_query .= PMA_backquote($is_key[$k]) . "='" . $source_result_set[$j][$is_key[$k]] . "'";
                        if ($k < (sizeof($is_key)-1)) {
                            $trg_select_query .= " AND ";
                        }
                    }
                }

                $target_result_set = PMA_DBI_fetch_result($trg_select_query, null, null, $trg_link);
                if ($target_result_set) {

                    // Fetch the row from the source server to do a comparison
                    $src_select_query = "SELECT * FROM " . PMA_backquote($src_db) . "."
                        . PMA_backquote($matching_table[$matching_table_index]) . " WHERE ";

                    if (sizeof($is_key) == 1) {
                        $src_select_query .= PMA_backquote($is_key[0]) . "='" . $source_result_set[$j] . "'";
                    } elseif (sizeof($is_key) > 1) {
                        for ($k=0; $k< sizeof($is_key); $k++) {
                            $src_select_query .= PMA_backquote($is_key[$k]) . "='" . $source_result_set[$j][$is_key[$k]] . "'";
                            if ($k < (sizeof($is_key) - 1)) {
                                $src_select_query .= " AND ";
                            }
                        }
                    }

                    $src_result_set = PMA_DBI_fetch_result($src_select_query, null, null, $src_link);

                    /**
                    * Comparing each corresponding field of the source and target matching rows.
                    * Placing the primary key, value of primary key, field to be updated, and the
                    * new value of field to be updated in each row of the update array.
                    */
                    for ($m = 0; ($m < $fields_num[$matching_table_index]) && ($starting_index == 0) ; $m++) {
                        if (isset($src_result_set[0][$fld[$m]])) {
                            if (isset($target_result_set[0][$fld[$m]])) {
                                if (($src_result_set[0][$fld[$m]] != $target_result_set[0][$fld[$m]]) && (! (in_array($fld[$m], $is_key)))) {
                                    if (sizeof($is_key) == 1) {
                                        if ($source_result_set[$j]) {
                                            $update_array[$matching_table_index][$update_row][$is_key[0]] = $source_result_set[$j];
                                        }
                                    } elseif (sizeof($is_key) > 1) {
                                        for ($n=0; $n < sizeof($is_key); $n++) {
                                            if (isset($src_result_set[0][$is_key[$n]])) {
                                                $update_array[$matching_table_index][$update_row][$is_key[$n]] = $src_result_set[0][$is_key[$n]];
                                            }
                                        }
                                    }

                                    $update_array[$matching_table_index][$update_row][$update_field] = $fld[$m];

                                    $update_field++;
                                    if (isset($src_result_set[0][$fld[$m]])) {
                                        $update_array[$matching_table_index][$update_row][$update_field] = $src_result_set[0][$fld[$m]];
                                        $update_field++;
                                    }
                                    $starting_index = $m;
                                    $update_row++;
                                }
                            } else {
                                if (sizeof($is_key) == 1) {
                                    if ($source_result_set[$j]) {
                                        $update_array[$matching_table_index][$update_row][$is_key[0]] = $source_result_set[$j];
                                    }
                                } elseif (sizeof($is_key) > 1) {
                                    for ($n = 0; $n < sizeof($is_key); $n++) {
                                        if (isset($src_result_set[0][$is_key[$n]])) {
                                            $update_array[$matching_table_index][$update_row][$is_key[$n]] = $src_result_set[0][$is_key[$n]];
                                        }
                                    }
                                }

                                $update_array[$matching_table_index][$update_row][$update_field] = $fld[$m];

                                $update_field++;
                                if (isset($src_result_set[0][$fld[$m]])) {
                                    $update_array[$matching_table_index][$update_row][$update_field] = $src_result_set[0][$fld[$m]];
                                    $update_field++;
                                }
                                $starting_index = $m;
                                $update_row++;
                            }
                        }
                    }
                    for ($m = $starting_index + 1; $m < $fields_num[$matching_table_index] ; $m++) {
                        if (isset($src_result_set[0][$fld[$m]])) {
                            if (isset($target_result_set[0][$fld[$m]])) {
                                if (($src_result_set[0][$fld[$m]] != $target_result_set[0][$fld[$m]]) && (!(in_array($fld[$m], $is_key)))) {
                                    $update_row--;
                                    $update_array[$matching_table_index][$update_row][$update_field] = $fld[$m];
                                    $update_field++;
                                    if ($src_result_set[0][$fld[$m]]) {
                                        $update_array[$matching_table_index][$update_row][$update_field] = $src_result_set[0][$fld[$m]];
                                        $update_field++;
                                    }
                                    $update_row++;
                                }
                            } else {
                                $update_row--;
                                $update_array[$matching_table_index][$update_row][$update_field] = $fld[$m];
                                $update_field++;
                                if ($src_result_set[0][$fld[$m]]) {
                                    $update_array[$matching_table_index][$update_row][$update_field] = $src_result_set[0][$fld[$m]];
                                    $update_field++;
                                }
                                $update_row++;
                            }
                        }
                    }
                } else {
                    /**
                     * Placing the primary key, and the value of primary key of the row that is to be inserted in the target table
                     */
                    if (sizeof($is_key) == 1) {
                        if (isset($source_result_set[$j])) {
                            $insert_array[$matching_table_index][$insert_row][$is_key[0]] = $source_result_set[$j];
                        }
                    } elseif (sizeof($is_key) > 1) {
                        for ($l = 0; $l < sizeof($is_key); $l++) {
                            if (isset($source_result_set[$j][$matching_tables_fields[$matching_table_index][$l]])) {
                                $insert_array[$matching_table_index][$insert_row][$is_key[$l]] = $source_result_set[$j][$matching_tables_fields[$matching_table_index][$l]];
                            }
                        }
                    }
                    $insert_row++;
                }
            } else {
                /**
                 * Placing the primary key, and the value of primary key of the row that is to be inserted in the target table
                 * This condition is met when there is an additional column in the source table
                 */
                if (sizeof($is_key) == 1) {
                    if (isset($source_result_set[$j])) {
                        $insert_array[$matching_table_index][$insert_row][$is_key[0]] = $source_result_set[$j];
                    }
                } elseif (sizeof($is_key) > 1) {
                    for ($l = 0; $l < sizeof($is_key); $l++) {
                        if (isset($source_result_set[$j][$matching_tables_fields[$matching_table_index][$l]])) {
                            $insert_array[$matching_table_index][$insert_row][$is_key[$l]] = $source_result_set[$j][$matching_tables_fields[$matching_table_index][$l]];
                        }
                    }
                }
                $insert_row++;
            }
        } // for loop ends
    }
}

/**
 * Finds the rows which are to be deleted from target table.
 *
 * @param array   &$delete_array        array containing rows that are to be deleted
 * @param array   $matching_table       array containing matching table names
 * @param int     $matching_table_index index of a table from $matching_table array
 * @param array   $trg_keys             array of target table keys
 * @param array   $src_keys             array of source table keys
 * @param string  $trg_db               name of target database
 * @param db_link $trg_link             connection established with target server
 * @param string  $src_db               name of source database
 * @param db_link $src_link             connection established with source server
 */
function PMA_findDeleteRowsFromTargetTables(&$delete_array, $matching_table, $matching_table_index, $trg_keys, $src_keys, $trg_db, $trg_link, $src_db, $src_link)
{
    if (isset($trg_keys[$matching_table_index])) {
        $target_key_values = PMA_get_column_values($trg_db, $matching_table[$matching_table_index], $trg_keys[$matching_table_index], $trg_link);
    }
    if (isset($src_keys[$matching_table_index])) {
        $source_key_values = PMA_get_column_values($src_db, $matching_table[$matching_table_index], $src_keys[$matching_table_index], $src_link);
    }
    $all_keys_match = 1;
    for ($a = 0; $a < sizeof($trg_keys[$matching_table_index]); $a++) {
        if (isset($trg_keys[$matching_table_index][$a])) {
            if (! (in_array($trg_keys[$matching_table_index][$a], $src_keys[$matching_table_index]))) {
                $all_keys_match = 0;
            }
        }
    }
    if (! ($all_keys_match)) {
        if (isset($target_key_values)) {
            $delete_array[$matching_table_index] = $target_key_values;
        }
    }
    if (isset($trg_keys[$matching_table_index])) {
        if ((sizeof($trg_keys[$matching_table_index]) == 1) && $all_keys_match) {
            $row = 0;
            if (isset($target_key_values)) {
                for ($i = 0; $i < sizeof($target_key_values); $i++) {
                    if (! (in_array($target_key_values[$i], $source_key_values))) {
                        $delete_array[$matching_table_index][$row] = $target_key_values[$i];
                        $row++;
                    }
                }
            }
        } elseif ((sizeof($trg_keys[$matching_table_index]) > 1) && $all_keys_match) {
            $row = 0;
            if (isset($target_key_values)) {
                for ($i = 0; $i < sizeof($target_key_values); $i++) {
                    $is_present = false;
                    for ($j = 0; $j < sizeof($source_key_values) && ($is_present == false) ; $j++) {
                        $check = true;
                        for ($k = 0; $k < sizeof($trg_keys[$matching_table_index]); $k++) {
                            if ($target_key_values[$i][$trg_keys[$matching_table_index][$k]] != $source_key_values[$j][$trg_keys[$matching_table_index][$k]]) {
                                $check = false;
                            }
                        }
                        if ($check) {
                            $is_present = true;
                        }
                    }
                    if (! ($is_present)) {
                        for ($l = 0; $l < sizeof($trg_keys[$matching_table_index]); $l++) {
                            $delete_array[$matching_table_index][$row][$trg_keys[$matching_table_index][$l]] = $target_key_values[$i][$trg_keys[$matching_table_index][$l]];
                        }
                        $row++;
                    }
                }
            }
        }
    }
}

/**
 * PMA_dataDiffInUncommonTables() finds the data difference in  $source_tables_uncommon
 *
 * @param array  $source_tables_uncommon table names that are in source db and not in target db
 * @param string $src_db                 name of source database
 * @param mixed  $src_link               connection established with source server
 * @param int    $index                  index of a table from $matching_table array
 * @param array  &$row_count             number of rows
 *
 * @return nothing
 */
function PMA_dataDiffInUncommonTables($source_tables_uncommon, $src_db, $src_link, $index, &$row_count)
{
    $query = "SELECT COUNT(*) FROM " . PMA_backquote($src_db) . "." . PMA_backquote($source_tables_uncommon[$index]);
    $rows  = PMA_DBI_fetch_result($query, null, null, $src_link);
    $row_count[$index] = $rows[0];
}

/**
 * PMA_updateTargetTables() sets the updated field values to target table rows using $update_array[$matching_table_index]
 *
 * @param array   $table                Matching tables' names
 * @param array   $update_array         A three dimensional array containing field
 *                                      value updates required for each matching table
 * @param string  $src_db               Name of source database
 * @param string  $trg_db               Name of target database
 * @param mixed   $trg_link             Connection established with target server
 * @param int     $matching_table_index index of matching table in matching_table_array
 * @param array   $matching_table_keys
 * @param boolean $display
 */
function PMA_updateTargetTables($table, $update_array, $src_db, $trg_db, $trg_link, $matching_table_index, $matching_table_keys, $display)
{
    if (isset($update_array[$matching_table_index])) {
        if (sizeof($update_array[$matching_table_index])) {

            for ($update_row = 0; $update_row < sizeof($update_array[$matching_table_index]); $update_row++) {

                if (isset($update_array[$matching_table_index][$update_row])) {
                    $update_fields_num = sizeof($update_array[$matching_table_index][$update_row])-sizeof($matching_table_keys[$matching_table_index]);
                    if ($update_fields_num > 0) {
                        $query = "UPDATE " . PMA_backquote($trg_db) . "." .PMA_backquote($table[$matching_table_index]) . " SET ";

                        for ($update_field = 0; $update_field < $update_fields_num; $update_field = $update_field+2) {
                            if (isset($update_array[$matching_table_index][$update_row][$update_field]) && isset($update_array[$matching_table_index][$update_row][$update_field+1])) {
                                $query .= PMA_backquote($update_array[$matching_table_index][$update_row][$update_field]) . "='" . $update_array[$matching_table_index][$update_row][$update_field+1] . "'";
                            }
                            if ($update_field < ($update_fields_num - 2)) {
                                $query .= ", ";
                            }
                        }
                        $query .= " WHERE ";
                        if (isset($matching_table_keys[$matching_table_index])) {
                            for ($key = 0; $key < sizeof($matching_table_keys[$matching_table_index]); $key++) {
                                if (isset($matching_table_keys[$matching_table_index][$key])) {
                                    $query .= PMA_backquote($matching_table_keys[$matching_table_index][$key]) . "='" . $update_array[$matching_table_index][$update_row][$matching_table_keys[$matching_table_index][$key]] . "'";
                                }
                                if ($key < (sizeof($matching_table_keys[$matching_table_index]) - 1)) {
                                    $query .= " AND ";
                                }
                            }
                        }
                        $query .= ';';
                        if ($display == true) {
                            echo "<p>" . $query . "</p>";
                        }
                        PMA_DBI_try_query($query, $trg_link, 0);
                    }
                }
            }
        }
    }
}

/**
 * PMA_insertIntoTargetTable() inserts missing rows in the target table using $array_insert[$matching_table_index]
 *
 * @todo this function uses undefined variables and is possibly broken: $matching_tables,
 *       $matching_tables_fields, $remove_indexes_array, $matching_table_keys
 *
 * @param array  $matching_table          matching table names
 * @param string $src_db                  name of source database
 * @param string $trg_db                  name of target database
 * @param mixed  $src_link                connection established with source server
 * @param mixed  $trg_link                connection established with target server
 * @param array  $table_fields            field names of a table
 * @param array  &$array_insert
 * @param int    $matching_table_index    index of matching table in matching_table_array
 * @param array  $matching_tables_keys    field names that are keys in the matching table
 * @param array  $source_columns          source column information
 * @param array  &$add_column_array       column names that are to be added in target table
 * @param array  $criteria                criteria like type, null, collation, default etc
 * @param array  $target_tables_keys      field names that are keys in the target table
 * @param array  $uncommon_tables         table names that are present in source db but not in targt db
 * @param array  &$uncommon_tables_fields field names of the uncommon tables
 * @param array  $uncommon_cols           column names that are present in target table and not in source table
 * @param array  &$alter_str_array        column names that are to be altered
 * @param array  &$source_indexes         column names on which indexes are made in source table
 * @param array  &$target_indexes         column names on which indexes are made in target table
 * @param array  &$add_indexes_array      column names on which index is to be added in target table
 * @param array  &$alter_indexes_array    column names whose indexes are to be altered. Only index name and uniqueness of an index can be changed
 * @param array  &$delete_array           rows that are to be deleted
 * @param array  &$update_array           rows that are to be updated in target
 * @param bool   $display
 */
function PMA_insertIntoTargetTable($matching_table, $src_db, $trg_db, $src_link, $trg_link, $table_fields, &$array_insert, $matching_table_index,
 $matching_tables_keys, $source_columns, &$add_column_array, $criteria, $target_tables_keys, $uncommon_tables, &$uncommon_tables_fields, $uncommon_cols,
 &$alter_str_array, &$source_indexes, &$target_indexes, &$add_indexes_array, &$alter_indexes_array, &$delete_array, &$update_array, $display)
{
    if (isset($array_insert[$matching_table_index])) {
        if (sizeof($array_insert[$matching_table_index])) {
            for ($insert_row = 0; $insert_row< sizeof($array_insert[$matching_table_index]); $insert_row++) {
                if (isset($array_insert[$matching_table_index][$insert_row][$matching_tables_keys[$matching_table_index][0]])) {

                    $select_query = "SELECT * FROM " . PMA_backquote($src_db) . "." . PMA_backquote($matching_table[$matching_table_index]) . " WHERE ";
                    for ($i = 0; $i < sizeof($matching_tables_keys[$matching_table_index]); $i++) {
                        $select_query .= $matching_tables_keys[$matching_table_index][$i] . "='";
                        $select_query .= $array_insert[$matching_table_index][$insert_row][$matching_tables_keys[$matching_table_index][$i]] . "'" ;

                        if ($i < (sizeof($matching_tables_keys[$matching_table_index]) - 1)) {
                            $select_query.= " AND ";
                        }
                    }
                    $select_query .= "; ";
                    $result = PMA_DBI_fetch_result($select_query, null, null, $src_link);
                    $insert_query = "INSERT INTO " . PMA_backquote($trg_db) . "." . PMA_backquote($matching_table[$matching_table_index]) ." (";

                    for ($field_index = 0; $field_index < sizeof($table_fields[$matching_table_index]); $field_index++) {
                        $insert_query .=  PMA_backquote($table_fields[$matching_table_index][$field_index]);

                        $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $trg_db ."'
                                         AND TABLE_NAME = '" . $matching_table[$matching_table_index]. "'AND COLUMN_NAME = '" .
                                         $table_fields[$matching_table_index][$field_index] . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;" ;

                        $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $trg_link);
                        if (sizeof($is_fk_result) > 0) {
                            for ($j = 0; $j < sizeof($is_fk_result); $j++) {
                                $table_index = array_keys($matching_table, $is_fk_result[$j]['REFERENCED_TABLE_NAME']);

                                if (isset($alter_str_array[$table_index[0]])) {
                                    PMA_alterTargetTableStructure(
                                        $trg_db, $trg_link, $matching_tables, $source_columns, $alter_str_array, $matching_tables_fields,
                                        $criteria, $matching_tables_keys, $target_tables_keys, $table_index[0], $display
                                    );
                                    unset($alter_str_array[$table_index[0]]);
                                }
                                if (isset($uncommon_columns[$table_index[0]])) {
                                    PMA_removeColumnsFromTargetTable($trg_db, $trg_link, $matching_tables, $uncommon_columns, $table_index[0], $display);
                                    unset($uncommon_columns[$table_index[0]]);
                                }
                                if (isset($add_column_array[$table_index[0]])) {
                                    PMA_findDeleteRowsFromTargetTables(
                                        $delete_array, $matching_tables, $table_index[0], $target_tables_keys,
                                        $matching_tables_keys, $trg_db, $trg_link, $src_db, $src_link
                                    );

                                    if (isset($delete_array[$table_index[0]])) {
                                        PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $table_index[0], $target_tables_keys, $delete_array, $display);
                                        unset($delete_array[$table_index[0]]);
                                    }
                                    PMA_addColumnsInTargetTable(
                                        $src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns, $add_column_array,
                                        $matching_tables_fields, $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables,
                                        $uncommon_tables_fields, $table_index[0], $uncommon_cols, $display
                                    );
                                    unset($add_column_array[$table_index[0]]);
                                }
                                if (isset($add_indexes_array[$table_index[0]])
                                    || isset($remove_indexes_array[$table_index[0]])
                                    || isset($alter_indexes_array[$table_index[0]])
                                ) {
                                    PMA_applyIndexesDiff(
                                        $trg_db, $trg_link, $matching_tables, $source_indexes, $target_indexes, $add_indexes_array,
                                        $alter_indexes_array, $remove_indexes_array, $table_index[0], $display
                                    );

                                    unset($add_indexes_array[$table_index[0]]);
                                    unset($alter_indexes_array[$table_index[0]]);
                                    unset($remove_indexes_array[$table_index[0]]);
                                }
                                if (isset($update_array[$table_index[0]])) {
                                    PMA_updateTargetTables(
                                        $matching_tables, $update_array, $src_db, $trg_db, $trg_link,
                                        $table_index[0], $matching_table_keys, $display
                                    );
                                    unset($update_array[$table_index[0]]);
                                }
                                if (isset($array_insert[$table_index[0]])) {
                                     PMA_insertIntoTargetTable(
                                         $matching_table, $src_db, $trg_db, $src_link, $trg_link, $table_fields, $array_insert, $table_index[0],
                                         $matching_tables_keys, $source_columns, $add_column_array, $criteria, $target_tables_keys, $uncommon_tables,
                                         $uncommon_tables_fields, $uncommon_cols, $alter_str_array, $source_indexes, $target_indexes, $add_indexes_array,
                                         $alter_indexes_array, $delete_array, $update_array, $display
                                     );
                                     unset($array_insert[$table_index[0]]);
                                }
                            }
                        }
                        if ($field_index < sizeof($table_fields[$matching_table_index])-1) {
                            $insert_query .= ", ";
                        }
                    }
                    $insert_query .= ") VALUES(";
                    if (sizeof($table_fields[$matching_table_index]) == 1) {
                         $insert_query .= "'" . PMA_sqlAddSlashes($result[0]) . "'";
                    } else {
                        for ($field_index = 0; $field_index < sizeof($table_fields[$matching_table_index]); $field_index++) {
                            if (isset($result[0][$table_fields[$matching_table_index][$field_index]])) {
                                $insert_query .= "'" . PMA_sqlAddSlashes($result[0][$table_fields[$matching_table_index][$field_index]]) . "'";
                            } else {
                                $insert_query .= "'NULL'";
                            }
                            if ($field_index < (sizeof($table_fields[$matching_table_index])) - 1) {
                                    $insert_query .= " ," ;
                            }
                        }
                    }
                    $insert_query .= ");";
                    if ($display == true) {
                        PMA_displayQuery($insert_query);
                    }
                    PMA_DBI_try_query($insert_query, $trg_link, 0);
                }
            }
        }
    }
}

/**
 * PMA_createTargetTables() Create the missing table $uncommon_table in target database
 *
 * @param string $src_db                  name of source database
 * @param string $trg_db                  name of target database
 * @param mixed  $src_link                connection established with source server
 * @param mixed  $trg_link                connection established with target server
 * @param array  &$uncommon_tables        names of tables present in source but not in target
 * @param int    $table_index             index of table in $uncommon_tables array
 * @param array  &$uncommon_tables_fields field names of the uncommon table
 * @param bool   $display
 */
function PMA_createTargetTables($src_db, $trg_db, $src_link, $trg_link, &$uncommon_tables, $table_index, &$uncommon_tables_fields, $display)
{
    if (isset($uncommon_tables[$table_index])) {
        $fields_result = PMA_DBI_get_columns($src_db, $uncommon_tables[$table_index], null, true, $src_link);
        $fields = array();
        foreach ($fields_result as $each_field) {
            $field_name = $each_field['Field'];
            $fields[] = $field_name;
        }
        $uncommon_tables_fields[$table_index] = $fields;

        $Create_Query = PMA_DBI_fetch_value("SHOW CREATE TABLE " . PMA_backquote($src_db) . '.' . PMA_backquote($uncommon_tables[$table_index]), 0, 1, $src_link);

        // Replace the src table name with a `dbname`.`tablename`
        $Create_Table_Query = preg_replace('/' . preg_quote(PMA_backquote($uncommon_tables[$table_index]), '/') . '/',
                                            PMA_backquote($trg_db) . '.' .PMA_backquote($uncommon_tables[$table_index]),
                                            $Create_Query,
                                            $limit = 1
        );

        $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $src_db . "'
                        AND TABLE_NAME = '" . $uncommon_tables[$table_index] . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;" ;

        $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $src_link);
        if (sizeof($is_fk_result) > 0) {
            for ($j = 0; $j < sizeof($is_fk_result); $j++) {
                if (in_array($is_fk_result[$j]['REFERENCED_TABLE_NAME'], $uncommon_tables)) {
                    $table_index = array_keys($uncommon_tables, $is_fk_result[$j]['REFERENCED_TABLE_NAME']);
                    PMA_createTargetTables($src_db, $trg_db, $trg_link, $src_link, $uncommon_tables, $table_index[0], $uncommon_tables_fields, $display);
                    unset($uncommon_tables[$table_index[0]]);
                }
            }
        }
        $Create_Table_Query .= ';';
        if ($display == true) {
            echo '<p>' . $Create_Table_Query . '</p>';
        }
        PMA_DBI_try_query($Create_Table_Query, $trg_link, 0);
    }
}
/**
 * PMA_populateTargetTables() inserts data into uncommon tables after they have been created
 *
 * @param string $src_db                 name of source database
 * @param string $trg_db                 name of target database
 * @param mixed  $src_link               connection established with source server
 * @param mixed  $trg_link               connection established with target server
 * @param array  $uncommon_tables        uncommon table names (table names that are present in source but not in target db)
 * @param int    $table_index            index of table in matching_table_array
 * @param array  $uncommon_tables_fields field names of the uncommon table
 * @param bool   $display
 *
 * @todo This turns NULL values into '' (empty string)
 */
function PMA_populateTargetTables($src_db, $trg_db, $src_link, $trg_link, $uncommon_tables, $table_index, $uncommon_tables_fields, $display)
{
    $display = false; // todo: maybe display some of the queries if they are not too numerous
    $unbuffered_result = PMA_DBI_try_query('SELECT * FROM ' . PMA_backquote($src_db) . '.' . PMA_backquote($uncommon_tables[$table_index]), $src_link, PMA_DBI_QUERY_UNBUFFERED);
    if (false !== $unbuffered_result) {
        $insert_query = 'INSERT INTO ' . PMA_backquote($trg_db) . '.' .PMA_backquote($uncommon_tables[$table_index]) . ' VALUES';
        while ($one_row = PMA_DBI_fetch_row($unbuffered_result)) {
            $insert_query .= '(';
            $key_of_last_value = count($one_row) - 1;
            foreach ($one_row as $key => $value) {
                $insert_query .= "'" . PMA_sqlAddSlashes($value) . "'";
                if ($key < $key_of_last_value) {
                    $insert_query .= ",";
                }
            }
            $insert_query .= '),';
        }
        $insert_query = substr($insert_query, 0, -1);
        $insert_query .= ';';
        if ($display == true) {
            PMA_displayQuery($insert_query);
        }
        PMA_DBI_try_query($insert_query, $trg_link, 0);
    }
}

/**
 * PMA_deleteFromTargetTable() delete rows from target table
 *
 * @param string $trg_db             name of target database
 * @param mixed  $trg_link           connection established with target server
 * @param array  $matching_tables    matching table names
 * @param int    $table_index        index of table in matching_table_array
 * @param array  $target_tables_keys primary key names of the target tables
 * @param array  $delete_array       key values of rows that are to be deleted
 * @param bool   $display
 */
function PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $table_index, $target_tables_keys, $delete_array, $display)
{
    for ($i = 0; $i < sizeof($delete_array[$table_index]); $i++) {
        if (isset($target_tables_keys[$table_index])) {
            $delete_query = 'DELETE FROM ' . PMA_backquote($trg_db) . '.' .PMA_backquote($matching_tables[$table_index]) . ' WHERE ';
            for ($y = 0; $y < sizeof($target_tables_keys[$table_index]); $y++) {
                $delete_query .= PMA_backquote($target_tables_keys[$table_index][$y]) . " = '";

                if (sizeof($target_tables_keys[$table_index]) == 1) {
                    $delete_query .= $delete_array[$table_index][$i] . "'";
                } elseif (sizeof($target_tables_keys[$table_index]) > 1) {
                    $delete_query .= $delete_array[$table_index][$i][$target_tables_keys[$table_index][$y]] . "'";
                }
                if ($y < (sizeof($target_tables_keys[$table_index]) - 1)) {
                    $delete_query .= ' AND ';
                }
                $pk_query = "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . $trg_db . "'
                            AND REFERENCED_TABLE_NAME = '" . $matching_tables[$table_index]."' AND REFERENCED_COLUMN_NAME = '"
                           . $target_tables_keys[$table_index][$y] . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;";

                $pk_query_result = PMA_DBI_fetch_result($pk_query, null, null, $trg_link);
                $result_size = sizeof($pk_query_result);

                if ($result_size > 0) {
                    for ($b = 0; $b < $result_size; $b++) {
                        $drop_pk_query = "DELETE FROM " . PMA_backquote($pk_query_result[$b]['TABLE_SCHEMA']) . "." . PMA_backquote($pk_query_result[$b]['TABLE_NAME']) . " WHERE " . PMA_backquote($pk_query_result[$b]['COLUMN_NAME']) . " = " . $target_tables_keys[$table_index][$y] . ";";
                        PMA_DBI_try_query($drop_pk_query, $trg_link, 0);
                    }
                }
            }
        }
        if ($display == true) {
            echo '<p>' . $delete_query . '</p>';
        }
        PMA_DBI_try_query($delete_query, $trg_link, 0);
    }
}

/**
 * PMA_structureDiffInTables() Gets all the column information for source and target table.
 * Compare columns on their names.
 * If column exists in target then compare Type, Null, Collation, Key, Default and Comment for that column.
 * If column does not exist in target table then it is placed in  $add_column_array.
 * If column exists in target table but criteria is different then it is palced in $alter_str_array.
 * If column does not exist in source table but is present in target table then it is placed in  $uncommon_columns.
 * Keys for all the source tables that have a corresponding target table are placed  in $matching_tables_keys.
 * Keys for all the target tables that have a corresponding source table are placed  in $target_tables_keys.
 *
 * @param string $src_db                 name of source database
 * @param string $trg_db                 name of target database
 * @param mixed  $src_link               connection established with source server
 * @param mixed  $trg_link               connection established with target server
 * @param array  $matching_tables        names of matching tables
 * @param array  &$source_columns        columns information of the source tables
 * @param array  &$target_columns        columns information of the target tables
 * @param array  &$alter_str_array       three dimensional associative array first index being the matching table index, second index being column name for which target
 *                                       column have some criteria different and third index containing the criteria which is different.
 * @param array  &$add_column_array      two dimensional associative array, first index of the array contain the matching table number and second index contain the
 *                                       column name which is to be added in the target table
 * @param array  &$uncommon_columns      columns that are present in the target table but not in the source table
 * @param array  $criteria               criteria which are to be checked for field that is present in source table and target table
 * @param array  &$target_tables_keys    field names which is key in the target table
 * @param int    $matching_table_index   number of the matching table
 */
function PMA_structureDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, &$source_columns, &$target_columns, &$alter_str_array,
 &$add_column_array, &$uncommon_columns, $criteria, &$target_tables_keys, $matching_table_index)
{
    //Gets column information for source and target table
    $source_columns[$matching_table_index] = PMA_DBI_get_columns_full($src_db, $matching_tables[$matching_table_index], null, $src_link);
    $target_columns[$matching_table_index] = PMA_DBI_get_columns_full($trg_db, $matching_tables[$matching_table_index], null, $trg_link);
    foreach ($source_columns[$matching_table_index] as $column_name => $each_column) {
        if (isset($target_columns[$matching_table_index][$column_name]['Field'])) {
            //If column exists in target table then matches criteria like type, null, collation, key, default, comment of the column
            for ($i = 0; $i < sizeof($criteria); $i++) {
                if ($source_columns[$matching_table_index][$column_name][$criteria[$i]] != $target_columns[$matching_table_index][$column_name][$criteria[$i]]) {
                    if (($criteria[$i] == 'Default') && ($source_columns[$matching_table_index][$column_name][$criteria[$i]] == '' )) {
                        $alter_str_array[$matching_table_index][$column_name][$criteria[$i]] = 'None';
                    } else {
                        if (! (($criteria[$i] == 'Key') && (($source_columns[$matching_table_index][$column_name][$criteria[$i]] == 'MUL')
                            || ($target_columns[$matching_table_index][$column_name][$criteria[$i]] == 'MUL')
                            || ($source_columns[$matching_table_index][$column_name][$criteria[$i]] == 'UNI')
                            || ($target_columns[$matching_table_index][$column_name][$criteria[$i]] == 'UNI')))
                        ) {
                            $alter_str_array[$matching_table_index][$column_name][$criteria[$i]] = $source_columns[$matching_table_index][$column_name][$criteria[$i]];
                        }
                    }
                }
            }
        } else {
            $add_column_array[$matching_table_index][$column_name]= $column_name;
        }
    }
    //Finds column names that are present in target table but not in source table
    foreach ($target_columns[$matching_table_index] as $fld_name => $each_column) {
        if (! (isset($source_columns[$matching_table_index][$fld_name]['Field']))) {
            $fields_uncommon[] = $fld_name;
        }
        if ($target_columns[$matching_table_index][$fld_name]['Key'] == 'PRI') {
            $keys[] = $fld_name;
        }
    }
    if (isset($fields_uncommon)) {
        $uncommon_columns[$matching_table_index] = $fields_uncommon;
    }
    if (isset($keys)) {
        $target_tables_keys[$matching_table_index] = $keys;
    }
}
/**
 * PMA_addColumnsInTargetTable() adds column that are present in source table but not in target table
 *
 * @param string $src_db                  name of source database
 * @param string $trg_db                  name of target database
 * @param mixed  $src_link                connection established with source server
 * @param mixed  $trg_link                connection established with target server
 * @param array  $matching_tables         names of matching tables
 * @param array  $source_columns          columns information of the source tables
 * @param array  &$add_column_array       the names of the column(field) that are to be added in the target
 * @param array  $matching_tables_fields
 * @param array  $criteria                criteria
 * @param array  $matching_tables_keys    field names which is key in the source table
 * @param array  $target_tables_keys      field names which is key in the target table
 * @param array  $uncommon_tables         table names that are present in source db and not in target db
 * @param array  &$uncommon_tables_fields names of the fields of the uncommon tables
 * @param int    $table_counter           number of the matching table
 * @param array  $uncommon_cols
 * @param bool   $display
 */
function PMA_addColumnsInTargetTable($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns, &$add_column_array, $matching_tables_fields,
         $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables, &$uncommon_tables_fields, $table_counter, $uncommon_cols, $display)
{
    for ($i = 0; $i < sizeof($matching_tables_fields[$table_counter]); $i++) {
        if (isset($add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]])) {
            $query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$table_counter]). " ADD COLUMN " .
            PMA_backquote($add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]]) . " " . $source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Type'];

            if ($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Null'] == 'NO') {
                $query .= ' Not Null ';
            } elseif ($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Null'] == 'YES') {
                $query .= ' Null ';
            }
            if ($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Collation'] != '') {
                $query .= ' COLLATE ' . $source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Collation'];
            }
            if ($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Default'] != '') {
                $query .= " DEFAULT " . $source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Default'];
            }
            if ($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Comment'] != '') {
                $query .= " COMMENT " . $source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Comment'];
            }
            if ($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Key'] == 'PRI' ) {
                $trg_key_size = sizeof($target_tables_keys[$table_counter]);
                if ($trg_key_size) {
                    $check = true;
                    for ($a = 0; ($a < $trg_key_size) && ($check); $a++) {
                        if (! (in_array($target_tables_keys[$table_counter], $uncommon_cols))) {
                             $check = false;
                        }
                    }
                    if (! $check) {
                        $query .= " ,DROP PRIMARY KEY " ;
                    }
                }
                $query .= " , ADD PRIMARY KEY (";
                for ($t = 0; $t < sizeof($matching_tables_keys[$table_counter]); $t++) {
                    $query .= PMA_backquote($matching_tables_keys[$table_counter][$t]);
                    if ($t < (sizeof($matching_tables_keys[$table_counter]) - 1)) {
                        $query .= " , " ;
                    }
                }
                $query .= ")";
            }

            $query .= ";";
            if ($display == true) {
                echo '<p>' . $query . '</p>';
            }
            PMA_DBI_try_query($query, $trg_link, 0);

            //Checks if column to be added is a foreign key or not
            $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $trg_db . "' AND TABLE_NAME = '"
            . $matching_tables[$table_counter] . "' AND COLUMN_NAME ='" . $add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]] .
            "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;";

            $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $src_link);

            //If column is a foreign key then it is checked that referenced table exist in target db. If referenced table does not exist in target db then
            //it is created first.
            if (isset($is_fk_result)) {
                if (in_array($is_fk_result[0]['REFERENCED_TABLE_NAME'], $uncommon_tables)) {
                    $table_index = array_keys($uncommon_tables, $is_fk_result[0]['REFERENCED_TABLE_NAME']);
                    PMA_checkForeignKeys($src_db, $src_link, $trg_db, $trg_link, $is_fk_result[0]['REFERENCED_TABLE_NAME'], $uncommon_tables, $uncommon_tables_fields, $display);
                    PMA_createTargetTables($src_db, $trg_db, $trg_link, $src_link, $uncommon_tables, $table_index[0], $uncommon_tables_fields, $display);
                    unset($uncommon_tables[$table_index[0]]);
                }
                $fk_query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$table_counter]) .
                            "ADD CONSTRAINT FOREIGN KEY " . PMA_backquote($add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]]) . "
                            (" . $add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]] . ") REFERENCES " . PMA_backquote($trg_db) .
                             '.' . PMA_backquote($is_fk_result[0]['REFERENCED_TABLE_NAME']) . " (" . $is_fk_result[0]['REFERENCED_COLUMN_NAME'] . ");";

                PMA_DBI_try_query($fk_query, $trg_link, null);
            }
        }
    }
}
/**
 * PMA_checkForeignKeys() checks if the referenced table have foreign keys.
 * uses    PMA_createTargetTables()
 *
 * @param string $src_db                  name of source database
 * @param mixed  $src_link                connection established with source server
 * @param string $trg_db                  name of target database
 * @param mixed  $trg_link                connection established with target server
 * @param string $referenced_table        table whose column is a foreign key in another table
 * @param array  &$uncommon_tables        names that are uncommon
 * @param array  &$uncommon_tables_fields field names of the uncommon table
 * @param bool   $display
 */
function PMA_checkForeignKeys($src_db, $src_link, $trg_db, $trg_link, $referenced_table, &$uncommon_tables, &$uncommon_tables_fields, $display)
{
    $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $src_db . "'
                    AND TABLE_NAME = '" . $referenced_table . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;";

    $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $src_link);
    if (sizeof($is_fk_result) > 0) {
        for ($j = 0; $j < sizeof($is_fk_result); $j++) {
            if (in_array($is_fk_result[$j]['REFERENCED_TABLE_NAME'], $uncommon_tables)) {
                $table_index = array_keys($uncommon_tables, $is_fk_result[$j]['REFERENCED_TABLE_NAME']);
                PMA_checkForeignKeys(
                    $src_db, $src_link, $trg_db, $trg_link, $is_fk_result[$j]['REFERENCED_TABLE_NAME'],
                    $uncommon_tables, $uncommon_tables_fields, $display
                );
                PMA_createTargetTables($src_db, $trg_db, $trg_link, $src_link, $uncommon_tables, $table_index[0], $uncommon_tables_fields, $display);
                unset($uncommon_tables[$table_index[0]]);
            }
        }
    }
}
/**
 * PMA_alterTargetTableStructure() alters structure of the target table using $alter_str_array
 *
 * @param string $trg_db                 name of target database
 * @param mixed  $trg_link               connection established with target server
 * @param array  $matching_tables        names of matching tables
 * @param array  &$source_columns        columns information of the source table
 * @param array  &$alter_str_array       column name and criteria which is to be altered for the targert table
 * @param array  $matching_tables_fields name of the fields for the matching table
 * @param array  $criteria               criteria
 * @param array  &$matching_tables_keys  field names which is key in the source table
 * @param array  &$target_tables_keys    field names which is key in the target table
 * @param int    $matching_table_index   number of the matching table
 * @param bool   $display
 */
function PMA_alterTargetTableStructure($trg_db, $trg_link, $matching_tables, &$source_columns, &$alter_str_array, $matching_tables_fields, $criteria,
 &$matching_tables_keys, &$target_tables_keys, $matching_table_index, $display)
{
    $check = true;
    $sql_query = '';
    $found = false;

    //Checks if the criteria to be altered is primary key
    for ($v = 0; $v < sizeof($matching_tables_fields[$matching_table_index]); $v++) {
        if (isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$v]]['Key'])) {
            if ($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$v]]['Key'] == 'PRI' ) {
                $check = false;
            }
        }
    }

    $pri_query = null;
    if (! $check) {
        $pri_query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$matching_table_index]);
        if (sizeof($target_tables_keys[$matching_table_index]) > 0) {
            $pri_query .= "  DROP PRIMARY KEY ," ;
        }
        $pri_query .= "  ADD PRIMARY KEY (";
        for ($z = 0; $z < sizeof($matching_tables_keys[$matching_table_index]); $z++) {
            $pri_query .= PMA_backquote($matching_tables_keys[$matching_table_index][$z]);
            if ($z < (sizeof($matching_tables_keys[$matching_table_index]) - 1)) {
                $pri_query .= " , " ;
            }
        }
        $pri_query .= ");";
    }

    if (isset($pri_query)) {
        if ($display == true) {
            echo '<p>' . $pri_query . '</p>';
        }
        PMA_DBI_try_query($pri_query, $trg_link, 0);
    }
    for ($t = 0; $t < sizeof($matching_tables_fields[$matching_table_index]); $t++) {
        if ((isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]])) && (sizeof($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]) > 0)) {
            $sql_query = 'ALTER TABLE ' . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$matching_table_index]) . ' MODIFY ' .
            PMA_backquote($matching_tables_fields[$matching_table_index][$t]) . ' ' . $source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]['Type'];
            $found = false;
            for ($i = 0; $i < sizeof($criteria); $i++) {
                if (isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]]) && $criteria[$i] != 'Key') {
                    $found = true;
                    if (($criteria[$i] == 'Type') && (! isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i+1]]))) {
                        if ($source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i + 1]] == 'NO') {
                            $sql_query .= " Not Null" ;
                        } elseif ($source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i + 1]] == 'YES') {
                            $sql_query .= " Null" ;
                        }
                    }
                    if (($criteria[$i] == 'Null') && ( $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] == 'NO')) {
                        $sql_query .= " Not Null "  ;
                    } elseif (($criteria[$i] == 'Null') && ($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] == 'YES')) {
                        $sql_query .= " Null "  ;
                    }
                    if ($criteria[$i] == 'Collation') {
                        if ( !(isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        }
                        $sql_query .=  " COLLATE " . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] ;
                    }
                    if (($criteria[$i] == 'Default') && ($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] == 'None')) {
                        if ( !(isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        }
                    } elseif ($criteria[$i] == 'Default') {
                        if (! (isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        }
                        if (is_string($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]])) {
                            if ($source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]['Type'] != 'timestamp') {
                                $sql_query .=  " DEFAULT '" . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] . "'";
                            } elseif ($source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]['Type'] == 'timestamp') {
                                $sql_query .=  " DEFAULT " . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]];
                            }
                        } elseif (is_numeric($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]])) {
                            $sql_query .=  " DEFAULT " . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]];
                        }
                    }
                    if ($criteria[$i] == 'Comment') {
                        if ( !(isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        }
                        $sql_query .=  " COMMENT '" . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] . "'" ;
                    }
                }
            }
        }
        $sql_query .= ";";
        if ($found) {
            if ($display == true) {
                echo '<p>' . $sql_query . '</p>';
            }
            PMA_DBI_try_query($sql_query, $trg_link, 0);
        }
    }
    $check = false;
    $query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$matching_table_index]);
    for ($p = 0; $p < sizeof($matching_tables_keys[$matching_table_index]); $p++) {
        if ((isset($alter_str_array[$matching_table_index][$matching_tables_keys[$matching_table_index][$p]]['Key']))) {
            $check = true;
            $query .= ' MODIFY ' . PMA_backquote($matching_tables_keys[$matching_table_index][$p]) . ' '
            . $source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$p]]['Type'] . ' Not Null ';
            if ($p < (sizeof($matching_tables_keys[$matching_table_index]) - 1)) {
                $query .= ', ';
            }
        }
    }
    $query .= ';';
    if ($check) {
        if ($display == true) {
            echo '<p>' . $query . '</p>';
        }
        PMA_DBI_try_query($query, $trg_link, 0);
    }
}

/**
 * PMA_removeColumnsFromTargetTable() removes the columns which are present in target table but not in source table.
 *
 * @param string $trg_db           name of target database
 * @param mixed  $trg_link         connection established with target server
 * @param array  $matching_tables  names of matching tables
 * @param array  $uncommon_columns array containing the names of the column which are to be dropped from the target table
 * @param int    $table_counter    index of the matching table as in $matchiing_tables array
 * @param bool   $display
 */
function PMA_removeColumnsFromTargetTable($trg_db, $trg_link, $matching_tables, $uncommon_columns, $table_counter, $display)
{
    if (isset($uncommon_columns[$table_counter])) {
        $drop_query = "ALTER TABLE " . PMA_backquote($trg_db) . "." . PMA_backquote($matching_tables[$table_counter]);
        for ($a = 0; $a < sizeof($uncommon_columns[$table_counter]); $a++) {
            //Checks if column to be removed is a foreign key in any table
            $pk_query = "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . $trg_db . "'
                         AND REFERENCED_TABLE_NAME = '" . $matching_tables[$table_counter]."' AND REFERENCED_COLUMN_NAME = '"
                         . $uncommon_columns[$table_counter][$a] . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;";

            $pk_query_result = PMA_DBI_fetch_result($pk_query, null, null, $trg_link);
            $result_size = sizeof($pk_query_result);

            if ($result_size > 0) {
                for ($b = 0; $b < $result_size; $b++) {
                    $drop_pk_query = "ALTER TABLE " . PMA_backquote($pk_query_result[$b]['TABLE_SCHEMA']) . "." . PMA_backquote($pk_query_result[$b]['TABLE_NAME']) . "
                                      DROP FOREIGN KEY " . PMA_backquote($pk_query_result[$b]['CONSTRAINT_NAME']) . ", DROP COLUMN " . PMA_backquote($pk_query_result[$b]['COLUMN_NAME']) . ";";
                    PMA_DBI_try_query($drop_pk_query, $trg_link, 0);
                }
            }
            $query = "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $trg_db . "' AND TABLE_NAME = '"
                     . $matching_tables[$table_counter]. "' AND COLUMN_NAME = '" . $uncommon_columns[$table_counter][$a] . "'
                      AND TABLE_NAME <> REFERENCED_TABLE_NAME;";

            $result = PMA_DBI_fetch_result($query, null, null, $trg_link);

            if (sizeof($result) > 0) {
                $drop_query .= " DROP FOREIGN KEY " . PMA_backquote($result[0]['CONSTRAINT_NAME']) . ",";
            }
            $drop_query .=  " DROP COLUMN " . PMA_backquote($uncommon_columns[$table_counter][$a]);
            if ($a < (sizeof($uncommon_columns[$table_counter]) - 1)) {
                $drop_query .= " , " ;
            }
        }
        $drop_query .= ";" ;

        if ($display == true) {
            echo '<p>' . $drop_query . '</p>';
        }
        PMA_DBI_try_query($drop_query, $trg_link, 0);
    }
}

/**
 * PMA_indexesDiffInTables() compares the source table indexes with target table indexes and keep the indexes to be added in target table in $add_indexes_array
 * indexes to be altered in $alter_indexes_array and indexes to be removed from target table in $remove_indexes_array.
 * Only keyname and uniqueness characteristic of the indexes are altered.
 *
 * @param string $src_db                name of source database
 * @param string $trg_db                name of target database
 * @param mixed  $src_link              connection established with source server
 * @param mixed  $trg_link              connection established with target server
 * @param array  $matching_tables       matching tables name
 * @param array  &$source_indexes       indexes of the source table
 * @param array  &$target_indexes       indexes of the target table
 * @param array  &$add_indexes_array    name of the column on which the index is to be added in the target table
 * @param array  &$alter_indexes_array  key name which needs to be altered
 * @param array  &$remove_indexes_array key name of the index which is to be removed from the target table
 * @param int    $table_counter         number of the matching table
 */
function PMA_indexesDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, &$source_indexes, &$target_indexes, &$add_indexes_array,
 &$alter_indexes_array, &$remove_indexes_array, $table_counter)
{
    //Gets indexes information for source and target table
    $source_indexes[$table_counter] = PMA_DBI_get_table_indexes($src_db, $matching_tables[$table_counter], $src_link);
    $target_indexes[$table_counter] = PMA_DBI_get_table_indexes($trg_db, $matching_tables[$table_counter], $trg_link);
    for ($a = 0; $a < sizeof($source_indexes[$table_counter]); $a++) {
        $found = false;
        $z = 0;
        //Compares key name and non_unique characteristic of source indexes with target indexes
        /*
         * @todo compare the length of each sub part
         */
        while (($z <= sizeof($target_indexes[$table_counter])) && ($found == false)) {
            if (isset($source_indexes[$table_counter][$a]) && isset($target_indexes[$table_counter][$z]) && $source_indexes[$table_counter][$a]['Key_name'] == $target_indexes[$table_counter][$z]['Key_name']) {
                $found = true;
                if (($source_indexes[$table_counter][$a]['Column_name'] != $target_indexes[$table_counter][$z]['Column_name']) || ($source_indexes[$table_counter][$a]['Non_unique'] != $target_indexes[$table_counter][$z]['Non_unique'])) {
                    if (! (($source_indexes[$table_counter][$a]['Key_name'] == "PRIMARY") || ($target_indexes[$table_counter][$z]['Key_name'] == 'PRIMARY'))) {
                        $alter_indexes_array[$table_counter][] = $source_indexes[$table_counter][$a]['Key_name'];
                    }
                }
            }
            $z++;
        }
        if ($found === false) {
            if (! ($source_indexes[$table_counter][$a]['Key_name'] == 'PRIMARY')) {
                $add_indexes_array [$table_counter][] = $source_indexes[$table_counter][$a]['Column_name'];
            }
        }
    }

    //Finds indexes that exist on target table but not on source table
    for ($b = 0; $b < sizeof($target_indexes[$table_counter]); $b++) {
        $found = false;
        $c = 0;
        while (($c <= sizeof($source_indexes[$table_counter])) && ($found == false)) {
            if ($target_indexes[$table_counter][$b]['Column_name'] == $source_indexes[$table_counter][$c]['Column_name']) {
                $found = true;
            }
            $c++;
        }
        if ($found === false) {
            $remove_indexes_array[$table_counter][] = $target_indexes[$table_counter][$b]['Key_name'];
        }
    }
}

/**
 * PMA_applyIndexesDiff() create indexes, alters indexes and remove indexes.
 *
 * @param string $trg_db               name of target database
 * @param mixed  $trg_link             connection established with target server
 * @param array  $matching_tables      matching tables name
 * @param array  $source_indexes       indexes of the source table
 * @param array  $target_indexes       indexes of the target table
 * @param array  $add_indexes_array    column names on which indexes are to be created in target table
 * @param array  $alter_indexes_array  column names for which indexes are to be altered
 * @param array  $remove_indexes_array key name of the indexes which are to be removed from the target table
 * @param int    $table_counter        number of the matching table
 * @param $display
 */
function PMA_applyIndexesDiff ($trg_db, $trg_link, $matching_tables, $source_indexes, $target_indexes, $add_indexes_array, $alter_indexes_array,
          $remove_indexes_array, $table_counter, $display)
{
    //Adds indexes on target table
    if (isset($add_indexes_array[$table_counter])) {
        $sql = "ALTER TABLE " . PMA_backquote($trg_db) . "." . PMA_backquote($matching_tables[$table_counter]) . " ADD" ;
        for ($a = 0; $a < sizeof($source_indexes[$table_counter]); $a++) {
            if (isset($add_indexes_array[$table_counter][$a])) {
                for ($b = 0; $b < sizeof($source_indexes[$table_counter]); $b++) {
                    if ($source_indexes[$table_counter][$b]['Column_name'] == $add_indexes_array[$table_counter][$a]) {
                        if ($source_indexes[$table_counter][$b]['Non_unique'] == '0') {
                            $sql .= " UNIQUE ";
                        }
                        $sql .= " INDEX " . PMA_backquote($source_indexes[$table_counter][$b]['Key_name']) . " (" . $add_indexes_array[$table_counter][$a] . " );";
                        if ($display == true) {
                            echo '<p>' . $sql . '</p>';
                        }
                        PMA_DBI_try_query($sql, $trg_link, 0);
                    }
                }
            }
        }
    }
    //Alter indexes of target table

    if (isset($alter_indexes_array[$table_counter])) {
        $query = "ALTER TABLE " . PMA_backquote($trg_db) . "." . PMA_backquote($matching_tables[$table_counter]);
        for ($a = 0; $a < sizeof($alter_indexes_array[$table_counter]); $a++) {
            if (isset($alter_indexes_array[$table_counter][$a])) {
                $query .= ' DROP INDEX ' . PMA_backquote($alter_indexes_array[$table_counter][$a]) . " , ADD ";
                $got_first_index_column = false;
                for ($z = 0; $z < sizeof($source_indexes[$table_counter]); $z++) {
                    if ($source_indexes[$table_counter][$z]['Key_name'] == $alter_indexes_array[$table_counter][$a]) {
                        if (! $got_first_index_column) {
                            if ($source_indexes[$table_counter][$z]['Non_unique'] == '0') {
                                $query .= " UNIQUE ";
                            }
                            $query .= " INDEX " . PMA_backquote($source_indexes[$table_counter][$z]['Key_name']) . " (" . PMA_backquote($source_indexes[$table_counter][$z]['Column_name']);
                            $got_first_index_column = true;
                        } else {
                            // another column for this index
                            $query .= ', ' . PMA_backquote($source_indexes[$table_counter][$z]['Column_name']);
                        }
                    }
                }
                $query .= " )";
            }
        }
        $query .= ';';
        if ($display == true) {
            echo '<p>' . $query . '</p>';
        }
        PMA_DBI_try_query($query, $trg_link, 0);
    }
    //Removes indexes from target table
    if (isset($remove_indexes_array[$table_counter])) {
        $drop_index_query = "ALTER TABLE " . PMA_backquote($trg_db) . "." . PMA_backquote($matching_tables[$table_counter]);
        for ($a = 0; $a < sizeof($target_indexes[$table_counter]); $a++) {
            if (isset($remove_indexes_array[$table_counter][$a])) {
                $drop_index_query .= " DROP INDEX " . PMA_backquote($remove_indexes_array[$table_counter][$a]);
            }
            if ($a < (sizeof($remove_indexes_array[$table_counter]) - 1)) {
                $drop_index_query .= " , " ;
            }
        }
        $drop_index_query .= " ; " ;
        if ($display == true) {
            echo '<p>' . $drop_index_query . '</p>';
        }
        PMA_DBI_try_query($drop_index_query, $trg_link, 0);
    }
}

/**
 * PMA_displayQuery() displays a query, taking the maximum display size
 * into account
 *
 * @param string $query the query to display
 *
 * @return nothing
 */
function PMA_displayQuery($query)
{
    if (strlen($query) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
        $query = substr($query, 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) . '[...]';
    }
    echo '<p>' . htmlspecialchars($query) . '</p>';
}

/**
 * PMA_syncDisplayHeaderCompare() shows the header for source database
 *
 * @param string $src_db source db name
 * @param string $trg_db target db name
 *
 * @return nothing
 */
function PMA_syncDisplayHeaderCompare($src_db, $trg_db)
{
    echo '<fieldset style="padding:0"><div style="padding:1.5em; overflow:auto; height:220px">';

    echo '<table class="data">';
    echo '<tr>';
    echo '<th>' . __('Source database') . ':  ' . htmlspecialchars($src_db) . '<br />(';
    if ('cur' == $_SESSION['src_type']) {
        echo __('Current server');
    } else {
        echo __('Remote server') . ' ' . htmlspecialchars($_SESSION['src_server']['host']);
    }
    echo ')</th>';
    echo '<th>' . __('Difference') . '</th>';
    echo '<th>' . __('Target database') . ':  '. htmlspecialchars($trg_db) . '<br />(';
    if ('cur' == $_SESSION['trg_type']) {
        echo __('Current server');
    } else {
        echo __('Remote server') . ' ' . htmlspecialchars($_SESSION['trg_server']['host']);
    }
    echo ')</th>';
    echo '</tr>';
}

/**
 * Prints table row
 *
 * $rows contains following keys:
 * - src_table_name - source server table name
 * - dst_table_name - target server table name
 * - btn_type - 'M' or 'U'
 * - btn_structure - null or arguments for showDetails in server_synchronize.js (without img_obj and table_name):
 *                       i, update_size, insert_size, remove_size, insert_index, remove_index
 *
 * @param array $rows
 */
function PMA_syncDisplayDataCompare($rows)
{
    global $pmaThemeImage;

    $odd_row = true;
    foreach ($rows as $row) {
        echo '<tr class=" ' . ($odd_row ? 'odd' : 'even') . '">';
        echo '<td>' . htmlspecialchars($row['src_table_name']) . '</td><td style="text-align:center">';
        if (isset($row['btn_structure']) && $row['btn_structure']) {
            // parameters: i, update_size, insert_size, remove_size, insert_index, remove_index
            $p = $row['btn_structure'];
            $p[0] = $row['btn_type'] . 'S' . $p[0];
            echo '<img class="icon struct_img" src="' . $pmaThemeImage . 'new_struct.png" width="16" height="16"
                 alt="Structure" title="' . __('Click to select') . '" style="cursor:pointer" onclick="showDetails('
                 . "'" . implode($p, "','") . "'"
                 . ', this, ' . "'" . PMA_escapeJsString(htmlspecialchars($row['src_table_name'])) . "'" . ')" /> ';
        }
        if (isset($row['btn_data']) && $row['btn_data']) {
            // parameters: i, update_size, insert_size, remove_size, insert_index, remove_index
            $p = $row['btn_data'];
            $p[0] = $row['btn_type'] . 'D' . $p[0];
            echo '<img class="icon data_img" src="' . $pmaThemeImage . 'new_data.png" width="16" height="16"
                alt="Data" title="' . __('Click to select') . '" style="cursor:pointer" onclick="showDetails('
                . "'" . implode($p, "','") . "'"
                . ', this, ' . "'" . PMA_escapeJsString(htmlspecialchars($row['src_table_name'])) . "'" . ')" />';
        }
        echo '</td><td>' . htmlspecialchars($row['dst_table_name']) . '</td></tr>';
        $odd_row = !$odd_row;
    }
}

/**
 * array PMA_get_column_values (string $database, string $table, string $column , mysql db link $link = null)
 *
 * @param string $database name of database
 * @param string $table    name of table to retrieve columns from
 * @param string $column   name of the column to retrieve data from
 * @param mixed  $link     mysql link resource
 *
 * @return array $field_values
 */
function PMA_get_column_values($database, $table, $column, $link = null)
{
    $query = 'SELECT ';
    for ($i=0; $i< sizeof($column); $i++) {
        $query.= PMA_backquote($column[$i]);
        if ($i < (sizeof($column)-1)) {
            $query.= ', ';
        }
    }
    $query.= ' FROM ' . PMA_backquote($database) . '.' . PMA_backquote($table);
    $field_values = PMA_DBI_fetch_result($query, null, null, $link);

    if (! is_array($field_values) || count($field_values) < 1) {
        return false;
    }
    return $field_values;
}
?>
