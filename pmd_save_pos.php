<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once 'libraries/pmd_common.php';

$cfgRelation = PMA_getRelationsParam();

if (! $cfgRelation['designerwork']) {
    PMD_err_sav();
}

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'IS_AJAX',
    'die_save_pos',
    'server',
    't_h',
    't_v',
    't_x',
    't_y'
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

foreach ($t_x as $key => $value) {
    // table name decode (post PDF exp/imp)
    $KEY = empty($IS_AJAX) ? urldecode($key) : $key;
    list($DB,$TAB) = explode(".", $KEY);
    PMA_queryAsControlUser(
        'DELETE FROM ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
        . '.' . PMA_Util::backquote($GLOBALS['cfgRelation']['designer_coords'])
        . ' WHERE `db_name` = \'' . PMA_Util::sqlAddSlashes($DB) . '\''
        . ' AND `table_name` = \'' . PMA_Util::sqlAddSlashes($TAB) . '\'',
        true, PMA_DBI_QUERY_STORE
    );

    PMA_queryAsControlUser(
        'INSERT INTO ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
        . '.' . PMA_Util::backquote($GLOBALS['cfgRelation']['designer_coords'])
        . ' (db_name, table_name, x, y, v, h)'
        . ' VALUES ('
        . '\'' . PMA_Util::sqlAddSlashes($DB) . '\', '
        . '\'' . PMA_Util::sqlAddSlashes($TAB) . '\', '
        . '\'' . PMA_Util::sqlAddSlashes($t_x[$key]) . '\', '
        . '\'' . PMA_Util::sqlAddSlashes($t_y[$key]) . '\', '
        . '\'' . PMA_Util::sqlAddSlashes($t_v[$key]) . '\', '
        . '\'' . PMA_Util::sqlAddSlashes($t_h[$key]) . '\')',
        true, PMA_DBI_QUERY_STORE
    );
}
//----------------------------------------------------------------------------

function PMD_err_sav()
{
    global $die_save_pos; // if this file included
    if (! empty($die_save_pos)) {
        header("Content-Type: text/xml; charset=utf-8");
        header("Cache-Control: no-cache");
        die('<root act="save_pos" return="' . __('Error saving coordinates for Designer.') . '"></root>');
    }
}

if (! empty($die_save_pos)) {
    header("Content-Type: text/xml; charset=utf-8");
    header("Cache-Control: no-cache");
    ?>
    <root act='save_pos' return='<?php echo __('Modifications have been saved'); ?>'></root>
    <?php
}
?>
