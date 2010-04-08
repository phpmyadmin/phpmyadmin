<?php 
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
* PMA_getMatchingTables places matching tables in source 
* and target databases in $matching_tables array whereas
* $uncommon_source_tables array gets the tables present in
* source database but are absent from target database.
* Criterion for matching tables is just comparing their names.
*           
* @param    $trg_tables   array of target database table names, 
* @param    $src_tables   array of source database table names, 
* 
* @param    &$matching_tables           empty array passed by reference to save names of matching tables, 
* @param    &$uncommon_source_tables    empty array passed by reference to save names of tables present in 
*                                       source database but absent from target database
*/
        
function PMA_getMatchingTables($trg_tables, $src_tables, &$matching_tables, &$uncommon_source_tables)
{
    for($k=0; $k< sizeof($src_tables); $k++) {                  
        $present_in_target = false;   
        for($l=0; $l < sizeof($trg_tables); $l++) {   
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
* PMA_getNonMatchingTargetTables() places tables present
* in target database but are absent from source database
* 
* @param    $trg_tables   array of target database table names, 
*  
* @param    $matching_tables           $matching tables array containing names of matching tables, 
* @param    &$uncommon_target_tables    empty array passed by reference to save names of tables presnet in 
*                                       target database but absent from source database
*/

function PMA_getNonMatchingTargetTables($trg_tables, $matching_tables, &$uncommon_target_tables)
{
    for($c=0; $c<sizeof($trg_tables) ;$c++) {
        $match = false;          
        for($d=0; $d < sizeof($matching_tables); $d++)
        {
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
 * PMA_dataDiffInTables() finds the difference in source and target matching tables by
 * first comparing source table's primary key entries with target table enteries.
 * It gets the field names for the matching table also for comparisons.
 * If the entry is found in target table also then it is checked for the remaining
 * field values also, in order to check whether update is required or not.
 * If update is required, it is placed in $update_array
 * Otherwise that entry is placed in the $insert_array.
 * 
 * @uses     PMA_DBI_get_fields()
 * @uses     PMA_DBI_get_column_values()
 * @uses     PMA_DBI_fetch_result()
 * 
 * @param    $src_db    name of source database
 * @param    $trg_db    name of target database
 * @param    $src_link  connection established with source server
 * @param    $trg_link  connection established with target server
 * @param    $index     Index of a table from $matching_table array 
 * 
 * @param    $update_array    A three dimensional array passed by reference to 
 *                            contain updates required for each matching table
 * @param    $insert_array    A three dimensional array passed by reference to 
 *                            contain inserts required for each matching table
 * @param    $fields_num      A two dimensional array passed by reference to 
 *                            contain number of fields for each matching table
 * @param    $matching_table   array containing matching table names
 * 
 * @param    $matching_tables_fields    A two dimensional array passed by reference to contain names of fields for each matching table
 * 
 * @param    $matching_tables_keys     A two dimensional array passed by reference to contain names of keys for each matching table
 */                                                  
function PMA_dataDiffInTables($src_db, $trg_db, $src_link, $trg_link, &$matching_table, &$matching_tables_fields,
    &$update_array, &$insert_array, &$delete_array, &$fields_num, $matching_table_index, &$matching_tables_keys)
{   
    if (isset($matching_table[$matching_table_index])) {
        $fld = array();
        $fld_results = PMA_DBI_get_fields($src_db, $matching_table[$matching_table_index], $src_link);
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
        
        $source_result_set = PMA_DBI_get_column_values($src_db, $matching_table[$matching_table_index], $is_key, $src_link);      
        $source_size = sizeof($source_result_set);
        
        $trg_fld_results = PMA_DBI_get_fields($trg_db, $matching_table[$matching_table_index], $trg_link);
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
        $update_field = 0;
        $insert_field = 0;
        $starting_index = 0; 
        
        for ($j = 0; $j < $source_size; $j++) { 
            $starting_index = 0;
			$update_field = 0; 

			if (isset($source_result_set[$j]) && ($all_keys_match)) {

				// Query the target server to see which rows already exist
                $trg_select_query = "SELECT * FROM " . PMA_backquote($trg_db) . "." 
        	        . PMA_backquote($matching_table[$matching_table_index]) . " WHERE ";
  
                if (sizeof($is_key) == 1) {
                    $trg_select_query .= $is_key[0]. "='" . $source_result_set[$j] . "'";
                } elseif (sizeof($is_key) > 1){
                    for ($k=0; $k < sizeof($is_key); $k++) {
                        $trg_select_query .= $is_key[$k] . "='" . $source_result_set[$j][$is_key[$k]] . "'";
                        if ($k < (sizeof($is_key)-1)){
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
                        $src_select_query .= $is_key[0] . "='" . $source_result_set[$j] . "'";
                    } else if(sizeof($is_key) > 1){
                        for ($k=0; $k< sizeof($is_key); $k++) {
                            $src_select_query .= $is_key[$k] . "='" . $source_result_set[$j][$is_key[$k]] . "'";
                            if ($k < (sizeof($is_key) - 1)){
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
                    for ($m = $starting_index + 1; $m < $fields_num[$matching_table_index] ; $m++)
                    {   
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
                        for($l = 0; $l < sizeof($is_key); $l++) {
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
* PMA_findDeleteRowsFromTargetTables finds the rows which are to be deleted from target table.
* @uses   sizeof()
* @uses   PMA_DBI_get_column_values()
* @uses   in_array()
* 
* @param  $delete_array          array containing rows that are to be deleted 
* @param  $matching_table        array containing matching table names
* @param  $matching_table_index  index of a table from $matching_table array
* @param  $trg_keys              array of target table keys
* @param  $src_keys              array of source table keys
* @param  $trg_db                name of target database
* @param  $trg_link              connection established with target server
* @param  $src_db                name of source database 
* @param  $src_link              connection established with source server
* 
*/
function PMA_findDeleteRowsFromTargetTables(&$delete_array, $matching_table, $matching_table_index, $trg_keys, $src_keys, $trg_db, $trg_link,$src_db, $src_link)
{
    if (isset($trg_keys[$matching_table_index])) {
        $target_key_values = PMA_DBI_get_column_values($trg_db, $matching_table[$matching_table_index], $trg_keys[$matching_table_index], $trg_link);      
        $target_row_size = sizeof($target_key_values);        
    }
    if (isset($src_keys[$matching_table_index])) {
        $source_key_values = PMA_DBI_get_column_values($src_db, $matching_table[$matching_table_index], $src_keys[$matching_table_index], $src_link);      
        $source_size = sizeof($source_key_values);        
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
* @uses   PMA_DBI_fetch_result()
* 
* @param  $source_tables_uncommon  array of table names; containing table names that are in source db and not in target db
* @param  $src_db                  name of source database
* @param  $src_link                connection established with source server
* @param  $index                   index of a table from $matching_table array
* @param  $row_count               number of rows
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
* @uses    PMA_DBI_fetch_result()
* @uses    PMA_backquote()
*  
* @param    $table                 Array containing matching tables' names 
* @param    $update_array          A three dimensional array containing field
*                                  value updates required for each matching table
* @param    $src_db                Name of source database 
* @param    $trg_db                Name of target database
* @param    $trg_link              Connection established with target server
* @param    $matching_table_index  index of matching table in matching_table_array    
* @param    $display               true/false value
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
                             $query .= $update_array[$matching_table_index][$update_row][$update_field] . "='" . $update_array[$matching_table_index][$update_row][$update_field+1] . "'";
                         }
                         if ($update_field < ($update_fields_num - 2)) {
                             $query .= ", ";    
                         }
                     }
                     $query .= " WHERE ";
                     if (isset($matching_table_keys[$matching_table_index])) {
                        for ($key = 0; $key < sizeof($matching_table_keys[$matching_table_index]); $key++)
                        {
                            if (isset($matching_table_keys[$matching_table_index][$key])) {
                            
                                $query .= $matching_table_keys[$matching_table_index][$key] . "='" . $update_array[$matching_table_index][$update_row][$matching_table_keys[$matching_table_index][$key]] . "'";
                            }
                            if ($key < (sizeof($matching_table_keys[$matching_table_index]) - 1)) {
                                 $query .= " AND ";
                            }
                        }
                    }
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
* @uses    PMA_DBI_fetch_result()
* @uses    PMA_backquote()
* 
*                                              
* @param  $matching_table         array containing matching table names
* @param  $src_db                 name of source database
* @param  $trg_db                 name of target database
* @param  $src_link               connection established with source server
* @param  $trg_link               connection established with target server
* @param  $table_fields           array containing field names of a table
* @param  $array_insert            
* @param  $matching_table_index   index of matching table in matching_table_array 
* @param  $matching_tables_keys   array containing field names that are keys in the matching table
* @param  $source_columns         array containing source column information
* @param  $add_column_array       array containing column names that are to be added in target table
* @param  $criteria               array containing criterias like type, null, collation, default etc
* @param  $target_tables_keys     array containing field names that are keys in the target table
* @param  $uncommon_tables        array containing table names that are present in source db but not in targt db
* @param  $uncommon_tables_fields array containing field names of the uncommon tables
* @param  $uncommon_cols          column names that are present in target table and not in source table
* @param  $alter_str_array        array containing column names that are to be altered 
* @param  $source_indexes         column names on which indexes are made in source table 
* @param  $target_indexes         column names on which indexes are made in target table 
* @param  $add_indexes_array      array containing column names on which index is to be added in target table
* @param  $alter_indexes_array    array containing column names whose indexes are to be altered. Only index name and uniqueness of an index can be changed 
* @param  $delete_array           array containing rows that are to be deleted
* @param  $update_array           array containing rows that are to be updated in target
* @param  $display                true/false value
*
*/
function PMA_insertIntoTargetTable($matching_table, $src_db, $trg_db, $src_link, $trg_link, $table_fields, &$array_insert, $matching_table_index,
 $matching_tables_keys, $source_columns, &$add_column_array, $criteria, $target_tables_keys, $uncommon_tables, &$uncommon_tables_fields,$uncommon_cols, 
 &$alter_str_array,&$source_indexes, &$target_indexes, &$add_indexes_array, &$alter_indexes_array, &$delete_array, &$update_array, $display)
{   
    if(isset($array_insert[$matching_table_index])) {
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
                    $result = PMA_DBI_fetch_result ($select_query, null, null, $src_link);
                    $insert_query = "INSERT INTO " . PMA_backquote($trg_db) . "." . PMA_backquote($matching_table[$matching_table_index]) ." (";
                    
                    for ($field_index = 0; $field_index < sizeof($table_fields[$matching_table_index]); $field_index++) 
                    {
                        $insert_query .=  $table_fields[$matching_table_index][$field_index];
                        
                        $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $trg_db ."' 
                                         AND TABLE_NAME = '" . $matching_table[$matching_table_index]. "'AND COLUMN_NAME = '" .
                                         $table_fields[$matching_table_index][$field_index] . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;" ;
                    
                        $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $trg_link);
                        if (sizeof($is_fk_result) > 0) {
                            for ($j = 0; $j < sizeof($is_fk_result); $j++)
                            {
                                $table_index = array_keys($matching_table, $is_fk_result[$j]['REFERENCED_TABLE_NAME']);
                                 
                                if (isset($alter_str_array[$table_index[0]])) {
                                   PMA_alterTargetTableStructure($trg_db, $trg_link, $matching_tables, $source_columns, $alter_str_array, $matching_tables_fields,
                                    $criteria, $matching_tables_keys, $target_tables_keys, $table_index[0], $display);
                                    unset($alter_str_array[$table_index[0]]);        
                                }                                                           
                                if (isset($uncommon_columns[$table_index[0]])) {
                                    PMA_removeColumnsFromTargetTable($trg_db, $trg_link, $matching_tables, $uncommon_columns, $table_index[0], $display);
                                    unset($uncommon_columns[$table_index[0]]); 
                                }           
                                if (isset($add_column_array[$table_index[0]])) {
                                    PMA_findDeleteRowsFromTargetTables($delete_array, $matching_tables, $table_index[0], $target_tables_keys, $matching_tables_keys,
                                    $trg_db, $trg_link, $src_db, $src_link);
                                     
                                    if (isset($delete_array[$table_index[0]])) {
                                       PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $table_index[0], $target_tables_keys, $delete_array, $display);
                                       unset($delete_array[$table_index[0]]); 
                                    }        
                                    PMA_addColumnsInTargetTable($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns, $add_column_array, 
                                    $matching_tables_fields, $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables,$uncommon_tables_fields,
                                    $table_index[0], $uncommon_cols, $display);
                                    unset($add_column_array[$table_index[0]]);
                                }
                                if (isset($add_indexes_array[$table_index[0]]) || isset($remove_indexes_array[$table_index[0]]) 
                                    || isset($alter_indexes_array[$table_index[0]])) {
                                    PMA_applyIndexesDiff ($trg_db, $trg_link, $matching_tables, $source_indexes, $target_indexes, $add_indexes_array, $alter_indexes_array, 
                                    $remove_indexes_array, $table_index[0], $display); 
                                   
                                    unset($add_indexes_array[$table_index[0]]);
                                    unset($alter_indexes_array[$table_index[0]]);
                                    unset($remove_indexes_array[$table_index[0]]);
                                }
                                if (isset($update_array[$table_index[0]])) {
                                    PMA_updateTargetTables($matching_tables, $update_array, $src_db, $trg_db, $trg_link, $table_index[0], $matching_table_keys,
                                     $display);
                                    unset($update_array[$table_index[0]]);
                                }
                                if (isset($array_insert[$table_index[0]])) {
                                     PMA_insertIntoTargetTable($matching_table, $src_db, $trg_db, $src_link, $trg_link, $table_fields, $array_insert,
                                     $table_index[0], $matching_tables_keys, $source_columns, $add_column_array, $criteria, $target_tables_keys, $uncommon_tables,
                                     $uncommon_tables_fields, $uncommon_cols, $alter_str_array, $source_indexes, $target_indexes, $add_indexes_array, 
                                     $alter_indexes_array, $delete_array, $update_array, $display); 
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
                         $insert_query .= "'" . $result[0] . "'";
                    } else {
                        for ($field_index = 0; $field_index < sizeof($table_fields[$matching_table_index]); $field_index++) {
                            if (isset($result[0][$table_fields[$matching_table_index][$field_index]])) {
                                $insert_query .= "'" . $result[0][$table_fields[$matching_table_index][$field_index]] . "'";
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
* @uses    PMA_DBI_get_fields()
* @uses    PMA_backquote()
* @uses    PMA_DBI_fetch_result()
*                                                                     
* @param    $src_db                 name of source database 
* @param    $trg_db                 name of target database
* @param    $trg_link               connection established with target server
* @param    $src_link               connection established with source server
* @param    $uncommon_table         name of table present in source but not in target
* @param    $table_index            index of table in matching_table_array 
* @param    $uncommon_tables_fields field names of the uncommon table
* @param    $display                 true/false value
*/ 
function PMA_createTargetTables($src_db, $trg_db, $src_link, $trg_link, &$uncommon_tables, $table_index, &$uncommon_tables_fields, $display)
{
    if (isset($uncommon_tables[$table_index])) {
        $fields_result = PMA_DBI_get_fields($src_db, $uncommon_tables[$table_index], $src_link);
        $fields = array();
        foreach ($fields_result as $each_field) {
            $field_name = $each_field['Field'];
            $fields[] = $field_name;
        }
        $uncommon_tables_fields[$table_index] = $fields; 
       
        $Create_Query = PMA_DBI_fetch_value("SHOW CREATE TABLE " . PMA_backquote($src_db) . '.' . PMA_backquote($uncommon_tables[$table_index]), 0, 1, $src_link);

        // Replace the src table name with a `dbname`.`tablename`
        $Create_Table_Query = preg_replace('/' . PMA_backquote($uncommon_tables[$table_index]) . '/', 
                                            PMA_backquote($trg_db) . '.' .PMA_backquote($uncommon_tables[$table_index]),
                                            $Create_Query,
                                            $limit = 1
        );

        $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $src_db . "' 
                        AND TABLE_NAME = '" . $uncommon_tables[$table_index] . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;" ;
                    
        $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $src_link);
        if (sizeof($is_fk_result) > 0) {
            for ($j = 0; $j < sizeof($is_fk_result); $j++)
            {
                if (in_array($is_fk_result[$j]['REFERENCED_TABLE_NAME'], $uncommon_tables)) {
                    $table_index = array_keys($uncommon_tables, $is_fk_result[$j]['REFERENCED_TABLE_NAME']);
                    PMA_createTargetTables($src_db, $trg_db, $trg_link, $src_link, $uncommon_tables, $table_index[0], $uncommon_tables_fields, $display);
                    unset($uncommon_tables[$table_index[0]]);
                }      
            }
         }
         if ($display == true) {
              echo '<p>' . $Create_Table_Query . '</p>';
         }
         PMA_DBI_try_query($Create_Table_Query, $trg_link, 0);
    }                                   
}
/**
* PMA_populateTargetTables() inserts data into uncommon tables after they have been created
* @uses   PMA_DBI_fetch_result()
* @uses  PMA_backquote()
* @uses   sizeof()
* @uses  PMA_DBI_try_query()
* 
* @param  $src_db                 name of source database
* @param  $trg_db                 name of target database
* @param  $src_link               connection established with source server
* @param  $trg_link               connection established with target server
* @param  $uncommon_tables        array containing uncommon table names (table names that are present in source but not in target db) 
* @param  $table_index            index of table in matching_table_array 
* @param  $uncommon_tables_fields field names of the uncommon table
* @param  $display                true/false value
*
* FIXME: This turns NULL values into '' (empty string)
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
            foreach($one_row as $key => $value) {
                $insert_query .= "'" . PMA_sqlAddslashes($value) . "'";
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
* @uses  sizeof()
* @uses  PMA_backquote()
* @uses  PMA_DBI_try_query()
* 
* 
* @param  $trg_db                 name of target database
* @param  $trg_link               connection established with target server
* @param  $matching_tables        array containing matching table names
* @param  $table_index            index of table in matching_table_array
* @param  $target_table_keys      primary key names of the target tables
* @param  $delete array           array containing the key values of rows that are to be deleted 
* @param  $display                true/false value
*/
function PMA_deleteFromTargetTable($trg_db, $trg_link, $matching_tables, $table_index, $target_tables_keys, $delete_array, $display) 
{
    for($i = 0; $i < sizeof($delete_array[$table_index]); $i++) {
        if (isset($target_tables_keys[$table_index])) {
            $delete_query = 'DELETE FROM ' . PMA_backquote($trg_db) . '.' .PMA_backquote($matching_tables[$table_index]) . ' WHERE ';         
            for($y = 0; $y < sizeof($target_tables_keys[$table_index]); $y++) {
                $delete_query .= $target_tables_keys[$table_index][$y] . " = '";
                
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
                        $drop_pk_query = "DELETE FROM " . PMA_backquote($pk_query_result[$b]['TABLE_SCHEMA']) . "." . PMA_backquote($pk_query_result[$b]['TABLE_NAME']) . " WHERE " . $pk_query_result[$b]['COLUMN_NAME'] . " = " . $target_tables_keys[$table_index][$y] . ";";
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
* @uses    PMA_DBI_get_columns_full()
* @uses    sizeof() 
*                                                                     
* @param    $src_db                name of source database 
* @param    $trg_db                name of target database
* @param    $src_link              connection established with source server
* @param    $trg_link              connection established with target server
* @param    $matching_tables       array containing names of matching tables
* @param    $source_columns        array containing columns information of the source tables
* @param    $target_columns        array containing columns information of the target tables
* @param    $alter_str_array       three dimensional associative array first index being the matching table index, second index being column name for which target 
*                                  column have some criteria different and third index containing the criteria which is different.
* @param    $add_column_array      two dimensional associative array, first index of the array contain the matching table number and second index contain the 
*                                  column name which is to be added in the target table
* @param    $uncommon_columns      array containing the columns that are present in the target table but not in the source table
* @param    $criteria              array containing the criterias which are to be checked for field that is present in source table and target table
* @param    $target_tables_keys    array containing the field names which is key in the target table
* @param    $matching_table_index  integer number of the matching table 
*                              
*/
function PMA_structureDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, &$source_columns, &$target_columns, &$alter_str_array,
 &$add_column_array, &$uncommon_columns, $criteria, &$target_tables_keys, $matching_table_index) 
{
    //Gets column information for source and target table
    $source_columns[$matching_table_index] = PMA_DBI_get_columns_full($src_db, $matching_tables[$matching_table_index], null, $src_link);
    $target_columns[$matching_table_index] = PMA_DBI_get_columns_full($trg_db, $matching_tables[$matching_table_index], null, $trg_link);
    foreach ($source_columns[$matching_table_index] as $column_name => $each_column) {
        if (isset($target_columns[$matching_table_index][$column_name]['Field'])) {
            //If column exists in target table then matches criterias like type, null, collation, key, default, comment of the column 
            for ($i = 0; $i < sizeof($criteria); $i++) {
                if ($source_columns[$matching_table_index][$column_name][$criteria[$i]] != $target_columns[$matching_table_index][$column_name][$criteria[$i]]) {
                    if (($criteria[$i] == 'Default') && ($source_columns[$matching_table_index][$column_name][$criteria[$i]] == '' )) {
                        $alter_str_array[$matching_table_index][$column_name][$criteria[$i]] = 'None'; 
                    } else {
                        if (! (($criteria[$i] == 'Key') && (($source_columns[$matching_table_index][$column_name][$criteria[$i]] == 'MUL')
                            || ($target_columns[$matching_table_index][$column_name][$criteria[$i]] == 'MUL') 
                            || ($source_columns[$matching_table_index][$column_name][$criteria[$i]] == 'UNI') 
                            || ($target_columns[$matching_table_index][$column_name][$criteria[$i]] == 'UNI')))) {
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
* @uses    sizeof()
* @uses    in_array()
* @uses    array_keys()
* @uses    PMA_checkForeignKeys()
* @uses    PMA_createTargetTables()
* @uses    PMA_DBI_try_query()
* @uses    PMA_DBI_fetch_result()
* 
* @param   $src_db                 name of source database 
* @param   $trg_db                 name of target database
* @param   $src_link               connection established with source server
* @param   $trg_link               connection established with target server
* @param   $matching_tables        array containing names of matching tables
* @param   $source_columns         array containing columns information of the source tables
* @param   $add_column_array       array containing the names of the column(field) that are to be added in the target
* @param   $matching_tables_fields
* @param   $criteria               array containing the criterias 
* @param   $matching_tables_keys   array containing the field names which is key in the source table
* @param   $target_tables_keys     array containing the field names which is key in the target table
* @param   $uncommon_tables        array containing the table names that are present in source db and not in target db
* @param   $uncommon_tables_fields array containing the names of the fields of the uncommon tables
* @param   $table_counter          integer number of the matching table
* @param   $uncommon_cols
* @param   $display                true/false value
*/
function PMA_addColumnsInTargetTable($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns, &$add_column_array, $matching_tables_fields,
         $criteria, $matching_tables_keys, $target_tables_keys, $uncommon_tables, &$uncommon_tables_fields, $table_counter, $uncommon_cols, $display)
{
    for ($i = 0; $i < sizeof($matching_tables_fields[$table_counter]); $i++) {
        if (isset($add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]])) {
            $query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$table_counter]). " ADD COLUMN " .
            $add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]] . " " . $source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Type'];
 
            if($source_columns[$table_counter][$matching_tables_fields[$table_counter][$i]]['Null'] == 'NO') {
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
                    $query .= $matching_tables_keys[$table_counter][$t];
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
                    PMA_checkForeignKeys($src_db, $src_link, $trg_db, $trg_link, $is_fk_result[0]['REFERENCED_TABLE_NAME'], $uncommon_tables, $uncommon_tables_fields);
                    PMA_createTargetTables($src_db, $trg_db, $trg_link, $src_link, $uncommon_tables, $table_index[0], $uncommon_tables_fields);
                    unset($uncommon_tables[$table_index[0]]);
                }
                $fk_query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$table_counter]) . 
                            "ADD CONSTRAINT FOREIGN KEY " . $add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]] . " 
                            (" . $add_column_array[$table_counter][$matching_tables_fields[$table_counter][$i]] . ") REFERENCES " . PMA_backquote($trg_db) .
                             '.' . PMA_backquote($is_fk_result[0]['REFERENCED_TABLE_NAME']) . " (" . $is_fk_result[0]['REFERENCED_COLUMN_NAME'] . ");";
             
                PMA_DBI_try_query($fk_query, $trg_link, null);    
            }
        }
    }
}
/**
* PMA_checkForeignKeys() checks if the referenced table have foreign keys.
* @uses   sizeof()
* @uses   in_array()
* @uses   array_keys()
* @uses   PMA_checkForeignKeys()
* uses    PMA_createTargetTables()
* 
* @param  $src_db                 name of source database
* @param  $src_link               connection established with source server
* @param  $trg_db                 name of target database
* @param  $trg_link               connection established with target server
* @param  $referenced_table       table whose column is a foreign key in another table
* @param  $uncommon_tables        array containing names that are uncommon 
* @param  $uncommon_tables_fields field names of the uncommon table
* @param  $display                true/false value
*/
function PMA_checkForeignKeys($src_db, $src_link, $trg_db, $trg_link ,$referenced_table, &$uncommon_tables, &$uncommon_tables_fields, $display)
{
    $is_fk_query = "SELECT * FROM  information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $src_db . "' 
                    AND TABLE_NAME = '" . $referenced_table . "' AND TABLE_NAME <> REFERENCED_TABLE_NAME;";
    
    $is_fk_result = PMA_DBI_fetch_result($is_fk_query, null, null, $src_link);
    if (sizeof($is_fk_result) > 0) {
        for ($j = 0; $j < sizeof($is_fk_result); $j++) {
            if (in_array($is_fk_result[$j]['REFERENCED_TABLE_NAME'], $uncommon_tables)) {
                $table_index = array_keys($uncommon_tables, $is_fk_result[$j]['REFERENCED_TABLE_NAME']);
                PMA_checkForeignKeys($src_db, $src_link, $trg_db, $trg_link, $is_fk_result[$j]['REFERENCED_TABLE_NAME'], $uncommon_tables, 
                $uncommon_tables_fields, $display); 
                PMA_createTargetTables($src_db, $trg_db, $trg_link, $src_link, $uncommon_tables, $table_index[0], $uncommon_tables_fields, $display);
                unset($uncommon_tables[$table_index[0]]);
            }      
        }
    }
}
/**
* PMA_alterTargetTableStructure() alters structure of the target table using $alter_str_array
* @uses    sizeof()
* @uses    PMA_DBI_fetch_result()
* @uses    is_string()
* @uses    is_numeric()
* @uses    PMA_DBI_try_query()
* 
* 
* @param   $trg_db                 name of target database
* @param   $trg_link               connection established with target server
* @param   $matching_tables        array containing names of matching tables
* @param   $source_columns         array containing columns information of the source table
* @param   $alter_str_array        array containing the column name and criteria which is to be altered for the targert table
* @param   $matching_tables_fields array containing the name of the fields for the matching table 
* @param   $criteria               array containing the criterias
* @param   $matching_tables_keys   array containing the field names which is key in the source table
* @param   $target_tables_keys     array containing the field names which is key in the target table
* @param   $matching_table_index   integer number of the matching table
* @param   $display                true/false value
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
    $pri_query;
    if (! $check) {
        $pri_query = "ALTER TABLE " . PMA_backquote($trg_db) . '.' . PMA_backquote($matching_tables[$matching_table_index]);
        if (sizeof($target_tables_keys[$matching_table_index]) > 0) {
            $pri_query .= "  DROP PRIMARY KEY ," ;
        }
        $pri_query .= "  ADD PRIMARY KEY (";
        for ($z = 0; $z < sizeof($matching_tables_keys[$matching_table_index]); $z++) {
            $pri_query .= $matching_tables_keys[$matching_table_index][$z];
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
            $matching_tables_fields[$matching_table_index][$t] . ' ' . $source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]['Type']; 
            $found = false;
            for ($i = 0; $i < sizeof($criteria); $i++)
            {
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
                        if( !(isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        }
                        $sql_query .=  " COLLATE " . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] ;
                    }
                    if (($criteria[$i] == 'Default') && ($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] == 'None')) {
                        if( !(isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        }
                    } elseif($criteria[$i] == 'Default') {
                        if(! (isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
                            $sql_query .= " Not Null " ;
                        } 
                        if (is_string($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]])) {
                            if ($source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]['Type'] != 'timestamp') {
                                $sql_query .=  " DEFAULT '" . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]] . "'";
                            } elseif($source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]]['Type'] == 'timestamp') {
                                $sql_query .=  " DEFAULT " . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]]; 
                            }
                        } elseif (is_numeric($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]])) {
                            $sql_query .=  " DEFAULT " . $alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[$i]];
                        }
                    }
                    if ($criteria[$i] == 'Comment') {
                        if( !(isset($alter_str_array[$matching_table_index][$matching_tables_fields[$matching_table_index][$t]][$criteria[2]]))) {
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
    for($p = 0; $p < sizeof($matching_tables_keys[$matching_table_index]); $p++) {
        if ((isset($alter_str_array[$matching_table_index][$matching_tables_keys[$matching_table_index][$p]]['Key']))) {
            $check = true;
            $query .= ' MODIFY ' . $matching_tables_keys[$matching_table_index][$p] . ' '
            . $source_columns[$matching_table_index][$matching_tables_fields[$matching_table_index][$p]]['Type'] . ' Not Null ';
            if ($p < (sizeof($matching_tables_keys[$matching_table_index]) - 1)) {
                $query .= ', ';
            }
        }
    }
    if ($check) {
        if ($display == true) {
            echo '<p>' . $query . '</p>';
        }                
        PMA_DBI_try_query($query, $trg_link, 0);
    }
}

/**
* PMA_removeColumnsFromTargetTable() removes the columns which are present in target table but not in source table.
* @uses   sizeof()
* @uses   PMA_DBI_try_query()
* @uses   PMA_DBI_fetch_result() 
* 
* @param  $trg_db            name of target database
* @param  $trg_link          connection established with target server
* @param  $matching_tables   array containing names of matching tables
* @param  $uncommon_columns  array containing the names of the column which are to be dropped from the target table
* @param  $table_counter     index of the matching table as in $matchiing_tables array 
* @param  $display           true/false value
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
                                      DROP FOREIGN KEY " . $pk_query_result[$b]['CONSTRAINT_NAME'] . ", DROP COLUMN " . $pk_query_result[$b]['COLUMN_NAME'] . ";";
                    PMA_DBI_try_query($drop_pk_query, $trg_link, 0);                   
                }              
            }       
            $query = "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $trg_db . "' AND TABLE_NAME = '" 
                     . $matching_tables[$table_counter]. "' AND COLUMN_NAME = '" . $uncommon_columns[$table_counter][$a] . "'
                      AND TABLE_NAME <> REFERENCED_TABLE_NAME;";

            $result = PMA_DBI_fetch_result($query, null, null, $trg_link);

            if (sizeof($result) > 0) {
                $drop_query .= " DROP FOREIGN KEY " . $result[0]['CONSTRAINT_NAME'] . ",";
            }
            $drop_query .=  " DROP COLUMN " . $uncommon_columns[$table_counter][$a];
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
*  PMA_indexesDiffInTables() compares the source table indexes with target table indexes and keep the indexes to be added in target table in $add_indexes_array
*  indexes to be altered in $alter_indexes_array and indexes to be removed from target table in $remove_indexes_array.
*  Only keyname and uniqueness characteristic of the indexes are altered.
*  @uses  sizeof()
*  @uses  PMA_DBI_get_table_indexes()
* 
* @param   $src_db                 name of source database 
* @param   $trg_db                 name of target database
* @param   $src_link               connection established with source server
* @param   $trg_link               connection established with target server
* @param  $matching_tables         array containing the matching tables name
* @param  $source_indexes          array containing the indexes of the source table 
* @param  $target_indexes          array containing the indexes of the target table
* @param  $add_indexes_array       array containing the name of the column on which the index is to be added in the target table
* @param  $alter_indexes_array     array containing the key name which needs to be altered
* @param  $remove_indexes_array    array containing the key name of the index which is to be removed from the target table
* @param  $table_counter           number of the matching table 
*/
function PMA_indexesDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, &$source_indexes, &$target_indexes, &$add_indexes_array,
 &$alter_indexes_array, &$remove_indexes_array, $table_counter)
{
    //Gets indexes information for source and target table
    $source_indexes[$table_counter] = PMA_DBI_get_table_indexes($src_db, $matching_tables[$table_counter],$src_link);
    $target_indexes[$table_counter] = PMA_DBI_get_table_indexes($trg_db, $matching_tables[$table_counter],$trg_link); 
    for ($a = 0; $a < sizeof($source_indexes[$table_counter]); $a++) {
        $found = false;
        $z = 0;
        //Compares key name and non_unique characteristic of source indexes with target indexes
        /*
         * @todo compare the length of each sub part
         */
        while (($z <= sizeof($target_indexes[$table_counter])) && ($found == false))
        {
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
            if(! ($source_indexes[$table_counter][$a]['Key_name'] == 'PRIMARY')) {
                $add_indexes_array [$table_counter][] = $source_indexes[$table_counter][$a]['Column_name']; 
            }
        }
    }
    
    //Finds indexes that exist on target table but not on source table
    for ($b = 0; $b < sizeof($target_indexes[$table_counter]); $b++) {
        $found = false;
        $c = 0;
        while (($c <= sizeof($source_indexes[$table_counter])) && ($found == false))
        {
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
* @uses   sizeof()
* @uses   PMA_DBI_try_query()
* 
* @param   $trg_db                 name of target database
* @param   $trg_link               connection established with target server
* @param  $matching_tables         array containing the matching tables name
* @param  $source_indexes          array containing the indexes of the source table 
* @param  $target_indexes          array containing the indexes of the target table
* @param  $add_indexes_array       array containing the column names on which indexes are to be created in target table
* @param  $alter_indexes_array     array containing the column names for which indexes are to be altered
* @param  $remove_indexes_array    array containing the key name of the indexes which are to be removed from the target table
* @param  $table_counter           number of the matching table 
* @param  $display                 true/false value
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
                        $sql .= " INDEX " . $source_indexes[$table_counter][$b]['Key_name'] . " (" . $add_indexes_array[$table_counter][$a] . " );";
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
                $drop_index_query .= " DROP INDEX " . $remove_indexes_array[$table_counter][$a];  
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
 * @uses   $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] 
 * 
 * @param   $query                 the query to display 
*/
function PMA_displayQuery($query) {
    if (strlen($query) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
        $query = substr($query, 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) . '[...]';
    }
    echo '<p>' . htmlspecialchars($query) . '</p>';
}

/**
 * PMA_syncDisplayHeaderSource() shows the header for source database 
 * @uses   $GLOBALS['strDatabase_src'] 
 * @uses   $GLOBALS['strDifference'] 
 * @uses   $GLOBALS['strCurrentServer'] 
 * @uses   $GLOBALS['strRemoteServer'] 
 * @uses   $_SESSION['src_type'] 
 * @uses   $_SESSION['src_server']['host'] 
 *
 * @param  string $src_db          source db name 
*/
function PMA_syncDisplayHeaderSource($src_db) {
    echo '<div id="serverstatus" style = "overflow: auto; width: 1020px; height: 220px; border-left: 1px gray solid; border-bottom: 1px gray solid; padding:0px; margin-bottom: 1em "> ';

    echo '<table id="serverstatusconnections" class="data" width="55%">';
    echo '<tr>';
    echo '<th>' . $GLOBALS['strDatabase_src'] . ':  ' . $src_db . '<br />(';
    if ('cur' == $_SESSION['src_type']) {
        echo $GLOBALS['strCurrentServer'];
    } else {
        echo $GLOBALS['strRemoteServer'] . ' ' . $_SESSION['src_server']['host'];
    }
    echo ')</th>';
    echo '<th>' . $GLOBALS['strDifference'] . '</th>';
    echo '</tr>';
}

/**
 * PMA_syncDisplayHeaderTargetAndMatchingTables() shows the header for target database and the matching tables
 * @uses   $GLOBALS['strDatabase_trg'] 
 * @uses   $GLOBALS['strCurrentServer'] 
 * @uses   $GLOBALS['strRemoteServer'] 
 * @uses   $_SESSION['trg_type'] 
 * @uses   $_SESSION['trg_server']['host'] 
 * 
 * @param   string  $trg_db          target db name 
 * @param   array   $matching_tables
 * @return  boolean $odd_row         current value of this toggle 
*/
function PMA_syncDisplayHeaderTargetAndMatchingTables($trg_db, $matching_tables) {
    echo '<table id="serverstatusconnections" class="data" width="43%">';
    echo '<tr>';
    echo '<th>' . $GLOBALS['strDatabase_trg'] . ':  '. $trg_db . '<br />(';
    if ('cur' == $_SESSION['trg_type']) {
        echo $GLOBALS['strCurrentServer'];
    } else {
        echo $GLOBALS['strRemoteServer'] . ' ' . $_SESSION['trg_server']['host'];
    }
    echo ')</th>';
    echo '</tr>';
    $odd_row = false;
    foreach ($matching_tables as $tbl_name) {
        $odd_row = PMA_syncDisplayBeginTableRow($odd_row);
        echo '<td>  ' . htmlspecialchars($tbl_name) . '</td>';
        echo '</tr>';
    }
    return $odd_row;
}

/**
 * PMA_syncDisplayBeginTableRow() displays the TR tag for alternating colors 
 * 
 * @param   boolean $odd_row        current status of the toggle 
 * @return  boolean $odd_row        final status of the toggle 
*/
function PMA_syncDisplayBeginTableRow($odd_row) {
    $odd_row = ! $odd_row;
    echo '<tr height="32" class=" ';
    echo $odd_row ? 'odd' : 'even';
    echo '">';
    return $odd_row;
}
?>
