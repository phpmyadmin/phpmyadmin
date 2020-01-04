<?php
/**
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SqlController;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var SqlController $controller */
$controller = $containerBuilder->get(SqlController::class);
$controller->index();
