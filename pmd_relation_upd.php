<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

include_once 'pmd_session.php';
require_once './libraries/relation.lib.php';
extract($_POST); 
extract($_GET);
$die_save_pos = 0;
include_once 'pmd_save_pos.php';
list($DB1,$T1) = explode(".",$T1);
list($DB2,$T2) = explode(".",$T2);

PMA_getRelationsParam();

//++++++++++++++++++++++++++++++++++++++++++++++++++++ InnoDB ++++++++++++++++++++++++++++++++++++++++++++++++++++


$tables = PMA_DBI_get_tables_full($db, $T1);
$type_T1 = strtoupper($tables[$T1]['ENGINE']);
$tables = PMA_DBI_get_tables_full($db, $T2);
$type_T2 = strtoupper($tables[$T2]['ENGINE']);

if ($type_T1 == 'INNODB' && $type_T2 == 'INNODB') {
    $existrel_innodb = PMA_getForeigners($DB2, $T2, '', 'innodb');

    if (PMA_MYSQL_INT_VERSION >= 40013 && isset($existrel_innodb[$F2]['constraint'])) {
        $upd_query  = 'ALTER TABLE ' . PMA_backquote($T2)
                  . ' DROP FOREIGN KEY '
                  . PMA_backquote($existrel_innodb[$F2]['constraint']);
        $upd_rs     = PMA_DBI_query($upd_query);
    }
}
//---------------------------------------------------------------------------------------------------  

PMA_query_as_cu("DELETE FROM ".$cfg['Server']['relation']." WHERE 
                 master_db = '$DB2' AND master_table = '$T2' AND master_field = '$F2' 
                 AND foreign_db = '$DB1' AND foreign_table = '$T1' AND foreign_field = '$F1'", FALSE, PMA_DBI_QUERY_STORE);

PMD_return(1, $strRelationDeleted); 

function PMD_return($b,$ret)
{
  global $K;
  die('<root act="relation_upd" return="'.$ret.'" b="'.$b.'" K="'.$K.'"></root>');
}
?>

