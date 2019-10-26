<?php
/**
 * Renders data dictionary
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\DataDictionaryController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

Util::checkParameters(['db']);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DataDictionaryController $controller */
$controller = $containerBuilder->get(DataDictionaryController::class);

$header = $response->getHeader();
$header->enablePrintView();

$response->addHTML($controller->index());
