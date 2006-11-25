<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

include_once 'pmd_common.php';
require_once './libraries/relation.lib.php';
      
$alltab_rs = PMA_query_as_cu('SHOW TABLES FROM '.PMA_backquote($cfg['Server']['pmadb']),FALSE,PMA_DBI_QUERY_STORE) or PMD_err_sav(); 

$seen_pmd_table = false;
while ($tab_name = @PMA_DBI_fetch_row($alltab_rs)) {
    if (stristr($tab_name[0],$GLOBALS['cfgRelation']['designer_coords'])) {
        $seen_pmd_table = true;
        break; 
    }
}

if ( ! $seen_pmd_table) {
    PMD_err_sav();   
}
    
foreach ($t_x as $key => $value) { 
    list($DB,$TAB) = explode(".", $key);
    PMA_query_as_cu("DELETE FROM ".$GLOBALS['cfgRelation']['designer_coords']." 
                   WHERE `db_name`='$DB' AND `table_name` = '$TAB'",FALSE,PMA_DBI_QUERY_STORE) or PMD_err_sav();
    PMA_query_as_cu("INSERT INTO ".$GLOBALS['cfgRelation']['designer_coords']." 
                         (db_name, table_name, x, y, v, h)
                  VALUES ('$DB','$TAB','$t_x[$key]','$t_y[$key]','$t_v[$key]','$t_h[$key]')",FALSE,PMA_DBI_QUERY_STORE) or PMD_err_sav();
}
//----------------------------------------------------------------------------

function PMD_err_sav() {
    global $die_save_pos; // if this file included
    if (! empty($die_save_pos)) {
        die('<root act="save_pos" return="Problem on table ' . $GLOBALS['cfgRelation']['designer_coords'] . '"></root>');
    }
}

if(! empty($die_save_pos)) {
?>
<root act='save_pos' return='<?php echo $strModifications; ?>'></root>
<? 
}
?>
