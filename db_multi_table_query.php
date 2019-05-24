<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles database multi-table querying
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\MultiTableQueryController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var MultiTableQueryController $controller */
$controller = $containerBuilder->get(MultiTableQueryController::class);

/** @var Template $template */
$template = $containerBuilder->get('template');

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
    $scripts->addFile('database/multi_table_query.js');
    $scripts->addFile('database/query_generator.js');

    $response->addHTML($controller->index($template));
}
