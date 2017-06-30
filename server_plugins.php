<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server plugins page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\server\ServerPluginsController;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = \PMA\libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\server\ServerPluginsController'
);
$container->alias(
    'ServerPluginsController',
    'PMA\libraries\controllers\server\ServerPluginsController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerPluginsController $controller */
$controller = $container->get(
    'ServerPluginsController', array()
);
$controller->indexAction();
