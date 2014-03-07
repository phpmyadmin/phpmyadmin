<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PMD handler for creating new relation
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/common.inc.php';

PMA_Response::getInstance()->disable();

require_once 'libraries/pmd_common.php';
$die_save_pos = 0;
require_once 'pmd_save_pos.php';
extract($_POST, EXTR_SKIP);

$tables = $GLOBALS['dbi']->getTablesFull($db, $T1);
$type_T1 = strtoupper($tables[$T1]['ENGINE']);
$tables = $GLOBALS['dbi']->getTablesFull($db, $T2);
$type_T2 = strtoupper($tables[$T2]['ENGINE']);

// native foreign key
if (PMA_Util::isForeignKeySupported($type_T1)
    && PMA_Util::isForeignKeySupported($type_T2)
    && $type_T1 == $type_T2
) {
    // relation exists?
    $existrel_foreign = PMA_getForeigners($db, $T2, '', 'foreign');
    if (isset($existrel_foreign[$F2])
        && isset($existrel_foreign[$F2]['constraint'])
    ) {
         PMD_return_new(0, __('Error: relation already exists.'));
    }
    // note: in InnoDB, the index does not requires to be on a PRIMARY
    // or UNIQUE key
    // improve: check all other requirements for InnoDB relations
    $result = $GLOBALS['dbi']->query(
        'SHOW INDEX FROM ' . PMA_Util::backquote($db)
        . '.' . PMA_Util::backquote($T1) . ';'
    );
    $index_array1 = array(); // will be use to emphasis prim. keys in the table view
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        $index_array1[$row['Column_name']] = 1;
    }
    $GLOBALS['dbi']->freeResult($result);

    $result = $GLOBALS['dbi']->query(
        'SHOW INDEX FROM ' . PMA_Util::backquote($db)
        . '.' . PMA_Util::backquote($T2) . ';'
    );
    // will be used to emphasis prim. keys in the table view
    $index_array2 = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        $index_array2[$row['Column_name']] = 1;
    }
    $GLOBALS['dbi']->freeResult($result);

    if (! empty($index_array1[$F1]) && ! empty($index_array2[$F2])) {
        $upd_query  = 'ALTER TABLE ' . PMA_Util::backquote($db)
            . '.' . PMA_Util::backquote($T2)
            . ' ADD FOREIGN KEY ('
            . PMA_Util::backquote($F2) . ')'
            . ' REFERENCES '
            . PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($T1) . '('
            . PMA_Util::backquote($F1) . ')';

        if ($on_delete != 'nix') {
            $upd_query   .= ' ON DELETE ' . $on_delete;
        }
        if ($on_update != 'nix') {
            $upd_query   .= ' ON UPDATE ' . $on_update;
        }
        $upd_query .= ';';
        $GLOBALS['dbi']->tryQuery($upd_query) or PMD_return_new(0, __('Error: Relation not added.'));
        PMD_return_new(1, __('FOREIGN KEY relation added'));
    }
} else { // internal (pmadb) relation
    if ($GLOBALS['cfgRelation']['relwork'] == false) {
        PMD_return_new(0, __('Error: Relational features are disabled!'));
    } else {
        // no need to recheck if the keys are primary or unique at this point,
        // this was checked on the interface part

        $q  = 'INSERT INTO ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_Util::backquote($cfgRelation['relation'])
                            . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                            . ' values('
                            . '\'' . PMA_Util::sqlAddSlashes($db) . '\', '
                            . '\'' . PMA_Util::sqlAddSlashes($T2) . '\', '
                            . '\'' . PMA_Util::sqlAddSlashes($F2) . '\', '
                            . '\'' . PMA_Util::sqlAddSlashes($db) . '\', '
                            . '\'' . PMA_Util::sqlAddSlashes($T1) . '\','
                            . '\'' . PMA_Util::sqlAddSlashes($F1) . '\')';

        if (PMA_queryAsControlUser($q, false, PMA_DatabaseInterface::QUERY_STORE)) {
            PMD_return_new(1, __('Internal relation added'));
        } else {
            PMD_return_new(0, __('Error: Relation not added.'));
        }
    }
}

/**
 * Send xml
 *
 * @param string $b   Value of attribute "b"
 * @param string $ret Value of attribute "return"
 */
function PMD_return_new($b,$ret)
{
    global $db,$T1,$F1,$T2,$F2;
    header("Content-Type: text/xml; charset=utf-8");
    header("Cache-Control: no-cache");
    die('<root act="relation_new" return="'.$ret.'" b="'.$b.
    '" DB1="'.urlencode($db).
    '" T1="'.urlencode($T1).
    '" F1="'.urlencode($F1).
    '" DB2="'.urlencode($db).
    '" T2="'.urlencode($T2).
    '" F2="'.urlencode($F2).
    '"></root>');
}
?>
