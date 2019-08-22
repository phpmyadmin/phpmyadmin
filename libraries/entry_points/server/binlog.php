<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server binary log page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var BinlogController $controller */
$controller = $containerBuilder->get(BinlogController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

$response->addHTML($controller->indexAction([
    'log' => $_POST['log'] ?? null,
    'pos' => $_POST['pos'] ?? null,
    'is_full_query' => $_POST['is_full_query'] ?? null,
]));
