<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var Relation $relation */
$relation = $containerBuilder->get('relation');

// If request for creating the pmadb
if (isset($_POST['create_pmadb']) && $relation->createPmaDatabase()) {
    $relation->fixPmaTables('phpmyadmin');
}

// If request for creating all PMA tables.
if (isset($_POST['fixall_pmadb'])) {
    $relation->fixPmaTables($GLOBALS['db']);
}

$cfgRelation = $relation->getRelationsParam();
// If request for creating missing PMA tables.
if (isset($_POST['fix_pmadb'])) {
    $relation->fixPmaTables($cfgRelation['db']);
}

$response->addHTML(
    $relation->getRelationsParamDiagnostic($cfgRelation)
);
