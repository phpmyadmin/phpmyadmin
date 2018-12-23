<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server charsets and collations page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\ServerCollationsController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Server\ServerCollationsController'
);
$container->alias(
    'ServerCollationsController',
    'PhpMyAdmin\Controllers\Server\ServerCollationsController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerCollationsController $controller */
$controller = $container->get(
    'ServerCollationsController',
    []
);
$controller->indexAction();
