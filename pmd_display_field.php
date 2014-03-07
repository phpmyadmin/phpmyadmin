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

$table = $_POST['T'];
$display_field = $_POST['F'];

if ($cfgRelation['displaywork']) {

    $disp     = PMA_getDisplayField($db, $table);
    if ($disp) {
        if ($display_field != $disp) {
            $upd_query = 'UPDATE '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.'
                . PMA_Util::backquote($cfgRelation['table_info'])
                . ' SET display_field = \''
                . PMA_Util::sqlAddSlashes($display_field) . '\''
                . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        } else {
            $upd_query = 'DELETE FROM '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.'
                . PMA_Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        }
    } elseif ($display_field != '') {
        $upd_query = 'INSERT INTO '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.'
            . PMA_Util::backquote($cfgRelation['table_info'])
            . '(db_name, table_name, display_field) '
            . ' VALUES('
            . '\'' . PMA_Util::sqlAddSlashes($db) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($table) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($display_field) . '\')';
    }

    if (isset($upd_query)) {
        $upd_rs    = PMA_queryAsControlUser($upd_query);
    }
} // end if

header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: no-cache");
die("<root act='save_pos' return='"
    . __('Modifications have been saved') . "'></root>");
?>
