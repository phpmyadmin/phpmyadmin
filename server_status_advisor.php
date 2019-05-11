<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the advisor feature
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

$scripts = $response->getHeader()->getScripts();
$scripts->addFile('server_status_advisor.js');

$containerBuilder->set('status_data', new Data());
/** @var AdvisorController $controller */
$controller = $containerBuilder->get('server_status_advisor_controller');

$response->addHTML($controller->index());
