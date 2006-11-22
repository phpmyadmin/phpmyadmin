<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

include_once 'pmd_common.php';
$die_save_pos = 0;
include_once 'pmd_save_pos.php';
require_once './libraries/relation.lib.php';
extract($_POST); 
PMA_getRelationsParam();

$tables = PMA_DBI_get_tables_full($db, $T1);
$type_T1 = strtoupper($tables[$T1]['ENGINE']);
$tables = PMA_DBI_get_tables_full($db, $T2);
$type_T2 = strtoupper($tables[$T2]['ENGINE']);

//  I n n o D B
if ($type_T1 == 'INNODB' and $type_T2 == 'INNODB') {
    // relation exists?
    $existrel_innodb = PMA_getForeigners($db, $T2, '', 'innodb'); 
    if (isset($existrel_innodb[$F2]) 
     && isset($existrel_innodb[$F2]['constraint'])) {
	     PMD_return(0,'ERROR : Relation already exists!');
    }
// note: in InnoDB, the index does not requires to be on a PRIMARY
// or UNIQUE key
// improve: check all other requirements for InnoDB relations
    $result      = PMA_DBI_query('SHOW INDEX FROM ' . PMA_backquote($T1) . ';');
    $index_array1   = array(); // will be use to emphasis prim. keys in the table view
    while ($row = PMA_DBI_fetch_assoc($result))
        $index_array1[$row['Column_name']] = 1;
    PMA_DBI_free_result($result);
  
    $result     = PMA_DBI_query('SHOW INDEX FROM ' . PMA_backquote($T2) . ';');
    $index_array2  = array(); // will be used to emphasis prim. keys in the table view
    while ($row = PMA_DBI_fetch_assoc($result)) 
        $index_array2[$row['Column_name']] = 1;
    PMA_DBI_free_result($result);

    if (! empty($index_array1[$F1]) && ! empty($index_array2[$F2])) {
        $upd_query  = 'ALTER TABLE ' . PMA_backquote($T2)
                 . ' ADD FOREIGN KEY ('
                 . PMA_backquote($F2) . ')'
                 . ' REFERENCES '
                 . PMA_backquote($db) . '.'
                 . PMA_backquote($T1) . '('
                 . PMA_backquote($F1) . ')';

        if ($on_delete != 'nix') { 
            $upd_query   .= ' ON DELETE ' . $on_delete;
        }
        if ($on_update != 'nix') {
            $upd_query   .= ' ON UPDATE ' . $on_update;
        }
        PMA_DBI_try_query($upd_query) or PMD_return(0,'ERROR : Relation not added!!!');
    PMD_return(1,$strInnoDBRelationAdded);
    }

//  n o n - I n n o D B
} else {
    // no need to recheck if the keys are primary or unique at this point,
    // this was checked on the interface part

    $q = "INSERT INTO ".PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])." 
       (master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)
       VALUES ('".$db."','".$T2."','$F2','".
       $db."','".$T1."','$F1')";
       PMA_query_as_cu( $q , true, PMA_DBI_QUERY_STORE);// or PMD_return(0,'ERROR : Relation not added!'); 
       
   PMD_return(1, $strInternalRelationAdded); 
}

function PMD_return($b,$ret)
{
    global $db,$T1,$F1,$T2,$F2;
    die('<root act="relation_new" return="'.$ret.'" b="'.$b.'" DB1="'.$db.'" T1="'.$T1.'" F1="'.$F1.'" DB2="'.$db.'" T2="'.$T2.'" F2="'.$F2.'"></root>');
}
?>
