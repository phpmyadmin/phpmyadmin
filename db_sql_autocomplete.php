<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table/Column autocomplete in SQL editors
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

if ($GLOBALS['cfg']['EnableAutocompleteForTablesAndColumns']) {
    $db = isset($_POST['db']) ? $_POST['db'] : $GLOBALS['db'];
    $sql_autocomplete = [];
    if ($db) {
        $tableNames = $dbi->getTables($db);
        foreach ($tableNames as $tableName) {
            $sql_autocomplete[$tableName] = $dbi->getColumns(
                $db,
                $tableName
            );
        }
    }
} else {
    $sql_autocomplete = true;
}

$response->addJSON("tables", json_encode($sql_autocomplete));
