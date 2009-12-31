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

$cfgRelation = PMA_getRelationsParam();

if (! $cfgRelation['designerwork']) {
    PMD_err_sav();
}

foreach ($t_x as $key => $value) {
    $KEY = empty($IS_AJAX) ? urldecode($key) : $key; // table name decode (post PDF exp/imp)
    list($DB,$TAB) = explode(".", $KEY);
    PMA_query_as_controluser('DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']) . '
                      WHERE `db_name` = \'' . PMA_sqlAddslashes($DB) . '\'
                        AND `table_name` = \'' . PMA_sqlAddslashes($TAB) . '\'', true, PMA_DBI_QUERY_STORE);

    PMA_query_as_controluser('INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']) . '
                         (db_name, table_name, x, y, v, h)
                  VALUES ('
                  . '\'' . PMA_sqlAddslashes($DB) . '\', '
                  . '\'' . PMA_sqlAddslashes($TAB) . '\', '
                  . '\'' . PMA_sqlAddslashes($t_x[$key]) . '\', '
                  . '\'' . PMA_sqlAddslashes($t_y[$key]) . '\', '
                  . '\'' . PMA_sqlAddslashes($t_v[$key]) . '\', '
                  . '\'' . PMA_sqlAddslashes($t_h[$key]) . '\''
                  . ')', true, PMA_DBI_QUERY_STORE);
}
//----------------------------------------------------------------------------

function PMD_err_sav() {
    global $die_save_pos; // if this file included
    if (! empty($die_save_pos)) {
        header("Content-Type: text/xml; charset=utf-8");
        header("Cache-Control: no-cache");
        die('<root act="save_pos" return="strErrorSaveTable"></root>');
    }
}

if(! empty($die_save_pos)) {
  header("Content-Type: text/xml; charset=utf-8");
  header("Cache-Control: no-cache");
?>
<root act='save_pos' return='<?php echo 'strModifications'; ?>'></root>
<?php
}
?>
