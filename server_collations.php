<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server charsets and collations page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\server\ServerCollationsController;
use PMA\libraries\Response;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\server\ServerCollationsController'
);
$container->alias(
    'ServerCollationsController',
    'PMA\libraries\controllers\server\ServerCollationsController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/** @var ServerCollationsController $controller */
$controller = $container->get(
    'ServerCollationsController', array()
);
$controller->indexAction();
