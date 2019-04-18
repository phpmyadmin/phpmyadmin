<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database structure manipulation
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Replication;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/db_common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(StructureController::class);
$container->set(Response::class, Response::getInstance());
$container->alias('response', Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'db' => $db,
    'relation' => new Relation($dbi),
    'replication' => new Replication(),
];

/** @var StructureController $controller */
$controller = $container->get(StructureController::class, $dependency_definitions);

/** @var Response $response */
$response = $container->get(Response::class);

if ($response->isAjax() && ! empty($_REQUEST['favorite_table'])) {
    $json = $controller->addRemoveFavoriteTablesAction([
        'favorite_table' => $_REQUEST['favorite_table'],
        'favorite_tables' => $_REQUEST['favorite_tables'] ?? null,
        'sync_favorite_tables' => $_REQUEST['sync_favorite_tables'] ?? null,
        'add_favorite' => $_REQUEST['add_favorite'] ?? null,
        'remove_favorite' => $_REQUEST['remove_favorite'] ?? null,
    ]);
    if ($json !== null) {
        $response->addJSON($json);
    }
} elseif ($response->isAjax()
    && isset($_REQUEST['real_row_count'])
    && (bool) $_REQUEST['real_row_count'] === true
) {
    $response->addJSON($controller->handleRealRowCountRequestAction([
        'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
        'table' => $_REQUEST['table'] ?? null,
    ]));
} else {
    $response->getHeader()->getScripts()->addFiles([
        'db_structure.js',
        'tbl_change.js',
    ]);

    $response->addHTML($controller->index([
        'submit_mult' => $_POST['submit_mult'] ?? null,
        'selected_tbl' => $_POST['selected_tbl'] ?? null,
        'mult_btn' => $_POST['mult_btn'] ?? null,
        'sort' => $_REQUEST['sort'] ?? null,
        'sort_order' => $_REQUEST['sort_order'] ?? null,
    ]));
}
