<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin-Designer
 */

/**
 *
 */
include_once 'pmd_common.php';
require_once './libraries/relation.lib.php';
extract($_POST, EXTR_SKIP);
extract($_GET, EXTR_SKIP);
$die_save_pos = 0;
include_once 'pmd_save_pos.php';
list($DB1,$T1) = explode(".",$T1);
list($DB2,$T2) = explode(".",$T2);

$tables = PMA_DBI_get_tables_full($db, $T1);
$type_T1 = strtoupper($tables[$T1]['ENGINE']);
$tables = PMA_DBI_get_tables_full($db, $T2);
$type_T2 = strtoupper($tables[$T2]['ENGINE']);

if (PMA_foreignkey_supported($type_T1) && PMA_foreignkey_supported($type_T2) && $type_T1 == $type_T2) {
    // InnoDB
    $existrel_foreign = PMA_getForeigners($DB2, $T2, '', 'foreign');

    if (isset($existrel_foreign[$F2]['constraint'])) {
        $upd_query  = 'ALTER TABLE ' . PMA_backquote($T2)
                  . ' DROP FOREIGN KEY '
                  . PMA_backquote($existrel_foreign[$F2]['constraint']);
        $upd_rs     = PMA_DBI_query($upd_query);
    }
} else {
    // internal relations
    PMA_query_as_controluser('DELETE FROM '
              . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
              . $cfg['Server']['relation'].' WHERE '
              . 'master_db = \'' . PMA_sqlAddslashes($DB2) . '\''
              . ' AND master_table = \'' . PMA_sqlAddslashes($T2) . '\''
              . ' AND master_field = \'' . PMA_sqlAddslashes($F2) . '\''
              . ' AND foreign_db = \'' . PMA_sqlAddslashes($DB1) . '\''
              . ' AND foreign_table = \'' . PMA_sqlAddslashes($T1) . '\''
              . ' AND foreign_field = \'' . PMA_sqlAddslashes($F1) . '\''
              , FALSE, PMA_DBI_QUERY_STORE);
}
PMD_return_upd(1, 'strRelationDeleted');

function PMD_return_upd($b,$ret)
{
  global $K;
  header("Content-Type: text/xml; charset=utf-8");
  header("Cache-Control: no-cache");
  die('<root act="relation_upd" return="'.$ret.'" b="'.$b.'" K="'.$K.'"></root>');
}
?>
