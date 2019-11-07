<?php
/**
 * Editor for Geometry data types.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var GisDataEditorController $controller */
$controller = $containerBuilder->get(GisDataEditorController::class);

$response->addJSON($controller->index());
