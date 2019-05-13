<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

$header = $response->getHeader();
$header->disableMenuAndConsole();

/** @var TransformationOverviewController $controller */
$controller = $containerBuilder->get(TransformationOverviewController::class);

$response->addHTML($controller->indexAction());
