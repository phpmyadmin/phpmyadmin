<?php
/**
 * Created by PhpStorm.
 * User: manish
 * Date: 16/7/17
 * Time: 1:26 PM
 */
if (!isset($_POST['type']) && !isset($_POST['uuid'])) {
    exit;
} else {
    include_once 'libraries/common.inc.php';
    include_once 'libraries/relation.lib.php';
    $cfgRelation = PMA_getRelationsParam();
    if (!empty($cfgRelation['progress']) && !empty($cfgRelation['db'])) {
        if ($_POST['type'] == "createexportprogress") {
            $sql_query = 'INSERT INTO ' .
                PhpMyAdmin\Util::backquote($cfgRelation['db']) . '.' .
                PhpMyAdmin\Util::backquote($cfgRelation['progress']) .
                ' VALUES ("' . $_POST['uuid'] . '", "export", "", 0, 1)';
            PMA_queryAsControlUser($sql_query);
        } else if ($_POST['type'] == "updateexportprogress") {
            $sql_query = 'SELECT * FROM ' .
                PhpMyAdmin\Util::backquote($cfgRelation['db']) . '.' .
                PhpMyAdmin\Util::backquote($cfgRelation['progress']) .
                ' WHERE type = "export" AND uuid = "' . $_POST['uuid'] . '"';
            $result = PMA_queryAsControlUser($sql_query);
            $result = $GLOBALS['dbi']->fetchRow($result);
            echo json_encode($result);
        } else if ($_POST['type'] == "deleteexportprogress") {
            $sql_query = 'DELETE FROM ' .
                PhpMyAdmin\Util::backquote($cfgRelation['db']) . '.' .
                PhpMyAdmin\Util::backquote($cfgRelation['progress']) .
                ' WHERE type = "export" AND uuid = "' . $_POST['uuid'] . '"';
            PMA_queryAsControlUser($sql_query);
        }
    }
}
?>