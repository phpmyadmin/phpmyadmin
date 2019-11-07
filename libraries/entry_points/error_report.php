<?php
/**
 * Handle error report submission
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\ErrorReportController;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var ErrorReportController $controller */
$controller = $containerBuilder->get(ErrorReportController::class);
$controller->index();
