<?php
/**
 * displays the advisor feature
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

require_once ROOT_PATH . 'libraries/replication.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$scripts = $response->getHeader()->getScripts();
$scripts->addFile('server/status/advisor.js');

/** @var AdvisorController $controller */
$controller = $containerBuilder->get(AdvisorController::class);

$response->addHTML($controller->index());
