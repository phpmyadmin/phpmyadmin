<?php
/**
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\ReplaceController;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var ReplaceController $controller */
$controller = $containerBuilder->get(ReplaceController::class);
$controller->index();
