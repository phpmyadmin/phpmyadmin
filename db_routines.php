<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Routines management.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\Database\RoutinesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var string $db */
$db = $containerBuilder->getParameter('db');

/** @var string $table */
$table = $containerBuilder->getParameter('table');

/** @var CheckUserPrivileges $checkUserPrivileges */
$checkUserPrivileges = $containerBuilder->get('check_user_privileges');
$checkUserPrivileges->getPrivileges();

$_PMA_RTE = 'RTN';

/** @var RoutinesController $controller */
$controller = $containerBuilder->get(RoutinesController::class);

if (! $response->isAjax()) {
    /**
     * Displays the header and tabs
     */
    if (! empty($table) && in_array($table, $dbi->getTables($db))) {
        include_once ROOT_PATH . 'libraries/tbl_common.inc.php';
    } else {
        $table = '';
        include_once ROOT_PATH . 'libraries/db_common.inc.php';

        list(
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,
            $is_show_stats,
            $db_is_system_schema,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos
            ) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
    }
} else {
    /**
     * Since we did not include some libraries, we need
     * to manually select the required database and
     * create the missing $url_query variable
     */
    if (strlen($db) > 0) {
        $dbi->selectDb($db);
        if (! isset($url_query)) {
            $url_query = Url::getCommon(
                [
                    'db' => $db,
                    'table' => $table,
                ]
            );
        }
    }
}

$controller->index([
    'type' => $_REQUEST['type'] ?? null,
]);
