<?php
/**
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\StructureController;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var StructureController $controller */
$controller = $containerBuilder->get(StructureController::class);
$controller->index();
