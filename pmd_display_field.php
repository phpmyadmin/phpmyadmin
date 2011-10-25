<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/pmd_common.php';


$table = $T;
$display_field = $F;

if ($cfgRelation['displaywork']) {

    $disp     = PMA_getDisplayField($db, $table);
    if ($disp) {
        if ($display_field != $disp) {
            $upd_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                       . ' SET display_field = \'' . PMA_sqlAddSlashes($display_field) . '\''
                       . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                       . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\'';
        }
    } elseif ($display_field != '') {
        $upd_query = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                   . '(db_name, table_name, display_field) '
                   . ' VALUES('
                   . '\'' . PMA_sqlAddSlashes($db) . '\','
                   . '\'' . PMA_sqlAddSlashes($table) . '\','
                   . '\'' . PMA_sqlAddSlashes($display_field) . '\')';
    }

    if (isset($upd_query)) {
        $upd_rs    = PMA_query_as_controluser($upd_query);
    }
} // end if

header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: no-cache");
die("<root act='save_pos' return=__('Modifications have been saved')></root>");
?>
