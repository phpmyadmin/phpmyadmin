<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server charsets and collations page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PhpMyAdmin\Controllers\Server\ServerCollationsController;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
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
    'ServerCollationsController', array()
);
$controller->indexAction();
