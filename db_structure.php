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
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/db_common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var StructureController $controller */
$controller = $containerBuilder->get(StructureController::class);

if ($response->isAjax() && ! empty($_REQUEST['favorite_table'])) {
    $json = $controller->addRemoveFavoriteTablesAction([
        'favorite_table' => $_REQUEST['favorite_table'],
        'favoriteTables' => $_REQUEST['favoriteTables'] ?? null,
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
        'database/structure.js',
        'table/change.js',
    ]);

    $response->addHTML($controller->index([
        'submit_mult' => $_POST['submit_mult'] ?? null,
        'selected_tbl' => $_POST['selected_tbl'] ?? null,
        'mult_btn' => $_POST['mult_btn'] ?? null,
        'sort' => $_REQUEST['sort'] ?? null,
        'sort_order' => $_REQUEST['sort_order'] ?? null,
    ]));
}
