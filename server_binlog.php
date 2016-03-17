<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server binary log page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\server\ServerCollationsController;
use PMA\libraries\Response;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\server\ServerBinlogController'
);
$container->alias(
    'ServerBinlogController',
    'PMA\libraries\controllers\server\ServerBinlogController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/** @var ServerBinlogController $controller */
$controller = $container->get(
    'ServerBinlogController', array()
);
$controller->indexAction();
