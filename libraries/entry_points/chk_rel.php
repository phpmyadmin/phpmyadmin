<?php
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var CheckRelationsController $controller */
$controller = $containerBuilder->get(CheckRelationsController::class);

$response->addHTML($controller->index([
    'create_pmadb' => $_POST['create_pmadb'] ?? null,
    'fixall_pmadb' => $_POST['fixall_pmadb'] ?? null,
    'fix_pmadb' => $_POST['fix_pmadb'] ?? null,
]));
