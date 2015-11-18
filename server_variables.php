<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server variables page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\server\ServerVariablesController;
use PMA\libraries\Response;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\server\ServerVariablesController'
);
$container->alias(
    'ServerVariablesController',
    'PMA\libraries\controllers\server\ServerVariablesController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/** @var ServerVariablesController $controller */
$controller = $container->get(
    'ServerVariablesController', array()
);
$controller->indexAction();
