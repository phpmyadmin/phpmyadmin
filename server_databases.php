<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server databases page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var DatabasesController $controller */
$controller = $containerBuilder->get(DatabasesController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$checkUserPrivileges = new CheckUserPrivileges($dbi);
$checkUserPrivileges->getPrivileges();

if (isset($_POST['drop_selected_dbs'])
    && $response->isAjax()
    && ($dbi->isSuperuser() || $GLOBALS['cfg']['AllowUserDropDatabase'])
) {
    $response->addJSON($controller->dropDatabasesAction([
        'drop_selected_dbs' => $_POST['drop_selected_dbs'],
        'selected_dbs' => $_POST['selected_dbs'] ?? null,
    ]));
} elseif (isset($_POST['new_db'])
    && $response->isAjax()
) {
    $response->addJSON($controller->createDatabaseAction([
        'new_db' => $_POST['new_db'],
        'db_collation' => $_POST['db_collation'] ?? null,
    ]));
} else {
    $header = $response->getHeader();
    $scripts = $header->getScripts();
    $scripts->addFile('server/databases.js');

    $response->addHTML($controller->indexAction([
        'statistics' => $_REQUEST['statistics'] ?? null,
        'pos' => $_REQUEST['pos'] ?? null,
        'sort_by' => $_REQUEST['sort_by'] ?? null,
        'sort_order' => $_REQUEST['sort_order'] ?? null,
    ]));
}
