<?php
/**
 * Handles server charsets and collations page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var CollationsController $controller */
$controller = $containerBuilder->get(CollationsController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);
$response->addHTML($controller->indexAction());
