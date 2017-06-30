<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table/Column autocomplete in SQL editors
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Response;
require_once 'libraries/common.inc.php';

if ($GLOBALS['cfg']['EnableAutocompleteForTablesAndColumns']) {
    $db = isset($_POST['db']) ? $_POST['db'] : $GLOBALS['db'];
    $sql_autocomplete = array();
    if ($db) {
        $tableNames = $GLOBALS['dbi']->getTables($db);
        foreach ($tableNames as $tableName) {
            $sql_autocomplete[$tableName] = $GLOBALS['dbi']->getColumns(
                $db, $tableName
            );
        }
    }
} else {
    $sql_autocomplete = true;
}
$response = Response::getInstance();
$response->addJSON("tables", json_encode($sql_autocomplete));
