<?php
/**
 * Handles server plugins page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var PluginsController $controller */
$controller = $containerBuilder->get(PluginsController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);
$response->addHTML($controller->index());
