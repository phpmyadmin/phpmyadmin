<?php
/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$header = $response->getHeader();
$header->disableMenuAndConsole();

/** @var TransformationOverviewController $controller */
$controller = $containerBuilder->get(TransformationOverviewController::class);

$response->addHTML($controller->indexAction());
