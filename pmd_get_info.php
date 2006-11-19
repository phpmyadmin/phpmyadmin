<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/*
@author  Ivan A Kirillov (develop.php@gmail.com)
www.phpMyDesigner.net
*/
require_once './libraries/relation.lib.php';
function get_tabs() // PMA_DBI
{
    global $db;
    $GLOBALS['PMD']['TABLE_NAME'] = array();// that foreach no error
    $GLOBALS['PMD']['OWNER'] = array(); 
    $GLOBALS['PMD']['TABLE_NAME_SMALL'] = array(); 

    $tables = PMA_DBI_get_tables_full($db);
    // seems to be needed later
    PMA_DBI_select_db($db);
    $i = 0;
    foreach ($tables as $one_table) {
        $GLOBALS['PMD']['TABLE_NAME'][$i] = $db . "." . $one_table['TABLE_NAME'];
        $GLOBALS['PMD']['OWNER'][$i] = $db;
        $GLOBALS['PMD']['TABLE_NAME_SMALL'][$i] = $one_table['TABLE_NAME'];  
        $GLOBALS['PMD']['TABLE_TYPE'][$i] = strtoupper($one_table['ENGINE']);
        $i++;
    } 
    //  return $GLOBALS['PMD'];       // many bases // not use ??????
}

function get_tab_info() // PMA_DBI //PMA_backquote
{ 
    global $db;
    PMA_DBI_select_db($db);
    $tab_column = array();
    for ( $i=0; $i < sizeof( $GLOBALS['PMD']["TABLE_NAME"] ); $i++ ) {
        PMA_DBI_select_db($db);
        $fields_rs   = PMA_DBI_query('SHOW FULL FIELDS FROM '.PMA_backquote($GLOBALS['PMD']["TABLE_NAME_SMALL"][$i]), NULL, PMA_DBI_QUERY_STORE);
        $fields_cnt  = PMA_DBI_num_rows($fields_rs);
        $j=0;
        while ($row = PMA_DBI_fetch_assoc($fields_rs)) {   
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['COLUMN_ID'][$j]   = $j;
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['COLUMN_NAME'][$j] = $row['Field'];
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['TYPE'][$j]        = $row['Type'];
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['NULLABLE'][$j]    = $row['Null'];
            $j++;
        }
    }
return $tab_column;
}
//-------------------------------------CONTR-----------------------------------------------
function get_script_contr() {
    global $db;
    PMA_DBI_select_db($db);
    $con["C_NAME"] = array();
    PMA_getRelationsParam();
    $i = 0;
    $alltab_rs  = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db), NULL, PMA_DBI_QUERY_STORE);
    while ($val = @PMA_DBI_fetch_row($alltab_rs)) {
        $row = PMA_getForeigners($db,$val[0],'','internal');
        if ($row !== false) {
            foreach ($row as $field => $value) { 
                $con['C_NAME'][$i] = '';
                $con['DTN'][$i]    = $db . "." . $val[0];
                $con['DCN'][$i]    = $field;
                $con['STN'][$i]    = $value['foreign_db'] . "." . $value['foreign_table'];
                $con['SCN'][$i]    = $value['foreign_field'];
                $i++;
            }
        }
        $row = PMA_getForeigners($db,$val[0],'','innodb');
        if ($row !== false) {
            foreach ($row as $field => $value) { 
                $con['C_NAME'][$i] = '';
                $con['DTN'][$i]    = $db.".".$val[0];
                $con['DCN'][$i]    = $field;
                $con['STN'][$i]    = $value['foreign_db'].".".$value['foreign_table'];
                $con['SCN'][$i]    = $value['foreign_field'];
                $i++;
            }
        }
    } 
  
    $ti = 0;
    $script_contr = "<script> var contr = new Array();";
    for ( $i=0; $i < sizeof( $con["C_NAME"] ); $i++ ) {
        $script_contr .= " contr[$ti] = new Array();\n";
        $script_contr .= "  contr[$ti]['".$con['C_NAME'][$i]."'] = new Array();\n";
        if (in_array($con['DTN'][$i],$GLOBALS['PMD']["TABLE_NAME"]) && in_array($con['STN'][$i],$GLOBALS['PMD']["TABLE_NAME"])) {
            $script_contr .= "  contr[$ti]['".$con['C_NAME'][$i]."']['".$con['DTN'][$i]."'] = new Array();\n";$m_col = array();//}
            $script_contr .= "  contr[$ti]['".$con['C_NAME'][$i]."']['".$con['DTN'][$i]."']['".$con['DCN'][$i]."'] = new Array();\n";//}
            $script_contr .= "    contr[$ti]['".$con['C_NAME'][$i]."']['".$con['DTN'][$i]."']['".$con['DCN'][$i]."'][0] = '".$con['STN'][$i]."';\n"; // 
            $script_contr .= "    contr[$ti]['".$con['C_NAME'][$i]."']['".$con['DTN'][$i]."']['".$con['DCN'][$i]."'][1] = '".$con['SCN'][$i]."';\n"; // 
        }
    $ti++;
    }
    $script_contr .= "</script>";
    return $script_contr;
}

function get_pk_or_unique_keys() {
    global $db;
    require_once('./libraries/tbl_indexes.lib.php');

    PMA_DBI_select_db($db);
    $tables_pk_or_unique_keys = array();
  
    for( $I=0; $I<sizeof($GLOBALS['PMD']['TABLE_NAME_SMALL']); $I++) {
        $ret_keys = PMA_get_indexes($GLOBALS['PMD']['TABLE_NAME_SMALL'][$I]);
        if (! empty($ret_keys)) {
            // reset those as the function uses them by reference
            $indexes = $indexes_info = $indexes_data = array();
            PMA_extract_indexes($ret_keys, $indexes, $indexes_info, $indexes_data);
            // for now, take into account only the first index segment
            foreach ($indexes_data as $key_name => $one_index) {
                $column_name = $one_index[1]['Column_name'];
                if (isset($indexes_info[$key_name]) && $indexes_info[$key_name]['Non_unique'] == 0) {
                    $tables_pk_or_unique_keys[$GLOBALS['PMD']['OWNER'][$I] . '.' .$GLOBALS['PMD']['TABLE_NAME_SMALL'][$I] . '.' . $column_name] = 1;
                }
            }
        }
    }
    return $tables_pk_or_unique_keys;
}

function get_all_keys() {
    global $db;
    require_once('./libraries/tbl_indexes.lib.php');

    PMA_DBI_select_db($db);
    $tables_all_keys = array();
  
    for( $I=0; $I<sizeof($GLOBALS['PMD']['TABLE_NAME_SMALL']); $I++) {
        $ret_keys = PMA_get_indexes($GLOBALS['PMD']['TABLE_NAME_SMALL'][$I]);
        if (! empty($ret_keys)) {
            // reset those as the function uses them by reference
            $indexes = $indexes_info = $indexes_data = array();
            PMA_extract_indexes($ret_keys, $indexes, $indexes_info, $indexes_data);
            // for now, take into account only the first index segment
            foreach ($indexes_data as $one_index) {
                $column_name = $one_index[1]['Column_name'];
                $tables_all_keys[$GLOBALS['PMD']['OWNER'][$I] . '.' .$GLOBALS['PMD']['TABLE_NAME_SMALL'][$I] . '.' . $column_name] = 1;
            }
        }
    }
    return $tables_all_keys;
}

function get_script_tabs() {
    $script_tabs = "<script> var j_tabs = new Array();\n";
    for ( $i=0; $i < sizeof( $GLOBALS['PMD']['TABLE_NAME'] ); $i++ ) {
        $script_tabs .= "j_tabs['".$GLOBALS['PMD']['TABLE_NAME'][$i]."'] = '".$GLOBALS['PMD']['TABLE_TYPE'][$i]."';\n";
    }
    $script_tabs .= "</script>";
    return $script_tabs;
}

function get_tab_pos() { 
    PMA_getRelationsParam();
    $stmt = PMA_query_as_cu("SELECT * FROM " . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']), FALSE, PMA_DBI_QUERY_STORE); 
    if ( $stmt ) // exist table repository
    {
        while ($t_p = PMA_DBI_fetch_array($stmt, MYSQL_ASSOC)) { 
            $t_name = $t_p['db_name'] . '.' . $t_p['table_name'];
            $tab_pos[ $t_name ]['X'] = $t_p['x'];
            $tab_pos[ $t_name ]['Y'] = $t_p['y'];
            $tab_pos[ $t_name ]['V'] = $t_p['v'];
            $tab_pos[ $t_name ]['H'] = $t_p['h'];
        }
    }    
    return isset($tab_pos) ? $tab_pos : NULL;
}

function get_owners() {
    $m = array();
    $j = 0;
    for ( $i=0; $i < sizeof( $GLOBALS['PMD']["OWNER"] ); $i++ ) {
        if( ! in_array($GLOBALS['PMD']["OWNER"][$i],$m)) {
            $m[$j++] = $GLOBALS['PMD']["OWNER"][$i];
        }
    }
    return $m;
}

?>
