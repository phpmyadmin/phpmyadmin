<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles database multi-table querying
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\MultiTableQueryController;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$response = Response::getInstance();

$controller = new MultiTableQueryController(
    $response,
    $GLOBALS['dbi'],
    $db
);

if (isset($_POST['sql_query'])) {
    $controller->displayResults([
        'sql_query' => $_POST['sql_query'],
        'db' => $_REQUEST['db'] ?? null,
    ]);
} elseif (isset($_GET['tables'])) {
    $response->addJSON($controller->table([
        'tables' => $_GET['tables'],
        'db' => $_REQUEST['db'] ?? null,
    ]));
} else {
    $header = $response->getHeader();
    $scripts = $header->getScripts();
    $scripts->addFile('vendor/jquery/jquery.md5.js');
    $scripts->addFile('db_multi_table_query.js');
    $scripts->addFile('db_query_generator.js');

    $response->addHTML($controller->index());
}
