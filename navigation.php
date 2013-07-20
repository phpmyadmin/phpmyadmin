<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */

// Include common functionalities
require_once './libraries/common.inc.php';

// Also initialises the collapsible tree class
require_once './libraries/navigation/Navigation.class.php';

$cfgRelation = PMA_getRelationsParam();
if (isset($_REQUEST['hideNavItem']) && $cfgRelation['navwork']) {
    if (! empty($_REQUEST['itemName']) && ! empty($_REQUEST['itemType'])) {
        $navTable = PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['navigation']);
        $sqlQuery = "INSERT INTO " . $navTable
            . "(`username`, `item_name`, `item_type`, `db_name`, `table_name`)"
            . " VALUES ("
            . "'" . $GLOBALS['cfg']['Server']['user'] . "',"
            . "'" . $_REQUEST['itemName'] . "',"
            . "'" . $_REQUEST['itemType'] . "',"
            . "'" . (! empty($_REQUEST['dbName']) ? $_REQUEST['dbName'] : "") . "',"
            . "'" . (! empty($_REQUEST['tableName']) ? $_REQUEST['tableName'] : "")
            . "')";
        PMA_queryAsControlUser($sqlQuery, true);
    }
    exit;
}

// Do the magic
$response = PMA_Response::getInstance();
if ($response->isAjax()) {
    $navigation = new PMA_Navigation();
    $response->addJSON('message', $navigation->getDisplay());
} else {
    $response->addHTML(
        PMA_Message::error(
            __('Fatal error: The navigation can only be accessed via AJAX')
        )
    );
}
?>
