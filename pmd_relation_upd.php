<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PMD relation update handler
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/common.inc.php';

PMA_Response::getInstance()->disable();

require_once 'libraries/pmd_common.php';
extract($_POST, EXTR_SKIP);
extract($_GET, EXTR_SKIP);
$die_save_pos = 0;
require_once 'pmd_save_pos.php';
list($DB1, $T1) = explode(".", $T1);
list($DB2, $T2) = explode(".", $T2);

$tables = $GLOBALS['dbi']->getTablesFull($db, $T1);
$type_T1 = strtoupper($tables[$T1]['ENGINE']);
$tables = $GLOBALS['dbi']->getTablesFull($db, $T2);
$type_T2 = strtoupper($tables[$T2]['ENGINE']);

$try_to_delete_internal_relation = false;

if (PMA_Util::isForeignKeySupported($type_T1)
    && PMA_Util::isForeignKeySupported($type_T2)
    && $type_T1 == $type_T2
) {
    // InnoDB
    $existrel_foreign = PMA_getForeigners($DB2, $T2, '', 'foreign');

    if (isset($existrel_foreign[$F2]['constraint'])) {
        $upd_query  = 'ALTER TABLE ' . PMA_Util::backquote($DB2)
            . '.' . PMA_Util::backquote($T2) . ' DROP FOREIGN KEY '
            . PMA_Util::backquote($existrel_foreign[$F2]['constraint'])
            . ';';
        $upd_rs     = $GLOBALS['dbi']->query($upd_query);
    } else {
        // there can be an internal relation even if InnoDB
        $try_to_delete_internal_relation = true;
    }
} else {
    $try_to_delete_internal_relation = true;
}
if ($try_to_delete_internal_relation) {
    // internal relations
    PMA_queryAsControlUser(
        'DELETE FROM '
        . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
        . $cfg['Server']['relation'] . ' WHERE '
        . 'master_db = \'' . PMA_Util::sqlAddSlashes($DB2) . '\''
        . ' AND master_table = \'' . PMA_Util::sqlAddSlashes($T2) . '\''
        . ' AND master_field = \'' . PMA_Util::sqlAddSlashes($F2) . '\''
        . ' AND foreign_db = \'' . PMA_Util::sqlAddSlashes($DB1) . '\''
        . ' AND foreign_table = \'' . PMA_Util::sqlAddSlashes($T1) . '\''
        . ' AND foreign_field = \'' . PMA_Util::sqlAddSlashes($F1) . '\'',
        false,
        PMA_DatabaseInterface::QUERY_STORE
    );
}
PMA_returnUpd(1, __('Relation deleted'));

?>
