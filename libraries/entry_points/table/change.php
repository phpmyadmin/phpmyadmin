<?php
/**
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\ChangeController;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var ChangeController $controller */
$controller = $containerBuilder->get(ChangeController::class);
$controller->index();
