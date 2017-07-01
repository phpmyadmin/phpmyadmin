<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server engines page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\server\ServerEnginesController;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\server\ServerEnginesController'
);
$container->alias(
    'ServerEnginesController',
    'PMA\libraries\controllers\server\ServerEnginesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerEnginesController $controller */
$controller = $container->get(
    'ServerEnginesController', array()
);
$controller->indexAction();
