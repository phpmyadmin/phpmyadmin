<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/common.inc.php';

PMA_Response::getInstance()->disable();

require_once 'libraries/pmd_common.php';

$common_functions = PMA_CommonFunctions::getInstance();

$table = $T;
$display_field = $F;

if ($cfgRelation['displaywork']) {

    $disp     = PMA_getDisplayField($db, $table);
    if ($disp) {
        if ($display_field != $disp) {
            $upd_query = 'UPDATE ' . $common_functions->backquote($GLOBALS['cfgRelation']['db']) . '.' . $common_functions->backquote($cfgRelation['table_info'])
                       . ' SET display_field = \'' . $common_functions->sqlAddSlashes($display_field) . '\''
                       . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                       . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . $common_functions->backquote($GLOBALS['cfgRelation']['db']) . '.' . $common_functions->backquote($cfgRelation['table_info'])
                       . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                       . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        }
    } elseif ($display_field != '') {
        $upd_query = 'INSERT INTO ' . $common_functions->backquote($GLOBALS['cfgRelation']['db']) . '.' . $common_functions->backquote($cfgRelation['table_info'])
                   . '(db_name, table_name, display_field) '
                   . ' VALUES('
                   . '\'' . $common_functions->sqlAddSlashes($db) . '\','
                   . '\'' . $common_functions->sqlAddSlashes($table) . '\','
                   . '\'' . $common_functions->sqlAddSlashes($display_field) . '\')';
    }

    if (isset($upd_query)) {
        $upd_rs    = PMA_queryAsControlUser($upd_query);
    }
} // end if

header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: no-cache");
die("<root act='save_pos' return=__('Modifications have been saved')></root>");
?>
